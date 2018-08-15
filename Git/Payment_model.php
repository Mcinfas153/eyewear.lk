<?php

class Payment_model extends MY_Model {

    function __construct() {
        parent::__construct();
    }

    public function get_payments_by_invoice_id($invoice_id) {
        $this->db->select('*');
        $this->db->from('payment p');
        $this->db->where('p.invoice_id', $invoice_id);
        $q = $this->db->get();
        $res = $q->result_array();
        return $res;
    }

    public function get_all_mode_of_payments() {
        $q = $this->db->get_where('mode_of_payment', array('enabled', 1));
        $res = $q->result_array();
        return $res;
    }

    public function get_mode_of_payment_fields($mode_of_payment_id) {
        $this->db->select('mopf.*,pf.name as field_name,pf.type as field_type,pf.field_name as field_id');
        $this->db->from('mode_of_payment_fields mopf');
        $this->db->join('payment_field pf', 'pf.id = mopf.payment_field_id', 'inner');
        $this->db->order_by('mopf.`order`', 'asc');
        $this->db->where(array('mopf.mode_of_payment_id' => $mode_of_payment_id, 'mopf.enabled' => 1));
        $q = $this->db->get();
        $res = $q->result_array();
        return $res;
    }

}
