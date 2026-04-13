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
 * Cette classe représente une aide de vue
 * 
 * qui permet de créer l'instance de jquery
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

  class Sirah_View_Helper_Months extends Zend_View_Helper_Abstract
  {
  	
  		
  	
      public function months()
      {
         $mois   = array(1    => "01 - Janvier",
                         2    => "02 -Février",
                         3    => "03 - Mars",
                         4    => "04 - Avril",
                         5    => "05 - Mai",
                         6    => "06 - Juin",
                         7    => "07 - Juillet",
                         8    => "08 - Août",
                         9    => "09 - Septembre",
                         10   => "10 - Octobre",
                         11   => "11 - Novembre",
                         12   => "12 - Décembre");
         return $mois;
      }  	  	

   }

