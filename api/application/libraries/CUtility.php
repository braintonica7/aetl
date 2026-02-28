<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CUility
 *
 * @author Jawahar
 */
class CUtility {

    static function get_UUID($data = NULL) {
        if ($data == NULL)
            $data = openssl_random_pseudo_bytes(16);
        
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        //return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));  //hypens removed to make it 32 chars long.        
    }

    static function startsWith($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    static function endsWith($string, $endString) {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

}
