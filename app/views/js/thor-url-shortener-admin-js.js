/*!
 * Thor URL Shortener Admin
 * Licensed under the MIT license
 */
jQuery(document).ready(function(){
  jQuery(document).on('change',".Thor_theme-trigger",function(){
  	var c=jQuery(this).val();
  	jQuery("#Thor_widget_title").text(jQuery('.Thor_theme-trigger :selected').text());
    jQuery("#Thor_demo").find("#Thor_main").removeClass();
    jQuery("#Thor_demo").find("#Thor_main").addClass(c+"-c");
    if(c=="cc"){
    	jQuery(".Thor_custom_message").fadeIn();
    }else{
    	jQuery("#Thor_custom_message").hide();
    }
    jQuery('body,html').animate({ scrollTop: 0 });    	
  });     
  jQuery("#quick_url_shorten_admin_button").click(function(e){
    e.preventDefault();
    var c=jQuery("#quick_url_shorten_admin_form");
    var appurl=c.attr("data-url");
    var url=c.find("#quick_url_shorten_admin_input");

    jQuery.getJSON(appurl+"api?callback=?",
      {
        api: c.find("#quick_url_shorten_admin_api").val(),
        url: url.val(),
        custom: c.find("#quick_url_shorten_admin_custom").val()
      },
      function(html) {
        if(html.error){
          c.find('#quick_url_shorten_admin_message').hide().html('<div id="message" class="error">'+html.msg+'</div>').fadeIn('slow');               
        }else{          
          c.find('#quick_url_shorten_admin_message').hide();
          url.val(html.short);  
          url.select();                      
        }     
    });        
  });
});