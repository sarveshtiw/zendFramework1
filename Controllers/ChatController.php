<?php

/* this controller is for website
 * Author name : akhilesh singh
 * Date Created : 24-3-2015
 * Description: used for chatting
 */

class ChatController extends My_Controller_Admin {

    public function preDispatch() {
        parent::preDispatch();
    }

    public function init() {
        $this->_helper->layout->enableLayout();
        //Zend_Layout::getMvcInstance()->setLayout('new_layout');
        
        $this->view->menu = "CHAT";
    }

    public function indexAction() {
        $friendTable = new Application_Model_DbTable_Friends();
        $this->view->mesg = $this->_helper->FlashMessenger->getMessages('group_create');
        $languageTranslate = new Application_Model_DbTable_LanguageTranslate();
        $groupTable = new Application_Model_DbTable_Groups();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $groupId = $this->getRequest()->getParam('groupId', '');
        $secGroupId = $this->getRequest()->getParam('secGroupId', '');
        $languageList = $languageTranslate->getList();
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId);//$friendTable->myfriends($this->loggedUserRow->userId, true);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);
        $this->view->groupDetails = $groupDetails = ($groupId)?$groupTable->getGroupDetails($groupId):$secGroupTable->getGroupDetails($secGroupId);
        $this->view->userList = $paginator;
        $this->view->languageList = $languageList;
        $this->view->groupId = $groupId;
        $this->view->secGroupId = $secGroupId;
    }
    
