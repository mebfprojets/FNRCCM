<?php

class Model_Localitecommune extends Sirah_Model_Default
{
	 
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectCommune  = $dbAdapter->select()->from(array("C" => $tableName));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectCommune->where("C.code=?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectCommune->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && intval($filters["regionid"]) ) {
			$selectCommune->where("C.regionid= ?", intval( $filters["regionid"] ) );
		}
		if( isset($filters["provinceid"]) && intval($filters["provinceid"]) ) {
			$selectCommune->where("C.provinceid= ?", intval( $filters["provinceid"] ) );
		}
		if( isset($filters["communeid"]) && intval($filters["communeid"]) ) {
			$selectCommune->where("C.communeid= ?", intval( $filters["communeid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"]) ) {
			$selectCommune->where("C.localiteid= ?", intval( $filters["localiteid"] ) );
		}
		$selectCommune->order(array("C.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectCommune->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectCommune, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectCommune  = $dbAdapter->select()->from(array("C" => $tableName), array("C.communeid"));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectCommune->where("C.code=?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectCommune->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && intval($filters["regionid"]) ) {
			$selectCommune->where("C.regionid= ?", intval( $filters["regionid"] ) );
		}
		if( isset($filters["provinceid"]) && intval($filters["provinceid"]) ) {
			$selectCommune->where("C.provinceid= ?", intval( $filters["provinceid"] ) );
		}
		if( isset($filters["regionid"]) && intval($filters["regionid"]) ) {
			$selectCommune->where("C.regionid= ?", intval( $filters["regionid"] ) );
		}
		if( isset($filters["provinceid"]) && intval($filters["provinceid"]) ) {
			$selectCommune->where("C.provinceid= ?", intval( $filters["provinceid"] ) );
		}
		if( isset($filters["communeid"]) && intval($filters["communeid"]) ) {
			$selectCommune->where("C.communeid= ?", intval( $filters["communeid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"]) ) {
			$selectCommune->where("C.localiteid= ?", intval( $filters["localiteid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectCommune );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectCommune )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	 
}