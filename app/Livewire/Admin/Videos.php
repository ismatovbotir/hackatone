<?php

namespace App\Livewire\Admin;

use App\Models\ActivityVideo;
use App\Services\Health\ActivityVideos;
use App\Services\Media\VideoGenerator;
use App\Support\ActivityMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Example GIF per activity: upload one (published at once) or generate one with AI.
 * AI results are drafts (usually MP4): an admin must watch them, and on Publish the admin's browser
 * converts the draft to a small GIF and uploads it. Clients only ever get GIFs.
 */
#[Layout('layouts::panel')]
class Videos extends Component
{
    use WithFileUploads;

    /** @var array<string, TemporaryUploadedFile|null> */
    public array $files = [];

    /** GIF made in the browser from an MP4 draft, uploaded on Publish. */
    public $gif = null;

    /** @var array<string, string> key => prompt */
    public array $prompts = [];

    public function mount(ActivityVideos $videos): void
    {
        foreach ($videos->all() as $key => $v) {
            $this->prompts[$key] = $v['prompt'];
        }
    }

    public function savePrompt(string $key, ActivityVideos $videos): void
    {
        $this->assertKey($key);
        $this->validate(["prompts.$key" => ['required', 'string', 'max:2000']]);

        $prompt = trim($this->prompts[$key]);
        // Store only edited prompts so the built-in text can improve later.
        ActivityVideo::updateOrCreate(['key' => $key], ['prompt' => $prompt === $videos->defaultPrompt($key) ? null : $prompt]);
        session()->flash('status', t('vid.saved'));
    }

    public function resetPrompt(string $key, ActivityVideos $videos): void
    {
        $this->assertKey($key);
        $this->prompts[$key] = $videos->defaultPrompt($key);
    }

    /** An admin's own upload is published straight away. */
    public function upload(string $key): void
    {
        $this->assertKey($key);
        $this->validate(
            ["files.$key" => ['required', 'file', 'mimes:gif', 'max:'.config('hh.media_max_kb')]],
            ["files.$key.*" => t('e.media_file', (int) (config('hh.media_max_kb') / 1024))],
        );

        $file = $this->files[$key];
        $type = ActivityMedia::typeOf((string) file_get_contents($file->getRealPath()));
        if ($type !== 'gif') {
            $this->addError("files.$key", t('e.media_file', (int) (config('hh.media_max_kb') / 1024)));

            return;
        }

        $path = $file->storeAs('activity', $key.'-'.Str::random(8).'.'.$type, 'uploads');
        $this->publishFile($key, $path, $type);
        $this->files[$key] = null;
        session()->flash('status', t('vid.saved'));
    }

    public function generate(string $key, VideoGenerator $generator, ActivityVideos $videos): void
    {
        $this->assertKey($key);
        $row = ActivityVideo::firstOrNew(['key' => $key]);
        if ($row->generating()) {
            return;
        }

        $prompt = trim($this->prompts[$key] ?? '') ?: $videos->defaultPrompt($key);

        try {
            $job = $generator->start($prompt);
            $row->fill(['job_id' => $job['id'], 'job_started_at' => now(), 'job_error' => null])->save();
            $this->handle($row, $job, $generator); // short clips can finish within the first request
        } catch (Throwable $e) {
            $row->fill(['job_id' => null, 'job_error' => mb_substr($e->getMessage(), 0, 250)])->save();
        }
    }

    /** Polled by the page while a generation is running. */
    public function checkJobs(VideoGenerator $generator): void
    {
        foreach (ActivityVideo::whereNotNull('job_id')->get() as $row) {
            try {
                $this->handle($row, $generator->check($row->job_id), $generator);
            } catch (Throwable $e) {
                $row->fill(['job_id' => null, 'job_error' => mb_substr($e->getMessage(), 0, 250)])->save();
            }
        }
    }

