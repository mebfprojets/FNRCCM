<?php

class EntreprisemembersController extends Sirah_Controller_Default
{
	
	
	public function listAction()
	{		
		$this->_helper->layout->setLayout("base");
		$this->view->title  = " Gestion du personnel de l'entreprise "  ;
		
		$model              = $this->getModel("structurepersonnel");
		$members            = array();
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
		$defaultName        = (isset($params["global-filter"]) && !empty($params["global-filter"])) ? $stringFilter->filter($params["global-filter"]) : null;		
		$filters            = array(
				                    "lastname"  => null,
				                    "firstname" => $defaultName,
				                    "email"     => null,
				                    "country"   => null,
				                    "language"  => null);
		
		if(!empty($params)) {
			foreach($params as $filterKey => $filterValue){
				$filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}		
		$members              = $model->getList($filters , $pageNum , $pageSize);
		$paginator            = $model->getListPaginator($filters);
		
		if(null !== $paginator) {
			$paginator->setCurrentPageNumber($pageNum);
			$paginator->setItemCountPerPage($pageSize);
		}
		$this->view->columns   = array("left");
		$this->view->members   = $members;
		$this->view->filters   = $filters;
		$this->view->paginator = $paginator;
		
	}
	
	public function createAction()
	{
		$this->view->title  = " Enregistrer un nouveau membre du personnel ";
		
		$model              = $this->getModel("structurepersonnel");
		
		$defaultData        = $model->getEmptyData();
		$errorMessages      = array();
		
		if($this->_request->isPost()) {
			$postData        = $this->_request->getPost();
			$formData        = array_intersect_key($postData ,  $defaultData);
			$insert_data     = array_merge( $defaultData , $formData);
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
			$emailValidator       = new Sirah_Validateur_Email();

			if(!$emailValidator->isValid($insert_data["email"])) {
				$errorMessages[]  = " Veuillez entrer une adresse email valide";
			}
			if(!$strNotEmptyValidator->isValid($insert_data["telephone"])) {
				$errorMessages[]  = " Veuillez entrer un numéro de téléphone mobile valide";
			}
			if(!$strNotEmptyValidator->isValid($insert_data["firstname"])) {
				$errorMessages[]  = "Le prénom que vous avez saisi pour le contact, est invalide";
			} else {
				$insert_data["firstname"] = $stringFilter->filter($insert_data["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($insert_data["lastname"])) {
				$errorMessages[]  = "Le nom que vous avez saisi est invalide";
			} else {
				$insert_data["lastname"] = $stringFilter->filter($insert_data["lastname"]);
			}
			$postData["birthday_year"]        = (isset($postData["birthday_year"]))  ? sprintf("%04d",$postData["birthday_year"])  : "0000";
			$postData["birthday_month"]       = (isset($postData["birthday_month"])) ? sprintf("%02d",$postData["birthday_month"]) : "00";
			$postData["birthday_day"]         = (isset($postData["birthday_day"]))   ? sprintf("%02d",$postData["birthday_day"])    : "00";
			$zendBirthDay                     = new Zend_Date(array( "year"  => $postData["birthday_year"]  ,
					                                                 "month" => $postData["birthday_month"] ,
					                                                 "day"   => $postData["birthday_day"]));
			$insert_data["birthday"]          = $zendBirthDay->getTimestamp();
			$insert_data["entrepriseid"]      = 1;
			$insert_data["matricule"]         = Sirah_Functions_Generator::getRandomVar(5);
			$insert_data["birthaddress"]      = $stringFilter->filter($insert_data["birthaddress"]);
			$insert_data["presentation"]      = $stringFilter->filter($insert_data["presentation"]);
			$insert_data["socialstate"]       = $stringFilter->filter($insert_data["socialstate"]);
			$insert_data["professionalstate"] = $stringFilter->filter($insert_data["professionalstate"]);
			$insert_data["language"]          = $stringFilter->filter($insert_data["language"]);
			$insert_data["sexe"]              = $stringFilter->filter($insert_data["sexe"]);
			$insert_data["creatoruserid"]     = 0;
			$insert_data["creationdate"]      = time();						
			$defaultData                      = $insert_data;
		
			if(empty($errorMessages)) {
				if($dbAdapter->insert($prefixName . "system_structure_personnel" , $insert_data)) {
					$memberid          = $dbAdapter->lastInsertId();
					$member            = $model->findRow( $memberid , "id" , null , false );					
					$member->matricule = $member->matricule . "" .$memberid;
					$member->save();
				
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("success"  => "Le membre du personnel  a été enregistré avec succès"));
						exit;
					}
					$this->setRedirect("Le membre du personnel  a été enregistré avec succès", "success" );
					$this->redirect("entreprisemembers/infos/id/".$memberid);
					
				}  else {
					if($this->_request->isXmlHttpRequest()) {						
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "L'enregistrement du membre du personnel a echoué"));
						exit;
					}
					$this->setRedirect("L'enregistrement du membre du personnel a echoué" , "error");
					$this->redirect("entreprisemembers/list")	;
				}
			} else {
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach($errorMessages as $message) {
					$this->_helper->Message->addMessage($message) ;
				}
			}
		}
	    $defaultData["birthday_year"] = (isset($defaultData["birthday_year"]))  ? sprintf("%04d" , $defaultData["birthday_year"])  : "0000";
	    $defaultData["birthday_month"]= (isset($defaultData["birthday_month"])) ? sprintf("%02d" , $defaultData["birthday_month"]) : "00";
	    $defaultData["birthday_day"]  = (isset($defaultData["birthday_day"]))   ? sprintf("%02d" , $defaultData["birthday_day"])    : "00";
		$this->view->data             = $defaultData;
	}
	
	
	public function editAction()
	{
		$this->view->title = "Mettre à jour les informations d'un membre du personnel";
		$memberid          = $this->_getParam("id" , $this->_getParam("memberid" , 0));
				
		if(!$memberid) {
			if($this->_request->isXmlHttpRequest()) {
			    $this->_helper->viewRenderer->setNorender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour cette requete sont invalides "));
					exit;
			}
		   $this->setRedirect("Les paramètres fournis pour cette requete sont invalides" , "error");
		   $this->redirect("entreprisemembers/list");
		}
		$model          = $this->getModel("entreprisemember");
		$member         = $model->findRow($memberid , "id" , null , false);
		$errorMessages  = array();
				
		if(!$member) {
			if($this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNorender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour cette requete sont invalides "));
				exit;
			}
			$this->setRedirect("Les paramètres fournis pour cette requete sont invalides" , "error");
			$this->redirect("entreprisemembers/list");
		}
		$defaultData         = $contact->toArray();
		$errorMessages       = array();
	
		if($this->_request->isPost()) {
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
			
			if(!$strNotEmptyValidator->isValid($update_data["firstname"])) {
				$errorMessages[]  = "Le prénom que vous avez saisi , est invalide";
			} else {
				$update_data["firstname"] = $stringFilter->filter($update_data["firstname"]);
			}
			if(!$strNotEmptyValidator->isValid($update_data["lastname"])) {
				$errorMessages[]  = "Le nom que vous avez saisi est invalide";
			} else {
				$update_data["lastname"]      = $stringFilter->filter($update_data["lastname"]);
			}
			$postData["birthday_year"]        = (isset($postData["birthday_year"]))  ? sprintf("%04d",$postData["birthday_year"])  : "0000";
			$postData["birthday_month"]       = (isset($postData["birthday_month"])) ? sprintf("%02d",$postData["birthday_month"]) : "00";
			$postData["birthday_day"]         = (isset($postData["birthday_day"]))   ? sprintf("%02d",$postData["birthday_day"])    : "00";
			$zendBirthDay                     = new Zend_Date(array( "year"  => $postData["birthday_year"]  ,
					                                                 "month" => $postData["birthday_month"] ,
					                                                 "day"   => $postData["birthday_day"]));
			$update_data["birthday"]          = $zendBirthDay->getTimestamp();
			$update_data["presentation"]      = $stringFilter->filter($update_data["presentation"]);
			$update_data["socialstate"]       = $stringFilter->filter($update_data["socialstate"]);
			$update_data["professionalstate"] = $stringFilter->filter($update_data["professionalstate"]);
			$update_data["language"]          = $stringFilter->filter($update_data["language"]);
			$update_data["sexe"]              = $stringFilter->filter($update_data["sexe"]);
			$update_data["updateduserid"]     = $me->userid;
			$update_data["updatedate"]        = time();
			$defaultData                      = $update_data;
			
			$member->setFromArray($update_data);				
			if(empty($errorMessages)) {
				if($member->save()) {
					$member  = $model->findRow($memberid , "id" , null , false);
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						$jsonErrorArray  = $member->toArray();
						$jsonErrorArray["success"] = "Les informations de la personne ont été mises à jour avec succès";
						echo ZendX_JQuery::encodeJson($jsonErrorArray);
						exit;
					}
					$this->setRedirect("Les informations de la personne ont été mises à jour avec succès", "success" );
					$this->redirect("entreprisemembers/infos/id/".$memberid);
	
				}  else {
					if($this->_request->isXmlHttpRequest()) {
						$this->_helper->viewRenderer->setNoRender(true);
						$this->_helper->layout->disableLayout(true);
						echo ZendX_JQuery::encodeJson(array("error"  => "Aucune modifiation n'a été apportée sur les données de la personne "));
						exit;
					}
					$this->setRedirect(" Aucune modifiation n'a été apportée sur les données de la personne " , "message");
					$this->redirect("entreprisemembers/infos/id/".$memberid);
				}
			} else {
				$defaultData   = $update_data;
				if($this->_request->isXmlHttpRequest()) {
					$this->_helper->viewRenderer->setNoRender(true);
					$this->_helper->layout->disableLayout(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach($errorMessages as $message) {
					$this->_helper->Message->addMessage($message) ;
				}
			}					
		}	
		$this->view->data          = $defaultData;
	}
	
	public function infosAction()
	{
		$this->_helper->layout->setLayout("base");
		
		$memberid               = intval($this->_getParam("memberid" , $this->_getParam("id" , 0)));		
		$model                  = $this->getModel("structurepersonnel");
				
		if(!$memberid) {
			if($this->_request->isXmlHttpRequest()) {
			   $this->_helper->viewRenderer->setNorender(true);
			   $this->_helper->layout->disableLayout(true);
			   echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour cette requete sont invalides "));
					exit;
				}
		  $this->setRedirect("Les paramètres fournis pour cette requete sont invalides" , "error");
		  $this->redirect("entreprisemembers/list");
		}
		
		$member      = $model->findRow($memberid , "id" , null , false);		
		if(!$contact) {
			if($this->_request->isXmlHttpRequest()) {
			   $this->_helper->viewRenderer->setNorender(true);
			   $this->_helper->layout->disableLayout(true);
			   echo ZendX_JQuery::encodeJson(array("error"  => "Les paramètres fournis pour cette requete sont invalides "));
			   exit;
			}
			$this->setRedirect("Les paramètres fournis pour cette requete sont invalides" , "error");
			$this->redirect("entreprisemembers/list");
		}		
				
		$this->view->member      = $member;
		$this->view->title       = " Les informations d'un membre du personnel ";
		$this->view->columns     = array("right" , "left");		
	}	
		
	



}






