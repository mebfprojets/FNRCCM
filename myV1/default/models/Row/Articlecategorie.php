<?php

class Model_Articlecategorie extends Sirah_Model_Default
{
		
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$selectCategories = $dbAdapter->select()->from(array("C"=> $tablePrefix."erccm_crm_content_categories" ));
		
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectCategories->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"])){
			$selectCategories->where("C.code=?",strip_tags($filters["code"]));
		}
		$selectCategories->order(array("C.catid DESC","C.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectCategories->limitPage( $pageNum , $pageSize);
		}		
		return $dbAdapter->fetchAll( $selectCategories, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectCategories= $dbAdapter->select()->from(array("C"=> $tablePrefix."erccm_crm_content_categories" ), array("C.catid"));
		
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectCategories->where("C.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["code"]) && !empty($filters["code"])){
			$selectCategories->where("C.code=?",strip_tags($filters["code"]));
		}
		if( isset($filters["uniteid"]) && intval($filters["uniteid"])){
			$selectCategories->where("C.unitemesureid=?",intval($filters["uniteid"]));
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectCategories->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectCategories );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectCategories )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;	
	}         
}