<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Zend\Mvc\MvcEvent;

$constants = isset($constants) ? $constants : function () {
    
};
if (isset($e)) {
    $e = $e ? $e : new MvcEvent();
    $request = $e->getTarget()->getServiceLocator()->get('Request');
    if(!defined('PROTOCOL')){
        if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){
            define('PROTOCOL', sprintf('%s://', $_SERVER['HTTP_X_FORWARDED_PROTO']));    
        }else{
            define('PROTOCOL', sprintf('%s://', $request->getUri()->getScheme()));
        }
    }
    if (!defined('WEB_HOST')) {
        define('WEB_HOST', sprintf(PROTOCOL . $request->getUri()->getHost()));
    }
    define('WEB_IMG_URL', PROTOCOL . $constants('imagehost'));
    define('SITE_URL', $constants('web_url') . '/');
} else if (!defined('PROTOCOL') || !defined('WEB_HOST')) {
    defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
    define("IMAGEHOST","s3.amazonaws.com/munchado/assets/"); 
    define('DS',"/");
    define('BASE_DIR', realpath(__DIR__ . "/../"));
    if (APPLICATION_ENV == 'qc' || APPLICATION_ENV == 'qa') {
        define('PROTOCOL', 'http://');
        define('WEB_HOST', PROTOCOL.'api.munchado.in');        
        define('SITE_URL', "qc.munchado.in" . '/');
    } else if (APPLICATION_ENV == 'local') {
        define('PROTOCOL', 'http://');
        define('WEB_HOST', PROTOCOL.'api.munch-local.com');
        define('SITE_URL', "munchado-local.com" . '/');
        define('SERVER_FOR_SALESMANAGO','Local_User');
    } else if (APPLICATION_ENV == 'demo') {
        define('PROTOCOL', 'https://');
        define('WEB_HOST', PROTOCOL.'demoapi.munchado.com');
        define('SITE_URL', "demo.munchado.com" . '/');
        define('SERVER_FOR_SALESMANAGO','Demo_User');
    } else {
        define('PROTOCOL', 'https://');
        define('WEB_HOST', PROTOCOL.'api.munchado.com');
        define('SITE_URL', "munchado.com" . '/');
        define('SERVER_FOR_SALESMANAGO','Live_User');
    }
    define('WEB_IMG_URL', PROTOCOL . IMAGEHOST);
}

