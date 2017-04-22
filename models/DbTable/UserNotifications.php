<?php

class Application_Model_DbTable_UserNotificationsRow extends Zend_Db_Table_Row_Abstract{
    
   
}

class Application_Model_DbTable_UserNotifications extends Zend_Db_Table_Abstract
{
    protected $_name = 'user_notifications';
    protected $_primary = 'id';
    protected $_rowClass = 'Application_Model_DbTable_UserNotificationsRow';
    
    public function getRowById($id){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $select = $userNotificationTable->select()
                        ->where('id =?',$id);
        
        return $userNotificationTable->fetchRow($select);
        
    }
    
    public function getNotificationsByUserId($userId,$creation_date = false){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $select = $userNotificationTable->select()->setIntegrityCheck(false)
                                        ->from('user_notifications',array("*"))
                                        ->joinInner('notification_types', 'notification_types.id =  user_notifications.notf_type_id',array('type'));
        
        if($creation_date){
            $select->where('creation_date >?',$creation_date);
        }
        
        $userNotificationRowset = $userNotificationTable->fetchAll($select);
       
        return $userNotificationRowset;
    }
    
    public function saveNotification($data){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $userNotificationRow = $userNotificationTable->createRow($data);
        $userNotificationRow->save();
        
    }
    
    public function createFriendNotification($friendRow){ 
        $this->db = Zend_Db_Table::getDefaultAdapter(); 
		$translate = Zend_Registry::get('Zend_Translate');// initialize database adapter 
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $userTable = new Application_Model_DbTable_Users();
        $notifiTypeTable = new Application_Model_DbTable_NotificationType();
        
        if($friendRow && $friendRow->userId && $friendRow->friendId){
            $userRow = $userTable->getRowById($friendRow->userId);
            $friendUserRow = $userTable->getRowById($friendRow->friendId);

            $select = $userNotificationTable->select()
                                    ->where('user_id =?',$friendRow->friendId)
                                    ->where('friend_row_id =?',$friendRow->id)
                                    ->where('status =?',"1");

            $oldNotificationRow = $userNotificationTable->fetchRow($select);


            switch ($friendRow->status){
                case 0:
                    $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::INCOMING_FRIEND_REQUEST);

                    $data = array(
                        'user_id'           => $friendRow->friendId,
                        'from_user_id'      => $friendRow->userId,
                        'friend_row_id'     => $friendRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $userRow->userNickName.$translate->translate('notifications_txt_3'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'status'            => 1
                    );
                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();
                    
                    break;
                case 1:
                    
                    $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::ACCEPT_FRIEND_REQUEST);
                    $this->db->delete('user_notifications',"user_id =".$friendRow->friendId." && from_user_id=".$friendRow->userId." && notf_type_id=1");

                    $data = array(
                        'user_id'           => $friendRow->userId,
                        'from_user_id'      => $friendRow->friendId,
                        'friend_row_id'     => $friendRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $friendUserRow->userNickName.$translate->translate('notifications_txt_4'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'is_read'           => "0",
                        'status'            => "1"
                    );

                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();

                    $data = array(
                        'user_id'           => $friendRow->friendId,
                        'from_user_id'      => $friendRow->userId,
                        'friend_row_id'     => $friendRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $translate->translate('notifications_txt_5a').$userRow->userNickName.$translate->translate('notifications_txt_5'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'is_read'           => "1",
                        'status'            => "1"
                    );

                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();
                    
                    break;
                    case 2:
                        $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::INCOMING_FRIEND_REQUEST);
                        $this->db->delete('user_notifications',"user_id =".$friendRow->friendId." && from_user_id=".$friendRow->userId." && notf_type_id=1");

                        $data = array(
                            'user_id'           => $friendRow->friendId,
                            'from_user_id'      => $friendRow->userId,
                            'friend_row_id'     => $friendRow->id,
                            'notf_type_id'      => $notifiTypeRow->id,
                            'message'           => $userRow->userNickName.$translate->translate('notifications_txt_6'),
                            'creation_date'     => date("Y-m-d H:i:s"),
                            'modification_date' => date("Y-m-d H:i:s"),
                            'status'            => 2
                        );
                        $userNotificationRow = $userNotificationTable->createRow($data);
                        $userNotificationRow->save();

                        break;
            }

        }
        
    }
    
    
    public function createTrusteeNotification($trusteeRow){
        $this->db = Zend_Db_Table::getDefaultAdapter();
		$translate = Zend_Registry::get('Zend_Translate'); // initialize database adapter 
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $userTable = new Application_Model_DbTable_Users();
        $notifiTypeTable = new Application_Model_DbTable_NotificationType();
        
        if($trusteeRow && $trusteeRow->userId && $trusteeRow->trusteeId){
            $userRow = $userTable->getRowById($trusteeRow->userId);
            $trusteeUserRow = $userTable->getRowById($trusteeRow->trusteeId);
            
            $select = $userNotificationTable->select()
                                    ->where('user_id =?',$trusteeRow->trusteeId)
                                    ->where('trustee_row_id =?',$trusteeRow->id)
                                    ->where('status =?',"1");

            $oldNotificationRow = $userNotificationTable->fetchRow($select);

            
            switch ($trusteeRow->status){
                case 0:
                    $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::INCOMING_TRUSTEE_REQUEST);

                    $data = array(
                        'user_id'           => $trusteeRow->trusteeId,
                        'from_user_id'      => $trusteeRow->userId,
                        'trustee_row_id'    => $trusteeRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $userRow->userNickName.$translate->translate('notifications_txt_7'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'status'            => "1"
                    );

                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();
                    break;
                case 1:

                    $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::ACCEPT_TRUSTEE_REQUEST);
                    $this->db->delete('user_notifications',"user_id =".$trusteeRow->trusteeId." && from_user_id=".$trusteeRow->userId." && notf_type_id=3");
                    $data = array(
                        'user_id'           => $trusteeRow->userId,
                        'from_user_id'      => $trusteeRow->trusteeId,
                        'trustee_row_id'    => $trusteeRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $trusteeUserRow->userNickName.$translate->translate('notifications_txt_8'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'is_read'           => "0",
                        'status'            => "1"
                    );

                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();
                    $translate = Zend_Registry::get('Zend_Translate');
                    $data = array(
                        'user_id'           => $trusteeRow->trusteeId,
                        'from_user_id'      => $trusteeRow->userId,
                        'trustee_row_id'    => $trusteeRow->id,
                        'notf_type_id'      => $notifiTypeRow->id,
                        'message'           => $translate->translate('notifications_txt_1') . $userRow->userNickName . $translate->translate('notifications_txt_2'),
                        'creation_date'     => date("Y-m-d H:i:s"),
                        'modification_date' => date("Y-m-d H:i:s"),
                        'is_read'           => "1",
                        'status'            => "1"
                    );

                    $userNotificationRow = $userNotificationTable->createRow($data);
                    $userNotificationRow->save();

                    break;
                    case 2:
                        $notifiTypeRow = $notifiTypeTable->getRowByType(Application_Model_DbTable_NotificationType::INCOMING_TRUSTEE_REQUEST);
                      
                        $this->db->delete('user_notifications',"user_id =".$trusteeRow->trusteeId." && from_user_id=".$trusteeRow->userId." && notf_type_id=3");

                        $data = array(
                            'user_id'           => $trusteeRow->trusteeId,
                            'from_user_id'      => $trusteeRow->userId,
                            'trustee_row_id'    => $trusteeRow->id,
                            'notf_type_id'      => $notifiTypeRow->id,
                            'message'           => $userRow->userNickName.$translate->translate('notifications_txt_9'),
                            'creation_date'     => date("Y-m-d H:i:s"),
                            'modification_date' => date("Y-m-d H:i:s"),
                            'status'            => 2
                        );

                        $userNotificationRow = $userNotificationTable->createRow($data);
                        $userNotificationRow->save();
                    break;
            }

        }
        
    }
    
    
    public function createWANotification($user_id,$wa_id,$wa_event_detail_id,$notification_type){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $userTable = new Application_Model_DbTable_Users();
        $notifiTypeTable = new Application_Model_DbTable_NotificationType();
        
        
        if($notifTypeRow = $notifiTypeTable->getRowByType($type)){
            
            if($userNotificationRow = $this->getRowByWaIdAndUserId($user_id, $wa_id, $notf_type_id)){
                
            }else{
                $data = array(
                    'user_id'           => $user_id,
                    'from_user_id'      => $user_id,
                    'wa_id'             => $wa_id,
                    'wa_detail_id'      => $wa_event_detail_id,
                    'notf_type_id'      => $notifTypeRow->id,
                    'message'           => $message,
                    'creation_date'     => date("Y-m-d H:i:s"),
                    'modification_date' => date("Y-m-d H:i:s"),
                    'message'           => $message
                );

                $userNotificationRow = $userNotificationTable->createRow($data);
                $userNotificationRow->save();
            }
        }
        
    }
    
