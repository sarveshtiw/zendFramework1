<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class FriendController extends My_Controller_Abstract{
    
    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
    /**
     *  this web service will provide the list of my WA friends 
     *  without pagination 
    */
    
    public function myFriendsAction(){
        
        $friendTable            = new Application_Model_DbTable_Friends();
        $userTable              = new Application_Model_DbTable_Users();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $trusteeTable           = new Application_Model_DbTable_Trustee(); 
        $blockUserTable         = new Application_Model_DbTable_BlockUsers();
        $decoded                = $this->common->Decoded();
//         $decoded = array(
//                "last_request_time" => "2015-07-25 11:18:44",
//                "userDeviceId" => "EAD52633-D594-4258-A9C5-AC3A44F11907",
//                "userId" => 198,
//                "userSecurity" => "afe2eb9b1de658a39e896591999e1b59"
//            );
        $userSecurity           = $decoded['userSecurity'];
        $userId                 = $decoded['userId'];
        
        $last_request_time      = isset($decoded['last_request_time'])?$decoded['last_request_time']:'';
       
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId));
            $userTable->updateRequestTime("getFriendListTime",$userId);
                 $friendRowset = $friendTable->getMyFriendList($userId, $last_request_time,true);
                 $friendData   = array();
                foreach($friendRowset as $friendRow){

                    if(($last_request_time == "") || (strtotime($friendRow->modifyDate) > $last_request_time) || (strtotime($friendRow->userModifieddate) > $last_request_time)){
                        $hide_profile   = "0";
                        $friendName     = "";
                        $friendFullName = "";
                        /**
                         *  if $friendRow->user_id exist then it is wa user otherwise non wa user
                         */
                        if($friendRow->user_id){
                                if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId,$friendRow->user_id)){
                                    $hide_profile = $editFriendTrusteeRow->hideProfile;
                                    $friendName = $editFriendTrusteeRow->name;
                                }else{
                                    $friendName = $friendRow->userNickName;  

                                }
                                $friendFullName = $friendRow->userFullName;
                        }else{
                            $friendName = $friendRow->friendName;  // non wa user
                        }

                        $trusteeRow     = false;
                        $blockUserRow   = false;

                        if($friendRow->user_id){
                                $trusteeRow   = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$friendRow->user_id);
                                $blockUserRow = $blockUserTable->getBlockRelation($friendRow->user_id,$userId);
                            }
                           
                        $friendData[]       = array(
                            'id'                => $friendRow->id,
                            'friendId'          => ($friendRow->user_id)?$friendRow->user_id:"0",
                            'isTrustee'         => ($trusteeRow && ($trusteeRow->status == "1"))?"1":"0",
                            'hideProfile'       => $hide_profile,
                            'status'            => $friendRow->status,
                            'friendName'        => $friendName,
                            'friendFullName'    => $friendFullName,
                            'friendEmail'       => ($friendRow->friendEmail && $friendRow->is_send_request)?$friendRow->friendEmail:($friendRow->userEmail?$friendRow->userEmail:""),
                            'friendCountryCode' => ($friendRow->userCountryCode)?$friendRow->userCountryCode:"",
                            'friendPhone'       => ($friendRow->friendPhone)?$friendRow->friendPhone:($friendRow->phoneWithCode?$friendRow->phoneWithCode:""),
                            'friendFbId'        => ($friendRow->facebookId)?$friendRow->facebookId: ($friendRow->userFbId?$friendRow->userFbId:""),
                            'friendTwitterId'   => ($friendRow->userTwitterId)?$friendRow->userTwitterId:"",
                            'isOnline'          => ($friendRow->isOnline)?$friendRow->isOnline:"0",
                            'lastSeenTime'      => ($friendRow->lastSeenTime)?$friendRow->lastSeenTime:($friendRow->acceptDate?$friendRow->acceptDate:$friendRow->creationDate), 
                            'friendImage'       => ($friendRow->userImage)?$this->makeUrl($friendRow->userImage):"",
                            'creationDate'      => $friendRow->creationDate,
                            'quickBloxId'       => ($friendRow->quickBloxId)?$friendRow->quickBloxId:"",
                            'profileStatus'     => ($friendRow->profileStatus)?$friendRow->profileStatus:"",
                            'user_location'     => ($friendRow->user_location)?$friendRow->user_location:"",
                            'block_by_user'     => $blockUserRow,
                        );
                    }  
                }
                 $responseData = array(
                    'response_string'       => 'Friends list',
                     'error_code'           => '0',
                     'response_error_key'   => '0',
                     'last_request_time'    => date("Y-m-d H:i:s"),
                     'result'               => $friendData
                );

                echo json_encode($responseData);exit;
           
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
        
    /**
     *  getting all incoming and outgoing friend request done
     *  
     * without pagination 
     */
    
    public function friendRequestAction(){
        $friendTable        = new Application_Model_DbTable_Friends();
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
        $decoded            = $this->common->Decoded();
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userId));
            $userTable->updateRequestTime("friendRequestTime",$userId);
                $friendRowset = $friendTable->getPendingFriends($userId);
                $friendData   = array();
                foreach($friendRowset as $friendRow){
                    $friendData[] = array(
                        'id'                => $friendRow->id,
                        'friendId'          => ($friendRow->user_id)?$friendRow->user_id:"0",
                        'friendName'        => ($friendRow->friendName && $friendRow->is_send_request)?$friendRow->friendName: ($friendRow->userNickName ? $friendRow->userNickName:""),
                        'friendFullName'    => ($friendRow->friendName && $friendRow->is_send_request)?$friendRow->friendName: ($friendRow->userFullName ? $friendRow->userFullName:""),
                        'friendEmail'       => ($friendRow->friendEmail && $friendRow->is_send_request)?$friendRow->friendEmail: ($friendRow->userEmail ? $friendRow->userEmail:""),
                        'friendCountryCode' => ($friendRow->userCountryCode)?$friendRow->userCountryCode:"",
                        'friendPhone'       => ($friendRow->friendPhone)?$friendRow->friendPhone: ($friendRow->phoneWithCode ? $friendRow->phoneWithCode:""),
                        'isOnline'          => ($friendRow->user_id)?$userSettingTable->isUserOnline($friendRow->user_id):"0",
                        'lastSeenTime'      => ($friendRow->lastSeenTime)?$friendRow->lastSeenTime:$friendRow->creationDate,
                        'is_send_request'   => $friendRow->is_send_request,
                        'friendImage'       => ($friendRow->userImage)?$this->makeUrl($friendRow->userImage):"",
                        'creationDate'      => $friendRow->creationDate,
                        'modifyDate'        => $friendRow->modifyDate,
                        'quickBloxId'       => ($friendRow->quickBloxId)?$friendRow->quickBloxId:""
                    );
                }
                $this->common->displayMessage("Incoming outgoing friends request list","0",$friendData,"0");
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
        
        exit;
    }
    
    /**
     * function for count incoming friends and trustees request
     */
    
    public function countIncomingRequestAction(){
        $friendTable    = new Application_Model_DbTable_Friends();
        $trusteeTable   = new Application_Model_DbTable_Trustee();
        $userTable      = new Application_Model_DbTable_Users();
        
        $decoded        = $this->common->Decoded();
        $userSecurity   = $decoded['userSecurity'];
        $userId         = $decoded['userId'];
        $userDeviceId   = $decoded['userDeviceId'];
     /*
        $userId         = 4;
        $userSecurity   = "afe2eb9b1de658a39e896591999e1b59";
        $userDeviceId   = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";
     */                               
         if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userSecurity));
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            if($userRow = $userTable->getRowById($userId)){
                // incoming friend request 
                $select = $friendTable->select()->setIntegrityCheck(false)
                        ->from('friends',array('*'))
                        ->joinLeft('users', 'users.userId = friends.userId',array('user_id' => 'userId','userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','userImage','isOnline','lastSeenTime'))
                        ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
                        ->where('users.userStatus=?',Application_Model_DbTable_Users::STATUS_ACTIVE)
                        ->where('friends.friendId =?',$userId);
                
                if($userRow->friendRequestTime){
                    $select->where('friends.modifyDate >?',$userRow->friendRequestTime);
                }
                           
                $friendRowset = $friendTable->fetchAll($select);
               
                $friendCount = count($friendRowset);
                
                // count outgoing accepted request by other
                
                $acceptFriendRequestSelect = $friendTable->select()
                                                ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                                                ->where('userId =?',$userId);
                
                if($userRow->getFriendListTime){
                    $acceptFriendRequestSelect->where('friends.acceptDate >?',$userRow->getFriendListTime);
                }
                
                $acceptFriendRowset = $friendTable->fetchAll($acceptFriendRequestSelect);
                
                $acceptFriendCount = count($acceptFriendRowset);
                
                // incoming trustee request 
                 
                $select1 = $trusteeTable->select()->setIntegrityCheck(false)
                        ->from('trustees',array('*'))
                        ->joinLeft('users', 'users.userId = trustees.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','isOnline','lastSeenTime'))
                        ->where('trustees.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
                        ->where('users.userStatus=?',Application_Model_DbTable_Users::STATUS_ACTIVE)
                        ->where('trustees.trusteeId =?',$userId);
               
                if($userRow->trusteeRequestTime){
                    $select1->where('trustees.modifyDate >?',$userRow->trusteeRequestTime);
                }  
                 
                $trusteeRowset = $trusteeTable->fetchAll($select1);
                $trusteeCount  = count($trusteeRowset);
                
                
                // count outgoing accepted trustee request by other
                
                $acceptTrusteeRequestSelect = $trusteeTable->select()
                                                ->where('trustees.status =?',"1")
                                                ->where('userId =?',$userId);
                
                if($userRow->getTrusteeListTime){
                    $acceptTrusteeRequestSelect->where('trustees.acceptDate >?',$userRow->getTrusteeListTime);
                }
                
                $acceptTrusteeRowset = $trusteeTable->fetchAll($acceptTrusteeRequestSelect);
                
                $acceptTrusteeCount  = count($acceptTrusteeRowset);
                 
                $response = array(
                    'friend_count'          => $friendCount,
                    'accept_friend_count'   => $acceptFriendCount,
                    'trustee_count'         => $trusteeCount,
                    'accept_trustee_count'  => $acceptTrusteeCount,
                );
                
               $this->common->displayMessage("incoming friends and trustees request count","0",$response,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"3");
            }
            
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"2");
         }    
        
    }
    /**
    *  accept reject incoming friend request from device done
     *  without pagination
    */ 
   
    /**
     * accept reject incoming friend request by friend row id 
     */
    
   public function acceptRejectIncomingRequestAction(){
       
        $userNotificationTable   = new Application_Model_DbTable_UserNotifications();
        $friendTable             = new Application_Model_DbTable_Friends();
        $userTable               = new Application_Model_DbTable_Users();
        $userSettingTable        = new Application_Model_DbTable_UserSetting();
        $trusteeTable            = new Application_Model_DbTable_Trustee();
        $waPointsTable           = new Application_Model_DbTable_WaPoints();
        
        $decoded                 = $this->common->Decoded();
        $userSecurity            = $decoded['userSecurity'];
        $friendRowId             = $decoded['friendRowId'];
        $isAccept                = $decoded['isAccept'];
        $userId                  = $decoded['userId'];
        $request_from            = $decoded['request_from'];
        
    /*    $decoded                 = array(
                                        "userDeviceId"      => "8D40CA44-52B9-4D3F-8671-DF9681BCDC36",
                                        "userId"            => 4,
                                       );
	$userId                  = 4;
        $userDeviceId            = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";
        $userSecurity            = "afe2eb9b1de658a39e896591999e1b59";
	$isAccept                = 1;
	$friendRowId             = 50;
        $request_from            = "";  */
    
        if(($userSecurity == $this->servicekey) || ($request_from == "web")){
            
            if($request_from !="web"){
                $this->common->checkEmptyParameter1(array($userId,$friendRowId));
                if(isset($decoded['userDeviceId'])){
                    $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
                }
            }else{
                if(!$this->loggedUserRow){
                    $this->common->displayMessage("logout","15",array(),"15");
                }
                $userId = $this->loggedUserRow->userId;
            }
            
            if($userRow = $userTable->getRowById($userId)){
                    if($friendRow = $friendTable->getRowById($friendRowId)){
                        
                        if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $this->common->displayMessage('Friend request is already accepted',"1",array(),"6");
                        
                            
                        }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                            $this->common->displayMessage('Friend request is already deleted',"1",array(),"7");
                        
                            
                        }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE){
                                if($friendUserRow = $userTable->getRowById($friendRow->userId)){
                                    if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                                        $friendRow->status = $isAccept;
                                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                                        $friendRow->acceptDate = date("Y-m-d H:i:s");
                                        $friendRow->save();
                                        
                                        $userNotificationTable->createFriendNotification($friendRow);
                                       
                                        /*Insert in wa_points and update friends table point_id*/
                                         $waPointsTable->createPointsRow($friendRow->userId,$userId, $trusteeId = 0, $friendRow->point_id,$friendRow->status, $trustee_point_id = 0, $trustee_status = 0, $isTrustee = 0);
                                        /*wa_points end*/
                                       
                                        $responseMessage = "Friend request accepted successfully";
                                        
                                        $resultData =  array(
                                            'userImage' => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                            'userId'    => $userRow->userId,
                                            'userName'  => $userRow->userNickName
                                        );
                                        
                                        $message = ucfirst($userRow->userNickName). " accepted your friend request";

                                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendRow->userId);

                                        foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                            if($loginDeviceRow->userDeviceType == "iphone"){
                                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'accept_friend_request','userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userRow->userImage);
                                            }else{
                                                $payload = array(
                                                    'message'   => $message,
                                                    'type'      => "accept_friend_request",
                                                    'result'    => $resultData
                                               );
                                                $payload = json_encode($payload);
                                            }
                                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                        }

                                    }else{
                                        $friendRow->status = Application_Model_DbTable_Friends::STATUS_DELETED;
                                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                                        $friendRow->save();
                                        $responseMessage = "Friend request rejected successfully";
                                        
                                        $trusteeTable->removeTrusteeRelationShip($friendRow->userId, $friendRow->friendId);
                                    }

                                    $this->common->displayMessage($responseMessage,"0",array(),"0");
                                }else{
                                    $this->common->displayMessage('Friend account is not available',"1",array(),"4");
                                }
                        }else{
                            
                        }
               
                    }else{
                        $this->common->displayMessage('Friend account is not available',"1",array(),"2");
                    }
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"5");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
     }
    
      /**
       * accept reject incoming friend request by friend id
       */
      public function acceptRejectRequestByFriendIdAction(){
          
        $userNotificationTable  = new Application_Model_DbTable_UserNotifications();
        $friendTable            = new Application_Model_DbTable_Friends();
        $userTable              = new Application_Model_DbTable_Users();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $trusteeTable           = new Application_Model_DbTable_Trustee();
        
        $decoded                = $this->common->Decoded();
        $userSecurity           = $decoded['userSecurity'];
        $friendId               = $decoded['friendId'];
        $isAccept               = $decoded['isAccept'];
        $userId                 = $decoded['userId'];
        $userDeviceId           = $decoded['userDeviceId'];
    /*
	$userId                  = 4;
        $userSecurity            = "afe2eb9b1de658a39e896591999e1b59";
	$isAccept                = 1;
	$friendId                = 19;        
        $userDeviceId            = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";
    */
	  
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userId,$friendId,$userDeviceId));
            
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                    if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$friendId)){
                        
                        if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $this->common->displayMessage('Friend request is already accepted',"1",array(),"5");
                        
                            
                        }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                            $this->common->displayMessage('Friend request is already deleted',"1",array(),"6");
                        
                            
                        }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE){
                                if($friendUserRow = $userTable->getRowById($friendRow->userId)){
                                    if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                                        $friendRow->status = $isAccept;
                                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                                        $friendRow->acceptDate = date("Y-m-d H:i:s");
                                        $friendRow->save();
                                        
                                        $userNotificationTable->createFriendNotification($friendRow);
                                        
                                        $responseMessage = "Friend request accepted successfully";
                                        
                                        $resultData =  array(
                                            'userImage' => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                            'userId'    => $userRow->userId,
                                            'userName'  => $userRow->userNickName
                                        );
                                        
                                        $message = ucfirst($userRow->userNickName). " accepted your friend request";

                                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendRow->userId);

                                        foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                            if($loginDeviceRow->userDeviceType == "iphone"){
                                                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'accept_friend_request','userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userImage);
                                            }else{
                                                $payload = array(
                                                    'message'   => $message,
                                                    'type'      => "accept_friend_request",
                                                    'result'    => $resultData
                                               );
                                                $payload = json_encode($payload);
                                            }
                                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                        }


                                    }else{
                                        $friendRow->status = Application_Model_DbTable_Friends::STATUS_DELETED;
                                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                                        $friendRow->save();
                                        $responseMessage = "Friend request rejected successfully";
                                        
                                        $trusteeTable->removeTrusteeRelationShip($friendRow->userId, $friendRow->friendId);
                                    }

                                    $this->common->displayMessage($responseMessage,"0",array(),"0");
                                }else{
                                    $this->common->displayMessage('Friend account is not available',"1",array(),"2");
                                }
                        }else{
                            
                        }
               
                    }else{
                        $this->common->displayMessage('Friend account is not available',"1",array(),"2");
                    }
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"4");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
     }
     
   public function sendFriendRequestAction(){
       
        $userNotificationTable   = new Application_Model_DbTable_UserNotifications(); 
        $friendTable             = new Application_Model_DbTable_Friends();
        $decoded                 = $this->common->Decoded();
        $userSecurity            = $decoded['userSecurity'];
       
        //$userSecurity            = "afe2eb9b1de658a39e896591999e1b59";
        //$userDeviceId            = "8D40CA44-52B9-4D3F-8671-DF9681BCDC36";
      
        if($userSecurity == $this->servicekey){
            $userId     = $decoded['userId'];
            $friendId   = $decoded['friendId'];
           
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }            
         /*
            $userId                  = 4;
            $friendId                = 19;           
            if(isset($userId) && isset($userDeviceId)){
                $this->common->isUserLogin($userId,$userDeviceId);
            }
         */
            $this->common->checkEmptyParameter1(array($userId,$friendId));
           
            /**
             * check request send by login user 
            */
            
            if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$friendId)){
                
                if($friendRow->status == "0"){
                    if($friendRow->userId == $userId){
                        $this->common->displayMessage("This user is already in your outgoing friends list","1",array(),"2");
                    }else{
                        $this->common->displayMessage("This user is already in your incoming friends list","1",array(),"3");
                    }

                }elseif($friendRow->status == "1"){

                    $this->common->displayMessage("This user is already in your friends list","1",array(),"4");

                }elseif($friendRow->status == "2"){  // delete this rejected friend row then add new one
                    $friendRow->userId = $userId;
                    $friendRow->friendId = $friendId;
                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                    $friendRow->status = "0";
                    $friendRow->save();
                    $userNotificationTable->createFriendNotification($friendRow);
                    
                    $this->common->displayMessage("Friend request send successfully","0",array(),"0");
                }

            }else{
                $data = array(
                    'userId'        => $userId,
                    'friendId'      => $friendId,
                    'status'        => "0",
                    'creationDate'  => date("Y-m-d H:i:s"),
                    'modifyDate'    => date("Y-m-d H:i:s")
                );

                $friendRow = $friendTable->createRow($data);
                $userNotificationTable->createFriendNotification($friendRow);
                
                $this->common->displayMessage("Friend request send successfully","0",array(),"0");
            }
                      
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
            
     }
     
     /**
      * cancel outgoing friend request done
      */
     
     public function cancelOutgoingRequestAction(){
         
        $friendTable  = new Application_Model_DbTable_Friends();
        $userTable    = new Application_Model_DbTable_Users();
        
        $decoded      = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        
        if($userSecurity == $this->servicekey){
            $friendRowId = $decoded['friendRowId'];
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($friendRowId));
            
            if($friendRow = $friendTable->getRowById($friendRowId)){
                $friendRow->delete();
                $this->common->displayMessage('Friend request cancel successfully',"0",array(),"0");
            }else{
                $this->common->displayMessage('Friend row id is not available',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }   
     }
     
     /**
      *  add friends and trustees done from search wa user and  near by users
      *  (Multiple users at a time)
      */
     
     public function addFriendAndTrusteeAction(){
         
         $userTable             = new Application_Model_DbTable_Users();
         $friendTable           = new Application_Model_DbTable_Friends();
         $trusteeTable          = new Application_Model_DbTable_Trustee();
         $userSettingTable      = new Application_Model_DbTable_UserSetting();
         $userNotificationTable = new Application_Model_DbTable_UserNotifications();
         
         $decoded       = $this->common->Decoded();
         $userSecurity  = $decoded['userSecurity'];
         if($userSecurity == $this->servicekey){
             $userId        = $decoded['userId'];
             $friendsIdSet  = $decoded['friendsIds'];
             $trusteesIdSet = $decoded['trusteesIds'];
             if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
             }
             $this->common->checkEmptyParameter1(array($userId));
            if($userRow = $userTable->getRowById($userId)){
                /**
                 *  check missing parameters
                */
                
                foreach($trusteesIdSet as $trusteeId){
                    if(!in_array($trusteeId, $friendsIdSet)){
                        array_push($friendsIdSet, $trusteeId);
                    }
                }
                
                /**
                 *  code for adding new friends
                */
                
                foreach($friendsIdSet as $friendId){
                        $data = array(
                            'userId'        => $userId,
                            'friendId'      => $friendId,
                            'status'        => '0',
                            'creationDate'  => date('Y-m-d H:i:s'),
                            'modifyDate'    => date('Y-m-d H:i:s')
                        );
                    
                        $isFriendSave = false;
                       $send_user_notification = false;
                            
                    if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$friendId)){
                        
                        if($friendRow->status == "0"){
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->save();
                            $send_user_notification = true;
                        }
                        
                        if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                            $friendRow->userId = $userId;
                            $friendRow->friendId = $friendId;
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->status = "0";
                            $friendRow->save();
                            
                            $send_user_notification = true;
                            $isFriendSave = true;
                        }
                        
                    }else{
                        
                        $friendRow = $friendTable->createRow($data);
                        $friendRow->save();
                        $isFriendSave = true;
                        $send_user_notification = true;
                    }    
                        
                        if($send_user_notification){
                            $userNotificationTable->createFriendNotification($friendRow);
                        }
                    
                        /**
                        * push notification work
                        */
                       if(!in_array($friendId, $trusteesIdSet)){
                            if($isFriendSave && ($friendUserRow = $userTable->getRowById($friendId))){
                                    
                                    $resultData =  array(
                                            'userImage' => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                            'userId'    => $userRow->userId,
                                            'userName'  => $userRow->userNickName
                                     );
                                    
                                     $message = $userRow->userNickName. " wants to add you as a friend";

                                     $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendId);

                                     foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                         if($loginDeviceRow->userDeviceType == "iphone"){
                                             $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
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
                        }
                       

                       /**
                        * push notification work end
                        */
                    
                }
                
                /**
                 *  code for adding trustees
                 */
                foreach($trusteesIdSet as $trusteeId){
                    $data = array(
                        'userId'        => $userId,
                        'trusteeId'      => $trusteeId,
                        'status'        => '0',
                        'creationDate'  => date('Y-m-d H:i:s'),
                        'modifyDate'    => date('Y-m-d H:i:s')
                    );
                    
                    $isTrusteeSave = false;
                    $send_trustee_notification = false; 
                    
                    if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$trusteeId)){
                        
                        if($trusteeRow->status == "0"){
                            $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                            $trusteeRow->save();
                            $send_trustee_notification = true;
                        }
                        
                        if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                            $trusteeRow->delete();
                            
                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
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
                    
                    if($isTrusteeSave && ($trusteeUserRow = $userTable->getRowById($trusteeId))){
                        $resultData =  array(
                                'userImage' => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );

                        $message = $userRow->userNickName. " wants to add you as a trustee";
                        
                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeId);
                            
                        foreach ($userLoginDeviceRowset as $loginDeviceRow){

                            if($loginDeviceRow->userDeviceType == "iphone"){
                                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
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
                    
                    /**
                     * push notification work end
                     */
                }
                
                $this->common->displayMessage("Friends and Trustees successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("Account does not exist","1",array(),"2");
            }             
             
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"3");
         }
         
     }
     
     /**
      *  add friends and trustees from phone contacts 
      */
     
     public function addFriendAndTrusteePhoneContactAction(){
         $decoded = $this->common->Decoded();
         
         $userSecurity = $decoded['userSecurity'];
         
         $userTable = new Application_Model_DbTable_Users();
         $friendTable = new Application_Model_DbTable_Friends();
         $trusteeTable = new Application_Model_DbTable_Trustee();
         $userSettingTable = new Application_Model_DbTable_UserSetting();
         $userNotificationTable = new Application_Model_DbTable_UserNotifications();
         
         if($userSecurity == $this->servicekey){
             $userId = $decoded['userId'];
             $requestDataset = $decoded['requestDataset'];
             
             $this->common->checkEmptyParameter1(array($userId,$requestDataset));
             
             if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
             }
             
             if($userRow = $userTable->getRowById($userId)){

                if(!is_array($decoded['requestDataset'])){
                    $requestDataset = json_decode($decoded['requestDataset'],true);
                }
                
                $phoneSet = array();

                foreach($requestDataset as $requestData){
                    $this->common->checkEmptyParameter1(array($requestData['name'],$requestData['phone']));

                   if(!empty($phoneSet) &&  in_array($requestData['phone'], $phoneSet)){
                       $this->common->displayMessage("This records contains duplicate values","1",array(),"4");
                   }

                   $phoneSet[] = $requestData['phone'];
                }

                foreach($requestDataset as $requestData){
                   $name = $requestData['name'];
                   $phone = $requestData['phone'];
                   $email = isset($requestData['email']) ? $requestData['email']:"";
                   
                   $isFriend = $requestData['isFriend'];
                   $isTrustee = $requestData['isTrustee'];
                   $isWaUser = "0";
                   $waUserId = "";
                   
                   /**
                    *  if request is for adding friend
                    */
                   
                   if($isTrustee == "1"){
                       $isFriend = "1";
                   }
                   
                   
                   if($isWaUser == "0"){
                       
                       $waUserRowset = $userTable->getUsersByPhone($phone);
                       
                       if(count($waUserRowset)>1){
                           $this->addMultipleUsersAsFriendAndTrustee($userId,$waUserRowset,$requestData);
                           continue;
                       }
                       
                       if($waUserRow = $userTable->checkDuplicateRecordByField("phoneWithCode", $phone)){
                           $isWaUser = "1";
                           $waUserId = $waUserRow->userId;
                       }
                       
                       if(!$waUserRow && $email){
                          if($waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $email)){
                                $isWaUser = "1";
                                $waUserId = $waUserRow->userId;
                            } 
                       }
                   }
                   
                   if($isFriend == "1"){
                        $data = array(
                            'userId'       => $userId,
                            'friendId'     => ($waUserId)?$waUserId:new Zend_Db_Expr('null'),
                            'friendName'   => $name,
                            'friendPhone'  => $phone,
                            'friendEmail'  => $email,  
                            'status'        => '0',
                            'creationDate'  => date('Y-m-d H:i:s'),
                            'modifyDate'    => date('Y-m-d H:i:s')
                        );

                        if($isWaUser && ($waUserId !="")){
                           $isFriendSave = false;
                           $send_user_notification = false;
                           
                           if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$waUserId)){
                               if($friendRow->status == "0"){
                                   $friendRow->modifyDate = date('Y-m-d H:i:s');
                                   $friendRow->save();
                                   $send_user_notification = true;
                               }
                               
                               if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                                    $friendRow->userId = $userId;
                                    $friendRow->friendId = $waUserId;
                                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                                    $friendRow->status = "0";
                                    $friendRow->save();
                                    $isFriendSave = true;
                                    $send_user_notification = true;
                               } 
                           }else{
                               $friendRow = $friendTable->createRow($data);
                               $friendRow->save();
                               $isFriendSave = true;
                               $send_user_notification = true;
                           }  
                           
                           if($send_user_notification){
                               $userNotificationTable->createFriendNotification($friendRow);
                           }
                           
                           if($isFriendSave && ($isTrustee !="1")){
                                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                                
                                if($friendUserRow = $userTable->getRowById($waUserId)){
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
                           
                        }else{
                            $friendRow = $friendTable->getRowByUserIdAndPhone($userId,$phone);
                            
                            if(!$friendRow && $email){
                                $friendRow = $friendTable->getRowByUserIdAndEmail($userId,$email);
                            }
                            
                           $isFriendSave = false;
                           
                           if($friendRow){
                               
                               if($friendRow->status == "0"){
                                   $friendRow->modifyDate = date('Y-m-d H:i:s');
                                   $friendRow->save();
                               }
                               
                               if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                                    $friendRow->status = "0";
                                    $friendRow->save();
                                    $isFriendSave = true;
                                    
                               } 
                           }else{
                               $friendRow = $friendTable->createRow($data);
                               $friendRow->save();
                               $isFriendSave = true;
                           } 
                            
                            
                            if($isFriendSave){
                                    $randomNumber = $this->common->randomAlphaNum(10);
                                    $friendRow->confirmCode = $randomNumber;
                                    $friendRow->save();
                                    $acceptRejectLink = $this->baseUrl.'?friend_request_id='.$friendRow->id.'&confirm_code='.$randomNumber;
                                    
                                    $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);
                                    
                                    if($email){
                                         $para= array(
                                            'friend_name'           => $friendRow->friendName,
                                            'from_name'             => $userRow->userNickName,
                                            'accept_reject_link'    => $acceptRejectLink,
                                            'baseUrl'               => $this->baseUrl
                                        );

                                         $this->user->sendmail($para,'friend_request.phtml',$email,'Friend Request for WA app');
                                    }
                                    
                                    if($phone && ($email == "")){
                                        $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                        $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                                        
                                        $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                        $this->common->sendSms_recommend($phone,$message);
                                    }
                            }
                        } 
                        
                    }
                    
                   /**
                    *  if request is for adding trustee
                    */
                    
                    if($isTrustee == "1"){
                        $data = array(
                            'userId'        => $userId,
                            'trusteeId'     => ($waUserId)?$waUserId:new Zend_Db_Expr('null'),
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
                        
                        if($isWaUser && ($waUserId !="")){
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
                            $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                            
                            if($isTrusteeSave){
                                if($trusteeUserRow = $userTable->getRowById($waUserId)){
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
                            
                        }else{
                            
                            $isTrusteeSave = false;
                            
                            $trusteeRow = $trusteeTable->getRowByUserIdAndPhone($userId,$phone);
                            
                            if(!$trusteeRow && $email){
                                $trusteeRow = $trusteeTable->getRowByUserIdAndEmail($userId,$email);
                            }
                            
                            if($trusteeRow){
                                if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                                    $trusteeRow->delete();

                                    $trusteeRow = $trusteeTable->createRow($data);
                                    $trusteeRow->save();
                                    $isTrusteeSave = true;
                                }
                                
                                if($trusteeRow->status == "0"){
                                     $isTrusteeSave = true;
                                 }
                                
                                
                            }else{
                                $trusteeRow = $trusteeTable->createRow($data);
                                $trusteeRow->save();
                                $isTrusteeSave = true;
                                
                            }
                            
                            if($isTrusteeSave){
                                $randomNumber = $this->common->randomAlphaNum(10);

                                $trusteeRow->confirmCode = $randomNumber;
                                $trusteeRow->save();

                                $acceptRejectLink = $this->baseUrl.'?trustee_request_id='.$trusteeRow->id.'&confirm_code='.$randomNumber;
                                $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                                if($email){
                                    $para= array(
                                        'trustee_name'          => $trusteeName,
                                        'from_name'             => $userRow->userNickName,
                                        'accept_reject_link'    => $acceptRejectLink,
                                        'baseUrl'               => $this->baseUrl
                                    );

                                    $this->user->sendmail($para,'trustee_request.phtml',$email,'Trustee request from one of your contacts or WA-friends!');
                                }
                                
                                
                                if($phone && ($email == "")){
                                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                    $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                                    $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                    
                                    $this->common->sendSms_recommend($phone,$message);
                                }
                                
                            }
                            
                        }
                    }
                    
                    if(($isFriend=="1")&& ($isTrustee =="1") && !$isWaUser){
                        $trusteeRow->friend_request_id = $friendRow->id;
                        $trusteeRow->save();
                    }
                }
                
                $this->common->displayMessage("friends and trustees added successfully","0",array(),"0");
             }else{
                 $this->common->displayMessage("User account is not exist","1",array(),"3");
             }  
             
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"2");
         }
     }
     
     /**
      *  adding friends and trustees using email or phone
      */
     
      public function addFriendAndTrusteeByEmailPhoneAction(){
         $decoded = $this->common->Decoded();
         
         $userSecurity = $decoded['userSecurity'];
         
         $userTable = new Application_Model_DbTable_Users();
         $friendTable = new Application_Model_DbTable_Friends();
         $trusteeTable = new Application_Model_DbTable_Trustee();
         $userSettingTable = new Application_Model_DbTable_UserSetting();
         $userNotificationTable = new Application_Model_DbTable_UserNotifications();
         
         if($userSecurity == $this->servicekey){
             $userId = $decoded['userId'];
             $name = $decoded['name'];
             $email = $decoded['email'];
             $phone = $decoded['phone'];
             $isFriend = $decoded['isFriend'];
             $isTrustee = $decoded['isTrustee'];
             
             if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
             }
             
             if($email){
                 $this->common->checkEmptyParameter1(array($userId,$name,$email));
             }else{
                 $this->common->checkEmptyParameter1(array($userId,$name,$phone));
             }
             
             if($userRow = $userTable->getRowById($userId)){

                $waUserRow = false;
                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                
                if($phone){
                    if($phone == $userRow->phoneWithCode){
                        $this->common->displayMessage("You are not allowed to add your own account details","1",array(),"6");
                    }
                    $waUserRow = $userTable->checkDuplicateRecordByField('phoneWithCode', $phone);
                    
                    $waUserRowset = $userTable->getUsersByPhone($phone);
                       
                    if(count($waUserRowset)>1){
                        $this->addMultipleUsersAsFriendAndTrustee($userId,$waUserRowset,$decoded);
                        $this->common->displayMessage("Friends and Trustees added succefully","0",array(),"0");
                    }
                }

                if($email && !$waUserRow){
                    if($email == $userRow->userEmail){
                        $this->common->displayMessage("You are not allowed to add your own account details","1",array(),"6");
                    }
                    
                    $waUserRow = $userTable->checkDuplicateRecordByField('userEmail', $email);
                }
                
                // start add trustee code
                
                if($isTrustee == "1"){
                     $isFriend = "1";
                     $data = array(
                        'userId'        => $userId,
                        'trusteeId'     => ($waUserRow)?$waUserRow->userId:new Zend_Db_Expr('null'),
                        'trusteeName'   => $name,
                        'trusteeEmail'  => $email,
                        'trusteePhone'  => $phone,
                        'status'        => "0",
                        'creationDate'  => date('Y-m-d H:i:s'),
                        'modifyDate'    => date('Y-m-d H:i:s')
                    );


                    $isTrusteeSave = false;
                    $send_trustee_notification = false;
                    
                    if($waUserRow){
                        
                        if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$waUserRow->userId)){
                            
                            if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                                $this->common->displayMessage("This trustee already added","1",array(),"5");  
                            }
                            
                            
                            if($trusteeRow->status =="0"){
                                $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                                $trusteeRow->save();
                                $send_trustee_notification = true;
                             }
                            
                            if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                                $trusteeRow->delete();

                                $trusteeRow = $trusteeTable->createRow($data);
                                $trusteeRow->save();
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
                            $resultData =  array(
                                    'userImage' => $userImage,
                                    'userId'    => $userRow->userId,
                                    'userName'  => $userRow->userNickName
                             );

                            $message = $userRow->userNickName. " wants to add you as a trustee";
                            
                            $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserRow->userId);
                            
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


                        /**
                         * push notification work end
                         */

                    }
                    else{

                        if($email){
                            $trusteeRow = $trusteeTable->getRowByUserIdAndEmail($userId, $email);
                        }
                        
                        if($phone && !$trusteeRow){
                            $trusteeRow = $trusteeTable->getRowByUserIdAndPhone($userId, $phone);
                        }
                        
                        if($trusteeRow){
                            
                            if($trusteeRow->status == "1"){
                                $this->common->displayMessage("This trustee already added","1",array(),"5");
                            }
                            
                            if($trusteeRow->status =="0"){
                                $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                                $trusteeRow->save();
                             }
                            
                            if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                                $trusteeRow->delete();

                                $trusteeRow = $trusteeTable->createRow($data);
                                $trusteeRow->save();
                            }
                            
                            $isTrusteeSave = true;
                        }else{
                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
                            $isTrusteeSave = true;

                        }

                        if($isTrusteeSave){
                            
                            $randomNumber = $this->common->randomAlphaNum(10);

                            $trusteeRow->confirmCode = $randomNumber;
                            $trusteeRow->save();

                            $acceptRejectLink = $this->baseUrl.'?trustee_request_id='.$trusteeRow->id.'&confirm_code='.$randomNumber;
                            $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);
                            
                            if($email){
                                $para= array(
                                    'trustee_name'          => $name,
                                    'from_name'             => $userRow->userNickName,
                                    'accept_reject_link'    => $acceptRejectLink,
                                    'baseUrl'               => $this->baseUrl
                                );

                                $this->user->sendmail($para,'trustee_request.phtml',$email,'Trustee request from one of your contacts or WA-friends!');
                            }
                            
                            if($phone && ($email == "")){
                                $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                                $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                
                                $this->common->sendSms_recommend($phone,$message);
                            }
                            
                        }

                    }
                    
                }
                
                // end add trustee code
                
                // start add friend code 
                if($isFriend == "1"){
                        $data = array(
                            'userId'       => $userId,
                            'friendId'     => ($waUserRow)?$waUserRow->userId:new Zend_Db_Expr('null'),
                            'friendName'   => $name,
                            'friendEmail'  => $email,
                            'friendPhone'  => $phone, 
                            'friendImage'  => "",
                            'status'        => '0',
                            'creationDate'  => date('Y-m-d H:i:s'),
                            'modifyDate'    => date('Y-m-d H:i:s')
                        );

                        
                        $isFriendSave = false;
                        
                        if($waUserRow){
                           
                           $friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$waUserRow->userId);
                           
                           
                           if($friendRow){
                                 
                                 if($friendRow->status =="0"){
                                     $friendRow->modifyDate = date('Y-m-d H:i:s');
                                     $friendRow->save();
                                 }
                                
                                 if(($friendRow->status !="1") && ($friendRow->status !="0")){
                                     $friendRow->userId = $userId;
                                     $friendRow->friendId = $waUserRow->userId;
                                     $friendRow->modifyDate = date('Y-m-d H:i:s');
                                     $friendRow->status = "0";
                                     $friendRow->save();
                                     $isFriendSave = true;
                                 }
                                 
                                 
                           }else{
                               $isFriendSave = true;
                               $friendRow = $friendTable->createRow($data);
                               $friendRow->save();
                           }
                           
                           if($isFriendSave && ($isTrustee !="1")){
                                /**
                                * push notification send to wa user
                                */
                                $userNotificationTable->createFriendNotification($friendRow);
                                
                                $resultData =  array(
                                        'userImage' => $userImage,
                                        'userId'    => $userRow->userId,
                                        'userName'  => $userRow->userNickName
                                 );

                                $message = $userRow->userNickName. " wants to add you as a friend";
                                
                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserRow->userId);
                            
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
                                
                                // push notification code end
                           }
                           
                        }else{
                            $friendRow = false;
                            
                            if($email){
                                $friendRow = $friendTable->getRowByUserIdAndEmail($userId, $email);
                            }
                            
                            if($phone && !$friendRow){
                                $friendRow = $friendTable->getRowByUserIdAndPhone($userId, $phone);
                            }
                            
                            if($friendRow){
                                 if($friendRow->status =="0"){
                                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                                    $friendRow->save();
                                 } 
                                
                                 if(($friendRow->status !="1") && ($friendRow->status !="0")){
                                     $friendRow->modifyDate = date('Y-m-d H:i:s');
                                     $friendRow->status = "0";
                                     $friendRow->save();
                                     
                                     $isFriendSave = true;
                                 }
                                 
                            }else{
                               $friendRow = $friendTable->createRow($data);
                               $friendRow->save();
                               $isFriendSave = true;
                            }
                            
                            if($isFriendSave && ($isTrustee !="1")){
                                $randomNumber = $this->common->randomAlphaNum(10);
                                $friendRow->confirmCode = $randomNumber;
                                $friendRow->save();
                                $acceptRejectLink = $this->baseUrl.'?friend_request_id='.$friendRow->id.'&confirm_code='.$randomNumber;

                                $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);
                                
                                if($email){
                                    $para= array(
                                        'friend_name'           => $friendRow->friendName,
                                        'baseUrl'               => $this->baseUrl,
                                        'from_name'             => $userRow->userNickName,
                                        'accept_reject_link'    => $acceptRejectLink
                                    );

                                    $this->user->sendmail($para,'friend_request.phtml',$friendRow->friendEmail,'Friend request from one of your contacts or WA-friends!');

                                }

                                if($phone && ($email == "")){
                                    $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                    $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                                    $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;

                                    $this->common->sendSms_recommend($phone,$message);
                                }
                            }
                        } 
                        
                        if(($isFriend=="1")&& ($isTrustee =="1") && !$waUserRow){
                                $trusteeRow->friend_request_id = $friendRow->id;
                                $trusteeRow->save();
                            }
                        
                    }
                    
                    // end add friend code
                    $this->common->displayMessage("Friends and Trustees added succefully","0",array(),"0");
             }else{
                 $this->common->displayMessage("User account is not exist","1",array(),"3");
             }  
             
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"2");
         }
      }
     
     /**
      *  get list of all friends and trustees by userId
      */
     
      public function getFriendsAndTrusteesAction(){
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                 $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,userDeviceId));
            
            if($userRow = $userTable->getRowById($userId)){
                
                // outgoing
                $select1 = $friendTable->select()->setIntegrityCheck(false)
                        ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))  
                        ->joinLeft('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userImage','userPhone','isOnline','lastSeenTime'))
                        ->where('friends.userId =?',$userId)
                        ->where('(friends.friendId is null')
                        ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                        ->where('(friends.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
                        ->orWhere('friends.status =?)',Application_Model_DbTable_Friends::STATUS_ACTIVE);
                        
                
                // incoming
                
                $select2 = $friendTable->select()->setIntegrityCheck(false)
                        ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))  
                        ->joinLeft('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userImage','userPhone','isOnline','lastSeenTime'))
                        ->where('friends.friendId =?',$userId)
                        ->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                        ->where('(friends.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
                        ->orWhere('friends.status =?)',Application_Model_DbTable_Friends::STATUS_ACTIVE);
                
                $select = $friendTable->select()
                            ->union(array($select1, $select2))
                            ->order('userFullName');
                            
                
                $friendData = array();
                
                $friendRowset = $friendTable->fetchAll($select);
                
                 // outgoing trustee request  
                 $select1 = $trusteeTable->select()
                                ->setIntegrityCheck(false)
                                ->from('trustees',array('*','is_send_request' => new Zend_Db_Expr('1')))
                                ->joinLeft('users', 'users.userId = trustees.trusteeId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','isOnline','lastSeenTime'))
                                ->where('trustees.userId =?',$userId)
                                ->where('(trustees.trusteeId is null')
                                ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                                ->where('(trustees.status =?',Application_Model_DbTable_Trustee::STATUS_INACTIVE)
                                ->orWhere('trustees.status =?)',Application_Model_DbTable_Trustee::STATUS_ACTIVE);
                
                
                // incoming trustee request 
                 
                 $select2 = $trusteeTable->select()->setIntegrityCheck(false)
                        ->from('trustees',array('*','is_send_request' => new Zend_Db_Expr('0')))
                        ->joinLeft('users', 'users.userId = trustees.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','isOnline','lastSeenTime'))
                        ->where('users.userStatus=?',Application_Model_DbTable_Users::STATUS_ACTIVE)
                        ->where('trustees.trusteeId =?',$userId)
                        ->where('(trustees.status =?',Application_Model_DbTable_Trustee::STATUS_INACTIVE)
                        ->orWhere('trustees.status =?)',Application_Model_DbTable_Trustee::STATUS_ACTIVE);
               
                 
                $select = $trusteeTable->select()
                            ->union(array($select1, $select2))
                            ->order('userFullName');
                           
                $trusteeRowset = $trusteeTable->fetchAll($select);
                             
                 $responseData = array();
                 $friendUniqueData = array();
                 
                 foreach($friendRowset as $friendRow){
                     
                    $isFriend = ($friendRow->status == "1")?"1":($friendRow->is_send_request ? "2":"3 ");
                    $isTrustee = "0";
                    
                    $friendEmail = ($friendRow->friendEmail)?$friendRow->friendEmail:($friendRow->userEmail ? $friendRow->userEmail:"");
                    $friendPhone = ($friendRow->friendPhone)?$friendRow->friendPhone:($friendRow->userPhone ? $friendRow->userPhone:"");
                    
                    $responseData[] = array(
                        'id'            => $friendRow->id,
                        'user_id'       => ($friendRow->user_id)?$friendRow->user_id:"",
                        'name'          => ($friendRow->friendName)?$friendRow->friendName:($friendRow->userNickName ? $friendRow->userNickName:""),
                        'email'         => $friendEmail,
                        'phone'         => $friendPhone,
                        'isOnline'      =>  $friendRow->user_id ? ($userSettingTable->isUserOnline($friendRow->user_id)?"1":"0"):"0",
                        'lastSeenTime'  => ($friendRow->lastSeenTime)?$friendRow->lastSeenTime:"",
                        'image'         => ($friendRow->userImage)?$this->makeUrl($friendRow->userImage):"",
                        'creationDate'  =>  $friendRow->creationDate,
                        'isFriend'      =>  $isFriend,
                        'isTrustee'     => "0"
                    );
                      
                    if($friendPhone){
                        $friendUniqueData[] = $friendPhone;
                    }else{
                        $friendUniqueData[] = $friendEmail;
                    }
                }
                
                foreach($trusteeRowset as $trusteeRow){
                    
                    $isFriend = "0";
                    $isTrustee = ($trusteeRow->status == "1")?"1":($trusteeRow->is_send_request ? "2":"3 ");
                    
                    $search_found = false;
                    $trusteeEmail = ($trusteeRow->trusteeEmail)? $trusteeRow->trusteeEmail:($trusteeRow->userEmail ? $trusteeRow->userEmail:"");
                    $trusteePhone = ($trusteeRow->trusteePhone)? $trusteeRow->trusteePhone:($trusteeRow->userPhone ? $trusteeRow->userPhone:"");
                    $trusteeName = ($trusteeRow->trusteeName) ? $trusteeRow->trusteeName:($trusteeRow->userNickName ? $trusteeRow->userNickName:"");
                    
                    
                    if($trusteePhone && in_array($trusteePhone, $friendUniqueData)){
                        $key = array_search($trusteePhone , $friendUniqueData);
                        $search_found = true;
                    }
                    
                    if(!$search_found && $trusteeEmail && in_array($trusteeEmail, $friendUniqueData)){
                         $key = array_search($trusteeEmail , $friendUniqueData);
                         $search_found = true;
                    }
                    
                    if($search_found){
                        $responseData[$key]['isTrustee'] = $isTrustee;
                        
                        if(strtotime($trusteeRow->creationDate)> strtotime($responseData[$key]['creationDate'])){
                             $responseData[$key]['name'] = $trusteeName;
                        }
                        
                    }else{
                        
                        $responseData[] = array(
                            'id'            => $trusteeRow->id,
                            'user_id'       => ($trusteeRow->user_id)?$trusteeRow->user_id:"",
                            'name'          => $trusteeName,
                            'email'         => $trusteeEmail,
                            'phone'         => $trusteePhone,
                            'isOnline'      =>  $trusteeRow->user_id ? $userSettingTable->isUserOnline($trusteeRow->user_id):"",
                            'lastSeenTime'  => ($trusteeRow->lastSeenTime)?$trusteeRow->lastSeenTime:"",
                            'image'         => ($trusteeRow->userImage)?$this->makeUrl($trusteeRow->userImage):"",
                            'creationDate'  =>  $trusteeRow->creationDate,
                            'isFriend'      =>  $isFriend,
                            'isTrustee'     =>  $isTrustee
                        );
                    }
                    
                }
                
                $this->common->displayMessage('Trustees and friends list',"0",$responseData,"0");
                
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"3"); 
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"2"); 
        }
    }
    
    /**
      *  function for delete friend
      */
     
     public function deleteFriendAction(){
        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $waPointsTable = new Application_Model_DbTable_WaPoints();
        $userSecurity = $decoded['userSecurity'];
        $friendRowId = $decoded['friendRowId'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            if(isset($userId) && isset($userDeviceId)){
                $this->common->isUserLogin($userId,$userDeviceId);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$friendRowId));
            
            if($friendRow = $friendTable->getRowById($friendRowId)){
                $friendRow->status = "2";
                $friendRow->modifyDate = date('Y-m-d H:i:s');
                $friendRow->save();
                
                /*Delete in wa_points and update friends table point_id*/
                $waPointsTable->deleteWaPointRow($friendRow->userId,$friendRow->friendId,$trusteeId=0,$isTrustee=0);
                /*wa_points end*/
                
                $otherUserId = ($userId !=$friendRow->userId)?$friendRow->userId:$friendRow->friendId;
                
                if($otherUserId){
                    $trusteeTable->breakTrusteeRelation($userId,$otherUserId);
                }
                
                $editFriendTrusteeTable->deleteEditName($userId, $otherUserId);
                $this->common->displayMessage("Friend delete successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Friend id is not correct","1",array(),"3");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
     }
    
     
     /**
      * delete friend by friend id and user id
      */
     
     public function deleteFriendByFriendIdAction(){
        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $userSecurity = $decoded['userSecurity'];
        $friendId = $decoded['friendId'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            $this->common->isUserLogin($userId,$userDeviceId);
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$friendId));
            
            if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)){
                $friendRow->status = "2";
                $friendRow->modifyDate = date('Y-m-d H:i:s');
                $friendRow->save();
                
                $trusteeTable->breakTrusteeRelation($userId,$friendId);
                
                $editFriendTrusteeTable->deleteEditName($userId, $friendId);
                $this->common->displayMessage("Friend delete successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Friend id is not correct","1",array(),"3");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
     }
     
   /**
      *  function for edit friends details
      */
     
     public function editFriendDetailsAction(){
        $decoded = $this->common->Decoded();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        
        $userSecurity = $decoded['userSecurity'];
        $friendRowId = $decoded['friendRowId'];
        $userId = $decoded['userId'];
        $friendName = (isset($decoded['friendName']) && (trim($decoded['friendName']) !=""))?$decoded['friendName']:"";
        $friendEmail = (isset($decoded['friendEmail']) && (trim($decoded['friendEmail']) !=""))?$decoded['friendEmail']:"";
        $friendPhone = (isset($decoded['friendPhone']) && (trim($decoded['friendPhone']) !=""))?$decoded['friendPhone']:"";
             
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$friendRowId,$userId));
            
            if($friendRow = $friendTable->getRowById($friendRowId)){
                
                if($friendName){
                    $friendRow->friendName = $friendName;
                }
                
                if($friendEmail){
                    $friendRow->friendEmail = $friendEmail;
                }
                
                if($friendPhone){
                    $friendRow->friendPhone = $friendPhone;
                }
                
                $friendRow->modifyDate = date('Y-m-d H:i:s');
                $friendRow->save();
                
                $this->common->displayMessage("Friend details edit successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("Friend id is not correct","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"1");
        }
     }  
     
     public function editFriendTrusteeDetailsAction(){
        $decoded = $this->common->Decoded();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $userSecurity = $decoded['userSecurity'];
        
        $userId         = $decoded['userId'];
        $otherUserId    = $decoded['otherUserId'];
        $userDeviceId   = $decoded['userDeviceId'];
        $name           = $decoded['name'];
        $hideProfile    = ($decoded['hideProfile'])?"1":"0";
       
        if($userSecurity == $this->servicekey){
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$otherUserId,$name));
            
            if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId)){
                $friendRow->modifyDate = date("Y-m-d H:i:s");
                $friendRow->save();
            }
            
            if($userRow = $userTable->getRowById($userId)){
                
                if(!$otherUserRow = $userTable->getRowById($otherUserId)){
                    $this->common->displayMessage("other user account is not exist","1",array(),"4");
                }
                
                $data = array(
                    'userId'        => $userId,
                    'otherUserId'   => $otherUserId,
                    'userDeviceId'  => $userDeviceId,
                    'name'          => $name,
                    'hideProfile'   => $hideProfile
                );
                
                $editDetailsRow = $editFriendTrusteeTable->editDetails($data); 
                $this->common->displayMessage("Update Friends/Trustee details successfully","0",array(),"0");
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
     }
     
     public function resendFriendRequestAction(){
         
        $friendTable            = new Application_Model_DbTable_Friends();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $userTable              = new Application_Model_DbTable_Users();
        $userNotificationTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded                = $this->common->Decoded();
        $userSecurity           = $decoded['userSecurity'];
        $request_from           = $decoded['request_from'];
        //$decoded                = array('userSecurity'=>'afe2eb9b1de658a39e896591999e1b59','userDeviceId' => '8D40CA44-52B9-4D3F-8671-DF9681BCDC36','userId'=> 4,'request_form'=>'web','friendRowId'=>6);
        //$userSecurity           = "afe2eb9b1de658a39e896591999e1b59";
        
        if(($userSecurity == $this->servicekey)|| ($request_from == "web")){
            $userId      = $decoded['userId'];
            $friendRowId = $decoded['friendRowId'];
            
            if($request_from != "web"){
                $this->common->checkEmptyParameter1(array($userId,$friendRowId));
                
                if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                    $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
                }
            }else{
                if(!$this->loggedUserRow){
                    $this->common->displayMessage("logout","15",array(),"15");
                }
                $userId = $this->loggedUserRow->userId;
                            
            }
            
            if($userRow = $userTable->getRowById($userId)){
                
                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                
                if($friendRow = $friendTable->getRowById($friendRowId)){
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                        $this->common->displayMessage("Friend request is already accepted","1",array(),"5");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                        $this->common->displayMessage("Friend request is already rejected","1",array(),"6");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE){
                        if($friendRow->friendId){
                                /**
                                * push notification work
                                */
                               $userNotificationTable->createFriendNotification($friendRow);
                               
                               if($friendUserRow = $userTable->getRowById($friendRow->friendId)){
                                   $resultData =  array(
                                           'userImage' => $userImage,
                                           'userId'    => $userRow->userId,
                                           'userName'  => $userRow->userNickName
                                    );

                                    $message = $userRow->userNickName. " wants to add you as a friend";
                                    
                                    $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendRow->friendId);

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
                               }else{
                                   $this->common->displayMessage("Friend account is not exist","1",array(),"7");
                               }

                               /**
                                * push notification work end
                                */
                            
                        }else{
                             // for non wa users
                            
                            if($friendRow->friendEmail){
                                $randomNumber = $this->common->randomAlphaNum(10);
                                $friendRow->confirmCode = $randomNumber;
                                $friendRow->save();
                                $acceptRejectLink = $this->baseUrl.'?friend_request_id='.$friendRow->id.'&confirm_code='.$randomNumber;

                                 $para= array(
                                     'friend_name'          => $friendRow->friendName,
                                     'baseUrl'              => $this->baseUrl,
                                     'accept_reject_link'   => $acceptRejectLink,
                                     'from_name'            => $userRow->userNickName
                                 );

                                 $this->user->sendmail($para,'friend_request.phtml',$friendRow->friendEmail,'Friend request from one of your contacts or WA-friends!');
                            }
                            
                            if(($friendRow->friendPhone) && ($friendRow->friendEmail == "")){
                                
                                $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                                $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                $this->common->sendSms_recommend($friendRow->friendPhone,$message);
                            }
                        }
                    }
                    
                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                    $friendRow->save();
                    $this->common->displayMessage("Friend request send successfully","0",array(),"0");
                    
                }else{
                    $this->common->displayMessage("Friend account is not correct","1",array(),"4");
                }
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }   
     }
     
     
     /**
      * resend friend request by friend id
      */
     public function resendRequestByFriendIdAction(){
        $friendTable = new Application_Model_DbTable_Friends();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        
        if($userSecurity == $this->servicekey){
            $userId = $decoded['userId'];
            $friendId = $decoded['friendId'];
            
            if($request_from != "web"){
                $this->common->checkEmptyParameter1(array($userId,$friendId));
                
                if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                    $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
                }
            }else{
                if(!$this->loggedUserRow){
                    $this->common->displayMessage("logout","15",array(),"15");
                }
                $userId = $this->loggedUserRow->userId;
                            
            }
            
            if($userRow = $userTable->getRowById($userId)){
                
                if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)){
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                        $this->common->displayMessage("Friend request is already accepted","1",array(),"5");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                        $this->common->displayMessage("Friend request is already rejected","1",array(),"6");
                    }
                    
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE){
                        if($friendRow->friendId){
                                /**
                                * push notification work
                                */
                               $userNotificationTable->createFriendNotification($friendRow);
                               $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                               
                               if($friendUserRow = $userTable->getRowById($friendRow->friendId)){
                                   $resultData =  array(
                                           'userImage' => $userImage,
                                           'userId'    => $userRow->userId,
                                           'userName'  => $userRow->userNickName
                                    );

                                    $message = $userRow->userNickName. " wants to add you as a friend";
                                    
                                    $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendRow->friendId);

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
                               }else{
                                   $this->common->displayMessage("Friend account is not exist","1",array(),"7");
                               }

                               /**
                                * push notification work end
                                */
                            
                        }else{
                             // for non wa users
                            
                            if($friendRow->friendEmail){
                                $randomNumber = $this->common->randomAlphaNum(10);
                                $friendRow->confirmCode = $randomNumber;
                                $friendRow->save();
                                $acceptRejectLink = $this->baseUrl.'?friend_request_id='.$friendRow->id.'&confirm_code='.$randomNumber;

                                 $para= array(
                                     'friend_name'          => $friendRow->friendName,
                                     'baseUrl'              => $this->baseUrl,
                                     'accept_reject_link'   => $acceptRejectLink,
                                     'from_name'            => $userRow->userNickName
                                 );

                                 $this->user->sendmail($para,'friend_request.phtml',$friendRow->friendEmail,'Friend request from one of your contacts or WA-friends!');
                            }
                            
                            if(($friendRow->friendPhone) && ($friendRow->friendEmail == "")){
                                
                                $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                                $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                                $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                $this->common->sendSms_recommend($friendRow->friendPhone,$message);
                            }
                        }
                    }
                    
                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                    $friendRow->save();
                    $this->common->displayMessage("Friend request send successfully","0",array(),"0");
                    
                }else{
                    $this->common->displayMessage("Friend account is not correct","1",array(),"4");
                }
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
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
        
        $userRow = $userTable->getRowById($userId);
        
        $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
        
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
                        $isFriendSave = true;
                        $send_friend_notification = true;
                    } 
                }else{
                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save();
                    $isFriendSave = true;
                    $send_friend_notification = true;
                }  
                
                if($send_friend_notification){
                    $userNotificationTable->createFriendNotification($friendRow);
                }
                
                if($isFriendSave && ($isTrustee !="1")){
                     if($friendUserRow = $userTable->getRowById($waUserId)){
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
     
    public function friendProfileAction(){
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $decoded = $this->common->Decoded();
        
//       $decoded = array(
//            'userSecurity'    => 'afe2eb9b1de658a39e896591999e1b59',
//            'userId'    => '198',
//            'userDeviceId'    => "EAD52633-D594-4258-A9C5-AC3A44F11907",
//            'friendId'    => '199'
//        );
    
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $friendId = $decoded['friendId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity = $this->servicekey){
            $this->common->isUserLogin($userId,$userDeviceId);
            $this->common->checkEmptyParameter1(array($userId,$friendId));
            
            if(($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")){
                
                if(($friendUserRow = $userTable->getRowById($friendId)) && ($friendUserRow->userStatus == "1")){
                    
                    $blockUserRow = $blockUsersTable->getRowByUserIdAndBlockUserId($userId,$friendId);
                    $commonGroupsRowset = $groupTable->commonGroups($userId, $friendId);
                    $common_group_data = array();
                    foreach($commonGroupsRowset as $commonGroupRow){
                        $common_group_data[] = array(
                            'groupId'       => $commonGroupRow->groupId,
                            'groupName'     => $commonGroupRow->groupName,
                            'groupImage'    => $commonGroupRow->groupImage,
                            'count'         => $commonGroupRow->count
                        );
                    }

                    $friendTrusteeRelation = $friendTable->checkFriendAndTrusteeRelation($userId, $friendId);
                    
                    if(($incomingTrusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($friendId,$userId)) && ($incomingTrusteeRow->status == "0") ){
                        $friendTrusteeRelation['isTrustee'] = "3";
                    }
                    
                    
                    $responseData = array(
                        'friendName'            => $friendUserRow->userNickName,
                        'friendEmail'           => $friendUserRow->userEmail,
                        'friendImage'           => ($friendUserRow->userImage)?$this->makeUrl($friendUserRow->userImage):"",
                        'friendCoverImage'      => ($friendUserRow->userCoverImage)?$this->makeUrl('/'.$friendUserRow->userCoverImage):"",
                        'friendPhone'           => $friendUserRow->userPhone,
                        'phoneWithCode'         => ($friendUserRow->phoneWithCode)?$friendUserRow->phoneWithCode:"",
                        'profileStatus'         => ($friendUserRow->profileStatus)?$friendUserRow->profileStatus:"",
                        'user_location'         => ($friendUserRow->user_location)?$friendUserRow->user_location:"",
                        'common_groups_data'    => $common_group_data,
                        'isFriend'              => $friendTrusteeRelation['isFriend'],
                        'isTrustee'             => $friendTrusteeRelation['isTrustee'],
                        'isBlock'               => ($blockUserRow)?$blockUserRow->status:"0",
                        'quickBloxId'           => ($friendUserRow->quickBloxId)?$friendUserRow->quickBloxId:""
                    );

                    $this->common->displayMessage("common groups","0",$responseData,"0");
                }else{
                    $this->common->displayMessage("Friend account is not exist","1",array(),"5");
                }
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
     
    }
    
    public function checkFriendTrusteeRelationAction(){
       $userTable = new Application_Model_DbTable_Users();
       $friendTable = new Application_Model_DbTable_Friends();
       $trusteeTable = new Application_Model_DbTable_Trustee();
       
       $decoded = $this->common->Decoded();
       
       $userSecurity = $decoded['userSecurity'];
       $userDeviceId = $decoded['userDeviceId'];
       $userId = $decoded['userId'];
       $otherUserId = $decoded['friendid'];
       
       if($userSecurity == $this->servicekey){
           
           $this->common->checkEmptyParameter(array($userSecurity,$userDeviceId,$userId,$otherUserId));
           
           if(($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus  == "1")){
               
                $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId);  // wa user is friend or not
                $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $otherUserId);
                /**
                    * code for finding friend status
                    * 1 means friend , 0 means no relation , 2 means outgoing pending friend request, 3 means incoming pending friend request
                */

               $isFriend = '0';
               $isTrustee = '0';
               
               if($friendRow){
                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                        $isFriend = '1';
                    }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE) {
                          $isFriend = ($friendRow->userId == $userId) ? '2':'3';
                    }else{

                    } 
               }
               
                /**
                 *  code for finding trustee status
                 *  1 means trustee , 0 means no relation , 2 outgoing trustee request
                 */

                if($trusteeRow){
                   if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE){
                       $isTrustee = '1';
                   }elseif($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_INACTIVE) {
                       $isTrustee = '2';
                   }else{

                   }
                }
               
               $data = array(
                   'isFriend' => $isFriend,
                   'isTrustee' => $isTrustee,
               );
               
               $this->common->displayMessage('success',"0",$data,"0"); 
               
           }else{
              $this->common->displayMessage('user account is not exist',"1",array(),"2"); 
           }
           
       }else{
           $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
       }
   }
    
    public function testAction(){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $friendRow = $friendTable->getRowById('3');
        
        $userNotificationTable->createFriendNotification($friendRow);
        exit;
    }
    
    public function test1Action(){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $trusteeRow = $trusteeTable->getRowById('1');
        
        $userNotificationTable->createTrusteeNotification($trusteeRow);
        exit;
    }
    
    public function addFriendAndTrusteeOneUserAction(){
         $decoded = $this->common->Decoded();
         
         $userSecurity = $decoded['userSecurity'];
         
         $userTable = new Application_Model_DbTable_Users();
         $friendTable = new Application_Model_DbTable_Friends();
         $trusteeTable = new Application_Model_DbTable_Trustee();
         $userSettingTable = new Application_Model_DbTable_UserSetting();
         $userNotificationTable = new Application_Model_DbTable_UserNotifications();
         if($userSecurity == $this->servicekey){
             $userId = $decoded['userId'];
             $userDeviceId = $decoded['userDeviceId'];
             $otherUserId = $decoded['otherUserId'];
             $is_trustee_also = ($decoded['is_trustee_also'])?$decoded['is_trustee_also']:"0";
             
             $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$otherUserId));
             
             if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
             }
             
             $friendId = $otherUserId;
             $trusteeId = false;
             
             if($is_trustee_also == "1"){
                 $trusteeId = $otherUserId;
             }
             
             
            if($userRow = $userTable->getRowById($userId)){
                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                /**
                 *  code for adding new friends
                */
                $data = array(
                    'userId'        => $userId,
                    'friendId'      => $friendId,
                    'status'        => '0',
                    'creationDate'  => date('Y-m-d H:i:s'),
                    'modifyDate'    => date('Y-m-d H:i:s')
                );

                $isFriendSave = false;

                if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId,$friendId)){

                    if($friendRow->status == "0"){
                        $friendRow->modifyDate = date('Y-m-d H:i:s');
                        $friendRow->save();
                    }

                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                        $friendRow->userId = $userId;
                        $friendRow->friendId = $friendId;
                        $friendRow->modifyDate = date('Y-m-d H:i:s');
                        $friendRow->status = "0";
                        $friendRow->save();

                        $isFriendSave = true;
                    }
                }else{
                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save();
                    $isFriendSave = true;
                }    
                    /**
                    * push notification work
                    */
                if($is_trustee_also =="0"){
                    if($isFriendSave && ($friendUserRow = $userTable->getRowById($friendId))){
                        $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );

                         $message = $userRow->userNickName. " wants to add you as a friend";

                         $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendId);

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
                 }

                /**
                 * push notification work end
                 */

                 /**
                  *  code for adding trustees
                  */
                if($is_trustee_also == "1"){
                    $data = array(
                        'userId'        => $userId,
                        'trusteeId'      => $trusteeId,
                        'status'        => '0',
                        'creationDate'  => date('Y-m-d H:i:s'),
                        'modifyDate'    => date('Y-m-d H:i:s')
                    );

                    $isTrusteeSave = false;
                    
                    if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$trusteeId)){

                        if($trusteeRow->status == "0"){
                            $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                            $trusteeRow->save();
                            
                        }

                        if($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT){
                            $trusteeRow->delete();

                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
                            $isTrusteeSave = true;
                            
                        }
                    }else{
                        $trusteeRow = $trusteeTable->createRow($data);
                        $trusteeRow->save();
                        $isTrusteeSave = true;
                        
                    }
                    
                    

                    /**
                     * push notification work
                     */

                    if($isTrusteeSave && ($trusteeUserRow = $userTable->getRowById($trusteeId))){
                        $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userNickName
                         );

                        $message = $userRow->userNickName. " wants to add you as a trustee";

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeId);

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

                    /**
                     * push notification work end
                     */

                }

                    $this->common->displayMessage("Friends and Trustees successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("Account does not exist","1",array(),"2");
            }             
             
         }else{
             $this->common->displayMessage("You could not access this web-service","1",array(),"3");
         }
         
     }

