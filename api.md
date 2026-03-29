# Mikro Sipariş API Kurulumu

Bu doküman, Mikro tarafındaki sipariş verilerini portal uygulamasına güvenli ve sürdürülebilir şekilde aktarabilmek için BT ekibinin yapması gereken tüm adımları açıklar.

Amaç:
- Mikro veritabanında bir SQL `VIEW` oluşturmak
- Bu view'dan veri okuyan bir HTTP API endpoint yayınlamak
- Portal sunucusunun bu endpoint'e erişebilmesini sağlamak
- Portal tarafında gelen kolonları sipariş alanlarıyla eşleyebilmek

Bu doküman özellikle aşağıdaki senaryo için yazılmıştır:
- Mikro şirket içinde veya local ağda çalışıyor
- Portal ise ayrı bir sunucuda çalışıyor
- Portal, doğrudan veritabanına bağlanmayacak
- Portal yalnızca BT'nin hazırladığı HTTP endpoint'i çağıracak

## 1. Mimari Özeti

Kurulacak akış:

1. Mikro veritabanında bir SQL view oluşturulur
2. BT, bu view'ı okuyan küçük bir API servisi yayınlar
3. Portal sunucusu bu API'ye HTTP isteği atar
4. Portal, gelen JSON veriyi kendi sipariş modeline dönüştürür

Önemli not:
- Portal SQL çalıştırmaz
- Portal yalnızca API endpoint çağırır
- `SQL View Adı` portal tarafında sadece referans ve mapping amacıyla tutulur
- Asıl çalışan alan `Base URL` + `Sipariş Endpoint Yolu` olacaktır

## 2. Ağ ve Firewall Gereksinimleri

Portal sunucusu, BT'nin yayınladığı API endpoint'e erişebilmelidir.

Gerekli koşullar:
- API çalışan sunucu portal sunucusundan erişilebilir olmalı
- İlgili port firewall'da açık olmalı
- Mümkünse erişim sadece portal sunucusunun IP adresine whitelist ile sınırlandırılmalı

Önerilen güvenlik seçenekleri:
- VPN üzerinden erişim
- Reverse proxy arkasında yayın
- Sadece sabit IP whitelist
- API key ile ek doğrulama

Önerilen yaklaşım:
- API, Mikro'ya erişimi olan şirket içi Windows/IIS veya .NET sunucusunda çalışsın
- Portal sunucusu yalnızca bu API'ye erişsin

Örnek:
- BT sunucu IP: `192.168.1.50`
- API port: `5000`
- Endpoint URL: `http://192.168.1.50:5000/api/portal-orders`

Portal ayarı:
- `Base URL` = `http://192.168.1.50:5000`
- `Sipariş Endpoint Yolu` = `/api/portal-orders`

## 3. Portalın Beklediği Veri Kimlik Kuralları

Portal tarafında sipariş içe alma şu kurallarla çalışır:

- Tedarikçi kimliği: `supplier_code`
- Sipariş kimliği: `(supplier_id, order_no)`
- Satır kimliği: `line_no`

Çok kritik:
- `supplier_code`, portal veritabanındaki `supplier_mikro_accounts.mikro_cari_kod` alanı ile eşleşmelidir
- `order_no` global değil, supplier bazında uniq kabul edilir
- `line_no` mümkünse gerçek Mikro satır kimliği yani `sip_satirno` olmalıdır

## 4. Zorunlu Alanlar

### Sipariş başlığı için gerekli alanlar
- `supplier_code`
- `supplier_name`
- `order_no`
- `order_date`
- `status`
- `due_date`
- `notes`

### Sipariş satırları için gerekli alanlar
- `line_no`
- `stock_code`
- `stock_name`
- `order_qty`
- `shipped_quantity`
- `unit`
- `line_notes`

### Portal için teknik olarak zorunlu minimum alanlar
- `supplier_code`
- `order_no`
- `line_no`
- `stock_code`
- `stock_name`
- `order_qty`

## 5. Veri Format Kuralları

BT tarafında üretilen JSON aşağıdaki kurallara uymalıdır:

- Tarihler: `YYYY-MM-DD`
- Sayısal alanlar: numerik
- Boş alanlar: mümkünse `null`
- Durum alanı: mümkünse aşağıdaki standarda normalize edilmeli

Durum dönüşümleri:
- `active`, `aktif`, `open` -> `active`
- `draft`, `taslak` -> `draft`
- `completed`, `closed`, `kapali` -> `completed`
- `cancelled`, `iptal` -> `cancelled`

## 6. SQL View Oluşturma

