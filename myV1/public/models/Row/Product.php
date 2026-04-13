<?php

class Model_Product extends Sirah_Model_Default
{
	
	public function documentname($documentid)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectDocument = $dbAdapter->select()->from(array("P"=> $table->info("name")),array("P.libelle"))
				                              ->join(array("D"=> $tablePrefix."system_users_documents"),"D.documentid=P.documentid",null)
											  ->where("D.documentid=?",$documentid)
											  ->where("P.documentid=?",$documentid);
		return $dbAdapter->fetchOne($selectDocument);
	}
	
	public function createCode()
	{
		$table               = $this->getTable();
		$dbAdapter           = $table->getAdapter();
		$tablePrefix         = $table->info("namePrefix");
		$tableName           = $table->info("name");
		
		$selectProduit       = $dbAdapter->select()->from(array("P" => $tableName),array("total"=>"COUNT(P.productid)"));
		$nbreTotal           = $dbAdapter->fetchOne($selectProduit)+1;
		$newCodeProduit      = sprintf("Po-%06d", $nbreTotal );
		while($existProduit  = $this->findRow($newCodeProduit, "code", null, false)) {
			  $nbreTotal++;
			  $newCodeProduit= sprintf("Po-%06d", $nbreTotal );
		}
		
		return $newCodeProduit;
	}
	
	public function count()
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectProduit = $dbAdapter->select()->from(array("P"=> $table->info("name")),array("COUNT(P.productid)"));
		
		return $dbAdapter->fetchOne($selectProduit);
	}
	
	public function reservationid($type=1)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectProduitId = $dbAdapter->select()->from(array("P"=> $table->info("name")),array("P.productid"))
		                                       ->where("P.libelle LIKE '%reservation%'")
											   ->where("P.documentid=0")
											   ->where("P.registreid=0");
	    if( $type==2 ) {
			$selectProduitId->where("P.libelle LIKE '%Moral%'");
		}
	    return $dbAdapter->fetchOne($selectProduitId);
	}
	
	public function registredoc($documentid,$registreid=null)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("P" => $table->info("name")),array("P.productid","P.code","P.libelle","P.cout_ht","P.cout_ttc","P.description","P.documentid","P.catid","P.documentcatid"))
				                               ->join(array("D" => $tablePrefix."system_users_documents") ,"D.documentid=P.documentid", array("D.filename","D.filepath","D.filextension","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                               ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=P.documentid",array("RD.access"))
				                               ->where("RD.documentid=?",$documentid)
											   ->where("D.documentid=?",$documentid)
											   ->where("P.documentid=?",$documentid);
		 
		if( null !== $registreid ) {
			$selectDocuments->where("RD.registreid=?",$registreid);
		}
		$selectDocuments->order(array("RD.registreid DESC","RD.documentid DESC"));
		return $dbAdapter->fetchRow( $selectDocuments, array() , Zend_Db::FETCH_ASSOC );
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectProduct = $dbAdapter->select()->from(array("P"=> $table->info("name")),  array("P.productid","P.libelle","P.cout_ht","P.cout_ttc","P.description" ))
				                             ->join(array("C"=> $tablePrefix."erccm_vente_products_categories") ,"C.catid=P.catid" , array("categorie" => "C.libelle"));
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectProduct->where("P.libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}		
		if( isset($filters["catid"]) && intval($filters["catid"])){
			$selectProduct->where("P.catid=?" , intval($filters["catid"]));
		}	
		if(intval($pageNum) && intval($pageSize)) {
			$selectProduct->limitPage($pageNum , $pageSize);
		}
		$selectProduct->order(array("P.productid DESC"));
		return $dbAdapter->fetchAll($selectProduct, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListPaginator($filters = array())
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
	    $selectProduct = $dbAdapter->select()->from(array("P"=>$table->info("name")),  array("P.productid"))
				                             ->join(array("C"=>$tablePrefix ."erccm_vente_products_categories"),"C.catid=P.catid" ,null)	;
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectProduct->where("P.libelle LIKE ?" , "%".strip_tags($filters["libelle"])."%");
		}		
		if( isset($filters["catid"]) && intval($filters["catid"])){
			$selectProduct->where("P.catid=?" , intval($filters["catid"]));
		}	
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectProduct);
		$rowCount          = intval(count($dbAdapter->fetchAll($selectProduct)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
	



}
