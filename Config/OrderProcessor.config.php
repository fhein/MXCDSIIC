<?php

use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;

return [
    'error_responses' => [
        DropshipException::ORDER_DROPSHIP_NOK => [
            'message'           => 'Die Übertragung der Bestellung ist fehlgeschlagen. Bitte überprüfen Sie die Bestellung.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        DropshipException::ORDER_POSITIONS_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailTitle'         => 'Fehler in den Bestellpositionen',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da einzelne Bestellpositionen fehlerhaft sind.',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Fehler in den Bestellpositionen',
            'message'           => 'Fehler in den Bestellpositionen',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Fehler in der Lieferadresse',
            'mailTitle'         => 'Fehler in der Lieferadresse',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da die Lieferaddresse Fehler aufweist.',
            'message'           => 'Fehler in der Lieferadresse',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        DropshipException::MODULE_API_NO_RESPONSE => [
            'message'           => 'Bestellung konnte nicht übertragen werden, da der InnoCigs Server nicht antwortet. Die Übertragung wird wiederholt. Keine Aktion erforderlich.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_JSON_ENCODE => [
            'message'           => 'Fehler bei der Dekodierung der Antwort des InnoCigs Servers. Status der Bestellung unbekannt. Bitte kontaktieren Sie InnoCigs.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_JSON_DECODE => [
            'message'           => 'Fehler bei der Dekodierung der Antwort des InnoCigs Servers. Status der Bestellung unbekannt. Bitte kontaktieren Sie InnoCigs.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_INVALID_XML_DATA => [
            'message'           => 'Ungültiges Datenformat der Antwort des InnoCigs Servers. Status der Bestellung unbekannt. Bitte kontaktieren Sie InnoCigs.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_HTTP_STATUS => [
            'message'           => 'HTTP Statusfehler beim Zugriff auf den InnoCigs Server. Bestellung konnte nicht übertragen werden. Die Übermittlung wird wiederholt. Keine Aktion erforderlich.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_SUPPLIER_ERRORS => [
            'message'           => 'Bestellung konnte nicht übertragen werden. InnoCigs meldet Fehler. Bitte überprüfen Sie die Bestellung.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
    ]
];
