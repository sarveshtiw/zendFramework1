<?php

class Application_Model_DbTable_LanguageTranslateRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_LanguageTranslate extends Zend_Db_Table_Abstract {

    protected $_name = 'language_translate';
    protected $_id = 'id';
    protected $_rowClass = "Application_Model_DbTable_LanguageTranslateRow";
    public function __construct() {
        parent::__construct();
    }

    public function getRowById($id) {
        $friendTable = new Application_Model_DbTable_Friends();

        $select = $friendTable->select()
                ->where('id =?', $id);

        return $friendTable->fetchRow($select);
    }

    /**
     * 
     * @param this function will check 2 wa users are friend or not first we will check request send by login user and 
     * 
     * @param type $userId and $friendId
     * @return type friend row
     * 
     */
    public function getRowByUserIdAndFriendId($userId, $friendId) {
        $friendTable = new Application_Model_DbTable_Friends();

        $select = $friendTable->select()
                ->where('(userId =?', $userId)
                ->where('friendId =?)', $friendId)
                ->orWhere('(userId =?', $friendId)
                ->where('friendId =?)', $userId);
        $friendRow = $friendTable->fetchRow($select);
        return $friendRow;
    }

    public function saveData($arrData) {
            $this->insert($arrData);
    }

    public function getList(){
        $select = $this->select();
        $result = $this->fetchAll($select);
        return $result;
    }
    public function searchLanguage($searchText){
        $select = $this->select();
        $select->where("name LIKE ?",'%'.$searchText.'%');
        $result = $this->fetchAll($select)->toArray();
        return $result;
    }
    
   
    public function getByUserId($user_id) {
        $select = $this->select()->from($this->info('name'))->where("user_id='{$user_id}'");
        $result = $this->fetchRow($select);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

}

?>
