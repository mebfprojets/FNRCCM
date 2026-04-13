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
		$select    = "SELECT ipFrom,ipTo FROM system_countries_ip WHERE $ipNumber BETWEEN ipFrom AND ipTo ";
		
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
	
	public function zipCode( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.code_calling"))->where("C.code=?", $countryCode);	
		 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	public function callingCode( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.code_calling"))->where("C.code=?", $countryCode);	
		 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	public function currency( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.code_currency"))->where("C.code=?", $countryCode);	
		 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	public function language( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.code_language"))->where("C.code=?", $countryCode);			 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	public function capital( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.capital"))->where("C.code=?", $countryCode);			 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	public function flag( $countryCode = null) 
	{
		if( null === $countryCode ) {
			$countryCode = $this->code;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.flag"))->where("C.code=?", $countryCode);			 
		return $dbAdapter->fetchOne( $selectCountry );
	}
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectCountry->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectCountry->order(array("C.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectCountry->limitPage($pageNum, $pageSize);
		}
		return $dbAdapter->fetchAll( $selectCountry, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectCountry = $dbAdapter->select()->from(array("C"=>$table->info("name")), array("C.id"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectCountry->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectCountry->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectCountry );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectCountry )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

	

}

