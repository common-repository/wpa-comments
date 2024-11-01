<?php
/**
 * Plugin Name:  WPA Comments
 * Description:  Takes preprepared lists of comments and comment authors and randomizes them before inserting into database.
 * Version:      1.0
 * Author:       ziemekpr0
 * Author Email: ziemekpr0@gmail.com
 * Author URI:   http://wpadmin.pl
 * Text Domain:  wpa-comments
 * Domain Path:  /languages
 * License:      GPLv2 or later
 */


/* Security check - block direct access */
if (!defined('ABSPATH')) exit('No direct script access allowed');


/* Constants */
define('WPAC_DIR_PATH', plugin_dir_path(__FILE__));
define('WPAC_VERSION', '1.0');

class WPA_Comments
{
	// we will generate comments with random dates, starting from the week before
	private $date_min = 7;

	public function __construct()
	{
		/* Plugin init - loads plugins textdomain */
		add_action('plugins_loaded', array(&$this, 'my_plugin_init'));

		/* Add menu items to dashboard */
		add_action('admin_menu', array(&$this, 'admin_menu_items'));
		
		/* Register settings */
		add_action('admin_init', array(&$this, 'register_settings'));

		/* Handle form post - add comments to db */
		add_action('admin_post_wpa_add_comments', array(&$this, 'add_comments'));

	}

	// -------------------------------------------------------------------

	public function my_plugin_init()
	{
		$languages_path = basename(dirname(__FILE__)) .'/languages';
		load_plugin_textdomain('wpa-comments', false, $languages_path);
	}

	// -------------------------------------------------------------------

	/* Add menu options */
	public function admin_menu_items()
	{
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page('WPA Comments', 'WPA Comments', 'manage_options', 'wpa-comments', array(&$this, 'view_main'), 'dashicons-star-filled');
			
			// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
			add_submenu_page('wpa-comments', __('Authors', 'wpa-comments'), __('Authors', 'wpa-comments'), 'manage_options', 'wpa-comments-manage-users', array(&$this, 'view_users_form'));
			add_submenu_page('wpa-comments', __('Comments', 'wpa-comments'), __('Comments', 'wpa-comments'), 'manage_options', 'wpa-comments-manage-comments', array(&$this, 'view_comments_form'));
	}

	// -------------------------------------------------------------------

	public function register_settings()
	{
		register_setting('wpa_comments_authors', 'wpa_comments_authors', array(&$this, 'sanitize_input'));
		register_setting('wpa_comments_comments', 'wpa_comments_comments', array(&$this, 'sanitize_input'));
	}

	// -------------------------------------------------------------------

