<?php

class Application_model_DbTable_Wa1Row extends Zend_Db_Table_Row_Abstract{
     public function deliveryDate(){
        return date("Y-m-d", strtotime($this->delivery_date));
    }
    
    public function deliveryTime(){
        return date("H:i", strtotime($this->delivery_date));
    }
}

class Application_Model_DbTable_Wa1 extends Zend_Db_Table_Abstract{
    protected $_name = "wa";
    protected $_primary = "wa_id";
    protected $_rowClass = "Application_model_DbTable_Wa1Row";
    
    const WA_TYPE_GO = 'go';
    const WA_TYPE_ARREST = 'arrest';
    const WA_TYPE_EVENT = 'event';
    const WA_TYPE_AFTERLIFE = 'after';
    const TYPE_CHAT_MESSAGE = "chat_message";
    
    const MESSAGE_TYPE_TEXT = 'text';
    const MESSAGE_TYPE_AUDIO = 'audio';
    const MESSAGE_TYPE_VIDEO = 'video';
    const MESSAGE_TYPE_IMAGE = 'image';
    
    public function getRowById($wa_id){
        $waTable = new Application_Model_DbTable_Wa1();
        
        $select = $waTable->select()
                            ->where('wa_id =?',$wa_id);
        
        return $waTable->fetchRow($select);
    }
    
