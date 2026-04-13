<?php

class Model_Registrephysique extends Sirah_Model_Default
{
	
	public function documents( $registreid = null  )
	{
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
				                                                                                                    "D.filedescription","D.filesize","D.documentid","D.resourceid", "D.userid"))
				                                 ->join(array("C" => $tablePrefix ."system_users_documents_categories"),"C.id = D.category", array("category"=> "C.libelle"))
				                                 ->join(array("RD"=> $tablePrefix ."rccm_registre_documents"),"RD.documentid  = D.documentid",array("RD.access"))
				                                 ->where("RD.registreid = ?", $registreid  );
		$selectDocuments->order(array("RD.registreid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R"    => $tablePrefix ."rccm_registre" ), array("R.registreid", "R.numero", "R.libelle", "R.description", "R.date","R.category", "annee" => "FROM_UNIXTIME(R.date,'%Y')"))
		                                      ->join(array("RP"   => $tablePrefix ."rccm_registre_physique")   , "RP.registreid   = R.registreid",array("RP.exploitantid"))
		                                      ->join(array("RE"   => $tablePrefix ."rccm_registre_exploitants"), "RE.exploitantid = RP.exploitantid", array("RE.nom", "RE.prenom","RE.adresse"))
		                                      ->joinLeft(array("L"=> $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", array("domaine" =>"D.libelle"));
	    if(isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$likeNumero    = new Zend_Db_Expr("R.numero  LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeLibelle   = new Zend_Db_Expr("R.libelle LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeLastname  = new Zend_Db_Expr("RE.nom    LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeFirstname = new Zend_Db_Expr("RE.prenom LIKE '%".strip_tags($filters["searchQ"])."%'");
			$selectRegistre->where("{$likeNumero} OR {$likeLibelle} OR {$likeLastname} OR {$likeFirstname}");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) && (null!==$filters["nom"])){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && (empty($filters["prenom"]) || (null ==$filters["prenom"]) ) ) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"]) && (null!==$filters["prenom"])){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRegistre->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("FROM_UNIXTIME(R.date,'%Y') = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistre->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d') = ? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		$selectRegistre->order(array("R.date DESC","L.libelle ASC", "D.libelle ASC", "R.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R"    => $tablePrefix ."rccm_registre" ), array("R.registreid"))
		                                      ->join(array("RP"   => $tablePrefix ."rccm_registre_physique")   , "RP.registreid   = R.registreid", null )
		                                      ->join(array("RE"   => $tablePrefix ."rccm_registre_exploitants"), "RE.exploitantid = RP.exploitantid", null )
		                                      ->joinLeft(array("L"=> $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", null )
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", null );
	   if(isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$likeNumero    = new Zend_Db_Expr("R.numero  LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeLibelle   = new Zend_Db_Expr("R.libelle LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeLastname  = new Zend_Db_Expr("RE.nom    LIKE '%".strip_tags($filters["searchQ"])."%'");
			$likeFirstname = new Zend_Db_Expr("RE.prenom LIKE '%".strip_tags($filters["searchQ"])."%'");
			$selectRegistre->where("{$likeNumero} OR {$likeLibelle} OR {$likeLastname} OR {$likeFirstname}");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) && (null!==$filters["nom"])){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && (empty($filters["prenom"]) || (null ==$filters["prenom"]) ) ) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"]) && (null!==$filters["prenom"])){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRegistre->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("FROM_UNIXTIME(R.date,'%Y') = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistre->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d') = ? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}             
  }