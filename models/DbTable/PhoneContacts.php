<?php
class Application_Model_DbTable_PhoneContacts extends Zend_Db_Table_Abstract{

    protected $_name = 'phoneContact';
    protected $_id = 'id';
    
    public function getRowById($id){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $select = $phoneContactTable->select()
                       ->where('id =?',$id);
        
        return $phoneContactTable->fetchRow($select);
    }
    
    public function getRowByUserIdAndPhoneAndDeviceId($user_id,$userDeviceId,$phone){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        
        $select = $phoneContactTable->select()
                       ->where('user_id =?',$user_id)
                       ->where('device_id =?',$userDeviceId)
                       ->where('phone_number =?',$phone);
        return $phoneContactTable->fetchRow($select);
        
    }
    
    public function getRowByUserIdAndPhoneBookIdAndDeviceId($user_id,$userDeviceId,$phone_book_id){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        
        $select = $phoneContactTable->select()
                       ->where('user_id =?',$user_id)
                       ->where('device_id =?',$userDeviceId)
                       ->where('phone_book_id =?',$phone_book_id);
        return $phoneContactTable->fetchRow($select);
    }
    
    /**
     *  get records using phone number 
     */
    
    public function getRecordsByPhoneNumber($phone,$phoneWithCode){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $select = $phoneContactTable->select()
                        ->where('(phone_number =?',$phone)
                        ->orWhere('phone_number =?)',$phoneWithCode);
        
        $phoneRowset = $phoneContactTable->fetchAll($select); 
        return $phoneRowset;
    }
    
