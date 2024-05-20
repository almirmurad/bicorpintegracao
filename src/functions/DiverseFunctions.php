<?php

namespace src\functions;

class DiverseFunctions{

    //CONVERTE PARA DATA EM PORTUGUÊS
    public static function convertDate($date)
    {

        $dateIni = explode('T', $date);
        $datePt = explode('-', $dateIni[0]);
        $datePtFn = implode("/", array_reverse($datePt)); // . " às " . $dateIni[1];

        return $datePtFn;
    }

    public static function limpa_cpf_cnpj($valor){
        $valor = trim($valor);
        $valor = str_replace(array('.','-','/'), "", $valor);
        return $valor;
       }

}