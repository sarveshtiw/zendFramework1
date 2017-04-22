<?php
class Application_Model_DbTable_AccountSetting extends Zend_Db_Table_Abstract{
    
    protected $_name = 'accountSetting';
    protected $_id = 'id';
    
    const AUTO_FRIEND_ON = 'on';
    const AUTO_FRIEND_OFF = 'off'; //extra_privacy
    const EXTRA_PRIVACY_ON = 'on';
    const EXTRA_PRIVACY_OFF = 'off';
    
    public function getRowById($id){
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $select = $accountSettingTable->select()
                             ->where('id =?',$id);
        
        return $accountSettingTable->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        
        $select = $accountSettingTable->select()
                             ->where('userId =?',$userId);
        
        return $accountSettingTable->fetchRow($select);
    }
    
   
    
}

?>
