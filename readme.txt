=== WP-Polaroid Plus ===
Author: I. Savkovic
Contributors: I. Savkovic
Donate link: http://www.polaroidgallery.hostoi.com/
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.8.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tags: picture, pictures, gallery, galleries, imageflow, coverflow, flow, image, images, flow, lightbox, carousel, autorotate, automatic, rotate, media, tages

Polaroid Plus style picture gallery with Lightbox popups. Uses images from either the Wordpress Media Library or an uploaded directory of images. 

== Description ==

Display images as polaroid pictures on the current page or post using the basic JQuery library.

There are three ways to insert a WP-Polaroid Plus gallery:

1. Select the images attached to your post/page with the shortcode [wp-polaroidplus]
2. Upload your pictures to a subfolder and use the shortcode [wp-polaroidplus dir=SUBFOLDER]
3. Tag images in your media library using the Media Tags plugin by Paul Menard and use the shortcode [wp-polaroidplus mediatag=tagslug]

== Features ==

* Multiple galleries per page
* Configure the background color, text color, container width and choose black or white for the scrollbar. 
* Auto-rotation of the images
* Configure the starting slide number
* Touch control of the scrollbar
* Optional link field in the image editor to link an image to an URL instead of the lightbox
* Option to open links in the same window or a new window
* Enable/disable automatic rotation for each instance of a gallery
* Supports full text description in the popup window of a gallery from the media library


== Installation ==

1. Unzip to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the gallery in Settings -> WP-Polaroid Plus.

= Using the built-in Wordpress library: =

1. Upload images using the Wordpress image uploader on your post or page or into the media library. Enter a title to display, and optionally enter an external link.
2. Use the shortcode [wp-polaroidplus] anywhere in the post or page
3. If you want the image to link to an external URL, enter the address in the WP-Polaroid Plus link field in the image editor (ie: http://www.website.com). If the link field does not contain a URL, the image will link to the full size popup image with the description (if any) displayed as text below the image.


= For galleries based on a subfolder: =

1. Create a folder for your galleries within your WordPress installation, wherever you want. The location has to be accessible from the internet - for example you could use wp-content/galleries.
2. Upload your image galleries to a subfolder of this folder, for example you might upload your images under "wp-content/galleries/subfolder".
3. Set the "Path to galleries from homepage root path" in the settings admin page for WP-Polaroid Plus. Enter the path with trailing slash like: "wp-content/galleries/". NEW - alternatively you may also enter the full path like "http://www.mywebsite.com/wp-content/galleries/". Note that the gallery must reside on the same server as the blog. If you have entered the gallery path correctly you will see a list of the sub-directories on the settings page.
4. Insert a gallery on a page by specifying the shortcode [wp-polaroidplus dir=subfolder] on your post or page.

This gallery style will display the image names as the captions, and will link to the full size image.


== Frequently Asked Questions ==

= How can I help support this plugin? =

A donation to support this plugin would be greatly appreciated. I also appreciate a rating on the WordPress plugin directory.

== Screenshots ==

1. WP-Polaroid Plus
2. Choose the options you need. 

== Changelog ==

Version 1.5 (April 27, 2014)

* Better central image display 

Version 1.4 (April 27, 2014)

* Better image display 

Version 1.3 (June 09, 2013)

* Fix problem with image display on window resize 

Version 1.2 (May 18, 2013)

* Fix problem with image display


Version 1.1 (May 18, 2013)

* Fix screenshots in readme.txt
* Enable transform property for different browsers

Version 1.0 (May 09, 2013)

* Initial version

== Upgrade Notice ==

* Initial version




