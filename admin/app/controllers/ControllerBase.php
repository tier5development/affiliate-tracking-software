<?php

namespace Vokuro\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Vokuro\Models\Agency;
use Vokuro\Models\FacebookScanning;
use Vokuro\Models\GoogleScanning;
use Vokuro\Models\Location;
use Vokuro\Models\LocationNotifications;
use Vokuro\Models\LocationReviewSite;
use Vokuro\Models\Review;
use Vokuro\Models\ReviewInvite;
use Vokuro\Models\ReviewsMonthly;
use Vokuro\Models\SharingCode;
use Vokuro\Models\Users;
use Vokuro\Models\UsersSubscription;
use Vokuro\Models\YelpScanning;
use Vokuro\Services\Permissions;
use Vokuro\Services\ServicesConsts;
use Services_Twilio;
use Services_Twilio_RestException;

/**
 * ControllerBase
 * This is the base controller for all controllers in the application
 */
class ControllerBase extends Controller
{
    protected $permissions;
    protected $user_object;

    public function checkIntegerOrThrowException($int, $message = null)
    {
        if (!is_int($int)) {
            var_dump($int);
            throw new \Exception(($message) ? $message : 'Invalid parameter provided, expected integer');
        }
    }

    // remove
    public function clean($val)
    {
        return strip_tags(
            $val,
            ['<p><a><br><hr><h1><h2><h3><h4><h5><h6><b>']
        );
        
        return $val;
    }

    public function RedirectDomain($Domain)
    {
        $TLDomain = $this->config->application->domain;
        $PageURL = 'http';

        if ($_SERVER['SERVER_PORT'] === 443) {
            $PageURL .= 's';
        }

        $PageURL .= "://{$Domain}.{$TLDomain}.com/" . $_SERVER['REQUEST_URI'];
        
        return $this->response->redirect($PageURL);
    }

    public function GetLocationReviewSite($location_id, $ReviewSiteType)
    {
        $conditions = "location_id = :location_id: AND review_site_id = " . $ReviewSiteType;
        $parameters = array("location_id" => $location_id);
        $objLocationReviewSite = LocationReviewSite::findFirst(array($conditions, "bind" => $parameters));

        if (!$objLocationReviewSite) {
            $objLocationReviewSite = new LocationReviewSite();
            $objLocationReviewSite->review_site_id = $ReviewSiteType;
            $objLocationReviewSite->location_id = $location_id;
            $objLocationReviewSite->is_on = 0;
            $objLocationReviewSite->access_token = "";
            $objLocationReviewSite->json_access_token = "";
            $objLocationReviewSite->save();
        }

        return $objLocationReviewSite;
    }


    public function initialize()
    {
        error_reporting(E_ALL ^ E_NOTICE);

        $this->permissions = new Permissions();
        $this->user_object = $this->getUserObject();
        $identity = $this->auth->getIdentity();

        if (is_array($identity)) {
            $top_user_id = $this->session->get('auth-identity')['id'];
            $result = $this->db->query(
                "SELECT * FROM `users` WHERE `id`=" . $top_user_id
            );
            
            $usersTopbanner = $result->fetch();

            $this->view->top_banner_session = $_SESSION['top_banner_session'];
            $this->view->top_banner = $usersTopbanner['top_banner_show'];

            $location_id_busi = $this->session->get('auth-identity')['location_id'];

            $this->view->top_location_id = $location_id_busi;
            
            /***** business connection *****/

            $conditions = "location_id = :location_id:";

            $parameters = array(
                "location_id" => $location_id_busi
            );

            $loc = Location::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters
                )
            );

            $this->view->location = $loc;

            $objGoogleReviewSite = $this->GetLocationReviewSite(
                $location_id_busi,
                \Vokuro\Models\Location::TYPE_GOOGLE
            );
            
            $this->view->GoogleMyBusinessConnected = $objGoogleReviewSite && $objGoogleReviewSite->json_access_token ? true : false;

            $objfacebookReviewSite = $this->GetLocationReviewSite(
                $location_id_busi,
                \Vokuro\Models\Location::TYPE_FACEBOOK
            );
            
            $this->view->facebookMyBusinessConnected = $objfacebookReviewSite && $objfacebookReviewSite->access_token ? true : false;

            $objyelpReviewSite = $this->GetLocationReviewSite(
                $location_id_busi,
                \Vokuro\Models\Location::TYPE_YELP
            );
            
            $this->view->YelpMyBusinessConnected = isset($objyelpReviewSite->external_location_id) && $objyelpReviewSite->external_location_id && $objyelpReviewSite->external_location_id != '';

            /***** business connection *****/

            $userObj = $this->getUserObject();
            $this->view->loggedUser = $userObj;
            
            
            // find the agency
            $conditions = "agency_id = :agency_id:";

            $parameters = array(
                "agency_id" => $userObj->agency_id
            );

