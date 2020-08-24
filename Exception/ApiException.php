<?php

namespace MxcDropshipInnocigs\Exception;

use RuntimeException;

class ApiException extends RuntimeException {

    const NO_RESPONSE       = 1000;
    const JSON_DECODE       = 1001;
    const JSON_ENCODE       = 1002;
    const INNOCIGS_ERRORS   = 1004;

    // InnoCigs API errors
    const LOGIN_FAILED                  = 10000;
    const INVALID_XML                   = 10001;
    const NO_DROPSHIP_DATA              = 10002;
    const DROPSHIP_DATA_INCOMPLETE      = 10003;
    const UNKNOWN_API_FUNCTION          = 10004;
    const MISSING_ORIGINATOR            = 10005;
    const INVALID_ORIGINATOR            = 10006;
    const PAYMENT_LOCKED                = 10007;
    const PAYMENT_LIMIT_EXCEEDED        = 10008;
    const XML_ALREADY_UPLOADED          = 20000;
    const DROPSHIP_DATA_X_INCOMPLETE    = 20001;
    const ORIGINATOR_DATA_X_MISSING     = 20002;
    const ORIGINATOR_DATA_X_INCOMPLETE  = 20003;
    const RECIPIENT_DATA_X_MISSING      = 20004;
    const RECIPIENT_DATA_X_INCOMPLETE   = 20005;
    const DROPSHIP_WITHOUT_PRODUCTS     = 20006;
    const PRODUCT_DEFINITION_ERROR_1    = 20007;
    const PRODUCT_DEFINITION_ERROR_2    = 20008;
    const PRODUCT_DEFINITION_ERROR_3    = 20009;
    const MISSING_ORDERNUMBER           = 20010;
    const DUPLICATE_ORDERNUMBER         = 20011;
    const ADDRESS_DATA_ERROR            = 20012;
    const PRODUCT_NUMBER_MISSING        = 30000;
    const PRODUCT_NOT_AVAILABLE_1       = 30001;
    const PRODUCT_NOT_AVAILABLE_2       = 30002;
    const PRODUCT_UNKNOWN_1             = 30003;
    const PRODUCT_UNKNOWN_2             = 30004;
    const PRODUCT_UNKNOWN_3             = 30005;
    const PRODUCT_UNKNOWN_4             = 30006;
    const NOT_ONE_ORDER                 = 40001;
    const HEAD_DATA_MISSING             = 40002;
    const DELIVERY_ADDRESS_INVALID_1    = 40004;
    const DELIVERY_ADDRESS_INVALID_2    = 40005;
    const ORDER_NUMBER_INVALID_1        = 40006;
    const ORDER_NUMBER_INVALID_2        = 40007;
    const ORDER_POSITION_ERROR_1        = 40010;
    const ORDER_POSITION_ERROR_2        = 40011;
    const ORDER_POSITION_ERROR_3        = 40012;
    const ORDER_POSITION_ERROR_4        = 40013;
    const ORDER_POSITION_ERROR_5        = 40014;
    const ORDER_POSITION_ERROR_6        = 40015;
    const ORDER_POSITION_ERROR_7        = 40016;
    const ORDER_POSITION_ERROR_8        = 40017;
    const ORDER_POSITION_ERROR_9        = 40018;
    const ORDER_POSITION_ERROR_10       = 40019;
    const TOO_MANY_API_ACCESSES         = 50000;
    const MAINTENANCE                   = 50001;

    protected $innocigsErrors = null;

    public static function fromInvalidXML() {
        $msg = 'InnoCigs API: <br/>Invalid XML data received.';
        $code = self::INVALID_XML;
        return new ApiException($msg, $code);
    }

    public static function fromJsonEncode() {
        $msg = 'InnoCigs API: <br/>Failed to encode XML data to JSON.';
        $code = self::INVALID_XML;
        return new ApiException($msg, $code);
    }

    public static function fromJsonDecode() {
        $msg = 'InnoCigs API: <br/>Failed to decode JSON data.';
        $code = self::INVALID_XML;
        return new ApiException($msg, $code);
    }

    public static function fromInnocigsErrors(array $errors) {
        $code = self::INNOCIGS_ERRORS;
        $msg = 'InnoCigs API error codes available.';
        $e =  new ApiException($msg, $code);
        $e->setInnocigsErrors($errors);
        return $e;
    }

    public static function fromHttpStatus(int $status) {
        $code = $status;
        $msg = sprintf('InnoCigs API: <br\>HTTP Status: %u', $status);
        return new ApiException($msg, $code);
    }

    public function setInnocigsErrors(array $errors)
    {
        // a single error was returned
        if (isset($errors['ERROR']['CODE'])) {
            $this->innocigsErrors[] = $errors['ERROR'];
            return;
        }
        // multiple errors were returned
        foreach ($errors['ERROR'] as $error)
        {
            $this->innocigsErrors[] = $error;
        }
    }

    public function getInnocigsErrors()
    {
        return $this->innocigsErrors;
    }
}

