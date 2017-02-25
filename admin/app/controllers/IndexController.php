<?php

namespace Vokuro\Controllers;

use Vokuro\Models\Agency;
use Vokuro\Models\Location;
use Vokuro\Models\LocationReviewSite;
use Vokuro\Models\Review;
use Vokuro\Models\ReviewInvite;
use Vokuro\Models\ReviewsMonthly;
use Vokuro\Models\Users;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
/**
 * Display the default index page.
 */
class IndexController extends ControllerBase
{

    public function initialize()
    {

        $this->tag
             ->setTitle('Get Mobile Reviews | Dashboard');
        parent::initialize();

        //add needed css
        $this->assets
             ->addCss('/css/subscription.css')
             ->addCss('/assets/global/plugins/card-js/card-js.min.css');

        //add needed js
        $this->assets
             ->addJs('/assets/global/plugins/card-js/card-js.min.js');
    }

    /**
     * Default action. Set the public layout (layouts/private.volt)
     */
    public function indexAction()
    {
        $this->view->TLDomain = $this->config->application->domain;
        $tUser = $this->auth->getIdentity();
        $logged_in = is_array($tUser);
       
        if ($logged_in) {
            /*
             * TODO: The setup process is currently "baked" into the free signup sequence.
             * There's no time to rewrite it so I will add a check here to see if the user has
             * any locations yet.  If not, we know that this action is being called from
             * the create business sequence.
             */

            $top_user_id = $this->session->get('auth-identity')['id'];
            
            $identity = $this->session->get('auth-identity');

            $this->runIfLoggedIn($logged_in, $tUser, $top_user_id, $identity);
        } else {
            $this->runIfNotLoggedIn();
        }

        if ($identity['location_id'] > 0) {
            $this->step1($identity['location_id']);

            $this->step2($identity['location_id']);
        }
    }

    public function runIfLoggedIn($logged_in, $tUser, $top_user_id, $identity)
    {
        $result = $this->db->query(
            "SELECT * FROM `users` WHERE `id` = " . $top_user_id
        );

        $usersTopbanner = $result->fetch();
      
        $this->view->top_banner = $usersTopbanner['top_banner_show'];

        $userManager = $this->di->get('userManager');

        $isBusiness = $userManager->isBusiness($this->session);
        $signupPage = $userManager->currentSignupPage($this->session);

        if ($isBusiness && $signupPage) {
            $this->response->redirect('/session/signup' . $signupPage);
            return;
        }

        if (isset($_POST['locationselect'])) {
            $this->auth->setLocation($_POST['locationselect']);
        }

        $this->view->setVar('logged_in', $logged_in);
        $this->view->setTemplateBefore('private');


        if ($tUser['is_admin']) {
            $this->view->pick('admindashboard/index');
        } else {
            if (!$isBusiness) {
                /** agency redirect 23.11.2016**/

                $this->view->setTemplateBefore('public');


                $parts = explode(".", $_SERVER['SERVER_NAME']);

                if (count($parts) > 2 && $parts[0] != 'www') {
                    // Subdomain exists.  Probably should do count($parts) == 3.
                    $subdomain = $parts[0];

                    $agency = Agency::findFirst([
                        "custom_domain = :custom_domain:",
                        "bind" => ["custom_domain" => $subdomain]
                    ]);

                    // Subdomain must exist
                    if (!$agency) {
                        $this->response->setStatusCode(404, "Not Found");
                        echo "<h1>404 Page Not Found</h1>";
                        $this->view->disable();
                        return;
                    }

                    $objSuperUser = \Vokuro\Models\Users::findFirst(
                        "agency_id = {$agency->agency_id} AND role = 'Super Admin'"
                    );

                    // GARY_TODO:  Hackfix.  Deleting should set the enabled to 0 or something like that, rather than just modifying the name.  Getting this out stat though.
                    $objSubscriptionPricingPlan = \Vokuro\Models\SubscriptionPricingPlan::findFirst(
                        "is_viral = 1 AND user_id = {$objSuperUser->id} AND name NOT LIKE 'deleted-%'"
                    );
                    
                    if (!$objSubscriptionPricingPlan) {
                        $objSubscriptionPricingPlan = \Vokuro\Models\SubscriptionPricingPlan::findFirst(
                            "user_id = {$objSuperUser->id}"
                        );
                    }

                    $this->view->SubscriptionCode = $objSubscriptionPricingPlan->short_code;

                    $this->view->SubDomain = $subdomain;

                    if (!empty($_GET['name']) && !empty($_GET['phone'])) {
                        // Loaded from GET for preview page from agency signup process
                        $this->view->Name = $_GET['name'];
                        $this->view->Phone = $_GET['phone'];
                        $this->view->PrimaryColor = '#' . $_GET['primary_color'];
                        $this->view->SecondaryColor = '#' . $_GET['secondary_color'];
                        $this->view->logo_path = !empty($_GET['logo_path']) ? '/img/agency_logos/'.$_GET['logo_path'] : '';
                        $this->view->CleanUrl = true;
                    } else {
                        // Loaded from DB for subdomain
                        $this->view->Name = !empty($agency->name) ? $agency->name : (!empty($_SESSION['demo_name']) ? $_SESSION['demo_name'] : 'Agency');
                        $this->view->Phone = !empty($agency->phone) ? $agency->phone : '(888) 555-1212';
                        $this->view->PrimaryColor = !empty($agency->main_color) ? $agency->main_color : '#2a3644';
                        $this->view->SecondaryColor = !empty($agency->secondary_color) ? $agency->secondary_color : '#65CE4D';
                        $this->view->logo_path = !empty($agency->logo_path) ? '/img/agency_logos/' . $agency->logo_path : '';
                    }

                    $this->view->salesPage = true;
                    $this->view->pick('agencysignup/sales');
                    return;
                } else {
                    $this->response->redirect('/agency');
                    return;
                }
                /** agency redirect 23.11.2016**/
            }
        }


        $objUser = \Vokuro\Models\Users::findFirst("id = " . $identity['id']);
        $objBusiness = \Vokuro\Models\Agency::findFirst("agency_id = {$objUser->agency_id}");
        $objSubscriptionManager = new \Vokuro\Services\SubscriptionManager();
        $subsMgr = $objSubscriptionManager->GetBusinessSubscriptionLevel($objBusiness->agency_id);
        $subsDiscount = $objSubscriptionManager->GetBusinessSubscriptionUpgradeDiscount($objBusiness->agency_id);
        $this->view->SubscriptionPlan = $subsMgr;
        $this->view->DiscountAmount = $subsDiscount;
    }

