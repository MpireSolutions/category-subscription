<?php
/**
	Plugin Name: Category Email Subscribe
	Description: Plugin that allows Users to Subscribe for Emails based on Category.They will receive an email when a post is published in the category they have subscribed to.
	Version: 1.0
	Author: Uttam Mogilicherla
	Author URI: http://mpiresolutions.com
	Author Email: mpiresolutions@gmail.com
	License: GPL


	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	GNU General Public License: http://www.gnu.org/licenses/gpl.html
	
  TO Do :

  100 	Allow to edit subscriber
  110	Logs of Email Sent. Failures etc...  
  120	Import Subscribers
  130	Send Email when someone subscribes. According to setting
  140	Send Email when someone unsubscribes. According to setting
  150	Send Email of Log of Email sends
  160	How to Import Export Category
  170	Send Email to user for confirmation
  180	Allow to select multiple categories
  190	Allow user to update their subscription / unsubscribe
 */
 

/* If no Wordpress, go home */

if (!defined('ABSPATH')) { exit; }

/* Load Language */
add_action( 'plugins_loaded', 'email_load_textdomain' );

function email_load_textdomain() {
	load_plugin_textdomain('stemail', false,  dirname( plugin_basename( __FILE__ ) ) . "/language/");
}	

define('WP_CATEGORY_EMAIL_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_CATEGORY_EMAIL_URL', plugins_url('', __FILE__));

/**
 * 2. Global Parameters
 */
 
global $email_table_suffix;
global $category_email_db_ver;


$category_email_db_ver = "0.6";
$email_table_suffix = "category_email";

/**
 * 3. Activation / deactivation
 */
 
register_activation_hook(__FILE__, 'category_email_install');
register_deactivation_hook(__FILE__, 'category_email_uninstall');

function category_email_install() {
	global $wpdb;
	global $category_email_db_ver;
	global $email_table_suffix;
	
	$email_table = $wpdb->prefix . $email_table_suffix;
	
	$db_ver=get_option('category_email_db_ver',"0.5");
	$db_ver=(float) $db_ver;

	$category_email_db_ver = (float) $category_email_db_ver;
	
	/** If Updating from an older version */
	if($db_ver < $category_email_db_ver)
	{
		if($db_ver == "0.5"){
			$sql = "ALTER TABLE $email_table CHANGE category category VARCHAR(100);";
			$wpdb->query($sql);
		}
	}
	/* If new installation*/ 
	else{
		//Create table for subscribers
		$sql = "CREATE TABLE $email_table  (
			id INT(9) NOT NULL AUTO_INCREMENT,
			name VARCHAR(200),
			email VARCHAR(200) NOT NULL,
			category VARCHAR(100),
			UNIQUE KEY id (id)
		);";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	//Set DB Version
	update_option("category_email_db_ver", $category_email_db_ver);
	
    //Set Send Email
	update_option( 'category_email_send_email', get_option('admin_email') );
	
	//Set From Name
	update_option( 'category_email_from_name', get_option('blogname') );
}

function category_email_uninstall() {
	/** Do Nothing **/	
}

/** Short Code to display Subscription Form **/
add_shortcode("category_subscribe_form", "category_email_subscribe_shortcode");
add_shortcode("category_unsubscribe_form", "category_email_unsubscribe_shortcode");

