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

class Admin_StatistiquesController extends Sirah_Controller_Default
{
	
	public function listAction()
	{		
		$this->view->title         = "Bilan statistique des RCCMS indexés dans la base de données du Fichier National du Registre de Commerce et du Crédit Mobilier";
		$modelRequete              = $this->getModel("requete");
		$modelRegistre             = $this->getModel("registre");
		$modelLocalite             = $this->getModel("localite");
		$modelDomaine              = $this->getModel("domaine");
		$me                        = Sirah_Fabric::getUser();
		$localiteid                = 0;		
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter              = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());		
		
		$params                    = $this->_request->getParams();
		$filters                   = array("domaineids"=>null,"domaineid"=>0,"localiteids"=>null,"localiteid"=>0,"periodstart"=>0,"periodend"=>0,"periode_start"=>0,"periode_end"=>0,"creatorid"=>0,
		                                   "periodstart_day"=>1,"periodstart_month"=>1,"periodstart_year"=>1997,"periodend_day"=>date("d"),"periodend_month"=>date("m"),"periodend_year"=>date("Y"));		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_day"]))
				&&
			(isset($filters["periodend_day"])   && intval($filters["periodend_day"]  )) && (isset($filters["periodstart_day"])   && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=>$filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=>$filters["periodend_year"]  ,"month"=>$filters["periodend_month"]  ,"day"=> $filters["periodend_day"]  ));
			$filters["periodstart"] = ($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP)  : 0;
			$filters["periodend"]   = ($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP)  : 0;
			$filters["periode_start"] = $filters["periode_end"]   = 0;
			if( $filters["periodstart"]>0) {
				$filters["periodstart_year"] = 0;
			}
			if( $filters["periodend"]>0 ) {
				$filters["periodend_year"]   = 0;
			}
		}
		if( isset($params["localiteids"]) && !empty( $params["localiteids"] ) ) {
			$localiteids             = (array)$params["localiteids"];
			if( is_string( $localiteids) ) {
				$localiteids         = array( $params["localiteids"] );
			}
			foreach( $localiteids as $dKey => $dVal ) {
				     $localiteids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["localiteids"]      = $localiteids;
		}
		if( isset($params["domaineids"]) && !empty( $params["domaineids"] ) ) {
			$domaineids             = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids         = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]      = $domaineids;
		}
		
		/*
		if( $me->isAllowed("requetes", "list" )) {
			$this->view->requetes     = $modelRequete->getList(array("validated" => 0 ));
		}*/
		if( !$me->isAdmin() ) {
			 $localiteid              = $me->city;
		}		
		$this->view->statlocalites    = $modelRegistre->dashboardStatLocalites($filters);
		$this->view->statyears        = $modelRegistre->dashboardStatyears(    $filters);
		$this->view->statypes         = $modelRegistre->dashboardStatypes(     $filters);
		//$this->view->statsexes        = array();
		//$this->view->statnationalites = $modelRegistre->dashboardStatnationalites();
		//$this->view->statages         = $modelRegistre->statAges();

		//$this->view->userstats        = $modelRegistre->getNbreByUsers();
		$this->view->total            = $modelRegistre->getTotal($filters);

        $this->view->checkedDomaines  = $domaineids;
		$this->view->checkedLocalites = $localiteids;
		$this->view->localites        = $modelLocalite->getSelectListe(null, array("localiteid", "code")   , array(), null , null , false );
		$this->view->localiteslib     = $this->view->localitesList = $modelLocalite->getSelectListe("Sélectionnez une localité", array("localiteid", "libelle"), array(), null , null , false );
		$this->view->domainesList     = $modelDomaine->getSelectListe(  "Sélectionnez un secteur d'articles",array("domaineid","libelle"), array(),null,null,false );
		$this->view->domaines         = $modelRegistre->domaines();
		$this->view->annees           = $modelRegistre->getStatYears($filters);
		$this->view->filters          = $filters;
		$this->view->params           = $params;			 						
	}
	
	public function exportpdfAction()
	{
		$this->_helper->layout->disableLayout(true);
		$view                      = &$this->view;
		 
        $layoutFormat              = $this->_getParam("layoutformat", $this->_getParam("output","pdf")); 
		$modelRegistre             = $this->getModel("registre");
		$modelLocalite             = $this->getModel("localite");
		$modelDomaine              = $this->getModel("domaine");
		$me                        = Sirah_Fabric::getUser();
		$localiteid                = 0;		
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter              = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());		
		
		$params                    = $this->_request->getParams();
		$filters                   = array("domaineids"=>null,"domaineid"=>0,"localiteids"=>null,"localiteid"=>0,"periodstart"=>0,"periodend"=>0,"periode_start"=>0,"periode_end"=>0,
		                                   "periodstart_day"=>1,"periodstart_month"=>1,"periodstart_year"=>1997,"periodend_day"=>date("d"),"periodend_month"=>date("m"),"periodend_year"=>date("Y"));		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_day"]))
				&&
			(isset($filters["periodend_day"])   && intval($filters["periodend_day"]  )) && (isset($filters["periodstart_day"])   && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=>$filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=>$filters["periodend_year"]  ,"month"=>$filters["periodend_month"]  ,"day"=> $filters["periodend_day"]  ));
			$filters["periodstart"] = $filters["periode_start"] =($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP)  : 0;
			$filters["periodend"]   = $filters["periode_end"]   =($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP)  : 0;
			if( $filters["periodstart"]>0) {
				$filters["periodstart_year"] = 0;
			}
			if( $filters["periodend"]>0 ) {
				$filters["periodend_year"]   = 0;
			}
		}
		if( isset($params["localiteids"]) && !empty( $params["localiteids"] ) ) {
			$localiteids             = (array)$params["localiteids"];
			if( is_string( $localiteids) ) {
				$localiteids         = array( $params["localiteids"] );
			}
			foreach( $localiteids as $dKey => $dVal ) {
				     $localiteids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["localiteids"]      = $localiteids;
		}
		if( isset($params["domaineids"]) && !empty( $params["domaineids"] ) ) {
			$domaineids                  = (array)$params["domaineids"];
			if( is_string( $domaineids) ) {
				$domaineids              = array( $params["domaineids"] );
			}
			foreach( $domaineids as $dKey => $dVal ) {
				     $domaineids[$dKey]  = $stringFilter->filter($dVal );
			}
			$filters["domaineids"]       = $domaineids;
		}
        $this->view->checkedDomaines  = $domaineids;
		$this->view->localites        = $modelLocalite->getSelectListe(null, array("localiteid", "code")   , array(), null , null , false );
		$this->view->localiteslib     = $this->view->localitesList = $localitesList = $modelLocalite->getSelectListe("Sélectionnez une localité", array("localiteid", "libelle"), array(), null , null , false );
		$this->view->domainesList     = $modelDomaine->getSelectListe(  "Sélectionnez un secteur d'articles",array("domaineid","libelle"), array(),null,null,false );
		$this->view->domaines         = $domaines = $modelRegistre->domaines();
		$this->view->annees           = $annees   = $modelRegistre->getStatYears($filters);
		$this->view->filters          = $filters;
		$this->view->params           = $params;
        $this->view->models           = $models   = array("registre"=>$modelRegistre,"localite"=>$modelLocalite,"domaine"=>$modelDomaine);		
		if( strtoupper($layoutFormat)=="PDF") {
			$viewTitle                   = "Bilan statistique des RCCMS indexés dans la base de données du Fichier National du Registre de Commerce et du Crédit Mobilier";
			if( $filters["periodstart"] > 0 && $filters["periodstart"] < $filters["periodend"] && $zendPeriodeStart && $zendPeriodeEnd ) {
				$viewTitle               = sprintf("Bilan statistique des RCCMS indexés au FNRCCM: dans la période du %s au %s ", $zendPeriodeStart->toString("dd MMM YYYY"), $zendPeriodeEnd->toString("dd MMM YYYY"));
			}
			$this->_helper->layout->disableLayout(true);
		    $this->_helper->viewRenderer->setNoRender(true);
			 
			$output           ="<table align=\"center\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\">";
                 $output     .="    <tr bgcolor=\"#CCCCCC\" style=\"background-color:#CCCCCC\">
										<td align=\"center\" style=\"height:25px;border-bottom:1px solid black;\" width=\"100%\">                   
										   <span style=\"height:25px;font-family:arial;font-size:14pt;font-weight:bold\">".$viewTitle ."</span>
										</td> 
									</tr> ";
            $output          .=" </table> <br/><br/>";
			$output          .= $view->partial("statistiques/statglobal.phtml", array("annees"=>$annees,"localites"=>$localitesList,"filters"=>$filters,"models"=>$models,"params"=>$params));
		    //print_r($output); die();
			$PDF              = Sirah_Fabric::getPdf(array("orientation"=>"L","format"=>"A4"));
			$PDF->SetCreator("SIRAAH");
			$PDF->SetTitle($viewTitle );
			$PDF->SetMargins(  5 ,40, 5 );
			
			$margins           = $PDF->getMargins();
			$contenuWidth      = $PDF->getPageWidth()-$margins["left"]-$margins["right"];					
			$PDF->AddPage();
			$PDF->SetFont("helvetica", "" , 9);
			$PDF->writeHTML($output, true , false , true , false , 'C' );
			$PDF->Output("StatistiquesRegistresIndexation.pdf", "D");
			exit;
		}
	}
	
}

