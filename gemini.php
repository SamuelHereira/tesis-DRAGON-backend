<?php
header("Content-Type: application/json");

require_once 'clases/gemini.class.php';
require_once 'clases/conexion/respuestaGenerica.php';

$_gemini = new Gemini();
$_respuestas = new RespuestaGenerica;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['topic']) || !isset($input['gameMode'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'topic' or 'gameMode'."]);
        exit;
    }

    $topic = $input['topic'];
    $gameMode = intval($input['gameMode']);

    // 1. Generar prompt y llamar a Gemini
    $prompt = $_gemini->generatePrompt($topic, $gameMode);
    $responseText = $_gemini->callGemini($prompt);

    if (!$responseText) {
        http_response_code(500);
        echo json_encode(["error" => "No valid response from Gemini."]);
        exit;
    }

    // 2. Limpiar ```json si existe
    $cleaned = trim($responseText);
    $cleaned = preg_replace('/^```json|```$/i', '', $cleaned);
    $cleaned = trim($cleaned);

    // 3. Convertir a JSON válido
    $decoded = json_decode($cleaned, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        http_response_code(500);
        echo json_encode(["error" => "Gemini response could not be parsed as JSON."]);
        exit;
    }

    // 4. Devolver solo el array limpio
    echo json_encode($decoded);
} else {
    http_response_code(405);
    echo json_encode($_respuestas->error_405());
}
