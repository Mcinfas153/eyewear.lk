<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('common');
//        $this->load->model('itemmodel');
    }

    function upload_image() {
        $image_type = $this->input->post('image_type');
        $res = array();
        if ($image_type == 'brand-logo-image') {
            $res = $this->common->upload_image(
                    'brand-logo', BRAND_LOGO_MINWIDTH, BRAND_LOGO_MINHEIGHT, BRAND_LOGO_MAXWIDTH, BRAND_LOGO_MAXHEIGHT, BRAND_LOGO_MAXSIZE, BRAND_LOGO_ALLOWEDTYPES, BRAND_LOGO_UPLOAD_PATH, TRUE);
        } else if ($image_type == 'news-image') {
            $res = $this->common->upload_image(
                    'news-image', NEWS_MINWIDTH, NEWS_MINHEIGHT, NEWS_MAXWIDTH, NEWS_MAXHEIGHT, NEWS_MAXSIZE, NEWS_ALLOWEDTYPES, NEWS_UPLOAD_PATH, TRUE);
        } else if ($image_type == 'item-image') {
            $res = $this->common->upload_image(
                    'item-image', ITEM_MINWIDTH, ITEM_MINHEIGHT, ITEM_MAXWIDTH, ITEM_MAXHEIGHT, ITEM_MAXSIZE, ITEM_ALLOWEDTYPES, ITEM_UPLOAD_PATH, TRUE);
        } else if ($image_type == 'model-image') {
            $res = $this->common->upload_image(
                    'model-image', ITEM_MINWIDTH, ITEM_MINHEIGHT, ITEM_MAXWIDTH, ITEM_MAXHEIGHT, ITEM_MAXSIZE, ITEM_ALLOWEDTYPES, MODEL_UPLOAD_PATH, TRUE);
        } else if ($image_type == 'prescription-image') {
            $res = $this->common->upload(
                    'prescription-image', PRESCRIPTION_MINWIDTH, PRESCRIPTION_MINHEIGHT, PRESCRIPTION_MAXWIDTH, PRESCRIPTION_MAXHEIGHT, PRESCRIPTION_MAXSIZE, PRESCRIPTION_ALLOWEDTYPES, PRESCRIPTION_UPLOAD_PATH, TRUE);
        }

        if ($res['error_no'] == 0) {
            Ajax::AddAuditTrailEntry(AUDITTRAIL_ADDNEW, 'Image Upload', "File Name: " . $res['upload_data']['file_name']);
        }
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    function upload_image_with_thumbnails() {
        $image_type = $this->input->post('image_type');
        $res1 = array();
        if ($image_type == 'model-image') {
            $res1 = $this->common->upload_model_image('model-image');
        } else if ($image_type == 'item-image') {
            $res1 = $this->common->upload_item_image('item-image');
        }
        if ($res1['error_no'] == 0) {
            Ajax::AddAuditTrailEntry(AUDITTRAIL_ADDNEW, 'Image Upload', "File Name: " . $res1['hq_image_name']);
        }
        header('Content-Type: application/json');
        echo json_encode($res1);
    }

    function get_table_data($entity) {
        $allowed_entities = array('brand');
        $results = array();
        if (in_array($entity, $allowed_entities)) {
            $data = $this->itemmodel->get_all_brands(TRUE);
        }
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    function toggle_enable() {
        $error_no = 0;
        $error = '';
        if (isset($_SESSION['admin'])) {
            $table_name = $this->input->post('table');
            $id = $this->input->post('id');
            if ($table_name != null && strlen(trim($table_name)) > 0 && strlen(trim($table_name)) < 25) {
                if ($id != null && strlen(trim($id)) > 0 && strlen(trim($id)) < 4) {
                    if (!$this->common->toggle_enable($table_name, $id, 1)) {
                        $error_no = 41;
                    }
                }
            }
        } else {
            $error_no = 1;
        }
        if ($error_no != 0) {
            $error = $this->config->item($error_no, 'msg');
        }
        header('Content-Type: application/json');
        echo json_encode(array('error_no' => $error_no, 'error' => $error));
    }

    public function password_reset_request() {
        $error_no = 0;
        $error = '';
        $username = $this->input->post('forgot-username');
        $code = '';
        if ($username != NULL) {
            $username = trim($username);
            $length = strlen($username);
            if ($length > 0 && $length < 100) {
                //get user details
                $this->load->model('Sed_core_user');
                $user_obj = Sed_core_user::get_user_by_username_recovery_mail($username);
                if ($user_obj && isset($user_obj['username']) && isset($user_obj['id'])) {
                    if ($user_obj['recovery_email'] != NULL && strlen($user_obj['recovery_email']) > 0) {
                        //create new password reset code
                        $code = $this->common->get_unique_code(8, 'sed_user_code', 'code');
                        //create reset password record and send email
                        if (!$this->Sed_core_user->reset_password_by_email($user_obj['id'], $user_obj['recovery_email'], $code)) {
                            $error_no = 160;
                        } else {
                            Ajax::AddAuditTrailEntry(AUDITTRAIL_INFORMATION, 'Password Reset Reqest', "Username : " . $user_obj['username']);
                        }
                    } else {
                        $error_no = 159;
                    }
                } else {
                    $error_no = 158;
                }
            } else {
                $error_no = 157;
            }
        } else {
            $error_no = 157;
        }
        if ($error_no != 0) {
            $error = $this->config->item($error_no, 'msg');
        }
        header('Content-Type: application/json');
        echo json_encode(array('error_no' => $error_no, 'error' => $error, 'code' => $code));
    }

    public function save_prescription_data() {
        $success = 0;
        if (isset($_SESSION['prescription_session'])) {
            unset($_SESSION["prescription_session"]);
        }

        $prescription_cookie = array(
            'right_cyl' => $this->input->post('right_cyl'),
            'right_sph' => $this->input->post('right_sph'),
            'right_axis' => $this->input->post('right_axis'),
            'left_cyl' => $this->input->post('left_cyl'),
            'left_sph' => $this->input->post('left_sph'),
            'left_axis' => $this->input->post('left_axis'),
            'pd' => $this->input->post('pd'),
            'lens_type' => $this->input->post('lens_type'),
            'near_right' => $this->input->post('near_right'),
            'near_left' => $this->input->post('near_left'),
            'near_pd' => $this->input->post('near_pd'),
            'near_lens_type' => $this->input->post('near_lens_type'),
            'remarks' => $this->input->post('remarks'),
            'prescription_image' => $this->input->post('prescription_image'),
        );

        $_SESSION['prescription_session'] = $prescription_cookie;
        if (isset($_SESSION['prescription_session'])) {
            $success = 1;
        }
        header('Content-Type: application/json');
        echo json_encode(array('success' => $success));
    }

}
