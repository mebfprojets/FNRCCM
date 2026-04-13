<?php

class Admin_EntreprisesController extends Sirah_Controller_Default
{
 
	
	
	public function listAction()
	{
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("base");
		}
		$this->view->title    = "La liste des entreprises réservées ou non autorisées"  ;
		
		$model                = $this->getModel("demandeentreprise");
		$modelDomaine         = $this->getModel("domaine");
		$modelCity            = $this->getModel("countrycity");
		$modelEntreprisegroup = $this->getModel("entreprisegroup");
		$modelEntrepriseforme = $this->getModel("entrepriseforme");
		$entreprises          = array();
		$paginator            = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter         =  new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))    ? intval($params["page"])     : 1;
		$pageSize             = (isset($params["maxitems"]))? intval($params["maxitems"]) : NB_ELEMENTS_PAGE;
		$generalFilter        = (isset($params["generalfilter"]))? $stringFilter->filter($params["generalfilter"]) : (isset($params["searchq"])?$stringFilter->filter($params["searchq"]) : "");
		$filters              = array("nomcommercial"=>$generalFilter,"numrccm"=>null,"numifu"=>null,"email"=>null,"telephone"=> null,"siteweb"=> null,"groupid"=>0,"domaineid"=>0,"formid"=>0,
		                              "domaine"=>null,"country"=> null,"city"=> null,"address" => null,"demandeid"=>0,"demandeurid"=>0,"promoteurid"=>0,);
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		$entreprises             = $model->getList($filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator( $filters );
		if( null !== $paginator ) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->entreprises = $entreprises;	
		$this->view->domaines    = $modelDomaine->getSelectListe("Selectionnez un secteur d'activité", array("domaineid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->formes      = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->filters     = $filters;
		$this->view->paginator   = $paginator;
		$this->view->maxitems    = $pageSize;
		$this->view->pageNum     = $pageNum;
		$this->view->pageSize    = $pageSize;
		$this->view->columns     = array("left");
	}
	 	
	
	public function createAction()
	{
		 if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("base");
		}
		$this->view->title                 = "Enregistrer une nouvelle entreprise non autorisée ou déjà réservée";		
		$model                             = $this->getModel("demandentreprise");
		$modelEntreprisegroup              = $this->getModel("entreprisegroup");
		$modelEntrepriseforme              = $this->getModel("entrepriseforme");
		$modelDomaine                      = $this->getModel("domaine");
		$modelCity                         = $this->getModel("countrycity");

		$defaultData                       = $model->getEmptyData();
		$errorMessages                     = array();
				
		$defaultData["country"]            = "BF";
		$defaultData["blacklisted"]        = 1;
		$defaultData["reserved"]           = 0;
		
		if( $this->_request->isPost()) {
			
			$postData                      = $this->_request->getPost();	
 
			$me                            = Sirah_Fabric::getUser();
			$userTable                     = $model->getTable();
			$prefixName                    = $userTable->info("namePrefix");
			$dbAdapter                     = $userTable->getAdapter();
			
			$defaultEntrepriseData         = $modelEntreprise->getEmptyData();
			$insert_data                   = array_merge($defaultEntrepriseData,array_intersect_key($postData,$defaultEntrepriseData));
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                  = new Zend_Filter();
			$stringFilter->addFilter(        new Zend_Filter_StringTrim());
			$stringFilter->addFilter(        new Zend_Filter_StripTags());
			 
			//On crée les validateurs nécessaires
			$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			//On crée les validateurs nécessaires
			$insert_data["demandeid"]      = 0;			
            $insert_data["demandeurid"]    = 0;
			$insert_data["promoteurid"]    = 0;
			$insert_data["responsable"]    = "";
			$insert_data["catid"]          = 0;
			$insert_data["localiteid"]     = (isset($postData["localiteid"] ))? intval($postData["localiteid"]) : $me->localiteid;
			$insert_data["domaineid"]      = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : 0;
			$insert_data["formid"]         = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : 0;
			$insert_data["country"]        = (isset($postData["country"]    ))? $stringFilter->filter($postData["country"])  : "BF";
			$insert_data["city"]           = (isset($postData["city"]       ))? $stringFilter->filter($postData["city"])     : "OUA";
			$insert_data["address"]        = (isset($postData["address"]    ))? $stringFilter->filter($postData["address"])  : $promoteurData["adresse"];
			$insert_data["activite"]       = (isset($postData["activite"]   ))? $stringFilter->filter($postData["activite"]) : "";
			$insert_data["numrccm"]        = (isset($postData["numrccm"]    ))? $stringFilter->filter($postData["numrccm"])  : "";
			$insert_data["numcnss"]        = (isset($postData["numcnss"]    ))? $stringFilter->filter($postData["numcnss"])  : "";
			$insert_data["numifu"]         = (isset($postData["numifu"]     ))? $stringFilter->filter($postData["numifu"])   : "";
			$insert_data["telephone"]      = (isset($postData["telephone"]  ))? $stringFilter->filter($postData["telephone"]): "";
			$insert_data["email"]          = (isset($postData["email"]      ))? $stringFilter->filter($postData["email"])    : "";
			$insert_data["reserved"]       = (isset($postData["reserved"]   ))? intval($postData["reserved"])                : 0;
			$insert_data["blacklisted"]    = (isset($postData["blacklisted"]))? intval($postData["blacklisted"])             : 1;
			$insert_data["datecreation"]   =  $insert_data["datefermeture"] = "";
			$insert_data["creationdate"]   =  time();
			$insert_data["creatorid"]      =  $me->userid;
			$insert_data["updateduserid"]     =  $insert_data["updatedate"] = 0;
			
			if( isset($postData["nomcommercial"]) && $strNotEmptyValidator->isValid($postData["nomcommercial"])) {
				$insert_data["nomcommercial"] =  $stringFilter->filter($postData["nomcommercial"]);
			} else {
				$errorMessages[]              = "Veuillez saisir le nom commercial de l'entreprise";
			}
			if( isset($postData["sigle"]) &&  $strNotEmptyValidator->isValid($postData["sigle"])) {
				$insert_data["sigle"]         = $stringFilter->filter($postData["sigle"]);
			}
			if( empty( $errorMessages ))  {
				$clean_insert_data       = array_intersect_key( $insert_data, $defaultEntrepriseData);
				if( $dbAdapter->insert( $prefixName . "reservation_demandes_entreprises", $clean_insert_data )) {
					$entrepriseid        = $dbAdapter->lastInsertId();
					$entreprise          = $model->findRow($entrepriseid, "entrepriseid", null , false );
					
					 		
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender( true );
						$this->_helper->layout->disableLayout( true );
						$this->getResponse()->setHeader("Content-type", "application/json");
						$jsonReturnArray                 = $insert_data;
						$jsonReturnArray["entreprise"]   = $entreprise->libelle;
						$jsonReturnArray["entrepriseid"] = $entrepriseid;
						$jsonReturnArray["success"]      = "Les informations de l'entreprise ont été enregistrée savec succès";
						$jsonReturnArray["error"]        = "";
						echo ZendX_JQuery::encodeJson( $jsonReturnArray );
						exit;
					}
					$this->setRedirect("Les informations de l'entreprise ont été enregistrée savec succès", "success");
					$this->redirect("admin/demandentreprises/infos/entrepriseid/".$entrepriseid );
				}
			}
            if( count( $messages )) {
			   $defaultData       = array_merge($defaultData, $defaultEntrepriseData,$insert_data, $postData);
			   if( $this->_request->isXmlHttpRequest()) {
			       $this->_helper->viewRenderer->setNoRender(true);
			       $this->_helper->layout->disableLayout(true);
			       echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages)));
			       exit;
			    }
			    foreach($errorMessages as $errorMessage) {
			    		$this->_helper->Message->addMessage($errorMessage , "error");
			    }
			}				
		}		 				 
		$this->view->data         = $defaultData;
		$this->view->groupes      = $modelEntreprisegroup->getSelectListe("Selectionnez un groupe", array("groupid", "libelle"),array("orders" => array("libelle ASC")), null , null , false );
		$this->view->domaines     = $modelDomaine->getSelectListe("Selectionnez un domaine", array("domaineid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->formes       = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
	    $this->view->columns      = array("left");
	}
	
	public function editAction()
	{
		if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
			$this->view->isAjax  = true;
		} else {
			$this->_helper->layout->setLayout("base");
		}
		$this->view->title       = "Mettre à jour les informations de l'entreprise";
		
		$id                      = $entrepriseid = intval($this->_getParam("id" , $this->_getParam("entrepriseid" , 0)));		
		$model                   = $this->getModel("demandentreprise");
		$modelEntreprisegroup    = $this->getModel("entreprisegroup");
		$modelEntrepriseforme    = $this->getModel("entrepriseforme");
		$modelDomaine            = $this->getModel("domaine");
		$entreprise              = $entrepriseRow = $model->findRow( $entrepriseid, "entrepriseid" , null , false );
		if(!$entreprise ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender( true );
				$this->_helper->layout->disableLayout( true );
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour cette requete sont invalides", "error");
			$this->redirect("admin/demandentreprises/list");
		}
		$defaultData             = $entrepriseRow->toArray();
 
		$errorMessages           = array();
		if( $this->_request->isPost()) {
			$postData                      = $this->_request->getPost();	
			$me                            = Sirah_Fabric::getUser();
			$userTable                     = $model->getTable();
			$prefixName                    = $userTable->info("namePrefix");
			$dbAdapter                     = $userTable->getAdapter();
			
			$defaultEntrepriseData         = $entrepriseRow->toArray();
			$update_data                   = array_merge($defaultEntrepriseData,array_intersect_key($postData,$defaultEntrepriseData));
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter                  = new Zend_Filter();
			$stringFilter->addFilter(        new Zend_Filter_StringTrim());
			$stringFilter->addFilter(        new Zend_Filter_StripTags());
			 
			//On crée les validateurs nécessaires
			$strNotEmptyValidator          = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			//On crée les validateurs nécessaires
			$update_data["demandeid"]      = $defaultEntrepriseData["demandeid"];			
            $update_data["demandeurid"]    = $defaultEntrepriseData["demandeurid"];
			$update_data["promoteurid"]    = $defaultEntrepriseData["promoteurid"];
			$update_data["responsable"]    = $defaultEntrepriseData["responsable"];
			$update_data["catid"]          = $defaultEntrepriseData["catid"];
			$update_data["localiteid"]     = (isset($postData["localiteid"]   ))? intval($postData["localiteid"]) : $me->localiteid;
			$update_data["domaineid"]      = (isset($postData["domaineid"]) && isset($domaines[$postData["domaineid"]]))?$postData["domaineid"] : $defaultEntrepriseData["domaineid"];
			$update_data["formid"]         = (isset($postData["formid"])    && isset($formes[$postData["formid"]]     ))?$postData["formid"]    : $defaultEntrepriseData["formid"];
			$update_data["country"]        = (isset($postData["country"]      ))? $stringFilter->filter($postData["country"])       : $defaultEntrepriseData["country"];
			$update_data["city"]           = (isset($postData["city"]         ))? $stringFilter->filter($postData["city"])          : $defaultEntrepriseData["city"];
			$update_data["address"]        = (isset($postData["address"]      ))? $stringFilter->filter($postData["address"])       : $defaultEntrepriseData["adresse"];
			$update_data["activite"]       = (isset($postData["activite"]     ))? $stringFilter->filter($postData["activite"])      : $defaultEntrepriseData["activite"];
			$update_data["nomcommercial"]  = (isset($postData["nomcommercial"]))? $stringFilter->filter($postData["nomcommercial"]) : $defaultEntrepriseData["nomcommercial"];
			$update_data["numrccm"]        = (isset($postData["numrccm"]      ))? $stringFilter->filter($postData["numrccm"])       : $defaultEntrepriseData["numrccm"];
			$update_data["numcnss"]        = (isset($postData["numcnss"]      ))? $stringFilter->filter($postData["numcnss"])       : $defaultEntrepriseData["numcnss"];
			$update_data["numifu"]         = (isset($postData["numifu"]       ))? $stringFilter->filter($postData["numifu"])        : $defaultEntrepriseData["numifu"];
			$update_data["telephone"]      = (isset($postData["telephone"]    ))? $stringFilter->filter($postData["telephone"])     : $defaultEntrepriseData["telephone"];
			$update_data["email"]          = (isset($postData["email"]        ))? $stringFilter->filter($postData["email"])         : $defaultEntrepriseData["email"];
			$update_data["reserved"]       = (isset($postData["reserved"]     ))? intval($postData["reserved"])                     : $defaultEntrepriseData["reserved"];
			$update_data["blacklisted"]    = (isset($postData["blacklisted"]  ))? intval($postData["blacklisted"])                  : $defaultEntrepriseData["blacklisted"];
			$update_data["updatedate"]     =  time();
			$update_data["updateduserid"]  =  $me->userid;
			
			if(!$strNotEmptyValidator->isValid($update_data["nomcommercial"])) {
				$errorMessages[]           = "Veuillez saisir le nom commercial de l'entreprise";
			}
			    
			if( empty($errorMessages)) {
                if( isset( $update_data["entrepriseid"])) {
					unset( $update_data["entrepriseid"]);
				}
				$entreprise->setFromArray( $update_data );
			    if(!$entreprise->save()) {
			    	if( $this->_request->isXmlHttpRequest()) {
			    	    $this->_helper->viewRenderer->setNoRender(true);
			    		$this->_helper->layout->disableLayout(true);
			    		echo ZendX_JQuery::encodeJson(array("error"  => "Aucune mise à jour réelle n'a été appliquée dans les informations de l'entreprise"));
			    		exit;
			    	}
			    	$this->setRedirect("Aucune mise à jour réelle n'a été appliquée dans les informations de l'entreprise" , "message");
			    	$this->redirect("entreprise/infos");
			    } else {
			    	if( $this->_request->isXmlHttpRequest() ) {
							$this->_helper->viewRenderer->setNoRender( true );
							$this->_helper->layout->disableLayout( true );
							$this->getResponse()->setHeader("Content-type", "application/json");
							$jsonReturnArray                 = $updated_data;
							$jsonReturnArray["entreprise"]   = $entreprise->libelle;
							$jsonReturnArray["entrepriseid"] = $entrepriseid;
							$jsonReturnArray["success"]      = "Les informations de l'entreprise ont été mises à jour avec succès";
							$jsonReturnArray["error"]        = "";
							echo ZendX_JQuery::encodeJson( $jsonReturnArray );
							exit;
					}
					$this->setRedirect("Les informations de l'entreprise ont été mises à jour avec succès", "success");
					$this->redirect("admin/demandentreprises/infos/entrepriseid/".$entrepriseid );
			    }			    	
			} else {
			   $defaultData   = $postData;
			   if( $this->_request->isXmlHttpRequest()) {
			       $this->_helper->viewRenderer->setNoRender(true);
			       $this->_helper->layout->disableLayout(true);
			       echo ZendX_JQuery::encodeJson(array("error"  => implode(" , " , $errorMessages)));
			       exit;
			    }
			    foreach($errorMessages as $errorMessage) {
			    		$this->_helper->Message->addMessage($errorMessage , "error");
			    }
			}
		}		
		$this->view->entreprise       = $entreprise;		
		$this->view->data             = $defaultData;
		$this->view->domaines         = $modelDomaine->getSelectListe(        "Selectionnez un domaine"         , array("domaineid", "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->formes           = $modelEntrepriseforme->getSelectListe("Selectionnez une forme juridique", array("formid"   , "libelle"), array("orders" => array("libelle ASC")), null , null , false );
		$this->view->entrepriseid     = $entrepriseid;
	}
	
			
	public function infosAction()
	{		
	     if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("base");
		}
		$id                      = $entrepriseid = intval($this->_getParam("id" , $this->_getParam("entrepriseid", 0)));		
		$model                   = $this->getModel("demandentreprise");	
		$modelDemande            = $this->getModel("demande");
		$modelPromoteur          = $this->getModel("demandepromoteur");
		$modelDemandeur          = $this->getModel("demandeur");
		$entreprise              = $model->findRow($entrepriseid, "entrepriseid", null, false);		
 	
		if(!$entreprise ) {
			if( $this->_request->isXmlHttpRequest() ) {
				$this->_helper->viewRenderer->setNoRender(true);
			    $this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete ne sont pas valides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete ne sont pas valides", "error");
			$this->redirect("admin/demandentreprises/list");
		}
		
		$this->view->title       = "Les informations de l'entreprise";
		$this->view->entreprise  = $entreprise;
        $this->view->demande     = ( $entreprise->demandeid  )?$modelDemande->findRow(  $entreprise->demandeid  ,"demandeid"  , null, false) : null;	
        $this->view->demandeur   = ( $entreprise->demandeurid)?$modelDemandeur->findRow($entreprise->demandeurid,"demandeurid", null, false) : null;	
        $this->view->promoteur   = ( $entreprise->promoteurid)?$modelPromoteur->findRow($entreprise->promoteurid,"promoteurid", null, false) : null;		
		$this->view->domaine     = $entreprise->findParentRow("Table_Domaines");
		$this->view->forme       = $entreprise->findParentRow("Table_Entrepriseformes");
		$this->render("infos");
	}
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$model         = $this->getModel("demandentreprise");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixeName   = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("entrepriseids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if( count($ids)) {
			foreach($ids as $id) {
 
					$entreprise  = $model->findRow( $id , "entrepriseid" , null , false );
					if( $entreprise) {
						$demandeid            = $entreprise->demandeid;
						$demandeurid          = $entreprise->demandeurid;
						$promoteurid          = $entreprise->promoteurid;
						if(!$entreprise->delete()) {
							$errorMessages[]  = " Erreur de la base de donnée L'entreprise id#$id n'a pas été supprimée ";
						} else {
							$dbAdapter->delete( $prefixeName."reservation_demandes"  , "entrepriseid=".$id);
							$dbAdapter->delete( $prefixeName."reservation_demandeurs", "demandeurid=" .$demandeurid);
							$dbAdapter->delete( $prefixeName."reservation_promoteurs", "promoteurid=" .$promoteurid);
						}
					} else {
							$errorMessages[]  = "Aucune entrée valide n'a été trouvée pour cette entreprise id #$id ";
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
			$this->redirect("admin/demandentreprises/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les entreprises selectionnées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les entreprises selectionnées ont été supprimées avec succès", "success");
			$this->redirect("admin/demandentreprises/list");
		}
	}	
}