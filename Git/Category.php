<?php

//Authoe : Mcinfas
//date : 2018/01/16

defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('category_model');
    }

    public function index() {
        if (isset($_SESSION['admin'])) {
            $data['title'] = 'Admin | Category';
            $data['nestable_categories'] = $this->category_model->get_nestable_view();
            $data['main_categories'] = $this->category_model->get_parent_categories();
            $data['categories'] = $this->category_model->get_all_categories();
            $data['main_content'] = 'admin/category';
            $this->load->view('templates/admin_template', $data);
            Category::AddAuditTrailEntry(AUDITTRAIL_VIEW, 'Category', '');
        } else {
            redirect(base_url('admin/login_view'));
        }
    }

    public function add_category() {
        $error_no = 0;
        $error = '';
        if (isset($_SESSION['admin'])) {
            $category_name = $this->input->post('category_name');
            $main_category_id = $this->input->post('parent_category_id');
            $category_description = $this->input->post('category_description');

            $info = array(
                'category_name' => $category_name,
                'category_description' => $category_description,
                'main_category_id' => $main_category_id,
            );

            $res = $this->category_model->add_category($info);
            if ($res) {
                Category::AddAuditTrailEntry(AUDITTRAIL_ADDNEW, 'Category', $category_name);
            } else {
                $error_no = 301;
                Category::AddAuditTrailEntry(AUDITTRAIL_WARNING, 'Add Category', '');
            }
        } else {
            redirect(base_url('admin/login_view'));
        }
        header('Content-Type: application/json');
        echo json_encode(array('error_no' => $error_no, 'error' => $error));
    }

    public function edit_category() {
        $error_no = 0;
        $error = '';
        if (isset($_SESSION['admin'])) {
            $category_name = $this->input->post('category_name');
            $category_id = $this->input->post('category_id');
            $category_description = $this->input->post('category_description');

            $info = array(
                'category_name' => $category_name,
                'category_id' => $category_id,
                'category_description' => $category_description,
            );

            $res = $this->category_model->edit_category($info);
            if ($res) {
                Category::AddAuditTrailEntry(AUDITTRAIL_UPDATE, 'Category', $category_name);
            } else {
                $error_no = 305;
                Category::AddAuditTrailEntry(AUDITTRAIL_WARNING, 'Update Category', '');
            }
        } else {
            redirect(base_url('admin/login_view'));
        }
        header('Content-Type: application/json');
        echo json_encode(array('error_no' => $error_no, 'error' => $error));
    }

}
