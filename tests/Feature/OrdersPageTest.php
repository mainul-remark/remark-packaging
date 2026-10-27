<?php

use App\Models\CartonType;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\PackagingDemoSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('shows an empty state before any orders exist', function (): void {
    $this->get(route('orders.index'))->assertOk()->assertSee('No orders found');
});

it('lists orders with customer, destination and packing progress', function (): void {
    $this->seed(PackagingDemoSeeder::class);

    $this->get(route('orders.index'))->assertOk()
        ->assertSee('SO-1001')->assertSee('SO-1002')->assertSee('Fresh Mart Superstores')->assertSee('Dhaka Distribution Hub')->assertSee('0 / 120 units');
    $this->get(route('orders.index', ['q' => 'INV-5002']))->assertOk()->assertSee('SO-1002')->assertDontSee('SO-1001');
    $this->get(route('orders.index', ['status' => 'CANCELLED']))->assertOk()->assertSee('No orders found');
});

it('shows an order with its lines and cartons, and tracks packed units', function (): void {
    $this->seed(PackagingDemoSeeder::class);
    $order = Order::query()->where('order_number', 'SO-1001')->firstOrFail();
    $line = $order->items()->where('original_sku', 'RICE-1KG')->firstOrFail();

    $this->postJson(route('package-station.store'), [
        'order_id' => $order->id,
        'idempotency_key' => (string) Str::uuid(),
        'cartons' => [['carton_type_id' => CartonType::query()->value('id'), 'items' => [['order_item_id' => $line->id, 'quantity' => 12]]]],
    ])->assertCreated();

    $this->get(route('orders.show', $order))->assertOk()
        ->assertSee('RICE-1KG')->assertSee('SO-1001-ASO-01')->assertSee('12 / 120 packed');
    $this->get(route('orders.index'))->assertOk()->assertSee('12 / 120 units');
});

it('marks an order packed once every line is fully covered and stops listing it for packing', function (): void {
    $this->seed(PackagingDemoSeeder::class);
    $order = Order::query()->where('order_number', 'SO-1002')->firstOrFail();

    $this->postJson(route('package-station.store'), [
        'order_id' => $order->id,
        'idempotency_key' => (string) Str::uuid(),
        'cartons' => [['carton_type_id' => CartonType::query()->value('id'), 'items' => $order->items->map(fn ($item): array => ['order_item_id' => $item->id, 'quantity' => $item->required_quantity])->all()]],
    ])->assertCreated();

    expect($order->refresh()->status)->toBe('PACKED');
    $this->get(route('package-station'))->assertOk()->assertDontSee('SO-1002');
    $this->get(route('orders.index', ['status' => 'PACKED']))->assertOk()->assertSee('SO-1002');
});

it('opens the package station for an order from the orders list', function (): void {
    $this->seed(PackagingDemoSeeder::class);
    $order = Order::query()->where('order_number', 'SO-1001')->firstOrFail();

    $this->get(route('orders.index'))->assertSee(route('package-station', ['order' => $order->id]));
    $this->get(route('package-station', ['order' => $order->id]))->assertOk();
});
