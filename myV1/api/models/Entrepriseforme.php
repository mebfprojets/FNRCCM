<?php

class Model_Entrepriseforme extends Sirah_Model_Default
{
	

  
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$selectFormes= $dbAdapter->select()->from(array("F" => $tableName));
	
	    if( isset($filters["code"]) && !empty($filters["code"])){
			$selectFormes->where("F.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectFormes->where("F.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["type"]) && !empty($filters["type"]) ){
			$selectFormes->where("F.type LIKE ?","%".strip_tags($filters["type"])."%");
		}
		if( isset($filters["typeid"]) && intval($filters["typeid"]) ){
			$selectFormes->where("F.typeid=?",intval($filters["typeid"]));
		}
		$selectFormes->order(array("F.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectFormes->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectFormes, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectFormes  = $dbAdapter->select()->from(array("F" => $tableName), array("F.formid"));
	
	    if( isset($filters["code"]) && !empty($filters["code"])){
			$selectFormes->where("F.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectFormes->where("F.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["type"]) && !empty($filters["type"]) ){
			$selectFormes->where("F.type LIKE ?","%".strip_tags($filters["type"])."%");
		}
		if( isset($filters["typeid"]) && intval($filters["typeid"]) ){
			$selectFormes->where("F.typeid=?",intval($filters["typeid"]));
		}	
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectFormes );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectFormes )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}