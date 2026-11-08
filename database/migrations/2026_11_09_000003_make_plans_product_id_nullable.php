<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * plans.product_id holds the App Store / Google Play product ids of the
     * mobile apps and is optional now (plans sold only on the website have
     * none). Raw SQL: changing a column with the schema builder needs
     * doctrine/dbal, which this project does not use. Fresh installs (and
     * the SQLite tests) get a nullable column from the create migration.
     */
    public function up()
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE plans MODIFY product_id VARCHAR(150) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE plans ALTER COLUMN product_id DROP NOT NULL');
        }
    }

    public function down()
    {
        // Keep the column nullable: plans saved without product ids would
        // block going back to NOT NULL.
    }
};
