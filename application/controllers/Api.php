<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

    }

    public function getAllTableData($table_name)
    {

        $result = $this->db->select('*')
            ->from($table_name)
            ->get();

        echo json_encode(array('status'=>'success', 'result'=>$result->result()));
    }


}