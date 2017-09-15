<?php

namespace Home\Controller;

use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;

class FourceUpdateAppController extends AbstractRestfulController {

    public function getList() {
        $currentVersion = $this->getQueryParams("current_version", false);
        $userAgent = StaticFunctions::getUserAgent();
        $hardVersion = ($userAgent == "android") ? HARD_VERSION_ANDROID : HARD_VERSION_IOS;
        $softVersion = ($userAgent == "android") ? SOFT_VERSION_ANDROID : SOFT_VERSION_IOS;

        if ($currentVersion < $hardVersion) {
            $updateType = "hard";
        } elseif ($currentVersion < $softVersion) {
            $updateType = "soft";
        } else {
            $updateType = "no";
        }

        $appUpdate = [
            "upgrade_type" => $updateType,
            "counter" => COUNTER,
            "message" => FOURCE_UPDATE_MESSAGE,
            "clear_data" => CLEAR_DATA
        ];
        return $appUpdate;
    }
}
