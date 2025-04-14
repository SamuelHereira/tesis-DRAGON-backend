<?php
require_once 'PHPExcel/Classes/PHPExcel.php';

require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(["error" => "No se subió ningún archivo"]);
        exit;
    }

    $archivo = $_FILES['file']['tmp_name'];

    try {
        $reader = PHPExcel_IOFactory::createReaderForFile($archivo);
        $spreadsheet = $reader->load($archivo);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true); // A = col 1, B = col 2...

        $requerimientos = [];
        $i = 0;
        foreach ($data as $index => $row) {
            if ($index == 1) continue; // Saltar encabezado

            $requerimientos[] = [
                "id" => (string)$i++,
                "requerimiento" => $row['A'] ?? '',
                "retroalimentacion" => $row['B'] ?? '',
                "opcionRequerimiento" => $row['C'] ?? 'RF',
                "requerimientoBase" => "No",
                "requerimientoCompleto" => "",
                "requerimientoFallido" => false,
                "puntosAdicionales" => 100
            ];
        }

        echo json_encode(["requerimientos" => $requerimientos]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al leer el archivo: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}