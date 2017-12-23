<?php
/*
Plugin Name: Favorite Posts
Plugin URI: https://wedevs.com/
Description: Favorite your posts
Version: 1.0
Author: Tareq Hasan
Author URI: https://tareq.co/
License: GPL2 or later
*/

/**
 * Copyright (c) 2016 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */
// don't call the file directly
if ( !defined( 'ABSPATH' ) )
    exit;

require_once dirname( __FILE__ ) . '/favorite-post-widget.php';

if ( ! class_exists( 'API' ) ) {
	require_once dirname( __FILE__ ) . '/API.php';
}


if ( ! class_exists( 'FavoritesController' ) ) {
	require_once dirname( __FILE__ ) . '/FavoritesController.php';
}
add_action( 'rest_api_init', 'create_initial_rest_routes_fav');
if ( ! function_exists( 'create_initial_rest_routes_fav' ) ) {
	function create_initial_rest_routes_fav() {
		$controller = new FavoritesController;
		$controller->register_routes();

	}
}


/**
 * WeDevs_Favorite_Posts class
 *
 * @class WeDevs_Favorite_Posts The class that holds the entire WeDevs_Favorite_Posts plugin
 */
class WeDevs_Favorite_Posts {

    /**
     * @var string table name
     */
    private $table;

    /**
     * @var object $wpdb object
     */
    private $db;

    private $api;

