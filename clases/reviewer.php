<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';

class Reviewer  extends Conexion
{

      private function obtenerJuego($id_juego)
    {
        $query = "SELECT *FROM juegos WHERE id_juego = '$id_juego'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return $datos[0];
        } else {
            return 0;
        }
    }

    public function obtainValidReviewers($gameId, $role)
    {
     
        $query = "SELECT idUsuario, nombres, apellidos, usuario, correo
        FROM usuarios
        WHERE rol = '$role' 
        AND idUsuario NOT IN (
            SELECT id_usuario
            FROM revisor_juego
            WHERE id_juego = $gameId
        )
        AND idUsuario NOT IN (
            SELECT id_profesor
            FROM juegos
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
        $query = "SELECT u.idUsuario, u.nombres, u.apellidos, u.usuario, u.correo, u.rol
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
        $query = "SELECT 
                    rj.id_revisor_juego,
                    j.id_juego,
                    j.fecha_creacion,
                    j.fecha_finalizacion,
                    j.id_profesor,
                    concat(u.nombres, ' ', u.apellidos) as profesor,
                    (SELECT COUNT(*) from revision_revisor_juego WHERE id_revisor_juego = rj.id_revisor_juego) as total_revision,
                    j.json
                    FROM revisor_juego rj
                    JOIN juegos j ON rj.id_juego = j.id_juego
                    JOIN usuarios u ON j.id_profesor = u.idUsuario
                    WHERE id_usuario = $reviewerId
                    ";
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
            $role = $datos['rol'] ?? 'e';
            $reviewers = $this->obtainValidReviewers($gameId, $role);
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

    public function obtenerJuegoRevisor($reviewerId)
    {
        $query = "SELECT 
                    rj.id_revisor_juego,
                    j.id_juego,
                    j.fecha_creacion,
                    j.fecha_finalizacion,
                    j.id_profesor,
                    concat(u.nombres, ' ', u.apellidos) as profesor,
                    (SELECT COUNT(*) from revision_revisor_juego WHERE id_revisor_juego = rj.id_revisor_juego) as total_revision,
                    j.json,
                    (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                        'id_revision_revisor_juego', id_revision_revisor_juego,
                        'id_revisor_juego', id_revisor_juego,
                        'id_requerimiento', id_requerimiento,
                        'titulo', titulo,
                        'retroalimentacion', retroalimentacion,
                        'tipo', tipo,
                        'fecha_revision', fecha_revision
                        )
                        )
                        FROM revision_revisor_juego
                        WHERE id_revisor_juego = rj.id_revisor_juego
                    ) AS revisiones,
                      (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id_revision_profesor', rp.id_revision_profesor,
                            'id_revision_revisor_juego', rp.id_revision_revisor_juego,
                            'id_revisor_juego', rp.id_revisor_juego,
                            'aprobado', rp.aprobado,
                            'retroalimentacion', rp.retroalimentacion,
                            'fecha_revision', rp.fecha_revision
                        )
                        )
                        FROM revision_profesor rp
                        JOIN revision_revisor_juego rrj ON rp.id_revision_revisor_juego = rrj.id_revision_revisor_juego
                        WHERE rrj.id_revisor_juego = $reviewerId
                    ) AS revisiones_profesor
                    FROM revisor_juego rj
                    JOIN juegos j ON rj.id_juego = j.id_juego
                    JOIN usuarios u ON j.id_profesor = u.idUsuario
                    WHERE id_revisor_juego = $reviewerId
                    ";
        $datos = parent::obtenerDatos($query);

        if(isset($datos[0])) {
            return $datos[0];
        } else {
            return null;
        }
    }

    public function obtenerJuegoProfesorRevisor($reviewerId)
    {
        $query =  $query = "SELECT 
                                rj.id_revisor_juego,
                                j.id_juego,
                                j.fecha_creacion,
                                j.fecha_finalizacion,
                                j.id_profesor,
                                CONCAT(u.nombres, ' ', u.apellidos) AS profesor,
                                j.json
                                -- (
                                --     SELECT JSON_ARRAYAGG(
                                --         JSON_OBJECT(
                                --             'id_revision_revisor_juego', rrj.id_revision_revisor_juego,
                                --             'id_revisor_juego', rrj.id_revisor_juego,
                                --             'id_requerimiento', rrj.id_requerimiento,
                                --             'titulo', rrj.titulo,
                                --             'retroalimentacion', rrj.retroalimentacion,
                                --             'tipo', rrj.tipo,
                                --             'fecha_revision', rrj.fecha_revision,
                                --             'no_feedback', rrj.no_feedback
                                --         )
                                --     )
                                --     FROM revision_revisor_juego rrj
                                --     WHERE rrj.id_requerimiento IN (
                                --         SELECT r.id_requerimientos
                                --         FROM requerimientos r
                                --         WHERE JSON_CONTAINS(j.json, JSON_QUOTE(CAST(r.id_requerimientos AS CHAR)), '$[*].id_requerimientos')
                                --     )
                                -- ) AS revisiones
                            FROM revisor_juego rj
                            JOIN juegos j ON rj.id_juego = j.id_juego
                            JOIN usuarios u ON j.id_profesor = u.idUsuario
                            WHERE rj.id_revisor_juego = $reviewerId";
    
        $datos = parent::obtenerDatos($query);

        if(isset($datos[0])) {
            return $datos[0];
        } else {
            return null;
        }
    }

    public function obtenerProfesorRevisionesRequerimiento($idRevisorJuego, $idRequerimiento) {
        $query = "SELECT 
                    rrj.id_revision_revisor_juego,
                    rrj.id_revisor_juego,
                    rrj.id_requerimiento,
                    rrj.titulo,
                    rrj.retroalimentacion,
                    rrj.tipo,
                    rrj.fecha_revision,
                    rrj.no_feedback,
                    concat(u.nombres, ' ', u.apellidos) as estudiante, 

                    -- OBTENER INFO DE LA PREGUNTA DEL JUEGO
                    (SELECT j.json
                    FROM juegos j
                    WHERE j.id_juego = (SELECT id_juego FROM revisor_juego WHERE id_revisor_juego = $idRevisorJuego)
                    ) AS pregunta_info,
                    (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id_revision_profesor', id_revision_profesor,
                            'id_revision_revisor_juego', id_revision_revisor_juego,
                            'id_revisor_juego', id_revisor_juego,
                            'aprobado', aprobado,
                            'retroalimentacion', retroalimentacion,
                            'fecha_revision', fecha_revision
                        )
                        )
                        FROM revision_profesor rp
                        WHERE id_revision_revisor_juego = rrj.id_revision_revisor_juego 
                        AND id_revisor_juego = $idRevisorJuego

                    ) AS revisiones
                  FROM revision_revisor_juego rrj
                  JOIN revisor_juego rj ON rrj.id_revisor_juego = rj.id_revisor_juego
                  JOIN juegos j ON rj.id_juego = j.id_juego
                  JOIN usuarios u ON rj.id_usuario = u.idUsuario
                  WHERE id_requerimiento = $idRequerimiento AND j.id_JUEGO = (SELECT id_juego FROM revisor_juego WHERE id_revisor_juego = $idRevisorJuego)";

        return parent::obtenerDatos($query);
    }

        // id_revision_profesor INT NOT NULL AUTO_INCREMENT,
        // id_revision_revisor_juego INT NOT NULL,
        // id_revisor_juego INT NOT NULL,
        // aprobado INT NOT NULL,
        // retroalimentacion VARCHAR(500) NULL,
        // fecha_revision DATETIME NOT NULL,
    public function revisarPorProfesor($idRevisionRevisorJuego, $idRevisorJuego, $aprobado, $feedback) {

        $fechaRevision = date('Y-m-d H:i:s');
        $query = "INSERT INTO revision_profesor (id_revision_revisor_juego, id_revisor_juego, aprobado, retroalimentacion, fecha_revision) VALUES (?, ?, ?, ?, ?)";
        $types = "iiiss";
        $params = [$idRevisionRevisorJuego, $idRevisorJuego, $aprobado, $feedback, $fechaRevision];

        return $this->nonQueryIdParams($query, $types, $params);
    }



    public function getJuegoRevisor($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_revisor_juego'])) {
            return $_respustas->error_400("El campo 'id_revisor_juego' es requerido.");
        } else {
            $revisorJuegoId = $datos['id_revisor_juego'];
            $juego = $this->obtenerJuegoRevisor($revisorJuegoId);

            $revisiones = $juego['revisiones'] ?? "[]";
            
            if($juego) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "id_revisor_juego" => $juego['id_revisor_juego'],
                    "id_juego" => $juego['id_juego'],
                    "fecha_creacion" => $juego['fecha_creacion'],
                    "fecha_finalizacion" => $juego['fecha_finalizacion'],
                    "id_profesor" => $juego['id_profesor'],
                    "profesor" => $juego['profesor'],
                    "total_revision" => $juego['total_revision'],
                    "json" => json_decode($juego['json'], true)[0],
                    "revisiones" => json_decode($revisiones, true),
                    "revisiones_profesor" => is_null($juego['revisiones_profesor']) ? [] : json_decode($juego['revisiones_profesor'], true)
                );
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
        }
    }

    public function getJuegoProfesorRevisor($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_revisor_juego'])) {
            return $_respustas->error_400("El campo 'id_revisor_juego' es requerido.");
        } else {
            $revisorJuegoId = $datos['id_revisor_juego'];
            $juego = $this->obtenerJuegoProfesorRevisor($revisorJuegoId);

            $revisiones = $juego['revisiones'] ?? "[]";
            
            if($juego) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "id_revisor_juego" => $juego['id_revisor_juego'],
                    "id_juego" => $juego['id_juego'],
                    "fecha_creacion" => $juego['fecha_creacion'],
                    "fecha_finalizacion" => $juego['fecha_finalizacion'],
                    "id_profesor" => $juego['id_profesor'],
                    "profesor" => $juego['profesor'],
                    "json" => json_decode($juego['json'], true)[0],
                    "revisiones" => json_decode($revisiones, true)
                );
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
        }
    }




    public function revisarRequerimientoJuego($idRevisorJuego, $idRequerimiento, $titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback) {
        $query = "INSERT INTO revision_revisor_juego (id_revisor_juego, id_requerimiento, titulo, retroalimentacion, tipo, fecha_revision, no_feedback) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $types = "iissssi";
        $params = [$idRevisorJuego, $idRequerimiento, $titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function actualizarRevisionRequerimientoJuego($idRevisorJuego, $idRequerimiento, $titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback) {
        $query = "UPDATE revision_revisor_juego SET titulo = ?, retroalimentacion = ?, tipo = ?, fecha_revision = ?, no_feedback = ? WHERE id_revisor_juego = ? AND id_requerimiento = ?";
        $types = "ssssiis";
        $params = [$titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback, $idRevisorJuego, $idRequerimiento];

        return $this->nonQueryIdParams($query, $types, $params);
    }

    public function postRevisarRequerimientoJuego($json) {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_revisor_juego']) || !isset($datos['id_requerimiento']) || !isset($datos['retroalimentacion'])) {
            return $_respustas->error_400("Los campos 'id_revisor_juego', 'id_requerimiento', 'retroalimentacion' son requeridos.");
        } else {
            $idRevisorJuego = $datos['id_revisor_juego'];
            $idRequerimiento = $datos['id_requerimiento'];
            $titulo = $datos['titulo'];
            $retroalimentacion = $datos['retroalimentacion'];
            $tipo = $datos['tipo'];
            $fechaRevision = date('Y-m-d H:i:s');
            $noFeedback = isset($datos['no_feedback']) ? 1 : 0;

            if(isset($datos['id_revision'])) {
                $result = $this->actualizarRevisionRequerimientoJuego($idRevisorJuego, $idRequerimiento, $titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback);
            } else {    
                $result = $this->revisarRequerimientoJuego($idRevisorJuego, $idRequerimiento, $titulo, $retroalimentacion, $tipo, $fechaRevision, $noFeedback); 
            }
            
            
            $result = $_respustas->response;
            $result["result"] = "OK";
            return $result;
        }
    }

    public function getProfesorRevisionesRequerimiento($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_revisor_juego']) || !isset($datos['id_requerimiento'])) {
            return $_respustas->error_400("Los campos 'id_revisor_juego' y 'id_requerimiento' son requeridos.");
        } else {
            $revisorJuegoId = $datos['id_revisor_juego'];
            $requerimientoId = $datos['id_requerimiento'];
            $revisiones = $this->obtenerProfesorRevisionesRequerimiento($revisorJuegoId, $requerimientoId);
            foreach ($revisiones as $key => $revision)  {
                if (isset($revision['revisiones']) && !is_null($revision['revisiones'])) {
                    $revision['revisiones'] = json_decode($revision['revisiones'], true) ?? [];
                    
                } else {
                    $revision['revisiones'] = [];
                }

                if (isset($revision['pregunta_info']) && !is_null($revision['pregunta_info'])) {
                    $revision['pregunta_info'] = json_decode($revision['pregunta_info'], true) ?? [];
                    // del primer elemento del json ir a requerimientos y obtener el qye tenga id = al id_requerimiento
                    $preguntaInfo = $revision['pregunta_info'];
                    $requerimientos = $preguntaInfo[0]['requerimientos'] ?? [];
                    $requerimientoInfo = array_filter($requerimientos, function($req) use ($requerimientoId) {
                        return $req['id'] == $requerimientoId;
                    });
                    $requerimientoInfo = array_values($requerimientoInfo);
                    if (isset($requerimientoInfo[0])) {
                        $revision['pregunta_info'] = $requerimientoInfo[0];
                    } else {
                        $revision['pregunta_info'] = [];
                    }
                } 

                $revisiones[$key] = $revision;
            }




            if (is_array($revisiones)) {
                $result = $_respustas->response;
                $result["result"] = $revisiones;
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
        }
    }

    public function postRevisarPorProfesor($json) {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_revision_revisor_juego']) || !isset($datos['id_revisor_juego']) || !isset($datos['feedback'])) {
            return $_respustas->error_400("Los campos 'id_revision_revisor_juego', 'id_revisor_juego', 'feedback' son requeridos.");
        } else {
            $idRevisionRevisorJuego = $datos['id_revision_revisor_juego'];
            $idRevisorJuego = $datos['id_revisor_juego'];
            $feedback = $datos['feedback'];
            $aprobado = $datos['aprobado'] ?? 0;

            $result = $this->revisarPorProfesor($idRevisionRevisorJuego, $idRevisorJuego, $aprobado, $feedback);

            $result = $_respustas->response;
            $result["result"] = "OK";
            return $result;
        }
    }

    public function obtenerReporteRevisionesPorRequerimiento($json) {
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego'])) {
            return (new RespuestaGenerica)->error_400("El campo 'id_juego' es requerido.");
        }
        $id_juego = $datos['id_juego'];

        $_respustas = new RespuestaGenerica;

        // 1. Obtener el JSON del juego
        $juego = $this->obtenerJuego($id_juego);
        if (!$juego || empty($juego['json'])) {
            return $_respustas->error_200("JUEGO_NO_ENCONTRADO");
        }

        $niveles = json_decode($juego['json'], true);
        if (!is_array($niveles) || !isset($niveles[0]['requerimientos'])) {
            return $_respustas->error_200("JSON_INVALIDO");
        }

        $reporte = [];

        foreach ($niveles[0]['requerimientos'] as $req) {
            $idReq = $req['id'];
            $titulo = $req['requerimiento'];
            $tipo = $req['opcionRequerimiento'];
            $retroalimentacion = $req['retroalimentacion'] ?? '';

            // 2. Obtener revisiones asociadas a este id_requerimiento
            $query = "SELECT 
                        CONCAT(u.nombres, ' ', u.apellidos) AS revisor,
                        rrj.retroalimentacion,
                        rrj.titulo,
                        rrj.tipo,
                        rrj.no_feedback,
                        rrj.fecha_revision
                    FROM revision_revisor_juego rrj
                    JOIN revisor_juego rj ON rrj.id_revisor_juego = rj.id_revisor_juego
                    JOIN usuarios u ON rj.id_usuario = u.idUsuario
                    WHERE rj.id_juego = $id_juego AND rrj.id_requerimiento =$idReq";

            $datosRevisiones = parent::obtenerDatos($query);
            $revisiones = [];
            foreach ($datosRevisiones as $revision) {
                $revisiones[] = $revision;
            }
            // 3. Construir el reporte
        

            $reporte[] = [
                'id_requerimiento' => $idReq,
                'titulo' => $titulo,
                'tipo_requerimiento' => $tipo,
                'retroalimentacion' => $retroalimentacion,
                'revisiones' => $revisiones
            ];
            $juego['requerimientos'] = $reporte;
        }
        // eliminar el campo json del juego
        unset($juego['json']);
        $response = $_respustas->response;
        $response["result"] = $juego;
        return $response;
    }

  public function obtenerReporteRevisionesProfesoresPorRevision($json) {
    $datos = json_decode($json, true);
    if (!isset($datos['id_juego'])) {
        return (new RespuestaGenerica)->error_400("El campo 'id_juego' es requerido.");
    }

    $id_juego = (int)$datos['id_juego'];
    $_respustas = new RespuestaGenerica;

    // Obtener el JSON del juego
    $juego = $this->obtenerJuego($id_juego);
    if (!$juego || empty($juego['json'])) {
        return $_respustas->error_200("JUEGO_NO_ENCONTRADO");
    }

    $niveles = json_decode($juego['json'], true);
    if (!is_array($niveles) || empty($niveles) || !isset($niveles[0]['requerimientos'])) {
        return $_respustas->error_200("JSON_INVALIDO");
    }

    $reporte = [];

    foreach ($niveles[0]['requerimientos'] as $req) {
        $idReq = (int)$req['id'];
        $titulo = $req['requerimiento'];
        $tipo = $req['opcionRequerimiento'];
        $retroalimentacion = $req['retroalimentacion'] ?? '';

        // Consulta SQL solo para ese requerimiento
        $query = "SELECT 
                    CONCAT(u.nombres, ' ', u.apellidos) AS revisor,
                    rrj.retroalimentacion,
                    rrj.titulo,
                    rrj.tipo,
                    rrj.no_feedback,
                    rrj.fecha_revision,
                    rrj.id_revision_revisor_juego,
                    rrj.id_revisor_juego,
                    rrj.id_requerimiento,
                    (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id_revision_profesor', rp.id_revision_profesor,
                            'id_revision_revisor_juego', rp.id_revision_revisor_juego,
                            'id_revisor_juego', rp.id_revisor_juego,
                            'aprobado', rp.aprobado,
                            'retroalimentacion', rp.retroalimentacion,
                            'fecha_revision', rp.fecha_revision,
                            'revisor', CONCAT(u.nombres, ' ', u.apellidos)
                        )
                    )
                    FROM revision_profesor rp
                    JOIN revisor_juego rj ON rp.id_revisor_juego = rj.id_revisor_juego
                    JOIN usuarios u ON rj.id_usuario = u.idUsuario
                    WHERE rp.id_revision_revisor_juego = rrj.id_revision_revisor_juego
                    ) AS revisiones_profesores 
                FROM revision_revisor_juego rrj
                JOIN revisor_juego rj ON rrj.id_revisor_juego = rj.id_revisor_juego
                JOIN usuarios u ON rj.id_usuario = u.idUsuario
                WHERE rj.id_juego = $id_juego AND rrj.id_requerimiento = $idReq";

        $datosRevisiones = parent::obtenerDatos($query);
        $revisiones = [];

        foreach ($datosRevisiones as $revision) {
            // Decodificar revisiones_profesores si existen
            if (isset($revision['revisiones_profesores']) && !is_null($revision['revisiones_profesores'])) {
                $revision['revisiones_profesores'] = json_decode($revision['revisiones_profesores'], true) ?? [];
            } else {
                $revision['revisiones_profesores'] = [];
            }
            $revisiones[] = $revision;
        }

        $reporte[] = [
            'id_requerimiento' => (string)$idReq,
            'titulo' => $titulo,
            'tipo_requerimiento' => $tipo,
            'retroalimentacion' => $retroalimentacion,
            'revisiones' => $revisiones
        ];
        $juego['requerimientos'] = $reporte;
    }

    $response = $_respustas->response;
    $response["result"] = $juego;
    return $response;
}

    public function obtenerReporteRevisionesPorRequerimientoYEstudiante($json) {
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego'])) {
            return (new RespuestaGenerica)->error_400("El campo 'id_juego' es requerido.");
        }
        $id_juego = $datos['id_juego'];
        if (!isset($datos['id_estudiante'])) {
            return (new RespuestaGenerica)->error_400("El campo 'id_estudiante' es requerido.");
        }
        $id_estudiante = (int)$datos['id_estudiante'];

        $_respustas = new RespuestaGenerica;

        // 1. Obtener el JSON del juego
        $juego = $this->obtenerJuego($id_juego);
        if (!$juego || empty($juego['json'])) {
            return $_respustas->error_200("JUEGO_NO_ENCONTRADO");
        }

        $niveles = json_decode($juego['json'], true);
        if (!is_array($niveles) || !isset($niveles[0]['requerimientos'])) {
            return $_respustas->error_200("JSON_INVALIDO");
        }

        $reporte = [];

        foreach ($niveles[0]['requerimientos'] as $req) {
            $idReq = $req['id'];
            $titulo = $req['requerimiento'];
            $tipo = $req['opcionRequerimiento'];
            $retroalimentacion = $req['retroalimentacion'] ?? '';

            // 2. Obtener revisiones asociadas a este id_requerimiento
            $query = "SELECT 
                        CONCAT(u.nombres, ' ', u.apellidos) AS revisor,
                        rrj.retroalimentacion,
                        rrj.titulo,
                        rrj.tipo,
                        rrj.no_feedback,
                        rrj.fecha_revision
                    FROM revision_revisor_juego rrj
                    JOIN revisor_juego rj ON rrj.id_revisor_juego = rj.id_revisor_juego
                    JOIN usuarios u ON rj.id_usuario = u.idUsuario
                    WHERE rj.id_juego = $id_juego AND rrj.id_requerimiento =$idReq
                    AND rj.id_usuario = $id_estudiante";

            $datosRevisiones = parent::obtenerDatos($query);
            $revisiones = [];
            foreach ($datosRevisiones as $revision) {
                $revisiones[] = $revision;
            }
            // 3. Construir el reporte
        

            $reporte[] = [
                'id_requerimiento' => $idReq,
                'titulo' => $titulo,
                'tipo_requerimiento' => $tipo,
                'retroalimentacion' => $retroalimentacion,
                'revisiones' => $revisiones
            ];
            $juego['requerimientos'] = $reporte;
        }
        // eliminar el campo json del juego
        unset($juego['json']);
        $response = $_respustas->response;
        $response["result"] = $juego;
        return $response;
    }

}