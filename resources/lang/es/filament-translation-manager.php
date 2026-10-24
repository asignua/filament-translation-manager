<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Traducciones',
        'singular' => 'Traducción',
        'plural' => 'Traducciones',
    ],
    'table' => [
        'group' => 'Grupo',
        'key' => 'Clave',
        'missing' => 'falta',
    ],
    'form' => [
        'identity' => 'Identidad',
        'group_help' => '"*" es el grupo de claves estilo JSON (la clave es el texto completo); los demás grupos son los nombres de los archivos de idioma, p. ej. "auth".',
        'texts' => 'Textos',
        'file_default' => 'Valor del archivo: :text',
    ],
    'filters' => [
        'missing_in' => 'Falta en',
        'missing_any' => 'Falta en algún idioma',
    ],
    'actions' => [
        'quick_translate' => 'Traducción rápida',
        'scan' => 'Escanear código',
        'scan_description' => 'Encuentra las cadenas __(), trans(), trans_choice(), @lang y Lang::get() y tus archivos de idioma, y añade las filas que faltan. Los textos editados nunca se sobrescriben.',
        'prune' => 'Eliminar también las filas que ya no existen',
        'prune_help' => 'Elimina las filas creadas por un escaneo anterior cuya clave ya no está en el código ni en los archivos de idioma. Las cadenas construidas en tiempo de ejecución no se detectan, así que revisa antes el resultado.',
        'export' => 'Exportar',
        'import' => 'Importar',
        'import_help' => 'Un archivo JSON con el formato lang/xx.json: {"clave": "texto"}; las claves de grupo como "group.key".',
        'overwrite' => 'Sobrescribir los textos existentes',
    ],
    'fields' => [
        'locale' => 'Idioma',
        'file' => 'Archivo',
    ],
    'scan' => [
        'done' => 'Escaneo terminado',
        'summary' => 'Encontradas :found, creadas :created, completadas :filled, eliminadas :pruned, omitidas :skipped.',
    ],
    'import' => [
        'invalid' => 'El archivo no es un objeto JSON válido',
        'done' => 'Importación terminada',
        'summary' => 'Creadas :created, actualizadas :updated, omitidas :skipped.',
    ],
    'quick' => [
        'navigation' => 'Traducción rápida',
        'title' => 'Traducción rápida',
        'empty' => 'Escribe primero una traducción.',
        'saved' => 'Guardado',
        'all_done' => 'No queda nada por traducir en este idioma.',
        'remaining' => 'Quedan :count',
        'save_next' => 'Guardar y siguiente',
        'skip' => 'Omitir',
    ],
    'switcher' => [
        'label' => 'Idioma',
    ],
];
