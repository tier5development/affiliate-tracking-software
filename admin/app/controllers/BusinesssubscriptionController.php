<?php

namespace Vokuro\Controllers;

use Exception;
use Vokuro\Utils;
use Vokuro\Models\Users;
use Vokuro\Models\Agency;
use Vokuro\Services\ServicesConsts;

/**
 * Vokuro\Controllers\BusinessSubscriptionController
 * CRUD to manage users
 */
class BusinessSubscriptionController extends ControllerBase {

    public function initialize() {

        $identity = $this->session->get('auth-identity');
        if ($identity && $identity['profile'] != 'Employee') {
            $this->tag->setTitle('Review Velocity | Subscription');
            $this->view->setTemplateBefore('private');
        } else {
            $this->response->redirect('/session/login?return=/');
            $this->view->disable();
            return;
        }
        parent::initialize();

        //add needed css
        $this->assets
            ->addCss('/assets/global/plugins/bootstrap-summernote/summernote.css')
            ->addCss('/css/subscription.css')
            ->addCss('/css/slider-extended.css')
            ->addCss('/assets/global/plugins/card-js/card-js.min.css');

        //add needed js
        $this->assets
            ->addJs('/assets/global/plugins/bootstrap-summernote/summernote.min.js')
            ->addJs('/assets/global/plugins/card-js/card-js.min.js');
    }

    public function indexAction() {

        /* Get services */
        $userManager = $this->di->get('userManager');
        $subscriptionManager = $this->di->get('subscriptionManager');
        $smsManager = $this->di->get('smsManager');
        $paymentService = $this->di->get('paymentService');

        /* Get the role type */
        $isBusiness = $userManager->isBusiness($this->session);

        /* Show sms quota? */
        $this->view->showSmsQuota = $isBusiness;
        if ($isBusiness) {

            /* Get sms quota parameters */
            $smsQuotaParams = $smsManager->getBusinessSmsQuotaParams(
                $userManager->getLocationId($this->session)
            );

            if ($smsQuotaParams['hasUpgrade']) {
                // REFACTOR: DOESN'T SEEM TO BE GETTING CALLED
                // $percent = ($total_sms_month > 0 ? number_format((float)($sms_sent_this_month_total / $total_sms_month) * 100, 0, '.', ''):100);
                // if ($percent > 100) $percent = 100;
            } else {
                $this->view->showBarText = $smsQuotaParams['percent'] > 60 ? "style=\"display: none;\"" : "";
            }
            $this->view->smsQuotaParams = $smsQuotaParams;
        }

        /* Get subscription paramaters */
        $userId = $userManager->getUserId($this->session);

        /* Get the subscription plan */
        $subscriptionPlanData = $subscriptionManager->getSubscriptionPlan($userId);

        /* Filter out the pricing plan details into its own view because it contains markup */
        $this->view->pricingDetails = $subscriptionPlanData['pricingPlan']['pricing_details'];

        /* Set pricing plan details to empty so it doesn't display when attaching the json string to the data attribute */
        $subscriptionPlanData['pricingPlan']['pricing_details'] = '';
        $this->view->subscriptionPlanData = $subscriptionPlanData;
        $this->view->paymentPlan =
            $this->view->subscriptionPlanData['subscriptionPlan']['payment_plan'] === ServicesConsts::$PAYMENT_PLAN_TRIAL ? 'TRIAL' : 'PAID';

        /* Payments paramaters */
        $provider = ServicesConsts::$PAYMENT_PROVIDER_STRIPE;

        $this->view->registeredCardType = $paymentService->getRegisteredCardType($userId, $provider);

    }

