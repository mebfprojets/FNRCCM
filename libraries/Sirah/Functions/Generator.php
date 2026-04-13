<?php

class Sirah_Functions_Generator
{


    public function getRandomVar($longueur=12)
    {
        $chars          = 'abcdefghijklmnopqrstuvwz0123456789+=ù%_';
		$max			=	strlen( $chars ) - 1;
		$token			=	'';
		for( $i = 0; $i < $longueur; ++$i ) {
			$token .=	$chars[ (rand( 0, $max )) ];
		}
         return $token;
     }
     
     
     public function getAlpha( $length = 8)
     {
     	$salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
     	$len  = strlen($salt);
     	$makepass = '';
     
     	$stat = @stat(__FILE__);
     	if(empty($stat) || !is_array($stat)){
     		$stat = array(php_uname());
     	}    
     	mt_srand(crc32(microtime() . implode('|', $stat)));     
     	for ($i = 0; $i < $length; $i ++) {
     		$makepass .= $salt[mt_rand(0, $len -1)];
     	}    
     	return $makepass;
     }
     
     public function getInteger( $length = 8 )
     {
     	$chars          = '0123456789';
     	$max			=	strlen( $chars ) - 1;
     	$token			=	'';
     	for( $i = 0; $i < $length; ++$i ) {
     		$token .=	$chars[ (rand( 0, $max )) ];
     	}
     	return $token;
     }

}
