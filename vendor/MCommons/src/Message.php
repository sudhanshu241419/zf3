<?php
namespace MCommons;

use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions as SmtpOptions;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use MCommons\StaticFunctions;

class Message extends \Zend\Mail\Message
{

    protected $_smtpOptions = array();
    protected $_attachment = false;
    public function addAttachment(MimePart $attachment) {
    	$this->_attachment = $attachment;
    }

    /**
     * Send mail from the message API
     *
     * @return \StaticFunctions\Message
     */
    public function Sendmail()
    {
        // first of all get the body of the message as plain text and convert it to HTML
        $text = new MimePart('');
        $text->type = "text/plain";
        
        $html = new MimePart($this->getBody());
        $html->type = "text/html";
        
        if (! $this->getSubject()) {
            $this->setSubject("Email:");
        }
        
        $body = new MimeMessage();
        $parts = array($html);
        if($this->_attachment) {
        	$parts[] = $this->_attachment;
        }
        $body->setParts($parts);
        
        $this->setBody($body);
        
        $this->setEncoding("UTF-8");
        
        $transport = new SmtpTransport();
        $transport->setOptions($this->getSmtpOptions());
        $transport->send($this);
        $this->_attachment = false;
        return $this;
    }

    protected function getSmtpOptions()
    {
        $config = StaticFunctions::getServiceLocator()->get('Config');
        return new SmtpOptions(array(
            'name' => 'munchado.com',
            'host' => $config['constants']['email']['smtp']['host'],
            'connection_class' => 'plain',
            'connection_config' => array(
                'username' => $config['constants']['email']['smtp']['connection_config']['username'],
                'password' => $config['constants']['email']['smtp']['connection_config']['password'],
                'ssl' => $config['constants']['email']['smtp']['connection_config']['ssl']
            )
        ));
    }
    
    public function getSmtpOptionsForAttachment()
    {
    	$config = StaticFunctions::getServiceLocator()->get('Config');
    	return new SmtpOptions(array(
    			'name' => 'munchado.com',
    			'host' => $config['constants']['email']['smtp']['host'],
    			'connection_class' => 'plain',
    			'connection_config' => array(
    					'username' => $config['constants']['email']['smtp']['connection_config']['username'],
    					'password' => $config['constants']['email']['smtp']['connection_config']['password'],
    					'ssl' => $config['constants']['email']['smtp']['connection_config']['ssl']
    			)
    	));
    }
}