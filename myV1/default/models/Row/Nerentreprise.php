<?php

class Model_Nerentreprise extends Sirah_Model_Default
{
	 
	 
	public function getList($filters = array() , $pageNum = 0 , $pageSize = 0, $orders=array("E.num_rccm ASC"))
	{
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectEntreprises = $dbAdapter->select()->from(array("E"=> $tablePrefix."fnere_registres_entreprises"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectEntreprises->where("E.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( count($orders) ) {
			$selectEntreprises->order($orders);
		} else {
			$selectEntreprises->order(array("E.libelle ASC"));
		}
		
		if( intval($pageNum) && intval($pageSize)) {
			$selectEntreprises->limitPage( $pageNum , $pageSize);
		}
		//print_r($selectEntreprises->__toString());die();
		return $dbAdapter->fetchAll( $selectEntreprises, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$selectEntreprises  = $dbAdapter->select()->from(array("E"=>$tablePrefix."fnere_registres_entreprises"),array("E.entrepriseid"));
	
		 
		if( intval($pageNum) && intval($pageSize)) {
			$selectEntreprises->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectEntreprises );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectEntreprises )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}

}
