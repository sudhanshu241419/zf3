<?php

use MCommons\StaticOptions;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));

require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');

$city = new \Home\Model\City();
$currentDateTime = $city->getCityCurrentDateTime(18848);
//$currentDate = date('Y-m-d', strtotime('-1 day',strtotime($currentDateTime)));
$currentDate = date('Y-m-d', strtotime($currentDateTime));
// Find current day user point
$users = getUserPoints('currentDate', $currentDate);
$salesManago = new Salesmanago();
$userModal = new User\Model\User();
if (!empty($users) && count($users) > 0) {

    foreach ($users as $key => $uId) {

        $userpoint = getUserPoints('userpoint', $uId['user_id']);
        $varUserEmail = $userModal->getUserEmail($uId['user_id']);
        
        if (!empty($userpoint)) {

            $totalPoint = $userpoint['total_point'];
            $balancePoint = $userpoint['total_point'] - $userpoint['total_redeem_point'];
            $redeemedPoint = $userpoint['total_redeem_point'];

            if (isset($varUserEmail['email']) && $varUserEmail['email'] != null && $varUserEmail['email'] != '') {
                $dollar = $balancePoint/100;
                $salesManagoData = array('email' => $varUserEmail['email'], 'point' => thousandSap($balancePoint), 'totalpoint' => thousandSap($totalPoint),'redeemed_point'=>thousandSap($redeemedPoint),'earned_dollar'=>$dollar);
                $salesManago->earnPointOnSalesmanago($salesManagoData);
            }
        }
    }
}

function thousandSap($num){
    $explrestunits = "" ;
    if(strlen($num)>3){
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3);
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits;
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++){
            if($i==0){
                $explrestunits .= (int)$expunit[$i].",";
            }else{
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash;
}

function getUserPoints($condition, $filter) {

    $userPointModal = new \User\Model\UserPoint();
    $select = new Select();
    $where = New Where();
    $select->from($userPointModal->getDbTable()->getTableName());

    if ($condition == 'currentDate') {
        $select->columns(array('user_id'));
        $where->like('created_at', '%' . $filter . '%');
        $select->where($where);
        $select->group('user_id');
        //pr($select->getSqlString($userPointModal->getPlatform()),1);
        return $userPointModal->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
    } else {
        $select->columns(array('total_point' => new \Zend\Db\Sql\Expression('sum(points)'), 'total_redeem_point' => new \Zend\Db\Sql\Expression('sum(redeemPoint)')));
        $where->equalTo('user_id', $filter);
        $select->where($where);
        //pr($select->getSqlString($userPointModal->getPlatform()));
        return current($userPointModal->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray());
    }
}
