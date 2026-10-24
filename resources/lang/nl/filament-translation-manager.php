<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Vertalingen',
        'singular' => 'Vertaling',
        'plural' => 'Vertalingen',
    ],
    'table' => [
        'group' => 'Groep',
        'key' => 'Sleutel',
        'missing' => 'ontbreekt',
    ],
    'form' => [
        'identity' => 'Identiteit',
        'group_help' => '"*" is de groep van JSON-achtige sleutels (de hele tekst is de sleutel); andere groepen zijn de namen van taalbestanden, bijv. "auth".',
        'texts' => 'Teksten',
        'file_default' => 'Bestandswaarde: :text',
    ],
    'filters' => [
        'orphaned' => 'Niet gevonden in code',
        'confirmed' => 'Bevestigd',
        'missing_in' => 'Ontbreekt in',
        'missing_any' => 'Ontbreekt in een taal',
    ],
    'actions' => [
        'confirm' => 'Bevestigen',
        'unconfirm' => 'Bevestiging intrekken',
        'prune_mark' => 'Ook niet meer gevonden rijen markeren',
        'prune_mark_help' => 'Markeert rijen waarvan de sleutel niet meer in de code en de lang-bestanden staat (ze blijven in de tabel en de markering verdwijnt als de sleutel terugkomt). Er wordt niets verwijderd.',
        'quick_translate' => 'Snel vertalen',
        'scan' => 'Code scannen',
        'scan_description' => 'Vindt __(), trans(), trans_choice(), @lang en Lang::get() en je taalbestanden en voegt ontbrekende rijen toe. Bewerkte teksten worden nooit overschreven.',
        'prune' => 'Ook rijen verwijderen die niet meer voorkomen',
        'prune_help' => 'Verwijdert rijen van een eerdere scan waarvan de sleutel niet meer in de code of taalbestanden staat. Tijdens runtime samengestelde teksten worden niet gevonden, controleer het resultaat dus eerst.',
        'export' => 'Exporteren',
        'import' => 'Importeren',
        'import_help' => 'Een JSON-bestand in het formaat lang/xx.json: {"sleutel": "tekst"}; groepssleutels als "group.key".',
        'overwrite' => 'Bestaande teksten overschrijven',
    ],
    'fields' => [
        'locale' => 'Taal',
        'file' => 'Bestand',
    ],
    'scan' => [
        'summary_marks' => ':marked gemarkeerd als niet gevonden, :restored hersteld.',
        'done' => 'Scan voltooid',
        'summary' => ':found gevonden, :created aangemaakt, :filled aangevuld, :pruned verwijderd, :skipped overgeslagen.',
    ],
    'import' => [
        'invalid' => 'Het bestand is geen geldig JSON-object',
        'done' => 'Import voltooid',
        'summary' => ':created aangemaakt, :updated bijgewerkt, :skipped overgeslagen.',
    ],
    'quick' => [
        'navigation' => 'Snel vertalen',
        'title' => 'Snel vertalen',
        'empty' => 'Voer eerst een vertaling in.',
        'saved' => 'Opgeslagen',
        'all_done' => 'Er valt in deze taal niets meer te vertalen.',
        'remaining' => ':count over',
        'save_next' => 'Opslaan & volgende',
        'skip' => 'Overslaan',
    ],
    'switcher' => [
        'label' => 'Taal',
    ],
    'status' => [
        'label' => 'Status',
        'ok' => 'OK',
        'missing' => 'Ontbreekt',
        'orphaned' => 'Niet in code',
    ],
];
