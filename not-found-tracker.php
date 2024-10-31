<?php
/*
  Plugin Name: Not Found Tracker
  Description: Track Not Found address on your website
  Plugin URI: http://wordpress.org/plugins/not-found-tracker
  Author: Masdi
  Author URI: http://profiles.wordpress.org/masdimsd
  Version: 0.1
  License: GPL2
 */

/*

  Copyright (C) 2014  Masdi masdimsd@ymail.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of nftracker
 *
 * @author Masdi
 */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once plugin_dir_path(__FILE__) . 'nfrecord.php';

if (!class_exists('Tracker_List_Table')) {
    require_once plugin_dir_path(__FILE__) . 'Tracker_List_Table.php';
}
if (!class_exists('Ip_List_Table')) {
    require_once plugin_dir_path(__FILE__) . 'Ip_List_Table.php';
}

class nftracker {

    //put your code here
    protected $plugin_name;
    protected $table_prefix;
    protected $menu_slug;
    protected $table_name;
    protected $nfrecord;
    protected $hooked_slug;

    function __construct() {
        $this->nfrecord = new nfrecord();
        $this->menu_slug = 'nftracker';
        $this->plugin_name = 'not-found-tracker.php';
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_submenu'));
            add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);
        }
        add_action('template_redirect', array($this, 'record_data'));
        register_activation_hook(__FILE__, array($this, 'first_install'));
        register_uninstall_hook('uninstall.php', 'uninstall');
    }

    function add_screen_option_page() {
        add_screen_option('per_page', array(
            'label' => 'Links per page',
            'default' => 10,
            'option' => $this->menu_slug . '_perpage')
        );
    }

    function set_screen_option($status, $option, $value) {
        //die($option);
        if ($this->menu_slug . '_perpage' == $option) {
            return $value;
        }

        return $status;
    }

    function add_submenu() {

        $this->hooked_slug = add_management_page('List Of Not Found Access', 'Not Found Access List', 'administrator', $this->menu_slug, array(&$this, 'display_track'));
        add_action('load-' . $this->hooked_slug, array(&$this, 'add_screen_option_page'));
    }

    function display_track() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        switch ($action) {
            case 'detail':
                $my_list_table = new Ip_List_Table(
                        array('singular' => 'wp_nftracker_list',
                    'plural' => 'wp_nftracker_lists',
                    'ajax' => true));

                break;

            default:

                $my_list_table = new Tracker_List_Table(
                        array('singular' => 'wp_nftracker_list',
                    'plural' => 'wp_nftracker_lists',
                    'ajax' => true));
                break;
        }
        ?>
        <div class="wrap">
            <?php
            $my_list_table->prepare_items();
            ?><form action="tools.php?page=<?php echo $this->menu_slug;?>"><?php
            
            $my_list_table->display();
            ?></form>

        </div>
        <?php
    }

    function first_install() {
        $this->nfrecord->create_table();
    }

    function record_data() {
        if (is_404()) {
            $this->nfrecord->record();
        }
    }

}

new nftracker();
