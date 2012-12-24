<?php
/*
Plugin Name: Redirect Outbound Links
Plugin URI: http://remkusdevries.com/plugins/redirect-outbound-inks
Description: Redirect Outbound Links is a complete URL management system that allows you create, manage, and track outbound links from your site by using custom post types and 301 redirects.
Version: 0.1.1
Author: Remkus de Vries
Author URI: http://remkusdevries.com/
License:

  Copyright Nathan Rice from his SimpleURLs plugin as a basis.

  Copyright 2012 (remkus@forsite.nu).

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class HideTrackLinks {

	/**
	 * Constants
	 */
	const name = 'Redirect Outbound Links';
	const slug = 'redirect_outbound_links';

	// Constructor
	function __construct() {
		//register_activation_hook( __FILE__, 'flush_rewrite_rules' );

		add_action( 'init', array( &$this, 'localization' ) );
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'init', 'register_taxonomy_genre' );
		add_action( 'init', 'register_taxonomy_providers' );
		add_action( 'manage_posts_custom_column', array( &$this, 'columns_data' ) );
		add_filter( 'manage_edit-hidetracklinks_columns', array( &$this, 'columns_filter' ) );
		add_action( 'admin_menu', array( &$this, 'add_meta_box' ) );
		add_action( 'save_post', array( &$this, 'meta_box_save' ), 1, 2 );
		add_action( 'template_redirect', array( &$this, 'count_and_redirect' ) );
	}

	add_action( 'init', 'register_cpt_hidetracklinks' );

	function localization() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Registering Custom Post Type
	 *
	 * @return [type] [description]
	 */
	function register_cpt_hidetracklinks() {

		$labels = array(
			'name'					=> _x( 'Hide and Track Links', 'hidetracklinks' ),
			'singular_name'			=> _x( 'Hide and Track link', 'hidetracklinks' ),
			'add_new'				=> _x( 'Add New Link', 'hidetracklinks' ),
			'add_new_item'			=> _x( 'Add New Hide and Track link', 'hidetracklinks' ),
			'edit_item'				=> _x( 'Edit Hide and Track link', 'hidetracklinks' ),
			'new_item'				=> _x( 'New Hide and Track link', 'hidetracklinks' ),
			'view_item'				=> _x( 'View Hide and Track link', 'hidetracklinks' ),
			'search_items'			=> _x( 'Search Hide and Track Links', 'hidetracklinks' ),
			'not_found'				=> _x( 'No hide and track links found', 'hidetracklinks' ),
			'not_found_in_trash'	=> _x( 'No hide and track links found in Trash', 'hidetracklinks' ),
			'parent_item_colon'		=> _x( 'Parent Hide and Track link:', 'hidetracklinks' ),
			'menu_name'				=> _x( 'Hide and Track Links', 'hidetracklinks' ),
		);

		$args = array(
			'labels'				=> $labels,
			'hierarchical'			=> false,
			'description'			=> 'Hide and Track Links post type',
			'supports'				=> array(
				'title',
				'revisions'
			),
			'taxonomies'			=> array(
				'provider',
				'genre'
			),
			'public'				=> true,
			'show_ui'				=> true,
			'show_in_menu'			=> true,
			'menu_position'			=> 88.11,
			'menu_icon'				=> 'the_url',
			'show_in_nav_menus'		=> true,
			'publicly_queryable'	=> true,
			'exclude_from_search'	=> false,
			'has_archive'			=> false,
			'query_var'				=> true,
			'can_export'			=> true,
			'rewrite'				=> array(
				'slug'				=> 'out',
				'with_front'		=> false,
				'feeds'				=> true,
				'pages'				=> false
			),
			'capability_type'		=> 'post'
		);

		register_post_type( 'hidetracklinks', $args );
	}


	/**
	 * [register_taxonomy_providers description]
	 *
	 * @return [type] [description]
	 */
	function register_taxonomy_providers() {

		$labels = array(
			'name'							=> _x( 'Providers', 'hidetracklinks' ),
			'singular_name'					=> _x( 'Provider', 'hidetracklinks' ),
			'search_items'					=> _x( 'Search Providers', 'hidetracklinks' ),
			'popular_items'					=> _x( 'Popular Providers', 'hidetracklinks' ),
			'all_items'						=> _x( 'All Providers', 'hidetracklinks' ),
			'parent_item'					=> _x( 'Parent Provider', 'hidetracklinks' ),
			'parent_item_colon'				=> _x( 'Parent Provider:', 'hidetracklinks' ),
			'edit_item'						=> _x( 'Edit Provider', 'hidetracklinks' ),
			'update_item'					=> _x( 'Update Provider', 'hidetracklinks' ),
			'add_new_item'					=> _x( 'Add New Provider', 'hidetracklinks' ),
			'new_item_name'					=> _x( 'New Provider', 'hidetracklinks' ),
			'separate_items_with_commas'	=> _x( 'Separate providers with commas', 'hidetracklinks' ),
			'add_or_remove_items'			=> _x( 'Add or remove Providers', 'hidetracklinks' ),
			'choose_from_most_used'			=> _x( 'Choose from most used Providers', 'hidetracklinks' ),
			'menu_name'						=> _x( 'Providers', 'hidetracklinks' ),
		);

		$args = array(
			'labels'			=> $labels,
			'public'			=> true,
			'show_in_nav_menus'	=> false,
			'show_ui'			=> true,
			'show_tagcloud'		=> false,
			'hierarchical'		=> false,
			'rewrite'			=> false,
			'query_var'			=> true
		);

		register_taxonomy( 'providers', array( 'hidetracklinks' ), $args );


		/**
		 * [register_taxonomy_genre description]
		 *
		 * @return [type] [description]
		 */
		function register_taxonomy_genre() {

			$labels = array(
				'name'							=> _x( 'Genre', 'hidetracklinks' ),
				'singular_name'					=> _x( 'Genre', 'hidetracklinks' ),
				'search_items'					=> _x( 'Search Genres', 'hidetracklinks' ),
				'popular_items'					=> _x( 'Popular Genre', 'hidetracklinks' ),
				'all_items'						=> _x( 'All Genres', 'hidetracklinks' ),
				'parent_item'					=> _x( 'Parent Genre', 'hidetracklinks' ),
				'parent_item_colon'				=> _x( 'Parent Genre:', 'hidetracklinks' ),
				'edit_item'						=> _x( 'Edit Genre', 'hidetracklinks' ),
				'update_item'					=> _x( 'Update Genre', 'hidetracklinks' ),
				'add_new_item'					=> _x( 'Add New Genre', 'hidetracklinks' ),
				'new_item_name'					=> _x( 'New Genre', 'hidetracklinks' ),
				'separate_items_with_commas'	=> _x( 'Separate genre with commas', 'hidetracklinks' ),
				'add_or_remove_items'			=> _x( 'Add or remove Genres', 'hidetracklinks' ),
				'choose_from_most_used'			=> _x( 'Choose from most used Genres', 'hidetracklinks' ),
				'menu_name'						=> _x( 'Genre', 'hidetracklinks' ),
			);

			$args = array(
				'labels'			=> $labels,
				'public'			=> true,
				'show_in_nav_menus'	=> false,
				'show_ui'			=> true,
				'show_tagcloud'		=> false,
				'hierarchical'		=> false,
				'rewrite'			=> false,
				'query_var'			=> true
			);

			register_taxonomy( 'genre', array( 'hidetracklinks' ), $args );

		}

		function columns_filter( $columns ) {

			$columns = array(
				'cb'		=> '<input type="checkbox" />',
				'title'		=> _x( 'Title', 'hidetracklinks' ),
				'url'		=> _x( 'Redirect to', 'hidetracklinks' ),
				'permalink'	=> _x( 'Permalink', 'hidetracklinks' ),
				'clicks'	=> _x( 'Clicks', 'hidetracklinks' )
			);

			return $columns;

		}

		function columns_data( $column ) {

			global $post;

			$url = get_post_meta( $post->ID, '_hidetracklinks_redirect', true );
			$count = get_post_meta( $post->ID, '_hidetracklinks_count', true );

			if ( $column == 'url' ) {
				echo make_clickable( esc_url( $url ? $url : '' ) );
			}
			elseif ( $column == 'permalink' ) {
				echo make_clickable( get_permalink() );
			}
			elseif ( $column == 'clicks' ) {
				echo esc_html( $count ? $count : 0 );
			}

		}

		function add_meta_box() {
			add_meta_box( 'hidetracklinks', __( 'URL Information', 'hidetracklinks' ), array( &$this, 'meta_box' ), 'hidetracklinks', 'normal', 'high' );
		}

		function meta_box() {
			global $post;

			printf( '<input type="hidden" name="_hidetracklinks_nonce" value="%s" />', wp_create_nonce( plugin_basename( __FILE__ ) ) );

			printf( '<p><label for="%s">%s</label></p>', '_hidetracklinks_redirect', __( 'Redirect URI', 'hidetracklinks' ) );
			printf( '<p><input style="%s" type="text" name="%s" id="%s" value="%s" /></p>', 'width: 99%;', '_hidetracklinks_redirect', '_hidetracklinks_redirect', esc_attr( get_post_meta( $post->ID, '_hidetracklinks_redirect', true ) ) );

			$count = isset( $post->ID ) ? get_post_meta( $post->ID, '_hidetracklinks_count', true ) : 0;
			printf( '<p>This URL has been accessed <b>%d</b> times.', esc_attr( $count ) );

		}

		function meta_box_save( $post_id, $post ) {

			$key = '_hidetracklinks_redirect';

			// verify the nonce
			if ( !isset( $_POST['_hidetracklinks_nonce'] ) || !wp_verify_nonce( $_POST['_hidetracklinks_nonce'], plugin_basename( __FILE__ ) ) )
				return;

			// don't try to save the data under autosave, ajax, or future post.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) return;

			// is the user allowed to edit the post or page?
			if ( !current_user_can( 'delete_pages' ) || $post->post_type != 'hidetracklinks' )
				return;

			$value = isset( $_POST[$key] ) ? $_POST[$key] : '';

			if ( $value ) {
				// save/update
				update_post_meta( $post->ID, $key, $value );
			} else {
				// delete if blank
				delete_post_meta( $post->ID, $key );
			}

		}


		function count_and_redirect() {

			if ( !is_singular( 'hidetracklinks' ) )
				return;

			global $wp_query;

			// Update the count
			$count = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, '_hidetracklinks_count', true ) : 0;
			update_post_meta( $wp_query->post->ID, '_hidetracklinks_count', $count + 1 );

			// Handle the redirect
			$redirect = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, '_hidetracklinks_redirect', true ) : '';

			if ( !empty( $redirect ) ) {
				wp_redirect( esc_url_raw( $redirect ), 301 );
				exit;
			}
			else {
				wp_redirect( home_url(), 302 );
				exit;
			}

		}

	}

$HideTrackLinks = new HideTrackLinks;