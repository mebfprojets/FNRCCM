<?php

setlocale(LC_TIME,"fr_FR","FRA");

defined('TTF_DIR')
	|| DEFINE("TTF_DIR", "fonts");
   
function showDate($date,$format="d/m/Y H:i:s"){
    $dateTime  = new DateTime($date);     
    return $dateTime->format($format);
}
   
function yScaleCallback($aVal) {
  return number_format($aVal,0,'',' ');
}
function createYearsArray( $from , $to , $sort="desc")
{ 
     	$years  = array();
     	for( $i=$from;$i<=$to;$i++){
     		 $years[$i]   = sprintf("%04d",$i);
     	}
     	switch(strtolower( $sort ) ) {
     		case "desc":
     		default:
     			arsort( $years , SORT_NUMERIC );
     			break;     			
     		case "asc":
     			asort( $years , SORT_NUMERIC );
     	}
     	return $years;
}

function createHoursArray()
{
   $hours  = array();
   for($i=0;$i<=24;$i++){
     	$hours[$i]  = sprintf("%02d" , $i);
    }
   return $hours;
}

function createMinutesArray( )
{
	$minutes  = array();
	for($i=0;$i<=59;$i++){
		$minutes[$i]  = sprintf("%02d" , $i);
	}
	return $minutes;
}
     
function createDatesArray()
{
    $days  = array();
    for( $i=1;$i<=31;$i++){
     	 $days[$i]  = sprintf("%02d",$i);
    }
   return $days;
}
defined("API_TOUCHPAY_AGENCY_CODE")
    || DEFINE("API_TOUCHPAY_AGENCY_CODE","MSENT2793");
defined("API_TOUCHPAY_SECURE_CODE")
    || DEFINE("API_TOUCHPAY_SECURE_CODE","suzYy19U3ev2TIsabnK6JtKaFG75oZjc1zD36Mc4QPgcVXRZAM");
defined("API_TOUCHPAY_DOMAIN")
    || DEFINE("API_TOUCHPAY_DOMAIN","maison.com");	
	
defined("API_GOOGLE_RECAPTCHA_SITE_KEY")
    || DEFINE("API_GOOGLE_RECAPTCHA_SITE_KEY","6LccIdUjAAAAAN03LJc_FfUEqnUBEMgSdMl7T5Yt");
	  
defined("API_GOOGLE_RECAPTCHA_SECRETE_KEY")
    || DEFINE("API_GOOGLE_RECAPTCHA_SECRETE_KEY","6LccIdUjAAAAALJqJSg2g7FQgdSGFdcAK5tZARFq");

defined("APPLICATION_ORDERCART_EXPIRATION")
    || define("APPLICATION_ORDERCART_EXPIRATION",86400);
defined('APPLICATION_DEFAULT_USERS_ROLENAME')
    || define('APPLICATION_DEFAULT_USERS_ROLENAME', "Public");	 
defined("APPLICATION_CLIENTS_ROLENAME")
    || define("APPLICATION_CLIENTS_ROLENAME", "OPERATEURS");
defined("APPLICATION_LEADERS_ROLENAME")
    || define("APPLICATION_LEADERS_ROLENAME", "PROMOTEURS");     
defined("APPLICATION_MANAGERS_ROLENAME")
    || define("APPLICATION_MANAGERS_ROLENAME", "DIRECTEURS");
defined("APPLICATION_PARTNERS_ROLENAME")
    || define("APPLICATION_PARTNERS_ROLENAME", "PARTENAIRES"); 
defined("APPLICATIONS_RECIPIENTS_ROLENAME")
    || define("APPLICATION_RECIPIENTS_ROLENAME", "BENEFICIAIRES");

defined("API_ODS_CONFIG_FILE")
      || DEFINE("API_ODS_CONFIG_FILE","D:\\webserver/www/sigueapi/config/default.json");
defined("API_ODS_HOST")
      || DEFINE("API_ODS_HOST","http://10.3.1.72");	  