	public function add_comments()
	{

		/* validate nonce */
		if(!check_admin_referer('wpa_add_comments', 'wpa_add_comments_nonce')) {
			wp_redirect(esc_url($_SERVER['HTTP_REFERER'] .'&settings-updated=false')); exit;
		}

		global $wpdb;
		$prefix = $wpdb->prefix;

		if(isset($_POST['mode-remove']))
		{
			// get posts ids for later recalculation
			// SELECT comment_post_ID, count(comment_post_ID) AS comment_count FROM wp_comments WHERE comment_agent = "WPA_Comments" GROUP BY comment_post_ID
			$posts = $wpdb->get_results( 'SELECT comment_post_ID, count(comment_post_ID) AS comment_count FROM '. $prefix .'comments WHERE comment_agent = "WPA_Comments" GROUP BY comment_post_ID;', OBJECT );
			$wpdb->delete( $prefix .'comments', array("comment_agent" => "WPA_Comments"));

			// recalculate comments counts
			if(!empty($posts))
				foreach ($posts as $post)
					wp_update_comment_count($post->comment_post_ID);

			// rows should be deleted so nothing more to do here
			wp_redirect(esc_url($_SERVER['HTTP_REFERER'] .'&settings-updated=true')); exit;

		}
		else if(isset($_POST['mode-1']))
		{
			// all published posts
			$posts = $wpdb->get_results( 'SELECT ID, post_title FROM '. $prefix .'posts WHERE post_type="post" AND post_status = "publish";', OBJECT );
		}
		else if(isset($_POST['mode-2']) && isset($_POST['wpa-m2-condition']) && isset($_POST['wpa-m2-param']))
		{
			switch($_POST['wpa-m2-condition'])
			{
				case 'lt':
					$condition = '<';
					break;
				case 'gt':
					$condition = '>';
					break;
				default:
					$condition = '=';

			}
			// all published with comments count
			$posts = $wpdb->get_results( 'SELECT ID, post_title FROM '. $prefix .'posts WHERE post_type="post" AND post_status = "publish" AND comment_count'. $condition . esc_sql($_POST['wpa-m2-param']) .';', OBJECT );
		}
		else if(isset($_POST['mode-3']) && isset($_POST['wpa-csv-list']))
		{
			// posts list
			$posts = $wpdb->get_results( 'SELECT ID, post_title FROM '. $prefix .'posts WHERE post_type="post" AND post_status = "publish" AND ID IN ('. esc_sql($_POST['wpa-csv-list']) .');', OBJECT );
		}
		else
		{
			// something went wrong, so just give up and return to the previous page
			wp_redirect(esc_url($_SERVER['HTTP_REFERER'] .'&settings-updated=false')); exit;
		}


		// if there's no post then go back
		if(empty($posts)){wp_redirect(esc_url($_SERVER['HTTP_REFERER'] .'&settings-updated=false')); exit;}

		$comments = explode("\n", str_replace("\r", "", get_option('wpa_comments_comments')));
		$authors = explode("\n", str_replace("\r", "", get_option('wpa_comments_authors')));

		// if there's no comments entrys or authors then dont bother
		if(empty($comments) || empty($authors)) {wp_redirect(esc_url($_SERVER['HTTP_REFERER'] .'&settings-updated=false')); exit;}

		// determine comments count ranges
		if($_POST['wpa-comments-min'] <= $_POST['wpa-comments-max'])
		{
			$noc_min = $_POST['wpa-comments-min'];
			$noc_max = $_POST['wpa-comments-max'];
		}
		else
		{
			$noc_min = $_POST['wpa-comments-max'];
			$noc_max = $_POST['wpa-comments-min'];
		}

		foreach($posts as $post)
		{
			// number of comments to add
			$noc = mt_rand($noc_min, $noc_max);

			for($i=0;$i<$noc;$i++)
			{ 

				$data = array(
					'comment_post_ID' => $post->ID,
					'comment_author' => sanitize_text_field($authors[array_rand($authors, 1)]),
					'comment_author_email' => 'admin@admin.com',
					'comment_author_url' => '',
					'comment_content' => sanitize_text_field($comments[array_rand($comments, 1)]),
					'comment_type' => '',
					'comment_parent' => 0,
					'user_id' => 0,
					'comment_author_IP' => '127.0.0.1',
					'comment_agent' => 'WPA_Comments',
					'comment_date' => date('Y-m-d H:i:s', mt_rand(time()-($this->date_min * 24 * 60 * 60), time())),
					'comment_approved' => 1,
				);

				wp_insert_comment($data);

			}

		}

		add_settings_error( 'wpa-comments', esc_attr( 'settings_updated' ), 'Komentarze zostały dodane', 'updated' );

		wp_redirect(esc_url($_SERVER['HTTP_REFERER']) .'&settings-updated=true'); exit;
	}

	// -------------------------------------------------------------------
	
