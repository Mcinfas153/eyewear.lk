<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Invoice_model');
        $this->load->model('Payment_model');
    }

    public function payment_process() {
        if (isset($_SESSION['admin'])) {
            $invoice_id = $this->uri->segment(3);
            if (isset($invoice_id) && $invoice_id > 0) {
                $data['mode_of_payments'] = $this->Payment_model->get_all_mode_of_payments();
                $data['invoice_details'] = $this->Invoice_model->get_invoice_details_by_id($invoice_id);
                $data['title'] = 'Admin | Payment Process';
                $data['main_content'] = 'admin/payment_process';
                $this->load->view('templates/admin_template', $data);
            } else {
                //invoice id not found
                redirect(base_url('error/page/7'));
            }
        } else {
            redirect(base_url('admin/login_view'));
        }
    }

    public function payment_process_next() {
        if (isset($_SESSION['admin'])) {
            if ($_POST['invoice_id']) {
                $invoice_id = $this->input->post('invoice_id');
                $mode_of_payment = $this->input->post('mode_of_payment');
                $total_payable = $this->input->post('total_payable');

                $data['invoice_id'] = $invoice_id;
                $data['mode_of_payment'] = $mode_of_payment;
                $data['total_payable'] = $total_payable;
                $data['fields'] = $this->Payment_model->get_mode_of_payment_fields($mode_of_payment);
                $data['invoice_details'] = $this->Invoice_model->get_invoice_details_by_id($invoice_id);
                $data['title'] = 'Admin | Payment Process';
                $data['main_content'] = 'admin/payment_process_next';
                $this->load->view('templates/admin_template', $data);
            }
        } else {
            redirect(base_url('admin/login_view'));
        }
    }

}
