# Shopiwise Ödeme Geçidi Kurulum Kılavuzu

Bu kılavuz, Shopiwise Ödeme Geçidi eklentisinin WordPress sitenize nasıl kurulacağını ve yapılandırılacağını açıklar.

## İndir

[Eklentiyi buradan indirebilirsiniz](https://raw.githubusercontent.com/arasors/shopiwise-pay-wordpress/refs/heads/main/shopiwise-pay-wp.zip)

## Kurulum

1. Eklenti dosyalarını `/wp-content/plugins/shopiwise-payment-gateway` dizinine yükleyin
2. WordPress admin panelinden "Eklentiler" menüsüne gidin
3. "Shopiwise Payment Gateway" eklentisini bulun ve "Etkinleştir" düğmesine tıklayın

## Yapılandırma

### API Anahtarı ve Mağaza ID Alma

1. [Shopiwise](https://shopiwise.net) hesabınıza giriş yapın
2. "Ayarlar" > "API Anahtarları" menüsüne gidin
3. Yeni bir API anahtarı oluşturun veya mevcut bir anahtarı kullanın
4. Mağaza ID'nizi "Mağazalarım" sayfasından alın

### Eklenti Ayarları

1. WordPress admin panelinden "WooCommerce" > "Ayarlar" > "Ödeme" menüsüne gidin
2. "Shopiwise ile Güvenli Ödeme" seçeneğini bulun ve "Yönet" düğmesine tıklayın
3. Aşağıdaki ayarları yapılandırın:
   - **Etkinleştir/Devre Dışı Bırak**: Ödeme geçidini etkinleştirmek için işaretleyin
   - **Başlık**: Müşterinin ödeme sırasında göreceği başlık
   - **Açıklama**: Müşterinin ödeme sırasında göreceği açıklama
   - **API Anahtarı**: Shopiwise hesabınızdan aldığınız API anahtarı
   - **Mağaza ID**: Shopiwise hesabınızdan aldığınız Mağaza ID
   - **Debug Modu**: Sorun giderme için işlem loglarını kaydetmek için etkinleştirin
4. "Değişiklikleri Kaydet" düğmesine tıklayın


## Canlı Kullanım

1. Test modunu devre dışı bırakın
2. API anahtarınızın ve Mağaza ID'nizin doğru olduğundan emin olun
3. Ödeme geçidini müşterilerinizle kullanmaya başlayın

## Sorun Giderme

Eklenti ile ilgili sorunlar yaşıyorsanız:

1. Debug modunu etkinleştirin
2. WooCommerce > Durum > Loglar sayfasından "shopiwise-payment" loglarını kontrol edin
3. Shopiwise API bağlantınızı "Shopiwise Ödeme" sayfasından test edin
4. Sorun devam ederse, [destek sayfamızı](https://shopiwise.net/cr/help) ziyaret edin

## Güvenlik

- API anahtarınızı ve Mağaza ID'nizi kimseyle paylaşmayın
- WordPress ve WooCommerce'in güncel sürümlerini kullanın
- Güvenli bir SSL sertifikası kullanın (HTTPS)

## Destek

Eklenti ile ilgili sorularınız veya sorunlarınız için:

- [Destek](https://shopiwise.net/cr/help)