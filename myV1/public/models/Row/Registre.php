<?php

class Model_Registre extends Sirah_Model_Default
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
	
	public function hasBlasklisted($ipaddress)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectSearchKey= $dbAdapter->select()->from(array("RS" => $tablePrefix."system_guests_connexion_ipinfos"))
		                                      ->where("RS.ipaddress=?",substr(strip_tags($ipaddress),0,26))
											  ->where("RS.blacklisted=1");
		return 	$dbAdapter->fetchRow($selectSearchKey,array(),5);								  
	}
	
	public function hasViewed($ipaddress, $registreid)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectSearchKey= $dbAdapter->select()->from(array("RS" => $tablePrefix."rccm_registre_consultations"),array("RS.ipaddress","RS.registreid"))
		                                      ->where("RS.ipaddress=?",substr(strip_tags($ipaddress),0,26))
											  ->where("RS.registreid=?",intval($ipaddress));
		return 	$dbAdapter->fetchRow($selectSearchKey,array(),5);								  
	}
	
	public function hasSearched($ipaddress, $searchKey)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectSearchKey= $dbAdapter->select()->from(array("RS" => $tablePrefix."rccm_registre_search"),array("RS.ipaddress","RS.searchkey"))
		                                      ->where("RS.ipaddress=?",substr(strip_tags($ipaddress),0,26))
											  ->where("RS.searchkey=?",substr(strip_tags($searchKey),0,200));
		return 	$dbAdapter->fetchRow($selectSearchKey,array(),5);								  
	}
	
	public function cleanName($keywords,$numeroRCCM=null)
	{
		if(!empty($numeroRCCM) ) {
			$keywords    = str_ireplace($numeroRCCM,"",$keywords);
		}
		$keywords        = str_ireplace(array("()","BURKINA","FASO","Burkina","burkina","faso","Sarl","SARL","S.A.R.L","SARL","S.A","Union","Service","SERVICE","SERVICES","MULTISERVICES","Multi-service","MULTI-SERVICES","multiservices","multiservice"), "", $keywords );
		preg_match_all("/\([^\)]*\)/", $keywords, $matches);
		if( count($matches[0]) ) {
			$keywords    = str_replace(end($matches[0]),"", $keywords);
		}
		
		return $keywords ;
	}
	
	public function getListAfter($registreid,$periodStart=0,$limitPage=100,$annee=0,$orders=array("R.registreid DESC"))
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistres= $dbAdapter->select()->from(    array("R" => $tablePrefix."rccm_registre" ))
		                                      ->join(    array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid"         , array("RP.representantid"))
		                                      ->join(    array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.passport","RE.sexe","RE.telephone"))											  
											  ->joinLeft(array("E" => $tablePrefix."rccm_registre_entreprises")  ,"E.registreid=R.registreid"          , array("E.responsable","E.responsableid","E.responsable_email","E.num_securite_social","E.num_ifu","E.num_rc","E.reference","E.chiffre_affaire","E.groupid","E.address","E.email","E.phone1","E.phone2","E.siteweb","E.country","E.city","E.zip","E.nbemployes_min","E.nbemployes_max","E.datecreation","E.presentation","E.region"))
											  ->joinLeft(array("M" => $tablePrefix."rccm_registre_modifications"),"M.registreid=R.registreid"          , array("typeModification"=>"M.type","M.activite_actuel","M.activite_suppr","M.activite_ajout"))
											  ->joinLeft(array("S" => $tablePrefix."rccm_registre_suretes")      ,"S.registreid=R.registreid"          , array("S.titre","S.nom_constituant","S.numrccm_constituant","S.estate","S.valeur","typeSurete"=>"S.type","S.periodstart","S.periodend"))
											  ->where("R.registreid>=?", intval($registreid));
		if( intval($periodStart)) {
			$selectRegistres->where("R.creationdate>=?", intval($periodStart));
		}
        if( intval($annee)) {
			$selectRegistres->where("R.annee=?", intval($annee));
		}			
        if( intval($limitPage) ) {
			$selectRegistres->limitPage(1,intval($limitPage));
		}	
        if( count($orders) ) {
			$selectRegistres->order($orders);
		} else {
			$selectRegistres->order(array("R.registreid DESC"));
		}			
	    return $dbAdapter->fetchAll($selectRegistres, array(), Zend_Db::FETCH_ASSOC);
	}
	
	
	public function last( $annee=0,$maxRegistreId=0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.localiteid","R.domaineid","R.numero","R.numifu","R.numcnss","R.libelle","R.description","R.date","R.category","R.type","date_registre"=>"R.date","annee"=>"R.annee","R.creationdate","R.creatorid"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid"         , array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.passport","RE.sexe","RE.telephone"))											  
											  ->join(array("L" => $tablePrefix."rccm_localites")             ,"L.localiteid=R.localiteid"          , array("localite"=>"L.libelle"))
		                                      ->join(array("D" => $tablePrefix."rccm_domaines")              ,"D.domaineid=R.domaineid"            , array("domaine" =>"D.libelle"))
											  ->order(array("R.registreid DESC"));
	    if( intval($annee)) {
			$selectRegistre->where("R.annee=?", intval($annee));
		}
		if( intval($maxRegistreId)) {
			$selectRegistre->where("R.registreid<=?", intval($maxRegistreId));
		}
		return $dbAdapter->fetchRow($selectRegistre,array(), Zend_Db::FETCH_ASSOC);  
	}
	
	public function emptywords()
	{		 
		$emptyWords        = array("Burkina","burkina","faso","Sarl","SARL","Ets","Etablissement","Société","Entreprise","ENTREPRISE","DU","BURKINA","FASO","Frères","etablissement","société","Société","Societe","SOCIETE","ETABLISSEMENT","Freres","FRERE","FRERES","Ouagadougou","Ouaga","OUAGADOUGOU","S.A.R.L","SARL","S.A","Union","Service","SERVICE","SERVICES","MULTISERVICES","Multi-service","MULTI-SERVICES","multiservices","multiservice","GENERAL");
	    return $emptyWords;
	}
	
	public function insertsigue($data, $creatorid=26)
	{
		$model = $modelRegistre = $this;
		$modelRegistrePhysique  = new Model_Registrephysique();
		$modelRegistreMorale    = new Model_Registremorale();
		$modelLocalite          = new Model_Localite();
		$modelDomaine           = new Model_Domaine();
		$modelEntreprise        = new Model_Entreprise();
		$modelFormeJuridique    = new Model_Entrepriseforme();
		$modelRepresentant      = new Model_Representant();
		$modelEntrepriseAddress = new Model_Registreadresse();
		
		$strNotEmptyValidator   = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
		//On crée les filtres qui seront utilisés sur les données du formulaire
		$stringFilter           = new Zend_Filter();
		$stringFilter->addFilter( new Zend_Filter_StringTrim());
		$stringFilter->addFilter( new Zend_Filter_StripTags());
		$stringFilter->addFilter( new Sirah_Filtre_Encode());
		$stringFilter->addFilter( new Sirah_Filtre_FormatDate());
		$stringFilter->addFilter( new Sirah_Filtre_StripNull());
		
		$layout                 = Zend_Layout::getMvcInstance();
        $view                   = $layout->getView(); 
		$countries              = $view->countries();
		
		$modelTable             = $this->getTable();
		$tablePrefix            = $prefixName = $modelTable->info("namePrefix");
		$dbAdapter              = $modelTable->getAdapter();
		
		$registreRow            =  array_map(function($field) use ($stringFilter){$cleanField=$field;if(is_string($field)){$cleanField = $stringFilter->filter($field); } return $cleanField; }, $data);
		$DateDemande            = (isset($registreRow["DateDemande"]        ))?$registreRow["DateDemande"]                                        : "";
		$DateRCCM               = (isset($registreRow["DateRCCM"]           ))?$registreRow["DateRCCM"]                                           : "";
		$DateCNSS               = (isset($registreRow["DateCNSS"]           ))?$registreRow["DateCNSS"]                                           : "";
		$DateIFU                = (isset($registreRow["DateIFU"]            ))?$registreRow["DateIFU"]                                            : "";
		$DateImmIFU             = (isset($registreRow["DateImmIFU"]         ))?$registreRow["DateImmIFU"]                                         : "";
		$NumeroRCCM             = (isset($registreRow["NumeroRCCM"]         ))?$stringFilter->filter($registreRow["NumeroRCCM"])                  : "";	
		$NumeroIFU              = (isset($registreRow["NumeroIFU"]          ))?$stringFilter->filter($registreRow["NumeroIFU"] )                  : "";
		$NumeroCNSS             = (isset($registreRow["NumeroCNSS"]         ))?$stringFilter->filter($registreRow["NumeroCNSS"] )                 : "";
		$Description            = (isset($registreRow["SecteurActivite"]    ))?$stringFilter->filter($registreRow["SecteurActivite"] )            : "";		
		$IdEntreprise           = (isset($registreRow["IdEntreprise"]       ))?$stringFilter->filter($registreRow["IdEntreprise"] )               : "";
		$IdActivite             = (isset($registreRow["IdActivite"]         ))?$stringFilter->filter($registreRow["IdActivite"] )                 : "";
		$IdFormeJuridique       = (isset($registreRow["IdFormeJuridique"]   ))?$stringFilter->filter($registreRow["IdFormeJuridique"])            : "";	
		$IdIFU                  = (isset($registreRow["IdIFU"]              ))?$stringFilter->filter($registreRow["IdIFU"] )                      : "";
		$IdCNSS                 = (isset($registreRow["IdCNSS"]             ))?$stringFilter->filter($registreRow["IdCNSS"] )                     : "";
		$IdStatus               = (isset($registreRow["IdStatus"]           ))?$stringFilter->filter($registreRow["IdStatus"] )                   : "";						 
		$NomCommercial          = (isset($registreRow["NomCommercial"]      ))?$stringFilter->filter($registreRow["NomCommercial"])               : "";
		$DenominationSociale    = (isset($registreRow["DenominationSociale"]))?$stringFilter->filter($registreRow["DenominationSociale"])         : "";
		$Sigle                  = (isset($registreRow["Sigle"]              ))?$stringFilter->filter($registreRow["Sigle"])                       : "";
		$Regime                 = (isset($registreRow["Regime"]             ))?$stringFilter->filter($registreRow["Regime"])                      : "";
		$NbActions              = (isset($registreRow["NombreActions"]) && !is_null($registreRow["NombreActions"]))?$registreRow["NombreActions"] : 0;
		$DontNumeraire          = (isset($registreRow["DontNumeraire"]) && $registreRow["DontNumeraire"] != "NULL")?$registreRow["DontNumeraire"] : 0;
		$DontNature             = (isset($registreRow["DontNature"])    && $registreRow["DontNature"]    != "NULL")?$registreRow["DontNature"]    : 0; 
		$Capital                = (isset($registreRow["Capital"])       && $registreRow["Capital"]       != "NULL")?$registreRow["Capital"]       : 0;  
		$ifuBNC                 = (isset($registreRow["BNC"])           && $registreRow["BNC"]           != "NULL")?$registreRow["BNC"]           : "";
		$ifuTVA                 = (isset($registreRow["TVA"])           && $registreRow["TVA"]           != "NULL")?$registreRow["TVA"]           : "";
		$ifuBICA                = (isset($registreRow["BICA"])          && $registreRow["BICA"]          != "NULL")?$registreRow["BICA"]          : "";
		$codeEntreprise         =  new Zend_Db_Expr("CAST(E.IdEntreprise AS VARCHAR(60))");
							 
		 $zendDate              = $zendDateCNSS = $zendDateIFU = $zendDateImmIFU = null;
		 $rccmIdIFU             = 0;
		 $rccmIdCNSS            = 0;
		 $rccmIdCommune         = 0;
		 $rccmIdAddress         = 0;
		 $rccmIdStatus          = 0;
		 $rccmIdLocalite        = 0;
		 $rccmTypeId            = 3;
		 $rccmFormId            = 0;
		 $entrepriseGroupId     = 0;
		 $rccmDomaineId         = 0;
		 $registreid            = 0;
		 $registreObj           = $entrepriseObj = $entrepriseAddressObj = null;
		 $registreid            = false;
		 
		 if( empty($NumeroRCCM) ) {
			 return false;
		 }
		 if( $foundRegistre = $modelRegistre->findRow($NumeroRCCM, "numero", null, false)) {
			 return false;
		 }			 
		 if( is_a($DateRCCM, "DateTime")) {
			$DateRCCM        = $DateRCCM->format(Zend_Date::ISO_8601);
		 }
		 if( is_a($DateCNSS, "DateTime")) {
			$DateCNSS        = $DateCNSS->format(Zend_Date::ISO_8601);
		 }
		 if( is_a($DateIFU, "DateTime")) {
			$DateIFU         = $DateIFU->format(Zend_Date::ISO_8601);
		 }
		 if( is_a($DateImmIFU, "DateTime")) {
			$DateImmIFU      = $DateImmIFU->format(Zend_Date::ISO_8601);
		 }
		 if( Zend_Date::isDate( $DateRCCM,"dd/MM/YYYY")) {
			 $zendDate       = new Zend_Date($DateRCCM, Zend_Date::DATES ,"fr_FR");								 
		 } elseif( Zend_Date::isDate(        $DateRCCM,"YYYY-MM-dd") ) {
			  $zendDate      = new Zend_Date($DateRCCM,"YYYY-MM-dd");
		 } elseif( Zend_Date::isDate(        $DateRCCM, Zend_Date::ISO_8601)) {
			  $zendDate      = new Zend_Date($DateRCCM, Zend_Date::ISO_8601);
		 } else {
			  $zendDate      = null;
		 }
		 if( Zend_Date::isDate( $DateCNSS,"dd/MM/YYYY")) {
			 $zendDateCNSS   = new Zend_Date($DateCNSS, Zend_Date::DATES ,"fr_FR");								 
		 } elseif( Zend_Date::isDate(        $DateCNSS,"YYYY-MM-dd") ) {
			  $zendDateCNSS  = new Zend_Date($DateCNSS,"YYYY-MM-dd");
		 } elseif( Zend_Date::isDate(        $DateCNSS, Zend_Date::ISO_8601) ) {
			  $zendDateCNSS  = new Zend_Date($DateCNSS, Zend_Date::ISO_8601);
		 } else {
			  $zendDateCNSS  = null;
		 }					 
		 if( Zend_Date::isDate( $DateIFU,"dd/MM/YYYY")) {
			 $zendDateIFU    = new Zend_Date($DateIFU, Zend_Date::DATES ,"fr_FR");								 
		 } elseif( Zend_Date::isDate(        $DateIFU,"YYYY-MM-dd") ) {
			  $zendDateIFU   = new Zend_Date($DateIFU,"YYYY-MM-dd");
		 } elseif( Zend_Date::isDate(        $DateIFU, Zend_Date::ISO_8601) ) {
			  $zendDateIFU   = new Zend_Date($DateIFU, Zend_Date::ISO_8601);
		 } else {
			  $zendDateIFU   = null;
		 }
		 if( Zend_Date::isDate( $DateImmIFU,"dd/MM/YYYY")) {
			 $zendDateImmIFU = new Zend_Date($DateImmIFU, Zend_Date::DATES ,"fr_FR");								 
		 } elseif( Zend_Date::isDate(        $DateImmIFU,"YYYY-MM-dd") ) {
			  $zendDateImmIFU= new Zend_Date($DateImmIFU,"YYYY-MM-dd");
		 } elseif( Zend_Date::isDate(        $DateImmIFU, Zend_Date::ISO_8601) ) {
			  $zendDateImmIFU= new Zend_Date($DateImmIFU, Zend_Date::ISO_8601);
		 } else {
			  $zendDateImmIFU= null;
		 }
		 if( null == $zendDate ) {
			 return false;
		 }
		 if( empty($NomCommercial) ) {
			 $NomCommercial  = (!empty($Sigle))? sprintf("%s %s", $DenominationSociale, $Sigle) :  $DenominationSociale;
		 }
		 $DateDemande        = ( null!== $zendDate ) ? $zendDate->toString("dd/MM/YYYY") : "";
		 if(($NomCommercial=="NULL") || !$strNotEmptyValidator->isValid($NomCommercial) ) {
			 $errorMessages[]= sprintf("Le nom commercial du numéro RCCM N° %s de SIGUE est invalide",$NumeroRCCM, $DateDemande);
			 return false;
		 }	elseif($foundRegistreByLib = $modelRegistre->findRow($NomCommercial, "libelle", null, false)) {
			 $NomCommercial  = (!empty($Sigle))?sprintf(" %s (%s)", $NomCommercial, $Sigle ) : $NomCommercial;
		 }						 
		 if( substr($NumeroRCCM,0, 2) !== "BF") {
			 $NumeroRCCM     = sprintf("BF%s", $NumeroRCCM);
		 }				
         $numeroParts        = $modelRegistre->getNumParts($NumeroRCCM);					 
		 $numLocalite        = (isset($numeroParts["localite"]))? $numeroParts["localite"] : ((strlen($NumeroRCCM)<16)?trim(substr($NumeroRCCM,2,3)) : trim(substr($NumeroRCCM,3,3)));
		 $numYear            = (isset($numeroParts["annee"]   ))? $numeroParts["annee"]    : ((strlen($NumeroRCCM)<16)?trim(substr($NumeroRCCM,5,4)) : trim(substr($NumeroRCCM,9,4)));
		 $numTypeRegistre    = (isset($numeroParts["typeRCCM"]))? $numeroParts["typeRCCM"] : ((strlen($NumeroRCCM)<16)?trim(substr($NumeroRCCM,9,1)) : trim(substr($NumeroRCCM,14,1)));							 
		 $NumeroRCCM         = $registreRow["NumeroRCCM"] = $modelRegistre->normalizeNum($NumeroRCCM);	
 
		 if( false==$modelRegistre->checkNum($NumeroRCCM)) {
			 return false;
		 }					 
		 if(substr($numTypeRegistre,0,1)=="A") {
			 $rccmTypeId     = 1;
		 } elseif(substr($numTypeRegistre,0,1)=="B") {
			 $rccmTypeId     = 2;
		 } else  {
			 return false;
		 }		
		 if( $numTypeRegistre=="M") {
			 return false;
		 }
		  //On vérifie si le secteur d'activité existe déjà sinon on créée
		 if( $foundCodeActivite                  = $modelDomaine->findRow($registreRow["CodeActivite"],"code", null, false)) {
			 $rccmDomaineId                      = $foundCodeActivite->domaineid;  
		 } elseif($foundLibActivite              = $modelDomaine->findRow($registreRow["NomActivite"] ,"libelle", null, false)) { 
			 $rccmDomaineId                      = $foundLibActivite->domaineid;  
			 
		 } else {
			 $insert_domaine_data                = array("creatorid"=>$creatorid,"creationdate"=>time(),"updatedate"=>0,"updateduserid"=>0,"parentid"=>0);
			 $insert_domaine_data["code"]        = $stringFilter->filter($registreRow["CodeActivite"]);
			 $insert_domaine_data["libelle"]     = $stringFilter->filter($registreRow["NomActivite"]);
			 $insert_domaine_data["description"] = $stringFilter->filter($registreRow["SecteurActivite"]);
			 try {
				if( $dbAdapter->insert($tablePrefix."rccm_domaines", $insert_domaine_data)) {
					$rccmDomaineId            = $dbAdapter->lastInsertId();
				}
			 } catch(Exception $e ) {
				 $errorMessage                = sprintf("L'enregistrement du secteur d'activité du numéro RCCM %s a echoué pour les raisons suivantes : %s ", $NumeroRCCM, $e->getMessage());
				 $this->setError( $errorMessage );
				 return false;
			 }					 
		 }
		 //On vérifie si la forme juridique existe déjà
		 if( $foundFormeJuridique             = $modelFormeJuridique->findRow($registreRow["IdFormeJuridique"],"code", null, false)) {
			 $rccmFormId                      = $foundFormeJuridique->formid;  
		 }  else {
			 $insert_forme_data               = array("creatorid"=>$creatorid,"creationdate"=>time(),"updatedate"=>0,"updateduserid"=>0);
			 $insert_forme_data["code"]       = $stringFilter->filter($registreRow["IdFormeJuridique"]);
			 $insert_forme_data["libelle"]    = $stringFilter->filter($registreRow["forme"]);
			 if( $dbAdapter->insert($tablePrefix."rccm_registre_entreprises_forme_juridique", $insert_forme_data)) {
				 $rccmFormId                  = $dbAdapter->lastInsertId();
			 }
		 }
		 if( $localiteRow = $modelLocalite->findRow($numLocalite, "code", null, false )) {
			 $rccmIdLocalite                  = $localiteRow->localiteid;
		 }
		 $rccm_data                           = array("numero"=>$NumeroRCCM,"localiteid"=>$rccmIdLocalite,"annee"=>$numYear,"ifuid"=>$rccmIdIFU,"cnssid"=>$rccmIdCNSS,"numifu"=>$NumeroIFU,"numcnss"=>$NumeroCNSS,"libelle"=>$NomCommercial,"description"=>$Description,"statut"=>0,"statusid"=>0,"domaineid"=>$rccmDomaineId,"type"=> $rccmTypeId,"nbactions"=>$NbActions,"capital"=>$Capital,"capital_numeraire"=>$DontNumeraire,"capital_nature"=>$DontNature);
		 $rccm_data["date"]	                  = ( $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
		 $defaultRCCMData                     = $modelRegistre->getEmptyData();
		 $insert_rccm_data                    = array_merge( $defaultRCCMData, $rccm_data );
		 $insert_rccm_data["parentid"]        = $insert_rccm_data["communeid"] = $insert_rccm_data["addressid"] = $insert_rccm_data["updateduserid"] = $insert_rccm_data["updatedate"] = $insert_rccm_data["cpcid"] = 0;
		 $insert_rccm_data["params"]          = "";	
		 $insert_rccm_data["telephone"]       = $registreRow["TelMobile1"];
		 $insert_rccm_data["adresse"]         = $registreRow["Adresse"];
		 $insert_rccm_data["checked"]         = 1;
		 $insert_rccm_data["category"]        = ( $rccmTypeId==1) ?"P0" : "M0";
		 $insert_rccm_data["creatorid"]       = $creatorid;
		 $insert_rccm_data["creationdate"]    = time();						
		try {
			if( $dbAdapter->insert($tablePrefix."rccm_registre", $insert_rccm_data)) {
				$registreid                   = $dbAdapter->lastInsertId();
                $entrepriseid                 = 0;
                $NbEmployes                   = (isset($registreRow["EffectifEmployePermanent"])   && $registreRow["EffectifEmployePermanent"]  !="NULL")?$registreRow["EffectifEmployePermanent"] : 0;					 
				$ChiffreAffaire               = (isset($registreRow["ChiffreAffairePrevisionnel"]) && $registreRow["ChiffreAffairePrevisionnel"]!="NULL")?$registreRow["ChiffreAffairePrevisionnel"] : 0;
				$entreprise_data              = array("registreid"=>$registreid,"pagekey"=>$registreRow["codeEntreprise"],"reference"=>$registreRow["codeEntreprise"],"num_securite_social"=>$NumeroCNSS,"num_rc"=>$NumeroRCCM,"num_ifu"=>$NumeroIFU,"libelle"=>$NomCommercial,"datecreation"=>$rccm_data["date"],"capital"=>$Capital,"chiffre_affaire"=>$ChiffreAffaire,"nbemployes_min"=>1,"nbemployes_max"=>$NbEmployes,"country"=>"BF","presentation"=>$Description,"domaineid"=>$rccmDomaineId,"formid"=>$rccmFormId,"groupid"=>1);
				if(!$entrepriseObjFound       = $modelEntreprise->findRow($registreRow["codeEntreprise"], "pagekey", null, false))	{
					$entrepriseObjFound       = $modelEntreprise->findRow($NumeroRCCM, "num_rc", null, false)	;						 
				} 	
                if( isset($entrepriseObjFound->entrepriseid) )	{
					$entrepriseid             = $entrepriseObjFound->entrepriseid;
					try {
						$entrepriseObj        = $entrepriseObjFound;
						$entrepriseObjFound->setFromArray($entreprise_data);
						$entrepriseObjFound->save();
					} catch( Exception $e ) {
						$errorMessage = sprintf("L'enregistrement ou la mise à jour du RCCM N° %s a echoué pour les raisons suivantes : %s.", $NumeroRCCM, $e->getMessage());
						$this->setError( $errorMessage );
						if( $registreid ) {
							$dbAdapter->delete($tablePrefix."rccm_registre", array("registreid=?"=> $registreid ));
						}
						return false;
					}
				} else {
					$entreprise_data                  = array_merge(array("city"=>"0","responsable"=>"","responsableid"=> "","responsable_email" => "","siteweb"=> "","fax"=>"","zip"=>"","phone1"=> "","phone2"=>"","email"=> "","address"=> "","logo"=>""), $entreprise_data);
					$entreprise_data["creatorid"]     = $creatorid; 
					$entreprise_data["creationdate"]  = time(); 
					$entreprise_data["updateduserid"] = $entreprise_data["updatedate"] = 0;
					try {
						if( $dbAdapter->insert($tablePrefix."rccm_registre_entreprises", $entreprise_data) ) {
							$entrepriseid             = $dbAdapter->lastInsertId();
							$entrepriseObj            = $modelEntreprise->findRow( $entrepriseid, "entrepriseid", null, false );							
						}
					} catch(Exception $e) {
						$errorMessage                 = sprintf("L'enregistrement ou la mise à jour du RCCM N° %s a echoué pour les raisons suivantes : %s.", $NumeroRCCM, $e->getMessage());
						$this->setError( $errorMessage );
						if( $registreid ) {
							$dbAdapter->delete($tablePrefix."rccm_registre", array("registreid=?"=> $registreid ));
						}						
						return false;
					}							
				}	
                if( $entrepriseid) {
					$promoteurDefaultData             = $modelRepresentant->getEmptyData();
					$promoteur_data                   = $promoteurDefaultData;
					$promoteur_data["datenaissance"]  = ( isset( $registreRow["datenaissance"] ))? $registreRow["datenaissance"] : "";
					$promoteur_data["lieunaissance"]  = $stringFilter->filter($registreRow["LieuNaissance"]  );
					$promoteur_data["marital_status"] = $stringFilter->filter($registreRow["SituationMatrimoniale"] );
					$promoteur_data["nom"]            = (isset($registreRow["Nom"])    && !empty($registreRow["Nom"]   ))? $registreRow["Nom"]                   : $stringFilter->filter($registreRow["Surnom"]);
					$promoteur_data["prenom"]         = (isset($registreRow["Prenom"]) && !empty($registreRow["Prenom"]))? sprintf("%s", $registreRow["Prenom"]) : $stringFilter->filter($registreRow["Surnom"]);
					$promoteur_data["adresse"]        = sprintf("%s,Code Postal:%s,Quartier:%s,Porte: %s", $stringFilter->filter( $registreRow["Adresse"]), $stringFilter->filter( $registreRow["CodePostal"]), $stringFilter->filter( $registreRow["Quartier"]), $stringFilter->filter( $registreRow["Porte"]));
					$promoteur_data["city"]           = 0;
					$promoteur_data["profession"]     = $stringFilter->filter($registreRow["Fonction"]);
					$promoteur_data["country"]        = $promoteurNationalite = $stringFilter->filter( $registreRow["Nationalite"]);
					$promoteur_data["email"]          = $stringFilter->filter(  $registreRow["Email"]);
					$promoteur_data["telephone"]      = implode("/", array($stringFilter->filter($registreRow["Mobile1"]), $stringFilter->filter($registreRow["Tel1Domicile"]), $stringFilter->filter( $registreRow["Mobile2"]), $stringFilter->filter( $registreRow["Tel2Domicile"]) ));
					$promoteur_data["passport"]       = (isset($registreRow["passport"]))? $stringFilter->filter($registreRow["passport"]) : "";
					$promoteur_data["cnib"]           = (isset($registreRow["cnib"]    ))? $stringFilter->filter($registreRow["cnib"] )    : "";
					$promoteur_data["sexe"]           = (isset($registreRow["Sexe"]    ))? $stringFilter->filter($registreRow["Sexe"] )    : $registreRow["Gender"];
					$promoteur_data["structure"]      = "";
					$promoteur_data["creatorid"]      = $creatorid;
					$promoteur_data["creationdate"]   = time();
					$promoteur_data["updateduserid"]  = 0;
					$promoteur_data["updatedate"]     = 0;
					  
					if( $promoteur_data["sexe"] =="Masculin" || $promoteur_data["sexe"] =="Homme" || $promoteur_data["sexe"] == "2") {
						$promoteur_data["sexe"]      = "M";
					} else {
						$promoteur_data["sexe"]      = "F";
					}
					if(!isset($countries[$promoteurNationalite] )) {
						if( $countryFound            = Sirah_Functions_ArrayHelper::search($countries, $promoteurNationalite)) {
							$promoteurNationalite    = $promoteur_data["country"] = key($countryFound); 
						}
					}
					  //On vérifie que le promoteur n'est pas déjà enregistré
					$foundPromoteurRow               = $modelRegistre->getList(array("nom"=>$promoteur_data["nom"],"prenom"=>$promoteur_data["prenom"],"registreid"=>$registreid));
					$foundPromoteurRowPASSPORT       = $modelRegistre->getList(array("passport"=>$promoteur_data["passport"],"registreid"=>$registreid));
					 
					if( isset($foundPromoteurRow[0]["registreid"]) || isset($foundPromoteurRowPASSPORT[0]["registreid"]) ) {
						return $registreid;
					}
					try {
						if($dbAdapter->insert($tablePrefix."rccm_registre_representants", $promoteur_data )) {
						   $representantid                   = $dbAdapter->lastInsertId();
						   $promoteur_data["representantid"] = $representantid;
						   $dirigeantData                    = array("registreid"=>$registreid,"representantid"=>$representantid,"fonction"=>$promoteur_data["profession"],"entrepriseid"=>$entrepriseid);
						   $dbAdapter->insert($tablePrefix."rccm_registre_dirigeants", $dirigeantData);
						}
					} catch( Exception $e ) {
						  $errorMessage   = sprintf("Une erreur s'est produite dans la création du promoteur du RCCM N° %s : %s", $NumeroRCCM, $e->getMessage());
						  $this->setError( $errorMessage );
						  if( $registreid ) {
							  $dbAdapter->delete($tablePrefix."rccm_registre"            , array("registreid=?"=> $registreid ));
							  $dbAdapter->delete($tablePrefix."rccm_registre_entreprises", array("registreid=?"=> $registreid ));
							  $dbAdapter->delete($tablePrefix."rccm_registre_dirigeants" , array("registreid=?"=> $registreid ));
						  }
						  return false;
					}
				}				
			}
		} catch(Exception $e ) {
			$errorMessage                     = sprintf("L'enregistrement ou la mise à jour du RCCM N° %s a echoué pour les raisons suivantes : %s.", $NumeroRCCM, $e->getMessage());
			$this->setError( $errorMessage );
			return false;
		}
		
		return $registreid;
	}
	
	public function apiodsearch($filters = array())
	{
		$foundRows     = array();
		
		return $foundRows;
	}
	
	public function siguesearch( $filters = array(), $page=0, $pageSize=10, $useAPI = false)
	{
		ini_set('memory_limit', '-1');
		$foundRows     = array();
		if( $useAPI == false ) {
			$appConfigSession = new Zend_Session_Namespace("AppConfig");
			$databases = ( isset($appConfigSession->resources["sigue.databases"]))?$appConfigSession->resources["sigue.databases"] : array();
			$selectSQL = "SELECT [ENT].[No_] as [codeEntreprise], [ENT].[No_] as [IdEntreprise], 
							     [ENT].[Source Request No_] as [NumeroDemande], [ENT].[Commercial Name] as [libelle], [ENT].[Commercial Name] as [NomCommercial], 
							     [ENT].[DonominationSocial] as [DenominationSociale],[ENT].[Sigle],[ENT].[Company Type] as [TypeEntreprise], 
							     [ENT].[TypeEntreprise] as [TypeEntrepriseId], [ENT].[StatusRCCM], [ENT].[StatusIFU], [ENT].[StatusCNSS], 
							     [ENT].[RCCM] as [NumeroRCCM], [ENT].[RCCM] as [Numero], [ENT].[IFU] as [NumeroIFU], [ENT].[CNSS] as [NumeroCNSS], 
							     [ENT].[Created Date] as [DateDemande], [ENT].[DateValidationRCCM] as [DateRCCM], [ENT].[DateValidationIFU] as [DateImmIFU], 
							     [ENT].[DateValidationIFU] as [DateIFU],[ENT].[DateValidationCNSS] as [DateCNSS], [ENT].[Employee Temporary] as [EffectifEmployeTemporaire], 
							     [ENT].[Employee Permanat] as [EffectifEmployePermanent],[ENT].[Employee Etranger] as [EffectifEmployeEtranger], 
							     [ENT].[CapitalSocial] as [DontNature], [ENT].[capitalEnIndustrie], [ENT].[CapitalEnNature], [ENT].[CapitalEnNumeraire] as [DontNumeraire], 
							     [ENT].[CapitalEnNumeraire] as [Capital], [ENT].[NbreActions] as [NombreActions], [ENT].[MontantAction], 
							     [ENT].[OpionsTVA] as [TVA], [ENT].[Taxation Regime] as [Regime], [ENT].[FormeJuridique] as [IdFormeJuridique], 
							     [FRM].[Libelle] as [NomFormeJuridique], [FRM].[Code] as [codeForme], [ENT].[Primary Activity] as [IdActivite], 
							     [ENT].[Primary Activity] as [IdActivitePrincipale], [ENT].[Primary Activity] as [NomActivite], 
							     [ENT].[Activity Sector] as [ActiviteSecondaire], [ENT].[Primary Activity] as [codeActivite], [ACT].[Description] as [SecteurActivite], 
							     [ACT].[Description] as [libActivite], [ACT].[Description] as [descriptionActivite], [ACT].[Description] as [ObjetSocial], 
							     [ACT].[Code] as [CodeActivite], [ENT].[Pays], [ENT].[Adress], [ENT].[Adress] as [Adresse], [ENT].[Adress] as [AdresseProm], 
							     [ENT].[Quartier], [ENT].[Porte] as [NumeroPorte], [ENT].[Avenue], [ENT].[Rue], [ENT].[E-Mail] as [Email], [ENT].[Mobile 1] as [TelMobile1], 
							     [ENT].[Mobile 2] as [TelMobile2], [ENT].[Tel Domicile] as [TelBureau], [ENT].[Boite postale] as [BoitePostale], 
							     [ENT].[Province Code] as [Province], [ENT].[Arrondissement Code] as [Arrondissement], [U].[E-mail] as [EmailProm],
							     CONCAT(U.NomRaisonSociale,' ', U.NomJeuneFille,' ', U.Prenom,' ', U.Surnom) AS Name, [U].[Phone No_] as [TelephoneProm],[U].[Gender], 
							     [U].[Gender] as [Sexe], [U].[Surnom] as [Nom], [U].[Prenom], [U].[Surnom], [F].[Name] as [Fonction], [F].[Name] as [Profession], 
							     [U].[LieuNaissance],[U].[LieuNaissance] as lieunaissance, [U].[SituationMatrimoniale], [U].[DateNaissance],[U].[CIN] as [passport],[U].[CIN] as [cnib],[U].[Country Code] as [Nationalite], 
							     [TER].[NumeroSection], [TER].[Superficie], [TER].[NumeroLot], [TER].[NumeroParcelle], [TER].[Perimetre] 
						  FROM       [MEBF\$Entreprise] as [ENT] 
						  INNER JOIN [MEBF\$Usager] as [U] on [U].[No_] = [ENT].[Legal Representative] 
						  LEFT  JOIN [MEBF\$Usager Fonction] as [F] on [F].[Code] = [U].[IdFonction] 
						  LEFT  JOIN [MEBF\$Forme Juridique] as [FRM] on [FRM].[Code] = [ENT].[FormeJuridique] 
						  LEFT  JOIN [MEBF\$Company Activity] as [ACT] on [ACT].[Code] = [ENT].[Primary Activity] 
						  LEFT  JOIN [MEBF\$Terrain] as [TER] on [TER].[IdTerrain] = [ENT].[IdTerrain]  \n";
			$where       = array();
			if( isset($filters["numero"]) && !empty($filters["numero"]) ) {
				$where[] = "([ENT].[RCCM]=':numero')";
			}
			if( isset($filters["numifu"]) && !empty($filters["numifu"]) ) {
				$where[] = "([ENT].[IFU]=':numifu')";
			}
			if( isset($filters["numcnss"]) && !empty($filters["numcnss"]) ) {
				$where[] = "([ENT].[CNSS]=':numcnss')";
			}
			if( isset($filters["libelle"]) && !empty($filters["libelle"]) ) {
				$where[] = "([ENT].[Commercial Name] LIKE '%:libelle%' OR [ENT].[DonominationSocial] LIKE '%:libelle%' OR [ENT].[Sigle] LIKE '%:libelle%')";
			}	
			if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ) {
				$where[] = "([ENT].[Commercial Name] LIKE '%:searchQ%' OR [ENT].[DonominationSocial] LIKE '%:searchQ%' OR U.NomRaisonSociale LIKE '%:searchQ%' OR U.Prenom LIKE '%:searchQ%' OR [ENT].[RCCM] LIKE '%:searchQ%')";
			}
            if( isset($filters["name"]) && !empty($filters["name"]) ) {
				$where[] = "(U.Surnom LIKE '%:name%' OR U.Prenom LIKE '%:name%')";
			}
            if( isset($filters["nom"]) && !empty($filters["nom"]) ) {
				$where[] = "(U.Surnom LIKE '%:nom%')";
			}
            if( isset($filters["prenom"]) && !empty($filters["prenom"]) ) {
				$where[] = "(U.Prenom LIKE '%:prenom%')";
			}			
            if( isset($filters["description"]) && !empty($filters["description"]) ) {
				$where[] = "([ACT].[Description] LIKE '%:description%')";
			}		
            if( count( $where ) ) {
				$selectSQL.=" WHERE ".implode(" AND ", $where );
			}	
            if( $page && $pageSize ) {
				$offset    = $page*$pageSize;
				$selectSQL.=sprintf(" OFFSET %d ROWS FETCH NEXT %d ROWS ONLY ", $offset, $pageSize);
			}	
            //$selectSQL    .= " GROUP BY E.IdEntreprise,EU.IdEntreprise,R.NumeroRCCM, EU.IdUsager \n";			
            $selectSQL    .= " ORDER BY [ENT].[Commercial Name] ASC,[ENT].[DonominationSocial] ASC,U.Surnom ASC \n "	;	
			//echo $selectSQL; die();
            if( count(   $filters)) {
				foreach( $filters as $filterKey => $filterValue ) {
						 $paramKey              = sprintf(":%s", $filterKey );
						 $paramValue            = preg_replace("/[^A-Za-z0-9 ]/", "", $filterValue );
						 $parameters[$paramKey] = $paramValue;
						 $selectSQL             = str_replace($paramKey, $paramValue, $selectSQL);
						  //$dbStmt->bindParam($filterKey, $paramValue);									  								  
				}
			}		
 	
            if( count(   $databases) ) {
				foreach( $databases as $dbAdapter ) {
						 try {
							 $dbStmt        = $dbAdapter->query($selectSQL);
							 $dbStmt->execute($parameters);
							 $dbFoundRows   = $dbStmt->fetchAll(Zend_Db::FETCH_ASSOC);
						 } catch(Exception $e ) {
							 $errorMessage  = sprintf("Une erreur s'est produite dans la selection des données: %s",$e->getMessage());
						     $this->setError( $errorMessage );
						 }
						 if( count(   $dbFoundRows) ) {
							 foreach( $dbFoundRows as $foundKey=>$dbFoundRow ) {
								      if( isset($dbFoundRows[$foundKey]["DateNaissance"])) {
										  $zendDateNaissance     = null;
										  $DateNaissance         = $dbFoundRows[$foundKey]["DateNaissance"];
										  $DateNaissance         = $dbFoundRows[$foundKey]["datenaissance"] = (is_a($DateNaissance, "DateTime"))?$DateNaissance->format(Zend_Date::ISO_8601) : $DateNaissance;
										  if( Zend_Date::isDate( $DateNaissance,"dd/MM/YYYY")) {
											  $zendDateNaissance = new Zend_Date($DateNaissance, Zend_Date::DATES ,"fr_FR");								 
										  } elseif( Zend_Date::isDate(           $DateNaissance,"YYYY-MM-dd") ) {
											  $zendDateNaissance = new Zend_Date($DateNaissance,"YYYY-MM-dd");
										  } elseif( Zend_Date::isDate(           $DateNaissance, Zend_Date::ISO_8601)) {
											  $zendDateNaissance = new Zend_Date($DateNaissance, Zend_Date::ISO_8601);
										  } else {
											  $zendDateNaissance = null;
											  $DateNaissance     = $dbFoundRows[$foundKey]["datenaissance"] = "";
										  }
										  $dbFoundRows[$foundKey]["datenaissance"] = ($zendDateNaissance)?$zendDateNaissance->get('YYYY-MM-dd HH:mm:ss') : "";
									  }
							 }
						 }
						 
						 if( count($dbFoundRows) ) {
							 $foundRows = array_merge($foundRows,$dbFoundRows);
						 }						 
				}
			}			
		}		
		return $foundRows;
	}
	
	
	public function siguemissings( $filters = array())
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");
		
		$selectRegistres = $dbAdapter->select()->from(array("R" => $tablePrefix ."sigue_registres_missings" ));

		if( isset( $filters["annee"] ) && intval($filters["annee"]) ) {
			$selectRegistres->where("R.annee = ?", intval($filters["annee"]));
		}
		if( isset( $filters["localiteid"] ) && intval($filters["localiteid"]) ) {
			$selectRegistres->where("R.localiteid = ?", intval($filters["localiteid"]));
		}
		if( isset( $filters["localite"] ) && (null !== $filters["localite"] ) ) {
			$selectRegistres->where("R.localite = ?", strip_tags($filters["localite"]));
		}
		if( isset( $filters["documentkey"] ) && (null !== $filters["documentkey"] ) ) {
			$selectRegistres->where("R.documentkey = ?", strip_tags($filters["documentkey"]));
		}
		if( isset( $filters["siguenum"] ) && (null !== $filters["siguenum"] ) ) {
			$selectRegistres->where("R.siguenum = ?", strip_tags($filters["siguenum"]));
		}
		if( isset( $filters["numero"] ) && (null !== $filters["numero"] ) ) {
			$selectRegistres->where("R.numero   = ?", strip_tags($filters["numero"]));
		}
		if( isset( $filters["found"] ) && (null !== $filters["found"] ) ) {
			$selectRegistres->where("R.found    = ?", intval($filters["found"]));
		}
		return $dbAdapter->fetchAll($selectRegistres, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function findsiguemissingsdocs($registreid)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");		
		$selectDocuments = $dbAdapter->select()->from(array("RD"=> $tablePrefix ."sigue_registre_missings_documents"))
		                                       ->where("RD.registreid=?", intval($registreid))
											   ->order(array("RD.registreid ASC"));
	 
		return $dbAdapter->fetchAll( $selectDocuments, array(), Zend_Db::FETCH_ASSOC );
	}
	
	
	
	public function rheawebModifications($parentnum = null ) 
	{
		if( null === $parentnum ) {
			$parentnum       = $this->numero;
		}
		$table               = $this->getTable();
		$dbAdapter           = $table->getAdapter();
		$tablePrefix         = $table->info("namePrefix");		
		$selectModifications = $dbAdapter->select()->from(array("RM"=> $tablePrefix."rccm_registre_indexation"), array("RM.numero","RM.numparent","RM.nom","RM.prenom","RM.nom_commercial","RM.date_enregistrement","RM.date_naissance","RM.lieu_naissance","RM.adresse","RM.description","RM.telephone","RM.sexe","RM.nationalite","RM.passport","RM.situation_matrimonial"))->where("RM.numparent = ?", $parentnum);
		return $dbAdapter->fetchAll( $selectModifications ,array(), Zend_Db::FETCH_ASSOC);		
	}
	
		
	public function findsiguerc( $numRccm )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");		
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."sigue_registre" ))->where("R.numero=?" , strip_tags($numRccm));
		
		return $dbAdapter->fetchRow( $selectRegistre,array(), Zend_Db::FETCH_OBJ);
	}
	
	public function findsiguercdocs( $numRccm, $documentKey = null, $numdoctype = null )
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");		
		$selectDocuments = $dbAdapter->select()->from(array("R" => $tablePrefix ."sigue_registre" ))
		                                       ->join(array("RD"=> $tablePrefix ."sigue_registre_documents"),"RD.registreid=R.registreid" , array("RD.registreid","RD.documentid","RD.numdoc","RD.doctype","RD.documentkey","RD.documentspath"))
											   ->join(array("D" => $tablePrefix ."system_users_documents")  ,"D.documentid =RD.documentid", array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
			                                                                                                                                      "D.filedescription","D.filesize","D.documentid","D.resourceid", "D.userid"))
											   ->where(  "R.cleanum=?", strip_tags($numRccm))
			                                   ->orWhere("R.numero=?" , strip_tags($numRccm));
		if( null != $documentKey ) {
			$selectDocuments->where("RD.documentkey=?", strip_tags($documentKey));
		}
		if( null != $numdoctype ) {
			$selectDocuments->where("RD.doctype LIKE \"%".strip_tags($numdoctype)."%\"");
		}
		return $dbAdapter->fetchAll( $selectDocuments, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function findsiguercpairdocs( $numRccm, $documentKey = null)
	{
		$table           = $this->getTable();
		$dbAdapter       = $table->getAdapter();
		$tablePrefix     = $table->info("namePrefix");		
		$selectDocuments = $dbAdapter->select()->from(array("R" => $tablePrefix ."sigue_registre"), array())
		                                       ->join(array("RD"=> $tablePrefix ."sigue_registre_documents"),"RD.registreid=R.registreid" , array("RD.documentkey","RD.numrccm")) ;
		if( null != $numRccm ) {
			$selectDocuments->where(  "R.cleanum=?", strip_tags($numRccm))
			                ->orWhere("R.numero =?", strip_tags($numRccm));
		}
		if( null != $documentKey ) {
			$selectDocuments->where("RD.documentkey=?", strip_tags($documentKey));
		}
		return $dbAdapter->fetchPairs( $selectDocuments, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function findsiguercmms( $filters = array() )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");		
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."sigue_registre" ));
		
		if( isset( $filters["numrccm"] ) ) {
			$selectRegistre->where("R.cleanum=?", strip_tags($filters["numrccm"]))->orWhere("R.numero=?", strip_tags($filters["numrccm"]));
		}
		if( isset( $filters["nomcommercial"] ) ) {
			$selectRegistre->where(  "REPLACE(TRIM(R.nomcommercial),' ','') LIKE ?","%".$filters["nomcommercial"]."%");
		}
		if( isset( $filters["nompromoteur"] ) ) {
			$selectRegistre->where("REPLACE(TRIM(R.nompromoteur),' ','') LIKE ?","%".$filters["nompromoteur"]."%");
		}
		if( isset( $filters["mois"] )) {
			$selectRegistre->where("FROM_UNIXTIME(R.date,'%m') = ? ",$filters["mois"]);
		}
		if( isset( $filters["day"] )) {
			$selectRegistre->where("FROM_UNIXTIME(R.date,'%d') = ? ",$filters["day"]);
		}
		if( isset( $filters["annee"] ) && intval($filters["annee"])) {
			$selectRegistre->where("R.annee=?", intval($filters["annee"]));
		}
		if( isset( $filters["datedemande"] ) ) {
			$selectRegistre->where("R.datedemande=?", $filters["datedemande"] );
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			$selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d') = ? ",  $filters["date"]);
		}
		return $dbAdapter->fetchAll( $selectRegistre, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function getNumParts($numRccm)
	{
		$numRccm  = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($numRccm)));
		$parts    = array();
		if( empty( $numRccm )) {
			return $parts;
		}
		if( strlen($numRccm)>16) {
			$pattern = "/BF\-(?<localite>[a-zA-Z])\-(<codeJuridiction>[0-2]{2})\-(?<annee>[0-9]{4})\-(?<typeRCCM>(?:A|B|C|D|E|G|K|M|R|S|T)[0-9]{1,2})\-?(?<numero>[0-9]{4,5})$/i";		    
		} else {
			$pattern = "/BF\-?(?<localite>[a-zA-Z]{3})\-?(?<annee>[0-9]{4})\-?(?<typeRCCM>(?:A|B|C|D|E|G|K|M|R|S|T)[0-9]{0,1})\-?(?<numero>[0-9]{4,5})$/i";
		}		
		if(!preg_match($pattern,$numRccm, $matches) ) {
			$numRccmSplit = preg_split("/[-\s:]+/",$numRccm);
			if( isset($numRccmSplit[1])) {
				$matches["localite"]        = $numRccmSplit[1];
			}
			if( strlen($numRccm)>16) {
				$matches["codeJuridiction"] = $numRccmSplit[2];
				$matches["annee"]           = (isset($numRccmSplit[3]))?$numRccmSplit[3] : "";
				$matches["typeRCCM"]        = (isset($numRccmSplit[4]))?$numRccmSplit[4] : "";
				$matches["numero"]          = (isset($numRccmSplit[5]))?$numRccmSplit[5] : "";
			} else {
				$matches["annee"]           = $numRccmSplit[2];
				$matches["typeRCCM"]        = (isset($numRccmSplit[3]))?$numRccmSplit[3] : "";
				$matches["numero"]          = (isset($numRccmSplit[4]))?$numRccmSplit[4] : "";
			}
		} else {
			    $matches["localite"]        = trim(  substr($numRccm,2,3));
			    $matches["annee"]           = intval(substr($numRccm,5,4));
				$matches["typeRCCM"]        = trim(  substr($numRccm,9,1));
				$matches["numero"]          = trim(  substr($numRccm,10 ));
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
			$pattern = "/BF\-[a-zA-Z]{2,3}\-[0-2]{2}\-[0-9]{4}\-(?:A|B|C|D|E|G|K|M|R|S|T)[0-9]{2}\-[0-9]{4,5}$/i";
		} else {
			$pattern = "/BF\-?[a-zA-Z]{2,3}\-?[0-9]{4}\-?(?:A|B|C|D|E|G|K|M|R|S|T)[0-9]{0,1}\-?[0-9]{4,5}$/i";
		}
 
		if(!preg_match($pattern,$numRccm, $match) ) {
			return false;
		}
		return true;
	}
	
	public function likeNum($string)
	{
		$string  = preg_replace("/\s+/"," ",str_ireplace(array(" -"," - "),"-",trim($string)));
		 
		$pattern = "/BF\-?[a-zA-Z]{2,3}\-?[0-9]{0,2}\-?[0-9]{0,4}\-?[a-z0-9]{0,3}\-?[0-9]{0,5}$/i";
 
		if(!preg_match($pattern,$string, $match) ) {
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
		$rccmNormalTypes       = array("A","B","C","D","E","G","K","M","R","S","T");
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
	
	public function getTotal($filters=array())
	{
        $table       = $this->getTable();
		$dbAdapter   = $table->getAdapter();
		$tablePrefix = $table->info("namePrefix");		
		$selectTotal = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("total" => "COUNT(R.registreid)"))
		                                   ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"));
		
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectTotal->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectTotal->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectTotal->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectTotal->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectTotal->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectTotal->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		return $dbAdapter->fetchOne($selectTotal);
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
											       ->join(array("MT"=> $tablePrefix."rccm_registre_modifications_type"), "MT.type=RM.type", array("type" => "MT.libelle"))
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
											 ->group(array("R.parentid","R.registreid"))->order(array("RS.registreid DESC"));								 
		return $dbAdapter->fetchAll($selectSuretes,array(), Zend_Db::FETCH_ASSOC);		
	}
	
	public function representant( $registreid =null )
	{
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		
		$selectDirigeants  = $dbAdapter->select()->from(array("RE"=> $tablePrefix ."rccm_registre_representants"), array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.marital_status","RE.email","RE.telephone","RE.passport","RE.representantid","RE.country",
		                                                                                                                 "date_naissance_year"=>"YEAR(RE.datenaissance)","date_naissance_month"=> "MONTH(RE.datenaissance)","date_naissance_day"=>"DAYOFMONTH(RE.datenaissance)"))
		                                         ->join(array("D" => $tablePrefix ."rccm_registre_dirigeants")   , "D.representantid = RE.representantid",array("D.fonction"))
											     ->where("D.registreid = ?", intval($registreid))->order(array("RE.representantid ASC","RE.nom ASC","RE.prenom ASC"));
		return $dbAdapter->fetchRow($selectDirigeants, array(), Zend_Db::FETCH_OBJ);
	}
	
	public function dirigeants($registreid=null ) 
	{		
		if(!$registreid )  {
			$registreid    = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");		
		$selectDirigeants  = $dbAdapter->select()->from(array("RE"=>$tablePrefix."rccm_registre_representants"), array("RE.representantid","RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.marital_status","RE.email","RE.telephone","RE.cnib","RE.passport","RE.representantid","RE.sexe","RE.country","RE.city",
		                                                                                                               "date_naissance_year"=>"YEAR(RE.datenaissance)","date_naissance_month"=>"MONTH(RE.datenaissance)","date_naissance_day"=>"DAYOFMONTH(RE.datenaissance)","RE.creationdate","RE.creatorid"))
		                                         ->join(array("D" =>$tablePrefix."rccm_registre_dirigeants"),"D.representantid=RE.representantid",array("D.fonction","profession"=>"D.fonction","D.registreid"))
											     ->where("D.registreid=?", intval($registreid))
												 ->group(array("D.registreid","D.representantid","RE.representantid"))
												 ->order(array("RE.representantid ASC","RE.nom ASC", "RE.prenom ASC"));
		return $dbAdapter->fetchAll($selectDirigeants, array(), Zend_Db::FETCH_ASSOC);									  
	}
	
	public function enterprise($registreid=null)
	{
		if(!$registreid ) {
			$registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectEntreprise = $dbAdapter->select()->from(array("E"=> $tablePrefix."rccm_registre_entreprises"))->where("E.registreid=?",intval($registreid));
	    return $dbAdapter->fetchRow($selectEntreprise, array(),Zend_Db::FETCH_OBJ);
	}

	
	public function physiqueFromBfr( $str, $numRegistre )
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");
		$returnData    = array("nom_commercial" => "", "domaine" => "", "telephone" => "", "dirigeant" => "", "numero" => $numRegistre, "domaineid" => 0 );
		
		$strWithoutNum = trim(str_replace( $numRegistre, "", $str ));
		$searchDomaine = ( strlen($strWithoutNum) > 0 ) ? trim(substr( $strWithoutNum, -1*strlen($strWithoutNum), 10  )) : "";
		if( !empty( $searchDomaine )) {
			$searchDomaineSelect = $dbAdapter->select()->from( $tablePrefix ."rccm_domaines", array("domaineid","libelle"))->where("libelle LIKE ?", "%".strip_tags($searchDomaine)."%");
			$existantDomaine     = $dbAdapter->fetchRow( $searchDomaineSelect, array(), Zend_Db::FETCH_ASSOC );
		}		
		if( count( $existantDomaine )){
			$strWithoutNum            = trim(str_replace( $strWithoutNum, "", $existantDomaine["libelle"] ));
			$returnData["domaine"]    = $existantDomaine["libelle"];
			$returnData["domaineid"]  = $existantDomaine["domaineid"];
		}		
		$returnData["nom_commercial"] = $returnData["dirigeant"] = $strWithoutNum;		
		return $returnData;		
	}
	
	public function getNbreLocaliteAnnee( $localiteid, $annee, $creatorid =0,$filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$domaineids         = (isset($filters["domaineids"]))?$filters["domaineids"] : array();
		
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("nombre" => "COUNT(R.registreid)"))		                                       												
												  ->join(array("L" => $tablePrefix."rccm_localites"), "L.localiteid = R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",null)
												  ->where("R.localiteid=?", intval( $localiteid ))											  
												  ->where("R.annee= ?", intval( $annee));
		if( intval($creatorid) ) {
			$selectStatistiques->where("R.creatorid=?", intval($creatorid));
		}	
        if( is_array($domaineids) && count($domaineids)) {
			$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$domaineids));
		}		
        if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}	
       //print_r($selectStatistiques->__toString());die();		
		$selectStatistiques->group(array("R.localiteid","R.annee"))->order(array("R.localiteid ASC", "R.annee ASC"));
		return $dbAdapter->fetchOne( $selectStatistiques);
	}
	
	public function getNbreBySexe( $localiteid , $sexe )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("nombre" => "COUNT(DISTINCT(R.registreid))", "R.type", "R.localiteid"))
		                                          ->join(array("RP"=> $tablePrefix."rccm_registre_physique"), "RP.registreid = R.registreid", null)
		                                          ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",array("RE.sexe"))
		                                          ->where("R.localiteid = ?", intval( $localiteid ))->where("E.sexe = ?", $sexe );
		if( is_array($domaineids) && count($domaineids)) {
			$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$domaineids));
		}
		$selectStatistiques->group(array("R.localiteid","E.sexe"))->order(array("E.sexe ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getNbreByLocalite( $localiteid, $annee, $type )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"), array("nombre" => "COUNT(R.registreid)","R.type","annee" => "FROM_UNIXTIME(R.date,'%Y')","R.localiteid"))		                                          
		                                          ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",array("RE.sexe"))
												  ->join(array("L" => $tablePrefix."rccm_localites"), "L.localiteid = R.localiteid", array("localite" => "L.libelle"))
												  ->where("R.localiteid= ?",$localiteid)
												  ->where("R.type= ?",$type)
												  ->where("R.annee=?",$annee);
		 
		$selectStatistiques->group(array("R.localiteid","R.annee"))->order(array("R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getNbreByDomaine( $domaineid, $annee, $type )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("nombre"=>"COUNT(DISTINCT(R.registreid))","R.type","annee" => "FROM_UNIXTIME(R.date,'%Y')"))
		                                          ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("L" => $tablePrefix."rccm_localites"      ), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
												  ->where("R.domaineid = ?", intval($domaineid ))
												  ->where("R.type=?", intval( $type ))
		                                          ->where("R.annee=?", intval( $annee));
		$selectStatistiques->group(array("R.domaineid","R.type","R.annee"))->order(array("R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getNbreByUsers( $userid = 0, $role = null, $period_start = 0, $period_end = 0)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"       ), array("nombre"=>"COUNT(DISTINCT(R.registreid))","R.type","annee"=> "FROM_UNIXTIME(R.date,'%Y')","R.localiteid"))		                                         
												  ->join(array("L" => $tablePrefix."rccm_localites"      ), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
		                                          ->join(array("U" => $tablePrefix."system_users_account"), "U.userid = R.creatorid", array("U.lastname","U.firstname","U.username","U.phone1","U.phone2","U.activated","U.userid"))
												  ->where("R.registreid IN (SELECT RE.registreid FROM rccm_registre_dirigeants RE)");
												  
		if( intval( $userid ))	
			$selectStatistiques->where("R.creatorid = ?", intval($userid));	
		if( intval($period_end) ){
			$selectStatistiques->where("R.creationdate <= ?",intval($period_end));
		}
		if( intval($period_start )  ){
			$selectStatistiques->where("R.creationdate >= ?",intval($period_start));
		}
		if( is_array($domaineids) && count($domaineids)) {
			$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$domaineids));
		} 
		$selectStatistiques->group(array("R.creatorid","R.localiteid"))->order(array("R.creatorid DESC","R.localiteid DESC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	
	public function statdomaines( $domaineid, $type )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre"), array("nombre"=>"COUNT(R.registreid)","R.type","R.localiteid"))
		                                          ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", null)
		                                          ->join(array("D" => $tablePrefix ."rccm_domaines"),"D.domaineid=R.domaineid",array("domaine" => "D.libelle"))
		                                          ->where("R.type=?", intval( $type ))->where("R.domaineid=?",intval( $domaineid ) );
		$selectStatistiques->group(array("R.domaineid", "R.type"))->order(array("D.libelle ASC", "R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function statlocalites( $localiteid , $type )
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R"=>$tablePrefix."rccm_registre" ), array("nombre"=>"COUNT(R.registreid)","R.type"))
		                                          ->join(array("L"=>$tablePrefix."rccm_localites"),"L.localiteid = R.localiteid",  array("localite"=>"L.libelle","L.localiteid"))
												  ->where("R.type=?", intval( $type ))->where("L.localiteid = ?", intval( $localiteid ) );
		$selectStatistiques->group(array("R.localiteid", "R.type"))->order(array("L.libelle ASC", "R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatLocalites($filters = array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("nombre" => "COUNT(R.registreid)", "R.type", "R.localiteid"))
		                                          ->join(array("L" => $tablePrefix."rccm_localites"),"L.localiteid = R.localiteid",  array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",array("RE.sexe"));
		$selectStatistiques->group(array("R.localiteid"))->order(array("COUNT(R.registreid) DESC","L.libelle ASC", "R.type ASC"));
		
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatypes($filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"), array("nombre"=> "COUNT(R.registreid)","R.type"))
		                                          ->join(array("L" => $tablePrefix."rccm_localites"          ), "L.localiteid  = R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",null);		
		
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		
		$selectStatistiques->group(array("R.type"))->order(array("R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatsexes($localiteid = 0, $startYear = 2000, $endYear = 2017)
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre"), array("nombre"=> "COUNT(R.registreid)","R.type"))
		                                          ->join(array("L" => $tablePrefix ."rccm_localites"             ), "L.localiteid      = R.localiteid", array("localite" => "L.libelle", "L.localiteid"))
												  ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"   ), "RP.registreid     = R.registreid", null)
												  ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid", array("RE.sexe"));
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		} 
		if( intval( $startYear )) {
			$selectStatistiques->where("FROM_UNIXTIME(R.date,'%Y') >= ? ", intval($startYear));
		}
		if( intval( $endYear )) {
			$selectStatistiques->where("FROM_UNIXTIME(R.date,'%Y') <= ? ", intval($endYear));
		}
        $selectStatistiques->group(array("RE.sexe"))->order(array("RE.sexe ASC"));		
		return $dbAdapter->fetchAll( $selectStatistiques,array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatnationalites($filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("nombre"=> "COUNT(R.registreid)", "R.type"))
		                                          ->join(array("L" => $tablePrefix ."rccm_localites"          )   , "L.localiteid  = R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid = R.registreid", null)
												  ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid", array("RE.country", "nationalite" => "RE.country"));		
		$selectStatistiques->group(array("RE.country"))->order(array("RE.country ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function dashboardStatyears($filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("nombre"=> "COUNT(R.registreid)", "R.type","R.annee"))
		                                          ->join(array("L" => $tablePrefix ."rccm_localites"          ), "L.localiteid  = R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"),"RE.representantid=RP.representantid", null);
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		$selectStatistiques->group(array("R.annee"))->order(array("R.annee DESC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function statAges()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");		
		$statistiqueSql     = "SELECT TA.tranche_min, COALESCE(TA.tranche_max, 9999) AS tranche_max, COUNT(RE.representantid) AS nombre, TA.libelle 
		                               FROM       ".$tablePrefix ."rccm_registre_representants_tranche_age TA 
									   INNER JOIN ".$tablePrefix ."rccm_registre_representants RE ON ((CEIL(DATEDIFF(CURRENT_DATE,RE.datenaissance)/365.2425) > TA.tranche_min) AND (CEIL(DATEDIFF(CURRENT_DATE,RE.datenaissance)/365.2425) <= COALESCE(TRANCHE_MAX, 999) ))
									   INNER JOIN ".$tablePrefix ."rccm_registre_dirigeants    RD ON RD.representantid = RE.representantid 
									   INNER JOIN ".$tablePrefix ."rccm_registre R ON R.registreid = RD.registreid 
									   GROUP BY TA.tranche_min, TA.tranche_max 
									   ORDER BY 1";
	    return $dbAdapter->fetchAll($statistiqueSql,array(), Zend_Db::FETCH_ASSOC);	
	}
	
	public function getStatYears($filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
		
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("R.annee", "R.type"))		                                          
												  ->join(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid = R.localiteid", array("localite" => "L.libelle"))
												  ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", null);
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		$selectStatistiques->group(array("R.annee"))->order(array("R.annee DESC"))->limitPage(1,22);
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function statyears($filters=array())
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
	
		$selectStatistiques = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre"), array("nombre"=> "COUNT(R.registreid)", "R.type","R.annee"))
		                                          ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants"), "RP.registreid = R.registreid",array("RP.representantid"))
												  ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", null)
												  ->join(array("L" => $tablePrefix ."rccm_localites"      ), "L.localiteid = R.localiteid", array("localite" => "L.libelle"));
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectStatistiques->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectStatistiques->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectStatistiques->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectStatistiques->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectStatistiques->where("R.date <= ? " , intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectStatistiques->where("R.date >= ? " , intval($filters["periode_start"]));
		}
		
		$selectStatistiques->group(array("R.annee", "R.type"))
		                   ->order(array("R.annee DESC", "R.type ASC"));
		return $dbAdapter->fetchAll( $selectStatistiques, array(), Zend_Db::FETCH_ASSOC );
	}
	
	
    public function domaines()
	{
		$table              = $this->getTable();
		$dbAdapter          = $table->getAdapter();
		$tablePrefix        = $table->info("namePrefix");
        $tableName          = $table->info("name");
		
		$selectDomaines     = $dbAdapter->select()->from(array("D"=> $tablePrefix ."rccm_domaines"), array("D.domaineid","D.libelle"))
		                                          ->join(array("R"=> $tableName),"R.domaineid=D.domaineid", null );
		
		return $dbAdapter->fetchPairs($selectDomaines, array(), Zend_Db::FETCH_ASSOC);
	}
	
	public function document($documentid,$registreid=null)
	{
		if(!$registreid )  {
			$registreid = $this->registreid;
		}
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectDocument = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.documentid","D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.resourceid","D.userid"))
				                              ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("category"=>"C.libelle","categorie"=>"C.libelle","C.icon","catid"=>"C.id"))
				                              ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",array("RD.access"))
				                              ->where("RD.documentid=?", $documentid);
		if( null!=$registreid) {
			$selectDocument->where("RD.registreid=?", intval($registreid));
		}
		return $dbAdapter->fetchRow( $selectDocument, array(), Zend_Db::FETCH_ASSOC );
	}
    
	public function documents($registreid=null, $access = null,$documentid=null )
	{
		if( !$registreid )  {
			 $registreid   = $this->registreid;
		}
		$table             = $this->getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents"), array("D.documentid","D.resource","D.resourceid","D.filename","D.filepath","D.filextension","D.category","D.filemetadata","D.creationdate","D.filedescription","D.filesize","D.resourceid","D.userid","D.creatoruserid","D.updateduserid","D.updatedate"))
				                                 ->join(array("C" => $tablePrefix."system_users_documents_categories"),"C.id=D.category", array("categorie"=>"C.libelle","C.icon","catid"=>"C.id"))
				                                 ->join(array("RD"=> $tablePrefix."rccm_registre_documents"),"RD.documentid=D.documentid",array("RD.access"))
				                                 ->where("RD.registreid=?", $registreid  );
		if( null !== $access ) {
			$selectDocuments->where("D.access=?", intval( $access ));
		}
		if( null!=$documentid) {
			$selectDocuments->where("RD.documentid=?", intval($documentid));
		}
		$selectDocuments->order(array("RD.registreid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments, array(), Zend_Db::FETCH_ASSOC );
	}
	
	public function getRheawebList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R"=> $tablePrefix ."rheaweb_registre"))
	                                          ->join(array("L"=> $tablePrefix ."rccm_localites")  , "L.localiteid= R.localiteid", array("localite"=>"L.libelle"));	
	    if( isset($filters["numero"]) && !empty($filters["numero"]) ){
			$selectRegistre->where("R.numero=?",strip_tags($filters["numero"]));
		}
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$likeNumero     = new Zend_Db_Expr("R.numero  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumRheaweb = new Zend_Db_Expr("R.rheanum LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumKey     = new Zend_Db_Expr("R.numkey  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$selectRegistre->where("{$likeNumero} OR {$likeNumRheaweb} OR {$likeNumKey}");
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["localite"]) && !empty($filters["localite"])  ) {
			$selectRegistre->where("R.localite = ?", strip_tags( $filters["localite"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		$selectRegistre->order(array("R.annee DESC","R.numkey ASC","R.numero ASC","R.localiteid DESC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}		
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListMissings( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R"=> $tablePrefix ."rheaweb_registres_missings"), array("R.registreid","R.numero","R.rheanum","R.observations","R.numkey","R.annee","localiteCode"=>"R.localite","R.localiteid","R.found"))
	                                          ->join(array("L"=> $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"));
	
	    if( isset($filters["numero"]) && !empty($filters["numero"]) ){
			$selectRegistre->where("R.numero=?",strip_tags($filters["numero"]));
		}
		if(isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$likeNumero     = new Zend_Db_Expr("R.numero  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumRheaweb = new Zend_Db_Expr("R.rheanum LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumKey     = new Zend_Db_Expr("R.numkey  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$selectRegistre->where("{$likeNumero} OR {$likeNumRheaweb} OR {$likeNumKey}");
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["localite"]) && !empty($filters["localite"])  ) {
			$selectRegistre->where("R.localite = ?", strip_tags( $filters["localite"] ) );
		}
		if( isset($filters["found"]) && ((intval($filters["found"])==1) || (intval($filters["found"])==0) ) ) {
			$selectRegistre->where("R.found = ?", intval( $filters["found"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		$selectRegistre->order(array("R.annee ASC","R.numkey ASC","R.numero ASC","R.localiteid DESC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}		
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getListMissingsPaginator( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R"=> $tablePrefix ."rheaweb_registres_missings"), array("R.registreid","R.numero","R.rheanum","R.observations","R.numkey","R.annee","localiteCode"=>"R.localite","R.localiteid"))
	                                          ->join(array("L"=> $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"));	
	    if( isset($filters["numero"]) && !empty($filters["numero"]) ){
			$selectRegistre->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if(isset($filters["searchQ"]) && !empty($filters["searchQ"]) && (null!==$filters["searchQ"])){
			$likeNumero     = new Zend_Db_Expr("R.numero  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumRheaweb = new Zend_Db_Expr("R.rheanum LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$likeNumKey     = new Zend_Db_Expr("R.numkey  LIKE \"%".strip_tags($filters["searchQ"])."%\"");
			$selectRegistre->where("{$likeNumero} OR {$likeNumRheaweb} OR {$likeNumKey}");
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee = ?", intval( $filters["annee"] ));
		}
		if( isset($filters["localite"]) && !empty($filters["localite"])  ) {
			$selectRegistre->where("R.localite = ?", strip_tags( $filters["localite"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["found"]) && ((intval($filters["found"])==1) || (intval($filters["found"])==0) ) ) {
			$selectRegistre->where("R.found = ?", intval( $filters["found"] ) );
		}
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
	public function getSimilarList($searchQuery, $split_length= 4, $pageNum = 0 , $pageSize = 0, $orders = array("R.numero ASC","L.libelle ASC","R.libelle ASC","R.date DESC"))
	{
		$emptyWords     = $this->emptywords();
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("R.registreid","R.numero","R.libelle","R.description","R.date","R.category","R.type","date_registre" => "FROM_UNIXTIME(R.date,'%d/%m/%Y')","annee" => "FROM_UNIXTIME(R.date,'%Y')"))
		                                      ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid     = R.registreid",array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid = RP.representantid", array("RE.nom", "RE.prenom","RE.adresse"))
											  ->join(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid= R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D" => $tablePrefix ."rccm_domaines" ), "D.domaineid = R.domaineid ", array("domaine" =>"D.libelle"));
	    if( empty( $searchQuery )) {
			return array();
		}
		$searchSpaceArray = preg_split("/[\s,]+/", $searchQuery, $split_length);
		if( isset($searchSpaceArray[1])) {
			$searchAgainst = "+".$searchSpaceArray[0]."* ";
			unset($searchSpaceArray[0]);
			foreach( $searchSpaceArray as $searchWord ) {
				     $searchWordUppercase = strtoupper($searchWord);
				     if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
						$searchAgainst  .="~".$searchWord."* ";
				     }					     
			}			
		} else {
			$searchWordArray = str_split($searchQuery, 3);
			if( isset( $searchWordArray[0] )) {
				$searchAgainst = "+".$searchWordArray[0]."* ";
				unset($searchWordArray[0]);
			}
			if( count(   $searchWordArray)) {
				foreach( $searchWordArray as $searchWord ) {
				         $searchWordUppercase = strtoupper($searchWord);
						 if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
							$searchAgainst  .="~".$searchWord."* ";
						 }
			    }
			}
		}
		$likeLibelle       = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\")");
		$selectRegistre->where("{$likeLibelle}");
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}
	    return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getSimilarListPaginator($searchQuery, $split_length= 4)
	{
		$emptyWords     = $this->emptywords();
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix ."rccm_registre" ), array("R.registreid","R.numero","R.libelle","R.description","R.date","R.category","R.type","date_registre"=>"FROM_UNIXTIME(R.date,'%d/%m/%Y')","annee"=>"FROM_UNIXTIME(R.date,'%Y')"))
		                                      ->join(array("RP"=> $tablePrefix ."rccm_registre_dirigeants")   , "RP.registreid    =R.registreid",array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix ."rccm_registre_representants"), "RE.representantid=RP.representantid", array("RE.nom", "RE.prenom","RE.adresse"))
											  ->join(array("L" => $tablePrefix ."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
		                                      ->joinLeft(array("D" => $tablePrefix ."rccm_domaines" ), "D.domaineid=R.domaineid", array("domaine" =>"D.libelle"));
	    if( empty( $searchQuery )) {
			$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
			$paginationAdapter->setRowCount(0);
			$paginator         = new Zend_Paginator($paginationAdapter);
			return $paginator;
		}
		$searchSpaceArray = preg_split("/[\s,]+/", $searchQuery, $split_length);
		if( isset($searchSpaceArray[1])) {
			$searchAgainst = "+".$searchSpaceArray[0]."* ";
			unset($searchSpaceArray[0]);
			foreach( $searchSpaceArray as $searchWord ) {
				     if(!in_array($searchWord, $emptyWords) ) {
						$searchAgainst  .="~".$searchWord."* ";
				     }
			}			
		} else {
			$searchWordArray = str_split($searchQuery, 3);
			if( isset( $searchWordArray[0] )) {
				$searchAgainst = "+".$searchWordArray[0]."* ";
				unset($searchWordArray[0]);
			}
			if( count(   $searchWordArray)) {
				foreach( $searchWordArray as $searchWord ) {
				         if(!in_array($searchWord, $emptyWords) ) {
							$searchAgainst  .="~".$searchWord."* ";
						 }
			    }
			}
		}
		$likeLibelle       = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\")");
		$selectRegistre->where("{$likeLibelle}");
	    
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	}
	
	public function simpleList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("R.numero ASC","R.libelle ASC","R.date DESC") )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.type","R.localiteid","R.domaineid","R.numero","R.libelle","R.description","R.date","date_registre"=>"FROM_UNIXTIME(R.date,'%d/%m/%Y')","annee"=>"R.annee"));
	    
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"]) ){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"]) && (null!==$filters["description"]) ){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
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
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.creationdate<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.creationdate>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		if(intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage( $pageNum , $pageSize);
		}		
		//print_r($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function basicList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("R.numero ASC","L.libelle ASC","R.libelle ASC","R.date DESC") )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.type","R.localiteid","R.domaineid","R.numero","R.libelle","R.description","R.date","date_registre"=>"R.date","annee"=>"R.annee"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid",array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom"))
											  ->join(array("L" => $tablePrefix."rccm_localites"), "L.localiteid=R.localiteid", array("localite"=>"L.libelle"))
		                                      /*->joinLeft(array("D"=>$tablePrefix."rccm_domaines"), "D.domaineid=R.domaineid", array("domaine" =>"D.libelle"))*/;
	    
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords     = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["keywords"]  = str_replace('"', "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("'", "", $filters["keywords"]);
				$filters["keywords"]  = str_replace("-", ",",$filters["keywords"]);
				$filters["keywords"]  = str_replace("(", ";",$filters["keywords"]);
				$filters["keywords"]  = str_replace(")", ";",$filters["keywords"]);
				$filters["keywords"]  = trim($filters["keywords"],";,-\n\r ");
				$searchAgainst        = "";
				$cleanWord            = "";
				$keywordsCond         = array();
				$keywordsArr          = preg_split("/[,;\-\*\@]+/",trim(strip_tags($filters["keywords"])));
 
				if( count(   $keywordsArr)) {
					foreach( $keywordsArr as $keywordItem ) {
							 $keywordItem      = trim($keywordItem);
							 $searchSpaceArray = preg_split("/[\s]+/",trim(strip_tags($keywordItem)));
							 $searchAgainst    = "";
							 if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
								 foreach( $searchSpaceArray as $searchKeyId=>$searchWord ) {
										  $searchWord = trim($searchWord);
										  if( empty($searchWord) || (strlen($searchWord) < 3)) {
											  continue;
										  }
										  $searchWordUppercase = strtoupper($searchWord);
										  if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
											  if( $searchKeyId==0) {
												  $searchAgainst.= $searchWord."* ";
											  } else {
												  $searchAgainst.= "~".$searchWord."* ";
											  }
											  
											  $cleanWord      .=$searchWord." ";
										  }	
																			  
								 }
								 if(!empty($searchAgainst)) {
									  $keywordsCond[] = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");														 
								  }
							 }								
					}
				}	
				if( count($keywordsCond) && is_array($keywordsCond)) {
					$selectRegistre->where(implode(" OR ",$keywordsCond));
				}
			}			
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"]) ){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ){
			$emptyWords     = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]  = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike         =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["searchQ"] = str_replace('"', ""  ,$filters["searchQ"]);
				$filters["searchQ"] = str_replace("'", ""  ,$filters["searchQ"]);
				$filters["searchQ"] = str_replace("-", " " ,$filters["searchQ"]);
				$searchSpaceArray   = preg_split("/[\s,\-\*]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
				$searchAgainst      = "";
				if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
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
				$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
				//$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
				$selectRegistre->where("{$likeRCName}");
			}
			//print_r($selectRegistre->__toString()); die();
		}
		if( isset($filters["name"]) && !empty($filters["name"]) ){
			$filters["name"]  = str_replace('"', "", $filters["name"]);
			$filters["name"]  = str_replace("'", "", $filters["name"]);
			$filters["name"]  = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
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
		if( isset($filters["nom"]) && !empty($filters["nom"]) ){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && (empty($filters["prenom"]) || (null ==$filters["prenom"]) ) ) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"]) ){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistre->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != 0) && !empty($filters["country"])){
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["passport"]) && !empty($filters["passport"])){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistre->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee=?", intval( $filters["annee"] ));
		}
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])  ) {
			$selectRegistre->where("R.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectRegistre->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectRegistre->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectRegistre->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectRegistre->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage($pageNum , $pageSize);
		} else {
			$selectRegistre->limitPage(1,10);
		}	
        $selectRegistre->group(array("R.numero","R.registreid","RP.registreid")); 	
		//print_r($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function groupListByType( $filters = array() , $pageNum = 1 , $pageSize = 10, $orders = array("COUNT(R.registreid) DESC") )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"),array("nombre"=>"COUNT(R.registreid)","R.localiteid","R.type"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid",null)
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", null);
	    
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) && (null!==$filters["keywords"])){
			$filters["keywords"]  = str_replace('"', "", $filters["keywords"]);
			$filters["keywords"]  = str_replace("'", "", $filters["keywords"]);
			$filters["keywords"]  = str_replace("-", " ",$filters["keywords"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["keywords"])));
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
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"])){
			$emptyWords     = $this->emptywords();
			$filters["searchQ"] = str_replace('"', "",  $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "",  $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ", $filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["searchQ"])));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectRegistre->where("{$likeRCName} OR {$likeClientName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]  = str_replace('"', "", $filters["name"]);
			$filters["name"]  = str_replace("'", "", $filters["name"]);
			$filters["name"]  = str_replace("-", " ", $filters["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
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
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRegistre->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
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
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.creationdate<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.creationdate>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage($pageNum , $pageSize);
		}	
        $selectRegistre->group(array("R.type"));		
		//print_r($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function groupListByLocality( $filters = array() , $pageNum = 1 , $pageSize = 10, $orders = array("COUNT(R.registreid) DESC") )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"),array("nombre"=>"COUNT(R.registreid)","R.localiteid"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid",null)
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", null)
											  ->join(array("L" => $tablePrefix."rccm_localites"),"L.localiteid=R.localiteid", array("localite"=>"L.libelle"));
	    
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) && (null!==$filters["keywords"])){
			$filters["keywords"] = str_replace('"', "", $filters["keywords"]);
			$filters["keywords"] = str_replace("'", "", $filters["keywords"]);
			$filters["keywords"] = str_replace("-", " ",$filters["keywords"]);
			$searchSpaceArray    = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["keywords"])));
			$searchAgainst       = "";
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
			$filters["searchQ"] = str_replace('"', "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("'", "", $filters["searchQ"]);
			$filters["searchQ"] = str_replace("-", " ", $filters["searchQ"]);
			$searchSpaceArray   = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["searchQ"])));
			$searchAgainst      = "";
			if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
				foreach( $searchSpaceArray as $searchWord ) {
					     $searchAgainst.="+".$searchWord."* ";
				}
			}
			$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
			$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
			$selectRegistre->where("{$likeRCName} OR {$likeClientName}");
		}
		if( isset($filters["name"]) && !empty($filters["name"]) && (null!==$filters["name"])){
			$filters["name"]  = str_replace('"', "", $filters["name"]);
			$filters["name"]  = str_replace("'", "", $filters["name"]);
			$filters["name"]  = str_replace("-", " ",$filters["name"]);
			$searchSpaceArray = preg_split("/[\s,]+/",$dbAdapter->quote(strip_tags($filters["name"])));
			$searchAgainst    = "";
			$nom              = $prenom = "";
			if( isset($searchSpaceArray[0])) {
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
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"]) && (null!==$filters["numero"]) ){
			$selectRegistre->where("R.numero LIKE ?","%".strip_tags($filters["numero"])."%");
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
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
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.creationdate<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.creationdate>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage($pageNum , $pageSize);
		}	
        $selectRegistre->group(array("R.localiteid"));		
		//print_r($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0, $orders = array("R.annee DESC","R.date DESC","R.numero DESC","R.libelle ASC") )
	{		
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		//$dateNaissance  = new Zend_Db_Expr("DATE_FORMAT(RE.datenaissance,'%d/%m/%Y')");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre" ), array("R.registreid","R.localiteid","R.domaineid","R.numero","R.numifu","R.numcnss","R.libelle","R.description","R.date","R.category","R.type","date_registre"=>"R.date","annee"=>"R.annee"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid"         , array("RP.representantid"))
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid", array("RE.nom","RE.prenom","RE.adresse","RE.lieunaissance","RE.datenaissance","RE.passport","RE.sexe","RE.telephone"))											  
											  ->join(array("L" => $tablePrefix."rccm_localites")             ,"L.localiteid=R.localiteid"          , array("localite"=>"L.libelle"))
		                                      ->join(array("D" => $tablePrefix."rccm_domaines")              ,"D.domaineid=R.domaineid"            , array("domaine" =>"D.libelle"));
	    
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords               = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
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
					$selectRegistre->where(implode(" OR ",$keywordsCond));
				}
				//echo $selectRegistre->__toString(); die();
			}			
		}
		if( isset($filters["libelle"]) && !empty($filters["libelle"])){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"]) ){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ){
			$emptyWords                  = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]       = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike              =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
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
				$selectRegistre->where("{$likeRCName} OR {$likeClientName}");
			}
			//print_r($selectRegistre->__toString()); die();
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
					$selectRegistre->where(new Zend_Db_Expr("RE.nom    LIKE \"%".strip_tags(trim($nom))   ."%\""));
				}
				if(!empty( $prenom ) ) {
					$selectRegistre->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags(trim($prenom))."%\""));
				}
			} elseif(count($searchSpaceArray)==1) {
				$searchClientName         = 
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchName."\" IN BOOLEAN MODE)");				
				$selectRegistre->where("{$likeClientName}");
			}			
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) ){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && empty($filters["prenom"])) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"]) ){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistre->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && !empty($filters["country"])){
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["passport"]) && !empty($filters["passport"])){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistre->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee=?", intval( $filters["annee"] ));
		}
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["periodend"]) && intval($filters["periodend"]) ){
			$selectRegistre->where("R.creationdate<=?", intval($filters["periodend"]));
		}
		if( isset($filters["periodstart"]) && intval($filters["periodstart"]) ){
			$selectRegistre->where("R.creationdate>=?", intval($filters["periodstart"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"])  ) {
			$selectRegistre->where("R.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectRegistre->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectRegistre->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectRegistre->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectRegistre->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}
		if( is_array( $orders ) && !empty( $orders) ) {
			$selectRegistre->order( $orders );
		}		
		$selectRegistre->group(array("R.localiteid","R.domaineid","R.registreid"));
		if( intval($pageNum) && intval($pageSize)) {
			$selectRegistre->limitPage($pageNum,$pageSize);
		}		
		//print_r($selectRegistre->__toString()); die();
		return $dbAdapter->fetchAll( $selectRegistre, array() , Zend_Db::FETCH_ASSOC);
	    //print_r(count($rows)); die();
		//return $rows;
	}
	
	
	public function getListPaginator( $filters = array() )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"),array("R.registreid"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid", null)
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",null);	
		
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
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
								 foreach( $searchSpaceArray as $searchWord ) {
										  $searchWord = trim($searchWord);
										  if( empty($searchWord) || (strlen($searchWord) < 3)) {
											  continue;
										  }
										  $searchWordUppercase = strtoupper($searchWord);
										  if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
											  $searchAgainst  .= " +".$searchWord."* ";
											  $cleanWord      .=$searchWord." ";
										  }	
																			  
								 }
								 if(!empty($searchAgainst)) {
									  $keywordsCond[] = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");														 
								  }
							 }								
					}
				}	
				if( count($keywordsCond) && is_array($keywordsCond)) {
					$selectRegistre->where(implode(" OR ",$keywordsCond));
				}
			}			
		}
		if( isset($filters["libelle"])     && !empty($filters["libelle"])){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"])){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"])    && !empty($filters["passport"])){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"]) && !empty($filters["searchQ"]) ){
			$emptyWords                  = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]       = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike              =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
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
				$selectRegistre->where("{$likeRCName} OR {$likeClientName}");
			}
			//print_r($selectRegistre->__toString()); die();
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
					$selectRegistre->where(new Zend_Db_Expr("RE.nom    LIKE \"%".strip_tags(trim($nom))   ."%\""));
				}
				if(!empty( $prenom ) ) {
					$selectRegistre->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags(trim($prenom))."%\""));
				}
			} elseif(count($searchSpaceArray)==1) {
				$searchClientName         = 
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchName."\" IN BOOLEAN MODE)");				
				$selectRegistre->where("{$likeClientName}");
			}			
		}
		if( isset($filters["nom"]) && !empty($filters["nom"]) ){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && empty($filters["prenom"])) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"]) && !empty($filters["prenom"]) ){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistre->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))){
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistre->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee= ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistre->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["periodend"]) && intval($filters["periodend"]) ){
			$selectRegistre->where("R.creationdate<=?", intval($filters["periodend"]));
		}
		if( isset($filters["periodstart"]) && intval($filters["periodstart"]) ){
			$selectRegistre->where("R.creationdate>=?", intval($filters["periodstart"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectRegistre->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectRegistre->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectRegistre->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectRegistre->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}	 
		//print_r($selectRegistre->__toString()); die();
		$selectRegistre->group(array("R.registreid","RP.registreid")); 
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	} 


    public function basicListPaginator( $filters = array() )
	{
		$table          = $this->getTable();
		$dbAdapter      = $table->getAdapter();
		$tablePrefix    = $table->info("namePrefix");
		$selectRegistre = $dbAdapter->select()->from(array("R" => $tablePrefix."rccm_registre"),array("R.registreid"))
		                                      ->join(array("RP"=> $tablePrefix."rccm_registre_dirigeants")   ,"RP.registreid=R.registreid", null)
		                                      ->join(array("RE"=> $tablePrefix."rccm_registre_representants"),"RE.representantid=RP.representantid",null);	
		
		if( isset($filters["keywords"]) && !empty($filters["keywords"]) ){
			$emptyWords = $this->emptywords();
			if( $this->checkNum($filters["keywords"])) {
				$filters["numero"]    = $filters["keywords"];
				unset($filters["keywords"]);
			} elseif($this->likeNum($filters["keywords"])) {
				$numeroLike           =  trim(strip_tags($filters["keywords"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
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
								 foreach( $searchSpaceArray as $searchWord ) {
										  $searchWord = trim($searchWord);
										  if( empty($searchWord) || (strlen($searchWord) < 3)) {
											  continue;
										  }
										  $searchWordUppercase = strtoupper($searchWord);
										  if(!in_array($searchWord, $emptyWords)  && !in_array($searchWordUppercase, $emptyWords)) {
											  $searchAgainst  .= " +".$searchWord."* ";
											  $cleanWord      .=$searchWord." ";
										  }	
																			  
								 }
								 if(!empty($searchAgainst)) {
									  $keywordsCond[] = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");														 
								  }
							 }								
					}
				}	
				if( count($keywordsCond) && is_array($keywordsCond)) {
					$selectRegistre->where(implode(" OR ",$keywordsCond));
				}
			}			
		}
		if( isset($filters["libelle"])     && !empty($filters["libelle"])){
			$selectRegistre->where("R.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
		if( isset($filters["description"]) && !empty($filters["description"])){
			$selectRegistre->where("R.description LIKE ?","%".strip_tags($filters["description"])."%");
		}
		if( isset($filters["passport"])    && !empty($filters["passport"])){
			$selectRegistre->where("RE.passport LIKE ?","%".strip_tags($filters["passport"])."%");
		}		
		if( isset($filters["searchQ"])     && !empty($filters["searchQ"]) ){
			$emptyWords     = $this->emptywords();
			if( $this->checkNum($filters["searchQ"])) {
				$filters["numero"]  = $filters["searchQ"];
				unset($filters["searchQ"]);
			} elseif($this->likeNum($filters["searchQ"])) {
				$numeroLike         =  trim(strip_tags($filters["searchQ"]))."%";
				$selectRegistre->where("R.numero LIKE ?",$numeroLike);
		    } else {
				$filters["searchQ"] = str_replace('"', ""  ,$filters["searchQ"]);
				$filters["searchQ"] = str_replace("'", ""  ,$filters["searchQ"]);
				$filters["searchQ"] = str_replace("-", " " ,$filters["searchQ"]);
				$searchSpaceArray   = preg_split("/[\s,\-\*]+/",trim($dbAdapter->quote(strip_tags($filters["searchQ"]))));
				$searchAgainst      = "";
				if( isset($searchSpaceArray[0]) && !empty($searchSpaceArray[0])) {
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
				$likeRCName    = new Zend_Db_Expr("MATCH(R.numero,R.libelle) AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");
				//$likeClientName= new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchAgainst."\" IN BOOLEAN MODE)");			 
				$selectRegistre->where("{$likeRCName}");
			}			
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
					$selectRegistre->where(new Zend_Db_Expr("RE.nom    LIKE \"%".strip_tags(trim($nom))   ."%\""));
				}
				if(!empty( $prenom ) ) {
					$selectRegistre->where(new Zend_Db_Expr("RE.prenom LIKE \"%".strip_tags(trim($prenom))."%\""));
				}
			} elseif(count($searchSpaceArray)==1) {
				$searchClientName         = 
				$likeClientName           = new Zend_Db_Expr("MATCH(RE.nom,RE.prenom)   AGAINST (\"".$searchName."\" IN BOOLEAN MODE)");				
				$selectRegistre->where("{$likeClientName}");
			}			
		}
		if( isset($filters["nom"]) && !empty($filters["nom"])){
			$selectRegistre->where("RE.nom LIKE ?","%".strip_tags($filters["nom"])."%");
			if( isset($filters["prenom"]) && (empty($filters["prenom"]) || (null ==$filters["prenom"]) ) ) {
				$selectRegistre->orWhere("RE.prenom LIKE ?","%".$filters["nom"]."%");
			}
		}
		if( isset($filters["prenom"])    && !empty($filters["prenom"]) ){
			$selectRegistre->where("RE.prenom LIKE ?","%".$filters["prenom"]."%");
		}
		if( isset($filters["telephone"]) && !empty($filters["telephone"]) ){
			$selectRegistre->where("RE.telephone LIKE ?","%".$filters["telephone"]."%");
		}
		if( isset($filters["sexe"]) && (($filters["sexe"] == "F") || ($filters["sexe"] == "M"))   ){
			$selectRegistre->where("RE.sexe = ?", strip_tags($filters["sexe"]));
		}
		if( isset($filters["country"]) && ($filters["country"] != null)  && ($filters["country"] != 0) && (!empty($filters["country"]))   ){
			$selectRegistre->where("RE.country=?", strip_tags($filters["country"]));
		}
		if( isset($filters["numero"]) && !empty($filters["numero"])){
			$selectRegistre->where("R.numero=?", strip_tags($filters["numero"]) );
		}
		if( isset($filters["numcnss"]) && !empty($filters["numcnss"])){
			$selectRegistre->where("R.numcnss=?", strip_tags($filters["numcnss"]) );
		}
		if( isset($filters["numifu"]) && !empty($filters["numifu"])){
			$selectRegistre->where("R.numifu=?", strip_tags($filters["numifu"]) );
		}
		if( isset($filters["annee"]) && intval($filters["annee"])   ){
			$selectRegistre->where("R.annee= ?", intval( $filters["annee"] ));
		}
		if( isset($filters["domaineid"]) && intval($filters["domaineid"])  ) {
			$selectRegistre->where("R.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["localiteid"]) && intval($filters["localiteid"])  ) {
			$selectRegistre->where("R.localiteid = ?", intval( $filters["localiteid"] ) );
		}
		if( isset($filters["type"]) && intval($filters["type"])  ) {
			$selectRegistre->where("R.type=?", intval( $filters["type"] ) );
		}
		if( isset($filters["types"]) && count($filters["types"])  && is_array($filters["types"])) {
			$selectRegistre->where("R.type IN (?)", array_map("intval",$filters["types"]));
		}
		if( isset($filters["date"]) && !empty($filters["date"]) && (null !== $filters["date"] ) ) {
			if( Zend_Date::isDate($filters["date"], "YYYY-MM-dd")) {
			    $selectRegistre->where("FROM_UNIXTIME(R.date,'%Y-%m-%d')=? ", $filters["date"]);
			    $filters["periode_end"] = $filters["periode_start"]  = null;
			}
		}
		if( isset($filters["periode_end"]) && intval($filters["periode_end"]) ){
			$selectRegistre->where("R.date<=?", intval($filters["periode_end"]));
		}
		if( isset($filters["periode_start"]) && intval($filters["periode_start"]) ){
			$selectRegistre->where("R.date>=?", intval($filters["periode_start"]));
		}
		if( isset($filters["creatorid"]) && intval($filters["creatorid"])  ) {
			$selectRegistre->where("R.creatorid = ?", intval( $filters["creatorid"] ) );
		}
		if( isset( $filters["registreids"] ) && is_array( $filters["registreids"] )) {
			if( count( $filters["registreids"])) {
				$selectRegistre->where("R.registreid IN (?)", array_map("intval",$filters["registreids"]));
			}			
		}
		if( isset( $filters["domaineid"] ) && intval( $filters["domaineid"] ) ) {
			$selectRegistre->where("R.domaineid=?" , intval( $filters["domaineid"] ) );
		}
		if( isset( $filters["domaineids"] ) && is_array( $filters["domaineids"] )) {
			if( count( $filters["domaineids"])) {
				$selectRegistre->where("R.domaineid IN (?)", array_map("intval",$filters["domaineids"]));
			}			
		}
		if( isset( $filters["localiteid"] ) && intval( $filters["localiteid"] ) ) {
			$selectRegistre->where("R.localiteid=?" , intval( $filters["localiteid"] ) );
		}
		if( isset( $filters["localiteids"] ) && is_array( $filters["localiteids"] )) {
			if( count( $filters["localiteids"])) {
				$selectRegistre->where("R.localiteid IN (?)", array_map("intval",$filters["localiteids"]));
			}			
		}	 
		//print_r($selectRegistre->__toString()); die();
		$selectRegistre->group(array("R.numero","R.registreid","RP.registreid")); 
		$paginationAdapter = new Zend_Paginator_Adapter_DbSelect( $selectRegistre );
		$rowCount          = intval(count($dbAdapter->fetchAll(   $selectRegistre )));
		$paginationAdapter->setRowCount($rowCount);
		$paginator         = new Zend_Paginator($paginationAdapter);
		return $paginator;
	} 	
}