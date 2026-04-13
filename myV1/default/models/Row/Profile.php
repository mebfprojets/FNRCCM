<?php

class Model_Profile extends Sirah_Model_Default
{
	
	static public function getTypeOfUsers( )
	{
		$typeOfUsers = array( 0  => "Personne", 1  => "Tout le monde",
				              2  => "Uniquement mes collaborateurs et recruteurs",
				              3  => "Uniquement les recruteurs et formateurs",
				              4  => "Uniquement ceux qui possèdent un compte");
		return $typeOfUsers;
	}
	
	public function isAllowed( $userid , $object , $profileid = 0 )
	{  
		$user           = Sirah_Fabric::getUser( $userid );
		$myProfile      = $this;
		if( !$myProfile->profileid ) {
			 $myProfile = $this->getRow( $profileid, true, false );
		}
		$object         = preg_replace("/\./i", "_", $object );
		$myParams       = $myProfile->paramsToArray();
		if( !array_key_exists( $object, $myParams ) ) {
			return false;
		}
		$typeOfUser     = $myParams[$object];
		$isAllowed      = false;
		switch( intval( $typeOfUser ) ) {
			case 1:
				$isAllowed = true;
				break;
			case 2:
			case 3:
				$isAllowed = ( $user->isRecruteurs()     || 
				               $user->isManagers()       ||  
				               $user->isProfessionnals() || 
				               $user->isAdministrateur() || 
				               $user->isSuperviseur()    || 
				               $user->isAdmin() );
			
		}
		return	$isAllowed;	
	}
	
	
	function setParams($parametres = array())
	{
		$me            = Sirah_Fabric::getUser();
		$defaultParams = array(
				               "view_profile_infos"       => 1,
				               "view_profile_coordonnees" => 2,
				               "view_profile_experiences" => 2,
				               "view_profile_formations"  => 2,
				               "view_profile_tags"        => 2,
				               "view_profile_content"     => 2,
				               "view_profile_contacts"    => 2,
				               "view_profile_cv"          => 3,
				               "findme_from_name"         => 3,
				               "findme_from_phone"        => 2,
				               "findme_from_email"        => 2,
				               "findme_from_tags"         => 1,
				               "allow_robots_index"       => 2 );
		$parametres    = array_merge( $defaultParams , $parametres );
		$formatParams  = "";
		if(count(    $parametres ) ) {
			foreach( $parametres as $key=>$val ) {
				$formatParams  .= "{$key}={$val};";
			}
		}
		$this->params = substr( $formatParams , 0 , -1 );
		return $this->save();
	}
	
	public function create( $userid )
	{
		$table       = $this->_table;
		$adapter     = $table->getAdapter();
		$tableName   = $table->info("name");
		$prefix      = $table->info("namePrefix");
		$user        = Sirah_Fabric::getUser($userid);
		$userTable   = $user->getTable();
		$userData    = $userTable->getData();
		$profileData = $this->toArray();
		$profileRow  = array_intersect_key( $userData , $profileData );
		$row         = null;		
		if( empty( $profileRow["matricule"] ) ) {
			$profileRow["matricule"]  = $this->generateMatricule($userid);
		}
		$profileRow["updateduserid"] = 0; $profileRow["creatorid"]    = 1;
		$profileRow["updatedate"]    = 0; $profileRow["creationdate"] = time();
		if($adapter->insert( $prefix . $tableName , $profileRow)) {
			$profileId  = $adapter->lastInsertId();
			$row        = $this->findRow( $profileId , "profileid" , null , false );
			if( $row ) {
				$tableContact  = new Table_Profilecoordonnees();
				$tableAvatar   = new Table_Profileavatars();
				$contactCols   = $tableContact->info(Zend_Db_Table_Abstract::COLS );
				$contactData   = array_combine($contactCols, array_pad(array(), count( $contactCols ), null ) );
				$contactRowData= array_intersect_key( $userData , $contactData);
				$contactRowData["tel_mob"]       = ( isset( $userData["phone1"] ) ) ? $userData["phone1"] : "";
				$contactRowData["tel_bureau"]    = ( isset( $userData["phone2"] ) ) ? $userData["phone2"] : "";
				$contactRowData["email"]         = ( isset( $userData["email"] ) ) ? $userData["email"] : "";
				$contactTblName                  = $tableContact->info("name");
				$contactRowData["profileid"]     = $profileId;
				$contactRowData["updateduserid"] = 0; $contactRowData["creatorid"]    = 1;
				$contactRowData["updatedate"]    = 0; $contactRowData["creationdate"] = time();
				$adapter->insert( $prefix . $contactTblName , $contactRowData);	
				//On crée aussi l'avatar
				if( !empty( $userData["avatar"] ) ) {
				  $avatarCols              = $tableAvatar->info(Zend_Db_Table_Abstract::COLS );
				  $avatarData              = array_combine( $avatarCols , array_pad(array(), count( $avatarCols ), null ) );
				  $avatarData["profileid"] = $profileId;
				  $avatarData["libelle"]   = sprintf(" Avatar de %s %s " , $userData["lastname"] , $userData["firstname"] );
				  $avatarData["filename"]  = $userData["avatar"];
				  $avatarTblName           = $tableAvatar->info("name");
				  $adapter->insert( $prefix . $avatarTblName , $avatarData );
				}
			}
		}
		return $row;
	}
	
