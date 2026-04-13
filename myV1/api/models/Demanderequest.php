<?php

class Model_Demanderequest extends Sirah_Model_Default
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
	
	
	public function demanderequest($demandeid,$userid=0)
	{
		if(!intval($userid)) {
			$me         = Sirah_Fabric::getUser();
			$userid     = $me->userid;
		}
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
 
		$selectOperator = $dbAdapter->select()->from(array("R"=> $tableName))
		                                      ->join(array("O"=> $tablePrefix."reservation_demandes_requests_operators"),"O.requestid=R.requestid", array("O.name","O.email","O.sent","O.senddate"))
											  ->where("R.demandeid=?" ,intval($demandeid))
											  ->where("O.operatorid=?",intval($userid   ));
		return $dbAdapter->fetchRow($selectOperator,null,5);									  
	}
	
	
	public function operator($userid,$requestid=0)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		
		if(!intval($requestid)) {
			$requestid  = $this->requestid;
		}
		$selectOperator = $dbAdapter->select()->from(array("R"=> $tableName))
		                                      ->join(array("O"=> $tablePrefix."reservation_demandes_requests_operators"),"O.requestid=R.requestid", array("O.name","O.email","O.sent","O.senddate"))
											  ->where("O.requestid=?" ,intval($requestid))
											  ->where("O.operatorid=?",intval($userid   ));
		return $dbAdapter->fetchRow($selectOperator,null,5);									  
	}
	
	public function requestOperator($requestid)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		
		if(!intval($requestid)) {
			$requestid  = $this->requestid;
		}
		$selectOperator = $dbAdapter->select()->from(array("R"=> $tableName))
		                                      ->join(array("O"=> $tablePrefix."reservation_demandes_requests_operators"),"O.requestid=R.requestid", array("O.name","O.email","O.sent","O.senddate"))
											  ->where("O.requestid=?" ,intval($requestid));
		return $dbAdapter->fetchRow($selectOperator,null,5);									  
	}
	
	
	public function nonProcessedRequests($periodStart=0,$creatorid=0)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		$selectRequests = $dbAdapter->select()->from(    array("RQ"=> $tableName), array("RQ.requestid","RQ.processed" ,"RQ.validated","RQ.notified","RQ.operatorid","RQ.updatedate","RQ.creationdate"))
		                                      ->join(    array("R" => $tablePrefix."reservation_demandes")             ,"R.demandeid=RQ.demandeid"     , "R.*")
											  ->join(    array("RD"=> $tablePrefix."reservation_demandeurs")           ,"RD.demandeurid=R.demandeurid" , array("RD.accountid","demandeurName"=>"RD.name","demandeurLastname"=>"RD.lastname","demandeurFirstname"=>"RD.firstname","demandeurIdentite"=>"RD.numidentite","demandeurPhone"=>"RD.telephone","demandeurEmail"=>"RD.email"))
											  ->join(    array("P" => $tablePrefix."reservation_promoteurs")           ,"P.promoteurid=R.promoteurid"  , array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
											  ->join(    array("E" => $tablePrefix."reservation_demandes_entreprises") ,"E.entrepriseid=R.entrepriseid", array("E.nomcommercial","E.sigle","E.denomination","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->joinLeft(array("S" => $tablePrefix."reservation_demandes_statuts")     ,"S.statutid=R.statutid"        , array("statut"       =>"S.libelle"))
											  ->joinLeft(array("DC"=> $tablePrefix."reservation_demandes_documents")   ,"DC.demandeid=R.demandeid"     , array("documentitle" =>"DC.libelle"))
											  ->joinLeft(array("D" => $tablePrefix."system_users_documents")           ,"D.documentid=DC.documentid"   , array("D.documentid","D.userid","D.filename","D.filepath","D.resource","D.resourceid","D.category","D.filemetadata","D.filextension","D.filedescription","D.filesize","D.access"))
											  ->joinLeft(array("CT"=> $tablePrefix."system_users_documents_categories"),"CT.id=D.category"             , array("documentCategorie"=>"CT.libelle","CT.icon","documentCatId"=>"CT.id"))
											  ->where("RQ.processed=0");
	    if( intval($periodStart) ) {
			$selectRequests->where("(RQ.creationdate<='".$periodStart."') OR (D.creationdate<='".$periodStart."')");
		}
		if( intval($creatorid) ){
			$creatorid     = intval($creatorid);
			$selectRequests->where("(RQ.operatorid='".$creatorid."') OR (R.creatorid='".$creatorid."')");
		}
		return $dbAdapter->fetchAll($selectRequests,array(),Zend_Db::FETCH_ASSOC);
	}
	
	public function processedRequests($periodStart=0,$creatorid=0,$notified=0)
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");
		$selectRequests = $dbAdapter->select()->from(    array("RQ"=> $tableName), array("RQ.requestid","RQ.processed","RQ.validated","RQ.notified","RQ.operatorid"))
		                                      ->join(    array("R" => $tablePrefix."reservation_demandes")             ,"R.demandeid=RQ.demandeid"     , "R.*")
											  ->join(    array("RD"=> $tablePrefix."reservation_demandeurs")           ,"RD.demandeurid=R.demandeurid" , array("RD.accountid","demandeurName"=>"RD.name","demandeurLastname"=>"RD.lastname","demandeurFirstname"=>"RD.firstname","demandeurIdentite"=>"RD.numidentite","demandeurPhone"=>"RD.telephone","demandeurEmail"=>"RD.email"))
											  ->join(    array("P" => $tablePrefix."reservation_promoteurs")           ,"P.promoteurid=R.promoteurid"  , array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
											  ->join(    array("E" => $tablePrefix."reservation_demandes_entreprises") ,"E.entrepriseid=R.entrepriseid", array("E.nomcommercial","E.sigle","E.denomination","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->joinLeft(array("S" => $tablePrefix."reservation_demandes_statuts")     ,"S.statutid=R.statutid"        , array("statut"       =>"S.libelle"))
											  ->joinLeft(array("DC"=> $tablePrefix."reservation_demandes_documents")   ,"DC.demandeid=R.demandeid"     , array("documentitle" =>"DC.libelle"))
											  ->joinLeft(array("D" => $tablePrefix."system_users_documents")           ,"D.documentid=DC.documentid"   , array("D.documentid","D.userid","D.filename","D.filepath","D.resource","D.resourceid","D.category","D.filemetadata","D.filextension","D.filedescription","D.filesize","D.access"))
											  ->joinLeft(array("CT"=> $tablePrefix."system_users_documents_categories"),"CT.id=D.category"             , array("documentCategorie"=>"CT.libelle","CT.icon","documentCatId"=>"CT.id"))
											  ->where("RQ.processed=1");
	    if( intval($periodStart) ) {
			$selectRequests->where( "(RQ.updatedate>='".$periodStart."') OR (D.updatedate>='".$periodStart."')");
		}
		if( intval($creatorid) ){
			$creatorid     = intval($creatorid);
			$selectRequests->where("(RQ.operatorid='".$creatorid."') OR (R.creatorid='".$creatorid."')");
		}
		if( $notified===0 || $notified===1 ){
			$selectRequests->where("RQ.notified=?", intval($notified));
		}
		//var_dump($selectRequests->__toString()); die();
		return $dbAdapter->fetchAll($selectRequests,array(),Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getList($filters = array() , $pageNum = 0 , $pageSize = 0, $orders=array("R.expired DESC","R.date DESC","D.lastname ASC","D.firstname ASC"))
	{
		$modelTable     = $this->getTable();
		$dbAdapter      = $modelTable->getAdapter();
		$tablePrefix    = $modelTable->info("namePrefix");		
		$tableName      = $modelTable->info("name");		
		$selectDemandes = $dbAdapter->select()->from(    array("RQ"=> $tableName), array("RQ.requestid","RQ.processed","R.operatorid"))
		                                      ->join(    array("R" => $tablePrefix."reservation_demandes"),"R.demandeid=RQ.demandeid","R.*")
		                                      ->join(    array("P" => $tablePrefix."reservation_promoteurs"),"P.promoteurid=R.promoteurid", array("promoteurName"=>"P.name","promoteurLastname"=>"P.lastname","promoteurFirstname"=>"P.firstname","promoteurIdentite"=>"P.numidentite","promoteurPhone"=>"P.telephone","promoteurEmail"=>"P.email"))
		                                      ->join(    array("D" => $tablePrefix."reservation_demandeurs"),"D.demandeurid=R.demandeurid", array("demandeurName"=>"D.name","demandeurLastname"=>"D.lastname","demandeurFirstname"=>"D.firstname","demandeurIdentite"=>"D.numidentite","demandeurPhone"=>"D.telephone","demandeurEmail"=>"D.email"))
											  ->join(    array("I" => $tablePrefix."reservation_demandeurs_identite") ,"I.identityid=D.identityid", array("numeroPiece"=>"I.numero","I.organisme_etablissement","I.lieu_etablissement","I.date_etablissement"))
											  ->join(    array("T" => $tablePrefix."reservation_demandes_types")      ,"T.typeid=R.typeid", array("typeDemande"=>"T.libelle"))
											  ->join(    array("E" => $tablePrefix."reservation_demandes_entreprises"),"E.entrepriseid=R.entrepriseid",array("E.nomcommercial","E.sigle","E.denomination","E.formid","E.address","E.activite","E.telephone","E.domaineid"))
											  ->joinLeft(array("S" => $tablePrefix."reservation_demandes_statuts"),"S.statutid=R.statutid", array("statut"=>"S.libelle"))
											  ->joinLeft(array("L" => $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid"         , array("localite"=>"L.libelle"));
												
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
		if( isset($filters["libelle"])   && !empty($filters["libelle"]) ){
			$selectDemandes->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["numero"])    && !empty($filters["numero"])){
			$selectDemandes->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["lastname"])   && !empty($filters["lastname"])){
			$selectDemandes->where(  "D.lastname LIKE ?","%".strip_tags($filters["lastname"])."%")
			               ->orWhere("P.lastname LIKE ?","%".strip_tags($filters["lastname"])."%");
		}
		if( isset($filters["firstname"])  && !empty($filters["firstname"])){
			$selectDemandes->where(  "D.firstname LIKE ?","%".$filters["firstname"]."%")
			               ->orWhere("P.firstname LIKE ?","%".$filters["firstname"]."%");
		}
		if( isset($filters["email"])      && !empty($filters["email"])){
			$selectDemandes->where(  "D.email=?",$filters["email"])
			               ->orWhere("P.email=?",$filters["email"]);
		}
		if( isset($filters["country"])    && !empty($filters["country"])){
			$selectDemandes->where("D.country=?",$filters["country"]);
		}
		if( isset($filters["sexe"])       && !empty($filters["sexe"])){
			$selectDemandes->where(  "D.sexe=?",$filters["sexe"])
			               ->orWhere("P.sexe=?",$filters["sexe"]);
		}
	    if( isset($filters["telephone"])  && !empty($filters["telephone"])){
			$selectDemandes->where($dbAdapter->quote("D.telephone=".$filters["telephone"]));
		}
		if( isset($filters["numrccm"])    && !empty($filters["numrccm"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numrccm=".$filters["numrccm"]));
		}
		if( isset($filters["numcnss"])    && !empty($filters["numcnss"]) ){
			$selectDemandes->where($dbAdapter->quote("E.numcnss=".$filters["numcnss"]));
		}
		if( isset($filters["numifu"])     && !empty($filters["numifu"]) ){
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
		if( isset($filters["date"]) && !empty($filters["date"])) {
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
		if( isset( $filters["periode_start"]) && intval($filters["periode_start"] )) {
			$selectDemandes->where("R.date>= ?", intval($filters["periode_start"] ));
		}
	    if( isset( $filters["periode_end"])   && intval($filters["periode_end"] ) ) {
			$selectDemandes->where("R.date<= ?", intval($filters["periode_end"] ) );
		}
		if( isset($filters["creatorid"])      && intval($filters["creatorid"])  ) {
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
