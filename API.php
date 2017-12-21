<?php

class API {

	/**
	* @var string table name
	*/
	private $table;

	/**
	* @var object $wpdb object
	*/
	private $db;

	public function __construct() {
		global $wpdb;

		// setup table name
		$this->db = $wpdb;
		$this->table = $this->db->prefix . 'favorite_post';
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

		return $this->db->get_row( $this->db->prepare( $sql, $post_id, $user_id ) );
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

}

