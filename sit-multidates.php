<?php
/*
Plugin Name: SIT Special Multi Dates
Plugin URI:
Description: Měl by být aktivní ACF plugin
Version: 1.0.2
Author: Jaroslav Dvorak
Author URI:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-j3w-multidate
Domain Path: /lang/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Cesta k pluginu
if ( !defined('SITMD_PLUGIN_PATH') ) {
	define( 'SITMD_PLUGIN_PATH', plugin_dir_url( __FILE__ ) );
}

// Vendor JS
add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'sitmd', SITMD_PLUGIN_PATH  . 'assets/main.css', [], filemtime( SITMD_PLUGIN_PATH  . 'assets/main.css' ) );
	wp_enqueue_script('sitmd-vendor', SITMD_PLUGIN_PATH . 'assets/vendor.min.js', '', '', true);
	wp_enqueue_script('sitmd', SITMD_PLUGIN_PATH . 'assets/core.js', 'jquery', filemtime( SITMD_PLUGIN_PATH . 'assets/core.js' ), true);
} );

// Register Meta Box
add_action( 'add_meta_boxes', function():void {

	add_meta_box(
		'sit_special_dates', // $id
		'Datumy', // $title
		'j3w_show_special_dates_meta_box', // $callback
		'events', // $screen
		'side', // $context
		'high' // $priority
	);

} );

// Display fields
function j3w_show_special_dates_meta_box():void {

	global $post;

	$sitmd_from = get_post_meta( $post->ID, 'sitmd_from', true );
	$sitmd_to = get_post_meta( $post->ID, 'sitmd_to', true );

	$sitmd_other = get_post_meta( $post->ID, 'sitmd_other', true );
	// Array to template
	if ( $sitmd_other != '' ) {
		$sitmd_other_sorted = sitmd_sort_dates( $sitmd_other );
		$sitmd_other_dates = explode( ",", $sitmd_other_sorted );
	}
	else {
		$sitmd_other_dates = []; // Empty array
	}

	require_once __DIR__ . "/views/meta-box.php";

}

// Save fields
add_action( 'save_post', function( $post_id ) {

	// verify nonce
	if ( !wp_verify_nonce( $_POST['sit_special_dates_nonce'], basename( SITMD_PLUGIN_PATH ) ) ) {
		return $post_id;
	}
	// Tohle asi nefugnuje nebo to nechapu
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// check permissions
	if ( 'events' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
	}

	$old_from = get_post_meta( $post_id, 'sitmd_from', true );
	$new_from = $_POST['sitmd_from'];

	if ( $new_from && $new_from !== $old_from ) {
		update_post_meta( $post_id, 'sitmd_from', $new_from );
	} elseif ( '' === $new_from && $old_from ) {
		delete_post_meta( $post_id, 'sitmd_from', $old_from );
	}

	$old_to = get_post_meta( $post_id, 'sitmd_to', true );
	$new_to = $_POST['sitmd_to'];

	if ( $new_to && $new_to !== $old_to ) {
		update_post_meta( $post_id, 'sitmd_to', $new_to );
	} elseif ( '' === $new_to && $old_to ) {
		delete_post_meta( $post_id, 'sitmd_to', $old_to );
	}

	$old_other = get_post_meta( $post_id, 'sitmd_other', true );
	$new_other = sitmd_sort_dates( (string)$_POST['sitmd_other'] );

	if ( $new_other && $new_other !== $old_other ) {
		update_post_meta( $post_id, 'sitmd_other', $new_other );
	} elseif ( '' === $new_other && $old_other ) {
		delete_post_meta( $post_id, 'sitmd_other', $old_other );
	}

} );

function sitmd_sort_dates( string $dates ):string {

	if ( $dates != '' ) {
		// Cretae array
		$dates_arr = explode( ",", $dates );

		$dates = array_map(
			function ( string $dateString ) {
				return new \DateTimeImmutable( $dateString );
			},
			$dates_arr
		);
		// Sort
		sort( $dates ); // Use rsort() for descending order
		// Format
		$dates_arr = array_map(
			function ( \DateTimeImmutable $date ) {
				return $date->format( "Y-m-d" );
			},
			$dates
		);

		return implode( ",", $dates_arr );
	}

	return $dates;

}
