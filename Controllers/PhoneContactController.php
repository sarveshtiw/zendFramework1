<?php
class PhoneContactController extends My_Controller_Abstract{

    public function init(){
        parent::init ();
        $this->_helper->layout->disableLayout();
        
    }
    
    public function indexAction(){
        
    }
    
    /**
     * add phone contact web service
     */
    
    public function addContactAction(){
        $userTable = new Application_Model_DbTable_Users();
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $decoded = $this->common->Decoded();
     
   /*     $handle = fopen('php://input','r');
                
       // 	$jsonInput = urldecode(fgets($handle));
                $jsonInput = fgets($handle);
        
        
        $file = fopen("new_file.txt", "w");
        fwrite($file, $jsonInput);
        fclose($file);
        chmod($file, 0777); 
        exit;
   /*     $decoded = array(
            'userSecurity'  => 'afe2eb9b1de658a39e896591999e1b59',
            'userId'        => '586',
            'userDeviceId'  => '2147483647',
            'phoneDataset'  => array(
                Array
                (
                    "phone" => "+123456789",
                    "phone_book_id" => "217"
                ),
                Array
                (
                    "phone" => "+12222222229",
                    "phone_book_id" => "218"
                ),
                Array
                (
                    "phone" => "+3333333333339",
                    "phone_book_id" => "219"
                )
            )
        ); 
     */
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $phoneDataset = $decoded['phoneDataset'];
        $is_return_result = (isset($decoded['is_return_result']) && $decoded['is_return_result'])?$decoded['is_return_result']:"0";
        $last_request_time = isset($decoded['last_request_time'])?$decoded['last_request_time']:'';
        
        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
        //    $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                    
                    $userAccountSettingRow = $accountSettingTable->getRowByUserId($userId);
                    
                    $responseDataset = $this->addPhoneContact($userId,$userDeviceId,$phoneDataset);
                    
                    $friendData = array();
                    
                    if($is_return_result && $userAccountSettingRow){
                        
                        // make auto friend for incoing request
                        
//                        if($userAccountSettingRow->extra_privacy == Application_Model_DbTable_AccountSetting::EXTRA_PRIVACY_OFF){
                           // $phoneContactTable->makeAutoFriend($userRow);  
//                        }
                        
                        // make friend to my contact
                        
                        //if($userAccountSettingRow->auto_friends == Application_Model_DbTable_AccountSetting::AUTO_FRIEND_ON){
                           // $phoneContactTable->makeFriendToContact($userRow);   
                        //}
                        
                       
                        $friendData = $friendTable->getMyFriendList($userId, $last_request_time);
                       
                        $userRow->getFriendListTime = date("Y-m-d H:i:s");
                        $userRow->save();
                        
                        $last_request_time = date("Y-m-d H:i:s");
                    }
                    
                    $response = array(
                        'response_string'       => 'Contact save successfully',
                        'error_code'            => '0',
                        'response_error_key'    => '0',
                        'last_request_time'     => $last_request_time,
                        'result'                => $friendData
                    );
                    echo json_encode($response);exit;
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
        
    }
    
    /**
     *  save phone contact in database and check phone number is match with any wa user or not
     */
    
    public function addPhoneContact($user_id,$userDeviceId,$phoneDataset){
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $userAccountSettingRow = $accountSettingTable->getRowByUserId($user_id);
        
        foreach($phoneDataset as $phoneData){
            $phone = $phoneData['phone'];
            $phone_book_id = $phoneData['phone_book_id'];
            $phoneContactRow = $phoneContactTable->getRowByUserIdAndPhoneBookIdAndDeviceId($user_id,$userDeviceId, $phone_book_id);

            if($phoneContactRow){
               
               if($phoneData['is_deleted'] == "1"){
                   
                   $phoneContactRow->delete();
               }else{
                  if($phoneContactRow->phone_number != $phone){
                     $phoneContactRow->phone_number = $phone;
                     $phoneContactRow->modified_date = date("Y-m-d H:i:s");
                     $phoneContactRow->save();
                  } 
               }
                
            }else{
                 $data = array(
                    'user_id'       => $user_id,
                    'device_id'     => $userDeviceId,
                    'phone_book_id' => $phone_book_id,
                    'phone_number'  => $phone,
                    'creation_date' => date("Y-m-d H:i:s"),
                    'modified_date' => date("Y-m-d H:i:s")
                );

                $phoneContactRow = $phoneContactTable->createRow($data);
                $phoneContactRow->save();
            }

            if(!$phoneContactRow->wa_user_id && ($userRow = $userTable->getRowByPhone($phone)) && $userRow->isActive()){
                $phoneContactRow->wa_user_id = $userRow->userId;
                $phoneContactRow->modified_date = date("Y-m-d H:i:s");
                $phoneContactRow->save();
            }
       }
        return true;
    }
    
    public function getContactsAction(){
        $userTable = new Application_Model_DbTable_Users();
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        
        $decoded = $this->common->Decoded();
        
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        
        if($userSecurity == $this->servicekey){
            $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if($userRow = $userTable->getRowById($userId)){
                
            }else{
                $this->common->displayMessage('User account is not exist',"1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
        }
    }
}

?>
