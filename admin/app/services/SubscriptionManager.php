<?php

namespace Vokuro\Services;

use Vokuro\Services\ServicesConsts;
use Vokuro\Models\BusinessSubscriptionPlan;
use Vokuro\Models\SubscriptionPricingPlan;
use Vokuro\Models\SubscriptionPricingPlanParameterList;
    
class SubscriptionManager extends BaseService {
    
    function __construct($config) {
        parent::__construct($config);
    }
    
    public function getSubscriptionPricingPlans() {
        return $subscriptionPricingPlans = SubscriptionPricingPlan::find();
    }
    
    public function createSubscriptionPlan($newSubscriptionParameters) {
        
        $userId = $newSubscriptionParameters['userAccountId'];
        
        /* Configure subscription parameters */
        if ($newSubscriptionParameters['pricingPlanId'] === 'Unpaid') {
            
            $pricingPlan = SubscriptionPricingPlan::findFirst();   // Default pricing plan is always first in the table
            $pricingPlanId = $pricingPlan->id;
            $locations = $newSubscriptionParameters['freeLocations'];
            $smsMessagesPerLocation = $newSubscriptionParameters['freeSmsMessagesPerLocation'];
            $paymentPlan = ServicesConsts::$PAYMENT_PLAN_FREE;
            
        } else {
            
            $subscriptionPricingPlan = SubscriptionPricingPlan::query()  
                ->where("id = :id:")
                ->bind(["id" => $newSubscriptionParameters['pricingPlanId']])
                ->execute()
                ->getFirst();
            $pricingPlanId = $subscriptionPricingPlan->id;   
            if ($subscriptionPricingPlan->getTrialPeriod()) {
                $paymentPlan = ServicesConsts::$PAYMENT_PLAN_TRIAL;  
                $locations = $subscriptionPricingPlan->getMaxLocationsOnFreeAccount();
                $smsMessagesPerLocation = $subscriptionPricingPlan->getMaxMessagesOnFreeAccount();
            } else {
                $paymentPlan = ServicesConsts::$PAYMENT_PLAN_MONTHLY;;
                $locations = 0;
                $smsMessagesPerLocation = 0;;
            }
            
        }
        
        // Subscription plan
        $subscriptionPlan = new BusinessSubscriptionPlan();
        $subscriptionPlan->setUserId(intval($userId));
        $subscriptionPlan->setLocations(intval($locations));
        $subscriptionPlan->setSmsMessagesPerLocation(intval($smsMessagesPerLocation));
        $subscriptionPlan->setPaymentPlan($paymentPlan);
        $subscriptionPlan->setSubscriptionPricingPlanId(intval($pricingPlanId));
        if (!$subscriptionPlan->create()) {
            return false;
        }
        
        return true;
    }
    
    public function getSubscriptionPlan($userId) {
        $subscriptionPlan = BusinessSubscriptionPlan::query()  
            ->where("user_id = :user_id:")
            ->bind(["user_id" => intval($userId)])
            ->execute()
            ->getFirst();
        if(!$subscriptionPlan) {
            return false;
        }
        return $subscriptionPlan->toArray();
    }
    
    public function getPricingPlan($pricingPlanId) {
        $subscriptionPricingPlan = SubscriptionPricingPlan::query()  
            ->where("id = :id:")
            ->bind(["id" => intval($pricingPlanId)])
            ->execute()
            ->getFirst();
        if(!$subscriptionPricingPlan) {
            return false;
        }
        return $subscriptionPricingPlan->toArray();
    }
    
    public function getPricingPlanByName($userId, $pricingPlanName) {
        $subscriptionPricingPlan = SubscriptionPricingPlan::query()  
            ->where("user_id = :userId:")
            ->andWhere("name = :pricingPlanName:")
            ->bind(["userId" => $userId, "pricingPlanName" => $pricingPlanName])
            ->execute()
            ->getFirst();
        if(!$subscriptionPricingPlan) {
            return false;
        }
        return $subscriptionPricingPlan->toArray();
    }
    
    public function createPricingProfile($parameters) {
        
        $status = false;
        
        try {
                
            $id = $this->createSubscriptionPricingPlan($parameters);
            if (!$id) {
                throw new \Exception();
            }
        
            if (!$this->appendPricingParameterLists($id, $parameters)) {
                throw new \Exception();
            }
            
            $status = true;
        
        } catch(Exception $e) {}
        
        return $status;
        
    }
    
    public function getAllPricingPlansByUserId($userId) {
        $subscriptionPricingPlans = SubscriptionPricingPlan::query()  
            ->where("user_id = :userId:")
            ->andWhere("deleted_at = '0000-00-00 00:00:00'")
            ->bind(["userId" => $userId])
            ->execute();
        return $subscriptionPricingPlans;
    }
    
