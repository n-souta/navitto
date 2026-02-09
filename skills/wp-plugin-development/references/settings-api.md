# Settings API (admin options)

This guide explains the WordPress Settings API and how to use it to add settings pages and store user-configurable options.

Core APIs:

- `register_setting()`
- `add_settings_section()`
- `add_settings_field()`

Upstream references:

- Settings API overview: https://developer.wordpress.org/plugins/settings/settings-api/
- Register settings: https://developer.wordpress.org/plugins/settings/registration/
- Add settings fields: https://developer.wordpress.org/plugins/settings/settings-fields/

Practical guardrails:

- Use `sanitize_callback` to validate/sanitize data.
- Use capability checks (commonly `manage_options`) for settings screens and saves.
- Escape values on output (`esc_attr`, `esc_html`, etc.).

