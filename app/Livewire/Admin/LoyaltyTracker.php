<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\Loyalty\SquareLoyalty;
use App\Services\Square\SquareGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * The website's side of Square Loyalty: whether it's connected, what
 * website orders earned and spent, anything still waiting to reach
 * Square, and a lookup of any customer's points by phone. Full loyalty
 * reports (in-store too) stay in the Square Dashboard.
 */
class LoyaltyTracker extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $lookupPhone = '';

    /** @var array{phone: string, points: ?int, member: bool, history: array}|null */
    public ?array $lookup = null;

    public ?string $lookupError = null;

    public ?string $syncMessage = null;

    public function lookUp(SquareLoyalty $loyalty, SquareGateway $square): void
    {
        $this->reset('lookup', 'lookupError');
        $phone = SquareLoyalty::phone($this->lookupPhone);

        if (! $phone) {
            $this->lookupError = 'Enter a phone number with area code.';

            return;
        }

        if (! $loyalty->enabled()) {
            $this->lookupError = 'Square Loyalty isn’t connected yet.';

            return;
        }

        try {
            $account = $square->findLoyaltyAccount($phone);
            $this->lookup = [
                'phone' => $phone,
                'member' => $account !== null,
                'points' => $account['balance'] ?? null,
                'lifetime' => $account['lifetime_points'] ?? null,
                'since' => isset($account['enrolled_at']) ? Carbon::parse($account['enrolled_at'])->timezone(config('shop.timezone'))->format('j M Y') : null,
            ];
        } catch (Throwable $e) {
            report($e);
            $this->lookupError = 'Square could not be reached. Try again in a minute.';
        }
    }

    public function syncNow(): void
    {
        try {
            Artisan::call('loyalty:sync');
            $this->syncMessage = trim(Artisan::output());
        } catch (Throwable $e) {
            report($e);
            $this->syncMessage = 'The retry failed: '.$e->getMessage();
        }
    }

    public function render(SquareLoyalty $loyalty)
    {
        $enabled = $loyalty->enabled();
        $program = $enabled ? $loyalty->program() : null;
        $since = now()->subDays(30);
        $recent = Order::where('placed_at', '>=', $since);

        return view('livewire.admin.loyalty-tracker', [
            'enabled' => $enabled,
            'switchedOff' => ! config('loyalty.enabled'),
            'env' => SquareGateway::payments()['env'],
            'tier' => $program['tier'] ?? null,
            'stats' => [
                'orders' => (clone $recent)->where('loyalty_points', '>', 0)->count(),
                'points' => (int) (clone $recent)->sum('loyalty_points') - (int) (clone $recent)->sum('loyalty_points_reversed'),
                'members' => (clone $recent)->whereNotNull('loyalty_account_id')->distinct()->count('loyalty_account_id'),
                'rewards' => (clone $recent)->where('loyalty_reward_status', SquareLoyalty::REWARD_REDEEMED)->count(),
                'waiting' => $enabled ? Order::whereNull('loyalty_synced_at')
                    ->where('placed_at', '>=', now()->subDays((int) config('loyalty.retry_days')))
                    ->where(fn ($q) => $q->where('status', Order::STATUS_PAID)
                        ->orWhere(fn ($q) => $q->where('status', Order::STATUS_PLACED)->where('fulfilment_status', Order::FULFILMENT_COMPLETED)))
                    ->count() : 0,
            ],
            'orders' => Order::where(fn ($q) => $q->whereNotNull('loyalty_account_id')->orWhereNotNull('loyalty_reward_id'))
                ->latest('placed_at')
                ->paginate(20),
        ])->layout('layouts.admin', ['header' => 'Loyalty']);
    }
}
