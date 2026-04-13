<?php

class Model_Registrestatut extends Sirah_Model_Default
{
	

  
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
		$tableName   = $table->info("name");
		$selectStatut= $dbAdapter->select()->from(array("S" => $tableName ));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectStatut->where("S.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectStatut->where("S.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectStatut->order(array("S.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectStatut->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectStatut, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectStatut  = $dbAdapter->select()->from(array("S" => $tableName), array("S.statusid"));
	
	    if( isset($filters["code"]) && !empty($filters["code"]) && (null!==$filters["code"]) ){
			$selectStatut->where("S.code = ?","%".strip_tags($filters["code"])."%");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectStatut->where("S.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}		
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectStatut );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectStatut )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}