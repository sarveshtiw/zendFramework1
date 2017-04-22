<?php

class Application_Model_DbTable_UsersRow extends Zend_Db_Table_Row_Abstract{
    
   public function getName($loggedUserId){
        
       $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($loggedUserId,$this->userId)){
            $friendName = $editFriendTrusteeRow->name;
        }else{
            $friendName = $this->userNickName;
        }
        
        return $friendName;
    }
    
    public function getCountryName(){
        if($countryRow = Application_Model_DbTable_Countries::getRowByCountryCode(trim($this->userCountryCode))){
           return $countryRow->country_english_name; 
        }else{
            return false;
        }
    }
    
    public function isActive(){
        return ($this->userStatus == Application_Model_DbTable_Users::STATUS_ACTIVE)? true:false;
    }
}

class Application_Model_DbTable_Users extends Zend_Db_Table_Abstract
{
    protected $_name = 'users';
    protected $_primary = 'userId';
    protected $_rowClass = 'Application_Model_DbTable_UsersRow';
    
    const STATUS_INACTIVE = '0';
    const STATUS_ACTIVE = '1';
    const STATUS_DELETED = '2';
    const STATUS_CANCELLED = '3';
    const STATUS_BLOCK = '4';
    protected $membershipCredits;

    public function __construct($config = array()) {
        $this->membershipCredits = array("3"=>1000);
        parent::__construct($config);
    }
    public function checkDuplicateRecordByField($fieldName, $value,$userId = null){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select();
        
        if($fieldName == "userPhone"){
           $select = $select->where("(userPhone =?",$value)
                            ->orWhere("phoneWithCode =?)",$value);
           
        }elseif($fieldName == "userName"){
            $select = $select->where("LCASE(userName) =?",strtolower($value));
        }else{
            $select = $select->where($fieldName." =?",$value);
        }
        
        if($userId){
            $select = $select->where('userId !=?', $userId);
        }
                       
        return $userTable->fetchRow($select);
    }
    
    public function getRowById($userId){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                        ->where('userId =?',$userId);
        
        $userRow = $userTable->fetchRow($select);
        return $userRow;
    }
    public function getByQuickBlox($quickBloxID){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                        ->where('quickBloxId =?',$quickBloxID);
        
        $userRow = $userTable->fetchRow($select);
        return $userRow;
    }
    public function getRowByQuickBloxId($quickBloxArrID){
        $select = $this->select()
                        ->where('quickBloxId IN(?)',$quickBloxArrID);
        $userArr= $this->fetchAll($select)->toArray();
        return $userArr;
    }
    
     public function getAllRecords($userId = null){
        $userTable = new Application_Model_DbTable_Users();
        $select  = $userTable->select();//
                        //->where('userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE);
        if($userId){
            $select->where("userId!=?",$userId);
            $select->limit(15,0);
        }
        //echo $select;die();
        
        return $userTable->fetchAll($select);
    }
    
    public function updateUserDeviceDetails($userId,$deviceDetails){
        $userTable = new Application_Model_DbTable_Users();
        
        $userRow = $userTable->getRowById($userId);
        $userRow->setFromArray($deviceDetails);
        
        $userRow->save();
    }
    
    public function getRowByPhone($phone){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                            ->where("(userPhone =?",$phone)
                            ->orWhere("phoneWithCode =?)",$phone);
        
        $userRow = $userTable->fetchRow($select);
        return $userRow;
    }
    
      public function getRowByPhoneAccountSettig($phone){
        $userTable = new Application_Model_DbTable_Users();
        $select = $userTable->select()->setIntegrityCheck(false)->from('users','*')
                            ->joinInner('accountSetting', 'users.userId = accountSetting.userId',array("auto_friends","extra_privacy"))
                            ->where("(userPhone =?",$phone)
                            ->orWhere("phoneWithCode =?)",$phone);
       
        $userRow = $userTable->fetchRow($select);
        return $userRow;
    }
    
    public function phoneOwnerRow($phone){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                            ->where("(userPhone =?",$phone)
                            ->orWhere("phoneWithCode =?)",$phone)
                            ->where("is_phone_owner =?","1");
        
        $userRow = $userTable->fetchRow($select);
        return $userRow;
    }
    
    public function getUsersByPhone($phone){
        $userTable = new Application_Model_DbTable_Users();
        
        $select = $userTable->select()
                            ->where("(userPhone =?",$phone)
                            ->orWhere("phoneWithCode =?)",$phone)
                            ->where("userStatus !=?","2");
                            
        return $userTable->fetchAll($select);
    }

    //// function created by Sarvesh Tiwari 1/4/2015 /////
    
    public function getRowByEmail($userEmail){
        $userTable = new Application_Model_DbTable_Users();
        
        $select    = $userTable->select()
                    ->where('userEmail =?', $userEmail);       
        $userRow   = $userTable->fetchRow($select);
        return $userRow;
    }

    //used for update user membership
    public function updateMembership($membership,$user_id=0){
        
        $userRow = $this->getRowById($user_id);
        $userRow->setFromArray(array("subscriptionPlan"=>$membership));
        $userRow->save();

    }
    
     //used for checking user credits its total of both 
    //totalCredits and totalPoints
    public function checkCredits($user_id=0,$credits=100){
        $select = $this->select()->from($this->info('name'),'(totalPoints + totalCredits) AS totalCredits');
        $select->where("userId=?",$user_id);
        $result = $this->fetchRow($select);
        $totalCredits = $result->totalCredits;
        if($totalCredits>=$credits){
            return true;
        }
        else{
            return false;
        }
    }
    
    public function getUserCredits($userId){
        $select = $this->select()->from($this->info('name'),"totalCredits")
                    ->where("userId=?",$userId);
        $result = $this->fetchRow($select);
        if($result)
            return $result->totalCredits;
        else
            return 0;
    }
    /*
     * created date :   21-4-2015
     * Author       :   Akhilesh Singh
     * description  :   used for update last request time
     * param        :   column name,user_id
     */
    
    public function updateRequestTime($column,$user_id){
       $this->update(array("$column" => date("Y-m-d H:i:s")), "userId =" . $user_id);
    }
    
    public function getUserList($language=null){
         $select = $this->select()->from('users_backup');
         if($language){
             $select->group('userCountryCode');
         }
         $select->order('userNickName ASC');
         $result = $this->fetchAll($select);
         return $result;
         
    }
    
    public function upDateSendStatus($email){
        $db = Zend_Db_Table::getDefaultAdapter();
        $query ="update users_backup is_send=1 where userEmail='{$email}'";
        $db->query($db);
         
    }
    
    public function getCountryNameByCode($code){
        $code= str_replace(' ', '', $code);
        if($countryRow = Application_Model_DbTable_Countries::getRowByCountryCode(trim($code))){
           return $countryRow->country_english_name; 
        }else{
            return false;
        }
    }

}