    /**
     * Constructor for the WeDevs_Favorite_Posts class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        global $wpdb;

        // setup table name
        $this->db = $wpdb;
        $this->api = new API;	
        $this->table = $this->db->prefix . 'favorite_post';

        register_activation_hook( __FILE__, array($this, 'activate') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );

        // Localize our plugin
        add_action( 'init', array($this, 'localization_setup') );

        // Loads frontend scripts and styles
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );

        // adds shortcode support favorite post button
        add_shortcode( 'favorite-post-btn', array($this, 'button_shortcode') );
        add_shortcode( 'favorite-post', array($this, 'display_shortcode') );

        // Ajax vote
        add_action( 'wp_ajax_wfp_action', array($this, 'favorite_post') );
    }

    /**
     * Initializes the WeDevs_Favorite_Posts() class
     *
     * Checks for an existing WeDevs_Favorite_Posts() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new WeDevs_Favorite_Posts();
        }

        return $instance;
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(11) unsigned NOT NULL DEFAULT '0',
          `post_id` int(11) unsigned NOT NULL DEFAULT '0',
          `post_type` varchar(20) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `post_id` (`post_id`),
          CONSTRAINT post_user_constraint UNIQUE (`user_id`,`post_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $this->db->query( $sql );
      $sql = "CREATE TABLE IF NOT EXISTS `wp_post_favorites` (
     `post_id` bigint(20) NOT NULL,
     `favorites` bigint(20) NOT NULL,
     PRIMARY KEY (`post_id`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	$this->db->query( $sql );
      $sql = "DROP TRIGGER IF EXISTS add_post_favorites ;";
	$this->db->query( $sql );
      $sql = "DROP TRIGGER IF EXISTS delete_post_favorites;";
	$this->db->query( $sql );
      $sql = "CREATE TRIGGER add_post_favorites AFTER INSERT ON `wp_favorite_post`
     FOR EACH ROW
     BEGIN
       INSERT INTO `wp_post_favorites` (`post_id`, `favorites`) VALUES(New.`post_id`,1) ON DUPLICATE KEY UPDATE `favorites`=`favorites`+1;
    END;";
	$this->db->query( $sql );
      $sql = "CREATE TRIGGER delete_post_favorites AFTER DELETE ON `wp_favorite_post`
     FOR EACH ROW
     BEGIN
       UPDATE `wp_post_favorites` set `favorites`=`favorites`- 1 WHERE `post_id` = OLD.`post_id`;
    END;";
	$this->db->query( $sql );
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {

    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'wfp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Enqueue admin scripts
     *
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     */
    public function enqueue_scripts() {

        /**
         * All styles goes here
         */
        wp_enqueue_style( 'wfp-styles', plugins_url( 'css/style.css', __FILE__ ), false, date( 'Ymd' ) );

        /**
         * All scripts goes here
         */
        wp_enqueue_script( 'wfp-scripts', plugins_url( 'js/script.js', __FILE__ ), array('jquery'), false, true );


        /**
         * Example for setting up text strings from Javascript files for localization
         *
         * Uncomment line below and replace with proper localization variables.
         */
        wp_localize_script( 'wfp-scripts', 'wfp', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wfp_nonce' ),
            'errorMessage' => __( 'Something went wrong', 'wfp' )
        ) );
    }

    /**
     * Ajax handler for inserting a vote
     *
     * @return void
     */
    function favorite_post() {
        check_ajax_referer( 'wfp_nonce', 'nonce' );

        // bail out if not logged in
        if ( !is_user_logged_in() ) {
            wp_send_json_error();
        }

        // so, the user is logged in huh? proceed on
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $user_id = get_current_user_id();

        if ( !$this->api->get_post_status( $post_id, $user_id ) ) {

            $this->api->insert_favorite( $post_id, $user_id );

            wp_send_json_success( '<span class="wpf-favorite">&nbsp;</span> ' . __( 'Remove from favorite', 'wfp' ) );
        } else {

            $this->api->delete_favorite( $post_id, $user_id );

            wp_send_json_success( '<span class="wpf-not-favorite">&nbsp;</span> ' . __( 'Add to favorite', 'wfp' ) );
        }
    }

    /**
     * Favorite post link button
     *
     * @param int $post_id
     * @return void
     */
    function link_button( $post_id ) {

        if ( !is_user_logged_in() ) {
            return;
        }

        $status = $this->api->get_post_status( $post_id, get_current_user_id() );
        ?>

        <a class="wpf-favorite-link" href="#" data-id="<?php echo $post_id; ?>">
            <?php if ( $status ) { ?>
                <span class="wpf-favorite">&nbsp;</span> <?php _e( 'Remove from favorite', 'wfp' ); ?>
            <?php } else { ?>
                <span class="wpf-not-favorite">&nbsp;</span> <?php _e( 'Add to favorite', 'wfp' ); ?>
            <?php } ?>
        </a>

        <?php
    }

    /**
     * Display favorite posts
     *
     * @param string $post_type
     * @param int $user_id
     * @param int $limit
     * @param bool $show_remove
     */
    function display_favorites( $post_type = 'all', $user_id = false, $limit = 10, $show_remove = true ) {

        $posts = $this->api->get_favorites( $post_type, $user_id, $limit );

        echo '<ul>';
        if ( $posts ) {

            $remove_title = __( 'Remove from favorite', 'wfp' );
            $remove_link = ' <a href="#" data-id="%s" title="%s" class="wpf-remove-favorite">x</a>';

            foreach ($posts as $item) {
                $extra = $show_remove ? sprintf( $remove_link, $item->post_id, $remove_title ) : '';
                printf( '<li><a href="%s">%s</a>%s</li>', get_permalink( $item->post_id ), get_the_title( $item->post_id ), $extra );
            }
        } else {
            printf( '<li>%s</li>', __( 'Nothing found', 'wfp' ) );
        }
        echo '</ul>';
    }

    /**
     * Shortcode for favorite link button
     *
     * @global object $post
     * @param array $atts
     * @return string
     */
    function button_shortcode( $atts ) {
        global $post;

        ob_start();
        $atts = extract( shortcode_atts( array('post_id' => 0), $atts ) );

        if ( !$post_id ) {
            $post_id = $post->ID;
        }

        $this->link_button( $post_id );

        return ob_get_clean();
    }

    /**
     * Shortcode for displaying posts
     *
     * @global object $post
     * @param array $atts
     * @return string
     */
    function display_shortcode( $atts ) {
        global $post;

        ob_start();
        $atts = extract( shortcode_atts( array('user_id' => 0, 'count' => 10, 'post_type' => 'all', 'remove_link' => false), $atts ) );

        if ( !$user_id ) {
            $user_id = get_current_user_id();
        }

        $this->display_favorites( $post_type, $user_id, $count, $remove_link );

        return $html;
    }

}

// WeDevs_Favorite_Posts

$favorite_post = WeDevs_Favorite_Posts::init();

/**
 * Wrapper function for favorite post button
 *
 * @global type $post
 * @param type $post_id
 */
function wfp_button( $post_id = null ) {
    global $post;

    if ( !$post_id ) {
        $post_id = $post->ID;
    }

    WeDevs_Favorite_Posts::init()->link_button( $post_id );
}