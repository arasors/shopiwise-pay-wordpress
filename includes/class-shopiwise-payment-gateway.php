<?php
/**
 * Shopiwise Payment Gateway
 *
 * WooCommerce için Shopiwise ödeme geçidi sınıfı
 */

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

/**
 * WC_Shopiwise_Payment_Gateway Class
 */
class WC_Shopiwise_Payment_Gateway extends WC_Payment_Gateway {

    /**
     * API URL
     *
     * @var string
     */
    private $api_url;

    /**
     * Test modu
     *
     * @var bool
     */
    private $test_mode;

    /**
     * API anahtarı
     *
     * @var string
     */
    private $api_key;

    /**
     * Mağaza ID
     *
     * @var string
     */
    private $store_id;

    /**
     * Debug modu
     *
     * @var bool
     */
    private $debug_mode;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'shopiwise_payment';
        $this->icon               = SHOPIWISE_PAYMENT_PLUGIN_URL . 'assets/images/shopiwise-logo.png';
        $this->has_fields         = false;
        $this->method_title       = __('Shopiwise Ödeme', 'shopiwise-payment');
        $this->method_description = __('Shopiwise ödeme geçidi ile ödemeleri güvenle alın.', 'shopiwise-payment');
        $this->supports           = array(
            'products',
            'refunds'
        );

        // Ayarları yükle
        $this->init_form_fields();
        $this->init_settings();

        // Ayarları tanımla
        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->enabled            = $this->get_option('enabled');
        $this->test_mode          = 'yes' === $this->get_option('test_mode');
        $this->debug_mode         = 'yes' === $this->get_option('debug_mode');
        $this->api_key            = $this->get_option('api_key');
        $this->store_id           = $this->get_option('store_id');
        
        // API URL'sini ayarla
        $this->api_url = $this->test_mode ? 'https://test-api.shopiwise.net' : 'https://api.shopiwise.net';

