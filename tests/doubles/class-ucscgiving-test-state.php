<?php
/**
 * Mutable state backing the WordPress function stubs.
 *
 * @package ucsc-giving-functionality
 */

/**
 * Values the WordPress stand-ins in tests/stubs.php read from.
 *
 * Tests set what they need in setUp() and call reset() so nothing leaks
 * between cases.
 */
final class UCSCGiving_Test_State {

	/**
	 * ACF field values, keyed by field selector.
	 *
	 * @var array<string,mixed>
	 */
	public static $fields = array();

	/**
	 * Post meta values, keyed by "{post_id}:{meta_key}".
	 *
	 * @var array<string,mixed>
	 */
	public static $meta = array();

	/**
	 * Term objects, or WP_Error, keyed by term ID.
	 *
	 * @var array<int,mixed>
	 */
	public static $terms = array();

	/**
	 * Value returned by is_search().
	 *
	 * @var bool
	 */
	public static $is_search = false;

	/**
	 * Query vars, keyed by name.
	 *
	 * @var array<string,mixed>
	 */
	public static $query_vars = array();

	/**
	 * Value returned by locate_template().
	 *
	 * @var string
	 */
	public static $located_template = '';

	/**
	 * Value returned by get_the_ID().
	 *
	 * @var int
	 */
	public static $current_post_id = 0;

	/**
	 * Restore every stub to its default.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$fields           = array();
		self::$meta             = array();
		self::$terms            = array();
		self::$is_search        = false;
		self::$query_vars       = array();
		self::$located_template = '';
		self::$current_post_id  = 0;
	}
}
