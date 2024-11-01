<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_Widget_Jarvis_Recent_Viewed_Products extends WP_Widget
{
    var $woo_widget_cssclass;
    var $woo_widget_description;
    var $woo_widget_idbase;
    var $woo_widget_name;

    /**
     * constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {


        /* Widget variable settings. */
        $this->woo_widget_cssclass = 'woocommerce widget_jarvis_recently_viewed_products';
        $this->woo_widget_description = __('JARVIS recently viewed products', 'jarvis');
        $this->woo_widget_idbase = 'jarvis_recently_viewed_product';
        $this->woo_widget_name = __('JARVIS Recently Viewed Product', 'jarvis');

        /* Widget settings. */
        $widget_ops = array('classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description);

        /* Create the widget. */
        parent::__construct('jarvis_widget_recently_viewed_products', $this->woo_widget_name, $widget_ops);
    }


    /**
     * widget function.
     *
     * @see WP_Widget
     * @access public
     * @param array $args
     * @param array $instance
     * @return void
     */
    function widget($args, $instance)
    {

        extract($args);

        $defaults = array(
            'jarvis_title' => '',
            'jarvis_font_size' => '26',
        );
        $instance = wp_parse_args($instance, $defaults);

        $title = apply_filters('widget_title', $instance['jarvis_title'], $instance, $this->id_base);
        $font_size = $instance['jarvis_font_size'];
        $jarvis_container_font_size = $instance['jarvis_container_font_size'];
        $jarvis_num_recent_products = $instance['jarvis_num_recent_products'];

        echo do_shortcode("[jarvis-recently-viewed-product-widget title='" . $title . "' 
                                   font_size='" . $font_size . "'
                                   jarvis_container_font_size='" . $jarvis_container_font_size . "'
                                   jarvis_num_recent_products='" . $jarvis_num_recent_products . "' ]");
    }

    /**
     * update function.
     *
     * @see WP_Widget->update
     * @access public
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    function update($new_instance, $old_instance)
    {
        global $woocommerce;

        $instance['jarvis_title'] = wp_strip_all_tags(stripslashes($new_instance['jarvis_title']));
        $instance['jarvis_num_recent_products'] = wp_strip_all_tags(stripslashes($new_instance['jarvis_num_recent_products']));
        $instance['jarvis_font_size'] = stripslashes($new_instance['jarvis_font_size']);
        $instance['jarvis_container_font_size'] = stripslashes($new_instance['jarvis_container_font_size']);

        return $instance;
    }

    /**
     * form function.
     *
     * @see WP_Widget->form
     * @access public
     * @param array $instance
     * @return void
     */
    function form($instance)
    {
        global $woocommerce;

        if (!isset($instance['jarvis_title']))
            $instance['jarvis_title'] = '';

        if (!isset($instance['product_number']))
            $instance['product_number'] = '';

        if (!isset($instance['jarvis_font_size']))
            $instance['jarvis_font_size'] = '16';

        if (!isset($instance['jarvis_container_font_size']))
            $instance['jarvis_container_font_size'] = '16';

        if (!isset($instance['jarvis_num_recent_products']))
            $instance['jarvis_num_recent_products'] = '10';

        ?>

        <div class="jarvis-widget">

            <p>
                <label for="<?php echo esc_attr($this->get_field_id('jarvis_title')); ?>"><?php esc_html_e('Title:', 'jarvis') ?></label>
                <input type="text" class="widefat"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_title')); ?>"
                       value="<?php if (isset($instance['jarvis_title'])) echo esc_attr($instance['jarvis_title']); ?>"/>
                <small><?php esc_html_e("Title of the widget.", "jarvis"); ?></small>
            </p>

            <p>
                <label for="font_size"><?php esc_html_e('Title Font Size in Px', 'jarvis') ?></label>
                <input type="text" class="small-number widefat"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_font_size')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_font_size')); ?>"
                       value="<?php if (isset($instance['jarvis_font_size'])) echo esc_attr($instance['jarvis_font_size']); ?>"/>


            </p>
            <p>
                <label for="font_size"><?php esc_html_e('Content Font Size in Px', 'jarvis') ?></label>
                <input type="text" class="small-number widefat"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_container_font_size')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_container_font_size')); ?>"
                       value="<?php if (isset($instance['jarvis_container_font_size'])) echo esc_attr($instance['jarvis_container_font_size']); ?>"/>
            </p>
            <p>
                <label for="font_size"><?php esc_html_e('Number of recent products to display', 'jarvis') ?></label>
                <input type="number" class="small-number widefat"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_num_recent_products')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_num_recent_products')); ?>"
                       value="<?php if (isset($instance['jarvis_num_recent_products'])) echo esc_attr($instance['jarvis_num_recent_products']); ?>"/>
            </p>


        </div>

        <?php
    }

}


?>