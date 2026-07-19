-- ============================================================
-- Hospital Management System - Database Schema
-- Engine: MySQL / MariaDB (InnoDB)
-- Import this file in phpMyAdmin or via:
--   mysql -u root -p < hms_schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS hms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hms_db;

-- ------------------------------------------------------------
-- 1. USERS (login accounts for Admin, Doctor, Receptionist)
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,           -- bcrypt hash
    role ENUM('admin','doctor','receptionist') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. DOCTORS (extends users with role='doctor')
-- ------------------------------------------------------------
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    qualification VARCHAR(150),
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    available_days VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
    available_time_start TIME DEFAULT '09:00:00',
    available_time_end TIME DEFAULT '17:00:00',
    room_no VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. PATIENTS
-- ------------------------------------------------------------
CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) NOT NULL UNIQUE,   -- e.g. PT-0001
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    dob DATE,
    age INT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    blood_group VARCHAR(5),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    registered_by INT,                          -- user_id of receptionist/admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registered_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. WARDS & BEDS
-- ------------------------------------------------------------
CREATE TABLE wards (
    ward_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(50) NOT NULL,
    ward_type ENUM('General','ICU','Private','Semi-Private','Emergency') DEFAULT 'General',
    total_beds INT NOT NULL DEFAULT 0,
    floor_no VARCHAR(10),
    charge_per_day DECIMAL(10,2) DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE beds (
    bed_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    bed_number VARCHAR(20) NOT NULL,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    FOREIGN KEY (ward_id) REFERENCES wards(ward_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_ward_bed (ward_id, bed_number)
) ENGINE=InnoDB;

CREATE TABLE admissions (
    admission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    bed_id INT NOT NULL,
    doctor_id INT,
    admission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    discharge_date DATETIME NULL,
    reason TEXT,
    status ENUM('admitted','discharged') DEFAULT 'admitted',
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (bed_id) REFERENCES beds(bed_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. APPOINTMENTS (also used as the basis for consultations)
-- ------------------------------------------------------------
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. LAB TESTS
-- ------------------------------------------------------------
CREATE TABLE lab_test_catalog (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    test_price DECIMAL(10,2) DEFAULT 0,
    normal_range VARCHAR(100)
) ENGINE=InnoDB;

CREATE TABLE lab_tests (
    lab_test_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    test_id INT NOT NULL,
    test_date DATE DEFAULT (CURRENT_DATE),
    result_value VARCHAR(255),
    result_notes TEXT,
    status ENUM('pending','completed') DEFAULT 'pending',
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL,
    FOREIGN KEY (test_id) REFERENCES lab_test_catalog(test_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. PHARMACY / MEDICINE INVENTORY
-- ------------------------------------------------------------
CREATE TABLE medicines (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    manufacturer VARCHAR(100),
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_qty INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 10,
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    prescribed_date DATE DEFAULT (CURRENT_DATE),
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE prescription_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    dosage VARCHAR(50),       -- e.g. "1-0-1"
    duration_days INT,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. BILLING / INVOICES
-- ------------------------------------------------------------
CREATE TABLE invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(20) NOT NULL UNIQUE,    -- e.g. INV-0001
    patient_id INT NOT NULL,
    appointment_id INT NULL,
    admission_id INT NULL,
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    medicine_charges DECIMAL(10,2) DEFAULT 0,
    lab_charges DECIMAL(10,2) DEFAULT 0,
    ward_charges DECIMAL(10,2) DEFAULT 0,
    other_charges DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_status ENUM('paid','unpaid','partial') DEFAULT 'unpaid',
    payment_method ENUM('cash','card','upi','insurance','other') DEFAULT 'cash',
    amount_paid DECIMAL(10,2) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (admission_id) REFERENCES admissions(admission_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SEED DATA
-- ------------------------------------------------------------

-- Default users. Password for ALL three accounts below is: Admin@123
-- (bcrypt hash - verified working with PHP's password_verify())
INSERT INTO users (username, password, role, full_name, email, phone) VALUES
('admin', '$2b$10$4Sh86c7ZmwO1A1U.zZM9ruP9XRGOPKylx9QHCWUv32J/YAGzFgLna', 'admin', 'System Administrator', 'admin@hms.local', '9999999999'),
('drsmith', '$2b$10$4Sh86c7ZmwO1A1U.zZM9ruP9XRGOPKylx9QHCWUv32J/YAGzFgLna', 'doctor', 'John Smith', 'drsmith@hms.local', '9888888888'),
('reception1', '$2b$10$4Sh86c7ZmwO1A1U.zZM9ruP9XRGOPKylx9QHCWUv32J/YAGzFgLna', 'receptionist', 'Priya Sharma', 'reception1@hms.local', '9777777777');

INSERT INTO doctors (user_id, specialization, qualification, experience_years, consultation_fee, room_no) VALUES
(2, 'Cardiology', 'MBBS, MD (Cardiology)', 12, 500.00, '201');

INSERT INTO wards (ward_name, ward_type, total_beds, floor_no, charge_per_day) VALUES
('General Ward A', 'General', 10, '1', 500.00),
('ICU', 'ICU', 5, '2', 2500.00),
('Private Ward', 'Private', 6, '3', 1500.00);

-- Auto-generate beds for each ward
INSERT INTO beds (ward_id, bed_number, status)
SELECT 1, CONCAT('A-', LPAD(n, 2, '0')), 'available' FROM (
    SELECT ROW_NUMBER() OVER () AS n FROM information_schema.columns LIMIT 10
) t;

INSERT INTO beds (ward_id, bed_number, status)
SELECT 2, CONCAT('ICU-', LPAD(n, 2, '0')), 'available' FROM (
    SELECT ROW_NUMBER() OVER () AS n FROM information_schema.columns LIMIT 5
) t;

INSERT INTO beds (ward_id, bed_number, status)
SELECT 3, CONCAT('P-', LPAD(n, 2, '0')), 'available' FROM (
    SELECT ROW_NUMBER() OVER () AS n FROM information_schema.columns LIMIT 6
) t;

INSERT INTO lab_test_catalog (test_name, test_price, normal_range) VALUES
('Complete Blood Count (CBC)', 300.00, 'Varies by component'),
('Blood Sugar (Fasting)', 150.00, '70-100 mg/dL'),
('Lipid Profile', 600.00, 'Varies by component'),
('Liver Function Test', 700.00, 'Varies by component'),
('X-Ray Chest', 400.00, 'N/A'),
('ECG', 250.00, 'N/A');

INSERT INTO medicines (medicine_name, category, manufacturer, unit_price, stock_qty, reorder_level, expiry_date) VALUES
('Paracetamol 500mg', 'Analgesic', 'Generic Pharma', 2.00, 500, 50, '2027-12-31'),
('Amoxicillin 250mg', 'Antibiotic', 'Generic Pharma', 5.50, 200, 30, '2027-06-30'),
('Cetirizine 10mg', 'Antihistamine', 'Generic Pharma', 1.50, 300, 40, '2027-09-30'),
('Omeprazole 20mg', 'Antacid', 'Generic Pharma', 3.00, 150, 20, '2027-03-31'),
('Metformin 500mg', 'Antidiabetic', 'Generic Pharma', 2.50, 250, 30, '2027-11-30');
