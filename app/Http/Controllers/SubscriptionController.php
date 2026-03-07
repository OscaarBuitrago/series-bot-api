<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        return response()->json([
            'tier' => $user->subscription_tier,
            'queries_this_month' => $user->queries_this_month,
            'queries_limit' => $user->isPro() ? null : 10,
            'subscribed' => $subscription?->active() ?? false,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:pro',
        ]);

        $user = $request->user();

        $checkoutUrl = $user->newSubscription('default', config('services.stripe.pro_price_id'))
            ->checkout([
                'success_url' => config('app.frontend_url') . '/settings?subscribed=1',
                'cancel_url' => config('app.frontend_url') . '/settings',
            ])
            ->url;

        return response()->json(['checkout_url' => $checkoutUrl]);
    }

    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        $portalUrl = $user->billingPortalUrl(
            config('app.frontend_url') . '/settings'
        );

        return response()->json(['portal_url' => $portalUrl]);
    }

    public function webhook(Request $request)
    {
        // Stripe webhook handled by Cashier's built-in route
        // Register via: php artisan cashier:webhook
    }
}
