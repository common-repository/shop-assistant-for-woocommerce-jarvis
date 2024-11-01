<?php

/**
 * @param $type
 * Display recently viewed products
 */
if (!defined('ABSPATH')) exit; // Exit if accessed directly
function jarvis_options_field_display($type)
{

    switch ($type) {

        case 'input' :
            ?>

            <input type="text" name="jarvis-settings[<?php echo esc_attr($args['type']) ?>]"
                   value="<?php echo esc_attr($settings[$args['type']]) ?>" class="regular-text"/>
            <?php
            break;
        case 'small-input' :
            ?>
            <input type="text" name="jarvis-settings[<?php echo esc_attr($args['type']) ?>]"
                   value="<?php echo esc_attr($settings[$args['type']]) ?>"
                   class="small-text"/>
            <?php esc_html_e('px', 'jarvis') ?>
            <?php
            break;
        case 'filter' :
            global $woocommerce; ?>
            <div class="row">
                <div class="col-sm-4">
                    <?php esc_html_e("Text", "jarvis"); ?>
                    <a
                        class="header-help help_tip"
                        data-tip="<?php esc_html_e('Place your natural language text here.', 'jarvis'); ?>"
                        href="#">[?]</a>
                </div>
                <div class="col-sm-4">
                    <?php esc_html_e("Option", "jarvis"); ?>
                    <a
                        class="header-help help_tip"
                        data-tip="<?php esc_html_e('Option could be price, category, terms etc.', 'jarvis'); ?>"
                        href="#">[?]</a>
                </div>
                <div class="col-sm-4">
                    <?php esc_html_e("Placeholder", "jarvis"); ?>
                    <a
                        class="header-help help_tip"
                        data-tip="<?php esc_html_e('Placeholder for option which will be displayed in frontend', 'jarvis'); ?>"
                        href="#">[?]</a>
                </div>
            </div>
            <div class="jarvis_sentence_builder">

                <div class="repeatable">
                    <?php LanguageBuilder::repopulate(); ?>
                </div>
                <div class="row form-group span4">
                    <input type="button" value="<?php esc_html_e("Add New Term", "jarvis"); ?>"
                           class="btn button btn-default add"/>
                </div>
            </div>
            <script type="text/template" id="jarvis_sentence_builder">
                <?php echo LanguageBuilder::get_template(); ?>
            </script>
            <?php
            break;
    }
}


// Register the shortcode
add_shortcode("jarvis-recently-viewed-products", "wsaj_recently_viewed_products");
add_shortcode("jarvis-recently-viewed-product-widget", "wsaj_recently_viewed_product_widget");
add_shortcode("jarvis-cart-products", "jarvis_get_cart_products");

add_shortcode("jarvis_recommended_products", "jarvis_get_recommended_products");
add_shortcode('last_sold_product', 'last_sold_product');
add_shortcode('last-sold-product-widget', 'last_sold_product_widget');


function jarvis_get_recommended_products()
{
    global $post;
    global $product;
    $recommended_products_title = get_option('recommended_products_title') ? get_option('recommended_products_title') : esc_html('Recommended Products', 'jarvis');

    $font_size = get_option('global_widget_font_size') ? get_option('global_widget_font_size') : '20';
    $args = array('post_type' => 'product', 'meta_key' => '_featured', 'posts_per_page' => 6, 'columns' => '3', 'meta_value' => 'yes');
    $loop = new WP_Query($args);
    $html = '<div id="jarvis_ajax_search_products"></div>';
    $html .= '<div class="jarvis-featured-products">';
    $html .= '<h2 class="jarvis_title" style="font-size:' . $font_size . '">' . $recommended_products_title . '</h2>';
    $html .= '<ul class="jarvis_products">';

    $products = unserialize(get_option('jarvis-recommended-products'));
    //$_pf = new WC_Product_Factory();
    if(!empty($products)){
        foreach ($products as $id){
            //$product = $_pf->get_product($id);

            $product = wc_get_product($id);
            $price = get_post_meta($id, '_price', true);
            $html .= '<li class="jarvis-product">';
            $html .= '<a href="' . get_permalink($id) . '" title="' . esc_attr($product->get_title() ? $product->get_title() : $id) . '">';
            $html .= get_the_post_thumbnail($id, 'shop_thumbnail') . '</a>
           <div class="jarvis-product-summary">
           <div class="jarvis-product-table">
           <div class="jarvis-product-table-cell">
           <h3 class="jarvis_product_title"><a href="' . get_permalink($id) . '" title="' . esc_attr($product->get_title() ? $product->get_title() : $id) . '">' . $product->get_title() . '</a></h3>
           <div class="price">' . $product->get_price_html() . '</div>';

            if ($product->is_type('simple')) {
                $html .= '<a href="' . get_site_url() . '?add-to-cart=' . $id . '"  title="' . esc_attr($product->get_title() ? $product->get_title() : $id) . '"  class="jarvis-button jarvis-button-cart add_to_cart_button ajax_add_to_cart"  data-quantity="1" data-product_id="' . $id . '" >Add to Cart</a>';
            } else {
                $html .= '<a href="' . get_permalink($id) . '"  title="' . esc_attr($product->get_title() ? $product->get_title() : $id) . '"  class="jarvis-button jarvis-button-cart"  >View Detail</a>';
            }


            $html .= ' </div>
           </div>
           </div>
           </li>';

        }

    }
    $html .= '</ul>';
    $html .= '</div>';
    wp_reset_query();

    if (get_option('recommended_products')) {
        return $html;
    }


}

