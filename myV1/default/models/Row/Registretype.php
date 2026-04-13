<?php

class Model_Registretype extends Sirah_Model_Default
{
	 
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectType   = $dbAdapter->select()->from(array("T" => $tablePrefix ."rccm_types" ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectType->where("T.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectType->order(array("T.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectType->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectType, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");
		$selectType  = $dbAdapter->select()->from(array("T" => $tablePrefix ."rccm_types" ), array("T.typeid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectType->where("T.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectType->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectType );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectType )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}
