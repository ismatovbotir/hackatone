<?php

namespace App\Services\Media;

use App\Support\ActivityMedia;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Text-to-video through Hugging Face Inference Providers (Replicate-style predictions, billed to the HF token,
 * which includes monthly free credits). A generation is started, then checked until it finishes; the admin page
 * polls, so no request runs longer than shared hosting allows.
 */
class VideoGenerator
{
    /** Hosts a finished video may be downloaded from. Anything else is refused. */
    private const DOWNLOAD_HOSTS = ['replicate.delivery', 'huggingface.co'];

    /** Added to every prompt so results stay suitable for children. */
    public const SAFETY = 'Safe for children: cartoon illustration only, no real people, no realistic faces, no nudity or revealing clothes, no violence or injury, no weapons, no text, no logos, no brands.';

    public function configured(): bool
    {
        return filled(config('services.huggingface.token'));
    }

    /** @return array{id: string, status: string, output: ?string, error: ?string} */
    public function start(string $prompt): array
    {
        $response = $this->http()
            ->withHeaders(['Prefer' => 'wait=20'])
            ->post('v1/models/'.config('services.huggingface.video_model').'/predictions', [
                'input' => ['prompt' => trim($prompt).' '.self::SAFETY],
            ]);

        return $this->result($response);
    }

    /** @return array{id: string, status: string, output: ?string, error: ?string} */
    public function check(string $id): array
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,100}$/', $id)) {
            throw new RuntimeException('Bad job id.');
        }

        return $this->result($this->http()->get('v1/predictions/'.$id));
    }

    /**
     * Download a finished video, checking where it comes from, its real type and its size.
     *
     * @return array{bytes: string, type: string}
     */
    public function download(string $url): array
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = collect(self::DOWNLOAD_HOSTS)->contains(fn ($h) => $host === $h || str_ends_with($host, '.'.$h));
        if (($parts['scheme'] ?? '') !== 'https' || ! $allowed) {
            throw new RuntimeException('The result came from a host that is not allowed.');
        }

        $max = config('hh.ai_media_max_kb') * 1024;
        $response = Http::withOptions(['allow_redirects' => false])->timeout(30)->get($url);
        $bytes = $response->successful() ? $response->body() : '';

        if ($bytes === '' || strlen($bytes) > $max) {
            throw new RuntimeException('The result is empty or too large.');
        }

        $type = ActivityMedia::typeOf($bytes);
        if ($type === null) {
            throw new RuntimeException('The result is not an MP4 or GIF file.');
        }

        return ['bytes' => $bytes, 'type' => $type];
    }

    private function result(Response $response): array
    {
        $json = $response->json() ?? [];

        if (! $response->successful() || ! isset($json['id'])) {
            $message = $json['error'] ?? $json['detail'] ?? $json['message'] ?? ('HTTP '.$response->status());
            throw new RuntimeException(is_string($message) ? mb_substr($message, 0, 200) : 'Request failed.');
        }

        $output = $json['output'] ?? null;

        return [
            'id' => (string) $json['id'],
            'status' => (string) ($json['status'] ?? 'starting'), // starting, processing, succeeded, failed, canceled
            'output' => is_array($output) ? ($output[0] ?? null) : $output,
            'error' => isset($json['error']) && is_string($json['error']) ? mb_substr($json['error'], 0, 200) : null,
        ];
    }

    private function http(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('Hugging Face token is not set.');
        }

        return Http::baseUrl(rtrim(config('services.huggingface.router'), '/').'/')
            ->withToken(config('services.huggingface.token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(25);
    }
}
