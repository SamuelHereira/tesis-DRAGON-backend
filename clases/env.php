<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Elimina comillas si las hay
        $value = trim($value, "'\"");

        putenv("$key=$value");       // Añade a variables del entorno
        $_ENV[$key] = $value;        // Opcional
        $_SERVER[$key] = $value;     // Opcional
    }
}

