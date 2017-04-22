<?php
class Application_Model_DbTable_WaTrusteeRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaTrustee extends Zend_Db_Table_Abstract{
    protected $_name = "wa_trustees";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaTrusteeRow";
    
    public function getRowById($id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()
                            ->where('id =?',$id);
        
        return $waTrusteeTable->fetchRow($select);
    }
    
    public function getWaReceivers($wa_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(false)
                            ->from('wa_trustees',array("*"))
                            ->joinLeft("users", "users.userId = wa_trustees.receiver_id",array("userId","userNickName","userImage","quickBloxId"))
                            ->where("wa_trustees.wa_id =?",$wa_id)
                            ->where("users.userStatus =?",Application_Model_DbTable_Users::STATUS_ACTIVE);
        
        return $waTrusteeTable->fetchAll($select);
    }
    
    public function getWaTrustees($user_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(false)
                            ->from('wa_trustees',array("*"))
                            ->joinLeft("users", "users.userId = wa_trustees.receiver_id",array("userId","userNickName","userImage","quickBloxId"))
                            ->where("wa_trustees.user_id =?",$user_id)
                            ->where("users.userStatus =?",Application_Model_DbTable_Users::STATUS_ACTIVE);
        
        return $waTrusteeTable->fetchAll($select);
    }
      
    public function getTrusteesByUserId($user_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(FALSE)
                    ->from('wa_trustees', array('*'))
                    ->joinLeft("users","users.userId = wa_trustees.user_id", array("userId","userNickName","userImage"))
                    ->where("wa_trustees.receiver_id =?", $user_id)
                    ->where("wa_trustees.testament_id !='0'")
                    ->where("users.userStatus =?", Application_Model_DbTable_Users::STATUS_ACTIVE);

        return $waTrusteeTable->fetchAll($select);
    }
    
    public function getWATrusteesByUserId($user_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(FALSE)
                    ->from('wa_trustees', array('*'))
                    ->joinLeft("users","users.userId = wa_trustees.user_id", array("userId","userNickName","userImage"))
                    ->where("wa_trustees.receiver_id =?", $user_id)
                    ->where("wa_trustees.wa_id !='0'")
                    ->where("users.userStatus =?", Application_Model_DbTable_Users::STATUS_ACTIVE);

        return $waTrusteeTable->fetchAll($select);
    }
    
    public function getWAAfterTrusteesByUserId($user_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(FALSE)
                    ->from('wa_trustees', array('*'))
                    ->joinLeft("users","users.userId = wa_trustees.user_id", array("userId","userNickName","userImage"))
                    ->joinInner("wa","wa.user_id = wa_trustees.user_id", array("type"))
                    ->where("wa_trustees.receiver_id =?", $user_id)
                    ->where("wa_trustees.wa_id !='0'")
                    ->where('wa.type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
                    ->where("users.userStatus =?", Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->group("users.userId");
        
        return $waTrusteeTable->fetchAll($select);
    }
    
    public function getTrusteesByTestamentId($user_id,$testament_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(FALSE)
                    ->from('wa_trustees', array('*'))
                    ->joinLeft("users","users.userId = wa_trustees.receiver_id", array("userId","userNickName","userImage"))
                    ->where("wa_trustees.user_id =?", $user_id)
                    ->where("wa_trustees.testament_id =?", $testament_id)
                    ->where("users.userStatus =?", Application_Model_DbTable_Users::STATUS_ACTIVE);
        return $waTrusteeTable->fetchAll($select);
    }
    
    public function getTrusteesByWAId($user_id,$wa_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()->setIntegrityCheck(FALSE)
                    ->from('wa_trustees', array('*'))
                    ->joinLeft("users","users.userId = wa_trustees.receiver_id", array("userId","userNickName","userImage"))
                    ->where("wa_trustees.user_id =?", $user_id)
                    ->where("wa_trustees.wa_id =?", $wa_id)
                    ->where("users.userStatus =?", Application_Model_DbTable_Users::STATUS_ACTIVE);
        return $waTrusteeTable->fetchAll($select);
    }

    public function deleteReceivers($user_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $waTrusteeTable->delete(array("user_id = ?"=>$user_id,"testament_id =?"=> ''));
    }
    
    public function deleteTestamentReceivers($user_id,$testament_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
         
         $waTrusteeTable->delete(array("user_id = ?"=>$user_id,"testament_id !=''"));
    }
    
    public function deleteWaReceivers($user_id,$wa_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $waTrusteeTable->delete(array("user_id = ?"=>$user_id,"wa_id = ?"=>$wa_id,"testament_id =?"=> ''));
    }
    
    public function getRowByUserIdAndReceiverId($user_id,$receiver_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()
                                  ->where("user_id =?",$user_id)
                                  ->where("receiver_id =?",$receiver_id);
        
        return $waTrusteeTable->fetchRow($select);
    }
    
    public function getRowByReceiverIdAndUserId($user_id,$receiver_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()
                                  ->where("user_id =?",$user_id)
                                  ->where("receiver_id =?",$receiver_id)
                                  ->where("wa_id =?",'0');
     
        return $waTrusteeTable->fetchRow($select);
    }
    
    public function getAllWARowByReceiverIdAndUserId($user_id,$receiver_id){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $select = $waTrusteeTable->select()
                                  ->where("user_id =?",$user_id)
                                  ->where("receiver_id =?",$receiver_id)
                                  ->where("wa_id !='0'");
     
        return $waTrusteeTable->fetchAll($select);
    }
    
    public function addWaTrustee($user_id,$receiver_trusteeset){
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        
        $this->deleteReceivers($user_id);
        
        foreach ($receiver_trusteeset as $receiverId){
            $data = array(
                'user_id'       => $user_id,
                'receiver_id'   => $receiverId
            );

            if($receiverId != $user_id){
                $waTrusteeRow = $waTrusteeTable->createRow($data);
                $waTrusteeRow->save();
            }
        }
        
    }
    
    public function addEventWaTrustee($user_id,$receiver_trusteeset,$event_id)
    {
        $WaTrusteeResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        foreach($receiver_trusteeset as $receiverId)
        {
            $data = array(
                        'user_id'   => $user_id,
                        'object_id' => $event_id,
                        'trustee_id' => $receiver_trusteeset,
                        'response'  => 'not_send'
                    );
            
            if($receiverId != $user_id){
                $waTrusteeRow = $waTrusteeResponseTable->createRow($data);
                $waTrusteeRow->save();
            }
            
        }
    }
    
}

?>
