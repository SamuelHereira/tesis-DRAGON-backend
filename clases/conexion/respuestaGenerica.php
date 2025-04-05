<?php
   Class RespuestaGenerica {
    public  $response = [
        "code" => "200",
        "msg" => "OK",
        "result" => array()
    ];

    public function error_405(){
        $this->response['code'] = "405";
        $this->response['msg'] = "Metodo no permitido";
        $this->response['result'] = array();
        return $this->response;
    }

    public function error_200($valor = "Datos incorrectos"){
        $this->response['code'] = "200";
        $this->response['msg'] = $valor;
        $this->response['result'] = array();
        return $this->response;
    }


    public function error_400(){
        $this->response['code'] = "400";
        $this->response['msg'] = "Datos enviados incompletos o con formato incorrecto";
        $this->response['result'] = array();
        return $this->response;
    }


    public function error_500($valor = "Error interno del servidor"){
        $this->response['code'] = "500";
        $this->response['msg'] = $valor;
        $this->response['result'] = array();
        return $this->response;
    }


    public function error_401($valor = "No autorizado"){
        $this->response['code'] = "401";
        $this->response['msg'] = $valor;
        $this->response['result'] = array();
        return $this->response;
    }

   }
?>