<?php
class WaSosController extends My_Controller_Abstract{
    
    public function preDispatch(){
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
    
    public function createProfileAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $deviceType                     = $decoded['device_type'];
        $sos_id                         = $decoded['sos_id'];
        $blood_group                    = $decoded['blood_group'];
        $illnesses                      = $decoded['illnesses'];
        $is_medication                  = $decoded['is_medication'];
        $medication_description         = $decoded['medication_description'];
        $allergies                      = $decoded['allergies'];
        $is_organ_donations             = $decoded['is_organ_donations'];
        $organ_donations_description    = $decoded['organ_donations_description'];
        $mobile_number                  = $decoded['mobile_number'];
        $facebook_username              = $decoded['facebook_username'];
        $facebook_tokens                = $decoded['facebook_tokens'];
        $twitter_username               = $decoded['twitter_username'];
        $twitter_oauth_tokens           = $decoded['twitter_oauth_tokens'];
        $twitter_oauth_secret_tokens    = $decoded['twitter_oauth_secret_tokens'];
        $youtube_channel                = $decoded['youtube_channel'];
        $receiver_userset               = $decoded['receiver_userset'];
        $receiver_emailset              = $decoded['receiver_emailset'];
        $receiver_phoneset              = $decoded['receiver_phoneset'];
        $emergency_message              = $decoded['emergency_message'];
        $is_gps                         = $decoded['is_gps'];
        $is_alarm                       = $decoded['is_alarm'];
        $is_media_broadcast             = $decoded['is_media_broadcast'];
        $is_broadcast_video             = $decoded['is_broadcast_video'];
        $is_broadcast_facebook          = $decoded['is_broadcast_facebook'];
        $is_broadcast_twitter           = $decoded['is_broadcast_twitter'];
        $is_broadcast_youtube           = $decoded['is_broadcast_youtube'];
        $pincode                        = $decoded['pincode'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);  
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                  
                $db = $this->db;
                $db->beginTransaction();

                try 
                {
                    $count  = 0;
                    $sosRow = false;
                    $sosReceiverRow = false;
                    $creation_date = date('Y-m-d H:i:s');
                    $is_profile_active = '1';
                    $userProfiles  = $sosTable->countSOSByUserId($userId);
                    
                    if(($totalProfiles = count($userProfiles)) >= 0){
                        $profile_id   = $totalProfiles + 1;
                    }
                    
                    if($sos_id){
                        $sosRow = $sosTable->getRowById($sos_id);
                        if($sosRow){
                            $sos_id            = $sosRow->sos_id;
                            $profile_id        = $sosRow->profile_id;
                            $creation_date     = $sosRow->creation_date;
                            $is_profile_active = $sosRow->is_profile_active;
                        }
                    }
                    
                    if($profile_id <= 3) {
                        $data   = array(
                                    'user_id'                     => $userId,
                                    'blood_group'                 => $blood_group,  
                                    'illnesses'                   => $illnesses,  
                                    'is_medication'               => $is_medication, 
                                    'medication_description'      => $medication_description, 
                                    'allergies'                   => $allergies,    
                                    'is_organ_donations'          => $is_organ_donations, 
                                    'organ_donations_description' => $organ_donations_description,
                                    'mobile_number'               => $mobile_number,
                                    'facebook_username'           => $facebook_username,   
                                    'facebook_tokens'             => $facebook_tokens,   
                                    'twitter_username'            => $twitter_username,     
                                    'twitter_oauth_tokens'        => $twitter_oauth_tokens,   
                                    'twitter_oauth_secret_tokens' => $twitter_oauth_secret_tokens,
                                    'youtube_channel'             => $youtube_channel,   
                                    'emergency_message'           => $emergency_message,   
                                    'is_gps'                      => $is_gps,
                                    'is_alarm'                    => $is_alarm,       
                                    'pincode'                     => $pincode, 
                                    'profile_id'                  => $profile_id,
                                    'is_media_broadcast'          => $is_media_broadcast,
                                    'is_broadcast_video'          => $is_broadcast_video,
                                    'is_broadcast_facebook'       => $is_broadcast_facebook,
                                    'is_broadcast_twitter'        => $is_broadcast_twitter,
                                    'is_broadcast_youtube'        => $is_broadcast_youtube,
                                    'is_profile_active'           => $is_profile_active,
                                    'creation_date'               => $creation_date,
                                    'modification_date'           => date('Y-m-d H:i:s'),
                                    'is_status'                   => '1'
                                );

                        if(count($receiver_userset) > 0){
                            if($sosRow){
                                $sosRow->setFromArray($data);
                                $sosId = $sosRow->save();                        
                            }else{
                                $sosRow = $sosTable->createRow($data);
                                $sosId = $sosRow->save();
                            }  

                            if((isset($facebook_username) && ($facebook_tokens))){
                                $userData  = array(
                                    'userFbId'          => $facebook_username,
                                    'userfbaccesstoken' => $facebook_tokens
                                );

                                $userRow = $userRow->setFromArray($userData);
                                $userRow->save();
                            }

                            if((isset($twitter_username) && ($twitter_oauth_tokens) && ($twitter_oauth_secret_tokens))){
                                $userData  = array(
                                    'userTwitterId'  => $twitter_username
                                );

                                $userRow = $userRow->setFromArray($userData);
                                $userRow->save();
                            }

                            if($sosId){
                                $sosReceiverTable->deleteSOSReceivers($sosId);
                                if(count($receiver_userset) > 0){
                                    foreach($receiver_userset as $receiverId){  
                                        $receiverData = array(
                                                        'sos_id'      => $sosId,
                                                        'user_id'     => $userId,
                                                        'receiver_id' => $receiverId,
                                                        'is_send'     => '0'
                                                    );
                                        if($receiverId != $userId) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }
                                
                                if(count($receiver_emailset) > 0){
                                    foreach($receiver_emailset as $receiverEmail){  
                                        $receiverData = array(
                                                        'sos_id'         => $sosId,
                                                        'user_id'        => $userId,
                                                        'receiver_email' => $receiverEmail,
                                                        'is_send'        => '0'
                                                    );
                                        if($receiverEmail != $userRow->userEmail) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }  
                                
                                if(count($receiver_phoneset) > 0){
                                    foreach($receiver_phoneset as $receiverPhone){  
                                        $receiverData = array(
                                                        'sos_id'         => $sosId,
                                                        'user_id'        => $userId,
                                                        'receiver_phone' => $receiverPhone,
                                                        'is_send'        => '0'
                                                    );
                                        if($receiverPhone != $userRow->userPhone) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }
                               
                                if(!empty($sos_id)){
                                    $sosRows = $sosTable->getOthersProfilesByUserId($userId,$sos_id);
                                    if(!empty($sosRows)){
                                        foreach ($sosRows as $sosRow){
                                            if($sosRow->is_profile_active == 1){
                                                $is_profile_active = '1';
                                            }else{
                                                $is_profile_active = '0';
                                            }
                                            $sosData = array(
                                                        'pincode'                     => $pincode,
                                                        'facebook_username'           => $facebook_username,   
                                                        'twitter_username'            => $twitter_username,   
                                                        'facebook_tokens'             => $facebook_tokens,   
                                                        'twitter_oauth_tokens'        => $twitter_oauth_tokens,   
                                                        'twitter_oauth_secret_tokens' => $twitter_oauth_secret_tokens,
                                                        'is_profile_active'           => $is_profile_active
                                                    ); 

                                            $sosRow->setFromArray($sosData);
                                            $sosRow->save();
                                        }
                                    }
                                }else{
                                    $sosRows = $sosTable->getOthersProfilesByUserId($userId,$sosId);
                                    if(!empty($sosRows)){
                                        foreach ($sosRows as $sosRow){
                                            $is_profile_active = '0';
                                            $sosData = array(
                                                        'pincode'                     => $pincode,
                                                        'facebook_username'           => $facebook_username,   
                                                        'twitter_username'            => $twitter_username,   
                                                        'facebook_tokens'             => $facebook_tokens,   
                                                        'twitter_oauth_tokens'        => $twitter_oauth_tokens,   
                                                        'twitter_oauth_secret_tokens' => $twitter_oauth_secret_tokens,
                                                        'is_profile_active'           => $is_profile_active
                                                    ); 

                                            $sosRow->setFromArray($sosData);
                                            $sosRow->save();
                                        }
                                    }
                                }   
                                
                                $waGuardRow = $sosEmergencyTable->isUserWaGuardExists($userId);
                                if(!empty($waGuardRow)){
                                    $waGuardData = array('pincode' => $pincode);
                                    $waGuardRow = $waGuardRow->setFromArray($waGuardData);
                                    $waGuardRow->save();
                                }
                                                                
                                $waTrackRow = $sosEmergencyTable->isUserWaTrackExists($userId);
                                if(!empty($waTrackRow)){
                                    $waTrackData = array('pincode' => $pincode);
                                    $waTrackRow = $waTrackRow->setFromArray($waTrackData);
                                    $waTrackRow->save();
                                }
                            }
                           $result = array('sos_id' => $sosId, 'profile_id' => "$profile_id");

                           $db->commit();
                           $this->common->displayMessage($this->view->translate('profile_created_success'),'0',$result,'0');   

                        } else{
                            $this->common->displayMessage($this->view->translate('sos_receiver_not_exist'),'1',array(),'154');
                        }                        
                    } else{
                        $this->common->displayMessage($this->view->translate('max_profiles_create'),'1',array(),'152');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
              exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function activateVolunteerAction(){            
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];   
        $is_volunteer                   = $decoded['is_volunteer'];
        $user_professions               = $decoded['user_professions'];
        $deviceLanguage                 = $decoded['deviceLanguage'];        
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {      
                $db = $this->db;
                $db->beginTransaction();
                
                try 
                {   
                    $sosRow = $sosSettingTable->getRowByUserId($userId);
                    if(!empty($sosRow->sett_id)) {       
                        $data   = array(
                                    'is_volunteer'      => $is_volunteer,
                                    'user_professions'  => $user_professions
                                );

                        $sosRow->setFromArray($data);
                        $sett_id = $sosRow->save();    
                    }else{      
                        $data   = array(
                                    'user_id'            => $userId,
                                    'is_volunteer'       => $is_volunteer,
                                    'is_sos_active'      => '0',
                                    'is_sos_setup'       => '0',
                                    'user_professions'   => $user_professions
                                );

                        $sosRow  = $sosSettingTable->createRow($data);
                        $sett_id = $sosRow->save();    
                    }
                    $result = array('sett_id' => $sett_id);
                    
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $db->commit();
               $this->common->displayMessage($this->view->translate('volunteer_activated_success'),'0',$result,'0');   
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function sosSetupAction(){            
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];  
        $is_sos_setup                   = $decoded['is_sos_setup'];
        $latitude                       = $decoded['latitude'];
        $longitude                      = $decoded['longitude'];
        $deviceLanguage                 = $decoded['deviceLanguage'];        
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                             
                try 
                {
                    $sosRow = $sosSettingTable->getRowByUserId($userId);
                    if(!empty($sosRow->sett_id)) {                     
                        $data   = array(
                                    'is_sos_setup'  => $is_sos_setup 
                                );

                        $sosRow->setFromArray($data);
                        $sett_id = $sosRow->save();    
                    }else{
                        $data   = array(
                                    'user_id'            => $userId,
                                    'is_volunteer'       => '0',
                                    'is_sos_active'      => '0',
                                    'is_sos_setup'       => $is_sos_setup,
                                    'user_professions'   => '0'
                                );

                        $sosRow  = $sosSettingTable->createRow($data);
                        $sett_id = $sosRow->save();  
                    }

                    if(!empty($latitude) && !empty($longitude)){                    
                        $userData   = array(
                                        'userLatitude'  => $latitude,
                                        'userLongitude' => $longitude
                                    );
                        $userRow->setFromArray($userData);
                        $userRow->save();
                    }
                   $result = array('sett_id' => $sett_id, 'is_sos_setup' => $is_sos_setup);
                    
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $db->commit(); 
               $this->common->displayMessage($this->view->translate('sos_setup_success'),'0',$result,'0');   
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function activateSosAction(){            
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEventSendDetailsTable       = new Application_Model_DbTable_WaSosEventSendDetails();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications();  
        $sosVolunteerHistoryTable       = new Application_Model_DbTable_WaSosVolunteerHistory();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $this->getRequest()->getPost('userId');
        $userDeviceId                   = $this->getRequest()->getPost('userDeviceId');
        $userDeviceToken                = $this->getRequest()->getPost('userDeviceToken');        
        $deviceType                     = $this->getRequest()->getPost('device_type');
        $userSecurity                   = $this->getRequest()->getPost('userSecurity');  
        $is_sos_active                  = $this->getRequest()->getPost('is_sos_active');
        $latitude                       = $this->getRequest()->getPost('latitude');
        $longitude                      = $this->getRequest()->getPost('longitude');
        $deviceLanguage                 = $this->getRequest()->getPost('deviceLanguage'); 
        $attachment_type                = $this->getRequest()->getPost('attachment_type');
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                                
                try 
                {
                    $sosSettingRow = $sosSettingTable->getRowByUserId($userId); 
      
                    if(!empty($sosSettingRow->sett_id) && ($sosSettingRow->is_sos_setup == 1)){                     
                        $sosSettingData = array(
                                            'is_sos_active' => $is_sos_active 
                                        );

                        $sosSettingRow = $sosSettingRow->setFromArray($sosSettingData);
                        $sett_id = $sosSettingRow->save();    

                        $userData   = array(
                                       'userLatitude'  => $latitude,
                                       'userLongitude' => $longitude
                                    );

                        if(!empty($latitude) && !empty($longitude)) {
                            $userRow->setFromArray($userData);
                            $userRow->save();
                        }
                       
                        $sosRow   = $sosTable->getActiveProfileByUserId($userId); 
                  
                        if($sosRow->is_send == 0){ 
                            $sosReceiverRows  = $sosReceiverTable->getRowsBySOSId($sosRow->sos_id);
                            $sosVolunteerRows = $sosTable->getNearByUserId($userId,$userRow->userLatitude,$userRow->userLongitude);

                            $sosEventSendDetailsRow = $sosEventSendDetailsTable->getRowByUserId($userId);
                            if(count($sosEventSendDetailsRow) <= 0){ 
                                $sosEventSendDeatilsData = array(
                                                            'sos_id'            => $sosRow->sos_id,
                                                            'user_id'           => $userId,
                                                            'event_send_date'   => date('Y-m-d H:i:s'),
                                                            'event_alert_count' => 1,
                                                            'creation_date'     => date('Y-m-d H:i:s'),
                                                            'modification_date' => date('Y-m-d H:i:s'),
                                                            'is_send'           => '1'
                                                        );
                                $sosEventSendDetailsRow = $sosEventSendDetailsTable->createRow($sosEventSendDeatilsData);
                                $sosEventSendDetailsRow->save();
                            } 
                            
                            $alert_type   = "wa_sos_activate_request";
                            $msg          = $sosRow->emergency_message;
                            if($sosRow->is_gps == 1){
                                foreach ($sosVolunteerRows as $sosVolunteerRow){
                                    $postData  = array(
                                                    'user_id'      => $userId,
                                                    'receiver_id'  => $sosVolunteerRow->userId,
                                                    'sos_id'       => $sosRow->sos_id
                                                );
                                    $this->getUserDeviceDetailsAction($postData, $msg, $alert_type); 

                                    $full_name    = $sosVolunteerRow->userNickName; 
                                    $message      = $full_name.' - '.$msg;// $this->view->translate('needs_your_help');

                                    $userNotificationsTable->createNotification($userId, $sosVolunteerRow->userId, $message, Application_Model_DbTable_NotificationType::SOS_ACTIVATE_REQUEST);

                                    $sosHistoryData = array(
                                                        'sos_id'            => $sosRow->sos_id,
                                                        'user_id'           => $userId,
                                                        'receiver_id'       => $sosVolunteerRow->userId,
                                                        'creation_date'     => date('Y-m-d H:i:s'),
                                                        'modification_date' => date('Y-m-d H:i:s')                                                    
                                                    );
                                    $sosVolunteerHistoryRow = $sosVolunteerHistoryTable->createRow($sosHistoryData);
                                    $sosVolunteerHistoryRow->save();                                
                                }
                            }
                         
                            foreach($sosReceiverRows as $receiverRow){                            
                                if($receiverRow->receiver_id != 0){
                                    $postData  = array(
                                                    'user_id'      => $receiverRow->user_id,
                                                    'receiver_id'  => $receiverRow->receiver_id,
                                                    'sos_id'       => $receiverRow->sos_id
                                               );
                                    $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);   
                                       
                                    $message  = $full_name.' - '.$msg; // this->view->translate('needs_your_help');

                                    $userNotificationsTable->createNotification($userId, $receiverRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_ACTIVATE_REQUEST);

                                }else if($receiverRow->receiver_email != ''){
                                    $params = array(
                                        'userNickName'      => $userRow->userNickName,
                                        'baseUrl'           => $this->baseUrl,
                                        'emergency_message' => $msg
                                    );
                                    $this->mail($params,'incoming_sos.phtml',$receiverRow->receiver_email,'Welcome To WA-app');

                                }else if($receiverRow->receiver_phone != ''){
                                    $message      = $full_name.' - '.$msg;
                                    $this->common->sendSms_recommend($receiverRow->receiver_phone,$message);
                                }
                            }
                        }
                        
                        $file = "";
                        if(!empty($_FILES['file']['name'])){
                            if(!empty($attachment_type)){
                                $mediaFile = $_FILES['file'];
                                $data   = array(
                                            'attachment_type' => $attachment_type,
                                            'file'            => $_FILES['file'],
                                            'sos_id'          => $sosRow->sos_id,
                                            'userId'          => $userId
                                        );
                                $request_url  = $this->baseUrl()."/wa-sos/upload-attachments";
                                $this->curlRequestAction($request_url,$data);
                                if($attachment_type == 2){
                                    $file = "image";
                                }else if($attachment_type == 1){
                                    $file = "video";
                                }else if($attachment_type == 0){
                                    $file = "audio";
                                }else{
                                    $file = "";
                                }                      

                                if($sosRow->is_broadcast_facebook == 1){
                                    if(!empty($sosRow->facebook_tokens)){
                                       $this->facebookBroadcastAction($sosRow);
                                    }
                                }

                                if($sosRow->is_broadcast_twitter == 1){   
                                    if(!empty($sosRow->twitter_oauth_tokens) && !empty($sosRow->twitter_oauth_secret_tokens)){
                                        $this->twitterBroadcastAction($sosRow);
                                    }
                                } 
                            }else{
                               $this->common->displayMessage($this->view->translate('attachment_type_not_defined'),'1',array(),'178');
                            }
                        }
                        
                        $sosData   = array('is_send' => '1');
                       
                        if($sosRow){
                            $sosRow = $sosRow->setFromArray($sosData);
                            $sosRow->save();
                        }  
                        
                       $result = array('sett_id' => $sett_id, 'is_sos_active' => $is_sos_active, 'file' => $file); 
                       $this->common->displayMessage($this->view->translate('sos_activated_success'),'0',$result,'0');   
                    }else{
                        $this->common->displayMessage($this->view->translate('not_sos_setup'),'1',array(),'180');
                    }
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function curlRequestAction($target_url,$data){   
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,FALSE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $go     = curl_exec($ch);
        $result = json_decode($go,TRUE);  
        curl_close($ch);
    }
    
    public function uploadAttachmentsAction(){
        $sosTable         = new Application_Model_DbTable_WaSos();
        $sosFilesTable    = new Application_Model_DbTable_WaSosFiles();
        
        $body             = $this->getRequest()->getRawBody();
        $postData         = json_decode($body, true);
        $bucket_name      = "wa-sos";        
        $response_url     = $this->common->uploadWABucket($bucket_name, $postData['file']);       
        $sosFilesData     = array(
                                'sos_id'             => $postData['sos_id'],
                                'file_type'          => $postData['attachment_type'],
                                'file_url'           => $response_url['new_file_name'],
                                'is_facebook_send'   => '0',
                                'is_twitter_send'    => '0',
                                'is_send'            => '0',
                                'creation_date'      => date('Y-m-d H:i:s')
                          );
        
        $sosFilesRow = $sosFilesTable->createRow($sosFilesData);
        $sosFilesRow->save();
        exit();
    } 
    
    public function facebookBroadcastAction($sosRow){
        $sosTable       = new Application_Model_DbTable_WaSos();
        $sosFilesTable  = new Application_Model_DbTable_WaSosFiles();
        $fbData         = array(
                            'tokenString'        => $sosRow->facebook_tokens
                        ); 
        $sosData        = array(
                            'is_media_broadcast' => $sosRow->is_media_broadcast,
                            'is_media_video'     => $sosRow->is_broadcast_video,
                            'user_id'            => $sosRow->user_id,
                            'text'               => $sosRow->emergency_message, 
                            'sos_id'             => $sosRow->sos_id,
                            'is_send'            => $sosRow->is_send
                        );
     
        $sosFileData = array();
        $sosFileRows = $sosFilesTable->getFacebookRowBySosId($sosRow->sos_id);
        if($sosData->is_send == 0){
            $file_type = "";
            $file_url  = "";
            $this->common->fbShare($fbData,$sosData,$file_type,$file_url);                
        }
        
        if(!empty($sosFileRows)){
            foreach ($sosFileRows as $sosFileRow){
                $this->common->fbShare($fbData,$sosData,$sosFileRow->file_type, $sosFileRow->file_url);
                $sosFileData   = array(
                                    'is_facebook_send'    => "1",
                               ); 

                $sosFileRow->setFromArray($sosFileData);
                $sosFileRow->save();
            }
        }       
        $this->sendSocialNotificationsAction($sosRow->sos_id);
    }
    
    public function twitterBroadcastAction($sosRow){
        $sosTable       = new Application_Model_DbTable_WaSos();
        $sosFilesTable  = new Application_Model_DbTable_WaSosFiles();
        $twData         = array(
                            'twitter_oauth_tokens'         => $sosRow->twitter_oauth_tokens,
                            'twitter_oauth_secret_tokens'  => $sosRow->twitter_oauth_secret_tokens
                        );
        $sosData        = array(
                            'is_media_broadcast' => $sosRow->is_media_broadcast,
                            'is_media_video'     => $sosRow->is_broadcast_video,
                            'user_id'            => $sosRow->user_id,
                            'text'               => $sosRow->emergency_message, 
                            'sos_id'             => $sosRow->sos_id,
                            'is_send'            => $sosRow->is_send
                        ); 
       
        $sosFileData    = array();
        $sosFileRows    = $sosFilesTable->getTwitterRowBySosId($sosRow->sos_id);
        if($sosData->is_send == 0){
            $file_url  = "";
            $this->common->twShare($twData,$sosData,$sosFileRow->file_url);              
        }
        
        if(!empty($sosFileRows)){
            foreach ($sosFileRows as $sosFileRow){
                $this->common->twShare($twData,$sosData,$sosFileRow->file_url);          
                $sosFileData  = array(
                                    'is_twitter_send' => '1'
                              );
                
                $sosFileRow->setFromArray($sosFileData);
                $sosFileRow->save();
            }
        }
       $this->sendSocialNotificationsAction($sosRow->sos_id);
    }
    
    public function sendSocialNotificationsAction($sos_id){ 
        $userTable               = new Application_Model_DbTable_Users();
        $sosTable                = new Application_Model_DbTable_WaSos();
        $sosFilesTable           = new Application_Model_DbTable_WaSosFiles();
        $sosReceiverTable        = new Application_Model_DbTable_WaSosReceiver();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $sosFileRow         = $sosFilesTable->getSocialSharedRowsBySosId($sos_id);
  
        $alert_type   = "wa_sos_social_notification_request";
        if($sosFileRow->is_facebook_send == 1){
            $msg    = $this->view->translate('facebook_share_notification');
          
            $sosReceiverRows   = $sosReceiverTable->getRowsBySOSId($sos_id);

            if(!empty($sosReceiverRows)){
                foreach ($sosReceiverRows as $receiverRow){
                    if($receiverRow->receiver_id != 0){
                        $postData  = array(
                                      'user_id'      => $receiverRow->user_id,
                                      'receiver_id'  => $receiverRow->receiver_id,
                                      'sos_id'       => $sos_id
                                   );

                        $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);   

                        $senderRow        = $userTable->getRowById($receiverRow->user_id);
                        $full_name        = $senderRow->userNickName; 
                        $message          = $full_name.' '.$this->view->translate('facebook_share_notification');

                        $usertNotificationsTable->createNotification($receiverRow->user_id, $receiverRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_ACTIVATE_REQUEST);
                    }
                }
            }
        }
       
        if($sosFileRow->is_twitter_send == 1){
            $alert_type   = "wa_sos_social_notification_request";
            $msg          = $this->view->translate('twitter_share_notification');
            
            $sosReceiverRows    = $sosReceiverTable->getRowsBySOSId($sos_id);
            if(!empty($sosReceiverRows)){
                foreach ($sosReceiverRows as $receiverRow){
                    if($receiverRow->receiver_id != 0){
                        $postData  = array(
                                      'user_id'      => $receiverRow->user_id,
                                      'receiver_id'  => $receiverRow->receiver_id,
                                      'sos_id'       => $receiverRow->sos_id
                                   );
                        $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);   

                        $senderRow        = $userTable->getRowById($receiverRow->user_id);
                        $full_name        = $senderRow->userNickName; 
                        $message          = $full_name.' '.$this->view->translate('facebook_share_notification');

                        $usertNotificationsTable->createNotification($receiverRow->user_id, $receiverRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_ACTIVATE_REQUEST);
                    }
                }
            }
        }
    } 
                            
    public function deactivateSosAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEventSendDetailsTable       = new Application_Model_DbTable_WaSosEventSendDetails();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications();
        $sosVolunteerHistoryTable       = new Application_Model_DbTable_WaSosVolunteerHistory();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];  
        $is_sos_active                  = $decoded['is_sos_active'];
        $latitude                       = $decoded['latitude'];
        $longitude                      = $decoded['longitude'];
        $deviceLanguage                 = $decoded['deviceLanguage']; 
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                                
                try 
                {
                    $sosRow = $sosSettingTable->getRowByUserId($userId);
                    if(!empty($sosRow->sett_id) && ($sosRow->is_sos_setup == 1)){ 
                        if($sosRow->is_sos_active == 1){ 
                            $data   = array(
                                        'is_sos_active' => $is_sos_active
                                    );

                            $sosRow->setFromArray($data);
                            $sett_id = $sosRow->save();    

                            $userData   = array(
                                           'userLatitude'  => $latitude,
                                           'userLongitude' => $longitude
                                        );

                            if(!empty($latitude) && !empty($longitude)) {
                                $userRow->setFromArray($userData);
                                $userRow->save();
                            }
                            $result = array('sett_id' => $sett_id);

                            $sosRow  = $sosTable->getActiveProfileByUserId($userId); 

                            if($sosRow->is_send == 1){ 
                                $sosReceiverRows  = $sosReceiverTable->getRowsBySOSId($sosRow->sos_id);
                                $sosVolunteerRows = $sosVolunteerHistoryTable->getRowBySOSId($userId,$sosRow->sos_id);

                                $sosEventSendDetailsRow = $sosEventSendDetailsTable->getRowByUserId($userId);
                                if(count($sosEventSendDetailsRow) <= 0){ 
                                    $sosEventSendDeatilsData = array(
                                                                'sos_id'            => $sosRow->sos_id,
                                                                'user_id'           => $userId,
                                                                'event_send_date'   => date('Y-m-d H:i:s'),
                                                                'event_alert_count' => 1,
                                                                'creation_date'     => date('Y-m-d H:i:s'),
                                                                'modification_date' => date('Y-m-d H:i:s'),
                                                                'is_send'           => '1'
                                                            );
                                    $sosEventSendDetailsRow = $sosEventSendDetailsTable->createRow($sosEventSendDeatilsData);
                                    $sosEventSendDetailsRow->save();
                                } 
                            
                                if($sosRow->is_gps == 1){
                                    foreach ($sosVolunteerRows as $sosVolunteerRow){
                                        $postData  = array(
                                                        'user_id'      => $userId,
                                                        'receiver_id'  => $sosVolunteerRow->receiver_id,
                                                        'sos_id'       => $sosRow->sos_id
                                                    );
                                        $alert_type   = "wa_sos_deactivate_request";
                                        $msg          = $this->view->translate('sos_deactivate');
                                        $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);

                                        $full_name    = $sosVolunteerRow->userNickName; 
                                        $message      = $full_name.' - '.$this->view->translate('sos_deactivate');

                                        $userNotificationsTable->createNotification($sosVolunteerRow->userId, $userId, $message, Application_Model_DbTable_NotificationType::SOS_DEACTIVATE_REQUEST);
                                    }
                                }
                            
                                $alert_type   = "wa_sos_deactivate_request";
                                $full_name    = $userRow->userNickName; 
                                foreach($sosReceiverRows as $receiverRow){                            
                                    if($receiverRow->receiver_id != '0'){
                                        $postData   = array(
                                                        'user_id'      => $receiverRow->user_id,
                                                        'receiver_id'  => $receiverRow->receiver_id,
                                                        'sos_id'       => $receiverRow->sos_id
                                                    );
                                        $msg       = $this->view->translate('sos_deactivate');
                                        $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);   

                                        $message   = $full_name.' - '.$this->view->translate('sos_deactivate');

                                        $userNotificationsTable->createNotification($userId, $receiverRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_DEACTIVATE_REQUEST);

                                    }else if($receiverRow->receiver_email != ''){
                                        $params = array(
                                            'userNickName'      => $userRow->userNickName,
                                            'baseUrl'           => $this->baseUrl
                                        );
                                        $this->mail($params,'stop_sos.phtml',$receiverRow->receiver_email,'Welcome To WA-app');

                                    }else if($receiverRow->receiver_phone != ''){                                     
                                        $message          = $full_name.' - '.$this->view->translate('sos_deactivate');
                                        $this->common->sendSms_recommend($receiverRow->receiver_phone,$message);

                                    }
                                }
                            }
                            $sosSendArr = array('is_send' => '0');
                            $sosRow = $sosRow->setFromArray($sosSendArr);
                            $sosRow->save();
                            
                            $this->common->displayMessage($this->view->translate('sos_deactivated_success'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('Your SOS is not triggerd'),'1',array(),'156');
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('You have not sos setup.'),'1',array(),'156');
                    }                    
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }

    public function getUserDeviceDetailsAction($arr,$msg,$alert_type){ 
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
  
        $user_id            = $arr['user_id'];
        $receiver_id        = $arr['receiver_id'];
        $sos_id             = $arr['sos_id'];
        $senderRow          = $userTable->getRowById($user_id);
        $receiverRow        = $userTable->getRowById($receiver_id);  
     
        if($senderRow && $senderRow->isActive()) 
        {
            $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($receiverRow->userId);            
            $message            = ucfirst($senderRow->userNickName)." - ".$msg;
            
            if(!empty($userLoginDeviceSet))
            {
                foreach ($userLoginDeviceSet as $loginDeviceRow)
                {
                    if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                    {
                        if($loginDeviceRow->userDeviceType == "iphone")
                        {
                            $payload['aps']  = array(
                                                'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message
                                              );
                        }
                        else
                        {  
                            $resultData = array(
                                            'userImage'  => ($receiverRow->userImage)?$this->makeUrl($receiverRow->userImage):"",
                                            'userId'     => $receiverRow->userId,
                                            'userName'   => $receiverRow->userNickName,
                                            'sos_id'     => $sos_id
                                        );

                            $payload    = array(
                                            'message'   => $message,
                                            'type'      => $alert_type,
                                            'result'    => $resultData    
                                        );

                            $payload = json_encode($payload);  
                        }         
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType,$loginDeviceRow->userDeviceToken,$payload);  
                    }              
                }
            } 
        }
    }
    
    public function getDetailsByUserIdAction(){            
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
       
        $result                         = array();
        $result_arr                     = array();
        $trackData                      = array();
        $guardData                      = array();
        $guardArr                       = array();
        $trackArr                       = array();
        $receiver_userset               = array();
        $receiver_emailset              = array();
        $receiver_phoneset              = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                  
                try 
                {
                    $sosRows   = $sosTable->getAllProfilesByUserId($userId);
                  
                    if(!empty($sosRows)) {
                        foreach($sosRows as $sosRow) {                                           
                            $sosData    = array(
                                            'sos_id'                      => $sosRow->sos_id,
                                            'user_id'                     => $sosRow->user_id,
                                            'blood_group'                 => $sosRow->blood_group ? $sosRow->blood_group:"",
                                            'illnesses'                   => $sosRow->illnesses ? $sosRow->illnesses:"",
                                            'is_medication'               => $sosRow->is_medication,
                                            'medication_description'      => $sosRow->medication_description ? $sosRow->medication_description:"",
                                            'allergies'                   => $sosRow->allergies ? $sosRow->allergies:"",
                                            'is_organ_donations'          => $sosRow->is_organ_donations,
                                            'organ_donations_description' => $sosRow->organ_donations_description ? $sosRow->organ_donations_description:"",
                                            'mobile_number'               => $sosRow->mobile_number ? $sosRow->mobile_number:"",
                                            'facebook_username'           => $sosRow->facebook_username ? $sosRow->facebook_username:"",
                                            'facebook_tokens'             => $sosRow->facebook_tokens ? $sosRow->facebook_tokens:"",
                                            'twitter_username'            => $sosRow->twitter_username ? $sosRow->twitter_username:"",
                                            'twitter_oauth_tokens'        => $sosRow->twitter_oauth_tokens ? $sosRow->twitter_oauth_tokens:"",
                                            'twitter_oauth_secret_tokens' => $sosRow->twitter_oauth_secret_tokens ? $sosRow->twitter_oauth_secret_tokens:"",
                                            'youtube_channel'             => $sosRow->youtube_channel ? $sosRow->youtube_channel:"",
                                            'emergency_message'           => $sosRow->emergency_message ? $sosRow->emergency_message:"",
                                            'is_gps'                      => $sosRow->is_gps,
                                            'is_alarm'                    => $sosRow->is_alarm,
                                            'is_media_broadcast'          => $sosRow->is_media_broadcast,
                                            'is_broadcast_video'          => $sosRow->is_broadcast_video,
                                            'is_broadcast_facebook'       => $sosRow->is_broadcast_facebook,
                                            'is_broadcast_twitter'        => $sosRow->is_broadcast_twitter,
                                            'is_broadcast_youtube'        => $sosRow->is_broadcast_youtube,
                                            'pincode'                     => $sosRow->pincode,
                                            'profile_id'                  => $sosRow->profile_id,
                                            'is_profile_active'           => $sosRow->is_profile_active,
                                            'is_status'                   => $sosRow->is_status
                                        );
                            
                            $sosReceiverRows = $sosReceiverTable->getRowsBySOSId($sosRow->sos_id);
                            
                            if(!empty($sosReceiverRows)){
                                foreach ($sosReceiverRows as $receiverRow){
                                    if(!empty($receiverRow->receiver_id)){
                                        $receiver_userset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'receiver_id'     => $receiverRow->receiver_id,
                                                                'userNickName'    => $receiverRow->userNickName,
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                    if(!empty($receiverRow->receiver_email)){
                                        $receiver_emailset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'receiver_email'  => $receiverRow->receiver_email ? $receiverRow->receiver_email:"",
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                    if(!empty($receiverRow->receiver_phone)){
                                        $receiver_phoneset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'receiver_phone'  => $receiverRow->receiver_phone ? $receiverRow->receiver_phone:"",
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                }
                            }   
                            
                            $result_arr[]   = array_merge($sosData,array('receiver_userset' => $receiver_userset,'receiver_emailset' => $receiver_emailset,'receiver_phoneset' => $receiver_phoneset));  
                            $sosData        = array();
                            $receiverData   = array(); 
                           
                            $receiver_userset    = array();
                            $receiver_emailset   = array();
                            $receiver_phoneset   = array();
                        }
                        
                        $sosSettingRow = $sosSettingTable->getRowByUserId($userId);
                        $sosSettingArr = array(
                                            'is_volunteer'      => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer:"0",
                                            'is_sos_active'     => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active:"0",
                                            'is_sos_setup'      => $sosSettingRow->is_sos_setup ? $sosSettingRow->is_sos_setup:"0",
                                            'user_professions'  => $sosSettingRow->user_professions ? $sosSettingRow->user_professions:"0"
                                       );
                                                
                        $waGuardRow  = $sosEmergencyTable->isUserWaGuardExists($userId);
                        if(!empty($waGuardRow)){
                            $guardReceiverRows = $sosReceiverTable->getWaEmergencyRowsByUserId($waGuardRow->sos_emergency_id,$userId);
                            $guardData  = array(
                                            'sos_emrgency_id' => $waGuardRow->sos_emergency_id,
                                            'emergency_type'  => $waGuardRow->emergency_type,
                                            'preset_message'  => $waGuardRow->preset_message,
                                            'pincode'         => $waGuardRow->pincode,
                                            'interval_time'   => $waGuardRow->interval_time,
                                            'is_start'        => $waGuardRow->is_start
                                        );       
                            
                            $guard_receiver_userset    = array();
                            $guard_receiver_emailset   = array();
                            $guard_receiver_phoneset   = array();
                          
                            foreach ($guardReceiverRows as $guardReceiverRow){
                                if(!empty($guardReceiverRow->receiver_id)){
                                    $guard_receiver_userset[]  = array(
                                                                    'sos_receiver_id' => $guardReceiverRow->sos_receiver_id,
                                                                    'user_id'         => $guardReceiverRow->user_id,
                                                                    'receiver_id'     => $guardReceiverRow->receiver_id,
                                                                    'userNickName'    => $guardReceiverRow->userNickName,
                                                                    'is_send'         => $guardReceiverRow->is_send,
                                                                    'is_deleted'      => $guardReceiverRow->is_deleted
                                                               );
                                }
                                if(!empty($guardReceiverRow->receiver_email)){
                                    $guard_receiver_emailset[] = array(
                                                                    'sos_receiver_id' => $guardReceiverRow->sos_receiver_id,
                                                                    'user_id'         => $guardReceiverRow->user_id,
                                                                    'receiver_email'  => $guardReceiverRow->receiver_email ? $guardReceiverRow->receiver_email:"",
                                                                    'is_send'         => $guardReceiverRow->is_send,
                                                                    'is_deleted'      => $guardReceiverRow->is_deleted
                                                                );
                                }
                                if(!empty($guardReceiverRow->receiver_phone)){
                                    $guard_receiver_phoneset[] = array(
                                                                    'sos_receiver_id' => $guardReceiverRow->sos_receiver_id,
                                                                    'user_id'         => $guardReceiverRow->user_id,
                                                                    'receiver_phone'  => $guardReceiverRow->receiver_phone ? $guardReceiverRow->receiver_phone:"",
                                                                    'is_send'         => $guardReceiverRow->is_send,
                                                                    'is_deleted'      => $guardReceiverRow->is_deleted
                                                                );
                                }
                            }               
                     
                            $guardArr   = array_merge($guardData,
                                            array('guard_receiver_userset'  => $guard_receiver_userset),
                                            array('guard_receiver_emailset' => $guard_receiver_emailset),
                                            array('guard_receiver_phoneset' => $guard_receiver_phoneset)
                                        );  
                        }
                        
                        $waTrackRow  = $sosEmergencyTable->isUserWaTrackExists($userId);
                        if(!empty($waTrackRow)){  
                            $trackReceiverRows = $sosReceiverTable->getWaEmergencyRowsByUserId($waTrackRow->sos_emergency_id,$userId);
                            $trackData  = array(
                                            'sos_emrgency_id' => $waTrackRow->sos_emergency_id,
                                            'emergency_type'  => $waTrackRow->emergency_type,
                                            'pincode'         => $waTrackRow->pincode,
                                            'interval_time'   => $waTrackRow->interval_time,
                                            'end_time'        => $waTrackRow->end_time,
                                            'is_start'        => $waTrackRow->is_start
                                        );
                            
                            $track_receiver_userset    = array();
                            $track_receiver_emailset   = array();
                            $track_receiver_phoneset   = array();
                            
                            foreach ($trackReceiverRows as $trackReceiverRow){
                                if(!empty($trackReceiverRow->receiver_id)){  
                                    $track_receiver_userset[] = array(
                                                                'sos_receiver_id' => $trackReceiverRow->sos_receiver_id,
                                                                'user_id'         => $trackReceiverRow->user_id,
                                                                'receiver_id'     => $trackReceiverRow->receiver_id,
                                                                'userNickName'    => $trackReceiverRow->userNickName,
                                                                'is_send'         => $trackReceiverRow->is_send,
                                                                'is_deleted'      => $trackReceiverRow->is_deleted
                                                            );
                                }
                                if(!empty($trackReceiverRow->receiver_email)){
                                    $track_receiver_emailset[] = array(
                                                                'sos_receiver_id' => $trackReceiverRow->sos_receiver_id,
                                                                'sos_id'          => $trackReceiverRow->sos_id,
                                                                'user_id'         => $trackReceiverRow->user_id,
                                                                'receiver_email'  => $trackReceiverRow->receiver_email ? $trackReceiverRow->receiver_email:"",
                                                                'is_send'         => $trackReceiverRow->is_send,
                                                                'is_deleted'      => $trackReceiverRow->is_deleted
                                                            );
                                }
                                if(!empty($trackReceiverRow->receiver_phone)){
                                    $track_receiver_phoneset[] = array(
                                                                'sos_receiver_id' => $trackReceiverRow->sos_receiver_id,
                                                                'user_id'         => $trackReceiverRow->user_id,
                                                                'receiver_phone'  => $trackReceiverRow->receiver_phone ? $trackReceiverRow->receiver_phone:"",
                                                                'is_send'         => $trackReceiverRow->is_send,
                                                                'is_deleted'      => $trackReceiverRow->is_deleted
                                                            );
                                }
                            }    
                            $trackArr  = array_merge($trackData,
                                            array('track_receiver_userset' => $track_receiver_userset),
                                            array('track_receiver_emailset' => $track_receiver_emailset),
                                            array('track_receiver_phoneset' => $track_receiver_phoneset)
                                       );  
                        }
                        $result = array_merge($result_arr,$sosSettingArr,
                                    array('wa_guard_setting'=> $guardArr),
                                    array('wa_track_setting'=> $trackArr)
                                );  
                    } 
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                } 
               $this->common->displayMessage($this->view->translate('user_profiles_detail'),'0',$result,'0');  
           
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function getDetailsBySosIdAction(){            
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];
        $sos_id                         = $decoded['sos_id'];    
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $sosData                        = array();        
        $receiver_userset               = array();
        $receiver_emailset              = array();
        $receiver_phoneset              = array();
        $result_arr                     = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {             
                try 
                {
                    if($sos_id){ 
                        $sosRow          = $sosTable->getRowById($sos_id);
                        $sosReceiverRows = $sosReceiverTable->getRowsBySOSId($sos_id);
                       
                        if($sosRow) {                       
                            $sosData = array(
                                        'sos_id'                      => $sosRow->sos_id,
                                        'user_id'                     => $sosRow->user_id,
                                        'blood_group'                 => $sosRow->blood_group ? $sosRow->blood_group:"",
                                        'illnesses'                   => $sosRow->illnesses ? $sosRow->illnesses:"",
                                        'is_medication'               => $sosRow->is_medication,
                                        'medication_description'      => $sosRow->medication_description ? $sosRow->medication_description:"",
                                        'allergies'                   => $sosRow->allergies ? $sosRow->allergies:"",
                                        'is_organ_donations'          => $sosRow->is_organ_donations,
                                        'organ_donations_description' => $sosRow->organ_donations_description ? $sosRow->organ_donations_description:"",
                                        'mobile_number'               => $sosRow->mobile_number ? $sosRow->mobile_number:"",
                                        'facebook_username'           => $sosRow->facebook_username ? $sosRow->facebook_username:"",
                                        'facebook_tokens'             => $sosRow->facebook_tokens ? $sosRow->facebook_tokens:"",
                                        'twitter_username'            => $sosRow->twitter_username ? $sosRow->twitter_username:"",
                                        'twitter_oauth_tokens'        => $sosRow->twitter_oauth_tokens ? $sosRow->twitter_oauth_tokens:"",
                                        'twitter_oauth_secret_tokens' => $sosRow->twitter_oauth_secret_tokens ? $sosRow->twitter_oauth_secret_tokens:"",
                                        'youtube_channel'             => $sosRow->youtube_channel ? $sosRow->youtube_channel:"",
                                        'emergency_message'           => $sosRow->emergency_message ? $sosRow->emergency_message:"",
                                        'is_gps'                      => $sosRow->is_gps,
                                        'is_alarm'                    => $sosRow->is_alarm,
                                        'is_media_broadcast'          => $sosRow->is_media_broadcast,
                                        'is_broadcast_video'          => $sosRow->is_broadcast_video,
                                        'is_broadcast_facebook'       => $sosRow->is_broadcast_facebook,
                                        'is_broadcast_twitter'        => $sosRow->is_broadcast_twitter,
                                        'is_broadcast_youtube'        => $sosRow->is_broadcast_youtube,
                                        'pincode'                     => $sosRow->pincode,
                                        'profile_id'                  => $sosRow->profile_id,
                                        'is_profile_active'           => $sosRow->is_profile_active,
                                        'is_status'                   => $sosRow->is_status
                                    );
                            
                            if(!empty($sosReceiverRows)){
                                foreach ($sosReceiverRows as $receiverRow){
                                    if(!empty($receiverRow->receiver_id)){
                                        $userRow        = $userTable->getRowById($receiverRow->receiver_id);
                                        $receiver_userset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'userFullName'    => $userRow->userFullName,
                                                                'receiver_id'     => $receiverRow->receiver_id,
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                    if(!empty($receiverRow->receiver_email)){
                                        $receiver_emailset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'receiver_email'  => $receiverRow->receiver_email ? $receiverRow->receiver_email:"",
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                    if(!empty($receiverRow->receiver_phone)){
                                        $receiver_phoneset[] = array(
                                                                'sos_receiver_id' => $receiverRow->sos_receiver_id,
                                                                'sos_id'          => $receiverRow->sos_id,
                                                                'user_id'         => $receiverRow->user_id,
                                                                'receiver_phone'  => $receiverRow->receiver_phone ? $receiverRow->receiver_phone:"",
                                                                'is_send'         => $receiverRow->is_send,
                                                                'is_deleted'      => $receiverRow->is_deleted
                                                            );
                                    }
                                }
                            }
                            
                            $result_arr = array_merge($sosData,array('receiver_userset' => $receiver_userset,'receiver_emailset' => $receiver_emailset,'receiver_phoneset' => $receiver_phoneset));  
                            
                            $sosSettingRow = $sosSettingTable->getRowByUserId($userId);
                            $sosSettingArr = array(
                                                'is_volunteer'      => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer:"0",
                                                'is_sos_active'     => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active:"0",
                                                'user_professions'  => $sosSettingRow->user_professions ? $sosSettingRow->user_professions:"0"
                                           );
                            $result    = array_merge($result_arr,$sosSettingArr);  
                            $this->common->displayMessage($this->view->translate('sos_details_by_sos_id'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('sos_id_not_exist'),'1',array(),'160');   
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_id_parameter_is_missing'),'1',array(),'158');   
                    }   
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function activateProfileAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];
        $sos_id                         = $decoded['sos_id'];    
        $is_profile_active              = $decoded['is_profile_active'];    
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $sosData                        = array();
        $receiverData                   = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {    
                $db = $this->db;
                $db->beginTransaction();
                              
                try 
                {
                    if($sos_id){ 
                        $sosRow      = $sosTable->getRowById($sos_id);
                       
                        if($sosRow) {                       
                            $data = array(
                                        'sos_id'               => $sosRow->sos_id,
                                        'is_profile_active'    => $is_profile_active
                                    );
                             
                            $sosRow->setFromArray($data);
                            $sosRow->save();    
                            
                            $sosRows = $sosTable->getOthersProfilesByUserId($userId,$sos_id);
                            if(!empty($sosRows)){
                                $is_profile_active = '0';
                                foreach ($sosRows as $sosRow){
                                    $sosData = array(
                                                'is_profile_active'  => $is_profile_active
                                            ); 

                                    $sosRow->setFromArray($sosData);
                                    $sosRow->save();
                                }
                            }
                            
                            $result = array('sos_id' => $sos_id);
                            
                            $db->commit();
                            $this->common->displayMessage($this->view->translate('profile_activated_success'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('sos_id_not_exist'),'1',array(),'160');   
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_id_parameter_is_missing'),'1',array(),'158');   
                    }   
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }

    public function getIncomingSosRequestAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosVolunteerHistoryTable       = new Application_Model_DbTable_WaSosVolunteerHistory();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $waTrackHistoryTable            = new Application_Model_DbTable_WaTrackHistory();
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $result                         = array();
        $sosReceiverArr                 = array();
        $sosVolunteerArr                = array();
        $emergency_userlist             = array();
                  
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try 
                {  
                    $sosReceiverRows  = $sosReceiverTable->getActiveSOSRowsByUserId($userId);
                    $sosVolunteerRows = $sosVolunteerHistoryTable->getRowByUserId($userId);
                    if(!empty($sosReceiverRows)){
                        foreach ($sosReceiverRows as $sosReceiverRow){
                            $sosReceiverArr[] = array(
                                                    'sos_id'        => $sosReceiverRow->sos_id,
                                                    'emergency_user_id' => $sosReceiverRow->user_id,
                                                    'userNickName'  => $sosReceiverRow->userFullName,
                                                    'userLatitude'  => $sosReceiverRow->userLatitude,
                                                    'userLongitude' => $sosReceiverRow->userLongitude
                                                );
                        }
                        foreach ($sosVolunteerRows as $sosVolunteerRow){
                            $sosVolunteerArr[] = array(
                                                    'sos_id'        => $sosVolunteerRow->sos_id,
                                                    'emergency_user_id'  => $sosVolunteerRow->user_id,
                                                    'userNickName'  => $sosVolunteerRow->userFullName,
                                                    'userLatitude'  => $sosVolunteerRow->userLatitude,
                                                    'userLongitude' => $sosVolunteerRow->userLongitude
                                              );
                        }
                                                  
                        $guardReceiverRows = $sosReceiverTable->getActiveWAGuardByUserId($userId);
                        
                        if(!empty($guardReceiverRows)){                           
                            $guard_receiver_userset    = array();
                            
                            foreach ($guardReceiverRows as $guardReceiverRow){
                                if(!empty($guardReceiverRow->receiver_id)){
                                    $guard_receiver_userset[]  = array(
                                                                    'sos_receiver_id' => $guardReceiverRow->sos_receiver_id,
                                                                    'emergency_user_id' => $guardReceiverRow->user_id,
                                                                    'userNickName'    => $guardReceiverRow->userFullName,
                                                                    'preset_message'  => $guardReceiverRow->preset_message
                                                               );
                                }
                            }               
                        }
                        
                        $trackReceiverRows = $sosReceiverTable->getActiveWATrackByUserId($userId);
                       
                        if(!empty($trackReceiverRows)){     
                            $track_receiver_userset    = array();
                             
                            foreach ($trackReceiverRows as $trackReceiverRow){
                                if(!empty($trackReceiverRow->receiver_id)){  
                                    $track_receiver_userset[] = array(
                                                                'sos_receiver_id' => $trackReceiverRow->sos_receiver_id,
                                                                'emergency_user_id' => $trackReceiverRow->user_id,
                                                                'userNickName'    => $trackReceiverRow->userFullName
                                                            );
                                }
                            }
                        }
                        $emergency_userlist = array_merge($sosReceiverArr,$sosVolunteerArr);
                        $result = array_merge(
                                    array('emergency_userlist' => $emergency_userlist),
                                    array('guard_receiver_userset' => $guard_receiver_userset),
                                    array('track_receiver_userset' => $track_receiver_userset)
                                );
                    }  
                   $this->common->displayMessage($this->view->translate('sos_activated_users'),'0',$result,'0');   
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function getIncomingSosRequestBySosIdAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosVolunteerHistoryTable       = new Application_Model_DbTable_WaSosVolunteerHistory();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosFilesTable                  = new Application_Model_DbTable_WaSosFiles();
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $deviceType                     = $decoded['device_type'];
        $userSecurity                   = $decoded['userSecurity'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        $sos_id                         = $decoded['sos_id'];
        
        $result                         = array();
        $sosVolunteerArr                = array();
        $sosReceiverArr                 = array();
        $sosFileArr                     = array();
        $images                         = array();
        $video                          = array();
        $audio                          = array();
                  
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try 
                {  
                    if($sos_id){ 
                        if($sosRow     = $sosTable->getRowById($sos_id)) {       
                            $sosReceiverRow  = $sosReceiverTable->getActiveSOSRowBySOSId($userId, $sos_id);
                            $sosVolunteerRow = $sosVolunteerHistoryTable->getRowBySOSId($userId, $sos_id);
                            $sosFilesRows    = $sosFilesTable->getSocialRowBySosId($sos_id);
                                                        
                            if(!empty($sosFilesRows)){
                                foreach($sosFilesRows as $sosFileRow){
                                    if($sosFileRow->file_type == 2){
                                        $images[] = $sosFileRow->file_url;
                                    }else if($sosFileRow->file_type == 1){
                                        $video[] = $sosFileRow->file_url;
                                    }else{
                                        $audio[] = $sosFileRow->file_url;
                                    }
                                }
                                $sosFileArr = array(
                                                'images' => $images,
                                                'video'  => $video,
                                                'audio'  => $audio,
                                            );
                            }   
                           
                            if(!empty($sosReceiverRow)){
                                $sosReceiverArr = array(
                                                    'sos_id'            => $sosReceiverRow->sos_id,
                                                    'user_id'           => $sosReceiverRow->user_id,
                                                    'userNickName'      => $sosReceiverRow->userFullName,
                                                    'userLatitude'      => $sosReceiverRow->userLatitude,
                                                    'userLongitude'     => $sosReceiverRow->userLongitude,
                                                    'emergency_message' => $sosReceiverRow->emergency_message                                      
                                                );
                            }
                           
                            if(!empty($sosVolunteerRow)){
                                $sosVolunteerArr = array(
                                                    'sos_id'            => $sosVolunteerRow->sos_id,
                                                    'user_id'           => $sosVolunteerRow->user_id,
                                                    'userNickName'      => $sosVolunteerRow->userFullName,
                                                    'userLatitude'      => $sosVolunteerRow->userLatitude,
                                                    'userLongitude'     => $sosVolunteerRow->userLongitude,
                                                    'emergency_message' => $sosVolunteerRow->emergency_message                                      
                                                );
                            }

                            $result = array_merge($sosReceiverArr,$sosVolunteerArr,array('attachment' => $sosFileArr));
                            
                            $this->common->displayMessage($this->view->translate('sos_activated_user_detail'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('sos_id_not_exist'),'1',array(),'160');   
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_id_parameter_is_missing'),'1',array(),'158');   
                    }   
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function getWaSosMedicalDetailsAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $sos_id                         = $decoded['sos_id'];    
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $result                         = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                  
                try 
                {
                    if($sos_id){ 
                        $sosRow    = $sosTable->getRowById($sos_id);
                       
                        if($sosRow) {                       
                            $result = array(
                                        'sos_id'                      => $sosRow->sos_id,
                                        'user_id'                     => $sosRow->user_id,
                                        'blood_group'                 => $sosRow->blood_group ? $sosRow->blood_group:"",
                                        'illnesses'                   => $sosRow->illnesses ? $sosRow->illnesses:"",
                                        'is_medication'               => $sosRow->is_medication,
                                        'medication_description'      => $sosRow->medication_description ? $sosRow->medication_description:"",
                                        'allergies'                   => $sosRow->allergies ? $sosRow->allergies:"",
                                        'is_organ_donations'          => $sosRow->is_organ_donations,
                                        'organ_donations_description' => $sosRow->organ_donations_description ? $sosRow->organ_donations_description:"",
                                    );
                            $this->common->displayMessage($this->view->translate('user_medical_details'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('sos_id_not_exist'),'0',array(),'160');   
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_id_parameter_is_missing'),'0',array(),'158');   
                    }   
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1', array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function receiverResponseAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosEventSendDetailsTable       = new Application_Model_DbTable_WaSosEventSendDetails();
        $sosEventResponseTable          = new Application_Model_DbTable_WaSosEventResponse();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];  
        $deviceLanguage                 = $decoded['deviceLanguage'];
        $sos_id                         = $decoded['sos_id'];  
        $response                       = $decoded['response'];
        
        $result                         = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {    
                $db = $this->db;
                $db->beginTransaction();
                         
                try 
                {  
                    if($sos_id){ 
                        $sosRow                 = $sosTable->getRowById($sos_id);
                        $sosEventSendDetailsRow = $sosEventSendDetailsTable->getRowBySosId($sos_id);
                        $sosEventResponseRow    = $sosEventResponseTable->getResponseBySosId($sos_id);
                        if(empty($response)){
                            $response = '0';
                        }
                     
                        if(($sosRow->is_send == 1) || ($sosEventSendDetailsRow->is_send == 1)) { 
                            if(count($sosEventResponseRow) == 0){
                                $data   = array(
                                            'object_id'        => $sosEventSendDetailsRow->sos_event_id,
                                            'sos_id'           => $sos_id,
                                            'receiver_id'      => $userId,
                                            'user_id'          => $sosRow->user_id,
                                            'response'         => $response ,
                                            'response_time'    => date('Y-m-d H:i:s')
                                        );

                                $sosEventResponseRow  = $sosEventResponseTable->createRow($data);
                                $event_response = $sosEventResponseRow->save();
                                      
                                $result = array('response_id' => $event_response,'response' => $response);
                                
                                $postData  = array(
                                                'user_id'      => $userId,
                                                'receiver_id'  => $sosRow->user_id,
                                                'sos_id'       => $sosRow->sos_id
                                           );
                                
                                $alert_type   = "wa_sos_receiver_response_request";
                                $full_name    = $userRow->userNickName; 
                                if($response == 1){
                                    $msg          = $this->view->translate('sos_receiver_coming_for_help');
                                    $message      = $full_name.' '.$this->view->translate('sos_receiver_coming_for_help');
                                }else{
                                    $msg          = $this->view->translate('sos_receiver_not_coming_for_help');
                                    $message      = $full_name.' '.$this->view->translate('sos_receiver_not_coming_for_help');
                                }
                                $this->getUserDeviceDetailsAction($postData, $msg, $alert_type); 

                                $userNotificationsTable->createNotification($userId, $sosRow->user_id, $message, Application_Model_DbTable_NotificationType::SOS_RECEIVER_RESPONSE);

                                $db->commit();
                                $this->common->displayMessage($this->view->translate('sos_receiver_response'),'0',$result,'0');
                            }else{
                                $this->common->displayMessage($this->view->translate('sos_receiver_already_given_response'),'1',array(),'162');
                            }
                        }else{
                            $this->common->displayMessage($this->view->translate('sos_id_not_exist'),'1',array(),'160');   
                        } 
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_id_parameter_is_missing'),'1',array(),'158');   
                    }   
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }   
    }

    public function getComingHelpUsersListAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEventSendDetailsTable       = new Application_Model_DbTable_WaSosEventSendDetails();
        $sosEventResponseTable          = new Application_Model_DbTable_WaSosEventResponse();
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];  
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $result                         = array();
        $type1_count                    = 0;
        $type2_count                    = 0;
        $type_count                     = 0;
                
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                  
                try 
                {
                    $sosSettingRow  = $sosSettingTable->getRowByUserId($userId);
                    if($sosSettingRow->is_sos_setup != 0 && $sosSettingRow->is_sos_active != 0){
                        $sosEventResponseRows = $sosEventResponseTable->getComingHelpUsersByUserId($userId);
                        if(count($sosEventResponseRows) > 0){
                            foreach ($sosEventResponseRows as $sosEventResponseRow){
                                $user_professions = $sosEventResponseRow['user_professions'];
                                if($user_professions == 1){
                                   $type1_count++; 
                                }else if($user_professions == 2){
                                   $type2_count++; 
                                }else{
                                    $type_count++; 
                                }
                            }
                           $result = array('doctor' => "$type1_count", 'law' => "$type2_count");
                        }else{ 
                            $result = array('doctor' => "0", 'law' => "0");
                        }  
                       $this->common->displayMessage($this->view->translate('sos_receiver_coming'),'0',$result,'0');
                    }else{
                        $this->common->displayMessage($this->view->translate('sos_not_activate'),'1',array(),'164');
                    }
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }   
    }
    
    public function activateWaGuardAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
      
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $sos_emergency_id               = $decoded['sos_emergency_id'];  
        $interval_time                  = $decoded['interval_time'];
        $preset_message                 = $decoded['preset_message'];    
        $pincode                        = $decoded['pincode']; 
        $receiver_userset               = $decoded['receiver_userset'];
        $receiver_emailset              = $decoded['receiver_emailset'];
        $receiver_phoneset              = $decoded['receiver_phoneset']; 
        $is_start                       = $decoded['is_start'];  
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $data                           = array();
        $receiverData                   = array();
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waSosRow = $sosTable->getActiveProfileByUserId($userId);
                    if(!empty($waSosRow)){
                        if($waSosRow->pincode == $pincode){
                            $addMinutes      = $interval_time.' minutes';
                            $event_send_time = date('Y-m-d H:i:s', strtotime("+$addMinutes"));
                            $creation_date   = date('Y-m-d H:i:s');
                            if(!empty($sos_emergency_id)){
                                $sosEmergencyRow = $sosEmergencyTable->getRowById($sos_emergency_id);
                                $creation_date   = $sosEmergencyRow->creation_date;
                            }

                            $data   = array(
                                        'sos_emergency_id'  => $sos_emergency_id,
                                        'user_id'           => $userId,
                                        'emergency_type'    => '1',
                                        'preset_message'    => $preset_message,
                                        'pincode'           => $pincode,
                                        'interval_time'     => $interval_time,
                                        'end_time'          => '',
                                        'creation_date'     => $creation_date,
                                        'modification_date' => date('Y-m-d H:i:s'),
                                        'is_start'          => $is_start,
                                        'is_send'           => '0',
                                        'is_status'         => '1'
                                    );

                            if(count($receiver_userset) > 0){  
                                if(!empty($sosEmergencyRow->sos_emergency_id)){
                                    $sosEmergencyRow   = $sosEmergencyRow->setFromArray($data);
                                    $sosId  = $sosEmergencyRow->save();

                                    $emergencyRow   = $sosEmergencySendDetailsTable->getRowByEmergencyId($sos_emergency_id);

                                    $emergencyData  = array(
                                                        'id'                => $emergencyRow->id,
                                                        'sos_emergency_id'  => $sosId,
                                                        'user_id'           => $userId,
                                                        'emergency_type'    => '1',
                                                        'event_send_time'   => $event_send_time,
                                                        'event_end_time'    => '',
                                                        'creation_date'     => $creation_date,
                                                        'modification_date' => date('Y-m-d H:i:s'),
                                                        'event_response'    => '2',
                                                        'event_send'        => '0',
                                                        'event_status'      => '1'
                                                    );

                                    $emergencyRow = $emergencyRow->setFromArray($emergencyData);
                                    $emergencyRow->save();

                                }else{
                                    $sosRow = $sosEmergencyTable->createRow($data);
                                    $sosId  = $sosRow->save();                        

                                    $emergencyData  = array(
                                                        'sos_emergency_id'  => $sosId,
                                                        'user_id'           => $userId,
                                                        'emergency_type'    => '1',
                                                        'event_send_time'   => $event_send_time,
                                                        'event_end_time'    => '',
                                                        'creation_date'     => $creation_date,
                                                        'modification_date' => date('Y-m-d H:i:s'),
                                                        'event_response'    => '2',
                                                        'event_send'        => '0',
                                                        'event_status'      => '1'
                                                    );

                                    $emergencyRow = $sosEmergencySendDetailsTable->createRow($emergencyData);
                                    $emergencyRow->save();
                                }

                                if(!empty($sosEmergencyRow->sos_emergency_id)){
                                    $sosReceiverTable->deleteEmergencyReceivers($sosEmergencyRow->sos_emergency_id);
                                }

                                if(count($receiver_userset) > 0){
                                    foreach($receiver_userset as $receiverId){   
                                        $receiverData   = array(
                                                            'sos_emergency_id' => $sosId,
                                                            'user_id'          => $userId,
                                                            'receiver_id'      => $receiverId,
                                                            'is_send'          => '0'
                                                        );
                                        if($receiverId != $userId) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }
                                if(count($receiver_emailset) > 0){
                                    foreach($receiver_emailset as $receiverEmail){  
                                        $receiverData = array(
                                                        'sos_emergency_id' => $sosId,
                                                        'user_id'          => $userId,
                                                        'receiver_email'   => $receiverEmail,
                                                        'is_send'          => '0'
                                                    );
                                        if($receiverId != $userId) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                } 
                                if(count($receiver_phoneset) > 0){
                                    foreach($receiver_phoneset as $receiverPhone){
                                        $receiverData = array(
                                                        'sos_emergency_id'  => $sosId,
                                                        'user_id'           => $userId,
                                                        'receiver_phone'    => $receiverPhone,
                                                        'is_send'           => '0'
                                                    );
                                        if($receiverId != $userId) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }

                              $result = array('wa_guard_id' => $sosId,'is_start' => $is_start);

                              $db->commit();
                              $this->common->displayMessage($this->view->translate('guard_activated_success'),'0',$result,'0');   

                            } else {
                                $this->common->displayMessage($this->view->translate('guard_receiver_not_exist'),'1',array(),'166');
                            }  
                        }else{
                          $this->common->displayMessage($this->view->translate('Your Pincode not match to WA-SOS'),'1',array(),'182');
                        }                       
                    }else{
                      $this->common->displayMessage($this->view->translate('You can not activate WA-Guard without WA-SOS profile'),'1',array(),'184');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function stopWaGuardAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
      
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $is_start                       = $decoded['is_start'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $data                           = array();
        $receiverData                   = array();
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waGuardRow = $sosEmergencyTable->isUserWaGuardExists($userId);
                    if(!empty($waGuardRow)){
                        if($waGuardRow->is_start != 2){
                            $data   = array(
                                        'user_id'     => $userId,
                                        'is_start'    => $is_start
                                    );
                            $waGuardRow = $waGuardRow->setFromArray($data);
                            $waGuardRow->save();

                            $sosEmergencySendRows = $sosEmergencySendDetailsTable->getWAGuardRowByUserId($userId);
                        
                            foreach ($sosEmergencySendRows as $sosEmergencySendRow){
                                $sosEmergencyData = array(
                                                       'modification_date' => date('Y-m-d H:i:s'),
                                                       'event_status'      => '0'
                                                   );
                                $sosEmergencyRow = $sosEmergencySendRow->setFromArray($sosEmergencyData);
                                $sosEmergencyRow->save();
                            }
                            
                            $result = array('is_start' => $is_start);
                        
                            $db->commit();
                            $this->common->displayMessage($this->view->translate('guard_deactivated_success'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('guard_already_stopped'),'1',array(),'168');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('guard_setting_not_exist'),'1',array(),'170');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function resetWaGuardAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $interval_time                  = $decoded['interval_time']; 
        $is_reset                       = $decoded['is_reset']; 
        $id                             = $decoded['id'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waGuardRow = $sosEmergencyTable->isUserWaGuardExists($userId);                    
                    if(!empty($waGuardRow)){                        
                        if($waGuardRow->is_start != 2){
                            $sosEmergencySendRow = $sosEmergencySendDetailsTable->getRowById($id);
                            if(empty($interval_time)){
                                $interval_time   = $waGuardRow->interval_time;  
                            }
                            $data = array('event_response' => $is_reset);
                            $sosEmergencyRow = $sosEmergencySendRow->setFromArray($data);
                            $sosEmergencyRow->save();
                            
                            $addMinutes          = $interval_time.' minutes';
                            $event_send_time     = date('Y-m-d H:i:s', strtotime("+$addMinutes"));

                            $emergencyData      = array(
                                                    'sos_emergency_id'  => $sosEmergencySendRow->sos_emergency_id,
                                                    'user_id'           => $userId,
                                                    'emergency_type'    => '1',
                                                    'event_send_time'   => $event_send_time,
                                                    'event_end_time'    => '',
                                                    'creation_date'     => date('Y-m-d H:i:s'),
                                                    'modification_date' => date('Y-m-d H:i:s'),
                                                    'event_response'    => '2',
                                                    'event_send'        => '0',
                                                    'event_status'      => '1'
                                                );

                            $emergencyRow = $sosEmergencySendDetailsTable->createRow($emergencyData);
                            $id = $emergencyRow->save();
                            
                            $result = array('id' => $id,'event_send_time' => $event_send_time);

                            $db->commit();
                            $this->common->displayMessage($this->view->translate('gaurd_reset_success'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('guard_already_stopped'),'1',array(),'168');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('guard_setting_not_exist'),'1',array(),'170');
                    }
                } catch (Exception $ex){
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }        
    }
    
    public function waGuardUserNotAliveResponseAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity']; 
        $response                       = $decoded['response'];
        $id                             = $decoded['id'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waGuardRow = $sosEmergencyTable->isUserWaGuardExists($userId);
                    
                    if(!empty($waGuardRow)){                   
                        if($waGuardRow->is_start != 2){     
                            $sosEmergencySendRow = $sosEmergencySendDetailsTable->getRowById($id);

                            $emergencyData  = array(
                                                'event_response'    => $response
                                            );

                            $emergencyRow = $sosEmergencySendRow->setFromArray($emergencyData);
                            $emergencyRow->save();
                            
                            $sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waGuardRow->sos_emergency_id);
                        
                            $alert_type = "wa_guard_receiver_request";                            
                            $total = 0;
                            foreach ($sosReceiversRows as $sosReceiversRow){   
                                $total = $total + 1;                         
                                if(!empty($sosReceiversRow->user_id)){ 
                                    $receiverRow  = $userTable->getRowById($sosReceiversRow->receiver_id);

                                    $message = $userRow->userNickName." ".$this->view->translate('send_guard_emergency_message');

                                    if($receiverRow && $receiverRow->isActive()){
                                        $data = array();
                                        $this->sendWAEmergencyNotificationsAction($sosReceiversRow->receiver_id, $alert_type, $message,$data);
                                        
                                        $userNotificationsTable->createNotification($userId, $sosReceiversRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_RECEIVER_RESPONSE);
                                    }
                                }
                          
                                if(!empty($receiverRow->userEmail))
                                {
                                    $params = array(
                                        'userNickName'      => $userRow->userNickName,
                                        'baseUrl'           => $this->baseUrl
                                    );
                                    $this->mail($params,'incoming_wa_guard.phtml',$receiverRow->userEmail,'Welcome To WA-app');
                                }

                                if(!empty($receiverRow->userPhone))
                                {
                                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                    $ios_app_link     = $this->common->get_tiny_url($this->ios_app_link);

                                    $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                    $this->common->sendSms_recommend($receiverRow->userPhone,$message);
                                }                                
                            }
                            $result = array('total WA-Guard receivers' => "$total");

                            $db->commit();
                            $this->common->displayMessage($this->view->translate('guard_user_not_alive_response'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('guard_already_stopped'),'1',array(),'168');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('guard_setting_not_exist'),'1',array(),'170');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }  
    }
    
    public function sendWAEmergencyNotificationsAction($user_id,$alert_type,$message,$data)
    { 
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        
        $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($user_id);            
        $userRow            = $userTable->getRowById($user_id);
        
        if(!empty($userLoginDeviceSet))
        {
            foreach ($userLoginDeviceSet as $loginDeviceRow)
            {
                if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                {
                    if($loginDeviceRow->userDeviceType == "iphone")
                    {
                        $payload['aps'] = array(
                                            'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message, 'event_id'   => $event_id
                                        );
                    }
                    else
                    {  
                        $resultData     =  array(
                                            'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                            'userId'     => $userRow->userId,
                                            'userName'   => $userRow->userNickName
                                        );
                        
                        if(isset($data['emergency_user_id'])){
                            $resultData = array_merge($resultData,array('emergency_user_id' => $data['emergency_user_id']));
                        }

                        $payload        = array(
                                            'message'   => $message,
                                            'type'      => $alert_type,
                                            'result'    => $resultData    
                                        );

                        $payload = json_encode($payload);  
                    }                   
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType,$loginDeviceRow->userDeviceToken,$payload);  
                }              
            }
        } 
    }
    
    public function activateWaTrackAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $waTrackHistoryTable            = new Application_Model_DbTable_WaTrackHistory();                
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $interval_time                  = $decoded['interval_time'];
        $end_time                       = $decoded['end_time'];  
        $pincode                        = $decoded['pincode']; 
        $receiver_userset               = $decoded['receiver_userset'];
        $receiver_emailset              = $decoded['receiver_emailset'];
        $receiver_phoneset              = $decoded['receiver_phoneset'];
        $userLatitude                   = $decoded['userLatitude'];
        $userLongitude                  = $decoded['userLongitude'];   
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $data                           = array();
        $receiverData                   = array();
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waSosRow = $sosTable->getActiveProfileByUserId($userId);
                    if(!empty($waSosRow)){
                        if($waSosRow->pincode == $pincode){
                            $addMinutes  = $interval_time.' minutes';
                            $event_send_time = date('Y-m-d H:i:s', strtotime("+$addMinutes"));
                            $event_end_time = '';
                            if(!empty($end_time)){   
                                $addMinutes  = $end_time.' minutes';
                                $event_end_time = date('Y-m-d H:i:s', strtotime("+$addMinutes"));
                            }
                            $data   = array(
                                        'user_id'           => $userId,
                                        'emergency_type'    => '2',
                                        'pincode'           => $pincode,
                                        'interval_time'     => $interval_time,
                                        'end_time'          => $end_time,
                                        'creation_date'     => date('Y-m-d H:i:s'),
                                        'modification_date' => date('Y-m-d H:i:s'),
                                        'is_send'           => '0',
                                        'is_start'          => '1',
                                        'is_status'         => '1'
                                    );

                            $waTrackRow = $sosEmergencyTable->isUserWaTrackExists($userId);

                            if(count($receiver_userset) > 0){      
                                if(count($waTrackRow) > 0){
                                    $sosRow = $waTrackRow->setFromArray($data);
                                    $sosId  = $sosRow->save();

                                    if(!empty($userLatitude) && !empty($userLongitude)) {                                
                                        $waTrackHistoryTable->deleteTrackLocation($userId);
                                    }
                                    $sosReceiverTable->deleteEmergencyReceivers($waTrackRow->sos_emergency_id); 
                                }else{
                                    $sosRow = $sosEmergencyTable->createRow($data);
                                    $sosId  = $sosRow->save();                                  
                                }

                                if(!empty($userLatitude) && !empty($userLongitude)) {
                                    $trackHistoryData   = array(
                                                            'object_id'    => $sosId,
                                                            'user_id'      => $userId,
                                                            'latitude'     => $userLatitude,
                                                            'longitude'    => $userLongitude,
                                                            'is_deleted'   => '0',
                                                            'creation_date'=> date('Y-m-d H:i:s')
                                                        );

                                    $waTrackHistoryRow = $waTrackHistoryTable->createRow($trackHistoryData);
                                    $waTrackHistoryRow->save();  
                                }

                                $emergencyData  = array(
                                                    'sos_emergency_id'  => $sosId,
                                                    'user_id'           => $userId,
                                                    'emergency_type'    => '2',
                                                    'event_send_time'   => $event_send_time,
                                                    'event_end_time'    => $event_end_time,
                                                    'creation_date'     => date('Y-m-d H:i:s'),
                                                    'modification_date' => date('Y-m-d H:i:s'),
                                                    'event_response'    => '2',
                                                    'event_send'        => '0',
                                                    'event_status'      => '1'
                                                );
                                if(count($waTrackRow) > 0){        
                                    $emergencyRow = $sosEmergencySendDetailsTable->getWATrackByUserId($userId);
                                    $emergencyRow = $emergencyRow->setFromArray($emergencyData);
                                    $emergencyRow->save();
                                }else{
                                    $emergencyRow = $sosEmergencySendDetailsTable->createRow($emergencyData);
                                    $emergencyRow->save();
                                }

                                if(count($receiver_userset) > 0){
                                    $alert_type = "wa_track_receiver_request";
                                    foreach($receiver_userset as $receiverId) {   
                                        $receiverData = array(
                                                        'sos_emergency_id' => $sosId,
                                                        'user_id'          => $userId,
                                                        'receiver_id'      => $receiverId,
                                                        'is_send'          => '0'
                                                    );

                                        if($receiverId != $userId) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save(); 
                                        }
                                    }
                                }
                                
                                if(count($receiver_emailset) > 0){
                                    foreach($receiver_emailset as $receiverEmail){  
                                        $receiverData  = array(
                                                            'sos_emergency_id' => $sosId,
                                                            'user_id'          => $userId,
                                                            'receiver_email'   => $receiverEmail,
                                                            'is_send'          => '0'
                                                       );
                                        if($receiverEmail != $userRow->userEmail) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save(); 
                                        }
                                    }
                                } 
                                
                                if(count($receiver_phoneset) > 0){
                                    foreach($receiver_phoneset as $receiverPhone) {  
                                        $receiverData = array(
                                                            'sos_emergency_id'  => $sosId,
                                                            'user_id'           => $userId,
                                                            'receiver_phone'    => $receiverPhone,
                                                            'is_send'           => '0'
                                                      );
                                        if($receiverPhone != $userRow->userPhone) {
                                            $sosReceiverRow = $sosReceiverTable->createRow($receiverData);
                                            $sosReceiverRow->save();  
                                        }
                                    }
                                }

                                $result = array('wa_track_id' => $sosId);

                                $db->commit();
                                $this->common->displayMessage($this->view->translate('track_activated_success'),'0',$result,'0');   
                            } else {
                                $this->common->displayMessage($this->view->translate('track_receiver_not_exist'),'1',array(),'172');
                            }                             
                        }else{
                          $this->common->displayMessage($this->view->translate('You Pincode not match to WA-SOS'),'1',array(),'186');
                        }                       
                    }else{
                      $this->common->displayMessage($this->view->translate('You can not create WA-Track without WA-SOS profile'),'1',array(),'188');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function stopWaTrackAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();   
      
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $is_start                       = $decoded['is_start'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $data                           = array();
        $receiverData                   = array();
       
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waTrackRow = $sosEmergencyTable->isUserWaTrackExists($userId);
                    if(!empty($waTrackRow)){
                        if($waTrackRow->is_start != 2){
                            $data   = array(
                                        'user_id'     => $userId,
                                        'is_start'    => $is_start
                                    );
                            $waTrackRow = $waTrackRow->setFromArray($data);
                            $waTrackRow->save();
                           
                            $sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waTrackRow->sos_emergency_id);
                            $alert_type = "wa_track_receiver_request";
                            foreach ($sosReceiversRows as $sosReceiversRow){              
                                if(!empty($sosReceiversRow->user_id)){ 
                                    $receiverRow  = $userTable->getRowById($sosReceiversRow->receiver_id);

                                    $message = $userRow->userNickName." ".$this->view->translate('stop_track_emergency');

                                    if($receiverRow && $receiverRow->isActive()){
                                        $data1 = array('emergency_user_id' => $sosReceiversRow->receiver_id);
                                       
                                        $this->sendWAEmergencyNotificationsAction($sosReceiversRow->receiver_id, $alert_type, $message, $data1);
                                    }
                                }
                          
                                if(!empty($receiverRow->userEmail)){
                                    $params = array(
                                        'userNickName'      => $userRow->userNickName,
                                        'baseUrl'           => $this->baseUrl
                                    );
                                    //$this->mail($params,'stop_wa_track.phtml',$receiverRow->userEmail,'Welcome To WA-app');
                                }

                                if(!empty($receiverRow->userPhone)){
                                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                    $ios_app_link     = $this->common->get_tiny_url($this->ios_app_link);

                                    $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                    $this->common->sendSms_recommend($receiverRow->userPhone,$message);
                                }
                            }
                            
                            $result = array('is_start' => $is_start);
                        
                            $db->commit();
                            $this->common->displayMessage($this->view->translate('track_deactivated_success'),'0',$result,'0');   
                        }else{
                            $this->common->displayMessage($this->view->translate('track_already_stopped'),'1',array(),'176');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('track_setting_not_exist'),'1',array(),'174');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function waTrackChangeLocationAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $waTrackHistoryTable            = new Application_Model_DbTable_WaTrackHistory();                
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity'];
        $userLatitude                   = $decoded['userLatitude'];
        $userLongitude                  = $decoded['userLongitude'];   
        $deviceLanguage                 = $decoded['deviceLanguage'];
               
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waTrackRow = $sosEmergencyTable->isUserWaTrackExists($userId);
                    if(count($waTrackRow) > 0){
                        $isUserLatLongRow = $waTrackHistoryTable->isUserWaTrackLatLongExists($userId,$userLatitude,$userLongitude);
                        if(count($isUserLatLongRow) < 1){
                            $trackHistoryData   = array(
                                                    'object_id'    => $waTrackRow->sos_emergency_id,
                                                    'user_id'      => $userId,
                                                    'latitude'     => $userLatitude,
                                                    'longitude'    => $userLongitude,
                                                    'creation_date'=> date('Y-m-d H:i:s')
                                                );

                            $waTrackHistoryRow = $waTrackHistoryTable->createRow($trackHistoryData);
                            $waTrackHistoryRow->save();  
                        }
                        
                        $sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waTrackRow->sos_emergency_id);
                        $alert_type = "wa_track_receiver_request";                            
                        $total = 0;
                        foreach ($sosReceiversRows as $sosReceiversRow){   
                            $total = $total + 1;                         
                            if(!empty($sosReceiversRow->user_id)){ 
                                $receiverRow  = $userTable->getRowById($sosReceiversRow->receiver_id);

                                $message = $userRow->userNickName." ".$this->view->translate('send_track_emergency_message');

                                if($receiverRow && $receiverRow->isActive()){
                                    $data = array('emergency_user_id' => $sosReceiversRow->receiver_id);
                                    $this->sendWAEmergencyNotificationsAction($sosReceiversRow->receiver_id, $alert_type, $message, $data);
                                }
                            }
                            if($receiverRow->userPhone)
                            {
                                $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                $ios_app_link     = $this->common->get_tiny_url($this->ios_app_link);

                                $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                $this->common->sendSms_recommend($receiverRow->userPhone,$message);
                            }
                        }
                        $result = array('total WA-Track receivers' => $total);

                        $db->commit();
                        $this->common->displayMessage($this->view->translate('track_activated_success'),'0',$result,'0');   

                    } else {
                        $this->common->displayMessage($this->view->translate('track_setting_not_exist'),'1',array(),'174');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        } 
    }
    
    public function getWaTrackHistoryAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $sosTable                       = new Application_Model_DbTable_WaSos();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $waTrackHistoryTable            = new Application_Model_DbTable_WaTrackHistory();                
       
        $decoded                        = $this->common->Decoded();
        $userId                         = $decoded['userId'];
        $userDeviceId                   = $decoded['userDeviceId'];
        $userDeviceToken                = $decoded['userDeviceToken'];
        $userSecurity                   = $decoded['userSecurity']; 
        $emergencyUserId                = $decoded['emergency_user_id'];
        $deviceLanguage                 = $decoded['deviceLanguage'];
        
        $result                         = array();
        
        $this->user->setLanguage($deviceLanguage);
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                $db = $this->db;
                $db->beginTransaction();
                     
                try 
                {   
                    $waTrackRow = $sosEmergencyTable->isUserWaTrackExists($emergencyUserId);
                    if(count($waTrackRow) > 0){
                        $waTrackHistoryRows = $waTrackHistoryTable->getRowByUserId($emergencyUserId);
                        
                        foreach ($waTrackHistoryRows as $waTrackHistoryRow){   
                           $data[]  = array(
                                        'userLatitude'  => $waTrackHistoryRow->latitude,
                                        'userLongitude' => $waTrackHistoryRow->longitude,
                                        'creation_date' => $waTrackHistoryRow->creation_date
                                    );
                        }
                        $result = $data;

                        $db->commit();
                        $this->common->displayMessage($this->view->translate('track_user_history'),'0',$result,'0');   

                    } else {
                        $this->common->displayMessage($this->view->translate('track_setting_not_exist'),'1',array(),'174');
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               exit();
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        } 
    }
    
    public function testAction(){
        $message = "Welcome to WA-app SMS API testing Message";
        $receiver_phone = "+919716573164";
       // $this->common->sendSms_recommend($userPhoneNumber, $message);
        $this->common->sendSms_recommend($receiver_phone,$message);
        echo 'called';
        exit();
    }
    
    public function fbTestAction(){
       $this->common->fbAccess();
       //exit;
    }
    
    public function fbTest1Action(){
        $this->common->getfbLoginToken();
        exit;
    }
             
    public function twAction(){
        $sosTable = new Application_Model_DbTable_WaSos();
        $sosRow   = $sosTable->getRowById(3);
        $image = 'http://52.4.193.184/new_images/ban_pic1.png';
       echo file_get_contents($image); 
        //cho mb_convert_encoding($data, "EUC-JP", "auto");
        exit;
        //echo '<pre>';
        //print_r($sosRow); exit;
        $this->twitterBroadcastAction($sosRow);
        echo 'called';
        exit();
    }
}