    public function runIfNotLoggedIn()
    {
        // Check for use of whitelabel domain
        // Moved to here from AgencySignupController::salesAction (agencysignup/sales)
        // The best way to fully test this is to use your local hosts file to treat your local reviewvelocity server as getmobilereiews.com
        
        $parts = explode(".", $_SERVER['SERVER_NAME']);

        if (count($parts) > 2 && $parts[0] != 'www') { // Subdomain exists.  Probably should do count($parts) == 3.
            $subdomain = $parts[0];

            $agency = Agency::findFirst([
                    "custom_domain = :custom_domain:",
                    "bind" => ["custom_domain" => $subdomain]
                ]);

            // Subdomain must exist
            if (!$agency) {
                $this->response->setStatusCode(404, "Not Found");
                echo "<h1>404 Page Not Found</h1>";
                $this->view->disable();
                return;
            }

            $this->view->SubDomain = $subdomain;

            if (!empty($_GET['name']) && !empty($_GET['phone'])) {
                // Loaded from GET for preview page from agency signup process
                $this->view->Name = $_GET['name'];
                $this->view->Phone = $_GET['phone'];
                $this->view->PrimaryColor = '#'.$_GET['primary_color'];
                $this->view->SecondaryColor = '#'.$_GET['secondary_color'];
                $this->view->logo_path = !empty($_GET['logo_path']) ? '/img/agency_logos/'.$_GET['logo_path'] : '';
                $this->view->CleanUrl = true;
            } else {
                // Loaded from DB for subdomain
                $this->view->Name = !empty($agency->name) ? $agency->name : (!empty($_SESSION['demo_name']) ? $_SESSION['demo_name'] : 'Agency');
                $this->view->Phone = !empty($agency->phone) ? $agency->phone : '(888) 555-1212';
                $this->view->PrimaryColor = !empty($agency->main_color) ? $agency->main_color : '#2a3644';
                $this->view->SecondaryColor = !empty($agency->secondary_color) ? $agency->secondary_color : '#65CE4D';
                $this->view->logo_path = !empty($agency->logo_path) ? '/img/agency_logos/'.$agency->logo_path : '';
            }

            $objSuperUser = \Vokuro\Models\Users::findFirst(
                "agency_id = {$agency->agency_id} AND role = 'Super Admin'"
            );
            
            // GARY_TODO:  Hackfix.  Deleting should set the enabled to 0 or something like that, rather than just modifying the name.  Getting this out stat though.
            $objSubscriptionPricingPlan = \Vokuro\Models\SubscriptionPricingPlan::findFirst(
                "is_viral = 1 AND user_id = {$objSuperUser->id} AND name NOT LIKE 'deleted-%'"
            );
            
            if (!$objSubscriptionPricingPlan) {
                $objSubscriptionPricingPlan = \Vokuro\Models\SubscriptionPricingPlan::findFirst(
                    "user_id = {$objSuperUser->id}"
                );
            }

            $this->view->SubscriptionCode = $objSubscriptionPricingPlan->short_code;

            $this->view->salesPage = true;

            $this->view->pick('agencysignup/sales');
            
            return;
        } else {
            // Normal index page, not loading from subdomain
            $this->response->redirect('/session/login');
            $this->view->disable();

            return;
        }
    }

