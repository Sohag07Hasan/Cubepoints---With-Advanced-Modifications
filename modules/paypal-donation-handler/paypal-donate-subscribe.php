<?php

cp_module_register(__('PayPal Donation Handler', 'cp') , 'cp_donate' , '1.0', 'Mahibul Hasan', 'http://sohag07hasan.elance.com', 'http://wpfbapps.com' , __('Allow users to get points, if they donate or subscirbe.', 'cp'), 1);

//short code systmem 
	add_shortcode('paypal_subscription','cp_subscribe_form_get');
	add_shortcode('paypal_donation','cp_donation_form_get');
	
	function cp_subscribe_form_get(){
		return get_option('cp_subscription_form');
	}
	function cp_donation_form_get(){
		return get_option('cp_donation_form');	
	}
	
	//short code to insert hidden filelds to the donation button;
	add_filter('the_content', 'cp_donation_hidden_fields', 150); 
	function cp_donation_hidden_fields($content){
				
		if(strpos($content,'[donation_hiddens]')){					
			$notify_url = '/?don-sub=1';
			$pattern = '/\[donation_hiddens\]/';
			$content = shotcodechanging_cp($pattern,$notify_url,$content,'donation');				
		}
		
		if(strpos($content,'[subscription_hiddens]')){
			$notify_url = '/?don-sub=1';
			$pattern = '/\[subscription_hiddens\]/';
			$content = shotcodechanging_cp($pattern,$notify_url,$content,'subscription');
			
				
		}
		return $content;
	}
	
	function shotcodechanging_cp($pattern,$notify_url,$content,$type){
		
		$hiddenfileds = '';
		
		if(is_user_logged_in()){
				global $current_user;
				get_currentuserinfo();
				$value = $type.'|'.$current_user->ID;
		}
		else{
			$value = 'no';
		}
			
			$home = get_option('home');
			$return_url = $home.'/?donation=done';
			$cancel_url = $home.'/?donation=cancel';			
			$notify_url = $home.$notify_url;
			$hiddenfileds .= '<input type="hidden" name="custom" value="'.$value.'" />';
			$hiddenfileds .= '<input type="hidden" name="return" value="'.$return_url.'" />';
			$hiddenfileds .= '<input type="hidden" name="cancel" value="'.$cancel_url.'" />';
			$hiddenfileds .= '<input type="hidden" name="notify_url" value="'.$notify_url.'" />';			
			$content = preg_replace($pattern,$hiddenfileds,$content);
			return $content;
	}


//end of th shorcode actions

