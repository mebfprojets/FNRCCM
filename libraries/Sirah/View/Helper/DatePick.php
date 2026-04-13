<?php

class Sirah_View_Helper_DatePick extends Zend_View_Helper_Abstract
{


  public function datePick($id , $value=null , $params = array() , $attribs=array())
  {
     //On supprime l'id de la memoire du DOM
      $onLoad="jQuery('#{$id}').datepicker('destroy');";
      $this->view->jQuery()->addOnLoad($onLoad);
      $defaultParams   =  array(
                   "dateFormat"        => "yy-mm-dd",
                   "dayNames"          => array("Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"),
                   "monthNames"        => array("Janvier","Fevrier","Mars","Avril","Mai","Juin","Juillet","Aout","Septembre","Octobre","Novembre","Decembre"),
                   "dayNamesMin"       => array("Dim","Lun","Ma","Me","Jeu","Ven","Sam"),
                   "dayNamesShort"     => array("Dim","Lun","Ma","Me","Jeu","Ven","Sam"),
                   "monthNamesShort"   => array("Jan","Fev","Mar","Avr","Ma","Jui","Juil","Aou","Sep","Oct","Nov","Dec"),
                   "currentText"       => "Aujourd'hui",
                   "prevText"          => "Precedent",
                   "nextText"          => "Suivant",
                   "duration"          => "fast");
    //On ecrase les parametres par defaut
     $defaultParams = array_merge( $defaultParams ,  $params );
     $datePicker    = $this->view->datePicker($id , $value , $defaultParams , $attribs);
     return $datePicker;

    }

}
