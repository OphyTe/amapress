<?php
/**
 * @package Amapress
 */
/*
Plugin Name: Amapress
Plugin URI: http://amapress.fr/
Description: 
Version: 0.88.90
Requires PHP: 5.6
Requires WP: 4.4
Author: ShareVB
Author URI: http://amapress.fr/
License: GPLv2 or later
Text Domain: amapress
GitHub Plugin URI: comptoirdesappli/amapress
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2015-2017 ShareVB
*/

// Make sure we don't expose any info if called directly

if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'AMAPRESS__MINIMUM_WP_VERSION', '4.4' );
define( 'AMAPRESS_MINIMUM_PHP_VERSION', '5.6' );
define( 'AMAPRESS__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AMAPRESS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMAPRESS__PLUGIN_FILE', __FILE__ );
define( 'AMAPRESS_DELETE_LIMIT', 100000 );
define( 'AMAPRESS_DB_VERSION', 86 );
define( 'AMAPRESS_VERSION', '0.88.90' );
//remove_role('responable_amap');

function amapress_ensure_no_cache() {
	if ( ! defined( 'DONOTCACHEPAGE ' ) ) {
		define( 'DONOTCACHEPAGE ', true );
	}
}

require_once AMAPRESS__PLUGIN_DIR . 'vendor/autoload.php';

function amapress_get_github_updater_url() {
	return is_multisite() ? network_admin_url( 'settings.php?page=github-updater' ) : admin_url( 'options-general.php?page=github-updater' );
}

function amapress_wp_mail( $to, $subject, $message, $headers = '', $attachments = array(), $cc = null, $bcc = null ) {
	$subject = wp_specialchars_decode( $subject );
	if ( empty( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	if ( is_array( $to ) ) {
		$to = implode( ', ', $to );
	}
	if ( empty( $headers ) ) {
		$headers = array();
	}
	if ( is_string( $headers ) ) {
		$headers = str_replace( "\r", '', $headers );
		$headers = explode( "\n", $headers );
	}
	$headers   = array_map( function ( $h ) {
		return trim( $h );
	}, $headers );
	$headers   = array_filter( $headers,
		function ( $h ) {
			return ! empty( $h ) && ! empty( trim( $h ) );
		} );
	$headers   = array_filter( $headers,
		function ( $h ) {
			return 0 !== stripos( $h, 'Content-Type' );
		} );
	$headers[] = 'Content-Type: text/html; charset=utf-8';
	if ( ! empty( $cc ) ) {
		$headers   = array_filter( $headers,
			function ( $h ) {
				return 0 !== stripos( $h, 'Cc' );
			} );
		$headers[] = 'Cc:' . implode( ', ', $cc );
	}
	if ( ! empty( $bcc ) ) {
		$headers   = array_filter( $headers,
			function ( $h ) {
				return 0 !== stripos( $h, 'Bcc' );
			} );
		$headers[] = 'Bcc:' . implode( ', ', $bcc );
	}
	if ( null == $attachments ) {
		$attachments = [];
	}
	global $amapress_send_mail_to;
	if ( ! empty( $amapress_send_mail_to ) ) {
		$h       = esc_html( var_export( $headers, true ) );
		$message = "Mail redirigé vers $amapress_send_mail_to pour test\nOriginal To : $to\nOriginal Headers: $h\n\n" . $message;
		$to      = $amapress_send_mail_to;
		$headers = array_filter( $headers, function ( $h ) {
			return strpos( $h, 'Cc:' ) === false && strpos( $h, 'Bcc:' ) === false;
		} );
	} elseif ( isset( $_GET['test_mail'] ) || Amapress::getOption( 'test_mail_mode' ) || defined( 'AMAPRESS_TEST_MAIL_MODE' ) ) {
		$h            = esc_html( var_export( $headers, true ) );
		$test_mode_to = Amapress::getOption( 'test_mail_target' );
		$message      = "Site en mode de test mail : tous les emails sortants sont redirigés vers $test_mode_to\nOriginal To : $to\nOriginal Headers: $h\n\n" . $message;
		$to           = $test_mode_to;
		$headers      = array_filter( $headers, function ( $h ) {
			return strpos( $h, 'Cc:' ) === false && strpos( $h, 'Bcc:' ) === false;
		} );
	}

	return wp_mail( $to, wp_kses_decode_entities( wp_unslash( $subject ) ), wptexturize( wpautop( wp_unslash( $message ) ) ), $headers, $attachments );
//    remove_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type', 50);
}

function amapress_dump( $v ) {
	echo '<pre>';
	var_dump( $v );
	echo '</pre>';
}

global $amapress_notices;
$amapress_notices = array();
function amapress_add_admin_notice( $message, $type, $is_dismissible, $escape = true ) {
	global $amapress_notices;
	$amapress_notices[] = amapress_get_admin_notice( $message, $type, $is_dismissible, $escape );
}

function amapress_get_admin_notice( $message, $type, $is_dismissible, $escape = true ) {
	$class = $is_dismissible ? "notice-$type is-dismissible" : "notice-$type";

	return sprintf( '<div class="notice %1$s"><p>%2$s</p></div>', esc_attr( $class ), ! $escape ? $message : esc_html( $message ) );
}

add_action( 'admin_notices', 'amapress_output_admin_notices' );
function amapress_output_admin_notices() {
	global $amapress_notices;

	foreach ( $amapress_notices as $notice ) {
		echo $notice;
	}
}


function amapress_debug_backtrace_summary( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
	if ( version_compare( PHP_VERSION, '5.2.5', '>=' ) ) {
		$trace = debug_backtrace( false );
	} else {
		$trace = debug_backtrace();
	}

	$caller      = array();
	$check_class = ! is_null( $ignore_class );
	$skip_frames ++; // skip this function

	foreach ( $trace as $call ) {
		if ( empty( $call['line'] ) ) {
			$call['line'] = '0';
		}
		if ( empty( $call['file'] ) ) {
			$call['file'] = 'unknown';
		}

		if ( $skip_frames > 0 ) {
			$skip_frames --;
		} elseif ( isset( $call['class'] ) ) {
			if ( $check_class && $ignore_class == $call['class'] ) {
				continue;
			} // Filter out calls

			$file = str_replace( array( WP_CONTENT_DIR, ABSPATH ), '', $call['file'] );

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}[{$file}:{$call['line']}]";
		} else {
			if ( in_array( $call['function'], array( 'do_action', 'apply_filters' ) ) ) {
				$caller[] = "{$call['function']}('{$call['args'][0]}')";
			} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ) ) ) {
				$caller[] = $call['function'] . "('" . str_replace( array(
						WP_CONTENT_DIR,
						ABSPATH
					), '', ! empty( $call['args'][0] ) ? $call['args'][0] : '#unk#' ) . "')";
			} else {
				$file     = str_replace( array( WP_CONTENT_DIR, ABSPATH ), '', $call['file'] );
				$caller[] = "{$call['function']}[{$file}:{$call['line']}]";
			}
		}
	}
	if ( $pretty ) {
		return join( ', ', array_reverse( $caller ) );
	} else {
		return $caller;
	}
}

function amapress_exception_error_handler( $errno, $errstr, $errfile, $errline, $errcontext ) {
	// handle @
	if ( 0 === error_reporting() ) {
		return false;
	}
	$message = $errstr . ' in ' . $errfile . ' on line ' . $errline .
	           ', backtrace: ' . amapress_debug_backtrace_summary( null, 1 ) .
	           ', url: ' . $_SERVER['REQUEST_URI'] .
	           ', user: ' . get_current_user_id();

	if ( strpos( $message, 'Load_Resend_Welcome_Email' ) !== false ) {
		return true;
	}

	if ( WP_DEBUG || ini_get( 'log_errors' ) ) {
		error_log( $message );
	}

	return true;
}

