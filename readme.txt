=== Posthaste ===
Contributors: smajda
Author URI: http://jon.smajda.com
Plugin URI: http://wordpress.org/extend/plugins/posthaste/
Tags: prologue, post
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 1.3

Adds the post box from the Prologue theme (modified to include a Title field, Category dropdown and a  Save as Draft option) to any theme.

== Description ==

Adds the post box from the [Prologue theme](http://wordpress.org/extend/themes/prologue/) (modified to include a Title field and Category dropbox) to any theme.

This plugin reuses much code from the [Prologue](http://wordpress.org/extend/themes/prologue/) theme according to the terms of the GNU General Public License. So a big thanks to the authors of Prologue, Joseph Scott and Matt Thomas.

A few notes about the plugin's behavior: 

* In WordPress 2.7 and higher, you can select which fields you want to appear in the form and what pages you want the form to appear on. Settings are in "Settings -> Writing -> Posthaste Settings".
* If you leave the "Title:" field blank, it takes the first 40 characters of the post content and makes that the title.
* If you leave the "Category:" box at its default setting ("Category...") it posts to your default category. _However..._
* If you have a category named 'asides', it will put posts with empty titles into the 'asides' category even if you do not explicitly specify the 'asides' category in the dropdown. You can then [style them as asides](http://codex.wordpress.org/Adding_Asides).
* The included CSS is deliberately simple. If your theme already styles forms, it will probably inherit your theme's styling. If you want to customize the appearance of the form, just customize your own css files.

== Installation ==

Just upload the `posthaste` directory to `/wp-content/plugins/` and activate. No settings or anything. 

== Frequently Asked Questions ==

= Can I customize the automatic 'asides' behavior? =

If you call your 'asides' category something other than 'asides' or if you just don't like this behavior at all, you can change it. There's no administration interface for this plugin (for now), so you'll have to edit the `posthaste.php` file directly, but fortunately its very easy. Just find the line right at the top of the plugin that says `"asidesCatName" => "asides"` and change the `"asides"` to whatever you call asides, or, to get rid of this behavior entirely, just type in anything that _is not_ the name of a category on your blog.

= Help! I activated the plugin but the form isn't showing up! =

It's possible your theme has `get_sidebar()` placed _before_ the loop at the top of your theme (Most themes call `get_sidebar()` after the loop, but some do it before). This plugin attaches the form at the start of the loop, which usually works fine. In order to prevent the "Recent Posts" widget (or any other widgets which call [multiple loops](http://codex.wordpress.org/The_Loop#Multiple_Loops)) from _also_ causing the form to display, the plugin deactives the form once `get_sidebar()` is called. So if `get_sidebar()` runs first, the form won't appear in the "real loop" either.

If you're willing to edit your theme's `index.php` file, the fix is easy. Just place the following where you want the form to appear on your page (probably right before [the loop](http://codex.wordpress.org/The_Loop)):

`<?php if(function_exists(posthasteForm)) { posthasteForm(); } ?>`

Another option, if you have no other loops called on a page and would rather edit the plugin instead of your theme, is to comment out the action that removes the loop at `get_sidebar()`. Find the following line near the bottom of the plugin:

`add_action('get_sidebar', removePosthasteInSidebar);`

and comment it out by adding two slashes at the beginning of the line:

`//add_action('get_sidebar', removePosthasteInSidebar);`

_Developers: if there's a better way to handle this, I'd be very appreciative if you'd let me know because this is an annoying workaround._

== Screenshots ==

1. This is what the form looks like on a simple theme with a white background without any fancy CSS styling on the forms. 
2. Here's the form on the default K2 theme. 
3. Choose which fields to show. In Settings -> Writing (2.7 only).

== Changelog ==

= 1.3 =
* You can now choose where you want the form to appear in Settings > Writing > Posthaste Settings. You can display the form on your Front Page (default), Front Page and all archive pages, all pages or only on the archive pages for a specific category.
* You can also now choose whether or not to display the "Hello" greeting and admin links.
* Category selection works a little different now. By default, the category dropdown selects your default category, unless you're showing the form on a category archive page, in which case it selects the category for that page. If you aren't displaying the category dropdown at all, it will post to your default category *unless* you're posting from a category archive page, then it will post to the category of the category archive page you're on.
* Tag selection is similar: if you show the form on a tag archive page, that tag is auto-filled in the tag field (if it's visible) or auto-added to a new post from that page (if the tag field is not visible).


= 1.2.1 =
* Fixed gravatars 

= 1.2 = 
* Added auto-suggest feature to Tags field 
* Optional avatar display. 

= 1.1 =
* You can now choose which fields you want to show up under Settings -> Writing -> Posthaste Settings (WP 2.7 only). 
* Also adds a checkbox to save your post as a Draft. 

= 1.0.1 =
* Filters HTML out of title field. Just a one-line change. For blogs with a small, private groups of trusted authors who don't care about this, feel free to skip this update.

= 1.0 = 
* First release.
