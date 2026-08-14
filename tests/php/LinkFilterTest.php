<?php
/**
 * Tests for ucscgiving_link_filter().
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the post_type_link filter that points Standard funds at the
 * external giving site and leaves everything else alone.
 */
class LinkFilterTest extends TestCase {

	/**
	 * The permalink handed to the filter.
	 *
	 * @var string
	 */
	private $permalink = 'https://example.test/fund/some-fund/';

	/**
	 * Reset the WordPress stubs before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		UCSCGiving_Test_State::reset();
	}

	/**
	 * Build a post object.
	 *
	 * The filter only reads ->ID and ->post_type and has no type hint, so a
	 * plain object is enough; no WP_Post stand-in is needed.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type.
	 * @return object
	 */
	private function make_post( $post_id, $post_type ) {
		return (object) array(
			'ID'        => $post_id,
			'post_type' => $post_type,
		);
	}

	/**
	 * Build a fund post and assign it a fund-type term.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $term_name Fund type term name.
	 * @return object
	 */
	private function make_fund( $post_id = 7, $term_name = 'Standard' ) {
		UCSCGiving_Test_State::$object_terms[ $post_id . ':fund-type' ] = array( $term_name );

		return $this->make_post( $post_id, 'fund' );
	}

	/**
	 * Anything that is not a fund is passed straight through.
	 *
	 * @return void
	 */
	public function test_leaves_non_fund_post_types_untouched() {
		$post = $this->make_post( 7, 'post' );

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * A fund with no fund-type term keeps its own permalink.
	 *
	 * @return void
	 */
	public function test_returns_permalink_when_fund_type_term_is_missing() {
		$post = $this->make_post( 7, 'fund' );

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * A fund-type term from a different post must not leak across.
	 *
	 * The filter asks about a specific post, so assigning the term to some
	 * other post changes nothing here.
	 *
	 * @return void
	 */
	public function test_returns_permalink_when_the_term_belongs_to_another_post() {
		UCSCGiving_Test_State::$fields['base_url']          = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation']       = 'ABC123';
		UCSCGiving_Test_State::$object_terms['8:fund-type'] = array( 'Standard' );

		$post = $this->make_post( 7, 'fund' );

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * A "Standard" term in some other taxonomy is not a fund type.
	 *
	 * @return void
	 */
	public function test_returns_permalink_when_standard_is_in_another_taxonomy() {
		UCSCGiving_Test_State::$fields['base_url']     = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation']  = 'ABC123';
		UCSCGiving_Test_State::$object_terms['7:area'] = array( 'Standard' );

		$post = $this->make_post( 7, 'fund' );

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * Documents the risk the taxonomy panel introduces.
	 *
	 * The ACF radio this replaced could hold one value. WordPress renders a
	 * hierarchical taxonomy as a checkbox list, so an editor can now tick
	 * Standard *and* Priority, and the fund links out. Nothing in the data
	 * model prevents it; this pins what happens when it occurs rather than
	 * claiming it is desirable.
	 *
	 * @return void
	 */
	public function test_links_out_when_standard_is_one_of_several_terms() {
		UCSCGiving_Test_State::$fields['base_url']          = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation']       = 'ABC123';
		UCSCGiving_Test_State::$object_terms['7:fund-type'] = array( 'Priority', 'Standard' );

		$post = $this->make_post( 7, 'fund' );

		$this->assertSame(
			'https://give.example.edu/fund/ABC123',
			ucscgiving_link_filter( $this->permalink, $post )
		);
	}

	/**
	 * Non-Standard funds ("Priority") render their own single template.
	 *
	 * @return void
	 */
	public function test_returns_permalink_for_non_standard_fund_type() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';

		$post = $this->make_fund( 7, 'Priority' );

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * Without base_url the permalink is left alone rather than truncated.
	 *
	 * @return void
	 */
	public function test_returns_permalink_when_base_url_is_missing() {
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';

		$post = $this->make_fund();

		$this->assertSame( $this->permalink, ucscgiving_link_filter( $this->permalink, $post ) );
	}

	/**
	 * The happy path: a Standard fund links out to the giving site.
	 *
	 * @return void
	 */
	public function test_returns_external_url_for_standard_fund() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';

		$post = $this->make_fund();

		$this->assertSame(
			'https://give.example.edu/fund/ABC123',
			ucscgiving_link_filter( $this->permalink, $post )
		);
	}

	/**
	 * Documents current behaviour: a Standard fund with no designation code
	 * still links out, to the bare base URL.
	 *
	 * @return void
	 */
	public function test_returns_bare_base_url_when_designation_is_missing() {
		UCSCGiving_Test_State::$fields['base_url'] = 'https://give.example.edu/fund/';

		$post = $this->make_fund();

		$this->assertSame(
			'https://give.example.edu/fund/',
			ucscgiving_link_filter( $this->permalink, $post )
		);
	}

	/**
	 * The filter reads its own $post argument rather than loop state, so a
	 * different current post must not change the result. This is the
	 * regression guard for the permalink fix in #121.
	 *
	 * @return void
	 */
	public function test_reads_the_post_argument_not_loop_state() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';
		UCSCGiving_Test_State::$meta['8:designation'] = 'WRONG';
		UCSCGiving_Test_State::$current_post_id       = 8;

		$post = $this->make_fund( 7 );

		$this->assertSame(
			'https://give.example.edu/fund/ABC123',
			ucscgiving_link_filter( $this->permalink, $post )
		);
	}
}
