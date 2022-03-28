<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $login_status = $this->session->userdata('admin_login_status');
        if ($login_status != true)
            redirect(base_url('admin'));

        $this->load->model('Admin_Logger');
    }

    public function index()
    {
        $data['presentations'] = $this->getPresentationListArray();
        $data['new_uploads'] = $this->getAllPresentationsWithNewUploads();
        $assigned_ids = array();
        $session_dates = array();
        $session_names = array();
        $presentation_titles = array();
        foreach ($data['presentations'] as $presentation){
            if($presentation->assigned_id !== null){
                $assigned_ids[] = $presentation->assigned_id;
                $session_dates[] = $presentation->presentation_date;
                $session_names[] = $presentation->session_name;
                $presentation_titles[] = $presentation->name;
            }
        }

         array_multisort($assigned_ids, $session_dates, $session_names, $presentation_titles, SORT_ASC);
        $session_dates = array_unique($session_dates);
        $session_names = array_unique($session_names);
        $presentation_titles = array_unique($presentation_titles);
        asort($presentation_titles);
        asort($session_names);
        asort($session_dates);
        $data['assigned_ids'] = $assigned_ids;
        $data['session_dates'] = $session_dates;
        $data['session_names'] = $session_names;
        $data['presentation_titles'] = $presentation_titles;
        $this->load->view('admin/head');

        $this->load->view('admin/dashboard', $data);

        $this->load->view('admin/models/change-password');
        $this->load->view('admin/models/files');
        $this->load->view('admin/models/load-presentations');
        $this->load->view('admin/models/create-presentation');

        $this->load->view('admin/foot');
    }

    public function getPresentationList()
    {

        $this->db->select("p.*, s.name as session_name,s.id as session_id, pr.presenter_id, CONCAT(pr.first_name, ' ', pr.last_name) as presenter_name, pr.email as email, rm.name as room_name, rm.id as room_id");
        $this->db->from('presentations p');
        $this->db->join('sessions s', 's.id = p.session_id');
        $this->db->join('presenter pr', 'pr.presenter_id = p.presenter_id');
        $this->db->join('room rm', 'p.room_id = rm.id');
        $this->db->order_by('p.created_on', 'DESC');
        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            foreach ($result->result() as $row)
                $row->uploadStatus = $this->checkUploadStatus($row->id);

            echo json_encode(array('status'=>'success', 'data'=>$result->result()));
            return;
        } else {
            echo json_encode(array('status'=>'error', 'msg'=>'Unable to load your presentations data'));
            return;
        }
    }

    public function getUploadedFiles($user_id, $presentation_id, $room_id)
    {
        $this->db->select('*');
        $this->db->from('uploads');
        $this->db->where('presenter_id', $user_id);
        $this->db->where('presentation_id', $presentation_id);
        $this->db->where('deleted', 0);
//        $this->db->where('room_id', $room_id);

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            echo json_encode(array('status'=>'success', 'msg'=>'Files are uploaded', 'files'=>$result->result()));
        }else{
            echo json_encode(array('status'=>'error', 'msg'=>'No files uploaded yet'));
        }

        return;
    }

    private function checkUploadStatus($presentation_id)
    {
        $this->db->select('*');
        $this->db->from('uploads');
        $this->db->where('presentation_id', $presentation_id);
        $this->db->where('deleted', 0);

        $result = $this->db->get();

        if ($result->num_rows() > 0)
            return $result->num_rows();

        return false;
    }

    public function openFile($file_id)
    {
        $login_status = $this->session->userdata('admin_login_status');
        if ($login_status != true)
        {
            echo 'You are not logged in.';
            return;
        }

        $this->db->select('*');
        $this->db->from('uploads');
        $this->db->where('id', $file_id);
        //$this->db->where('deleted', 0);

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            $this->update_downloaded_uploads($file_id);
            $this->Admin_Logger->log("Downloaded", null, null, $file_id);

            $file = FCPATH.$result->row()->file_path;
            $new_filename = $result->row()->name;

            header("Content-Type: {$result->row()->format}");
            header("Content-Length: " . filesize($file));
            header('Content-Disposition: attachment; filename="' . $new_filename . '"');
            readfile($file);

        }else{
            echo 'Either this file does not exist or you are not authorized to open it.';
        }

        return;
    }

    public function activatePresentation($presentation_id)
    {
        $this->db->set('active', 1);
        $this->db->where('id', $presentation_id);
        $this->db->update('presentations');

        if ($this->db->affected_rows() > 0)
        {
            $this->Admin_Logger->log("Activated", null, $presentation_id);

            echo json_encode(array('status'=>'success', 'msg'=>'Presentation activated'));

        }else{
            echo json_encode(array('status'=>'error', 'msg'=>'Database error'));
        }
    }

    public function disablePresentation($presentation_id)
    {
        $this->db->set('active', 0);
        $this->db->where('id', $presentation_id);
        $this->db->update('presentations');

        if ($this->db->affected_rows() > 0)
        {
            $this->Admin_Logger->log("Disabled", null, $presentation_id);

            echo json_encode(array('status'=>'success', 'msg'=>'Presentation disabled'));

        }else{
            echo json_encode(array('status'=>'error', 'msg'=>'Database error'));
        }
    }

    public function loadPresentations()
    {

        $allowed_column_names = array(
            'A'=>'Assigned.ID',
            'B'=>'Abstract.ID',
            'C'=>'Session.Name',
            'D'=>'Session.Full.Name',
            'E'=>'Presentation.Title',
            'F'=>'PA.email',
            'G'=>'Name.Prefix',
            'H'=>'PA.Firstname',
            'I'=>'PA.Lastname',
            'J'=>'Room',
            'K'=>'Session.Date',
            'L'=>'Session.Start.Time',
            'M'=>'Session.End.Time',
            'N'=>'Presentation.Start.Time',
        );

        $required_column_names = array(
            'C'=>'Session.Name',
            'D'=>'Session.Full.Name',
            'E'=>'Presentation.Title',
            'F'=>'PA.email',
            'H'=>'PA.Firstname',
            'I'=>'PA.Lastname',
            'J'=>'Room',
//            'J'=>'Presentation.Date',
//            'K'=>'Session.Start.Time',
//            'L'=>'Session.End.Time',
//            'M'=>'Presentation.Start.Time',
        );

        $param_column_index = array(
            'assigned_id'=>'A',
            'email'=>'F',
            'name_prefix'=>'G',
            'first_name'=>'H',
            'last_name'=>'I',
            'session_name'=>'C',
            'session_full_name'=>'D',
            'presentation_name'=>'E',
            'presentation_date'=>'K',
            'room'=>'J',
            'session_start_time'=>'L',
            'session_end_time'=>'M',
            'presentation_start'=>'N',
        );

        $admin_id = $_SESSION['user_id'];

        if (!isset($_FILES['file']['name']))
        {
            echo json_encode(array('status'=>'failed', 'msg'=>'File is required'));
            return;
        }

        $file = $_FILES['file'];

        $this->load->library('excel');

        $objPHPExcel = PHPExcel_IOFactory::load($file['tmp_name']);


        /** Save file for logging */
        $unique_file_name = date("Y-m-d_H:i:s").'.'.pathinfo($file["name"])['extension'];
        move_uploaded_file($file["tmp_name"], FCPATH.'upload_system_files/data_load_files/'.$unique_file_name);
        $this->Admin_Logger->log("Data load initiated", $file['name']." ($unique_file_name)");


        $cell_collection = $objPHPExcel->getActiveSheet()->getCellCollection();

        /** @var array $cell
         * Get the data from spreadsheet file
         */
        foreach ($cell_collection as $cell)
        {
            $column = $objPHPExcel->getActiveSheet()->getCell($cell)->getColumn();
            $row = $objPHPExcel->getActiveSheet()->getCell($cell)->getRow();
            $data_value = $objPHPExcel->getActiveSheet()->getCell($cell)->getValue();

            if ($row == 1) {
                $header[$column] = $data_value;
            } else {
                $rows[$row][$column] = $data_value;
            }
        }

        foreach ($allowed_column_names as $columnIndex => $column_name)
        {
            /** @var array $header */
            if ($header[$columnIndex] != $column_name)
            {
                $this->Admin_Logger->log("Data load error", "Column {$columnIndex} is not {$column_name} in the row 1");
                echo json_encode(array('status'=>'failed', 'msg'=>"Column {$columnIndex} is not {$column_name} in the row 1", 'updatedPresentations'=>0, 'createdPresentations'=>0));
                return;
            }
        }

        $this->db->trans_begin();

        $duplicateRows = 0;
        $createdPresentations = 0;
        /** @var array $rows */
        foreach ($rows as $row => $row_columns)
        {

//            print_r(isset($row_columns['K'])?'hi':'no');exit;
            /** Empty column value catcher */
            foreach ($required_column_names as $columnIndex => $column_name)
            {
                if ($row_columns[$columnIndex] == '')
                {
                    $this->db->trans_rollback();
                    $this->Admin_Logger->log("Data load error", "{$column_name} (Column {$columnIndex}) is empty in the row {$row}");
                    echo json_encode(array('status'=>'failed', 'msg'=>"{$column_name} (Column {$columnIndex}) is empty in the row {$row}", 'updatedPresentations'=>0, 'createdPresentations'=>0));
                    return;
                }
            }

            $name_prefix = (isset($row_columns['F']))?str_replace('\'', "\`", $row_columns[$param_column_index['name_prefix']]):'';
            $first_name = str_replace('\'', "\`", $row_columns[$param_column_index['first_name']]);
            $last_name = str_replace('\'', "\`", $row_columns[$param_column_index['last_name']]);
            $email = str_replace('\'', "\`", $row_columns[$param_column_index['email']]);
            $password = str_replace('\'', "\`", $first_name);
            $session_name = str_replace('\'', "\`", $row_columns[$param_column_index['session_name']]);
            $session_full_name = str_replace('\'', "\`", $row_columns[$param_column_index['session_full_name']]);
            $presentation_name = str_replace('\'', "\`", $row_columns[$param_column_index['presentation_name']]);
            $room_name = str_replace('\'', "\`", $row_columns[$param_column_index['room']]);
            $created_date_time = date("Y-m-d H:i:s");

            $start_time = 'null';
            $end_time = 'null';
            $presentation_date = 'null';
            $assigned_id = 'null';
            $presentation_start = 'null';

            if(isset($row_columns[$param_column_index['presentation_date']]))
            {
                $presentation_date = gmdate('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP(str_replace('\'', "\`", $row_columns[$param_column_index['presentation_date']])));
                $presentation_date = ($presentation_date == '')? 'null':"'{$presentation_date}'";
            }

            if(isset($row_columns[$param_column_index['presentation_start']]))
            {
                $presentation_start = gmdate('H:i:s', PHPExcel_Shared_Date::ExcelToPHP(str_replace('\'', "\`", $row_columns[$param_column_index['presentation_start']])));
                $presentation_start = ($presentation_start == '')? 'null':"'{$presentation_start}'";
            }

            if(isset($row_columns[$param_column_index['session_start_time']]))
            {
                $start_time = gmdate('H:i:s', PHPExcel_Shared_Date::ExcelToPHP(str_replace('\'', "\`", $row_columns[$param_column_index['session_start_time']])));
                $start_time = ($start_time == '')?'null': "'{$start_time}'";
            }
            if(isset($row_columns[$param_column_index['session_end_time']]))
            {
                $end_time = gmdate('H:i:s', PHPExcel_Shared_Date::ExcelToPHP(str_replace('\'', "\`", $row_columns[$param_column_index['session_end_time']])));
                $end_time = ($end_time == '')?'null': "'{$end_time}'";
            }

            if(isset($row_columns[$param_column_index['assigned_id']]))
            {
                $assigned_id = (str_replace('\'', "\`", $row_columns[$param_column_index['assigned_id']]));
                $assigned_id = ($assigned_id == '')?'null': "'{$assigned_id}'";
            }

            $exists = $this->checkDuplicate($email, $session_name, $presentation_name, $room_name);

            if ($exists)
            {
                $desc = json_encode($exists);
                $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Ignored load item', '{$desc}', '{$exists->presentation_id}', '{$exists->presenter_id}', '{$created_date_time}')");
                $duplicateRows = $duplicateRows+1;

            }else{

                try{
                    $emailExists = $this->checkEmailExists($email);
                    if ($emailExists)
                    {
                        $presenter_id = $emailExists;
                    }else{
                        $this->db->query("INSERT INTO `presenter`(`name_prefix`, `first_name`, `last_name`, `email`, `password`, `creation_date`) VALUES ('{$name_prefix}', '{$first_name}','{$last_name}','{$email}','{$password}','{$created_date_time}')");
                        $presenter_id = $this->db->insert_id();
                        $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Created user', null, null, '{$presenter_id}', '{$created_date_time}')");
                    }

                    $sessionExists = $this->checkSessionExists($session_name);
                    if ($sessionExists)
                    {
                        $session_id = $sessionExists;
                    }else{
                        $this->db->query("INSERT INTO `sessions`(`name`, `full_name`) VALUES ('{$session_name}', '{$session_full_name}')");
                        $session_id = $this->db->insert_id();
                        $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Created session', null, null, '{$session_id}', '{$created_date_time}')");
                    }

                    $roomExist = $this->checkRoomExist($room_name);
                    if($roomExist){
                        $room_id = $roomExist;
                    }else{
                        $this->db->query("INSERT INTO `room`(`name`) VALUES ('{$room_name}')");
                        $room_id = $this->db->insert_id();
                        $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Created room', null, null, '{$room_id}', '{$created_date_time}')");
                    }

                    $presentationExists = $this->checkPresentationExists($presentation_name, $session_id, $presenter_id);
                    if ($presentationExists)
                    {
                        $desc = json_encode($presentationExists);
                        $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Ignored load item', '{$desc}', '{$presentationExists->id}', '{$presentationExists->presenter_id}', '{$created_date_time}')");
                        $duplicateRows = $duplicateRows+1;
                    }else{
                        $this->db->query("INSERT INTO `presentations`(`name`, `session_id`, `presenter_id`, `created_on`, `presentation_date`, `start_time`, `end_time`, `room_id`, `presentation_start`, `assigned_id`) VALUES ('{$presentation_name}','{$session_id}','{$presenter_id}','{$created_date_time}', ".$presentation_date.", ".$start_time.", ".$end_time.", ".$room_id.", ".$presentation_start.", ".$assigned_id.")");
                        $presentation_id = $this->db->insert_id();
                        $this->db->query("INSERT INTO `admin_logs`(`admin_id`, `log_name`, `log_desc`, `ref_presentation_id`, `other_ref`, `date_time`) VALUES ( '{$admin_id}', 'Created presentation', null, '{$presentation_id}', null, '{$created_date_time}')");
                        $createdPresentations = $createdPresentations+1;
                    }

                }catch (Exception $e)
                {
                    $this->db->trans_rollback();
                    $this->Admin_Logger->log("Data load error", $e->getMessage());
                    echo json_encode(array('status'=>'failed', 'msg'=>'Query Error: '.$e->getMessage(), 'updatedPresentations'=>0, 'createdPresentations'=>0));
                    return;
                }


            }


        }

        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            $this->Admin_Logger->log("Data load error", json_encode($this->db->error()));
            echo json_encode(array('status'=>'failed', 'msg'=>'Query Transaction Error: Unable to load the data', 'updatedPresentations'=>0, 'createdPresentations'=>0));
            return;
        }
        else
        {
            $this->db->trans_commit();
            $this->Admin_Logger->log("Data load success", json_encode(array('updatedPresentations'=>0, 'createdPresentations'=>$createdPresentations, 'duplicatedRows'=>$duplicateRows)));
            echo json_encode(array('status'=>'success', 'msg'=>'Data loaded successfully', 'updatedPresentations'=>0, 'createdPresentations'=>$createdPresentations, 'duplicatedRows'=>$duplicateRows));
            return;
        }

        return;
    }

    private function checkDuplicate($email, $session_name, $presentation_name, $room_name)
    {
        $this->db->select('p.presenter_id, pr.id as presentation_id, s.id as session_id')
            ->from('presentations pr')
            ->join('presenter p', "p.presenter_id = pr.presenter_id")
            ->join('sessions s', "s.id = pr.session_id")
            ->join('room r', 'pr.room_id = r.id')
            ->where('p.email', "$email")
            ->where('s.name', "$session_name")
            ->where('r.name', "$room_name")
            ->where('pr.name', "$presentation_name");

        $result = $this->db->get();

        if ($result->num_rows() > 0)
            return $result->row();

        return false;
    }

    private function checkEmailExists($email)
    {
        $this->db->select('presenter_id')
            ->from('presenter')
            ->where('email', "$email");

        $result = $this->db->get();

        if ($result->num_rows() > 0)
            return $result->row()->presenter_id;

        return false;
    }

    private function checkSessionExists($session_name)
    {
        $this->db->select('id')
            ->from('sessions')
            ->where('name', "$session_name");

        $result = $this->db->get();

        if ($result->num_rows() > 0)
            return $result->row()->id;

        return false;
    }

    private function checkRoomExist($room_name){
        $this->db->select('id')
            ->from('room')
            ->where('name', "$room_name");

        $result = $this->db->get();

        if ($result->num_rows() > 0)
            return $result->row()->id;

        return false;
    }

    private function checkPresentationExists($presentation_name, $session_id, $presenter_id)
    {
        $query = $this->db->query("select id, name, session_id, presenter_id from presentations where name='{$presentation_name}' and session_id='{$session_id}' and presenter_id='{$presenter_id}'");

        if ($query->num_rows() > 0)
            return $query->result_object()[0];

        return false;
    }

