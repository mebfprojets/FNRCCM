<?php

class LocalitesController extends Sirah_Controller_Default
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
					$this->redirect("localites/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement de la localité a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement de la localité a echoué" , "error");
					$this->redirect("localites/list")	;
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
			$this->redirect("localites/list");
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
			$this->redirect("localites/list");
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
					$this->redirect("localites/infos/id/".$localiteid);	
				}  else {
					if( $this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "Aucune modifiation n'a été apportée sur les informations de la localité"));
						exit;
					}
					$this->setRedirect("Aucune modifiation n'a été apportée sur les informations de la localité" , "message");
					$this->redirect("localites/infos/id/".$localiteid);
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
			$this->redirect("localites/list");
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
			$this->redirect("localites/list");
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
			$this->redirect("localites/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les localités indiquées ont été supprimées avec succès"));
				exit;
			}
			$this->setRedirect("Les localités indiquées ont été supprimées avec succès", "success");
			$this->redirect("localites/list");
		}
	}
}