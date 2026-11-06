<?php
/**
 * WaspCreators\Storage class.
 *
 * @package wasp
 */

namespace WaspCreators;

/**
 * Option storage backend.
 *
 * Wasp reads and writes every value through this class so the same form can be
 * stored per site (wp_options) or network wide (wp_sitemeta on multisite).
 *
 * @since 3.1.0
 */
class Storage {

	/**
	 * Per-site storage (get_option / update_option).
	 *
	 * @var string
	 */
	const SITE = 'site';

	/**
	 * Network-wide storage (get_site_option / update_site_option).
	 * Falls back to per-site storage on single site installs, which is what
	 * WordPress itself does for site options.
	 *
	 * @var string
	 */
	const NETWORK = 'network';

	/**
	 * Storage mode.
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Constructor.
	 *
	 * @param string $mode Storage::SITE or Storage::NETWORK. Unknown values fall back to SITE.
	 */
	public function __construct( $mode = self::SITE ) {
		$this->mode = ( self::NETWORK === $mode ) ? self::NETWORK : self::SITE;
	}

	/**
	 * Returns the storage mode.
	 *
	 * @return string
	 */
	public function get_mode() {
		return $this->mode;
	}

	/**
	 * Whether values are stored network wide.
	 *
	 * @return bool
	 */
	public function is_network() {
		return self::NETWORK === $this->mode;
	}

	/**
	 * Gets an option.
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Value returned when the option does not exist.
	 * @return mixed
	 */
	public function get( $name, $default = false ) {
		if ( $this->is_network() ) {
			return \get_site_option( $name, $default );
		}

		return \get_option( $name, $default );
	}

	/**
	 * Adds an option if it does not exist yet.
	 *
	 * Site options keep WordPress' default autoload behaviour, exactly as before
	 * 3.1.0, because plugins often read their settings on every request.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public function add( $name, $value ) {
		if ( $this->is_network() ) {
			return \add_site_option( $name, $value );
		}

		return \add_option( $name, $value );
	}

	/**
	 * Creates or updates an option.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public function update( $name, $value ) {
		if ( $this->is_network() ) {
			return \update_site_option( $name, $value );
		}

		return \update_option( $name, $value );
	}

	/**
	 * Deletes an option.
	 *
	 * @param string $name Option name.
	 * @return bool
	 */
	public function delete( $name ) {
		if ( $this->is_network() ) {
			return \delete_site_option( $name );
		}

		return \delete_option( $name );
	}
}