BT ekibi, kendi Mikro tablo yapısına göre aşağıdaki örneği uyarlamalıdır.

Örnek SQL Server view:

```sql
CREATE VIEW dbo.vw_portal_purchase_orders AS
SELECT
    h.cari_kod                         AS supplier_code,
    h.cari_unvan                       AS supplier_name,
    h.evrak_no                         AS order_no,
    CAST(h.tarih AS date)              AS order_date,
    h.durum                            AS status,
    CAST(h.teslim_tarihi AS date)      AS due_date,
    h.aciklama                         AS notes,
    l.sip_satirno                      AS line_no,
    l.stok_kodu                        AS stock_code,
    l.stok_adi                         AS stock_name,
    CAST(l.miktar AS int)              AS order_qty,
    CAST(ISNULL(l.sevk_miktar, 0) AS int) AS shipped_quantity,
    l.birim                            AS unit,
    l.satir_notu                       AS line_notes
FROM dbo.siparis_baslik h
INNER JOIN dbo.siparis_satir l
    ON l.evrak_no = h.evrak_no;
GO
```

Not:
- Buradaki tablo isimleri örnektir
- BT kendi canlı Mikro şemasına göre uyarlamalıdır
- Alan alias'ları korunmalıdır

## 7. JSON Yayın Stratejisi

Portal iki payload tipini destekler:

### Seçenek A: `nested_lines`
Her sipariş tek bir kayıt olur, satırlar `lines` dizisi içinde gelir.

Avantaj:
- Portal için daha temiz
- Mapping daha okunur

### Seçenek B: `flat_rows`
Her satır tek kayıt olarak gelir, aynı sipariş birden fazla kez tekrar eder.

Avantaj:
- SQL view'dan üretmesi daha kolay
- BT tarafında implementasyonu daha hızlı

Portal her iki modu da destekler.

Öneri:
- BT isterse önce `flat_rows` ile başlasın
- Daha sonra istenirse `nested_lines` versiyonuna geçilebilir

## 8. Örnek Endpoint Tanımı

Endpoint:
- `GET /api/portal-orders`

Opsiyonel query parametreleri:
- `supplier_code`
- `updated_after`
- `limit`

Örnek çağrı:

```http
GET /api/portal-orders?supplier_code=120.01.001&limit=500
X-API-Key: BURAYA_GIZLI_KEY
```

## 9. Önerilen C# / .NET Uygulaması

Bu bölüm BT'nin uygulayabileceği örnek bir ASP.NET Core minimal API içerir.

### 9.1. Proje oluşturma

```bash
dotnet new web -n PortalOrdersApi
cd PortalOrdersApi
dotnet add package Dapper
dotnet add package Microsoft.Data.SqlClient
dotnet add package Swashbuckle.AspNetCore
```

### 9.2. `appsettings.json`

```json
{
  "ConnectionStrings": {
    "MikroDb": "Server=localhost;Database=MikroDb;User Id=sa;Password=YOUR_PASSWORD;TrustServerCertificate=True;"
  },
  "PortalApi": {
    "ApiKey": "BURAYA_GIZLI_KEY"
  },
  "Logging": {
    "LogLevel": {
      "Default": "Information",
      "Microsoft.AspNetCore": "Warning"
    }
  }
}
```

### 9.3. `Program.cs`

