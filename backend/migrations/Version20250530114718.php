<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530114718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE designs (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, data JSON NOT NULL, width INT NOT NULL, height INT NOT NULL, background JSON NOT NULL, animation_settings JSON NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, has_animation TINYINT(1) NOT NULL, fps DOUBLE PRECISION DEFAULT NULL, duration DOUBLE PRECISION DEFAULT NULL, thumbnail VARCHAR(500) DEFAULT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_3D1AB0FDD17F50A6 (uuid), INDEX IDX_3D1AB0FD166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE export_jobs (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, format VARCHAR(20) NOT NULL, quality VARCHAR(20) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, scale INT DEFAULT NULL, transparent TINYINT(1) DEFAULT 0 NOT NULL, background_color VARCHAR(7) DEFAULT NULL, animation_settings JSON DEFAULT NULL, status VARCHAR(20) NOT NULL, progress INT DEFAULT 0 NOT NULL, file_path VARCHAR(255) DEFAULT NULL, file_name VARCHAR(100) DEFAULT NULL, file_size BIGINT DEFAULT NULL, mime_type VARCHAR(50) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, error_details JSON DEFAULT NULL, processing_time_ms INT DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, user_id INT NOT NULL, design_id INT NOT NULL, UNIQUE INDEX UNIQ_8E225B2ED17F50A6 (uuid), INDEX IDX_8E225B2EA76ED395 (user_id), INDEX IDX_8E225B2EE41DC9B2 (design_id), INDEX idx_export_status (status), INDEX idx_export_created (created_at), INDEX idx_user_export_status (user_id, status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE layers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, properties JSON NOT NULL, transform JSON NOT NULL, z_index INT NOT NULL, visible TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, opacity DOUBLE PRECISION NOT NULL, animations JSON DEFAULT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, mask JSON DEFAULT NULL, design_id INT NOT NULL, parent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E688ED50D17F50A6 (uuid), INDEX IDX_E688ED50E41DC9B2 (design_id), INDEX IDX_E688ED50727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, url VARCHAR(500) NOT NULL, thumbnail_url VARCHAR(500) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, duration INT DEFAULT NULL, metadata JSON DEFAULT NULL, tags JSON NOT NULL, source VARCHAR(100) DEFAULT NULL, source_id VARCHAR(255) DEFAULT NULL, attribution VARCHAR(500) DEFAULT NULL, license VARCHAR(500) DEFAULT NULL, is_premium TINYINT(1) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6A2CA10CD17F50A6 (uuid), INDEX idx_media_type (type), INDEX idx_media_user (user_id), INDEX idx_media_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE plugins (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, identifier VARCHAR(100) NOT NULL, version VARCHAR(20) NOT NULL, status VARCHAR(50) NOT NULL, manifest JSON NOT NULL, permissions JSON NOT NULL, entry_point VARCHAR(500) NOT NULL, icon_url VARCHAR(500) DEFAULT NULL, banner_url VARCHAR(500) DEFAULT NULL, categories JSON NOT NULL, tags JSON NOT NULL, is_premium TINYINT(1) DEFAULT 0 NOT NULL, price NUMERIC(8, 2) DEFAULT NULL, install_count INT DEFAULT 0 NOT NULL, rating NUMERIC(3, 2) DEFAULT '0.00' NOT NULL, rating_count INT DEFAULT 0 NOT NULL, security_scan JSON DEFAULT NULL, review_notes LONGTEXT DEFAULT NULL, reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_EC85F671D17F50A6 (uuid), INDEX IDX_EC85F671FC6B21F1 (reviewed_by_id), INDEX idx_plugin_status (status), INDEX idx_plugin_user (user_id), INDEX idx_plugin_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_public TINYINT(1) NOT NULL, thumbnail VARCHAR(500) DEFAULT NULL, tags JSON NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_5C93B3A4D17F50A6 (uuid), INDEX IDX_5C93B3A4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE templates (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, category VARCHAR(100) NOT NULL, tags JSON NOT NULL, width INT NOT NULL, height INT NOT NULL, canvas_settings JSON NOT NULL, layers JSON NOT NULL, thumbnail_url VARCHAR(500) DEFAULT NULL, preview_url VARCHAR(500) DEFAULT NULL, is_premium TINYINT(1) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, usage_count INT DEFAULT 0 NOT NULL, rating NUMERIC(3, 2) DEFAULT '0.00' NOT NULL, rating_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6F287D8ED17F50A6 (uuid), INDEX IDX_6F287D8EB03A8386 (created_by_id), INDEX idx_template_category (category), INDEX idx_template_premium (is_premium), INDEX idx_template_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, username VARCHAR(100) NOT NULL, is_active TINYINT(1) NOT NULL, email_verified TINYINT(1) NOT NULL, last_login_at DATETIME DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, plan VARCHAR(50) NOT NULL, settings JSON NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, failed_login_attempts INT NOT NULL, locked_until DATETIME DEFAULT NULL, password_reset_token VARCHAR(255) DEFAULT NULL, password_reset_token_expires_at DATETIME DEFAULT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, email_verification_token_expires_at DATETIME DEFAULT NULL, timezone VARCHAR(50) DEFAULT NULL, language VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E9D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE designs ADD CONSTRAINT FK_3D1AB0FD166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE export_jobs ADD CONSTRAINT FK_8E225B2EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE export_jobs ADD CONSTRAINT FK_8E225B2EE41DC9B2 FOREIGN KEY (design_id) REFERENCES designs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE layers ADD CONSTRAINT FK_E688ED50E41DC9B2 FOREIGN KEY (design_id) REFERENCES designs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE layers ADD CONSTRAINT FK_E688ED50727ACA70 FOREIGN KEY (parent_id) REFERENCES layers (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plugins ADD CONSTRAINT FK_EC85F671A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plugins ADD CONSTRAINT FK_EC85F671FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES users (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates ADD CONSTRAINT FK_6F287D8EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE designs DROP FOREIGN KEY FK_3D1AB0FD166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE export_jobs DROP FOREIGN KEY FK_8E225B2EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE export_jobs DROP FOREIGN KEY FK_8E225B2EE41DC9B2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE layers DROP FOREIGN KEY FK_E688ED50E41DC9B2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE layers DROP FOREIGN KEY FK_E688ED50727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plugins DROP FOREIGN KEY FK_EC85F671A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE plugins DROP FOREIGN KEY FK_EC85F671FC6B21F1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates DROP FOREIGN KEY FK_6F287D8EB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE designs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE export_jobs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE layers
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE media
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE plugins
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projects
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE templates
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
