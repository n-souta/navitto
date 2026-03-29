=== Navitto ===

Contributors: nsouta
Tags: navigation, table of contents, fixed nav, toc, heading
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fixed navigation bar that follows H2 headings and shows a simple, sticky table of contents.

== Description ==

Navitto adds a fixed navigation bar that follows the H2 headings of a single post or page and behaves like a compact table of contents.  
It helps readers understand “where they are” in long-form content and lets them jump smoothly to each section.

**Features**

* **Fixed navigation bar** – Stays at the top (or bottom) of the screen while scrolling and always shows the list of H2 headings.
* **Display modes** – Show all headings, select specific headings to show, or hide the nav entirely per post/page.
* **Customizer integration** – Choose a design preset (simple or theme-based), position (top/bottom), height, and font weight.
* **Bulk apply** – Enable or disable Navitto for all existing posts at once from “Settings → Navitto”.
* **Theme-aware** – Tries to detect common fixed headers and adjust scroll offset so the heading is not hidden behind the header.
* **Posts and pages** – Works on both posts and pages, which is useful for long landing pages and comparison content.

Navitto does **not** require any license key or external service.  
All features included in this plugin are fully available for free.

== Installation ==

1. Upload the plugin ZIP or search for “Navitto” from “Plugins → Add New” in your WordPress admin and install it.
2. Click “Activate”.
3. Open the post or page edit screen. You will see the “Navitto” meta box in the sidebar, where you can choose the display mode and select headings.
4. (Optional) Go to “Appearance → Customize → Navitto” to adjust design presets, position, height, and font weight.
5. (Optional) Go to “Settings → Navitto” to change the default behavior for new posts and run bulk enable/disable for existing posts.

== Frequently Asked Questions ==

= The fixed nav does not appear =

* Make sure the post/page is not set to “Hide fixed nav” in the Navitto meta box.
* The fixed nav is shown only when the content has at least two H2 headings. If there is only one (or none), it will not appear.

= How do I choose which headings are shown? =

Select “Choose headings to display” in the Navitto meta box.  
You can then check the H2 headings you want to show and optionally override their label text.
You can also control when the fixed nav appears (from the top of the page, or after passing the first selected heading).

= Can I insert the nav inside the theme header? =

If your theme supports the `navitto_fixed_nav_inside_header` filter and outputs the nav in the header, Navitto can be placed inside the header area.  
Please refer to your theme’s documentation for details.

== Screenshots ==

1. Navitto meta box on the post edit screen. You can choose the display mode, select headings, and adjust the trigger.
2. Example of the fixed navigation (simple preset, fixed at the top). The active item changes as you scroll through H2 headings.
3. “Navitto” section in the Customizer. You can configure presets, position, height, and font weight.
4. “Settings → Navitto” screen. Default behavior and bulk enable/disable controls.

== Changelog ==

= 1.0.0 = (2026-03-29)
* Initial release on the WordPress.org Plugin Directory.
* Changed: Customizer inline CSS is built only from predefined fixed strings (allowlisted theme_mod pairs), per Plugin Directory review guidance.

= 1.2.0 = (2026-02-20)
* Changed: Removed license checks and external license validation to comply with WordPress.org guidelines.
* Changed: Made all features included in this plugin fully available without any license key.
* Improved: Theme integration and minor visual tweaks to the fixed navigation.

= 1.1.0 = (2026-02-19)
* Added: Design presets (“Simple” / “Theme-based”) with Customizer controls.
* Changed: Respect the “Enable by default for new posts” option when determining initial display mode.
* Changed: Bulk enable/disable now preserves each post’s selected headings and display mode.
* Changed: Updated bulk apply description text.
* Removed: “Navitto – Common Settings” section from the Customizer (scroll offset fixed at 100px).

== Upgrade Notice ==

= 1.0.0 =
Initial release on WordPress.org. Customizer inline CSS uses only predefined CSS fragments for directory compliance. No functional changes from the last pre-release build.

= 1.2.0 =
This release removes license checks and external license validation and makes all included features available for free. It also includes minor visual and compatibility improvements for the fixed navigation bar.
