<?php
spl_autoload_register(function ($class) {
    // Autoload PhpSpreadsheet
    $prefixPhpSpreadsheet = 'PhpOffice\\PhpSpreadsheet\\';
    $baseDirPhpSpreadsheet = __DIR__ . '/PhpSpreadsheet/src/PhpSpreadsheet/';

    if (strncmp($prefixPhpSpreadsheet, $class, strlen($prefixPhpSpreadsheet)) === 0) {
        $relative_class = substr($class, strlen($prefixPhpSpreadsheet));
        $file = $baseDirPhpSpreadsheet . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Autoload Composer\Pcre
    $prefixPcre = 'Composer\\Pcre\\';
    $baseDirPcre = __DIR__ . '/Composer/Pcre/';

    if (strncmp($prefixPcre, $class, strlen($prefixPcre)) === 0) {
        $relative_class = substr($class, strlen($prefixPcre));
        $file = $baseDirPcre . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});