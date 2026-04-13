<?php

class Model_Registremodification extends Sirah_Model_Default
{
	
	public function getNumParts($numRccm)
	{
		$numRccm  = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		$parts    = array();
		if( empty( $numRccm )) {
			return $parts;
		}
		if( strlen($numRccm)>18) {
			$pattern = "/BF\-(?<localite>[a-zA-Z])\-(<codeJuridiction>[0-2]{2})\-(?<annee>[0-9]{4})\-(?<typeRCCM>(?:M|R|T)[0-9]{1,2})\-?(?<numero>[0-9]{5})$/i";		    
		} else {
			$pattern = "/BF\-?(?<localite>[a-zA-Z]{2,3})\-?(?<annee>[0-9]{4})\-?(?<typeRCCM>(?:M|R|T)[0-9]{0,2})\-?(?<numero>[0-9]{4,5})$/i";
		}		
		if(!preg_match($pattern,$numRccm, $matches) ) {
			$numRccmSplit = preg_split("/[-\s:]+/",$numRccm);
			if( isset($numRccmSplit[1])) {
				$matches["localite"]        = $numRccmSplit[1];
			}
			if( strlen($numRccm)>18) {
				$matches["codeJuridiction"] = $numRccmSplit[2];
				$matches["annee"]           = (isset($numRccmSplit[3]))?$numRccmSplit[3] : "";
				$matches["typeRCCM"]        = (isset($numRccmSplit[4]))?$numRccmSplit[4] : "";
				$matches["numero"]          = (isset($numRccmSplit[5]))?$numRccmSplit[5] : "";
			} else {
				$matches["annee"]           = $numRccmSplit[2];
				$matches["typeRCCM"]        = (isset($numRccmSplit[3]))?$numRccmSplit[3] : "";
				$matches["numero"]          = (isset($numRccmSplit[4]))?$numRccmSplit[4] : "";
			}
		}
		if( count($matches)) {
			$parts = $matches;
		}
		return $parts;
	}
	
	public function checkNum($numRccm)
	{
		$numRccm = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		if( empty( $numRccm )) {
			return false;
		}
		if( strlen($numRccm)>18) {
			$pattern = "/BF\-[a-zA-Z]{2,3}\-[0-2]{2}\-[0-9]{4}\-(?:M|R|T)[0-9]{0,3}\-[0-9]{4,5}$/i";
		} else {
			$pattern = "/BF\-?[a-zA-Z]{2,3}\-?[0-9]{0,2}\-?[0-9]{4}\-?(?:M|R|T)[0-9]{0,3}\-?[0-9]{4,5}$/i";
		}
		if(!preg_match($pattern,$numRccm, $match) ) {
			return false;
		}
		return true;
	}
	
	public function isValidNum($numeroRCCM, $localite = null, $annee = 0)
	{
		if(!$this->checkNum($numeroRCCM )) {
			return FALSE;
		}
		$cleanNumero = $this->normalizeNum($numeroRCCM,$annee,$localite);
		return stripos($numeroRCCM,$cleanNumero);
	}
	