set_error_handler( 'amapress_exception_error_handler', E_ALL | E_STRICT );

function amapress_wpmail_content_type() {
	return 'text/html';
}

//add_filter( 'wp_mail_content_type', 'amapress_wpmail_content_type' );

function amapress_get_default_wordpress_from_email() {
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}

	return 'wordpress@' . $sitename;
}

//function amapress_add_replyto($args) {
//    if(!empty($args['headers'])) {
//        $headers = $args['headers'];
//        if (is_string($headers)) {
//            $headers = str_replace("\r", '', $headers);
//            $headers = explode("\n", $headers);
//        }
//        $reply_to_name = Amapress::getOption('email_replyto_name');
//        $reply_to_mail = Amapress::getOption('email_replyto_mail');
//        $has_replyto = false;
//        foreach ($headers as $h) {
//            if (strpos(strtolower($h), 'reply-to:') === 0)
//                $has_replyto=true;
//        }
//        if (!$has_replyto && !empty($reply_to_mail)) {
//            if (!empty($reply_to_name))
//                $headers[] = "Reply-to: $reply_to_name <$reply_to_mail>";
//            else
//                $headers[] = "Reply-to: $reply_to_mail";
//        }
//
//        $args['headers'] = $headers;
//    }
//    return $args;
//}
//add_filter('wp_mail', 'amapress_add_replyto');


if ( ! defined( 'AMAPRESS_CSV_DELIMITER' ) ) {
	define( 'AMAPRESS_CSV_DELIMITER', "," );
}
//define( 'WP_DEBUG', true );

