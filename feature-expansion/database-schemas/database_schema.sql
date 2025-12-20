-- Mobile Mechanic Call Handling System Database Schema
-- This integrates with existing Rukovoditel CRM or creates standalone tables

-- Main customer calls table
CREATE TABLE IF NOT EXISTS customer_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(36) UNIQUE NOT NULL,
    call_sid VARCHAR(64),
    phone_number VARCHAR(20),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    address TEXT,
    vehicle_year VARCHAR(4),
    vehicle_make VARCHAR(50),
    vehicle_model VARCHAR(50),
    engine_size VARCHAR(20),
    problem_description TEXT,
    urgency TINYINT DEFAULT 3,
    status ENUM('in_progress', 'data_collected', 'confirmed', 'scheduled', 'completed') DEFAULT 'in_progress',
    source ENUM('phone_call', 'roadside_form', 'web_form') DEFAULT 'phone_call',
    recording_url VARCHAR(500),
    transcription_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_call_sid (call_sid),
    INDEX idx_phone (phone_number),
    INDEX idx_urgency (urgency),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Call recordings and transcriptions
CREATE TABLE IF NOT EXISTS call_recordings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(36),
    recording_url VARCHAR(500),
    transcription_text TEXT,
    ai_extracted_data JSON,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (call_id) REFERENCES customer_calls(call_id) ON DELETE CASCADE,
    INDEX idx_call_id (call_id),
    INDEX idx_status (processing_status)
);

-- SMS confirmations sent to customers
CREATE TABLE IF NOT EXISTS sms_confirmations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(36),
    phone_number VARCHAR(20),
    message_text TEXT,
    confirmation_url VARCHAR(500),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    clicked_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (call_id) REFERENCES customer_calls(call_id) ON DELETE CASCADE,
    INDEX idx_call_id (call_id),
    INDEX idx_phone (phone_number)
);

-- Sample data for testing
INSERT INTO customer_calls (
    call_id, phone_number, first_name, last_name, 
    address, vehicle_year, vehicle_make, vehicle_model,
    problem_description, urgency, status, source
) VALUES (
    'sample-123', '+19041234567', 'John', 'Smith',
    '123 Main St, St Augustine FL', '2018', 'Honda', 'Civic',
    'Engine making strange noise when starting', 4, 'confirmed', 'phone_call'
);

-- Create indexes for performance
CREATE INDEX idx_urgency_created ON customer_calls(urgency, created_at);
CREATE INDEX idx_status_urgency ON customer_calls(status, urgency);

-- View for high-priority customers
CREATE OR REPLACE VIEW high_priority_calls AS
SELECT 
    call_id,
    CONCAT(first_name, ' ', last_name) as customer_name,
    phone_number,
    address,
    CONCAT(vehicle_year, ' ', vehicle_make, ' ', vehicle_model) as vehicle,
    problem_description,
    urgency,
    status,
    created_at
FROM customer_calls 
WHERE urgency >= 4 
  AND status IN ('data_collected', 'confirmed')
ORDER BY urgency DESC, created_at ASC;