<?php
defined( 'ABSPATH' ) OR exit;

/*
 * Plugin Name: Heyday - Site Search
 * Plugin URI: https://heyday.io/
 * Description: Elevate your website with HeyDay's AI-powered search box.
 * Version: 1.0.0
 * Author: Heyday - Site Search
 * Author URI: https://heyday.io/about.html
 * Text Domain: heyday
 * License: GPL v2 or later
 * License URI: https://heyday.io/terms.html
 */

define('heydayWebPush_HEYDAY_OPTIONS', 'heyday-search');
include plugin_dir_path(__FILE__) . '/admin/'.heydayWebPush_HEYDAY_OPTIONS.'.php';
register_activation_hook(__FILE__, ['heydayWebSearch_Heyday_search_Plugin', 'on_activation']);
add_action('activated_plugin', ['heydayWebSearch_Heyday_search_Plugin', 'redir'] );
register_uninstall_hook(__FILE__, ['heydayWebSearch_Heyday_search_Plugin', 'on_uninstall']);
register_deactivation_hook( __FILE__, ['heydayWebSearch_Heyday_search_Plugin', 'on_deactivation']);
add_action('plugins_loaded', ['heydayWebSearch_Heyday_search_Plugin', 'init' ]);  
add_action('save_post', 'heyday_on_post_changed', 10, 3 );
add_action('woocommerce_product_set_stock_status', 'heyday_on_product_stock_status_changed', 10, 3);

add_action('rest_api_init', 'register_all_products');

function register_all_products() {
    register_rest_route('heydayplugin/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'get_all_products',
        'permission_callback' => '__return_true',
        'args' => array(
            'page' => array(
                'required' => false,
                'type' => 'integer',
            ),
            'per_page' => array(
                'required' => false,
                'type' => 'integer',
            ),
        ),
    ));
}

