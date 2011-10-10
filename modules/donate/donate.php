<?php

/** Donate Module */

cp_module_register(__('Donate', 'cp') , 'donate' , '1.0', 'CubePoints & Mahibul Hasan(edited)', 'http://cubepoints.com', 'http://cubepoints.com' , __('This module allows your users to donate points to each other.', 'cp'), 1);

if(cp_module_activated('donate')){

	function cp_module_donate_scripts(){
	
		wp_register_script('boxy',
		   CP_PATH . 'assets/boxy/javascripts/jquery.boxy.js',
		   array('jquery'),
		   '0.1.4' );

		wp_register_style('boxy', CP_PATH . 'assets/boxy/stylesheets/boxy.css');

		wp_enqueue_script('boxy');
		wp_enqueue_style('boxy');
		
	}

	add_action('init', 'cp_module_donate_scripts');
	
	
	
	
	/*
	 * adding jquery auto complete
	 * */
	
	add_action('wp_print_scripts','cp_module_jquery_ui_add');
	add_action('wp_print_styles','cp_module_jquery_ui_css_add');
	function cp_module_jquery_ui_add(){
		
		if ( ! is_admin() ) : 
			wp_enqueue_script('jquery');
			
			
			//passimg the array to the autocomplete object
			global $wpdb;
			
			$authors = $wpdb->get_col("SELECT `user_login` FROM $wpdb->users");
			$auth = '';
			foreach($authors as $author){
				$auth .= $author . '-';
			}
			
			
			
			wp_register_script('cp_ui_autocomplete_js',CP_PATH . 'assets/jquery-autocomplete/js/jquery-ui-1.8.16.custom.min.js', array('jquery'));			
			wp_enqueue_script('cp_ui_autocomplete_js');
			
			wp_register_script('cp_autocomplete_js',CP_PATH . 'modules/donate/auto-complete.js', array('jquery'));			
			wp_enqueue_script('cp_autocomplete_js');
			
			//localising scripts
			wp_localize_script( 'cp_autocomplete_js', 'CPAUTO', array( 
				'authors' => trim($auth,'-')				
			));
			
			
		endif;
	}
	
	function cp_module_jquery_ui_css_add(){
		
		if ( ! is_admin() ) :					
			wp_register_style('cp_ui_autocomplete_css',CP_PATH . 'assets/jquery-autocomplete/css/ui-lightness/jquery-ui-1.8.16.custom.css');
			wp_enqueue_style('cp_ui_autocomplete_css');	
		endif;	
			
	}
	
	
	
	function cp_module_donate_do(){
		$recipient = $_POST['recipient'];
		$points = $_POST['points'];
		$message = str_replace("'",'&#039;',htmlentities(stripslashes($_POST['message'])));
		$user =  get_userdatabylogin($recipient);

		if(!is_user_logged_in()){
			$r['success'] = false;
			$r['message'] = __('You must be logged in to make a donation!', 'cp');
		}
		
		else if($recipient==''){
			$r['success'] = false;
			$r['message'] = __('Please enter the username of the recipient!', 'cp');
		}
		
		else if($user->ID==''){
			$r['success'] = false;
			$r['message'] = __('You have entered an invalid recipient!', 'cp');
		}
		
		else if($user->ID==cp_currentUser()){
			$r['success'] = false;
			$r['message'] = __('You cannot donate to yourself!', 'cp');
		}
		
		else if(!is_numeric($points)){
			$r['success'] = false;
			$r['message'] = __('You have entered an invalid number of points!', 'cp');
		}
		
		else if((int)$points<1){
			$r['success'] = false;
			$r['message'] = __('You have to donate at least one point!', 'cp');
		}
		
		else if((int)$points != (float) $points){
			$r['success'] = false;
			$r['message'] = __('You have entered an invalid number of points!', 'cp');
		}
		
		else if((int)$points>(int)cp_getPoints(cp_currentUser())){
			$r['success'] = false;
			$r['message'] = __('You do not have that many points to donate!', 'cp');
		}
		
		else if(strlen($message)>160){
			$r['success'] = false;
			$r['message'] = __('The message you have entered is too long!', 'cp');
		}
		
		else{
			$r['success'] = true;
			$r['message'] = __('Your donation is successful!', 'cp');
			cp_points('donate_from', $user->ID, $points, serialize(array("from"=>cp_currentUser(),"message"=>$message)));
			cp_points('donate_to', cp_currentUser(), -$points, serialize(array("to"=>$user->ID,"message"=>$message)));
			$r['pointsd'] = cp_displayPoints(0, 1, 1);
			$r['points'] = cp_displayPoints(0, 1, 0);
		}

		echo json_encode($r);
		die();
	}
	
	add_action('wp_ajax_cp_module_donate_do', 'cp_module_donate_do');
	add_action('wp_ajax_nopriv_cp_module_donate_do', 'cp_module_donate_do');
	
	// Handle JS
	
	wp_register_script('cp_donate_script', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)). 'donate.js', array('jquery'));

	function cp_module_donate_script(){ 
		wp_enqueue_script('cp_donate_script');
	}

	add_action('init', 'cp_module_donate_script');
	
	wp_localize_script( 'cp_donate_script', 'cp_donate', array(
		ajax_url=>get_bloginfo('url').'/wp-admin/admin-ajax.php',
		confirmation=>__('Are you sure you want to donate points?', 'cp'),
		recipient=>__('Recipient', 'cp'),
		message=>__('Message', 'cp'),
		amount=>__('Amount', 'cp'),
		donate_points=>__('Donate Points', 'cp'),
		donate=>__('Donate Points', 'cp')
	) );
	
	function cp_module_donate_widget(){
		if(is_user_logged_in()){
			?>
				<li><a class="cp_donate_button" href="javascript:void(0);" onclick="cp_module_donate();"><?php _e('Donate', 'cp'); ?></a></li>
			<?php
		}
	}

	add_action('cp_pointsWidget', 'cp_module_donate_widget',1000);
	
	/** Donations log hook */
	add_action('cp_logs_description','cp_admin_logs_desc_donate', 10, 4);
	function cp_admin_logs_desc_donate($type,$uid,$points,$data){
		if($type!='donate_to'&&$type!='donate_from') { return; }
		$data = unserialize($data);
		$user = get_userdata($data['to']+$data['from']);
		if($type=='donate_to'){ 
			echo __('Donation to', 'cp') . ' "' . $user->user_login . '"';
		}
		if($type=='donate_from'){ 
			echo __('Donation from', 'cp') . ' "' . $user->user_login . '"';
		}
		if($data['message']!=''){
			echo ' <a href="javascript:void(0);" onclick="Boxy.alert(\''.htmlspecialchars($data['message']).'\')">[' . __('Message', 'cp') . ']</a>';
		}
	}
	
}
?>
