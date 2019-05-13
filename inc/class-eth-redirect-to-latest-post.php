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

		// By default, there's also nothing to do.
		$should_intercept = false;

		/**
		 * Check if request is for our slug.
		 *
		 * The first condition catches permastructs that are more than just
		 * post slug, whereas the second catches for slug-only permalinks.
		 */
		if ( isset( $r->query_vars['pagename'] ) && $this->slug === $r->query_vars['pagename'] ) {
			$should_intercept = true;
		} elseif ( isset( $r->query_vars['name'] ) && $this->slug === $r->query_vars['name'] ) {
			$should_intercept = true;
		}

		// Handle redirection.
		if ( $should_intercept ) {
			$latest = get_posts(
				array(
					'posts_per_page'   => 1,
					'post_type'        => 'post',
					'orderby'          => 'date',
					'order'            => 'desc',
					'suppress_filters' => false,
					'no_found_rows'    => true,
				)
			);

			if ( is_array( $latest ) && ! empty( $latest ) ) {
				$latest = array_shift( $latest );

				$dest = get_permalink( $latest->ID );

				if ( ! $dest ) {
					$dest = user_trailingslashit( home_url() );
				}

				// Not validating in case other plugins redirect elsewhere.
				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				wp_redirect( $dest, 302 );
				exit;
			}
		}
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
