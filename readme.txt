=== SimpleComments ===
Contributors: sephers2
Tags: comments, simple, css, customisable
Requires at least: 3.1.4
Tested up to: 3.1.4
Stable tag: 1.4

SimpleComments is a fully customisable, easy to use comment system for your wordpress site.

== Description ==

PLEASE NOTE THIS IS IN BETA AND SHOULD NOT BE USED OTHER THAN FOR TESTING AND EVALUATING!

SimpleComments is an easy to use wordpress plugin that allows users and developers to add an effective system for people to comment on their posts. SimpleComments adds a page to the administration area with settings to completely change how the comments work. For users with previous coding experience, there is an easy to edit CSS file to completely alter the look of the comment tree.

For developers, the entire plugin is documented allowing the whole system to change as per the users needs, but for those who have no coding experience, the system works right out of the box, giving an easy to use interface for users to comment on wordpress posts.

== Features ==

*   Easy to integrated into any theme.
*   Allows restrictions to be set based on the user.
*   Fully documented and customisable for PHP and Javascript developers.
*   Fully AJAX enabled.
*   Allows comments to be reported and makes use of the wordpress moderation system.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

Upload the files in SimpleComments.zip to the `/wp-content/plugins/` directory

Activate the plugin through the 'Plugins' menu in WordPress

First add: 
`<?php wp_enqueue_script("jquery"); ?>`
to your themes header file, but ensure it comes before wp_head();

Then add:
`<?php simpleComments($id); ?>`
in your theme files where you want the comments to be displayed, where $id is the
id of the post on that page.
	
For example, if used within the Loop:
`<?php simpleComments(get_the_ID()); ?>`

== Troubleshoot ==
	
When installing you may get a notice saying something about headers in a file called pluggable.php 
Just to say this isn't the plugin's fault and it will have installed if you go back to the plugin page but if you want to ensure the notice doesn't come again, open that file 'pluggable.php', scroll to the bottom and if there's a missing `?>` then add it. 

== Changelog ==

= 1.4 =
* Add BETA feedback to Administration page

= 1.3.1 =
* Updated readme.txt

= 1.3 =
* Updated readme.txt

= 1.2 =
* Altered AJAX method in simp_comments.php - should give better compatability
* Fixed smInstall() to fix report bug 

= 1.1 =
* Updated 'simp_comments_report.php' - removed error message if no reported comments are found

= 1.0 =
* SimpleComments BETA - inital release

== License ==

Copyright 2011 George Sephton
	
This code is released under the Creative Commons Attribution-ShareAlike	license

See more at http://creativecommons.org/licenses/by-sa/3.0/legalcode