<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;

class RestaurantStory extends AbstractModel {

    public $id;
    public $restaurant_id;
    public $chef_story_source;
    public $chef_name;
    public $image_type;
    public $restaurant_owner;
    public $chef_story;
    public $awards;
    public $shows;
    public $image;
    public $restaurant_desc;
    public $restaurant_history;
    public $restaurant_history_source;
    public $other_info;
    public $other_info_source;
    public $final_story;
    public $tag;
    public $title;
    public $decor;
    public $decor_image;
    public $atmosphere;
    public $atmosphere_image;
    public $cuisine;
    public $cuisine_image;
    public $chef_story_image;
    public $neighborhood;
    public $neighborhood_image;
    public $awards_image;
    public $service;
    public $service_image;
    public $experience;
    public $experience_image;
    public $ambience;
    public $ambience_image;
    public $hover_links;
    public $fun_facts;
    public $fun_facts_image;
    public $location;
    public $location_image;
    public $restaurant_history_image;
    public $other_info_image;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->tableGateway = $tableGateway;
    }

    public function findDetailedStory($restaurant_id = 0, $isMobile = false, $config = null, $cssPath = null, $currentTime = null) {
        $storyDetails = $this->find(array(
                    'columns' => array(
                        'id',
                        'restaurant_id',
                        'chef_name',
                        'chef_story_source',
                        'title',
                        'image',
                        'decor',
                        'decor_image',
                        'cuisine',
                        'cuisine_image',
                        'experience',
                        'experience_image',
                        'location',
                        'location_image',
                        'service',
                        'service_image',
                        'atmosphere',
                        'atmosphere_image',
                        'neighborhood',
                        'neighborhood_image',
                        'awards',
                        'awards_image',
                        'ambience',
                        'ambience_image',
                        'restaurant_history',
                        'restaurant_history_image',
                        'other_info_image'
                    ),
                    'where' => array(
                        'restaurant_id' => $restaurant_id
                    )
                ))->toArray();

        if (empty($storyDetails)) {
            return [];
        }
        $restaurantModel = \MCommons\StaticFunctions::getServiceLocator()->get(Restaurant::class);
        $restaurantData = $restaurantModel->findRestaurant(array(
            'columns' => array(
                'rest_code'
            ),
            'where' => array(
                'id' => $restaurant_id
            )
        ));
        $rest_code = strtolower($restaurantData[0]['rest_code']);
        $restaurant_stories = $history = $decor = $cuisine = $experience = $location = $service = $atmosphere = $atmosphere = $neighborhood = $awards = $ambience = [];
        foreach ($storyDetails as $value) {
            $value = array_intersect_key($value, array_flip(array(
                'title',
                'decor',
                'decor_image',
                'cuisine',
                'cuisine_image',
                'experience',
                'experience_image',
                'location',
                'location_image',
                'service',
                'service_image',
                'atmosphere',
                'atmosphere_image',
                'neighborhood',
                'neighborhood_image',
                'awards',
                'awards_image',
                'ambience',
                'ambience_image',
                'restaurant_history',
                'restaurant_history_image'
            )));
            $strHtml = '';
            $strHtml .= '<!DOCTYPE html>';
            $strHtml .= '<head>';
            $strHtml .= '<meta http-equiv="Expires" content="' . $currentTime . '">';
            $strHtml .= '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
            $strHtml .= '<meta http-equiv="Pragma" content="no-cache" /><div class="story_area">';
            $strHtml .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
            $strHtml .= '<meta name="viewport" content="width=device-width,initial-scale=1.0, user-scalable=no">';
            $strHtml .= '<meta name="apple-mobile-web-app-capable" content="yes">';
            $strHtml .= '<title>MunchAdo.com - Hanger Management</title>';
            $strHtml .= '<meta name="description" content=\'Our servers are loaded with local restaurants ready to serve you food and finally deliver an answer to "Where do you want to eat?" Search Hangry. Order Happy. Reserve Satisfied.\'>';
            $strHtml .= '<link href="' . $cssPath . '" rel="stylesheet">';
            $strHtml .= '</head>';
            $strHtml .= '<body>';
            if (isset($value ['title']) && !empty($value ['title'])) {
                $strHtml .= '<p class="_title">' . $value ['title'] . '</p>';
            }
            $strHtml .= '<div class="content_horizontal">';
            $i = 0;
            foreach ($value as $keys => $val) {
                $i++;
                $class = ($i % 2 == 1) ? "l_side" : "r_side";
                if ($keys == 'title')
                    $restaurant_stories ['story_title'] = $value ['title'];


                if ($keys == 'restaurant_history') {
                    $history ['title'] = "The History";
                    $history ['description'] = $value ['restaurant_history'];
                    if ($value ['restaurant_history_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['restaurant_history_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $history ['title'] . '</h2>';
                        $strHtml .= '<p>' . $history ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'decor') {
                    $decor ['title'] = "The Decor";
                    $decor ['description'] = $value ['decor'];
                    if ($value ['decor_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['decor_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $decor['title'] . '</h2>';
                        $strHtml .= '<p>' . $decor['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'cuisine') {
                    $cuisine ['title'] = "The Cuisine";
                    $cuisine ['description'] = $value ['cuisine'];
                    if ($value ['cuisine_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['cuisine_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $cuisine['title'] . '</h2>';
                        $strHtml .= '<p>' . $cuisine ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'experience') {
                    $experience ['title'] = "The Experience";
                    $experience ['description'] = $value ['experience'];
                    if ($value ['experience_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['experience_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $experience['title'] . '</h2>';
                        $strHtml .= '<p>' . $experience ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'location') {
                    $location ['title'] = "The Location";
                    $location ['description'] = $value ['location'];
                    if ($value ['location_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['location_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $location['title'] . '</h2>';
                        $strHtml .= '<p>' . $location ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'service') {
                    $service ['title'] = "The Service";
                    $service ['description'] = $value ['service'];
                    if ($value ['service_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['service_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $service['title'] . '</h2>';
                        $strHtml .= '<p>' . $service ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'atmosphere') {
                    $atmosphere ['title'] = "The Atmosphere";
                    $atmosphere ['description'] = $value ['atmosphere'];
                    if ($value ['atmosphere_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['atmosphere_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $atmosphere['title'] . '</h2>';
                        $strHtml .= '<p>' . $atmosphere ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'neighborhood') {
                    $neighborhood ['title'] = "The Neighborhood";
                    $neighborhood ['description'] = $value ['neighborhood'];
                    if ($value ['neighborhood_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['neighborhood_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $neighborhood['title'] . '</h2>';
                        $strHtml .= '<p>' . $neighborhood ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'awards') {
                    $awards ['title'] = "The Awards";
                    $awards ['description'] = $value ['awards'];
                    if ($value ['awards_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['awards_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $awards['title'] . '</h2>';
                        $strHtml .= '<p>' . $awards ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }

                if ($keys == 'ambience') {
                    $ambience ['title'] = "The Ambience";
                    $ambience ['description'] = $value ['ambience'];
                    if ($value ['ambience_image'] != '') {
                        $strHtml .= '<div class="list ' . $class . '">';
                        $strHtml .= '<div class="image"><img src="' . $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/' . $rest_code . '/' . $value ['ambience_image'] . '" alt="" title="" /></div>';
                        $strHtml .= '<div class="cont"><div class="v_align_middle"><h2>' . $ambience['title'] . '</h2>';
                        $strHtml .= '<p>' . $ambience ['description'] . '</p></div></div>';
                        $strHtml .= '</div>';
                    }
                }
            }
            $strHtml .= '</div>';
            $strHtml .= '</div>';
            $strHtml .= '</body></html>';
        }
        $restaurant_stories ['section_list'] [] = $history;
        $restaurant_stories ['section_list'] [] = $decor;
        $restaurant_stories ['section_list'] [] = $cuisine;
        $restaurant_stories ['section_list'] [] = $experience;
        $restaurant_stories ['section_list'] [] = $location;
        $restaurant_stories ['section_list'] [] = $service;
        $restaurant_stories ['section_list'] [] = $atmosphere;
        $restaurant_stories ['section_list'] [] = $neighborhood;
        $restaurant_stories ['section_list'] [] = $awards;
        $restaurant_stories ['section_list'] [] = $ambience;

        return $strHtml;
    }

}
