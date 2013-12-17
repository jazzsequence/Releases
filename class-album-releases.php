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
		global $is_apache;
		// all the actions go here
		add_action( 'init', array( $this, 'post_type_releases' ), 0 );
		add_action( 'init', array( $this, 'releases_taxonomies' ), 0 ); // taxonomy for genre
		add_action( 'admin_menu', array( $this, 'custom_meta_boxes_releases' ) );
		add_action( 'save_post', array( $this, 'releases_save_product_postdata' ), 1, 2 ); // save the custom fields
		add_filter( 'manage_edit-plague-release_columns', array( $this, 'releases_edit_release_columns' ) );
		add_action( 'manage_plague-release_posts_custom_column', array( $this, 'releases_manage_release_columns' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles') );
		// Rename "featured image"
		add_action('admin_head-post-new.php', array($this, 'change_thumbnail_html'));
		add_action('admin_head-post.php', array($this, 'change_thumbnail_html'));
		add_action( 'add_meta_boxes', array( $this, 'rebuild_thumbnail_metabox' ) );
		// add content filter for releases
		add_filter( 'the_content', array( $this, 'filter_release_content' ) );
		// deal with permalink stuff
		if ( true == $is_apache ) {
			// only do this if we're on an apache server
			add_filter('post_type_link', array( $this, 'filter_release_permalink' ), 10, 3);
			add_action( 'init', array( $this, 'release_permastruct' ) );
		}
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
		global $is_apache;
		if ( true == $is_apache ) {
			$rewrite = false;
		} else {
			$rewrite = array( 'slug' => 'album' );
		}
	    $labels = array(
			'name' => __('Releases', 'plague-releases'),
			'singular_name' => __('Album', 'plague-releases'),
			'add_new' => __('Add New', 'plague-releases'),
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
			'rewrite' => $rewrite,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'exclude_from_search' => false
	  );

	  register_post_type( 'plague-release', $args );
	}


	public function releases_taxonomies() {
		if ( !taxonomy_exists( 'genre' ) ) {
			register_taxonomy( 'genre', 'plague-release', array( 'hierarchical' => true, 'label' => __('Genre', 'plague-releases'), 'query_var' => 'genre', 'rewrite' => array( 'slug' => 'genre' ) ) ); // this is the genre taxonomy for album releases
		}
		if ( !taxonomy_exists( 'artist' ) ) {
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
			register_taxonomy( 'artist', 'plague-release', array( 'hierarchical' => true, 'labels' => $artist_labels, 'query_var' => 'artist', 'rewrite' => array( 'slug' => 'artist' ) ) ); // this is the artist taxonomy for releases
		}
	}

	public function filter_release_permalink($url, $post_id, $leavename) {
		if (strpos($url, '%artist%') === FALSE) return $url;

		$permalink = null;
		// Get post
		$post = get_post($post_id);
		if (!$post) return $url;

		// Get taxonomy terms
		$terms = get_the_terms($post_id, 'artist');
		if (!is_wp_error($terms) && !empty($terms)) {
			foreach ( $terms as $term ) {
				$taxonomy_slug = $term->slug;
			}
		}
		else {
			$taxonomy_slug = 'no-artist';
		}

		// $permalink_structure = str_replace('%artist%', $taxonomy_slug, $permalink_structure);

		$permalink = str_replace( array( '%artist%', '%postname%' ), array( $taxonomy_slug, $post->post_name ), $url );

		return $permalink;
	}

	public function release_permastruct() {
		global $wp_rewrite;
		$permalink_structure = '/album/%artist%/%postname%/';
		$wp_rewrite->add_rewrite_tag("%postname%", '([^/]+)', "plague-release=");
		$wp_rewrite->add_permastruct('plague-release', $permalink_structure, false);
	}


	/* create custom meta boxes */

	public function custom_meta_boxes_releases() {
	    add_meta_box("releases-details",  __( "Album Details", 'plague-releases' ),  array( $this, "meta_cpt_releases" ), "plague-release", "normal", "low");
		add_meta_box("releases-buy", __( "Purchase Links", 'plague-releases' ), array( $this, "meta_cpt_releases_buy" ),"plague-release","side","low");
	}

	public function meta_cpt_releases() {
	    global $post;

		echo '<input type="hidden" name="releases_noncename" id="releases_noncename" value="' .
		wp_create_nonce( plugin_basename(__FILE__) ) . '" />';


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
		echo '<input class="widefat" type="text" id="datepicker" name="release_date" value="' . wp_kses_post( get_post_meta($post->ID, 'release_date', true ) ) . '" /></p>';
		echo '<p><label for="plague_release_number">' . __( 'Release Number <em>(if applicable)</em>', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="plague_release_number" value="' . wp_kses( get_post_meta( $post->ID, 'plague_release_number', true ), array() ) . '" /></p>';

		if ( get_post_meta( $post->ID, 'plague_release_number', true ) ) {
			$plague_release = get_post_meta( $post->ID, 'plague_release_number', true );
		} else {
			$plague_release = 'PLAGUE000';
		};

		echo '<p><label for="internet_archive">' . __( 'Archive.org Release Identifier', 'plague-releases' ) . '</label><br />';
		echo '<input class="widefat" type="text" name="internet_archive" value="' . wp_kses( get_post_meta( $post->ID, 'internet_archive', true ), array() ) . '" /><br />';
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

	public function rebuild_thumbnail_metabox() {
		remove_meta_box( 'postimagediv', 'plague-release', 'side' );
    	add_meta_box('postimagediv', __('Album Cover', 'plague-releases'), 'post_thumbnail_meta_box', 'plague-release', 'side', 'default');
	}

	/**
	 * Filter for the featured image post box
	 *
	 * @since 	2.0.0
	 */
	public function change_thumbnail_html( $content ) {
	    if ('plague-release' == $GLOBALS['post_type'])
	      add_filter('admin_post_thumbnail_html', array($this,'do_thumb'));
	}

	/**
	 * Replaces "Set featured image" with "Album Book Cover"
	 *
	 * @since 	2.0.0
	 *
	 * @return 	string 	returns the modified text
	 */
	public function do_thumb($content){
		 return str_replace(__('Set featured image'), __('Select Album Cover', 'plague-releases'),$content);
	}

	/* When the post is saved, saves our product data */
	public function releases_save_product_postdata($post_id, $post) {
		$nonce = isset( $_POST['releases_noncename'] ) ? $_POST['releases_noncename'] : 'all the pigs, all lined up';
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
			'title' => __( 'Album', 'plague-releases' ),
			'artist' => __( 'Artist(s)', 'plague-releases' ),
			'release_date' => __( 'Release Date', 'plague-releases' ),
			'genre' => __( 'Genre', 'plague-releases' ),
			'plague_release_number' => __( 'Release Number', 'plague-releases' )
		);
		return $columns;
	}

	/* now we're going to get the data to fill the columns */
	public function releases_manage_release_columns( $column, $post_id ) {
		global $post;
		switch ( $column ) {
			case 'artist' :
				$terms = get_the_terms( $post_id, 'artist' );
				if ( !empty( $terms ) ) {
					$out = array();

					/* Loop through each term, linking to the 'edit posts' page for the specific term */
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'artist' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'artist', 'display' ) )
						);
					}

					/* Join the terms, separating them with a comma. */
					echo join( ', ', $out );
				}
				/* If no terms were found, output a default message. */
				else {
					_e( 'No artists', 'plague-releases' );
				}

				break;
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
					_e( 'No Genres', 'plague-releases' );
				}

				break;

			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}
	public function admin_styles() {
		wp_enqueue_style( 'plague-fonts', plugins_url( 'css/plague-fonts.css', __FILE__ ), array(), $this->version );
		wp_enqueue_style( 'releases-admin-css', plugins_url( 'css/releases-admin.css', __FILE__ ), array(), $this->version );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'plague-releases-js', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery-ui-datepicker' ), '1.0' );
	}

	public function filter_release_content( $content ) {
		global $post;

		// get the artist(s)
		if ( get_the_artist_list() ) {
			$artist_list = get_the_artist_list();
		} else {
			$artist_list = null;
		}
		if ( get_the_artists() ) {
			$artists = get_the_artists();
		} else {
			$artists = null;
		}

		// get the genres
		if ( get_the_genres() ) {
			$genre_list = get_the_genres();
		} else {
			$genre_list = null;
		}

		$entry_open = '<div class="entry-content">';
		$entry_close = '</div>';

		// the artist for output
		$the_artist = null;
		if ( $artists ) {
			$the_artist = '<div class="the_artist">';
			$the_artist .= $artists;
			$the_artist .= '</div>';
		}

		$the_date = null;
		if ( get_post_meta( $post->ID, 'release_date', true ) ) {
			$release_date = get_post_meta( $post->ID, 'release_date', true );
			$the_date = '<div class="release-date">';
			$the_date .= sprintf( '%1$s' . __( 'Release Date:', 'plague-releases' ) . '%2$s %3$s', '<label for="release-date">', '</label>', strip_tags( $release_date ) );
			$the_date .= '</div>';
		}

		$the_release_number = null;
		if ( get_post_meta( $post->ID, 'plague_release_number', true ) ) {
			$release_number = get_post_meta( $post->ID, 'plague_release_number', true );
			$the_release_number = '<div class="release-number">';
			$the_release_number .= sprintf( '%1$s' . __( 'Release Number:', 'plague-releases' ) . '%2$s %3$s', '<label for="release-number">', '</label>', strip_tags( $release_number ) );
			$the_release_number .= '</div><!-- end release number -->';
		}

		// get the thumbnail
		$thumbnail = null;
		if ( has_post_thumbnail( $post->ID ) ) {
			$the_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
			$the_full_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' );
			$thumbnail_url = $the_thumbnail['0'];
			$thumbnail_full_url = $the_full_thumbnail['0'];

			$thumbnail = '<div class="thumbnail alignleft pull-left">';
			$thumbnail .= '<a href="'. htmlspecialchars( $thumbnail_full_url ) . '"><img src="' . $thumbnail_url . '" alt="' . $artist_list . ' - ' . get_the_title( $post->ID ) . '" /></a>';
			$thumbnail .= '</div>';
		}

		// get the purchase link
		$purchase_url = null;
		if ( get_post_meta( $post->ID, 'bandcamp_url', true ) || get_post_meta( $post->ID, 'itunes_url', true ) || get_post_meta( $post->ID, 'spotify_url', true ) || get_post_meta( $post->ID, 'amazonmp3_url', true ) || get_post_meta( $post->ID, 'zune_url', true ) || get_post_meta( $post->ID, 'emusic_url', true ) || get_post_meta( $post->ID, 'napster_url', true ) || get_post_meta( $post->ID, 'rhapsody_url', true ) || get_post_meta( $post->ID, 'reverbnation_buy_url', true ) || get_post_meta( $post->ID, 'internet_archive', true ) ) {
			$bandcamp_url = get_post_meta( $post->ID, 'bandcamp_url', true );
			$itunes_url = get_post_meta( $post->ID, 'itunes_url', true );
			$spotify_url = get_post_meta( $post->ID, 'spotify_url', true );
			$zune_url = get_post_meta( $post->ID, 'zune_url', true );
			$amazonmp3_url = get_post_meta( $post->ID, 'amazonmp3_url', true );
			$napster_url = get_post_meta( $post->ID, 'napster_url', true );
			$emusic_url = get_post_meta( $post->ID, 'emusic_url', true );
			$reverbnation_buy_url = get_post_meta( $post->ID, 'reverbnation_buy_url', true );
			$rhapsody_url = get_post_meta( $post->ID, 'rhapsody_url', true );
			$internet_archive = get_post_meta( $post->ID, 'internet_archive', true );
			$purchase_url = '<div class="purchase-links">' . __( 'Download this album:', 'plague-releases' ) . ' ';
			if ( $bandcamp_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $bandcamp_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-bandcamp"></i>';
				$purchase_url .= '</a>';
			}
			if ( $itunes_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $itunes_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-itunes"></i>';
				$purchase_url .= '</a>';
			}
			if ( $spotify_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $spotify_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-spotify"></i>';
				$purchase_url .= '</a>';
			}
			if ( $zune_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $zune_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-zune"></i>';
				$purchase_url .= '</a>';
			}
			if ( $amazonmp3_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $amazonmp3_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-amazonmp3"></i>';
				$purchase_url .= '</a>';
			}
			if ( $napster_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $napster_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-napster"></i>';
				$purchase_url .= '</a>';
			}
			if ( $emusic_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $emusic_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-emusic"></i>';
				$purchase_url .= '</a>';
			}
			if ( $rhapsody_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $rhapsody_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-rhapsody"></i>';
				$purchase_url .= '</a>';
			}
			if ( $reverbnation_buy_url ) {
				$purchase_url .= '<a href="' . htmlspecialchars( $reverbnation_buy_url ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-reverbnation"></i>';
				$purchase_url .= '</a>';
			}
			if ( $internet_archive ) {
				$purchase_url .= '<a href="http://archive.org/details/' . htmlspecialchars( $internet_archive ) . '" target="_blank">';
				$purchase_url .= '<i class="plague-i-archive"></i>';
				$purchase_url .= '</a>';
			}
			$purchase_url .= '</div>';
		}

		// get the embed code
		$embed_code = null;
		if ( get_post_meta( $post->ID, 'embed_code', true ) ) {
			$embed = get_post_meta( $post->ID, 'embed_code', true );
			$embed_code = '<div class="clear clearfix embed-code">';
			$embed_code .= $embed;
			$embed_code .= '</div>';
		}

		// get the review meta
		$release_meta = null;
		if ( $genre_list ) {
			$release_meta = '<div class="release-meta">';
			if ( $genre_list ) {
				$release_meta .= '<span class="genres">';
				$release_meta .= '<label for="genre-list">' . __( 'Genres:', 'plague-releases' ) . '</label>&nbsp;';
				$release_meta .= $genre_list;
				$release_meta .= '</span>';
			}
			$release_meta .= '</div>';
		}

		// get the track list
		$the_tracklist = null;
		if ( get_post_meta( $post->ID, 'tracklist', true ) ) {
			$tracklist = get_post_meta( $post->ID, 'tracklist', true );
			$the_tracklist = '<div class="tracklist">';
			$the_tracklist .= '<label for="tracklist">' . __( 'Track list:', 'plague-releases' ) . '</label><br />';
			$the_tracklist .= wpautop(wp_kses_post( $tracklist ));
			$the_tracklist .= '</div>';
		}

		$before_content = '<div class="release-entry row">';
		$after_content = '</div>';

		if ( 'plague-release' == get_post_type() && in_the_loop() && is_single($post->ID) ) {
			return $entry_open . $the_artist . $thumbnail . $the_date . $the_release_number . $before_content . $content . $after_content . $purchase_url . $entry_close . $the_tracklist . $embed_code . $release_meta;
		} else {
			return $content;
		}
	}
}