    public function enablePricingPlanById($pricingPlanId, $enable) {  // Second param is a dirty filthy hack :(, See comment below for details
        $subscriptionPricingPlan = SubscriptionPricingPlan::query()  
            ->where("id = :id:")
            ->bind(["id" => $pricingPlanId])
            ->execute()
            ->getFirst();
        if (!$subscriptionPricingPlan) {
            return false;
        }
        
        $subscriptionPricingPlan->enabled = $enable === 'true' ? 1 : 0;
        $subscriptionPricingPlan->updated_at = time();
        if (!$subscriptionPricingPlan->update()) {
            return false;
        }
        
        return true;
    }
    
    public function deletePricingPlanById($pricingPlanId) {
        $subscriptionPricingPlan = SubscriptionPricingPlan::query()  
            ->where("id = :id:")
            ->bind(["id" => $pricingPlanId])
            ->execute()
            ->getFirst(); 
        if (!$subscriptionPricingPlan) {
            return false;
        }
        
        $subscriptionPricingPlan->deleted_at = time();
        // $result = $db->query("DELETE FROM subscription_pricing_plan WHERE id=" . $subscriptionPricingPlan->id ); // Working now
        if (!$subscriptionPricingPlan->update()) {
            return false;
        }
        
        return true;
    }
    
    private function createSubscriptionPricingPlan($parameters) {
        
        $subscriptionPricingPlan = new SubscriptionPricingPlan();
        $subscriptionPricingPlan->user_id = $parameters["userId"];
        $subscriptionPricingPlan->name = $parameters["name"];                               
        $subscriptionPricingPlan->enabled = true;
        $subscriptionPricingPlan->enable_trial_account = $parameters["enableTrialAccount"];
        $subscriptionPricingPlan->enable_discount_on_upgrade = $parameters["enableDiscountOnUpgrade"];
        $subscriptionPricingPlan->base_price = $parameters["basePrice"];
        $subscriptionPricingPlan->cost_per_sms = $parameters["costPerSms"];
        $subscriptionPricingPlan->max_messages_on_trial_account = $parameters["maxMessagesOnTrialAccount"];
        $subscriptionPricingPlan->updgrade_discount = $parameters["upgradeDiscount"];
        $subscriptionPricingPlan->charge_per_sms = $parameters["chargePerSms"];
        $subscriptionPricingPlan->max_sms_messages = $parameters["maxSmsMessages"];
        $subscriptionPricingPlan->enable_annual_discount = $parameters["enableAnnualDiscount"];
        $subscriptionPricingPlan->annual_discount = $parameters["annualDiscount"];
        $subscriptionPricingPlan->pricing_details = $parameters["pricingDetails"] ? : new \Phalcon\Db\RawValue('default');
        if (!$subscriptionPricingPlan->create()) {
            return false;
        }
        
        return $subscriptionPricingPlan->id;
    }
        
    private function appendPricingParameterLists($id, $parameters) {
        
        foreach($parameters as $segment => $params) {    
            
            if(substr($segment,0,7) !== "segment") {
                continue;
            }
            
            $pricingParameterList = $this->createPricingParameterList($id, $params);
            if(!$pricingParameterList) {
                return false;
            }
            
        }
        
        return true;
    }
    
    private function createPricingParameterList($id, $parameters) {
         
        $subscriptionPricingPlanParameterList = new SubscriptionPricingPlanParameterList();
        $subscriptionPricingPlanParameterList->subscription_pricing_plan_id = intval($id);
        $subscriptionPricingPlanParameterList->min_locations = intval($parameters['minLocations']);
        $subscriptionPricingPlanParameterList->max_locations = intval($parameters['maxLocations']);
        $subscriptionPricingPlanParameterList->location_discount_percentage = floatval($parameters['locationDiscountPercentage']);
        $subscriptionPricingPlanParameterList->base_price = floatval($parameters['basePrice']);
        $subscriptionPricingPlanParameterList->sms_charge = floatval($parameters['smsCharge']);
        $subscriptionPricingPlanParameterList->total_price = floatval($parameters['totalPrice']);
        $subscriptionPricingPlanParameterList->location_discount = floatval($parameters['locationDiscount']);
        $subscriptionPricingPlanParameterList->upgrade_discount = floatval($parameters['upgradeDiscount']);
        $subscriptionPricingPlanParameterList->sms_messages = intval($parameters['smsMessages']);
        $subscriptionPricingPlanParameterList->sms_cost = floatval($parameters['smsCost']);
        $subscriptionPricingPlanParameterList->profit_per_location = floatval($parameters['profitPerLocation']);
        if(!$subscriptionPricingPlanParameterList->create()) {
            return false;
        }
        
        return true;
    }
    
}
