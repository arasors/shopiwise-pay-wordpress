# Shopiwise Payment Gateway

WordPress WooCommerce için Shopiwise ödeme geçidi eklentisi.

## Açıklama

Shopiwise Payment Gateway, WordPress WooCommerce sitenizde Shopiwise ödeme sistemini kullanmanızı sağlayan bir eklentidir. Bu eklenti sayesinde müşterileriniz, güvenli bir şekilde kredi kartı ile ödeme yapabilirler.

## Özellikler

- Kolay kurulum ve yapılandırma
- Güvenli ödeme işlemi
- Kredi kartı ile ödeme
- Taksitli ödeme desteği
- Otomatik sipariş durumu güncelleme
- Test modu

## İndir

[Eklentiyi buradan indirebilirsiniz](./shopiwise-pay-wp.zip)

## Kurulum

1. Eklenti dosyalarını `/wp-content/plugins/shopiwise-payment-gateway` dizinine yükleyin
2. WordPress admin panelinden eklentiyi etkinleştirin
3. WooCommerce ayarlarından "Ödeme" sekmesine gidin
4. "Shopiwise ile Güvenli Ödeme" seçeneğini etkinleştirin ve ayarları yapılandırın

[Kurulum talimatları için buraya tıklayın](./KURULUM.md)

## Gereksinimler

- WordPress 5.0 veya üzeri
- WooCommerce 4.0 veya üzeri
- PHP 7.2 veya üzeri

## Yapılandırma

1. Shopiwise hesabınızdan API anahtarınızı ve Mağaza ID'nizi alın
2. WooCommerce > Ayarlar > Ödeme > Shopiwise ile Güvenli Ödeme sayfasına gidin
3. API anahtarınızı ve Mağaza ID'nizi girin
4. Diğer ayarları isteğinize göre yapılandırın
5. Ayarları kaydedin

## Sık Sorulan Sorular

### Test modunu nasıl kullanabilirim?

Eklenti ayarlarından "Test Modu"nu etkinleştirerek gerçek ödemeler almadan önce ödeme sistemini test edebilirsiniz.

### Ödeme işlemi nasıl çalışır?

1. Müşteri siparişini tamamlar ve ödeme için "Shopiwise ile Güvenli Ödeme" seçeneğini seçer
2. Müşteri, Shopiwise güvenli ödeme sayfasına yönlendirilir
3. Müşteri, kredi kartı bilgilerini girer ve ödemeyi tamamlar
4. Ödeme başarılı olduğunda, müşteri sitenize geri yönlendirilir ve sipariş durumu güncellenir

### Ödeme işlemi başarısız olursa ne olur?

Ödeme işlemi başarısız olursa, müşteri sitenize geri yönlendirilir ve sipariş durumu "Başarısız" olarak güncellenir. Müşteri, ödeme işlemini tekrar deneyebilir.

## Destek

Eklenti ile ilgili sorunlarınız için [destek sayfamızı](https://shopiwise.net/cr/help) ziyaret edebilirsiniz.

## Lisans

Bu eklenti, GPL v2 veya sonraki sürümler altında lisanslanmıştır.

