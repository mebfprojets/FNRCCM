<?php

class Table_Activitevideos extends Sirah_Model_Table
{

     protected $_name         = "sdr_projects_gallery_videos";

     protected $_primary      = array("videoid");
     
     protected $_referenceMap = array( "Galerie" => array(
     		                                                    "columns"       => array("galleryid") ,
     		                                                    "refTableClass" => "Table_Activitegaleries" ,
     		                                                    "refColumns"    => array("galleryid")
                                                           )   	 	 
     		                    );
         
  }

