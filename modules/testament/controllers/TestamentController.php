<?php

class Testament_TestamentController extends My_Controller_Abstract
{    
    public function preDispatch() {
        parent::preDispatch();
    }
    
    public function init() {
        $this->_helper->layout->enableLayout();
        $this->view->menu = 'my_testament';
    }
    
    public function indexAction() { 
        $this->_helper->layout->setLayout('new_layout');
    }
    
    public function checkTrusteesAction() {
        $this->_helper->layout->setLayout('new_layout');
        $flag = 0;
        
        $trusteeTable   = new Application_Model_DbTable_Trustee();
        $userId         = $this->loggedUserRow->userId;
        $myTrustees     = $trusteeTable->countMyTrustees($userId);
        if(!empty($myTrustees) && (count($myTrustees) > 4)){
            $flag = 1; 
        }
       echo $flag;
       exit();
    }
    
    public function addAction() {
        $this->_helper->layout->setLayout('new_layout');
        $userTable              = new Application_Model_DbTable_Users();
        $countryTable           = new Application_Model_DbTable_Countries();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $friendTable            = new Application_Model_DbTable_Friends();
        
        $myFriends              = $friendTable->myfriends($this->loggedUserRow->userId);
        
        $this->view->countriesList = $countryTable->getCountriesList();
        $this->view->userEmail     = $this->loggedUserRow->userEmail;
        $this->view->friendsRowset = $myFriends;
    }
 
    public function step1Action() { 
        $userTable             = new Application_Model_DbTable_Users();
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        $waRepresentativeTable = new Application_Model_DbTable_WaTestamentRepresentatives();
        $countryTable          = new Application_Model_DbTable_Countries();
        $waCreditsTable        = new Application_Model_DbTable_WaCredits();
    
        $testament_id          = $this->getRequest()->getPost('testament_id','');
        if (!empty($testament_id)) {
            $testament_id   = $this->common->setDecrypt($testament_id);
        }  
        $userId             = $this->loggedUserRow->userId;
        $full_name          = $this->getRequest()->getPost('full_name', '');
        $gender             = $this->getRequest()->getPost('gender', '');
        $address1           = $this->getRequest()->getPost('address1', '');
        $address2           = $this->getRequest()->getPost('address2', '');
        $zipcode            = $this->getRequest()->getPost('zip', '');
        $city               = $this->getRequest()->getPost('city', '');
        $citizen_country    = $this->getRequest()->getPost('citizen_country', '');
        $country            = $this->getRequest()->getPost('country', '');
        $nationality        = $this->getRequest()->getPost('nationality', '');
        $id_type            = $this->getRequest()->getPost('id_type', '');
        $id_number          = $this->getRequest()->getPost('id_number', '');
        $other_id_type      = $this->getRequest()->getPost('other_id_type', '');
        $wa_username        = $this->getRequest()->getPost('wa_username', '');        
        $res_count          = 0;
        $first_res_gender   = $this->getRequest()->getPost('first_res_gender', '');
        $res_name           = $this->getRequest()->getPost('res_name', '');
        $res_address1       = $this->getRequest()->getPost('res_address1', '');
        $res_address2       = $this->getRequest()->getPost('res_address2', '');
        $res_city           = $this->getRequest()->getPost('res_city', '');
        $res_country        = $this->getRequest()->getPost('res_country', '');
        $res_postcode       = $this->getRequest()->getPost('res_postcode', '');
        $res_wausername     = $this->getRequest()->getPost('res_wauseremail', '');
        $res_phone_number   = $this->getRequest()->getPost('res_phone_number', '');
        $sec_res_gender     = $this->getRequest()->getPost('sec_res_gender', '');
        $res_gender         = array(
                                urldecode($first_res_gender),
                                urldecode($sec_res_gender)
                            );        
        $testamentRow          = false;
        $is_existing_testament = false;
       
        if ($testament_id) {
            $is_existing_testament = true;
            $testamentRow          = $waTestamentTable->getRowById($testament_id);
        }
        
        $db = $this->db;
        $db->beginTransaction();
        try
        {
            $data = array(
                    'user_id'         => $userId,
                    'full_name'       => urldecode($full_name),
                    'gender'          => urldecode($gender),
                    'address1'        => urldecode($address1),
                    'address2'        => urldecode($address2),
                    'postcode'        => urldecode($postcode),
                    'city'            => urldecode($city),
                    'citizen_country' => urldecode($citizen_country),
                    'country'         => $countryRow['country_english_name'],
                    'nationality'     => urldecode($nationality),
                    'id_type'         => urldecode($id_type),
                    'id_number'       => urldecode($id_number),
                    'wa_username'     => urldecode($wa_username),
                    'phone_number'    => urldecode($phone_number),
                    'creation_date'   => date('Y-m-d H:i:s'),
                    'is_status'       => 1
                );

            $countryRow = $countryTable->getRowById(urldecode($country));
            if ($testamentRow) {
                $testamentRow->setFromArray($data);
                $testamentRow->save();
                $testamentRow = $testament_id;
            } else {
                $testamentRow = $waTestamentTable->saveTestament($data);
            }
            if ($is_existing_testament) {
                $waRepresentativeTable->deleteRepresentatives($testamentRow);
            }
            ($representative_set) ? $this->InsertMultipleRows('wa_testaments_represtatives', $representative_set) : "0";
            $loggedUserArr = $this->loggedUserRow->toArray();
            $data          = array_merge($loggedUserArr, array(
                                'testament_id' => $testamentRow
                            ));
            $loggedUserRow = (object) $data;
        } catch (Exception $ex) {
            $db->rollBack();
            echo $ex->getMessage(); exit();
        }
      $db->commit();
      echo $this->common->setEncrypt($loggedUserRow->testament_id);
      exit();
    }
    
