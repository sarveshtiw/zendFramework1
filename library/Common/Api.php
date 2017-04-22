<?php

/* * *******************************************************************
 * 	This library is used for used common function for web services
 * *********************************************************************** */

class Common_Api {

    public $testament_bucket;
    public $wa_messages_bucket;

    public function Request($key) {
        if (isset($_REQUEST[$key]) && trim($_REQUEST[$key]) != "") {
            return urldecode($_REQUEST[$key]);
        } else {
            return $_REQUEST[$key];
        }
    }

    public function __construct() {
        $this->configs = $config_details = Zend_Registry::get('config');
        $this->quickblox_details = isset($config_details->quickblox_details) ? $config_details->quickblox_details : "";
        $this->testament_bucket = isset($config_details->s3->testament_bucket) ? $config_details->s3->testament_bucket : "";
        $this->wa_messages_bucket = isset($config_details->s3->wa_messages_bucket) ? $config_details->s3->wa_messages_bucket : "";
        // Added by Sarvesh for WA-SOS attachment S3 09 Sep 2015
        $this->wa_sos_bucket = isset($config_details->s3->wa_sos_bucket) ? $config_details->s3->wa_sos_bucket : "";
        $this->twitter_details = isset($config_details->twitter) ? $config_details->twitter : "";
        
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->user = new Application_Model_UsersMapper();

        $this->uploadpath = Zend_Registry::getInstance()->constants->rootmedia;
        $this->basemedia = Zend_Registry::getInstance()->constants->basemedia;
    }

    public function Decoded() {
        $handle = fopen('php://input', 'r');

        //$jsonInput = urldecode(fgets($handle));
        $jsonInput = fgets($handle);

        $decoded = json_decode($jsonInput, true);

        if ($decoded['encoded']) {
            foreach ($decoded as $key => $val) {
                $new_decoded[$key] = urldecode($val);
            }
            return $new_decoded;
        } else {
            return $decoded;
        }
    }

    public function encrypt($plain) {
        $cipher = array();
        for ($x = 0; $x < strlen($plain); $x++) {
            ord($plain[$x]);
            $cipher[] = ord($plain[$x]);
        }
        return implode('/', $cipher);
    }

    // To decrypt a string
    public function decrypt($cipher) {
        $data = explode('/', $cipher);
        $plain = '';
        for ($x = 0; $x < count($data); $x++) {
            $plain .= chr($data[$x]);
        }
        return $plain;
    }

    /* To give a specific message on different call */

    public function specificMessage($message, $errorCode, $result = '0', $key = 'result', $newKey = false, $keyresult = false, $newkey1 = false, $keyresult1 = false, $newarray = array()) {
        $data = array("Response_String" => $message, "errcode" => $errorCode, $key => $result);

        if ($newKey)
            $data[$newKey] = $keyresult;
        if ($newkey1)
            $data[$newkey1] = $keyresult1;

        if (count($newarray) > 0) {
            foreach ($newarray as $k => $narr) {
                $data[$k] = $narr;
            }
        }
        echo json_encode($data);
        exit;
    }

    /* To give a specific message on different call */

    public function displayMessage($message, $errorCode, $result = array(), $response_error_key = "0") {
        $data = array("response_string" => $message, "error_code" => $errorCode, "result" => $result, "response_error_key" => $response_error_key);

        echo json_encode($data);
        exit;
    }

    // For the case of parameter missing
    public function parameterMissing($panel = false) {
        $data = array("Response_String" => "Required Parameter Missing.", "errcode" => _PARAMETER, "result" => false);

        if ($panel !== false)
            $data['panel'] = $panel;
        echo json_encode($data);
        exit;
    }

    public function parameterMissing1($panel = false) {
        $data = array("response_string" => "Required Parameter Missing.", "error_code" => "1", "response_error_key" => "1");
        if ($panel !== false)
            $data['panel'] = $panel;
        echo json_encode($data);
        exit;
    }

    //Problem related to database manipulation (DML) query
    public function webservicesProblem() {
        $data = array("Response_String" => "Web-services Problem.", "errcode" => _PARAMETER, "result" => false);
        echo json_encode($data);
        exit;
    }

    public function checkEmptyParameter(array $array, $panel = false) {
        foreach ($array as $key => $val) {
            if (empty($val) || null == $val) {
                $data = array("response_string" => "$key is missing.", "error_code" => "1", "response_error_key" => "1");
                echo json_encode($data);
                exit;
            }
        }
    }

