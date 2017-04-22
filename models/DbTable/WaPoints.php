<?php

class Application_model_DbTable_WaPointsRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaPoints extends Zend_Db_Table_Abstract {

    protected $_name = "wa_points";
    protected $_primary = "point_id";
    protected $_rowClass = "Application_model_DbTable_WaPointsRow";

    const FIFTY_POINT = 2;
    const TRUSTEE_POINT = 10;
    const FRIEND_POINT = 10;
    const COUNT_FRIEND = 50;
    const BONUS_POINT_FRIEND_COUNT=5;
    const TRUSTEE_POINT_TYPE = 2;
    const FRIEND_POINT_TYPE = 1;
    const DELETE_TRUSTEE_POINT_TYPE=5;
    const DELETE_FRIEND_POINT_TYPE=6;
    const COUNT_FRIENDS_TYPE = 3;
    const SPEND_TYPE = 4;

    protected $point;
    protected $point_type;
    protected $user_id;
    protected $is_delete;

    public function getRowById($point_id) {
        $waPointsTable = new Application_Model_DbTable_WaPoints();
        $select = $waPointsTable->select()
                ->where('point_id =?', $point_id);
        return $waPointsTable->fetchRow($select);
    }

    //Check whether specific trustees friend exists or not in deleted status.
    public function getAlreadyFriendById($trusteeId, $userid) {
        //When delete trustee, still friend. so Points update in friend.
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $selectTrustee = $db->select()->from('trustees')->where("userId =" . $userid . " AND trusteeId =" . $trusteeId);
        $waPointsTrustee = $db->fetchRow($selectTrustee);

        //set point id to friend
        $db->update('friends', array("point_id" => $waPointsTrustee->point_id), "userId =" . $userid . " AND friendId =" . $trusteeId);
        //Update Trustee points to 0
        $db->update('trustees', array("point_id" => 0), "userId =" . $userid . " AND trusteeId =" . $trusteeId);
        return $waPointsTrustee->point_id;
    }

    //Get total number of friends and trustee for specific user in table : wa_points , so we check (MOD 50 Point check).
    public function getfriendTrusteeCountById($userid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $db->select()->from('wa_points')->where('user_id=?', $userid)->where('is_delete=?', 0)->order('point_id Desc');
        $waPoints = $db->fetchAll($select);

        if (count($waPoints) > 0) {
            $count = count($waPoints);
        } else {
            $count = 0;
        }
        return $count;
    }

    //Get count_trustee from table : wa_points , so we check last trustee number count.
    public function getLastTrusteeCountById($userid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $db->select()->from('wa_points')->where('user_id=?', $userid)->where('count_trustee>0')->order('point_id Desc')->limit(1);
        $waPoints = $db->fetchRow($select);

        if ($waPoints->count_trustee > 0) {
            $count_trustee = $waPoints->count_trustee;
        } else {
            $count_trustee = 0;
        }
        return $count_trustee;
    }

    //Get count_friend from table : wa_points , so we check last friend number count.
    public function getLastFriendCountById($userid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $db->select()->from('wa_points')->where('user_id=?', $userid)->where('count_friend>0')->order('point_id Desc')->limit(1);
        $waPoints = $db->fetchRow($select);
        if ($waPoints->count_friend > 0) {
            $count_friend = $waPoints->count_friend;
        } else {
            $count_friend = 0;
        }
        return $count_friend;
    }

    //Check whether friend already exist with deleted stauts.
    public function getAlreadyFriend($trusteeId, $userid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $db->select()->from('friends')->where("userId =" . $userid . " AND friendId =" . $trusteeId);
        $waPoints = $db->fetchRow($select);

        if ($waPoints->point_id > 0) {
            $friend_point_id = $waPoints->point_id;
            $db->update('friends', array("point_id" => 0), "userId =" . $userid . " AND friendId =" . $trusteeId);
        } else {
            $friend_point_id = 0;
        }
        return $friend_point_id;
    }

    //Whenever we create friend or trustee, this function called.
    public function createPointsRow($userId, $friendId = 0, $trusteeId = 0, $friend_point_id = 0, $friend_status = 1, $trustee_point_id = 0, $trustee_status = 1, $isTrustee = 0) { //on friend or trustee request accept , one entry in wa_points table
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        if ($isTrustee > 0) {   // Trustee
                $this->point = Application_Model_DbTable_WaPoints::TRUSTEE_POINT;
                $this->point_type = Application_Model_DbTable_WaPoints::TRUSTEE_POINT_TYPE;
                $this->user_id = $userId;
                $this->is_delete=0;
                $lastPointId = $this->insertPoint();
                $db->update('trustees', array("point_id" =>$lastPointId ), array("userId =?"=>$userId ,"trusteeId =?"=>$trusteeId));
                $this->bonusPoints(Application_Model_DbTable_WaPoints::COUNT_FRIENDS_TYPE);
        } else {   //Friends           
                $this->point = Application_Model_DbTable_WaPoints::TRUSTEE_POINT;
                $this->point_type = Application_Model_DbTable_WaPoints::FRIEND_POINT_TYPE;
                $this->user_id = $userId;
                $this->is_delete=0;
                $lastPointId = $this->insertPoint();
                $db->update('friends', array("point_id" => $lastPointId), array("userId =?"=>$userId ,"friendId =?"=>$friendId));
                $this->bonusPoints(Application_Model_DbTable_WaPoints::COUNT_FRIENDS_TYPE);
            }
    }