/** Admin Page **/
if (is_admin()) {
    require_once dirname(__FILE__) . '/category_email_subscribe_admin.php';
    add_action('admin_print_scripts', 'category_email_subscribe_admin_scripts');
}
function category_email_subscribe_admin_scripts() {
	wp_register_style('category-email-style.css',WP_CATEGORY_EMAIL_URL.'/css/style.css');
	wp_enqueue_style('category-email-style.css');
	
	wp_register_style('category-email-multiple-select.css',WP_CATEGORY_EMAIL_URL.'/css/multiple-select.css');
	wp_enqueue_style('category-email-multiple-select.css');
	
	wp_enqueue_script('jquery');
	
	wp_enqueue_script( 'category-email-jquery.multiple.select.js', WP_CATEGORY_EMAIL_URL . '/scripts/jquery.multiple.select.js', array(), '1.0.0', true );
	wp_enqueue_script( 'category-email-jquery.csv.js', WP_CATEGORY_EMAIL_URL . '/scripts/jquery.csv-0.71.min.js', array(), '1.0.0', true );
	wp_enqueue_script( 'category-email-admin_scripts.js', WP_CATEGORY_EMAIL_URL . '/scripts/admin_scripts.js', array(), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'category_email_subscribe_scripts' );

function category_email_subscribe_scripts() {
	wp_register_style('category-email-multiple-select.css',WP_CATEGORY_EMAIL_URL.'/css/multiple-select.css');
	wp_enqueue_style('category-email-multiple-select.css');
	
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'category-email-jquery.multiple.select.js', WP_CATEGORY_EMAIL_URL . '/scripts/jquery.multiple.select.js', array(), '1.0.0', true );
	wp_enqueue_script( 'category-email-scripts.js', WP_CATEGORY_EMAIL_URL . '/scripts/scripts.js', array(), '1.0.0', true );
}

function category_email_subscribe_form($atts){
	extract($atts);
	
	$return = '<form class="subscribe_form" method="post"><input class="hiddenfield" name="subscribe_form" type="hidden" value="1">';
	
	if ($prepend) $return .= '<p class="prepend">'.$prepend.'</p>';
	
	if ($_POST['unsubsc'] == 0 && $thankyou) { 
		if (!is_email($_POST['email']))
		{
			$return .= '<p class="error" style="color:red;margin: 0 0 12px 0;">Please provide valid email address.</p>'; 
                }elseif($_POST['category'] == '' && $_POST['unsubsc'] != 1){
                    $return .= '<p class="error" style="color:red;margin: 0 0 12px 0;">Please select at least one category to subscribe.</p>'; 
                }elseif ($thankyou){
                        if($_POST['unsubsc'] == 1){
                            $return .= '<p class="thankyou">Subscription preferences updated.</p>';
                        }else{
                            $return .= '<p class="thankyou">'.$thankyou.'</p>'; 
                        }
		}	
	}
	
	if ($showname) $return .= '<p class="name"><label class="namelabel" for="name">'.$nametxt.'</label><input class="nameinput" placeholder="'.$nameholder.'" name="name" type="text" value=""></p>';
	$return .= '<p class="email"><label class="emaillabel" for="email">'.$emailtxt.'</label><input class="emailinput" name="email" placeholder="'.$emailholder.'" type="text" value=""></p>';
	$select_cats = wp_dropdown_categories("name=category[]&id=category&echo=0&hierarchical=1&exclude=67");	
	$select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats );
	if ($showcategory) $return .= '<p class="category"><label class="categorylabel" for="category">'.$categorytxt.'</label><br/>'  . $select_cats . '</p>';
	$return .= '<p class="submit"><input id="subscribe" name="submit" class="btn submitbtn" type="submit" value="'.($submittxt?$submittxt:'Submit').'"></p>';
	$return .= '<button name="unsubcribe" class="" id="unsubscribe">'.('Update Subscription Preferences').'</button></p>';	
	$return .= '<input type="hidden" id="unsubsc" name="unsubsc">';     	
	$return .= '</form>';
	
 	return $return;
}
function category_email_unsubscribe_form($atts){
	extract($atts);
	
	$return = '<form class="subscribe_form" method="post"><input class="hiddenfield" name="subscribe_form" type="hidden" value="1">';
	
	if ($prepend) $return .= '<p class="prepend">'.$prepend.'</p>';
	//print_r($_POST);
	if ($_POST['unsubsc'] == 1 && $thankyou) { 
		if (!is_email($_POST['email']))
		{
			$return .= '<p class="error" style="color:red;margin: 0 0 12px 0;">Please provide valid email address.</p>'; 
                }elseif($_POST['category'] == '' && $_POST['unsubsc'] != 1){
                    $return .= '<p class="error" style="color:red;margin: 0 0 12px 0;">Please select at least one category to subscribe.</p>'; 
                }elseif ($thankyou){
                        if($_POST['unsubcribe'] ){
                            $return .= '<p class="thankyou">Subscription preferences updated.</p>';
                        }else{
                            $return .= '<p class="thankyou">'.$thankyou.'</p>'; 
                        }
		}	
	}
	$email = $_GET['email'];
        $cat = $_GET['cat'];
	if ($showname) $return .= '<p class="name"><label class="namelabel" for="name">'.$nametxt.'</label><input class="nameinput" placeholder="'.$nameholder.'" name="name" type="text" value=""></p>';
	$return .= '<p class="email" style="  width: 100%"><label class="emaillabel" for="email">'.$emailtxt.'</label><input class="emailinput" name="email" placeholder="'.$emailholder.'"  type="text" value="'.$email.'" hello style="  border: 1px solid #b5b5b5; background-color: #FFFFFF; border-radius: 4px;"></p>';
	$select_cats = wp_dropdown_categories("name=category[]&id=category&echo=0&hierarchical=1&exclude=67&selected=4");	
	$select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats );
	if ($showcategory) $return .= '<p class="category"><label class="categorylabel" for="category">'.$categorytxt.'</label><br/>'  . $select_cats . '</p>';
	$return .= '<p class="submit"><input id="subscribe" name="unsubcribe" class="btn submitbtn" type="submit" value="Update Subscription"></p>';
	//$return .= '<button name="unsubcribe" class="" id="unsubscribe" value="1">'.('Update Subscription Preferences').'</button></p>';	
	$return .= '<input type="hidden" id="unsubsc" name="unsubsc" value="1">';     	
	$return .= '</form>';
	
 	return $return;
}

