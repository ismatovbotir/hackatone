<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::panel')]
class Clients extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $telegram = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'telegram'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $q = trim($this->q);
        $digits = preg_replace('/\D/', '', $q);

        $clients = Client::with(['user', 'latestMeasurement'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"))
                ->when(strlen($digits) >= 3, fn ($w) => $w->orWhere('phone', 'like', "%{$digits}%"))))
            ->when($this->telegram === 'linked', fn ($query) => $query->whereNotNull('telegram_chat_id'))
            ->when($this->telegram === 'not', fn ($query) => $query->whereNull('telegram_chat_id'))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.clients', ['clients' => $clients])->title(t('admin.clients'));
    }
}