	public function view_main()
	{
		?>
		<div class="wrap">
			<h1><?php _e('WPA Comments Generator', 'wpa-comments'); ?></h1>
			
			<?php if(isset($_GET['settings-updated']) && $_GET['settings-updated']=='true'): ?>
			<div class="notice notice-success is-dismissible"> 
				<p><strong><?php _e('Settings saved.', 'wpa-comments') ?></strong></p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e('Dismiss this notice.', 'wpa-comments') ?></span>
				</button>
			</div>
			<?php elseif(isset($_GET['settings-updated']) && $_GET['settings-updated']=='false'): ?>
			<div class="notice notice-error is-dismissible"> 
				<p><strong><?php _e('Something went wrong.', 'wpa-comments') ?></strong></p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e('Dismiss this notice.', 'wpa-comments') ?></span>
				</button>
			</div>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="wpa-form" method="post">
				<input name="action" value="wpa_add_comments" type="hidden" />
				<?php wp_nonce_field('wpa_add_comments', 'wpa_add_comments_nonce'); ?>

				<h3><?php _e('Drawing range', 'wpa-comments'); ?></h3>
				<p><?php _e('First, set the minimum and maximum range for drawing the random number of comments to be added.', 'wpa-comments'); ?></p>

				<select name="wpa-comments-min" id="wpa-comments-min">
					<option value="0">0</option>
					<option value="1">1</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
				</select>  	&mdash; 

				<select name="wpa-comments-max" id="wpa-comments-max">
					<option value="1">1</option>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
					<option value="25">25</option>
				</select>

				<hr><!-- End section -->
				<!-- ========================= -->

				<h3><?php _e('Add to all posts', 'wpa-comments'); ?></h3>
				<p><?php _e('Adds random number of comments (from the setted range) to all published posts.', 'wpa-comments'); ?></p>
				<div><?php submit_button(__('Add comments', 'wpa-comments'), 'primary', 'mode-1'); ?></div>

				<hr><!-- End section -->
				<!-- ========================= -->

				<h3><?php _e('Add to all posts, where', 'wpa-comments'); ?></h3>
				<p><?php _e('Adds random number of comments (from the setted range) to all published posts, where number of comments is ', 'wpa-comments'); ?>
				
				<select name="wpa-m2-condition" id="wpa-m2-condition">
					<option value="eq"><?php _e('equal to', 'wpa-comments'); ?></option>
					<option value="lt"><?php _e('less then', 'wpa-comments'); ?></option>
					<option value="gt"><?php _e('greater then', 'wpa-comments'); ?></option>	
				</select> 

				<select name="wpa-m2-param" id="wpa-m2-param">
					<option value="0">0</option>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
					<option value="25">25</option>
					<option value="30">30</option>
					<option value="35">35</option>
					<option value="40">40</option>
					<option value="45">45</option>
					<option value="50">50</option>
				</select>.</p>

				<div><?php submit_button(__('Add comments', 'wpa-comments'), 'primary', 'mode-2'); ?></div>


				<hr><!-- End section -->
				<!-- ========================= -->

				<h3><?php _e('Add to selected posts', 'wpa-comments'); ?></h3>
				<p><?php _e('Adds random number of comments (from the setted range) only to posts with ID:', 'wpa-comments'); ?></p>

				<p style="max-width:300px;"><input type="text" name="wpa-csv-list" style="width:100%;" ></p>
				<p class="description"><?php _e('Put coma separated list with posts IDs i.e.: 1,2,3. For the security reasons it only works with published posts.', 'wpa-comments'); ?></p>
				<div><?php submit_button(__('Add comments', 'wpa-comments'), 'primary', 'mode-3'); ?></div>



				<hr><!-- End section -->
				<!-- ========================= -->

				<h3><?php _e('Remove added comments', 'wpa-comments'); ?></h3>
				<p><?php _e('Removes all, added with this plugin comments.', 'wpa-comments'); ?></p>

				<div><?php submit_button(__('Remove comments', 'wpa-comments'), 'primary', 'mode-remove'); ?></div>

			</form>
		</div>
		<?php
	}
	
	// -------------------------------------------------------------------

	public function sanitize_input($options)
	{
		return $options;
	}

	public function view_users_form()
	{
		?>
		<div class="wrap">
			<h1><?php _e('WPA Comments - Manage comment authors', 'wpa-comments'); ?></h1>
			<p><?php _e('Add authors list (1 per line): ', 'wpa-comments'); ?></p>

			<?php settings_errors(); ?>

			<form id="template" action="options.php" method="post">

				<?php settings_fields('wpa_comments_authors'); ?>
				<?php $wpa_comments_authors = get_option('wpa_comments_authors'); ?>

				<div><textarea cols="70" rows="30" name="wpa_comments_authors"><?php echo esc_html($wpa_comments_authors); ?></textarea></div>
				<div><?php submit_button(); ?></div>
			</form>
		</div>
		<?php
	}

	// -------------------------------------------------------------------

	public function view_comments_form()
	{
		?>
		<div class="wrap">
			<h1><?php _e('WPA Comments - Manage comments', 'wpa-comments'); ?></h1>
			<p><?php _e('Add comments list (1 per line): ', 'wpa-comments'); ?></p>
			
			<?php settings_errors(); ?>

			<form id="template" action="options.php" method="post">

				<?php settings_fields('wpa_comments_comments'); ?>
				<?php $wpa_comments_comments = get_option('wpa_comments_comments'); ?>

				<div><textarea cols="70" style="white-space:pre;overflow:scoll-x" rows="30" name="wpa_comments_comments"><?php echo esc_html($wpa_comments_comments); ?></textarea></div>
				<div><?php submit_button(); ?></div>
			</form>
		</div>
		<?php
	}
}

new WPA_Comments;


/* Stuff to do when activated */
function activate_wpac()
{
	require_once( WPAC_DIR_PATH .'/inc/class-wpac-activate.php' );
	WPAC_Activate::activate();
}

register_activation_hook( __FILE__, 'activate_wpac' );