    public function checkEmptyParameter1(array $array, $panel = false) {
        foreach ($array as $key => $val) {
            if (empty($val) || null == $val) {
                $data = array("response_string" => "$key is missing.", "error_code" => "1", "response_error_key" => "1");
                echo json_encode($data);
                exit;
                //     $this->parameterMissing($panel);
            }
        }
    }

    public function uploadImage1($file, $target_path, $returnImageUrl) {

        $common = new Common_Api();

        $name = $file['name'];
        //   $ext=end(explode(".",$name));

        $ext = $this->getExtension($name);

        $newfilename = md5(time()) . "." . $ext;

        $target_path = $target_path . $newfilename;
        $file_path = $returnImageUrl . $newfilename;

        $allowedExts = array("GIF", "JPEG", "JPG", "PNG", "PJPEG", "X-PNG");

        $response = array();

        if (in_array(strtoupper($ext), $allowedExts)) {
            if (isset($file["error"]) && ($file["error"] != "")) {
                $response['error'] = $file['error'];
            } else {
                move_uploaded_file($file['tmp_name'], $target_path);
                $response['file_path'] = $file_path;
            }
        } else {
            $response['error'] = "Invalid_file";
        }
        return $response;
    }

    public function uploadImage($file, $folder = false) {

        $common = new Common_Api();
        $name = $file['name'];

        // $ext_set = explode(".",$name);

        $ext = $this->getExtension($name);

        $newfilename = md5(time() . rand()) . "." . $ext;

        $target_path = $_SERVER['DOCUMENT_ROOT'] . "/images/";
        if ($folder) {
            $target_path = $target_path . $folder . "/";
        }
        $target_path = $target_path . $newfilename;
        $allowedExts = array("GIF", "JPEG", "JPG", "PNG", "PJPEG", "X-PNG");
        $response = array();

        if (in_array(strtoupper($ext), $allowedExts)) {
            if (isset($file["error"]) && ($file["error"] != "")) {
                $response['error'] = $file['error'];
            } else {

                $getResponce = move_uploaded_file($file['tmp_name'], $target_path);
                $response['new_file_name'] = $newfilename;
            }
        } else {
            $response['error'] = "Invalid_file";
        }
        return $response;
    }

    public function getExtension($name) {
        $ext_set = explode(".", $name);
        $count = count($ext_set) - 1;

        return $ext_set[$count];
    }

    public function uploadMedia($file, $type = false) {

        $common = new Common_Api();
        $name = $file['name'];
        //    $ext=end(explode(".",$name));

        $ext = $this->getExtension($name);

        if ($type == "audio") {
            $ext = "mp3";
        } else {
            $ext = "mp4";
        }

        $newfilename = md5(time() . rand()) . "." . $ext;

        $target_path = $_SERVER['DOCUMENT_ROOT'] . "/upload/"; //$this->uploadpath.

        if ($folder) {
            $target_path = $target_path . $folder . "/";
        }

        $target_path = $target_path . $newfilename;
        $response = array();

        if (isset($file["error"]) && ($file["error"] != "")) {
            $response['error'] = $file['error'];
        } else {
            move_uploaded_file($file['tmp_name'], $target_path);
            $response['new_file_name'] = $newfilename;
        }
        return $response;
    }

    public function upload($file, $type = false) {
        $common = new Common_Api();

        if (is_array($file)) {
            if (count($file['name']) > 1) {
                $response = array();
                foreach ($file['name'] as $key => $value) {
                    $name = $value;
                    $ext = $this->getExtension($name);

                    $newfilename = md5(time() . rand()) . "." . $ext;
                    $target_path = $_SERVER['DOCUMENT_ROOT'] . "/testaments/" . $type . "/";

                    $target_path = $target_path . $newfilename;

                    if (isset($key["error"][$key]) && ($file["error"][$key] != "")) {
                        $response['error'][$key] = $file['error'][$key];
                    } else {
                        $result = move_uploaded_file($file['tmp_name'][$key], $target_path);
                        $response['new_file_name'][$key] = $newfilename;
                    }
                }
            } else {
                $name = $file['name'];
                $ext = $this->getExtension($name);

                $newfilename = md5(time() . rand()) . "." . $ext;
                $target_path = $_SERVER['DOCUMENT_ROOT'] . "/testaments/" . $type . "/";

                $target_path = $target_path . $newfilename;
                $response = array();

                if (isset($file["error"]) && ($file["error"] != "")) {
                    $response['error'] = $file['error'];
                } else {
                    $result = move_uploaded_file($file['tmp_name'], $target_path);
                    $response['new_file_name'] = $newfilename;
                }
            }
        }
        return $response;
    }

