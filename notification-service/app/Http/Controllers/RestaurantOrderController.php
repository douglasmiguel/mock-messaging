<?php

namespace App\Http\Controllers;

use App\Models\ActionToken;
use App\Models\ActionTokenAudit;
use App\Models\OrderSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class RestaurantOrderController extends Controller
{
    public function show(string $token): View
    {
        [, $order] = $this->resolveToken($token);

        return view('restaurant-orders.show', compact('token', 'order'));
    }

    public function confirm(string $token): RedirectResponse
    {
        return $this->action($token, 'restaurant/confirm');
    }

    public function refuse(string $token): RedirectResponse
    {
        return $this->action($token, 'restaurant/refuse');
    }

    public function ready(string $token): RedirectResponse
    {
        return $this->action($token, 'restaurant/ready');
    }

    private function action(string $token, string $path): RedirectResponse
    {
        [$token, $order] = $this->resolveToken($token);
        $response = Http::acceptJson()
            ->withHeader('X-Service-Key', config('messaging.service_key'))
            ->post(config('messaging.order_service_url').'/api/v1/internal/orders/'.$order->order_id.'/'.$path);

        if ($response->failed()) {
            $this->recordAttempt($token, $path, false);

            return back()->with('error', $response->json('message', 'The order action could not be completed.'));
        }

        $order->update(['status' => $response->json('data.status')]);
        $this->recordAttempt($token, $path, true);

        return redirect()->route('restaurant-orders.show', request()->route('token'))->with('success', 'Order updated.');
    }

    /** @return array{ActionToken, OrderSnapshot} */
    private function resolveToken(string $token): array
    {
        $capability = ActionToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('actor', 'restaurant')
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
