var $ = jQuery.noConflict();

jQuery(document).ready(function(){

	jQuery('#edit_subscriber').hide();
	
	jQuery( ".edit_this_subscriber" ).click(function() {
		jQuery('#edit_subscriber').show();
		
		var id = jQuery(this).attr('id');
		jQuery('#edit_id').val(id);
		
		var name = jQuery(this).attr('name');
		jQuery('#edit_name').val(name);
		
		var email = jQuery(this).attr('email');
		jQuery('#edit_email').val(email);
		
		var category = jQuery(this).attr('category');//comma separated ids
		jQuery('#edit_category').val('');
		var category_array = $.csv.toArray(category);
		jQuery.each(category_array, function( index, value ) {
			jQuery('#edit_category option[value=' + value + ']').attr('selected', true);
		});
	});
	
	
	
});