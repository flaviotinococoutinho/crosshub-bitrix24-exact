<?php

namespace CrossHub\Domain;

/** Transformacoes puras preservadas do contrato de entrada. */
final class Normalizer
{
    public static function meaning($input_str, $array_sinonimos, $default=NULL){
        $conversaoaccents = array('á' => 'a','à' => 'a','ã' => 'a','â' => 'a', 'é' => 'e',
            'ê' => 'e', 'í' => 'i', 'ï'=>'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', "ö"=>"o",
            'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ'=>'n', 'Á' => 'A', 'À' => 'A', 'Ã' => 'A',
            'Â' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ï'=>'I', "Ö"=>"O", 'Ó' => 'O',
            'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ç' =>'C');
        $input_str = strtr($input_str, $conversaoaccents);
        $input_str = preg_replace('/[^a-zA-Z0-9]/i', "", $input_str);
        $input_str = mb_strtoupper($input_str, 'UTF-8');
        $array_sinonimos = array_change_key_case($array_sinonimos, CASE_UPPER);
        if(array_key_exists($input_str, $array_sinonimos)) {
            return $array_sinonimos[$input_str];
        } else {
            return $default;
        }
    }

    public static function fullName($nome="",$sobrenome="") {
        $full = !empty($sobrenome)?$nome." ".$sobrenome:$nome;
        $full = mb_ereg_replace('\.', '. ', $full);
        $full = mb_ereg_replace('\s+', ' ',$full);
        $arr_nome = explode(" ",$full);
        return trim(implode(" ", array_unique($arr_nome)));
    }

    public static function email($email = "") {
        $sanitizechars = array('A' => 'a','á' => 'a','à' => 'a','ã' => 'a','â' => 'a',
            'B' => 'b', 'é' => 'e','C' =>'c','ç' => 'c', 'Ç' =>'c','D' => 'd','E'=>'e','ê' => 'e',
            'í' => 'i', 'I' => 'i', 'ï'=>'i', 'ó' => 'o', 'ô' => 'o','ò' => 'o', 'õ' => 'o', 'ö'=>'o',
            'ú' => 'u', 'ü' => 'u', 'ñ'=>'n', 'Á' => 'a', 'À' => 'a', 'Ã' => 'a',
            'Â' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ï'=>'i', 'Ö'=>'o', 'Ó' => 'o',
            'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ü' => 'u','K' => 'k','Y' => 'y',
            'F' => 'f','G' => 'g','H' => 'h','J' => 'j','L' => 'l','M' => 'm',
            'N' => 'n','O' => 'o','P' => 'p','Q' => 'q','R' => 'r','S' => 's',
            'T' => 't','U' => 'u','V' => 'v','X' => 'x','Z' => 'z','W' => 'w',
            ' ' => '','?' => '','!' => '','(' => '',')' => '','gmail.com.br'=>'gmail.com',
            'hotmail.com.br'=>'hotmail.com','yahoo.com.br'=>'yahoo.com');
        return strtr($email,$sanitizechars);
    }
}