    /*
     * description : used for insert point in the wa_point table 
     */

    public function insertPoint() {
        $data = array(
            'user_id' => $this->user_id,
            'points' => $this->point,
            'point_type' => $this->point_type,
            'creation_date' => date("Y-m-d H:i:s"),
            'modification_date' => date("Y-m-d H:i:s"),
            'is_delete' => $this->is_delete,
        );
        $lastPointId = $this->insert($data);
        return $lastPointId;
    }

    //Delete friend or trustee, corrensponding status change in tables : friends, trustees and wa_points
    public function deleteWaPointRow($userId, $friendId = 0, $trusteeId = 0, $isTrustee = 0) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        if ($isTrustee > 0) {   // Trustee
                $this->point = Application_Model_DbTable_WaPoints::TRUSTEE_POINT;
                $this->point_type = Application_Model_DbTable_WaPoints::DELETE_TRUSTEE_POINT_TYPE;
                $this->user_id = $userId;
                $this->is_delete=1;
                $lastPointId = $this->insertPoint();
        } else {   //Friends           
                $this->point = Application_Model_DbTable_WaPoints::TRUSTEE_POINT;
                $this->point_type = Application_Model_DbTable_WaPoints::DELETE_FRIEND_POINT_TYPE;
                $this->user_id = $userId;
                $this->is_delete=1;
                $lastPointId = $this->insertPoint();
            }
    }

    //Insert or update points for specific userId in table : bonus_points
    public function bonusPoints($point_type) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $selectBonusRow = $db->select()->from('bonus_points',array("bonus_id"))->where("userId =" . $this->user_id." AND point_type='{$point_type}'");
        $waBonusPoints = $db->fetchRow($selectBonusRow);
        if (!$waBonusPoints) {
            $select = $db->select()->from("users",array("totalFriends"))->where("userId=".$this->user_id);
            $friendsCount = $db->fetchRow($select); 
            if($friendsCount->totalFriends>=Application_Model_DbTable_WaPoints::BONUS_POINT_FRIEND_COUNT){
                $data = array(
                        'userId' => $this->user_id,
                        'bonus'=>  Application_Model_DbTable_WaPoints::COUNT_FRIEND,
                        'point_type' => $point_type
                    );
                   $db->insert('bonus_points', $data);
                   $this->point = Application_Model_DbTable_WaPoints::COUNT_FRIEND;
                   $this->point_type = $point_type;
                   $this->insertPoint();
                }
            }
    }

    //Get points by userId
    public function getPointsById($userId) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        //Get all records from wa_points where is_delete=0, if is_delete=1, that points cannot included until creation_date < 1yr. If users creation date is more then 1 yr that points will include.
        //Calculate sum of points of specific user in table : wa_points
        $selectWaPoints = $db->select()->
                from('wa_points', array("totalWaPoints" => "sum(points)"))
                ->where('user_id=?', $userId)
                ->where('(is_delete=?', 0)
                ->orWhere('DATE_ADD(creation_date,INTERVAL 1 YEAR)<?)', date('Y-m-d H:i:s'))
        ;
        $waPoints = $db->fetchRow($selectWaPoints);

        //Select Bonus points corresponding to user in table : bonus_points
        $selectBonusPoints = $db->select()->from('bonus_points', array('bonus'))->where('userId=?', $userId);
        $bonusPoints = $db->fetchRow($selectBonusPoints);

        $totalWaPoints = ($waPoints->totalWaPoints > 0) ? $waPoints->totalWaPoints : 0;
        $totalBonusPoints = ($bonusPoints->bonus > 0) ? $bonusPoints->bonus : 0;
        return $total = ($totalWaPoints + $totalBonusPoints);
    }

    // get total friends by userId
    public function CountFriendsByUserId($userId) {
//        $db = Zend_db_Table::getDefaultAdapter();
//        $db->setFetchMode(Zend_Db::FETCH_OBJ);
//
//        $selectWaFriends = $db->select()->
//                from('wa_points', array('count_friend' => "sum(`count_friend`)"))
//                ->where('user_id=?', $userId)
//                ->where('(is_delete=?', 0)
//                ->orWhere('DATE_ADD(creation_date,INTERVAL 1 YEAR)<?)', date('Y-m-d H:i:s'))
//        ;
//        $waFriends = $db->fetchRow($selectWaFriends);
//        return $waFriends->count_friend;
    }
    
     public function transferPoint($arrParam) {
        $arrData = array(
            "user_id"=>$arrParam['userId'],
            "points"=>$arrParam['points'],
            "point_type"=>4,
            "creation_date"=>date("Y-m-d H:i:s")
        );
        $userTable = new Application_Model_DbTable_Users();
        $credits = $arrParam['points'];
        $query = "update users set totalCredits=totalCredits+$credits where userId='{$arrParam['userId']}'";
        $userTable->getDefaultAdapter()->query($query);
        $this->insert($arrData);
    }

}
