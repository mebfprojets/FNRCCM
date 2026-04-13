<?php

class DomainesController extends Sirah_Controller_Default
{
  	
	
	public function listAction()
	{		
	    if( $this->_request->isXmlHttpRequest()) {
			$this->_helper->layout->disableLayout(true);
		} else {
			$this->_helper->layout->setLayout("default");
		}			
		$this->view->title  = " Les domaines d'activites"  ;
		
		$model              = $this->getModel("domaine");
		
		$domaines           = array();
		$paginator          = null;
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter       = new Zend_Filter();
		$stringFilter->addFilter(new Zend_Filter_StringTrim());
		$stringFilter->addFilter(new Zend_Filter_StripTags());
		
		//On crée un validateur de filtre
		$strNotEmptyValidator = new Zend_Validate_NotEmpty(array("integer","zero","string","float","empty_array","null"));
				
		$params               = $this->_request->getParams();
		$pageNum              = (isset($params["page"]))     ? intval($params["page"]) : 1;
		$pageSize             = (isset($params["maxitems"])) ? intval($params["maxitems"]) : 10;		
		
		$filters              = array("libelle" => null, "parentid" => null );		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$domaines             = $model->getList( $filters , $pageNum , $pageSize);
		$paginator            = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns   = array("left");
		$this->view->domaines = $domaines;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		$this->view->pageNum   = $pageNum;
		$this->view->pageSize  = $pageSize;			
	}
	
	public function createAction()
	{
		$this->view->title     = "Enregistrer un secteur d'activités ";
		
		$model                 = $this->getModel("domaine");
		
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
			
			$libelle              = $stringFilter->filter($insert_data["libelle"]);
			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]  = " Veuillez entrer une désignation valide pour ce secteur d'activités";
			} elseif( $model->findRow( $libelle , "libelle" , null , false )) {
				$errorMessages[]                = sprintf(" Un secteur d'activités existant porte la désignation %s , veuillez entrer une désignation différente " , $libelle );
		    } else {
				$insert_data["libelle"]         = $libelle;
			}						 
			$insert_data["description"]         = $stringFilter->filter( $insert_data["description"] );
			$insert_data["parentid"]            = intval( $insert_data["parentid"] );
			$insert_data["creatorid"]           = $me->userid;
			$insert_data["creationdate"]        = time();											
			if(empty($errorMessages)) {
				if( $dbAdapter->insert( $prefixName . "rccm_domaines", $insert_data) ) {
					$domaineid       = $dbAdapter->lastInsertId();				
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "Le secteur d'activités a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Le secteur d'activités a été enregistré avec succès", "success" );
					$this->redirect("domaines/infos/id/" . $domaineid );					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement du secteur d'activités a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du secteur d'activités a echoué" , "error");
					$this->redirect("domaines/list")	;
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
		$this->view->title      = " Mettre à jour les informations d'un secteur d'activités  ";
		
		$domaineid              = intval($this->_getParam("domaineid", $this->_getParam("id" , 0)));
		
		if(!$domaineid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>"Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("domaines/list");
		}		
		$model                = $this->getModel("domaine");
 	
		$domaine              = $model->findRow( $domaineid , "domaineid" , null , false);		
		if(!$domaine) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("domaines/list");
		}		
		$defaultData         = $domaine->toArray();
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
				$errorMessages[]           = " Veuillez entrer une désignation valide du secteur d'activités";
			} elseif( $model->findRow( $libelle , "libelle" , null , false ) && ( $domaine->libelle != $libelle ) ) {
				$errorMessages[]           = sprintf(" Un secteur d'activité existant porte la désignation %s , veuillez entrer une désignation différente ", $libelle );
		    } else {
				$update_data["libelle"]    = $libelle;
			}
			$update_data["parentid"]       = intval( $update_data["parentid"] );
			$update_data["description"]    = $stringFilter->filter( $update_data["description"] );
			$update_data["updateduserid"]  = $me->userid;
			$update_data["updatedate"]     = time();	
			$domaine->setFromArray( $update_data);				
			if(empty($errorMessages)) {
				if( $domaine->save()) {					 					
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray            = $update_data;
						$jsonErrorArray["success"] = "Les informations du secteur d'activités ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations du secteur d'activités ont été mises à jour avec succès", "success" );
					$this->redirect("domaines/infos/id/".$domaineid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations du secteur d'activités"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations du secteur d'activités" , "message");
					$this->redirect("domaines/infos/id/".$domaineid);
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
		$this->view->domaineid   = $domaineid;
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
			$this->redirect("domaines/list");
		}		
		$model          = $this->getModel("domaine");
		$domaine        = $model->findRow( $id , "domaineid" , null , false);		
		if(!$domaine) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error"  => " Aucune entrée de secteur d'activités n'a été retrouvée avec cet identifiant "));
				exit;
			}
			$this->setRedirect(" Aucune entrée de secteur d'activités n'a été retrouvée avec cet identifiant " , "error");
			$this->redirect("domaines/list");
		}
		$this->view->domaine    = $domaine;
		$this->view->title      = " Les informations d'un secteur d'activités ";
		$this->view->columns    = array("left");	
	} 	
	
	
	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout(true);
		$model         = $this->getModel("domaine");
		$ids           = $this->_getParam("domaineids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode("," , $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach(  $ids as $id) {
				$domaine  = $model->findRow( $id , "domaineid" , null , false );
				if($domaine) {
					if(!$domaine->delete()) {
						$errorMessages[]  = " Erreur de la base de donnée Le secteur d'activités id#$id n'a pas été supprimé ";
					}
				} else {
					    $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour le secteur d'activités id #$id ";
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
			$this->redirect("domaines/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les domaines d'activités indiqués ont été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les domaines d'activités indiqués ont été supprimés avec succès", "success");
			$this->redirect("domaines/list");
		}
	}
}