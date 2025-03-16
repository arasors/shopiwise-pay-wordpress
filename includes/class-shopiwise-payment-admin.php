<?php
/**
 * Shopiwise Payment Admin
 *
 * Shopiwise ödeme geçidi admin ayarları
 */

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

/**
 * WC_Shopiwise_Payment_Admin Class
 */
class WC_Shopiwise_Payment_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Admin menüsüne ekle
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin bildirimleri
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Admin scriptleri
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX işlemleri
        add_action('wp_ajax_shopiwise_test_connection', array($this, 'test_connection'));
    }

    /**
     * Admin menüsüne ekle
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Shopiwise Ödeme', 'shopiwise-payment'),
            __('Shopiwise Ödeme', 'shopiwise-payment'),
            'manage_woocommerce',
            'shopiwise-payment',
            array($this, 'admin_page')
        );
    }

    /**
     * Admin sayfası
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Shopiwise Ödeme Geçidi Ayarları', 'shopiwise-payment'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Bağlantı Durumu', 'shopiwise-payment'); ?></h2>
                <p><?php echo esc_html__('Shopiwise API bağlantınızı test edin.', 'shopiwise-payment'); ?></p>
                <button id="shopiwise-test-connection" class="button button-primary">
                    <?php echo esc_html__('Bağlantıyı Test Et', 'shopiwise-payment'); ?>
                </button>
                <div id="shopiwise-connection-result" style="margin-top: 10px;"></div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php echo esc_html__('Ödeme Geçidi Ayarları', 'shopiwise-payment'); ?></h2>
                <p><?php echo esc_html__('Ödeme geçidi ayarlarını WooCommerce ayarlarından yapabilirsiniz.', 'shopiwise-payment'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=shopiwise_payment')); ?>" class="button button-secondary">
                    <?php echo esc_html__('Ayarlara Git', 'shopiwise-payment'); ?>
                </a>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php echo esc_html__('Yardım ve Destek', 'shopiwise-payment'); ?></h2>
                <p><?php echo esc_html__('Shopiwise ödeme geçidi hakkında yardım ve destek almak için aşağıdaki bağlantıları kullanabilirsiniz.', 'shopiwise-payment'); ?></p>
                <ul>
                    <li><a href="https://github.com/arasors/shopiwise-pay-wordpress" target="_blank"><?php echo esc_html__('Dokümantasyon', 'shopiwise-payment'); ?></a></li>
                    <li><a href="https://shopiwise.net/cr/help" target="_blank"><?php echo esc_html__('Destek', 'shopiwise-payment'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Admin bildirimleri
     */
    public function admin_notices() {
        $settings = get_option('woocommerce_shopiwise_payment_settings');
        
        if (!$settings || empty($settings['api_key']) || empty($settings['store_id'])) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php
                    echo sprintf(
                        __('Shopiwise Ödeme Geçidi için API anahtarı ve Mağaza ID ayarlarını yapmanız gerekiyor. <a href="%s">Ayarlar sayfasına git</a>.', 'shopiwise-payment'),
                        esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=shopiwise_payment'))
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Admin scriptleri
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_shopiwise-payment' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'shopiwise-admin',
            SHOPIWISE_PAYMENT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SHOPIWISE_PAYMENT_VERSION,
            true
        );
        
        wp_localize_script(
            'shopiwise-admin',
            'shopiwiseAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shopiwise-admin-nonce'),
                'testing_connection' => __('Bağlantı test ediliyor...', 'shopiwise-payment'),
                'connection_success' => __('Bağlantı başarılı!', 'shopiwise-payment'),
                'connection_error' => __('Bağlantı hatası:', 'shopiwise-payment')
            )
        );
    }

    /**
     * API bağlantısını test et
     */
    public function test_connection() {
        check_ajax_referer('shopiwise-admin-nonce', 'nonce');
        
        $settings = get_option('woocommerce_shopiwise_payment_settings');
        
        if (!$settings || empty($settings['api_key']) || empty($settings['store_id'])) {
            wp_send_json_error(__('API anahtarı veya Mağaza ID ayarlanmamış.', 'shopiwise-payment'));
            return;
        }
        
        // $test_mode ? 'https://test-api.shopiwise.net' : 
        $test_mode = isset($settings['test_mode']) && 'yes' === $settings['test_mode'];
        $api_url = 'https://shopiwise.net/api';
        
        $response = wp_remote_get(
            $api_url . '/api/v1/store',
            array(
                'method' => 'GET',
                'timeout' => 45,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $settings['api_key']
                )
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 400 || (isset($body['error']) && $body['error'])) {
            wp_send_json_error($body['error'] ?? __('Bilinmeyen hata.', 'shopiwise-payment'));
            return;
        }
        
        wp_send_json_success(__('Bağlantı başarılı! Mağaza bilgileri alındı.', 'shopiwise-payment'));
    }
} 