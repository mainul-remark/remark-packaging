@extends('admin.master')
@section('title', $carton->carton_number)

@php
    $grams = fn ($value) => $value === null ? '—' : number_format((float) $value, 1).' g';
    $labels = [
        'request-weighing' => ['Send to scale', 'btn-primary'], 'weigh' => ['Record weight', 'btn-primary'], 'override' => ['Supervisor override', 'btn-warning'],
        'return-to-packing' => ['Return to packing', 'btn-outline-secondary'], 'request-approval' => ['Request approval', 'btn-primary'],
        'verify' => ['Verify', 'btn-success'], 'reject' => ['Reject', 'btn-outline-danger'], 'seal' => ['Apply seal', 'btn-success'], 'reopen' => ['Reopen carton', 'btn-outline-danger'],
    ];
@endphp

@section('content')
<div class="page-breadcrumb border-bottom mb-3">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-package-variant-closed mr-2"></i>{{ $carton->carton_number }}</h5>
            <p class="mb-0 text-muted">{{ $carton->order->order_number }} · {{ $carton->order->customer->name }} → {{ $carton->order->destination->name }}</p>
        </div>
        <div class="col-md-5 align-self-center text-md-right py-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('cartons.index') }}"><i class="mdi mdi-arrow-left"></i> All cartons</a>
            <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('cartons.label', $carton) }}"><i class="mdi mdi-printer"></i> View label</a>
        </div>
    </div>
</div>

