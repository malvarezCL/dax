<?php
/**
 * Description of DigitalAnalytix
 *
 * @author jvillalobos
 */
class DigitalAnalytix {

    private $parameters = array();
    private $beacon_url = '';
    private $beacon_js = '';

    function __construct() {
        $this->parameters['c1'] = '2';
        $this->parameters['c2'] = get_option('c2');
        $this->parameters['ns_site'] = get_option('ns_site');
        $this->parameters['name'] = null;
        $this->beacon_url = $this->get_proper_dax_url();
        $this->beacon_js = $this->get_proper_dax_url(true);
    }

    function build_admin() {
        if (!current_user_can('manage_options')) {
            wp_die(__('KEEP OUT! Authorized Personnel Only!'));
        }
        echo '<div id="dax_admin_page">';
        echo '<h1>Digital Analytix Web Census</h1>';
        echo '<p>This plugin will integrate the census measurements beacon to send these to Digital Analytix platform, if you have an account with this service you can check the captured measurements at http://dax.comscore.com</p>';
        echo '</div>';
        echo '<form method="post" action="options.php">';
        wp_nonce_field('update-options');
        $c2 = get_option('c2');
        $ns_site = get_option('ns_site');
        $dax_enabled = get_option('dax-enabled') != '' ? 'checked="checked"' : '';
        echo <<<HTML
            <p>____c2:<input type="text" placeholder="comScore identifier" id="c2" value="$c2" tabindex="1" size="30" name="c2"></p>
            <p>ns_site:<input type="text" placeholder="Site identifier" id="ns_site" value="$ns_site" tabindex="2" size="30" name="ns_site"></p>
            <p><label for="dax-enabled">Enable Digital Analytix for this site </label><input type="checkbox" name="dax-enabled" id="dax-enabled" value="true" $dax_enabled /></p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="c2,ns_site,dax-enabled" />
            <input type="submit" class="button-primary" value="Save Changes" />
HTML;
        echo '</form>';
    }

    function build_menu() {
        add_options_page('comScore', 'Digital Analytix', 'manage_options', 'cs-dax-wp-basic-admin', array($this, 'build_admin'));
//      add_menu_page(__('comScore', 'build_admin'), __('comScore', 'cs-dax-wp-basic-admin'), 'manage_options', 'cs-dax-wp-basic-admin', plugin_dir_url(__FILE__).'images/logo.png');
    }

    function print_tag() {
        $params = $this->get_params();
        $dax_inline_tag =
                <<<aScript
<!-- Begin comScore Inline Tag 1.1302.13 -->
<script type="text/javascript">
    var DAxParams = {};
    DAxParams.beacon_url = "$this->beacon_url";
    DAxParams.dax_params = "$params";
    function udm_(e){var t="comScore=",n=document,r=n.cookie,i="",s="indexOf",o="substring",u="length",a=2048,f,l="&ns_",c="&",h,p,d,v,m=window,g=m.encodeURIComponent||escape;if(r[s](t)+1)for(d=0,p=r.split(";"),v=p[u];d<v;d++)h=p[d][s](t),h+1&&(i=c+unescape(p[d][o](h+t[u])));e+=l+"_t="+ +(new Date)+l+"c="+(n.characterSet||n.defaultCharset||"")+"&c8="+g(n.title)+i+"&c7="+g(n.URL)+"&c9="+g(n.referrer),e[u]>a&&e[s](c)>0&&(f=e[o](0,a-8).lastIndexOf(c),e=(e[o](0,f)+l+"cut="+g(e[o](f+1)))[o](0,a)),n.images?(h=new Image,m.ns_p||(ns_p=h),h.src=e):n.write("<","p","><",'img src="',e,'" height="1" width="1" alt="*"',"><","/p",">")};
    udm_('$this->beacon_url$params');
</script>
<noscript><img src="$this->beacon_url$params" height="1" width="1" alt="*" style="display:none"></noscript> 
<!-- End comScore Inline Tag -->
aScript;
        return $dax_inline_tag . PHP_EOL;
    }

    function print_footer() {
        $c2 = $this->get_param('c2');
        $dax_inline_importer =
                <<<aScript
    <!-- Begin comScore Inline Tag 1.1111.15 -->
        <script type="text/javascript" src="$this->beacon_js$c2/ct.js"></script>
    <!-- End comScore Inline Tag -->
aScript;
        echo $dax_inline_importer . PHP_EOL;
    }

    function create_db() {
        /* global $wpdb;
          $table_name = $wpdb->prefix . 'cs_dax_settings';
          $sql = "
          CREATE TABLE $table_name (
          id int(25) NOT NULL AUTO_INCREMENT,
          date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          c2 VARCHAR(20) NOT NULL,
          site VARCHAR(20) NOT NULL,
          enabled BOOLEAN DEFAULT 0 NOT NULL,
          admins BOOLEAN DEFAULT 0 NOT NULL,
          cat_rooted BOOLEAN DEFAULT 0 NOT NULL,
          UNIQUE KEY id (id)
          );";
          require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          dbDelta($sql); */
    }

    function insert_data() {
        /* global $wpdb;
          $table_name = $wpdb->prefix . 'cs_dax_settings';
          $rows_affected = $wpdb->insert($table_name, array('date_created' => current_time('mysql'), 'c2' => '', 'site' => 'your-site', 'enabled' => 0, 'admins' => 0, 'cat_rooted' => 0)); */
    }

    function set_param($label, $value) {
        $this->parameters[$label] = $value;
    }

    function get_param($label) {
        return $this->parameters[$label];
    }

    function get_params() {
        $result = '';
        $count = 1;
        $totalcount = count($this->parameters);
        foreach ($this->parameters as $a => $b) {
            if ($count < $totalcount)
                $result .= "$a=$b&";
            if ($count == $totalcount)
                $result .= "$a=$b";
            $count++;
        }
        return $result;
    }
    
    function get_proper_dax_url($isImportJs = false) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://sb." : "http://b.";
        $domainName = $isImportJs ? "scorecardresearch.com/c2/" : "scorecardresearch.com/p?";
        return $protocol.$domainName;
    }

    function destructor() {
        
    }
}
?>