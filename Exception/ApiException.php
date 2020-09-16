<?php

namespace MxcDropshipInnocigs\Exception;

use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;

// This class is not really an exception. It maps to DropshipException.

class ApiException
{
    const PRODUCT_UNKNOWN_1             = 30003;

    // this is a workaround because the InnoCigs API does not return UNKNOWN_PRODUCT
    // if an unknown product get queried via &command=product&model=unknown
    public static function fromEmptyProductInfo() {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        $errors = [
            'errors' => [
                'error' => [
                    'CODE'    => ApiException::PRODUCT_UNKNOWN_1,
                    'MESSAGE' => 'Unbekanntes Produkt,'
                ]
            ]
        ];
        return DropshipException::fromSupplierErrors($supplierId, $errors);
    }

    public static function fromInvalidXML() {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        return DropshipException::fromInvalidXml($supplierId);
    }

    public static function fromJsonEncode() {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        return DropshipException::fromJsonEncode($supplierId);
    }

    public static function fromJsonDecode() {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        return DropshipException::fromJsonDecode($supplierId);
    }

    public static function fromSupplierErrors(array $errors) {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        return DropshipException::fromSupplierErrors($supplierId, $errors);
    }

    public static function fromHttpStatus(int $status) {
        $supplierId = MxcDropshipInnocigs::getModule()->getId();
        return DropshipException::fromHttpStatus($supplierId, $status);
    }
}

