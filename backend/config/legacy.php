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

];
