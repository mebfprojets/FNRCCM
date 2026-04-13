<?php
/**
 * Ce fichier est une partie de la librairie de SIRAH
 *
 * Cette librairie est essentiellement basee sur les composants des la
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
 * Cette classe permet de gerer la création
 * et la modification des documents PDF de
 * l'application.
 *
 *
 * @copyright  Copyright (c) 2013-2020 SIRAH BURKINA FASO
 * @license    http://sirah.net/license
 * @version    $Id:
 * @link
 * @since
 */

require_once("tcpdf/tcpdf.php");

class Sirah_Pdf_Default extends TCPDF 
{
	
	public function __construct( $orientation = 'P' , $unit = 'mm' , $format='A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false )
	{
		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
	}
		
		
	public function AddPhotos( $fichiers = array() , $cols = 3 , $rows = 0 , $x = 0 , $y = 0 , $w = 0 , $h = 0 , $paddings=array() )
	{
		$horizontal_alignments = array('L', 'C', 'R');
		$vertical_alignments   = array('T', 'M', 'B');
		
		if(!$cols) {
			$cols           = 3;
		}		
		if(!$rows) {
			$rows           = count($fichiers) / $cols;
		}		
		if(!$x) {
			$x              = $this->GetX();
		}		
		if(!$y) {
			$y              = $this->GetY();
		}		
		if(empty($paddings)){
			$paddings       = array(   "left"     =>  2,
					                   "top"      =>  2,
					                   "bottom"   =>  2,
					                   "right"    =>  2 );
		}		
		if(count($fichiers))
		{
			$border_style   =  array("L"    =>   array("width"   =>  "0.25","cap" => "round","join" => "mitter","dash" => 0,"color" => array(0,0,0)),
					                 "T"    =>   array("width"   => "0.25","cap"  => "round","join" => "mitter","dash" => 0,"color" => array(0,0,0)),
					                 "R"    =>   array("width"   => "0.25","cap"  => "round","join" => "mitter","dash" => 0,"color" => array(0,0,0)),
					                 "B"    =>   array("width"   => "0.25","cap"  => "round","join" => "mitter","dash" => 0,"color" => array(0,0,0)) );
		
			for ($i = 0; $i < $rows; ++$i)
			{
				$fitbox = $horizontal_alignments[$i].' ';
				$x      = $this->original_lMargin;
				$rectW  = $w;
				$rectH  = $h;
				for ($j = 0; $j < $cols; ++$j)
				{
					$fichier  = array_shift($fichiers);
					if($fichier!==false)
					{
						$image        = $fichier["fichier"];
						$imgsize      = @getimagesize($image);
						if($imgsize !== FALSE)
						{
							list($imgW,$imgH)  = $imgsize;
		
							$imgW         = $this->pixelsToUnits($imgW);
							$imgH         = $this->pixelsToUnits($imgH);
							$fitbox{1}    = $vertical_alignments[$j];
		
							$designation  = (isset($fichier["designation"])) ? stripslashes($fichier["designation"]) : "";
		
							$rectW        = ($w==0) ? ($imgW+$paddings["left"]+$paddings["right"])    : $w;
							$rectH        = ($h==0) ? ($imgH+$paddings["top"]+$paddings["bottom"]+10) : $h;
		
							$imgX         = $x+$paddings["left"];
							$imgY         = $y+$paddings["top"];
		
							$designationX = $imgX;
							$designationY = $y+$imgH+$paddings["top"];
							$designationW = $rectW;
							$designationH = $paddings["top"]+$paddings["bottom"]+10;
		
							$this->Rect($x, $y,$rectW,$rectH,'F',$border_style, array(255,255,255));
							$this->Image($image,$imgX,$imgY,$imgW,$imgH, '', '', '', false, 300, '', false, false, 0, $fitbox, false, false);
		
							$this->Rect($x,$designationY,$designationW,$designationH,'F',array(), array(0,0,0));
		
							$this->SetTextColor(255,255,255);
							$this->Text($designationX,$designationY+2,$designation);
							$this->SetTextColor(0,0,0);
		
							$x += $rectW+5; // nouvelle colonne
						}
					}
				}
				$y += $rectH+5; // nouvelle ligne
			}
		}
	}
	
	public function Footer()
	{
		if( !$this->print_footer ) {
			return;
		}
		$structure  = null;
		if(class_exists("Model_Structure")) {
			$modelStructure  = new Model_Structure();
			$structure       = $modelStructure->get();
		}
		$libelle   = ($structure) ?  $structure->libelle   : "";
		$telephone = ($structure) ?  $structure->telephone : "";
		$adresse   = ($structure) ?  $structure->adresse   : "";
		$email     = ($structure) ?  $structure->email     : "";
		$fax       = ($structure) ?  $structure->fax       : "";
		$siteweb   = ($structure) ?  $structure->siteweb   : "";
		$ville     = ($structure) ?  $structure->ville     : "";
		$ifu       = ($structure) ?  $structure->ifu       : "";
		$capital   = ($structure) ?  number_format($structure->capital , 0 , " "," ") : "";
		
		$current_y = $this->y;
		$line_width= 0.85 / $this->k;
		
		$this->SetTextColor(0, 0, 0);		
		$this->SetY( $current_y + 10 );				
		$this->SetLineStyle(array('width'  =>  $line_width, 'cap'  =>  'butt', 'join'  =>  'miter', 'dash'  =>  0, 'color'  =>  array(0, 0, 0)));
		
		if(empty($this->pagegroups)) {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();		
		} else {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
		}
		
		$txt = $adresse . " Tel: ".$telephone." Fax: ".$fax."
		                    Email: <u>".$email."</u>  Site Web : <b>".$siteweb."</b>
		                    SARL au capital de ".$capital." F CFA IFU : ".$ifu." - Compte N° 00135880001876";
		
		$textWidth  = $this->getPageWidth()-$this->original_rMargin-$this->original_lMargin-15;		
		if($this->getRTL()) {
			$this->SetX($this->original_rMargin);
			$this->MultiCell($textWidth,5,$txt,"T","C",false,0,'','',true,0,true);
			$this->Cell(0, 0, $pagenumtxt, 'LT', 0, 'L');
		} else {
			$this->SetX($this->original_lMargin);
			$this->MultiCell($textWidth  , 5  ,  $txt  ,  "T"  ,  "C"  , false , 0 , '' , '' , true , 0 , true);
			$this->Cell(0, 0, $pagenumtxt, 'LT', 0, 'R');
		}
	}
	
	public function Header()
	{
		if( !$this->print_footer ) {
			return;
		}
		$structure      = null;
		$modelStructure = null;
		if(class_exists("Model_Structure")) {
			$modelStructure  = new Model_Structure();
			$structure       = $modelStructure->get();
		}
		$libelle   = ($structure) ?  $structure->libelle   : "";
		$telephone = ($structure) ?  $structure->telephone : "";
		$adresse   = ($structure) ?  $structure->adresse   : "";
		$email     = ($structure) ?  $structure->email     : "";
		$fax       = ($structure) ?  $structure->fax       : "";
		$siteweb   = ($structure) ?  $structure->siteweb   : "";
		$ville     = ($structure) ?  $structure->ville     : "";
		$ifu       = ($structure) ?  $structure->ifu       : "";
		$capital   = ($structure) ?  number_format($structure->capital , 0 , " "," ") : "";
		
		if($this->header_xobjid<0) {
			$this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
			$headerfont     = $this->getHeaderFont();
			$headerdata     = $this->getHeaderData();		
			$this->y        = $this->header_margin;		
			$this->y        = $this->header_margin;
		
			if ($this->rtl){
				$this->x    = $this->w - $this->original_rMargin;
			} else {
				$this->x    = $this->original_lMargin;
			}		
			$imgy            = $this->y;
			$textFontFamily  = $this->getFontFamily();
			$textFontSize    = $this->getFontSize();
			$logoWidth       = 0;
			$textCellHeight  = 0;			
			$logo            = ( $modelStructure ) ?  $modelStructure->getLogoPath()   : null ;
			$entete          = ( $modelStructure ) ?  $modelStructure->getDocumentEntetePath() : null;
			
			if( null !== $entete && file_exists($entete) ) {
				$imsize           = @getimagesize($entete);
				list($pixw, $pixh)= $imsize;
				$enteteWidth      = $this->pixelsToUnits($pixw);
				$enteteHeight     = $this->pixelsToUnits($pixh);
				if((!$entete || !file_exists( $entete )) && (($headerdata['entete']) && ($headerdata['entete'] != K_BLANK_IMAGE))) {
					$entete       = $headerdata['entete'];
					$enteteWidth  = $headerdata['entete_width'];
				}
				if( file_exists( $entete )) {
					$imgtype = $this->getImageFileType( $entete );
					if (($imgtype == 'eps') OR ($imgtype == 'ai')){
						$this->ImageEps( $entete , '', '', $enteteWidth);
					} elseif($imgtype == 'svg') {
						$this->ImageSVG($entete , '' , '' , $enteteWidth ) ;
					} else {
						$this->Image( $entete , '' , '' , $enteteWidth , 0 , '' , '' , 'L' , false);
					}
				}
				return;
			}			
			if($logo) {		
				//On recupere les tailles originales du logo en pixels		
				$imsize           = @getimagesize($logo);
				list($pixw, $pixh)= $imsize;
				$logoWidth        = $this->pixelsToUnits($pixw);
				$textCellHeight   = $logoHeight = $this->pixelsToUnits($pixh);
				if((!$logo || !file_exists($logo)) && (($headerdata['logo']) && ($headerdata['logo'] != K_BLANK_IMAGE))) {
					$logo         = $headerdata['logo'];
					$logoWidth    = $headerdata['logo_width'];
				}		
				if( file_exists($logo)) {
					$imgtype = $this->getImageFileType($logo);		
					if (($imgtype == 'eps') OR ($imgtype == 'ai')){
						$this->ImageEps($logo, '', '', $logoWidth);
					} elseif($imgtype == 'svg') {
						$this->ImageSVG($logo , '' , '',$logoWidth ) ;
					} else {
						$this->Image( $logo, '' , '' , $logoWidth , 0 , '' , '' , 'L' , false);
					}
					$imgy = $this->getImageRBY();
				}
			}
			//Si l'affichage du texte de l'entete est autorisé dans le systeme
				// On definit les marges du texte qui s'affichera pres du logo
				if($this->getRTL()) {
					$header_x   = $this->original_rMargin + ($logoWidth * 1.1);
				} else {
					$header_x   = $this->original_lMargin + ($logoWidth * 1.1);
				}
				$cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($logoWidth * 1.1);
		
				$this->SetTextColor(0, 0, 0);
		
				$textCellHeight = round(($this->cell_height_ratio * $headerfont[2]) / $this->k, 2);		
				$textFontFamily = $headerfont[0];		
				$textFontSize   = $headerfont[2];
		
				// Le titre de l'entete
				if(isset($headerdata['title']) && !empty($headerdata['title'])){
					$this->SetFont($textFontFamily, 'B',$textFontSize+1);
					$this->SetX($header_x);
					$this->MultiCell( $cw , $textCellHeight , $headerdata['title'] , 0 , 'C' , 0 , 1 , '' , '' , true , 0 , true);
				}				 
				//Le texte de l'entete
				if(isset($headerdata['string']) && !empty($headerdata['string'])) {
					$this->SetFont($textFontFamily, $headerfont[1],$textFontSize);
					$this->SetX( $header_x );
					$this->MultiCell($cw,$textCellHeight, $headerdata['string'], 0 , 'C' , 0, 1, '', '', true, 0, true);
				} else {
					$header_string  = " Tel: ".$telephone."/ Fax: ".$fax."\n Email: <u>".$email."</u>\n  Site Web : <b>".$siteweb."</b>";
					$this->SetFont($textFontFamily , $headerfont[1],$textFontSize);
					$this->SetX($header_x);
					$this->MultiCell($cw , $textCellHeight , $header_string , 0, 'C', 0, 1, '', '', true, 0, true);
				}		
				//On illustre la fin de l'entete par une ligne		
				$this->SetLineStyle(array('width'  =>  0.65 / $this->k, 'cap'  =>  'butt', 'join'  =>  'miter', 'dash'  =>  0, 'color'  =>  array(0, 0, 0)));		
				$this->SetY((2.835 / $this->k) + max($imgy, $this->y));
		
				if($this->rtl) {
					$this->SetX($this->original_rMargin);
				} else {
					$this->SetX($this->original_lMargin);
				}
				$this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
				$this->SetY($this->y + 10);
				$this->endTemplate();				
		$x = 0;
		$dx = 0;
		if ($this->booklet AND (($this->page % 2) == 0)) {
			// adjust margins for booklet mode
			$dx = ($this->original_lMargin - $this->original_rMargin);
		}
		if ($this->rtl) {
			$x = $this->w + $dx;
		} else {
			$x = 0 + $dx;
		}
		   $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
	   }				
	}

 }