    public function step1($locationId)
    {
        $conditions = "location_id = :location_id:";

        $parameters = array(
            "location_id" => $locationId
        );

        $loc = Location::findFirst(
            array($conditions, "bind" => $parameters)
        );

        $this->view->location = $loc;
        $this->view->location_id = $LocationID = $locationId;
        
        $objBusiness = \Vokuro\Models\Agency::findFirst(
            "agency_id = {$loc->agency_id}"
        );

        $dbYelpReviews = \Vokuro\Models\Review::find(
            "location_id = {$LocationID} and rating_type_id = " . \Vokuro\Models\Location::TYPE_YELP
        );
        
        $dbFacebookReviews = \Vokuro\Models\Review::find(
            "location_id = {$LocationID} and rating_type_id = " . \Vokuro\Models\Location::TYPE_FACEBOOK
        );
        
        $dbGoogleReviews = \Vokuro\Models\Review::find(
            "location_id = {$LocationID} and rating_type_id = " . \Vokuro\Models\Location::TYPE_GOOGLE
        );

        $YelpSinceCreate = 0;
        $FacebookSinceCreate = 0;
        $GoogleSinceCreate = 0;
        $TotalYelpRating = 0;
        $TotalFacebookRating = 0;
        $TotalGoogleRating = 0;

        $this->view->new_reviews = ReviewsMonthly::newReviewReport(
            $this->session->get('auth-identity')['location_id']
        );

        foreach ($dbYelpReviews as $objYelpReview) {
            if (strtotime($objBusiness->date_created) < strtotime($objYelpReview->time_created)) {
                $YelpSinceCreate++;
            }

            $TotalYelpRating += $objYelpReview->rating;
        }

        // Yelp stats work differently since we calculate based on the #s yelp gives us, rather than we import since we can only import 1 yelp review at a time.  Leaving code in for when we solve this problem.
        $objYelpReviewSite = \Vokuro\Models\LocationReviewSite::findFirst(
            "location_id = {$LocationID} and review_site_id = " . \Vokuro\Models\Location::TYPE_YELP
        );
        $TotalYelpRating = $objYelpReviewSite ? $objYelpReviewSite->rating * $objYelpReviewSite->review_count : 0;


        foreach ($dbFacebookReviews as $objFacebookReview) {
            if (strtotime($objBusiness->date_created) < strtotime($objFacebookReview->time_created)) {
                $FacebookSinceCreate++;
            }

            $TotalFacebookRating += $objFacebookReview->rating;
        }

        foreach ($dbGoogleReviews as $objGoogleReview) {
            if(strtotime($objBusiness->date_created) < strtotime($objGoogleReview->time_created)) {
                $GoogleSinceCreate++;
            }

            $TotalGoogleRating += $objGoogleReview->rating;
        }

        $this->view->yelp_review_count = $YelpReviewCount = $objYelpReviewSite ? $objYelpReviewSite->review_count : 0;
        $this->view->facebook_review_count = $FacebookReviewCount = count($dbFacebookReviews);
        $this->view->google_review_count = $GoogleReviewCount = count($dbGoogleReviews);

        $TotalReviews = $FacebookReviewCount + $GoogleReviewCount + $YelpReviewCount;

        /*** 5/12/2016 ***/

        $objFbReviewSite = \Vokuro\Models\LocationReviewSite::findFirst(
            "location_id = {$LocationID} and review_site_id = " . \Vokuro\Models\Location::TYPE_FACEBOOK
        );

        $objGlReviewSite = \Vokuro\Models\LocationReviewSite::findFirst(
            "location_id = {$LocationID} and review_site_id = " . \Vokuro\Models\Location::TYPE_GOOGLE
        );

        $this->view->total_reviews = $objYelpReviewSite->review_count+$objFbReviewSite->review_count+$objGlReviewSite->review_count;

        /*** 5/12/2016 ***/

        $this->view->yelp_rating = $YelpReviewCount > 0 ? $TotalYelpRating / $YelpReviewCount : 0;
        $this->view->facebook_rating = $FacebookReviewCount > 0 ? $TotalFacebookRating / $FacebookReviewCount : 0;
        $this->view->google_rating = $GoogleReviewCount > 0 ? $TotalGoogleRating / $GoogleReviewCount : 0;
        $this->view->average_rating = $AverageRating = $TotalReviews > 0 ? ($TotalYelpRating + $TotalFacebookRating + $TotalGoogleRating ) / $TotalReviews : 0;

        // New Reviews Since Joining Get Mobile Reviews
        $this->view->total_reviews_location = $YelpSinceCreate + $FacebookSinceCreate + $GoogleSinceCreate;

        $negative_total = ReviewInvite::count(
            array(
                "column" => "review_invite_id",
                "conditions" => "location_id = " . $this->session->get('auth-identity')['location_id'] . " AND recommend = 'N' AND sms_broadcast_id IS NULL ",
            )
        );

        $this->view->negative_total = $negative_total;

        $positive_total = ReviewInvite::count(
            array(
                "column" => "review_invite_id",
                "conditions" => "location_id = " . $this->session->get('auth-identity')['location_id'] . " AND recommend = 'Y' AND sms_broadcast_id IS NULL ",
            )
        );

        $this->view->positive_total = $positive_total;

        // Calculate Revenue Retained
        // Look in settings for the "Lifetime Value of the Customer"
        $conditions = "agency_id = :agency_id:";
        $parameters = array("agency_id" => $loc->agency_id);
        
        $agency = Agency::findFirst(
            array($conditions, "bind" => $parameters)
        );

        if ($agency) {
            $this->view->revenue_retained = ($positive_total * $loc->lifetime_value_customer);
            $this->view->agency = $agency;
        }

        $this->getSMSReport();

        // Find the employee conversion report type
        $conversion_report_type = 'this_month'; //default this month

        if (isset($_GET['crt'])) {
            if ($_GET['crt'] == 2)
                $conversion_report_type = 'last_month';
            if ($_GET['crt'] == 3)
                $conversion_report_type = 'all_time';
        }

        $this->view->conversion_report_type = $conversion_report_type;

        // Default this month
        $now = new \DateTime('now');
        $start_time = $now->format('Y') . '-' . $now->format('m') . '-01';
        $end_time = date("Y-m-d 23:59:59", strtotime("last day of this month"));

        
        // Get the employee conversion reports
        // FEEDBACK

        $review_type_id = $this->locationReviewType(
            $this->session->get('auth-identity')['location_id']
        );

        $this->view->review_invite_type_id = $review_type_id;

        $employee_conversion_report_generate = Users::getEmployeeConversionReportGenerate(
            $review_type_id,
            $loc->agency_id,
            $start_time,
            $end_time,
            $this->session->get('auth-identity')['location_id'],
            'DESC'
        );

        $this->view->employee_conversion_report_generate = $employee_conversion_report_generate;

        // no query results used
        // if not null will show conversion report
        $this->view->employee_conversion_report = Users::getEmployeeConversionReport(
            $loc->agency_id,
            $start_time,
            $end_time,
            $this->session->get('auth-identity')['location_id'],
            'DESC'
        );

        // We need to find the most recent reviews
        $start_time = date("Y-m-d", strtotime("first day of previous month"));
        $end_time = date("Y-m-d 23:59:59", strtotime("last day of previous month"));

        $review_report = Review::find(
            array(
                "conditions" => "location_id = " . $this->session->get('auth-identity')['location_id'],
                "limit" => 3,
                "order" => "time_created DESC"
            )
        );

        $this->view->review_report = $review_report;

        // Last month!
        $start_time = date("Y-m-d", strtotime("first day of previous month"));
        $end_time = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
        $sms_sent_last_month = ReviewInvite::count(
            array(
                "column" => "review_invite_id",
                "conditions" => "date_sent >= '" . $start_time . "' AND date_sent <= '" . $end_time . "' AND location_id = " . $this->session->get('auth-identity')['location_id'] . " AND sms_broadcast_id IS NULL AND sms_broadcast_id IS NULL ",
            )
        );

        $this->view->sms_sent_last_month = $sms_sent_last_month;

        // This month!
        $start_time = date("Y-m-d", strtotime("first day of this month"));
        $end_time = date("Y-m-d 23:59:59", strtotime("last day of this month"));

        $sms_sent_this_month = ReviewInvite::count(
            array(
                "column" => "review_invite_id",
                "conditions" => "date_sent >= '" . $start_time . "' AND date_sent <= '" . $end_time . "' AND location_id = " . $this->session->get('auth-identity')['location_id'] . " AND sms_broadcast_id IS NULL ",
            )
        );

        $this->view->sms_sent_this_month = $sms_sent_this_month;

        $this->view->total_reviews_this_month = \Vokuro\Models\Review::count(
            "time_created BETWEEN '{$start_time}' AND '{$end_time}' AND location_id = {$LocationID}"
        );

        $this->view->review_goal = $loc->review_goal;

        $percent_needed = 10;

        $this->view->percent_needed = $percent_needed;

        $this->view->total_sms_needed = round($loc->review_goal / ($percent_needed / 100));

        $this->getShareInfo($agency);

        $conditions = "location_id = :location_id: AND review_site_id = " . \Vokuro\Models\Location::TYPE_YELP;
        $parameters = array("location_id" => $this->session->get('auth-identity')['location_id']);
        
        $Obj = LocationReviewSite::findFirst(
            array($conditions, "bind" => $parameters)
        );
        
        //start with Yelp reviews, if configured
        if (isset($Obj) && isset($Obj->external_id) && $Obj->external_id) {
            $this->view->yelp_id = $Obj->external_id;
        } else {
            $this->view->yelp_id = '';
        }

        //look for a google review configuration

        $conditions = "location_id = :location_id: AND review_site_id = " . \Vokuro\Models\Location::TYPE_GOOGLE;
        $parameters = array("location_id" => $this->session->get('auth-identity')['location_id']);
        
        $Obj = LocationReviewSite::findFirst(
            array($conditions, "bind" => $parameters)
        );
        
        //start with google reviews, if configured
        if (isset($Obj) && isset($Obj->external_id) && $Obj->external_id) {
            $this->view->google_place_id = $Obj->external_id;
        } else {
            $this->view->google_place_id = '';
        }

        //look for a facebook review configuration
        $conditions = "location_id = :location_id: AND review_site_id = " . \Vokuro\Models\Location::TYPE_FACEBOOK;
        $parameters = array("location_id" => $this->session->get('auth-identity')['location_id']);
        
        $Obj = LocationReviewSite::findFirst(
            array($conditions, "bind" => $parameters)
        );
        
        //start with Facebook reviews, if configured
        if (isset($Obj) && isset($Obj->external_id) && $Obj->external_id) {
            $this->view->facebook_page_id = $Obj->external_id;
        } else {
            $this->view->facebook_page_id = '';
        }
        //###  END: find review site config info ###
    }


