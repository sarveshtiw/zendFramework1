<?php

class Application_model_DbTable_WaTestamentsRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_WaTestaments extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_testaments";
    protected $_primary  = "testament_id";
    protected $_rowClass = "Application_model_DbTable_WaTestamentsRow";
   
    const TESTAMENT_TYPE_EVENT = 'wa_testament';
    const TESTAMENT_VITAL_CKECK_EVENT = 'wa_testament_vital_check';
    const TESTAMENT_OWNER_EVENT = 1;
    const TESTAMENT_TRUSTEE_EVENT = 2;
    const TESTAMENT_RECEIVER_EVENT = 3;
    
    public function saveTestament($data)
    { 
       $insert_id      = $this->insert($data);
       return $insert_id;                
    }   
    
    public function getRowById($testament_id)
    {
        $select     = $this->select()
                     ->where('testament_id =?', $testament_id);
        return $this->fetchRow($select);                         
    }
    
    public function getLastRowByUserId($user_id)
    {
        $select     = $this->select()
                    ->where('user_id =?', $user_id)
                    ->order('testament_id Desc')
                    ->limit(1);
        return $this->fetchRow($select);
    }
    
    public function getFinalTestamentRowByUserId($user_id)
    {
        $select     = $this->select()
                    ->where('user_id =?', $user_id)
                    ->where('created_pdf_link != ""')
                    ->order('testament_id Desc')
                    ->limit(1);
        return $this->fetchRow($select);
    }
    
    public function getRecordsForPushNoitifications()
    {        
        $currentTime   = time();
        $currentDate   = date("Y-m-d H:i:s");
        $select        = $this->select()->setIntegrityCheck(false)
                            ->from("wa_testaments", array("*"))
                            ->joinLeft('wa_event_send_details', 'wa_event_send_details.testament_id = wa_testaments.testament_id', 
                                    array("id","usertype","event_send_date"))
                           //->where("((owner_alert_time <?", $currentDate)
                           //->where("owner_alert_count <?)", 3)
                           //->orWhere("(trustee_alert_time <?", $currentDate)
                           //->Where("trustee_alert_count <?))", 4)
                            ->where("wa_event_send_details.event_send_date <?", $currentDate)
                            ->where("wa_event_send_details.is_status =?",'0')
                            ->where("wa_event_send_details.event_status =?",'1')
                            ->where("(wa_testaments.is_send =?",'0')
                            ->orWhere("wa_testaments.is_send =?",'1')
                            ->orWhere("wa_testaments.is_send =?)",'2')
                            ->where("wa_testaments.is_status =?",'1')
                            ->where("wa_event_send_details.vital_check_type =?", Application_Model_DbTable_WaEventSendDetails::VITAL_CHECK_TYPE_TESTAMENT)
			    ->order("wa_event_send_details.id ASC");
        $waEventRowSet  = $this->fetchAll($select);   
        return $waEventRowSet;
    }
    
    public function sendAllTestamentUsers($userId,$value)
    {
        $select   = $this->select()
                    ->where('user_id =?',$userId)
                    ->where('is_status =?', 1);
        $Rowset   = $this->fetchRow($select);
        
        if(isset($Rowset))
        {
            $Rowset->is_send = $value;
            $Rowset->save();
        }
    } 
   
    public function sendVitalCheckUsers($testament_id,$value)
    {
        $select   = $this->select()
                    ->where('testament_id =?',$testament_id)
                    ->where('is_status =?', '1');
        $Rowset   = $this->fetchRow($select);
        
        if(isset($Rowset))
        {
            $Rowset->is_send = $value;
            $Rowset->save();
        }
    }
 
    public function getTestamentRowByUserId($testament_id,$user_id)
    {
        $select    = $this->select()
                    ->where('user_id =?',$user_id)
                    ->where('testament_id =?',$testament_id);
        return $this->fetchRow($select);
    } 
    
    public function getAllTestamentsByUserId($user_id)
    {
        $select    = $this->select()
                    ->where('user_id =?',$user_id)
                    ->where('is_status =?','1')
                    ->order('testament_id DESC');
        return $this->fetchAll($select);
    }
    
}

class Application_Model_DbTable_WaTestamentFiles extends Zend_Db_Table_Abstract {
    protected $_name     = "wa_testaments_files";
    protected $_primary  = "id";
     
    public function saveTestamentFiles($data)
    {
        $insert_id              = $this->insert($data);
        return $insert_id;  
    }
    
    public function getRowById($row_id)
    {
        $select = $this->select()
                ->where('id =?',$row_id);
        return $this->fetchRow($select);
    }

