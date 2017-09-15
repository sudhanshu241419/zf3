<?php

namespace User\FormFilter;

use Zend\InputFilter\InputFilterAwareInterface as InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use MCommons\StaticOptions;

class ChangePasswordFormFilter implements InputFilterAwareInterface {

    protected $inputFilter;

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        $adapter = StaticOptions::getDbReadAdapter();
        if (!$this->inputFilter) {
            $session = StaticOptions::getUserSession();
            $user_id = $session->getUSerId();
            $inputFilter = new InputFilter ();
            $inputFactory = new InputFactory ();
            $inputFilter->add($inputFactory->createInput(array(
                        'name' => 'current_password',
                        'validators' => array(
                            array(
                                'name' => 'NotEmpty',
                                'options' => array(
                                    'messages' => array(
                                        'isEmpty' => 'Woah now, we can\'t let you go without a password.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'string_length',
                                'options' => array(
                                    'min' => 6,
                                    'messages' => array(
                                        'stringLengthTooShort' => 'You need to use at least 6 characters". Try making it a personal catchphrase. Like yabadabadoo. But not that. Seriously Don\'t.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'Db\RecordExists',
                                'options' => array(
                                    'adapter' => $adapter,
                                    'table' => 'users',
                                    'field' => 'password',
                                    'exclude' => $adapter->getPlatform()->quoteIdentifier('id') . ' = ' . $adapter->getPlatform()->quoteValue($user_id),
                                    'messages' => array(
                                        'noRecordFound' => 'Current password not match.'
                                    )
                                )
                            )
                        )
            )));
            $inputFilter->add($inputFactory->createInput(array(
                        'name' => 'new_password',
                        'validators' => array(
                            array(
                                'name' => 'NotEmpty',
                                'options' => array(
                                    'messages' => array(
                                        'isEmpty' => 'Woah now, we can\'t let you go without a password.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'string_length',
                                'options' => array(
                                    'min' => 6,
                                    'messages' => array(
                                        'stringLengthTooShort' => 'You need to use at least 6 characters". Try making it a personal catchphrase. Like yabadabadoo. But not that. Seriously Don\'t.'
                                    )
                                )
                            )
                        )
            )));
            $inputFilter->add($inputFactory->createInput(array(
                        'name' => 'new_password_confirm',
                        'validators' => array(
                            array(
                                'name' => 'NotEmpty',
                                'options' => array(
                                    'messages' => array(
                                        'isEmpty' => 'Woah now, we can\'t let you go without a password.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'string_length',
                                'options' => array(
                                    'min' => 6,
                                    'messages' => array(
                                        'stringLengthTooShort' => 'You need to use at least 6 characters". Try making it a personal catchphrase. Like yabadabadoo. But not that. Seriously Don\'t.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'identical',
                                'options' => array(
                                    'token' => 'new_password'
                                )
                            )
                        )
            )));
            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }

}
