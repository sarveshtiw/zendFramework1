<?php
class IndexController extends My_Controller_Abstract
{
    public function init(){
       // parent::preDispatch();
    }
    
    public function indexAction(){
//          echo date("Y-m-d H:i:s");
//echo gmdate("l") . "<br>";
//echo $startTime = date("Y-m-d H:i:s", strtotime('330 minutes', time()));
//// Prints the day, date, month, year, time, AM or PM
//echo gmdate("l jS \of F Y h:i:s A") . "<br>";
//echo date("l jS \of F Y h:i:s A") . "<br>";die();
        if(isset($this->loggedUserRow)){
           //$this->_redirect($this->makeUrl('/chat/chat'));
        }
        $userTable = new Application_Model_DbTable_Users();
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $countryTable = new Application_Model_DbTable_Countries();
        $userSettingTable  = new Application_Model_DbTable_UserSetting();
        $language =$this->getRequest()->getCookie('lang');
        $trustee_id = $this->getRequest()->getQuery('trustee_request_id','');
        $friend_id = $this->getRequest()->getQuery('friend_request_id','');
        $this->view->language = $language;
        
        $confirmCode = $this->getRequest()->getQuery('confirm_code','');
        
        $errors = array();
       
        if($trustee_id && $confirmCode){
            if($trusteeRow = $TrusteeTable->getRowByIdAndCode($trustee_id, $confirmCode)){
                $trusteeUserRow = $userTable->getRowById($trusteeRow->userId);
            }
        }
       
        if($friend_id && $confirmCode && !$trusteeRow){
            if($friendRow = $friendTable->getRowByIdAndCode($friend_id,$confirmCode)){
                $friendUserRow = $userTable->getRowById($friendRow->userId);
            }
        }
      
       if($this->getRequest()->isPost()){
           $userName = trim($this->getRequest()->getPost('login_user_name',''));
           $userPassword = trim($this->getRequest()->getPost('login_user_password',''));
           
           $errors = array();
           
           if($userName == ""){
               $errors['login_user_name']  = "Required Field";
           }
           
           if($userPassword == ""){
               $errors['login_user_password']  = "Required Field";
           }
           
           if(empty($errors)){
               $select = $userTable->select()
                            ->where('userEmail =?',$userName)
                            ->where('userPassword =?',md5($userPassword));
              
//                $select = $userTable->select()
//                            ->where('(userPhone =?',$userName)
//                            ->orWhere('userEmail=?)',$userName)
//                            ->where('userPassword =?',md5($userPassword));
//              
               $userRow = $userTable->fetchRow($select);
               if($userRow){
                   if($userRow->userStatus == "1"){
                        $webLoginToken = strtotime(date('Y-m-d H:i:s')).rand(1000,10000);
                        setcookie("adult", "", time() - 3600);
                        $data = array(
                            'userId'        => $userRow->userId,
                            'mediaType'     => 'web',
                            'isOnline'      => '1',
                            "userLoginTime" => date('Y-m-d H:i:s'),
                            "webLoginToken" => $webLoginToken,
                            "mediaType"     => 'web'
                        );
                       // $this->sendCurlRequest("/index/get-dialog?email=$userName");
                        $userSettingRow = $userSettingTable->createRow($data);
                        $userSettingRow->save();
                         
                        $userData = $userRow->toArray();
                        $userData = array_merge($userData,array(
                            'webLoginToken' => $webLoginToken,
                            'usrSettId'     => $userSettingRow->usrSettId
                        ));
                        $auth = Zend_Auth::getInstance();
                        $auth->setStorage(new Zend_Auth_Storage_Session('login'));
                        $auth->getStorage()->write($userData);
                        $this->_redirect($this->makeUrl('/chat/chat'));
                   }else{
                       $this->view->userRow = $userRow;
                       if($userRow->userStatus == "0"){
                           $errors['in_active_account'] = "Your account is inactive";
                       }
                   }
               }else{
                   $errors['login_user_name'] = "Either Email/Phone or password is incorrect";
               }
               
           }
       }
       
       
       $this->view->errors = $errors;
       $this->view->countriesList = $countryTable->getCountriesList();
       $this->_helper->layout->disableLayout();
    }
    
    public function logoutAction(){
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('login'));
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        if($auth->hasIdentity()){
            $authData = $auth->getIdentity();
            
            $select = $userSettingTable->select()
                                ->where('usrSettId =?',$authData['usrSettId']);
            
            if($userSettingRow = $userSettingTable->fetchRow($select)){
                $userSettingRow->delete();
               
            /*  if($userSettingTable->isUserOnline($authData['userId'])){
                    $device_logout_namespace = new Zend_Session_Namespace('other_device_logout');
                    $device_logout_namespace->other_device = true;
                    $device_logout_namespace->logout_userId = $authData['userId'];
                }
             */
            }
            
            $auth->clearIdentity();
        }
        
        $this->_redirect($this->baseUrl());
    }
     
    public function otherDeviceLogoutAction(){
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userId = $this->getRequest()->getPost('user_id','');
        
        if($userId && $this->getRequest()->isPost()){
            $userSettingTable->logoutWithAllDevice($userId);
        }
        
        echo "success";exit;
        
    }
    function confirmAction(){
        $this->_helper->layout->setLayout('new_layout');
    }
    
     function confirm2Action(){
        $this->_helper->layout->setLayout('new_layout');
    }
    
    function exitSiteAction(){
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('login'));
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        if($auth->hasIdentity()){
            $authData = $auth->getIdentity();
            
            $select = $userSettingTable->select()
                                ->where('usrSettId =?',$authData['usrSettId']);
            
            if($userSettingRow = $userSettingTable->fetchRow($select)){
                $userSettingRow->delete();
            }
            $auth->clearIdentity();
        }
        $this->redirect("/help/faq");
    }
    
    function enterSiteAction(){
        setcookie('adult', 1, time() + time()+31556926, "/");
        $this->redirect("/chat/chat");
    }


}

