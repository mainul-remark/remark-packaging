<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed(RoleTableSeeder::class);
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('activity-log.view');
    $this->actingAs($this->user);
});

it('shows summaries and expandable activity details', function (): void {
    $activity = Activity::query()->create([
        'log_name' => 'product',
        'description' => 'updated product',
        'event' => 'updated',
        'subject_type' => Product::class,
        'subject_id' => 99,
        'causer_type' => User::class,
        'causer_id' => $this->user->id,
        'properties' => [
            'old' => ['name' => 'Old carton'],
            'attributes' => ['name' => 'New carton'],
            'ip' => '127.0.0.1',
        ],
    ]);

    $this->get(route('activity-log.view'))
        ->assertOk()
        ->assertSee('Total Logs')
        ->assertSee('Apply Filters')
        ->assertSee('Updated product')
        ->assertSee('Old carton')
        ->assertSee('New carton')
        ->assertSee('activity-log-'.$activity->id, false);
});

it('filters activity logs by event actor search and date', function (): void {
    Activity::query()->create([
        'log_name' => 'product',
        'description' => 'created matching carton',
        'event' => 'created',
        'causer_type' => User::class,
        'causer_id' => $this->user->id,
        'properties' => ['reference' => 'MATCH-100'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Activity::query()->create([
        'log_name' => 'product',
        'description' => 'deleted unrelated carton',
        'event' => 'deleted',
        'properties' => [],
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $this->get(route('activity-log.view', [
        'search' => 'MATCH-100',
        'event' => 'created',
        'causer_id' => $this->user->id,
        'date_from' => today()->toDateString(),
        'date_to' => today()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Created matching carton')
        ->assertDontSee('Deleted unrelated carton');
});

it('rejects a date range ending before it starts', function (): void {
    $this->get(route('activity-log.view', [
        'date_from' => '2026-10-20',
        'date_to' => '2026-10-19',
    ]))->assertSessionHasErrors('date_to');
});
