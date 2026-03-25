<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Синхронізація аватарок з legacy-хоста (T113)
    |--------------------------------------------------------------------------
    |
    | Джерело/призначення задаються лише в .env (SSH-ключ у агенті; паролі не в репо).
    | Приклад джерела: user@board.te.ua:/var/www/board.te.ua/html/avatar/
    | Призначення: локальний каталог (напр. storage/app/legacy-avatars).
    |
    */
    'avatar_rsync_source' => env('LEGACY_AVATAR_RSYNC_SOURCE', ''),
    'avatar_rsync_dest' => env('LEGACY_AVATAR_RSYNC_DEST', ''),

    /*
    |--------------------------------------------------------------------------
    | Каталог uploads на legacy-хості (T132)
    |--------------------------------------------------------------------------
    |
    | Типовий шлях на board.te.ua: /var/www/board.te.ua/html/uploads/
    |
    */
    'uploads_rsync_source' => env('LEGACY_UPLOADS_RSYNC_SOURCE', ''),
    'uploads_rsync_dest' => env('LEGACY_UPLOADS_RSYNC_DEST', ''),

    /*
    |--------------------------------------------------------------------------
    | Ремап URL у БД (T132)
    |--------------------------------------------------------------------------
    |
    | Базовий публічний origin нового сайту **без** завершального слешу, наприклад https://chat.example.com
    |
    */
    'url_remap_target_origin' => env('LEGACY_URL_REMAP_TARGET_ORIGIN', ''),

];
