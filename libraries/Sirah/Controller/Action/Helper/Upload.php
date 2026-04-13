<?php

/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basée sur les composants des la
 * librairie de Zend Framework
 * LICENSE: SIRAH
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

/**
 * Cette classe représente une aide d'action
 * 
 * qui permet d'enregistrer les messages d'erreurs
 * 
 * générés par l'application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

  class Sirah_Controller_Action_Helper_Upload 
        extends Zend_Controller_Action_Helper_Abstract
  {
  	
  	
  	/**
  	 * La m�thode directement appel�e par le controlleur
  	 *
  	 * @param  string | Zend_File_Transfer_Adapter $adapter 
  	 * @param  array                               $types les types de fichiers que nous souhaitons uploader
  	 * @param  integer                             $size la taille maximale des fichiers que nous voulons uploader
  	 * @param  $validators                         les validateurs des fichiers
  	 * @param  $filters                            les filtres sur les fichiers
  	 * @return  
  	 */
  	public function direct($adapter="Http",$types=array(),$size=0,$validators=array(),$filters=array())
  	{
  		//On initialise l'adaptataur d'upload
  	}

  	
  	

   }

