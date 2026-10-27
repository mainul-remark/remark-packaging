@extends('admin.master')
@section('title', $order->order_number)

@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    $required = (float) $order->items->sum('required_quantity');
    $done = (float) $order->items->sum(fn ($item): float => $packed[$item->id] ?? 0);
@endphp

@section('content')
<div class="page-breadcrumb border-bottom mb-3">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-clipboard-text mr-2"></i>{{ $order->order_number }} <span class="badge badge-info ml-2">{{ str_replace('_', ' ', $order->status) }}</span></h5>
            <p class="mb-0 text-muted">{{ $order->customer->name }} → {{ $order->destination->name }} · {{ $order->order_date->format('M j, Y') }}</p>
        </div>
        <div class="col-md-5 align-self-center text-md-right py-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.index') }}"><i class="mdi mdi-arrow-left"></i> All orders</a>
            @if (in_array($order->status, ['OPEN', 'IN_PROGRESS'], true))
                <a class="btn btn-sm btn-primary" href="{{ route('package-station', ['order' => $order->id]) }}"><i class="mdi mdi-package-variant-closed"></i> Pack this order</a>
            @endif
        </div>
    </div>
</div>

<div class="container-fluid pb-4">
    <div class="card mb-3"><div class="card-body">
        <div class="row small">
            <div class="col-6 col-md-3"><span class="text-muted">Invoice</span><br><strong>{{ $order->invoice_number ?? '—' }}</strong></div>
            <div class="col-6 col-md-3"><span class="text-muted">Priority</span><br><strong>{{ $order->priority <= 1 ? 'High' : ($order->priority <= 3 ? 'Medium' : 'Normal') }}</strong></div>
            <div class="col-6 col-md-3"><span class="text-muted">Units</span><br><strong>{{ $qty($done) }} / {{ $qty($required) }} packed</strong></div>
            <div class="col-6 col-md-3"><span class="text-muted">Cartons</span><br><strong>{{ $cartons->count() }}</strong></div>
        </div>
    </div></div>

    <div class="card mb-3"><div class="card-header">Order lines</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>Line</th><th>Product</th><th class="text-right">Required</th><th class="text-right">Packed</th><th class="text-right">Remaining</th><th>UoM</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php $left = max(0, (float) $item->required_quantity - ($packed[$item->id] ?? 0)); @endphp
                    <tr>
                        <td>{{ $item->external_line_number ?? $loop->iteration }}</td>
                        <td><strong>{{ $item->original_sku }}</strong> <small class="text-muted">{{ $item->original_description }}</small></td>
                        <td class="text-right">{{ $qty($item->required_quantity) }}</td>
                        <td class="text-right">{{ $qty($packed[$item->id] ?? 0) }}</td>
                        <td class="text-right {{ $left > 0 ? '' : 'text-success' }}">{{ $left > 0 ? $qty($left) : 'Done' }}</td>
                        <td>{{ $item->uom }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="card"><div class="card-header">Cartons</div>
        @if ($cartons->isEmpty())
            <div class="card-body text-muted">No cartons packed for this order yet.</div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($cartons as $carton)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('cartons.show', $carton) }}">{{ $carton->carton_number }}</a>
                        <span><small class="text-muted mr-3">{{ $qty($carton->unit_count) }} units · {{ number_format((float) $carton->system_weight_g, 1) }} g</small><span class="badge badge-info">{{ str_replace('_', ' ', $carton->status->value) }}</span></span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
