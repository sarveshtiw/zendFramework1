<?php
class CommonserviceController extends My_Controller_Abstract{
   
    public function preDispatch() {
        parent::preDispatch();
        
        $this->_helper->layout->disableLayout();
    }
    
    public function countriesAction(){
        $countryTable = new Application_Model_DbTable_Countries();
        
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
                
	if($userSecurity==$this->servicekey){
            $countriesRowset = $countryTable->getRecords();
        
            $records = array();
                
            foreach($countriesRowset as $countryRow){
                $records[] = array(
                    'countryId'         => $countryRow->countryId,
                    'countryName'       => $countryRow->countryName,
                    'countryIsoCode1'   => $countryRow->countryIsoCode2,
                    'countryIsoCode2'   => $countryRow->countryIsoCode3,
                    'countryIsdCode'    => $countryRow->countryIsdCode,
                );
            }

            if(count($records)){
                $this->common->displayMessage("Countries listing found successfully","0",$records);
            }else{
                $this->common->displayMessage("Countries listing not found","1",array(),"1");
            } 
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"2");
        }
    }
    
    /**
     * all webservice details
     */
    
    public function detailsAction(){
        
        $adminLogin = new Zend_Session_Namespace('admin_login');
        if(!$adminLogin->userDetails){
            $this->_redirect('/commonservice/login');
        }
        
        
    }
    
    public function loginAction(){
       
        $adminLogin = new Zend_Session_Namespace('admin_login');
        if($adminLogin->userDetails){
            $this->_redirect('/commonservice/details');
        }
        if($this->getRequest()->isPost()){
            
            $email = $this->getRequest()->getPost('email','');
            $password = md5($this->getRequest()->getPost('password',''));
            
            $adminUsersTable = new Application_Model_DbTable_AdminUsers();
            
            $select = $adminUsersTable->select()
                            ->where('email =?',$email)
                            ->where('password =?',$password);
            
            if($adminUserRow = $adminUsersTable->fetchRow($select)){
                $adminLogin->userDetails =  $adminUserRow->toArray();  
                $this->_redirect('/commonservice/details');
            }else{
                $this->view->email = $email;
                $this->view->error = "Please provide the correct credentials.";
            }
            
        }
        
    }
    
    public function logoutAction(){
        $adminLogin = new Zend_Session_Namespace('admin_login');
        if($adminLogin->userDetails){
            unset($adminLogin->userDetails);
        }
        
        $this->_redirect('/commonservice/login');
    }
    
    public function uploadUserImageAction(){

        $decoded = $this->common->Decoded();
        $userSecurity = $this->getRequest()->getPost('userSecurity','');
        
        $userTable = new Application_Model_DbTable_Users();
        $this->servicekey = $userSecurity;

        if($userSecurity == $this->servicekey){
            $userId = $this->getRequest()->getPost('userId','');
            //$this->common->checkEmptyParameter1(array($userId));

            if($userRow = $userTable->getRowById($userId)){
                
                $response = $this->common->uploadImage($_FILES["file"],"users");

                if(isset($response['new_file_name'])){
                    
                    $file_path = "/images/users/".$response['new_file_name'];
                    
                    $userRow->userImage = $file_path;
                    $userRow->userModifieddate = date("Y-m-d H:i:s");
                    
                    $userRow->save();
                    
                    $this->common->displayMessage($this->makeUrl($file_path),"0",array(),"0");

                }elseif(isset($response['error'])){
                    if($response['error'] == "Invalid_file"){
                        $this->common->displayMessage("Invalid file","1",array(),"4");
                    }else{
                        $this->common->displayMessage($response['error'],"1",array(),"5");
                    }
                }else{

                }
            }else{
                $this->common->displayMessage("User account not exist","1",array(),"2");
            }
            
        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }
        
        exit;
    }
    
    public function emptyDatabaseAction(){
        
        $where = "1=1";
        $this->db->delete('usrSetting', $where);
        $this->db->delete('users', $where);
        $this->db->delete('trustees', $where);
        $this->db->delete('secretGroup', $where);
        $this->db->delete('secGroupUsers', $where);
        $this->db->delete('groups', $where);
        $this->db->delete('groupMembers', $where);
        $this->db->delete('friends', $where);
        $this->db->delete('editFriendTrusteeDetails', $where);
        $this->db->delete('accountSetting', $where);
        
        exit;
        $usrSetting->delete("1=1");
        exit;
    }
    
    
}

?>