function last_sold_product($attributes)
{
    global $wpdb, $woocommerce;
    $last_sold_product_title = get_option('last_sold_product_title') ? get_option('last_sold_product_title') : esc_html('Recently Sold Products', 'jarvis');
    $no_last_sold_product = get_option('no_last_sold_product') ? get_option('no_last_sold_product') : esc_html('No recently sold products found.', 'jarvis');
    $last_sold_from = get_option('last_sold_from') ? get_option('last_sold_from') : esc_html('from', 'jarvis');
    $last_sold_guest_from = get_option('last_sold_guest_from') ? get_option('last_sold_guest_from') : esc_html('Guest from', 'jarvis');
    $last_sold_just_purchased = get_option('last_sold_just_purchased') ? get_option('last_sold_just_purchased') : esc_html('just purchased', 'jarvis');
    $last_sold_purchased_a = get_option('last_sold_purchased_a') ? get_option('last_sold_purchased_a') : esc_html('purchased a', 'jarvis');
    $last_sold_time = get_option('last_sold_time') ? get_option('last_sold_time') : esc_html('About 20 minutes  ago', 'jarvis');


    $defaults = array('max_products' => 1, 'title' => esc_attr($last_sold_product_title) );
    $parameters = shortcode_atts($defaults, $attributes);
    $max = absint($parameters['max_products']);  // number of products to show
    $title = sanitize_text_field($parameters['title']);
    $html = '<div class="last_sold_product">' . PHP_EOL;
    if ($title) {
        $html .= '<h3>' . esc_attr($title) . '</h3>' . PHP_EOL;
    }
    $table = $wpdb->prefix . 'woocommerce_order_items';
    //$my_query = $wpdb->prepare("SELECT * FROM $table WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $max);
    $nr_rows = $wpdb->query($wpdb->prepare("SELECT * FROM %s WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $table, $max));
    if (!$nr_rows) {
        $html .= '<p>' . esc_html($no_last_sold_product, 'jarvis') . '</p>';
    } else {
        $html .= PHP_EOL;
        for ($offset = 0; $offset < $nr_rows; $offset++) {
            if (is_user_logged_in()) {
                $user_info = wp_get_current_user();
            }
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM %s WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $table, $max), OBJECT, $offset);
            $product_name = $row->order_item_name;

            $product = get_page_by_title($product_name, OBJECT, 'product');
            $url = get_permalink($product->get_id());
            $order_id = $row->order_id;
            $_SESSION['jarvis_last_order_id'] = $order_id;
            $order = new WC_Order($order_id);
            $user = $order->get_user();
            $user_city = $order->shipping_city;

            if ($user) {
                $user_id = $user->ID;
            } else {
                $user_id = 'Guest';
            }
            if ($user_info->ID == $user_id) {
                $unix_date = strtotime($order->order_date);
                $date = gmdate('d/m/y', $unix_date);

                //Guest from Location (City) just purchased Flying Ninja!
                $html .= '<strong>' . $user->data->display_name . '</strong>' . ' ' . $last_sold_from . ' ' . $user_city . ' ' . $last_sold_just_purchased . '  ' . '<a href="' . $url . '">' . $product_name . '</a> ' . PHP_EOL;
            } else {
                $unix_date = strtotime($order->order_date);
                $date = gmdate('d/m/y', $unix_date);
                $html .= '<strong>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</strong>' . ' ' . $last_sold_from . ' ' . $user_city . ' ' . $last_sold_just_purchased . '  ' . '<a href="' . $url . '">' . $product_name . '</a> ' . PHP_EOL;


                $html .= '<div id="message-purchased" class="customized " style="display: none;"
			 data-next_item="1"
			 data-loop="1"
			 data-initial_delay="5"
			 data-notification_per_page="30"
			 data-display_time="10"
			 data-next_time="25"
			 >
<img src="' . get_site_url() . get_the_post_thumbnail($product->get_id()) . '" class="wcn-product-image"><p>' . $last_sold_guest_from . ' ' . $user_city . ' ' . $last_sold_purchased_a . ' <a href="' . $url . '">' . $product_name . '</a>
		 		<small>' . $last_sold_time . '</small>
		</p><span id="notify-close"></span>
			
		</div>';

            }
        }
        //  $html .= PHP_EOL;
    }
    //$html .= '</div>' . PHP_EOL;
    if ( isset($_SESSION['jarvis_last_order_id']) && $_SESSION['jarvis_last_order_id'] == $order_id) {
        return false;
    } else {
        return $html;
    }
}