```csharp
using System.Data;
using Microsoft.Data.SqlClient;
using Dapper;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

builder.Services.AddScoped<IDbConnection>(_ =>
    new SqlConnection(builder.Configuration.GetConnectionString("MikroDb")));

var app = builder.Build();

app.UseSwagger();
app.UseSwaggerUI();

app.MapGet("/api/portal-orders", async (
    HttpRequest request,
    IDbConnection db,
    IConfiguration config,
    string? supplier_code,
    DateTime? updated_after,
    int? limit) =>
{
    var apiKey = request.Headers["X-API-Key"].FirstOrDefault();
    var expectedApiKey = config["PortalApi:ApiKey"];

    if (string.IsNullOrWhiteSpace(expectedApiKey) || apiKey != expectedApiKey)
    {
        return Results.Unauthorized();
    }

    var take = limit.GetValueOrDefault(500);
    if (take <= 0) take = 500;
    if (take > 5000) take = 5000;

    var sql = @"
SELECT TOP (@Take)
    supplier_code,
    supplier_name,
    order_no,
    order_date,
    status,
    due_date,
    notes,
    line_no,
    stock_code,
    stock_name,
    order_qty,
    shipped_quantity,
    unit,
    line_notes
FROM dbo.vw_portal_purchase_orders
WHERE (@SupplierCode IS NULL OR supplier_code = @SupplierCode)
ORDER BY order_date DESC, order_no ASC, line_no ASC;";

    var rows = (await db.QueryAsync<PortalOrderFlatRow>(sql, new
    {
        Take = take,
        SupplierCode = supplier_code
    })).ToList();

    var data = rows
        .GroupBy(x => new
        {
            x.supplier_code,
            x.supplier_name,
            x.order_no,
            x.order_date,
            x.status,
            x.due_date,
            x.notes
        })
        .Select(g => new PortalOrderDto
        {
            supplier_code = g.Key.supplier_code,
            supplier_name = g.Key.supplier_name,
            order_no = g.Key.order_no,
            order_date = g.Key.order_date?.ToString("yyyy-MM-dd"),
            status = NormalizeStatus(g.Key.status),
            due_date = g.Key.due_date?.ToString("yyyy-MM-dd"),
            notes = g.Key.notes,
            lines = g
                .OrderBy(x => x.line_no)
                .Select(x => new PortalOrderLineDto
                {
                    line_no = x.line_no,
                    stock_code = x.stock_code,
                    stock_name = x.stock_name,
                    order_qty = x.order_qty,
                    shipped_quantity = x.shipped_quantity,
                    unit = x.unit,
                    line_notes = x.line_notes
                })
                .ToList()
        })
        .ToList();

    return Results.Ok(new { data });
});

app.Run();

static string NormalizeStatus(string? status)
{
    if (string.IsNullOrWhiteSpace(status))
        return "active";

    return status.Trim().ToLower() switch
    {
        "active" => "active",
        "aktif" => "active",
        "open" => "active",
        "draft" => "draft",
        "taslak" => "draft",
        "completed" => "completed",
        "kapali" => "completed",
        "closed" => "completed",
        "cancelled" => "cancelled",
        "iptal" => "cancelled",
        _ => "active"
    };
}

public sealed class PortalOrderFlatRow
{
    public string supplier_code { get; set; } = "";
    public string? supplier_name { get; set; }
    public string order_no { get; set; } = "";
    public DateTime? order_date { get; set; }
    public string? status { get; set; }
    public DateTime? due_date { get; set; }
    public string? notes { get; set; }
    public string line_no { get; set; } = "";
    public string stock_code { get; set; } = "";
    public string? stock_name { get; set; }
    public int order_qty { get; set; }
    public int? shipped_quantity { get; set; }
    public string? unit { get; set; }
    public string? line_notes { get; set; }
}

public sealed class PortalOrderDto
{
    public string supplier_code { get; set; } = "";
    public string? supplier_name { get; set; }
    public string order_no { get; set; } = "";
    public string? order_date { get; set; }
    public string status { get; set; } = "active";
    public string? due_date { get; set; }
    public string? notes { get; set; }
    public List<PortalOrderLineDto> lines { get; set; } = new();
}

public sealed class PortalOrderLineDto
{
    public string line_no { get; set; } = "";
    public string stock_code { get; set; } = "";
    public string? stock_name { get; set; }
    public int order_qty { get; set; }
    public int? shipped_quantity { get; set; }
    public string? unit { get; set; }
    public string? line_notes { get; set; }
}
```

## 10. Flat Rows Versiyonu

BT nested JSON üretmek istemezse aşağıdaki gibi de dönebilir.

Portal bunu grouping ile toparlayabilir.

Örnek cevap:

```json
{
  "data": [
    {
      "supplier_code": "120.01.001",
      "supplier_name": "ABC Ambalaj",
      "order_no": "SIP-2026-001",
      "order_date": "2026-03-29",
      "status": "active",
      "due_date": "2026-04-05",
      "notes": "Test siparişi",
      "line_no": "1",
      "stock_code": "STK-001",
      "stock_name": "Etiket A",
      "order_qty": 1000,
      "shipped_quantity": 0,
      "unit": "Adet",
      "line_notes": null
    },
    {
      "supplier_code": "120.01.001",
      "supplier_name": "ABC Ambalaj",
      "order_no": "SIP-2026-001",
      "order_date": "2026-03-29",
      "status": "active",
      "due_date": "2026-04-05",
      "notes": "Test siparişi",
      "line_no": "2",
      "stock_code": "STK-002",
      "stock_name": "Etiket B",
      "order_qty": 2500,
      "shipped_quantity": 0,
      "unit": "Adet",
      "line_notes": null
    }
  ]
}
```

Bu durumda portal tarafında:
- `Payload Modu` = `flat_rows`

## 11. IIS / Windows Servis Yayını

BT aşağıdaki seçeneklerden biriyle servisi yayınlayabilir:

