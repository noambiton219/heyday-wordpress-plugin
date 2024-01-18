<?php
class heydayWebSearch_Heyday_search_Plugin_menue
{
    private static $settings = [];
    private static $randPassword = '';

    public static function heydayWebPush_enqueue_custom_admin_style() 
    {
        wp_enqueue_style('heydayWebPush_heyday-push-main-login', plugin_dir_url( __FILE__ ) . 'style/heyday_style.css');
    }

    public static function heydayWebPush_enqueue_custom_admin_script() 
    {
        wp_enqueue_script('heydayWebPush_heyday-push-main', plugin_dir_url( __FILE__ ) . 'scripts/heyday_login_script.js');
    }

    public static function heydayWebPush_print_inline_reactivatio_script() 
    {   $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $settings = heydayWebSearch_Heyday_search_Plugin_menue::$settings;
        $email = "";
        $password = "";
        if (isset($heydayPluginOptions['heyday_user_reg'])) {
            $password = $heydayPluginOptions['heyday_user_reg'];
            $email = $settings["admin_email"];
        }
        
        $script = "heyday_reactivationSuccess('" . $email . "', '" . $password . "');";
        wp_add_inline_script('heydayWebPush_heyday-push-main', $script);
    }

    public static function heydayWebPush_print_inline_init_script() 
    {   
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $randPassword = heydayWebSearch_Heyday_search_Plugin_menue::$randPassword;
        $settings = heydayWebSearch_Heyday_search_Plugin_menue::$settings;
        $randPassword_esc = esc_html(heydayWebSearch_Heyday_search_Plugin_menue::$randPassword);
        $heyDaySettings = ["admin_email" => $settings["admin_email"]];
        if(isset($settings["affId"]))
            $heyDaySettings["affId"] = $settings["affId"];
        $heyDaySettings = json_encode(array_map('esc_html', $heyDaySettings));
        
        $heyday_queryParams = [];
        if(isset($_GET['accessToken']))
        {
            $heyday_queryParams['accessToken'] = isset($_GET['accessToken']) ? sanitize_text_field($_GET['accessToken']) : '';
            if(!preg_match("/^\d+\-\d+\-\d+\-\d+_\d+$/", $heyday_queryParams['accessToken']))
                return;
        }

        if(isset($_GET['globalErr']))
            $heyday_queryParams['globalErr'] = intval($_GET['globalErr']);

        $heyday_queryParams = json_encode(array_map('esc_attr', $heyday_queryParams));
        $blogname = esc_html(get_option('blogname'));
        $r = parse_url(plugins_url('heyday-search.php', __FILE__ ));
        $host = home_url();

        if (
            (isset($heyDaySettings['affId']) && !isset($_GET['accessToken'])) || 
            isset($heyDaySettings['accessToken']) || 
            (isset($_GET['globalErr']) && $_GET['globalErr'] == '1')
        ) {
            if (isset($heydayPluginOptions['heyday_user_reg'])) {
                $randPassword_esc = $heydayPluginOptions['heyday_user_reg'];
                $randPassword = $heydayPluginOptions['heyday_user_reg'];
            }
            
        } else {
            $password = $randPassword;

            if (!isset($heydayPluginOptions['heyday_user_reg'])) {
                $heydayPluginOptions['heyday_user_reg'] = $password;
                update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
            } else {
                $randPassword_esc = $heydayPluginOptions['heyday_user_reg'];
                $randPassword = $heydayPluginOptions['heyday_user_reg'];
            }
        }
        /*output js script & code */
        $jsCode = "window.heyday_randPassword = '{$randPassword}';
        window.heyday_randPassword_esc = '{$randPassword_esc}';
        window.blogname = '{$blogname}';
        window.wpHost = '{$host}';
        window.heyDaySettings = {$heyDaySettings};
        window.heyday_queryParams = {$heyday_queryParams};
        heyday_mannageAccount();";
        
        wp_add_inline_script('heydayWebPush_heyday-push-main', $jsCode);
    }

