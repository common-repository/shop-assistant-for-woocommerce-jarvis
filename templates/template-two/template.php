<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {  exit; }
?>

<div id="genie-target" class="jarvis-mode-<?php echo esc_attr(get_option('jarvis_mode'));?>">
    <div id="jarvis_body">
        <button id="btn-close-modal" value="x" class="jarvis_close close-genie-target">x</button>
        <div class="qc-row">
            <div class="qc-col-md-4">
                <?php echo do_shortcode("[jarvis-recently-viewed-products]")  ;?>
            </div>
            <div class="qc-col-md-4">
                <?php echo do_shortcode("[qc_jarvis]")  ;?>
            </div>
            <div class="qc-col-md-4">
                <?php echo do_shortcode("[jarvis-cart-products]")  ;?>
            </div>
        </div>
        <div class="qc-row">
            <div class="qc-col-md-12">
                <div id="jarvis_ajax_search_products"></div>
            </div>
        </div>
    </div>
</div>