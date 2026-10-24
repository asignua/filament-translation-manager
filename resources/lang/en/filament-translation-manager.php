<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Translations',
        'singular' => 'Translation',
        'plural' => 'Translations',
    ],
    'table' => [
        'group' => 'Group',
        'key' => 'Key',
        'missing' => 'missing',
    ],
    'form' => [
        'identity' => 'Identity',
        'group_help' => '"*" is the group of JSON-style keys (the whole string is the key); other groups are the lang file names, e.g. "auth".',
        'texts' => 'Texts',
        'file_default' => 'File default: :text',
    ],
    'filters' => [
        'orphaned' => 'Not found in code',
        'confirmed' => 'Confirmed',
        'missing_in' => 'Missing in',
        'missing_any' => 'Missing in any locale',
    ],
    'actions' => [
        'confirm' => 'Confirm',
        'unconfirm' => 'Remove confirmation',
        'prune_mark' => 'Also mark rows no longer found',
        'prune_mark_help' => 'Marks rows whose key is gone from the code and lang files (they stay in the table and the mark is removed when the key comes back). Nothing is deleted.',
        'quick_translate' => 'Quick translate',
        'scan' => 'Scan code',
        'scan_description' => 'Finds __(), trans(), trans_choice(), @lang and Lang::get() strings and your lang files and adds the missing rows. Texts you edited are never overwritten.',
        'prune' => 'Also delete rows no longer found',
        'prune_help' => 'Deletes rows created by an earlier scan whose key is gone from the code and lang files. Strings built at runtime are not found, so check the result first.',
        'export' => 'Export',
        'import' => 'Import',
        'import_help' => 'A JSON file shaped like lang/xx.json: {"key": "text"}; group keys as "group.key".',
        'overwrite' => 'Overwrite existing texts',
    ],
    'fields' => [
        'locale' => 'Locale',
        'file' => 'File',
    ],
    'scan' => [
        'summary_marks' => 'Marked as not found :marked, restored :restored.',
        'done' => 'Scan finished',
        'summary' => 'Found :found, created :created, filled :filled, deleted :pruned, skipped :skipped.',
    ],
    'import' => [
        'invalid' => 'The file is not a valid JSON object',
        'done' => 'Import finished',
        'summary' => 'Created :created, updated :updated, skipped :skipped.',
    ],
    'quick' => [
        'navigation' => 'Quick translate',
        'title' => 'Quick translate',
        'empty' => 'Type a translation first.',
        'saved' => 'Saved',
        'all_done' => 'Nothing left to translate in this language.',
        'remaining' => ':count left',
        'save_next' => 'Save & next',
        'skip' => 'Skip',
    ],
    'switcher' => [
        'label' => 'Language',
    ],
    'status' => [
        'label' => 'Status',
        'ok' => 'OK',
        'missing' => 'Missing',
        'orphaned' => 'Not in code',
    ],
];
