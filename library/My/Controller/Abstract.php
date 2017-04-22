<?php

class My_Controller_Abstract extends Zend_Controller_Action {

    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->setLayout('new_layout');
        date_default_timezone_set('UTC');
        $this->common = new Common_Api();

        $auth = Zend_Auth::getInstance();
        $auth->setStorage(new Zend_Auth_Storage_Session('login'));
        $authData = $auth->getIdentity();
        $userTable = new Application_Model_DbTable_Users();

        $request_controller_name = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();

        if ($auth->hasIdentity()) {
            $userId = $authData['userId'];

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->userStatus == "1")) {

                $this->view->loggedUserRow = $this->loggedUserRow = $userRow;
                $this->_helper->layout->enableLayout();
            } else {
                $auth->clearIdentity();
                if (!in_array($request_controller_name, array("help"))) {
                    $this->_helper->layout->disableLayout();
                }
            }
        }

        $this->baseUrl = $this->view->baseUrl = $this->baseUrl();

        $this->view->request = $this->getRequest();
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination.phtml');

        $this->config_details = Zend_Registry::get('config');

        $this->quickblox_details = $this->config_details->quickblox_details;
        $this->quickblox_details_new = $this->config_details->quickblox_details_new;

        $this->uploadpath = $this->config_details->constants->rootmedia;
        $this->servicekey = $this->config_details->constants->servicekey;
        $this->adminmail = $this->config_details->constants->adminmail;

        $this->servicekey = $this->config_details->constants->servicekey;
        $this->android_app_link = "https://play.google.com/store/apps/details?id=com.wa_app&rdid=com.wa_app";
        $this->ios_app_link = "https://play.google.com/store/apps/details?id=com.wa_app&rdid=com.wa_app";
        $this->tiny_url_android = 'https://goo.gl/Rv8ktY';
        $this->tiny_url_ios = 'http://appstore.com/allinwa';
        $this->user = new Application_Model_UsersMapper();
        $this->_Pathffmeg = trim(shell_exec('which ffmpeg'));

        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
    }

    public function baseUrl() {
        $frontController = Zend_Controller_Front::getInstance();
        return $frontController->getBaseUrl();
    }

    public function imageUrl() {
        
    }

    public function makeUrl($url) {
        if ($url['0'] != "/") {
            $url = "/" . $url;
        }
        return $this->baseUrl() . $url;
    }

    public function displayMessage($message, $errorCode, $result = array(), $response_error_key = "0") {
        $data = array("response_string" => $message, "error_code" => $errorCode, "result" => $result, "response_error_key" => $response_error_key);

        echo json_encode($data);
        exit;
    }

    public function updateQuickBloxDetails($userRow) {
        $zend_session = new Zend_Session_Namespace();

        $token = $this->quickBloxAdminToken();

        $data = array("user" => array(
                "email" => $userRow->userEmail,
                "full_name" => $userRow->userNickName
        ));

        $userDetails = json_encode($data);

        $ch = curl_init($this->quickblox_details_new->api_end_point . "/users/$userRow->quickBloxId.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
    }

    public function deleteFromQuickBlox($quickBloxId) {
        $token = $this->quickBloxAdminToken();

        $ch = curl_init($this->quickblox_details_new->api_end_point . "/users/$quickBloxId.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
    }

    public function updateQuickbloxByEmail($userDetails, $email) {
        $zend_session = new Zend_Session_Namespace();

        $token = $this->quickBloxAdminToken();

        $data = array("user" => array(
                "email" => $userRow->userEmail,
                "full_name" => $userRow->userNickName
        ));

        $userDetails = json_encode($data);

        $ch = curl_init($this->quickblox_details_new->api_end_point . "/users/$email.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        return $resultJson = curl_exec($ch);
        //$resultArr = json_decode($resultJson, true);
    }

    /**
     *   creating token for admin account
     */
    public function quickBloxAdminToken() {
        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[login]=" . $this->quickblox_details_new->user_login . "&user[password]=" . $this->quickblox_details_new->user_password;

        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);

        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[login]' => $this->quickblox_details_new->user_login,
            'user[password]' => $this->quickblox_details_new->user_password
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://api.quickblox.com/session.json
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response
        $responce = curl_exec($curl);

        $re = json_decode($responce, true);

        return (isset($re['session']) && isset($re['session']['token'])) ? $re['session']['token'] : false;
    }

    /**
     *   new user signup
     */
    public function quickBloxSignup($userRow) {

        if ($token = $this->quickBloxAdminToken()) {
            $curl = curl_init();

            $data = array("user" => array(
                    "password" => '12345678',
                    "email" => $userRow->userEmail,
                    "full_name" => $userRow->userNickName
            ));

            $userDetails = json_encode($data);

            $ch = curl_init($this->quickblox_details_new->api_end_point . '/users.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );

            $resultJson = curl_exec($ch);
            $resultArr = json_decode($resultJson, true);
            // if email already register then get the details and return quickbox id
            if (isset($resultArr['error']) && isset($resultArr['error']['email']) && $resultArr['error']['email'][0] == 'has already been taken') {
                $resultArr = $this->updateQuickbloxByEmail($userDetails, $userRow->userEmail);
                return $resultArr['user']['id'];
            }

            if (isset($resultArr['user']['id'])) {
                return $resultArr['user']['id'];
            } else {
                if ($quick_blox_id = $this->quick_blox_user_login($userRow)) {
                    return $quick_blox_id;
                } else {
                    $userRow->delete();
                    $this->common->displayMessage('There is some problem please try after some time', "1", array(), "7");
                }
            }
        } else {
            $userRow->delete();
            $this->common->displayMessage('There is some problem please try after some time', "1", array(), "7");
        }
    }

    /**
     *   new user signup
     */
    public function quickBloxSignupNew($login, $name) {
        if ($token = $this->quickBloxAdminToken()) {
            $curl = curl_init();
            $userDetails = json_encode(array("user" => array("password" => '12345678', "login" => $login, "full_name" => $name)));
            $ch = curl_init($this->quickblox_details_new->api_end_point . '/users.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );
            $resultJson = curl_exec($ch);
            $resultArr = json_decode($resultJson, true);
            // if email already register then get the details and return quickbox id
            if (isset($resultArr['error']) && isset($resultArr['error']['login']) && $resultArr['error']['login'][0] == 'has already been taken') {
                $resultArr = $this->updateQuickbloxByEmailNew($name, $login);
                return $resultArr['user']['id'];
            }
            if (isset($resultArr['user']['id'])) {
                return $resultArr['user']['id'];
            } else {
                if ($quick_blox_id = $this->quick_blox_user_loginNew($login)) {
                    return $quick_blox_id;
                }
            }
        }
    }

    public function quick_blox_user_loginNew($login) {
        $userName = $login;
        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[login]=" . $userName . "&user[password]=" . "12345678";
        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);
        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[login]' => $userName,
            'user[password]' => "12345678"
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://apiwaapp.quickblox.com/session.json
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response
        $response_json = curl_exec($curl);
        $response = json_decode($response_json, true);
//        print_r($response);
        if ($response && isset($response['session']) && isset($response['session']['user_id'])) {
            return $response['session']['user_id'];
        } else {
            return false;
        }
    }

    /**
     *   quick blox user login quick_blox_user_login  quick_blox_login_at_signup_time
     */
    public function quick_blox_user_login($userRow) {
        $userEmail = $userRow->userEmail;

        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[email]=" . $userEmail . "&user[password]=" . "12345678";
        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);

        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[email]' => $userEmail,
            'user[password]' => "12345678"
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://apiwaapp.quickblox.com/session.json

        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        $response_json = curl_exec($curl);
        $response = json_decode($response_json, true);

        if ($response && isset($response['session']) && isset($response['session']['user_id'])) {
            return $response['session']['user_id'];
        } else {
            return false;
        }
    }

    /**
     *   creating quickblox user token 
     */
    public function quickBloxUserTokenNew($userRow) {
        $userName = $userRow->userName;
        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[login]=" . $userName . "&user[password]=" . "12345678";
        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);

        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[login]' => $userName,
            'user[password]' => "12345678"
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://api.quickblox.com/session.json
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        $response_json = curl_exec($curl);
        $response = json_decode($response_json, true);

        if ($response && $response['session']['token']) {
            return $response['session']['token'];
        }
    }

    public function quickBloxUserToken($userRow) {
        $userEmail = $userRow->userEmail;

        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[email]=" . $userEmail . "&user[password]=" . "12345678";
        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);

        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[email]' => $userEmail,
            'user[password]' => "12345678"
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://api.quickblox.com/session.json
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        $response_json = curl_exec($curl);
        $response = json_decode($response_json, true);

        if ($response && $response['session']['token']) {
            return $response['session']['token'];
        }
    }

    public function getQuickBloxUserToken($userRow) {
        echo $userEmail = $userRow->userName;

        $nonce = rand();
        $timestamp = time();
        $signature_string = "application_id=" . $this->quickblox_details_new->app_id . "&auth_key=" . $this->quickblox_details_new->auth_key . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[login]=" . $userEmail . "&user[password]=" . "12345678";
        $signature = hash_hmac('sha1', $signature_string, $this->quickblox_details_new->auth_secret);

        $post_body = http_build_query(array(
            'application_id' => $this->quickblox_details_new->app_id,
            'auth_key' => $this->quickblox_details_new->auth_key,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $signature,
            'user[login]' => $userEmail,
            'user[password]' => "12345678"
        ));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->quickblox_details_new->api_end_point . '/' . $this->quickblox_details_new->api_path_session); // Full path is - https://api.quickblox.com/session.json
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        $response_json = curl_exec($curl);
        $response = json_decode($response_json, true);


        if ($response && $response['session']['token']) {
            return $response['session']['token'];
        }
    }

    /**
     * creating quick blox chat dialog id 
     */
    public function createDialogId($token, $quickBloxId, $returnResult = false) {
        $curl = curl_init();
        $data = array(
            'occupants_ids' => $quickBloxId,
            'type' => 3
        );

        $userDetails = json_encode($data);

        $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Dialog.json');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );

        $resultJson = curl_exec($ch);
        $resultArr = json_decode($resultJson, true);

        if ($returnResult) {
            return $resultArr;
        }

        if ($resultArr && isset($resultArr['_id'])) {
            return $resultArr['_id'];
        } else {
            return false;
        }
    }

    public function createQuickBloxGroup($userId, $groupName, $groupMembers) {
        $userTable = new Application_Model_DbTable_Users();
        $userRow = $userTable->getRowById($userId);

        $data_arr = array('is_secret' => 1, 'class_name' => 'secret');
        if ($token = $this->quickBloxUserToken($userRow)) {
            $curl = curl_init();

            $data = array(
                'type' => 2,
                'name' => $groupName,
                'occupants_ids' => implode(",", $groupMembers),
                'data' => $data_arr
            );
            // $this->getDialog($userId);

            $userDetails = json_encode($data);

            $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Dialog.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );
            $resultJson = curl_exec($ch);
            $resultArr = json_decode($resultJson, true);
//            echo "<pre>";
//            print_r($resultArr);
//            die();
            return $resultArr;
        } else {
            return false;
        }
    }

    public function createQuickBloxSimpleGroup($userId, $groupName, $groupMembers) {
        $userTable = new Application_Model_DbTable_Users();
        $userRow = $userTable->getRowById($userId);
        if ($token = $this->quickBloxUserToken($userRow)) {
            $curl = curl_init();

            $data = array(
                'type' => 2,
                'name' => $groupName,
                'occupants_ids' => implode(",", $groupMembers),
            );
            // $this->getDialog($userId);

            $userDetails = json_encode($data);

            $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Dialog.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );
            $resultJson = curl_exec($ch);
            $resultArr = json_decode($resultJson, true);
//            echo "<pre>";
//            print_r($resultArr);
//            die();
            return $resultArr;
        } else {
            return false;
        }
    }

    public function getDialog($userId) {
        $userTable = new Application_Model_DbTable_Users();
        $userRow = $userTable->getRowById($userId);

        $data_arr = array('issecret' => 1, 'class_name' => 'secret');
        if ($token = $this->quickBloxUserToken($userRow)) {
            $curl = curl_init();

            $ch = curl_init($this->quickblox_details_new->api_end_point . "/chat/Dialog.json?data[class_name]=secret&data['issecret']=1");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                "QuickBlox-REST-API-Version: 0.1.0",
                "QB-Token: $token")
            );
            $resultJson = curl_exec($ch);
            $resultArr = json_decode($resultJson, true);
            echo "<pre>";
            print_r($resultArr);
            die();
            return $resultArr;
        } else {
            return false;
        }
    }

    public function convertIntoUtcTime($date, $user_time_zone) {
        try {
            $schedule_date = new DateTime($date, new DateTimeZone($user_time_zone));

            $schedule_date->setTimeZone(new DateTimeZone('UTC'));
            $utc_date_time = $schedule_date->format('Y-m-d H:i:s');
            return $utc_date_time;
        } catch (Exception $e) {
            return $date;
        }
    }

    public function isRequestFromPhoneApp() {
        //     echo $_SERVER['HTTP_USER_AGENT']."<br>";
        $iPod = stripos($_SERVER['HTTP_USER_AGENT'], "iPod");
        $iPhone = stripos($_SERVER['HTTP_USER_AGENT'], "iPhone");
        $iPad = stripos($_SERVER['HTTP_USER_AGENT'], "iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'], "Android");

        if ($iPod || $iPhone || $iPad || $Android) {
            //        echo "yes";
            //  return true;
        } else {
            //   return false;
            //        echo "no";
        }
        //     exit;
    }

    function getThumbImage($video_path, $thumbnail_path) {
        $video_path = getcwd() . $video_path;
        $thumbnail = getcwd() . $thumbnail_path;

        $movie = new ffmpeg_movie($videoPath, false);
        $this->videoDuration = $movie->getDuration();
        $this->frameCount = $movie->getFrameCount();
        $this->frameRate = $movie->getFrameRate();
        $this->videoTitle = $movie->getTitle();
        $this->author = $movie->getAuthor();
        $this->copyright = $movie->getCopyright();
        $this->frameHeight = $movie->getFrameHeight();
        $this->frameWidth = $movie->getFrameWidth();

        $capPos = ceil($this->frameCount / 4);

        if ($this->frameWidth > 120) {
            $cropWidth = ceil(($this->frameWidth - 120) / 2);
        } else {
            $cropWidth = 0;
        }
        if ($this->frameHeight > 90) {
            $cropHeight = ceil(($this->frameHeight - 90) / 2);
        } else {
            $cropHeight = 0;
        }
        if ($cropWidth % 2 != 0) {
            $cropWidth = $cropWidth - 1;
        }
        if ($cropHeight % 2 != 0) {
            $cropHeight = $cropHeight - 1;
        }

        $frameObject = $movie->getFrame($capPos);


        if ($frameObject) {
            //  $imageName = "tmb_vid_122jpg";

            $frameObject->resize(120, 90, 0, 0, 0, 0);
            imagejpeg($frameObject->toGDImage(), $thumbnail);
        } else {
            $imageName = "";
        }

        //   return $imageName;
    }

    /**
     *   function for sending mails 
     */
    public function mail($para = array(), $template, $to, $subject) {
        $common = new Common_Api();

        $html = new Zend_View();

        $html->setScriptPath(APPLICATION_PATH . '/views/emails/');

        if (count($para) > 0) {
            foreach ($para as $key => $value) {
                $html->assign($key, $value);
            }
        }

        $bodyText = $html->render($template);
        $common->sendMail($to, $bodyText, $subject);
    }

    function createThumbnail($video_path, $thumbnail_path) {
        $ffmpeg = $this->_Pathffmeg;
        if (empty($ffmpeg)) {
            die('ffmpeg not available');
        }

        $video = getcwd() . $video_path;
        $thumbnail = getcwd() . $thumbnail_path;
        $second = 1;
        $cmd = "$ffmpeg -i $video -deinterlace -an -ss $second -t 00:00:01 -s 150x90 -r 1 -y -vcodec mjpeg -f mjpeg  $thumbnail 2>&1";

        exec($cmd);
    }

    /*     * *********
     * @function   : used for get video duration/length
     * @parma      : video path
     * @return     : string
     * @author     : Akhilesh Singh |2014-3-19|
     * ** */

    function getDuration($video) {
        $ffmpeg = $this->_Pathffmeg;
        $video = getcwd() . $video;
        // "$ffmpeg -i $video 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
        $time = exec("$ffmpeg -i $video 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");
        $duration = explode(":", $time);
        $duration_in_seconds = $duration[0] * 3600 + $duration[1] * 60 + round($duration[2]);
        $seconds = $duration_in_seconds;
        $minutes = $seconds / 60;
        $real_minutes = floor($minutes);
        $real_seconds = round(($minutes - $real_minutes) * 60);
        return $real_minutes . "." . $real_seconds;
    }

    function setVideoTime($time) {
        return ($time == 1 ? "60" : str_replace("0.", "", $time));
    }

    public function randomAlphaNum($length) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return strtolower($token);
    }

    public function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0)
            return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    public function cleanString($string = null) {
        return mb_convert_encoding($string, "UTF-8", "HTML-ENTITIES");
    }

    ///// function Created By Sarvesh Tiwari 30/3/2015 //////
    ///// function Used for Insert Multiples Values once time /////

    public function InsertMultipleRows($table, $data) {
        $cols = '';
        $val = '';
        $val2 = '';
        $query = 'Insert Into ' . $table;
        if (array_key_exists('0', $data)) {
            $col = '(';
            foreach ($data[0] as $key => $value) {
                $cols .= ',' . $key;
            }
            $col .= substr($cols, 1) . ')';

            $values = 'values';
            foreach ($data as $value) {
                $val .= ",(";
                $val2 = '';
                foreach ($value as $key => $val4) {
                    $val2 .= ",'" . addslashes($value[$key]) . "'";
                }
                $val3 = substr($val2, 1);
                $val .= $val3 . ")";
            }
            $sql = $query . $col . $values . substr($val, 1);
        } else {
            $col = '(';
            foreach ($data as $key => $value) {
                $cols .= ',' . $key;
            }
            $col .= substr($cols, 1) . ')';

            $values = 'values (';
            foreach ($data as $key => $value) {
                $val .= ",'" . addslashes($value) . "'";
            }
            $val2 .= substr($val, 1) . ')';
            $sql = $query . $col . $values . $val2;
        }
        $this->db->query($sql);
        return $this->db->lastInsertId();
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

    /*
     * check request is valid for ajax request action
     */

    function checkValidRequest() {
        $refere_url = $_SERVER['HTTP_REFERER'];
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (strpos($refere_url, 'wa-app.com') !== false) {
                return true;
            }
            //modify by Akhilesh Singh 10 Aug 2015 add this line
            else if (strpos($refere_url, '52.4.193.184') !== false) {
                return true;
            } else {
                echo json_encode(array("errorMessage" => "Invalid Request"));
                die();
            }
        } else {
            echo json_encode(array("errorMessage" => "Invalid Request"));
            die();
        }
    }

    /*
     * delete dialog from quickblox for specific user
     */

    public function deleteDialogFromQuickBlox($token, $dialogId) {

        $this->quickblox_details_new->api_end_point . "/Dialog/$dialogId.json";
        $ch = curl_init($this->quickblox_details_new->api_end_point . "/chat/Dialog/$dialogId.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
        $json = json_decode($resultJson, true);
        print_r($json);
    }

    /*
     * used for check the json data
     */

    function printJson($decoded, $file = 'error') {
        $filePath = getcwd() . "/" . $file . "." . "txt";
        $myfile = fopen($filePath, "w+") or die("Unable to open file!");
        $date = date("Y-m-d H:i:s") . "<br>";
        chmod($filePath, 0777);
        fwrite($myfile, $date);
        $json = json_encode($decoded);
        fwrite($myfile, $json);
        fclose($myfile);
    }

    public function removeUser($token, $dialogId, $userDetails) {
        $ch = curl_init($this->quickblox_details_new->api_end_point . "/chat/Dialog/$dialogId.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
    }

    public function updateUser($token, $dialogId, $userDetails) {
        $ch = curl_init($this->quickblox_details_new->api_end_point . "/chat/Dialog/$dialogId.json");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $userDetails);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
    }

    public function setDateFormat($date, $format = "Y-m-d") {
        return date($format, strtotime($date));
    }

    /*
     * create folder if not exist
     * Author: Akh
     * Date  : 18 Aug 2015
     */

    function createDirectory($folderName) {
        $createFolder = getcwd() . "/" . $folderName . "/";
        if (!file_exists($createFolder)) {
            mkdir($createFolder);
            @chmod($createFolder, 0755);
        }
    }

    function crop($new_name, $path, $xInput, $yInput, $newW, $newH, $new_width = 250, $new_height = 250) {
        $x = getimagesize($path);
        $iWidth = $new_width;
        $iHeight = $new_height; // desired image result dimensions
        $iJpgQuality = 90;

        switch ($x['mime']) {
            case "image/gif":
                $img = imagecreatefromgif($path);
                break;
            case "image/jpeg":
                $img = imagecreatefromjpeg($path);
                break;
            case "image/jpg":
                $img = imagecreatefromjpeg($path);
                break;
            case "image/png":
                $iJpgQuality = 9;
                $img = imagecreatefrompng($path);
                break;
        }
        $img_base = imagecreatetruecolor((int) $newW, (int) $newH);
        imagecopyresampled($img_base, $img, 0, 0, (int) $xInput, (int) $yInput, (int) $newW, (int) $newH, (int) $newW, (int) $newH);
        $path_info = pathinfo($path);
        $new_image = getcwd() . $new_name . "." . $path_info['extension'];
        switch (strtolower($path_info['extension'])) {
            case "gif":
                imagegif($img_base, $new_image, $iJpgQuality);
                break;
            case "jpeg":
                imagejpeg($img_base, $new_image, $iJpgQuality);
                break;
            case "jpg":
                imagejpeg($img_base, $new_image, $iJpgQuality);
                break;
            case "png":
                imagepng($img_base, $new_image, $iJpgQuality);
                break;
        }
        return $new_name . "." . $path_info['extension'];
    }

##########################################################################################################
# IMAGE FUNCTIONS																						 #
# You do not need to alter these functions																 #
##########################################################################################################

    function resizeImage($image, $width, $height, $scale) {
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);
        $newImageWidth = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
        switch ($imageType) {
            case "image/gif":
                $source = imagecreatefromgif($image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source = imagecreatefromjpeg($image);
                break;
            case "image/png":
            case "image/x-png":
                $source = imagecreatefrompng($image);
                break;
        }
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $width, $height);

        switch ($imageType) {
            case "image/gif":
                imagegif($newImage, $image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                imagejpeg($newImage, $image, 90);
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage, $image);
                break;
        }

        chmod($image, 0777);
        return $image;
    }

    //You do not need to alter these functions
    function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale) {
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);

        $newImageWidth = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
        switch ($imageType) {
            case "image/gif":
                $source = imagecreatefromgif($image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source = imagecreatefromjpeg($image);
                break;
            case "image/png":
            case "image/x-png":
                $source = imagecreatefrompng($image);
                break;
        }
        imagecopyresampled($newImage, $source, 0, 0, $start_width, $start_height, $newImageWidth, $newImageHeight, $width, $height);
        $path_info = pathinfo($image);
        $new_image = getcwd() . $thumb_image_name . "." . $path_info['extension'];
        switch ($imageType) {
            case "image/gif":
                imagegif($newImage, $new_image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                imagejpeg($newImage, $new_image, 90);
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage, $new_image);
                break;
        }
        chmod($new_image, 0777);
        return $thumb_image_name . "." . $path_info['extension'];
    }

    //You do not need to alter these functions
    function getHeight($image) {
        $size = getimagesize($image);
        $height = $size[1];
        return $height;
    }

    //You do not need to alter these functions
    function getWidth($image) {
        $size = getimagesize($image);
        $width = $size[0];
        return $width;
    }

    public function localTime($date = null, $format = "Y-m-d h:i") {
        $date = isset($date) ? $date : date("Y-m-d H:i:s");
        if ($_COOKIE['offset']) {
            $offset = $_COOKIE['offset'];
            $newDate = date($format, strtotime("+$offset minutes", strtotime($date)));
            return $newDate;
        } else {
            return $date;
        }
    }
}
