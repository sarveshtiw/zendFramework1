<?php

class Application_Model_DbTable_WaSosEventResponseRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosEventResponse extends Zend_Db_Table_Abstract{
    protected $_name = "wa_sos_event_response";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaSosEventResponseRow";
    
    public function getRowById($id){
        $select = $this->select()
                    ->where('id =?', $id);
        return $this->fetchRow($select);
    }
    
    public function getResponseBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId);
        return $this->fetchAll($select)->toArray();
    }
    
    public function getComingHelpUsersByUserId($userId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_event_response', array('*')) 
                    ->joinInner('wa_sos_setting', 'wa_sos_setting.user_id = wa_sos_event_response.user_id',array('*'))                
                    ->joinInner('users', 'users.userId = wa_sos_setting.user_id',array('*'))
                    ->where('wa_sos_event_response.user_id =?', $userId)
                    ->where('wa_sos_event_response.response =?', '1');
       
        return $this->fetchAll($select)->toArray();
    }
}

