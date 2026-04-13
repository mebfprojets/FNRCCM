<?php

class IndexController extends Sirah_Controller_Default
{
	
	public function listdemandes()
	{
	}
	
	public function testAction()
	{
		$this->_helper->viewRenderer->setNoRender(true);
		$config                         = Sirah_Fabric::getConfig();
		$defaultFromEmail               = $config["resources"]["mail"]["defaultFrom"]["email"];
		$defaultFromName                = $config["resources"]["mail"]["defaultFrom"]["name"];
		
		
		$mailer                         = Sirah_Fabric::getMailer();
		Zend_Mail::clearDefaultFrom();
        Zend_Mail::clearDefaultReplyTo();
		$mailer->setFrom("noreply@siraah.net","FNRCCM");
		$mailer->setSubject("FNRCCM : Message de tests");
		$mailer->addTo("banaohamed@gmail.com","BANAO");
		$mailer->setBodyHtml("<b> Essai d'envoi de mail.</b> ");		
		$mailSent                       = true;							
		try{
			$mailer->send();
		} catch(Exception $e) {
			$mailSent    = false;
			echo $e->getMessage();
		}
		if( $mailSent ) {
			echo "<br/> Message Envoyé : ".$defaultFromEmail;
		} else {
			echo "<br/> Message Non Envoyé : ".$defaultFromEmail;
		}
	}
	
	public function indexAction()
	{
	    $this->_helper->layout->setLayout("home")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                          = &$this->view;

		$application                   = new Zend_Session_Namespace("erccmapp");
		$modelProjet                   = new Model_Project();
		if(!isset($application->initialized) || !isset($application->project)) {
			
			$appConfigSession          = new Zend_Session_Namespace("AppConfig");
			$application->initialized  = 1;
			$application->project      = (isset($appConfigSession->project))?$appConfigSession->project : $modelProjet->findRow(1, "current", null, false );
			$application->projectStats = $modelProjet->stats(1);
			$application->params       = $appConfigSession->params;
			$application->documentypes = Model_Project::documentypes();
			$application->productypes  = Model_Project::productypes();
		}
		$modelArticle                  = new Model_Article();
		$project                       = (isset($appConfigSession->project))?$appConfigSession->project : null;
		$layout                        = $this->_helper->layout();
		$viewBasePath                  = APPLICATION_TEMPLATES."/public";
		$layoutContent      = "";
		$linkPresentation   = "#";
		if(!$project ) {
			$layoutContent  = " <div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/customer.jpg\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1  class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div class=\"section-body\"> 
											<p> Le ERCCM est une plateforme de services en ligne du <a title=\"A propos de cette structure\" href=\"#\"> Fichier National du Registre du Commerce et du Crédit Mobilier (FNRCCM) </a> créée dans le but d'archiver, de centraliser et de rendre accessibles au public les du Registres du Commerce et du Crédit Mobilier (RCCM) immatriculés au Burkina Faso. </p>
											<p> Cette plateforme permet la recherche et la consultation en ligne des <a title=\"Consulter les types de documents officiels\" href=\"#\"> documents officiels </a>  des entreprises immatriculées à la <a title=\"En ssavoir plus sur la maison de l'entreprise\" href=\"#\"> Maison de l'Entreprise du Burkina Faso </a>. </p>										
										</div>                           
									</div>                      
								</div>";	             
		} else {
			$layoutContent  = " <div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/customer.jpg\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1 class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div  class=\"section-body\">".$project->introduction."</div>                           
									</div>                      
								</div>";
		    $view->headMeta()->appendName("description", htmlentities(strip_tags($project->introduction)) );
		}		
		$view->headMeta()->appendName("keywords", "burkina,ouagadougou,faso,afrique,ohada,rccm,entreprise,entreprises,ohada,base,de,donnees,MEBF,CCIBF,fichier-national,maison,de,l-entreprise,mebf,registre,erccm,fnrccm,documents,fn-rccm,e-rccm,FN-RCCM,RCCM,commerce,fichier,national,credit,mobilier,reservation,disponibilité,nom,commercial,denomination" );
		$view->modules       = array("content-top-mod","search-mod","rightmenu-mod","content-bottom-mod","slideshow-mod");
		$view->documentTypes = (isset($application->documentypes))? $application->documentypes : array();	
		$view->productTypes  = (isset($application->productypes ))? $application->productypes  : array();
		$view->stats         = (isset($application->projectStats))? $application->projectStats : $modelProjet->stats(1);
		$view->actualites    = $view->items = $modelArticle->getList(array("catid"=>2),1,10);

 
		$view->title         = "BIENVENUE SUR LA PLATEFORME DU FICHIER NATIONAL DU REGISTRE DE COMMERCE ET DU CREDIT MOBILIER";
		$view->columns       = array("content");
        $view->modules       = array("appfeatures","slideshow");		
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
		if( $this->_request->isXmlHttpRequest()){
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
