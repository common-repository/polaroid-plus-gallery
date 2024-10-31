<?php
/*
Plugin Name: WP-Polaroid Plus Gallery
Plugin URI: http://www.polaroidgallery.hostoi.com
Description: WordPress implementation of the polaroid picture gallery. 
Version: 1.5
Author: I. Savkovic
Author URI: http://www.polaroidgallery.hostoi.com

Originally based on the plugin by Bev Stofko http://www.stofko.ca/wp-imageflow2-wordpress-plugin/.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
global $wp_version;
define('POLAROIDPLUSVERSION', version_compare($wp_version, '2.8.4', '>='));

if(!defined("PHP_EOL")){define("PHP_EOL", strtoupper(substr(PHP_OS,0,3) == "WIN") ? "\r\n" : "\n");}

if (!class_exists("PPGallery")) {
Class PPGallery
{
	var $adminOptionsName = 'ppgallery_options';

	/* html div ids */
	var $imageflow2div = 'pp_imageflow';
	var $loadingdiv   = 'pp_loading';
	var $imagesdiv    = 'pp_images';
	var $captionsdiv  = 'pp_captions';
	var $sliderdiv    = 'pp_slider';
	var $scrollbardiv = 'pp_scrollbar';
	var $noscriptdiv  = 'pp_imageflow_noscript';
	

	var $pp_instance = 0;
	var $pp_id = 0;

	function ppgallery()
	{
		if (!POLAROIDPLUSVERSION)
		{
			add_action ('admin_notices',__('WP-Polaroid Plus requires at least WordPress 2.8.4','wp-polaroidplus'));
			return;
		}	
		
		register_activation_hook( __FILE__, array(&$this, 'activate'));
		register_deactivation_hook( __FILE__, array(&$this, 'deactivate'));
		add_action('init', array(&$this, 'addScripts'));	
		add_action('admin_menu', array(&$this, 'add_settings_page'));
		add_shortcode('wp-polaroidplus', array(&$this, 'flow_func'));	
		add_filter("attachment_fields_to_edit", array(&$this, "image_links"), null, 2);
		add_filter("attachment_fields_to_save", array(&$this, "image_links_save"), null , 2);

	}
	
	function activate()
	{
		/*
		** Nothing needs to be done for now
		*/
	}
	
	function deactivate()
	{
		/*
		** Nothing needs to be done for now
		*/
	}			
	
	function flow_func($attr,$pp_id) {
		/*
		** WP-Polaroid Plus gallery shortcode handler
		*/

		/* Increment the instance to support multiple galleries on a single page */
		$this->pp_instance ++;


		/* Load scripts, get options */
		$options = $this->getAdminOptions();

		/* Produce the Javascript for this instance */
		$js  = "\n".'<script type="text/javascript">'."\n";
		$js .= 'jQuery(document).ready(function() { '."\n".'var polaroidplus_' . $this->pp_instance . ' = new polaroidplus('.$this->pp_instance.','.$this->pp_id.');'."\n";
		$js .= 'polaroidplus_' . $this->pp_instance . '.init( {';

		if ( !isset ($attr['rotate']) ) {
			$js .= 'conf_autorotate: "' . $options['autorotate'] . '", ';
		} else {
			$js .= 'conf_autorotate: "' . $attr['rotate'] . '", ';
		}
		$js .= 'conf_autorotatepause: ' . $options['pause'] . ', ';
		if ( !isset ($attr['startimg']) ) {
			$js .= 'conf_startimg: 1' . ', ';
		} else {
			$js .= 'conf_startimg: ' . $attr['startimg'] . ', ';
		}
		if ( !isset ($attr['samewindow']) ) {
			$js .= $options['samewindow']? 'conf_samewindow: true' : 'conf_samewindow: false';
		} else {
			$js .= 'conf_samewindow: ' . $attr['samewindow'];
		}

		$js .= '} );'."\n";
		$js .= '});'."\n";
		$js .= "</script>\n\n";

		/* Get the list of images */
		$image_list = apply_filters ('pp_image_list', array(), $attr);
		if (empty($image_list)) {
		 	if ( !isset ($attr['dir']) ) {
				$image_list = $this->images_from_library($attr, $options);
			} else {
				$image_list = $this->images_from_dir($attr, $options);
	  		}
		}

		/* Prepare options */
		$bgcolor = $options['bgcolor'];
		$txcolor = $options['txcolor'];
		$slcolor = $options['slcolor'];
		$width   = $options['width'];
		$height  = $options['height'];
		$link    = $options['link'];

	

		$plugin_url = plugins_url( '', __FILE__ );

		/**
		* Start output
		*/
		$noscript = '<noscript><div id="' . $this->noscriptdiv . '_' . $this->pp_instance . '" class="' . $this->noscriptdiv . '">';	
		$output  = '<div id="' . $this->imageflow2div . '_' . $this->pp_instance . '" class="' . $this->imageflow2div . '" style="background-color: ' . $bgcolor . '; color: ' . $txcolor . '; width: ' . $width . '; height: ' . $height .'">' . PHP_EOL; 
		$output .= '<div id="' . $this->loadingdiv . '_' . $this->pp_instance . '" class="' . $this->loadingdiv . '" style="color: ' . $txcolor . ';">' . PHP_EOL;
		$output .= '<b>';
		$output .= __('Loading Images','wp-polaroidplus');
		$output .= '</b><br/>' . PHP_EOL;
		$output .= '<img src="' . $plugin_url . '/img/loading.gif" width="208" height="13" alt="' . $this->loadingdiv . '" />' . PHP_EOL;
		$output .= '</div>' . PHP_EOL;
		$output .= '<div id="' . $this->imagesdiv . '_' . $this->pp_instance . '" class="' . $this->imagesdiv . '">' . PHP_EOL;	

	
		/**
		* Add images
		*/
		if (!empty ($image_list) ) {
		    $i = 0;
		    foreach ( $image_list as $this_image ) {

			
			/* What does the carousel image link to? */
			$linkurl 		= $this_image['link'];
			$rel 			= '';
			$dsc			= '';
			if ($linkurl === '') {
				/* We are linking to the popup - use the title and description as the alt text */
				$linkurl = $this_image['large'];
				$rel = ' data-style="pp_lightbox"';
				$alt = ' alt="'.$this_image['title'].'"';
				if ($this_image['desc'] != '') {
					
					$dsc = ' data-description="' . htmlspecialchars(str_replace(array("\r\n", "\r", "\n"), "<br />", $this_image['desc'])) . '"';
				}
			} else {
				/* We are linking to an external url - use the title as the alt text */
				$alt = ' alt="'.$this_image['title'].'"';
			}
			
		
		$output .= '<img src="'.$this_image['small'].'" data-link="'.$linkurl.'"'. $rel . $alt . $dsc . ' />';

		
			/* build separate thumbnail list for users with scripts disabled */
			$noscript .= '<a href="' . $linkurl . '"><img src="' . $this_image['small'] .'" width="100"  alt="'.$this_image['title'].'" /></a>';
			$i++;
			
		    }
		    $this->pp_id ++;
		}
					
		
		$output .= '</div>' . PHP_EOL;
		$output .= '<div id="' . $this->captionsdiv . '_' . $this->pp_instance . '" class="' . $this->captionsdiv . '"';
		if ($options['nocaptions']) $output .= ' style="display:none !important;"';
		$output .= '></div>' . PHP_EOL;
		$output .= '<div id="' . $this->scrollbardiv . '_' . $this->pp_instance . '" class="' . $this->scrollbardiv;
		if ($slcolor == "black") $output .= ' black';
		$output .= '"';
		if ($options['noslider']) $output .= ' style="display:none !important;"';
		$output .= '><div id="' . $this->sliderdiv . '_' . $this->pp_instance . '" class="' . $this->sliderdiv . '">' . PHP_EOL;
		$output .= '</div>';
		$output .= '</div>' . PHP_EOL;
		$output .= $noscript . '</div></noscript></div>';	

		return $js . $output;
		
		

	}

	function images_from_library ($attr, $options) {
		/*
		** Generate a list of the images we are using from the Media Library
		*/
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}

		/*
		** Standard gallery shortcode defaults that we support here	
		*/
		global $post;
		extract(shortcode_atts(array(
				'order'      => 'ASC',
				'orderby'    => 'menu_order ID',
				'id'         => $post->ID,
				'include'    => '',
				'exclude'    => '',
				'mediatag'	 => '',	// corresponds to Media Tags plugin by Paul Menard
		  ), $attr));
	
		$id = intval($id);
		if ( 'RAND' == $order )
			$orderby = 'none';

		if ( !empty($mediatag) ) {
			$mediaList = get_attachments_by_media_tags("media_tags=$mediatag&orderby=$orderby&order=$order");
			$attachments = array();
			foreach ($mediaList as $key => $val) {
				$attachments[$val->ID] = $mediaList[$key];
			}
		} elseif ( !empty($include) ) {
			$include = preg_replace( '/[^0-9,]+/', '', $include );
			$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
			$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		}

		$image_list = array();
		foreach ( $attachments as $id => $attachment ) {
			$small_image = wp_get_attachment_image_src($id, "medium");
			$large_image = wp_get_attachment_image_src($id, "large");

			/* If the media description contains an url and the link option is enabled, use the media description as the linkurl */
			$link_url = '';
			if (($options['link'] == 'true') && 
				((substr($attachment->post_content,0,7) == 'http://') || (substr($attachment->post_content,0,8) == 'https://'))) {
				$link_url = $attachment->post_content;
			}

			$image_link = get_post_meta($id, '_pp-image-link', true);
			if (isset($image_link) && ($image_link != '')) $link_url = $image_link;

			$image_list[] = array (
				'small' => $small_image[0],
				'large' => $large_image[0],
				'link'  => $link_url,
				'title' => $attachment->post_title,
				'desc'  => $attachment->post_content,
			);

		}
		return $image_list;
		
	}

	function images_from_dir ($attr, $options) {
		/*
		** Generate the image list by reading a folder
		*/
		$image_list = array();

		$galleries_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $this->get_path($options['gallery_url']);
		if (!file_exists($galleries_path))
			return '';

		/*
		** Gallery directory is ok - replace the shortcode with the image gallery
		*/
		$plugin_url = get_option('siteurl') . "/" . PLUGINDIR . "/" . plugin_basename(dirname(__FILE__)); 			
			
		$gallerypath = $galleries_path . $attr['dir'];
		if (file_exists($gallerypath))
		{	
			$handle = opendir($gallerypath);
			while ($image=readdir($handle)) {
				if (filetype($gallerypath."/".$image) != "dir" && !preg_match('/refl_/',$image)) {
					$pageURL = 'http';
					if (isset($_SERVER['HTTPS']) && ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
					$pageURL .= "://";
					if ($_SERVER["SERVER_PORT"] != "80") {
				   	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
				} else {
				   	$pageURL .= $_SERVER["SERVER_NAME"];
				}
				$imagepath = $pageURL . '/' . $this->get_path($options['gallery_url']) . $attr['dir'] . '/' . $image;
				$image_list[] = array (
					'small' => $imagepath,
					'large' => $imagepath,
					'link'  => '',
					'title' => $image,
					'desc'  => '',
			);
			    }
		//	    $this->pp_id ++;
			}
			closedir($handle);
		}

		return $image_list;
	}


	function getAdminOptions() {
		/*
		** Merge default options with the saved values
		*/
		$use_options = array(	'gallery_url' => '0', 	// Path to gallery folders when not using built in gallery shortcode
						'bgcolor' => '#000000', // Background color defaults to black
						'txcolor' => '#ffffff', // Text color defaults to white
						'slcolor' => 'white',	// Slider color defaults to white
						'link'    => 'false',	// Don't link to image description
						'width'   => '640px',	// Width of containing div
						'height'  => '480px',	// Height of containing div
						'autorotate' => 'off',	// True to enable auto rotation
						'pause' =>	'3000',	// Time to pause between auto rotations
						'samewindow' => false,	// True to open links in same window rather than new window
						'nocaptions' => false,	// True to hide captions in the carousel
						'noslider' => false,	// True to hide the scrollbar
						'defheight' => false	// True to use default value
					);
		$saved_options = get_option($this->adminOptionsName);
		if (!empty($saved_options)) {
			foreach ($saved_options as $key => $option)
				$use_options[$key] = $option;
		}
		
		if ($use_options['defheight'] == 'true')
		{
			$use_options['height'] = '480px';
			}
			

		
		return $use_options;
	}

	function get_path($gallery_url) {
		/*
		** Determine the path to prepend with DOCUMENT_ROOT
		*/
		if (substr($gallery_url, 0, 7) != "http://") return $gallery_url;

		$dir_array = parse_url($gallery_url);
		return $dir_array['path'];
	}

	function addScripts()
	{
		if (!is_admin()) {
			wp_enqueue_style( 'ppgallerycss',  plugins_url('css/screen.css', __FILE__));
			wp_enqueue_script('pp_gallery', plugins_url('js/polaroidplus.js', __FILE__), array('jquery'), '1.7');
		} else {
			wp_enqueue_script('pp_utility_js', plugins_url('js/pp_utility.js', __FILE__));
		}
	}	

	function image_links($form_fields, $post) {
		$form_fields["pp-image-link"] = array(
			"label" => __("WP-Polaroid Plus Link"),
			"input" => "", // this is default if "input" is omitted
			"value" => get_post_meta($post->ID, "_pp-image-link", true),
      	 	"helps" => __("To be used with carousel added via [wp-polaroidplus] shortcode."),
		);
	   return $form_fields;
	}

	function image_links_save($post, $attachment) {
		// $attachment part of the form $_POST ($_POST[attachments][postID])
      	// $post['post_type'] == 'attachment'
		if( isset($attachment['pp-image-link']) ){
			// update_post_meta(postID, meta_key, meta_value);
			update_post_meta($post['ID'], '_pp-image-link', $attachment['pp-image-link']);
		}
		return $post;
	}

	function add_settings_page() {
		add_options_page('WP-Polaroid Plus Options', 'WP-Polaroid Plus', 'manage_options', 'wpPolaroidPlus', array(&$this, 'settings_page'));
	}

	function settings_page() {
		global $options_page;

		if (!current_user_can('manage_options'))
			wp_die(__('Sorry, but you have no permission to change settings.','wp-polaroidplus'));	
			
		$options = $this->getAdminOptions();
		if (isset($_POST['save_ppgallery']) && ($_POST['save_ppgallery'] == 'true') && check_admin_referer('ppgallery_options'))
		{
			echo "<div id='message' class='updated fade'>";	

			/*
			** Validate the background colour
			*/
			if ((preg_match('/^#[a-f0-9]{6}$/i', $_POST['ppgallery_bgc'])) || ($_POST['ppgallery_bgc'] == 'transparent')) {
				$options['bgcolor'] = $_POST['ppgallery_bgc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid background color, not saved.','wp-polaroidplus'). " - " . $_POST['ppgallery_bgc'] ."</b></p>";	
			}

			/*
			** Validate the text colour
			*/
			if (preg_match('/^#[a-f0-9]{6}$/i', $_POST['ppgallery_txc'])) {
				$options['txcolor'] = $_POST['ppgallery_txc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid text color, not saved.','wp-polaroidplus'). " - " . $_POST['ppgallery_txc'] ."</b></p>";	
			}

			/* 
			** Look for disable captions option
			*/
			if (isset ($_POST['ppgallery_nocaptions']) && ($_POST['ppgallery_nocaptions'] == 'nocaptions')) {
				$options['nocaptions'] = true;
			} else {
				$options['nocaptions'] = false;
			}

			/*
			** Validate the slider color
			*/
			if (($_POST['ppgallery_slc'] == 'black') || ($_POST['ppgallery_slc'] == 'white')) {
				$options['slcolor'] = $_POST['ppgallery_slc'];
			} else {
			echo "<p><b style='color:red;'>".__('Invalid slider color, not saved.','wp-polaroidplus'). " - " . $_POST['ppgallery_slc'] ."</b></p>";	
			}

			/* 
			** Look for disable slider option
			*/
			if (isset ($_POST['ppgallery_noslider']) && ($_POST['ppgallery_noslider'] == 'noslider')) {
				$options['noslider'] = true;
			} else {
				$options['noslider'] = false;
			}

			/*
			** Accept the container width
			*/
			$options['width'] = $_POST['ppgallery_width'];
			
			/*
			** Look for the container height
			*/
	//		$options['height'] = $_POST['ppgallery_height'];
			
			if (isset ($_POST['ppgallery_defheight']) && ($_POST['ppgallery_defheight'] == 'defheight')) {
				$options['defheight'] = true;
				$options['height'] = $_POST['height'];
			} else {
				$options['defheight'] = false;
				$options['height'] = $_POST['ppgallery_height'];
			}
		
			
			/* 
			** Look for link to new window option
			*/
			if (isset ($_POST['ppgallery_samewindow']) && ($_POST['ppgallery_samewindow'] == 'same')) {
				$options['samewindow'] = true;
			} else {
				$options['samewindow'] = false;
			}

			/* 
			** Look for auto rotate option
			*/
			if (isset ($_POST['ppgallery_autorotate']) && ($_POST['ppgallery_autorotate'] == 'autorotate')) {
				$options['autorotate'] = 'on';
			} else {
				$options['autorotate'] = 'off';
			}

			/*
			** Accept the pause value
			*/
			$options['pause'] = $_POST['ppgallery_pause'];

			/*
			** Done validation, update whatever was accepted
			*/
			$options['gallery_url'] = trim($_POST['ppgallery_path']);
			update_option($this->adminOptionsName, $options);
			echo '<p>'.__('Settings were saved.','wp-polaroidplus').'</p></div>';	
		}
			
		?>
					
		<div class="wrap">
			
			<h2>WP-Polaroid Plus Settings</h2>
			<form action="options-general.php?page=wpPolaroidPlus" method="post">
	    		<h3><?php echo __('Formatting','wp-polaroidplus'); ?></h3>

	    		<table class="form-table">
				<tr>
					<th scope="row">
					<label for="ppgallery_bgc"><?php echo __('Background color', 'wp-polaroidplus'); ?></label>
					</td>
					<td>
					<input type="text" name="ppgallery_bgc" id="ppgallery_bgc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['bgcolor']; ?>">
					&nbsp;<em>Hex value or "transparent"</em>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<label for="ppgallery_txc"><?php echo __('Text color', 'wp-polaroidplus'); ?></label>
					</td>
					<td>
					<input type="text" name="ppgallery_txc" onkeyup="colorcode_validate(this, this.value);" value="<?php echo $options['txcolor']; ?>">
					&nbsp;<label for="ppgallery_nocaptions">Disable captions: </label>
					<input type="checkbox" name="ppgallery_nocaptions" id="ppgallery_nocaptions" value="nocaptions" <?php if ($options['nocaptions'] == 'true') echo ' CHECKED'; ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
					<label for="ppgallery_txc"><?php echo __('Slider color', 'wp-polaroidplus'); ?></label>
					</td>
					<td>
					<select name="ppgallery_slc">
					<option value="white"<?php if ($options['slcolor'] == 'white') echo ' SELECTED'; echo __('>White', 'wp-polaroidplus'); ?></option>
					<option value="black"<?php if ($options['slcolor'] == 'black') echo ' SELECTED'; echo __('>Black', 'wp-polaroidplus'); ?></option>
					</select>
					&nbsp;<label for="ppgallery_noslider">Disable slider: </label>
					<input type="checkbox" name="ppgallery_noslider" id="ppgallery_noslider" value="noslider" <?php if ($options['noslider'] == 'true') echo ' CHECKED'; ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Container width CSS', 'wp-polaroidplus'); ?>
					</td>
					<td>
					<input type="text" name="ppgallery_width" value="<?php echo $options['width']; ?>"> 
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Container height', 'wp-polaroidplus'); ?>
					</td>
					<td>
					<input type="text" name="ppgallery_height" value="<?php echo $options['height']; ?>"> 
					&nbsp;<label for="ppgallery_defheight">Default value (480px): </label>
					<input type="checkbox" name="ppgallery_defheight" id="ppgallery_defheight" value="defheight" <?php if ($options['defheight'] == 'true') echo ' CHECKED'; ?> />
				</td>
				</tr>
			</table>

	    		<h3><?php echo __('Behaviour','wp-polaroidplus'); ?></h3>
			<p>The images in the carousel will by default link to a Lightbox enlargement of the image. Alternatively, you may specify
a URL to link to each image. This link address should be configured in the image uploader/editor of the Media Library.</p>
	    		<table class="form-table">
				<tr>
					<th scope="row">
					<?php echo __('Open URL links in same window', 'wp-polaroidplus'); ?>
					</td>
					<td>
					<input type="checkbox" name="ppgallery_samewindow" value="same" <?php if ($options['samewindow'] == 'true') echo ' CHECKED'; ?> /> <em>The default is to open links in a new window</em>
					</td>
				</tr>
				
			
				<tr>
					<th scope="row">
					<?php echo __('Enable auto rotation', 'wp-polaroidplus'); ?>
					</td>
					<td>
					<input type="checkbox" name="ppgallery_autorotate" value="autorotate" <?php if ($options['autorotate'] == 'on') echo ' CHECKED'; ?> /> <em>This may be overridden in the shortcode</em>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php echo __('Auto rotation pause', 'wp-polaroidplus'); ?>
					</td>
					<td>
					<input type="text" name="ppgallery_pause" value="<?php echo $options['pause']; ?>"> 
					</td>
				</tr>
			</table>

	    		<h3><?php echo __('Galleries Based on Folders','wp-polaroidplus'); ?></h3>
			  <a style="cursor:pointer;" title="Click for help" onclick="toggleVisibility('detailed_display_tip');">Click to toggle detailed help</a>
			  <div id="detailed_display_tip" style="display:none; width: 600px; background-color: #eee; padding: 8px;
border: 1px solid #aaa; margin: 20px; box-shadow: rgb(51, 51, 51) 2px 2px 8px;">
				<p>You can upload a collection of images to a folder and have WP-Polaroid Plus read the folder and gather the images, without the need to upload through the Wordpress image uploader. Using this method provides fewer features in the gallery since there are no titles, links, or descriptions stored with the images. This is provided as a quick and easy way to display an image carousel.</p>
				<p>The folder structure should resemble the following:</p>
				<p>
- wp-content<br />
&nbsp;&nbsp;&nbsp;- galleries<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- gallery1<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image1.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image2.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image3.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- gallery2<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image4.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image5.jpg<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- image6.jpg</p>

				<p>With this structure you would enter "wp-content/galleries/" as the folder path below.</p>
</div>

	    		<table class="form-table">
	    			<tr>
					<th scope="row">
					<?php echo __('Folder Path','wp-polaroidplus'); ?>	
					</td>
					<td>
					<?php echo __('This should be the path to galleries from homepage root path, or full url including http://.','wp-polaroidplus'); ?>
					<br /><input type="text" size="35" name="ppgallery_path" value="<?php echo $options['gallery_url']; ?>">
					<br /><?php echo __('e.g.','wp-polaroidplus'); ?> wp-content/galleries/
					<br /><?php echo __('Ending slash, but NO starting slash','wp-polaroidplus'); ?>
					</td>
				</tr>
	    			<tr>
					<th scope="row">
					<?php echo __('These folder galleries were found:','wp-polaroidplus'); ?>	
					</th>
					<td>
					<?php
						$galleries_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $this->get_path($options['gallery_url']);
						if (file_exists($galleries_path)) {
							$handle	= opendir($galleries_path);
							while ($dir=readdir($handle))
							{
								if ($dir != "." && $dir != "..")
								{									
									echo "[wp-polaroidplus dir=".$dir."]";
									echo "<br />";
								}
							}
							closedir($handle);								
						} else {
							echo "Gallery path doesn't exist";
						}					
					?>
					</td>
				</tr>
			</table>

			<p class="submit"><input class="button button-primary" name="submit" value="<?php echo __('Save Changes','wp-polaroidplus'); ?>" type="submit" /></p>

			   		

			<input type="hidden" value="true" name="save_ppgallery">
			<?php
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field('ppgallery_options');
			?>
			</form>				

		</div>
		
		<?php			
	}		
}

}

if (class_exists("PPGallery")) {
	$ppgallery = new PPGallery();
}
?>
