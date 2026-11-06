<?php
/**
 * Test Class: FieldsTest
 * Summary: Unit tests for Fields class.
 *
 * @package wasp
 */

namespace WaspCreators\Tests;

use WaspCreators\Fields;
use Brain\Monkey;

/**
 * Summary of FieldsTest
 */
class FieldsTest extends BaseTestCase {

	/**
	 * Summary of test_all_field_types
	 *
	 * @return void
	 */
	public function test_all_field_types() {
		$types = [
			'text_input',
			'multi_text_input',
			'file_input',
			'taginput',
			'taginput_regex',
			'radio',
			'select',
			'checkbox',
			'onecheckbox',
			'textarea',
		];

		// Mock checked() and selected() to return strings.
		Monkey\Functions\when( 'checked' )->alias(
			function ( $checked, $current, $is_echo = true ) {
				if ( $checked === $current ) {
					if ( $is_echo ) {
						echo ' checked="checked"';
						return;
					}
					return ' checked="checked"';
				}
				return '';
			}
		);

		Monkey\Functions\when( 'selected' )->alias(
			function ( $selected, $current, $is_echo = true ) {
				if ( $selected === $current ) {
					if ( $is_echo ) {
						echo ' selected="selected"';
						return;
					}
					return ' selected="selected"';
				}
				return '';
			}
		);

		foreach ( $types as $type ) {
			$args = [
				'type'        => $type,
				'id'          => 'test_id',
				'name'        => 'test_name',
				'value'       => 'test_value',
				'option'      => 'test_option',
				'options'     => [
					[
						'value' => 'opt1',
						'label' => 'Option 1',
					],
					[
						'value' => 'opt2',
						'label' => 'Option 2',
					],
				],
				'placeholder' => 'Test Placeholder',
				'presets'     => 'Test Presets',
				'field_type'  => 'url',
				'html_before' => '<div class="before">',
				'html_after'  => '</div>',
			];

			$html = Fields::prepare( $args );

			// Basic assertions to ensure HTML was built.
			$this->assertIsString( $html, "Field type '{$type}' should return a string." );
			$this->assertStringContainsString( '<div class="before">', $html, "Field type '{$type}' missing html_before." );
			$this->assertStringContainsString( '</div>', $html, "Field type '{$type}' missing html_after." );
			$this->assertStringContainsString( 'test_name', $html, "Field type '{$type}' missing name attribute." );
		}
	}

	/**
	 * Summary of test_fields_not_found
	 *
	 * @return void
	 */
	public function test_fields_not_found() {
		$args = [
			'type'      => 'text_input',
			'not_found' => 'Field Not Found Message',
		];

		$html = Fields::prepare( $args );

		$this->assertEquals( 'Field Not Found Message', $html );
	}

	/**
	 * Summary of test_filter_output
	 *
	 * @return void
	 */
	public function test_filter_output() {
		$raw_html = '<input type="text" id="test" />';
		$filtered = Fields::filter_output( $raw_html, '<p>', '</p>' );

		// wp_kses isn't mocked deeply but Monkey returns the first argument if not set?
		// Wait, we didn't mock wp_kses. It will crash if wp_kses is not mocked.
		// Let's mock wp_kses to just return the string.
		Monkey\Functions\when( 'wp_kses' )->returnArg( 1 );

		$filtered = Fields::filter_output( $raw_html, '<p>', '</p>' );
		$this->assertStringContainsString( '<p>', $filtered );
		$this->assertStringContainsString( '</p>', $filtered );
	}
}
