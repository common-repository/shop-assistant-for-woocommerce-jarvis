<?php
/**
 * Plugin Name: WooCommerce Shop Assistant JARVIS
 * Plugin URI: https://woowbot.pro/
 * Description: Let your store help your customers like a real person with your own completely customizable search.
 * Version: 2.9.2
 * @package   JARVIS
 * @author    QuantumCloud
 * @category  WooCommerce
 * Author: QunatumCloud
 * Author URI: https://woowbot.pro/
 * Requires at least: 4.6
 * Tested up to: 6.6.2
 * Text Domain: jarvis
 * Domain Path: /lang/
 * License: GPL2
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly


if ( ! defined( 'QC_JARVIS_VERSION' ) ) {
    define('QC_JARVIS_VERSION', '2.8.2');
}
if ( ! defined( 'QC_JARVIS_REQUIRED_WOOCOMMERCE_VERSION' ) ) {
    define('QC_JARVIS_REQUIRED_WOOCOMMERCE_VERSION', 2.2);
}
if ( ! defined( 'QC_JARVIS_PLUGIN_DIR_PATH' ) ) {
    define('QC_JARVIS_PLUGIN_DIR_PATH', basename(plugin_dir_path(__FILE__)));
}
if ( ! defined( 'QC_JARVIS_PLUGIN_URL' ) ) {
    define('QC_JARVIS_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if ( ! defined( 'QC_JARVIS_IMG_URL' ) ) {
    define('QC_JARVIS_IMG_URL', QC_JARVIS_PLUGIN_URL . "images/");
}
if ( ! defined( 'QC_JARVIS_IMG_ABSOLUTE_PATH' ) ) {
    define('QC_JARVIS_IMG_ABSOLUTE_PATH', plugin_dir_path(__FILE__) . "images");
}

/**
 * Do not forget about translating your plugin
 */
