<?php

class Gemini {
    private $apiKey;
    private $apiUrl;

    public function __construct() {
        $this->apiKey = "AIzaSyCxfSpOr1NVRA_-WbT5n_wGbfTQim9GZVw";
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$this->apiKey}";
    }

    public function generatePrompt(string $topic, int $gameMode): string {
        $promptTypes = [
            1 => "10 functional requirements (code: RF) and 10 non-functional requirements (code: RNF)",
            2 => "10 ambiguous functional requirements (code: FA) and 10 non-ambiguous functional requirements (code: FN)",
            3 => "10 ambiguous non-functional requirements (code: NFA) and 10 non-ambiguous non-functional requirements (code: NFN)"
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
}
