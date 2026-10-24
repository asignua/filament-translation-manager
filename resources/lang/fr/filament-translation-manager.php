<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Traductions',
        'singular' => 'Traduction',
        'plural' => 'Traductions',
    ],
    'table' => [
        'group' => 'Groupe',
        'key' => 'Clé',
        'missing' => 'manquant',
    ],
    'form' => [
        'identity' => 'Identité',
        'group_help' => '"*" est le groupe des clés de style JSON (la clé est le texte entier) ; les autres groupes sont les noms des fichiers de langue, par ex. "auth".',
        'texts' => 'Textes',
        'file_default' => 'Valeur du fichier : :text',
    ],
    'filters' => [
        'orphaned' => 'Introuvable dans le code',
        'confirmed' => 'Confirmées',
        'missing_in' => 'Manquant en',
        'missing_any' => 'Manquant dans une langue',
    ],
    'actions' => [
        'confirm' => 'Confirmer',
        'unconfirm' => 'Retirer la confirmation',
        'prune_mark' => 'Marquer aussi les lignes introuvables',
        'prune_mark_help' => 'Marque les lignes dont la clé a disparu du code et des fichiers lang (elles restent dans le tableau et la marque disparaît quand la clé revient). Rien n’est supprimé.',
        'quick_translate' => 'Traduction rapide',
        'scan' => 'Analyser le code',
        'scan_description' => 'Trouve les chaînes __(), trans(), trans_choice(), @lang et Lang::get() ainsi que vos fichiers de langue, puis ajoute les lignes manquantes. Les textes modifiés ne sont jamais écrasés.',
        'prune' => 'Supprimer aussi les lignes introuvables',
        'prune_help' => 'Supprime les lignes créées par une analyse précédente dont la clé a disparu du code et des fichiers de langue. Les chaînes construites à l\'exécution ne sont pas détectées : vérifiez d\'abord le résultat.',
        'export' => 'Exporter',
        'import' => 'Importer',
        'import_help' => 'Un fichier JSON au format lang/xx.json : {"clé": "texte"} ; clés de groupe sous la forme "group.key".',
        'overwrite' => 'Écraser les textes existants',
    ],
    'fields' => [
        'locale' => 'Langue',
        'file' => 'Fichier',
    ],
    'scan' => [
        'summary_marks' => ':marked marquées comme introuvables, :restored rétablies.',
        'done' => 'Analyse terminée',
        'summary' => ':found trouvées, :created créées, :filled complétées, :pruned supprimées, :skipped ignorées.',
    ],
    'import' => [
        'invalid' => 'Le fichier n\'est pas un objet JSON valide',
        'done' => 'Import terminé',
        'summary' => ':created créées, :updated mises à jour, :skipped ignorées.',
    ],
    'quick' => [
        'navigation' => 'Traduction rapide',
        'title' => 'Traduction rapide',
        'empty' => 'Saisissez d\'abord une traduction.',
        'saved' => 'Enregistré',
        'all_done' => 'Il ne reste rien à traduire dans cette langue.',
        'remaining' => ':count restantes',
        'save_next' => 'Enregistrer et suivant',
        'skip' => 'Passer',
    ],
    'switcher' => [
        'label' => 'Langue',
    ],
    'status' => [
        'label' => 'Statut',
        'ok' => 'OK',
        'missing' => 'Manquant',
        'orphaned' => 'Absent du code',
    ],
];
