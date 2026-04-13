<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 * Auteur : Banao Hamed
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Le controlleur d'actions sur le profil
 * 
 * d'un utilisateur de l'application.
 *
 *
 * @copyright Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license http://sirah.net/license
 * @version $Id:
 * @link
 *
 * @since
 *
 */

class DashboardController extends Sirah_Controller_Default
{
	
	public function listAction()
	{		
		   $this->_helper->layout->setLayout("home")->setLayoutPath(APPLICATION_TEMPLATES .'/public');
		$view                  = &$this->view;
				
		$modelProjet           = new Model_Project();
		$modelDocumentCategory = new Model_Documentcategorie();
		$project               = $modelProjet->findRow("1" , "projectid", null, false );
		$layout                = $this->_helper->layout();
		$viewBasePath          = APPLICATION_TEMPLATES."/public";
		$layoutContent         = "";
		$linkPresentation      = "#";
		if(!$project ) {
			$layoutContent     =" <div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/company.png\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1 class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div  class=\"section-body\"> 
											<p> Le ERCCM est une plateforme de services en ligne du <a title=\"A propos de cette structure\" href=\"#\"> Fichier National du Registre du Commerce et du Crédit Mobilier (FNRCCM) </a> créée dans le but d'archiver, de centraliser et de rendre accessibles au public les du Registres du Commerce et du Crédit Mobilier (RCCM) immatriculés au Burkina Faso. </p>
											<p> Cette plateforme permet la recherche et la consultation en ligne des <a title=\"Consulter les types de documents officiels\" href=\"#\"> documents officiels </a>  des entreprises immatriculées à la <a title=\"En ssavoir plus sur la maison de l'entreprise\" href=\"#\"> Maison de l'Entreprise du Burkina Faso </a>. </p>										
										</div>                           
									</div>                      
								</div>";	             
		} else {
			$layoutContent    = "<div class=\"row section-content\">
									<div class=\"col-md-4 col-sm-4 col-xs-12\"> <img class=\"pull-left\"  src=\"/myTpl/public/images/company.png\" /></div>
									<div class=\"col-md-8 col-sm-8 col-xs-12\">
										<h1 class=\"section-hero\"> ERCCM : CONSULTEZ DES RCCM EN LIGNE </h1>                              
										<div  class=\"section-body\">".$project->introduction."</div>                           
									</div>                      
								</div>";
		    $view->headMeta()->appendName("description", htmlentities(strip_tags($project->introduction)) );
		}		
		$view->headMeta()->appendName("keywords", "burkina,faso,afrique,travel,voyage,courrier,colis,," );
		$view->modules              = array("content-top-mod","search-mod","rightmenu-mod","content-bottom-mod","slideshow-mod");
		$view->documentTypes        = $modelDocumentCategory->getList(array("public"=>1));	

		$view->title                = "BIENVENUE SUR LA PLATEFORME ERCCM";
		$view->columns              = array("content");
        $view->modules              = array("appfeatures","slideshow");
			 
						
	}
	
}

