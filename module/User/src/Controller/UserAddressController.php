<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;
use User\Model\UserAddress;
use City\Model\City;
use User\Functions\UserFunctions;

class UserAddressController extends AbstractRestfulController {

    public function create($data) {
        if (empty($data)) {
            throw new \Exception("Invalid Parameters", 400);
        } else {
            $userAddress = $this->getServiceLocator(UserAddress::class);
            $userAddress->address_name = '';
            $session = $this->getUserSession();
            $isLoggedIn = $session->isLoggedIn();
            if ($isLoggedIn) {
                $data ['user_id'] = $session->getUserId();
                $userAddress->user_id = (int) $data ['user_id'];
            }

            if (isset($data ['first_name']) && !empty($data ['first_name'])) {
                $firstName = $data ['first_name'];
            } else {
                throw new \Exception("First name can't be empty", 400);
            }

            if (isset($data ['last_name']) && !empty($data ['last_name'])) {
                $lastName = $data['last_name'];
            } else {
                $lastName = "";
                //throw new \Exception("Last name can't be empty", 400);
            }

            if (isset($data ['address1']) && !empty($data ['address1'])) {
                $userAddress->street = $data ['address1'];
            } else {
                throw new \Exception("Address can't be empty", 400);
            }

            if (isset($data ['city']) && !empty($data ['city'])) {
                $userAddress->city = $data ['city'];
            } else {
                throw new \Exception("City dose not exists", 400);
            }

            if (isset($data ['state']) && !empty($data ['state'])) {
                $userAddress->state = $data ['state'];
            } else {
                throw new \Exception("State does not exists", 400);
            }

            if (isset($data ['email']) && !empty($data ['email'])) {
                $userAddress->email = $data ['email'];
            } else {
                throw new \Exception("Email can't be empty", 400);
            }

            if (!isset($data['zipcode']) || empty($data['zipcode'])) {
                throw new \Exception("User zip code is required", 400);
            }

            if (!isset($data['address_lat']) && empty($data['address_lat'])) {
                throw new \Exception("Address latitude is not valid", 400);
            }
            if (!isset($data['address_lng']) && empty($data['address_lng'])) {
                throw new \Exception("Address longitude is not valid", 400);
            }
            $userFunctions = $this->getServiceLocator(UserFunctions::class);
            $locationData = $session->getUserDetail('selected_location', []);
            $currentDate = $userFunctions->userCityTimeZone($locationData);
            $userAddress->zipcode = isset($data['zipcode']) ? $data['zipcode'] : "";
            $userAddress->phone = (isset($data ['phone'])) ? $data ['phone'] : '';
            $userAddress->apt_suite = (isset($data ['address2'])) ? $data ['address2'] : '';
            $userAddress->delivery_instructions = (isset($data ['instruction'])) ? $data ['instruction'] : '';
            $userAddress->address_type = 's';
            $userAddress->status = 1;
            $userAddress->address_name = $firstName . " " . $lastName;
            $data ['created_on'] = $currentDate;
            $data ['updated_at'] = $currentDate;

            $options = [
                'columns' => ['id'],
                'where' => [
                    'user_id' => $data['user_id'],
                    'latitude' => $data['address_lat'],
                    'longitude' => $data['address_lng'],
                ]
            ];
            $userAddressDetail = $userAddress->getUserAddressInfo($options);

            if (!empty($userAddressDetail)) {
                $userAddress->id = $userAddressDetail ['id'];
            }

            try {
                $userAddress->latitude = $data['address_lat'];
                $userAddress->longitude = $data['address_lng'];
                $userAddress->google_addrres_type = 'street';
                $response = $userAddress->addAddress();
            } catch (\Exception $ex) {
                return $this->sendError([
                            'error' => $ex->getMessage()
                                ], $ex->getCode());
            }

            if (!$response) {
                throw new \Exception('Unable to save address', 400);
            }

            $name = explode(" ", $response['address_name']);
            $response['first_name'] = $name[0];
            $response['last_name'] = $name[1];
            unset($response['delivery_instructions'], $response['address_name'], $response['country'], $response['takeout_instructions'], $response['created_on'], $response['updated_at'], $response['status'], $response['address_type'], $response['mobile']);
            return $response;
        }
    }

