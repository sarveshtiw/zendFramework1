<?php

class Application_model_DbTable_WaEventSendDetailsRow extends Zend_Db_Table_Row_Abstract{
     public function deliveryDate(){
        return date("Y-m-d", strtotime($this->delivery_date));
    }
    
    public function deliveryTime(){
        return date("H:i", strtotime($this->delivery_date));
    }
}

class Application_Model_DbTable_WaEventSendDetails extends Zend_Db_Table_Abstract{
    protected $_name = "wa_event_send_details";
    protected $_primary = "id";
    protected $_rowClass = "Application_model_DbTable_WaEventSendDetailsRow";
    
    const VITAL_CHECK_QUARTERLY = 'quarterly';
    const VITAL_CHECK_BIANNUAL = 'biannual';
    const VITAL_CHECK_ANNUALLY = 'annually';
    
    const VITAL_CHECK_HOUR = 'hour';
    const VITAL_CHECK_DAYS = 'day';
    const VITAL_CHECK_WEEK = 'week';
    const VITAL_CHECK_MONTH = 'month';
    
    const VITAL_CHECK_TYPE_EVENT = 'event'; 
    const VITAL_CHECK_TYPE_AFTER = 'after'; 
    const VITAL_CHECK_TYPE_TESTAMENT = 'testament'; 
    
    
    public function getRowById($id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                            ->where('id =?',$id);
        
        return $waEventSendTable->fetchRow($select);
    }
     
    public function getRowByWaId($wa_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('wa_id =?',$wa_id)
                        ->where('is_active =?','1');
        
        return $waEventSendTable->fetchRow($select);
    }

    public function getRowByUserId($user_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                            ->where('user_id =?',$user_id);
        
        return $waEventSendTable->fetchRow($select);
    }
    
    public function getAllRowByUserId($user_id,$testament_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('testament_id =?',$testament_id);
        
        return $waEventSendTable->fetchAll($select);
    }
    
