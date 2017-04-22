<?php 
class ContactController extends My_Controller_Abstract{
    
    public function sendRequestAction(){
        $userTable = new Default_Model_Users();
        $decoded = $this->getPostValues();
        $requestTable = new Default_Model_Request();
        
        $decoded = array(
            'user_id'               => '1',
            'user_security'         => 'afe2eb9b1de658a39e896591999e1b59',
            'recipient_first_name'  => 'abc',
            'recipient_last_name'   => 'xyz',
            'recipient_email'       => 'abc@gmail.com',
            'recipient_phone'       => '',
            'user_device_id'        => '12345',   
            'user_device_token'     => '12345'
        );
        
        
        $user_security = $decoded['user_security'];
        $user_id = $decoded['user_id'];
        $recipient_first_name = $decoded['recipient_first_name'];
        $recipient_last_name = $decoded['recipient_last_name'];
        $recipient_email = (isset($decoded['recipient_email']) && $decoded['recipient_email']!="")?$decoded['recipient_email']:"";
        $recipient_phone = (isset($decoded['recipient_phone']) && $decoded['recipient_phone']!="")?$decoded['recipient_phone']:"";
        
        $user_device_id = $decoded['user_device_id'];
        $user_device_token = $decoded['user_device_token'];
        
        if($user_security == $this->user_security){
            if($recipient_email){
                $request_type = "email";
                $this->checkEmptyParameter(array($user_security,$user_id,$user_device_id,$user_device_token,$recipient_email));    
            }else{
                $request_type = "phone";
                $this->checkEmptyParameter(array($user_security,$user_id,$user_device_id,$user_device_token,$recipient_phone));
            }
                        
            if($userRow = $userTable->getRowByUserId($user_id)){
                if(($userRow->user_device_id == $user_device_id) && ($userRow->user_device_token == $user_device_token)){
                            
                    $request_param = ($request_type == "email")?$recipient_email:$recipient_phone;
                    $random = $this->generateRandomCode();
                    
                   if($requestRow = $requestTable->getRowByUserIdAndRequestParam($user_id,$request_param)){
                      if($requestRow->status == "1"){
                            $data = array(
                                'recipient_email'   => $requestRow->recipient_email,
                                'recipient_phone'   => $requestRow->recipient_phone,
                                'response_email'    => $requestRow->response_email,
                                'response_phone'    => $requestRow->response_phone,
                            );
                            $this->displayMessage('Already accepted request',"1",$data,"5");        
                      }
                      elseif($requestRow->status == "2"){
                            $this->displayMessage('Already rejected request',"1",array(),"6");
                      }
                      else{
                            if($requestRow->second_request_time){
                                $this->displayMessage('You already sended request two times',"1",array(),"7");    
                            }else{
                                $requestRow->second_request_time = date("Y-m-d H:i:s");
                                $requestRow->save();
                            } 
                      }
                   }else{
                        if($recipient_phone['0'] !="+"){
                            $recipient_phone = "+1".$recipient_phone;
                        }
                        
                        $data = array(
                            'user_id'               => $user_id,
                            'request_type'          => $request_type,
                            'device_id'             => $user_device_id,
                            'device_token'          => $user_device_token,
                            'recipient_first_name'  => $recipient_first_name,
                            'recipient_last_name'   => $recipient_last_name,
                            'recipient_email'       => $recipient_email,
                            'recipient_phone'       => $recipient_phone,
                            'first_request_time'    => date('Y-m-d H:i:s'),
                            'status'                => '0',
                            'verification_code'     => $random
                        );
                          
                        $requestRow = $requestTable->createRow($data);
                        $requestRow->save();
                   }
                   
                   $request_url = $this->makeUrl('/contact/request?request='.$requestRow->request_id."&code=".$requestRow->verification_code);
                        
                   if($request_type == "email"){
                        $emailParams = array(
                              'user_name'       => $userRow->getName(),
                              'recipient_name'  => $requestRow->getRecipientName(),
                              'request_url'     => $request_url
                        );
                                 
                        $this->sendEmail($recipient_email, 'CONTACT_REQUEST', $emailParams);
                        $this->displayMessage('Contact request send successfuly',"0",array(),"0");
                   }else{
                        
                        $request_url = $this->get_tiny_url($request_url);
                        $message = $userRow->getName()." has requested contact info. updates from you.  Click here ".$request_url. "to view full message in your browser:";
                        
                        $this->sendSms_recommend($recipient_phone,$request_url);
                   
                   }     
                   $this->displayMessage('Request send successfully',"0",array(),"0"); 
                }else{
                    $this->displayMessage('User is not login',"1",array(),"4");
                }
                                
            }else{
                $this->displayMessage('User is not exist',"1",array(),"3");
            }
            
        }else{
            $this->displayMessage('You could not access this web-service',"1",array(),"2");
        }
        
    }    
    
