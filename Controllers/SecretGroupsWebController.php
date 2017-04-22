<?php

class SecretGroupsWebController extends My_Controller_Abstract {

    public function init() {
        parent::init();
        $this->_helper->layout->disableLayout();
    }

    public function indexAction() {
        
    }

    public function createGroupWebAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $groupName1 = $this->getRequest()->getPost('groupName', '');
        $userId = $this->loggedUserRow->userId;
        $groupName = $groupName1; //.'_secret';
        //die();
        $groupImage = $_FILES['groupImage'];
        $adminSecretName = $this->getRequest()->getPost('adminSecretName', '');
        $adminSecretImage = $_FILES['adminSecretImage'];
        $adminMessageDeleteTime = $this->getRequest()->getPost('adminMessageDeleteTime', '');
        $memberPassword = $this->getRequest()->getPost('memberPassword', '');
        $requestMemberSet = $this->getRequest()->getPost('requestMemberSet', '');
        $secGroupRow = $secGroupTable->getRowByUserIdAndGroupName($userId, $groupName);
        if ($secGroupRow->status == "2") {
            $secGroupRow->status = "1";
            $secGroupRow->modifyDate = date("Y-m-d H:i:s");
            $secGroupRow->save();
        } else {
            $data = array(
                'groupName' => $groupName,
                'adminId' => $userId,
                'creationDate' => date("Y-m-d H:i:s"),
                'modifyDate' => date("Y-m-d H:i:s"),
                'status' => "1",
                'deleteMessageMinutes'=>$this->getRequest()->getPost('minutes'),
                'deleteMessageHours'=>$this->getRequest()->getPost('hours'),
                'deleteMessageSeconds'=>$this->getRequest()->getPost('seconds')
                
            );

            $secGroupRow = $secGroupTable->createRow($data);
        }

        $secGroupRow->save();
         
        if (is_array($groupImage)) {
            $response = $this->common->uploadImage($groupImage);

            if (isset($response['new_file_name'])) {
                $file_path = "/images/" . $response['new_file_name'];
                $secGroupRow->groupImage = $file_path;
                $secGroupRow->save();
            }
        }

        $reponseData = array();
        $groupMembersQuickBloxId = array();

