<?php
/**
 * Plugin Name: Delete User - Extra Output
 * Plugin URI:  http://plugish.com
 * Description: Designed on a whim, to provide extra output for users when deleting them.
 * Version:     0.1.0
 * Author:      Jay Wood
 * Author URI:  http://plugish.com
 * License:     GPLv2+
 */

/**
 * Copyright (c) 2015 Jay Wood (email : jjwood2004@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

class Delete_Users_Extra_Output{

	public function hooks() {
		add_action( 'delete_user_form', array( $this, 'add_output' ) );
	}

	public function add_output( $current_user ) {

		if( empty( $current_user ) || ! isset( $current_user->ID ) ){
			return false;
		}

		$post_types = get_post_types( '', 'objects' );
		error_log( print_r( $post_types, 1 ) );
		if( empty( $post_types ) ){
			// really? Not like it'll happen, but just in case.
			return false;
		}

		$output  = "<h4>Content that will be affected:</h4>";
		$output .= "<ul>";
		$tmp_arr = array();
		foreach ( $post_types as $type_key => $type_settings ) {
			$name = isset( $type_settings->labels ) && isset( $type_settings->labels->name ) ? $type_settings->labels->name : false;
			if( isset( $_GET['users'] ) ){
				foreach( $_GET['users'] as $userid ){
					$userid = absint( $userid );
					$post_count = 0;
					if( 'attachment' == $type_key ){
						$post_count = $this->get_attachment_count( $userid );
					} else {
						$post_count = $this->count_user_posts_by_type( $userid, $type_key );
					}
					$previous = isset( $tmp_arr[ $name ] ) ? $tmp_arr[ $name ] : 0;
					$tmp_arr[ $name ] = $previous + $post_count;
				}
			} else if( isset( $_GET['user'] ) ){
				$userid = absint( $_GET['user'] );
				$post_count = 0;
				if( 'attachment' == $type_key ){
					$post_count = $this->get_attachment_count( $userid );
				} else {
					$post_count = $this->count_user_posts_by_type( $userid, $type_key );
				}
				$previous = isset( $tmp_arr[ $name ] ) ? $tmp_arr[ $name ] : 0;
				$tmp_arr[ $name ] = $previous + $post_count;
			}

		}

		foreach( $tmp_arr as $post_type => $post_count ){
			if( empty( $post_count ) ){
				unset( $tmp_arr{ $post_type } );
			}
		}

		if ( ! empty( $tmp_arr ) ){
			foreach( $tmp_arr as $post_type => $post_count ){
				$output .= "<li>$post_type: $post_count</li>";
			}
		} else {
			$output .= "<li>No content will be affected this operation.</li>";
		}

		$output .= "</ul>";
		echo $output;
		return true;
	}

	public function count_user_posts_by_type( $userid, $post_type = 'post' ) {
		global $wpdb;

		$where = get_posts_by_author_sql( $post_type, true, $userid );

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

		return $count;
	}

	public function get_attachment_count( $userid = 0 ) {
		if( empty( $userid ) ){
			return 0;
		}
		global $wpdb;

		$where = $wpdb->prepare( 'WHERE post_type = %s AND post_author = %d AND post_status = %s', 'attachment', $userid, 'inherit' );
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where");
		return $count;

	}

}

$GLOBALS['Delete_Users_Extra_Output'] = new Delete_Users_Extra_Output();
$GLOBALS['Delete_Users_Extra_Output']->hooks();