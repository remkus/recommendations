<?php
/**
 * Recommendations plugin.
 *
 * This is a complete URL management system that allows you create, manage, and track outbound links from your site by using custom post types and 301 redirects.
 *
 * @package Recommendations
 * @author Remkus de Vries
 * @author Gary Jones
 *
 * Plugin Name: Recommendations
 * Plugin URI: http://remkusdevries.com/plugins/recommendations/
 * Description: Complete URL management system that allows you create, manage, and track outbound links from your site via 301 redirects.
 * Version: 0.3.3
 * Author: Remkus de Vries
 * Author URI: https://remkusdevries.com/
 * License: GPL-2.0+
 *
 * Copyright Nathan Rice from his SimpleURLs plugin as a basis.
 *
 * Copyright 2012 (remkus@forsite.media).
 *
 * This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as
 *  published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
*/

/**
 * Recommendations plugin, main class.
 *
 * @package Recommendations
 * @author Remkus de Vries
 * @author Gary Jones
 */
class recommendations {
	/** @var Plugin slug. */
	public $slug = 'recommendations';

	/** @var Meta key for storing redirection. */
	public $key = '_recommendations_redirect';

	/** @var Meta key for storing redirection counter. */
	public $counter_key = '_recommendations_count';

	/** @var Name of the custom post type. */
	public $cpt = 'recommends';

	/** @var Name of the providers taxonomy. */
	public $providers = 'providers';

	/** @var Name of the genre taxonomy. */
	public $genre = 'genre';

	/** @var Name of the nonce. */
	public $nonce_name = '_recommendations_nonce';

