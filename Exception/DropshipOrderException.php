<?php

namespace MxcDropshipInnocigs\Exception;

use RuntimeException;

class DropshipOrderException extends RuntimeException
{
    const PRODUCT_NOT_AVAILABLE     = 2001;
    const PRODUCT_UNKNOWN           = 2002;
    const PRODUCT_NUMBER_MISSING    = 2003;
    const PRODUCT_OUT_OF_STOCK      = 2004;
    const POSITION_EXCEEDS_STOCK    = 2005;
    const RECIPIENT_ADDRESS_ERROR   = 2006;
    const POSITIONS_ERROR           = 2007;
    const API_EXCEPTION             = 2008;
    const INNOCIGS_ERRORS           = 2009;
    const DROPSHIP_NOK              = 2010;

    private $addressErrors = [];
    private $positions = [];
    private $dropshipInfo = [];
    private $innocigsErrors = [];

    public static function fromInvalidRecipientAdress(array $errors)
    {
        $msg = 'Invalid recipient address.';
        $e = new self($msg, self::RECIPIENT_ADDRESS_ERROR);
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
        $code = self::INNOCIGS_ERRORS;
        $msg = 'InnoCigs API error codes available.';
        $e =  new ApiException($msg, $code);
        $e->setInnocigsErrors($errors);
        return $e;
    }

    public static function fromDropshipNOK(array $errors, array $info)
    {
        $code = self::DROPSHIP_NOK;
        $msg = 'InnoCigs API error codes available.';
        $e =  new ApiException($msg, $code);
        $e->setInnocigsErrors($errors);
        $e->setDropshipInfo($info);
        return $e;
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

    public function getPositions(array $positions) {
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