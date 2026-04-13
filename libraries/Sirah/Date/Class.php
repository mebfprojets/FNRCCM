<?php

class Sira_Date_Class extends DateTime{


public $langue="fr";

public $date=null;

public static $format="d-m-Y H:i:s";

public $translator=null;



function __construct($date="now",$timeZone=null,$langue="fr"){

            parent::__construct($date);
            if($timeZone!==null){
                 if (!($timeZone instanceof DateTimeZone)) {
                      $timeZone=new DateTimeZone($timeZone);
                  }
               $this->setTimezone($timeZone);
              }
           $this->_createTranslator($langue);

  }


function setLangue($lng){

    $this->langue=$lng;

  }


static function convert($number,$from="hour",$to="day",$date=null){

        $val =  null;

        switch($to){

                case "minute":
                        if($from==="second")
                        {
                          $val  = $number/60;                               

                             }
                        break;
                case "hour":
                           switch($from){

                               case "second"  : 
                                              $val = $number/3600;
                                              break;
                               case "minute"  :
                                              $val = $number/60;
                                              break;                              

                                    }
                            break;
               case "day":
                           switch($from){
                        
                                  case "second":
                                                 $heures = Sira_Date_Class::convert($number,"second","hour");
                                                 $val    = Sira_Date_Class::convert($heures,"hour","day");
                                                 break;
                                  case "minute":
                                                 $heures = Sira_Date_Class::convert($number,"minute","hour");
                                                 $val    = Sira_Date_Class::convert($heures,"hour","day");
                                                 break;
                                  case "hour"  :
                                                 $val    =  $number/24;
                                                 break;                    
                                  }
                            break;
              case "month":
                            switch($from){

                                case "second" :
                                                $jours = Sira_Date_Class::convert($number,"second","day");
                                                $val   = Sira_Date_Class::convert($jours,"day","month");
                                                break;
                               case  "minute" :
                                                $jours = Sira_Date_Class::convert($number,"minute","day");
                                                $val   = Sira_Date_Class::convert($jours,"day","month");
                                                break;
                               case  "hour"   :
                                                $jours = Sira_Date_Class::convert($number,"hour","day");
                                                $val   = Sira_Date_Class::convert($jours,"day","month");
                                                break;
                               case  "day"   :
                                                $val   = $number/30;
                                                break;
 

                            }
           }


            return $val;

}

function _createTranslator($langue=null){

        if(null===$langue){

             $langue=$this->langue;

           }
      $translatorClass="Sira_Date_Translators_".ucfirst($langue);

      if(!class_exists($translatorClass)){

           trigger_error( "Impossible de recuperer le traducteur en langue  {$langue} " );

          }

       $this->translator=$translator=new $translatorClass;
       return $translator;

   }

function translate($var,$type="jour",$abbr=false){

    if(!is_object($this->translator)){

        trigger_error(" Impossible de traduire le temps que vous avez spécifié ");

     }
     return ($abbr)?$this->translator->abreger($var,$type):$this->translator->find($var,$type);

  }

function getLangue(){

   return $this->langue;

}


public static function setFormat($format){

    self::$format=$format;

}

function getPremierJourMois(){

     $mois=$this->mois;
     $annee=$this->annee;

     $timestamp=mktime(0,0,0,$mois,1,$annee);
     $this->setTimestamp($timestamp);
     return $this->jourSemaine;
  }


function getInfos()
{
     $timestamp = $this->format("U");
     $infos     = getdate($timestamp);
     return $infos;
   }

function getNomJours($abbr=false)
{    
     $i=1;
     $jours=array();
     while($i<8){

        $jours[$i]=$this->translate($i,"jour",$abbr);
        $i++;
      }

     return $jours;
  }

function suivant($element){

  $val=null;
  switch($element){
        
          case "annee":
                      $this->modify(" +1 year ");
                      $val=$this->annee;
                      break;
          case "mois":
                      $this->modify("+1 month");
                      $val=$this->mois;
                      break;
          case "jour":
                      $this->modify("+1 day");
                      $val=$this->jourMois;
                      break;
          default :
                     $val=null;
       }

     return $val;
   }



function precedent(){

  $val=null;
  switch($element){
        
          case "annee":
                      $this->modify(" -1 year ");
                      $val=$this->annee;
                      break;
          case "mois":
                      $this->modify("-1 month");
                      $val=$this->mois;
                      break;
          case "jour":
                      $this->modify("-1 day");
                      $val=$this->jourMois;
                      break;
          default :
                     $val=null;
       }

     return $val;

    }
  public function __toString(){

		return (string) parent::format(self::$format);
	}

function isEnglishLanguage(){

     return ($this->langue=="En");

  }


function estBissextile(){

     return (boolean) $this->format("L",true);
 
  }

function __get($nom){

    $value = null;

    switch ($nom) {

         case "nbreJourMois":
                                $value = $this->format('t');
				break;
         case  "jourMois"   :
                                $value=$this->format("d");
                                break;
         case "nomJourMois":
                                $jourNum=$this->format("w");
                                $value=  $this->translate($jourNum);
                                break;
         case "nomJourMoisAbbr":
                                $jourNum=$this->format("w");
                                $value=($this->isEnglishLanguage())?$this->format("D"):$this->translate($jourNum);
                                break;
         case "jourSemaine":   
                                $value = $this->format("w");
                                break;
         case "nomJourSemaine": 
                                $numJourSem = $this->format("w");  
                                $value = ($this->isEnglishLanguage())? $this->format("w"):$this->translate($numJourSem);
                                break;
         case "semaine":
                                $value=$this->format("W");
                                break;
         case "mois"      :
                                $value=$this->format("m");
                                break;
         case "nomMois"   :
                                $numMois=$this->format("n");
                                $value=($this->isEnglishLanguage())?$this->format("F"):$this->translate($numMois,"mois",false);
                                break;

         case "numMois"    :    $value=$this->format("n");
                                break;
         case "nomMoisAbbr":
                                $numMois = $this->format("n");
                                $value=($this->isEnglishLanguage())?$this->format("M"):$this->translate($numMois,"mois");
                                break;
         case "heure"      :

                                $value=$this->format("H");
                                break;
         case "minute"       :
                                $value=$this->format("i");
                                break;
         case "seconde"      :
                                $value=$this->format("s");
                                break;
         case "annee"        :
                                $value=$this->format("Y");
                                break;
          case "anneeAbbr"   :
                                $value=$this->format("y");
                                break;
          default            :
                               if(!isset($his->$name)){
                              
                                   trigger_error("  Impossible de recuperer cette propriete de la date ");
                               }
         }

          return $value;
   }




}
