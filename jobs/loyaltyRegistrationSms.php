<?php
use MCommons\StaticOptions;
use User\Model\UserFunctions;
use Zend\Db\Sql\Predicate\Expression;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');

      $str= $_GET['q'];
     // pr($str,1);
      $userFunction = new \User\UserFunctions();
      if($userFunction->parseLoyaltyRegistrationSms($str)){          
          if($userFunction->userRegistrationWithSmsWeb()){             
                $userFunction->registerRestaurantServer();
                return array("success"=>true);
          }
          
      }
 return array("success"=>false);