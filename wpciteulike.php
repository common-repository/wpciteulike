<?php

/*
 * Plugin Name: wpCiteULike
 * Plugin URI: http://www.mathias-funk.com/projects/wpciteulike
 * Description: wpciteulike enables to add a bibliography maintained with CiteULike.org formatted as HTML to wordpress pages and posts. The input data is the bibtex meta data from CiteULike.org and the output is HTML.
 * Version: 0.7.1
 * Author: Mathias Funk (mattfunk)
 * Author URI: http://www.mathias-funk.com
 */


/*
 * Copyright 2009-2010 Mathias Funk (email : mathias <DOT> funk <AT> gmail <DOT> com)
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 */


/*
 * 
 * This plug-in is based on the great work of Sergio Andreozzi who created the
 * bib2html plug-in for Wordpress. Earlier versions of that plug-in have been
 * improved by Cristiana Bolchini, Patrick Mau&eacute;, Nemo, and Marco Loregian.
 * 
 * With wpCiteULike I decided to derive a specialized plug-in from the bib2html
 * plug-in that focuses on CiteULike.org and also improved other parts. Thanks go to
 * Christoph Bartneck, Evan Karapanos, Jun Hu, Sjriek Alers, and Peter Peters for 
 * brainstorming, suggesting, insisting and testing. :)
 * 
 */

require('process.php');

// register plugin with Wordpress
add_action('wp_head', 'wpciteulike_header');
add_filter('the_content', 'wpciteulike', 1);
add_action('admin_menu', 'wpciteulike_register_settings');
add_action('admin_menu', 'wpciteulike_add_options');

function wpciteulike_register_settings()
{
	register_setting( 'wpciteulike_display_options', 'wpciteulike_bibliography_style' );
	register_setting( 'wpciteulike_display_options', 'wpciteulike_html_cache' );
	register_setting( 'wpciteulike_display_options', 'wpciteulike_title_link' );
	register_setting( 'wpciteulike_display_options', 'wpciteulike_reset_cache' );
	register_setting( 'wpciteulike_display_options', 'wpciteulike_reset_html_cache' );
	register_setting( 'wpciteulike_display_options', 'wpciteulike_css_style' );
}

function wpciteulike_add_options()
{
	add_options_page('WP CiteULike Settings', 'wpCiteULike', 'manage_options', 'wpCiteULike', 'wpciteulike_display_options');
}

include(dirname(__FILE__).'/options.php');


?>