function last_sold_product_widget($attributes)
{
    global $wpdb, $woocommerce;
    $last_sold_product_title = get_option('last_sold_product_title') ? get_option('last_sold_product_title') : esc_html('Recently Sold Products', 'jarvis');
    $no_last_sold_product = get_option('no_last_sold_product') ? get_option('no_last_sold_product') : esc_html('No recently sold products found.', 'jarvis');
    $last_sold_from = get_option('last_sold_from') ? get_option('last_sold_from') : esc_html('from', 'jarvis');
    $last_sold_just_purchased = get_option('last_sold_just_purchased') ? get_option('last_sold_just_purchased') : esc_html('just purchased', 'jarvis');

    $defaults = array('max_products' => 1, 'title' => $last_sold_product_title);
    $parameters = shortcode_atts($defaults, $attributes);
    $max = absint($parameters['max_products']);  // number of products to show
    $title = sanitize_text_field($parameters['title']);
    $html = '<div class="last_sold_product">' . PHP_EOL;
    if ($title) {
        $html .= '<h3>' . $title . '</h3>' . PHP_EOL;
    }
    $table = $wpdb->prefix . 'woocommerce_order_items';
    $my_query = $wpdb->prepare("SELECT * FROM %s WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $table, $max);
    $nr_rows = $wpdb->query($wpdb->prepare("SELECT * FROM %s WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $table, $max));
    if (!$nr_rows) {
        $html .= '<p>' . esc_html( $no_last_sold_product, 'jarvis' ) . '</p>';
    } else {

        $from = get_option('user_from_title') ? get_option('user_from_title') : ' ';
        $from_purchased = get_option('user_purchased_a_title') ? get_option('user_purchased_a_title') : ' ';
        $html .= PHP_EOL;
        for ($offset = 0; $offset < $nr_rows; $offset++) {
            if (is_user_logged_in()) {
                $user_info = wp_get_current_user();
            }
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM %s WHERE `order_item_type`='line_item' ORDER BY `order_id` DESC LIMIT %d", $table, $max), OBJECT, $offset);
            $product_name = $row->order_item_name;
            $product = get_page_by_title($product_name, OBJECT, 'product');
            $url = get_permalink($product->get_id());
            $order_id = $row->order_id;
            $order = new WC_Order($order_id);
            $user = $order->get_user();
            $user_city = $order->get_billing_city();

            if ($user) {
                $user_id = $user->ID;
            } else {
                $user_id = 'Guest';
            }
            if ($user_info->ID == $user_id) {
                $unix_date  = strtotime($order->order_date);
                $date       = gmdate('d/m/y', $unix_date);

                //Guest from Location (City) just purchased Flying Ninja!
                $html .= '<strong>' . $user->data->display_name . '</strong>' . ' ' . $from . ' ' . $user_city . ' ' . $from_purchased . ' ' . '<a href="' . $url . '">' . $product_name . '</a> ' . PHP_EOL;
            } else {
                $unix_date  = strtotime($order->order_date);
                $date       = gmdate('d/m/y', $unix_date);
                $html .= '<strong>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</strong>' . ' ' . $last_sold_from . ' ' . $user_city . ' ' . $last_sold_just_purchased . '  ' . '<a href="' . $url . '">' . $product_name . '</a> ' . PHP_EOL;

            }
        }
        $html .= PHP_EOL;
    }
    $html .= '</div>' . PHP_EOL;
    return esc_attr($html);
}


function jarvis_get_cart_products()
{

    $your_basket_title = get_option('your_basket_title') ? get_option('your_basket_title') : esc_html('Your Basket', 'jarvis');
    $cart_no_product_found = get_option('cart_no_product_found') ? get_option('cart_no_product_found') : esc_html('You do not have any products in the cart', 'jarvis');
    $cart_total_price = get_option('cart_total_price') ? get_option('cart_total_price') : esc_html('Total Price', 'jarvis');
    $cart_cart_link = get_option('cart_cart_link') ? get_option('cart_cart_link') : esc_html('Cart', 'jarvis');
    $cart_checkout_link = get_option('cart_checkout_link') ? get_option('cart_checkout_link') : esc_html('Checkout', 'jarvis');
    $font_size = get_option('global_widget_font_size') ? get_option('global_widget_font_size') : esc_html('20', 'jarvis');
    $html = '
    <div class="qcld_cart-products">
        <h2 class="jarvis_title" style="font-size: ' . esc_attr($font_size) . 'px ">' . esc_attr($your_basket_title) . '</h2>
        <div class="qcld_cart_prod_table" id="qcld_cart_prod_table">

            <div class="qcld_cart_prod_table_body">';
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $itemCount = count($items);
    if ($itemCount > 0) {

        $html .= '<table width="100%" border="0">
                        <tbody>';

        foreach ($items as $item => $values) {
            // $_product = $values['data']->post;
            //product image
            $product = wc_get_product($values['product_id']);
            $price = get_post_meta($values['product_id'], '_price', true);
            //<td class="cartImg">' . $product->get_image() . '</td>
            $html .= '
                            <tr>
                                <td class="cartTitle">' . esc_attr($product->get_title()) . '</td>
                                <td class="cartPrice">' . get_woocommerce_currency_symbol() . esc_attr($price) . '</td>
                                <td class="qcld_cart_qty">' . esc_attr($values['quantity']) . '<span class="qcld-cart-item-remove" jarvis-cart-item="' . esc_attr($item) . '" >X</span></td>
                            </tr>';
        }
        $html .= '</tbody>
                    </table><div class="cart-total">' . esc_attr($cart_total_price) . ' : ' . $woocommerce->cart->get_cart_total() . ' </div>';
    } else {
        $html .= '<div class="qcld_no_cartprods">' . esc_attr($cart_no_product_found) . '</div>';
    }
    $html .= '</div>

<div class="qcld_checkout_buttons"><a href="' . esc_url(wc_get_cart_url()) . '"
                                              class="jarvis-button jarvis-button-cart">' . esc_attr($cart_cart_link) . '</a><a
                    href="' . esc_url(wc_get_checkout_url()) . '" class="jarvis-button jarvis-button-checkout">' . esc_attr($cart_checkout_link) . '</a>
        </div>
        </div>
        
    </div>';

    if (get_option('cart_products')) {
        return $html;
    }

}

function wsaj_track_product_view()
{
    if (!is_singular('product')) {
        return;
    }

    global $post;


    $viewed_products = (isset($_COOKIE['woocommerce_recently_viewed']) && !empty($_COOKIE['woocommerce_recently_viewed']) ) ? (array)explode('|',sanitize_text_field(wp_unslash($_COOKIE['woocommerce_recently_viewed']))) : array();

    if (!in_array($post->ID, $viewed_products)) {
        $viewed_products[] = $post->ID;
    }

    if (sizeof($viewed_products) > 15) {
        array_shift($viewed_products);
    }

    // Store for session only
    wc_setcookie('woocommerce_recently_viewed', implode('|', $viewed_products));
}

add_action('template_redirect', 'wsaj_track_product_view', 20);
?>
<?php
function extract_shortcode_from_content($the_content)
{

    $shortcode = "";
    $pattern = get_shortcode_regex();
    preg_match_all('/' . $pattern . '/uis', $the_content, $matches);

    for ($i = 0; $i < 40; $i++) {

        if (isset($matches[0][$i])) {
            $shortcode .= $matches[0][$i];
        }

    }
    $shortcode = str_replace("][", "#", $shortcode);
    $shortcode = str_replace("[", "", $shortcode);
    $shortcode = str_replace("]", "", $shortcode);
    $shortcode = explode("#", $shortcode);
    return $shortcode;

}

function wsaj_recently_viewed_product_widget($atts = array(), $content = null, $tag= null)
{
    // Get WooCommerce Global
    global $post, $woocommerce, $product;
    $recently_viewed_title = get_option('recently_viewed_products_title') ? get_option('recently_viewed_products_title') : esc_html('Recently Viewed Products', 'jarvis');
    $recently_viewed_add_to_cart = get_option('recently_viewed_add_to_cart') ? get_option('recently_viewed_add_to_cart') : esc_html('Add to cart', 'jarvis');
    $recently_viewed_details = get_option('recently_viewed_details') ? get_option('recently_viewed_details') : esc_html('View Detail', 'jarvis');
    $recently_viewed_no_product_founnd = get_option('recently_viewed_no_product_founnd') ? get_option('recently_viewed_no_product_founnd') : esc_html('You have not viewed any products yet !', 'jarvis');
    $font_size = get_option('global_widget_font_size') ? get_option('global_widget_font_size') : '20';
    // Get recently viewed product cookies data
    $viewed_products = ( isset($_COOKIE['woocommerce_recently_viewed']) && !empty(sanitize_text_field(wp_unslash($_COOKIE['woocommerce_recently_viewed']))) ) ? (array)explode('|', sanitize_text_field(wp_unslash($_COOKIE['woocommerce_recently_viewed']))) : array();
    $viewed_products = array_filter(array_map('absint', $viewed_products));


    // If no data, quit

    //var_dump($viewed_products);
    if (!empty($viewed_products)) {
        $wp_query = new WP_Query(array(
            'posts_per_page' => 10,
            'no_found_rows' => 1,
            'post_status' => 'publish',
            'post_type' => 'product',
            'post__in' => $viewed_products,


        ));

        ob_start();


        $html = '<div class="jarvis_recently_viewed_products">
    <h2 class="jarvis_title" style="font-size:' . $font_size . 'px ">' . $recently_viewed_title . '</h2>
    <div class="qcld_cart_prod_table" >
    <div class="qcld_jarvis_recently_viewed_body">
    <ul class="jarvis_product_list">';
        while ($wp_query->have_posts()) : $wp_query->the_post();
            global $post, $product;
            $html .= '<li class="jarvis_product">
            <a href="' . esc_url(get_permalink($product->get_id())) . '"
               title="' . esc_attr($product->get_title()) . '">
                ' . $product->get_image() . '
            </a>
             <a href="' . esc_url(get_permalink($product->get_id())) . '"
               title="' . esc_attr($product->get_title()) . '">
                <h3 class="jarvis_product_title">' . $product->get_title() . '</h3>
                </a>
             <span class="price">' . $product->get_price_html() . '</span>';

            if ($product->is_type('simple')) {
                $html .= '<a rel="nofollow" href="' . get_site_url() . '?add-to-cart=' . $product->get_id() . '" data-quantity="1" data-product_id="' . $product->get_id() . '" data-product_sku="" class="add_to_cart_button ajax_add_to_cart jarvis-button jarvis-button-cart">' . $recently_viewed_add_to_cart . '</a>';
            } elseif ($product->is_type('variable')) {
                $html .= '<a href="' . esc_url(get_permalink($product->get_id())) . '" title="' . esc_attr($product->get_title()) . '" class="jarvis-button jarvis-button-cart">' . $recently_viewed_details . '</a>';
            }


            $html .= '<!--<a rel="nofollow" href="' . esc_url(get_permalink($product->get_id())) . '"
               class="jarvis-button jarvis-button-cart">Add to Cart</a>-->
            </li>';


        endwhile;
        $html .= '</ul></div></div></div>';
        if (get_option('recent_products')) {
            return $html;
        } else {
            $html = '';
            return $html;
        }
        wp_reset_query();
        wp_reset_postdata();
    } else {
        $html = '<div class="jarvis_recently_viewed_products">
    <h2 class="jarvis_title" style="font-size:' . $font_size . 'px ">' . $recently_viewed_title . '</h2>
    <div class="qcld_cart_prod_table" >
    <div class="qcld_jarvis_recently_viewed_body">';

        $html .= '<p style="text-align: center">' . $recently_viewed_no_product_founnd;
        $html .= '</div></div></div>';

        return $html;
    }

}

function wsaj_recently_viewed_products($atts, $content = null)
{
    // Get WooCommerce Global
    global $post, $woocommerce, $product;
    $recently_viewed_title = get_option('recently_viewed_products_title') ? get_option('recently_viewed_products_title') : esc_html('Recently Viewed Products', 'jarvis');
    $font_size = get_option('global_widget_font_size') ? get_option('global_widget_font_size') : '20';
    // Get recently viewed product cookies data
    $viewed_products = ( isset($_COOKIE['woocommerce_recently_viewed']) && !empty($_COOKIE['woocommerce_recently_viewed']) ) ? (array)explode('|', sanitize_text_field(wp_unslash($_COOKIE['woocommerce_recently_viewed']))) : array();
    $viewed_products = array_filter(array_map('absint', $viewed_products));


    // If no data, quit

    //var_dump($viewed_products);
    if (!empty($viewed_products)) {
        $wp_query = new WP_Query(array(
            'posts_per_page' => 10,
            'no_found_rows' => 1,
            'post_status' => 'publish',
            'post_type' => 'product',
            'post__in' => $viewed_products,


        ));

        ob_start();


        $html = '<div class="jarvis_recently_viewed_products">
<h2 class="jarvis_title" style="font-size:' . $font_size . 'px ">' . $recently_viewed_title . '</h2>
<div class="qcld_cart_prod_table" >
<div class="qcld_jarvis_recently_viewed_body">
<ul class="jarvis_product_list">';
        while ($wp_query->have_posts()) : $wp_query->the_post();
            global $post, $product;
            $html .= '<li class="jarvis_product">
            <a href="' . esc_url(get_permalink($product->get_id())) . '"
               title="' . esc_attr($product->get_title()) . '">
                ' . $product->get_image() . '
            </a>
             <a href="' . esc_url(get_permalink($product->get_id())) . '"
               title="' . esc_attr($product->get_title()) . '">
                <h3 class="jarvis_product_title">' . $product->get_title() . '</h3>
                </a>
             <span class="price">' . $product->get_price_html() . '</span>';

            if ($product->is_type('simple')) {
                $html .= '<a rel="nofollow" href="' . get_site_url() . '?add-to-cart=' . $product->get_id() . '" data-quantity="1" data-product_id="' . $product->get_id() . '" data-product_sku="" class="add_to_cart_button ajax_add_to_cart jarvis-button jarvis-button-cart">Add to cart</a>';
            } elseif ($product->is_type('variable')) {
                $html .= '<a href="' . esc_url(get_permalink($product->get_id())) . '" title="' . esc_attr($product->get_title()) . '" class="jarvis-button jarvis-button-cart">View Detail</a>';
            }


            $html .= '<!--<a rel="nofollow" href="' . esc_url(get_permalink($product->get_id())) . '"
               class="jarvis-button jarvis-button-cart">Add to Cart</a>-->
            </li>';


        endwhile;
        $html .= '</ul></div></div></div>';
        if (get_option('recent_products')) {
            return $html;
        } else {
            $html = '';
            return $html;
        }
        wp_reset_query();
        wp_reset_postdata();
    } else {
        $html = '<div class="jarvis_recently_viewed_products">
<h2 class="jarvis_title" style="font-size:' . $font_size . 'px ">' . $recently_viewed_title . '</h2>
<div class="qcld_cart_prod_table" >
<div class="qcld_jarvis_recently_viewed_body">';

        $html .= '<p style="text-align: center">You have not viewed any products yet !';
        $html .= '</div></div></div>';

        return $html;
    }


}


add_action('wp_footer', 'woojarvis_load_footer_html');
function woojarvis_load_footer_html()
{ ?>
    <div id="jarvis-icon-container">

    <?php if (get_option('disable_jarvis') == 1):
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    //$items = $woocommerce->cart->get_cart_contents_count();
    $itemCount = count($items);
    if (get_option('jarvis_mode') == 'full') {
        //Javris first mode.
        ?>
        <div id="jarvis-full-page-wrapper">
            <a id="genie-lamp" href="#genie-target"><img
                    src="<?php echo esc_url(QC_JARVIS_IMG_URL) . '/' . esc_attr(get_option('jarvis_icon')); ?>" alt=""> </a>
            <?php if (get_option('disable_cart_item') != 1) { ?>
                <a id="genie-cart" href="<?php echo esc_url(site_url()); ?>/cart"> <span style=" top: -2px;position: relative;"
                                                                                class="genie-cart-items"><?php echo esc_attr($itemCount); ?></span>
                </a>
            <?php } ?>

        </div>
    <?php } else if (get_option('jarvis_mode') == 'quickball') {
        //Pin Ball  effect html start
        ?>
        <div id="jarvis-ball-wrapper">

            <div id="jarvis-pinball-box" style="display:none" class="jarvis-ball-container animated">
                <div class="jarvis-ball-inner">

                </div>
            </div>
            <!--jarvis-ball-container-->
            <div id="jarvis-ball" class="jarvis-ball">
                <ul>
                    <?php if (get_option('recent_products') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="1"><a style="cursor:pointer"></a></li>
                    <?php }
                    if (get_option('recommended_products') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="2"><a style="cursor:pointer"> </a></li>
                    <?php }
                    if (get_option('cart_products') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="3"><a style="cursor:pointer"><span
                                    class="genie-cart-items"><?php echo esc_attr($itemCount); ?></span></a></li>
                    <?php }
                    if (get_option('jarvis_search') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="4"><a style="cursor:pointer"></a></li>
                    <?php }
                    if (get_option('custom_quickball') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="5"><a
                                style="background-image: url(<?php echo esc_url(QC_JARVIS_IMG_URL) . 'quickball-icon/custom_quickball_icon.png'; ?>) !important;background-size: 30px;"
                                href="<?php echo esc_url(get_option('custom_quickball_link') != '' ? get_option('custom_quickball_link') : site_url()); ?>"
                                target="_blank"></a></li>

                    <?php }
                    if (get_option('support_quickball') != "") { ?>
                        <li class="jarvis-ball-style" data-ball-style="6"><a style="cursor:pointer"></a></li>
                    <?php } ?>

                </ul>
                <img src="<?php echo esc_url(QC_JARVIS_IMG_URL) . '/' . esc_attr(get_option('jarvis_icon')); ?>" alt="jarvis-icoon">
                <?php if (get_option('disable_cart_item') != 1) { ?>
                    <span class="genie-cart-items"><?php echo esc_attr($itemCount); ?></span>
                <?php } ?>
            </div>
            <!--container-->
        </div>
        <!--jarvis-ball-wrapper-->
        <?php

    } //Pin Ball  effect html end
    ?>

    <span class="tooltip-effect-4 qcld_jarvis_tooltip_genie_cart" id="qcld_jarvis_tooltip_genie_cart">
        <!--<span class="tooltip-item">quasar</span>-->
        <span class="tooltip-content">
            <ul>
                <li id="genie-cart-added-message"></li>
            </ul>
        </span>
    </span>
    <?php $show_sales_alert = get_option('fake_sale_notification');
    if ($show_sales_alert == "") { ?>

        <div id="jarvis_sell_notification">


            <?php $sale_notification_sound = get_option('fake_sale_notification_sound');
            if ($sale_notification_sound == "") { ?>
                <audio id="woocommerce-notification-audio">
                    <source src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/sell-notification.mp3"></source>
                </audio>
            <?php } ?>


            <div id="message-purchased" class="customized " style="display: none;"
                 data-next_item="2"
                 data-loop="1"
                 data-initial_delay="5"
                 data-notification_per_page="30"
                 data-display_time="10"
                 data-next_time="20">
            </div>


            <script type='text/javascript'>
                var wnotification_ajax_url = "<?php echo esc_url(get_site_url()); ?>/wp-admin/admin-ajax.php"
            </script>

        </div>
        <?php if (get_option('sell_notification_off_mobile') == "sell_notification_off_mobile") {
            //echo get_option('sell_notification_off_mobile');

            ?>
            <style>
                @media screen and (max-width: 640px) {
                    #jarvis_sell_notification {
                        display: none;
                    }
                }
            </style>

        <?php } ?>


    <?php } ?>


    <?php $message_status = get_option('disable_notification');
    if ($message_status == 1) {
        // Nothing to do
    } else {
        ?>
        <span class="tooltip-effect-4 qcld_jarvis_tooltip" id="qcld_jarvis_tooltip"
              data-loop="<?php if (get_option('loop_notification') == 1) {
                  echo 1;
              } else {
                  echo 0;
              }; ?>"><!--<span class="tooltip-item">quasar</span>--><span
                class="tooltip-content">
<ul class="qcld_jarvis_msg" id="qcld_jarvis_msg"
    data-global-timer="<?php echo esc_attr(get_option('global_notification_delay_time')); ?>">
    <?php if (get_option('message_one') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_one') ? get_option('notification_delay_one') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_one')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_two') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_two') ? get_option('notification_delay_two') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_two')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_three') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_three') ? get_option('notification_delay_three') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_three')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_four') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_four') ? get_option('notification_delay_four') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_four')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_five') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr( get_option('notification_delay_five') ? get_option('notification_delay_five') : get_option('global_notification_delay_time') ); ?>"><?php echo esc_attr(get_option('message_five')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_six') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_six') ? get_option('notification_delay_six') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_six')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_seven') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr( get_option('notification_delay_seven') ? get_option('notification_delay_seven') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_seven')); ?></li>
    <?php endif; ?>
    <?php if (get_option('message_eight') != ''): ?>
        <li class="jarvisMsgItem"
            data-timer="<?php echo esc_attr(get_option('notification_delay_eight') ? get_option('notification_delay_eight') : get_option('global_notification_delay_time')); ?>"><?php echo esc_attr(get_option('message_eight')); ?></li>
    <?php endif; ?>