    /** A GIF draft is published as it is; an MP4 draft goes through publishDraftAsGif. */
    public function publishDraft(string $key): void
    {
        $this->assertKey($key);
        $row = ActivityVideo::find($key);
        if (! $row?->draft_path || $row->draft_type !== 'gif') {
            return;
        }

        $this->publishFile($key, $row->draft_path, $row->draft_type);
        $row->refresh()->fill(['draft_path' => null, 'draft_type' => null])->save();
        session()->flash('status', t('vid.published'));
    }

    /** Publish the GIF the admin's browser made from the MP4 draft, and delete the draft. */
    public function publishDraftAsGif(string $key): void
    {
        $this->assertKey($key);
        $row = ActivityVideo::find($key);
        if (! $row?->draft_path) {
            return;
        }

        $this->validate(
            ['gif' => ['required', 'file', 'mimes:gif', 'max:'.config('hh.media_max_kb')]],
            ['gif.*' => t('e.media_file', (int) (config('hh.media_max_kb') / 1024))],
        );
        if (ActivityMedia::typeOf((string) file_get_contents($this->gif->getRealPath())) !== 'gif') {
            $this->addError('gif', t('e.media_file', (int) (config('hh.media_max_kb') / 1024)));

            return;
        }

        $path = $this->gif->storeAs('activity', $key.'-'.Str::random(8).'.gif', 'uploads');
        $this->publishFile($key, $path, 'gif');
        Storage::disk('uploads')->delete($row->draft_path);
        $row->refresh()->fill(['draft_path' => null, 'draft_type' => null])->save();
        $this->gif = null;
        session()->flash('status', t('vid.published'));
    }

    public function discardDraft(string $key): void
    {
        $this->assertKey($key);
        $row = ActivityVideo::find($key);
        if ($row?->draft_path) {
            Storage::disk('uploads')->delete($row->draft_path);
            $row->fill(['draft_path' => null, 'draft_type' => null])->save();
        }
    }

    public function removeMedia(string $key): void
    {
        $this->assertKey($key);
        $row = ActivityVideo::find($key);
        if ($row?->media_path) {
            Storage::disk('uploads')->delete($row->media_path);
            $row->fill(['media_path' => null, 'media_type' => null])->save();
        }
    }

    /** Act on a job result: keep waiting, store the draft, or record the error. */
    private function handle(ActivityVideo $row, array $job, VideoGenerator $generator): void
    {
        if (in_array($job['status'], ['starting', 'processing'], true)) {
            // Give up after 10 minutes so a lost job does not block the button forever.
            if ($row->job_started_at?->lt(now()->subMinutes(10))) {
                $row->fill(['job_id' => null, 'job_error' => t('vid.timeout')])->save();
            }

            return;
        }

        if ($job['status'] !== 'succeeded' || ! $job['output']) {
            $row->fill(['job_id' => null, 'job_error' => $job['error'] ?: t('vid.failed')])->save();

            return;
        }

        $file = $generator->download($job['output']);
        $path = 'activity/'.$row->key.'-ai-'.Str::random(8).'.'.$file['type'];
        Storage::disk('uploads')->put($path, $file['bytes']);

        if ($row->draft_path) {
            Storage::disk('uploads')->delete($row->draft_path);
        }
        $row->fill(['draft_path' => $path, 'draft_type' => $file['type'], 'job_id' => null, 'job_error' => null])->save();
    }

    private function publishFile(string $key, string $path, string $type): void
    {
        $row = ActivityVideo::firstOrNew(['key' => $key]);
        if ($row->media_path && $row->media_path !== $path) {
            Storage::disk('uploads')->delete($row->media_path);
        }
        $row->fill(['media_path' => $path, 'media_type' => $type])->save();
    }

    private function assertKey(string $key): void
    {
        abort_unless(array_key_exists($key, $this->prompts), 404);
    }

    public function render(ActivityVideos $videos, VideoGenerator $generator)
    {
        $items = $videos->all();

        return view('livewire.admin.videos', [
            'items' => $items,
            'aiReady' => $generator->configured(),
            'anyRunning' => $items->contains(fn ($i) => $i['row']?->generating()),
        ])->title(t('admin.videos'));
    }
}
