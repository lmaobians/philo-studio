<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the unified account fields used by administrators and staff.
 *
 * The legacy admins table only stored username/password/full_name/created_at.
 * This narrow migration intentionally avoids a full schema diff so unrelated
 * pre-existing schema drift is left untouched.
 */
final class Version20261111000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email, role, status and updated_at to admins for admin/staff accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admins ADD email VARCHAR(180) DEFAULT NULL");
        $this->addSql("ALTER TABLE admins ADD role VARCHAR(20) NOT NULL DEFAULT 'ROLE_ADMIN'");
        $this->addSql("ALTER TABLE admins ADD status VARCHAR(20) NOT NULL DEFAULT 'Active'");
        $this->addSql("ALTER TABLE admins ADD updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");

        // The original administrator keeps its historical login and gains the
        // new email address so password recovery/profile screens have a value.
        $this->addSql("UPDATE admins SET email = 'admin@philostudio.com' WHERE admin_id = 1 AND email IS NULL");
        $this->addSql("UPDATE admins SET role = 'ROLE_ADMIN' WHERE role IS NULL OR role = ''");
        $this->addSql("UPDATE admins SET status = 'Active' WHERE status IS NULL OR status = ''");
        $this->addSql('UPDATE admins SET updated_at = created_at WHERE updated_at IS NULL');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMINS_EMAIL ON admins (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ADMINS_EMAIL ON admins');
        $this->addSql('ALTER TABLE admins DROP updated_at');
        $this->addSql('ALTER TABLE admins DROP status');
        $this->addSql('ALTER TABLE admins DROP role');
        $this->addSql('ALTER TABLE admins DROP email');
    }
}
