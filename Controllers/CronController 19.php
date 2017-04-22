<?php

class CronController extends My_Controller_Abstract {
    
    public function preDispatch(){
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
    
    public function indexAction(){ 
        $this->eventTestamentDeliverAction();
    }
       
    public function eventTestamentDeliverAction()
    {      
        $userTable                   = new Application_Model_DbTable_Users();
        $waTestamentTable            = new Application_Model_DbTable_WaTestaments();
        $userSettingTable            = new Application_Model_DbTable_UserSetting();
        $waEventDetailTable          = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteesTable             = new Application_Model_DbTable_WaTrustee();
                    
        $waEventDetailRowset         = $waTestamentTable->getRecordsForPushNoitifications();
	
        if(!empty($waEventDetailRowset)) {
            foreach ($waEventDetailRowset as $waEventDetailRow)
            {
                $testamentRow     = $waTestamentTable->getRowById($waEventDetailRow->testament_id);
                
                $waEventRow = $waEventDetailTable->getRowById($waEventDetailRow->id);
                $send_alert_notification_to_owner = false;
                $send_alert_notification_to_trustee = false;
   
                if($waEventDetailRow->usertype == '1')
                { 
                    $send_alert_notification_to_owner = true;
                    $waEventRow->is_status = '1';
                    $this->sendAlertToOwnerAction($waEventDetailRow->user_id, Application_Model_DbTable_WaTestaments::TESTAMENT_VITAL_CKECK_EVENT);
                    $waTestamentTable->sendVitalCheckUsers($waEventDetailRow->testament_id,'1');
                    $waEventRow->save();
                }
                else
                { 
                    $send_alert_notification_to_trustee = true;  
                    $trustee_set = $waTrusteesTable->getTrusteesByTestamentId($waEventDetailRow->user_id, $waEventDetailRow->testament_id);

                    if(!empty($trustee_set))
                    {
                        foreach($trustee_set as $row)
                        { 
                            if($send_alert_notification_to_trustee){ 
                               $this->sendAlertToTrusteeAction($row->user_id, $row->receiver_id, Application_Model_DbTable_WaTestaments::TESTAMENT_VITAL_CKECK_EVENT);
                            }
                        }
                    } 
                    $waEventRow->is_status = '1';
                    $waTestamentTable->sendVitalCheckUsers($waEventDetailRow->testament_id,'2');
                    $waEventRow->save();
                }
            }
        }
        $this->eventReceivedTestamentAction();
        exit();
    }
     
    public function eventReceivedTestamentAction()
    { 
        $waReceiverTable             = new Application_Model_DbTable_WaReceiver();
        $userSettingTable            = new Application_Model_DbTable_UserSetting();
        $waEventDetailTable          = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteesTable             = new Application_Model_DbTable_WaTrustee();
        $waTestamentTable            = new Application_Model_DbTable_WaTestaments();
    
        $waEventDetailRowset      = $waEventDetailTable->getAllRowByUserIdAndVitalType();
        if(!empty($waEventDetailRowset))
        {
            foreach ($waEventDetailRowset as $waEventDetailRow)
            {
                $send_testament_to_receiver     = false;
                $trustee_set      = $waTrusteesTable->getTrusteesByTestamentId($waEventDetailRow->user_id, $waEventDetailRow->testament_id);
                $count_send_trustee = count($trustee_set);
                $count_response_trustee = $waEventTrusteeResponseTable->countSendResponse($waEventDetailRow->testament_id);
                if($count_response_trustee > ($count_send_trustee/2))
                {  
                    $send_testament_to_receiver = true;
                    $testamentReceivers = $waReceiverTable->getRecieversByTestamentId($waEventDetailRow->testament_id);
 
		    if(!empty($testamentReceivers))
                    {   
                        foreach($testamentReceivers as $row)
                        { 
                            if ($send_testament_to_receiver){                       
                                $this->sendEventToReceiverAction($row->userId, Application_Model_DbTable_WaTestaments::TESTAMENT_VITAL_CKECK_EVENT, "testament", $waEventDetailRow->testament_id);
                            }
                        }                        
		        $waTestamentTable->sendVitalCheckUsers($waEventDetailRow->testament_id,'3');
		        $waEventRow->save();
                    }
                }
            }
        }
        exit();
    }

    /**
     * function for sending event alert to owner
     */
    
    public function sendAlertToOwnerAction($user_id,$alert_type)
    {
        $userTable   = new Application_Model_DbTable_Users();
      
        $message     = "Are you alive?";
        $is_after    = ($alert_type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)?"1":"0";
        
        $userRow     = $userTable->getRowById($user_id);
        
        if($userRow && $userRow->isActive()){
            $this->getUserDeviceDetailsAction($user_id, $alert_type, $message);
        }
    }
    
    /**
     * function for sending event alert to trustee
     */
    
    public function sendAlertToTrusteeAction($user_id,$receiver_id,$alert_type)
    {
        $userTable              = new Application_Model_DbTable_Users();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
        $waEventResponseTable   = new Application_Model_DbTable_WaEventTrusteeResponse();
    
        $userRow  = $userTable->getRowById($user_id);
        $message  = "Did your friend ".$userRow->userNickName." pass away?";
        $is_after = ($alert_type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)?"1":"0";
 
	if(!$waEventResponseTable->getRowByDetailIdAndUserId($receiver_id, $user_id))
	{ 
            $userRow  = $userTable->getRowById($receiver_id);

            if($userRow && $userRow->isActive()){
                $this->getUserDeviceDetailsAction($userRow->userId, $alert_type, $message);
            }
	}   
    }

    /**
     *  function for sending event to receiver
     */
    
    public function sendEventToReceiverAction($user_id, $alert_type, $type, $testament_id = false)
    {
        $userTable         = new Application_Model_DbTable_Users();
        $waTable           = new Application_Model_DbTable_Wa();
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable   = new Application_Model_DbTable_WaReceiver();

        if($type == 'testament'){
            $testamentRow = $waTestamentsTable->getRowById($testament_id);
          
            if($testamentRow)
            {
                $testamentReceiverRowset = $waReceiverTable->getRecieversByTestamentId($testament_id);
                $testamentOwnerRow = $userTable->getRowById($testamentRow->user_id);
           	
                foreach($testamentReceiverRowset as $row)
                {
                    if ($row['receiver_id'])
                    {
                        if($row['user_id']){  
                            $userRow      = $userTable->getRowById($row['user_id']);
                            $receiverRow  = $userTable->getRowById($row['receiver_id']);
                           
                            $message = $testamentOwnerRow->userNickName." send testament Message";
                            
                            if($receiverRow && $receiverRow->isActive()){
                                $this->getUserDeviceDetailsAction($row['receiver_id'], $alert_type, $message);
                            }
                          
                            if($receiverRow->userEmail)
                            {
                                $params = array(
                                    'userNickName'      => $testamentOwnerRow->userNickName,
                                    'baseUrl'           => $this->baseUrl
                                );
                                $this->mail($params,'incoming_testament.phtml',$receiverRow->userEmail,'Welcome To WA-app');
                            }
                            
                            if($receiverRow->userPhone)
                            {
                                $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                $ios_app_link     = $this->common->get_tiny_url($this->ios_app_link);

                                $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                $this->common->sendSms_recommend($receiverRow->userPhone,$message);
                            }  
                        }
                    }
                }
            }
        }        
    }
    
    public function getUserDeviceDetailsAction($user_id,$alert_type,$message)
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
                                            'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message
                                        );
                    }
                    else
                    {  
                        $resultData =  array(
                                        'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                        'userId'     => $userRow->userId,
                                        'userName'   => $userRow->userNickName
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
    
    /**
     * diliver wa-sos using cron job that will be run after every mins
    */
    
    public function eventSosDeliverAction()
    {           
        $userTable                   = new Application_Model_DbTable_Users();
        $waSosTable                  = new Application_Model_DbTable_WaSos();
        $userSettingTable            = new Application_Model_DbTable_UserSetting();
        $waSosEventDetailTable       = new Application_Model_DbTable_WaSosEventSendDetails();
        $waSosReceiversTable         = new Application_Model_DbTable_WaSosReceiver();
        $waSosEventResponseTable     = new Application_Model_DbTable_WaSosEventResponse();
                    
        $waSosEventDetailRowset      = $waSosEventDetailTable->getRecordsForPushNoitifications();
	
        if(!empty($waSosEventDetailRowset)) {
            foreach ($waSosEventDetailRowset as $waSosEventDetailRow)
            {
                $sosReceiverRows = $waSosReceiversTable->getRowsBySOSId($waSosEventDetailRow->sos_id);
                $sosEventResponseRow = $waSosEventResponseTable->getResponseBySosId($waSosEventDetailRow->sos_id);
                if(count($sosEventResponseRow) < 1){
                    $waSosEventDetailRow->event_alert_count = $waSosEventDetailRow->event_alert_count + 1;
                    $waSosEventDetailRow->event_send_date   = date('Y-m-d H:i:s', strtotime("5 minutes"));
                    $waSosEventDetailRow->modification_date = date('Y-m-d H:i:s');
                    $waSosEventDetailRow->save();
                    
                    foreach($sosReceiverRows as $sosReceiverRow){
                        $this->sendAlertToSOSVolunteerAction($sosReceiverRow->user_id, $sosReceiverRow->receiver_id, $sosReceiverRow->sos_id, "wa_sos_activate_request");
                    }
                }
            }
        }
       exit();
    } 
    
    /**
     * function for sending event alert to SOS Volunteer
     */
    
    public function sendAlertToSOSVolunteerAction($user_id,$receiver_id,$sos_id,$alert_type)
    {
        $userTable                  = new Application_Model_DbTable_Users();
        $userSettingTable           = new Application_Model_DbTable_UserSetting();
        $waTrusteeTable             = new Application_Model_DbTable_WaTrustee();
        $waSOSEventResponseTable    = new Application_Model_DbTable_WaEventTrusteeResponse();
        $sosTable                   = new Application_Model_DbTable_WaSos();
    
        $userRow            = $userTable->getRowById($user_id);
        $sosRow             = $sosTable->getRowById($sos_id);
        $message            = $userRow->userNickName." - ".$sosRow->emergency_message;
      
	if(!$waEventResponseTable->getRowByDetailIdAndUserId($receiver_id, $user_id))
	{ 
            if($userRow && $userRow->isActive()){
                $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($receiver_id);            

                if(!empty($userLoginDeviceSet))
                {
                    foreach ($userLoginDeviceSet as $loginDeviceRow)
                    {
                        if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                        {
                            if($loginDeviceRow->userDeviceType == "iphone")
                            {
                               $payload['aps']  = array(
                                                    'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message, 'sos_id'     => $sos_id
                                                );
                            }
                            else
                            {  
                                $resultData = array(
                                                'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                                'userId'     => $userRow->userId,
                                                'userName'   => $userRow->userNickName,
                                                'sos_id'     => $sos_id
                                            );

                                $payload   = array(
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
    }
    
    /**
     * function for sending event alert to SOS Receivers
    */
 
    public function sendAlertToSOSReceiversAction($user_id,$receiver_id,$sos_id,$alert_type)
    {
        $userTable              = new Application_Model_DbTable_Users();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
        $waEventResponseTable   = new Application_Model_DbTable_WaEventTrusteeResponse(); 
        $sosTable               = new Application_Model_DbTable_WaSos();
        
        $userRow            = $userTable->getRowById($user_id);
        $sosRow             = $sosTable->getRowById($sos_id);
        $message            = $userRow->userNickName." - ".$sosRow->emergency_message;
      
	if(!$waEventResponseTable->getRowByDetailIdAndUserId($receiver_id, $user_id))
	{ 
            if($userRow && $userRow->isActive()){
                $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($receiver_id);            

                if(!empty($userLoginDeviceSet))
                {
                    foreach ($userLoginDeviceSet as $loginDeviceRow)
                    {
                        if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                        {
                            if($loginDeviceRow->userDeviceType == "iphone")
                            {
                               $payload['aps']  = array(
                                                    'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message, 'sos_id'     => $sos_id
                                                );
                            }
                            else
                            {  
                                $resultData    =  array(
                                                    'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                                    'userId'     => $userRow->userId,
                                                    'userName'   => $userRow->userNickName,
                                                    'sos_id'     => $sos_id
                                                );

                                $payload   = array(
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
    }
    
    /* function created by sarvesh for WA-Guard notifications */
    
    public function eventWaGuardDeliverAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $userSettingTable               = new Application_Model_DbTable_UserSetting();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();
        
        $waGuardEventDetailRowset       = $sosEmergencySendDetailsTable->getWAGuardRecordsForPushNoitifications();
        
        if(!empty($waGuardEventDetailRowset)) {            
            $alert_type = "wa_guard_request";
            foreach ($waGuardEventDetailRowset as $waGuardEventDetailRow)
            {
                $waGuardEventDetailRow->event_send = '1';
                $next_time  = date('Y-m-d H:i:s', strtotime("1 minutes"));
                $waGuardEventDetailRow->next_event_send_time = $next_time;
                $waGuardEventDetailRow->save();
                
                $sosEmergencyTable->sendEmergencyReceivers($sos_emergency_id);
                    
                $this->sendAlertToWAGuardOwnerAction($waGuardEventDetailRow->user_id, $waGuardEventDetailRow->id, $waGuardEventDetailRow->user_id, $alert_type);
            }  
        }
       $this->sendWAGuardReceiversNotificationAction();	
       exit();
    }
        
    public function sendAlertToWAGuardOwnerAction($user_id,$event_id,$alert_type)
    {
        $userTable       = new Application_Model_DbTable_Users();
      
        $message         = "Are you still doing fine?";
        $userRow         = $userTable->getRowById($user_id);
        
        if($userRow && $userRow->isActive()){
            $this->sendWAEmergencyNotificationsAction($user_id, $event_id, $alert_type, $message);
        }
        exit();
    }
   
    public function sendWAEmergencyNotificationsAction($user_id,$event_id,$alert_type,$message)
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
                       $payload['aps']  = array(
                                            'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message, 'event_id'   => $event_id
                                        );
                    }
                    else
                    {  
                        $resultData = array(
                                        'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                        'userId'     => $userRow->userId,
                                        'userName'   => $userRow->userNickName,
                                        'event_id'   => $event_id
                                    );

                        $payload   = array(
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
    
    public function sendWAGuardReceiversNotificationAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $userSettingTable               = new Application_Model_DbTable_UserSetting();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $waGuardEventDetailRowset       = $sosEmergencySendDetailsTable->getWAGuardSendNoitificationUsers();
        $currentTime                    = date('Y-m-d H:i:s');
        
        if(!empty($waGuardEventDetailRowset)){            
            $alert_type = "wa_guard_receiver_request";
            foreach ($waGuardEventDetailRowset as $waGuardEventDetailRow)
            {
		$sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waGuardEventDetailRow->sos_emergency_id);
		                
                foreach ($sosReceiversRows as $sosReceiversRow){   
                    if(!empty($sosReceiversRow->user_id)){ 
                        $receiverRow  = $userTable->getRowById($sosReceiversRow->receiver_id);

                        $message = $userRow->userNickName." ".$this->view->translate('send_guard_emergency_message');

                        if($receiverRow && $receiverRow->isActive()){
                            $this->getUserDeviceDetailsAction($sosReceiversRow->receiver_id, $alert_type, $message);

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
            }
        }
    }
    
    public function sendWaTrackReceiversNotificationAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $userSettingTable               = new Application_Model_DbTable_UserSetting();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $waTrackEventDetailRowset       = $sosEmergencySendDetailsTable->getWATrackRecordsForPushNoitifications();
        $currentTime                    = date('Y-m-d H:i:s');
        
        if(!empty($waTrackEventDetailRowset)){            
            $alert_type = "wa_track_receiver_request";
            foreach ($waTrackEventDetailRowset as $waTrackEventDetailRow)
            {
		$sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waTrackEventDetailRow->sos_emergency_id);
		                
                foreach ($sosReceiversRows as $sosReceiversRow){   
                    if(!empty($sosReceiversRow->user_id)){ 
                        $receiverRow  = $userTable->getRowById($sosReceiversRow->receiver_id);
                        $userRow      = $userTable->getRowById($sosReceiversRow->user_id);

                        $message      = $userRow->userNickName." ".$this->view->translate('send_track_emergency_message');

                        if($receiverRow && $receiverRow->isActive()) {
                            $this->getUserDeviceDetailsAction($sosReceiversRow->receiver_id, $alert_type, $message);
                            $userNotificationsTable->createNotification($sosReceiversRow->user_id, $sosReceiversRow->receiver_id, $message, Application_Model_DbTable_NotificationType::SOS_RECEIVER_RESPONSE);
                            
                        }
                    }

                    if(!empty($receiverRow->userEmail)) {
                        $params = array(
                                    'userNickName'      => $userRow->userNickName,
                                    'baseUrl'           => $this->baseUrl
                                );
                        $this->mail($params,'incoming_wa_track.phtml',$receiverRow->userEmail,'Welcome To WA-app');
                    }

                    if(!empty($receiverRow->userPhone)){
                        $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                        $ios_app_link     = $this->common->get_tiny_url($this->ios_app_link);

                        $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                        $this->common->sendSms_recommend($receiverRow->userPhone,$message);
                    }                                
                }
            }
        }
    }
    
    public function stopWaTrackReceiversNotificationAction(){
        $userTable                      = new Application_Model_DbTable_Users();
        $userSettingTable               = new Application_Model_DbTable_UserSetting();
        $sosSettingTable                = new Application_Model_DbTable_WaSosSetting();
        $sosEmergencyTable              = new Application_Model_DbTable_WaSosEmergency();
        $sosEmergencySendDetailsTable   = new Application_Model_DbTable_WaSosEmergencySendDetails();
        $sosReceiverTable               = new Application_Model_DbTable_WaSosReceiver();
        $userNotificationsTable         = new Application_Model_DbTable_UserNotifications(); 
       
        $waTrackEventDetailRowset       = $sosEmergencySendDetailsTable->getWaTrackSendNoitificationUsers();
        $currentTime                    = date('Y-m-d H:i:s');
        
        if(!empty($waTrackEventDetailRowset)){            
            $alert_type = "wa_track_receiver_request";
            foreach ($waTrackEventDetailRowset as $waTrackEventDetailRow)
            {
                $waTrackEventDetailRow->event_status = '0';
                $waTrackEventDetailRow->save();
                
                if($waTrackEventDetailRow->is_start != 2)
                {
                    $waTrackEventDetailRow->is_start = '2';
                    $waTrackEventDetailRow->save();
                    
                    $sosReceiversRows = $sosReceiverTable->getRowsByEmergencyId($waTrackEventDetailRow->sos_emergency_id);
                  
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
                }
            }
        }
    }
}
