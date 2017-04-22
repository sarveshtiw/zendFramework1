<?php
/*
 * Push controller will be used for sending push on device
 */
 class PushController extends My_Controller_Abstract{
     
     public function init() {
         parent::init();
        date_default_timezone_set('UTC');
        $this->common = new Common_Api();
        $this->servicekey = Zend_Registry::getInstance()->constants->servicekey;
        
        $this->_helper->layout->disableLayout();
     }
     
     public function curlRequestAction(){
         $type = $this->getRequest()->getPost('type','');
         $method = $this->getRequest()->getPost('method','');
         $userId = $this->getRequest()->getPost('userId','');
         
         switch ($type) {
             case "new_signup":
                 $this->sendPushToContacts($userId);
             
                 break;

             default:
                 break;
         }
         exit;
     }
     
     public function sendPushToContacts($userId){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        
        if($userRow = $userTable->getRowById($userId)){
            $contactRowset = $phoneContactTable->getRecordsByPhoneNumber($userRow->userPhone, $userRow->phoneWithCode);
       
            foreach($contactRowset as $contactRow){
                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($contactRow->user_id);

                $pushMessage = $userRow->userNickName." created his/her account in WA-app";
                $pushType = "user_signup";

                foreach ($userLoginDeviceRowset as $loginDeviceRow){
                    if($loginDeviceRow->userDeviceType !="web"){
                        if($loginDeviceRow->userDeviceType == "iphone"){
                            $payload['aps'] = array('alert'=>$pushMessage,'badge'=>0,'sound' =>'Default','type' =>$pushType, 'userId'=>$userRow->userId,'userName'=>$userRow->userFullName,'userImage'=>$userRow->userImage);
                        }else{
                            $payload = array(
                                'message'   => $pushMessage,
                                'type'      => $pushType,
                                'result'    => array('userId'=>$userRow->userId,'userName'=>$userRow->userFullName,'userImage'=>$userRow->userImage)
                           );
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                    }
                }
           }
        }
   }
     
     
 }
?>
