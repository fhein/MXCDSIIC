<?php

namespace MxcDropshipInnocigs\Exception;

use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;

// This class is not really an exception. It maps to DropshipException.

class ApiException
{
    const PRODUCT_UNKNOWN_1             = 30003;

    // this is a workaround because the InnoCigs API does not return UNKNOWN_PRODUCT
    // if an unknown product get queried via &command=product&model=unknown
    public static function fromEmptyProductInfo() {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        $errors = [
            'ERROR' => [
                'CODE'    => ApiException::PRODUCT_UNKNOWN_1,
                'MESSAGE' => 'Unbekanntes Produkt,'
            ]
        ];
        return DropshipException::fromSupplierErrors($supplier, $errors);
    }

    public static function fromXmlError(int $error) {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromXmlError($supplier, $error);
    }

    public static function fromSupplierErrors(array $errors) {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromSupplierErrors($supplier, $errors);
    }

    public static function fromHttpStatus(int $status) {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromHttpStatus($supplier, $status);
    }

    public static function fromInvalidOrderPositions($positionErrors)
    {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromInvalidOrderPositions($supplier, $positionErrors);
    }

    public static function fromInvalidRecipientAddress(array $errors)
    {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromInvalidRecipientAddress($supplier, $errors);
    }

    public static function fromClientException(Throwable $e)
    {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromClientException($supplier, $e);
    }
}