    public function locationReviewType($locationId)
    {
        $conditions = "location_id = :location_id:";
        $parameters = array("location_id" => $locationId);
        $review_info = Location::findFirst(
            array($conditions, "bind" => $parameters)
        );

        if (!empty($review_info) && $review_info->review_invite_type_id) {
            $review_type_id = $review_info->review_invite_type_id;
        } else {
            $review_type_id = 1;
        }

        return $review_type_id;
    }

    public function step2($locationId)
    {
        $sql = "SELECT `sent_by_user_id` "
            . "FROM `review_invite` "
            . "WHERE `location_id` = " . $locationId . " "
            . "GROUP BY  `sent_by_user_id`";

        $list = new ReviewInvite();
        $params = null;
        $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
        $userset = $rs->toArray();
        $YNrating_array_set = array();

        foreach ($userset as $key => $value) {
            // GARY_TODO:  Need to ask jon why this case ever happens.
            if (!$value['sent_by_user_id']) {
                continue;
            }

            $sentByUserId = $value['sent_by_user_id'];


            if ($sentByUserId) {
                $YNrating_array_set[$sentByUserId]['feedback_average'] = $this->customerSatisfactionAverage(
                    $sentByUserId,
                    $locationId
                );

                $YNrating_array_set[$sentByUserId]['feedback_amount'] = $this->customerSatisfactionAmount(
                    $sentByUserId,
                    $locationId
                );

                $YNrating_array_set[$sentByUserId]['feedback_total'] = $this->customerSatisfactionTotal(
                    $sentByUserId,
                    $locationId
                );
                
                $this->view->YNrating_array_set = $YNrating_array_set;
            }
        }
    }

