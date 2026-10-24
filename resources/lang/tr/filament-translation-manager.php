<?php

declare(strict_types=1);

return [
    'resource' => [
        'navigation' => 'Çeviriler',
        'singular' => 'Çeviri',
        'plural' => 'Çeviriler',
    ],
    'table' => [
        'group' => 'Grup',
        'key' => 'Anahtar',
        'missing' => 'eksik',
    ],
    'form' => [
        'identity' => 'Kimlik',
        'group_help' => '"*", JSON tarzı anahtarların grubudur (anahtar metnin tamamıdır); diğer gruplar dil dosyalarının adlarıdır, örn. "auth".',
        'texts' => 'Metinler',
        'file_default' => 'Dosyadaki değer: :text',
    ],
    'filters' => [
        'missing_in' => 'Eksik olduğu dil',
        'missing_any' => 'Herhangi bir dilde eksik',
    ],
    'actions' => [
        'quick_translate' => 'Hızlı çeviri',
        'scan' => 'Kodu tara',
        'scan_description' => '__(), trans(), trans_choice(), @lang ve Lang::get() metinlerini ve dil dosyalarınızı bulur, eksik satırları ekler. Düzenlediğiniz metinlerin üzerine asla yazılmaz.',
        'prune' => 'Artık bulunmayan satırları da sil',
        'prune_help' => 'Önceki taramayla oluşturulmuş ve anahtarı kodda ile dil dosyalarında artık bulunmayan satırları siler. Çalışma anında oluşturulan metinler bulunamaz, bu yüzden önce sonucu kontrol edin.',
        'export' => 'Dışa aktar',
        'import' => 'İçe aktar',
        'import_help' => 'lang/xx.json biçiminde bir JSON dosyası: {"anahtar": "metin"}; grup anahtarları "group.key" olarak.',
        'overwrite' => 'Mevcut metinlerin üzerine yaz',
    ],
    'fields' => [
        'locale' => 'Dil',
        'file' => 'Dosya',
    ],
    'scan' => [
        'done' => 'Tarama tamamlandı',
        'summary' => ':found bulundu, :created oluşturuldu, :filled tamamlandı, :pruned silindi, :skipped atlandı.',
    ],
    'import' => [
        'invalid' => 'Dosya geçerli bir JSON nesnesi değil',
        'done' => 'İçe aktarma tamamlandı',
        'summary' => ':created oluşturuldu, :updated güncellendi, :skipped atlandı.',
    ],
    'quick' => [
        'navigation' => 'Hızlı çeviri',
        'title' => 'Hızlı çeviri',
        'empty' => 'Önce bir çeviri yazın.',
        'saved' => 'Kaydedildi',
        'all_done' => 'Bu dilde çevrilecek bir şey kalmadı.',
        'remaining' => ':count kaldı',
        'save_next' => 'Kaydet ve sonraki',
        'skip' => 'Atla',
    ],
    'switcher' => [
        'label' => 'Dil',
    ],
];
