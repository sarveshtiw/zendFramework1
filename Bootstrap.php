<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    public function run() {
        Zend_Controller_Action_HelperBroker::addPrefix('Helpers');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginator/control.phtml');
        parent::run();
    }

    protected function _initAutoloader() {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        //$autoloader->registerNamespace('Josh_');		
        //$autoloader->registerNamespace('Facebook_');

        return $autoloader;
    }

    protected function _initConstants() {
        $registry = Zend_Registry::getInstance();
        $registry->constants = new Zend_Config($this->getApplication()->getOption('constants'));
        $registry->facebook = new Zend_Config($this->getApplication()->getOption('facebook'));

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

        Zend_Registry::set('config', $config);

        define('_SUCCESS', 0); //Success
        define('_PARAMETER', 1); //Parameter Missing
        define('_QUERY', 2); //Query Problem Or Web-Servcies Problem
        define('_DUPLICACY', 3); //Username or emailID already existing in database
        define('_INVALID_ACCESS', 4); //Username Or UserID or emailID not found in database OR Invalid AppKey
        define('_RECORD', 5); //Record Not Found
        define('_OTHER', 6);
        define('_MAILFROMEMAIL', 'support@appxcel.com');
        define('_MAILFROMNAME', 'WAWAFT');
    }

    protected function _initTranslate() {
        Zend_Loader::loadClass('Zend_Controller_Request_Http');
        $request = new Zend_Controller_Request_Http();
        $language = $request->getParam("l", '');
        if ($language) {
            setcookie('lang', $language,time()+31556926 ,'/');
            $locale = $language;
        } else {
            $request = new Zend_Controller_Request_Http();
            $locale = ($request->getCookie('lang'))?$request->getCookie('lang'):"en"; 
        }
        
        $filePath = APPLICATION_PATH . "/language/" . $locale . ".csv";
        if (!file_exists($filePath)) {
            setcookie('lang', "en",time()+31556926 ,'/');
            $filePath = APPLICATION_PATH . "/language/en.csv";
            $locale="en";
        }
        $translate = new Zend_Translate(
                array("adapter" => 'csv', "content" => $filePath, "locale" => $locale, 'delimiter' => '^'));
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Translate', $translate);
        $langSess = new Zend_Session_Namespace('language');
        $langSess->locale = $locale;
        $translate->setLocale($locale);
    }

}
