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
	
	public function countretries($demandeid=0)
	{
		if(!$demandeid ) {
			$demandeid      = $this->demandeid;
		}		
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectCountRetry   = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes_retries"),array("total"=>new Zend_Db_Expr("COUNT(R.demandeid)")))
		                                          ->join(array("D"=> $tablePrefix."reservation_demandes"),"D.demandeid=R.demandeid"    ,null)
												  ->where("R.demandeid=?", intval($demandeid))
												  ->where("D.demandeid=?", intval($demandeid))
												  ->group(array("R.demandeid"));
        return $dbAdapter->fetchOne($selectCountRetry); 		
	}
	
	public function lastretry($demandeid=0, $return="array")
	{
		if(!$demandeid ) {
			$demandeid      = $this->demandeid;
		}		
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectRetry        = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes_retries"))
		                                          ->join(array("D"=> $tablePrefix."reservation_demandes")  ,"D.demandeid=R.demandeid"    ,array("D.statutid"))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid",array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                          ->join(array("M"=> $tablePrefix."reservation_demandeurs"),"M.demandeurid=D.demandeurid",array("M.accountid","userid"=>"M.accountid","M.email","phone"=>"M.telephone","demandeurName"=>"M.name","demandeurLastname"=>"M.lastname","demandeurFirstname"=>"M.firstname","demandeurIdentite"=>"M.numidentite","demandeurPhone"=>"M.telephone","demandeurEmail"=>"M.email"))
												  ->where("R.demandeid=?", intval($demandeid))
												  ->where("D.demandeid=?", intval($demandeid))
												  ->order(array("R.processed DESC","R.creationdate DESC"));
		if( $return == "array" ) {
			return $dbAdapter->fetchRow($selectRetry,array(),Zend_Db::FETCH_ASSOC);
		} 
        return $dbAdapter->fetchRow($selectRetry,array(),5); 		
	}
	
	public function retries($demandeid=0, $return="array")
	{
		if(!$demandeid ) {
			$demandeid      = $this->demandeid;
		}		
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectRetry        = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes_retries"))
		                                          ->join(array("D"=> $tablePrefix."reservation_demandes")  ,"D.demandeid=R.demandeid"    ,array("D.statutid"))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid",array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                          ->join(array("M"=> $tablePrefix."reservation_demandeurs"),"M.demandeurid=D.demandeurid",array("M.accountid","userid"=>"M.accountid","M.email","phone"=>"M.telephone","demandeurName"=>"M.name","demandeurLastname"=>"M.lastname","demandeurFirstname"=>"M.firstname","demandeurIdentite"=>"M.numidentite","demandeurPhone"=>"M.telephone","demandeurEmail"=>"M.email"))
												  ->where("R.demandeid=?", intval($demandeid))
												  ->where("D.demandeid=?", intval($demandeid))
												  ->order(array("R.processed DESC","R.creationdate DESC"));
		if( $return == "array" ) {
			return $dbAdapter->fetchAll($selectRetry,array(),Zend_Db::FETCH_ASSOC);
		} 
        return $dbAdapter->fetchAll($selectRetry,array(),5); 		
	}
	
	public function getWebRequests($operatorId=null,$processed=null,$validated=null,$statutId=null)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$selectRequests     = $dbAdapter->select()->from(array("D"  => $tablePrefix."reservation_demandes"))
		                                          ->join(array("AP" => $tablePrefix."api_reservation_demandes")     ,"AP.sync_demandeid=D.demandeid",null)
		                                          ->join(array("R"  => $tablePrefix."reservation_demandes_requests"),"R.demandeid=AP.demandeid",array("R.processed","R.validated"))
												  ->join(array("O"  => $tablePrefix."reservation_demandes_requests_operators"),"O.requestid=R.requestid",array("O.operatorid"))
												  ->join(array("P"  => $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid",null)
		                                          ->join(array("M"  => $tablePrefix."reservation_demandeurs"),"M.demandeurid=D.demandeurid",null)
												  /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"(E.demandeid=D.demandeid) AND (E.entrepriseid=D.entrepriseid OR E.nomcommercial=D.denomination)",null)										  
												    
												  ->where("D.WEB=1")*/ 
												  ->group(array("D.localiteid","D.demandeurid","D.promoteurid","D.demandeid"));
		if( null!== $operatorId){
			$selectRequests->where("O.operatorid=?", intval($operatorId));
		}
        if( null!== $processed) {
			$selectRequests->where("R.processed=?" , intval($processed));
			if( intval($processed) ==  0) {
				//$validated = 0;
				$selectRequests->where("D.expired=0");
				$selectRequests->where("D.statutid IN (1,7,8)");
				//$selectRequests->where("R.validated=0");
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
		
		$selectRequests     = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes_requests"),array("R.processed","R.validated"))
		                                          ->join(array("AP"=> $tablePrefix."api_reservation_demandes"),"AP.demandeid=R.demandeid",null)
												  ->join(array("D" => $tablePrefix."reservation_demandes")    ,"D.demandeid=AP.sync_demandeid",array("total"=>new Zend_Db_Expr("COUNT(DISTINCT D.demandeid)"),"D.statutid"))
												  ->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"O.requestid=R.requestid",array("O.operatorid")) 
												  ->join(array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=D.promoteurid",null)
		                                          ->join(array("M" => $tablePrefix."reservation_demandeurs"),"M.demandeurid=D.demandeurid",null) 
												  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"(E.demandeid=D.demandeid) AND (E.entrepriseid=D.entrepriseid OR E.denomination=D.objet)",null)
												   
												  ->where("D.web=1")
												  ->group(array("O.operatorid"));
		if( null!== $operatorId) {
			$selectRequests->where("O.operatorid=?", intval($operatorId));
		}
        if( null!== $processed) {
			$selectRequests->where("R.processed=?" , intval($processed));
			if( intval($processed) ==  0) {
				$selectRequests->where("D.statutid IN (1,7,8)")
							   ->where("D.expired=0");
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
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes" ), array("annee" => new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))
												  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
												  ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
												  /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.nomcommercial=R.denomination)",null)*/;
		$selectStatistiques->group(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))
		                   ->order(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y') DESC")))
						   ->limitPage(1,28);
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getTotal($userid=0,$filters=array())
	{
        $table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");		
		$selectTotal = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes" ), array("total" =>new Zend_Db_Expr("COUNT(R.demandeid)")))
		                                   ->join(array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                   ->join(array("D" => $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
										   /*->join(array("E" => $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.nomcommercial=R.denomination)",null)*/;
		
		if( isset($filters["date"]) && !empty($filters["date"]) && (null!==$filters["date"])){
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectTotal->where(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y-%m-%d')=?"), $filters["date"]);
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
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectTotal->where("R.WEB= ?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) && ($filters["personne_physique"]!=="null") && intval($filters["personne_physique"])==1 ) {
			$selectTotal->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectTotal->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		return $dbAdapter->fetchOne($selectTotal);
	}
	
	public function getNbreByUsers( $userid = 0, $role = null, $period_start = 0, $period_end = 0,$typeid=0,$web=4)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."reservation_demandes"), array("nombre"=>new Zend_Db_Expr("COUNT(DISTINCT(R.demandeid))"),"annee"=>new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')"),"R.localiteid"))		                                         
												  ->join(array("L" => $tablePrefix."rccm_localites"      ), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
		                                          ->join(array("U" => $tablePrefix."system_users_account"), "U.userid = R.creatorid", array("U.lastname","U.firstname","U.username","U.phone1","U.phone2","U.activated","U.userid"));
												  
		if( intval($userid )) {	
			$selectStatistiques->where("R.creatorid=?", intval($userid));
		}	
        if( intval($typeid )) {	
			$selectStatistiques->where("R.typeid=?", intval($typeid));
		}		
		if( intval($period_end)){
			$selectStatistiques->where("R.date <= ?",intval($period_end));
		}
		if( intval($period_start )  ){
			$selectStatistiques->where("R.date >= ?",intval($period_start));
		}		 
		if( isset($web) && ($web!=="null") && intval($web)<=1  &&  intval($web)>=0) {
			$selectStatistiques->where("R.WEB= ?", intval($web));
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
												  ->where(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')= ?"), intval( $annee));
		if( intval($creatorid) ) {
			$selectStatistiques->where("R.creatorid=?", intval($creatorid));
		}	
        if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectStatistiques->where(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y-%m-%d')=?"), $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectStatistiques->where(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')=?"),intval( $filters["annee"]));
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
        if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectStatistiques->where("R.WEB= ?", intval($filters["web"]));
		}
        if( isset($filters["personne_physique"]) && (intval($filters["personne_physique"])==1)) {
			$selectStatistiques->where("R.personne_morale=0" );
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectStatistiques->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}		
		$selectStatistiques->group(array("R.localiteid",new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))->order(array("R.localiteid ASC", "FROM_UNIXTIME(R.date,'%Y') ASC"));
		return $dbAdapter->fetchOne( $selectStatistiques);
	}
	
	public function getNbreByStatut($userid =0,$filters=array())
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")        ,array("nombre"=>new Zend_Db_Expr("count(DISTINCT R.demandeid)"),"R.statutid"))
		                                      ->join(array("S"=> $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle"))
		                                       
											  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											  /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.nomcommercial=R.denomination)",null)*/
											  ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid" ,null)											  
											  ->group(array("R.statutid"));
		if( intval( $userid)) {
			$selectDemandes->where("R.creatorid=?", intval($userid));
		}	
        if( isset($filters["date"]) && !empty($filters["date"])  ) {
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
        if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.WEB= ?", intval($filters["web"]));
		}	
        if( isset($filters["personne_physique"]) &&  (intval($filters["personne_physique"])==1)) {
			$selectDemandes->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectDemandes->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}		
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);									  
	}
	
	public function getNbreByLocalite($userid=0,$filters=array())
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("nombre"=>new Zend_Db_Expr("COUNT(DISTINCT R.demandeid)"),"R.localiteid"))
		                                      ->join(array("L"=> $tablePrefix."rccm_localites")        ,"L.localiteid=R.localiteid"  , array("localite"=>"L.libelle"))
											  ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											  /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND E.entrepriseid=R.entrepriseid AND (E.entrepriseid=R.entrepriseid OR E.nomcommercial=R.denomination)",null)*/
											  ->group(array("R.localiteid","L.localiteid"));
		if( intval( $userid)) {
			$selectDemandes->where("R.creatorid=?", intval($userid));
		}		
        if( isset($filters["date"]) && !empty($filters["date"]) ) {
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
		if( isset($filters["web"])  && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.WEB= ?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) && (intval($filters["personne_physique"])==1)) {
			$selectDemandes->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectDemandes->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);									  
	}
	
	public function getNbreByYears($userid=0,$filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("nombre"=>new Zend_Db_Expr("COUNT(DISTINCT R.demandeid)"),"annee" =>new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))
		                                          ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
											      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                          ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											      /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.denomination=R.objet)",null)*/;
		if( intval( $userid)) {
			$selectStatistiques->where("R.creatorid=?", intval($userid));
		}	
        if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectStatistiques->where(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')=?"),intval( $filters["annee"]));
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
				//$selectStatistiques->where("E.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}		
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectStatistiques->where("R.WEB= ?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) &&  (intval($filters["personne_physique"])==1)) {
			$selectStatistiques->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectStatistiques->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		$selectStatistiques->group(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))->order(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y') DESC")));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatyears()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes")  ,array("COUNT(DISTINCT R.demandeid)"))
		                                          ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
											      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", null)
		                                          ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", null)
											      /*->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.nomcommercial=R.denomination)",null)*/;
		
		$selectStatistiques->group(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y')")))->order(array(new Zend_Db_Expr("FROM_UNIXTIME(R.date,'%Y') DESC")));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function lastId( )
	{
		 
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		$selectLastDemande  = $dbAdapter->select()->from( array("R"=>$tablePrefix."reservation_demandes"),array(new Zend_Db_Expr("MAX(R.demandeid)")))
												  ->order(array("R.demandeid DESC"))
												  ->limitPage(1,1);
		$lastDbDemandeId    = $dbAdapter->fetchOne($selectLastDemande);
		if( $lastDbDemandeId ) {
			$demandeid      = $lastDbDemandeId + 100;
		}
		return $demandeid;
	}
	
	public function reference($year=0)
	{
		if(!intval($year)) {
			$year        = date("Y");
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDemande   = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"), array(new Zend_Db_Expr("MAX(D.demandeid)")))
		                                       ->where(new Zend_Db_Expr("FROM_UNIXTIME(D.date,'%Y')=?"), $year)
											   ->order(array("D.demandeid DESC"))
											   ->limitPage(1,1);;		
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
		$selectDemande   = $dbAdapter->select()->from(array("D"=> $tablePrefix."reservation_demandes"), array(new Zend_Db_Expr("MAX(D.demandeid)")))
		                                       ->where(new Zend_Db_Expr("FROM_UNIXTIME(D.date,'%Y')=?"), $year)
											   ->order(array("D.demandeid DESC"))
											   ->limitPage(1,1);		
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
		$selectDocuments = $dbAdapter->select()->from(     array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid","D.userid"))
				                               ->joinLeft( array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","C.icon"))
				                               ->join(     array("RD"=> $tablePrefix."reservation_demandes_documents"),"RD.documentid=D.documentid",array("RD.demandeid","RD.libelle"))
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
		                                        ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite")      ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
												->join(array("T"=> $tablePrefix."reservation_demandeurs_identite_types"),"T.typeid=I.typeid"        , array("typePiece"  =>"T.libelle"))
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
	
	
	public function __getList($filters=array(),$pageNum=0, $pageSize=0, $orders=array("R.date DESC","R.creationdate DESC","R.demandeid DESC","R.web DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes"))
		                                      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("D.accountid","userid"=>"D.accountid","D.email","phone"=>"D.telephone","demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"E.demandeid=R.demandeid AND (E.entrepriseid=R.entrepriseid OR E.denomination=R.denomination)",array("E.nomcommercial","E.sigle","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->join(array("S"=> $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle","statutLibelle"=>"S.libelle"))
											  ->group(array("R.localiteid","R.demandeurid","R.promoteurid","R.demandeid"));
												
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
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
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
		if( isset($filters["objet"]) && !empty($filters["objet"]) ){
			$selectDemandes->where("R.objet LIKE ?","%".strip_tags($filters["objet"])."%");
		}
		if( isset($filters["denomination"]) && !empty($filters["denomination"]) ){
			$selectDemandes->where("R.denomination LIKE ?","%".strip_tags($filters["denomination"])."%");
		}
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$selectDemandes->where("R.keywords LIKE ?","%".strip_tags($filters["keywords"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectDemandes->where("R.reference=?", strip_tags($filters["reference"]) );
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"])){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])     && !empty($filters["email"])){
			$selectDemandes->where(  "D.email=?",$filters["email"]);
		}
		 
		if( isset($filters["sexe"]) && !empty($filters["sexe"])){
			$selectDemandes->where("D.sexe=?",$filters["sexe"]);
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
		/*if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		*/
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
		if( isset($filters["demandeid"]) && intval($filters["demandeid"])  ) {
			$selectDemandes->where("R.demandeid = ?", intval( $filters["demandeid"] ) );
		}
		if( isset( $filters["demandeids"] ) && is_array( $filters["demandeids"] )) {
			if( count( $filters["demandeids"])) {
				$selectDemandes->where("R.demandeid IN (?)", array_map("intval",$filters["demandeids"]));
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
		if( isset($filters["retried"]) && intval($filters["retried"]) ) {
			$selectDemandes->join(array("DR"=>$tablePrefix."reservation_demandes_retries"),"DR.demandeid=R.demandeid", array("DR.ancien_nom","DR.ancien_denomination","DR.ancien_sigle","DR.nouveau_nom","DR.nouveau_denomination","DR.nouveau_sigle","DR.nbreEssais",
			                                                                                                                 "retry_processed"=>"DR.processed","retry_validated"=>"DR.validated","retry_date"=>"DR.date","retry_creationdate"=>"DR.creationdate","retry_creatorid"=>"DR.creatorid"))
			               ->where("R.retries>=?", intval($filters["retried"]));
		}
		if( isset($filters["expired"]) && intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["disponible"]) && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible= ?", intval($filters["disponible"]));
		}
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.WEB= ?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) && (intval($filters["personne_physique"])==1)) {
			$selectDemandes->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectDemandes->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		if( isset($filters["paid"]) && intval($filters["paid"])<=1  &&  intval($filters["paid"])>=0) {
			$selectDemandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.demandeid=R.demandeid",array("CL.commandeid","CL.productid"))
			               ->where("CL.commandeid IN (SELECT CO.commandeid FROM ".$tablePrefix."erccm_vente_commandes CO WHERE CO.validated=1)");
		}
		if( isset($filters["webrequests"])   && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=R.demandeid",array("RQ.processed","RQ.validated"));
		    $selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"O.demandeid=R.demandeid", null);
			//$selectDemandes->group(array("R.demandeid","RQ.demandeid"));
			$selectDemandes->where("R.WEB=1");
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
				if( intval($filters["processed"]) ==  0) {
					$filters["validated"] = 0;
					$filters["expired"]   = 0;
					$selectDemandes->where(new Zend_Db_Expr("R.statutid NOT IN (2,3,4,5,6)"));
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
			$selectDemandes->order(array("R.creationdate DESC","R.demandeid DES","D.lastname ASC","D.firstname ASC"));
		}
		$selectDemandes->group(array("R.localiteid","R.demandeurid","R.promoteurid","R.demandeid","E.demandeid"));
		//print_r($selectDemandes->__toString()); die();
		return $dbAdapter->fetchAll($selectDemandes, array(), Zend_Db::FETCH_ASSOC);										
	}
	
	
		
	public function getList($filters=array(),$pageNum=0, $pageSize=0, $orders=array("R.date DESC","R.creationdate DESC","R.demandeid DESC","R.web DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes"))
		                                      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("D.accountid","userid"=>"D.accountid","D.email","phone"=>"D.telephone","demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite") ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
											  ->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"(E.demandeid=R.demandeid) AND ((E.nomcommercial=R.objet) OR (E.denomination=R.denomination) OR (E.entrepriseid=R.entrepriseid))",array("E.nomcommercial","E.sigle","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->joinLeft(array("S"=> $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle","statutLibelle"=>"S.libelle"))
											  ->join(array("L"=> $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid"         , array("localite"=>"L.libelle"))
											  ->group(array("R.localiteid","R.demandeurid","R.promoteurid","R.demandeid"));
												
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			if( isset($filters["webrequests"])) {
				unset($filters["webrequests"]);
			}
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
			if( isset($filters["webrequests"])) {
				unset($filters["webrequests"]);
			}
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
		if( isset($filters["objet"]) && !empty($filters["objet"]) ){
			$selectDemandes->where("R.objet LIKE ?","%".strip_tags($filters["objet"])."%");
		}
		if( isset($filters["denomination"]) && !empty($filters["denomination"]) ){
			$selectDemandes->where("R.denomination LIKE ?","%".strip_tags($filters["denomination"])."%");
		}
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$selectDemandes->where("R.keywords LIKE ?","%".strip_tags($filters["keywords"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["reference"]) && !empty($filters["reference"])){
			$selectDemandes->where("R.reference=?", strip_tags($filters["reference"]) );
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"])){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])     && !empty($filters["email"])){
			$selectDemandes->where(  "D.email=?",$filters["email"]);
		}
		 
		if( isset($filters["sexe"]) && !empty($filters["sexe"])){
			$selectDemandes->where("D.sexe=?",$filters["sexe"]);
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
		/*if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		*/
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"] ) ) {
			//$selectDemandes->where("R.date>= ?", intval($filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"])   && intval($filters["periode_end"] ) ) {
			//$selectDemandes->where("R.date<= ?", intval($filters["periode_end"] ) );
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
		if( isset($filters["sync_demandeid"]) && intval($filters["sync_demandeid"])  ) {
			$selectDemandes->where("R.sync_demandeid= ?", intval( $filters["sync_demandeid"] ) );
		}
		if( isset($filters["demandeid"]) && intval($filters["demandeid"])  ) {
			$selectDemandes->where("R.demandeid = ?", intval( $filters["demandeid"] ) );
		}
		if( isset( $filters["demandeids"] ) && is_array( $filters["demandeids"] )) {
			if( count( $filters["demandeids"])) {
				$selectDemandes->where("R.demandeid IN (?)", array_map("intval",$filters["demandeids"]));
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
		if( isset($filters["retried"]) && intval($filters["retried"]) ) {
			$selectDemandes->join(array("DR"=>$tablePrefix."reservation_demandes_retries"),"DR.demandeid=R.demandeid", array("DR.ancien_nom","DR.ancien_denomination","DR.ancien_sigle","DR.nouveau_nom","DR.nouveau_denomination","DR.nouveau_sigle","DR.nbreEssais",
			                                                                                                                 "retry_processed"=>"DR.processed","retry_validated"=>"DR.validated","retry_date"=>"DR.date","retry_creationdate"=>"DR.creationdate","retry_creatorid"=>"DR.creatorid"))
			               ->where("R.retries>=?", intval($filters["retried"]));
		}
		if( isset($filters["expired"]) && intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["disponible"]) && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible= ?", intval($filters["disponible"]));
		}
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.WEB= ?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) && (intval($filters["personne_physique"])==1)) {
			$selectDemandes->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectDemandes->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		if( isset($filters["paid"]) && intval($filters["paid"])<=1  &&  intval($filters["paid"])>=0) {
			$selectDemandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.demandeid=R.demandeid",array("CL.commandeid","CL.productid"))
			               ->where("CL.commandeid IN (SELECT CO.commandeid FROM ".$tablePrefix."erccm_vente_commandes CO WHERE CO.validated=1)");
		}
		
		if( intval($pageNum) && intval($pageSize)) {
			$selectDemandes->limitPage($pageNum , $pageSize);
		}
		if(!empty($orders) && is_array($orders) ) {
			$selectDemandes->order($orders);
		} else {
			$selectDemandes->order(array("R.creationdate DESC","R.demandeid DES","D.lastname ASC","D.firstname ASC"));
		}
		if( isset($filters["webrequests"])   && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("AP"=> $tablePrefix."api_reservation_demandes")    ,"(AP.sync_demandeid=R.demandeid) OR (AP.numero=R.reference)",array("remote_demandeid"=>"AP.demandeid","remote_numero"=>"AP.numero","remote_reference"=>"AP.reference","local_demandeid"=>"AP.sync_demandeid","remote_demandeurid"=>"AP.demandeurid","remote_promoteurid"=>"AP.promoteurid"));			
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=AP.demandeid",array("RQ.processed","RQ.validated"));		    								  
			$selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"(O.requestid=RQ.requestid) OR (O.demandeid=RQ.demandeid)",null);
			//$selectDemandes->group(array("R.demandeid","AP.sync_demandeid"));
			$selectDemandes->where("R.web=1");
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
				if( intval($filters["processed"])== 0) {
					//$filters["validated"] = 0;
					//$filters["expired"]   = 0;
					$selectDemandes->where(new Zend_Db_Expr("R.statutid IN (1,7,8)"));
				}
			}
			if( isset($filters["validated"]) && intval($filters["validated"])<=1  &&  intval($filters["validated"])>=0) {
				$selectDemandes->where("RQ.validated=?", intval($filters["validated"]));
				if( intval($filters["validated"]) ==  0) {
					//$selectDemandes->where("R.disponible=0");
				}
			}
			if( isset($filters["operatorid"]) && intval($filters["operatorid"])) {
				$selectDemandes->where("O.operatorid=?", intval($filters["operatorid"]));
			}
			//print_r($selectDemandes->__toString()); die();
		}
		$selectDemandes->group(array("R.localiteid","R.demandeurid","R.demandeid","E.demandeid"));
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
												->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid",null)
		                                        ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid",null)
											    ->join(array("I"=> $tablePrefix."reservation_demandeurs_identite") ,"I.identityid=D.identityid",null)
												->join(array("E"=> $tablePrefix."reservation_demandes_entreprises"),"(E.demandeid=R.demandeid) AND ((E.nomcommercial=R.denomination) OR (E.denomination=R.denomination) OR (E.entrepriseid=R.entrepriseid))",null)
												->group(array("R.localiteid","R.demandeurid","R.promoteurid","R.demandeid"));
												
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ){
			if( isset($filters["webrequests"])) {
				unset($filters["webrequests"]);
			}
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
			if( isset($filters["webrequests"])) {
				unset($filters["webrequests"]);
			}
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
				$selectDemandes->where(  new Zend_Db_Expr("D.lastname LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectDemandes->where(  new Zend_Db_Expr("D.firstname LIKE \"%".strip_tags($prenom)."%\""));
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
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"])   && !empty($filters["firstname"]) ){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])      && !empty($filters["email"]) ){
			$selectDemandes->where(  "D.email=?",$filters["email"]);
		}
		if( isset($filters["country"])    && !empty($filters["country"]) ){
			$selectDemandes->where("D.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"])       && !empty($filters["sexe"]) ){
			$selectDemandes->where(  "D.sexe=?",$filters["sexe"]);
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
		/*if( isset( $filters["annee"] ) && intval( $filters["annee"] ) ) {
			$selectDemandes->where("FROM_UNIXTIME(R.date,'%Y')=?",intval( $filters["annee"]));
		}
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"])) {
			$selectDemandes->where("R.date>= ?", intval($filters["periode_start"]));
		}
	    if( isset( $filters["periode_end"] )  && intval($filters["periode_end"]  )) {
			$selectDemandes->where("R.date<= ?", intval($filters["periode_end"]  ));
		}
		*/
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"] ) ) {
			//$selectDemandes->where("R.date>= ?", intval($filters["periode_start"] ) );
		}
	    if( isset( $filters["periode_end"])   && intval($filters["periode_end"] ) ) {
			//$selectDemandes->where("R.date<= ?", intval($filters["periode_end"] ) );
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
		if( isset($filters["sync_demandeid"]) && intval($filters["sync_demandeid"])  ) {
			$selectDemandes->where("R.sync_demandeid= ?", intval( $filters["sync_demandeid"] ) );
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
		if( isset($filters["retried"]) && intval($filters["retried"]) ) {
			$selectDemandes->where("R.retries>=?", intval( $filters["retried"] ) );
		}
		if( isset($filters["expired"]) && intval($filters["expired"])<=1  &&  intval($filters["expired"])>=0) {
			$selectDemandes->where("R.expired=?", intval( $filters["expired"] ) );
		}
		if( isset($filters["disponible"]) && ($filters["disponible"]!=="null") && intval($filters["disponible"])<=1  &&  intval($filters["disponible"])>=0) {
			$selectDemandes->where("R.disponible=?", intval($filters["disponible"]));
		}
		if( isset($filters["web"]) && ($filters["web"]!=="null") && intval($filters["web"])<=1  &&  intval($filters["web"])>=0) {
			$selectDemandes->where("R.web=?", intval($filters["web"]));
		}
		if( isset($filters["personne_physique"]) && (intval($filters["personne_physique"])==1)) {
			$selectDemandes->where("R.personne_morale=0");
		}
		if( isset($filters["personne_morale"]) && ($filters["personne_morale"]!=="null") && intval($filters["personne_morale"])<=1  &&  intval($filters["personne_morale"])>=0) {
			$selectDemandes->where("R.personne_morale= ?", intval($filters["personne_morale"]));
		}
		if( isset($filters["paid"]) && intval($filters["paid"])<=1  &&  intval($filters["paid"])>=0) {
			$selectDemandes->join(array("CL"=>$tablePrefix."erccm_vente_commandes_ligne"),"CL.demandeid=R.demandeid",array("CL.commandeid","CL.productid"))
			               ->where("CL.commandeid IN (SELECT CO.commandeid FROM ".$tablePrefix."erccm_vente_commandes CO WHERE CO.validated=1)");
		}
		if( isset($filters["webrequests"])   && ($filters["webrequests"]!=="null") && intval($filters["webrequests"])<=1 && intval($filters["webrequests"])>=0) {
			$selectDemandes->join(array("AP"=> $tablePrefix."api_reservation_demandes")    ,"(AP.sync_demandeid=R.demandeid) OR (AP.numero=R.reference)",array("remote_demandeid"=>"AP.demandeid","remote_numero"=>"AP.numero","remote_reference"=>"AP.reference","local_demandeid"=>"AP.sync_demandeid","remote_demandeurid"=>"AP.demandeurid","remote_promoteurid"=>"AP.promoteurid"));			
			$selectDemandes->join(array("RQ"=> $tablePrefix."reservation_demandes_requests"),"RQ.demandeid=AP.demandeid",array("RQ.processed","RQ.validated"));		    								  
			$selectDemandes->join(array("O" => $tablePrefix."reservation_demandes_requests_operators"),"(O.requestid=RQ.requestid) OR (O.demandeid=RQ.demandeid)",null);
			//$selectDemandes->group(array("R.demandeid","AP.sync_demandeid"));
			$selectDemandes->where("R.web=1");
			if( isset($filters["processed"]) && intval($filters["processed"])<=1  &&  intval($filters["processed"])>=0) {
				$selectDemandes->where("RQ.processed=?", intval($filters["processed"]));
				if( intval($filters["processed"])== 0) {
					//$filters["validated"] = 0;
					//$filters["expired"]   = 0;
					$selectDemandes->where(new Zend_Db_Expr("R.statutid IN (1,7,8)"));
				}
			}
			if( isset($filters["validated"]) && intval($filters["validated"])<=1  &&  intval($filters["validated"])>=0) {
				$selectDemandes->where("RQ.validated=?", intval($filters["validated"]));
				if( intval($filters["validated"]) ==  0) {
					//$selectDemandes->where("R.disponible=0");
				}
			}
			if( isset($filters["operatorid"]) && intval($filters["operatorid"])) {
				$selectDemandes->where("O.operatorid=?", intval($filters["operatorid"]));
			}
			//print_r($selectDemandes->__toString()); die();
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
		$selectDemandes = $dbAdapter->select()->from(array("R"=> $tablePrefix."reservation_demandes"))
		                                      ->join(array("P"=> $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(array("D"=> $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->join(array("S"=> $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle"));
												
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
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["lastname"]) && !empty($filters["lastname"]) ){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"]) && !empty($filters["firstname"]) && (null!==$filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%")
			               ->orWhere("P.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"]) && !empty($filters["email"]) ){
			$selectDemandes->where(  "D.email=?",$filters["email"])
			               ->orWhere("P.email=?",$filters["email"]);
		}
		 
		if( isset($filters["sexe"]) && !empty($filters["sexe"]) && (null!==$filters["sexe"])){
			$selectDemandes->where(  "D.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
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
		if( isset($filters["retried"]) && intval($filters["retried"]) ) {
			$selectDemandes->join(array("DR"=>$tablePrefix."reservation_demandes_retries"),"DR.demandeid=R.demandeid", array("DR.ancien_nom","DR.ancien_denomination","DR.ancien_sigle","DR.nouveau_nom","DR.nouveau_denomination","DR.nouveau_sigle","DR.nbreEssais",
			                                                                                                                 "retry_processed"=>"DR.processed","retry_validated"=>"DR.validated","retry_date"=>"DR.date","retry_creationdate"=>"DR.creationdate","retry_creatorid"=>"DR.creatorid"))
			               ->where("R.retries>=?", intval($filters["retried"]));
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

