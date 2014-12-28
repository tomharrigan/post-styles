=== McNinja Post Styles ===
Contributors: TomHarrigan
Tags: formatting, taxonomy, style, post formats, 
Requires at least: 3.1
Tested up to: 4.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Unleash the creativity of your content

== Description ==

McNinja Post Styles is what we always wanted Post Formats to be, flexible and extendable. Unleash the creativity of your content.

McNinja Post Styles...

* Creates a Post Style taxonomy, allowing authors to choose how to display a Post
* Supports all of the formats added by Post Formats (aside, gallery, link, image, quote, status, video, audio, chat) so that if your theme made use of Post Formats, the same templates can be used.
* Provides a filter hook so that you can add/create your own Post Styles (formats). Why be limited to what's provided in WP Core? 
* Provides a metabox on the Post Edit screen to easily choose the desired style.

Does not include content templates.

For more information or to follow the project, check out the [project page](http://thomasharrigan.com/mcninja-post-styles/).

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' Plugin Dashboard
1. Select `mcninja-post-styles.zip` from your computer
1. Upload
1. Activate the plugin on the WordPress Plugin Dashboard

= Using FTP =

1. Extract `mcninja-post-styles.zip` to your computer
1. Upload the `mcninja-post-styles` directory to your `wp-content/plugins` directory
1. Activate the plugin on the WordPress Plugins dashboard

== Frequently Asked Questions ==

= How do I make use of Post Styles? =

In your loop or other area in which displaying a content template, change your get_template_part call to the following:

get_template_part( 'content', get_post_style() );

get_post_style() will return the slug of the selected post style, for example, if a post is using the 'image' Post Style, then get_template_part will be looking to use content-image.php to display the post.

= What template will be used if the 'Standard' post style (default) is selected? =

By default, it will look for content-post.php, if there is no content-post.php, it will use content.php

= How do I add CSS to a specific Post Style? =

Posts will have a class associated with their style. The class name is in the form of 'post-style-(slug-name)', so a post using 'image' can be targeted with the '.post-style-image' class.

= How do I add a new Post Style? =

The 'post_style_strings' filter allows you to add new styles. It provides an array of Post Styles and you can add your new post style. The example below adds a new post style named 'Golden Unicorn', with a slug 'golden-unicorn'.

function my_new_custom_post_style( $strings ) {
	$strings['golden-unicorn'] = _x( 'Golden Unicorn', 'Post style' );
	return $strings;
}
add_filter( 'post_style_strings', 'my_new_custom_post_style');

= Why aren't there template files? =

This plugin provides the mechanism for allowing custom post formats. It is basically a glorified taxonomy. It is up to themes and developers to utlize this. I'll be writing some tutorials and examples on my blog shortly though. Feel free to shoot me an email or contact me in the meantime. 

== Screenshots ==

1. The metabox added to the Post Edit screen
2. Example of a post stream utlizing McNinja Post Styles with 'video', 'image', and 'standard' post styles.

== Changelog ==

= 2.0 =
* Add content formatting based on Post Style
* Add "Embed" Post Style
* Add "Chat" Post Style

= 1.1 =
* Expose get_post_style_link() function
* Expose get_post_style_string() function
* i18n support: add .pot file and make strings translateable
* Add backwards compatibility with post-format CSS classes for themes already implementing post formats

= 1.0 =
* Initial release
