<?php
class Application_Model_DbTable_SecGroupMembers extends Zend_Db_Table_Abstract{
    
    protected $_name = 'secGroupMembers';
    protected $_id = 'id';
    
    const MEMBER_INACTIVE = '0';   // pending friend request
    const MEMBER_ACTIVE = '1';
    const MEMBER_DELETED = '2';
    const MEMBER_BLOCK = '3';
    
    public function getRowById($id){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()
                             ->where('id =?',$id);
        
        return $secGroupMembersTable->fetchRow($select);
    }
    
    public function getRowByMemberIdAndGroupId($memberId,$groupId){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()
                            ->where('memberId =?',$memberId)
                            ->where('secGroupId =?',$groupId);
        
        return $secGroupMembersTable->fetchRow($select);
    }
    
    public function deletedMembersList($groupId,$last_request_time=false){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()->setIntegrityCheck(false)
                                ->from('secGroupMembers',array("*"))
                                ->joinInner('users', "secGroupMembers.memberId = users.userId",array('*'))
                                ->where('secGroupMembers.secGroupId =?',$groupId)
                                ->where('secGroupMembers.status=?',"2");
                                
        if($last_request_time){
            $select->where('secGroupMembers.modifyDate >?',$last_request_time);
        }
        return $secGroupMembersTable->fetchAll($select);
    }
    
    public function newMembersList($groupId,$last_request_time=false){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()->setIntegrityCheck(false)
                                ->from('secGroupMembers',array("*"))
                                ->joinInner('users', "secGroupMembers.memberId = users.userId",array('*'))
                                ->where('secGroupMembers.secGroupId =?',$groupId)
                                ->where('secGroupMembers.status=?',"1");
                                
        if($last_request_time){
            $select->where('secGroupMembers.modifyDate >?',$last_request_time);
        }
        return $secGroupMembersTable->fetchAll($select);
    }
    
    /**
     * getting one active member from groups 
     */
    
    public function getOneActiveMember($secretGroupId){
       $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
       
       $select = $secGroupMembersTable->select()
                                ->setIntegrityCheck(false)
                                ->from('secGroupMembers',array('*'))
                                ->joinInner('users', "secGroupMembers.memberId = users.userId",array("*"))
                                ->where("secGroupMembers.status=?","1")
                                ->where("users.userStatus =?","1")
                                ->where("secGroupMembers.secGroupId =?",$secretGroupId);
       
       return $secGroupMembersTable->fetchRow($select);
       
    }
    
    public function getGroupMembers($secretGroupId,$active = false){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()->setIntegrityCheck(false)
                                        ->from('secGroupMembers',array("*"))
                                        ->joinInner('users', "secGroupMembers.memberId = users.userId",array("*"))
                                        ->where('users.userStatus =?',"1")
                                        ->where("secGroupMembers.secGroupId =?",$secretGroupId);
        if($active){
            $select->where('secGroupMembers.status=?',"1");
        }
        
        return $secGroupMembersTable->fetchAll($select);
    }
    
    public function deleteMembers($secGroupId){
        $secGroupMembersTable = new Application_Model_DbTable_SecGroupMembers();
        
        $select = $secGroupMembersTable->select()
                                ->where('secGroupId =?',$secGroupId);
        
        $secGrpMemberRowset = $secGroupMembersTable->fetchAll($select);
        
        foreach($secGrpMemberRowset as $secGrpMemberRow){
            $secGrpMemberRow->status = "2";
            $secGrpMemberRow->modifyDate = date("Y-m-d H:i:s");
            $secGrpMemberRow->save();
        }
    }/*
     * used for match user password for secgroup member for chat or get history
     */
     function groupLogin($userId=0,$groupId=0,$password=null){
            $select = $this->select()->setIntegrityCheck(false)
                                        ->from('secGroupMembers',array("memberId"))
                                        ->joinInner('users', "secGroupMembers.memberId = users.userId",array(''))
                                        ->where('users.userStatus =?',"1")
                                        ->where('secGroupMembers.memberPassword =?',md5($password))
                                        ->where('secGroupMembers.secGroupId =?',$groupId);
            $select->where('secGroupMembers.memberId =?',$userId);
            $select->where('secGroupMembers.status!=?',"2");
            $result = $this->fetchRow($select);
            $data = ($result)?true:false;
            return $data;
    }
    function getMemberIds($groupId){
            $select = $this->select()->setIntegrityCheck(false)
                                        ->from('secGroupMembers',array("GROUP_CONCAT(memberId) as members"))
                                        ->joinInner('users', "secGroupMembers.memberId = users.userId",array(''))
                                        ->where('users.userStatus =?',"1")
                                        ->where('secGroupMembers.secGroupId =?',$groupId);
            $select->where('secGroupMembers.status!=?',"2");
            $result = $this->fetchAll($select);
            $data = ($result)?explode(",",$result[0]->members):"";
            return $data;
    }
     function updateData($arrData,$where){
        $this->update($arrData,$where);
    }
}

?>
