<?php

class GroupsController extends My_Controller_Abstract {

    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->disableLayout();
    }

    public function indexAction() {
        
    }

    public function createGroupsAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $decoded = $this->common->Decoded();
        $userSecurity = $this->getRequest()->getPost('userSecurity', '');
        $userId = $this->getRequest()->getPost('userId', '');
        $userDeviceId = $this->getRequest()->getPost('userDeviceId', '');
        $groupName = $this->getRequest()->getPost('groupName', '');
        $description = $this->getRequest()->getPost('description', '');
        $groupImage = $_FILES['groupImage'];
        $requestMemberStr = $this->getRequest()->getPost('requestMemberSet', '');
        $groupMembersQuickBloxId = $this->getRequest()->getPost('requestQBMemberSet', '');
        $groupMembersQuickBloxId = explode(",", $groupMembersQuickBloxId);
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $groupName, $requestMemberStr));
            if ($userRow = $userTable->getRowById($userId)) {
                $requestMemberSet = explode(",", $requestMemberStr);
                if (count($requestMemberSet) < 2) {
                    $this->common->displayMessage('Select atleast 2 members for group', "1", array(), "5");
                }

                $reponseData = array();
                $memberNames = array();
                $memberNames[] = $userRow->userNickName;
                if (count($groupMembersQuickBloxId)) {
                    if ($quick_blox_response = $this->createQuickBloxSimpleGroup($userId, $groupName, $groupMembersQuickBloxId)) {
                        if (isset($quick_blox_response['_id'])) {
                            $data = array(
                                'groupName' => $groupName,
                                'adminId' => $userId,
                                'description' => $description,
                                'creationDate' => date("Y-m-d H:i:s"),
                                'modifyDate' => date("Y-m-d H:i:s"),
                                'status' => "1"
                            );

                            $groupRow = $groupTable->createRow($data);
                            $groupRow->save();
                            $groupRow->quick_blox_group_id = $quick_blox_response['_id'];
                            $groupRow->room_jid = $quick_blox_response['xmpp_room_jid'];
                            $groupRow->save();
                            if (is_array($groupImage)) {
                                $response = $this->common->uploadImage($groupImage);

                                if (isset($response['new_file_name'])) {
                                    $file_path = "/images/" . $response['new_file_name'];
                                    $groupRow->groupImage = $file_path;
                                    $groupRow->save();
                                }
                            }
                            foreach ($requestMemberSet as $requestMemberId) {
                                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                                    if (!$groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($requestMemberId, $groupRow->groupId)) {
                                        $data = array(
                                            'memberId' => $requestMemberId,
                                            'quick_blox_group_id' => $groupRow->quick_blox_group_id,
                                            'joiningDate' => date("Y-m-d H:i:s"),
                                            'status' => "1",
                                            'modifyDate' => date("Y-m-d H:i:s")
                                        );

                                        $groupMemberRow = $groupMemberTable->createRow($data);
                                        $groupMemberRow->save();
                                        $reponseData[] = array(
                                            'memberId' => $requestMemberId,
                                            'memberRowId' => $groupMemberRow->id,
                                            'memberName' => ($requestUserRow->userNickName) ? $requestUserRow->userNickName : "",
                                            'memberImage' => ($requestUserRow->userImage) ? $this->makeUrl($requestUserRow->userImage) : "",
                                            'quickBloxId' => ($requestUserRow->quickBloxId) ? $requestUserRow->quickBloxId : ""
                                        );

                                        $memberNames[] = $requestUserRow->userNickName;
                                    }

                                    if ($requestUserRow->quickBloxId) {
                                        $groupMembersQuickBloxId[] = $requestUserRow->quickBloxId;
                                    }
                                }
                            }

                            $reponseData[] = array(
                                'memberId' => $userId,
                                'memberRowId' => $groupMemberRow->id,
                                'memberName' => ($userRow->userNickName) ? $userRow->userNickName : "",
                                'memberImage' => ($userRow->userImage) ? $this->makeUrl($userRow->userImage) : "",
                                'quickBloxId' => ($userRow->quickBloxId) ? $userRow->quickBloxId : ""
                            );

                            $data = array(
                                'memberId' => $userId,
                                'quick_blox_group_id' => $groupRow->quick_blox_group_id,
                                'joiningDate' => date("Y-m-d H:i:s"),
                                'status' => "1",
                                'modifyDate' => date("Y-m-d H:i:s")
                            );

                            $groupMemberRow = $groupMemberTable->createRow($data);
                            $groupMemberRow->save();
                            $response = array(
                                'response_string' => 'Group created successfully',
                                'error_code' => '0',
                                'members' => count($memberNames) ? implode(",", $memberNames) : "",
                                'response_error_key' => '0',
                                'groupImage' => ($groupRow->groupImage) ? $this->makeUrl($groupRow->groupImage) : "",
                                'groupName' => $groupRow->groupName,
                                'quick_blox_group_id' => ($groupRow->quick_blox_group_id) ? $groupRow->quick_blox_group_id : "",
                                'room_jid' => ($groupRow->room_jid) ? $groupRow->room_jid : "",
                                'result' => $reponseData
                            );
                            echo json_encode($response);
                            die();
                        } else {
                            $this->common->displayMessage("There is some problem while creating group", "1", array(), "1");
                        }
                    }
                } else {
                    $this->common->displayMessage('Please select min 2 users', "1", array(), "2");
                }
            } else {
                $this->common->displayMessage('User account is not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for edit user
     */
    public function editGroupAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();

        $decoded = $this->common->Decoded();
        $userSecurity = $this->getRequest()->getPost('userSecurity', '');
        $userId = $this->getRequest()->getPost('userId', '');
        $userDeviceId = $this->getRequest()->getPost('userDeviceId', '');
        $quick_blox_group_id = $this->getRequest()->getPost('quick_blox_group_id', '');
        $groupName = $this->getRequest()->getPost('groupName', '');
        $groupImage = $_FILES['groupImage'];
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $quick_blox_group_id));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {

                if ($groupRow = $groupTable->getRowById($quick_blox_group_id)) {
                    if ($groupName) {
                        $groupRow->groupName = $groupName;
                    }

                    if (is_array($groupImage)) {
                        $response = $this->common->uploadImage($groupImage);

                        if (isset($response['new_file_name'])) {
                            $file_path = "/images/" . $response['new_file_name'];
                            $groupRow->groupImage = $file_path;
                            $groupRow->save();
                        }
                    }

                    $groupRow->modifyDate = date("Y-m-d H:i:s");
                    $groupRow->save();

                    $reponseData = array(
                        'quick_blox_group_id' => $quick_blox_group_id,
                        'groupName' => $groupRow->groupName,
                        'groupImage' => ($groupRow->groupImage) ? $this->makeUrl($groupRow->groupImage) : "",
                    );

                    $pushData = array(
                        'type' => 'edit_group',
                        'quick_blox_group_id' => $quick_blox_group_id
                    );
                    $this->sendCurlNotification($pushData);
                    $this->common->displayMessage('Group details updated successfully', "0", $reponseData, "0");
                } else {
                    $this->common->displayMessage('Group id is not correct', "1", array(), "4");
                }
            } else {
                $this->common->displayMessage('User account is not exist', "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    /**
     *  function for Deling new users to group
     */
    public function addMemberAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $groupId = $decoded['groupId'];
        $quick_blox_group_id = $decoded['quick_blox_group_id'];
        $requestMemberSet = $decoded['requestMemberSet'];
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $quick_blox_group_id, $requestMemberSet));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {

                if ($groupRow = $groupTable->getRowById($quick_blox_group_id)) {
                    if ($groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($userId, $quick_blox_group_id)) {
                        if (count($requestMemberSet)) {
                            foreach ($requestMemberSet as $requestMemberId) {
                                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                                    if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($requestMemberId, $groupRow->quick_blox_group_id)) {
                                        if ($groupMemberRow->status == Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                            $groupMemberRow->status = Application_Model_DbTable_GroupMembers::MEMBER_ACTIVE;

                                            $groupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                            $groupMemberRow->save();
                                        }
                                    } else {
                                        $data = array(
                                            'memberId' => $requestMemberId,
                                            'quick_blox_group_id' => $groupRow->quick_blox_group_id,
                                            'joiningDate' => date("Y-m-d H:i:s"),
                                            'status' => "1",
                                            'modifyDate' => date("Y-m-d H:i:s")
                                        );

                                        $groupMemberRow = $groupMemberTable->createRow($data);
                                        $groupMemberRow->save();
                                    }
                                }

                                $groupRow->modifyDate = date("Y-m-d H:i:s");
                                $groupRow->save();
                                $countGroupMembers = count($groupMemberTable->getGroupMembers($groupRow->quick_blox_group_id, true));
                                if ($countGroupMembers == "1") {
                                    $groupRow->adminId = $requestMemberId;
                                    $groupRow->save();
                                }

                                $this->sendAddGroupNotification($quick_blox_group_id, $requestMemberId);
                            }

                            $pushData = array(
                                'type' => 'edit_group',
                                'quick_blox_group_id' => $quick_blox_group_id
                            );
                            $this->sendCurlNotification($pushData);

                            $this->common->displayMessage("Members added successfully", "0", array(), "0");
                        } else {
                            $this->common->displayMessage("Select atleast one user", "1", array(), "5");
                        }
                    } else {
                        $this->common->displayMessage('You are not admin of this group', "1", array(), "6");
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

    public function addMember1Action() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $groupId = $decoded['groupId'];

        $requestMemberSet = $decoded['requestMemberSet'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $groupId, $requestMemberSet));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {
                if ($groupRow = $groupTable->getRowById($groupId)) {
                    $groupId = $groupRow->groupId;
                    if ($groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($userId, $groupId)) {
                        if (count($requestMemberSet)) {
                            foreach ($requestMemberSet as $requestMemberId) {
                                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                                    if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($requestMemberId, $groupRow->groupId)) {
                                        if ($groupMemberRow->status == Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                            $groupMemberRow->status = Application_Model_DbTable_GroupMembers::MEMBER_ACTIVE;

                                            $groupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                            $groupMemberRow->save();
                                        }
                                    } else {
                                        $data = array(
                                            'memberId' => $requestMemberId,
                                            'groupId' => $groupRow->groupId,
                                            'joiningDate' => date("Y-m-d H:i:s"),
                                            'status' => "1",
                                            'modifyDate' => date("Y-m-d H:i:s")
                                        );

                                        $groupMemberRow = $groupMemberTable->createRow($data);
                                        $groupMemberRow->save();
                                    }
                                }

                                $groupRow->modifyDate = date("Y-m-d H:i:s");
                                $groupRow->save();

                                $countGroupMembers = count($groupMemberTable->getGroupMembers($groupRow->groupId, true));
                                if ($countGroupMembers == "1") {
                                    $groupRow->adminId = $requestMemberId;
                                    $groupRow->save();
                                }
                            }

                            $this->common->displayMessage("Members added successfully", "0", array(), "0");
                        } else {
                            $this->common->displayMessage("Select atleast one user", "1", array(), "5");
                        }
                    } else {
                        $this->common->displayMessage('You are not admin of this group', "1", array(), "6");
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

    /**
     * function for deleting users from group Note : Admin can delete to any member but user can delete to him self only
     */
    public function deleteMemberAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $memberId = $decoded['memberId'];
        $quick_blox_group_id = $decoded['quick_blox_group_id'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $userId, $memberId, $quick_blox_group_id));
            if ($userRow = $userTable->getRowById($userId)) {
                if ($groupRow = $groupTable->getRowById($quick_blox_group_id)) {
                    if (($groupRow->adminId == $userId) || ($userId == $memberId)) {
                        if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($memberId, $quick_blox_group_id)) {
                            $array = (object) array('userEmail' => $userRow->userEmail);
                            $token = $this->quickBloxUserToken($array);
                             $arrData = array("pull_all"=>array('occupants_ids'=>array($userRow->quickBloxId)));
                             $this->updateUser($token, $groupRow->quick_blox_group_id,  json_encode($arrData));
                           // $this->deleteDialogFromQuickBlox($token, $groupRow->quick_blox_group_id);
                            $groupMemberRow->status = "2";
                            $groupMemberRow->modifyDate = date("Y-m-d H:i:s");
                            $groupMemberRow->save();

                            $groupRow->modifyDate = date("Y-m-d H:i:s");
                            $groupRow->save();

                            if (($groupRow->adminId == $memberId) && ($activeMemberRow = $groupMemberTable->getOneActiveMember($quick_blox_group_id))) {
                                $groupRow->adminId = $activeMemberRow->userId;
                                $groupRow->save();
                            }

                            if (!count($groupMemberTable->getGroupMembers($quick_blox_group_id, true))) {
                                $groupRow->status = "2";
                                $groupRow->save();
                            }

                            $pushData = array(
                                'type' => 'edit_group',
                                'quick_blox_group_id' => $quick_blox_group_id
                            );

                            $this->sendLeaveGroupNotification($quick_blox_group_id, $memberId);
                            $this->sendCurlNotification($pushData);

                            $this->common->displayMessage('Member deleted successfully', "0", array(), "0");
                        } else {
                            $this->common->displayMessage('Member id is not correct', "1", array(), "6");
                        }
                    } else {
                        $this->common->displayMessage('You are not admin of this group', "1", array(), "5");
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

    /**
     *  function for getting all groups name with members names in string formate
     */
    public function groupsWithMembersNameAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $last_request_time = isset($decoded['last_request_time']) ? $decoded['last_request_time'] : '';
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId));
            $this->common->isUserLogin($userId, $userDeviceId);

            if ($userRow = $userTable->getRowById($userId)) {

                $groupsDetails = array();  // consist details of groups and its member

                $select = $groupTable->userGroups($userId, true);

                if ($last_request_time) {
                    $select = $select->where('groups.modifyDate >?', $last_request_time);

                    $deletedGroupRowset = $groupTable->deletedGroupsList($last_request_time);
                    $groupsLeftByUsers = $groupMemberTable->groupLeftByUser($userId, $last_request_time);
                    $deletedGroupsIds = array();

                    foreach ($deletedGroupRowset as $deletedGroupRow) {
                        $deletedGroupsIds[] = $deletedGroupRow->groupId;
                    }

                    foreach ($groupsLeftByUsers as $groupLeftRow) {
                        $deletedGroupsIds[] = $groupLeftRow->groupId;
                    }
                }

                $groupsRowset = $groupTable->fetchAll($select);

                foreach ($groupsRowset as $groupRow) {
                    $membersSet = array();
                    $membersDetails = array();
                    $memberNames = array();
                    $membersRowset = $groupMemberTable->getGroupMembers($groupRow->groupId, $last_request_time);

                    foreach ($membersRowset as $memberRow) {
                        $memberNames[] = $memberRow->userNickName;
                    }

                    $data = array(
                        'groupId' => $groupRow->groupId,
                        'is_admin' => ($groupRow->adminId == $userId) ? "1" : "0",
                        'adminId' => ($groupRow->adminId) ? $groupRow->adminId : "",
                        'groupName' => ($groupRow->groupName) ? $groupRow->groupName : "",
                        'groupImage' => ($groupRow->groupImage) ? $this->makeUrl($groupRow->groupImage) : "",
                        'creationDate' => $groupRow->creationDate,
                        'modifyDate' => $groupRow->modifyDate,
                        'members' => count($memberNames) ? implode(",", $memberNames) : "",
                        'totalMembers' => count($memberNames),
                        'quick_blox_group_id' => ($groupRow->quick_blox_group_id) ? $groupRow->quick_blox_group_id : "",
                        'room_jid' => ($groupRow->room_jid) ? $groupRow->room_jid : ""
                    );
                    $groupsDetails[] = $data;
                }

                $responseData = array(
                    'response_string' => 'Groups with members name',
                    'error_code' => '0',
                    'response_error_key' => '0',
                    'last_request_time' => date("Y-m-d H:i:s"),
                    'result' => $groupsDetails,
                    'deleted_groups_ids' => count($deletedGroupsIds) ? $deletedGroupsIds : array()
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

    public function groupMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $quick_blox_group_id = $decoded['quick_blox_group_id'];
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $userId, $quick_blox_group_id));
            $this->common->isUserLogin($userId, $userDeviceId);
            $last_request_time = isset($decoded['last_request_time']) ? $decoded['last_request_time'] : '';

            if ($userRow = $userTable->getRowById($userId)) {
                if ($groupRow = $groupTable->getRowById($quick_blox_group_id)) {
                    $newMembersList = array();
                    $deleteMembersList = array();
                    $newMembersRowset = $groupMemberTable->newMembersList($quick_blox_group_id, $last_request_time);
                    $deleteMembersRowset = $groupMemberTable->deletedMembersList($quick_blox_group_id, $last_request_time);
                    foreach ($newMembersRowset as $newMemberRow) {
                        $newMembersList[] = $newMemberRow->memberId;
                    }

                    foreach ($deleteMembersRowset as $deleteMemberRow) {
                        $deleteMembersList[] = $deleteMemberRow->memberId;
                    }


                    $membersData = array();

                    foreach ($newMembersRowset as $groupMemberRow) {
                        $membersData[] = array(
                            'memberRowId' => $groupMemberRow->id,
                            'memberId' => $groupMemberRow->memberId,
                            'memberName' => ($groupMemberRow->userNickName) ? $groupMemberRow->userNickName : "",
                            'memberImage' => ($groupMemberRow->userImage) ? $this->makeUrl($groupMemberRow->userImage) : "",
                            'quickBloxId' => ($groupMemberRow->quickBloxId) ? $groupMemberRow->quickBloxId : ""
                        );
                    }

                    $response = array(
                        'response_string' => 'Group members list',
                        'error_code' => '0',
                        'response_error_key' => '0',
                        'adminId' => ($groupRow->adminId) ? $groupRow->adminId : "",
                        'groupImage' => ($groupRow->groupImage) ? $this->makeUrl($groupRow->groupImage) : "",
                        'groupName' => ($groupRow->groupName) ? $groupRow->groupName : "",
                        'last_request_time' => strtotime(date("Y-m-d H:i:s")),
                        'quick_blox_group_id' => ($groupRow->quick_blox_group_id) ? $groupRow->quick_blox_group_id : "",
                        'room_jid' => ($groupRow->room_jid) ? $groupRow->room_jid : "",
                        'new_members_list' => $newMembersList,
                        'delete_members_list' => $deleteMembersList,
                        'result' => $membersData
                    );
                    echo json_encode($response);
                    exit;
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

    /**
     *  function for add and delete members
     */
    public function addDeleteMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $decoded = $this->common->Decoded();
        $this->printJson(json_encode($decoded));
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $quick_blox_group_id = $decoded['quick_blox_group_id'];
        $addMemberSet = $decoded['addMemberSet'];
        $deleteMemberSet = $decoded['deleteMemberSet'];
        $addQuickBloxId = array();
        $deleteQuickBloxId = array();

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $quick_blox_group_id));

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {
                $this->common->isUserLogin($userId, $userDeviceId);
                if ($groupRow = $groupTable->getRowById($quick_blox_group_id)) {
                    foreach ($addMemberSet as $addMemberId) {
                        $requestUserRow=$userTable->getRowById($addMemberId);
                        $addQuickBloxId[]=$requestUserRow->quickBloxId;
                        if (($requestUserRow) && ($requestUserRow->userStatus == "1")) {
                            if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($addMemberId, $quick_blox_group_id)) {
                                if ($groupMemberRow->status == Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                    $groupMemberRow->status = Application_Model_DbTable_GroupMembers::MEMBER_ACTIVE;

                                    $groupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                    $groupMemberRow->save();
                                }
                            } else {
                                $data = array(
                                    'memberId' => $addMemberId,
                                    'quick_blox_group_id' => $quick_blox_group_id,
                                    'joiningDate' => date("Y-m-d H:i:s"),
                                    'status' => "1",
                                    'modifyDate' => date("Y-m-d H:i:s")
                                );

                                $groupMemberRow = $groupMemberTable->createRow($data);
                                $groupMemberRow->save();
                                
                            }
                        }
                        
                        $groupRow->modifyDate = date("Y-m-d H:i:s");
                        $groupRow->save();
                    }

                    if ($groupRow->adminId == $userId) {
                        foreach ($deleteMemberSet as $deleteMemberId) {
                            $requestUserRow=$userTable->getRowById($deleteMemberId);
                            $deleteQuickBloxId[]=$requestUserRow->quickBloxId;
                            if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndQuickBloxGroupId($deleteMemberId, $quick_blox_group_id)) {
                                if ($groupMemberRow->status != Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                    $groupMemberRow->status = "2";
                                    $groupMemberRow->modifyDate = date("Y-m-d H:i:s");
                                    $groupMemberRow->save();
                                    $groupRow->modifyDate = date("Y-m-d H:i:s");
                                    $groupRow->save();
                                }
                            }
                        }

                        $countGroupMembers = count($groupMemberTable->getGroupMembers($quick_blox_group_id, true));

                        if (!$countGroupMembers) {
                            $groupRow->status = "2";
                            $groupRow->save();
                        }
                    }

                    $select = $groupMemberTable->select()->setIntegrityCheck(false)
                            ->from('groupMembers', array("*"))
                            ->joinLeft('users', "groupMembers.memberId = users.userId", array('*'))
                            ->where('groupMembers.quick_blox_group_id =?', $quick_blox_group_id)
                            ->where('users.userStatus =?', "1")
                            ->Where('groupMembers.status=?', "1");

                    $groupMemberRowset = $groupMemberTable->fetchAll($select);
                    $array = (object) array('userEmail' => $userRow->userEmail);
                    $token = $this->quickBloxUserToken($array);
                    if(count($addQuickBloxId)>0){
                        $arrData = array("push_all"=>array('occupants_ids'=>$addQuickBloxId));
                        $this->updateUser($token, $quick_blox_group_id,  json_encode($arrData));
                    }
                    if(count($deleteQuickBloxId)>0){
                        $arrData = array("pull_all"=>array('occupants_ids'=>$deleteQuickBloxId));
                        $this->updateUser($token, $quick_blox_group_id,  json_encode($arrData));
                    }
                    $membersData = array();

                    foreach ($groupMemberRowset as $groupMemberRow) {
                        $membersData[] = array(
                            'quickBloxId' => ($groupMemberRow->quickBloxId) ? $groupMemberRow->quickBloxId : "",
                            'memberRowId' => $groupMemberRow->id,
                            'memberId' => $groupMemberRow->memberId,
                            'memberName' => ($groupMemberRow->userFullName) ? $groupMemberRow->userFullName : "",
                            'memberImage' => ($groupMemberRow->userImage) ? $this->makeUrl($groupMemberRow->userImage) : "",
                        );
                    }

                    $pushData = array(
                        'type' => 'edit_group',
                        'groupId' => $groupId
                    );

                    $this->sendCurlNotification($pushData);

                    $pushData = array(
                        'type' => 'add_delete',
                        'quick_blox_group_id' => $quick_blox_group_id,
                        'add_member_set' => $addMemberSet,
                        'delete_member_set' => (($groupRow->adminId == $userId) && count($deleteMemberSet)) ? $deleteMemberSet : array()
                    );

                    $this->sendCurlNotification($pushData);

                    $this->common->displayMessage('Member added/deleted successfully', "0", $membersData, "0");
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

    /**
     * send edit group notification to all members
     */
    public function testAction() {
        $data = array(
            'type' => 'edit_group',
            'groupId' => "4"
        );

        $this->sendCurlNotification($data);
        exit;
    }

    public function sendCurlNotification($data) {

        $url = $this->baseUrl . "/groups/get-curl-url";

        $ch = curl_init();
        $u = curl_setopt($ch, CURLOPT_URL, $url);
        $p = curl_setopt($ch, CURLOPT_POST, true);
        $f = curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $h = curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $t = curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $c = curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $j = curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //      $jsonn = json_encode($fields);
        $result = curl_exec($ch);
    }

    public function getCurlUrlAction() {
        $notification_type = $this->getRequest()->getPost('type');


        if ($notification_type == "edit_group") {

            if ($groupId = $this->getRequest()->getPost('quick_blox_group_id')) {
                $this->sendGroupEditNotification($groupId);
            }
        } else if ($notification_type == "add_delete") {

            if ($quick_blox_group_id = $this->getRequest()->getPost('quick_blox_group_id', '')) {

                $add_member_set = $this->getRequest()->getPost('add_member_set', '');
                $delete_member_set = $this->getRequest()->getPost('delete_member_set', '');

                if (count($add_member_set)) {
                    foreach ($add_member_set as $add_member_id) {
                        $this->sendAddGroupNotification($groupId, $add_member_id);
                    }
                }

                if (count($delete_member_set)) {
                    foreach ($delete_member_set as $delete_member_id) {
                        $this->sendLeaveGroupNotification($quick_blox_group_id, $delete_member_id);
                    }
                }
            }
        } else {
            
        }
    }

    public function sendGroupEditNotification($groupId) {

        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        if ($groupRow = $groupTable->getRowById($groupId)) {
            $groupMemberRowset = $groupMemberTable->getGroupMembers($groupId, true);

            foreach ($groupMemberRowset as $groupMemberRow) {

                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($groupMemberRow->userId);

                $message = "";

                foreach ($userLoginDeviceRowset as $loginDeviceRow) {
                    if ($loginDeviceRow->userDeviceType != "web") {
                        if ($loginDeviceRow->userDeviceType == "iphone") {
                            $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'edit_group', 'group_id' => $groupId);
                        } else {
                            $payload = array(
                                'message' => $message,
                                'type' => "edit_group",
                                'result' => array('quick_blox_group_id' => $groupId)
                            );
                            $payload = json_encode($payload);
                        }
                        $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
                    }
                }
            }
        }
    }

    /**
     * send edit group notification to all members
     */
    public function sendLeaveGroupNotification($quick_blox_group_id, $member_id) {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($member_id);

        $message = "";

        foreach ($userLoginDeviceRowset as $loginDeviceRow) {
            if ($loginDeviceRow->userDeviceType != "web") {
                if ($loginDeviceRow->userDeviceType == "iphone") {
                    $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'leave_group', 'quick_blox_group_id' => $groupId);
                } else {
                    $payload = array(
                        'message' => $message,
                        'type' => "leave_group",
                        'result' => array('quick_blox_group_id' => $quick_blox_group_id)
                    );
                    $payload = json_encode($payload);
                }

                $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
            }
        }
    }

    public function sendAddGroupNotification($groupId, $member_id) {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($member_id);

        $message = "";

        foreach ($userLoginDeviceRowset as $loginDeviceRow) {
            if ($loginDeviceRow->userDeviceType != "web") {
                if ($loginDeviceRow->userDeviceType == "iphone") {
                    $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'add_group', 'group_id' => $groupId);
                } else {
                    $payload = array(
                        'message' => $message,
                        'type' => "add_group",
                        'result' => array('group_id' => $groupId)
                    );
                    $payload = json_encode($payload);
                }

                $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
            }
        }
    }

}

?>