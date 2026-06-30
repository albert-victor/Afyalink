# AfyaLink MVP

**Offline-first hospital service availability finder for Tanzania**

> AfyaLink is **NOT** a replacement for doctors or clinicians. It shows which hospitals have which services available **right now** – Dar es Salaam, Pwani, and Morogoro.

Built for **MUHAS Local AI Buildathon 2026** – theme: *Local AI, Local Solutions, Local Impact*.

## Quick Start

### 1. Prerequisites

- PHP 8.1+ with `pdo_pgsql` and `curl` extensions
- PostgreSQL 14+
- Apache (XAMPP) or similar web server

### 2. Configure environment

Copy `.env.example` to `.env` and set your credentials:

```env
DB_NAME=afyalink
DB_USER=postgres
DB_PASS=your_password
OPENROUTER_API_KEY=your_key
```

### 3. Create database & import MOH HFR data

```powershell
php setup.php
php tools/import_hfr.php
```

This imports **344 real operating facilities** from [MOH HFR Portal](https://hfrportal.moh.go.tz/web/index.php) for Dar es Salaam, Morogoro, and Pwani – including MUHIMBILI, AMANA, MWANANYAMALA, Temeke, Morogoro RRH, TUMBI, and district/health centers.

### 4. Open the app

```
http://localhost/Afyalink/
```

## Features (MVP)

| Feature | Description |
|---------|-------------|
| **Real-time availability** | Shows which services are open *now* based on schedules + live status |
| **Specialist schedules** | Daily hours per clinic (e.g. dental 09:00–20:00) with open/closed/limited status |
| **5 regions** | Dar es Salaam, Pwani, Morogoro, Iringa, Mwanza – 448 hospitals |
| **20 service types** | Emergency, maternity, lab, dental, cardiology, surgery, etc. |
| **Offline-first** | Service Worker + IndexedDB cache – works without internet |
| **AI assistant** | OpenRouter-powered helper (finds hospitals, never diagnoses) |
| **Swahili + English** | Bilingual UI |
| **PWA ready** | Installable on mobile devices |

## Tech Stack

- **Frontend:** HTML5, CSS3, vanilla JavaScript
- **Backend:** PHP 8 (REST API)
- **Database:** PostgreSQL
- **AI:** OpenRouter API (Llama 3.2)
- **Offline:** Service Worker + IndexedDB

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/regions.php` | GET | Regions + districts |
| `/api/services.php` | GET | Service types |
| `/api/hospitals.php` | GET | List/search hospitals |
| `/api/hospitals.php?id=1` | GET | Hospital detail + services |
| `/api/stats.php` | GET | Dashboard stats |
| `/api/ai-chat.php` | POST | AI assistant |

## Hackathon Alignment

See [HACKATHON.md](HACKATHON.md) for pre-written answers matching the MUHAS application form.

## Disclaimer

AfyaLink provides **informational** hospital service availability only. For medical advice, consult a qualified healthcare professional. For emergencies, call **114**.

## License

MIT – MUHAS Local AI Buildathon 2026
