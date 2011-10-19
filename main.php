<?php
/*
Plugin Name: Releases
Plugin URI: http://arcanepalette.com
Description: CPT plugin for album releases, built for <a href="http://plaguemusic.com">Plague Music</a>.
Version: 1.2.2
Author: Arcane Palette Creative Design
Author URI: http://arcanepalette.com/
*/

/* changelog */
/*
    Version 1.1
	added artist taxonomy
    Version 1.1.1
	removed post thumbnails (redundant since we're using a custom image uploader)
    Version 1.1.2
	added note to tracklist textarea description
    Version 1.1.3
	sanitized textarea to prevent malicious code from being embedded in release pages
	embed code area obviously needs html to be allowed.  sanitized the html before it goes to the database and put a strongly-worded warning threatening anyone who misuses their privelages
	Version 1.1.4
	added release date and plague release number
	Version 1.1.5
	added archive.org release identifier
	Version 1.1.6
	troubleshot why plague release wasn't displaying in input box when value was the same as archive.org release identifier (answer, i have no idea, but it's working)
	added plague release number to echo under the input box in case of above issue
	added option to insert plague release number into the description of the internet archive input box	
	Version 1.2 
	added custom columns for releases
	Version 1.2.1
	added more options for buy now links
	Version 1.2.2
	changed plague_release meta tag name to plague_release_number -- seems to be working in the columns now
*/

/* create the custom post type */
function post_type_releases() {
    $labels = array(
		'name' => _x('Album Releases', 'post type general name'),
		'singular_name' => _x('Album', 'post type singular name'),
		'add_new' => _x('Add New', 'product'),
		'add_new_item' => __('Add New Album'),
		'edit_item' => __('Edit Album'),
		'edit' => _x('Edit', 'releases'),
		'new_item' => __('New Album'),
		'view_item' => __('View Album'),
		'search_items' => __('Search Album Releases'),
		'not_found' =>  __('No releases found'),
		'not_found_in_trash' => __('No releases found in Trash'),
		'view' =>  __('View Album Release'),
		'parent_item_colon' => ''	
  );
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array("slug" => "releases"),
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title','editor', ),	
		'exclude_from_search' => false
  );

  register_post_type( 'releases', $args );
}

add_action( 'init', 'post_type_releases', 0 );

add_action( 'init', 'releases_taxonomies', 0 ); // taxonomy for genre

function releases_taxonomies() { 
	register_taxonomy( 'genre', 'releases', array( 'hierarchical' => true, 'label' => __('Genre', 'taxonomy general name'), 'query_var' => 'genre', 'rewrite' => array( 'slug' => 'genre' ) ) ); // this is the genre taxonomy for album releases
$artist_labels = array(
	'name' => __( 'Artists' ),
	'singular_name' => __( 'Artists' ),
	'search_items' => __( 'Search Artists' ),
	'all_items' => __( 'All Artists' ),
	'edit_item' => __( 'Edit Artist' ),
	'update_item' => __( 'Update Artist' ),
	'add_new_item' => __( 'Add New Artist' ),
	'new_item_name' => __( 'New Artist Name' ),
);	
	register_taxonomy( 'artist', 'releases', array( 'hierarchical' => true, 'labels' => $artist_labels, 'query_var' => 'artist', 'rewrite' => array( 'slug' => 'artist' ) ) ); // this is the artist taxonomy for releases
	}
	

/* create custom meta boxes */

function custom_meta_boxes_releases() {
    add_meta_box("releases-details", "Album Details", "meta_cpt_releases", "releases", "normal", "low");
	add_meta_box("releases-buy","Purchase Links","meta_cpt_releases_buy","releases","side","low");
}

add_action('admin_menu', 'custom_meta_boxes_releases');

