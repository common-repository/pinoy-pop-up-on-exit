<?php
/*
Plugin Name: Pinoy Pop-up on Exit
Plugin URI: http://www.99webmasters.com
Description: Unblockable and customizable pop-up on exit on homepage and/or INDIVIDUAL post/page.
Author: Stanley Dumanig
Version: 1.0
Author URI: http://www.pinoyinternetbusiness.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

if ( !class_exists('PopupCustomFields') ) {

	class PopupCustomFields {
		var $popPrefix = 'popexit_';
		var $customFields =	array(
			array(
				"name"			=> "redirect-url",
				"title"			=> "Redirect URL",
				"description"	=> "Full URL path to where you want people to be redirected.",
				"type"			=> "text",
				"scope"			=>	array( "post" , "page" ),
				"capability"	=> "edit_pages", "edit_posts"
			),
			array(
				"name"			=> "message",
				"title"			=> "Alert Box Message",
				"description"	=> "Alert message that people will see when they try to exit the page.",
				"type"			=> "textarea",
				"scope"			=>	array( "post" , "page" ),
				"capability"	=> "edit_pages", "edit_posts"
			)
		);

		function PopupCustomFields() { $this->__construct(); }

		function __construct() {
			add_action( 'admin_menu', array( &$this, 'createCustomFields' ) );
			add_action( 'save_post', array( &$this, 'saveCustomFields' ), 1, 2 );
			add_action( 'do_meta_boxes', array( &$this, 'removeDefaultCustomFields' ), 10, 3 );
		}

		function removeDefaultCustomFields( $type, $context, $post ) {
			foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
				remove_meta_box( 'postcustom', 'post', $context );
				remove_meta_box( 'postcustom', 'page', $context );
			}
		}

		function createCustomFields() {
			if ( function_exists( 'add_meta_box' ) ) {
				add_meta_box( 'my-custom-fields', 'Popup on Exit Options', array( &$this, 'displayCustomFields' ), 'page', 'normal', 'high' );
				add_meta_box( 'my-custom-fields', 'Popup on Exit Options', array( &$this, 'displayCustomFields' ), 'post', 'normal', 'high' );
			}
		}

		function displayCustomFields() {
			global $post;
			?>
			<div class="form-wrap">
				<?php
				wp_nonce_field( 'my-custom-fields', 'my-custom-fields_wpnonce', false, true );
				foreach ( $this->customFields as $customField ) {
					$scope = $customField[ 'scope' ];
					$output = false;
					foreach ( $scope as $scopeItem ) {
						switch ( $scopeItem ) {
							case "post": {
								if ( basename( $_SERVER['SCRIPT_FILENAME'] )=="post-new.php" || $post->post_type=="post" )
									$output = true;
								break;
							}
							case "page": {
								if ( basename( $_SERVER['SCRIPT_FILENAME'] )=="page-new.php" || $post->post_type=="page" )
									$output = true;
								break;
							}
						}
						if ( $output ) break;
					}
					if ( !current_user_can( $customField['capability'], $post->ID ) )
						$output = false;
					if ( $output ) { ?>
						<div class="form-field form-required">
							<?php
							switch ( $customField[ 'type' ] ) {
								case "textarea": {
									// Text area
									echo '<label for="' . $this->popPrefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
									echo '<textarea name="' . $this->popPrefix . $customField[ 'name' ] . '" id="' . $this->popPrefix . $customField[ 'name' ] . '" columns="30" rows="3">' . htmlspecialchars( get_post_meta( $post->ID, $this->popPrefix . $customField[ 'name' ], true ) ) . '</textarea>';
									break;
								}
								default: {
									echo '<label for="' . $this->popPrefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
									echo '<input type="text" name="' . $this->popPrefix . $customField[ 'name' ] . '" id="' . $this->popPrefix . $customField[ 'name' ] . '" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->popPrefix . $customField[ 'name' ], true ) ) . '" />';
									break;
								}
							}
							?>
							<?php if ( $customField[ 'description' ] ) echo '<p>' . $customField[ 'description' ] . '</p>'; ?>
						</div>
					<?php
					}
				} ?>
			</div>
			<?php
		}

		function saveCustomFields( $post_id, $post ) {
			if ( !wp_verify_nonce( $_POST[ 'my-custom-fields_wpnonce' ], 'my-custom-fields' ) )
				return;
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			if ( $post->post_type != 'page' && $post->post_type != 'post' )
				return;
			foreach ( $this->customFields as $customField ) {
				if ( current_user_can( $customField['capability'], $post_id ) ) {
					if ( isset( $_POST[ $this->popPrefix . $customField['name'] ] ) && trim( $_POST[ $this->popPrefix . $customField['name'] ] ) ) {
						update_post_meta( $post_id, $this->popPrefix . $customField[ 'name' ], $_POST[ $this->popPrefix . $customField['name'] ] );
					} else {
						delete_post_meta( $post_id, $this->popPrefix . $customField[ 'name' ] );
					}
				}
			}
		}

	} // End Class

} // End if class exists statement

// Instantiate the class
if ( class_exists('PopupCustomFields') ) {
	$PopupCustomFields_var = new PopupCustomFields();
}

function show_pinoy_popup() {
global $post;
$popURL = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	if(get_post_meta($post->ID, "popexit_redirect-url", $single = true) != "") {
		$escaped_value = str_replace(chr(13),'\n',htmlspecialchars_decode(get_post_meta($post->ID, "popexit_message", $single = true)));
		$escaped_value = str_replace(chr(10),'',$escaped_value);
		echo '<script language="javascript">'. "\n";
		$stringReady = str_replace(array("<br/>", "<br>", "<br />"), "\n", $escaped_value);
		echo 'var popmsg = "' . str_replace('"', '\"', $stringReady ) . '";' . "\n";
		echo 'var redirectURL = "'. htmlspecialchars_decode(get_post_meta($post->ID, "popexit_redirect-url", $single = true)) . '";' . "\n";
		echo '</script>'. "\n";
		echo '<script language="javascript" src="' . $popURL . 'popup.js"></script>' . "\n";
	}
}
add_action("wp_footer","show_pinoy_popup");
?>