        // Ayarları kaydetme hook'u
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        
        // Ödeme sayfası hook'u
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        
        // Callback URL'leri
        add_action('woocommerce_api_wc_shopiwise_payment_gateway', array($this, 'check_response'));
    }

    /**
     * Form alanlarını başlat
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Etkinleştir/Devre Dışı Bırak', 'shopiwise-payment'),
                'type'        => 'checkbox',
                'label'       => __('Shopiwise Ödeme Geçidini Etkinleştir', 'shopiwise-payment'),
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => __('Başlık', 'shopiwise-payment'),
                'type'        => 'text',
                'description' => __('Ödeme sırasında müşteriye gösterilecek başlık.', 'shopiwise-payment'),
                'default'     => __('Kredi Kartı ile Öde', 'shopiwise-payment'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Açıklama', 'shopiwise-payment'),
                'type'        => 'textarea',
                'description' => __('Ödeme sırasında müşteriye gösterilecek açıklama.', 'shopiwise-payment'),
                'default'     => __('Kredi kartı ile güvenli ödeme yapın.', 'shopiwise-payment'),
                'desc_tip'    => true,
            ),
            'api_key' => array(
                'title'       => __('API Anahtarı', 'shopiwise-payment'),
                'type'        => 'text',
                'description' => __('Shopiwise hesabınızdan aldığınız API anahtarı.', 'shopiwise-payment'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'store_id' => array(
                'title'       => __('Mağaza ID', 'shopiwise-payment'),
                'type'        => 'text',
                'description' => __('Shopiwise hesabınızdaki mağaza ID.', 'shopiwise-payment'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_mode' => array(
                'title'       => __('Test Modu', 'shopiwise-payment'),
                'type'        => 'checkbox',
                'label'       => __('Test modunu etkinleştir', 'shopiwise-payment'),
                'default'     => 'yes',
                'description' => __('Test modunda gerçek ödeme alınmaz, sadece test amaçlı kullanılır.', 'shopiwise-payment'),
            ),
            'debug_mode' => array(
                'title'       => __('Debug Modu', 'shopiwise-payment'),
                'type'        => 'checkbox',
                'label'       => __('Debug modunu etkinleştir', 'shopiwise-payment'),
                'default'     => 'no',
                'description' => __('Debug modunda işlem logları kaydedilir.', 'shopiwise-payment'),
            ),
            'api_info' => array(
                'title'       => __('API Bilgileri', 'shopiwise-payment'),
                'type'        => 'title',
                'description' => __('API anahtarınızı ve Mağaza ID\'nizi Shopiwise hesabınızdan alabilirsiniz. <a href="https://shopiwise.net/m/store-manager/advanced" target="_blank">Shopiwise Mağaza Yönetimi</a> sayfasından API Keys bölümünden yeni bir API anahtarı oluşturabilirsiniz.', 'shopiwise-payment'),
            ),
        );
    }

    /**
     * Ödeme işlemi
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        // Sipariş verilerini hazırla
        $order_data = $this->prepare_order_data($order);
        
        // Shopiwise'a sipariş gönder
        $response = $this->create_shopiwise_order($order_data);
        
        if ($response && isset($response['success']) && $response['success'] && isset($response['order_id'])) {
            // Siparişi beklemede olarak işaretle
            $order->update_status('on-hold', __('Shopiwise ödeme bekleniyor.', 'shopiwise-payment'));
            
            // Sepeti boşalt
            WC()->cart->empty_cart();
            
            // Shopiwise ödeme sayfasına yönlendir
            return array(
                'result'   => 'success',
                'redirect' => $this->get_payment_url($response['order_id'])
            );
        } else {
            // Hata mesajı göster
            wc_add_notice(__('Ödeme işlemi başlatılamadı. Lütfen tekrar deneyin.', 'shopiwise-payment'), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => ''
            );
        }
    }

    /**
     * Sipariş verilerini hazırla
     */
    private function prepare_order_data($order) {
        $items = array();
        
        // Ürünleri hazırla
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = array(
                'id'       => $product->get_id(),
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price'    => $order->get_item_total($item, false),
                'options'  => $this->get_item_options($item)
            );
        }
        
        // Müşteri bilgilerini hazırla
        $customer_data = array(
            'firstName'    => $order->get_billing_first_name(),
            'lastName'     => $order->get_billing_last_name(),
            'email'        => $order->get_billing_email(),
            'phoneNumber'  => $order->get_billing_phone(),
            'country'      => $order->get_billing_country(),
            'city'         => $order->get_billing_city(),
            'district'     => $order->get_billing_state(),
            'neighborhood' => $order->get_billing_address_1(),
            'street'       => $order->get_billing_address_2(),
            'buildingNo'   => '',
            'zipCode'      => $order->get_billing_postcode(),
        );
        
        // Sepet tutarlarını hazırla
        $basket_amount = array(
            'subTotal' => $order->get_subtotal(),
            'shipping' => $order->get_shipping_total(),
            'discount' => $order->get_discount_total(),
            'total'    => $order->get_total(),
            'currency' => $order->get_currency()
        );
        
        // Sipariş verilerini hazırla
        $order_data = array(
            'store'         => $this->store_id,
            'trackId'       => $order->get_id(),
            'products'      => $items,
            'basketAmount'  => $basket_amount,
            'paymentMethod' => 'creditCard',
            'returnUrl'     => $this->get_return_url($order),
            'cancelUrl'     => $order->get_cancel_order_url(),
            'callbackUrl'   => WC()->api_request_url('WC_Shopiwise_Payment_Gateway'),
        );
        
        // Müşteri bilgilerini ekle
        $order_data = array_merge($order_data, $customer_data);
        
        return $order_data;
    }

    /**
     * Ürün seçeneklerini al
     */
    private function get_item_options($item) {
        $options = array();
        
        // Varyasyon bilgilerini al
        if ($item->get_variation_id()) {
            $variation = wc_get_product($item->get_variation_id());
            if ($variation) {
                foreach ($variation->get_attributes() as $attribute => $value) {
                    $taxonomy = str_replace('attribute_', '', $attribute);
                    $term = get_term_by('slug', $value, $taxonomy);
                    $options[] = array(
                        'title' => wc_attribute_label($taxonomy),
                        'name'  => $term ? $term->name : $value
                    );
                }
            }
        }
        
        return $options;
    }

    /**
     * Shopiwise'a sipariş gönder
     */
    private function create_shopiwise_order($order_data) {
        $response = wp_remote_post(
            $this->api_url . '/api/service/order',
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'X-Source'      => 'wordpress'
                ),
                'body'        => json_encode($order_data),
                'cookies'     => array()
            )
        );
        
        if (is_wp_error($response)) {
            if ($this->debug_mode) {
                $this->log('Sipariş oluşturma hatası: ' . $response->get_error_message());
            }
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($this->debug_mode) {
            $this->log('Sipariş oluşturma yanıtı: ' . print_r($body, true));
        }
        
        return $body;
    }

    /**
     * Shopiwise ödeme URL'sini al
     */
    private function get_payment_url($order_id) {
        return $this->api_url . '/pay/' . $order_id . '?sid=' . $this->store_id;
    }

    /**
     * Ödeme sonucu kontrolü
     */
    public function check_response() {
        $data = $_POST;
        
        if ($this->debug_mode) {
            $this->log('Callback yanıtı: ' . print_r($data, true));
        }
        
        if (isset($data['order_id']) && isset($data['status'])) {
            $order_id = $data['order_id'];
            $status = $data['status'];
            $order = wc_get_order($order_id);
            
            if (!$order) {
                if ($this->debug_mode) {
                    $this->log('Sipariş bulunamadı: ' . $order_id);
                }
                exit;
            }
            
            if ($status === 'SUCCESS') {
                // Ödeme başarılı
                $order->payment_complete();
                $order->add_order_note(__('Shopiwise ödeme başarılı.', 'shopiwise-payment'));
                
                // Başarılı ödeme sonrası yönlendirme
                $redirect_url = $this->get_return_url($order);
                wp_redirect($redirect_url);
                exit;
            } else {
                // Ödeme başarısız
                $order->update_status('failed', __('Shopiwise ödeme başarısız.', 'shopiwise-payment'));
                
                // Başarısız ödeme sonrası yönlendirme
                $redirect_url = $order->get_cancel_order_url();
                wp_redirect($redirect_url);
                exit;
            }
        }
        
        exit;
    }

    /**
     * Log kaydet
     */
    private function log($message) {
        if ($this->debug_mode) {
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'shopiwise-payment'));
        }
    }
} 