<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class Gemini {
    private $apiKey;
    private $apiUrl;

    public function __construct() {
        $this->apiKey = "AIzaSyCxfSpOr1NVRA_-WbT5n_wGbfTQim9GZVw";
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";
    }

    public function generatePrompt(string $topic, int $gameMode): string {
        $promptTypes = [
            1 => "10 ambiguous non-functional requirements (code: NFA) and 10 non-ambiguous non-functional requirements (code: NFN)",
            2 => "10 functional requirements (code: RF) and 10 non-functional requirements (code: RNF)",
            3 => "10 ambiguous functional requirements (code: FA) and 10 non-ambiguous functional requirements (code: FN)"
        ];

        if (!isset($promptTypes[$gameMode])) {
            return "Invalid game mode.";
        }

        $modeDescription = $promptTypes[$gameMode];

        return <<<PROMPT
            You are an expert in requirements engineering and serious game design.

            Given the topic: "$topic", generate 20 requirements for a serious game based on this topic.

            Use the selected mode to generate requirements:
            $modeDescription

            1. Mode 1 → 10 functional requirements (`RF`) and 10 non-functional requirements (`RNF`)
            2. Mode 2 → 10 ambiguous functional requirements (`FA`) and 10 non-ambiguous functional requirements (`FN`)
            3. Mode 3 → 10 ambiguous non-functional requirements (`NFA`) and 10 non-ambiguous non-functional requirements (`NFN`)

            Each requirement must:
            - Be written in **Spanish**
            - Start with **"El sistema deberá..."**
            - Be written in a **formal software requirement style**
            - Be short and clear (max 20 words per field)
            - Include:
            - `title`: the requirement sentence
            - `feedback`: short justification (why it’s clear/ambiguous, functional/non-functional, etc.)
            - `type_code`: one of [RF, RNF, FA, FN, NFA, NFN]

            Return only the valid JSON array. No extra explanation, markdown, or wrapping.

            Example format:

            ```json
            [
            {
                "title": "El sistema deberá mostrar la lista de vehículos disponibles.",
                "feedback": "Claro y funcional. Se puede verificar.",
                "type_code": "RF"
            }
            ]
        PROMPT;
    }

    public function callGemini(string $prompt): ?string {
        $data = [
            "contents" => [[
                "parts" => [[ "text" => $prompt ]]
            ]]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        

        if (curl_errno($ch)) {
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $res = json_decode($response, true);
        return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    public function generarRequisitos(string $topic, int $gameMode) {
        $_respuestas = new RespuestaGenerica;

        if (empty($topic)) {
            return $_respuestas->error_400("El campo 'topic' es requerido.");
        }

        if (empty($gameMode) || !in_array($gameMode, [1, 2, 3])) {
            return $_respuestas->error_400("El campo 'gameMode' es requerido y debe ser 1, 2 o 3.");
        }
        


        $prompt = $this->generatePrompt($topic, $gameMode);
        $responseText = $this->callGemini($prompt);

        if (!$responseText) {
            return $_respuestas->error_500("No se obtuvo respuesta de Gemini");
        }

        // Limpiar ```json si existe
        $cleaned = trim($responseText);
        $cleaned = preg_replace('/^```json|```$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        // Convertir a JSON válido
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $_respuestas->error_500("La respuesta de Gemini no se pudo convertir en JSON válido.");
        }

        $result = $_respuestas->response;
        $result["result"] = $decoded;
        return $result;
    }

}