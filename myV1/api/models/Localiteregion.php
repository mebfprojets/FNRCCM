<?php

class Model_Localiteregion extends Sirah_Model_Default
{
	 
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$tableName       = $table->info("name");
		$selectRegion    = $dbAdapter->select()->from(array("R" => $tableName));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectRegion->where("R.code=?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegion->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && !empty($filters["regionid"]) && (null!==$filters["regionid"]) ) {
			$selectRegion->where("R.regionid= ?", intval( $filters["regionid"] ) );
		}
		$selectRegion->order(array("R.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegion->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectRegion, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectRegion  = $dbAdapter->select()->from(array("R" => $tableName), array("R.regionid"));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectRegion->where("R.code=?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectRegion->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["regionid"]) && !empty($filters["regionid"]) && (null!==$filters["regionid"]) ) {
			$selectRegion->where("R.regionid= ?", intval( $filters["regionid"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegion );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegion )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}	 
}