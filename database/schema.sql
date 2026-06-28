-- AfyaLink MVP Database Schema (PostgreSQL)
-- Hospital service availability – NOT a clinical decision tool

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS regions (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    name_sw     VARCHAR(100) NOT NULL,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS districts (
    id          SERIAL PRIMARY KEY,
    region_id   INTEGER NOT NULL REFERENCES regions(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL,
    name_sw     VARCHAR(100) NOT NULL,
    UNIQUE (region_id, name)
);

CREATE TABLE IF NOT EXISTS service_types (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(50) NOT NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    name_sw     VARCHAR(100) NOT NULL,
    icon        VARCHAR(50) DEFAULT 'medical',
    category    VARCHAR(50) DEFAULT 'general'
);

CREATE TABLE IF NOT EXISTS hospitals (
    id              SERIAL PRIMARY KEY,
    facility_code   VARCHAR(20) UNIQUE,
    hfr_id          INTEGER,
    name            VARCHAR(200) NOT NULL,
    name_sw         VARCHAR(200),
    district_id     INTEGER NOT NULL REFERENCES districts(id),
    facility_type   VARCHAR(50) NOT NULL DEFAULT 'hospital',
    hfr_facility_type VARCHAR(120),
    council         VARCHAR(100),
    ownership       VARCHAR(80),
    operating_status VARCHAR(30) DEFAULT 'Operating',
    address         TEXT,
    phone           VARCHAR(30),
    emergency_phone VARCHAR(30),
    latitude        DECIMAL(10, 7),
    longitude       DECIMAL(10, 7),
    is_24_7         BOOLEAN DEFAULT FALSE,
    is_active       BOOLEAN DEFAULT TRUE,
    data_source     VARCHAR(120) DEFAULT 'MOH HFR Portal',
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS hospital_services (
    id              SERIAL PRIMARY KEY,
    hospital_id     INTEGER NOT NULL REFERENCES hospitals(id) ON DELETE CASCADE,
    service_type_id INTEGER NOT NULL REFERENCES service_types(id) ON DELETE CASCADE,
    is_available    BOOLEAN DEFAULT TRUE,
    notes           TEXT,
    notes_sw        TEXT,
    UNIQUE (hospital_id, service_type_id)
);

-- Weekly schedule: day_of_week 0=Sunday .. 6=Saturday
CREATE TABLE IF NOT EXISTS service_schedules (
    id                  SERIAL PRIMARY KEY,
    hospital_service_id INTEGER NOT NULL REFERENCES hospital_services(id) ON DELETE CASCADE,
    day_of_week         SMALLINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    open_time           TIME NOT NULL,
    close_time          TIME NOT NULL,
    is_closed           BOOLEAN DEFAULT FALSE,
    UNIQUE (hospital_service_id, day_of_week)
);

-- Real-time status updates from facility staff (MVP: seeded + API)
CREATE TABLE IF NOT EXISTS service_status_log (
    id                  SERIAL PRIMARY KEY,
    hospital_service_id INTEGER NOT NULL REFERENCES hospital_services(id) ON DELETE CASCADE,
    status              VARCHAR(20) NOT NULL DEFAULT 'available',
    wait_minutes        INTEGER,
    updated_by          VARCHAR(100),
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_hospitals_district ON hospitals(district_id);
CREATE INDEX IF NOT EXISTS idx_hospitals_facility_code ON hospitals(facility_code);
CREATE INDEX IF NOT EXISTS idx_hospital_services_hospital ON hospital_services(hospital_id);
CREATE INDEX IF NOT EXISTS idx_service_schedules_service ON service_schedules(hospital_service_id);
CREATE INDEX IF NOT EXISTS idx_status_log_service ON service_status_log(hospital_service_id, created_at DESC);

CREATE OR REPLACE FUNCTION update_hospital_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_hospitals_updated ON hospitals;
CREATE TRIGGER trg_hospitals_updated
    BEFORE UPDATE ON hospitals
    FOR EACH ROW EXECUTE FUNCTION update_hospital_timestamp();
