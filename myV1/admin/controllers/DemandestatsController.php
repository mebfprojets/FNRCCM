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

class Admin_DemandestatsController extends Sirah_Controller_Default
{
	
	public function listAction()
	{		
		$this->view->title         = "Bilan statistique des demandes de vérification et de reservation de noms commerciaux";
 
		$modelDemande              = $this->getModel("demande");
		$modelLocalite             = $this->getModel("localite");
		$modelType                 = $this->getModel("demandetype");	
		$me                        = Sirah_Fabric::getUser();
		$localiteid                = $userid = 0;		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter              = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());		
		
		$params                    = $this->_request->getParams();
		$filters                   = array("periodstart_year"=>0,"periodend_year"=>0,"localiteid"=>0,"personne_physique"=>4,"catid"=>0,"typeid"=>0,"web"=>4,"periodstart"=>0,"periodend"=>0,"localiteids"=>null,
		                                   "periodstart_day" =>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_day"]))
				&&
			(isset($filters["periodend_day"])  && intval($filters["periodend_day"] ))   && (isset($filters["periodstart_day"])  && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=>$filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=>$filters["periodend_year"]  ,"month"=>$filters["periodend_month"]  ,"day"=> $filters["periodend_day"]  ));
			$filters["periodstart"] = $filters["periode_start"] =($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periodend"]   = $filters["periode_end"]   =($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
			if( $filters["periodstart"]>0) {
				$filters["periodstart_year"] = 0;
			}
			if( $filters["periodend"]>0 ) {
				$filters["periodend_year"] = 0;
			}
		}
		
		if( isset( $params["localiteids"] ) && !empty( $params["localiteids"] ) ) {
			$localiteids             = (array)$params["localiteids"];
			if( is_string( $localiteids) ) {
				$localiteids         = array( $params["localiteids"] );
			}
			foreach( $localiteids as $dKey => $dVal ) {
				     $localiteids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["localiteids"]      = $localiteids;
		}
		if( isset($filters["personne_physique"]) && intval($filters["personne_physique"]==1)) {
			$filters["personne_physique"]= 1;
			$params["personne_physique"] = 1;
		} elseif( isset($filters["personne_physique"]) && intval($filters["personne_physique"]==2)) {
			$filters["personne_morale"]  = 1;
			$params["personne_physique"] = 2;
			unset($filters["personne_physique"]);
		} else {
			$params["personne_physique"] = 4;
			unset($filters["personne_physique"]);
			if( isset($filters["personne_morale"])) {
				unset($filters["personne_morale"]);
			}
		}
 
		$this->view->checkedLocalites    = $localiteids;		
		$this->view->statlocalites       = $modelDemande->getNbreByLocalite(0,$filters);
		$this->view->statstatuts         = $modelDemande->getNbreByStatut(0,$filters);
		$this->view->statyears           = $modelDemande->getNbreByYears(0,$filters);
		$this->view->userstats           = $modelDemande->getNbreByUsers(0,null,$filters["periodstart"],$filters["periodend"]);
		$this->view->total               = $modelDemande->getTotal(0,$filters);

        $this->view->filters             = $filters;
		$this->view->params              = $params;
		$this->view->types               = $modelType->getSelectListe(    "Sélectionnez un type de demandes", array("typeid"    , "libelle"), array(),null,null,false );
		$this->view->localites           = $modelLocalite->getSelectListe(null, array("localiteid", "code")   , array() , null , null , false );
		$this->view->localiteslib        = $this->view->localitesList = $modelLocalite->getSelectListe("Sélectionnez une localité", array("localiteid", "libelle"), array() , null , null , false );
		$this->view->annees              = $modelDemande->getStatYears( );
			 
						
	}
	