//if the module is activated everything goes here
if ( cp_module_activated( 'cp_donate' ) ) :
	
	
	//admin submenu page
	function cp_donate_add_admin_page(){
		add_submenu_page('cp_admin_manage', 'CubePoints - ' .__('PayPal Donation Handler','cp'), __('PayPal Donation handler','cp'), 8, 'paypal_donation_handler_admin', 'paypal_donation_handler_admin');
	}
	add_action('cp_admin_pages','cp_donate_add_admin_page');
	
	function paypal_donation_handler_admin(){
				
		//checking if the form is submitted
		if($_POST['cp_donation_form_submit'] == 'Y') :
				
			update_option('cp_donation_amt',trim($_POST['cp_donation_amt']));
			update_option('cp_subscribtion_amt',trim($_POST['cp_subscribtion_amt']));
			
			update_option('cp_donation_form',stripcslashes($_POST['cp_donation_form']));
			update_option('cp_subscription_form',stripcslashes($_POST['cp_subscription_form']));			
		
		endif;
		
		?>
		
		<div class="wrap">
			<h2>CubePoints - <?php _e('PayPal Donation Handling', 'cp'); ?></h2>
			<?php _e('Configure the PayPal Donation module.', 'cp'); ?><br /><br />
			<?php _e('PayPal Username, Sandbox mode, currency values are taken form Paypal Top-Up Module', 'cp'); ?><br /><br />

			<form action="" method="post">			
				
				<h3><?php _e('Points for Subscribe Button','cp'); ?></h3>
				<table class="form-table">				
				<tr valign="top">
					<th scope="row"><label for="cp_subscribtion_amt"><?php _e('Points per dollar ($)', 'cp'); ?>:</label></th>
					<td valign="middle"><input type="text" id="cp_subscribtion_amt" name="cp_subscribtion_amt" value="<?php echo get_option('cp_subscribtion_amt'); ?>" /></td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="cp_subscription_form"><?php _e('Paypal Subscription Form ', 'cp'); ?>:</label></th>
					<td valign="middle"><textarea id="cp_subscription_form" name="cp_subscription_form" cols="90" rows="13" style="font-size:10px;" ><?php echo get_option('cp_subscription_form');?></textarea>
					<br/>
					<small>
						Please copy the shortcode <span style="color:#24007B">[subscription_hiddens]</span> after the <?php echo htmlentities('<form> tag'); ?>
						<br/>
						 Use <strong style="color:red">[paypal_subscription]</strong> shortcode to get the form in any post or page </strong>
					
					</td>
				</tr>	
				
				</table>
				<br/><br/>
				<h3>PayPal Donation Button</h3>
				<table class="form-table">				
				<tr valign="top">
					<th scope="row"><label for="cp_donation_amt"><?php _e('Points per dollar ($) ', 'cp'); ?>:</label></th>
					<td valign="middle"><input type="text" id="cp_donation_amt" name="cp_donation_amt" value="<?php echo get_option('cp_donation_amt'); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cp_donation_form"><?php _e('Paypal Donation Form ', 'cp'); ?>:</label></th>
					<td valign="middle"><textarea id="cp_donation_form" name="cp_donation_form" cols="90" rows="13" style="font-size:10px;" ><?php echo get_option('cp_donation_form');?></textarea>
					<br/>
					<small>
					<br/>
						Please copy the shortcode <span style="color:#24007B">[donation_hiddens]</span> after the <?php echo htmlentities('<form> tag'); ?>
					<br/>
						 Use <strong style="color:red">[paypal_donation]</strong> shortcode to get the form in any post or page </strong>
					
					</td>
					</td>
				</tr>									
				</table>				
				
				
				
				<input type="hidden" name="cp_donation_form_submit" value="Y" />
				<input type="submit" class="button-primary" value="Update Options" />
			</form>
		</div>
		
		<?php
	}
	
	
	//paypal response parsing
	add_action('init','cp_paypal_donation_ipn');
	function cp_paypal_donation_ipn(){	
		
		if($_REQUEST['don-sub'] == 1){	
			
			if(!function_exists('wp_mail')){
				include ABSPATH.'wp-includes/pluggable.php' ;
			}
									
			// read the post from PayPal system and add 'cmd'
			//$type = $_REQUEST['type'];
			$req = 'cmd=_notify-validate';

			foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
			}

			if(get_option('cp_module_paypal_sandbox')){
				$loc = 'ssl://www.sandbox.paypal.com';
			}
			else{
				$loc = 'ssl://www.paypal.com';
			}
			
			// post back to PayPal system to validate
			$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
			$fp = fsockopen ($loc, 443, $errno, $errstr, 30);
			//checking if the user is logged in
						
			// assign posted variables to local variables
			$item_name = $_POST['item_name'];
			$item_number = $_POST['item_number'];
			$payment_status = $_POST['payment_status'];
			$payment_amount = $_POST['mc_gross'];
			$payment_currency = $_POST['mc_currency'];
			$txn_id = $_POST['txn_id'];
			$receiver_email = $_POST['receiver_email'];
			$payer_email = $_POST['payer_email'];
			$custom = $_POST['custom'];	
			$payment_amount = $_POST['mc_gross'];		
			
			
			
			if (!$fp) {
				//http error
			}
			else {
				
				fputs ($fp, $header . $req);
				while (!feof($fp)) {
					$res = fgets ($fp, 1024);
					if (strcmp ($res, "VERIFIED") == 0) {						
							
														
						// check the payment_status is Completed
							if($payment_status == 'Completed' || $payment_status == 'Pending') { 
								
							// check that txn_id has not been previously processed
								global $wpdb;
								$results = $wpdb->get_results('SELECT * FROM `'.CP_DB.'` WHERE `type`=\'paypal\' OR `type`=\'donate-subscribe\'');
																		
								if($results){
									foreach($results as $result){
										$data = $result->data;
										if($data['txn_id']==$txn_id){ die(); }
									}
								}
																
							// check that receiver_email is your Primary PayPal email
								if($receiver_email!=get_option('cp_module_paypal_account')){ die(); }					
															
								
							//checking if the donor is registered and what type of doantion is done
								if($custom == 'no') die();
								$custom = explode('|',$custom);
								$uid = $custom[1];
								$type = $custom[0];
								//checking if it is a donation or subscription
								$points = 0;
								if($type == 'subscription'){
									$points = get_option('cp_subscribtion_amt');				
								}
								if($type == 'donation'){
									$points = get_option('cp_donation_amt');
								}
								
								//calculating the points
								
								$points = $points * $payment_amount;
								$points = (int)ceil($points);
								
								/*
								
								if(!function_exists('wp_mail')){
									include ABSPATH.'wp-includes/pluggable.php' ;
								}
								
								wp_mail('hyde.sohag@gmail.com','test',$type.$points);
								*/
																					
							// process payment
							   cp_points('donate-subscribe', $uid, (int)$points, serialize(array('txn_id'=>$txn_id,'payer_email'=>$payer_email,'amt'=>$payment_amount)));
						   }
						   else{ die(); }
							
					}
					else if (strcmp ($res, "INVALID") == 0) {
					// invalid paypal return
						die();
					}
				}
				fclose ($fp);
			}
			exit();
		}
	}
	
	/** PayPal donations logs  hook */
	add_action('cp_logs_description','cp_donations_logs', 10, 4);
	function cp_donations_logs($type,$uid,$points,$data){
		if($type!='donate-subscribe') { return; }
		$data = unserialize($data);
		echo '<span title="'.__('Paid by', 'cp').': '.$data['payer_email'].'">'.__('Donations/Subscribe(paypal)', 'cp').' (ID: '.$data['txn_id'].')</span>';
	}
	
	
	
	//donation confirmation messages
	add_action('init','cd_donation_message');	
	function cd_donation_message(){	
		if ($_GET['donation'] == 'done'){
				cp_module_paypal_showMessage(__('Thank You for your contribution!', 'cp'));
		}		
		if ($_GET['donation'] == 'cancel'){
				cp_module_paypal_showMessage(__('Your donation is not completed!', 'cp'));
		}
	}
	
endif;



?>
