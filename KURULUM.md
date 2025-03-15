# Shopiwise Ödeme Geçidi Kurulum Kılavuzu

Bu kılavuz, Shopiwise Ödeme Geçidi eklentisinin WordPress sitenize nasıl kurulacağını ve yapılandırılacağını açıklar.

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
   - **Test Modu**: Gerçek ödemeler almadan önce test etmek için etkinleştirin
   - **Debug Modu**: Sorun giderme için işlem loglarını kaydetmek için etkinleştirin
4. "Değişiklikleri Kaydet" düğmesine tıklayın

## Test Etme

1. Test modunu etkinleştirin
2. Test bir sipariş oluşturun
3. Ödeme yöntemi olarak "Shopiwise ile Güvenli Ödeme" seçeneğini seçin
4. Siparişi tamamlayın
5. Shopiwise ödeme sayfasına yönlendirileceksiniz
6. Test kredi kartı bilgilerini girin ve ödemeyi tamamlayın
7. Ödeme başarılı olduğunda, sitenize geri yönlendirileceksiniz ve sipariş durumu güncellenecektir

### Test Kredi Kartı Bilgileri

Test modunda aşağıdaki kredi kartı bilgilerini kullanabilirsiniz:

- **Kart Numarası**: 4355 0843 5508 4355
- **Son Kullanma Tarihi**: Gelecekteki herhangi bir tarih (örn. 12/30)
- **CVV**: 000
- **Kart Sahibi**: TEST

## Canlı Kullanım

1. Test modunu devre dışı bırakın
2. API anahtarınızın ve Mağaza ID'nizin doğru olduğundan emin olun
3. Ödeme geçidini müşterilerinizle kullanmaya başlayın

## Sorun Giderme

Eklenti ile ilgili sorunlar yaşıyorsanız:

1. Debug modunu etkinleştirin
2. WooCommerce > Durum > Loglar sayfasından "shopiwise-payment" loglarını kontrol edin
3. Shopiwise API bağlantınızı "Shopiwise Ödeme" sayfasından test edin
4. Sorun devam ederse, [destek sayfamızı](https://shopiwise.net/support) ziyaret edin

## Güvenlik

- API anahtarınızı ve Mağaza ID'nizi kimseyle paylaşmayın
- WordPress ve WooCommerce'in güncel sürümlerini kullanın
- Güvenli bir SSL sertifikası kullanın (HTTPS)

## Destek

Eklenti ile ilgili sorularınız veya sorunlarınız için:

- [Dokümantasyon](https://shopiwise.net/docs)
- [Destek](https://shopiwise.net/support)
- E-posta: support@shopiwise.net 