    public function requestAction(){
        $userTable = new Default_Model_Users();
        $requestTable = new Default_Model_Request();
        $request_id = $this->getRequest()->getQuery('request','');
        $verification_code = $this->getRequest()->getQuery('code','');
    
        $requestRow = $requestTable->getRowByRequestIdAndCode($request_id,$verification_code);
        $errors = array();
        
        if($requestRow && $this->getRequest()->isPost()){
            $data = array(
                'response_email' => trim($this->getRequest()->getPost('response_email','')),
                'response_phone' => trim($this->getRequest()->getPost('response_phone',''))
            );
            
            if(($data['response_email'] == "") && ($data['response_phone'] == "")){
                $errors['response_email'] = "atleast one field is required";
            }
            
            if($data['response_email'] !=""){
                $validateData = array(
                    'response_email' => $data['response_email']                  
                ); 
               
               if(($validatedErrors = $requestTable->validate($validateData)) && ($validatedErrors !== true)){
                    $errors['response_email'] = $validatedErrors['response_email'];        
                }
            }
            
            if(empty($errors)){
                $requestRow->response_email = $data['response_email'];
                $requestRow->response_email = $data['response_phone'];
                $requestRow->response_time = date("Y-m-d H:i:s");
                $requestRow->status = "1";
                $requestRow->save();
                $this->view->response_save = true;
                
            }
            
            
        } 
    
        $this->view->errors = $errors;
        $this->view->requestRow = $requestRow;
    }
    
    public function getResponseAction(){
        $userTable = new Default_Model_Users();
        $requestTable = new Default_Model_Request();
        $decoded = $this->getPostValues();
        
        $decoded = array(
            'user_id'               => '1',
            'user_security'         => 'afe2eb9b1de658a39e896591999e1b59',
            'user_device_id'        => '12345',   
            'user_device_token'     => '12345',
            'last_request_time'     => "",
        );
        
        $user_security      = $decoded['user_security'];
        $user_id            = $decoded['user_id'];
        $user_device_id     = $decoded['user_device_id'];
        $user_device_token  = $decoded['user_device_token'];
        $last_request_time  = (isset($decoded['last_request_time']) && $decoded['last_request_time'])?$decoded['last_request_time']:"";
        
        if($user_security == $this->user_security){
            $this->checkEmptyParameter(array($user_security,$user_id,$user_device_id,$user_device_token));
            
            if($userRow = $userTable->getRowByUserId($user_id)){
                if(($userRow->user_device_id == $user_device_id) && ($userRow->user_device_token == $user_device_token)){
                    
                    $select = $requestTable->select();
                    
                    if($last_request_time){
                        $select = $select->where('modification_time >=?',$last_request_time);
                    }
                    
                    $requestRowset = $requestTable->fetchAll($select);
                    
                    $responseData = array();
                    
                    foreach($requestRowset as $requestRow){
                        $data = array(
                            'device_id'             => $requestRow->device_id,
                            'device_token'          => $requestRow->device_token,
                            'recipient_email'       => $requestRow->recipient_email,
                            'recipient_phone'       => $requestRow->recipient_phone,
                            'response_email'        => $requestRow->response_email,
                            'response_phone'        => $requestRow->response_phone,
                            'first_request_time'    => $requestRow->first_request_time,
                            'second_request_time'   => $requestRow->second_request_time,
                            'response_time'         => $requestRow->response_time,
                            'status'                => ($requestRow->status == "0")?($requestRow->second_request_time?"4":"3"):$requestRow->status,
                        );
                        $responseData[] = $data;
                    }
                    
                    $this->displayMessage('Request Responses',"0",$responseData,"0");
                    
                }else{
                    $this->displayMessage('User is not login',"1",array(),"4");
                }
            }else{
                $this->displayMessage('User is not exist',"1",array(),"3");
            }
            
        }else{
            $this->displayMessage('You could not access this web-service',"1",array(),"2");
        }
        
    }
    
    
}

?>