//define('WEB_IMG_URL', PROTOCOL . $constants('imagehost'));
//define('SITE_URL', $constants('web_url') . '/');
define('IMAGE_PATH', WEB_IMG_URL . 'munch_images/');
define("THUMB", "thumb");
define("DASHBOARD_URL", "https://qa.munchado.biz");
define("SEARCH_PAGINATION_LIMIT", 10);
define('WEB_URL', WEB_HOST . '/');
define('WEB_HOST_URL', sprintf(PROTOCOL . SITE_URL));
define('NO_IMAGE_PATH', WEB_URL . 'img/');
define("REST_DEFAULT_IMAGE", NO_IMAGE_PATH . "no_pic.jpg");
define("FOOD", 'food');
define("RESTAURANT", 'restaurant');
define("CUISINE", "cuisine");
define("MAX_IMAGE_UPLOAD_SIZE_LIMIT", 10); // in MBs for image upload size limit
define("APP_PUBLIC_PATH", BASE_DIR . DS . "public" . DS); // appication public path
define("TEMP_USER_MENU_IMG_PATH", "user_images" . DS . "temporary"); // temporary image path
define("PRE_USER_MENU_IMG_PATH", "user_images" . DS . "pre_moderation"); // pre moderation
define("POST_USER_MENU_IMG_PATH", "user_images" . DS . "post_moderation"); // post moderation
define('USER_REVIEW_IMAGE', WEB_URL . 'assets/user_images/'); // user profile image
define('USER_UPLOADED_IMAGE_PROFILE', WEB_URL . "user_images/profile" . DIRECTORY_SEPARATOR); // user profile image
define('GOOGLE_MAP_API', "http://www.geobytes.com/IpLocator.htm"); // geo ip locator url
define("DEFAULT_TIME_ZONE", 'America/Los_Angeles'); // Default Timezone
define('MAIL_IMAGE_PATH', WEB_IMG_URL . 'email_images/');
define('TEMPLATE_IMG_PATH', WEB_URL . 'img/');
define("EMAIL_FROM", "notifications@munchado.com");
define('SENDER_NAME', 'Munch Ado');
define("SUBJECT_INVITE_A_FRIEND", "A Friend Invite you on Munch Ado!");
define('USER_RESTAURANT_IMAGE_DIR', 'user_images' . DS . 'restaurant' . DS);
define("TWITTER_APP_KEY", "7gpJTmHONpbAHkz1hxsIA");
define("TWITTER_SECRET_KEY", "hGJtxZtEMgVHZCNOLvCtxrUwNXWS2vYmCGjkyRvgF0");
define("HOTMAIL_CLIENT_ID", "000000004C10DF8A");
define("HOTMAIL_CLIENT_SECRET", "Y7urzSTmHzXNWOJzTIcq6NstlFvsKKTX");
define("HOTMAIL_SCOPE", "wl.basic wl.emails wl.contacts_emails");
define("HOTMAIL_AUTH_REDIRECT", PROTOCOL . "munch-local.com/wapi/user/microsoftcontact/microsoftauthenticate");
define("SHOW_PER_PAGE", 50);
define("NOTIFICATION_SENDER_EMAIL", 'notifications@munchado.com');
define("NOTIFICATION_SENDER_NAME", "Munch Ado");
define("SMALL_GROUP_VALUE", "5");
define("TIME_INTERVAL", "30");
define("SERVICE_PROVIDER_EMAIL", "dyadav@aydigital.com");
define("CC_EMAIL", serialize(array("sumca2004@gmail.com")));
define("MUNCHADO_ACCOUNT_AT_SERVICE_PROVIDER", "14545");
define("CURRENT_ORDER_PICKUP_TIME", "+15 minutes");
define("PRE_ORDER_PICKUP_TIME", "-30 minutes");
define("SAMPLE_TEXT_FILE", APP_PUBLIC_PATH . DS . "user_images");
define("LOG_DIR", BASE_DIR . DS . "log");
define("LOG_FILE_NAME", LOG_DIR . DS . "munch_api_err.log");
define("USER_RESTAURANT_IMAGE_UPLOAD", BASE_DIR . DS);
define("APPLIED_FINAL_TOTAL", 0.5);
define("USER_IMAGE_UPLOAD", "assets/user_images/");
define("COUPON_SUBSCRIPTION", 0);
define("PROMOCODE_ENDDATE", 'P2D'); //P1M//P2D//PT2H
define("COUNTRY_CODE_US_MOB", '1');
define("USER_IMAGE_WALLPAPER", "assets/user_images/wallpaper");
define("B2B_IMAGE_UPLOAD", "assets/");
define("CRM_CAPPING", false);
define("CRM_OPEN_CLOSE_TIME", "00:00-23:59"); //09:00-22:00//00:00-00:00
define("ORDER_TIME_SLOT", "00-23:59"); //10-22:30//00-00:00
define("PROMOCODE_FIRST_REGISTRATION", 10);
define("EXPIRE_PROMOCODE_REGISTRATION", 48);
define("ASSIGNDOLLAR5PROMO", true);
define("POINT_REDEEM_LIMIT", 100);
define("SMS_NEW_USER", "Welcome to %s Dine & More rewards program presented by Munch Ado!\nVisit %s and start earning points towards irresistible rewards and free food!");
define("SMS_REGISTER_USER", "Welcome to %s Dine & More rewards program!\n\nVisit %s\nand start earning points towards irresistible rewards and free food!");
define("SMS_ERROR_CODE", "Sorry we could not detect a valid email or code. Don't forget to include spaces! Please try again in the following format:\n");
define("SMS_ERROR_REGISTER_ALREADY_WITH_RESTAURANT", "You're already a member of %s Dine & More program.");
define("EARLY_BIRD_SPECIAL_DAYS", 30);
define("SALESMANAGO_OWNER_EMAIL", "no-reply@munchado.com");
define("MUNCHADO_DINE_MORE_CODE", 'M100000');
define("SALESMANGO_LOG_FILE_NAME", LOG_DIR . DS . "salesmango.log");

