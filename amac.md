Benim için PHP tabanlı, üretime alınabilecek bir “Tedarikçi Artwork Portalı” sistemi tasarla ve geliştir.

Teknoloji tercihleri:
- Backend: PHP
- Tercihen framework: Laravel
- Veritabanı: MySQL veya PostgreSQL
- Sunucu: DigitalOcean Droplet
- Dosya depolama: DigitalOcean Spaces
- Web server: Nginx
- Dağıtım: Docker / Docker Compose tercih edilir
- Arayüz: Modern, sade, kurumsal, mobil uyumlu
- Dil: Türkçe arayüz
- Yetkilendirme: Rol bazlı kullanıcı sistemi

Projenin amacı:
Satın alma tarafında çok sayıda tedarikçimiz var. Bu tedarikçiler, koli, etiket, kutu, ambalaj, baskı ve benzeri tasarım gerektiren ürünleri üretmeden önce bizim grafik departmanımızdan güncel artwork dosyasını istiyor. Bugün bu süreç e-posta ile ilerliyor ve ciddi karmaşa yaratıyor. İstediğim sistem, e-posta trafiğini ortadan kaldıracak merkezi bir tedarikçi portalı olsun.

İstenen ana işleyiş:
1. Her tedarikçi kendine özel kullanıcı adı ve şifre ile sisteme giriş yapabilsin.
2. Her tedarikçi sadece kendi siparişlerini görebilsin.
3. Tedarikçi sipariş listesinde kendi satın alma siparişlerini veya ilgili iş kayıtlarını görüntüleyebilsin.
4. Tedarikçi sipariş satırına girdiğinde o siparişe bağlı güncel artwork dosyasını görebilsin.
5. Artwork dosyaları PDF, AI, EPS, ZIP gibi formatlarda olabilir.
6. Büyük dosya boyutları desteklenmeli. Bazı dosyalar 1 GB’a kadar çıkabilir.
7. Grafik departmanı sisteme artwork yükleyebilsin, yeni revizyon ekleyebilsin, eski revizyonları arşivleyebilsin.
8. Sistemde her zaman “güncel aktif revizyon” net şekilde gösterilsin.
9. Tedarikçi mümkünse sadece final/güncel dosyayı görsün. İç kullanıcı isterse tüm revizyon geçmişini görebilsin.
10. Tedarikçi artwork dosyasını görüntüleyebilsin veya indirebilsin.
11. Tüm süreç loglansın: kim ne zaman giriş yaptı, hangi dosyayı görüntüledi, ne indirdi, hangi revizyon yüklendi.

Mimari beklenti:
- Uygulama DigitalOcean Droplet üzerinde çalışacak.
- Dosyalar kesinlikle sunucu diskinde değil, DigitalOcean Spaces üzerinde tutulacak.
- Dosya yükleme ve indirme işlemleri Spaces ile entegre çalışmalı.
- Büyük dosyalar için uygun yükleme stratejisi düşünülmeli.
- Uygulama, dosyaları veritabanında metadata olarak tutmalı; gerçek dosyalar Spaces’te bulunmalı.
- İndirme bağlantıları güvenli olmalı.
- Dosyalar public açık olmamalı. Yetki kontrolünden sonra güvenli erişim sağlanmalı.

Kullanıcı rolleri:
1. Admin
   - Tüm sistemi yönetir
   - Kullanıcı açar/kapatır
   - Tedarikçi firma tanımlar
   - Yetki verir
   - Tüm kayıtları görür

2. Satın Alma
   - Siparişleri görür
   - Tedarikçi-sipariş eşleştirmelerini yönetebilir
   - Artwork durumunu takip eder

3. Grafik Departmanı
   - Artwork yükler
   - Revizyon oluşturur
   - Eski dosyaları pasife alır
   - Güncel revizyonu işaretler
   - Sipariş satırına dosya bağlar

4. Tedarikçi
   - Sadece kendine ait kayıtları görür
   - Sadece yetkili olduğu siparişleri açar
   - Güncel artwork dosyasını görüntüler / indirir
   - Gerekirse “gördüm”, “indirdim”, “onay bekliyor” gibi aksiyonlar verebilir

İstenen modüller:
1. Giriş ve kullanıcı yönetimi
2. Rol ve yetki yönetimi
3. Tedarikçi firma yönetimi
4. Sipariş yönetimi
5. Sipariş satırı yönetimi
6. Artwork yükleme modülü
7. Artwork revizyon yönetimi
8. Dosya önizleme / indirme modülü
9. Aktivite logları
10. Dashboard / özet ekranlar
11. Bildirim altyapısı (ilk fazda opsiyonel)
12. Arama ve filtreleme ekranları

Sipariş mantığı:
- Her siparişin bir veya daha fazla sipariş satırı olabilir.
- Artwork dosyaları sipariş bazlı değil, gerekirse sipariş satırı bazlı ilişkilendirilebilmeli.
- Her sipariş satırı için birden fazla revizyon olabilir.
- Bunlardan yalnızca biri “aktif/güncel” olarak işaretlenmeli.
- Tedarikçi ekranında varsayılan olarak sadece aktif revizyon gösterilmeli.