    function getList() {
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        if(!$isLoggedIn){
           throw new \Exception('User unavailable', 400); 
        }
        try {
            $userId = $session->getUserId();
            $selectedLocation = $session->getUserDetail('selected_location', []);
            $cityId = $selectedLocation ['city_id'];
            $cityModel = $this->getServiceLocator(City::class);
            $cityDetails = $cityModel->cityDetails($cityId);
            $cityname = $cityDetails[0]['city_name'];
            $userAddress = $this->getServiceLocator(UserAddress::class);
            $response = $userAddress->getUserAddressDetail($userId, $cityname);
            $resturentId = $this->getQueryParams('restid');
            foreach ($response as $key => $val) {
                if ($resturentId) {
                    $cityDelivery = $this->getServiceLocator(\Search\Common\CityDeliveryCheck::class);
                    $res_can_deliver =$cityDelivery->canDeliver($resturentId, $val['latitude'], $val['longitude']);
                    $response [$key] ['res_can_deliver'] = $res_can_deliver ? '1' : '0';
                }
                if ($key == 0) {
                    $response [$key] ['default'] = '1';
                } else {
                    $response [$key] ['default'] = '0';
                }
            }
            $userFunction = $this->getServiceLocator(UserFunctions::class);
            $response = $userFunction->ReplaceNullInArray($response);

            foreach ($response as $key => $val) {
                $name = explode(" ", $val['address_name']);
                $response[$key]['first_name'] = isset($name[0]) ? $name[0] : "";
                $response[$key]['last_name'] = isset($name[1]) ? $name[1] : "";

                unset($response[$key]['address_name'], $response[$key]['mobile'], $response[$key]['country'], $response[$key]['takeout_instructions'], $response[$key]['address_type']);
            }
            return $response;
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On User Address Api');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function update($id, $data) {
        if (isset($id) && !empty($id) && !empty($data)) {
            $userAddress = $this->getServiceLocator(UserAddress::class);
            $userAddress->address_name = '';
            $session = $this->getUserSession();
            $isLoggedIn = $session->isLoggedIn();
            if ($isLoggedIn) {
                $data ['user_id'] = $session->getUserId();
                $userAddress->user_id = (int) $data ['user_id'];
            }

            if (isset($data ['first_name']) && !empty($data ['first_name'])) {
                $firstName = $data ['first_name'];
            } else {
                throw new \Exception("First name can't be empty", 400);
            }

            if (isset($data ['last_name']) && !empty($data ['last_name'])) {
                $lastName = $data['last_name'];
            } else {
                $lastName = "";
            }

            if (isset($data ['address1']) && !empty($data ['address1'])) {
                $userAddress->street = $data ['address1'];
            } else {
                throw new \Exception("Address can't be empty", 400);
            }

            if (isset($data ['city']) && !empty($data ['city'])) {
                $userAddress->city = $data ['city'];
            } else {
                throw new \Exception("City dose not exists", 400);
            }

            if (isset($data ['state']) && !empty($data ['state'])) {
                $userAddress->state = $data ['state'];
            } else {
                throw new \Exception("State dose not exists", 400);
            }

            if (isset($data ['email']) && !empty($data ['email'])) {
                $userAddress->email = $data ['email'];
            } else {
                throw new \Exception("Email can't be empty", 400);
            }

            if (!isset($data['zipcode']) || empty($data['zipcode'])) {
                throw new \Exception("User zip code is required", 400);
            }

            if (!isset($data['address_lat']) && empty($data['address_lat'])) {
                throw new \Exception("Address latitude is not valid", 400);
            }
            if (!isset($data['address_lng']) && empty($data['address_lng'])) {
                throw new \Exception("Address longitude is not valid", 400);
            }

            $userFunctions = $this->getServiceLocator(UserFunctions::class);
            $locationData = $session->getUserDetail('selected_location', []);
            $currentDate = $userFunctions->userCityTimeZone($locationData);
            $userAddress->id = $id;
            $userAddress->zipcode = isset($data['zipcode']) ? $data['zipcode'] : "";
            $userAddress->phone = (isset($data ['phone'])) ? $data ['phone'] : '';
            $userAddress->apt_suite = (isset($data ['address2'])) ? $data ['address2'] : '';
            $userAddress->delivery_instructions = (isset($data ['instruction'])) ? $data ['instruction'] : '';
            $userAddress->address_type = 's';
            $userAddress->status = 1;
            $userAddress->address_name = $firstName . " " . $lastName;

            $data ['updated_at'] = $currentDate;

            try {
                $userAddress->latitude = $data['address_lat'];
                $userAddress->longitude = $data['address_lng'];
                $userAddress->google_addrres_type = 'street';
                $response = $userAddress->addAddress();
            } catch (\Exception $ex) {
                return $this->sendError([
                            'error' => $ex->getMessage()
                                ], $ex->getCode());
            }
            if (!$response) {
                throw new \Exception('Unable to save address', 400);
            }

            $name = explode(" ", $response['address_name']);
            $response['first_name'] = $name[0];
            $response['last_name'] = $name[1];
            unset($response['delivery_instructions'], $response['address_name'], $response['country'], $response['takeout_instructions'], $response['created_on'], $response['updated_at'], $response['status'], $response['address_type'], $response['mobile']);
            return $response;
        } else {
            throw new \Exception("Address data is not valid", 400);
        }
    }

}
