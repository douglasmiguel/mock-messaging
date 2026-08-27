<?php

namespace App\Http\Controllers;

use App\Models\ActionToken;
use App\Models\ActionTokenAudit;
use App\Models\Issue;
use App\Models\OrderSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ClientOrderController extends Controller
{
    public function show(string $token): View
    {
        [, $order] = $this->resolveToken($token);

        return view('client-orders.show', compact('token', 'order'));
    }

    public function cancel(string $token): RedirectResponse
    {
        [$token, $order] = $this->resolveToken($token);
        $response = Http::acceptJson()
            ->withHeader('X-Service-Key', config('messaging.service_key'))
            ->post(config('messaging.order_service_url').'/api/v1/internal/orders/'.$order->order_id.'/client/cancel');

        if ($response->failed()) {
            $this->recordAttempt($token, 'client/cancel', false);

            return back()->with('error', $response->json('message', 'The order could not be cancelled.'));
        }

        $order->update(['status' => $response->json('data.status')]);
        $this->recordAttempt($token, 'client/cancel', true);

        return redirect()->route('client-orders.show', request()->route('token'))->with('success', 'Order cancelled.');
    }

    public function issue(Request $request, string $token): RedirectResponse
    {
        [$token, $order] = $this->resolveToken($token);
        $data = $request->validate(['description' => ['required', 'string', 'max:1000']]);
        Issue::query()->create(['order_id' => $order->order_id, 'description' => $data['description']]);
        $this->recordAttempt($token, 'client/issues', true);

        return redirect()->route('client-orders.show', request()->route('token'))->with('success', 'Your issue has been recorded.');
    }

    /** @return array{ActionToken, OrderSnapshot} */
    private function resolveToken(string $token): array
    {
        $capability = ActionToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('actor', 'client')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
        $capability->update(['last_used_at' => now()]);
        $order = OrderSnapshot::query()->where('order_id', $capability->order_id)->firstOrFail();

        return [$capability, $order];
    }

    private function recordAttempt(ActionToken $token, string $action, bool $succeeded): void
    {
        ActionTokenAudit::query()->create([
            'action_token_id' => $token->id,
            'order_id' => $token->order_id,
            'actor' => $token->actor,
            'action' => $action,
            'succeeded' => $succeeded,
            'request_fingerprint' => hash('sha256', (string) request()->ip()),
        ]);
    }
}
