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

class Admin_DashboardController extends Sirah_Controller_Default
{
	
	public function listAction()
	{		
		$this->view->title          = "ERCCM : Tableau de bord";
		$modelRequete               = $this->getModel("requete");
		$modelDemande               = $this->getModel("demande");
		$modelRegistre              = $this->getModel("registre");
		$modelLocalite              = $this->getModel("localite");
		$modelDemande               = $this->getModel("demande");
		$me                         = Sirah_Fabric::getUser();
		
		$stateStore                 = new Zend_Session_Namespace("Statestore");
		$stateStore->setExpirationSeconds(300);
		
		if(!isset( $stateStore->filters) || $resetSearch) {
			$stateStore->filters = array("_dashboard" => array() );
			$stateStore->dashboardData        = array();
		}
		if(!isset( $stateStore->filters["_dashboard"]["startyear"]) ) {
			$stateStore->filters["_dashboard"] = array("cohortid"=>0,"today"=>date("Y-m-d")                                                   );			
		}
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter               = new Zend_Filter();
		$stringFilter->addFilter(     new Zend_Filter_StringTrim());
		$stringFilter->addFilter(     new Zend_Filter_StripTags());
		$params                     = $this->_request->getParams();
		$filters                    = array("periode_start_year"=>2000,"periode_start_month"=>"01","periode_start_day"=>"01","periode_end_year"=>date("Y"),"periode_end_month"=>12,"periode_end_day"  =>31);		
		if(!empty(   $params )) {
			foreach( $params as $filterKey => $filterValue){
				     $filters[$filterKey]  =  $stringFilter->filter($filterValue);
			}
		}
		if( (isset( $filters["periode_end_month"]) && intval( $filters["periode_end_month"])) && (isset($filters["periode_start_month"]) && intval( $filters["periode_start_month"] ))
				&&
			(isset( $filters["periode_end_day"])   && intval( $filters["periode_end_day"]  )) && (isset($filters["periode_start_day"])   && intval( $filters["periode_start_day"] ))
		)	{
			$zendPeriodeStart               = new Zend_Date(array("year"=> $filters["periode_start_year"],"month"=>$filters["periode_start_month"],"day"=> $filters["periode_start_day"]  ));
			$zendPeriodeEnd                 = new Zend_Date(array("year"=> $filters["periode_end_year"]  ,"month"=>$filters["periode_end_month"]  ,"day"=> $filters["periode_end_day"]    ));
			$filters["periode_start"]       = $period_start = ($zendPeriodeStart)? $zendPeriodeStart->get(Zend_Date::TIMESTAMP)  : 0;
			$filters["periode_end"]         = $period_end   = ($zendPeriodeEnd  )? $zendPeriodeEnd->get(  Zend_Date::TIMESTAMP)  : 0;
		}
		$localiteid                         = 0;	
        $operatorId                         = $me->userid;	
		 
		if(!$me->isAdmin() ) {
			$localiteid                     = $me->city;				
		}		
		$this->view->statlocalites          = $stateStore->dashboardData["statlocalites"] = (isset($stateStore->dashboardData["statlocalites"]))? $stateStore->dashboardData["statlocalites"] : (($me->isAllowed("registres","list"))?$modelRegistre->dashboardStatLocalites() : array());
		$this->view->statyears              = $stateStore->dashboardData["statyears"]     = (isset($stateStore->dashboardData["statyears"]    ))? $stateStore->dashboardData["statyears"]     : (($me->isAllowed("registres","list"))?$modelRegistre->dashboardStatyears()     : array());
		$this->view->statypes               = array();
		$this->view->statsexes              = array();
		$this->view->statnationalites       = array();
		$this->view->registres              = array();
		$this->view->userstats              = $modelRegistre->getNbreByUsers(0, null,0,0 );
		$this->view->total                  = ($me->isAllowed("registres","list"))?$modelRegistre->getTotal() : 0;
		$this->view->typesdocuments         = array();
		$this->view->localites              = $stateStore->dashboardData["localites"]    = (isset($stateStore->dashboardData["localites"]   ))? $stateStore->dashboardData["localites"]    : $modelLocalite->getSelectListe(null, array("localiteid", "code") , array() , null , null , false );
		$this->view->localiteslib           = $stateStore->dashboardData["localiteslib"] = (isset($stateStore->dashboardData["localiteslib"]))? $stateStore->dashboardData["localiteslib"] : $modelLocalite->getSelectListe(null, array("localiteid", "libelle"), array() , null , null , false );
		$this->view->annees                 = $stateStore->dashboardData["annees"]       = (isset($stateStore->dashboardData["localites"]))? $stateStore->dashboardData["localites"] : $modelRegistre->getStatYears();
		
		$this->view->operatorid             = $operatorId;
		$this->view->nonProcessedRequests   = $modelDemande->countWebRequests($operatorId,0); 
		$this->view->processedRequests      = $modelDemande->countWebRequests($operatorId,1); 
		$this->view->validatedRequests      = $modelDemande->countWebRequests($operatorId,1,1); 
		//$this->view->nbreDemandesByLocalite = ($me->isAllowed("demandes","list"))?$modelDemande->getNbreByLocalite(0)  : array();
		$this->view->nbreDemandesByStatut   = ($me->isAllowed("demandes","list"))?$modelDemande->getNbreByStatut(  0)  : array();
		$this->view->nbreDemandesByAnnee    = ($me->isAllowed("demandes","list"))?$modelDemande->getNbreByYears(   0)  : array();
		//$this->view->nbreDemandesByUser     = ($me->isAllowed("demandes","list"))?$modelDemande->getNbreByUsers(   0)  : array();
		$this->view->nbreTotalDemandes      = ($me->isAllowed("demandes","list"))?$modelDemande->getTotal($operatorId) : array();						
	}
	
}