function category_email_subscribe_shortcode($atts=array()){
	$atts = shortcode_atts(array(
        "showname" => false,
		"nametxt" => 'Name:',
		"nameholder" => 'Name...',
		"emailtxt" => 'Email:',
		"emailholder" => 'Email Address...',
		"showcategory" => true,
		"categorytxt" => 'Category:',
		"submittxt" =>'Submit',
		"jsthanks" => false,
		"thankyou" => 'Thank you for subscribing to our mailing list'
    ), $atts);
	
	return category_email_subscribe_form($atts);
}
function category_email_unsubscribe_shortcode($atts=array()){
	$atts = shortcode_atts(array(
        "showname" => false,
		"nametxt" => 'Name:',
		"nameholder" => 'Name...',
		"emailtxt" => 'Email:',
		"emailholder" => 'Email Address...',
		"showcategory" => true,
		"categorytxt" => 'Category:',
		"submittxt" =>'Submit',
		"jsthanks" => false,
		"thankyou" => 'Thank you for subscribing to our mailing list'
    ), $atts);
	
	return category_email_unsubscribe_form($atts);
}
// Handle form Post
if ($_POST['subscribe_form']) {
	
	global $wpdb;
	global $email_table_suffix;
    $subscribers_table = $wpdb->prefix . $email_table_suffix;
	
	$name = $_POST['name'];
	$email = $_POST['email'];
	$category = $_POST['category'];
	  
	if (is_email($email)) {
          	 
                if($_POST['unsubsc'] == 1){
                    $unid = $_GET['id'];
                    $exists = $wpdb->get_results("SELECT * FROM ".$subscribers_table." where email like '".esc_sql($email)."' limit 1");
                    $cid = explode(",",$exists[0]->category);
                    if(!$_POST['category']){
                    	$_POST['category'] = array();
                    }
                    $results=array_intersect($cid,$_POST['category']);
                   
                   $insertcatg = implode(",",$_POST['category']);
                   if($insertcatg == ''){
                   	$insertcatg = '0';
                   }
                        $wpdb->query('UPDATE '.$subscribers_table.' SET category = "'.$insertcatg.'" WHERE email = "'.$email.'"');                 
                }else{
                 
                    if($_POST['category'] != ""){
                        $category = implode(",",$_POST['category']);
                        $exists = $wpdb->get_results("SELECT * FROM ".$subscribers_table." where email like '".esc_sql($email)."' limit 1");
                        if (empty($exists) ) {
                            $wpdb->insert($subscribers_table,array('name'=>esc_sql($name), 'email'=>esc_sql($email),'category'=>$category));
                        } 
                     }
                        if($_POST['unsubcribe']){
                           $cat = $_POST['category'];
 			     $cat = count($cat);
 			   if($cat < 1){
 			   	$_POST['category'] = array();
 			   }
 			     
	                            if($cat > 1){
	                            $dbctgs = explode(',',$fetcharr['category']);
	                            $mergedarr = array_merge($dbctgs, $_POST['category']);
	                            $uniarr = array_unique($mergedarr); 
	                            $category = implode(",",$uniarr);
	                            }else{
	                                $category = implode(",",$_POST['category']);
	                            }
	                            if($cat < 1){
	 			   	$category = 0;
	 			   }
	                             
	                            $email = esc_sql($email);
	                             $q_update = "UPDATE ".$subscribers_table." SET category = ".$category." WHERE email =  '".$email."' ";
	                            $wpdb->query($q_update); 
                            }
                         
                    
                }  
            
	}
}

