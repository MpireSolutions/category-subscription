<table class="wp-list-table widefat fixed striped st-category-email-subscribe_page_category_email_subscriber">
    <thead>
            <tr>
            <th scope="col" id="id" class="manage-column column-id" style="display:none;">ID</th>
            <th scope="col" id="name" class="manage-column column-name sorted desc" style="">
            <a href="<?php echo admin_url(); ?>admin.php?page=category_email_subscriber&amp;orderby=name&amp;order=asc">
            <span>Name</span><span class="sorting-indicator"></span>
            </a>
            </th>
            <th scope="col" id="email" class="manage-column column-email sortable asc" style="">
            <a href="<?php echo admin_url(); ?>admin.php?page=category_email_subscriber&amp;orderby=email&amp;order=desc">
            <span>Email</span><span class="sorting-indicator"></span>
            </a>
            </th>
            <th scope="col" id="category" class="manage-column column-category" style="">Categories</th>
            <th scope="col" id="actions" class="manage-column column-actions" style="">Action</th>
            </tr>
    </thead>
    <tbody id="the-list">
            <?php
            foreach ( $data as $value ){
            //if( $sub_search === $value['name'] || $sub_search === $value['email']){ ?>
            <tr>
            <td class="id column-id" style="display:none;">1</td>
            <td class="name column-name"><?php echo $value['name']; ?></td>
            <td class="email column-email"><?php echo $value['email']; ?></td>
            <td class="category column-category"><?php echo $value['category']; ?></td>
            <td class="actions column-actions"><?php echo $value['actions']; ?></td>
            </tr>
            <?php } //} ?>
    </tbody>   
    <tfoot>
            <tr>
            <th scope="col" id="id" class="manage-column column-id" style="display:none;">ID</th>
            <th scope="col" id="name" class="manage-column column-name sorted desc" style="">
            <a href="<?php echo admin_url(); ?>admin.php?page=category_email_subscriber&amp;orderby=name&amp;order=asc">
            <span>Name</span><span class="sorting-indicator"></span>
            </a>
            </th>
            <th scope="col" id="email" class="manage-column column-email sortable asc" style="">
            <a href="<?php echo admin_url(); ?>admin.php?page=category_email_subscriber&amp;orderby=email&amp;order=desc">
            <span>Email</span><span class="sorting-indicator"></span>
            </a>
            </th>
            <th scope="col" id="category" class="manage-column column-category" style="">Categories</th>
            <th scope="col" id="actions" class="manage-column column-actions" style="">Action</th>
            </tr>
    </tfoot>        
</table>
<?php echo '<pre>'; ?>
<script>
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
</script>