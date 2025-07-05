<?php
require_once 'clases/imports.class.php';
require_once 'clases/conexion/respuestaGenerica.php';

require_once 'clases/env.php'; // Asegúrate de que este archivo existe y contiene la función loadEnv

loadEnv(__DIR__ . '/.env'); // Carga el archivo .env desde el directorio actual

$_imports = new imports();
$_respuestas = new RespuestaGenerica;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datosArray = [];

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'importarExcel':
                $datosArray = $_imports->importarExcel();
                break;
            default:
                $datosArray = $_respuestas->error_405();
                break;
        }
    }

    header('Content-Type: application/json');
    if ($datosArray["code"] != "200") {
        $responseCode = $datosArray["code"];
        http_response_code($responseCode);
    } else {
        http_response_code(200);
    }

    echo json_encode($datosArray);
} else {
    header('Content-Type: application/json');
    $datosArray = $_respuestas->error_405();
    echo json_encode($datosArray);
}