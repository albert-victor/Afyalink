# AfyaLink – Architecture & Data Flow

## Je, app inafanya kazi offline?

**Ndiyo** – kwa safu 3:

| Safu | Mahali | Inahifadhi nini |
|------|--------|-----------------|
| **1. IndexedDB** | Browser (`assets/js/offline.js`) | Majibu ya API + bundle kamili (7 siku) |
| **2. Service Worker** | `sw.js` | Faili za UI + cache ya API (network-first) |
| **3. SQLite fallback** | `database/afyalink_fallback.sqlite` | Nakala ya PostgreSQL kwenye server |

**Jinsi inavyofanya kazi:**
1. Ukiwa **online** – app inapakia data kutoka PostgreSQL, inahifadhi kwenye IndexedDB
2. Ukiwa **offline** – inatumia cache ya browser; bado unaweza kutafuta hospitali na kuona huduma
3. Ikiwa **PostgreSQL imeanguka** – server inaendelea kwa SQLite fallback (`DB_DRIVER=auto`)

---

## Inapakia nini na kutoka wapi?

```
┌─────────────┐     GET /api/*.php      ┌──────────────┐
│   Browser   │ ──────────────────────► │  PHP Backend │
│  (app.js)   │                         │  (api/*.php) │
└─────────────┘                         └──────┬───────┘
       │                                       │
       │ preload                               ▼
       │ /api/offline-bundle.php      ┌──────────────┐
       ▼                              │ PostgreSQL   │ ◄── primary
┌─────────────┐                        │  (afyalink)  │
│  IndexedDB  │                        └──────┬───────┘
│  + SW cache │                               │ sync
└─────────────┘                        ┌──────▼───────┐
                                       │   SQLite     │ ◄── fallback
                                       │  (.sqlite)   │
                                       └──────────────┘
```

**Endpoints muhimu:**

| Endpoint | Inapakia |
|----------|----------|
| `api/regions.php` | Mikoa + wilaya |
| `api/services.php` | Aina za huduma |
| `api/hospitals.php` | Hospitali + huduma zilizo wazi *sasa* |
| `api/stats.php` | Takwimu za dashboard |
| `api/offline-bundle.php` | **Kila kitu** kwa offline (389 hospitali) |
| `api/ai-chat.php` | Msaidizi wa AI (inahitaji mtandao) |
| `api/meta.php` | Taarifa ya driver/cache |

---

## Database inahifadhi nini?

| Jedwali | Yaliyomo |
|---------|----------|
| `regions` | Mikoa (Dar, Morogoro, Pwani, Iringa) |
| `districts` | Wilaya/council |
| `hospitals` | Hospitali kutoka MOH HFR – jina, code, simu, anuani, aina |
| `service_types` | Aina za huduma (maternity, lab, emergency, nk.) |
| `hospital_services` | Huduma gani hospitali inatoa |
| `service_schedules` | Ratiba ya wiki kwa huduma/wataalamu (saa za kufungua/kufunga) |
| `service_status_log` | **Updates za muda halisi** kutoka admin |

---

## Admin Panel – wapi?

**URL:** `http://localhost:8080/admin/`

Mfanyakazi wa hospitali:
1. Anaingia kwa **Facility Code** (HFR, mf. `105905-4`) + **PIN** (`.env` → `ADMIN_PIN`)
2. Anaona huduma za hospitali yake tu
3. Anasasisha: inapatikana / imepungua / haipatikani, dakika za kusubiri, maelezo
4. Mabadiliko yanaingia `service_status_log` → watumiaji wanaona mara moja

**Baada ya updates nyingi**, sync SQLite:
```powershell
php tools/sync_sqlite.php
```

---

## Amri muhimu

```powershell
php -S localhost:8080              # Endesha app
php setup.php                      # Schema + seed
php tools/import_hfr.php           # Pakia kutoka HFR
php tools/sync_sqlite.php          # PostgreSQL → SQLite backup
php tools/verify.php               # Angalia idadi
```
