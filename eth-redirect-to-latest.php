<?php
/*
Plugin Name: ETH Redirect to Latest Post
Plugin URI: https://ethitter.com/plugins/
Description: Redirect a chosen slug to the whatever is currently the latest post
Author: Erick Hitter
Version: 0.1
Author URI: https://ethitter.com/
Text Domain: eth_redirect_to_latest_post
Domain Path: /languages/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ETH_Redirect_To_Latest_Post {
	/**
	 * PLUGIN SETUP
	 */

	/**
	 * Singleton
	 */
	private static $instance = null;

	/**
	 * Instantiate singleton
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Dummy magic methods
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; uh?' ), '0.1' ); }
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; uh?' ), '0.1' ); }
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/**
	 * Class properties
	 */
	private $name = 'ETH Redirect to Latest Post';
	private $slug = 'latest';

	/**
	 * Register plugin's setup action
	 */
	private function __construct() {
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
	}

	/**
	 * Redirect to the latest post any requests made to plugin's slug
	 */
	public function action_parse_request( $r ) {
		if ( isset( $r->query_vars['pagename'] ) && $this->slug === $r->query_vars['pagename'] ) {
			$latest = get_posts( array(
				'posts_per_page'   => 1,
				'post_type'        => 'post',
				'orderby'          => 'date',
				'order'            => 'desc',
				'suppress_filters' => false,
				'no_found_rows'    => true,
			) );

			if ( is_array( $latest ) && ! empty( $latest ) ) {
				$latest = array_shift( $latest );

				$dest = get_permalink( $latest->ID );

				if ( ! $dest ) {
					$dest = user_trailingslashit( home_url() );
				}

				wp_redirect( $dest, 302 ); // Not validating in case other plugins redirect elsewhere
				exit;
			}
		}
	}
}

/**
 * One instance to rule them all
 */
ETH_Redirect_To_Latest_Post::get_instance();
