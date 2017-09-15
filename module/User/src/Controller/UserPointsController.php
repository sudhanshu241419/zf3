<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserPoints;
use MCommons\StaticFunctions;
use User\Model\UserPoint;
use Restaurant\Model\Restaurant;

class UserPointsController extends AbstractRestfulController {

    public function getList() {
        $session = $this->getUserSession();
        if ($session) {
            $login = $session->isLoggedIn();
            if (!$login) {
                throw new \Exception('No Active Login Found.');
            }
        } else {
            throw new \Exception('No Active Login Found.');
        }
        $userId = $session->getUserId();
        $friendId = $this->getQueryParams('friendid', false);
        if ($friendId) {
            $userId = $friendId;
        }
        $restaurantId = $this->getQueryParams('restid', false);
        
        $UserPoints = [];
        $total_points = $this->userPointsCount($userId);
        $UserPoints['redeemed_point'] = count($total_points[0]['redeemed_points']) > 0 ? strval($total_points[0]['redeemed_points']) : '0';
        $balance_points = strval($total_points[0]['points']);
        $UserPoints['total_points'] = (count($balance_points) > 0) ? $balance_points : 0;
        $redeemed_point = $UserPoints['total_points'] - $UserPoints['redeemed_point'];
        
        
        if($restaurantId){
            $config = $this->getServiceLocator('Config');
            $pointEqualDollar = $config ['constants']['pointEqualDollar']; 
            $restaurant = $this->getServiceLocator(Restaurant::class);
            $options = ["columns"=>["order_pass_through"],"where"=>['id'=>$restaurantId]];
            $restDetails = $restaurant->findRestaurant($options);
            $UserPoints['can_pay_by_points'] = ((int)$restDetails->order_pass_through==0)?1:0;
            $UserPoints['redeemable_point'] = $redeemed_point;
            $UserPoints['point_equal_dollar'] = $pointEqualDollar[1];
        }else{
            $UserPoints['cash_available'] = StaticFunctions::amtRedeemPoint($redeemed_point);
            $UserPoints['cash_available'] = $UserPoints['cash_available'] > 0 ? strval($UserPoints['cash_available']) : '0';
            $UserPoints['activities'] = $this->userPointsDetails($userId);
            $totalRecords = count($UserPoints['activities']);
            $UserPoints['total_records'] = $totalRecords;
            $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
            $page = $this->getQueryParams('page', 1);
            $offset = 0;
            if ($page > 0) {
                $page = ($page < 1) ? 1 : $page;
                $offset = ($page - 1) * ($limit);
            }            
            $UserPoints['activities'] = array_slice($UserPoints['activities'], $offset, $limit);
        }
        $UserPoints['minimum_point_for_redeem'] = POINT_REDEEM_LIMIT;
        return $UserPoints;
    }

    private function userPointsCount($userId) {
        $userPoints = $this->getServiceLocator(UserPoint::class);
        $data = $userPoints->countUserPoints($userId);
        return $data;
    }

    private function userPointsDetails($userId) {
        $userPoints = $this->getServiceLocator(UserPoints::class);
        $data = $userPoints->UserPointsDetails($userId);
        $activity = [];
        foreach ($data as $key => $val) {
            $activity[] = $val;
            $activity[$key]['activity_points'] = ($activity[$key]['activity_type']==="Redeem")?$activity[$key]['redeemPoint']:$activity[$key]['activity_points'];
            $dateObject = new \DateTime($val['created_at']);
            $activity[$key]['activity_date'] = $dateObject->format('Y-m-d H:i');
            unset($activity[$key]['created_at'], $activity[$key]['activity_id'],$activity[$key]['redeemPoint']);
        }
        return $activity;
    }

}