    public function addPhoneContact($user_id,$userDeviceId,$phone,$phone_book_id){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $phoneContactRow = $phoneContactTable->getRowByUserIdAndPhoneAndDeviceId($user_id,$userDeviceId, $phone);
        
        if(!$phoneContactRow){
            $data = array(
                'user_id'       => $user_id,
                'device_id'     => $userDeviceId,
                'phone_book_id' => $phone_book_id,
                'phone_number'  => $phone,
                'creation_date' => date("Y-m-d H:i:s"),
                'modified_date' => date("Y-m-d H:i:s")
            );
            
            $phoneContactRow = $phoneContactTable->createRow($data);
            $phoneContactRow->save();
            
        }
        
        if(!$phoneContactRow->wa_user_id && ($userRow = $userTable->getRowByPhone($phone)) && ($userRow->isActive())){
             $phoneContactRow->wa_user_id = $userRow->userId;
             $phoneContactRow->modified_date = date("Y-m-d H:i:s");
             $phoneContactRow->save();
        }
        
        if($phoneContactRow->wa_user_id){
            if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $phoneContactRow->wa_user_id)){
                if($friendRow->status != Application_Model_DbTable_Friends::STATUS_ACTIVE){
                    $friendRow->status = "1";
                    $friendRow->modifyDate = date("Y-m-d H:i:s");
                    $friendRow->using_phonebook = "1";
                    $friendRow->save();
                }
            }else{
                $data = array(
                    'userId'            => $user_id,
                    'friendId'          => $phoneContactRow->wa_user_id,
                    'using_phonebook'   => "1",
                    'status'            => "1",
                    'modifyDate'        => date("Y-m-d H:i:s"),
                    'creationDate'      => date("Y-m-d H:i:s"),
                    'acceptDate'        => date("Y-m-d H:i:s")
                );
                
                $friendRow = $friendTable->createRow($data);
                $friendRow->save();
            }
        }
    }
    
    /**
     * this function updating user account for incoming request
     */
    
    public function makeAutoFriend($userRow){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $userId = $userRow->userId;
        $userPhone = $userRow->userPhone;
        $phoneWithCode = $userRow->phoneWithCode;
        
        $select = $phoneContactTable->select()->setIntegrityCheck(false)
                                ->from('phoneContact',array("*"))
                                ->joinInner('users', 'users.userId = phoneContact.user_id',array("userStatus"))
                                ->joinInner('accountSetting', 'users.userId = accountSetting.userId',array("auto_friends","extra_privacy"))
                                ->joinLeft('friends', 'friends.id = phoneContact.friend_row_id',array('friendStatus' => 'status'))
                                ->where('(friends.status is null')
                                ->orWhere('friends.status !=?)',"1")
                                ->where('users.userStatus =?',"1")
                                ->where('phoneContact.wa_user_id =?',$userRow->userId)
                                //->where('accountSetting.auto_friends =?',"on");
                                ->where('accountSetting.extra_privacy =?',"off");

        $phoneContactRowset = $phoneContactTable->fetchAll($select);                        
        
        foreach($phoneContactRowset as $phoneContactRow){
                    $contactRow = $phoneContactTable->getRowById($phoneContactRow->id);
                    
                    if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $phoneContactRow->user_id)){
                        if($friendRow->status != Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $friendRow->status = "1";
                            $friendRow->modifyDate = date("Y-m-d H:i:s");
                            $friendRow->using_phonebook = "1";
                            $friendRow->save();
                            $contactRow->modified_date = date("Y-m-d H:i:s");
                            $contactRow->save();
                        }
                    }else{
                        $data = array(
                            'userId'            => $phoneContactRow->wa_user_id,
                            'friendId'          => $phoneContactRow->user_id,
                            'using_phonebook'   => "1",
                            'status'            => "1",
                            'modifyDate'        => date("Y-m-d H:i:s"),
                            'creationDate'      => date("Y-m-d H:i:s"),
                            'acceptDate'        => date("Y-m-d H:i:s")
                        );

                        $friendRow = $friendTable->createRow($data);
                        $friendRow->save(); 
                   }
                   
                   $contactRow->friend_row_id = $friendRow->id; 
                   $contactRow->save();
        }
    }
    
    
     /**
     * this function updating user account for incoming request
     */
    
    public function makeFriends($userRow){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $userId = $userRow->userId;
        $userPhone = $userRow->userPhone;
        $phoneWithCode = $userRow->phoneWithCode;
        
        $select = $phoneContactTable->select()->setIntegrityCheck(false)
                                ->from('phoneContact',array("*"))
                                ->joinInner('users', 'users.userId = phoneContact.user_id',array("userStatus"))
                                ->joinInner('accountSetting', 'users.userId = accountSetting.userId',array("auto_friends","extra_privacy"))
                                ->where('phoneContact.user_id =?',$userRow->userId);
        $phoneContactRowset = $phoneContactTable->fetchAll($select);                        
        
        foreach($phoneContactRowset as $phoneContactRow){
                    $contactRow = $userTable->getRowByPhoneAccountSettig($phoneContactRow->phone_number);
                    if($contactRow && $contactRow->extra_privacy=='off'){
                    if($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $contactRow->userId)){
                        if($friendRow->status != Application_Model_DbTable_Friends::STATUS_ACTIVE){
                            $friendRow->status = "1";
                            $friendRow->modifyDate = date("Y-m-d H:i:s");
                            $friendRow->using_phonebook = "1";
                            $friendRow->save();
                        }
                    }else{
                        $data = array(
                            'userId'            => $userId,
                            'friendId'          => $contactRow->userId,
                            'using_phonebook'   => "1",
                            'status'            => "1",
                            'modifyDate'        => date("Y-m-d H:i:s"),
                            'creationDate'      => date("Y-m-d H:i:s"),
                            'acceptDate'        => date("Y-m-d H:i:s")
                        );

                        $friendRow = $friendTable->createRow($data);
                        $friendRow->save(); 
                   }
                  }
                  
        }
    }
    
    public function makeFriendToContact($userRow){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $select = $phoneContactTable->select()->setIntegrityCheck(false)
                                        ->from('phoneContact',array("*"))
                                        ->joinInner('users', 'users.userId = phoneContact.wa_user_id',array('userStatus'))
                                        ->joinInner('accountSetting', 'users.userId = accountSetting.userId',array("auto_friends","extra_privacy"))
                                        ->joinLeft('friends', 'friends.id = phoneContact.friend_row_id',array('friendStatus' => 'status'))
                                        ->where('(friends.status is null')
                                        ->orWhere('friends.status !=?)',"1")
                                        ->where('users.userStatus =?',"1")
                                        ->where('phoneContact.user_id =?',$userRow->userId)
                                        ->where('accountSetting.extra_privacy =?',"off");
        
        $phoneContactRowset = $phoneContactTable->fetchAll($select);
        
        foreach($phoneContactRowset as $phoneContactRow){
                $contactRow = $phoneContactTable->getRowById($phoneContactRow->id);
                
                if($friendRow = $friendTable->getRowByUserIdAndFriendId($userRow->userId, $phoneContactRow->wa_user_id)){
                    if($friendRow->status != Application_Model_DbTable_Friends::STATUS_ACTIVE){
                        $friendRow->status = "1";
                        $friendRow->modifyDate = date("Y-m-d H:i:s");
                        $friendRow->using_phonebook = "1";
                        $friendRow->save();
                        $contactRow->modified_date = date("Y-m-d H:i:s");
                        $contactRow->save();
                    }
                }else{
                    $data = array(
                        'userId'            => $phoneContactRow->user_id,
                        'friendId'          => $phoneContactRow->wa_user_id,
                        'using_phonebook'   => "1",
                        'status'            => "1",
                        'modifyDate'        => date("Y-m-d H:i:s"),
                        'creationDate'      => date("Y-m-d H:i:s"),
                        'acceptDate'        => date("Y-m-d H:i:s")
                    );

                    $friendRow = $friendTable->createRow($data);
                    $friendRow->save(); 
               }

               $contactRow->friend_row_id = $friendRow->id; 
               $contactRow->save();
        }
    }
    
    /**
     *  this function will return my conacts which are also my friends 
     */
    
    public function getMyContactFriends($user_id,$user_device_id){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $select = $phoneContactTable->select()->setIntegrityCheck(false)
                                        ->from('phoneContact',array("*"))
                                        ->joinInner('friends', 'phoneContact.friend_row_id = friends.id',array('status'))
                                        ->joinInner('users', 'users.userId = phoneContact.wa_user_id',array('userStatus'))
                                        ->where('friends.status =?',"1")
                                        ->where('users.userStatus =?',"1")
                                        ->where('phoneContact.user_id =?',$user_id)
                                        ->where('phoneContact.device_id =?',$user_device_id);
                                        
        
        $myContactFriendsRowset = $phoneContactTable->fetchAll($select);
        
        $response = array();
        
        foreach($myContactFriendsRowset as $contactFriendRow){
            $response[] = array(
                'phone_book_id' => $contactFriendRow->phone_book_id,
                'phone'         => $contactFriendRow->phone_number,
                'server_tbl_id' => $contactFriendRow->id,
                'friend_id'     => $phoneContactRow->wa_user_id
            );
        }
        
        
        return $response;
        
       }
      
       public function updateWaUserIdWithPhone($userRow){
            $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
            
            $data = array('wa_user_id' => $userRow->userId,);
            
            $where = "phone_number ='".$userRow->userPhone."' or phone_number ='".$userRow->phoneWithCode."'";
            
            $phoneContactTable->update($data, $where);
           
      }
    
}
?>