defined("API_ODS_URI_ROOT")
      || DEFINE("API_ODS_URI_ROOT","/apiods/api");	  
defined("API_ODS_URI")
      || DEFINE("API_ODS_URI",API_ODS_HOST."/apiods/api");	 	  
defined("API_ODS_PORT")
      || DEFINE("API_ODS_PORT","3090");	
defined("API_ODS_AUTH_USER")
      || DEFINE("API_ODS_AUTH_USER","bhamed"); 	
defined("API_ODS_AUTH_PWD")
      || DEFINE("API_ODS_AUTH_PWD","bhamed"); 

defined("API_SIGUE_CONFIG_FILE")
      || DEFINE("API_SIGUE_CONFIG_FILE","D:\\webserver/www/sigueapi/config/default.json");
defined("API_SIGUE_HOST")
      || DEFINE("API_SIGUE_HOST","http://10.60.16.19");	
defined("API_SIGUE_URI_ROOT")
      || DEFINE("API_SIGUE_URI_ROOT","/sigueapi/v1");	 	  
defined("API_SIGUE_PORT")
      || DEFINE("API_SIGUE_PORT","3090");	
defined("API_SIGUE_AUTH_USER")
      || DEFINE("API_SIGUE_AUTH_USER","banaohamed"); 	
defined("API_SIGUE_AUTH_PWD")
      || DEFINE("API_SIGUE_AUTH_PWD","1234"); 
	  
defined("SIGUEDB_HOST")
      || DEFINE("SIGUEDB_HOST","10.60.16.17"); 	
defined("SIGUEDB_NAME")
      || DEFINE("SIGUEDB_NAME","SIGU_Commun_PROD");	  
defined("SIGUEDB_USERNAME")
      || DEFINE("SIGUEDB_USERNAME","admin_fnere"); 	  
defined("SIGUEDB_PWD")
      || DEFINE("SIGUEDB_PWD","P@ssw0rd"); 
defined("SIGUEDB_PORT")
      || DEFINE("SIGUEDB_PORT","8014");	
	  
defined("VIEW_BASE_URI")
     || DEFINE("VIEW_BASE_URI", "http://localhost/erccm");
defined("DEFAULT_UPLOAD_MAXSIZE")
     || DEFINE("DEFAULT_UPLOAD_MAXSIZE", "50MB");
  
defined("NB_CHECK_AUTHS_CAPTCHA")
     || DEFINE("NB_CHECK_AUTHS_CAPTCHA", 10 );
defined("NB_CHECK_AUTHS")
     || DEFINE("NB_CHECK_AUTHS", 10);
defined("BASIC_MODE")
     || DEFINE("BASIC_MODE", 1 );
defined("DEFAULT_START_YEAR")
     || DEFINE("DEFAULT_START_YEAR", 1998);
defined("DEFAULT_END_YEAR")
     || DEFINE("DEFAULT_END_YEAR", intval(date("Y") +1));
defined("DEFAULT_START_MONTH")
     || DEFINE("DEFAULT_START_MONTH", "01");
defined("DEFAULT_END_MONTH")
     || DEFINE("DEFAULT_END_MONTH", "01");
defined("DEFAULT_START_DAY")
     || DEFINE("START_DAY", "01");
defined("DEFAULT_END_DAY")
     || DEFINE("DEFAULT_END_DAY", "01");
defined('APPLICATION_HOST')
     || define('APPLICATION_HOST', 'localhost');
defined('ENTREPRISEID')
     || define('ENTREPRISEID',1);
// Définit l'environnement dans lequel l'application fonctionne
defined('APPLICATION_ENV')
     || define('APPLICATION_ENV', 'development');

defined('APPLICATION_DEBUG')
     || define('APPLICATION_DEBUG', ((APPLICATION_ENV === "development" || APPLICATION_ENV === "production") ? 1 : 0));

defined("DEFAULT_FIND_DOCUMENTS_SRC")
     || define("DEFAULT_FIND_DOCUMENTS_SRC", "C:\SAUVEGARDES\ERCCM");
