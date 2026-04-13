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
 * Cette classe correspond à l'adaptateur des fichiers CSV
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Sirah_Filesystem_Adapter_Csv extends Sirah_Filesystem_Adapter_Abstract
{

	/**
	 * @var array tableau des lignes du fichier
	 */
	protected $_lines     = array();
			
	/**
	 * @var array tableau contenant la première ligne du fichier
	 */
	protected $_header     = array();
	
	/**
	 * @var boolean
	 */
	protected $_has_header = true;
	
	
	/**
	 *  @var integer 
	 */
	protected $_length    = 4096;
	
	/**
	 *  @var string
	 */
	protected $_delimiter  = ";";
	
	
	/**
	 * @var array tableau des lignes du fichier
	 */
	protected $_updatedLines = array();
	
	
	/**
	 * Permet de creer l'objet
	 *
	 * @public
	 * @param   string   $filename     Le nom du fichier
	 * @param   string   $mode         Le mode d'acces au fichier.
	 * @param   mixed    $lock         Le mode de verrouillage du fichier
	 * @param   array    $params       Des parametres optionnels
	 *
	 * @since
	 */
	public function __construct( $filename , $options = array() , $mode = O_READ_DONLY , $lock=false)
	{
		if( isset($options["delimiter"]) && ( strlen( $options["delimiter"] ) != 1 ) ) {
			throw new Sirah_Filesystem_Exception( " Le delimiteur que vous avez fourni, est invalide ");
		}
		parent::__construct( $filename , $options , $mode , $lock );
		if( strtolower( $this->_extension ) != "csv" && strtolower( $this->_extension ) != "xls" &&  strtolower( $this->_extension ) != "xlxs"){
			throw new Sirah_Filesystem_Exception( " Le fichier $filename n'utilise pas un adaptateur valide ");
		}
		if( empty( $this->_lines ) ) {
			$this->_lines  = $this->getDefaultLines( );
		} else {
			$this->save();
		}
	}
	
	public function sanitizeFieldKey( $fieldKey )
	{
		if( !empty( $fieldKey ) ) {
			$fieldKey = preg_replace('/\s+/' , '_' , trim( $fieldKey ) );
		}
		return $fieldKey; 		
	}
	
	public function sanitizeFieldValue( $fieldValue )
	{
		if( ( is_string( $fieldValue ) && empty( $fieldValue ) ) || ( $fieldValue == null ) || is_null( $fieldValue ) && is_string( $fieldValue ) ) {
			 $fieldValue = " ";
		}
		return $fieldValue ;
	}
		
	/**
	 * Permet de récupérer les lignes par defaut du fichier
	 *
	 * @return array les lignes du fichier
	 *
	 * @since
	 */
	public function getDefaultLines( $from = 0 , $to = 0 )
	{
		$handle        = $this->getHandle();
		$from          = ( 0 === $from ) ? ( ( $to > 0) ? 0 : -1 ) : $from;
		$line          = 0;
		$defaultLines  = array();
		if( $this->_has_header ) {
			$header = $this->getHeader();	
			while ( $from < $to && ( $row = fgetcsv( $handle , $this->_length ,  $this->_delimiter ) ) !== FALSE ) {				
				$defaultLines[$line]  = array();
				for ( $lineField = 0 ; $lineField < count( $header ) ; $lineField++ ) {
					  $fieldKey                       = $this->sanitizeFieldKey( $header[ $lineField ] );
					  $defaultLines[$line][$fieldKey] = (array_key_exists( $fieldKey , $row )) ? $this->sanitizeFieldValue( $row[$fieldKey] ) : ( ( array_key_exists( $lineField , $row ) ) ? $this->sanitizeFieldValue( $row[$lineField] ) : "" );
				}
				if( $to > 0 ) {
					$from++;
				}
				$line++;
			}
		} else {
			while ( $from < $to && ( ( $row = fgetcsv( $handle , $this->_length ,  $this->_delimiter ) ) !== FALSE ) ) {
				$defaultLines[ $line ]  = array();
				for ( $lineField = 0 ; $lineField < count( $row ) ; $lineField++ ) {
					  $defaultLines[ $line ][ $lineField ] = ( array_key_exists( $lineField , $row ) )  ? $this->sanitizeFieldValue( $row[ $lineField ] ) : " ";
				}
				if( $to > 0 ) {
					$from++;
				}
				$line++;
			}
		}
		return $defaultLines; 		
	}
	
	
	/**
	 * Permet de récupérer la ligne de l'entete du fichier
	 *
	 * @return array un tableau associatif des différents champs de la première ligne
	 *
	 */
	public function getHeader( )
	{
		$handle   = $this->getHandle();
		if( empty( $this->_header ) && $this->_has_header ) {
			$header = fgetcsv( $handle , $this->_length , $this->_delimiter );
			if( FALSE !== $header ) {
				for ( $h = 0 ; $h < count( $header ) ; $h++ ) {
					  $this->_header[ $h ] = $header[$h];
				}
			}
		}
		return $this->_header;
	}
	
	
	/**
	 * Permet de récupérer la ligne de l'entete du fichier
	 *
	 * @param array $row un tableau associatif des différents champs de la première ligne
	 *
	 */
	public function setHeader( $row )
	{
		if( !empty( $row ) ) {
			 $this->_header  = $row ;
			 return true;
		}
		return false;
	}
	
	
	/**
	 * Permet de mettre à jour les paramètres de la classe
	 *
	 * @param   array   $options  les options
	 *
	 * @return  Sirah_Filesystem_Adapter_Abstract instance
	 *
	 * @since
	 */
	public function setOptions( $options )
	{
		parent::setOptions( $options );	
		if( isset( $options["has_header"] ) ) {
			$this->_has_header  = (bool) $options["has_header"];
			if( isset( $options["lines"] ) && !isset( $options["header"] ) && $this->_has_header ) {
				$header  = array_shift( $options["lines"] );
				$this->setHeader( $header );
			}
		} 
		if( isset( $options["header"] ) && $this->_has_header ) {
			$this->setHeader( $options["header"] );
		}
		if( isset( $options["delimiter"] ) && !empty( $options["delimiter"] ) ) {
			$this->_delimiter = $options["delimiter"];
		}
		if( isset( $options["length"] ) && intval( $options["length"] ) ) {
			$this->_length    = intval( $options["length"] );
		}
		if( isset( $options["lines"] ) && !empty( $options["lines"] )  ) {
			$this->setLines( $options["lines"] );
		}		
	}
	
	/**
	 * Permet de mettre à jour les lignes du fichier
	 *
	 * @param   array $lines les lignes
	 *
	 * @since
	 */
	public function setLines( $lines = array()  )
	{
		if( empty( $lines ) ) {
			return true;
		}
		$fileLines  = $this->_lines;
		if( !empty(  $lines ) ) {
			 foreach( $lines as $linekey => $line ) {
				if ( is_array( $line ) ) {
					if ( $this->_has_header && !empty( $this->_header ) ) {
					    for ( $lineField = 0 ; $lineField < count( $this->_header ) ; $lineField++ ) {
						      $lineFieldKey                               = $this->sanitizeFieldKey( $this->_header[ $lineField ] );						      
						      if( in_array( $lineFieldKey , $this->_header ) ) {
						      	  $fileLines[ $linekey ][ $lineFieldKey ] = $this->sanitizeFieldValue( $line[ $lineFieldKey ] );
						      } else {
						      	  $fileLines[ $linekey ][ $lineField ]    = ( array_key_exists( $lineField , $this->_header ) ) ? $this->sanitizeFieldValue( $line[ $lineField] ) : "";
						      }
					    }
					} else {
						$fileLines[ $linekey ]  = $line  ;
					}
				}
			}
			if ( $this->_has_header && empty( $this->_header )  ) {
				 $this->_header  = array_shift( $fileLines );
			}
			$this->_lines  = $fileLines;
			return count( $fileLines );
		}
	  return false;
	}
	
	public function getDelimiter()
	{
		return $this->_delimiter;
	}
	
	public function setDelimiter( $delimiter )
	{
		if( strlen( $delimiter ) == 1 ) {
			$this->_delimiter = $delimiter; 
		}	
		return $this;	
	}
	
	public function getLength()
	{
		return $this->_length;
	}
	
	public function setLength( $length )
	{
		if( $length > 1 ) {
			$this->_length = $length;
		}
		return $this;
	}
	
	/**
	 * Permet de vérifier si le fichier a une entete ou pas
	 *
	 *
	 */
	public function hasHeader()
	{
		return $this->_has_header;
	}
	
	/**
	 * Permet de sauvegarder le fichier avec les dernières données
	 *
	 */
	public function save( $rows = array() , $filename = null , $copy = true )
	{		
		$savingFile = $this;
		$header     = $savingFile->getHeader();
		
		if( null !== $filename && ( $filename != $this->getPathname() )) {
			$filename_destination = dirname( $filename );
			$copyNewname          = $this->getName() . "." .$this->getFilextension();
			if( $copy ) {
				$savingFileName   = $savingFile->copy( $filename_destination , true , $copyNewname );
				$savingFile       = new self( $savingFileName , array( "delimiter" => $savingFile->getDelimiter() , "length" => $this->getLength() , "has_header" => $this->hasHeader() , "header" => $header ,"lines" => $rows  ) , "wb+" );
				return true;
			} else {
				$savingFile->move( $filename_destination , true , $copyNewname );
			}
		}
		if( !$savingFile instanceof Sirah_Filesystem_Adapter_Abstract ) {
			 return false;
		}
		if( $savingFile->isLocked() ) {
			throw new Sirah_Filesystem_Exception( "Impossible de mettre à jour le fichier ".$this->getPathname().", car celui-ci est verrouillé ");
		}
		if( !empty( $header ) && $savingFile->hasHeader() ) {
			$savingFile->writeLine( $header , "\n" , 0 ) ;
		}
		if( !$savingFile->setLines( $rows ) ) {
			return false;
		}
		$lines  = $savingFile->getLines();
		if (!empty( $lines ) ) {
			$df  = $this->getHandle();
			fputs( $df, "\xEF\xBB\xBF" ); // UTF-8 BOM !!!!!
			foreach ( $lines as $line ) {
				      $savingFile->writeLine( $line , "\n" , 0 ) ;
			}
		}
		return true;
	}
	
	/**
	 * Permet d'insérer une ligne dans le fichier 
	 *
	 *
	 * @since
	 */
	public function writeLine( $line , $crlf = "\n" , $offset = 0 )
	{
		if( $offset ) {
			$this->seek( $offset );
		}
		if( empty( $line ) ) {
			throw new Sirah_Filesystem_Exception( " La ligne que vous souhaitez insérer dans le fichier ".$this->getPathname()." est vide. " );
		}
		$line        = array_map( array("Sirah_Filesystem_Adapter_Csv" , "sanitizeFieldValue") , $line );
		$bytes       = 0;	
		$handle      = $this->getHandle();
		$delimiter   = $this->getDelimiter();		
		if (  FALSE === ( $bytes = fputcsv( $handle , $line , $delimiter ) ) ) {
			  throw new Sirah_Filesystem_Exception( " L'écriture de la ligne a echoué " );
		}
		return $bytes;
	}
	
	/**
	 * Permet de récupérer les lignes du fichier
	 *
	 * @return array les lignes du fichier
	 */
	public function getLines( )
	{
		if( empty( $this->_lines ) ) {
			$this->_lines = $this->getDefaultLines();
		}
		return $this->_lines;
	}
	
	
	public function writeHTML($htmlString)
	{
		if( empty($htmlString) ) {
			return false;
		}
		$csvHtml          = str_get_html($htmlString);
		foreach( $csvHtml->find('tr') as $element){
                 $td      = array();
                 foreach( $element->find('th') as $row)  {
                          $td[] = $row->plaintext;
                 }
                 $this->writeLine($td);
                 $td     = array();
                 foreach( $element->find('td') as $row)  {
                          $td[] = $row->plaintext;
                 }
                 $this->writeLine($td);
        }
		return true;
	}
	
	/**
	 * Permet de récupérer une ligne
	 *
	 * @param  string $key la clé de la ligne
	 * 
	 * @return array un tableau contenant les champs de la ligne
	 */
	public function getLine( $key )
	{
		if( isset( $this->_lines[ $key ] ) ) {
			return $this->_lines[ $key ];
		}
		return false;
	}
	
	/**
	 * Permet de récupérer les lignes du fichier
	 *
	 * @param array   $newline un tableau de données correspondant à la ligne 
	 * 
	 * @param string  $key une chaine de caractère indiquant la clé de la ligne
	 * 
	 * @return 
	 */
	public function insert( $newline , $key )
	{
		if( !is_array( $newline ) || empty( $key ) || ( null == $key ) ) {
			throw new Sirah_Filesystem_Exception( "Impossible d'insérer la ligne, car elle est invalide " );
		}
		if( !isset( $this->_lines[ $key ] ) ) {
			$this->setLines( array( $key => $newline ) );
			return $this->writeLine( $newline );
		}	
		return false;			
	}
	
	
	/**
	 * Permet de supprimer une ligne du fichier
	 *
	 * @param string  $key une chaine de caractère indiquant la clé de la ligne
	 *
	 * @return
	 */
	public function delete( $key )
	{
		if( isset( $this->_lines[ $key ] ) ) {
			unset( $this->_lines[ $key ] );
			return $this->save();
		}
		return false;
	}
	
	
	/**
	 * Permet de mettre à jour une ligne du fichier
	 *
	 * @param string  $key          une chaine de caractère indiquant la clé de la ligne
	 * @param array   $updatingline un tableau correspondant aux nouvelles données de la ligne 
	 *
	 * @return
	 */
	public function update( $key , $updatingline )
	{
		if( isset( $this->_lines[ $key ] ) && !empty( $updatingline ) ) {
			$this->_lines[ $key ]  = $updatingline;
			return $this->save();
		}
		return false;
	}
	
	
	/**
	 * Permet d'envoyer le fichier au navigateur
	 *
	 * @param string  $name le nom du fichier
	 *
	 * @return
	 */
	public function Output( $name = "exportlist.csv")
	{
		$filename = $this->getPathname();
		$data     = ob_get_contents();
		if(!empty($data)) {
			throw new Sirah_Filesystem_Exception(" Impossible d'envoyer ce fichier au navigateur, des données ont déjà été envoyées : " . $data );
		}
		header('Content-Description: File Transfer');
		if( headers_sent($headerFilename, $linenum)) {
			throw new Sirah_Filesystem_Exception(" Des données ont été déjà envoyées au navigateur depuis le fichier $headerFilename à la ligne $linenum, impossible d'envoyer le contenu du fichier");
		}
		header('Cache-Control: public, must-revalidate, max-age=0');
		header('Pragma: public');
		header('Expires: 0');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		if( strpos(php_sapi_name(), 'cgi') === false) {
			header('Content-Type: application/force-download');
			header('Content-Type: application/octet-stream', false);
			header('Content-Type: application/download', false);
			header('Content-Type: application/excel', false);
			header('Content-Type: application/csv', false);
		} else {
			header('Content-Type: text/csv; charset=utf-8');
		}
		header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.basename($name).'";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($filename));
		echo "\xEF\xBB\xBF";
		echo $this->readAll();		
	}
	
	

 }