register_activation_hook( __FILE__, array( 'Amapress', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Amapress', 'plugin_deactivation' ) );

function amapress__( $s ) {
	return __( $s, 'amapress' );
}

if ( ! defined( 'FREE_PAGES_PERSO' ) ) {
	require_once( AMAPRESS__PLUGIN_DIR . 'utils/install-github-updater.php' );
}

require_once( AMAPRESS__PLUGIN_DIR . 'titan-framework/titan-framework.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'state.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/options.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapressentities.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'impersonation.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapressmeseventswidget.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapresscopyleftwidget.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressmenuwidget.php');
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.users.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.paniers.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.distributions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.commandes.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress.contrats.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/class-amapress-event-base.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/class-amapress-calendar.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressadmin.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'class.amapressmembership.php');
//require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress-widget.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/include.entities.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'options/customizer.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'options/tabs.models.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'options/options.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/utils.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/register.php' );


require_once( AMAPRESS__PLUGIN_DIR . 'utils/Encoding.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/export-posts-to-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/export-users-to-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/import-posts-from-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/import-users-from-csv.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'utils/class.amapress-taxonomy.php' );

require_once( AMAPRESS__PLUGIN_DIR . 'entities/row.actions.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'entities/bulk.actions.php' );

function amapress_mail_from( $old ) {
	$new = Amapress::getOption( 'email_from_mail' );

	return empty( $new ) ? $old : $new;
}

function amapress_mail_from_name( $old ) {
	$new = Amapress::getOption( 'email_from_name' );

	return empty( $new ) ? $old : $new;
}

add_filter( 'wp_mail_from', 'amapress_mail_from' );
add_filter( 'wp_mail_from_name', 'amapress_mail_from_name' );

new AmapressUserTaxonomy();

function amapress_unsimplify_post_type( $post_type ) {
	$pts = AmapressEntities::getPostTypes();
	if ( array_key_exists( $post_type, $pts ) ) {
		return ( ! empty( $pts[ $post_type ]['internal_name'] ) ? $pts[ $post_type ]['internal_name'] : 'amps_' . $post_type );
	}

	return $post_type;
}

function amapress_simplify_post_type( $post_type ) {
	if ( is_array( $post_type ) ) {
		return array_map( 'amapress_simplify_post_type', $post_type );
	}

	switch ( $post_type ) {
		case 'page':
		case 'post':
		case 'user':
			return $post_type;
		case 'amps_lieu':
			return 'lieu_distribution';
		case 'amps_assemblee':
			return 'assemblee_generale';
		case 'amps_contrat_inst':
			return 'contrat_instance';
		case 'amps_contrat_quant':
			return 'contrat_quantite';
		case 'amps_cont_pmt':
			return 'contrat_paiement';
		case 'amps_adh_pmt':
			return 'adhesion_paiement';
		case 'amps_user_plike':
			return 'user_produit_like';
		case 'amps_inter_panier':
			return 'intermittence_panier';
//        case 'amps_inter_adhe':
//            return 'adhesion_intermittence';
		case 'amps_adh_req';
			return 'adhesion_request';
		case 'amps_adh_per';
			return 'adhesion_period';
		case 'amps_mailing':
			return 'mailinglist';
		case AmapressMailingGroup::INTERNAL_POST_TYPE:
			return AmapressMailingGroup::POST_TYPE;
	}
	if ( strpos( $post_type, 'amps_' ) === 0 ) {
		return substr( $post_type, 5 );
	} else {
		return $post_type;
	}
}

if ( ! function_exists( 'array_group_by' ) ) :
	/**
	 * Groups an array by a given key.
	 *
	 * Groups an array into arrays by a given key, or set of keys, shared between all array members.
	 *
	 * Based on {@author Jake Zatecky}'s {@link https://github.com/jakezatecky/array_group_by array_group_by()} function.
	 * This variant allows $key to be closures.
	 *
	 * @param array $array The array to have grouping performed on.
	 * @param mixed $key,... The key to group or split by. Can be a _string_,
	 *                       an _integer_, a _float_, or a _callable_.
	 *
	 *                       If the key is a callback, it must return
	 *                       a valid key from the array.
	 *
	 *                       ```
	 *                       string|int callback ( mixed $item )
	 *                       ```
	 *
	 * @return array|null Returns a multidimensional array or `null` if `$key` is invalid.
	 */

	function array_group_by( array $array, $key ) {
		if ( ! is_string( $key ) && ! is_int( $key ) && ! is_float( $key ) && ! is_callable( $key ) ) {
			trigger_error( 'array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR );

			return null;
		}

		$func = ( is_callable( $key ) ? $key : null );
		$_key = $key;

		// Load the new array, splitting by the target key
		$grouped = [];
		foreach ( $array as $value ) {
			if ( is_callable( $func ) ) {
				$key = call_user_func( $func, $value );
			} else {
				$key = $value[ $_key ];
			}

			$grouped[ $key ][] = $value;
		}

		// Recursively build a nested grouping if more parameters are supplied
		// Each grouped array value is grouped according to the next sequential key
		if ( func_num_args() > 2 ) {
			$args = func_get_args();

			foreach ( $grouped as $key => $value ) {
				$params          = array_merge( array( $value ), array_slice( $args, 2, func_num_args() ) );
				$grouped[ $key ] = call_user_func_array( 'array_group_by', $params );
			}
		}

		return $grouped;
	}
endif;

if ( ! function_exists( 'get_posts_count' ) ) {
	/**
	 * Retrieve count of latest posts or posts matching criteria.
	 *
	 */
	function get_posts_count( $args = null ) {
		$args                   = wp_parse_args( $args );
		$args['fields']         = 'ids';
		$args['no_found_rows']  = true;
		$args['posts_per_page'] = - 1;
		$posts                  = get_posts( $args );

		return count( $posts );
	}
}

//

if ( ! function_exists( 'get_users_cached' ) ) {
	/** @return WP_User[] */
	function get_users_cached( $args = array() ) {
		$args = wp_parse_args( $args );

		$query = serialize( $args );
		$res   = wp_cache_get( $query );
		if ( false === $res ) {
			$user_search = new WP_User_Query();
			$user_search->prepare_query( $args );
			$user_search->query();
			$res = $user_search->get_results();
			wp_cache_set( $query, $res );
		}

		return $res;
	}
}

if ( ! function_exists( 'get_users_count' ) ) {
	/**
	 * Retrieve count of users matching criteria.
	 *
	 */
	function get_users_count( $args = array() ) {
		$args                = wp_parse_args( $args );
		$args['count_total'] = false;

		$user_search = new WP_User_Query();
		$user_search->prepare_query( $args );

		global $wpdb;
		$query = "SELECT COUNT(DISTINCT $wpdb->users.ID) $user_search->query_from $user_search->query_where";
		$res   = wp_cache_get( $query );
		if ( false === $res ) {
			$res = intval( $wpdb->get_var( $query ) );
			wp_cache_set( $query, $res );
		}

		return $res;
	}
}

function array_mode( $arr ) {
	$values = array_count_values( $arr );

	return array_search( max( $values ), $values );
}

// register Foo_Widget widget

function amapress_register_widgets() {
	register_widget( 'Amapress_Next_Events_Widget' );
	register_widget( 'Amapress_Copyleft_Widget' );
//    register_widget('Amapress_Menu_Widget');
}

add_action( 'widgets_init', 'amapress_register_widgets' );
add_action( 'init', array( 'Amapress', 'init' ), 0 );
add_action( 'init', array( 'Amapress_Calendar', 'init' ) );
add_action( 'init', array( 'AmapressUsers', 'init' ) );
add_action( 'init', array( 'AmapressContrats', 'init' ) );
add_action( 'tf_create_options', array( 'Amapress', 'init_post_metaboxes' ) );
add_action( 'init', 'amapress_global_init', 15 );

//add_action('admin_page_access_denied', function() {
//    var_dump(amp_user_can_access_admin_page());
//    die('xdfsfsd');
//});

function amapress_global_init() {
	TitanFrameworkOptionAddress::$geoprovider = Amapress::getOption( 'geocode_provider' );
	$key                                      = Amapress::getOption( 'google_map_key' );
	if ( ! empty( $key ) ) {
		TitanFrameworkOptionAddress::$google_map_api_key = $key;
	}
	TitanFrameworkOptionAddress::$here_map_app_id   = Amapress::getOption( 'here_map_app_id' );
	TitanFrameworkOptionAddress::$here_map_app_code = Amapress::getOption( 'here_map_app_code' );

	global $amapress_smtpMailingQueue;
	require_once( AMAPRESS__PLUGIN_DIR . 'modules/mailqueue/AmapressSMTPMailingQueue.php' );
	$amapress_smtpMailingQueue = new AmapressSMTPMailingQueue();

	if ( ! wp_next_scheduled( 'amps_cleanings' ) ) {
		wp_schedule_event( time(), 'daily', 'amps_cleanings' );
	}
	add_action( 'amps_cleanings', function () {
		Amapress::cleanFilesOlderThanDays( Amapress::getAttachmentDir(), 3 * 30 );
		Amapress::cleanFilesOlderThanDays( Amapress::getContratDir(), 6 * 30 );
	} );

	if ( ! wp_next_scheduled( 'amps_updnotif' ) ) {
		wp_schedule_event( time(), 'weekly', 'amps_updnotif' );
	}

	add_action( 'amps_updnotif', function () {
		require_once AMAPRESS__PLUGIN_DIR . 'modules/updnotifier/AmapressUpdateNotifier.php';
		$message = AmapressUpdateNotifier::getUpdateMessage( 2 );
		if ( ! empty( $message ) ) {
			amapress_wp_mail( get_option( 'admin_email' ),
				'Mises à jour requises',
				wpautop( $message ) );
		}
	} );

	do_action( 'amapress_init' );

//    $users = get_users(
//        array('amapress_contrat' => 'no')
//    );
	//amapress_update_all_posts();
}

function amapress_get_avatar_meta_name() {
	global $wpdb, $blog_id;

	return $wpdb->get_blog_prefix( $blog_id ) . 'user_avatar';
}

/**
 * Get size information for all currently-registered image sizes.
 *
 * @return array $sizes Data for all currently-registered image sizes.
 * @uses   get_intermediate_image_sizes()
 * @global $_wp_additional_image_sizes
 */
function get_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}

/**
 * Get size information for a specific image size.
 *
 * @param string $size The image size for which to retrieve data.
 *
 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
 * @uses   get_image_sizes()
 *
 */
function get_image_size( $size ) {
	$sizes = get_image_sizes();

	if ( isset( $sizes[ $size ] ) ) {
		return $sizes[ $size ];
	}

	return false;
}

/**
 * Get the width of a specific image size.
 *
 * @param string $size The image size for which to retrieve data.
 *
 * @return bool|string $size Width of an image size or false if the size doesn't exist.
 * @uses   get_image_size()
 *
 */
function get_image_width( $size ) {
	if ( ! $size = get_image_size( $size ) ) {
		return false;
	}

	if ( isset( $size['width'] ) ) {
		return $size['width'];
	}

	return false;
}

/**
 * Get the height of a specific image size.
 *
 * @param string $size The image size for which to retrieve data.
 *
 * @return bool|string $size Height of an image size or false if the size doesn't exist.
 * @uses   get_image_size()
 *
 */
function get_image_height( $size ) {
	if ( ! $size = get_image_size( $size ) ) {
		return false;
	}

	if ( isset( $size['height'] ) ) {
		return $size['height'];
	}

	return false;
}

add_filter( 'post_thumbnail_html', function ( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
	$post_type = get_post_type( $post_id );

	if ( AmapressContrat::INTERNAL_POST_TYPE == $post_type || AmapressContrat_instance::INTERNAL_POST_TYPE == $post_type ) {
		if ( get_image_height( $size ) !== get_image_width( $size ) ) {
			return '';
		}
	}

	return $html;
}, 10, 5 );

add_action( 'updated_postmeta', 'amapress_updated_postmeta', 10, 4 );
function amapress_updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( $meta_key == '_thumbnail_id' && $meta_value ) {
		$att = get_post( $meta_value );
		if ( $att && ! $att->post_parent ) {
			wp_update_post( array(
				'ID'          => $att->ID,
				'post_parent' => $object_id,
			) );
		}
	}
}

function amapress_get_avatar_url( $id, $meta_name, $size, $default_image, $user = 0 ) {
	$key = "amapress_get_avatar_url_$id";
	$url = wp_cache_get( $key );
	if ( false === $url ) {
		if ( empty( $meta_name ) ) {
			$meta_name = '_thumbnail_id';
		}
		if ( $user ) {
			$avatar = get_user_meta( $id, $meta_name, true );
		} else {
			$avatar = get_post_meta( $id, $meta_name, true );
		}
		if ( ! $avatar ) {
			$meta_name = 'amapress_icon_id';
			if ( $user ) {
				$avatar = get_user_meta( $id, $meta_name, true );
			} else {
				$avatar = get_post_meta( $id, $meta_name, true );
			}
		}

		$url = null;
		if ( $avatar ) {
			if ( is_array( $avatar ) ) {
				$avatar = $avatar[0];
			}
			$url = wp_get_attachment_image_src( $avatar, $size );
			if ( $url ) {
				$url = $url[0];
			}
		}

		if ( empty( $url ) && ! empty( $default_image ) ) {
			$url = AMAPRESS__PLUGIN_URL . 'images/' . $default_image;
		}

		wp_cache_set( $key, $url );
	}

	return $url;
}

function amapress_get_font_icon( $icon_name, $add_class = '' ) {
	if ( strpos( $icon_name, 'fa-' ) === 0 ) {
		return '<i class="' . $add_class . ' fa ' . $icon_name . '" aria-hidden="true"></i>';
	} else if ( strpos( $icon_name, 'dashicons-' ) === 0 ) {
		return '<span class="' . $add_class . ' dashicons-before ' . $icon_name . '" aria-hidden="true"></span>';
	} else if ( strpos( $icon_name, 'flaticon-' ) === 0 ) {
		return '<span class="' . $add_class . ' dashicons-before ' . $icon_name . '" aria-hidden="true"></span>';
	} else {
		return $icon_name;
	}
}

function amapress_get_icon_html( $option_name, $default ) {
	$optVal = Amapress::getOption( $option_name );
	if ( empty( $optVal ) ) {
		$optVal = $default;
	}
	if ( empty( $optVal ) ) {
		return '';
	}

	return amapress_get_font_icon( $optVal );
}

function amapress_echo_img_thumb( $filename, $alt, $size ) {
	$src = AMAPRESS__PLUGIN_URL . 'images/' . $filename;
	echo "<img src='$src' alt='$alt' class='img_thumb_$size' />";
}

function amapress_get_post_field_as_text( $post_id, $post_type, $field_name ) {
	return esc_html( get_post_meta( $post_id, "amapress_{$post_type}_{$field_name}", true ) );
}

function amapress_get_post_field_as_html( $post_id, $post_type, $field_name ) {
	return get_post_meta( $post_id, "amapress_{$post_type}_{$field_name}", true );
}

function mc_admin_users_caps( $caps, $cap, $user_id, $args ) {

//    var_dump($cap);
	foreach ( $caps as $key => $capability ) {

		if ( $capability != 'do_not_allow' ) {
			continue;
		}

		switch ( $cap ) {
			case 'edit_user':
			case 'edit_users':
				$caps[ $key ] = 'edit_users';
				break;
			case 'delete_user':
			case 'delete_users':
				$caps[ $key ] = 'delete_users';
				break;
			case 'create_users':
				$caps[ $key ] = $cap;
				break;
		}
	}

	return $caps;
}

add_filter( 'map_meta_cap', 'mc_admin_users_caps', 1, 4 );
remove_all_filters( 'enable_edit_any_user_configuration' );
add_filter( 'enable_edit_any_user_configuration', '__return_true' );

/**
 * Checks that both the editing user and the user being edited are
 * members of the blog and prevents the super admin being edited.
 */
function mc_edit_permission_check() {
	global $current_user, $profileuser;

	$screen = get_current_screen();

//    die($screen->id);

	wp_get_current_user();

	if ( ! is_super_admin( $current_user->ID ) && in_array( $screen->base, array(
			'user-edit',
			'user-edit-network'
		) ) ) { // editing a user profile
		if ( is_super_admin( $profileuser->ID ) ) { // trying to edit a superadmin while less than a superadmin
			wp_die( __( 'You do not have permission to edit this user.' ) );
		} elseif ( ! ( is_user_member_of_blog( $profileuser->ID, get_current_blog_id() ) && is_user_member_of_blog( $current_user->ID, get_current_blog_id() ) ) ) { // editing user and edited user aren't members of the same blog
			wp_die( __( 'You do not have permission to edit this user.' ) );
		}
	}
}

if ( is_multisite() ) {
	add_filter( 'admin_head', 'mc_edit_permission_check', 1, 4 );
}


add_filter( 'user_has_cap', 'amapress_check_permissions', 10, 3 );
function amapress_check_permissions( $allcaps, $cap, $args ) {
//    var_dump($cap);
//    die();
//    var_dump($allcaps);
//    die();
	return $allcaps;
}

function amapress_unconfirmed_do_network_admin( $do_network_admin ) {
	return false;
}

add_filter( 'unconfirmed_do_network_admin', 'amapress_unconfirmed_do_network_admin' );

function amapress_check_info_visibility( $value, $name, AmapressUser $user ) {
	if ( 'force' == $value ) {
		return true;
	}
	if ( $value == 'default' || Amapress::toBool( $value ) ) {
		if ( $name == 'roles' ) {
			return true;
		}
		$user_right = $user->getDisplayRight( $name );
		if ( ! empty( $user_right ) && $user_right != 'default' ) {
			return Amapress::toBool( $user_right );
		}
		if ( $name == 'avatar' ) {
			return get_option( 'show_avatars' );
		}
		if ( $name == 'tel_mobile' ) {
			if ( Amapress::toBool( Amapress::getOption( "allow_show_resp_distrib_tel" ) )
			     && AmapressDistributions::isCurrentUserResponsableThisWeek( $user->ID )
			) {
				return true;
			}
		}

		$def = Amapress::getOption( "allow_show_$name" );
		if ( empty( $def ) || ! Amapress::toBool( $def ) ) {
			return amapress_can_access_admin() || AmapressDistributions::isCurrentUserResponsableThisWeek();
		} else {
			return true;
		}
	} else {
		return false;
	}
}


function amapress_bbp_enable_visual_editor( $args = array() ) {
	$args['tinymce']   = true;
	$args['quicktags'] = false;
	$args['teeny']     = false;

	return $args;
}

add_filter( 'bbp_after_get_the_content_parse_args', 'amapress_bbp_enable_visual_editor' );

// Sets the display name to first name and last name
add_filter( 'pre_user_display_name', 'amapress_default_display_name' );
function amapress_default_display_name( $name ) {
	global $pagenow;

	if ( 'user-edit.php' == $pagenow || 'user-new.php' == $pagenow || 'profile.php' == $pagenow ) {
		$firstname = null;
		if ( isset( $_POST['first_name'] ) ) {
			$firstname = sanitize_text_field( $_POST['first_name'] );
		}
		$lastname = null;
		if ( isset( $_POST['last_name'] ) ) {
			$lastname = sanitize_text_field( $_POST['last_name'] );
		}
		if ( ! empty( $firstname ) && ! empty( $lastname ) ) {
			$name = $firstname . ' ' . $lastname;
		}
	}

	return $name;
}

add_action( 'user_register', 'amapress_multisite_save_names', 10, 1 );
function amapress_multisite_save_names( $user_id ) {
	if ( is_multisite() ) {
		if ( isset( $_POST['first_name'] ) ) {
			update_user_meta( $user_id, 'first_name', $_POST['first_name'] );
		}
		if ( isset( $_POST['last_name'] ) ) {
			update_user_meta( $user_id, 'last_name', $_POST['last_name'] );
		}
	}
}

function amapress_multisite_createuser_noconfirm( $type ) {
	if ( is_multisite() ) {
// Load up the passed data, else set to a default.
		$creating = isset( $_POST['createuser'] );
//        $new_user_login = $creating && isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';
		$new_user_firstname = $creating && isset( $_POST['first_name'] ) ? wp_unslash( $_POST['first_name'] ) : '';
		$new_user_lastname  = $creating && isset( $_POST['last_name'] ) ? wp_unslash( $_POST['last_name'] ) : '';
//        $new_user_email = $creating && isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
		$new_user_uri = $creating && isset( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
//        $new_user_role = $creating && isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
		$new_user_send_notification = $creating && ! isset( $_POST['send_user_notification'] ) ? false : true;
//        $new_user_ignore_pass = $creating && isset( $_POST['noconfirmation'] ) ? wp_unslash( $_POST['noconfirmation'] ) : '';

		?>
        <table class="form-table">
            <tr class="form-field">
                <th scope="row"><label for="first_name"><?php _e( 'First Name' ) ?> </label></th>
                <td><input name="first_name" type="text" id="first_name"
                           value="<?php echo esc_attr( $new_user_firstname ); ?>"/></td>
            </tr>
            <tr class="form-field">
                <th scope="row"><label for="last_name"><?php _e( 'Last Name' ) ?> </label></th>
                <td><input name="last_name" type="text" id="last_name"
                           value="<?php echo esc_attr( $new_user_lastname ); ?>"/></td>
            </tr>
            <tr class="form-field">
                <th scope="row"><label for="url"><?php _e( 'Website' ) ?></label></th>
                <td><input name="url" type="url" id="url" class="code"
                           value="<?php echo esc_attr( $new_user_uri ); ?>"/>
                </td>
            </tr>
            <tr class="form-field form-required user-pass1-wrap">
                <th scope="row">
                    <label for="pass1">
						<?php _e( 'Password' ); ?>
                        <span class="description hide-if-js"><?php _e( '(required)' ); ?></span>
                    </label>
                </th>
                <td>
                    <input class="hidden" value=" "/><!-- #24364 workaround -->
                    <button type="button"
                            class="button wp-generate-pw hide-if-no-js"><?php _e( 'Show password' ); ?></button>
                    <div class="wp-pwd hide-if-js">
						<?php $initial_password = wp_generate_password( 24 ); ?>
                        <span class="password-input-wrapper">
                        <input type="password" name="pass1" id="pass1" class="regular-text" autocomplete="off"
                               data-reveal="1" data-pw="<?php echo esc_attr( $initial_password ); ?>"
                               aria-describedby="pass-strength-result"/>
                    </span>
                        <button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0"
                                aria-label="<?php esc_attr_e( 'Hide password' ); ?>">
                            <span class="dashicons dashicons-hidden"></span>
                            <span class="text"><?php _e( 'Hide' ); ?></span>
                        </button>
                        <button type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0"
                                aria-label="<?php esc_attr_e( 'Cancel password change' ); ?>">
                            <span class="text"><?php _e( 'Cancel' ); ?></span>
                        </button>
                        <div style="display:none" id="pass-strength-result" aria-live="polite"></div>
                    </div>
                </td>
            </tr>
            <tr class="form-field form-required user-pass2-wrap hide-if-js">
                <th scope="row"><label for="pass2"><?php _e( 'Repeat Password' ); ?> <span
                                class="description"><?php _e( '(required)' ); ?></span></label></th>
                <td>
                    <input name="pass2" type="password" id="pass2" autocomplete="off"/>
                </td>
            </tr>
            <tr class="pw-weak">
                <th><?php _e( 'Confirm Password' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="pw_weak" class="pw-checkbox"/>
						<?php _e( 'Confirm use of weak password' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Send User Notification' ) ?></th>
                <td>
                    <input type="checkbox" name="send_user_notification" id="send_user_notification"
                           value="1" <?php checked( $new_user_send_notification ); ?> />
                    <label
                            for="send_user_notification"><?php _e( 'Send the new user an email about their account.' ); ?></label>
                </td>
            </tr>
        </table>
		<?php
	}
	if ( ! is_super_admin() ) {
		?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Skip Confirmation Email' ); ?></th>
                <td>
                    <input type="checkbox" name="noconfirmation" id="adduser-noconfirmation"
                           value="1" <?php checked( isset( $_POST['noconfirmation'] ) ? $_POST['noconfirmation'] : 1, 1 ); ?> />
                    <label
                            for="adduser-noconfirmation"><?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label>
                </td>
            </tr>
        </table>
		<?php
	}
}

add_action( "user_new_form", "amapress_multisite_createuser_noconfirm" );

add_filter( 'wpmu_signup_user_notification', 'amapress_auto_activate_users', 10, 4 );
function amapress_auto_activate_users( $user, $user_email, $key, $meta ) {
	if ( ! current_user_can( 'create_users' ) ) {
		return false;
	}

	if ( ! empty( $_POST['noconfirmation'] ) && $_POST['noconfirmation'] == 1 ) {
		wpmu_activate_signup( $key );

		return false;
	}
}

/*
if ( is_admin() ) {
	require_once( AMAPRESS__PLUGIN_DIR . 'class.amapress-admin.php' );
	add_action( 'init', array( 'Amapress_Admin', 'init' ) );
}
*/

//add wrapper class around deprecated akismet functions that are referenced elsewhere
//require_once( AMAPRESS__PLUGIN_DIR . 'wrapper.php' );

function amapress_get_user_by_id_or_archived( $user_id ) {
	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		$user               = new WP_User();
		$user->ID           = $user_id;
		$user->user_email   = "archived$user_id@nomail.org";
		$user->first_name   = "Archived";
		$user->last_name    = $user_id;
		$user->display_name = "Archived $user_id";
		$user->user_login   = "archived$user_id";
	}

	return $user;
}

if ( ! function_exists( 'get_user_by' ) ) :
	function get_user_by( $field, $value ) {
		$key = "get_user_by_$field-$value";
		$res = wp_cache_get( $key );
		if ( false === $res ) {
			$userdata = WP_User::get_data_by( $field, $value );

			$not_found = false;
			if ( ! $userdata ) {
				if ( 'email' == $field ) {
					global $wpdb;
					if ( ! $user_id = $wpdb->get_var( $wpdb->prepare(
						"SELECT user_id FROM $wpdb->usermeta WHERE meta_key IN ('email2','email3','email4') AND meta_value = %s", $value
					) )
					) {
						$not_found = true;
					}

					$userdata = WP_User::get_data_by( 'ID', intval( $user_id ) );
					if ( ! $userdata ) {
						$not_found = true;
					}
				} else {
					$not_found = true;
				}
			}

			if ( $not_found ) {
				$res = null;
			} else {

				$user = new WP_User();
				$user->init( $userdata );

				$res = $user;
			}

			wp_cache_set( $key, $res );
		}

		if ( null === $res ) {
			return false;
		} else {
			return $res;
		}
	}
endif;

function amapress_user_contact_methods( $user_contact ) {
	// Add user contact methods
	$user_contact['email2'] = __( 'Email 2' );
	$user_contact['email3'] = __( 'Email 3' );
	$user_contact['email4'] = __( 'Email 4' );

	return $user_contact;
}

add_filter( 'user_contactmethods', 'amapress_user_contact_methods' );

function amapress_user_has_cap( $allcaps, $caps, $args ) {
	if ( ! isset( $args[0] ) ) {
		return $allcaps;
	}

	$cap = $args[0];
//    var_dump($cap);
	if ( isset( $args[2] ) && ( 'delete_users' == $cap || 'remove_users' == $cap || 'delete_user' == $cap || 'remove_user' == $cap ) ) {
		$allcaps[ $caps[0] ] = apply_filters( 'amapress_can_delete_user', true, $args[2] );
	} else if ( isset( $args[2] ) && ( 'edit_users' == $cap || 'edit_user' == $cap ) ) {
		//do nothing
	} else if ( isset( $args[2] ) && strpos( $cap, 'delete_' ) === 0 ) {
		if ( ! isset( $allcaps[ $cap ] ) || ! $allcaps[ $cap ] ) {
			return $allcaps;
		}

		$post_id = isset( $args[2] ) ? $args[2] : 0;

		$post_type = amapress_simplify_post_type( get_post_type( $post_id ) );

//        die($post_type);
		$allcaps[ $caps[0] ] = apply_filters( "amapress_can_delete_$post_type", true, $post_id );
//        $post_types = AmapressEntities::getPostTypes();
//        if (isset($post))
	} else if ( isset( $args[2] ) && strpos( $cap, 'edit_' ) === 0 ) {
		if ( ! isset( $allcaps[ $cap ] ) || ! $allcaps[ $cap ] ) {
			return $allcaps;
		}

		$post_id = isset( $args[2] ) ? $args[2] : 0;

		$post_type = amapress_simplify_post_type( get_post_type( $post_id ) );

//        die($post_type);
		$allcaps[ $caps[0] ] = apply_filters( "amapress_can_edit_$post_type", true, $post_id );
//        $post_types = AmapressEntities::getPostTypes();
//        if (isset($post))
	} else if ( isset( $args[1] ) ) {
		$user_id = isset( $args[1] ) ? $args[1] : amapress_current_user_id();
		$user    = AmapressUser::getBy( $user_id );
		if ( $user ) {
			$user_role_caps = $user->getAmapRoleCapabilities();
			if ( ! empty( $user_role_caps ) && $cap = "access_admin" ) {
				$allcaps[ $caps[0] ] = true;
			} else if ( in_array( $cap, $user_role_caps ) ) {
				$allcaps[ $caps[0] ] = true;
			}
		}
	}

//	if ('delete_post' == $cap)
//	    die('aa');
//	$allcaps[$cap] = false;
//    var_dump($cap);
//    var_dump($allcaps[$cap]);
//    if (isset($allcaps['publish_adhesions'])) var_dump($allcaps['publish_adhesions']);
//	$allcaps[$caps[0]] = true;
	return $allcaps;
}

add_filter( 'user_has_cap', 'amapress_user_has_cap', 10, 3 );

if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		/** @var AmapressSMTPMailingQueue $amapress_smtpMailingQueue */
		global $amapress_smtpMailingQueue;

		if ( empty( $to ) ) {
			throw new Exception( "Trying send email with empty To" );
		}
		if ( empty( $message ) ) {
			throw new Exception( "Trying send email with empty Content" );
		}

		if ( $amapress_smtpMailingQueue ) {
			return $amapress_smtpMailingQueue->wp_mail( $to, $subject, $message, $headers, $attachments );
		} else {
//            wp_mail($to, $subject, $message, $headers, $attachments);
			die( "Uh, no wp_mail ???" );
		}
	}
} else {
	amapress_add_admin_notice(
		'Un autre plugin a déjà remplacé la fonction de gestion des emails. Certaines fonctionnalités d\'Amapress pourraient ne pas fonctionner correctement.',
		'warning', true );
}

add_filter( 'is_protected_meta', function ( $protected, $meta_key = null ) {
	if ( strpos( $meta_key, 'amapress_' ) || strpos( $meta_key, 'amps_' ) ) {
		return true;
	}

	return $protected;
}, 10, 2 );

add_filter( 'password_reset_expiration', function ( $expiration ) {
	return floatval( Amapress::getOption( 'welcome-mail-expiration', '180' ) ) * 24 * 60 * 60;
} );

//add_filter('wp_insert_post_data', function ($data) {
//	amapress_dump($_POST);
//	amapress_dump($data);
//    die();
//});

//TODO : dont know why amps_adhesion does not publish...
add_action( 'save_post', function () {
	if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == 'amps_adhesion' && isset( $_POST['post_ID'] ) ) {
		wp_publish_post( $_POST['post_ID'] );
	}
} );


//add_filter('posts_distinct', function() {
//    return 'DISTINCT';
//},1);
//
//add_filter('posts_groupby', function($sql) {
//	return '';
//},1);

function get_posts_by_meta_query( $meta_query ) {
	global $wpdb;
	$meta_sql = get_meta_sql( $meta_query, 'post', $wpdb->posts, 'ID' );

	return $wpdb->get_col( "SELECT DISTINCT $wpdb->posts.ID FROM $wpdb->posts {$meta_sql['join']} WHERE 1=1 AND $wpdb->posts.post_status = 'publish' {$meta_sql['where']}" );
}

function amapress_display_post_types_nav_box() {
	$hidden_nav_boxes = get_user_option( 'metaboxhidden_nav-menus' );

	if ( empty( $hidden_nav_boxes ) ) {
		return;
	}

	$need_save = false;
	foreach (
		[
			'post-type-' . AmapressProducteur::INTERNAL_POST_TYPE,
			'post-type-' . AmapressContrat::INTERNAL_POST_TYPE,
			'post-type-' . AmapressLieu_distribution::INTERNAL_POST_TYPE,
			'post-type-' . AmapressProduit::INTERNAL_POST_TYPE,
			'post-type-' . AmapressRecette::INTERNAL_POST_TYPE,
			AmapressProduit::CATEGORY,
			AmapressRecette::CATEGORY,
		] as $post_type
	) {
		$post_type_nav_box = 'add-' . $post_type;

		if ( in_array( $post_type_nav_box, $hidden_nav_boxes ) ):
			$need_save = true;
			foreach ( $hidden_nav_boxes as $i => $nav_box ):
				if ( $nav_box == $post_type_nav_box ) {
					unset( $hidden_nav_boxes[ $i ] );
				}
			endforeach;
		endif;
	}
	if ( $need_save ) {
		update_user_option( get_current_user_id(), 'metaboxhidden_nav-menus', $hidden_nav_boxes );
	}
}

add_action( 'admin_init', 'amapress_display_post_types_nav_box' );

function amapress_feedback_footer() {
	if ( ! Amapress::getOption( 'feedback' ) ) {
		return;
	}

	$options = [
		'context'        => [
			'user' => amapress_is_user_logged_in() ? admin_url( 'user-edit.php?user_id=' . wp_get_current_user()->ID ) : 'Not logged',
		],
		'h2cPath'        => 'https://html2canvas.hertzen.com/dist/html2canvas.min.js',
		//plugins_url( '/js/html2canvas.min.js', __FILE__ ),
		'label'          => 'Feedback',
		'header'         => 'Envoyer un retour sur Amapress',
		'url'            => admin_url( 'admin-ajax.php?action=send_feedback' ),
		'action'         => 'send_feedback',
		'nextLabel'      => 'Continuer',
		'reviewLabel'    => 'Finaliser',
		'sendLabel'      => 'Envoyer',
		'closeLabel'     => 'Fermer',
		'messageSuccess' => 'Feedback envoyé avec succès',
		'messageError'   => "Une erreur s'est produite pendant l'envoi",
	];
	echo '<script type="text/javascript">
            jQuery(document).ready(function() {
                Feedback(' . wp_json_encode( $options ) . ');
            });      
    </script>';
}

add_action( 'wp_footer', 'amapress_feedback_footer', 9999 );
add_action( 'admin_footer', 'amapress_feedback_footer', 9999 );

function amapress_send_feedback() {
	wp_mail( 'support@amapress.fr', 'Feedback', var_export( $_POST, true ) );
	echo 'ok';
	wp_die();
}

add_action( 'wp_ajax_send_feedback', 'amapress_send_feedback' );
add_action( 'wp_ajax_nopriv_send_feedback', 'amapress_send_feedback' );

include AMAPRESS__PLUGIN_DIR . 'utils/CustomPostStatus.php';
CustomPostStatus::register( 'archived', [
	AmapressLieu_distribution::INTERNAL_POST_TYPE,
	AmapressContrat::INTERNAL_POST_TYPE,
	AmapressProducteur::INTERNAL_POST_TYPE,
	AmapressProduit::INTERNAL_POST_TYPE,
	AmapressRecette::INTERNAL_POST_TYPE
],
	array(
		'label'                     => _x( 'Archivé', 'post' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Archivé <span class="count">(%s)</span>', 'Archivés <span class="count">(%s)</span>' ),
	)
);

add_action( 'amapress_init', function () {
	require_once AMAPRESS__PLUGIN_DIR . 'modules/docspace/AmapressDocSpace.php';
	new AmapressDocSpace( 'responsables', 'edit_posts', 'edit_posts', 'edit_posts' );
	$subfolders = Amapress::getOption( 'docspace_resps_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			new AmapressDocSpace( 'responsables-' . $subfolder, 'edit_posts', 'edit_posts', 'edit_posts' );
		}
	}
	new AmapressDocSpace( 'amapiens', 'read', 'edit_posts', 'read' );
	$subfolders = Amapress::getOption( 'docspace_amapiens_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			new AmapressDocSpace( 'amapiens-' . $subfolder, 'read', 'edit_posts', 'read' );
		}
	}
	new AmapressDocSpace( 'public', '', 'edit_posts', '' );
	$subfolders = Amapress::getOption( 'docspace_public_folders' );
	if ( ! empty( $subfolders ) ) {
		$subfolders = trim( str_replace( ' ', '', $subfolders ) );
		foreach ( explode( ',', $subfolders ) as $subfolder ) {
			new AmapressDocSpace( 'public-' . $subfolder, '', 'edit_posts', '' );
		}
	}

	if ( Amapress::getOption( 'auto-post-thumb' ) ) {
		add_filter( 'get_post_metadata', function ( $value, $object_id, $meta_key, $single ) {
			if ( $meta_key !== '_thumbnail_id' || $value ) {
				return $value;
			}

			preg_match( '~<img[^>]+wp-image-(\\d+)~', get_post_field( 'post_content', $object_id ), $matches );
			if ( $matches ) {
				return $matches[1];
			}

			return $value;
		}, 10, 4 );
	}
} );

