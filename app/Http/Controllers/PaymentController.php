<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class PaymentController extends Controller
{


    function index(Request $request) {

        Stripe::setApiKey(config('stripe.stripe_sk'));
        // coming from frontend as a token or method
        $paymentMethodId = $request->paymentMethodId;
        // get Login User id to get user data / or use id from req to get data
        $user = User::where('id',$request->user_id)->first();
        // Get data of customer from stripe against following paymentMethodId
        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

        // Extract the brand of card 
        $cardType = $paymentMethod->card->brand;
        // Extract the last four digits
        $lastFourDigits = $paymentMethod->card->last4;
        try {
                // create Stripe Customer in stripe 
                $stripeCustomer = \Stripe\Customer::create([
                    'email' => $request->email,
                    'name'=>$request->name,
                    'payment_method' => $paymentMethodId,
               
                ]);
                // save record of card in database
                Payment::create([
                    'user_id'=>$request->user_id,
                    'payment_id'=>$paymentMethodId,
                    'customer'=>$stripeCustomer->id,
                    'last_4_digits'=>$lastFourDigits,
                    'card_type'=>$cardType
                  ]);
            // Create a PaymentIntent and charge a payment
            $intent = PaymentIntent::create([
                'payment_method' => $paymentMethodId, // from frontend
                'amount' => $request->payment*1000, // Set the amount to be charged (in cents)
                'currency' => 'usd', 
                'confirmation_method' => 'manual', // always manual
                'confirm' => true, // always true,
                'return_url' => 'https://127.0.0.1:8000', // necessary param
                'customer' => $stripeCustomer, // customer data we cretaed in stripe also passes to payment so that payment is chraged
                                              // for that customer
            ]);
            return response()->json(['success' => true, 'message' => 'Payment successfully Recieved']);
        }catch(\Exception $e){
            return response()->json(['success' => false, 'message' => $e->getMessage()]);

        }
    }

    function getCardOfLoginCustomer(){
        $user = Auth::user();
        $payment =Payment::where('user_id',$user->id)->get();
        return response()->json($payment);
    }
    function payment_with_card(Request $request)  {
        Stripe::setApiKey(config('stripe.stripe_sk'));

        $paymentMethodId = $request->payment_id;

        $customer = $request->customer;

        try{

            $intent = PaymentIntent::create([
            'payment_method' => $paymentMethodId,
            'amount' => 1000, // Set the amount to be charged (in cents)
            'currency' => 'usd',
            'confirmation_method' => 'manual',
            'confirm' => true,
            'return_url' => 'https://127.0.0.1:8000',
            'customer' => $customer,
        ]);


        return response()->json(['message' => 'Payment successfully Recieved']);

    }catch(\Exception $e){
        return response()->json([ 'error' => $e->getMessage()]);

    }


    }
}
