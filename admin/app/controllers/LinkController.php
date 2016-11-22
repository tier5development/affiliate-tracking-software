<?php
    namespace Vokuro\Controllers;

    use Phalcon\Tag;
    use Phalcon\Mvc\Model\Criteria;
    use Phalcon\Paginator\Adapter\Model as Paginator;
    use Vokuro\Forms\ChangePasswordForm;
    use Vokuro\Models\Location;
    use Vokuro\Forms\UsersForm;
    use Vokuro\Models\Users;
    use Vokuro\Models\UsersLocation;
    use Vokuro\Models\PasswordChanges;
    use Vokuro\Services\Email;
    use Vokuro\Forms\LocationForm;
    use Vokuro\Models\Agency;
    use Vokuro\Models\FacebookScanning;
    use Vokuro\Models\GoogleScanning;
    use Vokuro\Models\LocationReviewSite;
    use Vokuro\Models\Region;
    use Vokuro\Models\ReviewInvite;
    use Vokuro\Models\Review;
    use Vokuro\Models\ReviewsMonthly;
    use Vokuro\Models\UsersSubscription;
    use Vokuro\Models\YelpScanning;
    use Vokuro\Services\Reviews;

    use Services_Twilio;
    use Services_Twilio_RestException;


    require_once __DIR__ . '/../../vendor/autoload.php';
    use net\authorize\api\contract\v1 as AnetAPI;
    use net\authorize\api\controller as AnetController;

    /**
     * Vokuro\Controllers\UsersController
     * CRUD to manage users
     */
    class LinkController extends ControllerBase
    {
        

         public $facebook_access_token;

         public function initialize() {

            $this->tag->setTitle('Review Velocity | Link');
            
            $path_to_admin = realpath(__DIR__ . '/../../');
            include_once $path_to_admin . '/app/library/Google/mybusiness/Mybusiness.php';
            define('APPLICATION_NAME', 'User Query - Google My Business API');
            define('CLIENT_SECRET_PATH', $path_to_admin . '/app/models/client_secrets.json');


            parent::initialize();
        }



        public function createlinkAction($uid)
        {
         /* if ($this->session->has('auth-identity')) 
          {
              $this->view->setTemplateBefore('private');   
          }*/

            $getcode=base64_decode($uid);
            $getarray=explode('-',$getcode);
            $id=$getarray[0];
            
            $conditions_user = "id = :id:";
            $parameters_user = array("id" => $id);
            $userinfo = Users::findFirst(array($conditions_user, "bind" => $parameters_user));


            if(empty($userinfo))
            {
                echo 'sorry this page does not exists';
                exit;
            }
            $conditions = "user_id = :user_id:";
            $parameters = array("user_id" => $id);
            $userObj = UsersLocation::find(array($conditions, "bind" => $parameters));
           /*if($userObj->location_id!='')
           {
            $conditions1 = "location_id = :location_id:";
            $parameters1 = array("location_id" => $userObj->location_id);
            $userObj1 = Location::findFirst(array($conditions1, "bind" => $parameters1));

            echo $userObj1->name;exit;
           }
            */
           $make_location_array=array();

           if(!empty($userObj))
           {
                foreach($userObj as $obj)
                {
                $conditions1 = "location_id = :location_id:";
                $parameters1 = array("location_id" => $obj->location_id);
                $userObj1 = Location::findFirst(array($conditions1, "bind" => $parameters1)); 
                    $make_location_array[$obj->location_id]=$userObj1->name;
                }

                //print_r($make_location_array);exit;
           }
                $agency_id=$userinfo->agency_id;
               $this->view->userlocations = $make_location_array;
               $this->view->agency = $agency_id;
               $this->view->userID = $uid;
               $this->view->render('users', 'sendreviewlink');
               $this->view->disable();
               return;  
        }


    

             public function send_review_invite_employeeAction($uid=null) {

                /*$identity = $this->auth->getIdentity();*/
                // If there is no identity available the user is redirected to index/index
                
                /*** get post value ***/

                if ($this->request->isPost()) {

                    

            $conditions_user = "agency_id = :agency_id:";
            $parameters_user = array("agency_id" => $_POST['agency_id']);
            
             $agencyLocationInfo = Location::findFirst(array($conditions_user, "bind" => $parameters_user));
            //echo '<pre>';print_r($agencyInfo);exit;

            $agency_location_id=$agencyLocationInfo->location_id;//exit;
            $agency_location_name=$agencyLocationInfo->name;

            $AgencyID=$_POST['agency_id'];
            $objAgency = Agency::findFirst("agency_id = {$AgencyID}");
        // Are we a business?
        if($objAgency->parent_id > 0) {
            // Return parent's keys.
            $objParentAgency = \Vokuro\Models\Agency::findFirst("agency_id = " . $objAgency->parent_id);
            $TwilioSID = $objParentAgency->twilio_auth_messaging_sid;
            $TwilioToken = $objParentAgency->twilio_auth_token;
            // We use the businesses' from number if it exists, otherwise use the agency's.
            $TwilioFrom = $objAgency->twilio_from_phone ?: $objParentAgency->twilio_from_phone;
            $TwilioAPI = $objParentAgency->twilio_api_key;
        } elseif($objAgency->parent_id == \Vokuro\Models\Agency::BUSINESS_UNDER_RV || $IsAdmin) {
            // Business under RV.  Return default from config.
            $TwilioSID = $this->config->twilio->twilio_auth_messaging_sid;
            $TwilioToken = $this->config->twilio->twilio_auth_token;
            $TwilioFrom = $this->config->twilio->twilio_from_phone;
            $TwilioAPI = $this->config->twilio->twilio_api_key;
        }
            

            $twilio_api_key=$TwilioAPI;
            $twilio_auth_token=$TwilioToken;
            $twilio_auth_messaging_sid=$TwilioSID;
            $twilio_from_phone=$TwilioFrom;
                   
                    //echo 'SMS starts';exit;
                //the user wants to send an SMS, so first save it in the database
                if (!$_POST['phone'] || $_POST['phone'] == '') {
                    //$this->flash->error('Please enter a Phone number.');
                    //throw new Exception('Please enter a Phone number.', 123);
                    $this->view->disable();
                    echo 'Please enter a Phone number.';
                    return;
                } else {
                        if($_POST['location_id']=='')
                        {
                        $location_name=$agency_location_name;
                        $location_id=$agency_location_id;
                        }
                        else
                        {
                             $location_name=$_POST['location_name'];
                             $location_id=$_POST['location_id'];
                        }
                    
                    //else we have a phone number, so send the message
                    $name = $_POST['name'];
                    $message = $_POST['SMS_message'].'  Reply stop to be removed';
                    //replace out the variables
                    $message = str_replace("{location-name}", $location_name, $message);
                    $message = str_replace("{name}", $name, $message);
                    $guid = $this->GUID();//exit;
                   $message = str_replace("{link}", $this->googleShortenURL('http://' . $_SERVER['HTTP_HOST'] . '/review/?a=' . $guid), $message);

                  // exit;

                    $phone = $_POST['phone'];
                   $uid=$_POST['userID'];//exit;
                    //save the message to the database before sending the message
                    $invite = new ReviewInvite();
                    $invite->assign(array(
                        'name' => $name,
                        'location_id' => $location_id,
                        'phone' => $phone,
                        //TODO: Added google URL shortener here
                        'api_key' => $guid,
                        'sms_message' => $message.'  Reply stop to be removed',
                        'date_sent' => date('Y-m-d H:i:s'),
                        'date_last_sent' => date('Y-m-d H:i:s'),
                        'sent_by_user_id' => $identity['id']
                    ));

                    if (!$invite->save()) {
                        $this->view->disable();
                        echo $invite->getMessages();
                        return;
                    } else {

                        /*echo $twilio_api_key;
                        echo '<br>';
                        echo $twilio_auth_token;
                        echo '<br>';
                        echo $twilio_auth_messaging_sid;
                        echo '<br>';
                        echo $twilio_from_phone;
                        echo '<br>';
                        echo 'Agency Id: '.$AgencyID;
                         echo '<br>';exit;*/
                        //The message is saved, so send the SMS message now

                        /*$twilio_api_key='AC68cd1cc8fe2ad03d2aa4d388b270577d' ;
                        $twilio_auth_token='42334ec4880d850d6c9683a4cd9d94b8'; 
                        $twilio_auth_messaging_sid='MGa8510e68cd75433880ba6ea48c0bd81e';
                        $twilio_from_phone='+18582120211';*/
                        //$phone='(559) 425-4015';

                        $twilio_api_key='AC00b855893dab69e458170cc524233f47' ;
                        $twilio_auth_token='8a314c3ff7e285dfb4c02c93c257025e'; 
                        $twilio_auth_messaging_sid='MG28424d425f7128e97d4c96f2fdc44f2d';
                        $twilio_from_phone='4253654160';
                        
                        //echo $this->twilio_api_key;exit;
                        if ($this->SendSMS($phone, $message, $twilio_api_key, $twilio_auth_token, $twilio_auth_messaging_sid, $twilio_from_phone)) {
                            //echo $uid;exit;

                            //$this->flash->success("The SMS was sent successfully to: " . $phone);
                            //$this->view->render('users', 'reviewmsg');

                            $this->flashSession->success("The SMS was sent successfully to: " . $phone.".This page will automatically refresh in 5 seconds.");
                            $this->view->disable();
                            return $this->response->redirect('link/send_review_invite_employee/'.$uid);
                            
                           
                        }
                    }
                }
            }

                /*** get post value ***/
                else
                {
                    //echo $uid;exit;
                    //echo 'fgggggggggggggggggg';exit;
                    if($uid)
                    {
                        $this->view->linkId = $uid;
                    }

                    $this->view->render('users', 'reviewmsg');
                    
                }

             }


            

    }
