<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class gukki_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function listTable() {
        $sql = 'SHOW TABLES';
        $query = $this->db->query($sql);
        return $query->result();
    }

    public function getTableSchema($table){
        $sql = "DESCRIBE $table";
        $query = $this->db->query($sql);
        return $query->result();
    }

}
?>
