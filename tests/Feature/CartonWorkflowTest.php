<?php

use App\Models\Carton;
use App\Models\CartonType;
use App\Models\LabelPrintJob;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\PackagingDemoSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    $this->seed(PackagingDemoSeeder::class);
    $this->seed(RoleTableSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('administrator'));
});

/** Packs every line of SO-1002 into one large carton: 18 x 2040 g + 40 x 515 g + 540 g tare = 57,860 g. */
function packWholeOrder(string $orderNumber = 'SO-1002'): Carton
{
    $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();
    test()->postJson(route('package-station.store'), [
        'order_id' => $order->id,
        'idempotency_key' => (string) Str::uuid(),
        'cartons' => [[
            'carton_type_id' => CartonType::query()->where('code', 'LARGE')->value('id'),
            'items' => $order->items->map(fn ($item): array => ['order_item_id' => $item->id, 'quantity' => $item->required_quantity])->all(),
        ]],
    ])->assertCreated();

    return Carton::query()->firstOrFail();
}

function act(Carton $carton, string $action, array $body = []): TestResponse
{
    return test()->postJson(route('cartons.act', [$carton, $action]), $body);
}

it('takes a carton through weighing, approval and sealing', function (): void {
    $carton = packWholeOrder();

    act($carton, 'request-weighing')->assertOk()->assertJsonPath('data.status', 'AWAITING_WEIGHT');
    act($carton, 'weigh', ['measured_weight_g' => 57860])->assertOk()->assertJsonPath('data.status', 'WEIGHT_VERIFIED');
    act($carton, 'request-approval')->assertOk()->assertJsonPath('data.status', 'AWAITING_APPROVAL');
    act($carton, 'verify')->assertOk()->assertJsonPath('data.status', 'VERIFIED');
    act($carton, 'seal', ['seal_number' => 'SEAL-001'])->assertOk()->assertJsonPath('data.status', 'SEALED');

    expect($carton->refresh()->seal_number)->toBe('SEAL-001')
        ->and((float) $carton->verified_weight_g)->toBe(57860.0)
        ->and($carton->verified_by)->not->toBeNull();

    act($carton, 'reopen', ['reason' => 'Wrong seal applied'])->assertOk()->assertJsonPath('data.status', 'REOPENED');
    expect($carton->refresh()->seal_number)->toBeNull();
});

it('keeps a failed weighing attempt and lets a supervisor override it with a reason', function (): void {
    $carton = packWholeOrder();
    act($carton, 'request-weighing');

    act($carton, 'weigh', ['measured_weight_g' => 59000])->assertOk()->assertJsonPath('data.status', 'WEIGHT_FAILED');
    act($carton, 'override')->assertStatus(422);
    act($carton, 'override', ['reason' => 'Wet packaging'])->assertOk()->assertJsonPath('data.status', 'WEIGHT_VERIFIED');

    expect($carton->weightMeasurements()->count())->toBe(1)
        ->and($carton->weightVerifications()->firstOrFail()->override_reason)->toBe('Wet packaging');
});

it('rejects workflow actions that do not fit the carton status', function (): void {
    $carton = packWholeOrder();

    act($carton, 'verify')->assertStatus(409);
    act($carton, 'seal', ['seal_number' => 'SEAL-002'])->assertStatus(409);
    expect($carton->refresh()->status->value)->toBe('CONTENTS_VERIFIED');
});

it('prints a label once and requires a reason to reprint it', function (): void {
    $carton = packWholeOrder();

    $this->postJson(route('cartons.label-print', $carton))->assertCreated()->assertJsonPath('data.is_reprint', false);
    $this->postJson(route('cartons.label-print', $carton))->assertStatus(422)->assertJsonPath('code', 'REPRINT_REASON_REQUIRED');
    $this->postJson(route('cartons.label-print', $carton), ['reason' => 'Label torn'])->assertCreated()->assertJsonPath('data.copy_number', 2)
        ->assertJsonPath('data.label.number', $carton->carton_number)->assertJsonPath('data.label.contents.0.sku', 'SUGAR-2KG');
    expect(LabelPrintJob::query()->where('carton_id', $carton->id)->where('is_reprint', true)->value('reprint_reason'))->toBe('Label torn');

    $this->get(route('cartons.label', $carton))->assertOk()->assertSee($carton->carton_number);
});

