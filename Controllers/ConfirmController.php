<?php
class ConfirmController extends My_Controller_Abstract{
    
    public function preDispatch() {
        parent::preDispatch();
    }
    
    public function emailAction(){
       $userTable = new Application_Model_DbTable_Users();
       $changeUserDetailsTable = new Application_Model_DbTable_ChangeUserDetails();
       
       $record_id = $this->getRequest()->getQuery('record_id','');
       $confirm_code = $this->getRequest()->getQuery('confirm_code','');
       
       $userChangesRow = $changeUserDetailsTable->getRowByIdAndConfirmCode($record_id, $confirm_code);
       
       $confirm_email_change = false;
       
       if($userChangesRow){
           if(!$userChangesRow->is_link_expire && !$userChangesRow->isTimeExpire()){
               $confirm_email_change = true;
               $userRow = $userTable->getRowById($userChangesRow->user_id);
               $userRow->userEmail = $userChangesRow->change_user_email;
               $userRow->userModifieddate = date("Y-m-d H:i:s");
               $userRow->save();
           }
           
           $userChangesRow->is_link_expire = 1;
           $userChangesRow->save();
       }
       
       $emailChangesNamespace = new Zend_Session_Namespace('user_email_changes');
       
       $emailChangesNamespace->userChangesRow = ($userChangesRow)? true:false;
       $emailChangesNamespace->confrim = $confirm_email_change;
       
       if($this->loggedUserRow){
           $this->_redirect($this->makeUrl('/'));
       }else{
           $this->_redirect($this->makeUrl('/trustees'));
       }
       
    }
    
}

?>
