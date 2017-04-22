<?php
class Application_Model_DbTable_GroupMembers extends Zend_Db_Table_Abstract{
    
    protected $_name = 'groupMembers';
    protected $_id = 'id';
    
    const MEMBER_INACTIVE = '0';   // pending friend request
    const MEMBER_ACTIVE = '1';
    const MEMBER_DELETED = '2';
    const MEMBER_BLOCK = '3';
    
    public function getRowById($id){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupMemberTable->select()
                             ->where('id =?',$id);
        
        return $groupMemberTable->fetchRow($select);
    }
    
    
    public function getRowByMemberIdAndQuickBloxGroupId($memberId,$quick_blox_group_id){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupMemberTable->select()
                            ->where('memberId =?',$memberId)
                            ->where('quick_blox_group_id =?',$quick_blox_group_id);
        
        return $groupMemberTable->fetchRow($select);
    }
    
    
    
    
    public function getGroupMembers($groupId,$active = false){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupMemberTable->select()->setIntegrityCheck(false)
                                        ->from('groupMembers',array("*"))
                                        ->joinInner('users', "groupMembers.memberId = users.userId",array('*'))
                                        ->where('users.userStatus =?',"1")
                                        ->where('groupMembers.quick_blox_group_id =?',$groupId);
        if($active){
            $select->where('groupMembers.status=?',"1");
        }
        
        return $groupMemberTable->fetchAll($select);
    }
    
    
    
    public function deleteUserFromGroups($userId){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $groupMemberTable->delete("memberId = ".$userId);
    }
    
    public function deleteGroupMembers($groupId){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $groupMemberTable->delete("groupId = ".$groupId);
    }
    
    public function deletedMembersList($groupId,$last_request_time=false){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupMemberTable->select()->setIntegrityCheck(false)
                                ->from('groupMembers',array("*"))
                                ->joinInner('users', "groupMembers.memberId = users.userId",array('*'))
                                ->where('groupMembers.quick_blox_group_id =?',$groupId)
                                ->where('groupMembers.status=?',"2");
                                
        if($last_request_time){
            $select->where('groupMembers.modifyDate >?',$last_request_time);
        }
        return $groupMemberTable->fetchAll($select);
    }
    
    public function newMembersList($groupId,$last_request_time=false){
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        
        $select = $groupMemberTable->select()->setIntegrityCheck(false)
                                ->from('groupMembers',array("*"))
                                ->joinInner('users', "groupMembers.memberId = users.userId",array('*'))
                                ->where('groupMembers.quick_blox_group_id =?',$groupId)
                                ->where('users.userStatus =?',"1")
                                ->where('groupMembers.status=?',"1");
                                
        if($last_request_time){
            $select->where('groupMembers.modifyDate >?',$last_request_time);
        }
        return $groupMemberTable->fetchAll($select);
    }
    
    /**
     * getting one active member from groups 
     */
    
    public function getOneActiveMember($groupId){
       $groupMemberTable = new Application_Model_DbTable_GroupMembers();
       $select = $groupMemberTable->select()
                                ->setIntegrityCheck(false)
                                ->from('groupMembers',array('*'))
                                ->joinInner('users', "groupMembers.memberId = users.userId",array("*"))
                                ->where("groupMembers.status=?","1")
                                ->where("users.userStatus =?","1")
                                ->where("groupMembers.quick_blox_group_id =?",$groupId);
       
       return $groupMemberTable->fetchRow($select);
       
    }
    
    public function groupLeftByUser($userId,$last_request_time=false){
       $groupMemberTable = new Application_Model_DbTable_GroupMembers();
       $select = $groupMemberTable->select()
                                ->from('groupMembers',array('groupId'))
                                ->where('memberId =?',$userId)
                                ->where('status =?','2');
       if($last_request_time){
           $select->where('modifyDate >?',$last_request_time);
       }
       
       return $groupMemberTable->fetchAll($select);
    }
    
    public function getMyFriendsGroupMember($user_id,$group_id){
        
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        
        
        $select1 = $userTable->select()->setIntegrityCheck(false)
                                    ->from('users',array('userId'))
                                    ->joinInner('friends', 'F1.friendId = users.userId' , array())    
                                    ->where('users.userStatus =?',"1")
                                    ->where("friends.userId =?",$user_id);
                                    
                                    
        echo $select;exit;
        
        $select = $friendTable->select()->setIntegrityCheck()
                                    ->from('friends',array())
                                    ->joinInner(array('users1'=> 'users'), 'friends.userId = users1.userId',array('userId'))    
                                    ->joinInner(array('users2'=> 'users'), 'friends.friendId = users2.userId',array('userId'));
                                        
                
        $select = $groupMemberTable->select()->setIntegrityCheck(false)
                                ->from('groupMembers',array('*'))
                                ->joinInner('friends', '', $cols);
    }
    
    function updateData($arrData,$where){
        $this->update($arrData,$where);
    }
    
    function getMemberIds($groupId){
            $select = $this->select()->setIntegrityCheck(false)
                                        ->from('groupMembers',array("GROUP_CONCAT(memberId) as members"))
                                        ->joinInner('users', "groupMembers.memberId = users.userId",array(''))
                                        ->where('users.userStatus =?',"1")
                                        ->where('groupMembers.groupId =?',$groupId);
            $select->where('groupMembers.status=?',"1");
            $result = $this->fetchAll($select);
            $data = ($result)?explode(",",$result[0]->members):"";
            return $data;
    }
    
}

?>
