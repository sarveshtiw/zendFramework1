<?php

class AjaxController extends My_Controller_Abstract {

    public function init() {
        parent::init();
        $this->user = new Application_Model_UsersMapper();
    }

    public function signupAction() {
        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('login'));

        $userTable = new Application_Model_DbTable_Users();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();

        if ($this->getRequest()->isPost()) {
            $userNickName = $this->getRequest()->getPost('userNickName', '');
            $userFullName = $this->getRequest()->getPost('userFullName', '');
            $userEmail = $this->getRequest()->getPost('userEmail', '');
            $userCountryCode = $this->getRequest()->getPost('userCountryCode', '');
            $userPhone = $this->getRequest()->getPost('userPhone', '');
            $userPassword = $this->getRequest()->getPost('userPassword', '');
            $secQuestion = $this->getRequest()->getPost('secQuestion', '');
            $secAnswer = $this->getRequest()->getPost('secAnswer', '');

            $errors = array();

            if (!$userNickName) {
                $errors['userNickName'] = $this->view->translate('index_txt_78');
            }
            if (!$userFullName) {
                $errors['userFullName'] = $this->view->translate('index_txt_78');
            }
            if (!$userEmail) {
                $errors['userEmail'] = $this->view->translate('index_txt_78');
            }
            if (!$userCountryCode) {
                $errors['userCountryCode'] = $this->view->translate('index_txt_78');
            }
            if (!$userPhone) {
                $errors['userPhone'] = $this->view->translate('index_txt_78');
            }
            if (!$userPassword) {
                $errors['userPassword'] = $this->view->translate('index_txt_78');
            }
            if (!$secQuestion) {
                $errors['secQuestion'] = $this->view->translate('index_txt_78');
            }
            if (!$secAnswer) {
                $errors['secAnswer'] = $this->view->translate('index_txt_78');
            }
            if (!isset($errors['userEmail'])) {
                if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors['userEmail'] = $this->view->translate('email_txt_28');
                }
            }

            if (!isset($errors['userEmail'])) {
                if ($userTable->checkDuplicateRecordByField('userEmail', $userEmail)) {
                    $errors['userEmail'] = $this->view->translate('email_txt_29');
                }
            }

            $phoneOwnerRow = $userTable->phoneOwnerRow($userPhone);

            $response = array();

            if (empty($errors)) {

                if ($userCountryCode['0'] == "+") {
                    $userPhoneNumber = trim($userCountryCode) . trim($userPhone);
                } else {
                    $userPhoneNumber = "+" . trim($userCountryCode) . trim($userPhone);
                }
                $random = rand(1000, 9999);
                $data = array(
                    'userName' => $userNickName,
                    'userNickName' => $userNickName,
                    'userFullName' => $userFullName,
                    'userEmail' => $userEmail,
                    'userCountryCode' => $userCountryCode,
                    'userPhone' => $userPhone,
                    "phoneWithCode" => $userPhoneNumber,
                    "is_phone_owner" => ($phoneOwnerRow) ? "0" : "1",
                    "userPasscode" => $random,
                    'userPassword' => md5($userPassword),
                    'secQuestion' => $secQuestion,
                    'secAnswer' => $secAnswer,
                    'userStatus' => "0",
                    'subscriptionPlan' => "0",
                    'userInsertDate' => date("Y-m-d H:i:s"),
                    'userModifieddate' => date("Y-m-d H:i:s"),
                    "subsPlanStartDate" => date('Y-m-d H:i:s'),
                    "lastSeenTime" => date("Y-m-d H:i:s"),
                    'userThemeColor' => "6",
                );
                $userRow = $userTable->createRow($data);
                $userRow->save();

                $quickBloxId = $this->quickBloxSignup($userRow);

                $userRow->quickBloxId = $quickBloxId;
                $userRow->save();

                $accountSettingData = array(
                    'userId' => $userRow->userId,
                    'auto_friends' => "on",
                    'extra_privacy' => "off",
                    'creationDate' => date("Y-m-d H:i:s"),
                    'modifyDate' => date("Y-m-d H:i:s"),
                    'availableForDates' => "0"
                );

                $accountSettingRow = $accountSettingTable->createRow($accountSettingData);
                $accountSettingRow->save();

                if ($userRow->is_phone_owner) {

                    $para = array(
                        'name' => $userRow->userNickName,
                        'code' => $random,
                        'baseUrl' => $this->baseUrl
                    );

                    $this->user->sendmail($para, 'registration.phtml', $userEmail, $this->view->translate('email_txt_25'));

                    $message = $this->view->translate('email_txt_16a') . " $random " . $this->view->translate('email_txt_17a');
                    $this->common->sendSms_recommend($userPhoneNumber, $message);
                } else {

                    $para = array(
                        'name' => $phoneOwnerRow->userNickName,
                        'new_user' => $userRow->userNickName,
                        'code' => $random,
                        'baseUrl' => $this->baseUrl
                    );

                    $this->user->sendmail($para, 'registration.phtml', $phoneOwnerRow->userEmail, $this->view->translate('email_txt_25'));

                    $params = array(
                        'new_user' => $userRow->userNickName,
                        'phone_owner_name' => $phoneOwnerRow->userNickName,
                        'baseUrl' => $this->baseUrl
                    );

                    $this->user->sendmail($params, 'registration_new_user.phtml', $userRow->userEmail, $this->view->translate('email_txt_25'));
                }


                $response['status'] = "success";
                $response['user_id'] = $userRow->userId;
            } else {
                $response['status'] = "fail";
                $response['errors'] = $errors;
            }

