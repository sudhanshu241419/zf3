<?php

namespace Home\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Functions\UserFunctions;

class CampaignsController extends AbstractRestfulController {

    public function getList() {
        $config = $this->getServiceLocator('Config');
        if ($config ['constants'] ['campaigns']['promotion_five_dollar']) {
            $promotion = ["amount" => PROMOCODE_FIRST_REGISTRATION, "description" => "Register Today and Get $" . PROMOCODE_FIRST_REGISTRATION . " Off Your First Order!*", "valid" => "*Valid at Select Restaurants Only | Expires " . EXPIRE_PROMOCODE_REGISTRATION . " Hours After Registration"];
        } else {
            $promotion = ["amount" => 0, "description" => "", "valid" => ""];
        }
        $dealCount = 0;
        $config ['constants'] ['campaigns']['promotion'] = $promotion;
        $session = $this->getUserSession();
        if ($session->isLoggedIn()) {
            $userId = $session->getUserId();
            $locationData = $session->getUserDetail('selected_location');
            $dealCount = $this->getUnreadDealCount($locationData, $userId);
        }
        $config ['constants'] ['campaigns']['user_deals_count'] = $dealCount;
        return $config ['constants'] ['campaigns'];
    }

    private function getUnreadDealCount($locationData, $userId) {
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        $userFunctions->getUserDealData($currentDate, $userId);
        return $userFunctions->totalUnreadDeals;
    }
}
