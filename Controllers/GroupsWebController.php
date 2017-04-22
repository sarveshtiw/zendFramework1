 <?php

class GroupsWebController extends My_Controller_Abstract {

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
        $userId = $this->loggedUserRow->userId;
        $groupName = $this->getRequest()->getPost('groupName', '');
        $description = $this->getRequest()->getPost('description', '');
        $groupImage = $_FILES['groupImage'];
        $requestMemberSet = $this->getRequest()->getPost('requestMemberSet', '');
        if ($userRow = $userTable->getRowById($userId)) {
            $a = 1;
            $b = 2;
            if ($a == $b) {
                $this->common->displayMessage('User already have one group with this name', "1", array(), "4");
            } else {
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

                if (is_array($groupImage)) {
                    $response = $this->common->uploadImage($groupImage);

                    if (isset($response['new_file_name'])) {
                        $file_path = "/images/" . $response['new_file_name'];
                        $groupRow->groupImage = $file_path;
                        $groupRow->save();
                    }
                }

                $reponseData = array();
                $groupMembersQuickBloxId = array();

                $memberNames = array();

                $memberNames[] = $userRow->userNickName;

                foreach ($requestMemberSet as $requestMemberId) {
                    if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                        if (!$groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $groupRow->groupId)) {
                            $data = array(
                                'memberId' => $requestMemberId,
                                'groupId' => $groupRow->groupId,
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
                    'groupId' => $groupRow->groupId,
                    'joiningDate' => date("Y-m-d H:i:s"),
                    'status' => "1",
                    'modifyDate' => date("Y-m-d H:i:s")
                );

                $groupMemberRow = $groupMemberTable->createRow($data);
                $groupMemberRow->save();

                if ($userRow->quickBloxId) {
                    $groupMembersQuickBloxId[] = $userRow->quickBloxId;
                }

                if (count($groupMembersQuickBloxId)) {
                    if ($quick_blox_response = $this->createQuickBloxSimpleGroup($userId, $groupRow->groupName, $groupMembersQuickBloxId, false)) {
                        if (isset($quick_blox_response['_id'])) {
                            $groupRow->quick_blox_group_id = $quick_blox_response['_id'];
                            $groupRow->room_jid = $quick_blox_response['xmpp_room_jid'];
                            $groupRow->save();
                        }
                    }   
                }
                $messageData = array(
                    'name' => $groupName,
                    'groupServerId' => $groupRow->groupId,
                    'image' => ($groupRow->groupImage) ? $this->makeUrl($groupRow->groupImage) : "",
                    'occupants_ids' => implode(",", $groupMembersQuickBloxId),
                    'group_id' => ($groupRow->quick_blox_group_id) ? $groupRow->quick_blox_group_id : "",
                    'room_jid' => ($groupRow->room_jid) ? $groupRow->room_jid : "",
                    'notification_type' => 1,
                    'save_to_history' => 1,
                    'send_to_chat'=>1,
                    'userEmail' => $this->loggedUserRow->userEmail,
                    'login_id' => $this->loggedUserRow->quickBloxId,
                    'message' => $this->loggedUserRow->userNickName . ' created this group',
                );
                $this->sendCurlAsyncRequest("/groups-web/send-qb-notification", $messageData);
                $this->_helper->FlashMessenger->addMessage('Group created successfully', 'group_create');
                $this->_redirect('/chat?groupId=' . $groupRow->groupId);
            }
        } else {
            $this->common->displayMessage('User account is not exist', "1", array(), "2");
        }
    }

    /*
     * get group details by ajax for edit group
     */

