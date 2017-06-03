/*
 * ====================================================================================
 * ----------------------------------------------------------------------------------
 *  Version 1.0
 * ----------------------------------------------------------------------------------
 * ====================================================================================
 */ 
var getLocation = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l;
}; 
(function($){
    // jShorten Method
    $.fn.extend({ 
        shorten: function(settings) {
            var defaults = {
              url:null,
              key:null,
              internal: false
            };
            var s = $.extend(defaults, settings);
            var error=0;

            if(s.url===null){
              console.log('Please set the url to the api of the url shortener script.');
                error=1;
            }
            if(s.key===null){
                console.log('Please set your API key.');
                error=1;              
            }
            if(error==0){  
                $(this).each(function(){
                  var e=$(this);
                  var l = getLocation(e.attr("href"));
                  if(l.hostname!=location.hostname){
                    $.getJSON(s.url+"api?callback=?",
                      {
                        api: s.key,
                        url: e.attr("href")
                      },
                      function(r) {
                       if(r.error=='0'){
                          e.attr('href',r.short);
                       }else{
                          console.log(r.msg);
                       }      
                    });               
                  }
                });
            }                          
        }
    });
    $(document).ready(function(){
      // Shorten Ajax
      $(document).on('submit',"#Thor_main form#Thor_form",function(e) {
        e.preventDefault();
        var form=$(this);
        var main=$(this).parent("#Thor_main");
        var current=$(".current-container");
        var url=form.find("#Thor_url");
        var appurl=$(this).attr("action");
        var share_text=$("#Thor_share_text").val();
        

        if(!url.val()){
          main.find('#Thor_message').hide().html('Please enter a valid URL (including http:&#47;&#47;)').fadeIn('slow');
          main.find('#Thor_message').addClass("Thor_error");
        }else{
           main.find('#Thor_loading').fadeIn();
          $.getJSON(appurl+"api?callback=?",
            {
              api: main.find("#Thor_token").val(),
              url: url.val(),
              custom: main.find("#Thor_custom_input").val()
            },
            function(html) {
              var share_html="<a href='https://twitter.com/share?url="+encodeURIComponent(html.short)+"&text="+encodeURI(share_text).replace(/%20/g,'+')+"' target='_blank'>Tweet</a> <a href='https://www.facebook.com/sharer.php?u="+html.short+"' target='_blank'>Share</a>";              
              main.find('#Thor_loading').fadeOut();
              if(html.error){
                main.find('#Thor_message').hide().html(html.msg).fadeIn('slow');               
                main.find('#Thor_message').addClass("Thor_error");
              }else{          
                main.find('#Thor_message').hide();
                main.find("#Thor_custom_container").html(share_html);
                url.val(html.short);  
                url.select();                      
              }     
          });                     
        }
      });   
      // Fix Widget size
      var w=$(".shortener_widget #Thor_main").width();
      if(w<450) $(".shortener_widget #Thor_main").addClass("widget_fix");
    });
})(jQuery);