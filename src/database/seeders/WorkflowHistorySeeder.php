<?php

namespace Database\Seeders;

use App\Models\WorkflowHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkflowHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WorkflowHistory::create([
            'workflow_id' => 1,
            'from_state' => null,
            'to_state' => 'draft',
            'acted_by' => 1,
            'comment' => 'Created',
            'meta' => ['source' => 'seeder'],
            'created_at' => now(),
        ]);
        
        WorkflowHistory::create([
            'workflow_id' => 2,
            'from_state' => null,
            'to_state' => 'draft',
            'acted_by' => 1,
            'comment' => 'Created',
            'created_at' => now()->subMinutes(10),
        ]);

        WorkflowHistory::create([
            'workflow_id' => 2,
            'from_state' => 'draft',
            'to_state' => 'submitted',
            'acted_by' => 1,
            'comment' => 'Submitted',
            'created_at' => now()->subMinutes(7),
        ]);

        WorkflowHistory::create([
            'workflow_id' => 2,
            'from_state' => 'submitted',
            'to_state' => 'approved',
            'acted_by' => 2,
            'comment' => 'Approved for demo',
            'created_at' => now()->subMinutes(3),
        ]);
    }
}
