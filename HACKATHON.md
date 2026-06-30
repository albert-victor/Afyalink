# MUHAS Local AI Buildathon 2026 — Complete Application Guide

**Team:** AfyaLink  
**Form URL:** [Student Team Application Form](https://utafiti.muhas.ac.tz/surveys/?s=CRWJLLE3JCMCWWN3)  
**Deadline:** 30 June 2026

Use this document as your copy-paste reference. Tone: professional, precise, and reflective of disciplined teamwork.

---

## Product Reference (for your team)

**AfyaLink** is an offline-first hospital and specialist service availability platform for Tanzania. It surfaces which clinics — dental, cardiology, maternity, laboratory, emergency, and more — are open *right now*, with published daily schedules (e.g. dental clinic 09:00–20:00), live facility updates, and bilingual Swahili/English support.

- **448 hospitals** from MoH HFR across 5 regions  
- **20+ service types**, including 10+ specialist categories  
- **Not a clinical tool** — informational navigation only  

---

# SECTION 01 — TEAM LEADER INFORMATION

| Field | Response |
|-------|----------|
| **1.1 Team Name** | AfyaLink |
| **1.2 Team Leader Full Name** | *[Your full legal name]* |
| **1.3 Gender** | *[Select]* |
| **1.4 University / Institution** | *[Your institution]* |
| **1.5 Degree Programme & Year of Study** | *[e.g. BSc Computer Science, Year 3]* |
| **1.6 Team Leader Email** | *[Professional email]* |
| **1.7 WhatsApp Phone Number** | *[+255…]* |
| **1.8 How did you hear about the Buildathon?** | *[Select applicable source(s)]* |

---

# SECTION 02 — FULL TEAM COMPOSITION

*Members 1–4 are required. Members 5–6 are optional. Complete on behalf of the full team.*

For each member (m1–m6), provide:

| Field | Guidance |
|-------|----------|
| Full Name | Legal name as it appears on university records |
| Sex | As required by the form |
| University | Institution name |
| Programme & Year | e.g. MBChB Year 4; BSc Information Technology Year 2 |
| Email | Active student email |
| WhatsApp | Reachable number with country code |

**Team composition tip:** Present a deliberate blend — e.g. software engineering, health sciences, public health, and product/UX — to reinforce the multidisciplinary narrative in Section 07.

---

# SECTION 03 — HEALTHCARE PROBLEM STATEMENT

### 3.1 Does your team already have a specific healthcare problem in mind?

**Select:** Yes we have a specific idea

---

### 3.2 Which healthcare area best describes your focus?

**Select all that apply:**

- Rural Healthcare Access  
- Offline Healthcare AI Systems  
- Healthcare Language Tools (Swahili AI)  
- Community Healthcare / CHW Support  
- Medical Accessibility & Inclusion  

---

### 3.3 Describe the healthcare problem *(100 words)*

```
Across Tanzania, patients routinely undertake costly journeys to facilities only to discover that the specialist service they require — dental, cardiology, maternity, laboratory, or emergency care — is closed, at capacity, or operating outside any publicly visible schedule. National directories catalogue facilities; they do not communicate whether a specialist clinic is open at this moment or what today's operating window is. The resulting information gap wastes household income, prolongs suffering, and delays appropriate referrals. AfyaLink addresses this by surfacing real-time specialist and general service availability, publishing daily schedules, enabling facility-led status updates, and delivering the experience offline-first so patients can plan before they travel.
```

---

### 3.4 Why is this important in the Tanzanian context? *(100 words)*

```
Tanzania's health system comprises more than 14,000 registered facilities, yet specialist services remain concentrated, inconsistently scheduled, and largely invisible to the communities they serve. A dental clinic may operate only between 09:00 and 20:00; a cardiology unit may suspend services without public notice. Rural populations in Pwani and Morogoro absorb disproportionate transport burdens; urban patients in Dar es Salaam queue at facilities that lack the services they require. AfyaLink leverages authenticated MoH HFR data across 448 hospitals, combining weekly specialist schedules with live availability signals to empower patients, families, and community health workers — augmenting, never replacing, clinical judgement.
```

---

### 3.5 Why offline-first AI?

```
Connectivity in Tanzania is uneven. Patients and community health workers in peri-urban and rural settings frequently lack reliable mobile data at the point of need. An offline-first architecture ensures that hospital directories, specialist schedules, and recent availability states are cached locally through Service Worker and IndexedDB, remaining accessible after a single synchronisation. When connectivity permits, our bilingual AI assistant accelerates discovery; when it does not, structured search over cached data preserves full functionality. This design embodies equitable access and the programme's mandate: Local AI, Local Solutions, Local Impact.
```

---

# SECTION 04 — TEAM MOTIVATION & VISION

### 4.1 Why does your team want to join this programme? *(100 words)*

```
Our team is united by a shared conviction: Tanzanian patients deserve technology that performs in the field, not merely in theory. Collectively, we have already delivered AfyaLink — a functional MVP integrating 448 MoH HFR facilities, a specialist scheduling engine, facility-administered live status updates, an installable progressive web application, and a responsible bilingual AI layer. The Buildathon offers the mentorship, ethical AI training, and national platform we need to refine, validate, and scale our solution responsibly. We enter this programme prepared to learn with discipline, execute with accountability, and contribute meaningfully to Tanzania's digital health agenda.
```

---

### 4.2 What skills or knowledge does your team most want to gain? *(100 words)*

```
We seek structured advancement in responsible health AI — including clinical safety boundaries, Swahili natural language processing, and resilient offline deployment. Equally, we value mentorship on Ministry of Health stakeholder engagement, field research with patients and community health workers, and the operational knowledge required to scale open digital health infrastructure. Training in edge inference, health-data governance, and sustainable implementation models will directly strengthen AfyaLink's specialist scheduling module and inform our roadmap toward HMIS-aligned integration. We are committed to translating programme learning into durable public benefit.
```

---

### 4.3 What healthcare impact do you expect your solution to achieve? *(100 words)*

```
AfyaLink is designed to reduce unsuccessful care-seeking journeys by making specialist clinic availability legible in real time — including daily operating hours across 448 hospitals in five regions. Patients conserve transport expenditure; fewer individuals arrive at closed or capacity-constrained services. Community health workers counsel families using synchronised offline data. Facility personnel publish live status — available, limited, estimated wait — through a dedicated admin interface. The platform provides navigational intelligence only; it does not diagnose. The architecture is nationally extensible across the full MoH HFR registry.
```

---

# SECTION 05 — TEAM TECHNICAL BACKGROUND

### 5.1 Technical skills *(select all that apply)*

- Web development  
- Database management  
- Version control (Git / GitHub)  
- Python programming  
- Machine learning basics  
- Data analysis  

*Select only those your team can credibly demonstrate.*

---

### 5.2 Primary academic discipline

**Select:** Multi-disciplinary

*Alternative if more accurate:* Health Informatics / Digital Health, or Computer Science / IT / Software Engineering

---

### 5.3 Technical readiness

**Select:** 4 *(or Strong coding/AI experience if your team warrants it)*

---

### 5.4 Previous hackathon / innovation / research participation

**Select:** Yes

---

### 5.4a Describe the experience *(100 words)*

```
As a coordinated team, we architected and deployed AfyaLink ahead of this application: an offline-first care-navigation platform built on PHP, PostgreSQL, Service Worker, and IndexedDB, populated with 448 verified facilities from the Tanzania MoH HFR Portal. Our signature contribution is a specialist availability engine — weekly schedules and real-time status for dental, cardiology, surgical, optical, and allied services — coupled with a bilingual interface displaying today's operating window. SQLite fallback and a facility admin panel complete a production-minded MVP, demonstrating disciplined execution, clear role division, and collaborative delivery under a shared technical vision.
```

---

# SECTION 06 — COMMITMENT & LOGISTICAL READINESS

### 6.1 Commitment to weekly sessions

**Select:** Yes fully commit

---

### 6.2 Computer access

**Select:** All members (4–6)

*If not universally true, select the most accurate option and address gaps proactively in 8.3.*

---

### 6.3 Internet connectivity

**Select:** 3 or 4 *(adjust to your team's reality)*

---

### 6.4 Participation constraints

**Select:** None

*If applicable, select relevant constraints and complete the explanation fields with specific dates and mitigation plans.*

---

# SECTION 07 — TEAM STATEMENT & DIVERSITY

### 7.1 One-sentence pitch

```
AfyaLink empowers Tanzanians to locate hospitals and specialist clinics that are genuinely available right now — with transparent daily schedules, offline resilience, and informational clarity that never substitutes for clinical care.
```

---

### 7.2 Top three team strengths

```
1. Integrated specialist availability intelligence — our team engineered a scheduling and live-status layer covering 10+ specialist service categories across 448 MoH-verified hospitals, giving patients actionable visibility into clinic hours and real-time capacity.

2. Production-grade offline-first architecture — we delivered a full-stack platform (PHP, PostgreSQL, Service Worker, IndexedDB, SQLite fallback) designed for the connectivity realities of rural Pwani, Morogoro, and peri-urban Dar es Salaam.

3. Context-grounded, responsible innovation — we combine authenticated national health data, bilingual Swahili/English design, and an AI assistant bounded by explicit clinical disclaimers, reflecting disciplined teamwork across technical and health-domain expertise.
```

---

### 7.3 Diversity and inclusion statement

```
Our team is intentionally composed across complementary disciplines — software engineering, health sciences, and systems thinking — so that AfyaLink is shaped by the communities it serves, not built in isolation. We design for equitable access: patients seeking a dental specialist in Dar es Salaam, families navigating rural referral pathways in Pwani, and community health workers operating without reliable connectivity. We believe diverse perspectives produce more accountable health technology, and we hold ourselves to that standard in every design and engineering decision we make as a team.
```

*Personalise with your actual universities, programmes, and gender balance where appropriate.*

---

# SECTION 08 — DECLARATIONS & CONSENT

### 8.1 I have read and agree to all declarations

**Select:** Yes

---

### 8.2 Data Protection Consent

**Select:** Yes

---

### 8.3 Anything else you would like us to know about your team?

```
AfyaLink is not a concept — it is a working MVP. Our team has already integrated 448 facilities from the Tanzania MoH HFR Portal, implemented specialist schedule display (e.g. dental clinic hours 09:00–20:00), facility-administered live status updates, offline progressive web application functionality, and a bilingual AI assistant with explicit non-clinical guardrails. Repository: [INSERT GITHUB URL]. We welcome the opportunity to demonstrate our prototype and discuss a responsible national scale-up pathway. Theme alignment: Local AI, Local Solutions, Local Impact.
```

---

### Team Leader Name (Digital Signature)

*[Full legal name of Team Leader — must match Section 01]*

---

# APPENDIX — Demo Script for Evaluators

1. Open the application and acknowledge the clinical disclaimer.  
2. Filter by specialist service (Dental / Cardiology).  
3. Select a hospital and review today's schedule (e.g. *Today: 09:00 – 20:00*) and availability status.  
4. Toggle Swahili ↔ English.  
5. Disable connectivity — confirm offline cache retains schedules and search.  
6. Query the AI assistant: *"Which hospitals have dental services open right now?"*  
7. Demonstrate the facility admin panel for live status updates.  

---

# APPENDIX — Technical Stack Summary

| Layer | Technology |
|-------|------------|
| Backend | PHP 8, REST API |
| Database | PostgreSQL (+ SQLite fallback) |
| Frontend | HTML5, CSS3, vanilla JavaScript |
| Offline | Service Worker, IndexedDB |
| AI | OpenRouter API (bilingual, guardrailed) |
| Distribution | Progressive Web App (installable) |
| Data source | Tanzania MoH HFR Portal |

---

*Prepared for MUHAS Local AI Buildathon 2026 — AfyaLink Team*
