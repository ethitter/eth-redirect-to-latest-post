<?php
/**
 * Class PluginTest.
 *
 * @package ETH_Redirect_To_Latest_Post
 */

/**
 * Plugin test case.
 */
class PluginTest extends WP_UnitTestCase {
	/**
	 * ID of latest post.
	 *
	 * @var int
	 */
	protected static $latest_id;

	/**
	 * Create some objects with various dates, so something
	 * can be considered "latest."
	 *
	 * We create an assortment of posts and pages with the
	 * slug used for redirection so that we can confirm that
	 * redirection takes priority.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory->post->create_many(
			10,
			array(
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-1 minute' ) ),
				'post_name' => 'latest',
			)
		);

		$this->factory->post->create_many(
			10,
			array(
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
				'post_name' => 'latest',
			)
		);

		$this->factory->post->create_many(
			10,
			array(
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-1 minute' ) ),
				'post_type' => 'page',
				'post_name' => 'latest',
			)
		);

		$this->factory->post->create_many(
			10,
			array(
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
				'post_type' => 'page',
				'post_name' => 'latest',
			)
		);

		static::$latest_id = $this->factory->post->create();
	}

	/**
	 * Test function that retrieves latest post when
	 * requested as a non-hierarchical permastruct.
	 */
	public function test_get_latest_non_hierarchical() {
		$fake_request = new stdClass();
		$fake_request->query_vars = array(
			'name' => 'latest',
		);

		$this->assert_for_request( $fake_request );
	}

	/**
	 * Test function that retrieves latest post when
	 * requested as a non-hierarchical permastruct.
	 */
	public function test_get_latest_hierarchical() {
		$fake_request = new stdClass();
		$fake_request->query_vars = array(
			'pagename' => 'latest',
		);

		$this->assert_for_request( $fake_request );
	}

	/**
	 * Helper for shared assertions.
	 *
	 * @param stdClass $fake_request Faux WP object.
	 */
	protected function assert_for_request( $fake_request ) {
		$redirect = ETH_Redirect_To_Latest_Post::get_instance()->get_redirect_for_request( $fake_request );

		$this->assertEquals(
			get_permalink( static::$latest_id ),
			$redirect->destination,
			'Failed to assert that redirect destination is permalink of latest post.'
		);

		$this->assertEquals(
			302,
			$redirect->status_code,
			'Failed to assert that redirect status code is for a temporary redirect.'
		);
	}

	/**
	 * Test that redirection doesn't return a post with
	 * the slug that's also used for redirection.
	 */
	public function test_not_matching_post_name() {
		$fake_request = new stdClass();
		$fake_request->query_vars = array(
			'name' => 'latest',
		);

		$redirect = ETH_Redirect_To_Latest_Post::get_instance()->get_redirect_for_request( $fake_request );

		$object_by_slug = get_posts(
			array(
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'name'           => $fake_request->query_vars['name'],
			)
		);

		$this->assertCount(
			1,
			$object_by_slug,
			'Failed to assert that a post exists with the slug used for redirection.'
		);

		$object_by_slug = array_shift( $object_by_slug );

		$this->assertNotEquals(
			get_permalink( $object_by_slug ),
			$redirect->destination,
			'Failed to assert that latest post is not the object with the slug "latest."'
		);
	}

	/**
	 * Test that redirection doesn't return a post with
	 * the slug that's also used for redirection.
	 */
	public function test_not_matching_page_name() {
		$fake_request = new stdClass();
		$fake_request->query_vars = array(
			'pagename' => 'latest',
		);

		$redirect = ETH_Redirect_To_Latest_Post::get_instance()->get_redirect_for_request( $fake_request );

		$object_by_slug = get_posts(
			array(
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'post_type'      => 'page',
				'pagename'       => $fake_request->query_vars['pagename'],
			)
		);

		$this->assertCount(
			1,
			$object_by_slug,
			'Failed to assert that a page exists with the slug used for redirection.'
		);

		$object_by_slug = array_shift( $object_by_slug );

		$this->assertNotEquals(
			get_permalink( $object_by_slug ),
			$redirect->destination,
			'Failed to assert that latest post is not the object with the slug "latest."'
		);
	}
}
