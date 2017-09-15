<?php

use MCommons\StaticOptions;
set_time_limit(0);
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');

//========== LOG FILE =======================
$job_log_filename = __DIR__ . '/log/' . 'job_' . basename(__FILE__, ".php") . '.log';
$fh_log_file = fopen($job_log_filename, 'a');
fwrite($fh_log_file, 'Time:' . date("Y-m-d H:i:s") . ". Checking new referred users.\n");

$userFunctions = new \User\UserFunctions();
$ur = new \User\Model\UserReferrals();
$eligible_inviters = $ur->getInvitersThreeOrMoreOrderPlaced();
foreach ($eligible_inviters as $inviter) {
    if ($inviter['count'] > 2) {
        $userFunctions->doReferralCreditTransaction($inviter['inviter_id']);
        $userFunctions->sendReferralCreditPubnubNotification($inviter['inviter_id']);
        fwrite($fh_log_file, 'Time:' . date("Y-m-d H:i:s") . '. 30$ referral credit to user_id:' . $inviter['inviter_id'] . "\n");
    }
}

//====================== END CODE. CLOSE LOG FILE =====================
fclose($fh_log_file);