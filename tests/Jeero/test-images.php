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
}
