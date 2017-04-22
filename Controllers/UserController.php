<?php
class UserController extends My_Controller_Abstract{

    public function preDispatch() {
        parent::preDispatch();
        $this->fbAppId = "257955024398140";
        $this->fbsecret = "37c0762eddc12332c410a8552b25c939";
        $this->_helper->layout->disableLayout();
    }
    
    /**
      *  webservice for WA users using pagination
     */
    
    public function searchWaUsersAction(){
        
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();
        
        $decoded = $this->common->Decoded();
       // print_r($decoded);
        $userSecurity = $decoded['userSecurity'];
        
        $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
    //    $userDeviceId = "46572D18-87EB-4F93-A330-1503F955FF9D";
      
        
        if($userSecurity == $userSecurity){
//            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
//                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
//            }
            
            $userId = $decoded['userId'];
            $search = (isset($decoded['search']) && trim($decoded['search']) !="")?  trim($decoded['search']):"";
            $offset = isset($decoded['offset'])?$decoded['offset']:'0';

            if($userRow = $userTable->getRowById($userId)){
                                /**
                 *  call procedure for user search
                 */
                $db = Zend_Db_Table::getDefaultAdapter();
                $db->setFetchMode(Zend_Db::FETCH_OBJ);
               // echo "call searchUser('$search',$userId)";
                //die();
                $stmt = $db->query("call searchUser('$search',$userId)");
                $rows = $stmt->fetchAll();
                unset($stmt);

                //$totalRecords = count($userTable->fetchAll($select));
                $totalRecords = count($rows);
                
                $moreRecords = '0';
                $newOffset = '0';
                $userData = array();

                if($totalRecords){
//                    $paginationSelect = $select->order('userFullName')
//                                ->limit(50,$offset);


                    $userRowset = $rows;//$userTable->fetchAll($paginationSelect); 

                    foreach($userRowset as $searchUserRow){

                        $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $searchUserRow->userId);  // wa user is friend or not
                        $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $searchUserRow->userId);
                        
                        /**
                         *  code for finding friend status
                         *  1 means friend , 0 means no relation , 2 means outgoing pending friend request, 3 means incoming pending friend request
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
                           
                        }else{
                           $isTrustee = '0';
                        }
                        
                        $userSettingRow = $accountSettingTable->getRowByUserId($searchUserRow->userId);
                    
                        $searchFullName = ($userSettingRow)?$userSettingRow->searchFullName:"1";
                        $searchCompleteName = ($userSettingRow)?$userSettingRow->searchCompleteName:"1";
                        
                        $data = array(
                            'userId'            => $searchUserRow->userId,
                            'userName'          => $searchUserRow->userNickName,
                            'userFullName'      => $searchUserRow->userFullName,
                            'showFullName'      => $searchFullName,
                            'showCompleteName'  => $searchCompleteName,
                            'userCountryCode'   => $searchUserRow->userCountryCode,
                            'userPhone'         => $searchUserRow->userPhone,
                            'userEmail'         => $searchUserRow->userEmail,
                            'userImage'         => ($searchUserRow->userImage)?$this->makeUrl($searchUserRow->userImage):"",
                            'isOnline'          => $userSettingTable->isUserOnline($searchUserRow->userId),
                            'lastSeenTime'      => $searchUserRow->lastSeenTime,
                            'isFriend'          => $isFriend,
                            'isTrustee'         => $isTrustee,
                            'distance'          => ($userRow->userLatitude && $userRow->userLongitude)? $searchUserRow->distance:"0",
                            'quickBloxId'       => ($searchUserRow->quickBloxId)?$searchUserRow->quickBloxId:""
                            
                        );
                        
                        array_push($userData, $data);       
                    }

                    $newOffset = $offset + 50;

                    if($totalRecords > $newOffset){
                        $moreRecords = "1";
                    }

                }
               
                $responseData = array(
                    'response_string'   => 'user records',
                     'error_code'       => '0',
                     'more_records'     => $moreRecords,
                     'offset'           => $newOffset,
                     'result'           => $userData
                );

                echo json_encode($responseData);exit;
                
            }else{
                $this->common->displayMessage('This user does not exist',"1",array(),"2"); 
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"2");
        }
    }
    
    /**
      *  delete user webservice
     */
    
    public function deleteuserAction(){
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                //$this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userId));			
            
            $select = $userTable->select()
                            ->where('userId =?',$userId);
            
            if($userRow = $userTable->fetchRow($select)){
                $this->deleteUserRecords($userId);
                $userRow->delete();
                $this->common->displayMessage('User deleted successfully',"0",array(),"0");
                
            }else{
                $this->common->displayMessage('Account does not exist',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
        exit;
    }
     
    public function searchNearByUsersAction(){
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
     /* $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
        $userId = "1";
        $userDeviceId = "46572D18-87EB-4F93-A330-1503F955FF9D";
      */
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            $search = (isset($decoded['search']) && trim($decoded['search']) !="")?  trim($decoded['search']):"";
            $offset = isset($decoded['offset'])?$decoded['offset']:'0';
            
            if($userRow = $userTable->getRowById($userId)){
                
                $usersData = array();
                $moreRecords = '0';
                $newOffset = '0';
                $accountSettingRow = $accountSettingTable->getRowByUserId($userId);
                
                if($userRow->userLatitude && $userRow->userLongitude){
                    $distance="(((acos(sin((".$userRow->userLatitude."*pi()/180)) * sin((users.userLatitude*pi()/180))+cos((".$userRow->userLatitude."*pi()/180)) * cos((users.userLatitude*pi()/180)) * cos(((".$userRow->userLongitude."- users.userLongitude)* pi()/180))))*180/pi())*60*1.1515)";   // get distance in miles
                    
                    $friendIds = $friendTable->myfriendsIds($userId, true);
                    
                    $select = $userTable->select()->setIntegrityCheck(false)
                                ->from('users',array('*','distance' => $distance))
                                ->joinInner("accountSetting", "accountSetting.userId = users.userId",array('near_by','availableForDates'))
                                ->where('users.userStatus = ?','1')
                                ->where("$distance <=?",$accountSettingRow->range)
                                ->where('users.userLatitude is not null')
                                ->where('users.userLongitude is not null')
                                ->where('users.userLatitude !=?','0.000000')
                                ->where('users.userId !=?',$userId)
                                ->where('((users.userId in ('.$friendIds.')')
                                ->where('accountSetting.near_by =?)','1')
                                ->orWhere('(users.userId not in ('.$friendIds.')')
                                ->where('accountSetting.availableForDates =?','1')
                                ->where("$accountSettingRow->availableForDates =?))",'1');
                    
                    /**
                     *  for searching in near by users
                     */
                    
                    /**
                    * remove block users from the search
                    */
                    
                    $select_block_user = $blockUserTable->select()
                                                ->from('blockUsers',array("userId"))
                                                ->where('blockUserId =?',$userId)
                                                ->where('status =?',"1"); 
                
                    $select_block_user_by_me = $blockUserTable->select()
                                                    ->from('blockUsers',array("blockUserId"))
                                                    ->where('userId =?',$userId)
                                                    ->where('status =?',"1"); 

                    $select->where('users.userId not in ('.$select_block_user.')');

                    $select->where('users.userId not in ('.$select_block_user_by_me.')');

                
                    if($search){
                        $select_in_match_user_ids = "select userId from (
                                SELECT userId,userEmail, SUBSTRING_INDEX( SUBSTRING_INDEX( userFullName,  ' ', 1 ) ,  ' ', -1 ) AS first_name,
                                IF( LENGTH( userFullName ) - LENGTH( REPLACE( userFullName,  ' ',  '' ) ) >1, SUBSTRING_INDEX( SUBSTRING_INDEX( userFullName,  ' ', 2 ) ,  ' ', -1 ) , NULL ) AS middle_name, 
                                SUBSTRING_INDEX( SUBSTRING_INDEX( userFullName,  ' ', 3 ) ,  ' ', -1 ) AS last_name
                                FROM users
                                 ) temp
                                    WHERE first_name like '".$search."%' or middle_name like '".$search."%' or last_name like '".$search."%' or userEmail = '".$search."' or userPhone ='".$search."' or phoneWithCode ='".$search."'";
                        
                        $select = $select->where('users.userId in ('.$select_in_match_user_ids.')');
                    }
                    
                    /**
                     *  for searching in near by users code end
                     */
                    
                    $totalRecords = count($userTable->fetchAll($select));
                    
                    if($totalRecords){
                        
                        $paginationSelect = $select->order('distance')
                                                    ->limit(10,$offset);          
                        
                        $userRowset = $userTable->fetchAll($paginationSelect);
                        
                        foreach ($userRowset as $searchUserRow){
                            $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $searchUserRow->userId);  // wa user is friend or not
                            $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $searchUserRow->userId);
                            
                            /**
                            *  code for finding friend status
                            *  1 means friend , 0 means no relation , 2 means outgoing pending friend request, 3 means incoming pending friend request
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
                                'userId'            => $searchUserRow->userId,
                                'userName'          => $searchUserRow->userNickName,
                                'userFullName'      => $searchUserRow->userFullName,
                                'userCountryCode'   => $searchUserRow->userCountryCode,
                                'userPhone'         => $searchUserRow->userPhone,
                                'userEmail'         => $searchUserRow->userEmail,
                                'userImage'         => ($searchUserRow->userImage)?$this->makeUrl($searchUserRow->userImage):"",
                                'isOnline'          => $userSettingTable->isUserOnline($searchUserRow->userId),
                                'lastSeenTime'      => $searchUserRow->lastSeenTime,
                                'isFriend'          => $isFriend,
                                'isTrustee'         => $isTrustee,
                                'distance'          => $searchUserRow->distance ? round($searchUserRow->distance, 2):"0",
                                'userAge'           => $searchUserRow->userAge,
                                'userDob'           => $searchUserRow->userDob,
                                'quickBloxId'       => ($searchUserRow->quickBloxId)?$searchUserRow->quickBloxId:"",
                                'availableForDates' => $searchUserRow->availableForDates,
                            );
                            
                            array_push($usersData, $data);
                        }

                        $newOffset = $offset + 10;

                        if($totalRecords > $newOffset){
                            $moreRecords = "1";
                        }
                    }
                    
                }
                
                $responseData = array(
                    'response_string'   => 'Near by users',
                     'error_code'       => '0',
                     'more_records'     => $moreRecords,
                     'offset'           => $newOffset,
                     'result'           => $usersData
                );

                echo json_encode($responseData);exit;
                
            }else{
                $this->common->displayMessage('This user does not exist',"1",array(),"2"); 
            }
            
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
        
        exit;
    }
    
    
    
   
    
    public function deleteUserByPhoneAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                        ->where('userPhone =?',$this->getRequest()->getQuery('phone'));
        
        if($userRow = $userTable->fetchRow($select)){
            $userRow->delete();
            $this->common->displayMessage('user deleted successfully','0',array(),"0");
        }else{
            $this->common->displayMessage('user does not exist','1',array(),"1");
        }
        
        exit;
    }
    
    /**
     *  delete user directly from web
     */
    
    
    public function deleteAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $search = $this->getRequest()->getQuery('search');
        
        $select = $userTable->select()
                        ->where('(userPhone =?',$search)
                        ->orWhere('userEmail =?)',$search);
        
        if($userRow = $userTable->fetchRow($select)){
            $userRow->delete();
            $this->common->displayMessage('user deleted successfully','0',array(),"0");
        }else{
            $this->common->displayMessage('user does not exist','1',array(),"1");
        }
        
        exit;
    }
   
    
    /**
     * function for update user location (latitude and longitude)
     */
    
    public function updateUserLocationAction(){
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userTable = new Application_Model_DbTable_Users();
        
        if($userSecurity == $this->servicekey){
            $userId = $decoded['userId'];
            $userLatitude = $decoded['userLatitude'];
            $userLongitude = $decoded['userLongitude'];
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userId,$userLatitude,$userLongitude));
            

            if($userRow = $userTable->getRowById($userId)){
                
                if(($userLatitude !="0.000000") && ($userLongitude !="0.000000")){
                    $userRow->userLatitude = $userLatitude;
                    $userRow->userLongitude = $userLongitude;
                    $userRow->save();   
                }
                
                $this->common->displayMessage('user location updated successfully',"0",array(),"0");

            }else{
                $this->common->displayMessage('Account does not exist',"1",array(),"2");
            }

        }else{
           $this->common->displayMessage('You could not access this web-service',"1",array(),"3"); 
        }
        
        exit;
    }

    public function updateUserDeviceDetailsAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        
        if($userSecurity == $this->servicekey){
            
            $userId             = $decoded['userId'];
            $userDeviceId       = $decoded['userDeviceId'];
            $userDeviceToken    = $decoded['userDeviceToken'];
            $userDeviceType     = $decoded['userDeviceType'];
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken,$userDeviceType));
            
            if($userRow = $userTable->getRowById($userId)){
                $userDeviceDetails = array(
                    "userDeviceId"      => $userDeviceId, 
                    "userDeviceToken"   => $userDeviceToken, 
                    "userDeviceType"    => $userDeviceType,
                    "userModifieddate"  => date('Y-m-d H:i:s')
                );

                $userTable->updateUserDeviceDetails($userRow->userId, $userDeviceDetails);
                $this->common->displayMessage('User device details updated successfully',"0",array(),"0");
                
            }else{
                $this->common->displayMessage('Account does not exist',"1",array(),"2");
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3"); 
        }
        
        exit;
        
    }
    
    /**
     *  function check user is online or not
     */
    
    public function isUserOnlineAction(){
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        
        if($userSecurity == $this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            $this->common->checkEmptyParameter1(array($userId,$userSecurity));
            
            if($userRow = $userTable->getRowById($userId)){
                $isUserOnline = ($userSettingTable->isUserOnline($userId))?"1":"0";
                
                $this->common->displayMessage('online status',"0",array('isUserOnline' => $isUserOnline),"0");
            
            }else{
                $this->common->displayMessage('User account is not exits',"1",array(),"2"); 
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3"); 
        }
    }

    
    /**
     *  function change online status
     */
    
    public function changeOnlineStatusAction(){
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        
        if($userSecurity == $this->servicekey){
            $userId = $decoded['userId'];
            $deviceId = $decoded['userDeviceId'];
            $onlineStatus = $decoded['onlineStatus'];
            $userDeviceToken = trim($decoded['userDeviceToken']);
            //$myfile = fopen($_SERVER['DOCUMENT_ROOT']."/newfile.txt","a") or die("Unable to open file!");
            //$txt = date("Y-m-d H:i:s");
            //fwrite($myfile, $txt);
            //$txt = "online status : - ".print_r($decoded,true)."\n";
            //fwrite($myfile, $txt);
            //fclose($myfile);
//            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
//                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
//            }
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$deviceId));
            
            if($userRow = $userTable->getRowById($userId)){
                
                if($userSettingRow = $userSettingTable->getRowByUserIdAndDeviceId($userId, $deviceId)){
                   
                    $userSettingRow->isOnline = ($onlineStatus)?"1":"0";
                    
                    if($userDeviceToken && ($userDeviceToken!="123456789")){
                        $userSettingRow->userDeviceToken = $userDeviceToken;
                    }
                    
                    $userSettingRow->save();
                    
                    if($onlineStatus != "1"){
                        if(!$userSettingTable->isUserOnline($userId)){
                            $userRow->lastSeenTime = date("Y-m-d H:i:s");
                        }
                    }
                    
                    $userRow->userModifieddate = date("Y-m-d H:i:s");
                    $userRow->save();
                    
                    $this->common->displayMessage('status change successfully',"0",array(),"0");
                }else{
                    $this->common->displayMessage('already logout user',"1",array(),"2");
                }
                
            }else{
                $this->common->displayMessage('User account is not exits',"1",array(),"3"); 
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"4"); 
        }
    }
    
    
    
    /**
     * function for getting facebook friends
     */
    
   public function facebookUserAction(){
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $fbAccessToken = $decoded['fbAccessToken'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $search = isset($decoded['search'])?trim($decoded['search']):false;
        
        if($this->servicekey){
            
            if(isset($decoded['userId']) && isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($decoded['userId'],$decoded['userDeviceId']);
            }
            
            if($userRow = $userTable->getRowById($userId)){
               $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId,$fbAccessToken));
                
                try{ 
                    $fr_url = 'https://graph.facebook.com/me/friends?access_token='.$fbAccessToken.'&fields=id,name,email,picture.width(100).height(100).type(large)';
                    $fr_json = file_get_contents($fr_url);
                    $fr_res = json_decode($fr_json,true);
                }
                catch(Exception $e)
                {
                        $this->common->displayMessage($e->getMessage(),"1",array(),"4");
                }
                
                $fbResponseData = $fr_res['data'];
                
                $responseData = array();
                
                foreach($fr_res['data'] as $fbData){
                    if(($search =="") || (strpos($fbData['name'], $search)!==false)){
                        $isWaUser = "0";
                        $isFriend = "0";
                        $isTrustee = "0";

                        $waUserRow = $userTable->checkDuplicateRecordByField("userFbId", $fbData['id']);

                        if(!$waUserRow && isset($fbData['email']) && ($fbData['email'] !="")  ){
                            $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $fbData['email']);
                        }
                        
                        $friendRow = $friendTable->getRowByUserIdAndFacebookId($userId, $fbData['id']);
                        $trusteeRow = $trusteeTable->getRowByUserIdAndFacebookId($userId, $fbData['id']);
                        
                        if($waUserRow){
                            $isWaUser = "1";

                            if(!$friendRow){
                                $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $waUserRow->userId);
                            }
                            
                            if(!$trusteeRow){
                               $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $waUserRow->userId);
                            }
                        }
                        
                        /**
                        *  code for finding friend status
                        *  1 means friend , 0 means no relation , 2 means outgoing pending friend request, 3 means incoming pending friend request
                        */

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
                            *  1 means trustee , 0 means no relation , 2 means outgoing trustee request
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
                            'facebookId'    => $fbData['id'],
                            'friendName'    => $fbData['name'],
                            'friendEmail'   => isset($fbData['email'])?$fbData['email']:"",
                            'friendpicture' => isset($fbData['picture'])?$fbData['picture']['data']['url']:"",
                            'isWaUser'      => $isWaUser,
                            'isFriend'      => $isFriend,
                            'isTrustee'     => $isTrustee
                        );

                        $responseData[] = $data;
                   } 
                }
                $this->common->displayMessage('facebook users',"0",$responseData,"0"); 
                
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"3"); 
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"2"); 
        }
    }
    
    
   public function checkAvailabilityAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        if($this->getRequest()->isPost()){
            $fieldName = $this->getRequest()->getPost('fieldName','');
            $fieldvalue = $this->getRequest()->getPost('fieldValue','');
            
            if($userRow = $userTable->checkDuplicateRecordByField($fieldName, $fieldvalue)){
              echo "exist";  
            }else{
              echo "available";
            }
            exit;
            
        }
    }
    
   public function updateSearchSettingAction(){
        $userTable = new Application_Model_DbTable_Users();
        $searchSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        $searchCompleteName = $decoded['searchName'];
        $searchEmail = $decoded['searchEmail'];
        $searchPhone = $decoded['searchPhone'];
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId));
            
            if(isset($decoded['userDeviceId'])){
                $this->common->isUserLogin($userId,$userDeviceId);
            }
            
            
            if($userRow = $userTable->getRowById($userId)){
                
                $data = array(
                    'searchCompleteName'  => $searchCompleteName ? "1":"0",
                    'searchEmail'         => $searchEmail ? "1":"0",
                    'searchPhone'         => $searchPhone ? "1":"0",
                    'modifyDate'          => date("Y-m-d H:i:s")
                );
                
                if($userSearchSettingRow = $searchSettingTable->getRowByUserId($userId)){
                    $userSearchSettingRow->setFromArray($data);
                    $userSearchSettingRow->save();
                    
                }else{
                    $data = array_merge($data,array(
                        'userId'        => $userId,
                        'creationDate'  => date("Y-m-d H:i:s"),
                    ));
                    
                    $userSearchSettingRow = $searchSettingTable->createRow($data);
                    $userSearchSettingRow->save();
                }
                
                $this->common->displayMessage("Search setting updated successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
        
    }
    
    public function updateVolunteerSettingAction(){
        
        $userTable = new Application_Model_DbTable_Users();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $is_volunteer = ($decoded['is_volunteer'])?$decoded['is_volunteer']:"0";
    /*    
        $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
        $userId = 45;
        $userDeviceId = "1225";
        $is_volunteer = 0;
      */  
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                $data = array(
                     'is_volunteer' => $is_volunteer,
                     'modifyDate'   => date("Y-m-d H:i:s")
                );
                
                if($accountSettingRow = $accountSettingTable->getRowByUserId($userId)){
                    $accountSettingRow->setFromArray($data);
                    $accountSettingRow->save();
                    
                }else{
                    $data = array_merge($data,array(
                        'userId' => $userId
                    ));
                    
                    $accountSettingRow = $accountSettingTable->createRow($data);
                    $accountSettingRow->save();
                }
                
                $this->common->displayMessage("Volunteer setting updated successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
        
    }
    
   public function deleteUserRecords($userId){
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userRow = $userTable->getRowById($userId);
       //$phoneContactTable->deleteContact($userId);
        
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query("delete from phoneContact where user_id='{$userId}' OR wa_user_id='{$userId}'");
       // $db->query("delete from wa_payment_info where user_id='{$userId}'");
        //$db->query("delete from wa_credits where user_id='{$userId}'");
        $db->query("delete from wa_event_send_details where user_id='{$userId}'");
        $db->query("delete from wa_event_trustee_response where user_id='{$userId}'");
        $db->query("delete from wa_points where user_id='{$userId}'");
        $db->query("delete from wa_receivers where receiver_id='{$userId}'");
        //$db->query("delete from wa_testaments where user_id='{$userId}'");
        //$db->query("delete from wa_users_credit_card where user_id='{$userId}'");
        $db->query("delete from wa_trustees where user_id='{$userId}' OR receiver_id='{$userId}'");
        $db->query("delete from wa where user_id='{$userId}'");
        $db->query("delete from user_notifications where user_id='{$userId}'");
        $db->query("delete from usrSetting where userId='{$userId}'");
        $db->query("delete from trustees where userId='{$userId}'");
        $db->query("delete from secretGroup where adminId='{$userId}'");
        $db->query("delete from secGroupMembers where memberId='{$userId}'");
        $db->query("delete from groups where adminId='{$userId}'");
        $db->query("delete from groupMembers where memberId='{$userId}'");
        $db->query("delete from editFriendTrusteeDetails where userId='{$userId}'");
        $db->query("delete from customize_remove_frnds where user_id='{$userId}' OR friend_id='{$userId}'");
        $db->query("delete from change_user_details where user_id='{$userId}'");
        $db->query("delete from bonus_points where userId='{$userId}'");
        $db->query("delete from blockUsers where userId='{$userId}' OR blockUserId='{$userId}'");
        $db->query("delete from accountSetting where userId='{$userId}'");
        
   //   $groupTable->deleteGroupsByAdminId($userId);            // delete groups created by users
   //   $groupMemberTable->deleteUserFromGroups($userId);       // delete users from other groups
        $friendTable->deleteUserFriends($userId);
        $trusteeTable->deleteUserTrustee($userId);
        
        $acceptTrusteesRowset = $trusteeTable->getIncomingTrusteesRequest($userId);
        
        foreach ($acceptTrusteesRowset as $trusteeRow){
            if($trusteeRow->status == "1"){
                
                $message = $userRow->userFullName. " deleted your trustee relationship";

                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeRow->userId);
                
                $userImage = ($userRow->userImage)?$this->makeUrl($userRow->userImage):"";
                
                foreach ($userLoginDeviceRowset as $loginDeviceRow){

                    if($loginDeviceRow->userDeviceType == "iphone"){
                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'incoming_friend_request', 'userId'=>$userRow->userId,'userName'=>$userRow->userFullName,'userImage'=>$userImage);
                    }else{
                        
                        $resultData =  array(
                                'userImage' => $userImage,
                                'userId'    => $userRow->userId,
                                'userName'  => $userRow->userFullName
                         );
                        
                        $payload = array(
                            'message'   => $message,
                            'type'      => "delete_trustee_relationship",
                            'result'    => $resultData
                       );
                        $payload = json_encode($payload);
                    }
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                }

                
                
            }else{
                $trusteeRow->delete();
            }
        }
   }
   
   public function updateExtraPrivacyAction(){
        $userTable = new Application_Model_DbTable_Users();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        $auto_friends = (isset($decoded['auto_friends']) && $decoded['auto_friends']) ?$decoded['auto_friends']:"";
        $extra_privacy = (isset($decoded['extra_privacy']) && $decoded['extra_privacy']) ?$decoded['extra_privacy']:"";
        
    /*  $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
        $userId = "46";
        $auto_friends = "on";
        $userDeviceId = "354994058074523";
      */  
        if($userSecurity == $this->servicekey){
            
    //      $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
//          $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                    $accountSettingRow = $accountSettingTable->getRowByUserId($userId);
                    
                    if($accountSettingRow){
                        if($auto_friends){
                            $accountSettingRow->auto_friends = $auto_friends;
                        }
                        
                        if($extra_privacy){
                            $accountSettingRow->extra_privacy = $extra_privacy;
                        }
                        $accountSettingRow->modifyDate = date("Y-m-d H:i:s");
                        $accountSettingRow->save();
                        
                    }else{
                         $data = array(
                            'userId'        => $userRow->userId,
                            'creationDate'  => date("Y-m-d H:i:s"),
                            'modifyDate'    => date("Y-m-d H:i:s")
                        );
                        
                        $accountSettingRow = $accountSettingTable->createRow($data);
                        $accountSettingRow->save();
                    }
                 /**
                  *  make auto friend for incoing request
                  */   
                    
                 if($extra_privacy == Application_Model_DbTable_AccountSetting::EXTRA_PRIVACY_OFF){
                    // $phoneContactTable->makeAutoFriend($userRow);  
                     $phoneContactTable->makeFriends($userRow); 
                    
                 }   
                 
                 /**
                  * make friend to my contact
                  */
                 if($auto_friends == Application_Model_DbTable_AccountSetting::AUTO_FRIEND_ON){
                     
                     //$phoneContactTable->makeFriendToContact($userRow);
                 }
                 
                 $contactFriendSet = $phoneContactTable->getMyContactFriends($userId,$userDeviceId);
                 $this->common->displayMessage('Setting updated',"0", $contactFriendSet,"0");
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
   }
   
   
   public function uploadCoverImageAction(){
        $decoded = $this->common->Decoded();
        $userSecurity = $this->getRequest()->getPost('userSecurity','');
        $userDeviceId = $this->getRequest()->getPost('userDeviceId','');
        $userTable = new Application_Model_DbTable_Users();
        
        if($userSecurity == $this->servicekey){
            $userId = $this->getRequest()->getPost('userId','');
            
            $this->common->checkEmptyParameter1(array($userId,$userSecurity,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                
                $response = $this->common->uploadImage($_FILES["file"],"users");

                if(isset($response['new_file_name'])){
                    
                    $file_path = "images/users/".$response['new_file_name'];
                    
                    $userRow->userCoverImage = $file_path;
                    $userRow->userModifieddate = date("Y-m-d H:i:s");
                    
                    $userRow->save();
                    
                    $this->common->displayMessage($this->makeUrl($file_path),"0",array(),"0");

                }elseif(isset($response['error'])){
                    if($response['error'] == "Invalid_file"){
                        $this->common->displayMessage("Invalid file","1",array(),"4");
                    }else{
                        $this->common->displayMessage($response['error'],"1",array(),"5");
                    }
                }else{

                }
            }else{
                $this->common->displayMessage("User account not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
    
   public function updateDobAction(){
        $userTable = new Application_Model_DbTable_Users();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $userDob = $decoded['userDob'];
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId,$userDob));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                 $userRow->userDob = $userDob;
                 $userRow->userModifieddate = date("Y-m-d H:i:s");
                 $userRow->save();
                 
                 $this->common->displayMessage('Date of birth updated successfully',"0", array(),"0");
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"2");
            }
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
   }
   
   public function blockUserAction(){
        $userTable = new Application_Model_DbTable_Users();
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $decoded = $this->common->Decoded();
        
//       $decoded = array(
//            'userId'        => "199",
//            'userSecurity'  => "afe2eb9b1de658a39e896591999e1b59",
//            'userDeviceId'  => "E693168F-22E5-4945-A5AE-8C5054401F4A",
//            'blockUserId'   => "249"
//        );
       
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $blockUserId = $decoded['blockUserId'];
        $isBlock = $decoded['isBlock'];
        
        if($userSecurity == $this->servicekey){
            
            if(($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())){
                
                $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId,$blockUserId));
                $this->common->isUserLogin($userId,$userDeviceId);
                
                $blockUserRow = $blockUsersTable->getRowByUserIdAndBlockUserId($userId, $blockUserId);
                if($isBlock=="1"){
                    if($blockUserRow) {
                         if($blockUserRow->status == "1"){
                             $this->common->displayMessage("user already block","1",array(),"4");
                         }else{
                             $blockUserRow->status = Application_Model_DbTable_BlockUsers::BLOCK_STATUS_ACTIVE;
                             $blockUserRow->modifiedDate = date("Y-m-d H:i:s");
                             $blockUserRow->save();
                         }
                         
                    }
                    else{
                         $data = array(
                            'userId'        => $userId,
                            'blockUserId'   => $blockUserId,
                            'status'        => Application_Model_DbTable_BlockUsers::BLOCK_STATUS_ACTIVE,
                            'modifiedDate'  => date("Y-m-d H:i:s")
                        );

                        $blockUserRow = $blockUsersTable->createRow($data);
                        $blockUserRow->save();
                    }
                    
                    $friendTable->removeFriendRelationShip($userId, $blockUserId);
                    $trusteeTable->removeTrusteeRelationShip($userId, $blockUserId);
                    $blockUserRow = $blockUsersTable->getBlockRelation($blockUserId,$userId);
                    $this->common->displayMessage("user block successfully","0",array('block_by_user'=>$blockUserRow),"0");
                    
                }else{
                    if($blockUserRow){
                       $blockUserRow = $blockUsersTable->getBlockRow($userId,$blockUserId);
                       $blockUserRow->delete();
                       $friendTable->removeFriendRelationShip($userId, $blockUserId);
                      $trusteeTable->removeTrusteeRelationShip($userId, $blockUserId);
                       $blockUserRow = $blockUsersTable->getBlockRelation($blockUserId,$userId);
                       $this->common->displayMessage("user un block successfully","0",array('block_by_user'=>$blockUserRow),"0");
                    }else{
                        $this->common->displayMessage("user already un block","1",array(),"5");
                    }
                }
                
                $this->common->displayMessage("Block user setting updated successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
   }
   
   public function blockUsersListAction(){
       $userTable = new Application_Model_DbTable_Users();
       $blockUsersTable = new Application_Model_DbTable_BlockUsers();
   
       $decoded = $this->common->Decoded();
       
     /*   $decoded = array(
          'userId'        => "21",
          'userSecurity'  => "afe2eb9b1de658a39e896591999e1b59",
          'userDeviceId'  => "01ADFAB2-2EE7-49AB-9AEC-DE4BC9F548CA"
        );
     */   
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            
            if(($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())){
                
                $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
                $this->common->isUserLogin($userId,$userDeviceId);
                
                $blockUserRowset = $blockUsersTable->blockUsers($userId,true);
                
                $data = array();
                
                foreach ($blockUserRowset as $blockUserRow){
                    $data[] = $blockUserRow->blockUserId;
                }
                
                $this->common->displayMessage("Block users listing","0",$data,"0");
                
            }else{
                $this->common->displayMessage("User account is not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
       
   }
   
   public function testAction(){
    echo $this->makeUrl("/testing");exit;
   }
}
?>
