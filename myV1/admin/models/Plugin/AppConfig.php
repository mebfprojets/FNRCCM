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
 * Cette classe correspond à un plugin de vérification
 * des droits d'accès à une ressource de l'application
 * En cas d'interdiction d'accès, il route la requete
 * sur la ressource de notification du refus d'accès
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class Plugin_AppConfig extends Zend_Controller_Plugin_Abstract
{
	
    
	
	 
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$front               = Zend_Controller_Front::getInstance();
		$controllerName      = $request->getControllerName();
		$moduleName          = $request->getModuleName();
		
		$appConfigSession    = new Zend_Session_Namespace("AppConfig");
		$projectInstance     = null;
		$appCongig           = null;
		$me                  = Sirah_Fabric::getUser();
		if( false!= $me->getParam("localiteid") ) {
			define("LOCALITEID", $me->getParam("localiteid"));
		}
		if(!defined("LOCALITEID")) {
			define("LOCALITEID", 0);
		}
		if(!defined("ENTREPRISEID")) {
			define( "ENTREPRISEID", 1);
		}
		define("USERID", $me->userid);
		if( isset($appConfigSession->project->projectid) && intval($appConfigSession->project->projectid)) {
			$projectInstance = $appConfigSession->project;
			$appConfigSession= $appConfigSession->params;
		} else {			 
			$appConfigSession->setExpirationSeconds( 86400 * 3 );
			$model           = new Model_Project();
			$projectInstance = $model->findRow(1, "current", null, false );
			if( $projectInstance ) {
				$userParams                     = $me->getParams();
				$projectInstance->setParams( $userParams, false );
			    $appConfigSession->project      = $projectInstance;
			    $appConfigSession->entreprise   = $entreprise = ($projectInstance) ? $projectInstance->findParentRow("Table_Entreprises") : null;
				$appConfigSession->entrepriseid = ($entreprise) ? $entreprise->entrepriseid : ENTREPRISEID;
				$appConfigSession->params       = $projectInstance->getParams();
				if( isset( $appConfigSession->params->default_period_start) && !intval($appConfigSession->params->default_period_start )) {
					$appConfigSession->params->default_period_start = $projectInstance->startime;
				}
				if( isset( $appConfigSession->params->default_period_end) && !intval($appConfigSession->params->default_period_end)) {
					$appConfigSession->params->default_period_end   = $projectInstance->endtime;
				}
			}
		}
        if( $appConfigSession )	{
			define("ENTREPRISEID"           , $appConfigSession->entrepriseid);
			define("NB_ELEMENTS_PAGE"       , $appConfigSession->nb_elements_page);

			
			define("DEFAULT_START_YEAR"     , date("Y", intval($appConfigSession->params->default_period_start)));
			define("DEFAULT_START_MONTH"    , date("m", intval($appConfigSession->params->default_period_start)));
			define("DEFAULT_START_DAY"      , date("d", intval($appConfigSession->params->default_period_start)));
			define("DEFAULT_END_YEAR"       , date("Y", intval($appConfigSession->params->default_period_end  )));
			define("DEFAULT_END_MONTH"      , date("m", intval($appConfigSession->params->default_period_end  )));
			define("DEFAULT_END_DAY"        , date("d", intval($appConfigSession->params->default_period_end  )));
			define("DEFAULT_PERIOD_END"     , $appConfigSession->params->default_period_end);
			define("DEFAULT_PERIOD_START"   , $appConfigSession->params->default_period_start);
			define("DEFAULT_PERIOD_END_FR"  , date("d/m/Y",$appConfigSession->params->default_period_end));
			define("DEFAULT_PERIOD_START_FR", date("d/m/Y",$appConfigSession->params->default_period_start));
			define("DEFAULT_PERIOD_END_EN"  , date("Y-m-d",$appConfigSession->params->default_period_end));
			define("DEFAULT_PERIOD_START_EN", date("Y-m-d",$appConfigSession->params->default_period_start));
			
		
		    define("DEFAULT_FIND_DOCUMENTS_AUTOMATICALLY"  , $appConfigSession->params->default_find_documents);
			define("DEFAULT_FIND_DOCUMENTS_SRC"            , $appConfigSession->params->default_find_documents_src);
			define("DEFAULT_CHECK_DOCUMENTS_AUTOMATICALLY" , $appConfigSession->params->default_check_documents);
			define("APPLICATION_REGISTRE_DEFAULT_DOMAINEID", $appConfigSession->params->default_domaineid);
			define("APPLICATION_INDEXATION_STOCKAGE_FOLDER", $appConfigSession->params->default_indexation_folder_destination);
			
			define("DEFAULT_PDF_HEADER"   , $appConfigSession->params->default_pdf_header  );
			define("DEFAULT_PDF_FOOTER"   , $appConfigSession->params->default_pdf_footer  );
			define("DEFAULT_PDF_WIDTH"    , $appConfigSession->params->default_pdf_width   );
			define("DEFAULT_PDF_MARGINS"  , $appConfigSession->params->default_pdf_margins );

           			
		}			
	}	     
  }
