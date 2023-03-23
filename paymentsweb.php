<?php
/*
Plugin Name: Payment plugin
Description: Payment plugin
Version: 1.x.x
Author: zony
Author URI:  https://linkedin.com/in/muhammadzarrar
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//check for update
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    '',
    __FILE__,
    ''
);
$myUpdateChecker->setAuthentication('');
$myUpdateChecker->setBranch('master');


add_action( 'plugins_loaded', function(){
    $instance = new PaymentsWeb;
    $instance->init();
});

class PaymentsWeb{
    public $plugin_unique_id = '....';
    public $plugin_settings  = '';
    private static $instance = null;

    private function __construct() {
    }
 
    public static function getInstance() {
       if (self::$instance == null) {
          self::$instance = new Sample();
       }
       return self::$instance;
    }

    private function init(){
        $this->paymentsweb_define_constants();
        if ( class_exists( 'WC_Payment_Gateway' ) ) :
            $this->paymentsweb_includes();
            $this->plugin_settings = get_option("woocommerce_woo_paymentsweb_settings");
            
            add_filter( 'plugin_action_links_' . paymentsweb_BASENAME,array($this,'paymentsweb_plugin_setting'));
            add_filter( 'http_request_timeout', array($this,'paymentsweb_timeout_extend' ));
            add_action('woocommerce_receipt_woo_paymentsweb', array($this, 'receipt_page'));

            if(isset($this->plugin_settings['show_profile']) && $this->plugin_settings['show_profile']=="yes") :
            //    removed
            endif;

        else : 
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice_for_paymentsweb' ) );
        endif;
    }

    private function paymentsweb_define_constants()
        {
            $this->paymentsweb_define( 'paymentsweb_plugin_url', plugin_dir_url( __FILE__ ) );
            $this->paymentsweb_define( 'paymentsweb_ABSPATH', dirname( __FILE__ ) );
            $this->paymentsweb_define( 'paymentsweb_BASENAME', plugin_basename( __FILE__ ) );
        }

    public function woocommerce_missing_notice_for_paymentsweb()
        {
            echo '<div class="error woocommerce-message wc-connect"><p>' . sprintf( __( 'Sorry, <strong>paymentsweb plugin</strong> requires WooCommerce to be installed and activated first' ) ) . '</p></div>';
        }

    public function paymentsweb_timeout_extend($time){
        return 60;
    }

    public function receipt_page($order_id){
      //removed
    }

    public function profile_field_display_admin_meta($order){
        echo '<div class="address"><p><strong>'.__('Profile').':</strong> ' . get_post_meta( $order->get_id(), 'profile', true ) . '</p></div><div class="edit_address"></div>';
    }

    public function profile_field_save( $post_id, $post ){
        update_post_meta( $post_id, 'profile', wc_clean( $_POST[ 'profile' ] ) );
    }

    public function profile_field_display_front( $checkout ) {
        woocommerce_form_field( 'profile', array(
            'type'          => 'text',
            'class'         => array('my-field-class form-row-wide' ),
            'label'         => __('Profile', $this->plugin_unique_id ),
            'placeholder'   => __(''),
            ), $this->plugin_settings['profile'] );
    }

    public function profile_field_checkout_update( $order_id ) {
        if ( ! empty( $_POST['profile'] ) ) {
            update_post_meta( $order_id, 'profile', sanitize_text_field( $_POST['profile'] ) );
        }
    }

}
