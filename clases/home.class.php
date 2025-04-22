<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';


class home extends Conexion
{

    public function convertirFechaISOaMySQL($fechaISO) {
        return date('Y-m-d H:i:s', strtotime($fechaISO));
    }

    public function getUsuario($json)
    {

        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['idUsuario']) || !isset($datos["usuario"])) {
            return $_respustas->error_400();
        } else {
            $usuario = $datos['usuario'];
            $idUsuario = $datos['idUsuario'];
            $datos = $this->obtenerUsuarioRegistro($idUsuario, $usuario);
            if ($datos) {

                $result = $_respustas->response;
                $result["result"] = array(
                    "registro" => $datos["registroLogin"],
                    "rol" => $datos["rol"],
                    "isRevisor" => $datos["isRevisor"]
                );
                return $result;
            } else {
                return $_respustas->error_200("user_false");
            }
        }
    }

    public function getDatosUsuarios($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['idUsuario']) || !isset($datos["usuario"])) {
            return $_respustas->error_400();
        } else {
            $usuario = $datos['usuario'];
            $idUsuario = $datos['idUsuario'];
            $datos = $this->obtenerDatosUsuarios($idUsuario, $usuario);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "usuario" => $datos["usuario"],
                    "nombres" => $datos["nombres"],
                    "apellidos" => $datos["apellidos"],
                    "correo" => $datos["correo"],
                    "rol" => $datos["rol"],
                    "fechaNacimiento" => $datos["fechaNacimiento"],
                );
                return $result;
            } else {
                return $_respustas->error_200("user_false");
            }
        }
    }

    public function getJuego($json)
    {

        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id'])) {
            return $_respustas->error_400();
        } else {
            $id = $datos['id'];
            $idUsuario = $datos['idusuario'];
            $datos1 = $this->verificarJuego($idUsuario, $id);
            if ($datos1 == 0) {
                $datos = $this->obtenerJuego($id);
                if ($datos) {

                    $result = $_respustas->response;
                    $result["result"] = array(
                        "fecha_creacion" => $datos["fecha_creacion"],
                        "fecha_finalizacion" => $datos["fecha_finalizacion"],
                        "estado" => $datos["estado"],
                        "id_tipo_juego" => $datos["id_tipo_juego"],
                        "num_requerimientos_aleatorios" => $datos["num_requerimientos_aleatorios"],
                        "json" => $datos["json"],
                    );
                    return $result;
                } else {
                    return $_respustas->error_200("not_game");
                }
            } else {
                return $_respustas->error_200("not_game_used");
            }

        }
    }

    public function closeJuego($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_juego']) || !isset($datos['id_profesor'])) {
            return $_respustas->error_400();
        } else {
            $id_juego = $datos['id_juego'];
            $id_profesor = $datos['id_profesor'];

            $datos = $this->cerrarJuego($id_juego, $id_profesor);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = 'OK';
                return $result;
            } else {
                return $_respustas->error_200("La creación del juego no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
            }
        }
    }

    public function getJuegosPublicos($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        $datos = $this->obtenerJuegoPublicos();
        if ($datos) {
            $result = $_respustas->response;
            $result["result"] = $datos;
            return $result;
        } else {
            return $_respustas->error_200("not_game");
        }
    }
    public function getJuegosProfesor($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id'])) {
            return $_respustas->error_400();
        } else {
            $id = $datos['id'];
            $datos = $this->obtenerJuegoProfesor($id);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = $datos;
                return $result;
            } else {
                return $_respustas->error_200("not_game");
            }
        }
    }

    public function getRequerimientos()
    {
        $_respustas = new RespuestaGenerica;
        $datos = $this->obtenerRequerimientos();
        if ($datos) {
            $result = $_respustas->response;
            $result["result"] = $datos;
            return $result;
        } else {
            return $_respustas->error_200("not_requerimientos");
        }
    }
    public function getJugadosJugados($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        $datos = $this->obternerJuegosJugados($datos['id']);
        if ($datos) {
            $result = $_respustas->response;
            $result["result"] = $datos;
            return $result;
        } else {
            return $_respustas->error_200("not_juegos");
        }
    }

    public function postCreateJuego($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_profesor']) || !isset($datos["fechaCreacion"]) || !isset($datos["fechaFinilizacion"]) || !isset($datos["json"]) || !isset($datos["id_tipo_juego"])) {
            return $_respustas->error_400();
        } else {
            $usuario = $datos['id_profesor'];
            $fecha_creacion = $datos['fechaCreacion'];
            $fecha_finalizacion = $datos['fechaFinilizacion'];
            $json_data = $datos['json'];
            $id_tipo_juego = $datos['id_tipo_juego'];
            $num_requerimientos_aleatorios = $datos['num_requerimientos_aleatorios'];


            $fechaCreacion = $this->convertirFechaISOaMySQL($fecha_creacion);
            $fechaFinalizacion = $this->convertirFechaISOaMySQL($fecha_finalizacion);

            $datos = $this->crearJuego($usuario, $fechaCreacion, $fechaFinalizacion, $json_data, $id_tipo_juego, $num_requerimientos_aleatorios);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "id_juego" => $datos
                );
                return $result;
            } else {
                return $_respustas->error_200("La creación del juego no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
            }
        }
    }

    public function postRequerimientos($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        $datos = $this->guardarRequerimientos($datos['requisitos']);
        if (isset($datos[0])) {
            $result = $_respustas->response;
            $result["result"] = $datos;
            return $result;
        } else {
            return $_respustas->error_200("error_guardarTodos");
        }
    }

    public function postPuntaje($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_persona']) || !isset($datos["id_juego"]) || !isset($datos["puntaje"]) || !isset($datos["hora_inicio"]) || !isset($datos["hora_fin"])) {
            return $_respustas->error_400();
        } else {
            $id_persona = $datos['id_persona'];
            $id_juego = $datos['id_juego'];
            $puntaje = $datos['puntaje'];
            $hora_inicio = $datos['hora_inicio'];
            $hora_fin = $datos['hora_fin'];
            $aciertos = $datos['aciertos'];
            $errores = $datos['errores'];

            $datos = $this->crearPuntaje($id_persona, $id_juego, $puntaje, $hora_inicio, $hora_fin, $aciertos, $errores);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = $datos[0]["mensaje"];
                return $result;
            } else {
                return $_respustas->error_200("La creación la puntación fue incorrecta");
            }
        }
    }

    public function postEditarUsuario($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_persona']) || !isset($datos["password"]) || !isset($datos["nombres"]) || !isset($datos["apellidos"]) || !isset($datos["fechaN"]) || !isset($datos["new_password"])) {
            return $_respustas->error_400();
        } else {
            $id_persona = $datos['id_persona'];
            $password = parent::encriptar($datos['password']);
            $nombres = $datos['nombres'];
            $apellidos = $datos['apellidos'];
            $fechaN = $datos['fechaN'];
            if (empty($datos['new_password'])) {
                $new_password = $datos['new_password'];
            } else {
                $new_password = parent::encriptar($datos['new_password']);
            }

            $datos = $this->editarPerfil($id_persona, $password, $nombres, $apellidos, $fechaN, $new_password);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = $datos[0]["mensaje"];
                return $result;
            } else {
                return $_respustas->error_200("La edición del usuaruo fue incorrecta. Intenlo más tarde");
            }
        }
    }

    public function getDatosReporte($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['id_usuario']) || !isset($datos["id_juego"])) {
            return $_respustas->error_400();
        } else {
            $id_usuario = $datos['id_usuario'];
            $id_juego = $datos['id_juego'];
            $datos = $this->obtenerDatosReportes($id_usuario, $id_juego);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "data" => $datos[0]['data'],
                    "mensaje" => $datos[0]['mensaje']
                );
                return $result;
            } else {
                return $_respustas->error_200("DATA_INCORRECTA");
            }
        }
    }

    private function crearPuntaje($id_persona, $id_juego, $puntaje, $hora_inicio, $hora_fin, $aciertos, $errores)
    {
        $consulta = "CALL ASIGNAR_PUNTAJE(?, ?, ?, ?, ?, ?, ?, @p_mensaje)";
        $parametros = [
            ':p_id_persona' => $id_persona,
            ':p_id_juego' => $id_juego,
            ':p_puntaje' => $puntaje,
            ':p_aciertos' => $aciertos,
            ':p_errores' => $errores,
            ':p_hora_inicio' => $hora_inicio,
            ':p_hora_fin' => $hora_fin
        ];
        $datos = parent::obtenerDatosMensaje($consulta, $parametros);
        if ($datos[0]["mensaje"] != 0) {
            return $datos;
        } else {
            return 0;
        }
    }

    private function obtenerUsuarioRegistro($id_usuario, $usuario)
    {
        $query = "SELECT registroLogin, rol, 
            EXISTS (
                SELECT 1 
                FROM revisor_juego r 
                WHERE r.id_usuario = u.idUsuario
            ) AS isRevisor 
        FROM usuarios u WHERE idUsuario = '$id_usuario' AND usuario = '$usuario'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0]["registroLogin"])) {
            return $datos[0];
        } else {
            return 0;
        }
    }

    private function obtenerDatosUsuarios($id_usuario, $usuario)
    {
        $query = "SELECT usuario, nombres, apellidos, correo, rol, fechaNacimiento FROM usuarios WHERE idUsuario = '$id_usuario' AND usuario = '$usuario'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0]["usuario"])) {
            return $datos[0];
        } else {
            return 0;
        }
    }

    private function crearJuego($id_usuario, $fecha_creacion, $fecha_finalizacion, $json, $id_tipo_juego, $num_requerimientos_aleatorios)
    {
        $query = "INSERT INTO juegos (id_profesor, fecha_creacion, fecha_finalizacion, json, id_tipo_juego, num_requerimientos_aleatorios) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$id_usuario, $fecha_creacion, $fecha_finalizacion, $json, $id_tipo_juego, $num_requerimientos_aleatorios];
        $types = "isssii"; // tipos de los parámetros: i = integer, s = string        
        return $this->nonQueryIdParams($query, $types, $params);
    }
    private function cerrarJuego($id_juego, $id_profesor)
    {
        $query = "UPDATE juegos SET estado = 0 where id_profesor = $id_profesor and id_juego = $id_juego";
        return parent::nonQuery($query);
    }

    private function editarPerfil($id_persona, $password, $nombres, $apellidos, $fechaN, $new_password)
    {
        $consulta = "CALL EDITAR_USUARIO(?, ?, ?, ?, ?, ?, @p_mensaje)";
        $parametros = [
            ':p_nombres' => $nombres,
            ':p_id_usuario' => $id_persona,
            ':p_fechaN' => $fechaN,
            ':p_apellidos' => $apellidos,
            ':p_password' => $password,
            ':p_new_password' => $new_password
        ];
        $datos = parent::obtenerDatosMensaje($consulta, $parametros);
        if ($datos[0]["mensaje"] != 0) {
            return $datos;
        } else {
            return 0;
        }
    }

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

    private function verificarJuego($id, $id_juego)
    {
        $query = "SELECT *FROM puntaje_juego WHERE id_persona = $id and id_juego = $id_juego";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return 1;
        } else {
            return 0;
        }
    }

    private function obtenerJuegoPublicos()
    {
        $query = "SELECT *FROM juegos WHERE juego_publico = 'S'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
    }
    private function obtenerJuegoProfesor($id_profesor)
    {
        $query = "SELECT *FROM juegos WHERE id_profesor = $id_profesor";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
    }

    private function obtenerRequerimientos()
    {
        $query = "SELECT *FROM requerimientos WHERE estado = 'A'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
    }

    private function obtenerDatosReportes($id_usuario, $id_juego)
    {
        $consulta = "CALL OBTENER_REPORTE_JUEGO(?, ?, @p_data, @p_mensaje)";
        $parametros = [
            ':p_id_usuario' => $id_usuario,
            ':p_id_juego' => $id_juego
        ];
        $datos = parent::obtenerProcedimientoAlmacendao($consulta, $parametros);
        if ($datos[0]["mensaje"] != 0) {
            return $datos;
        } else {
            return 0;
        }
    }

    private function obternerJuegosJugados($id_usuario)
    {
        $query = "SELECT *FROM puntaje_juego WHERE id_persona = $id_usuario";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return $datos;
        } else {
            return 0;
        }
        
    }

}




?>