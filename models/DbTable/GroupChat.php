<?php
class Application_Model_DbTable_GroupChat extends Zend_Db_Table_Abstract{
    
    protected $_name = 'groupChat';
    protected $_id = 'groupChatId';
    
    public function getRowById($id){
        $groupChatTable = new Application_Model_DbTable_GroupChat();
        
        $select = $groupChatTable->select()
                        ->where('groupChatId =?',$id);
    
        return $groupChatTable->fetchRow($select);
    }
    
    public function getChatByUserId($groupId){
        $groupChatTable = new Application_Model_DbTable_GroupChat();
        
        $select = $groupChatTable->select()->setIntegrityCheck(false)
                            ->from('groupChat',array('*'))
                            ->joinLeft('users', 'users.userId = groupChat.userId',array('*'))
                            ->where('groupId =?',$groupId);
        
        return $groupChatTable->fetchAll($select);
    
        
    }
    
    
}
?>
