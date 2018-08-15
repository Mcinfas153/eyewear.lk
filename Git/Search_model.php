<?php

class Search_model extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Itemmodel');
        $this->load->helper('cookie');
    }

    public function model_search($product, $category, $arrang_by, $arrange_mode) {
        if ($product != NULL) {

            $sql = "SELECT m.* FROM model m JOIN metadata_product p ON (m.product_id = p.id) WHERE m.enabled = 1 AND p.enabled = 1 AND m.product_id = $product ";
            if ($category != NULL) {
                $sql .= " AND (m.sub_category = $category OR m.sub_category IN (SELECT id FROM category c WHERE c.parent_category_id = $category))";
            }
            if ($arrang_by != NULL && $arrange_mode != NULL) {
                if ($arrang_by == 'name') {
                    $sql .= " ORDER BY m.model_name " . $arrange_mode;
                } else if ($arrang_by == 'price') {
                    $sql .= " ORDER BY m.display_price_sale " . $arrange_mode;
                }
            }
            $models = $this->db->query($sql)->result_array();
            if (count($models) > 0) {
                for ($i = 0; $i < count($models); $i++) {
                    $models[$i]['image'] = $this->Itemmodel->get_single_model_image($models[$i]['id']);
                    $models[$i]['brand'] = $this->get_model_brand($models[$i]['id']);
                }
            }
            return $models;
        }
        return FALSE;
    }
    
    public function get_model_brand($model_id){
        $brand_field_id = BRAND_FIELD_ID;
        $sql = "SELECT * FROM field_value fv where fv.field_id = $brand_field_id AND fv.id IN (SELECT mf.value_id FROM model_field mf WHERE mf.model_id = $model_id  AND mf.value_id IS NOT NULL)";
        $res = $this->db->query($sql)->result_array();
        if(count($res) > 0){
            return $res[0]['value'];
        }
        return '';
    }

    public function add_recent_model($model_id) {
        $cookie = get_cookie('recent_models');
        if ($cookie == NULL) {
            //create cookie
            set_cookie('recent_models', json_encode([$model_id]), "345600");
        } else {
            $current_models = json_decode($cookie);
            if (!in_array($model_id, $current_models)) {
                if (count($current_models) > 4) {
                    array_shift($current_models);
                }
                $current_models[] = $model_id;
                set_cookie('recent_models', json_encode($current_models), "345600");
            }
        }
    }

    public function add_recent_item($item_id) {
        $cookie = get_cookie('recent_items');
        if ($cookie == NULL) {
            //create cookie
            set_cookie('recent_items', json_encode([$item_id]), "345600");
        } else {
            $current_items = json_decode($cookie);
            if (!in_array($item_id, $current_items)) {
                if (count($current_items) > 4) {
                    array_shift($current_items);
                }
                $current_items[] = $item_id;
                set_cookie('recent_items', json_encode($current_items), "345600");
            }
        }
    }

    public function get_recent_models() {
        $recent_models = [];
        $cookie = get_cookie('recent_models');
        if ($cookie != NULL) {
            $models = json_decode($cookie);
            foreach ($models as $k => $m) {
                $recent_models[] = $this->Itemmodel->get_model_by_id($m, '215x120');
            }
        }
        return $recent_models;
    }

    public function get_recent_items() {
        $recent_items = [];
        $cookie = get_cookie('recent_items');
        if ($cookie != NULL) {
            $items = json_decode($cookie);
            foreach ($items as $k => $m) {
                $recent_items[] = $this->Itemmodel->get_item_by_id($m, '215x120');
            }
        }
        return $recent_items;
    }

    public function set_cookie_value($name, $value, $defult_value) {
        $current_value = get_cookie($name);
        $val = $defult_value;
        if ($current_value != NULL) {
            $val = $value;
        }
        setcookie($name, $val);
        return $val;
    }

    public function get_related_models($sub_category, $model_id, $nof_models = 5) {
        $related_models = [];
        $sql = "SELECT m.id
                FROM model m
                WHERE m.enabled = 1
                    AND m.id <> $model_id
                    AND m.sub_category IN (SELECT 
                        sc.id
                    FROM category sc
                    WHERE sc.enabled = 1
                        AND sc.parent_category_id = (SELECT 
                            cat.parent_category_id
                        FROM
                            category cat
                        WHERE
                            cat.id = $sub_category))
                ORDER BY rand()
                LIMIT $nof_models";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as $row) {
            $related_models[] = $this->Itemmodel->get_model_by_id($row['id'], '215x120');
        }
        return $related_models;
    }

    public function get_related_items($sub_category, $model_id, $nof_items = 5) {
        $related_items = [];
        $sql = "SELECT it.id
                FROM item it
                JOIN model m ON (it.model_id = m.id)
                WHERE m.enabled = 1
                    AND m.id <> $model_id
                    AND m.sub_category IN (SELECT 
                        sc.id
                    FROM category sc
                    WHERE sc.enabled = 1
                        AND sc.parent_category_id = (SELECT 
                            cat.parent_category_id
                        FROM
                            category cat
                        WHERE
                            cat.id = $sub_category))
                ORDER BY rand()
                LIMIT $nof_items";
        $res = $this->db->query($sql)->result_array();
        foreach ($res as $row) {
            $related_items[] = $this->Itemmodel->get_item_by_id($row['id'], '215x120');
        }
        return $related_items;
    }

    public function get_pagination($total, $per_page, $url) {
        $this->load->library('pagination');
        $config['base_url'] = $url;
        $config['total_rows'] = $total;
        $config['per_page'] = $per_page;
        $this->pagination->initialize($config);

        $links = $this->pagination->create_links();
        return $links;
    }

    public function get_selected_product_field_values($model_id) {
        $field_values = [];
        $product_fields = $this->Itemmodel->get_product_fields_by_model_id($model_id);
        if (count($product_fields) > 0) {
            foreach ($product_fields as $field) {
                if ($field['field_type'] == 'LIST') {
                    $field_values[$field['html_id']] = $this->input->post($field['html_id']);
                }
            }
        }
        return $field_values;
    }

    public function get_matching_item($model_id, $product_fields) {
        $selected_item = FALSE;
        $model_items = $this->Itemmodel->get_items_by_model_id($model_id, 1);
        if (count($model_items) > 0) {
            foreach ($model_items as $item) {
                if ($this->is_item_matching($item, $product_fields)) {
                    $selected_item = $item;
                }
            }
        }
        return $selected_item;
    }

    private function is_item_matching($item, $product_fields) {
        $existing_field_count = $matching_field_count = 0;
        $item_product_field_values = $this->Itemmodel->get_item_product_field_values_id($item['id']);
        foreach ($product_fields as $field => $value) {
            foreach ($item_product_field_values as $fv) {
                if ($fv['html_id'] == $field) {
                    $existing_field_count++;
                    if ($fv['value'] == $value) {
                        $matching_field_count++;
                    }
                }
            }
        }
        if ($existing_field_count > 0) {
            return $matching_field_count == $existing_field_count;
        }
        return FALSE;
    }

    public function get_shopping_cart_data() {
        $cart_items_json = get_cookie('shopping_cart');
        $cart_items = [];
        if ($cart_items_json != NULL) {
            $cart_items = json_decode($cart_items_json, TRUE);
            for ($i = 0; $i < count($cart_items); $i++) {
                $cart_items[$i]['item_data'] = $this->Itemmodel->get_item_by_id($cart_items[$i]['item'], '215x120');
            }
        }
        return $cart_items;
    }

    public function get_taxes() {
        $taxes = $this->db
                        ->from('tax_master')
                        ->where('enabled', 1)
                        ->order_by('apply_order')
                        ->get()->result_array();
        return $taxes;
    }

    public function empty_cart() {
        delete_cookie('shopping_cart');
    }
}
