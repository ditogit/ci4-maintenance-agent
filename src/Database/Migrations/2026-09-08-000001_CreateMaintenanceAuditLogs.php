<?php

namespace MaintenanceAgent\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaintenanceAuditLogs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'request_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'action'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'affected_rows' => ['type' => 'INT', 'default' => 0],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'metadata'      => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('maintenance_audit_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('maintenance_audit_logs', true);
    }
}