function apply_template($post_detail, $template = false){
	//include( $template );
        $category_email_template = get_option( 'category_email_template' );
        $category_email_template = stripslashes($category_email_template);
	//Blog Name
	$category_email_template = str_replace('%blog_name%',$post_detail['blog_name'],$category_email_template);
	//Featured Image
        $category_email_template = str_replace('%featured_image%',$post_detail['featured_image'],$category_email_template);
	//Post Category
        $category_email_template = str_replace('%post_category%',$post_detail['post_category'],$category_email_template);
	//Post Title
	$category_email_template = str_replace('%post_title%',$post_detail['post_title'],$category_email_template);
	//Post Link
	$category_email_template = str_replace('%post_link%',$post_detail['post_link'],$category_email_template);
	//Author Link
	$category_email_template = str_replace('%author_link%',$post_detail['author_link'],$category_email_template);
	//Unsubcribe
	$category_email_template = str_replace('%unsubscribe%',$post_detail['unsubscribe'],$category_email_template);
	//Author Name
	$category_email_template = str_replace('%author_name%',$post_detail['author_name'],$category_email_template);
	//Post Content
	$category_email_template = str_replace('%post_content1%', $post_detail['post_content'], $category_email_template);
	//Post Date
	$category_email_template = str_replace('%post_date%',date("M d,Y",strtotime($post_detail['post_date'])),$category_email_template);
	
	
	//March 7, 2014 at 5:08 pm

	return $category_email_template;
}
//Send Email on Publish Post
add_action('transition_post_status','send_email', 10, 3);

function set_html_content_type() {
	return 'text/html';
}