add_action( 'admin_init', function () {
	$contrat_to_generate = AmapressContrats::get_contrat_to_generate();
	if ( ! empty( $contrat_to_generate ) ) {
		amapress_add_admin_notice(
			'Les contrats suivants nécessitent une mise à jour : ' .
			implode( ', ', array_map( function ( $dn ) {
				/** @var AmapressContrat_instance $dn */
				$l      = admin_url( 'post.php?post=' . $dn->getID() . '&action=edit' );
				$tit    = esc_html( $dn->getTitle() );
				$status = '(' . AmapressContrats::contratStatus( $dn->getID(), 'span' ) . ')';

				return "<a href='{$l}' target='_blank'>{$tit}</a> {$status}";
			}, $contrat_to_generate ) ),
			'warning', false, false
		);
	}

	add_action( 'tf_custom_admin_amapress_action_test_convert_ws', function () {
		require_once 'utils/sample_pdf_convert.php';
		amapress_test_convertpdf_ws();
	} );
} );

if ( defined( 'WP_CORRECT_OB_END_FLUSH_ALL' ) ) {
	remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
}

if ( ! defined( 'AMAPRESS_ALLOW_XMLRPC' ) ) {
	add_action( "init", function () {
		global $pagenow; // get current page
		if ( ! empty( $pagenow ) && "xmlrpc.php" === $pagenow ) {
			header( "HTTP/1.1 403 Forbidden" ); // Produit une erreur 403
			exit; // exit request
		}

		return;
	} );
}

