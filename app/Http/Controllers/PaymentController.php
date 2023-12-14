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
        $paymentMethodId = $request->paymentMethodId;
        $user = User::where('id',$request->user_id)->first();
        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

        // Extract the last four digits
        $lastFourDigits = $paymentMethod->card->last4;
        // $myDatabasePaymentUser = Payment::where('user_id',$user->id)->first();
        // dd($myDatabasePaymentUser);
        // $paymentObject = Payment::where('user_id',$user->id)->get();
        // $alreadyUsedId =$paymentObject->payment_id;
        // dd($alreadyUsedId);
        // $stripeCustomerRetrive = \Stripe\Customer::retrieve($paymentObject->customer);


        // dd($alreadyUsedId);

        // dd($request->all());
        try {

            // Check if the user has a Stripe Customer ID

                // If not, create a customer in Stripe and save the ID to your database
                $stripeCustomer = \Stripe\Customer::create([
                    'email' => $request->email,
                    'name'=>$request->name,
                    'payment_method' => $paymentMethodId,
                //     // Add other customer information as needed
                ]);
                Payment::create([
                    'user_id'=>$request->user_id,
                    'payment_id'=>$paymentMethodId,
                    'customer'=>$stripeCustomer->id,
                    'last_4_digits'=>$lastFourDigits,

                  ]);


            // Create a PaymentIntent
            $intent = PaymentIntent::create([
                'payment_method' => $paymentMethodId,
                'amount' => $request->payment*1000, // Set the amount to be charged (in cents)
                'currency' => 'usd',
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => 'https://127.0.0.1:8000',
                'customer' => $stripeCustomer,
            ]);


            return response()->json(['success' => true, 'message' => 'Payment successfully Recieved']);

        }catch(\Exception $e){
            return response()->json(['success' => false, 'message' => $e->getMessage()]);

        }
    }

    function getCardOfLoginCustomer(){
        $payment =Payment::all();
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
