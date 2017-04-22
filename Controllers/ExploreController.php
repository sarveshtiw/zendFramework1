<?php

/*****************
    * Zend Framework
    * @category   Zend
    * @package    Zend_Controller_Action
    * @copyright  Copyright (c) 2008-2012 Zend Technologies USA Inc. (http://www.zend.com)
    * @license    http://framework.zend.com/license/new-bsd     New BSD License
    * @version    $Id: DbTable.php 8862 2012-03-16 15:36:00Z thomas $
    * @author     Ankit Kumar(http://www.fb.com/gshukla67).
    * Create Date 22-01-2014
    * Update Date 22-01-2014
*****/

class ExploreController extends Zend_Controller_Action {

	protected $_db;
    protected $_dbObj;
    protected $_KeyObj;
    protected $_JsonObj;
    protected $_userObj;
    protected $_memberObj;
    
    public function init() {
        //parent::init();
        $this->_helper->layout()->disableLayout(); 
        $this->_helper->viewRenderer->setNoRender(true);
		
        $this->_JsonObj = new Zend_Json();
		$this->_userObj = new User_Users();
		$this->_KeyObj = new Service_Service();
		$this->_memberObj = new UserMembership_Membership();
		
		$this->_dbObj = new Globals();
		$this->_db = $this->_dbObj->getDBConnection();
    }

    public function indexAction() {
		echo "<h1 style='margin-left:41%; margin-top:20%; font-color:#641F11;'><span style='font-size: 50px;'>RED</span><span style='font-size: 45px;'>call</span></h1>";
	}
	
	public function getAction() {
        $objRequest = $this->getRequest();
        $allGetDataArr = $objRequest->getParams();
		if(isset($allGetDataArr['search'])) {
			$response = $this->searchUser($allGetDataArr['device_key'], $allGetDataArr['redcall_key'], $allGetDataArr['uid'], $allGetDataArr['search'], $allGetDataArr['offset']);
		}
		$this->getResponse()->setHeader('Content-Type', 'application/json')->appendBody($response);
    }

	public function postAction() {
        $this->getResponse()->setHeader('Content-Type', 'application/json')->appendBody('');
    }

	public function putAction() {
        $this->getResponse()->setHeader('Content-Type', 'application/json')->appendBody('');
    }
	
	public function deleteAction() {
        $this->getResponse()->setHeader('Content-Type', 'application/json')->appendBody('');
    }

	/****************
        * searchUser	Action use to get the searched user.
		* @params		Key and search.
		* @return		Josn data
        * @author     	Ankit Kumar(http://www.fb.com/gshukla67).
        * Create Date 	22-01-2014
    *****/
	public function searchUser($device_key=NULL, $redcall_key=NULL, $uid=NULL, $search=NULL, $offset=NULL) {
		// GET THE MESSAGE FROM ERROR.INI FILES.
		$error_msg = $this->errorMessage('explore');
        $error_encode = json_encode($error_msg);
        $error_encode = str_replace('"', "'", $error_encode);

		// SERVICE AOUTH CODE START HERE.
		$auth = Zend_Auth::getInstance();
		$authAdapter = new Zend_Auth_Adapter_DbTable($this->_db,'redcall_security_key');
		$authAdapter->setIdentityColumn('service_key')
					->setCredentialColumn('user_id')
					->setCredentialColumn('device_key')
					->setIdentity($redcall_key)
					->setCredential($uid)
					->setCredential($device_key);
		$ZendAuthResultArr = $auth->authenticate($authAdapter);
		if($ZendAuthResultArr->isValid()) {
			if ((int) $uid) {
				// FILTER THE DATA AND RETURN THE RESPONCE.
				$userArr = array();
				if (empty($offset))
					$offset = 0;
				if (substr($search, 0, 1) == "@")
					$serachResultArr = $this->_userObj->getSrarchByUsername($uid, substr($search, 1), $offset, 36);
				else
					$serachResultArr = $this->_userObj->getSrarchByName($uid, $search, $offset, 36);
				foreach ($serachResultArr AS $key=>$user) {
					if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/public/upload_image/" . $user['id'] . "/profiles/" . $user['redcall_image']) && $user['redcall_image'] != "") {
						$image = "/public/upload_image/" . $user['id'] . "/profiles/" . $user['redcall_image'];
					} else {
						$image = "";
					}
					if ($user['request_status'] != "")
						$request_status = $user['request_status'];
					else 
						$request_status = "";
					$userArr[$key] = array(
						'id' => $user['id'],
						'redcall_fname' => $user['redcall_fname'],
						'redcall_lname' => $user['redcall_lname'],
						'redcall_image' => $image,
						'redcall_username' => $user['redcall_username'],
						'redcall_profile_status' => $user['redcall_profile_status'],
						'request_status' => $request_status,
						'creation_date' => date("Y-m-d", $user['creation_date'])
					);
				}
				$successMsgArr = array();
				$successMsgArr['CODE'] = 200;
				$successMsgArr['APICODERESULT'] = "SEARCH_SUCCESS";
				$successMsgArr['MESSAGE'] =	$error_msg['search']['success'];
				$successMsgArr['VALUE'] =	$userArr;
				return $errorMsgJson = $this->_JsonObj->encode($successMsgArr);
			} else {
				$errorMsgArr = array();
				$errorMsgArr['CODE'] =	400;
				$errorMsgArr['APICODERESULT'] = "SEARCH_ERROR";
				$errorMsgArr['MESSAGE'] =	$error_msg['user_id']['no_empty'];
				return $errorMsgJson = $this->_JsonObj->encode($errorMsgArr);
			}
		} else {
			$errorMsgArr = array();
			$errorMsgArr['CODE'] =	46;
			$errorMsgArr['APICODERESULT'] = "AUDIENCE_ERROR";
			$errorMsgArr['MESSAGE'] =	"Authentication failed.";
			return $errorMsgJson = $this->_JsonObj->encode($errorMsgArr);
		}
	}
	
	/****************
        * errorMessage  Function use to get error message on this controller.
		* @params		sect_name
		* @return		Json data.
        * @author     	Ankit Kumar(http://www.fb.com/gshukla67).
        * Create Date 	22-01-2014
    *****/
	public function errorMessage($sect_name = '') {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/error.ini', $sect_name, true);
        return $errorMessage = $config->toArray();
    }
}
?>