function get_all_products($request) {
    $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
    $selected_categories = array();
    $selected_attributes = array();
    $fieldsPriority = array();
    $posts = array();
    
    if(isset($heydayPluginOptions['heyday_selected_categories'])){
        $selected_categories = $heydayPluginOptions['heyday_selected_categories'];
    }
    if(isset($heydayPluginOptions['heyday_selected_attributes'])){
        $selected_attributes = $heydayPluginOptions['heyday_selected_attributes'];
    }
    if (isset($heydayPluginOptions['heyday_fields_priority'])) {
        $fieldsPriority = $heydayPluginOptions['heyday_fields_priority'];
    }
    $params = $request->get_params();
    $page = isset($params['page']) ? $params['page'] : 1;
    $per_page = isset($params['per_page']) ? $params['per_page'] : 10000;
    
    if($per_page > 10000)
        $per_page = 10000;

    $payload = '';
    $post_type_args = array(
        'public'   => true
     );
    $post_types = get_post_types( $post_type_args );
    unset($post_types["product"]);

    if ($per_page > 100) {
        $limit = 100;
        $offset = 0;
    
        while ($offset < $per_page) {
            $fetched_posts = get_posts(array(
                'post_type'      => $post_types,
                'post_status'    => array('publish', 'pending', 'future', 'private', 'inherit', 'trash'),
                'posts_per_page' => $limit,
                'offset'         => $offset,
                'paged'          => $page,
                'no_found_rows'  => true,
            ));
            if (empty($fetched_posts)) {
                break;
            }
            $posts = array_merge($posts, $fetched_posts);
            $offset += $limit;
        }
    }else{
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => array('publish', 'pending','future', 'private', 'inherit', 'trash'),
            'posts_per_page' => $per_page,
            'paged' => $page,
            'no_found_rows'  => true,
        ));
    }
    foreach ($posts as $post) {
        $content = $post->post_content;
        $content = wp_strip_all_tags( $content );
        $pattern = '/[\r\n]+|&nbsp;/';
        $content = preg_replace($pattern, '', $content);
        $tags = get_the_tags($post->ID);
        if($tags ){
            $tag_names = array();
            foreach($tags as $tag){
                $tag_names[] = $tag->name;
            }
            $tags = implode(', ',$tag_names);
        }
        $author_id = get_post_field ('post_author', $post->ID);
        $author = get_the_author_meta( 'display_name' , $author_id ); 
        $category_names = array();
        $post_categories = get_the_category($post->ID);
        if(!empty($post_categories)){
            $category_names = wp_list_pluck($post_categories, 'name');
        }

        $j = ['pageData'=>[
            'id'=>$post->ID,
            'title' => $post->post_title,
            'postType'=>$post->post_type,
            'description' => strip_tags(get_the_excerpt($post)),
            'modifyTime' => $post->post_modified,
            'creationTime' => strtotime($post->post_date),
            'url' => get_permalink($post->ID),
            'author'=>$author,
            'status' => $post->post_status,
            'img' => get_the_post_thumbnail_url($post->ID),
            'category_names' => $category_names,
            'section' => $tags,
            ],
            
            'docBody' => $content,
            ];
        $payload .= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ."\r\n";
    }

    $products = array();
    if ($per_page > 100) {
        $limit = 100;
        $offset = 0;
    
        while ($offset < $per_page) {
            $fetched_posts = get_posts(array(
                'post_type' => 'product',
                'post_status'    => array('publish', 'pending', 'future', 'private', 'inherit', 'trash'),
                'posts_per_page' => $limit,
                'offset'         => $offset,
                'paged'          => $page,
                'no_found_rows'  => true,
            ));
            if (empty($fetched_posts)) {
                break;
            }
            $products = array_merge($products, $fetched_posts);
            $offset += $limit;
        }
    }else{
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => array('publish', 'pending','future', 'private', 'inherit', 'trash'),
            'posts_per_page' => $per_page,
            'paged' => $page,
            'no_found_rows'  => true,
        ));
    }
 
    foreach ($products as $post) {
        $product_obj = wc_get_product($post->ID);
        $post_id = $post->ID;

        $product_gallery = get_post_meta($post_id, '_product_image_gallery', true);
        $product_gallery_ids = explode(',', $product_gallery);
        $product_gallery_urls = array();

        foreach ($product_gallery_ids as $product_gallery_id) {
            $product_gallery_urls[] = wp_get_attachment_url($product_gallery_id);
        }

        $content = $post->post_content;
        $content = wp_strip_all_tags( $content );
        $pattern = '/[\r\n]+|&nbsp;/';
        $content = preg_replace($pattern, '', $content);

        $categories = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'names'));
        $categories = array_filter($categories, function($category) use ($selected_categories) {
            return in_array($category, $selected_categories);
        });
        
        $attributes = $product_obj->get_attributes();
        $attributes_data = array();
        
        foreach ($attributes as $attribute) {
            $attribute_slug = $attribute->get_name();
            $attribute_name = wc_attribute_label($attribute_slug);
            if ($attribute->is_taxonomy()) {
                $attribute_taxonomy = $attribute->get_taxonomy(); 
                $attribute_values = wc_get_product_terms($product_obj->get_id(), $attribute_taxonomy, array('fields' => 'names'));
                $attributes_data[$attribute_name] = $attribute_values;
           
            } else {
                $attribute_options = $attribute->get_options();
                $attributes_data[$attribute_name] = $attribute_options;
            }
        }
        foreach ($attributes_data as $key => $value) {
            if (substr($key, 0, 3) === 'pa_') {
                $new_key = str_replace('pa_', '', $key);
                $attributes_data[$new_key] = $value;
                unset($attributes_data[$key]);
            }
        }

        $sorting_price =$sale_price = $price=0;
        if($product_obj->get_regular_price()){
            $price = preg_replace('/[^0-9\.]/', '', $product_obj->get_regular_price(),);
            if($product_obj->get_sale_price()){
                $sale_price = preg_replace('/[^0-9\.]/', '', $product_obj->get_sale_price());
                if ((float)$sale_price > (float)$price && (float)$sale_price > 0) {
                    $sale_price = 0;
                }
                else
                $sorting_price = $sale_price;
            }
            else
                $sorting_price = $price;
        }else{
            $price=$product_obj->get_price();
        }
        $reg_price = $product_obj->get_price();
        $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $reg_price, $product_obj);
        if($discount !== false) {
            if((float)$discount < (float)$price){
                $sale_price = $discount;
                $sorting_price = $sale_price;
            }else{
                $sale_price = 0; 
            }
        }
        $displayed_price = wc_get_price_to_display($product_obj);
        $regular_price = $price;
        if ((float)$regular_price !== (float)$displayed_price && (float)$displayed_price < (float)$regular_price) {
            $sale_price = $displayed_price;
            $sorting_price = $displayed_price;
        }else{
            if((float)$regular_price !== (float)$displayed_price){
                $price = $displayed_price;
                $sale_price = 0;
                $sorting_price = $price;
            }
        }
        if((float)$sale_price <= 0){
            $sale_price = 0;
        }
        if($sorting_price == 0){
            $sorting_price = $price;
        }

        $sku = $product_obj->get_sku();

        $custom_label_1 = get_post_meta($post->ID, 'customs_label_1', true);
        $custom_label_2 = get_post_meta($post->ID, 'customs_label_2', true);
        $custom_label_3 = get_post_meta($post->ID, 'customs_label_3', true);
        $product_id = $post->ID;
        if ( taxonomy_exists( 'product_brand' ) ) {
            $brand_terms = wp_get_post_terms( $product_id, 'product_brand' );
            if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
                $brand_term = $brand_terms[0];
                $brand_name = $brand_term->name;
                $brand_link = get_term_link($brand_term);
                if (is_wp_error($brand_link)) {
                    $brand_link = '';
                }
                $thumbnail_id = get_term_meta( $brand_term->term_id, 'thumbnail_id', true );
        
                if ( $thumbnail_id ) {
                    $brand_image_url = wp_get_attachment_url( $thumbnail_id );
                } else {
                    $brand_image_url = '';
                }
        
            } else {
                $brand_name = '';
                $brand_image_url = '';
                $brand_link = ''; 
            }
        
        } else {
            $brand_name = '';
            $brand_image_url = ''; 
            $brand_link = '';
        }
        if (!empty($brand_name) && isset($brand_name)) {
            $attributes_data['brand'] = $brand_name;
        }
        $is_hidden = false;
        $visibility = $product_obj->get_catalog_visibility();
        if ( 'hidden' == $visibility ) {
            $is_hidden = true;
        }

        $short_description = $product_obj->get_short_description();    

        $j = ['pageData'=>[
            'id'=>$post->ID,
            'title' => $post->post_title,
            'postType'=>$post->post_type,
            'description' => strip_tags($content),
            'short_description' => strip_tags($short_description),
            'modifyTime' => strtotime($post->post_modified),
            'creationTime' => strtotime($post->post_date),
            'url' => get_permalink($post->ID),
            'status' => $post->post_status,
            'img' => get_the_post_thumbnail_url($post->ID),
            'shipping' => $product_obj->get_shipping_class(),
            'stock_status' => $product_obj->get_stock_status(),
            'additional_images' => $product_gallery_urls,
            'custom_label_1' => $custom_label_1,
            'custom_label_2'=>$custom_label_2,
            'custom_label_3'=> $custom_label_3,
            'brand_image_url' => $brand_image_url,
            'hide_from_catalog' => $is_hidden,
            'brand_name'=>$brand_name,
            'sale_price' => $sale_price,
            'discount' => $discount,
            'reg_price' => $product_obj->get_regular_price(),
            'display_price' =>  wc_get_price_to_display($product_obj),
            'attribute_array'=> $attributes_data,
            'brand_link'=>$brand_link,
            'categories' => $categories,
            'currency' => html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8'),
            'sku'=> $sku
            ],
        ];
        $payload .= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ."\r\n";
    }
    return rest_ensure_response($payload);
}

