<?php

class Model_Entrepriseforme extends Sirah_Model_Default
{
	

  
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$selectForme = $dbAdapter->select()->from(array("F" => $tableName));
	
	    if( isset($filters["code"]) && !empty($filters["code"])  ){
			$selectForme->where("F.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectForme->where("F.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectForme->order(array("F.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectForme->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectForme, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$tableName     = $table->info("name");
		$selectForme   = $dbAdapter->select()->from(array("F" => $tableName), array("F.formid"));
	
	    if( isset($filters["code"])    && !empty($filters["code"]) ){
			$selectForme->where("F.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectForme->where("F.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}	
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectForme );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectForme )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}