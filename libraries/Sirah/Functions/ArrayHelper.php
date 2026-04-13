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
 * Cette classe intègre des opérations de traitement des élements des tableaux
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
class Sirah_Functions_ArrayHelper 
{
	public function rangeWithSign($value)
	{
		if(is_float($value) && ($value > 0)) {
			return strval("+".$value);
		} 
		return $value;
	}
	
	public function rangeWithCombine( $from, $to, $step = 1, $show_sign = false)
	{
	  if($show_sign)
		   return array_combine(range($from, $to , $step ), array_map(array(__CLASS__,"rangeWithSign"),range($from,$to, $step )));
       else	
           return array_combine(range($from, $to , $step ), range($from,$to, $step ));		   
	}
	
	
	/**
	 * Permet des convertir toutes les valeurs d'un tableau en entier
	 *
	 * @static
	 *
	 * @param $array   array  array to convert
	 * @param $default mixed  value (int|array) to assign if $array is not an array

	 */
	function toInteger(&$array, $default = null) 
	{
		if (is_array ( $array )) {
			foreach ( $array as $i => $v ) {
				$array [$i] = ( int ) $v;
			}
		} else {
			if ($default === null) {
				$array = array ();
			} elseif (is_array ( $default )) {
				Sirah_Functions_ArrayHelper::toInteger ( $default, null );
				$array = $default;
			} else {
				$array = array (( int ) $default );
			}
		}
	}
	
	static public function search($arr, $needle)
	{		 
		$matches = array_filter($arr,function($haystack) use (&$needle){
			         $haystack = strtr($haystack, 'ÁÀÂÄÃÅÇÉÈÊËÍÏÎÌÑÓÒÔÖÕÚÙÛÜÝ', 'AAAAAACEEEEEIIIINOOOOOUUUUY');
                     $haystack = strtr($haystack, 'áàâäãåçéèêëíìîïñóòôöõúùûüýÿ', 'aaaaaaceeeeiiiinooooouuuuyy');
			         return(stripos($haystack,$needle) !== false );});
		return $matches;
	}
	
	
	/**
	 * Permet de récupérer les clés d'un tableau correspondant à un type de variable bien donné
	 *
	 * @static
	 * 
	 * @param  $input         array  le tableau concerné
	 * @param  $filterType    string le type de clé 
	 * @param  $search_value  string la valeur des clés qu'on souhaite récupérer
	 * @param  $strict        bool   
	 * @param  $keyPrefix     string le préfix associé aux clés
	 *	
	 */
	function getKeys($input=array(),$filterType=null,$search_value=null,$strict=false,$keyPrefix="")
	{
		$allKeys      = array_keys($input,$search_value,$strict);
		$filteredKeys = array();
		
		if(null===$filterType){
			return $allKeys ;
		}				
		if( count(   $allKeys)){
			foreach( $allKeys as $key){
				if( gettype($key) == $filterType){
					$filteredKeys[] = $keyPrefix . $key;
				}
			}
		}
		return $filteredKeys;
	}
	
	
	/**
	 * Permet de récupérer les valeurs d'un tableau correspondant à un type de variable bien donné
	 *
	 * @static
	 *
	 * @param  $input         array  le tableau concerné
	 * @param  $filterType    string le type de clé
	 * @param  $search_value  string la valeur des clés qu'on souhaite récupérer
	 * @param  $strict        bool
	 * @param $valuePrefix   string
	 *
	 */
	function getValues($input=array() , $filterType=null , $search_value = null , $strict=false , $valuePrefix="")
	{
		$allValues      = array_values($input);
		$filteredValues = array();
	
		if( null===$filterType){
			return $allValues ;
		}	
		if(count($allValues)){
			foreach($allValues as $key => $value){
				if(gettype($value) == $filterType) {
					$filteredValues[] = $valuePrefix . $value;
				}
			}
		}
		return $filteredValues;
	}
		
	
	
	
	function createRecursiveArray($keys, $val) 
	{		
		$recursiveArray = array ();		
		if (count ( $keys )) {			
			for($i = 0; $i < count ( $keys ); $i ++) {
				reset ( $keys );
				$key = array_shift ( $keys );
				if ($i == count ( $keys )) {					
					$recursiveArray [$key] = $val;
				} else {					
					$recursiveArray [$key] = Sirah_Functions_ArrayHelper::createRecursiveArray ( $keys, $val );				
				}
			}
		}		
		return $recursiveArray;
	}
	
	/**
	 * Permet de créer un objet stdClass à partir d'un tableau
	 *
	 * @static
	 *
	 * @param $array array   le tableau
	 * @param $calss string  le type de classe à créer
	 * 
	 * @return un objet

	 */
	function toObject(&$array, $class = 'stdClass') 
	{
		$obj = null;
		if (is_array ( $array )) {
			$obj = new $class ();
			foreach ( $array as $k => $v ) {
				if (is_array ( $v )) {
					$obj->$k = Sirah_Functions_ArrayHelper::toObject ( $v, $class );
				} else {
					$obj->$k = $v;
				}
			}
		}
		return $obj;
	}
	