    public function uploadTestament($file, $type = FALSE) {
        include_once 's3/s3_config.php';
        //instantiate the class
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $bucket = "wa-testament";
        //$s3->putBucket($bucket, S3::ACL_PUBLIC_READ);
        $common = new Common_Api();
        $response = array();
        if (!empty($file)) {
            $newfilename = time() . ".text";
            $s3->putBucket($bucket, S3::ACL_PUBLIC_READ);
            if ($s3->putObjectFile($_SERVER['DOCUMENT_ROOT'] . $file, $bucket, $newfilename, S3::ACL_PUBLIC_READ)) {
                $response['new_file_name'] = $this->testament_bucket . $newfilename;
            } else {
                $response['error'] = 'error';
            }
        }
        return $response;
    }

    public function uploadS3Bucket($bucket, $file, $folder = false) {
        include_once 's3/s3_config.php';
        //instantiate the class
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $common = new Common_Api();
        $response = array();
        if (!empty($file)) {
            $ext = $this->getExtension($file['name']);
            $newfilename = rand() . time() . "." . $ext;
            $tmp = $file['tmp_name'];
            $s3->putBucket($bucket, S3::ACL_PUBLIC_READ);
            if ($s3->putObjectFile($tmp, $bucket, $folder . "/" . $newfilename, S3::ACL_PUBLIC_READ)) {
                $response['new_file_name'] = $this->wa_messages_bucket . $folder . '/' . $newfilename;
            } else {
                $response['error'] = 'error';
            }
        }
        return $response;
    }

    // function created by Sarvesh for WA-SOS attachment S3 09 Sep 2015

    public function uploadWABucket($bucket, $file) {
        include_once 's3/s3_config.php';
        //instantiate the class
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $common = new Common_Api();
        $response = array();
        if (!empty($file)) {
            $ext = $this->getExtension($file['name']);
            $newfilename = rand() . time() . "." . $ext;
            $tmp = $file['tmp_name'];
            $s3->putBucket($bucket, S3::ACL_PUBLIC_READ);
            if ($s3->putObjectFile($tmp, $bucket, $newfilename, S3::ACL_PUBLIC_READ)) {
                $response['new_file_name'] = $this->wa_sos_bucket . $newfilename;
            } else {
                $response['error'] = 'error';
            }
        }
        return $response;
    }

    public function randomAlphaNum($length) {
        // To generate a random alphaNumeric number
        $rangeMin = pow(36, $length - 1); //smallest number to give length digits in base 36
        $rangeMax = pow(36, $length) - 1; //largest number to give length digits in base 36
        $base10Rand = mt_rand($rangeMin, $rangeMax); //get the random number
        $newRand = base_convert($base10Rand, 10, 36); //convert it
        return $newRand; //spit it out
    }

    public function sendMail($to, $message, $subject) {
        $config = array(
            'ssl' => 'tls',
            'auth' => 'login',
            'port' => $this->configs->email_config->port,
            'username' => $this->configs->email_config->username,
            'password' => $this->configs->email_config->password
        );

        /* SMTP MAIL  START */
        $transport = new Zend_Mail_Transport_Smtp($this->configs->email_config->host, $config);
        $mail = new Zend_Mail('utf-8');
        $mail->setType(Zend_Mime::MULTIPART_RELATED);
        $mail->addTo($to);
        $mail->setFrom($this->configs->email_config->adminEmail, $this->configs->email_config->adminEmailFrom);
        $mail->setSubject($subject);

        $mail->setBodyHtml($message);
        try {
            $mail->send($transport);
            $mailMes = 1;
        } catch (Exception $e) {
            $mailMes = 0;
        }
    }

    /*
     * 	To send puch message on device(Android and iPhone)
     * 	@param $deviceType string // example android
     * 	@param $deviceToken string 
     * 	@param $payload array 
     */

