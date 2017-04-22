<?php
/*
 * author: Akhilesh Singh
 */
class WaCreditController extends My_Controller_Abstract {
    
    public function preDispatch() {
        parent::preDispatch();
        $this->_helper->layout->disablelayout();
    }
    /*
     * description: use for create credit type
     * mandatory param: amount,no_of_credits
     */
    public function createCreditTypeAction()
    {
        $waCreditsTable    = new Application_Model_DbTable_WaCreditsType();       
        $decoded           = $this->common->decoded();
        $userSecurity      = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey) {
            try {
                $this->common->checkEmptyParameter1(array($decoded['amount'],$decoded['num_of_credits']));
                $waCreditsTable->setData($decoded);
                $waCreditsTable->SaveCreditsType();
                $this->common->displayMessage("Wa credit created successfully", "0", array(), "0");
            } catch (Exception $ex) {
                $this->common->displayMessage($ex->getMessage(), "1", array(), "10");
            }            
        }
        else {
             $this->common->displayMessage("You could not access for this web-service", "1", array(),"3");
        }
    }
    /*
     * description : used for update credit type details by credit id
     * mandatory param: amount,id,no_of_credits
     */
    public function updateCreditTypeAction()
    {
        $waCreditsTable    = new Application_Model_DbTable_WaCreditsType();       
        $decoded           = $this->common->decoded();
        $userSecurity      = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey) {
            try {
                $this->common->checkEmptyParameter1(array($decoded['id'],$decoded['amount'],$decoded['num_of_credits']));
                $waCreditsTable->setData($decoded);
                $waCreditsTable->UpdateCreditsType();
                $this->common->displayMessage("Credit updated successfully", "0", array(), "0");
            } catch (Exception $ex) {
                $this->common->displayMessage($ex->getMessage(), "1", array(), "10");
            }            
        }
        else {
             $this->common->displayMessage("You could not access for this web-service", "1", array(),"3");
        }
    }
    /*
     * description : used for getting credits card details by id
     * mandatory param: id
     */
    public function creditTypeDetailsAction()
    {
        $waCreditsTable   = new Application_Model_DbTable_WaCreditsType();
        $decoded          = $this->common->decoded();
        $userSecurity     = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($decoded['id']));
            $creditRow        = $waCreditsTable->getRowById($decoded['id']);
            if($creditRow > 0)
            {
                    $this->common->specificMessage($creditRow, "0", array(), "0");
            }
            else
            {
                $this->common->displayMessage("No result found", "1", array(), "12");
            }
        } 
        else
        {
            $this->common->displayMessage("You could not access for this web-service", "1", array(), "3");
        }
    }
    /*
     * description : used for getting list of credits plan
     * mandatory param: none
     */
    public function getPlanListAction()
    {
        $waCreditsTable   = new Application_Model_DbTable_WaCreditsType();
        $decoded          = $this->common->decoded();
        $userSecurity     = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey)
        {
            $creditRow        = $waCreditsTable->getList();
            if($creditRow > 0)
            {
                    $this->common->specificMessage($creditRow, "0", array(), "0");
            }
            else
            {
                $this->common->displayMessage("No result found", "1", array(), "12");
            }
        } 
        else
        {
            $this->common->displayMessage("You could not access for this web-service", "1", array(), "3");
        }
    }
    /*
     * description: used for delete plan by id
     * mandatory:id
     */
    public function deleteCreditTypeAction()
    {
        $waCreditsTable     = new Application_Model_DbTable_WaCreditsType();
        $decoded            = $this->common->decoded();
        $userSecurity       = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($decoded['id']));
            $waCreditsTable->DeleteCreditsType($decoded['id']);
            $this->common->displayMessage("WA Credit deleted successfully", "0", array(),"0");
        }
        else
        {
            $this->common->displaytMessage("You could not access this web-service", "1", array(), "3");
        }
    }
    
   public function checkCreditsAction(){
        $userTable     = new Application_Model_DbTable_Users();
        $decoded            = $this->common->decoded();
        $userSecurity       = $decoded['userSecurity'];
        if($userSecurity == $this->servicekey)
        {
            $this->common->checkEmptyParameter1(array($decoded['userId'],$decoded['credits']));
            if($userTable->checkCredits($decoded['userId'],$decoded['credits'])){
                $this->common->displayMessage("You have sufficient credits", "0", array(),"0");
            }else{
                $this->common->displayMessage("Don't have sufficient credits", "2", array(),"2");
            }
        }
        else
        {
            $this->common->displaytMessage("You could not access this web-service", "1", array(), "3");
        }
   }
    
    
}

