<?php
require_once 'clases/home.class.php';
require_once 'clases/reviewer.php';
require_once 'clases/conexion/respuestaGenerica.php';

require_once 'clases/env.php'; // Asegúrate de que este archivo existe y contiene la función loadEnv

loadEnv(__DIR__ . '/.env'); // Carga el archivo .env desde el directorio actual

$_auth = new home;
$_reviewer = new Reviewer;
$_respuestas = new RespuestaGenerica;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    // $postBody = file_get_contents("php://input");

    // $datosArray = $_auth->getUsuario($postBody);

    // header('Content-Type: application/json');
    // if($datosArray["code"] != "200"){
    //     $responseCode = $datosArray["code"];
    //     http_response_code($responseCode);
    // }
    // else{
    //     http_response_code(200);
    // }
    // echo json_encode($datosArray);
    $postBody = file_get_contents("php://input");
    $datosArray = [];
    $requestData = json_decode($postBody, true);

    if (isset($requestData['action'])) {
        switch ($requestData['action']) {
            case 'getUsuarios':
                $datosArray = $_auth->getUsuario($postBody);
                break;
            case 'crearJuego':
                $datosArray = $_auth->postCreateJuego($postBody);
                break;
            case 'obtenerJuego':
                $datosArray = $_auth->getJuego($postBody);
                break;
            case 'obtenerJuegosProfesor':
                $datosArray = $_auth->getJuegosProfesor($postBody);
                break;
            case 'obtenerJuegoPublicos':
                $datosArray = $_auth->getJuegosPublicos($postBody);
                break;
            case 'guardarRequerimientos':
                $datosArray = $_auth->postRequerimientos($postBody);
                break;
            case 'obtenerRequerimientos':
                $datosArray = $_auth->getRequerimientos();
                break;
            case 'guardarPuntaje':
                $datosArray = $_auth->postPuntaje($postBody);
                break;
            case 'editarPerfil':
                $datosArray = $_auth->postEditarUsuario($postBody);
                break;
            case 'obtenearDatosUsuarios':
                $datosArray = $_auth->getDatosUsuarios($postBody);
                break;
            case 'cerrarJuego':
                $datosArray = $_auth->closeJuego($postBody);
                break;
            case 'getDatosReporte':
                $datosArray = $_auth->getDatosReporte($postBody);
                break;
            case 'getJuegosJugados':
                $datosArray = $_auth->getJugadosJugados($postBody);
                break;
            
            // Revisores
            case 'obtenerRevisoresValidos':
                $datosArray = $_reviewer->getValidReviewers($postBody);
                break;

            case 'obtenerRevisores':
                $datosArray = $_reviewer->getReviewers($postBody);
                break;
            
            case 'asignarRevisor':
                $datosArray = $_reviewer->postAddReviewer($postBody);
                break;
            case 'eliminarRevisor':
                $datosArray = $_reviewer->postRemoveReviewer($postBody);
                break;
            case 'getJuegosRevisor': 
                $datosArray = $_reviewer->getJuegosRevisor($postBody);
                break;

            case 'obtenerJuegoRevisor':
                $datosArray = $_reviewer->getJuegoRevisor($postBody);
                break;
            
            case 'postRevisarRequerimientoJuego':
                $datosArray = $_reviewer->postRevisarRequerimientoJuego($postBody);
                break;

            case 'obtenerJuegoProfesorRevisor':
                $datosArray = $_reviewer->getJuegoProfesorRevisor($postBody);
                break;

            case 'obtenerProfesorRevisionesRequerimiento':
                $datosArray = $_reviewer->getProfesorRevisionesRequerimiento($postBody);
                break;

            case 'postRevisarPorProfesor':
                $datosArray = $_reviewer->postRevisarPorProfesor($postBody);
                break;

            case 'reporteRevisionesPorRequerimiento':
                $datosArray = $_reviewer->obtenerReporteRevisionesPorRequerimiento($postBody);
                break;

            case 'reporteRevisionesPorRequerimientoYEstudiante':
                $datosArray = $_reviewer->obtenerReporteRevisionesPorRequerimientoYEstudiante($postBody);
                break;

            case 'reporteRevisionesProfesoresPorRevision':
                $datosArray = $_reviewer->obtenerReporteRevisionesProfesoresPorRevision($postBody);
                break;


            // case 'postRevisarRequerimientoJuego':
            //     $datosArray = $_reviewer->postRevisarRequerimientoJuego($postBody);
                // break;

            default:
                $datosArray = $_respuestas->error_405();
                break;
        }
    } else {
        $datosArray = $_respuestas->error_400();
    }
    header('Content-Type: application/json');
    if ($datosArray["code"] != "200") {
        $responseCode = $datosArray["code"];
        http_response_code($responseCode);
    } else {
        http_response_code(200);
    }
    echo json_encode($datosArray);
} else {
    // if($_SERVER['REQUEST_METHOD'] == "PUT") {
    //     $postBody = file_get_contents("php://input");

    //     $datosArray = $_auth->CreacionUsuario($postBody);

    //     header('Content-Type: application/json');
    //     if($datosArray["code"] != "200"){
    //         $responseCode = $datosArray["code"];
    //         http_response_code($responseCode);
    //     }
    //     else{
    //         http_response_code(200);
    //     }
    //     echo json_encode($datosArray);
    // }
    // else{
    header('Content-Type: application/json');
    $datosArray = $_respuestas->error_405();
    echo json_encode($datosArray);

    //}
}


?>