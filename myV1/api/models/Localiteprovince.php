<?php

class Model_Localiteprovince extends Sirah_Model_Default
{
	 
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectProvince  = $dbAdapter->select()->from(array("P" => $tableName));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectProvince->where("P.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectProvince->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && intval($filters["regionid"]) ) {
			$selectProvince->where("P.regionid= ?", intval( $filters["regionid"] ) );
		}
		if( isset($filters["provinceid"]) && intval($filters["provinceid"]) ) {
			$selectProvince->where("P.provinceid= ?", intval( $filters["provinceid"] ) );
		}
		$selectProvince->order(array("P.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectProvince->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectProvince, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectProvince  = $dbAdapter->select()->from(array("P" => $tableName), array("P.provinceid"));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectProvince->where("P.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectProvince->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && intval($filters["regionid"]) ) {
			$selectProvince->where("P.regionid= ?", intval( $filters["regionid"] ) );
		}
		if( isset($filters["provinceid"]) && intval($filters["provinceid"]) ) {
			$selectProvince->where("P.provinceid= ?", intval( $filters["provinceid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectProvince );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectProvince )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	 
}