<?php

namespace Database\Seeders;

use App\Models\Workflow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkflowsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Workflow::create([
            'public_id' => (string) Str::ulid(),
            'title' => 'Draft: Purchase Request',
            'body' => 'Requesting approval for a demo purchase.',
            'current_state' => 'draft',
            'created_by' => 1,
        ]);
        Workflow::create([
            'public_id' => (string) Str::ulid(),
            'title' => 'Submitted: Travel Request',
            'body' => 'Business trip approval request.',
            'current_state' => 'approved',
            'created_by' => 1,
        ]);
    }
}
