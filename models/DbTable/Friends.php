<?php
class Application_Model_DbTable_FriendsRow extends Zend_Db_Table_Row_Abstract{
    public function getName($logged_user_id){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $friendName = "";
        $logged_user_id = intval($logged_user_id);
        
        if($this->friendId){
            $other_user_id = ($logged_user_id == $this->user_id)?$this->friendId:$this->friendId;

            if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId(intval($logged_user_id),$other_user_id)){
                $friendName = $editFriendTrusteeRow->name;
            }
        }
        
        if(!$friendName){
            $friendName =($this->friendId)?$this->userNickName:$this->friendName;  
        }
            
        return $friendName;
    }
    
}
class Application_Model_DbTable_Friends extends Zend_Db_Table_Abstract{
    protected $_name = 'friends';
    protected $_id   = 'id';
    protected $_rowClass = "Application_Model_DbTable_FriendsRow";
    
    const STATUS_INACTIVE = '0';   // pending friend request
    const STATUS_ACTIVE   = '1';
    const STATUS_DELETED  = '2';
    const STATUS_BLOCK    = '3';
    
    public function getRowById($id){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('id =?',$id);
        
        return $friendTable->fetchRow($select);
    }
    
    
    /**
     * 
     * @param this function will check 2 wa users are friend or not first we will check request send by login user and 
     * 
     * @param type $userId and $friendId
     * @return type friend row
     * 
     */
    
    public function getRowByUserIdAndFriendId($userId,$friendId){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('(userId =?',$userId)
                        ->where('friendId =?)',$friendId)
                        ->orWhere('(userId =?',$friendId)
                        ->where('friendId =?)',$userId);
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;
    }
    
    public function getRowByUserIdAndEmail($userId,$email){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('userId =?',$userId)
                        ->where('friendEmail =?',$email);
    
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;  
    }
    
    public function getRowByUserIdAndPhone($userId,$phone){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('userId =?',$userId)
                        ->where('friendPhone =?',$phone);
    
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;  
    }
    
    public function getRowByUserIdAndFacebookId($userId,$facebookId){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('userId =?',$userId)
                        ->where('facebookId =?',$facebookId);
    
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;
    }
    
    /**
     *  update new user id in incoming request
     */
    
    public function updateNewUserId($userId){
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        
        if($userRow = $userTable->getRowById($userId)){
            /**
             *  get all friends with new user phone number and update his    user id
            */

            $phoneSelect = $friendTable->select()
                            ->where('friendPhone =?',$userRow->phoneWithCode)
                            ->where('friendId is ?',new Zend_Db_Expr('null'));

            $friendRowset = $friendTable->fetchAll($phoneSelect);

            foreach($friendRowset as $friendRow){
                $friendRow->friendId = $userRow->userId;
                $friendRow->save();
            }

            /**
             *  get all friends with new user email and update his user id
             */

            $emailSelect = $friendTable->select()
                            ->where('friendEmail =?',$userRow->userEmail)
                            ->where('friendId is ?',new Zend_Db_Expr('null'));

            $friendRowset = $friendTable->fetchAll($emailSelect);

            foreach($friendRowset as $friendRow){
                $friendRow->friendId = $userRow->userId;
                $friendRow->save();
            }

            
        }
        
    }
    
