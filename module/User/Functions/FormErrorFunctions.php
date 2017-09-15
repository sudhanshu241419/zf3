<?php

namespace User\Functions;

class FormErrorFunctions {

    public function getLoginFormError($error) {
        if (isset($error ['email'])) {
            if (isset($error ['email'] ['isEmpty'])) {
                $error = $error ['email'] ['isEmpty'];
            } elseif (isset($error ['email'] ['emailAddressInvalidFormat'])) {
                $error = $error ['email'] ['emailAddressInvalidFormat'];
            } elseif (isset($error ['email'] ['emailAddressInvalidHostname'])) {
                $error = $error ['email'] ['emailAddressInvalidHostname'];
            } elseif (isset($error ['email'] ['noRecordFound'])) {
                $error = $error ['email'] ['noRecordFound'];
            }
        } elseif ($error ['password']) {
            if (isset($error ['password'] ['isEmpty'])) {
                $error = $error ['password'] ['isEmpty'];
            } elseif (isset($error ['password'] ['stringLengthTooShort'])) {
                $error = $error ['password'] ['stringLengthTooShort'];
            }
        }
        return $error;
    }

}
