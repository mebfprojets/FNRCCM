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
 * Cette classe intègre des aides de traitement des chaines de caractère
 * de la plateforme basée sur SIRAH
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */

if (extension_loaded('mbstring') || ((!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && dl('mbstring.so'))))
{
	// Make sure to suppress the output in case ini_set is disabled
	@ini_set('mbstring.internal_encoding', 'UTF-8');
	@ini_set('mbstring.http_input', 'UTF-8');
	@ini_set('mbstring.http_output', 'UTF-8');
}


if (function_exists('iconv') || ((!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && dl('iconv.so'))))
{
	// These are settings that can be set inside code
	iconv_set_encoding("internal_encoding", "UTF-8");
	iconv_set_encoding("input_encoding"   , "UTF-8");
	iconv_set_encoding("output_encoding"  , "UTF-8");
}


abstract class Sirah_Functions_String
{
	
	public static function split_name($name) {
		$name       = trim($name);
		$last_name  = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name = trim( preg_replace('#'.preg_quote($last_name,'#').'#', '', $name ) );
		return array($first_name, $last_name);
	}
	
	public static function array_strtolower($value , $index)
	{
		$value  = Sirah_Functions_String::strtolower($value);
	}
	
	public static function array_strtoupper($value , $index)
	{
		$value  = Sirah_Functions_String::strtoupper($value);
	}
	
	public static function fixJson( $string ){
		$regex = '/(?<!")([a-zA-Z0-9_]+)(?!")(?=:)/i';
	    $encoded = preg_replace($regex, '"$1"', $string);
		return preg_replace('/":([0-9]+),/', '":$1,', $encoded);
    }
	public function removeUrl( $text ) 
	{
		$U = explode(' ', $text );
	
		$W =array();
		foreach ($U as $k => $u) {
			if (stristr($u,'http') || (count(explode('.',$u)) > 1)) {
				unset($U[$k]);
				return Sirah_Functions_String::removeUrl( implode(' ',$U));
			}
		}
		return implode(' ', $U);
	}
	
	public static function stripos($haystack, $needle, $offset = false)
	{
		if ($offset === false) {
			return stripos($haystack, $needle);
		} else {
			return stripos($haystack, $needle, $offset);
		}
	}


	/**
	 *
	 * Recherche la première occurence d'une chaine en utilisant UTF8.
	 *
	 * @param   string   $haystack     La chaine dans laquelle on doit rechercher
	 * @param   string   $needle       La chaine dont la position est recherchée
	 * @param   integer  $offset       Optionnel, indique la position à partir de laquelle il faut commencer
	 *
	 * @return  mixed  Le nombre de caractère se trouvant avant la première occurence ou false
	 *
	 * @see     http://www.php.net/strpos
	 * 
	 */
	public static function strpos($haystack, $needle, $offset = false)
	{
		if ($offset === false) {
			return utf8_strpos($haystack, $needle);
		} else {
			return utf8_strpos($haystack, $needle, $offset);
		}
	}
	
	/**
	 *
	 * Recherche la dernière occurence d'une chaine en utilisant UTF8.
	 *
	 * @param   string   $haystack     La chaine dans laquelle on doit rechercher
	 * @param   string   $needle       La chaine dont la position est recherchée
	 * @param   integer  $offset       Optionnel, indique la position à partir de laquelle il faut commencer
	 *
	 * @return  mixed  Le nombre de caractère se trouvant avant la dernière occurence ou false
	 *
	 * @see     http://www.php.net/strpos
	 * 
	 */
	public static function strrpos($haystack, $needle, $offset = false)
	{
		if ($offset === false) {
			return utf8_strrpos($haystack, $needle);
		} else {
			return utf8_strrpos($haystack, $needle, $offset);
		}
	}
	
