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

class Admin_SaledashboardController extends Sirah_Controller_Default
{
	
	public function listAction()
	{		
		$this->view->title                   = "Tableau de bord des achats et commandes";
		$model                               = $modelCommande = $this->getModel("commande");
		$modelPaiement                       = $this->getModel("commandepaiement");
		$modelFacture                        = $this->getModel("commandefacture");
		$modelMember                         = $this->getModel("member");
		$modelDemande                        = $this->getModel("demande");
		$me                                  = Sirah_Fabric::getUser();
		
		//On crée les filtres qui seront utilisés sur les paramètres de recherche
		$stringFilter                        = new Zend_Filter();
		$stringFilter->addFilter(              new Zend_Filter_StringTrim());
		$stringFilter->addFilter(              new Zend_Filter_StripTags());
		$params                              = $this->_request->getParams();
		$filters                             = array("periode_start_year"=>2018,"periode_start_month"=>"09","periode_start_day"=>"01","periode_end_year"  =>2022,"periode_end_month"  =>"12","periode_end_day"  =>"31");		
		 					
	    $this->view->totalMembers            = $modelMember->count();	
	    $this->view->commandeTotal           = $model->getBilanTotal();		
		$this->view->commandeBilanStatut     = $model->getBilanStatut();
		$this->view->commandeBilanProductype = $model->getBilanByProductype();
		$this->view->commandeBilanAnnuel     = $model->getBilanAnnuel();
		
		$this->view->paiementTotal           = $modelPaiement->getBilanTotal();
		$this->view->paiementBilanStatut     = $modelPaiement->getBilanStatut();
		$this->view->paiementBilanProductype = $modelPaiement->getBilanByProductype();
		$this->view->paiementBilanAnnuel     = $modelPaiement->getBilanAnnuel();
	
	}
	
}