    public function getGroupDetailsAction() {
        $this->_helper->layout()->disableLayout();
        //$this->checkValidRequest();
        $friendTable = new Application_Model_DbTable_Friends();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $groupTable = new Application_Model_DbTable_Groups();
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);
        $groupId = $this->getRequest()->getParam('id', 0);
        $type = $this->getRequest()->getParam('type', 1);
        if ($type == 2) {
//            $groupMemberId = $groupMemberTable->getMemberIds($groupId);
//            $groupDetails = $groupTable->getGroupDetails($groupId);
//            $isAdmin = ($groupDetails->adminId == $this->loggedUserRow->userId) ? true : false;
//            $this->view->groupMemberId = $groupMemberId;
//            $this->view->groupDetails = $groupDetails;
//            $this->view->isAdmin = $isAdmin;
        }
        $this->view->userList = $paginator;
        switch($type){
            case 1 :
                $this->renderScript('groups-web/create-group.phtml');
            break;
            case 2 :
                $groupMemberId = $groupMemberTable->getMemberIds($groupId);
                $groupDetails = $groupTable->getGroupDetails($groupId);
                $isAdmin = ($groupDetails->adminId == $this->loggedUserRow->userId) ? true : false;
                $this->view->groupMemberId = $groupMemberId;
                $this->view->groupDetails = $groupDetails;
                $this->view->isAdmin = $isAdmin;
                $this->renderScript('groups-web/edit-group.phtml');
            break;
            case 3 :
                $this->renderScript('groups-web/create-secret-group.phtml');
            break;
            case 4 :
                $secretGroupTable = new Application_Model_DbTable_SecretGroups();
                $secretGroupMember = new Application_Model_DbTable_SecGroupMembers();
                $userId = $this->loggedUserRow->userId;
                $groupMemberId = $secretGroupMember->getMemberIds($groupId);
                //print_r($groupMemberId);
                $groupDetails = $secretGroupTable->getGroupAllDetails($groupId,$userId);
                $isAdmin = ($groupDetails->adminId == $this->loggedUserRow->userId) ? true : false;
                $this->view->groupMemberId = $groupMemberId;
                $this->view->groupDetails = $groupDetails;
                $this->view->isAdmin = $isAdmin;
                $this->renderScript('groups-web/edit-secret-group.phtml');
            break;
        }
        }
   

    /**
     *  function for edit group
     */
    public function editGroupsAction() {
        $groupTable = new Application_Model_DbTable_Groups();
        $groupId = $this->getRequest()->getPost('groupId', '');
        $groupName = $this->getRequest()->getPost('groupName', '');
        $removeUser = $this->getRequest()->getPost('remove_user', '');
        $addUsers=$this->getRequest()->getPost('requestMemberSet');
        $groupImage = $_FILES['groupImage'];
        $groupRow = $groupTable->getRowById($groupId);
        //print_r($groupRow);die();
        if ($groupRow->adminId == $this->loggedUserRow->userId) {
            ($groupName) ? $groupRow->groupName = $groupName : "";
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
            $pushData = array('type' => 'edit_group', 'groupId' => $groupId);
            $this->sendCurlAsyncRequest('/groups-web/add-member', array('userId'=>$this->loggedUserRow->userId,'groupId' => $groupId, 'requestMemberSet' => json_encode($addUsers)));
            $this->sendCurlNotification($pushData);
            if ($groupRow->adminId == $this->loggedUserRow->userId) {
                if (count($removeUser) > 0) {
                    echo count($removeUser);
                    $this->sendCurlAsyncRequest('/groups-web/delete-member', array('userId'=>$this->loggedUserRow->userId,'groupId' => $groupId, 'requestMemberSet' => json_encode($removeUser)));
                }
            }
            //die();
            $this->_helper->FlashMessenger->addMessage('Group updated successfully', 'group_create');
            $this->_redirect('/chat?groupId=' . $groupRow->groupId);
        }
        $this->_redirect('/chat?groupId=' . $groupRow->groupId);
    }

    /**
     *  function for adding new users to group
     */
    public function addMemberAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $groupId = $this->getRequest()->getPost('groupId');
        $requestMemberSet = $this->getRequest()->getPost('requestMemberSet');
        $requestMemberSet = json_decode($requestMemberSet);
        if ($groupRow = $groupTable->getRowById($groupId)) {
            if (count($requestMemberSet)) {
                foreach ($requestMemberSet as $requestMemberId) {
                    if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                        if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $groupRow->groupId)) {
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

                    $this->sendAddGroupNotification($groupId, $requestMemberId);
                }

                $pushData = array(
                    'type' => 'edit_group',
                    'groupId' => $groupId
                );
                $this->sendCurlNotification($pushData);
            } else {
                $this->common->displayMessage("Select atleast one user", "1", array(), "5");
            }
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
                    if ($groupMemberTable->getRowByMemberIdAndGroupId($userId, $groupId)) {
                        if (count($requestMemberSet)) {
                            foreach ($requestMemberSet as $requestMemberId) {
                                if (($requestUserRow = $userTable->getRowById($requestMemberId)) && ($requestUserRow->userStatus == "1")) {
                                    if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($requestMemberId, $groupRow->groupId)) {
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
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $memberIds = $this->getRequest()->getPost('requestMemberSet');
        $groupId = $this->getRequest()->getPost('groupId');
	$userId = $this->getRequest()->getPost('userId');
        $groupRow = $groupTable->getRowById($groupId);
	if ($groupRow->adminId==$userId) {
            $memberIds=json_decode($memberIds);
            foreach($memberIds as $memberId){
                $groupMemberTable->updateData(array('status' => 2, 'modifyDate' => date("Y-m-d H:i:s")), "memberId='{$memberId}' AND groupId='{$groupId}'");
                $groupRow->modifyDate = date("Y-m-d H:i:s");
                $groupRow->save();
                $pushData = array('type' => 'edit_group','groupId' => $groupId);
                $this->sendLeaveGroupNotification($groupId, $memberId);
                $this->sendCurlNotification($pushData);
          }
        }

    }

    /**
     *  function for getting all groups name with members names in string formate
     */
    public function getGroupListAction() {
        $this->checkValidRequest();
        $groupTable = new Application_Model_DbTable_Groups();
        $userId = $this->loggedUserRow->userId;
        $select = $groupTable->userGroups($userId, true);
        $groupsRowset = $groupTable->fetchAll($select);
        $this->view->groupList = $groupsRowset;
    }
    /*
     * check request is valid for ajax request action
     */
    function checkValidRequest(){
        $refere_url = $_SERVER['HTTP_REFERER'];
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
            if (strpos($refere_url,'wa-app.com') !== false) {
                return true;
            }else{
                 echo json_encode(array("errorMessage"=>"Invalid Request"));
                die();  
            }
        }else{
            echo json_encode(array("errorMessage"=>"Invalid Request"));
            die();
        }
    }
    public function groupMembersAction() {
        $userTable = new Application_Model_DbTable_Users();
        $groupTable = new Application_Model_DbTable_Groups();
        $groupMemberTable = new Application_Model_DbTable_GroupMembers();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $groupId = $decoded['groupId'];

        /*  $userId = 577;
          $groupId = 5;
          $userDeviceId = "352742062340774";
          $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
         */

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $userId, $groupId));
            $this->common->isUserLogin($userId, $userDeviceId);
            $last_request_time = isset($decoded['last_request_time']) ? $decoded['last_request_time'] : '';

            if ($userRow = $userTable->getRowById($userId)) {
                if ($groupRow = $groupTable->getRowById($groupId)) {
                    $groupId = $groupRow->groupId;

                    $newMembersList = array();
                    $deleteMembersList = array();

                    $newMembersRowset = $groupMemberTable->newMembersList($groupId, $last_request_time);
                    $deleteMembersRowset = $groupMemberTable->deletedMembersList($groupId, $last_request_time);

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
                            'is_online' => $userSettingTable->isUserOnline($groupMemberRow->memberId),
                            'quickBloxId' => ($groupMemberRow->quickBloxId) ? $groupMemberRow->quickBloxId : ""
                        );
                    }

                    $response = array(
                        'response_string' => 'Group members list',
                        'error_code' => '0',
                        'response_error_key' => '0',
                        'groupId' => $groupRow->groupId,
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

        /*     $decoded = array(
          'userSecurity' => 'afe2eb9b1de658a39e896591999e1b59',
          'userId'       => 16,
          'groupId'      => '548e89779a686954c9006a0b',
          'userDeviceId'  => "D531D6DF-2662-4D17-B26B-990D5C17539B",
          'addMemberSet'  => array(),
          'deleteMemberSet' => array(32)
          );
         */
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $groupId = $decoded['groupId'];
        $addMemberSet = $decoded['addMemberSet'];
        $deleteMemberSet = $decoded['deleteMemberSet'];

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId, $userDeviceId, $groupId));

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {
                $this->common->isUserLogin($userId, $userDeviceId);

                if ($groupRow = $groupTable->getRowById($groupId)) {

                    $groupId = $groupRow->groupId;

                    foreach ($addMemberSet as $addMemberId) {
                        if (($requestUserRow = $userTable->getRowById($addMemberId)) && ($requestUserRow->userStatus == "1")) {
                            if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($addMemberId, $groupId)) {
                                if ($groupMemberRow->status == Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                    $groupMemberRow->status = Application_Model_DbTable_GroupMembers::MEMBER_ACTIVE;

                                    $groupMemberRow->modifyDate = date('Y-m-d H:i:s');
                                    $groupMemberRow->save();
                                }
                            } else {
                                $data = array(
                                    'memberId' => $addMemberId,
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
                    }

                    if ($groupRow->adminId == $userId) {
                        foreach ($deleteMemberSet as $deleteMemberId) {
                            if ($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($deleteMemberId, $groupId)) {
                                if ($groupMemberRow->status != Application_Model_DbTable_GroupMembers::MEMBER_DELETED) {
                                    $groupMemberRow->status = "2";
                                    $groupMemberRow->modifyDate = date("Y-m-d H:i:s");
                                    $groupMemberRow->save();
                                    $groupRow->modifyDate = date("Y-m-d H:i:s");
                                    $groupRow->save();
                                }
                            }
                        }

                        $countGroupMembers = count($groupMemberTable->getGroupMembers($groupRow->groupId, true));

                        if (!$countGroupMembers) {
                            $groupRow->status = "2";
                            $groupRow->save();
                        }
                    }

                    $select = $groupMemberTable->select()->setIntegrityCheck(false)
                            ->from('groupMembers', array("*"))
                            ->joinLeft('users', "groupMembers.memberId = users.userId", array('*'))
                            ->where('groupMembers.groupId =?', $groupId)
                            ->where('users.userStatus =?', "1")
                            ->Where('groupMembers.status=?', "1");

                    $groupMemberRowset = $groupMemberTable->fetchAll($select);

                    $membersData = array();

                    foreach ($groupMemberRowset as $groupMemberRow) {
                        $membersData[] = array(
                            'quickBloxId' => ($groupMemberRow->quickBloxId) ? $groupMemberRow->quickBloxId : "",
                            'memberRowId' => $groupMemberRow->id,
                            'memberId' => $groupMemberRow->memberId,
                            'memberName' => ($groupMemberRow->userFullName) ? $groupMemberRow->userFullName : "",
                            'memberImage' => ($groupMemberRow->userImage) ? $this->makeUrl($groupMemberRow->userImage) : "",
                            'is_online' => $userSettingTable->isUserOnline($groupMemberRow->memberId)
                        );
                    }

                    $pushData = array(
                        'type' => 'edit_group',
                        'groupId' => $groupId
                    );

                    $this->sendCurlNotification($pushData);

                    $pushData = array(
                        'type' => 'add_delete',
                        'groupId' => $groupId,
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

            if ($groupId = $this->getRequest()->getPost('groupId')) {
                $this->sendGroupEditNotification($groupId);
            }
        } else if ($notification_type == "add_delete") {

            if ($groupId = $this->getRequest()->getPost('groupId', '')) {

                $add_member_set = $this->getRequest()->getPost('add_member_set', '');
                $delete_member_set = $this->getRequest()->getPost('delete_member_set', '');

                if (count($add_member_set)) {
                    foreach ($add_member_set as $add_member_id) {
                        $this->sendAddGroupNotification($groupId, $add_member_id);
                    }
                }

                if (count($delete_member_set)) {
                    foreach ($delete_member_set as $delete_member_id) {
                        $this->sendLeaveGroupNotification($groupId, $delete_member_id);
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
                                'result' => array('group_id' => $groupId)
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
    public function sendLeaveGroupNotification($groupId, $member_id) {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($member_id);

        $message = "";

        foreach ($userLoginDeviceRowset as $loginDeviceRow) {
            if ($loginDeviceRow->userDeviceType != "web") {
                if ($loginDeviceRow->userDeviceType == "iphone") {
                    $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'leave_group', 'group_id' => $groupId);
                } else {
                    $payload = array(
                        'message' => $message,
                        'type' => "leave_group",
                        'result' => array('group_id' => $groupId)
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

    function sendCurlAsyncRequest($url, $body) {
        $url = $this->baseUrl . $url;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, FALSE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        //curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10);
        $go = curl_exec($curl);
        $re = json_decode($go, true);
        curl_close($curl);
        return $re;
    }

    public function sendQbNotificationAction() {
        $data = $_POST;
        $occupentIds = explode(",", $data['occupants_ids']);
        $occupentIds = array_diff($occupentIds, array($data['login_id']));
        $array = (object) array('userEmail' => $data['userEmail']);
        $token = $this->quickBloxUserToken($array);
        unset($data['userEmail']);
        unset($data['login_id']);
        foreach ($occupentIds as $id) {
            $chat_dialog_id = $this->createDialogId($token, $id);
            $data['recipient_id'] = $id;
            $data['chat_dialog_id'] = $chat_dialog_id;
            $json_data = json_encode($data);
            $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Message.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );
            $result_json = curl_exec($ch);
            $result_arr = json_decode($result_json, true);
        }
        die();
    }

    /*   public function leaveGroupAction(){
      $userTable = new Application_Model_DbTable_Users();
      $groupTable = new Application_Model_DbTable_Groups();
      $groupMemberTable = new Application_Model_DbTable_GroupMembers();
      $userSettingTable = new Application_Model_DbTable_UserSetting();
      $groupMemberTable = new Application_Model_DbTable_GroupMembers();

      $decoded = $this->common->Decoded();
      $userSecurity = $decoded['userSecurity'];
      $userId = $decoded['userId'];
      $userDeviceId = $decoded['userDeviceId'];
      $groupId = $decoded['groupId'];

      if($userSecurity == $this->servicekey){
      $this->common->checkEmptyParameter1(array($userSecurity,$userId,$userDeviceId,$groupId));

      if($userRow = $userTable->getRowById($userId)){
      $this->common->isUserLogin($userId,$userDeviceId);

      if($groupRow = $groupTable->getRowById($groupId)){
      if($groupMemberRow = $groupMemberTable->getRowByMemberIdAndGroupId($userId,$groupId)){
      if($groupMemberRow->status != Application_Model_DbTable_GroupMembers::MEMBER_DELETED){
      $groupMemberRow->status = "2";
      $groupMemberRow->modifyDate = date("Y-m-d H:i:s");
      $groupMemberRow->save();

      $groupRow->modifyDate = date("Y-m-d H:i:s");
      $groupRow->save();

      }
      }

      if($groupRow->adminId == $userId){

      }

      $this->common->displayMessage("Group member deleted successfully","0",array(),"0");
      }else{
      $this->common->displayMessage("Group id is not correct","1",array(),"4");
      }

      }else{
      $this->common->displayMessage("User account is not exist","1",array(),"2");
      }

      }else{
      $this->common->displayMessage('You could not access this web-service',"1",array(),"3");
      }
      }

     */
}

?>
