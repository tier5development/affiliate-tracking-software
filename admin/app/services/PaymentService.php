<?php

namespace Vokuro\Services;

use Vokuro\Models\Users;
use Vokuro\Models\Agency;
use Vokuro\Services\ServicesConsts;
use Vokuro\Models\SubscriptionPlan;
use Vokuro\Models\AuthorizeDotNet as AuthorizeDotNetModel;
use Vokuro\Payments\AuthorizeDotNet as AuthorizeDotNetPayment;

class PaymentService extends BaseService {
        
    function __construct($config) {
        parent::__construct($config);
    }
    
    public function hasPaymentProfile($paymentParams) {
        $class = $this->getProviderClass($paymentParams['provider']);
        switch($class) {
            case ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET:
                $creditCard = AuthorizeDotNetModel::query()
                    ->where("user_id = :userId:")
                    ->bind(["userId" => $paymentParams['userId']])
                    ->execute()
                    ->getFirst();
                $status = $creditCard ? true : false;
                break;
            default:
                $status = false;
                break;
        }
        return $status;    
    }
    
    public function createPaymentProfile($ccParameters) {
        $class = $this->getProviderClass($ccParameters['provider']);
        
        switch($class) {
            case ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET:
                $status = $this->createAuthorizeDotNetPaymentProfile($ccParameters);
                break;
            default:
                $status = false;
                break;
        }
        
        return $status;
    }
    
    public function updatePaymentProfile($ccParameters) {
        $class = $this->getProviderClass($ccParameters['provider']);
        
        switch($class) {
            case ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET:
                $status = $this->updateAuthorizeDotNetPaymentProfile($ccParameters);
                break;
            default:
                $status = false;
                break;
        }
        
        return $status;
    }
    
    public function changeSubscription($subscriptionParameters) {
        $class = $this->getProviderClass($subscriptionParameters['provider']);
        
        switch($class) {
            case ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET:
                $status = $this->changeAuthorizeDotNetSubscription($subscriptionParameters);
                break;
            default:
                $status = false;
                break;
        }
        
        if ($status) {
            $subscriptionPlan = SubscriptionPlan::query()
                ->where("user_id = :userId:")
                ->bind(["userId" => $subscriptionParameters['userId']])
                ->execute()
                ->getFirst();
            $subscriptionPlan->setLocations($subscriptionParameters['locations']);
            $subscriptionPlan->setSmsMessagesPerLocation($subscriptionParameters['messages']);
            $subscriptionPlan->setPaymentPlan($subscriptionParameters['planType']);
            if (!$subscriptionPlan->save()) {
                $status = false;
            }
        }
        
        return $status;
    }
    
    private function getProviderClass($provider) {
        $providerClass = null;
        
        switch($provider) {
            case ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET:
                $providerClass = ServicesConsts::$PAYMENT_PROVIDER_AUTHORIZE_DOT_NET; 
                break;
            case ServicesConsts::$PAYMENT_PROVIDER_STRIPE:
            default:
                break;
        }
        
        return $providerClass;
    }
    
    private function createAuthorizeDotNetPaymentProfile($ccParameters) {
        $authorizeDotNet = new AuthorizeDotNetPayment($this->config);
        
        $user = Users::query()
            ->where("id = :id:")
            ->bind(["id" => $ccParameters['userId']])
            ->execute()
            ->getFirst();
        $agency = Agency::query()
            ->where("agency_id = :agency_id:")
            ->bind(["agency_id" => $user->agency_id])
            ->execute()
            ->getFirst();
        
        $parameters['customerType'] = 'individual';
	$parameters['customerProfileDescription'] = 'Empty';
        $parameters['email'] = $user->email;
	$parameters['cardNumber'] = $ccParameters['cardNumber'];
	$parameters['cardExpiryDate'] = $ccParameters['expirationDate'];
	$parameters['cardCode'] = $ccParameters['csv'];
        $parameters['firstName'] = $user->name;
	$parameters['lastName'] = "";
	$parameters['companyName'] = $agency->name;
	$parameters['companyAddress'] = $agency->address;
	$parameters['city'] = "Los Angeles";
	$parameters['state'] = $agency->state_province;
	$parameters['zip'] = $agency->postal_code;
	$parameters['country'] = $agency->country;
        
        $profile = $authorizeDotNet->createCustomerProfile($parameters);
        if (!$profile) {
            return false;
        }
        
        $authorizeDotNetModel = new AuthorizeDotNetModel();
        $authorizeDotNetModel->setUserId($ccParameters['userId']);
        $authorizeDotNetModel->setCustomerProfileId($profile['customerProfileId']);
        if(!$authorizeDotNetModel->create()) {
            return false;
        }
        
        return true;
    }
    
