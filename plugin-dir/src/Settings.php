<?php

namespace iTRON\WPGoneControl;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Settings {
	public static string $optionPrefix;
	const MANAGE_CAPS = 'wp_gone_control_manage_options';

	public static function init(): void {
		add_action( 'carbon_fields_register_fields', [ self::class, 'createOptions' ] );
		add_action( 'after_setup_theme', [ self::class, 'loadCarbon' ] );

		self::$optionPrefix = PLUGIN_SLUG . '_';
	}

	public static function loadCarbon(): void {
		Carbon_Fields::boot();
	}

	public static function createOptions(): void {
		$option_page = Container::make( OPTIONS_MODE, 'WP Gone Control' );
		$settings    = [];

		$option_page->add_fields( $settings )
		            ->set_icon( 'dashicons-drumstick' )
		            ->where( 'current_user_capability', 'IN', [ self::MANAGE_CAPS, 'manage_options' ] );
	}

	private static function isOverloaded( $optionSlug ): bool {
		return defined( 'WP_GONE_CONTROL_' . strtoupper( $optionSlug ) );
	}

	private static function getOverloaded( $optionSlug ) {
		return constant( 'WP_GONE_CONTROL_' . strtoupper( $optionSlug ) );
	}

	/**
	 * @todo Cache invalidation.
	 *
	 * @param string $optionSlug
	 *
	 * @return mixed|null
	 */
	public static function getOption( string $optionSlug ) {
		if ( self::isOverloaded( $optionSlug ) ) {
			return self::getOverloaded( $optionSlug );
		}

		// Carbon Fields does not have a built-in caching mechanism, lol.
		$cache = wp_cache_get( $optionSlug, PLUGIN_SLUG );
		if ( false !== $cache ) {
			return $cache;
		}

		$value = carbon_get_theme_option( self::$optionPrefix . $optionSlug );
		wp_cache_set( $optionSlug, $value, PLUGIN_SLUG );

		return $value;
	}

	public static function getInterval(): int {
		return (int) self::getOption( 'reset_interval' );
	}
}
