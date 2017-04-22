<?php
 
class Application_model_DbTable_WaPaymentsRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaPayments extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_payments";
    protected $_primary  = "payment_id";
    protected $_rowClass = "Application_model_DbTable_WaPaymentsRow";
    
    public function getRowById($id)
    {
        $waPaymentsTable     = new Application_Model_DbTable_WaPayments();
        $select              = $waPaymentsTable->select()
                               ->where('payer_id =?', $id);
        return $waPaymentsTable->fetchRow($select);
    }
    
    public function SavePaymentData($data)
    {
       $waPaymentsTable     = new Application_Model_DbTable_WaPayments();
       $insert_id           = $waPaymentsTable->insert($data);
       return $insert_id;
    }
}