</ul>
</span></span>
        </div>
    <?php } ?>


    <?php
    $theme_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', get_option('jarvis_theme'));
    if ($theme_name == 'theme-one') {
        include_once('templates/template-one/template.php');
    } else if ($theme_name == 'theme-two') {
        include_once('templates/template-two/template.php');
    } else if ($theme_name == 'theme-three') {
        include_once('templates/template-three/template.php');
    } else if ($theme_name == 'theme-four') {
        include_once('templates/template-four/template.php');
    }
    ?>

<?php endif;

}


//add_filter('woocommerce_add_to_cart_fragments', 'qcld_jarvis_ajax_get_cart_products');


do_action('woocommerce_set_cart_cookies', TRUE);


add_action('wp_ajax_get_cart_products', 'qcld_jarvis_ajax_get_cart_products');
add_action('wp_ajax_nopriv_get_cart_products', 'qcld_jarvis_ajax_get_cart_products');


function qcld_jarvis_ajax_get_cart_products()
{

    check_ajax_referer( 'jarvis', 'security');

    global $woocommerce;
    $cart_no_product_found = get_option('cart_no_product_found') ? get_option('cart_no_product_found') : esc_html('You do not have any products in the cart', 'jarvis');
    $cart_total_price = get_option('cart_total_price') ? get_option('cart_total_price') : esc_html('Total Price', 'jarvis');
    $html = '<div class="qcld_cart_prod_table_body">';

    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $itemCount = count($items);
    if ($itemCount > 0) {

        $html .= '<table width="100%" border="0">
                        <tbody>';

        foreach ($items as $item => $values) {
            //$_product = $values['data']->post;
            //product image
            $product = wc_get_product($values['product_id']);
            $price = get_post_meta($values['product_id'], '_price', true);
            //<td class="cartImg">' . $product->get_image() . '</td>
            $html .= '
                            <tr>
                                <td class="cartTitle">' . $product->get_title() . '</td>
                                <td class="cartPrice">' . get_woocommerce_currency_symbol() . $price . '</td>
                                <td class="qcld_cart_qty">' . esc_attr($values['quantity']) . '<span class="qcld-cart-item-remove" jarvis-cart-item="' . esc_attr($item) . '" >X</span></td>
                            </tr>';
        }
        $html .= '</tbody>
                    </table><div class="cart-total">' . esc_attr($cart_total_price) . ' : ' . $woocommerce->cart->get_cart_total() . ' </div>';
    } else {
        $html .= '<div class="qcld_no_cartprods">' . esc_attr($cart_no_product_found) . '</div>';
    }
    $html .= '</div>';
    echo $html;


    wp_die();
}


