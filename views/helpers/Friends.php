<?php

class Zend_View_Helper_Friends extends Zend_View_Helper_Abstract {

    public function friends($userId, $friendId) {
        $friendTable = new Application_Model_DbTable_Friends();
        $friend_class = "tcl_button_ck";
        if ($friendRow = $friendTable->getRowByUserIdAndFriendId($userId, $friendId)) {
            if ($friendRow->status == "0") {
                $friend_class = "tcl_button_gray";
            }

            if ($friendRow->status == "1") {
                $friend_class = "tcl_button_green";
            }
        }
        
        return $friend_class;
    }

}

?>
