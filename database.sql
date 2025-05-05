-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    target_score DECIMAL(2,1) DEFAULT 0.0,
    test_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- IELTS Sections Table
CREATE TABLE ielts_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,  -- Reading, Writing, Listening, Speaking
    description TEXT NULL
);

-- Practice Tests Table
CREATE TABLE practice_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    test_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Test Scores Table
CREATE TABLE test_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    practice_test_id INT NOT NULL,
    section_id INT NOT NULL,
    score DECIMAL(2,1) NOT NULL,
    time_spent INT NULL,  -- Time spent in minutes
    details TEXT NULL,    -- JSON data for detailed breakdown
    FOREIGN KEY (practice_test_id) REFERENCES practice_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id)
);

-- Study Sessions Table
CREATE TABLE study_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    duration INT NULL,  -- Duration in minutes
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id)
);

-- Study Resources Table
CREATE TABLE study_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    section_id INT NOT NULL,
    resource_type VARCHAR(50) NOT NULL,  -- Practice Test, Vocabulary, etc.
    file_path VARCHAR(255) NULL,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id)
);

-- User Resources Tracking Table
CREATE TABLE user_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resource_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    rating INT NULL,  -- User rating of resource
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES study_resources(id) ON DELETE CASCADE
);

-- Study Goals Table
CREATE TABLE study_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NULL,  -- NULL for overall goals
    target_score DECIMAL(2,1) NOT NULL,
    target_date DATE NOT NULL,
    achieved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id)
);

-- Weak Areas Table
CREATE TABLE weak_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    section_id INT NOT NULL,
    sub_skill VARCHAR(100) NOT NULL,  -- "Skimming", "Grammar", etc.
    priority INT DEFAULT 1,  -- 1-5 scale
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id)
);

-- Study Plans Table
CREATE TABLE study_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'active',  -- active, completed, cancelled
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Study Plan Items Table
CREATE TABLE study_plan_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    study_plan_id INT NOT NULL,
    section_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    scheduled_date DATE NOT NULL,
    duration INT NOT NULL,  -- Duration in minutes
    completed BOOLEAN DEFAULT FALSE,
    resource_id INT NULL,  -- Optional reference to a resource
    FOREIGN KEY (study_plan_id) REFERENCES study_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES ielts_sections(id),
    FOREIGN KEY (resource_id) REFERENCES study_resources(id)
);