	public function getRow( $userid = 0 , $new = true , $cached = true ) 
	{
		$table           = $this->_table;
		$adapter         = $table->getAdapter();
		$tableName       = $table->info("name");		
		if(!$userid) {
			$user        = Sirah_Fabric::getUser();
			$userid      = $user->userid;
		}	
		$prefixCacheId   = "row_".$tableName;
		$cacheId         = "pk_".$userid;
		if( ( false !== ( $cachedRow  = $this->fetchInCache($cacheId , $prefixCacheId)) ) && $cached ){
			return $cachedRow;
		}		
		$select          = $table->select()->where("userid = ?" , intval($userid));
		$row             = $table->fetchRow($select);		
		if(!$row && $new && $userid) {
			$row = $this->create($userid);
		}
		if($row && $cached) {
			$this->saveToMemory($row , $cacheId , $prefixCacheId);
		}
		return $row;		
	}
	
	public function generateMatricule( $userid = null )
	{
		$user           = Sirah_Fabric::getUser($userid);
		$userTable      = $user->getTable();
		$adapter        = $userTable->getAdapter();
		$prefix         = $userTable->info("namePrefix");
		if( ( null === $userid ) || ( intval($userid) == 0 ) ) {			
			$selectLastUser = $adapter->select()->from( $prefix . "system_users_account")->order(array("userid DESC"));
			$lastUser       = $adapter->fetchRow( $selectLastUser , array() , 5 );
			if( $lastUser ) {
				$userid     = intval( $lastUser->userid ) + 1;
			} else {
				$userid     = 1;
			}
		}	
		$uniquekey          = "Ml-".sprintf("%05d" , intval($userid));
		while( $this->findRow( $uniquekey , "matricule" , null , false ) || $userTable->find(array("username" => $uniquekey))) {
			   $userid++;
			   $uniquekey   = "Ml-".sprintf("%05d" , $userid);   
		}
		return 	$uniquekey;	
	}
	
	
	public function documents( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
				                                                                                                    "D.filedescription", "D.filesize", "D.documentid", "D.resourceid", "D.userid"))
		                                        ->joinLeft(array("C"=> $tablePrefix ."system_users_documents_categories"),"C.id = D.category", array("category"=> "C.libelle"))
		                                        ->join(array("P"=> $tablePrefix ."system_users_profile_documents"),"P.documentid = D.documentid",array())
		                                        ->where("P.profileid = ?", intval( $profileid )); 
		if( $limit ) {
			$selectDocuments->limit( intval( $limit ) );
		}
		$selectDocuments->order(array("P.profileid DESC", "D.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	
	
	public function cvdocs( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
				                                                                                                    "D.filedescription", "D.filesize", "D.documentid", "D.resourceid", "D.userid"))
				                                 ->join(array("V" => $tablePrefix ."system_users_profile_cversions"),"V.documentid = D.documentid", array( "V.numversion", "V.dateversion", "V.description"))
				                                 ->where("V.profileid = ?", intval( $profileid ));
		if( $limit ) {
			$selectDocuments->limit( intval( $limit ) );
		}
		$selectDocuments->order(array("V.profileid DESC", "V.dateversion DESC", "V.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	public function letterdocs( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			$profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDocuments   = $dbAdapter->select()->from(array("D" => $tablePrefix ."system_users_documents"), array("D.filename","D.filepath","D.filextension","D.filemetadata", "D.creationdate",
				                                                                                                    "D.filedescription", "D.filesize", "D.documentid", "D.resourceid", "D.userid"))
				                                 ->join(array("V" => $tablePrefix ."system_users_profile_letterversions"),"V.documentid = D.documentid", array( "V.numversion", "V.dateversion", "V.description"))
				                                 ->where("V.profileid = ?", intval( $profileid ));
		if( $limit ) {
			$selectDocuments->limit( intval( $limit ) );
		}
		$selectDocuments->order(array("V.profileid DESC", "V.dateversion DESC", "V.documentid DESC"));
		return $dbAdapter->fetchAll( $selectDocuments );
	}
	public function domaines( $profileid = 0 , $limit = 20 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectDomaines    = $dbAdapter->select()->from(array("UD" => $tablePrefix ."system_users_profile_domaines"), array("UD.domaineid"))
		                                         ->join(array("D"  => $tablePrefix ."system_offre_domaines"),"D.id = UD.domaineid", array("domaine"=> "D.libelle"))
		                                         ->where("UD.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectDomaines->limit( intval( $limit ) );
		}
		$selectDomaines->order(array("UD.profileid DESC", "D.libelle ASC"));
		return $dbAdapter->fetchAll( $selectDomaines );
	}
	
	public function tags( $profileid = 0 , $limit = 20 )
	{
		if( !$profileid )  {
			 $profileid = $this->profileid;
		}
		$table        = $this->_getTable();
		$dbAdapter    = $table->getAdapter();
		$tablePrefix  = $table->info("namePrefix");
		$selectTags   = $dbAdapter->select()->from(array("K" => $tablePrefix ."system_users_profile_tags"), array("K.tagid"))
		                                    ->join(array("G" => $tablePrefix ."system_general_keywords"), "G.id = K.tagid", array("keyword" => "G.libelle"))
		                                    ->where("K.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectTags->limit( intval( $limit ) );
		}
		$selectTags->order(array("K.profileid DESC"));
		return $dbAdapter->fetchAll( $selectTags );
	}
	
	public function projects( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectProjects    = $dbAdapter->select()->from(array("P"    => $tablePrefix . "system_users_profile_projects") , 
				                                        array("start"=>"FROM_UNIXTIME(P.periode_start,'%d/%m/%Y')","end"=>"FROM_UNIXTIME(P.periode_end,'%d/%m/%Y')",
				                                              "P.presentation", "P.theme","P.city","P.country","P.projectid","P.periode_end","P.periode_start"))
		                                         ->joinLeft(array("E"=> $tablePrefix ."system_entreprises"),"E.id = P.beneficiaireid", array("beneficiaire"=> "E.libelle"))
		                                         ->joinLeft(array("D"=> $tablePrefix ."system_offre_domaines"),"D.id = P.domaineid", array("domaine"=> "D.libelle"))
		                                         ->where("P.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectProjects->limit( intval( $limit ) );
		}
		$selectProjects->order(array("P.profileid","P.periode_start DESC", "P.projectid DESC"));
		return $dbAdapter->fetchAll( $selectProjects );		                  
	}
	
	public function languages( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectLanguages   = $dbAdapter->select()->from(array("L" => $tablePrefix ."system_users_profile_languages"), array("L.appreciation", "L.level","L.code","L.languageid"))
						                         ->joinLeft(array("LA"=> $tablePrefix ."system_languages"),"LA.code = L.code", array("language"=> "LA.libelle"))
						                         ->where("L.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectLanguages->limit( intval( $limit ) );
		}
		$selectLanguages->order(array("L.profileid DESC", "LA.libelle ASC", "L.languageid DESC"));
		return $dbAdapter->fetchAll( $selectLanguages );
	}
	
	public function competences( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectCompetences = $dbAdapter->select()->from(array("P" => $tablePrefix ."system_users_profile_professions"), array("P.appreciation", "P.level","P.competenceid"))
		                                         ->joinLeft(array("PR"=> $tablePrefix ."system_offre_professions"),"P.professionid = PR.id", array("profession"=> "PR.libelle"))
		                                         ->where("P.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectCompetences->limit( intval( $limit ) );
		}
		$selectCompetences->order(array("P.profileid DESC", "P.competenceid DESC"));
		return $dbAdapter->fetchAll( $selectCompetences );
	}
	
	public function certifications( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");
		$selectCertifications = $dbAdapter->select()->from(array("C" => $tablePrefix ."system_users_profile_certifications"), array("C.appreciation", "C.level","C.certificationid",
				                                                                                                                    "C.periode_end", "C.periode_start"))
		                                            ->join(array("K" => $tablePrefix ."system_general_keywords"),"K.id = C.keywordid", array("keyword"=> "K.libelle"))
		                                            ->joinLeft(array("E"=> $tablePrefix ."system_entreprises"),"E.id = C.entrepriseid", array("entreprise"=> "E.libelle"))
		                                            ->where("C.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectCertifications->limit( intval( $limit ) );
		}
		$selectCertifications->order(array("C.profileid DESC", "C.periode_start DESC", "C.periode_end DESC", "C.certificationid DESC"));
		return $dbAdapter->fetchAll( $selectCertifications );
	}
	
	public function carreers( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid )  {
			 $profileid    = $this->profileid;
		}
		$table             = $this->_getTable();
		$dbAdapter         = $table->getAdapter();
		$tablePrefix       = $table->info("namePrefix");		
		$selectCarreers    = $dbAdapter->select()->from(array("C"    => $tablePrefix . "system_users_profile_carreers") , 
				                                        array("start"=>"FROM_UNIXTIME(C.periode_start,'%d/%m/%Y')","end"=>"FROM_UNIXTIME(C.periode_end,'%d/%m/%Y')",
				                                              "C.description", "C.departement","C.email","C.current","C.country","C.carreerid","C.periode_end", "C.periode_start"))
		                                         ->joinLeft(array("E"=> $tablePrefix ."system_entreprises"),"E.id = C.entrepriseid", array("entreprise"=> "E.libelle"))
		                                         ->joinLeft(array("P"=> $tablePrefix ."system_offre_professions"),"P.id = C.professionid", array("profession"=> "P.libelle"))
		                                         ->joinLeft(array("D"=> $tablePrefix ."system_offre_domaines")   ,"D.id = C.domaineid", array("domaine"=> "D.libelle"))
		                                         ->where("C.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectCarreers->limit( intval( $limit ) );
		}
		$selectCarreers->order(array("C.profileid", "C.current DESC", "C.periode_start DESC","C.carreerid DESC"));
		return $dbAdapter->fetchAll( $selectCarreers );		                                         		
	}
	
	public function formations( $profileid = 0 , $limit = 10 )
	{
		if( !$profileid ) {
			 $profileid   = $this->profileid;
		}
		$table            = $this->_getTable();
		$dbAdapter        = $table->getAdapter();
		$tablePrefix      = $table->info("namePrefix");
		$selectFormations = $dbAdapter->select()->from(array("F"    => $tablePrefix . "system_users_profile_formations") ,
				                                       array("start"=>"FROM_UNIXTIME(F.periode_start,'%d/%m/%Y')","end"=>"FROM_UNIXTIME(F.periode_end,'%d/%m/%Y')",
						                                     "F.description" , "F.diplome","F.intitule","F.country","F.formationid","F.periode_end", "F.periode_start"))
						->join(array("E"=> $tablePrefix ."system_entreprises"),"E.id = F.entrepriseid", array("entreprise"=> "E.libelle"))
						->joinLeft(array("B"=> $tablePrefix ."system_entreprises") ,"B.id = F.beneficiaireid", array("beneficiaire"=> "B.libelle"))
						->joinLeft(array("D"=> $tablePrefix ."system_offre_domaines") ,"D.id = F.domaineid", array("domaine"=> "D.libelle"))
						->joinLeft(array("N"=> $tablePrefix ."system_offre_educations") ,"N.id = F.educationid", array("niveau"=> "N.libelle"))
						->where("F.profileid = ?", intval( $profileid ) );
		if( $limit ) {
			$selectFormations->limit( intval( $limit ) );
		}
		$selectFormations->order(array("F.profileid", "F.periode_start DESC","F.formationid DESC"));
		return $dbAdapter->fetchAll( $selectFormations );
	}    
 }