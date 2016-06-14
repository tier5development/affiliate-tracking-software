<?php

namespace Vokuro\Services;

use Vokuro\Models\Location;
use Vokuro\Models\ReviewInvite;
use Vokuro\Models\ReviewsMonthly;

class SmsManager extends BaseService {  

    static $reviewPercentage = 10;
    
    function __construct($config) {
        parent::__construct($config);
    }
    
    public function getBusinessSmsQuotaParams($locationId) {

        $smsQuotaParams['hasUpgrade'] = false;

        /* Sms sent last month */
        $lastMonthStartTime = date("Y-m-d", strtotime("first day of previous month"));
        $lastMonthEndTime = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
       
        $smsQuotaParams['smsSentLastMonth'] = ReviewInvite::query() 
            ->columns("review_invite_id")
            ->where('date_sent >= :startTime:') 
            ->andWhere('date_sent >= :endTime:')
            ->andWhere('location_id >= :locationId:')
            ->andWhere('sms_broadcast_id IS NULL')
            ->bind([
                "startTime" => $lastMonthStartTime, 
                "endTime" => $lastMonthEndTime,
                "locationId" => $locationId
                ])
            ->execute()
            ->count();
        
        /* Sms sent this month */
        $thisMonthStartTime = date("Y-m-d", strtotime("first day of this month"));
        $thisMonthEndTime = date("Y-m-d 23:59:59", strtotime("last day of this month"));

        $smsQuotaParams['smsSentThisMonth'] = ReviewInvite::query() 
            ->columns("review_invite_id")
            ->where('date_sent >= :startTime:') 
            ->andWhere('date_sent >= :endTime:')
            ->andWhere('location_id >= :locationId:')
            ->andWhere('sms_broadcast_id IS NULL')
            ->bind([
                "startTime" => $thisMonthStartTime, 
                "endTime" => $thisMonthEndTime,
                "locationId" => $locationId
                ])
            ->execute()
            ->count();
        
        /* Reviews sent last month */  
        $smsQuotaParams['numReviewsLastMonth'] = ReviewsMonthly::sum(
            [
                "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                "conditions" => "month = " . date("m", strtotime("first day of previous month")) . " AND year = '" . date("Y", strtotime("first day of previous month")) . "' AND location_id = " . $locationId,
            ]
        );
        
        /* Reviews sent 2 months ago */
        $smsQuotaParams['numReviewsTwoMonthsAgo'] = ReviewsMonthly::sum(
            [
                "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                "conditions" => "month = " . date("m", strtotime("-2 months", time())) . " AND year = '" . date("Y", strtotime("-2 months", time())) . "' AND location_id = " . $locationId,
            ]
        );
        
        /* Reviews total last month */
        $smsQuotaParams['totalReviewsLastMonth'] = $smsQuotaParams['numReviewsLastMonth'] - $smsQuotaParams['numReviewsTwoMonthsAgo'];
    
        /* Reviews this month */
        $smsQuotaParams['numReviewsThisMonth'] = ReviewsMonthly::sum(
            [        
                "column" => "COALESCE(facebook_review_count, 0) + COALESCE(google_review_count, 0) + COALESCE(yelp_review_count, 0)",
                "conditions" => "month = " . date("m", strtotime("first day of this month")) . " AND year = '" . date("Y", strtotime("first day of this month")) . "' AND location_id = " . $locationId,
            ]
        );
        
        /* Reviews total this month */
        $smsQuotaParams['totalReviewsThisMonth'] = $smsQuotaParams['numReviewsThisMonth'] - $smsQuotaParams['totalReviewsLastMonth'];
        
        /* Set the agency SMS limit */
        $location = Location::findFirst(
            [ 
                "location_id = :location_id:", 
                "bind" => [ "location_id" => $locationId ]        
            ]
        );
        if ($location) {
        
            $smsQuotaParams['reviewGoal'] = $location->review_goal;
            $smsQuotaParams['percentNeeded'] = SmsManager::$reviewPercentage;
            $smsQuotaParams['totalSmsNeeded'] = round(
                    $smsQuotaParams['reviewGoal'] / ($smsQuotaParams['percentNeeded'] * 0.1)
            );
            
        } else {
            
            $smsQuotaParams['reviewGoal'] = 0;
            $smsQuotaParams['percentNeeded'] = 0;
            $smsQuotaParams['totalSmsNeeded'] = 0;
            
        }
        
        if (!$smsQuotaParams['hasUpgrade']) {
            $smsQuotaParams['percent'] = 
                ($smsQuotaParams['totalSmsNeeded'] > 0 ? 
                floatval(number_format((float)($smsQuotaParams['smsSentThisMonth'] / $smsQuotaParams['totalSmsNeeded']) * 100, 0, '.', '')) : 
                100);
            $smsQuotaParams['percent']  > 100 ? 100 : $smsQuotaParams['percent'] ;
        } else { 
            $smsQuotaParams['percent'] = 100;
        }
         
        return $smsQuotaParams;
    }
    
    public function getAgencySmsQuotaParams() {

        // $smsQuotaParams['showUpgradeMessage'] = false;
        // if (!(isset($this->session->get('auth-identity')['agencytype']) && $this->session->get('auth-identity')['agencytype'] == 'agency')) {
        //     //we have a business, so check if free
        //     //echo '<p>$agency->subscription_id:'.$agency->subscription_id.'</p>';
        //     //echo '<p>$agency->agency_id:'.$agency->agency_id.'</p>';
        //     if (isset($agency->subscription_id) && $agency->subscription_id > 0) {
        //         //we have a subscription, check if free
        //         $conditions = "subscription_id = :subscription_id:";
        //         $parameters = array("subscription_id" => $agency->subscription_id);
        //         $subscriptionobj = Subscription::findFirst(array($conditions, "bind" => $parameters));
        //         if ($subscriptionobj->amount > 0) {
        //             $this->view->is_upgrade = false;
        //         } else {
        //             $this->view->is_upgrade = true;
        //         }
        //     } else {
        //         $this->view->is_upgrade = true;
        //     }
        // }
        
    }
    
}
