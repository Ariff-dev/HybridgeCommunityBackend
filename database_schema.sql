-- Database Schema for HybridgeCommunityBackend
-- Run this script to create the necessary tables

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id_user VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refresh tokens table
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    token VARCHAR(500) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Board table (if not exists)
CREATE TABLE IF NOT EXISTS board (
    id_board INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    state VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned VARCHAR(36),
    FOREIGN KEY (assigned) REFERENCES users(id_user) ON DELETE SET NULL,
    INDEX idx_state (state),
    INDEX idx_assigned (assigned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add total_posts column to users table (for blog functionality)
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_posts INT DEFAULT 0;

-- Blog posts table
CREATE TABLE IF NOT EXISTS blog_posts (
    id_post VARCHAR(36) PRIMARY KEY,
    author_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content_markdown LONGTEXT NOT NULL,
    excerpt TEXT,
    cover_image_url VARCHAR(500),
    status ENUM('draft', 'published') DEFAULT 'draft',
    likes_count INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id_user) ON DELETE CASCADE,
    INDEX idx_author_id (author_id),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post images table (for additional images in posts)
CREATE TABLE IF NOT EXISTS post_images (
    id_image INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(36) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    cloudinary_id VARCHAR(255),
    alt_text VARCHAR(255),
    position INT DEFAULT 1,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id_post) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post likes table (tracks which users liked which posts)
CREATE TABLE IF NOT EXISTS post_likes (
    id_like INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id_post) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
    UNIQUE KEY unique_post_user_like (post_id, user_id),
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags table (for categorizing posts)
CREATE TABLE IF NOT EXISTS tags (
    id_tag INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post tags junction table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS post_tags (
    post_id VARCHAR(36) NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id_post) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id_tag) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*
======================================
DEPRECATED: API Keys System
======================================
Removed in favor of JWT authentication
Migration date: 2025-12-27

CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    permissions JSON,
    is_active BOOLEAN DEFAULT 1,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_value (key_value),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/
