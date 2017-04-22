<?php
class Application_Model_DbTable_WaReceiverRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaReceiver extends Zend_Db_Table_Abstract{
    protected $_name = "wa_receivers";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaReceiverRow";
    
    public function getRowById($id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $select = $waReceiverTable->select()
                            ->where('id =?',$id);
        
        return $waReceiverTable->fetchRow($select);
    }
    
    public function getRowByReceiverId($receiver_id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $select = $waReceiverTable->select()
                            ->where('receiver_id =?',$receiver_id);
        
        return $waReceiverTable->fetchRow($select);
    }

    public function getWaReceivers($wa_id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $select = $waReceiverTable->select()->setIntegrityCheck(false)
                            ->from('wa_receivers',array("*"))
                            ->joinLeft("users", "users.userId = wa_receivers.receiver_id",array("userId","userNickName","userImage","quickBloxId"))
                            ->where("wa_receivers.wa_id =?",$wa_id);
        
        return $waReceiverTable->fetchAll($select);
    }
    
    public function getWaReceiversByWAId($wa_id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $select = $waReceiverTable->select()->setIntegrityCheck(false)
                            ->from('wa_receivers',array("receiver_email","wa_id"))
                            ->where("wa_receivers.wa_id =?",$wa_id)
                            ->where("wa_receivers.receiver_email !=''");
        
        return $waReceiverTable->fetchAll($select);
    }

    public function deleteReceivers($wa_id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $waReceiverTable->delete(array("wa_id = ?"=>$wa_id));
        
    }
    
    public function getRowByWaIdAndReceiverId($wa_id,$receiver_id){
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $select = $waReceiverTable->select()
                                  ->where("wa_id =?",$wa_id)
                                  ->where("receiver_id =?",$receiver_id);
        
        return $waReceiverTable->fetchRow($select);
    }
    
    public function saveWaReceiver($data)
    {
       $insert_id       = $this->insert($data);
       return $insert_id;
    }
    
    public function getRowByTestamentIdAndReceiverId($testament_id,$receiver_id)
    {
        $select        = $this->select()
                        ->where("testament_id =?",$testament_id)
                        ->where('receiver_id =?',$receiver_id);
        return $this->fetchRow($select);
    }
    
    public function getTestamentRecievers($user_id)
    {
        $select  = $this->select()->setIntegrityCheck(false)
                    ->from('wa_receivers', array("*"))
                    ->joinLeft("wa_testaments", "wa_testaments.testament_id = wa_receivers.testament_id",array("*"))
                    ->where("wa_receivers.receiver_id =?", $user_id)
                    ->where("wa_testaments.is_send =?", '3');//3
        return $this->fetchAll($select)->toArray();    
    } 

    public function getRecieversByTestamentId($testament_id)
    {
        $select  = $this->select()->setIntegrityCheck(false)
                    ->from('wa_receivers', array("*"))
                    ->joinLeft("wa_testaments", "wa_testaments.testament_id = wa_receivers.testament_id",array("*"))
                    ->where("wa_receivers.testament_id =?", $testament_id);
        return $this->fetchAll($select)->toArray();    
    } 

    public function deleteTestamentRecievers($testament_id)
    {
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        
        $waReceiverTable->delete(array("testament_id = ?"=>$testament_id));
    }
}

