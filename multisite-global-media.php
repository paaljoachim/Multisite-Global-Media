<?php
/**
 * Plugin Name: Multisite Global Media
 * Description: Share an media library across multisite network
 * Network:     true
 * Plugin URI:
 * Version:     0.0.1
 * Author:      Dominik Schilling, Frank Bültge
 * Author URI:  http://bueltge.de/
 * License:     GPLv2+
 * License URI: ./license.txt
 * Text Domain: global-media
 * Domain Path: /languages
 *
 * Php Version 5.3
 *
 * @package WordPress
 * @author  Dominik Schilling <d.schilling@inpsyde.com>, Frank Bültge <f.bueltge@inpsyde.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 2015-01-26
 */
namespace Multisite_Global_Media;

/**
 * Don't call this file directly.
 */
defined( 'ABSPATH' ) || die();

/**
 * Id of side inside the network, there store the global media
 *
 * @var    integer
 * @since  2015-01-22
 */
const BLOG_ID = 3;

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
/**
 * Enqueue script for media modal
 *
 * @since   2015-01-26
 * @return null
 */
function enqueue_scripts() {

	if ( 'post' !== get_current_screen()->base ) {
		return NULL;
	}

	wp_enqueue_script(
		'global-media',
		plugins_url( 'assets/js/global-media.js', __FILE__ ),
		array( 'media-views' ),
		'0.1',
		TRUE
	);
}

add_filter( 'media_view_strings', __NAMESPACE__ . '\get_media_strings' );
/**
 * Define Strings for translation
 *
 * @since   2015-01-26
 * @param $strings
 *
 * @return mixed
 */
function get_media_strings( $strings ) {

	$strings[ 'globalMediaTitle' ] = __( 'Global Media', 'global-media' );

	return $strings;
}

/**
 * Prepare media for javascript
 *
 * @since   2015-01-26
 * @param $response
 *
 * @return mixed
 */
function prepare_attachment_for_js( $response ) {

	$id_prefix = BLOG_ID . '00000';

	$response[ 'id' ]                 = $id_prefix . $response[ 'id' ]; // Unique ID, must be a number.
	$response[ 'nonces' ][ 'update' ] = FALSE;
	$response[ 'nonces' ][ 'edit' ]   = FALSE;
	$response[ 'nonces' ][ 'delete' ] = FALSE;
	$response[ 'editLink' ]           = FALSE;

	return $response;
}

add_action( 'wp_ajax_query-attachments', __NAMESPACE__ . '\ajax_query_attachments', 0 );
/**
 * Same as wp_ajax_query_attachments() but with switch_to_blog support.
 *
 * @since   2015-01-26
 * @return void
 */
function ajax_query_attachments() {

	$query = isset( $_REQUEST[ 'query' ] ) ? (array) $_REQUEST[ 'query' ] : array();

	if ( ! empty( $query[ 'global-media' ] ) ) {
		switch_to_blog( BLOG_ID );

		add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\prepare_attachment_for_js' );
	}

	wp_ajax_query_attachments();
	exit;
}

/**
 * Send media to editor
 *
 * @since   2015-01-26
 * @param $html
 * @param $id
 *
 * @return mixed
 */
function media_send_to_editor( $html, $id ) {

	$id_prefix = BLOG_ID . '00000';
	$new_id    = $id_prefix . $id; // Unique ID, must be a number.

	$search  = 'wp-image-' . $id;
	$replace = 'wp-image-' . $new_id;
	$html    = str_replace( $search, $replace, $html );

	return $html;
}

add_action( 'wp_ajax_send-attachment-to-editor', __NAMESPACE__ . '\ajax_send_attachment_to_editor', 0 );
/**
 * Send media via AJAX call to editor
 *
 * @since   2015-01-26
 * @return  void
 */
function ajax_send_attachment_to_editor() {

	$attachment = wp_unslash( $_POST[ 'attachment' ] );
	$id         = $attachment[ 'id' ];
	$id_prefix  = BLOG_ID . '00000';

	if ( FALSE !== strpos( $id, $id_prefix ) ) {
		$attachment[ 'id' ]    = str_replace( $id_prefix, '', $id ); // Unique ID, must be a number.
		$_POST[ 'attachment' ] = wp_slash( $attachment );

		switch_to_blog( BLOG_ID );

		add_filter( 'media_send_to_editor', __NAMESPACE__ . '\media_send_to_editor', 10, 2 );
	}

	wp_ajax_send_attachment_to_editor();
	exit();
}

add_action( 'wp_ajax_get-attachment', __NAMESPACE__ . '\ajax_get_attachment', 0 );
/**
 * Get attachment
 *
 * @since   2015-01-26
 * @return  void
 */
function ajax_get_attachment() {

	$id        = $_REQUEST[ 'id' ];
	$id_prefix = BLOG_ID . '00000';

	if ( FALSE !== strpos( $id, $id_prefix ) ) {
		$id               = str_replace( $id_prefix, '', $id ); // Unique ID, must be a number.
		$_REQUEST[ 'id' ] = $id;

		switch_to_blog( BLOG_ID );
		add_filter( 'wp_prepare_attachment_for_js', __NAMESPACE__ . '\prepare_attachment_for_js' );
		restore_current_blog();
	}

	wp_ajax_get_attachment();
	exit();
}