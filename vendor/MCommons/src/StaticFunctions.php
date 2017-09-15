<?php

namespace MCommons;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Adapter\Adapter;
use Restaurant\Model\Restaurant;
use Zend\Db\Sql\Select;
use Home\Model\City;
use Zend\Db\Sql\Where;
use Restaurant\Model\Calendar;
use Auth\Model\UserSession;
use Zend\Filter\File\RenameUpload;
use TwitterOAuth;
use MCommons\Message;
use Zend\View\Model\ViewModel;
use Restaurant\RestaurantDetailsFunctions;
use Zend\View\Renderer\PhpRenderer;
use Zend\Mime\Mime;
use Zend\Mime\Part;
use Zend\Mime\Message as MimeMessage;
use Zend\Mail\Transport\Smtp;

class StaticFunctions {

    protected static $_serviceLocator;
    protected static $_db_read_adapter;
    protected static $_db_write_adapter;
    protected static $_date_time_zone;
    protected static $_date_time;
    private static $_cache = [];
    private static $_user_session = false;
    public static $inactive = 0;
    public static $upcoming = 1;
    public static $canceled = 2;
    public static $rejected = 3;
    public static $confirmed = 4;
    public static $city_id = 0;
    public static $_userAgent = false;
    public static $_dashboardToken = false;
    private static $_dashboard_session = false;
    public static $careerFile = [];
    private static $_email_sent_success = 1;
    public static $_allowedImageTypes = array(
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'jpg' => 'image/jpg'
    );
    public static $_allowedCareerTypes = array(
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
    );
    public static $captcha_array = array(
        0 => array(
            "captcha_id" => 1,
            "captcha_key" => "chicken",
            "captcha_title" => "Place the <b>chicken</b> below on the plate to the side to prove you are not a string of 1s and 0s.",
            "captcha_icon" => "capa1-icon"
        ),
        1 => array(
            "captcha_id" => 2,
            "captcha_key" => "pizza",
            "captcha_title" => "Place the <b>pizza</b> below on the plate to the side to prove you are not a string of 1s and 0s.",
            "captcha_icon" => "capa3-icon"
        ),
        2 => array(
            "captcha_id" => 3,
            "captcha_key" => "burger",
            "captcha_title" => "Place the <b>burger</b> below on the plate to the side to prove you are not a string of 1s and 0s.",
            "captcha_icon" => "capa2-icon"
        ),
        3 => array(
            "captcha_id" => 4,
            "captcha_key" => "sushi",
            "captcha_title" => "Place the <b>sushi</b> below on the plate to the side to prove you are not a string of 1s and 0s.",
            "captcha_icon" => "capa4-icon"
        )
    );
    public static $review_for = array(
        '1' => 'delivery',
        '2' => 'takeout',
        '3' => 'dine_in'
    );
    public static $temp_food = array(
        '1' => 'too_cold',
        '2' => 'just_right',
        '3' => 'too_hot'
    );
    public static $taste_test = array(
        '1' => 'horiable',
        '2' => 'ok but could be better',
        '3' => 'loved it'
    );
    public static $services = array(
        '1' => 'extremly nice',
        '2' => 'just right',
        '3' => 'Un-acceptiable Unfriendly'
    );
    public static $noise_level = array(
        '1' => 'quit and conversational',
        '2' => 'normal',
        '3' => 'loud'
    );
    public static $status = array(
        '0' => 'new',
        '1' => 'approved',
        '2' => 'disapproved',
        '3' => 'deleted'
    );
    public static $on_time = array(
        '0' => 'yes',
        '1' => 'no'
    );
    public static $fresh_prepared = array(
        '0' => 'yes',
        '1' => 'no'
    );
    public static $as_specifications = array(
        '0' => 'yes',
        '1' => 'no'
    );
    public static $order_again = array(
        '0' => 'yes',
        '1' => 'no'
    );
    public static $come_back = array(
        '0' => 'yes',
        '1' => 'no'
    );
    public static $liked = array(
        '0' => 'No',
        '1' => 'Yes',
        'null' => 'did not click'
    );
    public static $notification_type = array(
        '1' => 'order',
        '2' => 'group order',
        '3' => 'reservation',
        '4' => 'reviews'
    );
    public static $book_mark_types = array(
        "lo",
        "ti",
        "wi"
    );
    public static $review_text = array(
        "1" => "delivery",
        "2" => "takeout",
        "3" => "dine-in"
    );
    public static $order_address_type = array(
        "b" => "billing",
        "s" => "shipping"
    );
    public static $cusine_in_order = array(
        'Americas' => 1,
        'Asia' => 2,
        'Europe' => 3,
        'Africa' => 4,
        'Australia' => 5
    );
    public static $priceMap = array(
        'any' => 5,
        '$' => 1,
        '$$' => 2,
        '$$$' => 3,
        '$$$$' => 4
    );
    public static $days = array(
        1 => 'mo',
        2 => 'tu',
        3 => 'we',
        4 => 'th',
        5 => 'fr',
        6 => 'sa',
        7 => 'su'
    );
    public static $dayMapping = array(
        'mo' => 'Mon',
        'tu' => 'Tue',
        'we' => 'Wed',
        'th' => 'Thu',
        'fr' => 'Fri',
        'sa' => 'Sat',
        'su' => 'Sun'
    );
    public static $points_source = array(
        1 => 'Order placed/Takeout',
        2 => 'Group order placed',
        3 => 'Reserve a table',
        4 => 'Purchase a Deal/Coupon',
        5 => 'Invite Friends',
        6 => 'Rate & Review',
        7 => 'Post pictures',
        8 => 'Report errors',
        9 => 'Complete profile',
        10 => 'Post on Facebook',
        11 => 'Post on Twitter'
    );
    public static $timeSlots = array(
        '00:00',
        '00:30',
        '01:00',
        '01:30',
        '02:00',
        '02:30',
        '03:00',
        '03:30',
        '04:00',
        '04:30',
        '05:00',
        '05:30',
        '06:00',
        '06:30',
        '07:00',
        '07:30',
        '08:00',
        '08:30',
        '09:00',
        '09:30',
        '10:00',
        '10:30',
        '11:00',
        '11:30',
        '12:00',
        '12:30',
        '13:00',
        '13:30',
        '14:00',
        '14:30',
        '15:00',
        '15:30',
        '16:00',
        '16:30',
        '17:00',
        '17:30',
        '18:00',
        '18:30',
        '19:00',
        '19:30',
        '20:00',
        '20:30',
        '21:00',
        '21:30',
        '22:00',
        '22:30',
        '23:00',
        '23:30'
    );
    public static $AriatimeSlots = array(
        '00:00',
        '00:30',
        '01:00',
        '01:30',
        '02:00',
        '02:30',
        '03:00',
        '03:30',
        '04:00',
        '04:30',
        '05:00',
        '05:30',
        '06:00',
        '06:30',
        '07:00',
        '07:30',
        '08:00',
        '08:30',
        '09:00',
        '09:30',
        '10:00',
        '10:30',
        '11:00',
        '11:30',
        '12:00',
        '12:30',
        '13:00',
        '13:30',
        '14:00',
        '14:30',
        '15:00',
        '15:30',
        '16:00',
        '16:30',
        '17:00',
        '17:30',
        '18:00',
        '18:30',
        '21:00',
        '21:30',
        '22:00',
        '22:30',
        '23:00',
        '23:30'
    );
    private static $next_slot_time_diff = '30 minutes';
    private static $next_day_slots_startTime = '23:00:00';

    const MYSQL_DATE_FORMAT = 'Y-m-d H:i:s';
    const COOKIE = "D, d M Y H:i:s T";

    public static function expiry_time() {
        return time() + (48 * 3600);
    }

    /**
     * Generate the desired output string
     *
     * @param string $time            
     * @param string $format            
     * @param string $outputFormat            
     * @return string
     */
    public static function getFormattedDateTime($time, $format, $outputFormat) {

        if (empty($time)) {
            return '';
        }
        try {
            if ($format != null && $format != null && $outputFormat != null) {
                $dateTime = \DateTime::createFromFormat($format, $time);
                return $dateTime->format($outputFormat);
            } else {
                return '';
            }
        } catch (\Exception $ex) {
            return '';
        }
    }

    /**
     *
     * @var reservation mapped party array
     */
    public static $reservation_party_arr = array(
        "1" => "1 A Me Party",
        "2" => "2 A One-on-one",
        "3" => "3 The Three Amigos",
        "4" => "4 A Quartet",
        "5" => "5 Party of 5",
        "6" => "6 The Six Flag Bearers",
        "7" => "7 The Magnificent 7",
        "8" => "8 The Eight Days of the Week",
        "9" => "9 The 9 out of 10",
        "10" => "10 A Small Village",
        "11" => "11 The 11 Piping Pipers",
        "12" => "12 A Dozen",
        "13" => "13 A Baker’s Dozen",
        "14" => "14 A Bad Baker’s Dozen",
        "15" => "15 A Medium Village",
        "16" => "16 The Sweet 16",
        "17" => "17 The Sexy 17",
        "18" => "18 The 18% Tippers",
        "19" => "19 The Prime 19",
        "20" => "20 A Standard Village",
        "21" => "21+ Larger Party"
    );

