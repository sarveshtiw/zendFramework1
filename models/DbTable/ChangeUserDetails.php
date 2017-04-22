<?php
class Application_Model_DbTable_ChangeUserDetailsRow extends Zend_Db_Table_Row_Abstract{
    
    public function isTimeExpire(){
        $creation_date = $this->creaion_date;
        
        if((time() - strtotime($creation_date)) > 24*60*60){
            return true;
        }else{
            return false;
        }
    }
    
}

class Application_Model_DbTable_ChangeUserDetails extends Zend_Db_Table_Abstract{
    protected $_name = "change_user_details";
    protected $_primary = "id";
    protected $_rowClass = "Application_Model_DbTable_ChangeUserDetailsRow";
    
    public function getRowById($id){
        $changeUserDetailTable = new Application_Model_DbTable_ChangeUserDetails();
       
        $select = $changeUserDetailTable->select()
                            ->where('id =?',$id);
        
        return $changeUserDetailTable->fetchRow($select);
    }
    
    public function getRowByIdAndConfirmCode($id,$confirm_code){
        $changeUserDetailTable = new Application_Model_DbTable_ChangeUserDetails();
        
        $select = $changeUserDetailTable->select()
                        ->where('id =?',$id)
                        ->where('confirm_code =?',$confirm_code);
        
        return $changeUserDetailTable->fetchRow($select);
    }
    
}

?>
