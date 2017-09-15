<?php

return [
    'constants' => [
        'protocol' => 'http',
        'imagehost' => 'qa.hungrybuzz.info/assets/',
        'facebook' => [
            'app_key' => '160675754093111',
            'app_secret' => 'aa35db6158113b205a874e68336af808',
            'page_id' => '491596394245644',
            'access_token' => '160675754093111|dm3VQyzrl9xfmUWNlZcuiaQwojw'
        ],
        'twitter' => [
            'handle' => 'Munch_Ado',
            'key' => '7gpJTmHONpbAHkz1hxsIA',
            'secret' => 'hGJtxZtEMgVHZCNOLvCtxrUwNXWS2vYmCGjkyRvgF0'
        ],
        'yahoo' => [
            'consumerKey' => 'dj0yJmk9TVQwbERnYk1wTjllJmQ9WVdrOWJHOU1XVzFPTnpnbWNHbzlNVEl3TmpjME1EWXkmcz1jb25zdW1lcnNlY3JldCZ4PTNm',
            'consumerSecret' => 'c87f8b093b1c28147a64efba3e564f7007a7b0fd'
        ],
        'gmail' => [
            'client_id' => '888693714463-66eqtkgav6n0snng5m83l1vq03ajt89v.apps.googleusercontent.com',
            'client_secret' => 'uOy5wavqu8Hkg6SUJyebtx6-'
        ],
        'hotmail' => [
            'client_id' => '000000004011FEB1',
            'client_secret' => 'ISOeGXQmlA5Gw9mqaIuCZhx1j17LprHD',
            'scope' => 'wl.basic wl.emails wl.contacts_emails',
            'redirect_uri' => 'munch-local.com/wapi/user/microsoftcontact/microsoftauthenticate'
        ],
        'instagram' => [
            'client_id' => '9ce363963a7745a8ba80dc3912ef06c8',
            'client_secret' => '19271ea7d29f4bb6bcd4267294c9f312',
            'access_token' => '401132820.9ce3639.b141e77825d44b23a4f97bb240716e90'
        ],
        'stripe' => [
            'secret_key' => 'sk_test_210sbEg9qLGepDaguTfuVnRw'
        ],
        'stripe' => [
            //Local --QA - Demo - Staging
            'secret_key' => 'sk_test_210sbEg9qLGepDaguTfuVnRw',
        //Local --QA - Demo - Staging End
        ],
        'solr' => [
            'qc' => [
                'protocol' => 'http://',
                'host' => 'qc.munchado.in',
                'port' => 8984,
                'context' => 'solr'
            ],
            'local' => [
                'protocol' => 'http://',
                'host' => 'localhost',
                'port' => 8984,
                'context' => 'solr'
            ]
        ],
        'blog' => [
            'blog_url' => 'http://blog.munchado.com/feed/'
        ],
        // QA
        'google+' => [
            'app_id' => '106525625756971589805',
            'api_key' => 'AIzaSyCGSFlgPwDhGVrL0Ss8pF6GZTZhV0oYLM8',
            'client_id' => '888693714463-66eqtkgav6n0snng5m83l1vq03ajt89v.apps.googleusercontent.com',
            'client_secret' => 'uOy5wavqu8Hkg6SUJyebtx6-',
            'developer_key' => 'AIzaSyCGSFlgPwDhGVrL0Ss8pF6GZTZhV0oYLM8',
            'gmail_scope' => 'https://www.google.com/m8/feeds',
            'redirect_uri' => 'munch-local.com/wapi/user/googlelogin/googleauthenticate',
            'contact_redirect_uri' => 'munch-local.com/wapi/user/googlecontact/googleauthenticate'
        ],
        'pubnub' => [
            'PUBNUB_PUBLISH_KEY' => 'pub-c-f87e9dcb-cb4e-403c-8987-cc0866d5263e',
            'PUBNUB_SUBSCRIBE_KEY' => 'sub-c-10490f34-064b-11e3-991c-02ee2ddab7fe',
            'PUBNUB_ENQUE' => 0
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'channel' => 'default',
            'enabled' => false
        ],
        'email' => [
            'demo_email' => 'notifications@munchado.com',
            'default_from' => [
                'name' => "Munchado Support",
                'email' => "notifications@munchado.com"
            ],
            'smtp' => [
                'name' => 'MunchAdo Support',
                'host' => 'smtp.gmail.com',
                'port' => 465,
                'connection_class' => 'login',
                'connection_config' => [
                    'username' => 'MunchAdo2015@gmail.com',
                    'password' => 'MunchAdo@2015',
                    'ssl' => 'tls'
                ]
            ]
        ],
        'web_url' => 'munchado-local.com',
        'dashboard_url' => 'dashboard-local.com',
        'memcache' => true,
        'campaigns' => [
            'sweepstakes' => false,
            'promotion_five_dollar' => false,
        ],
    ],
    'memcached' => [
        'servers' => ['localhost', '127.0.0.1']
    ],
    'php-settings' => [
        'error_reporting' => 15,
    ],
    // local
    'image_base_urls' => [
        'local-api' => 'munch-local.com',
        'local-cms' => 'http://hbcms-local.com',
        'amazon' => 'qa.hungrybuzz.info/assets/munch_images/'
    ],
    'mongo' => [
        'live' => ['host' => 'mongodb://127.0.0.1:27017',
            'database' => 'MunchAdo',
            'enabled' => true,
            'collection' => 'logs',
        //'user' => 'root',
        // 'pwd' => 'root'
        ],
        'local' => ['host' => 'mongodb://127.0.0.1:27017',
            'database' => 'MunchAdo',
            'enabled' => true,
            'collection' => 'logs',
        //'user' => 'root',
        // 'pwd' => 'root'
        ],
        'qc' => ['host' => 'mongodb://127.0.0.1:27017',
            'database' => 'MunchAdo',
            'enabled' => true,
            'collection' => 'logs',
        //'user' => 'root',
        // 'pwd' => 'root'
        ]
    ],
    'ga' => [
        'google_analytics_projectid' => '102104572',
        'authfile' => BASE_DIR . DS . 'vendor/MunchAdo-8ddb5c1a2800.json',
    ],
    'api_standards' => [
        // Default text 'token'
        'token_text' => 'token',
        'formatter_text' => 'response_type',
        'default_formatter' => 'json',
        'default_ttl' => 315360000
    ]
];
