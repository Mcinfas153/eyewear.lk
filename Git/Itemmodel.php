<?php

class Itemmodel extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_all_brands($table_data = FALSE, $enabled = NULL) {
        $res = array();
        if ($enabled != NULL && ($enabled == 0 || $enabled == 1)) {
            $this->db->where('enabled', $enabled);
        }
        if ($table_data == TRUE) {
            $this->db->select('name, logo_image, lastupdated_on');
            $results = $this->db->get('brand')->result();
            foreach ($results as $row) {
                array_push($res, array($row->name, $row->logo_image, $row->lastupdated_on));
            }
        } else {
            $res = $this->db->get('brand')->result_array();
        }
        return $res;
    }

    public function add_brand($brand_name, $brand_logo, $created_by) {
        return $this->db->insert('brand', array(
                    'name' => $brand_name,
                    'logo_image' => $brand_logo,
                    'created_by' => $created_by,
                    'enabled' => 1,
                    'created_on' => date('Y-m-d h:i:s'),
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => date('Y-m-d h:i:s'),
        ));
    }

    public function edit_brand($brand_id, $brand_name, $brand_image, $updated_by) {
        $info = array(
            'name' => $brand_name,
            'lastupdated_by' => $updated_by,
            'lastupdated_on' => date('Y-m-d h:i:s'),
        );
        $image = $brand_image;
        if ($image != null && strlen(trim($image))) {
            $info['logo_image'] = trim($image);
        }
        $this->db->where('id', $brand_id);
        $res = $this->db->update('brand', $info);
        return $res;
    }

    public function get_all_item_types($enabled = NULL) {
        if ($enabled != NULL) {
            $e = 0;
            if ($enabled) {
                $e = 1;
            }
            $this->db->where('enabled', $e);
        }
        return $this->db->get('product_type')->result_array();
    }

    public function add_attribute($name, $data_type, $unit, $order_id, $min_value, $max_value, $is_mandatory, $created_by) {
        $date = date('y-m-d h:i:s');
        return $this->db->insert('model_field', array(
                    'field_name' => $name,
                    'data_type' => $data_type,
                    'unit' => $unit,
                    'display_order' => $order_id,
                    'min_value' => $min_value,
                    'max_value' => $max_value,
                    'is_mandatory' => ($is_mandatory) ? 1 : 0,
                    'enabled' => 1,
                    'created_by' => $created_by,
                    'created_on' => $date,
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => $date
        ));
    }

    public function edit_attribute($id, $name, $data_type, $unit, $order_id, $min_value, $max_value, $is_mandatory, $updated_by) {
        $date = date('y-m-d h:i:s');
        $this->db->where('id', $id);
        return $this->db->update('model_field', array(
                    'field_name' => $name,
                    'data_type' => $data_type,
                    'unit' => $unit,
                    'display_order' => $order_id,
                    'min_value' => $min_value,
                    'max_value' => $max_value,
                    'is_mandatory' => $is_mandatory,
                    'lastupdated_by' => $updated_by,
                    'lastupdated_on' => $date
        ));
    }

    public function get_all_master_data($enabled = NULL, $id = NULL) {
        if ($enabled != NULL) {
            $e = 0;
            if ($enabled) {
                $e = 1;
            }
            $this->db->where('enabled', $e);
        }
        if ($id != NULL) {
            $this->db->where('mf.id', $id);
        }
        $res = $this->db
                        ->select('mf.*')
                        ->from('model_field mf')
                        ->get()->result_array();
        $q = $this->db->last_query();
        return $res;
    }

    public function get_attribute_by_id($id) {
        $res = $this->Common->get_all_meta_data(NULL, $id);
        if (count($res) > 0) {
            $attr = $res[0];
            $attr['values'] = $this->db->get_where('field_value', array('field_id' => $id))->result_array();
            return $attr;
        }
        return FALSE;
    }

    public function add_attribute_value($att_id, $value, $dispaly_order, $created_by) {
        $date = date('y-m-d h:i:s');
        $res = $this->db->insert('field_value', array(
            'field_id' => $att_id,
            'value' => $value,
            'display_order' => $dispaly_order,
            'enabled' => 1,
            'created_by' => $created_by,
            'created_on' => $date,
            'lastupdated_by' => $created_by,
            'lastupdated_on' => $date,
        ));
        return $res;
    }

    public function add_field_value($field_id, $value, $created_by) {
        $date = date('y-m-d h:i:s');
        $res = $this->db->insert('field_value', array(
            'field_id' => $field_id,
            'value' => $value,
            'enabled' => 1,
            'created_by' => $created_by,
            'created_on' => $date,
            'lastupdated_by' => $created_by,
            'lastupdated_on' => $date,
        ));
        return $res;
    }

    public function edit_field_value($value_id, $value, $created_by) {
        $date = date('y-m-d h:i:s');
        $this->db->where('id', $value_id);
        $res = $this->db->update('field_value', array(
            'value' => $value,
            'lastupdated_by' => $created_by,
            'lastupdated_on' => $date,
        ));
        return $res;
    }

    public function edit_attribute_value($value_id, $value, $dispaly_order, $updated_by) {
        $date = date('y-m-d h:i:s');
        $this->db->where('id', $value_id);
        $res = $this->db->update('field_value', array(
            'value' => $value,
            'display_order' => $dispaly_order,
            'lastupdated_by' => $updated_by,
            'lastupdated_on' => $date,
        ));
        return $res;
    }

    public function get_values_by_model_field($id) {
        $this->db->select('mfv.*, mf.field_name');
        $this->db->from(' model_field_value mfv');
        $this->db->join('model_field mf', 'mf.id = mfv.model_field_id', 'inner');
        $this->db->where(array('mfv.model_field_id' => $id));
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    public function add_model_field_value($info, $created_by) {
        $date = date('y-m-d h:i:s');
        $data = array(
            'model_field_id' => $info['model_field_id'],
            'value' => $info['value'],
            'display_order' => $info['dispaly_order'],
            'enabled' => 1,
            'created_by' => $created_by,
            'created_on' => $date,
            'lastupdated_by' => $created_by,
            'lastupdated_on' => $date,
        );

        $res = $this->db->insert('model_field_value', $data);
        $q = $this->db->last_query();
        return $res;
    }

    public function edit_model_field_value($value_id, $value, $dispaly_order, $updated_by) {
        $date = date('y-m-d h:i:s');
        $this->db->where('id', $value_id);
        $res = $this->db->update('model_field_value', array(
            'value' => $value,
            'display_order' => $dispaly_order,
            'lastupdated_by' => $updated_by,
            'lastupdated_on' => $date,
        ));
        return $res;
    }

    public function get_model_field_name($model_field_id) {
        $this->db->select('field_name');
        $this->db->from('model_field');
        $this->db->where('id', $model_field_id);
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    public function get_model_field_by_id($model_field_id) {
        $this->db->select('*');
        $this->db->from('model_field');
        $this->db->where('id', $model_field_id);
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    public function is_field_value_existing($field_id, $value) {
        $count = $this->db
                ->like('value', trim($value))
                ->where('field_id', $field_id, 'none')
                ->count_all_results('field_value');
        return $count > 0;
    }

    public function add_model($model_name, $sub_category, $product, $description, $display_price_original, $display_price_sale, $created_by) {
        $date = date('y-m-d h:i:s');
        return $this->db->insert('model', [
                    'model_name' => $model_name,
                    'description' => $description,
                    'sub_category' => $sub_category,
                    'product_id' => $product,
                    'display_price_original' => $display_price_original,
                    'display_price_sale' => $display_price_sale,
                    'enabled' => 1,
                    'created_by' => $created_by,
                    'created_on' => $date,
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => $date
        ]);
    }

    public function edit_model($model_id, $model_name, $sub_category, $description, $display_price_original, $display_price_sale, $created_by) {
        $date = date('y-m-d h:i:s');
        $this->db->where('id', $model_id);
        return $this->db->update('model', [
                    'model_name' => $model_name,
                    'description' => $description,
                    'sub_category' => $sub_category,
                    'display_price_original' => $display_price_original,
                    'display_price_sale' => $display_price_sale,
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => $date
        ]);
    }

    public function get_all_models($enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('m.enabled', $enabled);
        }
        $res = $this->db
                        ->select('m.*, cat.name as sub_category_name, pc.name as parent_category_name, p.name as product_name')
                        ->from('model m')
                        ->join('metadata_product p', 'm.product_id= p.id')
                        ->join('category cat', 'm.sub_category = cat.id', 'left')
                        ->join('category pc', 'cat.parent_category_id = pc.id', 'inner')
                        ->get()->result_array();
        return $res;
    }

    public function get_product_fields_by_model_id($model_id) {
        $res = $this->db
                        ->select('f.*')
                        ->from('field f')
                        ->join('product_field pf', 'pf.field_id = f.id')
                        ->join('model m', 'm.product_id = pf.product_id')
                        ->where('f.enabled', 1)
                        ->where('pf.enabled', 1)
                        ->where('m.enabled', 1)
                        ->where('m.id', $model_id)
                        ->get()->result_array();
        $q = $this->db->last_query();
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]['field_type'] == 'LIST') {
//                $res[$i]['all_values'] = $this->get_item_field_values_by_model_id($res[$i]['id'], $model_id);
                $res[$i]['all_values'] = $this->get_model_field_values_by_model_id($res[$i]['id'], $model_id);
            }
        }
        return $res;
    }

    public function get_model_field_values_by_model_id($field_id, $model_id) {
        $sql = "SELECT
                        fv.*
                FROM
                        field_value fv
                    JOIN 
                        model_field_value mfv ON (mfv.field_value_id = fv.id)
                WHERE
                    fv.enabled = 1
                    AND mfv.enabled = 1
                    AND fv.field_id = $field_id
                    AND mfv.model_id = $model_id";
        return $this->db->query($sql)->result_array();
    }

    public function get_product_fields_by_model_id_backend($model_id) {
        $res = $this->db
                        ->select('f.*')
                        ->from('field f')
                        ->join('product_field pf', 'pf.field_id = f.id')
                        ->join('model m', 'm.product_id = pf.product_id')
                        ->where('f.enabled', 1)
                        ->where('pf.enabled', 1)
                        ->where('m.enabled', 1)
                        ->where('m.id', $model_id)
                        ->get()->result_array();
        $q = $this->db->last_query();
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]['field_type'] == 'LIST') {
                $res[$i]['backend_values'] = $this->get_field_values_by_model_id($res[$i]['id'], $model_id);
            }
        }
        return $res;
    }

    public function get_item_field_values_by_model_id($field_id, $model_id) {
        $sql = "SELECT ifv.id as field_value_id, ifv.value FROM item_field_value ifv where ifv.field_id = $field_id AND ifv.item_id IN (SELECT it.id FROM item it WHERE it.model_id = $model_id)";
        $res = $this->db->query($sql)->result_array();
        for ($i = 0; $i < count($res); $i++) {
            if (intval($res[$i]['value']) > 0) {
                //get field value
                $res[$i]['text_value'] = $this->get_field_value($field_id, $res[$i]['field_value_id']);
            }
        }
        return $res;
    }

    public function get_field_value($field_id, $value_id) {
        $res = $this->db->get_where('field_value', ['field_id' => $field_id, 'id' => $value_id])->result_array();
        $q = $this->db->last_query();
        if (count($res) > 0) {
            return $res[0]['value'];
        }
        return '';
    }

    public function get_model_by_id($model_id, $holder_size) {
        $res = $this->db
                        ->select('m.*')
                        ->from('model m')
                        ->where('m.enabled', 1)
                        ->where('m.id', $model_id)
                        ->get()->result_array();
        if (count($res) > 0) {
            $model = $res[0];
            $model['images'] = $this->get_single_model_image($model['id'], $holder_size);
            return $model;
        }
        return FALSE;
    }

    public function get_selected_model_field_values($field_id, $model_id) {
        $q = "
        SELECT 
            fv.id as field_value_id, m.id as model_id, fv.value,
            (SELECT 
                    COUNT(*) > 0
                FROM
                    model_field_value mfv
                WHERE
                    mfv.field_value_id = fv.id
                        AND mfv.model_id = $model_id
                        ) AS existing,
            (SELECT 
                    COUNT(*) > 0
                FROM
                    model_field_value mfv
                WHERE
                    mfv.field_value_id = fv.id
                        AND mfv.model_id = $model_id
                                        AND mfv.enabled = 1
                        ) AS selected
        FROM
            field_value fv
                JOIN
            field f ON (fv.field_id = f.id)
                JOIN
            product_field pf ON pf.field_id = f.id
                JOIN
            model m ON (m.product_id = pf.product_id)
        WHERE
            f.enabled = 1 AND fv.enabled = 1
                AND m.id = $model_id
                AND f.id = $field_id";
        $res = $this->db->query($q)->result_array();
        return $res;
    }

    public function update_model_field_values($field_id, $model_id, $values_array, $created_by) {
        $date = date('y-m-d h:i:s');
        $ok = TRUE;
        foreach ($values_array as $values) {
            $field_value_id = $values['field_value_id'];
//            $model_id = $values['model_id'];
            if ($values['existing'] == 1) {//update
                $this->db->where('field_value_id', $field_value_id);
                $this->db->where('model_id', $model_id);
                $u = $this->db->update('model_field_value', [
                    'enabled' => $values['selected'],
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => $date
                ]);
                if (!$u) {
                    $ok = FALSE;
                    break;
                }
            } else {
                if ($values['selected'] == 1) {//insert
                    $i = $this->db->insert('model_field_value', [
                        'model_id' => $model_id,
                        'field_value_id' => $field_value_id,
                        'enabled' => 1,
                        'created_by' => $created_by,
                        'created_on' => $date,
                        'lastupdated_by' => $created_by,
                        'lastupdated_on' => $date
                    ]);
                    if (!$i) {
                        $ok = FALSE;
                        break;
                    }
                }
            }
        }
        return $ok;
    }

    public function get_add_item_parameters() {
        $model_id = $this->input->post('model-id');
        $session_parameters = [
            'model-id' => $model_id,
            'item-name' => addslashes($this->input->post('item-name')),
            'item-code' => addslashes($this->input->post('item-code')),
            'item-desc' => addslashes($this->input->post('item-desc')),
            'item-unit' => addslashes($this->input->post('item-unit')),
            'show-selling-price' => $this->input->post('show-selling-price')
        ];
        $field_parameters = $this->get_add_item_field_parameters($model_id);
        $session_parameters['field_parameters'] = $field_parameters;
        return $session_parameters;
    }

    private function get_add_item_field_parameters($model_id) {
        $fields = $this->Itemmodel->get_product_fields_by_model_id($model_id);
        $result = [];
        foreach ($fields as $field) {
            $html_id = $field['html_id'];
            if (isset($_POST[$html_id])) {
                $result[$html_id] = $this->get_item_field_value_from_post($html_id, $field['field_type']);
            }
        }
        return $result;
    }

    private function get_item_field_value_from_post($html_id, $field_type) {
        $value = NULL;
        $raw_value = $this->input->post($html_id);
        switch ($field_type) {
            case 'TEXT':
            case 'RICH_TEXT':
            case 'MEMO':
                $value = addslashes($raw_value);
                break;
            case 'INT':
                $value = intval($raw_value);
                break;
            case 'FLOAT':
                $value = floatval($raw_value);
                break;
            case 'BOOLEAN':
                $value = ($raw_value == 'YES') ? 1 : 0;
                break;
            case 'DATE':
                $value = date('Y-m-d', strtotime($raw_value));
                break;
            case 'LIST':
                $value = $raw_value;
        }
        return $value;
    }

    private function get_field_value_by_value_id($value_id) {
        $res = $this->db->get_where('field_value', ['id' => $value_id])->result_array();
        if (count($res) > 0) {
            return $res[0]['value'];
        }
        return FALSE;
    }

    public function add_item($parameters, $image_files, $created_by) {
        $date = date('y-m-d h:i:s');
        $creation_data = ['enabled' => 1, 'created_by' => $created_by, 'created_on' => $date, 'lastupdated_by' => $created_by, 'lastupdated_on' => $date];
        $this->db->trans_start();
        $res = $this->db->insert('item', array_merge([
            'model_id' => $parameters['model-id'],
            'name' => $parameters['item-name'],
            'code' => $parameters['item-code'],
            'description' => $parameters['item-desc'],
            'unit' => $parameters['item-unit'],
            'show_selling_price' => $parameters['show-selling-price']
                        ], $creation_data));
        if ($res) {
            $item_id = $this->db->insert_id();
            $res2 = $this->add_item_field_values($parameters['model-id'], $item_id, $parameters['field_parameters'], $creation_data);
            if ($res2 != FALSE) {
                $res3 = $this->add_item_images($item_id, $image_files, $creation_data);
                if ($res3 != FALSE) {
                    $this->db->trans_complete();
                    return $item_id;
                }
            }
        }
        $this->db->trans_rollback();
        return FALSE;
    }

    private function add_item_field_values($model_id, $item_id, $field_parameters, $creation_data) {
        $fields = $this->Itemmodel->get_product_fields_by_model_id($model_id);
        $ok = TRUE;
        foreach ($fields as $field) {
            $html_id = $field['html_id'];
            $value = $field_parameters[$html_id];
            if (isset($field_parameters[$html_id])) {
                $field_value = $text_value = '';
                if ($field['field_type'] == 'TEXT' || $field['field_type'] == 'RICH_TEXT' || $field['field_type'] == 'MEMO') {
                    $text_value = $value;
                } else {
                    $field_value = $value;
                }
                $res = $this->db->insert('item_field_value', array_merge([
                    'item_id' => $item_id,
                    'field_id' => $field['id'],
                    'value' => $field_value,
                    'text_value' => $text_value
                                ], $creation_data));
                if ($res == FALSE) {
                    $ok = FALSE;
                    break;
                }
            }
        }
        return $ok;
    }

    private function add_item_images($item_id, $image_files, $creation_data) {
        $ok = TRUE;
        if (count($image_files)) {
            foreach ($image_files as $image) {
                $res = $this->db->insert('item_image', array_merge([
                    'item_id' => $item_id,
                    'image_name' => $image,
                    'is_preferred_image' => 0
                                ], $creation_data));
                if ($res == FALSE) {
                    $ok = FALSE;
                    break;
                }
            }
        }
        return $ok;
    }

    public function get_all_items($enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('m.enabled', $enabled);
        }
        $items = $this->db
                        ->select('i.*, m.model_name, c.name as sub_category, c2.id as parent_category_id, c2.name as parent_category, c.id as sub_category_id, p.counting_behaviour')
                        ->from('item i')
                        ->join('model m', 'i.model_id = m.id')
                        ->join('metadata_product p', 'm.product_id = p.id')
                        ->join('category c', 'm.sub_category = c.id')
                        ->join('category c2', 'c.parent_category_id= c2.id')
                        ->get()->result_array();
        $q = $this->db->last_query();
        if (count($items) > 0) {
            for ($i = 0; $i < count($items); $i++) {
                $items[$i]['field_values'] = $this->get_item_field_values($items[$i]['id']);
                $items[$i]['images'] = $this->get_item_images($items[$i]['id']);
            }
        }
        return $items;
    }

    public function get_item_field_values($item_id) {
        $res = $this->db
                        ->select('ifv.*, f.field_name, f.field_type')
                        ->from('item_field_value ifv')
                        ->join('field f', 'ifv.field_id = f.id')
                        ->where('ifv.item_id', $item_id)
                        ->get()->result_array();
        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                $value = NULL;
                if ($res[$i]['field_type'] == 'LIST') {
                    $value = $this->get_field_value_by_value_id($res[$i]['value']);
                } else if (in_array($res[$i]['field_type'], ['TEXT', 'RICH_TEXT', 'MEMO'])) {
                    $value = $res[$i]['text_value'];
                } else {
                    $value = $res[$i]['value'];
                }
                $res[$i]['field_value'] = $value;
            }
            return $res;
        }
        return FALSE;
    }

    public function get_item_images($items_id) {
        $res = $this->db->get_where('item_image', ['item_id' => $items_id])->result_array();
        return $res;
    }

    public function get_item_by_id($item_id, $holder_size = NULL) {
        $res = $this->db
                ->select('it.*, m.sub_category, m.model_name, p.counting_behaviour, cat.name as sub_category, u.unit as unit_symbol')
                ->from('item it')
                ->join('model m', 'it.model_id = m.id')
                ->join('metadata_product p', 'm.product_id = p.id')
                ->join('category cat', 'm.sub_category = cat.id')
                ->join('unit u', 'it.unit = u.id', 'left')
                ->where('it.id', $item_id)
                ->get()
                ->result_array();
        if (count($res) > 0) {
            $item = $res[0];
            $fields = $this->get_product_fields_by_model_id($item['model_id']);
            $item['fields'] = $this->update_selected_field_values($fields, $item_id);
            $item['image'] = $this->get_single_item_image($item['id'], $holder_size);
            $item['price'] = $this->Itemmodel->get_item_price($item['id']);
            return $item;
        }
        return FALSE;
    }

    public function get_item_by_item_code($item_code) {
        $res = $this->db
                ->select('it.*, m.sub_category, m.model_name, p.counting_behaviour')
                ->from('item it')
                ->join('model m', 'it.model_id = m.id')
                ->join('metadata_product p', 'm.product_id = p.id')
                ->where('it.code', $item_code)
                ->get()
                ->result_array();
        if (count($res) > 0) {
            $item = $res[0];
            $fields = $this->get_product_fields_by_model_id($item['model_id']);
            $item['fields'] = $this->update_selected_field_values($fields, $item['id']);
            return $item;
        }
        return FALSE;
    }

    public function get_item_images_by_item_id($item_id) {
        $res = $this->db->get_where('item_image', ['item_id' => $item_id])->result_array();
        return $res;
    }

    public function get_model_images_by_model_id($model_id) {
        $res = $this->db->get_where('model_image', ['model_id' => $model_id])->result_array();
        return $res;
    }

    public function update_selected_field_values($fields, $item_id) {
        $text_types = ['TEXT', 'RICH_TEXT', 'MEMO'];
        $values = $this->db->get_where('item_field_value', ['item_id' => $item_id])->result_array();
        for ($i = 0; $i < count($fields); $i++) {
            foreach ($values as $val) {
                if ($val['field_id'] == $fields[$i]['id']) {
                    $value = $val['value'];
                    if (in_array($fields[$i]['field_type'], $text_types)) {
                        $value = $val['text_value'];
                    }
                    $fields[$i]['value'] = $value;
                }
            }
        }
        return $fields;
    }

    public function edit_item($parameters, $created_by) {
        $date = date('y-m-d h:i:s');
        $creation_data = ['lastupdated_by' => $created_by, 'lastupdated_on' => $date];
        $this->db->trans_start();
        $item_id = $parameters['item-id'];
        $this->db->where('id', $item_id);
        $res = $this->db->update('item', array_merge([
            'model_id' => $parameters['model-id'],
            'name' => $parameters['item-name'],
            'code' => $parameters['item-code'],
            'description' => $parameters['item-desc'],
            'unit' => $parameters['item-unit'],
            'show_selling_price' => $parameters['show-selling-price']
                        ], $creation_data));
        if ($res) {
            $res2 = $this->edit_item_field_values($parameters['model-id'], $item_id, $parameters['field_parameters'], $creation_data);
            if ($res2 != FALSE) {
                $this->db->trans_complete();
                return $item_id;
            }
        }
        $this->db->trans_rollback();
        return FALSE;
    }

    private function edit_item_field_values($model_id, $item_id, $field_parameters, $creation_data) {
        $fields = $this->Itemmodel->get_product_fields_by_model_id($model_id);
        $ok = TRUE;
        foreach ($fields as $field) {
            $html_id = $field['html_id'];
            $value = $field_parameters[$html_id];
            if (isset($field_parameters[$html_id])) {
                $field_value = $text_value = '';
                if ($field['field_type'] == 'TEXT' || $field['field_type'] == 'RICH_TEXT' || $field['field_type'] == 'MEMO') {
                    $text_value = $value;
                } else {
                    $field_value = $value;
                }
                $field_id = $this->get_field_id_by_html_id($html_id);
                $this->db->where('field_id', $field_id);
                $this->db->where('item_id', $item_id);
                $res = $this->db->update('item_field_value', array_merge([
                    'value' => $field_value,
                    'text_value' => $text_value
                                ], $creation_data));
                if ($res == FALSE) {
                    $ok = FALSE;
                    break;
                }
            }
        }
        return $ok;
    }

    private function get_field_id_by_html_id($html_id) {
        $res = $this->db->get_where('field', ['html_id' => $html_id])->result_array();
        if (count($res) > 0) {
            return $res[0]['id'];
        }
    }

    public function get_grn_data() {
        return [
            'supplier' => $this->input->post('supplier'),
            'grn-date' => $this->input->post('grn-date'),
            'grn-ref' => $this->input->post('grn-ref'),
            'grn-total' => $this->input->post('grn-total'),
            'grn-vahicle' => $this->input->post('grn-vahicle'),
            'grn-driver' => $this->input->post('grn-driver'),
            'grn-unloading' => $this->input->post('grn-unloading'),
            'grn-tips' => $this->input->post('grn-tips'),
            'location' => $this->input->post('location')
        ];
    }

    public function get_grn_items() {
        $item_ids = $this->input->post('item-id');
        $item_unique = $this->input->post('item-unique');
        $item_serials = $this->input->post('item-serial');
        $item_qtys = $this->input->post('item-qty');
        $item_costs = $this->input->post('item-cost');
        $expiry_date = $this->input->post('item-expiry-date');
        $item_data = [];
        for ($i = 0; $i < count($item_ids); $i++) {
            $item_data[] = [
                'item_id' => $item_ids[$i],
                'item_unique' => $item_unique[$i],
                'item_serial' => $item_serials[$i],
                'item_qty' => $item_qtys[$i],
                'item_cost' => $item_costs[$i],
                'item-expiry-date' => $expiry_date[$i],
            ];
        }
        return $item_data;
    }

    public function add_grn($grn_data, $grn_items, $created_by) {
        $date = date('y-m-d h:i:s');
        $creation_data = ['enabled' => 1, 'created_by' => $created_by, 'created_on' => $date, 'lastupdated_by' => $created_by, 'lastupdated_on' => $date];
        $this->db->trans_start();
        $res = $this->db->insert('grn', array_merge([
            'ref_number' => $grn_data['grn-ref'],
            'supplier' => $grn_data['supplier'],
            'date' => $grn_data['grn-date'],
            'total' => $grn_data['grn-total'],
            'vehicle_number' => $grn_data['grn-vahicle'],
            'driver' => $grn_data['grn-driver'],
            'unloading' => $grn_data['grn-unloading'],
            'tips' => $grn_data['grn-tips'],
            'location' => $grn_data['location']
                        ], $creation_data));
        if ($res) {
            $grn_id = $this->db->insert_id();
            $res2 = $this->add_grn_items($grn_id, $grn_items, $creation_data);
            if ($res2 != FALSE) {
                $this->db->trans_complete();
                return $grn_id;
            }
        }
        $this->db->trans_rollback();
        return FALSE;
    }

    private function add_grn_items($grn_id, $grn_items, $creation_data) {
        $ok = TRUE;
        foreach ($grn_items as $item) {
            $res = $this->db->insert('grn_item', array_merge([
                'grn_id' => $grn_id,
                'item_id' => $item['item_id'],
                'item_serial' => $item['item_serial'],
                'item_qty' => $item['item_qty'],
                'item_cost' => $item['item_cost'],
                'expiry_date' => $item['item-expiry-date']
                            ], $creation_data));
            if ($res == FALSE) {
                $ok = FALSE;
                break;
            }
        }
        return $ok;
    }

    public function get_all_grns($enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db
                        ->select('g.*,s.name as supplier_name, u.name as created_user')
                        ->from('grn g')
                        ->join('supplier s', 'g.supplier = s.id')
                        ->join('sed_sys_user u', 'g.created_by = u.id')
                        ->get()->result_array();
        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['items'] = $this->db->get_where('grn_item', ['grn_id' => $res[$i]['id']]);
            }
            return $res;
        }
        return FALSE;
    }

    public function is_item_code_exists($item_code) {
        $this->db->like('code', $item_code);
        $res = $this->db->get('item')->result_array();
        return count($res) > 0;
    }

    public function all_item_units($enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db->get('unit')->result_array();
        return $res;
    }

    public function get_grn_by_id($grn_id) {
        $res = $this->db
                        ->select('g.*,s.name as supplier_name, u.name as created_user')
                        ->from('grn g')
                        ->join('supplier s', 'g.supplier = s.id')
                        ->join('sed_sys_user u', 'g.created_by = u.id')
                        ->where('g.id', $grn_id)
                        ->get()->result_array();
        if (count($res) > 0) {
            $grn = $res[0];
            $grn['items'] = $this->get_grn_items_by_grn_id($grn['id']);
            return $grn;
        }
        return FALSE;
    }

    public function get_grn_items_by_grn_id($grn_id) {
        $q = str_replace('@grn_id', $grn_id, $this->grn_item_detail);
        $res = $this->db->query($q)->result_array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['stock_in_hand'] = $this->get_stock_in_hand_by_item_id($res[$i]['id']);
        }
        return $res;
    }

    /**
     * This function is incomplete.
     * Sold amount has to be deducted from recieved amount.
     * Query also should be changed
     */
    public function get_stock_in_hand_by_item_id($grn_item_id) {
        $q = str_replace('@item_id', $grn_item_id, $this->stock_in_hand);
        $res = $this->db->query($q)->result_array();
        if (count($res) > 0 && isset($res[0]['recieved_qty'])) {
            return $res[0]['recieved_qty'];
        }
        return 0;
    }

    public function get_grn_history_by_item_id($item_id) {
        $q = "SELECT 
                    gi.*, g.date, g.ref_number, br.name as location_name
                FROM
                    grn_item gi
                        JOIN
                    grn g ON (gi.grn_id = g.id)
                                LEFT JOIN branch br on(g.location = br.id)
                WHERE
                    g.enabled = 1 AND gi.enabled = 1
                    AND gi.item_id = $item_id "
                . " order by g.date desc, g.id desc";
        $res = $this->db->query($q)->result_array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['stock_in_hand'] = $this->get_stock_in_hand_by_item_id($item_id);
        }
        return $res;
        return $res;
    }

    public function get_post_images($id, $is_model = FALSE) {
        $images = [];
        $image_id = $this->input->post('image-id');
        $file_name = $this->input->post('hq-file-name');
        $zoom_file_name = $this->input->post('zoom-file-name');
        $thumbnail_file_name = $this->input->post('thumbnail-file-name');
        $image_existing = $this->input->post('image-existing');
        $image_enabled = $this->input->post('image-enabled');
        $preferred_image = $this->input->post('preferred-image');
        if (count($file_name) > 0) {
            for ($i = 0; $i < count($image_id); $i++) {
                $ar = [
                    'image_id' => $image_id[$i],
                    'file_name' => $file_name[$i],
                    'zoom_file_name' => $zoom_file_name[$i],
                    'thumbnail_file_name' => $thumbnail_file_name[$i],
                    'image_existing' => $image_existing[$i],
                    'image_enabled' => $image_enabled[$i],
                    'preferred_image' => $preferred_image[$i],
                ];
                if ($is_model) {
                    $ar['model_id'] = $id;
                } else {
                    $ar['item_id'] = $id;
                }
                $images[] = $ar;
            }
        }
        return $images;
    }

    public function update_images($table, $images, $created_by) {
        $update_data = ['lastupdated_on' => date('Y-m-d'), 'lastupdated_by' => $created_by];
        $create_data = ['created_on' => date('Y-m-d'), 'created_by' => $created_by];
        $ok = TRUE;
        foreach ($images as $img) {
            $existing = $img['image_existing'];
            unset($img['image_existing']);
            if ($existing == 0) {//insert image
                $ok = $this->db->insert($table, array_merge([
                    'item_id' => $img['item_id'],
                    'image_name' => $img['file_name'],
                    'zoom_image_name' => $img['zoom_file_name'],
                    'thumbnail_image_name' => $img['thumbnail_file_name'],
                    'enabled' => $img['image_enabled'],
                    'is_preferred_image' => $img['preferred_image']
                                ], $create_data, $update_data));
            } else {//update image
                $this->db->where('id', $img['image_id']);
                unset($img['image_id']);
                $ok = $this->db->update($table, array_merge([
                    'enabled' => $img['image_enabled'],
                    'is_preferred_image' => $img['preferred_image']
                                ], $update_data));
            }
            if (!$ok) {
                break;
            }
        }
        return $ok;
    }

    public function update_model_images($images, $created_by) {
        $update_data = ['lastupdated_on' => date('Y-m-d'), 'lastupdated_by' => $created_by];
        $create_data = ['created_on' => date('Y-m-d'), 'created_by' => $created_by];
        $ok = TRUE;
        foreach ($images as $img) {
            $existing = $img['image_existing'];
            unset($img['image_existing']);
            if ($existing == 0) {//insert image
                $ok = $this->db->insert('model_image', array_merge([
                    'model_id' => $img['model_id'],
                    'image_name' => $img['file_name'],
                    'zoom_image_name' => $img['zoom_file_name'],
                    'thumbnail_image_name' => $img['thumbnail_file_name'],
                    'enabled' => $img['image_enabled'],
                    'is_preferred_image' => $img['preferred_image']
                                ], $create_data, $update_data));
            } else {//update image
                $this->db->where('id', $img['image_id']);
                unset($img['image_id']);
                $ok = $this->db->update('model_image', array_merge([
                    'enabled' => $img['image_enabled'],
                    'is_preferred_image' => $img['preferred_image']
                                ], $update_data));
            }
            if (!$ok) {
                break;
            }
        }
        return $ok;
    }

    public function get_single_item_image($item_id, $holder_size = '266x150') {
        $image_path = '';
        $images = $this->db->get_where('item_image', ['item_id' => $item_id, 'enabled' => 1])->result_array();
        if (count($images) > 0) {
            $preferred_image = NULL;
            foreach ($images as $img) {
                if ($img['is_preferred_image'] == 1) {
                    $preferred_image = $img['image_name'];
                    break;
                }
            }
            if ($preferred_image == NULL) {
                $preferred_image = $images[0]['image_name'];
            }
            $image_path = base_url('public/runningimages/item/image/' . $preferred_image);
        } else {
            $image_path = "holder.js/$holder_size?random=no";
        }
        return $image_path;
    }

    public function add_selling_price($grn_item_id, $item_id, $effective_date, $price, $created_by) {
        $date = date('Y-m-d h:i:s');
        $res = $this->db->insert('item_selling_price', [
            'item_id' => $item_id,
            'grn_item_id' => $grn_item_id,
            'effective_date' => $effective_date,
            'price' => $price,
            'created_by' => $created_by,
            'created_on' => $date,
            'lastupdated_by' => $created_by,
            'lastupdated_on' => $date
        ]);
        return $res;
    }

    public function get_price_history_by_grn_item_id($grn_item_id) {
        $res = $this->db
                        ->select("isp.id, isp.item_id, isp.grn_item_id, isp.price, DATE_FORMAT(isp.effective_date,'%Y-%m-%d %h:%i %p') as effective_from , u.name as created_user, DATE_FORMAT(isp.created_on,'%Y-%m-%d %h:%i %p') as created_date")
                        ->from('item_selling_price isp')
                        ->join('sed_sys_user u', 'isp.created_by=u.id')
                        ->where('isp.grn_item_id', $grn_item_id)
                        ->where('isp.enabled', 1)
                        ->get()->result_array();
        return $res;
    }

    public function get_model_images($model_id, $enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db->get_where('model_image', ['model_id' => $model_id])->result_array();
        return $res;
    }

    public function get_single_model_image($model_id, $holder_size = '266x150') {
        $image_path = '';
        $images = $this->get_model_images($model_id, 1);
        if (count($images) > 0) {
            $preferred_image = NULL;
            foreach ($images as $img) {
                if ($img['is_preferred_image'] == 1) {
                    $preferred_image = $img['image_name'];
                    break;
                }
            }
            if ($preferred_image == NULL) {
                $preferred_image = $images[0]['image_name'];
            }
            $image_path = base_url('public/runningimages/model/image/' . $preferred_image);
        } else {
//            $image_path = base_url('public/images/no-image.png');
            $image_path = "holder.js/$holder_size?random=no";
        }
        return $image_path;
    }

    public function get_list_type_fields($enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db->get_where('field', ['field_type' => 'LIST'])->result_array();
        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['values'] = $this->get_field_values($res[$i]['id'], 1);
            }
            return $res;
        }
        return FALSE;
    }

    public function get_field_values($field_id, $enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db->get_where('field_value', ['field_id' => $field_id])->result_array();
        return $res;
    }

    public function get_field_values_by_model_id($field_id, $model_id) {
        $sql = "SELECT 
                    fv.*, mfv.enabled as selected, mfv.model_id IS NOT NULL as existing
                FROM
                    field_value fv
                        LEFT JOIN
                    model_field_value mfv ON (mfv.field_value_id = fv.id AND mfv.model_id = $model_id)
                WHERE
                    field_id = $field_id";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function add_model_field($model_id, $field_id, $created_by) {
        return $this->db->insert('model_field', array(
                    'model_id' => $model_id,
                    'field_id' => $field_id,
                    'created_by' => $created_by,
                    'enabled' => 1,
                    'created_on' => date('Y-m-d h:i:s'),
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => date('Y-m-d h:i:s'),
        ));
    }

    public function get_model_fields($model_id) {
        $res = $this->db
                        ->select('mf.*, f.field_name, fv.value')
                        ->from('model_field mf')
                        ->join('field f', 'mf.field_id = f.id')
                        ->join('field_value fv', 'mf.value_id = fv.id', 'left')
                        ->where('mf.model_id', $model_id)
//                ->where('mf.enabled', 1)
//                ->where('f.enabled', 1)
                        ->get()->result_array();
        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['values'] = $this->get_field_values($res[$i]['field_id'], 1);
            }
            return $res;
        }
        return FALSE;
    }

    public function select_model_field_values($model_fied_ids, $model_fied_enabled, $model_fied_values, $created_by) {
        $ok = TRUE;
        for ($i = 0; $i < count($model_fied_ids); $i++) {
            if ($model_fied_enabled[$i] == 1) {
                $this->db->where('id', $model_fied_ids[$i]);
                $ok = $this->db->update('model_field', [
                    'value_id' => $model_fied_values[$i],
                    'lastupdated_by' => $created_by,
                    'lastupdated_on' => date('Y-m-d h:i:s'),
                ]);
                if ($ok == FALSE) {
                    break;
                }
            }
        }
        return $ok;
    }

    public function get_items_by_model_id($model_id, $enabled = NULL) {
        if ($enabled != NULL) {
            $this->db->where('enabled', $enabled);
        }
        $res = $this->db->get_where('item', ['model_id' => $model_id])->result_array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['fields'] = $this->get_product_fields_by_model_id($res[$i]['model_id']);
        }
        return $res;
    }

    public function get_item_product_field_values_id($item_id) {
        $sql = "SELECT 
                    ifv.*, f.field_name, f.field_type, f.html_id
                FROM
                    item_field_value AS ifv
                        JOIN
                    field f ON (ifv.field_id = f.id)
                WHERE
                    ifv.item_id = $item_id
                    AND ifv.enabled = 1
                    AND f.field_type = 'LIST'";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function get_item_price($item_id) {
        $sql = "SELECT *
                FROM item_selling_price isp
                WHERE
                    isp.item_id = $item_id
                    AND isp.effective_date < now()
                ORDER BY isp.effective_date desc
                LIMIT 1";
        $res = $this->db->query($sql)->result_array();
        if (count($res) > 0) {
            return $res[0]['price'];
        }
        return NULL;
    }

//////////////// Query Section 
    private $grn_item_detail = "
        SELECT 
            gi.*,
            it.code,
            it.name AS item_name,
            COALESCE((SELECT 
                            isp.price
                        FROM
                            item_selling_price isp
                        WHERE
                            isp.enabled = 1
                                AND isp.grn_item_id = gi.id
                                AND NOW() > isp.effective_date
                        ORDER BY isp.effective_date DESC
                        LIMIT 1),
                    0) AS selling_price
        FROM
            grn_item gi
                JOIN
            grn g ON (gi.grn_id = g.id)
                JOIN
            item it ON (gi.item_id = it.id)
        WHERE
            g.enabled = 1 AND gi.enabled = 1 AND gi.grn_id = @grn_id";
    private $stock_in_hand = "SELECT 
                                    sum(gi.item_qty) as recieved_qty
                                FROM
                                    grn_item gi
                                        JOIN
                                    grn g ON (gi.grn_id = g.id)
                                WHERE
                                    gi.enabled = 1 AND g.enabled = 1 AND gi.id = @item_id";

}