	public function normalizeNum($numRccm,$annee=null,$localiteCode=null,$countryCode="BF",$idLength=4)
	{
		if( empty( $numRccm )) {
			return false;
		}
		$rccmNormalTypes       = array("M","R","T");
		$numRccm               = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		$numRccmParts          = $this->getNumParts($numRccm);
		$numRccmCountry        = $countryCode = (!empty($countryCode))?$countryCode : "BF";
		$numRccmJuridiction    = "";
		if((isset($numRccmParts["codeJuridiction"]) && !empty($numRccmParts["codeJuridiction"])) || 
		    strlen($numRccm)>15) {
			$numRccmCountry    = "BF";
		    $numRccmLocalite   = (isset($numRccmParts["localite"]))?$numRccmParts["localite"] : trim(  substr($numRccm,2,3 ));
			$numRccmJuridiction= (isset($numRccmParts["codeJuridiction"]))?$numRccmParts["codeJuridiction"] : trim(  substr($numRccm,5,2));
			$numRccmAnnee      = (isset($numRccmParts["annee"]   ))?$numRccmParts["annee"]    : intval(substr($numRccm,7,4 ));
			$numRccmTypeCode   = (isset($numRccmParts["typeRCCM"]))?$numRccmParts["typeRCCM"] : trim(  substr($numRccm,11,3));
			$numRccmId         = (isset($numRccmParts["numero"]  ))?$numRccmParts["numero"]   : trim(  substr($numRccm,14,5));
		    if(!in_array(substr($numRccmTypeCode,0,1), $rccmNormalTypes)) {
				return false;
			}
		} else {
			$numRccm           = trim(preg_replace("/\s+/", "", $numRccm ));
			$numRccmCountry    = trim(  substr($numRccm, 0, 2));
			$numRccmLocalite   = trim(  substr($numRccm, 2, 3));
			$numRccmAnnee      = intval(substr($numRccm, 5, 4));
			$numRccmTypeCode   = trim(  substr($numRccm, 9, 1));
			$numRccmId         = trim(  substr($numRccm, 10  ));			
			if(!in_array($numRccmTypeCode, $rccmNormalTypes)) {
				return false;
			}
		}
		if((null != $annee) && ( $annee!=$numRccmAnnee ) && intval($annee) > 2000) {
			$numRccmAnnee      = $annee;
		}
		if((null != $localiteCode) && ($localiteCode != $numRccmLocalite ) ) {
			$numRccmLocalite   = $localiteCode;
		}
		if((null != $countryCode ) && ($countryCode  != $numRccmCountry) ) {
			$numRccmCountry    = $countryCode;
		}
		if( empty( $numRccmCountry) || empty($numRccmLocalite) || empty($numRccmAnnee) || empty($numRccmTypeCode)) {
			return false;
		}
		if( strlen($numRccm)<16 || !isset($numRccmParts["codeJuridiction"])) {
			$cleanNum          = vsprintf("%s%s%04d%s%05d",array($numRccmCountry,$numRccmLocalite,$numRccmAnnee,$numRccmTypeCode,$numRccmId));
		} else {
			$cleanNum          = vsprintf("%s-%s-%02d-%04d-%s-%05d",array($numRccmCountry,$numRccmLocalite,$numRccmJuridiction,$numRccmAnnee,$numRccmTypeCode,$numRccmId));
		}
		return $cleanNum;
	}
	
