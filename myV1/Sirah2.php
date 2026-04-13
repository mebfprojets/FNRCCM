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
 * Cette classe correspond au point d'entrée
 * de l'application à partir duquel démarre
 * notre application avec ses différentes ressources
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */
class Sirah_Bootstrap_Project extends Sirah_Bootstrap
{

	
	/**
	 * On initialise les routeurs de l'application
	 *
	 */
    protected function _initRouter()
	{
		$this->bootstrap("db");
		$front           = $this->bootstrap('FrontController')->getResource('FrontController');
		$routersPath     = APPLICATION_PATH . DS . "default" . DS . "routers" . DS; 
		//configuration de l'Autoload
		$modelLoader     = new Zend_Loader_Autoloader_Resource(array('namespace' => '','basePath' => $routersPath ));
		$modelLoader->addResourceType('routers', '', 'Route');
		$router            = $front->getRouter();	
		$profileRoute      = new Route_User("user", array("module" => "default", "controller" => "profile", "action" => "infos"));
		$loginRoute        = new Zend_Controller_Router_Route_Static("login"    , array("controller" => "account"  , "action" => "login", "module" => "public"));
		$logoutRoute       = new Zend_Controller_Router_Route_Static("logout"   , array("controller" => "account"  , "action" => "logout","module" => "admin"));
		$homeRoute         = new Zend_Controller_Router_Route_Static("home"     , array("controller" => "index"    , "action" => "index", "module" => "admin"));
		$publicRoute       = new Zend_Controller_Router_Route_Static("home"     , array("controller" => "index"    , "action" => "index", "module" => "public"));
		$meRoute           = new Zend_Controller_Router_Route_Static("me"       , array("controller" => "myprofile", "action" => "infos", "module" => "admin"));
		$dashboardRoute    = new Zend_Controller_Router_Route_Static("dashboard", array("controller" => "dashboard", "action" => "list" , "module" => "admin"));
		$adminRoute        = new Zend_Controller_Router_Route_Static("backend"  , array("controller" => "account"  , "action" => "login", "module" => "admin"));
		$localitesRoute    = new Zend_Controller_Router_Route_Regex("localites/(\d+)-([-\w]+)\.html",array("module" => "public","controller" => "localites","action" => "infos"),
				                                                                                     array(1 => "localiteid", 2 => "libelle"), "localites/%s-%s.html");
		$registreRoute     = new Zend_Controller_Router_Route_Regex("registre/(\d+)-([-\w]+)-([-\w]+)\.html",array("module" => "public", "controller" => "registres","action" => "infos"),array(1 => "registreid", 2=> "type", 3=> "registrelib"),"registre/%s-%s-%s.html");
		$registreListRoute = new Zend_Controller_Router_Route_Regex("registres/(\d+)-([-\w]+)\.html",array("module" => "public", "controller" => "registres", "action" => "list"),array(1 =>"typeid", 2=> "typelib"),"registres/%s-%s.html");
		$frontendRoute     = new Zend_Controller_Router_Route_Regex("^([-\w]+)-([-\w]+)\.html",array("module" => "public"),array(1 =>"controller",2 => "action"), "%s-%s.html");
		$documentsRoute    = new Zend_Controller_Router_Route_Regex("documents/document-(\d+)-([-\w]+)\.html",array("module"=>"public","controller"=>"documentheque","action" => "infos"),
				                                                                                              array(1 => "documentid", 2 => "libelle"),"documents/document-%s-%s.html");

		$router->addRoute("frontend"      , $frontendRoute    );
		$router->addRoute("administration", $adminRoute       );
		$router->addRoute("login"         , $loginRoute       );
		$router->addRoute("logout"        , $logoutRoute      );
		$router->addRoute("me"            , $meRoute          );
		$router->addRoute("public"        , $publicRoute      );
		$router->addRoute("home"          , $homeRoute        );
		$router->addRoute("dashboard"     , $dashboardRoute   );
		$router->addRoute("profile"       , $profileRoute     );
		$router->addRoute("registre"      , $registreRoute    );
		$router->addRoute("registrelist"  , $registreListRoute);
		$router->addRoute("localites"     , $localitesRoute   );
		$router->addRoute("documentheque" , $documentsRoute   );
	}
	
