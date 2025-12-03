<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
{
    $stripeSecret = config('services.stripe.secret');
\Stripe\Stripe::setApiKey($stripeSecret);


    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'], 
            'line_items' => [[
                'price' => $request->price_id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription', 
            'success_url' => 'http://localhost:3001/inicio',
            'cancel_url' => 'http://localhost:3001/config',
        ]);

        return response()->json(['url' => $session->url]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
