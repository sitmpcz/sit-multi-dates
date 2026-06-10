<?php
/*
Plugin Name: SIT Special Multi Dates
Plugin URI:
Description: Měl by být aktivní ACF plugin
Version: 1.2.1
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

// Verze databazoveho schematu (pro migrace)
if ( !defined( 'SITMD_DB_VERSION' ) ) {
    define( 'SITMD_DB_VERSION', '1.2.1' );
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
                duration int(11) DEFAULT '0' NOT NULL,
                fromto_only int(1) DEFAULT '0' NOT NULL,
                PRIMARY KEY (date_id),
                KEY post_id (post_id),
                KEY fromto_post_date (fromto_only, post_id, date_time)
           	) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

        } else {

            // Migrace: pridat sloupec duration (delka trvani v minutach), pokud chybi
            $has_duration = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'duration'" );
            if ( empty( $has_duration ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN duration int(11) DEFAULT 0 NOT NULL AFTER date_time" );
            }

            // Migrace: indexy pro vypisove dotazy (tabulka casem roste)
            if ( empty( $wpdb->get_results( "SHOW INDEX FROM {$table_name} WHERE Key_name = 'post_id'" ) ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD INDEX post_id (post_id)" );
            }
            if ( empty( $wpdb->get_results( "SHOW INDEX FROM {$table_name} WHERE Key_name = 'fromto_post_date'" ) ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD INDEX fromto_post_date (fromto_only, post_id, date_time)" );
            }

        }

        update_option( 'sitmd_db_version', SITMD_DB_VERSION );

    }

}

// Cesta k pluginu
if ( !defined('SITMD_PLUGIN_PATH') ) {
    define( 'SITMD_PLUGIN_PATH', plugin_dir_url( __FILE__ ) );
}

// Vendor JS - jen na editaci akce (jinde metabox neexistuje)
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( !in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( !$screen || $screen->post_type !== 'events' ) {
        return;
    }
    wp_enqueue_style( 'sitmd', SITMD_PLUGIN_PATH  . 'assets/main.css', [], filemtime( plugin_dir_path(__FILE__) . 'assets/main.css' ) );
    wp_enqueue_script('sitmd-vendor', SITMD_PLUGIN_PATH . 'assets/vendor.min.js', [], '', true);
    wp_enqueue_script('sitmd', SITMD_PLUGIN_PATH . 'assets/core.js', [ 'sitmd-vendor' ], filemtime( plugin_dir_path(__FILE__) . 'assets/core.js' ), true);
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

    // Skryte pole serializujeme jako "datum|hodiny,datum|hodiny,..." (stejny format jako produkuje core.js)
    $pairs = array_map(
        function ( array $d ):string {
            return $d['date'] . '|' . ( $d['duration_hours'] === '' ? '0' : $d['duration_hours'] );
        },
        $dates
    );
    $dates_string = implode( ',', $pairs );

    $sitmd_fromto_only = get_post_meta( $post->ID, 'sitmd_fromto_only', true );

    require_once __DIR__ . "/views/meta-box.php";
}

// Save fields
add_action( 'save_post_events', function( $post_id ) {

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
    // Tohle tady nemusi byt pokud volame 'save_post_events'
    if ( 'events' === $_POST['post_type'] ) {
        // Stacilo by to odsud
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
    elseif ( '' === $new_dates && empty( $old_dates ) ) {
        sitmd_delete_dates();
    }

    return $post_id;
} );

function sitmd_sort_dates( array $dates ):array {

    // Seradime podle data ($dates jsou pole ['date_time'=>..., 'duration'=>minuty])
    usort(
        $dates,
        function ( array $a, array $b ):int {
            return strcmp( $a['date_time'], $b['date_time'] );
        }
    );

    // Naformatujeme pro vystup: datum pro input, delka v hodinach (prazdne = nezadano)
    return array_map(
        function ( array $row ):array {
            $date    = new \DateTimeImmutable( $row['date_time'] );
            $minutes = (int) $row['duration'];
            return [
                'date'           => $date->format( "Y-m-d H:i" ),
                'duration_hours' => $minutes > 0 ? rtrim( rtrim( number_format( $minutes / 60, 2, '.', '' ), '0' ), '.' ) : '',
            ];
        },
        $dates
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
            $dates[] = [
                'date_time' => $row->date_time,
                'duration'  => isset( $row->duration ) ? (int) $row->duration : 0,
            ];
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

    // Polozky maji format "datum|hodiny" (hodiny mohou chybet u starych dat)
    $items = explode( ',', $dates_string );

    foreach ( $items as $item ) {
        if ( $item === '' ) {
            continue;
        }
        $parts   = explode( '|', $item );
        $date    = $parts[0];
        $hours   = isset( $parts[1] ) ? (float) str_replace( ',', '.', $parts[1] ) : 0;
        $minutes = (int) round( $hours * 60 );
        $wpdb->insert( $table_name, [
            "post_id"     => $post->ID,
            "date_time"   => $date,
            "duration"    => $minutes,
            "fromto_only" => $ft_only,
        ] );
    }
}

function sitmd_delete_dates():void {

    global $post, $wpdb;

    // Smazeme vsechny datumy spojene s timto post_id
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}sit_multidates WHERE post_id = %s",
            $post->ID
        )
    );
    // A smazeme i meta jestli jde o datumy OD/DO
    delete_post_meta( $post->ID, 'sitmd_fromto_only', null );

}