    public function getRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa1();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0'),'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('wa.user_id =?',$user_id)
                            ->where("type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE)
                            ->where("type !=?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                            ->where("type !=?",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->where('is_deleted =?',"0");
        
                            
        $searchSet = array(
            "text"  => "text", 
            "image" => "image_link",
            "audio" => "audio_link",
            "video" => "video_link"
        );
        
        if($search_type && ($search_type!="all")){
            $search_value = $searchSet[$search_type];
            
            $select1->where("$search_value !=?","");
        } 
        
        // incoming wa listing
        
        $current_date = date("Y-m-d H:i:s");
        
        if($get_incoming_wa){
            
            $select2 = $waTable->select()->setIntegrityCheck(false)
                                ->from("wa",array("*",'order_key' => 'last_delivery_date_utc','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where("wa.type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE)
                                ->where("wa.type !=?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                                ->where("wa.first_delivery_date_utc <=?",$current_date);
            
            if($search_type && ($search_type!="all")){
                $select2->where("$search_value !=?","");
                
            }
            
            $select = $waTable->select()
                                ->union(array($select1,$select2))
                                ->order('order_key desc')
                                ->limit(10,$offset);
            
            return $waTable->fetchAll($select);
            
        }else{
            $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            
            return $waTable->fetchAll($select1);
        }
        
        
    }
    
    
    /**
     * function for getting wa event and wa after listing 
     */
    
    public function getEventRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa1();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0')))
                            ->joinInner('wa_event_send_details','wa_event_send_details.wa_id = wa.wa_id',array('vital_check','vital_value', 'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('wa.user_id =?',$user_id)
                            ->where("(wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                            ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->where('wa.is_deleted =?',"0")
                            ->where("wa_event_send_details.is_active =?","1");
                            
        $searchSet = array(
            "text"  => "text", 
            "image" => "image_link",
            "audio" => "audio_link",
            "video" => "video_link"
        );
        
        if($search_type && ($search_type!="all")){
            $search_value = $searchSet[$search_type];
            
            $select1->where("$search_value !=?","");
        } 
        
        // incoming wa listing
        
        $current_date = date("Y-m-d H:i:s");
        
        if($get_incoming_wa){
            
            $select2 = $waTable->select()->setIntegrityCheck(false)
                                ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_event_send_details','wa_event_send_details.wa_id = wa.wa_id',array('vital_check','vital_value'))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where('wa.is_send =1')
                                ->where("(wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                                ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                                ->where("wa_event_send_details.is_active =?","1");
                                
            
            if($search_type && ($search_type!="all")){
                $select2->where("$search_value !=?","");
                
            }

            $select = $waTable->select()
                                ->union(array($select1,$select2))
                                ->order('order_key desc')
                                ->limit(10,$offset);
            return $waTable->fetchAll($select);
            
        }else{
            $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            
            return $waTable->fetchAll($select1);
        }
        
        
    }

    
     public function getEventRecordsByUserId1($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa1();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0')))
                            ->joinInner('wa_event_send_details','wa_event_send_details.user_id = wa.user_id',array('vital_check','vital_value', 'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('wa.user_id =?',$user_id)
                            ->where("(wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                            ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->where('wa.is_deleted =?',"0")
                            ->where("wa_event_send_details.is_active =?","1");
                            
        $searchSet = array(
            "text"  => "text", 
            "image" => "image_link",
            "audio" => "audio_link",
            "video" => "video_link"
        );
        
        if($search_type && ($search_type!="all")){
            $search_value = $searchSet[$search_type];
            
            $select1->where("$search_value !=?","");
        } 
        
        // incoming wa listing
        
        $current_date = date("Y-m-d H:i:s");
        
        if($get_incoming_wa){
            
            $select2 = $waTable->select()->setIntegrityCheck(false)
                                ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_event_send_details','wa_event_send_details.user_id = wa.user_id',array('vital_check','vital_value'))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where('wa.is_send =1')
                                ->where("(wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                                ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                                ->where("wa_event_send_details.is_active =?","1");
                                
            
            if($search_type && ($search_type!="all")){
                $select2->where("$search_value !=?","");
                
            }

            $select = $waTable->select()
                                ->union(array($select1,$select2))
                                ->order('order_key desc')
                                ->limit(10,$offset);
            return $waTable->fetchAll($select);
            
        }else{
            $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            
            return $waTable->fetchAll($select1);
        }
        
        
    }
    
    
    public function getReceivedRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa1();
        
        $searchSet = array(
            "text"  => "text", 
            "image" => "image_link",
            "audio" => "audio_link",
            "video" => "video_link"
        );
        
        $current_date = date("Y-m-d H:i:s");
        
        if($get_incoming_wa){
            
            $select2 = $waTable->select()->setIntegrityCheck(false)
                                ->from("wa",array("*",'order_key' => 'last_delivery_date_utc','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where("type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE)
                                ->where("type !=?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                                ->where("wa.first_delivery_date_utc <=?",$current_date);
            
            if($search_type && ($search_type!="all")){
                $select2->where("$search_value !=?","");
                
            }
            
            $select = $select2->order('order_key desc')->limit(10,$offset);
            
            return $waTable->fetchAll($select);
            
        }
        
        
    }
//Get Reveived Event Records By User Id.
    public function getReceivedEventRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa1();
        $searchSet = array(
            "text"  => "text", 
            "image" => "image_link",
            "audio" => "audio_link",
            "video" => "video_link"
        );
        
        // incoming wa listing
        $current_date = date("Y-m-d H:i:s");
        $select2 = $waTable->select()->setIntegrityCheck(false)
                ->from("wa",array("*",'order_key' => 'last_delivery_date_utc','is_incoming_request' => new Zend_Db_Expr('1')))
                ->joinInner('wa_event_send_details','wa_event_send_details.wa_id = wa.wa_id',array("vital_check"))
                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                ->where('wa_receivers.receiver_id =?',$user_id)
                ->where('wa.is_send =1')
                ->where("type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT);

        if($search_type && ($search_type!="all")){
            $select2->where("$search_value !=?","");

        }
        $select = $select2->order('order_key desc')->limit(10,$offset);
        return $waTable->fetchAll($select);
    }    
    
    
    public function sendAllUsersWa($user_id){
        $waTable = new Application_Model_DbTable_Wa1();
        
        $data = array(
            'is_send'           => 1,
            'modification_date' => date("Y-m-d H:i:s")
         );
         
         $where = $waTable->getAdapter()->quoteInto('user_id = ?',$user_id);

         $waTable->update($data, $where);
    
    }
    
        
    
}

?>