it('lists cartons and shows a carton with its actions', function (): void {
    $carton = packWholeOrder();

    $this->get(route('cartons.index'))->assertOk()->assertSee('cartonsDataTable');
    $this->getJson(route('cartons.index'))->assertOk()->assertJsonPath('data.0.carton_number', $carton->carton_number)->assertJsonPath('counts.CONTENTS_VERIFIED', 1);
    $this->getJson(route('cartons.index', ['q' => 'nothing-matches']))->assertOk()->assertJsonCount(0, 'data');
    $this->getJson(route('cartons.index', ['status' => 'SEALED']))->assertOk()->assertJsonCount(0, 'data');
    $this->get(route('cartons.show', $carton))->assertOk()->assertSee('Send to scale');
});

it('finds the cartons of an order by invoice or order number', function (): void {
    $carton = packWholeOrder();
    $secondCarton = $carton->replicate();
    $secondCarton->carton_number = $carton->carton_number.'-COPY';
    $secondCarton->batch_key = (string) Str::uuid();
    $secondCarton->save();

    $this->getJson(route('cartons.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.order_number', $carton->order->order_number)
        ->assertJsonPath('data.0.invoice_number', $carton->order->invoice_number);

    foreach ([$carton->order->invoice_number, $carton->order->order_number] as $search) {
        $this->getJson(route('cartons.index', ['q' => $search]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['carton_number' => $carton->carton_number])
            ->assertJsonFragment(['carton_number' => $secondCarton->carton_number]);
    }
});

it('shows the cross-check workflow in physical, contents, and decision order', function (): void {
    $this->get(route('carton-cross-check'))
        ->assertOk()
        ->assertSeeInOrder(['Carton and weight', 'Products in carton', 'Security decision'])
        ->assertSee('Inspection checklist')
        ->assertSee('Security note')
        ->assertSee('Clear for dispatch');
});

it('clears a carton only when the weight matches, and records a single decision', function (): void {
    $carton = packWholeOrder();
    act($carton, 'request-weighing');
    act($carton, 'weigh', ['measured_weight_g' => 57860]);
    $checklist = ['label_matched' => true, 'seal_intact' => true, 'condition_acceptable' => true];

    $this->getJson(route('carton-cross-check.lookup', ['code' => $carton->carton_number]))->assertOk()->assertJsonPath('data.recorded_weight_g', 57860);
    $this->postJson(route('carton-cross-check.store', $carton), ['decision' => 'CLEARED', 'checker_weight_g' => 60000, ...$checklist])
        ->assertStatus(422)->assertJsonPath('code', 'WEIGHT_OUT_OF_TOLERANCE');
    $this->postJson(route('carton-cross-check.store', $carton), ['decision' => 'FLAGGED', ...$checklist])
        ->assertStatus(422)->assertJsonPath('code', 'NOTE_REQUIRED');

    $this->postJson(route('carton-cross-check.store', $carton), ['decision' => 'CLEARED', 'checker_weight_g' => 57870, ...$checklist])
        ->assertCreated()->assertJsonPath('data.decision', 'CLEARED');
    $this->postJson(route('carton-cross-check.store', $carton), ['decision' => 'CLEARED', 'checker_weight_g' => 57860, ...$checklist])
        ->assertStatus(409)->assertJsonPath('code', 'DECISION_ALREADY_RECORDED');
});

it('does not cross-check a carton before its whole order is packed', function (): void {
    $order = Order::query()->where('order_number', 'SO-1001')->firstOrFail();
    $this->postJson(route('package-station.store'), [
        'order_id' => $order->id,
        'idempotency_key' => (string) Str::uuid(),
        'cartons' => [['carton_type_id' => CartonType::query()->value('id'), 'items' => [['order_item_id' => $order->items->first()->id, 'quantity' => 5]]]],
    ])->assertCreated();

    $this->postJson(route('carton-cross-check.store', Carton::query()->firstOrFail()), ['decision' => 'CLEARED', 'label_matched' => true, 'seal_intact' => true, 'condition_acceptable' => true])
        ->assertStatus(409)->assertJsonPath('code', 'ORDER_NOT_FULLY_PACKED');
});
