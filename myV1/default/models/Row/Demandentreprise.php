<?php

class Model_Demandentreprise extends Sirah_Model_Default
{
	
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("E.nomcommercial ASC"))
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$tableName        = $table->info("name");
		$selectEntreprise = $dbAdapter->select()->from(array("E"=> $tableName))
		                                        ->join(array("D"=> $tablePrefix."rccm_domaines"), "D.domaineid=E.domaineid", array("domaine"=> "D.libelle"));
	
		if( isset($filters["libelle"])       && !empty($filters["libelle"])){
			$selectEntreprise->where("E.nomcommercial LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["nomcommercial"]) && !empty($filters["nomcommercial"]) ){
			$selectEntreprise->where("E.nomcommercial LIKE ?","%".strip_tags($filters["nomcommercial"])."%");
		}
	    if( isset($filters["domaineid"]) && intval($filters["domaineid"]) ) {
			$selectEntreprise->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["formid"]) && intval($filters["formid"]) ) {
			$selectEntreprise->where("E.formid = ?", intval( $filters["formid"] ) );
		}
		if( isset($filters["promoteurid"]) && intval($filters["promoteurid"]) ) {
			$selectEntreprise->where("E.promoteurid=?", intval( $filters["promoteurid"] ) );
		}
		if( isset($filters["demandeurid"]) && intval($filters["demandeurid"]) ) {
			$selectEntreprise->where("E.demandeurid=?", intval( $filters["demandeurid"] ) );
		}
		if( isset($filters["reserved"])    && (intval($filters["reserved"])<=1  ||  intval($filters["reserved"])>=0)) {
			$selectEntreprise->where("E.reserved=?", intval( $filters["reserved"] ) );
		}
		if( isset($filters["expired"]) && (intval($filters["expired"])<=1  ||  intval($filters["expired"])>=0)) {
			$selectEntreprise->join(array("RD"=> $tablePrefix."reservation_demandes"), "RD.entrepriseid=E.entrepriseid",array("RD.expired"))			                 
			                 ->join(array("RV"=> $tablePrefix."reservation_demandes_reservations"),"RV.reservationid=RD.demandeid",null)
			                 ->where("RV.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["blacklisted"]) && ($filters["blacklisted"]!=="null") && intval($filters["blacklisted"])<=1  &&  intval($filters["blacklisted"])>=0) {
			$selectEntreprise->where("E.blacklisted= ?", intval($filters["blacklisted"]));
		}
		if(!empty($orders) && is_array($orders) ) {
			$selectEntreprise->order($orders);
		}		
		if( intval($pageNum) && intval($pageSize)) {
			$selectEntreprise->limitPage( $pageNum , $pageSize);
		}
		//print_r($selectEntreprise->__toString());die();
		return $dbAdapter->fetchAll( $selectEntreprise, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$tableName        = $table->info("name");
		$selectEntreprise = $dbAdapter->select()->from(array("E"=> $tableName), array("E.domaineid"))
		                                        ->join(array("D"=> $tablePrefix ."rccm_domaines"),"D.domaineid=E.domaineid", array("domaine"=> "D.libelle"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectEntreprise->where("E.nomcommercial LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["nomcommercial"]) && !empty($filters["nomcommercial"]) ){
			$selectEntreprise->where("E.nomcommercial LIKE ?","%".strip_tags($filters["nomcommercial"])."%");
		}
	    if( isset($filters["domaineid"]) && intval($filters["domaineid"]) ) {
			$selectEntreprise->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["formid"]) && intval($filters["formid"]) ) {
			$selectEntreprise->where("E.formid = ?", intval( $filters["formid"] ) );
		}
		if( isset($filters["promoteurid"]) && intval($filters["promoteurid"]) ) {
			$selectEntreprise->where("E.demandeurid=?", intval( $filters["demandeurid"] ) );
		}
		if( isset($filters["demandeurid"]) && intval($filters["demandeurid"]) ) {
			$selectEntreprise->where("E.demandeurid=?", intval( $filters["demandeurid"] ) );
		}
		if( isset($filters["reserved"])    && (intval($filters["reserved"])<=1  ||  intval($filters["reserved"])>=0)) {
			$selectEntreprise->where("E.reserved=?", intval( $filters["reserved"] ) );
		}
		if( isset($filters["expired"]) && (intval($filters["expired"])<=1  ||  intval($filters["expired"])>=0)) {
			$selectEntreprise->join(array("RD"=> $tablePrefix."reservation_demandes"), "RD.entrepriseid=E.entrepriseid",array("RD.expired"))			                 
			                 ->join(array("RV"=> $tablePrefix."reservation_demandes_reservations"),"RV.reservationid=RD.demandeid",null)
			                 ->where("RV.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["blacklisted"]) && ($filters["blacklisted"]!=="null") && intval($filters["blacklisted"])<=1  &&  intval($filters["blacklisted"])>=0) {
			$selectEntreprise->where("E.blacklisted= ?", intval($filters["blacklisted"]));
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectEntreprise->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectEntreprise );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectEntreprise )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
}
