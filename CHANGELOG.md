# Changelog

## 3.1.0

### Added
- `storage` constructor option: `'site'` (default) or `'network'` (multisite, `get_site_option`).
- Network admin save handler for network forms (`network_admin_edit_<page>`), with nonce and capability checks.
- `capability` constructor option; site forms pass it to `option_page_capability_<page>`.
- Fallback layer: `set_fallback_values()`, per-field `inherit` flag, locked keys, `Inherit` template,
  `get_resolved_values()`, `has_fallback()`, `is_locked()`, `is_inheritable()`.
- `Storage` class; `get_storage()`, `get_capability()`.
- `write_values()`: stores values without running the form middleware (for resets and other programmatic writes).
- `Templates::resolve()`; themes accept absolute paths and fall back to the default theme per template.

### Fixed
- Security: the AJAX loader called the hook and parameters sent by the browser, so anyone holding the
  nonce could call any PHP function. It now only uses the server-side field configuration and checks
  the form's capability.
- Security: field reset used the option name sent by the browser, so it could overwrite any option
  (for example `active_plugins`). It now only writes the form's own option.
- Reset handlers of several forms on one request no longer answer for each other.
- Field reset did not remove keys without a default: WordPress ran the form middleware on the reset write,
  which merged the old value back in.
- The template set was cached once per request, so a second theme was ignored.
- `remove_settings()` deleted row numbers instead of row option names.
- A section with `before`/`after` HTML lost its title and description (the section data was replaced by
  the HTML string). `$current_section` is documented as an array (fixes the IDE type error).
- First save of a form whose option did not exist yet could fatal on PHP 8 (`array + null`).
- Test bootstrap defines `ABSPATH` so the classes load under PHPUnit.
