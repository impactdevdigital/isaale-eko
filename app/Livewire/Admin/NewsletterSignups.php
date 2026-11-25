<?php

namespace App\Livewire\Admin;

use App\Models\Consent;
use App\Services\ConsentService;
use App\Services\Marketing\Brevo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Newsletter sign-ups (email consents): who signed up, from which form and
 * page, and whether they reached the Brevo list. Store managers only.
 */
class NewsletterSignups extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $source = '';

    /** active | unsubscribed | all */
    #[Url(except: 'active')]
    public string $status = 'active';

    public ?string $notice = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'source', 'status'], true)) {
            $this->resetPage();
        }
    }

    /** Send sign-ups that haven't reached Brevo yet (the hourly job does this too). */
    public function syncNow(Brevo $brevo): void
    {
        abort_unless(auth()->user()?->isManager(), 403);

        if (! $brevo->enabled()) {
            $this->notice = 'Brevo is not connected yet, so there is nothing to send.';

            return;
        }

        Artisan::call('brevo:sync');
        $this->notice = trim(Artisan::output());
    }

    public function export(): StreamedResponse
    {
        abort_unless(auth()->user()?->isManager(), 403);

        $rows = $this->query()->oldest('granted_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Signed up', 'First name', 'Last name', 'Email', 'Source', 'Page', 'Status', 'In Brevo']);

            foreach ($rows as $c) {
                [$first, $last] = Brevo::splitName($c->name ?: $c->user?->name);
                fputcsv($out, [
                    $c->granted_at?->timezone(config('shop.timezone'))->format('Y-m-d H:i'),
                    $first, $last, $c->email ?: $c->user?->email,
                    ConsentService::sourceLabel($c->source), $c->page,
                    $c->revoked_at ? 'Unsubscribed '.$c->revoked_at->timezone(config('shop.timezone'))->format('Y-m-d') : 'Subscribed',
                    $c->brevo_synced_at ? 'Yes' : 'No',
                ]);
            }

            fclose($out);
        }, 'newsletter-signups-'.now(config('shop.timezone'))->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(): Builder
    {
        return Consent::query()
            ->with('user:id,name,email')
            ->where('channel', 'email')
            ->when($this->status === 'active', fn ($q) => $q->whereNull('revoked_at'))
            ->when($this->status === 'unsubscribed', fn ($q) => $q->whereNotNull('revoked_at'))
            ->when($this->source !== '', fn ($q) => $q->where('source', $this->source))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.mb_strtolower(trim($this->search)).'%';
                $q->where(fn ($w) => $w->whereRaw('lower(email) like ?', [$term])->orWhereRaw('lower(name) like ?', [$term]));
            });
    }

    public function render(Brevo $brevo)
    {
        $active = Consent::where('channel', 'email')->whereNull('revoked_at');

        return view('livewire.admin.newsletter-signups', [
            'signups' => $this->query()->latest('granted_at')->latest('id')->paginate(25),
            'total' => (clone $active)->count(),
            'last7' => (clone $active)->where('granted_at', '>=', now()->subDays(7))->count(),
            'unsynced' => (clone $active)->whereNull('brevo_synced_at')->whereNotNull('email')->count(),
            'bySource' => (clone $active)->selectRaw('source, count(*) as total')->groupBy('source')->orderByDesc('total')->pluck('total', 'source'),
            'sources' => Consent::where('channel', 'email')->distinct()->orderBy('source')->pluck('source'),
            'brevoConnected' => $brevo->enabled(),
        ])->layout('layouts.admin', ['header' => 'Newsletter sign-ups']);
    }
}
