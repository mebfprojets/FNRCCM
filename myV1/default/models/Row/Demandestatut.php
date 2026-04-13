<?php

class Model_Demandestatut extends Sirah_Model_Default
{

    protected $_error       = null;
	
	
	public function setError($error)
	{
		$this->_error       = $error;
		return $this;
	}
	
	public function getError()
	{
		return $this->_error;
	}
 
         
}