        if (count($requestMemberSet)) {
            foreach ($requestMemberSet as $requestMemberId) {
                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {

                    $secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $secGroupRow->secGroupId);

                    if ($secGroupMemberRow) {

                        if ($secGroupMemberRow->status == "2") {
                            $secGroupMemberRow->status = "0";
                            $secGroupMemberRow->secretName = "";
                            $secGroupMemberRow->secretImage = "";
                            $secGroupMemberRow->joiningDate = date("Y-m-d H:i:s");
                            $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                            $secGroupMemberRow->save();
                        }
                    } else {
                        $data = array(
                            'memberId' => $requestMemberId,
                            'secGroupId' => $secGroupRow->secGroupId,
                            'joiningDate' => date("Y-m-d H:i:s"),
                            'status' => "0",
                            'modifyDate' => date("Y-m-d H:i:s")
                        );

                        $secGroupMemberRow = $secGroupMemberTable->createRow($data);
                        $secGroupMemberRow->save();
                    }

                    if ($requestUserRow->quickBloxId) {
                        $groupMembersQuickBloxId[] = $requestUserRow->quickBloxId;
                    }

                    $reponseData[] = array(
                        'memberId' => $requestMemberId,
                        'quickBloxId' => ($requestUserRow->quickBloxId) ? $requestUserRow->quickBloxId : ""
                    );
                }
            }
        }

        $file_path = false;

        if (is_array($adminSecretImage)) {
            $response = $this->common->uploadImage($adminSecretImage, "users");

            if (isset($response['new_file_name'])) {
                $file_path = "/images/users/" . $response['new_file_name'];
            }
        }

        $reponseData[] = array(
            'memberId' => $userRow->userId,
            'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
        );

        $data = array(
            'memberId' => $userId,
            'secGroupId' => $secGroupRow->secGroupId,
            'secretName' => $adminSecretName,
            'secretImage' => ($file_path) ? $file_path : "",
            'messageDeleteTime' => ($adminMessageDeleteTime) ? $adminMessageDeleteTime : "",
            'memberPassword' => md5($memberPassword),
            'joiningDate' => date("Y-m-d H:i:s"),
            'status' => "1",
            'modifyDate' => date("Y-m-d H:i:s")
        );

        $secGroupMemberRow = $secGroupMemberTable->createRow($data);
        $secGroupMemberRow->save();

        if ($userRow->quickBloxId) {
            $groupMembersQuickBloxId[] = $userRow->quickBloxId;
        }

        if (count($groupMembersQuickBloxId)) {
            if ($quick_blox_response = $this->createQuickBloxGroup($userId, $secGroupRow->groupName, $groupMembersQuickBloxId)) {
                if (isset($quick_blox_response['_id'])) {
                    $secGroupRow->quick_blox_group_id = $quick_blox_response['_id'];
                    $secGroupRow->room_jid = $quick_blox_response['xmpp_room_jid'];
                    $secGroupRow->save();
                }
            }
        }
        $messageData = array(
            'name' => $groupName,
            'groupServerId' => $secGroupRow->secGroupId,
            'image' => ($secGroupRow->groupImage) ? $this->makeUrl($secGroupRow->groupImage) : "",
            'occupants_ids' => implode(",", $groupMembersQuickBloxId),
            'chat_dialog_id' => ($secGroupRow->quick_blox_group_id) ? $groupRow->quick_blox_group_id : "",
            'room_jid' => ($secGroupRow->room_jid) ? $secGroupRow->room_jid : "",
            'notification_type' => 1,
            'save_to_history' => 1,
            'userEmail' => $this->loggedUserRow->userEmail,
            'login_id' => $this->loggedUserRow->quickBloxId,
            'message' => $this->loggedUserRow->userNickName . ' created this group',
        );
        $this->sendCurlAsyncRequest("/groups-web/send-qb-notification", $messageData);
        $this->_helper->FlashMessenger->addMessage('Group created successfully', 'group_create');
        $this->_redirect('/chat?secGroupId=' . $secGroupRow->secGroupId);
    }

    public function addMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $this->getRequest()->getPost('userDeviceId', '');

        $secretGroupId = $decoded['secretGroupId'];
        $requestMemberSet = $decoded['requestMemberSet'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secretGroupId, $requestMemberSet));

            if ($userDeviceId) {
                $this->common->isUserLogin($userId, $userDeviceId);
            }

            if ($userRow = $userTable->getRowById($userId)) {

                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {

                    if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret group already deleted", "1", array(), "6");
                    }
                    /**
                     *  update secret name and secret image of login user
                     */
                    if ($secretGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) {

                        if (count($requestMemberSet)) {
                            foreach ($requestMemberSet as $requestMemberId) {
                                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {

                                    $secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $secretGroupId);

                                    if ($secGroupMemberRow) {
                                        if ($secGroupMemberRow->status == "2") {
                                            $secGroupMemberRow->status = "0";
                                            $secGroupMemberRow->secretName = "";
                                            $secGroupMemberRow->secretImage = "";
                                            $secGroupMemberRow->joiningDate = date("Y-m-d H:i:s");
                                            $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                                            $secGroupMemberRow->save();
                                        }
                                    } else {
                                        $data = array(
                                            'memberId' => $requestMemberId,
                                            'secGroupId' => $secretGroupId,
                                            'joiningDate' => date("Y-m-d H:i:s"),
                                            'status' => "0",
                                            'modifyDate' => date("Y-m-d H:i:s")
                                        );
                                        $secGroupMemberRow = $secGroupMemberTable->createRow($data);
                                        $secGroupMemberRow->save();
                                    }
                                }
                            }
                        }

                        $select = $secGroupMemberTable->select()->setIntegrityCheck(false)
                                ->from('secGroupMembers', array("*"))
                                ->joinLeft('users', "secGroupMembers.memberId = users.userId", array('*'))
                                ->where('secGroupMembers.secGroupId =?', $secretGroupId)
                                ->where('users.userStatus =?', "1")
                                ->Where('secGroupMembers.status !=?', "2");

                        $secGroupMemberRowset = $secGroupMemberTable->fetchAll($select);

                        $membersData = array();

                        foreach ($secGroupMemberRowset as $memberRow) {
                            $membersData[] = array(
                                'memberRowId' => $memberRow->id,
                                'memberId' => $memberRow->memberId,
                                'memberName' => ($memberRow->secretName) ? $memberRow->secretName : "",
                                'memberImage' => ($memberRow->secretImage) ? $memberRow->secretImage : "",
                                'is_online' => $userSettingTable->isUserOnline($memberRow->memberId),
                                'status' => $memberRow->status
                            );
                        }

                        $response = array(
                            'response_string' => 'added new members successfully',
                            'error_code' => '0',
                            'result' => $membersData,
                            'sec_group_admin_id' => $secretGroupRow->adminId,
                            'response_error_key' => '0',
                            'secretGroupId' => $secretGroupId,
                            'message_delete_time' => $secretGroupMemberRow->messageDeleteTime,
                        );
                        echo json_encode($response);
                        exit;

                        /**
                         * adding new members code end
                         */
                    } else {
                        $this->common->displayMessage("user is not a group member", "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage('secret group id is not correct', '1', array(), '4');
                }
            } else {
                $this->common->displayMessage('User Account is not exist', '1', array(), '2');
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for updating secret group name and image
     */
    public function editGroupWebAction() {
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $userId = $this->loggedUserRow->userId;
        $secretGroupId = $this->getRequest()->getPost('secretGroupId', '');
        $secretGroupName = $this->getRequest()->getPost('secretGroupName', '');
        $removeUser = $this->getRequest()->getPost('remove_user', '');
        $addUsers=$this->getRequest()->getPost('requestMemberSet');
        $secretGroupImage = $_FILES['groupImage'];
        $memberPassword = $this->getRequest()->getPost('memberPassword', '');
        $userSecretName = $this->getRequest()->getPost('userSecretName', '');
        $userMessageDeleteTime = $this->getRequest()->getPost('userMessageDeleteTime', '');
       // print_r($_FILES);
        $userSecretImage = $_FILES['userSecretImage'];
        if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) {
            if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {

                if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                    $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                }

                if ($secretGroupRow->status == "2") {
                    $this->common->displayMessage("Secret group already deleted", "1", array(), "6");
                }

                if ($secretGroupName) {
                    $secretGroupRow->groupName = $secretGroupName;
                }

                if (is_array($secretGroupImage)) {
                    $response = $this->common->uploadImage($secretGroupImage);

                    if (isset($response['new_file_name'])) {
                        $file_path = "/images/" . $response['new_file_name'];
                        $secretGroupRow->groupImage = $file_path;
                    }
                }


                /**
                 *  updating login member details
                 */
                $is_update_member_details = false;

                if ($memberPassword) {
                    $secGroupMemberRow->memberPassword = md5($memberPassword);
                    $is_update_member_details = true;
                }

                if ($userSecretName) {
                    $secGroupMemberRow->secretName = $userSecretName;
                    $is_update_member_details = true;
                }

                if ($userMessageDeleteTime) {
                    $secGroupMemberRow->messageDeleteTime = $userMessageDeleteTime;
                    $is_update_member_details = true;
                }

                $file_path = false;

                if (is_array($userSecretImage)) {
                    $response = $this->common->uploadImage($userSecretImage, "users");

                    if (isset($response['new_file_name'])) {
                        $file_path = "/images/users/" . $response['new_file_name'];
                        $secGroupMemberRow->secretImage = $file_path;
                    }
                }
                
                if ($is_update_member_details) {
                    $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                }
                $secGroupMemberRow->status = "1";
                $secGroupMemberRow->save();
                $secretGroupRow->deleteMessageMinutes=$this->getRequest()->getPost('minutes');
                $secretGroupRow->deleteMessageHours=$this->getRequest()->getPost('hours');
                $secretGroupRow->deleteMessageSeconds=$this->getRequest()->getPost('seconds');
                $secretGroupRow->modifyDate = date("Y-m-d H:i:s");
                $secretGroupRow->save();
                /**
                 *  end login member detail code
                 */
                
                (count($addUsers)>0)?$this->sendCurlAsyncRequest('/secret-groups-web/add-member', array('userId' => $this->loggedUserRow->userId, 'groupId' => $secretGroupRow->secGroupId, 'requestMemberSet' => json_encode($addUsers))):"";
                if ($secretGroupRow->adminId == $this->loggedUserRow->userId) {
                    if (count($removeUser) > 0) {
                        echo count($removeUser);
                        $this->sendCurlAsyncRequest('/secret-groups-web/delete-member', array('userId' => $this->loggedUserRow->userId, 'groupId' => $secretGroupRow->secGroupId, 'requestMemberSet' => json_encode($removeUser)));
                    }
                }
                $this->_helper->FlashMessenger->addMessage('Group updated successfully', 'group_create');
                $this->_redirect('/chat?secGroupId=' . $secretGroupRow->secGroupId);
            } else {
                $this->common->displayMessage("Secret group id is not correct", '1', array(), '4');
            }
        } else {
            $this->common->displayMessage("User is not a group member", '1', array(), '5');
        }
    }

    /**
     * function for accepting membership 
     */
    public function memberAcceptanceAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $decoded = $this->common->Decoded();

        $userSecurity = $this->getRequest()->getPost('userSecurity', '');
        $userId = $this->getRequest()->getPost('userId', '');
        $userDeviceId = $this->getRequest()->getPost('userDeviceId', '');
        $secretGroupId = $this->getRequest()->getPost('secretGroupId', '');
        $memberPassword = $this->getRequest()->getPost('memberPassword', '');
        $userSecretName = $this->getRequest()->getPost('userSecretName', '');
        $userMessageDeleteTime = $this->getRequest()->getPost('userMessageDeleteTime', '');
        $userSecretImage = $_FILES['userSecretImage'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secretGroupId, $userSecretName, $userMessageDeleteTime));

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) {
                    if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {

                        if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                            $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                        }

                        if ($secretGroupRow->status == "2") {
                            $this->common->displayMessage("Already deleted group", "1", array(), "6");
                        }

                        /**
                         *  updating login member details
                         */
                        $secGroupMemberRow->status = "1";
                        $secGroupMemberRow->memberPassword = md5($memberPassword);
                        $secGroupMemberRow->secretName = $userSecretName;
                        $secGroupMemberRow->messageDeleteTime = $userMessageDeleteTime;
                        $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                        $secGroupMemberRow->status = "1";

                        $file_path = false;

                        if (is_array($userSecretImage)) {
                            $response = $this->common->uploadImage($userSecretImage, "users");

                            if (isset($response['new_file_name'])) {
                                $file_path = "/images/users/" . $response['new_file_name'];
                                $secGroupMemberRow->secretImage = $file_path;
                            }
                        }
                        $secGroupMemberRow->save();

                        $secretGroupRow->modifyDate = date("Y-m-d H:i:s");
                        $secretGroupRow->save();

                        /**
                         *  end login member detail code
                         */
                        $data = array(
                            'response_string' => 'Member accepted',
                            'error_code' => '0',
                            'response_error_key' => '0',
                            'groupId' => ($secretGroupRow->secGroupId) ? $secretGroupRow->secGroupId : "",
                            'is_admin' => ($secretGroupRow->adminId == $userId) ? "1" : "0",
                            'adminId' => $secretGroupRow->adminId,
                            'groupName' => $secretGroupRow->groupName,
                            'groupImage' => ($secretGroupRow->groupImage) ? $secretGroupRow->groupImage : "",
                            'creationDate' => $secretGroupRow->creationDate,
                            'modifyDate' => $secretGroupRow->modifyDate,
                            'messageDeleteTime' => ($secGroupMemberRow->messageDeleteTime) ? $secGroupMemberRow->messageDeleteTime : "",
                            'memberPassword' => ($secGroupMemberRow->memberPassword) ? $secGroupMemberRow->memberPassword : "",
                            'member_status' => ($secGroupMemberRow->status) ? "1" : "0",
                            'description' => ($secretGroupRow->description) ? $secretGroupRow->description : "",
                        );

                        echo json_encode($data);
                        exit;
                    } else {
                        $this->common->displayMessage("Secret group id is not correct", '1', array(), '4');
                    }
                } else {
                    $this->common->displayMessage("User is not a group member", '1', array(), '5');
                }
            } else {
                $this->common->displayMessage('User Account is not exist', '1', array(), '2');
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for getting all groups name with members names in string formate
     */
    public function secGroupsListAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];

        $last_request_time = isset($decoded['last_request_time']) ? $decoded['last_request_time'] : '';

        if ($userSecurity == $this->servicekey) {
            //       $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId));
            if ($decoded['userDeviceId']) {
                $this->common->isUserLogin($userId, $userDeviceId);
            }

            if ($userRow = $userTable->getRowById($userId)) {

                $secGroupsDetails = array();  // consist details of groups and its member

                $select = $secGroupTable->userGroups($userId, true);

                if ($last_request_time) {
                    $select->where('(secretGroup.modifyDate >?', $last_request_time)
                            ->orWhere('secGroupMembers.modifyDate >?)', $last_request_time);
                }

                $secGroupsRowset = $secGroupTable->fetchAll($select);

                foreach ($secGroupsRowset as $secGroupRow) {

                    if (!$this->checkAndDeleteWrongPasswordGroup($secGroupRow->secGroupId)) {
                        $data = array(
                            'groupId' => $secGroupRow->secGroupId,
                            'is_admin' => ($secGroupRow->adminId == $userId) ? "1" : "0",
                            'adminId' => ($secGroupRow->adminId) ? $secGroupRow->adminId : "",
                            'groupName' => ($secGroupRow->groupName) ? $secGroupRow->groupName : "",
                            'groupImage' => ($secGroupRow->groupImage) ? $secGroupRow->groupImage : "",
                            'memberPassword' => ($secGroupRow->memberPassword) ? $secGroupRow->memberPassword : "",
                            'creationDate' => $secGroupRow->creationDate,
                            'quick_blox_group_id' => ($secGroupRow->quick_blox_group_id) ? $secGroupRow->quick_blox_group_id : "",
                            'room_jid' => ($secGroupRow->room_jid) ? $secGroupRow->room_jid : "",
                            'modifyDate' => $secGroupRow->modifyDate,
                            'description' => ($secGroupRow->description) ? $secGroupRow->description : "",
                            'member_status' => ($secGroupRow->member_status) ? "1" : "0"
                        );
                        $secGroupsDetails[] = $data;
                    }
                }



                $deletedSecretGroups = $secGroupTable->deletedSecretGroups($userId, $last_request_time);
                $deletedGrpArr = array();

                foreach ($deletedSecretGroups as $deletedSecGrpRow) {
                    $deletedGrpArr[] = $deletedSecGrpRow->secGroupId;
                }

                $responseData = array(
                    'response_string' => 'Secret groups listing',
                    'error_code' => '0',
                    'response_error_key' => '0',
                    'last_request_time' => date("Y-m-d H:i:s"),
                    'result' => $secGroupsDetails,
                    'deleted_groups' => $deletedGrpArr
                );

                echo json_encode($responseData);
                exit;
            } else {
                $this->common->displayMessage('User account is not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  get group details  (secret group name, secret group image, login user secret name, login user secret image, message deleet time)
     */
    public function getGroupDetailAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $secretGroupId = $decoded['secretGroupId'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secretGroupId));

            if ($decoded['userDeviceId']) {
                $this->common->isUserLogin($userId, $userDeviceId);
            }

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {
                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret group is already deleted", "1", array(), "6");
                    }

                    if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) {

                        $data = array(
                            'groupName' => ($secretGroupRow->groupName) ? $secretGroupRow->groupName : "",
                            'groupImage' => ($secretGroupRow->groupImage) ? $secretGroupRow->groupImage : "",
                            'creationDate' => $secretGroupRow->creationDate,
                            'modifyDate' => $secretGroupRow->modifyDate,
                            'userSecretName' => ($secGroupMemberRow->secretName) ? $secGroupMemberRow->secretName : "",
                            'userSecretImage' => ($secGroupMemberRow->secretImage) ? $secGroupMemberRow->secretImage : "",
                            'messageDeleteTime' => ($secGroupMemberRow->messageDeleteTime) ? $secGroupMemberRow->messageDeleteTime : ""
                        );

                        $this->common->displayMessage("Group details", '0', $data, '0');
                    } else {
                        $this->common->displayMessage("User is not a group member", '1', array(), '5');
                    }
                } else {
                    $this->common->displayMessage("Secret group id is not correct", '1', array(), '4');
                }
            } else {
                $this->common->displayMessage('User account is not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  get secret group members
     */
    public function secretGroupMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $secretGroupId = $decoded['secretGroupId'];

        if ($this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secretGroupId));

            if (isset($decoded['userDeviceId'])) {
                $this->common->isUserLogin($userId, $userDeviceId);
            }

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {

                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret Group already deleted", "1", array(), "6");
                    }

                    if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if (($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) && ($secGroupMemberRow->status != "2")) {
                        $select = $secGroupMemberTable->select()->setIntegrityCheck(false)
                                ->from('secGroupMembers', array("*"))
                                ->joinLeft('users', "secGroupMembers.memberId = users.userId", array('*'))
                                ->where('secGroupMembers.secGroupId =?', $secretGroupId)
                                ->where('users.userStatus =?', "1")
                                ->Where('secGroupMembers.status !=?', "2");

                        $secGroupMemberRowset = $secGroupMemberTable->fetchAll($select);

                        $deletedMembersRowset = $secGroupMemberTable->deletedMembersList($secretGroupId);

                        $membersData = array();
                        $deleteMemberList = array();

                        foreach ($secGroupMemberRowset as $memberRow) {
                            $membersData[] = array(
                                'memberRowId' => $memberRow->id,
                                'memberId' => $memberRow->memberId,
                                'memberName' => ($memberRow->secretName) ? $memberRow->secretName : "",
                                'memberImage' => ($memberRow->secretImage) ? $memberRow->secretImage : "",
                                'is_online' => $userSettingTable->isUserOnline($memberRow->memberId),
                                'status' => $memberRow->status,
                                'quickBloxId' => ($memberRow->quickBloxId) ? $memberRow->quickBloxId : ""
                            );
                        }

                        foreach ($deletedMembersRowset as $deleteMemberRow) {
                            $deleteMemberList[] = $deleteMemberRow->userId;
                        }

                        $response = array(
                            'response_string' => 'Secret Group members list',
                            'error_code' => '0',
                            'result' => $membersData,
                            'delete_members_list' => $deleteMemberList,
                            'sec_group_admin_id' => ($secretGroupRow->adminId) ? $secretGroupRow->adminId : "",
                            'response_error_key' => '0',
                            'secretGroupId' => $secretGroupId,
                            'sec_group_image' => ($secretGroupRow->groupImage) ? $secretGroupRow->groupImage : "",
                            'sec_group_name' => ($secretGroupRow->groupName) ? $secretGroupRow->groupName : "",
                            'message_delete_time' => $secGroupMemberRow->messageDeleteTime,
                        );
                        echo json_encode($response);
                        exit;
                    } else {
                        $this->common->displayMessage("User is not a group user", "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage("Secret group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }
    /**
     * function for deleting users from group Note : Admin can delete to any member but user can delete to him self only
     */
    public function deleteMemberAction() {
        $groupTable = new Application_Model_DbTable_SecretGroups();
        $groupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $memberIds = $this->getRequest()->getPost('requestMemberSet');
        $groupId = $this->getRequest()->getPost('groupId');
	$userId = $this->getRequest()->getPost('userId');
        $groupRow = $groupTable->getRowById($groupId);
        print_r($memberIds);
	if ($groupRow->adminId==$userId) {
            $memberIds=json_decode($memberIds);
            foreach($memberIds as $memberId){
                print_r($memberIds);
                $groupMemberTable->updateData(array('status' => 2, 'modifyDate' => date("Y-m-d H:i:s")), "memberId='{$memberId}' AND secGroupId='{$groupId}'");
                $groupRow->modifyDate = date("Y-m-d H:i:s");
                $groupRow->save();
          }
        }

    }
    
    /**
     *  function for adding new users to group
     */
    public function addMemberAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_SecretGroups();
        $groupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $groupId = $this->getRequest()->getPost('groupId');
        $requestMemberSet = $this->getRequest()->getPost('requestMemberSet');
        $requestMemberSet = json_decode($requestMemberSet);
        if ($groupRow = $groupTable->getRowById($groupId)) {
                foreach ($requestMemberSet as $requestMemberId) {
                    if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                        if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $groupRow->secGroupId)) {
                            if ($groupMemberRow->status == Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                $groupMemberRow->status = Application_Model_DbTable_GroupMembers::MEMBER_ACTIVE;
                                $groupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                $groupMemberRow->save();
                            }
                        } else {
                            $data = array(
                                'memberId' => $requestMemberId,
                                'secGroupId' => $groupRow->secGroupId,
                                'joiningDate' => date("Y-m-d H:i:s"),
                                'status' => "1",
                                'modifyDate' => date("Y-m-d H:i:s")
                            );
                            $groupMemberRow = $groupMemberTable->createRow($data);
                            $groupMemberRow->save();
                        }
                    }

                   
                }
            }
    }
    /**
     *  delete secret group member
     */
    public function deleteMember1Action() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $memberId = $decoded['memberId'];
        $secretGroupId = $decoded['secretGroupId'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $memberId, $secretGroupId));

            if (isset($decoded['userDeviceId'])) {
                $this->common->isUserLogin($userId, $userDeviceId);
            }

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {
                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret Group already deleted", "1", array(), "9");
                    }

                    if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if (($secretGroupRow->adminId == $userId) || ($userId == $memberId)) {

                        $delete_sec_grp = false;

                        if ($secretGroupRow->adminId == $memberId) {
                            $this->deleteSecGrp($secretGroupId);
                            $this->common->displayMessage('Member deleted successfully', "0", array(), "0");
                        }

                        if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($memberId, $secretGroupId)) {
                            if ($secGroupMemberRow->status == "2") {
                                $this->common->displayMessage('Member already deleted', "1", array(), "7");
                            }

                            $secGroupMemberRow->status = "2";
                            $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                            $secGroupMemberRow->save();

                            $secretGroupRow->modifyDate = date("Y-m-d H:i:s");
                            $secretGroupRow->save();

                            if (!count($secGroupMemberTable->getGroupMembers($secretGroupId))) {
                                $this->deleteSecGrp($secretGroupId);
                                $this->common->displayMessage('Secret group deleted', "1", array(), "8");
                            }

                            $select = $secGroupMemberTable->select()->setIntegrityCheck(false)
                                    ->from('secGroupMembers', array("*"))
                                    ->joinLeft('users', "secGroupMembers.memberId = users.userId", array('*'))
                                    ->where('secGroupMembers.secGroupId =?', $secretGroupId)
                                    ->where('users.userStatus =?', "1")
                                    ->Where('secGroupMembers.status !=?', "2");

                            $secGroupMemberRowset = $secGroupMemberTable->fetchAll($select);

                            $membersData = array();

                            foreach ($secGroupMemberRowset as $memberRow) {
                                $membersData[] = array(
                                    'memberRowId' => $memberRow->id,
                                    'memberId' => $memberRow->memberId,
                                    'memberName' => ($memberRow->secretName) ? $memberRow->secretName : "",
                                    'memberImage' => ($memberRow->secretImage) ? $memberRow->secretImage : "",
                                    'is_online' => $userSettingTable->isUserOnline($memberRow->memberId),
                                    'status' => $memberRow->status
                                );
                            }

                            $response = array(
                                'response_string' => 'Member deleted successfully',
                                'error_code' => '0',
                                'result' => $membersData,
                                'sec_group_admin_id' => ($secretGroupRow->adminId) ? $secretGroupRow->adminId : "",
                                'response_error_key' => '0',
                                'secretGroupId' => $secretGroupId,
                                'message_delete_time' => $secretGroupMemberRow->messageDeleteTime,
                            );
                            echo json_encode($response);
                            exit;

                            $this->common->displayMessage('Member deleted successfully', "0", array(), "0");
                        } else {
                            $this->common->displayMessage('Member id is not correct', "1", array(), "6");
                        }
                    } else {
                        $this->common->displayMessage('You are not admin of this group', "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage("Secret group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for setting password for secret member
     */
    public function setPassword1Action() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $memberId = $decoded['memberId'];
        $secretGroupId = $decoded['secretGroupId'];
        $memberPassword = $decoded['memberPassword'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $memberId, $secretGroupId));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {
                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret Group already deleted", "1", array(), "9");
                    }

                    if ($this->checkAndDeleteWrongPasswordGroup($secretGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($memberId, $secretGroupId)) {

                        if ($secGroupMemberRow->status == "2") {
                            $this->common->displayMessage('Member already deleted', "1", array(), "5");
                        } else {
                            $secGroupMemberRow->memberPassword = md5($memberPassword);
                            $secGroupMemberRow->save();
                            $this->common->displayMessage("You are not a member of this group", "1", array(), "4");
                        }
                    } else {
                        $this->common->displayMessage("You are not a member of this group", "1", array(), "4");
                    }
                } else {
                    $this->common->displayMessage("Secret group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     * function for check secret group member password
     */
    public function checkSecretPasswordAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $secretGroupId = $decoded['secretGroupId'];
        $memberPassword = $decoded['memberPassword'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secretGroupId));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {
                if ($secretGroupRow = $secGroupTable->getRowById($secretGroupId)) {

                    if ($secretGroupRow->status == "2") {
                        $this->common->displayMessage("Secret group is already deleted", "1", array(), "9");
                    }

                    if ($secGroupMemberRow = $secGroupMemberTable->getRowByMemberIdAndGroupId($userId, $secretGroupId)) {

                        if ($secGroupMemberRow->status == "2") {
                            $this->common->displayMessage('Member already deleted', "1", array(), "6");
                        } else {

                            if ($secretGroupRow->incorrect_pwd_time) {

                                if ((time() - strtotime($secretGroupRow->incorrect_pwd_time)) > 30) {
                                    $secretGroupRow->status = '2';
                                    $secretGroupRow->modifyDate = date("Y-m-d H:i:s");
                                    $secretGroupRow->save();

                                    $secGroupMemberTable->deleteMembers($secretGroupId);
                                    $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                                } else {
                                    if ($secretGroupRow->incorrect_pwd_member_id == $userId) {
                                        $secretGroupRow->incorrect_pwd_time = new Zend_Db_Expr('null');
                                        $secretGroupRow->incorrect_pwd_member_id = new Zend_Db_Expr('null');
                                        $secretGroupRow->save();
                                    }
                                }
                            }

                            if ($secGroupMemberRow->memberPassword == md5($memberPassword)) {
                                //    $secGroupMemberRow->incorrect_pwd_time = new Zend_Db_Expr('null');
                                //    $secGroupMemberRow->save();
                                $this->common->displayMessage("Correct password", "0", array(), "0");
                            } else {
                                if (!$secretGroupRow->incorrect_pwd_time) {
                                    $secretGroupRow->incorrect_pwd_time = date("Y-m-d H:i:s");
                                    $secretGroupRow->incorrect_pwd_member_id = $userId;
                                    $secretGroupRow->save();
                                }
                                $this->common->displayMessage("Incorrect password", "1", array(), "7");
                            }
                        }
                    } else {
                        $this->common->displayMessage("You are not a member of this group", "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage("Secret group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for add and delete members
     */
    public function addDeleteMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secretGrpTable = new Application_Model_DbTable_SecretGroups();
        $secretGrpMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $secGroupId = $decoded['secGroupId'];
        $addMemberSet = $decoded['addMemberSet'];
        $deleteMemberSet = $decoded['deleteMemberSet'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secGroupId));

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {
                $this->common->isUserLogin($userId, $userDeviceId);

                if ($secGroupRow = $secretGrpTable->getRowById($secGroupId)) {

                    if ($secGroupRow->status == "2") {
                        $this->common->displayMessage("Secret Group already deleted", "1", array(), "7");
                    }

                    if ($this->checkAndDeleteWrongPasswordGroup($secGroupId)) {
                        $this->common->displayMessage("Group deleted due to incorrect password", "1", array(), "11");
                    }

                    if ($secGroupRow->adminId == $userId) {
                        foreach ($addMemberSet as $addMemberId) {
                            if (($requestUserRow = $userTable->getRowById($addMemberId)) && ($requestUserRow->userStatus == "1")) {
                                if ($secGroupMemberRow = $secretGrpMemberTable->getRowByMemberIdAndGroupId($addMemberId, $secGroupId)) {

                                    if ($secGroupMemberRow->status == Application_Model_DbTable_SecGroupMembers::MEMBER_DELETED) {
                                        $secGroupMemberRow->status = Application_Model_DbTable_SecGroupMembers::MEMBER_INACTIVE;
                                        $secGroupMemberRow->secretName = "";
                                        $secGroupMemberRow->secretImage = "";
                                        $secGroupMemberRow->joiningDate = date("Y-m-d H:i:s");
                                        $secGroupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                        $secGroupMemberRow->save();
                                    }
                                } else {
                                    $data = array(
                                        'memberId' => $addMemberId,
                                        'secGroupId' => $secGroupRow->secGroupId,
                                        'joiningDate' => date("Y-m-d H:i:s"),
                                        'status' => "0",
                                        'modifyDate' => date("Y-m-d H:i:s")
                                    );

                                    $secGroupMemberRow = $secretGrpMemberTable->createRow($data);
                                    $secGroupMemberRow->save();
                                }
                            }
                            $secGroupRow->modifyDate = date("Y-m-d H:i:s");
                            $secGroupRow->save();
                        }

                        $delete_sec_grp = false;

                        foreach ($deleteMemberSet as $deleteMemberId) {
                            if ($secGroupMemberRow = $secretGrpMemberTable->getRowByMemberIdAndGroupId($deleteMemberId, $secGroupId)) {

                                if ($secGroupMemberRow->status != Application_Model_DbTable_SecGroupMembers::MEMBER_DELETED) {
                                    $secGroupMemberRow->status = "2";
                                    $secGroupMemberRow->modifyDate = date("Y-m-d H:i:s");
                                    $secGroupMemberRow->save();

                                    $secGroupRow->modifyDate = date("Y-m-d H:i:s");
                                    $secGroupRow->save();
                                }
                            }

                            if ($deleteMemberId == $secGroupRow->adminId) {
                                $delete_sec_grp = true;
                            }
                        }

                        $countGroupMembers = count($secretGrpMemberTable->getGroupMembers($secGroupRow->secGroupId, true));

                        if (!$countGroupMembers) {
                            $delete_sec_grp = true;
                        }

                        if ($delete_sec_grp) {
                            $this->deleteSecGrp($secGroupId);
                            $this->common->displayMessage('delete secret group', "1", array(), "6");
                        }


                        $select = $secretGrpMemberTable->select()->setIntegrityCheck(false)
                                ->from('secGroupMembers', array("*"))
                                ->joinLeft('users', "secGroupMembers.memberId = users.userId", array('*'))
                                ->where('secGroupMembers.secGroupId =?', $secretGroupId)
                                ->where('users.userStatus =?', "1")
                                ->Where('secGroupMembers.status !=?', "2");

                        $secGroupMemberRowset = $secretGrpMemberTable->fetchAll($select);

                        $membersData = array();

                        foreach ($secGroupMemberRowset as $memberRow) {
                            $membersData[] = array(
                                'memberRowId' => $memberRow->id,
                                'memberId' => $memberRow->memberId,
                                'memberName' => ($memberRow->secretName) ? $memberRow->secretName : "",
                                'memberImage' => ($memberRow->secretImage) ? $memberRow->secretImage : "",
                                'is_online' => $userSettingTable->isUserOnline($memberRow->memberId),
                                'status' => $memberRow->status
                            );
                        }

                        $response = array(
                            'response_string' => 'Member added/deleted successfully',
                            'error_code' => '0',
                            'result' => $membersData,
                            'sec_group_admin_id' => ($secGroupRow->adminId) ? $secGroupRow->adminId : "",
                            'response_error_key' => '0',
                            'message_delete_time' => $secGroupMemberRow->messageDeleteTime,
                            'secretGroupId' => $secGroupId
                        );

                        echo json_encode($response);
                        exit;
                    } else {
                        $this->common->displayMessage('You are not a admin of this secret group', "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage("Group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function deleteSecretGroupAction() {
        $userTable = new Application_Model_DbTable_Users();
        $secretGrpTable = new Application_Model_DbTable_SecretGroups();
        $secretGrpMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $secGroupId = $decoded['secGroupId'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $secGroupId));
            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {
                if ($secGroupRow = $secretGrpTable->getRowById($secGroupId)) {

                    if ($secGroupRow->status == "2") {
                        $this->common->displayMessage("Secret Group already deleted", "1", array(), "5");
                    }

                    $this->deleteSecGrp($secGroupId);

                    $this->common->displayMessage("Secret Group deleted successfully", "0", array(), "0");
                } else {
                    $this->common->displayMessage("Secret Group id is not correct", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function deleteSecGrp($secret_grp_id) {
        $userTable = new Application_Model_DbTable_Users();
        $secretGrpTable = new Application_Model_DbTable_SecretGroups();
        $secretGrpMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $userSettingTable = new Application_Model_DbTable_UserSetting();

        if ($secGroupRow = $secretGrpTable->getRowById($secret_grp_id)) {
            $select = $secretGrpMemberTable->select()
                    ->where('secGroupId =?', $secret_grp_id)
                    ->where('status !=?', '2');

            $secretGrpMembersRowset = $secretGrpMemberTable->fetchAll($select);

            $message = "Delete secret group";

            foreach ($secretGrpMembersRowset as $secGrpMemberRow) {
                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($secGrpMemberRow->memberId);

                foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType == "iphone") {
                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'sound' => 'Default', 'type' => 'delete_secret_group', 'secret_group_id' => $secret_grp_id);
                    } else {
                        $payload = array(
                            'message' => $message,
                            'type' => "delete_secret_group",
                            'result' => array('secret_group_id' => $secret_grp_id)
                        );
                        $payload = json_encode($payload);
                    }
                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                }

                $secGroupRow->status = "2";
                $secGroupRow->groupName = "";
                $secGroupRow->groupEmail = "";
                $secGroupRow->description = "";
                $secGroupRow->groupImage = "";
                $secGroupRow->modifyDate = date("Y-m-d H:i:s");
                $secGroupRow->save();

                $secGrpMemberRow->status = "2";
                $secGrpMemberRow->modifyDate = date("Y-m-d H:i:s");
                $secGrpMemberRow->save();
            }
        }
    }

    public function testPushAction() {
        /*     $payload = array(
          'message'   => "",
          'type'      => "edit_group",
          'result'    => array('group_id' => "12")
          );
          $payload = json_encode($payload);
          $token = 'APA91bHX0NdIyAsHzFdycA864b35tttBeCyk-GMrKAFdiUGc5DsldeQGgUM38o3fpzDvpX-CW94sDUqysVj21QOWo1E3mabQIQHkoKjJRRpGLc5qSJ7BSfafZ_VhOqvRGMQZuDZS6_szCm1Iv6XmxtI5wPiq9Hn2Gw';
         */
        $message = "testing";
        $groupId = '91';

        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'edit_group', 'group_id' => $groupId);
        $token = "627c675551079298efaa23a05159f528b7f363a840752f455dd1e71e2ad09429"; //71183ed60646ed5f9d79efc88a3277adeb425f9dfcf886a2711a03229316f24c

        $this->common->sendPushMessage("iphone", $token, $payload);

        echo "send";
        exit;
    }

    public function checkAndDeleteWrongPasswordGroup($secret_grp_id) {
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $secGroupMemberTable = new Application_Model_DbTable_SecGroupMembers();

        $is_delete_group = false;

        if ($secretGroupRow = $secGroupTable->getRowById($secret_grp_id)) {
            if ($secretGroupRow->incorrect_pwd_time) {
                if ((time() - strtotime($secretGroupRow->incorrect_pwd_time)) > 30) {
                    $secretGroupRow->status = '2';
                    $secretGroupRow->modifyDate = date("Y-m-d H:i:s");
                    $secretGroupRow->save();

                    $secGroupMemberTable->deleteMembers($secretGroupId);

                    $is_delete_group = true;
                }
            }
        }

        return $is_delete_group;
    }

    /**
     *  function for getting all groups name with members names in string formate
     */
    public function getGroupListAction() {
        $this->_helper->layout()->disableLayout();
        $this->checkValidRequest();
        $groupTable = new Application_Model_DbTable_SecretGroups();
        $userId = $this->loggedUserRow->userId;
        $select = $groupTable->userGroups($userId, true);
        $groupsRowset = $groupTable->fetchAll($select);
        $this->view->groupList = $groupsRowset;
    }
     public function setPasswordAction() {
        $this->_helper->layout()->disableLayout();
        $this->checkValidRequest();
        $groupMemberTable = new Application_Model_DbTable_SecGroupMembers();
        $userId = $this->loggedUserRow->userId;
        $groupId = (int)$this->getRequest()->getPost("id");
        $dialog = $this->getRequest()->getPost("dialog");
        $password=$this->getRequest()->getPost('password','');
        $groupMemberTable->updateData(array('memberPassword'=>md5($password)), "memberId='{$userId}' AND secGroupId='{$groupId}'");
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
       // $result = $dialogMessageTable->geChatHistory('', $dialog, 2);
        $result = '';//$dialogMessageTable->getSecChatHistory($userId, $sender_id, $dialog, $groupId, $password);
        echo json_encode($result);
        die();
    }
    

}

?>
