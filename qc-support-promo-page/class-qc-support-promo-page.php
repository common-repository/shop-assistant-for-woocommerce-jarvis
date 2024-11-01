<?php
/*
* QuantumCloud Promo + Support Page
* Revised On: 18-10-2023
*/

if ( ! defined( 'qc_jarvis_support_path' ) ) {
    define('qc_jarvis_support_path', plugin_dir_path(__FILE__));
}

if ( ! defined( 'qc_jarvis_support_url' ) )
    define('qc_jarvis_support_url', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'qc_jarvis_img_url' ) )
    define('qc_jarvis_img_url', qc_jarvis_support_url . "/images" );


/*Callback function to add the menu */
function qc_jarvis_show_promo_page_callback_func(){

    add_submenu_page(
        "jarvis",
        esc_html__('More WordPress Goodies for You!', 'jarvis'),
        esc_html__('Support', 'jarvis'),
        'manage_options',
        "qc_jarvis_supports",
        'qc_jarvis_promo_support_page_callback_func'
    );
    
} //show_promo_page_callback_func

add_action( 'admin_menu', 'qc_jarvis_show_promo_page_callback_func', 99 );


/*******************************
 * Main Class to Display Support
 * form and the promo pages
 *******************************/

if ( ! function_exists( 'qc_jarvis_include_promo_page_scripts' ) ) {	
	function qc_jarvis_include_promo_page_scripts( ) {   


        if( isset($_GET["page"]) && !empty($_GET["page"]) && (   $_GET["page"] == "qc_jarvis_supports"  ) ){

            wp_enqueue_style( 'qcld-support-fontawesome-css', qc_jarvis_support_url . "css/font-awesome.min.css",  '', QC_JARVIS_VERSION, 'screen');                              
            wp_enqueue_style( 'qcld-support-style-css', qc_jarvis_support_url . "css/style.css",  '', QC_JARVIS_VERSION, 'screen');

            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-core');
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-jarvis-form-processor', qc_jarvis_support_url . 'js/support-form-script.js',  array('jquery', 'jquery-ui-core','jquery-ui-tabs'), QC_JARVIS_VERSION, true );

            wp_add_inline_script( 'jquery-jarvis-form-processor', 
                                    'var qc_jarvis_ajaxurl    = "' . admin_url('admin-ajax.php') . '";
                                    var qc_jarvis_ajax_nonce  = "'. wp_create_nonce( 'jarvis' ).'";   
                                ', 'before');
            
        }
	   
	}
	add_action('admin_enqueue_scripts', 'qc_jarvis_include_promo_page_scripts');
	
}
		
/*******************************
 * Callback function to show the HTML
 *******************************/

include_once qc_jarvis_support_path . '/qc-clr-recommendbot-support-plugin.php';

if ( ! function_exists( 'qc_jarvis_promo_support_page_callback_func' ) ) {

	function qc_jarvis_promo_support_page_callback_func() {
		
?>


        <div class="qc-jarvis-support qcld-support-new-page">
            <div class="support-btn-main justify-content-center">
                <div class="col text-center">
                    <h2 class="py-3"><?php esc_html_e('Check Out Some of Our Other Works that Might Make Your Website Better', 'jarvis'); ?></h2>
                    <h5><?php esc_html_e('All our Pro Version users get Premium, Guaranteed Quick, One on One Priority Support.', 'jarvis'); ?></h5>
                    <div class="support-btn">
                        <a class="premium-support" href="<?php echo esc_url('https://qc.ticksy.com/'); ?>" target="_blank"><?php esc_html_e('Get Priority Support ', 'jarvis'); ?></a>
                        <a style="width:282px" class="premium-support" href="<?php echo esc_url('https://www.quantumcloud.com/resources/kb-sections/comment-tools/'); ?>" target="_blank"><?php esc_html_e('Online KnowledgeBase', 'jarvis'); ?></a>
                    </div>
                </div>
            
                <div class="qc-column-12" >
                    <div class="support-btn">
                        
                        <a class="premium-support premium-support-free" href="<?php echo esc_url('https://www.quantumcloud.com/resources/free-support/','jarvis') ?>" target="_blank"><?php esc_html_e('Get Support for Free Version','jarvis') ?></a>
                    </div>
                </div>
            </div>
            
            <div class="qcld-plugins-lists">
                <div class="qcld-plugins-loading">
                    <img src="<?php echo esc_url(qc_jarvis_img_url); ?>/loading.gif" alt="loading">
                </div>
            </div>
        </div>
			
		
<?php
            
       
    }
}


/*******************************
 * Handle Ajex Request for Form Processing
 *******************************/
add_action( 'wp_ajax_qc_jarvis_process_qc_promo_form', 'qc_jarvis_process_qc_promo_form' );

if( !function_exists('qc_jarvis_process_qc_promo_form') ){

    function qc_jarvis_process_qc_promo_form(){

        check_ajax_referer( 'jarvis', 'security');
        
        $data['status']   = 'failed';
        $data['message']  = esc_html__('Problem in processing your form submission request! Apologies for the inconveniences.<br> 
Please email to <span style="color:#22A0C9;font-weight:bold !important;font-size:14px "> quantumcloud@gmail.com </span> with any feedback. We will get back to you right away!', 'jarvis');

        $name         = isset($_POST['post_name']) ? trim(sanitize_text_field(wp_unslash($_POST['post_name']))) : '';
        $email        = isset($_POST['post_email']) ? trim(sanitize_email(wp_unslash($_POST['post_email']))) : '';
        $subject      = isset($_POST['post_subject']) ? trim(sanitize_text_field(wp_unslash($_POST['post_subject']))) : '';
        $message      = isset($_POST['post_message']) ? trim(sanitize_text_field(wp_unslash($_POST['post_message']))) : '';
        $plugin_name  = isset($_POST['post_plugin_name']) ? trim(sanitize_text_field(wp_unslash($_POST['post_plugin_name']))) : '';

        if( $name == "" || $email == "" || $subject == "" || $message == "" )
        {
            $data['message'] = esc_html('Please fill up all the requried form fields.', 'jarvis');
        }
        else if ( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) 
        {
            $data['message'] = esc_html('Invalid email address.', 'jarvis');
        }
        else
        {

            //build email body

            $bodyContent = "";
                
            $bodyContent .= "<p><strong>".esc_html('Support Request Details:', 'jarvis')."</strong></p><hr>";

            $bodyContent .= "<p>".esc_html('Name', 'jarvis')." : ".$name."</p>";
            $bodyContent .= "<p>".esc_html('Email', 'jarvis')." : ".$email."</p>";
            $bodyContent .= "<p>".esc_html('Subject', 'jarvis')." : ".$subject."</p>";
            $bodyContent .= "<p>".esc_html('Message', 'jarvis')." : ".$message."</p>";

            $bodyContent .= "<p>".esc_html('Sent Via the Plugin', 'jarvis')." : ".$plugin_name."</p>";

            $bodyContent .="<p></p><p>".esc_html('Mail sent from:', 'jarvis')." <strong>".get_bloginfo('name')."</strong>, ".esc_html('URL:', 'jarvis')." [".get_bloginfo('url')."].</p>";
            $bodyContent .="<p>".esc_html('Mail Generated on:', 'jarvis')." " . gmdate("F j, Y, g:i a") . "</p>";           
            
            $toEmail = "quantumcloud@gmail.com"; //Receivers email address
            //$toEmail = "qc.kadir@gmail.com"; //Receivers email address

            //Extract Domain
            $url = get_site_url();
            $url = wp_parse_url($url);
            $domain = $url['host'];
            

            $fakeFromEmailAddress = "wordpress@" . $domain;
            
            $to = $toEmail;
            $body = $bodyContent;
            $headers = array();
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'From: '.esc_attr($name, 'jarvis').' <'.esc_attr($fakeFromEmailAddress, 'jarvis').'>';
            $headers[] = 'Reply-To: '.esc_attr($name, 'jarvis').' <'.esc_attr($email, 'jarvis').'>';

            $finalSubject = esc_html('From Plugin Support Page:', 'jarvis')." " . esc_attr($subject, 'jarvis');
            
            $result = wp_mail( $to, $finalSubject, $body, $headers );

            if( $result )
            {
                $data['status'] = 'success';
                $data['message'] = esc_html__('Your email was sent successfully. Thanks!', 'jarvis');
            }

        }

        ob_clean();

        
        echo wp_json_encode($data);
    
        die();
    }
}