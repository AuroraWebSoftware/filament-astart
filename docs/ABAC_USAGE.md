# ABAC Kullanım Kılavuzu

`filament-astart` plugin'i, `aurorawebsoftware/aauth` paketinin
**Attribute-Based Access Control (ABAC)** altyapısı için Filament tabanlı
bir yönetim arayüzü sunar. Kurallar role bazında, model_type bazında
saklanır ve query'lere otomatik olarak uygulanır.


---

## İçindekiler

1. [Hızlı Başlangıç](#hızlı-başlangıç)
2. [Bir Modeli ABAC-Enabled Yapma](#bir-modeli-abac-enabled-yapma)
3. [Konfigürasyon (Registry)](#konfigürasyon-registry)
4. [UI'da Kural Yazma](#uida-kural-yazma)
5. [Kural Yapısı](#kural-yapısı)
6. [Operatörler](#operatörler)
7. [Veri Akışı (Form ↔ DB)](#veri-akışı-form--db)
8. [Validation](#validation)
9. [Super-Admin Bypass](#super-admin-bypass)
10. [Bakım Komutu (Normalize)](#bakım-komutu-normalize)
11. [Audit Log (LogiAudit)](#audit-log-logiaudit)
12. [Programatik Kullanım](#programatik-kullanım)
13. [Sınırlamalar / Bilinen Konular](#sınırlamalar--bilinen-konular)

---

## Hızlı Başlangıç

3 adımda devrede:

```php
// 1) Model'i ABAC-enabled yap (AStartAbacModel önerilir — super-admin bypass dahil)
use AuroraWebSoftware\AAuth\Interfaces\AAuthABACModelInterface;
use AuroraWebSoftware\FilamentAstart\Traits\AStartAbacModel;

class Document extends Model implements AAuthABACModelInterface
{
    use AStartAbacModel;

    public static function getModelType(): string { return 'document'; }
    public static function getABACRules(): array { return []; }
}

// 2) config/astart-auth.php → abac.models içine kaydet
'abac' => [
    'enabled' => true,
    'models' => [
        'document' => [
            'class' => \App\Models\Document::class,
            'label' => 'Documents',
            'attributes' => [
                'status' => ['type' => 'string', 'options' => ['draft', 'active']],
            ],
        ],
    ],
],

// 3) Filament panel → herhangi bir rolün edit ekranı → "ABAC Kuralları" tabı
```

---

## Bir Modeli ABAC-Enabled Yapma

İki şart **birlikte** gerekir:

```php
use AuroraWebSoftware\AAuth\Interfaces\AAuthABACModelInterface;
use AuroraWebSoftware\FilamentAstart\Traits\AStartAbacModel;

class Document extends Model implements AAuthABACModelInterface
{
    use AStartAbacModel;

    /**
     * role_model_abac_rules tablosunda model_type kolonunda
     * kullanılan stringle aynı olmak ZORUNDA. Registry anahtarı
     * da aynı string olmalıdır.
     */
    public static function getModelType(): string
    {
        return 'document';
    }

    /**
     * Şu an scope sadece role-bazlı kuralları okuyor; bu metot
     * gelecekte fallback olarak kullanılabilir. Boş array
     * döndürmek güvenlidir.
     */
    public static function getABACRules(): array
    {
        return [];
    }
}
```

`AStartAbacModel` trait'i model boot edildiğinde wrapper bir global
scope ekler; o noktadan itibaren `Document::all()`, `Document::where(...)`
gibi **her sorgu** aktif rolün ABAC kuralına göre filtrelenir.
Super-admin tespit edilirse filtre devre dışı kalır
(bkz. [Super-Admin Bypass](#super-admin-bypass)).

### `AStartAbacModel` vs `AAuthABACModel`

| Senaryo | Trait |
|---|---|
| **Standart** (önerilen) | `AStartAbacModel` — super-admin bypass dahil |
| Super-admin'in de filtrelenmesini istiyorsan | aauth'un `AAuthABACModel` trait'i |

İki trait'in tek farkı bypass katmanı. Interface (`AAuthABACModelInterface`),
`getModelType()` ve `getABACRules()` sözleşmesi her ikisinde de aynıdır.

### ABAC'ı Bypass Etme

Belirli bir sorguda kuralları devre dışı bırakmak için:

```php
use AuroraWebSoftware\AAuth\Scopes\AAuthABACModelScope;

Document::withoutGlobalScope(AAuthABACModelScope::class)->get();
```

Tipik kullanım: arka plan job'ları, raporlar, admin panellerinde
toplu tutarlılık kontrolleri.

---

## Konfigürasyon (Registry)

`config/astart-auth.php` içindeki `abac` bloğu:

```php
'abac' => [
    'enabled' => true,                 // Master switch
    'models' => [
        'document' => [
            'class' => \App\Models\Document::class,
            'label' => 'Documents',
            'attributes' => [
                'status' => [
                    'type' => 'string',
                    'options' => ['draft', 'active', 'passive'],
                ],
                'amount' => [
                    'type' => 'numeric',
                    // 'options' bırakılırsa free-text giriş alanı çıkar
                ],
                'is_published' => [
                    'type' => 'boolean',
                    'options' => ['0', '1'],
                ],
            ],
        ],
    ],
],
```

| Anahtar | Açıklama |
|---|---|
| `enabled` | `false` ise tab tamamen gizlenir; whitelist boyutuna bakmaz. |
| `models` | `model_type => definition` haritası. |
| `class` | Tam nitelikli Eloquent class. (`Document::class`) |
| `label` | UI'da Section başlığı olarak görünür. |
| `attributes` | Kural editöründe seçilebilecek kolonlar (whitelist). |
| `attributes.{name}.type` | `string` / `numeric` / `boolean` / `date`. Şu an sadece bilgi amaçlı; v2'de tip-aware validation. |
| `attributes.{name}.options` | Varsa Select dropdown gelir; yoksa serbest TextInput. |

> **Güvenlik notu**: `password`, `remember_token` gibi hassas kolonları
> whitelist'e koymayın. ABAC editörü sadece listelenen kolonlarda kural
> yazılmasına izin verir, bu kasıtlı bir savunma katmanıdır.

---

## UI'da Kural Yazma

Her role edit ekranında ABAC sekmesi:

1. **Section** = bir model_type (registry'deki her model için bir section).
2. **Üst Mantıksal Operatör** (`VE` / `VEYA`) — section'daki bütün
   blokları nasıl birleştireceğimizi söyler.
3. **Bloklar Repeater'ı**: her blok ya `Koşul` ya da `Grup`.
4. **Koşul**: `attribute → operator → value` üçlüsü.
5. **Grup**: kendi mantıksal operatörü olan, içinde birden fazla koşul
   barındıran nested kural. (1 seviye nesting.)

### Örnek: "Yayında olan ve onaylı dökümanlar"

```
Üst operatör: VE (&&)
Bloklar:
  ▸ is_published = 1
  ▸ status       = active
```

### Örnek: "EU veya US bölgesindeki ve aktif kayıtlar"

```
Üst operatör: VE (&&)
Bloklar:
  ▸ status = active
  ▸ Grup (VEYA):
      • region = EU
      • region = US
```

### Boş Kural

Bütün blokları silip kaydedersen, ilgili `RoleModelAbacRule` satırı
**silinir** (`null` rules_json global scope'u kıracaktı). Kuralı
"boşaltmak" = kuralın o role için kapatılması demektir.

---

## Kural Yapısı

UI tarafından üretilen ve `role_model_abac_rules.rules_json` kolonuna
yazılan veri **aauth'un beklediği formattadır**. Örnek:

```php
[
    '&&' => [
        ['=' => ['attribute' => 'status', 'value' => 'active']],
        ['||' => [
            ['=' => ['attribute' => 'region', 'value' => 'EU']],
            ['=' => ['attribute' => 'region', 'value' => 'US']],
        ]],
    ],
]
```

Bu yapı `AAuthABACModelScope` tarafından rekürsif olarak SQL'e dönüştürülür:

```sql
WHERE (
    documents.status = 'active'
    AND (documents.region = 'EU' OR documents.region = 'US')
)
```

---

## Operatörler

### Mantıksal

| Sembol | Anlam |
|---|---|
| `&&` | AND (hepsi) |
| `\|\|` | OR (en az biri) |

### Karşılaştırma (`ABACCondition` enum)

| Sembol | Anlam |
|---|---|
| `=` | Eşit |
| `!=` | Eşit değil |
| `>` | Büyük |
| `<` | Küçük |
| `>=` | Büyük eşit |
| `<=` | Küçük eşit |
| `like` | Pattern eşleşmesi (`%foo%`) |

> aauth'un README'sinde `IN`, `NOT IN`, `NOT LIKE` operatörleri yazsa da
> `ABACCondition` enum'unda yokturlar; UI'da görünmezler. aauth tarafında
> genişletilmesi gerekiyor.

---

## Veri Akışı (Form ↔ DB)

```
┌──────────────┐  toFormState()    ┌─────────────────────┐
│ rules_json   │ ────────────────> │ AbacRuleBuilder     │
│ (DB)         │ <──────────────── │ (Filament Repeater) │
└──────────────┘  fromFormState()  └─────────────────────┘
       ▲                                    │
       │                                    │ user input
       │  HandlesAbacRules::saveAbacRules() │
       └────────────────────────────────────┘
              (validation + transaction)
```

- **Yükleme**: `EditRole::mutateFormDataBeforeFill` →
  `loadAbacRules()` → her model_type için `toFormState()` →
  `$data['abac_rules'][$modelType]`.
- **Kaydetme**: `mutateFormDataBeforeSave/Create` →
  `validateAbacRulesPayload()` → payload sakla → `afterSave/Create` →
  `saveAbacRules()` → `fromFormState()` → `updateOrCreate`/`delete`.

Tüm yazma işlemleri `DB::transaction()` içinde.

---

## Validation

Kural kaydetmeden önce **4 katman** çalışır:

1. **Yapısal**: blok tipi (`condition`/`group`), grup boş mu, alanlar var mı.
2. **Whitelist**: kullanılan `attribute`, registry'de var mı.
3. **Tip-aware**: registry'de `type` tanımlıysa value beklenen tipe uyuyor mu.
   - `numeric` / `integer` / `int` / `float` / `decimal` → `is_numeric()`
   - `boolean` / `bool` → `true/false/0/1/yes/no` türevleri
   - `date` / `datetime` → `strtotime()` parse edebiliyor mu
   - `string` / `text` → scalar (varsayılan, neredeyse her şey kabul)
   - Bilinmeyen tip → kontrol atlanır (custom registry'ler bozulmaz)
   - `like` operatörü için **bu katman atlanır** (LIKE her zaman string pattern alır)
4. **aauth ABACUtil**: `validateAbacRuleArray()` ile son kontrol.

Hata durumunda Filament `Notification` (kalıcı, kırmızı) gönderilir ve
`Halt` exception'ı atılarak save iptal edilir. Hatalar liste şeklinde
satır satır gösterilir:

```
[document #1] ':password' bu model için izin verilen özellikler arasında değil
[document #2] Değer boş olamaz
[document #3] 'amount' özelliği numeric tipinde olmalı (verilen: 'abc')
```

---

## Super-Admin Bypass

`AStartAbacModel` trait'i ABAC global scope'unu bir wrapper içinde
çalıştırır. Wrapper, sorgu çalışmadan **önce** super-admin olup
olmadığını kontrol eder; super-admin ise filtre uygulanmaz ve tüm
kayıtlar döner.

### Algılama yöntemi

Wrapper iki katmanlı kontrol yapar:

1. **Birincil**: `AAuth::isSuperAdmin()` — aauth'un kendi auth context'i
   üzerinden okur. Role seçilmiş bir kullanıcı için en güvenilir kaynak.
2. **Yedek**: `AAuthUtil::isSuperAdmin()` — config (`aauth-advanced.super_admin`)
   ve Filament auth üzerinden okur. aauth context henüz başlatılmamışsa
   devreye girer.
3. **İkisi de tespit edemezse**: bypass uygulanmaz, normal scope çalışır
   (güvenli default).

### Yapılandırma

`config/aauth-advanced.php`:

```php
'super_admin' => [
    'enabled' => true,
    'column' => 'is_super_admin',  // User modelindeki boolean kolon
],
```

User modelinde ilgili kolonun (`is_super_admin` veya senin tanımladığın)
boolean olarak set edilmiş olması yeterli — wrapper otomatik tanır.

### Bypass'i KULLANMAMAK

Super-admin'in de ABAC kuralına tabi olmasını istiyorsan, model'de
`AStartAbacModel` yerine aauth'un orijinal `AAuthABACModel` trait'ini
kullan. Bu durumda wrapper yok, doğrudan aauth scope çalışır.

### CLI / queue / non-auth context

Auth context yoksa (CLI command, queue job, vb.) bypass tetiklenmez ve
scope normal çalışır. Bu istenen davranıştır — arka plan işlemlerinin
ABAC kurallarını "atlaması" güvenlik açığına yol açabilir. Bilinçli
bypass için `withoutGlobalScope(AStartAbacModelScope::class)` kullan.

---

## Bakım Komutu (Normalize)

```bash
php artisan filament-astart:abac:normalize [--dry-run]
```

`role_model_abac_rules` tablosundaki **eski format (doc-style unwrapped)**
kuralları yeni sarmalı format'a çevirir. İdempotent — zaten temiz olan
satırlara dokunmaz.

### Ne zaman gerekir?

- **Normalde**: gerekmez. Plugin her yeni kural'ı doğru format'ta yazar.
- **Defansif olarak**: aauth'un scope iteration bug'ı düzelmeden önce
  manuel JSON eklenmiş veya başka bir araçtan import edilmiş kayıtlar
  varsa.
- **Auto-heal yedeği**: Role edit sayfası açıldığında plugin eski format
  tespit ederse zaten sessizce düzeltir; bu komut o açılmayı beklemeden
  toplu temizler.

### Kullanım

```bash
# Önce ne değişeceğini gör (DB'ye yazmaz)
php artisan filament-astart:abac:normalize --dry-run

# Gerçek çalıştırma
php artisan filament-astart:abac:normalize
```

Çıktı satır satır hangi (`role_id`, `model_type`) kombinasyonunun
değiştiğini gösterir; özet kısmında `updated`, `already canonical`,
`invalid/skipped` sayıları görünür.

---

## Audit Log (LogiAudit)

LogiAudit paketi yüklüyse plugin tüm UI mutasyon noktalarını
`logiaudit_logs` tablosuna **semantic event** olarak yazar. Plugin
column-level history yazmaz; her log entry insan-okunaklı bir cümle
olup detayı context JSON'unda taşır.

LogiAudit yüklü değilse hiçbir log yazılmaz. Master switch:
`config('astart-auth.log.enabled')` (default `true`).

### Tag taksonomisi

```
RBAC
  rbac.role          Role CRUD                              (süresiz)
  rbac.permissions   Permission grant/revoke (aggregate)    (süresiz)
  rbac.assignment    User-Role-Node atama/kaldırma          (süresiz)

ABAC
  abac               ABAC rule CRUD                         (süresiz)

AUTH
  auth.role_switch   Aktif rol değiştirme                   (7 gün)

USER
  user.lifecycle     User Create / Update                   (süresiz)
  user.status        Activate / Deactivate                  (süresiz)
  user.security      Lock, force password change,
                     terminate sessions, send PW reset      (süresiz / send PW reset: 30 gün)

ORG
  org.scope          Organization scope CRUD                (süresiz)
  org.node           Organization node CRUD                 (süresiz)
  org.tree           Organization tree CRUD                 (süresiz)
```

`logiaudit_logs` resource'unda `tag` kolonuyla filtrele.

### Log entry yapısı

Tüm log'lar şu şablonu izler:

```php
[
    'level' => 'info',
    'tag' => 'rbac.role',
    'model_type' => 'AuroraWebSoftware\\AAuth\\Models\\Role',
    'model_id' => 5,
    'ip_address' => '172.18.0.1',
    'message' => "Mehmet (#1) updated role 'Manager' (#5): name='Editor', status='passive'",
    'context' => [
        'action' => 'updated',
        'changes' => ['name' => ['from' => 'Manager', 'to' => 'Editor'], ...],
        'user_id' => 1,
        'user_name' => 'Mehmet',
        'user_class' => 'App\\Models\\User',
    ],
]
```

Causer bilgisi (`user_id` / `user_name` / `user_class`) her log'da
context JSON'una konur — `logiaudit_logs` schema'sında native
`user_id` kolonu olmadığı için.

### Permission değişiklikleri — aggregate format

Bir rolün permission'larını save'lediğinde **tek log entry** üretilir,
N permission değişse bile:

```
Mehmet (#1) updated permissions for role 'Manager' (#5):
  added [user_view, user_edit, role_view], removed [user_delete]
```

Context'te `added` ve `removed` listeleri ayrı array olarak durur.

### Hangileri loglanmaz?

| Olay | Sebep |
|---|---|
| Read query'ler / sayfa açma | Log değeri düşük, gürültü |
| Avatar yükleme | User update kapsamında |
| Login / logout | FiLogin plugin'i kendi log'unu tutar |
| Filament navigation | Out of scope |
| Eloquent CRUD events (model-level) | Plugin sadece UI action'larında log atar — programatik mutasyonlar kapsamda değil |

> `updated` event'leri yalnızca **gerçek değişiklik** olduğunda
> loglanır — sadece `updated_at` değişimi sessiz geçer.

`tag=abac` filtresi ile LogiAuditLog resource'unda sadece ABAC
event'lerini ayıklayabilirsin.

### Log'u devre dışı bırakmak

İki seviye:

- **Master switch**: `config('astart-auth.log.enabled')` = `false` →
  bütün plugin log'ları susar (UI action'ları + ABAC observer dahil).
- **LogiAudit yok**: paket kurulu değilse `addLog()` helper'ı tanımlı
  olmadığı için her log noktası sessiz no-op olur.

---

## Programatik Kullanım

### Helper'lar (`AAuthUtil`)

```php
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;

AAuthUtil::isAbacEnabled();                                  // bool
AAuthUtil::getAbacModels();                                  // array<string, def>
AAuthUtil::getAbacModelLabel('document');                    // 'Documents'
AAuthUtil::getAbacAttributes('document');                    // ['status' => [...], ...]
AAuthUtil::getAbacAttributeOptions('document', 'status');    // ['draft', 'active'] | null
```

### Transformer

Form state ↔ rules_json çift yönlü çeviri:

```php
use AuroraWebSoftware\FilamentAstart\Utils\AbacRuleTransformer;

$rulesJson = AbacRuleTransformer::fromFormState($formState);  // ?array
$formState = AbacRuleTransformer::toFormState($rulesJson);    // array
```

### Doğrudan kural kaydı (UI dışından)

```php
use AuroraWebSoftware\AAuth\Models\RoleModelAbacRule;

RoleModelAbacRule::updateOrCreate(
    ['role_id' => $roleId, 'model_type' => 'document'],
    ['rules_json' => [
        '&&' => [
            ['=' => ['attribute' => 'status', 'value' => 'active']],
        ],
    ]],
);
```

---

## Sınırlamalar / Bilinen Konular

| # | Konu | Geçici çözüm / Not |
|---|---|---|
| 1 | Nesting derinliği UI'da **2 seviye** ile sınırlı | Daha derin senaryolar için programatik kayıt kullanın. |
| 2 | aauth `ABACCondition` enum'unda `IN`, `NOT IN`, `NOT LIKE` yok | Şimdilik birden çok `=`'i `\|\|` ile birleştirin. |
| 3 | aauth scope'u model'in `getABACRules()` fallback'ini çağırmıyor | Sadece role-bazlı kurallar geçerli. |
| 4 | `AAuth::ABACRules()` her query'de DB hit yapar | Yüksek trafikli sayfalarda izlemeye değer; aauth backlog'unda. |
| 5 | `value` alanı her zaman string olarak saklanır | Numeric karşılaştırmalarda PDO type juggling devreye girer. |
| 6 | ~~Tip-aware validation (Seviye 2) henüz yok~~ | ✅ Eklendi: numeric/boolean/date/string kontrolü registry'deki `type` üzerinden. |
| 7 | ~~Aynı `(role_id, model_type)` çiftinde unique index yok~~ | ✅ Plugin migration'ı (`2026_05_12_000000_add_unique_index_to_role_model_abac_rules`) bu kısıtlamayı DB seviyesinde uygular. Eski duplicate satırlar varsa migration sırasında en güncel olan kalır. |

Bu liste yaşayan bir belge: yeni kısıtlamalar ortaya çıkarsa
aauth tarafında ele alınması gerekiyor.
