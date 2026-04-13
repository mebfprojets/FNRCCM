<?php


class ProjectPdf_Default extends Sirah_Pdf_Default 
{
	
	 
	
	public function Footer()
	{
		if( !$this->print_footer ) {
			return;
		}
		
		$footerWidth    = $this->getPageWidth() - $this->original_rMargin - $this->original_lMargin-15;	
		$current_y      = $this->y;
		$line_width     = 0.85 / $this->k;
		
		$this->setFillColor( 525 , 255 , 255 );
        $this->setTextColor( 0 , 0 , 0 );
        $this->SetFont("helvetica" , "" , 11 );		
		$this->SetY( $current_y + 10 );				
		$this->SetLineStyle(array('width'  =>  $line_width, 'cap'  =>  'butt', 'join'  =>  'miter', 'dash'  =>  0, 'color'  =>  array(0, 0, 0)));
		
		if(empty($this->pagegroups)) {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();		
		} else {
			$pagenumtxt = $this->l['w_page'] . ' ' . $this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
		}		
		if( defined("DEFAULT_PDF_FOOTER")) {
			$footerContent   = DEFAULT_PDF_FOOTER;
			if( empty( $footerContent )) {
				return;	
			}				
			$footerHtmlTxt   = preg_replace("/\[PAGE_NUM|NUM_PAGE|PAGINATION\]/", "<strong><u>".$pagenumtxt."</u></strong>", $footerContent);
			if( $this->getRTL()) {
			    $this->SetX($this->original_rMargin);
			} else {
				$this->SetX($this->original_lMargin);
			}
			$this->writeHTMLCell( $footerWidth, 0, "","", $footerHtmlTxt,  0, 0, true, "bottom", true);			
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
			
			if($this->getRTL()) {
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
		if(!defined("DEFAULT_PDF_HEADER")) {
			return;
		}
		$margins       = $this->getMargins();
        $contenuWidth  = $this->getPageWidth()-$margins["left"]-$margins["right"];
		$enteteContent = stripslashes(DEFAULT_PDF_HEADER);

		$this->setFillColor( 525 , 255 , 255 );
        $this->setTextColor( 0 , 0 , 0 );
		$this->writeHTMLCell($contenuWidth, 0, "","", $enteteContent ,  0, 0, true, "top", true);						
		$this->Ln();
		//$this->Cell($contenuWidth, 0, '', 'T', 0, 'C');
		$this->endTemplate();	
		
	
	}
		 
		 

 }



