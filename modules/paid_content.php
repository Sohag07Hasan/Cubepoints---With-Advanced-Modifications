<?php

/** Paid Content Module */

cp_module_register(__('Paid Content', 'cp') , 'pcontent' , '1.0', 'CubePoints', 'http://cubepoints.com', 'http://cubepoints.com' , __('This module lets you deduct point from users to view a page or post.', 'cp'), 1);

if(cp_module_activated('pcontent')){

	/* Define the custom box */
	add_action('admin_init', 'cp_module_pcontent_add_custom_box', 1);

	/* Do something with the data entered */
	add_action('save_post', 'cp_module_pcontent_save_postdata');

	/* Adds a box to the main column on the Post and Page edit screens */
	function cp_module_pcontent_add_custom_box() {
		add_meta_box( 'cp_module_pcontent_set', 'CubePoints - Paid Content', 'cp_module_pcontent_box', 'post', 'normal', 'high' );
		add_meta_box( 'cp_module_pcontent_set', 'CubePoints - Paid Content', 'cp_module_pcontent_box', 'page', 'normal', 'high' );
	}

	/* Prints the box content */
	function cp_module_pcontent_box() {

		global $post;

		// Use nonce for verification
		wp_nonce_field( plugin_basename(__FILE__), 'cp_module_pcontent_nonce' );

		// The actual fields for data entry
		echo '<br /><input type="checkbox" id="cp_module_pcontent_enable" name="cp_module_pcontent_enable" value="1" size="25" '.((bool)(get_post_meta($post->ID , 'cp_pcontent_points_enable', 1))?'checked="yes"':'').' /> ';
		echo '<label for="cp_module_pcontent_enable">' . __("Enable paid content" , 'cp') . '</label> ';
		echo '<br /><br />';
		echo '<label for="cp_module_pcontent_points">' . __("Number of points to be deducted to view this page / post" , 'cp') . ':</label> ';
		echo '<input type="text" id= "cp_module_pcontent_points" name="cp_module_pcontent_points" value="'.(int)get_post_meta($post->ID , 'cp_pcontent_points', 1).'" size="25" /><br /><br />';
	}

	/* When the post is saved, saves our custom data */
	function cp_module_pcontent_save_postdata( $post_id ) {

		// get post id from the revision id
		if($parent_id = wp_is_post_revision($post_id)){
			$post_id = $parent_id;
		}

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times

		if ( !wp_verify_nonce( $_POST['cp_module_pcontent_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;

	  
		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		// OK, we're authenticated: we need to find and save the data
		$points = (int)$_POST['cp_module_pcontent_points'];
		if($points<1){
			$points = 1;
		}
		update_post_meta($post_id, 'cp_pcontent_points_enable', (int)$_POST['cp_module_pcontent_enable']);
		update_post_meta($post_id, 'cp_pcontent_points', $points);

	}

	add_action('the_post','cp_module_pcontent_post');
	add_filter( "the_content", "cp_module_pcontent_post_content" );
	
	function cp_module_pcontent_post($p){
		$pcontent_enabled = (bool) get_post_meta($p->ID,'cp_pcontent_points_enable', 1);
		if(!$pcontent_enabled){
			return;
		}
		if(current_user_can( 'read_private_pages' )){
			return;
		}
		$uid = cp_currentUser();
		$pid = $p->ID;
		global $wpdb;
		if( (int) $wpdb->get_var("SELECT COUNT(*) FROM ".CP_DB." WHERE `uid`=$uid AND `data`=$pid AND `type`='pcontent'") != 0 ){
			return;
		}
		global $cp_module_pcontent_hide;
		$cp_module_pcontent_hide[] = $p->ID;
	}
	
	function cp_module_pcontent_post_content($content){
		global $post;
		global $cp_module_pcontent_hide;
		if(!in_array($post->ID,(array)$cp_module_pcontent_hide)){
			return $content;
		}
		$c = __('You need to pay %points% to view this page!', 'cp');
		$c .= apply_filters('cp_module_pcontent_post_content_'.$post->ID, '');
		$c .= '<br /><br /><form method="post">';
		$c .= '<input type="hidden" name="cp_module_pcontent_pay" value="'.$post->ID.'" />';
		$c .= '<input type="submit" value="'.__('View this page for %points%', 'cp').'" />';
		$c .= '</form>';
		$c = str_replace('%points%',cp_formatPoints(get_post_meta($post->ID,'cp_pcontent_points', 1)),$c);
		return $c;
	}
	
	add_action('init', 'cp_module_pcontent_buy');
	function cp_module_pcontent_buy(){
		if($_POST['cp_module_pcontent_pay']=='') return;
		$pcontent_enabled = (bool) get_post_meta($_POST['cp_module_pcontent_pay'],'cp_pcontent_points_enable', 1);
		if(!$pcontent_enabled) return;
		$uid = cp_currentUser();
		global $wpdb;
		$pid = $_POST['cp_module_pcontent_pay'];
		if( (int) $wpdb->get_var("SELECT COUNT(*) FROM ".CP_DB." WHERE `uid`=$uid AND `data`=$pid AND `type`='pcontent'") != 0 ){
			return;
		}
		if(!is_user_logged_in()){
			add_filter('cp_module_pcontent_post_content_'.$_POST['cp_module_pcontent_pay'], create_function('$data', 'return "<br /><br /><span style=\"color:red;\">'.__('Please log in to purchase this page!', 'cp').'</span>";'));
			return;
		}
		if(cp_getPoints(cp_currentUser())<get_post_meta($_POST['cp_module_pcontent_pay'],'cp_pcontent_points', 1)){
			add_filter('cp_module_pcontent_post_content_'.$_POST['cp_module_pcontent_pay'], create_function('$data', 'return "<br /><br /><span style=\"color:red;\">'.__('You do not have enough to pay for this page!', 'cp').'</span>";'));
			return;
		}
		cp_points('pcontent',cp_currentUser(),-get_post_meta($_POST['cp_module_pcontent_pay'],'cp_pcontent_points', 1),$_POST['cp_module_pcontent_pay']);
	}
	
	/** Paid Content Log Hook */
	add_action('cp_logs_description','cp_admin_logs_desc_pcontent', 10, 4);
	function cp_admin_logs_desc_pcontent($type,$uid,$points,$data){
		if($type!='pcontent') { return; }
		$post = get_post($data);
		echo __('Purchased view of', 'cp') . ' "<a href="'.get_permalink( $post ).'">' . $post->post_title . '</a>"';
	}

}
	
?>