	/**
	 * 
	 * Permet de récupérer une partie d'une chaine de caractère 
	 *
	 * @param   string   $string La chaine de caractère concernée, encodée en UTF-8.
	 * @param   integer  $from   La position de départ
	 * @param   integer  $to     Optionnellement la position à laquelle il faut s'arretr
	 *
	 * @return  mixed  une chaine ou false si rien n'est trouvé
	 *
	 * @see     http://www.php.net/substr
	 * 
	 */
	public static function substr($string, $from, $to = false)
	{
		if ($to === false){
			return utf8_substr( $string , $from);
		} else {
			return utf8_substr( $str , $from , $to);
		}
	}
	
	/**
	 *
	 * Retourne la forme minuscule de la chaine
	 * 
	 * @param   string  $string  La chaine de caractère encodée en UTF-8.
	 *
	 * @return  mixed  la chaine en minuscule ou false en cas d'echec
	 *
	 * @see http://www.php.net/strtolower
	 * 
	 */
	public static function strtolower( $string )
	{
		return utf8_strtolower($string);
	}
	
	/**
	 *
	 * Retourne la forme majuscule de la chaine
	 * 
	 * @param   string  $string  La chaine de caractère encodée en UTF-8.
	 *
	 * @return  mixed  la chaine en majsucule ou false en cas d'echec
	 *
	 * @see http://www.php.net/strtoupper
	 * 
	 */
	public static function strtoupper( $string )
	{
		return utf8_strtoupper($string);
	}
	
	/**
	 *
	 * Retourne le nombre de caractères contenus dans la chaine
	 *
	 * @param   string  $string La chaine de caractère encodée en UTF-8.
	 *
	 * @return  integer  Le nombre de caractères
	 *
	 * @see http://www.php.net/strlen
	 * 
	 */
	public static function strlen($string)
	{
		return utf8_strlen($string);
	}
	
	/**
	 * UTF-8 aware alternative to str_ireplace
	 * Version insensible à la casse de str_replace
	 * qui permet de remplacer une chaine contenue dans une autre
	 *
	 * @param   string   $search   La chaine recherchée
	 * @param   string   $replace  La chaine qui va subir le remplacement
	 * @param   string   $subject  La nouvelle chaine qui prendra la place de celle recherchée
	 * @param   integer  $count    Optionnel va contenir le nombre de remplacements qui sera effectué
	 *
	 * @return  string  Une nouvelle chaine UTF-8
	 *
	 * @see     http://www.php.net/str_ireplace
	 */
	public static function str_ireplace($search, $replace, $subject, $count = null)
	{
		require_once("Phputf8/str_ireplace.php");
		if ( $count === false ){
			return utf8_ireplace($search, $replace, $subject);
		} else {
			return utf8_ireplace($search, $replace, $subject, $count);
		}
	}
	
	/**
	 * UTF-8 aware alternative to str_split
	 * Permet de convertir une chaine en tableau
	 *
	 * @param   string   $string     Une chaine UTF8
	 * @param   integer  $split_len  Le pas
	 *
	 * @return  array
	 *
	 * @see     http://www.php.net/str_split
	 * 
	 */
	public static function str_split($string, $split_len = 1)
	{
		require_once("Phputf8/str_split.php");	
		return utf8_str_split($string, $split_len);
	}
	
	
	/**
	 * Trouve un segment de chaîne ne contenant pas certains caractères
	 *
	 * @param   string   $string  La première chaine concernée
	 * @param   string   $mask    La seconde chaîne.
	 * @param   integer  $start   Optionel La position sur la chaîne à partir de laquelle on analyse.
	 * @param   integer  $length  Optionel La taille à examiner de la chaîne.
	 *
	 * @return  integer  la longueur du segment 
	 *
	 * @see     http://www.php.net/strcspn
	 * 
	 */
	public static function strcspn($string, $mask, $start = null, $length = null)
	{
		require_once("Phputf8/strcspn.php");	
		if ($start === false && $length === false) {
			return utf8_strcspn($string, $mask);
		} elseif ($length === false) {
			return utf8_strcspn($string, $mask, $start);
		} else {
			return utf8_strcspn($string, $mask, $start, $length);
		}
	}
	
