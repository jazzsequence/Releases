<?php

class Album_Releases {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   2.0.0
	 *
	 * @var     string
	 */
	protected $version = '2.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    2.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'plague-releases';

	/**
	 * Instance of this class.
	 *
	 * @since    2.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    2.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     2.0.0
	 */
	private function __construct() {
		// all the actions go here
		add_action( 'init', array( $this, 'post_type_releases' ), 0 );
		add_action( 'init', array( $this, 'releases_taxonomies' ), 0 ); // taxonomy for genre
		add_action( 'admin_menu', array( $this, 'custom_meta_boxes_releases' ) );
		add_action( 'save_post', array( $this, 'releases_save_product_postdata' ), 1, 2 ); // save the custom fields
		add_filter( 'manage_edit-releases_columns', array( $this, 'releases_edit_release_columns' ) );
		add_action( 'manage_releases_posts_custom_column', array( $this, 'releases_manage_release_columns' ), 10, 2 );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     2.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/* create the custom post type */
	public function post_type_releases() {
	    $labels = array(
			'name' => _x('Releases', 'post type general name', 'plague-releases'),
			'singular_name' => _x('Album', 'post type singular name', 'plague-releases'),
			'add_new' => _x('Add New', 'product', 'plague-releases'),
			'add_new_item' => __('Add New Album', 'plague-releases'),
			'edit_item' => __('Edit Album', 'plague-releases'),
			'edit' => __('Edit', 'plague-releases'),
			'new_item' => __('New Album', 'plague-releases'),
			'view_item' => __('View Album', 'plague-releases'),
			'search_items' => __('Search Album Releases', 'plague-releases'),
			'not_found' =>  __('No album releases found', 'plague-releases'),
			'not_found_in_trash' => __('No album releases found in Trash', 'plague-releases'),
			'view' =>  __('View Album Release', 'plague-releases'),
			'parent_item_colon' => ''
	  );
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array("slug" => "album"),
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'exclude_from_search' => false
	  );

	  register_post_type( 'releases', $args );
	}


	public function releases_taxonomies() {
		register_taxonomy( 'genre', 'releases', array( 'hierarchical' => true, 'label' => __('Genre', 'plague-releases'), 'query_var' => 'genre', 'rewrite' => array( 'slug' => 'genre' ) ) ); // this is the genre taxonomy for album releases
			$artist_labels = array(
				'name' => __( 'Artists', 'plague-releases' ),
				'singular_name' => __( 'Artists', 'plague-releases' ),
				'search_items' => __( 'Search Artists', 'plague-releases' ),
				'all_items' => __( 'All Artists', 'plague-releases' ),
				'edit_item' => __( 'Edit Artist', 'plague-releases' ),
				'update_item' => __( 'Update Artist', 'plague-releases' ),
				'add_new_item' => __( 'Add New Artist', 'plague-releases' ),
				'new_item_name' => __( 'New Artist Name', 'plague-releases' ),
			);
		register_taxonomy( 'artist', 'releases', array( 'hierarchical' => true, 'labels' => $artist_labels, 'query_var' => 'artist', 'rewrite' => array( 'slug' => 'artist' ) ) ); // this is the artist taxonomy for releases
		}


	/* create custom meta boxes */

	public function custom_meta_boxes_releases() {
	    add_meta_box("releases-details",  __( "Album Details", 'plague-releases' ),  array( $this, "meta_cpt_releases" ), "releases", "normal", "low");
		add_meta_box("releases-buy", __( "Purchase Links", 'plague-releases' ), array( $this, "meta_cpt_releases_buy" ),"releases","side","low");
	}

