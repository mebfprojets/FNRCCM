<?php

class Sirah_View_Helper_Languages extends Zend_View_Helper_Abstract{


public function languages()
{

   $languages    = array(
		                  "FR"  => "Français" ,
		                  "EN"  => "English"  ,
		                  "ES"  => "Espagnol" ,
   		                  "AL"  => "Allemand" );   
   return $languages;
   }


}
