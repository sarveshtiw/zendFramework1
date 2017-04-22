<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Application_Model_DbTable_UserSetting extends Zend_Db_Table_Abstract{
    protected $_name = 'usrSetting';
    protected $_id = 'usrSettId';
    
    const MEDIA_TYPE_WEB = 'web';
    const MEDIA_TYPE_APP = 'app';
    
    
    public function getRowByUserIdAndDeviceId($userId,$deviceId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                        ->where('userId =?',$userId)
                        ->where('userDeviceId =?',$deviceId);    
        
        return $userSettingTable->fetchRow($select);
    }
    
     public function getRowByUserId($userId){
         
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $select = $userSettingTable->select()
                        ->where('userId =?',$userId);
        return $userSettingTable->fetchRow($select);
    }
    
    public function isUserOnline($userId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                                    ->where('userId =?',$userId)
                                    ->where('isOnline =?',"1");
        
        return ($userSettingTable->fetchRow($select))?"1":"0";
    }
    
    public function logoutWithAllDevice($userId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                            ->where('userId = ?',$userId)
                            ->where("mediaType=?","app");
        
        $userLoginDeviceRowset = $userSettingTable->fetchAll($select);
        
        foreach($userLoginDeviceRowset as $userDeviceRow){
            $userDeviceRow->delete();
        }
    }
    
    public function userLoginDeviceRowset($userId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                            ->where('userId = ?',$userId);
        
        return $userSettingTable->fetchAll($select);
    }
    
    public function deleteRowsByUserIdAndDeviceToken($userId,$userDeviceToken){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                            ->where('userId = ?',$userId)
                            ->where('userDeviceToken =?',$userDeviceToken);
        
        $settingRowset = $userSettingTable->fetchAll($select);
    
        foreach ($settingRowset as $settingRow){
            $settingRow->delete();
        }
    }
    
    public function deleteAllUserWithSameDevice($userDeviceId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                            ->where("userDeviceId =?",$userDeviceId);
        
        $userSettingRowset = $userSettingTable->fetchAll($select);
        
        foreach($userSettingRowset as $userSettingRow){
            $userSettingRow->delete();
        }
    }
    
    public function deleteAllUserWithSameDeviceToken($userDeviceToken){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        if($userDeviceToken !="1234"){
            
            $select = $userSettingTable->select()
                                ->where("userDeviceToken =?",$userDeviceToken);

            $userSettingRowset = $userSettingTable->fetchAll($select);

            foreach($userSettingRowset as $userSettingRow){
                $userSettingRow->delete();
            }
        }
   }
    
    public static function getRowByUserIdAnduserDeviceType($userId,$type = 'web'){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                                ->where('userId =?',$userId)
                                ->where('userDeviceType =?',$type);
        
        return $userSettingTable->fetchRow($select);
        
    }
    
    /**
     * delete from web and app both 
     */
    public function logoutFromOtherDevices($userId,$userDeviceId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $where = array(
                    'userId =?'=> $userId,
                    'userDeviceId !=?' => $userDeviceId
                 );
        
        $userSettingTable->delete($where);
    }
    
    /**
     *  check user login with mobile or website 
     */
    
    public static function userLoginMedia($userId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select = $userSettingTable->select()
                        ->where('userId =?',$userId)
                        ->where('mediaType =?',Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP);
        
        if($userSettingRow = $userSettingTable->fetchRow($select)){
            return $userSettingRow->mediaType;
        }else{
            $select = $userSettingTable->select()
                        ->where('userId =?',$userId)
                        ->where('mediaType =?',Application_Model_DbTable_UserSetting::MEDIA_TYPE_WEB);
            
            $userSettingRow = $userSettingTable->fetchRow($select);
            return ($userSettingRow)?$userSettingRow->mediaType:false;
        }
    }

    public function lastUserLogin($userId,$userDeviceId){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $select    =  $userSettingTable->select()
                        ->where('userId =?', $userId)
                        ->where('userDeviceId =?', $userDeviceId)
                        ->order('usrSettId Desc')
                        ->limit(1);
        return $this->fetchRow($select);
    }
}

?>