function meta_cpt_releases() {
    global $post;

	echo '<input type="hidden" name="releases_noncename" id="releases_noncename" value="' .
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

   	echo '<label for="album_art">Album Art</label><br />';

	//ajax upload
	$wud = wp_upload_dir();

?>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var uploader = new qq.FileUploader({
				element: document.getElementById('album_art_upload'),
				action: '<?php echo get_bloginfo('siteurl'); ?>/',
				onComplete: function (id,fileName,responseJSON) {
					if(responseJSON.success == true)
						jQuery('#album_art').val('<?php echo $wud["url"]; ?>/'+fileName);
				}
			});           
		});	
		</script>
	<input style="width: 55%;" id="album_art" name="album_art" value="<?php echo get_post_meta($post->ID, 'album_art', true); ?>" type="text" /><div id="album_art_upload"></div>
	<?php	


	echo '<label for="url_to_buy">URL to purchase album</label><br />';
	echo '<input style="width: 55%;" type="text" name="url_to_buy" value="'.get_post_meta($post->ID, 'url_to_buy', true).'" /><br /><br />';
	echo '<label for="tracklist">Track List (HTML is <em>not</em> allowed.)</label><br />';
	echo '<textarea style="width: 55%;" rows="5" cols="50" name="tracklist" />'.htmlspecialchars(get_post_meta($post->ID, 'tracklist', true)).'</textarea><br /><br />';
	
	echo '<label for="embed_code">Player Embed Code</label><br />';
	echo '<textarea style="width: 55%;" rows="5" cols="50" name="embed_code" />'.htmlspecialchars(get_post_meta($post->ID, 'embed_code', true)).'</textarea><br />(HTML is (obviously) allowed here.  However, if you embed anything other than a valid embed code for your player (like malicious scripts, iframes to anything other than a music player, gratuitous fancypants code, etc.), your account will be banned, you will be blacklisted from the site with no refund, and we will hunt you down with wolves and slaughter you in your sleep.  Don\'t do it.)<br />';
	
	echo '<label for="release_date">Album Release Date</label><br />';
	echo '<input style="width: 55%;" type="text" name="release_date" value="'.get_post_meta($post->ID, 'release_date', true).'" /><br /><br />';
	echo '<label for="plague_release_number">Plague Music Release number <em>(if applicable)</em></label><br />';
	echo '<input style="width: 55%;" type="text" name="plague_release_number" value"'.get_post_meta($post->ID, 'plague_release_number', true).'" /><br />';
	
	if (get_post_meta($post->ID,'plague_release_number')) { $plague_release = get_post_meta($post->ID,'plague_release_number',true); } else { $plague_release = 'PLAGUE000'; };
	echo '<p><label for="internet_archive">Archive.org Release Identifier</label><br />';
	echo '<input style="width: 55%;" type="text" name="internet_archive" value="'.get_post_meta($post->ID,'internet_archive', true).'" /><br />';
	echo 'If the release is posted in the Internet Archive, add the Plague Release number or other release identifier here, e.g. if the URL to your release is http://archive.org/details/'.$plague_release.', enter '.$plague_release.' here.';
	
}

function meta_cpt_releases_buy() {
  global $post;

	echo '<input type="hidden" name="releases_noncename" id="releases_noncename" value="' .
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	echo '<p><label for="bandcamp_url">Bandcamp URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="bandcamp_url" value="'.get_post_meta($post->ID,'bandcamp_url',true).'" /></p>';
	echo '<p><label for="itunes_url">iTunes URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="itunes_url" value="'.get_post_meta($post->ID,'itunes_url',true).'" /></p>';
	echo '<p><label for="spotify_url">Spotify URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="spotify_url" value="'.get_post_meta($post->ID,'spotify_url',true).'" /></p>';	
	echo '<p><label for="amazonmp3_url">AmazonMP3 URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="amazonmp3_url" value="'.get_post_meta($post->ID,'amazonmp3_url',true).'" /></p>';	
	echo '<p><label for="zune_url">Zune URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="zune_url" value="'.get_post_meta($post->ID,'zune_url',true).'" /></p>';	
	echo '<p><label for="emusic_url">eMusic URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="emusic_url" value="'.get_post_meta($post->ID,'emusic_url',true).'" /></p>';	
	echo '<p><label for="napster_url">Napster URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="napster_url" value="'.get_post_meta($post->ID,'napster_url',true).'" /></p>';	
	echo '<p><label for="rhapsody_url">Rhapsody URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="rhapsody_url" value="'.get_post_meta($post->ID,'rhapsody_url',true).'" /></p>';	
	echo '<p><label for="reverbnation_buy_url">Reverbnation URL</label><br />';
	echo '<input style="width: 100%;" type="text" name="reverbnation_buy_url" value="'.get_post_meta($post->ID,'reverbnation_buy_url',true).'" /></p>';	
}

