jQuery(document).ready( function() {

	/*jQuery("#api_connect").click( function(e){
		//e.preventDefault(); 
		nonce = jQuery(this).attr('data-nonce');//alert(nonce);
	 	jQuery.ajax({
        	type : "get",	
			url : myAjax.ajaxurl,
         	data : {action: "connect_google_api", nonce: nonce},
         	success: function(response) {
               jQuery("#response").html(response);
            }
      });   
	});*/
	
   jQuery("#btnAddEvent").click( function() {
      data = jQuery("#add_event").serialize();//alert(data);
      nonce = jQuery("#data-nonce").val();//alert(nonce);

      jQuery.ajax({
         type : "post",
         //dataType : "json",
         url : myAjax.ajaxurl,
         data : {action: "add_new_event", data : data, nonce: nonce},
         success: function(response) {
               jQuery("#response").html(response);
            }
      });   
   });
});