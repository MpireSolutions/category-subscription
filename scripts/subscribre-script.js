jQuery(document).ready(function($){
      $("#searchsubscribers").keyup(function(){
        var _searchsubscribers = $("#searchsubscribers").val();
            if( _searchsubscribers != '' ){
                $.post(
                STsubscribres.ajaxurl,
                { 
                    action   : 'search_subscribers_in_admin',
                    searchsubscribers : _searchsubscribers
                },
                function( response ){
                     $("#subscribersresult").html(response);
                });
            }else{
                $("#subscribersresult").html("");
            }
    	return false;
      });
        $('#searchsubscribers').keyup(function(e){
            if(e.keyCode == 27) {
                $(this).val('');
            }
        });
});