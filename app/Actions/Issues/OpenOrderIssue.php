<?php

namespace App\Actions\Issues;

use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\Setting;
use App\Models\User;
use App\Services\Orders\IssueEligibility;
use Illuminate\Support\Str;

class OpenOrderIssue
{
    public function __construct(private IssueEligibility $rules) {}

    /**
     * @param Order $order
     * @param array<int, array{order_item_id: int, quantity: int, issue_type: string, note?: string}> $lines
     * @param string $description
     * @param array $photos
     * @param string $channel
     * @param User|null $staff
     * @param string|null $overrideReason
     * @param string $kind
     * @return OrderIssue
     */
    public function handle(
        Order $order,
        array $lines,
        string $description,
        array $photos = [],
        string $channel = 'website',
        ?User $staff = null,
        ?string $overrideReason = null,
        string $kind = 'problem'
    ): OrderIssue {
        abort_if(empty($lines), 422, 'Please select at least one affected item from the order.');

        $withinWindow = true;
        foreach ($lines as $line) {
            $item = $order->items()->findOrFail($line['order_item_id']);
            $check = $this->rules->check($item, $line['issue_type']);
            if (! $check['ok'] && ! ($staff && $overrideReason)) {
                $withinWindow = false;
            }
        }

        $responseHours = (int) Setting::get('issue_response_hours', 24);

        $issue = OrderIssue::create([
            'reference' => 'IS-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'kind' => $kind,
            'channel' => $channel,
            'status' => OrderIssue::STATUS_NEW,
            'description' => $description,
            'within_window' => $withinWindow,
            'override_reason' => $overrideReason,
            'reported_at' => now(),
            'due_at' => now()->addHours($responseHours),
            'item_instruction' => $kind === 'safety' ? 'keep' : 'none',
        ]);

        foreach ($lines as $line) {
            $issue->items()->create($line);
        }

        foreach ($photos as $path) {
            $issue->photos()->create([
                'path' => $path,
                'uploaded_by' => $staff?->id,
            ]);
        }

        $issue->events()->create([
            'type' => 'opened',
            'actor' => $staff ? 'staff' : 'customer',
            'note' => "Reported via {$channel}",
        ]);

        return $issue;
    }
}
