<?php

/* this controller is for website
 */

class FriendsController extends My_Controller_Admin {

    public function init() {
        $this->_helper->layout->enableLayout();
        $this->view->menu = "FRIENDS";
    }

    public function indexAction() {
        $this->_helper->layout->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $this->view->countriesList = $countryTable->getCountriesList();
    }

    public function index1Action() {
        $this->_helper->layout->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $this->view->countriesList = $countryTable->getCountriesList();
    }

    public function friendsDataAction() {
        if ($this->getRequest()->getPost('type') == "approved") {
            $html = $this->view->action("accepted-friends", "friends", array());
        } else {
            $html = $this->view->action("pending-friends", "friends", array());
        }

        echo json_encode(array("html" => $html));
        exit;
    }

    public function acceptedFriendsAction() {

        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $page = $this->getRequest()->getParam('page', '1');
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        //$select = $friendTable->myfriendsWeb($this->loggedUserRow->userId, true);
        $searchText = $this->getRequest()->getPost("searchText", '');
        $alpha = $this->getRequest()->getPost("alpha", '');
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId, false, $searchText, false, $alpha);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(6);
        $totalPages = $paginator->getTotalItemCount() / 6;
        $viewMore = ($page < $totalPages) ? true : false;
        $this->view->viewMore = $viewMore;
        $this->view->friendsRowset = $paginator;
        //   $this->_helper->layout->disableLayout();
    }

    public function pendingFriendsAction() {

        $friendTable = new Application_Model_DbTable_Friends();
        $searchText = $this->getRequest()->getPost("searchText", '');
        $alpha = $this->getRequest()->getPost("alpha", '');
        $select = $friendTable->getPendingFriends($this->loggedUserRow->userId, true, $searchText, $alpha);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(6);
        $page = $this->getRequest()->getParam('page', '1');
        $totalPages = $paginator->getTotalItemCount() / 6;
        $viewMore = ($page < $totalPages) ? true : false;
        $this->view->viewMore = $viewMore;
        $this->view->pendingFriendsRowset = $paginator;
    }

    public function addAction() {
        $this->_helper->layout->setLayout('new_layout');
        $userTable = new Application_Model_DbTable_Users();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $accountSettingTable = new Application_Model_DbTable_AccountSetting();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();
        $search = trim($this->getRequest()->getQuery('search', ''));
        $this->view->search_type = $this->getRequest()->getParam('search_type', '');
        if ($this->loggedUserRow->userLatitude && $this->loggedUserRow->userLongitude) {
            $distance = "(((acos(sin((" . $this->loggedUserRow->userLatitude . "*pi()/180)) * sin((users.userLatitude*pi()/180))+cos((" . $this->loggedUserRow->userLatitude . "*pi()/180)) * cos((users.userLatitude*pi()/180)) * cos(((" . $this->loggedUserRow->userLongitude . "- users.userLongitude)* pi()/180))))*180/pi())*60*1.1515)";   // get distance in miles
            $select = $userTable->select()
                    ->from('users', array('*', 'distance' => $distance))
                    ->where('userStatus =?', Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('userId !=?', $this->loggedUserRow->userId);
        } else {
            $select = $userTable->select()
                    ->from('users', array('*'))
                    ->where('userStatus =?', Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('userId !=?', $this->loggedUserRow->userId);
        }

        $select_block_user = $blockUserTable->select()
                ->from('blockUsers', array("userId"))
                ->where('blockUserId =?', $this->loggedUserRow->userId)
                ->where('blockUsers.status =?', "1");

        $select_block_user_by_me = $blockUserTable->select()
                ->from('blockUsers', array("blockUserId"))
                ->where('userId =?', $this->loggedUserRow->userId)
                ->where('blockUsers.status =?', "1");

        $select->where('users.userId not in (' . $select_block_user . ')');

        $select->where('users.userId not in (' . $select_block_user_by_me . ')');


        if ($search) {

            $search_phone = $search;

            if ($search_phone[0] == "0") {
                $search_phone = substr($search_phone, 1);
            }

            $select_in_match_user_ids = "select userId from (
                        SELECT users.*,accountSetting.searchFullName,accountSetting.searchCompleteName,accountSetting.searchEmail,accountSetting.searchPhone,
                        SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName,  ' ', 1 ) ,  ' ', -1 ) AS first_name,

                        IF( LENGTH( users.userFullName ) - LENGTH( REPLACE( users.userFullName, ' ', '' ) ) >1,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName, ' ', 2 ) , ' ', -1 ) , NULL ) AS middle_name,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName, ' ', 3 ) , ' ', -1 ) AS last_name,

                        SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 4 ) ,  ' ', -1 ) AS comp_first_name,
                        IF( LENGTH( users.userNickName ) - LENGTH( REPLACE( users.userNickName,  ' ',  '' ) ) >1,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 5 ) ,  ' ', -1 ) , NULL ) AS comp_middle_name, 
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 6 ) ,  ' ', -1 ) AS comp_last_name

                        FROM users left join accountSetting on accountSetting.userId = users.userId
                         ) temp
                            WHERE (((searchFullName is null) or (searchFullName = '1')) and (LCASE(first_name) like '" . strtolower($search) . "%' or LCASE(middle_name) like '" . strtolower($search) . "%' or userFullName like '" . strtolower($search) . "%' or LCASE(last_name) like '" . strtolower($search) . "%'))
                                or (((searchCompleteName is null) or (searchCompleteName = '1')) and (LCASE(comp_first_name) like '" . strtolower($search) . "%' or LCASE(comp_middle_name) like '" . strtolower($search) . "%' or userNickName like '" . strtolower($search) . "%' or LCASE(comp_last_name) like '" . strtolower($search) . "%'))
                                or (((searchEmail is null) or (searchEmail = '1')) and (LCASE(userEmail) = '" . strtolower($search) . "' ))
                                or (((searchPhone is null) or (searchPhone = '1')) and (userPhone = '" . strtolower($search_phone) . "' or phoneWithCode ='" . $search_phone . "' ))";


            $select = $select->where('userId in (' . $select_in_match_user_ids . ')');
            $select->order("userNickName ASC");
            $paginatorParams = array(
                'search' => $search
            );

            Zend_Registry::set('paginatorParams', $paginatorParams);

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
            $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
            $paginator->setItemCountPerPage(6);
            $page = $this->getRequest()->getParam('page', '1');
            $totalPages = $paginator->getTotalItemCount() / 6;
            $viewMore = ($page < $totalPages) ? true : false;
            $this->view->viewMore = $viewMore;
            $this->view->userRowset = $paginator;
            $this->view->searchText = $search;
            $this->view->search_show=1;
        }
         $countryTable = new Application_Model_DbTable_Countries();
         $this->view->countriesList = $countryTable->getCountriesList();
    }

    public function add2Action() {
        // $this->_helper->layout->setLayout('new_layout');
        //{"name": "Chat with Garry and John", "pull_all": {"occupants_ids": [22]}}'
        $this->_helper->layout()->disableLayout();
        $userTable = new Application_Model_DbTable_Users();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();
        $search = trim($this->getRequest()->getPost('search', ''));
        $alpha = trim($this->getRequest()->getPost('alpha', ''));


        if ($this->loggedUserRow->userLatitude && $this->loggedUserRow->userLongitude) {
            $distance = "(((acos(sin((" . $this->loggedUserRow->userLatitude . "*pi()/180)) * sin((users.userLatitude*pi()/180))+cos((" . $this->loggedUserRow->userLatitude . "*pi()/180)) * cos((users.userLatitude*pi()/180)) * cos(((" . $this->loggedUserRow->userLongitude . "- users.userLongitude)* pi()/180))))*180/pi())*60*1.1515)";   // get distance in miles
            $select = $userTable->select()
                    ->from('users', array('*', 'distance' => $distance))
                    ->where('userStatus =?', Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('userId !=?', $this->loggedUserRow->userId);
        } else {
            $select = $userTable->select()
                    ->from('users', array('*'))
                    ->where('userStatus =?', Application_Model_DbTable_Users::STATUS_ACTIVE)
                    ->where('userId !=?', $this->loggedUserRow->userId);
        }

        $select_block_user = $blockUserTable->select()
                ->from('blockUsers', array("userId"))
                ->where('blockUserId =?', $this->loggedUserRow->userId)
                ->where('blockUsers.status =?', "1");

        $select_block_user_by_me = $blockUserTable->select()
                ->from('blockUsers', array("blockUserId"))
                ->where('userId =?', $this->loggedUserRow->userId)
                ->where('blockUsers.status =?', "1");

        $select->where('users.userId not in (' . $select_block_user . ')');

        $select->where('users.userId not in (' . $select_block_user_by_me . ')');


        if ($search) {

            $search_phone = $search;

            if ($search_phone[0] == "0") {
                $search_phone = substr($search_phone, 1);
            }
            if ($alpha == 1) {
                $select_in_match_user_ids = "select userId from (
                        SELECT users.*,accountSetting.searchFullName,accountSetting.searchCompleteName,accountSetting.searchEmail,accountSetting.searchPhone
                        FROM users left join accountSetting on accountSetting.userId = users.userId
                         ) temp
                            WHERE (((searchFullName is null) or (searchFullName = '1')) and (userFullName like '" . strtolower($search) . "%'))
                                or (((searchCompleteName is null) or (searchCompleteName = '1')) and (userNickName like '" . strtolower($search) . "%'))
                               ";
            } else {
                $select_in_match_user_ids = "select userId from (
                        SELECT users.*,accountSetting.searchFullName,accountSetting.searchCompleteName,accountSetting.searchEmail,accountSetting.searchPhone,
                        SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName,  ' ', 1 ) ,  ' ', -1 ) AS first_name,

                        IF( LENGTH( users.userFullName ) - LENGTH( REPLACE( users.userFullName, ' ', '' ) ) >1,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName, ' ', 2 ) , ' ', -1 ) , NULL ) AS middle_name,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userFullName, ' ', 3 ) , ' ', -1 ) AS last_name,

                        SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 4 ) ,  ' ', -1 ) AS comp_first_name,
                        IF( LENGTH( users.userNickName ) - LENGTH( REPLACE( users.userNickName,  ' ',  '' ) ) >1,
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 5 ) ,  ' ', -1 ) , NULL ) AS comp_middle_name, 
                            SUBSTRING_INDEX( SUBSTRING_INDEX( users.userNickName,  ' ', 6 ) ,  ' ', -1 ) AS comp_last_name

                        FROM users left join accountSetting on accountSetting.userId = users.userId
                         ) temp
                            WHERE (((searchFullName is null) or (searchFullName = '1')) and (LCASE(first_name) like '" . strtolower($search) . "%' or LCASE(middle_name) like '" . strtolower($search) . "%' or userFullName like '" . strtolower($search) . "%' or LCASE(last_name) like '" . strtolower($search) . "%'))
                                or (((searchCompleteName is null) or (searchCompleteName = '1')) and (LCASE(comp_first_name) like '" . strtolower($search) . "%' or LCASE(comp_middle_name) like '" . strtolower($search) . "%' or userNickName like '" . strtolower($search) . "%' or LCASE(comp_last_name) like '" . strtolower($search) . "%'))
                                or (((searchEmail is null) or (searchEmail = '1')) and (LCASE(userEmail) = '" . strtolower($search) . "' ))
                                or (((searchPhone is null) or (searchPhone = '1')) and (userPhone = '" . strtolower($search_phone) . "' or phoneWithCode ='" . $search_phone . "' ))";
            }
            $select = $select->where('userId in (' . $select_in_match_user_ids . ')');
            $select->order("userNickName ASC");
            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
            $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
            $paginator->setItemCountPerPage(6);
            $page = $this->getRequest()->getParam('page', '1');
            $totalPages = $paginator->getTotalItemCount() / 6;
            $viewMore = ($page < $totalPages) ? true : false;
            $this->view->viewMore = $viewMore;
            $this->view->userRowset = $paginator;
        }
    }

    public function searchAction() {
        $this->_helper->layout->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $this->view->countriesList = $countryTable->getCountriesList();
        $this->view->search_type = $this->getRequest()->getParam('search_type', '');
        $this->view->menu = "add-friend";
    }

    public function testAction() {
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();

        $friendName = "";

        $logged_user_id = "546";

        $other_user_id = "542";

        if ($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($logged_user_id, $other_user_id)) {

            $friendName = $editFriendTrusteeRow->name;
        }



        if (!$friendName) {
            $friendName = ($this->friendId) ? $this->userNickName : $this->friendName;
        }

        echo $friendName;
        exit;
    }

}

?>