add_action('wp_ajax_qcld_jarvis_get_sold_products', 'qcld_jarvis_get_sold_products');
add_action('wp_ajax_nopriv_qcld_jarvis_get_sold_products', 'qcld_jarvis_get_sold_products');

function qcld_jarvis_get_sold_products()
{
    $from = get_option('user_from_title') ? get_option('user_from_title') : ' ';
    $from_purchased = get_option('user_purchased_a_title') ? get_option('user_purchased_a_title') : ' ';
    $artificial_sale_data = json_decode(get_option('artificial_orders_val'));

    $min = 10;
    $max = 60;
    //var_dump( $artificial_sale_data);

    $i = 0;
    foreach ($artificial_sale_data as $artificial_sale) {
        $i++;

        $msgCount = count($artificial_sale_data);
        // count($artificial_sale_data);

        if ($i < $msgCount) {
            $next_item = $i;
        } else {
            $next_item = 1;
        }

        $html[$i] = '<div id="message-purchased" class="customized " style="display: none;"
			 data-next_item="' . ($i + 1) . '"
			 data-loop="1"
			 data-initial_delay="5"
			 data-notification_per_page="30"
			 data-display_time="' . esc_attr($artificial_sale->notification_duration) . '"
			 data-next_time="20">
		
			<img src="' . esc_url(get_site_url()) . get_the_post_thumbnail($artificial_sale->product_id) . '" class="wcn-product-image"><p>' . esc_attr($artificial_sale->customer_name) . ' ' . esc_attr($from) . ' ' . esc_attr($artificial_sale->customer_address) . ' ' . esc_attr($from_purchased) . ' ' . '  <a href="' . esc_url(get_site_url()) . '/product/woo-album-2/">' . get_the_title($artificial_sale->product_id) . '</a>
		 		<small>' . wp_rand($min, $max) . ' min ago </small>
		</p><span id="notify-close"></span>
			
		</div>';


    }

//var_dump($html);
    $itemNumber = wp_rand(1, $msgCount);
    //$itemNumber = $_REQUEST['next_item'];
    $real_sold_products = last_sold_product();
    if ($real_sold_products != '') {
        echo esc_attr("real sell");
        echo $real_sold_products;
    } else {
        echo esc_attr("Fake");
        echo $html[$itemNumber];
    }


    wp_die();
}

