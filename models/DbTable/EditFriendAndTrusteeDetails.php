<?php

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Application_Model_DbTable_EditFriendAndTrusteeDetails extends Zend_Db_Table_Abstract{
    
    protected $_name = 'editFriendTrusteeDetails';
    protected $_id = 'id';
    
    public function getRowById($id){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $select = $editFriendTrusteeTable->select()
                             ->where('id =?',$id);
        
        return $editFriendTrusteeTable->fetchRow($select);
    }
    
    public function getRowByUserIdAndOtherUserId($userId,$otherUserId){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $select = $editFriendTrusteeTable->select()
                            ->where('userId =?',$userId)
                            ->where('otherUserId =?',$otherUserId);

        return $editFriendTrusteeTable->fetchRow($select);
    }
    
    public function editDetails($data){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        $userId = $data['userId'];
        $otherUserId = $data['otherUserId'];
        $name = trim($data['name']);
        $hideProfile = $data['hideProfile'];
        
        if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId, $otherUserId)){
            if($name){
                $editFriendTrusteeRow->name = $name;
            }
            
            $editFriendTrusteeRow->hideProfile = $hideProfile;
            $editFriendTrusteeRow->modifyDate = date("Y-m-d H:i:s");
            $editFriendTrusteeRow->save();
            
        }else{
            $data = array_merge($data,array(
                'modifyDate' => date("Y-m-d H:i:s")
            ));
            
            $editFriendTrusteeRow = $editFriendTrusteeTable->createRow($data);
            $editFriendTrusteeRow->save();
        }
        
    }
    
    public function deleteEditName($userId,$otherUserId){
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        
        if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId, $otherUserId)){
            $editFriendTrusteeRow->delete();
        }
        
        if($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($otherUserId, $userId)){
            $editFriendTrusteeRow->delete();
        }
        
    }
    
}
?>
