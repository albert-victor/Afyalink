-- AfyaLink SQLite fallback schema (mirrors PostgreSQL)

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS regions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL UNIQUE,
    name_sw     TEXT NOT NULL,
    created_at  TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS districts (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    region_id   INTEGER NOT NULL REFERENCES regions(id) ON DELETE CASCADE,
    name        TEXT NOT NULL,
    name_sw     TEXT NOT NULL,
    UNIQUE (region_id, name)
);

CREATE TABLE IF NOT EXISTS service_types (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    code        TEXT NOT NULL UNIQUE,
    name        TEXT NOT NULL,
    name_sw     TEXT NOT NULL,
    icon        TEXT DEFAULT 'medical',
    category    TEXT DEFAULT 'general'
);

CREATE TABLE IF NOT EXISTS hospitals (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    facility_code   TEXT UNIQUE,
    hfr_id          INTEGER,
    name            TEXT NOT NULL,
    name_sw         TEXT,
    district_id     INTEGER NOT NULL REFERENCES districts(id),
    facility_type   TEXT NOT NULL DEFAULT 'hospital',
    hfr_facility_type TEXT,
    council         TEXT,
    ownership       TEXT,
    operating_status TEXT DEFAULT 'Operating',
    address         TEXT,
    phone           TEXT,
    emergency_phone TEXT,
    latitude        REAL,
    longitude       REAL,
    is_24_7         INTEGER DEFAULT 0,
    is_active       INTEGER DEFAULT 1,
    data_source     TEXT DEFAULT 'MOH HFR Portal',
    created_at      TEXT DEFAULT (datetime('now')),
    updated_at      TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS hospital_services (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    hospital_id     INTEGER NOT NULL REFERENCES hospitals(id) ON DELETE CASCADE,
    service_type_id INTEGER NOT NULL REFERENCES service_types(id) ON DELETE CASCADE,
    is_available    INTEGER DEFAULT 1,
    notes           TEXT,
    notes_sw        TEXT,
    UNIQUE (hospital_id, service_type_id)
);

CREATE TABLE IF NOT EXISTS service_schedules (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    hospital_service_id INTEGER NOT NULL REFERENCES hospital_services(id) ON DELETE CASCADE,
    day_of_week         INTEGER NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    open_time           TEXT NOT NULL,
    close_time          TEXT NOT NULL,
    is_closed           INTEGER DEFAULT 0,
    UNIQUE (hospital_service_id, day_of_week)
);

CREATE TABLE IF NOT EXISTS service_status_log (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    hospital_service_id INTEGER NOT NULL REFERENCES hospital_services(id) ON DELETE CASCADE,
    status              TEXT NOT NULL DEFAULT 'available',
    wait_minutes        INTEGER,
    updated_by          TEXT,
    created_at          TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_hospitals_district ON hospitals(district_id);
CREATE INDEX IF NOT EXISTS idx_hospitals_facility_code ON hospitals(facility_code);
CREATE INDEX IF NOT EXISTS idx_hospital_services_hospital ON hospital_services(hospital_id);
CREATE INDEX IF NOT EXISTS idx_service_schedules_service ON service_schedules(hospital_service_id);
CREATE INDEX IF NOT EXISTS idx_status_log_service ON service_status_log(hospital_service_id, created_at);
