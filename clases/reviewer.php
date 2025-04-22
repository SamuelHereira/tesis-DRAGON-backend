<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class Reviewer  extends Conexion
{

    public function obtainValidReviewers($gameId)
    {
     
        $query = "SELECT idUsuario, nombres, apellidos, usuario, correo
        FROM usuarios
        WHERE rol = 'e'
        AND idUsuario NOT IN (
            SELECT id_usuario
            FROM revisor_juego
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
        $query = "SELECT u.idUsuario, u.nombres, u.apellidos, u.usuario, u.correo
              FROM revisor_juego r
              INNER JOIN usuarios u ON r.id_usuario = u.idUsuario
              WHERE r.id_juego = $gameId";

        $datos = parent::obtenerDatos($query);

        if (isset($datos[0])) {
            return $datos;
        } else {
            return [];
        }
    }

    public function verifyReviewer($gameId, $userId)
    {
        // $query = verifica si el revisor ya existe para ese juego
        $query = "SELECT COUNT(*) as total
              FROM revisor_juego
              WHERE id_juego = $gameId AND id_usuario = $userId";

        $datos = parent::obtenerDatos($query);

        return $datos[0]['total'] > 0;
    }

    public function addReviewer($gameId, $reviewerId, $date)
    {
        if ($this->verifyReviewer($gameId, $reviewerId)) {
            return -1; 
        }

        $query = "INSERT INTO revisor_juego (id_juego, id_usuario, fecha_asignacion) VALUES (?, ?, ?)";
        $types = "iis";
        $params = [$gameId, $reviewerId, $date];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function removeReviewer($gameId, $reviewerId)
    {
        $query = "DELETE FROM revisor_juego WHERE id_juego = ? AND id_usuario = ?";
        $types = "ii";
        $params = [$gameId, $reviewerId];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function obtenerJuegosRevisor($reviewerId) {
        $query = "SELECT * FROM juegos WHERE id_juego in (SELECT id_juego from revisor_juego where usuario_id = $reviewerId";
        $datos = parent::obtenerDatos($query);

        if(isset($datos[0])) {
            return $datos;
        } else {
            return [];
        }
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
            if (is_array($reviewers)) {
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
            if (is_array($reviewers)) {
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
            } else {
                return $_respustas->response;
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

            return $_respustas->response;
        }
    }

    public function getJuegosRevisor($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_usuario'])) {
        } else {
            $reviewerId = $datos['id_usuario'];
            $juegos = $this->obtenerJuegosRevisor($reviewerId);
            if (is_array($juegos)) {
                $result = $_respustas->response;
                $result["result"] = $juegos;
                return $result;
            } else {
                return $_respustas->error_200("not_user");
            }
        }
    }


}