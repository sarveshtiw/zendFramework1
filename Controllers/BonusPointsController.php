<?php
class BonusPointsController extends My_Controller_Abstract{
    
    public function preDispatch() {
        parent::preDispatch();
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $this->user = new Application_Model_UsersMapper();

        $this->_helper->layout->disableLayout();
    }


    public function getPointsByUserIdAction(){
        
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waPoints = new Application_Model_DbTable_WaPoints();
        
        $decoded = $this->common->Decoded();
        
        $userId             = $this->getRequest()->getPost('userId','');
        $userSecurity       = $this->getRequest()->getPost('userSecurity','');
        $userDeviceId       = $this->getRequest()->getPost('userDeviceId','');
        $userDeviceToken    = $this->getRequest()->getPost('userDeviceToken','');

       
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){

                
                $db = $this->db;
                $db->beginTransaction();
                
                try{
                    $points = $waPoints->getPointsById($userId);
                    $bonusPointsDetails = array(
                        'userId'                 => $userId,
                        'points'                  => $points
                    );
                    $response = array(
                        "error_code"            => "0",
                        "response_error_key"    => "0",
                        "response_string"       => "WA Details",
                        "result"                => $bonusPointsDetails
                    );
                   echo json_encode($response);exit;
                
                }catch(Exception $e){
                    $db->rollBack();
                    $this->common->displayMessage("There is some error","1",array(),"12");
                }
                $db->commit();
                $this->common->displayMessage("WA created successfully","0",array(),"0");
                
            }else{
                $this->common->displayMessage("User account does not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
    }
    
    
}

?>
