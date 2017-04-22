<?php

class Application_Model_DbTable_WaSosEventSendDetailsRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosEventSendDetails extends Zend_Db_Table_Abstract{
    protected $_name       = "wa_sos_event_send_details"; 
    protected $_primary    = "sos_event_id";   
    protected $_row        = "Application_Model_DbTable_WaSosEventSendDetailsRow";
    
    public function getRowById($id){
        $select = $this->select()
                    ->where("sos_event_id =?", $id);
        return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $select = $this->select()
                    ->where("user_id =?", $userId);
        return $this->fetchAll($select)->toArray();
    }
    
    public function getAllRowByUserId($userId){
        $select = $this->select()
                    ->where("user_id =?", $userId);
        return $this->fetchAll($select);
    }
    
    public function getRecordsForPushNoitifications(){
        $currentDate = date('Y-m-d H:i:s');
        $select = $this->select()->setIntegrityCheck(false)
                    ->from("wa_sos_event_send_details", array("*"))
                    ->joinInner("wa_sos","wa_sos.sos_id = wa_sos_event_send_details.sos_id",array("*"))
                    ->joinInner("users","users.userId = wa_sos_event_send_details.user_id",array("*"))
                    ->where("wa_sos_event_send_details.event_alert_count <?", 3)
                    ->where("wa_sos_event_send_details.event_send_date <?", $currentDate)
                    ->order("sos_event_id ASC");
        
        return $this->fetchAll($select);
    }
    
    public function getRowBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId);
        return $this->fetchRow($select);
    }
}

