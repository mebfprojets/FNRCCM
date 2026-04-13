<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */


/**
 * Cette classe contient des méthodes intéressantes
 * 
 * Permettant des fonctions basiques
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Functions
{
	/**
	 * Permet de déterminer la taille d'un fichier
	 *
	 * @static
	 *
	 * @return string la taille du fichier
	 */
	public static function toByteString($bytes, $unit ="", $decimals = 2)
	{
		/*
		$sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		for ($i=0; $size >= 1024 && $i < 9; $i++) {
			$size /= 1024;
		}	
		return round($size, 2)." " . $sizes[$i];*/
		$units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4,
				       'PB'=> 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);		
		$value = 0;
		if ($bytes > 0) {
			// Generate automatic prefix by bytes
			// If wrong prefix given
			if (!array_key_exists($unit, $units)) {
				$pow = floor(log($bytes)/log(1024));
				$unit = array_search($pow, $units);
			}
		
			// Calculate byte value by prefix
			$value = ($bytes/pow(1024,floor($units[$unit])));
		}
		
		// If decimals is not numeric or decimals is less than 0
		// then set default value
		if (!is_numeric($decimals) || $decimals < 0) {
			$decimals = 2;
		}
		
		// Format output
		return sprintf('%.' . $decimals . 'f '.$unit, $value);
	}
	
	function byteFormat($bytes, $unit = "", $decimals = 2) {
		$units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4,
				'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);
	
		$value = 0;
		if ($bytes > 0) {
			// Generate automatic prefix by bytes
			// If wrong prefix given
			if (!array_key_exists($unit, $units)) {
				$pow = floor(log($bytes)/log(1024));
				$unit = array_search($pow, $units);
			}
	
			// Calculate byte value by prefix
			$value = ($bytes/pow(1024,floor($units[$unit])));
		}
	
		// If decimals is not numeric or decimals is less than 0
		// then set default value
		if (!is_numeric($decimals) || $decimals < 0) {
			$decimals = 2;
		}
	
		// Format output
		return sprintf('%.' . $decimals . 'f '.$unit, $value);
	}
	/**
	 * Permet de récupérer le pays d'un visiteur 
	 * à travers son adresse IP
	 *
	 * @static
	 *
	 * @return string le pays
	 */
	public static function getCountry($ip=null)
	{
		if(null===$ip){
			$ip  = Sirah_Functions::getIpAddress();
		}
	    $result  = "BF";
		$ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));	
		if($ip_data && $ip_data->geoplugin_countryName != null){
			$result = $ip_data->geoplugin_countryName;
		}	
		return $result;
	}
	
	
	/**
	 * Permet de deserialiser les données de session
	 *
	 * @static
	 *
	 * @return array les données de session sous forme de tableau
	 */
	public static function unserialize_session( $serialized_string) 
	{
		$variables = array();
		$a = preg_split("/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		for( $i = 0; $i < count($a); $i = $i+2){
			$variables[$a[$i]] = unserialize($a[$i+1]);
		}
		return $variables ;
	}   
	
	/**
	 * Permet de pinguer une addresse IP en utilisant les sockets.
	 *
	 * @static
	 *
	 * @return string  l'URL
	 */
	public static function url( $path , $query="/" , $port=80 , $host="" , $scheme="http")
	{
		$host  = (empty( $host ) )  ? $_SERVER["HTTP_HOST"] : $host;
		$url   = $scheme."://".$host;				
		if(extension_loaded("pecl_http") ) {
			return http_build_url( $url . "/" ,
					array(
							"scheme"  => $scheme,
							"host"    => $host,
							"path"    => $path,
							"query"   => $query
					),
					HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
			);
		}
		$query  = (is_array($query))  ?  http_build_query($query) . "\n" : $query;
		$query  = (empty($query) || ($query=="/" )) ? "" : "?". $query;
		
		$url    = $url .$path . $query;
		return $url;								
	}
	
	/**
	 * Permet de pinguer une addresse IP en utilisant les sockets.
	 *
	 * @static
	 *
	 * @return boolean
	 */
	public static function checkPing($host,$port=80,$timeout = 1)
	{
		$checkResult = false;
		if($fp = fsockopen($host,$port,$errCode,$errStr,$timeout)){
			$checkResult = true;
		}
		fclose($fp);
		return $checkResult;
	}
	
	/**
	 * Permet de pinguer une addresse IP en utilisant les sockets.
	 *
	 * @static
	 *
	 * @return boolean
	 */
	public static function pingHostSocket($host, $timeout = 1) 
	{
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket  = socket_create(AF_INET, SOCK_RAW, 1);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
		socket_connect($socket, $host, null);
		$ts = microtime(true);
		socket_send($socket, $package, strLen($package), 0);
		if (socket_read($socket, 255)) {
			$result = microtime(true) - $ts;
		} else {
			$result = false;
		}
		socket_close($socket);
		return $result;
	}

	/**
	 * Permet de récupérer l'addresse IP d'un visiteur
	 * 
	 * @static
	 *
	 * @return string
	 */
	public static function getIpAddress()
	{
	  $ipKeys    = array('HTTP_CLIENT_IP',
	  		             'HTTP_X_FORWARDED_FOR',
	  		             'HTTP_X_FORWARDED',
	  		             'HTTP_X_CLUSTER_CLIENT_IP', 
	  		             'HTTP_FORWARDED_FOR', 
	  		             'HTTP_FORWARDED', 
	  		             'REMOTE_ADDR');
	  foreach ( $ipKeys as $ipKey ) {
        if (array_key_exists($ipKey, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$ipKey]) as $ipaddress){
                $ipaddress = trim($ipaddress);
                if (filter_var($ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ipaddress;
                }
             }
          }
	    }	
	    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
	}
	
	
    /**
	 * Permet de récupérer le navigateur d'un visiteur
	 *
	 * @static
	 *
	 * @return string
	 */
	public  static function getBrowser()
	{
		$useragent  = $_SERVER['HTTP_USER_AGENT'];
		
		// check for most popular browsers first
		// unfortunately, that's IE. We also ignore Opera and Netscape 8
		// because they sometimes send msie agent
		if (strpos($useragent, 'MSIE') !== FALSE && strpos($useragent, 'Opera') === FALSE && strpos($useragent, 'Netscape') === FALSE) {
			//deal with Blazer
			if (preg_match("/Blazer\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Blazer ' . $matches[1];
			}
			//deal with IE
			if (preg_match("/MSIE ([0-9]{1,2}\.[0-9]{1,2})/", $useragent, $matches)) {
				return 'Internet Explorer ' . $matches[1];
			}
		}
		elseif (strpos($useragent, 'IEMobile') !== FALSE) {
			if (preg_match("/IEMobile\/([0-9]{1,2}\.[0-9]{1,2})/", $useragent, $matches)) {
				return 'Internet Explorer Mobile ' . $matches[1];
			}
		}
		elseif (strpos($useragent, 'Gecko')) {
			//deal with Gecko based
		
			//if firefox
			if (preg_match("/Firefox\/([0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2})?)/", $useragent, $matches)) {
				return 'Mozilla Firefox ' . $matches[1];
			}
		
			//if Netscape (based on gecko)
			if (preg_match("/Netscape\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Netscape ' . $matches[1];
			}
		
			//check chrome before safari because chrome agent contains both
			if (preg_match("/Chrome\/([^\s]+)/", $useragent, $matches)) {
				return 'Google Chrome ' . $matches[1];
			}
		
			//if Safari (based on gecko)
			if (preg_match("/Safari\/([0-9]{2,3}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Safari ' . $matches[1];
			}
		
			//if Galeon (based on gecko)
			if (preg_match("/Galeon\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Galeon ' . $matches[1];
			}
		
			//if Konqueror (based on gecko)
			if (preg_match("/Konqueror\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Konqueror ' . $matches[1];
			}
		
			// if Fennec (based on gecko)
			if (preg_match("/Fennec\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Fennec' . $matches[1];
			}
		
			// if Maemo (based on gecko)
			if (preg_match("/Maemo\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Maemo' . $matches[1];
			}
		
			//no specific Gecko found
			//return generic Gecko
			return 'Gecko based';
		}
		elseif (strpos($useragent, 'Opera') !== FALSE) {
			//deal with Opera
			if (preg_match("/Opera[\/ ]([0-9]{1}\.[0-9]{1}([0-9])?)/", $useragent, $matches)) {
				return 'Opera ' . $matches[1];
			}
		}
		elseif (strpos($useragent, 'Lynx') !== FALSE) {
			//deal with Lynx
			if (preg_match("/Lynx\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Lynx ' . $matches[1];
			}
		}
		elseif (strpos($useragent, 'Netscape') !== FALSE) {
			//NN8 with IE string
			if (preg_match("/Netscape\/([0-9]{1}\.[0-9]{1}(\.[0-9])?)/", $useragent, $matches)) {
				return 'Netscape ' . $matches[1];
			}
		}
		else {
			//unrecognized, this should be less than 1% of browsers (not counting bots like google etc)!
			return 'unknown';
		}
	}		
	
	/**
	 * Permet de convertir un code de couleur hexadécimal en nombre décimal
	 *
	 * @static
	 * @param  string $couleur le code de couleur
	 *
	 * @return string
	 */
	public static function hex2dec($couleur = "#000000")
	{
		//Le rouge
		$R                 = substr($couleur,1, 2);
		$rouge             = hexdec($R);
		
		//Le vert
		$V                 = substr($couleur, 3, 2);
		$vert              = hexdec($V);
		
		//Le bleu
		$B                 = substr($couleur, 5, 2);
		$bleu              = hexdec($B);
		
		$tbl_couleur       = array( "R"   => $rouge,
				                    "G"   => $vert,
				                    "B"   => $bleu);
		return $tbl_couleur;
	}
	
	

  }
