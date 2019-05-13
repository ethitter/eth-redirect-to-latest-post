<?php
/**
 * Plugin functionality.
 *
 * @package ETH_Redirect_To_Latest_Post
 */

/**
 * Class ETH_Redirect_To_Latest_Post.
 */
class ETH_Redirect_To_Latest_Post {
	/**
	 * PLUGIN SETUP
	 */

	/**
	 * Singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Instantiate singleton.
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Dummy magic method.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; uh?', 'eth_redirect_to_latest_post' ), '0.1' );
	}

	/**
	 * Dummy magic method.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; uh?', 'eth_redirect_to_latest_post' ), '0.1' );
	}

	/**
	 * Dummy magic method.
	 *
	 * @param string $name Method name.
	 * @param array  $args Method arguments.
	 * @return null
	 */
	public function __call( $name = '', $args = array() ) {
		return null;
	}

	/**
	 * Plugin's option name.
	 *
	 * @var string
	 */
	private $plugin_option_name = 'eth-redirect-to-latest';

	/**
	 * Plugin's slug.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Plugin's fallback slug.
	 *
	 * @var string
	 */
	private $default_slug = '';

	/**
	 * Register plugin's setup action.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
	}

	/**
	 * Translate plugin slug.
	 */
	public function action_init() {
		$this->default_slug = __( 'latest', 'eth_redirect_to_latest_post' );

		$_slug = get_option( $this->plugin_option_name, $this->default_slug );

		if ( is_string( $_slug ) && ! empty( $_slug ) ) {
			$this->slug = $_slug;
		}

		if ( empty( $this->slug ) ) {
			$this->slug = $this->default_slug;
		}
	}

	/**
	 * Redirect to the latest post any requests made to plugin's slug.
	 *
	 * @param WP $r WP object.
	 */
	public function action_parse_request( $r ) {
		// Nothing to do if permalinks aren't enabled.
		if ( ! $r->did_permalink ) {
			return;
		}

		$redirect = $this->get_redirect_for_request( $r );

		if ( null === $redirect ) {
			return;
		}

		header( 'x-redirect: eth-redirect-to-latest-post' );

		// Not validating in case other plugins redirect elsewhere.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect( $redirect->destination, $redirect->status_code );
		exit;
	}

	/**
	 * Parse the request to determine its redirect, if any.
	 *
	 * @param WP $r WP object.
	 * @return \stdClass|null
	 */
	public function get_redirect_for_request( $r ) {
		/**
		 * Check if request is for our slug.
		 *
		 * The first condition catches hierarchical permastructs, while
		 * the second catches non-hierarchical permastructs.
		 */
		if ( isset( $r->query_vars['pagename'] ) && $this->slug === $r->query_vars['pagename'] ) {
			$should_intercept = true;
		} elseif ( isset( $r->query_vars['name'] ) && $this->slug === $r->query_vars['name'] ) {
			$should_intercept = true;
		} else {
			$should_intercept = false;
		}

		if ( ! $should_intercept ) {
			return null;
		}

		$redirect = array(
			'destination' => '',
			'status_code' => 302,
		);

		$query_args = array(
			'posts_per_page'   => 1,
			'post_type'        => 'post',
			'orderby'          => 'date',
			'order'            => 'desc',
			'suppress_filters' => false,
			'no_found_rows'    => true,
		);

		/**
		 * Filters the query arguments that determine
		 * the latest post.
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param WP    $r          WP Object.
		 * @return array
		 */
		$query_args = apply_filters( 'eth_redirect_to_latest_post_query_args', $query_args, $r );

		$latest = get_posts( $query_args );
		if ( is_array( $latest ) && ! empty( $latest ) ) {
			$latest                  = array_shift( $latest );
			$redirect['destination'] = get_permalink( $latest->ID );
		}

		if ( empty( $redirect['destination'] ) ) {
			$redirect['destination'] = user_trailingslashit( home_url() );
		}

		/**
		 * Filters the redirection data.
		 *
		 * @param array         $redirect Array of redirect destination and status code.
		 * @param array|WP_Post $latest   Post object or empty array if no posts found.
		 * @param WP            $r        WP object.
		 * @return string
		 */
		$redirect = apply_filters( 'eth_redirect_to_latest_post_redirection', $redirect, $latest, $r );

		return (object) $redirect;
	}

	/**
	 * ADMIN OPTIONS
	 */

	/**
	 * Save plugin settings and register settings field
	 *
	 * Permalinks screen is a snowflake, hence the custom saving handler.
	 */
	public function action_admin_init() {
		// Make sure user has necessary permissions first.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save custom option, permalinks screen is a snowflake and doesn't fully use the Settings API.
		global $pagenow;

		if ( 'options-permalink.php' === $pagenow && isset( $_POST[ $this->plugin_option_name ] ) ) {
			check_admin_referer( 'update-permalink' );

			$_slug = sanitize_text_field( $_POST[ $this->plugin_option_name ] );

			if ( empty( $_slug ) ) {
				$_slug = $this->default_slug;
			}

			update_option( $this->plugin_option_name, $_slug );
		}

		// Add custom input field to permalinks screen.
		add_settings_field( $this->plugin_option_name, __( '&quot;Latest post&quot; slug', 'eth_redirect_to_latest_post' ), array( $this, 'settings_field' ), 'permalink', 'optional' );
	}

	/**
	 * Render settings field.
	 */
	public function settings_field() {
		?>
		<input type="text" name="<?php echo esc_attr( $this->plugin_option_name ); ?>" value="<?php echo esc_attr( $this->slug ); ?>" class="regular-text" />

		<p class="description">
			<?php
			printf(
				/* translators: 1. Default slug, wrapped in a <code> tag. */
				esc_html__(
					'Set the slug that will redirect to the latest published post. The default value is %s.',
					'eth_redirect_to_latest_post'
				),
				'<code style="font-style: normal;">' . esc_html( $this->default_slug ) . '</code>'
			);
			?>
		</p>
		<?php
	}
}

/**
 * One instance to rule them all.
 */
ETH_Redirect_To_Latest_Post::get_instance();
