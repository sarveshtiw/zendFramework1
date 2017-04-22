<?php
/* Author name : Rajesh Pal
 * Date : 26 Nov 2014
 * Your profile, you can edit your profile.
 */

class ProfileController extends My_Controller_Abstract {

    public function init() {
       // require_once realpath(dirname(__FILE__)).'/../../library/facebooksdk/src/facebook.php'; 
    
        $this->view->fbAppId = "266446760141219";
//        $this->fbsecret = "1334d98b576f2a9c668ed101a0fe1179";
//        $this->view->fbAppId      = "257955024398140";
//        $this->view->fbsecret     = "37c0762eddc12332c410a8552b25c939";
        $this->callback_url = "http://pay.wa-app.com/profile/post-fb-message/";
        
    }

    public function indexAction() {
        if ($this->loggedUserRow->userId>0) {
        }else{
            $this->_redirect($this->makeUrl('/'));
        }

        $userTable = new Application_Model_DbTable_Users();
         $userId = $this->getRequest()->getParam('userId','');
         if ($userId > 0) {
            $userId = $this->common->decrypt($userId);
            $userRow = $userTable->getRowById($userId);
            $this->view->userImage = ($userRow['userImage'] != '') ? $userRow['userImage'] : '';
            $this->view->userCoverImage = ($userRow['userCoverImage'] != '') ? $userRow['userCoverImage'] : '';
            $data = array(
                'userFullName' => $userRow['userFullName'],
                'userNickName' => $userRow['userNickName'],
                'userEmail' => $userRow['userEmail'],
                "phoneWithCode" => $userRow['phoneWithCode'],
                'secQuestion' => $userRow['secQuestion'],
                'secAnswer' => $userRow['secAnswer'],
                'user_location' => $userRow['user_location'],
                'profileStatus' => $userRow['profileStatus'],
                'disable' => 1
            );
        } else {
            $userId = $this->loggedUserRow->userId;
            $this->view->userImage = ($this->loggedUserRow->userImage != '') ? $this->loggedUserRow->userImage : '';
            $this->view->userCoverImage = ($this->loggedUserRow->userCoverImage != '') ? $this->loggedUserRow->userCoverImage : '';
            $data = array(
                'userFullName' => $this->loggedUserRow->userFullName,
                'userNickName' => $this->loggedUserRow->userNickName,
                'userEmail' => $this->loggedUserRow->userEmail,
                "phoneWithCode" => $this->loggedUserRow->phoneWithCode,
                'secQuestion' => $this->loggedUserRow->secQuestion,
                'secAnswer' => $this->loggedUserRow->secAnswer,
                'user_location' => $this->loggedUserRow->user_location,
                'profileStatus' => $this->loggedUserRow->profileStatus,
                'disable' => 0
            );
        }




        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {

            $this->view->profile = $data;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function updateUserDetailsAction() {
        $this->_helper->layout->disableLayout();
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;

        $name = $decoded['name'];
        $nickname = $decoded['nickname'];
        $email = $decoded['email'];
        $number = $decoded['number'];
        $location = $decoded['location'];

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $data = array(
                'userFullName' => $name,
                'userNickName' => $nickname,
                'userEmail' => $email,
                "phoneWithCode" => $number,
                'user_location' => $location
            );
            $this->db->update("users", $data, "userId = '" . $userId . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function updateUserAnswerAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;

        $answer = $decoded['answer'];
        $question = $decoded['question'];

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $data = array(
                'secAnswer' => $answer,
                'secQuestion' => $question,
            );
            $this->db->update("users", $data, "userId = '" . $userId . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function updateUserProfileStatusAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;

        $profilestatus = $decoded['profilestatus'];

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $data = array(
                'profileStatus' => $profilestatus
            );
            $this->db->update("users", $data, "userId = '" . $userId . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function varifyPasswordAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $decoded = $this->common->Decoded();
        $response = array();
        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            if (md5($decoded['password']) == $this->loggedUserRow['userPassword']) {
                $response['response_string'] = "success";
                echo json_encode($response);
                exit;
            } else {
                $this->common->displayMessage("fail", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function changePasswordAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $decoded = $this->common->Decoded();

        $oldpassword = $decoded['oldpassword'];
        $newpassword = $decoded['newpassword'];

        $response = array();
        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            if (md5($decoded['oldpassword']) == $this->loggedUserRow['userPassword']) {

                $data = array(
                    'userPassword' => md5($newpassword)
                );
                $this->db->update("users", $data, "userId = '" . $userId . "'");

                $response['response_string'] = "success";
                echo json_encode($response);
                exit;
            } else {
                $this->common->displayMessage("fail", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function addAction() { }
    
    public function uploadfileAction() {

        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userId = $this->loggedUserRow->userId;
        $userImage = ($this->loggedUserRow->userImage != '') ? end(explode('/', $this->loggedUserRow->userImage)) : '';
        $userCoverImage = ($this->loggedUserRow->userCoverImage != '') ? end(explode('/', $this->loggedUserRow->userCoverImage)) : '';


        if ($_REQUEST['act'] != 'upload') {

            /* get request parameter to crop image, and unlink previous and update in database for corresponding user. */
            $_REQUEST['y'] = $_REQUEST['y'];
            $_REQUEST['x'] = $_REQUEST['x'];
            $_REQUEST['y1'] = $_REQUEST['y1'];
            $_REQUEST['x1'] = $_REQUEST['x1'];
            $_REQUEST['height'] = $_REQUEST['height'];
            $_REQUEST['width'] = $_REQUEST['width'];
            $_REQUEST['img_src'] = APPLICATION_PATH . '/../public/uploads/temp/' . end(explode('/', $_REQUEST['img_src']));
            $_REQUEST['already_profile_img'] = $userImage;
            $_REQUEST['already_cover_img'] = $userCoverImage;
            $_REQUEST['coverimage'] = $_REQUEST['coverimage'];


            /* Unlink previous uploaded profile and cover image. */
            if ($_REQUEST['coverimage'] == '1') {
                unlink(APPLICATION_PATH . '/../public/images/users/' . $userCoverImage);
            } else {
                unlink(APPLICATION_PATH . '/../public/images/users/' . $userImage);
            }


            /* New name for profile and cover image. */
            $ext = end(explode(".", $_REQUEST['img_src']));
            $_REQUEST['filename'] = md5(time() . rand()) . "." . $ext;


            /* Check whether request for profile or cover image, change in database for corresponding image request. */
            if ($_REQUEST['coverimage'] == '1') {
                $data = array(
                    'userCoverImage' => 'http://pay.wa-app.com/images/users/' . $_REQUEST['filename']
                );
            } else {
                $data = array(
                    'userImage' => 'http://pay.wa-app.com/images/users/' . $_REQUEST['filename']
                );
            }

            $this->db->update("users", $data, "userId = '" . $userId . "'");
        }
        require_once APPLICATION_PATH . '/../includes/upload.php';

        exit;
    }
    
    public function postFbMessageAction(){
        $this->_helper->layout->disableLayout();
        $app_id = $this->fbAppId;
        $app_secret = $this->fbsecret; 
        $my_url = $this->callback_url; 
        $video_title = "";
        $video_desc = "";

        $code = $_REQUEST["code"];

        if(empty($code)) {
           $dialog_url = "http://www.facebook.com/dialog/oauth?client_id=" 
             . $app_id . "&redirect_uri=" . urlencode($my_url) 
             . "&scope=publish_stream";
            echo("<script>window.location.href='" . $dialog_url . "'</script>");
        }

        $token_url = "https://graph.facebook.com/oauth/access_token?client_id="
            . $app_id . "&redirect_uri=" . urlencode($my_url) 
            . "&client_secret=" . $app_secret 
            . "&code=" . $code;
        $access_token = file_get_contents($token_url);

        $this->view->post_url = "https://graph-video.facebook.com/me/videos?"
            . "title=" . $video_title. "&description=" . $video_desc 
            . "&". $access_token;

     }

    public function updateUserFbIdAction(){
        $this->_helper->layout->disableLayout();
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
        $userTable = new Application_Model_DbTable_Users();
        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;

        $userFbId = $decoded['userFbId'];
        $fbUserFullName = $decoded['fbUserFullName'];
        $fbUserEmail = $decoded['fbUserEmail'];
        $data = array(
                'userFbId' => $userFbId,
                'fbUserFullName' => $fbUserFullName,
                'fbUserEmail' => $fbUserEmail,
            );
        $this->db->update("users", $data, "userId = '" . $userId . "'");
        
    }
     
}

?>