add_action( 'admin_init', function () {
	if ( current_user_can( 'manage_options' ) && ( ! wp_doing_ajax() ) ) {
		$dir_name = basename( dirname( __FILE__ ) );
		if ( 'amapress' != $dir_name ) {
			amapress_add_admin_notice( 'Le nom du dossier d\'Amapress doit être "amapress" pour le bon fonctionnement de la mise à jour par GitHub Updater (actuellement, ' . $dir_name . '. Merci de renommer "' . dirname( __FILE__ ) . '" et de réactiver Amapress',
				'error', false );
		}

		if ( Amapress::getOption( 'test_mail_mode' ) ) {
			amapress_add_admin_notice( 'Le site est en <a target="_blank" href="' . admin_url( 'admin.php?page=amapress_options_page&tab=amp_tests_config' ) . '">mode de test mail</a>. Tous les emails envoyés par le site seront redirigés vers ' . Amapress::getOption( 'test_mail_target' ),
				'info', false, false );
		}


		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'get_plugin_updates' ) ) {
			require_once ABSPATH . '/wp-admin/includes/update.php';
		}
		foreach ( get_plugin_updates() as $plugin_file => $plugin_data ) {
			if ( false !== strpos( $plugin_file, 'amapress' ) ) {
				amapress_add_admin_notice( 'Une nouvelle version d\'Amapress est disponible : ' . Amapress::makeLink( admin_url( 'update-core.php' ), $plugin_data->update->new_version ),
					'warning', false, false );
			}
		}

		$errored_mails_count = AmapressSMTPMailingQueue::getErroredMailsCount();
		if ( $errored_mails_count > 0 ) {
			$errored_mails_url = admin_url( 'admin.php?page=amapress_mailqueue_options_page&tab=amapress_mailqueue_errored_mails' );
			amapress_add_admin_notice( "$errored_mails_count email(s) sortant(s) sont en <a href='$errored_mails_url' target='_blank'>erreur d'envoi</a>",
				'error', false, false );
		}
	}
} );

