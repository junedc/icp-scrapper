<?php

namespace Database\Seeders;

use App\Models\DealerOrderSnapshot;
use Illuminate\Database\Seeder;

class DealerOrderSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DealerOrderSnapshot::factory()->count(25)->create();
    }
}
