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
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        $errors = [
            'errors' => [
                'error' => [
                    'CODE'    => ApiException::PRODUCT_UNKNOWN_1,
                    'MESSAGE' => 'Unbekanntes Produkt,'
                ]
            ]
        ];
        return DropshipException::fromSupplierErrors($supplier, $errors);
    }

    public static function fromInvalidXML() {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromInvalidXml($supplier);
    }

    public static function fromJsonEncode() {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromJsonEncode($supplier);
    }

    public static function fromJsonDecode() {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromJsonDecode($supplier);
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

    public static function fromDropshipNOK(array $errors, array $data)
    {
        $supplier = MxcDropshipInnocigs::getModule()->getName();
        return DropshipException::fromDropshipNOK($supplier, $errors, $data);
    }

}

