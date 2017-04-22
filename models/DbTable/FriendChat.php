<?php
class Application_Model_DbTable_FriendChat extends Zend_Db_Table_Abstract{
    
    protected $_name = 'friendChat';
    protected $_id = 'chatId';
    
    public function getRowById($id){
      $friendChatTable = new Application_Model_DbTable_FriendChat();
      
      $select = $friendChatTable->select()
                        ->where('chatId =?',$id);
      
      return $friendChatTable->fetchRow($select);
    }
    
    public function getChatByUserId($userId,$otherUserId,$lastSeenTime, $isActive=false){
      $friendChatTable = new Application_Model_DbTable_FriendChat();
      
      $select = $friendChatTable->select()
                        ->where('(chatUserId =?',$userId)
                        ->where('otherUserId =?',$otherUserId)
                        ->orWhere('charUserId =?',$otherUserId)
                        ->where('otherUserId =?)',$userId);
      
      if($lastSeenTime){
          $select->where('creationDate >?',$lastSeenTime);
      }
      
      if($isActive){
          $select->where('isDeleted =?','0');
      }
     
     return $friendChatTable->fetchAll($select); 
    }
    
    
}

?>