	public function meta_cpt_releases() {
	    global $post;

		echo '<input type="hidden" name="releases_noncename" id="releases_noncename" value="' .
		wp_create_nonce( plugin_basename(__FILE__) ) . '" />';


		echo '<p><label for="url_to_buy">' . __( 'URL to purchase album', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="url_to_buy" value="'. mysql_real_escape_string( get_post_meta($post->ID, 'url_to_buy ', true ) ) . '" /></p>';
		echo '<p><label for="tracklist">' . __( 'Track List', 'plague-releases' ) . '</label><br />';
		wp_editor( wp_kses_post( get_post_meta( $post->ID, 'tracklist', true ) ), 'tracklist', array( 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 5, 'quicktags' => false ) );
		echo '</p>';

		$kses_allowed = array_merge(wp_kses_allowed_html( 'post' ), array('iframe' => array(
			'src' => array(),
			'style' => array(),
			'width' => array(),
			'height' => array(),
			'scrolling' => array(),
			'frameborder' => array()
			)));

		echo '<p><label for="embed_code">' . __( 'Player Embed Code', 'plague-releases' ) . '</label><br />';
		echo '<textarea class="widefat" rows="5" cols="50" name="embed_code" />'. wp_kses( get_post_meta( $post->ID, 'embed_code', true ), $kses_allowed ) . '</textarea></p>';

		echo '<p><label for="release_date">' . __( 'Album Release Date', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="release_date" value="' . wp_kses_post( get_post_meta($post->ID, 'release_date', true ) ) . '" /></p>';
		echo '<p><label for="plague_release_number">' . __( 'Release Number <em>(if applicable)</em>', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="plague_release_number" value"'. wp_kses( get_post_meta( $post->ID, 'plague_release_number', true ), array() ) . '" /></p>';

		if ( get_post_meta($post->ID,'plague_release_number') ) { $plague_release = get_post_meta($post->ID,'plague_release_number',true); } else { $plague_release = 'PLAGUE000'; };
		echo '<p><label for="internet_archive">' . __( 'Archive.org Release Identifier', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="internet_archive" value="' . wp_kses( get_post_meta($post->ID,'internet_archive', true ), array() ) . '" /><br />';
		echo sprintf( __( 'If the release is posted in the Internet Archive, add the Release Number or other release identifier here, e.g. if the URL to your release is http://archive.org/details/%1$s, enter %1$s here.', 'plague-releases' ), $plague_release );

	}

	public function meta_cpt_releases_buy() {
	  global $post;

		echo '<input type="hidden" name="releases_noncename" id="releases_noncename" value="' .
		wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		echo '<p><label for="bandcamp_url">' . __( 'Bandcamp URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="bandcamp_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'bandcamp_url', true ) ) . '" /></p>';
		echo '<p><label for="itunes_url">' . __( 'iTunes URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="itunes_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'itunes_url', true ) ) . '" /></p>';
		echo '<p><label for="spotify_url">' . __( 'Spotify URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="spotify_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'spotify_url', true ) ) . '" /></p>';
		echo '<p><label for="amazonmp3_url">' . __( 'AmazonMP3 URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="amazonmp3_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'amazonmp3_url', true ) ) . '" /></p>';
		echo '<p><label for="zune_url">' . __( 'Zune URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="zune_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'zune_url', true ) ) . '" /></p>';
		echo '<p><label for="emusic_url">' . __( 'eMusic URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="emusic_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'emusic_url', true ) ) . '" /></p>';
		echo '<p><label for="napster_url">' . __( 'Napster URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="napster_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'napster_url', true ) ) . '" /></p>';
		echo '<p><label for="rhapsody_url">' . __( 'Rhapsody URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="rhapsody_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'rhapsody_url', true ) ) . '" /></p>';
		echo '<p><label for="reverbnation_buy_url">' . __( 'Reverbnation URL', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="reverbnation_buy_url" value="'. mysql_real_escape_string( get_post_meta($post->ID,'reverbnation_buy_url', true ) ) . '" /></p>';
	}

	/* When the post is saved, saves our product data */
	public function releases_save_product_postdata($post_id, $post) {
		$nonce = isset( $_POST['reviews_noncename'] ) ? $_POST['reviews_noncename'] : 'all the pigs, all lined up';
		if ( !wp_verify_nonce( $nonce, plugin_basename(__FILE__) )) {
			return $post->ID;
		}

		/* confirm user is allowed to save page/post */
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post->ID ))
			return $post->ID;
		} else {
			if ( !current_user_can( 'edit_post', $post->ID ))
			return $post->ID;
		}

		/* ready our data for storage */
		$meta_keys = array(
			'url_to_buy' => 'url',
			'tracklist' => 'text',
			'embed_code' => 'embed',
			'release_date' => 'text',
			'plague_release_number' => 'text',
			'internet_archive' => 'text',
			'bandcamp_url' => 'url',
			'itunes_url' => 'url',
			'spotify_url' => 'url',
			'amazonmp3_url' => 'url',
			'zune_url' => 'url',
			'emusic_url' => 'url',
			'napster_url' => 'url',
			'rhapsody_url' => 'url',
			'reverbnation_buy_url' => 'url'
		);

		/* Add values of $mydata as custom fields */
		foreach ($meta_keys as $meta_key => $type) {
			if( $post->post_type == 'revision' )
				return;
			if ( isset( $_POST[ $meta_key ] ) ) {
				if ( $type == 'text' ) {
					$value = wp_kses_post( $_POST[ $meta_key ] );
				}
				if ( $type == 'embed' ) {
					$kses_allowed = array_merge(wp_kses_allowed_html( 'post' ), array('iframe' => array(
						'src' => array(),
						'style' => array(),
						'width' => array(),
						'height' => array(),
						'scrolling' => array(),
						'frameborder' => array()
						)));
					$value = wp_kses( $_POST[ $meta_key ], $kses_allowed );
				}
				if ( $type == 'url' ) {
					$value = htmlspecialchars( $_POST[ $meta_key ] );
				}
				update_post_meta( $post->ID, $meta_key, $value );
			} else {
				delete_post_meta( $post->ID, $meta_key );
			}
		}
	}

	/* add some custom columns */
	public function releases_edit_release_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Album Release',
			'release_date' => 'Release Date',
			'genre' => 'Genre',
			'plague_release_number' => 'Plague Release Number'
		);
		return $columns;
	}
	/* now we're going to get the data to fill the columns */
	add_action('manage_releases_posts_custom_column', 'releases_manage_release_columns',10,2);
	function releases_manage_release_columns( $column, $post_id ) {
		global $post;
		switch ( $column ) {
			case 'release_date' :
				$release_date = get_post_meta( $post_id, 'release_date', true);
				printf( $release_date );
				break;
			case 'plague_release_number' :
				$plague_release = get_post_meta ($post_id, 'plague_release_number', true);
				printf( $plague_release );
				break;
			case 'genre' :
				$terms = get_the_terms( $post_id, 'genre');
				/* If terms were found. */
				if ( !empty( $terms ) ) {

					$out = array();

					/* Loop through each term, linking to the 'edit posts' page for the specific term. */
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'genre' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'genre', 'display' ) )
						);
					}

					/* Join the terms, separating them with a comma. */
					echo join( ', ', $out );
				}

				/* If no terms were found, output a default message. */
				else {
					_e( 'No Genres' );
				}

				break;

			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}

}