<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Result;
use Illuminate\Console\Command;

class RecalculateResultPositions extends Command
{
    protected $signature = 'results:recalculate-positions {competition? : Only this competition ID}';

    protected $description = 'Recalculate competition result positions from the result values and each sport\'s ranking direction';

    public function handle(): int
    {
        $ids = $this->argument('competition')
            ? [(int) $this->argument('competition')]
            : Competition::whereHas('results')->pluck('id')->all();

        foreach ($ids as $id) {
            Result::recalculatePositions($id);
        }

        $this->info('Recalculated positions for '.count($ids).' competition(s).');

        return self::SUCCESS;
    }
}
