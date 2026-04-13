<?php

class Model_Suretetype extends Sirah_Model_Default
{
	 
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectLocalite = $dbAdapter->select()->from(array("L" => $tablePrefix ."rccm_localites" ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && !empty($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectLocalite->where("L.parentid = ?", intval( $filters["parentid"] ) );
		}
		$selectLocalite->order(array("L.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectLocalite->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectLocalite, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectLocalite  = $dbAdapter->select()->from(array("L" => $tablePrefix ."rccm_localites" ), array("L.localiteid"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectLocalite->where("L.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["parentid"]) && !empty($filters["parentid"]) && (null!==$filters["parentid"]) ) {
			$selectLocalite->where("L.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectLocalite->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectLocalite );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectLocalite )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	 
}