-- AfyaLink MVP – Base seed (regions + service types only)
-- Hospital data imported from MOH HFR Portal via: php tools/import_hfr.php

INSERT INTO regions (name, name_sw) VALUES
    ('Dar es Salaam', 'Dar es Salaam'),
    ('Morogoro', 'Morogoro'),
    ('Pwani', 'Pwani'),
    ('Iringa', 'Iringa'),
    ('Mwanza', 'Mwanza'),
    ('Mbeya', 'Mbeya'),
    ('Dodoma', 'Dodoma'),
    ('Arusha', 'Arusha')
ON CONFLICT (name) DO NOTHING;

INSERT INTO service_types (code, name, name_sw, icon, category) VALUES
    ('emergency',    'Emergency / A&E',       'Dharura / A&E',           'ambulance',  'critical'),
    ('maternity',    'Maternity & Delivery',  'Uzazi na Ujauzito',       'baby',       'specialist'),
    ('laboratory',   'Laboratory',            'Maabara',                 'flask',      'diagnostic'),
    ('xray',         'X-Ray & Imaging',       'X-Ray na Picha',          'scan',       'diagnostic'),
    ('pharmacy',     'Pharmacy',              'Duka la Dawa',            'pill',       'support'),
    ('surgery',      'Surgery',               'Upasuaji',                'scalpel',    'specialist'),
    ('pediatrics',   'Pediatrics / IMCI',     'Watoto',                  'child',      'specialist'),
    ('cardiology',   'Cardiology',            'Moyo',                    'heart',      'specialist'),
    ('dental',       'Dental',                'Menyu',                   'tooth',      'specialist'),
    ('mental',       'Mental Health',         'Afya ya Akili',           'brain',      'specialist'),
    ('blood_bank',   'Blood Bank',            'Benki ya Damu',           'blood',      'critical'),
    ('hiv',          'HIV/AIDS Care',         'Huduma za VVU/UKIMWI',    'ribbon',     'specialist'),
    ('physiotherapy','Physiotherapy',         'Tiba ya Mwili',           'walk',       'support'),
    ('optical',      'Optical / Eye Care',    'Macho',                   'eye',        'specialist'),
    ('dialysis',     'Dialysis',              'Dialysis',                'kidney',     'specialist'),
    ('opd',          'Outpatient (OPD)',      'Huduma za Nje (OPD)',     'medical',    'general'),
    ('ipd',          'Inpatient (IPD)',       'Wagonjwa wa Kulala',      'bed',        'general'),
    ('immunization', 'Immunization',          'Chanjo',                  'syringe',    'support'),
    ('tb',           'TB Care',               'Huduma za Kifua Kikuu',   'lungs',      'specialist'),
    ('malaria',      'Malaria Care',          'Huduma za Malaria',       'mosquito',   'specialist')
ON CONFLICT (code) DO NOTHING;
