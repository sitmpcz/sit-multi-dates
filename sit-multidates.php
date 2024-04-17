<?php
/*
Plugin Name: SIT Special Multi Dates
Plugin URI:
Description: Měl by být aktivní ACF plugin
Version: 1.0.9
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

// Setup
if ( !function_exists('SitMultidatesPluginSetup' ) ) {

    add_action( 'init', 'SitMultidatesPluginSetup' );

    register_activation_hook( __FILE__, 'SitMultidatesPluginSetup' );

    function SitMultidatesPluginSetup() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'sit_multidates';

        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
		        date_id mediumint(9) NOT NULL AUTO_INCREMENT,
		        post_id mediumint(9) NOT NULL,
                date_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (date_id)
           	) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

        }

    }

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

// Display dates on admin page
function j3w_show_special_dates_meta_box():void {

    global $post;

    $dates = sitmd_get_dates();
    if ( $dates ) {
        $dates = sitmd_sort_dates( $dates );
    }

    $dates_string = implode( ',', $dates );

    $sitmd_fromto_only = get_post_meta( $post->ID, 'sitmd_fromto_only', true );

    require_once __DIR__ . "/views/meta-box.php";
}

// Save fields
add_action( 'save_post', function( $post_id ) {

    global $post;

    // verify nonce
    if ( !wp_verify_nonce( $_POST['sit_special_dates_nonce'], basename( SITMD_PLUGIN_PATH ) ) ) {
        return $post_id;
    }
    // Tohle asi nefugnuje nebo to nechapu
    // check autosave
    if ( wp_is_post_autosave( $post_id ) ) {
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

    $old_sitmd_fromto_only = get_post_meta( $post->ID, 'sitmd_fromto_only', true );
    $new_sitmd_fromto_only = $_POST['sitmd_fromto_only'];

    if ( $new_sitmd_fromto_only && $new_sitmd_fromto_only !== $old_sitmd_fromto_only) {
        update_post_meta( $post_id, 'sitmd_fromto_only', $new_sitmd_fromto_only );
    } elseif ( '' == $new_sitmd_fromto_only && $old_sitmd_fromto_only ) {
        delete_post_meta( $post_id, 'sitmd_fromto_only', $old_sitmd_fromto_only );
    }

    $old_dates = sitmd_get_dates();
    $new_dates = $_POST['sitmd_dates'];

    if ( $new_dates && $new_dates != $old_dates ) {
        sitmd_update_dates( $new_dates );
    }
    elseif ( '' === $new_dates && $old_dates ) {
        // Delete?
    }

    return $post_id;
} );

function sitmd_sort_dates( array $dates ):array {

    $d = array_map(
        function ( string $date_string ) {
            return new \DateTimeImmutable( $date_string );
        },
        $dates
    );
    // Sort
    sort( $d ); // Use rsort() for descending order
    // Format
    return array_map(
        function ( \DateTimeImmutable $date ) {
            return $date->format( "Y-m-d H:i" );
        },
        $d
    );
}

function sitmd_get_dates():array {

    global $post, $wpdb;

    $dates = [];

    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sit_multidates WHERE post_id = %s",
            $post->ID
        )
    );

    if ( $result ) {
        foreach ( $result as $row ) {
            $dates[] = $row->date_time;
        }
    }

    return $dates;
}

function sitmd_update_dates( string $dates_string ):void {

    global $post, $wpdb;

    $table_name = $wpdb->prefix . "sit_multidates";

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}sit_multidates WHERE post_id = %s",
            $post->ID
        )
    );

    $sitmd_fromto_only = get_post_meta( $post->ID, 'sitmd_fromto_only', true );
    $ft_only = $sitmd_fromto_only == 1 ? 1 : 0;

    $dates = explode( ',', $dates_string );

    if ( $dates ) {
        foreach ( $dates as $date ) {
            $wpdb->insert( $table_name, [ "post_id" => $post->ID, "date_time" => $date, "fromto_only" => $ft_only ] );
        }
    }
}
