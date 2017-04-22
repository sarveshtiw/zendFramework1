<?php

class WaTestamentController extends My_Controller_Abstract {
   
    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }
   
    public function createTestamentAction()
    { 
        $userTable                           = new Application_Model_DbTable_Users();   
        $waTestamentTable                    = new Application_Model_DbTable_WaTestaments();
        $waTestmentfilesTable                = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable                    = new Application_Model_DbTable_WaEventSendDetails();
        $waReceiverTable                     = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable                      = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable               = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable                  = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable                       = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable                       = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable                = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable                      = new Application_Model_DbTable_WaTestamentWitnesses();
        $waCreditsTable                      = new Application_Model_DbTable_WaCredits();
        $userSettingTable                    = new Application_Model_DbTable_UserSetting();
       
        $decoded                             = $this->common->Decoded();
        $userId                              = $this->getRequest()->getPost('userId');       
        $userDeviceId                        = $this->getRequest()->getPost('userDeviceId');
        $userDeviceToken                     = $this->getRequest()->getPost('userDeviceToken');
        $device_type                         = $this->getRequest()->getPost('device_type');
        $userSecurity                        = $this->getRequest()->getPost('userSecurity');
        $full_name                           = $this->getRequest()->getPost('full_name','');
        $gender                              = $this->getRequest()->getPost('gender','');
        $address1                            = $this->getRequest()->getPost('address1','');
        $address2                            = $this->getRequest()->getPost('address2','');
        $postcode                            = $this->getRequest()->getPost('postcode','');
        $city                                = $this->getRequest()->getPost('city','');
        $country                             = $this->getRequest()->getPost('country','');        
        $nationality                         = $this->getRequest()->getPost('nationality','');
        $id_type                             = $this->getRequest()->getPost('id_type','');
        $id_number                           = $this->getRequest()->getPost('id_number','');
        $wausername                          = $this->getRequest()->getPost('wa_username','');
        $phone_number                        = $this->getRequest()->getPost('phone_number','');
        $representative_set                  = $this->getRequest()->getPost('representative_set','');
        $birth_certificate_place             = $this->getRequest()->getPost('birth_certificate_place','');
        $life_insurance_policies             = $this->getRequest()->getPost('life_insurance_policies','');
        $deposit_account_books               = $this->getRequest()->getPost('deposit_account_books','');
        $any_property                        = $this->getRequest()->getPost('any_property','');
        $other_property                      = $this->getRequest()->getPost('other_property','');
        $any_other_property                  = $this->getRequest()->getPost('any_other_property','');
        $account_set                         = $this->getRequest()->getPost('account_set','');
        $other_bank_account_set              = $this->getRequest()->getPost('other_bank_account_set','');
        $active_investments                  = $this->getRequest()->getPost('active_investments','');
        $other_possessions                   = $this->getRequest()->getPost('other_possessions','');
        $social_account_set                  = $this->getRequest()->getPost('social_account_set','');
        $estate_devise_set                   = $this->getRequest()->getPost('estate_devise_set','');
        $estate_per_devise_set               = $this->getRequest()->getPost('estate_per_devise_set','');
        $testament_special_request           = $this->getRequest()->getPost('testament_special_request','');
        $testaments_witness_set              = $this->getRequest()->getPost('testaments_witness_set','');
        $member_type                         = $this->getRequest()->getPost('member_type','');
        $total_paid                          = $this->getRequest()->getPost('total_paid','');
        $receiver_userset                    = $this->getRequest()->getPost('receiver_userset','');
        $receiver_trusteeset                 = $this->getRequest()->getPost('receiver_trusteeset','');
        $receiver_email_phoneset             = $this->getRequest()->getPost('receiver_email_phoneset','');
        $testament_id                        = $this->getRequest()->getPost('testament_id','');
        $vital_check                         = $this->getRequest()->getPost('vital_check', '');
        $vital_alert_count                   = $this->getRequest()->getPost('vital_alert_count','');
        $trustee_alert_time                  = $this->getRequest()->getPost('trustee_alert_time','');
        $citizen_country                     = $this->getRequest()->getPost('citizen_country','');
        $ip_address                          = $this->getRequest()->getPost('ip_address','');
        $latitude                            = $this->getRequest()->getPost('latitude','');  
        $longitude                           = $this->getRequest()->getPost('longitude','');
        $created_address                     = $this->getRequest()->getPost('created_address','');
        $recorded_audio                      = $this->getRequest()->getPost('recorded_audio','');   
        $images                              = $this->getRequest()->getPost('images','');
        $audio                               = $this->getRequest()->getPost('audio','');
        $video                               = $this->getRequest()->getPost('video','');
        $pdf                                 = $this->getRequest()->getPost('pdf','');
        $deviceLanguage                      = $this->getRequest()->getPost('deviceLanguage','');
        $modification_date                   = $this->getRequest()->getPost('modification_date','');
        
        $this->user->setLanguage($deviceLanguage);  
        $is_gender          = "";
        $is_gender1         = "";
        $is_gender2         = "";   
        $representativeArr  = array();
        $accountSetArr      = array();
        $otherAccountArr    = array();
        $investmentsArr     = array();
        $possessionsArr     = array();
        $estateSetArr       = array();
        $estatePerSetArr    = array();
        $socialAccountArr   = array();
        $estatePerRemaining = "";
        $witnessesArr       = array();
       /*
        $myfile     = fopen($_SERVER['DOCUMENT_ROOT']."/testaments/error.text", "a") or die("Unable to open file!");
                        fwrite($myfile, print_r($_POST, true));
                        fwrite($myfile, "<br>");
                        fclose($myfile);
               */
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
         
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {          
                $testamentRow = false;
                $is_existing_testament  = false;
                $testamentData  = false;                
                if($testament_id)
                {
                    $is_existing_testament = true;
                    $testamentRow          = $waTestamentTable->getRowById($testament_id);
                } 
                if(!empty($testaments_witness_set))
                {   
                    $arr = '';
                    $witnessIds = '';                    
                    $testaments_witness_set   = json_decode(urldecode($testaments_witness_set),true);
                    $witnessIds = array_values($testaments_witness_set);                   
                    if($witnessRows = $waWitnessTable->getWitnessesByIds($witnessIds))
                    {
                        if(!empty($witnessRows)) 
                        {
                            foreach ($witnessRows as $row)
                            {
                                $witnessesArr[] = array(
                                                    'witness_id'           => $row['witness_id'],
                                                    'name'                 => $row['name'],
                                                    'username'             => $row['username'],
                                                    'id_type'              => $row['id_type'],
                                                    'id_number'            => $row['id_number'],
                                                    'modification_date'    => $row['modification_date'],
                                                    'recorded_file_link'   => $row['recorded_file_link'],
                                                    'signature_image_link' => $row['signature_image_link']
                                                );
                            }
                        }
                    }
                }
               
                $db = $this->db;
                $db->beginTransaction();

                try 
                {
                    $user_gender =  $this->view->translate('TESTATOR');
                    if(strcasecmp($user_gender,$gender) == 0)
                    {
                        $gender = $this->view->translate('TESTATOR');
                    }
                    
                    $user_gender1 =  $this->view->translate('TESTATRIX');
                    if(strcasecmp($user_gender1,$gender) == 0)
                    {
                        $gender = $this->view->translate('TESTATRIX');
                    }
                    
                    $data   = array(
                                'user_id'                   => $userId,
                                'full_name'                 => $full_name,
                                'gender'                    => $gender,
                                'address1'                  => $address1,
                                'address2'                  => $address2,
                                'postcode'                  => $postcode,
                                'city'                      => $city,
                                'citizen_country'           => $citizen_country,
                                'country'                   => $country,
                                'nationality'               => $nationality,
                                'id_type'                   => $id_type,
                                'id_number'                 => $id_number,
                                'wa_username'               => $wausername,
                                'phone_number'              => $phone_number,
                                'birth_certificate_place'   => $birth_certificate_place,
                                'life_insurance_policies'   => $life_insurance_policies,
                                'deposit_account_books'     => $deposit_account_books,
                                'any_property'              => $any_property, 
                                'other_property'            => $other_property, 
                                'any_other_property'        => $any_other_property, 
                                'testament_special_request' => $testament_special_request,
                                'member_type'               => $member_type,
                                'is_send'                   => '1',
                                'recorded_audio'            => $recorded_audio,
                                'ip_address'                => $ip_address,
                                'latitude'                  => $latitude,
                                'longitude'                 => $longitude,
                                'created_address'           => $created_address,
                                'total_paid'                => $total_paid,
                                'modification_date'         => date('Y-m-d H:i:s'),
                                'is_status'                 => 1 
                            );

                    if($gender == $this->view->translate('TESTATOR'))
                    {
                        $is_gender  = $this->view->translate('his');   
                        $is_gender1 = $this->view->translate('him');
                        $is_gender2 = $this->view->translate('he');
                    }
                    else
                    {
                        $is_gender  = $this->view->translate('her');
                        $is_gender1 = $this->view->translate('her');
                        $is_gender2 = $this->view->translate('she');
                    }

                    if(!empty($representative_set))
                    {  
                        $representative_set = json_decode(urldecode($representative_set),true);
                        foreach ($representative_set as $row)
                        {
                            $representativeArr[]    = array(
                                                        'testament_id'     => $testament_id,
                                                        'res_name'         => $row['res_name'],
                                                        'res_gender'       => $row['res_gender'],
                                                        'res_address1'     => $row['res_address1'],
                                                        'res_address2'     => $row['res_address2'],
                                                        'res_postcode'     => $row['res_postcode'],
                                                        'res_city'         => $row['res_city'],
                                                        'res_country'      => $row['res_country'],
                                                        'res_wauseremail'  => $row['res_wauseremail'],
                                                        'res_phone_number' => $row['res_phone_number']
                                                    );
                        }
                    }

                    if(!empty($account_set))
                    {    
                        $account_set = json_decode(urldecode($account_set),true);
                        foreach ($account_set as $row)
                        {
                            $accountSetArr[]    = array(
                                                    'testament_id'          => $testament_id,
                                                    'type'                  => $row['type'],
                                                    'bank_name'             => $row['bank_name'],
                                                    'bank_address'          => $row['bank_address'],
                                                    'bank_account_number'   => $row['bank_account_number']
                                                );
                        }
                    }

                    if(!empty($other_bank_account_set))
                    { 
                        $other_bank_account_set = json_decode(urldecode($other_bank_account_set),true);
                        foreach ($other_bank_account_set as $row)
                        {
                            $otherAccountArr[]  = array(
                                                        'testament_id'          => $testament_id,
                                                        'type'                  => $row['type'],
                                                        'bank_name'             => $row['bank_name'],
                                                        'bank_address'          => $row['bank_address'],
                                                        'bank_account_number'   => $row['bank_account_number']
                                                    );
                        }
                    }

                    if(!empty($social_account_set))
                    {   
                        $social_account_set = json_decode(urldecode($social_account_set),true);
                        foreach ($social_account_set as $row)
                        {
                            $socialAccountArr[] = array(
                                                    'testament_id'       => $testament_id,
                                                    'url'                => $row['url'],
                                                    'username'           => $row['username'],
                                                    'password'           => $row['password'],
                                                    'security_ques'      => $row['security_ques'],
                                                    'is_status'          => $row['is_status'],
                                                    'managed_username'   => $row['managed_username'],
                                                );
                        }
                    } 

                    if(!empty($estate_devise_set))
                    {    
                        $estate_devise_set = json_decode(urldecode($estate_devise_set),true);
                        foreach ($estate_devise_set as $row)
                        {
                            $estateSetArr[] = array(
                                                'testament_id'      => $testament_id,
                                                'item_name'         => $row['item_name'],
                                                'beneficiairy'      => $row['beneficiairy'],
                                                'percentage'        => $row['percentage'],
                                            );
                        }
                    } 

                    if(!empty($estate_per_devise_set))
                    {
                        $estate_per_devise_set = json_decode(urldecode($estate_per_devise_set),true);
                        foreach ($estate_per_devise_set as $row)
                        {
                            $estatePerSetArr[]  = array(
                                                    'testament_id'   => $testament_id,
                                                    'item_name'      => $row['item_name'],
                                                    'beneficiairy'   => $row['beneficiairy'],
                                                    'percentage'     => $row['percentage'],
                                                );
                        }
                     
                        $account_set_arr = end($estate_per_devise_set);
                        if(!is_object($account_set_arr) && count($account_set_arr)==1){
                            $estatePerRemaining =  $account_set_arr;
                        }                     
                    } 

                    if(!empty($active_investments))
                    {
                        $active_investments = json_decode(urldecode($active_investments),true);
                        foreach ($active_investments as $row)
                        {
                            $investmentsArr[]   = array(
                                                    'testament_id' => $testament_id,
                                                    'type'         => $row['type'],
                                                    'description'  => $row['description']
                                                );
                        }
                    } 

                    if(!empty($other_possessions))
                    {
                        $other_possessions = json_decode(urldecode($other_possessions),true);
                        foreach ($other_possessions as $row)
                        {
                            $possessionsArr[]   = array(
                                                    'testament_id' => $testament_id,
                                                    'type'         => $row['type'],
                                                    'description'  => $row['description']
                                                );
                        }
                    } 
                    $count_witness           = count($testaments_witness_set);
                    $count_bankaccount       = count($accountSetArr);
                    $count_other_bankaccount = count($otherAccountArr);
                    $count_social_account    = count($socialAccountArr);
                    $count_devise_set        = count($estateSetArr);
                    $count_per_devise_set    = count($estatePerSetArr);
                    $resultData             = array_merge($data, array(
                                                'count_witness'          => $count_witness,
                                                'count_bankaccount'      => $count_bankaccount,
                                                'count_other_bankaccount'=> $count_other_bankaccount,
                                                'count_social_account'   => $count_social_account,
                                                'count_devise_set'       => $count_devise_set,
                                                'count_per_devise_set'   => $count_per_devise_set,
                                                'is_gender'              => $is_gender,
                                                'is_gender1'             => $is_gender1,
                                                'is_gender2'             => $is_gender2,
                                                'signature_image'        => '', 
                                                'active_investments'     => $investmentsArr,
                                                'other_possessions'      => $possessionsArr, 
                                                'representative_set'     => $representativeArr,
                                                'account_set'            => $accountSetArr,
                                                'other_bank_account_set' => $otherAccountArr,
                                                'social_account_set'     => $socialAccountArr,
                                                'estate_devise_set'      => $estateSetArr,
                                                'estate_per_devise_set'  => $estatePerSetArr,
                                                'estatePerRemaining'     => $estatePerRemaining,
                                                'testaments_witness_set' => $witnessesArr,
                                                'user_modification_date' => $modification_date,
                                                )
                                            );                    
                    $this->view->data   = $resultData;
                    $content            = $this->view->render('wa-testament/create-testament.phtml');
                    $header             = '<div class="pdf-header">'.$this->view->translate('wa_testament_heading').' & '.$this->view->translate('social').' “'.$this->view->translate('last_wish').'”</div>';
                    $footer             = '<div class="pdf-footer" style="margin-top: 28px;">'.$this->view->translate('testament_pdf_footer').'<br /><span>{PAGENO} / {nb} </span></div>';
                    $pdf_url            =  $this->common->createMpdf($header,$content,$footer); 
                  
                    if(!empty($_FILES['signature_image']))
                    {
                        if(!empty($testament_id)) {
                          if(count($witnessesArr) >= 2) {
                            if($userRow->lastSeenTime <= $userRow->userModifieddate){ 
                                $userLoginTime     = $userRow->userModifieddate;
                            } else {
                                $userLoginTime     = $userRow->lastSeenTime;
                            }
                            $vital_check       = json_decode(urldecode($vital_check),true); 
                            $vital_check_set   = $this->convertDateAction($userLoginTime, $vital_check);
                            $count_vital_check_set  = count($vital_check_set);
                            $vital_check_last_date  = end($vital_check_set);
                            $trustee_alert_set_time = json_decode(urldecode($trustee_alert_time),true);
                            $trustee_alert_time     = $this->convertDateAction($vital_check_last_date, $trustee_alert_set_time);

                            if($vital_alert_count == $count_vital_check_set) {
                                unlink($_SERVER['DOCUMENT_ROOT'].$pdf_url);
                                $signature_image_link  = $_FILES['signature_image'];                                 
                                $response   = $this->common->upload($signature_image_link,'signature_images');
                                if(isset($response))
                                {
                                    $signature_image_link   = "testaments/signature_images/".$response['new_file_name'];
                                    $resultData             = array_merge($resultData, array(
                                                                'signature_image' => $this->makeUrl($signature_image_link)
                                                                )                                            
                                                            );
                                }
                                $this->view->data   = $resultData;    
                                $content        = $this->view->render('wa-testament/create-testament.phtml');
                                $pdf_url        = $this->common->createMpdf($header,$content,$footer); 
                                $return_url     = $this->common->uploadTestament($pdf_url);
                                unlink($_SERVER['DOCUMENT_ROOT'].$pdf_url);
                                $bank_account_set          = array_merge($accountSetArr,$otherAccountArr);  
                                $testaments_others_set     = array_merge($investmentsArr,$possessionsArr);
                                $testaments_estate_set     = array_merge($estateSetArr,$estatePerSetArr);
                                if(!isset($return_url['error']))
                                {
                                    $data             = array_merge($data, array(
                                                            'created_pdf_link' => $return_url['new_file_name']
                                                          )
                                                        );
                                }
                                if($testamentRow)
                                {
                                    $testamentRow->setFromArray($data);
                                    $testamentRow->save();
                                    $testamentRow    = $testament_id;                                
                                }
                                else
                                {
                                    $testamentRow = $waTestamentTable->saveTestament($data);
                                }

                                if($is_existing_testament)
                                {
                                   $waRepresentativeTable->deleteRepresentatives($testamentRow);
                                   $waBankAccountTable->deleteBankAccounts($testamentRow);
                                   $waOthersTable->deleteOthersSet($testamentRow);
                                   $waSocialAccountTable->deleteSocialAccounts($testamentRow);
                                   $waEstateTable->deleteEstateDividations($testamentRow);
                                   $waTestmentfilesTable->deleteTestamentFiles($testamentRow);
                                   $waEventSendTable->deleteEventSendTable($userId);
                                }
                                ($representative_set) ? $this->InsertMultipleRows('wa_testaments_represtatives',$representativeArr):"0";
                                (count($bank_account_set)>0)?$this->InsertMultipleRows('wa_testaments_bank_accounts',$bank_account_set):"";
                                (count($testaments_others_set)>0)?$this->InsertMultipleRows('wa_testaments_others', $testaments_others_set):"";
                                (count($socialAccountArr)>0)?$this->InsertMultipleRows('wa_testaments_social_accounts', $socialAccountArr):""; 
                                (count($testaments_estate_set)>0)?$this->InsertMultipleRows('wa_testaments_estate', $testaments_estate_set):"";

                                $testamentData      = array(
                                                        'testament_id' => $testamentRow,
                                                        'creation_date' => date('Y-m-d H:i:s'),
                                                        'is_status'    => 1
                                                    );                
                                if(!empty($images))
                                {    
                                    $images = json_decode(urldecode($images),true);                                    
                                    if(count($images) > 0)
                                    {      
                                        $tdata  = array();
                                        foreach ($images as $image)
                                        {
                                            $testamentArr    = $testamentData;
                                            $image_link      = $image;
                                            $tdata[]         = array_merge($testamentArr, array(
                                                                    'type'      => 'image',
                                                                    'file_link' => $image_link
                                                                   )
                                                               );  
                                        }  
                                        $testamentData   = $tdata;
                                    }
                                }
                                if(!empty($audio))
                                {
                                    $audio_link     = $audio;
                                    $testamentData  = array_merge($testamentData, array(
                                                        'type'         => 'audio',
                                                        'file_link'    => $audio_link
                                                        )
                                                    );                                            
                                }
                                if(!empty($video))
                                {
                                    $video_link     = $video;
                                    $testamentData  = array_merge($testamentData, array(
                                                        'type'        => 'video',
                                                        'file_link'   => $video_link
                                                        )
                                                    );
                                }
                                if(!empty($pdf))
                                {
                                    $pdf_link       = $pdf;
                                    $testamentData  = array_merge($testamentData, array(
                                                        'type'      => 'Pdf',
                                                        'file_link' => $pdf_link
                                                      )
                                                    );
                                }                   
                                $this->InsertMultipleRows('wa_testaments_files',$testamentData);
                                $eventSendDetailDataArr = array(                         
                                                            'user_id'            => $userId,
                                                            'testament_id'       => $testamentRow,
                                                            'creation_date'      => date('Y-m-d H:i:s'),
                                                            'vital_check_type'   => Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT,
                                                            'is_status'          => 0, 
                                                            'event_status'       => '1'
                                                        );
 				$count = 0;
                                if(!empty($vital_check_set)) {
                                    foreach ($vital_check_set as $row) { 
                                            $eventSendDetailData[]  = array_merge($eventSendDetailDataArr,array('usertype'=>'1','event_send_date' => $row,'event_vital_value'=> $vital_check[$count++]));
                                    }
                                    $eventSendDetailData[] = array_merge($eventSendDetailDataArr,array('usertype'=>'2','event_send_date' => $trustee_alert_time[0],'event_vital_value'=>  $trustee_alert_set_time[0]));
                                } 
                                $eventSendDetailRow    = false; 
                                (count($vital_check_set)>0)?$this->InsertMultipleRows('wa_event_send_details', $eventSendDetailData):"";
                                if($testamentRow)
                                {
                                    if($is_existing_testament)
                                    {
                                        $waReceiverTable->deleteTestamentRecievers($testamentRow);                                
                                        $waTrusteeTable->deleteTestamentReceivers($userId,$testamentRow);
                                    }
                                    if(!empty($receiver_userset)) 
                                    {     
                                        $receiver_userset = json_decode(urldecode($receiver_userset),true);
                                        foreach ($receiver_userset as $receiverId)
                                        {
                                            $waReceiverData   = array(
                                                        'testament_id'   => $testamentRow,
                                                        'receiver_id'  => $receiverId
                                                    );
                                            if($receiverId != $userId){
                                                $waReceiverRow = $waReceiverTable->createRow($waReceiverData);
                                                $waReceiverRow->save();
                                            }
                                        }
                                    }
                                    if(!empty($receiver_trusteeset)) 
                                    {
                                        $receiver_trusteeset = json_decode(urldecode($receiver_trusteeset),true);
                                        foreach ($receiver_trusteeset as $receiverId)
                                        {
                                            $waTrusteeData  = array(
                                                                'testament_id' => $testamentRow,
                                                                'user_id'      => $userId,
                                                                'receiver_id'  => $receiverId
                                                            );
                                            if($receiverId != $userId){
                                                $waTrusteeRow = $waTrusteeTable->createRow($waTrusteeData);
                                                $waTrusteeRow->save();
                                            }
                                        }
                                    }
                                    $spendCreditData    = array(
                                                            'credit_type' => 2,
                                                            'userId'     => $userId,
                                                            'credits'    => $total_paid,
                                                        );

                                   // $waCreditsTable->spendCredits($spendCreditData); 
                                   $result  = array('pdf_url' => $return_url['new_file_name'],'testament_id' => $testament_id);
                               }     
                            }else{
                                $this->common->displayMessage($this->view->translate('vital_check_count'), '1', array(), '116');
                            }
                         }else{
                            $this->common->displayMessage($this->view->translate('witness_not_exist'), '1', array(), '118');
                         }
                        }else{
                            $this->common->displayMessage($this->view->translate('testament_id_not_exist'), '1', array(), '110');
                        }
                    }
                    else
                    {
                       $url     = $this->makeUrl($pdf_url);
                       $result  = array('pdf_url' => $url,'testament_id' => $testament_id);
                    } 
                 } catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
              $db->commit();
              $this->common->displayMessage($this->view->translate('testament_created_success'),"0",$result,"0");   
            }
            else 
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(),'3');
        }
    }
       
    public function getTestamentDetailsByIdAction()
    {
        $waUserTable            = new Application_Model_DbTable_Users();
        $waTestamentTable       = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        $waTestmentfilesTable   = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable       = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable  = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable     = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable          = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable          = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable   = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable         = new Application_Model_DbTable_WaTestamentWitnesses();
       
        $decoded                = $this->common->Decoded();       
        $userId                 = $decoded['userId'];
        $userDeviceId           = $decoded['userDeviceId'];
        $userDeviceToken        = $decoded['userDeviceToken'];
        $userSecurity           = $decoded['userSecurity'];
        $testament_id           = $decoded['testament_id'];
        $deviceLanguage         = $decoded['deviceLanguage'];       
        $this->user->setLanguage($deviceLanguage);    

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->IsUserLogin($userId,$userDeviceId);
            
            if(($userRow = $waUserTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($testamentRow  = $waTestamentTable->getRowById($testament_id))
                    {
                        $waOwnerRow     = $waUserTable->getRowById($userId);
                        
                        $representative_set        = array();
                        $bank_account_set          = array();
                        $social_account_set        = array();
                        $testaments_estate_set     = array();
                        $testaments_others_set     = array();
                        $testaments_witness_set    = array();
                        $receiver_userset          = array();
                        $receiver_names            = array();
                        $receiver_email_phoneset   = array();
                        $attachment_files          = array();                      
                        if($representiveArr  = $waRepresentativeTable->getRepresentatives($testament_id))   
                        {  
                            if(!empty($representiveArr))
                            {
                                foreach ($representiveArr as $row)
                                {
                                    $representative_set[]   = array(
                                                                'testament_id'     => $testamentRow,
                                                                'res_name'         => $row['res_name'],
                                                                'res_gender'       => $row['res_gender'],
                                                                'res_address1'     => $row['res_address1'],
                                                                'res_address2'     => $row['res_address2'],
                                                                'res_city'         => $row['res_city'],
                                                                'res_postcode'     => $row['res_postcode'],
                                                                'res_country'      => $row['res_country'],
                                                                'res_wauseremail'  => $row['res_wauseremail'],
                                                                'res_phone_number' => $row['res_phone_number']
                                                            );
                                }
                            }
                        }                       
                        if($bankAccountArr  = $waBankAccountTable->getBankAccounts($testament_id))
                        {
                            if(!empty($bankAccountArr))
                            {
                                foreach ($bankAccountArr as $row)
                                {
                                    $bank_account_set[] = array(
                                                            'type'                  => $row['type'],
                                                            'bank_name'             => $row['bank_name'],
                                                            'bank_address'          => $row['bank_address'],
                                                            'bank_account_number'   => $row['bank_account_number']
                                                        );
                                }
                            }
                        }
                  
                        if($socialAccountArr = $waSocialAccountTable->getSocialAccounts($testament_id))
                        {
                            if(!empty($socialAccountArr))
                            {
                                foreach ($socialAccountArr as $row)
                                {
                                    $social_account_set[]   = array(
                                                                'url'                => $row['url'],
                                                                'username'           => $row['username'],
                                                                'password'           => $row['password'],
                                                                'security_ques'      => $row['security_ques'],
                                                                'is_status'          => $row['is_status'],
                                                                'managed_username'   => $row['managed_username'],
                                                            );
                                }                                
                            }
                        }
                        
                        if($estateDividerArr = $waEstateTable->getEstateDividations($testament_id))
                        {
                            if(!empty($estateDividerArr))
                            {
                                foreach ($estateDividerArr as $row)
                                {
                                    $testaments_estate_set[]    = array(  
                                                                    'item_name'         => $row['item_name'],
                                                                    'beneficiairy'      => $row['beneficiairy'],
                                                                    'percentage'        => $row['percentage'],
                                                                );
                                }
                            }
                        }
                        
                        if($othersArr = $waOthersTable->getOthersSet($testament_id))
                        {
                            if(!empty($othersArr))
                            {
                                foreach ($othersArr as $row)
                                {
                                    $testaments_others_set[]    = array(                                        
                                                                        'type'          => $row['type'],
                                                                        'description'   => $row['description'],
                                                                 );
                                }
                            }
                        }
                        
                        if($witnessArr = $waWitnessTable->getWitnesses($testament_id))
                        {
                            if(!empty($witnessArr))
                            {
                                foreach ($othersArr as $row)
                                {
                                    $testaments_witness_set[]   = array(
                                                                        'name'              => $row['name'],
                                                                        'username'          => $row['username'],
                                                                        'id_type'           => $row['witsness_idtype'],
                                                                        'id_number'         => $row['witness_id_number'],
                                                                    );
                                    }
                            }
                        }
                   
                        if($attachmentsArr = $waTestmentfilesTable->getFilesByTestamentId($testament_id))
                        {
                            if(!empty($attachmentsArr))
                            {
                                foreach ($attachmentsArr as $row)
                                {
                                    $attachment_files[] = array(
                                                           'type'       => $row['type'],
                                                           'file_link'  => $row['file_link']
                                                        );  
                                }
                            }
                        }
                      
                        if($testamentReciverRow = $waReceiverTable->getRowByTestamentIdAndReceiverId($testament_id, $userId))
                        {
                            $testamentReceiverRow->is_read = "1";
                            $testamentReceiverRow->save();
                        }

                        $testamentReceiverRowset = $waReceiverTable->getTestamentRecievers($testament_id);
                        
                        if(!empty($testamentReceiverRowset))
                        {
                            foreach($testamentReceiverRowset as $testamentReceiverRow)
                            {
                                if($testamentReceiverRow->receiver_id){

                                    $receiver_userset[]    = array(
                                                                'userId'        => $testamentReceiverRow->receiver_id,
                                                                'userNickName'  => ($testamentReceiverRow->userNickName)?$testamentReceiverRow->userNickName:"",
                                                                'userImage'     => ($testamentReceiverRow->userImage)?$this->makeUrl($testamentReceiverRow->userImage):""
                                                            );
                                }else{
                                    $receiver_names[]       = array(
                                                                    'name'   => ($testamentReceiverRow->receiver_name)?$testamentReceiverRow->receiver_name:"",
                                                                    'email'  => ($testamentReceiverRow->receiver_email)?$testamentReceiverRow->receiver_email:"",
                                                                    'phone'  => ($testamentReceiverRow->receiver_phone)?$testamentReceiverRow->receiver_phone:""
                                                            );
                                }
                            }
                        }
                        
                        $testamentTrusteesRowset    = array(); //$waTrusteeTable->getWaReceivers($testament_id);

                        $data       =   array(  
                                            'testament_id'        => $testamentRow->testament_id,
                                            'full_name'           => $testamentRow->full_name,
                                            'gender'              => $testamentRow->gender,
                                            'address1'            => $testamentRow->address1,
                                            'address2'            => $testamentRow->address2,
                                            'postcode'            => $testamentRow->postcode,
                                            'city'                => $testamentRow->city,
                                            'country'             => $testamentRow->country,
                                            'nationality'         => $testamentRow->nationality,
                                            'id_type'             => $testamentRow->id_type,
                                            'id_number'           => $testamentRow->id_number,
                                            'wa_username'         => $testamentRow->wa_username,
                                            'birth_certificate_place' => $testamentRow->birth_certificate_place,
                                            'life_insurance_policies' => $testamentRow->life_insurance_policies,
                                            'deposit_account_books'   => $testamentRow->deposit_account_books,
                                            'other_property'          => $testamentRow->other_property,
                                            'testament_special_request' => $testamentRow->testament_special_request,
                                            'total_paid'             => $testamentRow->total_paid,
                                            'ip_address'             => $testamentRow->ip_address,
                                            'latitude'               => $testamentRow->latitude,   
                                            'longitude'              => $testamentRow->longitude, 
                                            'representative_set'     => $representative_set,
                                            'bank_account_set'       => $bank_account_set,
                                            'testaments_others_set'  => $testaments_others_set,
                                            'testaments_estate_set'  => $testaments_estate_set,
                                            'social_account_set'     => $social_account_set,
                                            'testaments_witness_set' => $testaments_witness_set,   
                                            'receiver_userset'       => $receiver_userset,
                                            'receiver_names'         => $receiver_names,
                                            'attachment_files'       => $attachment_files,
                                            'created_pdf_link'       => $testamentRow->created_pdf_link ? $this->makeUrl($testamentRow->created_pdf_link):"",
                                        );
                        $this->common->displayMessage($this->view->translate('testament_detail'),"0",$data,"0"); 
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('testament_id').' '.$this->view->translate('not_exist'),"1",array(),"110"); 
                    }
                }
                catch(Exception $ex){
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }

    public function inviteWitnessAction()
    {     
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded                 = $this->common->Decoded();  
        $userId                  = $decoded['userId']; 
        $userDeviceId            = $decoded['userDeviceId'];
        $userDeviceToken         = $decoded['userDeviceToken'];
        $userSecurity            = $decoded['userSecurity'];
        $gender                  = $decoded['gender'];
        $full_name               = $decoded['full_name'];
        $address1                = $decoded['address1'];
        $address2                = $decoded['address2'];
        $phone_number            = $decoded['phone_number'];
        $witness_set             = $decoded['witness_set'];
        $deviceLanguage          = $decoded['deviceLanguage'];
        $testament_id            = "";
        
        $this->user->setLanguage($deviceLanguage);    
        
        if(isset($decoded['testament_id'])) {
            $testament_id       = $decoded['testament_id'];
        }
      
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
       
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {  
                $rowcount  = $witnessTable->CheckWitnessExists($testament_id,$userId,$witness_set['username']);
               
                if(count($rowcount) < 1)
                {            
                    if(empty($testament_id))
                    {   
                        $data     =   array(
                                            'user_id'       => $userId,
                                            'full_name'     => $full_name,
                                            'gender'        => $gender,
                                            'address1'      => $address1,
                                            'address2'      => $address2,
                                            'phone_number'  => $phone_number,
                                            'creation_date' => date('Y-m-d H:i:s'),
                                            'is_status'     => 2
                                        );
                       
                        $testament_id   = $waTestamentsTable->saveTestament($data);
                    }
                    $uri            =  $this->baseUrl() ."/wa-testament/send-request";

                    $db = $this->db;
                    $db->beginTransaction();

                    try
                    {                
                        if(!empty($witness_set))
                        {
                            $witnessArr  =  array(
                                                'testament_id'      => $testament_id,
                                                'user_id'           => $userId,
                                                'name'              => $witness_set['name'],
                                                'username'          => $witness_set['username'],
                                                'id_type'           => $witness_set['id_type'],
                                                'id_number'         => $witness_set['id_number'],
                                                'address'           => $witness_set['address'],
                                                'phone'             => $witness_set['phone'],
                                                'creation_date'     => date('Y-m-d H:i:s'),
                                                'modification_date' => date('Y-m-d H:i:s'),
                                                'is_status'         => 0
                                            );
                            
                            $lastInsertId    = $witnessTable->saveTestamentWitnesses($witnessArr);
                            $data            = array('witness_id' => $lastInsertId, 'username'=> $witness_set['username'], 'testament_id' => $testament_id);  
                            $this->curlRequestAction($uri, $witnessArr); 
                        
                            $senderRow        = $userTable->getRowByEmail($witness_set['username']);  
                            $message          = $full_name.' '.$this->view->translate('send_witness_request');
                        
                            $notificationData = array(
                                                    'user_id'      => $senderRow->userId,
                                                    'from_user_id' => $userId,
                                                    'message'      => $message
                                                ); 
                            $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::INVITE_WITNESS_REQUEST);
                        }  
                        else
                        {
                            $this->common->displayMessage($this->view->translate('select_witness_for_request'),'1',array(),'112');
                        }
                    }
                    catch (Exception $ex) {
                        $db->rollBack();
                        $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                    }
                    $db->commit(); 
                    $this->common->displayMessage($this->view->translate('send_request_success'),"0",$data,"0"); 
                }
                else
                {    
                   $this->common->displayMessage($this->view->translate('already_send_request'),'1',array(),'114');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1', array(), '3');
        }
    }
    
    public function curlRequestAction($uri,$arr)
    {
        $ch     = curl_init();
        $url    = $uri;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,FALSE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $go     = curl_exec($ch);
        $result = json_decode($go,TRUE);    
        curl_close($ch);
    }

    public function sendRequestAction()
    {           
        $userTable       = new Application_Model_DbTable_Users();
        $alert_type      = "wa_testament_witness_request";
        $body            = $this->getRequest()->getRawBody();
        $postDataArr     = json_decode($body, true);
        if(!empty($postDataArr))
        {
            $msg           = $this->view->translate('send_witness_request');
            $this->getUserDeviceDetailsAction($postDataArr, $msg, $alert_type);        
        }
        exit();
    }
    
    public function updateWitnessDetailAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $witnessTable             = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded                  = $this->common->Decoded();
        $userId                   = $decoded['userId']; 
        $userDeviceId             = $decoded['userDeviceId'];
        $userDeviceToken          = $decoded['userDeviceToken'];
        $userSecurity             = $decoded['userSecurity'];
        $witness_id               = $decoded['witness_id'];
        $id_type                  = $decoded['id_type'];
        $id_number                = $decoded['id_number'];
        $deviceLanguage           = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);    
             
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                if($witness_id)
                {
                    $witnessRow = $witnessTable->getRowById($witness_id);
                }
                
                $data    = array(
                                'witness_id' => $witness_id,
                                'id_type'    => $id_type,
                                'id_number'  => $id_number
                            );   
                           
                try
                {
                    if($witnessRow)
                    {
                        $witnessRow->setFromArray($data);
                        $witnessRow->save();    
                    }
                    
                    $userRow1         = $userTable->getRowByEmail($witnessRow->username);                        
                    $witnessRow1      = $userTable->getRowById($witnessRow->user_id);
                    $postData         = array(
                                             'user_id'   => $userRow1->userId,
                                             'username'  => $witnessRow1->userEmail
                                        );
                    $alert_type       = "wa_testament_witness_update_request";
                    $msg              = $this->view->translate('update_witness_request');
                    $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);   
                    
                    $message          = $userRow1->userNickName.' '.$msg;
                    $notificationData = array(
                                            'user_id'      => $userRow1->userId,
                                            'from_user_id' => $witnessRow->user_id,
                                            'message'      => $message
                                        );
                    $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::UPDATE_WITNESS_REQUEST);
                }  
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
                $this->common->displayMessage($this->view->translate('testament_updated_success'),'0',$data,'0');
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }

    public function confirmWitnessAction()
    {         
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $witnessTable             = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessAnswersTable      = new Application_Model_DbTable_WaTestamentAnswers();
        $usertNotificationsTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded                   = $this->common->Decoded();
        $userId                    = $this->getRequest()->getPost('userId');
        $userDeviceId              = $this->getRequest()->getPost('userDeviceId');
        $userDeviceToken           = $this->getRequest()->getPost('userDeviceToken');
        $userSecurity              = $this->getRequest()->getPost('userSecurity');
        $witness_id                = $this->getRequest()->getPost('witness_id','');
        $testament_id              = $this->getRequest()->getPost('testament_id','');
        $question_set              = $this->getRequest()->getPost('question_set','');
        $recorded_file_link        = $this->getRequest()->getPost('recorded_file_link','');
        $ip_address                = $this->getRequest()->getPost('ip_address','');
        $latitude                  = $this->getRequest()->getPost('latitude','');
        $longitude                 = $this->getRequest()->getPost('longitude','');
        $address                   = $this->getRequest()->getPost('address','');
        $modification_date         = $this->getRequest()->getPost('modification_date','');
        $deviceLanguage            = $this->getRequest()->getPost('deviceLanguage','');
       
        $this->user->setLanguage($deviceLanguage);    
      
        if($userSecurity == $this->servicekey) 
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                $witnessRow = FALSE;                
                if(!empty($witness_id))
                {   
                    if(!empty($testament_id))
                    {
                        $witnessRow    = $witnessTable->getRowById($witness_id);
                        
                        if($witnessRow['is_status'] == 0) 
                        {    
                            $data       = array(
                                            'ip_address'    => $ip_address,
                                            'latitude'      => $latitude,
                                            'longitude'     => $longitude,
                                            'modification_date' => date('Y-m-d H:i:s'),
                                            'is_status'     => 1
                                        );
           
                            $db = $this->db;
                            $db->beginTransaction();    

                            try
                            {       
                                if(!empty($recorded_file_link))
                                {
                                    $data   = array_merge($data, array(
                                                                        'recorded_file_link'  => $recorded_file_link
                                                                   )
                                                                );                       
                                }

                                if(!empty($_FILES['signature_image']) && count($_FILES['signature_image']))
                                {
                                    $signature_image_link  = $_FILES['signature_image'];                                 
                                    $response   = $this->common->upload($signature_image_link,'signature_images');
                                    
                                    if(isset($response))
                                    {
                                        $signature_image_link   = "testaments/signature_images/".$response['new_file_name'];
                                        $data                   = array_merge($data, array(
                                                                    'signature_image_link' => $signature_image_link
                                                                    )                                            
                                                                );
                                    }
                                }
                                else
                                {
                                    if($witnessRow && $witnessRow->signature_image_link)
                                    {
                                        $witnessRow->signature_image_link = "";
                                    }
                                }
                            
                                if(!empty($question_set)){
                                    $answersRows = $witnessAnswersTable->getAnswersByWitnessId($witness_id);

                                    if(count($answersRows) > 0) {
                                          $witnessAnswersTable->deleteAnswers($witness_id);                    
                                    }
                                    
                                    if(is_array($question_set))
                                    {
                                        foreach ($question_set as $row){
                                            $answer = array_merge($row, array('witness_id' => $witness_id));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer); 
                                        }
                                    }
                                    else
                                    {
                                        $question_set = json_decode($question_set);
                                    
                                        foreach ($question_set as $row){
                                            $arr = (array) $row;
                                            $answer = array_merge($arr, array('witness_id' => $witness_id));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer); 
                                        }
                                    }
                                }
                                
                                if($witnessRow)
                                {
                                    $witnessRow->setFromArray($data);
                                    $witnessRow->save();                        
                                }
                                else
                                {
                                    $witessesTable->saveTestamentWitnesses($data);                        
                                }    

                                $witnessRow    = $witnessTable->getRowById($witness_id);
                                $testamentRow  = $waTestamentsTable->getRowById($testament_id);
                                $userRow1      = $userTable->getRowById($witnessRow['user_id']);
                                
                                $gender             = $testamentRow['gender'];
                                $is_gender          = "";
                                $is_gender1         = "";
                                $is_gender2         = "";   

                                if($gender == $this->view->translate('TESTATOR'))
                                {
                                    $is_gender  = $this->view->translate('his');   
                                    $is_gender1 = $this->view->translate('him');
                                    $is_gender2 = $this->view->translate('he');
                                }
                                else
                                {
                                    $is_gender  = $this->view->translate('her');
                                    $is_gender1 = $this->view->translate('her');
                                    $is_gender2 = $this->view->translate('she');
                                }

                                $resultData          = array_merge($data, array(
                                                            'full_name'              => $testamentRow['full_name'],
                                                            'address1'               => $testamentRow['address1'],
                                                            'address2'               => $testamentRow['address2'],
                                                            'wa_username'            => $userRow1['userEmail'],
                                                            'phone_number'           => $testamentRow['phone_number'],
                                                            'name'                   => $witnessRow['name'],
                                                            'username'               => $witnessRow['username'],
                                                            'id_type'                => $witnessRow['id_type'],
                                                            'id_number'              => $witnessRow['id_number'],
                                                            'modification_date'      => $witnessRow['modification_date'],
                                                            'address'                => $address,
                                                            'gender'                 => $gender,
                                                            'is_gender'              => $is_gender,
                                                            'is_gender1'             => $is_gender1,
                                                            'is_gender2'             => $is_gender2,
                                                            'recorded_file_link'     => $recorded_file_link,
                                                            'signature_image'        => $this->makeUrl($witnessRow['signature_image_link'])
                                                            )
                                                        );

                                $this->view->data       = $resultData;

                                $content = $this->view->render('wa-testament/witness-pdf.phtml');
                                $header  = '<div class="pdf-header">'.$this->view->translate('wa_testament_witness').'</div>';
                                $footer  = '<div class="pdf-footer" style="margin-top: 28px;">'.$this->view->translate('witness_pdf_footer').'<span>{PAGENO} / {nb} </span></div>';
                                $pdf_url =  $this->common->createMpdf($header,$content,$footer); 

                                $response  = $this->common->uploadTestament($pdf_url);
                                $result    = array();
                                if(isset($response))
                                {
                                    $result     = array(
                                                    'testament_id'         => $testament_id,
                                                    'signature_pdf_link'   => $response['new_file_name']
                                                  );
                                    
                                    $witnessRow->setFromArray($result);
                                    $witnessRow->save();   
                                }
                               
                                $userRow1         = $userTable->getRowByEmail($witnessRow->username);                        
                                $witnessRow1      = $userTable->getRowById($witnessRow->user_id);
                                $postData         = array(
                                                         'user_id'   => $userRow1->userId,
                                                         'username'  => $witnessRow1->userEmail
                                                    );
                                $alert_type       = "wa_testament_witness_request_result";
                                $msg              = $this->view->translate('accepted_witness_request');
                                $this->getUserDeviceDetailsAction($postData, $msg, $alert_type); 
                             
                                $message          = $userRow1->userNickName.' '.$msg;
                                $notificationData = array(
                                                        'user_id'      => $userRow1->userId,
                                                        'from_user_id' => $witnessRow->user_id,
                                                        'message'      => $message
                                                    );
                                $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::CONFIRM_WITNESS_REQUEST);

                                $result      =  array(
                                                    'witness_id'   => $witness_id,
                                                    'testament_id' => $testament_id,
                                                    'wintess_pdf'  => $response['new_file_name']
                                                ); 
                               // exit;
                            }
                            catch (Exception $ex)
                            {
                                $db->rollBack();
                                $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                            }
                           $db->commit();
                           $this->common->displayMessage($this->view->translate('witness_request_accepted'),'0',$result,'0'); 
                        }
                        else
                        {
                            $this->common->displayMessage($this->view->translate('already_request_accepted'),'1',array(),'120');
                        }
                    }
                    else
                    {
                       $this->common->displayMessage($this->view->translate('testament_id').' '.$this->view->translate('not_exist'),'1',array(),'110');
                    }
                }
                else
                {
                  $this->common->displayMessage($this->view->translate('witness_id').' '.$this->view->translate('not_exist'),'1',array(),'122');
                }
            } 
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }                   
    }
    
    public function cancelWitnessAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTestamentsTable  = new Application_Model_DbTable_WaTestaments();
        $witnessTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $witness_id         = $decoded['witness_id'];
        $deviceLanguage     = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);    
        
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                $data    = array(
                                'witness_id' => $witness_id,
                                'is_status' => '2',
                            );
                    
                $db = $this->db;
                $db->beginTransaction();
                
                try
                {         
                    if($witness_id) {
                        $witnessRow     = $witnessTable->getRowById($witness_id);
                    }
                  
                    if($witnessRow)
                    {
                        $witnessRow->setFromArray($data);
                        $witnessRow->save();
                     
                        $userRow1         = $userTable->getRowByEmail($witnessRow->username);                        
                        $witnessRow1      = $userTable->getRowById($witnessRow->user_id);
                        $postData         = array(
                                                 'user_id'   => $userRow1->userId,
                                                 'username'  => $witnessRow1->userEmail
                                             );
                        $alert_type      = "wa_testament_witness_cancel_request";
                        $msg              = $this->view->translate('cancel_witness_request');
                        $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);
                         
                        $message          = $userRow1->userNickName.' '.$msg;
                        $notificationData = array(
                                                'user_id'      => $userRow1->userId,
                                                'from_user_id' => $witnessRow->user_id,
                                                'message'      => $message
                                            ); 
                        $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::REJECT_WITNESS_REQUEST);
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('witness_id').' '.$this->view->translate('not_exist'),'1',array(),'122');
                    }
                }
                catch (Exception $ex)
                {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $db->commit();
               $this->common->displayMessage($this->view->translate('witness_request_rejected'),'0',$data,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function getUserDeviceDetailsAction($arr,$msg,$alert_type)
    { 
        $userTable          = new Application_Model_DbTable_Users();
        $userSettingTable   = new Application_Model_DbTable_UserSetting();
  
        $userId             = $arr['user_id'];
        $username           = $arr['username'];
        $witnessRow         = $userTable->getRowById($userId);
        $userRow            = $userTable->getRowByEmail($username);  
      
        if($userRow && $userRow->isActive()) 
        {
            $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($userRow->userId);            
            $message            = $witnessRow->userNickName." ".$msg;
            
            if(!empty($userLoginDeviceSet))
            {
                foreach ($userLoginDeviceSet as $loginDeviceRow)
                {
                    if($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken)
                    {
                        if($loginDeviceRow->userDeviceType == "iphone")
                        {
                           $payload['aps']   = array(
                                                'alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => $alert_type, 'message' => $message
                                              );
                        }
                        else
                        {  
                            $resultData     = array(
                                                 'userImage'  => ($userRow->userImage)?$this->makeUrl($userRow->userImage):"",
                                                 'userId'     => $userRow->userId,
                                                 'userName'   => $userRow->userNickName
                                              );

                            $payload         = array(
                                                 'message'   => $message,
                                                 'type'      => $alert_type,
                                                 'result'    => $resultData    
                                               );

                            $payload = json_encode($payload);  
                        }                   
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType,$loginDeviceRow->userDeviceToken,$payload);  
                    }              
                }
            } 
        }
    }
    
    public function getAllReceivedWitnessRequestAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTestament        = new Application_Model_DbTable_WaTestaments();
        $witnessTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessQuestions   = new Application_Model_DbTable_WaTestamentQuestions();
       
        $decoded            = $this->common->Decoded();
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);    
          
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    $witnessRows = $witnessTable->getAllWitnessesByUserEmail($userRow['userEmail']);
                    $questions   = $witnessQuestions->getQuestions($deviceLanguage);
                    $witnessArr  = array();
                    
                    if(!empty($witnessRows))
                    {       
                        foreach ($witnessRows as $row)
                        {
                            $testamentRow = $waTestament->getTestamentRowByUserId($row['testament_id'], $row['user_id']);
                            if($testamentRow['gender'] == $this->view->translate('TESTATOR'))
                            { 
                                $gender     = $this->view->translate('TESTATOR');
                                $is_gender  = $this->view->translate('he');
                                $is_gender1 = $this->view->translate('his');
                            }
                            else 
                            {
                                $gender     = $this->view->translate('TESTATRIX');
                                $is_gender  = $this->view->translate('she');
                                $is_gender1 = $this->view->translate('her');
                            }
                            $full_name      = ucfirst($row['full_name']);
                            $id_type        = ucfirst($row['id_type']);
                            $id_number      = $row['id_number'];
                         
                            foreach($questions as $ques)
                            {
                                $searchReplaceArray = array(
                                                        '{username}' => $full_name, 
                                                        '{gender}'   => $gender,
                                                        '{he/she}'   => $is_gender,
                                                        '{his/her}'  => $is_gender1,
                                                        '{id_type}'  => $id_type,
                                                        '{id_number}'=> $id_number
                                                      );
                                $result             = str_replace(
                                                        array_keys($searchReplaceArray), 
                                                        array_values($searchReplaceArray), 
                                                        $ques['question']
                                                      );
                                
                                $arr[]              = array(
                                                        'ques_id' => $ques['id'],
                                                        'question' => $result,
                                                        'is_status' => $ques['is_status']
                                                    );        
                            }
                          $witnessArr[]   = array_merge($row, array('questions' => $arr));         
                          $arr = array();
                        }                    
                    }
                  $this->common->displayMessage($this->view->translate('records_found'),'0',$witnessArr,'0');
                }  
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function getAllSendWitnessRequestAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTestament        = new Application_Model_DbTable_WaTestaments();
        $witnessTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded            = $this->common->Decoded();
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);   

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    $witnessRows = $witnessTable->sendAllWitnessesByUserId($userId);
                    
                    $this->common->displayMessage($this->view->translate('records_found'),'0',$witnessRows,'0');
                }  
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }        
    }
    
    public function updateEventSendDetailAction()
    {            
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $rowId               = $decoded['event_id'];
        $response            = $decoded['event_response'];
        $deviceLanguage      = $decoded['deviceLanguage'];
        $resultArr	     = "";
	$result 	     = array(); 
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($rowId)
                    { 
                        $EventSendDetailRow  = $waEventSendDetailsTable->getRowById($rowId); 
                    }
                    
                    if($EventSendDetailRow)
                    {  
                        $currentDate = date('Y-m-d H:i:s'); 
                        $eventRows = $waEventSendDetailsTable->getAllEventSendRowByUserId($userId);  
			$testament_id = $EventSendDetailRow->testament_id;
			$creation_date = date('Y-m-d H:i:s');
			$vital_check_type = Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT;
			
                        if ($response == 0)
                        { 
                            foreach($eventRows as $event){                         
                                if($event->usertype == '1' && $event->id == $rowId) {
                                  $eventData = array('id'=> $event->id,'event_response'=> $response,'is_status'=>'1','event_status'=>'1');    
                                  $resultArr = $waEventSendDetailsTable->updateEventSendTable($eventData);
                                } else { 
                                    if($event->usertype == '1') {
                                      $eventData1  = array('id'=> $event->id,'event_response' => '2','is_status'=>'0','event_status'=>'0');    
                                      $waEventSendDetailsTable->updateEventSendTable($eventData1);
                                    } else {
                                      $event_send_date = date('Y-m-d H:i:s', strtotime('+1 min'));
                                      $eventData2 = array('id'=> $event->id, 'event_send_date' => $event_send_date,'is_status'=>'0','event_status'=>'1');
                                      $resultArr3 =  $waEventSendDetailsTable->updateEventSendTable($eventData2);
                                   }
                                  $waTestamentsTable->sendVitalCheckUsers($event->testament_id,'1');
                                }                                   
                            }
                         
                        }
                        $result     =  array('event_id' => $resultArr);       
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('event_id_not_exist'), '1', array(), '124');
                    }
                } 
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
               $this->common->displayMessage($this->view->translate('vital_check_saved'), '0', $result, '0'); 
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        }
    }
    
    public function eventAutoResetVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $deviceLanguage      = $decoded['deviceLanguage'];
        $reset_vital_check   = $decoded['reset_vital_check'];
        $resultArr	     = array();
	$result 	     = array(); 
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    $vital_check_type = Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT;
                    $currentDate = date('Y-m-d H:i:s'); 
                    $eventRow    = $waEventSendDetailsTable->getLastRowByUserIdAndVitalType($userId,$vital_check_type);  
                    $eventVitalCheckRows = $waEventSendDetailsTable->getAllEventSendRowByUserId($userId);  
                    $testament_id = $eventRow->testament_id;
                    //$creation_date = date('Y-m-d H:i:s');

                    if ($reset_vital_check == 1)
                    {
                        foreach($eventVitalCheckRows as $event){
                            $after = array($event->event_vital_value);
                            $event_send_date  =  $this->convertDateAction($currentDate, $after);
                            $eventData   = array('id'=> $event->id, 'event_send_date' => $event_send_date[0], 'event_response' => '2', 'is_status' => '0', 'event_status' => '1');
                            $resultArr[] = $waEventSendDetailsTable->updateEventSendTable($eventData);                                 
                        }
                        if(!empty($event->testament_id)) {
                            $waTestamentsTable->sendVitalCheckUsers($event->testament_id,'0');
                        }
                    }
                    $result     =  array('event_id' => $resultArr);       
                } 
                catch (Exception $e)
                {
                    $this->common->displayMessage($e->getMessage(), '1', array(), '12');
                }
               $this->common->displayMessage($this->view->translate('reset_vital_check'), '0', $result, '0'); 
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        } 
    }
    
    public function eventNewResetVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
  
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $deviceLanguage      = $decoded['deviceLanguage'];
        $new_vital_check     = $decoded['new_vital_check'];
        $resultArr	     = array();
	$result 	     = array(); 
	$resultArr1  	     = array(); 
 	$resultArr2          = array();
  
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                 
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    $vital_check_type = Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT;
                    $currentDate = date('Y-m-d H:i:s'); 
                    $eventRow    = $waEventSendDetailsTable->getLastRowByUserIdAndVitalType($userId,$vital_check_type);  
                    $eventVitalCheckRows = $waEventSendDetailsTable->getAllEventSendRowByUserId($userId);  
                    $testament_id = $eventRow->testament_id;
                    $creation_date = date('Y-m-d H:i:s');
                   
                    if(!empty($new_vital_check))
                    {   
                        $eventData  = array(
                                        'user_id' => $userId,
                                        'testament_id' => $testament_id,
                                        'creation_date' => $creation_date,
                                        'vital_check_type' => $vital_check_type
                                    ); 
                        $waEventSendDetailsTable->deleteEventByUserIdAndVitalType($userId,$vital_check_type);
                        $count = 0;
                        $vital_check_arr  = $new_vital_check['vital_check'];
                        if(count($vital_check_arr) > 0) {
                            $event_send_date  =  $this->convertDateAction($currentDate,$vital_check_arr);
                            foreach($event_send_date as $eventSend) {
                                $eventData  = array_merge($eventData,array(
                                                'usertype' => '1',					
                                                'event_send_date' => $eventSend,
                                                "event_vital_value" => $vital_check_arr[$count++]
                                              )
                                            );
                                $resultArr1[] = $waEventSendDetailsTable->saveEventSendTable($eventData);
                            }
                        }  
                        $count1 = 0; 
                        $trsutee_vital_check  = $new_vital_check['trustee_alert_time'];
                        if(count($trsutee_vital_check) > 0) {
                            $event_send_date  =  $this->convertDateAction($currentDate,$trsutee_vital_check);
                            $eventData   =  array_merge($eventData,array(
                                                'usertype' => '2',					
                                                'event_send_date' =>  $event_send_date[$count1],
                                                "event_vital_value" => $trsutee_vital_check[$count1]
                                                )
                                            );
                          $resultArr2[] = $waEventSendDetailsTable->saveEventSendTable($eventData);			     
                        }
                        if(!empty($eventRow->testament_id)) {
                            $waTestamentsTable->sendVitalCheckUsers($eventRow->testament_id,'0');
                        }
                    }
                    $resultArr  = array_merge($resultArr1,$resultArr2);    
                    $result     = array('event_id' =>  $resultArr);     
                } catch (Exception $e) {
                    $this->common->displayMessage($e->getMessage(), '1', array(), '12');
                }
               $this->common->displayMessage($this->view->translate('new_vital_check_saved'), '0', $result, '0'); 
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        } 
    }
    
    public function trusteeResponseAction()
    {
  	$userTable                   = new Application_Model_DbTable_Users();
        $waEventSendDetailsTable     = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeReponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $decoded             = $this->common->Decoded();        
        $userId              = $decoded['userId'];
        $userDeviceId        = $decoded['userDeviceId'];
        $userDeviceToken     = $decoded['userDeviceToken'];
        $userSecurity        = $decoded['userSecurity'];
        $event_id            = $decoded['event_id'];
        $testament_id        = $decoded['testament_id']; 
        $trustee_id          = $decoded['trustee_id'];
        $event_response      = $decoded['event_response'];       
        $deviceLanguage      = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);   
       
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
                    
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                $db = $this->db;
                $db->beginTransaction();
          
                try
                { 
                    $responseRow = $waEventTrusteeReponseTable->getRowByDetailIdAndUserId($event_id,$userId);
                    if(empty($responseRow))
                    {
                        $data       =  array(
                                        'testament_id'      => $testament_id,
                                        'object_id'         => $event_id,
                                        'trustee_id'        => $trustee_id,
                                        'user_id'           => $userId,
                                        'response'          => $event_response,
                                        'response_time'     => date('Y-m-d H:i:s')
                                    );
                        
                        $insert_id = $waEventTrusteeReponseTable->save($data);                  
                        $result  =  array('response_id'   => $insert_id);                       
                    } 
                    else
                    {                        
                        $this->common->displayMessage($this->view->translate('trustee_given_response'),'1',array(),'126');
                    }                   
                } 
                catch (Exception $ex)
                {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $db->commit();
               $this->common->displayMessage($this->view->translate('trustee_response_saved'), '0', $result, '0');  
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1',array(),'3');
        }        
    }
    
    public function cancelAllWitnessAction()
    {
        $userTable          = new Application_Model_DbTable_Users();
        $waTestamentsTable  = new Application_Model_DbTable_WaTestaments();
        $witnessTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $testament_id       = $decoded['testament_id'];  
        $deviceLanguage     = $decoded['deviceLanguage'];
        $result             = array();
        
        $this->user->setLanguage($deviceLanguage);   
     
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {                
                $db = $this->db;
                $db->beginTransaction();
                
                try
                {    
                    if($testament_id)
                    {
                        $witnessRow     = $witnessTable->getWitnesses($testament_id);
                    }
                
                    $data   = array(
                                'is_status' => '2',
                            );
                     
                    if(count($witnessRow) > 0)
                    {   
                        foreach($witnessRow as $row)
                        {
                            $row->setFromArray($data);
                            $row->save();
                            
                            $userRow1         = $userTable->getRowByEmail($row->username);                        
                            $witnessRow1      = $userTable->getRowById($row->user_id);
                            $postData         = array(
                                                    'user_id'   => $userRow1->userId,
                                                    'username'  => $witnessRow1->userEmail
                                                );
                            $alert_type      = "wa_testament_witness_cancel_allrequest";
                            $msg             = $this->view->translate('cancel_witness_request');
                            $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);
                        }
                        
                        $result = array('total_afftected_rows' => count($witnessRow));
                    }
                }
                catch (Exception $ex)
                {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $db->commit();
               $this->common->displayMessage($this->view->translate('all_witness_rejected'),'0',$result,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function getAllMessagesAction()
    {
        $userTable                  = new Application_Model_DbTable_Users();
        $waTestamentsTable          = new Application_Model_DbTable_WaTestaments();       
        $waEventSendDetailsTable    = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeReponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteeTable           = new Application_Model_DbTable_WaTrustee();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        $owner_alert_set    = array();
        $trustee_alert_set  = array();
        $trustee_arr        = array();
        $owner_alert_row    = array();
        
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {  
                    $waEventSendRow  = $waEventSendDetailsTable->getAllSendVitalByUserId($userId); 
                    if(count($waEventSendRow) > 0) {
                        foreach ($waEventSendRow  as $row){
                            if($row['usertype'] == '1') { 
                                $owner_alert_set[] = array(
                                                        'id'               => $row['id'],
                                                        'user_id'          => $row['user_id'],
                                                        'testament_id'     => $row['testament_id'],
                                                        'usertype'         => $row['usertype'],
                                                        'event_send_date'  => $row['event_send_date'],
                                                        'event_response'   => $row['event_response'],
                                                        'is_status'        => $row['is_status']
                                                    );                                
                            }

                            if($row['usertype'] == '2') {
                                $trustee_set    = $waTrusteeTable->getTrusteesByUserId($row['user_id']);
                                if(count($trustee_set) > 0) {
                                    foreach ($trustee_set as $trustee){
                                        $eventRow = $waEventSendDetailsTable->getTrusteeAlertRowByUserId($trustee['userId']);
                                        $trustee_response_row  = $waEventTrusteeReponseTable->getRowByDetailIdAndUserId($eventRow['id'],$trustee['receiver_id']);
                                        if(!empty($trustee_response_row)){
                                            $response = $trustee_response_row->response;
                                        }else{
                                            $response = $eventRow['event_response'];
                                        }
                                        
                                        if(count($eventRow) > 0) {
                                            $trustee_arr[]  = array(
                                                                'userId'           => $trustee['userId'],
                                                                'userNickName'     => $trustee['userNickName'],
                                                                'testament_id'     => $trustee['testament_id'],
                                                                'id'               => $eventRow['id'],
                                                                'event_send_date'  => $eventRow['event_send_date'],
                                                                'event_response'   => $response,
                                                                'is_status'        => $eventRow['is_status'],
                                                            );
					}
                                    }
                                }                        
                            }
                        }
                    }
                    else
                    {
                        $waTrusteeRows  = $waTrusteeTable->getTrusteesByUserId($userId);                       
                        foreach ($waTrusteeRows as $trusteeRow) {
                            $eventRow = $waEventSendDetailsTable->getTrusteeAlertRowByUserId($trusteeRow['user_id']);
                            if($eventRow['is_status'] == '1'){
                                $trustee_response_row  = $waEventTrusteeReponseTable->getRowByDetailIdAndUserId($eventRow['id'],$trusteeRow['receiver_id']);
                                if(!empty($trustee_response_row)) {
                                    $response = $trustee_response_row->response;
                                }else{
                                    $response = $eventRow['event_response'];
                                }
                                if(count($eventRow) > 0) {
                                    $trustee_arr[]  = array(
                                                        'userId'           => $trusteeRow['userId'],
                                                        'userNickName'     => $trusteeRow['userNickName'],
                                                        'testament_id'     => $trusteeRow['testament_id'],
                                                        'id'               => $eventRow['id'],
                                                        'event_send_date'  => $eventRow['event_send_date'],
                                                        'event_response'   => $response,
                                                        'is_status'        => $eventRow['is_status'],
                                                    );
                                }
                            }
                        }
                    }
                    if(count($owner_alert_set) >= 1) {
                        $owner_alert_row = $owner_alert_set[0];
                    } else {
                        $owner_alert_row = $owner_alert_set;
                    }
                    $result = array('owner_alert_set' => $owner_alert_row, 'trustee_alert_set' => $trustee_arr);
                }
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $this->common->displayMessage($this->view->translate('records_found'),'0',$result,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function getAttachmentsAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentsTable      = new Application_Model_DbTable_WaTestaments();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $testament_id    = $decoded['testament_id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $testamentRows   = array();
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($testament_id)
                    {
                        $testamentRows = $waTestamentFilesTable->getFilesByTestamentId($testament_id);
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('testament_id').' '.$this->view->translate('not_exist'),'1',array(),'110');
                    }                    
                }
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
                $this->common->displayMessage($this->view->translate('records_found'),'0',$testamentRows,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
     
    public function updateAttachmentAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentsTable      = new Application_Model_DbTable_WaTestaments();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $row_id          = $decoded['id'];
        $file_link       = $decoded['file_link'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $result          = array();
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($testamentRow = $waTestamentFilesTable->getRowById($row_id))
                    {                        
                        $data   = array(
                                        'file_link' => $file_link,
                                    );
                        if($testamentRow)
                        {
                            $testamentRow->setFromArray($data);
                            $testamentRow->save();
                        }
                        $result = array('id' => $testamentRow['id']);                        
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('row_id').' '.$this->view->translate('not_exist'),'1',array(),'128');
                    }                    
                }
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }    
          $this->common->displayMessage($this->view->translate('attachment_updated_success'),'0',$result,'0');
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function deleteAttachmentAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentsTable      = new Application_Model_DbTable_WaTestaments();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $row_id          = $decoded['id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $result          = array();
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {
                    if($row_id)
                    {
                        $testamentRow = $waTestamentFilesTable->getRowById($row_id);
                        $data   = array('is_status' => '0');
                        if($testamentRow)
                        {
                            $testamentRow->setFromArray($data);
                            $testamentRow->save();
                        }
                        $result = array('id' => $testamentRow['id']);
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('row_id').' '.$this->view->translate('not_exist'),'1',array(),'128');
                    }                    
                }
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
              $this->common->displayMessage($this->view->translate('attachment_deleted_success'),'0',$result,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function deliverVitalCheckAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
        $usertNotificationsTable  = new Application_Model_DbTable_UserNotifications();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $eventResponse   = $decoded['event_response'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);   
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                try
                { 
                    $eventSendDetailRows = $waEventSendDetailsTable->getAllEventSendRowByUserId($userId);
                    if(!empty($eventSendDetailRows))
                    {
                        foreach ($eventSendDetailRows as $eventRow)
                        {
                            if($eventRow->usertype == '1'){
                                $data = array(
                                            'id' => $eventRow->id,
                                            'modification_date' => date('Y-m-d H:i:s'),
                                            'event_response' => '1',
                                            'is_status' => '1',
                                            'is_direct' => '1',  
                                            'event_status' => '1'                                          
                                        );
                                $result[] = $waEventSendDetailsTable->updateEventSendTable($data);                   
                            } else {
                                $data  = array(
                                            'id' => $eventRow->id,
                                            'event_send_date' => date('Y-m-d H:i:s'),
                                            'modification_date' => date('Y-m-d H:i:s'),
                                            'event_response' => '2',
                                            'is_status' => '0',
                                            'is_direct' => '0',  
                                            'event_status' => '1'       
                                        );
                                $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                            }
                           $waTestamentsTable->sendVitalCheckUsers($eventRow->testament_id,'1'); 
                        } 
                       $this->common->displayMessage($this->view->translate('send_trustee_alert'),'0',$result,'0');      
                    } 
                    else 
                    {
                        $this->common->displayMessage($this->view->translate('you_have_no_testament'),'0',array(),'130');
                    } 
                    exit;
                }
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function deceasedVitalCheckAction()
    {
        $userTable                   = new Application_Model_DbTable_Users();
        $waTestamentsTable           = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable     = new Application_Model_DbTable_WaEventSendDetails();
        $waEventTrusteeReponseTable  = new Application_Model_DbTable_WaEventTrusteeResponse();
        $waTrusteeTable              = new Application_Model_DbTable_WaTrustee();
        $usertNotificationsTable     = new Application_Model_DbTable_UserNotifications();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $trustee_id      = $decoded['trustee_id'];
        $event_response  = $decoded['event_response']; 
        $deviceLanguage  = $decoded['deviceLanguage'];
        $event_id        = "";
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);   
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                try
                {  
                    $waTrusteeRow  = $waTrusteeTable->getRowByReceiverIdAndUserId($trustee_id,$userId);
                    if(!empty($waTrusteeRow))
                    {  
                        $trustee_alert_row   = $waEventSendDetailsTable->getTrusteeAlertRowByUserId($trustee_id); //print_r($trustee_alert_row); exit;
                        $testament_id = $trustee_alert_row['testament_id'];
                        $event_id     = $trustee_alert_row['id'];                       
                        $trustee_response_row  = $waEventTrusteeReponseTable->getRowByDetailIdAndUserId($event_id,$trustee_id);
                        if(empty($trustee_response_row)) 
                        {                      
                            $eventSendDetailRows = $waEventSendDetailsTable->getAllEventSendRowByUserId($waTrusteeRow->user_id);
                            if(!empty($eventSendDetailRows))
                            {
                                foreach ($eventSendDetailRows as $eventRow)
                                {
                                    if($eventRow->usertype == '1')
                                    {
                                        $data   = array(
                                                    'id' => $eventRow->id,
                                                    'event_send_date' => date('Y-m-d H:i:s'),
                                                    'modification_date' => date('Y-m-d H:i:s'),
                                                    'event_response' => '1',
                                                    'is_direct' => '2',                                            
                                                    'is_status' => '1',
                                                    'event_status' => '1'    
                                                );
                                        $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                        $waTestamentsTable->sendVitalCheckUsers($eventRow->testament_id,'1');                    
                                    } 
                                    else 
                                    {
                                        $data      = array(
                                                        'id' => $event_id,
                                                        'event_send_date' => date('Y-m-d H:i:s'),
                                                        'modification_date' => date('Y-m-d H:i:s'),                                         
                                                        'event_response'  => $response,
                                                        'is_direct' => '2',                                            
                                                        'is_status' => '1',
                                                        'event_status' => '1'   
                                                    );
                                        $result[] = $waEventSendDetailsTable->updateEventSendTable($data);
                                        $waTestamentsTable->sendVitalCheckUsers($eventRow->testament_id,'2');  
                                    }
                                }  
                        
                                $responseData   =  array(
                                                    'testament_id'      => $testament_id,
                                                    'object_id'         => $event_id,
                                                    'trustee_id'        => $trustee_id,
                                                    'user_id'           => $userId,
                                                    'response'          => $event_response,
                                                    'response_time'     => date('Y-m-d H:i:s')
                                                );      
                                
                                $insert_id      = $waEventTrusteeReponseTable->save($responseData); 
                                $result         = array('response_id'   => $insert_id);       
                                $trusteeRows    = $waTrusteeTable->getTrusteesByTestamentId($userId, $testament_id);
                                
                                if(!empty($trusteeRows)) {
                                    foreach($trusteeRows as $trusteeRow) {
                                        $trusteeDetailRow  = $userTable->getRowById($trusteeRow->receiver_id);
                                        if($userRow->userEmail != $trusteeDetailRow->userEmail) {
                                            $postData       = array(
                                                                    'user_id'   => $userId,
                                                                    'username'  => $trusteeDetailRow->userEmail
                                                              );
                                            $alert_type      = "wa_testament_witness_trustee_request";
                                            $msg             = $this->view->translate('did_you_friend').' '.$userRow->userNikeName.' '.$this->view->translate('passed_way').'?';
                                            $this->getUserDeviceDetailsAction($postData, $msg, $alert_type);

                                            $notificationData = array(
                                                                    'user_id'      => $userId,
                                                                    'from_user_id' => $trusteeRow->receiver_id,
                                                                    'message'      => $msg
                                                                ); 

                                            $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::REJECT_WITNESS_REQUEST);
                                        }
                                    }
                                }
                                $waTestamentsTable->sendVitalCheckUsers($eventRow->testament_id,'2');
                                $this->common->displayMessage($this->view->translate('deceased_vital_check_success'),'1',array(),'0');
                            } 
                            else
                            {                        
                                $this->common->displayMessage($this->view->translate('you_have_no_vital_check'),'1',array(),'132');
                            }   
                        }
                        else
                        {                        
                            $this->common->displayMessage($this->view->translate('already_deceased_response'),'1',array(),'134');
                        }  
                    } 
                    else 
                    { 
                        $this->common->displayMessage($this->view->translate('not_exist_in_testament'),'1',array(),'136');
                    }
                    exit;
                } 
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
        
    public function receivedTestamentAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentsTable      = new Application_Model_DbTable_WaTestaments();
        $waWitnessesTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        
        $decoded         = $this->common->decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $attachment_set  = array();
        $witness_set     = array();
        
        $this->user->setLanguage($deviceLanguage);  
        
        if($userSecurity = $this->servicekey)
        {           
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
           
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            { 
                try
                {
                    $receiverRow            = $waReceiverTable->getTestamentRecievers($userId);
                    $result = array();
                 
                    if(count($receiverRow) > 0)
                    {
                        foreach ($receiverRow as $row)
                        {
                            $testamentRow        = $waTestamentsTable->getRowById($row['testament_id']);
                            $witnessRows         = $waWitnessesTable->getAllConfirmWitnessesByTestamentId($row['testament_id']);
                      
                            if(count($witnessRows) > 0)
                            {
                                foreach ($witnessRows as $witness)
                                {
                                    $witness_set[]  = array(
                                                        'witness_recorded_file' => $witness['recorded_file_link'],
                                                        'witness_pdf' => $witness['signature_pdf_link']
                                                    );
                                }
                            }
                            
                            $attachmentRows     = $waTestamentFilesTable->getFilesByTestamentId($row['testament_id']);
                            if(count($attachmentRows) > 0)
                            {
                                foreach ($attachmentRows as $attachment)
                                {
                                    $attachment_set[] = array(
                                                            'type' => $attachment['type'],
                                                            'file_link' => $attachment['file_link']
                                                        );
                                }
                            }
                            
                            $result[]  = array_merge($result,array(
                                            'user_id'       => $row['user_id'],
                                            'full_name'     => $row['full_name'],
                                            'testament_id'  => $row['testament_id'],
                                            'created_pdf_link' => $testamentRow['created_pdf_link'] ? $testamentRow['created_pdf_link']:"",
                                            'recorded_audio'   => $testamentRow['recorded_audio'] ? $testamentRow['recorded_audio']:"",
                                            'witness_set'    => $witness_set,
                                            'attachment_set' => $attachment_set
                                           )
                                        );
                        }      
                      $this->common->displayMessage($this->view->translate('records_found'),'0',$result,'0');
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('not_received_testament'),'1',array(),'138');
                    }    
                    exit;
                }
                catch(Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
    
    public function getTestamentListTrusteeAction()
    {
        $userTable                = new Application_Model_DbTable_Users();
        $waTestamentsTable        = new Application_Model_DbTable_WaTestaments();       
        $waEventSendDetailsTable  = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable           = new Application_Model_DbTable_WaTrustee();
        
        $decoded            = $this->common->Decoded(); 
        $userId             = $decoded['userId'];
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];
        $userSecurity       = $decoded['userSecurity'];
        $deviceLanguage     = $decoded['deviceLanguage'];
        $owner_alert_set    = array();
        $trustee_alert_set  = array();
        $trustee_arr        = array();
        
        $this->user->setLanguage($deviceLanguage);   
        
        if($userSecurity == $this->servicekey)
        {              
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->isUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {  
                    $waTrusteeRows  = $waTrusteeTable->getTrusteesByUserId($userId);                 
                    if(!empty($waTrusteeRows)) {
                        foreach ($waTrusteeRows as $trusteeRow) {
                            $testamentRow   = $waTestamentsTable->getRowById($trusteeRow['testament_id']);
                            $trustee_arr[]  = array(
                                                'userId'           => $trusteeRow['userId'],
                                                'userNickName'     => $trusteeRow['userNickName'],
                                                'userImage'        => $trusteeRow['userImage'],
                                                'testament_id'     => $trusteeRow['testament_id'],
                                                'is_send'          => $testamentRow['is_send'],
                                                'is_status'        => $testamentRow['is_status']
                                            );
                        }
                    }
                    $result = $trustee_arr;
                }
                catch (Exception $ex)
                {
                    $this->common->displayMessage($ex->getMessage(),'1',array(),'12');
                }
               $this->common->displayMessage($this->view->translate('records_found'),'0',$result,'0');
            }
            else
            {
                $this->common->dislayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'),'1',array(),'3');
        }
    }
        
    public function getFinalTestamentDetailsAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        $waTestmentfilesTable   = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable       = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable  = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable     = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable          = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable          = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable   = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable         = new Application_Model_DbTable_WaTestamentWitnesses();
       
        $decoded                = $this->common->Decoded();       
        $userId                 = $decoded['userId'];
        $userDeviceId           = $decoded['userDeviceId'];
        $userDeviceToken        = $decoded['userDeviceToken'];
        $userSecurity           = $decoded['userSecurity'];
        $deviceLanguage         = $decoded['deviceLanguage'];
       
        $this->user->setLanguage($deviceLanguage);    

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->IsUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                { 
                    $testamentRow  = $waTestamentsTable->getFinalTestamentRowByUserId($userId);
                    
                    if($testamentRow)
                    {           
                        $testament_id              = $testamentRow['testament_id'];   
                        $testaments_witness_set    = array();
                        $receiver_userset          = array();
                        $receiver_trusteeset       = array();
                        $attachment_set            = array();  
                        
                        if($witnessArr = $waWitnessTable->getWitnesses($testament_id)){
                            if(!empty($witnessArr)){
                                foreach ($witnessArr as $row) {
                                    $testaments_witness_set[]   = array(
                                                                    'witness_id'        => $row['witness_id'],
                                                                    'testament_id'      => $testament_id,
                                                                    'name'              => $row['name'],
                                                                    'username'          => $row['username'],
                                                                    'id_type'           => $row['id_type'],
                                                                    'id_number'         => $row['id_number'],
                                                                    'recorded_file'     => $row['recorded_file_link'],
                                                                    'pdf_url'           => $row['signature_pdf_link'],
                                                                    'is_status'         => $row['is_status']
                                                                );
                                }
                            }
                        }
                   
                        if($attachmentsArr = $waTestmentfilesTable->getFilesByTestamentId($testament_id)){
                            if(!empty($attachmentsArr)){
                                foreach ($attachmentsArr as $row){
                                    $attachment_set[]   = array(
                                                            'id'                => $row['id'],
                                                            'testament_id'      => $testament_id,                                                                        
                                                            'type'              => $row['type'],
                                                            'file_url'          => $row['file_link']
                                                        );  
                                }
                            }
                        }
                        
                        if($receiverRowset = $waReceiverTable->getRecieversByTestamentId($testament_id)){
                            if(!empty($receiverRowset)){
                                foreach($receiverRowset as $receiverRow){
                                    $receiver_userset[] = array(
                                                            'id'            => $receiverRow['id'],
                                                            'testament_id'  => $testament_id,  
                                                            'receiver_id'   => $receiverRow['receiver_id']
                                                        );
                                }
                            }
                        }
                       
                        if($trusteeRowset  = $waTrusteeTable->getTrusteesByTestamentId($userId, $testament_id)){
                            if(!empty($trusteeRowset)){
                                foreach($trusteeRowset as $trusteeRow){
                                    $receiver_trusteeset[]  = array(
                                                                'id'            => $trusteeRow['id'],
                                                                'testament_id'  => $testament_id,  
                                                                'receiver_id'   => $trusteeRow['receiver_id'],
                                                                'user_id'       => $trusteeRow['user_id']
                                                            );
                                }
                            }
                        }

                        $data       =   array(  
                                            'testament_id'              => $testamentRow->testament_id,
                                            'full_name'                 => $testamentRow->full_name,
                                            'wa_username'               => $testamentRow->wa_username,
                                            'recorded_audio'            => $testamentRow->recorded_audio,
                                            'pdf_url'                   => $testamentRow->created_pdf_link,
                                            'testaments_witness_set'    => $testaments_witness_set,   
                                            'receiver_userset'          => $receiver_userset,
                                            'receiver_trusteeset'       => $receiver_trusteeset,
                                            'attachment_set'            => $attachment_set
                                        );

                        $this->common->displayMessage($this->view->translate('view_testament_details'),"0",$data,"0"); 
                    }
                    else
                    {
                        $this->common->displayMessage($this->view->translate('not_created_testament'),"1",array(),"150"); 
                    }
                }
                catch(Exception $ex){
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }

    public function convertDateAction($userLoginTime,$data)
    { 
        $result = array(); 
        if(!empty($data)){ 
            foreach($data as $dateFormat){
                $date           = explode('/',$dateFormat);            
                $addMonths      = $date[0].' months';
                $addMonths      = date("Y-m-d H:i:s",strtotime("+$addMonths", strtotime($userLoginTime)));
                $addDays        = $date[1].' days';
                $addDays        = date("Y-m-d H:i:s",strtotime("+$addDays", strtotime($addMonths)));
                $addHours       = $date[2].' hours';
                $addHours       = date("Y-m-d H:i:s",strtotime("+$addHours", strtotime($addDays)));
                $addMinutes     = $date[3].' minutes';
                $addMinutes     = date("Y-m-d H:i:s",strtotime("+$addMinutes", strtotime($addHours)));
                $userLoginTime  = $addMinutes;
                $result[]       = $userLoginTime;
            }
        } 
        return $result;
    }

    public function updateTestamentReceiversAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentTable       = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
       
        $decoded                = $this->common->Decoded();       
        $userId                 = $decoded['userId'];
        $userDeviceId           = $decoded['userDeviceId'];
        $userDeviceToken        = $decoded['userDeviceToken'];
        $userSecurity           = $decoded['userSecurity'];
        $deviceLanguage         = $decoded['deviceLanguage'];
        $testament_id           = $decoded['testament_id'];
        $receiver_userset       = $decoded['receiver_userset'];
               
        $this->user->setLanguage($deviceLanguage);    

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->IsUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                {                  
                    if($testamentRow = $waTestamentTable->getRowById($testament_id))
                    {
                        if($testamentRow['is_send'] != '3')
                        {
                            if(!empty($receiver_userset)) 
                            {     
                                $waReceiverTable->deleteTestamentRecievers($testament_id);  

                                foreach ($receiver_userset as $receiverId)
                                {
                                    $waReceiverData   = array(
                                                            'testament_id'   => $testament_id,
                                                            'receiver_id'  => $receiverId
                                                        );
                                    if ($receiverId != $userId){
                                        $waReceiverRow = $waReceiverTable->createRow($waReceiverData);
                                        $waReceiverRow->save();
                                    }
                                }   

                                $this->common->displayMessage($this->view->translate('receiver_updated_success'),'0',array(),'0');
                            } else {
                                $this->common->displayMessage($this->view->translate('receiver_not_selected'),'1',array(),'142');
                            } 
                        } else {
                            $this->common->displayMessage($this->view->translate('receiver_can_not_update'),'1',array(),'144');
                        }
                    } else {    
                        $this->common->displayMessage($this->view->translate('testament_id_not_exist'),'1',array(),'110');
                    }
                  exit();
                }
                catch(Exception $ex){
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }
    
    public function updateTestamentTrusteesAction()
    {
        $userTable              = new Application_Model_DbTable_Users();
        $waTestamentTable       = new Application_Model_DbTable_WaTestaments();
        $waTrusteeTable         = new Application_Model_DbTable_WaTrustee();
       
        $decoded                = $this->common->Decoded();       
        $userId                 = $decoded['userId'];
        $userDeviceId           = $decoded['userDeviceId'];
        $userDeviceToken        = $decoded['userDeviceToken'];
        $userSecurity           = $decoded['userSecurity'];
        $deviceLanguage         = $decoded['deviceLanguage'];
        $testament_id           = $decoded['testament_id'];
        $receiver_trusteeset    = $decoded['receiver_trusteeset'];
               
        $this->user->setLanguage($deviceLanguage);    

        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken));
            $this->common->IsUserLogin($userId,$userDeviceId);
            
            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive())
            {
                try
                { 
                    if($testamentRow = $waTestamentTable->getRowById($testament_id))
                    {
                        if($testamentRow['is_send'] != '2' && $testamentRow['is_send'] != '3')
                        {
                            if(!empty($receiver_trusteeset)) 
                            {         
                                $waTrusteeTable->deleteTestamentReceivers($userId);

                                foreach ($receiver_trusteeset as $receiverId)
                                {
                                    $data   = array(
                                                    'testament_id' => $testament_id,
                                                    'user_id'      => $userId,
                                                    'receiver_id'  => $receiverId
                                                );
                                    if ($receiverId != $userId){
                                        $waTrusteeRow = $waTrusteeTable->createRow($data);
                                        $waTrusteeRow->save();
                                    }
                                }   

                                $this->common->displayMessage($this->view->translate('trustee_updated_success'),'0',array(),'0');
                            } else {
                                $this->common->displayMessage($this->view->translate('trustee_not_selected'),'1',array(),'146');
                            } 
                        } else {
                            $this->common->displayMessage($this->view->translate('trustee_can_not_update'),'1',array(),'148');
                        }
                    } else {    
                        $this->common->displayMessage($this->view->translate('testament_id_not_exist'),'1',array(),'110');
                    }
                  exit();
                }
                catch(Exception $ex){
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            }
            else
            {
                $this->common->displayMessage($this->view->translate('account_not_exist'),'1',array(),'2');
            }
        }
        else
        {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }

    public function truncateTablesAction()
    { 
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        
        $db->query('TRUNCATE TABLE wa_testaments');
        $db->query('TRUNCATE TABLE wa_event_send_details');
        //$db->query('TRUNCATE TABLE wa_event_send_history');
        $db->query('TRUNCATE TABLE wa_event_trustee_response');
        $db->query('TRUNCATE TABLE wa_receivers');
        $db->query('TRUNCATE TABLE wa_testaments_answers');
        $db->query('TRUNCATE TABLE wa_testaments_bank_accounts');
        $db->query('TRUNCATE TABLE wa_testaments_estate');
        $db->query('TRUNCATE TABLE wa_testaments_files');
        $db->query('TRUNCATE TABLE wa_testaments_others');
        $db->query('TRUNCATE TABLE wa_testaments_represtatives');
        $db->query('TRUNCATE TABLE wa_testaments_social_accounts');
        $db->query('TRUNCATE TABLE wa_testaments_witnesses');
        $db->query('TRUNCATE TABLE wa_trustees');
        exit;
    }
    
    public function decryptAction()
    { 
        $file = $this->getRequest()->getPost('file','');
        $file = "https://wa-testament.s3.amazonaws.com/1433500717.text";
        $this->common->fnDecrypt($file);
        exit;
    }
    

    public function receiverEmailAction()
    {
        $userTable = new Application_Model_DbTable_Users();
        $userId    = 5;
        $userRow   = $userTable->getRowById($userId);
        $params    = array(
                        'userNickName'      => $userRow->userNickName,
                        'userId'            => $this->common->setEncrypt($userId),
                        'baseUrl'           => $this->baseUrl
                    );       
        $this->mail($params,'incoming_testament.phtml',$userRow->userEmail,'WA-testament Received');
        exit;
    }
    
    public function testPushAction()
    {
	  $userDeviceType   = "android";
 	  $is_after = ($alert_type == Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE)?"1":"0";
          $userDeviceToken  = "APA91bH9gmfhBNvcl6YFbhdbiyqKjBKSgP-JRCj-U8eaxBytuGN2VKMJrw4t0bH_aVrvy5_SbeLvjREgiGs0G93I2IO7tCABfMBIJshra0uVyjwbM-RcM6oFwPdkP5CZm7S1F5GeEd9a";
          $message          =  "are you alive?";
          $resultData =  array(
                            'userImage'         => "",
                            'userId'            => "",
                            'userName'          => "",
                            'event_id'          => "118",
                            'is_trustee'        => '0',
                            'is_testament'      => "1",
                            'message'           => "wa_owner_alert"
                         );

        $payload = array(
          'message'   => $message,
          'type'      => "wa_testament",
          'result'    => $resultData    
        );
      
       $payload = json_encode($payload);
       $this->common->sendPushMessage($userDeviceType ,$userDeviceToken,  $payload);  
       exit;          
    }
}