    public function getFilesByTestamentId($testament_id)
    {        
        $select = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_files',array('*'))
                    ->joinLeft('wa_testaments','wa_testaments.testament_id = wa_testaments_files.testament_id',array(''))
                    ->where('wa_testaments_files.testament_id =?', $testament_id)
                    ->where('wa_testaments_files.is_status =?', '1');
        return $this->fetchAll($select)->toArray();
    } 
    
    public function deleteTestamentFiles($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentRepresentatives extends Zend_Db_Table_Abstract {
    
    protected $_name = "wa_testaments_represtatives";
    protected $_id   = "res_id";
    
    public function saveTestamentRepresentatives($data)
    { 
       $insert_id      = $this->insert($data);
       return $insert_id;                
    }  
    
    public function getRepresentatives($testament_id)
    {
        $select     = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_represtatives', array('*'))
                    ->joinLeft('wa_testaments', 'wa_testaments.testament_id = wa_testaments_represtatives.testament_id',array(''))
                    ->where('wa_testaments_represtatives.testament_id =?', $testament_id); 
        return $this->fetchAll($select)->toArray();
    }
 
    public function deleteRepresentatives($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentBankAccount extends Zend_Db_Table_Abstract {
    protected $_name  = "wa_testaments_bank_accounts";
    protected $_id    = "account_id";
    
    public function saveTestamentBankAccount($data)
    {
        $insert_id    = $this->insert($data);
        return $insert_id;
    }
       
    public function getBankAccounts($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_bank_accounts', array(`bank_name`, `bank_address`, `bank_account_number`))
                    ->joinLeft('wa_testaments', 'wa_testaments.testament_id = wa_testaments_bank_accounts.testament_id',array(''))
                    ->where('wa_testaments_bank_accounts.testament_id =?', $testament_id); 
        return $this->fetchAll($select);
    }
    
    public function deleteBankAccounts($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentSocialAccount extends Zend_Db_Table_Abstract {
    protected $_name  = "wa_testaments_social_accounts";
    protected $_id    = "social_id";
    
    public function saveTestamentSocialAccount($data)
    {
        $insert_id  = $this->insert($data);
        return $insert_id;
    }
    
    public function getSocialAccounts($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_social_accounts',array('*'))
                    ->joinLeft('wa_testaments', 'wa_testaments.testament_id = wa_testaments_social_accounts.testament_id',array(''))
                    ->where('wa_testaments.testament_id =?',$testament_id);
        return $this->fetchAll($select);
    }
    
    public function deleteSocialAccounts($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentEstate extends Zend_Db_Table_Abstract {
    protected $_name   = "wa_testaments_estate";
    protected $_id     = "estate_id";
    
    public function saveTestamentEstate($data)
    {
        $insert_id   = $this->insert($data);
        return $insert_id;
    }
    
    public function getEstateDividations($testament_id)
    {
        $select  = $this->select()->setIntegrityCheck(false)
                    ->from('wa_testaments_estate', array('*'))
                    ->joinLeft('wa_testaments', 'wa_testaments.testament_id = wa_testaments_estate.testament_id',array(''))
                    ->where('wa_testaments.testament_id =?', $testament_id);
        return $this->fetchAll($select);
    }
    
    public function deleteEstateDividations($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentOthers extends Zend_Db_Table_Abstract {
    protected $_name = "wa_testaments_others";
    protected $_id   = "id";
    
    public function saveTestamentOthers($data)
    {
        $insert_id  = $this->insert($data);
        return $insert_id;
    }
    
    public function getOthersSet($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(false)
                    ->from('wa_testaments_others',array('*'))
                    ->joinLeft('wa_testaments', 'wa_testaments.testament_id = wa_testaments_others.testament_id', array())
                    ->where('wa_testaments.testament_id =?', $testament_id);
        return $this->fetchAll($select);
    }
    
    public function deleteOthersSet($testament_id)
    {
        $this->delete(array("testament_id = ?"=>$testament_id));
    }
}

class Application_Model_DbTable_WaTestamentWitnesses extends Zend_Db_Table_Abstract {
    protected $_name = "wa_testaments_witnesses";
    protected $_id   = "witness_id";
    
    public function saveTestamentWitnesses($data)
    {
        $insert_id  = $this->insert($data);
        return $insert_id;
    }
    
    public function updateTestamentWitness($testament_id,$data)
    { 
       $this->db = Zend_Db_Table::getDefaultAdapter(); // initialize database adapter
       $result = $this->db->update("wa_testaments_witnesses", $data, "testament_id = '" .$testament_id . "'");
       return $result;
    }

    public function CheckWitnessExists($testament_id,$userId,$witness_username)
    {
        $select = $this->select()
            ->from('wa_testaments_witnesses',array('*'))
            ->where('testament_id =?',$testament_id)
            ->where('user_id =?',$userId)
            ->where('username =?',$witness_username)
            ->where('is_status ="0" OR is_status ="1"');
       
        return $this->fetchRow($select);
    }

    public function getWitnesses($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_witnesses',array('*'))
                    ->joinInner('wa_testaments','wa_testaments.testament_id = wa_testaments_witnesses.testament_id',array('*'))
                    ->where('wa_testaments_witnesses.testament_id =?', $testament_id)
                    ->where('wa_testaments_witnesses.is_status = "0" OR wa_testaments_witnesses.is_status = "1"');
        return $this->fetchAll($select);
    }
    
    public function getTestamentWitnesses($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_witnesses',array('username','is_status','id_type','id_number'))
                    ->joinInner('wa_testaments','wa_testaments.testament_id = wa_testaments_witnesses.testament_id',array('testament_id'))
                    ->where('wa_testaments_witnesses.testament_id =?', $testament_id)
                    ->where('wa_testaments_witnesses.is_status = "0" OR wa_testaments_witnesses.is_status = "1"');
        return $this->fetchAll($select);
    }

    public function getAllConfirmWitnessesByTestamentId($testament_id)
    {
        $select   = $this->select()->setIntegrityCheck(FALSE)
                    ->from('wa_testaments_witnesses',array('*'))
                    ->joinInner('wa_testaments','wa_testaments.testament_id = wa_testaments_witnesses.testament_id',array('*'))
                    ->where('wa_testaments_witnesses.testament_id =?', $testament_id)
                    ->where('wa_testaments_witnesses.is_status =?', '1');
        return $this->fetchAll($select);
    }
    
    public function getRowById($witness_id)
    {
       $select    = $this->select()
                    ->from('wa_testaments_witnesses',array('*'))
                    ->where('witness_id =?', $witness_id);
       return $this->fetchRow($select);
    }    
    
    public function getWitnessesByIds($witness_ids)
    {
       $select    = $this->select()
                    ->from('wa_testaments_witnesses',array('*'))
                    ->where('witness_id IN (?)', $witness_ids);       
       return $this->fetchAll($select);
    } 

    public function sendAllWitnessesByUserId($userId)
    {      
        $select     = $this->select()
                      ->from('wa_testaments_witnesses',array('witness_id', 'testament_id', 'name', 'username', 'id_type', 'id_number', 'ip_address', 'recorded_file_link', 'signature_pdf_link', 'is_status'))
                      ->where('user_id =?', $userId);
        
        return $this->fetchAll($select)->toArray();
    }
    
    public function getAllWitnessesByUserEmail($userEmail)
    {      
        $select     = $this->select()->distinct('user_id')->setIntegrityCheck(FALSE)
                        ->from('wa_testaments_witnesses',array('witness_id','user_id','id_type','id_number','is_status','recorded_file_link','signature_pdf_link','username'))
                        ->joinInner('wa_testaments','wa_testaments.testament_id = wa_testaments_witnesses.testament_id',array('full_name','gender','testament_id'))
                        ->where('wa_testaments_witnesses.username =?', $userEmail)
			->where('wa_testaments_witnesses.is_status ="0" OR wa_testaments_witnesses.is_status ="1"');

        return $this->fetchAll($select)->toArray();
    }
}

class Application_Model_DbTable_WaTestamentQuestions extends Zend_Db_Table_Abstract {
    
    protected $_name = "wa_testaments_questions";
    protected $_id   = "id";
    
    public function getQuestions($lang)
    { 
        if(!empty($lang)) {
            $select = $this->select()
                      ->from('wa_testaments_questions',array('*'))
                      ->where('lang =?',$lang)
                      ->order('id ASC');
        }  else { 
             $select = $this->select()
                      ->from('wa_testaments_questions',array('*'))
                      ->where('lang =?','en')
                      ->order('id ASC');
        }
        return $this->fetchAll($select)->toArray();
    }
}
class Application_Model_DbTable_WaTestamentAnswers extends Zend_Db_Table_Abstract {
    
    protected $_name = "wa_testaments_answers";
    protected $_id   = "id";
    
    public function saveAnswers($data)
    {
        $insert_id  = $this->insert($data);
        return $insert_id;
    }
    
    public function getAnswersByWitnessId($witness_id)
    {
        $select = $this->select()
                    ->from('wa_testaments_answers', array('*'))
                    ->where('witness_id =?',$witness_id)
                    ->order('id ASC');
        return $this->fetchAll($select)->toArray();
    }
    
    public function deleteAnswers($witness_id)
    {
        $result = $this->delete(array('witness_id =?'=>$witness_id));
        return $result;
    }
}
