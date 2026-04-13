// JavaScript Document
iLigneNum=0;
(function($){ 
$.fn.extend({
addRow:function(aoParams){
	iLigneNum++;
	var table=this;
	var aoDefaultParams={
		 "sId":"ligne"+iLigneNum,
		 "aoAttribs":[],
		 "aoCols":[]		
	};	
   var aoSettings= $.extend(aoDefaultParams,aoParams);
   var oRow=$('<tr></tr>').attr('id',aoSettings.sId);

   //On recupere la class de la premiere ligne
   var firstRow=table.children("tbody").eq(0).children('tr').eq(0);
   var firstClass=firstRow.attr('class');
   switch(firstClass){

      case 'odd':
                 oRow.addClass('even');
                 break;
     case 'even':
     default:
              oRow.addClass('odd');
       

    }
   oRow.css({'background-color':'blue','color':'white'});
   $.each(aoSettings.aoAttribs,function(sKey,sValue){								  
					oRow.attr(sKey,sValue);	 
                     });
   $.each(aoSettings.aoCols,function(){
	var oCol=$('<td></td>');
	oCol.append(this.oContent);
        if(this.aAppends){
            $.each(this.aAppends,function(){
                var oDomEl=document.createElement(this.sTag);
                if(this.sTagType){
                     oDomEl.type=this.sTagType;
                 }
                oDomEl.appendChild(document.createTextNode(this.sContent));
                
                if(this.aoAttribs){
                   $.each(this.aoAttribs,function(sKey,sValue){								  
					oDomEl.setAttribute(sKey,sValue);	 
                     });
                }
                var oDomCol=oCol[0];
                oDomCol.appendChild(oDomEl);
            });
         }
	
	oRow.append(oCol);										   
	});
        $('tr').remove("#"+aoSettings.sId);
        $('.dataTables_empty').remove();
	table.children('tbody').prepend(oRow);
                 }
	});
})(jQuery)
