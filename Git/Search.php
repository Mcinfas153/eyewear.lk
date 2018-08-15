<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Search_model');
        $this->load->model('Itemmodel');
        $this->load->model('Product_model');
        $this->load->model('Category_model');
        $this->load->model('Common');
        $this->load->helper('cookie');
    }

    public function index() {
        $product = $this->input->get('product');
        $category = $this->input->get('category');
        $view = $this->input->get('view');
        $arrange_by = $this->input->get('arrange_by');
        $arrange_mode = $this->input->get('arrange_mode');
        $show_amount = $this->input->get('show_amount');
        $models = [];
        $fields = [];
        if ($view != NULL && in_array($view, ['grid', 'list'])) {
            $view = $this->Search_model->set_cookie_value('view_mode', $view, 'grid');
        } else {
            $view = get_cookie('view_mode');
            if ($view == NULL) {
                $view = $this->Search_model->set_cookie_value('view_mode', $view, 'grid');
            }
        }
        if ($arrange_by != NULL && in_array($arrange_by, ['price', 'name'])) {
            $arrange_by = $this->Search_model->set_cookie_value('arrange_by', $arrange_by, 'price');
        } else {
            $arrange_by = get_cookie('arrange_by');
            if ($arrange_by == NULL) {
                $arrange_by = $this->Search_model->set_cookie_value('arrange_by', $arrange_by, 'price');
            }
        }
        if ($arrange_mode != NULL && in_array($arrange_mode, ['asc', 'desc'])) {
            $arrange_mode = $this->Search_model->set_cookie_value('arrange_mode', $arrange_mode, 'asc');
        } else {
            $arrange_mode = get_cookie('arrange_mode');
            if ($arrange_mode == NULL) {
                $arrange_mode = $this->Search_model->set_cookie_value('arrange_mode', $arrange_mode, 'asc');
            }
        }
        if ($show_amount != NULL && in_array(intval($show_amount), [12, 24, 36])) {
            $show_amount = $this->Search_model->set_cookie_value('show_amount', $show_amount, 12);
        } else {
            $show_amount = get_cookie('show_amount');
            if ($show_amount == NULL) {
                $show_amount = $this->Search_model->set_cookie_value('show_amount', $show_amount, 12);
            }
        }
        $data['view'] = $view;
        $data['arrange_by'] = $arrange_by;
        $data['arrange_mode'] = $arrange_mode;
        $data['show_amount'] = $show_amount;
        $url = base_url('search');
        if ($product != NULL) {
            $url .= '?product=' . intval($product);
            $data['product'] = $this->Product_model->get_product_by_id($product);
            $models = $this->Search_model->model_search($product, $category, $arrange_by, $arrange_mode);
            $fields = $this->Product_model->get_fields_by_product($product);
        }
        $data['pagination'] = $this->Search_model->get_pagination(count($models), $show_amount, $url);
        if ($category != NULL) {
            $url .= '&category=' . intval($category);
        }
        $data['url'] = $url;
        $data['models'] = $models;
        $data['fields'] = $fields;
        Search::AddAuditTrailEntry(AUDITTRAIL_SEARCH, 'Model', "Product ID:" . $product);
        $data['main_content'] = 'site/search/model_list';
        $this->load->view('templates/site_template', $data);
    }

    public function model_view($model_id = NULL) {
        if ($model_id != NULL) {
            $model = $this->Itemmodel->get_model_by_id($model_id, NULL);
            if ($model != FALSE) {
                $data['recent_items'] = $this->Search_model->get_recent_items();
                $data['related_items'] = $this->Search_model->get_related_items($model['sub_category'], $model['id'], 5);
//                $data['recent_models'] = $this->Search_model->get_recent_models();
//                $data['related_models'] = $this->Search_model->get_related_models($model['sub_category'], $model_id, 5);
                $this->Search_model->add_recent_model($model_id);
                $data['product'] = $this->Product_model->get_product_by_id($model['product_id']);
                $data['model'] = $model;
                $data['all_model_images'] = $this->Itemmodel->get_model_images($model_id, 1);

                $data['category_tree'] = $this->Category_model->get_category_by_subcategory($model['sub_category']);
                $data['model_fields'] = $this->Itemmodel->get_model_fields($model['id']);
                $data['product_fields'] = $this->Itemmodel->get_product_fields_by_model_id($model['id']);
                Search::AddAuditTrailEntry(AUDITTRAIL_VIEW, 'Model', "Model ID:" . $model_id);
                $data['main_content'] = 'site/search/model_view';
                $this->load->view('templates/site_template', $data);
            }
        }
    }

    public function search_stock() {
        $model_id = $this->input->post('model-id');
        if ($model_id != NULL) {
            $model = $this->Itemmodel->get_model_by_id($model_id, NULL);
            //get fields by model id
            $fields = $this->Itemmodel->get_product_fields_by_model_id_backend($model_id);
            $items = $this->Itemmodel->get_product_fields_by_model_id_backend($model_id);
            foreach ($items as $item) {
                foreach ($fields as $field) {
                    if ($field['field_type'] == 'LIST' && isset($_POST[$field['html_id']])) {
                        $search_value = $this->input->post($field['html_id']);
                        //get selected item values
                        $values = $this->Itemmodel->get_item_field_values($item['id']);
                        foreach ($values as $val) {
                            if ($val['field_id'] == $field['id']) {
                                if ($val['value'] == $search_value) {
                                    redirect(base_url('search/item_view/'.$item['id']));
                                    return;
                                }
                            }
                        }
                    }
                }
            }
            redirect(base_url('sederror/page/717'));
        }
    }

    public function item_view($item_id = NULL) {
        $item = $model = FALSE;
        $data = [];
        if ($item_id == NULL) {
            $model_id = $this->input->post('model-id');
            if ($model_id != NULL) {
                $model = $this->Itemmodel->get_model_by_id($model_id, NULL);
                $data['model'] = $model;
                $data['category_tree'] = $this->Category_model->get_category_by_subcategory($model['sub_category']);
                $data['model_fields'] = $this->Itemmodel->get_model_fields($model['id']);
                $data['product'] = $this->Product_model->get_product_by_id($model['product_id']);
                $product_fields = $this->Itemmodel->get_product_fields_by_model_id($model['id']);
                $product_field_values = $this->Search_model->get_selected_product_field_values($model_id, $product_fields);
                $data['product_fields'] = $product_fields;
                if ($model != FALSE) {
                    $item = $this->Search_model->get_matching_item($model_id, $product_field_values);
                    $data['item'] = $item;
                }
            }
        } else if (intval($item_id) > 0) {
            $item = $this->Itemmodel->get_item_by_id($item_id, NULL);
            $model = $this->Itemmodel->get_model_by_id($item['model_id'], NULL);
            $data['model'] = $model;
            $data['item'] = $item;
            $data['category_tree'] = $this->Category_model->get_category_by_subcategory($model['sub_category']);
            $data['model_fields'] = $this->Itemmodel->get_model_fields($model['id']);
            $data['product'] = $this->Product_model->get_product_by_id($model['product_id']);
            $product_fields = $this->Itemmodel->get_product_fields_by_model_id($model['id']);
            $product_field_values = $this->Search_model->get_selected_product_field_values($model['id'], $product_fields);
            $data['product_fields'] = $product_fields;
        }
        if ($item != FALSE) {
            $this->Search_model->add_recent_item($item['id']);
            $data['recent_items'] = $this->Search_model->get_recent_items();
            $data['related_items'] = $this->Search_model->get_related_items($model['sub_category'], $model['id'], 5);
            $data['item_field_values'] = $this->Itemmodel->get_item_field_values($item['id']);
            $data['item_selling_price'] = $this->Itemmodel->get_item_price($item['id']);
            $data['all_item_images'] = $this->Itemmodel->get_item_images($item['id'], 1);
            Search::AddAuditTrailEntry(AUDITTRAIL_VIEW, 'Item', "Item ID:" . $item['id']);
            $data['main_content'] = 'site/search/item_view';
            $this->load->view('templates/site_template', $data);
        } else {
            redirect(base_url('error/page/6'));
        }
    }

}
