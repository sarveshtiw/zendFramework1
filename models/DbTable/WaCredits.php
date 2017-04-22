<?php

class Application_model_DbTable_WaCreditsRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaCredits extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_credits";
    protected $_primary  = "id";
    protected $_rowClass = "Application_model_DbTable_WaCreditsRow";
    
      
    public function getRowById($id)
    {
        $waCreditsTable      = new Application_Model_DbTable_WaCredits();
        $select              = $waCreditsTypeTable->select()
                               ->where('id =?', $id);
        return $waCreditsTable->fetchRow($select);
    }
    
    public function SaveCredits($data)
    {
       $waCreditsTable      = new Application_Model_DbTable_WaCredits();
       $insert_id           = $waCreditsTable->insert($data);
       return $insert_id;
    }
    
    public function UpdateCredits($data)
    {   
        $waCreditsTable   = new Application_Model_DbTable_WaCredits();
        $where            = $data['id'];
        $insert_id        = $waCreditsTable->update($data, "id = " . $where);
        return $insert_id;
    }
    
    public function DeleteCreditsType($id)
    {
        $waCreditsTable   = new Application_Model_DbTable_WaCredits();
        $data             = array('is_delete' => 0);
        $result           = $waCreditsTable->update($data, "id = " . $id);
        return $result;
    }
    
}

