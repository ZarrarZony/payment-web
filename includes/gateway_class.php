<?php
class Paymentsweb_Gateway extends WC_Payment_Gateway {
    public $paymentsweb_id = '';
    private $api_url       = '';

    function __construct() {

            $this->id                   = $this->paymentsweb_id;
            $this->icon                 = plugins_url( '../assets/visa_master_small.png', plugin_basename( __FILE__ ) );
            $this->method_title         = '';
            $this->method_description   = '';
            $this->has_fields           = false;
            $this->supports             = array('products' );
            $this->init_form_fields();
            $this->init_settings();
    }

    public function paymentsweb_register_gateway( $methods )
    {
        $methods[] = 'Paymentsweb_Gateway';
        return $methods;
    }

    public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'         => __('Enable/Disable:', $this->paymentsweb_id),
                'type'          => 'checkbox',
                'label'         => __('Pay By Payments Web', $this->paymentsweb_id),
                'default'       => 'no',
                'description'   => 'Show in the Payment List as a payment option'
            )
        );

    }

    public function process_payment($order_id){
        $order = wc_get_order( $order_id );
        preg_match('/^(?:https?:\/\/)?(?:www\.)?([\w\-.]+)/', get_home_url(), $matches);
        $website_source = $matches[1];
        $data = [
            "order_id"         => $order_id,
            "website_source"   => $website_source,
            "profile"          => $this->settings['profile']
        ];
        $response = wp_remote_post( $this->api_url, [
            "headers" => array('Content-Type' => 'application/json'),
            "body"    => json_encode($data),
            "method"  => "POST",
        ] );

        if ( is_wp_error( $response ) ) {
           $error_message = $response->get_error_message();
           wc_add_notice('Something went wrong: '.$error_message, 'error' );
           return false;
        } else {
           $status = $response['response']['code'];
           $body   = json_decode($response['body'],true);
           update_post_meta( $order->id, '_iframe_url',$body['d']['deposit_url'] );
           $checkout_payment_url = $order->get_checkout_payment_url( true );
           if($status === 200 && $body['d']['success']) :
               return array(
                    'result' => 'success',
                    'redirect' => $checkout_payment_url
                ); 
            else : 
               wc_add_notice($body['d']['description'], 'error' );
               return false; 
            endif;
        }
    }
}