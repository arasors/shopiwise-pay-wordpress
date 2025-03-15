<?php
/**
 * Plugin Name: Shopiwise Payment Gateway
 * Plugin URI: https://shopiwise.net
 * Description: Shopiwise ödeme geçidi entegrasyonu ile WordPress WooCommerce sitenizde güvenli ödeme alın.
 * Version: 1.0.0
 * Author: Shopiwise
 * Author URI: https://shopiwise.net
 * Text Domain: shopiwise-payment
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

// Eklenti sabitlerini tanımla
define('SHOPIWISE_PAYMENT_VERSION', '1.0.0');
define('SHOPIWISE_PAYMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHOPIWISE_PAYMENT_PLUGIN_URL', plugin_dir_url(__FILE__));

// WooCommerce yüklü mü kontrol et
function shopiwise_payment_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'shopiwise_payment_woocommerce_missing_notice');
        return false;
    }
    return true;
}

// WooCommerce eksik uyarısı
function shopiwise_payment_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('Shopiwise Payment Gateway için WooCommerce eklentisinin yüklü ve aktif olması gerekiyor.', 'shopiwise-payment'); ?></p>
    </div>
    <?php
}

// Eklenti başlatma
function shopiwise_payment_init() {
    if (!shopiwise_payment_check_woocommerce()) {
        return;
    }

    // Ödeme geçidi sınıfını dahil et
    require_once SHOPIWISE_PAYMENT_PLUGIN_DIR . 'includes/class-shopiwise-payment-gateway.php';
    
    // Admin ayarlarını dahil et
    if (is_admin()) {
        require_once SHOPIWISE_PAYMENT_PLUGIN_DIR . 'includes/class-shopiwise-payment-admin.php';
        new WC_Shopiwise_Payment_Admin();
    }
    
    // Dil dosyalarını yükle
    load_plugin_textdomain('shopiwise-payment', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // WooCommerce ödeme geçitlerine Shopiwise'ı ekle
    add_filter('woocommerce_payment_gateways', 'shopiwise_add_payment_gateway');
}
add_action('plugins_loaded', 'shopiwise_payment_init');

// Ödeme geçidini WooCommerce'e ekle
function shopiwise_add_payment_gateway($gateways) {
    $gateways[] = 'WC_Shopiwise_Payment_Gateway';
    return $gateways;
}

// Eklenti aktivasyon işlemleri
function shopiwise_payment_activate() {
    // Aktivasyon işlemleri
    if (!get_option('woocommerce_shopiwise_payment_settings')) {
        add_option('woocommerce_shopiwise_payment_settings', array(
            'api_key' => '',
            'store_id' => '',
            'test_mode' => 'yes',
            'debug_mode' => 'no'
        ));
    }
}
register_activation_hook(__FILE__, 'shopiwise_payment_activate');

// Eklenti deaktivasyonu
function shopiwise_payment_deactivate() {
    // Deaktivasyon işlemleri
}
register_deactivation_hook(__FILE__, 'shopiwise_payment_deactivate');

// Eklenti kaldırma işlemleri
function shopiwise_payment_uninstall() {
    // Kaldırma işlemleri
    delete_option('woocommerce_shopiwise_payment_settings');
}
register_uninstall_hook(__FILE__, 'shopiwise_payment_uninstall');

// Ayarlar sayfasına link ekle
function shopiwise_payment_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=shopiwise_payment">' . __('Ayarlar', 'shopiwise-payment') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'shopiwise_payment_add_settings_link'); 