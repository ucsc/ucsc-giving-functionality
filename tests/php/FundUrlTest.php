<?php
/**
 * Tests for ucscgiving_fund_url().
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the block-binding callback that builds the external giving URL.
 */
class FundUrlTest extends TestCase {

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
	 * The happy path: base URL plus the fund's designation code.
	 *
	 * @return void
	 */
	public function test_concatenates_base_url_and_designation() {
		UCSCGiving_Test_State::$fields['base_url']     = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$current_post_id        = 42;
		UCSCGiving_Test_State::$meta['42:designation'] = 'ABC123';

		$this->assertSame( 'https://give.example.edu/fund/ABC123', ucscgiving_fund_url() );
	}

	/**
	 * With no designation the base URL is returned on its own.
	 *
	 * @return void
	 */
	public function test_returns_base_url_when_designation_is_missing() {
		UCSCGiving_Test_State::$fields['base_url'] = 'https://give.example.edu/fund/';
		UCSCGiving_Test_State::$current_post_id    = 42;

		$this->assertSame( 'https://give.example.edu/fund/', ucscgiving_fund_url() );
	}

	/**
	 * Without base_url there is nothing to build from.
	 *
	 * This is the ucsc-2022 coupling: base_url lives on an options page the
	 * theme registers, so it is empty whenever that theme is not active.
	 *
	 * @return void
	 */
	public function test_returns_empty_string_when_base_url_is_missing() {
		UCSCGiving_Test_State::$current_post_id        = 42;
		UCSCGiving_Test_State::$meta['42:designation'] = 'ABC123';

		$this->assertSame( '', ucscgiving_fund_url() );
	}

	/**
	 * Neither value present.
	 *
	 * @return void
	 */
	public function test_returns_empty_string_when_nothing_is_set() {
		$this->assertSame( '', ucscgiving_fund_url() );
	}
}
