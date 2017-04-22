<?php
class Application_Model_DbTable_TrusteeRow extends Zend_Db_Table_Row_Abstract{
    
    public function getName(){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        if($this->trusteeId && ($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($this->userId,$this->trusteeId))){
            $trusteeName = $editFriendTrusteeRow->name;
        }else{
            
            $trusteeName =($this->trusteeId)?$this->userNickName:$this->trusteeName;  
        }
        
        return $trusteeName;
    }
    
}

class Application_Model_DbTable_Trustee extends Zend_Db_Table_Abstract{
    
    protected $_name = 'trustees';
    protected $_id = 'id';
    protected $_rowClass = 'Application_Model_DbTable_TrusteeRow';
    
    const STATUS_INACTIVE = '0';   // pending friend request
    const STATUS_ACTIVE = '1';
    const STATUS_REJECT = '2';
    const STATUS_BLOCK = '3';
    
    public function getRowById($rowId){
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $TrusteeTable->select()
                        ->where('id =?',$rowId);
        
        return $TrusteeTable->fetchRow($select);
    }
    
    public function getTrusteesByUserId($userId){
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $TrusteeTable->select()
                        ->where('userId =?',$userId);
        
        return $TrusteeTable->fetchAll($select);
    }
    
    public function getRowByIdAndCode($id,$confirmCode){
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $TrusteeTable->select()
                        ->where('id =?',$id)
                        ->where('confirmCode =?',$confirmCode);
        
        return $TrusteeTable->fetchRow($select);
    }
    
    
    public function getRowByUserIdAndTrusteeId($userId,$trusteeId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                        ->where('userId =?',$userId)
                        ->where('trusteeId =?',$trusteeId);
                   
        $trusteeRow = $trusteeTable->fetchRow($select);
        
        return $trusteeRow;
    }
    
    
    
    public function getRowByUserIdAndEmail($userId,$email){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                        ->where('userId =?',$userId)
                        ->where('trusteeEmail =?',$email);
                   
        $trusteeRow = $trusteeTable->fetchRow($select);
        
        return $trusteeRow;
    }
    
    public function getRowByUserIdAndPhone($userId,$trusteePhone){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                        ->where('userId =?',$userId)
                        ->where('trusteePhone =?',$trusteePhone);
                   
        $trusteeRow = $trusteeTable->fetchRow($select);
        
        return $trusteeRow;
    }
    
    
    public function getRowByUserIdAndFacebookId($userId,$facebookId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                        ->where('userId =?',$userId)
                        ->where('facebookId =?',$facebookId);
    
        $trusteeRow = $trusteeTable->fetchRow($select);
        return $trusteeRow;
    }
    
    /**
     *  update new user id in incoming request
     */
    
    public function updateNewUserId($userId){
        $userTable = new Application_Model_DbTable_Users();
        
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        if($userRow = $userTable->getRowById($userId)){
                /**
             *  get all trustee with new user phone number and update his    user id
             */

            $phoneSelect = $trusteeTable->select()
                            ->where('trusteePhone =?',$userRow->phoneWithCode)
                            ->where('trusteeId is ?',new Zend_Db_Expr('null'));

            $trusteeRowset = $trusteeTable->fetchAll($phoneSelect);

            foreach($trusteeRowset as $trusteeRow){
                $trusteeRow->trusteeId = $userId;
                $trusteeRow->save();
            }

            /**
             *  get all trustee with new user email and update his user id
             */

            $emailSelect = $trusteeTable->select()
                            ->where('trusteeEmail =?',$userRow->userEmail)
                            ->where('trusteeId is ?',new Zend_Db_Expr('null'));

            $trusteeRowset = $trusteeTable->fetchAll($emailSelect);

            foreach($trusteeRowset as $trusteeRow){
                $trusteeRow->trusteeId = $userId;
                $trusteeRow->save();
            }
        }
    }

