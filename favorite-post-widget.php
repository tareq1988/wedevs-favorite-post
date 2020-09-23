<?php

/**
 * New WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class WeDevs_Favorite_Post_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     * */
    function __construct() {
        $widget_ops = array('classname' => 'wedevs-favorite-post' );
        parent::__construct( 'wedevs-favorite-post', __( 'Favorite post', 'wfp' ), $widget_ops );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array $args An array of standard parameters for widgets in this theme
     * @param array $instance An array of settings for this widget instance
     * @return void Echoes it's output
     * */
    function widget( $args, $instance ) {
        extract( $args, EXTR_SKIP );
        echo $before_widget;

        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        if(isset($instance['remove_link']) && $instance['remove_link'] == 'on'){
            $show_remove = true;
        }
        else $show_remove = false;
        WeDevs_Favorite_Posts::init()->display_favorites( $instance['post_type'], false, $instance['limit'], $show_remove );

        echo $after_widget;
    }

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array $new_instance An array of new settings as submitted by the admin
     * @param array $old_instance An array of the previous settings
     * @return array The validated and (if necessary) amended settings
     * */
    function update( $new_instance, $old_instance ) {
        // update logic goes here
        $updated_instance = $new_instance;
        return $updated_instance;
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array $instance An array of the current settings for this widget
     * @return void Echoes it's output
     * */
    function form( $instance ) {
        $defaults = array(
            'title' => __( 'Favorite Posts', 'wfp' ),
            'post_type' => 'all',
            'limit' => 10,
            'remove_link' => 'off'
        );

        $instance = wp_parse_args( (array) $instance, $defaults );
        $title = esc_attr( $instance['title'] );
        $post_type = esc_attr( $instance['post_type'] );
        $limit = esc_attr( $instance['limit'] );
        $remove_link = $instance['remove_link'] == 'on' ? 'on' : 'off';

        $post_types = get_post_types( array( 'public' => true ) );
        ?>
        <p>
            <label><?php _e( 'Title:', 'wfp' ); ?> </label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
        </p>

        <p>
            <label><?php _e( 'Post Type:', 'wfp' ) ?></label>
            <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" >
                <option value="all" <?php selected( $post_type, 'all' ) ?>><?php _e( 'All', 'wfp' ) ?></option>

                <?php foreach ($post_types as $pt) { ?>
                    <option value="<?php echo $pt; ?>" <?php selected( $post_type, $pt ) ?>><?php echo $pt; ?></option>
                <?php } ?>
            </select>
        </p>

        <p>
            <label><?php _e( 'Limit:', 'wedevs' ); ?> </label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>" />
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( $remove_link, 'on' ); ?> id="<?php echo $this->get_field_id( 'remove_link' ); ?>" name="<?php echo $this->get_field_name( 'remove_link' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'remove_link' ); ?>"><?php _e( 'Show remove link', 'wfp' ) ?></label>
        </p>
        <?php
    }

}

add_action( 'widgets_init', function (){register_widget('WeDevs_Favorite_Post_Widget');} );

