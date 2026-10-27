<?php

use App\Models\Carton;
use App\Models\CartonType;
use App\Models\Order;
use App\Models\PackagingMaterial;
use App\Models\User;
use Database\Seeders\PackagingDemoSeeder;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    $this->seed(PackagingDemoSeeder::class);
    $this->actingAs(User::factory()->create());
    $this->order = Order::query()->where('order_number', 'SO-1001')->firstOrFail();
    $this->riceLine = $this->order->items()->where('original_sku', 'RICE-1KG')->firstOrFail();
});

/** @param  array<string, mixed>  $overrides */
function cartonSpec(array $overrides = []): array
{
    return array_merge([
        'draft_reference' => 'C001',
        'carton_type_id' => CartonType::query()->where('code', 'MEDIUM')->value('id'),
        'items' => [['order_item_id' => test()->riceLine->id, 'quantity' => 10]],
        'materials' => [['material_id' => PackagingMaterial::query()->where('code', 'TAPE')->value('id'), 'quantity' => 1]],
    ], $overrides);
}

/** @param  list<array<string, mixed>>  $cartons */
function storeCartons(array $cartons, ?string $key = null): TestResponse
{
    return test()->postJson(route('package-station.store'), [
        'order_id' => test()->order->id,
        'idempotency_key' => $key ?? (string) Str::uuid(),
        'cartons' => $cartons,
    ]);
}

it('lists open orders and opens the workspace for one', function (): void {
    $this->get(route('package-station'))->assertOk()->assertSee('SO-1001')->assertSee('SO-1002');

    $this->get(route('package-station', ['order' => $this->order->id]))->assertOk()->assertSee('Fresh Mart Superstores');
});

it('creates a carton with the system weight and limits computed from governed data', function (): void {
    storeCartons([cartonSpec()])->assertCreated()->assertJsonPath('data.cartons.0.carton_number', 'SO-1001-C001');

    $carton = Carton::query()->firstOrFail();
    // 10 x 1020 g rice + 320 g medium carton tare + 45 g tape.
    expect((float) $carton->system_weight_g)->toBe(10565.0)
        ->and((float) $carton->weight_lower_limit_g)->toBe(10455.0)
        ->and((float) $carton->weight_upper_limit_g)->toBe(10675.0)
        ->and($carton->status->value)->toBe('CONTENTS_VERIFIED')
        ->and($carton->weight_flag)->toBeNull()
        ->and($this->order->refresh()->status)->toBe('IN_PROGRESS');
});

it('expands identical copies into separately numbered cartons', function (): void {
    storeCartons([cartonSpec(['copies' => 3])])->assertCreated();

    expect(Carton::query()->orderBy('id')->pluck('carton_number')->all())->toBe(['SO-1001-C001', 'SO-1001-C002', 'SO-1001-C003']);
});

it('replays a batch with the same idempotency key without creating cartons twice', function (): void {
    $key = (string) Str::uuid();
    storeCartons([cartonSpec()], $key)->assertCreated();
    storeCartons([cartonSpec()], $key)->assertOk()->assertJsonPath('data.replayed', true);

    expect(Carton::query()->count())->toBe(1);
});

it('rejects cartons that exceed the quantity left on the order', function (): void {
    storeCartons([cartonSpec(['items' => [['order_item_id' => $this->riceLine->id, 'quantity' => 49]]])])
        ->assertStatus(422)->assertJsonPath('code', 'ORDER_QUANTITY_EXCEEDED');

    expect(Carton::query()->count())->toBe(0);
});

it('only saves an out-of-range weight once the operator confirms it, and flags the carton', function (): void {
    storeCartons([cartonSpec(['manual_weight_g' => 11000])])->assertStatus(422)->assertJsonPath('code', 'WEIGHT_OUT_OF_RANGE');
    expect(Carton::query()->count())->toBe(0);

    storeCartons([cartonSpec(['manual_weight_g' => 11000, 'weight_flag_acknowledged' => true])])->assertCreated();

    $carton = Carton::query()->firstOrFail();
    expect($carton->weight_flag)->toBe('OVERWEIGHT')
        ->and((float) $carton->weight_variance_g)->toBe(435.0)
        ->and($carton->weight_flag_acknowledged_by)->not->toBeNull();
});

it('finds an open order by invoice number for the station search', function (): void {
    $this->getJson(route('package-station.find', ['q' => 'INV-5002']))->assertOk()->assertJsonPath('data.id', Order::query()->where('order_number', 'SO-1002')->value('id'));
    $this->getJson(route('package-station.find', ['q' => 'nothing']))->assertNotFound();
});

it('records the first print of a session of labels once and skips repeats', function (): void {
    storeCartons([cartonSpec()])->assertCreated();
    $id = Carton::query()->value('id');

    $this->postJson(route('cartons.label-prints'), ['carton_ids' => [$id]])->assertCreated()->assertJsonPath('data.printed.0.carton_id', $id);
    $this->postJson(route('cartons.label-prints'), ['carton_ids' => [$id]])->assertCreated()->assertJsonPath('data.already_printed.0', $id);
});

it('saves sorted cartons that hold a single product as identical copies', function (): void {
    storeCartons([cartonSpec(['packaging_mode' => 'SORTED', 'copies' => 2, 'materials' => []])])->assertCreated();

    expect(Carton::query()->pluck('packaging_mode')->map->value->unique()->all())->toBe(['SORTED'])
        ->and(Carton::query()->count())->toBe(2);
});

it('rejects a sorted carton that holds more than one product', function (): void {
    $oil = $this->order->items()->where('original_sku', 'OIL-1L')->firstOrFail();

    storeCartons([cartonSpec(['packaging_mode' => 'SORTED', 'items' => [
        ['order_item_id' => $this->riceLine->id, 'quantity' => 2], ['order_item_id' => $oil->id, 'quantity' => 2],
    ]])])->assertStatus(422)->assertJsonPath('code', 'SORTED_SINGLE_PRODUCT');

    expect(Carton::query()->count())->toBe(0);
});

it('defaults to an assorted carton when no packaging mode is sent', function (): void {
    storeCartons([cartonSpec()])->assertCreated();

    expect(Carton::query()->firstOrFail()->packaging_mode->value)->toBe('ASSORTED');
});
