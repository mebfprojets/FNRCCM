<?php

class Model_Entreprise extends Sirah_Model_Default
{
	protected $_inputKeys  = array("RAISON_SOCIAL" => "libelle", "IFU" => "reference", "EMAIL" => "email", "ADRESSE" => "address",'PAYS' => "country", "LOGO" =>  "logo",
	                               "CAPITAL"       => "capital", "CHIFFRE_AFFAIRE" => "chiffre_affaire"  , "DATE_CREATION" => "datecreation",'FORME_JURIDIQUE'=> "formid",
								   "CODE_POSTAL"   => "zip","SITE_WEB" => "siteweb", "TEL_FIXE" => "phone1", "TEL_MOB" => "phone2",'GROUPE' => null, 
								   "RESPONSABLE"   => "responsable","TEL_FAX" => "fax", "DOMAINE" => null, "PRESENTATION" => "presentation",
								   "RCCM"          => "num_rc", "CNSS"        => "num_securite_social");

	
	public function setInputKeys()
	{
		$groupe    = $this->findParentRow("Table_Entreprisegroups");
		$forme     = $this->findParentRow("Table_Entrepriseformes");
		$domaine   = $this->findParentRow("Table_Domaines");
		$country   = $this->findParentRow("Table_Countries");
		$city      = $this->findParentRow("Table_Countrycities");
		$tableData = $this->toArray();
		foreach( $this->_inputKeys as $key => $tableKey ) {
			     if(empty( $tableKey )) 
					 continue;
				 if( isset($tableData[$tableKey])) {
					 $this->_inputKeys[$key] = $tableData[$tableKey];
				 }
		}		
		if( isset($groupe->libelle)) {
			$this->_inputKeys["GROUPE"] = $groupe->libelle;
		}
		if( isset($forme->libelle)) {
			$this->_inputKeys["FORME_JURIDIQUE"] = $forme->libelle;
		}
		if( isset($domaine->libelle)) {
			$this->_inputKeys["DOMAINE"]         = $domaine->libelle;
		}
		if( isset($country->libelle)) {
			$this->_inputKeys["PAYS"]            = $country->libelle;
		}
		if( isset($city->libelle)) {
			$this->_inputKeys["VILLE"]           = $city->libelle;
		}
		return $this;
	}
	
	public function output( $string )
	{
		$this->setInputKeys();
		preg_match_all("/\[(.*?)\]/", $string, $matches);
		if( isset(   $matches[0] ) && count($matches[0])) {
			foreach( $matches[0] as $key  ) {
				     if(empty( $key))
						 continue;
					 $key  = strip_tags($key);
				     if(isset( $this->_inputKeys[$key])) {
						       $string = str_replace( "[".$key."]" , $this->_inputKeys[$key],  $string );
					 }
			}
		}		
		return $string;
    }
	
	public function htmlOutput( $string )
	{
		$this->setInputKeys();
		$logoFile          = VIEW_BASE_PATH."/images/default_company_logo.png";
		$entrepriseLogo    = $this->logo;
        if(!empty( $entrepriseLogo ) && file_exists($entrepriseLogo)) {			  
            $logoPathname  = str_replace( APPLICATION_PATH , ROOT_PATH . DS ."myV1" , $entrepriseLogo);
            $logoFile      = Sirah_Filesystem::truepath(str_replace( DS , "/" , $logoPathname));
			$this->_inputKeys["LOGO"]  = "<img src=\"".$logoFile."\" />";
        } 
		preg_match_all("/\[(.*?)\]/", $string, $matches);
		if( isset(   $matches[1] ) && count($matches[1])) {
			foreach( $matches[1] as $key  ) {
				     if(empty( $key))
						 continue;
					 $key  = strip_tags($key);
				     if(isset( $this->_inputKeys[$key])) {
						       $string = str_replace( "[".$key."]" , $this->_inputKeys[$key],  $string );
					 }
			}
		}		
		return $string;
    }

