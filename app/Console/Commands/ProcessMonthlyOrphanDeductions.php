<?php

namespace App\Console\Commands;

use App\Models\Orphan;
use Illuminate\Console\Command;

class ProcessMonthlyOrphanDeductions extends Command
{
    protected $signature = 'orphans:process-monthly-deductions';
    protected $description = 'Process monthly sponsorship deductions';

    public function handle(): int
    {
        $this->info('Processing monthly orphan sponsorship deductions...');

        try {
            $count = Orphan::processMonthlyDeductions();
            $this->info("Successfully processed {$count} sponsorships");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}