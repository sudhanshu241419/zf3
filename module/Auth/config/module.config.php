<?php
namespace Auth;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'wapi-token' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/wapi/auth/token[/:id]',
                    'constraints' => [
                        'id'     => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AuthController::class ,
                    ],
                ],
            ],
            
            'api-token' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/auth/token[/:id]',
                    'constraints' => [
                        'id'     => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AuthController::class
                   ]
               ]
            ],
           
            
        ],
    ],
    'view_manager' => [
        
   ],
    'constants' => [
        'order_type' => [
            'group' => 'G',
            'individual' => 'I',
            'orderPending' => '0'
        ],
        'order_status' => [
            'placed',//0
            'ordered',//1
            'confirmed',//2
            'delivered',//3
            'cancelled',//4
            'rejected',//5
            'arrived',//6
            'frozen',//7
            'ready',//8
            'archived'//9
        ],
        'reservation_status' => [
            'archived' => '0',
            'upcoming' => '1',
            'canceled' => '2',
            'rejected' => '3',
            'confirmed' => '4'
        ],
        'reservation_status_' => [
            '0' => 'archived',
            '1' => 'upcoming',
            '2' => 'canceled',
            '3' => 'rejected',
            '4' => 'confirmed'
        ],
        'reservation_invitation_status' => [
            'invited' => '0',
            'accepted' => '1',
            'denied' => '2',
            'submitted' => '3'
        ],
        'dealsCoupons_status' => [
            'live',
            'archived',
            'expired',
            'redeemed'
        ],
        'user_friends_status' => [
            'active' => '1',
            'inactive' => '0',
            'unfriend' => '2'
        ],
        'point_source_detail' => [
            'orderPlacedTakeout' => '1',
            'groupOrderPlaced' => '2',
            'reserveATable' => '3',
            'purchaseADealCoupon' => '4',
            'inviteFriends' => '5',
            'rateAndReview' => '6',
            'postPictures' => '7',
            'reportErrors' => '8',
            'completeProfile' => '9',
            'postOnFacebook' => '10',
            'postOnTwitter' => '11',
            'reservationAccept' => '17'
        ],
        'review_for' => [
            '1' => 'Delivery',
            '2' => 'Takeout',
            '3' => 'Dinein'
        ],
        'on_time' => [
            '0' => '',
            '1' => 'Yes',
            '2' => 'No'
        ],
        'fresh_prepared' => [
            '0' => '',
            '1' => 'Yes',
            '2' => 'No'
        ],
        'as_specifications' => [
            '0' => '',
            '1' => 'Yes',
            '2' => 'No'
        ],
        'temp_food' => [
            '0' => '',
            '1' => 'Too cold',
            '2' => 'Just right',
            '3' => 'Too hot'
        ],
        'taste_test' => [
            '0' => '',
            '1' => 'Horiable',
            '2' => 'Ok but could be better',
            '3' => 'Loved it'
        ],
        'services' => [
            '0' => '',
            '1' => 'Extremly nice',
            '2' => 'Just right',
            '3' => 'Un-acceptiable Unfriendly',
        ],
        'noise_level' => [
            '0' => '',
            '1' => 'Quite and Conversational',
            '2' => 'Normal',
            '3' => 'Loud'
        ],
        'order_again' => [
            '0' => '',
            '1' => 'Yes',
            '2' => 'No'
        ],
        'come_back' => [
            '0' => '',
            '1' => 'Yes',
            '2' => 'No'
        ],
        'mystery_meals_zip' => [
            '10001',
            '10011',
            '10018',
            '10019',
            '10036'
        ],
        'address_types_street' => [
            'street_address',
            'route',
            'premise',
            'subpremise',
            'natural_feature',
            'airport',
            'park',
            'establishment'
        ],
        'address_types_neighbourhood' => [
            'neighborhood',
            'neighbourhood',
            'sublocality',
            'sublocality_level_5'
        ],
        'address_types_zip' => [
            'postal_code'
        ],
        'address_types_city' => [
            "locality"
        ],
        'muncher_identifire' => [
            'fu_munchu' => 'fuMunchuMuncher',
            'health_nut' => 'healthNutMuncher',
            'sir_loin' => 'sirLoinMuncher',
            'vip' => 'vipMuncher',
            'home_eater' => 'homeEaterMuncher',
            'takeout_artist' => 'takeoutArtistMuncher',
            'food_pundit' => 'foodPunditMuncher',
            'munch_maven' => 'munchMavenMuncher',
            'cheesy_triangle' => 'cheesyTriangleMuncher',
        ],
        'special_character' => [
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'o',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ñ' => 'n',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            '&Aacute;' => 'A',
            '&aacute;' => 'a',
            '&Eacute;' => 'E',
            '&eacute;' => 'e',
            '&Iacute;' => 'I',
            '&iacute;' => 'i',
            '&Oacute;' => 'O',
            '&oacute;' => 'o',
            '&Ntilde;' => 'N',
            '&ntilde;' => 'n',
            '&Uacute;' => 'U',
            '&uacute;' => 'u',
            '&Uuml;' => 'U',
            '&uuml;' => 'u',
            '&#268' => 'C',
            '&#352;' => 'S',
            '&#381;' => 'Z',
            '&#269;' => 'c',
            '&#353;' => 's',
            '&#382;' => 'z',
            '\u2019s'=> "'s"
        ],
        'redemptionSpecial' => [
            'opennight' => 1,
            'cashback' => 2,
            'hispanicnight' => 3
        ],
        'notEmailRestriction' => [
            'forgot-password',
            'emailawarding',
            'modify-reservation',
            'modify-reservation-to-friends',
            'send-cancel-reservation',
            'send-cancel-reservation-friends',
            'is_buying_food_you_in',
            'friends-reservation-Invitation'],
        'pointEqualDollar'=>[1,0.01],//0 index contain point and 1 index contain dollar
    ],
    'campaigns' => [
            'sweepstakes'=>false,
            'promotion_five_dollar'=>false,            
        ],
    
];