//Fource update app constant

define("HARD_VERSION_ANDROID",1);
define("HARD_VERSION_IOS","1.4.2");
define("SOFT_VERSION_ANDROID",1);
define("SOFT_VERSION_IOS","0.0.0");
define("COUNTER",3);
define("CLEAR_DATA",false);//true/false (default:false)
define("FOURCE_UPDATE_MESSAGE","Hey Muncher! There is a new version of our app available to make your digital dining experience even better.");


/* * Dashboard constants * */
define("ARIA_ORDER_DELIVER_CONFIRM", "Aria_Order_Up");
define("ARIA_ORDER_TAKEOUT_CONFIRM", "Aria_Order_Up_takeout");
define("ARIA_SUBJECT_CONFIRM_ORDER", "Order Up at %s!");
define("ORDER_SUBMIT_INDIVIDUAL_2", "10_Order-Up");
define("SUBJECT_ORDER_SUBMIT_INDIVIDUAL_2", "Your Order from %s is All Set ");
define("PRE_ORDER_CONFIRM", "pre_order_confirm");
define("SUBJECT_PRE_ORDER_CONFIRM", "Planning Ahead at %s We see");
define("CONFIRM_TACKOUT_ORDER", "10_Order-Up-Takeout");
define("ORDER_CANCEL_INDIVIDUAL", "11_Uh-Oh-No-Food-For-You");
define("ORDER_CANCEL_ARIA", "Aria_Cancel_Order");
define("SUBJECT_ORDER_CANCEL_INDIVIDUAL", "Uh-Oh! No Food For You!");
define('DASHBOARD_EMAIL_FROM', 'Notification@MunchAdo.biz');
define('ARIAHK_REST_CODE', 'RNYMN05746');
define('ARIAWEST_REST_CODE', 'RNYMN0571');

define("READY_TACKOUT_ORDER","Order-Ready");
define("READY_TACKOUT_SUBJECT","Food's Ready at %s!");


define("CONFIRM_RESERVATION","05_Your-Reservation-has-been-Reserved");
define("SUBJECT_CONFIRM_RESERVATION", "We Can Squeeze You In at %s!");
define("CANCEL_RESERVATION", "07_About-That-Meal-You-Reserved");
define("SUBJECT_CANCEL_RESERVATION", "Oh No! No Spot for You!");
/*send  cancel pre order reservation mail to user and friends*/
define("RESERVATION_MODIFICATION", "About_Your_Table_Request_at");
define("SUBJECT_RESERVATION_MODIFICATION", "About Your Table Request at %s…");

define("CONFIRM_RESERVATION_TEMPLATES","Reservation-Confirmation");
define("SUBJECT_RESERVATION_CONFIRM_", "Your Day/Night/Afternoon on the Town is Set at %s");
define("CANCEL_RESERVATION_TEMPLATES", "Reservation-Cancellation");
define("SUBJECT_RESERVATION_CANCEL", "...About That Meal Your Reserved");
/*send  cancel pre order reservation mail to user and friends*/
define("RESERVATION_MODIFICATION_TEMPLATES", "Reservation-Modification");
define("SUBJECT_MODIFICATION_RESERVATION", "Uh-Oh Your Reservation at %s Was Changed");


/** Dashboard forgot password mail constants **/
define("FORGOT_PASSWORD_MAIL", "04_HungryBuzz-Password-Recovery-Squad-Says-Panic!");
define("SUBJECT_FORGOT_PASSWORD_MAIL", "Password Recovery!");

/*Send rebiew response from rasturant to user*/
define("RASTURANT_RESPONSE_ON_REVIEW" ,"20_The_Voice_of_the_Little_Guy_Was_Heard_by_the_Big_Guy");
define("SUBJECT_ON_REVIEW","%s Responded to Your Review");

//Fource update Dashboard app constant
    
