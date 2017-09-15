<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\ActivityFeed;
use User\Model\UserFriends;
use User\Functions\UserFunctions;
use Bookmark\Model\FeedBookmark;
use User\Model\FeedComment;

class UserActivityFeedController extends AbstractRestfulController {

    public function getList() {
        $feed = $feed1 = [];
        $userFriendModel = $this->getServiceLocator(UserFriends::class);
        $userId = $this->getUserSession()->getUserId();
        $type = $this->getQueryParams('type', false);
        $activityFeedModel = $this->getServiceLocator(ActivityFeed::class);
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $page = $this->getQueryParams('page', 1);
        $version = $this->getQueryParams('version');
        $friendCounts = $userFriendModel->getTotalUserFriends($userId);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        $joins [] = array(
            'name' => 'users',
            'on' => 'users.id = activity_feed.user_id',
            'columns' => array(
                'display_pic_url',
            ),
            'type' => 'left'
        );
        $joins [] = array(
            'name' => 'activity_feed_type',
            'on' => 'activity_feed_type.id = activity_feed.feed_type_id',
            'columns' => array(
                'feed_type',
            ),
            'type' => 'left'
        );

        $lastVerion = explode('.', '1.0.9');
        $version = explode('.', $version);
        $feedByVersion = false;
        foreach ($version as $key => $v) {
            if ($v > $lastVerion[$key]) {
                $feedByVersion = true;
            }
        }

        $friendsId = "";
        $friends = $userFriendModel->getUserFriendList($userId, $orderby = "");
        if (count($friends) > 0) {
            foreach ($friends as $key => $val) {
                $friendsId .= $val['friend_id'] . ",";
            }
            $friendsId = substr($friendsId, 0, -1);
        } else {
            $friendsId = 0;
        }


        if ($type === 'others' && $friendsId != 0) {

            if (!$version) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1" and feed_type_id!="52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );
                $countOption = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1" and feed_type_id!="52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            } else if (isset($feedByVersion) && $feedByVersion == true) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );
                $countOption = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            } else {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1" and feed_type_id <= "52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );
                $countOption = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $friendsId . ') and activity_feed.status="1" and feed_for_others!="" and activity_feed.privacy_status="1" and feed_type_id <= "52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            }
            $totalCount = $activityFeedModel->find($countOption)->count();
        } else {
            if (!$version) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id =' . $userId . ' and activity_feed.status="1" and feed_type_id!="52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );
                $countOptions = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id =' . $userId . ' and activity_feed.status="1" and feed_type_id!="52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            } else if (isset($feedByVersion) && $feedByVersion == true) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $userId . ') and activity_feed.status="1" and activity_feed.privacy_status="1"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );

                $countOptions = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $userId . ') and activity_feed.status="1" and activity_feed.privacy_status="1"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            } else {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id=' . $userId . ' and activity_feed.status="1" and feed_type_id<= "52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => $limit,
                    'offset' => $offset,
                    'joins' => $joins
                );
                $countOptions = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id=' . $userId . ' and activity_feed.status="1" and feed_type_id<= "52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'joins' => $joins
                );
            }

            $totalCount = $activityFeedModel->find($countOptions)->count();
        }
        if ($activityFeedModel->find($options)->toArray()) {
            $feed = $activityFeedModel->find($options)->toArray();
            $feedBookmark = $this->getServiceLocator(FeedBookmark::class);
            $feedComment = $this->getServiceLocator(FeedComment::class);
            foreach ($feed as $key => $val) {
                $opt1 = array(
                    'columns' => array('total_like' => new \Zend\Db\Sql\Expression('COUNT(id)')),
                    'where' => array('feed_id' => $val['id']));
                $opt2 = array(
                    'columns' => array('total_comment' => new \Zend\Db\Sql\Expression('COUNT(id)')),
                    'where' => array('feed_id' => $val['id']));
                $totalfeedbookmark = $feedBookmark->find($opt1)->toArray();
                $totalfeedcomment = $feedComment->find($opt2)->toArray();
                $feedGet = explode('{', $val['feed']);
                $checkMess = $checkReviewMessage = $checkTipMessage = $checkCaptionMessage = '';
                foreach ($feedGet as $v) {
                    if ($v != '') {
                        $sc = explode(',', $v);
                        foreach ($sc as $vv) {
                            if ($vv != '') {
                                $vvv = explode(':', $vv);
                                if ($vvv[0] == '"checkinmessage"') {
                                    $checkMess = trim($vvv[1], '"');
                                }
                                if ($vvv[0] == '"tip"') {
                                    $checkTipMessage = trim($vvv[1], '"');
                                    $checkTipMessage = str_replace('%2C', ',', $checkTipMessage);
                                }
                                if ($vvv[0] == '"caption"') {
                                    $checkCaptionMessage = trim($vvv[1], '"');
                                }
                                if ($vvv[0] == '"review_desc"') {
                                    $checkReviewMessage = trim(trim($vvv[1], '}'), '"');
                                }
                            }
                        }
                    }
                }
                $feedJsonVal = json_decode($val['feed'], true);
                if (!empty($feedJsonVal['feed_for_other'])) {
                    $feed[$key]['others'] = 1;
                } else {
                    $feed[$key]['others'] = 0;
                }
                if (key_exists('checkinmessage', $feedJsonVal)) {
                    unset($feedJsonVal['checkinmessage']);
                }
                if (isset($feedJsonVal['review']) && count($feedJsonVal['review']) > 0) {
                    unset($feedJsonVal['review']['review_desc']);
                    $feedJsonVal['review']['review_desc'] = $checkReviewMessage;
                    $feedJsonVal['review']['come_back'] = (string) $feedJsonVal['review']['come_back'];
                }
                $feed[$key]['total_like'] = (int) $totalfeedbookmark[0]['total_like'];
                $feed[$key]['total_comment'] = (int) $totalfeedcomment[0]['total_comment'];
                if ($feed[$key]['others'] == 1) {
                    $feed[$key]['user_like'] = ($userFunctions->userLikeFeed($userId, $val['id'])) ? 1 : 0;
                } else {
                    $feed[$key]['user_like'] = ($userFunctions->userLikeFeed($val['user_id'], $val['id'])) ? 1 : 0;
                }
                $feed[$key]['user_comment'] = ($userFunctions->userCommentFeed($val['user_id'], $val['id'])) ? 1 : 0;
                $feed[$key]['feedinfo'] = $feedJsonVal;
                if ($checkTipMessage != '') {
                    $feed[$key]['feedinfo']['tip'] = $checkTipMessage;
                }
                if ($checkCaptionMessage != '') {
                    $feed[$key]['feedinfo']['caption'] = $checkCaptionMessage;
                }
                $feed[$key]['feedinfo']['checkinmessage'] = $checkMess;
                $feed[$key]['feedinfo']['feed_for_other'] = isset($feed[$key]['feedinfo']['feed_for_other']) && $feed[$key]['feedinfo']['feed_for_other'] != '' ? str_replace('â€™', "'", $feed[$key]['feedinfo']['feed_for_other']) : '';
                $feed[$key]['feedinfo']['text'] = isset($feed[$key]['feedinfo']['text']) && $feed[$key]['feedinfo']['text'] != '' ? str_replace('â€™', "'", $feed[$key]['feedinfo']['text']) : '';
                $feed[$key]['display_pic_url'] = $userFunctions->findImageUrlNormal($val['display_pic_url'], $val['user_id']);
                unset($feed[$key]['feed'], $feed[$key]['feed_for_others'], $feed[$key]['event_date_time'], $feed[$key]['status'], $feed[$key]['feed_type_id']);
            }
            $feed1['feeds'] = $feed;
            $feed1['total_count'] = $totalCount;
            $feed1['friends_count'] = $friendCounts['total_friend'];
            return $feed1;
        }
        return $feed1;
    }

}