    public function createTestamentNotification($data,$notification_type)
    {
        $userTable             = new Application_Model_DbTable_Users();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $notifiTypeTable       = new Application_Model_DbTable_NotificationType();
        
        if($notifTypeRow = $notifiTypeTable->getRowByType($notification_type))
        { 
            $result         = array(
                                'user_id'           => $data['user_id'],
                                'from_user_id'      => $data['from_user_id'],                                
                                'notf_type_id'      => $notifTypeRow->id,
                                'message'           => $data['message'],
                                'creation_date'     => date('Y-m-d H:i:s'),
                                'modification_date' => date('Y-m-d H:i:s')
                            );
          
            $userNotificationRow = $userNotificationTable->createRow($result);
            $userNotificationRow->save();
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getRowByWaIdAndUserId($user_id,$wa_id,$notf_type_id){
        
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        
        $select = $userNotificationTable->select()
                                ->where('user_id =?',$user_id)
                                ->where('wa_id =?',$wa_id)
                                ->where('notf_type_id =?',$notf_type_id);
        
        return $userNotificationTable->fetchRow($select);
        
    }
    
    public function createNotification($sender_id,$receiver_id,$message,$type){
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
       
        $data = array(
                'user_id'           => $receiver_id,
                'from_user_id'      => $sender_id,
                'notf_type_id'      => $type,
                'message'           => $message,
                'creation_date'     => date("Y-m-d H:i:s"),
                'modification_date' => date("Y-m-d H:i:s")
            );

        $userNotificationRow = $userNotificationTable->createRow($data);
        $userNotificationRow->save();
    }
    
}