	/**
	 * Retourne une sous-chaîne de haystack, 
	 * allant de la première occurrence de needle (incluse) jusqu'à la fin de la chaîne
	 * insensible à la casse.
	 *
	 * @param   string  $haystack     
	 * @param   string  $needle  
	 *
	 * @return  string  la sous chaine
	 *
	 * @see     http://www.php.net/stristr
	 * 
	 */
	public static function stristr($haystack, $needle)
	{
		require_once("Phputf8/stristr.php");
		return utf8_stristr($haystack, $needle);
	}
	
	/**
	 * 
	 * Inverse une chaîne
	 *
	 * @param   string  $string la chaine à renverser
	 *
	 * @return  string   La chaine renversée
	 *
	 * @see     http://www.php.net/strrev
	 * 
	 */
	public static function strrev($string)
	{
		require_once("Phputf8/strrev.php");	
		return utf8_strrev($string);
	}
	
	/**
	 * UTF-8 aware alternative to strspn
	 * Trouve la longueur du segment initial d'une chaîne contenant tous les caractères d'un masque donné
	 *
	 * @param   string   $subject  La chaîne à analyser.
	 * @param   string   $mask     La liste des caractères autorisés
	 * @param   integer  $start    La position dans la chaîne subject à partir de laquelle nous devons chercher.
	 * @param   integer  $length   Optionnel La longueur de la chaîne à analyser.
	 *
	 * @return  integer
	 *
	 * @see     http://www.php.net/strspn
	 * 
	 */
	public static function strspn($subject, $mask, $start = null, $length = null)
	{
		require_once("Phputf8/strspn.php");	
		if ($start === null && $length === null){
			return utf8_strspn($subject, $mask);
		} elseif ($length === null) {
			return utf8_strspn($subject, $mask, $start);
		} else {
			return utf8_strspn($subject, $mask, $start, $length);
		}
	}
	
	/**
	 * Remplace un segment dans une chaîne
	 *
	 * @param   string   $string       La chaîne d'entrée.
	 * @param   string   $replacement  La chaîne de remplacement.
	 * @param   integer  $start        Si start est positif, le remplacement se fera à partir du caractère numéro start dans string.
	 * @param   integer  $length       Si length est fourni et positif, il représentera la longueur du segment de code remplacé dans la chaîne string
	 *
	 * @return  string
	 *
	 * @see     http://www.php.net/substr_replace
	 */
	public static function substr_replace($string, $replacement, $start, $length = null)
	{
		if ($length === false){
			return utf8_substr_replace($string, $replacement, $start);
		} else {
			return utf8_substr_replace($string, $replacement, $start, $length);
		}
	}
	
	/**
	 *
	 * Supprime les espaces (ou d'autres caractères) de début de chaîne
	 *
	 * @param   string  $str       La chaîne d'entrée.
	 * @param   string  $charlist  Optionnel les caractères à supprimer en utilisant le paramètre
	 *
	 * @return  string   la chaîne str, après avoir supprimé les caractères invisibles de début de chaîne
	 *
	 * @see     http://www.php.net/ltrim
	 * @since   11.1
	 */
	public static function ltrim($str, $charlist = false)
	{
		if (empty($charlist) && $charlist !== false){
			return $str;
		}	
		require_once("Phputf8/ltrim.php");	
		if ($charlist === false){
			return utf8_ltrim($str);
		} else {
			return utf8_ltrim($str, $charlist);
		}
	}
	
	/**
	 * Supprime les espaces (ou d'autres caractères) de fin de chaîne
	 *
	 * @param   string  $str       La chaîne d'entrée.
	 * @param   string  $charlist  Optionnel les caractères à supprimer en utilisant le paramètre
	 *
	 * @return  string  la chaîne str, après avoir supprimé les caractères invisibles de fin de chaîne
	 *
	 * @see     http://www.php.net/rtrim
	 */
	public static function rtrim( $str, $charlist = false)
	{
		if (empty($charlist) && $charlist !== false){
			return $str;
		}	
		require_once("Phputf8/rtrim.php");	
		if ( $charlist === false ) {
			return utf8_rtrim($str);
		} else {
			return utf8_rtrim($str, $charlist);
		}
	}
	
