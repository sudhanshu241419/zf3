<?php

use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userInvitationModel = new \User\Model\UserFriendsInvitation();
$joins [] = array(
    'name' => array(
        's' => 'users'
    ),
    'on' => new Expression("(s.id = user_invitations.user_id)"),
    'columns' => array(
       'first_name'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'r' => 'users'
    ),
    'on' => new Expression("(r.email = user_invitations.email)"),
    'columns' => array(
       'receiverName'=>'first_name','receiverId'=>'id'
    ),
    'type' => 'left'
);
$options = array(
    'columns' => array(
        'id', 'email', 'user_id'
    ),
    'joins' => $joins,
    'where' => array('user_invitations.cronUpdate = "0"'),
    'group'=>array('user_invitations.user_id','user_invitations.email')
);
$userInvitationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allInvitations = $userInvitationModel->find($options)->toArray();
//print_r($allInvitations);
//die;

if (count($allInvitations) > 0) {
    $user_function = new \User\UserFunctions();
    //echo date('H:i:s');
    foreach ($allInvitations as $key => $invitation) {
        $insertData = $invitation['id'];
        $mailText = '';
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $acceptLink = $webUrl . DS . 'friendInvitation' . DS . $insertData;
        if(isset($invitation['receiverName']) && !empty($invitation['receiverName'])){
            $fname = $invitation['receiverName'];
        }else{
            $femail = explode("@", $invitation['email']);
            $fname = $femail[0];
        }
//$acceptLink = $this->getBaseUrl() . DS . 'wapi' . DS . 'user' . DS . 'accepted' . DS . $insertData . '?token=' . $this->getUserSession()->token;
        $template = "join_at_munchado";//"friends-Invitation";
        $layout = 'email-layout/referral';//'email-layout/default_new';
        $userName = $invitation['first_name'];
        $friendName = $invitation['receiverName'];
        $variables = array(
            'username' => $userName,
            'inviter' => $userName,
            'mailtext' => $mailText,
            'link' => $acceptLink,
            'hostname' => $webUrl
        );
        $subject = "Join ".ucfirst($userName)." at Munch Ado";//ucfirst($userName) . ' Gave Us Your Email.That’s Cool, Right?';
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
        $userInvitationModel->updateCron($invitation['id']);
        $predicate = "id != ".$invitation['id']." and email = '".$invitation['email']."' and user_id = ".$invitation['user_id']." and cronUpdate = 0";
        $userInvitationModel->abstractDelete($predicate);
       // echo '++++++++++++++++++++'.date('H:i:s');
    }
    //echo '================='.date('H:i:s');
}