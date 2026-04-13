<?php

class RegistrephysiqueController extends Sirah_Controller_Default
{
  	public function importdbAction()
  	{
  		$this->view->title  = "Importer des données à partir d'une base de donnée source";
  		$modelLocalite      = $this->getModel("localite");
  		$modelDocument      = $this->getModel("document");
  		$model              = $this->getModel("registrephysique");
  		$modelRegistre      = $this->getModel("registre");
  		$modelTable         = $model->getTable();
  		$prefixName         = $modelTable->info("namePrefix");
  		$dbDestination      = $modelTable->getAdapter();
  		$me                 = Sirah_Fabric::getUser();
  		
  		$defaultData        = array("dbsource_host" => "localhost","dbsource_user" =>"root", "dbsource_password" => "", "annee" => null, "localiteid" => 0,
  				                    "dbsource_name" => "siget","dbsource_tablename"=>"archive", "srcpath" => "F:\webserver\\www\\rccm/data");
  		$errorMessages      = array();
  		$successMessages    = array();
  		$notSavedItems      = array("NUMERO_REGISTRE_TROP_LONG"=> array(), "DOCUMENT_PDF_MANQUANT"=> array(),"NOM_DIRIGEANT_INVALIDE" => array(),
  				                    "NOM_COMMERCIAL_INVALIDE"  => array(), "REGISTRE_EXISTANT" => array());
        
  		$savedItems         = array();
  		
  		$localites          = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "code") , array() , null , null , false );
  		if( $this->_request->isPost( )) {
  			$this->_helper->viewRenderer->setNoRender(true);
  			$this->_helper->layout->disableLayout(true);
  			
  			$postData       = $this->_request->getPost();  			
  			$dbsourceParams = array(
  					                 "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
  					                 "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
  					                 "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
  					                 "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "" ),
  					                 "isDefaultAdapter" => 0);
  			try{
  				$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
  				$dbSource->getConnection();  				
  			} catch( Zend_Db_Adapter_Exception $e ) {
  				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
  			} catch( Zend_Exception $e ) {
  				$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
  			}
  			
  			if( empty( $errorMessages )) { 				
  				$localitecode         = (isset( $postData["localiteid"]) && isset( $localites[$postData["localiteid"]] )) ? $localites[$postData["localiteid"]]: "";
  				$annee                = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )      : 0;
  				$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer" ,"zero" ,"string" ,"float" ,"empty_array" ,"null"));
  				if( $annee && $strNotEmptyValidator->isValid($localitecode)) {
  					$numeroInitial    = sprintf("BF%s%dA", $localitecode, $annee );
  					$dbSourceSelect   = $dbSource->select()->from("archive", array("analyse", "date_enregistrement", "date_deb"))
  					                                      ->where("analyse LIKE ?", "%".strip_tags( $numeroInitial )."%")->where("date_deb LIKE ?", "%".intval( $annee )."%");
  					$registres        = $dbSource->fetchAll( $dbSourceSelect );
  					
  					//print_r( $registres ); die();
  					if( count(   $registres )) {
  						foreach( $registres as $registre ) {
  							     $registreStr         = $registre["analyse"];
  							     $registreDate        = $registre["date_enregistrement"];
  							     $registreStrToArray= explode(",", $registreStr );
  							     $numRegistreStr      = ( isset( $registreStrToArray[0] )) ? $registreStrToArray[0] : "";
  							     $denominationStr     = ( isset( $registreStrToArray[1] )) ? $registreStrToArray[1] : "";
  							     $nomCommercialStr    = ( isset( $registreStrToArray[2] )) ? $registreStrToArray[2] : "";
  							     $dirigeantStr        = ( isset( $registreStrToArray[3] )) ? $registreStrToArray[3] : "";
  							     $lastItem            = ( isset( $registreStrToArray[4] )) ? $registreStrToArray[4] : "";
  							     $sourceFolder        = ( isset( $postData["srcpath"]   )) ? $postData["srcpath"] : "";
  							     $destCompletFolder   = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
  							     
  							     if( $strNotEmptyValidator->isValid( $sourceFolder ) && !is_dir( $sourceFolder )) {
  							     	 $errorMessages[] = sprintf("Le dossier source `%s` n'est pas valide, veuillez fournir un dossier valide", $sourceFolder );
  							     }  							     
  							     if($strNotEmptyValidator->isValid( $numRegistreStr ) && $strNotEmptyValidator->isValid($nomCommercialStr )) {
  							     	$numRegistreStrToArray = explode(":", $numRegistreStr );
  							     	$numRegistre       = ( isset( $numRegistreStrToArray[1])) ? strtoupper( trim($numRegistreStrToArray[1]) ) : "";
  							     	if( strlen( $numRegistre ) > 13 ) {
  							     		$notSavedItems["NUMERO_REGISTRE_TROP_LONG"][]  = $numRegistre;
  							     		continue;
  							     	}
  							     	if( $strNotEmptyValidator->isValid( $numRegistre ) ) {
  							     		//On vérifie dans la base de données de destination      
  							     		$foundRegistre = $modelRegistre->findRow( $numRegistre, "numero", null , false );
  							     		if( !$foundRegistre ) {
  							     			 //On enregistre le registre
  							     			 $insert_data                  = array();
  							     			 $insert_data["numero"]        = $numRegistre;
  							     			 $insert_data["localiteid"]    = intval( $postData["localiteid"] );
  							     			 $insert_data["type"]          = 1;
			                                 $insert_data["category"]      = "A";
			                                 $insert_data["description"]   = "";
			                                 $insert_data["creatorid"]     = $me->userid;
			                                 $insert_data["creationdate"]  = time();	
			                                 $insert_data["updateduserid"] = 0;
			                                 $insert_data["updatedate"]    = 0;
			                                 $insert_data["domaineid"]     = 1;
			                                 $successMessage               = null;
  							     			 
  							     			 //On recupère le libellé/nom commercial
  							     			 $nomCommercialStrToArray      = explode(":", $nomCommercialStr );
  							     			 $nomCommercialVal             = (isset($nomCommercialStrToArray[1])) ? trim($nomCommercialStrToArray[1]) : trim($nomCommercialStrToArray[0]);
  							     			 $nomCommercial                = stristr( $nomCommercialVal, "TELEPHONE", true  );
  							     			 if( !$strNotEmptyValidator->isValid( $nomCommercial )) {
  							     			 	preg_match('#NOM COMMERCIAL\s*:\s*(?P<nomcommercial>[^.,]+)#', $registreStr, $nomCommercialMatches);
  							     			 	if( isset( $nomCommercialMatches["nomcommercial"] )) {
  							     			 		$nomCommercial         = $nomCommercialMatches["nomcommercial"];
  							     			 	}
  							     			 }
  							     			  						     			 
  							     			 $telephone                    = str_replace(":", "", stristr($nomCommercial, "TELEPHONE", false ));
  							     			 if( !$strNotEmptyValidator->isValid( $telephone ) && ( $strNotEmptyValidator->isValid( $lastItem))) {
  							     			 	  $telephone               = str_replace("TELEPHONE :", "",stristr(trim( $dirigeantStr ), "TELEPHONE", false ));
  							     			 }
  							     			 $dirigeantName                = str_replace(":", "", stristr(trim($dirigeantStr), "DIRIGEANT", false ));
  							     			 if( !$strNotEmptyValidator->isValid( $dirigeantName ) && ( $strNotEmptyValidator->isValid( $lastItem))) {
  							     			 	  $dirigeantName           = str_replace("DIRIGEANT :", "",stristr(trim($lastItem), "DIRIGEANT", false ));
  							     			 }
  							     			 preg_match("/^([a-z]+)(?:,?\s?)?([a-z]+)?((?:\s)([a-z]))?$/i", str_replace("DIRIGEANT","", $dirigeantName ), $dirigeantNameToArray );
  							     			 
  							     			 //echo $telephone;
  							     			 //echo $dirigeantName . " et ". $telephone ."\n";
  							     			 
  							     			 //print_r( $nomCommercial ); die();
  							     			
  							     			 $insert_data["libelle"]        = $nomCommercial;
  							     			 if( $modelRegistre->findRow( $insert_data["libelle"], "libelle", null , false )) {
  							     			 	 $insert_data["libelle"]    = $insert_data["numero"]."/". $nomCommercial;
  							     			 }
  							     			 if( !$strNotEmptyValidator->isValid( $insert_data["libelle"] ) ){
  							     			 	  $notSavedItems["NOM_COMMERCIAL_INVALIDE"][]  = $numRegistre;
  							     			 	  continue;
  							     			 }
  							     		     if( !$strNotEmptyValidator->isValid( $dirigeantName ) ){
  							     			 	  $notSavedItems["NOM_DIRIGEANT_INVALIDE"][]  = $numRegistre;
  							     			 	  continue;
  							     			 }
  							     			 //print_r( $insert_data["libelle"] ); die();
  							     			 $registreZendDate          = new Zend_Date( sprintf("%s-%s-%d", "01", "01", $annee ), Zend_Date::DATES , "fr_FR" );
  							     			 $insert_data["date"]       = $registreZendDate->get(Zend_Date::TIMESTAMP);
  							     			     if($dbDestination->insert( $prefixName . "rccm_registre", $insert_data )) {
  							     			     	$savedItems[]       = sprintf("Le registre numéro %s a été enregistré avec succès sans son document", $insert_data["numero"]);
  							     			     	$registreid         = $dbDestination->lastInsertId();
  							     			     	//On enregistre les informations de l'exploitant
  							     			     	  							     			     		
  							     			     	$exploitant_data["datenaissance"] =  0;
  							     			     	$exploitant_data["lieunaissance"] = "";
  							     			     	$exploitant_data["marital_status"]= "";
  							     			     	$exploitant_data["nom"]           = ( isset( $dirigeantNameToArray[0])) ? $dirigeantNameToArray[0] : str_replace("DEMINATION : ","",$denominationStr);
  							     			     	$exploitant_data["prenom"]        = ( isset( $dirigeantNameToArray[1])) ? $dirigeantNameToArray[1] : "";
  							     			     	$exploitant_data["adresse"]       = "";
  							     			     	$exploitant_data["city"]          = 0;
  							     			     	$exploitant_data["country"]       = "BF";
  							     			     	$exploitant_data["email"]         = "";
  							     			     	$exploitant_data["telephone"]     = $telephone;
  							     			     	$exploitant_data["structure"]     = "";
  							     			     	$exploitant_data["creatorid"]     = $me->userid;
  							     			     	$exploitant_data["creationdate"]  = time();
  							     			     	$exploitant_data["updateduserid"] = 0;
  							     			     	$exploitant_data["updatedate"]    = 0;
  							     			     	
  							     			     	if( $dbDestination->insert( $prefixName . "rccm_registre_exploitants", $exploitant_data ) ) {
  							     			     		$exploitantid                 = $dbDestination->lastInsertId();
  							     			     		if( $dbDestination->insert( $prefixName . "rccm_registre_physique", array("registreid" => $registreid, "exploitantid" => $exploitantid ))) {
  							     			     			$registreDocPath          = trim( $sourceFolder ). DS . strtoupper( $localitecode ). DS .strtoupper( $numRegistre ).".pdf";
  							     			     			$destPathComplet          = $destCompletFolder . DS. strtoupper( $numRegistre )."_complet.pdf";
  							     			     			if( file_exists( $registreDocPath ) && ( copy( $registreDocPath, $destPathComplet ))) {
  							     			     				$documentData                     = array();
  							     			     				$documentData["userid"]           = $me->userid;
  							     			     				$documentData["category"]         = 1;
  							     			     				$documentData["resource"]         = "registrephysique";
  							     			     				$documentData["resourceid"]       = 0;
  							     			     				$documentData["filedescription"]  = $numRegistre;
  							     			     				$documentData["filemetadata"]     = "";
  							     			     				$documentData["creationdate"]     = time();
  							     			     				$documentData["creatoruserid"]    = $me->userid;
  							     			     				$completFilename                  = strtoupper( $numRegistre )."_complet";
  							     			     				$documentData["filename"]         = $modelDocument->rename( $completFilename, $me->userid );
  							     			     				$documentData["filepath"]         = $destPathComplet;
  							     			     				$documentData["access"]           = 6 ;
  							     			     				$documentData["filextension"]     = "pdf";
  							     			     				$documentData["filesize"]         = @filesize($registreDocPath);
  							     			     				if( $dbDestination->insert( $prefixName . "system_users_documents", $documentData ) ) {
  							     			     					$documentid                   = $dbDestination->lastInsertId();
  							     			     					$dbDestination->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
  							     			     				    $savedItems[]                 = sprintf("Le registre numéro %s a été enregistré avec succès avec son document", $insert_data["numero"]);
  							     			     				}
  							     			     				    $pdfComplet                   = Zend_Pdf::load( $destPathComplet );
  							     			     				    $pdfFormulaire                = new Zend_Pdf();
  							     			     				    $extractorPage                = new Zend_Pdf_Resource_Extractor();
  							     			     				    $pageFormulaire               = $extractorPage->clonePage($pdfComplet->pages[0]);
  							     			     				    $pdfFormulaire->pages[]       = $pdfFormulaire;
  							     			     				    $miniDocPath                  = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini". DS. strtoupper( $numRegistre )."_formulaire.pdf";
  							     			     				    
  							     			     				    if( $pdfFormulaire->save( $miniDocPath ) ) {
  							     			     				    	$miniFilename             = strtoupper( $numRegistre )."_mini";
  							     			     				        $documentData["filename"] = $modelDocument->rename( $miniFilename, $me->userid );
  							     			     				        $documentData["filepath"] = $miniDocPath;
  							     			     				        $documentData["access"]   = 0 ;
  							     			     				        if( $dbDestination->insert( $prefixName . "system_users_documents", $documentData )) {
  							     			     				        	$documentid           = $dbDestination->lastInsertId();
  							     			     				        	$dbDestination->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 0));
  							     			     				        }
  							     			     				    }
  							     			     				  							     		     				
  							     			     			} else {
  							     			     				$notSavedItems["DOCUMENT_PDF_MANQUANT"][]  = $numRegistre;
  							     			     				continue;
  							     			     			}  							     			     			
  							     			     			   
  							     			     		}
  							     			     	}  							    		     	       
  							     			     }  							     			
  							     		} else {
  							     			$notSavedItems["REGISTRE_EXISTANT"][]  = $numRegistre;
  							     		}
  							     	}
  							     }
  						}
  					} else {
  						$errorMessages[]  = "Aucun registre n'a été retrouvé conformément aux paramètres fournis";
  					}
  				}				 				  				
  			}
  			if( !count( $errorMessages)) {
  				$totalNotSaved  = count($registres ) - count($savedItems) ;
  				echo "-------------------------------------------------- RAPPORT D'IMPORT DES DONNEES A PARTIR D'UNE ANCIENNE BASE DE DONNEES-----------------------------------------<br/>\n";
  				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES IMPORTES       :".count($savedItems)." <br/> \n";
  				echo "----------------LOCALITE                           :".$localitecode." <br/> \n";
  				echo "----------------ANNEE                              :".$annee." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".$totalNotSaved." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES EXISTANTS DEJA :".count($notSavedItems["REGISTRE_EXISTANT"])." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES NUMERO REGISTRE TROP LONG :".count($notSavedItems["NUMERO_REGISTRE_TROP_LONG"])." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES NOM COMMERCIAL INVALIDE :".count($notSavedItems["REGISTRE_EXISTANT"])." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES NOM DIRIGEANT  INVALIDE :".count($notSavedItems["NOM_DIRIGEANT_INVALIDE"])." <br/> \n";
  				echo "----------------TOTAL DES REGISTRES DOCUMENT PDF MANQUANT:".count($notSavedItems["DOCUMENT_PDF_MANQUANT"])." <br/><br/><br/> \n"; 				
  				
  				if( count( $notSavedItems["NUMERO_REGISTRE_TROP_LONG"] )) {
  					echo "1-) ------------------------------------------------- LISTE DES NUMEROS DE REGISTRES TROP LONGS-----------------------------------------<br/>\n
  					          <ul>";
  					foreach( $notSavedItems["NUMERO_REGISTRE_TROP_LONG"] as $numRegistreItem ) {
  						     echo " <li> ".$numRegistreItem ." </li>";
  					}
  					echo "    </ul> <br/> <br/>";
  				}
  				if( count( $notSavedItems["NOM_COMMERCIAL_INVALIDE"] )) {
  					echo "2-) ------------------------------------------------- DES REGISTRES NOM COMMERCIAL INVALIDE-----------------------------------------<br/>\n
  					<ul>";
  					foreach( $notSavedItems["NOM_COMMERCIAL_INVALIDE"] as $numRegistreItem ) {
  						echo " <li> ".$numRegistreItem ." </li>";
  					}
  					echo "    </ul> <br/> <br/>";
  				}
  				if( count( $notSavedItems["NOM_DIRIGEANT_INVALIDE"] )) {
  					echo "3-) ------------------------------------------------- DES REGISTRES NOM DIRIGEANT  INVALIDE-----------------------------------------<br/>\n
  					<ul>";
  					foreach( $notSavedItems["NOM_DIRIGEANT_INVALIDE"] as $numRegistreItem ) {
  						echo " <li> ".$numRegistreItem ." </li>";
  					}
  					echo "    </ul> <br/> <br/>";
  				}
  				if( count( $notSavedItems["DOCUMENT_PDF_MANQUANT"] )) {
  					echo "4-) ------------------------------------------------- DES REGISTRES DOCUMENT PDF MANQUANT-----------------------------------------<br/>\n
  					<ul>";
  					foreach( $notSavedItems["DOCUMENT_PDF_MANQUANT"] as $numRegistreItem ) {
  						echo " <li> ".$numRegistreItem ." </li>";
  					}
  					echo "    </ul> <br/> <br/>";
  				}
  				
  				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
  				exit;
  				
  			} else  {
  				if( $this->_request->isXmlHttpRequest() ) {
  					$this->_helper->viewRenderer->setNoRender(true);
  					echo ZendX_JQuery::encodeJson(array("error" => implode(" " , $messages )));
  					exit;
  				}
  				foreach( $errorMessages as $message) {
  					     $this->_helper->Message->addMessage( $message , "error" ) ;
  				}
  			}
  			$defaultData       = $postData;
  		}
  		
  		$this->view->data      = $defaultData;
  		$this->view->localites = $localites;
  		$this->render("import");
  	}
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Liste des registres de type Personnes Physiques"  ;
		
		$model              = $this->getModel("registrephysique");
		$modelLocalite      = $this->getModel("localite");
		$modelDomaine       = $this->getModel("domaine");
		$me                 = Sirah_Fabric::getUser();
		
		$registres          = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		
		
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;		
		
		$filters            = array("libelle"=> null,"numero" => null, "domaineid" => null, "localiteid" => null,"creatorid" => 0,"annee" => 0,"nom" => null,"prenom" => null );		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( !$me->isAdmin() ) {
			 $filters["creatorid"] = $me->userid;
		}		
		$registres                 = $model->getList( $filters , $pageNum , $pageSize);
		$paginator                 = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->registres = $registres;
		$this->view->domaines  = $modelDomaine->getSelectListe( "Selectionnez un domaine"  , array("domaineid" , "libelle") , array() , null , null , false );
		$this->view->localites = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "libelle") , array() , null , null , false );
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title         = "Enregistrer un registre de type `Personnes Physiques`";
		
		$model                     = $this->getModel("registre");
		$modelExploitant           = $this->getModel("exploitant");
		$modelDomaine              = $this->getModel("domaine");
		$modelLocalite             = $this->getModel("localite");
		
		$registreDefaultData                 = $model->getEmptyData();
		$exploitantDefaultData               = $modelExploitant->getEmptyData();
		$domaines                            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites                           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$defaultData                         = array_merge( $exploitantDefaultData, $registreDefaultData );
		$defaultData["date_year"]            = date("Y");
		$defaultData["date_month"]           = null;
		$defaultData["date_day"]             = null;
		$defaultData["date_naissance_year"]  = null;
		$defaultData["date_naissance_month"] = null;
		$defaultData["date_naissance_day"]   = null;
		$errorMessages                       = array();
		
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$registre_data     = array_merge( $registreDefaultData  , array_intersect_key( $postData ,  $registreDefaultData   ));
			$exploitant_data   = array_merge( $exploitantDefaultData, array_intersect_key( $postData ,  $exploitantDefaultData ));
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator           = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
				
			$numero                         = $stringFilter->filter( $registre_data["numero"]  );
			$libelle                        = $stringFilter->filter( $registre_data["libelle"] );
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]             = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false )) {
				$errorMessages[]             = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$registre_data["numero"]     = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]             = " Veuillez entrer un nom commercial valide pour ce registre";
			} else {
				$registre_data["libelle"]    = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $exploitant_data["nom"] ) || !$strNotEmptyValidator->isValid( $exploitant_data["prenom"] )  ) {
				$errorMessages[]             = " Veuillez entrer un nom de famille et/ou prénom valide pour l'exploitant";
			}  
			if( !intval( $registre_data["localiteid"] ) || !isset( $localites[$registre_data["localiteid"]]) ) {
				$errorMessages[]             = "Veuillez sélectionner une localité valide";
			} else {
				$registre_data["localiteid"] = intval( $registre_data["localiteid"] ) ;
			}
					
			$dateYear                        = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                       = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                         = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";										
			$dateString                      = sprintf("%04d-%02d-%s", $dateYear, $dateMonth, $dateDay );
			$zendDate                        = ( Zend_Date::isDate( $dateString , "YYYY-MM-dd" ) ) ? new Zend_Date( $dateString, Zend_Date::DATES, "en_US") : null;
			
			$registre_data["date"]           = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;			
			$registre_data["type"]           = 1;
			$registre_data["category"]       = $stringFilter->filter( $registre_data["category"]     );
			$registre_data["description"]    = $stringFilter->filter( $registre_data["description"]  );
			$registre_data["creatorid"]      = $me->userid;
			$registre_data["creationdate"]   = time();	
			$registre_data["updateduserid"]  = 0;
			$registre_data["updatedate"]     = 0;
			$registre_data["domaineid"]      = intval( $registre_data["domaineid"] ) ;
			
			$docUpload                       = new Zend_File_Transfer();
			$docUpload->addValidator("Count"    , false , 3 );
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->addValidator("Size"     , false , array("max" => "10MB"));
			$docUpload->addValidator("FilesSize", false , array("max" => "10MB"));
			if( !$docUpload->isUploaded("docmini") ) {
				$errorMessages[]             = "Le document formulaire n'a pas été fourni";
			}
			if( !$docUpload->isUploaded("docoriginal")) {
				$errorMessages[]             = "Le document complet n'a pas été fourni";
			}			
			if( !count( $errorMessages  )) {
				if(   $dbAdapter->insert( $prefixName . "rccm_registre", $registre_data) ) {
					  $registreid            = $dbAdapter->lastInsertId();					  
					  //On enregistre les informations de l'exploitant					  
					  $dateNaissanceYear                = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
					  $dateNaissanceMonth               = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
					  $dateNaissanceDay                 = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
					  $dateNaissance                    = sprintf("%04d-%02d-%s", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
					  $zendDateNaissance                = ( Zend_Date::isDate( $dateNaissance, "YYYY-MM-dd" ) ) ? new Zend_Date( $dateNaissance, Zend_Date::DATES, "en_US") : null;
					  
					  $exploitant_data["datenaissance"] = ( null != $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::TIMESTAMP) : 0;
					  $exploitant_data["lieunaissance"] = $stringFilter->filter( $exploitant_data["lieunaissance"]  );
					  $exploitant_data["marital_status"]= $stringFilter->filter( $exploitant_data["marital_status"] );
					  $exploitant_data["nom"]           = $stringFilter->filter( $exploitant_data["nom"] );
					  $exploitant_data["prenom"]        = $stringFilter->filter( $exploitant_data["prenom"]  );
					  $exploitant_data["adresse"]       = $stringFilter->filter( $exploitant_data["adresse"] );
					  $exploitant_data["city"]          = 0;
					  $exploitant_data["country"]       = "BF";
					  $exploitant_data["email"]         = "";
					  $exploitant_data["telephone"]     = $stringFilter->filter( $exploitant_data["telephone"] );
					  $exploitant_data["structure"]     = "";
					  $exploitant_data["creatorid"]     = $me->userid;
					  $exploitant_data["creationdate"]  = time();
					  $exploitant_data["updateduserid"] = 0;
					  $exploitant_data["updatedate"]    = 0;
					  
					  if( $dbAdapter->insert( $prefixName . "rccm_registre_exploitants", $exploitant_data ) ) {
					  	  $exploitantid                 = $dbAdapter->lastInsertId();
					  	  if( $dbAdapter->insert( $prefixName . "rccm_registre_physique", array("registreid" => $registreid, "exploitantid" => $exploitantid ))) {
					  	  	
					  	  	   //On essaie d'enregistrer les documents du registre
					  	  	  $modelDocument                  = $this->getModel("document");
					  	  	  $cleanRegistreNumero            = preg_replace("/\s/", "", $registre_data["numero"] );
					  	  	  $miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini";
					  	  	  $orginalDocPathroot             = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
					  	  	  
					  	  	  $documentData                   = array();
					  	  	  $documentData["userid"]         = $me->userid;
					  	  	  $documentData["category"]       = 1;
					  	  	  $documentData["resource"]       = "registrephysique";
					  	  	  $documentData["resourceid"]     = 0;
					  	  	  $documentData["filedescription"]= $registre_data["numero"];
					  	  	  $documentData["filemetadata"]   = "";
					  	  	  $documentData["creationdate"]   = time();
					  	  	  $documentData["creatoruserid"]  = $me->userid;
					  	  	  					  	  	  					  	  	  					  	  	  
					  	  	  $searchIvalidStr          = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					  	  	  $replace                  = array ('e','a','i','u','o','n','y','c','-','','-');					  	  	  
					  	  	  $miniDocPathFilename      = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docmini"    , false ));
					  	  	  $originalDocPathFilename  = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docoriginal", false ));
					  	  	  $miniDocPath              = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					  	  	  $originalDocPath          = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;
					  	  	  $docUpload->addFilter("Rename", array("target" => $miniDocPath    , "overwrite" => true), "docmini");
					  	  	  
					  	  	  if( !$docUpload->isUploaded("docmini") ) {
					  	  	  	   $errorMessages[]     = "Le mini document(formulaire) n'a pas été transféré";
					  	  	  } else {
					  	  	  	   $docUpload->receive("docmini");
					  	  	  	   if( $docUpload->isReceived( "docmini") ) {
					  	  	  	   	   $miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
					  	  	  	   	   $miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
					  	  	  	   	   $miniFileSize                     = $docUpload->getFileSize("docmini");
					  	  	  	   	   $miniDocumentData                 = $documentData;
					  	  	  	   	   $miniFilename                     = $registre_data["numero"]."_mini";
					  	  	  	   	   $miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename, $me->userid );
					  	  	  	   	   $miniDocumentData["filepath"]     = $miniDocPath ;
					  	  	  	   	   $miniDocumentData["access"]       = 0 ;
					  	  	  	   	   $miniDocumentData["filextension"] = $miniDocExtension;
					  	  	  	   	   $miniDocumentData["filesize"]     = floatval( $miniFileSize );
					  	  	  	   	   if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
					  	  	  	   	   	   $documentid                   = $dbAdapter->lastInsertId();
					  	  	  	   	   	   $dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 0 ));
					  	  	  	   	   } else {
					  	  	  	   	   	   $errorMessages[]              = "Les informations du mini document ont été partiellement enregistrées";
					  	  	  	   	   }					  	  	  	   	
					  	  	  	   } else {
					  	  	  	   	   $errorMessages[] = "Le mini document(formulaire) n'a pas été reçu par le serveur";
					  	  	  	   }
					  	  	  }	
					  	  	  if( !$docUpload->isUploaded("docoriginal") ) {
					  	  	  	$errorMessages[]     = "Le  document complet n'a pas été transféré";
					  	  	  } else {
					  	  	  	$docUpload->addFilter("Rename", array("target" => $originalDocPath, "overwrite" => true), "docoriginal");
					  	  	  	$docUpload->receive("docoriginal");
					  	  	  	if( $docUpload->isReceived("docoriginal") ) {
					  	  	  		$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
					  	  	  		$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
					  	  	  		$originalFileSize                     = $docUpload->getFileSize("docoriginal");
					  	  	  		$originalDocumentData                 = $documentData;
					  	  	  		$originalFilename                     = $registre_data["numero"]."_complet";
					  	  	  		$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename , $me->userid );
					  	  	  		$originalDocumentData["filepath"]     = $originalDocPath;
					  	  	  		$originalDocumentData["access"]       = 6;
					  	  	  		$originalDocumentData["filextension"] = $originalDocExtension;
					  	  	  		$originalDocumentData["filesize"]     = floatval( $originalFileSize );
					  	  	  		if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData) ) {
					  	  	  			$documentid                       = $dbAdapter->lastInsertId();
					  	  	  			$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
					  	  	  		} else {
					  	  	  			$errorMessages[]                  = "Les informations du document complet ont été partiellement enregistrées";
					  	  	  		}					  	  	  	
					  	  	  	} else {
					  	  	  		    $errorMessages[]                  = "Le document complet n'a pas été reçu par le serveur";
					  	  	  	}
					  	  	  }				  	  	  
					  	  	  if( !count( $errorMessages ) ) {
					  	  	  	  if( $this->_request->isXmlHttpRequest() ) {
					  	  	  		  $this->_helper->viewRenderer->setNoRender(true);
					  	  	  		  $this->_helper->layout->disableLayout(true);
					  	  	  		  echo ZendX_JQuery::encodeJson(array("success" => "Les informations du registre de type physique ont été enregistrées avec succès"));
					  	  	  		  exit;
					  	  	  	   }
					  	  	  	      $this->setRedirect("Les informations du registre de type physique ont été enregistrées avec succès", "success" );
					  	  	  	      $this->redirect("registrephysique/infos/id/" . $registreid );					  	  	  	
					  	  	  }					  	  	  					  	  	  					  	  	  					  	  	
					  	  } else {
					  	  	$errorMessages[]= " Les informations du registre ont été partiellement enregistrées, veuillez reprendre l'opération";
					  	  }
					  } else {
					  	$errorMessages[]    = " Les informations de l'exploitant n'ont pas été enregistrées, veuillez reprendre l'opération";
					  }					  					  					 					
				}  else {
					    $errorMessages[]    = " Les informations du registre n'ont pas été enregistrées, veuillez reprendre l'opération";
				}
			} 
			            $defaultData        = array_merge( $defaultData , $postData );
		}		
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data      = $defaultData;
		$this->view->domaines  = $domaines;
		$this->view->localites = $localites;
	}
	
	
	public function editAction()
	{
		$this->view->title = " Mettre à jour les informations du registre ";
		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id", 0)));
		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelExploitant       = $this->getModel("exploitant");
		$modelDomaine          = $this->getModel("domaine");
		$modelLocalite         = $this->getModel("localite");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$physique              = $modelPhysique->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$physique ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registrephysique/list");
		}
		$domaines            = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$localites           = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "libelle"), array() , 0 , null , false);
		$exploitant          = $modelExploitant->findRow( $physique->exploitantid , "exploitantid", null , false );		
		$registreData        = $registre->toArray();
		$physiqueData        = $physique->toArray();
		$exploitantData      = $exploitant->toArray();
		$defaultData         = array_merge( $exploitantData, $physiqueData, $registreData );
		$errorMessages       = array();  
		
		$defaultData["date_year"]            = date("Y", $registre->date);
		$defaultData["date_month"]           = date("m", $registre->date);
		$defaultData["date_day"]             = date("d", $registre->date);
		$defaultData["date_naissance_year"]  = date("Y", $exploitant->datenaissance);
		$defaultData["date_naissance_month"] = date("m", $exploitant->datenaissance);
		$defaultData["date_naissance_day"]   = date("d", $exploitant->datenaissance);
		
		if( $this->_request->isPost()) {
			$postData             = $this->_request->getPost();
			$update_registre_data = array_merge( $registreData, array_intersect_key( $postData,  $registreData) );
			$update_physique_data = array_merge( $physiqueData, array_intersect_key( $postData,  $physiqueData) );
			$update_exploitant_data = array_merge($exploitantData, array_intersect_key( $postData,  $exploitantData) );
			$me                   = Sirah_Fabric::getUser();
			$userTable            = $me->getTable();
			$dbAdapter            = $userTable->getAdapter();
			$prefixName           = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$numero               = $stringFilter->filter( $update_registre_data["numero"]  );
			$libelle              = $stringFilter->filter( $update_registre_data["libelle"] );
			if(!$strNotEmptyValidator->isValid( $numero )) {
				$errorMessages[]                     = " Veuillez entrer un numéro de registre valide";
			} elseif( $model->findRow( $numero , "numero" , null , false ) && ( $registre->numero != $numero ) ) {
				$errorMessages[]                     = sprintf(" Un registre existant porte le numéro %s , veuillez entrer un numéro différent ", $numero );
			} else {
				$update_registre_data["numero"]      = $numero;
			}
			if(!$strNotEmptyValidator->isValid( $libelle )) {
				$errorMessages[]                     = " Veuillez entrer un nom commercial valide pour ce registre";
			} else {
				$update_registre_data["libelle"]     = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $update_exploitant_data["nom"] ) || !$strNotEmptyValidator->isValid( $update_exploitant_data["prenom"] )  ) {
				$errorMessages[]                     = " Veuillez entrer un nom de famille et/ou prénom valide pour l'exploitant";
			}
			if( !intval( $update_registre_data["localiteid"] ) || !isset( $localites[$update_registre_data["localiteid"]]) ) {
				$errorMessages[]                     = "Veuillez sélectionner une localité valide";
			} else {
				$update_registre_data["localiteid"]  = intval( $update_registre_data["localiteid"] ) ;
			}
			 
			$dateYear                                = (isset( $postData["date_year"] ))? $stringFilter->filter($postData["date_year"])  : "0000";
			$dateMonth                               = (isset( $postData["date_month"]))? $stringFilter->filter($postData["date_month"]) : "00";
			$dateDay                                 = (isset( $postData["date_day"]) && ( $postData["date_day"] != "00" ))? $stringFilter->filter($postData["date_day"]) : "05";
			$dateString                              = sprintf("%04d-%02d-%s", $dateYear, $dateMonth, $dateDay );
			$zendDate                                = ( Zend_Date::isDate( $dateString , "YYYY-MM-dd" ) ) ? new Zend_Date( $dateString, Zend_Date::DATES, "en_US") : null;
				
			$update_registre_data["domaineid"]       = intval( $update_registre_data["domaineid"] ) ;
			$update_registre_data["date"]            = ( null != $zendDate ) ? $zendDate->get(Zend_Date::TIMESTAMP) : 0;
			$update_registre_data["type"]            = 1;
			$update_registre_data["description"]     = $stringFilter->filter( $update_registre_data["description"]  );
			$update_registre_data["updateduserid"]   = $me->userid;
			$update_registre_data["updatedate"]      = time();
			
			
			//On enregistre les informations de l'exploitant
			$dateNaissanceYear                       = (isset( $postData["date_naissance_year"] ))? $stringFilter->filter($postData["date_naissance_year"])  : "0000";
			$dateNaissanceMonth                      = (isset( $postData["date_naissance_month"]))? $stringFilter->filter($postData["date_naissance_month"]) : "00";
			$dateNaissanceDay                        = (isset( $postData["date_naissance_day"]) && ( $postData["date_naissance_day"] != "00" ))? $stringFilter->filter($postData["date_naissance_day"]) : "05";
			$dateNaissance                           = sprintf("%04d-%02d-%s", $dateNaissanceYear, $dateNaissanceMonth, $dateNaissanceDay );
			$zendDateNaissance                       = ( Zend_Date::isDate( $dateNaissance, "YYYY-MM-dd" ) ) ? new Zend_Date( $dateNaissance, Zend_Date::DATES, "en_US") : null;
				
			$update_exploitant_data["datenaissance"] = ( null != $zendDateNaissance ) ? $zendDateNaissance->get(Zend_Date::TIMESTAMP) : 0;
			$update_exploitant_data["lieunaissance"] = $stringFilter->filter( $update_exploitant_data["lieunaissance"]  );
			$update_exploitant_data["marital_status"]= $stringFilter->filter( $update_exploitant_data["marital_status"] );
			$update_exploitant_data["nom"]           = $stringFilter->filter( $update_exploitant_data["nom"] );
			$update_exploitant_data["prenom"]        = $stringFilter->filter( $update_exploitant_data["prenom"]  );
			$update_exploitant_data["adresse"]       = $stringFilter->filter( $update_exploitant_data["adresse"] );
			$update_exploitant_data["city"]          = 0;
			$update_exploitant_data["country"]       = "BF";
			$update_exploitant_data["email"]         = "";
			$update_exploitant_data["telephone"]     = $stringFilter->filter( $update_exploitant_data["telephone"] );
			$update_exploitant_data["structure"]     = "";						
			$update_exploitant_data["updateduserid"]= $me->userid;
			$update_exploitant_data["updatedate"]   = time();
			
			$docUpload                              = new Zend_File_Transfer();
			$docUpload->addValidator("Extension", false , array("xls", "xlxs","pdf", "png", "gif", "jpg", "docx", "doc","bmp"));
			$docUpload->setOptions(array("ignoreNoFile" => true));
			
			if(isset(  $update_registre_data["registreid"])) {
				unset( $update_registre_data["registreid"] );
			}
			if(isset(  $update_exploitant_data["exploitantid"])) {
				unset( $update_exploitant_data["exploitantid"] );
			}
			 
			$registre->setFromArray(   $update_registre_data );
			$exploitant->setFromArray( $update_exploitant_data );
			if(empty($errorMessages)) {
				if( $registre->save() && $exploitant->save() ) {
					
					//On essaie d'enregistrer les documents du registre
					$modelDocument                  = $this->getModel("document");
					$cleanRegistreNumero            = preg_replace("/\s/", "", $update_registre_data["numero"] );
					$miniDocPathroot                = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "mini";
					$orginalDocPathroot             = APPLICATION_DATA_PATH . DS . "registres" . DS . "physiques". DS . "original";
					 
					$documentData                   = array();
					$documentData["userid"]         = $me->userid;
					$documentData["category"]       = 1;
					$documentData["resource"]       = "registrephysique";
					$documentData["resourceid"]     = 0;
					$documentData["filedescription"]= $registre_data["numero"];
					$documentData["filemetadata"]   = "";
					$documentData["creationdate"]   = time();
					$documentData["creatoruserid"]  = $me->userid;
					 
					$searchIvalidStr                = array ('@(é|è|ê|ë|Ê|Ë)@','@(á|ã|à|â|ä|Â|Ä)@i','@(ì|í|i|i|î|ï|Î|Ï)@i','@(ú|û|ù|ü|Û|Ü)@i','@(ò|ó|õ|ô|ö|Ô|Ö)@i','@(ñ|Ñ)@i','@(ý|ÿ|Ý)@i','@(ç)@i','!\s+!','@(^a-zA-Z0-9_)@');
					$replace                        = array ('e','a','i','u','o','n','y','c','-','','-');
					$miniDocPathFilename            = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docmini"    , false ));
					$originalDocPathFilename        = preg_replace( $searchIvalidStr, $replace, $docUpload->getFileName("docoriginal", false ));
					$miniDocPath                    = $miniDocPathroot    . DS . time() . "_" .$miniDocPathFilename;
					$originalDocPath                = $orginalDocPathroot . DS . time() . "_" .$originalDocPathFilename;					
					$docUpload->addFilter("Rename", array("target"=> $miniDocPath,"overwrite" => true), "docmini");
										 
					if( $docUpload->isUploaded("docmini") ) {						
						$docUpload->receive("docmini");						
						if( $docUpload->isReceived( "docmini") ) {
							$miniDocExtension                 = Sirah_Filesystem::getFilextension( $miniDocPathFilename );
							$miniTmpFilename                  = Sirah_Filesystem::getName( $miniDocPathFilename);
							$miniFileSize                     = $docUpload->getFileSize("docmini");
							$miniDocumentData                 = $documentData;
							$miniFilename                     = $cleanRegistreNumero."_mini";
							$miniDocumentData["filename"]     = $modelDocument->rename( $miniFilename , $me->userid );
							$miniDocumentData["filepath"]     = $miniDocPath ;
							$miniDocumentData["access"]       = 0;
							$miniDocumentData["filextension"] = $miniDocExtension;
							$miniDocumentData["filesize"]     = floatval( $miniFileSize );
							
							$dbAdapter->delete($prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=0"));
							$dbAdapter->delete($prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access = 0 AND registreid='".$registreid."')");
							
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $miniDocumentData ) ) {
								$documentid                   = $dbAdapter->lastInsertId();
								$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid,"documentid" => $documentid, "access" => 0));
							} else {
								$errorMessages[]              = "Les informations du mini document ont été partiellement enregistrées";
							}
						}  
					}
					$docUpload->addFilter("Rename", array("target"=> $originalDocPath,"overwrite" => true), "docoriginal");
					if( $docUpload->isUploaded("docoriginal") ) {
						$docUpload->receive("docoriginal");
						if( $docUpload->isReceived("docoriginal") ) {
							$originalDocExtension                 = Sirah_Filesystem::getFilextension( $originalDocPathFilename );
							$originalTmpFilename                  = Sirah_Filesystem::getName( $originalDocPathFilename);
							$originalFileSize                     = $docUpload->getFileSize("docoriginal");
							$originalDocumentData                 = $documentData;
							$originalFilename                     = $cleanRegistreNumero."_complet";
							$originalDocumentData["filename"]     = $modelDocument->rename( $originalFilename , $me->userid );
							$originalDocumentData["filepath"]     = $originalDocPath;
							$originalDocumentData["access"]       = 6;
							$originalDocumentData["filextension"] = $originalDocExtension;
							$originalDocumentData["filesize"]     = floatval( $originalFileSize );
							
							$dbAdapter->delete( $prefixName . "rccm_registre_documents", array("registreid=".$registreid, "access=6"));
							$dbAdapter->delete( $prefixName . "system_users_documents" , "documentid IN (SELECT documentid FROM ".$prefixName."rccm_registre_documents WHERE access=6 AND registreid='".$registreid."')");
							
							if( $dbAdapter->insert( $prefixName . "system_users_documents", $originalDocumentData) ) {
								$documentid                     = $dbAdapter->lastInsertId();
								$dbAdapter->insert( $prefixName . "rccm_registre_documents", array("registreid" => $registreid, "documentid" => $documentid, "access" => 6));
							} else {
								$errorMessages[]                = "Les informations du document complet ont été partiellement enregistrées";
							}
						} 
					}					
					if( !count( $errorMessages ) ) {
						if( $this->_request->isXmlHttpRequest()) {
							$this->_helper->viewRenderer->setNoRender(true);
							$this->_helper->layout->disableLayout(true);
							$jsonErrorArray            = $update_data;
							$jsonErrorArray["success"] = "Les informations du registre ont été mises à jour avec succès";
							echo ZendX_JQuery::encodeJson( $jsonErrorArray );
							exit;
						}
						$this->setRedirect("Les informations du registre ont été mises à jour avec succès", "success" );
						$this->redirect("registrephysique/infos/id/".$registreid );
					}																										
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("registrephysique/infos/id/".$localiteid);
				}
			} else {
				    $defaultData   = array_merge( $update_physique_data, $update_exploitant_data, $update_registre_data );				
			}					
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
				exit;
			}
			foreach( $errorMessages as $message) {
				     $this->_helper->Message->addMessage($message) ;
			}
		}
		$this->view->data        = $defaultData;
		$this->view->localiteid  = $localiteid;
		$this->view->domaines    = $domaines;
		$this->view->localites   = $localites;
	}	
 		
		
	public function infosAction()
	{		
		$registreid        = intval($this->_getParam("registreid", $this->_getParam("id" , 0)));		
		if(!$registreid ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registres/list");
		}		
		$model                 = $this->getModel("registre");
		$modelPhysique         = $this->getModel("registrephysique");
		$modelExploitant       = $this->getModel("exploitant");
 	
		$registre              = $model->findRow( $registreid, "registreid" , null , false);
		$physique              = $modelPhysique->findRow( $registreid, "registreid", null , false )	;	
		if(!$registre || !$physique ) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("registrephysique/list");
		}
		$exploitant            = $modelExploitant->findRow( $physique->exploitantid , "exploitantid", null , false );		
		$registreData          = $registre->toArray();
		$physiqueData          = $physique->toArray();
		$exploitantData        = $exploitant->toArray();
		$defaultData           = array_merge( $exploitantData, $physiqueData, $registreData );
		$this->view->data      = $defaultData;
		$this->view->registre  = $registre;
		$this->view->registreid= $registreid;
		$this->view->exploitant= $exploitant;
		$this->view->domaine   = $registre->findParentRow("Table_Domaines");
		$this->view->localite  = $registre->findParentRow("Table_Localites");
		$this->view->documents = $registre->documents();
		$this->view->title     = sprintf("Les informations du registre numero %s", $registre->numero);
		$this->view->columns   = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("registre");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("registreids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$registre                 = $model->findRow( $id , "registreid" , null , false );
				if( $registre  ) {
					if(!$registre->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée : Le registre id#$id n'a pas été supprimé";
					} else {
						$dbAdapter->delete($prefixName."rccm_registre_physique"   , "registreid=".$id);						
						$dbAdapter->delete($prefixName."rccm_registre_exploitants", "exploitantid IN (SELECT exploitantid FROM ".$prefixName."rccm_registre_physique WHERE registreid='".$id."')");						
						$dbAdapter->delete($prefixName."system_users_documents"   , "documentid IN (SELECT documentid   FROM ".$prefixName."rccm_registre_documents WHERE registreid='".$id."')");
						$dbAdapter->delete($prefixName."rccm_registre_documents"  , "registreid=".$id);
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le registre #$id ";
				}
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if(count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("registrephysique/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les registres selectionnés ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les registres selectionnés ont été supprimés avec succès", "success");
			$this->redirect("registrephysique/list");
		}
	}
}