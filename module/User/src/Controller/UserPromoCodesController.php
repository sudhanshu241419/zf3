<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;
use User\Functions\UserFunctions;
use City\Model\City;

class UserPromoCodesController extends AbstractRestfulController {

    public function getList() {
        try {
            //$userId = 345;
            $userId = $this->getUserSession()->getUserId();
            if (!$userId) {
                return [];
            }
            $selectedLocation = $this->getUserSession()->getUserDetail('selected_location', []);
            $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : false;
            //$cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
            if (!$cityId) {
                throw new \Zend\Mvc\Exception( "Something went wrong.", 500 );
            }
            $cityModel = $this->getServiceLocator(City::class);
            $cityDetails = $cityModel->cityDetails($cityId);
            $currentCityDateTime = StaticFunctions::getRelativeCityDateTime(['state_code' => $cityDetails [0] ['state_code']])->format('Y-m-d H:i:s');
            $userFunctions = $this->getServiceLocator(UserFunctions::class);
            $userFunctions->userId = $userId;
            $userFunctions->getUserPromocodeDetails();
            $userFunctions->currentDateTimeUnixTimeStamp = strtotime($currentCityDateTime);
            //pr($userFunctions->userPromocodes);die;
            if ($userFunctions->userPromocodes) {
                if ($userFunctions->getNewUserPromotion()) {
                    $userFunctions->userPromocodes[$userFunctions->promocodeId]['promocodeType'] = (int) 1;
                    $promocodes[] = $userFunctions->userPromocodes[$userFunctions->promocodeId];
                    return $promocodes;
                } elseif ($userFunctions->getUserPromocode()) {
                    $userFunctions->userPromocodes[$userFunctions->promocodeId]['promocodeType'] = (int) 0;
                    $promocodes[] = $userFunctions->userPromocodes[$userFunctions->promocodeId];
                    return $promocodes;
                }
            }
            return [];
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On Promocode Api');
            throw new \Exception("something went wrong", 400);
        }
    }
}
