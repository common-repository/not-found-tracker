<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of nfrecord
 *
 * @author Gigabyte
 */
class nfrecord {

    var $wpdb;
    var $table_prefix;
    var $table_link;
    var $table_ip;
    var $table_relation;

    function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
        $this->table_link = $this->table_prefix . '404_link';
        $this->table_ip = $this->table_prefix . '404_ip';
        $this->table_relation = $this->table_prefix . '404_relation';
    }

    function get_all_data($orderby = 'ID', $order = 'ASC', $perpage = null, $paged = null, $where = array()) {
        $sql = "SELECT * FROM `$this->table_link` ";
        if (is_array($where) && count($where) > 0) {
            foreach ($where as $key_cond => $val_cond) {
                $wh[] = '`' . $key_cond . '`="' . $val_cond . '"';
            };
            $sql .=' WHERE ' . implode(' AND ', $wh) . ' ';
        }
        $sql .=' ORDER BY ' . $orderby . ' ' . $order;
        //paged= page 1, page 2, page 3.
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $sql .=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        return $this->wpdb->get_results($sql);
    }

    function get_ip_data($table_name, $orderby = 'ID', $order = 'ASC', $perpage = null, $paged = null, $where = array()) {

        $sql = "SELECT ip.*,ip_rel.link_ID FROM `$this->table_ip` ip LEFT JOIN `$this->table_relation` ip_rel "
                . "ON ip.ID = ip_rel.ip_ID ";
        if (is_array($where) && count($where) > 0) {
            foreach ($where as $key_cond => $val_cond) {
                $wh[] = '`' . $key_cond . '`="' . $val_cond . '"';
            };
            $sql .=' WHERE ' . implode(' AND ', $wh) . ' ';
        }
        $sql .=' ORDER BY ' . $orderby . ' ' . $order;
        //paged= page 1, page 2, page 3.
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $sql .=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        return $this->wpdb->get_results($sql);
    }

    function get_total($table_name, $condition = array()) {
        $sql = 'SELECT COUNT(*) as total FROM `' . $table_name . '`';
        if (is_array($condition) && count($condition) > 0) {
            foreach ($condition as $key_cond => $val_cond) {
                $wh[] = '`' . $key_cond . '`="' . $val_cond . '"';
            };
            $sql .=' WHERE ' . implode(' AND ', $wh) . ' ';
        }
        $total = $this->wpdb->get_row($sql);
        if (is_null($total)) {
            return 0;
        } else {
            return $total->total;
        }
    }

    function record() {
        $remote_addr = $_SERVER['REMOTE_ADDR'];
        $time = date('Y-m-d H:i:s');
        $link = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $id = $this->find_link_id($link);

        //if link first time not found
        if (!$id) {
            $type = 'insert';
            $data_ip = array(
                'ip' => $remote_addr,
                'time' => $time,
                'count' => 1
            );
            $this->crud($this->table_ip, $data_ip);
            $last_inserted_ip_id = $this->wpdb->insert_id;
            $data = array(
                'url' => $link,
                'last_access' => $time,
                'access_count' => $this->count_access($last_inserted_ip_id)
            );
            $this->crud($this->table_link, $data);
            $last_inserted_link_id = $this->wpdb->insert_id;
            $this->crud($this->table_relation, array('link_ID' => $last_inserted_link_id, 'ip_ID' => $last_inserted_ip_id));
        }
        //if link has been added to database
        else {
            $type = 'update';
            $rel = $this->check_relation($id, $remote_addr);
            if (!is_array($rel)) {
                $data_ip = array(
                    'ip' => $remote_addr,
                    'time' => $time,
                    'count' => 1
                );
                $this->crud($this->table_ip, $data_ip);
                $last_inserted_ip_id = $this->wpdb->insert_id;
                $data_update_link = array(
                    'last_access' => $time,
                    'access_count' => $this->count_access($last_inserted_ip_id)
                );
                $this->crud($this->table_link, $data_update_link, array('ID' => $id), $type);
                $last_inserted_link_id = $this->wpdb->insert_id;
                $this->crud($this->table_relation, array('ip_ID' => $last_inserted_ip_id, 'link_ID' => $id));
            } else {
                $upd = $this->crud($this->table_ip, array('time' => $time, 'count' => $rel['count'] + 1), $rel, $type);
                if ($upd) {
                    $count = $this->count_link_access($id);
                    $this->crud($this->table_link, array('access_count' => $count, 'last_access' => $time), array('ID' => $id), $type);
                }
            }
            //check if ip has been access this address
            //$this->
        }
    }

    function count_link_access($id) {
        $sql = "SELECT SUM(ip.count) AS total FROM `$this->table_ip` ip LEFT JOIN `$this->table_relation` rel ON rel.ip_ID=ip.ID WHERE rel.link_ID='$id'";
        $total = $this->wpdb->get_row($sql);
        if (is_null($total)) {
            return 0;
        } else {
            return $total->total;
        }
    }

    function check_relation($link_id, $ip) {
        $sql = "SELECT `$this->table_ip`.`ip`,`$this->table_ip`.`ID`,`$this->table_ip`.`count` FROM `$this->table_ip` LEFT JOIN `$this->table_relation` " .
                "ON `$this->table_relation`.`ip_ID`=`$this->table_ip`.`ID`" .
                " WHERE `$this->table_relation`.`link_ID`='$link_id' AND `$this->table_ip`.`ip`='$ip'";
        $row = $this->wpdb->get_row($sql);
        if (is_null($row)) {
            return false;
        } else {
            return (array) $row;
        }
    }

    private function update_insert_ip($ID) {
        
    }

    private function count_access($ip_id) {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->table_ip . ' WHERE `ID`="' . $ip_id . '"';
        $total = $this->wpdb->get_row($sql);
        if (is_null($total)) {
            return 0;
        } else {
            return $total->total;
        }
    }

    private function get_last_id($table_name, $id = 'ID') {
        $sql = 'SELECT `' . $id . '` FROM `' . $table_name . '` ORDER BY `' . $id . '` DESC LIMIT 1';
        $row = $this->wpdb->get_row($sql);
        if (is_null($row)) {
            return 1;
        } else {
            return $row->ID + 1;
        }
    }

    private function find_ip_id($ip) {

        $sql = 'SELECT `ID` FROM `' . $this->table_ip . '` WHERE `ip`="' . $ip . '" ';
        $var = $this->wpdb->get_row($sql);
        if (is_null($var)) {
            return false;
        } else {
            return $var->ID;
        }
    }

    private function find_link_id($link) {
        $sql = 'SELECT `ID` FROM `' . $this->table_link . '` WHERE `url`="' . $link . '" ';
        $var = $this->wpdb->get_row($sql);
        if (is_null($var)) {
            return false;
        } else {
            return $var->ID;
        }
    }

    function crud($table_name, $data, $where = array(), $type = 'insert') {
        switch ($type) {
            case 'insert':
                $data_key = array_keys($data);
                $data_value = array_values($data);
                $sql = 'INSERT INTO `' . $table_name . '`';
                $sql .='(`' . implode('`,`', $data_key) . '`)';
                $sql .='VALUES(\'' . implode('\',\'', $data_value) . '\')';
                break;
            case 'update':

                $sql = 'UPDATE `' . $table_name . '`';
                $sql .=' SET ';
                foreach ($data as $key => $val) {
                    $set[] = "`$key`='$val'";
                }
                $sql .=implode(',', $set);
                if (count($where) > 0) {

                    $sql .=' WHERE ';
                    foreach ($where as $key => $val) {
                        $set_w[] = "`$key`='$val'";
                    }
                    $sql .=implode(' AND ', $set_w);
                }
                break;
            default:
                $sql = false;
                break;
        }
        if ($sql) {
            return $this->wpdb->query($sql);
        } else {
            return $sql;
        }
    }

    function delete_table() {
        $sql = 'DROP TABLE ' . $this->table_ip;
        dbDelta($sql, TRUE);
        $sql = 'DROP TABLE ' . $this->table_link;
        dbDelta($sql, TRUE);
        $sql = 'DROP TABLE ' . $this->table_relation;
        dbDelta($sql, TRUE);
    }

    function create_table() {
        $create_table = '';
        $sql = "SHOW TABLES FROM `{$this->wpdb->dbname}` LIKE '{$this->table_link}'";
        $table_exist = $this->wpdb->get_var($sql);
        if (!$table_exist) {
            $create_table = 'CREATE TABLE `' . $this->table_link . '` (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`url` TEXT NULL,
	`last_access` DATETIME NULL DEFAULT NULL,
	`access_count` INT(11) NULL DEFAULT NULL,
        PRIMARY KEY (`ID`)
        )' . (strlen(DB_COLLATE) > 0 ? 'COLLATE=' . DB_COLLATE : '') . ';';

            dbDelta($create_table, TRUE);
        }

        $sql = "SHOW TABLES FROM `{$this->wpdb->dbname}` LIKE '{$this->table_ip}'";
        $table_exist = $this->wpdb->get_var($sql);
        if (!$table_exist) {
            $create_table = 'CREATE TABLE `' . $this->table_ip . '` (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`ip` TEXT NULL,
	`time` DATETIME NULL DEFAULT NULL,
	`count` INT(11) NULL DEFAULT NULL,
        PRIMARY KEY (`ID`)
        )' . (strlen(DB_COLLATE) > 0 ? 'COLLATE=' . DB_COLLATE : '') . ';';
            dbDelta($create_table, TRUE);
        };
        $sql = "SHOW TABLES FROM `{$this->wpdb->dbname}` LIKE '{$this->table_relation}'";
        $table_exist = $this->wpdb->get_var($sql);
        if (!$table_exist) {
            $create_table = 'CREATE TABLE `' . $this->table_relation . '` (
	`link_ID` INT(11) NULL DEFAULT NULL,
	`ip_ID` INT(11) NULL DEFAULT NULL
        )' . (strlen(DB_COLLATE) > 0 ? 'COLLATE=' . DB_COLLATE : '') . ';';
            dbDelta($create_table, TRUE);
        }
    }

}
