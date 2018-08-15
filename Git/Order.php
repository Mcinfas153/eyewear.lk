<?php

class Order extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Order_model');
        $this->load->model('Product_model');
    }

    public function index() {
        if (isset($_SESSION['admin'])) {
            $data['orders'] = $this->Order_model->get_all_orders();
            $data['title'] = 'Admin | Order';
            $data['main_content'] = 'admin/order';
            $this->load->view('templates/admin_template', $data);
        } else {
            redirect(base_url('admin/login_view'));
        }
    }

    public function order_card() {
        if (isset($_SESSION['admin'])) {
            $key = $this->uri->segment(3);
            if (isset($key)) {
                $order_details = $this->Order_model->get_order_by_key($key);
                $data['order_details'] = $this->Order_model->get_order_by_id($order_details['id']);
                $data['title'] = 'Admin | Order Card';
                $this->load->view('admin/order_card', $data);
                //$this->load->view('admin/test_order_card', $data);
            } else {
                //order id not found
            }
        } else {
            redirect(base_url('admin/login_view'));
        }
    }

}
