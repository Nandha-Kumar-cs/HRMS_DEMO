<?php

/**
 * Local DB credentials — copy this to config/db.local.php and fill in.
 * config/db.local.php is .gitignored; never commit real credentials.
 *
 * Usage: set MAGDYN_DB_CONFIG=config/db.local.php in your .env
 */

return [
    'DB_CONNECTION' => 'mysql',
    'DB_HOST'       => '127.0.0.1',
    'DB_PORT'       => '3306',
    'DB_DATABASE'   => 'hrms',
    'DB_USERNAME'   => 'root',
    'DB_PASSWORD'   => '',
    'DB_CHARSET'    => 'utf8mb4',
    'DB_COLLATION'  => 'utf8mb4_unicode_ci',
];
