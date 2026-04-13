<?php

class Sira_Date_Translators_Default  implements Sira_Date_Translators_Interface{


protected $_jours=array(0=>"Dimanche",1=>"Lundi",2=>"Mardi",3=>"Mercredi",4=>"Jeudi",5=>"Vendredi",6=>"Samedi",7=>"Dimanche");

protected $_joursCourt=array(0=>"Dim",1=>"Lun",2=>"Mar",3=>"Mer",4=>"Jeu",5=>"Ven",6=>"Sam",7=>"Dim");

protected $_mois =array( 
                         1=>"Janvier",
                         2=>"Février",
                         3=>"Mars",
                         4=>"Avril",
                         5=>"Mai",
                         6=>"Juin",
                         7=>"Juillet",
                         8=>"Août",
                         9=>"Septembre",
                         10=>"Octobre",
                         11=>"Novembre",
                         12=>"Décembre"
                        );

protected $_moisCourt=array( 
                             1=>"Jan",
                             2=>"Fév",
                             3=>"Mar",
                             4=>"Avr",
                             5=>"Mai",
                             6=>"Juin",
                             7=>"Juil.",
                             8=>"Août",
                             9=>"Sept",
                            10=>"Oct",
                            11=>"Nov",
                            12=>"Dec"
                        );



function find($var,$type){

      $offset=(int)$var;
      $value="";

       switch($type){

       case "mois":
                    $value = isset($this->_mois[$offset])?$this->_mois[$offset]:$offset;
                    break;
       case "jour":
                    $value=isset($this->_jours[$offset])?$this->_jours[$offset]:$offset;
                    break;
    
       default:   trigger_error("
                    Impossible de traduire ce type de temps....
                        ");
                    


             }
       return $value;
     }


function abreger($var,$type){

      $offset=(int)$var;
      $value="";

       switch($type){

       case "mois":
                    $value = isset($this->_moisCourt[$offset])?$this->_moisCourt[$offset]:$offset;
                    break;
       case "jour":
                    $value=isset($this->_joursCourt[$offset])?$this->_joursCourt[$offset]:$offset;
                    break;
       default:   trigger_error("
                    Impossible de traduire ce type de temps....
                        ");
                    


             }
       return $value;

   }


function getOffset($var,$type){


     $value="";
     $mois=array_flip((array)$this->_mois);
     $jours=array_flip((array)$this->_jours);

       switch($type){

       case "mois":
                    $value = isset($mois[$offset])?$mois[$offset]:$offset;
                    break;
       case "jour":
                    $value=isset($jours[$offset])?$jours[$offset]:$offset;
                    break;
       default:   trigger_error("
                    Impossible de recuperer l'index de ce temps
                        ");
                    


             }
       return $value;


   }





}
