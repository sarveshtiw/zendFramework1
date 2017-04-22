<?php
class Application_Model_DbTable_WaSosSettingRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosSetting extends Zend_Db_Table_Abstract{
    protected $_name      = "wa_sos_setting";
    protected $_primary   = "sett_id";
    protected $_rowClass  = "Application_Model_DbTable_WaSosSettingRow";
    
    public function getRowById($settId){
        $select = $this->select()
                    ->where('sett_id =?', $settId);
       return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $select = $this->select()
                    ->from($this, array('max(sett_id) as sett_id','*'))
                    ->where('user_id =?', $userId);       
        return $this->fetchRow($select);
    }        
}

class Application_Model_DbTable_WaSosVolunteerHistoryRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosVolunteerHistory extends Zend_Db_Table_Abstract{
    protected $_name = "wa_sos_volunteer_history";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaSosVolunteerHistoryRow";
    
    public function getRowById($id){
        $select = $this->select()
                    ->where('id =?', $id);        
        return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $select =  $this->select()->setIntegrityCheck(false)
                        ->from('wa_sos_volunteer_history',array('*'))
                        ->joinInner('wa_sos','wa_sos.user_id = wa_sos_volunteer_history.user_id',array("sos_id"))
                        ->joinInner('wa_sos_setting','wa_sos_setting.user_id = wa_sos.user_id',array("*"))
                        ->joinInner('users','users.userId = wa_sos.user_id',array("userFullName","userLatitude","userLongitude"))
                        ->where('wa_sos_volunteer_history.receiver_id =?', $userId)
                        ->where('wa_sos_setting.is_sos_setup =?', '1')
                        ->where('wa_sos_setting.is_sos_active =?', '1')
                        ->where('wa_sos.is_profile_active =?', '1')
                        ->group('wa_sos.sos_id');       
        return $this->fetchAll($select);   
    }
           
    public function getRowBySOSId($userId,$sosId){
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('wa_sos_volunteer_history',array('*'))
                    ->joinInner('users','wa_sos_volunteer_history.user_id = users.userId',array("*"))
                    ->joinInner('wa_sos','wa_sos.user_id = users.userId',array("*"))
                    ->where('wa_sos_volunteer_history.receiver_id =?', $userId)
                    ->where('wa_sos.sos_id =?', $sosId);       
        return $this->fetchRow($select);
    } 
}

class Application_Model_DbTable_WaSosEmergencyRow extends Zend_Db_Table_Row_Abstract{
   
}

class Application_Model_DbTable_WaSosEmergency extends Zend_Db_Table_Abstract{
    protected $_name     = "wa_sos_emergency";
    protected $_primary  = "sos_emergency_id";
    protected $_rowClass = "Application_Model_DbTable_WaSosEmergencyRow";
    
    public function getRowById($sosId){
        $select = $this->select()
                    ->where('sos_emergency_id =?', $sosId);
        return $this->fetchRow($select);
    }
    
    public function isUserWaGuardExists($userId){
        $select  = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('emergency_type =?', '1');
        return $this->fetchRow($select);
    }    
    
    public function isUserWaTrackExists($userId){
        $select  = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('emergency_type =?', '2');
        return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){        
        $select  = $this->select()
                    ->where('user_id =?', $userId);
        return $this->fetchRow($select);
    }
    
    public function sendEmergencyReceivers($sos_emergency_id){        
        $data  = array('is_send' => '1');
        $where = array('sos_emergency_id' => $sos_emergency_id);
        $this->update($data, $where);
    }
    
    public function stopEmergencyReceivers($sos_emergency_id){        
        $data  = array('is_send' => '2');
        $where = array('sos_emergency_id' => $sos_emergency_id);
        $this->update($data, $where);
    }
}

class Application_Model_DbTable_WaSosEmergencySendDetailsRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaSosEmergencySendDetails extends Zend_Db_Table_Abstract{
    protected $_name = "wa_emergency_send_details"; 
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaSosEmergencySendDetailsRow";
    
    public function getRowById($id){
        $select = $this->select()
                    ->where('id =?', $id);
        return $this->fetchRow($select);
    }
    
