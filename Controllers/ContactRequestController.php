<?php
class ContactRequestController extends My_Controller_Abstract{

    public function init(){
        date_default_timezone_set('UTC');
        
        $this->common = new Common_Api();
        
        $this->servicekey = Zend_Registry::getInstance()->constants->servicekey;
        
    }
    
    public function emailRequestAction(){
        $contactRequestTable = new Application_Model_DbTable_ContactRequest();
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        
        
    }
    
    
    
    
}
?>
