<?php

class Application_Model_Game
{
	public function __construct()
    {
    $this->db = Zend_Db_Table::getDefaultAdapter();
    }
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }
	
	public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_Game');
        }
		
        return $this->_dbTable;
    }
    
    public function checkweekgamebydate($startdate,$enddate)
    {	$arr=$this->db->query("select * from cwGame where (('".$startdate."' between concat_ws(' ',gameStartDate,gameStartTime) and concat_ws(' ',gameEndDate,gameEndTime)) or ('".$enddate."' between concat_ws(' ',gameStartDate,gameStartTime) and concat_ws(' ',gameEndDate,gameEndTime))) and gameType='weekly' ")->fetchAll();
		return (count($arr)>0)?$arr[0]->gameid:"";
		}
		
	public function checkcompetitiongamebydate($startdate,$enddate)
    {	
		$arr=$this->db->query("select * from cwGame where (('".$startdate."' between concat_ws(' ',gameStartDate,gameStartTime) and concat_ws(' ',gameEndDate,gameEndTime)) or ('".$enddate."' between concat_ws(' ',gameStartDate,gameStartTime) and concat_ws(' ',gameEndDate,gameEndTime))) and gameType='competition' ")->fetchAll();
		return (count($arr)>0)?$arr[0]->gameid:"";
		}
	
	public function updateGameById($update,$gameId)
    {
		$this->getDbTable()->update($update,array('gameid = ? '=>$gameId));
		}
	public function insertGame($update)
	{
		$this->getDbTable()->insert($update);
		}
		
	public function getpackinfo($packid)
	{		
			return $this->db->select()->from('cwPack')->where('packId = ?',$packid)->query()->fetch();
		}
	
	
	
}
