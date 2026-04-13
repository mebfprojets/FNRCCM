(function($){

$.fn.loading=function(){

     var container=this;
     var baseUri=getBaseUrl();
     var loaderSrc=baseUri+"/icones/ajax-loader.gif";         
      var loader=$("<img > </img>");
      loader.attr("align","center");
      loader.attr('src',loaderSrc);
      loader.position({
        my:'center',
        at:'center', 
        of:messageBox               
       });
    container.html(loader);
      

  };
})(jQuery)
