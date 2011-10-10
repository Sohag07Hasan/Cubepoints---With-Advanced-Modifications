<?php

/** Points for Publishing of Custom Post Type Module */
cp_module_register(
	__( 'Custom Post Type Points', 'cp' ),
	'customposttypepoints',
	'1.1',
	'dbm(main) & Mahibul Hasan(editing)',
	'http://sohag07hasan.elance.com',
	'http://www.merovingi.com',
	__('This module awards points when publishing a custom post type item. Requires WordPress 3.0+', 'cp' ), 1 );

if ( cp_module_activated( 'customposttypepoints' ) ) {

	/** Module Configuration */
	add_action( 'cp_config_form', 'cp_customtype_admin_setting' );
	function cp_customtype_admin_setting() {
		echo '
		<br />
		<h3>Custom Post Type</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="cp_customtype_used">Custom Post Type:</label></th>
				<td valign="middle"><input type="text" id="cp_customtype_used" name="cp_customtype_used" value="' . get_option( 'cp_customtype_used' ) . '" size="30" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cp_customtype_points">Points for new custom post type:</label></th>
				<td valign="middle"><input type="text" id="cp_customtype_points" name="cp_customtype_points" value="' . get_option( 'cp_customtype_points' ) . '" size="30" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="cp_wiki_edit_points">Points for Editing an article:</label></th>
				<td valign="middle"><input type="text" id="cp_wiki_edit_points" name="cp_wiki_edit_points" value="' . get_option( 'cp_wiki_edit_points' ) . '" size="30" /></td>
			</tr>
		</table>' . "\n";
	}

	/** Save Module Congif */
	add_action( 'cp_config_process', 'cp_customtype_save' );
	function cp_customtype_save() {
		update_option( 'cp_customtype_used', $_POST['cp_customtype_used'] );
		update_option( 'cp_customtype_points', (int)$_POST['cp_customtype_points'] );
		update_option('cp_wiki_edit_points',(int)$_POST['cp_wiki_edit_points']);
	}

	/** When a item is published we want to add points */
	add_action( 'publish_' . get_option( 'cp_customtype_used' ), 'cp_module_customtype_publish_points' );
	function cp_module_customtype_publish_points( $post_id ) {		
		// get post id from the revision id
		if ( $parent_id = wp_is_post_revision( $post_id ) ) $post_id = $parent_id;

		// verify if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

		// verify that we are publishing the custom post type
		// this should never fail since the action should always be 'publish_$post_type'
		if ( get_post_type( $post_id ) != get_option( 'cp_customtype_used' ) ) return $post_id;

		// cehcking if the post is edited or newly created
		$post = get_post( $post_id );		
		$post_editors = get_post_meta($post_id,'wiki_editors',true);
		if(!$post_editors) {$post_editors = array(); }
		global $current_user;
		get_current_user();
		//var_dump($post_editors);
				
		if(in_array($post->post_author,$post_editors)){
			//echo 'the post is edited by current user';			
			if(!in_array($current_user->ID,$post_editors)){
				
				cp_points( get_option( 'cp_customtype_used' ), $current_user->ID, get_option( 'cp_wiki_edit_points' ), $post_id );
				$post_editors[] = $current_user->ID;
				update_post_meta($post_id,'wiki_editors',$post_editors);
				
			}
		}
		else{
			//echo 'the post is freshly created';
			cp_points( get_option( 'cp_customtype_used' ), $post->post_author, get_option( 'cp_customtype_points' ), $post_id );
			$post_editors[] = $post->post_author;
			update_post_meta($post_id,'wiki_editors',$post_editors);
		}	
			
	}

	/** Adjust the Log. Overwrite custom post type entries. */
	add_action( 'cp_logs_description', 'cp_admin_logs_desc_customposttype', 10, 4 );
	function cp_admin_logs_desc_customposttype( $type, $uid, $points, $data ) {
		// Grab details about the post type and the post itself.
		$post = get_post( $data );
		$obj = get_post_type_object( $type );
		// IF the type is not the custom type we set, bail.
		if ( $type != get_option( 'cp_customtype_used' ) )
			return;
		else
			echo 'Points given for ' . $obj->labels->singular_name . ':' . ' "<a href="' . get_permalink( $post ) . '">' . $post->post_title . '</a>"';
	}
}

?>
