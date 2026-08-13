CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    external_subject VARCHAR(191) NOT NULL,
    email VARCHAR(191) DEFAULT NULL,
    display_name VARCHAR(191) NOT NULL,
    role ENUM('viewer', 'editor', 'admin') NOT NULL DEFAULT 'viewer',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_external_subject (external_subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE device_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    manufacturer VARCHAR(191) NOT NULL,
    model VARCHAR(191) NOT NULL,
    description TEXT DEFAULT NULL,
    tx_power_dbm DECIMAL(5,2) NOT NULL,
    antenna_gain_dbi DECIMAL(5,2) DEFAULT NULL,
    minimum_calibration_pairs TINYINT UNSIGNED NOT NULL DEFAULT 3,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_device_types_manufacturer_model (manufacturer, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE measurement_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(191) NOT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    environment ENUM('indoor', 'outdoor', 'underground', 'mixed', 'unknown') NOT NULL DEFAULT 'unknown',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE measurements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    location_id BIGINT UNSIGNED NOT NULL,
    device_type_id BIGINT UNSIGNED DEFAULT NULL,
    source ENUM('field_tester', 'device') NOT NULL,
    pair_identifier CHAR(36) DEFAULT NULL,
    gateway_identifier VARCHAR(191) DEFAULT NULL,
    measured_at DATETIME NOT NULL,
    rssi_dbm DECIMAL(6,2) NOT NULL,
    snr_db DECIMAL(6,2) NOT NULL,
    spreading_factor TINYINT UNSIGNED NOT NULL,
    tx_power_dbm DECIMAL(5,2) NOT NULL,
    frequency_hz INT UNSIGNED DEFAULT NULL,
    data_rate VARCHAR(32) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_measurements_pair (pair_identifier),
    KEY ix_measurements_device_type (device_type_id),
    KEY ix_measurements_location (location_id),
    CONSTRAINT fk_measurements_location FOREIGN KEY (location_id) REFERENCES measurement_locations (id),
    CONSTRAINT fk_measurements_device_type FOREIGN KEY (device_type_id) REFERENCES device_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE device_calibrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_type_id BIGINT UNSIGNED NOT NULL,
    sample_count SMALLINT UNSIGNED NOT NULL,
    median_rssi_delta_db DECIMAL(6,2) NOT NULL,
    median_snr_delta_db DECIMAL(6,2) NOT NULL,
    rssi_spread_db DECIMAL(6,2) NOT NULL,
    snr_spread_db DECIMAL(6,2) NOT NULL,
    calculated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_calibrations_device_type (device_type_id),
    CONSTRAINT fk_calibrations_device_type FOREIGN KEY (device_type_id) REFERENCES device_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE evaluations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    field_measurement_id BIGINT UNSIGNED NOT NULL,
    device_type_id BIGINT UNSIGNED NOT NULL,
    calibration_id BIGINT UNSIGNED NOT NULL,
    estimated_rssi_dbm DECIMAL(6,2) NOT NULL,
    estimated_snr_db DECIMAL(6,2) NOT NULL,
    link_margin_db DECIMAL(6,2) NOT NULL,
    verdict ENUM('suitable', 'marginal', 'unsuitable', 'insufficient_data') NOT NULL,
    explanation TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_evaluations_measurement (field_measurement_id),
    CONSTRAINT fk_evaluations_measurement FOREIGN KEY (field_measurement_id) REFERENCES measurements (id),
    CONSTRAINT fk_evaluations_device_type FOREIGN KEY (device_type_id) REFERENCES device_types (id),
    CONSTRAINT fk_evaluations_calibration FOREIGN KEY (calibration_id) REFERENCES device_calibrations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
