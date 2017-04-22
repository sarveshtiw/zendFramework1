<?php
class Application_Model_DbTable_WaSosReceiverRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosReceiver extends Zend_Db_Table_Abstract{
    protected $_name = "wa_sos_receivers";
    protected $_primary = "sos_receiver_id";
    protected $_rowClass = "Application_Model_DbTable_WaSosReceiverRow";
    
    public function getRowById($sosId){
        $select = $this->select()
                    ->where('sos_receiver_id =?', $sosId);
        
        return $this->fetchRow($select);
    }
    
    public function getRowsBySOSId($sosId){        
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinLeft('users','users.userId = wa_sos_receivers.receiver_id',array("userNickName"))
                    ->where('wa_sos_receivers.sos_id =?', $sosId)
                    ->where('wa_sos_receivers.is_deleted =?', '0');
        
        return $this->fetchAll($select);
    }
        
    public function getActiveSOSRowBySOSId($userId,$sosId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinInner('users','wa_sos_receivers.user_id = users.userId',array("*"))
                    ->joinInner('wa_sos','wa_sos.user_id = users.userId',array("*"))
                    ->where('wa_sos_receivers.receiver_id =?', $userId)
                    ->where('wa_sos.sos_id =?', $sosId);
       
        return $this->fetchRow($select);
    } 

    public function getActiveSOSRowsByUserId($userId){        
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinInner('wa_sos','wa_sos.user_id = wa_sos_receivers.user_id',array("sos_id"))
                    ->joinInner('wa_sos_setting','wa_sos_setting.user_id = wa_sos.user_id',array("*"))
                    ->joinInner('users','users.userId = wa_sos.user_id',array("userFullName","userLatitude","userLongitude"))
                    ->where('wa_sos_receivers.receiver_id =?', $userId)
                    ->where('wa_sos_setting.is_sos_setup =?', '1')
                    ->where('wa_sos_setting.is_sos_active =?', '1')
                    ->where('wa_sos_receivers.is_deleted =?', '0')
                    ->where('wa_sos.is_profile_active =?', '1')
		    ->group('wa_sos.sos_id');
       
        return $this->fetchAll($select);
    }
    
    public function getAllRowsByReceiverId($receiverId){        
        $select = $this->select()
                    ->where('receiver_id =?', $receiverId)
                    ->where('is_deleted =?', '0');
        
        return $this->fetchAll($select);
    }
    
    public function deleteSOSReceivers($sosId){
        $this->delete(array("sos_id = ?"=>$sosId));
    } 
    
    public function deleteEmergencyReceivers($sosId){
        $this->delete(array("sos_emergency_id = ?"=>$sosId));
    } 
    
    public function getWaGuardRowsByUserId($Id,$userId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinInner('users','users.userId = wa_sos_receivers.receiver_id',array("userNickName"))
                    ->where('wa_sos_receivers.user_id =?', $userId)
                    ->where('wa_sos_receivers.sos_emergency_id =?', $Id);
        return $this->fetchAll($select);
    } 
    
    public function getWaGuardRowsByReceiverId($Id,$userId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))  
                    ->joinInner('users','users.userId = wa_sos_receivers.receiver_id',array("userNickName"))
                    ->where('wa_sos_receivers.receiver_id =?', $userId)
                    ->where('wa_sos_receivers.sos_emergency_id =?', $Id);       
        return $this->fetchAll($select);
    } 
    
    public function getWaTrackRowsByUserId($Id,$userId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->where('wa_sos_receivers.user_id =?', $userId)
                    ->where('wa_sos_receivers.sos_emergency_id =?', $Id);
        return $this->fetchAll($select);
    }    
    
    public function getRowsByEmergencyId($Id){
        $select = $this->select()
                    ->where('sos_emergency_id =?', $Id);
        return $this->fetchAll($select);
    }   
    
    public function getWaEmergencyRowsByUserId($Id,$userId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinLeft('users','users.userId = wa_sos_receivers.receiver_id',array("userNickName"))
                    ->where('wa_sos_receivers.user_id =?', $userId)
                    ->where('wa_sos_receivers.sos_emergency_id =?', $Id);
        return $this->fetchAll($select);
    } 
    
    public function getActiveWAGuardByUserId($userId){        
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinInner('wa_sos_emergency','wa_sos_emergency.sos_emergency_id = wa_sos_receivers.sos_emergency_id',array("sos_emergency_id","preset_message"))
                    ->joinInner('users','users.userId = wa_sos_emergency.user_id',array("userFullName","userLatitude","userLongitude"))
                    ->where('wa_sos_receivers.receiver_id =?', $userId)
                    ->where('wa_sos_emergency.emergency_type =?', '1')
                    ->where('wa_sos_emergency.is_start =?', '1')
                    ->where('wa_sos_receivers.is_deleted =?', '0')
		    ->group('wa_sos_emergency.sos_emergency_id');
      
        return $this->fetchAll($select);
    }
    
    public function getActiveWATrackByUserId($userId){        
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_receivers',array('*'))
                    ->joinInner('wa_sos_emergency','wa_sos_emergency.sos_emergency_id = wa_sos_receivers.sos_emergency_id',array("sos_emergency_id","preset_message"))
                    ->joinInner('users','users.userId = wa_sos_emergency.user_id',array("userFullName","userLatitude","userLongitude"))
                    ->where('wa_sos_receivers.receiver_id =?', $userId)
                    ->where('wa_sos_emergency.emergency_type =?', '2')
                    ->where('wa_sos_emergency.is_start =?', '1')
                    ->where('wa_sos_receivers.is_deleted =?', '0')
		    ->group('wa_sos_emergency.sos_emergency_id');
       
        return $this->fetchAll($select);
    }
}