    private function updateAuthorizeDotNetPaymentProfile($ccParameters) {
        $authorizeDotNet = new AuthorizeDotNetPayment($this->config);
        
        /* REFACTOR: For the time being, we have to pull the full set of user 
         * data in again on update calls as the API functionality for "field"
         * specific updates doesn't work.  We'll keep an eye this.  MT, 2016 
         */
        $user = Users::query()
            ->where("id = :id:")
            ->bind(["id" => $ccParameters['userId']])
            ->execute()
            ->getFirst();
        $agency = Agency::query()
            ->where("agency_id = :agency_id:")
            ->bind(["agency_id" => $user->agency_id])
            ->execute()
            ->getFirst();
        
        $parameters['customerType'] = 'individual';
	$parameters['customerProfileDescription'] = 'Empty';
        $parameters['email'] = $user->email;
	$parameters['cardNumber'] = $ccParameters['cardNumber'];
	$parameters['cardExpiryDate'] = $ccParameters['expirationDate'];
	$parameters['cardCode'] = $ccParameters['csv'];
        $parameters['firstName'] = $user->name;
	$parameters['lastName'] = "";
	$parameters['companyName'] = $agency->name;
	$parameters['companyAddress'] = $agency->address;
	$parameters['city'] = "Los Angeles";
	$parameters['state'] = $agency->state_province;
	$parameters['zip'] = $agency->postal_code;
	$parameters['country'] = $agency->country;
        
        return $authorizeDotNet->updatePaymentProfileForCustomer($parameters);
    }
    
    private function changeAuthorizeDotNetSubscription($subscriptionParameters) {
        
        /* Get the customer profile */
        $authorizeDotNetModel = AuthorizeDotNetModel::query()
            ->where("user_id = :userId:")
            ->bind(["userId" => $subscriptionParameters['userId']])
            ->execute()
            ->getFirst();
        if (!$authorizeDotNetModel) {
            return false;
        }
        
        /* Get the customer payment profile */
        $authorizeDotNetPayment = new AuthorizeDotNetPayment($this->config);
        if (!$authorizeDotNetPayment) {
            return false;
        }
        
        $parameters = [];
        $subscriptionId = $authorizeDotNetModel->getSubscriptionId();
        if($subscriptionId === 'N') {
            
            $parameters['customerProfileId'] = $authorizeDotNetModel->getCustomerProfileId();
            
            /* Get the customer payment profile */    
            $customerProfile = $authorizeDotNetPayment->getCustomerProfile($parameters);
            if (!$customerProfile) {
                return false;
            }
            
            // Get customer billing info
            $customerPaymentProfile = $customerProfile['paymentProfiles'][0];
            $shippingAddresses = $customerProfile['shippingAddresses'][0];
            
            $parameters['billTo'] = $customerPaymentProfile->getBillTo();
            $parameters['customerPaymentProfileId'] = $customerPaymentProfile->getCustomerPaymentProfileId();
            $parameters['customerAddressId'] = $shippingAddresses->getCustomerAddressId();
            $parameters['subscriptionName'] = "Review Velocity Subscription";
            $parameters['intervalLength'] = $this->config->authorizeDotNet->intervalLength;
            $parameters['unit'] = $this->config->authorizeDotNet->unit;
            $parameters['startDate'] = date("Y-m-d");
            $parameters['totalOccurences'] = $this->config->authorizeDotNet->totalOccurences;
            $parameters['amount'] = round($subscriptionParameters['price'], 2);
            
            $status = $authorizeDotNetPayment->createSubscriptionForCustomer($parameters);
            
        } else {
            
            $parameters['subscriptionId'] = $subscriptionParameters['price'];
            $parameters['amount'] = $subscriptionParameters['price'];
            $status = $authorizeDotNetPayment->updateSubscriptionForCustomer($parameters);
        
        }
        
        return $status;
    }
}
