<?php

class Order_model extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Itemmodel');
        $this->load->model('Common');
        $this->load->model('Search_model');
        $this->load->model('Customer_model');
        $this->load->model('Invoice_model');
        $this->load->helper('cookie');
    }

    public function create_order($cart_items, $calculate_tax, $customer_id) {
        $this->db->trans_start();
        $order_total = 0;
        $order_id = NULL;
        $success = TRUE;
        $order_date = date('Y-m-d H:i:s');
        if ($this->insert_order($customer_id, $order_date)) {
            $order_id = $this->db->insert_id();
            foreach ($cart_items as $item) {
                if (!$this->create_order_item($order_id, $item, $customer_id, $order_date)) {
                    $success = FALSE;
                    break;
                }
                $order_total += $item['qty'] * $item['item_data']['price'];
            }
            $this->update_order_amount($order_id, $order_total);
            $invoice_id = $this->Invoice_model->create_new_invoice($order_id, $order_total);
            if (isset($invoice_id) && $invoice_id > 0) {
                $res = $this->Invoice_model->get_order_item_to_invoice($invoice_id, $cart_items);

                if (!$res) {
                    $success = FALSE;
                }
            }
            if ($calculate_tax == TRUE && $success) {
                $acc_amount = $order_total;
                $total_amount = 0;
                $taxes = $this->Search_model->get_taxes();
                foreach ($taxes as $tax) {
                    if ($tax['accumilative'] == 0) {
                        $tax_amount = $acc_amount * $tax['percentage'] * 0.01;
                        $total_amount += $tax_amount;
                    } else {
                        $tax_amount = $total_amount * $tax['percentage'] * 0.01;
                        $total_amount += $tax_amount;
                    }

                    if (!$this->create_order_tax($order_id, $tax, $tax_amount, $customer_id, $order_date)) {
                        $success = FALSE;
                        break;
                    }
                }
            }
            if ($success) {
                $order_id = $order_id;
                $success = $this->db->trans_complete();

                //empty shopping cart 
                $this->Search_model->empty_cart();
            } else {
                $success = FALSE;
                $this->db->trans_rollback();
            }
        } else {
            $success = FALSE;
            $this->db->trans_rollback();
        }
        return array(
            'success' => $success,
            'order_id' => $order_id,
        );
    }

    private function insert_order($customer_id, $date) {
        $order_number = $this->get_new_order_number();
        $key = $this->Common->get_key(32, 'order', 'key');
        $shipping_address = (int) $_SESSION['shipping_address_id'];
        return $this->db->insert('order', [
                    'order_number' => $order_number,
                    'customer_id' => $customer_id,
                    'amount' => 0,
                    'order_date' => $date,
                    'shipping_address_id' => $shipping_address,
                    'key' => $key,
                    'enabled' => 1,
                    'created_by' => $customer_id,
                    'created_on' => $date,
                    'lastupdated_by' => $customer_id,
                    'lastupdated_on' => $date,
        ]);
    }

    private function create_order_item($order_id, $item, $customer_id, $date) {
        return $this->db->insert('order_item', [
                    'order_id' => $order_id,
                    'item_id' => $item['item'],
                    'qty' => $item['qty'],
                    'each_price' => $item['item_data']['price'],
                    'enabled' => 1,
                    'created_by' => $customer_id,
                    'created_on' => $date,
                    'lastupdated_by' => $customer_id,
                    'lastupdated_on' => $date,
        ]);
    }

    private function create_order_tax($order_id, $tax, $tax_amount, $customer_id, $date) {
        return $this->db->insert('order_tax', [
                    'order_id' => $order_id,
                    'tax_id' => $tax['id'],
                    'tax_amount' => $tax_amount,
                    'enabled' => 1,
                    'created_by' => $customer_id,
                    'created_on' => $date,
                    'lastupdated_by' => $customer_id,
                    'lastupdated_on' => $date,
        ]);
    }

    private function update_order_amount($order_id, $amount) {
        return $this->db
                        ->where('id', $order_id)
                        ->update('order', ['amount' => $amount]);
    }

    public function get_new_order_number() {
        $config = $this->Common->get_preferences();
        $order_number = $config['FIRST_ORDER_NUMBER'];
        $res = $this->db
                        ->select('order_number')
                        ->from('order')
                        ->where('enabled', 1)
                        ->order_by('id', 'desc')
                        ->get()->result_array();
        if (count($res) > 0) {
            $order_number = $res[0]['order_number'] + 1;
        }
        return $order_number;
    }

    public function get_after_order_url($preferences) {
        return $preferences['AFTER_ORDER_URL'];
    }

    public function get_addesses($entity, $ref_id, $enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        return $this->db
                        ->from('address')
                        ->where('entity', $entity)
                        ->where('reference_id', $ref_id)
                        ->get()->result_array();
    }

    public function send_order_email($order, $order_prefix) {
        $pdf = '';
        $customer = $this->Customer_model->get_customer_by_id($order['customer_id']);
        $tracking_code = $this->Common->get_email_tracking_code();
        if ($customer != FALSE && isset($customer['email'])) {
            $order_number = $order_prefix . $order['order_number'];
            $content = $this->get_order_email_content($order, $tracking_code);
            //$pdf = $this->Common->get_pdf_buffer($content);
            $res = $this->Common->easy_mail($customer['email'], 'Your Order Detail: ' . $order_number, [], $content, [[
            'buffer' => $pdf,
            'name' => $order_number . '.pdf',
            'mime' => 'application/pdf'
            ]]);
            if ($res) {
                $this->Common->add_sent_mail('ORDER_CREATION', $order['id'], $tracking_code, 0);
            }
        }
    }

    public function get_order_by_id($order_id) {
        $order = NULL;
        $this->db->select('ord.*,cus.first_name,cus.last_name,cus.mobile_phone,cus.city');
        $this->db->from('`order` ord');
        $this->db->join('customer cus', 'cus.id = ord.customer_id', 'inner');
        $q = $this->db->where('ord.id', $order_id);
        $q = $this->db->get();
        $res = $q->result_array();
        //$res = $this->db->get_where('order', ['id' => $order_id])->result_array();
        if (count($res) > 0) {
            $order = $res[0];
            $order['items'] = $this->get_order_items_by_order_id($order['id']);
            $order['taxes'] = $this->get_order_taxes_by_order_id($order['id']);
            $order['shipping'] = $this->get_shipping_address_by_id($order['shipping_address_id']);
        }
        return $order;
    }

    public function get_order_items_by_order_id($order_id) {
        $items = [];
        $items = $this->db->get_where('order_item', ['order_id' => $order_id])->result_array();
        if (count($items) > 0) {
            for ($i = 0; $i < count($items); $i++) {
                $items[$i]['item_data'] = $this->Itemmodel->get_item_by_id($items[$i]['id']);
            }
        }
        return $items;
    }

    public function get_order_taxes_by_order_id($order_id) {
        //$taxes = [];
        $this->db->select('ot.*,mt.tax_name,mt.percentage');
        $this->db->from('order_tax ot');
        $this->db->join('tax_master mt', 'mt.id = ot.tax_id', 'inner');
        $this->db->order_by('mt.apply_order', 'asc');
        $this->db->where(array('ot.order_id' => $order_id));
        $taxes = $this->db->get()->result_array();
        return $taxes;
    }

    public function get_order_email_content($order, $tracking_code) {
        $this->load->library('parser');
        $preferences = $this->Common->get_preferences();
        $content = '';
        $res = $this->db->get_where('template', ['id' => 2])->result_array();
        if ($res > 0) {
            $content = $res[0]['content'];
        }
        $items_html = $this->get_items_html($order['items']);
        $content = $this->parser->parse_string($content, [
            'order_number' => $preferences['ORDER_PREFIX'] . $order['order_number'],
            'items' => $items_html,
            'image_url' => base_url('public/images/e-logo.png'),
            'tracking_code' => $tracking_code
        ]);
        return $content;
    }

    private function get_items_html($order_items) {
        $html = "<table><thead><tr><th>Item</th><th>Each</th><th>Qty</th><th>Sub Total</th></tr></thead><tbody>";
        foreach ($order_items as $item) {
            $item_data = $item['item_data'];
            $html .= "<tr><td>" . $item_data['name'] . "</td><td>" . $item_data['price'] . "</td><td>" . $item['qty'] . "</td><td>" . ($item['qty'] * $item_data['price']) . "</td></tr>";
        }
        $html .= "</tbody><table></div>";
        return $html;
    }

    public function add_prescription($order_id) {
        $creation_data = $this->Common->get_customer_creation_data();
        $data = array(
            'order_id' => $order_id,
            'right_cyl' => $_SESSION['prescription_session']['right_cyl'],
            'right_sph' => $_SESSION['prescription_session']['right_sph'],
            'right_axis' => $_SESSION['prescription_session']['right_axis'],
            'left_cyl' => $_SESSION['prescription_session']['left_cyl'],
            'left_sph' => $_SESSION['prescription_session']['left_sph'],
            'left_axis' => $_SESSION['prescription_session']['left_axis'],
            'distance_pd' => $_SESSION['prescription_session']['pd'],
            'distance_lence_type' => $_SESSION['prescription_session']['lens_type'],
            'near_right' => $_SESSION['prescription_session']['near_right'],
            'near_left' => $_SESSION['prescription_session']['near_left'],
            'near_lens_type' => $_SESSION['prescription_session']['near_lens_type'],
            'near_pd' => $_SESSION['prescription_session']['near_pd'],
            'remarks' => $_SESSION['prescription_session']['remarks'],
            'image_file' => $_SESSION['prescription_session']['prescription_image'],
        );

        $res = $this->db->insert('prescriptions', array_merge($data, $creation_data));
        return $res;
    }

    public function get_shipping_address_by_id($shipping_address_id) {
        $q = $this->db->get_where('address', array('id' => $shipping_address_id));
        $res = $q->result_array();
        if (!empty($res)) {
            return $res[0];
        } else {
            return FALSE;
        }
    }

    public function get_prescription_by_id($order_id) {
        $q = $this->db->get_where('prescriptions', array('order_id' => $order_id));
        $res = $q->result_array();
        if (!empty($res)) {
            return $res[0];
        } else {
            return FALSE;
        }
    }

    public function get_all_orders() {
        $this->db->select('ord.*,cus.first_name,cus.last_name,cus.mobile_phone,cus.city');
        $this->db->from('`order` ord');
        $this->db->join('customer cus', 'cus.id = ord.customer_id', 'inner');
        $q = $this->db->get();
        $res = $q->result_array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['item_details'] = $this->get_order_items_by_order_id($res[$i]['id']);
            $res[$i]['shipping_details'] = $this->get_shipping_address_by_id($res[$i]['shipping_address_id']);
            $res[$i]['prescription_details'] = $this->get_prescription_by_id($res[$i]['id']);
        }
        return $res;
    }

    public function get_order_by_key($key) {
        $q = $this->db->get_where('`order`', array('key' => $key));
        $res = $q->result_array();
        if (!empty($res)) {
            return $res[0];
        } else {
            return FALSE;
        }
    }

}