/**
 * Get the URL of an admin menu item
 *
 * @param string $menu_item_file admin menu item file
 *          - can be obtained via array key #2 for any item in the global $menu or $submenu array
 * @param boolean $submenu_as_parent
 *
 * @return  string URL of admin menu item, NULL if the menu item file can't be found in $menu or $submenu
 */
function get_admin_menu_item_title( $menu_item_file, $submenu_as_parent = true ) {
	global $menu, $submenu, $self, $parent_file, $submenu_file, $plugin_page, $typenow;

	if ( ! is_array( $menu ) ) {
		return '';
	}

	$admin_is_parent = false;
	$item            = '';
	$submenu_item    = '';
	$title           = '';

	// 1. Check if top-level menu item
	foreach ( $menu as $key => $menu_item ) {
		if ( array_keys( $menu_item, $menu_item_file, true ) ) {
			$item = $menu[ $key ];
		}

		if ( $submenu_as_parent && ! empty( $submenu_item ) ) {
			$menu_hook = get_plugin_page_hook( $submenu_item[2], $item[2] );
			$menu_file = $submenu_item[2];

			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
				$menu_file = substr( $menu_file, 0, $pos );
			}
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $submenu_item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
				$admin_is_parent = true;
				$title           = $submenu_item[0];
			} else {
				$title = $submenu_item[0];
			}
		} elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
			$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
			$menu_file = $item[2];

			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
				$menu_file = substr( $menu_file, 0, $pos );
			}
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
				$admin_is_parent = true;
				$title           = $item[0];
			} else {
				$title = $item[0];
			}
		}
	}

	// 2. Check if sub-level menu item
	if ( ! $item ) {
		$sub_item = '';
		foreach ( $submenu as $top_file => $submenu_items ) {

			// Reindex $submenu_items
			$submenu_items = array_values( $submenu_items );

			foreach ( $submenu_items as $key => $submenu_item ) {
				if ( array_keys( $submenu_item, $menu_item_file ) ) {
					$sub_item = $submenu_items[ $key ];
					break;
				}
			}

			if ( ! empty( $sub_item ) ) {
				break;
			}
		}

		// Get top-level parent item
		foreach ( $menu as $key => $menu_item ) {
			if ( array_keys( $menu_item, $top_file, true ) ) {
				$item = $menu[ $key ];
				break;
			}
		}

		// If the $menu_item_file parameter doesn't match any menu item, return false
		if ( ! $sub_item ) {
			return false;
		}

		// Get URL
		$menu_file = $item[2];

		if ( false !== ( $pos = strpos( $menu_file, '?' ) ) ) {
			$menu_file = substr( $menu_file, 0, $pos );
		}

		// Handle current for post_type=post|page|foo pages, which won't match $self.
		$self_type = ! empty( $typenow ) ? $self . '?post_type=' . $typenow : 'nothing';
		$menu_hook = get_plugin_page_hook( $sub_item[2], $item[2] );

		$sub_file = $sub_item[2];
		if ( false !== ( $pos = strpos( $sub_file, '?' ) ) ) {
			$sub_file = substr( $sub_file, 0, $pos );
		}

		$title = $sub_item[0];
	}

	return strip_tags( $title );
}

