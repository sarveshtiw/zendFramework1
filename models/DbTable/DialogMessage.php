<?php

class Application_Model_DbTable_DialogMessageRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_DialogMessage extends Zend_Db_Table_Abstract {

    protected $_name = 'dialog_message';
    protected $_id = 'id';
    protected $_rowClass = "Application_Model_DbTable_DialogMessageRow";
    public function __construct() {
        parent::__construct();
    }

    public function getRowByChatDialog($id) {
       $select = $this->select()
                ->where('chat_dialog_id =?', $id)
                ->order('date_sent DESC')
                 ->limit(1);

        $result = $this->fetchRow($select);
        if($result)
            return $result->date_sent;
        else
            return false;
    }
   public function changeLanguage($message,$id){
     $this->update(array("translated"=>$message), "message_id='{$id}'");  
    }
    
    public function updateDialogLanguage($arrayData,$id){
     $this->update($arrayData, "chat_dialog_id='{$id}'");  
    }
    public function geChatHistory($sender_id,$recipient_id,$type,$id=null){
        $chat_dialog_id= ($type==2)?$recipient_id:$this->geDialogId($sender_id,$recipient_id,$type);
        if($chat_dialog_id){
        $select = $this->select()->from($this->info('name'),array('_id as id','content_type','created_at','chat_dialog_id','thumbDataStr','sender_id','nick_name','recipient_id','message','translated','time','is_read','adult_read','sender_read','message_id','src_lang','language','date_sent','video_thumbnail_url','attach_file_server_url'));
                   $select->where('chat_dialog_id=?',$chat_dialog_id);
                   $select->where('notification_type!=?',1);
                   ($id)?$select->where('_id<?',$id):"";
       
        $select->order('date_sent DESC');
        $select->limit(10);
        $result = $this->fetchAll($select)->toArray();
        krsort($result);
        //$this->update(array('is_read'=>2), "chat_dialog_id='{$chat_dialog_id}' AND sender_id='{$recipient_id}'");
        return (count($result)>0)?$result:"";
        }
        else{
            return false;
        }
    }
    
    public function geDialogId($sender_id,$recipient_id,$type){
        $select = $this->select()->from("dialog_message",array('chat_dialog_id'));
        $select->where("(`sender_id`=$sender_id AND recipient_id=$recipient_id) OR (`sender_id`=$recipient_id AND recipient_id=$sender_id)");
        $select->where('type=?',$type);
        $select->limit(1);
        $result = $this->fetchRow($select);
        if($result)
        return $result->chat_dialog_id;
        else
            return FALSE;
    }

    public function getSecChatHistory($userId,$sender_id, $dialog_id, $groupId, $password = null) {
            $objGroupMember = new Application_Model_DbTable_SecGroupMembers();
            $memberStatus = $objGroupMember->groupLogin($userId, $groupId, $password);
            if($memberStatus){
                $select = $this->select()->where('chat_dialog_id=?', $dialog_id);
                $select->order('date_sent DESC');
                $select->limit(30);
                $result = $this->fetchAll($select)->toArray();
                return $result;
            }else{
                return 2;
            }
    }
    /**
     * 
     * @param this function will check 2 wa users are friend or not first we will check request send by login user and 
     * 
     * @param type $userId and $friendId
     * @return type friend row
     * 
     */
    public function getRowByUserIdAndFriendId($userId, $friendId) {
        $friendTable = new Application_Model_DbTable_Friends();

        $select = $friendTable->select()
                ->where('(userId =?', $userId)
                ->where('friendId =?)', $friendId)
                ->orWhere('(userId =?', $friendId)
                ->where('friendId =?)', $userId);
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;
    }

    public function saveData($response) {
        $card_id = $response['id'];
        $payer_id = $response['payer_id'];
        $card_data = array('card_type' => $response['type'],
            'card_number' => $response['number'],
            'expire_month' => $response['expire_month'],
            'expire_year' => $response['expire_year'],
            'first_name' => $response['first_name'],
            'last_name' => $response['last_name'],
            'valid_until' => $response['valid_until'],
            'ccv2' => $response['cvv2_code'],
        );
        $arrData = array('user_id' => $response['user_id'],
            'card_id' => $card_id,
            'payer_id' => $payer_id,
            'created_at' => date("Y-m-d H:i:s"),
            'card_data' => Zend_Json::encode($card_data),
        );
        
        $this->insert($arrData);
    }

    public function updateData($response) {
        $card_id = $response['id'];
        $payer_id = $response['payer_id'];
        $card_data = array('card_type' => $response['type'], 'card_number' => $response['number'],
            'expire_month' => $response['expire_month'], 'expire_year' => $response['expire_year'],
            'first_name' => $response['first_name'], 'last_name' => $response['last_name'],
            'valid_until' => $response['valid_until'], 'ccv2' => $response['cvv2_code']);
        $arrData = array('user_id' => $response['user_id'], 'card_id' => $card_id,
            'payer_id' => $payer_id, 'created_at' => date("Y-m-d H:i:s"),
            'card_data' => Zend_Json::encode($card_data),
        );
        $this->update($arrData,"user_id='{$response['user_id']}'");
    }

    public function getByUserId($user_id) {
        $select = $this->select()->from($this->info('name'))->where("user_id='{$user_id}'");
        $result = $this->fetchRow($select);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    public function getUnreadMessageCount($sender_id=0,$recipient_id=0) {
        $select = $this->select()->from($this->info('name'),array("count(*) as total"));
        $select->where('sender_id=?',$sender_id);
        $select->where('recipient_id=?',$recipient_id);
       echo $select->where('is_read!=?',2);
        $result = $this->fetchRow($select);
        if ($result) {
            return $result['total'];
        } else {
            return false;
        }
    }

}

?>
