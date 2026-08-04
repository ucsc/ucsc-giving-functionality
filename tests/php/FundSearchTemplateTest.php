<?php
/**
 * Tests for ucscgiving_fund_search_template().
 *
 * This is the search_template filter that #121 found had never applied. The
 * cases below are the regression guard: PHPCS cannot catch a filter that
 * silently does nothing, but these will.
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers routing fund search results through the fund archive template.
 */
class FundSearchTemplateTest extends TestCase {

	/**
	 * The template path handed to the filter.
	 *
	 * @var string
	 */
	private $template = '/themes/ucsc-2022/search.php';

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
	 * A fund search falls through to the archive template.
	 *
	 * @return void
	 */
	public function test_returns_located_template_for_fund_search() {
		UCSCGiving_Test_State::$is_search               = true;
		UCSCGiving_Test_State::$query_vars['post_type'] = 'fund';
		UCSCGiving_Test_State::$located_template        = '';

		$this->assertSame( '', ucscgiving_fund_search_template( $this->template ) );
	}

	/**
	 * Searches for other post types keep the resolved search template.
	 *
	 * @return void
	 */
	public function test_leaves_other_post_type_searches_untouched() {
		UCSCGiving_Test_State::$is_search               = true;
		UCSCGiving_Test_State::$query_vars['post_type'] = 'post';

		$this->assertSame( $this->template, ucscgiving_fund_search_template( $this->template ) );
	}

	/**
	 * Outside a search the filter does nothing.
	 *
	 * @return void
	 */
	public function test_leaves_non_search_requests_untouched() {
		UCSCGiving_Test_State::$query_vars['post_type'] = 'fund';

		$this->assertSame( $this->template, ucscgiving_fund_search_template( $this->template ) );
	}

	/**
	 * The filter returns whatever locate_template() resolves, so it is that
	 * call — not the incoming path — that decides the result.
	 *
	 * @return void
	 */
	public function test_returns_whatever_locate_template_resolves() {
		UCSCGiving_Test_State::$is_search               = true;
		UCSCGiving_Test_State::$query_vars['post_type'] = 'fund';
		UCSCGiving_Test_State::$located_template        = '/themes/ucsc-2022/archive-fund.php';

		$this->assertSame(
			'/themes/ucsc-2022/archive-fund.php',
			ucscgiving_fund_search_template( $this->template )
		);
	}
}
