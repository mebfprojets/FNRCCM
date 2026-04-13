<?php

class Model_Demande extends Sirah_Model_Default
{
	
	protected $_error       = null;
	
	
	public function setError($error)
	{
		$this->_error       = $error;
		return $this;
	}
	
	public function getError()
	{
		return $this->_error;
	}
	
	public function lastretry($demandeid=0)
	{
		if(!$demandeid ) {
			$demandeid      = $this->demandeid;
		}		
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectRetry        = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes_retries"))
		                                          ->join(array("D"=> $tablePrefix."reservation_demandes"),"D.demandeid=R.demandeid",array("R.statutid"))
	}
	
	public function getWebRequests($operatorId=null,$processed=null,$validated=null,$statutId=null)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$selectRequests     = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"))
		                                          ->join(array("R"=> $tablePrefix."reservation_demandes_requests"),"R.demandeid=D.demandeid",array("R.processed","R.validated"))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid", null)
												  ->join(array("N"=> $tablePrefix."reservation_demandeurs"),"N.demandeurid=D.demandeurid", null);
		if( null!== $operatorId){
			$selectRequests->where("D.demandeid IN (SELECT O.demandeid FROM reservation_demandes_requests_operators O WHERE O.operatorid=?)", intval($operatorId));
		}
        if( null!== $processed) {
			$selectRequests->where("R.processed=?" , intval($processed));
			if( intval($processed) ==  0) {
				$validated = 0;
				$selectRequests->where("R.validated=0");
			}
		}
        if( null!== $validated) {
			$selectRequests->where("R.validated=?" , intval($validated));
		}	
        return $dbAdapter->fetchAll($selectRequests, array(), Zend_Db::FETCH_ASSOC );		
	}
	
	public function countWebRequests($operatorId=null,$processed=null,$validated=null,$statutId=null)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectRequests     = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"),array("D.statutid","total"=>new Zend_Db_Expr("COUNT(D.demandeid)")))
		                                          ->join(array("R"=> $tablePrefix."reservation_demandes_requests"),"R.demandeid=D.demandeid",array("R.processed","R.validated"))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid", null)
												  ->join(array("N"=> $tablePrefix."reservation_demandeurs"),"N.demandeurid=D.demandeurid", null);
		if( null!== $operatorId) {
			$selectRequests->where("D.demandeid IN (SELECT O.demandeid FROM reservation_demandes_requests_operators O WHERE O.operatorid=?)", intval($operatorId));
		}
        if( null!== $processed) {
			$selectRequests->where("R.processed=?" , intval($processed));
			if( intval($processed) ==  0) {
				$validated = 0;
				$selectRequests->where("R.validated=0")
				               ->where("R.notified=0");
			}
		}
        if( null!== $validated) {
			$selectRequests->where("R.validated=?" , intval($validated))
			               ->where("D.disponible=?", intval($validated));
		}	
        return $dbAdapter->fetchRow($selectRequests, array(), Zend_Db::FETCH_ASSOC );		
	}
	
	public function getStatYears()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes" ), array("annee" => "FROM_UNIXTIME(R.date,'%Y')"))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
												  ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
												  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null);
		$selectStatistiques->group(array("FROM_UNIXTIME(R.date,'%Y')"))
		                   ->order(array("FROM_UNIXTIME(R.date,'%Y') DESC"))
						   ->limitPage(1,22);
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getTotal($userid=0,$filters=array())
	{
        $table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");		
		$selectTotal = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes" ), array("total" => "COUNT(R.demandeid)"))
		                                   ->join(array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                   ->join(array("D" => $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
										   ->join(array("E" => $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null);
		
		if( isset($filters["date"]) && !empty($filters["date"]) && (null!==$filters["date"])){
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectTotal->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectTotal->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"] ) ) {
			$selectTotal->where("R.date>= ?", intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] )  &&  intval($filters["periode_end"] ) ) {
			$selectTotal->where("R.date<= ?", intval( $filters["periode_end"] ) );
		}		
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectTotal->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectTotal->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		return $dbAdapter->fetchOne($selectTotal);
	}
	
	public function getNbreByUsers( $userid = 0, $role = null, $period_start = 0, $period_end = 0)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes"), array("nombre"=>"COUNT(DISTINCT(R.demandeid))","annee"=> "FROM_UNIXTIME(R.date,'%Y')","R.localiteid"))		                                         
												  ->join(array("L" => $tablePrefix."rccm_localites"      ), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
		                                          ->join(array("U" => $tablePrefix."system_users_account"), "U.userid = R.creatorid", array("U.lastname","U.firstname","U.username","U.phone1","U.phone2","U.activated","U.userid"));
												  
		if( intval($userid ))	
			$selectStatistiques->where("R.creatorid=?", intval($userid));	
		if( intval($period_end)){
			$selectStatistiques->where("R.date <= ?",intval($period_end));
		}
		if( intval($period_start )  ){
			$selectStatistiques->where("R.date >= ?",intval($period_start));
		}		 
		$selectStatistiques->group(array("R.creatorid","R.localiteid"))->order(array("R.creatorid DESC","R.localiteid DESC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	
	public function getNbreLocaliteAnnee( $localiteid, $annee=0, $creatorid =0,$filters=array())
	{
		if( isset($filters["annee"]) && !intval($annee)) {
			$annee          = $filters["annee"];
		}
		$filters["annee"]   = $annee;
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes" ), array("nombre" => "COUNT(DISTINCT(R.demandeid))"))		                                       												
												  ->join(array("L" => $tablePrefix."rccm_localites"),"L.localiteid =R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("U" => $tablePrefix."system_users_account"),"U.userid=R.creatorid", null)
												  ->where("R.localiteid=?", intval( $localiteid ))											  
												  ->where("FROM_UNIXTIME(R.date,'%Y')= ?", intval( $annee));
		if( intval($creatorid) ) {
			$selectStatistiques->where("R.creatorid=?", intval($creatorid));
		}	
        if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectStatistiques->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectStatistiques->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval( $filters["periode_start"] ) ) {
			$selectStatistiques->where("R.date>= ?", intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] ) &&  intval( $filters["periode_end"] ) ) {
			$selectStatistiques->where("R.date<= ?", intval( $filters["periode_end"] ) );
		}		
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}		
		$selectStatistiques->group(array("R.localiteid","FROM_UNIXTIME(R.date,'%Y')"))->order(array("R.localiteid ASC", "FROM_UNIXTIME(R.date,'%Y') ASC"));
		return $dbAdapter->fetchOne( $selectStatistiques);
	}
	
	public function getNbreByStatut($userid =0,$filters=array())
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes")        ,array("nombre"=>"count(R.demandeid)","R.statutid"))
		                                      ->join(array("S" => $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle"))
		                                      ->join(array("U" => $tablePrefix."system_users_account"),"U.userid=R.creatorid", null)
											  ->join(array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                      ->join(array("D" => $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											  ->join(array("E" => $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null)
											  ->group(array("R.statutid"));
		if( intval( $userid)) {
			$selectDemandes->where("R.creatorid=?", intval($userid));
		}	
        if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectDemandes->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval( $filters["periode_start"] ) ) {
			$selectDemandes->where("R.date>= ?", intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] ) &&  intval( $filters["periode_end"] ) ) {
			$selectDemandes->where("R.date<= ?", intval( $filters["periode_end"] ) );
		}		
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectDemandes->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectDemandes->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}		
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);									  
	}
	
	public function getNbreByLocalite($userid=0,$filters=array())
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("nombre"=>"count(R.localiteid)","R.localiteid"))
		                                      ->join(array("L"=> $tablePrefix."rccm_localites")        ,"L.localiteid=R.localiteid"  , array("localite"=>"L.libelle"))
											  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null)
											  ->group(array("R.localiteid","L.localiteid"));
		if( intval( $userid)) {
			$selectDemandes->where("R.creatorid=?", intval($userid));
		}		
        if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectDemandes->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval( $filters["periode_start"] ) ) {
			$selectDemandes->where("R.date>= ?", intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] ) &&  intval( $filters["periode_end"] ) ) {
			$selectDemandes->where("R.date<= ?", intval( $filters["periode_end"] ) );
		}		
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectDemandes->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectDemandes->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);									  
	}
	
	public function getNbreByYears($userid=0,$filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("nombre"=>"count(R.localiteid)","annee" => "FROM_UNIXTIME(R.date,'%Y')"))
		                                          ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
											      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                          ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											      ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null);
		if( intval( $userid)) {
			$selectStatistiques->where("R.creatorid=?", intval($userid));
		}	
        if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectStatistiques->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval( $filters["periode_start"] ) ) {
			$selectStatistiques->where("R.date>=?",intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] ) &&    intval( $filters["periode_end"] ) ) {
			$selectStatistiques->where("R.date<=?",intval( $filters["periode_end"] ) );
		}		
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}		
		$selectStatistiques->group(array("FROM_UNIXTIME(R.date,'%Y')"))->order(array("FROM_UNIXTIME(R.date,'%Y') DESC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatyears()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("count(R.localiteid)",""))
		                                          ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
											      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                          ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											      ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null);
		
		$selectStatistiques->group(array("FROM_UNIXTIME(R.date,'%Y')"))->order(array("FROM_UNIXTIME(R.date,'%Y') DESC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function reference($year=0)
	{
		if(!intval($year)) {
			$year        = date("Y");
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDemande   = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"), array("COUNT(D.demandeid)"))
		                                       ->where("FROM_UNIXTIME(D.date,'%Y')=?", $year);		
		$totalDemande    = $dbAdapter->fetchOne($selectDemande)+1;		
		$newCode         = sprintf("%05d/%d", $totalDemande, $year);
		while($existRow  = $this->findRow($newCode, "numero", null, false)) {
			  $totalDemande++;
			  $newCode   = sprintf("%05d/%d", $totalDemande, $year);
		}	
		return $newCode;
	}
	
	public function webreference($year=0)
	{
		if(!intval($year)) {
			$year        = date("Y");
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDemande   = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"), array("COUNT(D.demandeid)"))
		                                       ->where("FROM_UNIXTIME(D.date,'%Y')=?", $year);		
		$totalDemande    = $dbAdapter->fetchOne($selectDemande)+1;		
		$newCode         = sprintf("Web%05d/%d", $totalDemande, $year);
		while($existRow  = $this->findRow($newCode, "numero", null, false)) {
			  $totalDemande++;
			  $newCode   = sprintf("Web%05d/%d", $totalDemande, $year);
		}	
		return $newCode;
	}
	
	public function reservationkey($length = 8)
	{		
	    $table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		do {
			$keyString    = Sirah_Functions_Generator::getInteger($length);
			$stmt         = $dbAdapter->query("SELECT code FROM ".$tablePrefix."reservation_demandes_reservations where code=?", array($keyString));
		} while(false!=$stmt->fetch()) ;
		return $keyString ;
	}
	
	public function documents($demandeid=null)
	{
		if(!$demandeid )  {
			$demandeid   = $this->demandeid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                               ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","C.icon"))
				                               ->join(array("RD"=> $tablePrefix."reservation_demandes_documents"),"RD.documentid=D.documentid",array("RD.demandeid","RD.libelle"))
				                               ->where("RD.demandeid=?", $demandeid  );
		$selectDocuments->order(array("RD.demandeid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments,array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function entreprise($demandeid=null)
	{
		if(!$demandeid ) {
			$demandeid    = $this->demandeid;
		}
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$selectEntreprise = $dbAdapter->select()->from(array("E"=> $tablePrefix."reservation_demandes_entreprises"))
		                                        ->where("E.demandeid=?",intval($demandeid));
	    return $dbAdapter->fetchRow($selectEntreprise, array(),Zend_Db::FETCH_OBJ);
	}
	
	public function promoteur($promoteurid=0) 
	{
		if(!$promoteurid ) {
			$promoteurid  = $this->promoteurid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectPromoteur  = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_promoteurs"))
		                                        ->joinLeft(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->joinLeft(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"))
												->where("D.promoteurid=?", $promoteurid);
	
	    return $dbAdapter->fetchRow($selectPromoteur, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function demandeur($demandeurid=0) 
	{
		if(!$demandeurid ) {
			$demandeurid  = $this->demandeurid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectDemandeur  = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandeurs"))
		                                        ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"))
												->where("D.demandeurid=?", $demandeurid);
	
	    return $dbAdapter->fetchRow($selectDemandeur, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function identite($identityid=0) 
	{
		if(!$identityid) {
			$identityid   = $this->identityid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		$selectIdentite   = $dbAdapter->select()->from(    array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,array("numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->joinLeft(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid", array("typePiece"  =>"T.libelle"))
												->where("I.identityid=?", $identityid);
	
	    return $dbAdapter->fetchRow($selectIdentite, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function reservation($reservationid=0) 
	{
		if(!$reservationid) {
			$reservationid  = $this->demandeid;
		}
		$modelTable         = $this->getTable();
		$dbAdapter          = $modelTable->getAdapter();
		$tablePrefix        = $modelTable->info("namePrefix");		
		$tableName          = $modelTable->info("name");
		$selectReservation  = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes_reservations"))
												  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises" ),"E.demandeid=R.reservationid", array("E.formid","E.domaineid","E.numrccm","E.numifu","E.numcnss"))
												  ->where("R.reservationid=?", $reservationid);
	
	    return $dbAdapter->fetchRow($selectReservation, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function verification($verificationid=0) 
	{
		if(!$verificationid) {
			$verificationid = $this->demandeid;
		}
		$modelTable         = $this->getTable();
		$dbAdapter          = $modelTable->getAdapter();
		$tablePrefix        = $modelTable->info("namePrefix");		
		$tableName          = $modelTable->info("name");
		$selectVerification = $dbAdapter->select()->from(array("V"=> $tablePrefix."reservation_demandes_verifications"))
												  ->where("V.verificationid=?", $verificationid);
	
	    return $dbAdapter->fetchRow($selectVerification, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function verifications($demandeid=0) 
	{
		if(!$demandeid) {
			$demandeid      = $this->demandeid;
		}
		$modelTable         = $this->getTable();
		$dbAdapter          = $modelTable->getAdapter();
		$tablePrefix        = $modelTable->info("namePrefix");		
		$tableName          = $modelTable->info("name");
		$selectVerifications= $dbAdapter->select()->from(array("VS"=> $tablePrefix."reservation_demandes_verifications_sources"))
		                                          ->join(array("S" => $tablePrefix."reservation_verifications_sources"),"S.sourceid=VS.sourceid", array("S.code","S.libelle","S.uri","successPercent"=>"S.poids"))
												  ->where("VS.verificationid=?", $demandeid);
	
	    return $dbAdapter->fetchAll($selectVerifications, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function verified($demandeid = 0, $source= null) 
	{
		if(!$demandeid ) {
			$demandeid    = $this->demandeid;
		}
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		
		$selectSource     = $dbAdapter->select()->from(array("VS" => $tablePrefix."reservation_demandes_verifications_sources"), array("VS.verificationid","VS.sourceid","VS.poids","VS.failed"))
		                                        ->join(array("S"  => $tablePrefix."reservation_verifications_sources"),"S.sourceid=VS.sourceid", array("S.code", "S.libelle","S.uri", "successPercent"=>"S.poids"))
												->where("VS.verificationid=?", intval($demandeid));
		if(!empty($source))	{
			$selectSource->where(  "S.code=?", strip_tags($source))
			             ->orWhere("S.libelle LIKE ?", "%".strip_tags($source)."%");
		}		
        return $dbAdapter->fetchRow($selectSource, array(), 5)	;	
	}
		
	public function getList($filters=array(),$pageNum=0, $pageSize=0, $orders=array("R.expired DESC","R.date DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(    array("R"=> $tablePrefix."reservation_demandes"))
		                                      ->join(    array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(    array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("D.accountid","userid"=>"D.accountid","D.email","phone"=>"D.telephone","demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->join(    array("I"=> $tablePrefix."reservation_demandeurs_identite") ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
											  ->join(    array("T"=> $tablePrefix."reservation_demandes_types")      ,"T.typeid=R.typeid", array("typeDemande"=>"T.libelle"))
											  ->join(    array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",array("E.nomcommercial","E.sigle","E.denomination","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->joinLeft(array("S"=> $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle","statutLibelle"=>"S.libelle"))
											  ->joinLeft(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid"         , array("localite"=>"L.libelle"));
												
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeDemandeLibelle = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeDemandeurName  = new Zend_Db_Expr("MATCH(D.lastname,D.firstname,D.numidentite) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$likePromoteurName  = new Zend_Db_Expr("MATCH(P.lastname,P.firstname) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$selectDemandes->where("{$likeDemandeLibelle} OR {$likeDemandeurName} or {$likePromoteurName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		/*if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"]) && (null!==$filters["demandeurname"])){
			$filters["demandeurname"]    = str_replace('"', "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("'", "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("-", " ",$filters["demandeurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["demandeurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["demandeurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"]) && (null!==$filters["promoteurname"])){
			$filters["promoteurname"]    = str_replace('"', "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("'", "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("-", " ",$filters["promoteurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["promoteurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["promoteurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}*/
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectDemandes->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"])){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%")
			               ->orWhere("P.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])     && !empty($filters["email"])){
			$selectDemandes->where(  "D.email=?",$filters["email"])
			               ->orWhere("P.email=?",$filters["email"]);
		}
		 
		if( isset($filters["sexe"]) && !empty($filters["sexe"])){
			$selectDemandes->where("D.sexe=?",$filters["sexe"])->orWhere("P.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"])){
			$selectDemandes->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}
		if( isset($filters["numrccm"]) && !empty($filters["numrccm"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numrccm=".$filters["numrccm"]));
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numcnss=".$filters["numcnss"]));
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numifu=".$filters["numifu"]));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectDemandes->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectDemandes->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["typeid"]) && intval($filters["typeid"])  ) {
			$selectDemandes->where("R.typeid=?", intval( $filters["typeid"] ) );
		}
		if( isset($filters["typeids"]) && count($filters["typeids"])  && is_array($filters["typeids"])) {
			$selectDemandes->where("R.typeid IN (?)", array_map("intval",$filters["typeids"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectDemandes->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["accountid"]) && intval($filters["accountid"])  ) {
			$selectDemandes->where("D.accountid=?", intval( $filters["accountid"] ) );
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"] ) ) {
			$selectDemandes->where("R.date>= ?", intval($filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"])   && intval($filters["periode_end"] ) ) {
			$selectDemandes->where("R.date<= ?", intval($filters["periode_end"] ) );
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectDemandes->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["identityid"]) && intval($filters["identityid"])){
			$selectDemandes->where("D.identityid=?", intval($filters["identityid"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])  ) {
			$selectDemandes->where("R.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectDemandes->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"])){
			$selectDemandes->where("D.name LIKE ?","%".strip_tags($filters["demandeurname"])."%");
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"])){
			$selectDemandes->where("P.name LIKE ?","%".strip_tags($filters["promoteurname"])."%");
		}
		if( isset($filters["demandeurid"]) && intval($filters["demandeurid"])  ) {
			$selectDemandes->where("R.demandeurid = ?", intval( $filters["demandeurid"] ) );
		}
		if( isset( $filters["demandeurids"] ) && is_array( $filters["demandeurids"] )) {
			if( count( $filters["demandeurids"])) {
				$selectDemandes->where("R.demandeurid IN (?)", array_map("intval",$filters["demandeurids"]));
			}			
		}
		if( isset($filters["promoteurid"]) && intval($filters["promoteurid"])  ) {
			$selectDemandes->where("R.promoteurid = ?", intval( $filters["promoteurid"] ) );
		}
		if( isset( $filters["promoteurids"] ) && is_array( $filters["promoteurids"] )) {
			if( count( $filters["promoteurids"])) {
				$selectDemandes->where("R.promoteurid IN (?)", array_map("intval",$filters["promoteurids"]));
			}			
		}
		if( isset($filters["statutid"]) && intval($filters["statutid"])  ) {
			$selectDemandes->where("R.statutid = ?", intval( $filters["statutid"] ) );
		}
		if( isset( $filters["statutids"] ) && is_array( $filters["statutids"] )) {
			if( count( $filters["statutids"])) {
				$selectDemandes->where("R.statutid IN (?)", array_map("intval",$filters["statutids"]));
			}			
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectDemandes->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectDemandes->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset($filters["expired"]) && intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["disponible"]) && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible= ?", intval($filters["disponible"]));
		}
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.web= ?", intval($filters["web"]));
		}
		if( isset($filters["paid"]) && intval($filters["paid"])<=1  &&  intval($filters["paid"])>=0) {
			$selectDemandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.demandeid=R.demandeid",array("CL.commandeid","CL.productid"))
			               ->where("CL.commandeid IN (SELECT CO.commandeid FROM ".$tablePrefix."erccm_vente_commandes CO WHERE CO.validated=1)");
		}
		if( isset($filters["webrequests"])   && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=R.demandeid",array("RQ.processed","RQ.validated"));
		    $selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"O.demandeid=R.demandeid", null);
			$selectDemandes->group(array("R.demandeid","RQ.demandeid","O.demandeid"));
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
				if( intval($filters["processed"]) ==  0) {
					$filters["validated"] = 0;
					$selectDemandes->where("RQ.validated=0");
				}
			}
			if( isset($filters["validated"]) && intval($filters["validated"])<=1  &&  intval($filters["validated"])>=0) {
				$selectDemandes->where("RQ.validated=?", intval($filters["validated"]));
				if( intval($filters["validated"]) ==  0) {
					$selectDemandes->where("R.disponible=0");
				}
			}
			if( isset($filters["operatorid"]) && intval($filters["operatorid"])) {
				$selectDemandes->where("O.operatorid=?", intval($filters["operatorid"]));
			}
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectDemandes->limitPage($pageNum , $pageSize);
		}
		if(!empty($orders) && is_array($orders) ) {
			$selectDemandes->order($orders);
		} else {
			$selectDemandes->order(array("R.expired DESC","R.date DESC","D.lastname ASC","D.firstname ASC"));
		}
		//print_r($selectDemandes->__toString()); die();
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);										
	}

    public function getListPaginator($filters = array())
	{
		$modelTable       = $this->getTable();
		$dbAdapter        = $modelTable->getAdapter();
		$tablePrefix      = $modelTable->info("namePrefix");		
		$tableName        = $modelTable->info("name");
		
		$selectDemandes   = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes"), array("R.demandeid"))
		                                        ->join(array("P"=> $tablePrefix."reservation_promoteurs")          ,"P.promoteurid=R.promoteurid",null)
		                                        ->join(array("D"=> $tablePrefix."reservation_demandeurs")          ,"D.demandeurid=R.demandeurid",null)
												->join(array("I"=> $tablePrefix."reservation_demandeurs_identite") ,"I.identityid=D.identityid",null)
												->join(array("T"=> $tablePrefix."reservation_demandes_types")      ,"T.typeid=R.typeid",null)
												->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid",null)
												->join(array("S"=> $tablePrefix."reservation_demandes_statuts")    ,"S.statutid=R.statutid",null);
												
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeDemandeLibelle = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeDemandeurName  = new Zend_Db_Expr("MATCH(D.lastname,D.firstname,D.numidentite) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$likePromoteurName  = new Zend_Db_Expr("MATCH(P.lastname,P.firstname) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$selectDemandes->where("{$likeDemandeLibelle} OR {$likeDemandeurName} or {$likePromoteurName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) ){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		/*if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"]) && (null!==$filters["demandeurname"])){
			$filters["demandeurname"]    = str_replace('"', "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("'", "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("-", " ",$filters["demandeurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["demandeurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["demandeurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"]) && (null!==$filters["promoteurname"])){
			$filters["promoteurname"]    = str_replace('"', "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("'", "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("-", " ",$filters["promoteurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["promoteurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["promoteurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}*/
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectDemandes->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["numero"])      && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["lastname"])    && !empty($filters["lastname"]) ){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"])   && !empty($filters["firstname"]) ){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%")
			               ->orWhere("P.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])      && !empty($filters["email"]) ){
			$selectDemandes->where(  "D.email=?",$filters["email"])
			               ->orWhere("P.email=?",$filters["email"]);
		}
		if( isset($filters["country"])    && !empty($filters["country"]) ){
			$selectDemandes->where("D.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"])       && !empty($filters["sexe"]) ){
			$selectDemandes->where(  "D.sexe=?",$filters["sexe"])
			               ->orWhere("P.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"])  && !empty($filters["telephone"]) ){
			$selectDemandes->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}
		if( isset($filters["numrccm"])    && !empty($filters["numrccm"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numrccm=".$filters["numrccm"]));
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numcnss=".$filters["numcnss"]));
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numifu=".$filters["numifu"]));
		}
		if( isset($filters["accountid"]) && intval($filters["accountid"])  ) {
			$selectDemandes->where("D.accountid=?", intval( $filters["accountid"] ) );
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectDemandes->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectDemandes->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["typeid"]) && intval($filters["typeid"])  ) {
			$selectDemandes->where("R.typeid=?", intval( $filters["typeid"] ) );
		}
		if( isset($filters["typeids"]) && count($filters["typeids"])  && is_array($filters["typeids"])) {
			$selectDemandes->where("R.typeid IN (?)", array_map("intval",$filters["typeids"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectDemandes->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectDemandes->where("R.date>= ?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectDemandes->where("R.date<= ?", intval($filters["periode_end"]  ));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectDemandes->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["identityid"]) && intval($filters["identityid"])){
			$selectDemandes->where("D.identityid=?", intval($filters["identityid"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])  ) {
			$selectDemandes->where("R.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectDemandes->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"]) ){
			$selectDemandes->where("D.name LIKE ?","%".strip_tags($filters["demandeurname"])."%");
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"]) ){
			$selectDemandes->where("P.name LIKE ?","%".strip_tags($filters["promoteurname"])."%");
		}
		if( isset($filters["demandeurid"]) && intval($filters["demandeurid"])  ) {
			$selectDemandes->where("R.demandeurid = ?", intval( $filters["demandeurid"] ) );
		}
		if( isset( $filters["demandeurids"] ) && is_array( $filters["demandeurids"] )) {
			if( count( $filters["demandeurids"])) {
				$selectDemandes->where("R.demandeurid IN (?)", array_map("intval",$filters["demandeurids"]));
			}			
		}
		if( isset($filters["promoteurid"]) && intval($filters["promoteurid"])  ) {
			$selectDemandes->where("R.promoteurid = ?", intval( $filters["promoteurid"] ) );
		}
		if( isset( $filters["promoteurids"] ) && is_array( $filters["promoteurids"] )) {
			if( count( $filters["promoteurids"])) {
				$selectDemandes->where("R.promoteurid IN (?)", array_map("intval",$filters["promoteurids"]));
			}			
		}
		if( isset($filters["statutid"]) && intval($filters["statutid"])  ) {
			$selectDemandes->where("R.statutid = ?", intval( $filters["statutid"] ) );
		}
		if( isset( $filters["statutids"] ) && is_array( $filters["statutids"] )) {
			if( count( $filters["statutids"])) {
				$selectDemandes->where("R.statutid IN (?)", array_map("intval",$filters["statutids"]));
			}			
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectDemandes->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectDemandes->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset($filters["expired"]) && intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["disponible"]) && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible=?", intval($filters["disponible"]));
		}
		if( isset($filters["paid"]) && intval($filters["paid"])<=1  &&  intval($filters["paid"])>=0) {
			$selectDemandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.demandeid=R.demandeid",array("CL.commandeid","CL.productid"))
			               ->where("CL.commandeid IN (SELECT CO.commandeid FROM ".$tablePrefix."erccm_vente_commandes CO WHERE CO.validated=1)");
		}
		if( isset($filters["webrequests"]) && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=R.demandeid",array("RQ.processed","RQ.validated"));
		    $selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"O.demandeid=R.demandeid", null);
			$selectDemandes->group(array("R.demandeid","RQ.demandeid","O.demandeid"));
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
				if( intval($filters["processed"]) ==  0) {
					$filters["validated"] = 0;
					$selectDemandes->where("RQ.validated=0");
				}
			}
			if( isset($filters["validated"]) && intval($filters["validated"])<=1  &&  intval($filters["validated"])>=0) {
				$selectDemandes->where("RQ.validated=?", intval($filters["validated"]));
				if( intval($filters["validated"]) ==  0) {
					$selectDemandes->where("R.disponible=0");
				}
			}
			if( isset($filters["operatorid"]) && intval($filters["operatorid"])) {
				$selectDemandes->where("O.operatorid=?", intval($filters["operatorid"]));
			}
		}
		//print_r($selectDemandes->__toString()); die();
        $paginationAdapter = new Zend_Paginator_Adapter_DbSelect($selectDemandes);
		$rowCount          = intval(count($dbAdapter->fetchAll($selectDemandes)));
		$paginationAdapter->setRowCount($rowCount);
		$paginator = new Zend_Paginator($paginationAdapter);
		 
		return $paginator;		
	}
	
	
	
	
	
	
	public function basicList($filters = array() , $pageNum = 0 , $pageSize = 0, $orders=array("R.expired DESC","R.date DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(    array("R" => $tablePrefix."reservation_demandes"))
		                                      ->join(    array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(    array("D" => $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->joinLeft(array("S" => $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle"));
												
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ",$filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,\-\@]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeDemandeLibelle = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeDemandeurName  = new Zend_Db_Expr("MATCH(D.lastname,D.firstname,D.numidentite) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$likePromoteurName  = new Zend_Db_Expr("MATCH(P.lastname,P.firstname) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$selectDemandes->where("{$likeDemandeLibelle} OR {$likeDemandeurName} or {$likePromoteurName} ");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]    = str_replace('"', "", $filters["name"]);
			$filters["name"]    = str_replace("'", "", $filters["name"]);
			$filters["name"]    = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""))
				               ->orWhere(new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		/*if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"]) && (null!==$filters["demandeurname"])){
			$filters["demandeurname"]    = str_replace('"', "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("'", "", $filters["demandeurname"]);
			$filters["demandeurname"]    = str_replace("-", " ",$filters["demandeurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["demandeurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["demandeurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"]) && (null!==$filters["promoteurname"])){
			$filters["promoteurname"]    = str_replace('"', "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("'", "", $filters["promoteurname"]);
			$filters["promoteurname"]    = str_replace("-", " ",$filters["promoteurname"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["promoteurname"])));
			$searchAgainst      = "";
			$nom                = $prenom = "";
			if( isset($searchSpaceArray[0])) {
				$nom            = $searchSpaceArray[0];
				$prenom         = str_replace($nom,"", $filters["promoteurname"]);
			}
			if(!empty( $nom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if(!empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("P.firstname LIKE \"%".strip_tags($prenom)."%\""));
			}
		}*/
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectDemandes->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) && (null!==$filters["lastname"])){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%")
			               ->orWhere("P.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectDemandes->where(  "D.email=?",$filters["email"])
			               ->orWhere("P.email=?",$filters["email"]);
		}
		if( isset($filters["country"]) && !empty($filters["country"]) && (null!==$filters["country"])){
			$selectDemandes->where("D.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"]) && !empty($filters["sexe"]) && (null!==$filters["sexe"])){
			$selectDemandes->where(  "D.sexe=?",$filters["sexe"])
			               ->orWhere("P.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"]) && (null!==$filters["telephone"])){
			$selectDemandes->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}
		if( isset($filters["numrccm"])   && !empty($filters["numrccm"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numrccm=".$filters["numrccm"]));
		}
		if( isset($filters["numcnss"])   && !empty($filters["numcnss"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numcnss=".$filters["numcnss"]));
		}
		if( isset($filters["numifu"])    && !empty($filters["numifu"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numifu=".$filters["numifu"]));
		}
		if( isset($filters["domaineid"])  && intval($filters["domaineid"])  ) {
			$selectDemandes->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectDemandes->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["typeid"])     && intval($filters["typeid"])  ) {
			$selectDemandes->where("R.typeid=?", intval( $filters["typeid"] ) );
		}
		if( isset($filters["typeids"])    && count($filters["typeids"])  && is_array($filters["typeids"])) {
			$selectDemandes->where("R.typeid IN (?)", array_map("intval",$filters["typeids"]));
		}
		if( isset($filters["date"])       && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectDemandes->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["accountid"]) && intval($filters["accountid"])  ) {
			$selectDemandes->where("D.accountid=?", intval( $filters["accountid"] ) );
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"] ) && intval( $filters["periode_start"] ) ) {
			$selectDemandes->where("R.date>= ?", intval( $filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"] ) && intval( $filters["periode_end"] ) ) {
			$selectDemandes->where("R.date<= ?", intval( $filters["periode_end"] ) );
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectDemandes->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["identityid"]) && intval($filters["identityid"])){
			$selectDemandes->where("D.identityid=?", intval($filters["identityid"]));
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])  ) {
			$selectDemandes->where("R.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectDemandes->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset($filters["demandeurname"]) && !empty($filters["demandeurname"]) && (null!==$filters["demandeurname"])){
			$selectDemandes->where("D.name LIKE ?","%".strip_tags($filters["demandeurname"])."%");
		}
		if( isset($filters["promoteurname"]) && !empty($filters["promoteurname"]) && (null!==$filters["promoteurname"])){
			$selectDemandes->where("P.name LIKE ?","%".strip_tags($filters["promoteurname"])."%");
		}
		if( isset($filters["demandeurid"]) && intval($filters["demandeurid"])  ) {
			$selectDemandes->where("R.demandeurid = ?", intval( $filters["demandeurid"] ) );
		}
		if( isset( $filters["demandeurids"] ) && is_array( $filters["demandeurids"] )) {
			if( count( $filters["demandeurids"])) {
				$selectDemandes->where("R.demandeurid IN (?)", array_map("intval",$filters["demandeurids"]));
			}			
		}
		if( isset($filters["promoteurid"]) && intval($filters["promoteurid"])  ) {
			$selectDemandes->where("R.promoteurid = ?", intval( $filters["promoteurid"] ) );
		}
		if( isset( $filters["promoteurids"] ) && is_array( $filters["promoteurids"] )) {
			if( count( $filters["promoteurids"])) {
				$selectDemandes->where("R.promoteurid IN (?)", array_map("intval",$filters["promoteurids"]));
			}			
		}
		if( isset($filters["statutid"]) && intval($filters["statutid"])  ) {
			$selectDemandes->where("R.statutid = ?", intval( $filters["statutid"] ) );
		}
		if( isset( $filters["statutids"] ) && is_array( $filters["statutids"] )) {
			if( count( $filters["statutids"])) {
				$selectDemandes->where("R.statutid IN (?)", array_map("intval",$filters["statutids"]));
			}			
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectDemandes->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset( $filters["domaineids"] )  && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectDemandes->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset($filters["expired"])       &&  intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?",intval($filters["expired"] ) );
		}
		if( isset($filters["disponible"])    && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible=?", intval($filters["disponible"]));
		}
		if( isset($filters["webrequests"])   && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=R.demandeid",array("RQ.processed","RQ.validated"));
		    $selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"O.demandeid=R.demandeid", null);
			$selectDemandes->group(array("R.demandeid","RQ.demandeid","O.demandeid"));
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
			}
			if( isset($filters["validated"]) && intval($filters["validated"])<=1  &&  intval($filters["validated"])>=0) {
				$selectDemandes->where("RQ.validated=?", intval($filters["validated"]));
			}
			if( isset($filters["operatorid"]) && intval($filters["operatorid"])) {
				$selectDemandes->where("O.operatorid=?", intval($filters["operatorid"]));
			}
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectDemandes->limitPage($pageNum , $pageSize);
		}
		if(!empty($orders) && is_array($orders) ) {
			$selectDemandes->order($orders);
		} else {
			$selectDemandes->order(array("R.expired DESC","R.date DESC","D.lastname ASC","D.firstname ASC"));
		}
		//print_r($selectDemandes->__toString()); die();
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);										
	}
}

