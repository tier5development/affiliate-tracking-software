<?php
    namespace Vokuro\Controllers;

    use Vokuro\Models\Agency;
    use Vokuro\Models\Location;
    use Vokuro\Models\LocationNotifications;
    use Vokuro\Models\LocationReviewSite;
    use Vokuro\Models\ReviewInvite;
    use Vokuro\Models\ReviewInviteReviewSite;
    use Vokuro\Models\ReviewSite;
    use Vokuro\Models\Users;
    use Vokuro\Services\Reviews;

    use Services_Twilio;
    use Services_Twilio_RestException;

    ini_set("display_errors", "on");

    class ReviewController extends ControllerBase {

        /**
         * Default action. Set the public layout (layouts/public.volt)
         */
        public function initialize() {
            $this->view->setTemplateBefore('public');
            parent::initialize();
        }

        public function expiredAction() {

        }


        public function indexAction() {

           $conditions = "api_key = :api_key:";

            $parameters = array("api_key" => htmlspecialchars($_GET["a"]));
            $review_invite = new ReviewInvite();
            $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));

            if($invite->rating)
                $this->response->redirect('/review/expired');

            if ($invite->location_id > 0) {

          
                //echo $invite->location_id;//exit;
                $locationobj = new Location();
                $location = $locationobj::findFirst($invite->location_id);


                $this->view->setVar('location', $location);

                $agencyobj = new Agency();
                $agency = $agencyobj::findFirst($location->agency_id);
               // echo $agency->parent_id;//exit;
                $parent_agency=$agencyobj::findFirst($agency->parent_id);
                //echo $parent_agency->name;exit;
                $this->view->parent_agency=$parent_agency;
                $this->view->sms_button_color = $location->sms_button_color;
                $this->view->logo_path = $location->sms_message_logo_path;
                $this->view->name = $location->name;

                $this->view->setVar('agency', $agency);

                switch($invite->review_invite_type_id) {
                    case ReviewInvite::RATING_TYPE_YES_NO:
                        $threshold = false;
                        break;
                    case ReviewInvite::RATING_TYPE_5_STAR:
                        $threshold = $location->rating_threshold_star;
                        break;
                    case ReviewInvite::RATING_TYPE_NPS:
                        $threshold = $location->rating_threshold_nps;
                        break;
                }

                $this->view->setVar('threshold', $threshold);

                //find what type of question we should ask
                $question_type = 1;
                if ($location && $location->review_invite_type_id > 0) {
                    $question_type = $location->review_invite_type_id;
                }

                // basic crawler detection and block script (no legit browser should match this)
                if (!empty($_SERVER['HTTP_USER_AGENT']) and preg_match('~(bot|google)~i', $_SERVER['HTTP_USER_AGENT'])) {
                    // this is a crawler and you should not show ads here
                } else {
                    $ref = '';
                    if (isset($_SERVER['HTTP_REFERER'])) $ref = $_SERVER['HTTP_REFERER'];
                    if (strpos($ref, 'google') !== FALSE) {
                        //redirect to wherever google people should go
                    } else {
                        if ($this->getIpMatchForGoogleBot($_SERVER['REMOTE_ADDR']) == false) {
                            //save when the user viewed this invite
                            $invite->date_viewed = date('Y-m-d H:i:s');
                            $invite->review_invite_type_id = $question_type;
                            $invite->save();
                        }
                    }
                }
                $this->view->setVar('invite', $invite);
            }
        }


        public function validateGoogleBotIP($ip) {
            $hostname = gethostbyaddr($ip); //"crawl-66-249-66-1.googlebot.com"
            return preg_match('/\.google\.com$/i', $hostname);
        }

        public function getIpMatchForGoogleBot($ip) {
            return $this->validateGoogleBotIP($ip);
        }

        public function recommendAction() {


        try {
                $rating = false;
                if (isset($_GET["r"])) $rating = htmlspecialchars($_GET["r"]);

                $conditions = "api_key = :api_key:";
                $parameters = array("api_key" => htmlspecialchars($_GET["a"]));
                $review_invite = new ReviewInvite();
                $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));
                if($invite->rating)
                    $this->response->redirect('/review/expired');
                   
                if ($invite->location_id > 0) {
                    
                     /**** send mail to business and user ***/
                     $user_sent=$invite->sent_by_user_id;
                     $userobj = new Users();
                     $user_info = $userobj::findFirst($user_sent);
                     $emp= $user_info->is_employee;
                     //echo "<br>";
                     $role= $user_info->role;


                         $locationobj = new Location();
                         $location = $locationobj::findFirst($invite->location_id);
                         $this->view->setVar('location', $location);
     
                        // echo $invite->location_id;exit;
                         $agencyobj = new Agency();
                         $agency = $agencyobj::findFirst($location->agency_id);
                         $parent_agency=$agencyobj::findFirst($agency->parent_id);
                          $this->view->parent_agency = $parent_agency;


            $TwilioToken = $parent_agency->twilio_auth_token;
            // We use the businesses' from number if it exists, otherwise use the agency's.
            $TwilioFrom = $parent_agency->twilio_from_phone;
            $TwilioAPI = $parent_agency->twilio_api_key;
                        //echo $parent_agency->name;exit;
                     if($emp==1 && $role=="Super Admin")
                     {  
                       
     
                         $conditions = "location_id = :location_id:";
                         $parameters = array("location_id" => $invite->location_id);
                         $agencynotifications = LocationNotifications::find(array($conditions, "bind" => $parameters));
                         $is_email_alert_on=0;
     
                         foreach($agencynotifications as $agencynotification) {
                             if ($agencynotification->user_id == $user_info->id) {
                                 $is_email_alert_on = ($agencynotification->email_alert==1?1:0);
                             } }
     
                         if($is_email_alert_on==1)
                         {
     
                           $EmailFrom = 'zacha@reviewvelocity.co';
                           $EmailFromName = "Zach Anderson";
                           $to=$user_info->email;
                           $subject="New Feedback Came Aginst Review";
                           $mail_body="Dear ".$user_info->name;
                           $mail_body=$mail_body."<p>You have received a new feed back against the feedback link you have sent to user.</p>";
                          $Mail = $this->getDI()->getMail();
                         $Mail->setFrom($EmailFrom, $EmailFromName);
                         $Mail->send($to, $subject, '', '', $mail_body);
     
     
                         if ($this->SendSMS($user_info->phone, $message, $TwilioAPI, $TwilioToken,  $TwilioFrom)) {
                          }
                        }
                      
                        //find the location review sites
                        $conditions = "location_id = :location_id: AND is_on = 1";
                        $parameters = array("location_id" => $invite->location_id);
                         
                        $review_site_list = LocationReviewSite::find(array($conditions, "bind" => $parameters, "order" => "sort_order ASC"));
                        
                        $this->view->review_site_list = $review_site_list;
                     }
                     else
                     {
                         $conditions = "location_id = :location_id:";
                         $parameters = array("location_id" => $invite->location_id);
                         $agencynotifications = LocationNotifications::find(array($conditions, "bind" => $parameters));
     
                           $is_email_alert_on=0;
     
                         foreach($agencynotifications as $agencynotification) {
                             if ($agencynotification->user_id == $user_info->id) {
                                 $is_email_alert_on = ($agencynotification->email_alert==1?1:0);
                             } }
     
                             
                         /*$TwilioToken = $this->config->twilio->twilio_auth_token;
                       
                         $TwilioFrom = $this->config->twilio->twilio_from_phone;
                       
                        $TwilioAPI = $this->config->twilio->twilio_api_key;*/
                        
                        if($is_email_alert_on==1)
                         {
                             /*** mail to user ***/
                               $EmailFrom = 'zacha@reviewvelocity.co';
                               $EmailFromName = "Zach Anderson";
                               $to=$user_info->email;
                               $subject="New Feedback Came Aginst Review";
                               $mail_body="Dear ".$user_info->name;
                               $mail_body=$mail_body."<p>You have received a new feed back against the feedback link you have sent to user.</p>";
                             $Mail = $this->getDI()->getMail();
                             $Mail->setFrom($EmailFrom, $EmailFromName);
                             $Mail->send($to, $subject, '', '', $mail_body);
         
                             /*** mail to user end ****/
         
                             /**** mail to busines ****/
         
                             $business_info =  \Vokuro\Models\Users::findFirst('agency_id = ' . $user_info->agency_id . ' AND role="Super Admin"');
         
                             $business_agency= \Vokuro\Models\Agency::findFirst('agency_id = ' . $user_info->agency_id);
         
                               $EmailFrom = 'zacha@reviewvelocity.co';
                               $EmailFromName = "Zach Anderson";
                               $to=$business_info->email;
                               $subject="New Feedback Came Aginst Review";
                               $mail_body="Dear ".$business_info->name;
                               $mail_body=$mail_body."<p>You have received a new feed back against the feedback link Employee under your business (".$user_info->name.") have sent to user.</p>";
                             $Mail = $this->getDI()->getMail();
                             $Mail->setFrom($EmailFrom, $EmailFromName);
                             $Mail->send($to, $subject, '', '', $mail_body);
         
                             /**** mail to busines ****/
         
                             /*** sms to user ***/
                              if ($this->SendSMS($user_info->phone, $message, $TwilioAPI, $TwilioToken,  $TwilioFrom)) {
                              }
                             /*** sms to user ***/
         
                             /*** sms to business ***/
         
                              if ($this->SendSMS($business_agency->phone, $message, $TwilioAPI, $TwilioToken,  $TwilioFrom)) {
                              }
                             /*** sms to business ***/
     
     
                         }
                         //exit;
                         /**** send mail to business and user ***/
     
                         $this->view->agency = $agency;
                         $this->view->sms_button_color = $location->sms_button_color;
                         $this->view->logo_path = $location->sms_message_logo_path;
                         $this->view->name = $location->name;
     
                         switch($invite->review_invite_type_id) {
                             case ReviewInvite::RATING_TYPE_YES_NO:
                                 $threshold = false;
                                 break;
                             case ReviewInvite::RATING_TYPE_5_STAR:
                                 $threshold = $location->rating_threshold_star;
                                 break;
                             case ReviewInvite::RATING_TYPE_NPS:
                                 $threshold = $location->rating_threshold_nps;
                                 break;
                         }
     
     
                         //find the location review sites
                         $conditions = "location_id = :location_id: AND is_on = 1";
                         $parameters = array("location_id" => $invite->location_id);
                         
                         $review_site_list = LocationReviewSite::find(array($conditions, "bind" => $parameters, "order" => "sort_order ASC"));
                        
                         $this->view->review_site_list = $review_site_list;
                     }
     
                     if ($rating && $rating < $threshold || ($invite->review_invite_type_id == ReviewInvite::RATING_TYPE_YES_NO && $rating == 1)) {
                         //redirect to the no thanks page
                         $this->response->redirect('/review/nothanks?r=' . $rating . '&a=' . htmlspecialchars($_GET["a"]));
                         $this->view->disable();
                         return;
                     }
     
                     // Query review_invite binding parameters with string placeholders
                     $conditions = "api_key = :api_key:";
     
                     // Parameters whose keys are the same as placeholders
                     $parameters = array("api_key" => htmlspecialchars($_GET["a"]));
     
                     // Perform the query
                     $review_invite = new ReviewInvite();
                     $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));
     
                     //save the rating
                     
                     $invite->rating = $rating;
                     $invite->recommend = 'Y';
                     $invite->save();
                     //we have the invite, now find the location
                     $locationobj = new Location();
                     $location = $locationobj::findFirst($invite->location_id);
     
                     $this->view->setVar('invite', $invite);
                     $this->view->setVar('location', $location);
                }

            } catch (\Exception $e) {
                echo "Exception: ", $e->getMessage(), "\n";
                echo " File=", $e->getFile(), "\n";
                echo " Line=", $e->getLine(), "\n";
                echo $e->getTraceAsString();
            }
        }


        public function nothanksAction() {

            $rating = ($_GET["r"]) ? htmlspecialchars($_GET["r"]) : '';
            // Query review_invite binding parameters with string placeholders
            $conditions = "api_key = :api_key:";

            // Parameters whose keys are the same as placeholders
            $parameters = array("api_key" => htmlspecialchars($_GET["a"]));
            // Perform the query
            $review_invite = new ReviewInvite();
            $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));

            if((!$invite || $invite->rating) && !$this->request->isPost())
               // $this->response->redirect('/review/expired');

            $invite->rating = $rating;
            $invite->recommend = 'N';
            $invite->save();
            $this->view->setVar('invite', $invite);
            //echo $invite->location_id;exit;
            //we have the invite, now find the location
            $locationobj = new Location();
            $location = $locationobj::findFirst($invite->location_id);
            //echo $location->agency_id;exit;
            //we have the location, now find the agency
            $agencyobj = new Agency();
            $agency = $agencyobj::findFirst($location->agency_id);
            $parent_agency=$agencyobj::findFirst($agency->parent_id);
            /*echo $parent_agency->name;
            echo "<br>";
            echo $parent_agency->website;exit;*/
            $this->view->sms_button_color = $location->sms_button_color;
            $this->view->logo_path = $location->sms_message_logo_path;
            $this->view->name = $location->name;
            $this->view->objname = $location->name;
            $this->view->objAgency=$agency;
            $this->view->parent_agency=$parent_agency;
            // Negative feedback comments are being posted
            if ($this->request->isPost()) {
                if ($invite->comments)
                    $this->response->redirect('/review/expired');

                $invite->comments = htmlspecialchars($_POST["comments"]);
                $invite->save();

                // Query review_invite binding parameters with string placeholders
                $conditions = "api_key = :api_key:";
                // Parameters whose keys are the same as placeholders
                $parameters = array("api_key" => htmlspecialchars($_GET["a"]));
                // Perform the query
                $review_invite = new ReviewInvite();
                $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));

                //send the notification about the feedback
                $message = 'Notification: Review invite feedback has been posted for ' . $location->name . ': http://' . $_SERVER['HTTP_HOST'] . '/reviews/';
                parent::sendFeedback($agency, $message, $location->location_id, 'Notification: Review invite feedback', $invite->sent_by_user_id);
            }
        }


        public function trackAction() {
            $review_invite_id = $_POST['i'];
           // exit;
            $review_site_id = $_POST['d'];
            $model=new ReviewInviteReviewSite();
            $this->view->disable();
            $count=$model->countexists($review_invite_id, $review_site_id);
                    if(count($count)==0)
                    {
                       $rirs = new ReviewInviteReviewSite();
            $rirs->review_invite_id = $review_invite_id;
            $rirs->review_site_id = $review_site_id;
            $rirs->save();  
            echo 'true';
                    }
                    else
                    {
                        echo 'true';
                    }
                
            //$this->checkIntegerOrThrowException($review_invite_id);
           // $this->checkIntegerOrThrowException($review_site_id);
           
          /*  $rirs = new ReviewInviteReviewSite();
            $rirs->review_invite_id = $review_invite_id;
            $rirs->review_site_id = $review_site_id;
            $rirs->save(); 
            
            $this->view->disable();
            echo 'true';*/
        }

        public function linkAction() {
            // Query review_invite binding parameters with string placeholders
            $conditions = "api_key = :api_key:";

            // Parameters whose keys are the same as placeholders
            $parameters = array("api_key" => htmlspecialchars($_GET["a"]));

            // Perform the query
            $review_invite = new ReviewInvite();
            $invite = $review_invite::findFirst(array($conditions, "bind" => $parameters));
            if ($this->validateGoogleBotIP($_SERVER['REMOTE_ADDR'])) {
            } else {
                //save when the user viewed this invite
                $invite->date_viewed = date('Y-m-d H:i:s');
                $invite->save();
            }

            $this->response->redirect($invite->link);
            $this->view->disable();
        }

    }