	/**
	 * Supprime les espaces (ou d'autres caractères) en début et fin de chaîne
	 *
	 * @param   string  $str       La chaine de caractères qui sera coupée.
	 * @param   string  $charlist  Optionnellement, les caractères supprimés peuvent aussi être spécifiés en utilisant le paramètre charlist
	 *
	 * @return  string  La chaîne de caractères coupée.
	 *
	 * @see     http://www.php.net/trim
	 */
	public static function trim($str, $charlist = false)
	{
		if ( empty($charlist) && $charlist !== false){
			 return $str;
		}	
		require_once("Phputf8/trim.php");	
		if ($charlist === false){
			return utf8_trim($str);
		} else {
			return utf8_trim($str, $charlist);
		}
	}
	
	/**
	 * Met le premier caractère en majuscule
	 *
	 * @param   string  $str           La chaîne d'entrée.
	 * @param   string  $delimiter     Le delimiteur
	 * @param   string  $newDelimiter  Le delimiteur du nouveau mot
	 *
	 * @return  
	 * @see     http://www.php.net/ucfirst

	 */
	public static function ucfirst($str, $delimiter = null, $newDelimiter = null)
	{
		require_once("Phputf8/ucfirst.php");
		if ($delimiter === null){
			return utf8_ucfirst($str);
		} else {
			if ( $newDelimiter === null ) {
				 $newDelimiter = $delimiter;
			}
			return implode($newDelimiter, array_map('utf8_ucfirst', explode($delimiter, $str)));
		}
	}
	
	/**
	 * Met en majuscule la première lettre de tous les mots
	 *
	 * @param   string  $str  La chaîne d'entrée.
	 *
	 * @return  string  Retourne la chaîne, après modification.
	 *
	 * @see     http://www.php.net/ucwords
	 */
	public static function ucwords($str)
	{
		require_once("Phputf8/ucwords.php");
		return utf8_ucwords($str);
	}
	
	
    public static function txtentities($html)
    {
           $trans = get_html_translation_table(HTML_ENTITIES);
           $trans = array_flip($trans);
           return strtr($html, $trans);
    }
    
    /**
     * Permet de convertir une chaine de caractère en nombre décimal
     *
     * @param   string  $str  La chaîne d'entrée.
     *
     * @return  string  Retourne la chaîne, après modification.
     *
     */
    public static function  strtofloat( $string )
    {
    	$string  = floatval( preg_replace("/[[:alpha:]]|[[:blank:]]|[[:space:]]/" , "" , $string ) );   
    	return $string;
    }
    
    /**
     * Permet de convertir une chaine de caractère en nombre entier
     *
     * @param   string  $str  La chaîne d'entrée.
     *
     * @return  string  Retourne la chaîne, après modification.
     *
     */
    public static function  strtoint( $string )
    {
    	$string  = intval(preg_replace("/[[:alpha:]]|[[:blank:]]|[[:space:]]/" , "" , $string));   
    	return $string;
    }
    
    
    
    public static function cleanUtf8( $str, $charset='utf-8' )
    {
    	$str     = str_replace(array( '(', ')', "/" ), "", stripslashes( $str ) );
    	$str     = preg_replace('/\\\\/', '', $str );
    	$str     = strtr($str ,array('.' => '', ',' => '', "'" => "", "`" => "", "’" => "", ":" => "", "_" => " "));
    	$search  = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@','@[.]$@');
    	$replace = array ('e','a','i','u','o','n','y','c',' ','','');
    	return preg_replace($search, $replace, $str);
    }
	
	
	public static function ulToArray($ul) 
	{
	  if (is_string($ul)) {
		// encode ampersand appropiately to avoid parsing warnings
		$ul=preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $ul);
		if (!$ul = simplexml_load_string($ul)) {
		  trigger_error("Syntax error in UL/LI structure");
		  return FALSE;
		}
		return self::ulToArray($ul);
	  } else if (is_object($ul)) {
		$output = array();
		foreach ($ul->li as $li) {
		  $output[] = (isset($li->ul)) ? self::ulToArray($li->ul) : (string) $li;
		}
		return $output;
	  } else return FALSE;
    }
}