function heyday_on_product_stock_status_changed($product_id, $new_status, $old_status) {
    $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
    $selected_categories = array();
    $selected_attributes = array();
    $fieldsPriority = array();
    if(isset($heydayPluginOptions['heyday_selected_categories'])){
        $selected_categories = $heydayPluginOptions['heyday_selected_categories'];
    }
    if(isset($heydayPluginOptions['heyday_selected_attributes'])){
        $selected_attributes = $heydayPluginOptions['heyday_selected_attributes'];
    }
    if (isset($heydayPluginOptions['heyday_fields_priority'])) {
        $fieldsPriority = $heydayPluginOptions['heyday_fields_priority'];
    }
    $payload ='';
    if ($new_status === 'outofstock' && $old_status !== 'outofstock') {
        $product_obj = wc_get_product($product_id);
        $is_hidden = false;
        $visibility = $product_obj->get_catalog_visibility();
        if ( 'hidden' == $visibility ) {
            $is_hidden = true;
        }
        $product_post = get_post($product_id);
        if($product_obj->get_stock_status() == 'outofstock' || $is_hidden || $product_post->post_status != 'publish'){
            $payload = json_encode(['pageData'=>['url'=>get_permalink($product_id), 'delete'=>1]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\r\n";
        }
    } elseif ($new_status === 'instock' && $old_status !== 'instock') {
        $product_obj = wc_get_product($product_id);
        $product_gallery = get_post_meta($product_id, '_product_image_gallery', true);
        $product_gallery_ids = explode(',', $product_gallery);
        $product_gallery_urls = array();
    
        foreach ($product_gallery_ids as $product_gallery_id) {
            $product_gallery_urls[] = wp_get_attachment_url($product_gallery_id);
        }
        $content = $post->post_content;
        $content = wp_strip_all_tags( $content );
        $pattern = '/[\r\n]+|&nbsp;/';
        $content = preg_replace($pattern, '', $content);
    
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
        $categories = array_filter($categories, function($category) use ($selected_categories) {
            return in_array($category, $selected_categories);
        });
        
        $attributes = $product_obj->get_attributes();
        $attributes_data = array();
        
        foreach ($attributes as $attribute) {
            $attribute_slug = $attribute->get_name();
            $attribute_name = wc_attribute_label($attribute_slug);
            if ($attribute->is_taxonomy()) {
                $attribute_taxonomy = $attribute->get_taxonomy(); 
                $attribute_values = wc_get_product_terms($product_obj->get_id(), $attribute_taxonomy, array('fields' => 'names'));
                $attributes_data[$attribute_name] = $attribute_values;
            } else {
                $attribute_options = $attribute->get_options();
                $attributes_data[$attribute_name] = $attribute_options;
            }
        }

        foreach ($attributes_data as $key => $value) {
            if (substr($key, 0, 3) === 'pa_') {
                $new_key = str_replace('pa_', '', $key);
                $attributes_data[$new_key] = $value;
                unset($attributes_data[$key]);
            }
        }
        $sorting_price =$sale_price = $price=0;
        if($product_obj->get_regular_price()){
            $price = preg_replace('/[^0-9\.]/', '', $product_obj->get_regular_price(),);
            if($product_obj->get_sale_price()){
                $sale_price = preg_replace('/[^0-9\.]/', '', $product_obj->get_sale_price());
                if ((float)$sale_price > (float)$price && (float)$sale_price > 0) {
                    $sale_price = 0;
                }
                else
                $sorting_price = $sale_price;
            }
            else
                $sorting_price = $price;
        }else{
            $price=$product_obj->get_price();
        }
        $reg_price = $product_obj->get_price();
        $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $reg_price, $product_obj);
        if($discount !== false) {
            if((float)$discount < (float)$price){
                $sale_price = $discount;
                $sorting_price = $sale_price;
            }else{
                $sale_price = 0; 
            }
        }
        $displayed_price =  wc_get_price_to_display($product_obj);
        $regular_price = $price;
        if ((float)$regular_price !== (float)$displayed_price && (float)$displayed_price < (float)$regular_price) {
            $sale_price = $displayed_price;
            $sorting_price = $displayed_price;
        }else{
            if((float)$regular_price !== (float)$displayed_price){
                $price = $displayed_price;
                $sale_price = 0;
                $sorting_price = $price;
            }
        }
        if((float)$sale_price <= 0){
            $sale_price = 0;
        }
        if($sorting_price == 0){
            $sorting_price = $price;
        }
        $sku = $product_obj->get_sku();
        $custom_label_1 = get_post_meta($product_id, 'customs_label_1', true);
        $custom_label_2 = get_post_meta($product_id, 'customs_label_2', true);
        $custom_label_3 = get_post_meta($product_id, 'customs_label_3', true);

        $structuredFieldPriority = [];

        foreach ($fieldsPriority as $field) {
            $structuredFieldPriority[$field['field']] = $field['priority'];
        }
    
        asort($structuredFieldPriority);
    
        $fullText = [];
        foreach ($structuredFieldPriority as $field => $priority) {
            array_push($fullText, [$field => $priority]);
        }
        $product_id = $product_id;
        $is_hidden = false;
        $visibility = $product_obj->get_catalog_visibility();
        if ( 'hidden' == $visibility ) {
            $is_hidden = true;
        }
        if ( taxonomy_exists( 'product_brand' ) ) {
            $brand_terms = wp_get_post_terms( $product_id, 'product_brand' );
            if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
                $brand_term = $brand_terms[0];
                $brand_name = $brand_term->name;
                $brand_link = get_term_link($brand_term);
                if (is_wp_error($brand_link)) {
                    $brand_link = '';
                }
                $thumbnail_id = get_term_meta( $brand_term->term_id, 'thumbnail_id', true );
        
                if ( $thumbnail_id ) {
                    $brand_image_url = wp_get_attachment_url( $thumbnail_id );
                } else {
                    $brand_image_url = '';
                }
        
            } else {
                $brand_name = '';
                $brand_image_url = '';
                $brand_link = ''; 
            }
        
        } else {
            $brand_name = '';
            $brand_image_url = ''; 
            $brand_link = '';
        }

        if (!empty($brand_name) && isset($brand_name)) {
            $attributes_data['brand'] = $brand_name;
        }
        $product_post = get_post($product_id);
        $short_description = $product_obj->get_short_description(); 
        $j = ['pageData'=>[
            'id' => $product_obj->get_id(),
            'title' => $product_post->post_title,
            'description' => strip_tags($product_post->post_content),
            'short_description' => strip_tags($short_description),
            'modifyTime' => strtotime($product_post->post_modified),
            'creationTime' => strtotime($product_post->post_date),
            'url' => get_permalink($product_id),
            'status' => $product_post->post_status,
            'img' => get_the_post_thumbnail_url($product_id),
            'shipping' => $product_obj->get_shipping_class(),
            'stock_status' => $product_obj->get_stock_status(),
            'additional_images' => $product_gallery_urls,
            'custom_label_1' => $custom_label_1,
            'custom_label_2'=>$custom_label_2,
            'custom_label_3'=> $custom_label_3,
            'brand_image_url' => $brand_image_url,
            'hide_from_catalog' => $is_hidden,
            'brand_name'=>$brand_name,
            'brand_link' => $brand_link,
            'attribute_array'=> $attributes_data,
            'discount' => $discount,
            'reg_price' => $product_obj->get_regular_price(),
            'p_price' =>  $product_obj->get_price(),
            'product' => [
                    'price' => (string)$price,
                    'currency' => html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8'),
            ],
    
            ],
            
            'setIndex'=> [
                'fullText' =>$fullText,
                'categories' => $categories,
                'attributes' => $attributes_data,
                'sorting' => [['price' => (int)(10*(float)$sorting_price) ]],
                'metaData' => [ 'category'=> trim(end($categories)) ,'sku'=>$sku]
            ],
            
        ];
        if($sale_price)
            $j['pageData']['product']['sale_price'] = $sale_price;


        $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $j['setIndex'] = json_encode($j['setIndex'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $s = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $payload .= $s ."\r\n";
        if($is_hidden || $product_post->post_status != 'publish'){
            $payload = json_encode(['pageData'=>['url'=>get_permalink($product_id), 'delete'=>1]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\r\n";
        }
    }
    $affId = 0000;
    $settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
    if(isset($settings['affId']))
    {
        $affId = $settings['affId']; 
    }
    $password = "";
    if(isset($heydayPluginOptions['heyday_user_reg'])){
        $password = $heydayPluginOptions['heyday_user_reg'];
    }
    $api_url = 'http://heyday.io/api/updateIndexer/' . $affId . '/' . $password;

    $request_args = array(
        'body'        => $payload,
        'headers'     => array(
            'Content-Type' => 'text/plain',
            'Expect'       => ''
        ),
    );
    
    $response = wp_remote_post($api_url, $request_args);
    
    if (is_wp_error($response)) {
        echo 'Error: ' . $response->get_error_message();
    } else {
        $result = wp_remote_retrieve_body($response);
        $status = $result . $payload;
    }
}



function heyday_on_post_changed( $post_id, $post ) {
    $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
    $selected_categories = array();
    $selected_attributes = array();
    $fieldsPriority = array();
    if(isset($heydayPluginOptions['heyday_selected_categories'])){
        $selected_categories = $heydayPluginOptions['heyday_selected_categories'];
    }
    if(isset($heydayPluginOptions['heyday_selected_attributes'])){
        $selected_attributes = $heydayPluginOptions['heyday_selected_attributes'];
    }
    if (isset($heydayPluginOptions['heyday_fields_priority'])) {
        $fieldsPriority = $heydayPluginOptions['heyday_fields_priority'];
    }
    $payload ='';
    $selected_post_types = array();
    if(isset($heydayPluginOptions['selected_posts_types'])){
        $selected_post_types = $heydayPluginOptions['selected_posts_types'];
    }
    if($post->post_status == 'trash'){
        $payload.= json_encode(['pageData'=>['url'=>get_permalink($post_id), 'delete'=>1]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\r\n";
    }elseif($post->post_status == 'publish'){
        if ($post->post_type == 'product' && in_array($post->post_type, $selected_post_types)) {
            $product_obj = wc_get_product($post->ID);
            $product_gallery = get_post_meta($post->ID, '_product_image_gallery', true);
            $product_gallery_ids = explode(',', $product_gallery);
            $product_gallery_urls = array();
        
            foreach ($product_gallery_ids as $product_gallery_id) {
                $product_gallery_urls[] = wp_get_attachment_url($product_gallery_id);
            }
            $content = $post->post_content;
            $content = wp_strip_all_tags( $content );
            $pattern = '/[\r\n]+|&nbsp;/';
            $content = preg_replace($pattern, '', $content);

            $categories = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'names'));
            $categories = array_filter($categories, function($category) use ($selected_categories) {
                return in_array($category, $selected_categories);
            });
            
            $attributes = $product_obj->get_attributes();
            $attributes_data = array();
            
            foreach ($attributes as $attribute) {
                $attribute_slug = $attribute->get_name();
                $attribute_name = wc_attribute_label($attribute_slug);
                if ($attribute->is_taxonomy()) {
                    $attribute_taxonomy = $attribute->get_taxonomy(); 
                    $attribute_values = wc_get_product_terms($product_obj->get_id(), $attribute_taxonomy, array('fields' => 'names'));
                    $attributes_data[$attribute_name] = $attribute_values;
                } else {
                    $attribute_options = $attribute->get_options();
                    $attributes_data[$attribute_name] = $attribute_options;
                }
            }

            foreach ($attributes_data as $key => $value) {
                if (substr($key, 0, 3) === 'pa_') {
                    $new_key = str_replace('pa_', '', $key);
                    $attributes_data[$new_key] = $value;
                    unset($attributes_data[$key]);
                }
            }
            $sorting_price =$sale_price = $price=0;
            if($product_obj->get_regular_price())
            {
                $price = preg_replace('/[^0-9\.]/', '', $product_obj->get_regular_price(),);
                if($product_obj->get_sale_price()){
                    $sale_price = preg_replace('/[^0-9\.]/', '', $product_obj->get_sale_price());
                    if ((float)$sale_price > (float)$price && (float)$sale_price > 0) {
                        $sale_price = 0;
                    }
                    else
                    $sorting_price = $sale_price;
                }
                else
                    $sorting_price = $price;
                }
            else
            {
                $price=$product_obj->get_price();
            }
            $reg_price = $product_obj->get_price();
            $discount = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $reg_price, $product_obj);
            if($discount !== false) {
                if((float)$discount < (float)$price){
                    $sale_price = $discount;
                    $sorting_price = $sale_price;
                }else{
                    $sale_price = 0; 
                }
            }
            $displayed_price =  wc_get_price_to_display($product_obj);
            $regular_price = $price;
            if ((float)$regular_price !== (float)$displayed_price && (float)$displayed_price < (float)$regular_price) {
                $sale_price = $displayed_price;
                $sorting_price = $displayed_price;
            }else{
                if((float)$regular_price !== (float)$displayed_price){
                    $price = $displayed_price;
                    $sale_price = 0;
                    $sorting_price = $price;
                }
            }
            if((float)$sale_price <= 0){
                $sale_price = 0;
            }
            if($sorting_price == 0){
                $sorting_price = $price;
            }
            $sku = $product_obj->get_sku();

            $custom_label_1 = get_post_meta($post->ID, 'customs_label_1', true);
            $custom_label_2 = get_post_meta($post->ID, 'customs_label_2', true);
            $custom_label_3 = get_post_meta($post->ID, 'customs_label_3', true);

            $structuredFieldPriority = [];

            foreach ($fieldsPriority as $field) {
                $structuredFieldPriority[$field['field']] = $field['priority'];
            }
        
            asort($structuredFieldPriority);
        
            $fullText = [];
            foreach ($structuredFieldPriority as $field => $priority) {
                array_push($fullText, [$field => $priority]);
            }
            $product_id = $post->ID;
            $is_hidden = false;
            $visibility = $product_obj->get_catalog_visibility();
            if ( 'hidden' == $visibility ) {
                $is_hidden = true;
            }
            if ( taxonomy_exists( 'product_brand' ) ) {
                $brand_terms = wp_get_post_terms( $product_id, 'product_brand' );
                if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
                    $brand_term = $brand_terms[0];
                    $brand_name = $brand_term->name;
                    $brand_link = get_term_link($brand_term);
                    if (is_wp_error($brand_link)) {
                        $brand_link = '';
                    }
                    $thumbnail_id = get_term_meta( $brand_term->term_id, 'thumbnail_id', true );
            
                    if ( $thumbnail_id ) {
                        $brand_image_url = wp_get_attachment_url( $thumbnail_id );
                    } else {
                        $brand_image_url = '';
                    }
            
                } else {
                    $brand_name = '';
                    $brand_image_url = '';
                    $brand_link = ''; 
                }
            
            } else {
                $brand_name = '';
                $brand_image_url = ''; 
                $brand_link = '';
            }

            if (!empty($brand_name) && isset($brand_name)) {
                $attributes_data['brand'] = $brand_name;
            }

            $short_description = $product_obj->get_short_description(); 
            $j = ['pageData'=>[
                'id'=>$post_id,
                'title' => $post->post_title,
                'description' => strip_tags($content),
                'short_description' => strip_tags($short_description),
                'modifyTime' => strtotime($post->post_modified),
                'creationTime' => strtotime($post->post_date),
                'url' => get_permalink($post_id),
                'status' => $post->post_status,
                'img' => get_the_post_thumbnail_url($post_id),
                'shipping' => $product_obj->get_shipping_class(),
                'stock_status' => $product_obj->get_stock_status(),
                'additional_images' => $product_gallery_urls,
                'custom_label_1' => $custom_label_1,
                'custom_label_2'=>$custom_label_2,
                'custom_label_3'=> $custom_label_3,
                'brand_image_url' => $brand_image_url,
                'hide_from_catalog' => $is_hidden,
                'brand_name'=>$brand_name,
                'brand_link' => $brand_link,
                'attribute_array'=> $attributes_data,
                'product' => [
                        'price' => (string)$price,
                        'currency' => html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8'),
                ],
        
                ],
                
                'setIndex'=> [
                    'fullText' =>$fullText,
                    'categories' => $categories,
                    'attributes' => $attributes_data,
                    'sorting' => [['price' => (int)(10*(float)$sorting_price) ]],
                    'metaData' => [ 'category'=> trim(end($categories)) ,'sku'=>$sku]
                ],
                
            ];
            if($sale_price)
                $j['pageData']['product']['sale_price'] = $sale_price;

    
            $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $j['setIndex'] = json_encode($j['setIndex'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $s = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
            $payload .= $s ."\r\n";
            if($product_obj->get_stock_status() == 'outofstock' || $is_hidden || $post->post_status != 'publish'){
                $payload = json_encode(['pageData'=>['url'=>get_permalink($post_id), 'delete'=>1]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\r\n";
            }
        }  
        if($post->post_type != 'product' && in_array($post->post_type, $selected_post_types)){
            $content = $post->post_content;
            $content = wp_strip_all_tags( $content );
            $pattern = '/[\r\n]+|&nbsp;/';
            $content = preg_replace($pattern, '', $content);
            $tags = get_the_tags($post->ID);
            if($tags ){
                $tag_names = array();
                foreach($tags as $tag){
                    $tag_names[] = $tag->name;
                }
                $tags = implode(', ',$tag_names);
            }
            $author_id = get_post_field ('post_author', $post->ID);
            $author = get_the_author_meta( 'display_name' , $author_id ); 
            $category_names = array();
            $post_categories = get_the_category($post->ID);
            if(!empty($post_categories)){
                $category_names = wp_list_pluck($post_categories, 'name');
            }

            $j = ['pageData'=>[
                'title' => $post->post_title,
                'description' => get_the_excerpt($post),
                'postType'=>$post->post_type,
                'modifyTime' => $post->post_modified,
                'creationTime' => strtotime($post->post_date),
                'url' => get_permalink($post->ID),
                'author'=>$author,
                'status' => $post->post_status,
                'img' => get_the_post_thumbnail_url($post->ID),
                'category_names' => $category_names,
                'section' => $tags,
                ],
                
                'docBody' => $content,
            ];
        

            $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            // $j['setIndex'] = json_encode($j['setIndex'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $s = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $payload .= $s ."\r\n";

        }
    }
    $affId = 0000;
    $settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
    if(isset($settings['affId']))
    {
        $affId = $settings['affId']; 
    }
    $password = "";
    if(isset($heydayPluginOptions['heyday_user_reg'])){
        $password = $heydayPluginOptions['heyday_user_reg'];
    }

    $api_url = 'http://heyday.io/api/updateIndexer/' . $affId . '/' . $password;
    $request_args = array(
        'body'        => $payload,
        'headers'     => array(
            'Content-Type' => 'text/plain',
            'Expect'       => ''
        ),
    );
    $response = wp_remote_post($api_url, $request_args);
    if (is_wp_error($response)) {
        echo 'Error: ' . $response->get_error_message();
    } else {
        $result = wp_remote_retrieve_body($response);
        $status = $result . $payload;
    }
}

class heydayWebSearch_Heyday_search_Plugin
{
    public static $instance;
    private $affId=-1;

    private function __construct()
    {   
        add_action( 'wp_ajax_load_user_data', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heyday_search_load_user_data']);
        add_action('wp_ajax_nopriv_load_user_data', ['heydayWebSearch_Heyday_search_Plugin_menue','heyday_search_load_user_data']);
        add_action('wp_ajax_check_status', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heyday_search_check_status']);
        add_action('wp_ajax_nopriv_check_status',['heydayWebSearch_Heyday_search_Plugin_menue', 'heyday_search_check_status']);
        add_action('wp_ajax_heyday_init_js', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heyday_search_heyday_init_js']);
        add_action('wp_ajax_nopriv_heyday_init_js',['heydayWebSearch_Heyday_search_Plugin_menue', 'heyday_search_heyday_init_js']);
        add_action('wp_ajax_get_product_attributes_and_categories', ['heydayWebSearch_Heyday_search_Plugin_menue','get_product_attributes_and_categories']);
        add_action('wp_ajax_save_index_configuration',  ['heydayWebSearch_Heyday_search_Plugin_menue','save_index_configuration']);
        add_action('wp_ajax_nopriv_save_index_configuration',  ['heydayWebSearch_Heyday_search_Plugin_menue','save_index_configuration']);
        add_action('wp_ajax_load_posts_and_products', ['heydayWebSearch_Heyday_search_Plugin_menue','heyday_select_post_type']); 
        add_action('wp_ajax_nopriv_load_posts_and_products', ['heydayWebSearch_Heyday_search_Plugin_menue','heyday_select_post_type']);
        add_action('wp_ajax_stop_load_progress', ['heydayWebSearch_Heyday_search_Plugin_menue','stop_load_progress']); 
        add_action('wp_ajax_nopriv_stop_load_progress', ['heydayWebSearch_Heyday_search_Plugin_menue','stop_load_progress']);
        add_action('wp_ajax_select_posts_types', ['heydayWebSearch_Heyday_search_Plugin_menue','select_posts_types']);
        add_action('wp_ajax_nopriv_select_posts_types', ['heydayWebSearch_Heyday_search_Plugin_menue','select_posts_types']);

        add_action('admin_menu', [$this, 'set_admin_pages']);
        $settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        if(isset($settings['affId']))
        {
            $this->affId = $settings['affId']; 
            add_action('wp_head', function() {
                heydayWebSearch_Heyday_search_Plugin::inject_head_tag($this->affId);
            });
            $heydayPluginOptions['heydayAffId'] = $this->affId;
            update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
        }

    }

    public static function init()
    {
        if(self::$instance == null)
        {
            self::$instance = new heydayWebSearch_Heyday_search_Plugin();
        }
        return self::$instance;
    }

    public static function set_admin_pages()
    {   
        add_menu_page('HayDay Search Configuration And Settings', 'HeyDay-Search', 'manage_options', heydayWebPush_HEYDAY_OPTIONS, ['heydayWebSearch_Heyday_search_Plugin_menue', 'heydayWebPush_heyday_settings']);
    }

    public static function inject_head_tag($affId){
        $r = parse_url(plugins_url('heyday-search.php', __FILE__ ));
        $host = $r['host'];
        $url = home_url();
        $host = preg_replace('#^https?://#i', '', $url);
        wp_enqueue_script('heydayWebPush_heyday-push-main', 'https://cdn.heyday.io/cstmst/heyDayMain.js?affId=' . $affId . '&d=' . $host, array(), false);
        heydayWebSearch_Heyday_search_Plugin::sendObject($affId,$host);
    }

    public static function sendObject($affId, $domain) {

        $heydayPluginOptions = get_option(heydaySearchPlugin_HEYDAY_OPTIONS, []);
        if(isset($heydayPluginOptions['checked_domain_to_aff']) && $heydayPluginOptions['checked_domain_to_aff'] == true)
        {
              return;
        }
        $heydayPluginOptions['checked_domain_to_aff'] = true;
        update_option(heydaySearchPlugin_HEYDAY_OPTIONS, $heydayPluginOptions);

        $data = array(
            'affId' => $affId,
            'domain' => $domain,
            'type' => 'wordpress',
            'callbackUrl' => 'http://'.$domain.'/wp-json/heyday-search/v1/check-aff/',
            'action' => 1600,
        );

        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $server_url = "https://admin.heyday.io/idx/OP";
        $request_args = array(
            'body'        => $json_data,
            'headers'     => array(
                'Content-Type' => 'application/json',
            ),
        );
        $response = wp_remote_post($server_url, $request_args);
        if (is_wp_error($response)) {
            echo 'Error: ' . $response->get_error_message();
        } else {
            $result = wp_remote_retrieve_body($response);
        }
    }

    public static function on_activation()
    {   
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
        $settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
        $settings["admin_email"] = get_option('admin_email');
        $settings["blogname"] = get_option('blogname');
        update_option(heydayWebPush_HEYDAY_OPTIONS, $settings);

        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'name' => 'heyday-search',
            'posts_per_page' => 1,
        );
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            $new_page = array(
                'post_content'  => '<div id="hdy_holder"></div>',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
                'post_name'     => 'heyday-search'
            );
        
            wp_insert_post($new_page);
        }
        
        wp_reset_postdata();
    }

    public static function on_deactivation()
    {   $affId = 000;
        $password = '';
        $email = get_option('admin_email');
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        if(isset($heydayPluginOptions['heydayAffId'])){
            $affId = $heydayPluginOptions['heydayAffId'];
        }
        if(isset($heydayPluginOptions['heyday_user_reg'])){
            $password = $heydayPluginOptions['heyday_user_reg'];
        }
        $loginRequest = [
            'action' => 1,
            'credentials' => [
                'uName' => $email,
                'password' => $password,
            ],
        ];
        
        $loginResponse = wp_remote_post('https://heyday.io/panWbPush/', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($loginRequest),
            'method' => 'POST',
        ]);
    
        if (is_wp_error($loginResponse)) {
            error_log('Failed to log in: ' . $loginResponse->get_error_message());
            return;
        }
        $loginResponseBody = json_decode(wp_remote_retrieve_body($loginResponse), true);
        if(!isset($loginResponseBody['accessToken'])){
            return;
        }
        $token = $loginResponseBody['accessToken'];
        $terminationRequest = [
            'action' => 30,
            'submitedAffId' => $affId,
        ];
    
        $url = "https://admin.heyday.io/panWbPush/?c=1&accessToken=" . $token . "&uName=" . $email;
        $referrer = "https://admin.heyday.io/?u=" . $email . "&t=" . $token . "&a=wordPress";
    
        $terminationResponse = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Referer' => $referrer,
            ],
            'body' => json_encode($terminationRequest),
            'method' => 'POST',
        ]);
    
        if (is_wp_error($terminationResponse)) {
            error_log('HeyDay: Failed to terminate: ' . $terminationResponse->get_error_message());
        }else{
            error_log('HeyDay: Success to delete user');
        }
    }
    
    public static function redir($plugin)
    {
        if ($plugin == plugin_basename(__FILE__)) {
            wp_redirect( admin_url( 'admin.php?page='.heydayWebPush_HEYDAY_OPTIONS ) );
            exit;
        }
    }

    public static function on_uninstall()
    {   
        delete_option(heydayWebPush_HEYDAY_OPTIONS);
        delete_option('heydaySearchPlugin_HEYDAY_OPTIONS');
    }
    
}