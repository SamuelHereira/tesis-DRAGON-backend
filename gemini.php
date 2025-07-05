<?php
require_once 'clases/gemini.class.php';
require_once 'clases/conexion/respuestaGenerica.php';

require_once 'clases/env.php'; // Asegúrate de que este archivo existe y contiene la función loadEnv

loadEnv(__DIR__ . '/.env'); // Carga el archivo .env desde el directorio actual


$_gemini = new Gemini();
$_respuestas = new RespuestaGenerica;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postBody = file_get_contents("php://input");
    $requestData = json_decode($postBody, true);
    $datosArray = [];

    if (isset($requestData['action'])) {
        switch ($requestData['action']) {
            case 'generar':
                    $tema = $requestData['topic'];
                    $modoJuego = intval($requestData['gameMode']);
                    $numRequisitos = intval($requestData['numRequirements']);
                    $ia = isset($requestData['ia']) ? $requestData['ia'] : 'gemini';
                    $datosArray = $_gemini->generarRequisitos($tema, $modoJuego, $numRequisitos, $ia);
                    break;
            default:
                $datosArray = $_respuestas->error_400();
                break;
        }
    } else {
        $datosArray = $_respuestas->error_200("Falta parámetro 'action'");
    }

    header("Content-Type: application/json");
    if ($datosArray["code"] != "200") {
        http_response_code($datosArray["code"]);
    } else {
        http_response_code(200);
    }
    echo json_encode($datosArray);
} else {
    header("Content-Type: application/json");
    echo json_encode($_respuestas->error_405());
}