            echo json_encode($response);
            exit;
        }
    }

    public function verifyAccountAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();

        if ($this->getRequest()->isPost()) {
            $userPasscode = $this->getRequest()->getPost('userPasscode');
            $userId = $this->getRequest()->getPost('userId');
            $trustee_row_id = $this->getRequest()->getPost('trustee_request_id', '');
            $friend_row_id = $this->getRequest()->getPost('friend_request_id', '');

            $errors = array();
            $response = array();

            if (!$userPasscode) {
                $errors['userPasscode'] = $this->view->translate('index_txt_76');
                $response['status'] = "fail";
            } else {
                $select = $userTable->select()
                        ->where('userId =?', $userId)
                        ->where('userPasscode =?', $userPasscode);

                if ($userRow = $userTable->fetchRow($select)) {
                    if ($userRow->userStatus == "0") {

                        $userRow->userStatus = "1";
                        $userRow->userModifieddate = date('Y-m-d H:i:s');
                        $userRow->save();

                        $trusteeTable->updateNewUserId($userId);
                        $friendTable->updateNewUserId($userId);
                    }

                    if ($trustee_row_id) {

                        if (($trusteeRow = $trusteeTable->getRowById($trustee_row_id)) && ($trusteeRow->status == "0")) {

                            if (($trusteeUserRow = $userTable->getRowById($trusteeRow->userId)) && ($trusteeUserRow->userStatus == "1")) {

                                $trusteeRow->trusteeId = $userRow->userId;
                                $trusteeRow->status = "1";
                                $trusteeRow->acceptDate = date("Y-m-d H:i:s");
                                $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                                $trusteeRow->save();
                                $friendTable->addTrusteeAsFriend($trusteeRow);  // add friend 

                                $resultData = array(
                                    'userImage' => ($trusteeRow->trusteeImage) ? $trusteeRow->trusteeImage : "",
                                    'userId' => $userRow->userId,
                                    'userName' => ($trusteeRow->trusteeName) ? $trusteeRow->trusteeName : (($userRow && $userRow->userFullName) ? $userRow->userFullName : "")
                                );

                                $message = ucfirst($trusteeRow->trusteeName) . $this->view->translate('email_txt_30');

                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                                foreach ($userLoginDeviceRowset as $loginDeviceRow) {

                                    if ($loginDeviceRow->userDeviceType == "iphone") {
                                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'accept_trustee_request', 'userId' => $resultData['userId'], 'userName' => $resultData['userName'], 'userImage' => $resultData['userImage']);
                                    } else {
                                        $payload = array(
                                            'message' => $message,
                                            'type' => "accept_trustee_request",
                                            'result' => $resultData
                                        );
                                        $payload = json_encode($payload);
                                    }
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                                }
                            }
                        }
                    }

                    if ($friend_row_id && !$trustee_row_id) {
                        if (($friendRow = $friendTable->getRowById($friend_row_id)) && ($friendRow->status == "0")) {
                            $friendRow->friendId = $userRow->userId;
                            $friendRow->status = "1";
                            $friendRow->acceptDate = date("Y-m-d H:i:s");
                            $friendRow->modifyDate = date("Y-m-d H:i:s");
                            $friendRow->save();
                        }
                    }

                    $webLoginToken = strtotime(date('Y-m-d H:i:s')) . rand(1000, 10000);

                    $data = array(
                        'userId' => $userRow->userId,
                        'mediaType' => 'web',
                        'isOnline' => '1',
                        "userLoginTime" => date('Y-m-d H:i:s'),
                        "webLoginToken" => $webLoginToken,
                        "mediaType" => Application_Model_DbTable_UserSetting::MEDIA_TYPE_WEB
                    );

                    $userSettingRow = $userSettingTable->createRow($data);
                    $userSettingRow->save();


                    $response['status'] = "success";
                    $response['trusteeExist'] = count($trusteeTable->getTrusteesByUserId($userRow->userId)) ? "1" : "0";
                    $response['user_id'] = $userRow->userId;

                    $auth = Zend_Auth::getInstance();
                    $auth->setStorage(new Zend_Auth_Storage_Session('login'));
                    $userData = $userRow->toArray();

                    $userData = array_merge($userData, array(
                        'is_first_time_login' => '1',
                        'usrSettId' => $userSettingRow->usrSettId,
                        "webLoginToken" => $webLoginToken
                    ));

                    $auth->getStorage()->write($userData);

                    $trustee_namespace = new Zend_Session_Namespace('trustee_name');
                    $trustee_namespace->add_trustee_first_time = true;
                } else {
                    $errors['userPasscode'] = $this->view->translate('index_txt_76');
                    $response['status'] = "fail";
                }

                $response['errors'] = $errors;
            }

            echo json_encode($response);
            exit;
        }
    }

    /**
     * resend verification code for activation of account 
     * required parameters: userSecurity, userId  
     */
    public function resendverificationcodeAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userId = $this->getRequest()->getPost('user_id', '');

        $userRow = $userTable->getRowById($userId);

        if ($userRow) {
            if ($userRow->userStatus == "0") {
                $random = rand(1000, 9999);
                $responseData = array(
                    'userId' => $userRow->userId,
                    'userPasscode' => $random
                );

                $emailParams = array(
                    'userName' => $userRow->userNickName,
                    'userPasscode' => $random,
                    'baseUrl' => $this->baseUrl
                );

                $userRow->userPasscode = $random;
                $userRow->save();

                $this->user->sendmail($emailParams, 'resendverificationcode.phtml', $userRow->userEmail, $this->view->translate('email_txt_25'));


                /**
                 * code for sending sms on phone
                 */
                $message = $this->view->translate('email_txt_16') . " $random " . $this->view->translate('email_txt_17');
                $userPhoneNumber = "+" . trim($userRow->userCountryCode) . trim($userRow->userPhone);

                $this->common->sendSms_recommend($userPhoneNumber, $message);


                $this->common->displayMessage($this->view->translate('email_txt_31'), "0", $responseData, "0");
            } elseif ($userRow->userStatus == "2") {
                $this->common->displayMessage($this->view->translate('email_txt_32'), "1", array(), "2");
            } else {
                $this->common->displayMessage($this->view->translate('email_txt_33'), "1", array(), "3");
            }
        } else {
            $this->common->displayMessage($this->view->translate('email_txt_34'), "1", array(), "4");
        }
    }

    public function addTrusteeAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $friendTable = new Application_Model_DbTable_Friends();

        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        parse_str($_POST['trusteesData'], $trusteesDataset);

        $trusteesDataset = $trusteesDataset['trusteeData'];

        $userId = $this->loggedUserRow->userId;
        $trusteeEmails = array();
        $trusteePhones = array();
        $userRow = $userTable->getRowById($userId);

        foreach ($trusteesDataset as $trusteeData) {
            $trusteeEmails[] = $trusteeData['email'];
            $trusteePhones[] = $trusteeData['phone'];
        }


        if (empty($trusteeEmails) && empty($trusteePhones)) {
            $this->common->displayMessage($this->view->translate('add_txt_7'), "1", array(), "2");
        }


        foreach ($trusteesDataset as $trusteeData) {
            $trusteeUserRow = false;
            $trusteeName = trim($trusteeData['name']);
            $trusteeEmail = trim($trusteeData['email']);
            $trusteePhone = trim($trusteeData['phone']);

            $trusteePhoneWithCode = $trusteePhone ? $this->common->getPhoneWithCode($trusteePhone, $userRow->userCountryCode) : "";

            if ($trusteePhoneWithCode) {
                $trusteeUserRow = $userTable->checkDuplicateRecordByField('phoneWithCode', $trusteePhoneWithCode);
            }

            if ($trusteeEmail && !$trusteeUserRow) {
                $trusteeUserRow = $userTable->checkDuplicateRecordByField('userEmail', $trusteeEmail);
            }

            $data = array(
                'userId' => $userId,
                'trusteeId' => ($trusteeUserRow) ? $trusteeUserRow->userId : new Zend_Db_Expr('null'),
                'trusteeName' => $trusteeName,
                'trusteeEmail' => $trusteeEmail,
                'trusteePhone' => $trusteePhoneWithCode,
                'status' => "0",
                'creationDate' => date('Y-m-d H:i:s'),
                'modifyDate' => date('Y-m-d H:i:s')
            );

            $trusteeRow = false;
            $isTrusteeSave = false;

            if ($trusteeUserRow) {
                $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeUserRow->userId);
            }

            if (!$trusteeRow && $trusteeData['email']) {
                $trusteeRow = $trusteeTable->getRowByUserIdAndEmail($userId, $trusteeData['email']);
            }


            if (!$trusteeRow && ($trusteeData['phone'] != "")) {
                $trusteeRow = $trusteeTable->getRowByUserIdAndPhone($userId, $trusteeData['phone']);
            }

            if ($trusteeRow) {
                if (($trusteeRow->status == "0") || ($trusteeRow->status == "2")) {

                    $trusteeRow->status = "0";
                    $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                    $isTrusteeSave = true;
                    $trusteeRow->save();
                }
            } else {
                $trusteeRow = $trusteeTable->createRow($data);
                $trusteeRow->save();
                $isTrusteeSave = true;
            }


            /**
             * save friend request to trustee 
             */
            $friendRow = $friendTable->sendFriendRequest($trusteeRow);

            if ($isTrusteeSave) {
                if (!$trusteeUserRow) {
                    $randomNumber = $this->common->randomAlphaNum(10);
                    $trusteeRow->friend_request_id = $friendRow->id;
                    $trusteeRow->confirmCode = $randomNumber;
                    $trusteeRow->save();
                    $acceptRejectLink = $this->baseUrl . '?trustee_request_id=' . $trusteeRow->id . '&confirm_code=' . $randomNumber;

                    $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                    if ($trusteeEmail) {
                        $para = array(
                            'trustee_name' => $trusteeName,
                            'from_name' => $userRow->userNickName,
                            'accept_reject_link' => $acceptRejectLink,
                            'baseUrl' => $this->baseUrl
                        );

                        $this->user->sendmail($para, 'trustee_request.phtml', $trusteeRow->trusteeEmail, $this->view->translate('email_txt_27'));
                    }

                    if ($trusteePhoneWithCode) {
                        $message = $userRow->userFullName . $this->view->translate('email_txt_35') . $tinyUrl;
                        //           $this->common->sendSms_recommend($trusteePhoneWithCode,$message);
                    }
                } else {
                    // code for  wa user
                    $resultData = array(
                        'userImage' => $userRow->userImage,
                        'userId' => $userRow->userId,
                        'userName' => $userRow->userFullName
                    );

                    $message = $userRow->userFullName . $this->view->translate('email_txt_19');

                    $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                    foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                        if ($loginDeviceRow->userDeviceType && $loginDeviceRow->userDeviceToken) {
                            if ($loginDeviceRow->userDeviceType == "iphone") {
                                $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_trustee_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                            } else {
                                $payload = array(
                                    'message' => $message,
                                    'type' => "incoming_trustee_request",
                                    'result' => $resultData
                                );
                                $payload = json_encode($payload);
                            }
                            //          $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);
                        }
                    }
                }
            }
        }

        $this->common->displayMessage($this->view->translate('email_txt_36'), "0", array(), "0");
    }

    public function deleteTrusteeAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $waPointsTable = new Application_Model_DbTable_WaPoints();
        $trusteeRowId = $this->getRequest()->getPost('trustee_row_id', '');

        if ($this->getRequest()->isPost()) {
            if ($trusteeRow = $trusteeTable->getRowById($trusteeRowId)) {
                if ($trusteeRow->status == "2") {
                    echo "Trustee already deleted";
                } else {
                    $trusteeRow->status = "2";
                    $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                    $trusteeRow->save();
                    /* Insert in wa_points and update friends table point_id */
                    $waPointsTable->deleteWaPointRow($trusteeRow->userId, $friendId = 0, $trusteeRow->trusteeId, $isTrustee = 1);
                    /* wa_points end */

                    echo $this->view->translate('email_txt_37');
                }
            }
        }
        exit;
    }

    /**
     *  cancel incoming and outgoing trustee request
     */
    public function cancelTrusteeRequestAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $trusteeRowId = $this->getRequest()->getPost('trustee_row_id', '');

        if ($this->getRequest()->isPost()) {

            $reponse = array();
            $trusteeRow = $trusteeTable->getRowById($trusteeRowId);

            if ($trusteeRow->status == "0") {

                $trusteeRow->status = "2";
                $trusteeRow->save();

                $reponse['reponse_key'] = $this->view->translate('pendingtrustees_txt_4b');
                $reponse['message'] = $this->view->translate('email_txt_38');
            } else if ($trusteeRow->status == "1") {
                $reponse['reponse_key'] = "already_accepted";
                $reponse['message'] = $this->view->translate('email_txt_39');
            } else {
                $reponse['reponse_key'] = "already_canceled";
                $reponse['message'] = $this->view->translate('email_txt_40');
            }

            echo json_encode($reponse);
        }
        exit;
    }

    /**
     * accept incoming trustee request
     */
    public function acceptTrusteeRequestAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $trusteeRowId = $this->getRequest()->getPost('trustee_row_id', '');
    }

    /**
     *  forgot password for web service for web site
     */
    public function forgotPasswordAction() {
        $userTable = new Application_Model_DbTable_Users();

        $user_email_phn = $this->getRequest()->getPost('user_email_phn');

        if (trim($user_email_phn)) {
            $select = $userTable->select()
                    ->where('(userPhone =?', $user_email_phn)
                    ->orWhere("phoneWithCode =?", $user_email_phn)
                    ->orWhere("userEmail =?)", $user_email_phn);

            if ($userRow = $userTable->fetchRow($select)) {
                $responseData = array(
                    'userId' => $userRow->userId,
                    'secQuestion' => $userRow->secQuestion,
                    'secAnswer' => $userRow->secAnswer
                );

                $this->common->displayMessage($this->view->translate('email_txt_41'), "0", $responseData, "0");
            } else {
                $this->common->displayMessage($this->view->translate('email_txt_42'), "1", array(), "1");
            }
        } else {
            $this->common->displayMessage($this->view->translate('email_txt_42'), "1", array(), "2");
        }
        exit;
    }

    public function verifySecurityAnswerAction() {
        $userTable = new Application_Model_DbTable_Users();

        $userId = $this->getRequest()->getPost('forgot_pwd_user_id', '');
        $secAnswer = $this->getRequest()->getPost('secAnswer', '');

        if ($this->getRequest()->isPost()) {
            if ($userRow = $userTable->getRowById($userId)) {
                if ($userRow->secAnswer == $secAnswer) {

                    $userPasscode = rand(1000, 9999);

                    $userRow->userPasscode = $userPasscode;
                    $userRow->save();

                    $emailParams = array(
                        'userName' => $userRow->userFullName,
                        'userPasscode' => $userPasscode,
                        'baseUrl' => $this->baseUrl()
                    );

                    $this->user->sendmail($emailParams, 'forgotpassword.phtml', $userRow->userEmail, $this->view->translate('email_txt_25'));

                    $responseData = array(
                        'userId' => $userRow->userId,
                        'userPasscode' => $userPasscode
                    );

                    /**
                     * code for sending sms on phone
                     */
                    $message = $this->view->translate('email_txt_16') . " $userPasscode " . $this->view->translate('email_txt_17');
                    $userPhoneNumber = "+" . trim($userRow->userCountryCode) . trim($userRow->userPhone);

                    //           $this->common->sendSms_recommend($userPhoneNumber,$message);

                    $this->common->displayMessage($this->view->translate('email_txt_43'), "0", $responseData, "0");
                } else {
                    $this->common->displayMessage($this->view->translate('index_txt_80'), "1", array(), "1");
                }
            } else {
                $this->common->displayMessage($this->view->translate('index_txt_80'), "1", array(), "1");
            }
        }

        exit;
    }

    /**
     * forgot password verify account 
     */
    public function forgotPwdVerifyAccountAction() {
        $userTable = new Application_Model_DbTable_Users();

        $userId = $this->getRequest()->getPost('userId', '');
        $userPasscode = $this->getRequest()->getPost('userPasscode', '');

        if ($this->getRequest()->isPost()) {
            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {
                if ($userRow->userPasscode == $userPasscode) {
                    $this->common->displayMessage($this->view->translate('email_txt_44'), "0", array(), "0");
                } else {
                    $this->common->displayMessage($this->view->translate('index_txt_76'), "1", array(), "2");
                }
            } else {
                $this->common->displayMessage($this->view->translate('email_txt_45'), "1", array(), "1");
            }
        }
    }

    public function resetPasswordAction() {
        $userTable = new Application_Model_DbTable_Users();

        if ($this->getRequest()->isPost()) {
            $userId = $this->getRequest()->getPost('userId', '');
            $userPassword = trim($this->getRequest()->getPost('userPassword', ''));
            if ($userRow = $userTable->getRowById($userId)) {

                $response = array();
                $errors = array();

                if (!$userPassword) {
                    $response['status'] = "fail";
                    $errors['password'] = $this->view->translate('index_txt_78');
                }

                if (empty($errors)) {
                    $userRow->userPassword = md5($userPassword);
                    $userRow->save();
                    $response['status'] = "success";
                } else {
                    $response['errors'] = $errors;
                }

                echo json_encode($response);
                exit;
            }
        }
        exit;
    }

    /**
     * delete trustee at signup time
     */
    public function rejectTrusteeRequestAction() {
        $TrusteeTable = new Application_Model_DbTable_Trustee();
        $trustee_row_id = $this->getRequest()->getPost('trustee_request_id', '');

        if ($trustee_row_id) {
            if (($trusteeRow = $TrusteeTable->getRowById($trustee_row_id)) && ($trusteeRow->status == "0")) {
                $trusteeRow->status = "2";
                $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                $trusteeRow->save();
            }
        }
        exit;
    }

    /**
     * reject friend request 
     */
    public function rejectFriendRequestAction() {
        $friendTable = new Application_Model_DbTable_Friends();

        $friend_row_id = $this->getRequest()->getPost('friend_request_id', '');

        if ($friend_row_id) {
            if (($friendRow = $friendTable->getRowById($friend_row_id)) && ($friendRow->status == "0")) {
                $friendRow->status = "2";
                $friendRow->modifyDate = date("Y-m-d H:i:s");
                $friendRow->save();
            }
        }
        exit;
    }

    public function editFriendTrusteeDetailsAction() {
        $decoded = $this->common->Decoded();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();

        $userId = $this->loggedUserRow->userId;
        $other_user_id = $this->getRequest()->getPost('other_user_id', '');
        $edit_name = $this->getRequest()->getPost('edit_name', '');
        $hide_profile_img = $this->getRequest()->getPost('hide_profile_img', '0');

        if ($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $other_user_id)) {
            $friendRow->modifyDate = date("Y-m-d H:i:s");
            $friendRow->save();
        }

        if ($userRow = $userTable->getRowById($userId)) {

            $data = array(
                'userId' => $userId,
                'otherUserId' => $other_user_id,
                'name' => $edit_name,
                'hideProfile' => $hide_profile_img
            );

            $editDetailsRow = $editFriendTrusteeTable->editDetails($data);
            $this->common->displayMessage($this->view->translate('email_txt_47'), "0", array(), "0");
        } else {
            $this->common->displayMessage($this->view->translate('email_txt_46'), "1", array(), "2");
        }
    }

    public function deleteFriendAction() {
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $waPointsTable = new Application_Model_DbTable_WaPoints();

        $friendRowId = $this->getRequest()->getPost("friend_row_id", "");

        if ($this->loggedUserRow) {
            $userId = $this->loggedUserRow->userId;

            if (($friendRow = $friendTable->getRowById($friendRowId)) && ($friendRow->status != '2')) {
                $friendRow->status = "2";
                $friendRow->modifyDate = date('Y-m-d H:i:s');
                $friendRow->save();

                /* Delete in wa_points and update friends table point_id */
                $waPointsTable->deleteWaPointRow($friendRow->userId, $friendRow->friendId, $trusteeId = 0, $isTrustee = 0);
                /* wa_points end */

                $otherUserId = ($userId != $friendRow->userId) ? $friendRow->userId : $friendRow->friendId;

                if ($otherUserId) {
                    $trusteeTable->breakTrusteeRelation($userId, $otherUserId);
                }

                $editFriendTrusteeTable->deleteEditName($userId, $otherUserId);
            }
            $this->common->displayMessage($this->view->translate('email_txt_48'), "0", array(), "0");
        }
        $this->common->displayMessage($this->view->translate('email_txt_48'), "0", array(), "0");
    }

    /**
     *  Cancel friend outgoing request 
     */
    public function cancelFriendRequestAction() {
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();

        $friendRowId = $this->getRequest()->getPost('friend_row_id', '');

        if ($this->getRequest()->isPost()) {

            $reponse = array();
            $friendRow = $friendTable->getRowById($friendRowId);

            if ($friendRow->status == "0") {
                $friendRow->status = "2";
                $friendRow->modifyDate = date("Y-m-d H:i:s");
                $friendRow->save();
                $reponse['reponse_key'] = $this->view->translate('pendingtrustees_txt_4b');
                $reponse['message'] = $this->view->translate('email_txt_49');

                if ($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($this->loggedUserRow->userId, $friendRow->friendId)) {
                    if ($trusteeRow->status != "2") {
                        $trusteeRow->status = "2";
                        $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                        $trusteeRow->save();
                    }
                }
            } else if ($friendRow->status == "1") {
                $reponse['reponse_key'] = "already_accepted";
                $reponse['message'] = $this->view->translate('email_txt_39');
            } else {
                $reponse['reponse_key'] = "already_canceled";
                $reponse['message'] = $this->view->translate('email_txt_40');
            }

            echo json_encode($reponse);
        }
        exit;
    }

    public function addFriendAndTrusteeAction() {
        $decoded = $this->common->Decoded();

        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $userId = $this->loggedUserRow->userId;
        $friendsIdSet = $decoded['friendsIds'];
        $trusteesIdSet = $decoded['trusteesIds'];

        $userRow = $this->loggedUserRow;

        foreach ($trusteesIdSet as $trusteeId) {
            if (!in_array($trusteeId, $friendsIdSet)) {
                array_push($friendsIdSet, $trusteeId);
            }
        }

        /**
         *  code for adding new friends
         */
        foreach ($friendsIdSet as $friendId) {
            $data = array(
                'userId' => $userId,
                'friendId' => $friendId,
                'status' => '0',
                'creationDate' => date('Y-m-d H:i:s'),
                'modifyDate' => date('Y-m-d H:i:s')
            );

            $isFriendSave = false;

            if ($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)) {
                if ($friendRow->status == "0") {
                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                    $friendRow->save();
                }

                if ($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED) {
                    $friendRow->userId = $userId;
                    $friendRow->friendId = $friendId;
                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                    $friendRow->status = "0";
                    $friendRow->save();
                    $isFriendSave = true;
                }
            } else {
                $friendRow = $friendTable->createRow($data);
                $friendRow->save();
                $isFriendSave = true;
            }
            /**
             * push notification work
             */
            if (!in_array($friendId, $trusteesIdSet)) {
                if ($isFriendSave && ($friendUserRow = $userTable->getRowById($friendId))) {
                    $resultData = array(
                        'userImage' => $userRow->userImage,
                        'userId' => $userRow->userId,
                        'userName' => $userRow->userFullName
                    );

                    $message = $userRow->userFullName . $this->view->translate('email_txt_13');

                    $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendId);

                    foreach ($userLoginDeviceRowset as $loginDeviceRow) {

                        if ($loginDeviceRow->userDeviceType == "iphone") {
                            $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_friend_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                        } else {
                            $payload = array(
                                'message' => $message,
                                'type' => "incoming_friend_request",
                                'result' => $resultData
                            );
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }

            /**
             * push notification work end
             */
        }

        /**
         *  code for adding trustees
         */
        foreach ($trusteesIdSet as $trusteeId) {
            $data = array(
                'userId' => $userId,
                'trusteeId' => $trusteeId,
                'status' => '0',
                'creationDate' => date('Y-m-d H:i:s'),
                'modifyDate' => date('Y-m-d H:i:s')
            );

            $isTrusteeSave = false;

            if ($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeId)) {

                if ($trusteeRow->status == "0") {
                    $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                    $trusteeRow->save();
                }

                if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT) {
                    $trusteeRow->delete();
                    $trusteeRow = $trusteeTable->createRow($data);
                    $trusteeRow->save();
                    $isTrusteeSave = true;
                }
            } else {
                $trusteeRow = $trusteeTable->createRow($data);
                $trusteeRow->save();
                $isTrusteeSave = true;
            }

            /**
             * push notification work
             */
            if ($isTrusteeSave && ($trusteeUserRow = $userTable->getRowById($trusteeId))) {
                $resultData = array(
                    'userImage' => $userRow->userImage,
                    'userId' => $userRow->userId,
                    'userName' => $userRow->userFullName
                );

                $message = $userRow->userFullName . " wants to add you as a trustee";

                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeId);

                foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType == "iphone") {
                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_trustee_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                    } else {
                        $payload = array(
                            'message' => $message,
                            'type' => "incoming_trustee_request",
                            'result' => $resultData
                        );
                        $payload = json_encode($payload);
                    }
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                }
            }

            /**
             * push notification work end
             */
        }
        $this->common->displayMessage("Friends and Trustees successfully", "0", array(), "0");
    }

    /**
     * resend trustee request
     */
    public function resendTrusteeRequestAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $trusteeRowId = $this->getRequest()->getPost('trustee_row_id', '');
        $userId = $this->loggedUserRow->userId;

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {

            if ($trusteeRow = $trusteeTable->getRowById($trusteeRowId)) {

                if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE) {
                    $this->common->displayMessage($this->view->translate('add_txt_10'), "1", array(), "5");
                }

                if ($friendRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT) {
                    $this->common->displayMessage($this->view->translate('add_txt_11'), "1", array(), "6");
                }

                /**
                 *  if trustee id is available then trustee is wa user otherwise non wa user
                 */
                if ($trusteeRow->trusteeId) {
                    // code for wa user
                    if ($trusteeUserRow = $userTable->getRowById($trusteeRow->trusteeId)) {

                        $resultData = array(
                            'userImage' => $userRow->userImage,
                            'userId' => $userRow->userId,
                            'userName' => $userRow->userFullName
                        );

                        $message = $userRow->userFullName . $this->view->translate('email_txt_19');

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeUserRow->userId);

                        foreach ($userLoginDeviceRowset as $loginDeviceRow) {

                            if ($loginDeviceRow->userDeviceType == "iphone") {
                                $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_trustee_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                            } else {
                                $payload = array(
                                    'message' => $message,
                                    'type' => "incoming_trustee_request",
                                    'result' => $resultData
                                );
                                $payload = json_encode($payload);
                            }
                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                        }
                    } else {
                        $this->common->displayMessage($this->view->translate('email_txt_50'), "1", array(), "7");
                    }
                } else {

                    $acceptRejectLink = $this->baseUrl . '?trustee_request_id=' . $trusteeRow->id . '&confirm_code=' . $randomNumber;
                    $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                    if ($trusteeRow->trusteeEmail) {
                        $para = array(
                            'trustee_name' => $trusteeName,
                            'from_name' => $userRow->userNickName,
                            'accept_reject_link' => $acceptRejectLink,
                            'baseUrl' => $this->baseUrl
                        );

                        $this->user->sendmail($para, 'trustee_request.phtml', $trusteeRow->trusteeEmail, $this->view->translate('email_txt_27'));
                    }

                    if ($trusteeRow->trusteePhone) {

                        $message = $userRow->userFullName . $this->view->translate('email_txt_35') . $tinyUrl;
                        //         $this->common->sendSms_recommend($trusteeRow->trusteePhone,$message);
                    }
                }

                $trusteeRow->modifyDate = date("Y-m-d H:i:s");
                $trusteeRow->save();

                $this->common->displayMessage($this->view->translate('email_txt_51'), "0", array(), "5");
            } else {
                $this->common->displayMessage("Trustee row id is not available", "1", array(), "4");
            }
        } else {
            $this->common->displayMessage($this->view->translate('email_txt_46'), "1", array(), "2");
        }
    }

    public function addFriendAndTrusteeByEmailPhoneAction() {
        $decoded = $this->common->Decoded();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();

        $decoded = array_merge($decoded, array(
            'userId' => $this->loggedUserRow->userId
        ));

        $userId = $this->loggedUserRow->userId;

        $name = $decoded['name'];
        $email = $decoded['email'];
        $phone = $decoded['country_code'].$decoded['phone'];
        $isFriend = $decoded['isFriend'];
        $isTrustee = $decoded['isTrustee'];

        if ($email) {
            $this->common->checkEmptyParameter1(array($userId, $name, $email));
        } else {
            $this->common->checkEmptyParameter1(array($userId, $name, $phone));
        }

        if ($userRow = $userTable->getRowById($userId)) {

            $waUserRow = false;

            if ($phone) {
                if ($phone == $userRow->userPhone) {
                    $this->common->displayMessage($this->view->translate('email_txt_52'), "1", array(), "6");
                }
                $waUserRow = $userTable->checkDuplicateRecordByField('phoneWithCode', $phone);

                $waUserRowset = $userTable->getUsersByPhone($phone);

                if (count($waUserRowset) > 1) {
                    $this->addMultipleUsersAsFriendAndTrustee($userId, $waUserRowset, $decoded);
                    $this->common->displayMessage($this->view->translate('email_txt_53'), "0", array(), "0");
                }
            }

            if ($email && !$waUserRow) {
                if ($email == $userRow->userEmail) {
                    $this->common->displayMessage($this->view->translate('email_txt_52'), "1", array(), "6");
                }

                $waUserRow = $userTable->checkDuplicateRecordByField('userEmail', $email);
            }

            // start add trustee code

            if ($isTrustee == "1") {
                $isFriend = "1";
                $data = array(
                    'userId' => $userId,
                    'trusteeId' => ($waUserRow) ? $waUserRow->userId : new Zend_Db_Expr('null'),
                    'trusteeName' => $name,
                    'trusteeEmail' => $email,
                    'trusteePhone' => $phone,
                    'status' => "0",
                    'creationDate' => date('Y-m-d H:i:s'),
                    'modifyDate' => date('Y-m-d H:i:s')
                );


                $isTrusteeSave = false;
                $send_trustee_notification = false;

                if ($waUserRow) {

                    if ($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $waUserRow->userId)) {

                        if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_ACTIVE) {
                            $this->common->displayMessage($this->view->translate('email_txt_54'), "1", array(), "5");
                        }


                        if ($trusteeRow->status == "0") {
                            $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                            $trusteeRow->save();
                            $send_trustee_notification = true;
                        }

                        if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT) {
                            $trusteeRow->delete();

                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
                            $isTrusteeSave = true;
                            $send_trustee_notification = true;
                        }
                    } else {
                        $trusteeRow = $trusteeTable->createRow($data);
                        $trusteeRow->save();
                        $isTrusteeSave = true;
                        $send_trustee_notification = true;
                    }

                    if ($send_trustee_notification) {
                        $userNotificationTable->createTrusteeNotification($trusteeRow);
                    }

                    /**
                     * push notification work
                     */
                    if ($isTrusteeSave) {
                        $resultData = array(
                            'userImage' => $userRow->userImage,
                            'userId' => $userRow->userId,
                            'userName' => $userRow->userNickName
                        );

                        $message = $userRow->userNickName . $this->view->translate('email_txt_19');

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserRow->userId);

                        foreach ($userLoginDeviceRowset as $loginDeviceRow) {

                            if ($loginDeviceRow->userDeviceType == "iphone") {
                                $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_trustee_request', 'userId' => $userRow->userId, 'userName' => $userRow->userNickName, 'userImage' => $userRow->userImage);
                            } else {
                                $payload = array(
                                    'message' => $message,
                                    'type' => "incoming_trustee_request",
                                    'result' => $resultData
                                );
                                $payload = json_encode($payload);
                            }
                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                        }
                    }


                    /**
                     * push notification work end
                     */
                } else {

                    if ($email) {
                        $trusteeRow = $trusteeTable->getRowByUserIdAndEmail($userId, $email);
                    }

                    if ($phone && !$trusteeRow) {
                        $trusteeRow = $trusteeTable->getRowByUserIdAndPhone($userId, $phone);
                    }

                    if ($trusteeRow) {

                        if ($trusteeRow->status == "1") {
                            $this->common->displayMessage($this->view->translate('email_txt_54'), "1", array(), "5");
                        }

                        if ($trusteeRow->status == "0") {
                            $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                            $trusteeRow->save();
                        }

                        if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT) {
                            $trusteeRow->delete();

                            $trusteeRow = $trusteeTable->createRow($data);
                            $trusteeRow->save();
                        }

                        $isTrusteeSave = true;
                    } else {
                        $trusteeRow = $trusteeTable->createRow($data);
                        $trusteeRow->save();
                        $isTrusteeSave = true;
                    }

                    if ($isTrusteeSave) {

                        $randomNumber = $this->common->randomAlphaNum(10);

                        $trusteeRow->confirmCode = $randomNumber;
                        $trusteeRow->save();

                        $acceptRejectLink = $this->baseUrl . '?trustee_request_id=' . $trusteeRow->id . '&confirm_code=' . $randomNumber;
                        $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                        if ($email) {
                            $para = array(
                                'trustee_name' => $name,
                                'from_name' => $userRow->userNickName,
                                'accept_reject_link' => $acceptRejectLink,
                                'baseUrl' => $this->baseUrl
                            );

                            $this->user->sendmail($para, 'trustee_request.phtml', $email, $this->view->translate('email_txt_27'));
                        }

                        if ($phone && ($email == "")) {
                            $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                            $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);
                            $message = $this->view->translate('email_txt_25'). " ," . $this->view->translate('email_txt_26') ." ios: " . $android_app_link . " android: " . $ios_app_link;

                            $this->common->sendSms_recommend($phone, $message);
                        }
                    }
                }
            }

            // end add trustee code
            // start add friend code 
            if ($isFriend == "1") {
                $data = array(
                    'userId' => $userId,
                    'friendId' => ($waUserRow) ? $waUserRow->userId : new Zend_Db_Expr('null'),
                    'friendName' => $name,
                    'friendEmail' => $email,
                    'friendPhone' => $phone,
                    'friendImage' => "",
                    'status' => '0',
                    'creationDate' => date('Y-m-d H:i:s'),
                    'modifyDate' => date('Y-m-d H:i:s')
                );


                $isFriendSave = false;

                if ($waUserRow) {

                    $friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $waUserRow->userId);


                    if ($friendRow) {

                        if ($friendRow->status == "0") {
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->save();
                        }

                        if (($friendRow->status != "1") && ($friendRow->status != "0")) {
                            $friendRow->userId = $userId;
                            $friendRow->friendId = $waUserRow->userId;
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->status = "0";
                            $friendRow->save();
                            $isFriendSave = true;
                        }
                    } else {
                        $isFriendSave = true;
                        $friendRow = $friendTable->createRow($data);
                        $friendRow->save();
                    }

                    if ($isFriendSave && ($isTrustee != "1")) {
                        /**
                         * push notification send to wa user
                         */
                        $userNotificationTable->createFriendNotification($friendRow);

                        $resultData = array(
                            'userImage' => $userRow->userImage,
                            'userId' => $userRow->userId,
                            'userName' => $userRow->userNickName
                        );

                        $message = $userRow->userNickName . $this->view->translate('email_txt_13');

                        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($waUserRow->userId);

                        foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                            if ($loginDeviceRow->userDeviceType == "iphone") {
                                $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_friend_request', 'userId' => $userRow->userId, 'userName' => $userRow->userNickName, 'userImage' => $userRow->userImage);
                            } else {
                                $payload = array(
                                    'message' => $message,
                                    'type' => "incoming_friend_request",
                                    'result' => $resultData
                                );
                                $payload = json_encode($payload);
                            }

                            $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                        }

                        // push notification code end
                    }
                } else {
                    $friendRow = false;

                    if ($email) {
                        $friendRow = $friendTable->getRowByUserIdAndEmail($userId, $email);
                    }

                    if ($phone && !$friendRow) {
                        $friendRow = $friendTable->getRowByUserIdAndPhone($userId, $phone);
                    }

                    if ($friendRow) {
                        if ($friendRow->status == "0") {
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->save();
                        }

                        if (($friendRow->status != "1") && ($friendRow->status != "0")) {
                            $friendRow->modifyDate = date('Y-m-d H:i:s');
                            $friendRow->status = "0";
                            $friendRow->save();

                            $isFriendSave = true;
                        }
                    } else {
                        $friendRow = $friendTable->createRow($data);
                        $friendRow->save();
                        $isFriendSave = true;
                    }

                    if ($isFriendSave && ($isTrustee != "1")) {
                        $randomNumber = $this->common->randomAlphaNum(10);
                        $friendRow->confirmCode = $randomNumber;
                        $friendRow->save();

                        $acceptRejectLink = $this->baseUrl . '?friend_request_id=' . $friendRow->id . '&confirm_code=' . $randomNumber;

                        $tinyUrl = $this->common->get_tiny_url($acceptRejectLink);

                        if ($email) {
                            $para = array(
                                'friend_name' => $friendRow->friendName,
                                'baseUrl' => $this->baseUrl,
                                'accept_reject_link' => $acceptRejectLink,
                                'from_name' => $userRow->userNickName
                            );

                            $this->user->sendmail($para, 'friend_request.phtml', $friendRow->friendEmail, $this->view->translate('email_txt_55'));
                        }

                        if ($phone && ($email == "")) {
                            $android_app_link = $this->common->get_tiny_url($this->android_app_link);
                            $ios_app_link = $this->common->get_tiny_url($this->ios_app_link);

                            $message = $this->view->translate('email_txt_25'). " ," . $this->view->translate('email_txt_26') . " ios: " . $android_app_link . " android: " . $ios_app_link;
                            $this->common->sendSms_recommend($phone, $message);
                        }
                    }
                }

                if (($isFriend == "1") && ($isTrustee == "1") && !$waUserRow) {
                    $trusteeRow->friend_request_id = $friendRow->id;
                    $trusteeRow->save();
                }
            }

            // end add friend code
            $this->common->displayMessage($this->view->translate('email_txt_53'), "0", array(), "0");
        } else {
            $this->common->displayMessage($this->view->translate('email_txt_46'), "1", array(), "3");
        }
    }

    public function addFriendAndTrusteeWebAction() {
        $decoded = $this->common->Decoded();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();


        $userId = $this->loggedUserRow->userId;
        $friendsIdSet = $decoded['friendsIds'];
        $trusteesIdSet = $decoded['trusteesIds'];


        $userRow = $this->loggedUserRow;

        foreach ($trusteesIdSet as $trusteeId) {
            if (!in_array($trusteeId, $friendsIdSet)) {
                array_push($friendsIdSet, $trusteeId);
            }
        }

        /**
         *  code for adding new friends
         */
        foreach ($friendsIdSet as $friendId) {
            $data = array(
                'userId' => $userId,
                'friendId' => $friendId,
                'status' => '0',
                'creationDate' => date('Y-m-d H:i:s'),
                'modifyDate' => date('Y-m-d H:i:s')
            );

            $isFriendSave = false;

            if ($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)) {
                if ($friendRow->status == "0") {
                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                    $friendRow->save();
                }

                if ($friendRow->status == Application_Model_DbTable_Friends::STATUS_DELETED) {
                    $friendRow->userId = $userId;
                    $friendRow->friendId = $friendId;
                    $friendRow->modifyDate = date('Y-m-d H:i:s');
                    $friendRow->status = "0";
                    $friendRow->save();
                    $isFriendSave = true;
                }
            } else {
                $friendRow = $friendTable->createRow($data);
                $friendRow->save();
                $isFriendSave = true;
            }


            /**
             * push notification work
             */
            if (!in_array($friendId, $trusteesIdSet)) {


                if ($isFriendSave && ($friendUserRow = $userTable->getRowById($friendId))) {

                    //friend notification (friend request sent)
                    $userNotificationTable->createFriendNotification($friendRow);

                    $resultData = array(
                        'userImage' => $userRow->userImage,
                        'userId' => $userRow->userId,
                        'userName' => $userRow->userFullName
                    );

                    $message = $userRow->userFullName . $this->view->translate('email_txt_13');

                    $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($friendId);

                    foreach ($userLoginDeviceRowset as $loginDeviceRow) {

                        if ($loginDeviceRow->userDeviceType == "iphone") {
                            $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_friend_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                        } else {
                            $payload = array(
                                'message' => $message,
                                'type' => "incoming_friend_request",
                                'result' => $resultData
                            );
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }

            /**
             * push notification work end
             */
        }

        /**
         *  code for adding trustees
         */
        foreach ($trusteesIdSet as $trusteeId) {
            $data = array(
                'userId' => $userId,
                'trusteeId' => $trusteeId,
                'status' => '0',
                'creationDate' => date('Y-m-d H:i:s'),
                'modifyDate' => date('Y-m-d H:i:s')
            );

            $isTrusteeSave = false;

            if ($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $trusteeId)) {

                if ($trusteeRow->status == "0") {
                    $trusteeRow->modifyDate = date('Y-m-d H:i:s');
                    $trusteeRow->save();
                }

                if ($trusteeRow->status == Application_Model_DbTable_Trustee::STATUS_REJECT) {
                    $trusteeRow->delete();
                    $trusteeRow = $trusteeTable->createRow($data);
                    $trusteeRow->save();
                    $isTrusteeSave = true;
                }
            } else {
                $trusteeRow = $trusteeTable->createRow($data);
                $trusteeRow->save();
                $isTrusteeSave = true;
            }

            /**
             * push notification work
             */
            if ($isTrusteeSave && ($trusteeUserRow = $userTable->getRowById($trusteeId))) {

                //Trustee request sent
                $userNotificationTable->createTrusteeNotification($trusteeRow);



                $resultData = array(
                    'userImage' => $userRow->userImage,
                    'userId' => $userRow->userId,
                    'userName' => $userRow->userFullName
                );

                $message = $userRow->userFullName . $this->view->translate('email_txt_19');

                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($trusteeId);

                foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType == "iphone") {
                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'incoming_trustee_request', 'userId' => $userRow->userId, 'userName' => $userRow->userFullName, 'userImage' => $userRow->userImage);
                    } else {
                        $payload = array(
                            'message' => $message,
                            'type' => "incoming_trustee_request",
                            'result' => $resultData
                        );
                        $payload = json_encode($payload);
                    }
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                }
            }

            /**
             * push notification work end
             */
        }
        $this->common->displayMessage("Friends and Trustees successfully", "0", array(), "0");
    }

    public function changeOnlineStatusAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $onlineStatus = $this->getRequest()->getPost('user_status');
        $quick_blox_id = $this->getRequest()->getPost('id');
        $userRow = $userTable->getByQuickBlox($quick_blox_id);
        if ($userRow) {
            try{
            $userSettingRow = $userSettingTable->getRowByUserId($userRow->userId); 
            if ($userSettingRow) {
                $userSettingRow->isOnline = (int)$onlineStatus;
                $userSettingRow->save();
                ($onlineStatus != "1")?$userRow->lastSeenTime = date("Y-m-d H:i:s"):'';
                $userRow->userModifieddate = date("Y-m-d H:i:s");
                $userRow->save();
            }
            }
            catch (Exception $e){
                print_r($e->getMessage());
            }
        }
        die();
    }
    /*
     * used for update group join status on when user join the group on qb
     * param    :   id(groupId)
     */
    public function changeStatusAction() {
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $userId = $this->loggedUserRow->userId;
        $groupId = $this->getRequest()->getPost('id',0);
        $groupMemberTable->update(array("join_status"=>1),"memberId='{$userId}' AND groupId='{$groupId}'");
        die();
    }
    
    /*
     * used for update group join status on when user join the group on qb
     * param    :   id(groupId)
     */
    public function changeMessageStatusAction() {
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
        $userId = $this->loggedUserRow->userId;
        $messageId = $this->getRequest()->getPost('message_id',0);
        $isRead = $this->getRequest()->getPost('message_status',0);
        $dialogMessageTable->update(array("is_read"=>$isRead),"message_id='{$messageId}'");
        die();
    }
    
    /*
     * used for get chat unread message count
     * param    :   sender_id,receiver_id
     */
    public function getUnreadMessageCountAction() {
        $this->checkValidRequest();
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
        $senderId = $this->getRequest()->getParam('sender_id',0);
        echo $dialogMessageTable->getUnreadMessageCount($senderId,$this->loggedUserRow->quickBloxId);
        die();
    }
    /*
     * used for get chat unread message count
     * param    :   sender_id,receiver_id
     */
    public function searchFriendsAction() {
        $this->_helper->layout()->disableLayout();
        $this->checkValidRequest();
        $friendsTable = new Application_Model_DbTable_Friends();
        $searchText= $this->getRequest()->getParam('searchTetxt','');
        $result = $friendsTable->getMyFriendListChat($this->loggedUserRow->userId,true,$searchText,true);
        //print_r($result);
        $this->view->userList =  $result;
    }

}

?>
