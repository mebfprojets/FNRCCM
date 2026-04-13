<?php

class Admin_IndexController extends Sirah_Controller_Default
{
	
	public function indexAction()
	{
	   
	    $model                   = $this->getModel("registre");
	    $modelTable              = $model->getTable();
		$prefixName              = $modelTable->info("namePrefix");
		$dbAdapter               = $modelTable->getAdapter();
		$this->view->title       = " Bienvenue sur l'interface d'administration de votre systeme ";
		$this->view->subtitle    = " Veuillez vous connecter pour accéder aux fonctionnalités ";
		
		 var_dump(Zend_Date::isDate("15/02/2016 00:00" ,"dd/mm/YYYY H:i"));
		
		$this->view->toolsbar    = "";
		
		$selectModificationTypes = $dbAdapter->select()->from( $prefixName ."rccm_registre_modifications_type");
		$modificationTypes       = $dbAdapter->fetchAll($selectModificationTypes, array() , Zend_Db::FETCH_ASSOC);
		if( count(   $modificationTypes ) ) {
			foreach( $modificationTypes as $modificationType ) {
				     echo $modificationType["type"]." : ".$modificationType["libelle"]." <br/> \n";
			}
		}
	}
	
	public function testAction()
	{
		$this->view->title            = " Bienvenue sur l'interface d'administration de votre systeme ";
		$this->view->subtitle         = " Veuillez vous connecter pour accéder aux fonctionnalités ";
		
		$this->view->toolsbar         = "";
		
		$captchaSession               = new Zend_Session_Namespace("captchas");
		$captchaSession->checksAuth   = 0;
	}
	
	
	public function importfromcsvAction()
	{
		$this->view->title = "IMPORTER LES DONNEES DEPUIS UN FICHIER CSV";
		
		$defaultData       = array("localiteid" => 0, "annee" => "2015");
		$modelLocalite     = $this->getModel("localite");
		$modelDocument     = $this->getModel("document");
		$model             = $this->getModel("registrephysique");
		$modelRegistre     = $this->getModel("registre");
		$modelEntreprise   = $this->getModel("entreprise");
		$modelTable        = $model->getTable();
		$prefixName        = $modelTable->info("namePrefix");
		$dbAdapter         = $modelTable->getAdapter();
		$me                = Sirah_Fabric::getUser();
		$errorMessages     = array();
		
		$annees            = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                   "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
		$localites         = $modelLocalite->getSelectListe("Selectionnez une localité", array("localiteid", "code") , array() , null , null , false );
		$notSavedItems     = array("NUMERO_REGISTRE_TROP_LONG"=> array(), "DOCUMENT_PDF_MANQUANT"=> array(),"NOM_DIRIGEANT_INVALIDE" => array(),
				                   "NOM_COMMERCIAL_INVALIDE"  => array(), "REGISTRE_EXISTANT" => array(), "NUMERO_REGISTRE_TROP_COURT"=> array());
		
		if( $this->_request->isPost( ) ) {
			$postData      = $this->_request->getPost();
			
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
			$stringFilter         = new Zend_Filter();
			//$stringFilter->addFilter(new Zend_Filter_Callback("utf8_encode"));
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$localiteid    = (isset( $postData["localiteid"] )) ? intval( $postData["localiteid"] ) : 0;
			$annee         = (isset( $postData["annee"]      )) ? intval( $postData["annee"] ) : 0;
			$registres     = array();
			
			if( !$annee || !isset( $annees[$annee]) ) {
				$errorMessages[] = "Veuillez sélectionner une année valide";
			}
			if( !$localiteid || !isset( $localites[$localiteid]) ) {
				$errorMessages[] = "Veuillez sélectionner une localité";
			}			
			$registresUpload = new Zend_File_Transfer();
			$registresUpload->addValidator('Count'    , false , 1 );
			$registresUpload->addValidator("Extension", false , array("csv", "xls", "xlxs"));
			$registresUpload->addValidator("FilesSize", false , array("max" => "100MB"));
				
			$basicFilename   = $registresUpload->getFileName('registres' , false );
			$destinationName = APPLICATION_DATA_USER_PATH . $basicFilename ;
				
			$registresUpload->addFilter("Rename", array("target" => $destinationName, "overwrite" => true) , "registres");
			
			if( $registresUpload->isUploaded("registres") && empty( $errorMessages )) {
				$localitecode    = (isset( $postData["localiteid"]) && isset( $localites[$localiteid] )) ? $localites[$localiteid]: "";
				$searchNumero    = sprintf("BF%s%dA", $localitecode, $annee );
				$registresUpload->receive( "registres");
				if( $registresUpload->isReceived("registres")) {
					$csvAdapter  = Sirah_Filesystem_File::fabric("Csv", array("filename" => $destinationName, "has_header" => 0 ) , "rb"  );
					$csvRows     = $csvAdapter->getLines();
					if( count(   $csvRows ) ) {
						foreach( $csvRows as $csvKey => $csvRow ) {
							     $csvDateDemande   = ( isset( $csvRow[0] )) ? $stringFilter->filter( $csvRow[0] ) : "";
							     $csvTypeDemande   = ( isset( $csvRow[1] )) ? $stringFilter->filter( $csvRow[1] ) : "";
							     $csvNomCommercial = ( isset( $csvRow[2] )) ? $stringFilter->filter( $csvRow[2] ) : "";
							     $csvType          = ( isset( $csvRow[3] )) ? $stringFilter->filter( $csvRow[3] ) : "";
							     $csvNumeroRegistre= ( isset( $csvRow[4] )) ? $stringFilter->filter( $csvRow[4] ) : "";
							     $csvDate          = ( isset( $csvRow[5]) ) ? $stringFilter->filter( $csvRow[5] ) : "";
							     $csvAdresse       = ( isset( $csvRow[6]) ) ? $stringFilter->filter( $csvRow[6] ) : "";
							     $csvDirigeant     = ( isset( $csvRow[7]) ) ? $stringFilter->filter( $csvRow[7] ) : "";
							     
							     $insert_data      = array();
							     
							     if( !$strNotEmptyValidator->isValid( $csvNumeroRegistre ) ) {
							     	  continue;
							     } else {
							     	$registres[]  = array("numero" => $csvNumeroRegistre,"date" => $csvDate,"dirigeant" => $csvDirigeant,"nom_commercial" => $csvNomCommercial);
							     }
							     if( strlen( $csvNumeroRegistre ) > 14 ) {
							     	 $notSavedItems["NUMERO_REGISTRE_TROP_LONG"][]   = $csvNumeroRegistre;
							     	 continue;
							     }
							     if( strlen( $csvNumeroRegistre ) < 12 ) {
							     	 $notSavedItems["NUMERO_REGISTRE_TROP_COURT"][] = $csvNumeroRegistre;
							     	 continue;
							     }
							     if( false === stripos( $csvNumeroRegistre, $searchNumero )) {
							     	 continue;
							     }
							     $numIdPrefix       = substr( $csvNumeroRegistre, 0 , 10);
							     $numIdKey          = substr( $csvNumeroRegistre, 10 , strlen( $csvNumeroRegistre ));
							     $csvNumeroRegistre = strtoupper( $numIdPrefix ) . sprintf("%04d", $numIdKey );
							     $foundRegistre     = $modelRegistre->findRow( $csvNumeroRegistre, "numero", null , false );
							     if( $foundRegistre ) {
							     	 $notSavedItems["REGISTRE_EXISTANT"][] = $csvNumeroRegistre;
							     	 continue;
							     }
						         if( !$strNotEmptyValidator->isValid( $csvDirigeant ) ){
									  $notSavedItems["NOM_DIRIGEANT_INVALIDE"][]  = $csvNumeroRegistre;
									  continue;
								 }
								 if( !$strNotEmptyValidator->isValid( $csvNomCommercial ) ){
								 	  $notSavedItems["NOM_COMMERCIAL_INVALIDE"][]  = $csvNumeroRegistre;
								 	  continue;
								 }
								 $dirigeantNameToArray   = ( !empty($csvDirigeant )) ? preg_split("/[\s,]+/", $csvDirigeant ) : array();
								 $dirigeantPrenomToArray = ( count( $dirigeantNameToArray )) ? array_slice( $dirigeantNameToArray, 1 ): array();
								 
								 if( Zend_Date::isDate( $csvDate , "dd/MM/YYYY" ) ) {
								 	$registreZendDate          = new Zend_Date( $csvDate, Zend_Date::DATES , "fr_FR" );
								 } else {
								 	$registreZendDate          = new Zend_Date( sprintf("%s-%s-%d", "01", "01", $annee ), Zend_Date::DATES , "fr_FR" );
								 }
								 if( $registreZendDate ) {
								 	 $insert_data["date"]      = $registreZendDate->get(Zend_Date::TIMESTAMP);
								 } else {
								 	continue;
								 }
								 if($modelRegistre->findRow( $csvNomCommercial, "libelle", null , false )) {
								 	$insert_data["libelle"]    = $csvNomCommercial."('".$csvNumeroRegistre."')";
								 }	
								 $insert_data["numero"]        = $csvNumeroRegistre;
								 $insert_data["libelle"]       = $csvNomCommercial;
								 $insert_data["localiteid"]    = $localiteid;								
								 $insert_data["description"]   = "";
								 $insert_data["creatorid"]     = $me->userid;
								 $insert_data["creationdate"]  = time();
								 $insert_data["updateduserid"] = 0;
								 $insert_data["updatedate"]    = 0;
								 $insert_data["domaineid"]     = 1;
								 if( false !== stripos( $csvType, "P.P") ) {	
								 	     $insert_data["type"]              = 1;
								 	     $insert_data["category"]          = "A";
								 	 if( $dbAdapter->insert( $prefixName . "rccm_registre", $insert_data ) ) {
								 	 	 $savedItems[]                     = sprintf("Le registre numéro %s a été enregistré avec succès sans son document", $insert_data["numero"]);
								 	 	 $registreid                       = $dbAdapter->lastInsertId();
								 	 	 $exploitantData                   = array();
								 	 	 $exploitant_data["datenaissance"] =  0;
								 	 	 $exploitant_data["lieunaissance"] = "";
								 	 	 $exploitant_data["marital_status"]= "";
								 	 	 $exploitant_data["nom"]           = ( isset( $dirigeantNameToArray[0])) ? $dirigeantNameToArray[0] : "";
								 	 	 $exploitant_data["prenom"]        = ( count( $dirigeantPrenomToArray )) ? implode(" ", $dirigeantPrenomToArray) : "";
								 	 	 $exploitant_data["adresse"]       = $csvAdresse;
								 	 	 $exploitant_data["city"]          = $localiteid;
								 	 	 $exploitant_data["country"]       = "BF";
								 	 	 $exploitant_data["email"]         = "";
								 	 	 $exploitant_data["telephone"]     = "";
								 	 	 $exploitant_data["structure"]     = "";
								 	 	 $exploitant_data["creatorid"]     = $me->userid;
								 	 	 $exploitant_data["creationdate"]  = time();
								 	 	 $exploitant_data["updateduserid"] = 0;
								 	 	 $exploitant_data["updatedate"]    = 0;
								 	 	 if($dbAdapter->insert( $prefixName. "rccm_registre_exploitants", $exploitant_data ) ) {
								 	 	 	$exploitantid                  = $dbAdapter->lastInsertId();
								 	 	 	$dbAdapter->insert( $prefixName. "rccm_registre_physique", array("registreid" => $registreid, "exploitantid" => $exploitantid ));								 	 	 	 
								 	 	 }
								 	 }								 	 
								 }	elseif( false !== stripos( $csvType, "SARL") ) {
								 	    $insert_data["type"]                = 2;
								 	    $insert_data["category"]            = "B";
								 	    if( $dbAdapter->insert( $prefixName . "rccm_registre", $insert_data ) ) {
								 	    	$savedItems[]                   = sprintf("Le registre numéro %s a été enregistré avec succès sans son document", $insert_data["numero"]);
								 	    	$registreid                     = $dbAdapter->lastInsertId();
								 	    	
								 	    	if( $entreprise = $modelEntreprise->findRow( $insert_data["libelle"], "libelle", null , false )) {
								 	    		$entrepriseid                  = $entreprise->entrepriseid;
								 	    		$dbAdapter->insert( $prefixName . "rccm_registre_moral",array("registreid" => $registreid,"entrepriseid"=> $entrepriseid,"administrateur" => $entreprise_data["responsable"] ));
								 	    	    continue;        
								 	    	}								 	    									 	    									 	    	
								 	    	$entreprise_data                   = array();
								 	    	
								 	    	$entreprise_data["libelle"]        = $stringFilter->filter($insert_data["libelle"]);
								 	    	$entreprise_data["adresse"]        = $csvAdresse;
								 	    	$entreprise_data["email"]          = "";
								 	    	$entreprise_data["phone1"]         = "";
								 	    	$entreprise_data["phone2"]         = "";
								 	    	$entreprise_data["siteweb"]        = "";
								 	    	$entreprise_data["country"]        = "";
								 	    	$entreprise_data["zip"]            = "";
								 	    	$entreprise_data["city"]           = 0;
								 	    	if( !empty($insert_data["numero"] ) ) {
								 	    		$pageKey                       = preg_replace('/\s+/', '-', strtolower( $insert_data["numero"] ) );
								 	    		$entreprise_data["pagekey"]    = $pageKey;
								 	    	}
								 	    	$entreprise_data["responsable"]    =  $csvDirigeant;
								 	    	$entreprise_data["capital"]        =  0;
								 	    	$entreprise_data["nbemployes_min"] =  0;
								 	    	$entreprise_data["nbemployes_max"] = 0;
								 	    	$entreprise_data["datecreation"]   = $insert_data["date"];
								 	    	$entreprise_data["presentation"]   = "";
								 	    	$entreprise_data["region"]         = 0;
								 	    	$entreprise_data["groupid"]        = 1;
								 	    	$entreprise_data["responsableid"]  = 0;
								 	    	$entreprise_data["reference"]      = $insert_data["numero"];
								 	    	$entreprise_data["creatorid"]      = $me->userid;
								 	    	$entreprise_data["creationdate"]   = time();
								 	    	$entreprise_data["updateduserid"]  = 0;
								 	    	$entreprise_data["updatedate"]     = 0;
								 	    	
								 	    	if( $dbAdapter->insert( $prefixName . "rccm_registre_entreprises", $entreprise_data ) ) {
								 	    		$entrepriseid                  = $dbAdapter->lastInsertId();
								 	    		$dbAdapter->insert( $prefixName . "rccm_registre_moral",array("registreid" => $registreid,"entrepriseid"=> $entrepriseid,"administrateur" => $entreprise_data["responsable"] ));
								 	    	}
								 	    }
								 }						 								 								 															
						}						
					} else {
						$errorMessages[] = "Aucune ligne n'a été trouvée dans le fichier Excel";
					}
				} else {
					    $errorMessages[] = "Le fichier Excel n'a pas pu etre copié sur le serveur avant l'import des données";
				}  
			} else {
				        $errorMessages[] = "Le fichier Excel n'a pas été reçu par le serveur ou des erreurs ont été produites";
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
				echo "----------------TOTAL DES REGISTRES NUMERO REGISTRE TROP COURT:".count($notSavedItems["NUMERO_REGISTRE_TROP_COURT"])." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NOM COMMERCIAL INVALIDE   :".count($notSavedItems["REGISTRE_EXISTANT"])." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NOM DIRIGEANT  INVALIDE   :".count($notSavedItems["NOM_DIRIGEANT_INVALIDE"])." <br/> \n";
				echo "----------------TOTAL DES REGISTRES DOCUMENT PDF MANQUANT     :".count($notSavedItems["DOCUMENT_PDF_MANQUANT"])." <br/><br/><br/> \n";
			
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
		
		$this->view->data       = $defaultData;
		$this->view->localites  = $localites;
		$this->view->annees     = $annees;
		$this->render("importcsv");
	}
	
	public function fromAction( )
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$postData              = $this->_request->getParams();
		$model                 = $this->getModel("registre");
		$modelDirigeant        = $this->getModel("registredirigeant");
		$modelRepresentant     = $this->getModel("representant");
		$modelDomaine          = $this->getModel("domaine");
		$modelLocalite         = $this->getModel("localite");
		$modelDocument         = $this->getModel("document");
		
		$dbsourceParams = array(
				                "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
				                "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
				                "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
				                "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "sigar" ),
				                "isDefaultAdapter" => 0);
		try{
			$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
			$dbSource->getConnection();
		} catch( Zend_Db_Adapter_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		} catch( Zend_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		}
		$localites           = $localitesCodes = $modelLocalite->getSelectListe("Selectionnez une localité"         , array("localiteid", "code"), array() , 0 , null , false);
		$annees              = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                     "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
		$kl                  = (isset( $postData["localite"])) ? strtoupper($postData["localite"]): "OUA";
		$srcPath             = "G:\\DATAS_RCCM\\GED\\SOURCE";
		
		$output          = "<table border='1' width='100%' cellspacing='2' cellpadding='2'> 
		                      <thead> <tr>
							              <th width='10%'> N° d'ordre </th>
							              <th width='20%'> Numéros RCCM </th> 
										  <th width='60%'> Noms & Prénoms (ou nom commercial) </th>
										  <th width='10%'> Fichiers </th>
									   </tr> </thead>
							  <tbody>";
		$ka = 2000;$rccms=array();
		for($ka;$ka <= 2015;$ka++) {			
			$searchRccmKey   = strtoupper( sprintf("BF%s%dA", $kl , $ka ) );
		    $dbSourceSelect  = $dbSource->select()->from(array("A" => "archive"), array("A.analyse","A.date_enregistrement","A.date_deb","A.id_archive"))
					                              ->join(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier","F.id_archive","F.nomged_fichier"))
					                              ->where("F.nom_fichier LIKE ?", "%".$searchRccmKey."%")->where("A.analyse LIKE ?", "%".$searchRccmKey."%")
												  ->where("A.date_deb = ?", $ka)
											      ->order(array("A.analyse ASC", "F.nom_fichier ASC"));
		$registres       = $dbSource->fetchAll( $dbSourceSelect );
		$i                 = 1;
		$rccms[$ka]        = array();
		$existants[$ka]    = array();
		$existantsNom[$ka] = array();
		if( count(   $registres )) {			
		$output         .= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d', $ka)."</strong></td> </tr>";
			foreach( $registres  as $registre ) {
				     $analyse        = $registreStr = $cleanTxt = trim( $registre["analyse"]) ;
					 $numRccm        = trim(str_replace(array(".pdf",".PDF"), "", $registre["nom_fichier"]));
					 $pathOfDocument = $srcPath . DS . $registre["id_archive"] . DS . $registre["nomged_fichier"];
					 if(!file_exists( $pathOfDocument )) {
						 continue;
					 }
					 preg_match("#(?P<numero>BF".strtoupper($kl)."(\d{4})(A|B|M|S)(\d{1,4}))#i", $analyse, $matches );
					 $replaceNumRccm  = (isset( $matches["numero"])) ? $matches["numero"] : $numRccm;
					 if(isset($rccms[$ka][$registre["id_archive"]])) continue;
					 
					 if( $replaceNumRccm ) {
						 while(stripos( $cleanTxt, $replaceNumRccm )!==false) {
							   $cleanTxt = trim(str_replace($replaceNumRccm, "", $cleanTxt));
						 }
					 }
					 if(( $x_pos    = stripos( $cleanTxt, ";"))!==false) {
						  $cleanTxt = trim(substr(  $cleanTxt, 0, $x_pos + 1), " ;");
					 }
					 $cleanTxt= trim(str_replace(array(":",",",": ",":  "), "", $cleanTxt));
					 $bgColor = (in_array($replaceNumRccm,$existants[$ka]) && !in_array($cleanTxt,$existantsNom[$ka])) ? "style=\"background-color:#FF0000;\" bgcolor=\"#FF0000\"": "";
					 $rccms[$ka][$registre["id_archive"]]  = $replaceNumRccm;
					 $existants[$ka][]                     = $replaceNumRccm;
					 $existantsNom[$ka][]                  = $cleanTxt;
					 $cleanTxtToArray = explode(",", $cleanTxt );
					 $name            = (isset($cleanTxtToArray[0])) ? $cleanTxtToArray[0] : "";
					 $output         .=" <tr ".$bgColor."> ";
					 $output         .=" <td> ".$i." </td> 
					                     <td> ".$replaceNumRccm." </td>
					                     <td> ".$analyse ." </td>
										 <td> ".$registre["nomged_fichier"]." </td>
										 ";
					 $output         .=" </tr> ";
				     
				    /**
					 preg_match('#NOM COMMERCIAL\s*:\s*(?P<nomcommercial>[^.,]+)#', $registreStr, $nomCommercialMatches);
				     preg_match('#RCCM\s*:\s*(?P<numero>[^.,]+)#', $registreStr, $numeroMatches);
				     preg_match('#DIRIGEANT\s*:\s*(?P<dirigeant>[^.,]+)#', $registreStr, $dirigeantMatches);
				     preg_match('#TELEPHONE\s*:\s*(?P<telephone>[^.,]+)#', $registreStr, $telephoneMatches);
				     
				     $output = "";
				     $tel    = ";";
				     if( isset( $numeroMatches["numero"])) {
				     	$output.= "NUMERO : ".$numeroMatches["numero"]."(".strlen(trim($numeroMatches["numero"])).")"."; ";
				     	
				     } 
				     if( isset( $dirigeantMatches["dirigeant"])) {
				     	$output.= "DR : ".$dirigeantMatches["dirigeant"]."; ";
				     }
				     if( isset( $telephoneMatches["telephone"])) {
				     	$output.= "TEL : ".$telephoneMatches["telephone"]."; ";
				     	$tel    = $telephoneMatches["telephone"];
				     }
				     if( isset( $nomCommercialMatches["nomcommercial"])) {
				     	 $output.= "NM : ".str_replace(array("TELEPHONE",":", $tel), "",  $nomCommercialMatches["nomcommercial"] )."; ";
				     } */
				     
				     //echo $analyse."<br/> <br/>";	
                    $i++;					 
			}
		} else {
			 $output.= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"><strong>".sprintf('Année %d', $ka)."</strong></td> </tr>";
			 $output.= "<tr><td align=\"center\" colspan=\"4\" style=\"text-align:center;\"> NON COLLECTE </td> </tr>";
		}
		}
        $output      .= "</tbody></table> ";		
		echo $output;	
	}	
		
	public function createdomainesAction( )
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		
		$model          = $this->getModel("registrephysique");
		$modelRegistre  = $this->getModel("registre");
		$modelTable     = $model->getTable();
		$prefixName     = $modelTable->info("namePrefix");
		$dbAdapter      = $modelTable->getAdapter();
		$postData       = $this->_request->getParams();
	
		$dbsourceParams = array(
				                "host"             => (isset($postData["dbsource_host"])     ? $postData["dbsource_host"] : "localhost" ),
				                "username"         => (isset($postData["dbsource_user"])     ? $postData["dbsource_user"] : "root"  ),
				                "password"         => (isset($postData["dbsource_password"]) ? $postData["dbsource_password"] : "" ),
				                "dbname"           => (isset($postData["dbsource_name"])     ? $postData["dbsource_name"] : "sigetbd" ),
				                "isDefaultAdapter" => 0);
		try{
			$dbSource   = Zend_Db::factory("Pdo_Mysql", $dbsourceParams);
			$dbSource->getConnection();
		} catch( Zend_Db_Adapter_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		} catch( Zend_Exception $e ) {
			$errorMessages[] = "Les paramètres de la base de donnée source ne sont pas valides, debogage: ".$e;
		}
		$localites           = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS");
		$annees              = array("2008"=> "2008", "2009"=> "2009", "2010"=> "2010", "2011"=> "2011", "2012"=>"2012",
				                     "2013"=> "2013", "2014"=> "2014", "2015"=> "2015", "2016"=> "2016");
		$kl                  = (isset( $postData["localite"])) ? strtoupper($postData["localite"]): "OUA";
		$ka                  = (isset( $postData["annee"]   )) ? intval( $postData["annee"] )  : "2015";
	
		$searchRccmKey   = strtoupper( sprintf("BF%s%dA", $kl , $ka ) );
		$dbSourceSelect  = $dbSource->select()->from(array("A" => "archive"), array("analyse", "date_enregistrement", "date_deb"))
		->join(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier"))
		->where("F.nom_fichier LIKE ?", "%".strip_tags( $searchRccmKey )."%");
		$registres       = $dbSource->fetchAll( $dbSourceSelect );
	
		if( count(   $registres )) {
			foreach( $registres  as $registre ) {
				$analyse = trim( $registre["analyse"]) ;
				$numero  = strstr($registre["nom_fichier"], ".", true );
				$str     = trim(str_replace( $numero, "", $analyse ));
				$rangees = preg_split("/[\s,]+/", $str );
				$domaineData = $rangees;
				$maxUnsetKey = 4;
				if( count( $rangees ) <= 8 )
					$maxUnsetKey = 3;
				for( $i = 0; $i <= $maxUnsetKey; ++$i  ) {
					unset( $domaineData[$i]);
				}
				$domaineLibelle = implode(" ", $domaineData );
				$searchDomaineSelect = $dbAdapter->select()->from( $prefixName ."rccm_domaines", array("domaineid","libelle"))->where("libelle LIKE ?", "%".strip_tags($domaineLibelle)."%");
				$existantDomaine     = $dbAdapter->fetchRow( $searchDomaineSelect, array(), Zend_Db::FETCH_ASSOC );
				
				if( !$existantDomaine ) {
					 $newDomaineData = array("libelle"   => $domaineLibelle, "description" => $domaineLibelle, "parentid" => 0, "creationdate" => time(), 
					 		                 "creatorid" => 1, "updatedate" => 0, "updateduserid" => 0 );
					 if( $dbAdapter->insert( $prefixName ."rccm_domaines", $newDomaineData )) {
					 	 echo $domaineLibelle."<br/> <br/>";
					 }
				}
				  
	
			}
		}
		
		print_r( count( $registres ));
	
	}
	
	
	
	public function renameAction()
	{
		
		$this->view->title  = "Copier/Renommer des RCCM";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
		
		$defaultData        = array("dbsource_host" => "localhost","dbsource_user" =>"root", "dbsource_password" => "", "annee" => $this->_getParam("annee",date("Y")), 
				                    "dbsource_name" => "sigar","dbsource_tablename"=>"archive","localitekey" => $this->_getParam("localitekey",null), "extract_pages" => null,
				                    "srcpath" => "G:\\DATAS_RCCM\\GED\\SOURCE", "destpath" => "G:\\DATAS_RCCM\\GED\\A_RETRAITER");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
		
		$renamedItems       = array();
		$localites          = $modelLocalite->getSelectListe(null, array("localiteid", "code") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
		
		if( $this->_request->isPost() ) {						
			require_once("tcpdf/tcpdf.php");
			require_once("Fpdi/fpdi.php");
										
			$postData       = $this->_request->getPost();
			$sourcePath     = (isset( $postData["srcpath"]   )) ? $postData["srcpath"]  : "F:\\DATAS_RCCM\\GED\\SOURCE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "F:\\DATAS_RCCM\\GED\\A_RETRAITER";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$extractPageStr = (isset( $postData["extract_pages"] )) ? $postData["extract_pages"] : "";
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
			if( !is_dir( $sourcePath )) {
				$errorMessages[] = "Le chemin du dossier source n'est pas trouvé";
			}
			if( !is_dir( $destPath )) {
				$errorMessages[] = "Le chemin du dossier de destination n'est pas trouvé";
			}			
			if( empty(  $errorMessages )) {
			    $searchRccmKey   = strtoupper( sprintf("BF%s%d", $kl , $ka ) );
			    $dbSourceSelect  = $dbSource->select()->from(array("A" => "archive"), array("analyse", "date_enregistrement", "date_deb"))
			    	     	                          ->joinLeft(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier"))
			    	     	                          ->where("F.nom_fichier LIKE ?", "%".strip_tags( $searchRccmKey )."%");
			    $registres       = $dbSource->fetchAll( $dbSourceSelect );
			    if( count(   $registres )) {
			    	foreach( $registres  as $registre ) { 
			    	     	 $fileSource         = $sourcePath . DS . intval( $registre["dossier"] ) . DS . $registre["filename"];			    	     	 
			    	     	 $numRegistre        = strstr($registre["nom_fichier"], ".", true );
                             $isMorale		     = false;
                             $basePath           = $destPath;							 
                             if( FALSE!=  stripos(strtoupper($registre["nom_fichier"]),sprintf("BF%s%dB", strtoupper($kl), intval($ka))) ) {
								 $isMorale	     = true;	
								 $basePath       = $destPath. DS . "MORALES";
							 } else {
								 $basePath       = $destPath. DS . "PHYSIQUES";
							 }
                             if(FALSE!=  stripos(strtoupper($registre["nom_fichier"]),sprintf("BF%s%dM", strtoupper($kl), intval($ka)))) {
								 $isMorale	     = true;	
								 $basePath       = $destPath. DS . "MODIFICATIONS";
							 } else {
								 $basePath       = $destPath. DS . "PHYSIQUES";
							 }	                             							 
			    	     	 $numIdPrefix        = substr( $numRegistre, 0 , 10);
			    	     	 $numIdKey           = substr( $numRegistre, 10 , strlen( $numRegistre ));
			    	     	 $numRegistre        = strtoupper( $numIdPrefix ) . sprintf("%04d", $numIdKey );
							 $filesDir           = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre;
			    	     	 $fileDest2          = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . strtoupper( $numIdPrefix ) . sprintf("%03d", $numIdKey ).".pdf";			    	     	 
			    	     	 $fileDest           = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre.".pdf";
							 $formulaireFileDest = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre. DS  . $numRegistre."-FR.pdf";
							 $psFileDest         = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre. DS  . $numRegistre."-PS.pdf";
							 $statutFileDest     = $basePath. DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre. DS  . $numRegistre."-ST.pdf";
							 if(is_dir( $filesDir )) {
								continue; 
							 }							 
			    	     	 if( !empty( $extractPageStr ) && file_exists( $fileSource )) {
			    	     	 	 $formFilepath = $destPath   . DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre."-FR.pdf";
			    	     	 	 $pdfDest      = new FPDI();
			    	     	 	 $pdfDest->AddPage();
			    	     	 	 $pdfDest->setSourceFile( $fileSource );
			    	     	 	 $extractPages = preg_split("/[\s,;]/", $extractPageStr );
			    	     	 	 $canSave      = false;
			    	     	 	 if( count(   $extractPages )) {
			    	     	 	 	 foreach( $extractPages as $pageKey ) {
			    	     	 	 	 	      if( $i = intval( $pageKey )) {
			    	     	 	 	 	          $pdfDest->useTemplate($pdfDest->importPage($i));
			    	     	 	 	 	          $canSave  = true;
			    	     	 	 	 	      }
			    	     	 	 	 }
			    	     	 	 	 if( $canSave ) 
			    	     	 	 	 	 $pdfDest->Output(  $formFilepath, "F");
			    	     	 	 }
			    	     	 }
							 @chmod($destPath, 0777 );
							 if(false == @mkdir($filesDir)) {								 
								 die( $filesDir);
							 }
			    	         if( !file_exists( $formulaireFileDest ) && file_exists( $fileSource )) {
							     if( (true == @copy( $fileSource , $formulaireFileDest )) && (true == @copy( $fileSource , $psFileDest ))) {
									 @copy( $fileSource , $statutFileDest );
								     $renamedItems[]  = $registre["nom_fichier"];
							     } else {
								     $notSavedItems[] = $registre["nom_fichier"];
							     }
						    }
			    	     	 /*			    	     	 			    	     	 			    	     	 				    	     	 			    	     	 			    	     	 			    	     	 
			    	     	 if( !file_exists( $fileDest ) && file_exists( $fileSource )) {
			    	     	     if( true == @copy( $fileSource , $fileDest )) {
			    	     	      	 $renamedItems[]         = $registre["nom_fichier"];			    	    	      	 			    	     	      	 			    	     	      	 			  	     	      	 
			    	     	     } else {
			    	     	      	 $notSavedItems[] = $registre["nom_fichier"];
			    	     	     }
			    	     	  }*/
			        }
			    }
			} 
			if( !count( $errorMessages )) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo "-------------------------------------------------- RAPPORT DE COPIER/RENOMMER DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count($renamedItems)." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
				
				if( count( $renamedItems )) {
					echo "  ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						     echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				$nextYear    = $ka+1;
				$linkOfNext  = $this->view->url(array("controller" => "index", "action" => "rename", "module" => "admin","localitekey" =>$postData["localitekey"], "annee" => $nextYear));
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
				echo "<a title=\"Effectuer le suivant\" href=\"".$linkOfNext."\"> Suivant </a>";
				exit;
			} else {
				$defaultData       = $postData;
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" " , $messages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					     $this->_helper->Message->addMessage( $message , "error" ) ;
				}
			}			
		}		
		$this->view->data      = $defaultData;
		$this->view->localites = $localites;
		$this->view->annees    = $annees;
		$this->render("renamephysiques");
		
	}
	
	
	public function renamemoralesAction()
	{
		$this->view->title  = "Copier/Renommer des RCCM Morales";
		$modelLocalite      = $this->getModel("localite");
		$model              = $this->getModel("registrephysique");
		$modelTable         = $model->getTable();
		$prefixName         = $modelTable->info("namePrefix");
		$dbDestination      = $modelTable->getAdapter();
		$me                 = Sirah_Fabric::getUser();
	
		$defaultData        = array("dbsource_host" => "localhost","dbsource_user" =>"root", "dbsource_password" => "", "annee" => $this->_getParam("annee",date("Y")), 
				                    "dbsource_name" => "sigar","dbsource_tablename"=>"archive","localitekey" => $this->_getParam("localitekey",null), "extract_pages" => null,
				                    "srcpath" => "G:\\DATAS_RCCM\\GED\\SOURCE", "destpath" => "G:\\DATAS_RCCM\\GED\\DESTINATION\\MORALES");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array();
	
		$renamedItems       = array();
		$localites          = $modelLocalite->getSelectListe(null, array("localiteid", "code") , array() , null , null , false );
		$annees             = array("2000"=>"2000","2001"=>"2001","2002"=>"2002","2003"=>"2003","2004"=>"2004","2005"=>"2005","2006"=>"2006","2007"=>"2007",
		                            "2008"=>"2008","2009"=>"2009","2010"=>"2010","2011"=>"2011","2012"=>"2012","2013"=>"2013","2014"=>"2014","2015"=>"2015","2016"=> "2016");
	
		if( $this->_request->isPost() ) {
			$this->_helper->viewRenderer->setNoRender(true);
			$this->_helper->layout->disableLayout(true);
			
			require_once("tcpdf/tcpdf.php");
			require_once("Fpdi/fpdi.php");
	
			$postData       = $this->_request->getPost();
			$sourcePath     = (isset( $postData["srcpath"]   )) ? $postData["srcpath"]  : "F:\\DATAS_RCCM\\GED\\SOURCE";
			$destPath       = (isset( $postData["destpath"]  )) ? $postData["destpath"] : "F:\\DATAS_RCCM\\GED\\DESTINATION\\MORALES";
			$kl             = (isset( $postData["localitekey"]) && isset( $localites[$postData["localitekey"]] )) ? $localites[$postData["localitekey"]]: "";
			$ka             = (isset( $postData["annee"]     )) ? intval( $postData["annee"] )  : date("Y");
			$extractPageStr = (isset( $postData["extract_pages"] )) ? $postData["extract_pages"] : "";
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
			if( !is_dir( $sourcePath )) {
				$errorMessages[] = "Le chemin du dossier source n'est pas trouvé";
			}
			if( !is_dir( $destPath )) {
				$errorMessages[] = "Le chemin du dossier de destination n'est pas trouvé";
			}
			if( empty(  $errorMessages )) {
				$searchRccmKey   = strtoupper( sprintf("BF%s%dB", $kl , $ka ) );
				$dbSourceSelect  = $dbSource->select()->from(array("A" => "archive"), array("analyse", "date_enregistrement", "date_deb"))
				                                      ->join(array("F" => "fichier"), "F.id_archive = A.id_archive", array("F.nom_fichier","dossier" =>"F.id_archive","filename" => "F.nomged_fichier"))
				                                      ->where("F.nom_fichier LIKE ?", "%".strip_tags( $searchRccmKey )."%");
				$registres       = $dbSource->fetchAll( $dbSourceSelect );
				if( count(   $registres )) {
					foreach( $registres  as $registre ) {
						     $numRegistre   = strstr($registre["nom_fichier"], ".", true );	
			    	     	 
			    	     	 $numIdPrefix = substr( $numRegistre, 0 , 10);
			    	     	 $numIdKey    = substr( $numRegistre, 10 , strlen( $numRegistre ));
			    	     	 $numRegistre = strtoupper( $numIdPrefix ) . sprintf("%04d", $numIdKey );
						     $fileSource  = $sourcePath . DS . intval( $registre["dossier"] )      . DS . $registre["filename"];
						     $fileDest    = $destPath   . DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre.".pdf";
						
						if( !empty( $extractPageStr ) && file_exists( $fileSource )) {
							$formFilepath = $destPath   . DS . strtoupper( $kl) . DS . intval($ka) . DS . $numRegistre."-FR.pdf";
							$pdfDest      = new FPDI();
							$pdfDest->AddPage();
							$pdfDest->setSourceFile( $fileSource );
							$extractPages = preg_split("/[\s,;]/", $extractPageStr );
							$canSave      = false;
							if( count(   $extractPages )) {
								foreach( $extractPages as $pageKey ) {
									if( $i = intval( $pageKey )) {
										$pdfDest->useTemplate($pdfDest->importPage($i));
										$canSave  = true;
									}
								}
								if( $canSave )
									$pdfDest->Output(  $formFilepath, "F");
							}
						}						
						if( !file_exists( $fileDest ) && file_exists( $fileSource )) {
							if( true == @copy( $fileSource , $fileDest )) {
								$renamedItems[]  = $registre["nom_fichier"];
							} else {
								$notSavedItems[] = $registre["nom_fichier"];
							}
						}
					}
				}
			}
			if( !count( $errorMessages )) {
				echo "-------------------------------------------------- RAPPORT DE COPIER/RENOMMER DES DONNEES-----------------------------------------<br/>\n";
				echo "----------------DATE IMPORT :".date("d/m/Y H:i:s")." <br/> \n";
				echo "----------------TOTAL DES REGISTRES SOURCE         :".count($registres )." <br/> \n";
				echo "----------------TOTAL DES REGISTRES COPIES ET RENOMMES      :".count($renamedItems)." <br/> \n";
				echo "----------------LOCALITE                           :".$kl." <br/> \n";
				echo "----------------ANNEE                              :".$ka." <br/> \n";
				echo "----------------TOTAL DES REGISTRES NON IMPORTES   :".count($notSavedItems)." <br/> \n";
	
				if( count( $renamedItems )) {
					echo " ------------------------------------------------- LISTE DES REGISTRES COPIES-----------------------------------------<br/>\n
					<ul>";
					foreach( $renamedItems as $numRegistreItem ) {
						echo " <li> ".$numRegistreItem ." </li>";
					}
					echo "    </ul> <br/> <br/>";
				}
				 
				$nextYear    = $ka+1;
				$linkOfNext  =  $this->view->url(array("controller" => "index", "action" => "renamemorales", "module" => "admin","localitekey" =>$postData["localitekey"], "annee" => $nextYear));
				echo "-------------------------------------------------- FIN DU RAPPORT D'IMPORT-----------------------------------------<br/>\n";
				echo "<a title=\"Effectuer le suivant\" href=\"".$linkOfNext."\"> Suivant </a>";
				exit;
			} else {
				$defaultData       = $postData;
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => implode(" " , $messages )));
					exit;
				}
				foreach( $errorMessages as $message) {
					$this->_helper->Message->addMessage( $message , "error" ) ;
				}
			}
		}
		$this->view->data      = $defaultData;
		$this->view->localites = $localites;
		$this->view->annees    = $annees;
		$this->render("renamemorales");
	
	}
	
	
	public function importphysiqueAction()
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
				                    "dbsource_name" => "siget","dbsource_tablename"=>"archive", "srcpath" => "F:\webserver\\www\\erccm/data/physique");
		$errorMessages      = array();
		$successMessages    = array();
		$notSavedItems      = array("NUMERO_REGISTRE_TROP_LONG"=> array(), "DOCUMENT_PDF_MANQUANT"=> array(),"NOM_DIRIGEANT_INVALIDE" => array(),
				                    "NOM_COMMERCIAL_INVALIDE"  => array(), "REGISTRE_EXISTANT" => array());
		
		$savedItems         = array();
		$localites = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS");
		$annees    = array("2010","2011","2012", "2013", "2014", "2015");
		
		if( $this->_request->isPost() ) {
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
				$dbSourceSelect   = $dbSource->select()->from(array("A" => "archive"), array("analyse", "date_enregistrement", "date_deb"))
				->join(array("F" => "fichier"), "F.id_archive = A.id")
				->where("analyse LIKE ?", "%".strip_tags( $numeroInitial )."%")->where("date_deb LIKE ?", "%".intval( $annee )."%");
				$registres        = $dbSource->fetchAll( $dbSourceSelect );
			}
			
		}
				
		$this->render("importphysique");
	}
	
	public function importmoralesAction()
	{
		$localites = array("MNG","GAO","BFR","OUA","OHG","YKO","ZNR","ORD","DBG","KDG","KYA","KGS");
		$annees    = array("2010","2011","2012", "2013", "2014", "2015");
	}
	
	
	
	public function errorAction()
	{
		echo "error";
		echo "Nous sommes sur la page d'accueil";
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	public function denyAction()
	{
		$loger     = $this->getHelper("Log")->getLoger();
		$resource  = $this->_request->getParam("_precController");
		if($this->_request->isXmlHttpRequest()){
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRender->setNoRender(true);
			$error=array("error"=>" Désolé ! Vous n'etes pas autorisé à effectuer cette action sur la ressource  {$resource}... ");
			echo ZendX_JQuery::encodeJson($error);
		}
		else{
			$this->view->message=" Désolé ! Vous n'etes pas autorisé à effectuer cette action sur la ressource {$resource}...";
			$this->render();
		}
		$author=null;
		$writer=& $loger->getWriter("fichier");
		$formater=new Zend_Log_Formatter_Simple(" %user% %message% à la date du %timestamp% \n" );
		$writer->setFormatter( $formater);
		$loger->autorisation(" il a tente d'acceder à la ressource {$resource} à laquelle il n'etait pas autorisé ");
		
	}



}
