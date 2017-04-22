<?php

class Zend_View_Helper_Trustee extends Zend_View_Helper_Abstract {

    public function trustee($userId,$friendId) {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $trustee_class = "tcl_button_ck";
        if ($trusteeRow = $trusteeTable->getRowByUserIdAndTrusteeId($userId, $friendId)) {
            if ($trusteeRow->status == "0") {
                $trustee_class = "tcl_button_gray";
            }

            if ($trusteeRow->status == "1") {
                $trustee_class = "tcl_button_green";
            }
        }
        return $trustee_class;
    }

}

?>
