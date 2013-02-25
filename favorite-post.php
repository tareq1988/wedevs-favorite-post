<?php
/*
Plugin Name: WeDevs Favorite Posts
Plugin URI: http://wedevs.com/
Description: Favorite your posts
Version: 0.1
Author: Tareq Hasan
Author URI: http://tareq.wedevs.com/
License: GPL2 or later
*/

/**
 * Copyright (c) 2013 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
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
if ( !defined( 'ABSPATH' ) ) exit;

require_once dirname( __FILE__ ) . '/favorite-post-widget.php';

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
        $this->table = $this->db->prefix . 'favorite_post';

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        // Loads frontend scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // adds shortcode support favorite post button
        add_shortcode( 'favorite-post-btn', array( $this, 'button_shortcode' ) );
        add_shortcode( 'favorite-post', array( $this, 'display_shortcode' ) );

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

        if ( ! $instance ) {
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
        $sql = "CREATE TABLE {$this->table} (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(11) unsigned NOT NULL DEFAULT '0',
          `post_id` int(11) unsigned NOT NULL DEFAULT '0',
          `post_type` varchar(20) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `post_id` (`post_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

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
        wp_enqueue_script( 'wfp-scripts', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), false, true );


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

        if ( !$this->get_post_status( $post_id, $user_id)) {

            $this->insert_favorite( $post_id, $user_id );

            wp_send_json_success( '<span class="wpf-favorite">&nbsp;</span> ' . __( 'Remove from favorite', 'wfp' ) );

        } else {

            $this->delete_favorite( $post_id, $user_id );

            wp_send_json_success( '<span class="wpf-not-favorite">&nbsp;</span> ' . __( 'Add to favorite', 'wfp' ) );
        }
    }

    /**
     * Gets a user vote for a post
     *
     * @param int $post_id
     * @param int $user_id
     * @return bool|object
     */
    function get_post_status( $post_id, $user_id ) {
        $sql = "SELECT post_id FROM {$this->table} WHERE post_id = %d AND user_id = %d";

        return $this->db->get_row( $this->db->prepare( $sql, $post_id, $user_id ));
    }

    /**
     * Insert a user vote
     *
     * @param int $post_id
     * @param int $user_id
     * @param int $vote
     * @return bool
     */
    public function insert_favorite( $post_id, $user_id ) {
        $post_type = get_post_field( 'post_type', $post_id );

        return $this->db->insert(
            $this->table,
            array(
                'post_id' => $post_id,
                'post_type' => $post_type,
                'user_id' => $user_id,
            ),
            array(
                '%d',
                '%s',
                '%d'
            )
        );
    }

    /**
     * Delete a user favorite
     *
     * @param int $post_id
     * @param int $user_id
     * @return bool
     */
    public function delete_favorite( $post_id, $user_id ) {
        $query = "DELETE FROM {$this->table} WHERE post_id = %d AND user_id = %d";

        return $this->db->query( $this->db->prepare( $query, $post_id, $user_id ) );
    }

    /**
     * Get favorite posts
     *
     * @param int $post_type
     * @param int $count
     * @param int $offset
     * @return array
     */
    function get_favorites( $post_type = 'all', $user_id = 0, $count = 10, $offset = 0 ) {
        $where = 'WHERE user_id = ';
        $where .= $user_id ? $user_id : get_current_user_id();
        $where .= $post_type == 'all' ? '' : " AND post_type = '$post_type'";


        $sql = "SELECT post_id, post_type
                FROM {$this->table}
                $where
                GROUP BY post_id
                ORDER BY post_type
                LIMIT $offset, $count";

        $result = $this->db->get_results( $sql );

        return $result;
    }

    function link_button( $post_id ) {

        if ( !is_user_logged_in() ) {
            return;
        }

        $status = $this->get_post_status( $post_id, get_current_user_id() );
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

    function display_favorites( $post_type = 'all', $user_id = false, $limit = 10, $show_remove = true) {

        $posts = $this->get_favorites( $post_type, $user_id, $limit );

        echo '<ul>';
        if ( $posts ) {

            $remove_title = __( 'Remove from favorite', 'wfp' );

            foreach ($posts as $item) {
                $extra = $show_remove ? sprintf( ' <a href="#" data-id="%s" title="%s" class="wpf-remove-favorite">x</a>', $item->post_id, $remove_title ) : '';
                printf( '<li><a href="%s">%s</a>%s</li>', get_permalink( $item->post_id ), get_the_title( $item->post_id ), $extra );
            }

        } else {
            printf( '<li>%s</li>', __( 'Nothing found', 'wfp' ) );
        }
        echo '</ul>';
    }

    function button_shortcode( $atts ) {
        global $post;

        ob_start();
        $atts = extract( shortcode_atts( array( 'post_id' => 0 ), $atts ) );

        if ( !$post_id ) {
            $post_id = $post->ID;
        }

        $this->link_button( $post_id );

        return ob_get_clean();
    }

    function display_shortcode( $atts ) {
        global $post;

        ob_start();
        $atts = extract( shortcode_atts( array( 'user_id' => 0, 'count' => 10, 'post_type' => 'all' ), $atts ) );

        if ( !$user_id ) {
            $user_id = get_current_user_id();
        }

        $posts = $this->get_favorites( $post_type, $user_id, $count );

        $html = '<ul>';
        if ( $posts ) {
            foreach ($posts as $item) {
                $html .= sprintf( '<li><a href="%s">%s</a></li>', get_permalink( $item->post_id ), get_the_title( $item->post_id ) );
            }
        } else {
            $html .= sprintf( '<li>%s</li>', __( 'Nothing found', 'wfp' ) );
        }
        $html .= '</ul>';

        return $html;
    }

} // WeDevs_Favorite_Posts

$favorite_post = WeDevs_Favorite_Posts::init();

function wfp_button( $post_id = null ) {
    global $post;

    if ( !$post_id ) {
        $post_id = $post->ID;
    }

    WeDevs_Favorite_Posts::init()->link_button( $post_id );
}