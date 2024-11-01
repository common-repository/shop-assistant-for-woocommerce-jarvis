<?php


if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_Widget_jarvis extends WP_Widget
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
        $this->woo_widget_cssclass = 'woocommerce widget_jarvis';
        $this->woo_widget_description = __('JARVIS natural search builder form widget', 'jarvis');
        $this->woo_widget_idbase = 'jarvis';
        $this->woo_widget_name = __('JARVIS', 'jarvis');

        /* Widget settings. */
        $widget_ops = array('classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description);

        /* Create the widget. */
        parent::__construct('jarvis_widget', $this->woo_widget_name, $widget_ops);
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
            'jarvis_color' => '#8e9396',
            'jarvis_theme' => 'none',
            'jarvis_font_size' => '26',
        );
        $instance = wp_parse_args($instance, $defaults);

        $title = apply_filters('widget_title', $instance['jarvis_title'], $instance, $this->id_base);
        $font_size = $instance['jarvis_font_size'];
        $secondary_font_size = $instance['jarvis_secondary_font_size'];

        echo do_shortcode("[qc_jarvis_widget title='" . $title . "' 
                                   font_size='" . $font_size . "'
                                   jarvis_secondary_font_size='" . $secondary_font_size . "']");
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

        $instance['jarvis_title']               = wp_strip_all_tags(stripslashes($new_instance['jarvis_title']));
        $instance['jarvis_font_size']           = stripslashes($new_instance['jarvis_font_size']);
        $instance['jarvis_secondary_font_size'] = stripslashes($new_instance['jarvis_secondary_font_size']);

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


        if (!isset($instance['jarvis_theme']))
            $instance['jarvis_theme'] = 'light';

        if (!isset($instance['jarvis_font_size']))
            $instance['jarvis_font_size'] = '26';

        if (!isset($instance['jarvis_secondary_font_size']))
            $instance['jarvis_secondary_font_size'] = '16';

        ?>

        <div class="jarvis-widget">

            <p>
                <label><?php esc_html_e("Search Phrase:", "jarvis"); ?></label>
            <div class="widget-no-phrase">
                <?php esc_html_e("Set search sentence from WooCommerce -> JARVIS-Woo", "jarvis"); ?>
            </div>

            </p>

            <p>
                <label for="<?php echo esc_attr($this->get_field_id('jarvis_title')); ?>"><?php esc_html_e('Title:', 'jarvis') ?></label>
                <input type="text" class="widefat"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_title')); ?>"
                       value="<?php if (isset($instance['jarvis_title'])) echo esc_attr($instance['jarvis_title']); ?>"/>
                <small><?php esc_html_e("This is optional and appears above the widget.", "jarvis"); ?></small>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('jarvis_font_size')); ?>"><?php esc_html_e('Font Size:', 'jarvis') ?></label>
                <input type="text" class="small-number"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_font_size')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_font_size')); ?>"
                       value="<?php if (isset($instance['jarvis_font_size'])) echo esc_attr($instance['jarvis_font_size']); ?>"/>
                px
                <small><?php esc_html_e("search phrase font size.", "jarvis"); ?></small>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('jarvis_secondary_font_size')); ?>"><?php esc_html_e('Font Size:', 'jarvis') ?></label>
                <input type="text" class="small-number"
                       id="<?php echo esc_attr($this->get_field_id('jarvis_secondary_font_size')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('jarvis_secondary_font_size')); ?>"
                       value="<?php if (isset($instance['jarvis_secondary_font_size'])) echo esc_attr($instance['jarvis_secondary_font_size']); ?>"/>
                px
                <small><?php esc_html_e("Widget content font size.", "jarvis"); ?></small>
            </p>


        </div>

        <?php
    }


}


