<?php

class MywaController extends My_Controller_Abstract {

    public function init() {
        parent::init();
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $this->user = new Application_Model_UsersMapper();

        $this->_helper->layout->disableLayout();
        $this->view->menu = "MYWA";
    }

    
    /****** Select Wa Go, Wa Event & Wa After ******/
    
    public function indexAction() {
       // $this->_helper->layout->setLayout('new_layout');
        $this->renderScript('mywa/coming-soon.phtml');
        
        /** Write Logs **/
//        $filename = '/logs/'.date('Y-m-d').'.txt';
//        $logData = '';
//        file_put_contents($filename ,$logData, FILE_APPEND | LOCK_EX);
        /** Write Logs **/
        
//        if ($this->loggedUserRow->userId > 0) {
//            
//        } else {
//            $this->_redirect($this->makeUrl('/'));
//        }
//        $this->view->menu = "LISTWA";
    }


    /************** Wa Go *********************/
    
    public function listAction() {
        $this->_helper->layout->disableLayout();
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();

        $userId = $this->loggedUserRow->userId;
        $offset = isset($decoded['offset']) ? $decoded['offset'] : 0;
        $get_incoming_wa = isset($decoded['get_incoming_wa']) ? $decoded['get_incoming_wa'] : '1';
        $search_type = isset($decoded['search_type']) ? $decoded['search_type'] : "all";
        $this->view->divtype = $decoded['divtype'];

        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {

            $offset = $offset * 10;
            if ($get_incoming_wa == '1') {
                $waRowset = $waTable->getGoRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            } else { 
                $waRowset = $waTable->getReceivedRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            }

            $waDetails = array();

            foreach ($waRowset as $waRow) {
                $waDetails[] = array(
                    'wa_id' => $waRow->wa_id,
                    'type' => $waRow->type,
                    'userNickName' => $waRow->userNickName,
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/upload/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'modification_date' => $waRow->modification_date,
                    'is_send' => $waRow->is_send,
                    'is_incoming_request' => $waRow->is_incoming_request
                );
            }

            $response = array(
                "error_code" => "0",
                "response_error_key" => "0",
                "response_string" => "WA Details",
                "result" => $waDetails
            );

            $this->view->result = json_encode($response);
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }

    public function waDetailsByIdAction() {
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();

        $userId = $this->loggedUserRow->userId; //$decoded['userId'];
        $waId = ($_REQUEST['waId'] > 0) ? $_REQUEST['waId'] : $decoded['waId']; // edit or create time get wa details


        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
            if ($waRow = $waTable->getRowById($waId)) {

                $waOwnerRow = $userTable->getRowById($waRow->user_id);

                $waReceiverRowset = $waReceiverTable->getWaReceivers($waId);

                $waReceiverSet = array();
                $waReceiverEmails = array();
                $waReceiverPhones = array();

                foreach ($waReceiverRowset as $waReceiverRow) {
                    if ($waReceiverRow->receiver_id) {

                        $waReceiverSet[] = array(
                            'userId' => $waReceiverRow->receiver_id,
                            'userNickName' => ($waReceiverRow->userNickName) ? $waReceiverRow->userNickName : "",
                            'userImage' => ($waReceiverRow->userImage) ? $waReceiverRow->userImage : ""
                        );
                    } elseif ($waReceiverRow->receiver_email) {

                        $waReceiverEmails['name'] = $waReceiverRow->receiver_name;
                        $waReceiverEmails['email'] = $waReceiverRow->receiver_email;
                        $waReceiverEmailsArray[] = $waReceiverEmails;
                    } else {
                        $waReceiverPhones['name'] = $waReceiverRow->receiver_name;
                        $waReceiverPhones['phone'] = $waReceiverRow->receiver_phone;
                        $waReceiverPhonesArray[] = $waReceiverPhones;
                    }
                }

                $data = array(
                    'wa_id' => $waRow->wa_id,
                    'is_incoming_request' => ($waRow->user_id = $waOwnerRow->userId) ? "0" : "1",
                    'type' => $waRow->type,
                    'sender_name' => $waOwnerRow->userNickName,
                    'sender_image' => $waOwnerRow->userImage,
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'is_send' => $waRow->is_send,
                    'local_time' => $waRow->local_time,
                    'is_annually' => $waRow->is_annually,
                    'sender_id' => $waOwnerRow->userId,
                    'waReceiverSet' => $waReceiverSet,
                    'waReceiverEmails' => $waReceiverEmailsArray,
                    'waReceiverPhones' => $waReceiverPhonesArray,
                );
                if ($_REQUEST['waId'] > 0) {
                    return json_encode($data);
                } else {
                    $this->common->displayMessage("Wa details by wa id", "0", $data, "0");
                }
            } else {
                $this->common->displayMessage("User account does not exist", "1", array(), "4");
            }
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }

    public function waSettingsAction() {
        $this->view->menu = "WASETTINGS";

        $this->view->waId = $this->getRequest()->getPost('waId');

        if ($this->view->waId > 0) {

            $this->view->editwadetails = $this->waDetailsByIdAction();

            $wadetailbyid = json_decode($this->view->editwadetails);

            $this->view->wacategory = $wadetailbyid->type;
            $this->view->watitle = $wadetailbyid->message_title;
        } else {
            $this->view->wacategory = $this->getRequest()->getPost('wacategory');
            $this->view->watitle = $this->getRequest()->getPost('watitle');
        }

        $zones = timezone_identifiers_list();

        foreach ($zones as $zone) {
            $zone = explode('/', $zone); // 0 => Continent, 1 => City
            // Only use "friendly" continent names
            if ($zone[0] == 'Africa' || $zone[0] == 'America' || $zone[0] == 'Antarctica' || $zone[0] == 'Arctic' || $zone[0] == 'Asia' || $zone[0] == 'Atlantic' || $zone[0] == 'Australia' || $zone[0] == 'Europe' || $zone[0] == 'Indian' || $zone[0] == 'Pacific') {
                if (isset($zone[1]) != '') {
                    $locations[$zone[0]][$zone[0] . '/' . $zone[1]] = str_replace('_', ' ', $zone[1]); // Creates array(DateTimeZone => 'Friendly name')
                }
            }
        }
        $this->view->locations = $locations;
        if ($this->view->wacategory == '') {
            $this->_redirect($this->makeUrl('/mywa/create-wa'));
        }
    }
    
    public function waDetailsPopupAction() {
        $decoded = $this->common->Decoded();
        $this->view->waId = $decoded['waId'];
        $this->_helper->layout->disableLayout();
    }
    
    public function waPreviewAction() {
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

        //list of selected friends ids
        $this->view->receiver_userset = serialize($receiver_userset);
        //If friends are not in friend list, we can enter his name and (emailid 0r phone).
        $this->view->receiver_email_phoneset = serialize($data);
    }
    
    
    public function delayedMessageAction() {
        //$this->_helper->layout->disableLayout();
        
        $decoded = $this->common->Decoded();

        $this->view->menu = "DELAYEDMESSAGE";

        $this->view->waId = $this->getRequest()->getPost('waId');

        if ($this->view->waId > 0) {

            $this->view->editwadetails = $this->waDetailsByIdAction();

            $wadetailbyid = json_decode($this->view->editwadetails);

            $this->view->wacategory = $wadetailbyid->type;
            $this->view->watitle = $wadetailbyid->message_title;
        } else {
            $this->view->wacategory = $this->getRequest()->getPost('wacategory');
            $this->view->watitle = $this->getRequest()->getPost('watitle');
        }

        $zones = timezone_identifiers_list();

        foreach ($zones as $zone) {
            $zone = explode('/', $zone); // 0 => Continent, 1 => City
            // Only use "friendly" continent names
            if ($zone[0] == 'Africa' || $zone[0] == 'America' || $zone[0] == 'Antarctica' || $zone[0] == 'Arctic' || $zone[0] == 'Asia' || $zone[0] == 'Atlantic' || $zone[0] == 'Australia' || $zone[0] == 'Europe' || $zone[0] == 'Indian' || $zone[0] == 'Pacific') {
                if (isset($zone[1]) != '') {
                    $locations[$zone[0]][$zone[0] . '/' . $zone[1]] = str_replace('_', ' ', $zone[1]); // Creates array(DateTimeZone => 'Friendly name')
                }
            }
        }
        $this->view->quickbloxId = $this->getRequest()->getParam('id','0');
        $this->view->locations = $locations;
    }
    
    public function createGoAction() {

        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $wa_id = $decoded['waId'];

        $message_title = $decoded['message_title'];
        $delivery_date = date('Y-m-d', strtotime($decoded['delivery_date']));
        $delivery_time = date('H:i:s', strtotime($decoded['delivery_time']));
        $local_time = $decoded['local_time'];
        $uploaded_files = ($decoded['uploaded_files'] != '') ? explode(',', $decoded['uploaded_files']) : '';
        $is_annually = $decoded['is_annually'];
        $receiver_userset = unserialize($decoded['receiver_userset']);
        $receiver_email_phoneset = unserialize($decoded['receiver_email_phoneset']);
        $is_chat_message = $decoded['is_chat_message'];
        $text = $decoded['message'];

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
                'message_title' => $message_title,
                'local_time' => $local_time,
                'creation_date' => date("Y-m-d H:i:s"),
                'delivery_date' => $date_time,
                'first_delivery_date_utc' => $utc_time,
                'last_delivery_date_utc' => $utc_time,
                'modification_date' => date("Y-m-d H:i:s"),
                'is_annually' => $is_annually,
                'text' => $text
            );

            $db = $this->db;
            $db->beginTransaction();

            try {
                $imgagearr = array('jpg','jpeg','gif','png');
                $audioarr = array('mp3');
                $videoarr = array('mp4');

                if (count($uploaded_files) > 0) {
                    foreach ($uploaded_files as $key => $val) {

                       $ext = explode('.', $val);
                        if (in_array(strtolower($ext[1]), $audioarr)) {//Audios
                            $data = array_merge($data, array(
                                'audio_link' => $val
                            ));
                        } else if (in_array(strtolower($ext[1]), $videoarr)) { //Videos
                            $video_ext = explode('.', $val);
                            $data = array_merge($data, array(
                                'video_link' => $val,
                                'thumbnail_link' => $video_ext[0] . '_thumb.jpg'
                            ));
                        } else if (in_array(strtolower($ext[1]), $imgagearr)) { //Images
                            $data = array_merge($data, array(
                                'image_link' => $val
                            ));
                        }
                        
                        
                    }
                }

                if ($waRow) {
                    $waRow->setFromArray($data);
                    $waRow->save();
                } else {
                    $waRow = $waTable->createRow($data);
                    $waRow->save();
                }

                if ($waRow->wa_id) {
                    if ($is_existing_wa) {
                        $waReceiverTable->deleteReceivers($waRow->wa_id);
                    }

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

                    for ($i = 0; $i < count($receiver_email_phoneset['name']); $i++) {
                        $waUserRow = false;
                        $name = $receiver_email_phoneset['name'][$i];
                        $email_phone = $receiver_email_phoneset['email_phone'][$i];
                        $email = '';
                        $phone = '';
                        if (strpos($email_phone, '@')) {
                            $email = $email_phone;
                        } else {
                            $phone = $email_phone;
                        }

                        if ($email != '') {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $email);
                        }
                        if (!$waUserRow && $phone) {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $phone);
                        }
                        $data = array(
                            'wa_id' => $waRow->wa_id,
                            'receiver_name' => $name,
                            'receiver_email' => $email,
                            'receiver_phone' => $phone,
                            'receiver_id' => $waUserRow ? $waUserRow->userId : new Zend_Db_Expr("NULL")
                        );
                        if (!$waUserRow || ($waUserRow->userId != $userId)) {
                            $waReceiverRow = $waReceiverTable->createRow($data);
                            $waReceiverRow->save();
                        }
                    }
                } else {
                    $this->common->displayMessage("There is some error", "1", array(), "12");
                }
            } catch (Exception $e) {
                $db->rollBack();
                $this->common->displayMessage("There is some error", "1", array(), "12");
            }
            $db->commit();
            $this->common->displayMessage("WA created successfully", "0", array(), "0");
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }

    
    /****** WA Event **********************/

    public function eventListAction() {
        $this->_helper->layout->disableLayout();
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $offset = isset($decoded['offset']) ? $decoded['offset'] : 0;
        $get_incoming_wa = isset($decoded['get_incoming_wa']) ? $decoded['get_incoming_wa'] : '1';
        $search_type = isset($decoded['search_type']) ? $decoded['search_type'] : "all";


        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
            
            $offset = $offset * 10;
            if ($get_incoming_wa == '1') { 
                $waRowset = $waTable->getEventRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            } else {
                $waRowset = $waTable->getReceivedEventRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            }

            $waDetails = array();

            foreach ($waRowset as $waRow) {
                $waDetails[] = array(
                    'wa_id' => $waRow->wa_id,
                    'type' => $waRow->type,
                    'userNickName' => $waRow->userNickName,
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/upload/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'modification_date' => $waRow->modification_date,
                    'is_send' => $waRow->is_send,
                    'is_incoming_request' => $waRow->is_incoming_request,
                    'delivery_date' => $waRow->deliveryDate(),
                    'is_read' => $waRow->is_read,
                    'vital_check' => $waRow->vital_check
                );
            }

            $response = array(
                "error_code" => "0",
                "response_error_key" => "0",
                "response_string" => "WA Details",
                "result" => $waDetails
            );
            $this->view->result = json_encode($response);
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }
    
    public function waEventDetailsByIdAction() {
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();

        $decoded = $this->common->Decoded();

        $userId = $this->loggedUserRow->userId; //$decoded['userId'];
        $waId = ($_REQUEST['waId'] > 0) ? $_REQUEST['waId'] : $decoded['waId']; // edit or create time get wa details


        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
            if ($waRow = $waTable->getRowById($waId)) {

                $waOwnerRow = $userTable->getRowById($waRow->user_id);


                /** wa_receiver table * */
                if ($waReceiverRow = $waReceiverTable->getRowByWaIdAndReceiverId($waId, $userId)) {
                    $waReceiverRow->is_read = "1";
                    $waReceiverRow->save();          // set read status 
                }

                $waReceiverRowset = $waReceiverTable->getWaReceivers($waId);

                $waReceiverSet = array();
                $waReceiverNames = array();


                foreach ($waReceiverRowset as $waReceiverRow) {
                    if ($waReceiverRow->receiver_id) {

                        $waReceiverSet[] = array(
                            'userId' => $waReceiverRow->receiver_id,
                            'userNickName' => ($waReceiverRow->userNickName) ? $waReceiverRow->userNickName : "",
                            'userImage' => ($waReceiverRow->userImage) ? $this->makeUrl($waReceiverRow->userImage) : ""
                        );
                    } elseif ($waReceiverRow->receiver_email) {

                        $waReceiverEmails['name'] = $waReceiverRow->receiver_name;
                        $waReceiverEmails['email'] = $waReceiverRow->receiver_email;
                        $waReceiverEmailsArray[] = $waReceiverEmails;
                    } else {
                        $waReceiverPhones['name'] = $waReceiverRow->receiver_name;
                        $waReceiverPhones['phone'] = $waReceiverRow->receiver_phone;
                        $waReceiverPhonesArray[] = $waReceiverPhones;
                    }
                }

                /** wa_trustee table * */
                if ($waTrusteeRow = $waTrusteeTable->getRowByWaIdAndReceiverId($waId, $userId)) {
                    $waTrusteeRow->is_read = "1";
                    $waTrusteeRow->save();          // set read status 
                }

                $waTrusteeRowset = $waTrusteeTable->getWaReceivers($waId);


                $waTrusteeSet = array();
                $waTrusteeNames = array();


                foreach ($waTrusteeRowset as $waTrusteeRow) {
                    if ($waTrusteeRow->receiver_id) {

                        $waTrusteeSet[] = array(
                            'userId' => $waTrusteeRow->receiver_id,
                            'userNickName' => ($waTrusteeRow->userNickName) ? $waTrusteeRow->userNickName : "",
                            'userImage' => ($waTrusteeRow->userImage) ? $this->makeUrl($waTrusteeRow->userImage) : ""
                        );
                    } else {
                        $waTrusteeNames[] = array(
                            'name' => ($waTrusteeRow->receiver_name) ? $waTrusteeRow->receiver_name : "",
                            'email' => ($waTrusteeRow->receiver_email) ? $waTrusteeRow->receiver_email : "",
                            'phone' => ($waTrusteeRow->receiver_phone) ? $waTrusteeRow->receiver_phone : ""
                        );
                    }
                }


                /** Response data prepration * */
                $waEventSendDetailRow = $waEventSendTable->getRowByWaId($waRow->wa_id);

                $data = array(
                    'wa_id' => $waRow->wa_id,
                    'is_incoming_request' => ($waRow->user_id == $userId) ? "0" : "1",
                    'type' => $waRow->type,
                    'sender_name' => $waOwnerRow->userNickName,
                    'sender_image' => ($waOwnerRow->userImage) ? $this->makeUrl($waOwnerRow->userImage) : "",
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/upload/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'is_send' => $waRow->is_send,
                    'sender_id' => $waOwnerRow->userId,
                    'waReceiverSet' => $waReceiverSet,
                    'waReceiverNames' => $waReceiverNames,
                    'waReceiverEmails' => $waReceiverEmailsArray,
                    'waReceiverPhones' => $waReceiverPhonesArray,
                    'waTrusteeSet' => $waTrusteeSet,
                    'waTrusteeNames' => $waTrusteeNames,
                    'local_time' => $waRow->local_time,
                    'delivery_date' => $waRow->deliveryDate(),
                    'delivery_time' => $waRow->deliveryTime(),
                    'is_annually' => $waRow->is_annually,
                    'is_read' => "1",
                    'vital_check' => ($waEventSendDetailRow->vital_check) ? $waEventSendDetailRow->vital_check : Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_QUARTERLY
                );
                if ($_REQUEST['waId'] > 0) {
                    return json_encode($data);
                } else {
                    $this->common->displayMessage("Wa details by wa id", "0", $data, "0");
                }
            } else {
                $this->common->displayMessage("User account does not exist", "1", array(), "4");
            }
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }
    
    public function waEventSettingsAction() { //Create Wa Event => Settings [wa-event-settings]
        $this->view->menu = "WAEVENTSETTINGS";

        $this->view->waId = $this->getRequest()->getPost('waId');

        if ($this->view->waId > 0) {

            $this->view->editwaeventdetails = $this->waEventDetailsByIdAction();

            $waeventdetailbyid = json_decode($this->view->editwaeventdetails);
            $this->view->wacategory = $waeventdetailbyid->type;
            $this->view->watitle = $waeventdetailbyid->message_title;
        } else {
            $this->view->wacategory = $this->getRequest()->getPost('wacategory');
            $this->view->watitle = $this->getRequest()->getPost('watitle');
        }
        
        if ($this->view->wacategory == '') {
            $this->_redirect($this->makeUrl('/mywa/create-wa'));
        }
    }

    public function waEventDetailsPopupAction() {
        $decoded = $this->common->Decoded();
        $this->view->waId = $decoded['waId'];
        $this->_helper->layout->disableLayout();
    }
    
    public function waEventPreviewAction() {
        $this->_helper->layout->disableLayout();
        $decoded = $this->common->Decoded();
        

        $data = array();
        $mytrusteeList = explode(',', $decoded['mytrusteeList']);//trustee list
        $myFriendList = explode(',', $decoded['myFriendList']);//friend list
        $all = $decoded['selected_friends']['userid'];// selected ids

        foreach ($all as $key => $val) {
            if (in_array($val, $myFriendList) && !in_array($val, $mytrusteeList)) {
                $receiver_userset[] = $val;
            }else if (in_array($val, $mytrusteeList)) {
                $receiver_trusteeset[] = $val;
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
        $this->view->vital_check = $decoded['vital_check'];
        $this->view->uploaded_files = $decoded['uploaded_files'];

        //list of selected friends ids
        $this->view->receiver_userset = serialize($receiver_userset);
        //list of selected friends ids
        $this->view->receiver_trusteeset = serialize($receiver_trusteeset);
        //If friends are not in friend list, we can enter his name and (emailid 0r phone).
        $this->view->receiver_email_phoneset = serialize($data);
    }
    
    public function createEventAction() { //Create Wa Event [create-event]
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();

        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $wa_id = $decoded['waId'];

        $message_title = $decoded['message_title'];
        $uploaded_files = ($decoded['uploaded_files'] != '') ? explode(',', $decoded['uploaded_files']) : '';
        $receiver_userset = unserialize($decoded['receiver_userset']);
        $receiver_trusteeset   = unserialize($decoded['receiver_trusteeset']);
        $receiver_email_phoneset = unserialize($decoded['receiver_email_phoneset']);
        $is_chat_message = $decoded['is_chat_message'];
        $text = $decoded['message'];
        $vital_check = $decoded['vital_check'];

        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {

            $waRow = false;
            $is_existing_wa = false;

            if ($wa_id) {
                $is_existing_wa = true;
                $waRow = $waTable->getRowById($wa_id);
            }

            $data = array(
                'user_id' => $userId,
                'type' => Application_Model_DbTable_Wa::WA_TYPE_EVENT,
                'message_title' => $message_title,
                'creation_date' => date("Y-m-d H:i:s"),
                'modification_date' => date("Y-m-d H:i:s"),
                'text'                  => $text
            );

            $db = $this->db;
            $db->beginTransaction();

            try {
                $imgagearr = array('jpg','jpeg','gif','png');
                $audioarr = array('mp3');
                $videoarr = array('mp4');
                if (count($uploaded_files) > 0) {
                    foreach ($uploaded_files as $key => $val) {
                        $ext = explode('.', $val);
                        if (in_array(strtolower($ext[1]), $audioarr)) {//Audios
                            $data = array_merge($data, array(
                                'audio_link' => $val
                            ));
                        } else if (in_array(strtolower($ext[1]), $videoarr)) { //Videos
                            $video_ext = explode('.', $val);
                            $data = array_merge($data, array(
                                'video_link' => $val,
                                'thumbnail_link' => $video_ext[0] . '_thumb.jpg'
                            ));
                        } else if (in_array(strtolower($ext[1]), $imgagearr)) { //Images
                            $data = array_merge($data, array(
                                'image_link' => $val
                            ));
                        }
                    }
                }

                if ($waRow) {
                    $waRow->setFromArray($data);
                    $waRow->save();
                } else {
                    $waRow = $waTable->createRow($data);
                    $waRow->save();
                }

                if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_QUARTERLY) {
                    //       $event_send_date = date('Y-m-d H:i:s', strtotime('+90 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+5 min'));
                } elseif ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_BIANNUAL) {
                    //   $event_send_date = date('Y-m-d H:i:s', strtotime('+180 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+10 min'));
                } else {
                    //    $event_send_date = date('Y-m-d H:i:s', strtotime('+365 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+15 min'));
                }

                $eventSendDetailData = array(
                    'wa_id' => $waRow->wa_id,
                    'creation_date' => date("Y-m-d H:i:s"),
                    'vital_check' => $vital_check,
                    'event_send_date' => $event_send_date,
                    'owner_alert_time' => $event_send_date,
                    'owner_alert_count' => 0,
                    'trustee_alert_count' => 0
                );

                $eventSendDetailRow = false;

                if ($is_existing_wa) {
                    $eventSendDetailRow = $waEventSendTable->getRowByWaId($waRow->wa_id);
                }

                if ($eventSendDetailRow) {
                    if ($eventSendDetailRow->vital_check == $vital_check) {
                        $eventSendDetailRow->setFromArray($eventSendDetailData);
                        $eventSendDetailRow->save();
                    }
                } else {
                    $eventSendDetailRow = $waEventSendTable->createRow($eventSendDetailData);
                    $eventSendDetailRow->save();
                }

                if ($waRow->wa_id) {
                    if ($is_existing_wa) {
                        $waReceiverTable->deleteReceivers($waRow->wa_id);
                           $waTrusteeTable->deleteReceivers($waRow->wa_id);
                    }

                    foreach ($receiver_userset as $receiverId){
                            $data = array(
                                'wa_id'         => $waRow->wa_id,
                                'receiver_id'   => $receiverId
                            );
                            
                            if($receiverId != $userId){
                                $waReceiverRow = $waReceiverTable->createRow($data);
                                $waReceiverRow->save();
                            }
                        }
                        
                        
                        foreach ($receiver_trusteeset as $receiverId){
                            $data = array(
                                'wa_id'         => $waRow->wa_id,
                                'receiver_id'   => $receiverId
                            );
                            

                            if($receiverId != $userId){
                                $waTrusteeRow = $waTrusteeTable->createRow($data);
                                $waTrusteeRow->save();
                            }
                        }

                    for ($i = 0; $i < count($receiver_email_phoneset['name']); $i++) {
                        $waUserRow = false;
                        $name = $receiver_email_phoneset['name'][$i];
                        $email_phone = $receiver_email_phoneset['email_phone'][$i];
                        $email = '';
                        $phone = '';
                        if (strpos($email_phone, '@')) {
                            $email = $email_phone;
                        } else {
                            $phone = $email_phone;
                        }

                        if ($email != '') {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $email);
                        }
                        if (!$waUserRow && $phone) {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $phone);
                        }
                        $data = array(
                            'wa_id' => $waRow->wa_id,
                            'receiver_name' => $name,
                            'receiver_email' => $email,
                            'receiver_phone' => $phone,
                            'receiver_id' => $waUserRow ? $waUserRow->userId : new Zend_Db_Expr("NULL")
                        );
                        if (!$waUserRow || ($waUserRow->userId != $userId)) {
                            $waReceiverRow = $waReceiverTable->createRow($data);
                            $waReceiverRow->save();
                        }
                    }
                } else {
                    $this->common->displayMessage("There is some error", "1", array(), "12");
                }
            } catch (Exception $e) {
                $db->rollBack();
                $this->common->displayMessage("There is some error", "1", array(), "12");
            }
            $db->commit();
            $this->common->displayMessage("WA created successfully", "0", array(), "0");
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }

    

    /********* WA AFTER *************/
    
    public function afterListAction() {
        $this->_helper->layout->disableLayout();
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();

        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $offset = isset($decoded['offset']) ? $decoded['offset'] : 0;
        $get_incoming_wa = isset($decoded['get_incoming_wa']) ? $decoded['get_incoming_wa'] : '1';
        $search_type = isset($decoded['search_type']) ? $decoded['search_type'] : "all";


        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
           

            $offset = $offset * 10;
            if ($get_incoming_wa == '1') {
                $waRowset = $waTable->getAfterRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            } else {
                $waRowset = $waTable->getReceivedAfterRecordsByUserId($userId, $offset, $get_incoming_wa, $search_type);
            }


            $waDetails = array();

            foreach ($waRowset as $waRow) {
                $waDetails[] = array(
                    'wa_id' => $waRow->wa_id,
                    'type' => $waRow->type,
                    'userNickName' => $waRow->userNickName,
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/upload/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'modification_date' => $waRow->modification_date,
                    'is_send' => $waRow->is_send,
                    'is_incoming_request' => $waRow->is_incoming_request,
                    'delivery_date' => $waRow->deliveryDate(),
                    'is_read' => $waRow->is_read,
                    'vital_check' => $waRow->vital_check,
                    'vital_value' => $waRow->vital_value
                );
            }

            $response = array(
                "error_code" => "0",
                "response_error_key" => "0",
                "response_string" => "WA Details",
                "result" => $waDetails
            );
            $this->view->result = json_encode($response);
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }
    
    public function waAfterDetailsByIdAction() {
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();

        $decoded = $this->common->Decoded();

        $userId = $this->loggedUserRow->userId; //$decoded['userId'];
        $waId = ($_REQUEST['waId'] > 0) ? $_REQUEST['waId'] : $decoded['waId']; // edit or create time get wa details


        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {
            if ($waRow = $waTable->getRowById($waId)) {

                $waOwnerRow = $userTable->getRowById($waRow->user_id);


                /** wa_receiver table * */
                if ($waReceiverRow = $waReceiverTable->getRowByWaIdAndReceiverId($waId, $userId)) {
                    $waReceiverRow->is_read = "1";
                    $waReceiverRow->save();          // set read status 
                }

                $waReceiverRowset = $waReceiverTable->getWaReceivers($waId);

                $waReceiverSet = array();
                $waReceiverNames = array();


                foreach ($waReceiverRowset as $waReceiverRow) {
                    if ($waReceiverRow->receiver_id) {

                        $waReceiverSet[] = array(
                            'userId' => $waReceiverRow->receiver_id,
                            'userNickName' => ($waReceiverRow->userNickName) ? $waReceiverRow->userNickName : "",
                            'userImage' => ($waReceiverRow->userImage) ? $this->makeUrl($waReceiverRow->userImage) : ""
                        );
                    } elseif ($waReceiverRow->receiver_email) {

                        $waReceiverEmails['name'] = $waReceiverRow->receiver_name;
                        $waReceiverEmails['email'] = $waReceiverRow->receiver_email;
                        $waReceiverEmailsArray[] = $waReceiverEmails;
                    } else {
                        $waReceiverPhones['name'] = $waReceiverRow->receiver_name;
                        $waReceiverPhones['phone'] = $waReceiverRow->receiver_phone;
                        $waReceiverPhonesArray[] = $waReceiverPhones;
                    }
                }

                /** wa_trustee table * */
                if ($waTrusteeRow = $waTrusteeTable->getRowByWaIdAndReceiverId($waId, $userId)) {
                    $waTrusteeRow->is_read = "1";
                    $waTrusteeRow->save();          // set read status 
                }

                $waTrusteeRowset = $waTrusteeTable->getWaReceivers($waId);


                $waTrusteeSet = array();
                $waTrusteeNames = array();


                foreach ($waTrusteeRowset as $waTrusteeRow) {
                    if ($waTrusteeRow->receiver_id) {

                        $waTrusteeSet[] = array(
                            'userId' => $waTrusteeRow->receiver_id,
                            'userNickName' => ($waTrusteeRow->userNickName) ? $waTrusteeRow->userNickName : "",
                            'userImage' => ($waTrusteeRow->userImage) ? $this->makeUrl($waTrusteeRow->userImage) : ""
                        );
                    } else {
                        $waTrusteeNames[] = array(
                            'name' => ($waTrusteeRow->receiver_name) ? $waTrusteeRow->receiver_name : "",
                            'email' => ($waTrusteeRow->receiver_email) ? $waTrusteeRow->receiver_email : "",
                            'phone' => ($waTrusteeRow->receiver_phone) ? $waTrusteeRow->receiver_phone : ""
                        );
                    }
                }


                /** Response data prepration * */
                $waEventSendDetailRow = $waEventSendTable->getRowByWaId($waRow->wa_id);

                $data = array(
                    'wa_id' => $waRow->wa_id,
                    'is_incoming_request' => ($waRow->user_id == $userId) ? "0" : "1",
                    'type' => $waRow->type,
                    'sender_name' => $waOwnerRow->userNickName,
                    'sender_image' => ($waOwnerRow->userImage) ? $this->makeUrl($waOwnerRow->userImage) : "",
                    'message_title' => $waRow->message_title,
                    'text' => $waRow->text ? $waRow->text : "",
                    'image_link' => $waRow->image_link ? $this->makeUrl('/images/' . $waRow->image_link) : "",
                    'audio_link' => $waRow->audio_link ? $this->makeUrl('/upload/' . $waRow->audio_link) : "",
                    'video_link' => $waRow->video_link ? $this->makeUrl('/upload/' . $waRow->video_link) : "",
                    'thumbnail_link' => $waRow->thumbnail_link ? $this->makeUrl('/upload/' . $waRow->thumbnail_link) : "",
                    'delivery_date' => $waRow->delivery_date,
                    'is_send' => $waRow->is_send,
                    'sender_id' => $waOwnerRow->userId,
                    'waReceiverSet' => $waReceiverSet,
                    'waReceiverNames' => $waReceiverNames,
                    'waReceiverEmails' => $waReceiverEmailsArray,
                    'waReceiverPhones' => $waReceiverPhonesArray,
                    'waTrusteeSet' => $waTrusteeSet,
                    'waTrusteeNames' => $waTrusteeNames,
                    'local_time' => $waRow->local_time,
                    'delivery_date' => $waRow->deliveryDate(),
                    'delivery_time' => $waRow->deliveryTime(),
                    'is_annually' => $waRow->is_annually,
                    'is_read' => "1",
                    'vital_value' => $waEventSendDetailRow->vital_value,
                    'vital_check' => ($waEventSendDetailRow->vital_check) ? $waEventSendDetailRow->vital_check : Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_HOUR
                );
//                echo "<pre>".$_REQUEST['waId'];
//                print_r($data);
//                exit;
                if ($_REQUEST['waId'] > 0) {
                    return json_encode($data);
                } else {
                    $this->common->displayMessage("Wa details by wa id", "0", $data, "0");
                }
            } else {
                $this->common->displayMessage("User account does not exist", "1", array(), "4");
            }
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }
        
    public function waAfterSettingsAction() { //Create Wa Event => Settings [wa-event-settings]
        $this->view->menu = "WAAFTERSETTINGS";

        $this->view->waId = $this->getRequest()->getPost('waId');

        if ($this->view->waId > 0) {

            $this->view->editwaafterdetails = $this->waAfterDetailsByIdAction();

            $waeventdetailbyid = json_decode($this->view->editwaafterdetails);
            $this->view->wacategory = $waeventdetailbyid->type;
            $this->view->watitle = $waeventdetailbyid->message_title;
        } else {
            $this->view->wacategory = $this->getRequest()->getPost('wacategory');
            $this->view->watitle = $this->getRequest()->getPost('watitle');
        }
        if ($this->view->wacategory == '') {
            $this->_redirect($this->makeUrl('/mywa/create-wa'));
        }
    }

    public function waAfterDetailsPopupAction() {
        $decoded = $this->common->Decoded();
        $this->view->waId = $decoded['waId'];
        $this->_helper->layout->disableLayout();
    }
    
    public function waAfterPreviewAction() {
        $this->_helper->layout->disableLayout();
        $decoded = $this->common->Decoded();
//        print_r($decoded);
//        exit;

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
        $this->view->vital_check = $decoded['vital_check'];
        $this->view->vital_value = $decoded['vital_value'];
        $this->view->uploaded_files = $decoded['uploaded_files'];

        //list of selected friends ids
        $this->view->receiver_userset = serialize($receiver_userset);
        //If friends are not in friend list, we can enter his name and (emailid 0r phone).
        $this->view->receiver_email_phoneset = serialize($data);
    }

    public function createAfterAction() { //Create Wa Event [create-event]
        $userTable = new Application_Model_DbTable_Users();
        $waTable = new Application_Model_DbTable_Wa();
        $waReceiverTable = new Application_Model_DbTable_WaReceiver();
        $waTrusteeTable = new Application_Model_DbTable_WaTrustee();
        $waEventSendTable = new Application_Model_DbTable_WaEventSendDetails();

        $decoded = $this->common->Decoded();
        $userId = $this->loggedUserRow->userId;
        $wa_id = $decoded['waId'];

        $message_title = $decoded['message_title'];
        $uploaded_files = ($decoded['uploaded_files'] != '') ? explode(',', $decoded['uploaded_files']) : '';
        $receiver_userset = unserialize($decoded['receiver_userset']);
        $receiver_trusteeset   = unserialize($decoded['receiver_trusteeset']);
        $receiver_email_phoneset = unserialize($decoded['receiver_email_phoneset']);
        $is_chat_message = $decoded['is_chat_message'];
        $text = $decoded['message'];
        $vital_check = $decoded['vital_check'];
        $vital_value = $decoded['vital_value'];

        if (($userRow = $userTable->getRowById($userId)) && $userRow->isActive()) {

            $waRow = false;
            $is_existing_wa = false;

            if ($wa_id) {
                $is_existing_wa = true;
                $waRow = $waTable->getRowById($wa_id);
            }

            $data = array(
                'user_id' => $userId,
                'type' => Application_Model_DbTable_Wa::WA_TYPE_AFTERLIFE,
                'message_title' => $message_title,
                'creation_date' => date("Y-m-d H:i:s"),
                'modification_date' => date("Y-m-d H:i:s"),
                'text'                  => $text
            );

            $db = $this->db;
            $db->beginTransaction();

            try {
                $imgagearr = array('jpg','jpeg','gif','png');
                $audioarr = array('mp3');
                $videoarr = array('mp4');
                if (count($uploaded_files) > 0) {
                    foreach ($uploaded_files as $key => $val) {
                        $ext = explode('.', $val);
                        if (in_array(strtolower($ext[1]), $audioarr)) {//Audios
                            $data = array_merge($data, array(
                                'audio_link' => $val
                            ));
                        } else if (in_array(strtolower($ext[1]), $videoarr)) { //Videos
                            $video_ext = explode('.', $val);
                            $data = array_merge($data, array(
                                'video_link' => $val,
                                'thumbnail_link' => $video_ext[0] . '_thumb.jpg'
                            ));
                        } else if (in_array(strtolower($ext[1]), $imgagearr)) { //Images
                            $data = array_merge($data, array(
                                'image_link' => $val
                            ));
                        }
                    }
                }

                if ($waRow) {
                    $waRow->setFromArray($data);
                    $waRow->save();
                } else {
                    $waRow = $waTable->createRow($data);
                    $waRow->save();
                }

                if ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_HOUR) {
                    //       $event_send_date = date('Y-m-d H:i:s', strtotime('+90 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+5 min'));
                } elseif ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_DAYS) {
                    //   $event_send_date = date('Y-m-d H:i:s', strtotime('+180 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+10 min'));
                } elseif ($vital_check == Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_WEEK) {
                    //   $event_send_date = date('Y-m-d H:i:s', strtotime('+180 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+10 min'));
                }else { //VITAL_CHECK_MONTH
                    //    $event_send_date = date('Y-m-d H:i:s', strtotime('+365 days'));
                    $event_send_date = date('Y-m-d H:i:s', strtotime('+15 min'));
                }

                $eventSendDetailData = array(
                    'wa_id' => $waRow->wa_id,
                    'creation_date' => date("Y-m-d H:i:s"),
                    'vital_check' => $vital_check,
                    'vital_value' => $vital_value,
                    'event_send_date' => $event_send_date,
                    'owner_alert_time' => $event_send_date,
                    'owner_alert_count' => 0,
                    'trustee_alert_count' => 0
                );

                $eventSendDetailRow = false;

                if ($is_existing_wa) {
                    $eventSendDetailRow = $waEventSendTable->getRowByWaId($waRow->wa_id);
                    
                }

                if ($eventSendDetailRow) {
                        $eventSendDetailRow->setFromArray($eventSendDetailData);
                        $eventSendDetailRow->save();
                } else {
                    $eventSendDetailRow = $waEventSendTable->createRow($eventSendDetailData);
                    $eventSendDetailRow->save();
                }

                if ($waRow->wa_id) {
                    if($is_existing_wa){
                           $waReceiverTable->deleteReceivers($waRow->wa_id);
                           $waTrusteeTable->deleteReceivers($waRow->wa_id);
                        }
                        
                        foreach ($receiver_userset as $receiverId){
                            $data = array(
                                'wa_id'         => $waRow->wa_id,
                                'receiver_id'   => $receiverId
                            );
                            
                            if($receiverId != $userId){
                                $waReceiverRow = $waReceiverTable->createRow($data);
                                $waReceiverRow->save();
                            }
                        }
                        
                        
                        foreach ($receiver_trusteeset as $receiverId){
                            $data = array(
                                'wa_id'         => $waRow->wa_id,
                                'receiver_id'   => $receiverId
                            );
                            

                            if($receiverId != $userId){
                                $waTrusteeRow = $waTrusteeTable->createRow($data);
                                $waTrusteeRow->save();
                            }
                        }

                    for ($i = 0; $i < count($receiver_email_phoneset['name']); $i++) {
                        $waUserRow = false;
                        $name = $receiver_email_phoneset['name'][$i];
                        $email_phone = $receiver_email_phoneset['email_phone'][$i];
                        $email = '';
                        $phone = '';
                        if (strpos($email_phone, '@')) {
                            $email = $email_phone;
                        } else {
                            $phone = $email_phone;
                        }

                        if ($email != '') {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userEmail", $email);
                        }
                        if (!$waUserRow && $phone) {
                            $waUserRow = $userTable->checkDuplicateRecordByField("userPhone", $phone);
                        }
                        $data = array(
                            'wa_id' => $waRow->wa_id,
                            'receiver_name' => $name,
                            'receiver_email' => $email,
                            'receiver_phone' => $phone,
                            'receiver_id' => $waUserRow ? $waUserRow->userId : new Zend_Db_Expr("NULL")
                        );
                        if (!$waUserRow || ($waUserRow->userId != $userId)) {
                            $waReceiverRow = $waReceiverTable->createRow($data);
                            $waReceiverRow->save();
                        }
                    }
                } else {
                    $this->common->displayMessage("There is some error", "1", array(), "12");
                }
            } catch (Exception $e) {
                $db->rollBack();
                $this->common->displayMessage("There is some error", "1", array(), "12");
            }
            $db->commit();
            $this->common->displayMessage("WA created successfully", "0", array(), "0");
        } else {
            $this->common->displayMessage("User account does not exist", "1", array(), "2");
        }
    }
    
    
    
    /******** Common Wa function (Go, Event, After) **********************/

    public function createWaAction() {
        $this->view->menu = "MYWA";
    }
    
    public function waFriendsListAction() {
        $this->_helper->layout->disableLayout();
        $friendTable = new Application_Model_DbTable_Friends();
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $blockUserTable = new Application_Model_DbTable_BlockUsers();

        $decoded = $this->common->Decoded();

        /*     $decoded = array(
          "last_request_time" => "",
          "userDeviceId"      => "355004055809100",
          "userId"            => "5",
          "userSecurity"      => "afe2eb9b1de658a39e896591999e1b59"
          );
         */

        $userId = $this->loggedUserRow->userId;

        $last_request_time = '';

        if ($userRow = $userTable->getRowById($userId)) {


            $userRow->getFriendListTime = date("Y-m-d H:i:s");
            $userRow->save();

            $friendRowset = $friendTable->getMyFriendList($userId, $last_request_time, true);

            $friendData = array();

            foreach ($friendRowset as $friendRow) {

                if (($last_request_time == "") || (strtotime($friendRow->modifyDate) > $last_request_time) || (strtotime($friendRow->userModifieddate) > $last_request_time)) {
                    $hide_profile = "0";
                    $friendName = "";
                    $friendFullName = "";

                    /**
                     *  if $friendRow->user_id exist then it is wa user otherwise non wa user
                     */
                    if ($friendRow->user_id) {
                        if ($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId, $friendRow->user_id)) {
                            $hide_profile = $editFriendTrusteeRow->hideProfile;
                            $friendName = $editFriendTrusteeRow->name;
                        } else {
                            $friendName = $friendRow->userNickName;
                        }
                        $friendFullName = $friendRow->userFullName;
                    } else {
                        $friendName = $friendRow->friendName;  // non wa user
                    }

                    $trusteeRow = false;
                    $blockUserRow = false;

                    if ($friendRow->user_id) {
                        $trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $friendRow->user_id);
                        $blockUserRow = $blockUserTable->getBlockRelation($friendRow->user_id, $userId);
                    }

                    $friendData[] = array(
                        'id' => $friendRow->id,
                        'friendId' => ($friendRow->user_id) ? $friendRow->user_id : "0",
                        'isTrustee' => ($trusteeRow && ($trusteeRow->status == "1")) ? "1" : "0",
                        'hideProfile' => $hide_profile,
                        'status' => $friendRow->status,
                        'friendName' => $friendName,
                        'friendFullName' => $friendFullName,
                        'friendEmail' => ($friendRow->friendEmail && $friendRow->is_send_request) ? $friendRow->friendEmail : ($friendRow->userEmail ? $friendRow->userEmail : ""),
                        'friendCountryCode' => ($friendRow->userCountryCode) ? $friendRow->userCountryCode : "",
                        'friendPhone' => ($friendRow->friendPhone) ? $friendRow->friendPhone : ($friendRow->phoneWithCode ? $friendRow->phoneWithCode : ""),
                        'friendFbId' => ($friendRow->facebookId) ? $friendRow->facebookId : ($friendRow->userFbId ? $friendRow->userFbId : ""),
                        'friendTwitterId' => ($friendRow->userTwitterId) ? $friendRow->userTwitterId : "",
                        'isOnline' => ($friendRow->user_id) ? $userSettingTable->isUserOnline($friendRow->user_id) : "0",
                        'lastSeenTime' => ($friendRow->lastSeenTime) ? $friendRow->lastSeenTime : ($friendRow->acceptDate ? $friendRow->acceptDate : $friendRow->creationDate),
                        'friendImage' => ($friendRow->userImage) ? $this->makeUrl($friendRow->userImage) : "",
                        'creationDate' => $friendRow->creationDate,
                        'quickBloxId' => ($friendRow->quickBloxId) ? $friendRow->quickBloxId : "",
                        'profileStatus' => ($friendRow->profileStatus) ? $friendRow->profileStatus : "",
                        'user_location' => ($friendRow->user_location) ? $friendRow->user_location : "",
                        'block_by_user' => ($blockUserRow) ? $blockUserRow->status : "0"
                    );
                }
            }


            $responseData = array(
                'response_string' => 'Friends list',
                'error_code' => '0',
                'response_error_key' => '0',
                'last_request_time' => date("Y-m-d H:i:s"),
                'result' => $friendData
            );

            echo json_encode($responseData);
            exit;
        } else {
            $this->common->displayMessage("Account does not exist", "1", array(), "2");
        }
    }
    
    public function uploadfileAction() {//Upload WA images,audio and video.
        $type = explode('/', $_FILES['file']['type']);
        if ($type[0] == "audio") { //WA Audio
            $response = $this->common->uploadMedia($_FILES['file'], 'audio');
            echo $response['new_file_name'];
        } else if ($type[0] == "video") { //Wa Videos
            $response = $this->common->uploadMedia($_FILES['file'], 'video');

            $file_path = "upload/" . $response['new_file_name'];
            $parse_file_name = explode(".", $response['new_file_name']);
            $thumbnail_path = '/images/' . $parse_file_name['0'] . "_thumb.jpg";
            $video_path = '/' . $file_path;
            $this->createThumbnail($video_path, $thumbnail_path);
            echo $response['new_file_name'];
        } else { //WA Images, Pdf
            $response = $this->common->uploadImage($_FILES['file']);
            echo $response['new_file_name'];
        }

        exit;
    }

    public function unlinkfileAction() {
        $imgagearr = array('jpg','jpeg','gif','png');
        $audioarr = array('mp3');
        $videoarr = array('mp4');
        $decoded = $this->common->Decoded();
        $imagepath = strtolower($decoded['imagepath']);
        
        if (in_array($imagepath, $audioarr)) {//Audios
            unlink('upload/' . $imagepath);
        } else if (in_array($imagepath, $videoarr)) { //Videos
            unlink('upload/' . $imagepath);
        } else if (in_array($imagepath, $imgagearr)) { //Videos
            unlink('images/' . $imagepath);
        }
        exit;
    }
    
    public function deleteAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $waTable = new Application_Model_DbTable_Wa();

        $decoded = $this->common->Decoded();
        $userId         = $this->loggedUserRow->userId;
        $wa_id          = $decoded['waId'];

           if(($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())){
               
               if($waRow = $waTable->getRowById($wa_id)){
                   $waRow->is_deleted = "1";
                   $waRow->modification_date = date("Y-m-d H:i:s");
                   $waRow->save();
               }else{
                   $this->common->displayMessage('Wa id is not correct',"1",array(),"4");
               }
              
               $this->common->displayMessage('Wa deleted successfully',"0",array(),"0");
           }else{
                $this->common->displayMessage('Account does not exist',"1",array(),"2");
           }

    }
    
    
    

    /******** SOS Alert **********************/
    
    public function sendMessageAction(){

    }
    
    public function sendMessagePhoneEmailAction() {
        
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $friendTable = new Application_Model_DbTable_Friends();
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $decoded = $this->common->Decoded();
        
        /*Maindetory Parameters*/
        $userSecurity       = $decoded['userSecurity'];
        $userId             = $decoded['userId'];  //'42'
        $userDeviceId       = $decoded['userDeviceId'];
        $userDeviceToken    = $decoded['userDeviceToken'];   
        $userLat            = $decoded['userLat'];  //'28.615400'
        $userLong           = $decoded['userLong']; //'77.374331'
        
        /*Optional Paramenters*/
        $userPhoneSet       = $decoded['userPhoneSet'];   
        $userEmailSet       = $decoded['userEmailSet'];   
        $waUserIds          = $decoded['waUserIds'];   //'4'
        $sosType            = $decoded['sosType']; //'2'    
                              //type =>  1: all near by user, 
                                      // 2: selected by user [Need Wa user ids]

        /*
            $userSecurity       =   'afe2eb9b1de658a39e896591999e1b59';
            $userId             =   '4';
            $userDeviceId       =   '355004055809100';
            $userDeviceToken    =   'APA91bFKQ67cfmr6BifFZ2jTElp0qaMmGct0tqlUmKZ4Y2pJeNl6dLRWa9HCDHEECTJ7-AGcBBbYOj5rsI5jcWhv9n3E2nyUSECne6bOZqUE1XYjqv-Ds7zvr3j-NpX6HOErBjrHGQ0rLBlk1CdjjXlUyI3EPmFsTQ';
            $userLat            =   '28.615400';
            $userLong           =   '77.374331';

            $userPhoneSet       =   '+919910586909';
            $userEmailSet       =   'rajesh.pal@appstudioz.com';
            $waUserIds          =   '4';
            $sosType            =   '2';
        */      

        if($userSecurity == $this->servicekey){
            
            $this->common->checkEmptyParameter1(array($userId,$userDeviceId,$userDeviceToken,$userLat,$userLong,$sosType));
            
            if(($sosType =='2') && ($userPhoneSet == "") && ($userEmailSet == "") && ($waUserIds == "") ){
                $this->common->displayMessage("Required parameters missing.","1",array(),"1");
            }

            $this->common->isUserLogin($userId,$userDeviceId);

            if(($userRow = $userTable->getRowById($userId)) && $userRow->isActive()){
                
                
                //Get Address Using Lat Long
                $address = $this->common->getAddressByLatLong($userLat, $userLong);
                
                
                //Send SMS to multiple Phone Numbers.
                if($userPhoneSet !=''){
                    $phoneList = explode(',',$userPhoneSet);
                    if(count($phoneList)>0){
                        foreach($phoneList as $key=>$phoneNumber){
                            $message = $userRow->userNickName." is in problem at ".$address.".";
                            $this->common->sendSms_recommend($phoneNumber,$message);
                        }
                    }
                }

                //Send Email to multiple EmailIds.
                if($userEmailSet !=''){
                    $emailList = explode(',',$userEmailSet);
                    if(count($emailList)>0){
                        foreach($emailList as $key=>$email){
                                $para= array(
                                    'sender_name'   => $userRow->userNickName,
                                    'address'       => $address
                                );
                                $this->user->sendmail($para,'sosalert.phtml',$email,'SOS Alert');
                        }
                    }
                }
                    
                    
                //SOS CONDITIONS
                
                if($sosType == '1'){ //type =>  1 : all near by user

                        //Latitude and Longitude
                        $distance = "(((acos(sin(($userLat*pi()/180)) * sin((users.userLatitude*pi()/180))+cos(($userLat*pi()/180)) * cos((users.userLatitude*pi()/180)) * cos((($userLong- users.userLongitude)* pi()/180))))*180/pi())*60*1.1515)";

                        $select = $userTable->select()->setIntegrityCheck(false)
                                     ->from('users',array('*','distance' => new Zend_Db_Expr($distance)))
                                     ->joinInner('accountSetting','accountSetting.userId=users.userId',array('is_volunteer'))
                                     ->where('userStatus =?',  Application_Model_DbTable_Users::STATUS_ACTIVE)
                                     ->where('is_volunteer =?',1)
                                     ->where('users.userId !=?', $userId)
                                     ->having('distance <?',2);
                        $nearUsersDetail = $userTable->fetchAll($select);
   
                        foreach($nearUsersDetail as $id){
                            
                                //Send Push Notification
                                $message = $userRow->userNickName." is in problem at ".$address.".";
                                
                                $resultData =  array(
                                    'userImage' => "",
                                    'userId'    => "",
                                    'userName'  => ""
                                );
                                
                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($id);
                                foreach ($userLoginDeviceRowset as $loginDeviceRow){

                                     if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'sos_alert', 'userId'=>"",'userName'=>"",'userImage'=>"");
                                    }else{
                                        $payload = array(
                                            'message'   => $message,
                                            'type'      => "sos_alert",
                                            'result'    => $resultData
                                       );
                                        $payload = json_encode($payload);
                                    }
//                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);

                                }
                        }

                }else if($sosType == '2'){ //type =>  2 : Selected user (Wa User Ids)
                
                    
                    if($waUserIds!=''){ //WA Users
                        
                        $waUsersList = explode(',',$waUserIds);
                        
                        if(count($waUsersList)>0){
                            foreach($waUsersList as $id){
                                
                                //Send Push Notification
                                $message = $userRow->userNickName." is in problem at ".$address.".";
                                
                                $resultData =  array(
                                    'userImage' => "",
                                    'userId'    => "",
                                    'userName'  => ""
                                );
                                
                                $userLoginDeviceRowset = $userSettingTable->userLoginDeviceRowset($id);

                                foreach ($userLoginDeviceRowset as $loginDeviceRow){
                                    
                                     if($loginDeviceRow->userDeviceType == "iphone"){
                                        $payload['aps'] = array('alert'=>$message,'badge'=>0,'sound' =>'Default','type' =>'sos_alert', 'userId'=>"",'userName'=>"",'userImage'=>"");
                                    }else{
                                        $payload = array(
                                            'message'   => $message,
                                            'type'      => "sos_alert",
                                            'result'    => $resultData
                                       );
                                        $payload = json_encode($payload);
                                    }
                                    
                                    $this->common->sendPushMessage($loginDeviceRow->userDeviceType ,$loginDeviceRow->userDeviceToken,  $payload);   

                                }//END PUSH

                            } 
                        }
                    }
                }//End of SOS else condition
                
            }else{
                $this->common->displayMessage("User account does not exist","1",array(),"2");
            }

        }else{
            $this->common->displayMessage("You could not access this web-service","1",array(),"3");
        }

        $this->_helper->layout->disableLayout();
        exit;
    }

    
    /***** Truncate all tables except [adminUsers, countries and notification_types] ********************************/
    
    public function truncateAction(){

        $truncateTables = new Application_Model_DbTable_TruncateAll();
        $truncateTables ->truncateTables();
        $this->_helper->layout->disableLayout();
        exit;
        
    }
    
}

?>
