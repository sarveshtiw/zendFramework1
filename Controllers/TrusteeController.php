<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class TrusteeController extends My_Controller_Abstract{
    
    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
    
    public function myTrusteesAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
    	if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId));
            if(isset($decoded['userDeviceId']) && isset($decoded['userId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
                $userTable->updateRequestTime("getTrusteeListTime",$userId);
                $trusteeRowset = $trusteeTable->getMyTrustees($userId);
                $trusteeData = array();
                foreach($trusteeRowset as $trusteeRow){
                    $hide_profile = "0";
                    $trusteeName = "";
                    $trusteeFullName = "";
                    
                    /**
                     *  if $friendRow->user_id exist then it is wa user otherwise non wa user
                     */
                    if($trusteeRow->trusteeId){
                            if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId,$trusteeRow->trusteeId)){
                                $hide_profile = $editFriendTrusteeRow->hideProfile;
                                $trusteeName = $editFriendTrusteeRow->name;
                            }else{
                                  $trusteeName = $trusteeRow->userNickName;  
                                
                            }
                            $trusteeFullName = $trusteeRow->userFullName;  
                    }else{
                        $trusteeName = $trusteeRow->trusteeName;  // non wa user
                    }
                    
                    $trusteeData[] = array(
                        'id'                => $trusteeRow->id,
                        'trusteeId'         => ($trusteeRow->trusteeId)?$trusteeRow->trusteeId:"",
                        'hideProfile'       => $hide_profile,
                        'trusteeName'       => $trusteeName,
                        'trusteeFullName'   => $trusteeFullName,
                        'trusteeEmail'      => ($trusteeRow->trusteeEmail)?$trusteeRow->trusteeEmail: ($trusteeRow->userEmail?$trusteeRow->userEmail:""),
                        'trusteePhone'      => ($trusteeRow->trusteePhone)?$trusteeRow->trusteePhone: ($trusteeRow->phoneWithCode?$trusteeRow->phoneWithCode:""),
                        'isOnline'          => ($trusteeRow->isOnline)?$trusteeRow->isOnline:"0",
                        'lastSeenTime'      => ($trusteeRow->lastSeenTime)?$trusteeRow->lastSeenTime:($trusteeRow->acceptDate?$trusteeRow->acceptDate:$trusteeRow->creationDate),
                        'trusteeImage'      => ($trusteeRow->userImage)? $this->makeUrl($trusteeRow->userImage):"",
                        'creationDate'      => $trusteeRow->creationDate,
                        'quickBloxId'       => ($trusteeRow->quickBloxId)?$trusteeRow->quickBloxId:""
                    );
                }
                $this->common->displayMessage("Trustees list","0",$trusteeData,"0");
          
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
        
        exit;
    }
    
    
    /**
     *  getting all incoming and outgoing trustees request done 
     */
    
    public function trusteeRequestAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        if($userSecurity == $this->servicekey){ 
                $this->common->checkEmptyParameter1(array($userId));
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
                $userTable->updateRequestTime("trusteeRequestTime",$userId);
                $trusteeRowset = $trusteeTable->getIncomingOutgoingRequest($userId);
                $trusteeData = array();
                foreach($trusteeRowset as $trusteeRow){
                    $trusteeData[] = array(
                        'id'                => $trusteeRow->id,
                        'trusteeId'         => ($trusteeRow->user_id)?$trusteeRow->user_id:"",
                        'trusteeName'       => ($trusteeRow->trusteeName && $trusteeRow->is_send_request)?$trusteeRow->trusteeName: ($trusteeRow->userFullName ? $trusteeRow->userFullName:""),
                        'trusteeEmail'      => ($trusteeRow->trusteeEmail && $trusteeRow->is_send_request)?$trusteeRow->trusteeEmail: ($trusteeRow->userEmail ?$trusteeRow->userEmail:"" ),
                        'trusteePhone'      => ($trusteeRow->trusteePhone && $trusteeRow->is_send_request)?$trusteeRow->trusteePhone: ($trusteeRow->phoneWithCode ? $trusteeRow->phoneWithCode:""),
                        'isOnline'          => ($trusteeRow->isOnline)?$trusteeRow->isOnline:"0",
                        'lastSeenTime'      => ($trusteeRow->lastSeenTime)?$trusteeRow->lastSeenTime:$trusteeRow->creationDate,
                        'is_send_request'   => $trusteeRow->is_send_request,
                        'trusteeImage'      => ($trusteeRow->userImage)? $this->makeUrl($trusteeRow->userImage):"",
                        'creationDate'      => $trusteeRow->creationDate,
                        'modifyDate'        => $trusteeRow->modifyDate,
                        'quickBloxId'      => $trusteeRow->quickBloxId,
                    );
                }
                $this->common->displayMessage("Incoming outgoing trustees request list","0",$trusteeData,"0");
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
        
        exit;
    }
    
     /**
     *  accept reject incoming trustee request by trustee row id
     */ 
    
    public function acceptRejectTrusteeIncomingRequestAction(){
        
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $waPointsTable = new Application_Model_DbTable_WaPoints();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        
        $trusteeRowId = $decoded['trusteeRowId'];
        $isAccept = $decoded['isAccept'];
        $userId = $decoded['userId'];
        $request_from = $decoded['request_from'];
        
    /* $decoded                 = array(
                                        "userDeviceId"      => "8D40CA44-52B9-4D3F-8671-DF9681BCDC36",
                                        "userId"            => 4,
                                       );
	$userId                  = 4;
        $userDeviceId            = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";
        $userSecurity            = "afe2eb9b1de658a39e896591999e1b59";
	$isAccept                = 1;
	$trusteeRowId            = 21;
        $request_from            = "web";
    */
        
        if(($userSecurity == $this->servicekey) || ($request_from == "web")){
            
            if($request_from !="web"){
                $this->common->checkEmptyParameter1(array($userId,$trusteeRowId));

                if(isset($decoded['userDeviceId']) && isset($decoded['userId'])){
                    $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
                }
            }
            
            if($userRow = $userTable->getRowById($userId)){
                
                if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                    
                  if($trusteeRow->status == "1"){
                        $this->common->displayMessage('Trustee request is already accepted',"1",array(),"6");
                    }
                    
                    if($trusteeRow->status == "2"){
                        $this->common->displayMessage('Trustee request is already deleted',"1",array(),"7");
                    }
               
                    if($trusteeUserRow = $userTable->getRowById($trusteeRow->userId)){
                        if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $trusteeRow->status = $isAccept;
                            $trusteeRow->acceptDate = date("Y-m-d H:i:s");
                            $responseMessage = "Trustee request accepted successfully";
                            
                            $pushMessage = $userRow->userNickName." accepted your trustee request";
                            $userNotificationTable->createTrusteeNotification($trusteeRow);
                            
                            /*Insert in wa_points and update friends table point_id*/
                            $waPointsTable->createPointsRow($trusteeRow->userId,$friendId = 0, $userId, $friend_point_id = 0,$friend_status=1, $trusteeRow->point_id,$trusteeRow->status, $isTrustee = 1);
                            /*wa_points end*/
                            
                        }else{
                            $trusteeRow->status = "2";
                            $responseMessage = "Trustee request rejected successfully";
                            $pushMessage = $userRow->userNickName." rejected your trustee request";
                            
                        }

                         $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                         $trusteeRow->save();
                         
                         /**
                          * check they are friend or not if not then make their friendship
                          */
                         
                         if($isAccept == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                             $otherUserId = $trusteeRow->userId;
                             
                             if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId)){
                                
                                 if($friendRow->status !="1"){
                                    $friendRow->status = "1";
                                    $friendRow->acceptDate = date("Y-m-d H:i:s");
                                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                 }
                                 
                             }else{
                                 $data = array(
                                     'userId'       => $userId,
                                     'friendId'     => $otherUserId,
                                     'status'       => "1",
                                     'acceptDate'   => date("Y-m-d H:i:s"),
                                     'creationDate' => date("Y-m-d H:i:s"),
                                     'modifyDate'   => date("Y-m-d H:i:s"),
                                 );
                                 
                                 $friendRow = $friendTable->createRow($data);
                                 $friendRow->save();
                             }
                         }
                         
                         $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                         
                         $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );
                         

                        $pushType =  ($isAccept == "1") ? "accept_trustee_request":"reject_trustee_request";

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeRow->userId);

                       foreach ($userLoginDeviceRowset as $loginDeviceRow){
                           if($loginDeviceRow->userDeviceType == "iphone"){
                               $payload['aps'] = array('alert'=>$pushMessage,'badge'=>0,'sound' =>'Default','type' =>$pushType, 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                           }else{
                               $payload = array(
                                   'message'   => $pushMessage,
                                   'type'      => $pushType,
                                   'result'    => $resultData
                               );
                               $payload = json_encode($payload);
                           }
                           $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                       }

                         
                        $this->common->displayMessage($responseMessage,"0",array(),"0");
                        
                    }else{
                        $this->common->displayMessage('Trustee account is not available',"1",array(),"4");
                    }

                }else{
                    $this->common->displayMessage('Trustee row id is not available',"1",array(),"2");
                }
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"5");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
     }
     
     /**
      * accept reject by trustee id
      */
     
     public function acceptRejectTrustByTrusteeIdAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeId = $decoded['trusteeId'];
        $isAccept = $decoded['isAccept'];
        $userId = $decoded['userId'];
        
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userId,$trusteeId));

            if(isset($decoded['userDeviceId']) && isset($decoded['userId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
                
            if($userRow = $userTable->getRowById($userId)){
                
                if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeId)){
                    
                  if($trusteeRow->status == "1"){
                        $this->common->displayMessage('Trustee request is already accepted',"1",array(),"6");
                    }
                    
                    if($trusteeRow->status == "2"){
                        $this->common->displayMessage('Trustee request is already deleted',"1",array(),"7");
                    }
               
                    if($trusteeUserRow = $userTable->getRowById($trusteeRow->userId)){
                        if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $trusteeRow->status = $isAccept;
                            $trusteeRow->acceptDate = date("Y-m-d H:i:s");
                            $responseMessage = "Trustee request accepted successfully";
                            
                            $pushMessage = $userRow->userNickName." accepted your trustee request";
                            $userNotificationTable->createTrusteeNotification($trusteeRow);
                        }else{
                            $trusteeRow->status = "2";
                            $responseMessage = "Trustee request rejected successfully";
                            $pushMessage = $userRow->userNickName." rejected your trustee request";
                            
                        }

                         $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                         $trusteeRow->save();
                         
                         /**
                          * check they are friend or not if not then make their friendship
                          */
                         
                         if($isAccept == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                             $otherUserId = $trusteeRow->userId;
                             
                             if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId)){
                                
                                 if($friendRow->status !="1"){
                                    $friendRow->status = "1";
                                    $friendRow->acceptDate = date("Y-m-d H:i:s");
                                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                 }
                                 
                             }else{
                                 $data = array(
                                     'userId'       => $userId,
                                     'friendId'     => $otherUserId,
                                     'status'       => "1",
                                     'acceptDate'   => date("Y-m-d H:i:s"),
                                     'creationDate' => date("Y-m-d H:i:s"),
                                     'modifyDate'   => date("Y-m-d H:i:s"),
                                 );
                                 
                                 $friendRow = $friendTable->createRow($data);
                                 $friendRow->save();
                             }
                         }
                         
                         $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                         
                         $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );
                         

                        $pushType =  ($isAccept == "1") ? "accept_trustee_request":"reject_trustee_request";

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeRow->userId);

                       foreach ($userLoginDeviceRowset as $loginDeviceRow){
                           if($loginDeviceRow->userDeviceType == "iphone"){
                               $payload['aps'] = array('alert'=>$pushMessage,'badge'=>0,'sound' =>'Default','type' =>$pushType, 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                           }else{
                               $payload = array(
                                   'message'   => $pushMessage,
                                   'type'      => $pushType,
                                   'result'    => $resultData
                               );
                               $payload = json_encode($payload);
                           }
                           $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                       }

                         
                        $this->common->displayMessage($responseMessage,"0",array(),"0");
                        
                    }else{
                        $this->common->displayMessage('Trustee account is not available',"1",array(),"4");
                    }

                }else{
                    $this->common->displayMessage('Trustee account is not available',"1",array(),"2");
                }
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"5");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
     }
     
     /**
      * cancel outgoing friend request done
      */
     
     public function cancelTrusteeOutgoingRequestAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        
        if($userSecurity == $this->servicekey){
            $trusteeRowId = $decoded['trusteeRowId'];
            
            if(isset($decoded['userDeviceId']) && isset($decoded['userId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($trusteeRowId));
            
           if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                $trusteeRow->delete();
                $this->common->displayMessage('Trustee request cancel successfully',"0",array(),"0");
            }else{
                $this->common->displayMessage('Trustee row id is not available',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }   
     }
     
     /**
      *  this function will be add multiple trustees at a sign time
      *   done
      */
     
     public function addTrusteesAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();	
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        
        if(!is_array($decoded['trusteeData']) && isset($decoded['userDeviceType']) &&($decoded['userDeviceType'] == "android")){
            $trusteesDataset = json_decode($decoded['trusteeData'],true);
        }else{
            $trusteesDataset = $decoded['trusteeData'];
        }
        
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId));
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            if($userRow = $userTable->getRowById($userId)){
                    $trusteeEmails = array();
                    $trusteePhones = array();
                    
                    foreach($trusteesDataset as $trusteeData){
                       
                        $this->common->checkEmptyParameter1(array($trusteeData['name']));

                        if((trim($trusteeData['email'])=="") && (trim($trusteeData['phone']) == "")){
                            $this->common->checkEmptyParameter1(array($trusteeData['email']));
                        }


                        if(!empty($trusteeEmails) && ($trusteeData['email'] !="") &&  in_array($trusteeData['email'], $trusteeEmails)){
                            $this->common->displayMessage("This records contains duplicate values","1",array(),"3");
                        }

                        if(!empty($trusteePhones) && ($trusteeData['phone'] !="") &&  in_array($trusteeData['phone'], $trusteePhones)){
                            $this->common->displayMessage("This records contains duplicate values","1",array(),"3");
                        }
                        
                        $trusteeEmails[] = $trusteeData['email'];
                        $trusteePhones[] = $trusteeData['phone'];
                    
                    }
                   
                    
                    if(empty($trusteeEmails) && empty($trusteePhones)){
                       $this->common->displayMessage("No records found","1",array(),"2");     
                    }

                    foreach($trusteesDataset as $trusteeData){
                        $trusteeUserRow = false;
                        
                        $trusteeName = trim($trusteeData['name']);
                        $trusteeEmail = trim($trusteeData['email']);
                        $trusteePhone = trim($trusteeData['phone']);
                        
                        $trusteePhoneWithCode = $trusteePhone ? $this->common->getPhoneWithCode($trusteePhone,$userRow->userCountryCode):"";
                        
                        $waUserRowset = false;
                        
                        if($trusteePhone){
                            $waUserRowset = $userTable->getUsersByPhone($trusteePhone);
                        }
                        
                        $requestData = array(
                            'name'      => $trusteeName,
                            'email'     => $trusteeEmail,
                            'phone'     => $trusteePhone,
                            'isFriend'  => $isFriend,
                            'isTrustee' => $isTrustee
                        );
                        
                        if(count($waUserRowset)>1){
                            $this->addMultipleUsersAsFriendAndTrustee($userId,$waUserRowset,$requestData);
                            continue;
                        }
                        
                        if($trusteePhoneWithCode){
                            $trusteeUserRow = $userTable->checkDuplicateRecordByField('phoneWithCode', $trusteePhoneWithCode);
                        }
                        
                        if($trusteeEmail && !$trusteeUserRow){
                            $trusteeUserRow = $userTable->checkDuplicateRecordByField('userEmail', $trusteeEmail);
                        }
                        
                        $data = array(
                            'userId'        => $userId,
                            'trusteeId'     => ($trusteeUserRow)?$trusteeUserRow->userId:new Zend_Db_Expr('null'),
                            'trusteeName'   => $trusteeName,
                            'trusteeEmail'  => $trusteeEmail,
                            'trusteePhone'  => $trusteePhoneWithCode,
                            'status'        => "0",
                            'creationDate'  => date('Y-m-d H:i:s'),
                            'modifyDate'    => date('Y-m-d H:i:s')
                        );
                        
                        $trusteeRow = false;
                        $isTrusteeSave = false;
                        
                        if($trusteeUserRow){
                            $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeUserRow->userId);    
                        }
                        
                        if(!$trusteeRow && $trusteeData['email']){
                            $trusteeRow = $trusteeTable->getRowByUserIdAndEmail($userId, $trusteeData['email']);    
                        }
                        
                        
                        if(!$trusteeRow && ($trusteeData['phone'] !="")){
                            $trusteeRow = $trusteeTable->getRowByUserIdAndPhone($userId, $trusteeData['phone']);
                        }
                        
                        if($trusteeRow){
                            
                            if(($trusteeRow->status == "0") || ($trusteeRow->status == "2")){
                                $trusteeRow->status = "0";
                                $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                                $isTrusteeSave = true;
                                $trusteeRow->save();
                            }
                        }else{
                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
                            $isTrusteeSave = true;
                        }
                        
                        if($isTrusteeSave){
                            $userNotificationTable->createTrusteeNotification($trusteeRow);
                        }
                        
                        /**
                         * save friend request to trustee 
                         */
                        
                        $friendRow = $friendTable->sendFriendRequest($trusteeRow);
                        
                        if($isTrusteeSave){
                                if(!$trusteeUserRow){
                                        $randomNumber = $this->common->randomAlphaNum(10);
                                        $trusteeRow->friend_request_id = $friendRow->id;
                                        $trusteeRow->confirmCode = $randomNumber;
                                        $trusteeRow->save();
                                        $acceptRejectLink = $this->baseUrl.'?trustee_request_id='.$trusteeRow->id.'&confirm_code='.$randomNumber;
                                        $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                                        if($trusteeEmail){
                                            $para= array(
                                                'trustee_name'          => $trusteeName,
                                                'from_name'             => $userRow->userNickName,
                                                'accept_reject_link'    => $acceptRejectLink,
                                                'baseUrl'               => $this->baseUrl
                                            );

                                            $this->user->sendmail($para,'trustee_request.phtml',$trusteeEmail,'Trustee request from one of your contacts or WA-friends!');
                                        }

                                        if($trusteePhoneWithCode && ($trusteeEmail == "")){
                                            
                                            $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                            $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                                            $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                            
                                            $this->common->sendSms_recommend($trusteePhoneWithCode,$message);
                                        }

                                    }else{
                                        // code for  wa user
                                        $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                                                
                                        $resultData =  array(
                                                'userImage' => $userImage,
                                                'userId'    => $userRow->userId,
                                                'userName'  => $userRow->userNickName
                                         );

                                        $message = $userRow->userNickName. " wants to add you as a trustee";

                                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                                        foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                            if($loginDeviceRow->userDeviceType == "iphone"){
                                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_trustee_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                                            }else{
                                                $payload = array(
                                                    'message'   => $message,
                                                    'type'      => "incoming_trustee_request",
                                                    'result'    => $resultData
                                               );
                                                $payload = json_encode($payload);
                                            }
                                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                        }

                                    }
                        }
                        
                    }

                    $this->common->displayMessage("Trustee added successfully","0",array(),"0");
            }else{
                $this->common->displayMessage('Account does not exist',"1",array(),"4");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"5");
        }
     }
     
      
      
     
     
     /**
      *  add reject trustee  request from web page (this request send by user at the time of sign up)
      * 
      *  done
      */
     
     public function resendTrusteeRequestAction(){
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();	
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeRowId = $decoded['trusteeRowId'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$trusteeRowId));
            
            if ($userRow = $userTable->getRowById($userId)){
                
                if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                    
                    if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                        $this->common->displayMessage("Trustee request is already accepted","1",array(),"5");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                        $this->common->displayMessage("Trustee request is already rejected","1",array(),"6");
                    }
                    
                    /**
                     *  if trustee id is available then trustee is wa user otherwise non wa user
                     */
                    if($trusteeRow->trusteeId){
                            // code for wa user
                            if($trusteeUserRow = $userTable->getRowById($trusteeRow->trusteeId)){
                                $userNotificationTable->createTrusteeNotification($trusteeRow);
                                
                                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                                
                                $resultData =  array(
                                        'userImage' => $userImage,
                                        'userId'    => $userRow->userId,
                                        'userName'  => $userRow->userNickName
                                 );
                                
                                $message = $userRow->userNickName. " wants to add you as a trustee";
                                
                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);
                            
                                foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                    if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_trustee_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                                    }else{
                                        $payload = array(
                                            'message'   => $message,
                                            'type'      => "incoming_trustee_request",
                                            'result'    => $resultData
                                       );
                                        $payload = json_encode($payload);
                                    }
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                }
                                
                            }else{
                                 $this->common->displayMessage("Trustee account is not exist","1",array(),"7");
                            }
                            
                     }else{
                        $acceptRejectLink = $this->baseUrl.'?trustee_request_id='.$trusteeRow->id.'&confirm_code='.$randomNumber;
                        $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                        if($trusteeRow->trusteeEmail){
                            $para= array(
                                'trustee_name'          => $trusteeName,
                                'from_name'             => $userRow->userNickName,
                                'accept_reject_link'    => $acceptRejectLink,
                                'baseUrl'               => $this->baseUrl
                            );

                            $this->user->sendmail($para,'trustee_request.phtml',$trusteeRow->trusteeEmail,'Trustee request from one of your contacts or WA-friends!');
                        }
                         
                        if($trusteeRow->trusteePhone){

                            $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                            $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                            $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                            $this->common->sendSms_recommend($trusteeRow->trusteePhone,$message);

                        }
                    }
                    
                    $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                    $trusteeRow->save();
                    
                    $this->common->displayMessage("Trustee request send successfully","0",array(),"0");
                     
                }else{
                    $this->common->displayMessage("Trustee row id is not available","1",array(),"4");
                }
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
         
     }
     
      /**
      *  resend trustee request using userId and trusteeId
      * 
      *  done
      */
     
     public function resendTrusteeRequestByTrusteeidAction(){
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeId = $decoded['trusteeId'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$trusteeId));
            
            if ($userRow = $userTable->getRowById($userId)){
                
                if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeId)){
                    
                    if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                        $this->common->displayMessage("Trustee request is already accepted","1",array(),"5");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                        $this->common->displayMessage("Trustee request is already rejected","1",array(),"6");
                    }
                    
                    /**
                     *  if trustee id is available then trustee is wa user otherwise non wa user
                     */
                    if($trusteeRow->trusteeId){
                            // code for wa user
                            
                            $userImage = ($userRow->userImage) ? $this->makeUrl($userRow->userImage):"";
                        
                            if($trusteeUserRow = $userTable->getRowById($trusteeRow->trusteeId)){
                                $userNotificationTable->createTrusteeNotification($trusteeRow);

                                $resultData =  array(
                                        'userImage' => $userImage,
                                        'userId'    => $userRow->userId,
                                        'userName'  => $userRow->userNickName
                                 );
                                
                                $message = $userRow->userNickName. " wants to add you as a trustee";
                                
                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);
                            
                                foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                    if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_trustee_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage' => $userImage);
                                    }else{
                                        $payload = array(
                                            'message'   => $message,
                                            'type'      => "incoming_trustee_request",
                                            'result'    => $resultData
                                       );
                                        $payload = json_encode($payload);
                                    }
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                }
                                
                            }else{
                                 $this->common->displayMessage("Trustee account is not exist","1",array(),"7");
                            }
                            
                     }else{
                        $acceptRejectLink = $this->baseUrl.'?trustee_request_id='.$trusteeRow->id.'&confirm_code='.$randomNumber;
                        $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                        if($trusteeRow->trusteeEmail){
                            $para= array(
                                'trustee_name'          => $trusteeName,
                                'from_name'             => $userRow->userNickName,
                                'accept_reject_link'    => $acceptRejectLink,
                                'baseUrl'               => $this->baseUrl
                            );

                            $this->user->sendmail($para,'trustee_request.phtml',$trusteeRow->trusteeEmail,'Trustee request from one of your contacts or WA-friends!');
                        }
                         
                        if($trusteeRow->trusteePhone){

                            $message = $userRow->userNickName." wants to add you as a trustee please follow this ".$tinyUrl;
                   //         $this->common->sendSms_recommend($trusteeRow->trusteePhone,$message);

                        }
                    }
                    
                    $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                    $trusteeRow->save();
                    
                    $this->common->displayMessage("Trustee request send successfully","0",array(),"0");
                     
                }else{
                    $this->common->displayMessage("Trustee id is not available","1",array(),"4");
                }
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
         
     }
     
     
     /**
      *  add reject trustee for non wa users 
      *  from web page
      */
     
     public function addRejectAction(){
         
         $TrusteeTable = new Application_Model_DbTable_Trustee();
         $userTable = new Application_Model_DbTable_Users();
         $userSettingTable = new Application_Model_DbTable_UserSetting();
         $friendTable = new Application_Model_DbTable_Friends();
         $userNotificationTable = new Application_Model_DbTable_UserNotifications();
         
         $id = $this->getRequest()->getQuery('id','');
         $confirmCode = $this->getRequest()->getQuery('confirmCode','');
         
         $trusteeRow = $TrusteeTable->getRowByIdAndCode($id,$confirmCode);
         
         if($trusteeRow){
             $trusteeUserRow = $userTable->getRowById($trusteeRow->userId);
             $userRow = ($trusteeRow->trusteeId) ? $userTable->getRowById($trusteeRow->trusteeId):false;
         }
         
         if($this->getRequest()->isPost()){
             $accept_reject = $this->getRequest()->getPost('accept_reject');
             $userFullName      = trim($this->getRequest()->getPost('userFullName',''));
             $userName          = trim($this->getRequest()->getPost('userName',''));
             $userPhone         = trim($this->getRequest()->getPost('userPhone',''));
             $userPassword      = trim($this->getRequest()->getPost('userPassword',''));
             $userDob           = $this->getRequest()->getPost('dob','');
                 
             $errors = array();
             
             if($accept_reject == "accept"){
                if($userCompleteName ==""){
                    $errors['userCompleteName'] = "Required field";
                }

                if($userFullName ==""){
                    $errors['userFullName'] = "Required field";
                }
                
                if($userName ==""){
                    $errors['userName'] = "Required field";
                }
                
                if($userPhone ==""){
                    $errors['userPhone'] = "Required field";
                }
                
                if($userPassword ==""){
                    $errors['userPassword'] = "Required field";
                }
                
                 if($userDob ==""){
                    $errors['dob'] = "Required field";
                }
                
                if(!isset($errors['userPhone'])){
                    if($userTable->checkDuplicateRecordByField('userPhone',$userPhone)){
                        $errors['userPhone'] = "Already exist";
                    }
                }
                
                if(!isset($errors['userName'])){
                    if($userTable->checkDuplicateRecordByField('userName',$userName)){
                        $errors['userName'] = "Already exist";
                    }
                }
                
                if(empty($errors)){
                    /**
                     *  sign up process
                     */
                    $data = array(
                        'userCompleteName'  => $userCompleteName,
                        'userFullName'      => $userFullName,
                        'userName'          => $userName,
                        'userPassword'      => md5($userPassword),
                        'userPhone'         => $userPhone,
                        'userEmail'         => $trusteeRow->trusteeEmail,
                        'userStatus'        => "1",
                        "userThemeColor"    => "6",
                        'userModifieddate'  => date("Y-m-d H:i:s"),
                        "lastSeenTime"      => date("Y-m-d H:i:s")
                   );
                    
                    $newUserRow = $userTable->createRow($data);
                    $newUserRow->save();
                    
                    $trusteeRow->trusteeId = $newUserRow->userId;
                    $trusteeRow->status = "1";
                    $trusteeRow->acceptDate = date("Y-m-d H:i:s");
                    $trusteeRow->trusteeDob = date("Y-m-d",strtotime($userDob));
                    $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                    $trusteeRow->save();
                    
                    $userNotificationTable->createTrusteeNotification($trusteeRow);
                    
                    if($trusteeRow->friend_request_id && ($friendRow = $friendTable->getRowById($trusteeRow->friend_request_id))){
                        $friendRow->friendId = $newUserRow->userId;
                        $friendRow->status = "1";
                        $friendRow->acceptDate = date("Y-m-d H:i:s");
                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                        $friendRow->save();
                        
                    }else{
                        $friendTable->addTrusteeAsFriend($trusteeRow);  // add friend 
                    }
                    
                    $this->view->success_message = "Request accepted successfully";
                    
                    if($trusteeUserRow){
                        
                        $resultData =  array(
                                'userImage' => ($trusteeRow->trusteeImage)?$this->makeUrl($trusteeRow->trusteeImage):(($newUserRow && $newUserRow->userImage)?$this->makeUrl($newUserRow->userImage):""),
                                'userId'    => $newUserRow->userId,
                                'userName'  => ($trusteeRow->trusteeName)?$trusteeRow->trusteeName:(($newUserRow && $newUserRow->userFullName)?$newUserRow->userFullName:"")
                         );

                        $message = ucfirst($trusteeRow->trusteeName). " accepted your trustee request";
                        
                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                        foreach ($userLoginDeviceRowset as $loginDeviceRow){
                            if($loginDeviceRow->userDeviceType == "iphone"){
                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'accept_trustee_request', 'userId'=>$resultData['userId'],'userName'=>$resultData['userName'],'userImage'=>$resultData['userImage']);
                            }else{
                                $payload = array(
                                    'message'   => $message,
                                    'type'      => "accept_trustee_request",
                                    'result'    => $resultData
                               );
                                $payload = json_encode($payload);
                            }
                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                        }
                    }
                }
           }else{
               
                $resultData =  array(
                        'userImage' => ($trusteeRow->trusteeImage)?$this->makeUrl($trusteeRow->trusteeImage):"",
                        'userId'    => "",
                        'userName'  => ($trusteeRow->trusteeName)?$trusteeRow->trusteeName:""
                 );

                $message = ucfirst($trusteeRow->trusteeName). " Trustee request rejected successfully";

                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                foreach ($userLoginDeviceRowset as $loginDeviceRow){
                    if($loginDeviceRow->userDeviceType == "iphone"){
                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'accept_trustee_request', 'userId'=>$resultData['userId'],'userName'=>$resultData['userName'],'userImage'=>$resultData['userImage']);
                    }else{
                        $payload = array(
                            'message'   => $message,
                            'type'      => "reject_trustee_request",
                            'result'    => $resultData
                       );
                        $payload = json_encode($payload);
                    }
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                }
               $trusteeRow->status = "2";
               $trusteeRow->modifyDate = date("Y-m-d H:i:s");
               $trusteeRow->save();
               $this->view->success_message = "Request rejected successfully";
           }
             
         }             
         
         if($trusteeRow){
             $this->view->userRow = $userRow;
             $this->view->trusteeUserRow = $trusteeUserRow;
         }
         $this->view->errors = $errors;
         $this->view->trusteeRow = $trusteeRow;
     }
     
     /**
      *  function for delete trustee
      */
     
     public function deleteTrusteeAction(){
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $waPointsTable = new Application_Model_DbTable_WaPoints();
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeRowId = $decoded['trusteeRowId'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$trusteeRowId));
            
            if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                $trusteeRow->status = "2";
                $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                $trusteeRow->save();
                /*Insert in wa_points and update friends table point_id*/
                $waPointsTable->deleteWaPointRow($trusteeRow->userId,$friendId=0,$trusteeRow->trusteeId,$isTrustee=1);
                /*wa_points end*/
                
                $this->common->displayMessage("Trustee delete successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Trustee id is not correct","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
     }
     
     /**
      *  function for delete trustee by user id and trustee id
      */
     
     public function deleteTrusteeRelationAction(){
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeId = $decoded['trusteeId']; 
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$trusteeId));
            
            if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeId)){
                if($trusteeRow->status == "2"){
                    $this->common->displayMessage("Trustee already deleted","0",array(),"4");
                }else{
                    $trusteeRow->status = "2";
                    $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                    $trusteeRow->save();

                    $this->common->displayMessage("Trustee delete successfully","0",array(),"0");
                }
            }else{
                $this->common->displayMessage("Trustee id is not correct","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
     }
     
     /**
      *  function for edit trustee details
      */
     
     public function editTrusteeDetailsAction(){
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        
        $userSecurity = $decoded['userSecurity'];
        $trusteeRowId = $decoded['trusteeRowId'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$trusteeRowId,$userId));
            
            if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                
                if(isset($decoded['trusteeName']) && (trim($decoded['trusteeName']) !="")){
                    $trusteeRow->trusteeName = $decoded['trusteeName'];
                }
                
                if(isset($decoded['trusteeEmail']) && (trim($decoded['trusteeEmail']) !="")){
                    $trusteeRow->trusteeEmail = $decoded['trusteeEmail'];
                }
                
                if(isset($decoded['trusteePhone']) && (trim($decoded['trusteePhone']) !="")){
                    $trusteeRow->trusteePhone = $decoded['trusteePhone'];
                }
                
                if(isset($decoded['trusteeAge']) && (trim($decoded['trusteeAge']) !="")){
                    $trusteeRow->trusteeAge = $decoded['trusteeAge'];
                }
                
                if(isset($decoded['trusteeDob']) && (trim($decoded['trusteeDob']) !="")){
                    $trusteeRow->trusteeDob = $decoded['trusteeDob'];
                }
                
                $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                $trusteeRow->save();
                
                $this->common->displayMessage("Trustee details edit successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Trustee row id is not correct","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"1");
        }
     }
     
     /**
      *  list of users whome i am trustee
      */
     
     public function whomIamTrusteeAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userDeviceId = $decoded['userDeviceId'];
        
        $userId = $decoded['userId'];
        
    //  $userId = "3";
    //  $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
   
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                $whomIamTrusteeUserRowset = $trusteeTable->whomIamTrustee($userId);
                
                $reponseData = array();
                
                foreach($whomIamTrusteeUserRowset as $trusteeUserRow){
                    
                    if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId,$trusteeUserRow->userId)){
                        $userName = $editFriendTrusteeRow->name;
                    }else{
                        $userName = $trusteeUserRow->userNickName;  
                    }
                    
                    $reponseData[] = array(
                        'trustee_row_id'    => $trusteeUserRow->id,
                        'userId'            => $trusteeUserRow->userId,
                        'userName'          => $userName,
                        'userFullName'      => $trusteeUserRow->userFullName,
                        'userImage'         => ($trusteeUserRow->userImage)? $this->makeUrl($trusteeUserRow->userImage):"",
                        'quickBloxId'       => ($trusteeRow->quickBloxId)?$trusteeRow->quickBloxId:""
                    );
                    
                }
                
                $this->common->displayMessage("Users lisiting whom am trustee","0",$reponseData,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
            exit;
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
     }
     
     
     public function addFriendAsTrusteeAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $friendId = $decoded['friendId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){
                    if(($friendUserRow = $userTable->getRowById($friendId)) && $userRow->isActive()){
                        if(($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)) && ($friendRow->status == "1")){
                            
                            $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $friendId);
                            $isTrusteeSave = false;

                            if($trusteeRow){
                                if($trusteeRow->status == "1"){
                                    $this->common->displayMessage('Already trustee',"1",array(),"4");
                                }else{
                                    $trusteeRow->status = "0";
                                    $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                                    $trusteeRow->save();
                                    $isTrusteeSave = true;
                                }
                            }else{
                                $data = array(
                                    'userId'        => $userId,
                                    'trusteeId'     => $friendId,
                                    'status'        => "0",
                                    'creationDate'  => date('Y-m-d H:i:s'),
                                    'modifyDate'    => date('Y-m-d H:i:s')
                                );

                                $isTrusteeSave = true;

                                $trusteeRow = $trusteeTable->createRow($data);
                                $trusteeRow->save();

                            }
                            
                            if($isTrusteeSave){
                                $userNotificationTable->createTrusteeNotification($trusteeRow);
                                
                                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                                
                                $resultData =  array(
                                        'userImage' => $userImage,
                                        'userId'    => $userRow->userId,
                                        'userName'  => $userRow->userNickName
                                 );

                                $message = $userRow->userNickName. " wants to add you as a trustee";

                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendUserRow->userId);

                                foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                    if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_trustee_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                                    }else{
                                        $payload = array(
                                            'message'   => $message,
                                            'type'      => "incoming_trustee_request",
                                            'result'    => $resultData
                                       );
                                        $payload = json_encode($payload);
                                    }
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                }
                            }
                            $this->common->displayMessage("Trustee added successfully","0",array(),"0");
                        }else{
                            $this->common->displayMessage('Friend is not exist',"1",array(),"5");
                        }
                    }else{
                        $this->common->displayMessage('Friend account is not exist',"1",array(),"4");
                    }
                
                    
            }else{
                $this->common->displayMessage('User account does not exist',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
     }
     
     
     /**
      *  add multiple users as a friend and trustee of same phone contact
      */
     
     public function addMultipleUsersAsFriendAndTrustee($userId,$waUserRowset,$requestData){
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();	
        
        $name = $requestData['name'];
        $phone = $requestData['phone'];
        $email = isset($requestData['email']) ? $requestData['email']:"";

        $isFriend = $requestData['isFriend'];
        $isTrustee = $requestData['isTrustee'];
        
        if($isTrustee == "1"){
            $isFriend = "1";
        }
        
        foreach ($waUserRowset as $waUserRow){
            $waUserId = $waUserRow->userId;
            
            if($isFriend == "1"){
                $data = array(
                    'userId'       => $userId,
                    'friendId'     => $waUserId,
                    'friendName'   => $name,
                    'friendPhone'  => $phone,
                    'friendEmail'  => $email,  
                    'status'        => '0',
                    'creationDate'  => date('Y-m-d H:i:s'),
                    'modifyDate'    => date('Y-m-d H:i:s')
                );
                
                $isFriendSave = false;
                $send_friend_notification = false;
                
                if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$waUserId)){
                    if($friendRow->status == "0"){
                        $friendRow->modifyDate = date('Y-m-d H:i:s');
                        $friendRow->save();
                        $send_friend_notification = true;
                    }

                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                         $friendRow->userId = $userId;
                         $friendRow->friendId = $waUserId;
                         $friendRow->modifyDate = date('Y-m-d H:i:s');
                         $friendRow->status = "0";
                         $friendRow->save();
                         $send_friend_notification = true;
                         $isFriendSave = true;
                    } 
                }else{
                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save();
                    $send_friend_notification = true;
                    $isFriendSave = true;
                }  
                
                if($send_friend_notification){
                    $userNotificationTable->createFriendNotification($friendRow);
                }
                
                if($isFriendSave && ($isTrustee !="1")){
                     if($friendUserRow = $userTable->getRowById($waUserId)){
                         
                         $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                         
                         $resultData =  array(
                                 'userImage' => $userImage,
                                 'userId'    => $userRow->userId,
                                 'userName'  => $userRow->userNickName
                          );

                         $message = $userRow->userNickName. " wants to add you as a friend";

                         $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserId);

                         foreach ($userLoginDeviceRowset as $loginDeviceRow){

                             if($loginDeviceRow->userDeviceType == "iphone"){
                                 $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_friend_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                             }else{
                                 $payload = array(
                                     'message'   => $message,
                                     'type'      => "incoming_friend_request",
                                     'result'    => $resultData
                                );
                                 $payload = json_encode($payload);
                             }
                             $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                         }

                     }
                     // push notification code end
                }
                
            }
            
            if($isTrustee == "1"){
                $data = array(
                    'userId'        => $userId,
                    'trusteeId'     => $waUserId,
                    'trusteeName'   => $name,
                    'trusteeEmail'  => $email,
                    'trusteePhone'  => $phone,
                    'status'        => "0",
                    'creationDate'  => date('Y-m-d H:i:s'),
                    'modifyDate'    => date('Y-m-d H:i:s')
                );
                // done
                $isTrusteeSave = false;
                $send_trustee_notification = false;
                
                if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$waUserId)){
                    if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                        $trusteeRow->delete();

                        $trusteeRow = $trusteeTable->createRow($data);
                        $trusteeRow->save();
                        $send_trustee_notification = true;
                        
                    }

                     if($trusteeRow->status == "0"){
                         $isTrusteeSave = true;
                         $send_trustee_notification = true;
                     }

                }else{
                    $trusteeRow = $trusteeTable->createRow($data);
                    $trusteeRow->save();
                    $isTrusteeSave = true;
                    $send_trustee_notification = true;
                }
                
                if($send_trustee_notification){
                    $userNotificationTable->createTrusteeNotification($trusteeRow);
                }
                
                /**
                 * push notification work
                 */
                if($isTrusteeSave){
                    if($trusteeUserRow = $userTable->getRowById($waUserId)){
                        $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                        $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );

                        $message = $userRow->userNickName. " wants to add you as a trustee";

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserId);

                        foreach ($userLoginDeviceRowset as $loginDeviceRow){

                            if($loginDeviceRow->userDeviceType == "iphone"){
                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_trustee_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                            }else{
                                $payload = array(
                                    'message'   => $message,
                                    'type'      => "incoming_trustee_request",
                                    'result'    => $resultData
                               );
                                $payload = json_encode($payload);
                            }
                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                        }

                    }
                }
                /**
                 * push notification work end
                 */
            }
             
        }
     }
    


   public function acceptRejectTrusteeIncomingRequestWebAction(){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        
	$trusteeRowId = $_REQUEST['trusteeRowId']; 
        $isAccept = $_REQUEST['isAccept'];
        $userId = $this->loggedUserRow->userId;

            
            if($userRow = $userTable->getRowById($userId)){
                
                if($trusteeRow = $trusteeTable->getRowById($trusteeRowId)){
                     
                  if($trusteeRow->status == "1"){
                        $this->common->displayMessage('Trustee request is already accepted',"1",array(),"6");
                    }
                    
                    if($trusteeRow->status == "2"){
                        $this->common->displayMessage('Trustee request is already deleted',"1",array(),"7");
                    }
               
                    if($trusteeUserRow = $userTable->getRowById($trusteeRow->userId)){
                        if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $trusteeRow->status = $isAccept;
                            $trusteeRow->acceptDate = date("Y-m-d H:i:s");
                            $responseMessage = "Trustee request accepted successfully";
                            
                            $pushMessage = $userRow->userNickName." accepted your trustee request";
                            $userNotificationTable->createTrusteeNotification($trusteeRow);
                        }else{
                            $trusteeRow->status = "2";
                            $responseMessage = "Trustee request rejected successfully";
                            $pushMessage = $userRow->userNickName." rejected your trustee request";
                            
                        }

                         $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                         $trusteeRow->save();
                         
                         /**
                          * check they are friend or not if not then make their friendship
                          */
                         
                         if($isAccept == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                             $otherUserId = $trusteeRow->userId;
                             
                             if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId)){
                                
                                 if($friendRow->status !="1"){
                                    $friendRow->status = "1";
                                    $friendRow->acceptDate = date("Y-m-d H:i:s");
                                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                 }
                                 
                             }else{
                                 $data = array(
                                     'userId'       => $userId,
                                     'friendId'     => $otherUserId,
                                     'status'       => "1",
                                     'acceptDate'   => date("Y-m-d H:i:s"),
                                     'creationDate' => date("Y-m-d H:i:s"),
                                     'modifyDate'   => date("Y-m-d H:i:s"),
                                 );
                                 
                                 $friendRow = $friendTable->createRow($data);
                                 $friendRow->save();
                             }
                         }
                         
                         
//                         $resultData =  array(
//                                'userImage' => $userRow->userImage,
//                                'userId'    => $userRow->userId,
//                                'userName'  => $userRow->userNickName
//                         );
//                         
//
//                        $pushType =  ($isAccept == "1") ? "accept_trustee_request":"reject_trustee_request";
//
//                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeRow->userId);
//
//                       foreach ($userLoginDeviceRowset as $loginDeviceRow){
//                           if($loginDeviceRow->userDeviceType == "iphone"){
//                               $payload['aps'] = array('alert'=>$pushMessage,'badge'=>0,'sound' =>'Default','type' =>$pushType, 'userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userRow->userImage);
//                           }else{
//                               $payload = array(
//                                   'message'   => $pushMessage,
//                                   'type'      => $pushType,
//                                   'result'    => $resultData
//                               );
//                               $payload = json_encode($payload);
//                           }
//                           $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
//                       }

                         
                        $this->common->displayMessage($responseMessage,"0",array(),"0");
                        
                    }else{
                        $this->common->displayMessage('Trustee account is not available',"1",array(),"4");
                    }

                }else{
                    $this->common->displayMessage('Trustee row id is not available',"1",array(),"2");
                }
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"5");
            }
        
        
        exit;
     }
     

}


?>
