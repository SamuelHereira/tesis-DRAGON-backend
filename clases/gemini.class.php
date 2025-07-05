<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class Gemini {
    private $geminiApiKey;
    private $geminiApiUrl;

    private $openAiApiKey;
    private $openAiApiUrl;

    public function __construct() {
        $this->geminiApiKey = getenv('GEMINI_API_KEY');
        $this->geminiApiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->geminiApiKey}";
        $this->openAiApiKey = getenv('OPENAI_API_KEY');
        $this->openAiApiUrl = "https://api.openai.com/v1/chat/completions";
    }

    public function generatePrompt(string $topic, int $gameMode, int $numRequirements): string {
        $num = $numRequirements;
            if ($num % 2 !== 0) {
                $num++; // Si es impar, lo ajustamos
            }
            $half = $num / 2;
        
        $promptTypes = [
            1 => "$half ambiguous non-functional requirements (code: NFA) and $half non-ambiguous non-functional requirements (code: NFN)",
            2 => "$half functional requirements (code: RF) and $half non-functional requirements (code: RNF)",
            3 => "$half ambiguous functional requirements (code: FA) and $half non-ambiguous functional requirements (code: FN)"
        ];

        if (!isset($promptTypes[$gameMode])) {
            return "Invalid game mode.";
        }

        $modeDescription = $promptTypes[$gameMode];

        return <<<PROMPT
            You are an expert in requirements engineering and serious game design.

            Given the topic: "$topic", generate requirements for a serious game based on this topic.

            Use the selected mode to generate requirements:
            $modeDescription

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

        $ch = curl_init($this->geminiApiUrl);
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

     public function callOpenAI(string $prompt): ?string {
        $postData = [
            "model" => "gpt-4", // o "gpt-3.5-turbo"
            "messages" => [
                [ "role" => "user", "content" => $prompt ]
            ],
            "temperature" => 0.7
        ];

        $headers = [
            "Authorization: Bearer {$this->openAiApiKey}",
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->openAiApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($postData)
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        $res = json_decode($response, true);
        return $res['choices'][0]['message']['content'] ?? null;
    }


    public function generarRequisitos(string $topic, int $gameMode, int $numRequirements, $ia = 'gemini') {
        $_respuestas = new RespuestaGenerica;

        if (empty($topic)) {
            return $_respuestas->error_400("El campo 'topic' es requerido.");
        }

        if (empty($gameMode) || !in_array($gameMode, [1, 2, 3])) {
            return $_respuestas->error_400("El campo 'gameMode' es requerido y debe ser 1, 2 o 3.");
        }

        if (empty($numRequirements) || !is_numeric($numRequirements) || $numRequirements < 1) {
            return $_respuestas->error_400("El campo 'numRequirements' es requerido y debe ser un número mayor que 0.");
        }
    

        $prompt = $this->generatePrompt($topic, $gameMode, $numRequirements);
        $responseText = $ia === 'gemini' ? $this->callGemini($prompt) : $this->callOpenAI($prompt);

        if (!$responseText) {
            return $_respuestas->error_500("No se obtuvo respuesta de la IA");
        }

        // Limpiar ```json si existe
        $cleaned = trim($responseText);
        $cleaned = preg_replace('/^```json|```$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        // Convertir a JSON válido
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $_respuestas->error_500("La respuesta de la IA no se pudo convertir en JSON válido.");
        }

        $result = $_respuestas->response;
        $result["result"] = $decoded;
        return $result;
    }

}