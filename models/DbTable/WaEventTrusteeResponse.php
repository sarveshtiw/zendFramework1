<?php
class Application_Model_DbTable_WaEventTrusteeResponseRow extends Zend_Db_Table_Row_Abstract{
   
}

class Application_Model_DbTable_WaEventTrusteeResponse extends Zend_Db_Table_Abstract
{
    protected $_name = 'wa_event_trustee_response';
    protected $_primary = 'id';
    protected $_rowClass = 'Application_Model_DbTable_WaEventTrusteeResponseRow';
    
    const TRUSTEE_RESPONSE_SEND = 'send';
    const TRUSTEE_RESPONSE_NOTSEND = 'not_send';
    
    
    public function getRowById($id){
      $eventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();  
      
      $select = $eventResponseTable->select()
                                ->where('id=?',$id);
      
      return $eventResponseTable->fetchRow($select);
      
    }
     
    public function save($data)
    {
        $eventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
    
        $insert_id = $eventResponseTable->insert($data);
        return $insert_id;
    }

    /**
     *  get all records by wa event detail id 
     */
    public function getAllResponseByWaSendId($wa_event_detail_id){
      $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
      
      $select = $waEventResponseTable->select()
                    ->where('object_id =?',$wa_event_detail_id);
      
      $eventResponseRowset = $waEventResponseTable->fetchAll($select);
      
      return $eventResponseRowset;          
    }
    
    public function countSendResponse($wa_event_detail_id){
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
       
        $select = $waEventResponseTable->select()
                            ->where('testament_id =?',$wa_event_detail_id)
                            ->where('response =?','1');
        return count($waEventResponseTable->fetchAll($select));
    }
    
    public function countWASendResponse($wa_event_detail_id){
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
       
        $select = $waEventResponseTable->select()
                            ->where('wa_id =?',$wa_event_detail_id)
                            ->where('response =?','1');
        return count($waEventResponseTable->fetchAll($select));
    }
     
    public function getRowByDetailIdAndUserId($wa_event_detail_id,$trustee_id){
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $select = $waEventResponseTable->select()
                            ->where('object_id =?',$wa_event_detail_id)
                            ->where('user_id =?',$trustee_id);
  
        return $waEventResponseTable->fetchRow($select);
    }
    
    public function getTrusteeRowByDetailIdAndUserId($wa_event_detail_id,$trustee_id){
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $select = $waEventResponseTable->select()
                            ->where('object_id =?',$wa_event_detail_id)
                            ->where('trustee_id =?',$trustee_id);
  
        return $waEventResponseTable->fetchRow($select);
    }
    
    public function deleteTrusteesResponse($user_id){
        $waEventResponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $waEventResponseTable->delete(array("user_id = ?"=>$user_id));
        
    }
}
