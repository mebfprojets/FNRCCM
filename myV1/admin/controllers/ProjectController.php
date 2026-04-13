<?php

class Admin_ProjectController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{
		$this->view->title  = "Vos projets";
		$model              = $this->getModel("project");
		$projects           = array();
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
		
		$filters            = array("libelle"         => null, "periode_start_day"   => DEFAULT_START_DAY,"periode_start_month"      => DEFAULT_START_MONTH,"periode_start_year" => DEFAULT_START_YEAR, 
		                            "periode_end_day" => DEFAULT_END_DAY,"periode_end_month" => DEFAULT_END_MONTH,"periode_end_year" => DEFAULT_END_YEAR);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$projects                = $model->getList( $filters , $pageNum , $pageSize);
		$paginator               = $model->getListPaginator($filters);
		
		if( null !== $paginator) {
			$paginator->setCurrentPageNumber( $pageNum  );
			$paginator->setItemCountPerPage(  $pageSize );
		}
		$this->view->columns      = array("left");
		$this->view->projects     = $projects;
		$this->view->filters      = $filters;
		$this->view->paginator    = $paginator;
		$this->view->pageNum      = $pageNum;
		$this->view->pageSize     = $pageSize;							 
	}		
	
	
	public function createAction()
	{
		$this->view->title            = "Enregistrer un projet";
	    $entrepriseid                 = intval($this->_getParam("id" , $this->_getParam("entrepriseid" , ENTREPRISEID)));
		$model                        = $this->getModel("project");
		
		$defaultData                  = $model->getEmptyData();
		$defaultData["startime_date"] = date("Y-m-d");
		$defaultData["endtime_date"]  = date("Y-m-d");
		$errorMessages                = array();

        if( $this->_request->isPost() ) {
			$postData                                = $this->_request->getPost();
			$formData                                = array_intersect_key($postData ,  $model->getEmptyData() );
			$insert_data                             = array_merge( $model->getEmptyData() , $formData);
			$me                                      = Sirah_Fabric::getUser();
			$userTable                               = $me->getTable();
			$dbAdapter                               = $userTable->getAdapter();
			$prefixName                              = $userTable->info("namePrefix");	
			
			$stringFilter                            = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			$strNotEmptyValidator                    = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$floatFiltre                             = new Sirah_Filtre_Float();
																	
			$zendDateDebut                           = (isset($postData["startime_date"]) && !empty($postData["startime_date"])) ? new Zend_Date($postData["startime_date"]) : null;
			$zendDateFin                             = (isset($postData["endtime_date"] ) && !empty($postData["endtime_date"] )) ? new Zend_Date($postData["endtime_date"])  : null;
			$insert_data["startime"]                 = ($zendDateDebut)? $zendDateDebut->get(Zend_Date::TIMESTAMP) : 0;
			$insert_data["endtime"]                  = ($zendDateFin)  ? $zendDateFin->get(  Zend_Date::TIMESTAMP) : 0;			
			$insert_data["entrepriseid"]             = $entrepriseid;
			$insert_data["libelle"]                  = $libelle =  $stringFilter->filter($insert_data["libelle"]);
			$insert_data["code"]                     = $stringFilter->filter($insert_data["code"]);
			$insert_data["introduction"]             = $stringFilter->filter($insert_data["introduction"]);
			$insert_data["objectif_global"]          = $stringFilter->filter($insert_data["objectif_global"]);
			$insert_data["objectif_strategique"]     = $stringFilter->filter($insert_data["objectif_strategique"]);
			$insert_data["context"]                  = $stringFilter->filter($insert_data["context"]);
			$insert_data["objectif_nb_rccm"]         = $floatFiltre->filter( $insert_data["objectif_nb_rccm"]);
			$insert_data["productkey"]               = "";
			$insert_data["productkeysalt"]           = "";
			$insert_data["params"]                   = "";
			$insert_data["responsable"]              = $stringFilter->filter( $insert_data["responsable"]);
			$insert_data["presentation"]             = $stringFilter->filter( $insert_data["presentation"]);
			$insert_data["presentation_structure"]   = $stringFilter->filter( $insert_data["presentation_structure"]);
			$insert_data["presentation_equipe"]      = $stringFilter->filter( $insert_data["presentation_equipe"]);
			$insert_data["current"]                  = (isset($postData["current"])) ? intval($postData["current"]) : 0;
			$insert_data["statut"]                   = (isset($postData["statut"] )) ? intval($postData["statut"])  : 0;
			$insert_data["creatorid"]                = $me->userid;
			$insert_data["creationdate"]             = time();
			$insert_data["entrepriseid"]             = $entrepriseid;
			$insert_data["updateduserid"]            = 0;
			$insert_data["updatedate"]               = 0;
			
			if(!$strNotEmptyValidator->isValid( $insert_data["libelle"])) {
				$errorMessages[]                     = " Veuillez entrer une désignation valide pour ce projet";
			} else {
				$insert_data["libelle"]              = $libelle;
			}
			if(!intval($insert_data["startime"]))  {
				$errorMessages[]                     = "Veuillez saisir une période initiale valide";
			}
			if(!intval($insert_data["endtime"]))  {
				$errorMessages[]                     = "Veuillez saisir une période de fin valide";
			}
			if( intval($insert_data["startime"]) > intval($insert_data["endtime"]) ) {
				$errorMessages[]                     = "La période initiale ne doit pas etre supérieure à  la période finale";
			}
			if(intval($insert_data["startime"]) && intval($insert_data["endtime"]) ) {
				$thisPeriodeProjects                 = $model->getList(array("startime" => intval($insert_data["startime"]),"endtime" => intval($insert_data["endtime"])));
				if(count( $thisPeriodeProjects )) {
					$errorMessages[]                 = sprintf("Un projet a été défini dans cette période : %s - %s", date("d/m/Y", intval($insert_data["startime"])), date("d/m/Y", intval($insert_data["endtime"])));
				}
			}
			if(!count( $errorMessages )) {
				if( intval($insert_data["current"]) == 1) {
					$dbAdapter->update( $prefixName ."rccm_projet_application", array("current" => 0), "entrepriseid=".$entrepriseid  );
				}
				if( $dbAdapter->insert( $prefixName ."rccm_projet_application", $insert_data) ) {
					$projectid      = $dbAdapter->lastInsertId();
                    $project        = $model->findRow( $projectid , "projectid", null , false );					
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "Votre projet a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Votre projet a été enregistré avec succès", "success" );
					$this->redirect("admin/project/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement du projet a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du projet a echoué" , "error");
					$this->redirect("admin/project/list")	;
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
		 $this->view->data         = $defaultData;
		 $this->view->params       = $defaultParams;	
		 $this->view->entrepriseid = $entrepriseid;
	}
	
	public function editAction()
	{
		$this->view->title        = "Mettre à  jour les informations d'un projet";
	    $projectid                = intval($this->_getParam("id", $this->_getParam("projectid", PROJECTID)));
		$entrepriseid             = intval($this->_getParam("entrepriseid", ENTREPRISEID));
		$model                    = $this->getModel("project");
		
		if(!$projectid) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/project/list");
		}		
		$project                  = $model->findRow($projectid , "projectid" , null , false);
        if(!$project) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramères fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides", "error");
			$this->redirect("admin/project/list");
		}		
		$defaultData                  = $project->toArray();
		$defaultData["startime_date"] = date("Y-m-d", $project->startime);
		$defaultData["endtime_date"]  = date("Y-m-d", $project->endtime);
		$errorMessages                = array();

        if( $this->_request->isPost() ) {
			$postData                 = $this->_request->getPost();
			$formData                 = array_intersect_key($postData ,  $defaultData);
			$updated_data             = array_merge( $defaultData, $formData);
			$me                       = Sirah_Fabric::getUser();
			$userTable                = $me->getTable();
			$dbAdapter                = $userTable->getAdapter();
			$prefixName               = $userTable->info("namePrefix");	
			
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter      = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
				
			//On crée les validateurs nécessaires
			$strNotEmptyValidator       = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			$floatFiltre                = new Sirah_Filtre_Float();
					
			$zendDateDebut                            = (isset($postData["startime_date"]) && !empty($postData["startime_date"])) ? new Zend_Date($postData["startime_date"]) : null;
			$zendDateFin                              = (isset($postData["endtime_date"] ) && !empty($postData["endtime_date"] )) ? new Zend_Date($postData["endtime_date"]) : null;
			$updated_data["startime"]                 = ($zendDateDebut)? $zendDateDebut->get(Zend_Date::TIMESTAMP) : 0;
			$updated_data["endtime"]                  = ($zendDateFin)  ? $zendDateFin->get(  Zend_Date::TIMESTAMP) : 0;
			
			$updated_data["entrepriseid"]             = $entrepriseid;
			$updated_data["libelle"]                  = $libelle =  $stringFilter->filter($updated_data["libelle"]);
			$updated_data["code"]                     = $stringFilter->filter($updated_data["code"]);
			$updated_data["introduction"]             = $stringFilter->filter($updated_data["introduction"]);
			$updated_data["objectif_global"]          = $stringFilter->filter($updated_data["objectif_global"]);
			$updated_data["objectif_strategique"]     = $stringFilter->filter($updated_data["objectif_strategique"]);
			$updated_data["context"]                  = $stringFilter->filter($updated_data["context"]);
			$updated_data["objectif_nb_rccm"]         = $floatFiltre->filter( $updated_data["objectif_nb_rccm"]);
			$updated_data["productkey"]               = "";
			$updated_data["productkeysalt"]           = "";
			$updated_data["params"]                   = "";
			$updated_data["responsable"]              = $stringFilter->filter( $updated_data["responsable"]);
			$updated_data["presentation"]             = $stringFilter->filter( $updated_data["presentation"]);
			$updated_data["presentation_structure"]   = $stringFilter->filter( $updated_data["presentation_structure"]);
			$updated_data["presentation_equipe"]      = $stringFilter->filter( $updated_data["presentation_equipe"]);
			$updated_data["current"]                  = (isset($postData["current"])) ? intval($postData["current"]) : $project->current;
			$updated_data["statut"]                   = (isset($postData["statut"] )) ? intval($postData["statut"])  : $project->statut;
			$updated_data["updateduserid"]            = $me->userid;
			$updated_data["updatedate"]               = time();
			
			if(!$strNotEmptyValidator->isValid( $updated_data["libelle"])) {
				$errorMessages[]                      = "Veuillez entrer une désignation valide pour ce projet";
			} else {
				$updated_data["libelle"]              = $libelle;
			}
			if(!intval($updated_data["startime"]))    {
				$errorMessages[]                      = "Veuillez saisir une période initiale valide";
			}
			if(!intval($updated_data["endtime"]))     {
				$errorMessages[]                      = "Veuillez saisir une période de fin valide";
			}
			if( intval($updated_data["startime"]) > intval($updated_data["endtime"]) ) {
				$errorMessages[]                      = "La période initiale ne doit pas etre supérieure à  la période finale";
			}
			if(intval($updated_data["startime"]) && intval($updated_data["endtime"]) ) {
				$selectProjects = $dbAdapter->select()->from(array("P" => $prefixName."rccm_projet_application"))
				                                      ->where("P.endtime  >= ?", intval($updated_data["startime"]))
													  ->where("P.startime <= ?", intval($updated_data["endtime"]))
													  ->where("projectid  <> ?", intval($projectid));
				$projects  = $dbAdapter->fetchAll($selectProjects);	
                if(count( $projets ))   {
					$errorMessages[]    = sprintf("Un projet a été défini dans cette période : %s - %s", date("d/m/Y", intval($updated_data["startime"])), date("d/m/Y", intval($updated_data["endtime"])));
                }							 
			}
			if(!count( $errorMessages )) {
				if( intval($updated_data["current"]) == 1) {
					$dbAdapter->update( $prefixName ."rccm_projet_application", array("current" => 0), "entrepriseid=".$entrepriseid  );
				}
				$project->setFromArray( $updated_data );
				if( $project->save() ) {					
					if( $this->_request->isXmlHttpRequest() ) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success" => "Votre projet a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Votre projet a été enregistré avec succès", "success" );
					$this->redirect("admin/project/list");					
				}  else {
					if( $this->_request->isXmlHttpRequest() ) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error" => "L'enregistrement du projet a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du projet a echoué" , "error");
					$this->redirect("admin/project/list")	;
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
		 $this->view->data         = $defaultData;
		 $this->view->entrepriseid = $entrepriseid;
	}
	
	public function paramsAction()
	{
		$id              = $projectid = intval($this->_getParam("id", $this->_getParam("projectid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/project/list");
		}		
		$model           = $this->getModel("project");
		$modelDomaine    = $this->getModel("domaine");
		$project         = $model->findRow($id, "projectid", null , false);		
		if(!$project) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun projet n'a été retrouvé avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucun projet n'a été retrouvé avec cet identifiant" , "error");
			$this->redirect("admin/project/list");
		}
        $entreprise                   = $project->findParentRow("Table_Entreprises");	
        if(!$entreprise) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucune entreprise n'est rattachée à  ce projet"));
				exit;
			}
			$this->setRedirect("Aucune entreprise n'est rattachée à  ce projet" , "error");
			$this->redirect("admin/project/list");
		}		
		$defaultParams                = $project->paramsToArray();
		if(empty( $defaultParams["default_pdf_header"] )) {
		   $defaultParams["default_pdf_header"] = "<table cellspacing=\"2\" cellpadding=\"2\" width=\"100%\" align=\"center\">
		                                             <tbody><tr> <td width=\"30%\"> [LOGO]          </td>
													             <td width=\"70%\"> [RAISON_SOCIAL] </td></tr></tbody>
												  </table> ";
		}
		//print_r($defaultParams);die();
		$errorMessages                  = array();
		$roles                          = Sirah_User_Acl_Table::getAllRoles();
		$domaines                       = $modelDomaine->getSelectListe( "Selectionnez un secteur d'activité", array("domaineid" , "libelle"), array() , 0 , null , false);
		$defaultParams["startime_date"] = (intval($defaultParams["default_period_start"])) ? date("Y-m-d", intval($defaultParams["default_period_start"])): null;
		$defaultParams["endtime_date"]  = (intval($defaultParams["default_period_end"]  )) ? date("Y-m-d", intval($defaultParams["default_period_end"])) : null;
		
		if( $this->_request->isPost() ) {
			$postData                 = $this->_request->getPost();
			$formData                 = array_intersect_key($postData ,$defaultParams);
			$update_params            = array_merge($defaultParams, $formData);
			$me                       = Sirah_Fabric::getUser();
			$userTable                = $me->getTable();
			$dbAdapter                = $userTable->getAdapter();
			$prefixName               = $userTable->info("namePrefix");            		
			
			$stringFilter             = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			$zendDateDebut                                = (isset($postData["startime_date"]) && !empty($postData["startime_date"]))? new Zend_Date($postData["startime_date"]): null;
			$zendDateFin                                  = (isset($postData["endtime_date"] ) && !empty($postData["endtime_date"] ))? new Zend_Date($postData["endtime_date"]) : null;
			$update_params["default_period_start"]        = ($zendDateDebut)? $zendDateDebut->get(Zend_Date::TIMESTAMP) : 0;
			$update_params["default_period_end"]          = ($zendDateFin)  ? $zendDateFin->get(  Zend_Date::TIMESTAMP) : 0;
			
			$update_params["nb_elements_page"]            = intval($update_params["nb_elements_page"]);
			$update_params["default_year"]                = ($update_params["default_year"]== "CURRENT") ? date("Y") : intval($update_params["default_year"]);
			$update_params["default_domaineid"]           = intval($update_params["default_domaineid"]);
			$update_params["default_check_documents"]     = intval($update_params["default_check_documents"]);
			$update_params["default_find_documents"]      = intval($update_params["default_find_documents"]);
			$update_params["default_find_documents_src"]  = $stringFilter->filter($update_params["default_find_documents_src"]);
			
			 
			if(!intval( $update_params["default_period_start"] )) {
				$errorMessages[]                     = "Veuillez fournir une période initiale valide";
			}
			if(!intval( $update_params["default_period_end"] )) {
				$errorMessages[]                     = "Veuillez fournir une période de fin valide";
			}			
			/*if(!isset($roles[$update_params["discount_increasing_request_to"]])) {
				$errorMessages[]                     = "Veuillez selectionner un role valide pour les demandes d'augmentation de remise";
			}*/
            if(isset($update_params["default_pdf_header"]) && !empty($update_params["default_pdf_header"])) {
				$update_params["default_pdf_header"] = preg_replace("/(src=\")(.*)myV1(.*)/", "$1".ROOT_PATH."/myV1/$3", $entreprise->htmlOutput($update_params["default_pdf_header"]));
			}
            if(isset($update_params["default_pdf_footer"]) && !empty($update_params["default_pdf_footer"])) {
				$update_params["default_pdf_footer"] = $entreprise->htmlOutput($update_params["default_pdf_footer"]);
			}		
			if( empty( $errorMessages )) {
				if( $project->setParams($update_params) ) {
					if( $this->_request->isXmlHttpRequest() ) {
						$jsonData                  = $update_params;
						$jsonData["success"]       = "Les informations de votre projet ont été enregistrés avec succés";
						echo ZendX_JQuery::encodeJson($jsonData);
						exit;
					}
					$this->setRedirect("Les paramètres du projet ont été mis à  jour avec succès", "success");
					$this->redirect("admin/project/infos/projectid/".$projectid);
				}
			}
			if(count( $errorMessages )) {
				$defaultParams    = $postData;
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
		$this->view->title                         = sprintf("Mise à  jour des paramètres du projet %s", $project->libelle);
		$this->view->roles                         = $roles;
		$this->view->domaines                      = $domaines;
		$this->view->data                          = $defaultParams;
		$this->view->projectid                     = $projectid;
		$this->view->project                       = $project;
		$this->render("editparams");
		
	}

	
	public function infosAction()
	{
		$id              = intval($this->_getParam("id", $this->_getParam("projectid"  , 0)));
		if(!$id) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Les paramètres fournis pour l'exécution de cette requete sont invalides"));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour l'exécution de cette requete sont invalides" , "error");
			$this->redirect("admin/project/list");
		}		
		$model           = $this->getModel("project");
		$project         = $model->findRow( $id , "projectid" , null , false);		
		if(!$project) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				echo ZendX_JQuery::encodeJson(array("error" => "Aucun projet n'a été retrouvé avec cet identifiant"));
				exit;
			}
			$this->setRedirect("Aucun projet n'a été retrouvé avec cet identifiant" , "error");
			$this->redirect("admin/project/list");
		}
		$this->view->project    = $project;
		$this->view->params     = $project->getParams();
		$this->view->title      = "Les informations d'un projet";
		$this->view->columns    = array("left");	
	}
	
	public function deleteAction()
	{					
		$model         = $this->getModel("project");
		$dbAdapter     = $model->getTable()->getAdapter();
		$prefixName    = $model->getTable()->info("namePrefix");
		$ids           = $this->_getParam("projectids", $this->_getParam("ids",array()));
		$errorMessages = array();
		if( is_string($ids) ) {
			$ids  = explode(",", $ids );
		}
		$ids      = (array)$ids;
		if(count($ids)) {
			foreach($ids as $id) {
				    $project   = $model->findRow( $id, "projectid", null , false);
					$projects  = $model->getList();
					if(($project->current==1) && (count($projects ) > 1)) {						
						$firstProjectId  =  $projects[1]["projectid"];
						if( $dbAdapter->update($prefixName."rccm_projet_application",array("current" => 1), "projectid=".$firstProjectId )) {
							if(!$dbAdapter->delete( $prefixName."rccm_projet_application", "projectid=".$id)) {					 
					            $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour ce projet id #$id ";
				            }
						} else {
								    $errorMessages[]  = "Aucun autre projet en exercice n'a pu etre défini ";
						} 
					} elseif(count($projects ) == 1 ) {
						           $errorMessages[]   = " Il ne reste qu'un seul projet dans votre système";
			        } else {
						if(!$dbAdapter->delete( $prefixName."rccm_projet_application", "projectid=".$id)) {					 
					        $errorMessages[]  = "Aucune entrée valide n'a été trouvée pour ce projet id #$id ";
				        }            
					}						
			}
		} else {
			            $errorMessages[]  = " Les parametres nécessaires à  l'exécution de cette requete, sont invalides ";
		}
		if(count($errorMessages)) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
		        $this->_helper->layout->disableLayout(true);
				 echo ZendX_JQuery::encodeJson(array("error" => implode("," , $errorMessages)));
				 exit;
			}
			foreach( $errorMessages as $errorMessage) {
				     $this->_helper->Message->addMessage( $errorMessage , "error");
			}
			$this->redirect("admin/project/list");
		} else {
			if( $this->_request->isXmlHttpRequest() ) {
				echo ZendX_JQuery::encodeJson(array("success" => "Les projets indiqués ont  été supprimés avec succès"));
				exit;
			}
			$this->setRedirect("Les projets indiqués ont  été supprimés avec succés", "success");
			$this->redirect("admin/project/list");
		}
	}		
}