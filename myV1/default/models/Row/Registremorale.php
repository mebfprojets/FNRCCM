<?php

class Model_Registremorale extends Sirah_Model_Default
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
	
	public function likeNum($string)
	{
		$string  = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($string)));
		 
		$pattern = "/BF\-?[a-zA-Z]{2,3}\-?[0-9]{0,2}\-?[0-9]{0,4}\-?B[0-9]{0,2}\-?[0-9]{0,5}$/i";
 
		if(!preg_match($pattern,$string, $match) ) {
			return false;
		}
		return true;
	}
	
	public function getNumParts($numRccm)
	{
		$numRccm  = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		$parts    = array();
		if( empty( $numRccm )) {
			return $parts;
		}
		if( strlen($numRccm)>18) {
			$pattern = "/BF\-(?<localite>[a-zA-Z])\-(<codeJuridiction>[0-2]{2})\-(?<annee>[0-9]{4})\-(?<typeRCCM>(?:B|D|G)[0-9]{1,2})\-?(?<numero>[0-9]{4,5})$/i";		    
		} else {
			$pattern = "/BF\-?(?<localite>[a-zA-Z]{2,3})\-?(?<annee>[0-9]{4})\-?(?<typeRCCM>(?:B|D|G)[0-9]{0,2})\-?(?<numero>[0-9]{4,5})$/i";
		}		
		if(!preg_match($pattern,$numRccm, $matches) ) {
			$numRccmSplit = preg_split("/[-\s:]+/",$numRccm);
			if( isset($numRccmSplit[1])) {
				$matches["localite"]        = $numRccmSplit[1];
			}
			if( strlen($numRccm)>16 ) {
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
			$pattern = "/BF\-[a-zA-Z]{2,3}\-[0-2]{2}\-[0-9]{4}\-(?:B|D|G)[0-9]{2}\-[0-9]{5}$/i";
		} else {
			$pattern = "/BF\-?[a-zA-Z]{2,3}\-?[0-9]{0,2}\-?[0-9]{4}\-?(?:B|D|G)[0-9]{0,2}\-?[0-9]{4,5}$/i";
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
		$rccmNormalTypes       = array("B","D");
		$numRccm               = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		$numRccmParts          = $this->getNumParts($numRccm);
		$numRccmCountry        = $countryCode = (!empty($countryCode))?$countryCode : "BF";
		$numRccmJuridiction    = "";
		if((isset($numRccmParts["codeJuridiction"]) && !empty($numRccmParts["codeJuridiction"])) || 
		    strlen($numRccm)>16) {
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
		$cleanNum    = vsprintf("%s%s%02d%04d%s%04d",array($numRccmCountry,$numRccmLocalite,$numRccmJuridiction,$numRccmAnnee,$numRccmTypeCode,$numRccmId));
		return $cleanNum;
	}
	
	public function modifications($registreid =null ) 
	{
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table               = $this->getTable();
		$dbAdapter           = $table->getAdapter();
		$tablePrefix         = $table->info("namePrefix");		
		$selectModifications = $dbAdapter->select()->from(array("RM"=> $tablePrefix."rccm_registre_modifications"), array("RM.activite_actuel","RM.activite_suppr","RM.activite_ajout"))
		                                           ->join(array("R" => $tablePrefix."rccm_registre" ),"R.registreid=RM.registreid", array("R.registreid","R.numero","R.libelle","R.description","R.date","R.category","annee" => "FROM_UNIXTIME(R.date,'%Y')"))	
											       ->join(array("MT"=> $tablePrefix ."rccm_registre_modifications_type"), "MT.type=RM.type", array("type" => "MT.libelle"))
												   ->where("R.parentid = ?", intval($registreid))
											       ->group(array("R.parentid","R.registreid"))
												   ->order(array("R.annee DESC","R.date DESC"));
		return $dbAdapter->fetchAll( $selectModifications ,array(), Zend_Db::FETCH_ASSOC);		
	}
	
	public function suretes($registreid =null ) 
	{
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");		
		$selectSuretes = $dbAdapter->select()->from(array("RS"=> $tablePrefix ."rccm_registre_suretes"), array("RS.titre","RS.estate","RS.periodstart","RS.periodend","RS.valeur"))
		                                     ->join(array("R" => $tablePrefix ."rccm_registre" ),"R.registreid=RS.registreid", array("R.registreid", "R.numero", "R.libelle", "R.description", "R.date","R.category", "annee" => "FROM_UNIXTIME(R.date,'%Y')"))	
											 ->join(array("ST"=> $tablePrefix ."rccm_registre_suretes_type"), "ST.type=RS.type", array("type" => "ST.libelle"))
											 ->where("R.parentid = ?", intval($registreid))
											 ->group(array("R.parentid","R.registreid"))
											 ->order(array("R.annee DESC","R.date DESC"));								 
		return $dbAdapter->fetchAll($selectSuretes,array(), Zend_Db::FETCH_ASSOC);		
	}
	
	public function dirigeants($registreid =null ) 
	{		
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		
		$selectDirigeants  = $dbAdapter->select()->from(array("RE"=> $tablePrefix ."rccm_registre_representants"), array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.marital_status","RE.email","RE.telephone","RE.passport","RE.sexe","RE.representantid","date_naissance_year"=>"YEAR(RE.datenaissance)","date_naissance_month"=>"MONTH(RE.datenaissance)","date_naissance_day"=>"DAYOFMONTH(RE.datenaissance)"))
		                                         ->join(array("D" => $tablePrefix ."rccm_registre_dirigeants")   , "D.representantid = RE.representantid",array("D.fonction", "profession" => "D.fonction"))
											     ->where("D.registreid = ?", intval($registreid))->order(array("RE.nom ASC", "RE.prenom ASC"));
		return $dbAdapter->fetchAll($selectDirigeants);									  
	}
	
    public function documents( $registreid = null , $access = null )
	{
		if( !$registreid )  {
			 $registreid = $this->registreid;
		}
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		$selectDocuments = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.documentid","D.resourceid", "D.userid"))
				                               ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id = D.category", array("category"=> "C.libelle"))
				                               ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid  = D.documentid",array("RD.access"))
				                               ->where("RD.registreid = ?", $registreid  );
		if( null !== $access ) {
			$selectDocuments->where("D.access = ?", intval( $access ));
		}
		$selectDocuments->order(array("RD.registreid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	public function getSimilarList($searchQuery, $split_length= 4)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("R.registreid", "R.numero", "R.libelle", "R.description", "R.date","R.category", "annee" => "FROM_UNIXTIME(R.date,'%Y')"))
		                                      ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid     = R.registreid",array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid", array("RE.nom", "RE.prenom","RE.adresse"))
		                                      ->join(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", array("domaine" =>"D.libelle"))
											  ->where("R.type = 1");
	    if( empty( $searchQuery )) {
			return array();
		}
		$searchSpaceArray = preg_split("/[\s,]+/", $searchQuery, $split_length);
		if( isset($searchSpaceArray[1])) {
			$againstValue = "+".$searchSpaceArray[0]."* ";
			unset($searchSpaceArray[0]);
			foreach( $searchSpaceArray as $searchWord ) {
				     $againstValue  .="~".$searchWord."* ";
			}			
		} else {
			$searchWordArray = str_split($searchQuery, 3);
			if( isset( $searchWordArray[0] )) {
				$againstValue = "+".$searchWordArray[0]."* ";
				unset($searchWordArray[0]);
			}
			if( count(   $searchWordArray)) {
				foreach( $searchWordArray as $searchWord ) {
				         $againstValue  .="~".$searchWord."* ";
			    }
			}
		}
		$selectRegistre->where("MATCH(R.libelle) AGAINST (\"".$againstValue."\") ");
		//print_r($selectRegistre->__toString()); die();
	    return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getSimilarListPaginator($searchQuery, $split_length= 4)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("R.registreid"))
		                                      ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid     = R.registreid",null)
		                                      ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid",null)
		                                      ->join(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", array("domaine" =>"D.libelle"))
											  ->where("R.type=2");
	    if( empty( $searchQuery )) {
			$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
			$paginationAdapter->setRowCount(0);
			$paginator         = new Zend_Paginator($paginationAdapter);
			return $paginator;
		}
		$searchSpaceArray = preg_split("/[\s,]+/", $searchQuery, $split_length);
		if( isset($searchSpaceArray[1])) {
			$againstValue = "+".$searchSpaceArray[0]."* ";
			unset($searchSpaceArray[0]);
			foreach( $searchSpaceArray as $searchWord ) {
				     $againstValue  .="~".$searchWord."* ";
			}			
		} else {
			$searchWordArray = str_split($searchQuery, 3);
			if( isset( $searchWordArray[0] )) {
				$againstValue = "+".$searchWordArray[0]."* ";
				unset($searchWordArray[0]);
			}
			if( count(   $searchWordArray)) {
				foreach( $searchWordArray as $searchWord ) {
				         $againstValue  .="~".$searchWord."* ";
			    }
			}
		}
		$likeLibelle       = new Zend_Db_Expr("MATCH(R.libelle) AGAINST (\"".$againstValue."\")");
		$selectRegistre->where("{$likeLibelle}");
	    
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("R.numero ASC","L.libelle ASC","R.libelle ASC","R.date DESC"))
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$dateNaissance  = new Zend_Db_Expr("DATE_FORMAT(RE.datenaissance,'%d/%m/%Y')");
		$selectRegistres= $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.numero","R.numifu","R.numcnss","R.libelle","R.description","R.date","R.category","annee"=> "FROM_UNIXTIME(R.date,'%Y')"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_entreprises") ,"RP.registreid=R.registreid",null )
											  ->join(array("RD"=> $tablePrefix."rccm_registre_dirigeants")  ,"RD.registreid=R.registreid",array("RD.representantid"))
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RD.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.passport","RE.sexe","RE.country","RE.marital_status","RE.telephone","RE.email","RE.profession","date_naissance"=>$dateNaissance))
		                                      ->join(array("L" => $tablePrefix."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D"=> $tablePrefix ."rccm_domaines"),"D.domaineid=R.domaineid ", array("domaine" =>"D.libelle"))->where("R.type=2");
	    
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords               = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistres->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["keywords"]  = str_replace('"', "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("'", "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("-", ",",$filters["keywords"]);
				$filters["keywords"]  = str_replace("(", ";",$filters["keywords"]);
				$filters["keywords"]  = str_replace(")", ";",$filters["keywords"]);
				$filters["keywords"]  = trim($filters["keywords"],";,-\s\n\r ");
				$searchAgainst        = "";
				$cleanWord            = "";
				$keywordsCond         = array();
				$keywordsArr          = preg_split("/[,;\-\*\@]+/",trim(strip_tags($filters["keywords"])));
				//print_r($keywordsArr); die();
				if( count(   $keywordsArr)) {
					foreach( $keywordsArr as $keywordItem ) {
							 $keywordItem      = trim($keywordItem);
							 $searchSpaceArray = preg_split("/[\s]+/",trim(strip_tags($keywordItem)));
							 $searchAgainst    = "";
							 if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
								 foreach( $searchSpaceArray as $searchKeyId=> $searchWord ) {
										  $searchWord = trim($searchWord);
										  if( empty($searchWord) || (strlen($searchWord) < 3)) {
											  continue;
										  }
										  $searchWordUppercase   = strtoupper($searchWord);
										  if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
											  $cleanSearchWord   = preg_replace("/[,;\-\*\@~]+/","",$searchWord);
											  if( $searchKeyId==0) {
												  $searchAgainst.= $cleanSearchWord."* ";
											  } else {
												  $searchAgainst.= "~".$cleanSearchWord."* ";
											  }										  
											  $cleanWord        .= $cleanSearchWord." ";
										  }																				  
								 }
								 if(!empty($searchAgainst)) {
									 $keywordsCond[]            = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");														 
								 }
							 }								
					}
				}	
				if( count($keywordsCond) && is_array($keywordsCond)) {
					$selectRegistres->where(implode(" OR ",$keywordsCond));
				}
			}			
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectRegistres->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"])){
			$selectRegistres->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"])){
			$selectRegistres->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"])     && !empty($filters["searchQ"]) ){
			$emptyWords                  = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]       = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike              =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistres->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["searchQ"]      = str_replace('"', ""  ,$filters["searchQ"]);
				$filters["searchQ"]      = str_replace("'", ""  ,$filters["searchQ"]);
				$filters["searchQ"]      = str_replace("-", " " ,$filters["searchQ"]);
				$searchQ                 = strip_tags($filters["searchQ"]);
				$searchSpaceArray        = preg_split("/[\s,\-\*]+/",trim($dbAdapter->quote($searchQ)));
				$searchAgainst           = "";
				if( isset(   $searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
					foreach( $searchSpaceArray as $searchWord ) {
							 $searchWord = trim($searchWord);
							 if( empty($searchWord) || (strlen($searchWord) < 3)) {
								 continue;
							 }
							 if(!in_array($searchWord, $emptyWords) ) {
								 $searchAgainst.="+".$searchWord."* ";
							 }	
					}
				}
				$likeRCName               = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");				
				$selectRegistres->where("{$likeRCName} OR {$likeClientName}");
			}
			//print_r($selectRegistres->__toString()); die();
		}
		if( isset($filters["name"]) && !empty($filters["name"]) ){
			$filters["name"]              = str_replace('"', "", $filters["name"]);
			$filters["name"]              = str_replace("'", "", $filters["name"]);
			$filters["name"]              = $searchName = strip_tags(str_replace("-"," ",trim($filters["name"])));
			$searchSpaceArray             = preg_split("/[\s,]+/",$searchName);
			$searchAgainst                = "";
			$nom                          = $prenom = "";
			if( count($searchSpaceArray) >= 2 ) {
				if( isset($searchSpaceArray[0])){
					$nom                  = $searchSpaceArray[0];
					$prenom               = str_replace($nom,"", trim($filters["name"]));
				}
				if(!empty( $nom ) ) {
					$selectRegistres->where(new Zend_Db_Expr("RE.nom    LIKE \"%".strip_tags(trim($nom))   ."%\""));
				}
				if(!empty( $prenom ) ) {
					$selectRegistres->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags(trim($prenom))."%\""));
				}
			} elseif(count($searchSpaceArray)==1) {
				$searchClientName         = 
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchName."\" IN BOOLEAN MODE)");				
				$selectRegistres->where("{$likeClientName}");
			}			
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) ){
			$selectRegistres->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && empty($filters["prenom"])) {
				$selectRegistres->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"])){
			$selectRegistres->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistres->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"]) ){
			$selectRegistres->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistres->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))   ){
			$selectRegistres->where("RE.country = ?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistres->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistres->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistres->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistres->where("FROM_UNIXTIME(R.date,'%Y') = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistres->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistres->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistres->where("FROM_UNIXTIME(R.date,'%Y-%m-%d') = ? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistres->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistres->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistres->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistres->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		$selectRegistres->group(array("R.registreid", "RP.registreid","RD.registreid"));
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistres->order( $orders );
		}
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistres->limitPage( $pageNum , $pageSize);
		}
		$rows     = array();
		try {	
		    $rows = $dbAdapter->fetchAll( $selectRegistres, array() , Zend_Db::FETCH_ASSOC);
		} catch(Exception $e) {
			$this->setError($e->getMessage());
			$rows = array();
		}
		return $rows;
	}
	
	
	public function getListPaginator( $filters = array() )
	{
	    $table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistres= $dbAdapter->select()->from(array("R"  => $tablePrefix ."rccm_registre" ), array("R.registreid"))
		                                      ->join(array("RP" => $tablePrefix ."rccm_registre_entreprises")  , "RP.registreid     = R.registreid", null )
											  ->join(array("RD" => $tablePrefix ."rccm_registre_dirigeants")   , "RD.registreid     = R.registreid", null)
		                                      ->join(array("RE" => $tablePrefix ."rccm_registre_representants"), "RE.representantid = RD.representantid", null)
		                                      ->join(array("L"  => $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", null)
		                                      ->joinLeft(array("D"  => $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", null)->where("R.type=2");
	    if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords               = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistres->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["keywords"]  = str_replace('"', "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("'", "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("-", ",",$filters["keywords"]);
				$filters["keywords"]  = str_replace("(", ";",$filters["keywords"]);
				$filters["keywords"]  = str_replace(")", ";",$filters["keywords"]);
				$filters["keywords"]  = trim($filters["keywords"],";,-\s\n\r ");
				$searchAgainst        = "";
				$cleanWord            = "";
				$keywordsCond         = array();
				$keywordsArr          = preg_split("/[,;\-\*\@]+/",trim(strip_tags($filters["keywords"])));
				//print_r($keywordsArr); die();
				if( count(   $keywordsArr)) {
					foreach( $keywordsArr as $keywordItem ) {
							 $keywordItem      = trim($keywordItem);
							 $searchSpaceArray = preg_split("/[\s]+/",trim(strip_tags($keywordItem)));
							 $searchAgainst    = "";
							 if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
								 foreach( $searchSpaceArray as $searchKeyId=> $searchWord ) {
										  $searchWord = trim($searchWord);
										  if( empty($searchWord) || (strlen($searchWord) < 3)) {
											  continue;
										  }
										  $searchWordUppercase   = strtoupper($searchWord);
										  if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
											  $cleanSearchWord   = preg_replace("/[,;\-\*\@~]+/","",$searchWord);
											  if( $searchKeyId==0) {
												  $searchAgainst.= $cleanSearchWord."* ";
											  } else {
												  $searchAgainst.= "~".$cleanSearchWord."* ";
											  }										  
											  $cleanWord        .= $cleanSearchWord." ";
										  }																				  
								 }
								 if(!empty($searchAgainst)) {
									 $keywordsCond[]            = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");														 
								 }
							 }								
					}
				}	
				if( count($keywordsCond) && is_array($keywordsCond)) {
					$selectRegistres->where(implode(" OR ",$keywordsCond));
				}
			}			
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) ){
			$selectRegistres->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"])){
			$selectRegistres->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"])){
			$selectRegistres->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"])     && !empty($filters["searchQ"]) ){
			$emptyWords                  = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]       = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike              =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistres->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["searchQ"]      = str_replace('"', ""  ,$filters["searchQ"]);
				$filters["searchQ"]      = str_replace("'", ""  ,$filters["searchQ"]);
				$filters["searchQ"]      = str_replace("-", " " ,$filters["searchQ"]);
				$searchQ                 = strip_tags($filters["searchQ"]);
				$searchSpaceArray        = preg_split("/[\s,\-\*]+/",trim($dbAdapter->quote($searchQ)));
				$searchAgainst           = "";
				if( isset(   $searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
					foreach( $searchSpaceArray as $searchWord ) {
							 $searchWord = trim($searchWord);
							 if( empty($searchWord) || (strlen($searchWord) < 3)) {
								 continue;
							 }
							 if(!in_array($searchWord, $emptyWords) ) {
								 $searchAgainst.="+".$searchWord."* ";
							 }	
					}
				}
				$likeRCName               = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");				
				$selectRegistres->where("{$likeRCName} OR {$likeClientName}");
			}
			//print_r($selectRegistres->__toString()); die();
		}
		if( isset($filters["name"]) && !empty($filters["name"]) ){
			$filters["name"]              = str_replace('"', "", $filters["name"]);
			$filters["name"]              = str_replace("'", "", $filters["name"]);
			$filters["name"]              = $searchName = strip_tags(str_replace("-"," ",trim($filters["name"])));
			$searchSpaceArray             = preg_split("/[\s,]+/",$searchName);
			$searchAgainst                = "";
			$nom                          = $prenom = "";
			if( count($searchSpaceArray) >= 2 ) {
				if( isset($searchSpaceArray[0])){
					$nom                  = $searchSpaceArray[0];
					$prenom               = str_replace($nom,"", trim($filters["name"]));
				}
				if(!empty( $nom ) ) {
					$selectRegistres->where(new Zend_Db_Expr("RE.nom    LIKE \"%".strip_tags(trim($nom))   ."%\""));
				}
				if(!empty( $prenom ) ) {
					$selectRegistres->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags(trim($prenom))."%\""));
				}
			} elseif(count($searchSpaceArray)==1) {
				$searchClientName         = 
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchName."\" IN BOOLEAN MODE)");				
				$selectRegistres->where("{$likeClientName}");
			}			
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) ){
			$selectRegistres->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && empty($filters["prenom"])) {
				$selectRegistres->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"])){
			$selectRegistres->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistres->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["passport"]) && !empty($filters["passport"]) ){
			$selectRegistres->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistres->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))   ){
			$selectRegistres->where("RE.country = ?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistres->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistres->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistres->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistres->where("FROM_UNIXTIME(R.date,'%Y') = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistres->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistres->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistres->where("FROM_UNIXTIME(R.date,'%Y-%m-%d') = ? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistres->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistres->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistres->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistres->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		$selectRegistres->group(array("R.registreid","RP.registreid","RD.registreid"));
		$paginator             = null; 
		try {
			$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistres );
			$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistres )));
			$paginationAdapter->setRowCount($rowCount);
			$paginator         = new Zend_Paginator($paginationAdapter);
		} catch(Exception $e) {
			$this->setError($e->getMessage());
			$paginator         = null;
		}
		
		return $paginator;
	}             		 
}