	public function exportpdfAction()
	{
		$this->_helper->layout->disableLayout(true);
		$view                      = &$this->view;
		 
        $layoutFormat              = $this->_getParam("layoutformat", $this->_getParam("output","pdf")); 
		$modelDemande              = $this->getModel("demande");
		$modelLocalite             = $this->getModel("localite");
		$me                        = Sirah_Fabric::getUser();
		$localiteid                = $userid = 0;		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter              = new Zend_Filter();
		$stringFilter->addFilter(    new Zend_Filter_StringTrim());
		$stringFilter->addFilter(    new Zend_Filter_StripTags());		
		
		$params                    = $this->_request->getParams();
		$filters                   = array("periodstart_year"=>0,"periodend_year"=>0,"localiteid"=>0,"catid"=>0,"periodstart"=>0,"periodend"=>0,"localiteids"=>null,
		                                   "periodstart_day" =>0,"periodstart_month"=>0,"periodstart_year"=>0,"periodend_day"=>0,"periodend_month"=>0,"periodend_year"=>0);		
		if(!empty(   $params)) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset($filters["periodend_month"]) && intval($filters["periodend_month"])) && (isset($filters["periodstart_month"]) && intval($filters["periodstart_day"]))
				&&
			(isset($filters["periodend_day"])  && intval($filters["periodend_day"] ))   && (isset($filters["periodstart_day"])  && intval($filters["periodstart_day"]  ))
		)	{
			$zendPeriodeStart       = new Zend_Date(array("year"=>$filters["periodstart_year"],"month"=>$filters["periodstart_month"],"day"=> $filters["periodstart_day"]));
			$zendPeriodeEnd         = new Zend_Date(array("year"=>$filters["periodend_year"]  ,"month"=>$filters["periodend_month"]  ,"day"=> $filters["periodend_day"]  ));
			$filters["periodstart"] = $filters["periode_start"] =($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP): 0;
			$filters["periodend"]   = $filters["periode_end"]   =($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP): 0;
			if( $filters["periodstart"]>0) {
				$filters["periodstart_year"] = 0;
			}
			if( $filters["periodend"]>0 ) {
				$filters["periodend_year"] = 0;
			}
		}
		
		if( isset( $params["localiteids"] ) && !empty( $params["localiteids"] ) ) {
			$localiteids             = (array)$params["localiteids"];
			if( is_string( $localiteids) ) {
				$localiteids         = array( $params["localiteids"] );
			}
			foreach( $localiteids as $dKey => $dVal ) {
				     $localiteids[$dKey] = $stringFilter->filter($dVal );
			}
			$filters["localiteids"]      = $localiteids;
		}
 
		$this->view->checkedLocalites    = $localiteids;		
        $this->view->filters             = $filters;
		$this->view->params              = $params;
		$this->view->localites           = $modelLocalite->getSelectListe(null, array("localiteid", "code")   , array() , null , null , false );
		$this->view->localiteslib        = $localitesList = $this->view->localitesList = $modelLocalite->getSelectListe("Sélectionnez une localité", array("localiteid", "libelle"), array() , null , null , false );
		$this->view->annees              = $annees        = $modelDemande->getStatYears( );
		$view->models                    = $models        = array("demande"=>$modelDemande,"localite"=>$modelLocalite);
 
		
		if( strtoupper($layoutFormat)=="PDF") {
			$viewTitle                   = "Bilan statistique des demandes de vérification et de reservation de noms commerciaux ";
			if( $filters["periodstart"] > 0 && $filters["periodstart"] < $filters["periodend"] && $zendPeriodeStart && $zendPeriodeEnd ) {
				$viewTitle              .= sprintf("dans la période du %s au %s", $zendPeriodeStart->toString("dd MMM YYYY"), $zendPeriodeEnd->toString("dd MMM YYYY"));
			}
			$this->_helper->layout->disableLayout(true);
		    $this->_helper->viewRenderer->setNoRender(true);
			 
			$output           ="<table align=\"center\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\">";
                 $output     .="    <tr bgcolor=\"#CCCCCC\" style=\"background-color:#CCCCCC\">
										<td align=\"center\" style=\"height:25px;border-bottom:1px solid black;\" width=\"100%\">                   
										   <span style=\"height:25px;font-family:arial;font-size:14pt;font-weight:bold;\">".$viewTitle ."</span>
										</td> 
									</tr> ";
            $output          .=" </table> <br/><br/>";
			$output          .= $view->partial("demandestats/statglobal.phtml", array("annees"=>$annees,"localites"=>$localitesList,"filters"=>$filters,"models"=>$models,"params"=>$params));
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
			$PDF->Output("StatistiquesDemandeVerifications.pdf", "D");
			exit;
		}
	}
	
}