//send notification e-mail on story publish
function send_email($new_status, $old_status, $post){
        $emm = array();
	global $wpdb;
	global $email_table_suffix;
	
        $table_name = $wpdb->prefix . $email_table_suffix;
	
	$send_email = get_option( 'category_email_send_email' );
	$from_name = get_option( 'category_email_from_name' );
	
	//From Name <Email>
	$headers[] = 'From: '.$from_name.' <'.$send_email.'>';
	
	
	$post = get_post($post->ID);

        if (($new_status == "publish" && $old_status == "publish") || ($new_status != "publish" && $old_status != "publish") || ($new_status != "publish" && $old_status == "publish")) return;

	// Post Title
	$subject = $post->post_title;
	$post_detail['post_title'] = $post->post_title;
	$post_detail['post_date'] = $post->post_date;
	//Post Link
	$post_detail['post_link'] = get_permalink( $post_ID );
	//Author
	$post_detail['author_name'] = get_the_author_meta( 'display_name', $post->post_author );
	$post_detail['author_link'] = get_the_author_meta( 'display_name', $post->post_author );
	
	//Blog Name
	$post_detail['blog_name']  = get_bloginfo('name');
	
	//Template
	
	// Post Content
	$post_detail['post_content'] = $post->post_excerpt;
	if($post->post_excerpt == ''){
		if(strlen($post->post_content)>= 200){
			$post_detail['post_content'] = substr($post->post_content, 200).'...';
		}else{
			$post_detail['post_content'] = $post->post_content;
		}
	}
	 
        //Post Category
        $post_detail['post_category'] = '';
        $categories = get_the_category($post->ID);
        end($categories);   
        $lastkey = key($categories);
        foreach($categories as $key => $category){
            $post_detail['post_category'] .=  ' <a href="'.get_site_url().'/category/'.$category->slug.'">'.$category->name.'</a>';
             $cat_id = $category->cat_ID;
            if(count($categories) > 1){             
                if($key != $lastkey){
                    $post_detail['post_category'] .= ', ';
                }
            }
        }
         
        $urlcatg = array();
        foreach($categories as $key => $category){
            $urlcatg[] = $category->term_id;
        }
	//Featured Image
        
        $thumbnail_src = get_the_post_thumbnail($post_ID, array(270, 270));
	$post_detail['featured_image']  = $thumbnail_src;//"<img src='".$thumbnail_src[0]."' width='100' height='100' />"; 


	// Get the Categories of the Post
	$categories = get_the_category($post_ID);
	//Get all the email address who have subscribed to this categories	
        //get e-mail address from post meta field
	$email_address = get_option( 'category_email_send_email' );
	add_filter('wp_mail_content_type', 'set_html_content_type');
         
	if($categories){
                $query = "";
		foreach($categories as $category) {
		    
                    if(!empty($query)){
                    $query  .= " union all ";
                    }
                    $query .= "SELECT * FROM ".$table_name." WHERE FIND_IN_SET(".$category->term_id.", category) ";
		}
		 
                $query = "SELECT distinct email from (".$query.") dt";
			$table_result = $wpdb->get_results($query);
                        $sentTo = 0;
			foreach ( $table_result as $table_row ) 
			{
                                if ($table_row->email) {
                                    if(wp_mail_dummy($table_row->email, $subject, $body, $headers)){
                                        
                                         $emm[0] = array('email' =>$table_row->email);
                                         $email = $table_row->email;
                                        $sentTo++;
                                        
                                        $post_detail['unsubscribe'] =  get_site_url().'/subscription-preferences/?email='.$email.'&cat='.$cat_id;
                                        $body = apply_template($post_detail);
                                        mandrill_email($emm, $body, $subject, $send_email, $from_name, $headers);
                                    } else {
                                    }
                                }
                                


                        }
                        

	}
	 
}
 
add_action('publish_future_post', 'send_email_schedule');
/*Function for scheduling the post*/
//send notification e-mail on story publish
function send_email_schedule($post){
        $emm = array();
	global $wpdb;
	global $email_table_suffix;

	$table_name = $wpdb->prefix . $email_table_suffix;
	
	$send_email = get_option( 'category_email_send_email' );
	$from_name = get_option( 'category_email_from_name' );
	
	//From Name <Email>
	$headers[] = 'From: '.$from_name.' <'.$send_email.'>';
        $post_ID = $post;	
	$post = get_post($post);
	// Post Title
	$subject = $post->post_title;
	$post_detail['post_title'] = $post->post_title;
	$post_detail['post_date'] = $post->post_date;
	//Post Link
	$post_detail['post_link'] = get_permalink( $post_ID );
	//Author
	$post_detail['author_name'] = get_the_author_meta( 'display_name', $post->post_author );
	$post_detail['author_link'] = get_the_author_meta( 'display_name', $post->post_author );
	
	//Blog Name
	$post_detail['blog_name']  = get_bloginfo('name');
	
	//Template
	
	// Post Content
	$post_detail['post_content'] = $post->post_excerpt;
	
        //Post Category
        $post_detail['post_category'] = '';
        $categories = get_the_category($post->ID);
        end($categories);   
        $lastkey = key($categories);
        foreach($categories as $key => $category){
            $post_detail['post_category'] .=  ' <a href="'.get_site_url().'/category/'.$category->slug.'">'.$category->name.'</a>';
            if(count($categories) > 1){             
                if($key != $lastkey){
                    $post_detail['post_category'] .= ', ';
                }
            }
        }
        $urlcatg = array();
        foreach($categories as $key => $category){
            $urlcatg[] = $category->term_id;
        }
//        $post_detail['unsubscribe'] =  get_site_url().'/unsubscribe?id[]='.implode('&amp;id[]=', array_map('urlencode', $urlcatg));
        $post_detail['unsubscribe'] =  get_site_url().'/unsubscribe';
	//Featured Image
        
        $thumbnail_src = get_the_post_thumbnail($post_ID, array(270, 270));
	$post_detail['featured_image']  = $thumbnail_src;//"<img src='".$thumbnail_src[0]."' width='100' height='100' />"; 

	$body = apply_template($post_detail);

	// Get the Categories of the Post
	$categories = get_the_category($post_ID);
	//Get all the email address who have subscribed to this categories	
        
	//get e-mail address from post meta field
	$email_address = get_option( 'category_email_send_email' );
	add_filter('wp_mail_content_type', 'set_html_content_type');
        
	if($categories){
                $query = "";
		foreach($categories as $category) {
                    if(!empty($query)){
                    $query  .= " union all ";
                    }
                    $query .= "SELECT * FROM ".$table_name." WHERE FIND_IN_SET(".$category->term_id.", category) ";
		}
                $query = "SELECT distinct email from (".$query.") dt";
			$table_result = $wpdb->get_results($query);
                        $sentTo = 0;
			foreach ( $table_result as $table_row ) 
			{
                                if ($table_row->email) {
                                    if(wp_mail_dummy($table_row->email, $subject, $body, $headers)){ 
                                        $emm[] = array('email' =>$table_row->email);
                                        $sentTo++;
                                    } else {
                                    }
                                }
                                


                        }
                        mandrill_email($emm, $body, $subject, $send_email, $from_name, $headers);

	}

}

