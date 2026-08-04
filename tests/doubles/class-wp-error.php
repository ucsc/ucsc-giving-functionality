<?php
/**
 * WP_Error stand-in.
 *
 * @package ucsc-giving-functionality
 */

if ( ! class_exists( 'WP_Error' ) ) {

	/**
	 * Minimal WP_Error.
	 *
	 * Exists so the is_wp_error() stub can keep core's semantics of testing
	 * the object's type rather than a marker property.
	 */
	class WP_Error {

		/**
		 * Error code.
		 *
		 * @var string
		 */
		public $code;

		/**
		 * Constructor.
		 *
		 * @param string $code Error code.
		 */
		public function __construct( $code = '' ) {
			$this->code = $code;
		}
	}
}
