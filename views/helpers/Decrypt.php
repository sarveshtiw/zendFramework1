<?php
class Zend_View_Helper_Decrypt extends Zend_View_Helper_Abstract{

    public function decrypt($cipher){
      $data = explode('/', $cipher);
		$plain = '';
		for ($x=0; $x<count($data); $x++) {
		$plain .= chr($data[$x]);
		}
		return $plain;
       
    }    

    
}
?>
