<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQL Server: drop check constraint on 'type' and recreate including Autorizador
        DB::statement("
            DECLARE @constraintName NVARCHAR(256)
            SELECT @constraintName = tc.CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE ccu
                ON tc.CONSTRAINT_NAME = ccu.CONSTRAINT_NAME
            WHERE tc.TABLE_NAME = 'users'
                AND ccu.COLUMN_NAME = 'type'
                AND tc.CONSTRAINT_TYPE = 'CHECK'
            IF @constraintName IS NOT NULL
                EXEC('ALTER TABLE users DROP CONSTRAINT [' + @constraintName + ']')
            ALTER TABLE users ADD CONSTRAINT chk_users_type
                CHECK ([type] IN (N'Administrador', N'Usuario', N'Autorizador'))
        ");
    }

    public function down(): void
    {
        DB::statement("
            IF EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'chk_users_type'
            )
                ALTER TABLE users DROP CONSTRAINT chk_users_type
            ALTER TABLE users ADD CONSTRAINT chk_users_type
                CHECK ([type] IN (N'Administrador', N'Usuario'))
        ");
    }
};
