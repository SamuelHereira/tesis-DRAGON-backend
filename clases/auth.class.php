<?php
require_once 'conexion/conexion.php';
require_once 'conexion/respuestaGenerica.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
//require 'vendor/autoload.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
//Create an instance; passing `true` enables exceptions


class auth extends Conexion
{
    public function login($json)
    {

        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['usuario']) || !isset($datos["password"])) {
            return $_respustas->error_400();
        } else {
            $usuario = $datos['usuario'];
            $password = $datos['password'];
            $password = parent::encriptar($password);
            $datos = $this->obtenerDatosUsuario($usuario, $password);
            if ($datos) {
                //     //verificar si la contraseña es igual
                //         if($password == $datos[0]['Password']){
                //                 if($datos[0]['Estado'] == "Activo"){
                //                     //crear el token
                //$verificar  = $this->insertarToken($datos[0]['UsuarioId']);
                //                     if($verificar){
                //                             // si se guardo
                $result = $_respustas->response;
                $result["result"] = array(
                    "usuario" => $usuario,
                    "idUsuario" => $datos[0]['idUsuario'],
                    "migrado" => $datos[0]['mensaje']
                );
                return $result;
                //                     }else{
                //                             //error al guardar
                //                             return $_respustas->error_500("Error interno, No hemos podido guardar");
                //                     }
                //                 }else{
                //                     //el usuario esta inactivo
                //                     return $_respustas->error_200("El usuario esta inactivo");
                //                 }
                //         }else{
                //             //la contraseña no es igual
                //             return $_respustas->error_200("El password es invalido");
                //         }
            } else {
                return $_respustas->error_200("usuario_incorrecto");
            }
        }
    }

    public function CreacionUsuario($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['usuario']) || !isset($datos["password"]) || !isset($datos["correo"])) {
            return $_respustas->error_400();
        } else {
            $usuario = $datos['usuario'];
            $password = $datos['password'];
            $password = parent::encriptar($password);
            $correo = $datos['correo'];
            $registroFecha = date("Y-m-d H:i:s");
            if ($this->buscarUser($usuario) == 1) {
                return $_respustas->error_200("user_ocupado");
            }
            if ($this->buscarEmail($correo) == 1) {
                return $_respustas->error_200("correo_ocupado");
            }

            $datos = $this->crearCuenta($usuario, $correo, $password, $registroFecha);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "usuario" => $usuario
                );
                return $result;
            } else {
                return $_respustas->error_200("La creación de la cuenta no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
            }


        }
    }

    public function migracionUsuario($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['usuario']) || !isset($datos['nombre']) || !isset($datos["apellido"]) || !isset($datos["rol"]) || !isset($datos["fechaNacimiento"]) || !isset($datos["id"])) {
            return $_respustas->error_400();
        } else {
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
            $rol = $datos['rol'];
            $fechaNacimiento = $datos['fechaNacimiento'];
            $id = $datos['id'];
            $usuario = $datos['usuario'];
            $datos = $this->migracionCuenta($nombre, $apellido, $rol, $fechaNacimiento, $id, $usuario);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "proceso" => 'OK'
                );
                return $result;
            } else {
                return $_respustas->error_200("La migración no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
            }
        }
    }
    public function recuperarPassword($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['correo']) || !isset($datos['password']) || !isset($datos['password2'])) {
            return $_respustas->error_400();
        } else {
            $correo = $datos['correo'];
            $password = $datos['password'];
            $password = parent::encriptar($password);
            $password2 = $datos['password2'];
            $datos = $this->passwordCambiar($correo, $password, $password2);
            if ($datos) {
                $result = $_respustas->response;
                $result["result"] = array(
                    "proceso" => 'OK'
                );
                return $result;
            } else {
                return $_respustas->error_200("La migración no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
            }
        }
    }

    public function EnvioPassword($json)
    {
        $_respustas = new RespuestaGenerica;
        $datos = json_decode($json, true);
        if (!isset($datos['correo'])) {
            return $_respustas->error_400();
        } else {
            //$usuario = $datos['correo'];
            //$password = parent::encriptar($password);
            $correo = $datos['correo'];
            $id_envio = $this->enviarDatosEmail($correo);
            if ($id_envio == 1) {
                if ($datos) {
                    $result = $_respustas->response;
                    $result["result"] = "OK";
                    return $result;
                } else {
                    return $_respustas->error_200("La creación de la cuenta no fue exitosa. Por favor, inténtelo nuevamente más tarde.");
                }
            } else {
                if ($id_envio == 0)
                    return $_respustas->error_200("correo_not");
                else
                    return $_respustas->error_200("correo_not_bd");
            }


        }
    }



    private function obtenerDatosUsuario($usuario, $password)
    {
        $consulta = "CALL LOGIN(?, ?, @p_idUsuario, @p_mensaje)";
        $parametros = [
            ':p_usuario' => $usuario,
            ':p_password' => $password
        ];
        $datos = parent::obtenerDatosLogin($consulta, $parametros);
        if ($datos[0]["idUsuario"] != 0) {
            return $datos;
        } else {
            return 0;
        }
    }

    private function crearCuenta($usuario, $correo, $password, $registroFecha)
    {
        $query = "INSERT INTO usuarios (usuario,correo,password,registroFecha) VALUES ('$usuario', '$correo', '$password', '$registroFecha')";
        return parent::nonQuery($query);
    }
    private function buscarUser($usuario)
    {
        $query = "SELECT *from usuarios where usuario = '$usuario'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return 1;
        } else {
            return 0;
        }
    }
    private function buscarEmail($correo)
    {
        $query = "SELECT *from usuarios where correo = '$correo'";
        $datos = parent::obtenerDatos($query);
        if (isset($datos[0])) {
            return 1;
        } else {
            return 0;
        }
    }

    private function migracionCuenta($nombre, $apellido, $rol, $fechaNacimiento, $id, $usuario)
    {
        $query = "UPDATE usuarios set nombres = '$nombre', apellidos = '$apellido', rol = '$rol', fechaNacimiento = '$fechaNacimiento', alias = '$usuario' where idUsuario = $id";
        return parent::nonQuery($query);
    }

    private function passwordTemporal($correo, $password)
    {
        $query = "UPDATE usuarios set passwordRecuperar = '$password' where correo = '$correo'";
        return parent::nonQuery($query);
    }

    private function passwordCambiar($correo, $password, $password2)
    {
        $query = "UPDATE usuarios set password = '$password', passwordRecuperar = '' where correo = '$correo' and passwordRecuperar = '$password2'";
        return parent::nonQuery($query);
    }


    private function enviarDatosEmail($correo)
    {
        if ($this->buscarEmail($correo) == 1) {
            $mail = new PHPMailer(true);
            try {
                $password = $this->generar_contrasena_temporal();
                //Server settings
                $mail->SMTPDebug = 0;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host = 'smtp.gmail.com';                     //Set the SMTP server to send through
                $mail->SMTPAuth = true;                                   //Enable SMTP authentication
                $mail->Username = 'serious.game.ug@gmail.com';                     //SMTP username
                $mail->Password = 'dkvakqmywddzgjnb';                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
                $mail->Port = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                //Recipients
                $mail->setFrom('serious.game.ug@gmail.com', 'ADMINISTRADOR');
                $mail->addAddress($correo);     //Add a recipient
                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Restablecer contraseña';
                $mail->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Recuperación de Contraseña Temporal</title>
        </head>
        <body>
            <p>Hola ' . htmlspecialchars($correo) . ',</p>

            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Para garantizar la seguridad de tu cuenta, hemos generado una contraseña temporal para ti. A continuación encontrarás los detalles:</p>

            <p><strong>Contraseña Temporal:</strong> ' . htmlspecialchars($password) . '</p>

            <p>Si no solicitaste este cambio de contraseña, por favor, contacta con nuestro equipo de soporte inmediatamente para asegurar la seguridad de tu cuenta.</p>

            <p>Gracias por confiar en nosotros.</p>
        </body>
        </html>';
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                $mail->send();
                $temp = $this->passwordTemporal($correo, $password);
                return 1;
            } catch (Exception $e) {
                return 0;
            }
        } else {
            return 9;
        }

    }

    function generar_contrasena_temporal($longitud = 8)
    {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=<>?';
        $contrasena = '';
        $max_index = strlen($caracteres) - 1;

        for ($i = 0; $i < $longitud; $i++) {
            $contrasena .= $caracteres[random_int(0, $max_index)];
        }

        return $contrasena;
    }


    private function insertarToken($usuarioid)
    {
        $val = true;
        $token = bin2hex(openssl_random_pseudo_bytes(16, $val));
        $date = date("Y-m-d H:i");
        $estado = "Activo";
        $query = "INSERT INTO usuarios_token (UsuarioId,Token,Estado,Fecha)VALUES('$usuarioid','$token','$estado','$date')";
        $verifica = parent::nonQuery($query);
        if ($verifica) {
            return $token;
        } else {
            return 0;
        }
    }


}




?>