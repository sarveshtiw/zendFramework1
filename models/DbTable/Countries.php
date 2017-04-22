<?php
class Application_Model_DbTable_Countries extends Zend_Db_Table_Abstract{
    
    protected $_name = 'countries';
    protected $_id = 'id';
    
    /**
     *  return the countries records 
     */
    
    public function getCountriesList(){
        $countryTable = new Application_Model_DbTable_Countries();
        $select = $countryTable->select()
                                ->order("country_english_name asc");
        
        $countriesRowset = $countryTable->fetchAll($select);

        $countriesData = array();

        foreach($countriesRowset as $countryRow){
            $countriesData[$countryRow->id] = array(
                'id'            => $countryRow->id, // Modified for Testament ##sarvesh
                'country_name'  => $countryRow->country_english_name,
                'country_code'  => $countryRow->country_code,
                'min_nsn'       => $countryRow->min_nsn,
                'max_nsn'       => $countryRow->max_nsn
            );
        }
        
        return $countriesData;
    }

    public static function getRowByCountryCode($country_code){
        $countryTable = new Application_Model_DbTable_Countries();
        $select = $countryTable->select()
                        ->where('country_code =?',$country_code);
        $countryRow = $countryTable->fetchRow($select);
        return $countryRow;
    }
    
    // Created By ##sarvesh for get Row by Country Id in testament date 2015-4-08
    public function getRowById($id){
        $countryTable = new Application_Model_DbTable_Countries();
        $select = $countryTable->select()
                        ->where('id =?',$id);
        $countryRow = $countryTable->fetchRow($select);        
        return $countryRow;
    }
}
