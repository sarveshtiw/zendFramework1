<?php
class Application_Model_DbTable_CustomizeRemoveFriends extends Zend_Db_Table_Abstract{
    
    protected $_name = 'customize_remove_frnds';
    protected $_id = 'id';
    
    public function getRowById($id){
      $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
      
      $select = $customizeRemoveFriendsTable->select()
                        ->where('id =?',$id);
      
      return $customizeRemoveFriendsTable->fetchRow($select);
    }
    
    public function getRowByUserIdAndFriendId($user_id, $friend_id){
      $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
      
      $select = $customizeRemoveFriendsTable->select()
                        ->where('user_id =?',$user_id)
                        ->where('friend_id =?',$friend_id);
      
      return $customizeRemoveFriendsTable->fetchRow($select);
    }
   
    /**
     *  return list of friends id who cant seen my last seen time
     */
    
      public function getCustomizeFriends($user_id){
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        
        $select = $customizeRemoveFriendsTable->select()
                                              ->where('user_id =?',$user_id);
                                        
        $cutomizeRemoveFriendsRowset = $customizeRemoveFriendsTable->fetchAll($select);
        
        $list = array();
        
        foreach ($cutomizeRemoveFriendsRowset as $cutomFriendRow){
            $list[] = $cutomFriendRow->friend_id;
        }
        
        return $list;
    }
    
    /**
     *  add friends cant see last status and remove friends can not be seen last status
     */
    
    public function updateCustomizeFriends($user_id, $addFriendsSet, $deletedFriendsSet){
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        
        $friendTable = new Application_Model_DbTable_Friends();
        
        foreach($addFriendsSet as $addFriendId){
            if($customizeFriendRow = $customizeRemoveFriendsTable->getRowByUserIdAndFriendId($user_id, $addFriendId)){
                $customizeFriendRow->delete();
            }
        }
        
        foreach ($deletedFriendsSet as $deleteFriendId){

            $customizeFriendRow = $customizeRemoveFriendsTable->getRowByUserIdAndFriendId($user_id, $deleteFriendId);

            if(($friendRow = $friendTable->getRowByUserIdAndFriendId($user_id, $deleteFriendId)) && ($friendRow->status == "1")){
                if(!$customizeFriendRow){
                    $data = array(
                        'user_id'   => $user_id,
                        'friend_id' => $deleteFriendId,
                    );

                    $customizeFriendRow = $customizeRemoveFriendsTable->createRow($data);
                    $customizeFriendRow->save();
                }
            }else{
                if($customizeFriendRow){
                    $customizeFriendRow->delete();
                }
            }
        }
    }
    
    public function deleteAllRowsbyUserId($userId){
        $customizeRemoveFriendsTable = new Application_Model_DbTable_CustomizeRemoveFriends();
        $customizeRemoveFriendsTable->delete(array('user_id =?'=>$userId));
    }
    
}

?>
