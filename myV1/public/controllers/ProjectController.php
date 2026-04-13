<?php

class ProjectController extends Sirah_Controller_Default
{
	
	public function init()
	{
		parent::init();
		//$this->view->headMeta()->
	}
	
	public function indexAction()
	{
	   
		$this->view->title   = " Bienvenue sur l'interface d'administration";
		$this->_helper->viewRenderer->setNoRender( true );
		
		echo "Juste un tests";
	}
	
	public function cguAction()
	{
		$this->view->title   = "Conditions Générales d'utilisation et conditions générales de vente";
		$this->_helper->viewRenderer->setNoRender( true );
		
		echo "Contenu bientôt disponible";
	}
	
	public function infosAction()
	{
		$this->view->title   = "Présentation du projet";
		$this->_helper->viewRenderer->setNoRender( true );
		
		echo "Juste un tests";
	}
	
	public function presentationAction()
	{
		$this->view->title             = "A propos du FNRCCM: Fichier National du Registre de Commerce et du Crédit Mobilier";
		$application                   = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($application->initialized) || !isset($application->project)) {
			$modelProjet               = new Model_Project();
			$appConfigSession          = new Zend_Session_Namespace("AppConfig");
			$application->initialized  = 1;
			$application->project      = (isset($appConfigSession->project))?$appConfigSession->project : $modelProjet->findRow(1, "current", null, false );
			$application->params       = $appConfigSession->params;
			$application->documentypes = Model_Project::documentypes();
			$application->productypes  = Model_Project::productypes();
		}
		$projectData                   = array("introduction"=>"","presentation"=>"");
		$project                       = (isset($appConfigSession->project))?$appConfigSession->project : null;
		