    /**
     * Check whether a customer profile exists for the current user
     */
    public function hasPaymentProfileAction() {
        $this->view->disable();

        $responseParameters['status'] = false;

        try {

            if (!$this->request->isPost()) {
                throw new \Exception();
            }

            /* Get services */
            $userManager = $this->di->get('userManager');
            $paymentService = $this->di->get('paymentService');

            /* Get the user id */
            $userId = $userManager->getUserId($this->session);

            $paymentParams = [
                'userId' => $userId,
                'provider' => ServicesConsts::$PAYMENT_PROVIDER_STRIPE
            ];

            $hasPaymentProfile = $paymentService->hasPaymentProfile($paymentParams);

            if (!$hasPaymentProfile) {
                throw new \Exception();
            }

            $responseParameters['status'] = true;

        } catch(Exception $e) {}

        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($responseParameters));
        return $this->response;
    }

    /**
     * Update credit card
     */
    public function updatePaymentProfileAction() {
        $this->view->disable();

        $responseParameters['status'] = false;

        try {

            if (!$this->request->isPost()) {
                throw new \Exception();
            }

            /* Get services */
            $userManager = $this->di->get('userManager');
            $paymentService = $this->di->get('paymentService');

            /* Get the user id */
            $userId = $userManager->getUserId($this->session);

            $user = Users::query()
                ->where("id = :id:")
                ->bind(["id" => $userId])
                ->execute()
                ->getFirst();
            $agency = Agency::query()
                ->where("agency_id = :agency_id:")
                ->bind(["agency_id" => $user->agency_id])
                ->execute()
                ->getFirst();


            $Provider = ServicesConsts::$PAYMENT_PROVIDER_STRIPE;

            // Card Number, Name and CSV aren't required for Stripe.  Just grab the token

            $cardNumber = $agency->parent_id == -1 ? $this->request->getPost('cardNumber', 'striptags') : '';
            $cardName = $agency->parent_id == -1 ? $this->request->getPost('cardName', 'striptags') : '';
            $csv = $agency->parent_id == -1 ? $this->request->getPost('csv', 'striptags') : '';
            $tokenID = $agency->parent_id == -1 ? '' : $this->request->getPost('tokenID', 'striptags');

            /* Format the date accordingly  */
            $date = Utils::formatCCDate($this->request->getPost('expirationDate', 'striptags'));

            /* Create the payment profile */
            $paymentParams = [ 'userId' => $userId, 'provider' => $Provider];
            $ccParameters = [
                'userId'                => $userId,
                'cardNumber'            => str_replace(' ', '', $cardNumber),
                'cardName'              => $cardName,
                'expirationDate'        => $date,
                'csv'                   => $csv,
                'provider'              => $Provider,
                'userEmail'             => $user->email,
                'userName'              => $user->name,
                'agencyName'            => $agency->name,
                'agencyAddress'         => $agency->address,
                'agencyCity'            => '', //$agency->city,  This field doesn't exist yet.  Will add later  TODO:  Fix this!
                'agencyStateProvince'   => $agency->state_province,
                'agencyPostalCode'      => $agency->postal_code,
                'agencyCountry'         => $agency->country,
                'tokenID'               => $tokenID,
            ];

            if ($paymentService->hasPaymentProfile($paymentParams)) {
                $profile = $paymentService->updatePaymentProfile($ccParameters);
                if (!$profile) {
                    throw new \Exception('Payment Profile Could not be updated');
                }
            } else {
                $profile = $paymentService->createPaymentProfile($ccParameters);
                if (!$profile) {
                    throw new \Exception('Payment Profile Could not be created');
                }
            }

            /*
             * Success!!!
             */
            $responseParameters['status'] = true;

        }  catch(Exception $e) {}

        /*
         * Construct the response
         */
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($responseParameters));
        return $this->response;
    }

    /**
     * Change plan
     */
    public function changePlanAction() {
        $this->view->disable();

        $responseParameters['status'] = false;

        try {

            if (!$this->request->isPost()) {
                throw new \Exception('POST request is required!');
            }

            /* Get services */
            $userManager = $this->di->get('userManager');
            $paymentService = $this->di->get('paymentService');
            $subscriptionManager = $this->di->get('subscriptionManager');

            /* Get the user id */
            $userId = $userManager->getUserId($this->session);
            $objUser = \Vokuro\Models\Users::findFirst("id = {$userId}");
            $objAgency = \Vokuro\Models\Agency::findFirst("agency_id = {$objUser->agency_id}");


            $objSubscriptionPlan = \Vokuro\Models\BusinessSubscriptionPlan::findFirst('user_id = ' . $userId);
            $objSubscriptionPlan->sms_messages_per_location = $this->request->getPost('messages', 'striptags');
            $objSubscriptionPlan->locations = $this->request->getPost('locations', 'striptags');
            if(!$objSubscriptionPlan->update())
                throw new \Exception('Could not update subscription plan.');

            /*
             * If they don't have a customer profile, then create one (they shouldn't have one if calling this action,
             * but check just to be safe)
             */
            $Provider = ServicesConsts::$PAYMENT_PROVIDER_STRIPE;
            $paymentParams = [
                'userId' => $userId,
                'provider' => $Provider
            ];

            $hasPaymentProfile = $paymentService->hasPaymentProfile($paymentParams);
            if(!$hasPaymentProfile) {
                throw new \Exception('Payment information not found!');
            }

            $intervalLength = $this->request->getPost('planType', 'striptags') === 'Annually' ? 12 : 1;

            /* Create the subscription */
            $subscriptionParameters = [
                'userId'            => $userId,
                'locations'         => $this->request->getPost('locations', 'striptags'),
                'messages'          => $this->request->getPost('messages', 'striptags'),
                'planType'          => $this->request->getPost('planType', 'striptags'),
                'price'             => $subscriptionManager->getSubscriptionPrice($userId, $this->request->getPost('planType', 'striptags')),
                'provider'          => $Provider,
                'intervalLength'    => $intervalLength,
            ];
            $changePlanSucceeded = $paymentService->changeSubscription($subscriptionParameters);
            if(!$changePlanSucceeded) {
                throw new \Exception('Could not change subscription.');
            }
            if(!$subscriptionManager->changeSubscriptionPlan($subscriptionParameters)) {
                throw new \Exception('Payment information not found!');
            }

            /*
             * Success!!!
             */
            $responseParameters['status'] = true;

        }  catch(Exception $e) {$responseParameters['error'] = $e->getMessage();}

        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($responseParameters));
        return $this->response;
    }

    /**
     * Show invoices
     */
    public function invoicesAction() {
        if ($this->request->isGet()) {

        }
    }

}
