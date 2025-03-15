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
        $this->api_url = $this->test_mode ? 'http://192.168.1.73:3000' : 'https://shopiwise.net';

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
        if ($this->debug_mode) {
            $this->log('Ödeme işlemi başlatılıyor. Sipariş ID: ' . $order_id);
        }
        
        $order = wc_get_order($order_id);
        
        // Sipariş verilerini hazırla
        $order_data = $this->prepare_order_data($order);
        
        if ($this->debug_mode) {
            $this->log('Sipariş verileri hazırlandı: ' . print_r($order_data, true));
        }
        
        // Shopiwise'a sipariş gönder
        $response = $this->create_shopiwise_order($order_data);
        
        if ($this->debug_mode) {
            $this->log('Shopiwise yanıtı: ' . print_r($response, true));
        }
        
        if ($response && isset($response['success']) && $response['success'] && isset($response['order_id'])) {
            if ($this->debug_mode) {
                $this->log('Sipariş başarıyla oluşturuldu. Shopiwise Sipariş ID: ' . $response['order_id']);
            }
            
            // Siparişi beklemede olarak işaretle
            $order->update_status('on-hold', __('Shopiwise ödeme bekleniyor.', 'shopiwise-payment'));
            
            // Sipariş notuna Shopiwise sipariş ID'sini ekle
            $order->add_order_note(sprintf(__('Shopiwise Sipariş ID: %s', 'shopiwise-payment'), $response['order_id']));
            
            // Sepeti boşalt
            WC()->cart->empty_cart();
            
            // Ödeme URL'sini oluştur
            $payment_url = $this->get_payment_url($response['order_id']);
            
            if ($this->debug_mode) {
                $this->log('Ödeme URL: ' . $payment_url);
            }
            
            // Shopiwise ödeme sayfasına yönlendir
            return array(
                'result'   => 'success',
                'redirect' => $payment_url
            );
        } else {
            // Hata mesajını logla
            if ($this->debug_mode) {
                $this->log('Ödeme işlemi başlatılamadı. Yanıt: ' . print_r($response, true));
            }
            
            // Hata mesajı göster
            $error_message = isset($response['message']) ? $response['message'] : __('Ödeme işlemi başlatılamadı. Lütfen tekrar deneyin.', 'shopiwise-payment');
            wc_add_notice($error_message, 'error');
            
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
            $product_data = array(
                'id'       => $product->get_id(),
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price'    => $order->get_item_total($item, false),
                'options'  => $this->get_item_options($item),
                'isGift'   => false,
                'image'    => 'https://avatar.vercel.sh/next.js',
                'totalPrice' => $order->get_item_total($item, false) * $item->get_quantity(),
                'type'     => 'physically'
            );
            
            // Ürün resmi ekle
            $image_id = $product->get_image_id();
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'medium');
                if ($image_url) {
                    $product_data['image'] = $image_url;
                }
            }
            
            $items[] = $product_data;
        }
        
        // Müşteri bilgilerini hazırla
        $customer_data = array(
            'firstName'    => $order->get_billing_first_name(),
            'lastName'     => $order->get_billing_last_name(),
            'email'        => $order->get_billing_email(),
            'phoneNumber'  => $order->get_billing_phone(),
            'country'      => strtolower($order->get_billing_country()),
            'city'         => $order->get_billing_city(),
            'district'     => $order->get_billing_state(),
            'neighborhood' => $order->get_billing_address_1(),
            'street'       => $order->get_billing_address_2(),
            'buildingNo'   => '',
            'apartmentNo'  => '',
            'addressDetails' => '',
            'zipCode'      => $order->get_billing_postcode(),
            'phoneAreaCode' => '',
            'isDigitalDelivery' => false,
            'emailForDelivery' => '',
            'paymentInfoCompleted' => true,
            'paymentInfoModal' => false,
            'noCreateAccount' => 'true',
            'language' => 'tr-TR'
        );
        
        // Sepet tutarlarını hazırla
        $subtotal = $order->get_subtotal();
        $total = $order->get_total();
        $discount = $order->get_discount_total();
        $shipping = $order->get_shipping_total();
        
        $basket_amount = array(
            'subTotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount > 0 ? $discount : 0,
            'total'    => $total,
            'amount'   => $subtotal + $shipping,
            'volume'   => 'volume1',
            'currency' => $order->get_currency(),
            'currentTotal' => round($total / 35, 1), // Total bölü 35
            'walletOff' => 0,
            'amountOff' => 0
        );
        
        // Sipariş verilerini hazırla
        $order_data = array(
            'store'         => $this->store_id,
            'trackId'       => $order->get_id(),
            'products'      => $items,
            'basketAmount'  => $basket_amount,
            'paymentMethod' => 'creditCard',
            'paymentStatus' => false,
            'installment'   => 1,
            'returnUrl'     => $this->get_return_url($order),
            'cancelUrl'     => $order->get_cancel_order_url(),
            'callbackUrl'   => WC()->api_request_url('WC_Shopiwise_Payment_Gateway'),
            'site'          => get_site_url(),
            'referer'       => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_site_url(),
            'nanoid'        => wp_generate_uuid4()
        );
        
        // Müşteri bilgilerini ekle
        $order_data = array_merge($order_data, $customer_data);
        
        if ($this->debug_mode) {
            $this->log('Hazırlanan sipariş verileri: ' . print_r($order_data, true));
        }
        
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
                    
                    // Benzersiz bir ID oluştur
                    $option_id = wp_generate_uuid4();
                    $title_id = wp_generate_uuid4();
                    
                    $option_name = $term ? $term->name : $value;
                    $option_title = wc_attribute_label($taxonomy);
                    
                    // Shopiwise formatında seçenek ekle
                    $options[$title_id] = array(
                        'id' => $option_id,
                        'name' => $option_name,
                        'title' => $option_title,
                        'price' => 0
                    );
                }
            }
        }
        
        // Özel seçenekleri ekle (örn: ürün eklentileri)
        $meta_data = $item->get_meta_data();
        if (!empty($meta_data)) {
            foreach ($meta_data as $meta) {
                if (!empty($meta->key) && !empty($meta->value) && substr($meta->key, 0, 1) !== '_') {
                    // Benzersiz bir ID oluştur
                    $option_id = wp_generate_uuid4();
                    $title_id = wp_generate_uuid4();
                    
                    // Shopiwise formatında seçenek ekle
                    $options[$title_id] = array(
                        'id' => $option_id,
                        'name' => $meta->value,
                        'title' => $meta->key,
                        'price' => 0
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
        $api_endpoint = $this->api_url . '/service/order';
        
        // İstek verilerini logla
        if ($this->debug_mode) {
            $this->log('Sipariş oluşturma isteği URL: ' . $api_endpoint);
            $this->log('Sipariş oluşturma isteği verileri: ' . print_r($order_data, true));
            $this->log('API Anahtarı: ' . substr($this->api_key, 0, 5) . '...' . substr($this->api_key, -5));
            $this->log('Mağaza ID: ' . $this->store_id);
        }
        
        $response = wp_remote_post(
            $api_endpoint,
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
                $this->log('Hata kodu: ' . $response->get_error_code());
            }
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($this->debug_mode) {
            $this->log('Sipariş oluşturma yanıtı HTTP kodu: ' . $status_code);
            $this->log('Sipariş oluşturma yanıtı: ' . print_r($body, true));
            $this->log('Tam yanıt: ' . print_r($response, true));
        }
        
        // HTTP durum kodu 200 değilse hata logla
        if ($status_code !== 200) {
            if ($this->debug_mode) {
                $this->log('Sipariş oluşturma başarısız. HTTP kodu: ' . $status_code);
                $this->log('Yanıt başlıkları: ' . print_r(wp_remote_retrieve_headers($response), true));
            }
        }
        
        return $body;
    }

    /**
     * Ödeme URL'sini oluştur
     */
    private function get_payment_url($order_id) {
        if ($this->debug_mode) {
            $this->log('Ödeme URL oluşturuluyor. Shopiwise Sipariş ID: ' . $order_id);
        }
        
        // API URL'sinden domain kısmını al
        $domain = preg_replace('/\/api$/', '', $this->api_url);
        $payment_url = $domain . '/pay/' . $order_id . '?sid=' . $this->store_id;
        
        if ($this->debug_mode) {
            $this->log('Oluşturulan ödeme URL: ' . $payment_url);
        }
        
        return $payment_url;
    }

    /**
     * Ödeme sonucu kontrolü
     */
    public function check_response() {
        if ($this->debug_mode) {
            $this->log('Callback yanıtı alındı. POST verileri: ' . print_r($_POST, true));
            $this->log('GET verileri: ' . print_r($_GET, true));
            $this->log('Tüm istek başlıkları: ' . print_r(getallheaders(), true));
        }
        
        $data = $_POST;
        
        if (empty($data)) {
            $json_data = file_get_contents('php://input');
            if (!empty($json_data)) {
                $data = json_decode($json_data, true);
                if ($this->debug_mode) {
                    $this->log('JSON verisi alındı: ' . $json_data);
                    $this->log('Çözümlenmiş JSON verisi: ' . print_r($data, true));
                }
            }
        }
        
        if (isset($data['order_id']) && isset($data['status'])) {
            $order_id = $data['order_id'];
            $status = $data['status'];
            
            if ($this->debug_mode) {
                $this->log('Sipariş ID: ' . $order_id . ', Durum: ' . $status);
            }
            
            $order = wc_get_order($order_id);
            
            if (!$order) {
                if ($this->debug_mode) {
                    $this->log('Sipariş bulunamadı: ' . $order_id);
                }
                echo json_encode(['success' => false, 'message' => 'Sipariş bulunamadı']);
                exit;
            }
            
            if ($status === 'SUCCESS') {
                // Ödeme başarılı
                if ($this->debug_mode) {
                    $this->log('Ödeme başarılı. Sipariş tamamlanıyor: ' . $order_id);
                }
                
                $order->payment_complete();
                $order->add_order_note(__('Shopiwise ödeme başarılı.', 'shopiwise-payment'));
                
                // Başarılı ödeme sonrası yönlendirme
                $redirect_url = $this->get_return_url($order);
                
                if ($this->debug_mode) {
                    $this->log('Başarılı ödeme sonrası yönlendirme URL: ' . $redirect_url);
                }
                
                echo json_encode(['success' => true, 'message' => 'Ödeme başarılı']);
                
                wp_redirect($redirect_url);
                exit;
            } else {
                // Ödeme başarısız
                if ($this->debug_mode) {
                    $this->log('Ödeme başarısız. Durum: ' . $status . ', Sipariş ID: ' . $order_id);
                    if (isset($data['error_message'])) {
                        $this->log('Hata mesajı: ' . $data['error_message']);
                    }
                }
                
                $order->update_status('failed', __('Shopiwise ödeme başarısız.', 'shopiwise-payment'));
                
                // Başarısız ödeme sonrası yönlendirme
                $redirect_url = wc_get_checkout_url();
                
                if ($this->debug_mode) {
                    $this->log('Başarısız ödeme sonrası yönlendirme URL: ' . $redirect_url);
                }
                
                echo json_encode(['success' => false, 'message' => 'Ödeme başarısız']);
                
                wp_redirect($redirect_url);
                exit;
            }
        }
        
        exit;
    }

    /**
     * Log mesajı
     */
    private function log($message) {
        if ($this->debug_mode) {
            if (!is_string($message)) {
                $message = print_r($message, true);
            }
            
            $logger = wc_get_logger();
            $context = array('source' => 'shopiwise-payment');
            
            // Tarih ve saat ekle
            $timestamp = date('Y-m-d H:i:s');
            $formatted_message = "[{$timestamp}] {$message}";
            
            $logger->debug($formatted_message, $context);
            
            // Ayrıca özel log dosyasına da yaz
            $log_dir = WP_CONTENT_DIR . '/shopiwise-logs';
            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);
            }
            
            $log_file = $log_dir . '/shopiwise-payment-' . date('Y-m-d') . '.log';
            error_log($formatted_message . "\n", 3, $log_file);
        }
    }
} 