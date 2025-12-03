<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
{
    //Obtenemos la llave secreta desde la configuracion
    $stripeSecret = config('services.stripe.secret');
    \Stripe\Stripe::setApiKey($stripeSecret);

    //definimos la URL base del Fronend
    // Si existe la variable FRONTEND_URL en Render, usa esa. Si no, usa localhost.
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3001');
    try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'], 
                'line_items' => [[
                    'price' => $request->price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription', 
                // 3. Usamos la variable dinámica aquí
                'success_url' => $frontendUrl . '/inicio?pago=exito',
                'cancel_url' => $frontendUrl . '/planes?pago=cancelado',
            ]);

            return response()->json(['url' => $session->url]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

