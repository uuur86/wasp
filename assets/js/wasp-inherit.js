/**
 * Wasp inheritable fields.
 *
 * Disables a field's inputs while it inherits its value, and enables them when
 * the "override" toggle is turned on. The server ignores inherited fields either
 * way; this only keeps the form honest about what will be saved.
 *
 * @package wasp
 * @since 3.1.0
 */
( function () {
	'use strict';

	function sync( wrapper ) {
		var toggle = wrapper.querySelector( '.wasp-inherit-checkbox' );
		var field  = wrapper.querySelector( '.wasp-inheritable-field' );

		if ( ! toggle || ! field ) {
			return;
		}

		var overridden = toggle.checked && ! toggle.disabled;
		var inputs     = field.querySelectorAll( 'input, select, textarea, button' );

		for ( var i = 0; i < inputs.length; i++ ) {
			inputs[ i ].disabled = ! overridden;
		}

		wrapper.setAttribute( 'data-wasp-state', toggle.disabled ? 'locked' : ( overridden ? 'overridden' : 'inherited' ) );
	}

	function init() {
		var wrappers = document.querySelectorAll( '.wasp-inheritable' );

		for ( var i = 0; i < wrappers.length; i++ ) {
			sync( wrappers[ i ] );
		}

		document.addEventListener( 'change', function ( event ) {
			var target = event.target;

			if ( ! target || ! target.classList || ! target.classList.contains( 'wasp-inherit-checkbox' ) ) {
				return;
			}

			var wrapper = target.closest( '.wasp-inheritable' );

			if ( wrapper ) {
				sync( wrapper );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
