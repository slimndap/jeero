<?php
/**
 * @group images
 */

use Jeero\Helpers\Images;

class Images_Test extends Jeero_Test {

	/**
	 * Mock binary data keyed by a unique part of the URL.
	 *
	 * @var array
	 */
	private $image_bodies = array();

	protected function setUp(): void {

		parent::setUp();

		$this->image_bodies = array(
			'first-image.png' => base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==' ), // 1x1 red png
			'second-image.png' => base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGP4zwAAAgMBYkP4Gf4AAAAASUVORK5CYII=' ), // 1x1 green png
		);

		add_filter( 'pre_http_request', array( $this, 'mock_image_downloads' ), 10, 3 );
	}

	protected function tearDown(): void {

		remove_filter( 'pre_http_request', array( $this, 'mock_image_downloads' ), 10 );

		parent::tearDown();
	}

	/**
	 * Mimic remote image downloads by writing a tiny PNG into the requested filename.
	 *
	 * @param  mixed  $preempt
	 * @param  array  $args
	 * @param  string $url
	 * @return mixed
	 */
	public function mock_image_downloads( $preempt, $args, $url ) {

		foreach ( $this->image_bodies as $needle => $body ) {
			if ( strpos( $url, $needle ) !== false ) {
				if ( ! empty( $args['filename'] ) ) {
					file_put_contents( $args['filename'], $body );
				}

				return array(
					'headers'  => array(),
					'body'     => null,
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => $args['filename'] ?? null,
				);
			}
		}

		return $preempt;
	}

	public function test_same_ref_with_changed_url_downloads_new_image() {

		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Image Post',
				'post_name'  => 'image-post',
			)
		);

		$structured_image = array(
			'ref'      => 'shared-ref',
			'url'      => 'https://example.com/first-image.png',
			'basename' => 'first-image',
			'alt'      => 'First Image',
		);

		$first_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $first_id );

		$first_path = get_attached_file( $first_id );
		$this->assertFileExists( $first_path );
		$this->assertSame( $this->image_bodies['first-image.png'], file_get_contents( $first_path ) );

		$structured_image['url']      = 'https://example.com/second-image.png';
		$structured_image['basename'] = 'second-image';

		$second_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $second_id );

		$second_path = get_attached_file( $second_id );
		$this->assertFileExists( $second_path );

		// Expect the file linked to the returned attachment to match the new URL's body.
		$this->assertSame(
			$this->image_bodies['second-image.png'],
			file_get_contents( $second_path ),
			'The image should be refreshed when the URL changes even if the ref is the same.'
		);
	}

	public function test_legacy_attachment_without_source_url_is_replaced_on_url_change() {

		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Legacy Post',
				'post_name'  => 'legacy-post',
			)
		);

		$structured_image = array(
			'ref'      => 'legacy-ref',
			'url'      => 'https://example.com/first-image.png',
			'basename' => 'first-image',
			'alt'      => 'First Image',
		);

		$first_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $first_id );

		// Simulate a legacy attachment without stored source URL meta.
		delete_post_meta( $first_id, Images\JEERO_IMG_SOURCE_URL_FIELD );

		$structured_image['url']      = 'https://example.com/second-image.png';
		$structured_image['basename'] = 'second-image';

		$second_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $second_id );
		$this->assertNotSame( $first_id, $second_id );

		$this->assertNull( get_post( $first_id ), 'Old attachment should be removed after refresh.' );
		$this->assertSame(
			$this->image_bodies['second-image.png'],
			file_get_contents( get_attached_file( $second_id ) )
		);
		$this->assertSame(
			$structured_image['url'],
			get_post_meta( $second_id, Images\JEERO_IMG_SOURCE_URL_FIELD, true )
		);

		$attachments = get_posts(
			array(
				'post_type'  => 'attachment',
				'fields'     => 'ids',
				'meta_key'   => Images\JEERO_IMG_REF_FIELD,
				'meta_value' => 'legacy-ref',
			)
		);
		$this->assertSame( array( $second_id ), $attachments );
	}

	public function test_legacy_attachment_without_source_url_is_refreshed_once_when_url_matches() {

		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Legacy Same URL',
				'post_name'  => 'legacy-same-url',
			)
		);

		$structured_image = array(
			'ref'      => 'legacy-ref-same',
			'url'      => 'https://example.com/first-image.png',
			'basename' => 'first-image',
			'alt'      => 'First Image',
		);

		$attachment_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $attachment_id );

		// Remove stored URL meta to mimic pre-migration state.
		delete_post_meta( $attachment_id, Images\JEERO_IMG_SOURCE_URL_FIELD );

		// Same URL and ref should refresh once to capture source URL without leaving duplicates.
		$refreshed_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertNotSame( $attachment_id, $refreshed_id );

		$this->assertSame(
			$structured_image['url'],
			get_post_meta( $refreshed_id, Images\JEERO_IMG_SOURCE_URL_FIELD, true )
		);

		$attachments = get_posts(
			array(
				'post_type'  => 'attachment',
				'fields'     => 'ids',
				'meta_key'   => Images\JEERO_IMG_REF_FIELD,
				'meta_value' => 'legacy-ref-same',
			)
		);
		$this->assertSame( array( $refreshed_id ), $attachments );

		$this->assertNull( get_post( $attachment_id ), 'Old attachment should be cleaned up to avoid duplicates.' );

		$path = get_attached_file( $refreshed_id );
		$this->assertFileExists( $path );
		$this->assertSame( $this->image_bodies['first-image.png'], file_get_contents( $path ) );
	}

	/**
	 * Reproduces the fatal where wp_delete_attachment hits wp_get_object_terms() WP_Error for an unregistered taxonomy.
	 *
	 * Currently fails until deletion is hardened.
	 */
	public function test_delete_old_attachment_with_invalid_taxonomy_relationship_fatals() {

		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Invalid Taxonomy',
				'post_name'  => 'invalid-taxonomy',
			)
		);

		$structured_image = array(
			'ref'      => 'invalid-tax-ref',
			'url'      => 'https://example.com/first-image.png',
			'basename' => 'first-image',
			'alt'      => 'First Image',
		);

		$first_id = Images\add_structured_image_to_library( $structured_image, $post_id );
		$this->assertIsInt( $first_id );

		register_taxonomy(
			'ghost_taxonomy',
			'attachment',
			array(
				'public' => false,
			)
		);

		$error_filter = function ( $terms, $object_ids, $taxonomies, $args ) {
			if ( in_array( 'ghost_taxonomy', (array) $taxonomies, true ) ) {
				return new WP_Error( 'invalid_taxonomy', 'Taxonomy no longer registered' );
			}

			return $terms;
		};

		add_filter( 'get_object_terms', $error_filter, 10, 4 );

		try {
			$structured_image['url']      = 'https://example.com/second-image.png';
			$structured_image['basename'] = 'second-image';

			// This triggers deletion of the previous attachment, which currently fatals.
			Images\add_structured_image_to_library( $structured_image, $post_id );
		} finally {
			remove_filter( 'get_object_terms', $error_filter, 10 );
			unregister_taxonomy( 'ghost_taxonomy' );
		}
	}
}
