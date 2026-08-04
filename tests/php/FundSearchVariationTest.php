<?php
/**
 * Tests for ucscgiving_create_fund_search_variation().
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the core/search block variation scoped to the fund post type.
 */
class FundSearchVariationTest extends TestCase {

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
	 * Build a block type object.
	 *
	 * The filter only reads ->name and has no type hint, so a plain object
	 * is enough; no WP_Block_Type stand-in is needed.
	 *
	 * @param string $name Block type name.
	 * @return object
	 */
	private function block_type( $name ) {
		return (object) array( 'name' => $name );
	}

	/**
	 * The variation is appended for core/search.
	 *
	 * @return void
	 */
	public function test_appends_variation_to_core_search() {
		$variations = ucscgiving_create_fund_search_variation( array(), $this->block_type( 'core/search' ) );

		$this->assertCount( 1, $variations );
		$this->assertSame( 'fund-search', $variations[0]['name'] );
		$this->assertSame( 'fund', $variations[0]['attributes']['query']['post_type'] );
	}

	/**
	 * Existing variations are preserved, not replaced.
	 *
	 * @return void
	 */
	public function test_preserves_existing_variations() {
		$existing = array( array( 'name' => 'existing' ) );

		$variations = ucscgiving_create_fund_search_variation( $existing, $this->block_type( 'core/search' ) );

		$this->assertCount( 2, $variations );
		$this->assertSame( 'existing', $variations[0]['name'] );
		$this->assertSame( 'fund-search', $variations[1]['name'] );
	}

	/**
	 * Other block types are returned untouched.
	 *
	 * @return void
	 */
	public function test_leaves_other_block_types_untouched() {
		$existing = array( array( 'name' => 'existing' ) );

		$variations = ucscgiving_create_fund_search_variation( $existing, $this->block_type( 'core/paragraph' ) );

		$this->assertSame( $existing, $variations );
	}
}