    public function sendPushMessage($deviceType, $deviceToken, $payload) {
        try {
            if ($deviceToken != "1234") {
                if (strtolower($deviceType) == 'android') {
                    $jsonreturn = $this->andriodPush($deviceToken, $payload);

                    $jsonObj = json_decode($jsonreturn);
                    $result = $jsonObj->results;

                    $key = $result[0];

                    if ($jsonObj->failure > 0 and $key->error == 'Unavailable') {

                        $this->andriodPush($deviceToken, $payload);
                    }
                } else if (strtolower($deviceType) == 'iphone') {
                    $this->sendIosPush($deviceToken, $payload);
                }
            }
        } catch (Exception $e) {
            
        }
    }

    function sendIosPush2($deviceToken, $payload) {
        $apnsHost = 'gateway.sandbox.push.apple.com';
        //$apnsHost = 'gateway.push.apple.com';
        $apnsPort = '2195';

        $apnsCert = getcwd() . '/ckpem/ck.pem';
        $passPhrase = '';
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
        $apnsConnection = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);
        //$payload['aps'] = array('alert' =>$message, 'badge' => 1, 'sound' => 'default');

        $payload = json_encode($payload);
        $deviceToken = $deviceToken;
        $token = str_replace(' ', '', $deviceToken);
        if (!empty($payload)) {
            $apnsMessage = chr(o) . pack("n", 32) . pack('H*', $token) . pack("n", strlen($payload)) . $payload;
            if (fwrite($apnsConnection, $apnsMessage)) {
                echo "done";
                exit;
                return "true";
            } else {

                return "false";
            }
        }
    }

    public function sendIosPush($deviceToken, $payload) {
        $payload = json_encode($payload);
        //$apnsHost = 'gateway.push.apple.com';          /*for distribution */
        //change by Akhilesh Singh 7-Aug-2015
        $apnsHost = 'gateway.sandbox.push.apple.com';    /* for development  */
        $apnsPort = '2195';

        //$apnsCert = '/home/india/public_html/buyingbuddy_prod/application/models/agent_ck.pem';

        $apnsCert = getcwd() . '/ckpem/ck.pem';

        //echo file_get_contents($apnsCert);
        $passPhrase = '';
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
        $apnsConnection = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);
        if ($apnsConnection == false) {
            //  echo "false";die;
        }
        $apnsMessage = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;
        if (fwrite($apnsConnection, $apnsMessage)) {
            //   echo "Done";die;
        }
        fclose($apnsConnection);
        //die();
    }

    public function andriodPush($deviceToken, $payload) {
        $registrationIDs = array($deviceToken);

        $apiKey = 'AIzaSyCvmsHQlLAPibea-b85YvbBCwUODhsMxNU'; //'AIzaSyDxCV5w7JNra1enOJWmZwyQvt7XFS87Fnc';//Please change API Key AIzaSyAKdX-XBkYXfnTumTLfhOjOIzYX6W3ZWoc
        $url = 'https://android.googleapis.com/gcm/send';
        $push_data['payload'] = $payload;
        $fields = array(
            'registration_ids' => $registrationIDs,
            'data' => $push_data,
        );
        $headers = array(
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        $u = curl_setopt($ch, CURLOPT_URL, $url);
        $p = curl_setopt($ch, CURLOPT_POST, true);
        $f = curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $h = curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $t = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $c = curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $j = curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $jsonn = json_encode($fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function sendSms_recommend($userPhone, $message) {

        include_once 'twilio-php-latest/Services/Twilio.php';

        $sid = "AC39bef9d28845298afee88cd7e3730382";
        $token = "a8194f7963739b8c6c6b3fc7db96d331";
        $fromNumber = "+1 678-515-5551";
        //echo $userPhone;
        if (!empty($userPhone)) {
            try {
                $client = new Services_Twilio($sid, $token);
                $status = $client->account->sms_messages->create($fromNumber, $userPhone, $message, array());
                //print_r($client->get_error_msg());
                $st = $client->get_error_msg();
                $twillio_err = ($st[1] == 400) ? ($st[0]) : '';
                //$this->printJson($st[1]."<br>".$userPhone);
            } catch (Exception $e) {
                //echo $e->getMessage();exit;
            }
        }
    }

    public function sendSms_recommend1($userPhone, $message) {

        include_once 'twilio-php-latest/Services/Twilio.php';

        $sid = "AC39bef9d28845298afee88cd7e3730382";
        $token = "a8194f7963739b8c6c6b3fc7db96d331";
        $fromNumber = "+1 678-515-5551";
        //echo $userPhone;
        if (!empty($userPhone)) {
            try {
                $client = new Services_Twilio($sid, $token);
                $status = $client->account->sms_messages->create($fromNumber, $userPhone, $message, array());
                //print_r($client->get_error_msg());
                $st = $client->get_error_msg();
                print_r($st);
                $twillio_err = ($st[1] == 400) ? ($st[0]) : '';
                //$this->printJson($st[1]."<br>".$userPhone);
            } catch (Exception $e) {
                //echo $e->getMessage();exit;
            }
        }
    }

    /**
     *  function for getting phone with country code
     */
    public function getPhoneWithCode($phone, $countryCode) {

        $check_plus_position = strpos($phone, '+');
        $check_zero_position = strpos($phone, '0');

        if ($check_plus_position === 0) {
            return $phone;
        } else {
            if ($check_zero_position === 0) {
                return "+" . trim($countryCode) . substr($phone, 1);
            } else {
                return "+" . trim($countryCode) . $phone;
            }
        }
    }

    /**
     *  function for getting tiny url
     */
    public function get_tiny_url($url) {
        return $url;
        $tiny = 'http://tinyurl.com/api-create.php?url=';

        return file_get_contents($tiny . urlencode(trim($url)));
    }

    /**
     *  check user is login or not
     */
    public function isUserLogin($userId, $deviceId) {
        $userSettingTable = new Application_Model_DbTable_UserSetting();

        $select = $userSettingTable->select()
                ->where('userId =?', $userId)
                ->where('userDeviceId =?', $deviceId);

        if ($userSettingRow = $userSettingTable->fetchRow($select)) {
            return true;
        }

        $this->displayMessage('You are not login', "10", array(), "10");
    }

    public function test($userPhone, $message) {
        include_once 'twilio-php-latest/Services/Twilio.php';

        $sid = "AC39bef9d28845298afee88cd7e3730382";
        $token = "a8194f7963739b8c6c6b3fc7db96d331";
        $fromNumber = "+1 678-515-5551";

        if (!empty($userPhone)) {
            try {
                $client = new Services_Twilio($sid, $token);
                $status = $client->account->sms_messages->create($fromNumber, $userPhone, $message, array());

                $st = $client->get_error_msg();
                $twillio_err = ($st[1] == 400) ? ($st[0]) : '';
            } catch (Exception $e) {
                //  echo $e->getMessage();exit;
            }
        }
    }

    public function sendCurlRequestForPush($data) {
        $url = $this->basemedia . "push/curl-request";

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

    public function createMpdf($header, $htmlcontent, $footer) {
        include_once "mpdf/mpdf.php";
        $mpdf = new mPDF("utf-8", "A4");
        $mpdf->defaultheaderline = 0;
        $mpdf->defaultheaderfontsize = 22;
        $mpdf->SetHTMLHeader($header);
        $mpdf->setAutoTopMargin = "stretch";
        $mpdf->autoMarginPadding = 12;
        $mpdf->allow_charset_conversion = true;
        $mpdf->charset_in = "windows-1252";
        $mpdf->defaultfooterline = 0;
        $mpdf->defaultfooterfontsize = 5;
        $mpdf->SetHTMLFooter($footer);
        $mpdf->WriteHTML($htmlcontent);
        $pdf = $mpdf->Output('', 'S');
        $result = $this->fnEncrypt($pdf);
        return $result;
    }

    public function fnEncrypt($contents) {
        $_cipher = MCRYPT_RIJNDAEL_128;
        $_mode = MCRYPT_MODE_CBC;
        $_key = "12345678911234567891123456789112";
        $_initializationVectorSize = 0;

        $blockSize = mcrypt_get_block_size($_cipher, $_mode);
        $pad = $blockSize - (strlen($contents) % $blockSize);
        $iv = mcrypt_create_iv($_initializationVectorSize, MCRYPT_DEV_URANDOM);

        $text = $iv . mcrypt_encrypt(
                        $_cipher, $_key, $contents . str_repeat(chr($pad), $pad), $_mode, $iv
        );
        $name = md5(time() . rand());
        $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . "/testaments/pdf/" . $name . ".text", "w") or die("Unable to open file!");
        fwrite($myfile, base64_encode($text));
        fclose($myfile);
        $result = "/testaments/pdf/" . $name . ".text";
        return $result;
    }

    public function fnDecrypt($file) {
        $sValue = file_get_contents($file);
        //echo $sValue; exit;
        $_cipher = MCRYPT_RIJNDAEL_128;
        $_mode = MCRYPT_MODE_CBC;
        $_key = utf8_encode("12345678911234567891123456789112");

        $_initializationVectorSize = 0;
        $initializationVector = substr(base64_decode($sValue), 0, $_initializationVectorSize);
        $data = mcrypt_decrypt(
                $_cipher, $_key, substr(base64_decode($sValue), $_initializationVectorSize), $_mode, $initializationVector
        );
        $pad = ord($data[strlen($data) - 1]);
        $text = substr($data, 0, -$pad);
        $name = md5(time() . rand());
        $pdf = fopen($_SERVER['DOCUMENT_ROOT'] . "/testaments/pdf/" . $name . ".pdf", 'w');
        fwrite($pdf, $text);
        $result = "/testaments/pdf/" . $name . ".pdf";
        return $result;
    }

    public function setEncrypt($data) {
        $encryption_key = "@#$%^&*!";
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($data), MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypted_string);
    }

    public function setDecrypt($data) {
        $encryption_key = utf8_encode("@#$%^&*!");
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, base64_decode($data), MCRYPT_MODE_ECB, $iv);
        return $decrypted_string;
    }

    /// created By sarvesh fb share iphone 18 aug 2015 //// 
    public function fbShare($fbData, $sosData, $file_type=false, $file_url=false) {
        include_once 'facebook-php-sdk-v4/src/Facebook/';
        include_once 'facebook-php-sdk-v4/src/Facebook/autoload.php';

        $app_id = $this->configs->facebook->appid;
        $app_secret = $this->configs->facebook->appsecret;

        $fb = new Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.2',
        ]);

        try {
            // Returns a `Facebook\FacebookResponse` object          
            if ($sosData['is_send'] == 0) {
                $messageData = [
                    'message' => $sosData['text'],
                ];
                $response = $fb->post('/me/feed', $messageData, $fbData['tokenString']);
            }

            if (!empty($file_type) && ($file_type == 0)) {
                $linkData = [
                    'link' => $file_url,
                ];
                $response = $fb->post('/me/feed', $linkData, $fbData['tokenString']);
            }

            if (!empty($file_type) && ($file_type == 1)) {
                $videoData = [
                    'source' => $fb->videoToUpload($file_url),
                ];
                $response = $fb->post('me/videos', $videoData, $fbData['tokenString']);
            }

            if (!empty($file_type) && ($file_type == 2)) {
                $imageData = [
                    'source' => $fb->fileToUpload($file_url),
                ];
                $response = $fb->post('me/photos', $imageData, $fbData['tokenString']);
            }
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit();
        }
    }

    public function twShare($twData, $sosData, $file) {
        include_once 'twitter-oauth/twitteroauth.php';
        
	$twitter_oauth_tokens        = $twData['twitter_oauth_tokens'];
	$twitter_oauth_secret_tokens = $twData['twitter_oauth_secret_tokens'];

	$connection = new TwitterOAuth($this->twitter_details->CONSUMER_KEY, $this->twitter_details->CONSUMER_SECRET, $twitter_oauth_tokens, $twitter_oauth_secret_tokens);
       
        if(!empty($sosData['text']) && ($sosData['is_send'] == 0)){
            $text = $sosData["text"];
	    $result = $connection->post('statuses/update', array('status' => "$text"));
        }

        //$file =  "http://52.4.193.184/new_images/ban_pic2.png"; //$file['tmp_name'];//

        if(!empty($file)){
	    $status_message = "";
	    $result = $connection->upload('statuses/update_with_media', array('status' => $status_message, 'media[]' =>  file_get_contents($file)));
        }

        //echo json_encode($result); exit;
       return $result;
    }
    
    public function twShare1($twData, $sosData, $file_type, $file_url) {
        include_once 'twitter-oauth/twitteroauth.php';

        $text = $sosData["text"];
        
	$twitter_oauth_tokens = $twData['twitter_oauth_tokens'];
	$twitter_oauth_secret_tokens = $twData['twitter_oauth_secret_tokens'];
        //'3314794056-bMyNZik6LnYxYwdn0MwKb2SevWhghps6HdRxl4c', '38PxnMLF6OjShozQLJRqcQ68NAl11Fukqq15AIGqVTtlc'
        $connection = new TwitterOAuth($this->twitter_details->CONSUMER_KEY, $this->twitter_details->CONSUMER_SECRET,$twitter_oauth_tokens,$twitter_oauth_secret_tokens);

       	$res = $connection->post('statuses/update', array('status' => "$text"));
        echo '<pre>';
        print_r($connection);
        //print_r($res);
        exit;
	//echo 'ca'; exit;
    }
    
    /// created By sarvesh fb share iphone 18 aug 2015 //// 
     public function fbAccess(){
  	include_once 'facebook-php-sdk-v4/src/Facebook/';
        include_once 'facebook-php-sdk-v4/src/Facebook/autoload.php';

	$app_id = $this->configs->facebook->appid;
        $app_secret = $this->configs->facebook->appsecret; 
	$fb = new Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.2',
        ]);

	$helper = $fb->getRedirectLoginHelper();

	$permissions = ['publish_actions']; // Optional permissions
	$loginUrl = $helper->getLoginUrl('http://52.4.193.184/wa-sos/fb-test1', $permissions);

	echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
    }

    public function getfbLoginToken(){
  	include_once 'facebook-php-sdk-v4/src/Facebook/';
        include_once 'facebook-php-sdk-v4/src/Facebook/autoload.php';

	// init app with app id and secret

        $app_id = $this->configs->facebook->appid;
        $app_secret = $this->configs->facebook->appsecret; 
	$fb = new Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.2',
        ]);

	$helper = $fb->getRedirectLoginHelper();  
	  
	try {  
	  $accessToken = $helper->getAccessToken();  
	} catch(Facebook\Exceptions\FacebookResponseException $e) {  
	  // When Graph returns an error  
	  echo 'Graph returned an error: ' . $e->getMessage();  
	  exit;  
	} catch(Facebook\Exceptions\FacebookSDKException $e) {  
	  // When validation fails or other local issues  
	  echo 'Facebook SDK returned an error: ' . $e->getMessage();  
	  exit;  
	}  

	if (! isset($accessToken)) {  
	  if ($helper->getError()) {  
	    header('HTTP/1.0 401 Unauthorized');  
	    echo "Error: " . $helper->getError() . "\n";
	    echo "Error Code: " . $helper->getErrorCode() . "\n";
	    echo "Error Reason: " . $helper->getErrorReason() . "\n";
	    echo "Error Description: " . $helper->getErrorDescription() . "\n";
	  } else {  
	    header('HTTP/1.0 400 Bad Request');  
	    echo 'Bad request';  
	  }  
	  exit;  
	}  

	// Logged in  
	echo '<h3>Access Token</h3>';  
	var_dump($accessToken->getValue());  
	  
	// The OAuth 2.0 client handler helps us manage access tokens  
	$oAuth2Client = $fb->getOAuth2Client();  

	// Get the access token metadata from /debug_token  
	$tokenMetadata = $oAuth2Client->debugToken($accessToken);  
	echo '<h3>Metadata</h3>';  
	var_dump($tokenMetadata);  
	  
	// Validation (these will throw FacebookSDKException's when they fail)  
	$tokenMetadata->validateAppId($app_id);  
	// If you know the user ID this access token belongs to, you can validate it here  
	// $tokenMetadata->validateUserId('123');  
	$tokenMetadata->validateExpiration();   
	   
	if (! $accessToken->isLongLived()) {  
	  // Exchanges a short-lived access token for a long-lived one  
	  try {  
	    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);  
	  } catch (Facebook\Exceptions\FacebookSDKException $e) {  
	    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>";  
	    exit;  
	  } 
	  echo '<h3>Long-lived</h3>';  
	  var_dump($accessToken->getValue());  
	}

	$_SESSION['fb_access_token'] = (string) $accessToken;  
	  
	// User is logged in with a long-lived access token.  
	// You can redirect them to a members-only page.  
	// header('Location: https://example.com/members.php');
    } 
}

