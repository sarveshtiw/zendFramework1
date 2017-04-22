<?php

class Application_Model_DbTable_PaymentInfoRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_PaymentInfo extends Zend_Db_Table_Abstract {

    protected $_name = 'wa_payment_info';
    protected $_id = 'id';
    protected $_rowClass = "Application_Model_DbTable_PaymentInfoRow";
    
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

    public function updateData($response) {
        $card_id = $response['id'];
        $payer_id = $response['payer_id'];
        $card_data = array('card_type' => $response['type'], 'card_number' => $response['number'],
            'expire_month' => $response['expire_month'], 'expire_year' => $response['expire_year'],
            'first_name' => $response['first_name'], 'last_name' => $response['last_name'],
            'valid_until' => $response['valid_until'], 'ccv2' => $response['cvv2_code']);
        $arrData = array('user_id' => $response['user_id'], 'card_id' => $card_id,
            'payer_id' => $payer_id, 'created_at' => date("Y-m-d H:i:s"),
            'card_data' => Zend_Json::encode($card_data),
        );
        $this->update($arrData,"user_id='{$response['user_id']}'");
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
