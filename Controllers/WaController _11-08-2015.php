<?php

class WaController extends My_Controller_Abstract
{    
    public function preDispatch() 
    {
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
    
    public function indexAction()
    {
	echo date('Y-m-d H:i:s'); exit; 
    }

    public function createGoAction()
    { 
        $userTable               = new Application_Model_DbTable_Users();
        $waTable                 = new Application_Model_DbTable_Wa();
        $waReceiverTable         = new Application_Model_DbTable_WaReceiver();
        
        $decoded                 = $this->common->Decoded();  
        $userId                  = $this->getRequest()->getPost('userId','');
        $userDeviceId            = $this->getRequest()->getPost('userDeviceId','');
        $userDeviceToken         = $this->getRequest()->getPost('userDeviceToken','');
        $device_type             = $this->getRequest()->getPost('device_type','');     
        $userSecurity            = $this->getRequest()->getPost('userSecurity','');
        $wa_id                   = $this->getRequest()->getPost('wa_id','');
        $message_title           = $this->getRequest()->getPost('message_title','');
        $delivery_date           = $this->getRequest()->getPost('delivery_date','');
        $delivery_time           = $this->getRequest()->getPost('delivery_time','');
        $local_time              = $this->getRequest()->getPost('local_time','');
        $is_annually             = $this->getRequest()->getPost('is_annually','0');
        $receiver_userset        = $this->getRequest()->getPost('receiver_userset',''); 
        $receiver_emailset       = $this->getRequest()->getPost('receiver_emailset','');   
        $receiver_email_phoneset = $this->getRequest()->getPost('receiver_email_phoneset','');        
        $receiver_email_phoneset = json_decode($receiver_email_phoneset);        
        $is_chat_message         = $this->getRequest()->getPost('is_chat_message','0');        
        $text                    = $this->getRequest()->getPost('text','');
        $exist_image             = $this->getRequest()->getPost('exist_image','');
        $exist_audio             = $this->getRequest()->getPost('exist_audio','');
        $exist_video             = $this->getRequest()->getPost('exist_video','');   
        $deviceLanguage          = $this->getRequest()->getPost('deviceLanguage','');         
        $bucket_name             = "wa-messages";
        $folder_name             = "wa-later";
        
        $this->user->setLanguage($deviceLanguage);  
    
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                if(!empty($receiver_userset)) {  
                    $creation_date = date("Y-m-d H:i:s");
                    $waRow = false;
                    $is_existing_wa = false;                
                    if($waRow = $waTable->getRowById($wa_id)){
                        $is_existing_wa = true;
                        $creation_date = $waRow->creation_date;
                    }         
                    if(empty($waRow) || $waRow->is_send == 0) {
                        $date_time   = urldecode($delivery_date)." ".urldecode($delivery_time);                
                        $date_time   = date("Y-m-d H:i:s",  strtotime($date_time));
                        $utc_time    = $this->convertIntoUtcTime($date_time, $local_time);                        
                        $data        = array(
                                        'user_id'                   => $userId,
                                        'type'                      => ($is_chat_message)?Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE:Application_Model_DbTable_Wa::WA_TYPE_GO,
                                        'message_title'             => urldecode($message_title),
                                        'local_time'                => urldecode($local_time),
                                        'creation_date'             => $creation_date,
                                        'delivery_date'             => urldecode($date_time),  
                                        'first_delivery_date_utc'   => $utc_time,
                                        'last_delivery_date_utc'    => $utc_time,
                                        'modification_date'         => date("Y-m-d H:i:s"),
                                        'is_annually'               => urldecode($is_annually),
                                        'text'                      => urldecode($text) 
                                    );     

                        $db = $this->db;
                        $db->beginTransaction();                
                        try
                        {
                            if(!empty($_FILES['image']) && count($_FILES['image'])){
                                $response = $this->common->uploadS3Bucket($bucket_name,$_FILES["image"],$folder_name);
                                if(isset($response['new_file_name'])){
                                    $file_path = $response['new_file_name'];
                                    $data      = array_merge($data,array(
                                                    'image_link'  => $file_path
                                                ));
                                }
                            }else{
                                if($waRow && $waRow->image_link && empty($exist_image)){
                                    $waRow->image_link = "";
                                }
                            }

                            if(!empty($_FILES['audio']) && count($_FILES['audio'])){                        
                                $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES["audio"],$folder_name);                        
                                if(isset($response['new_file_name'])){
                                    $file_path = $response['new_file_name'];
                                    $data   = array_merge($data,array(
                                                'audio_link'    => $file_path
                                            ));
                                }
                            }else{
                                if($waRow && $waRow->audio_link && empty($exist_audio)){
                                    $waRow->audio_link = "";
                                }
                            }

                            if(!empty($_FILES['video']) && count($_FILES['video'])){
                                $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES["video"],$folder_name);                        
                                if(isset($response['new_file_name'])){
                                    $file_path = $response['new_file_name'];
                                    /*$parse_file_name = explode(".", $_FILES['video']['name']);
                                    $thumbnail_path = '/images/'.$parse_file_name['0']."_thumb.jpg";
                                    $video_path = $file_path;
                                    $this->createThumbnail($video_path, $thumbnail_path);  ,
                                    'thumbnail_link'    => $thumbnail_path */
                                    $data   = array_merge($data,array(
                                                'video_link'  => $file_path   
                                            ));
                                }
                            }else{
                                if($waRow && $waRow->video_link && empty($exist_video)){
                                    $waRow->video_link = "";
                                }
                            }

                            if($waRow){
                                $waRow->setFromArray($data);
                                $waRow->save();
                            }else{
                                $waRow = $waTable->save($data);
                                $wa_id = $waRow;
                            } 
                            
                            if($wa_id){
                                if($is_existing_wa){
                                   $waReceiverTable->deleteReceivers($wa_id);
                                }                         
                                //$receiver_userset = json_decode(urldecode($receiver_userset),true);
                                foreach($receiver_userset as $receiverId){
                                    if($is_chat_message){
                                        $data   = array(
                                                    'wa_id'                 => $wa_id,
                                                    'receiver_quickblox_id' => $receiverId
                                                );
                                    }else{
                                        $data   = array(
                                                    'wa_id'         => $wa_id,
                                                    'receiver_id'   => $receiverId
                                                );
                                    }
                                    if($receiverId != $userId){
                                        $waReceiverRow = $waReceiverTable->createRow($data);
                                        $waReceiverRow->save();
                                    }
                                }
                                if(!empty($receiver_emailset)){
                                    //$receiver_emailset = json_decode(urldecode($receiver_emailset),true);
                                    foreach ($receiver_emailset as $receiverEmail){
                                        $data   = array(
                                                    'wa_id'          => $wa_id,
                                                    'receiver_email' => $receiverEmail
                                                );
                                        $waReceiverRow = $waReceiverTable->createRow($data);
                                        $waReceiverRow->save();
                                    }
                                }
                                if(!empty($receiver_email_phoneset)) {
                                    //$receiver_email_phoneset = json_decode(urldecode($receiver_email_phoneset),true);
                                    foreach ($receiver_email_phoneset as $receiverData){
                                        $waUserRow = false;
                                        if($receiverData->email){
                                            $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $receiverData->email);
                                        }
                                        if(!$waUserRow &&  $receiverData->phone){
                                            $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $receiverData->phone);
                                        }
                                        $data = array(
                                                'wa_id'             =>  $wa_id,
                                                'receiver_name'     =>  $receiverData->name,
                                                'receiver_email'    =>  $receiverData->email,
                                                'receiver_phone'    =>  $receiverData->phone,
                                                'receiver_id'       =>  $waUserRow ? $waUserRow->userId:new Zend_Db_Expr("NULL")
                                            );

                                        if(!$waUserRow || ($waUserRow->userId != $userId) ){
                                            $waReceiverRow = $waReceiverTable->createRow($data);
                                            $waReceiverRow->save();
                                        }                            
                                    }
                                }
                            } 
                        }catch(Exception $ex){
                            $db->rollBack();
                            $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                        }
                      $db->commit();               
                      $this->common->displayMessage($this->view->translate('wa-later_success'),"0",array(),"0"); 
                    }else{
                        $this->common->displayMessage($this->view->translate('wa-later_not_update'),"1",array(),"24");
                    }  
                }else{
                    $this->common->displayMessage($this->view->translate('wa_receiver_not_exist'),"1",array(),"32");
                }             
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }            
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
       
    /**
    *  Create Wa event  
    */    
    public function createEventAction()
    {   
        $userTable               = new Application_Model_DbTable_Users();
        $waTable                 = new Application_Model_DbTable_Wa();
        $waReceiverTable         = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable          = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable        = new Application_Model_DbTable_WaEventSendDetails();
        
        $decoded                 = $this->common->Decoded(); 
        $userId                  = $this->getRequest()->getPost('userId','');
        $userDeviceId            = $this->getRequest()->getPost('userDeviceId','');
        $userDeviceToken         = $this->getRequest()->getPost('userDeviceToken','');
        $userSecurity            = $this->getRequest()->getPost('userSecurity','');
        $device_type             = $this->getRequest()->getPost('device_type','');
        $wa_id                   = $this->getRequest()->getPost('wa_id','');
        $message_title           = $this->getRequest()->getPost('message_title','');
        $receiver_userset        = $this->getRequest()->getPost('receiver_userset','');
        $receiver_emailset       = $this->getRequest()->getPost('receiver_emailset',''); 
        $receiver_trusteeset     = $this->getRequest()->getPost('receiver_trusteeset','');
        $owner_vital_check       = $this->getRequest()->getPost('owner_vital_check','');
        $trustee_vital_check     = $this->getRequest()->getPost('trustee_vital_check','');  
        $vital_alert_count       = $this->getRequest()->getPost('vital_alert_count','');                
        $text                    = $this->getRequest()->getPost('text','');
        $exist_image             = $this->getRequest()->getPost('exist_image','');
        $exist_audio             = $this->getRequest()->getPost('exist_audio','');
        $exist_video             = $this->getRequest()->getPost('exist_video','');
        $deviceLanguage          = $this->getRequest()->getPost('deviceLanguage','');         
        $bucket_name             = "wa-messages";
        $folder_name             = "wa-event"; 
       
        $this->user->setLanguage($deviceLanguage);  
                             
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                if(!empty($receiver_userset)) {  
                    if(!empty($receiver_trusteeset)) {  
                        $waRow = false;
                        $is_existing_wa = false;

                        if($wa_id){
                            $is_existing_wa = true;
                            $waRow = $waTable->getRowById($wa_id);
                            $currentTime = $waRow->creation_date;
                        }
                        if(empty($waRow) || $waRow->is_send == 0) 
                        {
                            $currentTime             = date("Y-m-d H:i:s");
                            $owner_vital_check       = json_decode(urldecode($owner_vital_check),true); 
                            $vital_check_set         = $this->convertDateAction($currentTime,$owner_vital_check);
                            $count_vital_check_set   = count($vital_check_set);
                            $vital_check_last_date   = end($vital_check_set);
                            $trustee_vital_time      = json_decode(urldecode($trustee_vital_check),true);
                            $trustee_vital_check     = $this->convertDateAction($vital_check_last_date, $trustee_vital_time);

                            if($vital_alert_count == $count_vital_check_set)
                            {
                                $data   = array(
                                            'user_id'               => $userId,
                                            'type'                  => Application_Model_DbTable_Wa::WA_TYPE_EVENT,
                                            'message_title'         => $message_title,
                                            'creation_date'         => $currentTime,
                                            'modification_date'     => $currentTime,
                                            'text'                  => $text
                                        );

                                $db = $this->db;
                                $db->beginTransaction();      
                                
                                try
                                {
                                    if(!empty($_FILES['image']) && count($_FILES['image'])){
                                        $response = $this->common->uploadS3Bucket($bucket_name,$_FILES["image"],$folder_name);
                                        if(isset($response['new_file_name'])){
                                           $file_path = $response['new_file_name'];                                
                                           $data   = array_merge($data,array(
                                                       'image_link'    => $file_path
                                                   ));
                                        }
                                    }else{
                                        if($waRow && $waRow->image_link && empty($exist_image)){
                                            $waRow->image_link = "";
                                        }
                                    }

                                    if(!empty($_FILES['audio']) && count($_FILES['audio'])){                        
                                        $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES['audio'],$folder_name);                        
                                        if(isset($response['new_file_name'])){
                                            $file_path = $response['new_file_name'];
                                            $data = array_merge($data,array(
                                                'audio_link'    => $file_path
                                            ));
                                        }
                                    }else{
                                        if($waRow && $waRow->audio_link && empty($exist_audio)){
                                            $waRow->audio_link = "";
                                        }
                                    }

                                    if(!empty($_FILES['video']) && count($_FILES['video'])){
                                        $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES["video"],$folder_name);                        
                                        if(isset($response['new_file_name'])){
                                            $file_path = $response['new_file_name'];
                                          /*$parse_file_name = explode(".", $response['new_file_name']);
                                            $thumbnail_path = '/images/'.$parse_file_name['0']."_thumb.jpg";
                                            $video_path = '/'.$file_path;
                                            $this->createThumbnail($video_path, $thumbnail_path);
                                            'thumbnail_link'    => $thumbnail_path   */
                                            $data   = array_merge($data,array(
                                                        'video_link'        => $file_path   
                                                    ));                               
                                        }else{
                                            if($waRow && $waRow->video_link && empty($exist_video)){
                                                $waRow->video_link = "";
                                            }
                                        }   
                                    }

                                    if($waRow){
                                        $waRow->setFromArray($data);
                                        $waRow->save();
                                    }else{
                                       // $waRow = $waTable->createRow($data);
                                        $waRow = $waTable->save($data);
                                        $wa_id = $waRow;
                                    }   

                                    if($is_existing_wa){
                                        $waEventSendTable->deleteAllEventByWAId($userId,$wa_id);
                                        $waReceiverTable->deleteReceivers($wa_id);                               
                                        $waTrusteeTable->deleteWaReceivers($userId,$wa_id);
                                    }  
                                    $addDays = '180 days';
                                    $event_expiry_date = date("Y-m-d H:i:s",strtotime("+$addDays", strtotime($currentTime)));
                                    $eventSendDetailDataArr = array(       
                                                                  'wa_id'              => $wa_id,
                                                                  'user_id'            => $userId,
                                                                  'creation_date'      => $currentTime,
                                                                  'vital_check_type'   => Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT,
                                                                  'event_expiry_date'  => $event_expiry_date,
                                                                  'is_status'          => 0, 
                                                                  'event_status'       => '1'
                                                              );
                                    $count = 0;                   
                                    if(!empty($vital_check_set)) {
                                        foreach ($vital_check_set as $row) { 
                                                $eventSendDetailData[]  = array_merge($eventSendDetailDataArr,array('usertype'=>'1','event_send_date' => $row,'event_vital_value'=> $owner_vital_check[$count++]));
                                        }
                                        $eventSendDetailData[] = array_merge($eventSendDetailDataArr,array('usertype'=>'2','event_send_date' => $trustee_vital_check[0],'event_vital_value'=>  $trustee_vital_time[0]));
                                    }                   
                                    $eventSendDetailRow    = false; 
                                    (count($vital_check_set)>0)?$this->InsertMultipleRows('wa_event_send_details', $eventSendDetailData):"";

                                    if($wa_id){                                 
                                        if(!empty($receiver_userset))
                                        {
                                            $receiver_userset = json_decode(urldecode($receiver_userset),true); 
                                            foreach ($receiver_userset as $receiverId)
                                            {
                                                $data = array(
                                                    'wa_id'         => $wa_id,
                                                    'receiver_id'   => $receiverId
                                                );
                                                if($receiverId != $userId){
                                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                                    $waReceiverRow->save();
                                                }
                                            }
                                        } 
                                        if(!empty($receiver_emailset)){
                                            $receiver_emailset = json_decode(urldecode($receiver_emailset),true);
                                            foreach ($receiver_emailset as $receiverEmail){
                                                $data   = array(
                                                            'wa_id'          => $wa_id,
                                                            'receiver_email' => $receiverEmail
                                                        );
                                                $waReceiverRow = $waReceiverTable->createRow($data);
                                                $waReceiverRow->save();
                                            }
                                        }
                                        if(!empty($receiver_trusteeset)) 
                                        {
                                            $receiver_trusteeset = json_decode(urldecode($receiver_trusteeset),true); 
                                            foreach ($receiver_trusteeset as $receiverId)
                                            {
                                                $waTrusteeData  = array(
                                                                    'wa_id'        => $wa_id,
                                                                    'user_id'      => $userId,
                                                                    'receiver_id'  => $receiverId
                                                                );
                                                if($receiverId != $userId){
                                                    $waTrusteeRow = $waTrusteeTable->createRow($waTrusteeData);
                                                    $waTrusteeRow->save();
                                                }
                                            }
                                        }                       
                                    } 
                                }catch(Exception $ex){
                                    $db->rollBack();
                                    $this->common->displayMessage($ex->getMessage(),"1",array(),"12");
                                }
                              $db->commit();
                              $this->common->displayMessage($this->view->translate('wa-event_success'),"0",array(),"0");                     
                            }else{
                                $this->common->displayMessage($this->view->translate('vital_check_count'), '1', array(), '116');
                            } 
                        }else{
                            $this->common->displayMessage($this->view->translate('wa-event_not_update'),"1",array(),"34");
                        }
                    }else{
                        $this->common->displayMessage($this->view->translate('wa_trustee_not_exist'), '1', array(), '30');
                    } 
                }else{
                    $this->common->displayMessage($this->view->translate('wa_receiver_not_exist'), '1', array(), '32');
                }  
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }            
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
    
   /**
    *  Create Wa after  
   */
    public function createAfterAction()
    {        
        $userTable              = new Application_Model_DbTable_Users();
        $waTable                = new Application_Model_DbTable_Wa();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable       = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $decoded                 = $this->common->Decoded();    
        $userId                  = $this->getRequest()->getPost('userId','');
        $userDeviceId            = $this->getRequest()->getPost('userDeviceId','');
        $userDeviceToken         = $this->getRequest()->getPost('userDeviceToken','');
        $device_type             = $this->getRequest()->getPost('device_type');
        $userSecurity            = $this->getRequest()->getPost('userSecurity','');
        $wa_id                   = $this->getRequest()->getPost('wa_id','');
        $message_title           = $this->getRequest()->getPost('message_title','');
        $receiver_userset        = $this->getRequest()->getPost('receiver_userset','');
        $receiver_emailset       = $this->getRequest()->getPost('receiver_emailset',''); 
        $receiver_trusteeset     = $this->getRequest()->getPost('receiver_trusteeset','');
        $owner_vital_check       = $this->getRequest()->getPost('owner_vital_check','');
        $trustee_vital_check     = $this->getRequest()->getPost('trustee_vital_check','');  
        $vital_alert_count       = $this->getRequest()->getPost('vital_alert_count','');  
        $text                    = $this->getRequest()->getPost('text','');
        $exist_image             = $this->getRequest()->getPost('exist_image','');
        $exist_audio             = $this->getRequest()->getPost('exist_audio','');
        $exist_video             = $this->getRequest()->getPost('exist_video','');
        $deviceLanguage          = $this->getRequest()->getPost('deviceLanguage',''); 
        $bucket_name             = "wa-messages";
        $folder_name             = "wa-after";         
        
        $this->user->setLanguage($deviceLanguage);  

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {   
                if(!empty($receiver_userset)) {  
                    if(!empty($receiver_trusteeset)) {  
                        $creation_date = date("Y-m-d H:i:s");       
                        $waRow = false;
                        $is_existing_wa = false;

                        if($wa_id){
                            $is_existing_wa = true;
                            $waRow = $waTable->getRowById($wa_id);
                            $creation_date = $waRow->creation_date;
                        }
                        if(empty($waRow) || $waRow->is_send == 0) 
                        {    
                            $currentTime             = date("Y-m-d H:i:s");
                            $owner_vital_check       = json_decode(urldecode($owner_vital_check),true); 
                            $vital_check_set         = $this->convertDateAction($currentTime,$owner_vital_check);
                            $count_vital_check_set   = count($vital_check_set);
                            $vital_check_last_date   = end($vital_check_set);
                            $trustee_vital_time      = json_decode(urldecode($trustee_vital_check),true);
                            $trustee_vital_check     = $this->convertDateAction($vital_check_last_date, $trustee_vital_time);
                            
                            if($vital_alert_count == $count_vital_check_set)
                            {
                                $data   = array(
                                            'user_id'               => $userId,
                                            'type'                  => Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE,
                                            'message_title'         => $message_title,
                                            'creation_date'         => $creation_date,
                                            'modification_date'     => date("Y-m-d H:i:s"),                                              
                                            'is_annually'           => 0,
                                            'text'                  => $text
                                        );

                                $db = $this->db;
                                $db->beginTransaction();

                                try
                                { 
                                    if(!empty($_FILES['image']) && count($_FILES['image'])){
                                        $response = $this->common->uploadS3Bucket($bucket_name,$_FILES["image"],$folder_name);
                                        if(isset($response['new_file_name'])){
                                            $file_path = $response['new_file_name'];
                                            $data = array_merge($data,array(
                                                    'image_link'    => $file_path
                                                ));
                                        }
                                    }else{
                                        if($waRow && $waRow->image_link && empty($exist_image)){
                                            $waRow->image_link = "";
                                        }
                                    }

                                    if(!empty($_FILES['audio']) && count($_FILES['audio'])){                        
                                        $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES["audio"],$folder_name);                        
                                        if(isset($response['new_file_name'])){
                                            $file_path = $response['new_file_name'];
                                            $data = array_merge($data,array(
                                                'audio_link'    => $file_path
                                            ));
                                        }
                                    }else{
                                        if($waRow && $waRow->audio_link && empty($exist_audio)){
                                            $waRow->audio_link = "";
                                        }
                                    }

                                    if(!empty($_FILES['video']) && count($_FILES['video'])){
                                        $response =  $this->common->uploadS3Bucket($bucket_name,$_FILES["video"],$folder_name);                        
                                        if(isset($response['new_file_name'])){
                                            $file_path = $response['new_file_name'];  
                                        /* "upload/". $parse_file_name = explode(".", $response['new_file_name']);                            
                                            $thumbnail_path = '/images/'.$parse_file_name['0']."_thumb.jpg";
                                            $video_path = '/'.$file_path;
                                            $this->createThumbnail($video_path, $thumbnail_path);
                                            ,'thumbnail_link'    => $thumbnail_path */                            
                                            $data = array_merge($data,array(
                                                'video_link'        => $file_path   
                                            ));
                                        }
                                    }else{
                                        if($waRow && $waRow->video_link && empty($exist_video)){
                                            $waRow->video_link = "";
                                        }
                                    }

                                    if($waRow){
                                        $waRow->setFromArray($data);
                                        $waRow->save();
                                    }else{
                                       // $waRow = $waTable->createRow($data);
                                        $waRow = $waTable->save($data);
                                        $wa_id = $waRow;
                                    }                                

                                    if($is_existing_wa){
                                        $waEventSendTable->deleteAllEventByWAId($userId,$wa_id);
                                        $waReceiverTable->deleteReceivers($wa_id);                               
                                        $waTrusteeTable->deleteWaReceivers($userId,$wa_id);
                                    }  
                                    $currentTime = date("Y-m-d H:i:s");
                                    $addYears = '10 years';
                                    $event_expiry_date = date("Y-m-d H:i:s",strtotime("+$addYears", strtotime($currentTime)));
                                    $eventSendDetailDataArr = array(     
                                                                'wa_id'              => $wa_id,
                                                                'user_id'            => $userId,
                                                                'creation_date'      => $currentTime,
                                                                'vital_check_type'   => Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER,   
                                                                'event_expiry_date'  => $event_expiry_date,                      
                                                                'is_status'          => 0, 
                                                                'event_status'       => '1'
                                                            );
                                    $count = 0;
                                    if(!empty($vital_check_set)) {
                                       foreach ($vital_check_set as $row) { 
                                               $eventSendDetailData[]  = array_merge($eventSendDetailDataArr,array('usertype'=>'1','event_send_date' => $row,'event_vital_value'=> $owner_vital_check[$count++]));
                                       }
                                       $eventSendDetailData[] = array_merge($eventSendDetailDataArr,array('usertype'=>'2','event_send_date' => $trustee_vital_check[0],'event_vital_value'=>  $trustee_vital_time[0]));
                                    }                   
                                    $eventSendDetailRow    = false; 
                                    (count($vital_check_set)>0)?$this->InsertMultipleRows('wa_event_send_details', $eventSendDetailData):"";

                                    if($wa_id){                             
                                        if(!empty($receiver_userset))
                                        {
                                            $receiver_userset = json_decode(urldecode($receiver_userset),true); 
                                            foreach ($receiver_userset as $receiverId)
                                            {
                                                $data = array(
                                                    'wa_id'         => $wa_id,
                                                    'receiver_id'   => $receiverId
                                                );
                                                if($receiverId != $userId){
                                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                                    $waReceiverRow->save();
                                                }
                                            }
                                        } 
                                        if(!empty($receiver_emailset)){
                                            $receiver_emailset = json_decode(urldecode($receiver_emailset),true);
                                            foreach ($receiver_emailset as $receiverEmail){
                                                $data   = array(
                                                            'wa_id'          => $wa_id,
                                                            'receiver_email' => $receiverEmail
                                                        );
                                                $waReceiverRow = $waReceiverTable->createRow($data);
                                                $waReceiverRow->save();
                                            }
                                        }
                                        if(!empty($receiver_trusteeset)) 
                                        {
                                            $receiver_trusteeset = json_decode(urldecode($receiver_trusteeset),true); 
                                            foreach ($receiver_trusteeset as $receiverId)
                                            {
                                                $waTrusteeData  = array(
                                                                    'wa_id'        => $wa_id,
                                                                    'user_id'      => $userId,
                                                                    'receiver_id'  => $receiverId
                                                                );
                                                if($receiverId != $userId){
                                                    $waTrusteeRow = $waTrusteeTable->createRow($waTrusteeData);
                                                    $waTrusteeRow->save();
                                                }
                                            }
                                        }  
                                    }
                                } catch(Exception $ex){
                                    $db->rollBack();
                                    $this->common->displayMessage($ex->getMessage(),"1",array(),"12");
                                }
                              $db->commit();
                              $this->common->displayMessage($this->view->translate('wa-after_success'),"0",array(),"0");                     
                            }else{
                                $this->common->displayMessage($this->view->translate('vital_check_count'), '1', array(), '116');
                            } 
                        }else{
                            $this->common->displayMessage($this->view->translate('wa-after_not_update'),"1",array(),"28");
                        }
                    }else{
                        $this->common->displayMessage($this->view->translate('wa_trustee_not_exist'), '1', array(), '30');
                    } 
                }else{
                    $this->common->displayMessage($this->view->translate('wa_receiver_not_exist'), '1', array(), '32');
                }  
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }            
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
 
    /**
     *  listing of all wa
     */
    public function listAction()
    {
        $userTable           = new Application_Model_DbTable_Users();
        $waTable             = new Application_Model_DbTable_Wa();
        $waReceiverTable     = new Application_Model_DbTable_WaReceiver();
        
        $decoded             = $this->common->Decoded();   
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];    
        $userSecurity        = $decoded['userSecurity'];
        $offset              = isset($decoded['offset'])?$decoded['offset']:0;
        $get_incoming_wa     = isset($decoded['get_incoming_wa'])?$decoded['get_incoming_wa']:'1';
        $search_type         = isset($decoded['search_type']) ? $decoded['search_type']:"all";
        $deviceLanguage      = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage); 
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                $offset    = $offset*10;                
                $waRowset  = $waTable->getRecordsByUserId($userId,$offset,$get_incoming_wa,$search_type);
                $waDetails = array(); 
                if(!empty($waRowset)) {
                    foreach($waRowset as $waRow){                        
                        $waDetails[] = array(
                                        'wa_id'                 => $waRow->wa_id,
                                        'type'                  => $waRow->type ? $waRow->type:"",
                                        'userNickName'          => $waRow->userNickName ? $waRow->userNickName:"",
                                        'message_title'         => $waRow->message_title ? $waRow->message_title:"",
                                        'text'                  => $waRow->text ? $waRow->text:"",
                                        'image_link'            => $waRow->image_link ? $waRow->image_link:"",
                                        'audio_link'            => $waRow->audio_link ? $waRow->audio_link:"",
                                        'video_link'            => $waRow->video_link ? $waRow->video_link:"",
                                        'thumbnail_link'        => $waRow->thumbnail_link ? $waRow->thumbnail_link:"",
                                        'delivery_date'         => $waRow->delivery_date ? $waRow->delivery_date:"",
                                        'modification_date'     => $waRow->modification_date ? $waRow->modification_date:"",
                                        'is_send'               => $waRow->is_send,
                                        'is_incoming_request'   => $waRow->is_incoming_request,
                                        'is_read'               => $waRow->is_read
                                    );
                    }
                }                
                $response   = array(
                                "error_code"            => "0",
                                "response_error_key"    => "0",
                                "response_string"       => "WA Details",
                                "result"                => $waDetails
                            );                
                echo json_encode($response);
                exit();                
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }
        }else{
           $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }               
    }
    
    /**
     * details of one wa service using wa id
     */
    
    public function waDetailsByIdAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        
        $decoded            = $this->common->Decoded();        
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $wa_id              = $decoded['waId']; 
        $deviceLanguage     = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage); 
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())                
            {
                if($waRow = $waTable->getRowById($wa_id))
                {                    
                    $waOwnerRow = $userTable->getRowById($waRow->user_id);                    
                    if($waReceiverRow = $waReceiverTable->getRowByWaIdAndReceiverId($wa_id, $userId)){
                        $waReceiverRow->is_read = "1";
                        $waReceiverRow->save();   // set read status 
                    }
                    
                    $waReceiverRowset = $waReceiverTable->getWaReceivers($wa_id);                    
                    $waReceiverSet = array();
                    $waReceiverNames = array();
                    foreach($waReceiverRowset as $waReceiverRow)
                    {
                        if($waReceiverRow->receiver_id){                            
                            $waReceiverSet[] = array(
                                                'userId'        => $waReceiverRow->receiver_id,
                                                'userNickName'  => ($waReceiverRow->userNickName)?$waReceiverRow->userNickName:"",
                                                'userImage'     => ($waReceiverRow->userImage)?$this->makeUrl($waReceiverRow->userImage):""
                                            );
                        }else{
                            $waReceiverNames[] = array(
                                                    'name'   => ($waReceiverRow->receiver_name)?$waReceiverRow->receiver_name:"",
                                                    'email'  => ($waReceiverRow->receiver_email)?$waReceiverRow->receiver_email:"",
                                                    'phone'  => ($waReceiverRow->receiver_phone)?$waReceiverRow->receiver_phone:""
                                                );                            
                        }
                    }
                    
                    $data   = array(
                                'wa_id'                 => $waRow->wa_id,
                                'is_incoming_request'   => ($waRow->user_id == $userId)?"0":"1",
                                'type'                  => $waRow->type,
                                'sender_name'           => $waOwnerRow->userNickName,
                                'sender_image'          => ($waOwnerRow->userImage)? $this->makeUrl($waOwnerRow->userImage):"",
                                'message_title'         => $waRow->message_title,
                                'text'                  => $waRow->text ? $waRow->text:"",
                                'image_link'            => $waRow->image_link ? $waRow->image_link:"",
                                'audio_link'            => $waRow->audio_link ? $waRow->audio_link:"",
                                'video_link'            => $waRow->video_link ? $waRow->video_link:"",
                                'thumbnail_link'        => $waRow->thumbnail_link ? $waRow->thumbnail_link:"",
                                'delivery_date'         => $waRow->delivery_date,
                                'delivery_time'         => $waRow->deliveryTime(),
                                'is_send'               => $waRow->is_send,
                                'sender_id'             => $waOwnerRow->userId,
                                'waReceiverSet'         => $waReceiverSet,
                                'waReceiverNames'       => $waReceiverNames,
                                'local_time'            => $waRow->local_time,
                                'is_annually'           => $waRow->is_annually,
                                'is_read'               => "1",
                            );
                    
                    $this->common->displayMessage($this->view->translate('wa-later_details'),"0",$data,"0"); 
                }else{
                   $this->common->displayMessage($this->view->translate('wa-id_not_exist'),"1",array(),"26"); 
                }
            }else{
               $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2"); 
            }            
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
    
    /**
     * function for getting wa event and after lisiting
    */
    public function eventAfterListAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        $waEventSendTable   = new Application_Model_DbTable_WaEventSendDetails();
        
        $decoded            = $this->common->Decoded();
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $offset             = isset($decoded['offset'])?$decoded['offset']:0;
        $get_incoming_wa    = isset($decoded['get_incoming_wa'])?$decoded['get_incoming_wa']:'1';
        $search_type        = (isset($decoded['search_type']) && $decoded['search_type']) ? $decoded['search_type']:"all";
        $deviceLanguage     = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage); 
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                
                $offset     = $offset*10;                
                $waRowset   = $waTable->getEventRecordsByUserId($userId,$offset,$get_incoming_wa,$search_type);
                $waDetails  = array();
            
                foreach($waRowset as $waRow)
                {
                    $owner_vital_check = "";
                    $trustee_vital_check = "";
                    $waEventRows = $waEventSendTable->getAllEventRowByWaId($userId,$waRow->wa_id);
                    
                    foreach($waEventRows as $waEventRow){
                        if($waEventRow->usertype == '1') {
                            $owner_vital_check[] = $waEventRow->event_vital_value; 
                        } else if($waEventRow->usertype == '2') {
                            $trustee_vital_check[] = $waEventRow->event_vital_value;
                        }
                    }
                    if(!empty($waRow->wa_id)){
                        $waEventFisrtRow  = $waEventSendTable->getFirstRowByWaId($waRow->wa_id);                                               
                        $event_expiry_date = $waEventFisrtRow->event_expiry_date;
                        $event_send_date  = $waEventFisrtRow->event_send_date;
                    }
                    $waDetails[]   = array(
                                        'wa_id'                 => $waRow->wa_id,
                                        'type'                  => $waRow->type,
                                        'userNickName'          => $waRow->userNickName,
                                        'message_title'         => $waRow->message_title,
                                        'text'                  => $waRow->text ? $waRow->text:"",
                                        'image_link'            => $waRow->image_link ? $waRow->image_link:"",
                                        'audio_link'            => $waRow->audio_link ? $waRow->audio_link:"",
                                        'video_link'            => $waRow->video_link ? $waRow->video_link:"",
                                        'thumbnail_link'        => $waRow->thumbnail_link ? $waRow->thumbnail_link:"",
                                        'creation_date'         => $waRow->creation_date ? $waRow->creation_date:"",
                                        'delivery_date'         => $waRow->delivery_date ? $waRow->delivery_date:"",
                                        'event_expiry_date'     => $event_expiry_date ? $event_expiry_date:"",
                                        'is_send'               => $waRow->is_send,
                                        'is_incoming_request'   => $waRow->is_incoming_request,
                                        'is_read'               => $waRow->is_read,
                                        'event_send_date'       => $event_send_date ? $event_send_date:"",
                                        'owner_vital_check'     => $owner_vital_check ? $owner_vital_check:"",
                                        'trustee_vital_check'   => $trustee_vital_check ? $trustee_vital_check:"",
                                        'status'                => $waRow->status
                                    );
                }
                
                $response = array(
                                "error_code"            => "0",
                                "response_error_key"    => "0",
                                "response_string"       => "WA Details",
                                "result"                => $waDetails
                            );                
                echo json_encode($response);
                exit();                
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }               
    }
    
    public function eventAfterDetailsByIdAction()
    {        
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable     = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable   = new Application_Model_DbTable_WaEventSendDetails();
        
        $decoded            = $this->common->Decoded();        
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $wa_id              = $decoded['waId'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage); 
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                if($waRow = $waTable->getRowById($wa_id))
                {                   
                    $waOwnerRow = $userTable->getRowById($waRow->user_id);                                        
                    /** wa_receiver table **/                    
                    if($waReceiverRow = $waReceiverTable->getRowByWaIdAndReceiverId($wa_id, $userId)){
                        $waReceiverRow->is_read = "1";
                        $waReceiverRow->save(); // set read status 
                    }
                   
                    $waReceiverRowset = $waReceiverTable->getWaReceivers($wa_id);                    
                    $waReceiverSet    = array();
                    $waReceiverNames  = array();
                    foreach($waReceiverRowset as $waReceiverRow)
                    {
                        if($waReceiverRow->receiver_id){                            
                            $waReceiverSet[] = array(
                                                'userId'        => $waReceiverRow->receiver_id,
                                                'userNickName'  => ($waReceiverRow->userNickName)?$waReceiverRow->userNickName:"",
                                                'userImage'     => ($waReceiverRow->userImage)?$this->makeUrl($waReceiverRow->userImage):""
                                            );                            
                        }else{
                            $waReceiverNames[] = array(
                                                'name'   => ($waReceiverRow->receiver_name)?$waReceiverRow->receiver_name:"",
                                                'email'  => ($waReceiverRow->receiver_email)?$waReceiverRow->receiver_email:"",
                                                'phone'  => ($waReceiverRow->receiver_phone)?$waReceiverRow->receiver_phone:""
                                            );
                        }
                    }
                    
                    $waTrusteeRowset = $waTrusteeTable->getWaReceivers($wa_id);                    
                    $waTrusteeSet    = array();
                    $waTrusteeNames  = array();
                    foreach($waTrusteeRowset as $waTrusteeRow)
                    {
                        if($waTrusteeRow->receiver_id){
                            $waTrusteeSet[] = array(
                                                'userId'        => $waTrusteeRow->receiver_id,
                                                'userNickName'  => ($waTrusteeRow->userNickName)?$waTrusteeRow->userNickName:"",
                                                'userImage'     => ($waTrusteeRow->userImage)?$this->makeUrl($waTrusteeRow->userImage):""
                                            );
                        }else{
                            $waTrusteeNames[] = array(
                                                    'name'   => ($waTrusteeRow->receiver_name)?$waTrusteeRow->receiver_name:"",
                                                    'email'  => ($waTrusteeRow->receiver_email)?$waTrusteeRow->receiver_email:"",
                                                    'phone'  => ($waTrusteeRow->receiver_phone)?$waTrusteeRow->receiver_phone:""
                                                );
                        }
                    } 
                    
                    /** Response data prepration **/                 
                    $data = array(
                                'wa_id'                 => $waRow->wa_id,
                                'is_incoming_request'   => ($waRow->user_id == $userId)?"0":"1",
                                'type'                  => $waRow->type,
                                'sender_name'           => $waOwnerRow->userNickName,
                                'sender_image'          => ($waOwnerRow->userImage)?$this->makeUrl($waOwnerRow->userImage):"",
                                'message_title'         => $waRow->message_title,
                                'text'                  => $waRow->text ? $waRow->text:"",
                                'image_link'            => $waRow->image_link ? $waRow->image_link:"",
                                'audio_link'            => $waRow->audio_link ? $waRow->audio_link:"",
                                'video_link'            => $waRow->video_link ? $waRow->video_link:"",
                                'thumbnail_link'        => $waRow->thumbnail_link ? $waRow->thumbnail_link:"",
                                'delivery_date'         => $waRow->delivery_date ? $waRow->delivery_date:"",
                                'sender_id'             => $waOwnerRow->userId,
                                'waReceiverSet'         => $waReceiverSet,
                                'waReceiverNames'       => $waReceiverNames,
                                'waTrusteeSet'          => $waTrusteeSet,
                                'waTrusteeNames'        => $waTrusteeNames,
                                'is_read'               => "1",
                            );
                    $this->common->displayMessage($this->view->translate('wa-after_details'),"0",$data,"0"); 
                }else{
                    $this->common->displayMessage($this->view->translate('wa-id_not_exist'),"1",array(),"26"); 
                }
            }else{
               $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2"); 
            }
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
     
    public function deleteAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        $waTable            = new Application_Model_DbTable_Wa();
        $waEventSendTable   = new Application_Model_DbTable_WaEventSendDetails();

        $decoded            = $this->common->Decoded();
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $wa_id              = $decoded['wa_id'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage); 
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter(array($userId,$wa_id,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
           
            if(($userRow = $userTable->getRowById($userId)) && ($userRow->isActive()))
            {
                if($waRow = $waTable->getRowById($wa_id))
                {
                    $waRow->is_deleted = "1";
                    $waRow->modification_date = date("Y-m-d H:i:s");
                    $waRow->save();
                    
                    if($waRow->type == Application_Model_DbTable_Wa::WA_TYPE_EVENT || $waRow->type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE){
                        $eventSendDetailRows = $waEventSendTable->getAllEventRowByWaId($userId, $wa_id);
                        if(!empty($eventSendDetailRows)){
                            foreach($eventSendDetailRows as $row){
                                $row->event_status = "0";
                                $row->save();
                            }  
                        }
                    }
                    $this->common->displayMessage($this->view->translate('wa_delete_success'),"0",array(),"0");
                } else {
                    $this->common->displayMessage($this->view->translate('wa-id_not_exist'),"1",array(),"26");
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }           
        } else {
           $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
    
    /**
     * diliver wa using cron job that will be run after every mins
     */
    public function deliverAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
     
        $currentTime        = time();
        $currentDate        = date("Y-m-d H:i:s");       
        $waRowset           = $waTable->getAllWALaterRecordsForPushNoitifications();
        
        foreach($waRowset as $waRow)
        {
            $waRow->is_send = "3";
            if($waRow->is_annually){
                if($waRow->next_delivery_date_utc){
                    $waRow->next_delivery_date_utc = date('Y-m-d H:i:s',strtotime($waRow->next_delivery_date_utc . " + 365 day"));
                    $waRow->last_delivery_date_utc = date('Y-m-d H:i:s');
                }else{
                    $waRow->next_delivery_date_utc = date('Y-m-d H:i:s',strtotime($waRow->first_delivery_date_utc . " + 365 day"));
                }
            }                
            $waRow->save();

            if($waRow->type == Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE){
                $this->sendChatMessage($waRow);
            }
            else
            {
                $waReceiverRowset = $waReceiverTable->getWaReceivers($waRow->wa_id);
                $waOwnerRow       = $userTable->getRowById($waRow->user_id);                   
                foreach($waReceiverRowset as $waReceiverRow)
                {
                    if($waReceiverRow->receiver_id)
                    {             
                       // send push to wa receiver 
                        $resultData =  array(
                                        'userImage' => ($waOwnerRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                        'userId'    => $waOwnerRow->userId,
                                        'userName'  => $waOwnerRow->userNickName,
                                        'wa_id'     => $waRow->wa_id,     
                                        'wa_type'   => $waRow->type     
                                    );
                        $message = $this->view->translate('wa_later_received');
                        if($waReceiverRow->userId)
                        {
                            $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waReceiverRow->userId);
                            foreach ($userLoginDeviceRowset as $loginDeviceRow){
                                if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken){
                                    if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload    = array(
                                                        'aps'   => array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_wa', 'wa_id'=> $waRow->wa_id,'message' => $message)
                                                    );
                                    }else{
                                        $payload    = array(
                                                        'message'   => $message,
                                                        'type'      => "incoming_wa",
                                                        'result'    => $resultData    
                                                    );
                                       $payload = json_encode($payload);
                                    }

                                    try{
                                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                                    }catch(Exception $ex){
                                        echo $ex->getMessage();
                                        exit();
                                    }
                                }
                            }
                        }
                    }else if($waReceiverRow->receiver_email) {
                        $params = array(
                                    'userNickName'  => $waOwnerRow->userNickName,
                                    'baseUrl'       => $this->baseUrl
                                );
                        $this->mail($params,'incoming_wa.phtml',$waReceiverRow->receiver_email,'Welcome To WA-app');
                    }else if($waReceiverRow->receiver_phone){
                        $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                        $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                        $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                        $this->common->sendSms_recommend($waReceiverRow->receiver_phone,$message);
                    }
                }
                
                $waEmailReceiverSet = $waReceiverTable->getWaReceiversByWAId($waRow->wa_id);
                if(!empty($waEmailReceiverSet)){
                    foreach ($waEmailReceiverSet as $receiver_email){
                        $params = array(
                                    'userNickName'  => $waOwnerRow->userNickName,
                                    'baseUrl'       => $this->baseUrl
                                );
                        $this->mail($params,'incoming_wa.phtml',$receiver_email,'Welcome To WA-app');
                    }
                }
            }
        }
       $this->eventDeliverAction();
       exit();
    }
     
    public function eventDeliverAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTable                = new Application_Model_DbTable_Wa();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $waEventDetailTable     = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteesTable        = new Application_Model_DbTable_WaTrustee();
        
        $currentTime            = time();
        $currentDate            = date("Y-m-d H:i:s");
        $waEventDetailRowset    = $waTable->getAllWARecordsForPushNoitifications();
       
        if(!empty($waEventDetailRowset)) {
            foreach ($waEventDetailRowset as $waEventDetailRow)
            {
                $waRow  = $waTable->getRowById($waEventDetailRow->wa_id);

                $waEventRow = $waEventDetailTable->getRowById($waEventDetailRow->id);

                $send_wa_to_receiver = false;
                $send_alert_notification_to_owner = false;
                $send_alert_notification_to_trustee = false;
                
                if($waEventDetailRow->usertype == '1' && $waEventDetailRow->vital_check_type == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
                {
                    $send_alert_notification_to_owner = true;
                    $waEventRow->is_status = '1';
                    $this->sendAlertToOwner($waEventDetailRow->user_id, Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT);
                    $waTable->sendAllUsersWaEvent($waEventDetailRow->user_id, $waEventDetailRow->wa_id, '1');
                    $waEventRow->save();
                }
                else if($waEventDetailRow->usertype == '1' && $waEventDetailRow->vital_check_type == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
                {
                    $send_alert_notification_to_owner = true;
                    $waEventRow->is_status = '1';
                    $this->sendAlertToOwner($waEventDetailRow->user_id, Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER);
                    $waTable->sendAllUsersWaAfter($waEventDetailRow->user_id, $waEventDetailRow->wa_id, '1');
                    $waEventRow->save();
                }
                else
                {
                    $send_alert_notification_to_trustee = true;  
                    $trustee_set = $waTrusteesTable->getTrusteesByWAId($waEventDetailRow->user_id, $waEventDetailRow->wa_id);
                   
                    if(!empty($trustee_set)){
                        foreach($trustee_set as $row){ 
                            if($send_alert_notification_to_trustee) { 
                               $this->sendAlertToTrustee($row->user_id, $waEventDetailRow->vital_check_type);
                            }
                        }
                    } 
                    
                    if($waEventDetailRow->vital_check_type == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT) {
                        $waTable->sendAllUsersWaEvent($waEventDetailRow->user_id, $waEventDetailRow->wa_id, '2');
                    }
                    
                    if($waEventDetailRow->vital_check_type == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER) {
                        $waTable->sendAllUsersWaAfter($waEventDetailRow->user_id, $waEventDetailRow->wa_id, '2');
                    }
                   $waEventRow->is_status = '1';
                   $waEventRow->save();
                }
            }
        }
       $this->eventReceivedAction();
       exit();
    }
    
    public function eventReceivedAction()
    { 
        $waTable                     = new Application_Model_DbTable_Wa();
        $waReceiverTable             = new Application_Model_DbTable_WaReceiver();
        $userSettingTable            = new Application_Model_DbTable_UserSetting();
        $waEventDetailTable          = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteesTable             = new Application_Model_DbTable_WaTrustee();
    
        $waEventDetailRowset         = $waEventDetailTable->getAllWARowByUserIdAndVitalType();
       
        if(!empty($waEventDetailRowset))
        {
            foreach ($waEventDetailRowset as $waEventDetailRow)
            {
                $send_testament_to_receiver     = false;
                $trustee_set   = $waTrusteesTable->getTrusteesByWAId($waEventDetailRow->user_id, $waEventDetailRow->wa_id);
                $count_send_trustee = count($trustee_set);
                $count_response_trustee = $waEventTrusteeResponseTable->countWASendResponse($waEventDetailRow->wa_id);
                if($count_response_trustee > ($count_send_trustee/2))
                {  
                    $send_testament_to_receiver = true;
                    $waReceivers = $waReceiverTable->getWaReceivers($waEventDetailRow->wa_id);
 
		    if(!empty($waReceivers)){   
                        foreach($waReceivers as $row){ 
                            if ($send_testament_to_receiver){   //$row->userId, Application_Model_DbTable_WaTestaments::TESTAMENT_VITAL_CKECK_EVENT, "testament",                     
                                $this->sendEventToReceiver($waEventDetailRow->wa_id);
                            }
                        }                       
		       $waTable->sendVitalCheckUsers($waEventDetailRow->wa_id,'3');
                    }
                    $waEmailReceiverSet = $waReceiverTable->getWaReceiversByWAId($waEventDetailRow->wa_id);
                    if(!empty($waEmailReceiverSet)){
                        foreach ($waEmailReceiverSet as $receiver_email){
                            $params = array(
                                        'userNickName'  => "",
                                        'baseUrl'       => $this->baseUrl
                                    );
                            $this->mail($params,'incoming_wa.phtml',$receiver_email,'Welcome To WA-app');
                        }
                    }
                }
            }
        }
       exit();
    }
    
    /**
     * function for sending wa event alert to owner
    */
    public function sendAlertToOwner($user_id,$alert_type)
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        
        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($user_id);
        
        $message  = $this->view->translate('are_you_ok');
        $is_after = ($alert_type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)?"1":"0";
        
        foreach ($userLoginDeviceRowset as $loginDeviceRow)
        {
            if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
            {
                if($loginDeviceRow->userDeviceType == "iphone")
                {
                    $payload = array(
                                'aps'   => array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'wa_event_alert', 'wa_owner_id' => $user_id,'is_trustee' => '0','is_after' => $is_after)
                             );
                }
                else
                {
                    $resultData = array(
                                        'userImage'     => "",
                                        'userId'        => "",
                                        'userName'      => "",
                                        'wa_owner_id'   => $user_id,
                                        'is_trustee'    => '0',
                                        'is_after'      => $is_after
                                  );

                    $payload   = array(
                                    'message'   => $message,
                                    'type'      => "wa_event_alert",
                                    'result'    => $resultData    
                                );
                    $payload = json_encode($payload);
                }
                $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
            }
        }
    }
    
    /**
     * function for sending wa event alert to trustee
    */
    
    public function sendAlertToTrustee($user_id,$alert_type)
    {
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        $waTrusteeTable     = new Application_Model_DbTable_WaTrustee();
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $waTrusteeRowset = $waTrusteeTable->getWaTrustees($user_id);
        
        $message    = $this->view->translate('alert_to_trustee');
        $is_after   = ($alert_type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)?"1":"0";
        
        foreach($waTrusteeRowset as $waTrusteeRow)
        {
            if(!$waEventResponseTable->getRowByDetailIdAndUserId($user_id, $waTrusteeRow->userId))
            {
                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waTrusteeRow->userId);

                foreach ($userLoginDeviceRowset as $loginDeviceRow)
                {
                    if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                    {
                        if($loginDeviceRow->userDeviceType == "iphone"){
                            $payload = array(
                                        'aps'   => array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'wa_event_alert', 'wa_owner_id'=> $user_id,'is_trustee' => '1','is_after' => $is_after)
                                    );
                        }
                        else
                        {
                            $resultData =  array(
                                            'userImage'         => "",
                                            'userId'            => "",
                                            'userName'          => "",
                                            'wa_owner_id'       => $user_id,
                                            'is_trustee'        => '1',
                                            'is_after'          => $is_after
                                        );

                            $payload    = array(
                                            'message'   => $message,
                                            'type'      => "wa_event_alert",
                                            'result'    => $resultData    
                                        );
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }   
        }        
    }
    
    /**
     *  function for sending wa event 
    */
    public function sendEventToReceiver($wa_id)
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        
        if($waRow = $waTable->getRowById($wa_id))
        {
            $waReceiverRowset = $waReceiverTable->getWaReceivers($waRow->wa_id);
            $waOwnerRow = $userTable->getRowById($waRow->user_id);
            
            foreach($waReceiverRowset as $waReceiverRow)
            {
                if($waReceiverRow->receiver_id)
                { // send push to wa receiver 
                    $resultData =  array(
                                    'userImage'      => ($waOwnerRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                    'userId'         => $waOwnerRow->userId,
                                    'userName'       => $waOwnerRow->userNickName,
                                    'wa_id'          => $waRow->wa_id,
                                    'wa_receiver'    => '1',
                                    'wa_type'       => $waRow->type
                                );
                    if($waRow->type == Application_Model_DbTable_Wa::WA_TYPE_EVENT){
                        $message = $this->view->translate('wa_event_received');
                    }else if($waRow->type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE){
                        $message = $this->view->translate('wa_after_received');
                    }
                    if($waReceiverRow->userId)
                    {
                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waReceiverRow->userId);
                        foreach ($userLoginDeviceRowset as $loginDeviceRow)
                        {
                            if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken){
                                if($loginDeviceRow->userDeviceType == "iphone"){
                                    $payload = array(
                                                'aps'   => array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'wa_event_receiver_alert', 'wa_id'=> $waRow->wa_id,'wa_receiver'    => '1')
                                             );
                                }else{
                                    $payload = array(
                                                'message'   => $message,
                                                'type'      => "wa_event_receiver_alert",
                                                'result'    => $resultData    
                                             );
                                    $payload = json_encode($payload);
                                }
                              $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken, $payload);
                            }
                        }
                    }
                }else if($waReceiverRow->receiver_email){
                    $params = array(
                                'wa_owner_name'     => $waOwnerRow->userNickName,
                                'baseUrl'           => $this->baseUrl
                             );
                    $this->mail($params,'incoming_wa.phtml',$waReceiverRow->receiver_email,'Welcome To WA-app');
                }else if($waReceiverRow->receiver_phone){
                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                    $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                    $message = "Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                    $this->common->sendSms_recommend($waReceiverRow->receiver_phone,$message);
                }
            }
        }
    }
  
    public function getAllWaVitalCheckAction()
    {
        $userTable                   = new Application_Model_DbTable_Users();
        $waTable                     = new Application_Model_DbTable_Wa();       
        $waEventSendDetailsTable     = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeReponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteeTable              = new Application_Model_DbTable_WaTrustee();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        $owner_alert_set    = array();
        $trustee_alert_set  = array();
        $trustee_arr        = array();
        $owner_alert_row    = array();
        
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {  
                    $waEventSendRow  = $waEventSendDetailsTable->getAllWASendVitalByUserId($userId); 
                    if(count($waEventSendRow) > 0) {
                        $waOwnerEventRows = $waEventSendDetailsTable->getAllWAOwnerSendVitalByUserId($userId);
                        foreach ($waOwnerEventRows as $row){
                            $waRow       = $waTable->getRowById($row['wa_id']);
                            if($waRow->is_send == 1){
                                $owner_alert_set[]  = array(
                                                    'id'               => $row['id'] ? $row['id']:"",
                                                    'wa_id'            => $row['wa_id'] ? $row['wa_id']:"",
                                                    'is_send'          => $waRow['is_send'],
                                                    'user_id'          => $row['user_id'] ? $row['user_id']:"",
                                                    'message_title'    => $waRow['message_title'] ? $waRow['message_title']:"",
                                                    'vital_check_type' => $row['vital_check_type'] ? $row['vital_check_type']:"",
                                                    'creation_date'    => $row['creation_date'] ? $row['creation_date']:"",
                                                    'event_send_date'  => $row['event_send_date'] ? $row['event_send_date']:"",
                                                    'event_response'   => $row['event_response'],
                                                    'is_status'        => $row['is_status'] 
                                                );     
                            }
                        }
                        
                        $waTrusteeEventRows = $waEventSendDetailsTable->getAllWATrusteeSendVitalByUserId($userId);
                        foreach ($waTrusteeEventRows as $row){    
                            $trustee_arr = "";
                            $trustee_set    = $waTrusteeTable->getWATrusteesByUserId($row['user_id'],$row['wa_id']);
                            if(count($trustee_set) > 0) {
                                foreach ($trustee_set as $trustee){
                                    $waRow    = $waTable->getRowById($trustee['wa_id']);
                                    $eventRow = $waEventSendDetailsTable->getTrusteeAlertRowByWAId($trustee['user_id'],$trustee['wa_id']); 
                                    $trustee_response_row  = $waEventTrusteeReponseTable->getTrusteeRowByDetailIdAndUserId($eventRow['id'],$trustee['receiver_id']);
                                    if(!empty($trustee_response_row)){
                                        $response = $trustee_response_row->response;
                                        $is_owner = $trustee_response_row->is_owner;
                                    }else{
                                        $response = $eventRow['event_response'];
                                    }

                                    if(count($eventRow) > 0) {
                                        $trustee_arr[]  = array(
                                                            'id'               => $eventRow['id'] ? $eventRow['id']:"",
                                                            'wa_id'            => $trustee['wa_id'] ? $trustee['id']:"", 
                                                            'is_send'          => $waRow['is_send'],    
                                                            'message_title'    => $waRow['message_title'] ? $waRow['message_title']:"",
                                                            'vital_check_type' => $eventRow['vital_check_type'] ? $eventRow['vital_check_type']:"",
                                                            'creation_date'    => $row['creation_date'] ? $row['creation_date']:"",
                                                            'event_send_date'  => $eventRow['event_send_date'] ? $eventRow['event_send_date']:"",
                                                            'userId'           => $trustee['user_id'] ? $trustee['user_id']:"",
                                                            'userNickName'     => $trustee['userNickName'] ? $trustee['userNickName']:"",
                                                            'event_response'   => $response,
                                                            'is_owner'         => $is_owner ? $is_owner:"0",
                                                            'is_status'        => $eventRow['is_status'],
                                                        );
                                    }   
                                }
                            } 
                        }
                    }
                    else
                    {
                        $waTrusteeRows  = $waTrusteeTable->getWATrusteesByUserId($userId);  
                        foreach ($waTrusteeRows as $trusteeRow) {
                            $waRow      = $waTable->getRowById($trusteeRow['wa_id']);
                            $eventRow   = $waEventSendDetailsTable->getTrusteeAlertRowByWAId($trusteeRow['user_id'],$trusteeRow['wa_id']);
                            $trustee_response_row  = $waEventTrusteeReponseTable->getTrusteeRowByDetailIdAndUserId($eventRow['id'],$trusteeRow['receiver_id']);
                            if(!empty($trustee_response_row)) {
                                $response = $trustee_response_row->response;
                                $is_owner = $trustee_response_row->is_owner;
                            }else{
                                $response = $eventRow['event_response'];
                            }
                            if(count($eventRow) > 0) {
                                $trustee_arr[]  = array(
                                                    'id'               => $eventRow['id'] ? $eventRow['id']:"",
                                                    'wa_id'            => $trusteeRow['wa_id'] ? $trusteeRow['wa_id']:"",
                                                    'is_send'          => $waRow['is_send'],
                                                    'message_title'    => $waRow['message_title'] ? $waRow['message_title']:"",
                                                    'vital_check_type' => $eventRow['vital_check_type'] ? $eventRow['vital_check_type']:"",
                                                    'creation_date'    => $eventRow['creation_date'] ? $eventRow['creation_date']:"",
                                                    'event_send_date'  => $eventRow['event_send_date'] ? $eventRow['event_send_date']:"",
                                                    'userId'           => $trusteeRow['userId'] ? $trusteeRow['userId']:"",
                                                    'userNickName'     => $trusteeRow['userNickName'] ? $trusteeRow['userNickName']:"",
                                                    'event_response'   => $response,
                                                    'is_owner'         => $is_owner ? $is_owner:"0",
                                                    'is_status'        => $eventRow['is_status'],
                                                );
                            }
                        }
                    }
                    $result = array('owner_alert_set' => $owner_alert_set, 'trustee_alert_set' => $trustee_arr);
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $this->common->displayMessage($this->view->translate('records_found'),'0',$result,'0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
        
    public function ownerVitalCheckResponseAction()
    {            
        $userTable                = new Application_Model_DbTable_Users();
        $waTable                  = new Application_Model_DbTable_Wa();
        $waTrusteeTable           = new Application_Model_DbTable_WaTrustee();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded                  = $this->common->Decoded();        
        $userId                   = $decoded['userId'];
        $userDeviceId             = $decoded['userDeviceId'];
        $userDeviceToken          = $decoded['userDeviceToken'];
        $userSecurity             = $decoded['userSecurity'];
        $rowId                    = $decoded['event_id'];
        $response                 = $decoded['event_response'];
        $deviceLanguage           = $decoded['deviceLanguage'];
        $resultArr                = "";
	$result                   = array(); 
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($rowId){ 
                        $EventSendDetailRow  = $waEventSendDetailsTable->getRowById($rowId); 
                        $waRow               = $waTable->getRowById($EventSendDetailRow->wa_id);
                    }
                    if($waRow->is_send == '0' || $waRow->is_send == '1'){
                        if(!empty($EventSendDetailRow)){
                            $eventRows   = $waEventSendDetailsTable->getAllEventRowByWaId($userId,$EventSendDetailRow->wa_id);  
                            if($response == 0)
                            { 
                                foreach($eventRows as $event){                         
                                    if($event->usertype == '1' && $event->id == $rowId) {
                                        $eventData = array('id'=> $event->id,'event_response'=> $response,'is_status'=>'1','event_status'=>'1');    
                                        $resultArr = $waEventSendDetailsTable->updateEventSendTable($eventData);
                                    } else { 
                                        if($event->usertype == '1') {
                                             $eventData1  = array('id'=> $event->id,'event_response' => '2','is_status'=>'0','event_status'=>'0');    
                                            $waEventSendDetailsTable->updateEventSendTable($eventData1);
                                        } else {
                                            $event_send_date = date('Y-m-d H:i:s', strtotime('+1 min'));
                                            $eventData2 = array('id'=> $event->id, 'event_send_date' => $event_send_date,'is_status'=>'1','event_status'=>'1');
                                            $resultArr = $waEventSendDetailsTable->updateEventSendTable($eventData2);
                                      
                                            $trustee_set = $waTrusteeTable->getTrusteesByWAId($userId,$EventSendDetailRow->wa_id);
                                            if(!empty($trustee_set)){  
                                                foreach($trustee_set as $row){ 
                                                      $this->sendAlertToTrustee($row->user_id, $event->vital_check_type);
                                                }
                                            } 
                                        }
                                      $waTable->sendVitalCheckUsers($event->wa_id,'2');
                                    }                                   
                                }
                            }
                          $result   =  array('event_id' => $resultArr); 
                          $this->common->displayMessage($this->view->translate('vital_check_saved'), '0', $result, '0');       
                        } else {
                            $this->common->displayMessage($this->view->translate('event_id_not_exist'), '1', array(), '124');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('vital_check_not_give_response'), '1', array(), '38');
                    } exit();
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        }
    }
    
    public function waMessageTrusteeResponseAction()
    {
  	$userTable                    = new Application_Model_DbTable_Users();
        $waTable                      = new Application_Model_DbTable_Wa();
        $waTrusteeTable               = new Application_Model_DbTable_WaTrustee();
        $waEventSendDetailsTable      = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeResponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $event_id            = $decoded['event_id'];
        $wa_id               = $decoded['wa_id']; 
        $trustee_id          = $decoded['trustee_id'];
        $is_owner            = $decoded['is_owner'];
        $event_response      = $decoded['event_response'];       
        $deviceLanguage      = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);   
       
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                    
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                { 
                    if($rowId){ 
                        $waRow   = $waTable->getRowById($wa_id);
                    }
                    if($waRow->is_send != '3'){
                        //if($is_owner !=  '4'){
                            $responseRow = $waEventTrusteeResponseTable->getRowByDetailIdAndUserId($event_id,$userId);
                            if(empty($responseRow)){
                                $data   = array(
                                            'wa_id'             => $wa_id,
                                            'object_id'         => $event_id,
                                            'trustee_id'        => $trustee_id,
                                            'user_id'           => $userId,
                                            'response'          => $event_response,
                                            'is_owner'          => $is_owner,
                                            'response_time'     => date('Y-m-d H:i:s')
                                        );
                                
                                $insert_id = $waEventTrusteeResponseTable->save($data);                  
                                $result    =  array('response_id'   => $insert_id);    
                                $this->common->displayMessage($this->view->translate('trustee_response_saved'), '0', $result, '0');  
                            } else {                        
                                $this->common->displayMessage($this->view->translate('trustee_given_response'),'1',array(),'126');
                            } 
                        /*} else if($is_owner ==  '4') {                        
                            $waTrusteeRow  = $waTrusteeTable->getAllWARowByReceiverIdAndUserId($trustee_id,$userId);    
                            if(!empty($waTrusteeRow))
                            {  
                                $isDeceasedUserRow = $waEventSendDetailsTable->checkDeceasedByUserId($trustee_id);
                                if($isDeceasedUserRow->is_direct == '0') {
                                    foreach ($waTrusteeRow as $trusteeRow){ 
                                        $trustee_alert_row  = $waEventSendDetailsTable->getTrusteeAlertRowByWAId($trusteeRow->user_id,$trusteeRow->wa_id); //print_r($trustee_alert_row); exit();
                                        $wa_id        = $trustee_alert_row['wa_id'];
                                        $event_id     = $trustee_alert_row['id'];   
                                        $trustee_response_row  = $waEventTrusteeResponseTable->getRowByDetailIdAndUserId($event_id,$trusteeRow->receiver_id);
                                        if(empty($trustee_response_row)) {                      
                                            $eventSendDetailRows = $waEventSendDetailsTable->getAllWAEventSendRowByUserId($trusteeRow->user_id);
                                            if(!empty($eventSendDetailRows)) {
                                                foreach ($eventSendDetailRows as $eventRow) {
                                                    if($eventRow->usertype == '1') {
                                                        $data   = array(
                                                                    'id'             => $eventRow->id,
                                                                    'event_response' => '1',
                                                                    'is_direct'      => '2',                                            
                                                                    'is_status'      => '1',
                                                                    'event_status'   => '1',
                                                                    'event_send_date' => date('Y-m-d H:i:s'),
                                                                    'modification_date' => date('Y-m-d H:i:s'),   
                                                                );
                                                        $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                                        $waTable->sendVitalCheckUsers($eventRow->wa_id,'1');
                                                    } 
                                                    else 
                                                    {
                                                        $data   = array(
                                                                    'id'            => $event_id,                                       
                                                                    'event_response'=> $response,
                                                                    'is_direct'     => '2',                                            
                                                                    'is_status'     => '1',
                                                                    'event_status'  => '1',
                                                                    'event_send_date' => date('Y-m-d H:i:s'),
                                                                    'modification_date' => date('Y-m-d H:i:s') 
                                                                );
                                                        $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                                        $waTable->sendVitalCheckUsers($eventRow->wa_id,'2');
                                                    }
                                                }  

                                                $responseData   =  array(
                                                                    'wa_id'             => $eventRow->wa_id,
                                                                    'object_id'         => $event_id,
                                                                    'trustee_id'        => $trustee_id,
                                                                    'user_id'           => $userId,
                                                                    'response'          => $event_response,
                                                                    'response_time'     => date('Y-m-d H:i:s')
                                                                );      

                                                $insert_id[]      = $waEventTrusteeResponseTable->save($responseData); 
                                                $result         = array('response_id'   => $insert_id);       

                                                $this->common->displayMessage($this->view->translate('deceased_vital_check_success'),'0',$result,'0');
                                            }
                                        }
                                    }
                                } else {                        
                                    $this->common->displayMessage($this->view->translate('already_deceased_message_response'),'1',array(),'134');
                                } 
                            } else { 
                                $this->common->displayMessage($this->view->translate('not_exist_in_message'),'1',array(),'48');
                            } 
                        } */
                    } else {                        
                        $this->common->displayMessage($this->view->translate('trustee_not_give_response'),'1',array(),'36');
                    } 
                    exit();
                } catch (Exception $ex){
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        }        
    }
         
    public function waDeceasedVitalCheckAction()
    {
        $userTable                   = new Application_Model_DbTable_Users();
        $waTable                     = new Application_Model_DbTable_Wa();
        $waEventSendDetailsTable     = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeReponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteeTable              = new Application_Model_DbTable_WaTrustee();
        $usertNotificationsTable     = new Application_Model_DbTable_UserNotifications();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $trustee_id      = $decoded['trustee_id'];
        $event_response  = $decoded['event_response']; 
        $deviceLanguage  = $decoded['deviceLanguage'];
        $event_id        = "";
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);   
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                try
                {  
                    $waTrusteeRow  = $waTrusteeTable->getAllWARowByReceiverIdAndUserId($trustee_id,$userId);    
                    if(!empty($waTrusteeRow))
                    {  
                        $isDeceasedUserRow = $waEventSendDetailsTable->checkDeceasedByUserId($trustee_id);
                        if($isDeceasedUserRow->is_direct == '0') {
                            foreach ($waTrusteeRow as $trusteeRow){ 
                                $trustee_alert_row  = $waEventSendDetailsTable->getTrusteeAlertRowByWAId($trusteeRow->user_id,$trusteeRow->wa_id); //print_r($trustee_alert_row); exit();
                                $wa_id        = $trustee_alert_row['wa_id'];
                                $event_id     = $trustee_alert_row['id'];   
                                $trustee_response_row  = $waEventTrusteeReponseTable->getRowByDetailIdAndUserId($event_id,$trusteeRow->receiver_id);
                                if(empty($trustee_response_row)) {                      
                                    $eventSendDetailRows = $waEventSendDetailsTable->getAllWAEventSendRowByUserId($trusteeRow->user_id);
                                    if(!empty($eventSendDetailRows)) {
                                        foreach ($eventSendDetailRows as $eventRow) {
                                            if($eventRow->usertype == '1') {
                                                $data   = array(
                                                            'id'             => $eventRow->id,
                                                            'event_response' => '1',
                                                            'is_direct'      => '2',                                            
                                                            'is_status'      => '1',
                                                            'event_status'   => '1',
                                                            'event_send_date' => date('Y-m-d H:i:s'),
                                                            'modification_date' => date('Y-m-d H:i:s'),   
                                                        );
                                                $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                                $waTable->sendVitalCheckUsers($eventRow->wa_id,'1');
                                            } 
                                            else 
                                            {
                                                $data   = array(
                                                            'id'            => $event_id,                                       
                                                            'event_response'=> $response,
                                                            'is_direct'     => '2',                                            
                                                            'is_status'     => '1',
                                                            'event_status'  => '1',
                                                            'event_send_date' => date('Y-m-d H:i:s'),
                                                            'modification_date' => date('Y-m-d H:i:s') 
                                                        );
                                                $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                                $waTable->sendVitalCheckUsers($eventRow->wa_id,'2');
                                            }
                                        }  

                                        $responseData   =  array(
                                                            'wa_id'             => $eventRow->wa_id,
                                                            'object_id'         => $event_id,
                                                            'trustee_id'        => $trustee_id,
                                                            'user_id'           => $userId,
                                                            'response'          => $event_response,
                                                            'response_time'     => date('Y-m-d H:i:s')
                                                        );      

                                        $insert_id[]      = $waEventTrusteeReponseTable->save($responseData); 
                                        $result         = array('response_id'   => $insert_id);       

                                        $this->common->displayMessage($this->view->translate('deceased_vital_check_success'),'0',$result,'0');
                                    }
                                }
                            }
                        } else {                        
                            $this->common->displayMessage($this->view->translate('already_deceased_message_response'),'1',array(),'134');
                        } 
                    } else { 
                        $this->common->displayMessage($this->view->translate('not_exist_in_message'),'1',array(),'48');
                    } 
                   exit();
                } catch(Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function waDeliverVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTable                  = new Application_Model_DbTable_Wa();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable           = new Application_Model_DbTable_WaTrustee();
        $usertNotificationsTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded          = $this->common->decoded();
        $userId           = $decoded['userId'];
        $userDeviceId     = $decoded['userDeviceId'];
        $userDeviceToken  = $decoded['userDeviceToken'];
        $userSecurity     = $decoded['userSecurity'];
        $wa_id            = $decoded['wa_id'];
        $eventResponse    = $decoded['event_response'];
        $deviceLanguage   = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);   
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                try
                { 
                    $waRow  = $waTable->getRowById($wa_id);
                    if(!empty($waRow)){ 
                        if($waRow->type == Application_Model_DbTable_Wa::WA_TYPE_EVENT || $waRow->type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE) {
                            if($waRow->is_send == '0' || $waRow->is_send == '1') {
                                $eventSendDetailRows = $waEventSendDetailsTable->getAllEventRowByWaId($userId,$wa_id);
                                if(!empty($eventSendDetailRows))
                                {
                                    foreach ($eventSendDetailRows as $eventRow) {
                                        if($eventRow->usertype == '1') {
                                            $data  = array(
                                                        'id'             => $eventRow->id,
                                                        'event_response' => '1',
                                                        'is_status'      => '1',
                                                        'is_direct'      => '1',  
                                                        'event_status'   => '1',
                                                        'modification_date' => date('Y-m-d H:i:s')                                        
                                                    );
                                            $result[] = $waEventSendDetailsTable->updateEventSendTable($data);                   
                                        } else {
                                            $data  = array(
                                                        'id'             => $eventRow->id,
                                                        'event_response' => '2',
                                                        'is_status'      => '0',
                                                        'is_direct'      => '0',  
                                                        'event_status'   => '1',
                                                        'event_send_date' => date('Y-m-d H:i:s'),
                                                        'modification_date' => date('Y-m-d H:i:s')     
                                                    );
                                            $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                        }
                                        $waTable->sendVitalCheckUsers($eventRow->wa_id,'2');
                                        $trustee_set = $waTrusteeTable->getTrusteesByWAId($eventRow->user_id, $eventRow->wa_id);

                                        if(!empty($trustee_set)){
                                            foreach($trustee_set as $row){ 
                                                if($send_alert_notification_to_trustee) { 
                                                   $this->sendAlertToTrustee($row->user_id, $eventRow->vital_check_type);
                                                }
                                            }
                                        } 
                                    } 
                                   $this->common->displayMessage($this->view->translate('send_trustee_alert'),'0',$result,'0');      
                                } else {
                                    $this->common->displayMessage($this->view->translate('you_have_no_message'),'0',array(),'44');
                                }
                            } else { 
                                $this->common->displayMessage($this->view->translate('not_deliver_message'),'0',array(),'46');
                            }
                        } else if($waRow->type == Application_Model_DbTable_Wa::WA_TYPE_GO) { 
                            if($waRow->is_send == '0' || $waRow->is_send == '1') {
                                $this->sendWaLaterReceiversAction($wa_id);
                                $this->common->displayMessage($this->view->translate('success_message'),'0',array(),'0'); 
                            } else {
                                $this->common->displayMessage($this->view->translate('already_deliver_message'),'0',array(),'48');
                            }
                        } 
                    } else {
                        $this->common->displayMessage($this->view->translate('wa-id_not_exist'), '1', array(), '26'); 
                    } 
                  exit();
                } catch(Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function waEventAutoResetVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTable                  = new Application_Model_DbTable_Wa();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $deviceLanguage      = $decoded['deviceLanguage'];
        $wa_id               = $decoded['wa_id'];
        $reset_vital_check   = $decoded['reset_vital_check'];
        $resultArr	     = array();
	$result 	     = array(); 
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {  
                    if($waRow = $waTable->getRowById($wa_id))
                    {
                        if($waRow->is_send != '2' && $waRow->is_send != '3')
                        {
                            $currentDate = date('Y-m-d H:i:s'); 
                            $eventVitalCheckRows = $waEventSendDetailsTable->getAllEventRowByWaId($userId,$wa_id);  

                            if ($reset_vital_check == 1)
                            {
                                foreach($eventVitalCheckRows as $event){
                                    $after[] = $event->event_vital_value;
                                }
                                $event_send_date  =  $this->convertDateAction($currentDate, $after); 
                                $index = 0;
                                foreach($eventVitalCheckRows as $event){
                                    $eventData   = array('id'=> $event->id, 'event_send_date' => $event_send_date[$index], 'event_response' => '2', 'is_status' => '0', 'event_status' => '1','modification_date' => date('Y-m-d H:i:s'));
                                    $resultArr[] = $waEventSendDetailsTable->updateEventSendTable($eventData);  
                                    $index++;
                                }
                                if(!empty($event->wa_id)) {
                                    $waTable->sendVitalCheckUsers($event->wa_id,'0');
                                }
                               $result     =  array('event_id' => $resultArr); 
                               $this->common->displayMessage($this->view->translate('reset_vital_check'), '0', $result, '0'); 
                            } else {     
                                $this->common->displayMessage($this->view->translate('not_reset_vital_check'), '1', array(), '140'); 
                            }                             
                        } else {     
                            $this->common->displayMessage($this->view->translate('vital_check_not_give_response'), '1', array(), '38'); 
                        }
                    } else {     
                        $this->common->displayMessage($this->view->translate('wa-id_not_exist'), '1', array(), '26'); 
                    }
                   exit();
                } catch (Exception $ex){
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        } 
    }
    
    public function waEventNewResetVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTable                  = new Application_Model_DbTable_Wa();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $deviceLanguage      = $decoded['deviceLanguage'];
        $wa_id               = $decoded['wa_id'];
        $new_vital_check     = $decoded['new_vital_check'];
        $resultArr	     = array();
	$result 	     = array(); 
	$resultArr1  	     = array(); 
 	$resultArr2          = array();
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($waRow = $waTable->getRowById($wa_id))
                    {
                        if($waRow->is_send != '2' && $waRow != '3') {
                            $currentDate = date('Y-m-d H:i:s');                    
                            $eventVitalCheckRows = $waEventSendDetailsTable->getAllEventRowByWaId($userId,$wa_id);  

                            if(!empty($new_vital_check))
                            {   
                                $eventData  = array(
                                                'user_id' => $userId,
                                                'wa_id' => $wa_id,
                                                'creation_date' => $currentDate
                                            ); 
                                $waEventSendDetailsTable->deleteAllEventByWAId($userId,$wa_id);
                                $count = 0;
                                $vital_check_arr  = $new_vital_check['vital_check'];
                                if($wa->type == Application_Model_DbTable_Wa::WA_TYPE_EVENT){
                                    $addDays = '180 days';
                                    $event_expiry_date = date("Y-m-d H:i:s",strtotime("+$addDays", strtotime($currentDate)));
                                }else{
                                    $addYears = '10 years';
                                    $event_expiry_date = date("Y-m-d H:i:s",strtotime("+$addYears", strtotime($currentDate)));
                                }
                                
                                if(count($vital_check_arr) > 0) { 
                                   $event_send_date  =  $this->convertDateAction($currentDate,$vital_check_arr);
                                    foreach($event_send_date as $eventSend) {
                                        $eventData  = array_merge($eventData,array(
                                                        'usertype' => '1',					
                                                        'event_send_date' => $eventSend,
                                                        'event_vital_value' => $vital_check_arr[$count++],
                                                        'event_expiry_date' => $event_expiry_date
                                                      )
                                                    );
                                        $resultArr1[] = $waEventSendDetailsTable->saveEventSendTable($eventData);
                                    }
                                }  
                                $count1 = 0; 
                                $trsutee_vital_check  = $new_vital_check['trustee_alert_time'];
                                if(count($trsutee_vital_check) > 0) {
                                    $event_send_date  =  $this->convertDateAction($currentDate,$trsutee_vital_check);
                                    $eventData   =  array_merge($eventData,array(
                                                        'usertype' => '2',					
                                                        'event_send_date' =>  $event_send_date[$count1],
                                                        'event_vital_value' => $trsutee_vital_check[$count1], 
                                                        'event_expiry_date' => $event_expiry_date
                                                        )
                                                    );
                                  $resultArr2[] = $waEventSendDetailsTable->saveEventSendTable($eventData);			     
                                }
                                if(!empty($eventRow->wa_id)) {
                                    $waTable->sendVitalCheckUsers($eventRow->wa_id,'0');
                                }

                                $resultArr  = array_merge($resultArr1,$resultArr2);    
                                $result     = array('event_id' =>  $resultArr);     
                              $this->common->displayMessage($this->view->translate('new_vital_check_saved'), '0', $result, '0'); 
                            } else {
                                $this->common->displayMessage($this->view->translate('not_new_vital_check'), '1', array(), '42');
                            }
                        } else {
                            $this->common->displayMessage($this->view->translate('vital_check_not_give_response'), '1', array(), '38');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('wa-id_not_exist'), '1', array(), '2');
                    }
                  exit();
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        } 
    }
        
    public function getWaListTrusteeAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTable                  = new Application_Model_DbTable_Wa();       
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable           = new Application_Model_DbTable_WaTrustee();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        $owner_alert_set    = array();
        $trustee_alert_set  = array();
        $trustee_arr        = array();
        
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {  
                    $waTrusteeRows  = $waTrusteeTable->getWAAfterTrusteesByUserId($userId);              
                    if(!empty($waTrusteeRows)) {
                        foreach ($waTrusteeRows as $trusteeRow) {
                            $waRow          = $waTable->getRowById($trusteeRow['wa_id']);
                            $trustee_arr[]  = array(
                                                'userId'           => $trusteeRow['userId'],
                                                'userNickName'     => $trusteeRow['userNickName'],
                                                'userImage'        => $trusteeRow['userImage'],
                                                'wa_id'            => $trusteeRow['wa_id'],
                                                'is_send'          => $waRow['is_send'],
                                                'status'           => $waRow['status']
                                            );
                        }
                    }
                    if(count($trustee_arr) > 0){
                        $result = $trustee_arr;
                    }else{
                        $result = 0;
                    }
                } catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $this->common->displayMessage($this->view->translate('records_found'),'0',$result,'0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function getWaTrusteesListAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTrusteeTable     = new Application_Model_DbTable_WaTrustee();
        
        $decoded            = $this->common->Decoded();
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
        
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                $waTrusteesRowset = $waTrusteeTable->getWaTrustees($userId);
                $data = array();                
                foreach($waTrusteesRowset as $waTrusteeRow){
                    $data[] = $waTrusteeRow->receiver_id;
                }
                $this->common->displayMessage($this->view->translate('records_found'),"0",$data,"0");        
            }else{
                $this->common->displayMessage($this->view->translate('account_not_exist'),"1",array(),"2");
            }
        }else{
            $this->common->displayMessage($this->view->translate('not_access_service'),"1",array(),"3");
        }
    }
    
    public function sendDelayedMessageAction()
    {        
        $userTable = new Application_Model_DbTable_Users();
        $waTable   = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $decoded            = $this->common->Decoded();
      // var_dump($decoded);die();
        /*$userSecurity    = "afe2eb9b1de658a39e896591999e1b59";
        $userId          = "4";
        $userDeviceId	 = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";// bc86b1ddea663c96
        $wa_id		 = "";
        $userDeviceToken = "627c675551079298efaa23a05159f528b7f363a840752f455dd1e71e2ad09429";//APA91bElAhGnTRYIjDWrPCEmbUZy5qmC7qsv4QXpB1eqgGpokS9GzLSj0ZjJCX54jcnavfDlUQt6K8DHZHJolGSnaLQzeFRXu1ttQKnnmQsB9TaHcJP_-3KwBwhra6hS56ghuu6nLZKLsCevxX0MdEUDG0CCKjp5fQ"
        $message_title	 = "wagotest";
        $delivery_date   = "2015-03-03";
        $delivery_time   = "2:26:00";
        $local_time      = "Asia/Kolkata";
        $is_annually	 = "1";
        $receiver_userset = array("2","3");
        $device_type      = "iphone";
        $receiver_email_phoneset  = array();
        //print_r($receiver_email_phoneset); die;
        $receiver_email_phoneset  = "";
        $is_chat_message        = "0";
        $text                   = "This message is used for test";*/
       // $attachemnts = serialize(array("id"=>"id","url"=>"http://","type"=>"image"));
        //$custom_param = serialize(array("id"=>"id","url"=>"http://","type"=>"image"));
        
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $wa_id              = $decoded['wa_id'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $message_title      = $decoded['message_title'];
        $delivery_date      = $decoded['delivery_date'];
        $delivery_time      = $decoded['delivery_time'];
        $local_time         = $decoded['local_time'];
        $is_annually        = $decoded['is_annually'];
        $receiver_userset   = $decoded['receiver_userset'];
        $device_type        = $decoded['device_type'];
        $receiver_email_phoneset = $decoded['receiver_email_phoneset'];
        $receiver_email_phoneset = json_decode($receiver_email_phoneset);
        $is_chat_message    = isset($decoded['is_chat_message'])?$decoded['is_chat_message']:'0';
        $attachemnts        = isset($decoded['attachments'])?serialize($decoded['attachments']):NULL;
        $custom_param        = isset($decoded['custom_param'])?serialize($decoded['custom_param']):NULL;
        $text        = isset($decoded['text'])?$decoded['text']:'';
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken,$delivery_date,$delivery_time,$local_time));
            $this->common->isUserLogin($userId,$userDeviceId);
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){
                $waRow = false;
                $is_existing_wa = false;
                
                if($wa_id){
                    $is_existing_wa = true;
                    $waRow = $waTable->getRowById($wa_id);
                }                
                
                $date_time =  $delivery_date." ".$delivery_time;
                
                $date_time = date("Y-m-d H:i:s",  strtotime($date_time));
                $utc_time = $this->convertIntoUtcTime($date_time, $local_time);
                        
                $data = array(
                    'user_id'                   => $userId,
                    'type'                      => ($is_chat_message)?Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE:Application_Model_DbTable_Wa::WA_TYPE_GO,
                    'local_time'                => $local_time,
                    'creation_date'             => date("Y-m-d H:i:s"),
                    'delivery_date'             => $date_time,  
                    'first_delivery_date_utc'   => $utc_time,
                    'last_delivery_date_utc'    => $utc_time,
                    'modification_date'         => date("Y-m-d H:i:s"),
                    'text'                      => $text , 
                    'custom_param'             => $custom_param,  
                    'attachments'               => $attachemnts,  
                );
                
                $db = $this->db;
                $db->beginTransaction();
                
                try{
                    if($waRow){
                        $waRow->setFromArray($data);
                        $waRow->save();
                    }else{
                        $waRow = $waTable->createRow($data);
                        $waRow->save();
                    }
                   
                    if($waRow->wa_id){
                        if($is_existing_wa){
                           $waReceiverTable->deleteReceivers($waRow->wa_id);
                        } 
                        
                        if(!empty($receiver_userset)){
                            foreach ($receiver_userset as $receiverId){

                                if($is_chat_message){
                                    $data = array(
                                        'wa_id'                 => $waRow->wa_id,
                                        'receiver_quickblox_id' => $receiverId
                                    );

                                }else{
                                    $data = array(
                                        'wa_id'         => $waRow->wa_id,
                                        'receiver_id'   => $receiverId
                                    );
                                }

                                if($receiverId != $userId){
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }
                            }
                        } else {
                            
                            $this->common->displayMessage("receiver users not exists.","1",array(),"12");  
                        }
                        if(empty($receiver_email_phoneset)) {
                            foreach ($receiver_email_phoneset as $receiverData){
                                $waUserRow = false;

                                if($receiverData->email){
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $receiverData->email);
                                }

                                if(!$waUserRow &&  $receiverData->phone){
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $receiverData->phone);
                                }

                                $data = array(
                                    'wa_id'             => $waRow->wa_id,
                                    'receiver_name'     =>  $receiverData->name,
                                    'receiver_email'    =>  $receiverData->email,
                                    'receiver_phone'    =>  $receiverData->phone,
                                    'receiver_id'       =>  $waUserRow ? $waUserRow->userId:new Zend_Db_Expr("NULL")
                                );

                                if(!$waUserRow || ($waUserRow->userId != $userId) ){
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }                            
                            }
                        }
                    else {
                             $this->common->displayMessage("Receiver Email phoneset exists.","1",array(),"12");
                        }
                    }else{
                        $this->common->displayMessage("There is some error","1",array(),"12");
                    }
                }catch(Exception $e){
                    $db->rollBack();
                    $this->common->displayMessage($e->getMessage(),"1",array(),"12");
                }
                $db->commit();               
                $this->common->displayMessage("WA created successfully","0",array("wa_id"=>$waRow->wa_id),"0");
                
            }else{
                $this->common->displayMessage("User account does not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
    }
    
    
    public function editDelayedMessageAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        
        $decoded            = $this->common->Decoded();
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $wa_id              = $decoded['wa_id'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $delivery_date      = $decoded['delivery_date'];
        $delivery_time      = $decoded['delivery_time'];
        $local_time         = $decoded['local_time'];
        $receiver_userset   = $decoded['receiver_userset'];
        $device_type        = $decoded['device_type'];
        $receiver_email_phoneset = $decoded['receiver_email_phoneset'];
        $receiver_email_phoneset = json_decode($receiver_email_phoneset);
        $is_chat_message    = isset($decoded['is_chat_message'])?$decoded['is_chat_message']:'0';
        $attachemnts        = isset($decoded['attachments'])?serialize($decoded['attachments']):NULL;
        $custom_param        = isset($decoded['custom_param'])?serialize($decoded['custom_param']):NULL;
        $text        = isset($decoded['text'])?$decoded['text']:'';
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken,$delivery_date,$delivery_time,$local_time));
            $this->common->isUserLogin($userId,$userDeviceId);
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){
                $waRow = false;
                $is_existing_wa = false;
                
                if($wa_id){
                    $is_existing_wa = true;
                    $waRow = $waTable->getRowById($wa_id);
                }                
                
                $date_time =  $delivery_date." ".$delivery_time;
                
                $date_time = date("Y-m-d H:i:s",  strtotime($date_time));
                $utc_time = $this->convertIntoUtcTime($date_time, $local_time);
                        
                $data = array(
                    'user_id'                   => $userId,
                    'type'                      => ($is_chat_message)?Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE:Application_Model_DbTable_Wa::WA_TYPE_GO,
                    'local_time'                => $local_time,
                    'creation_date'             => date("Y-m-d H:i:s"),
                    'delivery_date'             => $date_time,  
                    'first_delivery_date_utc'   => $utc_time,
                    'last_delivery_date_utc'    => $utc_time,
                    'modification_date'         => date("Y-m-d H:i:s"),
                    'text'                      => $text , 
                    'custom_param'              => $custom_param,  
                    'attachments'               => $attachemnts,  
                );
                
                $db = $this->db;
                $db->beginTransaction();
                
                try{
                        $waRow->setFromArray($data);
                        $waRow->save();
                    if($waRow->wa_id){
                        if($is_existing_wa){
                           $waReceiverTable->deleteReceivers($waRow->wa_id);
                        } 
                        
                        if(!empty($receiver_userset)){
                            foreach ($receiver_userset as $receiverId){

                                if($is_chat_message){
                                    $data = array(
                                        'wa_id'                 => $waRow->wa_id,
                                        'receiver_quickblox_id' => $receiverId
                                    );

                                }else{
                                    $data = array(
                                        'wa_id'         => $waRow->wa_id,
                                        'receiver_id'   => $receiverId
                                    );
                                }

                                if($receiverId != $userId){
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }
                            }
                        } else {
                            
                            $this->common->displayMessage("receiver users not exists.","1",array(),"12");  
                        }
                        if(empty($receiver_email_phoneset)) {
                            foreach ($receiver_email_phoneset as $receiverData){
                                $waUserRow = false;

                                if($receiverData->email){
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $receiverData->email);
                                }

                                if(!$waUserRow &&  $receiverData->phone){
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $receiverData->phone);
                                }

                                $data = array(
                                    'wa_id'             => $waRow->wa_id,
                                    'receiver_name'     =>  $receiverData->name,
                                    'receiver_email'    =>  $receiverData->email,
                                    'receiver_phone'    =>  $receiverData->phone,
                                    'receiver_id'       =>  $waUserRow ? $waUserRow->userId:new Zend_Db_Expr("NULL")
                                );

                                if(!$waUserRow || ($waUserRow->userId != $userId) ){
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }                            
                            }
                        }
                    else {
                             $this->common->displayMessage("Receiver Email phoneset exists.","1",array(),"12");
                        }
                    }else{
                        $this->common->displayMessage("There is some error","1",array(),"12");
                    }
                }catch(Exception $e){
                    $db->rollBack();
                    $this->common->displayMessage($e->getMessage(),"1",array(),"12");
                }
                $db->commit();               
                $this->common->displayMessage("Delayed updated successfully","0",array("wa_id"=>$waRow->wa_id),"0");
                
            }else{
                $this->common->displayMessage("User account does not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
    }
    
    public function sendChatMessage($waRow){
        
        $userTable = new Application_Model_DbTable_Users();
        $waTable   = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waReceiverRowset = $waReceiverTable->getWaReceivers($waRow->wa_id);
        $waOwnerRow = $userTable->getRowById($waRow->user_id);
        $userRow = $userTable->getRowById($waRow->user_id);
        $token =  $this->quickBloxUserToken($userRow);
        foreach ($waReceiverRowset as $waReceiverRow){
            if($waReceiverRow->receiver_quickblox_id){
                $chat_dialog_id = $this->createDialogId($token,$waReceiverRow->receiver_quickblox_id);
                $custom_param=unserialize($waRow->custom_param);
                $data = array(
                    'chat_dialog_id' => $chat_dialog_id,
                    'message'        => $waRow->text,
                    'recipient_id'  =>  $waReceiverRow->receiver_quickblox_id,
                    "_id"=>$custom_param['_id'],
                    "send_to_chat"=>1,
                    "markable"=>1,
                    "isDelayed"=>1,
                    "deviceId"=>$custom_param['deviceId'],
                    "localId"=>$custom_param['localId'],
                    "thumbDataStr"=>$custom_param['thumbDataStr'],
                );
                 if($waRow->attachments){
                    $data['attachments']= (object)array(unserialize($waRow->attachments));
                }
                //print_r($data);die();
                $userDetails = json_encode($data);
             
                $ch = curl_init($this->quickblox_details_new->api_end_point.'/chat/Message.json');  
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: $token")
                );

                $resultJson = curl_exec($ch);
                $resultArr = json_decode($resultJson, true);
//                if($waRow->attachments){
//                   $this->sendMediaChat($token, $chat_dialog_id, unserialize($waRow->attachments), unserialize($waRow->custom_param),$waReceiverRow->receiver_quickblox_id);
//                }
               // print_r($resultArr);exit();
            }
        }
    }
        
        public function sendMediaChat($token,$chat_dialog_id,$attachments,$custom_param,$blox_id){
            $data = array(
                    'chat_dialog_id' => $chat_dialog_id,
                    'attachments'        => (object)array($attachments),
                    "_id"=>$custom_param['_id'],
                    "send_to_chat"=>1,
                    "markable"=>1,
                    "isDelayed"=>1,
                    "deviceId"=>$custom_param['deviceId'],
                    "localId"=>$custom_param['localId'],
                    "thumbDataStr"=>$custom_param['thumbDataStr'],
                    'recipient_id'  =>  $blox_id,
                );
             
        echo  $userDetails = json_encode($data);
             //   die();
                $ch = curl_init($this->quickblox_details_new->api_end_point.'/chat/Message.json');  
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: $token")
                );

                $resultJson = curl_exec($ch);
                $resultArr = json_decode($resultJson, true);
                
                print_r($resultArr);exit();
        }
        
    public function messageTestAction(){
      // die('ddd');
         $userTable = new Application_Model_DbTable_Users();
         $userRow = $userTable->getRowById(14);
         $token =  $this->quickBloxUserToken($userRow);
         $chat_dialog_id = $this->createDialogId($token,44299);
         $attachment = array("0"=>array('type' => 'image', "https://qb-wa-media.s3.amazonaws.com/2db4524460ee4ff3bc9d78cf67021c1300","id"=>44675),
             "1"=>array('type' => 'image', 'url' => "https://qb-wa-media.s3.amazonaws.com/2db4524460ee4ff3bc9d78cf67021c1300","id"=>44675));
           // $attachments = json_encode($attachment);
        // {"application_id":6,"created_at":"2015-03-27T10:19:43Z","localId":"625","deviceId":"6FFB4720-7F01-48E5-9C1C-3B1012C3A0B5"}
         $att = $attachment;
                $data = array(
                    'chat_dialog_id' =>$chat_dialog_id,
                    'message'        => "This is delayed test",
                    'recipient_id'  =>  44299,
                    
                    "send_to_chat"=>1,
                    "markable"=>1
                );
                
               // print_r($data);die();
             
                echo $userDetails = json_encode($data);
               //die();
                $ch = curl_init($this->quickblox_details_new->api_end_point.'/chat/Message.json');  
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: $token")
                );

                $resultJson = curl_exec($ch);
                $resultArr = json_decode($resultJson, true);
                
                print_r($resultArr);exit();
    
    } 
    
    public function gethistoryAction(){
       
        $waTable = new Application_Model_DbTable_Wa();
        $userTable = new Application_Model_DbTable_Users();
        
        $wa_id = "46";
        $waRow = $waTable->getRowById($wa_id);
        
        $waOwnerRow = $userTable->getRowById($waRow->user_id);
        
        $token =  $this->quickBloxUserToken($waOwnerRow);
        
        $ch = curl_init($this->quickblox_details_new->api_end_point.'/chat/Message.json?chat_dialog_id=5476bf059a6869db4300c82c');  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );

        $resultJson = curl_exec($ch);
        $resultArr = json_decode($resultJson, true);
        print_r($resultArr);
        exit();
        
    }
    
     /**
     * function for Activating or Deactivating wa event and after
     */
    public function eventAfterActivateDeactivateAction(){ 
        
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $decoded = $this->common->Decoded();
       
    
//       $decoded = array(
//            'userSecurity'      => 'afe2eb9b1de658a39e896591999e1b59',
//            'userDeviceId'      => "358152808327040",
//            'userDeviceToken'   => 'APA91bGvG_HZNR6r91SsUFEIw4eSNul9-PU8JvwuCgDYnTRs73_7NARxG18RLKJoMH6-GV9bC9nCrhmEsZl8lz13LVdods_94EoO9e1xTv1ZF1dlpu18kI4f0L3LY_Zc6HC-shVK2C4KjbA9pmaLb0tD1qzisJB2Ug',
//            'userId'            => 4,
//            'waId'              => 4,
//            'status'      => 0
//        );
      
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $waId               = ($decoded['waId']>0)?$decoded['waId']:'0';
        $waType             = $decoded['type'];
        $status             = $decoded['status'];
        
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken,$waType));
//            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){
                
                if($waId>0){

                    if($waRow = $waTable->getRowById($waId)){

                        if($waRow->user_id == $userId){
                            $waRow->status = $status;
                            $waRow->modification_date = date("Y-m-d H:i:s");
                            $waRow->save(); 

                            $this->common->displayMessage("status change successfully","0",array(),"0");
                        }else{
                            $this->common->displayMessage("You can't change status of this wa","1",array(),"5");
                        }

                    }else{
                        $this->common->displayMessage("incorrect wa id","1",array(),"4");
                    }
                }else{//all events
                    
                    $waRowset = $waTable->activateDeactivateEventAfterByWaId($userId,$waType,$status);
                    $this->common->displayMessage("status change successfully","0",array(),"0");
                    
                }               
                
                
                $waDetails = array();
               
                $waDetails[] = array(
                    'status'                => $status
                );
                
                $response = array(
                    "error_code"            => "0",
                    "response_error_key"    => "0",
                    "response_string"       => "WA Details",
                    "result"                => $waDetails
                );
                
                echo json_encode($response);exit();
                
            }else{
                $this->common->displayMessage("User account does not exist","1",array(),"2");
            }
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"3");
         }               
    }
    
    public function getDialogAction(){
        $endpoint="https://api.quickblox.com/";
        $token =  $this->quickBloxUserToken1();
                $data = array(
                    'chat_dialog_id' => $chat_dialog_id,
                    'message'        => $waRow->text,
                    'recipient_id'  =>  $waReceiverRow->receiver_quickblox_id
                );
             
                $userDetails = json_encode($data);
                
                $ch = curl_init($endpoint.'chat/Dialog.json');  
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                //curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: $token")
                );

                $resultJson = curl_exec($ch);
                $resultArr = json_decode($resultJson, true);
                echo "<pre>";
                print_r($resultArr);exit();
    }
    
    public function getDialogHistoryAction(){
       
        $attachment = array(array('type' => 'image', 'url' => "https://qb-wa-media.s3.amazonaws.com/0f1370e6357f4b6f8eb86e279e7c567d00","id"=>42358));

        $data = array(
          'chat_dialog_id' => $chat_dialog_id,
          'message' => "This is a message",
          'attachments' => (object) $attachment,
        );
        
        echo json_encode($data);die();
        $endpoint="https://api.quickblox.com/";
        $token =  $this->quickBloxUserToken1();
                $data = array(
                    'chat_dialog_id' => $chat_dialog_id,
                    'message'        => $waRow->text,
                    'recipient_id'  =>  $waReceiverRow->receiver_quickblox_id
                );
             
                $userDetails = json_encode($data);
                
                $ch = curl_init($endpoint.'chat/Message.json?chat_dialog_id=55100b6429108282d41c55a2');  
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                //curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: $token")
                );

                $resultJson = curl_exec($ch);
                $resultArr = json_decode($resultJson, true);
                echo "<pre>";
                print_r($resultArr);exit();
    }
      
    public function deleteDelayedAction(){
        $decoded = $this->common->Decoded();
        $waTable = new Application_Model_DbTable_Wa();
        $userSecurity = $decoded['userSecurity'];
        $wa_id= $decoded['wa_id'];
        $userId = $decoded['userId'];
        if($userSecurity == $this->servicekey){
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$wa_id));
            if($waRow = $waTable->getRowById($wa_id)){
               if($waRow->is_deleted=='1'){
                   $this->common->displayMessage("Delayed message already deleted","0",array(),"4");
                   
               }else{
                $waRow->is_deleted = "1";
                $waRow->modification_date = date("Y-m-d H:i:s");
                $waRow->save();
               }
                $this->common->displayMessage("Delayed messages deleted successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Id is not correct","1",array(),"2");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
     }
    
    public function convertDateAction($currentTime,$data)
    { 
        $result = array(); 
        if(!empty($data)){ 
            foreach($data as $dateFormat){
                $date           = explode('/',$dateFormat);    
                //$addWeeks       = $date[0].' weeks';
                //$addWeeks       = date("Y-m-d H:i:s",strtotime("+$addWeeks", strtotime($currentTime)));
                $addDays        = $date[0].' days';
                $addDays        = date("Y-m-d H:i:s",strtotime("+$addDays", strtotime($currentTime)));
                $addHours       = $date[1].' hours';
                $addHours       = date("Y-m-d H:i:s",strtotime("+$addHours", strtotime($addDays)));
                $addMinutes     = $date[2].' minutes';
                $addMinutes     = date("Y-m-d H:i:s",strtotime("+$addMinutes", strtotime($addHours)));
                $currentTime    = $addMinutes;
                $result[]       = $currentTime;
            }
        } 
       return $result;
    }
    
    public function sendWaLaterReceiversAction($wa_id)
    {   
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        $waTable            = new Application_Model_DbTable_Wa();
        $waReceiverTable    = new Application_Model_DbTable_WaReceiver();
        
        if($waRow = $waTable->getRowById($wa_id))
        {
            $waReceiverRowset = $waReceiverTable->getWaReceivers($waRow->wa_id);
            $waOwnerRow       = $userTable->getRowById($waRow->user_id);                   
            foreach($waReceiverRowset as $waReceiverRow)
            {
                if($waReceiverRow->receiver_id)
                {             
                   // send push to wa receiver 
                    $resultData =  array(
                                    'userImage' => ($waOwnerRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                    'userId'    => $waOwnerRow->userId,
                                    'userName'  => $waOwnerRow->userNickName,
                                    'wa_id'     => $waRow->wa_id,     
                                    'wa_type'   => $waRow->type     
                                );
                    $message = $this->view->translate('wa_later_received');
                    if($waReceiverRow->userId)
                    {
                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waReceiverRow->userId);
                        foreach ($userLoginDeviceRowset as $loginDeviceRow){
                            if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken){
                                if($loginDeviceRow->userDeviceType == "iphone"){
                                    $payload    = array(
                                                    'aps'   => array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_wa', 'wa_id'=> $waRow->wa_id,'message' => $message)
                                                );
                                }else{
                                    $payload    = array(
                                                    'message'   => $message,
                                                    'type'      => "incoming_wa",
                                                    'result'    => $resultData    
                                                );
                                   $payload = json_encode($payload);
                                }

                                try{
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                                }catch(Exception $ex){
                                    echo $ex->getMessage();
                                    exit();
                                }
                            }
                        }
                    }
                }else if($waReceiverRow->receiver_email) {
                    $params = array(
                                'userNickName'  => $waOwnerRow->userNickName,
                                'baseUrl'       => $this->baseUrl
                            );
                    $this->mail($params,'incoming_wa.phtml',$waReceiverRow->receiver_email,'Welcome To WA-app');
                }else if($waReceiverRow->receiver_phone){
                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                    $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                    $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                    $this->common->sendSms_recommend($waReceiverRow->receiver_phone,$message);
                }
            }
                
            $waEmailReceiverSet = $waReceiverTable->getWaReceiversByWAId($waRow->wa_id);
            if(!empty($waEmailReceiverSet)){
                foreach ($waEmailReceiverSet as $receiver_email){
                    $params = array(
                                'userNickName'  => $waOwnerRow->userNickName,
                                'baseUrl'       => $this->baseUrl
                            );
                    $this->mail($params,'incoming_wa.phtml',$receiver_email,'Welcome To WA-app');
                }
            }
            $waRow->is_send = "3";
            $waRow->save();
        }
    }
}
