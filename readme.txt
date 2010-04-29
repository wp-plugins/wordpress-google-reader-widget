=== Wordpress Google Reader Widget ===
Contributors: miguelibero
Donate link: http://www.peix.org/donate
Tags: widget, greader
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.2

Shows items in your google reader.

== Description ==

Google reader is an online RSS feed reader by Google Inc. It allows you to share combined feeds of shared, starred or tagged feeds.
This widget takes that data and shows it on your wordpress blog's sidebar.

== Installation ==

First install the widgets plugin, the instructions can be found here (Included from Wordpress 2.2 on). Then copy the directory to your wp-content/plugins directory. Enable the plugin in your wordpress administration and add it to your sidebar.

== Changelog ==

= 0.1 =
initial release

= 0.1.5 =
fixed small weird hex in feed bug

= 0.2 =
remade code to use new widget class and simplepie, added advanced format parameter

== Frequently Asked Questions ==

= How do I obtain my Google Reader ID? =

Go to your Google Reader page and select "shared items" on the left sidebar. The start of the page will explain how to share the items with oyr friends and will list a URL in the form http://www.google.com/reader/shared/[ID], the last numeric part is your ID.

= I selected a tag but no link is shown in the widget. How do I fix it? =

You first have to set the tag to public in your feed setings in your reader, tags are private by default.

== Screenshots ==

1. example widget
