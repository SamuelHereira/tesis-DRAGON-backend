<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}
class Conexion
{
    private $server;
    private $user;
    private $password;
    private $database;
    private $port;
    private $conexion;

    function __construct()
    {
        $this->server = "localhost";
        $this->user = "user";
        $this->password = "user";
        $this->database = "2025_c1_grupo3_2025";
        $this->port = "3306";

        $this->conexion = new mysqli($this->server, $this->user, $this->password, $this->database, $this->port);
        if ($this->conexion->connect_errno) {
            echo "algo va mal con la conexion";
            die();
        }
    }
    private function datosConexion()
    {
        $dirreccion = dirname(__FILE__);
        $jsonData = file_get_contents($dirreccion . "/" . "config");
        return json_decode($jsonData, true);
    }

    private function convertirUTF8($array)
    {
        array_walk_recursive($array, function (&$item, $key) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = utf8_encode($item);
            }
        });
        return $array;
    }

    public function obtenerDatos($sqlstr)
    {
        $results = $this->conexion->query($sqlstr);
        $resultArray = array();
        foreach ($results as $row) {
            $resultArray[] = $row;
        }
        return $this->convertirUTF8($resultArray);
    }

    public function obtenerDatosLogin($sqlstr, $parametros)
    {
        $stmt = $this->conexion->prepare($sqlstr);
        $tipos = '';
        $valores = [];
        foreach ($parametros as $valor) {
            if (is_int($valor)) {
                $tipos .= 'i';
            } elseif (is_float($valor)) {
                $tipos .= 'd';
            } elseif (is_string($valor)) {
                $tipos .= 's';
            } else {
                $tipos .= 'b';
            }
            $valores[] = $valor;
        }

        if (!empty($parametros)) {
            $stmt->bind_param($tipos, ...$valores);
        }

        $stmt->execute();

        $stmt->bind_result($p_idUsuario, $p_mensaje);

        $resultArray = [];
        while ($stmt->fetch()) {
            $fila = [
                'idUsuario' => $p_idUsuario,
                'mensaje' => $p_mensaje
            ];
            $resultArray[] = $fila;
        }

        return $this->convertirUTF8($resultArray);
    }

    public function obtenerProcedimientoAlmacendao($sqlstr, $parametros)
    {
        $stmt = $this->conexion->prepare($sqlstr);
        $tipos = '';
        $valores = [];
        foreach ($parametros as $valor) {
            if (is_int($valor)) {
                $tipos .= 'i';
            } elseif (is_float($valor)) {
                $tipos .= 'd';
            } elseif (is_string($valor)) {
                $tipos .= 's';
            } else {
                $tipos .= 'b';
            }
            $valores[] = $valor;
        }

        if (!empty($parametros)) {
            $stmt->bind_param($tipos, ...$valores);
        }

        $stmt->execute();

        $stmt->bind_result($p_data, $p_mensaje);

        $resultArray = [];
        while ($stmt->fetch()) {
            $fila = [
                'data' => $p_data,
                'mensaje' => $p_mensaje
            ];
            $resultArray[] = $fila;
        }

        return $this->convertirUTF8($resultArray);
    }

    public function obtenerDatosMensaje($sqlstr, $parametros)
    {
        $stmt = $this->conexion->prepare($sqlstr);
        $tipos = '';
        $valores = [];
        foreach ($parametros as $valor) {
            if (is_int($valor)) {
                $tipos .= 'i';
            } elseif (is_float($valor)) {
                $tipos .= 'd';
            } elseif (is_string($valor)) {
                $tipos .= 's';
            } else {
                $tipos .= 'b';
            }
            $valores[] = $valor;
        }

        if (!empty($parametros)) {
            $stmt->bind_param($tipos, ...$valores);
        }

        $stmt->execute();

        $stmt->bind_result($p_mensaje);

        $resultArray = [];
        while ($stmt->fetch()) {
            $fila = [
                'mensaje' => $p_mensaje
            ];
            $resultArray[] = $fila;
        }

        return $this->convertirUTF8($resultArray);
    }


    public function guardarRequerimientos($data)
    {

        $stmt = $this->conexion->prepare("INSERT INTO requerimientos (titulo, retroalimentacion, tipo_requerimiento) VALUES (?, ?, ?)");

        $stmt->bind_param("ssi", $requerimiento, $retroalimentacion, $idTipo);
        $resultArray = [];

        foreach ($data as $item) {
            $requerimiento = $item['requerimiento'];
            $retroalimentacion = $item['retroalimentacion'];
            $idTipo = $item['idTipo'];

            if (!$stmt->execute()) {
                $fila = [
                    'error' => true,
                    'mensaje' => $requerimiento
                ];
            } else {
                $fila = [
                    'error' => false,
                    'mensaje' => $requerimiento
                ];
            }
            $resultArray[] = $fila;
        }

        return $this->convertirUTF8($resultArray);
    }

    public function nonQuery($sqlstr)
    {
        $results = $this->conexion->query($sqlstr);
        return $this->conexion->affected_rows;
    }

    public function nonQueryId($sqlstr)
    {
        $results = $this->conexion->query($sqlstr);
        $filas = $this->conexion->affected_rows;
        if ($filas >= 1) {
            return $this->conexion->insert_id;
        } else {
            return 0;
        }
    }

    public function nonQueryIdParams($query, $types, $params)
    {
        if ($stmt = $this->conexion->prepare($query)) {
            // Vincular los parámetros
            $stmt->bind_param($types, ...$params);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                $filas = $stmt->affected_rows;
                if ($filas >= 1) {
                    return $stmt->insert_id;
                } else {
                    return 0;
                }
            } else {
                // Manejar errores de ejecución
                echo "Error al ejecutar la consulta: " . $stmt->error;
                return 0;
            }

            // Cerrar la declaración
            $stmt->close();
        } else {
            // Manejar errores de preparación
            echo "Error al preparar la consulta: " . $this->conexion->error;
            return 0;
        }
    }


    protected function encriptar($string)
    {
        return md5($string);
    }

    protected function desencriptar($encryptedPassword)
    {
        $secretKey = "seriousGame";
        //$decryptedPassword = openssl_decrypt($encryptedPassword, 'AES-128-ECB', $secretKey);
        $decryptedPassword = openssl_decrypt(base64_decode($encryptedPassword), 'AES-128-ECB', $secretKey, OPENSSL_RAW_DATA);
        return $decryptedPassword;
    }

}
?>