Veritabanı tarafında örnek tablolar öner:
- users
- roles
- permissions
- suppliers
- supplier_users
- purchase_orders
- purchase_order_lines
- artworks
- artwork_revisions
- artwork_download_logs
- artwork_view_logs
- audit_logs

Her tablo için:
- önerilen kolonları
- ilişkileri
- indeks ihtiyaçlarını
- temel foreign key yapısını
detaylı şekilde çıkar.

Dosya yönetimi beklentileri:
- Dosyalar DigitalOcean Spaces üzerinde klasör mantığıyla organize edilsin.
- Örnek path yapısı öner:
  /supplier/{supplier_id}/orders/{order_no}/lines/{line_id}/revisions/{revision_no}/
- Dosya adı standardı öner.
- Yüklenen dosyalarda versiyon takibi olsun.
- Eski dosyalar silinmesin, arşivlensin.
- Büyük dosya desteği olsun.
- Metadata olarak şunlar tutulsun:
  - orijinal dosya adı
  - sistem dosya adı
  - mime type
  - boyut
  - revision no
  - aktif mi
  - yükleyen kullanıcı
  - yükleme tarihi
  - açıklama/not

Arayüz beklentileri:
- Sade, modern, kurumsal tasarım
- Çok karmaşık olmayan, temiz panel
- Sol menü + üst bilgi yapısı olabilir
- Mobil uyumlu olsun
- Tedarikçi ekranı çok basit olsun
- İç kullanıcı ekranları daha detaylı olabilir

Ekranlar:
1. Login ekranı
2. Şifremi unuttum ekranı
3. Dashboard
4. Tedarikçi listesi
5. Tedarikçi detay
6. Sipariş listesi
7. Sipariş detay
8. Sipariş satırı detay
9. Artwork revizyon listesi
10. Artwork yükleme ekranı
11. Artwork detay ekranı
12. Log ekranı
13. Kullanıcı yönetimi ekranı
14. Rol/yetki ekranı
15. Profil ekranı

Dashboard’da görmek istediklerim:
- Bekleyen artwork sayısı
- Güncel artwork yüklenmiş sipariş sayısı
- Revizyon bekleyen kayıt sayısı
- Son yüklenen dosyalar
- Son indirilen dosyalar
- En aktif tedarikçiler
- En çok revizyon alan işler

Güvenlik beklentileri:
- Rol bazlı yetkilendirme
- Her kullanıcı sadece kendi yetkili olduğu verilere erişebilsin
- Dosyalar doğrudan public link ile açık olmasın
- Güvenli indirme mekanizması olsun
- Giriş logları tutulsun
- IP, zaman, kullanıcı, işlem bilgileri loglansın
- CSRF, XSS, SQL injection gibi temel güvenlik önlemleri düşünülmeli
- Laravel kullanılırsa best practice’lere uyulsun

Performans beklentileri:
- Büyük dosya metadata yönetimi optimize olsun
- Sayfalama olsun
- Filtreleme hızlı olsun
- İlişkili veriler doğru eager loading mantığıyla getirilsin
- Gereksiz ağır sorgular yazılmasın
- Log tabloları büyüdüğünde performans düşmemeli
- Gerekirse queue yapısı öner

DigitalOcean altyapı beklentisi:
- Uygulama DigitalOcean Droplet üzerinde çalışacak
- Dosyalar DigitalOcean Spaces üzerinde olacak
- Domain ve SSL desteği düşünülmeli
- Nginx reverse proxy yapısı önerilmeli
- Docker Compose ile çalıştırılabilir mimari tercih edilmeli
- Yedekleme stratejisi önerilmeli
- .env bazlı yapılandırma olmalı

İstenen çıktılar:
1. Tüm sistemin teknik mimari şeması
2. Veritabanı tasarımı
3. Klasör/proje yapısı
4. Modül listesi
5. Kullanıcı rolleri ve yetki matrisi
6. Sayfa sayfa ekran listesi
7. Laravel bazlı örnek proje yapısı
8. API endpoint önerileri
9. DigitalOcean Spaces entegrasyon yaklaşımı
10. Güvenli dosya erişim stratejisi
11. Kurulum adımları
12. MVP planı
13. Faz 2 ve Faz 3 geliştirme önerileri

Geliştirme yaklaşımı:
Önce MVP çıkar:
- login
- rol yönetimi
- tedarikçi listesi
- sipariş listesi
- sipariş satırına artwork bağlama
- artwork yükleme
- güncel revizyon gösterme
- güvenli indirme
- temel loglama

Sonra ikinci faz:
- revizyon geçmişi
- gelişmiş filtreler
- dashboard
- bildirimler
- “gördüm/onayladım” akışı
- indirme / görüntüleme detay logları

Sonra üçüncü faz:
- kalite dokümanları
- teknik çizimler
- numune onayı
- workflow / onay süreci
- satın alma ve grafik departmanı iş akışı otomasyonu

Beklentim:
Bana bu sistemi yazılım mimarı gibi tasarla. Yüzeysel cevap verme. Gerçek bir proje başlatacak seviyede detaylı, uygulanabilir, mantıklı ve kurumsal bir çözüm ver. Gerekirse tablo şemaları, örnek Laravel model yapıları, route yapısı, controller mantığı, dosya yükleme akışı, Spaces klasörleme stratejisi ve örnek ekran akışları da üret.