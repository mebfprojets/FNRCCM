<?php

class Model_Membergroup extends Sirah_Model_Default
{

	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectGroups = $dbAdapter->select()->from(array("G"=>$tablePrefix."rccm_members_groups" ));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectGroups->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectGroups->order(array("G.libelle ASC"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectGroups->limitPage(  $pageNum,$pageSize);
		}
		return $dbAdapter->fetchAll( $selectGroups, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator($filters = array())
	{
		$table        = $this->getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectGroups = $dbAdapter->select()->from(array("G"=> $tablePrefix."rccm_members_groups" ), array("G.groupid"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectGroups->where("G.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectGroups->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectGroups );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectGroups )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
         
  }