public function acceptRejectIncomingRequestWebAction(){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        /*
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $friendRowId = $decoded['friendRowId'];
        $isAccept = $decoded['isAccept'];
        $userId = $decoded['userId'];
        $request_from = $decoded['request_from'];
        
	
	$userId = "14";
        $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
	$isAccept = "1";
	$friendRowId = "23"
	*/

	$friendRowId = $_REQUEST['friendRowId'];
        $isAccept = $_REQUEST['isAccept'];
        $userId = $this->loggedUserRow->userId;

        if($userRow = $userTable->getRowById($userId)){
            
            $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
            
                if($friendRow = $friendTable->getRowById($friendRowId)){

                    if($friendRow->status == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                        $this->common->displayMessage('Friend request is already accepted',"1",array(),"6");


                    }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED){
                        $this->common->displayMessage('Friend request is already deleted',"1",array(),"7");


                    }elseif($friendRow->status == Application_Model_DbTable_Friends::STATUS_INACTIVE){
                            if($friendUserRow = $userTable->getRowById($friendRow->userId)){
                                if($isAccept == Application_Model_DbTable_Friends::STATUS_ACTIVE){
                                    $friendRow->status = $isAccept;
                                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                                    $friendRow->acceptDate = date("Y-m-d H:i:s");
                                    $friendRow->save();

                                    $userNotificationTable->createFriendNotification($friendRow);

                                    $responseMessage = "Friend request accepted successfully";

                                    $resultData =  array(
                                        'userImage' => $userImage,
                                        'userId'    => $userRow->userId,
                                        'userName'  => $userRow->userNickName
                                    );

                                    $message = ucfirst($userRow->userNickName). " accepted your friend request";

//                                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendRow->userId);
//
//                                        foreach ($userLoginDeviceRowset as $loginDeviceRow){
//
//                                            if($loginDeviceRow->userDeviceType == "iphone"){
//                                                $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'accept_friend_request','userId'=>$userRow->userId,'userName'=>$userRow->userNickName,'userImage'=>$userRow->userImage);
//                                            }else{
//                                                $payload = array(
//                                                    'message'   => $message,
//                                                    'type'      => "accept_friend_request",
//                                                    'result'    => $resultData
//                                               );
//                                                $payload = json_encode($payload);
//                                            }
//                                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
//
//                                        }


                                }else{

                                    $friendRow->status = Application_Model_DbTable_Friends::STATUS_DELETED;
                                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                                    $friendRow->save();
                                    $responseMessage = "Friend request rejected successfully";

                                    $trusteeTable->removeTrusteeRelationShip($friendRow->userId, $friendRow->friendId);

                                }

                                $this->common->displayMessage($responseMessage,"0",array(),"0");
                            }else{
                                $this->common->displayMessage('Friend account is not available',"1",array(),"4");
                            }
                    }else{

                    }

                }else{
                    $this->common->displayMessage('Friend account is not available',"1",array(),"2");
                }
        }else{
            $this->common->displayMessage("User account is not exist","1",array(),"5");
        }

        
        exit;
     }

     public function test2Action()
     {
         $userId        = 4;
         $count         = $this->getLastTrusteeCountById($userId); // count last trustee count number
         $count_trustee = ($count + 1);
         echo $count_trustee;
         exit;
     }
      public function getLastTrusteeCountById($userid) {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select()->from('wa_points')->where('user_id=?',$userid)->where('count_trustee>0')->order('point_id Desc')->limit(1);
        $waPoints = $db->fetchRow($select);
        
        if($waPoints->count_trustee > 0){
            $count_trustee = $waPoints->count_trustee;
        }else{
            $count_trustee = 0;
        }
        
        return $count_trustee;
    }
}
?>