add_filter( 'admin_footer_text', function ( $content ) {
	global $parent_file, $submenu_file, $pagenow;
	$title     = get_admin_menu_item_title( $parent_file );
	$subtitle  = $submenu_file != $parent_file && ! empty( $submenu_file ) ? get_admin_menu_item_title( $submenu_file ) : '';
	$tab_title = '';
	if ( 'admin.php' == $pagenow && ! empty( $_GET['page'] ) && ! empty( $_GET['tab'] ) ) {
		$current_page_id = $_GET['page'];
		$current_tab_id  = stripslashes( $_GET['tab'] );
		foreach ( AmapressEntities::getMenu() as $item ) {
			if ( isset( $item['id'] ) && $current_page_id == $item['id'] ) {
				if ( ! empty( $item['tabs'] ) && is_array( $item['tabs'] ) ) {
					if ( ! empty( $item['settings']['name'] ) ) {
						$subtitle = $item['settings']['name'];
					}
					foreach ( $item['tabs'] as $tab_name => $tab ) {
						if ( isset( $tab['id'] ) ) {
							$tab_id = $tab['id'];
						} else {
							$tab_id = $tab_name;
						}

						if ( $current_tab_id == $tab_id ) {
							$tab_title = $tab_name;
							break;
						}
					}
				}
			}
			if ( ! empty( $item['subpages'] ) ) {
				foreach ( $item['subpages'] as $subitem ) {
					if ( ! is_array( $subitem ) ) {
						continue;
					}
					if ( isset( $subitem['id'] ) && $current_page_id == $subitem['id'] ) {
						if ( ! empty( $subitem['settings']['name'] ) ) {
							$subtitle = $subitem['settings']['name'];
						}
						if ( ! empty( $subitem['tabs'] ) ) {
							foreach ( $subitem['tabs'] as $tab_name => $tab ) {
								if ( isset( $tab['id'] ) ) {
									$tab_id = $tab['id'];
								} else {
									$tab_id = $tab_name;
								}

								if ( $current_tab_id == $tab_id ) {
									$tab_title = $tab_name;
									break;
								}
							}
						}
					}
				}
			}
		}
	}
	$content .= ' | <strong>' .
	            Amapress::makeLink( '#', 'Tableau de bord>' . $title
	                                     . ( ! empty( $subtitle ) ? '>' . $subtitle : '' )
	                                     . ( ! empty( $tab_title ) ? ', onglet ' . $tab_title : '' ) )
	            . '</strong>';

	$mem_limit = (int) ini_get( 'memory_limit' );
	$mem_usage = function_exists( 'memory_get_usage' ) ? round( memory_get_usage() / 1024 / 1024, 2 ) : 0;

	$content .= ' | Mem. : ' . $mem_usage . ' of ' . $mem_limit . 'MB';

	return $content;
} );

