<?php

use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;

return [
    'error_context' => [
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
        DropshipException::MODULE_API_XML_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Ungültige XML-Daten',
            'mailTitle'         => 'Ungültige XML-Daten',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> wurde '
                                    . 'übertragen. Die Antwort des InnoCigs Servers enthält fehlerhafte XML-Daten. '
                                    . ' <strong>Der Status des Dropship-Auftrags ist unbekannt.</strong> '
                                    . 'Bitte kontaktieren Sie InnoCigs.',
            'message'           => 'Ungültige XML-Daten erhalten. Bestellstatus unklar. Kontaktieren Sie InnoCigs.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        DropshipException::MODULE_API_FAILURE => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: InnoCigs Schnittstelle nicht erreichbar',
            'mailTitle'         => 'InnoCigs Schnittstelle nicht erreichbar',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                   . 'übertragen werden, da der InnoCigs Server nicht erreichbar ist. Es ist keine Aktion '
                                   . 'erforderlich. Die Übertragung wird wiederholt. Kontaktieren Sie InnoCigs.',
            'message'           => 'Bestellung nicht übertragen. InnoCigs Server nicht erreichbar. Keine Aktion erforderlich.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_SUPPLIER_ERRORS => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: InnoCigs Fehlermeldungen',
            'mailTitle'         => 'InnoCigs Fehlermeldungen',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                   . 'übertragen werden. Der InnoCigs Server meldet Fehler.',
            'message'           => 'Bestellung konnte nicht übertragen werden. InnoCigs meldet Fehler. Bitte überprüfen Sie die Bestellung.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        'UNKNOWN_ERROR'         => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Unbekannter Fehler',
            'mailTitle'         => 'Unbekannter Fehler',
            'mailBody'          => 'beim Versand der Bestellung <strong>{$orderNumber}</strong> ist ein bisher nicht '
                                    . 'behandelbarer Fehler ist aufgetreten. Der Status der Bestellung ist unklar. Bitte '
                                    . 'informieren Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
            'message'           => 'Ein nicht behandelter Fehler ist aufgetreten. Sendungsstatus unklar. Informieren Sie den Entwickler.',
            'severity'          => DropshipLogger::CRIT,
            'status'            => DropshipManager::ORDER_STATUS_ERROR,
        ],
        'ORDER_SUCCESS' => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber} erfolgreich übertragen',
            'mailTitle'         => 'Bestellung erfolgreich übertragen',
            'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde erfolgreich '
                                    . 'an InnoCigs übertragen. Warte auf Tracking-Daten. ',
            'message'           => 'Bestellung erfolgreich übertragen',
            'severity'          => DropshipLogger::NOTICE,
            'status'            => DropshipManager::ORDER_STATUS_SENT,
        ]
    ]
];
