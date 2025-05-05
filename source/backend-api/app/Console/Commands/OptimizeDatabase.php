<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database tables for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database optimization...');

        // Get all tables in the database
        $tables = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableNames();

        $this->withProgressBar($tables, function ($table) {
            // Check if the table is InnoDB (for MySQL/MariaDB)
            $engine = DB::select("SHOW TABLE STATUS WHERE Name = '{$table}'")[0]->Engine ?? null;

            if ($engine === 'InnoDB') {
                // Optimize InnoDB tables
                $this->optimizeInnoDBTable($table);
            } else {
                // For other engines like MyISAM, we can use OPTIMIZE TABLE
                DB::statement("OPTIMIZE TABLE {$table}");
            }

            // Sleep briefly to prevent overloading the database
            usleep(100000); // 0.1 seconds
        });

        // Update statistics for query optimization
        $this->info('Updating database statistics...');
        DB::statement('ANALYZE TABLE ' . implode(', ', $tables));

        $this->info('Database optimization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Optimize an InnoDB table using the recommended approach.
     * InnoDB doesn't support direct OPTIMIZE TABLE like MyISAM does.
     */
    private function optimizeInnoDBTable(string $table): void
    {
        // For InnoDB, the recommended way to optimize is to recreate the table
        // using ALTER TABLE ... ENGINE=InnoDB
        DB::statement("ALTER TABLE {$table} ENGINE=InnoDB");
    }
}