add_action('wp_ajax_get_admin_order_notification_item', 'qcld_jarvis_get_admin_order_notification_item');
add_action('wp_ajax_nopriv_admin_order_notification_item', 'qcld_jarvis_get_admin_order_notification_item');

function qcld_jarvis_get_admin_order_notification_item()
{

    check_ajax_referer( 'jarvis', 'security');

    global $woocommerce;
    $params = array('posts_per_page' => -1, 'post_type' => 'product','post_status' => 'publish');
    //$wc_query = new WP_Query($params);
    $products = get_posts($params);
    $fake_product = (get_option('fake-product-one'));
    //$_pf = new WC_Product_Factory();
    $fake_product = wc_get_product($fake_product);

    $html = '<div class="block-inner">
   <div class="col-xs-12 text-right"> <button type="button"  class="btn btn-danger qcld-remove-on-item "><i class="fa fa-times" aria-hidden="true"></i>
 Remove</button></div>
    <div class="row"><div class="col-xs-12">';
    $html .= '';

    $html .= '<div class="cxsc-settings-blocks"> <h2>Select a product</h2>';
    $html .= '<div class="form-group"><select name="fake-product-one" class="jarvis_select_two">';

    $html .= '<option value="' . esc_attr($fake_product->id) . ' selected="selected">' . esc_attr($fake_product->get_title()) . '</option>';
    foreach ($products as $P) {
        $html .= '<option value="' . esc_attr($P->ID) . '">' . esc_attr(get_the_title($P->ID)) . '</option>';
    }
    $html .= '</select></div>';
    $html .= '</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			<p class="qc-opt-description"></p>
			<div class="cxsc-settings-blocks">
				<p><strong>Customer Name</strong></p>';
    $html .= '<input type="text" class="form-control customer-name" name="customer_name_one" ';
    if (get_option('customer_name_one')) {
        $html .=get_option('customer_address_one');
    }
    $html .= '" placeholder="Customer Name">';
    $html .= '<p><strong>Customer Address</strong></p>
				<textarea name="customer_address_one" class="form-control customer-address" cols="30" rows="2">';
    if (get_option('customer_address_one')) {
        $html .=get_option('customer_address_one');
    }
    $html .= '</textarea>';
    $html .= '<p>Fake Sale Notification Duration Time</p>';
    $html .= '<p><input class="form-control input-sm notification-duration" type="text" value="' . esc_attr(get_option('fake_sale_notification_delay_one')) . '" name="fake_sale_notification_delay_one"> <strong> in second</strong></p>';
    //end
    $html .= '</div>
		</div>
	</div>
	</div>';
    echo wp_send_json($html);
    wp_die();
}

/***************** On page ajax search and others start******************/
//Ajax based search method
add_action('wp_ajax_get_jarvis_ajax_search_products', 'qcld_jarvis_get_jarvis_ajax_search_products');
add_action('wp_ajax_nopriv_get_jarvis_ajax_search_products', 'qcld_jarvis_get_jarvis_ajax_search_products');