    public function step2Action()
    {
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waBankAccountTable         = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable              = new Application_Model_DbTable_WaTestamentOthers();
        $waSocialAccountTable       = new Application_Model_DbTable_WaTestamentSocialAccount();
        
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        $birth_certificate_checked  = $this->getRequest()->getPost('birth_certificate_checked', '');
        $birth_certificate_place    = $this->getRequest()->getPost('birth_certificate_place', '');
        $life_insurance_checked     = $this->getRequest()->getPost('life_insurance_checked', '');
        $life_insurance_policies    = $this->getRequest()->getPost('life_insurance_policies', '');
        $deposit_account_checked    = $this->getRequest()->getPost('deposit_account_checked', '');
        $deposit_account_books      = $this->getRequest()->getPost('deposit_account_books', '');
        $any_property_checked       = $this->getRequest()->getPost('any_property_checked', '');
        $any_property               = $this->getRequest()->getPost('any_property', '');
        $other_property_checked     = $this->getRequest()->getPost('other_property_checked', '');
        $other_property             = $this->getRequest()->getPost('other_property', '');
        $any_other_property_checked = $this->getRequest()->getPost('any_other_property_checked', '');
        $any_other_property         = $this->getRequest()->getPost('any_other_property', '');
        $account_checked            = $this->getRequest()->getPost('account_checked', '');
        $num_accounts               = $this->getRequest()->getPost('num_accounts', '');
        $active_investments_checked = $this->getRequest()->getPost('active_investments_checked', '');
        $active_investments         = $this->getRequest()->getPost('active_investments', '');
        $other_possessions_checked  = $this->getRequest()->getPost('other_possessions_checked', '');
        $other_possessions          = $this->getRequest()->getPost('other_possessions', '');
        $social_account_checked     = $this->getRequest()->getPost('social_account_checked', '');
        $is_existing_testament      = false;
        
        $data  = array(
                    'birth_certificate_place' => urldecode($birth_certificate_place),
                    'life_insurance_policies' => urldecode($life_insurance_policies),
                    'deposit_account_books'   => urldecode($deposit_account_books),
                    'any_property'            => urldecode($any_property),
                    'other_property'          => urldecode($other_property),
                    'any_other_property'      => urldecode($any_other_property),
                    'testament_special_request' => urldecode($testament_special_request),
                    'modification_date'       => date('Y-m-d H:i:s'),
                    'is_status'               => 1
                );
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }        
        if (empty($birth_certificate_checked) || empty($birth_certificate_place)) {
            $birth_certificate_place = "";
        }        
        if (empty($life_insurance_checked) || empty($life_insurance_policies)) {
            $life_insurance_policies = "";
        }        
        if (empty($deposit_account_checked) || empty($deposit_account_books)) {
            $deposit_account_books = "";
        }        
        if (empty($any_property_checked) || empty($any_property)) {
            $any_property = "";
        }        
        if (empty($other_property_checked) || empty($other_property)) {
            $other_property = "";
        }        
        if (empty($any_other_property_checked) || empty($any_other_property)) {
            $any_other_property = "";
        }        
        $bank_name           = $this->getRequest()->getPost('bank_name','');
        $bank_name           = json_decode($bank_name);
        $bank_address        = $this->getRequest()->getPost('bank_address','');
        $bank_address        = json_decode($bank_address);
        $bank_account_number = $this->getRequest()->getPost('bank_account_number','');
        $bank_account_number = json_decode($bank_account_number);
        $account_count       = 0;
        if (!empty($account_checked) && !empty($num_accounts)) {
            foreach ($bank_name as $bank) {
                $account_set[] = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '1',
                                    'bank_name'    => $bank,
                                    'bank_address' => urldecode($bank_address[$account_count]),
                                    'bank_account_number' => urldecode($bank_account_number[$account_count])
                                );
                $account_count++;
            }
        }        
        if (empty($active_investments_checked) || empty($active_investments)) {
            $active_investments = "";
        } else {
            $active_investments = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '1',
                                    'description'  => $active_investments
                                );
        }        
        if (empty($other_possessions_checked) || empty($other_possessions)) {
            $other_possessions = "";
        } else {
            $other_possessions = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '2',
                                    'description'  => $other_possessions
                                );
        }    
        $testaments_others_set   = array_merge($active_investments,$other_possessions);
        $social_url              = $this->getRequest()->getPost('social_url', '');
        $social_username         = $this->getRequest()->getPost('social_username', '');
        $social_password         = $this->getRequest()->getPost('social_password', '');
        $social_security_ques    = $this->getRequest()->getPost('social_security_ques', '');
        $social_is_status        = $this->getRequest()->getPost('social_ans1', '');
        $social_managed_username = $this->getRequest()->getPost('social_manage_username', '');
        $social_count            = 0;
        if (!empty($social_account_checked) && !empty($social_url)) {
            foreach ($social_url as $social) {
                $social_account_set[] = array(
                                        'testament_id'     => $testament_id,
                                        'url'              => $social,
                                        'username'         => $social_username[$social_count],
                                        'password'         => urldecode($social_password[$social_count]),
                                        'security_ques'    => urldecode($social_security_ques[$social_count]),
                                        'is_status'        => urldecode($social_is_status[$social_count]),
                                        'managed_username' => urldecode($social_managed_username[$social_count])
                                    );
               $social_count++;
            }
        }
        
        if ($testament_id) {
            $is_existing_testament = true;
            $testamentRow          = $waTestamentTable->getRowById($testament_id);
            if ($testamentRow) {
                $testamentRow->setFromArray($data);
                $testamentRow->save();
            }
            
            if ($is_existing_testament) {
                $waBankAccountTable->deleteBankAccounts($testament_id);
                $waSocialAccountTable->deleteSocialAccounts($testament_id);
                $waOthersTable->deleteOthersSet($testament_id);
            }
            (count($account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_bank_accounts', $account_set) : "";
            (count($social_account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_social_accounts', $social_account_set) : ""; 
            (count($testaments_others_set) > 0) ? $this->InsertMultipleRows('wa_testaments_others', $testaments_others_set) : "";                     
        }
      exit();
    }
    
    public function step3Action()
    {
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waEstateTable              = new Application_Model_DbTable_WaTestamentEstate();
        
        $estate_checked             = $this->getRequest()->getPost('estate_checked', '');
        $estate_devise_checked      = $this->getRequest()->getPost('estate_devise_checked', '');
        $item_name                  = $this->getRequest()->getPost('item_name', '');
        $beneficiairy               = $this->getRequest()->getPost('beneficiairy', '');        
        $percentage                 = $this->getRequest()->getPost('devise_percentage', '');
        $devise_beneficiairy        = $this->getRequest()->getPost('devise_beneficiairy', '');
        $remaining_percentage       = $this->getRequest()->getPost('remaining_percentage', '');        
        $testament_request          = $this->getRequest()->getPost('testament_request', '');
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        
        $estate_count = 0;
        if (count($item_name) > 0) {
            foreach ($item_name as $key => $item) {
                if (!empty($item)) {
                    $estate_devise_set[] = array(
                                            'testament_id'  => $testament_id,
                                            'type'          => '1',
                                            'item_name'     => $item,
                                            'beneficiairy'  => urldecode($beneficiairy[$estate_count])
                                        );
                    $estate_count++;
                }
            }
        }
        $estate_devise_count  = 0;
        if (count($percentage) > 0) {
            foreach ($percentage as $percent) {
                if (!empty($percent)) {
                    $estate_per_devise_set[] = array(
                                                'testament_id' => $testament_id,
                                                'type'         => '2',
                                                'percentage'   => urldecode($percent),
                                                'beneficiairy' => urldecode($devise_beneficiairy[$estate_devise_count])
                                            );
                    $estate_devise_count++;
                }
            }
        }
        $testaments_estate_set = array_merge($estate_devise_set,$estate_per_devise_set);
        $data   = array(
                    'testament_special_request' => urldecode($testament_request)
                );
        if ($testament_id) {
            $testamentRow  = $waTestamentTable->getRowById($testament_id);
            if ($testamentRow) {
                $is_existing_testament = true;
                $testamentRow->setFromArray($data);
                $testamentRow->save();
            }

            if ($is_existing_testament) {
                $waEstateTable->deleteEstateDividations($testamentRow);
            }               
           (count($testaments_estate_set) > 0) ? $this->InsertMultipleRows('wa_testaments_estate', $testaments_estate_set) : "";                               
        }
      exit();
    }
    
    public function addNewTestamentAction()
    { 
        $userTable                  = new Application_Model_DbTable_Users();
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waTestmentfilesTable       = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable           = new Application_Model_DbTable_WaEventSendDetails();
        $waReceiverTable            = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable             = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable      = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable         = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable              = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable              = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable       = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable             = new Application_Model_DbTable_WaTestamentWitnesses();
        $waCreditsTable             = new Application_Model_DbTable_WaCredits();
        $countryTable               = new Application_Model_DbTable_Countries();
        
        $userId                     = $this->loggedUserRow->userId;
        $full_name                  = $this->getRequest()->getPost('full_name', '');
        $gender                     = $this->getRequest()->getPost('gender', '');
        $address1                   = $this->getRequest()->getPost('address1', '');
        $address2                   = $this->getRequest()->getPost('address2', '');
        $zipcode                    = $this->getRequest()->getPost('zip', '');
        $city                       = $this->getRequest()->getPost('city', '');
        $citizen_country            = $this->getRequest()->getPost('citizen_country', '');
        $country                    = $this->getRequest()->getPost('country', '');
        $nationality                = $this->getRequest()->getPost('nationality', '');
        $id_type                    = $this->getRequest()->getPost('id_type', '');
        $id_number                  = $this->getRequest()->getPost('id_number', '');
        $other_id_type              = $this->getRequest()->getPost('other_id_type', '');
        $wa_username                = $this->getRequest()->getPost('wa_username', '');
        $birth_certificate_checked  = $this->getRequest()->getPost('birth_certificate_place_checked', '');
        $birth_certificate_place    = $this->getRequest()->getPost('birth_certificate_place', '');
        $life_insurance_checked     = $this->getRequest()->getPost('life_insurance_policies_checked', '');
        $life_insurance_policies    = $this->getRequest()->getPost('life_insurance_policies', '');
        $deposit_account_checked    = $this->getRequest()->getPost('deposit_account_books_checked', '');
        $deposit_account_books      = $this->getRequest()->getPost('deposit_account_books', '');
        $any_property_checked       = $this->getRequest()->getPost('any_property_checked', '');
        $any_property               = $this->getRequest()->getPost('any_property', '');
        $other_property_checked     = $this->getRequest()->getPost('other_property_checked', '');
        $other_property             = $this->getRequest()->getPost('other_property', '');
        $any_other_property_checked = $this->getRequest()->getPost('any_other_property_checked', '');
        $any_other_property         = $this->getRequest()->getPost('any_other_property', '');
        $account_checked            = $this->getRequest()->getPost('account_checked', '');
        $num_accounts               = $this->getRequest()->getPost('num_accounts', '');
        $active_investments_checked = $this->getRequest()->getPost('active_investments_checked', '');
        $active_investments         = $this->getRequest()->getPost('active_investments', '');
        $other_possessions_checked  = $this->getRequest()->getPost('other_possessions_checked', '');
        $other_possessions          = $this->getRequest()->getPost('other_possessions', '');
        $social_account_checked     = $this->getRequest()->getPost('social_account_checked', '');
        $testament_special_request  = $this->getRequest()->getPost('testament_request', '');
        $testaments_witness_set     = $this->getRequest()->getPost('testaments_witness_set', '');
        $member_type                = $this->getRequest()->getPost('member_type', '');
        $total_paid                 = $this->getRequest()->getPost('total_paid', '');
        $receiver_userset           = $this->getRequest()->getPost('receiver_userset', '');
        $receiver_trusteeset        = $this->getRequest()->getPost('receiver_trusteeset', '');
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        $vital_check                = $this->getRequest()->getPost('vital_check', '');
        $ip_address                 = $this->getRequest()->getPost('ip_address', '');
        $latitude                   = $this->getRequest()->getPost('latitude', '');
        $longitude                  = $this->getRequest()->getPost('longitude', '');
        $created_address            = $this->getRequest()->getPost('created_address', '');
        $recorded_audio             = $this->getRequest()->getPost('recorded_audio', '');
        $signature_image            = $this->getRequest()->getPost('signature_image', ''); //"testaments/signature_images/sign3.png";
        $images                     = $this->getRequest()->getPost('images', '');
        $audio                      = $this->getRequest()->getPost('audio', '');
        $video                      = $this->getRequest()->getPost('video', '');
        $pdf                        = $this->getRequest()->getPost('pdf', '');
        $deviceLanguage             = $this->getRequest()->getPost('deviceLanguage', '');
        $modification_date          = $this->getRequest()->getPost('modification_date', '');
        
        $this->user->setLanguage($deviceLanguage);
        $is_gender               = "";
        $is_gender1              = "";
        $is_gender2              = "";
        $representativeArr       = array();
        $accountSetArr           = array();
        $investmentsArr          = array();
        $possessionsArr          = array();
        $estateSetArr            = array();
        $estatePerSetArr         = array();
        $socialAccountArr        = array();
        $estatePerRemaining      = "";
        $witnessesArr            = array();
        
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        
        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) 
        {
            $testamentRow          = false;
            $is_existing_testament = false;
            $testamentData         = false;
            
            if ($testament_id) {
                $is_existing_testament = true;
                $testamentRow          = $waTestamentTable->getRowById($testament_id);
            }
            
            if (!empty($testaments_witness_set)) {
                $witnessRows = $waWitnessTable->getWitnessesByIds($testaments_witness_set);
                if (!empty($witnessRows)) {
                    foreach ($witnessRows as $row) {
                        $witnessesArr[] = array(
                                            'witness_id'        => $row['witness_id'],
                                            'name'              => $row['name'],
                                            'username'          => $row['username'],
                                            'id_type'           => $row['id_type'],
                                            'id_number'         => $row['id_number'],
                                            'modification_date' => $row['modification_date'],
                                            'recorded_file_link' => $row['recorded_file_link'],
                                            'signature_image_link' => $row['signature_image_link']
                                        );
                    }
                }
            }
           
            $db = $this->db;
            $db->beginTransaction();
            try 
            {
                $user_gender = $this->view->translate('TESTATOR');
                if (strcasecmp($user_gender, $gender) == 0) {
                    $gender = $this->view->translate('TESTATOR');
                }
                
                $user_gender1 = $this->view->translate('TESTATRIX');
                if (strcasecmp($user_gender1, $gender) == 0) {
                    $gender = $this->view->translate('TESTATRIX');
                }
                
                if (empty($birth_certificate_checked) || empty($birth_certificate_place)) {
                    $birth_certificate_place = "";
                }
                
                if (empty($life_insurance_checked) || empty($life_insurance_policies)) {
                    $life_insurance_policies = "";
                }
                
                if (empty($deposit_account_checked) || empty($deposit_account_books)) {
                    $deposit_account_books = "";
                }
                
                if (empty($any_property_checked) || empty($any_property)) {
                    $any_property = "";
                }
                
                if (empty($other_property_checked) || empty($other_property)) {
                    $other_property = "";
                }
                
                if (empty($any_other_property_checked) || empty($any_other_property)) {
                    $any_other_property = "";
                }
                
                $countryRow = $countryTable->getRowById(urldecode($country));
                
                $data = array(
                            'user_id'                   => $userId,
                            'full_name'                 => urldecode($full_name),
                            'gender'                    => urldecode($gender),
                            'address1'                  => urldecode($address1),
                            'address2'                  => urldecode($address2),
                            'postcode'                  => urldecode($zipcode),
                            'city'                      => urldecode($city),
                            'citizen_country'           => urldecode($citizen_country),
                            'country'                   => $countryRow['country_english_name'],
                            'nationality'               => urldecode($nationality),
                            'id_type'                   => urldecode($id_type),
                            'id_number'                 => urldecode($id_number),
                            'wa_username'               => urldecode($wa_username),
                            'phone_number'              => urldecode($this->loggedUserRow->userPhone),
                            'birth_certificate_place'   => urldecode($birth_certificate_place),
                            'life_insurance_policies'   => urldecode($life_insurance_policies),
                            'deposit_account_books'     => urldecode($deposit_account_books),
                            'any_property'              => urldecode($any_property),
                            'other_property'            => urldecode($other_property),
                            'any_other_property'        => urldecode($any_other_property),
                            'testament_special_request' => urldecode($testament_special_request),
                            'member_type'               => urldecode($member_type),
                            'is_send'                   => '1',
                            'recorded_audio'            => urldecode($recorded_audio),
                            'ip_address'                => urldecode($ip_address),
                            'latitude'                  => urldecode($latitude),
                            'longitude'                 => urldecode($longitude),
                            'created_address'           => urldecode($created_address),
                            'total_paid'                => urldecode($total_paid),
                            'modification_date'         => date('Y-m-d H:i:s'),
                            'is_status'                 => 1
                        );
                
                if ($gender == $this->view->translate('TESTATOR')) {
                    $is_gender  = $this->view->translate('his');
                    $is_gender1 = $this->view->translate('him');
                    $is_gender2 = $this->view->translate('he');
                } else {
                    $is_gender  = $this->view->translate('her');
                    $is_gender1 = $this->view->translate('her');
                    $is_gender2 = $this->view->translate('she');
                }
                
                $res_count        = 0;
                $first_res_gender = $this->getRequest()->getPost('first_res_gender', '');
                $res_name         = $this->getRequest()->getPost('res_name', '');
                $res_address1     = $this->getRequest()->getPost('res_address1', '');
                $res_address2     = $this->getRequest()->getPost('res_address2', '');
                $res_city         = $this->getRequest()->getPost('res_city', '');
                $res_country      = $this->getRequest()->getPost('res_country', '');
                $res_postcode     = $this->getRequest()->getPost('res_postcode', '');
                $res_wausername   = $this->getRequest()->getPost('res_wauseremail', '');
                $res_phone_number = $this->getRequest()->getPost('res_phone_number', '');
                $sec_res_gender   = $this->getRequest()->getPost('sec_res_gender', '');
                $res_gender       = array(
                                        urldecode($first_res_gender),
                                        urldecode($sec_res_gender)
                                    );
                if (!empty($res_name)) {
                    foreach ($res_name as $res_name) {
                        $rep_country          = $countryTable->getRowById(urldecode($res_country[$res_count]));
                        $representativeArr[]  = array(
                                                    'testament_id' => $testament_id,
                                                    'res_gender'   => urldecode($res_gender[$res_count]),
                                                    'res_name'     => $res_name,
                                                    'res_address1' => urldecode($res_address1[$res_count]),
                                                    'res_address2' => urldecode($res_address2[$res_count]),
                                                    'res_city'     => urldecode($res_city[$res_count]),
                                                    'res_country'  => $rep_country['country_english_name'],
                                                    'res_postcode' => urldecode($res_postcode[$res_count]),
                                                    'res_phone_number' => urldecode($res_phone_number[$res_count]),
                                                    'res_wauseremail' => urldecode($res_wausername[$res_count])
                                                );
                        $res_count++;
                    }
                }
                
                $bank_name           = $this->getRequest()->getPost('bank_name', '');
                $bank_address        = $this->getRequest()->getPost('bank_address', '');
                $bank_account_number = $this->getRequest()->getPost('bank_account_number', '');
                $account_count       = 0;
                if (!empty($account_checked) && !empty($num_accounts)) {
                    foreach ($bank_name as $bank) {
                        $accountSetArr[] = array(
                                            'testament_id' => $testament_id,
                                            'type'         => '1',
                                            'bank_name'    => $bank,
                                            'bank_address' => urldecode($bank_address[$account_count]),
                                            'bank_account_number' => urldecode($bank_account_number[$account_count])
                                        );
                        $account_count++;
                    }
                }
                if (empty($active_investments_checked) || empty($active_investments)) {
                    $active_investments = "";
                } else {
                    $investmentsArr[] = array(
                                        'testament_id' => $testament_id,
                                        'type'         => '1',
                                        'description'  => $active_investments
                                    );
                }
                if (empty($other_possessions_checked) || empty($other_possessions)) {
                    $other_possessions = "";
                } else {
                    $possessionsArr[] = array(
                                        'testament_id' => $testament_id,
                                        'type'        => '2',
                                        'description' => $other_possessions
                                      );
                }
                
                $social_url              = $this->getRequest()->getPost('social_url','');
                $social_username         = $this->getRequest()->getPost('social_email','');
                $social_password         = $this->getRequest()->getPost('social_password','');
                $social_security_ques    = $this->getRequest()->getPost('social_ques','');
                $social_is_status        = $this->getRequest()->getPost('social_ans1','');
                $social_managed_username = $this->getRequest()->getPost('social_manage_username','');
                $social_count            = 0;
                if (!empty($social_account_checked) && !empty($social_url)) {
                    foreach ($social_url as $social) {
                        $socialAccountArr[] = array(
                                                'testament_id'   => $testament_id,
                                                'url'            => $social,
                                                'username'       => $social_username[$social_count],
                                                'password'       => urldecode($social_password[$social_count]),
                                                'security_ques'  => urldecode($social_security_ques[$social_count]),
                                                'is_status'      => urldecode($social_is_status[$social_count]),
                                                'managed_username' => urldecode($social_managed_username[$social_count])
                                            );
                        $social_count++;
                    }
                }
                
                $item_name    = $this->getRequest()->getPost('item_name', '');
                $beneficiairy = $this->getRequest()->getPost('beneficiairy', '');
                $estate_count = 0;
                if (count($item_name) > 0) {
                    foreach ($item_name as $key => $item) {
                        if (!empty($item)) {
                            $estateSetArr[] = array(
                                                    'testament_id' => $testament_id,
                                                    'type' => '1',
                                                    'item_name' => $item,
                                                    'beneficiairy' => urldecode($beneficiairy[$estate_count])
                                                );
                            $estate_count++;
                        }
                    }
                }
                $percentage           = $this->getRequest()->getPost('devise_percentage', '');
                $devise_beneficiairy  = $this->getRequest()->getPost('devise_beneficiairy', '');
                $remaining_percentage = $this->getRequest()->getPost('remaining_percentage', '');
                $estate_devise_count  = 0;
                if (!empty($percentage)) {
                    foreach ($percentage as $percent) {
                        if (!empty($percent)) {
                            $estatePerSetArr[] = array(
                                                        'testament_id' => $testament_id,
                                                        'type' => '2',
                                                        'percentage' => urldecode($percent),
                                                        'beneficiairy' => urldecode($devise_beneficiairy[$estate_devise_count])
                                                    );
                            $estate_devise_count++;
                        }
                    }
                }
                $count_witness           = count($witnessesArr);
                $count_bankaccount       = count($accountSetArr);
                $count_social_account    = count($socialAccountArr);
                $count_devise_set        = count($estateSetArr);
                $count_per_devise_set    = count($estatePerSetArr);
                
                $resultData       = array_merge($data, array(
                                        'count_witness'        => $count_witness,
                                        'count_bankaccount'    => $count_bankaccount,
                                        'count_social_account' => $count_social_account,
                                        'count_devise_set'     => $count_devise_set,
                                        'count_per_devise_set' => $count_per_devise_set,
                                        'is_gender'            => $is_gender,
                                        'is_gender1'           => $is_gender1,
                                        'is_gender2'           => $is_gender2,
                                        'signature_image'      => '',
                                        'active_investments'   => $active_investments,
                                        'other_possessions'    => $other_possessions,
                                        'representative_set'   => $representativeArr,
                                        'account_set'          => $accountSetArr,
                                        'social_account_set'   => $socialAccountArr,
                                        'estate_devise_set'    => $estateSetArr,
                                        'estate_per_devise_set'=> $estatePerSetArr,
                                        'estatePerRemaining'   => $remaining_percentage,
                                        'testaments_witness_set' => $witnessesArr,
                                        'user_modification_date' => $modification_date
                                    ));
               
                $this->view->data = $resultData;
                $content          = $this->view->render('wa-testament/create-testament.phtml');
                $header           = '<div class="pdf-header">' . $this->view->translate('wa_testament_heading') . ' & ' . $this->view->translate('social') . ' “' . $this->view->translate('last_wish') . '”</div>';
                $footer           = '<div class="pdf-footer" style="margin-top: 28px;">' . $this->view->translate('testament_pdf_footer') . '<br /><span>{PAGENO} / {nb} </span></div>';
                $pdf_url          = $this->common->createMpdf($header, $content, $footer);
            /*    
                if (!empty($_FILES['signature_image'])) {
                    if (!empty($testament_id)) {
                        if (count($witnessesArr) >= 2) {
                            if ($userRow->lastSeenTime <= $userRow->userModifieddate) {
                                $userLoginTime = $userRow->userModifieddate;
                            } else {
                                $userLoginTime = $userRow->lastSeenTime;
                            }
                            $vital_check_set       = $this->convertDateAction($userLoginTime, $vital_check);
                            $count_vital_check_set = count($vital_check_set);
                            $vital_check_last_date = end($vital_check_set);
                            $trustee_alert_time    = $this->convertDateAction($vital_check_last_date, $trustee_alert_time);
                            
                            if ($vital_alert_count == $count_vital_check_set) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $pdf_url);
                                $signature_image_link = $_FILES['signature_image'];
                                $response             = $this->common->upload($signature_image_link, 'signature_images');
                                
                                if (isset($response)) {
                                    $signature_image_link = "testaments/signature_images/" . $response['new_file_name'];
                                    $resultData           = array_merge($resultData, array(
                                        'signature_image' => $this->makeUrl($signature_image_link)
                                    ));
                                }
                                
                                $this->view->data = $resultData;
                                
                                $content    = ""; //$this->view->render('wa-testament/create-testament.phtml');
                                $pdf_url    = $this->common->createMpdf($header, $content, $footer);
                                $return_url = $this->common->uploadTestament($pdf_url);
                                unlink($_SERVER['DOCUMENT_ROOT'] . $pdf_url);
                                
                                $bank_account_set      = array_merge($account_set, $other_bank_account_set);
                                $testaments_others_set = array_merge($active_investments, $other_possessions);
                                $testaments_estate_set = array_merge($estate_devise_set, $estate_per_devise_set);
                                
                                if (!isset($return_url['error'])) {
                                    $data = array_merge($data, array(
                                        'created_pdf_link' => $return_url['new_file_name']
                                    ));
                                }
                                
                                if ($testamentRow) {
                                    $testamentRow->setFromArray($data);
                                    $testamentRow->save();
                                    $testamentRow = $testament_id;
                                } else {
                                    $testamentRow = $waTestamentTable->saveTestament($data);
                                }
                                
                                if ($is_existing_testament) {
                                    $waRepresentativeTable->deleteRepresentatives($testamentRow);
                                    $waBankAccountTable->deleteBankAccounts($testamentRow);
                                    $waOthersTable->deleteOthersSet($testamentRow);
                                    $waSocialAccountTable->deleteSocialAccounts($testamentRow);
                                    $waEstateTable->deleteEstateDividations($testamentRow);
                                    $waTestmentfilesTable->deleteTestamentFiles($testamentRow);
                                    $waEventSendTable->deleteEventSendTable($testamentRow, $userId);
                                }
                                ($representative_set) ? $this->InsertMultipleRows('wa_testaments_represtatives', $representative_set) : "0";
                                (count($bank_account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_bank_accounts', $bank_account_set) : "";
                                (count($testaments_others_set) > 0) ? $this->InsertMultipleRows('wa_testaments_others', $testaments_others_set) : "";
                                (count($socialAccountArr) > 0) ? $this->InsertMultipleRows('wa_testaments_social_accounts', $socialAccountArr) : "";
                                (count($testaments_estate_set) > 0) ? $this->InsertMultipleRows('wa_testaments_estate', $testaments_estate_set) : "";
                                
                                $testamentData = array(
                                    'testament_id' => $testamentRow,
                                    'creation_date' => date('Y-m-d H:i:s'),
                                    'is_status' => 1
                                );
                                
                                if (!empty($images)) {
                                    if (count($images) > 0) {
                                        $tdata = array();
                                        foreach ($images as $image) {
                                            $testamentArr = $testamentData;
                                            $image_link   = $image;
                                            $tdata[]      = array_merge($testamentArr, array(
                                                'type' => 'image',
                                                'file_link' => $image_link
                                            ));
                                        }
                                        $testamentData = $tdata;
                                    }
                                }
                                
                                if (!empty($audio)) {
                                    $audio_link    = $audio;
                                    $testamentData = array_merge($testamentData, array(
                                                        'type' => 'audio',
                                                        'file_link' => $audio_link
                                                    ));
                                }
                                
                                if (!empty($video)) {
                                    $video_link    = $video;
                                    $testamentData = array_merge($testamentData, array(
                                        'type' => 'video',
                                        'file_link' => $video_link
                                    ));
                                }
                                
                                if (!empty($pdf)) {
                                    $pdf_link      = $pdf;
                                    $testamentData = array_merge($testamentData, array(
                                        'type' => 'Pdf',
                                        'file_link' => $pdf_link
                                    ));
                                }
                                
                                $this->InsertMultipleRows('wa_testaments_files', $testamentData);
                                
                                $eventSendDetailDataArr = array(
                                    'user_id'       => $userId,
                                    'testament_id'  => $testamentRow,
                                    'creation_date' => date('Y-m-d H:i:s'),
                                    'vital_check_type' => Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT,
                                    'is_status' => 0
                                );
                                $count                  = 0;
                                if (!empty($vital_check_set)) {
                                    foreach ($vital_check_set as $row) {
                                        $eventSendDetailData[] = array_merge($eventSendDetailDataArr, array(
                                            'usertype' => '1',
                                            'event_send_date' => $row,
                                            'event_vital_value' => $vital_check[$count++]
                                        ));
                                    }
                                    $eventSendDetailData[] = array_merge($eventSendDetailDataArr, array(
                                        'usertype' => '2',
                                        'event_send_date' => $trustee_alert_time[0],
                                        'event_vital_value' => $trustee_alert_set_time[0]
                                    ));
                                }
                                $eventSendDetailRow = false;
                                (count($vital_check_set) > 0) ? $this->InsertMultipleRows('wa_event_send_details', $eventSendDetailData) : "";
                                
                                if ($testamentRow) {
                                    if ($is_existing_testament) {
                                        $waReceiverTable->deleteTestamentRecievers($testamentRow);
                                        $waTrusteeTable->deleteTestamentReceivers($userId, $testamentRow);
                                    }
                                    
                                    if (!empty($receiver_userset)) {
                                        foreach ($receiver_userset as $receiverId) {
                                            $waReceiverData = array(
                                                'testament_id' => $testamentRow,
                                                'receiver_id' => $receiverId
                                            );
                                            
                                            if ($receiverId != $userId) {
                                                $waReceiverRow = $waReceiverTable->createRow($waReceiverData);
                                                $waReceiverRow->save();
                                            }
                                        }
                                    }
                                    
                                    if (!empty($receiver_trusteeset)) {
                                        foreach ($receiver_trusteeset as $receiverId) {
                                            $waTrusteeData = array(
                                                'testament_id' => $testamentRow,
                                                'user_id' => $userId,
                                                'receiver_id' => $receiverId
                                            );
                                            
                                            if ($receiverId != $userId) {
                                                $waTrusteeRow = $waTrusteeTable->createRow($waTrusteeData);
                                                $waTrusteeRow->save();
                                            }
                                        }
                                    }
                                    
                                    $spendCreditData = array(
                                        'credit_type' => 2,
                                        'userId' => $userId,
                                        'credits' => $total_paid
                                    );
                                    
                                    // $waCreditsTable->spendCredits($spendCreditData); 
                                    $result = array(
                                        'pdf_url'     => $return_url['new_file_name'],
                                        'testament_id' => $testament_id
                                    );
                                }
                            } else {
                                $this->displayMessage($this->view->translate('vital_check_count'), '1', array(), '6');
                            }
                        } else {
                            $this->displayMessage($this->view->translate('witness_not_exist'), '1', array(), '8');
                        }
                    } else {
                        $this->displayMessage($this->view->translate('testament_id_not_exist'), '1', array(), '10');
                    }
                } else {
                    $result = array(
                        'pdf_url' => $url,
                        'testament_id' => $testament_id
                    );
                }*/
                $uploaded_file_url = $this->common->fnDecrypt($this->makeUrl($pdf_url));
                echo $this->makeUrl($uploaded_file_url);
            } catch (Exception $ex) {
                $db->rollBack();
                $this->displayMessage($ex->getMessage(), '1', array(), '12');
            }
           $db->commit();
           exit();
        } else {
            $this->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
        }
    }
    
    public function step4Action()
    {
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        
        $testament_id          = $this->getRequest()->getPost('testament_id','');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
       print_r($_FILES['recorded_audio']); exit;
    /*    if ($testament_id) {
            if(isset($_FILES['recorded_audio'])){                
                $response   = $this->common->upload($_FILES['recorded_audio'],'recorded_files');
                $testamentRow    = $waTestamentTable->getRowById($testament_id);
                if ($testamentRow) {
                    $data = array(
                                'recorded_audio' => $response 
                            );
                    $testamentRow->setFromArray($data);
                    $testamentRow->save();
                }
            }else{
                echo 'Unable to record file!!'; 
            }
        }*/
      exit();
    }
        
    public function getTestamentDetailsByIdAction()
    {
        $waUserTable           = new Application_Model_DbTable_Users();
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable       = new Application_Model_DbTable_WaReceiver();
        $waTestmentfilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable      = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable        = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable    = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable         = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable         = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable  = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable        = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $testament_id    = $decoded['testament_id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->IsUserLogin($userId, $userDeviceId);
            
            if (($userRow = $waUserTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    if ($testamentRow = $waTestamentTable->getRowById($testament_id)) {
                        // $testament_id   = $testamentRow->testament_id;
                        $waOwnerRow = $waUserTable->getRowById($userId);
                        
                        $representative_set      = array();
                        $bank_account_set        = array();
                        $social_account_set      = array();
                        $testaments_estate_set   = array();
                        $testaments_others_set   = array();
                        $testaments_witness_set  = array();
                        $receiver_userset        = array();
                        $receiver_names          = array();
                        $receiver_email_phoneset = array();
                        $attachment_files        = array();
                        
                        if ($representiveArr = $waRepresentativeTable->getRepresentatives($testament_id)) {
                            if (!empty($representiveArr)) {
                                foreach ($representiveArr as $row) {
                                    $representative_set[] = array(
                                        'testament_id' => $testamentRow,
                                        'res_name' => $row['res_name'],
                                        'res_gender' => $row['res_gender'],
                                        'res_address1' => $row['res_address1'],
                                        'res_address2' => $row['res_address2'],
                                        'res_city' => $row['res_city'],
                                        'res_postcode' => $row['res_postcode'],
                                        'res_country' => $row['res_country'],
                                        'res_wauseremail' => $row['res_wauseremail'],
                                        'res_phone_number' => $row['res_phone_number']
                                    );
                                }
                            }
                        }
                        
                        if ($bankAccountArr = $waBankAccountTable->getBankAccounts($testament_id)) {
                            if (!empty($bankAccountArr)) {
                                foreach ($bankAccountArr as $row) {
                                    $bank_account_set[] = array(
                                        'type' => $row['type'],
                                        'bank_name' => $row['bank_name'],
                                        'bank_address' => $row['bank_address'],
                                        'bank_account_number' => $row['bank_account_number']
                                    );
                                }
                            }
                        }
                        
                        if ($socialAccountArr = $waSocialAccountTable->getSocialAccounts($testament_id)) {
                            if (!empty($socialAccountArr)) {
                                foreach ($socialAccountArr as $row) {
                                    $social_account_set[] = array(
                                        'url' => $row['url'],
                                        'username' => $row['username'],
                                        'password' => $row['password'],
                                        'security_ques' => $row['security_ques'],
                                        'is_status' => $row['is_status'],
                                        'managed_username' => $row['managed_username']
                                    );
                                }
                            }
                        }
                        
                        if ($estateDividerArr = $waEstateTable->getEstateDividations($testament_id)) {
                            if (!empty($estateDividerArr)) {
                                foreach ($estateDividerArr as $row) {
                                    $testaments_estate_set[] = array(
                                        'item_name' => $row['item_name'],
                                        'beneficiairy' => $row['beneficiairy'],
                                        'percentage' => $row['percentage']
                                    );
                                }
                            }
                        }
                        
                        if ($othersArr = $waOthersTable->getOthersSet($testament_id)) {
                            if (!empty($othersArr)) {
                                foreach ($othersArr as $row) {
                                    $testaments_others_set[] = array(
                                        'type' => $row['type'],
                                        'description' => $row['description']
                                    );
                                }
                            }
                        }
                        
                        if ($witnessArr = $waWitnessTable->getWitnesses($testament_id)) {
                            if (!empty($witnessArr)) {
                                foreach ($othersArr as $row) {
                                    $testaments_witness_set[] = array(
                                        'name' => $row['name'],
                                        'username' => $row['username'],
                                        'id_type' => $row['witsness_idtype'],
                                        'id_number' => $row['witness_id_number']
                                    );
                                }
                            }
                        }
                        
                        if ($attachmentsArr = $waTestmentfilesTable->getFilesByTestamentId($testament_id)) {
                            if (!empty($attachmentsArr)) {
                                foreach ($attachmentsArr as $row) {
                                    $attachment_files[] = array(
                                        'type' => $row['type'],
                                        'file_link' => $row['file_link']
                                    );
                                }
                            }
                        }
                        
                        if ($testamentReciverRow = $waReceiverTable->getRowByTestamentIdAndReceiverId($testament_id, $userId)) {
                            $testamentReceiverRow->is_read = "1";
                            $testamentReceiverRow->save();
                        }
                        
                        $testamentReceiverRowset = $waReceiverTable->getTestamentRecievers($testament_id);
                        
                        if (!empty($testamentReceiverRowset)) {
                            foreach ($testamentReceiverRowset as $testamentReceiverRow) {
                                if ($testamentReceiverRow->receiver_id) {
                                    
                                    $receiver_userset[] = array(
                                        'userId' => $testamentReceiverRow->receiver_id,
                                        'userNickName' => ($testamentReceiverRow->userNickName) ? $testamentReceiverRow->userNickName : "",
                                        'userImage' => ($testamentReceiverRow->userImage) ? $this->makeUrl($testamentReceiverRow->userImage) : ""
                                    );
                                    
                                } else {
                                    $receiver_names[] = array(
                                        'name' => ($testamentReceiverRow->receiver_name) ? $testamentReceiverRow->receiver_name : "",
                                        'email' => ($testamentReceiverRow->receiver_email) ? $testamentReceiverRow->receiver_email : "",
                                        'phone' => ($testamentReceiverRow->receiver_phone) ? $testamentReceiverRow->receiver_phone : ""
                                    );
                                }
                            }
                        }
                        
                        $testamentTrusteesRowset = array(); //$waTrusteeTable->getWaReceivers($testament_id);
                        
                        $data = array(
                            'testament_id' => $testamentRow->testament_id,
                            'full_name' => $testamentRow->full_name,
                            'gender' => $testamentRow->gender,
                            'address1' => $testamentRow->address1,
                            'address2' => $testamentRow->address2,
                            'postcode' => $testamentRow->postcode,
                            'city' => $testamentRow->city,
                            'country' => $testamentRow->country,
                            'nationality' => $testamentRow->nationality,
                            'id_type' => $testamentRow->id_type,
                            'id_number' => $testamentRow->id_number,
                            'wa_username' => $testamentRow->wa_username,
                            'birth_certificate_place' => $testamentRow->birth_certificate_place,
                            'life_insurance_policies' => $testamentRow->life_insurance_policies,
                            'deposit_account_books' => $testamentRow->deposit_account_books,
                            'other_property' => $testamentRow->other_property,
                            'testament_special_request' => $testamentRow->testament_special_request,
                            'total_paid' => $testamentRow->total_paid,
                            'is_annually' => $testamentRow->is_annually,
                            'ip_address' => $testamentRow->ip_address,
                            'latitude' => $testamentRow->latitude,
                            'longitude' => $testamentRow->longitude,
                            'representative_set' => $representative_set,
                            'bank_account_set' => $bank_account_set,
                            'testaments_others_set' => $testaments_others_set,
                            'testaments_estate_set' => $testaments_estate_set,
                            'social_account_set' => $social_account_set,
                            'testaments_witness_set' => $testaments_witness_set,
                            'receiver_userset' => $receiver_userset,
                            'receiver_names' => $receiver_names,
                            'attachment_files' => $attachment_files,
                            'created_pdf_link' => $testamentRow->created_pdf_link ? $this->makeUrl($testamentRow->created_pdf_link) : ""
                        );
                        
                        $this->common->displayMessage($this->view->translate('testament_detail_by_id'), "0", $data, "0");
                    } else {
                        $this->common->displayMessage($this->view->translate('testament_id') . ' ' . $this->view->translate('not_exist'), "1", array(), "12");
                    }
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }
    
    public function inviteWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $userId              = $this->loggedUserRow->userId;
        $gender              = $this->getRequest()->getPost('gender','');
        $full_name           = $this->getRequest()->getPost('full_name','');
        $address1            = $this->getRequest()->getPost('address1','');
        $address2            = $this->getRequest()->getPost('address2','');
        $phone_number        = $this->loggedUserRow->userPhone;
        $testament_id        = $this->getRequest()->getPost('testament_id');
        $witness_name        = $this->getRequest()->getPost('witness_name');
        $witness_email       = $this->getRequest()->getPost('witness_email');
        $witness_idtype      = $this->getRequest()->getPost('witness_idtype');
        $witness_idNumber    = $this->getRequest()->getPost('witness_idNumber');
                
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        $this->user->setLanguage($deviceLanguage);
        
        $rowcount = $witnessTable->CheckWitnessExists($testament_id, $userId, $witness_email);
        
        if (count($rowcount) < 1) {
            if (empty($testament_id)) {
                $data   = array(
                            'user_id'       => $userId,
                            'full_name'     => $full_name,
                            'gender'        => $gender,
                            'address1'      => $address1,
                            'address2'      => $address2,
                            'phone_number'  => $phone_number,
                            'creation_date' => date('Y-m-d H:i:s'),
                            'is_status'     => 2
                        );

                $testament_id = $waTestamentsTable->saveTestament($data);
            }
            $uri = $this->baseUrl() . "/wa-testament/send-request";
            
            $db = $this->db;
            $db->beginTransaction();
            
            try {
                if (!empty($witness_name)) {
                    $witnessArr = array(
                                    'testament_id'      => $testament_id,
                                    'user_id'           => $userId,
                                    'name'              => $witness_name,
                                    'username'          => $witness_email,
                                    'id_type'           => $witness_idtype,
                                    'id_number'         => $witness_idNumber,
                                    'creation_date'     => date('Y-m-d H:i:s'),
                                    'modification_date' => date('Y-m-d H:i:s'),
                                    'is_status'         => 0
                                );
                    
                    $lastInsertId = $witnessTable->saveTestamentWitnesses($witnessArr);
                    
                    $senderRow = $userTable->getRowByEmail($witness_email);
                    $message   = $full_name . ' ' . $this->view->translate('send_witness_request');
                   
                    $notificationData = array(
                                            'user_id'     => $senderRow->userId,
                                            'from_user_id' => $userId,
                                            'message'     => $message
                                        );
                    $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::INVITE_WITNESS_REQUEST);
                } else {
                    echo '<span class="btn-warning">'.$this->view->translate('select_witness_for_request').'</span>';
                    exit();
                }
            }
            catch (Exception $ex) {
                $db->rollBack();
                echo $ex->getMessage();
                exit();
            }
            $db->commit();  
            echo '<span class="btn-success">'.$this->view->translate('send_request_success').'</span>';
            exit();
        } else {
            echo '<span class="btn-warning">'.$this->view->translate('already_send_request').'</span>';
            exit();
        }
    }
    
    public function getAllWitnessByTestamentIdAction()
    {
        $this->_helper->layout->disableLayout();
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $userId           = $this->loggedUserRow->userId;        
        $testament_id     = $this->getRequest()->getPost('testament_id');
       
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }     
        $result = $witnessTable->getTestamentWitnesses($testament_id);
        $this->view->data = $result;
    }
    
    public function checkWitnessesExistAction()
    {
        $this->_helper->layout->disableLayout();
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $flag = 0;
        $userId           = $this->loggedUserRow->userId;        
        $testament_id     = $this->getRequest()->getPost('testament_id');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        $result = $witnessTable->getAllConfirmWitnessesByTestamentId($testament_id);
        if(count($result->toArray()) >= 2){
           $flag = 1;
        }
        echo $flag;
        exit();
    }
    
    public function curlRequestAction($uri, $arr)
    {
        $ch  = curl_init();
        $url = $uri;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $go     = curl_exec($ch);
        $result = json_decode($go, TRUE);
        curl_close($ch);
    }
    
    public function sendRequestAction()
    {
        $userTable = new Application_Model_DbTable_Users();
        
        $body        = $this->getRequest()->getRawBody();
        $postDataArr = json_decode($body, true);
        if (!empty($postDataArr)) {
            $msg = $this->view->translate('send_witness_request');
            $this->getUserDeviceDetailsAction($postDataArr, $msg);
        }
        exit();
    }
    
    public function updateWitnessDetailAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $witness_id      = $decoded['witness_id'];
        $id_type         = $decoded['id_type'];
        $id_number       = $decoded['id_number'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                if ($witness_id) {
                    $witnessRow = $witnessTable->getRowById($witness_id);
                }
                
                $data = array(
                    'witness_id' => $witness_id,
                    'id_type' => $id_type,
                    'id_number' => $id_number
                );
                
                try {
                    if ($witnessRow) {
                        $witnessRow->setFromArray($data);
                        $witnessRow->save();
                    }
                    
                    $userRow1    = $userTable->getRowByEmail($witnessRow->username);
                    $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
                    $postData    = array(
                        'user_id' => $userRow1->userId,
                        'username' => $witnessRow1->userEmail
                    );
                    $msg         = $this->view->translate('update_witness_request');
                    $this->getUserDeviceDetailsAction($postData, $msg);
                    
                    $message          = $userRow1->userNickName . ' ' . $msg;
                    $notificationData = array(
                        'user_id' => $userRow1->userId,
                        'from_user_id' => $witnessRow->user_id,
                        'message' => $message
                    );
                    $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::UPDATE_WITNESS_REQUEST);
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('record_updated_successfully'), '0', $data, '0');
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function confirmWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessAnswersTable     = new Application_Model_DbTable_WaTestamentAnswers();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $recorded_audio = $this->getRequest()->getPost('recorded_audio', '');
        echo 'sss';
        print_r($recorded_audio);
        exit;
        /*
        $decoded            = $this->common->Decoded();
        $userId             = $this->getRequest()->getPost('userId');
        $userDeviceId       = $this->getRequest()->getPost('userDeviceId');
        $userDeviceToken    = $this->getRequest()->getPost('userDeviceToken');
        $userSecurity       = $this->getRequest()->getPost('userSecurity');
        $witness_id         = $this->getRequest()->getPost('witness_id', '');
        $testament_id       = $this->getRequest()->getPost('testament_id', '');
        $question_set       = $this->getRequest()->getPost('question_set', '');
        $recorded_file_link = $this->getRequest()->getPost('recorded_file_link', '');
        $ip_address         = $this->getRequest()->getPost('ip_address', '');
        $latitude           = $this->getRequest()->getPost('latitude', '');
        $longitude          = $this->getRequest()->getPost('longitude', '');
        $address            = $this->getRequest()->getPost('address', '');
        $modification_date  = $this->getRequest()->getPost('modification_date', '');
        $deviceLanguage     = $this->getRequest()->getPost('deviceLanguage', '');
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $witnessRow = FALSE;
                if (!empty($witness_id)) {
                    if (!empty($testament_id)) {
                        $witnessRow = $witnessTable->getRowById($witness_id);
                        
                        if ($witnessRow['is_status'] == 0) {
                            $data = array(
                                'ip_address' => $ip_address,
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'modification_date' => date('Y-m-d H:i:s'),
                                'is_status' => 1
                            );
                            
                            $db = $this->db;
                            $db->beginTransaction();
                            
                            try {
                                if (!empty($recorded_file_link)) {
                                    $data = array_merge($data, array(
                                        'recorded_file_link' => $recorded_file_link
                                    ));
                                }
                                
                                if (!empty($_FILES['signature_image']) && count($_FILES['signature_image'])) {
                                    $signature_image_link = $_FILES['signature_image'];
                                    $response             = $this->common->upload($signature_image_link, 'signature_images');
                                    
                                    if (isset($response)) {
                                        $signature_image_link = "testaments/signature_images/" . $response['new_file_name'];
                                        $data                 = array_merge($data, array(
                                            'signature_image_link' => $signature_image_link
                                        ));
                                    }
                                } else {
                                    if ($witnessRow && $witnessRow->signature_image_link) {
                                        $witnessRow->signature_image_link = "";
                                    }
                                }
                                
                                if (!empty($question_set)) {
                                    $answersRows = $witnessAnswersTable->getAnswersByWitnessId($witness_id);
                                    
                                    if (count($answersRows) > 0) {
                                        $witnessAnswersTable->deleteAnswers($witness_id);
                                    }
                                    
                                    if (is_array($question_set)) {
                                        foreach ($question_set as $row) {
                                            $answer   = array_merge($row, array(
                                                'witness_id' => $witness_id
                                            ));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer);
                                        }
                                    } else {
                                        $question_set = json_decode($question_set);
                                        
                                        foreach ($question_set as $row) {
                                            $arr      = (array) $row;
                                            $answer   = array_merge($arr, array(
                                                'witness_id' => $witness_id
                                            ));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer);
                                        }
                                    }
                                }
                                
                                if ($witnessRow) {
                                    $witnessRow->setFromArray($data);
                                    $witnessRow->save();
                                } else {
                                    $witessesTable->saveTestamentWitnesses($data);
                                }
                                
                                $witnessRow   = $witnessTable->getRowById($witness_id);
                                $testamentRow = $waTestamentsTable->getRowById($testament_id);
                                $userRow1     = $userTable->getRowById($witnessRow['user_id']);
                                
                                $gender     = $testamentRow['gender'];
                                $is_gender  = "";
                                $is_gender1 = "";
                                $is_gender2 = "";
                                
                                if ($gender == $this->view->translate('TESTATOR')) {
                                    $is_gender  = $this->view->translate('his');
                                    $is_gender1 = $this->view->translate('him');
                                    $is_gender2 = $this->view->translate('he');
                                } else {
                                    $is_gender  = $this->view->translate('her');
                                    $is_gender1 = $this->view->translate('her');
                                    $is_gender2 = $this->view->translate('she');
                                }
                                
                                $resultData = array_merge($data, array(
                                    'full_name' => $testamentRow['full_name'],
                                    'address1' => $testamentRow['address1'],
                                    'address2' => $testamentRow['address2'],
                                    'wa_username' => $userRow1['userEmail'],
                                    'phone_number' => $testamentRow['phone_number'],
                                    'name' => $witnessRow['name'],
                                    'username' => $witnessRow['username'],
                                    'id_type' => $witnessRow['id_type'],
                                    'id_number' => $witnessRow['id_number'],
                                    'modification_date' => $witnessRow['modification_date'],
                                    'address' => $address,
                                    'gender' => $gender,
                                    'is_gender' => $is_gender,
                                    'is_gender1' => $is_gender1,
                                    'is_gender2' => $is_gender2,
                                    'recorded_file_link' => $recorded_file_link,
                                    'signature_image' => $this->makeUrl($witnessRow['signature_image_link'])
                                ));
                                
                                $this->view->data = $resultData;
                                
                                $content = $this->view->render('wa-testament/witness-pdf.phtml');
                                $header  = '<div class="pdf-header">' . $this->view->translate('WA_testament_witness') . '</div>';
                                $footer  = '<div class="pdf-footer" style="margin-top: 28px;">' . $this->view->translate('witness_pdf_footer') . '<span>{PAGENO} / {nb} </span></div>';
                                $pdf_url = $this->common->createMpdf($header, $content, $footer);
                                
                                $response = $this->common->uploadTestament($pdf_url);
                                $result   = array();
                                if (isset($response)) {
                                    $result = array(
                                        'testament_id' => $testament_id,
                                        'signature_pdf_link' => $response['new_file_name']
                                    );
                                    
                                    $witnessRow->setFromArray($result);
                                    $witnessRow->save();
                                }
                                
                                $userRow1    = $userTable->getRowByEmail($witnessRow->username);
                                $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
                                $postData    = array(
                                    'user_id' => $userRow1->userId,
                                    'username' => $witnessRow1->userEmail
                                );
                                $msg         = $this->view->translate('accepted_witness_request');
                                $this->getUserDeviceDetailsAction($postData, $msg);
                                
                                $message          = $userRow1->userNickName . ' ' . $msg;
                                $notificationData = array(
                                    'user_id' => $userRow1->userId,
                                    'from_user_id' => $witnessRow->user_id,
                                    'message' => $message
                                );
                                $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::CONFIRM_WITNESS_REQUEST);
                                
                                $result = array(
                                    'witness_id' => $witness_id,
                                    'testament_id' => $testament_id,
                                    'wintess_pdf' => $response['new_file_name']
                                );
                            }
                            catch (Exception $ex) {
                                $db->rollBack();
                                $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                            }
                            $db->commit();
                            $this->common->displayMessage($this->view->translate('witness_request_accepted'), '0', $result, '0');
                        } else {
                            $this->common->displayMessage($this->view->translate('already_request_accepted'), '1', array(), '2');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('testament_id') . ' ' . $this->view->translate('not_exist'), '1', array(), '2');
                    }
                } else {
                    $this->common->displayMessage($this->view->translate('witness_id') . ' ' . $this->view->translate('not_exist'), '1', array(), '2');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }*/
    }
    
    public function cancelWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $userId                  = $this->loggedUserRow->userId;
        $witness_id              = $this->getRequest()->getPost('witness_id');
        $witness_id              = $this->common->setDecrypt($witness_id);
        $flag = 0;
        if ($witnessRow = $witnessTable->getRowById($witness_id)) {
            $data = array(
                        'witness_id' => $witness_id,
                        'is_status' => '2'
                    );
            
            $witnessRow->setFromArray($data);
            $witnessRow->save();

            $userRow1    = $userTable->getRowByEmail($witnessRow->username);
            $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
            $postData    = array(
                'user_id' => $userRow1->userId,
                'username' => $witnessRow1->userEmail
            );
            $msg         = $this->view->translate('cancel_witness_request');

            $message          = $userRow1->userNickName . ' ' . $msg;
            $notificationData = array(
                'user_id' => $userRow1->userId,
                'from_user_id' => $witnessRow->user_id,
                'message' => $message
            );
            $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::REJECT_WITNESS_REQUEST);
            $flag = 1;
        }
        echo $flag;
        exit();
    }
    
    public function getUserDeviceDetailsAction($arr, $msg)
    {
        $userTable        = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $userId     = $arr['user_id'];
        $username   = $arr['username'];
        $witnessRow = $userTable->getRowById($userId);
        $userRow    = $userTable->getRowByEmail($username);
        
        if ($userRow && $userRow->isActive()) {
            $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($userRow->userId);
            $message            = $witnessRow->userNickName . " " . $msg;
            
            if (!empty($userLoginDeviceSet)) {
                foreach ($userLoginDeviceSet as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken) {
                        if ($loginDeviceRow->userDeviceType == "iphone") {
                            $payload['aps'] = array(
                                'alert' => $message,
                                'badge' => 0,
                                'sound' => 'Default',
                                'type' => 'wa_testament',
                                'message' => $message
                            );
                        } else {
                            $resultData = array(
                                'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                                'userId' => $userRow->userId,
                                'userName' => $userRow->userNickName
                            );
                            
                            $payload = array(
                                'message' => $message,
                                'type' => "wa_testament",
                                'result' => $resultData
                            );
                            
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }
        }
    }
    
    public function getAllReceivedWitnessRequestAction()
    {
        $userTable        = new Application_Model_DbTable_Users();
        $waTestament      = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessQuestions = new Application_Model_DbTable_WaTestamentQuestions();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $witnessRows = $witnessTable->getAllWitnessesByUserEmail($userRow['userEmail']);
                    
                    $questions  = $witnessQuestions->getQuestions($deviceLanguage);
                    $witnessArr = array();
                    
                    if (!empty($witnessRows)) {
                        foreach ($witnessRows as $row) {
                            $testamentRow = $waTestament->getTestamentRowByUserId($row['testament_id'], $row['user_id']);
                            if ($testamentRow['gender'] == $this->view->translate('TESTATOR')) {
                                $gender     = $this->view->translate('TESTATOR');
                                $is_gender  = $this->view->translate('he');
                                $is_gender1 = $this->view->translate('his');
                            } else {
                                $gender     = $this->view->translate('TESTATRIX');
                                $is_gender  = $this->view->translate('she');
                                $is_gender1 = $this->view->translate('her');
                            }
                            $full_name = ucfirst($row['full_name']);
                            $id_type   = ucfirst($row['id_type']);
                            $id_number = $row['id_number'];
                            
                            foreach ($questions as $ques) {
                                $searchReplaceArray = array(
                                    '{username}' => $full_name,
                                    '{gender}' => $gender,
                                    '{he/she}' => $is_gender,
                                    '{his/her}' => $is_gender1,
                                    '{id_type}' => $id_type,
                                    '{id_number}' => $id_number
                                );
                                $result             = str_replace(array_keys($searchReplaceArray), array_values($searchReplaceArray), $ques['question']);
                                
                                $arr[] = array(
                                    'ques_id' => $ques['id'],
                                    'question' => $result,
                                    'is_status' => 0
                                );
                            }
                            $witnessArr[] = array_merge($row, array(
                                'questions' => $arr
                            ));
                            $arr          = array();
                        }
                    }
                    $this->common->displayMessage($this->view->translate('records_found'), '0', $witnessArr, '0');
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getAllSendWitnessRequestAction()
    {
        $userTable    = new Application_Model_DbTable_Users();
        $waTestament  = new Application_Model_DbTable_WaTestaments();
        $witnessTable = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $witnessRows = $witnessTable->sendAllWitnessesByUserId($userId);
                    
                    $this->common->displayMessage($this->view->translate('records_found'), '0', $witnessRows, '0');
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', $witnessRows, '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function updateEventSendDetailAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable = new Application_Model_DbTable_WaEventSendDetails();
        $waEventSendHistoryTable = new Application_Model_DbTable_WaEventSendHistory();
        
        $decoded          = $this->common->Decoded();
        $userId           = $decoded['userId'];
        $userDeviceId     = $decoded['userDeviceId'];
        $userDeviceToken  = $decoded['userDeviceToken'];
        $userSecurity     = $decoded['userSecurity'];
        $rowId            = $decoded['event_id'];
        $vital_check      = $decoded['vital_check'];
        $response         = $decoded['response'];
        $deviceLanguage   = $decoded['deviceLanguage'];
        $onwer_alert_time = "";
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    if ($rowId) {
                        $EventSendDetailRow  = $waEventSendDetailsTable->getRowById($rowId);
                        $EventSendHistoryRow = $waEventSendHistoryTable->getRowByUserId($EventSendDetailRow['user_id'], $EventSendDetailRow['id']);
                    }
                    
                    if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_QUARTERLY) {
                        $vital_value = '3 months';
                    } else if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_BIANNUAL) {
                        $vital_value = '6 months';
                    } else {
                        $vital_value = '1 Years';
                    }
                    
                    
                    if ($EventSendHistoryRow->alert_time != $EventSendDetailRow->owner_alert_time) {
                        $owner_alert_time = date("Y-m-d H:i:s", strtotime("+$vital_value", strtotime($EventSendHistoryRow->alert_time)));
                    } else {
                        $owner_alert_time = date("Y-m-d H:i:s", strtotime("+$vital_value", strtotime($EventSendDetailRow->owner_alert_time)));
                    }
                    
                    $data = array(
                        'id' => $rowId,
                        'user_id' => $userId,
                        'vital_check' => $vital_check,
                        'vital_value' => $vital_value,
                        'owner_alert_time' => $owner_alert_time
                    );
                    
                    if ($EventSendDetailRow) {
                        $EventSendDetailRow->setFromArray($data);
                        $EventSendDetailRow->save();
                    }
                    
                    if ($EventSendHistoryRow) {
                        $responseData = array(
                            'id' => $EventSendHistoryRow->id,
                            'response' => $response
                        );
                        $EventSendHistoryRow->setFromArray($responseData);
                        $EventSendHistoryRow->save();
                    }
                    
                    $result = array(
                        'event_id' => $rowId,
                        'user_id' => $userId,
                        'owner_alert_time' => $owner_alert_time
                    );
                    
                }
                catch (Exception $e) {
                    $this->common->displayMessage($e->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('vital_check_saved'), '0', $result, '0');
            } else {
                $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function trusteeResponseAction()
    {
        $userTable                  = new Application_Model_DbTable_Users();
        $waEventTrusteeReponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $rowId           = $decoded['event_id'];
        $object_id       = $decoded['object_id'];
        $testament_id    = $decoded['testament_id'];
        $trustee_id      = $decoded['trustee_id'];
        $response        = $decoded['response'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $db = $this->db;
                $db->beginTransaction();
                
                try {
                    if ($rowId) {
                        $EventTrusteeReponseRow = $waEventTrusteeReponseTable->getRowById($rowId);
                    }
                    
                    $data = array(
                        'id' => $rowId,
                        'testament_id' => $testament_id,
                        'object_id' => $object_id,
                        'trustee_id' => $trustee_id,
                        'user_id' => $userId,
                        'response' => $response,
                        'modification_date' => date('Y-m-d H:i:s')
                    );
                    
                    if ($EventTrusteeReponseRow) {
                        $EventTrusteeReponseRow->setFromArray($data);
                        $EventTrusteeReponseRow->save();
                        
                        $insert_id = $rowId;
                    } else {
                        $insert_id = $waEventTrusteeReponseTable->saveTrusteeResponse($data);
                    }
                    
                    $result = array(
                        'rowId' => $insert_id
                    );
                }
                catch (Exception $e) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $db->commit();
                $this->common->displayMessage($this->view->translate('trustee_response_saved'), '0', $result, '0');
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function cancelAllWitnessAction()
    {
        $userTable         = new Application_Model_DbTable_Users();
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable      = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $testament_id    = $decoded['testament_id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $result          = array();
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $db = $this->db;
                $db->beginTransaction();
                
                try {
                    if ($testament_id) {
                        $witnessRow = $witnessTable->getWitnesses($testament_id);
                    }
                    
                    $data = array(
                        'is_status' => '2'
                    );
                    
                    if (count($witnessRow) > 0) {
                        foreach ($witnessRow as $row) {
                            $row->setFromArray($data);
                            $row->save();
                            
                            $userRow1    = $userTable->getRowByEmail($row->username);
                            $witnessRow1 = $userTable->getRowById($row->user_id);
                            $postData    = array(
                                'user_id' => $userRow1->userId,
                                'username' => $witnessRow1->userEmail
                            );
                            $msg         = $this->view->translate('cancel_witness_request');
                            $this->getUserDeviceDetailsAction($postData, $msg);
                        }
                        
                        $result = array(
                            'total_afftected_rows' => count($witnessRow)
                        );
                    }
                }
                catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $db->commit();
                $this->common->displayMessage($this->view->translate('all_witness_rejected'), '0', $result, '0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getAllMessagesAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable = new Application_Model_DbTable_WaEventSendDetails();
        $waEventSendHistoryTable = new Application_Model_DbTable_WaEventSendHistory();
        $waTrusteeTable          = new Application_Model_DbTable_WaTrustee();
        
        $decoded           = $this->common->Decoded();
        $userId            = $decoded['userId'];
        $userDeviceId      = $decoded['userDeviceId'];
        $userDeviceToken   = $decoded['userDeviceToken'];
        $userSecurity      = $decoded['userSecurity'];
        $testament_id      = $decoded['testament_id'];
        $deviceLanguage    = $decoded['deviceLanguage'];
        $owner_alert_times = array();
        $trusteeRows       = array();
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $waEventSendRow = $waEventSendDetailsTable->getRowByUserId($userId);
                    if ($waEventSendRow->owner_alert_count == 0) {
                        $owner_alert_times   = $waEventSendRow->owner_alert_time;
                        $trustee_alert_times = $waEventSendRow->trustee_alert_time;
                    } else {
                        $owner_alert_times = $waEventSendHistoryTable->getAllRowbyUserId($userId);
                    }
                    
                    $waTrusteeRowset = $waTrusteeTable->getWaTrustees($userId);
                    
                    if (!empty($waTrusteeRowset)) {
                        foreach ($waTrusteeRowset as $row) {
                            $trusteeRows[] = array(
                                'user_id' => $row->user_id,
                                'userNikeName' => $row->userNickName,
                                'userImage' => $row->userImage,
                                'trustee_alert_time' => $waEventSendRow->trustee_alert_time
                            );
                        }
                    }
                    
                    $result = array(
                        'owner_alert_set' => $owner_alert_times,
                        'trustee_alert_set' => $trusteeRows
                    );
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('records_found'), '0', $result, '0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getNationlityByCountryIdAction()
    {
        $countryTable = new Application_Model_DbTable_Countries();
        $country_id   = $_POST['id'];
        $nationalty   = $countryTable->getRowById($country_id);
        echo $nationalty->nationality;
        exit();
    }
    
    public function testAction()
    {
        $input    = $this->makeUrl("testaments/pdf/6379072befd804d7080484381905e8c4.pdf");
        $contents = file_get_contents($input);
        
        $Pass  = "12345678911234567891123456789112";
        $Clear = "This is a simple text. please check try agian.";
        
        $crypted = $this->fnEncrypt($contents, $Pass);
        // echo "Encrypred: ".$crypted."</br>";
        
        $newClear = $this->fnDecrypt($crypted, $Pass);
        echo "Decrypred: " . $newClear . "</br>";
        
        //Write data back to pdf file
        $pdf = fopen($_SERVER['DOCUMENT_ROOT'] . "/testaments/pdf/test.pdf", 'w');
        fwrite($pdf, $newClear);
        //close output file
        fclose($pdf);
        exit();
    }
    
    function fnEncrypt($sValue, $sSecretKey)
    {
        $_cipher                   = MCRYPT_RIJNDAEL_128;
        $_mode                     = MCRYPT_MODE_CBC;
        $_key                      = "12345678911234567891123456789112";
        $_initializationVectorSize = 0;
        
        $blockSize = mcrypt_get_block_size($_cipher, $_mode);
        $pad       = $blockSize - (strlen($sValue) % $blockSize);
        $iv        = mcrypt_create_iv($_initializationVectorSize, MCRYPT_DEV_URANDOM);
        
        $text = $iv . mcrypt_encrypt($_cipher, $_key, $sValue . str_repeat(chr($pad), $pad), $_mode, $iv);
        return base64_encode($text);
    }
    
    function fnDecrypt($sValue, $sSecretKey)
    {
        $_cipher = MCRYPT_RIJNDAEL_128;
        $_mode   = MCRYPT_MODE_CBC;
        $_key    = utf8_encode("12345678911234567891123456789112");
        
        $_initializationVectorSize = 0;
        $initializationVector      = substr(base64_decode($sValue), 0, $_initializationVectorSize);
        $data                      = mcrypt_decrypt($_cipher, $_key, substr(base64_decode($sValue), $_initializationVectorSize), $_mode, $initializationVector);
        $pad                       = ord($data[strlen($data) - 1]);
        $text                      = substr($data, 0, -$pad);
        return $text;
    }
    
    public function getAllTestamentByUserIdAction()
    {
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        
        /*$representative_set        = array();
        $bank_account_set          = array();
        $social_account_set        = array();
        $estate_devise_set         = array();
        $estate_per_devise_set     = array();
        
        if($representiveArr  = $waRepresentativeTable->getRepresentatives($testament_id)){  
        if(!empty($representiveArr)) {
        foreach ($representiveArr as $row) {
        $representative_set[]   = array(
        'testament_id'     => $testament_id,
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
        
        if($bankAccountArr  = $waBankAccountTable->getBankAccounts($testament_id)){
        if(!empty($bankAccountArr)) {
        foreach ($bankAccountArr as $row){
        $bank_account_set[] = array(
        'testament_id'          => $testament_id,
        'bank_name'             => $row['bank_name'],
        'bank_address'          => $row['bank_address'],
        'bank_account_number'   => $row['bank_account_number']
        );
        }
        }
        }
        
        if($socialAccountArr = $waSocialAccountTable->getSocialAccounts($testament_id)){
        if(!empty($socialAccountArr)){
        foreach ($socialAccountArr as $row){
        $social_account_set[]   = array(
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
        }
        
        if($estateDiviseArr = $waEstateTable->getEstateDividations($testament_id)){
        if(!empty($estateDiviseArr)){
        foreach ($estateDiviseArr as $row){
        if($row['percentage'] == 0) {
        $estate_devise_set[]  = array(  
        'testament_id'      => $testament_id,
        'item_name'         => $row['item_name'],
        'beneficiairy'      => $row['beneficiairy'],
        'percentage'        => $row['percentage'],
        );
        } else {                                        
        $estate_per_devise_set[] = array(  
        'testament_id'      => $testament_id,
        'item_name'         => $row['item_name'],
        'beneficiairy'      => $row['beneficiairy'],
        'percentage'        => $row['percentage'],
        );
        }
        }
        }
        $estateDiviseResult = array_merge($estate_devise_set,$estate_per_devise_set);
        }
        
        if($othersArr = $waOthersTable->getOthersSet($testament_id)){
        if(!empty($othersArr)){
        foreach ($othersArr as $row){
        if($row['type'] == '1') {
        $active_investments = $row['description'];
        } 
        
        if($row['type'] == '2') {                                         
        $other_possessions = $row['description'];
        }
        }
        }
        }
        */
    }
    
    public function checkEmailExistAction()
    {
        // echo $_POST['email'];
    }
    
    public function receiverAction()
    {
        echo $this->getRequest()->getParam('id');
        echo 'aaa';
        exit();
    }
    
    public function myTestamentsAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable       = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        $waWitnessesTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        
        if($userId = $this->loggedUserRow->userId)
        { 
            $activeTestaments   = $waTestamentTable->getFinalTestamentRowByUserId($userId);
            $allTestaments      = $waTestamentTable->getAllTestamentsByUserId($userId);
            $this->view->activeTestaments   = $activeTestaments;
            $this->view->allTestaments      = $allTestaments;
            $receiverRow             = $waReceiverTable->getTestamentRecievers($userId);
            $receivedTestaments      = array();

            if(count($receiverRow) > 0)
            {
                foreach ($receiverRow as $row)
                {
                    $testamentRow        = $waTestamentsTable->getRowById($row['testament_id']);
                    $witnessRows         = $waWitnessesTable->getAllConfirmWitnessesByTestamentId($row['testament_id']);

                    if(count($witnessRows) > 0) {
                        foreach ($witnessRows as $witness) {
                            $witness_set[]  = array(
                                                'witness_recorded_file' => $witness['recorded_file_link'],
                                                'witness_pdf' => $witness['signature_pdf_link']
                                            );
                        }
                    }
                            
                    $attachmentRows     = $waTestamentFilesTable->getFilesByTestamentId($row['testament_id']);
                    if(count($attachmentRows) > 0) {
                        foreach ($attachmentRows as $attachment) {
                            $attachment_set[] = array(
                                                    'type'      => $attachment['type'],
                                                    'file_link' => $attachment['file_link']
                                                );
                        }
                    }
                            
                    $receivedTestaments[]  = array_merge($result,array(
                                                'user_id'          => $row['user_id'],
                                                'full_name'        => $row['full_name'],
                                                'testament_id'     => $row['testament_id'],
                                                'created_pdf_link' => $testamentRow['created_pdf_link'] ? $testamentRow['created_pdf_link']:"",
                                                'recorded_audio'   => $testamentRow['recorded_audio'] ? $testamentRow['recorded_audio']:"",
                                                'witness_set'      => $witness_set,
                                                'attachment_set'   => $attachment_set
                                               )
                                            );
                }
            }
           $this->view->receivedTestaments = $receivedTestaments;
        }
    }
    
    public function witnessRequestAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        
        if($userId = $this->loggedUserRow->userId)
        {
            $userEmail   = $this->loggedUserRow->userEmail;
            $result      = $witnessTable->getAllWitnessesByUserEmail($userEmail);
            $this->view->result = $result;
        }            
    }
    
    public function confirmWitnessDetailsAction()
    {
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $witness_id       = $this->getRequest()->getPost('witness_id');
        $flag = 0;
        if($witnessRow  = $witnessTable->getRowById($witness_id)){
            $flag = 1;
            $this->witnessRequestViewAction($witnessRow);
        }
        echo $flag;
        exit();
    }
    
    public function witnessRequestViewAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $questionsTable   = new Application_Model_DbTable_WaTestamentQuestions();
        
        $witness_id       = "91"; //$this->getRequest()->getPost('witness_id','');
        if($witnessRow  = $witnessTable->getRowById($witness_id)){
            $lang        = 'en';
            $userEmail   = $this->loggedUserRow->userEmail;
            $questions   = $questionsTable->getQuestions($lang); 
            $testamentRow = $waTestamentTable->getTestamentRowByUserId($witnessRow['testament_id'], $witnessRow['user_id']);
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
            $full_name      = ucfirst($testamentRow['full_name']);
            $id_type        = ucfirst($testamentRow['id_type']);
            $id_number      = $testamentRow['id_number'];

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
            $this->view->result = $arr;
           //$witnessArr   = array_merge($witnessRow, array('questions' => $arr)); 
        }      
    }
    
    public function receivedTestamentsAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        
        if($this->loggeduserRow->userId){
          
        }
    }
    
    public function deleteTestamentAction(){
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        
        $testament_id    = $this->getRequest()->getPost('testament_id','');
        $flag = 0;
        if($testamentRow = $waTestamentsTable->getRowById($testament_id)){
            $data  = array(
                        'testament_id' => $testament_id,
                        'is_status' => '2'
                    );
            $testamentRow->setFromArray($data);
            $testamentRow->save();
            $flag = 1;
        }
        echo $flag;
        exit();
    }

    public function getFriendsAction()
    {
        $userTable   = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $myFriends = $friendTable->myfriends($this->loggedUserRow->userId);
        $search    = $this->getRequest()->getPost('search', '');
        
        $userImage = "../new_images/user_icon.png";
        echo '<ul class="userList">';
        foreach ($myFriends as $friend) {
            if (!empty($row->userImage)) {
                $userImage = $row->userImage;
            }
            echo "<li><a href='javascript:void(0);' onclick=\"selectUser('$friend->friend','');\"> <img src='.$userImage.' alt='' /> <h2>.$friend->userNickName. <small>Online</small></h2><div class='status online'></div></a></li>";
        }
        echo '</ul>';
        exit();
    }
    
    public function test1Action(){
       echo $this->common->setEncrypt("114");
       $id = "gizHw0VwUa6ynbpRC+uE1DkA2dHh1pVL83dVdWy/xKA=";
       echo 'encrypted '.$id;
       echo 'de'. $this->common->setDecrypt($id); exit;
    }
    
}
<?php

class Testament_TestamentController extends My_Controller_Abstract
{    
    public function preDispatch() {
        parent::preDispatch();
    }
    
    public function init() {
        $this->_helper->layout->enableLayout();
        $this->view->menu = 'my_testament';
    }
    
    public function indexAction() { 
        $this->_helper->layout->setLayout('new_layout');
    }
    
    public function checkTrusteesAction() {
        $this->_helper->layout->setLayout('new_layout');
        $flag = 0;
        
        $trusteeTable   = new Application_Model_DbTable_Trustee();
        $userId         = $this->loggedUserRow->userId;
        $myTrustees     = $trusteeTable->countMyTrustees($userId);
        if(!empty($myTrustees) && (count($myTrustees) > 4)){
            $flag = 1; 
        }
       echo $flag;
       exit();
    }
    
    public function addAction() {
        $this->_helper->layout->setLayout('new_layout');
        $userTable              = new Application_Model_DbTable_Users();
        $countryTable           = new Application_Model_DbTable_Countries();
        $userSettingTable       = new Application_Model_DbTable_UserSetting();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $friendTable            = new Application_Model_DbTable_Friends();
        
        $myFriends              = $friendTable->myfriends($this->loggedUserRow->userId);
        
        $this->view->countriesList = $countryTable->getCountriesList();
        $this->view->userEmail     = $this->loggedUserRow->userEmail;
        $this->view->friendsRowset = $myFriends;
    }
 
    public function step1Action() { 
        $userTable             = new Application_Model_DbTable_Users();
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        $waRepresentativeTable = new Application_Model_DbTable_WaTestamentRepresentatives();
        $countryTable          = new Application_Model_DbTable_Countries();
        $waCreditsTable        = new Application_Model_DbTable_WaCredits();
    
        $testament_id          = $this->getRequest()->getPost('testament_id','');
        if (!empty($testament_id)) {
            $testament_id   = $this->common->setDecrypt($testament_id);
        }  
        $userId             = $this->loggedUserRow->userId;
        $full_name          = $this->getRequest()->getPost('full_name', '');
        $gender             = $this->getRequest()->getPost('gender', '');
        $address1           = $this->getRequest()->getPost('address1', '');
        $address2           = $this->getRequest()->getPost('address2', '');
        $zipcode            = $this->getRequest()->getPost('zip', '');
        $city               = $this->getRequest()->getPost('city', '');
        $citizen_country    = $this->getRequest()->getPost('citizen_country', '');
        $country            = $this->getRequest()->getPost('country', '');
        $nationality        = $this->getRequest()->getPost('nationality', '');
        $id_type            = $this->getRequest()->getPost('id_type', '');
        $id_number          = $this->getRequest()->getPost('id_number', '');
        $other_id_type      = $this->getRequest()->getPost('other_id_type', '');
        $wa_username        = $this->getRequest()->getPost('wa_username', '');        
        $res_count          = 0;
        $first_res_gender   = $this->getRequest()->getPost('first_res_gender', '');
        $res_name           = $this->getRequest()->getPost('res_name', '');
        $res_address1       = $this->getRequest()->getPost('res_address1', '');
        $res_address2       = $this->getRequest()->getPost('res_address2', '');
        $res_city           = $this->getRequest()->getPost('res_city', '');
        $res_country        = $this->getRequest()->getPost('res_country', '');
        $res_postcode       = $this->getRequest()->getPost('res_postcode', '');
        $res_wausername     = $this->getRequest()->getPost('res_wauseremail', '');
        $res_phone_number   = $this->getRequest()->getPost('res_phone_number', '');
        $sec_res_gender     = $this->getRequest()->getPost('sec_res_gender', '');
        $res_gender         = array(
                                urldecode($first_res_gender),
                                urldecode($sec_res_gender)
                            );        
        $testamentRow          = false;
        $is_existing_testament = false;
       
        if ($testament_id) {
            $is_existing_testament = true;
            $testamentRow          = $waTestamentTable->getRowById($testament_id);
        }
        
        $db = $this->db;
        $db->beginTransaction();
        try
        {
            $data = array(
                    'user_id'         => $userId,
                    'full_name'       => urldecode($full_name),
                    'gender'          => urldecode($gender),
                    'address1'        => urldecode($address1),
                    'address2'        => urldecode($address2),
                    'postcode'        => urldecode($postcode),
                    'city'            => urldecode($city),
                    'citizen_country' => urldecode($citizen_country),
                    'country'         => $countryRow['country_english_name'],
                    'nationality'     => urldecode($nationality),
                    'id_type'         => urldecode($id_type),
                    'id_number'       => urldecode($id_number),
                    'wa_username'     => urldecode($wa_username),
                    'phone_number'    => urldecode($phone_number),
                    'creation_date'   => date('Y-m-d H:i:s'),
                    'is_status'       => 1
                );

            $countryRow = $countryTable->getRowById(urldecode($country));
            if ($testamentRow) {
                $testamentRow->setFromArray($data);
                $testamentRow->save();
                $testamentRow = $testament_id;
            } else {
                $testamentRow = $waTestamentTable->saveTestament($data);
            }
            if ($is_existing_testament) {
                $waRepresentativeTable->deleteRepresentatives($testamentRow);
            }
            ($representative_set) ? $this->InsertMultipleRows('wa_testaments_represtatives', $representative_set) : "0";
            $loggedUserArr = $this->loggedUserRow->toArray();
            $data          = array_merge($loggedUserArr, array(
                                'testament_id' => $testamentRow
                            ));
            $loggedUserRow = (object) $data;
        } catch (Exception $ex) {
            $db->rollBack();
            echo $ex->getMessage(); exit();
        }
      $db->commit();
      echo $this->common->setEncrypt($loggedUserRow->testament_id);
      exit();
    }
    
    public function step2Action()
    {
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waBankAccountTable         = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable              = new Application_Model_DbTable_WaTestamentOthers();
        $waSocialAccountTable       = new Application_Model_DbTable_WaTestamentSocialAccount();
        
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        $birth_certificate_checked  = $this->getRequest()->getPost('birth_certificate_checked', '');
        $birth_certificate_place    = $this->getRequest()->getPost('birth_certificate_place', '');
        $life_insurance_checked     = $this->getRequest()->getPost('life_insurance_checked', '');
        $life_insurance_policies    = $this->getRequest()->getPost('life_insurance_policies', '');
        $deposit_account_checked    = $this->getRequest()->getPost('deposit_account_checked', '');
        $deposit_account_books      = $this->getRequest()->getPost('deposit_account_books', '');
        $any_property_checked       = $this->getRequest()->getPost('any_property_checked', '');
        $any_property               = $this->getRequest()->getPost('any_property', '');
        $other_property_checked     = $this->getRequest()->getPost('other_property_checked', '');
        $other_property             = $this->getRequest()->getPost('other_property', '');
        $any_other_property_checked = $this->getRequest()->getPost('any_other_property_checked', '');
        $any_other_property         = $this->getRequest()->getPost('any_other_property', '');
        $account_checked            = $this->getRequest()->getPost('account_checked', '');
        $num_accounts               = $this->getRequest()->getPost('num_accounts', '');
        $active_investments_checked = $this->getRequest()->getPost('active_investments_checked', '');
        $active_investments         = $this->getRequest()->getPost('active_investments', '');
        $other_possessions_checked  = $this->getRequest()->getPost('other_possessions_checked', '');
        $other_possessions          = $this->getRequest()->getPost('other_possessions', '');
        $social_account_checked     = $this->getRequest()->getPost('social_account_checked', '');
        $is_existing_testament      = false;
        
        $data  = array(
                    'birth_certificate_place' => urldecode($birth_certificate_place),
                    'life_insurance_policies' => urldecode($life_insurance_policies),
                    'deposit_account_books'   => urldecode($deposit_account_books),
                    'any_property'            => urldecode($any_property),
                    'other_property'          => urldecode($other_property),
                    'any_other_property'      => urldecode($any_other_property),
                    'testament_special_request' => urldecode($testament_special_request),
                    'modification_date'       => date('Y-m-d H:i:s'),
                    'is_status'               => 1
                );
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }        
        if (empty($birth_certificate_checked) || empty($birth_certificate_place)) {
            $birth_certificate_place = "";
        }        
        if (empty($life_insurance_checked) || empty($life_insurance_policies)) {
            $life_insurance_policies = "";
        }        
        if (empty($deposit_account_checked) || empty($deposit_account_books)) {
            $deposit_account_books = "";
        }        
        if (empty($any_property_checked) || empty($any_property)) {
            $any_property = "";
        }        
        if (empty($other_property_checked) || empty($other_property)) {
            $other_property = "";
        }        
        if (empty($any_other_property_checked) || empty($any_other_property)) {
            $any_other_property = "";
        }        
        $bank_name           = $this->getRequest()->getPost('bank_name','');
        $bank_name           = json_decode($bank_name);
        $bank_address        = $this->getRequest()->getPost('bank_address','');
        $bank_address        = json_decode($bank_address);
        $bank_account_number = $this->getRequest()->getPost('bank_account_number','');
        $bank_account_number = json_decode($bank_account_number);
        $account_count       = 0;
        if (!empty($account_checked) && !empty($num_accounts)) {
            foreach ($bank_name as $bank) {
                $account_set[] = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '1',
                                    'bank_name'    => $bank,
                                    'bank_address' => urldecode($bank_address[$account_count]),
                                    'bank_account_number' => urldecode($bank_account_number[$account_count])
                                );
                $account_count++;
            }
        }        
        if (empty($active_investments_checked) || empty($active_investments)) {
            $active_investments = "";
        } else {
            $active_investments = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '1',
                                    'description'  => $active_investments
                                );
        }        
        if (empty($other_possessions_checked) || empty($other_possessions)) {
            $other_possessions = "";
        } else {
            $other_possessions = array(
                                    'testament_id' => $testament_id,
                                    'type'         => '2',
                                    'description'  => $other_possessions
                                );
        }    
        $testaments_others_set   = array_merge($active_investments,$other_possessions);
        $social_url              = $this->getRequest()->getPost('social_url', '');
        $social_username         = $this->getRequest()->getPost('social_username', '');
        $social_password         = $this->getRequest()->getPost('social_password', '');
        $social_security_ques    = $this->getRequest()->getPost('social_security_ques', '');
        $social_is_status        = $this->getRequest()->getPost('social_ans1', '');
        $social_managed_username = $this->getRequest()->getPost('social_manage_username', '');
        $social_count            = 0;
        if (!empty($social_account_checked) && !empty($social_url)) {
            foreach ($social_url as $social) {
                $social_account_set[] = array(
                                        'testament_id'     => $testament_id,
                                        'url'              => $social,
                                        'username'         => $social_username[$social_count],
                                        'password'         => urldecode($social_password[$social_count]),
                                        'security_ques'    => urldecode($social_security_ques[$social_count]),
                                        'is_status'        => urldecode($social_is_status[$social_count]),
                                        'managed_username' => urldecode($social_managed_username[$social_count])
                                    );
               $social_count++;
            }
        }
        
        if ($testament_id) {
            $is_existing_testament = true;
            $testamentRow          = $waTestamentTable->getRowById($testament_id);
            if ($testamentRow) {
                $testamentRow->setFromArray($data);
                $testamentRow->save();
            }
            
            if ($is_existing_testament) {
                $waBankAccountTable->deleteBankAccounts($testament_id);
                $waSocialAccountTable->deleteSocialAccounts($testament_id);
                $waOthersTable->deleteOthersSet($testament_id);
            }
            (count($account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_bank_accounts', $account_set) : "";
            (count($social_account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_social_accounts', $social_account_set) : ""; 
            (count($testaments_others_set) > 0) ? $this->InsertMultipleRows('wa_testaments_others', $testaments_others_set) : "";                     
        }
      exit();
    }
    
    public function step3Action()
    {
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waEstateTable              = new Application_Model_DbTable_WaTestamentEstate();
        
        $estate_checked             = $this->getRequest()->getPost('estate_checked', '');
        $estate_devise_checked      = $this->getRequest()->getPost('estate_devise_checked', '');
        $item_name                  = $this->getRequest()->getPost('item_name', '');
        $beneficiairy               = $this->getRequest()->getPost('beneficiairy', '');        
        $percentage                 = $this->getRequest()->getPost('devise_percentage', '');
        $devise_beneficiairy        = $this->getRequest()->getPost('devise_beneficiairy', '');
        $remaining_percentage       = $this->getRequest()->getPost('remaining_percentage', '');        
        $testament_request          = $this->getRequest()->getPost('testament_request', '');
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        
        $estate_count = 0;
        if (count($item_name) > 0) {
            foreach ($item_name as $key => $item) {
                if (!empty($item)) {
                    $estate_devise_set[] = array(
                                            'testament_id'  => $testament_id,
                                            'type'          => '1',
                                            'item_name'     => $item,
                                            'beneficiairy'  => urldecode($beneficiairy[$estate_count])
                                        );
                    $estate_count++;
                }
            }
        }
        $estate_devise_count  = 0;
        if (count($percentage) > 0) {
            foreach ($percentage as $percent) {
                if (!empty($percent)) {
                    $estate_per_devise_set[] = array(
                                                'testament_id' => $testament_id,
                                                'type'         => '2',
                                                'percentage'   => urldecode($percent),
                                                'beneficiairy' => urldecode($devise_beneficiairy[$estate_devise_count])
                                            );
                    $estate_devise_count++;
                }
            }
        }
        $testaments_estate_set = array_merge($estate_devise_set,$estate_per_devise_set);
        $data   = array(
                    'testament_special_request' => urldecode($testament_request)
                );
        if ($testament_id) {
            $testamentRow  = $waTestamentTable->getRowById($testament_id);
            if ($testamentRow) {
                $is_existing_testament = true;
                $testamentRow->setFromArray($data);
                $testamentRow->save();
            }

            if ($is_existing_testament) {
                $waEstateTable->deleteEstateDividations($testamentRow);
            }               
           (count($testaments_estate_set) > 0) ? $this->InsertMultipleRows('wa_testaments_estate', $testaments_estate_set) : "";                               
        }
      exit();
    }
    
    public function addNewTestamentAction()
    { 
        $userTable                  = new Application_Model_DbTable_Users();
        $waTestamentTable           = new Application_Model_DbTable_WaTestaments();
        $waTestmentfilesTable       = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable           = new Application_Model_DbTable_WaEventSendDetails();
        $waReceiverTable            = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable             = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable      = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable         = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable              = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable              = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable       = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable             = new Application_Model_DbTable_WaTestamentWitnesses();
        $waCreditsTable             = new Application_Model_DbTable_WaCredits();
        $countryTable               = new Application_Model_DbTable_Countries();
        
        $userId                     = $this->loggedUserRow->userId;
        $full_name                  = $this->getRequest()->getPost('full_name', '');
        $gender                     = $this->getRequest()->getPost('gender', '');
        $address1                   = $this->getRequest()->getPost('address1', '');
        $address2                   = $this->getRequest()->getPost('address2', '');
        $zipcode                    = $this->getRequest()->getPost('zip', '');
        $city                       = $this->getRequest()->getPost('city', '');
        $citizen_country            = $this->getRequest()->getPost('citizen_country', '');
        $country                    = $this->getRequest()->getPost('country', '');
        $nationality                = $this->getRequest()->getPost('nationality', '');
        $id_type                    = $this->getRequest()->getPost('id_type', '');
        $id_number                  = $this->getRequest()->getPost('id_number', '');
        $other_id_type              = $this->getRequest()->getPost('other_id_type', '');
        $wa_username                = $this->getRequest()->getPost('wa_username', '');
        $birth_certificate_checked  = $this->getRequest()->getPost('birth_certificate_place_checked', '');
        $birth_certificate_place    = $this->getRequest()->getPost('birth_certificate_place', '');
        $life_insurance_checked     = $this->getRequest()->getPost('life_insurance_policies_checked', '');
        $life_insurance_policies    = $this->getRequest()->getPost('life_insurance_policies', '');
        $deposit_account_checked    = $this->getRequest()->getPost('deposit_account_books_checked', '');
        $deposit_account_books      = $this->getRequest()->getPost('deposit_account_books', '');
        $any_property_checked       = $this->getRequest()->getPost('any_property_checked', '');
        $any_property               = $this->getRequest()->getPost('any_property', '');
        $other_property_checked     = $this->getRequest()->getPost('other_property_checked', '');
        $other_property             = $this->getRequest()->getPost('other_property', '');
        $any_other_property_checked = $this->getRequest()->getPost('any_other_property_checked', '');
        $any_other_property         = $this->getRequest()->getPost('any_other_property', '');
        $account_checked            = $this->getRequest()->getPost('account_checked', '');
        $num_accounts               = $this->getRequest()->getPost('num_accounts', '');
        $active_investments_checked = $this->getRequest()->getPost('active_investments_checked', '');
        $active_investments         = $this->getRequest()->getPost('active_investments', '');
        $other_possessions_checked  = $this->getRequest()->getPost('other_possessions_checked', '');
        $other_possessions          = $this->getRequest()->getPost('other_possessions', '');
        $social_account_checked     = $this->getRequest()->getPost('social_account_checked', '');
        $testament_special_request  = $this->getRequest()->getPost('testament_request', '');
        $testaments_witness_set     = $this->getRequest()->getPost('testaments_witness_set', '');
        $member_type                = $this->getRequest()->getPost('member_type', '');
        $total_paid                 = $this->getRequest()->getPost('total_paid', '');
        $receiver_userset           = $this->getRequest()->getPost('receiver_userset', '');
        $receiver_trusteeset        = $this->getRequest()->getPost('receiver_trusteeset', '');
        $testament_id               = $this->getRequest()->getPost('testament_id', '');
        $vital_check                = $this->getRequest()->getPost('vital_check', '');
        $ip_address                 = $this->getRequest()->getPost('ip_address', '');
        $latitude                   = $this->getRequest()->getPost('latitude', '');
        $longitude                  = $this->getRequest()->getPost('longitude', '');
        $created_address            = $this->getRequest()->getPost('created_address', '');
        $recorded_audio             = $this->getRequest()->getPost('recorded_audio', '');
        $signature_image            = $this->getRequest()->getPost('signature_image', ''); //"testaments/signature_images/sign3.png";
        $images                     = $this->getRequest()->getPost('images', '');
        $audio                      = $this->getRequest()->getPost('audio', '');
        $video                      = $this->getRequest()->getPost('video', '');
        $pdf                        = $this->getRequest()->getPost('pdf', '');
        $deviceLanguage             = $this->getRequest()->getPost('deviceLanguage', '');
        $modification_date          = $this->getRequest()->getPost('modification_date', '');
        
        $this->user->setLanguage($deviceLanguage);
        $is_gender               = "";
        $is_gender1              = "";
        $is_gender2              = "";
        $representativeArr       = array();
        $accountSetArr           = array();
        $investmentsArr          = array();
        $possessionsArr          = array();
        $estateSetArr            = array();
        $estatePerSetArr         = array();
        $socialAccountArr        = array();
        $estatePerRemaining      = "";
        $witnessesArr            = array();
        
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        
        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) 
        {
            $testamentRow          = false;
            $is_existing_testament = false;
            $testamentData         = false;
            
            if ($testament_id) {
                $is_existing_testament = true;
                $testamentRow          = $waTestamentTable->getRowById($testament_id);
            }
            
            if (!empty($testaments_witness_set)) {
                $witnessRows = $waWitnessTable->getWitnessesByIds($testaments_witness_set);
                if (!empty($witnessRows)) {
                    foreach ($witnessRows as $row) {
                        $witnessesArr[] = array(
                                            'witness_id'        => $row['witness_id'],
                                            'name'              => $row['name'],
                                            'username'          => $row['username'],
                                            'id_type'           => $row['id_type'],
                                            'id_number'         => $row['id_number'],
                                            'modification_date' => $row['modification_date'],
                                            'recorded_file_link' => $row['recorded_file_link'],
                                            'signature_image_link' => $row['signature_image_link']
                                        );
                    }
                }
            }
           
            $db = $this->db;
            $db->beginTransaction();
            try 
            {
                $user_gender = $this->view->translate('TESTATOR');
                if (strcasecmp($user_gender, $gender) == 0) {
                    $gender = $this->view->translate('TESTATOR');
                }
                
                $user_gender1 = $this->view->translate('TESTATRIX');
                if (strcasecmp($user_gender1, $gender) == 0) {
                    $gender = $this->view->translate('TESTATRIX');
                }
                
                if (empty($birth_certificate_checked) || empty($birth_certificate_place)) {
                    $birth_certificate_place = "";
                }
                
                if (empty($life_insurance_checked) || empty($life_insurance_policies)) {
                    $life_insurance_policies = "";
                }
                
                if (empty($deposit_account_checked) || empty($deposit_account_books)) {
                    $deposit_account_books = "";
                }
                
                if (empty($any_property_checked) || empty($any_property)) {
                    $any_property = "";
                }
                
                if (empty($other_property_checked) || empty($other_property)) {
                    $other_property = "";
                }
                
                if (empty($any_other_property_checked) || empty($any_other_property)) {
                    $any_other_property = "";
                }
                
                $countryRow = $countryTable->getRowById(urldecode($country));
                
                $data = array(
                            'user_id'                   => $userId,
                            'full_name'                 => urldecode($full_name),
                            'gender'                    => urldecode($gender),
                            'address1'                  => urldecode($address1),
                            'address2'                  => urldecode($address2),
                            'postcode'                  => urldecode($zipcode),
                            'city'                      => urldecode($city),
                            'citizen_country'           => urldecode($citizen_country),
                            'country'                   => $countryRow['country_english_name'],
                            'nationality'               => urldecode($nationality),
                            'id_type'                   => urldecode($id_type),
                            'id_number'                 => urldecode($id_number),
                            'wa_username'               => urldecode($wa_username),
                            'phone_number'              => urldecode($this->loggedUserRow->userPhone),
                            'birth_certificate_place'   => urldecode($birth_certificate_place),
                            'life_insurance_policies'   => urldecode($life_insurance_policies),
                            'deposit_account_books'     => urldecode($deposit_account_books),
                            'any_property'              => urldecode($any_property),
                            'other_property'            => urldecode($other_property),
                            'any_other_property'        => urldecode($any_other_property),
                            'testament_special_request' => urldecode($testament_special_request),
                            'member_type'               => urldecode($member_type),
                            'is_send'                   => '1',
                            'recorded_audio'            => urldecode($recorded_audio),
                            'ip_address'                => urldecode($ip_address),
                            'latitude'                  => urldecode($latitude),
                            'longitude'                 => urldecode($longitude),
                            'created_address'           => urldecode($created_address),
                            'total_paid'                => urldecode($total_paid),
                            'modification_date'         => date('Y-m-d H:i:s'),
                            'is_status'                 => 1
                        );
                
                if ($gender == $this->view->translate('TESTATOR')) {
                    $is_gender  = $this->view->translate('his');
                    $is_gender1 = $this->view->translate('him');
                    $is_gender2 = $this->view->translate('he');
                } else {
                    $is_gender  = $this->view->translate('her');
                    $is_gender1 = $this->view->translate('her');
                    $is_gender2 = $this->view->translate('she');
                }
                
                $res_count        = 0;
                $first_res_gender = $this->getRequest()->getPost('first_res_gender', '');
                $res_name         = $this->getRequest()->getPost('res_name', '');
                $res_address1     = $this->getRequest()->getPost('res_address1', '');
                $res_address2     = $this->getRequest()->getPost('res_address2', '');
                $res_city         = $this->getRequest()->getPost('res_city', '');
                $res_country      = $this->getRequest()->getPost('res_country', '');
                $res_postcode     = $this->getRequest()->getPost('res_postcode', '');
                $res_wausername   = $this->getRequest()->getPost('res_wauseremail', '');
                $res_phone_number = $this->getRequest()->getPost('res_phone_number', '');
                $sec_res_gender   = $this->getRequest()->getPost('sec_res_gender', '');
                $res_gender       = array(
                                        urldecode($first_res_gender),
                                        urldecode($sec_res_gender)
                                    );
                if (!empty($res_name)) {
                    foreach ($res_name as $res_name) {
                        $rep_country          = $countryTable->getRowById(urldecode($res_country[$res_count]));
                        $representativeArr[]  = array(
                                                    'testament_id' => $testament_id,
                                                    'res_gender'   => urldecode($res_gender[$res_count]),
                                                    'res_name'     => $res_name,
                                                    'res_address1' => urldecode($res_address1[$res_count]),
                                                    'res_address2' => urldecode($res_address2[$res_count]),
                                                    'res_city'     => urldecode($res_city[$res_count]),
                                                    'res_country'  => $rep_country['country_english_name'],
                                                    'res_postcode' => urldecode($res_postcode[$res_count]),
                                                    'res_phone_number' => urldecode($res_phone_number[$res_count]),
                                                    'res_wauseremail' => urldecode($res_wausername[$res_count])
                                                );
                        $res_count++;
                    }
                }
                
                $bank_name           = $this->getRequest()->getPost('bank_name', '');
                $bank_address        = $this->getRequest()->getPost('bank_address', '');
                $bank_account_number = $this->getRequest()->getPost('bank_account_number', '');
                $account_count       = 0;
                if (!empty($account_checked) && !empty($num_accounts)) {
                    foreach ($bank_name as $bank) {
                        $accountSetArr[] = array(
                                            'testament_id' => $testament_id,
                                            'type'         => '1',
                                            'bank_name'    => $bank,
                                            'bank_address' => urldecode($bank_address[$account_count]),
                                            'bank_account_number' => urldecode($bank_account_number[$account_count])
                                        );
                        $account_count++;
                    }
                }
                if (empty($active_investments_checked) || empty($active_investments)) {
                    $active_investments = "";
                } else {
                    $investmentsArr[] = array(
                                        'testament_id' => $testament_id,
                                        'type'         => '1',
                                        'description'  => $active_investments
                                    );
                }
                if (empty($other_possessions_checked) || empty($other_possessions)) {
                    $other_possessions = "";
                } else {
                    $possessionsArr[] = array(
                                        'testament_id' => $testament_id,
                                        'type'        => '2',
                                        'description' => $other_possessions
                                      );
                }
                
                $social_url              = $this->getRequest()->getPost('social_url','');
                $social_username         = $this->getRequest()->getPost('social_email','');
                $social_password         = $this->getRequest()->getPost('social_password','');
                $social_security_ques    = $this->getRequest()->getPost('social_ques','');
                $social_is_status        = $this->getRequest()->getPost('social_ans1','');
                $social_managed_username = $this->getRequest()->getPost('social_manage_username','');
                $social_count            = 0;
                if (!empty($social_account_checked) && !empty($social_url)) {
                    foreach ($social_url as $social) {
                        $socialAccountArr[] = array(
                                                'testament_id'   => $testament_id,
                                                'url'            => $social,
                                                'username'       => $social_username[$social_count],
                                                'password'       => urldecode($social_password[$social_count]),
                                                'security_ques'  => urldecode($social_security_ques[$social_count]),
                                                'is_status'      => urldecode($social_is_status[$social_count]),
                                                'managed_username' => urldecode($social_managed_username[$social_count])
                                            );
                        $social_count++;
                    }
                }
                
                $item_name    = $this->getRequest()->getPost('item_name', '');
                $beneficiairy = $this->getRequest()->getPost('beneficiairy', '');
                $estate_count = 0;
                if (count($item_name) > 0) {
                    foreach ($item_name as $key => $item) {
                        if (!empty($item)) {
                            $estateSetArr[] = array(
                                                    'testament_id' => $testament_id,
                                                    'type' => '1',
                                                    'item_name' => $item,
                                                    'beneficiairy' => urldecode($beneficiairy[$estate_count])
                                                );
                            $estate_count++;
                        }
                    }
                }
                $percentage           = $this->getRequest()->getPost('devise_percentage', '');
                $devise_beneficiairy  = $this->getRequest()->getPost('devise_beneficiairy', '');
                $remaining_percentage = $this->getRequest()->getPost('remaining_percentage', '');
                $estate_devise_count  = 0;
                if (!empty($percentage)) {
                    foreach ($percentage as $percent) {
                        if (!empty($percent)) {
                            $estatePerSetArr[] = array(
                                                        'testament_id' => $testament_id,
                                                        'type' => '2',
                                                        'percentage' => urldecode($percent),
                                                        'beneficiairy' => urldecode($devise_beneficiairy[$estate_devise_count])
                                                    );
                            $estate_devise_count++;
                        }
                    }
                }
                $count_witness           = count($witnessesArr);
                $count_bankaccount       = count($accountSetArr);
                $count_social_account    = count($socialAccountArr);
                $count_devise_set        = count($estateSetArr);
                $count_per_devise_set    = count($estatePerSetArr);
                
                $resultData       = array_merge($data, array(
                                        'count_witness'        => $count_witness,
                                        'count_bankaccount'    => $count_bankaccount,
                                        'count_social_account' => $count_social_account,
                                        'count_devise_set'     => $count_devise_set,
                                        'count_per_devise_set' => $count_per_devise_set,
                                        'is_gender'            => $is_gender,
                                        'is_gender1'           => $is_gender1,
                                        'is_gender2'           => $is_gender2,
                                        'signature_image'      => '',
                                        'active_investments'   => $active_investments,
                                        'other_possessions'    => $other_possessions,
                                        'representative_set'   => $representativeArr,
                                        'account_set'          => $accountSetArr,
                                        'social_account_set'   => $socialAccountArr,
                                        'estate_devise_set'    => $estateSetArr,
                                        'estate_per_devise_set'=> $estatePerSetArr,
                                        'estatePerRemaining'   => $remaining_percentage,
                                        'testaments_witness_set' => $witnessesArr,
                                        'user_modification_date' => $modification_date
                                    ));
               
                $this->view->data = $resultData;
                $content          = $this->view->render('wa-testament/create-testament.phtml');
                $header           = '<div class="pdf-header">' . $this->view->translate('wa_testament_heading') . ' & ' . $this->view->translate('social') . ' “' . $this->view->translate('last_wish') . '”</div>';
                $footer           = '<div class="pdf-footer" style="margin-top: 28px;">' . $this->view->translate('testament_pdf_footer') . '<br /><span>{PAGENO} / {nb} </span></div>';
                $pdf_url          = $this->common->createMpdf($header, $content, $footer);
            /*    
                if (!empty($_FILES['signature_image'])) {
                    if (!empty($testament_id)) {
                        if (count($witnessesArr) >= 2) {
                            if ($userRow->lastSeenTime <= $userRow->userModifieddate) {
                                $userLoginTime = $userRow->userModifieddate;
                            } else {
                                $userLoginTime = $userRow->lastSeenTime;
                            }
                            $vital_check_set       = $this->convertDateAction($userLoginTime, $vital_check);
                            $count_vital_check_set = count($vital_check_set);
                            $vital_check_last_date = end($vital_check_set);
                            $trustee_alert_time    = $this->convertDateAction($vital_check_last_date, $trustee_alert_time);
                            
                            if ($vital_alert_count == $count_vital_check_set) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $pdf_url);
                                $signature_image_link = $_FILES['signature_image'];
                                $response             = $this->common->upload($signature_image_link, 'signature_images');
                                
                                if (isset($response)) {
                                    $signature_image_link = "testaments/signature_images/" . $response['new_file_name'];
                                    $resultData           = array_merge($resultData, array(
                                        'signature_image' => $this->makeUrl($signature_image_link)
                                    ));
                                }
                                
                                $this->view->data = $resultData;
                                
                                $content    = ""; //$this->view->render('wa-testament/create-testament.phtml');
                                $pdf_url    = $this->common->createMpdf($header, $content, $footer);
                                $return_url = $this->common->uploadTestament($pdf_url);
                                unlink($_SERVER['DOCUMENT_ROOT'] . $pdf_url);
                                
                                $bank_account_set      = array_merge($account_set, $other_bank_account_set);
                                $testaments_others_set = array_merge($active_investments, $other_possessions);
                                $testaments_estate_set = array_merge($estate_devise_set, $estate_per_devise_set);
                                
                                if (!isset($return_url['error'])) {
                                    $data = array_merge($data, array(
                                        'created_pdf_link' => $return_url['new_file_name']
                                    ));
                                }
                                
                                if ($testamentRow) {
                                    $testamentRow->setFromArray($data);
                                    $testamentRow->save();
                                    $testamentRow = $testament_id;
                                } else {
                                    $testamentRow = $waTestamentTable->saveTestament($data);
                                }
                                
                                if ($is_existing_testament) {
                                    $waRepresentativeTable->deleteRepresentatives($testamentRow);
                                    $waBankAccountTable->deleteBankAccounts($testamentRow);
                                    $waOthersTable->deleteOthersSet($testamentRow);
                                    $waSocialAccountTable->deleteSocialAccounts($testamentRow);
                                    $waEstateTable->deleteEstateDividations($testamentRow);
                                    $waTestmentfilesTable->deleteTestamentFiles($testamentRow);
                                    $waEventSendTable->deleteEventSendTable($testamentRow, $userId);
                                }
                                ($representative_set) ? $this->InsertMultipleRows('wa_testaments_represtatives', $representative_set) : "0";
                                (count($bank_account_set) > 0) ? $this->InsertMultipleRows('wa_testaments_bank_accounts', $bank_account_set) : "";
                                (count($testaments_others_set) > 0) ? $this->InsertMultipleRows('wa_testaments_others', $testaments_others_set) : "";
                                (count($socialAccountArr) > 0) ? $this->InsertMultipleRows('wa_testaments_social_accounts', $socialAccountArr) : "";
                                (count($testaments_estate_set) > 0) ? $this->InsertMultipleRows('wa_testaments_estate', $testaments_estate_set) : "";
                                
                                $testamentData = array(
                                    'testament_id' => $testamentRow,
                                    'creation_date' => date('Y-m-d H:i:s'),
                                    'is_status' => 1
                                );
                                
                                if (!empty($images)) {
                                    if (count($images) > 0) {
                                        $tdata = array();
                                        foreach ($images as $image) {
                                            $testamentArr = $testamentData;
                                            $image_link   = $image;
                                            $tdata[]      = array_merge($testamentArr, array(
                                                'type' => 'image',
                                                'file_link' => $image_link
                                            ));
                                        }
                                        $testamentData = $tdata;
                                    }
                                }
                                
                                if (!empty($audio)) {
                                    $audio_link    = $audio;
                                    $testamentData = array_merge($testamentData, array(
                                                        'type' => 'audio',
                                                        'file_link' => $audio_link
                                                    ));
                                }
                                
                                if (!empty($video)) {
                                    $video_link    = $video;
                                    $testamentData = array_merge($testamentData, array(
                                        'type' => 'video',
                                        'file_link' => $video_link
                                    ));
                                }
                                
                                if (!empty($pdf)) {
                                    $pdf_link      = $pdf;
                                    $testamentData = array_merge($testamentData, array(
                                        'type' => 'Pdf',
                                        'file_link' => $pdf_link
                                    ));
                                }
                                
                                $this->InsertMultipleRows('wa_testaments_files', $testamentData);
                                
                                $eventSendDetailDataArr = array(
                                    'user_id'       => $userId,
                                    'testament_id'  => $testamentRow,
                                    'creation_date' => date('Y-m-d H:i:s'),
                                    'vital_check_type' => Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT,
                                    'is_status' => 0
                                );
                                $count                  = 0;
                                if (!empty($vital_check_set)) {
                                    foreach ($vital_check_set as $row) {
                                        $eventSendDetailData[] = array_merge($eventSendDetailDataArr, array(
                                            'usertype' => '1',
                                            'event_send_date' => $row,
                                            'event_vital_value' => $vital_check[$count++]
                                        ));
                                    }
                                    $eventSendDetailData[] = array_merge($eventSendDetailDataArr, array(
                                        'usertype' => '2',
                                        'event_send_date' => $trustee_alert_time[0],
                                        'event_vital_value' => $trustee_alert_set_time[0]
                                    ));
                                }
                                $eventSendDetailRow = false;
                                (count($vital_check_set) > 0) ? $this->InsertMultipleRows('wa_event_send_details', $eventSendDetailData) : "";
                                
                                if ($testamentRow) {
                                    if ($is_existing_testament) {
                                        $waReceiverTable->deleteTestamentRecievers($testamentRow);
                                        $waTrusteeTable->deleteTestamentReceivers($userId, $testamentRow);
                                    }
                                    
                                    if (!empty($receiver_userset)) {
                                        foreach ($receiver_userset as $receiverId) {
                                            $waReceiverData = array(
                                                'testament_id' => $testamentRow,
                                                'receiver_id' => $receiverId
                                            );
                                            
                                            if ($receiverId != $userId) {
                                                $waReceiverRow = $waReceiverTable->createRow($waReceiverData);
                                                $waReceiverRow->save();
                                            }
                                        }
                                    }
                                    
                                    if (!empty($receiver_trusteeset)) {
                                        foreach ($receiver_trusteeset as $receiverId) {
                                            $waTrusteeData = array(
                                                'testament_id' => $testamentRow,
                                                'user_id' => $userId,
                                                'receiver_id' => $receiverId
                                            );
                                            
                                            if ($receiverId != $userId) {
                                                $waTrusteeRow = $waTrusteeTable->createRow($waTrusteeData);
                                                $waTrusteeRow->save();
                                            }
                                        }
                                    }
                                    
                                    $spendCreditData = array(
                                        'credit_type' => 2,
                                        'userId' => $userId,
                                        'credits' => $total_paid
                                    );
                                    
                                    // $waCreditsTable->spendCredits($spendCreditData); 
                                    $result = array(
                                        'pdf_url'     => $return_url['new_file_name'],
                                        'testament_id' => $testament_id
                                    );
                                }
                            } else {
                                $this->displayMessage($this->view->translate('vital_check_count'), '1', array(), '6');
                            }
                        } else {
                            $this->displayMessage($this->view->translate('witness_not_exist'), '1', array(), '8');
                        }
                    } else {
                        $this->displayMessage($this->view->translate('testament_id_not_exist'), '1', array(), '10');
                    }
                } else {
                    $result = array(
                        'pdf_url' => $url,
                        'testament_id' => $testament_id
                    );
                }*/
                $uploaded_file_url = $this->common->fnDecrypt($this->makeUrl($pdf_url));
                echo $this->makeUrl($uploaded_file_url);
            } catch (Exception $ex) {
                $db->rollBack();
                $this->displayMessage($ex->getMessage(), '1', array(), '12');
            }
           $db->commit();
           exit();
        } else {
            $this->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
        }
    }
    
    public function step4Action()
    {
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        
        $testament_id          = $this->getRequest()->getPost('testament_id','');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
       print_r($_FILES['recorded_audio']); exit;
    /*    if ($testament_id) {
            if(isset($_FILES['recorded_audio'])){                
                $response   = $this->common->upload($_FILES['recorded_audio'],'recorded_files');
                $testamentRow    = $waTestamentTable->getRowById($testament_id);
                if ($testamentRow) {
                    $data = array(
                                'recorded_audio' => $response 
                            );
                    $testamentRow->setFromArray($data);
                    $testamentRow->save();
                }
            }else{
                echo 'Unable to record file!!'; 
            }
        }*/
      exit();
    }
        
    public function getTestamentDetailsByIdAction()
    {
        $waUserTable           = new Application_Model_DbTable_Users();
        $waTestamentTable      = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable       = new Application_Model_DbTable_WaReceiver();
        $waTestmentfilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        $waEventSendTable      = new Application_Model_DbTable_WaEventSendDetails();
        $waTrusteeTable        = new Application_Model_DbTable_WaTrustee();
        $waRepresentativeTable = new Application_Model_DbTable_WaTestamentRepresentatives();
        $waBankAccountTable    = new Application_Model_DbTable_WaTestamentBankAccount();
        $waOthersTable         = new Application_Model_DbTable_WaTestamentOthers();
        $waEstateTable         = new Application_Model_DbTable_WaTestamentEstate();
        $waSocialAccountTable  = new Application_Model_DbTable_WaTestamentSocialAccount();
        $waWitnessTable        = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $testament_id    = $decoded['testament_id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->IsUserLogin($userId, $userDeviceId);
            
            if (($userRow = $waUserTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    if ($testamentRow = $waTestamentTable->getRowById($testament_id)) {
                        // $testament_id   = $testamentRow->testament_id;
                        $waOwnerRow = $waUserTable->getRowById($userId);
                        
                        $representative_set      = array();
                        $bank_account_set        = array();
                        $social_account_set      = array();
                        $testaments_estate_set   = array();
                        $testaments_others_set   = array();
                        $testaments_witness_set  = array();
                        $receiver_userset        = array();
                        $receiver_names          = array();
                        $receiver_email_phoneset = array();
                        $attachment_files        = array();
                        
                        if ($representiveArr = $waRepresentativeTable->getRepresentatives($testament_id)) {
                            if (!empty($representiveArr)) {
                                foreach ($representiveArr as $row) {
                                    $representative_set[] = array(
                                        'testament_id' => $testamentRow,
                                        'res_name' => $row['res_name'],
                                        'res_gender' => $row['res_gender'],
                                        'res_address1' => $row['res_address1'],
                                        'res_address2' => $row['res_address2'],
                                        'res_city' => $row['res_city'],
                                        'res_postcode' => $row['res_postcode'],
                                        'res_country' => $row['res_country'],
                                        'res_wauseremail' => $row['res_wauseremail'],
                                        'res_phone_number' => $row['res_phone_number']
                                    );
                                }
                            }
                        }
                        
                        if ($bankAccountArr = $waBankAccountTable->getBankAccounts($testament_id)) {
                            if (!empty($bankAccountArr)) {
                                foreach ($bankAccountArr as $row) {
                                    $bank_account_set[] = array(
                                        'type' => $row['type'],
                                        'bank_name' => $row['bank_name'],
                                        'bank_address' => $row['bank_address'],
                                        'bank_account_number' => $row['bank_account_number']
                                    );
                                }
                            }
                        }
                        
                        if ($socialAccountArr = $waSocialAccountTable->getSocialAccounts($testament_id)) {
                            if (!empty($socialAccountArr)) {
                                foreach ($socialAccountArr as $row) {
                                    $social_account_set[] = array(
                                        'url' => $row['url'],
                                        'username' => $row['username'],
                                        'password' => $row['password'],
                                        'security_ques' => $row['security_ques'],
                                        'is_status' => $row['is_status'],
                                        'managed_username' => $row['managed_username']
                                    );
                                }
                            }
                        }
                        
                        if ($estateDividerArr = $waEstateTable->getEstateDividations($testament_id)) {
                            if (!empty($estateDividerArr)) {
                                foreach ($estateDividerArr as $row) {
                                    $testaments_estate_set[] = array(
                                        'item_name' => $row['item_name'],
                                        'beneficiairy' => $row['beneficiairy'],
                                        'percentage' => $row['percentage']
                                    );
                                }
                            }
                        }
                        
                        if ($othersArr = $waOthersTable->getOthersSet($testament_id)) {
                            if (!empty($othersArr)) {
                                foreach ($othersArr as $row) {
                                    $testaments_others_set[] = array(
                                        'type' => $row['type'],
                                        'description' => $row['description']
                                    );
                                }
                            }
                        }
                        
                        if ($witnessArr = $waWitnessTable->getWitnesses($testament_id)) {
                            if (!empty($witnessArr)) {
                                foreach ($othersArr as $row) {
                                    $testaments_witness_set[] = array(
                                        'name' => $row['name'],
                                        'username' => $row['username'],
                                        'id_type' => $row['witsness_idtype'],
                                        'id_number' => $row['witness_id_number']
                                    );
                                }
                            }
                        }
                        
                        if ($attachmentsArr = $waTestmentfilesTable->getFilesByTestamentId($testament_id)) {
                            if (!empty($attachmentsArr)) {
                                foreach ($attachmentsArr as $row) {
                                    $attachment_files[] = array(
                                        'type' => $row['type'],
                                        'file_link' => $row['file_link']
                                    );
                                }
                            }
                        }
                        
                        if ($testamentReciverRow = $waReceiverTable->getRowByTestamentIdAndReceiverId($testament_id, $userId)) {
                            $testamentReceiverRow->is_read = "1";
                            $testamentReceiverRow->save();
                        }
                        
                        $testamentReceiverRowset = $waReceiverTable->getTestamentRecievers($testament_id);
                        
                        if (!empty($testamentReceiverRowset)) {
                            foreach ($testamentReceiverRowset as $testamentReceiverRow) {
                                if ($testamentReceiverRow->receiver_id) {
                                    
                                    $receiver_userset[] = array(
                                        'userId' => $testamentReceiverRow->receiver_id,
                                        'userNickName' => ($testamentReceiverRow->userNickName) ? $testamentReceiverRow->userNickName : "",
                                        'userImage' => ($testamentReceiverRow->userImage) ? $this->makeUrl($testamentReceiverRow->userImage) : ""
                                    );
                                    
                                } else {
                                    $receiver_names[] = array(
                                        'name' => ($testamentReceiverRow->receiver_name) ? $testamentReceiverRow->receiver_name : "",
                                        'email' => ($testamentReceiverRow->receiver_email) ? $testamentReceiverRow->receiver_email : "",
                                        'phone' => ($testamentReceiverRow->receiver_phone) ? $testamentReceiverRow->receiver_phone : ""
                                    );
                                }
                            }
                        }
                        
                        $testamentTrusteesRowset = array(); //$waTrusteeTable->getWaReceivers($testament_id);
                        
                        $data = array(
                            'testament_id' => $testamentRow->testament_id,
                            'full_name' => $testamentRow->full_name,
                            'gender' => $testamentRow->gender,
                            'address1' => $testamentRow->address1,
                            'address2' => $testamentRow->address2,
                            'postcode' => $testamentRow->postcode,
                            'city' => $testamentRow->city,
                            'country' => $testamentRow->country,
                            'nationality' => $testamentRow->nationality,
                            'id_type' => $testamentRow->id_type,
                            'id_number' => $testamentRow->id_number,
                            'wa_username' => $testamentRow->wa_username,
                            'birth_certificate_place' => $testamentRow->birth_certificate_place,
                            'life_insurance_policies' => $testamentRow->life_insurance_policies,
                            'deposit_account_books' => $testamentRow->deposit_account_books,
                            'other_property' => $testamentRow->other_property,
                            'testament_special_request' => $testamentRow->testament_special_request,
                            'total_paid' => $testamentRow->total_paid,
                            'is_annually' => $testamentRow->is_annually,
                            'ip_address' => $testamentRow->ip_address,
                            'latitude' => $testamentRow->latitude,
                            'longitude' => $testamentRow->longitude,
                            'representative_set' => $representative_set,
                            'bank_account_set' => $bank_account_set,
                            'testaments_others_set' => $testaments_others_set,
                            'testaments_estate_set' => $testaments_estate_set,
                            'social_account_set' => $social_account_set,
                            'testaments_witness_set' => $testaments_witness_set,
                            'receiver_userset' => $receiver_userset,
                            'receiver_names' => $receiver_names,
                            'attachment_files' => $attachment_files,
                            'created_pdf_link' => $testamentRow->created_pdf_link ? $this->makeUrl($testamentRow->created_pdf_link) : ""
                        );
                        
                        $this->common->displayMessage($this->view->translate('testament_detail_by_id'), "0", $data, "0");
                    } else {
                        $this->common->displayMessage($this->view->translate('testament_id') . ' ' . $this->view->translate('not_exist'), "1", array(), "12");
                    }
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '0', array(), '3');
        }
    }
    
    public function inviteWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $userId              = $this->loggedUserRow->userId;
        $gender              = $this->getRequest()->getPost('gender','');
        $full_name           = $this->getRequest()->getPost('full_name','');
        $address1            = $this->getRequest()->getPost('address1','');
        $address2            = $this->getRequest()->getPost('address2','');
        $phone_number        = $this->loggedUserRow->userPhone;
        $testament_id        = $this->getRequest()->getPost('testament_id');
        $witness_name        = $this->getRequest()->getPost('witness_name');
        $witness_email       = $this->getRequest()->getPost('witness_email');
        $witness_idtype      = $this->getRequest()->getPost('witness_idtype');
        $witness_idNumber    = $this->getRequest()->getPost('witness_idNumber');
                
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        $this->user->setLanguage($deviceLanguage);
        
        $rowcount = $witnessTable->CheckWitnessExists($testament_id, $userId, $witness_email);
        
        if (count($rowcount) < 1) {
            if (empty($testament_id)) {
                $data   = array(
                            'user_id'       => $userId,
                            'full_name'     => $full_name,
                            'gender'        => $gender,
                            'address1'      => $address1,
                            'address2'      => $address2,
                            'phone_number'  => $phone_number,
                            'creation_date' => date('Y-m-d H:i:s'),
                            'is_status'     => 2
                        );

                $testament_id = $waTestamentsTable->saveTestament($data);
            }
            $uri = $this->baseUrl() . "/wa-testament/send-request";
            
            $db = $this->db;
            $db->beginTransaction();
            
            try {
                if (!empty($witness_name)) {
                    $witnessArr = array(
                                    'testament_id'      => $testament_id,
                                    'user_id'           => $userId,
                                    'name'              => $witness_name,
                                    'username'          => $witness_email,
                                    'id_type'           => $witness_idtype,
                                    'id_number'         => $witness_idNumber,
                                    'creation_date'     => date('Y-m-d H:i:s'),
                                    'modification_date' => date('Y-m-d H:i:s'),
                                    'is_status'         => 0
                                );
                    
                    $lastInsertId = $witnessTable->saveTestamentWitnesses($witnessArr);
                    
                    $senderRow = $userTable->getRowByEmail($witness_email);
                    $message   = $full_name . ' ' . $this->view->translate('send_witness_request');
                   
                    $notificationData = array(
                                            'user_id'     => $senderRow->userId,
                                            'from_user_id' => $userId,
                                            'message'     => $message
                                        );
                    $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::INVITE_WITNESS_REQUEST);
                } else {
                    echo '<span class="btn-warning">'.$this->view->translate('select_witness_for_request').'</span>';
                    exit();
                }
            }
            catch (Exception $ex) {
                $db->rollBack();
                echo $ex->getMessage();
                exit();
            }
            $db->commit();  
            echo '<span class="btn-success">'.$this->view->translate('send_request_success').'</span>';
            exit();
        } else {
            echo '<span class="btn-warning">'.$this->view->translate('already_send_request').'</span>';
            exit();
        }
    }
    
    public function getAllWitnessByTestamentIdAction()
    {
        $this->_helper->layout->disableLayout();
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $userId           = $this->loggedUserRow->userId;        
        $testament_id     = $this->getRequest()->getPost('testament_id');
       
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }     
        $result = $witnessTable->getTestamentWitnesses($testament_id);
        $this->view->data = $result;
    }
    
    public function checkWitnessesExistAction()
    {
        $this->_helper->layout->disableLayout();
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $flag = 0;
        $userId           = $this->loggedUserRow->userId;        
        $testament_id     = $this->getRequest()->getPost('testament_id');
        if (!empty($testament_id)) {
            $testament_id = $this->common->setDecrypt($testament_id);
        }
        $result = $witnessTable->getAllConfirmWitnessesByTestamentId($testament_id);
        if(count($result->toArray()) >= 2){
           $flag = 1;
        }
        echo $flag;
        exit();
    }
    
    public function curlRequestAction($uri, $arr)
    {
        $ch  = curl_init();
        $url = $uri;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $go     = curl_exec($ch);
        $result = json_decode($go, TRUE);
        curl_close($ch);
    }
    
    public function sendRequestAction()
    {
        $userTable = new Application_Model_DbTable_Users();
        
        $body        = $this->getRequest()->getRawBody();
        $postDataArr = json_decode($body, true);
        if (!empty($postDataArr)) {
            $msg = $this->view->translate('send_witness_request');
            $this->getUserDeviceDetailsAction($postDataArr, $msg);
        }
        exit();
    }
    
    public function updateWitnessDetailAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $witness_id      = $decoded['witness_id'];
        $id_type         = $decoded['id_type'];
        $id_number       = $decoded['id_number'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                if ($witness_id) {
                    $witnessRow = $witnessTable->getRowById($witness_id);
                }
                
                $data = array(
                    'witness_id' => $witness_id,
                    'id_type' => $id_type,
                    'id_number' => $id_number
                );
                
                try {
                    if ($witnessRow) {
                        $witnessRow->setFromArray($data);
                        $witnessRow->save();
                    }
                    
                    $userRow1    = $userTable->getRowByEmail($witnessRow->username);
                    $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
                    $postData    = array(
                        'user_id' => $userRow1->userId,
                        'username' => $witnessRow1->userEmail
                    );
                    $msg         = $this->view->translate('update_witness_request');
                    $this->getUserDeviceDetailsAction($postData, $msg);
                    
                    $message          = $userRow1->userNickName . ' ' . $msg;
                    $notificationData = array(
                        'user_id' => $userRow1->userId,
                        'from_user_id' => $witnessRow->user_id,
                        'message' => $message
                    );
                    $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::UPDATE_WITNESS_REQUEST);
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('record_updated_successfully'), '0', $data, '0');
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function confirmWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessAnswersTable     = new Application_Model_DbTable_WaTestamentAnswers();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $recorded_audio = $this->getRequest()->getPost('recorded_audio', '');
        echo 'sss';
        print_r($recorded_audio);
        exit;
        /*
        $decoded            = $this->common->Decoded();
        $userId             = $this->getRequest()->getPost('userId');
        $userDeviceId       = $this->getRequest()->getPost('userDeviceId');
        $userDeviceToken    = $this->getRequest()->getPost('userDeviceToken');
        $userSecurity       = $this->getRequest()->getPost('userSecurity');
        $witness_id         = $this->getRequest()->getPost('witness_id', '');
        $testament_id       = $this->getRequest()->getPost('testament_id', '');
        $question_set       = $this->getRequest()->getPost('question_set', '');
        $recorded_file_link = $this->getRequest()->getPost('recorded_file_link', '');
        $ip_address         = $this->getRequest()->getPost('ip_address', '');
        $latitude           = $this->getRequest()->getPost('latitude', '');
        $longitude          = $this->getRequest()->getPost('longitude', '');
        $address            = $this->getRequest()->getPost('address', '');
        $modification_date  = $this->getRequest()->getPost('modification_date', '');
        $deviceLanguage     = $this->getRequest()->getPost('deviceLanguage', '');
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $witnessRow = FALSE;
                if (!empty($witness_id)) {
                    if (!empty($testament_id)) {
                        $witnessRow = $witnessTable->getRowById($witness_id);
                        
                        if ($witnessRow['is_status'] == 0) {
                            $data = array(
                                'ip_address' => $ip_address,
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'modification_date' => date('Y-m-d H:i:s'),
                                'is_status' => 1
                            );
                            
                            $db = $this->db;
                            $db->beginTransaction();
                            
                            try {
                                if (!empty($recorded_file_link)) {
                                    $data = array_merge($data, array(
                                        'recorded_file_link' => $recorded_file_link
                                    ));
                                }
                                
                                if (!empty($_FILES['signature_image']) && count($_FILES['signature_image'])) {
                                    $signature_image_link = $_FILES['signature_image'];
                                    $response             = $this->common->upload($signature_image_link, 'signature_images');
                                    
                                    if (isset($response)) {
                                        $signature_image_link = "testaments/signature_images/" . $response['new_file_name'];
                                        $data                 = array_merge($data, array(
                                            'signature_image_link' => $signature_image_link
                                        ));
                                    }
                                } else {
                                    if ($witnessRow && $witnessRow->signature_image_link) {
                                        $witnessRow->signature_image_link = "";
                                    }
                                }
                                
                                if (!empty($question_set)) {
                                    $answersRows = $witnessAnswersTable->getAnswersByWitnessId($witness_id);
                                    
                                    if (count($answersRows) > 0) {
                                        $witnessAnswersTable->deleteAnswers($witness_id);
                                    }
                                    
                                    if (is_array($question_set)) {
                                        foreach ($question_set as $row) {
                                            $answer   = array_merge($row, array(
                                                'witness_id' => $witness_id
                                            ));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer);
                                        }
                                    } else {
                                        $question_set = json_decode($question_set);
                                        
                                        foreach ($question_set as $row) {
                                            $arr      = (array) $row;
                                            $answer   = array_merge($arr, array(
                                                'witness_id' => $witness_id
                                            ));
                                            $insertId = $witnessAnswersTable->saveAnswers($answer);
                                        }
                                    }
                                }
                                
                                if ($witnessRow) {
                                    $witnessRow->setFromArray($data);
                                    $witnessRow->save();
                                } else {
                                    $witessesTable->saveTestamentWitnesses($data);
                                }
                                
                                $witnessRow   = $witnessTable->getRowById($witness_id);
                                $testamentRow = $waTestamentsTable->getRowById($testament_id);
                                $userRow1     = $userTable->getRowById($witnessRow['user_id']);
                                
                                $gender     = $testamentRow['gender'];
                                $is_gender  = "";
                                $is_gender1 = "";
                                $is_gender2 = "";
                                
                                if ($gender == $this->view->translate('TESTATOR')) {
                                    $is_gender  = $this->view->translate('his');
                                    $is_gender1 = $this->view->translate('him');
                                    $is_gender2 = $this->view->translate('he');
                                } else {
                                    $is_gender  = $this->view->translate('her');
                                    $is_gender1 = $this->view->translate('her');
                                    $is_gender2 = $this->view->translate('she');
                                }
                                
                                $resultData = array_merge($data, array(
                                    'full_name' => $testamentRow['full_name'],
                                    'address1' => $testamentRow['address1'],
                                    'address2' => $testamentRow['address2'],
                                    'wa_username' => $userRow1['userEmail'],
                                    'phone_number' => $testamentRow['phone_number'],
                                    'name' => $witnessRow['name'],
                                    'username' => $witnessRow['username'],
                                    'id_type' => $witnessRow['id_type'],
                                    'id_number' => $witnessRow['id_number'],
                                    'modification_date' => $witnessRow['modification_date'],
                                    'address' => $address,
                                    'gender' => $gender,
                                    'is_gender' => $is_gender,
                                    'is_gender1' => $is_gender1,
                                    'is_gender2' => $is_gender2,
                                    'recorded_file_link' => $recorded_file_link,
                                    'signature_image' => $this->makeUrl($witnessRow['signature_image_link'])
                                ));
                                
                                $this->view->data = $resultData;
                                
                                $content = $this->view->render('wa-testament/witness-pdf.phtml');
                                $header  = '<div class="pdf-header">' . $this->view->translate('WA_testament_witness') . '</div>';
                                $footer  = '<div class="pdf-footer" style="margin-top: 28px;">' . $this->view->translate('witness_pdf_footer') . '<span>{PAGENO} / {nb} </span></div>';
                                $pdf_url = $this->common->createMpdf($header, $content, $footer);
                                
                                $response = $this->common->uploadTestament($pdf_url);
                                $result   = array();
                                if (isset($response)) {
                                    $result = array(
                                        'testament_id' => $testament_id,
                                        'signature_pdf_link' => $response['new_file_name']
                                    );
                                    
                                    $witnessRow->setFromArray($result);
                                    $witnessRow->save();
                                }
                                
                                $userRow1    = $userTable->getRowByEmail($witnessRow->username);
                                $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
                                $postData    = array(
                                    'user_id' => $userRow1->userId,
                                    'username' => $witnessRow1->userEmail
                                );
                                $msg         = $this->view->translate('accepted_witness_request');
                                $this->getUserDeviceDetailsAction($postData, $msg);
                                
                                $message          = $userRow1->userNickName . ' ' . $msg;
                                $notificationData = array(
                                    'user_id' => $userRow1->userId,
                                    'from_user_id' => $witnessRow->user_id,
                                    'message' => $message
                                );
                                $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::CONFIRM_WITNESS_REQUEST);
                                
                                $result = array(
                                    'witness_id' => $witness_id,
                                    'testament_id' => $testament_id,
                                    'wintess_pdf' => $response['new_file_name']
                                );
                            }
                            catch (Exception $ex) {
                                $db->rollBack();
                                $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                            }
                            $db->commit();
                            $this->common->displayMessage($this->view->translate('witness_request_accepted'), '0', $result, '0');
                        } else {
                            $this->common->displayMessage($this->view->translate('already_request_accepted'), '1', array(), '2');
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('testament_id') . ' ' . $this->view->translate('not_exist'), '1', array(), '2');
                    }
                } else {
                    $this->common->displayMessage($this->view->translate('witness_id') . ' ' . $this->view->translate('not_exist'), '1', array(), '2');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }*/
    }
    
    public function cancelWitnessAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $witnessTable            = new Application_Model_DbTable_WaTestamentWitnesses();
        $usertNotificationsTable = new Application_Model_DbTable_UserNotifications();
        
        $userId                  = $this->loggedUserRow->userId;
        $witness_id              = $this->getRequest()->getPost('witness_id');
        $witness_id              = $this->common->setDecrypt($witness_id);
        $flag = 0;
        if ($witnessRow = $witnessTable->getRowById($witness_id)) {
            $data = array(
                        'witness_id' => $witness_id,
                        'is_status' => '2'
                    );
            
            $witnessRow->setFromArray($data);
            $witnessRow->save();

            $userRow1    = $userTable->getRowByEmail($witnessRow->username);
            $witnessRow1 = $userTable->getRowById($witnessRow->user_id);
            $postData    = array(
                'user_id' => $userRow1->userId,
                'username' => $witnessRow1->userEmail
            );
            $msg         = $this->view->translate('cancel_witness_request');

            $message          = $userRow1->userNickName . ' ' . $msg;
            $notificationData = array(
                'user_id' => $userRow1->userId,
                'from_user_id' => $witnessRow->user_id,
                'message' => $message
            );
            $usertNotificationsTable->createTestamentNotification($notificationData, Application_Model_DbTable_NotificationType::REJECT_WITNESS_REQUEST);
            $flag = 1;
        }
        echo $flag;
        exit();
    }
    
    public function getUserDeviceDetailsAction($arr, $msg)
    {
        $userTable        = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        
        $userId     = $arr['user_id'];
        $username   = $arr['username'];
        $witnessRow = $userTable->getRowById($userId);
        $userRow    = $userTable->getRowByEmail($username);
        
        if ($userRow && $userRow->isActive()) {
            $userLoginDeviceSet = $userSettingTable->userLoginDeviceRowset($userRow->userId);
            $message            = $witnessRow->userNickName . " " . $msg;
            
            if (!empty($userLoginDeviceSet)) {
                foreach ($userLoginDeviceSet as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken) {
                        if ($loginDeviceRow->userDeviceType == "iphone") {
                            $payload['aps'] = array(
                                'alert' => $message,
                                'badge' => 0,
                                'sound' => 'Default',
                                'type' => 'wa_testament',
                                'message' => $message
                            );
                        } else {
                            $resultData = array(
                                'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                                'userId' => $userRow->userId,
                                'userName' => $userRow->userNickName
                            );
                            
                            $payload = array(
                                'message' => $message,
                                'type' => "wa_testament",
                                'result' => $resultData
                            );
                            
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }
        }
    }
    
    public function getAllReceivedWitnessRequestAction()
    {
        $userTable        = new Application_Model_DbTable_Users();
        $waTestament      = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $witnessQuestions = new Application_Model_DbTable_WaTestamentQuestions();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $witnessRows = $witnessTable->getAllWitnessesByUserEmail($userRow['userEmail']);
                    
                    $questions  = $witnessQuestions->getQuestions($deviceLanguage);
                    $witnessArr = array();
                    
                    if (!empty($witnessRows)) {
                        foreach ($witnessRows as $row) {
                            $testamentRow = $waTestament->getTestamentRowByUserId($row['testament_id'], $row['user_id']);
                            if ($testamentRow['gender'] == $this->view->translate('TESTATOR')) {
                                $gender     = $this->view->translate('TESTATOR');
                                $is_gender  = $this->view->translate('he');
                                $is_gender1 = $this->view->translate('his');
                            } else {
                                $gender     = $this->view->translate('TESTATRIX');
                                $is_gender  = $this->view->translate('she');
                                $is_gender1 = $this->view->translate('her');
                            }
                            $full_name = ucfirst($row['full_name']);
                            $id_type   = ucfirst($row['id_type']);
                            $id_number = $row['id_number'];
                            
                            foreach ($questions as $ques) {
                                $searchReplaceArray = array(
                                    '{username}' => $full_name,
                                    '{gender}' => $gender,
                                    '{he/she}' => $is_gender,
                                    '{his/her}' => $is_gender1,
                                    '{id_type}' => $id_type,
                                    '{id_number}' => $id_number
                                );
                                $result             = str_replace(array_keys($searchReplaceArray), array_values($searchReplaceArray), $ques['question']);
                                
                                $arr[] = array(
                                    'ques_id' => $ques['id'],
                                    'question' => $result,
                                    'is_status' => 0
                                );
                            }
                            $witnessArr[] = array_merge($row, array(
                                'questions' => $arr
                            ));
                            $arr          = array();
                        }
                    }
                    $this->common->displayMessage($this->view->translate('records_found'), '0', $witnessArr, '0');
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getAllSendWitnessRequestAction()
    {
        $userTable    = new Application_Model_DbTable_Users();
        $waTestament  = new Application_Model_DbTable_WaTestaments();
        $witnessTable = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $witnessRows = $witnessTable->sendAllWitnessesByUserId($userId);
                    
                    $this->common->displayMessage($this->view->translate('records_found'), '0', $witnessRows, '0');
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', $witnessRows, '12');
                }
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function updateEventSendDetailAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable = new Application_Model_DbTable_WaEventSendDetails();
        $waEventSendHistoryTable = new Application_Model_DbTable_WaEventSendHistory();
        
        $decoded          = $this->common->Decoded();
        $userId           = $decoded['userId'];
        $userDeviceId     = $decoded['userDeviceId'];
        $userDeviceToken  = $decoded['userDeviceToken'];
        $userSecurity     = $decoded['userSecurity'];
        $rowId            = $decoded['event_id'];
        $vital_check      = $decoded['vital_check'];
        $response         = $decoded['response'];
        $deviceLanguage   = $decoded['deviceLanguage'];
        $onwer_alert_time = "";
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    if ($rowId) {
                        $EventSendDetailRow  = $waEventSendDetailsTable->getRowById($rowId);
                        $EventSendHistoryRow = $waEventSendHistoryTable->getRowByUserId($EventSendDetailRow['user_id'], $EventSendDetailRow['id']);
                    }
                    
                    if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_QUARTERLY) {
                        $vital_value = '3 months';
                    } else if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_BIANNUAL) {
                        $vital_value = '6 months';
                    } else {
                        $vital_value = '1 Years';
                    }
                    
                    
                    if ($EventSendHistoryRow->alert_time != $EventSendDetailRow->owner_alert_time) {
                        $owner_alert_time = date("Y-m-d H:i:s", strtotime("+$vital_value", strtotime($EventSendHistoryRow->alert_time)));
                    } else {
                        $owner_alert_time = date("Y-m-d H:i:s", strtotime("+$vital_value", strtotime($EventSendDetailRow->owner_alert_time)));
                    }
                    
                    $data = array(
                        'id' => $rowId,
                        'user_id' => $userId,
                        'vital_check' => $vital_check,
                        'vital_value' => $vital_value,
                        'owner_alert_time' => $owner_alert_time
                    );
                    
                    if ($EventSendDetailRow) {
                        $EventSendDetailRow->setFromArray($data);
                        $EventSendDetailRow->save();
                    }
                    
                    if ($EventSendHistoryRow) {
                        $responseData = array(
                            'id' => $EventSendHistoryRow->id,
                            'response' => $response
                        );
                        $EventSendHistoryRow->setFromArray($responseData);
                        $EventSendHistoryRow->save();
                    }
                    
                    $result = array(
                        'event_id' => $rowId,
                        'user_id' => $userId,
                        'owner_alert_time' => $owner_alert_time
                    );
                    
                }
                catch (Exception $e) {
                    $this->common->displayMessage($e->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('vital_check_saved'), '0', $result, '0');
            } else {
                $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function trusteeResponseAction()
    {
        $userTable                  = new Application_Model_DbTable_Users();
        $waEventTrusteeReponseTable = new Application_Model_DbTable_WaEventTrusteeResponse();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $rowId           = $decoded['event_id'];
        $object_id       = $decoded['object_id'];
        $testament_id    = $decoded['testament_id'];
        $trustee_id      = $decoded['trustee_id'];
        $response        = $decoded['response'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $db = $this->db;
                $db->beginTransaction();
                
                try {
                    if ($rowId) {
                        $EventTrusteeReponseRow = $waEventTrusteeReponseTable->getRowById($rowId);
                    }
                    
                    $data = array(
                        'id' => $rowId,
                        'testament_id' => $testament_id,
                        'object_id' => $object_id,
                        'trustee_id' => $trustee_id,
                        'user_id' => $userId,
                        'response' => $response,
                        'modification_date' => date('Y-m-d H:i:s')
                    );
                    
                    if ($EventTrusteeReponseRow) {
                        $EventTrusteeReponseRow->setFromArray($data);
                        $EventTrusteeReponseRow->save();
                        
                        $insert_id = $rowId;
                    } else {
                        $insert_id = $waEventTrusteeReponseTable->saveTrusteeResponse($data);
                    }
                    
                    $result = array(
                        'rowId' => $insert_id
                    );
                }
                catch (Exception $e) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $db->commit();
                $this->common->displayMessage($this->view->translate('trustee_response_saved'), '0', $result, '0');
            } else {
                $this->common->displayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function cancelAllWitnessAction()
    {
        $userTable         = new Application_Model_DbTable_Users();
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable      = new Application_Model_DbTable_WaTestamentWitnesses();
        
        $decoded         = $this->common->Decoded();
        $userId          = $decoded['userId'];
        $userDeviceId    = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userSecurity    = $decoded['userSecurity'];
        $testament_id    = $decoded['testament_id'];
        $deviceLanguage  = $decoded['deviceLanguage'];
        $result          = array();
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $db = $this->db;
                $db->beginTransaction();
                
                try {
                    if ($testament_id) {
                        $witnessRow = $witnessTable->getWitnesses($testament_id);
                    }
                    
                    $data = array(
                        'is_status' => '2'
                    );
                    
                    if (count($witnessRow) > 0) {
                        foreach ($witnessRow as $row) {
                            $row->setFromArray($data);
                            $row->save();
                            
                            $userRow1    = $userTable->getRowByEmail($row->username);
                            $witnessRow1 = $userTable->getRowById($row->user_id);
                            $postData    = array(
                                'user_id' => $userRow1->userId,
                                'username' => $witnessRow1->userEmail
                            );
                            $msg         = $this->view->translate('cancel_witness_request');
                            $this->getUserDeviceDetailsAction($postData, $msg);
                        }
                        
                        $result = array(
                            'total_afftected_rows' => count($witnessRow)
                        );
                    }
                }
                catch (Exception $ex) {
                    $db->rollBack();
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $db->commit();
                $this->common->displayMessage($this->view->translate('all_witness_rejected'), '0', $result, '0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getAllMessagesAction()
    {
        $userTable               = new Application_Model_DbTable_Users();
        $waTestamentsTable       = new Application_Model_DbTable_WaTestaments();
        $waEventSendDetailsTable = new Application_Model_DbTable_WaEventSendDetails();
        $waEventSendHistoryTable = new Application_Model_DbTable_WaEventSendHistory();
        $waTrusteeTable          = new Application_Model_DbTable_WaTrustee();
        
        $decoded           = $this->common->Decoded();
        $userId            = $decoded['userId'];
        $userDeviceId      = $decoded['userDeviceId'];
        $userDeviceToken   = $decoded['userDeviceToken'];
        $userSecurity      = $decoded['userSecurity'];
        $testament_id      = $decoded['testament_id'];
        $deviceLanguage    = $decoded['deviceLanguage'];
        $owner_alert_times = array();
        $trusteeRows       = array();
        
        $this->user->setLanguage($deviceLanguage);
        
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array(
                $userId,
                $userDeviceId,
                $userDeviceToken
            ));
            $this->common->isUserLogin($userId, $userDeviceId);
            
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                try {
                    $waEventSendRow = $waEventSendDetailsTable->getRowByUserId($userId);
                    if ($waEventSendRow->owner_alert_count == 0) {
                        $owner_alert_times   = $waEventSendRow->owner_alert_time;
                        $trustee_alert_times = $waEventSendRow->trustee_alert_time;
                    } else {
                        $owner_alert_times = $waEventSendHistoryTable->getAllRowbyUserId($userId);
                    }
                    
                    $waTrusteeRowset = $waTrusteeTable->getWaTrustees($userId);
                    
                    if (!empty($waTrusteeRowset)) {
                        foreach ($waTrusteeRowset as $row) {
                            $trusteeRows[] = array(
                                'user_id' => $row->user_id,
                                'userNikeName' => $row->userNickName,
                                'userImage' => $row->userImage,
                                'trustee_alert_time' => $waEventSendRow->trustee_alert_time
                            );
                        }
                    }
                    
                    $result = array(
                        'owner_alert_set' => $owner_alert_times,
                        'trustee_alert_set' => $trusteeRows
                    );
                }
                catch (Exception $ex) {
                    $this->common->displayMessage($ex->getMessage(), '1', array(), '12');
                }
                $this->common->displayMessage($this->view->translate('records_found'), '0', $result, '0');
            } else {
                $this->common->dislayMessage($this->view->translate('account_not_exist'), '1', array(), '2');
            }
        } else {
            $this->common->displayMessage($this->view->translate('not_access_service'), '1', array(), '3');
        }
    }
    
    public function getNationlityByCountryIdAction()
    {
        $countryTable = new Application_Model_DbTable_Countries();
        $country_id   = $_POST['id'];
        $nationalty   = $countryTable->getRowById($country_id);
        echo $nationalty->nationality;
        exit();
    }
    
    public function testAction()
    {
        $input    = $this->makeUrl("testaments/pdf/6379072befd804d7080484381905e8c4.pdf");
        $contents = file_get_contents($input);
        
        $Pass  = "12345678911234567891123456789112";
        $Clear = "This is a simple text. please check try agian.";
        
        $crypted = $this->fnEncrypt($contents, $Pass);
        // echo "Encrypred: ".$crypted."</br>";
        
        $newClear = $this->fnDecrypt($crypted, $Pass);
        echo "Decrypred: " . $newClear . "</br>";
        
        //Write data back to pdf file
        $pdf = fopen($_SERVER['DOCUMENT_ROOT'] . "/testaments/pdf/test.pdf", 'w');
        fwrite($pdf, $newClear);
        //close output file
        fclose($pdf);
        exit();
    }
    
    function fnEncrypt($sValue, $sSecretKey)
    {
        $_cipher                   = MCRYPT_RIJNDAEL_128;
        $_mode                     = MCRYPT_MODE_CBC;
        $_key                      = "12345678911234567891123456789112";
        $_initializationVectorSize = 0;
        
        $blockSize = mcrypt_get_block_size($_cipher, $_mode);
        $pad       = $blockSize - (strlen($sValue) % $blockSize);
        $iv        = mcrypt_create_iv($_initializationVectorSize, MCRYPT_DEV_URANDOM);
        
        $text = $iv . mcrypt_encrypt($_cipher, $_key, $sValue . str_repeat(chr($pad), $pad), $_mode, $iv);
        return base64_encode($text);
    }
    
    function fnDecrypt($sValue, $sSecretKey)
    {
        $_cipher = MCRYPT_RIJNDAEL_128;
        $_mode   = MCRYPT_MODE_CBC;
        $_key    = utf8_encode("12345678911234567891123456789112");
        
        $_initializationVectorSize = 0;
        $initializationVector      = substr(base64_decode($sValue), 0, $_initializationVectorSize);
        $data                      = mcrypt_decrypt($_cipher, $_key, substr(base64_decode($sValue), $_initializationVectorSize), $_mode, $initializationVector);
        $pad                       = ord($data[strlen($data) - 1]);
        $text                      = substr($data, 0, -$pad);
        return $text;
    }
    
    public function getAllTestamentByUserIdAction()
    {
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        
        /*$representative_set        = array();
        $bank_account_set          = array();
        $social_account_set        = array();
        $estate_devise_set         = array();
        $estate_per_devise_set     = array();
        
        if($representiveArr  = $waRepresentativeTable->getRepresentatives($testament_id)){  
        if(!empty($representiveArr)) {
        foreach ($representiveArr as $row) {
        $representative_set[]   = array(
        'testament_id'     => $testament_id,
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
        
        if($bankAccountArr  = $waBankAccountTable->getBankAccounts($testament_id)){
        if(!empty($bankAccountArr)) {
        foreach ($bankAccountArr as $row){
        $bank_account_set[] = array(
        'testament_id'          => $testament_id,
        'bank_name'             => $row['bank_name'],
        'bank_address'          => $row['bank_address'],
        'bank_account_number'   => $row['bank_account_number']
        );
        }
        }
        }
        
        if($socialAccountArr = $waSocialAccountTable->getSocialAccounts($testament_id)){
        if(!empty($socialAccountArr)){
        foreach ($socialAccountArr as $row){
        $social_account_set[]   = array(
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
        }
        
        if($estateDiviseArr = $waEstateTable->getEstateDividations($testament_id)){
        if(!empty($estateDiviseArr)){
        foreach ($estateDiviseArr as $row){
        if($row['percentage'] == 0) {
        $estate_devise_set[]  = array(  
        'testament_id'      => $testament_id,
        'item_name'         => $row['item_name'],
        'beneficiairy'      => $row['beneficiairy'],
        'percentage'        => $row['percentage'],
        );
        } else {                                        
        $estate_per_devise_set[] = array(  
        'testament_id'      => $testament_id,
        'item_name'         => $row['item_name'],
        'beneficiairy'      => $row['beneficiairy'],
        'percentage'        => $row['percentage'],
        );
        }
        }
        }
        $estateDiviseResult = array_merge($estate_devise_set,$estate_per_devise_set);
        }
        
        if($othersArr = $waOthersTable->getOthersSet($testament_id)){
        if(!empty($othersArr)){
        foreach ($othersArr as $row){
        if($row['type'] == '1') {
        $active_investments = $row['description'];
        } 
        
        if($row['type'] == '2') {                                         
        $other_possessions = $row['description'];
        }
        }
        }
        }
        */
    }
    
    public function checkEmailExistAction()
    {
        // echo $_POST['email'];
    }
    
    public function receiverAction()
    {
        echo $this->getRequest()->getParam('id');
        echo 'aaa';
        exit();
    }
    
    public function myTestamentsAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable       = new Application_Model_DbTable_WaTestaments();
        $waReceiverTable        = new Application_Model_DbTable_WaReceiver();
        $waWitnessesTable       = new Application_Model_DbTable_WaTestamentWitnesses();
        $waTestamentFilesTable  = new Application_Model_DbTable_WaTestamentFiles();
        
        if($userId = $this->loggedUserRow->userId)
        { 
            $activeTestaments   = $waTestamentTable->getFinalTestamentRowByUserId($userId);
            $allTestaments      = $waTestamentTable->getAllTestamentsByUserId($userId);
            $this->view->activeTestaments   = $activeTestaments;
            $this->view->allTestaments      = $allTestaments;
            $receiverRow             = $waReceiverTable->getTestamentRecievers($userId);
            $receivedTestaments      = array();

            if(count($receiverRow) > 0)
            {
                foreach ($receiverRow as $row)
                {
                    $testamentRow        = $waTestamentsTable->getRowById($row['testament_id']);
                    $witnessRows         = $waWitnessesTable->getAllConfirmWitnessesByTestamentId($row['testament_id']);

                    if(count($witnessRows) > 0) {
                        foreach ($witnessRows as $witness) {
                            $witness_set[]  = array(
                                                'witness_recorded_file' => $witness['recorded_file_link'],
                                                'witness_pdf' => $witness['signature_pdf_link']
                                            );
                        }
                    }
                            
                    $attachmentRows     = $waTestamentFilesTable->getFilesByTestamentId($row['testament_id']);
                    if(count($attachmentRows) > 0) {
                        foreach ($attachmentRows as $attachment) {
                            $attachment_set[] = array(
                                                    'type'      => $attachment['type'],
                                                    'file_link' => $attachment['file_link']
                                                );
                        }
                    }
                            
                    $receivedTestaments[]  = array_merge($result,array(
                                                'user_id'          => $row['user_id'],
                                                'full_name'        => $row['full_name'],
                                                'testament_id'     => $row['testament_id'],
                                                'created_pdf_link' => $testamentRow['created_pdf_link'] ? $testamentRow['created_pdf_link']:"",
                                                'recorded_audio'   => $testamentRow['recorded_audio'] ? $testamentRow['recorded_audio']:"",
                                                'witness_set'      => $witness_set,
                                                'attachment_set'   => $attachment_set
                                               )
                                            );
                }
            }
           $this->view->receivedTestaments = $receivedTestaments;
        }
    }
    
    public function witnessRequestAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        
        if($userId = $this->loggedUserRow->userId)
        {
            $userEmail   = $this->loggedUserRow->userEmail;
            $result      = $witnessTable->getAllWitnessesByUserEmail($userEmail);
            $this->view->result = $result;
        }            
    }
    
    public function confirmWitnessDetailsAction()
    {
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $witness_id       = $this->getRequest()->getPost('witness_id');
        $flag = 0;
        if($witnessRow  = $witnessTable->getRowById($witness_id)){
            $flag = 1;
            $this->witnessRequestViewAction($witnessRow);
        }
        echo $flag;
        exit();
    }
    
    public function witnessRequestViewAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        $witnessTable     = new Application_Model_DbTable_WaTestamentWitnesses();
        $questionsTable   = new Application_Model_DbTable_WaTestamentQuestions();
        
        $witness_id       = "91"; //$this->getRequest()->getPost('witness_id','');
        if($witnessRow  = $witnessTable->getRowById($witness_id)){
            $lang        = 'en';
            $userEmail   = $this->loggedUserRow->userEmail;
            $questions   = $questionsTable->getQuestions($lang); 
            $testamentRow = $waTestamentTable->getTestamentRowByUserId($witnessRow['testament_id'], $witnessRow['user_id']);
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
            $full_name      = ucfirst($testamentRow['full_name']);
            $id_type        = ucfirst($testamentRow['id_type']);
            $id_number      = $testamentRow['id_number'];

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
            $this->view->result = $arr;
           //$witnessArr   = array_merge($witnessRow, array('questions' => $arr)); 
        }      
    }
    
    public function receivedTestamentsAction()
    {
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentTable = new Application_Model_DbTable_WaTestaments();
        
        if($this->loggeduserRow->userId){
          
        }
    }
    
    public function deleteTestamentAction(){
        $this->_helper->layout->setLayout('new_layout');
        $waTestamentsTable = new Application_Model_DbTable_WaTestaments();
        
        $testament_id    = $this->getRequest()->getPost('testament_id','');
        $flag = 0;
        if($testamentRow = $waTestamentsTable->getRowById($testament_id)){
            $data  = array(
                        'testament_id' => $testament_id,
                        'is_status' => '2'
                    );
            $testamentRow->setFromArray($data);
            $testamentRow->save();
            $flag = 1;
        }
        echo $flag;
        exit();
    }

    public function getFriendsAction()
    {
        $userTable   = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        
        $myFriends = $friendTable->myfriends($this->loggedUserRow->userId);
        $search    = $this->getRequest()->getPost('search', '');
        
        $userImage = "../new_images/user_icon.png";
        echo '<ul class="userList">';
        foreach ($myFriends as $friend) {
            if (!empty($row->userImage)) {
                $userImage = $row->userImage;
            }
            echo "<li><a href='javascript:void(0);' onclick=\"selectUser('$friend->friend','');\"> <img src='.$userImage.' alt='' /> <h2>.$friend->userNickName. <small>Online</small></h2><div class='status online'></div></a></li>";
        }
        echo '</ul>';
        exit();
    }
    
    public function test1Action(){
       echo $this->common->setEncrypt("114");
       $id = "gizHw0VwUa6ynbpRC+uE1DkA2dHh1pVL83dVdWy/xKA=";
       echo 'encrypted '.$id;
       echo 'de'. $this->common->setDecrypt($id); exit;
    }
    
}
