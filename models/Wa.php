<?php

class Application_model_DbTable_WaRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_Wa extends Zend_Db_Table_Abstract{
    protected $_name = "wa";
    protected $_primary = "wa_id";
    protected $_rowClass = "Application_model_DbTable_WaRow";
    
    const WA_TYPE_GO = 'go';
    const WA_TYPE_ARREST = 'arrest';
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
    
    public function getRecordsByUserId($user_id,$offset,$get_incoming_wa,$search_type){
        $waTable = new Application_Model_DbTable_Wa();
        
        // outgoing wa listing
        $select1 = $waTable->select()->setIntegrityCheck(false)
                            ->from('wa', array("*",'order_key' => 'modification_date','is_incoming_request' => new Zend_Db_Expr('0')))
                            ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                            ->where('user_id =?',$user_id)
                            ->where("type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE);
                            
        // incoming wa listing
        
        $current_date = date("Y-m-d H:i:s");
        
        if($get_incoming_wa){
            $select2 = $waTable->select()->setIntegrityCheck(false)
                                ->from("wa",array("*",'order_key' => 'last_delivery_date_utc','is_incoming_request' => new Zend_Db_Expr('1')))
                                ->joinInner('wa_receivers', 'wa_receivers.wa_id = wa.wa_id', array())
                                ->joinInner('users','users.userId = wa.user_id',array("userNickName"))
                                ->where('wa_receivers.receiver_id =?',$user_id)
                                ->where("type !=?",Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE)
                                ->where("wa.first_delivery_date_utc <=?",$current_date);

            $select = $waTable->select()
                                ->union(array($select1,$select2))
                                ->order('order_key desc')
                                ->limit(10,$offset);

            return $waTable->fetchAll($select);
            
        }else{
            $select1 = $select1->order('order_key desc')
                                ->limit(10,$offset);
            
            return $waTable->fetchAll($select);
        }
        
        
    }
}

?>
