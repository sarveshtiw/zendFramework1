<?php

class Application_Model_DbTable_NotificationTypeRow extends Zend_Db_Table_Row_Abstract{
    
   
}

class Application_Model_DbTable_NotificationType extends Zend_Db_Table_Abstract
{
    protected $_name = 'notification_types';
    protected $_primary = 'id';
    protected $_rowClass = 'Application_Model_DbTable_NotificationTypeRow';
    
    const INCOMING_FRIEND_REQUEST   = 'incoming_friend_request';
    const ACCEPT_FRIEND_REQUEST     = 'accept_friend_request';
    const INCOMING_TRUSTEE_REQUEST  = 'incoming_trustee_request';
    const ACCEPT_TRUSTEE_REQUEST    = 'accept_trustee_request';
    const WA_OWNER_ALERT            = 'wa_owner_alert';
    const WA_TURSTEE_ALERT          = 'wa_trustee_alert';
    const INVITE_WITNESS_REQUEST    = 'invite_witness_request';
    const REJECT_WITNESS_REQUEST    = 'reject_witness_request';
    const UPDATE_WITNESS_REQUEST    = 'update_witness_request';
    const CONFIRM_WITNESS_REQUEST   = 'confirm_witness_request';
    const TESTAMENT_TRUSTEE_ACCEPT  = 'testament_trustee_accept';
    const TESTAMENT_RECEIVER_ACCEPT = 'testament_receiver_accept';
    const SOS_ACTIVATE_REQUEST      = "wa_sos_activate_request";
    const SOS_DEACTIVATE_REQUEST    = "wa_sos_deactivate_request";
    const SOS_RECEIVER_RESPONSE     = "wa_sos_receiver_response";
    
    public function getRowById($id){
       $notifiTypeTable = new Application_Model_DbTable_NotificationType();
       $select = $notificationTypeTable->select()
                            ->where('id=?',$id);
       
       return $notifiTypeTable->fetchRow($select);
    }
    
    public function getRowByType($type){
        
        $notifiTypeTable = new Application_Model_DbTable_NotificationType();
        
        $select = $notifiTypeTable->select()
                        ->where('type =?',$type);
        
        return $notifiTypeTable->fetchRow($select);
                
    }
    
    public function getNotificationTypes(){
        $notifiTypeTable = new Application_Model_DbTable_NotificationType();
        
        return $notifiTypeTable->fetchAll();
    }
    
}