    public static function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
        static::$_serviceLocator = $serviceLocator;
    }

    public static function getServiceLocator() {
        if (!static::$_serviceLocator) {
            throw new \Exception("Unable to get service locator. Please set the instance of service locator first");
        }
        return static::$_serviceLocator;
    }

    public static function setUserAgent($userAgent) {

        if (!empty($userAgent->getFieldValue())) {
            if (preg_match('/Mozilla/i', $userAgent->getFieldValue()) || preg_match('/Chrome/i', $userAgent->getFieldValue()) || preg_match('/Safari/i', $userAgent->getFieldValue())) {
                static:: $_userAgent = "ws";
            } elseif (preg_match('/Android/i', $userAgent->getFieldValue())) {
                static:: $_userAgent = "android";
            } elseif (preg_match('/iOS/i', $userAgent->getFieldValue())) {
                static:: $_userAgent = "iOS";
            } else {
                static:: $_userAgent = "ws";
            }
        } else {
            static:: $_userAgent = "ws";
        }
    }

    public static function getUserAgent() {
        if (!static:: $_userAgent) {
            throw new \Exception("Unable to get user agent. Please set the instance of header for user agent first", 400);
        }
        //pr(static:: $_userAgent,1);
        return static:: $_userAgent;
    }

    public static function setDashboardToken($e) {
        $serverData = $e->getRequest()->getServer()->toArray();
        $queryParams = $e->getRequest()->getQuery()->toArray();
        $potRequest = $e->getRequest()->getPost()->toArray();

        if (isset($token['HTTP_TOKEN'])) {
            static:: $_dashboardToken = $token['HTTP_TOKEN'];
        } elseif (isset($queryParams['token'])) {
            static:: $_dashboardToken = $queryParams['token'];
        } elseif (isset($potRequest['token'])) {
            static:: $_dashboardToken = $potRequest['token'];
        } else {
            $tokenTime = microtime();
            $salt = "Munc!";
            $token = md5($salt . $tokenTime);
            static:: $_dashboardToken = $token;
        }
    }

    public static function getDashboardToken() {
        if (!static:: $_dashboardToken) {
            throw new \Exception("Unable to get token.");
        }
        //pr(static:: $_dashboardToken,1);
        return static:: $_dashboardToken;
    }

    public static function setDbReadAdapter(\Zend\Db\Adapter\AdapterServiceFactory $readAdapter) {
        pr($readAdapter, 1);
        static::$_db_read_adapter = $readAdapter;
    }

    public static function getDbReadAdapter() {

        if (!static::$_db_read_adapter) {
            static::setDbReadAdapter(static::getServiceLocator()->get('RestFunction\Db\Adapter\ReadAdapter'));
        }
        return static::$_db_read_adapter;
    }

    public static function setDbWriteAdapter(Adapter $writeAdapter) {
        static::$_db_write_adapter = $writeAdapter;
    }

    public static function getDbWriteAdapter() {
        if (!static::$_db_write_adapter) {
            static::setDbWriteAdapter(static::getServiceLocator()->get('RestFunction\Db\Adapter\WriteAdapter'));
        }
        return static::$_db_write_adapter;
    }

    public static function getDateTimeZone() {
        if (!static::$_date_time_zone) {
            static::setDateTimeZone();
        }
        return static::$_date_time_zone;
    }

    public static function setDateTimeZone($dateTimeZoneText = null) {
        $defaultDateTimeZoneText = date_default_timezone_get();
        if (!$dateTimeZoneText || !is_string($dateTimeZoneText)) {
            $dateTimeZoneText = $defaultDateTimeZoneText;
        }
        try {
            $dateTimeZone = new \DateTimeZone($dateTimeZoneText);
        } catch (\Exception $ex) {
            $dateTimeZone = new \DateTimeZone($defaultDateTimeZoneText);
        }
        static::$_date_time_zone = $dateTimeZone;
    }

    public static function getDateTime() {
        if (!static::$_date_time) {
            static::setDateTime();
        }
        return static::$_date_time;
    }

    public static function setDateTime($time = 'now', \DateTimeZone $dateTimeZone = null) {
        if (!$dateTimeZone) {
            $dateTimeZone = static::getDateTimeZone();
        }
        if (!$time) {
            throw new \Exception("Invalid Time Provided");
        }
        static::$_date_time = new \DateTime($time, $dateTimeZone);
    }

    /**
     * Get the relative datetime zone of with city code or timezone
     *
     * @param string|array $city
     *            (array : 'code','time_zone')
     * @param string $dateTime            
     * @param string $format            
     */
    public static function getRelativeCityDateTime(array $options = array(), $dateTime = 'now', $format = '') {
        $cityTimeZone = static::getTimeZoneMapped($options);
        if (strtolower($dateTime) == 'now') {
            $dateTime = new \DateTime();
            return $dateTime->setTimezone(new \DateTimeZone($cityTimeZone));
        }
        $dateTimeObject = \DateTime::createFromFormat($format, $dateTime, static::getDateTimeZone());
        return $dateTimeObject->setTimezone(new \DateTimeZone($cityTimeZone));
    }

    public static function getAbsoluteCityDateTime(array $options = array(), $dateTime = 'now', $format = '') {
        $cityTimeZone = static::getTimeZoneMapped($options);
        return $dateTimeObject = \DateTime::createFromFormat($format, $dateTime, new \DateTimeZone($cityTimeZone));
    }

    public static function convertToDateTime(array $options = array(), \DateTime $dateTime) {
        $cityTimeZone = static::getTimeZoneMapped($options);
        return $dateTime->setTimezone(new \DateTimeZone($cityTimeZone));
    }

    /**
     * Get time zone mapping according to restaurant_id, state_code, state_timezone
     *
     * @param array $options            
     * @throws \Exception
     * @return string TimeZoneText
     */
    public static function getTimeZoneMapped(array $options = []) {
        $sl = static::getServiceLocator();
        if (isset($options['state_timezone']) && $options['state_timezone']) {
            return $options['state_timezone'];
        } else
        if (isset($options['state_code']) && $options['state_code']) {
            if (isset(static::$_cache['state_code_timezone']) && static::$_cache['state_code_timezone']) {
                return static::$_cache['state_code_timezone'];
            }

            // Get time zoen with state code
            $city = $sl->get(\City\Model\City::class);
            $where = new Where();
            $where->notEqualTo('time_zone', '');
            $where->isNotNull('time_zone');
            $where->equalTo('state_code', $options['state_code']);
            $timeZoneResult = $city->getCity(array(
                        'columns' => array(
                            'time_zone'
                        ),
                        'where' => $where,
                        'limit' => 1
                    ))->current();
            //var_dump($timeZoneResult);
            if ($timeZoneResult) {
                static::$_cache['state_code_timezone'] = $timeZoneResult->offsetGet('time_zone');
                return static::$_cache['state_code_timezone'];
            }
        } else
        if (isset($options['restaurant_id']) && $options['restaurant_id']) {
            if (isset(static::$_cache['restaurant_id_timezone']) && static::$_cache['restaurant_id_timezone']) {
                return static::$_cache['restaurant_id_timezone'];
            }
            $resturantModel = self::getServiceLocator()->get(Restaurant::class);
            $timeZoneResult = $resturantModel->getTimeZoneResult($options['restaurant_id']);

            if ($timeZoneResult) {
                static::$city_id = $timeZoneResult->offsetGet('city_id');
                static::$_cache['restaurant_id_timezone'] = $timeZoneResult->offsetGet('time_zone');
                return static::$_cache['restaurant_id_timezone'];
            }
        }
        throw new \Exception('Invalid Options for DateTime Please provide restaurant_id or state_code or state_timezone', 500);
    }

    public static function getFormatter() {
        $sl = static::getServiceLocator();
        $routeMatch = $sl->get('Application')
                ->getMvcEvent()
                ->getRouteMatch();
        $config = $sl->get('config');
        try {
            if (isset($config['api_standards'])) {
                // Get api standards decided
                $apiStandards = $config['api_standards'];

                // Get default formatter text or set it to "formatter"
                $formatterText = isset($apiStandards['formatter_text']) ? $apiStandards['formatter_text'] : "formatter";

                // Set default formatter type from api_standards or set it default to JSON
                $defaultFormatter = isset($apiStandards['default_formatter']) ? $apiStandards['default_formatter'] : "json";

                // Get the formatter from query
                $params = $sl->get('request')
                        ->getQuery()
                        ->getArrayCopy();
                $formatter = isset($params[$formatterText]) ? $params[$formatterText] : $defaultFormatter;
            } else {
                throw new \Exception("Invalid Parameters");
            }
        } catch (\Exception $ex) {
            // On any exception set the formatter to the json
            $formatter = "json";
        }
        return $formatter;
        // $request->get
    }

    /**
     * Generate appropriate response with variables and response code
     *
     * @param ServiceLocatorInterface $sl            
     * @param array $vars            
     * @param number $response_code            
     * @return \Zend\Http\PhpEnvironment\Response $response
     */
    public static function getResponse(ServiceLocatorInterface $sl, array $vars = array(), $response_code = 200) {
        /**
         *
         * @var \Zend\Di\Di $di
         */
        $di = $sl->get('Di');
        /**
         *
         * @var array $configuration
         */
        $configuration = $sl->get('config');
        /**
         *
         * @var PostProcessor\AbstractPostProcessor $postProcessor
         */
        $formatter = StaticFunctions::getFormatter();

        $response = $sl->get('response');

        $postProcessor = $di->get(\Rest\Processors\Json::class, array(
            'vars' => $vars,
            'response' => $response
        ));

        $response->setStatusCode($response_code);
        $postProcessor->process();
        $response = $postProcessor->getResponse();
        return $response;
    }

    public static function getErrorResponse(ServiceLocatorInterface $sl, $message = 'Error Occured', $response_code = 500) {
        /**
         *
         * @var \Zend\Di\Di $di
         */
        $di = $sl->get('di');

        /**
         *
         * @var array $configuration
         */
        $configuration = $sl->get('config');

        /**
         *
         * @var PostProcessor\AbstractPostProcessor $postProcessor
         */
        $formatter = StaticFunctions::getFormatter();
        $response = $sl->get('response');

        $request = $sl->get('request');
        $requestType = (bool) $request->getQuery('mob', false) ? 'mobile' : 'web';

        $vars = StaticFunctions::formatResponse(array(
                    'error' => $message
                        ), $response_code, $message, $requestType);
        $postProcessor = $di->get($formatter . "_processor", array(
            'vars' => $vars,
            'response' => $response
        ));

        $response->setStatusCode($response_code);
        $postProcessor->process();
        $response = $postProcessor->getResponse();
        return $response;
    }

    public static function formatResponse(array $vars = array(), $status_code = 200, $message = 'Success', $device = 'web', $isTokenValid = true) {

        $device = strtolower($device . '');
        $formattedResponse = $vars;
        $config = self::getServiceLocator()->get('config');

        $base_url = $config['constants']['protocol'] . "://" . $config['constants']['imagehost'];

        //if (self::getUserAgent()!="ws") {
        if ($device == "mobile") {
            $formattedResponse = array(
                'result' => false,
                'message' => $message,
                'is_token_valid' => true,
                'base_url' => $base_url,
                "deeplink_url" => PROTOCOL . $config['constants']['web_url'],
                'data' => array()
            );
            if ($status_code == 200) {
                $formattedResponse['result'] = (bool) count($vars);
                $formattedResponse['data'] = $vars;
            } else {
                if (!$message) {
                    $formattedResponse['message'] = 'An Error Occured';
                }
                $formattedResponse['result'] = (false);
            }
            if (!$isTokenValid) {
                $formattedResponse['is_token_valid'] = false;
            }
        }
        return $formattedResponse;
    }

    public static function getExpiryHeaders() {
        $headers = new \Zend\Http\Headers();
        $headers->addHeaders(array(
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'
        ));
        return $headers;
    }

    /**
     * Generate time slots available for given date
     *
     * @param var $date
     *            = date for which slots are required
     * @param var $state_code
     *            = state code for which slots are required
     * @return \Zend\Http\PhpEnvironment\Response $response = array of timeslots
     */
    public static function getAllTimeSlots($date = '', $state_code) {
        if ($date == '') {
            return array();
        }
        $dateObject = static::getRelativeCityDateTime(array(
                    'state_code' => $state_code
        ));
        $todayDate = $dateObject->format('Y-m-d');
        $timeSlots = static::$timeSlots;
        if (strtotime($date) < strtotime($todayDate)) {
            return 0;
        } else
        if (strtotime($date) > strtotime($todayDate)) {
            $slots = array(
                'day' => 'today',
                'date' => $date,
                'slots' => $timeSlots
            );
        } else {
            $currentTime = $dateObject->format('H:i:s');
            if (strtotime($currentTime) < strtotime(static::$next_day_slots_startTime)) {
                foreach ($timeSlots as $key => $slot) {
                    if (strtotime($currentTime . ' + ' . static::$next_slot_time_diff) > strtotime($slot)) {
                        unset($timeSlots[$key]);
                    }
                }
                $slots = array(
                    'day' => 'today',
                    'date' => $date,
                    'slots' => $timeSlots
                );
            } else {
                $dateObject = $dateObject->add(new \DateInterval('P1D'));
                $nextDate = $dateObject->format('Y-m-d');
                $slots = array(
                    'day' => 'tomorrow',
                    'date' => $nextDate,
                    'slots' => $timeSlots
                );
            }
        }
        return $slots;
    }

    public static function getAllTimeSlotsReservation($date = '', $state_code) {
        if ($date == '') {
            return array();
        }
        $dateObject = static::getRelativeCityDateTime(array(
                    'state_code' => $state_code
        ));
        $todayDate = $dateObject->format('Y-m-d');
        $timeSlots = static::$timeSlots;
        if (strtotime($date) < strtotime($todayDate)) {
            return 0;
        } else
        if (strtotime($date) > strtotime($todayDate)) {
            $slots = array(
                'day' => 'today',
                'date' => $date,
                'slots' => $timeSlots
            );
        } else {
            $currentTime = $dateObject->format('H:i:s');
            if (strtotime($currentTime) < strtotime(static::$next_day_slots_startTime)) {
                foreach ($timeSlots as $key => $slot) {
                    if (strtotime($currentTime) > strtotime($slot)) {
                        unset($timeSlots[$key]);
                    }
                }
                $slots = array(
                    'day' => 'today',
                    'date' => $date,
                    'slots' => $timeSlots
                );
            } else {
                $dateObject = $dateObject->add(new \DateInterval('P1D'));
                $nextDate = $dateObject->format('Y-m-d');
                $slots = array(
                    'day' => 'tomorrow',
                    'date' => $nextDate,
                    'slots' => $timeSlots
                );
            }
        }
        return $slots;
    }

    /**
     * Generate time slots available for a restaurant on given date
     *
     * @param var $restaurant_id
     *            = id for restaurant
     * @param var $date
     *            = date for which slots are required
     * @return \Zend\Http\PhpEnvironment\Response $response = array of timeslots
     */
    public static function getRestaurantTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }
        $operationSlot = array();
        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);

        $inputDay = $inputDate->format('D');
        $mappedDay = array_flip(static::$dayMapping);
        $DayAbbr = $mappedDay[$inputDay];

        $options['where']['calendar_day'] = $DayAbbr;
        $options['where']['restaurant_id'] = $restaurant_id;
        $restaurantOperationHours = $calendar->getRestaurantOperationHours($options, $restaurant_id);
        $restaurantOperationCnt = count($restaurantOperationHours);

        $openTime1 = $closeTime1 = $openTime2 = $closeTime2 = false;
        if (isset($restaurantOperationHours['time1'])) {
            $openTime1 = $restaurantOperationHours['time1']['open_time'];
            $closeTime1 = $restaurantOperationHours['time1']['close_time'];
            $openDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime1, 'H:i:s');
            $closeDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime1, 'H:i:s');
        }
        if (isset($restaurantOperationHours['time2'])) {
            $openTime2 = $restaurantOperationHours['time2']['open_time'];
            $closeTime2 = $restaurantOperationHours['time2']['close_time'];

            $openDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime2, 'H:i:s');
            $closeDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime2, 'H:i:s');
        }
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();

        foreach ($timeSlots as $key => $slot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $slot, 'H:i');
            if ($openTime1 && $closeTime1 && $slotDateTime >= $openDateTime1 && $slotDateTime < $closeDateTime1) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
            if ($openTime2 && $closeTime2 && $slotDateTime >= $openDateTime2 && $slotDateTime < $closeDateTime2) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
        }

        $currdateObject = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));

        foreach ($opentimeSlot as $key => &$timeslot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $timeslot['time'], 'Y-m-d H:i');
            if ($slotDateTime < $currdateObject) {
                $timeslot['status'] = 0;
            }
            $timeslot['date'] = $inputDate->format('Y-m-d');
            $timeslot['time'] = $slotDateTime->format($output_datetime_format);
        }
        return $opentimeSlot;
    }

    public static function getRestaurantOrderTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $currDateTime->add(new \DateInterval('PT30M'));

        $slots = $calendar->getOrderOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];

        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }

        $opentimeSlot = array();
        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format('H:i'),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }
            $opentimeSlot[] = $sArray;
        }
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function getRestaurantTakeoutTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $currDateTime->add(new \DateInterval('PT30M'));

        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];

        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if (isset($ocSlots['day']) && $ocSlots['day'] == 'yesterday') {
                        if ($slotDateTime >= $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                            $finalSlots[] = $slotDateTime;
                        }
                    } else {
                        if ($slotDateTime > $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                            $finalSlots[] = $slotDateTime;
                        }
                    }
                }
            }
        }

        $opentimeSlot = array();
        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format('H:i'),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }
            $opentimeSlot[] = $sArray;
        }
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function getRestaurantReservationTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));

        $nextDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->add(new \DateInterval('P1D'));
        $nextDate = $nextDateTime->format('Y-m-d');

        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];
        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime < $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }

        $opentimeSlot = array();
        $orderOpenClose = explode("-", ORDER_TIME_SLOT);
        $crmOpenClose = explode("-", CRM_OPEN_CLOSE_TIME);
        $crmOpenTime = strtotime($inputDate->format('Y-m-d') . " " . $orderOpenClose[0] . ":00");
        $crmCloseTime = strtotime($inputDate->format('Y-m-d') . " " . $crmOpenClose[1]);

        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format($output_datetime_format),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }


            $slotDateTime = strtotime($inputDate->format('Y-m-d') . " " . $sArray['slot']);

            if (strtotime($inputDate->format('Y-m-d')) == strtotime($nextDate)) {
                if (strtotime($currDateTime->format('Y-m-d H:i')) > strtotime($currDateTime->format('Y-m-d') . " " . $crmOpenClose[1])) {
                    if ($slotDateTime >= $crmOpenTime) {
                        $opentimeSlot[] = $sArray;
                    }
                } elseif (strtotime($currDateTime->format('Y-m-d H:i')) < strtotime($currDateTime->format('Y-m-d') . " " . $crmOpenClose[1])) {
                    $opentimeSlot[] = $sArray;
                }
            } elseif (strtotime($inputDate->format('Y-m-d')) == strtotime($currDateTime->format('Y-m-d')) && strtotime($currDateTime->format('Y-m-d H:i')) > $crmCloseTime) {

                if ($slotDateTime >= $crmOpenTime && $slotDateTime <= $crmCloseTime) {
                    $opentimeSlot[] = $sArray;
                }
            } else {
                if (strtotime($inputDate->format('Y-m-d')) == strtotime($currDateTime->format('Y-m-d')) && strtotime($currDateTime->format('Y-m-d H:i')) < $crmOpenTime) {
                    if ($slotDateTime >= $crmOpenTime) {
                        $opentimeSlot[] = $sArray;
                    }
                } else {
                    $opentimeSlot[] = $sArray;
                }
            }
        }

        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function getRestaurantBreakfastReservationTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }
        $operationSlot = array();
        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $inputDateText = $inputDate->format('Y-m-d');
        $inputDay = $inputDate->format('D');
        $mappedDay = array_flip(static::$dayMapping);
        $DayAbbr = $mappedDay[$inputDay];

        $options['where']['calendar_day'] = $DayAbbr;
        $options['where']['restaurant_id'] = $restaurant_id;
        $restaurantOperationHours = $calendar->getRestaurantBreakfastOperationHours($options, $restaurant_id);
        $restaurantOperationCnt = count($restaurantOperationHours);

        $openTime1 = $closeTime1 = $openTime2 = $closeTime2 = false;
        if (isset($restaurantOperationHours['time1'])) {
            $openTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['breakfast_start_time'];
            $closeTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['breakfast_end_time'];
            $openDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime1, 'Y-m-d H:i:s');
            $closeDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime1, 'Y-m-d H:i:s');
        }
        if (isset($restaurantOperationHours['time2'])) {
            $openTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['breakfast_start_time'];
            $closeTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['breakfast_end_time'];

            $openDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime2, 'Y-m-d H:i:s');
            $closeDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime2, 'Y-m-d H:i:s');
        }
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();

        foreach ($timeSlots as $key => $slot) {
            $dateTimeSlot = $inputDateText . " " . $slot;
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $dateTimeSlot, 'Y-m-d H:i');
            if ($openTime1 && $closeTime1 && $slotDateTime >= $openDateTime1 && $slotDateTime < $closeDateTime1) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
            if ($openTime2 && $closeTime2 && $slotDateTime >= $openDateTime2 && $slotDateTime < $closeDateTime2) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
        }
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        foreach ($opentimeSlot as $key => &$timeslot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDateText . " " . $timeslot['time'], 'Y-m-d H:i');
            if ($slotDateTime < $inputDate || $slotDateTime < $currDateTime) {
                $timeslot['status'] = 0;
            }
            $timeslot['date'] = $inputDate->format('Y-m-d');
            $timeslot['time'] = $slotDateTime->format($output_datetime_format);
        }
        return $opentimeSlot;
    }

    public static function getRestaurantLunchReservationTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }
        $operationSlot = array();
        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $inputDateText = $inputDate->format('Y-m-d');
        $inputDay = $inputDate->format('D');
        $mappedDay = array_flip(static::$dayMapping);
        $DayAbbr = $mappedDay[$inputDay];

        $options['where']['calendar_day'] = $DayAbbr;
        $options['where']['restaurant_id'] = $restaurant_id;
        $restaurantOperationHours = $calendar->getRestaurantLunchOperationHours($options, $restaurant_id);
        $restaurantOperationCnt = count($restaurantOperationHours);

        $openTime1 = $closeTime1 = $openTime2 = $closeTime2 = false;
        if (isset($restaurantOperationHours['time1'])) {
            $openTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['lunch_start_time'];
            $closeTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['lunch_end_time'];
            $openDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime1, 'Y-m-d H:i:s');
            $closeDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime1, 'Y-m-d H:i:s');
        }
        if (isset($restaurantOperationHours['time2'])) {
            $openTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['lunch_start_time'];
            $closeTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['lunch_end_time'];

            $openDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime2, 'Y-m-d H:i:s');
            $closeDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime2, 'Y-m-d H:i:s');
        }
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();

        foreach ($timeSlots as $key => $slot) {
            $dateTimeSlot = $inputDateText . " " . $slot;
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $dateTimeSlot, 'Y-m-d H:i');
            if ($openTime1 && $closeTime1 && $slotDateTime >= $openDateTime1 && $slotDateTime < $closeDateTime1) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
            if ($openTime2 && $closeTime2 && $slotDateTime >= $openDateTime2 && $slotDateTime < $closeDateTime2) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
        }
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        foreach ($opentimeSlot as $key => &$timeslot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDateText . " " . $timeslot['time'], 'Y-m-d H:i');
            if ($slotDateTime < $inputDate || $slotDateTime < $currDateTime) {
                $timeslot['status'] = 0;
            }
            $timeslot['date'] = $inputDate->format('Y-m-d');
            $timeslot['time'] = $slotDateTime->format($output_datetime_format);
        }
        return $opentimeSlot;
    }

    public static function getRestaurantDinnerReservationTimeSlots($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }
        $operationSlot = array();
        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $inputDateText = $inputDate->format('Y-m-d');
        $inputDay = $inputDate->format('D');
        $mappedDay = array_flip(static::$dayMapping);
        $DayAbbr = $mappedDay[$inputDay];

        $options['where']['calendar_day'] = $DayAbbr;
        $options['where']['restaurant_id'] = $restaurant_id;
        $restaurantOperationHours = $calendar->getRestaurantDinnerOperationHours($options, $restaurant_id);
        $restaurantOperationCnt = count($restaurantOperationHours);

        $openTime1 = $closeTime1 = $openTime2 = $closeTime2 = false;
        if (isset($restaurantOperationHours['time1'])) {
            $openTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['dinner_start_time'];
            $closeTime1 = $inputDateText . " " . $restaurantOperationHours['time1']['dinner_end_time'];
            $openDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime1, 'Y-m-d H:i:s');
            $closeDateTime1 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime1, 'Y-m-d H:i:s');
        }
        if (isset($restaurantOperationHours['time2'])) {
            $openTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['dinner_start_time'];
            $closeTime2 = $inputDateText . " " . $restaurantOperationHours['time2']['dinner_end_time'];

            $openDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $openTime2, 'Y-m-d H:i:s');
            $closeDateTime2 = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $closeTime2, 'Y-m-d H:i:s');
        }
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();

        foreach ($timeSlots as $key => $slot) {
            $dateTimeSlot = $inputDateText . " " . $slot;
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $dateTimeSlot, 'Y-m-d H:i');
            if ($openTime1 && $closeTime1 && $slotDateTime >= $openDateTime1 && $slotDateTime < $closeDateTime1) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
            if ($openTime2 && $closeTime2 && $slotDateTime >= $openDateTime2 && $slotDateTime < $closeDateTime2) {
                $opentimeSlot[] = array(
                    'status' => 1,
                    'time' => $slot
                );
            }
        }
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        foreach ($opentimeSlot as $key => &$timeslot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDateText . " " . $timeslot['time'], 'Y-m-d H:i');
            if ($slotDateTime < $inputDate || $slotDateTime < $currDateTime) {
                $timeslot['status'] = 0;
            }
            $timeslot['date'] = $inputDate->format('Y-m-d');
            $timeslot['time'] = $slotDateTime->format($output_datetime_format);
        }
        return $opentimeSlot;
    }

    public static function setUserSession(UserSession $userSession) {
        static::$_user_session = $userSession;
    }

    public static function setDashboardSession(DashboardSession $DashboardSession) {
        static::$_dashboard_session = $dashboardSession;
    }

    /**
     *
     * @throws \Exception
     * @return \User\Model\User
     */
    public static function getUserSession() {
        if (!static::$_user_session) {
            throw new \Exception('User session not set.');
        }
        return static::$_user_session;
    }

    public static function getDashboardSession() {
        if (!static::$_dashboard_session) {
            throw new \Exception('Dashbord session not set.');
        }
        return static::$_dashboard_session;
    }

    /**
     * File validations (type, size, error)
     *
     * @param array $file            
     * @return array
     */
    public static function validateImage($file) {
        $mTypes = self::$_allowedImageTypes;
        if (empty($file['type']) || $file['type'] == null) {
            $val_response = array(
                'status' => false,
                'message' => 'File size exceeded, it should be upto ' . MAX_IMAGE_UPLOAD_SIZE_LIMIT . "MB(s)"
            );
        } elseif (!in_array(trim($file['type']), $mTypes)) {
            $val_response = array(
                'status' => false,
                'message' => 'Invalid image'
            );
        } elseif ($file['error'] != 0 && $file['error'] != 4) {
            $err_value = self::getUploadError($file['error']);
            $val_response = array(
                'status' => false,
                'message' => $err_value['msg']
            );
        } elseif (round(($file['size'] / 1048576), 2) > MAX_IMAGE_UPLOAD_SIZE_LIMIT) {
            // size validation
            $val_response = array(
                'status' => false,
                'message' => 'File size exceeded, it should be upto ' . MAX_IMAGE_UPLOAD_SIZE_LIMIT . "MB(s)"
            );
        } else {
            $val_response = array(
                'status' => true,
                'message' => 'Success.'
            );
        }
        return $val_response;
    }

    /**
     * Identify file upload error
     *
     * @param int $error_code            
     * @return array
     */
    public static function getUploadError($error_code) {
        $msg = '';
        $error = '';
        switch ($error_code) {
            case 1:
                $msg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                $error = 'UPLOAD_ERR_INI_SIZE';
                break;
            case 2:
                $msg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the form.';
                $error = 'UPLOAD_ERR_FORM_SIZE';
                break;
            case 3:
                $msg = 'The uploaded file was only partially uploaded.';
                $error = 'UPLOAD_ERR_PARTIAL';
                break;
            case 4:
                $msg = 'No file was uploaded.';
                $error = 'UPLOAD_ERR_NO_FILE';
                break;
            case 6:
                $msg = 'Missing a temporary folder.';
                $error = 'UPLOAD_ERR_NO_TMP_DIR';
                break;
            case 7:
                $msg = 'Failed to write file to disk.';
                $error = 'UPLOAD_ERR_CANT_WRITE';
                break;
            case 8:
                $msg = 'A PHP extension stopped the file upload.';
                $error = 'UPLOAD_ERR_EXTENSION';
                break;
            default:
                $msg = "No message";
                $error = "No error";
                break;

                return array(
                    'msg' => $msg,
                    'error' => $error
                );
        }
    }

    /**
     * function to upload user images
     *
     * @param array $files            
     * @param string $path            
     * @param string $dirname            
     * @throws \Exception
     * @return array
     */
    public static function uploadUserImages($files, $path, $dirname) {
        $isValid = true;
        $response = $resp = [];
        $directories = explode(DS, $dirname);
        $newpath = $path;
        foreach ($directories as $key => $dir) {
            $newpath .= $dir . DS;
            if (!file_exists($newpath)) {
                mkdir($newpath, 0777, true);
            }
        }
        if (!empty($files)) {
            foreach ($files as $fkey => $file) {
                $valid = StaticFunctions::validateImage($file);
                if ($valid['status']) {
                    $filter = new RenameUpload(array(
                        'target' => $path . DIRECTORY_SEPARATOR . $dirname . uniqid(rand(99, 9999) . "-" . mt_rand(11111, 999999) . "-"),
                        'use_upload_extension' => true
                    ));
                    $temp_resp = $filter->filter($files->$fkey);
                    if (isset($temp_resp['tmp_name'])) {
                        $filename = explode(DS, $temp_resp['tmp_name']);
                        $temp_resp['path'] = WEB_URL . $dirname . $filename[count($filename) - 1];
                        unset($temp_resp['tmp_name']);
                        unset($temp_resp['type']);
                        unset($temp_resp['error']);
                        unset($temp_resp['size']);
                    }
                    $resp[$fkey] = $temp_resp;
                } else {
                    $isValid = false;
                }
            }
        }
        if ($isValid) {
            $response = $resp;
        } else {
            throw new \Exception($valid['message'], 400);
        }
        return $response;
    }

    public static function generate_verification_code() {
        $length = 10;
        $verification_code = '';
        list ($usec, $sec) = explode(' ', microtime());
        mt_srand((float) $sec + ((float) $usec * 100000));
        $inputs = array_merge(range('z', 'a'), range(0, 9), range('A', 'Z'));
        for ($i = 0; $i < $length; $i ++) {
            $verification_code .= $inputs{mt_rand(0, 61)};
        }
        return $verification_code;
    }

    public static function generate_reservation_receipt_number($type, $state_code) {
        $timestamp = date('mdhis');
        $keys = rand(0, 9);
        $randString = 'M' . $timestamp . $keys;
        return $randString;
    }

    /**
     * Functin to move file from temporary directory to pre moderate
     *
     * @param string $file            
     * @param string $path            
     * @param string $newdirname            
     * @param string $olddirname            
     * @throws \Exception
     * @return string
     */
    public static function moveFile($file, $path, $newdirname, $olddirname) {
        $fileParts = explode("/", $file);
        $fileName = $fileParts[count($fileParts) - 1];
        $newname = $path . $newdirname . $fileName;
        $directories = explode(DS, $newdirname);
        $newpath = $path;
        foreach ($directories as $key => $dir) {
            $newpath .= $dir . DS;
            if (!file_exists($newpath)) {
                mkdir($newpath, 0777, true);
            }
        }
        try {
            $oldFilePath = $path . $olddirname . $fileName;
            rename($oldFilePath, $newname);
            return WEB_URL . $newdirname . $fileName;
        } catch (\Exception $exp) {
            throw new \Exception($exp->getMessage(), 400);
        }
    }

    public static function getConnectionWithTwitterAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
        $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
        return $connection;
    }

    public static function sendMail($sender, $sendername, $recievers, $template, $layout, $variables, $subject) {
        $config = self::getServiceLocator()->get('config');
        // Create a layout view so that it can set content as sub view
        $layoutView = new ViewModel();
        // create the sub view
        $view = new ViewModel();
        $layoutView->setTemplate($layout);

        $variables['hostname'] = isset($variables['hostname']) ? $variables['hostname'] : PROTOCOL . $config['constants']['web_url'];
        $view->setVariables($variables);
        $view->setTemplate($template);
        try {
            $renderer = self::getServiceLocator()->get('ViewRenderer');
        } catch (\Exception $ex) {
            // It is useful resque email
            $renderer = new PhpRenderer();
            $templateMaps = $config['view_manager']['template_map'];
            $resolver = new \Zend\View\Resolver\TemplateMapResolver($templateMaps);
            $renderer->setResolver($resolver);
        }

        $content = $renderer->render($view);
        $layoutVariables = array(
            'content' => $content,
            'web_url' => PROTOCOL . $config['constants']['web_url'],
            'order_url' => PROTOCOL . $config['constants']['web_url'] . "/order",
            'reserve_url' => PROTOCOL . $config['constants']['web_url'] . "/reserve",
            'privacy_url' => PROTOCOL . $config['constants']['web_url'] . "/privacy",
            'terms_url' => PROTOCOL . $config['constants']['web_url'] . "/terms",
            'support_url' => PROTOCOL . $config['constants']['web_url'] . "/support"
        );
        $layoutView->setVariables($layoutVariables);
        $content = $renderer->render($layoutView);
        if (is_array($recievers)) {
            foreach ($recievers as $reciever) {
                $settingUserDb = self::getServiceLocator()->get(\User\Model\User::class);
                $getMailerId = $settingUserDb->getUserEmailSubscriber($reciever);
                if (count($getMailerId) > 0 && $getMailerId != '') {
                    if (!self::getPermissionToSendMail($getMailerId['id'], $template)) {
                        return true;
                    }
                }
                //pr($content,1);
                //$mail = new Message();
                $mail = new Message();

                $mail->setBody($content);
                $mail->setFrom($sender, $sendername);
                $mail->setSubject($subject);
                $mail->setTo($reciever);
                try {
                    $mail->Sendmail();
                } catch (\Exception $e) {
                    \MUtility\MunchLogger::writeLog($e, 1, $e->getMessage());
                    continue;
                }
            }
        } else {
            echo $subject;
            return true;
        }
    }

    /**
     * function to fetch data from web service / API
     *
     * @param string $url            
     * @return multitype
     */
    public static function fetchDataFromUrl($url) {
        try {
            $config = array(
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'curloptions' => array(
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_AUTOREFERER => true,
                    CURLOPT_HEADER => true
                )
            );
            $client = new \Zend\Http\Client($url, $config);
            $req = $client->getRequest();
            $data = $client->send($req)->getBody();
        } catch (\Exception $ex) {
            return array(
                'error' => $ex->getMessage()
            );
        }
        return $data;
    }

    /**
     * get solr credentials from config
     *
     * @throws \Exception
     * @return array
     */
    public static function getSolrConnOptions() {
        $solr = array();
        $config = self::getServiceLocator()->get('config');
        if (isset($config['constants']['solr'])) {
            $solr = $config['constants']['solr'];
        } else {
            throw new \Exception('Solr Credentials not found', 503);
        }
        return $solr;
    }

    /**
     * get solr URL
     *
     * @throws \Exception
     * @return string
     */
    public static function getSolrUrl() {
        $env = getenv('APPLICATION_ENV');
        $config = self::getServiceLocator()->get('config');
        if (isset($config['constants']['solr'])) {
            $protocol = $config['constants']['solr'][$env]['protocol'];
            $host = $config['constants']['solr'][$env]['host'];
            $port = $config['constants']['solr'][$env]['port'];
            $context = $config['constants']['solr'][$env]['context'];
            $solrUrl = $protocol . $host . ":$port/$context/";
        } else {
            throw new \Exception('Solr URL not found', 503);
        }
        return $solrUrl;
    }

    public static function check_near_time($hi) {
        $arrNearTime = array();
        $arrHI = explode(":", $hi);
        $hours = $arrHI[0];
        $actual_minutes = $arrHI[1];
        $minutes = $actual_minutes;

        if ($minutes < 30) {
            $hours = $hours + 1;
            $minutes = 00;
        } else {
            $hours = $hours + 1;
            $minutes = 30;
        }
        if ($hours < 10) {
            $hours = '0' . $hours;
        }
        if ($minutes < 10) {
            $minutes = '0' . $minutes;
        }

        $arrNearTime['near_time'] = $hours . ':' . $minutes;
        $arrNearTime['actual_minutes'] = $actual_minutes;
        return $arrNearTime;
    }

    public static function extract_day_from_date($date) {
        $restaurant_day = array(
            'su',
            'mo',
            'tu',
            'we',
            'th',
            'fr',
            'sa'
        );
        if (empty($date))
            return '';
        $date = strtotime($date);
        $day = date('w', $date);
        return $restaurant_day[$day];
    }

    public static function filterRequestParams($input) {
        $htmlEntities = new \Zend\Filter\HtmlEntities();
        if (is_array($input)) {
            foreach ($input as $ikey => $ival) {
                $input[$ikey] = $htmlEntities->filter($ival);
            }
        } else {
            $input = $htmlEntities->filter($input);
        }
        return $input;
    }

    public static function pubnubPushNotification($message) {
        $user_current_notification = [];
        $uNotId = 0;
        if (isset($message['channel']) && $message['channel'] != '') {
            $exId = explode('_', $message['channel']);
            if (isset($exId[1]) && is_numeric($exId[1])) {
                $uNotId = $exId[1];
            } else if (isset($exId[2]) && is_numeric($exId[2])) {
                $uNotId = $exId[2];
            } else {
                $uNotId = 0;
            }
        }
        if (!self::getPermissionToSendNotification($uNotId) && ($exId[0] != "dashboard" || $exId[0] != "cmsdashboard")) {
            return true;
        }

        $type = [
            "myorders" => 2,
            "bill" => 18,
            "order" => 1,
            "reservation" => 3,
            "cancelreservation" => 15,
            "reviews" => 4,
            "others" => 0,
            "invite_friends" => 5,
            //"myfriends"=>5,
            "myreviews" => 6,
            "tip" => 7,
            "upload_photo" => 8,
            "bookmark" => 9,
            "friendship" => 10,
            "checkin" => 11,
            "snag-a-spot" => 19//, as discussed with Parmanad sir, close feed type from now.
                //"feed"=>12
        ];
        self::pubnub($message['channel'], $message);
        if (isset($message['msg'])) {
            $notificationModel = self::getServiceLocator()->get(\User\Model\UserNotification::class);
            $str_time = $notificationModel->getDayDifference($message['curDate'], $message['curDate']); //pr($message['curDate'],1);die('test');
            $notification = $notificationModel->countUserNotification($message['userId']);
            if (isset($message['sendcountzero']) && $message['sendcountzero'] == 0) {
                $count = 0;
            } else {
                $count = ($notification[0]['notifications'] == 0) ? 1 : $notification[0]['notifications'];
            }
            foreach ($message as $key => $val) {
                if (array_key_exists($message['type'], $type)) {
                    $user_current_notification['type'] = $type[$message['type']];
                }
                if ($key == 'userId') {
                    $user_current_notification['user_id'] = $val;
                } else if ($key == 'restaurantId') {
                    $user_current_notification['restaurant_id'] = $val;
                } else {
                    $user_current_notification[$key] = $val;
                }
            }
            $user_current_notification['msg_time'] = $str_time;
            self::pubnub('ios_' . $message['channel'], $message['msg']);
            self::pubnub('android_' . $message['channel'], $message['msg']);
        }
    }

    public static function pubnub($channel, $message) {
        $pnconf = new \PubNub\PNConfiguration();
        $config = self::getServiceLocator()->get('config');
        $pnconf->setSubscribeKey($config['constants']['pubnub']['PUBNUB_SUBSCRIBE_KEY']);
        $pnconf->setPublishKey($config['constants']['pubnub']['PUBNUB_PUBLISH_KEY']);
        $pnconf->setSecure(false);
        $pubnub = new \PubNub\PubNub($pnconf);
        // Subscribe is not async and will block the execution until complete.
        $result = $pubnub->publish()->channel($channel)->message($message)->sync();
    }

    /**
     * Extract image from base64 string and save it
     *
     * @param string $string            
     * @param string $basePath
     *            (base path upto public directory)
     * @param string $destinationDir
     *            (destination under public directory can be split with / if multiple subdirectories exists)
     * @throws \Exception
     * @return string
     */
    public static function getImagePath($base64_string = "", $basePath = "", $destinationDir = "") {
        if ($base64_string == "") {
            throw new \Exception("Image data dose not exits", 400);
        }
        // fetches image mime type from base64 string
        $pos = strpos($base64_string, ';');
        $type = explode(':', substr($base64_string, 0, $pos));
        $extension = array_search($type[1], self::$_allowedImageTypes);
        // check if image is valid
        if ($extension == "") {
            throw new \Exception("Invalid image.", 400);
        }

        // creates dir if dosenot exists
        if ($destinationDir != "") {
            $directories = explode(DS, $destinationDir);
            $newpath = $basePath;
            foreach ($directories as $key => $dir) {
                if ($dir != '') {
                    $newpath .= $dir . DS;
                }
                if (!file_exists($newpath)) {
                    mkdir($newpath, 0777, true);
                }
            }
        }

        // get userId from session
        $session = self::getUserSession();
        $userId = $session->getUserId();

        // fetches image mime type from base64 string
        $pos = strpos($base64_string, ';');
        $type = explode(':', substr($base64_string, 0, $pos));
        $extension = array_search($type[1], self::$_allowedImageTypes);
        // check if image is valid
        if ($extension == "") {
            throw new \Exception("Invalid image.", 400);
        }
        // fetches actual image data
        $base64_new_string = explode(",", $base64_string);
        $base64_string = $base64_new_string[1];
        // if base path dosenot exists, it uses temp system directory path
        if ($basePath == "") {
            $outputPath = ini_get('upload_tmp_dir');
            if (!$outputPath || $outputPath == "") {
                $outputPath = sys_get_temp_dir();
            }
            if (!$outputPath || $outputPath == "") {
                throw new \Exception("Invalid Temporary Path to Upload Files");
            }
        } else {
            // uses user defained path
            $outputPath = $basePath . $destinationDir;
            $returnPath = WEB_URL . $destinationDir;
        }
        $uniqueId = uniqid();
        $output_file = $outputPath . DIRECTORY_SEPARATOR . $uniqueId . "_" . $userId . "." . $extension;
        $return_file = $returnPath . $uniqueId . "_" . $userId . "." . $extension;
        // open file
        $ifp = @fopen($output_file, "wb");
        if (!$ifp) {
            throw new \Exception("Cannot open file for writing data");
        }
        // write file
        fwrite($ifp, base64_decode($base64_string));
        // close file
        fclose($ifp);
        return ($return_file);
    }

    // Functions to calculate Time Slots
    public static function getAllReserveSlots($restaurant_id, $date, $type, $ignorePassedTime = false, $getDetailedStatus = false) {
        $restaurantDetailsFunctions = new RestaurantDetailsFunctions();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, 'Y-m-d');

        $inputDay = $inputDate->format('D');
        $mappedDay = array_flip(static::$dayMapping);
        $DayAbbr = $mappedDay[$inputDay];
        $restaurantTimings = $restaurantDetailsFunctions->getRestaurantReserveOpenClose($restaurant_id, $DayAbbr);
        $restaurantTimings = $restaurantTimings[$DayAbbr];
        $currdateObject = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $combinedTimings = array();
        $slots = array();
        foreach ($restaurantTimings as $times) {
            $s = static::getReservationSlotsByOpenClose($times, $restaurant_id);
            $slots = array_merge($slots, $s);
        }
        $slots = array_map("unserialize", array_unique(array_map("serialize", $slots)));

        foreach ($slots as $key => $value) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slots[$key]['time'], 'Y-m-d H:i');
            if ($slotDateTime < $currdateObject) {
                $slots[$key]['status'] = 0;
            }
            $slots[$key]['date'] = $inputDate->format('Y-m-d');
            $slots[$key]['time'] = $slotDateTime->format('H:i');
        }
        array_multisort($slots);
        return static::splitTimeSlots($slots, $ignorePassedTime, $getDetailedStatus);
    }

    public static function getReservationSlotsByOpenClose($openClose, $restaurant_id) {
        $openTime = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $openClose['open'], 'H:i');
        $closeTime = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $openClose['close'], 'H:i');
        $midnight = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), '23:59:00', 'H:i:s');
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();
        foreach ($timeSlots as $key => $slot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $slot, 'H:i');
            if ($openTime < $closeTime) {
                if ($slotDateTime >= $openTime && $slotDateTime <= $closeTime) {
                    $opentimeSlot[] = array(
                        'status' => 1,
                        'time' => $slot
                    );
                }
            } else {
                // testing needs to be done
                if ($slotDateTime >= $openTime && $slotDateTime < $midnight || ($slotDateTime <= $closeTime)) {
                    $opentimeSlot[] = array(
                        'status' => 1,
                        'time' => $slot
                    );
                }
            }
        }
        return $opentimeSlot;
    }

    public static function getOrderSlotsByOpenClose($openClose, $restaurant_id) {
        $openTime = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $openClose['open'], 'H:i:s');
        $closeTime = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $openClose['close'], 'H:i:s');
        $midnight = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), '23:59:00', 'H:i:s');
        $timeSlots = static::$timeSlots;
        $opentimeSlot = array();
        foreach ($timeSlots as $key => $slot) {
            $slotDateTime = static::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $slot, 'H:i');
            if ($openTime < $closeTime) {
                if ($slotDateTime > $openTime && $slotDateTime <= $closeTime) {
                    $opentimeSlot[] = array(
                        'status' => 1,
                        'time' => $slot
                    );
                }
            } else {
                // testing needs to be done
                if ($slotDateTime > $openTime && $slotDateTime < $midnight || ($slotDateTime <= $closeTime)) {
                    $opentimeSlot[] = array(
                        'status' => 1,
                        'time' => $slot
                    );
                }
            }
        }
        return $opentimeSlot;
    }

    public static function splitTimeSlots($finalSlots, $ignorePassedTime = false, $getDetailedStatus = false) {
        $tomorrow = false;
        $prevTime = null;
        $finalReturn = array();
        foreach ($finalSlots as $key => $value) {
            if ($finalSlots[$key]['status'] == 0 && !$ignorePassedTime) {
                continue;
            }
            if (isset($prevTime)) {
                if ($prevTime > $finalSlots[$key]['time']) {
                    $tomorrow = true;
                } else {
                    $tomorrow = false;
                }
            }
            if ($tomorrow) {
                if ($getDetailedStatus) {
                    unset($finalSlots[$key]['date']);
                    $finalReturn['tomorrow'][] = $finalSlots[$key];
                } else {
                    $finalReturn['tomorrow'][] = $finalSlots[$key]['time'];
                }
            } else {
                if ($getDetailedStatus) {
                    unset($finalSlots[$key]['date']);
                    $finalReturn['today'][] = $finalSlots[$key];
                } else {
                    $finalReturn['today'][] = $finalSlots[$key]['time'];
                }
                $prevTime = $finalSlots[$key]['time'];
            }
        }
        return $finalReturn;
    }

    public static function getDateOrderSlots($restaurant_id, $date, $ignorePassedTime = false) {
        $todaysTimeSlots = self::getAllSlots($restaurant_id, $date, 'order', $ignorePassedTime);
        $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        $yesterdaysTimeSlots = self::getAllSlots($restaurant_id, $prevDate, 'order', $ignorePassedTime);

        $final = array(
            'today' => isset($todaysTimeSlots['today']) ? $todaysTimeSlots['today'] : array(),
            'tomorrow' => isset($yesterdaysTimeSlots['tomorrow']) ? $yesterdaysTimeSlots['tomorrow'] : array()
        );
        return array(
            'timeslots' => array_unique(array_merge($final['today'], $final['tomorrow']))
        );
    }

    public static function getDateReserveSlots($restaurant_id, $date, $ignorePassedTime = false, $getDetailedStatus = false) {
        $todaysTimeSlots = self::getAllReserveSlots($restaurant_id, $date, 'reservation', $ignorePassedTime, $getDetailedStatus);
        $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        $yesterdaysTimeSlots = self::getAllReserveSlots($restaurant_id, $prevDate, 'reservation', $ignorePassedTime, $getDetailedStatus);
        $final = array(
            'today' => isset($todaysTimeSlots['today']) ? $todaysTimeSlots['today'] : array(),
            'tomorrow' => isset($yesterdaysTimeSlots['tomorrow']) ? $yesterdaysTimeSlots['tomorrow'] : array()
        );
        return array(
            'timeslots' => array_unique(array_merge($final['today'], $final['tomorrow']), SORT_REGULAR)
        );
    }

    /**
     * push process to the queue
     *
     * @param array $data            
     * @throws \Exception
     * @return int
     */
    public static function resquePush($data = array(), $class = "") {
        //print_r($data);exit;
        $config = self::getServiceLocator()->get('config');
        if (empty($class) || $class == "SendEmail") {

            if (isset($config['resque-service']) && $config['resque-service']) {
                $token = \Resque::enqueue($config['constants']['redis']['channel'], 'SendEmail', $data, true);
                $status = new \Resque_Job_Status($token);
                return $status->get(); // Outputs the status
            } else {
                // sends email manually, if resque is disabled
                StaticFunctions::sendMail($data['sender'], $data['sendername'], $data['receivers'], $data['template'], $data['layout'], $data['variables'], $data['subject']);
            }
        } elseif ($class == "UploadS3") {
            if (isset($config['resque-service']) && $config['resque-service']) {
                $token = \Resque::enqueue($config['constants']['redis']['channel'], 'UploadS3', $data, true);
                $status = new \Resque_Job_Status($token);
                return $status->get(); // Outputs the status
            } else {
                // if resque is disabled it will helps to upload manually
                self::s3UploadImage($data);
            }
        } elseif ($class == "clevertap") {
            if (isset($config['clevertap_service']) && $config['clevertap_service']) {
                $token = \Resque::enqueue("netcore", 'netcore', $data, true);
                $status = new \Resque_Job_Status($token);
                return $status->get(); // Outputs the status
            }
        } elseif ($class == "activityLog") {
            if (isset($config['activity-log']) && $config['activity-log']) {
                $token = \Resque::enqueue($config['constants']['activityLogRedis']['channel'], 'ActivityLog', $data, true);
                $status = new \Resque_Job_Status($token);
                return $status->get(); // Outputs the status
            }
        } else {
            throw new \Exception($class . "Resque class dose not exists.", 400);
        }
    }

    private static function s3UploadImage($args) {
        $config = self::getServiceLocator()->get('config');
        $users = new \User\Model\User();
        $s3 = new S3Lib();
        $image_url = $config['image_base_urls']['local-api'];
        $bucket_name = $config['s3']['bucket_name'];
        $images = array();
        if ($args['op_type'] == 'all') {
            $images = $users->getDpImagesForUpload();
        } else
        if ($args['op_type'] == 'one') {
            $images = $users->getDpImagesForUpload($args['user_id']);
        }
        if (!empty($images)) {
            foreach ($images as $image) {
                $status = false;
                if (isset($image['display_pic_url']) && !empty($image['display_pic_url']) && $image['display_pic_url'] != null) {
                    $original_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url'];
                    $result = $s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if (isset($image['display_pic_url_normal']) && !empty($image['display_pic_url_normal']) && $image['display_pic_url_normal'] != null) {
                    $normal_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_normal'];
                    $result = $s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_normal'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if (isset($image['display_pic_url_large']) && !empty($image['display_pic_url_large']) && $image['display_pic_url_large'] != null) {
                    $large_image = $image_url . "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_large'];
                    $result = $s3->createObject($bucket_name, "/user_images/profile/" . $image['id'] . "/" . $image['display_pic_url_large'], $original_image);
                    if ($result) {
                        $status = true;
                    }
                }
                if ($status) {
                    try {
                        $users->updateImageStatus($image['id']);
                    } catch (\Exception $ex) {
                        
                    }
                }
            }
            return true;
        }
    }

    /**
     * 
     * @return Zend\Cache\Storage\Adapter\Redis
     */
    public static function getRedisCache() {
        $sl = StaticFunctions::getServiceLocator();
        $redis_cache = $sl->get("RedisCache");
        return $redis_cache;
    }

    public static function latLogDistanceCalculation($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $miles = number_format($miles, 2, '.', '');
        return $miles;
    }

    public static function sendMailToServiceProvider($sender, $sendername, $recievers, $template, $layout, $variables, $subject, $recieverCc, $fileAttachment) {

        $config = self::getServiceLocator()->get('config');
        // Create a layout view so that it can set content as sub view
        $layoutView = new ViewModel();
        // create the sub view
        $view = new ViewModel();
        $layoutView->setTemplate($layout);
        $variables['hostname'] = $config['constants']['web_url'];
        $view->setVariables($variables);
        $view->setTemplate($template);
        try {
            $renderer = self::getServiceLocator()->get('ViewRenderer');
        } catch (\Exception $ex) {
            // It is useful resque email
            $renderer = new PhpRenderer();
            $templateMaps = $config['view_manager']['template_map'];
            $resolver = new \Zend\View\Resolver\TemplateMapResolver($templateMaps);
            $renderer->setResolver($resolver);
        }
        $content = $renderer->render($view);
        $layoutVariables = array(
            'content' => $content,
            'web_url' => $config['constants']['web_url'],
            'order_url' => $config['constants']['web_url'] . "/order",
            'reserve_url' => $config['constants']['web_url'] . "/reserve",
            'privacy_url' => $config['constants']['web_url'] . "/privacy",
            'terms_url' => $config['constants']['web_url'] . "/terms",
            'support_url' => $config['constants']['web_url'] . "/support"
        );
        $layoutView->setVariables($layoutVariables);
        $content = $renderer->render($layoutView);

        $mail = new Message();
        $mimeMessage = new MimeMessage();
        $mine = new Mime();

        $html = new Part($content);
        $html->type = "text/html";

        $attachment = new Part(fopen($fileAttachment['filepath'], 'r'));
        $attachment->type = Mime::TYPE_OCTETSTREAM;
        $attachment->encoding = Mime::ENCODING_BASE64;
        $attachment->filename = $fileAttachment['filename'];
        $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

        $mimeMessage->setParts(array($html, $attachment));

        $mail->setEncoding('utf-8');

        $mail->setTo($recievers);
        $mail->setFrom($sender, $sendername);
        $mail->setBcc($recieverCc);
        $mail->setSubject($subject);
        $mail->setBody($mimeMessage);

        $transport = new Smtp();
        $transport->setOptions($mail->getSmtpOptionsForAttachment());
        $transport->send($mail);
    }

    ##################

    public static function getRestaurantOrderTimeSlotsMob($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $currDateTime->add(new \DateInterval('PT30M'));

        $slots = $calendar->getOrderOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];

        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }

        $opentimeSlot = array();
        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format('Y-m-d H:i'),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }
            $opentimeSlot[] = $sArray;
        }
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function getRestaurantTakeoutTimeSlotsMob($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $currDateTime->add(new \DateInterval('PT30M'));
        //pr($inputDate->format('Y-m-d H:i'),1);
        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];

        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if (isset($ocSlots['day']) && $ocSlots['day'] == 'yesterday') {
                        if ($slotDateTime >= $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                            $finalSlots[] = $slotDateTime;
                        }
                    } else {
                        if ($slotDateTime > $ocSlots['open'] && $slotDateTime <= $ocSlots['close']) {
                            $finalSlots[] = $slotDateTime;
                        }
                    }
                }
            }
        }

        $opentimeSlot = array();
        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format('Y-m-d H:i'),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }
            $opentimeSlot[] = $sArray;
        }
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function getRestaurantReservationTimeSlotsMob($restaurant_id, $date, $sixstyDate = false, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }
        foreach ($sixstyDate as $nDKey => $nextDateOfReservation) {
            $reservationAvailability = self::getNextDateOfReservationSlot($restaurant_id, $nextDateOfReservation);
            if ($reservationAvailability) {
                $date = $nextDateOfReservation;
                break;
            }
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));


        $nextDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->add(new \DateInterval('P1D'));
        $nextDate = $nextDateTime->format('Y-m-d');


        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];
        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime < $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }


        $opentimeSlot = array();
        $orderOpenClose = explode("-", ORDER_TIME_SLOT);
        $crmOpenClose = explode("-", CRM_OPEN_CLOSE_TIME);
        $crmOpenTime = strtotime($inputDate->format('Y-m-d') . " " . $orderOpenClose[0] . ":00");
        $crmCloseTime = strtotime($inputDate->format('Y-m-d') . " " . $crmOpenClose[1]);
        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format($input_datetime_format . " " . $output_datetime_format),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }
            $slotDateTime = strtotime($sArray['slot']);

            if (strtotime($inputDate->format('Y-m-d')) == strtotime($nextDate)) {
                if (strtotime($currDateTime->format('Y-m-d H:i')) > strtotime($currDateTime->format('Y-m-d') . " " . $crmOpenClose[1])) {
                    if ($slotDateTime >= $crmOpenTime) {
                        $opentimeSlot[] = $sArray;
                    }
                } elseif (strtotime($currDateTime->format('Y-m-d H:i')) < strtotime($currDateTime->format('Y-m-d') . " " . $crmOpenClose[1])) {
                    $opentimeSlot[] = $sArray;
                }
            } elseif (strtotime($inputDate->format('Y-m-d')) == strtotime($currDateTime->format('Y-m-d')) && strtotime($currDateTime->format('Y-m-d H:i')) > $crmCloseTime) {
                if ($slotDateTime >= $crmOpenTime && $slotDateTime <= $crmCloseTime) {
                    $opentimeSlot[] = $sArray;
                }
            } else {
                if (strtotime($inputDate->format('Y-m-d')) == strtotime($currDateTime->format('Y-m-d')) && strtotime($currDateTime->format('Y-m-d H:i')) < $crmOpenTime) {
                    if ($slotDateTime >= $crmOpenTime) {
                        $opentimeSlot[] = $sArray;
                    }
                } else {
                    $opentimeSlot[] = $sArray;
                }
            }
        }
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    ##################

    public static function uploadImageBase64($base64_string_array = array(), $basePath = "", $destinationDir = "") {
        $return_file = array();
        if (empty($base64_string_array)) {
            return $return_file;
        }
        // creates dir if dosenot exists
        if ($destinationDir != "") {
            $directories = explode(DS, $destinationDir);
            $newpath = $basePath;
            foreach ($directories as $key => $dir) {
                if ($dir != '') {
                    $newpath .= $dir . DS;
                }
                if (!file_exists($newpath)) {
                    mkdir($newpath, 0777, true);
                }
            }
        }

        // get userId from session
        $session = self::getUserSession();
        $userId = $session->getUserId();

        // fetches image mime type from base64 string
        foreach ($base64_string_array as $key => $base64_string) {
            $pos = strpos($base64_string, ';');
            $type = explode(':', substr($base64_string, 0, $pos));
            $extension = array_search($type[1], self::$_allowedImageTypes);
            // check if image is valid
            if ($extension == "") {
                return $return_file;
            }
            // fetches actual image data
            $base64_new_string = explode(",", $base64_string);
            $base64_string = $base64_new_string[1];

            $outputPath = $basePath . $destinationDir;
            $returnPath = WEB_URL . $destinationDir;
            $filename = uniqid(rand(99, 9999)) . "-" . mt_rand(11111, 999999) . "." . $extension;
            $output_file = $outputPath . DIRECTORY_SEPARATOR . $filename;
            $return_file[$key] = $returnPath . $filename;
            // open file
            $ifp = @fopen($output_file, "wb");
            if (!$ifp) {
                throw new \Exception("Cannot open file for writing data");
            }
            // write file
            fwrite($ifp, base64_decode($base64_string));
            // close file
            fclose($ifp);
        }
        return $return_file;
    }

    public static function getPerDayDeliveryStatus($restaurantId = false, $requestedDate = false) {

        if ($restaurantId) {
            if ($requestedDate) {
                $requestedDate = date('Y-m-d H:i:s', strtotime($requestedDate));
                $currentDay = self::getFormattedDateTime($requestedDate, 'Y-m-d H:i:s', 'D');
            } else {
                $currentDateTime = self::getRelativeCityDateTime(array(
                            'restaurant_id' => $restaurantId
                        ))->format(StaticFunctions::MYSQL_DATE_FORMAT);
                $currentDay = self::getFormattedDateTime($currentDateTime, 'Y-m-d H:i:s', 'D');
            }


            $calendarDay = array_search($currentDay, StaticFunctions::$dayMapping);
            $calendar = new Calendar();
            if (isset($calendar->calendarDayOperation($restaurantId, $calendarDay)[0])) {
                $dayOperation = $calendar->calendarDayOperation($restaurantId, $calendarDay)[0];
            } else {
                return false;
            }
            if ($dayOperation['breakfast_start_time'] == "00:00:00" && $dayOperation['breakfast_end_time'] == "00:00:00" && $dayOperation['lunch_start_time'] == "00:00:00" && $dayOperation['lunch_end_time'] == "00:00:00" && $dayOperation['dinner_start_time'] == "00:00:00" && $dayOperation['dinner_end_time'] == "00:00:00") {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function arrengeOrderOfCalendar($calendars = false) {
        $finalCalendar = array();
        $days = array('mo', 'tu', 'we', 'th', 'fr', 'sa', 'su');
        if ($calendars) {
            foreach ($calendars as $key => $cal) {
                if (in_array($cal['calendar_day'], $days)) {
                    $index = array_search($cal['calendar_day'], $days);
                    $finalCalendar[$index] = $cal;
                }
            }
        }
        return $finalCalendar;
    }

    public static function getResDistanceInMiles($res_id, $lat, $lng) {
        if (!$res_id) {
            return '0.00';
        }
        $restModel = new \Restaurant\Model\Restaurant();
        $data = $restModel->getRestaurantDeliveryData($res_id);
        return StaticFunctions::latLogDistanceCalculation($data['latitude'], $data['longitude'], $lat, $lng);
    }

    public static function sendSmsClickaTell($userSmsData, $userId = 0) {
//        if ($userId > 0) {
//            if (!self::getPermissionToSendSms($userId)) {
//                return true;
//            }
//        }
        $sl = static::getServiceLocator();
        $config = $sl->get('config');
        $clickatellConfig = $config['clikcatell'];
        $userMobNo = preg_replace('/\s+/', '', $clickatellConfig['country_code_us'] . $userSmsData['user_mob_no']);
        $SmsText = $userSmsData['message'];
        $clickatell = new \ClickaTell();
        $data = $clickatell->sendSms($clickatellConfig, $userMobNo, $SmsText);
        return $data;
    }

    /**
     * get aes params from config
     * @throws \Exception
     * @return array with keys aes_salt and aes_pass
     */
    public static function getAesOptions() {
        $config = self::getServiceLocator()->get('config');
        if (isset($config['constants']['crypto'])) {
            return $config['constants']['crypto'];
        } else {
            throw new \Exception('Encryption params not found', 503);
        }
    }

    public static function getRestaurantCityId($restaurantId) {
        $resturantModel = new Restaurant();
        $select = new Select();
        $select->from('restaurants');
        $select->columns(array(
            'city_id'
        ));

        $select->where(array(
            'id' => $restaurantId
        ));
        $resturantModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $restaurantCityDetail = $resturantModel->getDbTable()
                ->getReadGateway()
                ->selectWith($select)
                ->current();
        return $restaurantCityDetail;
    }

    /* This function is use to check wether the user have permission to send the notification or not
     * @parameter $userId
     * return boolean value
     */

    public static function getPermissionToSendNotification($userId = 0) {
        if ($userId > 0) {
            $settingDb = static::getServiceLocator()->get(\User\Model\UserActionSettings::class);
            $existActionSetting = current($settingDb->userActionSettings(['where' => ['user_id' => trim($userId)]]));
            if (!empty($existActionSetting)) {
                return $existActionSetting['notification_sent'];
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /* This function is use to check wether the user have permission to send the notification or not
     * @parameter $userId
     * return boolean value
     */

    public static function getPermissionToSendSms($userId = 0) {
        if ($userId > 0) {
            $settingDb = static::getServiceLocator()->get(\User\Model\UserActionSettings::class);
            $existActionSetting = current($settingDb->userActionSettings(array('where' => array('user_id' => trim($userId)))));
            if (!empty($existActionSetting)) {
                return $existActionSetting['sms_sent'];
            } else {
                return true;
            }
        }
    }

    /* This function is use to check wether the user have permission to send the notification or not
     * @parameter $userId
     * return boolean value
     */

    public static function getPermissionToSendMail($userId = 0, $template = false) {
        if ($userId > 0) {
            $template = str_replace('email-template/', '', $template);
            $config = self::getServiceLocator()->get('config');
            $notEmailRestrictionArray = $config['constants']['notEmailRestriction'];
            $settingDb = self::getServiceLocator()->get(\User\Model\UserActionSettings::class);
            $existActionSetting = current($settingDb->userActionSettings(array('where' => array('user_id' => trim($userId)))));
            if (in_array($template, $notEmailRestrictionArray)) {
                return true;
            } else if (!empty($existActionSetting)) {
                return $existActionSetting['email_sent'];
            } else {
                return true;
            }
        }
    }

    public static function getNextDateOfReservationSlot($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));


        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];
        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime < $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }

        $todayAvailable = false;

        foreach ($finalSlots as $slot) {

            if ($slot >= $currDateTime) {
                return $todayAvailable = true;
            }
        }
        return $todayAvailable;
    }

    public static function getRestaurantReservationTimeSlotsForCurrenttime($restaurant_id, $date, $input_datetime_format = 'Y-m-d', $output_datetime_format = 'H:i') {
        if ($restaurant_id == '') {
            return array();
        }

        $calendar = new Calendar();
        $inputDate = static::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, $input_datetime_format);
        $currDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));

        $nextDateTime = static::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->add(new \DateInterval('P1D'));
        $nextDate = $nextDateTime->format('Y-m-d');

        $slots = $calendar->getOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];
        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $finalSlots = array();
        foreach (static::$timeSlots as $slot) {
            $slotDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $inputDate->format('Y-m-d') . " " . $slot, 'Y-m-d H:i');
            foreach ($mergedSlots as $ocSlots) {
                if (!empty($ocSlots)) {
                    if ($slotDateTime >= $ocSlots['open'] && $slotDateTime < $ocSlots['close']) {
                        $finalSlots[] = $slotDateTime;
                    }
                }
            }
        }

        $opentimeSlot = array();

        foreach ($finalSlots as $slot) {
            $sArray = array(
                'slot' => $slot->format($output_datetime_format),
                'status' => 1
            );
            if ($slot <= $currDateTime) {
                $sArray['status'] = 0;
            }

            $slotDateTime = strtotime($inputDate->format('Y-m-d') . " " . $sArray['slot']);
            $opentimeSlot[] = $sArray;
        }

        return array_values(array_map('unserialize', array_unique(array_map('serialize', $opentimeSlot))));
    }

    public static function appUpdateNotification($message) {
        $user_current_notification = array();

        $config = self::getServiceLocator()->get('config');
        $pubnub = new \Pubnub(
                $config['constants']['pubnub']['PUBNUB_PUBLISH_KEY'], $config['constants']['pubnub']['PUBNUB_SUBSCRIBE_KEY'], "", false
        );
        $notificationModel = new \User\Model\UserNotification();
        $str_time = $notificationModel->getDayDifference($message['curDate'], $message['curDate']);
        $count = 1;
        $user_current_notification['type'] = 0;
        foreach ($message as $key => $val) {
            $user_current_notification[$key] = $val;
        }
        $user_current_notification['msg_time'] = $str_time;
        $user_current_notification['aps'] = array('alert' => $message['msg'], 'badge' => intval($count));
        $user_current_notification['channel'] = 'ios_' . $message['channel'];

        return $pubnub->publish(array(
                    'channel' => $message['channel'],
                    'message' => $user_current_notification,
                    'aps' => array('alert' => $message['msg'], 'badge' => intval($count))
        ));
    }

    public static function amtRedeemPoint($redeemed_point) {
        $sl = static::getServiceLocator();
        $config = $sl->get('Config');
        $pointEqualDollar = $config ['constants']['pointEqualDollar'];
        $point = $pointEqualDollar[0];
        $dollar = $pointEqualDollar[1];
        return ($redeemed_point * $dollar) / $point;
    }

    public static function getBitlyShortUrl($url, $format = 'txt') {
        $config = self::getServiceLocator()->get('config');
        $connectURL = 'http://api.bit.ly/v3/shorten?login=' . $config['bitly']['bit_login'] . '&apiKey=' . $config['bitly']['bit_app_key'] . '&uri=' . urlencode($url) . '&format=' . $format;
        return self::fetchDataFromUrl($connectURL);
    }

    public static function uploadCareerFile($files, $path, $dirname) {
        $isValid = true;
        $response = array();
        $resp = array();
        $i = 0;
        if (!empty($files)) {
            foreach ($files as $fkey => $file) {
                if (!empty($file['name'])) {
                    $valid = StaticFunctions::validateCareerFile($file);
                    if ($valid['status']) {
                        self::$careerFile[$i]['careerfile'] = $file;
                        $isValid = true;
                        $i++;
//                    $p = $path . $dirname . uniqid(rand(99, 9999) . "-" . mt_rand(11111, 999999) . "-");
//                    pr($p,1);
                        $filter = new RenameUpload(array(
                            'target' => $path . $dirname . uniqid(rand(99, 9999) . "-" . mt_rand(11111, 999999) . "-"),
                            'use_upload_extension' => true
                        ));
//                    pr($files->$fkey,1);
                        $temp_resp = $filter->filter($files->$fkey);
                        if (isset($temp_resp['tmp_name'])) {
                            $filename = explode(DS, $temp_resp['tmp_name']);
                            $temp_resp['path'] = $path . $dirname . $filename[count($filename) - 1];
                            unset($temp_resp['tmp_name']);
                            unset($temp_resp['error']);
                            unset($temp_resp['size']);
                        }
                        $resp[$fkey] = $temp_resp;
                    } else {
                        $isValid = false;
                    }
                }
            }
        }
        if ($isValid) {
            return $resp;
        } else {
            return $valid;
        }
        return false;
    }

    public static function validateCareerFile($file) {
        //pr($file['type']);
        if (empty($file['type']) || $file['type'] == null) {
            return $val_response = array(
                'status' => false,
                'message' => "We're sorry, we can not accept files over " . MAX_IMAGE_UPLOAD_SIZE_LIMIT . " mb."
            );
        } elseif (!in_array(trim($file['type']), self::$_allowedCareerTypes)) {
            return $val_response = array(
                'status' => false,
                'message' => "We're sorry, we can only accept .doc, .docx, and .pdf files."
            );
        } elseif ($file['error'] != 0 && $file['error'] != 4) {
            $err_value = self::getUploadError($file['error']);
            return $val_response = array(
                'status' => false,
                'message' => $err_value['msg']
            );
        } elseif (round(($file['size'] / 1048576), 2) > MAX_IMAGE_UPLOAD_SIZE_LIMIT) {
            // size validation
            return $val_response = array(
                'status' => false,
                'message' => "We're sorry, we can not accept files over " . MAX_IMAGE_UPLOAD_SIZE_LIMIT . " mb."//File size exceeded, it should be upto ' . MAX_IMAGE_UPLOAD_SIZE_LIMIT . "MB(s)"
            );
        } else {
            $val_response = array(
                'status' => true,
                'message' => 'Success.'
            );
        }
        return $val_response;
    }

    public static function sendCareerMail($sender, $sendername, $recievers, $template, $layout, $variables, $subject) {

        $config = self::getServiceLocator()->get('config');
        $layoutView = new ViewModel();
        $view = new ViewModel();
        $layoutView->setTemplate($layout);
        $variables['hostname'] = $config['constants']['web_url'];
        $view->setVariables($variables);
        $view->setTemplate($template);
        try {
            $renderer = self::getServiceLocator()->get('ViewRenderer');
        } catch (\Exception $ex) {
            // It is useful resque email
            $renderer = new PhpRenderer();
            $templateMaps = $config['view_manager']['template_map'];
            $resolver = new \Zend\View\Resolver\TemplateMapResolver($templateMaps);
            $renderer->setResolver($resolver);
        }

        $layoutVariables = array(
            'content' => $renderer->render($view),
            'web_url' => $config['constants']['web_url'],
            'order_url' => $config['constants']['web_url'] . "/order",
            'reserve_url' => $config['constants']['web_url'] . "/reserve",
            'privacy_url' => $config['constants']['web_url'] . "/privacy",
            'terms_url' => $config['constants']['web_url'] . "/terms",
            'support_url' => $config['constants']['web_url'] . "/support"
        );
        $layoutView->setVariables($layoutVariables);
        $content = $renderer->render($layoutView);

        $mail = new Message();
        $mimeMessage = new MimeMessage();

        $html = new Part($content);
        $html->type = "text/html";
        $setPart[] = $html;

        if ($variables['attachment']) {

            foreach ($variables['attachment_file'] as $key => $file) {

                $attachment = new Part(fopen($file['path'], 'r'));
                $attachment->type = Mime::TYPE_OCTETSTREAM;
                $attachment->encoding = Mime::ENCODING_BASE64;
                $attachment->filename = $file['name']; //$fileAttachment['filename'];
                $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                $setPart[] = $attachment;
            }
        }
        $mimeMessage->setParts($setPart);
        $mail->setEncoding('utf-8');
        $mail->setTo($recievers[0][0]);
        $mail->setFrom($sender, $sendername);
        $mail->setSubject($subject);
        $mail->setBody($mimeMessage);
        $transport = new Smtp();
        $transport->setOptions($mail->getSmtpOptionsForAttachment());
        $transport->send($mail);

        if ($variables['attachment']) {
            foreach ($variables['attachment_file'] as $key => $file) {
                unlink($file['path']);
            }
        }

        return true;
    }

    public static function fourceUpdate($currentVersion) {
        $hardVersion = DASHBOARD_HARD_VERSION_ANDROID;
        $softVersion = DASHBOARD_SOFT_VERSION_ANDROID;

        if ($currentVersion < $hardVersion) {
            $updateType = "hard";
        } elseif ($currentVersion < $softVersion) {
            $updateType = "soft";
        } else {
            $updateType = "no";
        }

        $appUpdate = array(
            "upgrade_type" => $updateType,
            "counter" => COUNTER,
            "message" => FOURCE_UPDATE_MESSAGE,
            "clear_data" => CLEAR_DATA,
            "apk_link" => APK_FILE_PATH
        );
        return $appUpdate;
    }

    public static function convertPointToDollar($earnPoint) {
        $sl = self::getServiceLocator();
        $config = $sl->get('Config');
        $pointEqualDollar = $config ['constants']['pointEqualDollar'];
        $point = $pointEqualDollar[0];
        $dollar = $pointEqualDollar[1];
        return ($earnPoint * $dollar) / $point;
    }

    public static function getPermissionToEmails($userId = 0) {
        if ($userId > 0) {
            $settingDb = new \User\Model\UserActionSettings();
            $existActionSetting = current($settingDb->select(array('where' => array('user_id' => trim($userId)))));
            if (!empty($existActionSetting)) {
                return $existActionSetting['email_sent'];
            } else {
                return true;
            }
        }
    }

    public static function resquePDF($data = array(), $class = "") {
        //print_r($data);exit;
        $config = self::getServiceLocator()->get('config');
        if (empty($class) || $class == "SendEmail") {

            return StaticFunctions::sendMailPDF($data['sender'], $data['sendername'], $data['receivers'], $data['template'], $data['layout'], $data['variables'], $data['subject']);
        } else {
            return '';
        }
    }

    public static function sendMailPDF($sender, $sendername, $recievers, $template, $layout, $variables, $subject) {
        $config = self::getServiceLocator()->get('config');
        // Create a layout view so that it can set content as sub view
        $layoutView = new ViewModel();
        // create the sub view
        $view = new ViewModel();
        $layoutView->setTemplate($layout);
        $variables['hostname'] = PROTOCOL . $config['constants']['web_url'];
        $view->setVariables($variables);
        $view->setTemplate($template);
        try {
            $renderer = self::getServiceLocator()->get('ViewRenderer');
        } catch (\Exception $ex) {
            // It is useful resque email
            $renderer = new PhpRenderer();
            $templateMaps = $config['view_manager']['template_map'];
            $resolver = new \Zend\View\Resolver\TemplateMapResolver($templateMaps);
            $renderer->setResolver($resolver);
        }
        $content = $renderer->render($view);
        $layoutVariables = array(
            'content' => $content,
            'web_url' => PROTOCOL . $config['constants']['web_url'],
            'order_url' => PROTOCOL . $config['constants']['web_url'] . "/order",
            'reserve_url' => PROTOCOL . $config['constants']['web_url'] . "/reserve",
            'privacy_url' => PROTOCOL . $config['constants']['web_url'] . "/privacy",
            'terms_url' => PROTOCOL . $config['constants']['web_url'] . "/terms",
            'support_url' => PROTOCOL . $config['constants']['web_url'] . "/support"
        );
        $layoutView->setVariables($layoutVariables);
        $content = $renderer->render($layoutView);
        return $content;
    }

}