		if(!$project ) {
			$modelProjet               = new Model_Project();
			$project                   = $modelProjet->findRow(1,"current", null, false );
		}
		if( $project ) {
			$projectData               = $project->toArray();
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message , "error") ;
			}
		}
		$this->view->introtext    = $this->view->introduction = $projectData["introduction"];
		$this->view->presentation = $this->view->contenu      = $projectData["presentation"];	
		$this->view->presentation_structure                   = $projectData["presentation_structure"];	
        $this->view->objectifs    = $projectData["objectifs"]; 	
		$this->view->title        = "A propos de la plateforme du Fichier National";
		 
	}
		 
	
	public function objectifsAction()
	{	
		$this->view->title             = "Objectifs visés";
		$model                         = new Model_Project();
		
		$application                   = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($application->initialized) || !isset($application->project)) {
			$modelProjet               = new Model_Project();
			$appConfigSession          = new Zend_Session_Namespace("AppConfig");
			$application->initialized  = 1;
			$application->project      = (isset($appConfigSession->project))?$appConfigSession->project : $modelProjet->findRow(1, "current", null, false );
			$application->params       = $appConfigSession->params;
			$application->documentypes = Model_Project::documentypes();
			$application->productypes  = Model_Project::productypes();
		}
		$projectData                   = array("introduction"=>"","presentation"=>"");
		$project                       = (isset($appConfigSession->project))?$appConfigSession->project : null;
		
		if(!$project ) {
			$modelProjet               = new Model_Project();
			$project                   = $modelProjet->findRow(1,"current", null, false );
		}
		if( $project ) {
			$projectData               = $project->toArray();
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message , "error") ;
			}
		}		
 	
		$this->view->objectif_global       = $projectData["objectif_global"];	
        $this->view->objectifs_specifiques = $projectData["objectifs"]; 
        $this->view->objectif_strategique  = $projectData["objectif_strategique"];
		
		 
	}
	
	public function equipeAction()
	{
		$this->view->title      = "Présentation de l'équipe du projet";
		$model                  = new Model_Project();
	
		$projectid              = "1";
		$project                = $model->findRow( $projectid , "projectid", null, false );
		$errorMessages          = array();
		$projectData            = $model->getEmptyData();
	
		if(!$project ) {
			$dbAdapter         = $model->getTable()->getAdapter();
			$prefixName        = $model->getTable()->info("namePrefix");
			$newRow            = array("projectid"=>1,"code"=>"ERCCM","libelle"=>"LA PLATEFORME ERCCM EST UNE PLATEFORME...","objectif_global"=>"Objectifs.","creatorid"=>1,"creationdate"=>time(),"updatedate"=> 0,"updateduserid"=> 0);
			if( $dbAdapter->insert($prefixName."envoitout_projet_application", $newRow ) ) {
				$project       = $model->findRow( $projectid , "projectid", null, false );
				$projectData   = $project->toArray();
			} else {
				$errorMessages[]= "Les informations du projet sont indisponibles";
			}
		} else {
			    $projectData    = $model->findRow( $projectid , "projectid", null, false );
		}
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error" =>  "Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				$this->_helper->Message->addMessage($message , "error") ;
			}
		}
		$this->_helper->viewRenderer->setNoRender( true );
		echo "<div class=\"row-fluid row-content\">";
		echo "    <p> ".html_entity_decode( $projectData["presentation_equipe"], ENT_QUOTES, "UTF-8" )." </p>";
		echo "</div>";
	}
	
	public function structureAction()
	{
		$this->view->title             = "A propos du FNRCCM: Fichier National du Registre de Commerce et du Crédit Mobilier";
		$application                   = new Zend_Session_Namespace("erccmapp");
		
		if(!isset($application->initialized) || !isset($application->project)) {
			$modelProjet               = new Model_Project();
			$appConfigSession          = new Zend_Session_Namespace("AppConfig");
			$application->initialized  = 1;
			$application->project      = (isset($appConfigSession->project))?$appConfigSession->project : $modelProjet->findRow(1, "current", null, false );
			$application->params       = $appConfigSession->params;
			$application->documentypes = Model_Project::documentypes();
			$application->productypes  = Model_Project::productypes();
		}
		$projectData                   = array("introduction"=>"","presentation"=>"");
		$project                       = (isset($appConfigSession->project))?$appConfigSession->project : null;
		
		if(!$project ) {
			$modelProjet               = new Model_Project();
			$project                   = $modelProjet->findRow(1,"current", null, false );
		}
		if( $project ) {
			$projectData               = $project->toArray();
		}
 
		if( count( $errorMessages ) ) {
			if( $this->_request->isXmlHttpRequest()) {
				$this->_helper->viewRenderer->setNoRender(true);
				$this->_helper->layout->disableLayout(true);
				echo ZendX_JQuery::encodeJson(array("error"=>"Des erreurs se sont produites : " . implode(" , " , $errorMessages)));
				exit;
			}
			foreach( $errorMessages as $message ) {
				     $this->_helper->Message->addMessage($message , "error") ;
			}
		}
		$this->view->introtext    = $this->view->introduction = $projectData["introduction"];
		$this->view->presentation = $this->view->contenu      = $projectData["presentation_structure"];	
		$this->view->presentation_structure                   = $projectData["presentation_structure"];	
        $this->view->objectifs    = $projectData["objectifs"]; 		
	}
	
	public function contactsAction()
	{
		$me                               = Sirah_Fabric::getUser();
		$view                             = &$this->view;
		$view->title                      = "Ecrire un message à notre équipe";
		
		$defaultData                      = array("send_copy"=>0,"name"=>null,"email"=>null,"phone"=>null,"address"=>null,"subject"=>null, "message"=>null,"userid"=>$me->userid);
		 		
		if( $this->_request->isPost() ) {
			//On crée les filtres qui seront utilisés sur les données du formulaire
			$stringFilter       = new Zend_Filter();
			$stringFilter->addFilter(new Zend_Filter_StringTrim());
			$stringFilter->addFilter(new Zend_Filter_StripTags());
			
			//On crée les validateurs nécessaires
			$strNotEmptyValidator         = new Zend_Validate_NotEmpty(array("integer","zero","string","float","null"));
			
			$nameValidator                = new Validator_Name();						
			$emailValidator               = new Sirah_Validateur_Email();
			$postData                     = $this->_request->getPost();
 
			$sendCopy                     = (isset( $postData["send_copy"] ))? intval($postData["send_copy"])              : 0;
			$contactName                  = (isset( $postData["name"]      ))? $stringFilter->filter($postData["name"])    : "";
			$contactEmail                 = (isset( $postData["email"]     ))? $stringFilter->filter($postData["email"])   : "";
			$contactPhone                 = (isset( $postData["phone"]     ))? $stringFilter->filter($postData["phone"])   : "";
			$contactAddress               = (isset( $postData["address"]   ))? $stringFilter->filter($postData["address"]) : "";
			
			$messageSubject               = (isset( $postData["subject"]   ))? $stringFilter->filter($postData["subject"]) : "";
			$messageContent               = (isset( $postData["message"]   ))? $stringFilter->filter($postData["message"]) : "";
			
			if(!$emailValidator->isValid( $contactEmail )) {
				$errorMessages[]          = "Veuillez fournir une adresse email valide";
			}
			if(!$strNotEmptyValidator->isValid( $contactName )){
				$errorMessages[]          = "Veuillez saisir votre nom et prénom";
			}
			if(!$strNotEmptyValidator->isValid( $messageSubject )){
				$errorMessages[]          = "Veuillez renseigner l'objet de votre message";
			}
			if(!$strNotEmptyValidator->isValid( $messageContent )){
				$errorMessages[]          = "Veuillez fournir du contenu à votre message";
			}
			$messageContent               = "Message en ligne d'un usager";
			if( empty( $errorMessages ) ) {
				$config                   = Sirah_Fabric::getConfig();		
				$mailer                   = Sirah_Fabric::getMailer();
				$defaultToEmail           = $config["system"]["application"]["enterprise"]["defaultEmail"];
				$defaultToName            = $config["system"]["application"]["enterprise"]["name"];
				
				$contactMessage           = "<strong><i> Vous avez reçu un mail depuis le site web du FNRCCM : Fichier National du RCCM </i></strong>"
				                           ."<br/><br/><br/>"
										   .$messageContent;
				$contactMessageSubject    =	" FNRCCM - ".$messageSubject;
                $msgPartialData           = array("subject"=>$contactMessageSubject,"message"=>$contactMessage,"logoMsg"=> APPLICATION_STRUCTURE_LOGO,"replyToEmail"=>$defaultToEmail,
						                          "replyToName"=>$defaultToName,"replyToTel"=>"","replyToSiteWeb"=>"http://www.fichiernationalenligne.com/about","toName"=>$defaultToName,"toEmail"=>$defaultToEmail );
				$msgBody                  = $this->view->partial("mailtpl/default.phtml" , $msgPartialData );
				$mailer->setFrom(   $defaultToEmail, $defaultToName);
				$mailer->setSubject($contactMessageSubject);
				$mailer->addTo(     $defaultToEmail, $defaultToName);				
                if( $sendCopy ) {
					$mailer->addCc($contactEmail , $contactName);
				}
                $mailer->setBodyHtml( $msgBody );				
                try{
					$mailer->send();
				} catch( Exception $e) {
					$errorMessages[]      = $e->getMessage();
				}                					
			}			
			if( empty( $errorMessages ) ) {
				if( $this->_request->isXmlHttpRequest( ) ) {
						$this->_helper->viewRenderer->setNoRender(true);
						echo ZendX_JQuery::encodeJson( array("success"=>"Votre message a été transmis avec succès à notre équipe. Une réponse vous sera très bientôt transmise. Merci"));
						exit;
				}
				$this->setRedirect("Votre message a été transmis avec succès à notre équipe. Une réponse vous sera très bientôt transmise. Merci", "success");
				$this->redirect("index/index");
			} else {
				$defaultData   = $postData;
				if( $this->_request->isXmlHttpRequest() ) {
					$this->_helper->viewRenderer->setNoRender(true);
					echo ZendX_JQuery::encodeJson(array("error" => "Des erreurs sont produites ".implode(" , " , $errorMessages )));
					exit;
				}
				foreach( $errorMessages as $message ) {
					     $this->_helper->Message->addMessage($message) ;
				}
			}
		}
		$view->user             = $me;
		$view->data             = $defaultData;
		$view->columns          = $view->modules = array();
		$view->showLayoutTitle  = true;
	}
	
	public function faqAction()
	{
	
		$this->view->title   = " Foire aux Questions ";
		$this->_helper->viewRenderer->setNoRender( true );
	    
		echo "Foire aux questions";
		 	
	}
	
	public function tarificationAction()
	{
		$this->_helper->layout->setLayout("default")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
	    $modelProductCategory = new Model_Productcategorie();
		
		$this->view->title    = " Plan de tarification ";
		$this->view->products = $modelProductCategory->getList();
	
		 	
	}
	
	public function listAction()
	{
	
		$this->view->title   = " Liste des partenaires";
		$this->_helper->viewRenderer->setNoRender( true );
	
		echo "Juste un test au niveau des partenaires";			
	}
	
	

}