<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\MenuBookmark;
use Zend\Db\Sql\Predicate\Expression;
use User\Functions\UserFunctions;

class MenuBookmarksController extends AbstractRestfulController {

    const FORCE_LOGIN = true;

    public function getList() {
        $session = $this->getUserSession();
        $userId = $session->getUserId();
        $friendId = $this->getQueryParams('friendid',false);
        if($friendId){
            $userId = $friendId;
        }
        $menuBookmarks = $this->getServiceLocator(MenuBookmark::class);
        $order = array(
            'menu_bookmarks.created_on' => 'desc'
        );
        $joins[] = array(
            'name' => array(
                'r' => 'restaurants'
            ),
            'on' => new Expression("(r.id = menu_bookmarks.restaurant_id)"),
            'columns' => array(
                'restaurant_name',
                'closed',
                'inactive'
            ),
            'type' => 'left'
        );
        $joins[] = array(
            'name' => array(
                'm' => 'menus'
            ),
            'on' => new Expression("(m.id = menu_bookmarks.menu_id)"),
            'columns' => array(
                'status'
            ),
            'type' => 'Inner'
        );
        $options = array(
            'columns' => array(
                'user_id',
                'restaurant_id',
                'menu_id',
                'menu_name',
                'type',
                'created_on'
            ),
            'where' => array(
                'user_id' => $userId
            ),   
            'order'=>$order,
            'joins' => $joins,
            'group'=>array('menu_id','user_id','type')
        );

        $menubookmarkdetails = $menuBookmarks->find($options)->toArray();
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $response = $userFunctions->arrangeMenuBookmarks($menubookmarkdetails);
        $count = $this->addCountToResponse($response);
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
        $lovedIt = $wantIt = $triedIt = 0;
        foreach ($response as $single) {
            if ($single ['loved_it']) {
                $lovedIt ++;
            }
            if ($single ['want_it']) {
                $wantIt ++;
            }
            if ($single ['tried_it']) {
                $triedIt ++;
            }
        }
        return [
            'loved_it_count' => $lovedIt,
            'crave_it_count' => $wantIt,
            'tried_it_count' => $triedIt
        ];
    }
}
