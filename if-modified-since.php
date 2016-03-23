<?php
/**
 * Plugin Name: If Modified Since
 * Plugin URI:  http://wordpress.org/plugins/if-modified-since/
 * Description: Lightweight plugin to handle the `If Modifed Since` functionality.
 * Version:     0.9
 * Author:      Ezra Verheijen
 * Author URI:  http://profiles.wordpress.org/ezraverheijen/
 * License:     GPL v3
 * 
 * @link https://www.feedthebot.com/ifmodified.html
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have recieved a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses>.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class If_Modified_Since {

	public function __construct() {
        add_action( 'template_redirect', array( $this, 'respond_if_modified_since' ) );
	}

    public function respond_if_modified_since() {
        if ( ! $mtime = $this->get_mtime() ) {
            return;
        }

        if ( ! $this->is_valid_unix_timestamp( $mtime ) ) {
            return;
        }

        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );

        if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) &&
            strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) >= $mtime )
        {
            header( $this->get_http_protocol() . ' 304 Not Modified' );
            exit;
        }
    }

    public function get_mtime() {
        global $wp_query;

        $mtime = null;

        if ( $wp_query->is_home() ) {
            $mtime = $this->get_archive_mtime( 'post_type', 'post' );
        } elseif ( $wp_query->is_single() || $wp_query->is_page() ) {
            if ( $id = $wp_query->get_queried_object_id() ) {
                $mtime = $this->get_post_mtime( $id );
            }
        } elseif ( $wp_query->is_category() ) {
            if ( $id = $wp_query->get_queried_object_id() ) {
                $mtime = $this->get_archive_mtime( 'cat', $id );
            }
        } elseif ( $wp_query->is_tag() ) {
            if ( $id = $wp_query->get_queried_object_id() ) {
                $mtime = $this->get_archive_mtime( 'tag_id', $id );
            }
        } elseif ( $wp_query->is_tax() ) {
            if ( $id = $wp_query->get_queried_object_id() ) {
                $mtime = $this->get_archive_mtime( 'tax_id', $id );
            }
        } elseif ( $wp_query->is_author() ) {
            if ( $id = $wp_query->get_queried_object_id() ) {
                $mtime = $this->get_archive_mtime( 'author', $id );
            }
        } elseif ( $wp_query->is_year() ) {
            $year  = $wp_query->get( 'm' ) ? $wp_query->get( 'm' ) : $wp_query->get( 'year' );
            $mtime = $this->get_archive_mtime( 'year', $year );
        } elseif ( $wp_query->is_month() ) {
            $month = $wp_query->get( 'm' ) ? mb_substr( $wp_query->get( 'm' ), 4, 2 ) : $wp_query->get( 'monthnum' );
            $mtime = $this->get_archive_mtime( 'monthnum', $month );
        } elseif ( $wp_query->is_day() ) {
            $day   = $wp_query->get( 'm' ) ? mb_substr( $wp_query->get( 'm' ), 6, 2 ) : $wp_query->get( 'day' );
            $mtime = $this->get_archive_mtime( 'day', $day );
        } elseif ( $wp_query->is_post_type_archive() ) {
            $mtime = $this->get_archive_mtime( 'post_type', $wp_query->get( 'post_type' ) );
        }

        return apply_filters( 'if_modified_since', $mtime );
    }

	public function get_archive_mtime( $query, $object_id ) {
		$mtime = null;

		$query = new WP_Query( $query . '=' . $object_id . '&posts_per_page=1&no_found_rows=true' );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$mtime = $this->get_post_mtime( get_the_ID() );
			}
		}

		wp_reset_postdata();

		return $mtime;
	}

	public function get_post_mtime( $post_id ) {
		return get_post_modified_time( 'U', false, $post_id );
	}

    public function is_valid_unix_timestamp( $timestamp ) {
        return ( is_int( $timestamp ) &&
                $timestamp === strtotime( date( 'd-m-Y H:i:s', $timestamp ) ) );
    }

    public function get_http_protocol() {
        return ( ! empty( $_SERVER['SERVER_PROTOCOL'] ) ) ? sanitize_text_field( $_SERVER['SERVER_PROTOCOL'] ) : 'HTTP/1.1';
    }

}

$if_modified_since = new If_Modified_Since();