//    public function chatAction() {
//        $id = $this->getRequest()->getParam('id','');
//        $this->_helper->layout->setLayout('new_layout');
//        $friendTable = new Application_Model_DbTable_Friends();
//        $this->view->mesg = $this->_helper->FlashMessenger->getMessages('group_create');
//        $languageTranslate = new Application_Model_DbTable_LanguageTranslate();
//        $groupTable = new Application_Model_DbTable_Groups();
//        $secGroupTable = new Application_Model_DbTable_SecretGroups();
//        $groupId = $this->getRequest()->getParam('groupId', '');
//        $secGroupId = $this->getRequest()->getParam('secGroupId', '');
//        $languageList = $languageTranslate->getList();
//        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId,true);//$friendTable->myfriends($this->loggedUserRow->userId, true);
//        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
//        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
//        $paginator->setItemCountPerPage(116);
//        $this->view->groupDetails = $groupDetails = ($groupId)?$groupTable->getGroupDetails($groupId):$secGroupTable->getGroupDetails($secGroupId);
//        $this->view->userList = $paginator;
//        $this->view->languageList = $languageList;
//        $this->view->groupId = $groupId;
//        $this->view->secGroupId = $secGroupId;
//        $this->view->user_select_id=isset($id)?$this->common->decrypt($id):"";
//        $this->renderScript('chat/chat3.phtml');
//    }
//    
     public function chatAction() {
        $id = $this->getRequest()->getParam('id','');
        $friendTable = new Application_Model_DbTable_Friends();
        $this->view->mesg = $this->_helper->FlashMessenger->getMessages('group_create');
        $languageTranslate = new Application_Model_DbTable_LanguageTranslate();
        $groupTable = new Application_Model_DbTable_Groups();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $groupId = $this->getRequest()->getParam('groupId', '');
        $secGroupId = $this->getRequest()->getParam('secGroupId', '');
        $languageList = $languageTranslate->getList();
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId,true);//$friendTable->myfriends($this->loggedUserRow->userId, true);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);
        $this->view->groupDetails = $groupDetails = ($groupId)?$groupTable->getGroupDetails($groupId):$secGroupTable->getGroupDetails($secGroupId);
        $this->view->userList = $paginator;
        $this->view->languageList = $languageList;
        $this->view->groupId = $groupId;
        $this->view->secGroupId = $secGroupId;
        $this->view->user_select_id=isset($id)?$this->common->decrypt($id):"";
        $this->renderScript('chat/chat.phtml');
    }
    
     public function chat2Action() {
         // Prints the day
         echo date("Y-m-d H:i:s");
echo gmdate("l") . "<br>";
echo $startTime = date("Y-m-d H:i:s", strtotime('330 minutes', time()));
// Prints the day, date, month, year, time, AM or PM
echo gmdate("l jS \of F Y h:i:s A") . "<br>";
echo date("l jS \of F Y h:i:s A") . "<br>";

        $decoded            = $this->common->Decoded();
        $this->printJson($decoded);
        die();
        //$this->_helper->layout->disableLayout();
        $this->_helper->layout->setLayout('new_layout');
        $friendTable = new Application_Model_DbTable_Friends();
        $this->view->mesg = $this->_helper->FlashMessenger->getMessages('group_create');
        $languageTranslate = new Application_Model_DbTable_LanguageTranslate();
        $groupTable = new Application_Model_DbTable_Groups();
        $secGroupTable = new Application_Model_DbTable_SecretGroups();
        $groupId = $this->getRequest()->getParam('groupId', '');
        $secGroupId = $this->getRequest()->getParam('secGroupId', '');
        $languageList = $languageTranslate->getList();
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId);//$friendTable->myfriends($this->loggedUserRow->userId, true);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);
        $this->view->groupDetails = $groupDetails = ($groupId)?$groupTable->getGroupDetails($groupId):$secGroupTable->getGroupDetails($secGroupId);
        $this->view->userList = $paginator;
        $this->view->languageList = $languageList;
        $this->view->groupId = $groupId;
        $this->view->secGroupId = $secGroupId;
    }
    
    public function getFriendsListAction() {
        $this->_helper->layout()->disableLayout();
        $friendTable = new Application_Model_DbTable_Friends();
        $this->loggedUserRow->userId;
        $select = $friendTable->getMyFriendListChat($this->loggedUserRow->userId);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);
        $this->view->userList = $paginator;
        
    }
    
     public function chat1Action() {
         $phone="+905428196982";
         $message ="Welcome to WA-app use following link ios: ".$android_app_link." android: ".$ios_app_link;
                                
                                $this->common->sendSms_recommend($phone,$message);
                                die();
         $this->createQuickBloxGroup('132','secrete_Group',array('55040','55042'));
        //$this->createThumbnailUrl('https://qb-wa-media.s3.amazonaws.com/2dbb4e5c5c54424fabac4e127fe5539e00','/a.jpg');die();
        $friendTable = new Application_Model_DbTable_Friends();
        $languageTranslate = new Application_Model_DbTable_LanguageTranslate();
        $languageList = $languageTranslate->getList();
        $select = $friendTable->myfriends($this->loggedUserRow->userId, true);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
        $paginator->setCurrentPageNumber($this->getRequest()->getParam('page', '1'));
        $paginator->setItemCountPerPage(116);

        $this->view->userList = $paginator;
        $this->view->languageList = $languageList;
        //print_r($userList);die();
    }
    function searchLanguageAction(){
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $searchText = $this->getRequest()->getParam('q','');
        $languageTranslateTable =  new Application_Model_DbTable_LanguageTranslate();
        $result = $languageTranslateTable->searchLanguage($searchText);
        echo json_encode($result);
        die();
    }
    
     function changeLanguageAction(){
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $message = $this->getRequest()->getPost('message');
        $id=$this->getRequest()->getPost("id");
        $code = $this->getRequest()->getPost("code","");
        $dialog_id = $this->getRequest()->getPost("dialog_id","");
        $current_language=$this->getRequest()->getPost("current_language");
        $languageTranslateTable =  new Application_Model_DbTable_DialogMessage();
        $result = $languageTranslateTable->changeLanguage($message,$id);
        $languageTranslateTable->updateDialogLanguage(array('language'=>$current_language,'src_lang'=>$code),$dialog_id);
        setcookie("translate_language", $current_language, strtotime( '+365 days' ),"/");
        echo json_encode($result);
        die();
    }
    public function sendNotificationAction() {
        $userTable = new Application_Model_DbTable_Users();
        $notificationTable = new Application_Model_DbTable_UserNotifications();
        $userId = $this->getRequest()->getPost("from_user", 0);
        $receiverId = $this->getRequest()->getPost("user_id", 0);
        $message = $this->getRequest()->getPost("message", "");
        $result = $userTable->getRowByQuickBloxId(array($userId, $receiverId));
        if ($userId == $result[0]['quickBloxId']) {
            $fromUserId = $result[0]['userId'];
            $userId = $result[1]['userId'];
            $userName = $result[0]['userNickName'];
        } else {
            $fromUserId = $result[1]['userId'];
            $userId = $result[0]['userId'];
            $userName = $result[1]['userNickName'];
        }

        $message = $userName . " : " . $message;
        $notificationTable->createNotification($fromUserId, $userId, $message, 7);

        exit();
    }

    public function getChatHistoryAction() {
        $sender_id = $this->getRequest()->getParam('sender_id', 0);
        $recipient_id = $this->getRequest()->getParam('recipient_id', 0);
        $type = $this->getRequest()->getParam('type', 0);
        $password = $this->getRequest()->getParam('password', null);
        $id = $this->getRequest()->getParam('id', null);
        
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
        $result = $dialogMessageTable->geChatHistory($sender_id, $recipient_id, $type,$id);
        echo json_encode($result);
        die();
    }
    /*
     * used for getting the secChat group history
     */
     public function getSecChatHistoryAction() {
        $sender_id = $this->getRequest()->getParam('sender_id', 0);
        $dialog_id = $this->getRequest()->getParam('id', 0);
        $groupId = $this->getRequest()->getParam('groupId', 0);
        $password = $this->getRequest()->getParam('password', null);
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
        $result = $dialogMessageTable->getSecChatHistory($this->loggedUserRow->userId,$sender_id, $dialog_id, $groupId,$password);
        echo json_encode($result);
        die();
    }

    public function sendChatMessageAction() {
        $receiver_id = $this->getRequest()->getPost("receiver_id", 0);
        $message = $this->getRequest()->getPost("message", 0);
        $ext = end(explode(".",$this->getRequest()->getPost("fileName", 0)));
        $chat_type = $this->getRequest()->getPost("chat_type", 0);
        $dialog_id = $this->getRequest()->getPost('chat_dialog','');
       // $imageurl = $this->getRequest()->getPost('thumbnail');die();
        $url = 'https://qb-wa-media.s3.amazonaws.com/'.$this->getRequest()->getPost("url", '');
        $file_uid = $this->getRequest()->getPost("id", '');
       // $type = ($ext=='mp3')?"audio":($ext=='mp4')?"video":"photo";
        if($ext=='mp3'){
            $imageurl = $this->baseUrl()."/www/images/audio.png";
            $thumbStr = $this->createThumbImage($imageurl);
            $type="audio";
        }elseif($ext=='mp4'){
            $imageurl = $this->getRequest()->getPost('thumbnail');
            $thumbStr = $this->createThumbImage($imageurl);
            $type="video";
        }else{
            $thumbStr = $this->createThumbImage($url);
            $type="photo";
        }
        //echo $thumbStr;
        //die();
        $array = (object) array('userEmail' => $this->loggedUserRow->userEmail);
        $token = $this->quickBloxUserToken($array);
        $chat_dialog_id = $this->createDialogId($token, $receiver_id);
        $data = array(
            'chat_dialog_id' => ($chat_type=='groupchat')?$dialog_id:$chat_dialog_id,
            'message' => $type,
            "_id"=>$this->getRequest()->getPost("msg_id", 0),
            'typeStr'=>$type,
            'urlStr'=>$url,
            'nick'=>$this->loggedUserRow->userNickName,
            "send_to_chat" => 1,
            "markable" => 1,
            "deviceId" => 'web',
            "localId" => 'web',
            "thumbDataStr" => $thumbStr,
        );
        if($chat_type!='groupchat'){
            $data['recipient_id']=$receiver_id;
        }
        $data['attachments'] = (object) array(array('id' => $file_uid, 'type' => $type, 'url' => $url));

       echo $userDetails = json_encode($data);
       
       echo "<br/>";

        $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Message.json');
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
//                if($waRow->attachments){
//                   $this->sendMediaChat($token, $chat_dialog_id, unserialize($waRow->attachments), unserialize($waRow->custom_param),$waReceiverRow->receiver_quickblox_id);
//                }
        print_r($resultArr);
        exit;
    }

    public function sendChatMessage1Action($waRow) {
//        $this->imageCreate();
//        die();
        $image_data = base64_encode(file_get_contents($this->makeUrl("/www/chat-thumb/1430764471.jpg")));//file_get_contents($this->makeUrl("/www/chat-thumb/1430764471.jpg"));
       // $image_data = "aHR0cDovL2xvY2FsLndhLmNvbS93d3cvY2hhdC10aHVtYi8xNDMwNzY1NDY3LmpwZw==";
        //$image1 = base64_encode('https://qb-wa-media.s3.amazonaws.com/db463aaec5d44c7f9d64980418a8dee900');
        //$this->imagesetCover1('https://qb-wa-media.s3.amazonaws.com/db463aaec5d44c7f9d64980418a8dee900');

        echo '<img src="data:image/jpeg;base64,' . $image_data . '">';
        die();
        die();
        $array = (object) array('userEmail' => 'akashsingh@gmail.com');
        $token = $this->quickBloxUserToken($array);
        $reciver_id = 46681;
        $chat_dialog_id = $this->createDialogId($token, $reciver_id);
        $custom_param = unserialize($waRow->custom_param);
        $data = array(
            'chat_dialog_id' => $chat_dialog_id,
            'message' => 'mobile test',
            'recipient_id' => $reciver_id,
            "send_to_chat" => 1,
            "markable" => 1,
            "isDelayed" => 1,
            "deviceId" => 'web',
            "localId" => 'web',
            "thumbDataStr" => $custom_param['thumbDataStr'],
        );
        // if ($waRow->attachments) {
        $data['attachments'] = (object) array(array('id' => '56115', 'type' => 'image', 'url' => "https://qb-wa-media.s3.amazonaws.com/db463aaec5d44c7f9d64980418a8dee900"));
        //}
        //print_r($data);die();
        echo $userDetails = json_encode($data);

        $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Message.json');
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
//                if($waRow->attachments){
//                   $this->sendMediaChat($token, $chat_dialog_id, unserialize($waRow->attachments), unserialize($waRow->custom_param),$waReceiverRow->receiver_quickblox_id);
//                }
        print_r($resultArr);
        exit;
    }

    function createThumbImage1($img) {
        $img[] = 'https://qb-wa-media.s3.amazonaws.com/d01e2b99f93a49ffab1b2f9a4fc08a0800';
//$img[]='http://i.indiafm.com/stills/celebrities/sada/thumb5.jpg';
        foreach ($img as $newname => $i) {
            $this->save_image($i, $newname);
            if (getimagesize(basename($newname))) {
                echo '<h3 style="color: green;">Image ' . basename($i) . ' Downloaded OK</h3>';
            } else {
                echo '<h3 style="color: red;">Image ' . basename($i) . ' Download Failed</h3>';
            }
        }
    }

    function createThumbImage($image) {
        $img = file_get_contents($image);
        $im = imagecreatefromstring($img);
        $width = imagesx($im);
        $height = imagesy($im);
        if($width<180 && $height<135){
            return base64_encode(file_get_contents($image));
        }
        $newwidth = ($width>180)?'180':$width;
        $newheight = ($height>135)?'135':$height;
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        $file_name = '/www/chat-thumb/'.time().'.jpg';
        imagejpeg($thumb, getcwd().$file_name,100); //save image as jpg
        imagedestroy($thumb);
        imagedestroy($im);
        return base64_encode(file_get_contents($this->makeUrl($file_name)));
    }

     public function sendDelayedMessageAction() {
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $decoded = $this->common->Decoded();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $userId = $this->loggedUserRow->userId;
        $wa_id = $decoded['wa_id'];
        $delivery_date = $decoded['delivery_date'];
        $delivery_time = $decoded['delivery_time'];
        $local_time = $decoded['local_time'];
        $receiverId=$decoded['quickbloxId'];
        $is_chat_message = isset($decoded['is_chat_message']) ? $decoded['is_chat_message'] : '0';
        $attachemnts = isset($decoded['fileUrl']) ? serialize(array("type"=>$decoded['type'],'id'=>time(),'url'=>$decoded['fileUrl'])) : NULL;
        $thumbString=($decoded['thumbnail_image'])?$this->createThumbImage($decoded['thumbnail_image']):"";
        $custom_param = isset($decoded['thumbnail_image']) ? serialize(array("thumbDataStr"=>$thumbString,'id'=>time(),'deviceId'=>'web','localid'=>'1','nick'=>$this->loggedUserRow->userNickName)) : NULL;
        $text = isset($decoded['message']) ? $decoded['message'] : ''; 
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $date_time = $delivery_date . " " . $delivery_time;
                $date_time = date("Y-m-d H:i:s", strtotime($date_time));
                $utc_time = $this->convertIntoUtcTime($date_time, $local_time);
                $data = array(
                    'user_id' => $userId,
                    'type' => 'chat_message',
                    'local_time' => $local_time,
                    'creation_date' => date("Y-m-d H:i:s"),
                    'delivery_date' => $date_time,
                    'first_delivery_date_utc' => $utc_time,
                    'last_delivery_date_utc' => $utc_time,
                    'modification_date' => date("Y-m-d H:i:s"),
                    'text' => $text,
                    'custom_param' => $custom_param,
                    'attachments' => $attachemnts,
                );

                $db = $this->db;
                $db->beginTransaction();

                try {
                    
                    
                        $waRow = $waTable->createRow($data);
                        $waRow->save();
                    
                    if ($waRow->wa_id) {
                        if ($receiverId) {
                                    $data = array(
                                        'wa_id' => $waRow->wa_id,
                                        'receiver_quickblox_id' => $receiverId
                                    );
                                }

                                if ($receiverId != $userId) {
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }
                            
                       
                        
                    } else {
                        $this->common->displayMessage("There is some error", "1", array(), "12");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    $this->common->displayMessage($e->getMessage(), "1", array(), "12");
                }
                $db->commit();
                $this->common->displayMessage("WA created successfully", "0", array("wa_id" => $waRow->wa_id), "0");
            } else {
                $this->common->displayMessage("User account does not exist", "1", array(), "2");
            }
       
    }

    public function editDelayedMessageAction() {

        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();
        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $userDeviceId = $decoded['userDeviceId'];
        $wa_id = $decoded['wa_id'];
        $userDeviceToken = $decoded['userDeviceToken'];
        $delivery_date = $decoded['delivery_date'];
        $delivery_time = $decoded['delivery_time'];
        $local_time = $decoded['local_time'];
        $receiver_userset = $decoded['receiver_userset'];
        $device_type = $decoded['device_type'];
        $receiver_email_phoneset = $decoded['receiver_email_phoneset'];
        $receiver_email_phoneset = json_decode($receiver_email_phoneset);
        $is_chat_message = isset($decoded['is_chat_message']) ? $decoded['is_chat_message'] : '0';
        $attachemnts = isset($decoded['attachments']) ? serialize($decoded['attachments']) : NULL;
        $custom_param = isset($decoded['custom_param']) ? serialize($decoded['custom_param']) : NULL;
        $text = isset($decoded['text']) ? $decoded['text'] : '';
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userId, $userDeviceId, $userDeviceToken, $delivery_date, $delivery_time, $local_time));
            $this->common->isUserLogin($userId, $userDeviceId);
            if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
                $waRow = false;
                $is_existing_wa = false;

                if ($wa_id) {
                    $is_existing_wa = true;
                    $waRow = $waTable->getRowById($wa_id);
                }

                $date_time = $delivery_date . " " . $delivery_time;

                $date_time = date("Y-m-d H:i:s", strtotime($date_time));
                $utc_time = $this->convertIntoUtcTime($date_time, $local_time);

                $data = array(
                    'user_id' => $userId,
                    'type' => ($is_chat_message) ? Application_Model_DbTable_Wa::TYPE_CHAT_MESSAGE : Application_Model_DbTable_Wa::WA_TYPE_GO,
                    'local_time' => $local_time,
                    'creation_date' => date("Y-m-d H:i:s"),
                    'delivery_date' => $date_time,
                    'first_delivery_date_utc' => $utc_time,
                    'last_delivery_date_utc' => $utc_time,
                    'modification_date' => date("Y-m-d H:i:s"),
                    'text' => $text,
                    'custom_param' => $custom_param,
                    'attachments' => $attachemnts,
                );

                $db = $this->db;
                $db->beginTransaction();

                try {
                    $waRow->setFromArray($data);
                    $waRow->save();
                    if ($waRow->wa_id) {
                        if ($is_existing_wa) {
                            $waReceiverTable->deleteReceivers($waRow->wa_id);
                        }

                        if (!empty($receiver_userset)) {
                            foreach ($receiver_userset as $receiverId) {

                                if ($is_chat_message) {
                                    $data = array(
                                        'wa_id' => $waRow->wa_id,
                                        'receiver_quickblox_id' => $receiverId
                                    );
                                } else {
                                    $data = array(
                                        'wa_id' => $waRow->wa_id,
                                        'receiver_id' => $receiverId
                                    );
                                }

                                if ($receiverId != $userId) {
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }
                            }
                        } else {

                            $this->common->displayMessage("receiver users not exists.", "1", array(), "12");
                        }
                        if (empty($receiver_email_phoneset)) {
                            foreach ($receiver_email_phoneset as $receiverData) {
                                $waUserRow = false;

                                if ($receiverData->email) {
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $receiverData->email);
                                }

                                if (!$waUserRow && $receiverData->phone) {
                                    $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $receiverData->phone);
                                }

                                $data = array(
                                    'wa_id' => $waRow->wa_id,
                                    'receiver_name' => $receiverData->name,
                                    'receiver_email' => $receiverData->email,
                                    'receiver_phone' => $receiverData->phone,
                                    'receiver_id' => $waUserRow ? $waUserRow->userId : new Zend_Db_Expr("NULL")
                                );

                                if (!$waUserRow || ($waUserRow->userId != $userId)) {
                                    $waReceiverRow = $waReceiverTable->createRow($data);
                                    $waReceiverRow->save();
                                }
                            }
                        } else {
                            $this->common->displayMessage("Receiver Email phoneset exists.", "1", array(), "12");
                        }
                    } else {
                        $this->common->displayMessage("There is some error", "1", array(), "12");
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    $this->common->displayMessage($e->getMessage(), "1", array(), "12");
                }
                $db->commit();
                $this->common->displayMessage("Delayed updated successfully", "0", array("wa_id" => $waRow->wa_id), "0");
            } else {
                $this->common->displayMessage("User account does not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage("You could not access this web-service", "1", array(), "3");
        }
    }

    public function sendChatMessage($waRow) {

        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waReceiverRowset = $waReceiverTable->getWaReceivers($waRow->wa_id);
        $waOwnerRow = $userTable->getRowById($waRow->user_id);
        $userRow = $userTable->getRowById($waRow->user_id);
        $token = $this->quickBloxUserToken($userRow);
        foreach ($waReceiverRowset as $waReceiverRow) {
            if ($waReceiverRow->receiver_quickblox_id) {
                $chat_dialog_id = $this->createDialogId($token, $waReceiverRow->receiver_quickblox_id);
                $custom_param = unserialize($waRow->custom_param);
                $data = array(
                    'chat_dialog_id' => $chat_dialog_id,
                    'id'=>$waRow->msg_id,
                    'message' => $waRow->text,
                    'recipient_id' => $waReceiverRow->receiver_quickblox_id,
                   // "_id" => $custom_param['_id'],
                    "send_to_chat" => 1,
                    "markable" => 1,
                    "isDelayed" => 1,
                    "deviceId" => $custom_param['deviceId'],
                    "localId" => $custom_param['localId'],
                    "thumbDataStr" => $custom_param['thumbDataStr'],
                );
                if ($waRow->attachments) {
                    $data['attachments'] = (object) array(unserialize($waRow->attachments));
                }
                //print_r($data);die();
                $userDetails = json_encode($data);

                $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Message.json');
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
                $arryData = array("id"=>$resultArr['_id'],'message_id'=>$waRow->msg_id);
                echo json_encode($arryData);
//                if($waRow->attachments){
//                   $this->sendMediaChat($token, $chat_dialog_id, unserialize($waRow->attachments), unserialize($waRow->custom_param),$waReceiverRow->receiver_quickblox_id);
//                }
                // print_r($resultArr);exit;
            }
        }
    }
