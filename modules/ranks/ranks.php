<?php

/** Ranks Module */

cp_module_register(__('Ranks', 'cp') , 'ranks' , '1.0', 'CubePoints & Mahibul Hasan(edited)', 'http://sohag07hasan.elance.com', 'http://cubepoints.com' , __('Create and display user ranks with ranks logo based on the number of points they have.', 'cp'), 1);

function cp_module_ranks_data_install(){
	add_option('cp_module_ranks_data', array(0=>__('Newbie','cp')));
	add_option('cp_module_ranks_logo',array(0=>''));
}
add_action('cp_module_ranks_activate','cp_module_ranks_data_install');

/*************************************************
 *  * Upload libarary add
 * ***********************************************/
add_action('admin_enqueue_scripts','cp_module_media_thikbox_adding',12);
function cp_module_media_thikbox_adding(){
	if($_REQUEST['page'] == 'cp_modules_ranks_admin'){
		wp_enqueue_script('jquery');
		wp_enqueue_script('cp_module_media_upload',plugins_url('/',__FILE__).'script.js',array('jquery','media-upload','thickbox'));
		wp_enqueue_style('thickbox');
	}
}



if(cp_module_activated('ranks')){

	function cp_module_ranks_data_add_admin_page(){
		add_submenu_page('cp_admin_manage', 'CubePoints - ' .__('Ranks','cp'), __('Ranks','cp'), 8, 'cp_modules_ranks_admin', 'cp_modules_ranks_admin');
	}
	add_action('cp_admin_pages','cp_module_ranks_data_add_admin_page');

	function cp_modules_ranks_admin(){

	// handles form submissions
	if ($_POST['cp_module_ranks_data_form_submit'] == 'Y') {

		$cp_module_ranks_data_rank = trim($_POST['cp_module_ranks_data_rank']);
		$cp_module_ranks_data_points = (int) trim($_POST['cp_module_ranks_data_points']);
		$cp_module_ranks_logo = trim($_POST['cp_module_ranks_logo']);
		
		$ranks = get_option('cp_module_ranks_data');
		$logos = get_option('cp_module_ranks_logo');
		
		if($cp_module_ranks_data_rank==''||$_POST['cp_module_ranks_data_points']==''){
			echo '<div class="error"><p><strong>'.__('Rank name or points cannot be empty!','cp').'</strong></p></div>';
		}
		else if(!is_numeric($_POST['cp_module_ranks_data_points'])||$cp_module_ranks_data_points<0||(int)$_POST['cp_module_ranks_data_points']!=(float)$_POST['cp_module_ranks_data_points']){
			echo '<div class="error"><p><strong>'.__('Please enter only positive integers for the points!','cp').'</strong></p></div>';
		}
		else{
			if($ranks[$cp_module_ranks_data_points]!=''){
				echo '<div class="updated"><p><strong>'.__('Rank Updated','cp').'</strong></p></div>';
			}
			else{
				echo '<div class="updated"><p><strong>'.__('Rank Added','cp').'</strong></p></div>';
			}
			$ranks[$cp_module_ranks_data_points] = $cp_module_ranks_data_rank;
			$logos[$cp_module_ranks_data_points] = $cp_module_ranks_logo;
			update_option('cp_module_ranks_data' ,$ranks);
			update_option('cp_module_ranks_logo',$logos);
		}
	}
	
	if ($_POST['cp_rank_remove'] != '') {
		if((int)$_POST['cp_rank_remove']==0){
			echo '<div class="error"><p><strong>'.__('A rank name is needed for users with 0 points!<br /><br />To change the name of this rank, add another rank to replace this.','cp').'</strong></p></div>';
		}
		else{
			$ranks = get_option('cp_module_ranks_data');
			$logos = get_option('cp_module_ranks_logo');
			unset($logos[(int)$_POST['cp_rank_remove']]);
			unset($ranks[(int)$_POST['cp_rank_remove']]);
			update_option('cp_module_ranks_data', $ranks);
			update_option('cp_module_ranks_logo', $logos);
			echo '<div class="updated"><p><strong>'.__('Rank removed','cp').'</strong></p></div>';
		}
	}
		
	?>
	
	<div class="wrap">
		<h2>CubePoints - <?php _e('Ranks', 'cp'); ?></h2>
		<?php _e('Setup ranks for your users.', 'cp'); ?> <?php _e('To rename ranks, overwrite it with a new rank.', 'cp'); ?><br /><br />

		<table id="cp_modules_table" class="widefat datatables">
			<thead>
				<tr>
					<th scope="col" width="150" ><?php _e('Rank','cp'); ?></th>
					<th scope="col" width="150" style="text-align:center;"><?php _e('Logo','cp'); ?></th>
					<th scope="col" width="150" style="text-align:center;"><?php _e('Points','cp'); ?></th>
					<th scope="col" width="150"><?php _e('Action','cp'); ?></th>
				</tr>
			</thead>
			
			<tfoot>
				<tr>
					<th scope="col" width="150" ><?php _e('Rank','cp'); ?></th>
					<th scope="col" width="150" style="text-align:center;"><?php _e('Logo','cp'); ?></th>
					<th scope="col" width="150" style="text-align:center;"><?php _e('Points','cp'); ?></th>
					<th scope="col" width="150"><?php _e('Action','cp'); ?></th>
				</tr>
			</tfoot>
			
			<?php
					$ranks = (array)get_option('cp_module_ranks_data');
					$logos = get_option('cp_module_ranks_logo');
					
					if($ranks[0]==''){
						$ranks[0] = __('Newbie', 'cp');
						$logos[0] = '';
						update_option('cp_module_ranks_data', $ranks);
						update_option('cp_module_ranks_logo',$logos);
					}
					ksort($ranks);
					foreach($ranks as $points=>$rank){
					$logo = "<img style='height:20px;width:20px' src='$logos[$points]' alt='not available' />";
				?>
				<tr>
					<td><?php echo $rank; ?></td>
					<td style="text-align:center;"><?php echo $logo; ?></td>
					<td style="text-align:center;"><?php echo $points; ?></td>
					<td>
						<form method="post" name="cp_ranks_action_remove_<?php echo $points; ?>" style="display:inline;">
							<input type="hidden" name="cp_rank_remove" value="<?php echo $points; ?>" />
							<a href="javascript:void(0);" onclick="document.cp_ranks_action_remove_<?php echo $points; ?>.submit();"><?php _e('Remove'); ?></a>
						</form>
					</td>
				</tr>
				<?php
					}
				?>
		</table>
		
		<form name="cp_module_ranks_data_form" method="post">
			<input type="hidden" name="cp_module_ranks_data_form_submit" value="Y" />

		<h3><?php _e('Add Rank','cp'); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="cp_module_ranks_data_rank"><?php _e('Rank Name', 'cp'); ?>:</label></th>
				<td valign="middle"><input type="text" id="cp_module_ranks_data_rank" name="cp_module_ranks_data_rank" value="<?php echo get_option('cp_module_ranks_data_rank'); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cp_module_ranks_data_points"><?php _e('Points to reach this rank', 'cp'); ?>:</label></th>
				<td valign="middle"><input type="text" id="cp_module_ranks_data_points" name="cp_module_ranks_data_points" value="<?php echo get_option('cp_module_ranks_data_points'); ?>" size="40" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cp_module_ranks_logo"><?php _e('Rank Logo (link)', 'cp'); ?>:</label></th>
				<td colspan="2" valign="middle"><input type="text" id="cp_module_ranks_logo" name="cp_module_ranks_logo" value="" size="40" />
				<input type="button" id="cp_module_media_button" value="From Media Library" />
				<br/>
				<small> Insert the link of rank's logo Or let wordpress handle this. Just Click <span style="color:blue">From Media Library </span></small> 
				</td>
				
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Add Rank','cp'); ?>" />
		</p>
	</form>
	</div>
	<?php
	}

	function cp_module_ranks_getRank($uid){
		return cp_module_ranks_pointsToRank(cp_getPoints($uid));
	}
	
	function cp_module_ranks_pointsToRank($points){
		$ranks = get_option('cp_module_ranks_data');
		ksort($ranks);
		$ranks = array_reverse($ranks, 1);
		foreach($ranks as $p=>$r){
			if($points>=$p){
				return $r;
			}
		}
	}
	
	function cp_module_ranks_widget(){
		if(is_user_logged_in()){
			?>
				<li><?php _e('Rank', 'cp'); ?>: <?php echo cp_module_ranks_getRank(cp_currentUser()); ?></li>
			<?php
		}
	}

	add_action('cp_pointsWidget', 'cp_module_ranks_widget');
        
        // function to get the rank logo in the entire scripts
            function cp_module_ranks_getLogo($uid){
                    $points = cp_getPoints($uid);
                    $rank_logos = get_option('cp_module_ranks_logo');
                    ksort($rank_logos);
                    $rank_logos = array_reverse($rank_logos,1);
                    foreach($rank_logos as $key=>$value){
                            if($points >= $key){
                                    return $value;
                            }
                    }
            }
	
}
?>
