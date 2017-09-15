<?php

namespace User\FormFilter;

use Zend\InputFilter\InputFilterAwareInterface as InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use MCommons\StaticOptions;

class LoginFormFilter implements InputFilterAwareInterface {

    protected $inputFilter;

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    public function getInputFilter() {
        $adapter = StaticOptions::getDbReadAdapter();
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter ();
            $factory = new InputFactory ();
            $inputFilter->add($factory->createInput(array(
                        'name' => 'email',
                        'validators' => array(
                            array(
                                'name' => 'EmailAddress',
                                'options' => array(
                                    'messages' => array(
                                        'emailAddressInvalidHostname' => 'That don\'t look like any e-mail I ever seen. Maybe the \"@\" or the \".\" are in the wrong spot. This isn\'t cubism, put things where they belong!',
                                        'emailAddressInvalidFormat' => 'That don\'t look like any e-mail I ever seen. Maybe the \"@\" or the \".\" are in the wrong spot. This isn\'t cubism, put things where they belong!'
                                    )
                                )
                            ),
                            array(
                                'name' => 'Db\RecordExists',
                                'options' => array(
                                    'adapter' => $adapter,
                                    'table' => 'users',
                                    'field' => 'email',
                                    'messages' => array(
                                        'noRecordFound' => 'We couldn\'t find that email in our database. Maybe it ran off with another email, got married and changed its name to .net, .org. or some other crazy thing.'
                                    )
                                )
                            ),
                            array(
                                'name' => 'NotEmpty',
                                'options' => array(
                                    'messages' => array(
                                        'isEmpty' => 'Hey, you forgot something'
                                    )
                                )
                            )
                        )
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'password',
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
                                        'stringLengthTooShort' => 'Invalid Password'
                                    )
                                )
                            )
                        )
            )));
            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
