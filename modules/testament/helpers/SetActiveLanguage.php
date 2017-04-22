<?php

class Zend_View_Helper_SetActiveLanguage extends Zend_View_Helper_Abstract {

    public function setActiveLanguage() {
        $langSess = new Zend_Session_Namespace('language');
        $langSess->locale;
        $locale = $langSess->locale; //($langSess->locale)?$langSess->locale:($request->getCookie('lang'))?$request->getCookie('lang'):"en";
        $arra['en'] = "English";
        $arra['es'] = "Español";
        $arra['ar'] = "العربية";
        $arra['de'] = "Deutsch";
        $arra['nl'] = "Nederlands";
        $arra['it'] = "Italiano";
        $arra['pt_br'] = "Português";
        $arra['tr'] = "Türkçe";

        $arra['id'] = "Bahasa Indonesia";
        $arra['zh_tw'] = "繁體中文";
        $arra['fr'] = "Français";
        $arra['ja'] = "日本語";
        $arra['zh_cn'] = "简体中文";
        $arra['ko'] = "한국어";
        $arra['th'] = "ภาษาไทย";
        $arra['vi'] = "Tiếng Việt";
        $arra['pl'] = "Polski";
        $arra['cs'] = "Česky";

        $arra['ca'] = "Català";
        $arra['mk'] = "Македонски";
        $arra['nb'] = "Norsk";
        $arra['fa'] = "فارسی";
        $arra['hu'] = "Magyar";
        $arra['fi'] = "suomi";
        $arra['ru'] = "Pусский";
        $arra['sr'] = "српски";
        $arra['ms'] = "Bahasa Melayu";
        $arra['hr'] = "Hrvatski";
        return $arra[$locale];
    }

}

?>