    public function projects( $entrepriseid = 0, $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table         = $this->getTable();
		$dbAdapter     = $table->getAdapter();
		$tablePrefix   = $table->info("namePrefix");				
		if(!intval($entrepriseid)) {
			$entrepriseid  = $this->entrepriseid;
		}
        $selectProject = $dbAdapter->select()->from(array("P" => $tablePrefix ."rccm_projet_application"))
		                                     ->where("P.entrepriseid = ?", intval($entrepriseid));		
		if( isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectProject->where("P.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}			
		$selectProject->order(array("P.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
			$selectProject->limitPage( $pageNum , $pageSize);
		}
		return $dbAdapter->fetchAll( $selectProject, array() , Zend_Db::FETCH_ASSOC);
	}
	
	public function documents( $entrepriseid = null )
	{
		if( null === $entrepriseid ) {
			$entrepriseid = $this->entrepriseid;
		}
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$documents        = array();
		$selectDocument   = $dbAdapter->select()->from(array("D" => $tablePrefix."system_users_documents" ))
		                                        ->join(array("E" => $tablePrefix."rccm_registre_entreprises_documents"), "E.documentid = D.documentid", null )
		                                        ->where("E.entrepriseid= ?", intval($entrepriseid));
		$rows             = $dbAdapter->fetchAll($selectDocument);
		return $rows;
	}
	
    public function getList( $filters = array() , $pageNum = 0 , $pageSize = 0)
	{
		$table            = $this->getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$selectEntreprise = $dbAdapter->select()->from(    array("E" => $tablePrefix ."rccm_registre_entreprises" ))
		                                        ->joinLeft(array("F" => $tablePrefix ."rccm_registre_entreprises_forme_juridique"),"F.formid=E.formid", array("formeJuridique"=>"F.libelle"))
		                                        ->joinLeft(array("D" => $tablePrefix ."rccm_domaines"), "D.domaineid = E.domaineid", array("domaine"=> "D.libelle"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectEntreprise->where("E.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["domaineid"]) && intval($filters["domaineid"]) ) {
			$selectEntreprise->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["formid"]) && intval($filters["formid"]) ) {
			$selectEntreprise->where("E.formid = ?", intval( $filters["formid"] ) );
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
			$selectEntreprise->where("E.registreid = ?", intval( $filters["registreid"] ) );
		}
		if(isset($filters["address"]) && !empty($filters["address"]) && (null!==$filters["address"])){
			$selectEntreprise->where("E.address LIKE ?","%".strip_tags($filters["address"])."%");
		}
		if(isset($filters["phone"]) && !empty($filters["phone"]) && (null!==$filters["phone"])){
			$selectEntreprise->where("E.phone1 LIKE ?",strip_tags($filters["phone"])."%")
			                 ->orWhere("E.phone2 LIKE ?",strip_tags($filters["phone"])."%");
		}
		if(isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectEntreprise->where("E.email = ?",strip_tags($filters["email"]));
		}
		if(isset($filters["domaine"]) && !empty($filters["domaine"])){
			$searchDomaine = new Zend_Db_Expr("D.libelle LIKE '%".$filters["domaine"]."%'");
			if(($filters["domaine"] == "bank") || ($filters["domaine"] == "banque") || ($filters["domaine"] == "ban"))
			  $searchDomaine = new Zend_Db_Expr("((D.libelle LIKE 'bank%') OR (D.libelle LIKE 'banque%') OR (D.libelle LIKE 'ban%') )");
		    if(($filters["domaine"] == "assureur") || ($filters["domaine"] == "assurance") || ($filters["domaine"] == "assur"))
			  $searchDomaine = new Zend_Db_Expr("((D.libelle LIKE 'assurance%') OR (D.libelle LIKE 'assureur%') OR (D.libelle LIKE 'assur%') )");
			$selectEntreprise->where($searchDomaine);
		}
		$selectEntreprise->order(array("E.libelle ASC"));
		if(intval($pageNum) && intval($pageSize)) {
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
		$selectEntreprise = $dbAdapter->select()->from(array("E"     => $tablePrefix ."rccm_registre_entreprises" ), array("E.domaineid"))
		                                        ->joinLeft(array("D" => $tablePrefix ."rccm_domaines"), "D.domaineid = E.domaineid", array("domaine"=> "D.libelle"));
	
		if(isset($filters["libelle"]) && !empty($filters["libelle"]) && (null!==$filters["libelle"])){
			$selectEntreprise->where("E.libelle LIKE ?","%".strip_tags($filters["libelle"])."%");
		}
	    if( isset($filters["domaineid"]) && intval($filters["domaineid"]) ) {
			$selectEntreprise->where("E.domaineid = ?", intval( $filters["domaineid"] ) );
		}
		if( isset($filters["registreid"]) && intval($filters["registreid"]) ) {
			$selectEntreprise->where("E.registreid = ?", intval( $filters["registreid"] ) );
		}
		if( isset($filters["formid"]) && intval($filters["formid"]) ) {
			$selectEntreprise->where("E.formid = ?", intval( $filters["formid"] ) );
		}
		if(isset($filters["address"]) && !empty($filters["address"]) && (null!==$filters["address"])){
			$selectEntreprise->where("E.address LIKE ?","%".strip_tags($filters["address"])."%");
		}
		if(isset($filters["phone"]) && !empty($filters["phone"]) && (null!==$filters["phone"])){
			$selectEntreprise->where("E.phone1 LIKE ?","%".strip_tags($filters["phone"])."%")->where("E.phone2 LIKE ?","%".strip_tags($filters["phone"])."%");
		}
		if(isset($filters["email"]) && !empty($filters["email"]) && (null!==$filters["email"])){
			$selectEntreprise->where("E.email = ?",strip_tags($filters["email"]));
		}
		if(isset($filters["domaine"]) && !empty($filters["domaine"])){
			$searchDomaine = new Zend_Db_Expr("D.libelle LIKE '%".$filters["domaine"]."%'");
			if(($filters["domaine"] == "bank") || ($filters["domaine"] == "banque") || ($filters["domaine"] == "ban"))
			  $searchDomaine = new Zend_Db_Expr("((D.libelle LIKE 'bank%') OR (D.libelle LIKE 'banque%') OR (D.libelle LIKE 'ban%') )");
		    if(($filters["domaine"] == "assureur") || ($filters["domaine"] == "assurance") || ($filters["domaine"] == "assur"))
			  $searchDomaine = new Zend_Db_Expr("((D.libelle LIKE 'assurance%') OR (D.libelle LIKE 'assureur%') OR (D.libelle LIKE 'assur%') )");
			$selectEntreprise->where($searchDomaine);
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
