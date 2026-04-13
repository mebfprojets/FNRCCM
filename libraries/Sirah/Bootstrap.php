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
 * Cette classe correspond à une aide d'action 
 * qui permet de définir les layout par module
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

class ModuleLayoutLoader extends Zend_Controller_Action_Helper_Abstract
{
	/**
	 * S'exécute avant que l'application entre en boucle de distribution
	 */
    public function preDispatch()
    {
        $bootstrap = $this->getActionController()->getInvokeArg('bootstrap');
        $config    = $bootstrap->getOptions();
        $request   = $this->getRequest();
        $module    = $request->getModuleName();
     
        if (isset($config[$module]['resources']['layout']['layout']) && isset($config[$module]['resources']['layout']['layoutPath'])) {
            $layoutScript = $config[$module]['resources']['layout']['layout'];
            $layoutPath   = $config[$module]['resources']['layout']['layoutPath'];
            $this->getActionController()->getHelper('layout')->setLayout($layoutScript);
            $this->getActionController()->getHelper('layout')->setLayoutPath($layoutPath);
            if($request->isXmlHttpRequest()){
               $this->getActionController()->getHelper('layout')->disableLayout();
            }
        }
    }
}


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
class Sirah_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	
	/**
	 * Permet de lancer le processus  d'exécution de la requete du client
	 *
	 */
    public function run()
    {
	   // Cela permet d'avoir la configuration disponible de partout dans notre application
	   Zend_Registry::set('config', new Zend_Config($this->getOptions()));
		       
       $front   = $this->getResource('FrontController');
       $default = $front->getDefaultModule();
               
       if(null === $front->getControllerDirectory($default)) {
           throw new Zend_Application_Bootstrap_Exception('Aucun controlleur par défaut n\'a été enregistré dans le controlleur principal');
        }
        $front->setParam('bootstrap', $this);        
        $response = $front->dispatch();
        
        if ($front->returnResponse()){
        	return $response;
        }                       
    }

    /**
     * Permet d'initialiser la ressource de gestion des mails
     *
     * @return Zend_Mail instance
     */
    protected function _initMail()
    {
        $this->bootstrap("db");
        $options            =  $this->getOptions();
        $mailOptions        =  $options["resources"]["mail"];    
        $transportOptions   =  $mailOptions["transport"];
        $emailValidator     =  new Zend_Validate_EmailAddress();
        $mailTransport      =  null;
        
        if( isset($transportOptions["type"])){
            $transportType         = strtolower($transportOptions["type"]);    
            switch($transportType)
            {
                default:
                case "sendmail":
                    $mailTransport = new Zend_Mail_Transport_Sendmail();
                    break;
                case "smtp":
                    if(!isset($transportOptions["username"]) || !isset($transportOptions["password"])){
                        throw new Zend_Application_Resource_Exception("Impossible d'initialiser la ressource Mail, car des paramètres sont manquants");
                    }
                    $mailTransport  = new Zend_Mail_Transport_Smtp( $transportOptions["host"],
                                                                    array("auth"     => (isset($transportOptions["auth"]   ))? $transportOptions["auth"]    : "login",
                                                                          "port"     => (isset($transportOptions["port"]   ))? $transportOptions["port"]    : 587,
																		  "secured"  => (isset($transportOptions["secured"]))? $transportOptions["secured"] : false,
																		  "ssl"      => (isset($transportOptions["ssl"]    ))? $transportOptions["ssl"]     : "tls",
                                                                          "username" => $transportOptions["username"],
                                                                          "password" => $transportOptions["password"]));
                    break;
            }
        }        
        $mailerCharset  = (!empty($mailOptions["charset"] ) ) ? $mailOptions["charset"] : "UTF-8";
        $mailer         = new Sirah_Mail($mailerCharset);
        if( $mailTransport &&  
             (!isset($transportOptions['register']) || 
                $transportOptions['register'] == '1' || 
             	(isset($transportOptions['register']) && !is_numeric($transportOptions['register']) &&
            (bool)$transportOptions['register'] == true) ) ) {
         	
           Zend_Mail::setDefaultTransport($mailTransport);         
        }
        if( $emailValidator->isValid($mailOptions["defaultFrom"]["email"] )  ) {
             $defaultFromName   =  (!empty($mailOptions["defaultFrom"]["name"])) ? $mailOptions["defaultFrom"]["name"] : null;
             Zend_Mail::setDefaultFrom($mailOptions["defaultFrom"]["email"],$defaultFromName);
        }
        if( $emailValidator->isValid($mailOptions["defaultReplyTo"]["email"])){
            $defaultReplyToName   =  (empty($mailOptions["defaultReplyTo"]["name"]))?null:$mailOptions["defaultReplyTo"]["name"];
            Zend_Mail::setDefaultReplyTo($mailOptions["defaultReplyTo"]["email"],$defaultReplyToName);
        }
        Zend_Registry::set("mailer" , $mailer);
        return $mailer;
    }

     /**
      * Permet d'initialiser la ressource adaptateur 
      * de la base de données
      *
      * @return Zend_Db_Adapter_Abstract instance
      */
    protected function _initDb()
    {
	   $fc       = $this->bootstrap('frontcontroller')->frontcontroller;
       $resource = $this->getPluginResource('db');
       $adapter  = $resource->getDbAdapter();   
       try {
    	  $adapter->getConnection();
       } catch(Exception $e) {
    	  if($fc->throwExceptions()){
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
       Zend_Db_Table::setDefaultAdapter($adapter);
       Zend_Registry::set("db", $adapter);
       return $adapter;
     }
     
     
     /**
      * Permet d'initialiser la ressource de gestion des caches de l'application
      *
      * @return Zend_Db_Adapter_Abstract instance
      */
     protected function _initCachemanager()
     {
     	if (!Zend_Registry::isRegistered("cacheManager")) {
     		$manager      = new Zend_Cache_Manager;     	
     		$options      = $this->getOptions();
     		$cacheOptions = (isset($options["resources"]["cachemanager"]))?$options["resources"]["cachemanager"]:array();
     		if(!empty(   $cacheOptions)){
     		    foreach( $cacheOptions as $key => $value) {
						if($manager->hasCacheTemplate($key)) {
						   $manager->setTemplateOptions($key, $value);
						} else {
						   $manager->setCacheTemplate($key, $value);
						}
     		    }
     		}    
     		Zend_Registry::set("cacheManager",$manager);
     	}   	
     	return Zend_Registry::get("cacheManager");
     }
     

    /**
     * Permet d'initialiser la ressource adaptateur
     * de la base de données
     *
     * @return Zend_Db_Adapter_Abstract instance
     */
    protected function _initLog()
    {	
       $fc         = $this->bootstrap('frontcontroller')->frontcontroller;
       $options    = $this->getOptions();
       $logPath    = isset($options["log"]["stream_path"])?$options["log"]["stream_path"]:null;

       $log        = new Sirah_Log_Register();

       //On ajoute au redacteur par defaut, un redacteude journalisation
       $flux       = fopen($logPath,"ab+",false);
  
       if(!$flux){
             throw new Sirah_Exception(" Impossible d'ouvrir le flux avec le chemin $logPath ");
       } else {
   	    $log        = new Sirah_Log_Register();
   	    $redacteur  = new Zend_Log_Writer_Stream($flux);
   	
   	    $log->addWriter($redacteur,"fichier");
   	
   	   //On ajoute les cles pid et user
   	    $log->setEventItem("pid",getmypid());
   	    $log->setEventItem("user","invité");
   	
   	   //On ajoute des priorites
   	    $log->addPriority("AUTORISATION",8);
   	    $log->addPriority("AUTHENTIFICATION",9);   	 	
      }   
       //On stocke l'enregistreur dans le registre
       Zend_Registry::set("log",$log);
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
      Zend_Session::setSaveHandler($saveHandler);
      Zend_Session::setOptions($sessionOptions);
      
      try {
      	Zend_Session::start();
      } catch (Exception $e) {
      	print_r('setup error: ' . $e->getMessage() . "\n");
      }         
      $defaultNamespace   = new Zend_Session_Namespace("Default");
      if(!isset($defaultNamespace->initialized)){
          $defaultNamespace->initialized         = true;
       }
     }

      /**
       * Permet d'initialiser les plugins de l'application
       *
       */
      protected function _initPlugins()
      {
         $fc               = $this->bootstrap('frontcontroller')->frontcontroller;
         
         $rbacPlugin       = new Sirah_Controller_Plugin_Rbac();
         $chechAuthPlugin  = new Sirah_Controller_Plugin_Authcheck();         
         $fc->registerPlugin( $rbacPlugin);
         $fc->registerPlugin($chechAuthPlugin);     
      }
      
      
      /**
       * Permet d'initialiser les aides d'action du contraolleur
       *
       */
      protected function _initController()
      {
      	Zend_Controller_Action_HelperBroker::addPath("Sirah/Controller/Action/Helper","Sirah_Controller_Action_Helper");
      }
      
       
      /**
       * Permet d'initialiser le layout de l'application
       *
       */
     protected function _initLayoutHelper()
     {
        $this->bootstrap('frontController');
        $layout = Zend_Controller_Action_HelperBroker::addHelper(new ModuleLayoutLoader());
        return $layout;
     }

     /**
      * Permet d'initialiser le controlleur d'erreur de l'application
      *
      */
     protected function _initErrorHandlerPlugin()
     {   
        $front    = $this->bootstrap('frontcontroller')->frontcontroller;
        $error    = new Zend_Controller_Plugin_ErrorHandler();   
        $error->setErrorHandlerController('error')->setErrorHandlerAction('index');
    
        $front->registerPlugin($error);
        return $error;
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
      
      	return $modelLoader;    	
     }
          
   }
