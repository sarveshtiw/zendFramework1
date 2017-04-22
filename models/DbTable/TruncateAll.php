<?php

class Application_Model_DbTable_TruncateAllRow extends Zend_Db_Table_Row_Abstract {
    
}

class Application_Model_DbTable_TruncateAll extends Zend_Db_Table_Abstract {

    public function truncateTables() {

$html = '<style>
    .css_image_shadows {
        margin: 0 auto;
        padding: 10px 0;
        position: relative;
        width: 600px;
        z-index: 1;
    }
    .css_image_shadows:after {
        clear: both;
        content: "";
        display: block;
        font-size: 0;
        height: 0;
        visibility: hidden;
    }
    .drop-shadow {
        background: none repeat scroll 0 0 #FFFFFF;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
        float: left;
        margin: 0em 10px 7px;
        padding: 1em;
        position: relative;
        width: 40%;
    }
    .drop-shadow:before, .drop-shadow:after {
        content: "";
        position: absolute;
        z-index: -2;
    }
    .drop-shadow p {
        font-size: 16px;
        font-weight: bold;
    }
    .lifted {
        border-radius: 4px 4px 4px 4px;
    }
    .lifted:before, .lifted:after {
        bottom: 15px;
        box-shadow: 0 15px 10px rgba(0, 0, 0, 0.7);
        height: 20%;
        left: 10px;
        max-width: 300px;
        transform: rotate(-3deg);
        width: 50%;
    }
    .lifted:after {
        left: auto;
        right: 10px;
        transform: rotate(3deg);
    }
    .curled {
        border: 1px solid #EFEFEF;
        border-radius: 0 0 120px 120px / 0 0 6px 6px;
    }
    .curled:before, .curled:after {
        bottom: 12px;
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.5);
        height: 55%;
        left: 10px;
        max-width: 200px;
        transform: skew(-8deg) rotate(-3deg);
        width: 50%;
    }
    .curled:after {
        left: auto;
        right: 10px;
        transform: skew(8deg) rotate(3deg);
    }
    .perspective:before {
        bottom: 5px;
        box-shadow: -80px 0 8px rgba(0, 0, 0, 0.4);
        height: 35%;
        left: 80px;
        max-width: 200px;
        transform: skew(50deg);
        transform-origin: 0 100% 0;
        width: 50%;
    }
    .perspective:after {
        display: none;
    }
    .raised {
        box-shadow: 0 15px 10px -10px rgba(0, 0, 0, 0.5), 0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
    }
    .curved:before {
        border-radius: 10px 10px 10px 10px / 100px 100px 100px 100px;
        bottom: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
        left: 0;
        right: 50%;
        top: 10px;
    }
    .curved-vt-2:before {
        right: 0;
    }
    .curved-hz-1:before {
        border-radius: 100px 100px 100px 100px / 10px 10px 10px 10px;
        bottom: 0;
        left: 10px;
        right: 10px;
        top: 50%;
    }
    .curved-hz-2:before {
        border-radius: 100px 100px 100px 100px / 10px 10px 10px 10px;
        bottom: 0;
        left: 10px;
        right: 10px;
        top: 0;
    }
    .rotated {
        box-shadow: none;
        transform: rotate(-3deg);
    }
    .rotated > *:first-child:before {
        background: none repeat scroll 0 0 #FFFFFF;
        bottom: 0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
        content: "";
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        z-index: -1;
    }
    </style>            
    <div class="css_image_shadows"><div class="drop-shadow lifted" style="width:240px;height:20px;">TABLE NAME</div><div class="drop-shadow curled" style="width:60px;height:20px;visibility:hidden;">STATUS</div>';



$html .='<div class="drop-shadow lifted" style="width:240px;height:20px;">accountSetting</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">blockUsers</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">change_user_details</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">customize_remove_frnds</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">editFriendTrusteeDetails</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">friends</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">groupMembers</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">groups</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">phoneContact</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">secGroupMembers</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">secretGroup</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">trustees</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">users</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">user_notifications</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">usrSetting</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">wa</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">wa_event_send_details</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">wa_event_trustee_response</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">wa_receivers</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div><div class="drop-shadow lifted" style="width:240px;height:20px;">wa_trustees</div><div class="drop-shadow curled" style="width:60px;height:20px; color: #008000;">Done</div>
    
        
    ';

//        $this->getAdapter()->query('TRUNCATE TABLE accountSetting');
//        $this->getAdapter()->query('TRUNCATE TABLE blockUsers');
//        $this->getAdapter()->query('TRUNCATE TABLE change_user_details');
//        $this->getAdapter()->query('TRUNCATE TABLE customize_remove_frnds');
//        $this->getAdapter()->query('TRUNCATE TABLE editFriendTrusteeDetails');
//        $this->getAdapter()->query('TRUNCATE TABLE friends');
//        $this->getAdapter()->query('TRUNCATE TABLE groupMembers');
//        $this->getAdapter()->query('TRUNCATE TABLE groups');
//        $this->getAdapter()->query('TRUNCATE TABLE phoneContact');
//        $this->getAdapter()->query('TRUNCATE TABLE secGroupMembers');
//        $this->getAdapter()->query('TRUNCATE TABLE secretGroup');
//        $this->getAdapter()->query('TRUNCATE TABLE trustees');
//        $this->getAdapter()->query('TRUNCATE TABLE users');
//        $this->getAdapter()->query('TRUNCATE TABLE user_notifications');
//        $this->getAdapter()->query('TRUNCATE TABLE usrSetting');
//        $this->getAdapter()->query('TRUNCATE TABLE wa');
//        $this->getAdapter()->query('TRUNCATE TABLE wa_points');
//        $this->getAdapter()->query('TRUNCATE TABLE wa_event_send_details');
//        $this->getAdapter()->query('TRUNCATE TABLE wa_event_trustee_response');
//        $this->getAdapter()->query('TRUNCATE TABLE wa_receivers');
//        $this->getAdapter()->query('TRUNCATE TABLE wa_trustees');




        echo $html;
        exit;
    }

}

?>
