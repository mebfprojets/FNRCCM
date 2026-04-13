<?php

class Route_Articles extends Zend_Controller_Router_Route
{

	public static function getInstance( Zend_Config $config )
	{
		$defs = ( $config->defaults instanceof Zend_Config  ) ? $config->defaults->toArray() : array();
		return new self( $config->route , $defs);
	}
	
	
	public function __construct( $route, $defaults = array())
	{
		$this->_route    = trim($route, $this->_urlDelimiter);
		$this->_defaults = (array)$defaults;
	}	
	
	public function match($path, $partial = false)
	{
		if ($path instanceof Zend_Controller_Request_Http) {
			$path    = $path->getPathInfo();
		}	
		$path        = trim($path, $this->_urlDelimiter);
		$pathBits    = explode( $this->_urlDelimiter, $path);
 
		if( count($pathBits) != 1 ) {
			return false;
		}
		
		$searchKey   = $pathBits[0];
		$dbAdapter   = Zend_Registry::get("db");
		$stringFilter= new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		$result      = $dbAdapter->fetchRow("SELECT code,catid FROM erccm_crm_content_categories WHERE code=?", $stringFilter->filter(addslashes($searchKey)));		
		if ( $result ) {
			 $values = $this->_defaults + $result;			 
			 return $values;
		}	
		return false;
	}

	public function assemble( $data = array(), $reset = false, $encode = false, $partial = false)
	{
		return $data['code'];
	}		
}