<?php

if (!class_exists('WP_List_Table'))
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

require_once( ABSPATH . 'wp-admin/includes/screen.php' );

class Ip_List_Table extends WP_List_Table {

    function __construct($args = array()) {
        parent::__construct($args);
    }

    function get_columns() {
        $column = array(
            //'col_ID' => 'ID',
            'col_ip' => 'IP',
            'col_time' => 'Last Access',
            'col_count' => 'IP Count Access'
        );
        return $column;
    }

    function get_hidden_columns() {
        return array();
    }
    function extra_tablenav($which) {
        if($which=='top'){
            echo '<a href="?page=nftracker"><< Back</a>';
        }
    }
    function get_sortable_columns() {
        $sortable = array(
            'col_IP' => array('ip', true),
            'col_time' => array('time', true)
        );
        return $sortable;
    }

    function column_default($item, $column_name) {
        $column = str_replace('col_', '', $column_name);
        switch ($column) {
            case 'ID':
                return $item->ID;

                break;

            default:
                return $item->$column;
                break;
        }
    }

    function prepare_items() {
        $nf_record = new nfrecord();
        $user = get_current_user_id();
        $screen = get_current_screen();
        $screen_option = $screen->get_option('per_page', 'option');
        $get_perpage = get_user_meta($user, $screen_option, true);
        $perpage = (empty($get_perpage) || $get_perpage < 1) ? $screen->get_option('per_page', 'default') : $get_perpage;
        $columns = $this->get_columns();
        $hidden_column = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden_column, $sortable);
        $total_items = $nf_record->get_total($nf_record->table_ip);
        $id_link=isset($_GET['link'])?$_GET['link']:'0';
        $items = $nf_record->get_ip_data(
                $nf_record->table_ip, 
                isset($_GET['orderby']) ? $_GET['orderby'] : 'ID', 
                isset($_GET['order']) ? $_GET['order'] : 'ASC', 
                $perpage, isset($_GET['paged']) ? $_GET['paged'] : 1,
                array('ip_rel`.`link_ID'=>$id_link)
        );
        $total_page = ceil($total_items / $perpage);
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $perpage,
            'total_pages' => $total_page
        ));
        $this->items = $items;
    }

    //put your code here
}