    public function breakTrusteeRelation($userId,$otherUserId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId,$otherUserId)){
            $trusteeRow->delete();
        }
        
        if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($otherUserId,$userId)){
            $trusteeRow->delete();
        }
    }
    
    public function deleteUserTrustee($userId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $trusteeTable->delete("userId = ".$userId);
    }
    
    public function getIncomingTrusteesRequest($userId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                            ->where('trusteeId =?',$userId);
                              
        return $trusteeTable->fetchAll($select);
        
    }
    
    public function getMyTrustees($userId,$returnSelect = false){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        $select = $trusteeTable->select()
                        ->setIntegrityCheck(false)
                        ->from('trustees',array('*'))
                        ->joinLeft('users', 'users.userId = trustees.trusteeId',array('userStatus','userNickName','userFullName','userEmail','userCountryCode','phoneWithCode','userPhone','userImage','isOnline','lastSeenTime','quickBloxId'))
                        ->where('trustees.userId =?',$userId)
                        ->where('trustees.status =?',Application_Model_DbTable_Trustee::STATUS_ACTIVE)
                        ->where('trustees.trusteeId is not null')
                        ->where('users.userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE);
        
        if($returnSelect){
            return $select;
        }
        
        $trusteeRowset = $trusteeTable->fetchAll($select);
        return $trusteeRowset;
    }
    
    public function countMyTrustees($userId){
       $trusteeTable = new Application_Model_DbTable_Trustee();
        
       $select = $trusteeTable->select()
                        ->setIntegrityCheck(false)
                        ->from('trustees',array('*'))
                        ->joinLeft('users', 'users.userId = trustees.trusteeId',array('userStatus','userNickname','userFullName','userEmail','userCountryCode','phoneWithCode','userPhone','userPhone','userImage','isOnline','lastSeenTime','quickBloxId'))
                        ->where('trustees.userId =?',$userId)
                        ->where('trustees.status =?', Application_Model_DbTable_Trustee::STATUS_ACTIVE)
                        ->where('trustees.trusteeId is not null')
                        ->where('users.userStatus =?', Application_Model_DbTable_Trustee::STATUS_ACTIVE);
       
        return $trusteeTable->fetchAll($select)->toArray();
    }


    public function getIncomingOutgoingRequest($userId,$returnSelect = false,$search=false){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        // outgoing trustee request
        
        $select1 = $trusteeTable->select()
                                ->setIntegrityCheck(false)
                                ->from('trustees',array('*','is_send_request' => new Zend_Db_Expr('1')))
                                ->joinLeft('users', 'users.userId = trustees.trusteeId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','userFbId','userTwitterId','isOnline','lastSeenTime','quickBloxId'))
                                ->where('trustees.userId =?',$userId)
                                ->where('trustees.status =?',Application_Model_DbTable_Trustee::STATUS_INACTIVE)
                                ->where('(trustees.trusteeId is null')
                                ->orWhere('users.userStatus =?)',  Application_Model_DbTable_Users::STATUS_ACTIVE);
        
                
        // incoming trustee request 

         $select2 = $trusteeTable->select()->setIntegrityCheck(false)
                ->from('trustees',array('*','is_send_request' => new Zend_Db_Expr('0')))
                ->joinLeft('users', 'users.userId = trustees.userId',array('user_id' => 'userId', 'userStatus','userNickName','userFullName','userEmail','userCountryCode','userPhone','phoneWithCode','userImage','userFbId','userTwitterId','isOnline','lastSeenTime','quickBloxId'))
                ->where('trustees.status =?',Application_Model_DbTable_Friends::STATUS_INACTIVE)
                ->where('users.userStatus=?',Application_Model_DbTable_Users::STATUS_ACTIVE)
                ->where('trustees.trusteeId =?',$userId);
         
        if($search){
            $select1->where("(users.userNickName like ?","%$search%")
                   ->orWhere("users.userFullName like ?)","%$search%"); 
            
            $select2->where("(users.userNickName like ?","%$search%")
                   ->orWhere("users.userFullName like ?)","%$search%"); 
        }

        $select = $trusteeTable->select()
                    ->union(array($select1, $select2))
                    ->order('userFullName');
        
        if($returnSelect){
            return $select;
        }
        return $trusteeTable->fetchAll($select); 
                
    }
    
    /**
     *  list of the users whome i am trustees
     */
    
    public function whomIamTrustee($userId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $select = $trusteeTable->select()->setIntegrityCheck(false)
                                        ->from("trustees",array("status","id"))
                                        ->joinInner("users","users.userId=trustees.userId",array("*"))
                                        ->where("trustees.status =?","1")
                                        ->where("users.userStatus =?","1")
                                        ->where("trustees.trusteeId =?",$userId); 
        
        return $trusteeTable->fetchAll($select);
    }
    
    /**
     *  remove trustee relationship at the time of block user 
     */
    
    public function removeTrusteeRelationShip($userId,$otherUserId){
        $trusteeTable = new Application_Model_DbTable_Trustee();
        
        if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $otherUserId)){
            $trusteeRow->status = "2";
            $trusteeRow->modifyDate = date("Y-m-d H:i:s");
            $trusteeRow->save();
        }
        
        if($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($otherUserId, $userId)){
            $trusteeRow->status = "2";
            $trusteeRow->modifyDate = date("Y-m-d H:i:s");
            $trusteeRow->save();
        }
    }
}
?>
