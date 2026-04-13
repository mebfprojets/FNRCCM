<?php

class Model_Country extends Sirah_Model_Default
{
	protected $_tableClass  = "Table_Countries";
	
	public function getDefault( $ip = "197.239.66.113")
	{
		if ( null == $ip ) {
			 $ip  = Sirah_Functions::getIpAddress();
		}
		$ipNumber  = $this->IPAddress2IPNumber( $ip );
		$dbAdapter = $this->_getTable()->getAdapter();
		$select    = "SELECT ipFrom,ipTo FROM system_countries_iptocountry WHERE $ipNumber BETWEEN ipFrom AND ipTo ";
		
		return $dbAdapter->fetchAll( $select );
	}
	
	public function IPAddress2IPNumber( $dotted ) 
	{
		$dotted = preg_split( "/[.]+/", $dotted);
		$ip = (double) ($dotted[0]*16777216)+($dotted[1]*65536)+($dotted[2]
				*256)+($dotted[3]);
		return $ip;
	}
	
	function IPNumber2IPAddress($number) {
		$a = ($number/16777216)%256;
		$b = ($number/65536)%256;
		$c = ($number/256)%256;
		$d = ($number)%256;
		$dotted = $a.".".$b.".".$c.".".$d;
		return $dotted;
	}

	

  }

