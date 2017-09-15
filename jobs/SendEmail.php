<?php
use MCommons\StaticOptions;

class SendEmail
{

    private $application_env;

    function __construct()
    {
        $this->application_env = defined('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local';
        $this->initConstants();
    }

    public function initConstants()
    {
        $sl = StaticOptions::getServiceLocator();
        $config = $sl->get('Config');
        $constants = function ($search_term) use($config)
        {
            $currTarget = $config['constants'];
            $kArr = explode(":", $search_term);
            
            foreach ($kArr as $key => $value) {
                if (isset($currTarget[$value])) {
                    $currTarget = $currTarget[$value];
                } else {
                    throw new \Exception('Invalid Configuration Path: ' . $search_term . ' in $config["constants"]');
                }
            }
            return $currTarget;
        };
        if (($konstants = realpath(BASE_DIR . DS . 'config' . DS . 'konstants.php')) !== false) {
            return require_once $konstants;
        }
        return false;
    }

    public function setUp()
    {
        // ... Set up environment for this job
    }

    public function perform()
    {
        try {
            StaticOptions::sendMail($this->args['sender'], $this->args['sendername'], $this->args['receivers'], $this->args['template'], $this->args['layout'], $this->args['variables'], $this->args['subject']);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage(), 400);
        }
    }

    public function tearDown()
    {
        // ... Remove environment for this job
    }
}