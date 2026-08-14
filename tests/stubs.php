<?php
/**
 * WordPress function stand-ins for standalone test runs.
 *
 * Loaded only when no WordPress test suite is available. Each stub is the
 * smallest thing that lets the plugin load and its functions be exercised;
 * none of them reproduce WordPress behaviour faithfully. Values the tests
 * need to vary live on UCSCGiving_Test_State.
 *
 * Note that esc_url() here is a pass-through: these tests assert how the
 * fund URL is composed, not how WordPress escapes it.
 *
 * @package ucsc-giving-functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * No-op. The plugin registers hooks at file scope.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted argument count.
	 * @return bool
	 */
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * No-op. The plugin registers filters at file scope.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted argument count.
	 * @return bool
	 */
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Directory of the given file, with a trailing slash.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	/**
	 * Stand-in plugin URL.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_dir_url( $file ) {
		return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Plugin basename, as "directory/file.php".
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Pass-through translation.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Pass-through translation.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Pass-through. These tests assert URL composition, not escaping.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'get_field' ) ) {
	/**
	 * ACF get_field() stand-in.
	 *
	 * @param string     $selector Field name.
	 * @param int|string $post_id  Post ID, or 'option'.
	 * @return mixed
	 */
	function get_field( $selector, $post_id = false ) {
		return UCSCGiving_Test_State::$fields[ $selector ] ?? null;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Post meta stand-in.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed
	 */
	function get_post_meta( $post_id, $key = '', $single = false ) {
		return UCSCGiving_Test_State::$meta[ $post_id . ':' . $key ] ?? '';
	}
}

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Must match core's get_the_ID().
if ( ! function_exists( 'get_the_ID' ) ) {
	/**
	 * Current post ID stand-in.
	 *
	 * @return int
	 */
	function get_the_ID() {
		return UCSCGiving_Test_State::$current_post_id;
	}
}
// phpcs:enable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

if ( ! function_exists( 'has_term' ) ) {
	/**
	 * Object-term lookup stand-in.
	 *
	 * Matches a term name against the values and a term ID against the keys,
	 * mirroring core's name-or-slug-or-ID behaviour. Takes a post object as
	 * well as an ID, so the stub cannot pass for the wrong reason.
	 *
	 * @param string|int      $term     Term name or term ID.
	 * @param string          $taxonomy Taxonomy name.
	 * @param int|object|null $post     Post ID or post object.
	 * @return bool
	 */
	function has_term( $term = '', $taxonomy = '', $post = null ) {
		$post_id  = is_object( $post ) ? $post->ID : (int) $post;
		$assigned = UCSCGiving_Test_State::$object_terms[ $post_id . ':' . $taxonomy ] ?? array();

		if ( is_int( $term ) ) {
			return array_key_exists( $term, $assigned );
		}

		return in_array( $term, array_values( $assigned ), true );
	}
}

if ( ! function_exists( 'wp_get_object_terms' ) ) {
	/**
	 * Object-term list stand-in.
	 *
	 * Only the 'ids' field is modelled, which is all the plugin asks for.
	 *
	 * @param int    $object_id Object ID.
	 * @param string $taxonomy  Taxonomy name.
	 * @param array  $args      Query arguments.
	 * @return array
	 */
	function wp_get_object_terms( $object_id, $taxonomy, $args = array() ) {
		$assigned = UCSCGiving_Test_State::$object_terms[ $object_id . ':' . $taxonomy ] ?? array();

		if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
			return array_keys( $assigned );
		}

		return $assigned;
	}
}

if ( ! function_exists( 'wp_set_object_terms' ) ) {
	/**
	 * Object-term write stand-in.
	 *
	 * Records the call and applies it to the assigned terms, so a caller that
	 * writes and then re-reads sees its own write.
	 *
	 * It also fires the one hook core fires from here. add_action() is a no-op
	 * in this harness, so without this the plugin's set_object_terms callback
	 * would never re-enter and nothing would test that its corrective write
	 * settles instead of cascading.
	 *
	 * Term names are not recoverable from IDs alone, so the previous name is
	 * carried over where it is known and an empty string used otherwise.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Term IDs to set.
	 * @param string $taxonomy  Taxonomy name.
	 * @param bool   $append    Whether to append rather than replace.
	 * @return array The term IDs now assigned.
	 */
	function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
		UCSCGiving_Test_State::$term_writes[] = array( $object_id, $terms, $taxonomy, $append );

		$key      = $object_id . ':' . $taxonomy;
		$existing = UCSCGiving_Test_State::$object_terms[ $key ] ?? array();
		$updated  = $append ? $existing : array();

		foreach ( (array) $terms as $term_id ) {
			$updated[ (int) $term_id ] = $existing[ (int) $term_id ] ?? '';
		}

		UCSCGiving_Test_State::$object_terms[ $key ] = $updated;

		$tt_ids = array_keys( $updated );

		// Stands in for do_action( 'set_object_terms', … ) at the end of core's
		// wp_set_object_terms().
		if ( function_exists( 'ucscgiving_enforce_single_fund_type' ) ) {
			ucscgiving_enforce_single_fund_type( $object_id, $terms, $tt_ids, $taxonomy );
		}

		return $tt_ids;
	}
}

if ( ! function_exists( 'is_search' ) ) {
	/**
	 * Whether the current query is a search.
	 *
	 * @return bool
	 */
	function is_search() {
		return UCSCGiving_Test_State::$is_search;
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	/**
	 * Query var stand-in.
	 *
	 * @param string $query_var Query var name.
	 * @param mixed  $fallback  Value to return when the var is not set.
	 * @return mixed
	 */
	function get_query_var( $query_var, $fallback = '' ) {
		return UCSCGiving_Test_State::$query_vars[ $query_var ] ?? $fallback;
	}
}

if ( ! function_exists( 'locate_template' ) ) {
	/**
	 * Template lookup stand-in.
	 *
	 * @param string|array $template_names Template names.
	 * @param bool         $load           Whether to load the file.
	 * @param bool         $load_once      Whether to require_once.
	 * @return string
	 */
	function locate_template( $template_names, $load = false, $load_once = true ) {
		return UCSCGiving_Test_State::$located_template;
	}
}