    public function getWAGuardFirstRowByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('emergency_type =?', '1')
                    ->order('id DESC')
                    ->limit(1,0);
        return $this->fetchRow($select);
    }
    
    public function getWAGuardRowByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('emergency_type =?', '1')
                    ->where('event_send =?', '0')
                    ->order('id ASC');
        return $this->fetchAll($select);
    }  
    
    public function getWATrackByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('emergency_type =?', '2')
                    ->order('id DESC')
                    ->limit(1,0);
        return $this->fetchRow($select);
    }

    public function getWAGuardRecordsForPushNoitifications(){
        $currentTime = date("Y-m-d H:i:s");
        $select = $this->select()->setIntegrityCheck(false)
                    ->from("wa_emergency_send_details",array("*"))
                    ->joinInner("wa_sos_emergency", "wa_sos_emergency.sos_emergency_id = wa_emergency_send_details.sos_emergency_id",array("*"))
                    ->where("wa_emergency_send_details.event_send_time <?", $currentTime)
                    ->where("wa_emergency_send_details.emergency_type =?", '1')
                    ->where("wa_emergency_send_details.event_status =?", '1')
                    ->where("wa_emergency_send_details.event_send =?", '0')
                    ->where("wa_sos_emergency.is_start =?", "1");
        
        return $this->fetchAll($select);                
    } 

    public function getWAGuardSendNoitificationUsers(){
        $currentTime = date("Y-m-d H:i:s");
        $select = $this->select()->setIntegrityCheck(false)
                    ->from("wa_emergency_send_details",array("*"))
                    ->joinInner("wa_sos_emergency", "wa_sos_emergency.sos_emergency_id = wa_emergency_send_details.sos_emergency_id",array("*"))
                    ->where("wa_emergency_send_details.event_send_time <?", $currentTime)
                    ->where("wa_emergency_send_details.next_event_send_time <?", $currentTime)
                    ->where("wa_emergency_send_details.emergency_type =?", '1')
                    ->where("wa_emergency_send_details.event_status =?", '1')
                    ->where("wa_emergency_send_details.event_send =?", '1')
                    ->where("wa_emergency_send_details.event_response =?", '2')
                    ->where("wa_sos_emergency.is_start =?", "1");
        
        return $this->fetchAll($select);                
    }
    
    public function getWATrackRecordsForPushNoitifications(){
        $currentTime = date("Y-m-d H:i:s");
        $select = $this->select()->setIntegrityCheck(false)
                    ->from("wa_emergency_send_details",array("*"))
                    ->joinInner("wa_sos_emergency", "wa_sos_emergency.sos_emergency_id = wa_emergency_send_details.sos_emergency_id",array("*"))
                    ->where("wa_emergency_send_details.event_send_time <?", $currentTime)
                    ->where("wa_emergency_send_details.emergency_type =?", '2')
                    ->where("wa_emergency_send_details.event_status =?", '1')
                    ->where("wa_emergency_send_details.event_send =?", '0')
                    ->where("wa_sos_emergency.is_send =?", '0')
                    ->where("wa_sos_emergency.is_start =?", '1');        
        return $this->fetchAll($select);                
    } 
    
    public function getWATrackSendNoitificationUsers(){
        $currentTime = date("Y-m-d H:i:s");
        $select = $this->select()->setIntegrityCheck(false)
                    ->from("wa_emergency_send_details",array("*"))
                    ->joinInner("wa_sos_emergency","wa_sos_emergency.sos_emergency_id = wa_emergency_send_details.sos_emergency_id",array("*"))
                    ->where("wa_emergency_send_details.event_send_time <?", $currentTime) 
                    ->where("wa_emergency_send_details.event_end_time <?", $currentTime)
                    ->where("wa_emergency_send_details.emergency_type =?", '2')
                    ->where("wa_emergency_send_details.event_status =?", '1')
                    ->where("wa_emergency_send_details.event_send =?", '1')
                    ->where("wa_emergency_send_details.event_response =?", '2')
                    ->where("wa_sos_emergency.is_send =?", '1')
                    ->where("wa_sos_emergency.is_start =?", '1');
        
        return $this->fetchAll($select);                
    }
    
    public function getRowByEmergencyId($id){        
        $select = $this->select()
                    ->where('sos_emergency_id =?', $id);
        return $this->fetchRow($select);
    }
}

class Application_Model_DbTable_WaTrackHistoryRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaTrackHistory extends Zend_Db_Table_Abstract{
    protected $_name = "wa_track_history";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_WaTrackHistoryRow";
            
    public function getRowById($Id){
        $select = $this->select()
                    ->where('id =?', $Id);
        return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('is_deleted =?', '0');
        return $this->fetchAll($select);
    }
    
    public function deleteTrackLocation($userId){
        $data = array('is_deleted' => '1');
        $where = array('user_id' => $userId);
        $this->update($data, $where);
    }
    
    public function isUserWaTrackLatLongExists($userId,$userLatitude,$userLongitude){        
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('latitude =?', $userLatitude)
                    ->where('longitude =?', $userLongitude)
                    ->where('is_deleted =?', '0');
        return $this->fetchAll($select);
    }
}