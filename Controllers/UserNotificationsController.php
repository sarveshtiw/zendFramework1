<?php

class UserNotificationsController extends My_Controller_Abstract {

    public $friendTable;

    public function init() {
        $this->friendTable = new Application_Model_DbTable_Friends();
        parent::init();
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->db->setFetchMode(Zend_Db::FETCH_OBJ);
        $this->_helper->layout->disableLayout();
//        if ($this->loggedUserRow->userId > 0) {
//            
//        } else {
//            $this->_redirect($this->makeUrl('/'));
//        }
    }

    public function listAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();

        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];

        $last_request_time = (isset($decoded['last_request_time']) && $decoded['last_request_time'] ) ? $decoded['last_request_time'] : "";

        /*
          $userId = "14";
          $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
          $last_request_time = "2014-11-07 08:22:50";
         */

        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userSecurity, $userId));

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
                $userTable->updateRequestTime("getFriendListTime", $userId);
                $userTable->updateRequestTime("getTrusteeListTime", $userId);
                $select = $userNotificationTable->select()->setIntegrityCheck(false)
                        ->from('user_notifications', array("*"))
                        ->joinInner('users', 'users.userId = user_notifications.from_user_id', array("userId", "userNickName", "userFullName", "userImage"))
                        ->where('user_notifications.user_id =?', $userId);

                if ($last_request_time) {
                    $select->where('(user_notifications.modification_date >?', $last_request_time)
                            ->orWhere('users.userModifieddate >?)', $last_request_time);
                }

                $select->order("user_notifications.modification_date desc");

                $notificationRowset = $userNotificationTable->fetchAll($select);

                $response = array();

                foreach ($notificationRowset as $notificationRow) {


                    $response[] = array(
                        'userId' => $notificationRow->userId,
                        'notification_id' => $notificationRow->id,
                        'userImage' => ($notificationRow->userImage) ? $this->makeUrl($notificationRow->userImage) : "",
                        "userNickName" => $notificationRow->userNickName,
                        "userFullName" => $notificationRow->userFullName,
                        'message' => $notificationRow->message,
                        'creationDate' => $notificationRow->creation_date,
                        'modificationDate' => $notificationRow->modification_date
                    );
                }

                $result_arr = array(
                    'response_string' => "notifications list",
                    'error_code' => "0",
                    'result' => $response,
                    'response_error_key' => "0",
                    'last_request_time' => date("Y-m-d H:i:s")
                );

                echo json_encode($result_arr);
                exit;
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function deleteByIdAction() {
        $userTable = new Application_Model_DbTable_Users();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();

        $decoded = $this->common->Decoded();

        $userSecurity = $decoded['userSecurity'];
        $userId = $decoded['userId'];
        $notification_id = $decoded['notification_id'];
        $userDeviceId = $decoded['userDeviceId'];

        /*   $userId = "14";
          $userSecurity = "afe2eb9b1de658a39e896591999e1b59";
          $notification_id = "53";
         */
        if ($userSecurity == $this->servicekey) {
            $this->common->checkEmptyParameter1(array($userId, $notification_id));

            if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {

                $this->common->isUserLogin($userId, $userDeviceId);

                if ($notificationRow = $userNotificationTable->getRowById($notification_id)) {
                    if ($notificationRow->user_id == $userId) {

                        $notificationRow->delete();
                        $this->common->displayMessage("Notification deleted successfully", "0", array(), "0");
                    } else {
                        $this->common->displayMessage("You can't delete notification of other user", "1", array(), "5");
                    }
                } else {
                    $this->common->displayMessage("Notification id is not available", "1", array(), "4");
                }
            } else {
                $this->common->displayMessage("User account is not exist", "1", array(), "2");
            }
        } else {
            $this->common->displayMessage('You could not access this web-service', "1", array(), "3");
        }
    }

    public function listwebAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();
        $userId = $this->loggedUserRow->userId;

        $decoded = $this->common->Decoded();

        $offset = $decoded['offset']; //On scroll down change it (add 10 for next 10 records).
        $limit = $decoded['limit'];

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {

            $select = $userNotificationTable->select()->setIntegrityCheck(false)
                    ->from('user_notifications', array("*"))
                    ->joinInner('users', 'users.userId = user_notifications.from_user_id', array("userId", "userNickName", "userFullName", "userImage", 'quickBloxId'))
                    ->joinLeft('friends', 'user_notifications.friend_row_id = friends.id', array("frstatus" => "status", "frmodificationDate" => "modifyDate"))
                    ->joinLeft('trustees', 'user_notifications.trustee_row_id = trustees.id', array("trstatus" => "status", "trmodificationDate" => "modifyDate"))
                    ->where('user_notifications.user_id =?', $userId)
                    ->order("creation_date DESC")
                    ->limit($limit, $offset);



            $notificationRowset = $userNotificationTable->fetchAll($select);

            $response = array();

            foreach ($notificationRowset as $notificationRow) {
                if ($notificationRow->frmodificationDate > 0) {
                    $interval = date('F d', strtotime($notificationRow->frmodificationDate)) . ' at ' . date('h:i A', strtotime($notificationRow->frmodificationDate));
                } else if ($notificationRow->trmodificationDate > 0) {
                    $interval = date('F d', strtotime($notificationRow->trmodificationDate)) . ' at ' . date('h:i A', strtotime($notificationRow->trmodificationDate));
                } else {
                    $interval = date('F d', strtotime($notificationRow->modification_date)) . ' at ' . date('h:i A', strtotime($notificationRow->modification_date));
                }

                $response[] = array(
                    'userId' => $notificationRow->userId,
                    'notification_id' => $notificationRow->id,
                    'friend_row_id' => $notificationRow->friend_row_id,
                    'frstatus_row_id' => $notificationRow->frstatus,
                    'interval' => $interval,
                    'trustee_row_id' => $notificationRow->trustee_row_id,
                    'trstatus_row_id' => $notificationRow->trstatus,
                    'notification_type' => $notificationRow->notf_type_id,
                    'userImage' => ($notificationRow->userImage) ? $this->makeUrl($notificationRow->userImage) : "",
                    "userNickName" => $notificationRow->userNickName,
                    "userFullName" => $notificationRow->userFullName,
                    'message' => $notificationRow->message,
                    'creationDate' => $notificationRow->creation_date,
                    'modificationDate' => $notificationRow->modification_date,
                    'quickBloxId' => $notificationRow->quickBloxId,
                    'is_read' => $notificationRow->is_read
                );
            }

            $result_arr = array(
                'response_string' => "notifications list",
                'error_code' => "0",
                'result' => $response,
                'response_error_key' => "0",
                'last_request_time' => date("Y-m-d H:i:s")
            );



            echo json_encode($result_arr);
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function getListAction() {
        $this->_helper->layout->enableLayout();
        $html = $this->view->Action("listweb", "user-notifications", array());
        exit;
    }

    public function notificationcountAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();
        $userId = $this->loggedUserRow->userId;

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {

            $select = $userNotificationTable->select()->setIntegrityCheck(false)
                    ->from('user_notifications', array("*"))
                    ->joinInner('users', 'users.userId = user_notifications.user_id', array("userId", "userNickName", "userFullName", "userImage", "lastSeenNotification"))
                    ->where('user_notifications.user_id =?', $userId)
                    ->where('user_notifications.modification_date > users.lastSeenNotification');



            $notificationRowset = $userNotificationTable->fetchAll($select);


            $result_arr = array(
                'response_string' => "notifications list",
                'error_code' => "0",
                'notificationcount' => count($notificationRowset),
                'response_error_key' => "0",
                'last_request_time' => date("Y-m-d H:i:s")
            );


            echo json_encode($result_arr);
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function getNotificationCountAction() {
        $this->_helper->layout->enableLayout();
        $this->getDialog();
        $html = $this->view->Action("notificationcount", "user-notifications", array());
        exit;
    }

    /*
     * function     :   getDialog
     * param        :   none
     * description  :   user for getting all the user chatdialog from quickblox in which user involve 
     */

    public function getDialog() {
        $array = (object) array('userEmail' => $this->loggedUserRow->userEmail);
        $token = $this->quickBloxUserToken($array);
        $ch = curl_init($this->quickblox_details_new->api_end_point . '/chat/Dialog.json');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );

        $resultJson = curl_exec($ch);
        $resultArr = json_decode($resultJson, true);
//       echo "<pre>";
//       print_r($resultArr);die();
        if (is_array($resultArr['items'])) {
            foreach ($resultArr['items'] as $data) {
                $this->getMessageHistory($data, $token);
            }
        }
        // die();
    }

    /*
     * function     :   getMessageHostory
     * param        :   $data,$token(quickblox token)
     * description  :   used for getting the chat history of user for specific chat dialog  
     */

    public function getMessageHistory($data, $token) {
        $dialog_id = $data['_id'];
        $dialogMessageTable = new Application_Model_DbTable_DialogMessage();
        $result = $dialogMessageTable->getRowByChatDialog($dialog_id);
        $dateSent = ($result) ? "&date_sent[gt]=$result" : "";
        $url = $this->quickblox_details_new->api_end_point . "/chat/Message.json?sort_asc=date_sent&chat_dialog_id=$dialog_id$dateSent";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "QuickBlox-REST-API-Version: 0.1.0",
            "QB-Token: $token")
        );
        $resultJson = curl_exec($ch);
        $resultArr = json_decode($resultJson, true);
       if (count($resultArr['items']) > 0){
            $this->insertData2($resultArr, $data);
        }
           // $this->insertData2($resultArr, $data);
    }

    private function insertData($resultArr, $dialog_data) {
        try {
            $message = new Application_Model_DbTable_Message();
            foreach ($resultArr['items'] as $data) {
                $message->chat_dialog_id = $data['chat_dialog_id'];
                $message->created_at = $data['created_at'];
                $message->type = ($dialog_data['type']) ? $dialog_data['type'] : 3;
                $message->chat_type = ($dialog_data['data']) ? "3" : $dialog_data['type'];
                $message->occupants_ids = serialize($dialog_data['occupants_ids']);
                $arrData['content_type'] = 'text';
                $message->content_type = 'text';
                $message->recipient_id = $data['recipient_id'];
                $arrData['sender_id'] = $data['sender_id'];
                $message->sender_id = $data['sender_id'];
                $message->message_id = $data['_id'];
                $arrData['date_sent'] = $data['date_sent'];
                $message->date_sent = $data['date_sent'];
                $arrData['message'] = addslashes($this->setSmiley($data['message']));
                $message->message = addslashes($this->setSmiley($data['message']));
                $message->nick_name = $data['nick'];
                $message->updated_at = $data['updated_at'];
                $message->room_jid = isset($data['room_jid']) ? $data['room_jid'] : 0;
                $message->notification_type = isset($data['notification_type']) ? $data['notification_type'] : 0;
                $message->is_read = $data['read'];
                $message->thumbDataStr = isset($data['thumbDataStr']) ? $data['thumbDataStr'] : "";
                $message->file_send_status = isset($data['attachments']) ? 1 : 0;
                $message->attach_file_local_url = isset($data['fileUID']) ? $data['fileName'] : 0;
                if (isset($data['attachments']) && is_array($data['attachments'])) {
                    $message->attach_file_server_url = $data['attachments'][0]['url'];
                    $message->content_type = $data['attachments'][0]['type'];
                }
                $message->save();
                $this->friendTable->updateLastChat($arrData['recipient_id'], $arrData['sender_id'], array("message" => $arrData['message'], 'lastMessageDate' => $arrData['date_sent']));
                //$arrFinal[] = $arrData;
            }
            // $this->InsertMultipleRows('dialog_message', $arrFinal);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    /*
     * function     :   insertData
     * param        :   $arraydata,$type(dialog type 3->private,2->Group,1->Public group)
     * description  :   used for inserting data in dialog_msaage table for letter used
     */

    private function insertData2($resultArr, $dialog_data) {
        try {
            $arrFinal = array();
            foreach ($resultArr['items'] as $data) {
                $arrData['chat_dialog_id'] = $data['chat_dialog_id'];
                $arrData['created_at'] = $data['created_at'];
                $arrData['type'] = ($dialog_data['type']) ? $dialog_data['type'] : 3;
                $arrData['chat_type'] = ($dialog_data['data']) ? "3" : $arrData['type'];
                $arrData['occupants_ids'] = serialize($dialog_data['occupants_ids']);
                $arrData['content_type'] = 'text';
                $arrData['recipient_id'] = $data['recipient_id'];
                $arrData['sender_id'] = $data['sender_id'];
                $arrData['message_id'] = $data['_id'];
                $arrData['date_sent'] = $data['date_sent'];
                $arrData['message'] = addslashes($this->setSmiley($data['message']));
                $arrData['nick_name'] = $data['nick'];
                $arrData['updated_at'] = $data['updated_at'];
                $arrData['room_jid'] = isset($data['room_jid']) ? $data['room_jid'] : 0;
                $arrData['notification_type'] = isset($data['notification_type']) ? $data['notification_type'] : 0;
                $arrData['is_read'] = $data['read'];
                $arrData['thumbDataStr'] = isset($data['thumbDataStr']) ? $data['thumbDataStr'] : "";
                $arrData['file_send_status'] = isset($data['attachments']) ? 1 : 0;
                $arrData['attach_file_local_url'] = isset($data['fileUID']) ? $data['fileName'] : 0;
                if (isset($data['attachments']) && is_array($data['attachments'])) {
                    $arrData['attach_file_server_url'] = $data['attachments'][0]['url'];
                    $arrData['content_type'] = $data['attachments'][0]['type'];
                }
//                $user = new Application_Model_DbTable_Message();
//
//                $user->name = 'Bob';
//                $user->save();
                $this->friendTable->updateLastChat($arrData['recipient_id'], $arrData['sender_id'], array("message" => $arrData['message'], 'lastMessageDate' => $arrData['date_sent']));
                $arrFinal[] = $arrData;
            }
            $this->InsertMultipleRows('dialog_message', $arrFinal);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    function setSmiley($text) {
        $a = '{
                "😄": 0,
                "😃": 1,
                "😀": 2,
                "😊": 3,
                "☺️": 4,
                "😉": 5,
                "😍": 6,
                "😘": 7,
                "😚": 8,
                "😗": 9,
                "😙": 10,
                "😜": 11,
                "😝": 12,
                "😛": 13,
                "😳": 14,
                "😁": 15,
                "😔": 16,
                "😌": 17,
                "😒": 18,
                "😞": 19,
                "😣": 20,
                "😢": 21,
                "😂": 22,
                "😭": 23,
                "😪": 24,
                "😥": 25,
                "😰": 26,
                "😅": 27,
                "😓": 28,
                "😩": 29,
                "😫": 30,
                "😨": 31,
                "😱": 32,
                "😠": 33,
                "😡": 34,
                "😤": 35,
                "😖": 36,
                "😆": 37,
                "😋": 38,
                "😷": 39,
                "😎": 40,
                "😴": 41,
                "😵": 42,
                "😲": 43,
                "😟": 44,
                "😦": 45,
                "😧": 46,
                "😈": 47,
                "👿": 48,
                "😮": 49,
                "😬": 50,
                "😐": 51,
                "😕": 52,
                "😯": 53,
                "😶": 54,
                "😇": 55,
                "😏": 56,
                "😑": 57,
                "👲": 58,
                "👳": 59,
                "👮": 60,
                "👷": 61,
                "💂": 62,
                "👶": 63,
                "👦": 64,
                "👧": 65,
                "👨": 66,
                "👩": 67,
                "👴": 68,
                "👵": 69,
                "👱": 70,
                "👼": 71,
                "👸": 72,
                "😺": 73,
                "😸": 74,
                "😻": 75,
                "😽": 76,
                "😼": 77,
                "🙀": 78,
                "😿": 79,
                "😹": 80,
                "😾": 81,
                "👹": 82,
                "👺": 83,
                "🙈": 84,
                "🙉": 85,
                "🙊": 86,
                "💀": 87,
                "👽": 88,
                "💩": 89,
                "🔥": 90,
                "✨": 91,
                "🌟": 92,
                "💫": 93,
                "💥": 94,
                "💢": 95,
                "💦": 96,
                "💧": 97,
                "💤": 98,
                "💨": 99,
                "👂": 100,
                "👀": 101,
                "👃": 102,
                "👅": 103,
                "👄": 104,
                "👍": 105,
                "👎": 106,
                "👌": 107,
                "👊": 108,
                "✊": 109,
                "✌️": 110,
                "👋": 111,
                "✋": 112,
                "👐": 113,
                "👆": 114,
                "👇": 115,
                "👉": 116,
                "👈": 117,
                "🙌": 118,
                "🙏": 119,
                "☝️": 120,
                "👏": 121,
                "💪": 122,
                "🚶": 123,
                "🏃": 124,
                "💃": 125,
                "👫": 126,
                "👪": 127,
                "👬": 128,
                "👭": 129,
                "💏": 130,
                "💑": 131,
                "👯": 132,
                "🙆": 133,
                "🙅": 134,
                "💁": 135,
                "🙋": 136,
                "💆": 137,
                "💇": 138,
                "💅": 139,
                "👰": 140,
                "🙎": 141,
                "🙍": 142,
                "🙇": 143,
                "🎩": 144,
                "👑": 145,
                "👒": 146,
                "👟": 147,
                "👞": 148,
                "👡": 149,
                "👠": 150,
                "👢": 151,
                "👕": 152,
                "👔": 153,
                "👚": 154,
                "👗": 155,
                "🎽": 156,
                "👖": 157,
                "👘": 158,
                "👙": 159,
                "💼": 160,
                "👜": 161,
                "👝": 162,
                "👛": 163,
                "👓": 164,
                "🎀": 165,
                "🌂": 166,
                "💄": 167,
                "💛": 168,
                "💙": 169,
                "💜": 170,
                "💚": 171,
                "❤️": 172,
                "💔": 173,
                "💗": 174,
                "💓": 175,
                "💕": 176,
                "💖": 177,
                "💞": 178,
                "💘": 179,
                "💌": 180,
                "💋": 181,
                "💍": 182,
                "💎": 183,
                "👤": 184,
                "👥": 185,
                "💬": 186,
                "👣": 187,
                "💭": 188,
                "🐶": 189,
                "🐺": 190,
                "🐱": 191,
                "🐭": 192,
                "🐹": 193,
                "🐰": 194,
                "🐸": 195,
                "🐯": 196,
                "🐨": 197,
                "🐻": 198,
                "🐷": 199,
                "🐽": 200,
                "🐮": 201,
                "🐗": 202,
                "🐵": 203,
                "🐒": 204,
                "🐴": 205,
                "🐑": 206,
                "🐘": 207,
                "🐼": 208,
                "🐧": 209,
                "🐦": 210,
                "🐤": 211,
                "🐥": 212,
                "🐣": 213,
                "🐔": 214,
                "🐍": 215,
                "🐢": 216,
                "🐛": 217,
                "🐝": 218,
                "🐜": 219,
                "🐞": 220,
                "🐌": 221,
                "🐙": 222,
                "🐚": 223,
                "🐠": 224,
                "🐟": 225,
                "🐬": 226,
                "🐳": 227,
                "🐋": 228,
                "🐄": 229,
                "🐏": 230,
                "🐀": 231,
                "🐃": 232,
                "🐅": 233,
                "🐇": 234,
                "🐉": 235,
                "🐎": 236,
                "🐐": 237,
                "🐓": 238,
                "🐕": 239,
                "🐖": 240,
                "🐁": 241,
                "🐂": 242,
                "🐲": 243,
                "🐡": 244,
                "🐊": 245,
                "🐫": 246,
                "🐪": 247,
                "🐆": 248,
                "🐈": 249,
                "🐩": 250,
                "🐾": 251,
                "💐": 252,
                "🌸": 253,
                "🌷": 254,
                "🍀": 255,
                "🌹": 256,
                "🌻": 257,
                "🌺": 258,
                "🍁": 259,
                "🍃": 260,
                "🍂": 261,
                "🌿": 262,
                "🌾": 263,
                "🍄": 264,
                "🌵": 265,
                "🌴": 266,
                "🌲": 267,
                "🌳": 268,
                "🌰": 269,
                "🌱": 270,
                "🌼": 271,
                "🌐": 272,
                "🌞": 273,
                "🌝": 274,
                "🌚": 275,
                "🌑": 276,
                "🌒": 277,
                "🌓": 278,
                "🌔": 279,
                "🌕": 280,
                "🌖": 281,
                "🌗": 282,
                "🌘": 283,
                "🌜": 284,
                "🌛": 285,
                "🌙": 286,
                "🌍": 287,
                "🌎": 288,
                "🌏": 289,
                "🌋": 290,
                "🌌": 291,
                "🌠": 292,
                "⭐️": 293,
                "☀️": 294,
                "⛅️": 295,
                "☁️": 296,
                "⚡️": 297,
                "☔️": 298,
                "❄️": 299,
                "⛄️": 300,
                "🌀": 301,
                "🌁": 302,
                "🌈": 303,
                "🌊": 304,
                "🎍": 305,
                "💝": 306,
                "🎎": 307,
                "🎒": 308,
                "🎓": 309,
                "🎏": 310,
                "🎆": 311,
                "🎇": 312,
                "🎐": 313,
                "🎑": 314,
                "🎃": 315,
                "👻": 316,
                "🎅": 317,
                "🎄": 318,
                "🎁": 319,
                "🎋": 320,
                "🎉": 321,
                "🎊": 322,
                "🎈": 323,
                "🎌": 324,
                "🔮": 325,
                "🎥": 326,
                "📷": 327,
                "📹": 328,
                "📼": 329,
                "💿": 330,
                "📀": 331,
                "💽": 332,
                "💾": 333,
                "💻": 334,
                "📱": 335,
                "☎️": 336,
                "📞": 337,
                "📟": 338,
                "📠": 339,
                "📡": 340,
                "📺": 341,
                "📻": 342,
                "🔊": 343,
                "🔉": 344,
                "🔈": 345,
                "🔇": 346,
                "🔔": 347,
                "🔕": 348,
                "📢": 349,
                "📣": 350,
                "⏳": 351,
                "⌛️": 352,
                "⏰": 353,
                "⌚️": 354,
                "🔓": 355,
                "🔒": 356,
                "🔏": 357,
                "🔐": 358,
                "🔑": 359,
                "🔎": 360,
                "💡": 361,
                "🔦": 362,
                "🔆": 363,
                "🔅": 364,
                "🔌": 365,
                "🔋": 366,
                "🔍": 367,
                "🛁": 368,
                "🛀": 369,
                "🚿": 370,
                "🚽": 371,
                "🔧": 372,
                "🔩": 373,
                "🔨": 374,
                "🚪": 375,
                "🚬": 376,
                "💣": 377,
                "🔫": 378,
                "🔪": 379,
                "💊": 380,
                "💉": 381,
                "💰": 382,
                "💴": 383,
                "💵": 384,
                "💷": 385,
                "💶": 386,
                "💳": 387,
                "💸": 388,
                "📲": 389,
                "📧": 390,
                "📥": 391,
                "📤": 392,
                "✉️": 393,
                "📩": 394,
                "📨": 395,
                "📯": 396,
                "📫": 397,
                "📪": 398,
                "📬": 399,
                "📭": 400,
                "📮": 401,
                "📦": 402,
                "📝": 403,
                "📄": 404,
                "📃": 405,
                "📑": 406,
                "📊": 407,
                "📈": 408,
                "📉": 409,
                "📜": 410,
                "📋": 411,
                "📅": 412,
                "📆": 413,
                "📇": 414,
                "📁": 415,
                "📂": 416,
                "✂️": 417,
                "📌": 418,
                "📎": 419,
                "✒️": 420,
                "✏️": 421,
                "📏": 422,
                "📐": 423,
                "📕": 424,
                "📗": 425,
                "📘": 426,
                "📙": 427,
                "📓": 428,
                "📔": 429,
                "📒": 430,
                "📚": 431,
                "📖": 432,
                "🔖": 433,
                "📛": 434,
                "🔬": 435,
                "🔭": 436,
                "📰": 437,
                "🎨": 438,
                "🎬": 439,
                "🎤": 440,
                "🎧": 441,
                "🎼": 442,
                "🎵": 443,
                "🎶": 444,
                "🎹": 445,
                "🎻": 446,
                "🎺": 447,
                "🎷": 448,
                "🎸": 449,
                "👾": 450,
                "🎮": 451,
                "🃏": 452,
                "🎴": 453,
                "🀄️": 454,
                "🎲": 455,
                "🎯": 456,
                "🏈": 457,
                "🏀": 458,
                "⚽️": 459,
                "⚾️": 460,
                "🎾": 461,
                "🎱": 462,
                "🏉": 463,
                "🎳": 464,
                "⛳️": 465,
                "🚵": 466,
                "🚴": 467,
                "🏁": 468,
                "🏇": 469,
                "🏆": 470,
                "🎿": 471,
                "🏂": 472,
                "🏊": 473,
                "🏄": 474,
                "🎣": 475,
                "☕️": 476,
                "🍵": 477,
                "🍶": 478,
                "🍼": 479,
                "🍺": 480,
                "🍻": 481,
                "🍸": 482,
                "🍹": 483,
                "🍷": 484,
                "🍴": 485,
                "🍕": 486,
                "🍔": 487,
                "🍟": 488,
                "🍗": 489,
                "🍖": 490,
                "🍝": 491,
                "🍛": 492,
                "🍤": 493,
                "🍱": 494,
                "🍣": 495,
                "🍥": 496,
                "🍙": 497,
                "🍘": 498,
                "🍚": 499,
                "🍜": 500,
                "🍲": 501,
                "🍢": 502,
                "🍡": 503,
                "🍳": 504,
                "🍞": 505,
                "🍩": 506,
                "🍮": 507,
                "🍦": 508,
                "🍨": 509,
                "🍧": 510,
                "🎂": 511,
                "🍰": 512,
                "🍪": 513,
                "🍫": 514,
                "🍬": 515,
                "🍭": 516,
                "🍯": 517,
                "🍎": 518,
                "🍏": 519,
                "🍊": 520,
                "🍋": 521,
                "🍒": 522,
                "🍇": 523,
                "🍉": 524,
                "🍓": 525,
                "🍑": 526,
                "🍈": 527,
                "🍌": 528,
                "🍐": 529,
                "🍍": 530,
                "🍠": 531,
                "🍆": 532,
                "🍅": 533,
                "🌽": 534,
                "🏠": 535,
                "🏡": 536,
                "🏫": 537,
                "🏢": 538,
                "🏣": 539,
                "🏥": 540,
                "🏦": 541,
                "🏪": 542,
                "🏩": 543,
                "🏨": 544,
                "💒": 545,
                "⛪️": 546,
                "🏬": 547,
                "🏤": 548,
                "🌇": 549,
                "🌆": 550,
                "🏯": 551,
                "🏰": 552,
                "⛺️": 553,
                "🏭": 554,
                "🗼": 555,
                "🗾": 556,
                "🗻": 557,
                "🌄": 558,
                "🌅": 559,
                "🌃": 560,
                "🗽": 561,
                "🌉": 562,
                "🎠": 563,
                "🎡": 564,
                "⛲️": 565,
                "🎢": 566,
                "🚢": 567,
                "⛵️": 568,
                "🚤": 569,
                "🚣": 570,
                "⚓️": 571,
                "🚀": 572,
                "✈️": 573,
                "💺": 574,
                "🚁": 575,
                "🚂": 576,
                "🚊": 577,
                "🚉": 578,
                "🚞": 579,
                "🚆": 580,
                "🚄": 581,
                "🚅": 582,
                "🚈": 583,
                "🚇": 584,
                "🚝": 585,
                "🚋": 586,
                "🚃": 587,
                "🚎": 588,
                "🚌": 589,
                "🚍": 590,
                "🚙": 591,
                "🚘": 592,
                "🚗": 593,
                "🚕": 594,
                "🚖": 595,
                "🚛": 596,
                "🚚": 597,
                "🚨": 598,
                "🚓": 599,
                "🚔": 600,
                "🚒": 601,
                "🚑": 602,
                "🚐": 603,
                "🚲": 604,
                "🚡": 605,
                "🚟": 606,
                "🚠": 607,
                "🚜": 608,
                "💈": 609,
                "🚏": 610,
                "🎫": 611,
                "🚦": 612,
                "🚥": 613,
                "⚠️": 614,
                "🚧": 615,
                "🔰": 616,
                "⛽️": 617,
                "🏮": 618,
                "🎰": 619,
                "♨️": 620,
                "🗿": 621,
                "🎪": 622,
                "🎭": 623,
                "📍": 624,
                "🚩": 625,
                "🇯🇵": 626,
                "🇰🇷": 627,
                "🇩🇪": 628,
                "🇨🇳": 629,
                "🇺🇸": 630,
                "🇫🇷": 631,
                "🇪🇸": 632,
                "🇮🇹": 633,
                "🇷🇺": 634,
                "🇬🇧": 635,
                "1⃣": 636,
                "2⃣": 637,
                "3⃣": 638,
                "4⃣": 639,
                "5⃣": 640,
                "6⃣": 641,
                "7⃣": 642,
                "8⃣": 643,
                "9⃣": 644,
                "0⃣": 645,
                "🔟": 646,
                "🔢": 647,
                "#⃣": 648,
                "🔣": 649,
                "⬆️": 650,
                "⬇️": 651,
                "⬅️": 652,
                "➡️": 653,
                "🔠": 654,
                "🔡": 655,
                "🔤": 656,
                "↗️": 657,
                "↖️": 658,
                "↘️": 659,
                "↙️": 660,
                "↔️": 661,
                "↕️": 662,
                "🔄": 663,
                "◀️": 664,
                "▶️": 665,
                "🔼": 666,
                "🔽": 667,
                "↩️": 668,
                "↪️": 669,
                "ℹ️": 670,
                "⏪": 671,
                "⏩": 672,
                "⏫": 673,
                "⏬": 674,
                "⤵️": 675,
                "⤴️": 676,
                "🆗": 677,
                "🔀": 678,
                "🔁": 679,
                "🔂": 680,
                "🆕": 681,
                "🆙": 682,
                "🆒": 683,
                "🆓": 684,
                "🆖": 685,
                "📶": 686,
                "🎦": 687,
                "🈁": 688,
                "🈯️": 689,
                "🈳": 690,
                "🈵": 691,
                "🈴": 692,
                "🈲": 693,
                "🉐": 694,
                "🈹": 695,
                "🈺": 696,
                "🈶": 697,
                "🈚️": 698,
                "🚻": 699,
                "🚹": 700,
                "🚺": 701,
                "🚼": 702,
                "🚾": 703,
                "🚰": 704,
                "🚮": 705,
                "🅿️": 706,
                "♿️": 707,
                "🚭": 708,
                "🈷": 709,
                "🈸": 710,
                "🈂": 711,
                "Ⓜ️": 712,
                "🛂": 713,
                "🛄": 714,
                "🛅": 715,
                "🛃": 716,
                "🉑": 717,
                "㊙️": 718,
                "㊗️": 719,
                "🆑": 720,
                "🆘": 721,
                "🆔": 722,
                "🚫": 723,
                "🔞": 724,
                "📵": 725,
                "🚯": 726,
                "🚱": 727,
                "🚳": 728,
                "🚷": 729,
                "🚸": 730,
                "⛔️": 731,
                "✳️": 732,
                "❇️": 733,
                "❎": 734,
                "✅": 735,
                "✴️": 736,
                "💟": 737,
                "🆚": 738,
                "📳": 739,
                "📴": 740,
                "🅰": 741,
                "🅱": 742,
                "🆎": 743,
                "🅾": 744,
                "💠": 745,
                "➿": 746,
                "♻️": 747,
                "♈️": 748,
                "♉️": 749,
                "♊️": 750,
                "♋️": 751,
                "♌️": 752,
                "♍️": 753,
                "♎️": 754,
                "♏️": 755,
                "♐️": 756,
                "♑️": 757,
                "♒️": 758,
                "♓️": 759,
                "⛎": 760,
                "🔯": 761,
                "🏧": 762,
                "💹": 763,
                "💲": 764,
                "💱": 765,
                "©": 766,
                "®": 767,
                "™": 768,
                "❌": 769,
                "‼️": 770,
                "⁉️": 771,
                "❗️": 772,
                "❓": 773,
                "❕": 774,
                "❔": 775,
                "⭕️": 776,
                "🔝": 777,
                "🔚": 778,
                "🔙": 779,
                "🔛": 780,
                "🔜": 781,
                "🔃": 782,
                "🕛": 783,
                "🕧": 784,
                "🕐": 785,
                "🕜": 786,
                "🕑": 787,
                "🕝": 788,
                "🕒": 789,
                "🕞": 790,
                "🕓": 791,
                "🕟": 792,
                "🕔": 793,
                "🕠": 794,
                "🕕": 795,
                "🕖": 796,
                "🕗": 797,
                "🕘": 798,
                "🕙": 799,
                "🕚": 800,
                "🕡": 801,
                "🕢": 802,
                "🕣": 803,
                "🕤": 804,
                "🕥": 805,
                "🕦": 806,
                "✖️": 807,
                "➕": 808,
                "➖": 809,
                "➗": 810,
                "♠️": 811,
                "♥️": 812,
                "♣️": 813,
                "♦️": 814,
                "💮": 815,
                "💯": 816,
                "✔️": 817,
                "☑️": 818,
                "🔘": 819,
                "🔗": 820,
                "➰": 821,
                "〰": 822,
                "〽️": 823,
                "🔱": 824,
                "◼️": 825,
                "◻️": 826,
                "◾️": 827,
                "◽️": 828,
                "▪️": 829,
                "▫️": 830,
                "🔺": 831,
                "🔲": 832,
                "🔳": 833,
                "⚫️": 834,
                "⚪️": 835,
                "🔴": 836,
                "🔵": 837,
                "🔻": 838,
                "⬜️": 839,
                "⬛️": 840,
                "🔶": 841,
                "🔷": 842,
                "🔸": 843,
                "🔹": 844,
                "☺": 4,
                "✌": 110,
                "☝": 120,
                "❤": 172,
                "⭐": 293,
                "☀": 294,
                "⛅": 295,
                "☁": 296,
                "⚡": 297,
                "☔": 298,
                "❄": 299,
                "⛄": 300,
                "☎": 336,
                "⌛": 352,
                "⌚": 354,
                "✉": 393,
                "✂": 417,
                "✒": 420,
                "✏": 421,
                "🀄": 454,
                "⚽": 459,
                "⚾": 460,
                "⛳": 465,
                "☕": 476,
                "⛪": 546,
                "⛺": 553,
                "⛲": 565,
                "⛵": 568,
                "⚓": 571,
                "✈": 573,
                "⚠": 614,
                "⛽": 617,
                "♨": 620,
                "⬆": 650,
                "⬇": 651,
                "⬅": 652,
                "➡": 653,
                "↗": 657,
                "↖": 658,
                "↘": 659,
                "↙": 660,
                "↔": 661,
                "↕": 662,
                "◀": 664,
                "▶": 665,
                "↩": 668,
                "↪": 669,
                "ℹ": 670,
                "⤵": 675,
                "⤴": 676,
                "🈯": 689,
                "🈚": 698,
                "🅿": 706,
                "♿": 707,
                "Ⓜ": 712,
                "㊙": 718,
                "㊗": 719,
                "⛔": 731,
                "✳": 732,
                "❇": 733,
                "✴": 736,
                "♻": 747,
                "♈": 748,
                "♉": 749,
                "♊": 750,
                "♋": 751,
                "♌": 752,
                "♍": 753,
                "♎": 754,
                "♏": 755,
                "♐": 756,
                "♑": 757,
                "♒": 758,
                "♓": 759,
                "‼": 770,
                "⁉": 771,
                "❗": 772,
                "⭕": 776,
                "✖": 807,
                "♠": 811,
                "♥": 812,
                "♣": 813,
                "♦": 814,
                "✔": 817,
                "☑": 818,
                "〽": 823,
                "◼": 825,
                "◻": 826,
                "◾": 827,
                "◽": 828,
                "▪": 829,
                "▫": 830,
                "⚫": 834,
                "⚪": 835,
                "⬜": 839,
                "⬛": 840
            }';

        $newJson = '{"\ud83d\ude04":"0emojicon","\ud83d\ude03":"1emojicon","\ud83d\ude00":"2emojicon","\ud83d\ude0a":"3emojicon","\u263a\ufe0f":"4emojicon","\ud83d\ude09":"5emojicon","\ud83d\ude0d":"6emojicon","\ud83d\ude18":"7emojicon","\ud83d\ude1a":"8emojicon","\ud83d\ude17":"9emojicon","\ud83d\ude19":"10emojicon","\ud83d\ude1c":"11emojicon","\ud83d\ude1d":"12emojicon","\ud83d\ude1b":"13emojicon","\ud83d\ude33":"14emojicon","\ud83d\ude01":"15emojicon","\ud83d\ude14":"16emojicon","\ud83d\ude0c":"17emojicon","\ud83d\ude12":"18emojicon","\ud83d\ude1e":"19emojicon","\ud83d\ude23":"20emojicon","\ud83d\ude22":"21emojicon","\ud83d\ude02":"22emojicon","\ud83d\ude2d":"23emojicon","\ud83d\ude2a":"24emojicon","\ud83d\ude25":"25emojicon","\ud83d\ude30":"26emojicon","\ud83d\ude05":"27emojicon","\ud83d\ude13":"28emojicon","\ud83d\ude29":"29emojicon","\ud83d\ude2b":"30emojicon","\ud83d\ude28":"31emojicon","\ud83d\ude31":"32emojicon","\ud83d\ude20":"33emojicon","\ud83d\ude21":"34emojicon","\ud83d\ude24":"35emojicon","\ud83d\ude16":"36emojicon","\ud83d\ude06":"37emojicon","\ud83d\ude0b":"38emojicon","\ud83d\ude37":"39emojicon","\ud83d\ude0e":"40emojicon","\ud83d\ude34":"41emojicon","\ud83d\ude35":"42emojicon","\ud83d\ude32":"43emojicon","\ud83d\ude1f":"44emojicon","\ud83d\ude26":"45emojicon","\ud83d\ude27":"46emojicon","\ud83d\ude08":"47emojicon","\ud83d\udc7f":"48emojicon","\ud83d\ude2e":"49emojicon","\ud83d\ude2c":"50emojicon","\ud83d\ude10":"51emojicon","\ud83d\ude15":"52emojicon","\ud83d\ude2f":"53emojicon","\ud83d\ude36":"54emojicon","\ud83d\ude07":"55emojicon","\ud83d\ude0f":"56emojicon","\ud83d\ude11":"57emojicon","\ud83d\udc72":"58emojicon","\ud83d\udc73":"59emojicon","\ud83d\udc6e":"60emojicon","\ud83d\udc77":"61emojicon","\ud83d\udc82":"62emojicon","\ud83d\udc76":"63emojicon","\ud83d\udc66":"64emojicon","\ud83d\udc67":"65emojicon","\ud83d\udc68":"66emojicon","\ud83d\udc69":"67emojicon","\ud83d\udc74":"68emojicon","\ud83d\udc75":"69emojicon","\ud83d\udc71":"70emojicon","\ud83d\udc7c":"71emojicon","\ud83d\udc78":"72emojicon","\ud83d\ude3a":"73emojicon","\ud83d\ude38":"74emojicon","\ud83d\ude3b":"75emojicon","\ud83d\ude3d":"76emojicon","\ud83d\ude3c":"77emojicon","\ud83d\ude40":"78emojicon","\ud83d\ude3f":"79emojicon","\ud83d\ude39":"80emojicon","\ud83d\ude3e":"81emojicon","\ud83d\udc79":"82emojicon","\ud83d\udc7a":"83emojicon","\ud83d\ude48":"84emojicon","\ud83d\ude49":"85emojicon","\ud83d\ude4a":"86emojicon","\ud83d\udc80":"87emojicon","\ud83d\udc7d":"88emojicon","\ud83d\udca9":"89emojicon","\ud83d\udd25":"90emojicon","\u2728":"91emojicon","\ud83c\udf1f":"92emojicon","\ud83d\udcab":"93emojicon","\ud83d\udca5":"94emojicon","\ud83d\udca2":"95emojicon","\ud83d\udca6":"96emojicon","\ud83d\udca7":"97emojicon","\ud83d\udca4":"98emojicon","\ud83d\udca8":"99emojicon","\ud83d\udc42":"100emojicon","\ud83d\udc40":"101emojicon","\ud83d\udc43":"102emojicon","\ud83d\udc45":"103emojicon","\ud83d\udc44":"104emojicon","\ud83d\udc4d":"105emojicon","\ud83d\udc4e":"106emojicon","\ud83d\udc4c":"107emojicon","\ud83d\udc4a":"108emojicon","\u270a":"109emojicon","\u270c\ufe0f":"110emojicon","\ud83d\udc4b":"111emojicon","\u270b":"112emojicon","\ud83d\udc50":"113emojicon","\ud83d\udc46":"114emojicon","\ud83d\udc47":"115emojicon","\ud83d\udc49":"116emojicon","\ud83d\udc48":"117emojicon","\ud83d\ude4c":"118emojicon","\ud83d\ude4f":"119emojicon","\u261d\ufe0f":"120emojicon","\ud83d\udc4f":"121emojicon","\ud83d\udcaa":"122emojicon","\ud83d\udeb6":"123emojicon","\ud83c\udfc3":"124emojicon","\ud83d\udc83":"125emojicon","\ud83d\udc6b":"126emojicon","\ud83d\udc6a":"127emojicon","\ud83d\udc6c":"128emojicon","\ud83d\udc6d":"129emojicon","\ud83d\udc8f":"130emojicon","\ud83d\udc91":"131emojicon","\ud83d\udc6f":"132emojicon","\ud83d\ude46":"133emojicon","\ud83d\ude45":"134emojicon","\ud83d\udc81":"135emojicon","\ud83d\ude4b":"136emojicon","\ud83d\udc86":"137emojicon","\ud83d\udc87":"138emojicon","\ud83d\udc85":"139emojicon","\ud83d\udc70":"140emojicon","\ud83d\ude4e":"141emojicon","\ud83d\ude4d":"142emojicon","\ud83d\ude47":"143emojicon","\ud83c\udfa9":"144emojicon","\ud83d\udc51":"145emojicon","\ud83d\udc52":"146emojicon","\ud83d\udc5f":"147emojicon","\ud83d\udc5e":"148emojicon","\ud83d\udc61":"149emojicon","\ud83d\udc60":"150emojicon","\ud83d\udc62":"151emojicon","\ud83d\udc55":"152emojicon","\ud83d\udc54":"153emojicon","\ud83d\udc5a":"154emojicon","\ud83d\udc57":"155emojicon","\ud83c\udfbd":"156emojicon","\ud83d\udc56":"157emojicon","\ud83d\udc58":"158emojicon","\ud83d\udc59":"159emojicon","\ud83d\udcbc":"160emojicon","\ud83d\udc5c":"161emojicon","\ud83d\udc5d":"162emojicon","\ud83d\udc5b":"163emojicon","\ud83d\udc53":"164emojicon","\ud83c\udf80":"165emojicon","\ud83c\udf02":"166emojicon","\ud83d\udc84":"167emojicon","\ud83d\udc9b":"168emojicon","\ud83d\udc99":"169emojicon","\ud83d\udc9c":"170emojicon","\ud83d\udc9a":"171emojicon","\u2764\ufe0f":"172emojicon","\ud83d\udc94":"173emojicon","\ud83d\udc97":"174emojicon","\ud83d\udc93":"175emojicon","\ud83d\udc95":"176emojicon","\ud83d\udc96":"177emojicon","\ud83d\udc9e":"178emojicon","\ud83d\udc98":"179emojicon","\ud83d\udc8c":"180emojicon","\ud83d\udc8b":"181emojicon","\ud83d\udc8d":"182emojicon","\ud83d\udc8e":"183emojicon","\ud83d\udc64":"184emojicon","\ud83d\udc65":"185emojicon","\ud83d\udcac":"186emojicon","\ud83d\udc63":"187emojicon","\ud83d\udcad":"188emojicon","\ud83d\udc36":"189emojicon","\ud83d\udc3a":"190emojicon","\ud83d\udc31":"191emojicon","\ud83d\udc2d":"192emojicon","\ud83d\udc39":"193emojicon","\ud83d\udc30":"194emojicon","\ud83d\udc38":"195emojicon","\ud83d\udc2f":"196emojicon","\ud83d\udc28":"197emojicon","\ud83d\udc3b":"198emojicon","\ud83d\udc37":"199emojicon","\ud83d\udc3d":"200emojicon","\ud83d\udc2e":"201emojicon","\ud83d\udc17":"202emojicon","\ud83d\udc35":"203emojicon","\ud83d\udc12":"204emojicon","\ud83d\udc34":"205emojicon","\ud83d\udc11":"206emojicon","\ud83d\udc18":"207emojicon","\ud83d\udc3c":"208emojicon","\ud83d\udc27":"209emojicon","\ud83d\udc26":"210emojicon","\ud83d\udc24":"211emojicon","\ud83d\udc25":"212emojicon","\ud83d\udc23":"213emojicon","\ud83d\udc14":"214emojicon","\ud83d\udc0d":"215emojicon","\ud83d\udc22":"216emojicon","\ud83d\udc1b":"217emojicon","\ud83d\udc1d":"218emojicon","\ud83d\udc1c":"219emojicon","\ud83d\udc1e":"220emojicon","\ud83d\udc0c":"221emojicon","\ud83d\udc19":"222emojicon","\ud83d\udc1a":"223emojicon","\ud83d\udc20":"224emojicon","\ud83d\udc1f":"225emojicon","\ud83d\udc2c":"226emojicon","\ud83d\udc33":"227emojicon","\ud83d\udc0b":"228emojicon","\ud83d\udc04":"229emojicon","\ud83d\udc0f":"230emojicon","\ud83d\udc00":"231emojicon","\ud83d\udc03":"232emojicon","\ud83d\udc05":"233emojicon","\ud83d\udc07":"234emojicon","\ud83d\udc09":"235emojicon","\ud83d\udc0e":"236emojicon","\ud83d\udc10":"237emojicon","\ud83d\udc13":"238emojicon","\ud83d\udc15":"239emojicon","\ud83d\udc16":"240emojicon","\ud83d\udc01":"241emojicon","\ud83d\udc02":"242emojicon","\ud83d\udc32":"243emojicon","\ud83d\udc21":"244emojicon","\ud83d\udc0a":"245emojicon","\ud83d\udc2b":"246emojicon","\ud83d\udc2a":"247emojicon","\ud83d\udc06":"248emojicon","\ud83d\udc08":"249emojicon","\ud83d\udc29":"250emojicon","\ud83d\udc3e":"251emojicon","\ud83d\udc90":"252emojicon","\ud83c\udf38":"253emojicon","\ud83c\udf37":"254emojicon","\ud83c\udf40":"255emojicon","\ud83c\udf39":"256emojicon","\ud83c\udf3b":"257emojicon","\ud83c\udf3a":"258emojicon","\ud83c\udf41":"259emojicon","\ud83c\udf43":"260emojicon","\ud83c\udf42":"261emojicon","\ud83c\udf3f":"262emojicon","\ud83c\udf3e":"263emojicon","\ud83c\udf44":"264emojicon","\ud83c\udf35":"265emojicon","\ud83c\udf34":"266emojicon","\ud83c\udf32":"267emojicon","\ud83c\udf33":"268emojicon","\ud83c\udf30":"269emojicon","\ud83c\udf31":"270emojicon","\ud83c\udf3c":"271emojicon","\ud83c\udf10":"272emojicon","\ud83c\udf1e":"273emojicon","\ud83c\udf1d":"274emojicon","\ud83c\udf1a":"275emojicon","\ud83c\udf11":"276emojicon","\ud83c\udf12":"277emojicon","\ud83c\udf13":"278emojicon","\ud83c\udf14":"279emojicon","\ud83c\udf15":"280emojicon","\ud83c\udf16":"281emojicon","\ud83c\udf17":"282emojicon","\ud83c\udf18":"283emojicon","\ud83c\udf1c":"284emojicon","\ud83c\udf1b":"285emojicon","\ud83c\udf19":"286emojicon","\ud83c\udf0d":"287emojicon","\ud83c\udf0e":"288emojicon","\ud83c\udf0f":"289emojicon","\ud83c\udf0b":"290emojicon","\ud83c\udf0c":"291emojicon","\ud83c\udf20":"292emojicon","\u2b50\ufe0f":"293emojicon","\u2600\ufe0f":"294emojicon","\u26c5\ufe0f":"295emojicon","\u2601\ufe0f":"296emojicon","\u26a1\ufe0f":"297emojicon","\u2614\ufe0f":"298emojicon","\u2744\ufe0f":"299emojicon","\u26c4\ufe0f":"300emojicon","\ud83c\udf00":"301emojicon","\ud83c\udf01":"302emojicon","\ud83c\udf08":"303emojicon","\ud83c\udf0a":"304emojicon","\ud83c\udf8d":"305emojicon","\ud83d\udc9d":"306emojicon","\ud83c\udf8e":"307emojicon","\ud83c\udf92":"308emojicon","\ud83c\udf93":"309emojicon","\ud83c\udf8f":"310emojicon","\ud83c\udf86":"311emojicon","\ud83c\udf87":"312emojicon","\ud83c\udf90":"313emojicon","\ud83c\udf91":"314emojicon","\ud83c\udf83":"315emojicon","\ud83d\udc7b":"316emojicon","\ud83c\udf85":"317emojicon","\ud83c\udf84":"318emojicon","\ud83c\udf81":"319emojicon","\ud83c\udf8b":"320emojicon","\ud83c\udf89":"321emojicon","\ud83c\udf8a":"322emojicon","\ud83c\udf88":"323emojicon","\ud83c\udf8c":"324emojicon","\ud83d\udd2e":"325emojicon","\ud83c\udfa5":"326emojicon","\ud83d\udcf7":"327emojicon","\ud83d\udcf9":"328emojicon","\ud83d\udcfc":"329emojicon","\ud83d\udcbf":"330emojicon","\ud83d\udcc0":"331emojicon","\ud83d\udcbd":"332emojicon","\ud83d\udcbe":"333emojicon","\ud83d\udcbb":"334emojicon","\ud83d\udcf1":"335emojicon","\u260e\ufe0f":"336emojicon","\ud83d\udcde":"337emojicon","\ud83d\udcdf":"338emojicon","\ud83d\udce0":"339emojicon","\ud83d\udce1":"340emojicon","\ud83d\udcfa":"341emojicon","\ud83d\udcfb":"342emojicon","\ud83d\udd0a":"343emojicon","\ud83d\udd09":"344emojicon","\ud83d\udd08":"345emojicon","\ud83d\udd07":"346emojicon","\ud83d\udd14":"347emojicon","\ud83d\udd15":"348emojicon","\ud83d\udce2":"349emojicon","\ud83d\udce3":"350emojicon","\u23f3":"351emojicon","\u231b\ufe0f":"352emojicon","\u23f0":"353emojicon","\u231a\ufe0f":"354emojicon","\ud83d\udd13":"355emojicon","\ud83d\udd12":"356emojicon","\ud83d\udd0f":"357emojicon","\ud83d\udd10":"358emojicon","\ud83d\udd11":"359emojicon","\ud83d\udd0e":"360emojicon","\ud83d\udca1":"361emojicon","\ud83d\udd26":"362emojicon","\ud83d\udd06":"363emojicon","\ud83d\udd05":"364emojicon","\ud83d\udd0c":"365emojicon","\ud83d\udd0b":"366emojicon","\ud83d\udd0d":"367emojicon","\ud83d\udec1":"368emojicon","\ud83d\udec0":"369emojicon","\ud83d\udebf":"370emojicon","\ud83d\udebd":"371emojicon","\ud83d\udd27":"372emojicon","\ud83d\udd29":"373emojicon","\ud83d\udd28":"374emojicon","\ud83d\udeaa":"375emojicon","\ud83d\udeac":"376emojicon","\ud83d\udca3":"377emojicon","\ud83d\udd2b":"378emojicon","\ud83d\udd2a":"379emojicon","\ud83d\udc8a":"380emojicon","\ud83d\udc89":"381emojicon","\ud83d\udcb0":"382emojicon","\ud83d\udcb4":"383emojicon","\ud83d\udcb5":"384emojicon","\ud83d\udcb7":"385emojicon","\ud83d\udcb6":"386emojicon","\ud83d\udcb3":"387emojicon","\ud83d\udcb8":"388emojicon","\ud83d\udcf2":"389emojicon","\ud83d\udce7":"390emojicon","\ud83d\udce5":"391emojicon","\ud83d\udce4":"392emojicon","\u2709\ufe0f":"393emojicon","\ud83d\udce9":"394emojicon","\ud83d\udce8":"395emojicon","\ud83d\udcef":"396emojicon","\ud83d\udceb":"397emojicon","\ud83d\udcea":"398emojicon","\ud83d\udcec":"399emojicon","\ud83d\udced":"400emojicon","\ud83d\udcee":"401emojicon","\ud83d\udce6":"402emojicon","\ud83d\udcdd":"403emojicon","\ud83d\udcc4":"404emojicon","\ud83d\udcc3":"405emojicon","\ud83d\udcd1":"406emojicon","\ud83d\udcca":"407emojicon","\ud83d\udcc8":"408emojicon","\ud83d\udcc9":"409emojicon","\ud83d\udcdc":"410emojicon","\ud83d\udccb":"411emojicon","\ud83d\udcc5":"412emojicon","\ud83d\udcc6":"413emojicon","\ud83d\udcc7":"414emojicon","\ud83d\udcc1":"415emojicon","\ud83d\udcc2":"416emojicon","\u2702\ufe0f":"417emojicon","\ud83d\udccc":"418emojicon","\ud83d\udcce":"419emojicon","\u2712\ufe0f":"420emojicon","\u270f\ufe0f":"421emojicon","\ud83d\udccf":"422emojicon","\ud83d\udcd0":"423emojicon","\ud83d\udcd5":"424emojicon","\ud83d\udcd7":"425emojicon","\ud83d\udcd8":"426emojicon","\ud83d\udcd9":"427emojicon","\ud83d\udcd3":"428emojicon","\ud83d\udcd4":"429emojicon","\ud83d\udcd2":"430emojicon","\ud83d\udcda":"431emojicon","\ud83d\udcd6":"432emojicon","\ud83d\udd16":"433emojicon","\ud83d\udcdb":"434emojicon","\ud83d\udd2c":"435emojicon","\ud83d\udd2d":"436emojicon","\ud83d\udcf0":"437emojicon","\ud83c\udfa8":"438emojicon","\ud83c\udfac":"439emojicon","\ud83c\udfa4":"440emojicon","\ud83c\udfa7":"441emojicon","\ud83c\udfbc":"442emojicon","\ud83c\udfb5":"443emojicon","\ud83c\udfb6":"444emojicon","\ud83c\udfb9":"445emojicon","\ud83c\udfbb":"446emojicon","\ud83c\udfba":"447emojicon","\ud83c\udfb7":"448emojicon","\ud83c\udfb8":"449emojicon","\ud83d\udc7e":"450emojicon","\ud83c\udfae":"451emojicon","\ud83c\udccf":"452emojicon","\ud83c\udfb4":"453emojicon","\ud83c\udc04\ufe0f":"454emojicon","\ud83c\udfb2":"455emojicon","\ud83c\udfaf":"456emojicon","\ud83c\udfc8":"457emojicon","\ud83c\udfc0":"458emojicon","\u26bd\ufe0f":"459emojicon","\u26be\ufe0f":"460emojicon","\ud83c\udfbe":"461emojicon","\ud83c\udfb1":"462emojicon","\ud83c\udfc9":"463emojicon","\ud83c\udfb3":"464emojicon","\u26f3\ufe0f":"465emojicon","\ud83d\udeb5":"466emojicon","\ud83d\udeb4":"467emojicon","\ud83c\udfc1":"468emojicon","\ud83c\udfc7":"469emojicon","\ud83c\udfc6":"470emojicon","\ud83c\udfbf":"471emojicon","\ud83c\udfc2":"472emojicon","\ud83c\udfca":"473emojicon","\ud83c\udfc4":"474emojicon","\ud83c\udfa3":"475emojicon","\u2615\ufe0f":"476emojicon","\ud83c\udf75":"477emojicon","\ud83c\udf76":"478emojicon","\ud83c\udf7c":"479emojicon","\ud83c\udf7a":"480emojicon","\ud83c\udf7b":"481emojicon","\ud83c\udf78":"482emojicon","\ud83c\udf79":"483emojicon","\ud83c\udf77":"484emojicon","\ud83c\udf74":"485emojicon","\ud83c\udf55":"486emojicon","\ud83c\udf54":"487emojicon","\ud83c\udf5f":"488emojicon","\ud83c\udf57":"489emojicon","\ud83c\udf56":"490emojicon","\ud83c\udf5d":"491emojicon","\ud83c\udf5b":"492emojicon","\ud83c\udf64":"493emojicon","\ud83c\udf71":"494emojicon","\ud83c\udf63":"495emojicon","\ud83c\udf65":"496emojicon","\ud83c\udf59":"497emojicon","\ud83c\udf58":"498emojicon","\ud83c\udf5a":"499emojicon","\ud83c\udf5c":"500emojicon","\ud83c\udf72":"501emojicon","\ud83c\udf62":"502emojicon","\ud83c\udf61":"503emojicon","\ud83c\udf73":"504emojicon","\ud83c\udf5e":"505emojicon","\ud83c\udf69":"506emojicon","\ud83c\udf6e":"507emojicon","\ud83c\udf66":"508emojicon","\ud83c\udf68":"509emojicon","\ud83c\udf67":"510emojicon","\ud83c\udf82":"511emojicon","\ud83c\udf70":"512emojicon","\ud83c\udf6a":"513emojicon","\ud83c\udf6b":"514emojicon","\ud83c\udf6c":"515emojicon","\ud83c\udf6d":"516emojicon","\ud83c\udf6f":"517emojicon","\ud83c\udf4e":"518emojicon","\ud83c\udf4f":"519emojicon","\ud83c\udf4a":"520emojicon","\ud83c\udf4b":"521emojicon","\ud83c\udf52":"522emojicon","\ud83c\udf47":"523emojicon","\ud83c\udf49":"524emojicon","\ud83c\udf53":"525emojicon","\ud83c\udf51":"526emojicon","\ud83c\udf48":"527emojicon","\ud83c\udf4c":"528emojicon","\ud83c\udf50":"529emojicon","\ud83c\udf4d":"530emojicon","\ud83c\udf60":"531emojicon","\ud83c\udf46":"532emojicon","\ud83c\udf45":"533emojicon","\ud83c\udf3d":"534emojicon","\ud83c\udfe0":"535emojicon","\ud83c\udfe1":"536emojicon","\ud83c\udfeb":"537emojicon","\ud83c\udfe2":"538emojicon","\ud83c\udfe3":"539emojicon","\ud83c\udfe5":"540emojicon","\ud83c\udfe6":"541emojicon","\ud83c\udfea":"542emojicon","\ud83c\udfe9":"543emojicon","\ud83c\udfe8":"544emojicon","\ud83d\udc92":"545emojicon","\u26ea\ufe0f":"546emojicon","\ud83c\udfec":"547emojicon","\ud83c\udfe4":"548emojicon","\ud83c\udf07":"549emojicon","\ud83c\udf06":"550emojicon","\ud83c\udfef":"551emojicon","\ud83c\udff0":"552emojicon","\u26fa\ufe0f":"553emojicon","\ud83c\udfed":"554emojicon","\ud83d\uddfc":"555emojicon","\ud83d\uddfe":"556emojicon","\ud83d\uddfb":"557emojicon","\ud83c\udf04":"558emojicon","\ud83c\udf05":"559emojicon","\ud83c\udf03":"560emojicon","\ud83d\uddfd":"561emojicon","\ud83c\udf09":"562emojicon","\ud83c\udfa0":"563emojicon","\ud83c\udfa1":"564emojicon","\u26f2\ufe0f":"565emojicon","\ud83c\udfa2":"566emojicon","\ud83d\udea2":"567emojicon","\u26f5\ufe0f":"568emojicon","\ud83d\udea4":"569emojicon","\ud83d\udea3":"570emojicon","\u2693\ufe0f":"571emojicon","\ud83d\ude80":"572emojicon","\u2708\ufe0f":"573emojicon","\ud83d\udcba":"574emojicon","\ud83d\ude81":"575emojicon","\ud83d\ude82":"576emojicon","\ud83d\ude8a":"577emojicon","\ud83d\ude89":"578emojicon","\ud83d\ude9e":"579emojicon","\ud83d\ude86":"580emojicon","\ud83d\ude84":"581emojicon","\ud83d\ude85":"582emojicon","\ud83d\ude88":"583emojicon","\ud83d\ude87":"584emojicon","\ud83d\ude9d":"585emojicon","\ud83d\ude8b":"586emojicon","\ud83d\ude83":"587emojicon","\ud83d\ude8e":"588emojicon","\ud83d\ude8c":"589emojicon","\ud83d\ude8d":"590emojicon","\ud83d\ude99":"591emojicon","\ud83d\ude98":"592emojicon","\ud83d\ude97":"593emojicon","\ud83d\ude95":"594emojicon","\ud83d\ude96":"595emojicon","\ud83d\ude9b":"596emojicon","\ud83d\ude9a":"597emojicon","\ud83d\udea8":"598emojicon","\ud83d\ude93":"599emojicon","\ud83d\ude94":"600emojicon","\ud83d\ude92":"601emojicon","\ud83d\ude91":"602emojicon","\ud83d\ude90":"603emojicon","\ud83d\udeb2":"604emojicon","\ud83d\udea1":"605emojicon","\ud83d\ude9f":"606emojicon","\ud83d\udea0":"607emojicon","\ud83d\ude9c":"608emojicon","\ud83d\udc88":"609emojicon","\ud83d\ude8f":"610emojicon","\ud83c\udfab":"611emojicon","\ud83d\udea6":"612emojicon","\ud83d\udea5":"613emojicon","\u26a0\ufe0f":"614emojicon","\ud83d\udea7":"615emojicon","\ud83d\udd30":"616emojicon","\u26fd\ufe0f":"617emojicon","\ud83c\udfee":"618emojicon","\ud83c\udfb0":"619emojicon","\u2668\ufe0f":"620emojicon","\ud83d\uddff":"621emojicon","\ud83c\udfaa":"622emojicon","\ud83c\udfad":"623emojicon","\ud83d\udccd":"624emojicon","\ud83d\udea9":"625emojicon","\ud83c\uddef\ud83c\uddf5":"626emojicon","\ud83c\uddf0\ud83c\uddf7":"627emojicon","\ud83c\udde9\ud83c\uddea":"628emojicon","\ud83c\udde8\ud83c\uddf3":"629emojicon","\ud83c\uddfa\ud83c\uddf8":"630emojicon","\ud83c\uddeb\ud83c\uddf7":"631emojicon","\ud83c\uddea\ud83c\uddf8":"632emojicon","\ud83c\uddee\ud83c\uddf9":"633emojicon","\ud83c\uddf7\ud83c\uddfa":"634emojicon","\ud83c\uddec\ud83c\udde7":"635emojicon","1\u20e3":"636emojicon","2\u20e3":"637emojicon","3\u20e3":"638emojicon","4\u20e3":"639emojicon","5\u20e3":"640emojicon","6\u20e3":"641emojicon","7\u20e3":"642emojicon","8\u20e3":"643emojicon","9\u20e3":"644emojicon","0\u20e3":"645emojicon","\ud83d\udd1f":"646emojicon","\ud83d\udd22":"647emojicon","#\u20e3":"648emojicon","\ud83d\udd23":"649emojicon","\u2b06\ufe0f":"650emojicon","\u2b07\ufe0f":"651emojicon","\u2b05\ufe0f":"652emojicon","\u27a1\ufe0f":"653emojicon","\ud83d\udd20":"654emojicon","\ud83d\udd21":"655emojicon","\ud83d\udd24":"656emojicon","\u2197\ufe0f":"657emojicon","\u2196\ufe0f":"658emojicon","\u2198\ufe0f":"659emojicon","\u2199\ufe0f":"660emojicon","\u2194\ufe0f":"661emojicon","\u2195\ufe0f":"662emojicon","\ud83d\udd04":"663emojicon","\u25c0\ufe0f":"664emojicon","\u25b6\ufe0f":"665emojicon","\ud83d\udd3c":"666emojicon","\ud83d\udd3d":"667emojicon","\u21a9\ufe0f":"668emojicon","\u21aa\ufe0f":"669emojicon","\u2139\ufe0f":"670emojicon","\u23ea":"671emojicon","\u23e9":"672emojicon","\u23eb":"673emojicon","\u23ec":"674emojicon","\u2935\ufe0f":"675emojicon","\u2934\ufe0f":"676emojicon","\ud83c\udd97":"677emojicon","\ud83d\udd00":"678emojicon","\ud83d\udd01":"679emojicon","\ud83d\udd02":"680emojicon","\ud83c\udd95":"681emojicon","\ud83c\udd99":"682emojicon","\ud83c\udd92":"683emojicon","\ud83c\udd93":"684emojicon","\ud83c\udd96":"685emojicon","\ud83d\udcf6":"686emojicon","\ud83c\udfa6":"687emojicon","\ud83c\ude01":"688emojicon","\ud83c\ude2f\ufe0f":"689emojicon","\ud83c\ude33":"690emojicon","\ud83c\ude35":"691emojicon","\ud83c\ude34":"692emojicon","\ud83c\ude32":"693emojicon","\ud83c\ude50":"694emojicon","\ud83c\ude39":"695emojicon","\ud83c\ude3a":"696emojicon","\ud83c\ude36":"697emojicon","\ud83c\ude1a\ufe0f":"698emojicon","\ud83d\udebb":"699emojicon","\ud83d\udeb9":"700emojicon","\ud83d\udeba":"701emojicon","\ud83d\udebc":"702emojicon","\ud83d\udebe":"703emojicon","\ud83d\udeb0":"704emojicon","\ud83d\udeae":"705emojicon","\ud83c\udd7f\ufe0f":"706emojicon","\u267f\ufe0f":"707emojicon","\ud83d\udead":"708emojicon","\ud83c\ude37":"709emojicon","\ud83c\ude38":"710emojicon","\ud83c\ude02":"711emojicon","\u24c2\ufe0f":"712emojicon","\ud83d\udec2":"713emojicon","\ud83d\udec4":"714emojicon","\ud83d\udec5":"715emojicon","\ud83d\udec3":"716emojicon","\ud83c\ude51":"717emojicon","\u3299\ufe0f":"718emojicon","\u3297\ufe0f":"719emojicon","\ud83c\udd91":"720emojicon","\ud83c\udd98":"721emojicon","\ud83c\udd94":"722emojicon","\ud83d\udeab":"723emojicon","\ud83d\udd1e":"724emojicon","\ud83d\udcf5":"725emojicon","\ud83d\udeaf":"726emojicon","\ud83d\udeb1":"727emojicon","\ud83d\udeb3":"728emojicon","\ud83d\udeb7":"729emojicon","\ud83d\udeb8":"730emojicon","\u26d4\ufe0f":"731emojicon","\u2733\ufe0f":"732emojicon","\u2747\ufe0f":"733emojicon","\u274e":"734emojicon","\u2705":"735emojicon","\u2734\ufe0f":"736emojicon","\ud83d\udc9f":"737emojicon","\ud83c\udd9a":"738emojicon","\ud83d\udcf3":"739emojicon","\ud83d\udcf4":"740emojicon","\ud83c\udd70":"741emojicon","\ud83c\udd71":"742emojicon","\ud83c\udd8e":"743emojicon","\ud83c\udd7e":"744emojicon","\ud83d\udca0":"745emojicon","\u27bf":"746emojicon","\u267b\ufe0f":"747emojicon","\u2648\ufe0f":"748emojicon","\u2649\ufe0f":"749emojicon","\u264a\ufe0f":"750emojicon","\u264b\ufe0f":"751emojicon","\u264c\ufe0f":"752emojicon","\u264d\ufe0f":"753emojicon","\u264e\ufe0f":"754emojicon","\u264f\ufe0f":"755emojicon","\u2650\ufe0f":"756emojicon","\u2651\ufe0f":"757emojicon","\u2652\ufe0f":"758emojicon","\u2653\ufe0f":"759emojicon","\u26ce":"760emojicon","\ud83d\udd2f":"761emojicon","\ud83c\udfe7":"762emojicon","\ud83d\udcb9":"763emojicon","\ud83d\udcb2":"764emojicon","\ud83d\udcb1":"765emojicon","\u00a9":"766emojicon","\u00ae":"767emojicon","\u2122":"768emojicon","\u274c":"769emojicon","\u203c\ufe0f":"770emojicon","\u2049\ufe0f":"771emojicon","\u2757\ufe0f":"772emojicon","\u2753":"773emojicon","\u2755":"774emojicon","\u2754":"775emojicon","\u2b55\ufe0f":"776emojicon","\ud83d\udd1d":"777emojicon","\ud83d\udd1a":"778emojicon","\ud83d\udd19":"779emojicon","\ud83d\udd1b":"780emojicon","\ud83d\udd1c":"781emojicon","\ud83d\udd03":"782emojicon","\ud83d\udd5b":"783emojicon","\ud83d\udd67":"784emojicon","\ud83d\udd50":"785emojicon","\ud83d\udd5c":"786emojicon","\ud83d\udd51":"787emojicon","\ud83d\udd5d":"788emojicon","\ud83d\udd52":"789emojicon","\ud83d\udd5e":"790emojicon","\ud83d\udd53":"791emojicon","\ud83d\udd5f":"792emojicon","\ud83d\udd54":"793emojicon","\ud83d\udd60":"794emojicon","\ud83d\udd55":"795emojicon","\ud83d\udd56":"796emojicon","\ud83d\udd57":"797emojicon","\ud83d\udd58":"798emojicon","\ud83d\udd59":"799emojicon","\ud83d\udd5a":"800emojicon","\ud83d\udd61":"801emojicon","\ud83d\udd62":"802emojicon","\ud83d\udd63":"803emojicon","\ud83d\udd64":"804emojicon","\ud83d\udd65":"805emojicon","\ud83d\udd66":"806emojicon","\u2716\ufe0f":"807emojicon","\u2795":"808emojicon","\u2796":"809emojicon","\u2797":"810emojicon","\u2660\ufe0f":"811emojicon","\u2665\ufe0f":"812emojicon","\u2663\ufe0f":"813emojicon","\u2666\ufe0f":"814emojicon","\ud83d\udcae":"815emojicon","\ud83d\udcaf":"816emojicon","\u2714\ufe0f":"817emojicon","\u2611\ufe0f":"818emojicon","\ud83d\udd18":"819emojicon","\ud83d\udd17":"820emojicon","\u27b0":"821emojicon","\u3030":"822emojicon","\u303d\ufe0f":"823emojicon","\ud83d\udd31":"824emojicon","\u25fc\ufe0f":"825emojicon","\u25fb\ufe0f":"826emojicon","\u25fe\ufe0f":"827emojicon","\u25fd\ufe0f":"828emojicon","\u25aa\ufe0f":"829emojicon","\u25ab\ufe0f":"830emojicon","\ud83d\udd3a":"831emojicon","\ud83d\udd32":"832emojicon","\ud83d\udd33":"833emojicon","\u26ab\ufe0f":"834emojicon","\u26aa\ufe0f":"835emojicon","\ud83d\udd34":"836emojicon","\ud83d\udd35":"837emojicon","\ud83d\udd3b":"838emojicon","\u2b1c\ufe0f":"839emojicon","\u2b1b\ufe0f":"840emojicon","\ud83d\udd36":"841emojicon","\ud83d\udd37":"842emojicon","\ud83d\udd38":"843emojicon","\ud83d\udd39":"844emojicon","\u263a":"4emojicon","\u270c":"110emojicon","\u261d":"120emojicon","\u2764":"172emojicon","\u2b50":"293emojicon","\u2600":"294emojicon","\u26c5":"295emojicon","\u2601":"296emojicon","\u26a1":"297emojicon","\u2614":"298emojicon","\u2744":"299emojicon","\u26c4":"300emojicon","\u260e":"336emojicon","\u231b":"352emojicon","\u231a":"354emojicon","\u2709":"393emojicon","\u2702":"417emojicon","\u2712":"420emojicon","\u270f":"421emojicon","\ud83c\udc04":"454emojicon","\u26bd":"459emojicon","\u26be":"460emojicon","\u26f3":"465emojicon","\u2615":"476emojicon","\u26ea":"546emojicon","\u26fa":"553emojicon","\u26f2":"565emojicon","\u26f5":"568emojicon","\u2693":"571emojicon","\u2708":"573emojicon","\u26a0":"614emojicon","\u26fd":"617emojicon","\u2668":"620emojicon","\u2b06":"650emojicon","\u2b07":"651emojicon","\u2b05":"652emojicon","\u27a1":"653emojicon","\u2197":"657emojicon","\u2196":"658emojicon","\u2198":"659emojicon","\u2199":"660emojicon","\u2194":"661emojicon","\u2195":"662emojicon","\u25c0":"664emojicon","\u25b6":"665emojicon","\u21a9":"668emojicon","\u21aa":"669emojicon","\u2139":"670emojicon","\u2935":"675emojicon","\u2934":"676emojicon","\ud83c\ude2f":"689emojicon","\ud83c\ude1a":"698emojicon","\ud83c\udd7f":"706emojicon","\u267f":"707emojicon","\u24c2":"712emojicon","\u3299":"718emojicon","\u3297":"719emojicon","\u26d4":"731emojicon","\u2733":"732emojicon","\u2747":"733emojicon","\u2734":"736emojicon","\u267b":"747emojicon","\u2648":"748emojicon","\u2649":"749emojicon","\u264a":"750emojicon","\u264b":"751emojicon","\u264c":"752emojicon","\u264d":"753emojicon","\u264e":"754emojicon","\u264f":"755emojicon","\u2650":"756emojicon","\u2651":"757emojicon","\u2652":"758emojicon","\u2653":"759emojicon","\u203c":"770emojicon","\u2049":"771emojicon","\u2757":"772emojicon","\u2b55":"776emojicon","\u2716":"807emojicon","\u2660":"811emojicon","\u2665":"812emojicon","\u2663":"813emojicon","\u2666":"814emojicon","\u2714":"817emojicon","\u2611":"818emojicon","\u303d":"823emojicon","\u25fc":"825emojicon","\u25fb":"826emojicon","\u25fe":"827emojicon","\u25fd":"828emojicon","\u25aa":"829emojicon","\u25ab":"830emojicon","\u26ab":"834emojicon","\u26aa":"835emojicon","\u2b1c":"839emojicon","\u2b1b":"840emojicon"}';

        $ddd = json_decode($newJson, true);
        $searchReplaceArray = $ddd;
        $result = str_replace(
                array_keys($searchReplaceArray), array_values($searchReplaceArray), $text
        );
        return $result;
    }

    public function updatenotificationtimeAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $userTable = new Application_Model_DbTable_Users();
        $userSettingTable = new Application_Model_DbTable_UserSetting();
        $userNotificationTable = new Application_Model_DbTable_UserNotifications();
        $friendTable = new Application_Model_DbTable_Friends();
        $userId = $this->loggedUserRow->userId;

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $this->db->update("users", array("lastSeenNotification" => date("Y-m-d H:i:s")), "userId = '" . $userId . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function getUpdateNotificationTimeAction() {
        $this->_helper->layout->enableLayout();
        $html = $this->view->Action("updatenotificationtime", "user-notifications", array());
        exit;
    }

    public function updateNotificationReadAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $userTable = new Application_Model_DbTable_Users();
        $userId = $this->loggedUserRow->userId;

        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $this->db->update("user_notifications", array("is_read" => '1'), "user_id = '" . $userId . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

    public function readNotificationByIdAction() {
        $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter 
        $userTable = new Application_Model_DbTable_Users();
        $userId = $this->loggedUserRow->userId;

        $decoded = $this->common->Decoded();

        $notification_id = $decoded['notification_id'];



        if (($userRow = $userTable->getRowById($userId)) && ($userRow->isActive())) {
            $this->db->update("user_notifications", array("is_read" => '1'), "id = '" . $notification_id . "'");
            exit;
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }
    }

}

?>