	protected function _initErrorHandlerPlugin()
	{
		$front    = $this->bootstrap('frontcontroller')->frontcontroller;
		$error    = new Zend_Controller_Plugin_ErrorHandler();
		$error->setErrorHandlerModule('public')->setErrorHandlerController('error')->setErrorHandlerAction('index');
	
		$front->registerPlugin($error);
		return $error;
	}
	
 /**
    * Permet d'initialiser la ressource de gestion des sessions
    *
    */
   protected function _initSession()
   {
      $this->bootstrap("db");
      $dbAdapter                 =  Zend_Registry::get("db");       
      $options                   =  $this->getOptions();

      $sessionOptions            =  $options["resources"]["session"];
      $optionSaveHandler         =  $options["resources"]["session"]["saveHandler"]["options"];
      unset($sessionOptions["saveHandler"]);
      $optionSaveHandler["db"]   = $dbAdapter;

      $saveHandler               = new Zend_Session_SaveHandler_DbTable($optionSaveHandler);       
      Zend_Session::setSaveHandler( $saveHandler );
      Zend_Session::setOptions(     $sessionOptions);
             
      $defaultNamespace    = new Zend_Session_Namespace("Default");
      $userCreationSession = new Zend_Session_Namespace("inscription");
      $guestCounter        = new Zend_Session_Namespace("guestCounter");
      $authUser            = new Zend_Session_Namespace("User_Auth");
      if(!isset( $userCreationSession->initialised ) ) {
       	   $userCreationSession->initialised = true;
       	   $userCreationSession->setExpirationSeconds(86400);
       	   $userCreationSession->token = Sirah_User_Helper::getToken(15).time();
       }
       if( !isset( $guestCounter->initialised ) && ( !$authUser->initialised ) )  {
       	   $guestCounter->initialised        = true;
       	   $guestCounter->setExpirationSeconds(86400);
       	   //On vérifie d'abord si la visite de cet invité n'a pas encore été enregistré
       	   $guestIp         = Sirah_Functions::getIpAddress();
       	   $guestHttpClient = Sirah_Functions::getBrowser();
       	   $time            = time();
       	   $sessionId       = Zend_Session::getId();       	   
       	   $dbAdapter->insert("system_guests_connexion", array("ipaddress"  => $guestIp, "date" => $time, "sessionid" => $sessionId,
       	   		                                               "httpclient" => $guestHttpClient, "token" => $userCreationSession->token ));      	  
       }
     }
     
     /**
      * Permet d'initialiser les plugins de l'application
      *
      */
     protected function _initPlugins()
     {
     	$this->_initAutoloadRessource();
     	$fc              = $this->bootstrap('frontcontroller')->frontcontroller;   	 
     	$rbacPlugin      = new Plugin_Rbac();
     	$chechAuthPlugin = new Plugin_Authcheck();
     	$layoutPlugin    = new Plugin_Adminlayout();
		$appConfigPlugin = new Plugin_AppConfig();
     	$fc->registerPlugin( $rbacPlugin );
     	$fc->registerPlugin( $chechAuthPlugin );
     	$fc->registerPlugin( $layoutPlugin    );
		$fc->registerPlugin( $appConfigPlugin );
     }
     
     /**
      * Permet d'initialiser la ressource adaptateur
      * de la base de données
      *
      * @return Zend_Db_Adapter_Abstract instance
      */
     protected function _initDb()
     {
     	$this->bootstrap('multidb');
     	$fc       = $this->bootstrap('FrontController')->getResource('FrontController');   	
     	$multidb  = $this->getPluginResource('multidb');
     	$adapter  = $multidb->getDb();
     	try {
     		$adapter->getConnection();
     	} catch(Exception $e) {
     		if( $fc->throwExceptions() ) {
     			throw new $e;
     		}
     		$response = $fc->getResponse();
     		if( null === $response ) {
     			require_once "Zend/Controller/Response/Http.php";
     			$response = new Zend_Controller_Response_Http();
     			$fc->setResponse($response);
     		}
     		$response->setException($e);
     	}
     	Zend_Db_Table::setDefaultAdapter( $adapter );
     	Zend_Registry::set("db", $adapter );
     	return $adapter;
     }
                
	
	