    public function addTrusteeAsFriend($trusteeRow){
        $friendTable = new Application_Model_DbTable_Friends();
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        
        if($trusteeRow){
            $email = $trusteeRow->trusteeEmail;
            $phone = $trusteeRow->trusteePhone;
            $userId = $trusteeRow->userId;
             
            if($trusteeRow->trusteeId){
                /// for wa users
                if($friendRow = $friendTable->getRowByUserIdAndFriendId($trusteeRow->userId, $trusteeRow->trusteeId)){
                   if($friendRow->status !="1"){
                      $friendRow->status = "1";
                      $friendRow->modifyDate = date("Y-m-d H:i:s");
                      $friendRow->save();
                   } 
                }else{
                    $data = array(
                        'userId'      => $trusteeRow->userId,
                        'friendId'    => $trusteeRow->trusteeId,
                        'status'      => "1",
                        'modifyDate'  => date("Y-m-d H:i:s"),
                        'creationDate'  => date("Y-m-d H:i:s"),
                        'acceptDate'    => date("Y-m-d H:i:s")
                    );
                    
                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save();
                }
            }else{
                  // for non wa users
                
                if($email){
                    $friendRow = $friendTable->getRowByUserIdAndEmail($userId, $email);
                }

                if($phone && !$friendRow){
                    $friendRow = $friendTable->getRowByUserIdAndPhone($userId, $phone);
                }

                if($friendRow){
                     if(($friendRow->status !="1") && ($friendRow->status !="0")){
                         $friendRow->modifyDate = date('Y-m-d H:i:s');
                         $friendRow->status = "1";
                         $friendRow->acceptDate = date('Y-m-d H:i:s');
                         $friendRow->save();
                     }

                }else{
                   $data = array(
                        'userId'        => $trusteeRow->userId,
                        'friendId'      => new Zend_Db_Expr('null'),
                        'friendName'    => $trusteeRow->trusteeName,
                        'friendEmail'   => $trusteeRow->trusteeEmail,
                        'friendPhone'   => $trusteeRow->trusteePhone, 
                        'friendImage'   => "",
                        'status'        => '1',
                        'creationDate'  => date('Y-m-d H:i:s'),
                        'modifyDate'    => date('Y-m-d H:i:s'),
                        'acceptDate'    => date('Y-m-d H:i:s')
                    ); 
                   
                   $friendRow = $friendTable->createRow($data);
                   $friendRow->save();
                }
                
                
            }
        }
        
    }
    
   
    /**
     *  send friend request to user at time of sending trustee request
     */ 
     
    public function sendFriendRequest($trusteeRow){
        $friendTable = new Application_Model_DbTable_Friends();
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        
        if($trusteeRow){
            
             $email = $trusteeRow->trusteeEmail;
             $phone = $trusteeRow->trusteePhone;
             $userId = $trusteeRow->userId;
            // trustee is a wa user
            
            if($trusteeRow->trusteeId){
                if($friendRow = $friendTable->getRowByUserIdAndFriendId($trusteeRow->userId, $trusteeRow->trusteeId)){
                   if(($friendRow->status !="1") && ($friendRow->status !="0")){
                      $friendRow->userId = $trusteeRow->userId;
                      $friendRow->friendId = $trusteeRow->trusteeId;
                      $friendRow->status = "0";
                      $friendRow->modifyDate = date("Y-m-d H:i:s");
                      $friendRow->save();
                   } 
                }else{
                    $data = array(
                        'userId'        => $trusteeRow->userId,
                        'friendId'      => $trusteeRow->trusteeId,
                        'status'        => "0",
                        'modifyDate'    => date("Y-m-d H:i:s"),
                        'creationDate'  => date("Y-m-d H:i:s")
                    );
                    
                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save();
                }
            }else{
                // if trustee is not a wa user 
                
                if($email){
                    $friendRow = $friendTable->getRowByUserIdAndEmail($userId, $email);
                }

                if($phone && !$friendRow){
                    $friendRow = $friendTable->getRowByUserIdAndPhone($userId, $phone);
                }

                if($friendRow){
                     if(($friendRow->status !="1") && ($friendRow->status !="0")){
                         $friendRow->modifyDate = date('Y-m-d H:i:s');
                         $friendRow->status = "0";
                         $friendRow->save();
                     }

                }else{
                   $data = array(
                        'userId'        => $trusteeRow->userId,
                        'friendId'      => new Zend_Db_Expr('null'),
                        'friendName'    => $trusteeRow->trusteeName,
                        'friendEmail'   => $trusteeRow->trusteeEmail,
                        'friendPhone'   => $trusteeRow->trusteePhone, 
                        'friendImage'   => "",
                        'status'        => '0',
                        'creationDate'  => date('Y-m-d H:i:s'),
                        'modifyDate'    => date('Y-m-d H:i:s')
                    ); 
                   
                   $friendRow = $friendTable->createRow($data);
                   $friendRow->save();
                }
                
            }
            
            return $friendRow;
        }
        
    }
    
    public function deleteUserFriends($userId){
        $db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        
        $db->update('friends',array("status"=>"2","modifyDate"=>date('Y-m-d H:i:s')),"userId = ".$userId." or friendId =".$userId);
    }
    
    
    
