<?php

class Application_Model_DbTable_Groups extends Zend_Db_Table_Abstract{
    
    protected $_name = 'groups';
    protected $_id = 'groupId';
    
    public function getRowById($id){
        $groupTable = new Application_Model_DbTable_Groups();
        
        $select = $groupTable->select()
                             ->where('(groupId =?',$id)
                             ->orWhere('quick_blox_group_id =?)',$id);
        
        return $groupTable->fetchRow($select);
    }
    
    public function getRowByUserIdAndGroupName($userId,$groupName){
        $groupTable = new Application_Model_DbTable_Groups();
        
        $select = $groupTable->select()
                             ->where('adminId =?',$userId)
                             ->where('groupName =?',$groupName);   
                                
        
        return $groupTable->fetchRow($select);
    }
    
    /**
     *  list of all groups created by users
     */
    
    public function groupsCreatedByUser($userId){
        $groupTable = new Application_Model_DbTable_Groups();
        $select = $groupTable->select()
                            ->where('adminId =?',$userId);
    
        $groupsRowset = $groupTable->fetchAll($select);
        
        return $groupsRowset;
        
    }
    
     public function userGroups($userId,$returnSelect = false){
        $groupTable = new Application_Model_DbTable_Groups();
        $select = $groupTable->select()->setIntegrityCheck(false)
                                        ->from('groups',array('*'))
                                        ->joinInner('groupMembers', 'groups.groupId=groupMembers.groupId',array('join_status'))
                                        ->where('groupMembers.memberId =?',$userId)
                                        ->where('groupMembers.status =?',"1")    
                                        ->where('groups.status =?',"1");  
        
        if($returnSelect){
            return $select;
        }
    
        $groupsRowset = $groupTable->fetchAll($select);
        
        return $groupsRowset;
        
    }
    
    public function deleteGroupsByAdminId($userId){
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupTable->select()
                              ->where('adminId =?',$userId);
        
        $groupRowset = $groupTable->fetchAll($select);
        
        foreach($groupRowset as $groupRow){
            
            $groupMemberTable->deleteGroupMembers($groupRow->groupId);
            $groupRow->delete();
        }
    }
    
    /**
     * getting deleted groups listing 
     */
    
    public function deletedGroupsList($last_request_time){
        $groupTable = new Application_Model_DbTable_Groups();
        
        $select = $groupTable->select()
                        ->from('groups',array('groupId'))
                        ->where('status =?','2')
                        ->where('modifyDate >?',$last_request_time);
        
        return $groupTable->fetchAll($select);
                
    }
    
    /**
     * list of common groups
     */
    
    public function commonGroupsId($userId,$friendId){
        $groupMembersTable = new Application_Model_DbTable_GroupMembers();
        $condition = "(".$userId.",".$friendId.")";
        $select = $groupMembersTable->select()
                        ->from('groupMembers',array('quick_blox_group_id','memberId'))
                        ->group('quick_blox_group_id')
                        ->having("memberId in $condition");
        
        $groupMembersRowset = $groupMembersTable->fetchAll($select);
        
        $groupsIds = array();
        
        foreach ($groupMembersRowset as $groupMembersRow){
            $groupsIds[] = $groupMembersRow->quick_blox_group_id;
        }
        
        return $groupsIds;
        
    }
    
    public function commonGroups($userId,$friendId){
        $groupTable = new Application_Model_DbTable_Groups();
        
        $commonGroupIds = $groupTable->commonGroupsId($userId,$friendId);
        if(count($commonGroupIds)){
            foreach($commonGroupIds as $ids){
                $condition .= ",'".$ids."'";
            }
            $condition = "(".substr($condition,1).")";
            $select = $groupTable->select()->setIntegrityCheck(false)
                            ->from('groups', array('groupId','groupName','groupImage','status','quick_blox_group_id'))
                            ->joinInner("groupMembers", "groups.quick_blox_group_id = groupMembers.quick_blox_group_id",array('count' => 'count(*)','status'))
                            ->group('groups.quick_blox_group_id')
                            ->having("(groups.status = '1') AND (groupMembers.status = '1') AND (groups.quick_blox_group_id in $condition)");
           
           //echo $select;die();
           $commonGroupsRowset =  $groupTable->fetchAll($select);
           return $commonGroupsRowset;        
            
        }
        
        return array();
    }
    /*
     * 
     */
    public function getGroupDetails($group_id){
           $select = $this->select()->setIntegrityCheck(false)
                            ->from('groups', array('*'))
                            ->where('groups.groupId=?',$group_id);
           $result =  $this->fetchRow($select);
           return $result;        
    }
}

?>
