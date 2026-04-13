<?php


/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */



/**
 * Cette classe permet de réaliser
 * des opérations sur les données
 * des utilisateurs
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
class Sirah_User_Helper
{
	
	/**
	 * Permet de déterminer le pays de l'utilisateur
	 * à partir de son adresse ip
	 *
	 * @param $ipaddress   L'adresse IP de l'utilisateur
	 *
	 *
	 * @return string 
	 */
	public function getCountry( $ipaddress = null )
	{
		if( null == $ipaddress )  {
			$ipaddress  = Sirah_User_Helper::getIpaddress();
		}
		
		return "BF";
	}
	
	/**
	 * Permet de déterminer l'adresse IP de l'utilisateur
	 *
	 * @return string l'adresse IP
	 */
	public function getIpaddress()
	{
		$ipKeys    = array('HTTP_CLIENT_IP',
				           'HTTP_X_FORWARDED_FOR',
				           'HTTP_X_FORWARDED',
				           'HTTP_X_CLUSTER_CLIENT_IP',
				           'HTTP_FORWARDED_FOR',
				           'HTTP_FORWARDED',
				           'REMOTE_ADDR');
		foreach ($ipKeys as $ipKey){
			if (array_key_exists($ipKey, $_SERVER) === true){
				foreach (explode(',', $_SERVER[$ipKey]) as $ipaddress){
					$ipaddress = trim($ipaddress);
		
					if (filter_var($ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
						return $ipaddress;
					}
				}
			}
		}	
	}

	
	/**
	 * Permet de vérifier le mot de passe de l'utilisateur
	 * avec celui crypté dans la base de données
	 *
	 * @param $toverify       le mot de passe à vérifier
	 * @param $encrypted      le mot de passe crypté
	 *
	 *
	 * @return bool vrai ou faux en fonction du résultat
	 */
	public function verifyPassword( $toverify , $encrypted )
	{
		$regexCrypt  = "/^(.+):([^:]+)$/";
		if( preg_match($regexCrypt,$encrypted,$passwordParts ) ) {
			$partCrypted     = isset($passwordParts[1])?$passwordParts[1]:null;
			$partSalt        = isset($passwordParts[2])?$passwordParts[2]:"";
			$toverifycrypted = Sirah_User_Helper::cryptPassword($toverify,$partSalt);
			return ($toverifycrypted==$encrypted);
		}		
		return false;		
	}
	
	/**
	 * Permet de crypter le mot de passe de l'utilisateur
	 * 
	 * @param $password       le mot de passe de l'utilisateur
	 * @param $encryption     le type de cryptage du mot de passe
	 * @param $salt           le grain de sel de hashasge
	 * @param $separator      Le caractère qui sépare le grain de sel du mot crypté
	 *
	 *
	 * @return string le mot de passe crypté de l'utilisateur
	 */
	public function cryptPassword($password,$salt="",$encryption="md5-hex",$separator=":")
	{
		$encrypted   = null;
		if(empty($salt)){
			$salt    = Sirah_User_Helper::getSalt($encryption , $salt , $password);
		}	
		switch ($encryption)
		{
		   case 'plain' :
			 $encrypted    = $password;
		
		   case 'sha' :
				$encrypted = base64_encode(mhash(MHASH_SHA1, $password));
	
			case 'crypt' :
			case 'crypt-des' :
			case 'crypt-md5' :
			case 'crypt-blowfish' :
				$encrypted = crypt($password,$salt);
		
			case 'md5-base64' :
				$encrypted = base64_encode(mhash(MHASH_MD5, $password));
		
			case 'ssha' :
				$encrypted = base64_encode(mhash(MHASH_SHA1, $password.$salt).$salt);
		
			case 'smd5' :
				$encrypted = base64_encode(mhash(MHASH_MD5, $password.$salt).$salt);
		
			case 'aprmd5' :
				$length  = strlen($password);
				$context = $password.'$apr1$'.$salt;
				$binary  = Sirah_User_Helper::bin(md5($password.$salt.$password));
		
				for ($i = $length; $i > 0; $i -= 16) {
					$context .= substr($binary, 0, ($i > 16 ? 16 : $i));
				}
				for ($i = $length; $i > 0; $i >>= 1) {
					$context .= ($i & 1) ? chr(0) : $password[0];
				}
		
				$binary = Sirah_User_Helper::bin(md5($context));
		
				for ($i = 0; $i < 1000; $i ++) {
					$new = ($i & 1) ? $password : substr($binary, 0, 16);
					if ($i % 3) {
						$new .= $salt;
					}
					if ($i % 7) {
						$new .= $password;
					}
					$new .= ($i & 1) ? substr($binary, 0, 16) : $password;
					$binary = Sirah_User_Helper::bin(md5($new));
				}		
				$p = array ();
				for ($i = 0; $i < 5; $i ++) {
					$k = $i +6;
					$j = $i +12;
					if ($j == 16) {
						$j = 5;
					}
					$p[] = Sirah_User_Helper::toAPRMD5((ord($binary[$i]) << 16) | (ord($binary[$k]) << 8) | (ord($binary[$j])), 5);
				}	
				$encrypted ='$apr1$'.$salt.'$'.implode('', $p).Sirah_User_Helper::toAPRMD5(ord($binary[11]), 3);		
			case 'md5-hex' :
			default :
				$encrypted = ($salt) ? md5($password.$salt) : md5($password);
		}	
		$cryptedPassword  = $encrypted . $separator . $salt;
		
		return $cryptedPassword;
	}
	
	
	public function toAPRMD5($value, $count)
	{	
		/* 64 characters that are valid for APRMD5 passwords. */
		$APRMD5 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	
		$aprmd5 = '';
		$count = abs($count);
		while (-- $count) {
			$aprmd5 .= $APRMD5[$value & 0x3f];
			$value >>= 6;
		}
		return $aprmd5;
	}
	
	
	public	function bin($hex)
	{
		$bin = '';
		$length = strlen($hex);
		for ($i = 0; $i < $length; $i += 2) {
			$tmp = sscanf(substr($hex, $i, 2), '%x');
			$bin .= chr(array_shift($tmp));
		}
		return $bin;
	 }
	 
	 /**
	  * Permet de g�n�rer un jeton unique pour l'utilisateur
	  * 
	  * @param integer $length  la taille du jeton
	  * 
	  * @return string une chaine de caract�re correspondant au jeton
	  *
	  */
	 public function getToken($length=8)
	 {
	 	$randomVar    = "_+=abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZç@+mmopoyu_xghjo=b";	 	
	 	$hashedRandom = Sirah_User_Helper::hash($randomVar,2);
	 	$max		  =	strlen($hashedRandom) - 1;
	 	$token        = "";
	 	for( $i = 0; $i < $length; ++$i ) {
	 		$token   .=	$hashedRandom[(rand(0,$max ))];
	 	}	 	
	 	return $token;
	 }
	 
	 /**
	  * Permet de créer le hash d'une chaine de caractère
	  *
	  * @param  string la chaine de caractère
	  * @param  int    le nombre de fois qu'il doit hasher
	  *
	  *
	  * @return string une chaine de caractère hashée
	  *
	  */
	 function hash($input,$count=3)
	 {
	      $salt   = Sirah_User_Helper::getSalt(5);
	      $hash   = $input;
          for ($i = 0; $i < $count; ++$i) {
                $hash = hash("sha256", $input . $salt);
          }
          return $hash;
	 }
	 
	 /**
	  * Permet de cr�er un nombre al�atoire
	  * 
	  * dont la longueur varie entre $min et $max
	  *
	  * @param integer $min la longueur minimale
	  * @param integer $max la longeur  maximale
	  * 
	  * @uses openssl_random_pseudo_bytes
	  *
	  * @return string une chaine de caract�re correspondant au jeton
	  *
	  */
	 function crypto_rand_secure($min, $max) 
	 {
	 	$range = $max - $min;
	 	if ($range < 0) return $min; // not so random...
	 	$log = log($range, 2);
	 	$bytes = (int) ($log / 8) + 1; // length in bytes
	 	$bits = (int) $log + 1; // length in bits
	 	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	 	do {
	 		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
	 		$rnd = $rnd & $filter; // discard irrelevant bits
	 	} while ($rnd >= $range);
	 	return $min + $rnd;
	 }
	 
	/**
	 * Permet de générer un grain de sel en fonction du type de cryptage utilisé.
	 *
	 * @param   string  $encryption  Le type de cryptage utilisé.
	 * @param   string  $seed        Une chaine de caractère à partir de laquelle
	 *                                le grain de sel doit etre généré
	 * @param   string  $password    Le mot de passe (en clair).
	 *
	 * @return  string  Le grain de sel
	 *
	 */
	public static function getSalt($encryption = 'md5-hex',$seed='', $password = '')
	{
	    switch ($encryption)
		{
			case 'crypt':
			case 'crypt-des':
				if ($seed){
					return substr($seed, 0, 2);
				} else {
					return substr(md5(mt_rand()), 0, 2);
				}
				break;

			case 'crypt-md5':
				if ($seed){
					return substr($seed, 0, 12);
				}else {
					return '$1$' . substr(md5(mt_rand()), 0, 8) . '$';
				}
				break;

			case 'crypt-blowfish':
				if ($seed){
					return substr($seed, 0, 16);
				} else {
					return '$2$' . substr(md5(mt_rand()), 0, 12) . '$';
				}
				break;

			case 'ssha':
				if ($seed){
					return substr($seed, -20);
				} else {
					return mhash_keygen_s2k(MHASH_SHA1, $password, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;

			case 'smd5':
				if ($seed){
					return substr($seed, -16);
				} else {
					return mhash_keygen_s2k(MHASH_MD5, $password, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
				}
				break;

			case 'aprmd5': /* 64 characters that are valid for APRMD5 passwords. */
				$APRMD5 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

				if ($seed){
					return substr(preg_replace('/^\$apr1\$(.{8}).*/', '\\1', $seed), 0, 8);
				} else {
					$salt = '';
					for ($i = 0; $i < 8; $i++)
					{
						$salt .= $APRMD5{rand(0, 63)};
					}
					return $salt;
				}
				break;

			default:
				$salt = '';
				if ($seed) {
					$salt = $seed;
				}
				return $salt;
				break;
		}
	}
	 
}

