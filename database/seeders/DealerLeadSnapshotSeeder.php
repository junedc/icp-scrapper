<?php

namespace Database\Seeders;

use App\Models\DealerLeadSnapshot;
use Illuminate\Database\Seeder;

class DealerLeadSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DealerLeadSnapshot::factory()->count(25)->create();
    }
}
