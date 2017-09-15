<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\OrderProduct;
use Cartalyst\Stripe\Stripe;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * For Stripe Webhooks
     * @param Request $request
     * @param $campaign_key
     */
    public function stripeCallBack(Request $request,$campaign_key)
    {
        try{
            switch ($request['type'])
            {
                case 'customer.subscription.updated' :
                    $subscriptionUpdate = $this->subscriptionUpdated($request,$campaign_key);
                    Log::info($subscriptionUpdate);
                    break;
                case 'charge.refunded' :
                    $refund = $this->chargeRefunded($request,$campaign_key);
                    Log::info($refund);
                    break;
                default :
                    Log::info($request['type'].' is not in use');
            }
        } catch (\Exception $exception){
            Log::info($exception->getMessage());
        }
        http_response_code(200);
    }

    /**
     * Create a New Sale when a Subscription Updated
     * @param $event
     * @param $campaign_key
     * @return string
     */
    private function subscriptionUpdated($event,$campaign_key)
    {
        try {
            $campaign = Campaign::where('key',$campaign_key)->firstOrFail();
            $customer_id = $event['data']['object']['customer'];
            if ($campaign->test_sk != '' && $campaign->test_pk != '' && $campaign->live_sk != '' && $campaign->live_pk != ''){
                if($campaign->stripe_mode == 1){
                    $key = $campaign->test_sk;
                } else {
                    $key = $campaign->live_sk;
                }
                $stripe = Stripe::make($key);
                $customer = $stripe->customers()->find($customer_id);
                $myCustomer = OrderProduct::where('email',$customer['email'])->firstOrFail();

                $newCustomer = new OrderProduct();
                $newCustomer->log_id = $myCustomer->log_id;
                $newCustomer->product_id = $myCustomer->product_id;
                $newCustomer->email = $myCustomer->email;
                $newCustomer->status = 1;
                $newCustomer->save();

                return 'Customer Subscription Updated';
            } else {
                return 'Stripe is not integrated in campaign: '.$campaign->name;
            }
        } catch(\Exception $exception) {
            return $exception->getMessage();
        } catch (ModelNotFoundException $e){
            return $e->getMessage();
        }
    }
    public function chargeRefunded($event,$campaign_key)
    {
        try {
            Log::info($event);
            $campaign = Campaign::where('key',$campaign_key)->firstOrFail();
            $customer_id = $event['data']['object']['customer'];
            if ($campaign->test_sk != '' && $campaign->test_pk != '' && $campaign->live_sk != '' && $campaign->live_pk != ''){
                if($campaign->stripe_mode == 1){
                    $key = $campaign->test_sk;
                } else {
                    $key = $campaign->live_sk;
                }
                $stripe = Stripe::make($key);
                $customer = $stripe->customers()->find($customer_id);
                $myCustomer = OrderProduct::where('email',$customer['email'])->firstOrFail();

                $newCustomer = new OrderProduct();
                $newCustomer->log_id = $myCustomer->log_id;
                $newCustomer->product_id = $myCustomer->product_id;
                $newCustomer->email = $myCustomer->email;
                $newCustomer->status = 1;
                $newCustomer->save();

                return 'Customer Subscription Updated';
            } else {
                return 'Stripe is not integrated in campaign: '.$campaign->name;
            }
        } catch(\Exception $exception) {
            return $exception->getMessage();
        } catch (ModelNotFoundException $e){
            return $e->getMessage();
        }
    }
}
