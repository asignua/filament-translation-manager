<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Tłumaczenia',
        'singular' => 'Tłumaczenie',
        'plural' => 'Tłumaczenia',
    ],
    'table' => [
        'group' => 'Grupa',
        'key' => 'Klucz',
        'missing' => 'brak',
    ],
    'form' => [
        'identity' => 'Identyfikator',
        'group_help' => '"*" to grupa kluczy w stylu JSON (kluczem jest cały tekst); pozostałe grupy to nazwy plików lang, np. "auth".',
        'texts' => 'Teksty',
        'file_default' => 'W pliku: :text',
    ],
    'filters' => [
        'orphaned' => 'Nie znaleziono w kodzie',
        'confirmed' => 'Potwierdzone',
        'missing_in' => 'Brak w języku',
        'missing_any' => 'Brak w dowolnym języku',
    ],
    'actions' => [
        'confirm' => 'Potwierdź',
        'unconfirm' => 'Cofnij potwierdzenie',
        'prune_mark' => 'Oznacz też wiersze, których już nie ma',
        'prune_mark_help' => 'Oznacza wiersze, których klucza nie ma już w kodzie ani w plikach lang (zostają w tabeli, a znacznik znika, gdy klucz wróci). Nic nie jest usuwane.',
        'quick_translate' => 'Szybkie tłumaczenie',
        'scan' => 'Skanuj kod',
        'scan_description' => 'Znajduje ciągi __(), trans(), trans_choice(), @lang i Lang::get() oraz Twoje pliki lang i dodaje brakujące wiersze. Edytowane teksty nigdy nie są nadpisywane.',
        'prune' => 'Usuń też wiersze, których już nie ma',
        'prune_help' => 'Usuwa wiersze utworzone przez wcześniejsze skanowanie, których klucza nie ma już w kodzie ani w plikach lang. Ciągi budowane w trakcie działania nie są wykrywane, więc najpierw sprawdź wynik.',
        'export' => 'Eksport',
        'import' => 'Import',
        'import_help' => 'Plik JSON w formacie lang/xx.json: {"klucz": "tekst"}; klucze grup jako "group.key".',
        'overwrite' => 'Nadpisz istniejące teksty',
    ],
    'fields' => [
        'locale' => 'Język',
        'file' => 'Plik',
    ],
    'scan' => [
        'summary_marks' => 'Oznaczono jako nieznalezione :marked, przywrócono :restored.',
        'done' => 'Skanowanie zakończone',
        'summary' => 'Znaleziono :found, utworzono :created, uzupełniono :filled, usunięto :pruned, pominięto :skipped.',
    ],
    'import' => [
        'invalid' => 'Plik nie jest poprawnym obiektem JSON',
        'done' => 'Import zakończony',
        'summary' => 'Utworzono :created, zaktualizowano :updated, pominięto :skipped.',
    ],
    'quick' => [
        'navigation' => 'Szybkie tłumaczenie',
        'title' => 'Szybkie tłumaczenie',
        'empty' => 'Najpierw wpisz tłumaczenie.',
        'saved' => 'Zapisano',
        'all_done' => 'W tym języku nie ma już nic do przetłumaczenia.',
        'remaining' => 'Pozostało: :count',
        'save_next' => 'Zapisz i dalej',
        'skip' => 'Pomiń',
    ],
    'switcher' => [
        'label' => 'Język',
    ],
    'status' => [
        'label' => 'Status',
        'ok' => 'OK',
        'missing' => 'Brak tłumaczenia',
        'orphaned' => 'Brak w kodzie',
    ],
];
