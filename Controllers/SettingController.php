<?php
    class SettingController extends My_Controller_Abstract{
       
        public function preDispatch() {
            parent::preDispatch();
            $this->fbAppId = "257955024398140";
            $this->fbsecret = "37c0762eddc12332c410a8552b25c939";
            $this->_helper->layout->disableLayout();
        }


        public function updatePrivacySettingAction(){
        $userTable = new Application_Model_DbTable_Users();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        
        $decoded = $this->common->Decoded();

   /*     $decoded = array(
            'userDeviceId'      => "E18B3017-489C-453E-A097-2AAACE461F46",
            'userId'            => "2",
            'whoCanSeeMe'       => "1",
            'whoCanSeePP'       => "1",
            'whoCanSeeStatus'   => "0",
            'whoCanSeeLastSeen' => "2",
            'userSecurity'      => "afe2eb9b1de658a39e896591999e1b59"
        );
     */   
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        $searchBy = $decoded['searchBy'];
        
        $whoCanSeeLastSeen = $decoded['whoCanSeeLastSeen'];
        $whoCanSeeMe = $decoded['whoCanSeeMe'];
        $whoCanSeePP = $decoded['whoCanSeePP'];
        $whoCanSeeStatus = $decoded['whoCanSeeStatus'];
        $availableForDates = $decoded['availableForDates'];
        $autoFriends = $decoded['auto_friends'];
        $extraPrivacy = $decoded['extra_privacy']; 
        $near_by = $decoded['near_by'];
        
        $range = $decoded['range'];
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            
       //     $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
              
                
                $data = array();
                
                if(isset($decoded['searchBy']) && $searchBy){
                    if(isset($searchBy['fullName'])){
                        $data['searchFullName'] = $searchBy['fullName'];
                    }
                    
                    if(isset($searchBy['completeName'])){
                        $data['searchCompleteName'] = $searchBy['completeName'];
                    }
                    
                    if(isset($searchBy['email'])){
                        $data['searchEmail'] = $searchBy['email'];
                    }
                    
                    if(isset($searchBy['phone'])){
                        $data['searchPhone'] = $searchBy['phone'];
                    }
                    
                }
                
                if(isset($decoded['whoCanSeeLastSeen']) && $whoCanSeeLastSeen){
                    $data['whoCanSeeLastSeen'] = $whoCanSeeLastSeen;
                }
                
                if(isset($decoded['whoCanSeeMe']) && $whoCanSeeMe){
                    $data['whoCanSeeMe'] = $whoCanSeeMe;
                }
                
                if(isset($decoded['whoCanSeePP']) && $whoCanSeePP){
                    $data['whoCanSeePP'] = $whoCanSeePP;
                }
                
                if(isset($decoded['availableForDates'])){
                    $data['availableForDates'] = $availableForDates;
                }
                
                if(isset($decoded['range'])){
                    $data['range'] = $range;
                }
                
                if(isset($decoded['auto_friends'])){
                    $data['auto_friends'] = $autoFriends;
                }
                
                if(isset($decoded['extra_privacy'])){
                    $data['extra_privacy'] = $extraPrivacy;
                }
                
                if(isset($decoded['near_by'])){
                    $data['near_by'] = $near_by;
                }
                 
                if($accountSettingRow = $accountSettingTable->getRowByUserId($userId)){
                    
                    $data = array_merge($data,array(
                        'modifyDate'    => date("Y-m-d H:i:s")
                    ));
                    
                    $accountSettingRow->setFromArray($data);
                    $accountSettingRow->save();
                    
                }else{
                    $data = array_merge($data,array(
                        'userId'        => $userId,
                        'creationDate'  => date("Y-m-d H:i:s"),
                        'modifyDate'    => date("Y-m-d H:i:s")  
                    ));
                    $accountSettingRow = $accountSettingTable->createRow($data);
                    $accountSettingRow->save();
                }
               
                if(in_array($accountSettingRow->whoCanSeeLastSeen, array("1","2","3","4"))){
                    $customizeRemoveFriendsTable->deleteAllRowsbyUserId($userId,  Application_Model_DbTable_CustomizeRemoveFriends::WHO_CAN_SEE_LAST_SEEN);
                }
                
                if(in_array($accountSettingRow->whoCanSeeMe, array("1","2","3","4"))){
                    $customizeRemoveFriendsTable->deleteAllRowsbyUserId($userId,  Application_Model_DbTable_CustomizeRemoveFriends::WHO_CAN_SEE_ME);
                }
                
                if(in_array($accountSettingRow->whoCanSeePP, array("1","2","3","4"))){
                    $customizeRemoveFriendsTable->deleteAllRowsbyUserId($userId,  Application_Model_DbTable_CustomizeRemoveFriends::WHO_CAN_SEE_PROFILE_PIC);
                }
                
                 $this->common->displayMessage("Account setting updated successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
        
    }

    public function getUserPrivacySettingAction(){ 
        $userTable = new Application_Model_DbTable_Users();
        
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
     //   $userId = "497";
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            
            if(isset( $decoded['userDeviceId'])){
                $this->common->isUserLogin($userId,$userDeviceId);
            }
            
            if($userRow = $userTable->getRowById($userId)){
                $userSettingRow = $accountSettingTable->getRowByUserId($userId);
                $responseData = array(
                    'searchFullName'        => ($userSettingRow)?$userSettingRow->searchFullName:"1",
                    'searchCompleteName'    => ($userSettingRow)?$userSettingRow->searchCompleteName:"1",
                    'searchEmail'           => ($userSettingRow)?$userSettingRow->searchEmail:"1",
                    'searchPhone'           => ($userSettingRow)?$userSettingRow->searchPhone:"1",
                    'whoCanSeeLastSeen'     => ($userSettingRow)?$userSettingRow->whoCanSeeLastSeen:"1",
                    'whoCanSeeMe'           => ($userSettingRow)?$userSettingRow->whoCanSeeMe:"1",
                    'whoCanSeePP'           => ($userSettingRow)?$userSettingRow->whoCanSeePP:"1",
                    'whoCanSeeStatus'       => ($userSettingRow)?$userSettingRow->whoCanSeeStatus:"1",
                    'range'                 => ($userSettingRow)?$userSettingRow->range:"0",
                    'availableForDates'     => ($userSettingRow)?$userSettingRow->availableForDates:"0",
                    'auto_friends'          => ($userSettingRow)?$userSettingRow->auto_friends:"",
                    'extra_privacy'         => ($userSettingRow)?$userSettingRow->extra_privacy:"",
                    'near_by'               => ($userSettingRow)?$userSettingRow->near_by:"0"
              );
                
                $this->common->displayMessage("User account setting details","0",$responseData,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
    
    /**
     *  function for updating friends list which can see last seen time
     */
    
    public function updateCustomizeRemoveFriendsAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $addFriendsSet = $decoded['addFriendsSet'];
        $deleteFriendsSet = $decoded['deleteFriendsSet'];
        $remove_type = $decoded['remove_type']; 
      
   /*   $userSecurity = "afe2eb9b1de658a39e896591999e1b59";    // last_seen, who_can_see_me, profile_pic
        $userId = "14";
        $userDeviceId = "355004055809100";
        $addFriendsSet = array();
        $deleteFriendsSet = array(603,587,588,590,592);
        $remove_type = "last_seen";
     */  
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            
       //     $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                $customizeRemoveFriendsTable->updateCustomizeFriends($userId, $addFriendsSet, $deleteFriendsSet,$remove_type);
                
                $customize_friends_list = $customizeRemoveFriendsTable->getCustomizeFriends($userId,$remove_type);
                $this->common->displayMessage("Update costomize friends","0",$customize_friends_list,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
    
    /**
     * function for getting customize friends list
     */
    
    public function getCusomizeFriendsListAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $remove_type = $decoded['remove_type'];
        
    /*    $userSecurity = "afe2eb9b1de658a39e896591999e1b59";    // last_seen, who_can_see_me, profile_pic
        $userId = "14";
        $userDeviceId = "355004055809100";
        $remove_type = "who_can_see_me";
      */  
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            
            if(isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($userId,$userDeviceId);
            }
            
            if($userRow = $userTable->getRowById($userId)){
                $customize_friends_list = $customizeRemoveFriendsTable->getCustomizeFriends($userId,$remove_type);
                
                $this->common->displayMessage("Costomize friends list","0",$customize_friends_list,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
    
    public function testAction(){
        $url    = "http://api.quickblox.com/events.json";
        $data = '{"event": {"notification_type": "push", "environment": "production", "user": { "ids": "1326"}, "message": "payload=eyJhcHMiOnsic291bmQiOiJkZWZhdWx0IiwiYWxlcnQiOiJZb3UgaGF2ZSBqdXN0IHJlY2VpdmVkIGEgbmV3IG1lc3NhZ2UgZnJvbSBZdWxpYSBTeWRvcmVua28uIn0sIngiOnsiZGF0YSI6IntcInR5cGVcIjpcIm1lc3NhZ2VcIixcImZyb21fbmFtZVwiOlwiWXVsaWEgU3lkb3JlbmtvXCIsXCJmcm9tX2lkXCI6XCI4NTc1MTFcIixcImZyb21fZmFjZWJvb2tcIjpcIjEwMDAwMDM0OTA4MjYwM1wifSJ9fQ==", "push_type": "apns"}}';
        
        $headers = array(
                    'Content-Type: application/json',
                    "QuickBlox-REST-API-Version: 0.1.0",
                    "QB-Token: ee7db1a695eeb42830498720a7e9d56682472fbd"
                );
        
        $ch = curl_init();
        $u = curl_setopt($ch, CURLOPT_URL, $url);
        $p = curl_setopt($ch, CURLOPT_POST, true);
        $f = curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $h = curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $t = curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $c = curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $j = curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  //    $jsonn = json_encode($fields);
        
        $result = curl_exec($ch);
        echo "<pre>";
        print_r($result);exit;
        
    }
    
 }

?>