function qcld_jarvis_get_jarvis_ajax_search_products()
{
    //check_ajax_referer( 'jarvis', 'security');
    global $woocommerce, $wp_query, $wpdb;
    $product_categories = (isset($_GET['sa_product_cat'])) ? sanitize_text_field(wp_unslash($_GET['sa_product_cat'])) : null;
    $product_tags = (isset($_GET['sa_product_tag'])) ? sanitize_text_field(wp_unslash($_GET['sa_product_tag'])) : null;
    $min_price = (isset($_GET['sa_min_price'])) ? sanitize_text_field(wp_unslash($_GET['sa_min_price'])) : null;
    $max_price = (isset($_GET['sa_max_price'])) ? sanitize_text_field(wp_unslash($_GET['sa_max_price'])) : null;

    if (isset($product_categories) || ($product_tags)) {
        add_filter("woocommerce_is_filtered", 'woocommerce_is_filtered');
    }

    $tax_query = array('relation' => 'AND',);
    $meta_query = array('relation' => 'AND',);

    // Product Attributes
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    $selected_attributes = false;

    if ($attribute_taxonomies) {

        foreach ($attribute_taxonomies as $tax) {

            $attribute = sanitize_title($tax->attribute_name);
            $taxonomy = wc_attribute_taxonomy_name($attribute);

            // create an array of product attribute taxonomies
            $_attributes_array[] = $taxonomy;

            $name = 'sa_filter_' . $attribute;

            if (isset($_GET[$name]) && !empty($_GET[$name]) && taxonomy_exists($taxonomy)) {
                add_filter("woocommerce_is_filtered", 'woocommerce_is_filtered');
                $selected_attributes[$taxonomy]['terms'] = isset($_GET[$name]) ? sanitize_text_field(wp_unslash($_GET[$name])) : '';

            }
        }

        if ($selected_attributes) {

            foreach ($selected_attributes as $key => $value) {

                $tax_query[] = array(
                    'taxonomy' => $key,
                    'field' => 'id',
                    'terms' => $value["terms"],
                    'operator' => 'IN'
                );

            }

        }
    }

    // Product Categories
    if ((isset($product_categories)) && ($product_categories != "")) {

        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'id',
            'terms' => $product_categories,
            'operator' => 'IN'
        );

    }

    // Product Tags
    if ((isset($product_tags)) && ($product_tags != "")) {

        $tax_query[] = array(
            'taxonomy' => 'product_tag',
            'field' => 'id',
            'terms' => $product_tags,
            'operator' => 'IN'
        );

    }
    //pricing
    if ($min_price != null && $max_price != null) {
        $meta_query[] = array(
            'key' => '_price',
            'value' => array($min_price, $max_price),
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC'
        );
    }
    $argu_params = array(
        'posts_per_page' => -1,
        'post_type' => array('product'),
        'post_status' => 'publish',
        'tax_query' => $tax_query,
        'meta_query' => $meta_query,
    );

    $product_query = new WP_Query($argu_params);
    $product_num = $product_query->post_count;
    if ($product_num > 0) {
        $html = '<p class="ajax-search-message" style="border:1px green solid ;">' . esc_attr(get_option('p_scs_msg')) . '</p>';
    } else {
        $html = '<p class="ajax-search-message" style="border:1px red solid ;">' . esc_attr(get_option('p_fail_msg')) . '</p>';
    }

    $html .= '<div class="jarvis-featured-products">';

    //$_pf = new WC_Product_Factory();
    //repeating the products
    if ($product_num > 0) {

        $html .= '<ul class="jarvis_products">';
        while ($product_query->have_posts()) : $product_query->the_post();
            $product = wc_get_product(get_the_ID());
            //$qcld_thumb = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'shop_thumbnail' );
            $html .= '<li class="jarvis-product">';
            $html .= '<a href="' . get_permalink(get_the_ID()) . '" title="' . esc_attr($product->get_title() ? $product->get_title() : get_the_ID()) . '">';
            $html .= get_the_post_thumbnail(get_the_ID(), 'shop_thumbnail') . '</a>
       <div class="jarvis-product-summary">
       <div class="jarvis-product-table">
       <div class="jarvis-product-table-cell">
       <h3 class="jarvis_product_title"><a href="' . get_permalink(get_the_ID()) . '" title="' . esc_attr($product->get_title() ? $product->get_title() : get_the_ID()) . '">' . esc_attr($product->get_title()) . '</a></h3>
       <div class="price">' . $product->get_price_html() . '</div>';

            if ($product->is_type('simple')) {
                $html .= '<a href="' . esc_url(get_site_url()) . '?add-to-cart=' . esc_attr(get_the_ID()) . '"  title="' . esc_attr($product->get_title() ? $product->get_title() : get_the_ID()) . '"  class="jarvis-button jarvis-button-cart add_to_cart_button ajax_add_to_cart"  data-quantity="1" data-product_id="' . esc_attr(get_the_ID()) . '" >Add to Cart</a>';
            } else {
                $html .= '<a href="' . get_permalink(get_the_ID()) . '"  title="' . esc_attr($product->get_title() ? $product->get_title() : get_the_ID()) . '"  class="jarvis-button jarvis-button-cart"  >View Detail</a>';
            }
            $html .= ' </div>
       </div>
       </div>
       </li>';
        endwhile;
        wp_reset_postdata();
        $html .= '</ul>';
    }
    $html .= '</div>';
    $response = array('html' => $html, 'products_num' => $product_num);
    echo wp_send_json($response);
    wp_die();
}

//Add to cart simple product by ajax
add_action('wp_ajax_qcld_jarvis_add_to_cart', 'qcld_jarvis_add_to_cart');
add_action('wp_ajax_nopriv_qcld_jarvis_add_to_cart', 'qcld_jarvis_add_to_cart');


function qcld_jarvis_add_to_cart()
{
    check_ajax_referer( 'jarvis', 'security');
    $product_id         = isset($_POST['product_id']) ? stripslashes(sanitize_text_field(wp_unslash($_POST['product_id']))) : '';
    $product_quantity   = isset($_POST['quantity'])   ? stripslashes(sanitize_text_field(wp_unslash($_POST['quantity']))) : '';
    global $woocommerce;
    $result = $woocommerce->cart->add_to_cart($product_id, $product_quantity);
    if ($result != false) {
        echo wp_send_json(esc_attr('simple'));
    } else {
        echo wp_send_json(esc_attr('error'));
    }
    wp_die();
}

//Genie cart get cart items number and product title
add_action('wp_ajax_get_cart_items_num_product_title', 'qcld_jarvis_get_cart_items_num_product_title');
add_action('wp_ajax_nopriv_get_cart_items_num_product_title', 'qcld_jarvis_get_cart_items_num_product_title');

function qcld_jarvis_get_cart_items_num_product_title()
{
    check_ajax_referer( 'jarvis', 'security');
    $product_id = isset($_POST['product_id']) ? sanitize_text_field(wp_unslash($_POST['product_id'])) : '';
    $_pf = new WC_Product_Factory();
    //getting cart items number getting.
    global $woocommerce;
    $itemCount = $woocommerce->cart->get_cart();
    //getting product title
    $product = wc_get_product($product_id);
    $product_title = $product->get_title();
    $response = array('items_number' => $itemCount, 'title' => "<strong>" . $product_title . "</strong> added to the cart!");
    echo wp_send_json($response);
    wp_die();
}