function amapress_force_no_private( $post ) {
	if ( 'private' == $post['post_status']
	     && ( AmapressAdhesion::INTERNAL_POST_TYPE == $post['post_type']
	          || AmapressContrat_instance::INTERNAL_POST_TYPE == $post['post_type']
	          || AmapressContrat::INTERNAL_POST_TYPE == $post['post_type']
	          || AmapressProducteur::INTERNAL_POST_TYPE == $post['post_type'] ) ) {
		$post['post_status'] = 'publish';
	}

	return $post;
}

add_filter( 'wp_insert_post_data', 'amapress_force_no_private' );

add_action( 'wp_head', function () {
	$site_verification_google_id = Amapress::getOption( 'site_verif_google_id' );
	$site_verification_bing_id   = Amapress::getOption( 'site_verif_bing_id' );
	$other_header_html           = Amapress::getOption( 'other_site_html_header' );

	if ( ! empty( $site_verification_google_id ) ) {
		echo "
    <meta name='google-site-verification' content='$site_verification_google_id' />
    ";
	}
	if ( ! empty( $site_verification_bing_id ) ) {
		echo "<meta name='msvalidate.01' content='$site_verification_bing_id'>
    ";
	}

	if ( ! empty( $other_header_html ) ) {
		echo $other_header_html;
	}
} );

add_action( 'admin_post_tf_event_scheduler_resend', function () {
	$hook_name = $_REQUEST['hook_name'];
	$args      = json_decode( wp_unslash( $_REQUEST['args'] ), true );
	$args      = $args[0];
	echo '<p>Rappel lancé pour ' . $args['title'] . '</p>';
	do_action( $hook_name, $args );
	echo '<p>Rappel terminé</p>';
	//echo '<p>Mail de rappel renvoyé avec succès !</p>';
	die();
} );

add_action( 'admin_post_tf_event_scheduler_test', function () {
	global $amapress_send_mail_to;
	$user                  = AmapressUser::getBy( amapress_current_user_id() );
	$amapress_send_mail_to = $user->getEmail();
	$hook_name             = $_REQUEST['hook_name'];
	$args                  = json_decode( wp_unslash( $_REQUEST['args'] ), true );
	$args                  = $args[0];
	echo '<p>Rappel lancé pour ' . $args['title'] . '</p>';
	do_action( $hook_name, $args );
	echo '<p>Rappel terminé</p>';
	die();
} );

add_action( 'pre_get_posts', function ( $query ) {
	/* @var WP_Query $query */
	if ( ( is_category() || is_archive() ) && $query->is_main_query() ) {
		$post_type = $query->get( 'post_type' );
		if ( ! is_array( $post_type ) ) {
			$pt = AmapressEntities::getPostType( amapress_unsimplify_post_type( $post_type ) );
			if ( $pt ) {
				if ( isset( $pt['default_orderby'] ) ) {
					$default_orderby = $pt['default_orderby'];
					if ( false !== strpos( $default_orderby, 'amapress_' ) ) {
						$query->query_vars['orderby']  = 'meta_value_num';
						$query->query_vars['meta_key'] = $default_orderby;
					} else {
						$query->query_vars['orderby'] = 'meta_value_num';
					}
				}
				if ( isset( $pt['default_order'] ) ) {
					$query->query_vars['order'] = $pt['default_order'];
				}
			}
		}
	}

} );