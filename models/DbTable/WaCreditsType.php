<?php
 
class Application_model_DbTable_WaCreditsTypeRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaCreditsType extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_credits_type";
    protected $_primary  = "id";
    protected $_rowClass = "Application_model_DbTable_WaCreditsTypeRow";
    
    public function getRowById($id)
    {
        $waCreditsTypeTable  = new Application_Model_DbTable_WaCreditsType();
        $select              = $waCreditsTypeTable->select()
                               ->where('id =?', $id);
        return $waCreditsTypeTable->fetchRow($select);
    }
    
    public function SaveCreditsType($data)
    {
       $waCreditsTypeTable  = new Application_Model_DbTable_WaCreditsType();
       $insert_id           = $waCreditsTypeTable->insert($data);
       return $insert_id;
    }
    
    public function UpdateCreditsType($data)
    {   
        $waCreditsTypeTable   = new Application_Model_DbTable_WaCreditsType();
        $where                = $data['id'];
        $insert_id            = $waCreditsTypeTable->update($data, "id = " . $where);
        return $insert_id;
    }
    
    public function DeleteCreditsType($id)
    {
        $waCreditsTypeTable   = new Application_Model_DbTable_WaCreditsType();
        $data                 = array('is_deleted' => 1);
        $result               = $waCreditsTypeTable->update($data, "id = " . $id);
        return $result;
    }
}

