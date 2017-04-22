<?php
class Application_Model_DbTable_SecretGroups extends Zend_Db_Table_Abstract{
    
    protected $_name = 'secretGroup';
    protected $_id = 'secGroupId';
    
    public function getRowById($id){
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        
        $select = $secGroupTable->select()
                             ->where('secGroupId =?',$id);
        
        return $secGroupTable->fetchRow($select);
    }
    
    public function getRowByUserIdAndGroupName($userId,$groupName){
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        
        $select = $secGroupTable->select()
                             ->where('adminId =?',$userId)
                             ->where('groupName =?',$groupName);   
                                
        
        return $secGroupTable->fetchRow($select);
    }
    
    public function userGroups($userId,$returnSelect = false){
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $select = $secGroupTable->select()->setIntegrityCheck(false)
                                        ->from('secretGroup',array('*'))
                                        ->joinInner('secGroupMembers', 'secretGroup.secGroupId=secGroupMembers.secGroupId',array('member_status' => 'status','memberPassword'))
                                        ->where('secGroupMembers.memberId =?',$userId)
                                        ->where('(secGroupMembers.status =?',"0")
                                        ->orWhere('secGroupMembers.status =?)',"1")
                                        ->where('secretGroup.status =?',"1");  
        
        if($returnSelect){
            return $select;
        }
    
        return $secGroupTable->fetchAll($select);
        
    }
    
    public function deletedSecretGroups($userId,$last_request_time=false){
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        
        $select = $secGroupTable->select()
                                ->where('status =?',"2");
        
        if($last_request_time){
            $select->where('modifyDate >?',$last_request_time);
        }
        
        return $secGroupTable->fetchAll($select);
        
    }
     /*
     * 
     */
    public function getGroupDetails($group_id){
           $select = $this->select()->setIntegrityCheck(false)
                            ->from('secretGroup', array('*'))
                            ->where('secretGroup.secGroupId=?',$group_id);
           $result =  $this->fetchRow($select);
           return $result;        
    }
    
      /*
     * 
     */
    public function getGroupAllDetails($group_id,$userId){
           $select = $this->select()->setIntegrityCheck(false)
                            ->from('secretGroup', array('*'))
                            ->join("secGroupMembers",'secGroupMembers.secGroupId=secretGroup.secGroupId',array('secretName','secretImage','memberPassword','memberId','messageDeletHours','messageDeleteMinutes','messageDeleteSecods'))
                            ->where('secretGroup.secGroupId=?',$group_id)
                            ->where('secGroupMembers.memberId=?',$userId);
           $result =  $this->fetchRow($select);
           return $result;  
    }
    
}
?>
