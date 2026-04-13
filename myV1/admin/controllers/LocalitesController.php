<?php

class Admin_LocalitesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "LISTE DES LOCALITES"  ;
		
		$model              = $this->getModel("localite");
		
		$localites          = array();
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
		
		$filters            = array("libelle" => null, "parentid" => null );		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$localites             = $model->getList( $filters , $pageNum , $pageSize);
		$paginator             = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->localites = $localites;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;			
	}
	
	
	public function importcsvAction()
	{
		$this->view->title         = "Importer les communes";
		$model                     = $modelCommune = $this->getModel("localitecommune");
		$modelTable                = $model->getTable();
		$prefixName                = $modelTable->info("namePrefix");
		$dbDestination             = $dbAdapter       = $modelTable->getAdapter();
		$me                        = Sirah_Fabric::getUser();
		$errorMessages             = $successMessages = array();
		$jsonCsvRows               = array();
		$emptyFormeData            = $model->getEmptyData();
		if( $this->_request->isPost() ) {
			$me                    = Sirah_Fabric::getUser();
			$postData              = $this->_request->getPost();
			$stringFilter          = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$documentsUploadAdapter= new Zend_File_Transfer();
		    $documentsUploadAdapter->addValidator('Count'    , false , 1);
		    $documentsUploadAdapter->addValidator("Extension", false , array("csv", "xls", "xlxs"));
	        $documentsUploadAdapter->addValidator("FilesSize", false , array("max" => "100MB"));
		
		    $destinationName       = $me->getDatapath(). "csvFile.csv";						
		    $documentsUploadAdapter->addFilter("Rename", array("target" => $destinationName, "overwrite"=> true) , "communes");
			if( $documentsUploadAdapter->isUploaded("communes") ) {
				$documentsUploadAdapter->receive(   "communes");
				if( $documentsUploadAdapter->isReceived("communes") ) {
					$csvFile        = file($destinationName ,FILE_SKIP_EMPTY_LINES);
					$csvRows        = array_map("str_getcsv",$csvFile,array_fill(0, count($csvFile), ';'));
					$csvKeys        = array_shift($csvRows);
					$csvData        = array();
					//print_r(  $csvRows); die();	
					if( count(   $csvKeys)) {
						foreach( $csvKeys as $u => $csvKey) {
							     $csvKeys[$u]        = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '',trim($csvKey));
						}
						foreach ( $csvRows as $k=>$csvFileRow) {
							      $csvData[$k]       = array_combine($csvKeys,$csvFileRow);
						}
					}
					if( count(   $csvData )) {
						$jsonCsvRows["rows"]         = array();	
						$ligneid                     = 1;
						$i                           = 0;
						foreach( $csvData as $csvRow ) {
							     $CodeFNRCCM         = (isset($csvRow["code"])       && !empty($csvRow["code"]      ))? trim($csvRow["code"]) : (isset($csvRow["code_fnrccm"])?$csvRow["code_fnrccm"] : "");
						         $CodeFNERE          = (isset($csvRow["code_fnere"]) && !empty($csvRow["code_fnere"]))? trim($csvRow["code_fnere"]) : (isset($csvRow["code2"])?$csvRow["code2"] : "");
						         $Libelle            = (isset($csvRow["libelle"])    && !empty($csvRow["libelle"]   ))? trim($csvRow["libelle"]) : (isset($csvRow["nom"])?$csvRow["nom"] : "");
						         
								 if( empty($CodeFNRCCM) ) {
									 $errorMessages[]= sprintf("Le code FNRCCM est invalide à la ligne %d", $ligneid);
									 $ligneid++;
								     $i++;
									 continue;
								 }
								 if( empty($CodeFNERE) ) {
									 $errorMessages[]= sprintf("Le code FICHIER NERE est invalide à la ligne %d", $ligneid);
									 $ligneid++;
								     $i++;
									 continue;
								 }
								 if( empty($Libelle) ) {
									 $errorMessages[]= sprintf("La désignation de la commune est invalide à la ligne %d", $ligneid);
									 $ligneid++;
								     $i++;
									 continue;
								 }
								 $postData                        = array("code"=>$CodeFNRCCM,"nereCode"=>$CodeFNERE,"libelle"=>$Libelle,"description"=>$Libelle,"parentid"=>0,"profondeur"=>0,"special"=>0,"creationdate"=>time(),"creatorid"=>$me->userid,"updatedate"=>0,"updateduserid"=>0);
								 $insertData                      = array_merge($emptyFormeData, array_intersect_key($postData,$emptyFormeData));
								 $updateData                      = array();
								 $communeid                          = 0;
								 if( $foundByFNRCCM       = $model->findRow($CodeFNRCCM,"code",null,false)) {
								     $updateData                  = array_merge($foundByFNRCCM->toArray(), $insertData);
									 $communeid                      = $updateData["communeid"] = $foundByFNRCCM->communeid;
								 } elseif($foundByFNERE   = $model->findRow($CodeFNERE,"nereCode",null,false)) {
									 $updateData                  = array_merge($foundByFNERE->toArray(), $insertData);
									 $communeid                      = $updateData["communeid"] = $foundByFNERE->communeid;
								 } elseif($foundByLibelle = $model->findRow($Libelle,"libelle",null,false)) {
									 $updateData                  = array_merge($foundByLibelle->toArray(), $insertData);
									 $communeid                      = $updateData["communeid"] = $foundByLibelle->communeid;
								 }
								 if( count($updateData) && isset($updateData["communeid"])) {
									 $communeid                      = $updateData["communeid"];
									 unset($updateData["communeid"]);
									 $updateData["updatedate"]    = time();
									 $updateData["updateduserid"] = $me->userid;
									 try {
										 if( $dbAdapter->update( $prefixName."rccm_localites_provinces", $updateData , array("communeid=?"=>$communeid))) {
										     $successMessages[]   = sprintf("La commune %s a été mis à jour avec succès", $Libelle);
										 } else {
											 $errorMessages[]     = sprintf("La commune %s n'a pas été mise à jour pour des raisons inconnues", $Libelle);
										 }
									 } catch(Exception $e) {
										 $errorMessages[]         = sprintf("La commune %s n'a pas été mise à jour pour la raison : %s", $Libelle, $e->getMessage());
									 }
								 } /*else {
									 try {
										 if( $dbAdapter->insert( $prefixName."rccm_localites_provinces", $insertData)) {
										     $successMessages[]   = sprintf("La commune %s a été créée avec succès", $Libelle);
										 } else {
											 $errorMessages[]     = sprintf("La commune %s n'a pas été créée pour des raisons inconnues", $Libelle);
										 }
									 } catch(Exception $e) {
										 $errorMessages[]         = sprintf("La commune %s n'a pas été créée pour la raison : %s", $Libelle, $e->getMessage());
									 }
								 }*/
								 $ligneid++;
								 $i++;
						}
					}
				}
			}
			if( count($errorMessages ) ) {
			 	if( $this->_request->isXmlHttpRequest() ) {
			 		$this->_helper->viewRenderer->setNoRender(true);
			 		$this->_helper->layout->disableLayout(true);
			 		echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs se sont produites ".implode(" , " , $errorMessages )));
			 		exit;
			 	}
			 	foreach( $errorMessages as $message ) {
			 		     $this->_helper->Message->addMessage($message) ;
			 	}
				$this->redirect("admin/registres/list");
			}
			if( count($successMessages)) {							
				if( $this->_request->isXmlHttpRequest() ) {
			 		$this->_helper->viewRenderer->setNoRender(true);
			 		$this->_helper->layout->disableLayout(true);
			 		echo ZendX_JQuery::encodeJson(array("success"=> implode(", " , $successMessages )));
			 		exit;
			 	}
			 	foreach( $successMessages as $message ) {
			 		     $this->_helper->Message->addMessage( $message , "success" ) ;
			 	}
				$this->redirect("admin/registres/list");
			}
		}
		$this->view->data      = array();
		$this->render("importforms");	
	}
	
	public function createAction()
	{
		$this->view->title     = "ENREGISTRER UNE NOUVELLE LOCALITE";
		
		$model                 = $this->getModel("localite");
		
		$defaultData           = $model->getEmptyData();
		$errorMessages         = array();
		
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$formData          = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data       = array_merge( $model->getEmptyData() , $formData);
			$me                = Sirah_Fabric::getUser();
			$userTable         = $me->getTable();
			$dbAdapter         = $userTable->getAdapter();
			$prefixName        = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$floatValidator       = new Zend_Validate_Float();	
			$floatFilter	      = new Zend_Filter_Digits();
			
			$libelle              = $stringFilter->filter($insert_data["libelle"]);
			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]  = " Veuillez entrer une désignation valide pour cette localité";
			} elseif( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]                = sprintf(" Une localité existante porte la désignation %s , veuillez entrer une désignation différente " , $libelle );
		    } else {
				$insert_data["libelle"]         = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $insert_data["code"] ) && ( strlen( $insert_data["libelle"]) >= 2 ) ) {
				$libelleStrToArray       = str_split($insert_data["libelle"]);
				shuffle( $libelleStrToArray );
				$localiteCode            = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[1]);
				$i                       = 1;
				while( $model->findRow( $localiteCode, "code", null , false )) {
					$i++;
					if( isset( $libelleStrToArray[$i] ) ) {
						$localiteCode = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[$i]);
					} else {
						$localiteCode = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[1]).sprintf("%02d",$i);
					}
				}
				$insert_data["code"]   = $localiteCode;
			}
			if( $model->findRow( $insert_data["code"], "code", null , false ) )	 {
				$errorMessages[]                = "Le code de la localité que vous avez saisi, existe déjà";
			}						 
			$insert_data["description"]         = $stringFilter->filter( $insert_data["description"] );
			$insert_data["parentid"]            = intval( $insert_data["parentid"] );
			$insert_data["creatorid"]           = $me->userid;
			$insert_data["creationdate"]        = time();											
			if(empty($errorMessages)) {
				if( $dbAdapter->insert( $prefixName . "rccm_localites", $insert_data) ) {
					$localiteid       = $dbAdapter->lastInsertId();				
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "La localité a été enregistrée avec succès"));
						exit;
					}
					$this->setRedirect("La localité a été enregistrée avec succès", "success" );
					$this->redirect("admin/localites/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement de la localité a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la localité a echoué" , "error");
					$this->redirect("admin/localites/list")	;
				}
			} else {
				$defaultData  = array_merge( $defaultData , $postData );
				if( $this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$this->view->data      = $defaultData;	
	}
	
	
	public function editAction()
	{
		$this->view->title      = " Mettre à jour les informations d'une localité  ";
		
		$localiteid              = intval($this->_getParam("localiteid", $this->_getParam("id" , 0)));
		
		if(!$localiteid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/localites/list");
		}		
		$model                = $this->getModel("localite");
 	
		$localite              = $model->findRow( $localiteid , "localiteid" , null , false);		
		if(!$localite) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/localites/list");
		}		
		$defaultData         = $localite->toArray();
		$errorMessages       = array();  
		
		if( $this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$userTable       = $me->getTable();
			$dbAdapter       = $userTable->getAdapter();
			$prefixName      = $userTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$libelle              = $stringFilter->filter( $update_data["libelle"] );
			
		   if(!$strNotEmptyValidator->isValid($update_data["libelle"])) {
				$errorMessages[]           = " Veuillez entrer une désignation valide de la localité";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ( $localite->libelle != $libelle ) ) {
				$errorMessages[]           = sprintf(" Une localité existante porte la désignation %s , veuillez entrer une désignation différente ", $libelle );
		    } else {
				$update_data["libelle"]    = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $update_data["code"] ) && ( strlen( $update_data["libelle"]) >= 2 ) ) {
				$libelleStrToArray         = str_split($update_data["libelle"]);
				shuffle( $libelleStrToArray );
				$localiteCode              = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[1]);
				$i                         = 1;
				while( $model->findRow( $localiteCode, "code", null , false )) {
					$i++;
					if( isset( $libelleStrToArray[$i] ) ) {
						$localiteCode      = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[$i]);
					} else {
						$localiteCode      = strtoupper( $libelleStrToArray[0]."".$libelleStrToArray[1]).sprintf("%02d",$i);
					}
				}
				$update_data["code"]       = $localiteCode;
			}
			$update_data["parentid"]       = intval( $update_data["parentid"] );
			$update_data["description"]    = $stringFilter->filter( $update_data["description"] );
			$update_data["updateduserid"]  = $me->userid;
			$update_data["updatedate"]     = time();	
			$localite->setFromArray( $update_data);				
			if(empty($errorMessages)) {
				if( $localite->save()) {					 					
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations de la localité ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations de la localité ont été mises à jour avec succès", "success" );
					$this->redirect("admin/localites/infos/id/".$localiteid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("admin/localites/infos/id/".$localiteid);
				}
			} else {
				$defaultData   = $update_data;
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
		}	
		$this->view->data        = $defaultData;
		$this->view->localiteid  = $localiteid;
	}	
 		
		
	public function infosAction()
	{
		$id              = intval($this->_getParam("id", $this->_getParam("localiteid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/localites/list");
		}		
		$model          = $this->getModel("localite");
		$localite       = $model->findRow( $id , "localiteid" , null , false);		
		if( !$localite ){
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"  => " Aucune entrée de localité n'a été retrouvée avec cet identifiant "));
				exit;
			}
			$this->setRedirect("Aucune entrée de localité n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/localites/list");
		}
		$this->view->localite   = $localite;
		$this->view->title      = "LES INFORMATIONS D'UNE LOCALITE";
		$this->view->columns    = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$model         = $this->getModel("localite");
		$ids           = $this->_getParam("localiteids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$localite  = $model->findRow( $id , "localiteid" , null , false );
				if($localite) {
					if(!$localite->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée : La localité id#$id n'a pas été supprimée ";
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour la localité id #$id ";
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
			$this->redirect("admin/localites/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les localités indiquées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les localités indiquées ont été supprimées avec succès", "success");
			$this->redirect("admin/localites/list");
		}
	}
}