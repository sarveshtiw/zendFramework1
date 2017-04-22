<?php

class Application_model_DbTable_WaPointsHistoryRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaPointsHistory extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_points_history";
    protected $_primary  = "id";
    protected $_rowClass = "Application_Model_DbTable_WaPointsHistory";
    
    public function getRowById($id)
    {
        
    }
}