function mandrill_email($emails, $body, $subject, $send_email, $from_name, &$headers){
    require_once 'mailchimp-mandrill-api-php/src/Mandrill.php'; //Not required with Composer
    $mandrill_api= get_option( 'category_email_mandrill_api' ); // api key: g0jIEYsxlKGLIFIaqCcoVA
    try {
        $mandrill = new Mandrill($mandrill_api);
        $message = array(
            'html' => $body,
            'subject' => $subject,
            'from_email' => $send_email,
            'from_name' => $from_name,
            'to' => $emails,
            'headers' => $headers,
            'important' => false,
            'track_opens' => null,
            'track_clicks' => null,
            'auto_text' => null,
            'auto_html' => null,
            'inline_css' => null,
            'url_strip_qs' => null,
            'preserve_recipients' => null,
            'view_content_link' => null,
            'bcc_address' => null,
            'tracking_domain' => null,
            'signing_domain' => null,
            'return_path_domain' => null,
            'merge' => true,
            'merge_language' => 'mailchimp'
        );
        $async = false;
        $ip_pool = 'Main Pool';
        $send_at = date("Y-m-d H:i:s");
        $result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
//         print_r($result);
    } catch(Mandrill_Error $e) {
        // Mandrill errors are thrown as exceptions
        // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
        //throw $e;
    }                               
}

function wp_mail_dummy($email, $subject, $body, $headers){
    return true;
}

function writelog($msg){
    $logfilename = "logs.txt";
    $file = fopen($logfilename,"a+");
    echo fwrite($file,$msg."\n");
    fclose($file);
}

function vartostring($obj){
    ob_start();
    var_dump($obj);
    $result = ob_get_clean();
    return $result;
}