    public function customerSatisfactionAmount($employeeId, $locationId)
    {
        $data = $this->customerSatisfactionData(
            $employeeId,
            $locationId
        );

        return $data['amount'];
    }

    public function customerSatisfactionAverage($employeeId, $locationId)
    {
        $data = $this->customerSatisfactionData(
            $employeeId,
            $locationId
        );

        return $data['average'];
    }

    public function customerSatisfactionTotal($employeeId, $locationId)
    {
        $data = $this->customerSatisfactionData(
            $employeeId,
            $locationId
        );

        return $data['total'];
    }

    private function customerSatisfactionData($employeeId, $locationId)
    {
        $ratingTypeId = $this->locationReviewType($locationId);

        // get all employee customer feedback, narrow by location
        $sql = "SELECT `review_invite_type_id`, `rating` "
            . "FROM `review_invite` "
            . "WHERE  `sent_by_user_id` = " . $employeeId . " "
            . "AND `location_id` = " . $locationId;

        // Base model
        $list = new ReviewInvite();

        // Execute the query
        $params = null;
        $rs = new Resultset(null, $list, $list->getReadConnection()->query($sql, $params));
        $customerFeedback = $rs->toArray();
        
        // convert to current rating format
        // 1 = y/n
        // 2 = star
        // 3 = nps

        foreach ($customerFeedback as $key => $feedbackEntry) {
            if ($ratingTypeId == null) continue;

            $type = $feedbackEntry['review_invite_type_id'];
            $rating = $feedbackEntry['rating'];

            if ($ratingTypeId == 1) {
                if ($type == 2) {
                    $customerFeedback[$key]['rating'] = $this->convertStarToYesNo($rating);
                } else if ($type == 3) {
                    $customerFeedback[$key]['rating'] = $this->convertNPSToYesNo($rating);
                }
            } else if ($ratingTypeId == 2) {
                if ($type == 1) {
                    $customerFeedback[$key]['rating'] = $this->convertYesNoToStar($rating);
                } else if ($type == 3) {
                    $customerFeedback[$key]['rating'] = $this->convertNPStoStar($rating);
                }
            } else if ($ratingTypeId == 3) {
                if ($type == 1) {
                    $customerFeedback[$key]['rating'] = $this->convertYesNoToNPS($rating);
                } else if ($type == 2) {
                    $customerFeedback[$key]['rating'] = $this->convertStarToNPS($rating);
                }
            }
        }
        
        // calculate average
        $amount = count($customerFeedback);
        $total = 0;

        if ($ratingTypeId == 1) {
            $yes = 0;
            $no = 0;

            foreach ($customerFeedback as $feedbackEntry) {
                $rating = $feedbackEntry['rating'];

                if ($rating == 5 || $rating == 'Yes') {
                    $yes++;
                }

                if ($rating == 1 || $rating == 'No') {
                    $no++;
                }
            }
            
            $average = $amount > 0 ? $yes / $amount : 0;
        } else if ($ratingTypeId == 2) {

            foreach ($customerFeedback as $feedbackEntry) {
                $rating = $feedbackEntry['rating'];

                $total += $rating;
            }

            $average = $total / $amount;
        } else if ($ratingTypeId == 3) {
            foreach ($customerFeedback as $feedbackEntry) {
                $rating = $feedbackEntry['rating'];

                $total += $rating;
            }

            $average = round($total / $amount);
        }

        $data = array(
            'average' => $average,
            'total' => $total,
            'amount' => $amount,
            'ratingTypeId' => $ratingTypeId
        );

        return $data;
    }

    public function convertYesNoToNPS($rating)
    {
        if ($rating == 5 || $rating == 'Yes') {
            return 10;
        }

        if ($rating == 1 || $rating == 'No') {
            return 2;
        }
    }

    public function convertStarToNPS($rating)
    {
        return $rating * 2;
    }

    public function convertNPSToYesNo($rating, $threshold = 8)
    {
        if ($rating >= $threshold) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public function convertStarToYesNo($rating, $threshold = 4)
    {
        if ($rating >= $threshold) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public function convertNPStoStar($rating)
    {
        return $rating / 2;
    }

    public function convertYesNoToStar($rating)
    {
        if ($rating == 5 || $rating == 'Yes') {
            return 5;
        }

        if ($rating == 1 || $rating == 'No') {
            return 1;
        }
    }
}