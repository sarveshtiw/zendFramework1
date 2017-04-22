<?php

class Application_model_DbTable_WaRow extends Zend_Db_Table_Row_Abstract{
     public function deliveryDate(){
        return date("Y-m-d", strtotime($this->delivery_date));
    }
    
    public function deliveryTime(){
        return date("H:i", strtotime($this->delivery_date));
    }
}

class Application_Model_DbTable_Wa extends Zend_Db_Table_Abstract{
    protected $_name = "wa";
    protected $_primary = "wa_id";
    protected $_rowClass = "Application_model_DbTable_WaRow";
    
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
        $waTable = new Application_Model_DbTable_Wa();
        
        $select = $waTable->select()
                            ->where('wa_id =?',$wa_id);
        
        return $waTable->fetchRow($select);
    }

    public function save($data) {
        $waTable = new Application_Model_DbTable_Wa();
        $insert_id = $waTable->insert($data);
        return $insert_id;
    }
    
    public function getRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0'),'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('wa.user_id =?',$user_id)
                            ->where("type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE)
                            ->where("type !=?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                            ->where("type !=?",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->where('is_deleted !="1"')
                	    ->group('wa.wa_id');
        
                            
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
		            ->where("wa.first_delivery_date_utc <=?", $current_date)
		            ->where("wa.is_send =?",'3')
		            ->where('is_deleted !="1"')
		            ->group('wa.wa_id');
            
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
    
    public function getEventRecordsByUserId1($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
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
                                ->joinInner('wa_event_send_details','wa_event_send_details.vital_check = wa.type',array('vital_check','vital_value'))
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

    
     public function getEventRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                    ->from('wa', array("*", 'order_key' => 'modification_date', 'is_incoming_request' => new Zend_Db_Expr('0')))
                    ->joinInner('wa_event_send_details', 'wa_event_send_details.vital_check_type = wa.type', array('event_send_date', 'event_expiry_date', 'is_read' => new Zend_Db_Expr('1')))
                    ->joinInner('users', 'users.userId = wa.user_id', array("userNickName"))
                    ->where('wa.user_id =?', $user_id)
                    ->where("(wa.type =?", Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                    ->orWhere("wa.type =?)", Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                    ->where('wa.is_deleted =?', "0")
                    //->where("wa_event_send_details.user_id =?", $user_id)
                    ->group('wa.wa_id');

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
                    ->from('wa', array("*", 'order_key' => 'modification_date', 'is_incoming_request' => new Zend_Db_Expr('1')))
                    ->joinInner('wa_event_send_details', 'wa_event_send_details.vital_check_type = wa.type', array('event_send_date', 'event_expiry_date'))
                    ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                    ->joinInner('users', 'users.userId = wa.user_id', array("userNickName"))
                    ->where('wa_receivers.receiver_id =?', $user_id)
                    ->where('wa.is_send ="3"')
                    ->where("(wa.type =?", Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                    ->orWhere("wa.type =?)", Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                    ->where('wa.is_deleted =?', "0")
                    //->where("wa_event_send_details.user_id =?", $user_id)
                    ->group('wa.wa_id');
                                
            
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
        $waTable = new Application_Model_DbTable_Wa();
        
    
                      
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
        $waTable = new Application_Model_DbTable_Wa();
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
                ->joinInner('wa_event_send_details','wa_event_send_details.vital_check_type = wa.type',array("vital_check"))
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
    
   public function sendAllUsersWaEvent($user_id,$wa_id,$value)
    {
        $waTable = new Application_Model_DbTable_Wa();

        $select = $waTable->select()
                    ->where('user_id =?', $user_id)
                    ->where('wa_id =?', $wa_id)
                    ->where("type =?", Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                    //->where('is_send =?', '0')
                    ->where('status =?', '1')
                    ->where('is_deleted =?', '0')
                    ->where('is_expired =?', '0');

        $waRowset = $waTable->fetchAll($select);
        if(isset($waRowset))
        {
            foreach ($waRowset as $waRow)
            {
                $waRow->is_send = $value;
                $waRow->modification_date = date("Y-m-d H:i:s");
                $waRow->save();
            }
        }
    }

    public function sendAllUsersWaAfter($user_id,$wa_id,$value)
    {
        $waTable = new Application_Model_DbTable_Wa();

        $select = $waTable->select()
                ->where('user_id =?', $user_id)
                ->where('wa_id =?', $wa_id)
                ->where("type =?", Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                //->where('is_send =?', '0')
                ->where('status =?', '1')
                ->where('is_deleted =?', '0')
                ->where('is_expired =?', '0');

        $waRowset = $waTable->fetchAll($select);
        if(isset($waRowset))
        {
            foreach ($waRowset as $waRow)
            {
                $waRow->is_send = $value;
                $waRow->modification_date = date("Y-m-d H:i:s");
                $waRow->save();
            }
        }
    }

    public function sendVitalCheckUsers($wa_id,$value)
    {
        $select   = $this->select()
                    ->where('wa_id =?',$wa_id)
                    ->where('status =?', '1');
        $Rowset   = $this->fetchRow($select);
        
        if(isset($Rowset))
        {
            $Rowset->is_send = $value;
            $Rowset->save();
        }
    }

    public function getGoRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0'),'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('user_id =?',$user_id)
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
        $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            
        return $waTable->fetchAll($select1);

    }
    
      /**
     * function for getting wa event and wa after listing 
     */
        
    public function getAfterEventRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
    
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0')))
                            ->joinInner('wa_event_send_details','wa_event_send_details.vital_check = wa.type',array("vital_value","vital_check",'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('user_id =?',$user_id)
                            ->where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
                            ->where('wa.is_deleted =?',"0");
                            
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
                                ->from("wa",array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_event_send_details','wa_event_send_details.wa_id = wa.wa_id',array("vital_value","vital_check"))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where('wa.is_send =1')
                                ->where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT);
                                
            
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
    
    public function getAfterRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0')))
                            ->joinInner('wa_event_send_details','wa_event_send_details.vital_check_type = wa.type',array("vital_value","vital_check",'is_read' => new Zend_Db_Expr('1')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('wa.user_id =?',$user_id)
//                            ->where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT)
//                            ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->Where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)
                            ->where('wa.is_deleted =?',"0");
                            
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
                                ->from("wa",array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_event_send_details','wa_event_send_details.vital_check = wa.type',array("vital_value","vital_check"))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where('wa.is_send =1')
//                                ->where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_EVENT);
//                                ->orWhere("wa.type =?)",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE);
                                ->Where("wa.type =?",Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE);
                                
            
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
    
    
    
    public function activateDeactivateEventAfterByWaId($userId,$waType,$status){
        $waTable = new Application_Model_DbTable_Wa();
        $db = Zend_Db_Table::getDefaultAdapter();   
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $db->update('wa', array("status" => $status), "user_id =".$userId." AND type='".$waType."' ");
    }
    function getReceivedAfterRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
        ->from("wa",array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_event_send_details','wa_event_send_details.vital_check = wa.type',array("vital_value","vital_check"))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array('is_read'))
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$userId)
                                ->where('wa.is_send =1');
                            
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
        
            $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            return $waTable->fetchAll($select1);
    }

    public function getAllWALaterRecordsForPushNoitifications()
    {        
        $waTable        = new Application_Model_DbTable_Wa();
        
        $currentTime    = time();
        $currentDate    = date("Y-m-d H:i:s");   
        $select         = $waTable->select()
                                ->where("((first_delivery_date_utc <?",$currentDate)
                                ->orWhere("next_delivery_date_utc is not null")
                                ->where("next_delivery_date_utc <?)",$currentDate)
                                ->where('type !=?',Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
                                ->where('type !=?',Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
                                ->where('is_deleted =?',"0")
                                ->where('is_send =?)',"0");
        return $waTable->fetchAll($select);;
    }
    
    public function getAllWARecordsForPushNoitifications()
    {        
        $waTable        = new Application_Model_DbTable_Wa();
        
        $currentTime    = time();
        $currentDate    = date("Y-m-d H:i:s");   
        $select         = $this->select()->setIntegrityCheck(false)
                            ->from("wa", array("*"))
                            ->joinLeft('wa_event_send_details', 'wa_event_send_details.wa_id = wa.wa_id', 
                                    array("id","usertype","vital_check_type","event_send_date"))
                            ->where("wa_event_send_details.event_send_date <?", $currentDate)
                            ->where("wa_event_send_details.is_status =?",'0')
                            ->where("wa_event_send_details.event_status =?",'1')
                            ->where("(wa.is_send =?",'0')
                            ->orWhere("wa.is_send =?",'1')
                            ->orWhere("wa.is_send =?)",'2')
                            ->where("wa.status =?",'1')
                            ->where("(wa_event_send_details.vital_check_type =?", Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_EVENT)
			    ->orwhere("wa_event_send_details.vital_check_type =?)", Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_AFTER)
			    ->order("wa_event_send_details.id ASC");
        return $this->fetchAll($select);  
    }

}

?>