    public static function heydayWebPush_heyday_settings()
    {   
    add_action('heyday_admin_print_styles', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heydayWebPush_enqueue_custom_admin_style']);
    do_action('heyday_admin_print_styles');
    add_action('heyday_admin_enqueue_scripts', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heydayWebPush_enqueue_custom_admin_script']);
    do_action('heyday_admin_enqueue_scripts');
    heydayWebSearch_Heyday_search_Plugin_menue::$settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
    heydayWebSearch_Heyday_search_Plugin_menue::heyday_conf_settings_page();
    
        /*heyDayAffId must be int bigger than zero*/
        $heyDayAffId = (isset($_GET['heyDayAffId']) && (int)$_GET['heyDayAffId'] > 0) ? (int)$_GET['heyDayAffId'] : false;
        if(isset(heydayWebSearch_Heyday_search_Plugin_menue::$settings['affId']) && $heyDayAffId === false)
        {
            heydayWebSearch_Heyday_search_Plugin_menue::heydayWebPush_reactivationSuccess();
            return;
        }
        if($heyDayAffId !== false)
        {
            heydayWebSearch_Heyday_search_Plugin_menue::$settings['affId'] = $heyDayAffId;
            update_option(heydayWebPush_HEYDAY_OPTIONS, heydayWebSearch_Heyday_search_Plugin_menue::$settings);
        }

        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@$%&*';        
        $max = strlen($keyspace)-1;
        for($i=0;$i<8;$i++)
        {
            heydayWebSearch_Heyday_search_Plugin_menue::$randPassword .= $keyspace[ rand (0 , $max ) ];
        }

        add_action('heyday_admin_print_scripts', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heydayWebPush_print_inline_init_script']);
        do_action('heyday_admin_print_scripts');

    }

    public static function loadData($offset = 0, $limit = 50, $allData = array()) {
       
        $settings = get_option(heydayWebPush_HEYDAY_OPTIONS, []);
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $heyDayAffId = 000;
        if(isset($heydayPluginOptions['heydayAffId'])){
            $heyDayAffId = $heydayPluginOptions['heydayAffId'];
        }
        $password = "";
        if (isset($heydayPluginOptions['heyday_user_reg'])) {
            $password = $heydayPluginOptions['heyday_user_reg'];
        }
        if(isset($settings['affId']))
        {
            $affId = $settings['affId'];
        }else{
            $affId = 0000;
        }
        $api_url = 'http://heyday.io/api/updateIndexer/' . $affId . '/' . $password;

        $selected_post_types = array();
        if(isset($heydayPluginOptions['selected_posts_types'])){
            $selected_post_types = $heydayPluginOptions['selected_posts_types'];
        }

        $args = array(
            'posts_per_page'   => $limit,
            'offset'           => $offset,
            'post_type'        => $selected_post_types,
        );
    
        $posts_array = get_posts($args);

        if(is_wp_error($posts_array)){
            $heydayPluginOptions['heyday_search_error_fetch'] = 'Error';
        }else{
            $heydayPluginOptions['heyday_search_error_fetch'] = 'Success';
        }

        $allData = array_merge($allData, $posts_array);
    
        $payload ='';
        $i=0;
        $test = '';
        foreach($posts_array as $post) {
            if ( $post->post_type == 'product' ) {

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

            $selected_categories = array();
            if(isset($heydayPluginOptions['heyday_selected_categories'])){
                $selected_categories = $heydayPluginOptions['heyday_selected_categories'];
            }

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

            if($product_obj->get_stock_status() == 'outofstock'){
                continue;
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

            $custom_label_1 = get_post_meta($post->ID, 'customs_label_1', true);
            $custom_label_2 = get_post_meta($post->ID, 'customs_label_2', true);
            $custom_label_3 = get_post_meta($post->ID, 'customs_label_3', true);

            $fieldsPriority = array();
            if (isset($heydayPluginOptions['heyday_fields_priority'])) {
                $fieldsPriority = $heydayPluginOptions['heyday_fields_priority'];
            }
            $structuredFieldPriority = [];

            foreach ($fieldsPriority as $field) {
                $structuredFieldPriority[$field['field']] = $field['priority'];
            }
        
            asort($structuredFieldPriority);
        
            $fullText = [];
            foreach ($structuredFieldPriority as $field => $priority) {
                array_push($fullText, [$field => (int)$priority]);
            }
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
                continue;
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
                'p_price' =>  $product_obj->get_price(),
                'attribute_array'=> $attributes_data,
                'brand_link'=>$brand_link,
                'product' => [
                        'price' => (string)$price,
                        'currency' => html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT, 'UTF-8'),
                ],
    
                ],
                
                'setIndex'=> [
                    'fullText' => $fullText,
                    'categories' => $categories,
                    'attributes' => $attributes_data,
                    'sorting' => [['price' => (int)(10*(float)$sorting_price) ]],
                    'metaData' => [ 'category'=> trim(end($categories)) ,'sku'=>$sku]
                ],
                
            ];
            if($sale_price)
            $j['pageData']['product']['sale_price'] = $sale_price;

            $i++;
            $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $j['setIndex'] = json_encode($j['setIndex'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if($product_obj->get_stock_status() == 'outofstock' || $is_hidden || $post->post_status != 'publish'){
                $payload .= json_encode(['pageData'=>['url'=>get_permalink($post->ID), 'delete'=>1]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\r\n";
            }else{
                $payload .= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ."\r\n";
            }
    
        }elseif($post->post_type == 'post'){
            if($post->post_status != 'publish'){
                continue;
            }
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
                $i++;
                $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
                $payload .= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ."\r\n";
    
            }else{
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
                
                $i++;
                $j['pageData'] = json_encode($j['pageData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
                $payload .= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ."\r\n";
            }
        
            if(!($i%300))
            {
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
                }
        
                $payload='';
            }
        }

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
        $last_processed_posts = 0;
        if(isset($heydayPluginOptions['heyday_search_processed_posts'])){
            $last_processed_posts = $heydayPluginOptions['heyday_search_processed_posts'];
        }
        $numOfPosts = count($posts_array) + $last_processed_posts;
        $stopLoading = false;
        if(isset($heydayPluginOptions['heyday_stop_progress_flag'])){
            $stopLoading = $heydayPluginOptions['heyday_stop_progress_flag'];
        }
        $maxIndexPagesValue = 5000000;
        if(isset($heydayPluginOptions['heyday_max_index_pages'])){
            $maxIndexPagesValue = $heydayPluginOptions['heyday_max_index_pages'];
        }
        $total_posts = 0;
        if(isset($heydayPluginOptions['heyday_search_total_posts'])){
            $total_posts = $heydayPluginOptions['heyday_search_total_posts'];
        }

        if($numOfPosts < $total_posts && !$stopLoading && $numOfPosts < $maxIndexPagesValue){
            $heydayPluginOptions['heyday_search_progress_status'] = 'in progress';
            $heydayPluginOptions['heyday_search_processed_posts'] = $numOfPosts;
            update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
        }else{
            $heydayPluginOptions['heyday_search_progress_status'] = 'completed';
            $heydayPluginOptions['heyday_search_processed_posts'] = $numOfPosts;
            update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
        }
        return ($status);
    }

    public static function heyday_search_load_user_data() {
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $loadedPosts = 0;
        if(isset($heydayPluginOptions['heyday_search_processed_posts'])){
            $loadedPosts = $heydayPluginOptions['heyday_search_processed_posts'];
        }
        $result = heydayWebSearch_Heyday_search_Plugin_menue::loadData($loadedPosts, 50, array());
        $status = '';
        if(isset($heydayPluginOptions['heyday_search_progress_status'])){
            $status = $heydayPluginOptions['heyday_search_progress_status'];
        }

        wp_send_json([
            'status' => $status,
            'serverResult'=>  $result
        ]);
        wp_die();
    }

    public static function get_product_attributes_and_categories() {
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
    
        $result = array('categories' => array(), 'attributes' => array());

        $terms = get_terms('product_cat', array('hide_empty' => false));
        foreach ($terms as $term) {
            $result['categories'][$term->name] = $term->name;
        }

        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attribute) {
            $attr_name = wc_attribute_taxonomy_name($attribute->attribute_name);
            $result['attributes'][$attr_name] = $attribute->attribute_label;
        }
    
        $result['selected_categories'] = $selected_categories;
        $result['selected_attributes'] = $selected_attributes;
        $result['fields_priority'] = $fieldsPriority;
        $result['save_index_configuration_nonce'] = wp_create_nonce('heyday_save_index_conf_nonce');
        wp_send_json($result);
    }
    

    public static function save_index_configuration() {
        if (isset($_POST['heydayWpnonce']) && wp_verify_nonce($_POST['heydayWpnonce'], 'heyday_save_index_conf_nonce')) {
            $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
            $selectedCategories = isset($_POST['selectedCategories']) ? array_map('sanitize_text_field', $_POST['selectedCategories']) : [];
            $selectedAttributes = isset($_POST['selectedAttributes']) ? array_map('sanitize_text_field', $_POST['selectedAttributes']) : [];
            $fieldsPriority = isset($_POST['fieldsPriority']) ? array_map(function($item) {
                return array_map('sanitize_text_field', $item);
            }, $_POST['fieldsPriority']) : [];
            $heydayPluginOptions['heyday_selected_categories'] = $selectedCategories;
            $heydayPluginOptions['heyday_selected_attributes'] = $selectedAttributes;
            $heydayPluginOptions['heyday_fields_priority'] = $fieldsPriority;
            update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
            echo wp_json_encode(['success' => true]);
            wp_die();
        }else{
            error_log("validate failed save_index_configuration");
        }
    }

    public static function select_posts_types(){ 
        if (isset($_POST['heydayWpnonce']) && wp_verify_nonce($_POST['heydayWpnonce'], 'fetch_update_types_nonce')) {
            $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
            if ($_POST['loadType'] == 'fetch') {
                $args = array(
                'public'   => true
                );
                $post_types = get_post_types( $args );
                wp_send_json_success($post_types);
            }
            if ($_POST['loadType'] == 'update' && isset($_POST['selectedPostTypes'])) {
                $heydayPluginOptions['selected_posts_types'] = isset($_POST['selectedPostTypes']) ? array_map('sanitize_text_field', $_POST['selectedPostTypes']) : [];
                update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
                wp_send_json_success([
                    'status' => 'success',
                ]);
            } 
            wp_die();
        }
    }

    public static function heyday_select_post_type() {
        if (isset($_POST['heydayWpnonce']) && wp_verify_nonce($_POST['heydayWpnonce'], 'select_post_type_nonce')) {
            $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
            $heydayPluginOptions['heyday_stop_progress_flag'] = false;
            $heydayPluginOptions['heyday_search_processed_posts'] = 0;
    
            if (isset($_POST['maxIndexPagesValue'])) {
                $maxIndexPagesValue = isset($_POST['maxIndexPagesValue']) ? absint($_POST['maxIndexPagesValue']) : 0;
                $maxIndexPagesValue = intval($maxIndexPagesValue);
                $heydayPluginOptions['heyday_max_index_pages'] = $maxIndexPagesValue;
            } else {
                $heydayPluginOptions['heyday_max_index_pages'] = 5000000;
            }
    
            $selected_post_types = [];
            if (isset($heydayPluginOptions['selected_posts_types'])) {
                $selected_post_types = $heydayPluginOptions['selected_posts_types'];
            }
    
            $total_posts = 0;
            foreach ($selected_post_types as $post_type_name) {
                $count_posts = wp_count_posts($post_type_name);
                $published_posts = isset($count_posts->publish) ? $count_posts->publish : 0;
                $total_posts += $published_posts;
            }
            
            $scalar_values = array_map('esc_attr', $selected_post_types);
    
            $heydayPluginOptions['heyday_search_total_posts'] = $total_posts;
            update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
            
            echo wp_json_encode(['success' => $scalar_values]);
        }
    }
    

    public static function stop_load_progress(){
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $heydayPluginOptions['heyday_stop_progress_flag'] = false;
        update_option('heydaySearchPlugin_HEYDAY_OPTIONS', $heydayPluginOptions);
        wp_die();
    }
    
    public static function heyday_search_heyday_init_js() {
        wp_send_json([
            'fetchUpdateTypesNonce' => wp_create_nonce('fetch_update_types_nonce'),
            'selectPostTypeNonce' => wp_create_nonce('select_post_type_nonce'),
        ]);
    }

    public static function heyday_search_check_status() {
        $heydayPluginOptions = get_option('heydaySearchPlugin_HEYDAY_OPTIONS', []);
        $status = '';
        $processed_posts = 0;
        $total_posts = 0;
        $selected_post_types = array();
        if(isset($heydayPluginOptions['heyday_search_progress_status'])){
            $status = $heydayPluginOptions['heyday_search_progress_status'];
        }
        if(isset($heydayPluginOptions['heyday_search_processed_posts'])){
            $processed_posts = $heydayPluginOptions['heyday_search_processed_posts'];
        }
        if(isset($heydayPluginOptions['heyday_search_total_posts'])){
            $total_posts = $heydayPluginOptions['heyday_search_total_posts'];
        }
        if(isset($heydayPluginOptions['selected_posts_types'])){
            $selected_post_types = $heydayPluginOptions['selected_posts_types'];
        }

        if(in_array("product", $selected_post_types)){
            $web_type = 'product';
        }else{
            $web_type = 'post';
        }
        $url = home_url();
        $domain = preg_replace('#^https?://#i', '', $url);
        $error = 'Success';
        if(isset($heydayPluginOptions['heyday_search_error_fetch'])){
            $error = $heydayPluginOptions['heyday_search_error_fetch'];
        }
        $stopLoading = false;
        if(isset($heydayPluginOptions['heyday_stop_progress_flag'])){
            $stopLoading = $heydayPluginOptions['heyday_stop_progress_flag'];
        }
        if($stopLoading == true){
            $error = 'error';
        }

        wp_send_json([
            'status' => $status,
            'processed_posts' => $processed_posts,
            'total_posts' => $total_posts,
            'domain' => $domain,
            'web_type' => $web_type,
            'error'=> $error,
        ]);
        wp_die();
    }

    public static function heyday_conf_settings_page() {
        include plugin_dir_path(__FILE__) . 'settings-page.php';
    }

    private static function heydayWebPush_reactivationSuccess()
    {
        add_action('heyday_admin_print_scripts', ['heydayWebSearch_Heyday_search_Plugin_menue', 'heydayWebPush_print_inline_reactivatio_script']);
        do_action('heyday_admin_print_scripts');
    }
}

