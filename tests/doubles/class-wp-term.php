<?php
/**
 * WP_Term stand-in.
 *
 * @package ucsc-giving-functionality
 */

if ( ! class_exists( 'WP_Term' ) ) {

	/**
	 * Minimal WP_Term.
	 *
	 * The permalink filter tests its resolved term with `instanceof WP_Term`,
	 * so this class has to exist under its real name rather than being faked
	 * with stdClass.
	 */
	class WP_Term {

		/**
		 * Term ID.
		 *
		 * @var int
		 */
		public $term_id;

		/**
		 * Term name.
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Constructor.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $name    Term name.
		 */
		public function __construct( $term_id = 0, $name = '' ) {
			$this->term_id = $term_id;
			$this->name    = $name;
		}
	}
}
