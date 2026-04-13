<?php

class Admin_DomainesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = "Gestion des secteurs d'activités"  ;
		
		$model              = $this->getModel("domaine");		
		$domaines           = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator= new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params             = $this->_request->getParams();
		$pageNum            = (isset($params["page"]))     ? intval($params["page"])     : 1;
		$pageSize           = (isset($params["maxitems"])) ? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;		
		
		$filters            = array("libelle" => null, "code"=>null,"parentid"=>0);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$domaines        = $model->getList( $filters , $pageNum , $pageSize);
		$paginator          = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns       = array("left");
		$this->view->domaines      = $domaines;
		$this->view->filters       = $filters;
		$this->view->paginator     = $paginator;
		$this->view->pageNum       = $pageNum;
		$this->view->pageSize      = $pageSize;			
	}	
	
	public function importcsvAction()
	{
		$this->view->title         = "Importer des domaines";
		$model                     = $modelDomaine = $this->getModel("domaine");
		$modelTable                = $model->getTable();
		$prefixName                = $modelTable->info("namePrefix");
		$dbDestination             = $dbAdapter       = $modelTable->getAdapter();
		$me                        = Sirah_Fabric::getUser();
		$errorMessages             = $successMessages = array();
		$jsonCsvRows               = array();
		$emptyDomaineData          = $model->getEmptyData();
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
		    $documentsUploadAdapter->addFilter("Rename", array("target" => $destinationName, "overwrite"=> true) , "domaines");
			if( $documentsUploadAdapter->isUploaded("domaines") ) {
				$documentsUploadAdapter->receive(   "domaines");
				if( $documentsUploadAdapter->isReceived("domaines") ) {
					$csvFile        = file($destinationName ,FILE_SKIP_EMPTY_LINES);
					$csvRows        = array_map("str_getcsv",$csvFile,array_fill(0, count($csvFile), ';'));
					$csvKeys        = array_shift($csvRows);
					$csvData        = array();
					//print_r(  $csvRows); die();	
					if( count(   $csvKeys)) {
						foreach( $csvKeys as $u => $csvKey) {
							     $csvKeys[$u]   = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '',trim($csvKey));
						}
						foreach ( $csvRows as $k=>$csvFileRow) {
							      $csvData[$k]  = array_combine($csvKeys,$csvFileRow);
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
									 $errorMessages[]= sprintf("La désignation du domaine est invalide à la ligne %d", $ligneid);
									 $ligneid++;
								     $i++;
									 continue;
								 }
								 $postData                        = array("code"=>$CodeFNRCCM,"code_fnere"=>$CodeFNERE,"libelle"=>$Libelle,"description"=>$Libelle,"parentid"=>0,"profondeur"=>0,"special"=>0,"creationdate"=>time(),"creatorid"=>$me->userid,"updatedate"=>0,"updateduserid"=>0);
								 $insertData                      = array_merge($emptyDomaineData, array_intersect_key($postData,$emptyDomaineData));
								 $updateData                      = array();
								 $domaineid                       = 0;
								 if( $foundByFNRCCM       = $model->findRow($CodeFNRCCM,"code",null,false)) {
								     $updateData                  = array_merge($foundByFNRCCM->toArray(), $insertData);
									 $domaineid                   = $updateData["domaineid"] = $foundByFNRCCM->domaineid;
								 } elseif($foundByFNERE   = $model->findRow($CodeFNERE,"code_fnere",null,false)) {
									 $updateData                  = array_merge($foundByFNERE->toArray(), $insertData);
									 $domaineid                   = $updateData["domaineid"] = $foundByFNERE->domaineid;
								 } elseif($foundByLibelle = $model->findRow($Libelle,"libelle",null,false)) {
									 $updateData                  = array_merge($foundByLibelle->toArray(), $insertData);
									 $domaineid                   = $updateData["domaineid"] = $foundByLibelle->domaineid;
								 }
								 
								 if( count($updateData) && isset($updateData["domaineid"])) {
									 $domaineid                   = $updateData["domaineid"];
									 unset($updateData["domaineid"]);
									 $updateData["updatedate"]    = time();
									 $updateData["updateduserid"] = $me->userid;
									 try {
										 if( $dbAdapter->update( $prefixName."rccm_domaines", $updateData , array("domaineid=?"=>$domaineid))) {
										     $successMessages[]   = sprintf("Le domaine %s a été mis à jour avec succès", $Libelle);
										 } else {
											 $errorMessages[]     = sprintf("Le domaine %s n'a pas été mis à jour pour des raisons inconnues", $Libelle);
										 }
									 } catch(Exception $e) {
										 $errorMessages[]         = sprintf("Le domaine %s n'a pas été mis à jour pour la raison : %s", $Libelle, $e->getMessage());
									 }
								 } else {
									 try {
										 if( $dbAdapter->insert( $prefixName."rccm_domaines", $insertData)) {
										     $successMessages[]   = sprintf("Le domaine %s a été créé avec succès", $Libelle);
										 } else {
											 $errorMessages[]     = sprintf("Le domaine %s n'a pas été créé pour des raisons inconnues", $Libelle);
										 }
									 } catch(Exception $e) {
										 $errorMessages[]         = sprintf("Le domaine %s n'a pas été créé pour la raison : %s", $Libelle, $e->getMessage());
									 }
								 }
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
				$this->redirect("admin/domaines/list");
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
				$this->redirect("admin/domaines/list");
			}
		}
		$this->view->data      = array();
		$this->render("importcsv");	
	}
	
		
	public function createAction()
	{
		$this->view->title     = "Créer un secteur ou un sous-secteur";
		
		$me                    = Sirah_Fabric::getUser();                 
		
		$model                 = $this->getModel("domaine");				
		$defaultData           = $model->getEmptyData();
		$errorMessages         = array();
		$parents               = $model->getSelectListe("Sélectionnez un secteur parente",array("domaineid","libelle"), array(),null,null,false );
		if( $this->_request->isPost() ) {
			$postData          = $this->_request->getPost();
			$formData          = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data       = array_merge( $model->getEmptyData(), $formData);
			
			$modelTable         = $model->getTable();
			$dbAdapter         = $me->getTable()->getAdapter();
			$prefixName        = $me->getTable()->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator              = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$insert_data["libelle"]            = $libelle = $stringFilter->filter($insert_data["libelle"]);
			$insert_data["code"]               = $stringFilter->filter($insert_data["code"]);
			$insert_data["parentid"]           = intval($insert_data["parentid"]);
			$insert_data["description"]        = $stringFilter->filter($insert_data["description"]);
			$profondeur                        = 0;
			
			if(intval( $insert_data["parentid"]) ) {
				$parent                        = $model->findRow($insert_data["parentid"], "domaineid", null, false);
				if( $parent ) {
					$profondeur                = $parent->profondeur +1;
				} else {
					$errorMessages[]           = "Le secteur parent que vous avez selectionné est invalide ";
				}
			}			 
			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]              = " Veuillez entrer une désignation valide pour le secteur";
			} elseif( $model->findRow($insert_data["libelle"], "libelle" , null , false )) {
				$errorMessages[]              = sprintf("Un secteur existant porte la même désignation %s , veuillez entrer une désignation différente ", $insert_data["libelle"]);
		    }  
			if(!$strNotEmptyValidator->isValid( $insert_data["code"])) {
				$errorMessages[]              = " Veuillez entrer un code valide pour secteur";
			} elseif( $model->findRow($insert_data["code"], "code" , null , false )) {
				$errorMessages[]              = sprintf("Un secteur existant porte le code %s , veuillez entrer un code différent", $insert_data["code"]);
		    }  
			$insert_data["profondeur"]        = $profondeur;
			$insert_data["special"]           = 0;
			$insert_data["creatorid"]         = $me->userid;
			$insert_data["creationdate"]      = time();	
			$insert_data["updatedate"]        = $insert_data["updateduserid"] = 0;	
            				
			if( empty($errorMessages)) {
				$emptyData                    = $model->getEmptyData();
				$clean_insert_data            = array_intersect_key( $insert_data, $emptyData);
				if( $dbAdapter->insert($modelTable->info("name"), $clean_insert_data) ) {
					$domaineid                = $dbAdapter->lastInsertId();	
 				    //On vide le cache
                    $modelCache               = $model->getMetadataCache();
					if( $modelCache ) {
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}
					$successMessage           = sprintf("Le secteur %s:%s a été enregistré avec succès",$insert_data["code"], $insert_data["libelle"]);
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => $successMessage));
						exit;
					}
					$this->setRedirect($successMessage, "success" );
					$this->redirect("admin/domaines/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement du secteur a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du secteur a echoué" , "error");
					$this->redirect("admin/domaines/list")	;
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
		$this->view->data             = $defaultData;
		$this->view->parents          = $parents ;
		$this->view->parenTree        = $tree = Model_Domaine::listTree("Sélectionnez un secteur parent");
	}
	
	
	public function editAction()
	{
		$this->view->title            = " Mettre à jour les informations d'un secteur d'activité";
		
		$domaineid                    = intval($this->_getParam("domaineid", $this->_getParam("id" , 0)));
		
		if(!$domaineid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/domaines/list");
		}		
		$model                        = $this->getModel("domaine");
		$domaine                      = $model->findRow( $domaineid , "domaineid" , null , false);		
		if(!$domaine) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=> "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/domaines/list");
		}		
		$defaultData         = $domaine->toArray();
		$errorMessages       = array();  
		$parents             = $model->getSelectListe("Sélectionnez un secteur parent",array("domaineid","libelle"), array(),null,null,false );
		
		if( $this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$update_data     = array_merge( $defaultData , $postData);
			$me              = Sirah_Fabric::getUser();
			$modelTable       = $model->getTable();
			$dbAdapter       = $modelTable->getAdapter();
			$prefixName      = $modelTable->info("namePrefix");
				
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter    = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator             = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$update_data["libelle"]           = $libelle = $stringFilter->filter($update_data["libelle"]);
			$update_data["code"]              = $stringFilter->filter($update_data["code"]);
			$update_data["parentid"]          = intval($update_data["parentid"]);
			$update_data["description"]       = $stringFilter->filter($update_data["description"]);
			$profondeur                       = 0;

			if(intval( $update_data["parentid"]) ) {
				$parent                       = $model->findRow($update_data["parentid"], "domaineid", null, false);
				if( $parent ) {
					$profondeur               = $parent->profondeur +1;
				} else {
					$errorMessages[]          = "Le secteur d'activités parent que vous avez selectionné est invalide ";
				}
			} else {
				$errorMessages[]              = "Veuillez sélectionner un secteur parent à celui que vous souhaitez mettre à jour";
			}				
			if(!$strNotEmptyValidator->isValid( $update_data["libelle"])) {
				$errorMessages[]              = " Veuillez entrer une désignation valide pour secteur";
			} elseif( $model->findRow($update_data["libelle"], "libelle" , null , false ) && ( $domaine->libelle!=$update_data["libelle"])) {
				$errorMessages[]              = sprintf(" Un secteur existant porte la même désignation %s , veuillez entrer une désignation différente ", $update_data["libelle"]);
		    } else {
				$update_data["libelle"]       = $libelle;
			}
			if(!$strNotEmptyValidator->isValid( $update_data["code"])) {
				$errorMessages[]              = " Veuillez entrer un code valide pour secteur";
			} elseif( $model->findRow($update_data["code"], "code" , null , false ) && ( $domaine->code!=$update_data["code"])) {
				$errorMessages[]              = sprintf("Un secteur existant porte le code %s , veuillez entrer un code différent", $update_data["code"]);
		    }  
			$update_data["updateduserid"]     = $me->userid;
			$update_data["updatedate"]        = time();	
			$domaine->setFromArray( $update_data );				
			if( empty($errorMessages)) {
				if( $domaine->save()) {	
                    //On vide le cache
                    $modelCache          = $model->getMetadataCache();
					if( $modelCache ) {
						$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
					}					
					$successMessage      = sprintf("Les informations du secteur %s:%s ont été mises à jour avec succès",$update_data["code"], $update_data["libelle"]);
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = $successMessage;
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect($successMessage, "success" );
					$this->redirect("admin/domaines/infos/id/".$domaineid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations du secteur"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations du secteur" , "message");
					$this->redirect("admin/domaines/infos/id/".$domaineid);
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
		$this->view->data             = $defaultData;
		$this->view->domaineid        = $this->view->domaineid = $domaineid;
		$this->view->parents          = $parents ;
		$this->view->parenTree        = $tree = Model_Domaine::listTree("Sélectionnez un secteur parent");
	}	
 		
		
	public function infosAction()
	{
		$id              = intval($this->_getParam("id", $this->_getParam("domaineid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/domaines/list");
		}		
		$model      = $this->getModel("domaine");
		$domaine    = $model->findRow( $id , "domaineid" , null , false);		
		if(!$domaine) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun secteur n'a été retrouvé avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucun secteur n'a été retrouvée avec cet identifiant" , "error");
			$this->redirect("admin/domaines/list");
		}
		$this->view->domaine   = $domaine;
		$this->view->parent    = $domaine->parent();
		$this->view->children  = $domaine->children();
		$this->view->title     = sprintf(" Les informations du secteur %s", $domaine->libelle );
		$this->view->columns   = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$me            = Sirah_Fabric::getUser();
		$model         = $this->getModel("domaine");
		$modelProject  = $this->getModel("projectfiche");
		$dbAdapter     = $model->getTable()->getAdapter();
		$tablePrefix   = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("domaineids", $this->_getParam("ids",array()));
		$errorMessages = $filtersDomaines = array();
		if( is_string($ids) ) {
			$ids       = explode("," , $ids );
		}
 
		$ids           = (array)$ids;
		if( count(   $ids)) {
			foreach( $ids as $id) {
				     $domaineProjects     = $modelProject->getList(array("domaineid"=>$id));
					 if( isset( $domaineProjects[0]["domaine"] ) ) {
						 $errorMessages[] = sprintf("Le secteur %s comporte des projets et des documents, nous vous invitions à supprimer d'abord ces dossiers", $domaineProjects[0]["domaine"]);
						 continue;
					 }
					 $filtersDomaines[]   = "domaineid='".$id."'";
					 if(!$dbAdapter->delete( $tablePrefix."rccm_domaines", $filtersDomaines )) {
						 $errorMessages   = " Erreur : Le secteur id#$id n'a pas été supprimée ou a été déjà supprimée ";
					 } else {
						 //A voir dans la prochaine version
					 }					 
			}
		} else {
			            $errorMessages[]  = " Les paramètres nécessaires à l'exécution de cette requete, sont invalides ";
		}	
		if( count($errorMessages)) {
			if(  $this->_request->isXmlHttpRequest()) {
				 echo ZendX_JQuery::encodeJson(array("error"  => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage($errorMessage , "error");
			}
			$this->redirect("admin/domaines/list");
		} else {
			$modelCache = $model->getMetadataCache();
            if( $modelCache ) {
				$modelCache->clean(Zend_Cache::CLEANING_MODE_ALL);
			}				
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les secteurs selectionnés ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les secteurs selectionnés ont été supprimées avec succès", "success");
			$this->redirect("admin/domaines/list");
		}
	}
}