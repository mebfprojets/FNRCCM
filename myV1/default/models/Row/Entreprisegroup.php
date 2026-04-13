<?php

class Model_Entreprisegroup extends Sirah_Model_Default
{		
	public function getList($filters = array() , $pageNum = 0 , $pageSize = 0)
	{		
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");		
		$selectGroup  = $dbAdapter->select()->from(array("E" => $table->info("name")),  array("E.groupid", "E.libelle", "E.description"));
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"] ) ) {
			$selectGroup->where("E.libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}	
		if(intval($pageNum) && intval($pageSize)) {
			$selectGroup->limitPage($pageNum , $pageSize);
		}
		$selectGroup->order(array("E.groupid DESC"));		
		return $dbAdapter->fetchAll($selectGroup, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{		
	    $table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");		
	    $selectGroup  = $dbAdapter->select()->from(array("E" => $table->info("name")) , array("E.groupid"));
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"] ) ) {
			$selectGroup->where("E.libelle LIKE ?", "%".strip_tags($filters["libelle"])."%");
		}	
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectGroup );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectGroup )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);		 
		return $paginator;		
	}		 
}