	public function representant( $registreid =null )
	{
		if( !$registreid ) {
			 $registreid   = $this->registreid;
		}
		$table             = new Table_Representants();		
		$tablePrefix       = $table->info("namePrefix");
        $selectDirigeants  = $table->select()->setIntegrityCheck(false)		
		                                     ->from(array("RE"=> $tablePrefix ."rccm_registre_representants"), array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.marital_status","RE.email","RE.telephone","RE.passport","RE.representantid","RE.sexe","date_naissance_year" => "YEAR(RE.datenaissance)","date_naissance_month" => "MONTH(RE.datenaissance)", "date_naissance_day" => "DAYOFMONTH(RE.datenaissance)","RE.country"))
		                                     ->join(array("D" => $tablePrefix ."rccm_registre_dirigeants"   ), "D.representantid = RE.representantid",array("D.fonction","profession" => "D.fonction"))
											 ->where("D.registreid = ?", intval($registreid));
		return $table->fetchRow( $selectDirigeants, array("RE.representantid ASC","RE.nom ASC","RE.prenom ASC"));
	}
	
	public function dirigeants($registreid =null ) 
	{		
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		
		$selectDirigeants  = $dbAdapter->select()->from(array("RE"=> $tablePrefix ."rccm_registre_representants"), array("RE.nom", "RE.prenom","RE.adresse", "RE.lieunaissance", "RE.datenaissance", "RE.marital_status", "RE.email", "RE.telephone","RE.passport","RE.representantid",
		                                                                                                                 "date_naissance_year" => "YEAR(RE.datenaissance)", "date_naissance_month" => "MONTH(RE.datenaissance)", "date_naissance_day" => "DAYOFMONTH(RE.datenaissance)"))
		                                         ->join(array("D" => $tablePrefix ."rccm_registre_dirigeants")   , "D.representantid = RE.representantid",array("D.fonction"))
											     ->where("D.registreid = ?", intval($registreid))->order(array("RE.representantid ASC","RE.nom ASC", "RE.prenom ASC"));
		return $dbAdapter->fetchAll($selectDirigeants);									  
	}
	
    public function documents( $registreid = null , $access = null )
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
		if( null !== $access ) {
			$selectDocuments->where("D.access = ?", intval( $access ));
		}
		$selectDocuments->order(array("RD.registreid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("R.numero ASC","L.libelle ASC","R.libelle ASC","R.date DESC"))
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("RM"=> $tablePrefix ."rccm_registre_modifications"), array("RM.activite_actuel","RM.activite_suppr","RM.activite_ajout"))
		                                      ->join(array("R" => $tablePrefix ."rccm_registre" )             , "R.registreid=RM.registreid", array("R.registreid","R.numero","R.libelle","R.description","R.date","R.category","annee" => "FROM_UNIXTIME(R.date,'%Y')"))
                                              ->join(array("P" => $tablePrefix ."rccm_registre" )             , "P.registreid=R.parentid"   , array("parent_numero"=>"P.numero"))		                                      
		                                      ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid=R.registreid OR RP.registreid=R.parentid",array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid", array("RE.nom", "RE.prenom","RE.adresse"))
		                                      ->joinLeft(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid"   , array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("MT"=> $tablePrefix ."rccm_registre_modifications_type"), "MT.type=RM.type"  , array("type_modification" => "MT.libelle"))
											  ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", array("domaine" =>"D.libelle"));
	    if( isset($filters["keywords"]) && !empty($filters["keywords"]) && (null!==$filters["keywords"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["keywords"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeLibelle = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			
		    $selectRegistre->where("{$likeLibelle}");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"]) && (null!==$filters["description"]) ){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"]) && (null!==$filters["passport"]) ){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["searchQ"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectRegistre->where("{$likeRCName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["name"]));
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				$nom          = $searchSpaceArray[0];
				$prenom       = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectRegistre->where(new Zend_Db_Expr("RE.nom LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectRegistre->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags($prenom)."%\""));
			}
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
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))   ){
			$selectRegistre->where("RE.country = ?", strip_tags($filters["country"]));
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
		/*if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date >= ? " , intval($filters["periode_start"]));
		}*/
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["parentid"]) && intval($filters["parentid"])  ) {
			$selectRegistre->where("R.parentid = ?", intval( $filters["parentid"] ) );
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}
		//($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("RM"=>$tablePrefix ."rccm_registre_modifications"), array("RM.registreid"))
		                                      ->join(array("R" =>$tablePrefix ."rccm_registre" )             , "R.registreid     = RM.registreid", null)
		                                      ->join(array("P" =>$tablePrefix ."rccm_registre" )             , "P.registreid     = R.parentid"   , null)
											  ->join(array("RP"=>$tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid    = R.registreid" , null )
		                                      ->join(array("RE"=>$tablePrefix ."rccm_registre_representants"), "RE.representantid= RP.representantid", null )
		                                      ->join(array("L" =>$tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid"   , null )
											  ->join(array("MT"=>$tablePrefix ."rccm_registre_modifications_type"), "MT.type=RM.type"  , null )
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", null )
											  ->where("R.type = 4");
	    if( isset($filters["keywords"]) && !empty($filters["keywords"]) && (null!==$filters["keywords"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["keywords"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeLibelle = new Zend_Db_Expr("MATCH(RM.numero,R.libelle,R.description) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			
		    $selectRegistre->where("{$likeLibelle}");
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"]) && (null!==$filters["description"]) ){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"]) && (null!==$filters["passport"]) ){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["searchQ"]));
			$searchAgainst    = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectRegistre->where("{$likeRCName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$searchSpaceArray = preg_split("/[\s,]+/",strip_tags($filters["name"]));
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				$nom          = $searchSpaceArray[0];
				$prenom       = str_replace($nom,"", $filters["name"]);
			}
			if( empty( $nom ) ) {
				$selectRegistre->where(new Zend_Db_Expr("RE.nom LIKE \"%".strip_tags($nom)."%\""));
			}
			if( empty( $prenom ) ) {
				$selectRegistre->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags($prenom)."%\""));
			}
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
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))   ){
			$selectRegistre->where("RE.country = ?", strip_tags($filters["country"]));
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
		if( isset($filters["parentid"]) && intval($filters["parentid"])  ) {
			$selectRegistre->where("R.parentid = ?", intval( $filters["parentid"] ) );
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