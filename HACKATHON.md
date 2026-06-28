# MUHAS Local AI Buildathon 2026 – Application Guide

Use these pre-written answers when filling the [Student Team Application Form](https://utafiti.muhas.ac.tz/surveys/?s=CRWJLLE3JCMCWWN3).

---

## Section 03 – Healthcare Problem Statement

### 3.1 Does your team already have a specific healthcare problem in mind?
**Yes**

### 3.2 Which healthcare area best describes your focus?
- **Health systems / infrastructure**
- **Digital health / health information**

### 3.3 Describe the healthcare problem (100 words)

Patients in Tanzania often travel long distances to hospitals only to find that the service they need – maternity, laboratory, X-ray, emergency – is closed or unavailable. There is no simple, real-time way to know which hospital has which service open *right now*, especially in Dar es Salaam, Pwani, and Morogoro. Existing directories list facilities but not live availability. This wastes time, money, and can delay care. AfyaLink solves this by showing hospital service availability in real time, offline-first, so even users without reliable internet can find open services nearby.

### 3.4 Why important in Tanzanian context? (100 words)

Tanzania has over 14,000 health facilities (MoH HFR) but patients lack visibility into what is actually available today. Rural and peri-urban areas in Pwani and Morogoro face transport costs and unreliable connectivity. Urban Dar es Salaam has many facilities but overcrowding at the wrong ones. An offline-first tool reduces wasted journeys, helps families choose the right facility first, and supports referral decisions – without replacing clinicians. It aligns with Digital Health Strategy goals and Local AI sovereignty by running on local data with optional AI assistance.

### 3.5 Why offline-first AI?

Many patients and community health workers operate with intermittent or no mobile data. Offline-first design caches hospital and service data locally (Service Worker + IndexedDB) so the app works after one sync. AI via OpenRouter assists when online but degrades gracefully offline with cached search. This ensures equitable access in Pwani villages, Morogoro rural areas, and low-connectivity parts of Dar es Salaam – true Local AI, Local Impact.

---

## Section 04 – Team Motivation & Vision

### 4.1 Why join the programme?
*(Customize with your team story)*

We want to build practical health technology that serves Tanzanian patients today – not theoretical AI. The Buildathon offers mentorship, responsible AI training, and a path to showcase at the Digital Health Forum.

### 4.3 Expected healthcare impact (100 words)

AfyaLink reduces failed hospital visits by showing live service availability across 12 hospitals in 3 regions (MVP). Patients save transport costs and time; fewer arrive at closed maternity or lab units. CHWs can advise communities using cached offline data. The system does not diagnose – it directs people to appropriate facilities faster. Scalable to all MoH HFR facilities nationwide. Measurable impact: reduced repeat visits, faster emergency routing, improved facility utilization transparency.

---

## Section 07 – Team Statement

### 7.1 One-sentence pitch

**AfyaLink: Pata hospitali na huduma zinazopatikana sasa hivi – offline-first, si daktari, ni taarifa.**

*(English: AfyaLink shows which hospitals have which services open right now – offline-first, informational only, not a doctor.)*

### 7.2 Top three team strengths
1. Full-stack development (PHP, PostgreSQL, modern web)
2. Understanding of Tanzanian healthcare context and MoH data
3. Offline-first architecture and responsible AI integration

---

## Demo Checklist for Judges

- [ ] Open `http://localhost/Afyalink/`
- [ ] Show disclaimer: NOT a doctor replacement
- [ ] Filter by region (Dar / Pwani / Morogoro)
- [ ] Filter by service (e.g. Emergency, Maternity)
- [ ] Click hospital → see services open *now*
- [ ] Toggle Swahili ↔ English
- [ ] Turn off WiFi → app still works (offline cache)
- [ ] Open AI assistant → ask "Hospitali gani ina huduma ya uzazi wazi sasa?"
- [ ] Show stats: hospitals, services open now

## Repository Structure

```
Afyalink/
├── index.php          # Main app
├── api/               # REST endpoints
├── config/            # DB + OpenRouter config
├── database/          # schema.sql + seed.sql
├── assets/            # CSS, JS, icons
├── sw.js              # Service Worker
├── manifest.json      # PWA manifest
├── setup.php          # DB installer
└── .env               # Secrets (not in git)
```

## Tech Stack (for form Section 05)

- PHP 8, PostgreSQL
- HTML5, CSS3, JavaScript (vanilla)
- OpenRouter API (Llama 3.2)
- Service Worker + IndexedDB (offline-first)
- PWA (installable)
