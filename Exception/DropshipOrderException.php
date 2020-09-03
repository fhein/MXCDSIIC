<?php

namespace MxcDropshipInnocigs\Exception;

use RuntimeException;

class DropshipOrderException extends RuntimeException
{
    // will be thrown by DropshipOrder
    // catch and and query previous to access and handle the ApiException
    const API_EXCEPTION             = 2000;

    // will be thrown by DropshipOrder
    // catch and query the position detail via getPositions() on the DropshipOrderException
    const POSITIONS_ERROR           = 2100;

    // these will not be thrown
    // the positions hold an error code $position['CODE'] and error message $position['MESSAGE']
    const PRODUCT_NOT_AVAILABLE     = 2101;
    const PRODUCT_UNKNOWN           = 2102;
    const PRODUCT_NUMBER_MISSING    = 2103;
    const PRODUCT_OUT_OF_STOCK      = 2104;
    const POSITION_EXCEEDS_STOCK    = 2105;

    // will be thrown by DropshipOrder
    // catch and query the validation error messages via getAddress errors
    const RECIPIENT_ADDRESS_ERROR   = 2200;

    // these will not get thrown
    // getAddress() returns an array ['CODE' => one of these, 'MESSAGE' => the according message ]
    const RECIPIENT_COMPANY_TOO_LONG            = 2201;
    const RECIPIENT_COMPANY2_TOO_LONG           = 2202;
    const RECIPIENT_FIRST_NAME_TOO_SHORT        = 2203;
    const RECIPIENT_LAST_NAME_TOO_SHORT         = 2204;
    const RECIPIENT_NAME_TOO_LONG               = 2205;
    const RECIPIENT_STREET_ADDRESS_TOO_SHORT    = 2206;
    const RECIPIENT_STREET_ADDRESS_TOO_LONG     = 2207;
    const RECIPIENT_ZIP_TOO_SHORT               = 2208;
    const RECIPIENT_CITY_TOO_SHORT              = 2209;

    // will be thrown by DropshipOrderException
    const INNOCIGS_ERROR = 2300;
    // will be thrown by DropshipOrderException
    const DROPSHIP_NOK   = 2400;

    protected static $addressErrorMessages = [
        self::RECIPIENT_COMPANY_TOO_LONG         => 'Der Firmenname darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_COMPANY2_TOO_LONG        => 'Der Firmenname 2 darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_FIRST_NAME_TOO_SHORT     => 'Der Vorname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_LAST_NAME_TOO_SHORT      => 'Der Nachname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_NAME_TOO_LONG            => 'Vorname und Nachname dürfen zusammen nicht mehr als 34 Zeichen enthalten.',
        self::RECIPIENT_STREET_ADDRESS_TOO_SHORT => 'Die Straße mit Hausnummer muss mindestens aus fünf Zeichen bestehen.',
        self::RECIPIENT_STREET_ADDRESS_TOO_LONG  => 'Die Straße mit Hausnummer darf höchstens aus 35 Zeichen bestehen.',
        self::RECIPIENT_ZIP_TOO_SHORT            => 'Die Postleitzahl muss mindestens aus vier Zeichen bestehen.',
        self::RECIPIENT_CITY_TOO_SHORT           => 'Die Stadt muss mindestens aus drei Zeichen bestehen.',
    ];

    private $addressErrors = [];
    private $positions = [];
    private $dropshipInfo = [];
    private $innocigsErrors = [];

    public static function fromInvalidRecipientAddress(array $err)
    {
        $msg = 'Invalid recipient address.';
        $e = new self($msg, self::RECIPIENT_ADDRESS_ERROR);

        $errors = [];
        foreach ($err as $error) {
            $errors[] = [
                'CODE' => $error,
                'MESSAGE' => self::$addressErrorMessages[$error],
            ];
        }
        $e->setAddressErrors($errors);
        return $e;
    }

    public static function fromInvalidOrderPositions($positions)
    {
        $msg = 'Invalid order positions.';
        $e = new self($msg, self::POSITIONS_ERROR);
        $e->setPositions($positions);
        return $e;
    }

    public static function fromApiException($e)
    {
        $msg = 'Caught ApiException.';
        $e = new self($msg, self::API_EXCEPTION, $e);
        return $e;
    }

    public static function fromInnocigsErrors(array $errors)
    {
        $code = self::INNOCIGS_ERROR;
        $msg = 'InnoCigs API error codes available.';
        $e =  new DropshipOrderException($msg, $code);
        $e->setInnocigsErrors($errors);
        return $e;
    }

    public static function fromDropshipNOK(array $errors, array $info)
    {
        $code = self::DROPSHIP_NOK;
        $msg = 'InnoCigs API error codes available.';
        $e =  new DropshipOrderException($msg, $code);
        $e->setInnocigsErrors($errors);
        $e->setDropshipInfo($info);
        return $e;
    }

    public function setInnocigsErrors(array $errors)
    {
        $this->innocigsErrors = $errors;
    }

    public function getInnocigsErrors()
    {
        return $this->innocigsErrors;
    }

    public function setDropshipInfo($info)
    {
        $this->dropshipInfo = $info;
    }

    public function getDropshipInfo()
    {
        return $this->dropshipInfo;
    }

    public function setPositions(array $positions) {
        $this->positions = $positions;
    }

    public function getPositions() {
        return $this->positions;
    }

    public function setAddressErrors($errors) {
        $this->addressErrors = $errors;
    }

    public function getAddressErrors()
    {
        return $this->addressErrors;
    }

}