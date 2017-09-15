<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\RestaurantBookmark;
use Zend\Db\Sql\Predicate\Expression;
use User\Functions\UserFunctions;

class RestaurantBookmarksController extends AbstractRestfulController {

    const FORCE_LOGIN = true;

    public function getList() {
        $queryParams = $this->getRequest()->getQuery()->toArray();
        $session = $this->getUserSession();
        $userId = $session->getUserId();
        $friendId = $this->getQueryParams('friendid', false);
        if ($friendId) {
            $userId = $friendId;
        }
        $restaurantBookmarks = $this->getServiceLocator(RestaurantBookmark::class);
        $order = array(
            'created_on' => 'desc'
        );
        if (isset($queryParams ['sort'])) {
            if ($queryParams ['sort'] == 'date') {
                $order = array(
                    'created_on' => 'desc'
                );
            } elseif ($queryParams ['sort'] == 'alphabetical') {
                $order = array(
                    'restaurant_name' => 'asc'
                );
            }
        }
        $joins[] = array(
            'name' => array(
                'r' => 'restaurants'
            ),
            'on' => new Expression("(r.id = restaurant_bookmarks.restaurant_id)"),
            'columns' => array(
                'restaurant_name',
                'closed',
                'inactive'
            ),
            'type' => 'left'
        );
        $options = array(
            'columns' => array(
                'restaurant_id',
                'restaurant_name',
                'type',
                'created_on'
            ),
            'where' => array(
                'user_id' => $userId
            ),
            'joins' => $joins,
            'order' => $order
        );

        $restaurantBookmarkDetails = $restaurantBookmarks->find($options)->toArray();
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $response = $userFunctions->arrangeRestaurantBookmarks($restaurantBookmarkDetails);
        $count = $restaurantBookmarks->addCountToResponse($userId);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $page = $this->getQueryParams('page', 1);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        $totalBookmark = count($response);
        $response = array_slice($response, $offset, $limit);
        $finalResponse['bookmarks'] = $response;
        $finalResponse['count'] = $count;
        $finalResponse['total_bookmark'] = $totalBookmark;
        return $finalResponse;
    }

    private function addCountToResponse($response) {
        $lovedIt = $beenThere = $craveIt = 0;
        foreach ($response as $single) {
            if ($single ['loved_it']) {
                $lovedIt += 1;
            }
            if ($single ['been_there']) {
                $beenThere += 1;
            }
            if ($single ['crave_it']) {
                $craveIt += 1;
            }
        }
        return [
            'loved_it_count' => $lovedIt,
            'been_there_count' => $beenThere,
            'crave_it_count' => $craveIt
        ];
    }
}