    public function myfriends($userId,$returnSelect = false){
        $friendTable = new Application_Model_DbTable_Friends();
        
        // outgoing
            $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId'))
                    ->where('friends.userId =?',$userId)
                    ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                    ->where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId);

            // incoming

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId'))
                    ->where('friends.friendId =?',$userId)
                    ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                    ->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId);

            if($last_request_time){
                $select1 = $select1->where('friends.modifyDate >?', $last_request_time);
                $select2 = $select2->where('friends.modifyDate >?', $last_request_time);
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2))
                        ->order('userFullName');
            
            
            if($returnSelect){
                return $select;
            }

            return $friendTable->fetchAll($select);
        
    }
    
    
    public function myfriendsWeb($userId,$returnSelect = false){
        $friendTable = new Application_Model_DbTable_Friends();
        
        // outgoing
            $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId'))
                    ->joinLeft('editFriendTrusteeDetails', "editFriendTrusteeDetails.otherUserId = friends.friendId AND editFriendTrusteeDetails.userId=$userId",array('name', 'hideProfile'))
                    ->where('friends.userId =?',$userId)
                    ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                    ->where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId);

            // incoming

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId'))
                    ->joinLeft('editFriendTrusteeDetails', "editFriendTrusteeDetails.otherUserId = friends.userId AND editFriendTrusteeDetails.userId=$userId",array('name', 'hideProfile'))
                    ->where('friends.friendId =?',$userId)
                    ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                    ->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId);

            if($last_request_time){
                $select1 = $select1->where('friends.modifyDate >?', $last_request_time);
                $select2 = $select2->where('friends.modifyDate >?', $last_request_time);
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2))
                        ->order('userFullName');
            
            //echo $select;die();
            if($returnSelect){
                return $select;
            }

            return $friendTable->fetchAll($select);
        
    }
    
    public function myfriendsIds($userId,$returnSelect = false){
        $friendTable = new Application_Model_DbTable_Friends();
        
        // outgoing
        $select1 = $friendTable->select()->setIntegrityCheck(false)
                ->from('friends',array('friendId'))
                ->joinInner('users', 'users.userId = friends.friendId',array())
                ->where('friends.userId =?',$userId)
                ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                ->where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                ->where('users.userId !=?',$userId);

        // incoming
        $select2 = $friendTable->select()->setIntegrityCheck(false)
                ->from('friends',array('userId'))
                ->joinInner('users', 'users.userId = friends.userId',array())
                ->where('friends.friendId =?',$userId)
                ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_ACTIVE)
                ->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                ->where('users.userId !=?',$userId);

        $select = $friendTable->select()
                    ->union(array($select1, $select2));

        if($returnSelect){
            return $select;
        }

        return $friendTable->fetchAll($select);
        
    }
    
    public function searchFriends($userId,$order=false,$searchText=null,$result=false){
        //echo $searchText;die();
        $friendTable = new Application_Model_DbTable_Friends();
        $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.userId =?',$userId)
                    ->where('friends.status =?','1');
                 if($searchText){
                     $select1->where("userNickName LIKE '%$searchText%'");
                    }
                   $select1->where('(friends.friendId is null')
                    
                   ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                    
                    
            
            // incoming
            

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.friendId =?',$userId)
                    ->where('friends.status =?','1');
                if($searchText){
                     $select2->where("userNickName LIKE '%$searchText%'");
                    }
                    $select2->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                   

            if($last_request_time){
                $select1 = $select1->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time);
                                    
                $select2 = $select2->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time); 
                                    
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2));
                        ($order)?$select->order('lastMessageDate DESC'):$select->order('userFullName DESC');
                        if($result){
                            $friendRowset = $friendTable->fetchAll($select)->toArray();
                            return $friendRowset;
                        }else{
                            return $select;
                        }
        
    }
    public function getMyFriendListChat($userId,$order=false,$searchText=null,$result=false,$alphabate=null){
        //echo $searchText;die();
        $friendTable = new Application_Model_DbTable_Friends();
        $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('editFriendTrusteeDetails', "editFriendTrusteeDetails.otherUserId = friends.friendId AND editFriendTrusteeDetails.userId=$userId",array('name', 'hideProfile'))    
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.userId =?',$userId)
                    ->where('friends.status =?','1');
                    if($searchText!=null &&  $alphabate==null){
                     $select1->where("userNickName LIKE '%$searchText%'");
                    }
                    if($searchText!=null &&  $alphabate!=null){
                     $select1->where("userNickName LIKE '$searchText%'");
                    }
                   $select1->where('(friends.friendId is null')
                    
                   ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                    
                    
            
            // incoming
            

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('editFriendTrusteeDetails', "editFriendTrusteeDetails.otherUserId = friends.userId AND editFriendTrusteeDetails.userId=$userId",array('name', 'hideProfile'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.friendId =?',$userId)
                    ->where('friends.status =?','1');
//                if($searchText){
//                     $select2->where("userNickName LIKE '%$searchText%'");
//                    }
                    if($searchText!=null &&  $alphabate==null){
                     $select2->where("userNickName LIKE '%$searchText%'");
                    }
                    if($searchText!=null &&  $alphabate!=null){
                     $select2->where("userNickName LIKE '$searchText%'");
                    }
                    $select2->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                   

            if($last_request_time){
                $select1 = $select1->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time);
                                    
                $select2 = $select2->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time); 
                                    
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2));
                        ($order)?$select->order('lastMessageDate DESC'):$select->order('userFullName ASC');
                        return $select;
                        if($result){
                            $friendRowset = $friendTable->fetchAll($select);
                            return $friendRowset;
                        }else{
                            return $select;
                        }
        
    }
    /*used for getting freinds list already chat*/
    public function getChatUser($userId){
        $friendTable = new Application_Model_DbTable_Friends();
        $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.userId =?',$userId)
                    ->where('friends.status =?','1')
                    ->where('friends.message !=?','');
                
                   $select1->where('(friends.friendId is null')
                    
                   ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                    
                    
            
            // incoming
            

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.friendId =?',$userId)
                    ->where('friends.status =?','1')
                    ->where('friends.message !=?','');
                    $select2->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                   

            if($last_request_time){
                $select1 = $select1->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time);
                                    
                $select2 = $select2->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time); 
                                    
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2));
                        $select->order('userFullName ASC');
                        return $select;
                        
        
    }
    
    
    public function getMyFriendList($userId,$last_request_time = false,$returnRowset = false){
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();
        
        // outgoing
         
            $select1 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = friends.friendId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.userId =?',$userId)
                    ->where('(friends.friendId is null')
                    ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                    
            
            // incoming
            

            $select2 = $friendTable->select()->setIntegrityCheck(false)
                    ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
                    ->joinInner('users', 'users.userId = friends.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName', 'userEmail','userCountryCode','userImage','userPhone','phoneWithCode','userFbId','userTwitterId','isOnline','lastSeenTime','userModifieddate','quickBloxId','profileStatus','user_location'))
                    ->joinLeft('usrSetting', 'usrSetting.userId = friends.userId',array('isOnline'))
                    ->where('friends.friendId =?',$userId)
                    ->Where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('users.userId !=?',$userId)
                    ->group("users.userId");
                   

            if($last_request_time){
                $select1 = $select1->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time);
                                    
                $select2 = $select2->where('(friends.modifyDate >?', $last_request_time)
                                   ->orWhere('users.userModifieddate >?)',$last_request_time); 
                                    
            }
            
            $select = $friendTable->select()
                        ->union(array($select1, $select2))
                        ->order('userFullName');
        $friendRowset = $friendTable->fetchAll($select);
        
        if($returnRowset){
            return $friendRowset;
        }
        
        $friendData = array();
        
        foreach($friendRowset as $friendRow){

            if(($last_request_time == "") || (strtotime($friendRow->modifyDate) > $last_request_time) || (strtotime($friendRow->userModifieddate) > $last_request_time)){
                $hide_profile = "0";
                $friendName = "";
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

                $trusteeRow = false;
                $blockUserRow = false;
                
                if($friendRow->user_id){
                    $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$friendRow->user_id);
                    $blockUserRow = $blockUserTable->getBlockRelation($friendRow->user_id,$userId);
                }
                
                $friendData[] = array(
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
                    'isOnline'          => ($friendRow->user_id)?$userSettingTable->isUserOnline($friendRow->user_id):"0",
                    'lastSeenTime'      => ($friendRow->lastSeenTime)?$friendRow->lastSeenTime:($friendRow->acceptDate?$friendRow->acceptDate:$friendRow->creationDate), 
                    'friendImage'       => ($friendRow->userImage)?($friendRow->userImage):"",
                    'creationDate'      => $friendRow->creationDate,
                    'quickBloxId'       => ($friendRow->quickBloxId)?$friendRow->quickBloxId:"",
                    'profileStatus'     => ($friendRow->profileStatus)?$friendRow->profileStatus:"",
                    'user_location'     => ($friendRow->user_location)?$friendRow->user_location:"",
                    'block_by_user'     => ($blockUserRow)?$blockUserRow->status:"0"
                );
                
            }  
        }
        return $friendData;
    }
    
    
    /**
     * get list of pending friends ( of status 0) 
     */
    
    public function getPendingFriends($userId,$returnSelect = false,$searchText=null,$alphabate=null){
        $friendTable = new Application_Model_DbTable_Friends();
        
        // outgoing friend request  
        $select1 = $friendTable->select()->setIntegrityCheck(false)
               ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('1')))
               ->joinLeft('users', 'users.userId = friends.friendId',array('user_id' => 'userId','userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','isOnline','lastSeenTime','quickBloxId'))
               ->where('friends.userId =?',$userId);
                if($searchText!=null &&  $alphabate==null){
                     $select1->where("userNickName LIKE '%$searchText%'");
                    }
                    if($searchText!=null &&  $alphabate!=null){
                     $select1->where("userNickName LIKE '$searchText%'");
                    }
               $select1->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
               ->where('(friends.friendId is null')
               ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE);

       // incoming friend request 

       $select2 = $friendTable->select()->setIntegrityCheck(false)
               ->from('friends',array('*','is_send_request' => new Zend_Db_Expr('0')))
               ->joinLeft('users', 'users.userId = friends.userId',array('user_id' => 'userId','userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','isOnline','lastSeenTime','quickBloxId'))
               ->where('friends.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE);
               if($searchText!=null &&  $alphabate==null){
                     $select2->where("userNickName LIKE '%$searchText%'");
                    }
                    if($searchText!=null &&  $alphabate!=null){
                     $select2->where("userNickName LIKE '$searchText%'");
                    }
               $select2->where('users.userStatus=?',Application_Model_DbTable_Users::STATUS_ACTIVE)
               ->where('friends.friendId =?',$userId);

       $select = $friendTable->select()
                   ->union(array($select1, $select2))
                   ->order('userFullName');
       
       if($returnSelect){
           return $select;  
       }
       
       $friendRowset = $friendTable->fetchAll($select);
       
       return $friendRowset;
    }
    
    
    /**
     *  remove friend relationship at the time of block user 
     */
    
    public function removeFriendRelationShip($userId,$otherUserId){
        $friendTable = new Application_Model_DbTable_Friends();
        
        if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $otherUserId)){
           // $friendRow->status = "2";
            $friendRow->modifyDate = date("Y-m-d H:i:s");
            $friendRow->save();
        }
    }
    
    public function checkFriendAndTrusteeRelation($userId,$friendId){
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId);  // wa user is friend or not
        $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $friendId);
        
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
         
         $response = array(
             'isFriend'     => $isFriend,
             'isTrustee'    => $isTrustee
         );
         
         return $response;
         
    }
            
    public function getRowByIdAndCode($id,$confirmCode){
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $friendTable->select()
                        ->where('id =?',$id)
                        ->where('confirmCode =?',$confirmCode);
        
        return $friendTable->fetchRow($select);
    }
    public function updateLastChat($recepient_id,$sender_id,$arrData){
        $userTable = new Application_Model_DbTable_Users();
        $userRow1 = $userTable->getByQuickBlox($recepient_id);
        $userRow2 = $userTable->getByQuickBlox($sender_id);
        $this->update($arrData, "userId='{$userRow1->userId}' AND friendId='{$userRow2->userId}'");
        $this->update($arrData, "userId='{$userRow2->userId}' AND friendId='{$userRow1->userId}'");
        
          
    }
    
    
    
}
?>
