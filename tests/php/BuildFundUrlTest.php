<?php
/**
 * Tests for ucscgiving_build_fund_url().
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the single helper that composes the external Giving URL, and the
 * guarantee that both callers agree on the result.
 */
class BuildFundUrlTest extends TestCase {

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
	 * The happy path.
	 *
	 * @return void
	 */
	public function test_composes_base_url_and_designation() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';

		$this->assertSame( 'https://give.example.edu/fund/ABC123', ucscgiving_build_fund_url( 7 ) );
	}

	/**
	 * No base URL means no URL at all. Callers decide what to do with that.
	 *
	 * @return void
	 */
	public function test_returns_empty_string_without_base_url() {
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';

		$this->assertSame( '', ucscgiving_build_fund_url( 7 ) );
	}

	/**
	 * An absent designation leaves the base URL on its own.
	 *
	 * @return void
	 */
	public function test_returns_base_url_when_designation_is_empty() {
		UCSCGiving_Test_State::$fields['base_url'] = 'https://give.example.edu/fund/';

		$this->assertSame( 'https://give.example.edu/fund/', ucscgiving_build_fund_url( 7 ) );
	}

	/**
	 * A designation of "0" is a real code and must survive.
	 *
	 * This pins the one behaviour that changed when the two implementations
	 * were merged. `ucscgiving_fund_url()` previously guarded with
	 * `! empty( $designation )`, so a "0" code was silently dropped and the
	 * bare base URL returned; `ucscgiving_link_filter()` concatenated and
	 * kept it. The helper now concatenates, so both agree on the filter's
	 * behaviour, which is the correct one.
	 *
	 * @return void
	 */
	public function test_appends_a_zero_designation() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = '0';

		$this->assertSame( 'https://give.example.edu/fund/0', ucscgiving_build_fund_url( 7 ) );
	}

	/**
	 * The helper reads the post ID it is handed, not loop state.
	 *
	 * @return void
	 */
	public function test_reads_the_post_id_it_is_given() {
		UCSCGiving_Test_State::$fields['base_url']    = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$meta['7:designation'] = 'ABC123';
		UCSCGiving_Test_State::$meta['8:designation'] = 'WRONG';
		UCSCGiving_Test_State::$current_post_id       = 8;

		$this->assertSame( 'https://give.example.edu/fund/ABC123', ucscgiving_build_fund_url( 7 ) );
	}

	/**
	 * The drift guard this refactor exists for.
	 *
	 * The block binding and the permalink filter must produce the same URL
	 * for the same fund. Before the helper they computed it independently,
	 * and a change to one could silently leave the other behind.
	 *
	 * @return void
	 */
	public function test_binding_and_permalink_agree_for_a_standard_fund() {
		UCSCGiving_Test_State::$fields['base_url']          = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$object_terms['7:fund-type'] = array( 'Standard' );
		UCSCGiving_Test_State::$meta['7:designation']       = 'ABC123';
		UCSCGiving_Test_State::$current_post_id             = 7;

		$post = (object) array(
			'ID'        => 7,
			'post_type' => 'fund',
		);

		$this->assertSame(
			ucscgiving_fund_url(),
			ucscgiving_link_filter( 'https://example.test/fund/some-fund/', $post )
		);
	}
}