public function delayedPreviewAction() {
        $this->_helper->layout->disableLayout();
        $decoded = $this->common->Decoded();
        $data = array();
        $myFriendList = explode(',', $decoded['myFriendList']);
        $all = $decoded['selected_friends']['userid'];

        foreach ($all as $key => $val) {
            if (in_array($val, $myFriendList)) {
                $receiver_userset[] = $val;
            } else {
                $receiver_email_phoneset[] = $val;
            }
        }

        foreach ($receiver_email_phoneset as $key => $val) {
            $array = explode('@@', $val);
            $data['name'][] = $array[0];
            $data['email_phone'][] = $array[1];
        }

        $this->view->userId = $this->loggedUserRow->userId;
        $this->view->waId = $decoded['waId'];
        $this->view->message_category = $decoded['message_category'];
        $this->view->message_title = $decoded['message_title'];
        $this->view->message = $decoded['message'];
        $this->view->delivery_date = $decoded['delivery_date'];
        $this->view->delivery_time = $decoded['delivery_time'];
        $this->view->local_time = $decoded['local_time'];
        $this->view->is_annually = $decoded['is_annually'];
        $this->view->uploaded_files = $decoded['uploaded_files'];
        $this->view->fileUrl= $decoded['fileUrl'];
        $this->view->thumbnail_image = $decoded['thumbnail_image'];
        $this->view->type = $decoded['type'];
        $this->view->quickbloxId = $decoded['quickbloxId'];
        

        //list of selected friends ids
        $this->view->receiver_userset = serialize($receiver_userset);
        //If friends are not in friend list, we can enter his name and (emailid 0r phone).
        $this->view->receiver_email_phoneset = serialize($data);
    }
    
     public function uploadfileAction() {//Upload WA images,audio and video.
       $type = explode('/', $_FILES['file']['type']);
       
        if ($type[0] == "audio") { //WA Audio
            $response = $this->common->uploadMedia($_FILES['file'], 'audio');
            $arrData['fileUrl']=$this->makeUrl("/upload/".$response['new_file_name']);
            $arrData['thumbnail']=$this->makeUrl('/www/images/audio.png');
            $arrData['type']=$type[0];
            echo json_encode($arrData);
        } else if ($type[0] == "video") { //Wa Videos
            $response = $this->common->uploadMedia($_FILES['file'], 'video');
            $file_path = "upload/" . $response['new_file_name'];
            $parse_file_name = explode(".", $response['new_file_name']);
            $thumbnail_path = '/images/' . time().".jpg";
            $video_path = '/' . $file_path;
            $this->createThumbnail($video_path, $thumbnail_path);
            $arrData['fileUrl']=$this->makeUrl("/upload/".$response['new_file_name']);
            $arrData['thumbnail']=$this->makeUrl($thumbnail_path);
            $arrData['type']=$type[0];
            echo json_encode($arrData);
        } else { //WA Images, Pdf
            $response = $this->common->uploadImage($_FILES['file']);
            $arrData['fileUrl']=$this->makeUrl("/images/".$response['new_file_name']);
            $arrData['thumbnail']=$this->makeUrl("/images/".$response['new_file_name']);
            $arrData['type']='photo';
            echo json_encode($arrData);
        }

        exit;
    }
    
     /*
     * used for update group join status on when user join the group on qb
     * param    :   id(groupId)
     */
    public function confirmAgeAction() {
        $this->_helper->layout->disableLayout();
        $usersTable = new Application_Model_DbTable_Users();
        $userId = $this->loggedUserRow->userId;
        $usersTable->update(array("userAge"=>1),"userId='{$userId}'");
    }
    
    /*
     * used for update group join status on when user join the group on qb
     * param    :   id(groupId)
     */
    public function updateAdultReadEmojiAction() {
        $this->_helper->layout->disableLayout();
        $messageId = $this->getRequest()->getPost('message_id',0);
        $db = Zend_Db_Table::getDefaultAdapter();
        $query ="UPDATE `dialog_message` SET `adult_read` = 1 WHERE (message_id='{$messageId}' AND recipient_id='{$this->loggedUserRow->quickBloxId}')";
        $db->query($query);
        $query ="UPDATE `dialog_message` SET `sender_read` = 1 WHERE (message_id='{$messageId}' AND sender_id='{$this->loggedUserRow->quickBloxId}')";
        $db->query($query);
        die();
    }
    
     public function searchFriendsAction() {
        $this->_helper->layout()->disableLayout();
        $friendTable = new Application_Model_DbTable_Friends();
        $this->loggedUserRow->userId;
        $searchText= $this->getRequest()->getParam('searchText','');
        $result = $friendTable->searchFriends($this->loggedUserRow->userId,true,$searchText,true);
        echo (count($result)>0)?json_encode($result):"0";
        die();
        
    }
    
     public function readMessagesAction(){
        $sender_id= $this->getRequest()->getParam('senderId','');
        $dialogMessage = new Application_Model_DbTable_DialogMessage();
        $dialogMessage->update(array('is_read'=>2), "sender_id='{$sender_id}' AND recipient_id='{$this->loggedUserRow->userId}'");
        die();
        
   }
   
   function messageAction(){
        $user = new Application_Model_DbTable_Message();
        $id= $this->getRequest()->getParam('id',0);
        //$result = Application_Model_DbTable_Message::fetchOne(array('message_id'=>$id));
        $result= Application_Model_DbTable_Message::all(array('chat_dialog_id'=>$id))->sort(array('message_id'=>-1))->limit(1);
        echo "<pre>";
       echo $result->count();
        print_r($result);
        foreach ($result as $user) {
        print($user->message_id."<br />\n");
        }
        die();
        
   }
   


    

}

?>
