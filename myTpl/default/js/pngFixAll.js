/* ############## Configuration ############## */

// Chemin complet sans le nom de domaine de la page HTML vers les images appel?es en CSS
var ImgsPath = "";

// Gestion des exceptions
var Exceptions = new Array();
Exceptions[0] = "image-sans-transparence.png";
Exceptions[1] = "image-autre.png";
/*
Exceptions[2] = "";
etc...
*/

/* ############## Fin de Configuration ############## */

// Execution au chargement de la page
window.onload = function() {
	PngFixImg();
	PngFixBkground();
} 

// Mise en transparence des <img /> PNG
function PngFixImg() {
	var arVersion = navigator.appVersion.split("MSIE")
	var version = parseFloat(arVersion[1])
	
	if ((version >= 5.5) && (document.body.filters)) 
	{
	   for(var i=0; i<document.images.length; i++)
	   {
		  var img = document.images[i]
		  var imgName = img.src.toUpperCase()
		  if (imgName.substring(imgName.length-3, imgName.length) == "PNG")
		  {
			 var imgID = (img.id) ? "id='" + img.id + "' " : ""
			 var imgClass = (img.className) ? "class='" + img.className + "' " : ""
			 var imgTitle = (img.title) ? "title='" + img.title + "' " : "title='" + img.alt + "' "
			 var imgStyle = "display:inline-block;" + img.style.cssText 
			 if (img.align == "left") imgStyle = "float:left;" + imgStyle
			 if (img.align == "right") imgStyle = "float:right;" + imgStyle
			 if (img.parentElement.href) imgStyle = "cursor:hand;" + imgStyle
			 var strNewHTML = "<span " + imgID + imgClass + imgTitle
			 + " style=\"" + "width:" + img.width + "px; height:" + img.height + "px;" + imgStyle + ";"
			 + "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
			 + "(src=\'" + img.src + "\', sizingMethod='scale');\"></span>" 
			 img.outerHTML = strNewHTML
			 i = i-1
		  }
		  //if
	   }//for
	}//if
}//function



// Mise en transparence des images PNG en background CSS
function PngFixBkground() {
	
	// Tableau des feuilles de styles
	var StyleSheets = document.styleSheets;
	
	// Boucle sur les feuilles de styles
	for(i=0; i<StyleSheets.length; i++)
		{
		// Si il s'agit d'Internet Explorer
		if(StyleSheets[i].rules)
			{
			Rules = StyleSheets[i].rules;
			
			// Boucle sur les r?gles de la feuille de style
			for(j=0; j<Rules.length; j++)
				{
				// Si la r?gle contient une propri?t? "background"
				if(
				   (Rules[j].style.background) ||
				   (Rules[j].style.backgroundImage) ||
				   (Rules[j].style.backgroundRepeat)
				  )
					{
					// R?cup?ration des r?gles
					if(Rules[j].style.background)
						{
						BkgroundImg = Rules[j].style.background.match('[a-z0-9_-]*\.png');
						BkgroundRepeat = Rules[j].style.background.match('repeat|repeat\-x|repeat\-y|no\-repeat');
						}
					else
						{
						BkgroundImg = Rules[j].style.backgroundImage.match('[a-z0-9_-]*\.png');
						BkgroundRepeat = Rules[j].style.backgroundRepeat;
						}
					// Prise en compte des exceptions
					var regex = new RegExp(BkgroundImg, 'g');
					var yatil = regex.test(Exceptions);
					
					// Si l'image de fond est un PNG
					if(
					   	(BkgroundImg != null) &&
						(yatil == false)
					  )
						{
						// D?termination du sizingMethod suivant la m?thode de rep?tition de l'image
						if(BkgroundRepeat != null)
							{
							// Cas "Etirer"
							if(
								(BkgroundRepeat == "repeat") 	||
								(BkgroundRepeat == "repeat-x") 	||
								(BkgroundRepeat == "repeat-y")
							  )
								{sizingMethod = 'scale';}
							// Cas "Rogner"
							else
								{sizingMethod = 'crop';}							
							}
						
						// Retrait de l'image de fond
						Rules[j].style.backgroundImage = "none";
						
						// Application du filtre
						Rules[j].style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + ImgsPath + BkgroundImg + "', sizingMethod='" + sizingMethod + "')";
						
						} // if
						
					} // if
					
				} // for
				
			} // if
			
		} // for
		
}