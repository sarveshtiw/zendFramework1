<?php
class Application_Model_DbTable_WaSosRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSos extends Zend_Db_Table_Abstract{
    protected $_name = "wa_sos";
    protected $_primary = "sos_id";
    protected $_rowClass = "Application_Model_DbTable_WaSosRow";
    
    public function getRowById($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId);        
        return $this->fetchRow($select);
    }
    
    public function getRowByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId);
        return $this->fetchRow($select);
    } 
    
    public function getActiveProfileByUserId($userId){
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('is_profile_active =?', '1')
                    ->order('sos_id DESC')
                    ->limit(1,0);
        return $this->fetchRow($select);
    } 

    public function countSOSByUserId($userId){        
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('is_status =?', '1');
        return $this->fetchAll($select)->toArray();
    }
    
    public function getAllProfilesByUserId($userId){        
        $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->where('is_status =?', '1')
                    ->order('sos_id DESC');
        return $this->fetchAll($select);
    }
    
    public function getOthersProfilesByUserId($userId,$sosId){ 
        if(!empty($sosId)){
            $select = $this->select()
                        ->where('user_id =?', $userId)
                        ->where('sos_id !=?', $sosId);
        }else{
            $select = $this->select()
                    ->where('user_id =?', $userId)
                    ->order('sos_id DESC')
                    ->limit(2,1);
        }
        return $this->fetchAll($select);
    }
    
    public function getNearByUserId($userId,$userLatitude,$userLongitude){
        //$distance="(((acos(sin((".$userLatitude."*pi()/180)) * sin((users.userLatitude*pi()/180))+cos((".$userLatitude."*pi()/180)) * cos((users.userLatitude*pi()/180)) * cos(((".$userLongitude."- users.userLongitude)* pi()/180))))*180/pi())*60*1.1515)";   // get distance in miles
        $distance = "( acos( cos( radians(".$userLatitude.") ) * cos( radians(".$userLatitude.") ) * cos( radians(".$userLongitude.") - radians(".$userLongitude.") ) + sin( radians(".$userLatitude.") ) * sin( radians(".$userLatitude.") ) ) )";
        $select = $this->select()->setIntegrityCheck(false)
                    ->from('users',array('*','distance' => $distance))
                    ->joinInner("accountSetting", "accountSetting.userId = users.userId",array('near_by','availableForDates'))
                    ->joinInner("wa_sos_setting", "wa_sos_setting.user_id = users.userId",array('*'))
                    ->where('users.userStatus = ?','1')
                    ->where("$distance <=?",3)
                    ->where('users.userLatitude is not null')
                    ->where('users.userLongitude is not null')
                    ->where('users.userLatitude != "0.000000"')
                    ->where('users.userId !=?',$userId)
                    ->where('wa_sos_setting.is_volunteer =?','1');
     
        return $this->fetchAll($select);
    }
}

class Application_Model_DbTable_WaSosFilesRow extends Zend_Db_Table_Row_Abstract{
    
}

class Application_Model_DbTable_WaSosFiles extends Zend_Db_Table_Abstract{
    protected $_name = "wa_sos_files";
    protected $_primary = "sos_file_id";
    protected $_rowClass = "Application_Model_DbTable_WaSosFilesRow";
    
    public function getRowById($fileId){
        $select = $this->select()
                    ->where('sos_file_id =?', $fileId);
        return $this->fetchRow($select);
    }
    
    public function getRowBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId)
                    ->where('is_facebook_send =?', '0')
                    ->where('is_twitter_send =?', '0');
        return $this->fetchAll($select);
    }
    
    public function getFacebookRowBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId)
                    ->where('is_facebook_send =?', '0');
        return $this->fetchAll($select);
    }
    
    public function getTwitterRowBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId)
                    ->where('is_twitter_send =?', '0');
        return $this->fetchAll($select);
    }
    
    public function getSocialRowBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId);
        return $this->fetchAll($select);
    }
    
    public function getSocialSharedRowsBySosId($sosId){
        $select = $this->select()
                    ->where('sos_id =?', $sosId)
                    ->where('is_send =?', '0')
                    ->where('(is_facebook_send =?', '0')
                    ->orwhere('is_facebook_send =?)', '1')
                    ->orwhere('(is_twitter_send =?', '0')
                    ->orwhere('is_twitter_send =?)', '1')
                    ->order('sos_id DESC')
                    ->limit(1,0);
        return $this->fetchRow($select);
    }
    
    public function updateSOSFileRowByRowId($id,$data){        
        $where = "sos_file_id ='".$id."'";

       echo  $this->update($data, $where);
    }
}
