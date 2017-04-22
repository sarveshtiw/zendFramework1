<?php
/* Author name : Rajesh Pal
 * Date : 26 Nov 2014
 * List of users whom i am trustee.
 */

class WhomtrusteesController extends My_Controller_Admin {

    public function init() {

    }

    public function indexAction() {
        if ($this->loggedUserRow->userId>0) {
        }else{
            $this->_redirect($this->makeUrl('/'));
        }
    }

    public function whomTrusteeAction() {
        $trusteeTable = new Application_Model_DbTable_Trustee();
        $userTable = new Application_Model_DbTable_Users();
        $editFriendTrusteeTable = new Application_Model_DbTable_EditFriendAndTrusteeDetails();
        $decoded = $this->common->Decoded();

        $userId = $this->loggedUserRow->userId;


        if ($userRow = $userTable->getRowById($userId)) {

            $whomIamTrusteeUserRowset = $trusteeTable->whomIamTrustee($userId);

            $reponseData = array();

            foreach ($whomIamTrusteeUserRowset as $trusteeUserRow) {

                if ($editFriendTrusteeRow = $editFriendTrusteeTable->getRowByUserIdAndOtherUserId($userId, $trusteeUserRow->userId)) {
                    $userName = $editFriendTrusteeRow->name;
                } else {
                    $userName = $trusteeUserRow->userNickName;
                }

                $reponseData[] = array(
                    'trustee_row_id' => $trusteeUserRow->id,
                    'userId' => $trusteeUserRow->userId,
                    'userName' => $userName,
                    'userFullName' => $trusteeUserRow->userFullName,
                    'userImage' => ($trusteeUserRow->userImage) ? $trusteeUserRow->userImage : "",
                    'quickBloxId' => ($trusteeRow->quickBloxId) ? $trusteeRow->quickBloxId : ""
                );
            }

            $this->common->displayMessage("Users lisiting whom am trustee", "0", $reponseData, "0");
        } else {
            $this->common->displayMessage("User account is not exist", "1", array(), "2");
        }

        exit;
    }

}

?>
