<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Traduções',
        'singular' => 'Tradução',
        'plural' => 'Traduções',
    ],
    'table' => [
        'group' => 'Grupo',
        'key' => 'Chave',
        'missing' => 'ausente',
    ],
    'form' => [
        'identity' => 'Identidade',
        'group_help' => '"*" é o grupo das chaves no estilo JSON (a chave é o texto inteiro); os outros grupos são os nomes dos arquivos de idioma, por exemplo "auth".',
        'texts' => 'Textos',
        'file_default' => 'Valor do arquivo: :text',
    ],
    'filters' => [
        'orphaned' => 'Não encontrado no código',
        'confirmed' => 'Confirmadas',
        'missing_in' => 'Ausente em',
        'missing_any' => 'Ausente em algum idioma',
    ],
    'actions' => [
        'confirm' => 'Confirmar',
        'unconfirm' => 'Remover confirmação',
        'prune_mark' => 'Marcar também as linhas não encontradas',
        'prune_mark_help' => 'Marca as linhas cuja chave não está mais no código nem nos arquivos lang (elas ficam na tabela e a marca é removida quando a chave volta). Nada é excluído.',
        'quick_translate' => 'Tradução rápida',
        'scan' => 'Escanear código',
        'scan_description' => 'Encontra as strings __(), trans(), trans_choice(), @lang e Lang::get() e os seus arquivos de idioma, e adiciona as linhas que faltam. Textos editados nunca são sobrescritos.',
        'prune' => 'Excluir também as linhas não encontradas',
        'prune_help' => 'Exclui as linhas criadas por uma varredura anterior cuja chave não existe mais no código nem nos arquivos de idioma. Strings montadas em tempo de execução não são encontradas, então confira o resultado antes.',
        'export' => 'Exportar',
        'import' => 'Importar',
        'import_help' => 'Um arquivo JSON no formato lang/xx.json: {"chave": "texto"}; chaves de grupo como "group.key".',
        'overwrite' => 'Sobrescrever os textos existentes',
    ],
    'fields' => [
        'locale' => 'Idioma',
        'file' => 'Arquivo',
    ],
    'scan' => [
        'summary_marks' => ':marked marcadas como não encontradas, :restored restauradas.',
        'done' => 'Varredura concluída',
        'summary' => ':found encontradas, :created criadas, :filled complementadas, :pruned excluídas, :skipped ignoradas.',
    ],
    'import' => [
        'invalid' => 'O arquivo não é um objeto JSON válido',
        'done' => 'Importação concluída',
        'summary' => ':created criadas, :updated atualizadas, :skipped ignoradas.',
    ],
    'quick' => [
        'navigation' => 'Tradução rápida',
        'title' => 'Tradução rápida',
        'empty' => 'Digite uma tradução primeiro.',
        'saved' => 'Salvo',
        'all_done' => 'Não há mais nada para traduzir neste idioma.',
        'remaining' => 'Faltam :count',
        'save_next' => 'Salvar e próxima',
        'skip' => 'Pular',
    ],
    'switcher' => [
        'label' => 'Idioma',
    ],
    'status' => [
        'label' => 'Status',
        'ok' => 'OK',
        'missing' => 'Faltando',
        'orphaned' => 'Fora do código',
    ],
];
