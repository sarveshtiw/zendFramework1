<?php

class Application_Model_DbTable_SecretGroupChat extends Zend_Db_Table_Abstract{
    
    protected $_name = 'secGroupChat';
    protected $_id = 'secGroupChatId';
    
    public function getRowById($id){
        $secGrpChatTable = new Application_Model_DbTable_SecretGroupChat();
        
        $select = $secGrpChatTable->select()
                            ->where('secGroupChatId =?',$id);
    
        return $secGrpChatTable->fetchRow($id);
    }
    
    public function getChatByUserId(){
        $secGrpChatTable = new Application_Model_DbTable_SecretGroupChat();
        
        $select = $secGrpChatTable->select()->setIntegrityCheck(false)
                                    ->from('secGroupChat',array('*'))
                                    ->joinLeft('secGroupUsers', 'secGroupUsers.userId = secGroupChat.userId',array('secGroupId','alternateName','alternateImage','status'));    
         
       return $secGrpChatTable->fetchAll($select); 
    }
}



?>
