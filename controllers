<?php

class AuthController extends My_Controller_Abstract {

    public function preDispatch() {
        parent::preDispatch();

        $this->_helper->layout->disableLayout();
    }

    /**
     * signup function is for webservice of signup user
     * required parameters: userSecurity, userName, userFullName, userEmail, userPassword, userCountryCode
     * userPhone, userFbId, userDeviceToken, userDeviceId, userDeviceType, secQuestion, secAnswer
     */
    public function init() {
        $messages = $this->_helper->flashMessenger->getMessages();
        if (!empty($messages)) {
            $this->_helper->layout->getView()->message = $messages[0];
        }
    }

    public function resetdataAction() {
        
    }

    public function resettblsAction() {
        //When delete trustee, still friend. so Points update in friend.
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $password = $this->common->Request('code');
        if ($password == "wa123") {
            $account = new Application_Model_DbTable_AccountSetting();
            $blockUser = new Application_Model_DbTable_BlockUsers();
            $chgUser = new Application_Model_DbTable_ChangeUserDetails();
            $rmFrnds = new Application_Model_DbTable_CustomizeRemoveFriends();
            $trusteeDetails = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
            $frnds = new Application_Model_DbTable_Friends();
            $grpmembers = new Application_Model_DbTable_GroupMembers();
            $grps = new Application_Model_DbTable_Groups();
            $phoneContact = new Application_Model_DbTable_PhoneContacts();
            $secGrpMem = new Application_Model_DbTable_SecGroupMembers();
            $secGrps = new Application_Model_DbTable_SecretGroups();
            $trustee = new Application_Model_DbTable_Trustee();
            $users = new Application_Model_DbTable_Users();
            $userSetting = new Application_Model_DbTable_UserSetting();
            $userNotification = new Application_Model_DbTable_UserNotifications();
            $wa = new Application_Model_DbTable_Wa();
            $wa_event = new Application_Model_DbTable_WaEventSendDetails();
            $wa_event_trustee = new Application_Model_DbTable_WaEventTrusteeResponse();
            $wa_points = new Application_Model_DbTable_WaPoints();
            $wa_rec = new Application_Model_DbTable_WaReceiver();
            $wa_trustee = new Application_Model_DbTable_WaTrustee();
            $wa_credits_type = new Application_Model_DbTable_WaCreditsType();
            $wa_credits = new Application_Model_DbTable_WaCredits();

            $account->getAdapter()->query('TRUNCATE TABLE accountSetting');
            $blockUser->getAdapter()->query('TRUNCATE TABLE blockUsers');
            $chgUser->getAdapter()->query('TRUNCATE TABLE change_user_details');
            $rmFrnds->getAdapter()->query('TRUNCATE TABLE customize_remove_frnds');
            $trusteeDetails->getAdapter()->query('TRUNCATE TABLE editFriendTrusteeDetails');
            $frnds->getAdapter()->query('TRUNCATE TABLE friends');
            $grpmembers->getAdapter()->query('TRUNCATE TABLE groupMembers');
            $grps->getAdapter()->query('TRUNCATE TABLE groups');
            $phoneContact->getAdapter()->query('TRUNCATE TABLE phoneContact');
            $secGrpMem->getAdapter()->query('TRUNCATE TABLE secGroupMembers');
            $secGrps->getAdapter()->query('TRUNCATE TABLE secretGroup');
            $trustee->getAdapter()->query('TRUNCATE TABLE trustees');
            $users->getAdapter()->query('TRUNCATE TABLE users');
            $userSetting->getAdapter()->query('TRUNCATE TABLE usrSetting');
            $userNotification->getAdapter()->query('TRUNCATE TABLE user_notifications');
            $wa->getAdapter()->query('TRUNCATE TABLE wa');
            $wa_event_trustee->getAdapter()->query('TRUNCATE TABLE wa_event_trustee_response');
            $wa_points->getAdapter()->query('TRUNCATE TABLE wa_points');
            $wa_credits->getAdapter()->query('TRUNCATE TABLE wa_credits');
            $db->query('TRUNCATE TABLE bonus_points');
            $db->query('TRUNCATE TABLE wa_event_send_details');
            $db->query('TRUNCATE TABLE wa_payment_info');
            $db->query('TRUNCATE TABLE wa_plan_type');
            $db->query('TRUNCATE TABLE wa_trustees');
            $db->query('TRUNCATE TABLE wa_receivers');
            $db->query('TRUNCATE TABLE wa_testaments');
            $db->query('TRUNCATE TABLE wa_testaments_bank_accounts');
            $db->query('TRUNCATE TABLE wa_testaments_estate');
            $db->query('TRUNCATE TABLE wa_testaments_files');
            $db->query('TRUNCATE TABLE wa_testaments_others');
            $db->query('TRUNCATE TABLE wa_testaments_represtatives');
            $db->query('TRUNCATE TABLE wa_testaments_social_accounts');
            $db->query('TRUNCATE TABLE wa_testaments_witnesses');
            $this->_helper->flashMessenger('Reset tables successfully.');
        } else {
            $this->_helper->flashMessenger('Invalid Password');
        }
        $this->_helper->redirector('resetdata');
    }

    public function resetdbAction() {
        $truncate = new Application_Model_DbTable_TruncateAll();
        $truncate->truncateTables();
    }

    public function signup2Action() {
        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $arrDtat = array(
            "userNickName" => $decoded['name'],
            "userFullName" => $decoded['name'],
        );
        $userRow = $userTable->createRow($arrDtat);
        $userRow->save();
    }

