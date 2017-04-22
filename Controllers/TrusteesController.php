<?php

/* this controller is for website
 */

class TrusteesController extends My_Controller_Admin {

    public function init() {
        $this->_helper->layout->enableLayout();
        $this->view->menu = "TRUSTEES";
    }

    public function index22Action() {
        // $this->_helper->layout()->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();

        $trustee_namespace = new Zend_Session_Namespace('trustee_name');

        if ($trustee_namespace->add_trustee_first_time) {
            $trustee_namespace->add_trustee_first_time = false;
            $this->view->add_trustee_first_time = true;
        }

        $this->view->countriesList = $countryTable->getCountriesList();
    }

    public function indexAction() {
        $this->_helper->layout()->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();

        $trustee_namespace = new Zend_Session_Namespace('trustee_name');

        if ($trustee_namespace->add_trustee_first_time) {
            $trustee_namespace->add_trustee_first_time = false;
            $this->view->add_trustee_first_time = true;
        }

        $this->view->countriesList = $countryTable->getCountriesList();
        $this->renderScript('trustees/index.phtml');
    }

    public function trusteesDataAction() {
        if ($this->getRequest()->getPost('type') == "approved") {
            $html = $this->view->action("accepted-trustees", "trustees", array());
        } else {
            $html = $this->view->action("pending-trustees", "trustees", array());
        }

        echo json_encode(array("html" => $html));
        exit;
    }

    /**
     * function for getting list of trustees
     */
    public function acceptedTrusteesAction() {
        $this->_helper->layout()->setLayout('new_layout');
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $search = $this->getRequest()->getPost('search', null);
        $page = $this->getRequest()->getPost('page', 1);
        $alpha = $this->getRequest()->getPost('alpha', null);

        $select = $trusteeTable->getMyTrustees($this->loggedUserRow->userId, true);
        if ($search != null && $alpha == null) {
            $select->where("(users.userNickName like ?", "%$search%")
                    ->orWhere("users.userFullName like ?)", "%$search%");
        }
        if ($search != null && $alpha != null) {
            $select->where("userNickName LIKE '$search%'");
        }
//        if($search){
//            $select->where("(users.userNickName like ?","%$search%")
//                   ->orWhere("users.userFullName like ?)","%$search%"); 
//        }
//        
        $paginatorParams = array();
        Zend_Registry::set('paginatorParams', $paginatorParams);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(6);

        $this->view->trusteesRowset = $paginator;
        $totalPages = $paginator->getTotalItemCount() / 6;
        $viewMore = ($page < $totalPages) ? true : false;
        $this->view->viewMore = $viewMore;
        $this->_helper->layout->disableLayout();
    }

    /**
     * function for getting list of incoming and outgoing gtrustees request (means list of pending trustees)
     */
    public function pendingTrusteesAction() {
        $this->_helper->layout()->setLayout('layout');
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $search = $this->getRequest()->getPost('search', null);
        $page = $this->getRequest()->getPost('page', 1);
        $alpha = $this->getRequest()->getPost('alpha', null);
        $select = $trusteeTable->getIncomingOutgoingRequest($this->loggedUserRow->userId, true, $search,$alpha);
        
        $paginatorParams = array();
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(6);

        $this->view->pendingTrusteesRowset = $paginator;
        $totalPages = $paginator->getTotalItemCount() / 6;
        $viewMore = ($page < $totalPages) ? true : false;
        $this->view->viewMore = $viewMore;
        $this->_helper->layout->disableLayout();
    }

    public function searchAction() {
        $this->_helper->layout->setLayout('new_layout');
        $countryTable = new Application_Model_DbTable_Countries();
        $this->view->countriesList = $countryTable->getCountriesList();
        $this->view->search_type = $this->getRequest()->getParam('search_type', '');
        $this->view->menu = "add-trustee";
    }

    public function userguideAction() {
        
    }

}

?>