defined('APPLICATION_DEFAULT_USERS_ROLENAME')
     || define('APPLICATION_DEFAULT_USERS_ROLENAME', "Members");
     
defined('DS')
     || define('DS', "/");

defined('OS_WINDOWS')
     || define('OS_WINDOWS',!strncasecmp(PHP_OS,"win",3));

// Définit le chemin du repertoire de l'application
defined('SERVER_ROOT_PATH')
     || define('SERVER_ROOT_PATH', "/");

// Définit le chemin du repertoire de l'application
defined('ROOT_PATH')
     || define('ROOT_PATH','/erccm');

defined('BASE_PATH')
     || define('BASE_PATH','/erccm/');

defined('PUBLIC_PATH')
     || define('PUBLIC_PATH', realpath(dirname(__FILE__)));

defined('HEAD_TITLE')
     || define('HEAD_TITLE',"SIRAH : Bienvenue sur la plateforme du FICHIER NATIONAL DES REGISTRES DE COMMERCE ET DE MOBILIERS");

defined('APPLICATION_PATH')
     || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));

defined('LIBRARY_PATH')
     || define('LIBRARY_PATH', realpath(dirname(__FILE__) . '/../../../libraries'));

defined('DOCUMENTS_PATH')
     || define('DOCUMENTS_PATH',         APPLICATION_PATH.DS."documents");

defined('APPLICATION_DATA_PATH')
     || define('APPLICATION_DATA_PATH'  , DOCUMENTS_PATH . DS . "privatedata/sirahbf2546155aoo");

defined("APPLICATION_TEMPLATES")
     || define("APPLICATION_TEMPLATES" , realpath(dirname(__FILE__) . "/../../myTpl/"));

defined("APPLICATION_DATA_USER_PATH")
     || define("APPLICATION_DATA_USER_PATH", APPLICATION_DATA_PATH.DS."users".DS);

defined("APPLICATION_DATA_LOG")
     || define("APPLICATION_DATA_LOG"     , APPLICATION_DATA_PATH . DS . "logs" . DS . "errors.log");

defined("APPLICATION_DATA_CACHE")
     || define("APPLICATION_DATA_CACHE"   , APPLICATION_DATA_PATH . DS . "cachedata".DS);

defined("APPLICATION_DATA_SESSION")
     || define("APPLICATION_DATA_SESSION" , APPLICATION_DATA_PATH . DS . "sessiondata");

defined("USER_AVATAR_PATH")
     || define("USER_AVATAR_PATH"         , APPLICATION_DATA_USER_PATH . "avatars");

defined("APPLICATION_TABLE_NAME_PREFIX")
     || define("APPLICATION_TABLE_NAME_PREFIX","");

defined("APPLICATION_TABLE_SESSION_NAME")
     || define("APPLICATION_TABLE_SESSION_NAME" , APPLICATION_TABLE_NAME_PREFIX . "system_users_session");

defined("VIEW_BASE_PATH")
     || define("VIEW_BASE_PATH" , ROOT_PATH."/myTpl/rccm" );

defined("APPLICATION_TABLE_NAME_PREFIX")
     || define("APPLICATION_TABLE_NAME_PREFIX","");

defined("APPLICATION_TABLE_SESSION_NAME")
     || define("APPLICATION_TABLE_SESSION_NAME" , APPLICATION_TABLE_NAME_PREFIX . "system_users_session");

defined("APPLICATION_STRUCTURE_LOGO")
     || define("APPLICATION_STRUCTURE_LOGO" , "http://www.siraah.net/imgdata/rccm/logo.png");

defined("SERVER_IMAGES_PATHNAME")
     || define("SERVER_IMAGES_PATHNAME", "http://www.siraah.net/imgdata/rccm/");
	 
defined("APPLICATION_INDEXATION_STOCKAGE_FOLDER")
     || define("APPLICATION_INDEXATION_STOCKAGE_FOLDER", APPLICATION_DATA_PATH. DS. "GED");	 