<div class="container-fluid pb-4" id="carton-page" data-act="{{ url('admin/cartons/'.$carton->id.'/actions') }}">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Status <span class="badge badge-info ml-2" id="carton-status">{{ str_replace('_', ' ', $carton->status->value) }}</span>
                        @if ($carton->weight_flag)<span class="badge badge-warning ml-1">{{ $carton->weight_flag }}</span>@endif</h5>
                    <div id="carton-actions">
                        @foreach ($actions as $action)
                            <button type="button" class="btn btn-sm {{ $labels[$action][1] }} mr-1 mb-1" data-action="{{ $action }}">{{ $labels[$action][0] }}</button>
                        @endforeach
                        @if (! $actions)<span class="text-muted">No further actions.</span>@endif
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6 col-md-3"><small class="text-muted d-block">System weight</small><strong>{{ $grams($carton->system_weight_g) }}</strong></div>
                    <div class="col-6 col-md-3"><small class="text-muted d-block">Allowed range</small><strong>{{ number_format((float) $carton->weight_lower_limit_g, 1) }}–{{ number_format((float) $carton->weight_upper_limit_g, 1) }} g</strong></div>
                    <div class="col-6 col-md-3"><small class="text-muted d-block">Entered at packing</small><strong>{{ $grams($carton->manual_weight_g ?? $carton->machine_weight_g) }}</strong></div>
                    <div class="col-6 col-md-3"><small class="text-muted d-block">Verified weight</small><strong>{{ $grams($carton->verified_weight_g) }}</strong></div>
                </div>
                <hr>
                <div class="row small">
                    <div class="col-md-3"><span class="text-muted">Carton type</span><br>{{ $carton->cartonType?->name ?? '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Packed by</span><br>{{ $carton->packer?->name }}</div>
                    <div class="col-md-3"><span class="text-muted">Verified by</span><br>{{ $carton->verifier?->name ?? '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Seal number</span><br>{{ $carton->seal_number ?? '—' }}</div>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-header">Contents</div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th class="text-right">Quantity</th><th class="text-right">Unit weight</th><th class="text-right">Line weight</th></tr></thead>
                    <tbody>
                        @foreach ($carton->items as $item)
                            <tr><td><strong>{{ $item->product->sku }}</strong> <small class="text-muted">{{ $item->product->name }}</small></td><td class="text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}</td><td class="text-right">{{ $grams($item->unit_weight_snapshot_g) }}</td><td class="text-right">{{ $grams($item->quantity * $item->unit_weight_snapshot_g) }}</td></tr>
                        @endforeach
                        @foreach ($carton->materials as $row)
                            <tr class="text-muted"><td>{{ $row->material->name }} <small>(packaging)</small></td><td class="text-right">{{ rtrim(rtrim(number_format((float) $row->quantity, 3, '.', ''), '0'), '.') }}</td><td class="text-right">{{ $grams($row->unit_weight_snapshot_g) }}</td><td class="text-right">{{ $grams($row->total_weight_g) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>

            <div class="card mb-3"><div class="card-header">Weighing attempts</div>
                @if ($carton->weightMeasurements->isEmpty())<div class="card-body text-muted">No weighing attempts yet.</div>@else
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>When</th><th class="text-right">Measured</th><th class="text-right">Expected</th><th class="text-right">Range</th><th>Result</th><th>By</th></tr></thead>
                    <tbody>
                        @foreach ($carton->weightMeasurements as $m)
                            <tr><td>{{ $m->measured_at->format('m/d g:i A') }}</td><td class="text-right">{{ $grams($m->measured_weight_g) }}</td><td class="text-right">{{ $grams($m->expected_weight_g) }}</td><td class="text-right">{{ number_format((float) $m->lower_limit_g, 1) }}–{{ number_format((float) $m->upper_limit_g, 1) }}</td>
                                <td><span class="badge badge-{{ $m->result->value === 'PASS' ? 'success' : 'danger' }}">{{ $m->result->value }}</span></td><td>{{ $m->operator?->name }}</td></tr>
                        @endforeach
                        @foreach ($carton->weightVerifications->where('method', 'SUPERVISOR_OVERRIDE') as $v)
                            <tr class="table-warning"><td colspan="6">Supervisor override by {{ $v->acceptedBy?->name }}: {{ $v->override_reason }} @if ($v->status !== 'ACTIVE')<em>({{ strtolower($v->status) }})</em>@endif</td></tr>
                        @endforeach
                    </tbody>
                </table></div>@endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header">Status history</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($carton->statusHistory->reverse() as $h)
                        <li class="list-group-item"><strong>{{ str_replace('_', ' ', $h->to_status) }}</strong> <span class="text-muted">· {{ $h->actor?->name }} · {{ $h->occurred_at->format('m/d g:i A') }}</span>@if ($h->reason)<br><em>{{ $h->reason }}</em>@endif</li>
                    @endforeach
                </ul>
            </div>
            <div class="card mb-3"><div class="card-header">Label prints</div>
                @if ($carton->labelPrints->isEmpty())<div class="card-body text-muted small">Not printed yet.</div>@else
                <ul class="list-group list-group-flush small">
                    @foreach ($carton->labelPrints as $p)
                        <li class="list-group-item">Copy {{ $p->copy_number }} · {{ $p->printedBy?->name }} · {{ $p->printed_at->format('m/d g:i A') }}@if ($p->is_reprint)<br><em>Reprint: {{ $p->reprint_reason }}</em>@endif</li>
                    @endforeach
                </ul>@endif
            </div>
        </div>
    </div>
</div>
<div id="carton-toast" class="alert alert-dark" style="position:fixed;left:50%;bottom:30px;transform:translateX(-50%);display:none;z-index:70"></div>
@endsection

@push('scripts')
<script>
$(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content'), base = $('#carton-page').data('act');
    var prompts = {
        weigh: ['Measured gross weight in grams:', 'measured_weight_g'], override: ['Reason for supervisor override:', 'reason'],
        'return-to-packing': ['Reason for returning to packing:', 'reason'], reject: ['Reason for rejection:', 'reason'],
        seal: ['Seal number:', 'seal_number'], reopen: ['Reason for reopening:', 'reason']
    };
    function toast(message) { var $t = $('#carton-toast').text(message).fadeIn(120); clearTimeout(window.cartonToast); window.cartonToast = setTimeout(function () { $t.fadeOut(200); }, 3500); }
    $('#carton-actions').on('click', '[data-action]', function () {
        var action = $(this).data('action'), body = {};
        if (prompts[action]) {
            var answer = window.prompt(prompts[action][0]);
            if (!answer) { return; }
            body[prompts[action][1]] = answer;
        }
        $(this).prop('disabled', true);
        $.ajax({ url: base + '/' + action, method: 'POST', dataType: 'json', data: body, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
            .done(function () { window.location.reload(); })
            .fail(function (xhr) {
                var json = xhr.responseJSON || {};
                toast(json.message || (json.errors ? Object.values(json.errors)[0][0] : 'That action could not be completed.'));
                $('#carton-actions [data-action]').prop('disabled', false);
            });
    });
});
</script>
@endpush
