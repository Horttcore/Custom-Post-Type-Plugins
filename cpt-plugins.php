<?php
/*
Plugin Name: Custom Post Type Plugins
Plugin URI: http://horttcore.de
Description: WordPress Custom Post Type Plugins
Version: 1.0
Author: Ralf Hortt
Author URI: http://horttcore.de
License: GPL2
*/



/**
 * Security, checks if WordPress is running
 **/
if ( !function_exists( 'add_action' ) ) :
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
endif;



/*
 * Markdown library
 */
require_once( 'lib/markdown.php' );



/*
 * Html to Markdown library
 */
require_once( 'lib/html-to-markdown.php' );



/**
 *
 *  Custom Post Type Plugins
 *
 */
class Custom_Post_Type_Plugins
{


	const version = 1.0;



	/**
	 * Plugin constructor
	 *
	 * @return void
	 * @author Ralf Hortt
	 * @since 0.1
	 **/
	function __construct()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'enqueue_script', 'register_scripts' );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'init', array( $this, 'register_styles' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'the_content', array( $this, 'the_content' ) );

		add_shortcode( 'README', array( $this, 'shortcode_readme' ) );

		load_plugin_textdomain( 'cpt-plugins', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  );
	}



	/**
	 * Add meta box
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function add_meta_boxes()
	{
		add_meta_box( 'plugin-information', __( 'Plugin information', 'cpt-plugins' ), array( $this, 'meta_box_plugin_information' ), 'plugin' );
		add_meta_box( 'plugin-repositories', __( 'Repositories', 'cpt-plugins' ), array( $this, 'meta_box_repositories' ), 'plugin' );
		add_meta_box( 'plugin-readme', __( 'Readme', 'cpt-plugins' ), array( $this, 'meta_box_readme' ), 'plugin' );
		add_meta_box( 'plugin-stats', __( 'Statistics', 'cpt-plugins' ), array( $this, 'meta_box_statistcs' ), 'plugin' );
	}



	/**
	 * Get download stats
	 *
	 * @param int $post_id Post ID
	 * @return array Statistics
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function get_download_stats( $post_id )
	{
		$meta = get_post_meta( $post_id, '_plugin-meta', TRUE );

		$stats = array();

		if ( isset( $meta['wordpress-repository'] ) && '' != $meta['wordpress-repository'] ) :
			$data = $this->get_wordpress_plugin_information( $meta['wordpress-repository'] );
			$stats['wp-downloads'] = $data->downloaded;
			$stats['wp-rating'] = $data->rating;
			$stats['wp-rating-count'] = $data->num_ratings;
		endif;

		return apply_filters( 'download-stats', $stats );
	}



	/**
	 * Get github statistics
	 *
	 * github don't deliever any metrics
	 *
	 * @param str $user User
	 * @param str $repository Repository
	 * @return [type] [description]
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function get_github_download_stats( $user, $repository )
	{
		return;
	}



	/**
	 * Get WordPress plugin information
	 *
	 * @static
	 * @access public
	 * @param str $repository Repository
	 * @return obj Plugin inforation object
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	static public function get_wordpress_plugin_information( $repository )
	{
		if ( is_numeric( $repository) ) :
			$data = get_post_meta( $repository, '_plugin-meta', TRUE );
			if ( isset( $data['wordpress-repository'] ) )
				$repository = $data['wordpress-repository'];
		endif;

		if ( !is_admin() && FALSE !== ( $plugin_information = get_transient( 'plugin_information_' . $repository ) ) )
			return apply_filters( 'get_wordpress_plugin_information', $plugin_information );

		if ( !$repository)
			return;

		$response = wp_remote_post( 'http://api.wordpress.org/plugins/info/1.0/' . $repository, array(
			'action' => 'plugin_information',
			'slug' => $repository
		));

		if ( is_wp_error( $response ) )
			return FALSE;

		$plugin_information = unserialize( wp_remote_retrieve_body( $response ) );

		set_transient( 'plugin_information_' . $repository, $plugin_information, DAY_IN_SECONDS );
		return apply_filters( 'get_wordpress_plugin_information', $plugin_information );
	}



	/**
	 * Get github statistics
	 *
	 * @static
	 * @access public
	 * @param str $owner Owner
	 * @param str $repository Repository
	 * @param str $format Output format ( HTML/Markdown )
	 * @param bool $echo Echo output
	 * @return str Output
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	static public function github_readme( $owner, $repository, $format = 'HTML',  $echo = TRUE )
	{

		if ( is_admin() || FALSE === ( $plugin_information = get_transient( 'plugin_information' . $owner . '_' . $repository ) ) ) :

			$url = 'https://api.github.com/repos/' . esc_attr( $owner ) . '/' . esc_attr( $repository ) . '/readme';
			$response = wp_remote_get( $url, array( 'sslverify' => false )  );
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			$plugin_information = base64_decode( $response_body->content );

			set_transient( 'plugin_information' . $owner . '_' . $repository, $plugin_information, DAY_IN_SECONDS );

		endif;

		if ( 'HTML' == $format ) :
			$output = apply_filters( 'readme-html', $plugin_information, 'github' );
		elseif ( 'Markdown' == $format ) :
			$output = apply_filters( 'readme-markdown', $plugin_information, 'github' );
		endif;

		if ( $echo )
			echo $output;
		else
			return $output;
	}



	/**
	 * Meta box plugin information
	 *
	 * @return [type] [description]
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function meta_box_plugin_information( $post )
	{
		$data = $this->get_wordpress_plugin_information( $post->ID );

		unset( $data->sections, $data->compatibility );

		if ( !$data ) :

			echo '<strong>' . __( 'Not available', 'cpt-plugins' ) . '</strong>';

		else :

			$this->plugin_information( $data );

		endif;
	}



	/**
	 * Meta box fetch plugin information
	 *
	 * @access public
	 * @param obj $post Post obj
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function meta_box_repositories( $post )
	{
		$data = get_post_meta( $post->ID, '_plugin-meta', TRUE );

		wp_enqueue_script( 'cpt-plugins' );
		wp_enqueue_style( 'cpt-plugins' );

		?>
		<table class="form-table">
			<tr>
				<th rowspan="3"><?php _e( 'Plugin Repository', 'cpt-plugins' ); ?></th>
				<td></td>
			</tr>
			<tr>
				<td>
					<label><input <?php if ( is_array( $data['plugin-repository'] ) && in_array( 'wordpress', $data['plugin-repository'] ) ) echo 'checked="checked"' ?> name="plugin-repository[]" type="checkbox" id="wordpress-repository" value="wordpress" data-autosize="true"> <?php _e( 'WordPress.org', 'cpt-plugins' ) ?></label>
					<div class="wordpress-url">
						http://api.wordpress.org/plugins/info/1.0/<input type="text" name="wordpress-repository" class="wordpress-repository" placeholder="<?php _e( 'Plugin slug', 'cpt-plugins' ); ?>" value="<?php if ( $data ) echo esc_attr( $data['wordpress-repository'] ) ?>">
					</div>
				</td>
			</tr>	<tr>
				<td>
					<label><input <?php if ( is_array( $data['plugin-repository'] ) && in_array( 'github', $data['plugin-repository'] ) ) echo 'checked="checked"' ?> name="plugin-repository[]" type="checkbox" id="github-repository" value="github"> <?php _e( 'Github', 'cpt-plugins' ) ?></label>
					<div class="github-url">
						https://api.github.com/repos/<input type="text" name="github-owner" class="github-owner" placeholder="<?php _e( 'Owner', 'cpt-plugins' ); ?>" value="<?php if ( $data ) echo esc_attr( $data['github-owner'] ) ?>" data-autosize="true">/<input type="text" name="github-repository" class="github-repository" placeholder="<?php _e( 'Repository', 'cpt-plugins' ); ?>" value="<?php if ( $data ) echo esc_attr( $data['github-repository'] ) ?>" data-autosize="true">/readme
					</div>
				</td>
			</tr>
			<?php do_action( 'plugin-repositories', $post ) ?>
		</table>
		<?php
		wp_nonce_field( 'save-plugin-meta', 'save-plugin-meta-nonce' );

	}



	/**
	 * Meta box plugin information
	 *
	 * @access public
	 * @param obj $post Post obj
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function meta_box_readme( $post )
	{
		$meta = get_post_meta( $post->ID, '_plugin-meta', TRUE );

		if ( !isset( $meta['plugin-repository'] ) ) :

			echo '<strong>' . __( 'Not available', 'cpt-plugins' ) . '</strong>';

		else :

			?><h4><label><input <?php checked( 'custom', $meta['readme'] ) ?> type="radio" name="plugin-readme" value="custom"> <?php _e( 'Don\'t use a readme file', 'cpt-plugins' ); ?></label></h4><?php

			if ( 'wordpress' == $meta['plugin-repository'] || in_array( 'wordpress', $meta['plugin-repository'] ) ) :
				?>
				<h4><label><input <?php checked( 'wordpress', $meta['readme'] ) ?> type="radio" name="plugin-readme" value="wordpress"> <?php _e( 'Use WordPress.org plugin information', 'cpt-plugins' ); ?></label></h4>
				<div class="readme-preview">
					<?php echo apply_filters( 'the_content', $this->wordpress_readme( $meta['wordpress-repository'], 'Markdown', FALSE ) ) ?>
				</div>
				<?php
			endif;

			if ( 'github' == $meta['plugin-repository'] || in_array( 'github', $meta['plugin-repository'] ) ) :
				?>
				<h4><label><input <?php checked( 'github', $meta['readme'] ) ?> type="radio" name="plugin-readme" value="github"> <?php _e( 'Use Github readme', 'cpt-plugins' ); ?></h4></label>
				<div class="readme-preview">
					<?php echo apply_filters( 'the_content', $this->github_readme( $meta['github-owner'], $meta['github-repository'], 'Markdown', FALSE ) ) ?>
				</div>
				<?php
			endif;

			do_action( 'plugin-repository-readme', $post );

		endif;
	}



	/**
	 * Meta box plugin statistics
	 *
	 * @access public
	 * @param obj $post Post obj
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function meta_box_statistcs( $post )
	{
		$stats = $this->get_download_stats( $post->ID );

		if ( !$stats ) :

			echo '<strong>' . __( 'Not available', 'cpt-plugins' ) . '</strong>';

		else :

			?>
			<table class="form-table download-stats">
				<tr>
					<th colspan="2"><strong><?php _e( 'WordPress.org statistics', 'cpt-plugins' ); ?></strong></th>
				</tr>
				<tr>
					<th>Downloads</th>
					<td><?php echo number_format_i18n( $stats['wp-downloads'], 0 ) ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Rating Score', 'cpt-plugins' ); ?></th>
					<td><?php echo number_format_i18n( $stats['wp-rating'], 2 ) ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Rating Count', 'cpt-plugins' ); ?></th>
					<td><?php echo number_format_i18n( $stats['wp-rating-count'], 0 ) ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Average', 'cpt-plugins' ); ?></th>
					<td><?php echo number_format_i18n( $stats['wp-rating'] / $stats['wp-rating-count'], 2 ) ?></td>
				</tr>
			</table>
			<?php

		endif;

		do_action( 'meta_box_statistcs' );
	}



	public function plugin_information( $data )
	{
		$data = apply_filters( 'plugin_information', $data );

		if ( $data ) :

			?>
			<table class="form-table">
			<?php

			foreach ( $data as $key => $value ) :

				switch ( $key ) :

					case 'name' :
						$title = __( 'Name', 'cpt-plugins' );
						$val = $value;
						break;

					case 'slug' :
						$title = __( 'Slug', 'cpt-plugins' );
						$val = $value;
						break;

					case 'version' :
						$title = __( 'Version', 'cpt-plugins' );
						$val = $value;
						break;

					case 'requires' :
						$title = __( 'Requires', 'cpt-plugins' );
						$val = $value;
						break;

					case 'tested' :
						$title = __( 'Tested', 'cpt-plugins' );
						$val = $value;
						break;

					case 'rating' :
						$title = __( 'Rating', 'cpt-plugins' );
						$val = $value;
						break;

					case 'num_ratings' :
						$title = __( 'Ratings', 'cpt-plugins' );
						$val = $value;
						break;

					case 'downloaded' :
						$title = __( 'Downloaded', 'cpt-plugins' );
						$val = $value;
						break;

					case 'short_description' :
						$title = __( 'Short description', 'cpt-plugins' );
						$val = $value;
						break;

					case 'author' :
						$title = __( 'Author', 'cpt-plugins' );
						$val = $value;
						break;

					case 'last_updated' :
						$title = __( 'Last updated', 'cpt-plugins' );
						$val = date_i18n( get_option( 'date_format'), strtotime( $value ) );
						break;

					case 'added' :
						$title = __( 'Added', 'cpt-plugins' );
						$val = date_i18n( get_option( 'date_format'), strtotime( $value ) );
						break;

					case 'homepage' :
						$title = __( 'Homepage', 'cpt-plugins' );
						$val = make_clickable( $value );
						break;

					case 'author_profile' :
						$title = __( 'Author profile', 'cpt-plugins' );
						$val = make_clickable( $value );
						break;

					case 'download_link' :
						$title = __( 'Download link', 'cpt-plugins' );
						$val = make_clickable( $value );
						break;

					case 'contributors' :
						$title = __( 'Contributors', 'cpt-plugins' );

						if ( is_array( $value ) ) :

							$val = '';
							$i = 1;

							foreach ( $value as $k => $v ) :

								$val .= '<a href="' . $v . '">' . $k . '</a>';

								if ( $i + 1 == count( $value ) )
									$val .= ' ' . _x( 'and', 'John and Doe', 'cpt-plugins' ) . ' ';
								elseif ( 2 < count( $value ) && $i < count( $value ) )
									$val .= ', ';

								$i++;

							endforeach;

						else :

							$val = $value;

						endif;

						break;

					case 'tags' :
						$title = __( 'Tags', 'cpt-plugins' );
						if ( is_array( $value ) )
							$val = implode( ', ', $value );
						else
							$val = $value;
						break;

					default :
						$title = apply_filters( 'plugin_information_' . $key, $title );
						$val = apply_filters( 'plugin_information_' . $key . '_value', $value );

				endswitch;

				?>
				<tr>
					<th><?php echo $title ?></th>
					<td><?php echo $val ?></td>
				</tr>
				<?php

			endforeach;

			?>
			</table>
			<?php

		endif;

	}



	/**
	 * Post updated messages
	 *
	 * @param array $messages Update Messages
	 * @return void
	 * @author Ralf Hortt
	 * @since 0.1
	 **/
	public function post_updated_messages( array $messages )
	{
		global $post, $post_ID;

		$messages['plugin'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Plugin updated. <a href="%s">View Plugin</a>', 'cpt-plugins' ), esc_url( get_permalink($post_ID) ) ),
			2 => __( 'Custom field updated.', 'cpt-plugins' ),
			3 => __( 'Custom field deleted.', 'cpt-plugins' ),
			4 => __( 'Plugin updated.', 'cpt-plugins' ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __( 'Plugin restored to revision from %s', 'cpt-plugins' ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6 => sprintf( __( 'Plugin published. <a href="%s">View Plugin</a>', 'cpt-plugins' ), esc_url( get_permalink($post_ID) ) ),
			7 => __( 'Plugin saved.', 'cpt-plugins' ),
			8 => sprintf( __( 'Plugin submitted. <a target="_blank" href="%s">Preview Plugin</a>', 'cpt-plugins' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Plugin scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Plugin</a>', 'cpt-plugins' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __( 'Plugin draft updated. <a target="_blank" href="%s">Preview Plugin</a>', 'cpt-plugins' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		return $messages;
	}



	/**
	 * Readme
	 *
	 * @static
	 * @return [type] [description]
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	static public function readme( $format = 'HTML', $echo = TRUE, $post_id = FALSE )
	{
		$post_id = ( FALSE !== $post_id ) ? $post_id : get_the_ID();

		$meta = get_post_meta( $post_id, '_plugin-meta', TRUE );

		if ( 'wordpress' == $meta['readme'] )
			$output = Custom_Post_Type_Plugins::wordpress_readme( $meta['wordpress-repository'], 'HTML', FALSE );
		elseif ( 'github' == $meta['readme'] )
			$output = Custom_Post_Type_Plugins::github_readme( $meta['github-owner'], $meta['github-repository'], 'HTML', FALSE );
		else
			return;

		if ( 'HTML' == $format ) :
			$output = Markdown::defaultTransform($output);
		endif;

		if ( $echo )
			echo $output;
		else
			return $output;
	}



	/**
	 * Register post type
	 *
	 * @access public
	 * @return void
	 * @author Ralf Hortt
	 * @since 0.1
	 **/
	public function register_post_type()
	{
		$labels = array(
			'name' => _x( 'Plugins', 'post type general name', 'cpt-plugins' ),
			'singular_name' => _x( 'Plugin', 'post type singular name', 'cpt-plugins' ),
			'add_new' => _x( 'Add New', 'Plugin', 'cpt-plugins' ),
			'add_new_item' => __( 'Add New Plugin', 'cpt-plugins' ),
			'edit_item' => __( 'Edit Plugin', 'cpt-plugins' ),
			'new_item' => __( 'New Plugin', 'cpt-plugins' ),
			'view_item' => __( 'View Plugin', 'cpt-plugins' ),
			'search_items' => __( 'Search Plugins', 'cpt-plugins' ),
			'not_found' =>	__( 'No Plugins found', 'cpt-plugins' ),
			'not_found_in_trash' => __( 'No Plugins found in Trash', 'cpt-plugins' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Plugins', 'cpt-plugins' )
		);

		$args = array(
			'labels' => $labels,
			'public' => TRUE,
			'publicly_queryable' => TRUE,
			'show_ui' => TRUE,
			'show_in_menu' => TRUE,
			'query_var' => TRUE,
			'rewrite' => array( 'slug' => _x( 'plugin', 'post type slug', 'cpt-plugins' ) ),
			'capability_type' => 'post',
			'has_archive' => TRUE,
			'hierarchical' => TRUE,
			'menu_position' => NULL,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' )
		);

		register_post_type( 'plugin', $args );
	}



	/**
	 * Register scripts
	 *
	 * @access public
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function register_scripts()
	{
		wp_register_script( 'cpt-plugins', plugins_url( '/javascript/cpt-plugins.js', __FILE__ ), array( 'jquery' ), Custom_Post_Type_Plugins::version, TRUE );
	}



	/**
	 * Register scripts
	 *
	 * @access public
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function register_styles()
	{
		wp_register_style( 'cpt-plugins', plugins_url( '/css/cpt-plugins.css', __FILE__ ), Custom_Post_Type_Plugins::version, TRUE );
	}



	/**
	 * Register taxonomy
	 *
	 * @static public
	 * @return void
	 * @author Ralf Hortt
	 * @since 0.1
	 **/
	public function register_taxonomies()
	{
		// Plugin Category
		$labels = array(
			'name' => _x( 'Tags', 'taxonomy general name', 'cpt-plugins' ),
			'singular_name' => _x( 'Tag', 'taxonomy singular name', 'cpt-plugins' ),
			'search_items' =>	__( 'Search Tags', 'cpt-plugins' ),
			'popular_items' => __( 'Popular Tags', 'cpt-plugins' ),
			'all_items' => __( 'All Tags', 'cpt-plugins' ),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __( 'Edit Tag', 'cpt-plugins' ),
			'update_item' => __( 'Update Tag', 'cpt-plugins' ),
			'add_new_item' => __( 'Add New Tag', 'cpt-plugins' ),
			'new_item_name' => __( 'New Tag Name', 'cpt-plugins' ),
			'menu_name' => __( 'Tag', 'cpt-plugins' ),
		);

		register_taxonomy( 'plugin-tag','plugin',array(
			'hierarchical' => FALSE,
			'labels' => $labels,
			'show_ui' => TRUE,
			'query_var' => TRUE,
			'rewrite' => array( 'slug' => _x( 'plugin-tag', 'taxonomy slug', 'cpt-plugins' ) ),
			'show_admin_column' => TRUE,
		));
	}



	/**
	 * Callback to save the plugin meta data
	 *
	 * @access public
	 * @param int $post_id Post ID
	 * @return void
	 * @author Ralf Hortt
	 * @since 0.1
	 **/
	public function save_post( $post_id )
	{
		// Stop if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// Validate nonce
		if ( !isset( $_POST['save-plugin-meta-nonce'] ) || !wp_verify_nonce( $_POST['save-plugin-meta-nonce'], 'save-plugin-meta' ) )
			return;

		// Collect data
		$meta = array(
			/*
			'requires' => sanitize_text_field( $_POST['plugin-requires'] ),
			'compatible' => sanitize_text_field( $_POST['plugin-compatible'] ),
			'version' => sanitize_text_field( $_POST['plugin-version'] ),
			'download' => sanitize_text_field( $_POST['plugin-download'] ),
			'donate' => sanitize_text_field( $_POST['plugin-donate'] ),
			*/
			# Fetch
			'plugin-repository' => array_map( 'sanitize_text_field', $_POST['plugin-repository']),
			# WordPress.org
			'wordpress-repository' => sanitize_text_field( $_POST['wordpress-repository'] ),
			# Github
			'github-owner' => sanitize_text_field( $_POST['github-owner'] ),
			'github-repository' => sanitize_text_field( $_POST['github-repository'] ),
			# Readme
			'readme' => sanitize_text_field( $_POST['plugin-readme'] ),
		);

		// Save
		update_post_meta( $post_id, '_plugin-meta', $meta );

		if ( sanitize_text_field( $_POST['wordpress-repository'] ) ) :

			$plugin_information = $this->get_wordpress_plugin_information( $_POST['wordpress-repository'] );
			if ( is_array( $plugin_information->tags ) ) :

				wp_set_object_terms( $post_id, $plugin_information->tags, 'plugin-tag' );

			endif;

		endif;
	}



	/**
	 * Shortcode [README]
	 *
	 * @access public
	 * @param array  Attributes
	 * @param str  Content
	 * @return void
	 * @author Ralf Hortt
	 **/
	public function shortcode_readme( $atts = FALSE, $content = FALSE ){

		extract( shortcode_atts( array(
			'format' => 'HTML'
		), $atts ) );

		return $this->readme( $format, FALSE );

	}



	/**
	 * Inject the content
	 *
	 * @param str $content Content
	 * @return str Content
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	public function the_content( $content )
	{
		if ( !is_singular( 'plugin' ) )
			return $content;

		$meta = get_post_meta( get_the_ID(), '_plugin-meta', TRUE );
		if ( !$meta['wordpress-repository'] && !$meta['github-owner'] && !$meta['github-repository'] )
			return $content;

		if ( has_shortcode( $content, 'README' ) )
			return $content;

		if ( TRUE === apply_filters( 'readme-include-before', FALSE ) ) :

			return apply_filters( 'the-readme-content', $this->readme( 'HTML', FALSE ) . $content );

		elseif ( TRUE === apply_filters( 'readme-include-after', TRUE ) ) :

			return apply_filters( 'the-readme-content', $content . $this->readme( 'HTML', FALSE ) );

		endif;
	}



	/**
	 * WordPress.org readme
	 *
	 * @static
	 * @access public
	 * @return str Readme
	 * @since v1.0
	 * @author Ralf Hortt
	 */
	static public function wordpress_readme( $repository, $format = 'HTML', $echo = TRUE )
	{
		$response = Custom_Post_Type_Plugins::get_wordpress_plugin_information( $repository );

		if ( $response->sections ) :

			$content = '';

			foreach ( $response->sections as $key => $section ) :

				$content .= '<' . apply_filters( 'wordpress-section-headline', 'h2' ) . '>' . apply_filters( 'wordpress-section-title', __( str_replace( '_', ' ', ucfirst( $key ) ) ), 'cpt-plugins' ) . '</' . apply_filters( 'wordpress-section-headline', 'h2' ) . '>' . $section;

			endforeach;

		endif;

		if ( 'HTML' == $format ) :
			$output = apply_filters( 'readme-html', $content, 'wordpress' );
		elseif ( 'Markdown' == $format ) :
			$output = new HTML_To_Markdown( apply_filters( 'readme-markdown', $content, 'wordpress' ) );
		endif;

		if ( $echo )
			echo $output;
		else
			return $output;

	}



}

new Custom_Post_Type_Plugins;
