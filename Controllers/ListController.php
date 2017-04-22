<?php

class ListController extends My_Controller_Abstract {

    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->enableLayout();
    }

    public function indexAction() {
        $userTable = new Application_Model_DbTable_UsersBackup();
        $language = $this->getRequest()->getParam('language', '');
        $this->view->userList = $userTable->getUserList($language);
        
//         $this->sendCurlRequest(array('phone'=>'+905428196982','country'=>'Turkey',"deviceType"=>'android'));
//         $this->sendCurlRequest(array('phone'=>'+905428196982','country'=>'Turkey',"deviceType"=>'iphone'));
//            
//            die();
        if ($this->getRequest()->isPost()) {
            $userEmail = $this->getRequest()->getPost('userEmail', '');
            $country = $this->getRequest()->getPost('country', '');
            $phoneWithCode = $this->getRequest()->getPost('phoneWithCode', '');
            $deviceType = $this->getRequest()->getPost('deviceType', '');
            $userNickName = $this->getRequest()->getPost('userNickName', '');
            $userId = $this->getRequest()->getPost('userId', '');
            $i = 0;
//            $this->sendCurlRequest(array('phone'=>'+905428196982','country'=>'Turkey',"deviceType"=>'android'));
//            $this->sendCurlRequest(array('phone'=>'+905428196982','country'=>'Turkey',"deviceType"=>'iphone'));
//            
//            die();
            foreach ($userEmail as $data) {
                $this->sendCurlRequest(array("deviceType" => $deviceType[$i], "phone" => $phoneWithCode[$i], 'deviceToken' => '11', 'country' => $country[$i], 'userEmail' =>$data));
                $i++;
            }
            echo "Total sent:-".$i;
            die();
        }
    }

    public function index2Action() {
        echo $message ="WA-kullanıcısı, servislerimizi geliştirmemiz için hesabınızı silmeniz gerekmekte.Lütfen uygulamayı telefonunuzdan silin. Yeni sürümün bağlantısını göndereceğiz";
        $this->common->sendSms_recommend('+905428196982', $message);
        die();
        $this->sendCurlRequest(array("userId" => 1, "phone" => "+905428196982", 'deviceToken' => 111, 'country' => "Turkey", 'userEmail' => 'akh@wa-app.com', 'name' => 'ak'));
        die();
        $userTable = new Application_Model_DbTable_Users();
        $language = $this->getRequest()->getParam('language', '');
        $this->view->userList = $userTable->getUserList($language);
//        $userEmail = "akh@wa-app.com";
//        $para = array(
//            'name'=>'Akh',
//             'message1'=>'message1',
//            'message2'=>'messag2',
//        );
//        $this->user->sendmail($para,'notification.phtml',$userEmail,'Welcome To WA-app');
//        die();
//        $userPhoneNumber = "+905428196982";
        // $this->common->sendSms_recommend($userPhoneNumber,$message);
//        $this->sendCurlRequest(array('userEmail' => 'ak'));
//        die();
          //$this->sendCurlRequest(array("userId" => 1, "phone" => "+905072422467", 'deviceToken' => '11', 'country' => "Turkey", 'userEmail' => 'iwan@wa-app.com', 'name' => 'akh'));
         // $this->sendCurlRequest(array("userId" => 1, "phone" => "+905428196982", 'deviceToken' => '11', 'country' => "Turkey", 'userEmail' => 'iwan@wa-app.com', 'name' => 'akh'));
        if ($this->getRequest()->isPost()) {
            $userEmail = $this->getRequest()->getPost('userEmail', '');
            $country = $this->getRequest()->getPost('country', '');
            $phoneWithCode = $this->getRequest()->getPost('phoneWithCode', '');
            $userDeviceToken = $this->getRequest()->getPost('userDeviceToken', '');
            $userNickName = $this->getRequest()->getPost('userNickName', '');
            $deviceType = $this->getRequest()->getPost('deviceType', '');
            $userId = $this->getRequest()->getPost('userId', '');
            $i = 0;

            foreach ($userEmail as $data) {
//                if($data){
//                     $this->user->sendmail(array('name'=>$userNickName[$i]),'notification.phtml',$userEmail[$i],'We are improving');
//                }
//                if($phoneWithCode[$i]){
//                   $userPhoneNumber =$phoneWithCode[$i];
//                   $this->common->sendSms_recommend($userPhoneNumber,$message); 
//                }
//                if($userDeviceToken[$i]){
//                   $token =$userDeviceToken[$i];
//                   $this->common->sendSms_recommend($userPhoneNumber,$message); 
//                }
                $this->sendCurlRequest(array("userId" => $userId[$i], "phone" => "+905428196982", 'deviceToken' => $userDeviceToken[$i], 'country' => "Turkey", 'userEmail' => 'akh@wa-app.com', 'name' => $userNickName[$i]));
                $i++;
            }
        }
    }

    public function sendCurlRequest($data) {
        $url = "http://wa-app.com/list/send";
        $ch = curl_init();
        $u = curl_setopt($ch, CURLOPT_URL, $url);
        $p = curl_setopt($ch, CURLOPT_POST, true);
        $f = curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $h = curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $t = curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $c = curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $j = curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //$jsonn = json_encode($fields);
        $result = curl_exec($ch);
        print_r($result);
    }

    public function sendAction() {
//                $phone = $this->getRequest()->getPost('phone');
//                $message="Utenti WA, per migliorare i nostri servizi, bisogno cancellare il tuo conto. Si consiglia eliminare l’app del tel. Un collegamento con la nuova versione seguirà";
//                $this->common->sendSms_recommend($phone, $message);
//                $message ="WA-user, to improve our services we must delete your account. Please delete the app from your phone. We will send you a new link with the new version";
//                $this->common->sendSms_recommend($phone, $message);
//       // die();
        $userEmail = $this->getRequest()->getPost('userEmail','');
        $userId = $this->getRequest()->getPost('userId');
        $phone = $this->getRequest()->getPost('phone');
        $country = $this->getRequest()->getPost('country');
        $name = $this->getRequest()->getPost('name', '');
        $deviceType = $this->getRequest()->getPost('deviceType');
       //die();
       $this->printJson($phone);
        if ($userEmail) {
            $this->printJson($userEmail);
            $this->user->sendmail(array('name' => $name), 'notificationemail.phtml', $userEmail, 'We are improving');
        }

        //sending sms
//        if ($phone) {
//            if($deviceType=='iphone'){
//                if ($country == "Turkey") {
//                    $message ="‘’All-in-WA’’ yeni iOS versiyonuyla kullanima hazir: https://itunes.apple.com/us/app/wa-app/id982136038?ls=1&mt=8";
//                    $this->common->sendSms_recommend($phone, $message);
//    //                $message ="WA-user, to improve our services we must delete your account. Please delete the app from your phone. We will send you a new link with the new version";
//    //                $this->common->sendSms_recommend($phone, $message);
//                } else if ($country == "Italy") {
//                    $message='La nuova versione iOS di "All-in-WA" ora e disponibile, segui:https://itunes.apple.com/us/app/wa-app/id982136038?ls=1&mt=8';
//                    $this->common->sendSms_recommend($phone, $message);
//    //                $message ="WA-user, to improve our services we must delete your account. Please delete the app from your phone. We will send you a new link with the new version";
//    //                $this->common->sendSms_recommend($phone, $message);
//                } else if ($country == "France") {
//                    $message='Nouvelle version iOS de “All-in-WA" maintenant disponible, suivez ce lien: https://itunes.apple.com/us/app/wa-app/id982136038?ls=1&mt=8';
//                    $this->common->sendSms_recommend($phone, $message);
//    //                $message ="WA-user, to improve our services we must delete your account. Please delete the app from your phone. We will send you a new link with the new version";
//    //                $this->common->sendSms_recommend($phone, $message);
//                } else {
//                    $message ="New “All-in-WA” iOS version is now available, please go to: https://itunes.apple.com/us/app/wa-app/id982136038?ls=1&mt=8";
//                    $this->common->sendSms_recommend($phone, $message);
//                }
//            }
//            if($deviceType=='android'){
//                if ($country == "Turkey") {
//                    $message ="‘’All-in-WA’’ yeni Android versiyonuyla kullanima hazir: https://play.google.com/store/apps/details?id=com.wa_app";
//                    $this->common->sendSms_recommend($phone, $message);
//                } else if ($country == "Italy") {
//                    $message='La nuova versione android di "All-in-WA" ora e disponibile, segui: https://play.google.com/store/apps/details?id=com.wa_app';
//                    $this->common->sendSms_recommend($phone, $message);
//                } else if ($country == "France") {
//                    $message='Nouvelle version “android de All-in-WA" maintenant disponible, suivez ce lien: https://play.google.com/store/apps/details?id=com.wa_app';
//                    $this->common->sendSms_recommend($phone, $message);
//                } else {
//                    $message ="New “All-in-WA” android version is now available, please go to: https://play.google.com/store/apps/details?id=com.wa_app";
//                    $this->common->sendSms_recommend($phone, $message);
//                }
//            }
//             
//        }
        //sending push notification to the users
//         $message="Değerli WA-kullanıcısı, servislerimizi geliştirmemiz adına, bir defaya mahsus hesabınızı ve uygulamanın şu an kullandığınız sürümünü silmenizi rica ediyoruz. Yeni versiyonun linkini sizlere göndereceğiz. Teşekkürler WA-takımı";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage('iphone', "", $payload);
//                    die();
//        $userSettingTable = new Application_Model_DbTable_UserSetting();
//        $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($userId);
//        $message = "";
//        foreach ($userLoginDeviceRowset as $loginDeviceRow) {
//
//            if ($loginDeviceRow->userDeviceType != "web") {
//                if ($country == "Turkey") {
//                    $message="Değerli WA-kullanıcısı, servislerimizi geliştirmemiz adına, bir defaya mahsus hesabınızı ve uygulamanın şu an kullandığınız sürümünü silmenizi rica ediyoruz. Yeni versiyonun linkini sizlere göndereceğiz. Teşekkürler WA-takımı";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                    $message="Dear WA-user, in order to improve our services we need to delete your account and, for one time only, please delete the current version of the app from your phone. We will send you a new link with the new version. Thank you. WA-team";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                } else if ($country == "Italy") {
//                    $message="Caro Utenti WA, per migliorare i nostri servizi, abbiamo bisogno di cancellare il tuo conto e per una sola volta, si consiglia di eliminare la versione corrente del telefono. Vi invieremo un collegamento con la nuova versione presto. Grazie. WA-team.";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                    $message="Dear WA-user, in order to improve our services we need to delete your account and, for one time only, please delete the current version of the app from your phone. We will send you a new link with the new version. Thank you. WA-team";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                } else if ($country == "France") {
//                    $message="Cher membre WA, afin d'améliorer nos services, nous devons supprimer votre compte et, pour une fois seulement, veuillez supprimer la version actuelle de votre telephone. Nous vous enverrons un lien avec la nouvelle version. Merci. WA-team";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                    $message="Dear WA-user, in order to improve our services we need to delete your account and, for one time only, please delete the current version of the app from your phone. We will send you a new link with the new version. Thank you. WA-team";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                } else {
//                    $message="Dear WA-user, in order to improve our services we need to delete your account and, for one time only, please delete the current version of the app from your phone. We will send you a new link with the new version. Thank you. WA-team";
//                    if ($loginDeviceRow->userDeviceType == "iphone") {
//                        $payload['aps'] = array('alert' => $message, 'badge' => 0, 'type' => 'we_improve');
//                    } else {
//                        $payload = array(
//                            'message' => $message,
//                            'type' => "we_improve",
//                        );
//                        $payload = json_encode($payload);
//                    }
//                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType, $loginDeviceRow->userDeviceToken, $payload);
//                }
        // }
        //}
    }
    function printJson($decoded,$file='email'){
	$filePath = getcwd()."/".$file."."."txt";
        $myfile = fopen($filePath, "a+") or die("Unable to open file!");
        
	chmod($filePath, 0777);
        
        $json = json_encode($decoded);
        
        fwrite($myfile, $json);
        $date = date("Y-m-d H:i:s").PHP_EOL;
        fwrite($myfile,$date);
        fclose($myfile);
    }
    public function termsAction() {
        
    }

    public function featuresAction() {
        
    }

    public function contactAction() {
        
    }

    public function aboutusAction() {
        
    }

}

?>
