<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;

class TestController extends AbstractRestfulController {

    public function getList() {
        $channel = "munchado" . 130;
        $message = "testing";
        $this->pubnub($channel, $message);
    }

    public function pubnub($channel, $message) {
      
        $pnconf = new \PubNub\PNConfiguration();

        $pnconf->setSubscribeKey("sub-c-c86f935c-93d3-11e7-833d-32b0f7aa5bc4");
        $pnconf->setPublishKey("pub-c-d56a9d35-2223-4b10-9518-f989b452c6c1");
        $pnconf->setSecure(false);

        $pubnub = new \PubNub\PubNub($pnconf);
        $result = $pubnub->publish()->channel("my_channel")->message(["hello", "there"])->usePost(true)->sync();
        pr($result,1);
    }

}
