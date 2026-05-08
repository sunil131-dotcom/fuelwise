-- ============================================================
-- FuelWise Database Schema v2.0
-- AI-Powered Personalized Nutrition System
-- Updated with: multi-condition support, 7-day meal plans,
-- exercises, notifications, streaks, large food DB
-- ============================================================
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS fuelwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fuelwise;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    date_of_birth DATE NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- HEALTH PROFILES
-- Expanded with new conditions + multi-condition support
-- ============================================================
CREATE TABLE IF NOT EXISTS health_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    age INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    height DECIMAL(5,2) NOT NULL,
    bmi DECIMAL(5,2),
    bmr DECIMAL(7,2),
    activity_level ENUM('sedentary','lightly_active','moderately_active','very_active','extra_active') DEFAULT 'sedentary',
    -- Primary condition (for backward compatibility)
    condition_type ENUM('diabetes','ibs','hypertension','prediabetes','weight_management','both','multiple') NOT NULL,
    -- Multiple conditions (comma-separated, e.g. "diabetes,hypertension")
    conditions_list VARCHAR(200) DEFAULT NULL,
    -- Clinical values (all optional — user may not know these)
    glucose_level DECIMAL(5,2) DEFAULT NULL,
    blood_pressure_systolic INT DEFAULT NULL,
    blood_pressure_diastolic INT DEFAULT NULL,
    blood_pressure VARCHAR(20) DEFAULT NULL,
    insulin_level DECIMAL(6,2) DEFAULT NULL,
    cholesterol_total DECIMAL(5,2) DEFAULT NULL,
    -- Dietary preferences
    dietary_preference ENUM('none','vegetarian','vegan','halal','kosher','gluten_free','pescatarian','low_carb') DEFAULT 'none',
    cultural_background ENUM('western','indian','asian','mediterranean','african','latin','other') DEFAULT 'western',
    allergies TEXT,
    -- Goals
    weight_goal ENUM('lose','maintain','gain') DEFAULT 'maintain',
    target_weight DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ============================================================
-- CURATED FOODS TABLE
-- Hand-curated complete meals with condition safety flags
-- ============================================================
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    calories DECIMAL(7,2),
    protein DECIMAL(6,2),
    carbohydrates DECIMAL(6,2),
    fat DECIMAL(6,2),
    fiber DECIMAL(6,2),
    sugar DECIMAL(6,2),
    sodium DECIMAL(7,2),
    potassium DECIMAL(7,2) DEFAULT 0,
    glycemic_index INT,
    is_diabetes_safe TINYINT(1) DEFAULT 0,
    is_ibs_safe TINYINT(1) DEFAULT 0,
    is_hypertension_safe TINYINT(1) DEFAULT 0,
    is_prediabetes_safe TINYINT(1) DEFAULT 0,
    is_weight_loss_safe TINYINT(1) DEFAULT 0,
    is_vegetarian TINYINT(1) DEFAULT 0,
    is_vegan TINYINT(1) DEFAULT 0,
    is_halal TINYINT(1) DEFAULT 0,
    is_kosher TINYINT(1) DEFAULT 0,
    is_gluten_free TINYINT(1) DEFAULT 0,
    ingredients TEXT,
    cultural_origin ENUM('western','indian','asian','mediterranean','african','latin','universal') DEFAULT 'universal',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_food_name (name),
    INDEX idx_category (category),
    INDEX idx_diabetes (is_diabetes_safe),
    INDEX idx_ibs (is_ibs_safe),
    INDEX idx_hypertension (is_hypertension_safe)
);

-- ============================================================
-- FOOD NUTRITION REFERENCE DATABASE (imported from CSVs)
-- ============================================================
CREATE TABLE IF NOT EXISTS food_nutrition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    caloric_value DECIMAL(7,2),
    fat DECIMAL(6,2),
    saturated_fats DECIMAL(6,2),
    monounsaturated_fats DECIMAL(6,2),
    polyunsaturated_fats DECIMAL(6,2),
    carbohydrates DECIMAL(6,2),
    sugars DECIMAL(6,2),
    protein DECIMAL(6,2),
    dietary_fiber DECIMAL(6,2),
    cholesterol DECIMAL(7,2),
    sodium DECIMAL(7,2),
    water DECIMAL(7,2),
    vitamin_a DECIMAL(7,4) DEFAULT 0,
    vitamin_b12 DECIMAL(7,4) DEFAULT 0,
    vitamin_c DECIMAL(7,4) DEFAULT 0,
    vitamin_d DECIMAL(7,4) DEFAULT 0,
    vitamin_e DECIMAL(7,4) DEFAULT 0,
    calcium DECIMAL(7,2) DEFAULT 0,
    iron DECIMAL(7,4) DEFAULT 0,
    magnesium DECIMAL(7,2) DEFAULT 0,
    phosphorus DECIMAL(7,2) DEFAULT 0,
    potassium DECIMAL(7,2) DEFAULT 0,
    zinc DECIMAL(7,4) DEFAULT 0,
    nutrition_density DECIMAL(7,2) DEFAULT 0,
    is_diabetes_safe TINYINT(1) DEFAULT 0,
    is_ibs_safe TINYINT(1) DEFAULT 0,
    is_hypertension_safe TINYINT(1) DEFAULT 0,
    is_prediabetes_safe TINYINT(1) DEFAULT 0,
    is_weight_loss_safe TINYINT(1) DEFAULT 0,
    is_vegetarian TINYINT(1) DEFAULT 1,
    is_vegan TINYINT(1) DEFAULT 0,
    is_halal TINYINT(1) DEFAULT 1,
    is_gluten_free TINYINT(1) DEFAULT 0,
    fodmap_level ENUM('low','moderate','high','unknown') DEFAULT 'unknown',
    gi_category ENUM('low','medium','high','unknown') DEFAULT 'unknown',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nutrition_name (name),
    INDEX idx_sodium (sodium),
    INDEX idx_sugars (sugars),
    INDEX idx_calories (caloric_value)
);