    public function signupAction() {

        $decoded = $this->common->Decoded();
        $userSecurity = $this->common->Request('userSecurity');
        $this->user->setLanguage($decoded['deviceLanguage']);
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();

        $userSecurity = "afe2eb9b1de658a39e896591999e1b59";

        if ($userSecurity == $this->servicekey) {

            $userNickName = trim($this->common->Request('userNickName'));
            $userFullName = trim($this->common->Request('userFullName'));   // nick name

            $userEmail = trim($this->common->Request('userEmail'));
            $userPassword = $this->common->Request('userPassword');
            $userCountryCode = $this->common->Request('userCountryCode');
            $userPhone = $this->common->Request('userPhone');
            $userDeviceToken = $this->common->Request('userDeviceToken');
            $userDeviceId = $this->common->Request('userDeviceId');
            $userDeviceType = $this->common->Request('userDeviceType');
            $secQuestion = $this->common->Request('secQuestion');
            $secAnswer = $this->common->Request('secAnswer');
            $deviceLanguage = $this->common->Request('deviceLanguage');

            /*      $userNickName = "EEqwq";
              $userFullName = "EEqww";
              $userEmail = "egshsgeee@gm.com";
              $userPassword = "123456";
              $userCountryCode = "1";
              $userPhone = "1231273712";
              $userDeviceToken = "1234";
              $userDeviceId = "B99BD5F1-D6A5-44D6-977E-8DA5C0FAF214";
              $userDeviceType = "iphone";
              $secQuestion = "testing";
              $secAnswer = "test";
             */
            $this->common->checkEmptyParameter1(array($userFullName, $userCountryCode, $userPhone, $userEmail, $userPassword, $userDeviceId, $userDeviceType, $userDeviceToken, $secQuestion, $secAnswer)); // To check for compulsary parameters

            if (!$userNickName) {
                $userNickName = $userFullName;
            }

            if (!empty($userEmail)) {
                $checkEmail = $this->user->checkRecords('userEmail', $userEmail);

                if ($checkEmail != "") {
                    $this->common->displayMessage('This email is already exist', "1", array(), "5");
                    exit;
                }
            }

            $phoneOwnerRow = $userTable->phoneOwnerRow($userPhone);

            $random = rand(1000, 9999);

            if ($userCountryCode['0'] != "+") {
                $userCountryCode = "+" . $userCountryCode;
            }

            $userPhoneNumber = trim($userCountryCode) . trim($userPhone);

            $userPhoneNumber = str_replace(' ', '', $userPhoneNumber);
            $data_array = array(
                "userDeviceId" => $userDeviceId,
                "userDeviceToken" => $userDeviceToken,
                "userDeviceType" => $userDeviceType,
                "userNickName" => $userNickName,
                "userFullName" => $userFullName,
                "userEmail" => $userEmail,
                "userPassword" => md5($userPassword),
                "userPasscode" => $random,
                "userCountryCode" => $userCountryCode,
                "userPhone" => $userPhone,
                "phoneWithCode" => $userPhoneNumber,
                "is_phone_owner" => "0",
                "userThemeColor" => "7",
                "secQuestion" => $secQuestion,
                "secAnswer" => $secAnswer,
                "subsPlanStartDate" => date('Y-m-d H:i:s'),
                "userModifieddate" => date('Y-m-d H:i:s'),
                "userInsertDate" => date('Y-m-d H:i:s'),
                "userStatus" => "0",
                "lastSeenTime" => date("Y-m-d H:i:s")
            );

            $userRow = $userTable->createRow($data_array);
            $userRow->save();

            $quickBloxId = $this->quickBloxSignup($userRow);

            $userRow->quickBloxId = $quickBloxId;

            $userRow->save();

            $userID = $userRow->userId;

            if (!$phoneOwnerRow) {
                $para = array(
                    'name' => $userRow->userNickName,
                    'code' => $random,
                    'baseUrl' => $this->baseUrl,
                    'deviceLanguage' => $deviceLanguage,
                );

                $this->user->sendmail($para, 'registration.phtml', $userEmail, 'Welcome To WA-app');

                $message = "Your WA-app passcode is $random. Enter this code to verify your device.";
                $this->common->sendSms_recommend($userPhoneNumber, $message);
            } else {
                $para = array(
                    'name' => $phoneOwnerRow->userNickName,
                    'new_user' => $userRow->userNickName,
                    'code' => $random,
                    'baseUrl' => $this->baseUrl,
                    'lang' => $lang,
                );

                $this->user->sendmail($para, 'registration.phtml', $phoneOwnerRow->userEmail, 'Welcome To WA-app');

                $params = array(
                    'new_user' => $userRow->userNickName,
                    'phone_owner_name' => $phoneOwnerRow->userNickName,
                    'baseUrl' => $this->baseUrl,
                    'lang' => $lang,
                );

                $this->user->sendmail($params, 'registration_new_user.phtml', $userRow->userEmail, 'Welcome To WA-app');
            }

            $responseData = array(
                'userId' => $userRow->userId,
                'userNickName' => $userRow->userNickName,
                'userFullName' => $userRow->userFullName,
                'userEmail' => $userRow->userEmail,
                'userThemeColor' => $userRow->userThemeColor,
                'userImage' => $userRow->userImage,
                'userCountryCode' => $userRow->userCountryCode,
                'userPhone' => $userRow->userPhone,
                'userPasscode' => $random,
                'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
            );

            $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
            $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

            $userSettingData = array(
                "userId" => $userRow->userId,
                "userDeviceId" => $userDeviceId,
                "userDeviceToken" => $userDeviceToken,
                "userDeviceType" => $userDeviceType,
                "userLoginTime" => date('Y-m-d H:i:s')
            );

            $userSettingRow = $userSettingTable->createRow($userSettingData);
            $userSettingRow->save();

            $accountSettingData = array(
                'userId' => $userRow->userId,
                'auto_friends' => "on",
                'extra_privacy' => "off",
                'creationDate' => date("Y-m-d H:i:s"),
                'modifyDate' => date("Y-m-d H:i:s"),
                'availableForDates' => "0",
                'mediaType' => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
            );

            $accountSettingRow = $accountSettingTable->createRow($accountSettingData);
            $accountSettingRow->save();

            $phoneContactTable->updateWaUserIdWithPhone($userRow);

            $this->common->displayMessage('Signup successful', "0", $responseData, "0");
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "6");
        }
    }

    public function signupnewAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $this->common->Request('userSecurity');
        $this->user->setLanguage($decoded['deviceLanguage']);
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $phoneContactTable = new Application_Model_DbTable_PhoneContacts();
        if (1 == 1) {
            $userNickName = trim($this->common->Request('userNickName'));
            $userFullName = trim($this->common->Request('userFullName'));   // nick name
            $userEmail = trim($this->common->Request('userEmail'));
            $userPassword = $this->common->Request('userPassword');
            $userCountryCode = preg_replace('/\s+/', '', $this->common->Request('userCountryCode'));
            $userPhone = $this->common->Request('userPhone');
            $userDeviceToken = $this->common->Request('userDeviceToken');
            $userDeviceId = $this->common->Request('userDeviceId');
            $userDeviceType = $this->common->Request('userDeviceType');
            $deviceLanguage = $this->common->Request('deviceLanguage');
////
//              $userNickName = "EEqwq";
//              $userFullName = "EEqww";
//              $userEmail = "";
//              $userPassword = "123456";
//              $userCountryCode = "90";
//              $userPhone = "54281969821";
//              $userDeviceToken = "1234";
//              $userDeviceId = "B99BD5F1-D6A5-44D6-977E-8DA5C0FAF214";
//              $userDeviceType = "iphone";
//              $secQuestion = "testing";
//              $secAnswer = "test";
//              $deviceLanguage="en";
//             
            if ($userCountryCode['0'] != "+") {
                    $userCountryCode = "+".$userCountryCode;
            }
            $this->common->checkEmptyParameter1(array($userPassword, $userDeviceId, $userDeviceType, $userDeviceToken)); // To check for compulsary parameters
            if (!$userNickName) {
                $userNickName = $userFullName;
            }
            $userName = !empty($userEmail) ? $userEmail : $userCountryCode . $userPhone;
            $registrationType = !empty($userEmail) ? 1 : 2;
            $errorRecord = !empty($userEmail) ? "This email is already exist" : "This phone is already exist";
            $checkEmail = !empty($userEmail) ? $this->user->checkRecords('userEmail', $userName) : $this->user->checkRecords('phoneWithCode', $userName);
            if ($checkEmail != "") {
                $this->common->displayMessage($errorRecord, "1", array(), "5");
                exit;
            }
            $quickBloxId = $this->quickBloxSignupNew($userName, $userFullName);
            if ($quickBloxId) {
                $random = rand(1000, 9999);
                $userPhoneNumber = trim($userCountryCode) . trim($userPhone);
                $userPhoneNumber = str_replace(' ', '', $userPhoneNumber);
                $data_array = array(
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userNickName" => $userNickName,
                    "userFullName" => $userFullName,
                    "userName" => $userName,
                    "userEmail" => $userEmail,
                    "userPassword" => md5($userPassword),
                    "userPasscode" => $random,
                    "userCountryCode" => $userCountryCode,
                    "userPhone" => $userPhone,
                    "phoneWithCode" => $userPhoneNumber,
                    "is_phone_owner" => "0",
                    "userThemeColor" => "7",
                    "subsPlanStartDate" => date('Y-m-d H:i:s'),
                    "userModifieddate" => date('Y-m-d H:i:s'),
                    "userInsertDate" => date('Y-m-d H:i:s'),
                    "userStatus" => "0",
                    "quickBloxId" => $quickBloxId,
                    "lastSeenTime" => date("Y-m-d H:i:s")
                );

                $userRow = $userTable->createRow($data_array);
                $userRow->save();
                $userID = $userRow->userId;
                if ($registrationType == 1) {
                    $para = array('template' => 'registration.phtml', 'email' => $userEmail, 'name' => $userRow->userNickName, 'code' => $random, 'baseUrl' => $this->baseUrl, 'deviceLanguage' => $deviceLanguage, "subject" => 'Welcome To WA-app');
                    $this->sendCurlAsyncRequest("/auth/send-mail", $para);
                } else {
                    $message = "Your WA-app passcode is $random. Enter this code to verify your account.";
                    $this->common->sendSms_recommend($userPhoneNumber, $message);
                }
                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    'qbUserName' => $userRow->userName,
                    'userThemeColor' => $userRow->userThemeColor,
                    'userImage' => $userRow->userImage,
                    'userCountryCode' => $userRow->userCountryCode,
                    'userPhone' => $userRow->userPhone,
                    'userPasscode' => $random,
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
                );

                $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
                $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

                $userSettingData = array(
                    "userId" => $userRow->userId,
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userLoginTime" => date('Y-m-d H:i:s')
                );

                $userSettingRow = $userSettingTable->createRow($userSettingData);
                $userSettingRow->save();

                $accountSettingData = array(
                    'userId' => $userRow->userId,
                    'auto_friends' => "on",
                    'extra_privacy' => "off",
                    'creationDate' => date("Y-m-d H:i:s"),
                    'modifyDate' => date("Y-m-d H:i:s"),
                    'availableForDates' => "0",
                    'mediaType' => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
                );

                $accountSettingRow = $accountSettingTable->createRow($accountSettingData);
                $accountSettingRow->save();
                $phoneContactTable->updateWaUserIdWithPhone($userRow);
                $this->common->displayMessage('Signup successful', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('There is some problem please try later', "1", array(), "7");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "6");
        }
    }

    function sendMailAction() {
        $this->user->sendmail($this->getRequest()->getPost(), $this->getRequest()->getPost('template'), $this->getRequest()->getPost('email'), $this->getRequest()->getPost('subject'));
        die();
        
    }

    /**
     *  verifyaccount webservice will be call after signup if user will be enter correct passcode then 
     *  this service will activate to user
     *  required parameters: userSecurity, userId  
     */
    public function verifyaccountAction() {
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userDeviceId = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userDeviceType = $decoded['userDeviceType'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];

            $this->common->checkEmptyParameter1(array($userId));

            if ($userRow = $userTable->getRowById($userId)) {
                $phoneOwnerRow = $userTable->phoneOwnerRow($userRow->userPhone);

                $userRow->userStatus = "1";
                $userRow->userModifieddate = date('Y-m-d H:i:s');
                $userRow->is_phone_owner = ($phoneOwnerRow) ? "0" : "1";
                $userRow->save();

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
                );

                $trusteeTable->updateNewUserId($userId);
                $friendTable->updateNewUserId($userId);

                if ($userSettingRow = $userSettingTable->getRowByUserIdAndDeviceId($userId, $userDeviceId)) {
                    $userSettingRow->userDeviceToken = $userDeviceToken;
                    $userSettingRow->save();
                } else {

                    $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
                    $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

                    $data = array(
                        'userId' => $userRow->userId,
                        'userDeviceId' => $userDeviceId,
                        'userDeviceToken' => $userDeviceToken,
                        'userDeviceType' => $userDeviceType,
                        "userLoginTime" => date('Y-m-d H:i:s'),
                        'mediaType' => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
                    );

                    $userSettingRow = $userSettingTable->createRow($data);
                    $userSettingRow->save();
                }
                $this->common->displayMessage('Your account verified successfully', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('User account not exist', "1", array(), "3");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
            exit;
        }
    }

    public function verifyaccountnewAction() {
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userDeviceId = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userDeviceType = $decoded['userDeviceType'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];
            $this->common->checkEmptyParameter1(array($userId));
            if ($userRow = $userTable->getRowById($userId)) {
                $phoneOwnerRow = $userTable->phoneOwnerRow($userRow->userPhone);
                $userRow->userStatus = "1";
                $userRow->isEmailVerified = $decoded['isEmail'];
                $userRow->isPhoneVerified = $decoded['isPhone'];
                $userRow->userModifieddate = date('Y-m-d H:i:s');
                $userRow->is_phone_owner = ($phoneOwnerRow) ? "0" : "1";
                $userRow->save();

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    'qbUserName' => $userRow->userName,
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
                );

                $trusteeTable->updateNewUserId($userId);
                $friendTable->updateNewUserId($userId);

                if ($userSettingRow = $userSettingTable->getRowByUserIdAndDeviceId($userId, $userDeviceId)) {
                    $userSettingRow->userDeviceToken = $userDeviceToken;
                    $userSettingRow->save();
                } else {

                    $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
                    $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

                    $data = array(
                        'userId' => $userRow->userId,
                        'userDeviceId' => $userDeviceId,
                        'userDeviceToken' => $userDeviceToken,
                        'userDeviceType' => $userDeviceType,
                        "userLoginTime" => date('Y-m-d H:i:s'),
                        'mediaType' => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
                    );

                    $userSettingRow = $userSettingTable->createRow($data);
                    $userSettingRow->save();
                }
                $this->common->displayMessage('Your account verified successfully', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('User account not exist', "1", array(), "3");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
            exit;
        }
    }

    /**
     * resend verification code for activation of account 
     * required parameters: userSecurity, userId  
     */
    public function resendverificationcodeAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceType = $decoded['userDeviceType'];
        $userTable = new Application_Model_DbTable_Users();

        if (($userSecurity == $this->servicekey) || ($userDeviceType == "web")) {
            $this->common->checkEmptyParameter1(array($userId));

            $userRow = $userTable->getRowById($userId);
            $random = $userRow->userPasscode;
            $message = "Your WA-app passcode is $random. Enter this code to verify your device.";
            if ($userRow) {
                if ($userRow->userStatus == "0") {
                    $responseData = array(
                        'userId' => $userRow->userId,
                        'userPasscode' => $random
                    );

                    $emailParams = array(
                        'userName' => $userRow->userNickName,
                        'userPasscode' => $random,
                        'baseUrl' => $this->baseUrl
                    );

                    //$userRow->userPasscode = $random;
                    //$userRow->save();

                    $phoneOwnerRow = $userTable->phoneOwnerRow($userRow->userPhone);

                    if (!$phoneOwnerRow) {
                        $para = array(
                            'name' => $userRow->userNickName,
                            'code' => $random,
                            'baseUrl' => $this->baseUrl
                        );

                        $this->user->sendmail($para, 'registration.phtml', $userRow->userEmail, 'Welcome To WA-app');
                        $random = $userRow->userPasscode;
                        $message = "Your WA-app passcode is $random. Enter this code to verify your device.";
                        $this->common->sendSms_recommend($userRow->phoneWithCode, $message);
                    } else {
                        $para = array(
                            'name' => $phoneOwnerRow->userNickName,
                            'new_user' => $userRow->userNickName,
                            'code' => $random,
                            'baseUrl' => $this->baseUrl
                        );

                        $this->user->sendmail($para, 'registration.phtml', $userRow->userEmail, 'Welcome To WA-app');

                        $params = array(
                            'new_user' => $userRow->userNickName,
                            'phone_owner_name' => $phoneOwnerRow->userNickName,
                            'baseUrl' => $this->baseUrl
                        );

                        $this->user->sendmail($params, 'registration_new_user.phtml', $userRow->userEmail, 'Welcome To WA-app');
                    }

                    $this->common->sendSms_recommend($userRow->phoneWithCode, $message);

                    $this->common->displayMessage('Code resend successfully', "0", $responseData, "0");
                } else {
                    $this->common->displayMessage('Account is already activated', "1", array(), "2");
                }
            } else {
                $this->common->displayMessage('This user does not exist', "1", array(), "3");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "4");
            exit;
        }
    }

    public function resendverificationcodenewAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceType = $decoded['userDeviceType'];
        $isEmail = $decoded['isEmail'];
        $userTable = new Application_Model_DbTable_Users();
        if (($userSecurity == $this->servicekey) || ($userDeviceType == "web")) {
            $this->common->checkEmptyParameter1(array($userId));
            $userRow = $userTable->getRowById($userId);
            $random = $userRow->userPasscode;
            $responseData = array(
                        'userId' => $userRow->userId,
                        'userPasscode' => $random
                    );
            if ($userRow) {
                    if ($isEmail) {
                        $emailParams = array(
                            'userName' => $userRow->userNickName,
                            'userPasscode' => $random,
                            'baseUrl' => $this->baseUrl
                        );
                        $this->user->sendmail($emailParams, 'resendverificationcode.phtml', $userRow->userEmail, 'Welcome To WA-app');
                    }else{
                        $message = "Your WA-app passcode is $random. Enter this code to activate account";
                        $this->common->sendSms_recommend($userRow->phoneWithCode, $message);
                    }
                   $this->common->displayMessage('Code resend successfully', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('This user does not exist', "1", array(), "3");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "4");
            exit;
        }
    }

    /**
     * for login web service in which user will either use userName with password or phone number with password
     * depending on the login type (phone or userName) 
     */
    public function userloginAction() {

        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $sosTable = new Application_Model_DbTable_WaSos();
        $userSecurity = trim($decoded['userSecurity']);
        $sosSettingTable = new Application_Model_DbTable_WaSosSetting();  /// Created by Sarvesh 27/08/2015 /////////  
        if ($userSecurity == $this->servicekey) {
            $userEmail = trim($decoded['userEmail']);
            $userPassword = trim($decoded['userPassword']);
            $userPassword = md5($userPassword);

            $userDeviceType = trim($decoded['userDeviceType']);
            $userDeviceToken = trim($decoded['userDeviceToken']);
            $userDeviceId = $decoded['userDeviceId'];
            /* $userDeviceId = "341461C9-2B95-45AD-A309-21A8636B2A8C";
              $userDeviceToken = 1234;
              $userDeviceType = iphone;
              $userEmail = "devesh@gmail.com";
              $userPassword = 333;
              $userPassword = md5($userPassword);
              $userSecurity = afe2eb9b1de658a39e896591999e1b59; */

            $this->common->checkEmptyParameter1(array($userEmail, $userPassword, $userDeviceType, $userDeviceToken, $userDeviceId)); // To check for compulsary parameters

            $select = $userTable->select()
                    ->where('userEmail =?', $userEmail)
                    ->where('userPassword =?', $userPassword);

            if ($userRow = $userTable->fetchRow($select)) {
                $sosSettingRow = $sosSettingTable->getRowByUserId($userRow->userId);
                $sosRow = $sosTable->getActiveProfileByUserId($userRow->userId); /// Created by Sarvesh 27/08/2015 ///////// 

                if ($userRow->userStatus == "0") {
                    $responseData = array(
                        'userId' => $userRow->userId,
                        'userNickName' => $userRow->userNickName,
                        'userFullName' => $userRow->userFullName,
                        'userEmail' => $userRow->userEmail,
                        'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                        'userThemeColor' => $userRow->userThemeColor,
                        'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                        'userCountryCode' => $userRow->userCountryCode,
                        'userPhone' => $userRow->userPhone,
                        'secQuestion' => $userRow->secQuestion,
                        'secAnswer' => $userRow->secAnswer,
                        'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                        'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                        'userPasscode' => $userRow->userPasscode,
                        /// Created by Sarvesh 27/08/2015 ///////// 
                        'user_sos_setting' => array(
                            'is_volunteer' => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer : "0",
                            'is_sos_setup' => $sosSettingRow->is_sos_setup ? $sosSettingRow->is_sos_setup : "0",
                            'is_sos_active' => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active : "0",
                            'sos_id' => $sosRow->sos_id ? $sosRow->sos_id : "",
                            'pincode' => $sosRow->pincode ? $sosRow->pincode :"",
                            'is_media_broadcast' => $sosRow->is_media_broadcast ? $sosRow->is_media_broadcast : "",
                            'is_broadcast_video' => $sosRow->is_broadcast_video ? $sosRow->is_broadcast_video : "",
                            'is_gps' => $sosRow->is_gps ? $sosRow->is_gps : "",
                            'is_alarm' => $sosRow->is_alarm ? $sosRow->is_alarm : ""
                        )
                    );

                    $this->common->displayMessage("Please activate your account", "2", $responseData, "6");
                }

                if ($userRow->userStatus == 2) {
                    $this->common->displayMessage("Your account has been deleted. So please contact to admin", "1", array(), "3");
                }

                if ($userRow->userStatus == 3) {
                    $this->common->displayMessage("Your have cancelled your account. So please contact to admin", "1", array(), "4");
                }


                $userSettingRowset = $this->db->select()->from('usrSetting')
                        ->where('userId = ?', $userRow->userId)
                        ->where('userDeviceId !=?', $userDeviceId)
                        ->where('userDeviceToken !=?', $userDeviceToken)
                        ->query()
                        ->fetchAll();

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                    'userThemeColor' => $userRow->userThemeColor,
                    'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                    'userCountryCode' => $userRow->userCountryCode,
                    'userPhone' => $userRow->userPhone,
                    'subscriptionPlan' => $userRow->subscriptionPlan,
                    'trusteeExist' => count($trusteeTable->getTrusteesByUserId($userRow->userId)) ? "1" : "0",
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : "",
                    'secQuestion' => $userRow->secQuestion,
                    'secAnswer' => $userRow->secAnswer,
                    'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                    'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                    /// Created by Sarvesh 27/08/2015 ///////// 
                    'user_sos_setting' => array(
                        'is_volunteer' => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer : "0",
                        'is_sos_setup' => $sosSettingRow->is_sos_setup ? $sosSettingRow->is_sos_setup : "0",
                        'is_sos_active' => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active : "0",
                        'sos_id' => $sosRow->sos_id ? $sosRow->sos_id : "",
                        'pincode' =>$sosRow->pincode ? $sosRow->pincode :"",
                        'is_media_broadcast' => $sosRow->is_media_broadcast ? $sosRow->is_media_broadcast : "",
                        'is_broadcast_video' => $sosRow->is_broadcast_video ? $sosRow->is_broadcast_video : "",
                        'is_gps' => $sosRow->is_gps ? $sosRow->is_gps : "",
                        'is_alarm' => $sosRow->is_alarm ? $sosRow->is_alarm : ""
                    )
                );

                $userDeviceDetails = array(
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userModifieddate" => date('Y-m-d H:i:s')
                );

                $userTable->updateUserDeviceDetails($userRow->userId, $userDeviceDetails);


                $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
                $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

                $userSettingData = array(
                    "userId" => $userRow->userId,
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userLoginType" => "email",
                    "userLoginTime" => date('Y-m-d H:i:s'),
                    "mediaType" => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
                );

                $userSettingRow = $userSettingTable->createRow($userSettingData);
                $userSettingRow->save();

                if (count($userSettingRowset) > 0) {
                    $this->common->displayMessage("User Login with other devices", "3", $responseData, "7");
                } else {
                    $this->common->displayMessage("Login successfully", "0", $responseData, "0");
                }

                exit;
            } else {
                $this->common->displayMessage('Either mobile/username or password is incorrect', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "5");
            exit;
        }
    }

    public function userloginnewAction() {
        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $sosTable = new Application_Model_DbTable_WaSos();
        $userSecurity = trim($decoded['userSecurity']);
        $sosSettingTable = new Application_Model_DbTable_WaSosSetting();  /// Created by Sarvesh 27/08/2015 /////////  
        if ($userSecurity == $this->servicekey) {
            $userEmail = trim($decoded['userEmail']);
            $userPassword = trim($decoded['userPassword']);
            $userPassword = md5($userPassword);
            $userCountryCode = preg_replace('/\s+/', '', $decoded['userCountryCode']);
            $userPhone = $decoded['userPhone'];
            $userDeviceType = trim($decoded['userDeviceType']);
            $userDeviceToken = trim($decoded['userDeviceToken']);
            $userDeviceId = $decoded['userDeviceId'];
            $userRow = '';
            $activeStatus = 0;
            $this->common->checkEmptyParameter1(array($userPassword, $userDeviceType, $userDeviceToken, $userDeviceId)); // To check for compulsary parameters
            if (!empty($userEmail)) {
                $select = $userTable->select()
                        ->where('userEmail =?', $userEmail)
                        ->where('userPassword =?', $userPassword);
                $userRow = $userTable->fetchRow($select);
                $activeStatus = $userRow->isEmailVerified;
            } else {
                $phoneWithCode = trim($userCountryCode . $userPhone);
                $select = $userTable->select()
                        ->where('phoneWithCode =?', $phoneWithCode)
                        ->where('userPassword =?', $userPassword);
                $userRow = $userTable->fetchRow($select);

                $activeStatus = $userRow->isPhoneVerified;
            }

            if ($userRow) {
                $sosSettingRow = $sosSettingTable->getRowByUserId($userRow->userId);
                $sosRow = $sosTable->getActiveProfileByUserId($userRow->userId); /// Created by Sarvesh 27/08/2015 ///////// 
                if ($activeStatus == "0") {
                    $responseData = array(
                        'userId' => $userRow->userId,
                        'userNickName' => $userRow->userNickName,
                        'userFullName' => $userRow->userFullName,
                        'qbUserName' => $userRow->userName,
                        'userEmail' => $userRow->userEmail,
                        'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                        'userThemeColor' => $userRow->userThemeColor,
                        'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                        'userCountryCode' => $userRow->userCountryCode,
                        'userPhone' => $userRow->userPhone,
                        'secQuestion' => $userRow->secQuestion,
                        'secAnswer' => $userRow->secAnswer,
                        'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                        'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                        'userPasscode' => $userRow->userPasscode,
                        /// Created by Sarvesh 27/08/2015 ///////// 
                        'user_sos_setting' => array(
                            'is_volunteer' => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer : "0",
                            'is_sos_setup' => $sosSettingRow->is_sos_setup ? $sosSettingRow->is_sos_setup : "0",
                            'is_sos_active' => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active : "0",
                            'sos_id' => $sosRow->sos_id ? $sosRow->sos_id : "",
                            'pincode' =>$sosRow->pincode ? $sosRow->pincode :"",
                        )
                    );

                    $this->common->displayMessage("Please activate your account", "2", $responseData, "6");
                }

                if ($userRow->userStatus == 2) {
                    $this->common->displayMessage("Your account has been deleted. So please contact to admin", "1", array(), "3");
                }

                if ($userRow->userStatus == 3) {
                    $this->common->displayMessage("Your have cancelled your account. So please contact to admin", "1", array(), "4");
                }


                $userSettingRowset = $this->db->select()->from('usrSetting')
                        ->where('userId = ?', $userRow->userId)
                        ->where('userDeviceId !=?', $userDeviceId)
                        ->where('userDeviceToken !=?', $userDeviceToken)
                        ->query()
                        ->fetchAll();

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'qbUserName' => $userRow->userName,
                    'userEmail' => $userRow->userEmail,
                    'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                    'userThemeColor' => $userRow->userThemeColor,
                    'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                    'userCountryCode' => $userRow->userCountryCode,
                    'userPhone' => $userRow->userPhone,
                    'subscriptionPlan' => $userRow->subscriptionPlan,
                    'trusteeExist' => count($trusteeTable->getTrusteesByUserId($userRow->userId)) ? "1" : "0",
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : "",
                    'secQuestion' => $userRow->secQuestion,
                    'secAnswer' => $userRow->secAnswer,
                    'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                    'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                    /// Created by Sarvesh 27/08/2015 ///////// 
                    'user_sos_setting' => array(
                        'is_volunteer' => $sosSettingRow->is_volunteer ? $sosSettingRow->is_volunteer : "0",
                        'is_sos_setup' => $sosSettingRow->is_sos_setup ? $sosSettingRow->is_sos_setup : "0",
                        'is_sos_active' => $sosSettingRow->is_sos_active ? $sosSettingRow->is_sos_active : "0",
                        'sos_id' => $sosRow->sos_id ? $sosRow->sos_id : "",
                        'pincode' =>$sosRow->pincode ? $sosRow->pincode :"",
                    )
                );

                $userDeviceDetails = array(
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userModifieddate" => date('Y-m-d H:i:s')
                );

                $userTable->updateUserDeviceDetails($userRow->userId, $userDeviceDetails);


                $userSettingTable->deleteAllUserWithSameDevice($userDeviceId);
                $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

                $userSettingData = array(
                    "userId" => $userRow->userId,
                    "userDeviceId" => $userDeviceId,
                    "userDeviceToken" => $userDeviceToken,
                    "userDeviceType" => $userDeviceType,
                    "userLoginType" => "email",
                    "userLoginTime" => date('Y-m-d H:i:s'),
                    "mediaType" => Application_Model_DbTable_UserSetting::MEDIA_TYPE_APP
                );

                $userSettingRow = $userSettingTable->createRow($userSettingData);
                $userSettingRow->save();

                if (count($userSettingRowset) > 0) {
                    $this->common->displayMessage("User Login with other devices", "3", $responseData, "7");
                } else {
                    $this->common->displayMessage("Login successfully", "0", $responseData, "0");
                }

                exit;
            } else {
                $this->common->displayMessage('Either mobile/username or password is incorrect', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "5");
            exit;
        }
    }

    public function updateThemeColorAction() {
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userThemeColor = $decoded['userThemeColor'];

        if ($userSecurity == $this->servicekey) {

            if (isset($decoded['userId']) && isset($decoded['userDeviceId'])) {
                $this->common->isUserLogin($decoded['userId'], $decoded['userDeviceId']);
            }

            $this->common->checkEmptyParameter1(array($userId, $userThemeColor));
            $select = $userTable->select()
                    ->where('userId =?', $userId);

            if ($userRow = $userTable->fetchRow($select)) {
                $userRow->userThemeColor = $userThemeColor;
                $userRow->userModifieddate = date("Y-m-d H:i:s");
                $userRow->save();
                $this->common->displayMessage('User theme color changes successfully', "0", array(), "0");
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  logout web service is only for one device logout 
     */
    public function logoutAction() {
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userDeviceId = $decoded['userDeviceId'];
        $userId = $decoded['userId'];

        if ($userSecurity == $this->servicekey) {

            $this->common->checkEmptyParameter1(array($userId, $userDeviceId));

            if ($userSetttingRow = $userSettingTable->getRowByUserIdAndDeviceId($userId, $userDeviceId)) {
                $userSetttingRow->delete();
            }

            if ($isUserOnline = $userSettingTable->isUserOnline($userId)) {
                $this->common->displayMessage('user login with other device', "0", array(), "1");
            }

            if ($userRow = $userTable->getRowById($userId)) {
                $userRow->lastSeenTime = date("Y-m-d H:i:s");
                $userRow->save();
            }

            $this->common->displayMessage('successfully logout', "0", array(), "0");
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
            exit;
        }
    }
     
    public function logoutnewAction() {
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userDeviceId = $decoded['userDeviceId'];
        $userId = $decoded['userId'];

        if ($userSecurity == $this->servicekey) {

            $this->common->checkEmptyParameter1(array($userId, $userDeviceId));
            $userSettingTable->delete("userId='{$userId}'");
            if ($userRow = $userTable->getRowById($userId)) {
                $userRow->lastSeenTime = date("Y-m-d H:i:s");
                $userRow->save();
            }

            $this->common->displayMessage('successfully logout', "0", array(), "0");
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
            exit;
        }
    }
    /**
     * otherdevicelogout web service is for logout other users with same user device
     *  this service will be call at login time
     */
    public function otherdevicelogoutAction() {
        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];

        if ($userSecurity == $this->servicekey) {

            $this->common->checkEmptyParameter1(array($userId, $userDeviceId));

            $currentDeviceRow = $this->db->select()
                    ->from('usrSetting')
                    ->where('userId =?', $userId)
                    ->where('userDeviceId =?', $userDeviceId)
                    ->query()
                    ->fetch();

            $this->db->delete('usrSetting', "userId =" . $userId);

            $userSettingData = array(
                "userId" => $userId,
                "userDeviceId" => $userDeviceId,
                "userDeviceToken" => $currentDeviceRow->userDeviceToken,
                "userDeviceType" => $currentDeviceRow->userDeviceType,
                "userLoginType" => $currentDeviceRow->userLoginType,
                "userLoginTime" => $currentDeviceRow->userLoginTime
            );

            $this->db->insert('usrSetting', $userSettingData);

            $this->common->displayMessage('successfully logout', "0", array(), "0");
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
        }
    }

    /**
     * public function logout with all devices
     */
    public function logoutWithAllDeviceAction() {
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];

        if ($userSecurity == $this->servicekey) {

            $this->common->checkEmptyParameter1(array($userId));

            if ($userRow = $userTable->getRowById($userId)) {
                $userSettingTable->logoutWithAllDevice($userId);
                $userRow->lastSeenTime = date("Y-m-d H:i:s");
                $userRow->save();
                $this->common->displayMessage("logout successfully", "0", array(), "0");
            } else {
                $this->common->displayMessage("user account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "2");
        }
    }

    public function checkuniqueAction() {
        $decoded = $this->common->Decoded();
        $user_security = $decoded['userSecurity'];
        if ($user_security == $this->servicekey) {
            $userField = $decoded['userField']; //userName,userEmail,userFbId,userPhone
            $userValue = $decoded['userValue'];
            if (!empty($usrFirstName) && !empty($usrEmail)) {
                if ($userField == 'userName')
                    $msg = 'username';elseif ($userField == 'userEmail')
                    $msg = 'email';elseif ($userField == 'userFbId')
                    $msg = 'facebook account';
                else
                    $msg = 'phone';
                $checkunique = $this->user->checkRecords($userField, $userValue);
                if ($checkunique) {
                    $this->common->specificMessage('This ' . $msg . ' is already exist', 0, $userField, $userValue);
                } else {
                    $this->common->specificMessage('This ' . $msg . ' is available', 0, $userField, $userValue);
                }
            } else {
                $this->common->parameterMissing();
            }
        } else {
            $this->common->specificMessage('You could not access this web-service', 1, false);
        }
    }

    /**
     *  first request for forgot password and this service will return user's secret question and secret answer 
     *  on the basis of user email address 
     */
    public function forgotpasswordAction() {

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userEmail = trim($decoded['userEmail']);

        $userPhone = trim($decoded['userPhone']);
        $requestType = "email";

        if ($userSecurity == $this->servicekey) {

            if (trim($userEmail)) {
                $userRow = $this->db->select()->from("users")
                        ->where("userEmail =?", $userEmail)
                        ->query()
                        ->fetch();
            } else {
                $this->common->checkEmptyParameter1(array($userPhone));

                $userRow = $this->db->select()->from("users")
                        ->where("userPhone =?", $userPhone)
                        ->query()
                        ->fetch();
                $requestType = "phone";
            }

            if ($userRow) {
                $responseData = array(
                    'userId' => $userRow->userId,
                    'secQuestion' => $userRow->secQuestion,
                    'secAnswer' => $userRow->secAnswer
                );

                $this->common->displayMessage('Secret question answer', "0", $responseData, "0");
            } else {
                if ($requestType == "email") {
                    $this->common->displayMessage('No user exist with this email', "1", array(), "2");
                } else {
                    $this->common->displayMessage('No user exist with this phone number', "1", array(), "3");
                }
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "4");
        }
    }

    public function forgotpasswordnewAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userEmail = trim($decoded['userEmail']);
        $userCountryCode = trim($decoded['userCountryCode']);
        if ($userCountryCode['0'] != "+") {
            $userCountryCode = "+".$userCountryCode;
        }
        $userPhone = trim($decoded['userPhone']);
        if ($userSecurity == $this->servicekey) {
            $userName = !empty($userEmail) ? trim($userEmail) : trim($userCountryCode . $userPhone);
            $registrationType = !empty($userEmail) ? 1 : 2;
            $userRow = !empty($userEmail) ?$this->db->select()->from("users")->where("userEmail =?", $userName)->query()->fetch():$this->db->select()->from("users")->where("phoneWithCode =?", $userName)->query()->fetch();
            if ($userRow) {
                $userPasscode = rand(1000, 9999);
                $this->db->update("users", array("userPasscode" => $userPasscode), "userId = '" . $userRow->userId . "'");
                $responseData = array(
                    'userId' => $userRow->userId,
                    'userPasscode' => $userPasscode
                );
                if ($registrationType == 1) {
                    $para = array(
                        'userName' => $userRow->userNickName,
                        'userPasscode' => $userPasscode,
                        'baseUrl' => $this->baseUrl,
                        'template' => 'forgotpassword.phtml',
                        'email' => $userRow->userEmail,
                        'subject' => 'Forgot Password',
                    );
                    $this->sendCurlAsyncRequest("/auth/send-mail", $para);
                } else {
                    $message = "Your WA-app passcode is $userPasscode. Enter this code to reset your password";
                    $this->common->sendSms_recommend($userName, $message);
                }
                $this->common->displayMessage("Passcode", "0", $responseData, 0);
                die();
            } else {
                $this->common->displayMessage('No user exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "4");
        }
    }

    /**
     * 2nd request for forgot password this service will provide "passcode" for setting new password 
     *  and this request will come when user will give correct answer of his secret question
     */
    public function requestpasscodeAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];

            $this->common->checkEmptyParameter1(array($userId));

            $userRow = $this->db->select()->from('users')
                    ->where('userId =?', $userId)
                    ->query()
                    ->fetch();

            if ($userRow) {

                $userPasscode = rand(1000, 9999);

                $this->db->update("users", array("userPasscode" => $userPasscode), "userId = '" . $userId . "'");

                $emailParams = array(
                    'userName' => $userRow->userNickName,
                    'userPasscode' => $userPasscode,
                    'baseUrl' => $this->baseUrl
                );

                $this->user->sendmail($emailParams, 'forgotpassword.phtml', $userRow->userEmail, 'Welcome To WA');

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userPasscode' => $userPasscode
                );

                /**
                 * code for sending sms on phone
                 */
                $message = "Your WA-app passcode is $userPasscode. Enter this code to verify your device.";
                $userPhoneNumber = "+" . trim($userRow->userCountryCode) . trim($userRow->userPhone);

                //           $this->common->sendSms_recommend($userPhoneNumber,$message);

                $this->common->displayMessage('Request Passcode', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  webservice for setting new password  
     *  parameters : userSecurity, userId, userPassword
     */
    public function resetpasswordAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $trusteeTable = new Application_Model_DbTable_Trustee();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userPassword = $decoded['userPassword'];

        $userDeviceId = $decoded['userDeviceId'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $userDeviceType = $decoded['userDeviceType'];


        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userId, $userPassword));

            if ($userRow = $userTable->getRowById($userId)) {

                $userRow->userPassword = md5($userPassword);

                if ($userRow->userStatus == "0") {
                    $userRow->userStatus = "1";
                }

                $userRow->save();

                $responseData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                    'userEmail' => $userRow->userEmail,
                    'qbUserName' => $userRow->userName,
                    'userThemeColor' => $userRow->userThemeColor,
                    'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                    'userCountryCode' => $userRow->userCountryCode,
                    'userPhone' => $userRow->userPhone,
                    'subscriptionPlan' => $userRow->subscriptionPlan,
                    'trusteeExist' => count($trusteeTable->getTrusteesByUserId($userRow->userId)) ? "1" : "0",
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
                );

                if ($userSettingRow = $userSettingTable->getRowByUserIdAndDeviceId($userRow->userId, $userDeviceId)) {
                     $userSettingRow->userDeviceToken = $userDeviceToken;
                    $userSettingRow->save();
                } else {
                    $data = array(
                        'userId' => $userRow->userId,
                        'userDeviceId' => $userDeviceId,
                        'userDeviceToken' => $userDeviceToken,
                        'userDeviceType' => $userDeviceType,
                        "userLoginTime" => date('Y-m-d H:i:s')
                    );

                    $userSettingRow = $userSettingTable->createRow($data);
                    $userSettingRow->save();
                }

                $this->common->displayMessage('Your password change successfully', "0", $responseData, "0");
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  webservice for setting change password  
     *  parameters : userSecurity, userId, userOldPassword and userNewPassword
     */
    public function changepasswordAction() {
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userOldPassword = $decoded['userOldPassword'];
        $userNewPassword = $decoded['userNewPassword'];

        $userTable = new Application_Model_DbTable_Users();

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userId, $userOldPassword, $userNewPassword));

            $select = $userTable->select()
                    ->where('userId =?', $userId);

            $userRow = $userTable->fetchRow($select);


            if ($userRow) {
                if ($userRow->userPassword == md5($userOldPassword)) {
                    $userRow->userPassword = md5($userNewPassword);
                    $userRow->save();

                    $this->common->displayMessage('Your password change successfully', "0", array(), "0");
                } else {
                    $this->common->displayMessage('Old password is not correct', "1", array(), "4");
                }
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  webservice for updating user profile
     *  parameters : userSecurity, userId
     */
    public function userdetailsAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];

            if (isset($decoded['userId']) && isset($decoded['userDeviceId'])) {
                $this->common->isUserLogin($decoded['userId'], $decoded['userDeviceId']);
            }

            $this->common->checkEmptyParameter(array($userId));

            $select = $userTable->select()
                    ->where('userId =?', $userId);

            if ($userRow = $userTable->fetchRow($select)) {

                if (isset($decoded['userModifieddate']) && $decoded['userModifieddate'] != "") {

                    if ($userRow->userModifieddate == trim($decoded['userModifieddate'])) {

                        $this->common->displayMessage('no change', "0", array(), "1");
                    }
                }

                $userData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    'userLangauge' => ($userRow->userLangauge) ? $userRow->userLangauge : "",
                    'userThemeColor' => ($userRow->userThemeColor) ? $userRow->userThemeColor : "",
                    'userPhone' => ($userRow->userPhone) ? $userRow->userPhone : "",
                    'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                    'userCountryCode' => ($userRow->userCountryCode) ? $userRow->userCountryCode : "",
                    'userFbId' => ($userRow->userFbId) ? $userRow->userFbId : "",
                    'userTwitterId' => ($userRow->userTwitterId) ? $userRow->userTwitterId : "",
                    'userAge' => ($userRow->userAge) ? $userRow->userAge : "",
                    'secQuestion' => ($userRow->secQuestion) ? $userRow->secQuestion : "",
                    'secAnswer' => ($userRow->secAnswer) ? $userRow->secAnswer : "",
                    'subscriptionPlan' => ($userRow->subscriptionPlan) ? $userRow->subscriptionPlan : "",
                    'isOnline' => $userSettingTable->isUserOnline($userRow->userId),
                    'userModifieddate' => ($userRow->userModifieddate) ? $userRow->userModifieddate : "",
                    'lastSeenTime' => ($userRow->lastSeenTime) ? $userRow->lastSeenTime : "",
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : "",
                    'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                    'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                    'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                    'userCoverImage' => ($userRow->userCoverImage) ? $this->makeUrl("/" . $userRow->userCoverImage) : ""
                );

                $this->common->displayMessage('User Details', "0", $userData, "0");
            } else {
                $this->common->displayMessage('This user does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }

        exit;
    }

    /**
     *  get user details by quickblox id
     *  parameters : userSecurity,userId, quickBloxId
     */
    public function userdetailsByQuickbloxIdAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        //    $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
        //    $quickBloxId = "1773184";

        if ($userSecurity == $this->servicekey) {
            $quickBloxId = $decoded['quickBloxId'];
            $this->common->checkEmptyParameter(array($quickBloxId));

            $select = $userTable->select()
                    ->where('quickBloxId =?', $quickBloxId);

            if ($userRow = $userTable->fetchRow($select)) {

                $userData = array(
                    'userId' => $userRow->userId,
                    'userNickName' => $userRow->userNickName,
                    'userFullName' => $userRow->userFullName,
                    'userEmail' => $userRow->userEmail,
                    'userLangauge' => ($userRow->userLangauge) ? $userRow->userLangauge : "",
                    'userThemeColor' => ($userRow->userThemeColor) ? $userRow->userThemeColor : "",
                    'userPhone' => ($userRow->userPhone) ? $userRow->userPhone : "",
                    'userDob' => ($userRow->userDob) ? $userRow->userDob : "",
                    'userCountryCode' => ($userRow->userCountryCode) ? $userRow->userCountryCode : "",
                    'userFbId' => ($userRow->userFbId) ? $userRow->userFbId : "",
                    'userTwitterId' => ($userRow->userTwitterId) ? $userRow->userTwitterId : "",
                    'userAge' => ($userRow->userAge) ? $userRow->userAge : "",
                    'secQuestion' => ($userRow->secQuestion) ? $userRow->secQuestion : "",
                    'secAnswer' => ($userRow->secAnswer) ? $userRow->secAnswer : "",
                    'subscriptionPlan' => ($userRow->subscriptionPlan) ? $userRow->subscriptionPlan : "",
                    'isOnline' => $userSettingTable->isUserOnline($userRow->userId),
                    'userModifieddate' => ($userRow->userModifieddate) ? $userRow->userModifieddate : "",
                    'lastSeenTime' => ($userRow->lastSeenTime) ? $userRow->lastSeenTime : "",
                    'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : "",
                    'profileStatus' => ($userRow->profileStatus) ? $userRow->profileStatus : "",
                    'user_location' => ($userRow->user_location) ? $userRow->user_location : "",
                    'userImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                    'userCoverImage' => ($userRow->userCoverImage) ? $this->makeUrl($userRow->userCoverImage) : "",
                    'userStatus' => $userRow->userStatus
                );

                $this->common->displayMessage('User Details', "0", $userData, "0");
            } else {
                $this->common->displayMessage('This user does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }

        exit;
    }

    /**
     *  webservice for updating user profile
     *  parameters : userSecurity, userId
     */
    public function updateprofileAction() {
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $changeUserDetailsTable = new Application_Model_DbTable_ChangeUserDetails();

        /*    $decoded = array(
          'userSecurity'   => 'afe2eb9b1de658a39e896591999e1b59',
          'userId'         => '47',
          'userEmail'      => 'sarora1612@gmail.com'
          );
         */
        $userSecurity = $decoded['userSecurity'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];
            $userDeviceId = $decoded['userDeviceId'];

            //         $this->common->checkEmptyParameter(array($userId,$userDeviceId));
            //        $this->common->isUserLogin($userId,$userDeviceId);

            $select = $userTable->select()
                    ->where('userId =?', $userId);

            $userRow = $userTable->fetchRow($select);

            $userData = array();

            if ($userRow) {
                $is_change_in_quickblox = false;

                if (isset($decoded['userNickName']) && (trim($decoded['userNickName']) != "")) {
                    $is_change_in_quickblox = true;
                    $userData['userNickName'] = trim($decoded['userNickName']);
                }

                if (isset($decoded['userFullName']) && (trim($decoded['userFullName']) != "")) {
                    $userData['userFullName'] = trim($decoded['userFullName']);
                }

                if (isset($decoded['userEmail']) && (trim($decoded['userEmail']) != "")) {
                    if ($userTable->checkDuplicateRecordByField('userEmail', $decoded['userEmail'], $userRow->userId)) {
                        $this->common->displayMessage('User email is already exist', "1", array(), "4");
                        exit;
                    }

                    $is_change_in_email = false;

                    if ($userData['userEmail'] != trim($decoded['userEmail'])) {
                        $is_change_in_email = true;
                    }
                }



                if (isset($decoded['userLangauge']) && (trim($decoded['userLangauge']) != "")) {
                    $userData['userLangauge'] = trim($decoded['userLangauge']);
                }

                if (isset($decoded['userAge']) && (trim($decoded['userAge']) != "")) {
                    $userData['userAge'] = trim($decoded['userAge']);
                }

                if (isset($decoded['userThemeColor']) && (trim($decoded['userThemeColor']) != "")) {
                    $userData['userThemeColor'] = trim($decoded['userThemeColor']);
                }

                if (isset($decoded['userPhone']) && (trim($decoded['userPhone']) != "")) {


                    if ($userTable->checkDuplicateRecordByField('userPhone', $decoded['userPhone'], $userRow->userId)) {
                        $this->common->displayMessage('User phone is already exist', "1", array(), "5");
                        exit;
                    }

                    $userData['userCountryCode'] = trim($decoded['userCountryCode']) ? $decoded['userCountryCode'] : $userRow->userCountryCode;
                    $userData['userPhone'] = trim($decoded['userPhone']);
                    $userData['phoneWithcode'] = "+" . trim($decoded['userCountryCode']) . trim($decoded['userPhone']);
                }

                if (isset($decoded['secQuestion']) && (trim($decoded['secQuestion']) != "")) {
                    $userData['secQuestion'] = trim($decoded['secQuestion']);
                }

                if (isset($decoded['userDob']) && (trim($decoded['userDob']) != "")) {
                    $userData['userDob'] = trim($decoded['userDob']);
                }

                if (isset($decoded['secAnswer']) && (trim($decoded['secAnswer']) != "")) {

                    $userData['secAnswer'] = trim($decoded['secAnswer']);
                }

                if (isset($decoded['profileStatus']) && (trim($decoded['profileStatus']) != "")) {
                    $userData['profileStatus'] = trim($decoded['profileStatus']);
                }

                if (isset($decoded['user_location']) && (trim($decoded['user_location']) != "")) {
                    $userData['user_location'] = trim($decoded['user_location']);
                }

                if (isset($decoded['userFbId']) && (trim($decoded['userFbId']) != "")) {
                    $userData['userFbId'] = trim($decoded['userFbId']);
                }

                if (isset($decoded['userTwitterId']) && (trim($decoded['userTwitterId']) != "")) {
                    $userData['userTwitterId'] = trim($decoded['userTwitterId']);
                }

                if (count($userData) || $is_change_in_email) {
                    if (count($userData)) {
                        $userRow->setFromArray($userData);
                        $userRow->userModifieddate = date("Y-m-d H:i:s");
                        $userRow->save();
                    }

                    if ($is_change_in_email) {
                        // $changeUserDetailsTable
                        $confirm_code = $this->randomAlphaNum(12);
                        $data = array(
                            'user_id' => $userRow->userId,
                            'confirm_code' => $confirm_code,
                            'change_user_email' => trim($decoded['userEmail']),
                            'creation_date' => date("Y-m-d H:i:s"),
                            'is_link_expire' => "1"
                        );

                        $changeUserDetailRow = $changeUserDetailsTable->createRow($data);
                        $changeUserDetailRow->save();

                        // email send to user confrim for change email

                        $para = array(
                            'name' => $userRow->userNickName,
                            'user_email' => $userRow->userEmail,
                            'change_user_email' => trim($decoded['userEmail']),
                            'baseUrl' => $this->baseUrl,
                            'confirm_url' => $this->makeUrl('confirm/email?record_id=' . $changeUserDetailRow->id . '&confirm_code=' . $confirm_code)
                        );

                        $this->user->sendmail($para, 'Confirm_change_email.phtml', $userRow->userEmail, 'Change Email of WA-App');
                    }

                    $trusteeTable->updateNewUserId($userId);
                    $friendTable->updateNewUserId($userId);

                    if ($is_change_in_quickblox) {
                        $this->updateQuickBloxDetails($userRow);
                    }


                    if (isset($decoded['secQuestion']) || isset($decoded['secAnswer'])) {
                        $userSettingTable->logoutFromOtherDevices($userId, $userDeviceId);
                    }
                }

                $this->common->displayMessage('Profile updated successfully', "0");
            } else {
                $this->common->displayMessage('This user does not exist', "1", array(), "8");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "11");
        }
    }

    /**
     *  webservice for updating subscription plan
     *  parameters : userSecurity, userId, subscriptionPlan
     */
    public function updatesubscriptionplanAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];
            $userDeviceId = $decoded['userDeviceId'];
            $userDeviceToken = $decoded['userDeviceToken'];
            $userDeviceType = $decoded['userDeviceType'];

            $subsrciptionPlan = trim($decoded['subscriptionPlan']);
            if (!$subsrciptionPlan) {
                $subsrciptionPlan = '0';
            }
            $this->common->checkEmptyParameter(array($userId, $subsrciptionPlan));

            if (isset($decoded['userId']) && isset($decoded['userDeviceId'])) {
                $this->common->isUserLogin($decoded['userId'], $decoded['userDeviceId']);
            }

            $select = $userTable->select()
                    ->where('userId =?', $userId);

            $userRow = $userTable->fetchRow($select);

            if ($userRow) {
                $userRow->subscriptionPlan = $subsrciptionPlan;
                $userRow->subsPlanStartDate = date('Y-m-d H:i:s');
                $userRow->userModifieddate = date('Y-m-d H:i:s');

                $userRow->save();

                if ($userSettingRow = $userSettingTable->getRowByUserIdAndDeviceId($userId, $userDeviceId)) {

                    $userSettingRow->userDeviceToken = $userDeviceToken;
                    $userSettingRow->save();
                } else {
                    $data = array(
                        'userId' => $userRow->userId,
                        'userDeviceId' => $userDeviceId,
                        'userDeviceToken' => $userDeviceToken,
                        'userDeviceType' => $userDeviceType,
                        "userLoginTime" => date('Y-m-d H:i:s')
                    );

                    $userSettingRow = $userSettingTable->createRow($data);
                    $userSettingRow->save();
                }

                $this->common->displayMessage('Subscription plan updated successfully', "0");
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  webservice for deactivating user account
     *  parameters : userSecurity, userId
     */
    public function accountdeactivateAction() {
        $userTable = new Application_Model_DbTable_Users();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        if ($userSecurity == $this->servicekey) {
            $userId = $decoded['userId'];

            $this->common->checkEmptyParameter(array($userId));

            $select = $userTable->select()
                    ->where('userId=?', $userId);

            $userRow = $userTable->fetchRow($select);

            if ($userRow) {
                $userRow->userStatus = "2";
                $userRow->userModifieddate = date('Y-m-d H:i:s');
                $userRow->save();

                $this->deleteFromQuickBlox($userRow->quickBloxId);

                $this->common->displayMessage('Account deactivated successfully', "0");
            } else {
                $this->common->displayMessage('Account does not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function logoutOtherUserAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];

        $userDeviceToken = $decoded['userDeviceToken'];

        if ($userSecurity = $this->servicekey) {
            $this->common->checkEmptyParameter(array($userDeviceToken));
            $userSettingTable->deleteAllUserWithSameDeviceToken($userDeviceToken);

            $this->common->displayMessage('successfully logout from other device', "0", array(), "0");
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function testAction() {
        $userTable = new Application_Model_DbTable_Users();
        $result = $userTable->fetchAll();
        foreach ($result as $dd) {
            if ($dd->userEmail)
                $userTable->update(array("userName" => $dd->userEmail), "userEmail='{$dd->userEmail}'");
        }
    }

}
