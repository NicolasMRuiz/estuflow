<?php

// Executed in a pinned PHP container; never prints generated values.
if (! is_file('.env')) {
    $contents = file_get_contents('.env.example');
    $contents = str_replace('APP_KEY=', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $contents);
    $contents = preg_replace('/^DB_PASSWORD=$/m', 'DB_PASSWORD='.bin2hex(random_bytes(24)), $contents);
    $contents = preg_replace('/^TEST_DB_PASSWORD=$/m', 'TEST_DB_PASSWORD='.bin2hex(random_bytes(24)), $contents);
    $contents .= "\nLOCAL_UID=".posix_getuid()."\nLOCAL_GID=".posix_getgid()."\n";
    file_put_contents('.env', $contents);
    chmod('.env', 0600);
}

if (! is_file('.env.testing')) {
    $env = file_get_contents('.env');
    if (! preg_match('/^TEST_DB_PASSWORD=(\S+)$/m', $env, $matches)) {
        throw new RuntimeException('Falta TEST_DB_PASSWORD en .env. Configurarlo localmente; no compartir su valor.');
    }
    $contents = file_get_contents('.env.testing.example');
    $contents = str_replace('APP_KEY=', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $contents);
    $contents = preg_replace('/^DB_PASSWORD=$/m', 'DB_PASSWORD='.$matches[1], $contents);
    file_put_contents('.env.testing', $contents);
    chmod('.env.testing', 0600);
}
echo "Archivos de entorno preparados; los existentes se conservaron.\n";
