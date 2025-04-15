<?php
require_once __DIR__ . '/../libs/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__ . '/../libs/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class imports extends Conexion
{
    public function importarExcel()
    {
        $_respustas = new RespuestaGenerica;

        if (!isset($_FILES['file'])) {
            return $_respustas->error_200("No se subió ningún archivo");
        }

        $archivo = $_FILES['file']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($archivo);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);

            $requisitos = [];
            $i = 0;
            foreach ($data as $index => $row) {
                if ($index == 1) continue;

                $requisitos[] = [
                    "title" => $row['A'] ?? '',
                    "feedback" => $row['B'] ?? '',
                    "type_code" => $row['C'] ?? 'RF',
                ];
            }

            $result = $_respustas->response;
            $result["result"] = $requisitos;
            return $result;

        } catch (Exception $e) {
            return $_respustas->error_200("Error al leer el archivo: " . $e->getMessage());
        }
    }
}