/* deal with uploading image */
if(isset ($_GET["qqfile"]) && strlen($_GET["qqfile"]))
{
	$pluginurl = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
	include(WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/' . 'includes/upload.php');
	$wud = wp_upload_dir();

	/* list of valid extensions */
	$allowedExtensions = array('jpg', 'jpeg', 'gif', 'png', 'ico');

	/* max file size in bytes */
	$sizeLimit = 6 * 1024 * 1024;

	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($wud['path'].'/',true);
	
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	exit;
}


function releases_uploader_scripts() {
	
	$pluginurl = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));

	wp_enqueue_script('fileuploader', $pluginurl.'/includes/fileuploader.js',array('jquery'));
	wp_enqueue_style('fileuploadercss',$pluginurl.'/css/fileuploader.css');
}

function releases_uploader_styles() {
	$pluginurl = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));
	
	wp_enqueue_style('thickbox');
	wp_enqueue_style('fileuploadercss', $pluginurl.'/css/fileuploader.css');
}

add_action('admin_print_scripts', 'releases_uploader_scripts');
add_action('admin_print_styles', 'releases_uploader_styles');

/* When the post is saved, saves our product data */
function releases_save_product_postdata($post_id, $post) {
   	if ( !wp_verify_nonce( $_POST['releases_noncename'], plugin_basename(__FILE__) )) {
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
	foreach ($_POST as $key => $value) {
        $mydata[$key] = $value;
    }

	/* Add values of $mydata as custom fields */
	foreach ($mydata as $key => $value) {
		if( $post->post_type == 'revision' ) return;
		$value = implode(',', (array)$value);
		if(get_post_meta($post->ID, $key, FALSE)) {
			update_post_meta($post->ID, $key, $value);
		} else {
			add_post_meta($post->ID, $key, $value);
		}
		if(!$value) delete_post_meta($post->ID, $key);
	}
}

add_action('save_post', 'releases_save_product_postdata', 1, 2); // save the custom fields


/* add some custom columns */
add_filter('manage_edit-releases_columns','releases_edit_release_columns'); 
function releases_edit_release_columns( $columns ) {
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

add_action( 'admin_head', 'releases_icon' );
function releases_icon() {
    ?>
    <style type="text/css" media="screen">
        #menu-posts-releases .wp-menu-image {
            background: url(<?php bloginfo('wpurl'); ?>/wp-content/plugins/releases/images/disc-case.png) no-repeat 6px -17px !important;
        }
	#menu-posts-releases:hover .wp-menu-image, #menu-posts-releases.wp-has-current-submenu .wp-menu-image {
			background: url(<?php bloginfo('wpurl'); ?>/wp-content/plugins/releases/images/disc-case.png) no-repeat 6px 7px !important;
        }
    </style>

<?php } 

add_action('admin_head', 'releases_header');
function releases_header() {
        global $post_type;
	?>
	<style>
	<?php if (($_GET['post_type'] == 'releases') || ($post_type == 'releases')) : ?>
	#icon-edit { background: url(<?php bloginfo('wpurl'); ?>/wp-content/plugins/releases/images/music.png) no-repeat!important; }		
	<?php endif; ?>
        </style>
    <?php } ?>