	/**
	 * Initialise and integrate plugin.
	 *
	 * @since 0.2.0
	 */
	public function run() {
		//register_activation_hook( __FILE__, 'flush_rewrite_rules' );
		add_action( 'init', array( $this, 'localization' ) );
		add_action( 'init', array( $this, 'register_cpt_recommendations' ) );
		add_action( 'init', array( $this, 'register_taxonomy_genre' ) );
		add_action( 'init', array( $this, 'register_taxonomy_providers' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'columns_data' ) );
		add_filter( 'manage_edit-recommends_columns', array( $this, 'columns_filter' ) );
		add_action( 'admin_menu', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'meta_box_save' ), 1, 2 );
		add_action( 'template_redirect', array( $this, 'count_and_redirect' ) );
		add_action( 'add_meta_boxes', array( $this, 'remove_yoast_metabox' ) , 11 );
		add_filter( 'manage_edit-recommends_columns', array( $this, 'remove_yoast_seo_list_columns' ) );
		add_filter( 'rest_api_allowed_post_types', array( $this, 'allow_post_type_wpcom' ) );
	}

	/**
	 * Set up localization.
	 *
	 * @since 0.1.0
	 */
	public function localization() {
		load_plugin_textdomain( $this->slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register custom post type.
	 *
	 * @since 0.1.0
	 */
	public function register_cpt_recommendations() {
		$labels = array(
			'name'               => _x( 'Recommended Links', 'recommendations' ),
			'singular_name'      => _x( 'Recommended link', 'recommendations' ),
			'add_new'            => _x( 'Add New Link', 'recommendations' ),
			'add_new_item'       => _x( 'Add New Recommended link', 'recommendations' ),
			'edit_item'          => _x( 'Edit Recommended link', 'recommendations' ),
			'new_item'           => _x( 'New Recommended link', 'recommendations' ),
			'view_item'          => _x( 'View Recommended link', 'recommendations' ),
			'search_items'       => _x( 'Search Recommended Links', 'recommendations' ),
			'not_found'          => _x( 'No Recommended links found', 'recommendations' ),
			'not_found_in_trash' => _x( 'No Recommended links found in Trash', 'recommendations' ),
			'parent_item_colon'  => _x( 'Parent Recommended link:', 'recommendations' ),
			'menu_name'          => _x( 'Recommends', 'recommendations' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => __( 'Recommendations Links post type', 'recommendations' ),
			'supports'            => array(
				'title',
				'revisions',
			),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 88.11,
			'menu_icon'           => 'dashicons-thumbs-up',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => 'recommends',
				'with_front' => false,
				'feeds'      => true,
				'pages'      => false
			),
			'capability_type'     => 'post',
		);

		register_post_type( $this->cpt, $args );
	}


	/**
	 * Register the providers taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function register_taxonomy_providers() {

		$labels = array(
			'name'                       => _x( 'Providers', 'recommendations' ),
			'singular_name'              => _x( 'Provider', 'recommendations' ),
			'search_items'               => _x( 'Search Providers', 'recommendations' ),
			'popular_items'              => _x( 'Popular Providers', 'recommendations' ),
			'all_items'                  => _x( 'All Providers', 'recommendations' ),
			'parent_item'                => _x( 'Parent Provider', 'recommendations' ),
			'parent_item_colon'          => _x( 'Parent Provider:', 'recommendations' ),
			'edit_item'                  => _x( 'Edit Provider', 'recommendations' ),
			'update_item'                => _x( 'Update Provider', 'recommendations' ),
			'add_new_item'               => _x( 'Add New Provider', 'recommendations' ),
			'new_item_name'              => _x( 'New Provider', 'recommendations' ),
			'separate_items_with_commas' => _x( 'Separate providers with commas', 'recommendations' ),
			'add_or_remove_items'        => _x( 'Add or remove Providers', 'recommendations' ),
			'choose_from_most_used'      => _x( 'Choose from most used Providers', 'recommendations' ),
			'menu_name'                  => _x( 'Providers', 'recommendations' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_tagcloud'     => false,
			'hierarchical'      => false,
			'rewrite'           => false,
			'query_var'         => true,
		);

		register_taxonomy( $this->providers, array( $this->cpt ), $args );
	}

	/**
	 * Register the genre taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function register_taxonomy_genre() {
		$labels = array(
			'name'                       => _x( 'Genre', 'recommendations' ),
			'singular_name'              => _x( 'Genre', 'recommendations' ),
			'search_items'               => _x( 'Search Genres', 'recommendations' ),
			'popular_items'              => _x( 'Popular Genre', 'recommendations' ),
			'all_items'                  => _x( 'All Genres', 'recommendations' ),
			'parent_item'                => _x( 'Parent Genre', 'recommendations' ),
			'parent_item_colon'          => _x( 'Parent Genre:', 'recommendations' ),
			'edit_item'                  => _x( 'Edit Genre', 'recommendations' ),
			'update_item'                => _x( 'Update Genre', 'recommendations' ),
			'add_new_item'               => _x( 'Add New Genre', 'recommendations' ),
			'new_item_name'              => _x( 'New Genre', 'recommendations' ),
			'separate_items_with_commas' => _x( 'Separate genre with commas', 'recommendations' ),
			'add_or_remove_items'        => _x( 'Add or remove Genres', 'recommendations' ),
			'choose_from_most_used'      => _x( 'Choose from most used Genres', 'recommendations' ),
			'menu_name'                  => _x( 'Genre', 'recommendations' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_tagcloud'     => false,
			'hierarchical'      => false,
			'rewrite'           => false,
			'query_var'         => true,
		);

		register_taxonomy( $this->genre, array( $this->cpt ), $args );

	}

	/**
	 * Set columns to display for custom post type admin index.
	 *
	 * @since 0.1.0
	 */
	public function columns_filter( $columns ) {
		return array(
			'cb'        => '<input type="checkbox" />',
			'title'     => _x( 'Title', 'Column heading', 'recommendations' ),
			'url'       => _x( 'Redirect to', 'Column heading', 'recommendations' ),
			'permalink' => _x( 'Permalink', 'Column heading', 'recommendations' ),
			'clicks'    => _x( 'Clicks', 'Column heading', 'recommendations' ),
		);
	}

	/**
	 * Populate custom post type admin index columns.
	 *
	 * @since 0.1.0
	 */
	public function columns_data( $column ) {
		global $post;

		$url   = get_post_meta( $post->ID, $this->key, true );
		$count = get_post_meta( $post->ID, $this->counter_key, true );

		switch( $column ) {
			case 'url':
				echo make_clickable( esc_url( $url ? $url : '' ) );
				break;
			case 'permalink':
				echo make_clickable( get_permalink() );
				break;
			case 'clicks':
				echo esc_html( $count ? $count : 0 );
				break;
		}
	}

	/**
	 * Register meta box for display on custom post type editing.
	 *
	 * @since 0.1.0
	 */
	public function add_meta_box() {
		add_meta_box( 'recommendations', __( 'URL Information', 'recommendations' ), array( &$this, 'meta_box' ), $this->cpt, 'normal', 'high' );
	}

	/**
	 * Populate meta box.
	 *
	 * @since 0.1.0
	 */
	public function meta_box() {
		global $post;

		printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $this->nonce_name ), wp_create_nonce( plugin_basename( __FILE__ ) ) );

		printf( '<p><label for="%s">%s</label></p>', esc_attr( $this->key ), __( 'Redirect URI', 'recommendations' ) );
		printf( '<p><input type="text" name="%s" id="%s" value="%s" style="%s" /></p>', esc_attr( $this->key ), esc_attr( $this->key ), esc_attr( get_post_meta( $post->ID, $this->key, true ) ), esc_attr( 'width: 99%;' ) );

		$count = isset( $post->ID ) ? get_post_meta( $post->ID, $this->counter_key, true ) : 0;
		printf( '<p>This URL has been accessed <b>%d</b> times.</p>', $count );
	}

	/**
	 * Save meta box input values.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id Post ID to save meta data for.
	 * @param object $post    Post object.
	 *
	 * @return null Return early if invalid nonce, or trying to save under autosave,
	 *              ajax or future post, or incorrect user permissions.
	 */
	public function meta_box_save( $post_id, $post ) {
		// Verify the nonce
		if ( ! isset( $_POST[$this->nonce_name] ) || ! wp_verify_nonce( $_POST[$this->nonce_name], plugin_basename( __FILE__ ) ) )
			return;

		// Don't try to save the data under autosave, ajax, or future post.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;
		if ( defined( 'DOING_CRON' ) && DOING_CRON )
			return;

		// Is the user allowed to edit the post or page?
		if ( ! current_user_can( 'delete_pages' ) || $post->post_type != $this->cpt )
			return;

		$value = isset( $_POST[$this->key] ) ? $_POST[$this->key] : '';

		if ( $value )
			update_post_meta( $post->ID, $this->key, $value );
		else
			delete_post_meta( $post->ID, $this->key );
	}

	/**
	 * Increase counter and perform redirect when single link is accessed.
	 *
	 * @since 0.1.0
	 *
	 * @return null Return early and do nothing if not a single hide and track link.
	 */
	public function count_and_redirect() {
		if ( ! is_singular( $this->cpt ) )
			return;

		global $wp_query;

		// Update the count
		$count = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, $this->counter_key, true ) : 0;
		update_post_meta( $wp_query->post->ID, $this->counter_key, $count + 1 );

		// Handle the redirect
		$redirect = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, $this->key, true ) : '';


		if ( ! empty( $redirect ) ) {
			wp_redirect( esc_url_raw( $redirect ), 301 );
			exit;
		} else {
			wp_redirect( home_url(), 302 );
			exit;
		}
	}

	/**
	 * Remove Yoast SEO metaboxes
	 * @since  0.3.2
	 * @return [type] [description]
	 */
	public function remove_yoast_metabox() {
    	remove_meta_box( 'wpseo_meta', 'recommends', 'normal' );
	}

	/**
	 * Remove the Yoast SEO columns
	 * @since  0.3.2
	 * @param  [type] $columns [description]
	 * @return [type]          [description]
	 */
	public function remove_yoast_seo_list_columns( $columns) {
		unset( $columns['wpseo-score'] );
		unset( $columns['wpseo-title'] );
		unset( $columns['wpseo-metadesc'] );
		unset( $columns['wpseo-focuskw'] );
		return $columns;
	}  

	/**
 	 * Filter the list of Post Types available in the WordPress.com REST API.
	 *
	 * @since  0.3.3
	 * @param array $allowed_post_types Array of whitelisted Post Types.
	 * @return array $allowed_post_types Array of whitelisted Post Types, including our 'recommends' Custom Post Type.
	 */
	public function allow_post_type_wpcom( $allowed_post_types ) {
		$allowed_post_types[] = 'recommends';
    	return $allowed_post_types;
	}

}

$recommendations = new recommendations;
$recommendations->run();