	/**
	 * Permet d'initialiser la vue par defaut de l'application
	 *
	 */
	protected function _initView()
	{
		$front          = Zend_Controller_Front::getInstance();
		$request        = $front->getRequest();
		$controllerName = $request->getControllerName();
		$moduleName     = $request->getModuleName();
		$actionName     = $request->getActionName();
		
		$view           = new Zend_View();
		$view->rootPath = $rootPath   =  ROOT_PATH;
		$view           = new Zend_View();
		
		$front->setBaseUrl( BASE_PATH );
		
		$view->doctype('HTML5');
		$view->headTitle($view->escape(HEAD_TITLE));
		$view->title     = $view->escape( HEAD_TITLE ) ;
		
		// On ajoute les helpers des vues
		$view->addHelperPath('Sirah/View/Helper/','Sirah_View_Helper');
		$view->addHelperPath('Sirah/View/Helper/Supina'    , 'Sirah_View_Helper_Supina');
		$view->addHelperPath('Sirah/View/Helper/Toolsbar'  , 'Sirah_View_Helper_Toolsbar');
		$view->addHelperPath('Sirah/View/Helper/Buttonsbar', 'Sirah_View_Helper_Buttonsbar');
		$view->addHelperPath('Sirah/View/Helper/Script','Sirah_View_Helper_Script');
		$view->addHelperPath('Ext/ZendX/JQuery/View/Helper','ZendX_JQuery_View_Helper');
		$view->addHelperPath('Ext/Twitter/View/Helpers/Twitter/Bootstrap/Tables','Twitter_Bootstrap_Tables');
		$view->addHelperPath('Ext/Twitter/View/Helpers/Twitter/Bootstrap/Tables/Table','Twitter_Bootstrap_Tables_Table');
		$view->addHelperPath('Ext/Twitter/View/Helpers/Twitter/Bootstrap/Images','Twitter_Bootstrap_Images');
		$view->addHelperPath('Ext/Twitter/View/Helpers/Twitter/Bootstrap/Typography/','Twitter_Bootstrap_Typography');
		
		$view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=utf-8')->appendHttpEquiv('Content-Language', 'fr-FR');
		$jquery  =  $view->jQuery();
		$jquery->enable();
		$rmode   = ZendX_JQuery::RENDER_JQUERY_ON_LOAD
		         | ZendX_JQuery::RENDER_SOURCES
		         | ZendX_JQuery::RENDER_STYLESHEETS;
		
		$jquery->setRenderMode($rmode);
		
		$view->jsPath    = $jsPath  = ROOT_PATH ."/myTpl/rccm/js/";
		$view->cssPath   = $cssPath = ROOT_PATH ."/myTpl/rccm/css/";
		$supinaThemePath =            ROOT_PATH ."/myTpl/rccm/assets-minified/";
				
		$view->headLink()->appendStylesheet( $supinaThemePath . "helpers/helpers-all.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "elements/elements-all.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "icons/fontawesome/fontawesome.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "icons/linecons/linecons.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "icons/elusive/elusive.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "icons/typicons/typicons.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "snippets/snippets-all.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "applications/mailbox.css");
		
		
		$view->headLink()->appendStylesheet( $supinaThemePath . "themes/supina/default/layout-color.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "themes/supina/default/framework-color.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "themes/supina/border-radius.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "helpers/colors.css");
		
		$view->headLink()->appendStylesheet( $supinaThemePath . "widgets/modal/modal.css");
		$view->headLink()->appendStylesheet( $cssPath         . "bootstrap/css/bootstrap-modal.css");
		$view->headLink()->appendStylesheet( $cssPath         . "layout.css");
		$view->headLink()->appendStylesheet( $cssPath         . "pdfdialog.css");
		$view->headLink()->appendStylesheet( $cssPath         . "spinner.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "/widgets.css");		
		$view->headLink()->appendStylesheet( $supinaThemePath . "widgets/icheck/minimal/green.css");
		$view->headLink()->appendStylesheet( $supinaThemePath . "widgets/icheck/minimal/orange.css");		
		$view->headLink()->appendStylesheet( $supinaThemePath . "widgets/uniform/uniform.css");
		
		$view->headLink(array('rel' => "shortcut icon", "href" => $supinaThemePath ."images/icons/favicon.ico"), 'PREPEND');
		$view->headLink(array('rel' => "apple-touch-icon-precomposed", 'href' => $supinaThemePath ."images/icons/apple-touch-icon-57-precomposed.png" ), 'PREPEND');
		$view->headLink(array('rel' => "apple-touch-icon-precomposed", 'href' => $supinaThemePath ."images/icons/apple-touch-icon-72-precomposed.png" ), 'PREPEND');
		$view->headLink(array('rel' => "apple-touch-icon-precomposed", 'href' => $supinaThemePath ."images/icons/apple-touch-icon-114-precomposed.png"), 'PREPEND');
		$view->headLink(array('rel' => "apple-touch-icon-precomposed", 'href' => $supinaThemePath ."images/icons/apple-touch-icon-144-precomposed.png"), 'PREPEND');
		
		
		$view->headScript()->appendFile(  $supinaThemePath   ."js-core.js");		
		$view->headScript()->appendFile(  $supinaThemePath   ."widgets.js");
		$view->headScript()->appendFile(  $supinaThemePath   ."widgets/modal/modal.js");
		
		$view->headScript()->appendFile(  $jsPath            ."bootstrap/jquery.dialog2.js");
		$view->headScript()->appendFile(  $jsPath            ."bootstrap/jquery.dialog2.helpers.js");		
		$view->headScript()->appendFile(  $jsPath            ."jquery.numeric.js");
		$view->headScript()->appendFile(  $supinaThemePath   .'widgets/icheck/icheck.js');	
		$view->headScript()->appendFile(  $jsPath            ."sirah-1.0.js");
		$view->headScript()->appendScript("
				                            window.SIRAH ||(window.SIRAH={}); 
                                            SIRAH.basePath = \"".VIEW_BASE_PATH."/\";
                                            jQuery(document).ready(function(){												   
				                                   jQuery('.clearMessage').click(function(event){
				                                            event.preventDefault();
				                                            jQuery(this).closest('#currentMsg').fadeOut('slow');
				                                            jQuery(this).closest('div#sirah-page-message').fadeOut('slow');
				                                   });				                                   
	                                         });
				                             setTimeout(function() {jQuery('#loading').fadeOut( 400, 'linear' );}, 300);");
		
		$view->columns               = array("left","right");
		$view->jsHandler             = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
		$viewRenderer                = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		
		$viewRenderer->setView($view);
		
		return $view;
	}
	
	
	/**
	 * Permet d'initialiser l'autoloader des ressources independantes de l'application
	 *
	 */
	
	protected function _initAutoloadRessource()
	{
		$config    = $this->getOptions();
		$front     = $this->getResource('FrontController');
		$request   = $front->getRequest();
		 
		if ( null===$request ) {
			require_once 'Zend/Controller/Request/Http.php';
			$request = new Zend_Controller_Request_Http();
			$front->setRequest($request);
		}
		$module          = strtolower( $request->getModuleName() );
		$defaultPath     = APPLICATION_PATH . DS . $module .DS ;
		if ( !is_dir( $defaultPath  ) || empty( $module ) ) {
			$defaultPath = APPLICATION_PATH . DS . "default" . DS ;
		}
		$basePath        = $defaultPath . 'models' . DS;
		//configuration de l'Autoload
		$modelLoader     = new Zend_Loader_Autoloader_Resource(array('namespace' => '','basePath'  => $basePath));
		$modelLoader->addResourceType('forms'       , 'Form/'   , 'Form');
		$modelLoader->addResourceType('modelRows'   , 'Row/'    , 'Model');
		$modelLoader->addResourceType('modelTables' , 'Table/'  , 'Table');
		$modelLoader->addResourceType('modelPlugins', 'Plugin/' , 'Plugin');
		$modelLoader->addResourceType('modelPdf'    , 'Pdf/'    , 'ProjectPdf');
		$modelLoader->addResourceType('validators'  , 'Validator/' , 'Validator');
	
		return $modelLoader;
	}	
 }