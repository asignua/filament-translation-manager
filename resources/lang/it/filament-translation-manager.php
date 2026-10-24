<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Traduzioni',
        'singular' => 'Traduzione',
        'plural' => 'Traduzioni',
    ],
    'table' => [
        'group' => 'Gruppo',
        'key' => 'Chiave',
        'missing' => 'mancante',
    ],
    'form' => [
        'identity' => 'Identità',
        'group_help' => '"*" è il gruppo delle chiavi in stile JSON (la chiave è l\'intero testo); gli altri gruppi sono i nomi dei file di lingua, ad es. "auth".',
        'texts' => 'Testi',
        'file_default' => 'Valore del file: :text',
    ],
    'filters' => [
        'missing_in' => 'Mancante in',
        'missing_any' => 'Mancante in una lingua',
    ],
    'actions' => [
        'quick_translate' => 'Traduzione rapida',
        'scan' => 'Scansiona il codice',
        'scan_description' => 'Trova le stringhe __(), trans(), trans_choice(), @lang e Lang::get() e i tuoi file di lingua, e aggiunge le righe mancanti. I testi modificati non vengono mai sovrascritti.',
        'prune' => 'Elimina anche le righe non più trovate',
        'prune_help' => 'Elimina le righe create da una scansione precedente la cui chiave non è più nel codice né nei file di lingua. Le stringhe costruite a runtime non vengono rilevate, quindi controlla prima il risultato.',
        'export' => 'Esporta',
        'import' => 'Importa',
        'import_help' => 'Un file JSON nel formato lang/xx.json: {"chiave": "testo"}; chiavi di gruppo come "group.key".',
        'overwrite' => 'Sovrascrivi i testi esistenti',
    ],
    'fields' => [
        'locale' => 'Lingua',
        'file' => 'File',
    ],
    'scan' => [
        'done' => 'Scansione completata',
        'summary' => 'Trovate :found, create :created, integrate :filled, eliminate :pruned, saltate :skipped.',
    ],
    'import' => [
        'invalid' => 'Il file non è un oggetto JSON valido',
        'done' => 'Importazione completata',
        'summary' => 'Create :created, aggiornate :updated, saltate :skipped.',
    ],
    'quick' => [
        'navigation' => 'Traduzione rapida',
        'title' => 'Traduzione rapida',
        'empty' => 'Inserisci prima una traduzione.',
        'saved' => 'Salvato',
        'all_done' => 'Non resta nulla da tradurre in questa lingua.',
        'remaining' => 'Ancora :count',
        'save_next' => 'Salva e avanti',
        'skip' => 'Salta',
    ],
    'switcher' => [
        'label' => 'Lingua',
    ],
];
