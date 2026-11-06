/**
 * Wasp inheritable fields.
 *
 * Default mode: a field's inputs are disabled while it inherits its value and are
 * enabled when the "override" toggle is turned on.
 *
 * Auto mode (set_fallback_values( ..., [ 'auto_override' => true ] )): inputs stay
 * editable. Editing a field turns its override on; getting back to the inherited
 * value (by hand or with a [data-wasp-inherit-revert] control) turns it off.
 *
 * The server ignores inherited fields either way; this only keeps the form honest
 * about what will be saved.
 *
 * @package wasp
 * @since 3.1.0
 */
( function () {
	'use strict';

	function isAuto( wrapper ) {
		return wrapper.getAttribute( 'data-wasp-auto' ) === '1';
	}

	function parts( wrapper ) {
		return {
			toggle: wrapper.querySelector( '.wasp-inherit-checkbox' ),
			field: wrapper.querySelector( '.wasp-inheritable-field' )
		};
	}

	// Named form controls of the field (buttons and helper inputs without a name are left out).
	function controls( field ) {
		var all    = field.querySelectorAll( 'input[name], select[name], textarea[name]' );
		var result = [];

		for ( var i = 0; i < all.length; i++ ) {
			var type = ( all[ i ].type || '' ).toLowerCase();

			if ( type !== 'button' && type !== 'submit' && type !== 'reset' ) {
				result.push( all[ i ] );
			}
		}

		return result;
	}

	// Turns a value into a comparable string: lists are sorted, line breaks and "|" separate items.
	function normalize( value ) {
		var items = [];

		if ( value === null || value === undefined || value === false ) {
			return '';
		}

		if ( value === true ) {
			return '1';
		}

		if ( Array.isArray( value ) ) {
			items = value.map( String );
		} else if ( typeof value === 'object' ) {
			items = Object.keys( value );
		} else {
			items = String( value ).split( /[\r\n|]+/ );
		}

		return items
			.map( function ( item ) {
				return String( item ).trim();
			} )
			.filter( function ( item ) {
				return item !== '';
			} )
			.sort()
			.join( '\n' );
	}

	function readValue( field ) {
		var inputs     = controls( field );
		var checkboxes = inputs.filter( function ( el ) {
			return el.type === 'checkbox';
		} );
		var values     = [];

		for ( var i = 0; i < inputs.length; i++ ) {
			var el = inputs[ i ];

			if ( el.type === 'radio' ) {
				if ( el.checked ) {
					values.push( el.value );
				}
			} else if ( el.type === 'checkbox' ) {
				if ( el.checked ) {
					values.push( el.value );
				}
			} else if ( el.tagName === 'SELECT' && el.multiple ) {
				for ( var j = 0; j < el.options.length; j++ ) {
					if ( el.options[ j ].selected ) {
						values.push( el.options[ j ].value );
					}
				}
			} else {
				values.push( el.value );
			}
		}

		// A single checkbox stores its value or nothing.
		if ( checkboxes.length === 1 && inputs.length === 1 ) {
			return normalize( values.length ? values[ 0 ] : '' );
		}

		return normalize( values );
	}

	function setIfChanged( el, prop, next ) {
		if ( el[ prop ] !== next ) {
			el[ prop ] = next;
			el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}

	function applyValue( field, inherited ) {
		var inputs = controls( field );
		var wanted = normalize( inherited ).split( '\n' );
		var texts  = 0;

		for ( var i = 0; i < inputs.length; i++ ) {
			var el = inputs[ i ];

			if ( el.type === 'radio' || el.type === 'checkbox' ) {
				setIfChanged( el, 'checked', wanted.indexOf( el.value ) !== -1 );
			} else if ( el.tagName === 'SELECT' ) {
				for ( var j = 0; j < el.options.length; j++ ) {
					el.options[ j ].selected = wanted.indexOf( el.options[ j ].value ) !== -1;
				}
				el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} else if ( Array.isArray( inherited ) ) {
				setIfChanged( el, 'value', inherited[ texts ] !== undefined ? String( inherited[ texts ] ) : '' );
				texts++;
			} else {
				var text = inherited === null || inherited === undefined ? '' : String( inherited );

				// Like Templates::append(), single-line inputs carry line breaks as "|".
				if ( el.tagName !== 'TEXTAREA' ) {
					text = text.replace( /\r?\n/g, '|' );
				}

				setIfChanged( el, 'value', text );
			}
		}
	}

	function inheritedValue( wrapper ) {
		try {
			return JSON.parse( wrapper.getAttribute( 'data-wasp-inherited' ) || 'null' );
		} catch ( e ) {
			return null;
		}
	}

	function sync( wrapper ) {
		var p = parts( wrapper );

		if ( ! p.toggle || ! p.field ) {
			return;
		}

		var locked     = p.toggle.disabled;
		var overridden = p.toggle.checked && ! locked;
		var inputs     = p.field.querySelectorAll( 'input, select, textarea, button' );
		var editable   = overridden || ( isAuto( wrapper ) && ! locked );

		for ( var i = 0; i < inputs.length; i++ ) {
			inputs[ i ].disabled = ! editable;
		}

		wrapper.setAttribute( 'data-wasp-state', locked ? 'locked' : ( overridden ? 'overridden' : 'inherited' ) );
	}

	// Auto mode: the override follows whether the field differs from the inherited value.
	function follow( wrapper ) {
		var p = parts( wrapper );

		if ( ! p.toggle || ! p.field || p.toggle.disabled ) {
			return;
		}

		var inherited = normalize( inheritedValue( wrapper ) );
		var inputs    = controls( p.field );

		// An unchecked single checkbox matches an inherited "0" as well as an empty value.
		if ( inputs.length === 1 && inputs[ 0 ].type === 'checkbox' && inherited === '0' ) {
			inherited = '';
		}

		p.toggle.checked = readValue( p.field ) !== inherited;
		sync( wrapper );
	}

	function revert( wrapper ) {
		var p = parts( wrapper );

		if ( ! p.toggle || ! p.field || p.toggle.disabled ) {
			return;
		}

		applyValue( p.field, inheritedValue( wrapper ) );
		p.toggle.checked = false;
		sync( wrapper );

		wrapper.dispatchEvent( new CustomEvent( 'wasp:inherit-revert', { bubbles: true } ) );
	}

	function onEdit( event ) {
		var target  = event.target;
		var wrapper = target && target.closest ? target.closest( '.wasp-inheritable' ) : null;

		if ( ! wrapper ) {
			return;
		}

		if ( target.classList.contains( 'wasp-inherit-checkbox' ) ) {
			// Turning the override off by hand in auto mode goes back to the inherited value.
			if ( isAuto( wrapper ) && ! target.checked ) {
				revert( wrapper );
				return;
			}

			sync( wrapper );
			return;
		}

		if ( isAuto( wrapper ) && target.closest( '.wasp-inheritable-field' ) ) {
			follow( wrapper );
		}
	}

	function init() {
		var wrappers = document.querySelectorAll( '.wasp-inheritable' );

		for ( var i = 0; i < wrappers.length; i++ ) {
			sync( wrappers[ i ] );
		}

		document.addEventListener( 'change', onEdit );
		document.addEventListener( 'input', onEdit );

		document.addEventListener( 'click', function ( event ) {
			var control = event.target && event.target.closest ? event.target.closest( '[data-wasp-inherit-revert]' ) : null;
			var wrapper = control ? control.closest( '.wasp-inheritable' ) : null;

			if ( wrapper ) {
				event.preventDefault();
				revert( wrapper );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
