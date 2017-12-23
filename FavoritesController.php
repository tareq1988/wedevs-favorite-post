<?php

class FavoritesController extends WP_REST_Controller {

	private $api;
	public function __construct() {
		$this->api = new API;
		$this->namespace = 'favorites/v1';
		$this->rest_base = 'post';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => array(
					'favorite'    => array(
						'required' => false,
					),
				),

			),
		) );
		register_rest_field(
			'post',
			'favoraited',
			array(
				'get_callback'    => array( $this, 'add_favoraite_to_post_resource'),
			) );
	}

	/**
	 * Check if a given request has access to update a post.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		if ( !is_user_logged_in() ) {
			return new WP_Error( 'rest_cannot_edit', 'you need to log in' , array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Update a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$post_id = (int) $request['id'];
		$post = get_post( (int) $post_id );
		$favorite = $request['favorite'];
		if( $favorite=='true' )
			$favorited = 1;
		else
			$favorited = 0;

		if ( empty( $post_id ) || empty( $post->ID ) ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Post id is invalid.' ), array( 'status' => 400 ) );
		}

		$user_id = get_current_user_id();

		if( empty( $favorite ) ){
			$status = $this->api->get_post_status( $post_id, $user_id );
			if ( !$status ) {
				$this->api->insert_favorite( $post_id, $user_id );
			} else {
				 $this->api->delete_favorite( $post_id, $user_id );
			}
		} else {

			if ( $favorited ) {
				$this->api->insert_favorite( $post_id, $user_id );
			} else {
				 $this->api->delete_favorite( $post_id, $user_id );
			}			
		}
		if( empty( $favorite ) ){
			$data = array(

				'id'                 => $post_id,

				'favorite'           => !$status );
		} else {
			$status = $this->api->get_post_status( $post_id, $user_id );
			$data = array(

				'id'                 => $post_id,

				'favorite'           => !empty( $status) );		
		}

		return rest_ensure_response( $data );
	}

	function add_favoraite_to_post_resource( $object, $field_name, $request ) {
		return $this->api->get_favorites_count_for_post($object['id']);
	}

}
