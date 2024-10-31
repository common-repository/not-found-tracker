<?php

if (!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

require_once( ABSPATH . 'wp-admin/includes/screen.php' );
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tracker_List_Table
 *
 * @author Gigabyte
 */
class Tracker_List_Table extends WP_List_Table {

    function __construct($args = array()) {
        parent::__construct($args);
    }

    function extra_tablenav($which) {
        if ($which == 'top') {
            echo 'List Of Not Found URL Accessed';
            //$this->search_box(__('Search'), 'url');
        }
    }

    function column_default($item, $column_name) {
        $col = str_replace('col_', '', $column_name);
        switch ($col) {
            case 'ID':

                return $item->$col;
            case 'url':
                $actions = array(
                    'detail' => '<a href="?page=' . $_GET['page'] . '&action=detail&link=' . $item->ID . '">Details</a>'
                );
                return $item->url . $this->row_actions($actions);

            case 'last_access':
                return $item->$col;
            case 'access_count':
                return $item->$col . ' time' . ($item->$col > 1 ? 's' : '');
            default:
                return $item->$col;
        }
    }

    function get_sortable_columns() {
        return array(
            'col_url' => array('url', true)
        );
    }

    function get_hidden_columns() {
        return array(
            'col_ID' => array('ID', true)
        );
    }

    function get_columns() {
        $columns = array(
            //'col_ID' => 'ID',
            'col_url' => 'URL',
            'col_last_access' => 'Last Access',
            'col_access_count' => 'IP Count Access'
        );
        return $columns;
    }

    function prepare_items() {
        $nf_record = new nfrecord();
        $columns = $this->get_columns();
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');
        $get_perpage = get_user_meta($user, $option, true);
        $perpage = (empty($get_perpage) || $get_perpage < 1) ? $screen->get_option('per_page', 'default') : $get_perpage;

        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns(); // array();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $total_items = $nf_record->get_total($nf_record->table_link);
        $items = $nf_record->get_all_data(
                isset($_GET['orderby']) ? $_GET['orderby'] : 'ID', isset($_GET['order']) ? $_GET['order'] : 'ASC', $perpage, isset($_GET['paged']) ? $_GET['paged'] : 1
        );
        $total_page = ceil($total_items / $perpage);
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'total_pages' => $total_page,
            'per_page' => $perpage
        ));
        $this->items = $items;
    }

}