define("DASHBOARD_HARD_VERSION_ANDROID","4");
define("DASHBOARD_SOFT_VERSION_ANDROID","4");
define("DASHBOARD_COUNTER",3);
define("DASHBOARD_CLEAR_DATA",false);//true/false (default:false)
define("DASHBOARD_FOURCE_UPDATE_MESSAGE","Hey Muncher! There is a new version of our app available to make your digital dining experience even better.");
define('APK_FILE_PATH', WEB_URL . 'apk/app-release-live-api.apk');
define("DM_REFERRAL_IMAGE", "referral_share.jpg");
define("MUNCHADO_REFERRAL_IMAGE", "fb_share_findwhatyoucrave.jpg");
define("CAREER","assets/");
define("MUNCHADO_HR_EMAIL","crew@munchado.com");
define("BRAVVURA_HR_EMAIL","imagine@Bravvura.com");

// define("MUNCHADO_HR_EMAIL","aydtest@aydigital.in");
// define("BRAVVURA_HR_EMAIL","hrgtest@bravvura.com");
define("DINEANDMORE_POINT",100);
define("REGISTRATION_POINT",100);

define("LOG_FILE_NAME_CLEVER", LOG_DIR . DS ."clevertap.log");
define("WATING_TIME",2);
//B2B template
define('B2B_IMAGE_PATH',WEB_IMG_URL.'db_images/');
define("B2B_SINGUP", "B2B_registration");
define("SUBJECT_B2B_SINGUP", "Welcome To the Munch Ado Platform");
define("B2B_SINGUP_CRM", "B2B_registration_CRM");
define("SUBJECT_B2B_SINGUP_CRM", "New Contract"); //A Restaurant Owner Has Signed Up
define("B2B_SINGUP_CRM_FREELISTING", "B2B_registration_CRM_freelisting");
define("B2B_SINGUP_CRM_LOYALTY", "B2B_registration_CRM_loyalty");
define("B2B_SINGUP_CRM_MARKETING", "B2B_registration_CRM_Marketing");
define("B2B_SINGUP_CRM_ECOMMERCE_199", "B2B_registration_CRM_eCommerce_199");
define("B2B_SINGUP_CRM_ECOMMERCE_99", "B2B_registration_CRM_eCommerce_99");

define("B2B_SINGUP_CRM_SOCIAL_MEDIA", "B2B_registration_CRM_Social_Media");
define("B2B_SINGUP_CRM_ECOMMERCE_99_MARKETING", "B2B_registration_CRM_eCommerce_99_and_Marketing");
define("B2B_SINGUP_CRM_ECOMMERCE_99_SOCIAL", "B2B_registration_CRM_eCommerce_99_and_Social_Media");
define("B2B_SINGUP_CRM_ECOMMERCE_199_MARKETING", "B2B_registration_CRM_eCommerce_199_and_Marketing");
define("B2B_SINGUP_CRM_ECOMMERCE_199_SOCIAL", "B2B_registration_CRM_eCommerce_199_and_Social_Media");
define("B2B_SINGUP_CRM_MARKETING_SOCIAL_MEDIA", "B2B_registration_CRM_Marketing_and_Social_Media");

define("B2B_SINGUP_CRM_FREELISTING_PDF", "B2B_registration_CRM_freelisting_pdf");
define("B2B_SINGUP_CRM_LOYALTY_PDF", "B2B_registration_CRM_loyalty_pdf");
define("B2B_SINGUP_CRM_MARKETING_PDF", "B2B_registration_CRM_Marketing_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_199_PDF", "B2B_registration_CRM_eCommerce_199_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_99_PDF", "B2B_registration_CRM_eCommerce_99_pdf");

define("B2B_SINGUP_CRM_SOCIAL_MEDIA_PDF", "B2B_registration_CRM_Social_Media_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_99_MARKETING_PDF", "B2B_registration_CRM_eCommerce_99_and_Marketing_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_99_SOCIAL_PDF", "B2B_registration_CRM_eCommerce_99_and_Social_Media_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_199_MARKETING_PDF", "B2B_registration_CRM_eCommerce_199_and_Marketing_pdf");
define("B2B_SINGUP_CRM_ECOMMERCE_199_SOCIAL_PDF", "B2B_registration_CRM_eCommerce_199_and_Social_Media_pdf");
define("B2B_SINGUP_CRM_MARKETING_SOCIAL_MEDIA_PDF", "B2B_registration_CRM_Marketing_and_Social_Media_pdf");