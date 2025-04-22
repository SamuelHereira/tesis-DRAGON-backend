<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class Reviewer  extends Conexion
{

    public function obtainValidReviewers($gameId)
    {
        // $query = obtiene un listado de usuarios de rol "p" que no esten como revisores para ese juego
        $query = "SELECT idUsuario, nombres, apellidos, usuario, correo
        FROM usuarios
        WHERE rol = 'p'
        AND idUsuario NOT IN (
            SELECT id_usuario
            FROM revisores_juego
            WHERE id_juego = $gameId
        )";
        $datos = parent::obtenerDatos($query);

        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
    }

    public function obtainReviewers($gameId)
    {
        // $query = obtiene un listado de usuarios de rol "p" que no esten como revisores para ese juego
        $query = "SELECT u.idUsuario, u.nombres, u.apellidos, u.usuario, u.correo
              FROM revisores_juego r
              INNER JOIN usuarios u ON r.id_usuario = u.idUsuario
              WHERE r.id_juego = $gameId";

        $datos = parent::obtenerDatos($query);

        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
    }

    public function verifyReviewer($gameId, $userId)
    {
        // $query = verifica si el revisor ya existe para ese juego
        $query = "SELECT COUNT(*) as total
              FROM revisores_juego
              WHERE id_juego = $gameId AND id_usuario = $userId";

        $datos = parent::obtenerDatos($query);

        return $datos[0]['total'] > 0;
    }

    public function addReviewer($gameId, $reviewerId, $date)
    {
        if ($this->verifyReviewer($gameId, $reviewerId)) {
            return -1; 
        }

        $query = "INSERT INTO revisores_juego (id_juego, id_usuario, fecha_asignacion) VALUES (?, ?, ?)";
        $types = "ii";
        $params = [$gameId, $reviewerId, $date];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function removeReviewer($gameId, $reviewerId)
    {
        $query = "DELETE FROM revisores_juego WHERE id_juego = ? AND id_usuario = ?";
        $types = "ii";
        $params = [$gameId, $reviewerId];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function getValidReviewers($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego'])) {
            return $_respustas->error_400("El campo 'id_juego' es requerido.");
        } else {
            $gameId = $datos['id_juego'];
            $reviewers = $this->obtainValidReviewers($gameId);
            if ($reviewers) {
                $result = $_respustas->response;
                $result["result"] = $reviewers;
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
     
        }
    }
    
    public function getReviewers($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego'])) {
            return $_respustas->error_400("El campo 'id_juego' es requerido.");
        } else {
            $gameId = $datos['id_juego'];
            $reviewers = $this->obtainReviewers($gameId);
            if ($reviewers) {
                $result = $_respustas->response;
                $result["result"] = $reviewers;
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
     
        }
    }

    public function postAddReviewer($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego']) || !isset($datos['id_usuario'])) {
            return $_respustas->error_400("Los campos 'id_juego' y 'id_usuario' son requeridos.");
        } else {
            $gameId = $datos['id_juego'];
            $reviewerId = $datos['id_usuario'];
            $date = date('Y-m-d H:i:s');
            $result = $this->addReviewer($gameId, $reviewerId, $date);
            if ($result == -1) {
                return $_respustas->error_200("already_assigned");
            } elseif ($result > 0) {
                return $_respustas->response;
            } else {
                return $_respustas->error_500("Error al asignar el revisor.");
            }
        }
    }

    public function postRemoveReviewer($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego']) || !isset($datos['id_usuario'])) {
            return $_respustas->error_400("Los campos 'id_juego' y 'id_usuario' son requeridos.");
        } else {
            $gameId = $datos['id_juego'];
            $reviewerId = $datos['id_usuario'];
            $result = $this->removeReviewer($gameId, $reviewerId);
            if ($result > 0) {
                return $_respustas->response;
            } else {
                return $_respustas->error_500("Error al eliminar el revisor.");
            }
        }
    }

}
