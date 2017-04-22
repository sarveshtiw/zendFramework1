<?php
class Application_Model_DbTable_BlockUsersRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_BlockUsers extends Zend_Db_Table_Abstract{
    protected $_name = 'blockUsers';
    protected $_id = 'id';
    protected $_rowClass = "Application_Model_DbTable_BlockUsersRow";
    
    const BLOCK_STATUS_INACTIVE = 0;
    const BLOCK_STATUS_ACTIVE = 1;

    public function getRowById($id){
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        
        $select = $blockUsersTable->select()
                            ->where('id =?',$id);
        
        $blockUserRow = $blockUsersTable->fetchRow($select);
        return $blockUserRow;
    }
    
    public function blockUsers($userId,$is_active=false){
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        
        $select = $blockUsersTable->select()
                            ->where('userId=?',$userId);
        
        if($is_active){
            $select->where('status =?',"1");
        }
        
        return $blockUsersTable->fetchAll($select);
    }
    
    public function getRowByUserIdAndBlockUserId($userId,$blockUserId){
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        
        $select = $blockUsersTable->select()
                                ->where('userId =?',$userId)
                                ->where('blockUserId =?',$blockUserId);
        
        return $blockUsersTable->fetchRow($select);
        
    }
    /*
     * return 0 no block, 1->request user block,2->requestest user block by other,3->both block each other
     */
    public function getBlockRelation($userId,$blockUserId){
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
        $select = $blockUsersTable->select()
                                ->where('(userId =?',$userId)
                                ->where('blockUserId =?)',$blockUserId)
                                ->orWhere('(userId =?',$blockUserId)
                                ->where('blockUserId =?)',$userId);
        
        $result=$blockUsersTable->fetchAll($select)->toArray();
        if($result){
            if(count($result)>1){
                return 3;
            }else{
                return ($result[0]['blockUserId']==$userId)?1:2;
            }
        }else{
            return 0;
        }
    }
    
     public function getBlockRow($userId,$blockUserId){
        $blockUsersTable = new Application_Model_DbTable_BlockUsers();
         $select = $blockUsersTable->select()
                                ->where('(userId =?',$userId)
                                ->where('blockUserId =?)',$blockUserId);
        return $blockUsersTable->fetchRow($select);
    }
    
}


?>