if ( ! function_exists( 'qcld_jarvis_result_languages' ) ) {
  function qcld_jarvis_result_languages(){
    load_plugin_textdomain( 'jarvis', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
  }
}
add_action('init', 'qcld_jarvis_result_languages');

require_once("functions.php");
require_once("class-plugin-deactivate-feedback.php");
require_once('class-qc-free-plugin-upgrade-notice.php');

require_once( 'qc-support-promo-page/class-qc-support-promo-page.php');

/**
 * Main Class.
 */
class WC_Jarvis
{

    private $id = 'jarvis';

    private static $instance;

    /**
     *  Get Instance creates a singleton class that's cached to stop duplicate instances
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     *  Construct empty on purpose
     */

    private function __construct(){

    }

    /**
     *  Init behaves like, and replaces, construct
     */

    public function init(){

        // Check if WooCommerce is active, and is required WooCommerce version.
        if (!class_exists('WooCommerce') || version_compare(get_option('woocommerce_db_version'), QC_JARVIS_REQUIRED_WOOCOMMERCE_VERSION, '<')) {
            add_action('admin_notices', array($this, 'woocommerce_inactive_notice'));
            return;
        }

        $this->general_includes();

        add_action('admin_menu', array($this, 'admin_menu'));

        add_action( 'admin_init' , array($this, 'qc_jarvis_plugin_submenus')  );

        add_action('widgets_init', array($this, 'register_widgets'));

        if ( isset($_GET["page"]) && ($_GET["page"] == "jarvis")) {

            add_action('woocommerce_init', array($this, 'backend_includes'));

            add_action('admin_init', array($this, 'jarvis_save_options'));
        }

        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        if (!is_admin()) {

            add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        }

        add_shortcode('qc_jarvis', array($this, 'jarvis_frontend_shortcode'));
        add_shortcode('qc_jarvis_widget', array($this, 'wsaj_widget_shortcode'));

        add_filter('pre_get_posts', array($this, 'jarvis_search_query'));

        add_filter('loop_shop_post_in', array($this, 'jarvis_price_filter'));
    }


    /**
     * Add a submenu item to the WooCommerce menu
     */
    public function admin_menu()
    {

        /*

        add_submenu_page('woocommerce',
            __('JARVIS-Lite', 'jarvis'),
            __('JARVIS-Lite', 'jarvis'),
            'manage_woocommerce',
            $this->id,
            array($this, 'admin_page'));

        */

        add_menu_page(
            esc_html__('JARVIS-Lite', 'jarvis'),
            esc_html__('JARVIS-Lite', 'jarvis'),
            'manage_options',
            'jarvis',
            array($this, 'admin_page'),
            'dashicons-cart',
            20
        );


        add_submenu_page(
                'jarvis',
                esc_html__('JARVIS-Lite', 'jarvis'),
                esc_html__('JARVIS-Lite', 'jarvis'),
                'manage_woocommerce',
                $this->id,
                array($this, 'admin_page')
            );

    }

    

    public function qc_jarvis_plugin_submenus( $menu_ord ){
        global $submenu;

        $arr = array();
        // echo "<pre>";
        // var_dump( $submenu['jarvis'] );
        // wp_die();

        $arr[] = isset($submenu['jarvis'][0])   ? $submenu['jarvis'][0] : '';
        $arr[] = isset($submenu['jarvis'][301]) ? $submenu['jarvis'][301] : '';
        $arr[] = isset($submenu['jarvis'][300]) ? $submenu['jarvis'][300] : '';

        $submenu['jarvis'] = $arr;
        
        return $menu_ord;
    }

    /**
     * Include backend required files.
     *
     * @return void
     */
    public function backend_includes()
    {
        include_once('classes/jarvis-class-language-builder.php');


    }

    /**
     * Include general required files.
     *
     * @return void
     */
    public function general_includes()
    {

        // Shortcode Class
        include_once('classes/shortcodes/class-shortcode-qc-jarvis.php');
        include_once('classes/shortcodes/class-widget-shortcode.php');

        //Widget Class
        include_once('classes/widgets/class-wc-widget-jarvis.php');
        include_once('classes/widgets/class-wc-widget-jarvis-recent-viewed-products.php');
        include_once('classes/widgets/class-wc-widget-jarvis-recent-sold-product.php');


    }


    /**
     * Include admin scripts
     */
    public function admin_scripts($hook)
    {
        global $woocommerce, $wp_scripts;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        if ((( isset($_GET["page"])) && ($_GET["page"] == "jarvis")) || ($hook == "widgets.php")) {

            wp_enqueue_script('jquery');

            wp_enqueue_style('woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css',  '', QC_JARVIS_VERSION, 'screen');


            wp_register_style('jarvis-backend-style', QC_JARVIS_PLUGIN_URL . '/css/admin-style.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('jarvis-backend-style');

            $add_inline_styles = '.qcld_jarvis_wrap { background: #fff; box-shadow: 0px 0px 25px -5px rgba(0,0,0,0.45); padding: 20px; border-radius: 10px; min-height: 450px }
                                    .qcld_jarvis_wrap h2 { padding-left: 0; font-weight: bold;color:#333 !important;}
                                    .qcld_jarvis_wrap p { padding-left: 0; font-size:16px; font-weight: 400; }
                                    .qcld_jarvis_wrap p a{ text-decoration: none }  .jarvis_sentence_builder .span4 .add{
                                    margin-left: 15px !important;
                                    margin-top: 15px !important;
                                }';

            wp_add_inline_style( 'jarvis-backend-style', $add_inline_styles );


            wp_register_style('font-awesome', QC_JARVIS_PLUGIN_URL . '/css/font-awesome.min.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('font-awesome');

            wp_register_style('font-awesome-animation', QC_JARVIS_PLUGIN_URL . '/css/font-awesome-animation.min.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('font-awesome-animation');

            wp_register_style('sweetalert2-css', QC_JARVIS_PLUGIN_URL . '/css/sweetalert2.min.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('sweetalert2-css');

            wp_register_style('select2-style', QC_JARVIS_PLUGIN_URL . '/css/select2.min.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('select2-style');

    

            wp_register_style('tabs-jarvis', QC_JARVIS_PLUGIN_URL . '/css/tabs-jarvis.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('tabs-jarvis');


            wp_register_script('cbpFWTabs', QC_JARVIS_PLUGIN_URL . '/js/cbpFWTabs.js', array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('cbpFWTabs');

            wp_register_script('sweetatert2-js', QC_JARVIS_PLUGIN_URL . '/js/sweetalert2.min.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('sweetatert2-js');

            wp_register_script('modernizr-custom', QC_JARVIS_PLUGIN_URL . '/js/modernizr.custom.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('modernizr-custom');

            wp_register_script('jquery-ui', QC_JARVIS_PLUGIN_URL . '/js/jquery-ui.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('jquery-ui');

            wp_register_script('jquery-grideditor', QC_JARVIS_PLUGIN_URL . '/js/jquery.grideditor.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('jquery-grideditor');

            wp_register_script('select2', QC_JARVIS_PLUGIN_URL . '/js/select2.full.min.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('select2');



            wp_register_script('bootstrap-js', QC_JARVIS_PLUGIN_URL . '/js/bootstrap.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('bootstrap-js');

            wp_register_style('qc-layout', QC_JARVIS_PLUGIN_URL . '/css/qc-layout.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('qc-layout');

            wp_register_style('bootstrap-css', QC_JARVIS_PLUGIN_URL . '/css/bootstrap.min.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('bootstrap-css');

            wp_register_script('jarvis-repeatable', QC_JARVIS_PLUGIN_URL . '/js/jquery.repeatable.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('jarvis-repeatable');

            wp_register_script('jarvis-admin', QC_JARVIS_PLUGIN_URL . '/js/jarvis-admin.js',  array('jquery'), QC_JARVIS_VERSION, true);
            wp_enqueue_script('jarvis-admin');

            wp_register_script('jquery-tiptip', $woocommerce->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array('jquery'), $woocommerce->version, true);
            wp_enqueue_script('jquery-tiptip');

            wp_localize_script('jarvis-admin', 'ajax_object',
                array(
                    'ajax_url'      => admin_url('admin-ajax.php'),
                    'ajax_nonce'    => wp_create_nonce( 'jarvis' ),
                )
            );

            $jarvis_params = array(
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'default_button_text' => esc_html("Find Them!", "jarvis"),

            );

            wp_localize_script('shop-jarvis', 'qc_jarvis_params', $jarvis_params);

        }

    }


    public function frontend_scripts()
    {
        global $woocommerce, $wp_scripts;

        if (get_option("permalink_structure") == "") {
            $shop_url = get_post_type_archive_link('product');
        } else {
            $shop_url = get_permalink(get_option('woocommerce_shop_page_id'));
        }

        $jarvis_params = array(
            'shop_url' => $shop_url,
            'jarvis_pop_up_form_effect' => get_option("jarvis_form_animation"),
            'position_x' => get_option('position_x'),
            'position_y' => get_option('position_y'),
            'disable_cart_item' => get_option('disable_cart_item'),
            'disable_jarvis_icon_animation' => get_option('disable_jarvis_icon_animation'),
            'jarvis_notifications' => wp_json_encode(array(
                get_option('message_one'),
                get_option('message_two'),
                get_option('message_three'),
                get_option('message_four'),
                get_option('message_five'),
                get_option('message_six'),
                get_option('message_seven'),
                get_option('message_eight')
            ))
        );
        wp_register_script('jquery-ui', QC_JARVIS_PLUGIN_URL . '/js/jquery-ui.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('jquery-ui');

        wp_register_script('classie-js', QC_JARVIS_PLUGIN_URL . '/js/recomended-prod/classie.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('classie-js');


        wp_register_script('dynamics-js', QC_JARVIS_PLUGIN_URL . '/js/recomended-prod/dynamics.min.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('dynamics-js');


        wp_register_script('slimscroll-js', QC_JARVIS_PLUGIN_URL . '/js/jquery.slimscroll.min.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('slimscroll-js');


        wp_register_script('animatedModal.min', QC_JARVIS_PLUGIN_URL . '/js/animatedModal.min.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('animatedModal.min');


        wp_register_script('jquery-cookie', QC_JARVIS_PLUGIN_URL . '/js/jquery.cookie.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('jquery-cookie');

        wp_register_script('jarvis-typed', QC_JARVIS_PLUGIN_URL . '/js/jarvis-typed.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('jarvis-typed');

        wp_register_script('jarvis-frontend', QC_JARVIS_PLUGIN_URL . '/js/jarvis-frontend.js',  array('jquery', 'jquery-cookie'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('jarvis-frontend');

        wp_register_script('jarvis-sell-notification', QC_JARVIS_PLUGIN_URL . '/js/jarvis-sell-notification.js',  array('jquery'), QC_JARVIS_VERSION, true);
        wp_enqueue_script('jarvis-sell-notification');


        // wp_localize_script('jarvis-frontend', 'qc_jarvis_params', $jarvis_params);

        wp_localize_script('jarvis-frontend', 'ajax_object',
            array(
                'ajax_url'      => admin_url('admin-ajax.php'), 
                'image_path'    => QC_JARVIS_IMG_URL,
                'ajax_nonce'    => wp_create_nonce( 'jarvis' ),
            )
        );


        wp_register_style('qc-layout', QC_JARVIS_PLUGIN_URL . '/css/qc-layout.css',  '', QC_JARVIS_VERSION, 'screen');
        wp_enqueue_style('qc-layout');


        wp_register_style('animate-css', QC_JARVIS_PLUGIN_URL . '/css/animate.css',  '', QC_JARVIS_VERSION, 'screen');
        wp_enqueue_style('animate-css');


        wp_register_style('animatemodal-min-css', QC_JARVIS_PLUGIN_URL . '/css/animatemodal.min.css',  '', QC_JARVIS_VERSION, 'screen');
        wp_enqueue_style('animatemodal-min-css');


        wp_register_style('jarvis-frontend', QC_JARVIS_PLUGIN_URL . '/css/frontend-style.css',  '', QC_JARVIS_VERSION, 'screen');
        wp_enqueue_style('jarvis-frontend');

        $add_inline_styles = get_option('custom_global_css');

        wp_add_inline_style( 'jarvis-frontend', $add_inline_styles );

        wp_register_style('jarvis-sell-notification-css', QC_JARVIS_PLUGIN_URL . '/css/jarvis-sell-notification.css',  '', QC_JARVIS_VERSION, 'screen');

        wp_enqueue_style('jarvis-sell-notification-css');

        if (get_option('jarvis_mode') == 'full') {
            //Tooltip for previous
            wp_register_style('tooltip-classic-css', QC_JARVIS_PLUGIN_URL . '/css/tooltip-classic.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('tooltip-classic-css');
            $theme_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', get_option('jarvis_theme'));
            if ($theme_name == 'theme-one') {
                wp_register_style('jarvis-template', QC_JARVIS_PLUGIN_URL . '/css/jarvis-template-01.css',  '', QC_JARVIS_VERSION, 'screen');
                wp_enqueue_style('jarvis-template');

            } else if ($theme_name == 'theme-two') {
                wp_register_style('jarvis-template', QC_JARVIS_PLUGIN_URL . '/css/jarvis-template-02.css',  '', QC_JARVIS_VERSION, 'screen');
                wp_enqueue_style('jarvis-template');

            } else if ($theme_name == 'theme-three') {
                wp_register_style('jarvis-template', QC_JARVIS_PLUGIN_URL . '/css/jarvis-template-03.css',  '', QC_JARVIS_VERSION, 'screen');
                wp_enqueue_style('jarvis-template');
            }


        } else if (get_option('jarvis_mode') == 'quickball') {
            //Tooltip for pinball
            wp_register_style('tooltip-pinball-css', QC_JARVIS_PLUGIN_URL . '/css/tooltip-pinball.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('tooltip-pinball-css');
            wp_register_style('jarvis-template', QC_JARVIS_PLUGIN_URL . '/css/pinball.css',  '', QC_JARVIS_VERSION, 'screen');
            wp_enqueue_style('jarvis-template');
        }
        wp_localize_script('jarvis-frontend', 'qc_jarvis_params', $jarvis_params);

    }

    public function register_widgets()
    {

        register_widget('WC_Widget_jarvis');
        register_widget('WC_Widget_Jarvis_Recent_Viewed_Products');
        register_widget('WC_Widget_Jarvis_Recent_Sold_Product');

    }


    /**
     * Render the admin page
     */
    public function admin_page()
    {

        global $woocommerce;

        $action = 'admin.php?page=jarvis'; ?>
        <br>
        <form action="<?php echo esc_attr($action); ?>" method="POST" enctype="multipart/form-data">
            <div class="container form-container">
                <h2><?php  esc_html_e( 'JARVIS Control Panel', 'jarvis' ); ?></h2>
                <div class="qc_get_pro">
                    <h2><a href="<?php echo esc_url( 'https://www.quantumcloud.com/products/woocommerce-shop-assistant-jarvis/', 'jarvis' ); ?>"><?php  esc_html_e( 'Get The Professional Version', 'jarvis' ); ?></a></h2>
                    <p><a href="<?php echo esc_url( 'https://www.quantumcloud.com/', 'jarvis' ); ?>"> <?php  esc_html_e( 'JARVIS is a project by Web Design Company QuantumCloud', 'jarvis' ); ?></a></p>
                </div>
                <section class="jarvis-tab-container-inner">
                    <div class="tabs-jarvis tabs-jarvis-style-flip">
                        <nav>
                            <ul>
                                <li><a href="#section-flip-1"><i class="fa fa-toggle-on"></i><span> <strong><?php  esc_html_e( 'GENERAL SETTINGS', 'jarvis' ); ?></strong></span></a>
                                </li>
                                <li><a href="#section-flip-2"><i class="fa fa-search"></i><span> <?php  esc_html_e( 'SEARCH SETTINGS', 'jarvis' ); ?></span></a></li>
                                <li><a href="#section-flip-3"><i class="fa fa-gear faa-spin animated"></i> <span><?php  esc_html_e( 'ICONS & THEME', 'jarvis' ); ?></span></a>
                                </li>
                                <li><a href="#section-flip-4"><i class="fa fa-bell"></i><span> <?php  esc_html_e( 'MESSAGE CENTER', 'jarvis' ); ?></span></a>
                                </li>
                                <li><a href="#section-flip-5"><i class="fa fa-shopping-cart"></i><span> <?php  esc_html_e( 'ORDER NOTIFICATION', 'jarvis' ); ?></span></a>
                                </li>
                                <li><a href="#section-flip-7"><i class="fa fa-language"></i><span> <?php  esc_html_e( 'LANGUAGE CENTER', 'jarvis' ); ?></span></a></li>
                                <li><a href="#section-flip-8"><i class="fa fa-code"></i><span> <?php  esc_html_e( 'Custom CSS', 'jarvis' ); ?></span></a>
                                </li>
                                <li><a href="#section-flip-9"><i class="fa fa-line-chart"></i><span> <?php  esc_html_e( 'Conversion Report', 'jarvis' ); ?></span></a>
                                </li>
                            </ul>
                        </nav>
                        <div class="content-wrap">
                            <section id="section-flip-1">
                                <table class="table table-bordered striped">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font"> <?php  esc_html_e( 'JARVIS Mode', 'jarvis' ); ?></p>
                                            <select class="jarvis_select_two qc-opt-dcs-font jarvis_mode"
                                                    name="jarvis_mode">
                                                <option disabled="disabled" value="vertical"> <?php  esc_html_e( 'Vertical Balls (Pro Only)', 'jarvis' ); ?></option>
                                                <option disabled="disabled" value="slide"> <?php  esc_html_e( 'Slide Bar (Pro Only)', 'jarvis' ); ?></option>
                                                <option value="<?php echo esc_attr(get_option('jarvis_mode')); ?>"
                                                        selected="selected"><?php if (get_option('jarvis_mode') == 'quickball') {
                                                        esc_html_e("Quick Ball", 'jarvis');
                                                    } else if (get_option('jarvis_mode') == 'full') {
                                                        esc_html_e("Full Page View (Legacy)", 'jarvis');
                                                    } ?></option>
                                                <option value="quickball"> <?php  esc_html_e( 'Quick Ball', 'jarvis' ); ?></option>
                                                <!-- <option value="full"> <?php  esc_html_e( 'Full Page View (Legacy)', 'jarvis' ); ?></option> -->

                                            </select>

                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">  <?php  esc_html_e( 'Disable JARVIS', 'jarvis' ); ?></p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="disable_jarvis" type="checkbox" name="disable_jarvis" value="1" <?php echo esc_attr(get_option('disable_jarvis') == '' ? 'checked' : ''); ?>>
                                                <label for="disable_jarvis"> <?php  esc_html_e( 'Disable JARVIS to load', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font"> <?php  esc_html_e( 'This animation will appear when you click JARVIS icon on bottom right and close popup form ', 'jarvis' ); ?></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <select class="jarvis_select_two" name="jarvis_form_animation">
                                                        <option
                                                                value="<?php echo esc_attr(get_option('jarvis_form_animation')); ?>"
                                                                selected="selected"><?php echo esc_attr(get_option('jarvis_form_animation')); ?></option>
                                                        <optgroup label="Attention Seekers">
                                                            <option value="bounce"> <?php  esc_html_e( 'bounce', 'jarvis' ); ?></option>
                                                            <option value="flash"> <?php  esc_html_e( 'flash', 'jarvis' ); ?></option>
                                                            <option value="pulse"> <?php  esc_html_e( 'pulse', 'jarvis' ); ?></option>
                                                            <option value="rubberBand"> <?php  esc_html_e( 'rubberBand', 'jarvis' ); ?></option>
                                                            <option value="shake"> <?php  esc_html_e( 'shake', 'jarvis' ); ?></option>
                                                            <option value="swing"> <?php  esc_html_e( 'swing', 'jarvis' ); ?></option>
                                                            <option value="tada"> <?php  esc_html_e( 'tada', 'jarvis' ); ?></option>
                                                            <option value="wobble"> <?php  esc_html_e( 'wobble', 'jarvis' ); ?></option>
                                                            <option value="jello"> <?php  esc_html_e( 'jello', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Bouncing Entrances">
                                                            <option value="bounceIn"> <?php  esc_html_e( 'bounceIn', 'jarvis' ); ?></option>
                                                            <option value="bounceInDown"> <?php  esc_html_e( 'bounceInDown', 'jarvis' ); ?></option>
                                                            <option value="bounceInLeft"> <?php  esc_html_e( 'bounceInLeft', 'jarvis' ); ?></option>
                                                            <option value="bounceInRight"> <?php  esc_html_e( 'bounceInRight', 'jarvis' ); ?></option>
                                                            <option value="bounceInUp"> <?php  esc_html_e( 'bounceInUp', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Bouncing Exits">
                                                            <option value="bounceOut"> <?php  esc_html_e( 'bounceOut', 'jarvis' ); ?></option>
                                                            <option value="bounceOutDown"> <?php  esc_html_e( 'bounceOutDown', 'jarvis' ); ?></option>
                                                            <option value="bounceOutLeft"> <?php  esc_html_e( 'bounceOutLeft', 'jarvis' ); ?></option>
                                                            <option value="bounceOutRight"> <?php  esc_html_e( 'bounceOutRight', 'jarvis' ); ?></option>
                                                            <option value="bounceOutUp"> <?php  esc_html_e( 'bounceOutUp', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Fading Entrances">
                                                            <option value="fadeIn"> <?php  esc_html_e( 'fadeIn', 'jarvis' ); ?></option>
                                                            <option value="fadeInDown"> <?php  esc_html_e( 'fadeInDown', 'jarvis' ); ?></option>
                                                            <option value="fadeInDownBig"> <?php  esc_html_e( 'fadeInDownBig', 'jarvis' ); ?></option>
                                                            <option value="fadeInLeft"> <?php  esc_html_e( 'fadeInLeft', 'jarvis' ); ?></option>
                                                            <option value="fadeInLeftBig"> <?php  esc_html_e( 'fadeInLeftBig', 'jarvis' ); ?></option>
                                                            <option value="fadeInRight"> <?php  esc_html_e( 'fadeInRight', 'jarvis' ); ?></option>
                                                            <option value="fadeInRightBig"> <?php  esc_html_e( 'fadeInRightBig', 'jarvis' ); ?></option>
                                                            <option value="fadeInUp"> <?php  esc_html_e( 'fadeInUp', 'jarvis' ); ?></option>
                                                            <option value="fadeInUpBig"> <?php  esc_html_e( 'fadeInUpBig', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Fading Exits">
                                                            <option value="fadeOut"> <?php  esc_html_e( 'fadeOut', 'jarvis' ); ?></option>
                                                            <option value="fadeOutDown"> <?php  esc_html_e( 'fadeOutDown', 'jarvis' ); ?></option>
                                                            <option value="fadeOutDownBig"> <?php  esc_html_e( 'fadeOutDownBig', 'jarvis' ); ?></option>
                                                            <option value="fadeOutLeft"> <?php  esc_html_e( 'fadeOutLeft', 'jarvis' ); ?></option>
                                                            <option value="fadeOutLeftBig"> <?php  esc_html_e( 'fadeOutLeftBig', 'jarvis' ); ?></option>
                                                            <option value="fadeOutRight"> <?php  esc_html_e( 'fadeOutRight', 'jarvis' ); ?></option>
                                                            <option value="fadeOutRightBig"> <?php  esc_html_e( 'fadeOutRightBig', 'jarvis' ); ?></option>
                                                            <option value="fadeOutUp"> <?php  esc_html_e( 'fadeOutUp', 'jarvis' ); ?></option>
                                                            <option value="fadeOutUpBig"> <?php  esc_html_e( 'fadeOutUpBig', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Flippers">
                                                            <option value="flip"> <?php  esc_html_e( 'flip', 'jarvis' ); ?></option>
                                                            <option value="flipInX"> <?php  esc_html_e( 'flipInX', 'jarvis' ); ?></option>
                                                            <option value="flipInY"> <?php  esc_html_e( 'flipInY', 'jarvis' ); ?></option>
                                                            <option value="flipOutX"> <?php  esc_html_e( 'flipOutX', 'jarvis' ); ?></option>
                                                            <option value="flipOutY"> <?php  esc_html_e( 'flipOutY', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Lightspeed">
                                                            <option value="lightSpeedIn"> <?php  esc_html_e( 'lightSpeedIn', 'jarvis' ); ?></option>
                                                            <option value="lightSpeedOut"> <?php  esc_html_e( 'lightSpeedOut', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Rotating Entrances">
                                                            <option value="rotateIn"> <?php  esc_html_e( 'rotateIn', 'jarvis' ); ?></option>
                                                            <option value="rotateInDownLeft"> <?php  esc_html_e( 'rotateInDownLeft', 'jarvis' ); ?></option>
                                                            <option value="rotateInDownRight"> <?php  esc_html_e( 'rotateInDownRight', 'jarvis' ); ?></option>
                                                            <option value="rotateInUpLeft"> <?php  esc_html_e( 'rotateInUpLeft', 'jarvis' ); ?></option>
                                                            <option value="rotateInUpRight"> <?php  esc_html_e( 'rotateInUpRight', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Rotating Exits">
                                                            <option value="rotateOut"> <?php  esc_html_e( 'rotateOut', 'jarvis' ); ?></option>
                                                            <option value="rotateOutDownLeft"> <?php  esc_html_e( 'rotateOutDownLeft', 'jarvis' ); ?>
                                                            </option>
                                                            <option value="rotateOutDownRight"> <?php  esc_html_e( 'rotateOutDownRight', 'jarvis' ); ?>
                                                            </option>
                                                            <option value="rotateOutUpLeft"> <?php  esc_html_e( 'rotateOutUpLeft', 'jarvis' ); ?></option>
                                                            <option value="rotateOutUpRight"> <?php  esc_html_e( 'rotateOutUpRight', 'jarvis' ); ?>
                                                            </option>
                                                        </optgroup>

                                                        <optgroup label="Sliding Entrances">
                                                            <option value="slideInUp"> <?php  esc_html_e( 'slideInUp', 'jarvis' ); ?></option>
                                                            <option value="slideInDown"> <?php  esc_html_e( 'slideInDown', 'jarvis' ); ?></option>
                                                            <option value="slideInLeft"> <?php  esc_html_e( 'slideInLeft', 'jarvis' ); ?></option>
                                                            <option value="slideInRight"> <?php  esc_html_e( 'slideInRight', 'jarvis' ); ?></option>

                                                        </optgroup>
                                                        <optgroup label="Sliding Exits">
                                                            <option value="slideOutUp"> <?php  esc_html_e( 'slideOutUp', 'jarvis' ); ?></option>
                                                            <option value="slideOutDown"> <?php  esc_html_e( 'slideOutDown', 'jarvis' ); ?></option>
                                                            <option value="slideOutLeft"> <?php  esc_html_e( 'slideOutLeft', 'jarvis' ); ?></option>
                                                            <option value="slideOutRight"> <?php  esc_html_e( 'slideOutRight', 'jarvis' ); ?></option>

                                                        </optgroup>

                                                        <optgroup label="Zoom Entrances">
                                                            <option value="zoomIn"> <?php  esc_html_e( 'zoomIn', 'jarvis' ); ?></option>
                                                            <option value="zoomInDown"> <?php  esc_html_e( 'zoomInDown', 'jarvis' ); ?></option>
                                                            <option value="zoomInLeft"> <?php  esc_html_e( 'zoomInLeft', 'jarvis' ); ?></option>
                                                            <option value="zoomInRight"> <?php  esc_html_e( 'zoomInRight', 'jarvis' ); ?></option>
                                                            <option value="zoomInUp"> <?php  esc_html_e( 'zoomInUp', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Zoom Exits">
                                                            <option value="zoomOut"> <?php  esc_html_e( 'zoomOut', 'jarvis' ); ?></option>
                                                            <option value="zoomOutDown"> <?php  esc_html_e( 'zoomOutDown', 'jarvis' ); ?></option>
                                                            <option value="zoomOutLeft"> <?php  esc_html_e( 'zoomOutLeft', 'jarvis' ); ?></option>
                                                            <option value="zoomOutRight"> <?php  esc_html_e( 'zoomOutRight', 'jarvis' ); ?></option>
                                                            <option value="zoomOutUp"> <?php  esc_html_e( 'zoomOutUp', 'jarvis' ); ?></option>
                                                        </optgroup>

                                                        <optgroup label="Specials">
                                                            <option value="hinge"> <?php  esc_html_e( 'hinge', 'jarvis' ); ?></option>
                                                            <option value="rollIn"> <?php  esc_html_e( 'rollIn', 'jarvis' ); ?></option>
                                                            <option value="rollOut"> <?php  esc_html_e( 'rollOut', 'jarvis' ); ?></option>
                                                        </optgroup>
                                                    </select>

                                                </fieldset>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font"> <?php  esc_html_e( 'Display JARVIS search', 'jarvis' ); ?></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <input id="search" type="checkbox" name="jarvis_search"
                                                           value="jarvis_search" <?php echo esc_attr(get_option('jarvis_search') == 'jarvis_search' ? 'checked' : ''); ?>>
                                                    <label for="search"> <?php  esc_html_e( 'Enable JARVIS search', 'jarvis' ); ?></label>


                                                </fieldset>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font"><?php esc_html_e( 'Display Cart Items On Pop Up Window', 'jarvis' ); ?></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <input id="ham" type="checkbox" name="cart_products"
                                                           value="cart_products" <?php echo(get_option('cart_products') == 'cart_products' ? 'checked' : ''); ?>>
                                                    <label for="ham"> <?php esc_html_e( 'Enable Cart Items', 'jarvis' ); ?></label>


                                                </fieldset>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Display Recommended Products On Pop Up Window', 'jarvis' ); ?> <span class="badge"> <?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input disabled="disabled" id="pepperoni" type="checkbox"
                                                       name="recommended_products"
                                                       value="">
                                                <label for="pepperoni"> <?php esc_html_e( 'Enable Recommended Products', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                 <?php esc_html_e( 'Display Recent Products On Pop Up Window', 'jarvis' ); ?><span class="badge"> <?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input disabled="disabled" id="mushrooms" type="checkbox"
                                                       name="recent_products"
                                                       value="recent_products">
                                                <label for="mushrooms"> <?php esc_html_e( 'Enable Recent Products', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Repeat pop up notification through out users stay on the website', 'jarvis' ); ?>
                                                
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="loop_notification" type="checkbox"
                                                       name="loop_notification"
                                                       value="1" <?php echo esc_attr(get_option('loop_notification') == '1' ? 'checked' : ''); ?>>
                                                <label for="loop_notification"> <?php esc_html_e( 'Enable Notification Loop', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Disable notifications', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="disable_notification" type="checkbox"
                                                       name="disable_notification"
                                                       value="1" <?php echo esc_attr(get_option('disable_notification') == '1' ? 'checked' : ''); ?>>
                                                <label for="disable_notification"> <?php esc_html_e( 'Disable front end notification', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Disable Cart Item Number', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="disable_cart_item" type="checkbox"
                                                       name="disable_cart_item"
                                                       value="1" <?php echo esc_attr(get_option('disable_cart_item') == '1' ? 'checked' : ''); ?>>
                                                <label for="disable_cart_item"><?php esc_html_e( 'Disable Cart Item Number on JARVIS Icon ', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Disable JARVIS Icon Animation', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="disable_jarvis_icon_animation" type="checkbox"
                                                       name="disable_jarvis_icon_animation"
                                                       value="1" <?php echo esc_attr(get_option('disable_jarvis_icon_animation') == '1' ? 'checked' : ''); ?>>
                                                <label for="disable_jarvis_icon_animation"> <?php esc_html_e( 'Disable JARVIS Icon Animation for Quick Ball', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Support Quickball on JARVIS', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input id="support_quickball" type="checkbox"
                                                       name="support_quickball"
                                                       value="support_quickball" <?php echo esc_attr(get_option('support_quickball') == 'support_quickball' ? 'checked' : ''); ?>>
                                                <label for="support_quickball"> <?php esc_html_e( 'Enable SUPPORT quickball on JARVIS', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="support_quickball_email_contianer">
                                        <td>
                                            <div>
                                                <p class="qc-opt-title-font"> <?php esc_html_e( 'Support Email Address', 'jarvis' ); ?></p>
                                                <?php
                                                $url = get_site_url();
                                                $url = wp_parse_url($url);
                                                $domain = $url['host'];
                                                //$support_email = "support@" . $domain;
                                                $support_email = get_option('admin_email');
                                                ?>
                                                <input id="support_quickball_email" class="form-control"
                                                       type="email"
                                                       name="support_quickball_email"
                                                       value="<?php echo esc_attr(get_option('support_quickball_email') != '' ? get_option('support_quickball_email') : esc_attr($support_email)); ?>">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Add A Custom Quickball on JARVIS', 'jarvis' ); ?><span
                                                        class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <input disabled="disabled" id="custom_quickball" type="checkbox"
                                                       name="custom_quickball"
                                                       value="custom_quickball">
                                                <label for="custom_quickball"> <?php esc_html_e( 'Enable custom quickball on JARVIS', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <strong> <?php esc_html_e( 'Override Icon Position', 'jarvis' ); ?></strong>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <?php
                                                $jarvis_position_x = get_option('position_x');
                                                if ((!isset($jarvis_position_x)) || ($jarvis_position_x == "")) {
                                                    $jarvis_position_x = esc_html("0", "jarvis");
                                                }
                                                $jarvis_position_y = get_option('position_y');
                                                if ((!isset($jarvis_position_y)) || ($jarvis_position_y == "")) {
                                                    $jarvis_position_y = esc_html("0", "jarvis");
                                                } ?>
                                                <p><span class="qc-opt-dcs-font"> <?php esc_html_e( 'Offset', 'jarvis' ); ?></span>
                                                    <input type="number" class="qc-opt-dcs-font"
                                                           name="position_x"
                                                           id=""
                                                           value="<?php echo esc_attr($jarvis_position_x); ?>"
                                                           placeholder="From Right In px">
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'px from Right of the Browser Window', 'jarvis' ); ?></span>
                                                </p>


                                                <p><span class="qc-opt-dcs-font"> <?php esc_html_e( 'Offset', 'jarvis' ); ?></span>
                                                    <input type="number" class="qc-opt-dcs-font"
                                                           name="position_y"
                                                           id=""
                                                           value="<?php echo esc_attr($jarvis_position_y); ?>"
                                                           placeholder="From Bottom In Px"> <span
                                                            class="qc-opt-dcs-font"><?php esc_html_e( 'px from Bottom of the Browser Window', 'jarvis' ); ?></span>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font"> <?php esc_html_e( 'Select recommended products from dropdown to show them on front end', 'jarvis' ); ?> <span class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                            <div class="cxsc-settings-blocks">
                                                <?php $params = array('posts_per_page' => 10, 'post_type' => 'product');
                                                $wc_query = new WP_Query($params); ?>
                                                <select name="jarvis-recommended-products[]"
                                                        class="jarvis_select_two"
                                                        multiple="multiple" disabled>
                                                    <?php //$_pf = new WC_Product_Factory(); ?>
                                                    <?php
                                                    if(!empty(maybe_unserialize(get_option('jarvis-recommended-products')))){
                                                    $products = maybe_unserialize(get_option('jarvis-recommended-products')); ?>
                                                    <?php foreach ($products as $id): ?>
                                                        <?php $product = wc_get_product($id); ?>
                                                        <option value="<?php echo esc_attr($product->get_id()); ?>"
                                                                selected="selected"><?php echo esc_attr($product->get_title()); ?></option>
                                                    <?php endforeach;
                                                    }
                                                    ?>
                                                    <?php if ($wc_query->have_posts()) : ?>
                                                        <?php while ($wc_query->have_posts()) :$wc_query->the_post(); ?>
                                                            <option
                                                                    value="<?php the_ID(); ?>" disabled>
                                                                <?php the_title(); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                        <?php wp_reset_postdata(); ?>
                                                    <?php else: ?>
                                                        <li>
                                                            <?php esc_html_e('No Products'); ?>
                                                        </li>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                     <tr>
                                            <td>
                                                <p class="qc-opt-title-font"><strong> <?php esc_html_e( 'JARVIS', 'jarvis' ); ?></strong>  <?php esc_html_e( 'Loading Control Options', 'jarvis' ); ?> <span class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>

                                                <div class="cxsc-settings-blocks">
                                                    <div class="row">
                                                        <div class="col-sm-4 text-right">
                                                            <span class="qc-opt-title-font"><?php esc_html_e( 'Show on Home Page', 'jarvis' ); ?></span>

                                                        </div>
                                                        <div class="col-sm-8">


                                                            <label class="radio-inline">
                                                                <input id="jarvis-show-home-page" type="radio"
                                                                       name="jarvis_show_home_page"
                                                                       value="on" <?php echo esc_attr(get_option('jarvis_show_home_page') == 'on' ? 'checked' : ''); ?> disabled>
                                                                 <?php esc_html_e( 'YES', 'jarvis' ); ?>
                                                            </label>

                                                            <label class="radio-inline">
                                                                <input id="jarvis-show-home-page" type="radio"
                                                                       name="jarvis_show_home_page"
                                                                       value="off" <?php echo esc_attr(get_option('jarvis_show_home_page') == 'off' ? 'checked' : ''); ?> disabled>
                                                                 <?php esc_html_e( 'NO', 'jarvis' ); ?>
                                                            </label>


                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4 text-right">
                                                            <span class="qc-opt-title-font"> <?php esc_html_e( 'Show on Blog Posts', 'jarvis' ); ?></span>

                                                        </div>
                                                        <div class="col-sm-8">

                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-posts" type="radio"
                                                                       name="jarvis_show_posts"
                                                                       value="on" <?php echo esc_attr(get_option('jarvis_show_posts') == 'on' ? 'checked' : ''); ?> disabled>
                                                                 <?php esc_html_e( 'YES', 'jarvis' ); ?>
                                                            </label>

                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-posts" type="radio"
                                                                       name="jarvis_show_posts"
                                                                       value="off" <?php echo esc_attr(get_option('jarvis_show_posts') == 'off' ? 'checked' : ''); ?> disabled>
                                                                 <?php esc_html_e( 'NO', 'jarvis' ); ?>
                                                            </label>


                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 text-right">
                                                            <span class="qc-opt-title-font"> <?php esc_html_e( 'Show on  Pages', 'jarvis' ); ?></span>

                                                        </div>
                                                        <div class="col-md-8">


                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-pages" type="radio"
                                                                       name="jarvis_show_pages"
                                                                       value="on" <?php echo esc_attr(get_option('jarvis_show_pages') == 'on' ? 'checked' : ''); ?> disabled>
                                                                <?php esc_html_e( 'All Pages', 'jarvis' ); ?>
                                                            </label>

                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-pages" type="radio"
                                                                       name="jarvis_show_pages"
                                                                       value="off" <?php echo esc_attr(get_option('jarvis_show_pages') == 'off' ? 'checked' : ''); ?> disabled>
                                                                <?php esc_html_e( 'Selected Pages Only', 'jarvis' ); ?></label>

                                                           
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-4 text-right">
                                                            <span class="qc-opt-title-font"> <?php esc_html_e( 'Show on WooCommerce', 'jarvis' ); ?></span>

                                                        </div>
                                                        <div class="col-sm-8">


                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-woocommerce" type="radio"
                                                                       name="jarvis_show_woocommerce"
                                                                       value="on" <?php echo esc_attr(get_option('jarvis_show_woocommerce') == 'on' ? 'checked' : ''); ?> disabled>
                                                                 <?php esc_html_e( 'YES', 'jarvis' ); ?>
                                                            </label> 

                                                            <label class="radio-inline">
                                                                <input class="jarvis-show-woocommerce" type="radio"
                                                                       name="jarvis_show_woocommerce"
                                                                       value="off" <?php echo esc_attr(get_option('jarvis_show_woocommerce') == 'off' ? 'checked' : ''); ?> disabled>
                                                                  <?php esc_html_e( 'NO', 'jarvis' ); ?></label>


                                                        </div>
                                                    </div>
                                                    <br>
                                                    <span class="qc-opt-dcs-font"> <?php esc_html_e( 'You can also load JARVIS on specific page/s only using shortcode:', 'jarvis' ); ?> <strong>[jarvis_pro mode="vertical"]</strong> <span class="badge"> <?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></span>

                                                </div>
                                            </td>
                                        </tr>


                                    </tbody>
                                </table>
                            </section>

                           
                            <section id="section-flip-2">
                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <p class="qc-opt-title-font"> <?php esc_html_e( 'Construct your search query here. Add search terms here. You can connect each terms with natural language using the text fields', 'jarvis' ); ?></p>
                                        <td>
                                            <div class="cxsc-settings-blocks">
                                                <div class="phrase-example-holder">
                                                    <div class="phrase-example">
                                                        <div class="search-phrase btn alert alert-success"></div>
                                                        <div class="phrase-example-none pre-search-phrase">
                                                            <?php esc_html_e('Start building your search terms here', 'jarvis'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="cxsc-settings-block" id="cxsc-settings-block-general">
                                                    <?php jarvis_options_field_display('filter'); ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Rename Search Button Text', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <?php
                                                $jarvis_button_text = get_option('jarvis-search-button-text');
                                                if ((!isset($jarvis_button_text)) || ($jarvis_button_text == "")) {
                                                    $jarvis_button_text = esc_html("Find Them!", "jarvis");
                                                } ?>
                                                <input type="text" class="jarvis-search-button-text qc-opt-dcs-font"
                                                       name="jarvis-search-button-text"
                                                       id="jarvis-search-button-text"
                                                       value="<?php echo esc_attr(htmlentities($jarvis_button_text, ENT_QUOTES)); ?>">
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <p class="qc-opt-title-font">
                                                <?php esc_html_e( 'Show Search Result', 'jarvis' ); ?>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <ul class="">
                                                    <li><input type="radio"
                                                               name="jarvis_search_popup" <?php echo esc_attr(get_option('jarvis_search_popup') == '1' ? 'checked' : ''); ?>
                                                               value="1">
                                                        <span class="qc-opt-dcs-font"> <?php esc_html_e( 'In Pop Up Window', 'jarvis' ); ?></span>
                                                    </li>
                                                    <li><input disabled="disabled" type="radio"
                                                               name="jarvis_search_popup"
                                                               value="">
                                                        <span class="qc-opt-dcs-font"> <?php esc_html_e( 'In Shop Page', 'jarvis' ); ?></span>
                                                        <span class="badge"> <?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                            </section>
                            <section id="section-flip-3">
                                <table class="table table-bordered striped">
                                    <tbody>
                                    <tr>
                                        <td><br><br>
                                            <ul class="radio-list">
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-0.png"
                                                         alt=""> <input type="radio"
                                                                        name="jarvis_icon" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-0.png' ? 'checked' : ''); ?>
                                                                        value="icon-0.png">
                                                    <span class="qc-opt-dcs-font"> <?php esc_html_e( 'Icon - 0', 'jarvis' ); ?></span>
                                                </li>


                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-1.png"
                                                         alt=""> <input type="radio"
                                                                        name="jarvis_icon" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-1.png' ? 'checked' : ''); ?>
                                                                        value="icon-1.png">
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 1', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-2.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-2.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-2.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 2', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-3.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-3.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-3.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 3', 'jarvis' ); ?></span>
                                                </li>

                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-4.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-4.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-4.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 4', 'jarvis' ); ?></span>
                                                </li>


                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-5.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-5.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-5.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 5', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-6.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-6.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-6.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 6', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-7.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-7.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-7.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 7', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-8.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-8.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-8.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 8', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-9.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-9.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-9.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 9', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-10.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-10.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-10.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 10', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-11.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-11.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-11.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 11', 'jarvis' ); ?></span>
                                                </li>
                                                <li><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/icon-12.png"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="icon-12.png" <?php echo esc_attr(get_option('jarvis_icon') == 'icon-12.png' ? 'checked' : ''); ?>>
                                                    <span class="qc-opt-dcs-font"><?php esc_html_e( 'Icon - 12', 'jarvis' ); ?></span>
                                                </li>


                                                <li>
                                                    <img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/custom.png?<?php echo esc_attr(time()); ?>"
                                                         alt=""> <input type="radio" name="jarvis_icon"
                                                                        value="custom.png" <?php echo esc_attr(get_option('jarvis_icon') == 'custom.png' ? 'checked' : ''); ?>>

                                                    <span class="qc-opt-dcs-font"> <?php esc_html_e( 'Custom Icon', 'jarvis' ); ?></span>
                                                </li>


                                            </ul>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td><p class="qc-opt-title-font">
                                                 <?php esc_html_e( 'Upload custom Icon', 'jarvis' ); ?> <span class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span>
                                            </p>
                                            <div class="cxsc-settings-blocks">
                                                <p class="qc-opt-dcs-font"><?php echo esc_html_e('Select file to upload', 'jarvis') ?>
                                                    <input disabled="disabled" type="file"
                                                           name="custom_icon"
                                                           id="custom_icon"
                                                           size="35"
                                                           class=""/>
                                                    <label class="qc-opt-dcs-font" for="pepperoni"><?php esc_html_e( 'Upload Custom Icon', 'jarvis' ); ?></label>
                                            </div>
                                        </td>
                                    </tr>

                                    
                                    </tbody>
                                </table>
                            </section>
                            <section id="section-flip-4">
                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Global Notification Delay Time :', 'jarvis' ); ?>
                                    <input name="global_notification_delay_time"
                                           type="text"
                                           class="text-control input-sm"
                                           value="<?php echo esc_attr(get_option('global_notification_delay_time') ? get_option('global_notification_delay_time') : ''); ?>">
                                     <?php esc_html_e( 'in sec', 'jarvis' ); ?> </p>
                                <hr>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <h3 class="alert alert-success qc-opt-title-font"> <?php esc_html_e( 'SET NOTIFICATIONS FOR JARVIS', 'jarvis' ); ?></h3>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-xs-12">
                                        <p class="qc-opt-description"></p>
                                        <div class="cxsc-settings-blocks">
                                            <p>
                                                <?php $settings = array('textarea_name' =>
                                                    'message_one',
                                                    'textarea_rows' => 20,
                                                    'editor_height' => 100,
                                                    'editor_class' => 'customNotificationClass',
                                                    'media_buttons' => false
                                                );

                                                wp_editor(get_option('message_one'), 'message_one', $settings); ?>
                                        </div>
                                        <br>
                                        <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Message display duration time', 'jarvis' ); ?></p>
                                        <p>
                                            <input class="form-control input-sm" type="text"
                                                   value="<?php echo esc_attr(get_option('notification_delay_one')); ?>"
                                                   name="notification_delay_one">
                                            <strong class="qc-opt-dcs-font">  <?php esc_html_e( 'in second', 'jarvis' ); ?></strong></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <p class="qc-opt-description"></p>
                                        <div class="cxsc-settings-blocks">
                                            <p>
                                                <?php $settings = array('textarea_name' =>
                                                    'message_two',
                                                    'textarea_rows' => 20,
                                                    'editor_height' => 100,
                                                    'editor_class' => 'customNotificationClass',
                                                    'media_buttons' => false
                                                );

                                                wp_editor(get_option('message_two'), 'message_two', $settings); ?>
                                        </div>
                                        <br>
                                        <p class="qc-opt-dcs-font"><?php esc_html_e( 'Message display duration time ', 'jarvis' ); ?></p>
                                        <p>
                                            <input class="form-control input-sm" type="text"
                                                   value="<?php echo esc_attr(get_option('notification_delay_two')); ?>"
                                                   name="notification_delay_two">
                                            <strong class="qc-opt-dcs-font"> <?php esc_html_e( 'in second', 'jarvis' ); ?></strong></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-12">
                                        <p class="qc-opt-description"></p>
                                        <div class="cxsc-settings-blocks">
                                            <p>
                                                <?php $settings = array('textarea_name' =>
                                                    'message_three',
                                                    'textarea_rows' => 20,
                                                    'editor_height' => 100,
                                                    'editor_class' => 'customNotificationClass',
                                                    'media_buttons' => false
                                                );

                                                wp_editor(get_option('message_three'), 'message_three', $settings); ?>
                                        </div>
                                        <br>
                                        <p class="qc-opt-dcs-font"><?php esc_html_e( 'Message display duration time', 'jarvis' ); ?></p>
                                        <p>
                                            <input class="form-control input-sm" type="text"
                                                   value="<?php echo esc_attr(get_option('notification_delay_three')); ?>"
                                                   name="notification_delay_three">
                                            <strong class="qc-opt-dcs-font"> <?php esc_html_e( 'in second', 'jarvis' ); ?></strong></p>
                                    </div>
                                </div>
                            </section>

                            <section id="section-flip-5">
                                <div class="top-section">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Sale alerts will help you sell more products by creating an urgency among prospective buyers by showing who else just brought products from your store. If your store is not so busy, you can create artificial sell alerts below:', 'jarvis' ); ?></p>
                                            <p class="alert alert-success qc-opt-dcs-font "> <?php esc_html_e( 'GENERATE ARTIFICIAL SALE', 'jarvis' ); ?></p>

                                            <?php $artificial_sale_data = json_decode(get_option('artificial_orders_val')); ?>
                                            <p class="qc-opt-dcs-font">  <?php esc_html_e( 'Enable Sale Notification', 'jarvis' ); ?><span
                                                        class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <input disabled="disabled" id="fake_sale_notification"
                                                           type="checkbox"
                                                           name="fake_sale_notification"
                                                           value="fake_sale_notification" <?php echo esc_attr(get_option('fake_sale_notification') == 'fake_sale_notification' ? 'checked' : ''); ?>>
                                                    <label for="fake_sale_notification"> <?php esc_html_e( 'Disable Notification', 'jarvis' ); ?></label>
                                                </fieldset>
                                            </div>
                                            <br>
                                            <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Enable sale notification in mobile', 'jarvis' ); ?><span
                                                        class="badge"> <?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <input disabled="disabled" id="sell_notification_off_mobile"
                                                           type="checkbox"
                                                           name="sell_notification_off_mobile"
                                                           value="sell_notification_off_mobile" <?php echo esc_attr(get_option('sell_notification_off_mobile') == 'sell_notification_off_mobile' ? 'checked' : ''); ?>>
                                                    <label for="sell_notification_off_mobile"> <?php esc_html_e( 'Disable sell notification in mobile', 'jarvis' ); ?></label>
                                                </fieldset>
                                            </div>
                                            <br>
                                            <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Enable Sale Notification Sound', 'jarvis' ); ?><span
                                                        class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                            <div class="cxsc-settings-blocks">
                                                <fieldset>
                                                    <input disabled="disabled" id="notification_sound"
                                                           type="checkbox"
                                                           name="fake_sale_notification_sound"
                                                           value="fake_sale_notification_sound" <?php echo esc_attr(get_option('fake_sale_notification_sound') == 'fake_sale_notification_sound' ? 'checked' : ''); ?>>
                                                    <label for="notification_sound"><?php esc_html_e( 'Disable Notification Sound ', 'jarvis' ); ?></label>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <p qc-opt-dcs-font> <?php esc_html_e( 'Global notification delay time for artificial sale :', 'jarvis' ); ?>
                                                <input disabled="disabled"
                                                       name="global_fake_sale_delay_time"
                                                       type="text"
                                                       class="text-control input-sm"
                                                       value="<?php echo esc_attr(get_option('global_fake_sale_delay_time') ? get_option('global_fake_sale_delay_time') : ''); ?>">
                                                <?php esc_html_e( 'in sec', 'jarvis' ); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <!--top-section-->

                                <div class="block-section">
                                    <?php if (!empty($artificial_sale_data)) { ?>
                                        <?php foreach ($artificial_sale_data as $sale_data) : ?>
                                            <div class="block-inner">
                                                <div class="col-xs-12 text-right">
                                                    <button disabled="disabled" type="button"
                                                            class="btn btn-danger qcld-remove-on-item "><i
                                                                class="fa fa-times" aria-hidden="true"></i>
                                                        <?php esc_html_e( 'Remove', 'jarvis' ); ?>
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?php $products = $sale_data->product_id; ?>
                                                        <p class="qc-opt-title-font"><?php esc_html_e('Select a product', 'jarvis'); ?> <span
                                                                    class="badge"><?php esc_html_e('Pro version only', 'jarvis'); ?></span></p>
                                                        <div class="cxsc-settings-blocks">
                                                            <?php $params = array('posts_per_page' => -1, 'post_type' => 'product');
                                                            $wc_query = new WP_Query($params); ?>
                                                            <select disabled="disabled" name="fake-product-one"
                                                                    class="jarvis_select_two">
                                                                <?php //$_pf = new WC_Product_Factory(); ?>
                                                                <?php $product = wc_get_product($products); ?>
                                                                <option value="" selected="selected"><?php esc_html_e('Select product(s)', 'jarvis'); ?>
                                                                </option>
                                                                <?php if ($wc_query->have_posts()) : ?>
                                                                    <?php while ($wc_query->have_posts()) :$wc_query->the_post(); ?>
                                                                        <option value="<?php the_ID(); ?>">
                                                                            <?php the_title(); ?>
                                                                        </option>
                                                                    <?php endwhile; ?>
                                                                    <?php wp_reset_postdata(); ?>
                                                                <?php else: ?>
                                                                    <li>
                                                                        <?php esc_html_e('No Products', 'jarvis'); ?>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <p class="qc-opt-description"></p>
                                                        <div class="cxsc-settings-blocks">
                                                            <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Customer Name', 'jarvis' ); ?></p>
                                                            <input disabled="disabled" type="text"
                                                                   class="form-control customer-name"
                                                                   name="customer_name_one"
                                                                   value=""
                                                                   placeholder="Customer Name">
                                                            <p class="qc-opt-dcs-font"><?php esc_html_e( 'Customer Address', 'jarvis' ); ?></p>
                                                            <textarea disabled="disabled"
                                                                      name="customer_address_one"
                                                                      class="form-control customer-address"
                                                                      cols="10"
                                                                      rows="2"><?php echo esc_attr($sale_data->customer_address); ?></textarea>
                                                            <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Fake Sale Notification Duration Time', 'jarvis' ); ?></p>
                                                            <p class="qc-opt-dcs-font">
                                                                <input disabled="disabled"
                                                                       class="form-control input-sm notification-duration"
                                                                       type="text"
                                                                       value="<?php echo esc_attr($sale_data->notification_duration); ?>"
                                                                       name="fake_sale_notification_delay_one">
                                                                <strong>  <?php esc_html_e( 'in second', 'jarvis' ); ?></strong></p>
                                                        </div>
                                                        <br>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php } else { ?>
                                        <div class="block-inner">
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <p class="qc-opt-title-font"> <?php esc_html_e( 'Select a product', 'jarvis' ); ?><span
                                                                class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                                    <div class="cxsc-settings-blocks">
                                                        <?php $params = array('posts_per_page' => -1, 'post_type' => 'product');
                                                        $wc_query = new WP_Query($params); ?>
                                                        <select disabled="disabled" name="fake-product-one"
                                                                class="jarvis_select_two">
                                                            <option value=""><?php esc_html_e( 'Select a product', 'jarvis' ); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-xs-12">
                                                    <p class="qc-opt-description"></p>
                                                    <div class="cxsc-settings-blocks">
                                                        <p class="qc-opt-dcs-font"><?php esc_html_e( 'Customer Name', 'jarvis' ); ?></p>
                                                        <input disabled="disabled" type="text"
                                                               class="form-control customer-name"
                                                               name=""
                                                               value=""
                                                               placeholder="Customer Name">
                                                        <p class="qc-opt-dcs-font"><strong><?php esc_html_e( 'Customer Address', 'jarvis' ); ?></strong>
                                                        </p>
                                                        <textarea disabled="disabled" name=""
                                                                  class="form-control customer-address"
                                                                  cols="30"
                                                                  rows="2"></textarea>
                                                        <p class="qc-opt-dcs-font"><?php esc_html_e( 'Fake Sale Notification Duration Time', 'jarvis' ); ?></p>
                                                        <p class="qc-opt-dcs-font">
                                                            <input disabled="disabled"
                                                                   class="form-control input-sm notification-duration"
                                                                   type="text"
                                                                   value=""
                                                                   name="fake_sale_notification_delay_one">
                                                            <strong> <?php esc_html_e( 'in second', 'jarvis' ); ?></strong>
                                                        </p>
                                                    </div>
                                                    <br>
                                                </div>
                                            </div>
                                        </div>


                                    <?php } ?>
                                    <!-- block inner -->
                                </div>
                                <!--block-section-->
                                <div class="row">
                                    <div class="col-sm-6 text-left">
                                        <button class="btn btn-success " type="button" id="" disabled="disabled">
                                            <?php esc_html_e( 'Save Order Notification', 'jarvis' ); ?>
                                        </button>
                                        <input type="hidden" id="artificial-orders-val"
                                               name="artificial_orders_val">

                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <button class="btn btn-warning" type="button" disabled="disabled" id=""><i
                                                    class="fa fa-plus" aria-hidden="true"></i>
                                            <?php esc_html_e( 'Add More', 'jarvis' ); ?>
                                        </button>

                                    </div>
                                </div>
                            </section>
                            <section id="section-flip-7">
                                <tr>
                                    <td><p class="qc-opt-title-font"><strong> <?php esc_html_e( 'Translate various titles for popup view', 'jarvis' ); ?></strong></p>
                                        <div class="cxsc-settings-blocks">
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Recently Viewed Products - Left Blank For Default Value ', 'jarvis' ); ?></p>
                                                <p class="qc-opt-title-font"><?php esc_html_e( 'Global Font Size For Widgets : ', 'jarvis' ); ?><input
                                                            name="global_widget_font_size"
                                                            type="text"
                                                            class="text-control input-sm qc-opt-dcs-font"
                                                            value="<?php echo esc_attr(get_option('global_widget_font_size') ? get_option('global_widget_font_size') : ''); ?>">
                                                    in px
                                                </p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="recently_viewed_products_title"
                                                       value="<?php echo esc_attr(get_option('recently_viewed_products_title') ? get_option('recently_viewed_products_title') : ''); ?>"
                                                       placeholder="<?php esc_html_e( 'Recently Viewed Products ', 'jarvis' ); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-title-font">
                                                    <?php esc_html_e( 'How May I Assist You Today - Left Blank For Default Value', 'jarvis' ); ?>
                                                </p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="assist_today_title"
                                                       value="<?php echo esc_attr(get_option('assist_today_title') ? get_option('assist_today_title') : ''); ?>"
                                                       placeholder="<?php esc_html_e( 'How May I Assist You Today', 'jarvis' ); ?>">
                                            </div>

                                            <div class="form-group">
                                                <p class="qc-opt-title-font">
                                                    <?php esc_html_e( 'Recommended Products', 'jarvis' ); ?>
                                                </p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="recommended_products_title"
                                                       value="<?php echo esc_attr(get_option('recommended_products_title') ? get_option('recommended_products_title') : ''); ?>"
                                                       placeholder="<?php esc_html_e( 'Recommended Products', 'jarvis' ); ?>">
                                            </div>

                                            <div class="form-group">
                                                <p class="qc-opt-title-font">
                                                    <?php esc_html_e( 'User "From" Purchased a', 'jarvis' ); ?>
                                                </p>

                                                <div class="input-group">
                                                    <input name="user_from_title" type="text"
                                                           class="form-control qc-opt-dcs-font"
                                                           value="<?php echo esc_attr(get_option('user_from_title') ? get_option('user_from_title') : ''); ?>"/>
                                                    <span class="input-group-addon">-</span>
                                                    <input name="user_purchased_a_title" type="text"
                                                           class="form-control qc-opt-dcs-font"
                                                           value="<?php echo esc_attr(get_option('user_purchased_a_title') ? get_option('user_purchased_a_title') : ''); ?>"/>
                                                </div>
                                            </div>


                                        </div>
                                        <br>
                                    </td>


                                </tr>
                                <tr>
                                    <td>
                                        <div id="jarvis-ajax-search-message">
                                            <p class="qc-opt-title-font"><?php esc_html_e( 'Message settings for', 'jarvis' ); ?><strong> <?php esc_html_e( 'In Pop Up  Window', 'jarvis' ); ?></strong></p>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'if products found', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="p_scs_msg"
                                                       value="<?php echo esc_attr(get_option('p_scs_msg') != '' ? get_option('p_scs_msg') : 'Great! We have these products.'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'if products not found', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="p_fail_msg"
                                                       value="<?php echo esc_attr(get_option('p_fail_msg') != '' ? get_option('p_fail_msg') : 'Oops! Nothing matches your exact criteria. How about selecting some different options?'); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div id="jarvis-ajax-search-message">
                                            <p class="qc-opt-title-font"><?php esc_html_e( 'Message settings for ', 'jarvis' ); ?><strong> <?php esc_html_e( 'Recently Sold Products ', 'jarvis' ); ?></strong></p>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'Recently Sold Products ', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_product_title"
                                                       value="<?php echo esc_attr(get_option('last_sold_product_title') != '' ? get_option('last_sold_product_title') : 'Recently Sold Products'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'No recently sold products found ', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="no_last_sold_product"
                                                       value="<?php echo esc_attr(get_option('no_last_sold_product') != '' ? get_option('no_last_sold_product') : 'No recently sold products found.'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'from', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_from"
                                                       value="<?php echo esc_attr(get_option('last_sold_from') != '' ? get_option('last_sold_from') : 'from'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Guest from', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_guest_from"
                                                       value="<?php echo esc_attr(get_option('last_sold_guest_from') != '' ? get_option('last_sold_guest_from') : 'Guest from'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'just purchased', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_just_purchased"
                                                       value="<?php echo esc_attr(get_option('last_sold_just_purchased') != '' ? get_option('last_sold_just_purchased') : 'just purchased'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'purchased a', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_purchased_a"
                                                       value="<?php echo esc_attr(get_option('last_sold_purchased_a') != '' ? get_option('last_sold_purchased_a') : 'purchased a'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'Last sold Time', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="last_sold_time"
                                                       value="<?php echo esc_attr(get_option('last_sold_time') != '' ? get_option('last_sold_time') : 'About 20 minutes  ago'); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div id="jarvis-ajax-search-message">
                                            <p class="qc-opt-title-font"><?php esc_html_e( 'Message settings for ', 'jarvis' ); ?><strong> <?php esc_html_e( 'Recently Viewed Products ', 'jarvis' ); ?></strong></p>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Add to cart', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="recently_viewed_add_to_cart"
                                                       value="<?php echo esc_attr(get_option('recently_viewed_add_to_cart') != '' ? get_option('recently_viewed_add_to_cart') : 'Add to cart'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'View Detail', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="recently_viewed_details"
                                                       value="<?php echo esc_attr(get_option('recently_viewed_details') != '' ? get_option('recently_viewed_details') : 'View Detail'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'You have not viewed any products yet !', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="recently_viewed_no_product_founnd"
                                                       value="<?php echo esc_attr(get_option('recently_viewed_no_product_founnd') != '' ? get_option('recently_viewed_no_product_founnd') : 'You have not viewed any products yet !'); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div id="jarvis-ajax-search-message">
                                            <p class="qc-opt-title-font"> <?php esc_html_e( 'Message settings for', 'jarvis' ); ?><strong>  <?php esc_html_e( 'Cart', 'jarvis' ); ?></strong></p>

                                            <div class="form-group">
                                                <p class="qc-opt-title-font"> <?php esc_html_e( 'Your Basket - Left Blank For Default Value', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="your_basket_title"
                                                       value="<?php echo esc_attr(get_option('your_basket_title') ? get_option('your_basket_title') : 'Your Basket'); ?>">
                                            </div>

                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'You do not have any products in the cart ', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="cart_no_product_found"
                                                       value="<?php echo esc_attr(get_option('cart_no_product_found') != '' ? get_option('cart_no_product_found') : 'You do not have any products in the cart'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"><?php esc_html_e( 'Total Price', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="cart_total_price"
                                                       value="<?php echo esc_attr(get_option('cart_total_price') != '' ? get_option('cart_total_price') : 'Total Price'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Cart', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="cart_cart_link"
                                                       value="<?php echo esc_attr(get_option('cart_cart_link') != '' ? get_option('cart_cart_link') : 'Cart'); ?>">
                                            </div>
                                            <div class="form-group">
                                                <p class="qc-opt-dcs-font"> <?php esc_html_e( 'Checkout', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="cart_checkout_link"
                                                       value="<?php echo esc_attr(get_option('cart_checkout_link') != '' ? get_option('cart_checkout_link') : 'Checkout'); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div id="jarvis-ajax-search-message">
                                            <p class="qc-opt-title-font"> <?php esc_html_e( 'Message settings for', 'jarvis' ); ?> <strong> <?php esc_html_e( 'Quick Ball', 'jarvis' ); ?></strong></p>

                                            <div class="form-group">
                                                <p class="qc-opt-title-font"> <?php esc_html_e( 'Questions or Comments? Contact Us Value', 'jarvis' ); ?></p>
                                                <input type="text" class="form-control qc-opt-dcs-font"
                                                       name="jarvis_support_form_title"
                                                       value="<?php echo esc_attr(get_option('jarvis_support_form_title') ? get_option('jarvis_support_form_title') : 'Questions or Comments? Contact Us'); ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-group">
                                            <p class="qc-opt-title-font"> <?php esc_html_e( 'min ago', 'jarvis' ); ?> <span class="badge"><?php esc_html_e( 'Pro version only', 'jarvis' ); ?></span></p>
                                            <input type="text" class="form-control qc-opt-dcs-font"
                                                   name="jarvis_lan_notification_min_ago"
                                                   value="<?php echo esc_attr(get_option('jarvis_lan_notification_min_ago') ? get_option('jarvis_lan_notification_min_ago') : 'min ago'); ?>">
                                        </div>
                                    </td>
                                </tr>
                            </section>
                            <section id="section-flip-8">
                                <div class="top-section">
                                    <div class="row">
                                        <div class="col-xs-12">
                                            <p class="qc-opt-dcs-font"><?php esc_html_e( 'You can paste or write your custom css here.', 'jarvis' ); ?></p>
                                            <textarea name="custom_global_css"
                                                      class="form-control custom-global-css"
                                                      cols="10"
                                                      rows="4"><?php echo esc_attr(get_option('custom_global_css')); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            
                            <section id="section-flip-9">
                                <div class="top-section">
                                    <div class="row">
                                        <div class="col-xs-12">

                                        <div class="qcld_jarvis_wrap">
                                            <h2>  <?php esc_html_e( 'Conversion Report', 'jarvis' ); ?></h2>
                                            <p> <?php esc_html_e( 'Conversion Report is a Pro Version Feature. Please', 'jarvis' ); ?> <a href="<?php echo esc_url('https://www.quantumcloud.com/products/woocommerce-shop-assistant-jarvis/'); ?>" target="_blank"><span style="font-weight: bold; color: #FCB214"><?php esc_html_e( 'Upgrade to the Pro Version.', 'jarvis' ); ?> </span> </a></p>

                                            <div id="qcld_express_content">                  
                                              <!-- Promo Block 1 -->
                                              <div style="margin-top: 20px;"> <div class="qc-promo-plugins" ><img src="<?php echo esc_url(QC_JARVIS_IMG_URL); ?>/qc-logo-full.png" alt="QuantumCloud Logo"><br><br><hr><br><a href="<?php echo esc_url('http://www.quantumcloud.com'); ?>" target="_blank" style="margin-left: 65px;"><?php esc_html_e( 'QuantumCloud', 'jarvis' ); ?></a></div> </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div><!-- /content -->
                    </div><!-- /tabs-jarvis -->
                    <hr>
                    <div class="row">
                        <div class="text-left col-sm-3 col-sm-offset-3">
                            <input type="button" class="btn btn-warning submit-button"
                                   id="jarvis-reset-options-default"
                                   value="<?php esc_html_e('Reset all options to default', 'jarvis'); ?>"/>
                        </div>
                        <div class="text-right col-sm-6">
                            <input type="hidden" name="action" value="jarvis-submitted"/>
                            <input type="submit" class="btn btn-primary submit-button" name="submit"
                                   id="submit" value="<?php esc_html_e('Save Settings', 'jarvis'); ?>"/>
                        </div>
                    </div>

                </section>
            </div>


            <?php wp_nonce_field('jarvis'); ?>
        </form>

        <?php

    }

    /**
     * Create shortcode for Jarvis
     */
    public function jarvis_frontend_shortcode($atts)
    {

        global $woocommerce;

        if (class_exists('WC_Shortcodes') && method_exists('WC_Shortcodes', 'shortcode_wrapper')) {
            return WC_Shortcodes::shortcode_wrapper(array('QC_Shortcode_Jarvis', 'output'), $atts);
        } else {
            return $woocommerce->shortcode_wrapper(array('QC_Shortcode_Jarvis', 'output'), $atts);
        }
    }


    public function wsaj_widget_shortcode($atts)
    {

        global $woocommerce;

        if (class_exists('WC_Shortcodes') && method_exists('WC_Shortcodes', 'shortcode_wrapper')) {
            return WC_Shortcodes::shortcode_wrapper(array('QC_Widget_Jarvis', 'output'), $atts);
        } else {
            return $woocommerce->shortcode_wrapper(array('QC_Widget_Jarvis', 'output'), $atts);
        }
    }

    /**
     * Save Options
     */


    function jarvis_save_options()
    {

        global $woocommerce;

        if (!current_user_can('manage_options')) {
            exit;
        }


        if (isset($_POST['_wpnonce']) && sanitize_text_field(wp_unslash($_POST['_wpnonce']))) {


            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'jarvis');


            // Check if the form is submitted or not

            if (isset($_POST['submit'])) {

                if (isset($_POST["jarvis-terms-fields"])) {

                    $filter_fields =  isset($_POST["jarvis-terms-fields"]) ? wp_unslash($_POST["jarvis-terms-fields"]) : array();
                    if ($filter_fields) {
                        $formatted_filter_fields = array();
                        foreach ($filter_fields as $filter_field) {
                            if ($filter_field["filter"] != "") {
                                $formatted_filter_fields[] = stripslashes_deep(array_map("wc_clean", $filter_field));
                            }
                        }
                        $serialized_filter_fields = maybe_serialize($formatted_filter_fields);

                        update_option('jarvis-terms-fields', $serialized_filter_fields);

                    }
                } else {
                    if (( isset($_GET["page"]) && $_GET["page"] == "jarvis") && (isset($_POST["action"]))) {
                        delete_option('jarvis-terms-fields');
                    }
                }

                if (isset($_POST["jarvis-search-button-text"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["jarvis-search-button-text"])));
                    update_option('jarvis-search-button-text', $button_text);
                }
                if (isset($_POST["position_x"])) {
                    $position_x = sanitize_text_field(wp_unslash($_POST["position_x"]));
                    update_option('position_x', $position_x);
                }
                if (isset($_POST["position_y"])) {
                    $position_y = sanitize_text_field(wp_unslash($_POST["position_y"]));
                    update_option('position_y', $position_y);
                }


                /**
                 * ARTIFICIAL SALE NOTIFICATION DELAYS
                 */


                if ( isset($_POST['artificial_orders_val'])) {
                    $artificial_orders_val = isset($_POST['artificial_orders_val']) ? stripslashes(sanitize_text_field(wp_unslash($_POST['artificial_orders_val']))) : '';
                    update_option('artificial_orders_val', $artificial_orders_val);
                }


                /**
                 * MESSAGE NOTIFICATION DELAY
                 */


                if (isset($_POST["notification_delay_one"])) {
                    $notification_delay_one = sanitize_text_field(wp_unslash($_POST["notification_delay_one"]));
                    update_option('notification_delay_one', $notification_delay_one);
                }


                if (isset($_POST["notification_delay_two"])) {
                    $notification_delay_two = sanitize_text_field(wp_unslash($_POST["notification_delay_two"]));
                    update_option('notification_delay_two', $notification_delay_two);
                }


                if (isset($_POST["notification_delay_three"])) {
                    $notification_delay_three = sanitize_text_field(wp_unslash($_POST["notification_delay_three"]));
                    update_option('notification_delay_three', $notification_delay_three);
                }


                if (isset($_POST["notification_delay_four"])) {
                    $notification_delay_four = sanitize_text_field(wp_unslash($_POST["notification_delay_four"]));
                    update_option('notification_delay_four', $notification_delay_four);
                }


                if (isset($_POST["notification_delay_five"])) {
                    $notification_delay_five = sanitize_text_field(wp_unslash($_POST["notification_delay_five"]));
                    update_option('notification_delay_five', $notification_delay_five);
                }


                if (isset($_POST["notification_delay_six"])) {
                    $notification_delay_six = sanitize_text_field(wp_unslash($_POST["notification_delay_six"]));
                    update_option('notification_delay_six', $notification_delay_six);
                }
                if (isset($_POST["notification_delay_seven"])) {
                    $notification_delay_seven = sanitize_text_field(wp_unslash($_POST["notification_delay_seven"]));
                    update_option('notification_delay_seven', $notification_delay_seven);
                }

                if (isset($_POST["notification_delay_eight"])) {
                    $notification_delay_eight = sanitize_text_field(wp_unslash($_POST["notification_delay_eight"]));
                    update_option('notification_delay_eight', $notification_delay_eight);
                }

                if (isset($_POST["disable_jarvis"])) {
                    update_option('disable_jarvis', '');
                }else{
                    update_option('disable_jarvis', '1');
                }

                if (isset($_POST["jarvis_mode"])) {
                    $jarvis_mode = sanitize_text_field(wp_unslash($_POST["jarvis_mode"]));
                    update_option('jarvis_mode', $jarvis_mode);
                }else{
                    update_option('jarvis_mode', '');
                }
                if (isset($_POST["disable_cart_item"])) {
                    $disable_cart_item = sanitize_text_field(wp_unslash($_POST["disable_cart_item"]));
                    update_option('disable_cart_item', $disable_cart_item);
                }else{
                    update_option('disable_cart_item', '');
                }

                if (isset($_POST["disable_jarvis_icon_animation"])) {
                $disable_jarvis_icon_animation = sanitize_text_field(wp_unslash($_POST["disable_jarvis_icon_animation"]));
                update_option('disable_jarvis_icon_animation', $disable_jarvis_icon_animation);
                }else{
                    update_option('disable_jarvis_icon_animation', '');
                }
                /**
                 * FAKE SALE
                 */

                if (isset($_POST["customer_name_one"])) {
                    $customer_name_one = sanitize_text_field(wp_unslash($_POST["customer_name_one"]));
                    update_option('customer_name_one', $customer_name_one);
                }else{
                    update_option('customer_name_one', '');                    
                }

                if (isset($_POST["customer_address_one"])) {
                    $customer_address_one = sanitize_text_field(wp_unslash($_POST["customer_address_one"]));
                    update_option('customer_address_one', $customer_address_one);
                }else{
                    update_option('customer_address_one', '');
                }


                if (isset($_POST["customer_name_two"])) {
                    $customer_name_two = sanitize_text_field(wp_unslash($_POST["customer_name_two"]));
                    update_option('customer_name_two', $customer_name_two);
                }else{
                    update_option('customer_name_two', '');                    
                }

                if (isset($_POST["customer_address_two"])) {
                    $customer_address_two = sanitize_text_field(wp_unslash($_POST["customer_address_two"]));
                    update_option('customer_address_two', $customer_address_two);
                }else{
                    update_option('customer_address_two', '');

                }

                if (isset($_POST["customer_name_three"])) {
                    $customer_name_three = sanitize_text_field(wp_unslash($_POST["customer_name_three"]));
                    update_option('customer_name_three', $customer_name_three);
                }else{
                    update_option('customer_name_three', '');

                }


                if (isset($_POST["customer_address_three"])) {
                    $customer_address_three = sanitize_text_field(wp_unslash($_POST["customer_address_three"]));
                    update_option('customer_address_three', $customer_address_three);
                }else{
                    update_option('customer_address_three', '');
                    
                }


                /**
                 * FRONT END TITLE
                 */

                $recently_viewed_products_title = isset($_POST["recently_viewed_products_title"]) ? sanitize_text_field(wp_unslash($_POST["recently_viewed_products_title"])) : '';
                update_option('recently_viewed_products_title', $recently_viewed_products_title );

                $assist_today_title = isset($_POST["assist_today_title"]) ? sanitize_text_field(wp_unslash($_POST["assist_today_title"])) : '';
                update_option('assist_today_title', $assist_today_title );

                $your_basket_title = isset($_POST["your_basket_title"]) ? sanitize_text_field(wp_unslash($_POST["your_basket_title"])) : '';
                update_option('your_basket_title', $your_basket_title );

                $recommended_products_title = isset($_POST["recommended_products_title"]) ? sanitize_text_field(wp_unslash($_POST["recommended_products_title"])) : '';
                update_option('recommended_products_title', $recommended_products_title );

                $user_purchased_a_title = isset($_POST["user_purchased_a_title"]) ? sanitize_text_field(wp_unslash($_POST["user_purchased_a_title"])) : '';
                update_option('user_purchased_a_title', $user_purchased_a_title );

                $user_from_title = isset($_POST["user_from_title"]) ? sanitize_text_field(wp_unslash($_POST["user_from_title"])) : '';
                update_option('user_from_title', $user_from_title );


                if (isset($_POST["message_one"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_one"])));
                    update_option('message_one', $button_text);
                }
                if (isset($_POST["message_two"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_two"])));
                    update_option('message_two', $button_text);
                }
                if (isset($_POST["message_three"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_three"])));
                    update_option('message_three', $button_text);
                }
                if (isset($_POST["message_four"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_four"]))); 
                    update_option('message_four', $button_text);
                }
                if (isset($_POST["message_five"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_five"])));
                    update_option('message_five', $button_text);
                }
                if (isset($_POST["message_six"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_six"])));
                    update_option('message_six', $button_text);
                }
                if (isset($_POST["message_seven"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_seven"])));
                    update_option('message_seven', $button_text);
                }
                if (isset($_POST["message_eight"])) {
                    $button_text = stripslashes(sanitize_text_field(wp_unslash($_POST["message_eight"])));
                    update_option('message_eight', $button_text);
                }


                $jarvis_form_animation = isset($_POST['jarvis_form_animation']) ? sanitize_text_field(wp_unslash($_POST['jarvis_form_animation'])) : '';
                update_option('jarvis_form_animation', $jarvis_form_animation );

                if (isset($_POST["cart_products"])) {
                    update_option('cart_products', sanitize_text_field(wp_unslash($_POST['cart_products'])));
                }else{
                    update_option('cart_products', '');

                }

                if (isset($_POST["jarvis_search"])) {
                    update_option('jarvis_search', sanitize_text_field(wp_unslash($_POST['jarvis_search'])));
                }else{
                    update_option('jarvis_search', '');

                }

                if (isset($_POST["loop_notification"])) {
                    update_option('loop_notification', sanitize_text_field(wp_unslash($_POST['loop_notification'])));
                }else{
                    update_option('loop_notification', '');

                }


                if (isset($_POST["disable_notification"])) {
                    update_option('disable_notification', sanitize_text_field(wp_unslash($_POST['disable_notification'])));
                }else{
                    update_option('disable_notification', '');
                }


                $jarvis_icon = isset($_POST['jarvis_icon']) ? sanitize_text_field(wp_unslash($_POST['jarvis_icon'])) : esc_attr('icon-1.png');
                update_option('jarvis_icon', sanitize_text_field($jarvis_icon));

                $jarvis_search_popup = isset($_POST['jarvis_search_popup']) ? sanitize_text_field(wp_unslash($_POST['jarvis_search_popup'])) : esc_attr('2');
                update_option('jarvis_search_popup', sanitize_text_field($jarvis_search_popup));


                $jarvis_theme = isset($_POST['jarvis_theme']) ? sanitize_text_field(wp_unslash($_POST['jarvis_theme'])) : esc_attr('theme-one.jpg');
                update_option('jarvis_theme', sanitize_text_field($jarvis_theme));

                if (isset($_POST["last_purchased_product"])) {
                    update_option('last_purchased_product', sanitize_text_field(wp_unslash($_POST['last_purchased_product'])));
                }else{
                    update_option('last_purchased_product', '');
                }


                //Support Quickball
                if (isset($_POST["support_quickball"])) {
                    update_option('support_quickball', sanitize_text_field(wp_unslash($_POST['support_quickball'])));
                }else{
                    update_option('support_quickball', '');
                }

                if (isset($_POST["support_quickball_email"])) {
                    update_option('support_quickball_email', sanitize_text_field(wp_unslash($_POST['support_quickball_email'])));
                }else{
                    update_option('support_quickball_email', '');
                }
                //Custom Quickball


                if (isset($_POST["custom_quickball_link"])) {
                    update_option('custom_quickball_link', sanitize_text_field(wp_unslash($_POST['custom_quickball_link'])));
                }else{
                    update_option('custom_quickball_link', '');
                }


                if (isset($_POST["global_notification_delay_time"])) {
                    update_option('global_notification_delay_time', sanitize_text_field(wp_unslash($_POST['global_notification_delay_time'])));
                }else{
                    update_option('global_notification_delay_time', '');
                }

                if (isset($_POST["global_fake_sale_delay_time"])) {
                    update_option('global_fake_sale_delay_time', sanitize_text_field(wp_unslash($_POST['global_fake_sale_delay_time'])));
                }else{
                    update_option('global_fake_sale_delay_time', '');                
                }

                if (isset($_POST["global_widget_font_size"])) {
                    update_option('global_widget_font_size', sanitize_text_field(wp_unslash($_POST['global_widget_font_size'])));
                }else{
                    update_option('global_widget_font_size', '');                
                }

                if (isset($_POST["qcld_jarvis_front_tags"])) {
                    $grid_data = htmlentities(stripslashes(sanitize_text_field(wp_unslash($_POST['qcld_jarvis_front_tags']))));
                }else{
                   $grid_data=''; 
                }


                if (isset($_POST["fake-product-one"])) {
                    update_option('fake-product-one', sanitize_text_field(wp_unslash($_POST["fake-product-one"])));
                }else{
                    update_option('fake-product-one', '');
                }

                if (isset($_POST["fake-product-two"])) {
                    update_option('fake-product-two', sanitize_text_field(wp_unslash($_POST["fake-product-two"])));
                }else{
                    update_option('fake-product-two', '');                
                }

                if (isset($_POST["fake-product-three"])) {
                    update_option('fake-product-three', sanitize_text_field(wp_unslash($_POST["fake-product-three"])));
                }else{
                    update_option('fake-product-three','');

                }
                //Ajax search message setting
                $p_scs_msg = isset($_POST["p_scs_msg"]) ? sanitize_text_field(wp_unslash($_POST["p_scs_msg"])) : '';
                update_option('p_scs_msg', $p_scs_msg);
                $p_fail_msg = isset($_POST["p_fail_msg"]) ? sanitize_text_field(wp_unslash($_POST["p_fail_msg"])) : '';
                update_option('p_fail_msg', $p_fail_msg);


                //custom quickball icon

                if (isset($_FILES['custom_quickball_icon']['tmp_name']) && !empty($_FILES['custom_quickball_icon']['tmp_name']) ) {

                    $quickball_pic = esc_attr('custom_quickball_icon.png');
                    $quickball_img_path = QC_JARVIS_IMG_ABSOLUTE_PATH . '/quickball-icon/' . $quickball_pic;

                    $quickball_pic_loc = isset($_FILES['custom_quickball_icon']['tmp_name']) ? sanitize_text_field(wp_unslash($_FILES['custom_quickball_icon']['tmp_name'])) : '';


                    if ( !empty($quickball_pic_loc) && move_uploaded_file($quickball_pic_loc, $quickball_img_path)) {
                        update_option('custom_quickball_icon', $quickball_pic);
             
                    }


                }


                //Custom css to over write style.
                if (isset($_POST["custom_global_css"])) {
                    $custom_global_css = sanitize_text_field(wp_unslash($_POST["custom_global_css"]));
                    update_option('custom_global_css', $custom_global_css);
                }
                //Last sold products Language settings
                if (isset($_POST["last_sold_product_title"])) {
                    $last_sold_product_title = sanitize_text_field(wp_unslash($_POST["last_sold_product_title"]));
                    update_option('last_sold_product_title', $last_sold_product_title);
                }
                if (isset($_POST["no_last_sold_product"])) {
                    $no_last_sold_product = sanitize_text_field(wp_unslash($_POST["no_last_sold_product"]));
                    update_option('no_last_sold_product', $no_last_sold_product);
                }
                if (isset($_POST["last_sold_from"])) {
                    $last_sold_from = sanitize_text_field(wp_unslash($_POST["last_sold_from"]));
                    update_option('last_sold_from', $last_sold_from);
                }
                if (isset($_POST["last_sold_guest_from"])) {
                    $last_sold_guest_from = sanitize_text_field(wp_unslash($_POST["last_sold_guest_from"]));
                    update_option('last_sold_guest_from', $last_sold_guest_from);
                }
                if (isset($_POST["last_sold_just_purchased"])) {
                    $last_sold_just_purchased = sanitize_text_field(wp_unslash($_POST["last_sold_just_purchased"]));
                    update_option('last_sold_just_purchased', $last_sold_just_purchased);
                }
                if (isset($_POST["last_sold_purchased_a"])) {
                    $last_sold_purchased_a = sanitize_text_field(wp_unslash($_POST["last_sold_purchased_a"]));
                    update_option('last_sold_purchased_a', $last_sold_purchased_a);
                }
                if (isset($_POST["last_sold_time"])) {
                    $last_sold_time = sanitize_text_field(wp_unslash($_POST["last_sold_time"]));
                    update_option('last_sold_time', $last_sold_time);
                }
                //Cart language settings
                if (isset($_POST["cart_no_product_found"])) {
                    $cart_no_product_found = sanitize_text_field(wp_unslash($_POST["cart_no_product_found"]));
                    update_option('cart_no_product_found', $cart_no_product_found);
                }
                if (isset($_POST["cart_total_price"])) {
                    $cart_total_price = sanitize_text_field(wp_unslash($_POST["cart_total_price"]));
                    update_option('cart_total_price', $cart_total_price);
                }
                if (isset($_POST["cart_cart_link"])) {
                    $cart_cart_link = sanitize_text_field(wp_unslash($_POST["cart_cart_link"]));
                    update_option('cart_cart_link', $cart_cart_link);
                }
                if (isset($_POST["cart_checkout_link"])) {
                    $cart_checkout_link = sanitize_text_field(wp_unslash($_POST["cart_checkout_link"]));
                    update_option('cart_checkout_link', $cart_checkout_link);
                }
                //recent view product language settings.
                if (isset($_POST["recently_viewed_add_to_cart"])) {
                    $recently_viewed_add_to_cart = sanitize_text_field(wp_unslash($_POST["recently_viewed_add_to_cart"]));
                    update_option('recently_viewed_add_to_cart', $recently_viewed_add_to_cart);
                }
                if (isset($_POST["recently_viewed_details"])) {
                    $recently_viewed_details = sanitize_text_field(wp_unslash($_POST["recently_viewed_details"]));
                    update_option('recently_viewed_details', $recently_viewed_details);
                }
                if (isset($_POST["recently_viewed_no_product_founnd"])) {
                    $recently_viewed_no_product_founnd = sanitize_text_field(wp_unslash($_POST["recently_viewed_no_product_founnd"]));
                    update_option('recently_viewed_no_product_founnd', $recently_viewed_no_product_founnd);
                }
                //Quickball setting

                if (isset($_POST["jarvis_support_form_title"])) {
                    $jarvis_support_form_title = sanitize_text_field(wp_unslash($_POST["jarvis_support_form_title"]));
                    update_option('jarvis_support_form_title', $jarvis_support_form_title);
                }
            }
        }
    }

    function jarvis_search_query($query)
    {
        global $woocommerce, $wp_query, $wpdb;

        if (!is_admin() && $query->is_main_query()) {

            $woocommerce_current_page_id = (version_compare($woocommerce->version, '2.1', '<')) ? woocommerce_get_page_id('shop') : wc_get_page_id('shop');

            if (is_shop() || ($query->is_page() && 'page' == get_option('show_on_front') && $query->get('page_id') == $woocommerce_current_page_id)) {

                $product_categories = (isset($_GET['sa_product_cat'])) ? sanitize_text_field(wp_unslash($_GET['sa_product_cat'])) : null;
                $product_tags = (isset($_GET['sa_product_tag'])) ? sanitize_text_field(wp_unslash($_GET['sa_product_tag'])) : null;

                if (isset($product_categories) || ($product_tags)) {
                    add_filter("woocommerce_is_filtered", 'woocommerce_is_filtered');
                }

                $tax_query = false;

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

                        if ( isset($_GET[$name]) && !empty($_GET[$name]) && taxonomy_exists($taxonomy)) {
                            add_filter("woocommerce_is_filtered", 'woocommerce_is_filtered');
                            $selected_attributes[$taxonomy]['terms'] = sanitize_text_field(wp_unslash($_GET[$name]));

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

                if ($tax_query) {
                    add_filter("woocommerce_page_title", array($this, "jarvis_title"));
                    $tax_query['relation'] = 'AND';
                    $query->set('tax_query', $tax_query);
                }

            }
        }
        return $query;
        ///
    }

    function woocommerce_is_filtered()
    {

        return true;
    }

    function jarvis_title($title)
    {

        return esc_html("Search Results:", "jarvis");
    }

    /**
     * Price Filter post filter
     */
    function jarvis_price_filter($filtered_posts)
    {
        global $wpdb;

        if (isset($_GET['sa_max_price']) && isset($_GET['sa_min_price'])) {

            add_filter("woocommerce_is_filtered", array($this, 'woocommerce_is_filtered'));

            $matched_products = array();
            $min = floatval(wp_unslash($_GET['sa_min_price']));
            $max = floatval(wp_unslash($_GET['sa_max_price']));

            $matched_products_query = $wpdb->get_results($wpdb->prepare("
	        	SELECT DISTINCT ID, post_parent, post_type FROM $wpdb->posts
				INNER JOIN $wpdb->postmeta ON ID = post_id
				WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = 'publish' AND meta_key = %s AND meta_value BETWEEN %d AND %d
			", '_price', $min, $max), OBJECT_K);

            if ($matched_products_query) {
                foreach ($matched_products_query as $product) {
                    if ($product->post_type == 'product')
                        $matched_products[] = $product->ID;
                    if ($product->post_parent > 0 && !in_array($product->post_parent, $matched_products))
                        $matched_products[] = $product->post_parent;
                }
            }

            // Filter the id's
            if (sizeof($filtered_posts) == 0) {
                $filtered_posts = $matched_products;
                $filtered_posts[] = 0;
            } else {
                $filtered_posts = array_intersect($filtered_posts, $matched_products);
                $filtered_posts[] = 0;
            }

        }
        return (array)$filtered_posts;
    }

    /**
     * Display Notifications on specific criteria.
     *
     * @since    2.14
     */
    public static function woocommerce_inactive_notice()
    {
        if (current_user_can('activate_plugins')) :
            if (!class_exists('WooCommerce')) :
                deactivate_plugins(plugin_basename(__FILE__));
                ?>
                <div id="message" class="error">
                    <p>
                        <?php
                        printf(
                            esc_html('%1$s JARVIS for WooCommerce REQUIRES WooCommerce %2$s %3$s WooCommerce %4$s must be active for JARVIS to work. Please install & activate WooCommerce.', 'jarvis'),
                            '<strong>',
                            '</strong><br>',
                            '<a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank" >',
                            '</a>'
                        );
                        ?>
                    </p>
                </div>
                <?php
            elseif (version_compare(get_option('woocommerce_db_version'), QC_JARVIS_REQUIRED_WOOCOMMERCE_VERSION, '<')) :
                ?>
                <div id="message" class="error">
                    <p>
                        <?php
                        printf(
                            esc_html('%1$s JARVIS for WooCommerce is inactive %2$s This version of JARVIS requires WooCommerce %3$s or newer. For more information about our WooCommerce version support %4$s click here %5$s.', 'jarvis'),
                            '<strong>',
                            '</strong><br>',
                            esc_attr(QC_JARVIS_REQUIRED_WOOCOMMERCE_VERSION)
                        );
                        ?>
                    </p>
                    <div style="clear:both;"></div>
                </div>
                <?php
            endif;
        endif;
    }

}

/**
 * Instantiate plugin.
 *
 */

if (!function_exists('init_jarvis')) {
    function init_jarvis()
    {

        global $wc_jarvis;

        $wc_jarvis = WC_Jarvis::get_instance();
    }
}
add_action('plugins_loaded', 'init_jarvis');


register_activation_hook(__FILE__, 'jarvis_insert_demo_content');
function jarvis_insert_demo_content()
{

    $demo_data = array(
        0 => array(
            'text'      => esc_html('Find Products In'),
            'filter'    => 'product_cat',
            'priceone'  => '',
            'pricetwo'  => '',
            'label'     => 'Category'
        ),
        1 => array(
            'text'      => esc_html('Between Price'),
            'filter'    => 'price',
            'priceone'  => '100',
            'pricetwo'  => '500',
            'label'     => ''
        )
    );
    update_option('jarvis_icon', esc_attr('icon-0.png'));

    if (get_option('jarvis-terms-fields') == '') {
        update_option('jarvis-terms-fields', maybe_serialize($demo_data));
    }
    if (get_option('jarvis_theme') == '') {
        update_option('jarvis_theme', esc_attr('theme-one.jpg'));
    }

    update_option('loop_notification', 1);
    update_option('jarvis_search', esc_attr('jarvis_search'));
    update_option('cart_products', esc_attr('cart_products'));
    update_option('recommended_products', '');
    update_option('recent_products', esc_attr('recent_products'));
    update_option('jarvis_form_animation', esc_attr('bounce'));

    // Default search result page when plugin installed/activated/updated
    update_option('jarvis_search_popup', 1);

    update_option('global_notification_delay_time', 5);
    update_option('global_fake_sale_delay_time', 5);
    update_option('jarvis_mode', 'quickball');
    update_option('global_widget_font_size', esc_attr('20'));
    update_option('disable_jarvis', 1);


    update_option('fake_sale_notification', esc_attr('fake_sale_notification'));
    update_option('fake_sale_notification_sound', esc_attr('fake_sale_notification_sound'));
    update_option('sell_notification_off_mobile', esc_attr('sell_notification_off_mobile'));


    update_option('position_x', 50);
    update_option('position_y', 50);
    update_option('message_one', esc_html( "Hi, I am JARVIS. What can I help you with today?", 'jarvis'));

    update_option('notification_delay_one', 2);
    //Ajax search message settings
    if (get_option('p_scs_msg') != '') {
        update_option('p_scs_msg', esc_html( 'Great! We have these products.', 'jarvis'));
    }
    if (get_option('p_fail_msg') != '') {
        update_option('p_fail_msg', esc_html( 'Oops! Nothing matches your exact criteria. How about selecting some different options?', 'jarvis'));
    }
    update_option('user_from_title', esc_html( 'From', 'jarvis'));
    update_option('user_purchased_a_title', esc_html( 'Purchased a', 'jarvis'));
    //Last solde products language settings
    update_option('last_sold_product_title', esc_html( 'Recently Sold Products', 'jarvis'));
    update_option('no_last_sold_product', esc_html( 'No recently sold products found.', 'jarvis'));
    update_option('last_sold_from', esc_html( 'from', 'jarvis'));
    update_option('last_sold_guest_from', esc_html( 'Guest from', 'jarvis'));
    update_option('last_sold_just_purchased', esc_html( 'just purchased', 'jarvis'));
    update_option('last_sold_purchased_a', esc_html( 'Purchased a', 'jarvis'));
    update_option('last_sold_time', esc_html( 'About 20 minutes  ago', 'jarvis'));
    //Cart language settenings
    update_option('cart_no_product_found', esc_html( 'You do not have any products in the cart', 'jarvis'));
    update_option('cart_total_price', esc_html( 'Total Price', 'jarvis'));
    update_option('cart_cart_link', esc_html( 'Cart', 'jarvis'));
    update_option('cart_checkout_link', esc_html( 'Checkout', 'jarvis'));
    // Recent view product language settings
    update_option('recently_viewed_add_to_cart', esc_html( 'Add to cart', 'jarvis'));
    update_option('recently_viewed_details', esc_html( 'View Detail', 'jarvis'));
    update_option('recently_viewed_no_product_founnd', esc_html( 'You have not viewed any products yet !', 'jarvis'));
    update_option('jarvis_support_form_title', esc_html( 'Questions or Comments? Contact Us', 'jarvis'));

    //Registrating uninstalling hook
    add_option('jarvis_plugin_do_activation_redirect', true);

}

add_action('admin_init', 'jarvis_plugin_redirect');
if ( ! function_exists( 'jarvis_plugin_redirect' ) ) {
    function jarvis_plugin_redirect(){

        $screen = get_current_screen();

        if( ( isset( $screen->base ) && $screen->base == 'plugins' ) && get_option('jarvis_plugin_do_activation_redirect', false) ) {
            delete_option('jarvis_plugin_do_activation_redirect');
            if(!isset($_GET['activate-multi'])){
                wp_redirect("admin.php?page=jarvis");
            }
        }
    }
}



//
add_action('wp_ajax_jarvis_delete_all_options_for_uninstall', 'jarvis_delete_all_options_for_uninstall');
add_action('wp_ajax_nopriv_jarvis_delete_all_options_for_uninstall', 'jarvis_delete_all_options_for_uninstall');
//Jarvis all option will be delete during uninstlling.
function jarvis_delete_all_options_for_uninstall()
{
    check_ajax_referer( 'jarvis', 'security');

    delete_option('jarvis_icon');
    delete_option('jarvis-terms-fields');
    delete_option('jarvis_theme');
    //delete_option('cart_products');
    delete_option('disable_jarvis');


    delete_option('position_x');
    delete_option('position_y');
    //delete_option('message_one');
    delete_option('p_scs_msg');
    delete_option('p_fail_msg');
    delete_option('user_from_title');
    delete_option('user_purchased_a_title');
    delete_option('last_sold_product_title');
    delete_option('no_last_sold_product');
    delete_option('last_sold_from');
    delete_option('last_sold_guest_from');
    delete_option('last_sold_just_purchased');
    delete_option('last_sold_purchased_a');
    delete_option('last_sold_time');
    delete_option('cart_no_product_found');
    delete_option('cart_total_price');
    delete_option('cart_cart_link');
    delete_option('cart_checkout_link');
    //delete_option('recently_viewed_add_to_cart');
    //delete_option('recently_viewed_details');
    //delete_option('recently_viewed_no_product_founnd');
    //delete_option('jarvis-search-button-text');
    delete_option('artificial_orders_val');
    delete_option('notification_delay_one');
    delete_option('notification_delay_two');
    delete_option('notification_delay_thre');
    delete_option('notification_delay_four');
    delete_option('notification_delay_five');
    delete_option('notification_delay_six');
    delete_option('notification_delay_seven');
    delete_option('notification_delay_eight');
    update_option('jarvis_mode', 'quickball');
    delete_option('disable_cart_item');
    delete_option('disable_jarvis_icon_animation');
//    delete_option('customer_name_one');
//    delete_option('customer_address_one');
//    delete_option('customer_name_two');
//    delete_option('customer_address_two');
//    delete_option('customer_name_three');
//    delete_option('customer_address_three');
    delete_option('recently_viewed_products_title');
    delete_option('assist_today_title');
    delete_option('your_basket_title');
    delete_option('recommended_products_title');
    delete_option('message_one');
    delete_option('message_two');
    delete_option('message_three');
    delete_option('message_four');
    delete_option('message_five');
    delete_option('message_six');
    delete_option('message_seven');
    delete_option('message_eight');
    delete_option('jarvis_form_animation');
    delete_option('cart_products');

    delete_option('loop_notification');
    delete_option('disable_notification');
    delete_option('recommended_products');
    //delete_option('jarvis_search_popup');
    delete_option('last_purchased_product');
    //delete_option('recent_products');
    delete_option('custom_quickball');
    delete_option('custom_quickball_link');
    delete_option('custom_quickball_icon');
    delete_option('global_notification_delay_time');
    delete_option('global_fake_sale_delay_time');
    delete_option('global_widget_font_size');
    delete_option('jarvis-recommended-products');
    delete_option('fake-product-one');
    delete_option('fake-product-two');
    delete_option('fake-product-three');
    delete_option('custom_global_css');
    delete_option('jarvis_support_form_title');


    $demo_data = array(
        0 => array(
            'text'      => esc_html( 'Find Products In', 'jarvis'),
            'filter'    => 'product_cat',
            'priceone'  => '',
            'pricetwo'  => '',
            'label'     => 'Category'
        ),
        1 => array(
            'text'      => esc_html( 'Between Price', 'jarvis'),
            'filter'    => 'price',
            'priceone'  => '100',
            'pricetwo'  => '500',
            'label'     => ''
        )
    );
    update_option('jarvis_icon', 'icon-0.png');

    if (get_option('jarvis-terms-fields') == '') {
        update_option('jarvis-terms-fields', maybe_serialize($demo_data));
    }
    if (get_option('jarvis_theme') == '') {
        update_option('jarvis_theme', 'theme-one.jpg');
    }

    update_option('loop_notification', 1);
    update_option('jarvis_search', 'jarvis_search');

    update_option('cart_products', 'cart_products');
    update_option('recommended_products', '');
    update_option('recent_products', 'recent_products');
    update_option('jarvis_form_animation', 'bounce');

    // Default search result page when plugin installed/activated/updated
    update_option('jarvis_search_popup', 1);

    update_option('global_notification_delay_time', 5);
    update_option('global_fake_sale_delay_time', 5);
    update_option('jarvis_mode', 'quickball');
    update_option('global_widget_font_size', '20');
    update_option('disable_jarvis', 1);


    update_option('fake_sale_notification', 'fake_sale_notification');
    update_option('fake_sale_notification_sound', 'fake_sale_notification_sound');
    update_option('sell_notification_off_mobile', 'sell_notification_off_mobile');


    update_option('position_x', 50);
    update_option('position_y', 50);
    update_option('message_one', esc_html( "Hi, I am JARVIS. What can I help you with today?", 'jarvis'));
    update_option('notification_delay_one', 2);
    //Ajax search message settings
    if (get_option('p_scs_msg') != '') {
        update_option('p_scs_msg', esc_html( 'Great! We have these products.', 'jarvis'));
    }
    if (get_option('p_fail_msg') != '') {
        update_option('p_fail_msg', esc_html( 'Oops! Nothing matches your exact criteria. How about selecting some different options?', 'jarvis'));
    }
    update_option('user_from_title', esc_html( 'From', 'jarvis'));
    update_option('user_purchased_a_title', esc_html( 'Purchased a', 'jarvis'));
    //Last solde products language settings
    update_option('last_sold_product_title', esc_html( 'Recently Sold Products', 'jarvis'));
    update_option('no_last_sold_product', esc_html( 'No recently sold products found.', 'jarvis'));
    update_option('last_sold_from', esc_html( 'from', 'jarvis'));
    update_option('last_sold_guest_from', esc_html( 'Guest from', 'jarvis'));
    update_option('last_sold_just_purchased', esc_html( 'just purchased', 'jarvis'));
    update_option('last_sold_purchased_a', esc_html( 'Purchased a', 'jarvis'));
    update_option('last_sold_time', esc_html( 'About 20 minutes  ago', 'jarvis'));
    //Cart language settenings
    update_option('cart_no_product_found', esc_html( 'You do not have any products in the cart', 'jarvis'));
    update_option('cart_total_price', esc_html( 'Total Price', 'jarvis'));
    update_option('cart_cart_link', esc_html( 'Cart', 'jarvis'));
    update_option('cart_checkout_link', esc_html( 'Checkout', 'jarvis'));
    // Recent view product language settings
    update_option('recently_viewed_add_to_cart', esc_html( 'Add to cart', 'jarvis'));
    update_option('recently_viewed_details', esc_html( 'View Detail', 'jarvis'));
    update_option('recently_viewed_no_product_founnd', esc_html( 'You have not viewed any products yet !', 'jarvis') );
    update_option('jarvis_support_form_title',  esc_html( 'Questions or Comments? Contact Us', 'jarvis') );


    $html = esc_html('Reset all options to default successfully.', 'jarvis');

    wp_send_json($html);
    wp_die();
}

/**
 *
 * deactive Plugin Feedback.
 *
 */
$wpbot_feedback = new Wp_jarvis_assistant_Usage_Feedback(
            __FILE__,
            'quantumcloud@gmail.com',
            false,
            true

        );


//add_action( 'admin_notices', 'qcopd_jarvis_pro_notice',100 );
function qcopd_jarvis_pro_notice(){
    global $pagenow, $typenow;
    ?>
    <div id="message" class="notice notice-info is-dismissible" style="padding:4px 0px 0px 4px;background:#C13825;">
        <?php
            printf(
                esc_html('%1$s  %2$s   %3$s', 'jarvis'),
                '<a href="'.esc_url('https://www.quantumcloud.com/products/woocommerce-shop-assistant-jarvis/').'" target="_blank">',
                '<img src="'.esc_url(QC_JARVIS_IMG_URL).'/4th-of-july.gif" >',
                '</a>'
            );
        ?>
    </div>
<?php

}