//Genie cart get cart items number and product title
add_action('wp_ajax_remove_item_numbers_genie_cart', 'qcld_jarvis_remove_item_numbers_genie_cart');
add_action('wp_ajax_nopriv_remove_item_numbers_genie_cart', 'qcld_jarvis_remove_item_numbers_genie_cart');

function qcld_jarvis_remove_item_numbers_genie_cart()
{
    check_ajax_referer( 'jarvis', 'security');

    //getting cart items number getting.
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $itemCount = count($items);
    echo wp_send_json($itemCount);
    wp_die();
}

/***************** On page ajax search and others end ******************/
/***************** Pinball Start******************/
add_action('wp_ajax_pin_ball_mode', 'qcld_jarvis_pin_ball_mode');
add_action('wp_ajax_nopriv_pin_ball_mode', 'qcld_jarvis_pin_ball_mode');

function qcld_jarvis_pin_ball_mode()
{
    check_ajax_referer( 'jarvis', 'security');

    $style = isset($_POST['style']) ? sanitize_text_field(wp_unslash($_POST['style'])) : '';
    $html = "<div class='animated'>";
    if ($style == 6) { //Jarvis Support
        $html .= '<div class="jarvis-support-form-container"> 
                 <h2 class="jarvis_title">'.esc_html(get_option('jarvis_support_form_title') ? get_option('jarvis_support_form_title') : 'Questions or Comments?
Contact Us', 'jarvis').'</h2>
                <div class="jarvis-support-form-fullname">
                    <input id="jarvis_support_fullname" type="text" name="jarvis_support_fullname" placeholder="'.esc_html("Full Name", "jarvis").'">
                </div>
                <div class="jarvis-support-form-email">
                    <input id="jarvis_support_email" type="text" name="jarvis_support_email" placeholder="'.esc_html("Email Address", "jarvis").'">
                </div>
                <div class="jarvis-support-form-message">
                    <textarea  id="jarvis_support_Message" type="text" name="jarvis_support_Message" rows="4" cols="20" placeholder="'.esc_html("Message", "jarvis").'"></textarea>
                </div>
                <div class="jarvis-support-form-captcha"> 
                   <div id="jarvis-captcha" class="jarvis-captcha-view">
                     <span id="jarvis-captcha-code"></span>
                     <button type="button" id="javis-captcha-refresh">'.esc_html("Refresh", "jarvis").'</button>
                   </div>
                   <div class="jarvis-captcha-fields">
                      <input type="text" id="javis-captcha" class="captcha" maxlength="4" size="4" placeholder="'.esc_html("Enter captcha code", "jarvis").'" tabindex=3 />
                   </div>
                </div>
                <div class="jarvis-support-form-submit">
                    <span id="jarvis-support-form-validation" style="color:red"></span>
                    <button type="button" id="jarvis-support-form-submit">'.esc_html("Submit", "jarvis").'</button>
                </div>
                </div>';
    } else if ($style == 4) { //Search features
        $html .= do_shortcode("[qc_jarvis]");
    } else if ($style == 3) { //cart show
        $html .= do_shortcode("[jarvis-cart-products]");
    } else if ($style == 2) { //Recomended products
        $html .= do_shortcode("[jarvis_recommended_products]");
    } else if ($style == 1) { //recent viewed products
        $html .= do_shortcode("[jarvis-recently-viewed-products]");
    }
    $html .= "</div>";
    echo wp_send_json($html);
    wp_die();
}

add_action('wp_ajax_pin_ball_support', 'qcld_jarvis_pin_ball_support');
add_action('wp_ajax_nopriv_pin_ball_support', 'qcld_jarvis_pin_ball_support');

function qcld_jarvis_pin_ball_support()
{
    check_ajax_referer( 'jarvis', 'security');
    $name       = isset($_POST['name'])     ? trim(sanitize_text_field(wp_unslash($_POST['name']))) : '';
    $email      = isset($_POST['email'])    ? trim(sanitize_email(wp_unslash($_POST['email']))) : '';
    $message    = isset($_POST['message'])  ? trim(sanitize_text_field(wp_unslash($_POST['message']))) : '';
    $subject    = esc_html('Support Email from Jarvis by Client', 'jarvis');
    //Extract Domain
    $url = get_site_url();
    $url = wp_parse_url($url);
    $domain = $url['host'];
    //$support_email = "admin@" . $domain;
    $support_email = get_option('admin_email');
    $toEmail = get_option('support_quickball_email') != '' ? get_option('support_quickball_email') : $support_email;
    $fakeFromEmailAddress = "wordpress@" . $domain;
    //Starting messaging and status.
    $response['status'] = 'fail';
    $response['message'] = esc_html('Sorry! fail to send email.', 'jarvis');



    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $response['message'] = esc_html('Invalid email address.', 'jarvis');
        $response['status'] = 'fail';
    } else {


        //build email body
        $bodyContent = "";

        $bodyContent .= "<p><strong>".esc_html('Support Request Details:', 'jarvis')."</strong></p><hr>";

        $bodyContent .= "<p>".esc_html('Name', 'jarvis')." : " . $name . "</p>";
        $bodyContent .= "<p>".esc_html('Email', 'jarvis')." : " . $email . "</p>";
        $bodyContent .= "<p>".esc_html('Subject', 'jarvis')." : " . $subject . "</p>";
        $bodyContent .= "<p>".esc_html('Message', 'jarvis')." : " . $message . "</p>";

        $bodyContent .= "<p>".esc_html('Mail Generated on:', 'jarvis')." " . gmdate("F j, Y, g:i a") . "</p>";


        $to = $toEmail;
        $body = $bodyContent;
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $name . ' <' . $fakeFromEmailAddress . '>';
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';

        $result = wp_mail($to, $subject, $body, $headers);

        if($result){
            $response['status'] = 'success';
            $response['message'] = esc_html('Your email was sent successfully. Thanks!', 'jarvis');

        }
        
    }
    ob_clean();
    echo wp_json_encode($response);
    die();

}

/***************** Pinball ENd******************/
/***************** Remove Cart item from global cart ******************/
add_action('wp_ajax_jarvis_cart_item_remove', 'qcld_jarvis_cart_item_remove');
add_action('wp_ajax_nopriv_jarvis_cart_item_remove', 'qcld_jarvis_cart_item_remove');
function qcld_jarvis_cart_item_remove()
{
    check_ajax_referer( 'jarvis', 'security');
    //getting cart items n
    $cart_item_key = isset($_POST['cart_item']) ? trim(sanitize_text_field(wp_unslash($_POST['cart_item']))) : '';
    global $woocommerce;
    $result = $woocommerce->cart->remove_cart_item($cart_item_key);
    echo wp_send_json($result);
    wp_die();
}

/*
add_action('wp_default_scripts', function ($scripts) {
    if (!empty($scripts->registered['jquery'])) {
        $scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
    }
});*/