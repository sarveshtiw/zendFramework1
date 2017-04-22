<?php

class Application_Model_UsersMapper {

    protected $_dbTable;

    public function __construct() {
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function setDbTable($dbTable) {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Users');
        }

        return $this->_dbTable;
    }

    public function checkRecords($field, $value) {
        $result = $this->getDbTable()->select('userId')->where($field . ' = ?', $value)->query()->fetch();
        if ($result)
            return true;
        else
            return false;
    }

    public function getUserInfo($value) {

        $userInfo = array();
        $userInfo = $this->db->select()->from('users')->where('userId = ?', $value)->query()->fetch();

        return $userInfo;
    }

    public function db_fetch_single_cell($field, $value, $fieldname) {
        $result = $this->getDbTable()->select('userEmail')->where($field . ' = ?', $value)->query()->fetch()->{$fieldname};
        if ($result)
            return $result;
        else
            return false;
    }

    public function db_fetch_single_assoc_row($table, $field, $value) {
        $result = $this->db->select()->from($table)->where($field . ' = ?', $value)->query()->fetch();
        if ($result)
            return $result;
        else
            return false;
    }

    public function deleterows($table, $field, $value) {
        $result = $this->db->delete($table, $field . ' = ', $value)->query()->fetch();
    }

    public function insert($array) {
        try {
            $result = $this->getDbTable()->insert($array);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if ($result)
            return $result;
        else
            return false;
    }

    public function update($array, $where, $op = '=') {
        $where = $this->getDbTable()->getAdapter()->quoteInto($where['field'] . ' ' . $op . ' ?', $where['value']);
        try {
            return $result = $this->getDbTable()->update($array, $where);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries = array();
        foreach ($resultSet as $row) {
            $entries[] = $row;
        }

        return $entries;
    }

    public function checkLogin($requestData) {
        $userTable = $this->getDbTable();

        $select = $userTable->select()
                ->where('userPassword =?', md5($requestData['userPassword']));


        if ($requestData['loginType'] == 'phone') {
            $select->where('userPhone =?', $requestData['userPhone']);
        } else {
            $select->where('userName =?', $requestData['userName']);
        }

        return $userTable->fetchRow($select);
    }

    public function checkUserFbId($userfbId) {
        $userTable = $this->getDbTable();
        $select = $userTable->select()
                ->where('userFbId =?', $userfbId);

        return $userTable->fetchRow($select);
    }

    public function sendmail($para = array(), $template, $to, $subject) {

        $common = new Common_Api();

        // create view object
        $html = new Zend_View();

        $html->setScriptPath(APPLICATION_PATH . '/views/emails/');
        // assign valeues

        if (count($para) > 0) {
            foreach ($para as $key => $value) {
                $html->assign($key, $value);
            }
        }
        
        // create mail object
        // render view
         $bodyText = $html->render($template);
       
        // configure base stuff
//		define('_MAIL_EMAIL','support@appxcel.com');

        $common->sendMail($to, $bodyText, $subject);
        // die();

        //	mail($to,$subject, $bodyText, $headers,$extraKey); 
    }

    function setLanguage($language) {
        $lang = !empty($language) ? $language : "en";
        $filePath = APPLICATION_PATH . "/language/" . $lang . ".csv";
        if (!file_exists($filePath)) {
            $lang = "en";
            $filePath = APPLICATION_PATH . "/language/en.csv";
        }
        $translate = new Zend_Translate(
                array("adapter" => 'csv', "content" => $filePath, "locale" => $lang, 'delimiter' => '^'));
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Translate', $translate);
        $translate->setLocale($lang);
    }

}