//    private function check_upload_label($label){
//        $this->db->select('*')
//            ->from('upload_label')
//            ->where('name', $label)
//            ;
//        $result = $this->db->get();
//        if($result->num_rows() > 0){
//            return $result->result()[0];
//        }
//    }

    public function presentationToCsv(){
        $this->db->select('p.id,CONCAT(pr.first_name ," ", pr.last_name) as PresenterName, pr.email as email, l.name as LabelName, s.name as Category, p.name as PresentationName')
            ->from('presentations p ')
            ->join('upload_label l', 'p.label=l.id', 'left')
            ->join('sessions s', 'p.session_id = s.id', 'left')
            ->join('presenter pr', 'p.presenter_id = pr.presenter_id', 'left')
        ;
        $result = $this->db->get();

        if($result->num_rows() > 0){
            $filename = 'PresentationExport'.date('Y-m-d').'.csv';

            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename = $filename");
            header("Content-Type: application/csv;");

            $file = fopen('php://output', 'w');
            $header = array("id", "Presenter", "Email", "Label", "Category", "Presentation Title", "Status");
            fputcsv($file, $header);
            $data_array= array();
            foreach($result->result_array() as $data){
                $data['get_uploads'] = $this->get_uploads($data['id']);
                fputcsv($file, $data);
            }
            fclose($file);
            exit;
        }else{
            return '';
        }

    }

    function get_uploads($presentation_id){
        $this->db->select('count(*) as count')
            ->from('uploads')
            ->where('presentation_id', $presentation_id)
        ;
        $result = $this->db->get();
        if($result->num_rows() > 0){
            if($result->result()[0]->count <= 0 ){
                return 'No Data/File uploaded';
            }else{
                return $result->result()[0]->count.' Data/File uploaded';
            }

        }
    }

    public function get_presenter(){
        $presenters =  $this->db->select('*')
            ->from('presenter')
            ->get();

        if($presenters->num_rows()>0)
            echo json_encode($presenters->result());
        else
            echo json_encode('error');
    }

    public function save_presentation(){
        $post = $this->input->post();

        $presentation_field = array(
            'name'=> $post['presentation_title'],
            'presenter_id'=> $post['presenter_id'],
            'presentation_date'=> $post['presentation_date'],
            'start_time'=> $post['session_start'],
            'end_time'=> $post['session_end'],
            'created_on'=> date('Y-m-d H:i:s'),
            'presentation_start'=> date($post['presentation_start']),
            'assigned_id'=> ($post['assigned_id']),
        );

        if ($this->checkRoom($post['room_name'])){
            $presentation_field['room_id'] = $this->checkRoom($post['room_name']);
        }else{
            $this->db->insert('room', array('name'=>$post['room_name']));
            $presentation_field['room_id']=$this->db->insert_id();
        }

        if($this->checkSessionExist($post['session_name'], $post['session_full_name'])){
            $presentation_field['session_id'] = $this->checkSessionExist($post['session_name'], $post['session_full_name']);

        }else{
            $this->db->insert('sessions', array('name'=>$post['session_name'], 'full_name'=>$post['session_full_name']));
            $presentation_field['session_id'] = $this->db->insert_id();
        }


//        print_r($presentation_field);exit;
        $this->db->insert('presentations', $presentation_field);
        if($this->db->affected_rows()>0){
            echo json_encode('success');
        }else
            echo json_encode('error');

    }

    function checkRoom($room_name){
        $room_exist = $this->db->select('id')->from('room')->like('name', $room_name)->get();
        if($room_exist->num_rows()>0){
            return $room_exist->row()->id;
        }else{
            return false;
        }
    }

    function checkSessionExist($session_name, $session_full_name){
        $session_exist = $this->db->select('id')->from('sessions')->like('name', $session_name)->like('full_name', $session_full_name)->get();

        if($session_exist->num_rows()>0){
            return $session_exist->row()->id;
        }
        else{
            return false;
        }
    }


    public function getPresentationById()
    {
        $presentation_id = $this->input->post('presentation_id');

        $this->db->select("p.*, s.name as session_name, s.full_name as session_full_name, pr.presenter_id, CONCAT(pr.first_name, ' ', pr.last_name) as presenter_name, pr.email as email, rm.name as room_name, rm.id as room_id");
        $this->db->from('presentations p');
        $this->db->where('p.id', $presentation_id);
        $this->db->join('sessions s', 's.id = p.session_id');
        $this->db->join('presenter pr', 'pr.presenter_id = p.presenter_id');
        $this->db->join('room rm', 'p.room_id = rm.id');
        $this->db->order_by('p.created_on', 'DESC');
        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            foreach ($result->result() as $row)
                $row->uploadStatus = $this->checkUploadStatus($row->id);

            echo json_encode(array('status'=>'success', 'data'=>$result->result()), JSON_PRETTY_PRINT);
            return;
        } else {
            echo json_encode(array('status'=>'error', 'msg'=>'Unable to load your presentations data'));
            return;
        }
    }

    public function update_presentation($presentation_id){
        $post = $this->input->post();
//        print_r($post);exit;
        $presentation_field = array(
            'name'=>$post['presentation_title'],
            'presenter_id'=>$post['presenter_id'],
            'presentation_date'=> $post['presentation_date'],
            'updated_on'=>date('Y-m-d H:i:s'),
            'presentation_start'=>$post['presentation_start'],
            'start_time'=>$post['session_start'],
            'end_time'=>$post['session_end'],
            'assigned_id'=> ($post['assigned_id']),
        );



        if ($this->checkRoom($post['room_name'])){
            $presentation_field['room_id'] = $this->checkRoom($post['room_name']);
        }else{
            $this->db->insert('room', array('name'=>$post['room_name']));
            $presentation_field['room_id']=$this->db->insert_id();
        }

        if($this->checkSessionExist($post['session_name'], $post['session_full_name'])){
            $presentation_field['session_id'] = $this->checkSessionExist($post['session_name'], $post['session_full_name']);
        }else{
            $this->db->insert('sessions', array('name'=>$post['session_name'], 'full_name'=>$post['session_full_name']));
            $presentation_field['session_id'] = $this->db->insert_id();
        }

        $this->db->where('id', $presentation_id);
        $this->db->update('presentations', $presentation_field);
        if($this->db->affected_rows()>0){
            echo json_encode('success');
        }else
            echo json_encode('failed');

    }

    public function download_batch_by_presentation($presentation_id, $room_id, $user_id){
        $uploads = $this->db->select('*')
            ->from('uploads')
            ->where('presentation_id', $presentation_id)
            ->where('room_id', $room_id)
            ->where('presenter_id', $user_id)
            ->where('deleted', '0')
            ->get();


        if($uploads->num_rows()>0){
            echo json_encode(array('msg'=>'success', 'files'=>$uploads->result()));
        }else{
            echo json_encode(array('msg'=>'error', 'files'=>$uploads->result()));
        }
    }

    public function check_downloaded_uploads($file_id){
        $downloaded = $this->db->select('*')
            ->from('download_status')
            ->where('uploads_id', $file_id)
            ->where('admin_id',  $_SESSION['user_id'])
            ->where('download_status', 1)
            ->get();

        if($downloaded->num_rows()>0){
            echo json_encode(array('msg'=>'success', 'status'=>$downloaded->result(), 'file_id'=>$file_id));
        }else{
            echo json_encode(array('msg'=>'empty', 'status'=>$downloaded->result(), 'file_id'=>$file_id));
        }

    }

    public function update_downloaded_uploads($file_id){
        $result = $this->db->select('*')
            ->from('download_status')
            ->where('uploads_id', $file_id)
            ->where('admin_id', $_SESSION['admin_id'])
            ->get();

        if(!$result->num_rows()>0){
            $this->db->insert('download_status', array('uploads_id'=>$file_id, 'admin_id'=>$_SESSION['user_id'], 'download_status'=>1));
        }
    }



    function getDownloadsCount($presentation_id){
        $data = $this->db->select('*')
            ->from('download_status ds')
            ->join('uploads u', 'ds.uploads_id = u.id')
            ->where('admin_id', $_SESSION['user_id'])
            ->where('presentation_id', $presentation_id)
            ->get();

        if($data->num_rows()>0)
            return COUNT($data->result());
        else{
            return '0';
        }
    }

    function getUploadsCount($presentation_id){
        $data = $this->db->select('*')
            ->from('uploads')
            ->where('deleted', 0)
            ->where('presentation_id', $presentation_id)
            ->get();

        if(COUNT($data->result())){
            $uploadsCount = COUNT($data->result());
            $downloadsCount= $this->getDownloadsCount($presentation_id);
            $undownloadedFile = $uploadsCount-$downloadsCount;

            if($uploadsCount>0){
                echo json_encode(array('status'=>'1', 'upload_count'=>$uploadsCount, 'undownloaded'=>$undownloadedFile));
            }else{
                echo json_encode($uploadsCount);
            }
        }


    }

    function getUploadsCountDt($presentation_id)
    {
        $data = $this->db->select('*')
            ->from('uploads')
            ->where('deleted', 0)
            ->where('presentation_id', $presentation_id)
            ->get();

        if (COUNT($data->result())) {
            $uploadsCount = COUNT($data->result());
            $downloadsCount = $this->getDownloadsCount($presentation_id);
            $undownloadedFile = $uploadsCount - $downloadsCount;

            if ($uploadsCount > 0) {
                return $undownloadedFile;
            }
        }
        return '';
    }

    function getAllPresentationsWithNewUploads()
    {
        $data = $this->db->select('*')
            ->from('uploads')
            ->where('deleted', 0)
            ->get();
        $array = array();
        foreach ($data->result() as $upload_data){
                $downloadsCount = $this->getDownloadsCount($upload_data->presentation_id);
                if(COUNT($upload_data) > $downloadsCount){
                    $array[] = $upload_data->presentation_id;
            }
        }
        return (implode('-',$array));

    }

    public function download_checked_presentation_zip(){
        $presentationIds = $this->input->post('checkedPresentationIds');
        if(!$presentationIds){
            echo json_encode(array('status'=>'no_file_selected','msg'=>'Please Select Talks to Download'));
            die;
        }
        $presentationIds= explode('-', $presentationIds);

        echo json_encode($this->zipPresentation($presentationIds));

    }

    function zipPresentation($presentationIds){

        $day = date('m-d');

        $zip = new ZipArchive();

        $zipName = 'talks.zip';

        if ($zip->open($zipName, ZipArchive::OVERWRITE|ZipArchive::CREATE) !== TRUE) {
            return array('status'=>'error', 'msg'=>'Something went wrong!');
        }


        foreach ($presentationIds as $presentationId) {
            $result = $this->get_file_path($presentationId);
            if($result){
                foreach ($result as $index=> $data) {
                    $full_path = FCPATH . $data->file_path;
                    $filename = $data->name;
                    $session_name = $data->session_name;
                    $room_name = $data->room_name;
                    $presentation_day = date('m-d', strtotime($data->presentation_date));
                    $last_name = $data->last_name;
                    $prs = date('H:i', strtotime($data->presentation_start));
                    $presentation_time_dir = str_replace(':','',$prs).'_'.$last_name;
                    $zip->addFile($full_path, $presentation_day.'/'.$room_name.'/'.$session_name .'/'.$presentation_time_dir.'/'.$filename); // to add current file
                }
            }
        }

// close and save archive
        $zip->close();

        return array('status'=>'success', 'file_name'=>$zipName);

    }

    function get_file_path($presentationId){
        $file_path = $this->db->select('u.file_path, u.name, s.name as session_name, r.name as room_name, p.presentation_date as presentation_date, pr.last_name as last_name, pr.first_name as first_name, p.presentation_start as presentation_start')
            ->from('uploads u')
            ->join('presentations p', 'u.presentation_id = p.id', 'left')
            ->join('sessions s', 'p.session_id = s.id', 'left')
            ->join('room r', 'u.room_id = r.id', 'left')
            ->join('presenter pr', 'u.presenter_id = pr.presenter_id', 'left')
            ->where('u.deleted', '0')
            ->where('u.presentation_id', $presentationId)
            ->get();

        if($file_path->num_rows()>0){
            return $file_path->result();
        }else{
            return false;
        }
    }

    public function getPresentationsDt(){
        $post = $this->input->post();

        $this->db->select("p.*, s.name as session_name,s.id as session_id, pr.presenter_id,
         pr.first_name as first_name, pr.last_name as last_name, pr.email as email, rm.name as room_name, 
         rm.id as room_id, p.id as presentation_id, p.assigned_id as assigned_id");
        $this->db->from('presentations p');
        $this->db->join('sessions s', 's.id = p.session_id');
        $this->db->join('presenter pr', 'pr.presenter_id = p.presenter_id');
        $this->db->join('room rm', 'p.room_id = rm.id');

        $tempDbObj = clone $this->db;
        $total_results = $tempDbObj->count_all_results();

        foreach ($post['columns'] as $column){
            if ($column['search']['value']!='' && $column['search']['value']!= 'on' && $column['search']['value']!= 'active'  && $column['search']['value']!= 'disabled'  && $column['name'] != 'new-uploads')
                $this->db->like($column['name'], $column['search']['value']);

            if($column['search']['value']== 'active')
                $this->db->where('active',  '1');

            if($column['search']['value']== 'disabled')
                $this->db->where('active',  '0');

            if($column['search']['value'] != '' && $column['name']=='new-uploads'){
                $searcValue = explode('-',$column['search']['value']);
                $this->db->or_where_in('p.id', $searcValue);
            }
        }

        $tempDbObj = clone $this->db;
        $total_filtered_results = $tempDbObj->count_all_results();
        $tempDbObj = clone $this->db;
        $total_filtered_select = $tempDbObj->get();
        $total_filtered_items = array();
        foreach ($total_filtered_select->result() as $row)
        {
            $total_filtered_items[] = (int) $row->id;
        }

        if($post['columns']!=='action')
            $this->db->order_by($post['columns'][$post['order'][0]['column']]['name'], $post['order'][0]['dir']);
        else {
            $this->db->order_by('p.presentation_date', 'DESC');
            $this->db->order_by('p.presentation_start', 'DESC');
        }
        // Filter for pagination and rows per page
        if (isset($post['start']) && isset($post['length']))
            $this->db->limit($post['length'], $post['start']);

        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            foreach ($result->result() as $index=> $row){
                $row->uploadStatus = $this->checkUploadStatus($row->id);
                $row->index = $index+1;
                $row->newUploads = $this->getUploadsCountDt($row->id);
            }

            $response_array = array(
                "draw" => $post['draw'],
                "recordsTotal" => $total_results,
                "recordsFiltered" => $total_filtered_results,
                "data" => $result->result(),
                "total_filtered" => $total_filtered_items
            );

        } else {
            $response_array = array(
                "draw" => $post['draw'],
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => new stdClass()
            );

        }
        echo json_encode($response_array);
    }


    public function getLogs($presenter_id)
    {
        $logs = $this->db->select('log.*, icon.*, presenter.first_name, presenter.last_name, presentations.name, uploads.name file_name, uploads.file_path')
            ->from('presenter_logs log')
            ->join('presenter', 'log.presenter_id = presenter.presenter_id', 'left')
            ->join('presentations', 'log.ref_presentation_id = presentations.id', 'left')
            ->join('uploads', 'log.other_ref = uploads.id', 'left')
            ->join('log_icons icon', 'log.log_name = icon.log_name', 'left')
            ->where('log.presenter_id', $presenter_id)
            ->order_by('log.date_time', 'DESC')
            ->get();

        if($logs->num_rows()>0){
            echo json_encode($logs->result());
        }else{
            echo json_encode(array());
        }
    }

    public function getPresentationListArray()
    {

        $this->db->select("p.*, s.name as session_name,s.id as session_id, pr.presenter_id, CONCAT(pr.first_name, ' ', pr.last_name) as presenter_name, pr.email as email, rm.name as room_name, rm.id as room_id");
        $this->db->from('presentations p');
        $this->db->join('sessions s', 's.id = p.session_id');
        $this->db->join('presenter pr', 'pr.presenter_id = p.presenter_id');
        $this->db->join('room rm', 'p.room_id = rm.id');
        $this->db->order_by('p.created_on', 'DESC');
        $result = $this->db->get();

        if ($result->num_rows() > 0)
        {
            return $result->result();
        } else {
            return '';
        }
    }

}
