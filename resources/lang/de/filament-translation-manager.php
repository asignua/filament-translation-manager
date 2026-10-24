<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Übersetzungen',
        'singular' => 'Übersetzung',
        'plural' => 'Übersetzungen',
    ],
    'table' => [
        'group' => 'Gruppe',
        'key' => 'Schlüssel',
        'missing' => 'fehlt',
    ],
    'form' => [
        'identity' => 'Identität',
        'group_help' => '"*" ist die Gruppe der JSON-Schlüssel (der ganze Text ist der Schlüssel); andere Gruppen sind die Namen der Sprachdateien, z. B. "auth".',
        'texts' => 'Texte',
        'file_default' => 'Standard aus Datei: :text',
    ],
    'filters' => [
        'missing_in' => 'Fehlt in',
        'missing_any' => 'Fehlt in irgendeiner Sprache',
    ],
    'actions' => [
        'quick_translate' => 'Schnell übersetzen',
        'scan' => 'Code scannen',
        'scan_description' => 'Findet __(), trans(), trans_choice(), @lang und Lang::get() sowie Ihre Sprachdateien und legt fehlende Zeilen an. Bearbeitete Texte werden nie überschrieben.',
        'prune' => 'Auch nicht mehr gefundene Zeilen löschen',
        'prune_help' => 'Löscht Zeilen früherer Scans, deren Schlüssel in Code und Sprachdateien nicht mehr vorkommt. Zur Laufzeit zusammengesetzte Texte werden nicht gefunden, prüfen Sie das Ergebnis also vorher.',
        'export' => 'Export',
        'import' => 'Import',
        'import_help' => 'Eine JSON-Datei im Format lang/xx.json: {"schlüssel": "text"}; Gruppenschlüssel als "group.key".',
        'overwrite' => 'Vorhandene Texte überschreiben',
    ],
    'fields' => [
        'locale' => 'Sprache',
        'file' => 'Datei',
    ],
    'scan' => [
        'done' => 'Scan abgeschlossen',
        'summary' => ':found gefunden, :created erstellt, :filled ergänzt, :pruned gelöscht, :skipped übersprungen.',
    ],
    'import' => [
        'invalid' => 'Die Datei ist kein gültiges JSON-Objekt',
        'done' => 'Import abgeschlossen',
        'summary' => ':created erstellt, :updated aktualisiert, :skipped übersprungen.',
    ],
    'quick' => [
        'navigation' => 'Schnell übersetzen',
        'title' => 'Schnell übersetzen',
        'empty' => 'Geben Sie zuerst eine Übersetzung ein.',
        'saved' => 'Gespeichert',
        'all_done' => 'In dieser Sprache ist nichts mehr zu übersetzen.',
        'remaining' => ':count übrig',
        'save_next' => 'Speichern & weiter',
        'skip' => 'Überspringen',
    ],
    'switcher' => [
        'label' => 'Sprache',
    ],
];