### Seçenek A: IIS altında yayın
- API publish edilir
- IIS Application olarak eklenir
- Port veya reverse proxy tanımlanır

### Seçenek B: Windows Service
- `dotnet publish -c Release`
- servis olarak ayağa kaldırılır

### Seçenek C: Docker
- şirket içinde Docker kullanılıyorsa container olarak yayınlanır

En pratik seçenek genelde:
- IIS veya Windows Service

## 12. Güvenlik

En az şu önlemler alınmalıdır:

- `X-API-Key` ile erişim kontrolü
- firewall sadece portal sunucusuna izin vermeli
- mümkünse HTTPS kullanılmalı
- loglarda şifre / connection string tutulmamalı
- SQL kullanıcı yetkisi sadece okuma olmalı

Öneri:
- Mikro veritabanı için ayrı read-only kullanıcı açın

## 13. Test Adımları

BT tarafı aşağıdaki sırayla test etmelidir:

### Adım 1
SQL view tek başına çalışıyor mu?

```sql
SELECT TOP 10 * FROM dbo.vw_portal_purchase_orders;
```

### Adım 2
API tarayıcı veya Postman ile veri döndürüyor mu?

```http
GET http://SERVER_IP:5000/api/portal-orders
X-API-Key: BURAYA_GIZLI_KEY
```

### Adım 3
Portal sunucusundan bu endpoint erişilebiliyor mu?

Portal sunucusunda örnek:

```bash
curl -H "X-API-Key: BURAYA_GIZLI_KEY" http://SERVER_IP:5000/api/portal-orders
```

### Adım 4
Portal ayarlarına:
- Base URL
- endpoint path
- payload mode
girilir

### Adım 5
Portalda `Endpoint'ten Örnek Veri Çek` denenir

### Adım 6
Gerekirse kolon mapping yapılır

## 14. Portal Tarafında Kolon Eşleme

Portal artık aşağıdaki alanları eşleyebilir:

### Sipariş alanları
- `supplier_code`
- `supplier_name`
- `order_no`
- `order_date`
- `status`
- `due_date`
- `notes`
- `shipment_status`
- `shipment_reference`

### Satır alanları
- `line_no`
- `stock_code`
- `stock_name`
- `order_qty`
- `shipped_quantity`
- `unit`
- `line_notes`

Eğer BT doğrudan bu standart isimlerle JSON döndürürse:
- mapping yapmak gerekmez
- sadece endpoint tanımlamak yeterli olur

Eğer BT kendi kolon adlarını döndürürse:
- portal ekranında dropdown ile eşleme yapılır

Örnek:
- `CARI_KOD` -> `supplier_code`
- `EVRAK_NO` -> `order_no`
- `SIP_SATIRNO` -> `line_no`
- `STOK_KODU` -> `stock_code`

## 15. BT İçin Kısa Checklist

- Mikro DB'ye erişen read-only kullanıcı hazır
- SQL view oluşturuldu
- Endpoint yayınlandı
- API key eklendi
- Firewall portal IP için açıldı
- Postman testi yapıldı
- Portal sunucusundan erişim test edildi
- Portal ayarları girildi
- Örnek veri çekildi
- Mapping kaydedildi

## 16. Sık Hata Nedenleri

### Portal örnek veri çekemiyor
Olası nedenler:
- Base URL yanlış
- endpoint path yanlış
- port kapalı
- firewall engelliyor
- API key yanlış
- service ayakta değil

### JSON geliyor ama sipariş oluşmuyor
Olası nedenler:
- `supplier_code` eşleşmiyor
- `line_no` yok
- `order_no` boş
- mapping yanlış

### Tedarikçi bulunamıyor
Olası neden:
- Mikro payload içindeki `supplier_code`, portaldeki `mikro_cari_kod` ile eşleşmiyor

## 17. Önerilen İlk Canlı Kurulum

İlk kurulum için önerilen basit yol:

1. BT `flat_rows` view üretir
2. BT API endpoint yayınlar
3. Portal `flat_rows` modunda bağlanır
4. Kolon mapping yapılır
5. Küçük bir supplier ile test edilir
6. Sonra tüm supplier'lara açılır

Bu yöntem en güvenli başlangıçtır.

## 18. Portal Tarafı Notu

Portalda şu alanlar kullanılacaktır:
- Mikro bağlantı bilgileri
- Sipariş endpoint yolu
- Payload modu
- SQL view adı
- Kolon mapping

Mapping aktif olduğunda sipariş senkronizasyonu mevcut Mikro entegrasyon akışına otomatik dahil olur.