	/**
	 * Permet de créer une chaine de caractère à partir d'un tableau
	 *
	 * @static
	 *
	 * @param $array        array   le tableau
	 * @param $inner_glue   string  le séparateur interne
	 * @param $outer_glue   string  le séparateur externe
	 * @param $keepOuterKey bool    préserver les clé
	 *
	 * @return une chaine de caractère
	
	 */
	function toString($array = null, $inner_glue = '=', $outer_glue = ' ', $keepOuterKey = false) 
	{
		$output = array ();
		
		if (is_array ( $array )) {
			foreach ( $array as $key => $item ) {
				if (is_array ( $item )) {
					if ($keepOuterKey) {
						$output [] = $key;
					}
					$output [] = Sirah_Functions_ArrayHelper::toString ( $item, $inner_glue, $outer_glue, $keepOuterKey );
				} else {
					$output [] = $key . $inner_glue . '"' . $item . '"';
				}
			}
		}
		
		return implode ( $outer_glue, $output );
	}
	
	/**
	 * Permet de convertir un objet en tableau
	 *
	 * @static
	 *
	 * @param   object	L'objet source
	 * @param   boolean	La fonction doit elle s'appliquer de facon recursive ?
	 * @param   string	Une expression régulière en option pour vérifier les attributs de l'objet concernés
	 * @return  array array mapped from the given object

	 */
	function fromObject($p_obj, $recurse = true, $regex = null) 
	{
		$result = null;
		if (is_object ( $p_obj )) {
			$result = array ();
			foreach ( get_object_vars ( $p_obj ) as $k => $v ) {
				if ($regex) {
					if (! preg_match ( $regex, $k )) {
						continue;
					}
				}
				if (is_object ( $v )) {
					if ($recurse) {
						$result [$k] = Sirah_Functions_ArrayHelper::fromObject( $v, $recurse, $regex );
					}
				} else {
					$result [$k] = $v;
				}
			}
		}
		return $result;
	}
	
	
	/**
	 * Permet d'extraire des colonnes dans le tableau
	 *
	 * @static
	 *
	 * @param $array array le tableau
	 * @param $index la clé du tableau ou de l'objet
	 * @return un tableau des elements

	 */
	function getColumn(&$array, $index) 
	{
		$result = array ();
		
		if (is_array ( $array )) {
			$n = count ($array );
			for($i = 0; $i < $n; $i ++) {
				$item = & $array [$i];
				if (is_array ( $item ) && isset ( $item [$index] )) {
					$result [] = $item[$index];
				} elseif (is_object ( $item ) && isset ( $item->$index )) {
					$result [] = $item->$index;
				}
			}
		}
		return $result;
	}
	
	
	public function searchFromKey( $array, $keyNeedle , $valueKeysWithoutNeedle = true )
	{
		if(!is_array( $array ) || empty( $array )) {
			return false;
		}
		$filterArray    = array();
		foreach( $array as $k => $v ) {
			     if( strpos( $k, $keyNeedle ) === false ) continue;			     
			     $arrayKey               = ( $valueKeysWithoutNeedle ) ? str_replace( $keyNeedle, "", $k ) : $k;
			     $filterArray[$arrayKey] = $v;
		}				
		return $filterArray;
	}
	
	/**
	 * Utility function to return a value from a named array or a specified
	 * default
	 *
	 * @static
	 *
	 * @param $array array
	 *       	 array
	 * @param $name string
	 *       	 to search for
	 * @param $default mixed
	 *       	 value to give if no key found
	 * @param $type string
	 *       	 for the variable (INT, FLOAT, STRING, WORD, BOOLEAN, ARRAY)
	 * @return mixed value from the source array

	 */
	function getValue(&$array, $name, $default = null, $type = '') 
	{
		// Initialize variables
		$result = null;
		
		if (isset ( $array[$name] )) {
			$result = $array[$name];
		}
		
		// Handle the default case
		if (is_null ( $result )) {
			$result = $default;
		}
		
		// Handle the type constraint
		switch (strtoupper ( $type )) {
			case 'INT' :
			case 'INTEGER' :
				// Only use the first integer value
				@ preg_match ( '/-?[0-9]+/', $result, $matches );
				$result = @ ( int ) $matches [0];
				break;
			
			case 'FLOAT' :
			case 'DOUBLE' :
				// Only use the first floating point value
				@ preg_match ( '/-?[0-9]+(\.[0-9]+)?/', $result, $matches );
				$result = @ ( float ) $matches [0];
				break;
			
			case 'BOOL' :
			case 'BOOLEAN' :
				$result = ( bool ) $result;
				break;
			
			case 'ARRAY' :
				if (! is_array ( $result )) {
					$result = array ($result );
				}
				break;
			
			case 'STRING' :
				$result = ( string ) $result;
				break;
			
			case 'WORD' :
				$result = ( string ) preg_replace ( '#\W#', '', $result );
				break;
			
			case 'NONE' :
			default :
				// No casting necessary
				break;
		}
		return $result;
	}
	
}
