<?php

namespace Restaurant\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\RestaurantStory;
use \MCommons\StaticFunctions;

class RestaurantStoryController extends AbstractRestfulController {

    public $config;
    
    public function get($restaurantId) {
        $storyModel = $this->getServiceLocator(RestaurantStory::class);
        $cssPath = $this->getQueryParams('csspath', false);
        $this->config = $this->getServiceLocator('Config');
        //pr($this->config,1);
        $currentTime = StaticFunctions::getRelativeCityDateTime([
                    'restaurant_id' => $restaurantId
                ])->format(StaticFunctions::COOKIE);
        $storyDetails = $storyModel->findDetailedStory($restaurantId, $this->isMobile(), $this->config, $cssPath, $currentTime);
        return $response = [
            'story' => $storyDetails
        ];
    }
}
