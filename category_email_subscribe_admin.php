<?php

global $email_table_suffix;
$email_table_suffix = "category_email";

function category_email_admin_menu() {
    add_menu_page('Category Email Subscribe', 'Category Email Subscribe', 'manage_options', 'category_email_subscribe', 'category_email_subscribe_settings_page', plugins_url('category-email-subscribe/images/icon.png'));
	add_submenu_page('category_email_subscribe', 'Settings', 'Settings', 'manage_options', 'category_email_subscribe', 'category_email_subscribe_settings_page');
	add_submenu_page('category_email_subscribe', 'Subscribers', 'Subscribers', 'manage_options', 'category_email_subscriber', 'category_email_subscribe_subscribers_page');
}

add_action('admin_menu', 'category_email_admin_menu');
add_action('init', 'category_email_subscribe_export_csv');

function category_email_subscribe_check_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    
    return $data;
}

function category_email_subscribe_export_csv(){
	if(isset($_REQUEST['ExportCSV']))
	{
		global $wpdb;
		global $email_table_suffix;	
		
		$getTable = $wpdb->prefix . $email_table_suffix;
		echo category_email_subscribe_generate($getTable);
		exit;
	}
}

function category_email_subscribe_generate($getTable){
	ob_clean();
	
	$field='';
	$getField ='';
	global $wpdb;
	
	
	if($getTable){
		$result = $wpdb->get_results("SELECT * FROM $getTable");
		$requestedTable = mysql_query("SELECT * FROM ".$getTable);
		$fieldsCount = mysql_num_fields($requestedTable);
		
		for($i=0; $i<$fieldsCount; $i++){
			$field = mysql_fetch_field($requestedTable);
			$field = (object) $field;         
			$getField .= $field->name.',';
		}

		$sub = substr_replace($getField, '', -1);
		$fields = $sub; # GET FIELDS NAME
		$each_field = explode(',', $sub);		
		$csv_file_name = $getTable.'_'.date('Ymd_His').'.csv'; # CSV FILE NAME WILL BE table_name_yyyymmdd_hhmmss.csv
		
		# GET FIELDS VALUES WITH LAST COMMA EXCLUDED
		foreach($result as $row){
			for($j = 0; $j < $fieldsCount; $j++){
				if($j == 0) $fields .= "\n"; # FORCE NEW LINE IF LOOP COMPLETE
				$value = str_replace(array("\n", "\n\r", "\r\n", "\r"), "\t", $row->$each_field[$j]); # REPLACE NEW LINE WITH TAB
				$value = str_getcsv ( $value , ",", "\"" , "\\"); # SEQUENCING DATA IN CSV FORMAT, REQUIRED PHP >= 5.3.0
				$fields .= $value[0].','; # SEPARATING FIELDS WITH COMMA
			}			
			$fields = substr_replace($fields, '', -1); # REMOVE EXTRA SPACE AT STRING END
		}
		
		header("Content-type: text/x-csv"); # DECLARING FILE TYPE
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=".$csv_file_name); # EXPORT GENERATED CSV FILE
		header("Pragma: no-cache");
		header("Expires: 0");

		return $fields;
  }
}
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Subscribers_Table extends WP_List_Table
{
	/* Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
		usort( $data, array( &$this, 'sort_data' ) );

		$perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
		
		$this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
		
		$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
		
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
	/**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'          	=> 'ID',
			'name'  		=> 'Name',
            'email'  	=> 'Email',
			'category'  	=> 'Categories',
            'actions'      	=> 'Action'
        );
		
		return $columns;
    }
	/**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array('id');
    }
	/**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('name' 		=> array('name', true),
					 'email' 	=> array('email', true),
					);
    }
	/**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
		$this_page = $_SERVER['PHP_SELF']."?page=category_email_subscriber";
		
		global $wpdb;	
		global $email_table_suffix;	
	
		$email_table = $wpdb->prefix . $email_table_suffix;

        $data = array();

		$sql = "SELECT * FROM ".$email_table;
		$subscribers = $wpdb->get_results($sql);
		
		foreach ( $subscribers as $subscriber ) {
			$list_category= "";
			if($subscriber->category == 0){
				$category = "All Categories";
			}else{
				
				$categories = explode(",",$subscriber->category);
				foreach($categories as $category){
					$list_category .= get_cat_name($category)." ";
				}
				
			}
			$data[] = array(
                    'id'          => $subscriber->id,
                    'name'	  => $subscriber->name,
                    'email'    => $subscriber->email,
                    'category' => $list_category,
                    'actions' 	  => "<a href='#' id='".$subscriber->id."' name='".$subscriber->name."' email='".$subscriber->email."' category='".$subscriber->category."' class='edit_this_subscriber'>Edit</a> | <a href='".$this_page."&action=delete&delete_id=".$subscriber->id."'>Delete</a>"
               );
			}
        return $data;
    }
	// Used to display the value of the id column
	public function column_id($item)
	{
		return $item['id'];
	}
	
	/**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
			case 'name':
            case 'email':
			case 'category':
            case 'actions':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }
	/**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'member';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }

        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
function category_email_subscribe_settings_page() {

	$send_email = get_option( 'category_email_send_email' );
	$from_name = get_option( 'category_email_from_name' );
	$mandrill_api = get_option( 'category_email_mandrill_api' );
	$email_template = get_option( 'category_email_template' );
	if (isset($_POST['save_send_email'])) {
		$send_email = $_POST['send_email'];
		update_option( 'category_email_send_email', $send_email );
		$from_name = $_POST['from_name'];
		update_option( 'category_email_from_name', $from_name );
		$api = $_POST['mandrill_api'];
		update_option( 'category_email_mandrill_api', $api );
		$email_template = $_POST['email_template'];
		update_option( 'category_email_template', $email_template );
	}
	
	
	?>
		<div class="wrap">  
			<h2><?php _e('Category Email Subscribe Plugin', 'stemail')?></h2>
			<div class="postbox-container" style="width:70%;padding-right:25px;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Settings', 'stemail')?></span></h3>
							<div class="inside">
								<form id="settings" method="POST">
									<div>
										<label for="send_email"><?php _e('Send Email From (Email): ', 'stemail')?></label>
										<input class="regular-text" type="text" name="send_email" value="<?php echo $send_email; ?>"/>
									</div>
									<div>
										<label for="from_name"><?php _e('Send Email From (Name): ', 'stemail')?></label>
										<input class="regular-text" type="text" name="from_name" value="<?php echo $from_name; ?>"/>
									</div>	
									<div>
										<label for="from_name"><?php _e('Mandrill Email API Key: ', 'stemail')?></label>
										<input class="regular-text" type="text" name="mandrill_api" value="<?php echo $mandrill_api; ?>"/>
									</div>	
									<div>
										<label for="from_name"><?php _e('Email Template: ', 'stemail')?></label>
                                                                                <?php echo wp_editor( stripslashes($email_template), $editor_id = 0, $settings = array('textarea_name'=> 'email_template') );?>
                                                                                <p>Email Template Tokens: %post_title%, %author_name%, %post_date%, %featured_image%, %post_content1%</p>
									</div>	
									<input class="button-primary" type="submit" name="save_send_email" value="Save" />
								</form>
							</div>
						</div>
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('How to Use', 'stemail')?></span></h3>
							<div class="inside">
							<strong><?php _e('1. Enter the Send Email from Email and Name', 'stemail')?></strong><br/>
							<?php _e('All emails will be sent from this name and email', 'stemail')?><br/>
							<strong><?php _e('2. Add Subscribers', 'stemail')?></strong><br/>
							<?php _e('Go to St Category Emai Subscribe > Subscribers to Manage Subscribers', 'stemail')?><br/>
							<?php _e('You can manually add a subscriber', 'stemail')?><br/>
							<?php _e('Or Import an entire list', 'stemail')?><br/>
							<?php _e('The emails will be sent as soon as a ', 'stemail')?><strong><?php _e('Post is Published', 'stemail')?></strong><br/>
							<?php _e('The email will be sent only to the subscribers registered for the category of Post', 'stemail')?><br/>
							<strong><?php _e('3. Add Subscribe Form', 'stemail')?></strong><br/>
							<?php _e('Place the subscribe form on your website using ', 'stemail')?><strong><?php _e('Widget : Category Email Subscribe Form', 'stemail')?></strong>
							<?php _e('Or Short code [category_subscribe_form]', 'stemail')?>
							</div>
						</div>
					</div>
				</div>
            </div>		
			<div class="postbox-container side" style="width:20%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Show your Support', 'stemail')?></span></h3>
							<div class="inside">
								<p>
								<strong><?php _e('Want to help make this plugin even better? All donations are used to improve this plugin, so donate now!', 'stemail')?></strong>
								</p>
								<a href="http://sanskrutitech.in/wordpress-plugins/wordpress-plugins-daily-tip/"><?php _e('Donate', 'stemail')?></a>
							</div>
						</div>
						<div id="toc" class="postbox">
						<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Connect With Us ', 'stemail')?></span></h3>
							<div class="inside">
								<a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
								<a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
								<a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
								<a class="website" href="http://sanskrutitech.in/"></a>
								<a class="email" href="mailto:info@sanskrutitech.in"></a>
							</div>
						</div>
					</div>
				</div>
			</div>			
		</div>
	<?php
}

function email_get_abs_path_from_src_file($src_file)
{
	if(preg_match("/http/",$src_file))
	{
		$path = parse_url($src_file, PHP_URL_PATH);
		$abs_path = $_SERVER['DOCUMENT_ROOT'].$path;
		$abs_path = realpath($abs_path);
		if(empty($abs_path)){
			$wpurl = get_bloginfo('wpurl');
			$abs_path = str_replace($wpurl,ABSPATH,$src_file);
			$abs_path = realpath($abs_path);			
		}
	}
	else
	{
		$relative_path = $src_file;
		$abs_path = realpath($relative_path);
	}
	return $abs_path;
}
function email_read_dump($src_file,$email_table,$column_string="",$start_row=2)
{
	ini_set('auto_detect_line_endings', true);
	global $wpdb;
	$errorMsg = "";
	if(empty($src_file))
	{
            $errorMsg .= "<br />" . _e('Input file is not specified', 'stemail');
            return $errorMsg;
    }
	
	$file_path = email_get_abs_path_from_src_file($src_file);	
	
	$file_handle = fopen($file_path, "r");
	if ($file_handle === FALSE) {
		// File could not be opened...
		$errorMsg .= _e('Source file could not be opened!', 'stemail') . '<br />';
		$errorMsg .= _e('Error on fopen', 'stemail') .  "('$file_path')";	// Catch any fopen() problems.
		return $errorMsg;
	}
	
	$row = 1;
	while (!feof($file_handle) ) 
	{
		$line_of_text = fgetcsv($file_handle, 1024);
		if ($row < $start_row)
		{
			// Skip until we hit the row that we want to read from.
			$row++;
			continue;
		}
		$columns = count($line_of_text);
		
		if ($columns>1)
		{
	        	$query_vals = "'".esc_sql($line_of_text[0])."'";
	        	for($c=1;$c<$columns;$c++)
	        	{
					$line_of_text[$c] = utf8_encode($line_of_text[$c]);
					$line_of_text[$c] = addslashes($line_of_text[$c]);
	                $query_vals .= ",'".esc_sql($line_of_text[$c])."'";
					
	        	}
	        	        $query = "INSERT INTO $email_table ($column_string) VALUES ($query_vals)";
						
                        $results = $wpdb->query($query);
                        if(empty($results))
                        {
                            $errorMsg .= "<br />" . _e('Insert into the Database failed for the following Query: ', 'stemail') . " <br />";
                            $errorMsg .= $query;
                        }
	    }
		$row++;
	}
	fclose($file_handle);
	
	return $errorMsg;
}

function category_email_subscribe_subscribers_page() {
    global $wpdb;
    global $email_table_suffix;

    $email_table = $wpdb->prefix . $email_table_suffix;
    $column_string = "name,email,category";

    if (isset($_REQUEST['UploadFile'])) {
        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'];

        $tmp_name = $_FILES["csvfile"]["tmp_name"];
        $name = $_FILES["csvfile"]["name"];

        if (move_uploaded_file($tmp_name, "$target_path/$name")) {
            $file_name = $target_path . "/" . $name;
            $errorMsg = email_read_dump($file_name, $email_table, $column_string);

            if (empty($errorMsg)) {
                echo '<div id="message" class="updated fade"><p><strong>';
                echo _e('File content has been successfully imported into the database!', 'stemail');
                echo '</strong></p></div>';
            } else {
                echo '<div id="message" class="error"><p><strong>';
                echo _e('Error occured while trying to import!', 'stemail') . "<br />";
                echo $errorMsg;
                echo '</strong></p></div>';
            }
        } else {
            echo '<div id="message" class="error"><p><strong>';
            echo _e('There was an error uploading the file, please try again!', 'stemail');
            echo '</strong></p></div>';
        }
    }

    //Store the Data input if data is submitted
    if (isset($_REQUEST['Subscribe'])) {
        $sub_name = category_email_subscribe_check_input($_REQUEST["sub_name"]);
        $sub_email = category_email_subscribe_check_input($_REQUEST["sub_email"]);
        $category = $_REQUEST["category"];
        $category = implode(",", $category);

        //Insert
        $rows_affected = $wpdb->insert($email_table, array('name' => $sub_name, 'email' => $sub_email, 'category' => $category));
        echo "<div id=\"message\" class=\"updated fade\"><p><strong>Subscriber Added Successfully!</strong></p></div>";
    }
    if (isset($_REQUEST['action'])) {
        if (isset($_REQUEST['delete_id'])) {
            $id = $_REQUEST['delete_id'];

            $row_deleted = $wpdb->delete($email_table, array('id' => $id));
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>$row_deleted Email(s) Deleted Successfully!</strong></p></div>";
        }
    }
    if (isset($_REQUEST['Save'])) {
        $edit_id = $_REQUEST['edit_id'];
        $edit_name = $_REQUEST['edit_name'];
        $edit_email = $_REQUEST['edit_email'];
        $edit_category = "";
        if (isset($_REQUEST['edit_category'])) {
            $edit_category = $_REQUEST['edit_category'];
            $edit_category = implode(",", $edit_category);
        }

        $row_updated = $wpdb->update($email_table, array('name' => $edit_name, 'email' => $edit_email, 'category' => $edit_category), array('id' => $edit_id));
        if ($row_updated >= 1) {
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>$row_updated Subscribers(s) Updated Successfully!</strong></p></div>";
        } else {
            echo "<div id=\"message\" class=\"error\"><p><strong>Could not Update Subscriber due to some error</strong></p></div>";
        }
    }
    ?>
    <div class="wrap">  
        <h2><?php _e('Category Email Subscribe Plugin','stemail')?></h2>
        <div class="postbox-container" style="width:70%;padding-right:25px;">
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Search Subscribers','stemail')?></span></h3>
                        <input type="hidden" id="_wpnonce" name="_wpnonce" value="e8b27d6e53" />
                        <input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/admin.php?page=category_email_subscriber" />
                        <div class="inside">
                            <div class="search-sub">
                                <input type="text" id="searchsubscribers" name="searchsubscribers" placeholder="<?php _e('Find Subscribers Here', 'createplugin_context'); ?>"/>
                                <div id="subscribersresult" class="subscribersresult"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Subscribers','stemail')?></span></h3>
                        <div class="inside">
                            <!--input type="submit" name="ExportCSV" value="Export to CSV" id="btnExport" class="button" /-->
                            <?php
                                $Subscribers = new Subscribers_Table();
                                $Subscribers->prepare_items();
                                $Subscribers->display();
                            ?>
                        </div>
                    </div>
                </div>
                <div class="meta-box-sortables" id="edit_subscriber">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Edit Subscriber','stemail')?></span></h3>
                        <div class="inside">
                            <a name="edit_a_subscriber"></a>
                            <form id="edit_a_subscriber" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=category_email_subscriber"; ?>" method="post">
                                <input name="edit_id" id="edit_id" type="hidden" value=""/>
                                <div>
                                    <label><?php _e('Name','stemail')?></label>
                                    <input name="edit_name" id="edit_name" class="regular-text" value=""/>
                                </div>
                                <div>
                                    <label><?php _e('Email','stemail')?></label>
                                    <input name="edit_email" id="edit_email" class="regular-text" value=""/>
                                </div>
                                <div>
                                    <label for="edit_category"><?php _e('Category','stemail')?></label>
                                    <?php $select_cats = wp_dropdown_categories("name=edit_category[]&id=edit_category&echo=0&hide_empty=0&hierarchical=1")?>
                                    <?php $select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats ); ?>
                                    <?php echo $select_cats; ?>
                                </div>
                                <div>
                                    <input type="submit" class="button-primary" name="Save"  value="Save" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Import Subscribers','stemail')?></span></h3>
                        <div class="inside">
                            <a name="import_csv"></a>
                            <form id="upload" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=category_email_subscriber"; ?>" method="POST">
                                <input type="hidden" name="file_upload" id="file_upload" value="true" />
                                <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
                                <strong><?php _e('Choose a CSV file to upload: ','stemail')?></strong><input name="csvfile" id="csvfile" type="file" size="25" />
                                <input type="submit" class="button-primary" name="UploadFile" value="Upload File" />
                            </form>
                        </div>
                    </div>
                </div>
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Export Subscribers','stemail')?></span></h3>
                        <div class="inside">
                            <a name="export_csv"></a>
                            <form method="post">
                                <strong><?php _e('Click on button to export Subscribers : ','stemail')?></strong>
                                <input type="submit" class="button-primary" name="export-subscribers" value="Export CSV File" />
                            </form>
                        </div>
                    </div>
                </div>
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Add a Subscriber','stemail')?></span></h3>
                        <div class="inside">
                            <a name="add_subscriber"></a>
                            <form id="add_subscriber" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=category_email_subscriber"; ?>" method="post">
                                <div>
                                    <label><?php _e('Name','stemail')?></label>
                                    <input name="sub_name" class="regular-text code" value=""/>
                                </div>
                                <div>
                                    <label><?php _e('Email','stemail')?></label>
                                    <input name="sub_email" class="regular-text code" value=""/>
                                </div>
                                <div>
                                    <label for="category"><?php _e('Category','stemail')?></label>
                                    <?php $select_cats =  wp_dropdown_categories("name=category[]&id=category&show_option_all=All Categories&echo=0&hide_empty=0&hierarchical=1")?>
                                    <?php $select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats ); ?>
                                    <?php echo $select_cats; ?>
                                </div>
                                <div>
                                    <input type="submit" class="button-primary" name="Subscribe"  value="Subscribe" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>				
        <div class="postbox-container side" style="width:20%;">
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Manage Subscribers', 'stemail')?></span></h3>
                        <div class="inside">
                            <a href="#import_csv"><?php _e('Import Subscribers from CSV', 'stemail')?></a><br/>
                            <a href="#add_subscriber"><?php _e('Add a Subscriber', 'stemail')?></a>
                        </div>
                    </div>
                    <div id="toc" class="postbox">
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Show your Support', 'stemail')?></span></h3>
                        <div class="inside">
                            <p>
                                <strong><?php _e('Want to help make this plugin even better? All donations are used to improve this plugin, so donate $20, $50 or $100 now!', 'stemail')?></strong>
                            </p>
                            <a href="http://sanskrutitech.in/wordpress-plugins/category-email-subscribe/"><?php _e('Donate', 'stemail')?></a>
                        </div>
                    </div>
                    <div id="toc" class="postbox">
                    <div class="handlediv" title="Click to toggle"><br /></div>
                        <h3 class="hndle"><span><?php _e('Connect With Us ', 'stemail')?></span></h3>
                        <div class="inside">
                            <a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
                            <a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
                            <a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
                            <a class="website" href="http://sanskrutitech.in/"></a>
                            <a class="email" href="mailto:info@sanskrutitech.in"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

add_action( 'admin_head', 'enqueue_subscribers_ajax_script' );

function enqueue_subscribers_ajax_script(){
    wp_enqueue_script( 'category-ajax-scripts.js', WP_CATEGORY_EMAIL_URL . '/scripts/subscribre-script.js' );
    wp_enqueue_script('category-ajax-scripts.js');
    wp_localize_script( 'category-ajax-scripts.js', 'STsubscribres', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_ajax_nopriv_search_subscribers_in_admin', 'search_subscribers_in_admin' );
add_action( 'wp_ajax_search_subscribers_in_admin', 'search_subscribers_in_admin' );
    
function search_subscribers_in_admin() {

    if (isset($_POST['searchsubscribers']) && $_POST['searchsubscribers'] != '') {

        $admin_url = admin_url();
        $this_page = $admin_url . "admin.php?page=category_email_subscriber";

        global $wpdb;
        global $email_table_suffix;

        $sub_search = $_POST['searchsubscribers'];
        $email_table = $wpdb->prefix . $email_table_suffix;

        $data = array();

        $sql = "SELECT * FROM $email_table WHERE name LIKE '%" . $sub_search . "%' OR email LIKE '%" . $sub_search . "%'";
        $subscribers = $wpdb->get_results($sql);
        foreach ($subscribers as $subscriber) {

            $list_category = "";
            if ($subscriber->category == 0) {
                $category = "All Categories";
            } else {

                $categories = explode(",", $subscriber->category);
                foreach ($categories as $category) {
                    if (strlen($list_category) > 0 && !empty($list_category))
                        $list_category = $list_category . ", ";
                    $list_category .= get_cat_name($category) . " ";
                }
            }
            $data[] = array(
                'id' => $subscriber->id,
                'name' => $subscriber->name,
                'email' => $subscriber->email,
                'category' => $list_category,
                'actions' => "<a href='#edit_subscriber' id='" . $subscriber->id . "' name='" . $subscriber->name . "' email='" . $subscriber->email . "' category='" . $subscriber->category . "' class='edit_this_subscriber'>Edit</a> | <a href='" . $this_page . "&action=delete&delete_id=" . $subscriber->id . "'>Delete</a>"
            );
        }
        include_once('templates/table.php');
    }
}

function export_subscribers_file_csv(){
    
    if (isset($_POST['export-subscribers'])){
        
        $fileName = "subscribers.csv";
 
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$fileName}");
        header("Expires: 0");
        header("Pragma: public");
 
        $fileheader = @fopen( 'php://output', 'w' );
 
        global $wpdb;
        global $email_table_suffix;
        
        $query = "SELECT * FROM `{$wpdb->prefix}$email_table_suffix`";
        $result = $wpdb->get_results( $query, ARRAY_A );
         
        $header = false;
         
        foreach ( $result as $data ) {
            
            if ( !$header ) {
                fputcsv($fileheader, array_keys($data));
                $header = true;
            }
        
        fputcsv($fileheader, $data);
        }
        
        fclose($fileheader);
        exit;

    }
}
add_action('admin_init', 'export_subscribers_file_csv');
?>