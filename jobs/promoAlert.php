<?php

use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
return false;
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userPromocodesModel = new \Restaurant\Model\Promocodes();
$joins [] = array(
    'name' => array(
        'up' => 'user_promocodes'
    ),
    'on' => new Expression("(up.promo_id= promocodes.id)"),
    'columns' => array(
       'user_id'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        's' => 'users'
    ),
    'on' => new Expression("(up.user_id = s.id)"),
    'columns' => array(
       'first_name','email','city_id'
    ),
    'type' => 'inner'
);
$options = array(
    'columns' => array(
        'id','end_date'
    ),
    'joins' => $joins,
    'where' => array('promocodes.promocodeType="2" and promocodes.cronUpdate = "0" and up.reedemed="0"' )
);
$userPromocodesModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allInvitations = $userPromocodesModel->find($options)->toArray();
//pr($allInvitations,1);

if (count($allInvitations) > 0) {
    $user_function = new \User\UserFunctions();
    //echo date('H:i:s');
    foreach ($allInvitations as $key => $invitation) {
        $city = new \Home\Model\City();
        $invitation['city_id']=($invitation['city_id']!='' && $invitation['city_id']!=NULL && $invitation['city_id'] > 0)?$invitation['city_id']:18848;
        $currentDateTime = $city->getCityCurrentDateTime($invitation['city_id']);
        $currentTimeOrder = new \DateTime ();
        $arrivedTimeOrder = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $invitation['end_date']);
        $differenceOfTimeInMin = round(abs(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s")) - strtotime($currentDateTime)) / 60/60);
        //it should be 12 after testing by QA
       if($differenceOfTimeInMin<=12){
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $template = "promo-alert";
        $layout = 'email-layout/default_new';
        $userName = $invitation['first_name'];
        $variables = array(
            'username' => $userName,
            'acceptlink' => $webUrl,
            'hostname' => $webUrl,
            'promoamount'=>PROMOCODE_FIRST_REGISTRATION,
        );
        $subject = 'Your $'.PROMOCODE_FIRST_REGISTRATION.' Coupon is Expiring Soon!';
        $emailData = array(
            'receiver' => array(
                $invitation['email']
            ),
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );
        $user_function->sendMails($emailData);
        $userPromocodesModel->updateCron($invitation['id']);
        }
       // echo '++++++++++++++++++++'.date('H:i:s');
    }
    //echo '================='.date('H:i:s');
}