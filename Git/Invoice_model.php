<?php

class Invoice_model extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->load->helper('cookie');
        $this->load->model('Customer_model');
        $this->load->model('Order_model');
        $this->load->model('Common');
        $this->load->model('Payment_model');
    }

    public function create_new_invoice($order_id, $order_total) {
        //$shipping_profiles = $this->Customer_model->get_shipping_address_by_id($_SESSION['shipping_address_id']);

        $invoice_data['invoice_code'] = 'INV/' . date('Y') . '/' . random_string('nozero', 6);
        $invoice_data['key'] = $this->Common->get_key(32, 'order_invoice', 'key');
        $invoice_data['order_id'] = $order_id;
        $invoice_data['net_amount'] = $order_total;
        $invoice_data['amount'] = $order_total;
        $invoice_data['total_amount'] = $order_total;
        $invoice_data['currency'] = 1;
        $invoice_data['payment_method'] = PAYMENT_STANDARD;
        $invoice_data['payment_status'] = PAYNOW;
        $invoice_data['mode_of_payment'] = PAYMENT_CASH;
        $invoice_data['bill_to'] = $_SESSION['customer']['id'];
        $invoice_data['enabled'] = 1;
        $invoice_data['created_on'] = date('Y-m-d H:i:s');
        $invoice_data['created_by'] = $_SESSION['customer']['id'];
        $invoice_data['lastupdated_on'] = 1;
        $invoice_data['lastupdated_by'] = $_SESSION['customer']['id'];

        $res = $this->db->insert('order_invoice', $invoice_data);
        $insert_id = $this->db->insert_id();
        return $insert_id;
    }

    public function get_order_item_to_invoice($invoice_id, $cart_items) {
        foreach ($cart_items as $cart) {
            $res = $this->add_order_items_to_invoice($invoice_id, $cart);
            if (!$res) {
                $res = FALSE;
                break;
            }
        }
        return $res;
    }

    public function add_order_items_to_invoice($invoice_id, $cart) {
        $creation_data = $this->Common->get_customer_creation_data();
        $data = array(
            'invoice_id' => $invoice_id,
            'item_id' => $cart['item'],
            'amount' => $cart['item_data']['price'],
            'total_amount' => $cart['item_data']['price'],
            'nof_items' => $cart['qty'],
        );

        $res = $this->db->insert('order_invoice_item', array_merge($data, $creation_data));
        return $res;
    }

    public function get_invoice_details_by_id($invoice_id) {
        $this->db->select('oi.*,ord.customer_id,ord.order_number,ord.shipping_address_id,ord.order_date');
        $this->db->from('order_invoice oi');
        $this->db->join('`order` ord', 'ord.id = oi.order_id', 'inner');
        $this->db->where('oi.id', $invoice_id);
        $q = $this->db->get();
        $res = $q->result_array();
        if (!empty($res)) {
            $res = $res[0];
            $res['customer'] = $this->Customer_model->get_customer_by_id($res['customer_id']);
            $res['shipping_details'] = $this->Order_model->get_shipping_address_by_id($res['shipping_address_id']);
            $res['payment_details'] = $this->Payment_model->get_payments_by_invoice_id($invoice_id);
            $res['taxes'] = $this->Order_model->get_order_taxes_by_order_id($res['order_id']);
            return $res;
        } else {
            return FALSE;
        }
    }

    public function get_all_invoice() {
        $this->db->select('oi.*,ord.order_number,cus.first_name,cus.last_name');
        $this->db->from('order_invoice oi');
        $this->db->join('`order` ord', 'ord.id = oi.order_id', 'inner');
        $this->db->join('customer cus', 'cus.id = ord.customer_id', 'inner');
        $q = $this->db->get();
        $res = $q->result_array();
        for ($x = 0; $x < count($res); $x++) {
            $res[$x]['taxes'] = $this->Order_model->get_order_taxes_by_order_id($res[$x]['order_id']);
        }

        return $res;
    }

}