            $agency = Agency::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters
                )
            );

            if ($agency) {
                if ($agency->parent_id != 0) {
                    $conditions = "agency_id = :agency_id:";
                    $parameters = array(
                        "agency_id" => $agency->parent_id
                    );

                    $parentagency = Agency::findFirst(
                        array(
                            $conditions,
                            "bind" => $parameters
                        )
                    );

                    $this->view->setVar('agency_sms', trim($parentagency->SMS_message));
                }

                list($r, $g, $b) = sscanf($agency->main_color, "#%02x%02x%02x");
                $rgb = $r . ', ' . $g . ', ' . $b;

                $this->view->setVars([
                    'main_color_setting' => $agency->main_color,
                    'rgb' => $rgb,
                    'logo_path' =>  "/img/agency_logos/" . $agency->logo_path
                ]);

                $db = $this->di->get('db');
                $db->begin();
                $agency->agency_id;

                $result = $this->db->query(
                    "SELECT * FROM `notification` WHERE `to` =".$agency->agency_id." AND `read` = 0"
                );

                $x = $result->numRows();
                $this->view->NumberOfNotification;
                $this->view->setVar('NumberOfNotification', $x);
                $this->view->setVar('NumberAgency', $agency->agency_id);
            }

            $objSubscriptionManager = new \Vokuro\Services\SubscriptionManager();

            $this->view->ReachedMaxSMS = $userObj->is_admin ? false : $objSubscriptionManager->ReachedMaxSMS($agency->agency_id, $this->session->get('auth-identity')['location_id'])['ReachedLimit'];

            if ($agency->parent_id > 0) {

                // We're a business under an agency
                $objParentAgency = \Vokuro\Models\Agency::findFirst("agency_id = {$agency->parent_id}");

                $this->view->primary_color = $objParentAgency->main_color ?: "#2a3644";
                $this->view->secondary_color = $objParentAgency->secondary_color ?: "#2eb82e";
                $this->view->objParentAgency = $objParentAgency;
                
                $this->view->logo_path = ($objParentAgency->logo_path != "" ) ? "/img/agency_logos/{$objParentAgency->logo_path}" : "" ;
                $this->view->agencyName =  $objParentAgency->name;
            } else {
                $this->view->primary_color = "#2a3644";
                $this->view->secondary_color = "#2eb82e";
                $this->view->objParentAgency = null;
                $this->view->agencyName = ($agency->parent_id == -1 || ($this->user_object->is_admin == 1)) ? "Get Mobile Reviews" : $agency->name;

                if ($agency->parent_id == \Vokuro\Models\Agency::AGENCY) {
                    if (isset($agency->logo_path) && ($agency->logo_path != "")) {
                        if (strpos($agency->logo_path,'img/upload') !== false) {
                            $this->view->logo_path = "{$agency->logo_path}";
                        } else {
                            $this->view->logo_path = "/img/agency_logos/{$agency->logo_path}"; 
                        }
                    } else {
                        $this->view->logo_path = '/assets/layouts/layout/img/logo.png';
                    }
                } else {
                    // We're a business under RV
                    if($agency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV) {
                        $this->view->logo_path = '/assets/layouts/layout/img/logo.png';
                    }
                }
            }

            $objSMSManager = $this->di->get('smsManager');
            $tTwilioKeys = $objSMSManager->getTwilioKeys($agency->agency_id, $userObj->is_admin);

            $this->view->twilio_auth_messaging_sid = $this->twilio_auth_messaging_sid = $tTwilioKeys['twilio_auth_messaging_sid'];
            $this->view->twilio_auth_token = $this->twilio_auth_token = $tTwilioKeys['twilio_auth_token'];
            $this->view->twilio_from_phone = $this->twilio_from_phone = $tTwilioKeys['twilio_from_phone'];
            $this->view->twilio_api_key = $this->twilio_api_key = $tTwilioKeys['twilio_api_key'];

            // internal navigation parameters
            $this->configureNavigation($identity);

            if ($agency->parent_id > 0 || $agency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV) {
                /*
                 * Has this user provided their credit card info?
                 */
                $required = $this->di->get('subscriptionManager')->creditCardInfoRequired($this->session);
                $this->view->ccInfoRequired = $required ? "open" : "closed";
                $this->view->paymentService = 'Stripe';

                $objPricingPlan = $agency->subscription_id ? \Vokuro\Models\SubscriptionPricingPlan::findFirst('id = ' . $agency->subscription_id) : '';

                $objStripeSubscription = \Vokuro\Models\StripeSubscriptions::findFirst(
                    'user_id = ' . $identity['id']
                );

                $this->view->DisplaySubPopup = false;

                $this->view->SubscriptionLevel = $objSubscriptionManager->GetBusinessSubscriptionLevel(
                    $agency->agency_id
                );
                
                $userManager = $this->di->get('userManager');
                $this->view->NonTrial = false;

                if ($required == \Vokuro\Services\SubscriptionManager::CC_NON_TRIAL) {
                    // Non trial account.  Have we got their credit card yet?
                    if ($objStripeSubscription->stripe_customer_id
                        && ($objStripeSubscription->stripe_subscription_id == "N" || !$objStripeSubscription->stripe_subscription_id)) {
                        // Have CC info, but no subscription.  Redirect to business subscription page
                        if (strpos($_SERVER['REQUEST_URI'], 'businessSubscription') === false && !$agency->signup_page) {
                            $this->response->redirect("/businessSubscription");
                        }

                        $this->view->NonTrial = true;
                    }

                    if (!$objStripeSubscription->stripe_customer_id) {
                        if (strpos($_SERVER['REQUEST_URI'], 'businessSubscription') === false && !$agency->signup_page) {
                            $this->response->redirect("/businessSubscription");
                        }

                        $this->view->NonTrial = true;
                    }
                    $this->view->DisplaySubPopup = true;
                    $this->view->ccInfoRequired = "closed";
                }
            }

            // Check if business should be disabled
            $this->view->BusinessDisableBecauseOfStripe = false;
            $this->view->invalidBusinessSubscription = false;

            // Are we a business without an active subscription plan?
            if ($agency->parent_id > 0
                && (!$objStripeSubscription || !$objStripeSubscription->stripe_subscription_id || $objStripeSubscription->stripe_subscription_id == 'N')) {
                $this->view->invalidBusinessSubscription = true;

                // Disable business if agency has no stripe keys enabled.
                if (!$objParentAgency->stripe_publishable_keys || !$objParentAgency->stripe_account_secret) {
                    $this->view->BusinessDisableBecauseOfStripe = true;
                }
            }

            if ($agency->parent_id > 0) {
                $this->view->stripePublishableKey = $objParentAgency->stripe_publishable_keys;
            }

            // End disabled check

            $this->view->objAgency = $agency;

            // Should popup agency stripe modal?
            $this->AgencyInvalidStripe = false;
            $this->view->ShowAgencyStripePopup = false;

            if ($agency->parent_id == \Vokuro\Models\Agency::AGENCY) {
                if ((!$agency->stripe_publishable_keys || !$agency->stripe_account_secret) && !$userObj->is_admin) {
                    $this->view->AgencyInvalidStripe = true;
                    $this->view->ShowAgencyStripePopup = true;
                }

                $this->view->stripePublishableKey = ($agency && isset($agency->strip_publishable_key)) ? $agency->stripe_publishable_key : null;
            } else if ($agency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV) {
                $this->view->stripePublishableKey = $this->config->stripe->publishable_key;
            }

            $this->view->businessEmail = $agency->email;
            // End stripe modal

            if ($this->session->StripePopupDisabled) {
                $this->view->ShowAgencyStripePopup = false;
            }

            $conditions = "location_id = :location_id:";

            $parameters = array(
                "location_id" => $this->session->get('auth-identity')['location_id']
            );

            $loc = Location::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters)
            );

            $agencytype = $this->session->get('auth-identity')['agencytype'];

            $this->view->setVars([
                'agency' => $agency,
                'location' => $loc,
                'agencytype' => $agencytype,
                'location_id' => $this->session->get('auth-identity')['location_id'],
                'locations' => $this->session->get('auth-identity')['locations'],
                'is_admin' => $this->session->get('auth-identity')['is_admin'],
                'profile' => $this->session->get('auth-identity')['profile'],
                'name' => $this->session->get('auth-identity')['name'],
                'is_business_admin' => strpos($this->session->get('auth-identity')['profile'], 'Admin')
            ]);

            $this->getShareInfo($agency);
            $this->getTotalSMSSent($agency);
        }

        // find white label info based on the url
        $tHost = explode(".", $_SERVER['HTTP_HOST']);
        $sub = array_shift($tHost);

        if ($sub
            && $sub != ''
            && $sub != 'local'
            && $sub != 'my'
            && $sub != 'www'
            && $sub != 'reviewvelocity'
            && $sub != '104'
            && $sub != 'getmobilereviews') {

            // find the agency object
            $conditions = "custom_domain = :custom_domain:";

            $parameters = array(
                "custom_domain" => $sub
            );

            $agency = Agency::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters
                )
            );

            if ($agency) {
                $agency->logo_path = ($agency->logo_path == "") ? "" : "/img/agency_logos/" . $agency->logo_path;
                list($r, $g, $b) = sscanf($agency->main_color, "#%02x%02x%02x");
                $rgb = $r . ', ' . $g . ', ' . $b;
                $vars = [
                    'agency_id' => $agency->agency_id,
                    'agency' => $agency,
                    'agency_name' => $agency->name,
                    'main_color_setting' => $agency->main_color,
                    'rgb' => $rgb,
                    'logo_path' => $agency->logo_path,
                    'main_color' => str_replace('#', '', $agency->main_color),
                    'primary_color' => str_replace('#', '', $agency->main_color),
                    'secondary_color' => str_replace('#', '', $agency->secondary_color)
                ];

                $this->view->setVars($vars);
            }
        } else if ($this->session->has("sharing_code")) {

            $code = $this->session->get("sharing_code");

            $conditions = "viral_sharing_code = :viral_sharing_code:";

            $parameters = array(
                "viral_sharing_code" => $code
            );

            $agency = Agency::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters
                )
            );


            if ($agency->parent_id) {
                $agency1 = \Vokuro\Models\Agency::findFirst(
                    "agency_id = {$agency->parent_id}"
                );

                $this->view->agencyId = $agency1->agency_id;
                $this->view->agency_name = $agency1->name;
                $this->view->agency=$agency1;

            }

            $agency = $agency1;
        }

        $this->agency = $agency;

        if ($this->request->getPost('main_color')) {
            list($r, $g, $b) = sscanf($this->request->getPost('main_color'), "#%02x%02x%02x");
            $rgb = $r . ', ' . $g . ', ' . $b;
            $this->view->setVars([
                'main_color_setting' => $this->request->getPost('main_color'),
                'rgb' => $rgb
            ]);
        }
    }

    /**
     * Execute before the router so we can determine if this is a provate controller, and must be authenticated, or a
     * public controller that is open to all.
     *
     * @param Dispatcher $dispatcher
     * @return boolean
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerName();

        // Only check permissions on private controllers
        if ($this->acl->isPrivate($controllerName)) {

            // Get the current identity
            $identity = $this->auth->getIdentity();

            // If there is no identity available the user is redirected to index/index
            if (!is_array($identity)) {

                $this->flash->notice('You don\'t have access to this module: private');

                $dispatcher->forward(array(
                    'controller' => 'index',
                    'action' => 'index'
                ));
                return false;
            }


            // Check if the user have permission to the current option
            $actionName = $dispatcher->getActionName();
            if (!$this->acl->isAllowed($identity['profile'], $controllerName, $actionName)) {

                $this->flash->notice(
                    'You don\'t have access to this module: ' . $controllerName . ':' . $actionName
                );

                if ($this->acl->isAllowed($identity['profile'], $controllerName, 'index')) {
                    $dispatcher->forward(array(
                        'controller' => $controllerName,
                        'action' => 'index'
                    ));
                } else {
                    $dispatcher->forward(array(
                        'controller' => 'user_control',
                        'action' => 'index'
                    ));
                }

                return false;
            }
        }
    }

    public function getShareInfo($agency)
    {
        if (!$agency->viral_sharing_code) {
            $agency->viral_sharing_code = SharingCode::GenerateShareCode();
            $agency->save();
        }

        $this->view->share = $agency->viral_sharing_code;

        // build share links
        if ($this->config->application->environment == 'dev') {
            $Domain = $_SERVER['HTTP_HOST'];
        } else {
            $Domain = $agency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV ? 'reviewvelocity.co' : $agency->custom_domain;
        }

        $TLDomain = $this->config->application->domain;

        if ($Domain == '') {
            $Domain = "{$TLDomain}";
        }

        if ($Domain == ''){
            $Domain = $_SERVER['HTTP_HOST'];
        }
        
        $share_link = $this->googleShortenURL(
            "http://{$Domain}/session/signup?code={$agency->viral_sharing_code}"
        );

        $message_parent = '';
        $objParentAgency = '';

        /**** 24.11.2016 ****/
        $objAgency = \Vokuro\Models\Agency::findFirst("agency_id = {$agency->agency_id}");

        if ($objAgency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV) {
            $AgencyName = "Get Mobile Reviews";
            $AgencyUser = "Zach Anderson";
        } else if ($objAgency->parent_id == \Vokuro\Models\Agency::AGENCY) {
            // Thinking about this... I don't think this case ever happens.  A user is created for a business, so I don't know when it would be an agency.
            $objAgencyUser = \Vokuro\Models\Users::findFirst(
                "agency_id = {$objAgency->agency_id} AND role='Super Admin'"
            );
            
            $AgencyUser = $objAgencyUser->name;
            $AgencyName = $objAgency->name;
        } else if ($objAgency->parent_id > 0) {
            $objParentAgency = \Vokuro\Models\Agency::findFirst(
                "agency_id = {$objAgency->parent_id}"
            );
            
            $objAgencyUser = \Vokuro\Models\Users::findFirst(
                "agency_id = {$objParentAgency->agency_id} AND role='Super Admin'"
            );
            
            $AgencyName = $objParentAgency->name;
            $AgencyUser = $objAgencyUser->name;
            
            $resultx = $this->db->query(
                "SELECT * FROM `agency` WHERE `agency_id`=" . $objAgency->parent_id
            );
            
            $x = $resultx->fetch();

            if ($x['viral_email']) {
                $message_parent = $x['viral_email']; 
            }
        }

        if ($objParentAgency->twitter_message) {
            $twitter_message_set = $objParentAgency->twitter_message;
        } else if ($agency->twitter_message) {
            $twitter_message_set = $agency->twitter_message;
        } else {
            $twitter_message_set = "I just started using this amazing new software for my business. They are giving away a trial account here: {$share_link}";
        }

        $twitter_message_set = str_replace('{link}', $share_link, $twitter_message_set);        

        if ($objParentAgency->viral_email == '') {
             $message_set = "I just started using this amazing new software for my business. They are giving away a trial account here: {ShareLink}";
        } else {
            $message_set=$objParentAgency->viral_email;
        }
        
        if ($message_set) {
            if (strpos(strtolower($message_set),'{sharelink}') === false) {
                $message_set .= '{ShareLink}';
            }

            $message_set = str_ireplace('{ShareLink}',$share_link,$message_set);
            $message_set = str_ireplace('{AgencyUser}',$AgencyUser,$message_set);
            $message_set = str_ireplace('{AgencyName}',$AgencyName,$message_set);
            $message_set = str_ireplace('{BusinessName}',$agency->name,$message_set);
        }
       
        $this->view->setVars([
            'AgencyUser' => $AgencyUser,
            'AgencyName' =>$AgencyName,
            'share_message' => $message_set,
            'twitter_message_set'  => $twitter_message_set,
            'share_link' => $share_link,
            'share_subject' => $agency->name . ', thought this was awesome!',
            'domain'    => $TLDomain
        ]);

        $base_sms_allowed = 100;
        $additional_allowed = 25;

        $num_signed_up = SharingCode::count("sharecode = '{$agency->viral_sharing_code}'");

        $num_discount = (int) ($num_signed_up / 3); // find how many three
        $objSubscriptionManager = new \Vokuro\Services\SubscriptionManager();
        $identity = $this->session->get('auth-identity');

        if ($agency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV || $agency->parent_id > 0) {
            $MaxSMS = $objSubscriptionManager->GetMaxSMS($agency->agency_id, $identity['location_id']);
        } else {
            $MaxSMS = 0;
        }

        $NonViralSMS = $MaxSMS;
        $ViralSMS = $objSubscriptionManager->GetViralSMSCount($agency->agency_id);

        // $MaxSMS += $ViralSMS;
        $this->view->setVars([
            'total_sms_month' => $MaxSMS,
            'num_discount' => $num_discount,
            'num_signed_up' => $num_signed_up,
            'base_sms_allowed' => $base_sms_allowed,
            'additional_allowed' => $additional_allowed,
            'viral_sms' => $ViralSMS,
            'non_viral_sms' => $NonViralSMS,
        ]);
    }

    public function getSMSReport()
    {
        // check if the user should get the upgrade message (Only "business" agency_types who are signed up for Free accounts,
        // get the upgrade message)
        $is_upgrade = false;

        if ($this->session->get('auth-identity')['agencytype'] == 'business') {
            // GARY_TODO:  Refactor.  The agency_id I would think would be in the auth-identity, but it appears not to be.

            $objUser = \Vokuro\Models\Users::findFirst(
                "id = " . $this->session->get('auth-identity')['id']
            );

            $agency = \Vokuro\Models\Agency::findFirst(
                "agency_id = " . $objUser->agency_id
            );

            // we have a business, so check if free
            if ($agency->subscription_id > 0) {
                // we have a subscription, check if free
                $conditions = "subscription_id = :subscription_id:";

                $parameters = array(
                    "subscription_id" => $agency->subscription_id
                );

                $subscriptionobj = \Vokuro\Models\Subscription::findFirst(
                    array($conditions, "bind" => $parameters)
                );

                if (!($subscriptionobj->amount > 0)) {
                    $is_upgrade = true;
                }
            } else {
                $is_upgrade = true;
            }
        }

        $this->view->is_upgrade = $is_upgrade;

        if ($this->session->get('auth-identity')['agencytype'] == 'business') {
            if ($is_upgrade) {
                $identity = $this->auth->getIdentity();

                // find user
                $conditions = "id = :id:";

                $parameters = array(
                    "id" => $identity['id']
                );

                $userObj = Users::findFirst(
                    array($conditions, "bind" => $parameters)
                );

                // find the agency
                $conditions = "agency_id = :agency_id:";

                $parameters = array(
                    "agency_id" => $userObj->agency_id
                );

                $agency = Agency::findFirst(
                    array($conditions, "bind" => $parameters)
                );

                // get total sent
                $this->getTotalSMSSent($agency);

                // get share info
                $this->getShareInfo($agency);
            }

            // Last month!
            $start_time = date("Y-m-d", strtotime("first day of previous month"));
            $end_time = date("Y-m-d 23:59:59", strtotime("last day of previous month"));


            /* REFACTOR: Fix when there is not a location yet */
            $sms_sent_last_month = 0;

            if ($this->session->get('auth-identity')['location_id']) {
                $sms_sent_last_month = ReviewInvite::count(
                    array(
                        "column" => "review_invite_id",
                        "conditions" => "date_sent >= '" . $start_time . "' AND date_sent <= '" . $end_time . "' AND location_id = " . $this->session->get('auth-identity')['location_id'] . " AND sms_broadcast_id IS NULL",
                    )
                );
            }

            $this->view->sms_sent_last_month = $sms_sent_last_month;


            // This month!
            $start_time = date("Y-m-d", strtotime("first day of this month"));
            $end_time = date("Y-m-d 23:59:59", strtotime("last day of this month"));
            $sms_sent_this_month = 0;

            if ($this->session->get('auth-identity')['location_id']) {
                $sms_sent_this_month = ReviewInvite::count(
                    array(
                        "column" => "review_invite_id",
                        "conditions" => "date_sent >= '" . $start_time . "' AND date_sent <= '" . $end_time . "' AND location_id = " . $this->session->get('auth-identity')['location_id'] . " AND sms_broadcast_id IS NULL",
                    )
                );
            }

            $this->view->sms_sent_this_month = $sms_sent_this_month;

            // Last month!
            $this->view->num_reviews_last_month = 0;
            $this->view->num_reviews_two_months_ago = 0;

            if ($this->session->get('auth-identity')['location_id']) {
                $this->view->num_reviews_last_month = ReviewsMonthly::sum(
                    array(
                        "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                        "conditions" => "month = " . date("m", strtotime("first day of previous month")) . " AND year = '" . date("Y", strtotime("first day of previous month")) . "' AND location_id = " . $this->session->get('auth-identity')['location_id'],
                    )
                );

                $this->view->num_reviews_two_months_ago = ReviewsMonthly::sum(
                    array(
                        "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                        "conditions" => "month = " . date("m", strtotime("-2 months", time())) . " AND year = '" . date("Y", strtotime("-2 months", time())) . "' AND location_id = " . $this->session->get('auth-identity')['location_id'],
                    )
                );
            }

            $this->view->total_reviews_last_month = $this->view->num_reviews_last_month - $this->view->num_reviews_two_months_ago;

            // This month!
            $this->view->num_reviews_this_month = 0;

            if ($this->session->get('auth-identity')['location_id']) {
                $this->view->num_reviews_this_month = ReviewsMonthly::sum(
                    array(
                        "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                        "conditions" => "month = " . date("m", strtotime("first day of this month")) . " AND year = '" . date("Y", strtotime("first day of this month")) . "' AND location_id = " . $this->session->get('auth-identity')['location_id'],
                    )
                );
            }

            // find the location
            $this->view->review_goal = 0;

            $percent_needed = 10;
            
            $this->view->percent_needed = $percent_needed;
            $this->view->total_sms_needed = 0;

            if ($this->session->get('auth-identity')['location_id']) {
                $conditions = "location_id = :location_id:";
                
                $parameters = array(
                    "location_id" => $this->session->get('auth-identity')['location_id']
                );
                
                $location = Location::findFirst(array($conditions, "bind" => $parameters));

                // set the agency SMS limit
                $this->view->review_goal = $location->review_goal;

                // calculate how many sms messages we need to send to meet this goal.
                // $percent_needed = ($sms_sent_last_month>0?($this->view->total_reviews_last_month / $sms_sent_last_month)*100:0);
                // if ($percent_needed <= 0)
                $percent_needed = 10;
                $this->view->percent_needed = $percent_needed;
                $this->view->total_sms_needed = round($location->review_goal / ($percent_needed / 100));
            }
        } // end checking for business vs agency
    }

    public function getTotalSMSSent($agency)
    {
        // Total SMS Sent this month
        $start_time = date("Y-m-d", strtotime("first day of this month"));
        $end_time = date("Y-m-d 23:59:59", strtotime("last day of this month"));
        $sql = "SELECT review_invite_id "
              . "FROM review_invite "
              . "INNER JOIN location ON location.location_id = review_invite.location_id "
              . "WHERE location.agency_id = " . $agency->agency_id . " "
              . "AND date_sent >= '" . $start_time . "' "
              . "AND date_sent <= '" . $end_time . "' "
              . "AND sms_broadcast_id IS NULL";

        // Base model
        $list = new ReviewInvite();

        // Execute the query
        $params = null;
        $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
        $this->view->sms_sent_this_month_total = $rs->count();

        $sql1 = "SELECT review_invite_id "
                . "FROM review_invite "
                . "INNER JOIN location ON location.location_id = review_invite.location_id "
                . "WHERE location.agency_id = " . $agency->agency_id . " "
                . "AND date_sent >= '" . $start_time . "' "
                . "AND date_sent <= '" . $end_time . "' "
                . "AND sms_broadcast_id > 0";

        // Base model
        $list = new ReviewInvite();

        // Execute the query
        $params = null;
        $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql1, $params));
        $this->view->sms_sent_this_month_total_non = $rs->count();
    }

    public function SendSMS($phone, $smsBody, $AccountSid, $AuthToken, $twilio_from_phone)
    {
        if (!$AccountSid || !$AuthToken || !$twilio_from_phone) {
            return false;
        }

        $identity = $this->auth->getIdentity();
        $idxcx = $identity['id'];
        $sql = "SELECT * "
               . "FROM `twilio_number_to_business` "
               . "WHERE `buisness_id` = '" . $idxcx . "'";

        $result = $this->db->query();

        $smsdetails = $result->fetch();
        $xcd = $result->numRows();
        
        if ($xcd != 0) {
            $AccountSid = $smsdetails['parent_twilio_api_key'];
            $AuthToken = $smsdetails['parent_twilio_auth_token'];
            $twilio_from_phone = $smsdetails['phone_number'];
        }

        $client = new Services_Twilio($AccountSid, $AuthToken);

        try {
            $message = $client->account->messages->create(array(
                "From" => $this->formatTwilioPhone($twilio_from_phone),
                "To" => $phone,
                "Body" => $smsBody,
            ));
        } catch (Services_Twilio_RestException $e) {
            return false;
        }

        return true;
    }

    public function formatTwilioPhone($phone)
    {
        $phone = preg_replace('/\D+/', '', $phone);
        
        if (strlen($phone) == 10) {
            $phone = '1' . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Gets the site URL for the agency
     */
    public function getURL($subdomain)
    {
        // if we don't have the subdomain, then we need to find it
    }

    /**
     * Calls the Google API to shorten a URL
     */
    public function googleShortenURL($longUrl)
    {
        // Get API key from : http://code.google.com/apis/console/
        $apiKey = 'AIzaSyAPisblAqZJJ7mGWcORf4FBjNMQKV20J20';

        $postData = array('longUrl' => $longUrl);
        $jsonData = json_encode($postData);

        $curlObj = curl_init();

        curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key=' . $apiKey);
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

        $response = curl_exec($curlObj);

        $json = json_decode($response);

        curl_close($curlObj);

        return (isset($json->id) ? $json->id : $longUrl);
    }

    /**
     * Searches for users
     */



    public function usersFunctionality($profilesId, $locationid = 0)
    {
        $this->view->profilesId = $profilesId;

        // get the user id
        $identity = $this->auth->getIdentity();

        // If there is no identity available the user is redirected to index/index
        if (!is_array($identity)) {
            $this->response->redirect(
                '/session/login?return=/users/' . ($profilesId == 3 ? '' : 'admin')
            );
            
            $this->view->disable();
            
            return;
        }

        // Query binding parameters with string placeholders
        $conditions = "id = :id:";
        $parameters = array("id" => $identity['id']);
        $userObj = Users::findFirst(array($conditions, "bind" => $parameters));

        if ($locationid > 0) {
            //else only show the user the employees from the locations that they have access to
            $users = Users::getEmployeesByLocation($locationid);
            $usersGenerate = Users::getEmployeesByLocation($locationid);
        } else {
            $conditions = "location_id = :location_id:";

            $parameters = array(
                "location_id" => $this->session->get('auth-identity')['location_id']
            );

            $loc = Location::findFirst(
                array(
                    $conditions,
                    "bind" => $parameters
                )
            );

            //default this month
            $now = new \DateTime('now');
            $start_time = $now->format('Y') . '-' . $now->format('m') . '-01';
            $end_time = date("Y-m-d 23:59:59", strtotime("last day of this month"));

            if (isset($_GET['t']) && $_GET['t'] == 'lm') {
                $start_time = date("Y-m-d", strtotime("first day of previous month"));
                $end_time = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
            } else if (isset($_GET['t']) && $_GET['t'] == 'l') {
                $start_time = false;
                $end_time = false;
            } else if (isset($_GET['t']) && $_GET['t'] == 'c') {
                $start_time = $_POST['start'];
                $end_time = $_POST['end'];
                $start_time = date("Y-m-d H:i:s", strtotime($start_time));
                $end_time = date("Y-m-d H:i:s", strtotime($end_time));
            }

            if (strpos($_SERVER['REQUEST_URI'], 'users/admin') !== false) {
                if ($loc) {
                    $users = Users::getEmployeeListReport(
                        $userObj->agency_id,
                        false,
                        false,
                        $this->session->get('auth-identity')['location_id'],
                        $loc->review_invite_type_id,
                        $profilesId,
                        false
                    );

                    $usersGenerate = Users::getEmployeeListReport(
                        $userObj->agency_id,
                        false,
                        false,
                        $this->session->get('auth-identity')['location_id'],
                        $loc->review_invite_type_id,
                        $profilesId,
                        false
                    );
                }
            } else {
                $users_report = null;

                if ($loc) {
                    $users_report = Users::getEmployeeListReport(
                        $userObj->agency_id,
                        $start_time,
                        $end_time,
                        $this->session->get('auth-identity')['location_id'],
                        $loc->review_invite_type_id,
                        false,
                        true
                    );
                    
                    $users = Users::getEmployeeListReport(
                        $userObj->agency_id,
                        false,
                        false,
                        $this->session->get('auth-identity')['location_id'],
                        $loc->review_invite_type_id,
                        false,
                        true
                    );

                    $usersGenerate = Users::getEmployeeListReportGenerate(
                        $userObj->agency_id,
                        false,
                        false,
                        $this->session->get('auth-identity')['location_id'],
                        $loc->review_invite_type_id,
                        false,
                        true
                    );

                    $users_report_generate = Users::getEmployeeConversionReportGenerate(
                        $loc->review_invite_type_id,
                        $userObj->agency_id,
                        $start_time,
                        $end_time,
                        $this->session->get('auth-identity')['location_id'],
                        'desc'
                    );
                    
                    if ($loc->review_invite_type_id != '') {
                        $this->view->review_invite_type_id = $loc->review_invite_type_id; 
                    } else {
                        $this->view->review_invite_type_id = 1;
                    }
                    
                    $Reviewlist = ReviewInvite::FnallReview(true, 2);
                    
                    $this->view->Reviewlist = $Reviewlist;
                    $user_array = array();

                    foreach ($Reviewlist as $reviews) {
                        if (!in_array($reviews->sent_by_user_id, $user_array)) {
                            array_push($user_array, $reviews->sent_by_user_id);
                        }
                    }

                    $rating = array();

                    if (!empty($user_array)) {
                        for ($i = 0; $i < count($user_array); $i++) {
                            $type = 2;
                            $value = ReviewInvite::starrating(true, $user_array[$i]);

                            foreach ($value as $rates) {
                                $rating[$user_array[$i]] = $rates->rates . '-' . $rates->counts;
                            }
                        }
                    }
                    
                    $this->view->rating = $rating;

                    /*** number rating friday***/

                    $ReviewlistNumer = ReviewInvite::FnallReviewNumber(true, 3);
                   
                    $user_array_number = array();
                
                    foreach ($ReviewlistNumer as $reviews) {
                        if (!in_array($reviews->sent_by_user_id, $user_array_number)) {
                            array_push($user_array_number, $reviews->sent_by_user_id);
                        }
                    }

                    $rating_number = array();

                    for ($i = 0; $i < count($user_array_number); $i++) {
                        $value_number = ReviewInvite::starratingnumber(true, $user_array_number[$i]);

                        foreach ($value_number as $rates) {
                            $rating_number[$user_array_number[$i]] = $rates->rates . '-' . $rates->counts;
                        }
                    }

                    $this->view->rating_number = $rating_number;

                    /*** number rating ***/
                }

                $this->view->users_report = $users_report;
                $this->view->users_report_generate = $users_report_generate;
            }
        }

        if (!isset($users) || count($users) == 0) {
            if ($locationid <= 0) {
                $this->flash->notice(
                    "The search did not find any " . ($profilesId == 3 ? 'employees' : 'admin users')
                );
            }
        }

        if (isset($users)) {
            $this->view->users = $users;
            $this->view->usersGenerate = $usersGenerate;
        }

        $rating_array_set = array();
        $YNrating_array_set = array();

        foreach ($users_report_generate as $ux) {
            $sql = "SELECT COUNT(*) AS `numberx`, `review_invite_type_id`, `rating` "
                   . "FROM `review_invite` "
                   . "WHERE `sent_by_user_id` = " . $ux->id . " "
                   . "AND `review_invite_type_id` = 1 "
                   . "GROUP BY `rating`";

            // Base model
            $list = new ReviewInvite();

            // Execute the query
            $params = null;
            $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
            $YNrating_array_set[$ux->id] = $rs->toArray();
        }

        $this->view->YNrating_array_set = $YNrating_array_set;
        
        foreach ($users_report_generate as $ux){
            $sql = "SELECT COUNT(*) AS `numberx`, `review_invite_type_id`, SUM(  `rating` ) AS  `totalx` "
                   . "FROM `review_invite` "
                   . "WHERE `sent_by_user_id` = " . $ux->id . " "
                   . "GROUP BY  `review_invite_type_id`";

            // Base model
            $list = new ReviewInvite();

            // Execute the query
            $params = null;
            $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
            $rating_array_set[$ux->id] = $rs->toArray();
        }

        $this->view->rating_array_set = $rating_array_set;

        $rating_array_set_all = array();
        $YNrating_array_set_all = array();

        foreach ($usersGenerate as $ux) {
            $sql = "SELECT COUNT(*) AS  `numberx`, `review_invite_type_id`, `rating` "
                   . "FROM `review_invite` "
                   . "WHERE `sent_by_user_id` = " . $ux->id . " "
                   . "AND `review_invite_type_id` = 1 "
                   . "GROUP BY  `rating`";

            // Base model
            $list = new ReviewInvite();

            // Execute the query
            $params = null;
            $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
            $YNrating_array_set_all[$ux->id] = $rs->toArray();
        }

        $this->view->YNrating_array_set_all = $YNrating_array_set_all;

        foreach ($usersGenerate as $ux) {
            $sql = "SELECT COUNT(*) AS `numberx` ,`review_invite_type_id` , SUM(  `rating` ) AS  `totalx` "
                   . "FROM  `review_invite` "
                   . "WHERE  `sent_by_user_id` =" . $ux->id . " "
                   . "GROUP BY  `review_invite_type_id` ";

            // Base model
            $list = new ReviewInvite();

            // Execute the query
            $params = null;
            $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
            $rating_array_set_all[$ux->id] = $rs->toArray();
        }

        $this->view->rating_array_set_all = $rating_array_set_all;
    }

    public function importGoogle($Obj, $location, &$foundagency)
    {
        $google = new GoogleScanning();

        $google_reviews = $google->get_business($Obj->api_id);

        // import data from the feed into the database, first update the location
        $Obj->rating = $google_reviews['rating'];
        $Obj->review_count = (isset($google_reviews['user_ratings_total']) ? $google_reviews['user_ratings_total'] : 0);

        if (!isset($Obj->original_review_count) || (!($Obj->original_review_count > 0)) || $Obj->original_review_count > $Obj->review_count) {
            $Obj->original_rating = $Obj->rating;
            $Obj->original_review_count = $Obj->review_count;
        }

        $Obj->save();

        // now import the reviews (if not already in the database)
        // loop through reviews
        foreach ($google_reviews['reviews'] as $reviewDetails) {
            // check if the review is already in the db
            $conditions = "time_created = :time_created: AND rating_type_id = 3 AND location_id = " . $location->location_id;
            $parameters = array("time_created" => date("Y-m-d H:i:s", $reviewDetails['time']));
            
            $googlerev = Review::findFirst(
                array($conditions, "bind" => $parameters)
            );
            
            $n = 0;

            if (!$googlerev) {
                // we didn't find the review, so assign the values
                $r = new Review();

                $r->assign(array(
                    'rating_type_id' => 3, //3 = Google
                    'rating' => $reviewDetails['rating'],
                    'review_text' => $reviewDetails['text'],
                    'time_created' => date("Y-m-d H:i:s", $reviewDetails['time']),
                    'user_name' => $reviewDetails['author_name'],
                    'user_id' => $reviewDetails['author_url'],
                    'user_image' => (isset($reviewDetails['profile_photo_url']) ? $reviewDetails['profile_photo_url'] : ''),
                    // 'external_id' => $reviewDetails['id'],  google has no review id
                    'location_id' => $location->location_id,
                ));

                //save now
                $save = $r->save();

                if ($save) {
                    $n++;
                }

                // add agency to our found array
                if (isset($foundagency[$location->agency_id])) {
                    $foundagency[$location->agency_id] .= ', ';
                } else {
                    $foundagency[$location->agency_id] = '';
                }
                $foundagency[$location->agency_id] .= $location->name;
            }
        } // go to the next google review

            echo $n;
        $s = $this->di->get('ReviewService');

        /**
         * @var $s \Vokuro\Services\Reviews
         */
        try {
            if($location && $location->location_id) {
                $s->updateReviewCountByTypeAndLocationId(3, $location->location_id);
            }
        } catch (\Exception $e) {
            print "there was an error \n";
            print_r($e->getTraceAsString());
            exit();
        }
        return $Obj;
    }

    public function importYelp($Obj, $location, &$foundagency)
    {
        // first initialize our scanners
        $yelp = new YelpScanning();
        $yelp->construct();

        $yelp_reviews = $yelp->get_business($Obj->api_id);
        $yelpreviews = json_decode($yelp_reviews);

        // import data from the feed into the database, first update the location
        $Obj->rating = $yelpreviews->rating;
        $Obj->review_count = $yelpreviews->review_count;

        if (!isset($Obj->original_review_count)
            || (!($Obj->original_review_count > 0))
            || $Obj->original_review_count > $Obj->review_count) {
            $Obj->original_rating = $yelpreviews->rating;
            $Obj->original_review_count = $yelpreviews->review_count;
        }

        $Obj->save();

        // now import the review (if not already in the database)
        // loop through reviews
        foreach ($yelpreviews->reviews as $rev) {
            // check if the review is already in the db
            $conditions = "external_id = :external_id: AND rating_type_id = 1 AND location_id = " . $location->location_id;
            $parameters = array("external_id" => $rev->id);
            $yelprev = Review::findFirst(array($conditions, "bind" => $parameters));

            if (!$yelprev) {
                // we didn't find the review, so assign the values
                $r = new Review();
                $r->assign(array(
                    'rating_type_id' => 1, //1 = Yelp
                    'rating' => $rev->rating,
                    'review_text' => $rev->excerpt,
                    'time_created' => date("Y-m-d H:i:s", $rev->time_created),
                    'user_name' => $rev->user->name,
                    'user_id' => $rev->user->id,
                    'user_image' => $rev->user->image_url,
                    'external_id' => $rev->id,
                    'location_id' => $location->location_id,
                ));

                // save now
                $r->save();

                // add agency to our found array
                if (isset($foundagency[$location->agency_id])) {
                    $foundagency[$location->agency_id] .= ', ';
                } else {
                    $foundagency[$location->agency_id] = '';
                }
                $foundagency[$location->agency_id] .= $location->name;
            }
        } // go to the next yelp review

        return $Obj;
    }

    public function importFacebook($Obj, $location, &$foundagency)
    {
        $face = new FacebookScanning();
        $this->facebook_access_token = $face->getAccessToken();

        // first initialize our scanners
        $yelp = new YelpScanning();
        $yelp->construct();

        // Facebook has special permissions on public reviews, so lets try to scrape them
        $url = 'https://www.facebook.com/' . $Obj->external_id . '/reviews/';
        $results = $yelp->getHTML($url);

        // get the review info from the html
        // <meta content="#" itemprop="ratingValue" />
        $pos = strpos($results, '" itemprop="ratingValue"');
        $rating = substr($results, 0, $pos);
        $pos2 = strrpos($rating, '"');
        $rating = substr($rating, $pos2 + 1);

        // <meta content="6" itemprop="ratingCount" />
        $pos = strpos($results, '" itemprop="ratingCount');
        $rating_count = substr($results, 0, $pos);
        $pos2 = strrpos($rating_count, '"');
        $rating_count = substr($rating_count, $pos2 + 1);

        // import data from the feed into the database, first update the location
        $Obj->rating = $rating;
        $Obj->review_count = $rating_count;
        
        if (!isset($Obj->original_review_count)
            || (!($Obj->original_review_count > 0))
            || $Obj->original_review_count > $Obj->review_count) {
            $Obj->original_rating = $Obj->rating;
            $Obj->original_review_count = $Obj->review_count;
        }

        $Obj->save();

        // if we have a facebook page token, try to import reviews
        if (isset($Obj->access_token) && $Obj->access_token != '') {
            // use the graph api to get facebook "ratings" aka reviews
            require_once __DIR__ . "/../library/Facebook/autoload.php";
            require_once __DIR__ . "/../library/Facebook/Facebook.php";
            require_once __DIR__ . "/../library/Facebook/FacebookApp.php";
            require_once __DIR__ . "/../library/Facebook/FacebookClient.php";
            require_once __DIR__ . "/../library/Facebook/FacebookRequest.php";
            require_once __DIR__ . "/../library/Facebook/FacebookResponse.php";
            require_once __DIR__ . "/../library/Facebook/Authentication/AccessToken.php";
            require_once __DIR__ . "/../library/Facebook/Authentication/OAuth2Client.php";
            require_once __DIR__ . "/../library/Facebook/Helpers/FacebookRedirectLoginHelper.php";
            require_once __DIR__ . "/../library/Facebook/PersistentData/PersistentDataInterface.php";
            require_once __DIR__ . "/../library/Facebook/PersistentData/FacebookSessionPersistentDataHandler.php";
            require_once __DIR__ . "/../library/Facebook/Url/UrlDetectionInterface.php";
            require_once __DIR__ . "/../library/Facebook/Url/FacebookUrlDetectionHandler.php";
            require_once __DIR__ . "/../library/Facebook/Url/FacebookUrlManipulator.php";
            require_once __DIR__ . "/../library/Facebook/PseudoRandomString/PseudoRandomStringGeneratorTrait.php";
            require_once __DIR__ . "/../library/Facebook/PseudoRandomString/PseudoRandomStringGeneratorInterface.php";
            require_once __DIR__ . "/../library/Facebook/PseudoRandomString/OpenSslPseudoRandomStringGenerator.php";
            require_once __DIR__ . "/../library/Facebook/HttpClients/FacebookHttpClientInterface.php";
            require_once __DIR__ . "/../library/Facebook/HttpClients/FacebookCurl.php";
            require_once __DIR__ . "/../library/Facebook/HttpClients/FacebookCurlHttpClient.php";
            require_once __DIR__ . "/../library/Facebook/Http/RequestBodyInterface.php";
            require_once __DIR__ . "/../library/Facebook/Http/RequestBodyUrlEncoded.php";
            require_once __DIR__ . "/../library/Facebook/Http/GraphRawResponse.php";
            require_once __DIR__ . "/../library/Facebook/Exceptions/FacebookSDKException.php";
            require_once __DIR__ . "/../library/Facebook/Exceptions/FacebookAuthorizationException.php";
            require_once __DIR__ . "/../library/Facebook/Exceptions/FacebookAuthenticationException.php";
            require_once __DIR__ . "/../library/Facebook/Exceptions/FacebookResponseException.php";

            $this->fb = new \Services\Facebook\Facebook(array(
                'app_id' => '628574057293652',
                'app_secret' => '95e89ebac7173ba0980c36d8aa5777e4'
            ));

            $url = '/me/accounts';
            $pages = $this->fb->get($url, $Obj->access_token)->getDecodedBody();

            $page_access_token = '';

            if (!empty($pages['data'])) {
                foreach ($pages['data'] as $page) {
                    if ($page['id'] == $Obj->external_id) {
                        $page_access_token = $page['access_token'];
                    }
                }
            }

            // if we found a page access token, try to find reviews
            if ($page_access_token != '') {
                $reviews = $face->getBusinessReviews($Obj->external_id, $page_access_token);

                if (isset($reviews) && $reviews != '') {
                    $reviews = json_decode($reviews);
                }

                // now import the reviews (if not already in the database)
                // loop through reviews
                foreach ($reviews->data as $reviewDetails) {
                    // check if the review is already in the db
                    $conditions = "time_created = :time_created: AND rating_type_id = 2 AND location_id = " . $location->location_id;
                    $phpdate = strtotime($reviewDetails->created_time);
                    $parameters = array("time_created" => date("Y-m-d H:i:s", $phpdate));
                    
                    $googlerev = Review::findFirst(
                        array($conditions, "bind" => $parameters)
                    );
                    
                    if (!$googlerev) {
                        // we didn't find the review, so assign the values
                        $r = new Review();

                        $r->assign(array(
                            'rating_type_id' => 2, //2 = Facebook
                            'rating' => $reviewDetails->rating,
                            'review_text' => $reviewDetails->review_text,
                            'time_created' => date("Y-m-d H:i:s", $phpdate),
                            'user_name' => $reviewDetails->reviewer->name,
                            'user_id' => $reviewDetails->reviewer->id,
                            'location_id' => $location->location_id,
                        ));

                        // save now
                        $r->save();

                        // add agency to our found array
                        if (isset($foundagency[$location->agency_id])) {
                            $foundagency[$location->agency_id] .= ', ';
                        } else {
                            $foundagency[$location->agency_id] = '';
                        }

                        $foundagency[$location->agency_id] .= $location->name;
                    }
                } // go to the next facebook review
            }
        } // end checking for an access token

        try {
            $s = $this->di->get('ReviewService');
            /**
             * @var $s \Vokuro\Services\Reviews
             */
            if ($location && $location->location_id) {
                $s->updateReviewCountByTypeAndLocationId(3, $location->location_id);
            }

        } catch (\Exception $e) {
            print "there was an error \n";
            print_r($e->getTraceAsString());
            exit();
        }

        return $Obj;
    }

    /**
     * Searches for yelp id
     */
    public function yelpId($id)
    {
        $yelp = new YelpScanning();
        $url = 'http://yelp.com/biz/' . $id;
        $results = $yelp->getHTML($url);

        //get the id from the html
        $pos = strpos($results, 'href="/writeareview/biz/');
        $results = substr($results, $pos + 24);
        $pos2 = strpos($results, '?');
        $results = substr($results, 0, $pos2);

        return $results;
    }

    public function GUID()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(16384, 20479),
                       mt_rand(32768, 49151),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535),
                       mt_rand(0, 65535)
                      );
    }

    public function uploadAction($agencyid)
    {
        // Check if the user has uploaded files
        if ($this->request->hasFiles() == true) {
            try {
                $baseLocation = '/var/www/html/' . $this->config->webpathfolder->path . '/public/img/agency_logo/';

                // Print the real file names and sizes
                foreach ($this->request->getUploadedFiles() as $file) {
                    if ($file->getName() != '') {
                        // Move the file into the application
                        $filepath = $baseLocation . uniqid('logo');
                        $file->moveTo($filepath);

                        // resize
                        $image = new \Phalcon\Image\Adapter\GD($filepath);
                        $image->resize(200, 30)->save($filepath);

                        $filepath = '/admin' . str_replace("/var/www/html/" . $this->config->webpathfolder->path . "/public", "", $filepath);
                        $this->view->logo_path = $filepath;

                        return $filepath;
                    }
                }
            } catch(\Exception $e) {
                // here we explicitly do nothing
            }
        } else {
            // echo '<p>hasFiles() == true!</p>';
        }
    }

    private function configureNavigation($identity)
    {
        $internalNavParams = [];

        // Services
        $userManager = $this->di->get('userManager');
        $subscriptionManager = $this->di->get('subscriptionManager');

        // Identity
        $internalNavParams['isSuperUser'] = $userManager->isSuperAdmin($this->session);
        $internalNavParams['isAgencyAdmin'] = $userManager->isAgency($this->session);
        $internalNavParams['isBusinessAdmin'] = $userManager->isBusiness($this->session);
        $internalNavParams['isEmployee'] = $userManager->isEmployee($this->session);

        $userId = $userManager->getUserId($this->session);
        $objUser = \Vokuro\Models\Users::findFirst('id = ' . $userId);

        // Get super admin user id
        $objSuperUser = \Vokuro\Models\Users::findFirst(
            'agency_id = ' . $objUser->agency_id . ' AND role="Super Admin"'
        );

        $objAgency = \Vokuro\Models\Agency::findFirst(
            'agency_id = ' . $objUser->agency_id
        );

        // Subscriptions
        $userSubscription = $subscriptionManager->getSubscriptionPlan(
            $objSuperUser->id,
            $objAgency->subscription_id
        );

        // GARY_TODO Determine if the comment below is accurate.
        $internalNavParams['hasSubscriptions'] = !$internalNavParams['isSuperUser']
            && ($internalNavParams['isAgencyAdmin'] || $internalNavParams['isBusinessAdmin'])
            && ($userSubscription['subscriptionPlan']['payment_plan'] != ServicesConsts::$PAYMENT_PLAN_FREE)
            && ($userManager->hasLocation($this->session)
                && $internalNavParams['isBusinessAdmin'] || $internalNavParams['isAgencyAdmin']);

        $internalNavParams['hasPricingPlans'] = $internalNavParams['isSuperUser'] || $internalNavParams['isAgencyAdmin'];

        if ($internalNavParams['hasSubscriptions'] && $internalNavParams['isBusinessAdmin']) {
            $internalNavParams['subscriptionController'] = '/businessSubscription';
        }

        if ($internalNavParams['hasSubscriptions'] && $internalNavParams['isAgencyAdmin']) {
            $internalNavParams['subscriptionController'] = '/businessPricingPlan';
        }

        $this->view->sub_id=$objAgency->subscription_id;
        $this->view->internalNavParams = $internalNavParams;
    }

    /**
     * This function takes either an array or string, sets the content type and returns the response
     * @param $json array|string
     * @return \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
     */
    public function sendJSON($json)
    {
        if (is_array($json)) {
            $json = json_encode($json);
        }

        $this->view->disable();
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent($json);
        return $this->response;
    }

    // gave it public, not sure how it is used throughout the app
    public function encode($val)
    {
        if ($val) {
            return str_replace("'", "%27", str_replace("\"", "%22", $val));
        } else {
            return '';
        }
    }

    // also gave this public
    public function extractFromAdress($components, $type)
    {
        if (!is_array($components)) {
            throw new \Exception('$components, the first argument must be an array');
        }

        for ($i = 0; $i < count($components); ++$i) {
            for ($j = 0; $j < count($components[$i]['types']); ++$j) {
                if ($components[$i]['types'][$j] == $type) {
                    return $components[$i]['short_name'];
                }
            }
        }

        return "";
    }

    public function cssAction()
    {
        $this->response->setHeader("Content-Type", "text/css");

        //for ie
        $this->response->setHeader('X-Content-Type-Options','nosniff');
        $main_color = $this->request->get('primary_color');
        $secondary_color = $this->request->get('secondary_color');
        $this->view->setVars(['primary_color'=>$main_color,'secondary_color'=>$secondary_color]);
        $this->view->setMainView('layouts/css');
    }

    public function getLocationId()
    {
        $identity = $this->getIdentity();

        return $identity['location_id'];
    }

    public function getIdentity()
    {
        return $this->auth->getIdentity();
    }

    public function getPermissions()
    {
        if ($this->permissions) return $this->permissions;

        $this->permissions = new Permissions();

        return $this->permissions;
    }

    public function getUserObject()
    {
        $identity = $this->getIdentity();

        if (!$identity) return false;
        
        // If there is no identity available the user is redirected to index/index
        // Query binding parameters with string placeholders
        $conditions = "id = :id:";
        $parameters = array("id" => $identity['id']);
        return Users::findFirst(array($conditions, "bind" => $parameters));
    }
}
