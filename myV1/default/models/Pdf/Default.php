<?php


class ProjectPdf_Default extends Sirah_Pdf_Default 
{
	
	protected $printPageNum=0;
	
	public function SetPrintPagenum($state)
	{	
		$this->printPageNum = $state;
		return $this;
	}
	 
	
	public function Footer()
	{
		if(!$this->print_footer ) {
			return;
		}
		$modelProject       = new Model_Project();
		$appConfigSession   = new Zend_Session_Namespace("AppConfig");
		$projectid          = (isset($appConfigSession->project->projectid) && intval($appConfigSession->project->projectid))?$appConfigSession->project->projectid : 1;
		$project            = (intval($projectid))?$modelProject->findRow($projectid,"projectid",null,false) : null;
		$pageFooter         = (defined("DEFAULT_PDF_HEADER"))?DEFAULT_PDF_HEADER : "";
		if( $project ) {
			$projectParams  = $project->getParams();
			if( isset($projectParams->default_pdf_footer)) {
				$pageFooter = $projectParams->default_pdf_footer;
			}
		}
		 
		$footerWidth        = $this->getPageWidth() - $this->original_rMargin - $this->original_lMargin;	
		$current_y          = $this->y;
		$line_width         = 0.85 / $this->k;
		
		$this->setFillColor( 525 , 255 , 255 );
        $this->setTextColor( 0 , 0 , 0 );
        $this->SetFont("helvetica" , "" ,9);		
		$this->SetY( $current_y + 10 );				
		$this->SetLineStyle(array('width'=>$line_width, 'cap'=>'butt', 'join'=>'miter','dash'=>0, 'color'  =>  array(0, 0, 0)));
		
		if(empty($this->pagegroups)) {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();		
		} else {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
		}		
		if(!empty($pageFooter)) {
			$footerContent   = $pageFooter;			
			$footerHtmlTxt     = ($this->printPageNum)?preg_replace("/\[(PAGE_NUM|NUM_PAGE|PAGINATION)\]/", "<strong><u>".$pagenumtxt."</u></strong>",$footerContent) : preg_replace("/\[(PAGE_NUM|NUM_PAGE|PAGINATION)\]/","", $footerContent);
			if(!$this->printPageNum) {
				preg_match("{(?<footerTAG><td(.*)>(.*)<\/td>)(?<paginationTAG2><td(.*)>(.*)<\/td>)}",trim(preg_replace('/\s\s+/',' ',str_replace(array("\n\r","\n","\r"),"",$footerHtmlTxt))),$matches);
				if( isset($matches["footerTAG"])) {
					$footerHtmlTxt="<table style=\"width:100%\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\"><tr>";
					$footerHtmlTxt.= "   <td style=\"text-align:center;width:100%\" align=\"center\" width=\"100%\">".strip_tags($matches["footerTAG"])."</td>";						
				    $footerHtmlTxt.= "</tr></table>";
				}
			}
			if( $this->getRTL()) {
			    $this->SetX($this->original_rMargin);
				if( $this->printPageNum  ) {
					$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
				}
			} else {
				$this->SetX($this->original_lMargin);
				if( $this->printPageNum  ) {
					$this->Cell(0, 0, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
				}
			}
			//$this->writeHTMLCell( $footerWidth,10, "","", $footerHtmlTxt,  0, 0, true, "center", true);		
            $this->MultiCell($footerWidth,10,$footerHtmlTxt,0,"center",0,0,"","", true, 0 ,true, true, 10 , "M" );			
		} else {
			$entreprise= Sirah_Fabric::getEntreprise();
			$libelle   = ($entreprise) ?  $entreprise->libelle   : "";
		    $telephone = ($entreprise) ?  sprintf("%s / %s", $entreprise->phone1, $entreprise->phone2)     : "";
		    $adresse   = ($entreprise) ?  $entreprise->address   : "";
		    $email     = ($entreprise) ?  $entreprise->email     : "";
		    $fax       = ($entreprise) ?  $entreprise->fax       : "";
		    $siteweb   = ($entreprise) ?  $entreprise->siteweb   : "";
		    $ifu       = ($entreprise) ?  $entreprise->reference : "";
		    $capital   = ($entreprise) ?  number_format($entreprise->capital , 0 , " "," ") : "";			
			$txt       = $adresse." Tel: ".$telephone." Email: <u>".$email."</u>  Site Web : <b>".$siteweb."</b> SA au capital de ".$capital." F CFA IFU : ".$ifu." - ";		
		    $textWidth = $this->getPageWidth() - $this->original_rMargin - $this->original_lMargin-15;		
			
			if( $this->getRTL()) {
			    $this->SetX($this->original_rMargin);
			    $this->MultiCell( $footerWidth, 5,$txt,"T","C",false,0,'','',true,0,true);
			    $this->Cell(0, 0, $pagenumtxt , 'LT', 0, 'L');
		    } else {
			    $this->SetX($this->original_lMargin);
			    $this->MultiCell( $footerWidth  , 5  ,  $txt  ,  "T"  ,  "C"  , false , 0 , '' , '' , true , 0 , true);
			    $this->Cell(0, 0, $pagenumtxt, 'LT', 0, 'R');
		    }
		}		
	}
	
	public function Header()
	{
		if(!$this->print_header ) {
			return;
		}
		$modelProject       = new Model_Project();
		$appConfigSession   = new Zend_Session_Namespace("AppConfig");
		$projectid          = (isset($appConfigSession->project->projectid) && intval($appConfigSession->project->projectid))?$appConfigSession->project->projectid : 1;
		$project            = (intval($projectid))?$modelProject->findRow($projectid,"projectid",null,false) : null;
		$pageHeader         = (defined("DEFAULT_PDF_HEADER"))?DEFAULT_PDF_HEADER : "";
		if( $project ) {
			$projectParams  = $project->getParams();
			if( isset($projectParams->default_pdf_header)) {
				$pageHeader = $projectParams->default_pdf_header;
			}
		}
		if( empty($pageHeader)) {
			return;
		}
		$margins           = $this->getMargins();
        $contenuWidth      = $this->getPageWidth()-$margins["left"]-$margins["right"];
		$enteteContent     = stripslashes($pageHeader);

		$this->setFillColor( 525 , 255 , 255 );
        $this->setTextColor( 0 , 0 , 0 );
		$this->writeHTMLCell($contenuWidth, 0, "","", $enteteContent ,  0, 0, true, "top", true);						
		$this->Ln();
		$this->Cell($contenuWidth, 0, '', 'T', 0, 'C');
		$this->endTemplate();	
		
	
	}
		 
		 

 }



