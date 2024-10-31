<?php
require_once plugin_dir_path(__FILE__).'nfrecord.php';
    function uninstall() {
        nfrecord::delete_table();
    }