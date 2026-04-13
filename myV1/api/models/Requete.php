<?php

class Model_Requete extends Sirah_Model_Default
{
	public function typedocuments()
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocumentType= $dbAdapter->select()->from(array($tablePrefix ."system_users_documents_categories"),array("id", "libelle"))->where("applicationid = 2");
		
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectDocumentType->where("libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		$selectDocumentType->order(array(".libelle ASC"));
		 
		$rows = $dbAdapter->fetchPairs( $selectDocumentType );
		if( count( $rows )) {
			$rows[0] = "Selectionnez un type de document";
		} else {
			$rows    = array("Selectionnez un type de document");
		}
        return $rows;
	}
	

	    
	public function documents( $requestid = null, $type = null  )
	{
		if( !$requestid )  {
			 $requestid    = $this->requestid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
			                                                                                                        "D.filedescription","D.filesize","D.documentid","D.resourceid", "D.userid"))
				                                 ->join(array("C" => $tablePrefix ."system_users_documents_categories"),"C.id = D.category", array("category"=> "C.libelle"))
				                                 ->join(array("RD"=> $tablePrefix ."rccm_access_requests_documents"),"RD.documentid  = D.documentid",array("RD.requestid"))
				                                 ->join(array("R" => $tablePrefix ."rccm_access_requests" ), "R.requestid = RD.requestid", array());
		if( intval( $requestid )) {
			$selectDocuments->where("R.requestid = ?", $requestid  );
		}
		if( intval( $type )) {
			$selectDocuments->where("C.id = ?", intval( $type ));
		}				                                
		$selectDocuments->order(array("R.requestid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRequest = $dbAdapter->select()->from(array("RQ"=> $tablePrefix ."rccm_access_requests"), array("RQ.requestid","RQ.requestoken","RQ.typedocument","date"=>"RQ.creationdate","RQ.registreid","RQ.validated", "RQ.accepted"))
		                                     ->join(array("R" => $tablePrefix ."rccm_registre"),"R.registreid = RQ.registreid", array("numero"=>"R.numero", "R.libelle"))
		                                     ->join(array("U" => $tablePrefix ."system_users_account"), "U.userid = RQ.userid", array("U.lastname", "U.firstname", "U.email", "U.avatar", "U.userid", "U.country", "U.city", "U.username"));
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRequest->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRequest->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset( $filters["userid"]) && (intval( $filters["userid"]) > 0 ) )		{
			$selectRequest->where("RQ.userid = ?", intval( $filters["userid"] ));
		}
		if( isset( $filters["typedocument"]) && (intval( $filters["typedocument"]) > 0 ) )		{
			$selectRequest->where("RQ.typedocument = ?", intval( $filters["typedocument"] ));
		}
		if( isset( $filters["validated"]) && (intval( $filters["validated"]) >= 0 ) )		{
			$selectRequest->where("RQ.validated = ?", intval( $filters["validated"] ));
		}
		if( isset( $filters["registreid"]) && (intval( $filters["registreid"]) > 0 ) )		{
			$selectRequest->where("R.registreid = ?", intval( $filters["registreid"] ));
		}		 
		$selectRequest->order(array("RQ.creationdate DESC","R.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectRequest->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectRequest, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
	    $table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$selectRequest = $dbAdapter->select()->from(array("RQ"=> $tablePrefix ."rccm_access_requests"), array("RQ.requestid"))
		                                     ->join(array("R" => $tablePrefix ."rccm_registre"), "R.registreid = RQ.registreid", null);
	
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRequest->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRequest->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset( $filters["userid"]) && (intval( $filters["userid"]) > 0 ) )		{
			$selectRequest->where("RQ.userid = ?", intval( $filters["userid"] ));
		}
		if( isset( $filters["typedocument"]) && (intval( $filters["typedocument"]) > 0 ) )		{
			$selectRequest->where("RQ.typedocument = ?", intval( $filters["typedocument"] ));
		}
		if( isset( $filters["registreid"]) && (intval( $filters["registreid"]) > 0 ) )		{
			$selectRequest->where("R.registreid = ?", intval( $filters["registreid"] ));
		}
		if( isset( $filters["validated"]) && (intval( $filters["validated"]) >= 0 ) )		{
			$selectRequest->where("RQ.validated = ?", intval( $filters["validated"] ));
		}		
		if( intval($pageNum) && intval($pageSize)) {
			$selectRequest->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRequest );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRequest )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}		
}