/**
 * Add function to widgets_init that'll load our widget.
 */
 
 add_action('widgets_init','category_email_subscribe_load_widget');
 
 
 
 class category_email_subscribe_widget extends WP_Widget
 {
 
	/**
	 * Widget setup.
	 */
	 function __construct() {
		parent::__construct(
		// Base ID of your widget
		'category_email_subscribe_widget', 

		// Widget name will appear in UI
		__('Category Email Subscribe Form', 'stemail'), 

		// Widget description
		array( 'description' => __( 'An Widget that display Subscriber Form', 'stemail' ), ) 
		);
	}

	
	/**
	 * How to display the widget on the screen.
	 */
	 
	function widget($args,$instance)
	{
		extract($args);
		
		$title=apply_filters('widget_title',$instance['title']);
		echo $args['before_widget'];

		if ( $title )
		{
			echo $before_title . $title . $after_title;
		}
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		
		echo category_email_subscribe_form($instance);
		echo $args['after_widget'];

	}
	 
	 function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['prepend'] = strip_tags( $new_instance['prepend'] );
		$instance['showname'] = strip_tags( $new_instance['showname'] );
		$instance['nametxt'] = strip_tags($new_instance['nametxt']);
		$instance['nameholder'] = strip_tags($new_instance['nameholder']);
		$instance['emailtxt'] = strip_tags($new_instance['emailtxt']);
		$instance['emailholder'] = strip_tags($new_instance['emailholder']);
		$instance['showcategory'] = strip_tags($new_instance['showcategory']);
		$instance['categorytxt'] = strip_tags($new_instance['categorytxt']);
		$instance['submittxt'] = strip_tags($new_instance['submittxt']);
		$instance['jsthanks'] = strip_tags($new_instance['jsthanks']);
		$instance['thankyou'] = strip_tags($new_instance['thankyou']);
		return $instance;
	}
	
	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	 
	function form( $instance ) 
	{
		/* Set up some default widget settings. */
		$defaults = array( 	'prepend' => 'Subscribe to receive updates in email',
							'showname' => '1',
							'nametxt' => 'Name:',
							'nameholder' => 'Name...',
							'emailtxt' => 'Email:',
							'emailholder' => 'Email Address...',
							'showcategory' => '1',
							'categorytxt' => 'Category:',
							'submittxt' => 'Submit',
							'jsthanks' => '0',
							'thankyou' => 'Thank you for subscribing to our mailing list');
		$instance = wp_parse_args( $instance, $defaults );
		
	?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'prepend' ); ?>"><?php _e('Prepend:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'prepend' ); ?>" name="<?php echo $this->get_field_name( 'prepend' ); ?>" value="<?php echo $instance['prepend']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'showname' ); ?>"><?php _e('Show Name Field:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('showname'); ?>" name="<?php echo $this->get_field_name('showname'); ?>" type="checkbox" value="1" <?php if ($instance['showname']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'nametxt' ); ?>"><?php _e('Name Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'nametxt' ); ?>" name="<?php echo $this->get_field_name( 'nametxt' ); ?>" value="<?php echo $instance['nametxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'nameholder' ); ?>"><?php _e('Name Field Default Value:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'nameholder' ); ?>" name="<?php echo $this->get_field_name( 'nameholder' ); ?>" value="<?php echo $instance['nameholder']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'emailtxt' ); ?>"><?php _e('Email Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'emailtxt' ); ?>" name="<?php echo $this->get_field_name( 'emailtxt' ); ?>" value="<?php echo $instance['emailtxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'emailholder' ); ?>"><?php _e('Email Field Default Value:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'emailholder' ); ?>" name="<?php echo $this->get_field_name( 'emailholder' ); ?>" value="<?php echo $instance['emailholder']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'showcategory' ); ?>"><?php _e('Show Category Field:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('showcategory'); ?>" name="<?php echo $this->get_field_name('showcategory'); ?>" type="checkbox" value="1" <?php if ($instance['showcategory']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categorytxt' ); ?>"><?php _e('Category Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'categorytxt' ); ?>" name="<?php echo $this->get_field_name( 'categorytxt' ); ?>" value="<?php echo $instance['categorytxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'submittxt' ); ?>"><?php _e('Submit Button Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'submittxt' ); ?>" name="<?php echo $this->get_field_name( 'submittxt' ); ?>" value="<?php echo $instance['submittxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'jsthanks' ); ?>"><?php _e('Show JavaScript Thanks:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('jsthanks'); ?>" name="<?php echo $this->get_field_name('jsthanks'); ?>" type="checkbox" value="1" <?php if ($instance['jsthanks ']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'thankyou' ); ?>"><?php _e('Thank You Text', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'thankyou' ); ?>" name="<?php echo $this->get_field_name( 'thankyou' ); ?>" value="<?php echo $instance['thankyou']; ?>" style="width:100%;" />
		</p>

	<?php
	}
 }
 
 
 /**
 * Register our widget.
 * 'category_email_subscribe_load_widget' is the widget class used below.
 */
 function category_email_subscribe_load_widget()
 {
	register_widget('category_email_subscribe_widget'); 
 }
?>