-- ============================================================
-- RECOMMENDATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    condition_type VARCHAR(50) NOT NULL,
    conditions_list VARCHAR(200),
    daily_calories DECIMAL(7,2),
    daily_protein DECIMAL(6,2),
    daily_carbs DECIMAL(6,2),
    daily_fat DECIMAL(6,2),
    daily_fiber DECIMAL(6,2) DEFAULT 25,
    daily_sodium DECIMAL(7,2) DEFAULT 2300,
    breakfast TEXT,
    lunch TEXT,
    dinner TEXT,
    snacks TEXT,
    notes TEXT,
    plan_type ENUM('single_day','weekly') DEFAULT 'weekly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at)
);

-- ============================================================
-- WEEKLY MEAL PLAN
-- ============================================================
CREATE TABLE IF NOT EXISTS weekly_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recommendation_id INT NOT NULL,
    user_id INT NOT NULL,
    day_number TINYINT NOT NULL,
    day_name VARCHAR(20),
    breakfast TEXT,
    lunch TEXT,
    dinner TEXT,
    snacks TEXT,
    day_calories DECIMAL(7,2),
    day_protein DECIMAL(6,2),
    day_carbs DECIMAL(6,2),
    day_fat DECIMAL(6,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recommendation_id) REFERENCES recommendations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_rec (recommendation_id),
    INDEX idx_day (recommendation_id, day_number)
);

-- ============================================================
-- EXERCISES (sources cited in source_citation column)
-- ============================================================
CREATE TABLE IF NOT EXISTS exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('cardio','strength','flexibility','balance','mixed') NOT NULL,
    intensity ENUM('light','moderate','vigorous') DEFAULT 'light',
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    duration_minutes INT DEFAULT 30,
    calories_per_30min INT DEFAULT 100,
    met_value DECIMAL(4,2) DEFAULT 3.0,
    body_part VARCHAR(50) DEFAULT NULL,
    muscle_group VARCHAR(100) DEFAULT NULL,
    sets VARCHAR(20) DEFAULT NULL,
    reps VARCHAR(20) DEFAULT NULL,
    is_diabetes_safe TINYINT(1) DEFAULT 1,
    is_ibs_safe TINYINT(1) DEFAULT 1,
    is_hypertension_safe TINYINT(1) DEFAULT 1,
    is_prediabetes_safe TINYINT(1) DEFAULT 1,
    is_weight_loss TINYINT(1) DEFAULT 1,
    min_activity_level ENUM('sedentary','lightly_active','moderately_active','very_active','extra_active') DEFAULT 'sedentary',
    equipment_needed VARCHAR(150) DEFAULT 'None',
    instructions TEXT,
    contraindications TEXT,
    source_citation VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exercise_name (name),
    INDEX idx_type (type),
    INDEX idx_intensity (intensity)
);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info','success','warning','reminder','streak','tip') DEFAULT 'info',
    icon VARCHAR(10) DEFAULT '🔔',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255) DEFAULT NULL,
    action_label VARCHAR(50) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);

-- ============================================================
-- USER STREAKS
-- ============================================================
CREATE TABLE IF NOT EXISTS user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_login_date DATE DEFAULT NULL,
    total_logins INT DEFAULT 0,
    last_plan_date DATE DEFAULT NULL,
    total_plans_generated INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- PROFILE UPDATES LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS profile_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    update_type ENUM('full_assessment','quick_update','regenerate') DEFAULT 'quick_update',
    fields_changed VARCHAR(500),
    old_values TEXT,
    new_values TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (created_at)
);

-- ============================================================
-- PASSWORD RESETS & ADMIN TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    otp_code VARCHAR(10),
    otp_expires_at DATETIME,
    otp_attempts INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin — password: Admin@123
INSERT IGNORE INTO admins (full_name, email, password) VALUES
('FuelWise Admin', 'admin@fuelwise.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
