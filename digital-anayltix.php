<?php
/*
 * Plugin Name: Digital Analytix
 * Plugin URI: http://www.comscore.com/dax/
 * Description: Tagging Plugin for DAx
 * Version: 1.0 alpha
 * Author: José Villalobos
 * Author URI: http://www.4web.mx
 * Copyright 2014  José Villalobos (e-mail : jose@4web.mx) 

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
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if(isset($_GET['daxtest']) || true) {

	if(!defined('ABSPATH')) {
		die('No direct access to this file use CMS');
	}
	//require_once(WP_PLUGIN_DIR . "/" . basename(dirname(__FILE__)) . "/classes/");

	// Plugin constants
	define('DAX_PLUGIN_DIR', dirname(__FILE__));
	define('DAX_PLUGIN_URL', plugins_url('digital-analytix'));

	// Empty default class
	// TODO Convert a full class with methods and properties
	class DigitalAnalytix{}
	
	// Digital Analytix Object
	global $dax;
	$dax = new DigitalAnalytix();
	$dax->db_version = "1.0 alpha";
	$dax->c2 = get_option('dax_c2');
	$dax->ns_site = get_option('dax_ns_site');
	$dax->ns_prefix = get_option('dax_ns_prefix');
	$dax->params = array();
	$dax->params['c1'] = '2';
	$dax->params['c2'] = $dax->c2;
	$dax->params['ns_site'] = $dax->ns_site;
	$dax->params['wp_st'] = 'dev'; // change for development or production
	$dax->params['wp_dep'] = '';
	$dax->url = dax_get_protocol() . 'scorecardresearch.com/p?';

	// Sets admin menu and import scripts/styles for it
	add_action('init', 'dax_init');

	// Insert tag in the header
	add_action('wp_head', 'dax_tag_header', 100);	// lower priority to print right at the bottom of <head>

	// Insert import script in footer
	add_action('wp_footer', 'dax_tag_footer', 100); // lower priority to print right at the bottom of <body>
	add_action('wp_enqueue_scripts', 'dax_footer_scripts',100);
}

function dax_init()
{
	add_action('admin_menu', 'dax_plugin_menu');
	add_action('admin_enqueue_scripts', 'dax_plugin_scripts');
	add_action('admin_print_styles', 'dax_plugin_styles');
}

// Setting up menu for CMS
function dax_plugin_menu() 
{
	add_menu_page('Digital Analytix', 'Digital Analytix', 'manage_options', 'digital-analytix', 'dax_default_admin', '', 90);
}

// This will show the layout for admin purposes
function dax_default_admin()
{
	$was_sent = false;
	$ns_site = get_option('dax_ns_site');
	$c2 = get_option('dax_c2');
	$ns_prefix = get_option('dax_ns_prefix');

	// Validate the METHOD to save incoming information
	if($_SERVER['REQUEST_METHOD'] === 'POST') {
		$ns_site = filter_input(INPUT_POST, 'ns_site');
		$c2 = filter_input(INPUT_POST, 'c2');
		$ns_prefix = filter_input(INPUT_POST, 'ns_prefix');

		$up1 = update_option('dax_ns_site', $ns_site);
		$up2 = update_option('dax_c2', $c2);
		$up3 = update_option('dax_ns_prefix', $ns_prefix);
		if($up1 && $up2 && $up3) {
			$was_sent = true;
		}
	}

?>
<div class="wrap">
	<h2>Settings</h2>
	<p>Please fill your information with the provided values comscore contact sent you.</p>
	<div class="dax">
		<?php if($was_sent): ?>
		<p class="bg-success">Your information has been saved!</p>
		<?php endif; ?>
		<form class="form" role="form" method="POST">
			<div class="form-group">
			  <label for="ns_site">Client ns_site</label>
			  <input type="text" class="form-control" id="ns_site" name="ns_site" placeholder="Enter your ns_site" <?php echo $ns_site ? "value='$ns_site'" : ""; ?>>
			</div>
			<div class="form-group">
			  <label for="c2">Client C2</label>
			  <input type="text" class="form-control" id="c2" name="c2" placeholder="Enter your c2" <?php echo $c2 ? "value='$c2'" : ""; ?>>
			</div>
			<div class="form-group">
			  <label for="ns_prefix">Site prefix</label>
			  <input type="text" class="form-control" id="ns_prefix" name="ns_prefix" placeholder="Enter a site prefix" <?php echo $ns_prefix ? "value='$ns_prefix'" : ""; ?>>
			</div>
			<div class="form-group">
			  <button type="submit" class="btn btn-primary">Save</button>
			  <button type="button" class="btn btn-default">Cancel</button>
			</div>
		</form>
	</div>
</div>
<?php 
}
// end of layout for admin interface

function dax_footer_scripts()
{
	$version = '1.0.'.filemtime(DAX_PLUGIN_DIR . '/js/technical.min.js'); 
	wp_enqueue_script('daxtech', DAX_PLUGIN_URL . '/js/technical.min.js', array(), $version, true);
}

function dax_plugin_scripts()
{
	$version = '1.0.'.filemtime(DAX_PLUGIN_DIR . '/js/app.js'); 
	wp_enqueue_script('daxtech', DAX_PLUGIN_URL . '/js/app.js', array('jquery'), $version, true);
}

function dax_plugin_styles()
{
	$version = '1.0.'.filemtime(DAX_PLUGIN_DIR . '/css/bootstrap.css'); 
	wp_enqueue_style('dax', DAX_PLUGIN_URL . '/css/bootstrap.css', array(), $version);
}

function dax_tag_header() {
        global $dax;
        $dax->params = dax_get_data(); // gets additional information for parameters
        $tag = "<!-- Begin comScore Inline Tag 1.1302.13 -->".PHP_EOL;
        $tag .= '<script type="text/javascript">'.PHP_EOL;
        $tag .= 'var dax_obj = {};'.PHP_EOL;
        $tag .= 'dax_obj.beacon_url = "'. $dax->url .'";'.PHP_EOL;
    	$tag .= 'dax_obj.dax_params = "'. dax_params($dax->params) .'";'.PHP_EOL;
    	$tag .= 'function udm_(e){var t="comScore=",n=document,r=n.cookie,i="",s="indexOf",o="substring",u="length",a=2048,f,l="&ns_",c="&",h,p,dv,m=window,g=m.encodeURIComponent||escape;if(r[s](t)+1)for(d=0,p=r.split(";"),v=p[u];d<v;d++)h=p[d][s](t),h+1&&(i=c+unescape(p[d][o](h+t[u])));e+=l+"_t="+ +(new Date)+l+"c="+(n.characterSet||n.defaultCharset||"")+"&c8="+g(n.title)+i+"&c7="+g(n.URL)+"&c9="+g(n.referrer),e[u]>a&&e[s](c)>0&&(f=e[o](0,a-8).lastIndexOf(c),e=(e[o](0,f)+l+"cut="+g(e[o](f+1)))[o](0,a)),n.images?(h=new Image,m.ns_p||(ns_p=h),h.src=e):n.write("<",\'img src="\',e,\'" height="1" width="1" alt="*" style="display:none;"\',">")};'.PHP_EOL;
    	$tag .= 'udm_("'. $dax->url . dax_params($dax->params) .'");'.PHP_EOL;
		$tag .= '</script>'.PHP_EOL;
		$tag .= '<noscript><img src="'. $dax->url . dax_params($dax->params) . '" height="1" width="1" alt="*" style="display:none;"></noscript>'.PHP_EOL;
		$tag .= '<!-- End comScore Inline Tag -->'.PHP_EOL;
        echo $tag;
}

function dax_tag_footer() 
{
	global $dax;
	$tag  = '<!-- Begin comscore cs.js import -->'.PHP_EOL;
	$tag .= '<script type="text/javascript" src="http://b.scorecardresearch.com/c2/'.$dax->c2.'/ct.js"></script>'.PHP_EOL;
	$tag .= '<!-- End comscore cs.js import -->'.PHP_EOL;
	echo $tag;
}

function dax_get_protocol()
{
	$isSecure = false;

	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') 
	{
	    $isSecure = true;
	}	
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') 
	{
	    $isSecure = true;
	}
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
		$isSecure = true;
	}

	return $isSecure ? 'https://sb.' : 'http://b.';
}

function dax_params($params) 
{
	$output = '';
	foreach($params as $key => $value) 
	{
		$output .= $key . '=' . $value . '&';
	}
	return rtrim($output, '&');
}

function dax_get_data()
{
	global $dax;
	$dax->params['name'] = dax_get_name();
	$dax->params['wp_auid'] = dax_get_auid();
	$dax->params['wp_ver'] = dax_wp_version();

	return array_merge($dax->params, dax_get_extra_data());
}

function dax_get_name()
{
	global $dax, $wp_query, $wp, $wp_rewrite;
	global $post, $authordata;

	$c_permalink = trim(get_permalink(), '/');
	$c_baseurl = trim(get_bloginfo('url'), '/');
	$c_redirecturl = trim(filter_input(INPUT_SERVER, 'REDIRECT_URL'), '/');

	if(isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'blog') {
		$name = array();
		$name = array($dax->ns_prefix, 'blog', 'portada');
		return implode($name, '.');
	}

	if(is_home() || is_front_page() || ( $c_permalink == $c_baseurl)) {
		$name = array($dax->ns_prefix, 'inicio', 'portada');
		return implode($name, '.');
	}

	if(is_search()) {
		$name = array($dax->ns_prefix, 'buscador', 'resultados');
		return implode($name, '.');
	}

	if(is_page()) {
		$name = array();
		$name[] = $dax->ns_prefix;

		$page_hierarchy = get_ancestors($wp_query->post->ID, 'page');
        
        if(!empty($page_hierarchy)) {
        	$ancestors = implode($page_hierarchy, ',');
        	$pages = get_posts(array('post__in' => $ancestors));
        	foreach($pages as $page) {
        		for($i = 0; $i < count($pages); $i++) {
        			if($page->ID == $pages[$i]) {
        				$name[] = dax_slugify($page->post_title);
        			}
        		}
        	}
        }

        $name[] = dax_slugify($post->post_title);

        return implode($name, '.');
	}

	if(is_single()) {
		$name = array();
		$name[] = $dax->ns_prefix;

		if($post->post_type !== 'post') {
			$post_type_obj = get_post_type_object($post->post_type);
			$name[] = !isset($post_type_obj->rewrite) ? $post_type_obj->name : $post_type_obj->rewrite['slug'];
		}

		$terms =  get_the_terms($wp_query->post->ID, 'category');

		if(is_array($terms)) {
			$category = array_pop($terms);
		}

		if(!empty($category)) {
			$category_hierarchy = get_ancestors($category->term_id, 'category');
        
	        if(!empty($category_hierarchy)) {
	        	foreach($category_hierarchy as $cat_id) {
	        		$name[] = dax_slugify(get_category($cat_id)->name);
	        	}
	        }
	        $name[] = dax_slugify($category->name);
		}

		$name[] = dax_slugify($post->post_title);

		return implode($name, '.');
	}

	if(is_category()) {
		$name = array();
		$name[] = $dax->ns_prefix;

		if(isset($wp_rewrite->extra_permastructs['category'])) {
			$category_base = explode('/', $wp_rewrite->extra_permastructs['category']['struct']);
			$category_base = array_shift($category_base);
			if(!strpos($category_base, '%')) {
				$name[] = $category_base;
			}
		}

		if(isset($_GET['debug'])) {
			echo '<pre>';
			print_r($category_base);
			echo '</pre>';
		}

		$category = get_category(get_query_var('cat'), false);
		$category_hierarchy = get_ancestors($category->term_id, 'category');
		if(!empty($category_hierarchy)) {
			foreach($category_hierarchy as $cat_id) {
				$name[] = dax_slugify(get_category($cat_id)->name);
			}
		}
		$name[] = dax_slugify($category->name);
		$name[] = 'portada';

		return implode($name, '.');
	}

	if(is_post_type_archive()) {
		$name = array();
		$name[] = $dax->ns_prefix;

		$req_post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : $wp_query->query_vars['post_type'];
		$post_type_obj = get_post_type_object($req_post_type);

		$name[] = !isset($post_type_obj->rewrite) ? dax_slugify($post_type_obj->name) : $post_type_obj->rewrite['slug'];

		$name[] = 'portada';

		return implode($name, '.');
	}

	if(is_archive()) {
		$name = array();
		$name[] = $dax->ns_prefix;

		$tax_obj = get_taxonomy(key($wp_query->query));

		$name[] = !isset($tax_obj->rewrite) ? dax_slugify($tax_obj->name) : $tax_obj->rewrite['slug'];
		$name[] = $wp_query->query[key($wp_query->query)];
		$name[] = 'portada';

		return implode($name, '.');
	}

}

function dax_get_auid()
{
	return is_user_logged_in() ? hash('crc32', get_current_user_id()) : '0';
}

function dax_wp_version()
{
	global $wp_version;
	return $wp_version;
}

function dax_get_extra_data() {
	global $wp_query, $dax;

	$temp_arr = array();
	if(is_search()) {
		$term = html_entity_decode(trim(get_search_query()), ENT_QUOTES);
		$result = $wp_query->found_posts;
        $temp_arr["ns_search_result"] = $result;
		$temp_arr['ns_search_term'] = preg_replace(array("/&/", "/'/", "/\s/"), array("%26", "%27", "%20"), $term);
	}

	if (is_404()) {
        $temp_arr["name"] = "{$dax->ns_prefix}.error.404";
        $temp_arr["ns_http_status"] = "404";
    }

    if(is_paged()) {
    	$temp_arr["wp_pgn"] = $wp_query->query['paged'];
    	$temp_arr["wp_pgm"] = $wp_query->max_num_pages;
    }

	return $temp_arr;
}

function dax_remove_accent($str)
{
  $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
  $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
  return str_replace($a, $b, $str);
}

function dax_slugify($str)
{
  return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/', '/[_]/'),  array('', '-', '', '-'), dax_remove_accent($str)));
}