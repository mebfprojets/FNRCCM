<?php

class Model_Countrycity extends Sirah_Model_Default
{
	protected $_tableClass  = "Table_Countrycities";
	
	public function getDefault( $ip = null )
	{
		if ( null == $ip ) {
			 $ip  = Sirah_Functions::getIpAddress();
		}		
		$table    = $this->_getTable();
		$select   = $table->select()->from( $table , array("city"))->where("ipFrom <= INET_ATON( ? )", $ip)->where("ipTo >= INET_ATON( ? ) ", $ip );
		
		return $table->fetchOne( $select );
	}

	

  }