    public function getAllEventSendRowByUserId($user_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('wa_event_send_details.vital_check_type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT);
        return $waEventSendTable->fetchAll($select);
    }

    public function getAllWAEventSendRowByUserId($user_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('(wa_event_send_details.vital_check_type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
                        ->orwhere('wa_event_send_details.vital_check_type =?)', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER);
        return $waEventSendTable->fetchAll($select);
    }
 
    public function checkDeceasedByUserId($user_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('usertype =?','1')
                        ->where('(wa_event_send_details.vital_check_type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
                        ->orwhere('wa_event_send_details.vital_check_type =?)', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
                        ->order('id DESC')
                        ->limit(1); 
       
        return $waEventSendTable->fetchRow($select);                       
    }

    public function getAllRowByUserIdAndVitalType(){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()->setIntegrityCheck(FALSE)
                        ->from("wa_testaments", array("*"))
                        ->joinInner('wa_event_send_details', 'wa_event_send_details.user_id = wa_testaments.user_id', 
                               array("id","is_status","event_send_date"))
                        ->where('wa_event_send_details.vital_check_type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT)
                        ->where('wa_testaments.is_send =?','2')
                        ->where('wa_event_send_details.is_status =?','1')
                        ->where('wa_event_send_details.event_status =?','1')
                        ->group('wa_testaments.testament_id');
        
        return $waEventSendTable->fetchAll($select);
    }
    
    public function getAllWARowByUserIdAndVitalType(){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()->setIntegrityCheck(FALSE)
                        ->from("wa", array("*"))
                        ->joinInner('wa_event_send_details', 'wa_event_send_details.wa_id = wa.wa_id', 
                               array("id","is_status","event_send_date"))
                        ->where('(wa_event_send_details.vital_check_type =?', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
                        ->orwhere('wa_event_send_details.vital_check_type =?)', Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
                        ->where('wa.is_send =?','2')
                        ->where('wa.is_send !="3"')
                        ->where('wa_event_send_details.is_status =?','1')
                        ->where('wa_event_send_details.event_status =?','1')
                        ->group('wa.wa_id');
        
        return $waEventSendTable->fetchAll($select);
    }
    
    public function getRowByUserIdAndVitalType($user_id,$vital_check_type){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                            ->where('user_id =?',$user_id)
                            ->where('vital_check_type =?',$vital_check_type);
        
        return $waEventSendTable->fetchRow($select);
    }

    public function getLastRowByUserIdAndVitalType($user_id,$vital_type){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                            ->where('user_id =?',$user_id)
                            ->where('vital_check_type =?',$vital_type)                            
                            ->order('id DESC')
                            ->limit(1);
       
        return $waEventSendTable->fetchRow($select);
    }

    public function saveEventSendTable($data) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        $insert_id        = $waEventSendTable->insert($data); 
        return $insert_id;
    }
    
    public function updateEventSendTable($data) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        $waEventSendTable->update($data, 'id ='.$data['id']); 
        return $data['id'];
    }
   
    public function deleteEventSendTable($user_id) {
        $this->delete(array("testament_id !=''","user_id = ?"=>$user_id,"vital_check_type = ?"=> Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT));
    } 
    
    public function deleteAllEventByWAId($user_id,$wa_id) {
        $this->delete(array("user_id = ?"=>$user_id,"wa_id = ?"=> $wa_id));
    } 

    public function deleteEventByUserIdAndVitalType($user_id,$vital_type) {
        $this->delete(array("user_id = ?"=>$user_id,"vital_check_type = ?"=> $vital_type));
    }
    
    public function getAllSendVitalByUserId($user_id) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
               
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('is_status =?','1')
                        ->where('testament_id !="0"')
                        ->order('id DESC')
                        ->limit(1);
        $row    = $waEventSendTable->fetchRow($select); 
	if (!empty($row)) {
            $row    = $row->toArray();
            $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('testament_id =?', $row['testament_id'])
                        ->where('is_status =?','1');
            $row    = $waEventSendTable->fetchAll($select)->toArray();
        }
      return $row;     
    }
    
    public function getAllWASendVitalByUserId1($user_id) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
              
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('is_status =?','1')
                        ->where('wa_id !="0"')
                        ->order('id DESC')
                        ->limit(1);
        $row    = $waEventSendTable->fetchRow($select); 
	if (!empty($row)) {
            $row    = $row->toArray();
            $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('wa_id !="0"')
                        ->where('event_status =?','1');  
            $row    = $waEventSendTable->fetchAll($select)->toArray();
        }
      return $row;           
    }

    public function getAllWASendVitalByUserId($user_id) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                    ->where('user_id =?',$user_id)
                    ->where('wa_id !="0"')
                    ->where('event_status =?','1')
                    ->where('is_status =?','1');
        
        return $waEventSendTable->fetchAll($select)->toArray();          
    }
    
    public function getAllWAOwnerSendVitalByUserId($user_id) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                    ->where('user_id =?',$user_id)
                    ->where('usertype =?','1')
                    ->where('wa_id !="0"')
                    ->where('event_status =?','1')
                    ->where('is_status =?','1')
                    ->group('wa_id');
        
        return $waEventSendTable->fetchAll($select)->toArray();          
    }
    
    public function getAllWATrusteeSendVitalByUserId($user_id) {
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                    ->where('user_id =?',$user_id)
                    ->where('usertype =?','1')
                    ->where('wa_id !="0"')
                    ->where('event_status =?','1')
                    ->where('is_status =?','1')
                    ->group('wa_id');
       
        return $waEventSendTable->fetchAll($select)->toArray();          
    }
    
    public function getTrusteeAlertRowByUserId($user_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('usertype =?','2')
                        ->where('testament_id !="0"')
                        ->order('id DESC')
                        ->limit(1);
        
        return $waEventSendTable->fetchRow($select)->toArray();
    }
    
    public function getTrusteeAlertRowByWAId($user_id,$wa_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('usertype =?','2')
                        ->where('wa_id =?', $wa_id)
                        ->order('id DESC')
                        ->limit(1);
        
        return $waEventSendTable->fetchRow($select);
    }
    
    public function getFirstRowByWaId($wa_id)
    {        
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('wa_id =?',$wa_id)
                        ->order('id ASC')
                        ->limit(1);
        
        return $waEventSendTable->fetchRow($select);
    }
    
    public function getAllEventRowByWaId($user_id,$wa_id){
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();
        
        $select = $waEventSendTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('wa_id =?',$wa_id)
                        ->where('event_status =?','1')
                        ->order('id ASC');
        
        return $waEventSendTable->fetchAll($select);
    }
    
}

