@extends('admin.master')
@section('title', 'Orders')

@section('content')
<div class="page-breadcrumb border-bottom mb-3">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-clipboard-text mr-2"></i>Orders</h5>
            <p class="mb-0 text-muted">Orders waiting to be packed, with packing progress</p>
        </div>
        <div class="col-md-5 align-self-center py-2">
            <nav class="d-flex justify-content-md-end" aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li><li class="breadcrumb-item active">Orders</li></ol></nav>
        </div>
    </div>
</div>

<div class="container-fluid pb-4">
    <form class="form-inline mb-3" method="get" action="{{ route('orders.index') }}">
        <input class="form-control mr-2 mb-2" style="min-width:280px" type="search" name="q" value="{{ request('q') }}" placeholder="Order, invoice or customer">
        @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
        <button class="btn btn-primary mb-2 mr-2" type="submit">Search</button>
        @if (request('q'))<a class="btn btn-link mb-2" href="{{ route('orders.index', array_filter(['status' => $activeStatus])) }}">Clear</a>@endif
    </form>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ $activeStatus === '' ? 'active' : '' }}" href="{{ route('orders.index', array_filter(['q' => request('q')])) }}">All <span class="badge badge-light">{{ $counts->sum() }}</span></a></li>
        @foreach (['OPEN', 'IN_PROGRESS', 'PACKED', 'CANCELLED'] as $status)
            <li class="nav-item"><a class="nav-link {{ $activeStatus === $status ? 'active' : '' }}" href="{{ route('orders.index', array_filter(['status' => $status, 'q' => request('q')])) }}">{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }} <span class="badge badge-light">{{ $counts[$status] ?? 0 }}</span></a></li>
        @endforeach
    </ul>

    <div class="card">
        @if ($orders->isEmpty())
            <div class="card-body text-center text-muted py-5">
                <i class="mdi mdi-clipboard-text" style="font-size:40px"></i>
                <p class="mb-0">No orders found. @if (! request()->hasAny(['q', 'status']))Run <code>php artisan db:seed --class=PackagingDemoSeeder</code> to load demo orders.@endif</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Order</th><th>Customer</th><th>Destination</th><th>Date</th><th>Priority</th><th class="text-right">Lines</th><th style="min-width:170px">Packed</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $required = (float) $order->required_units;
                            $done = $packedByOrder[$order->id] ?? 0;
                            $percent = $required > 0 ? min(100, $done / $required * 100) : 0;
                        @endphp
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a><br><small class="text-muted">{{ $order->invoice_number ?? 'No invoice' }}</small></td>
                            <td>{{ $order->customer->name }}</td>
                            <td>{{ $order->destination->name }}</td>
                            <td>{{ $order->order_date->format('M j, Y') }}</td>
                            <td>{{ $order->priority <= 1 ? 'High' : ($order->priority <= 3 ? 'Medium' : 'Normal') }}</td>
                            <td class="text-right">{{ $order->items_count }}</td>
                            <td>
                                <small>{{ rtrim(rtrim(number_format($done, 3, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format($required, 3, '.', ''), '0'), '.') }} units</small>
                                <div class="progress" style="height:6px"><div class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}" style="width:{{ $percent }}%"></div></div>
                            </td>
                            <td><span class="badge badge-{{ $order->status === 'PACKED' ? 'success' : ($order->status === 'IN_PROGRESS' ? 'warning' : ($order->status === 'CANCELLED' ? 'secondary' : 'info')) }}">{{ str_replace('_', ' ', $order->status) }}</span></td>
                            <td class="text-right text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.show', $order) }}">View</a>
                                @if (in_array($order->status, ['OPEN', 'IN_PROGRESS'], true))
                                    <a class="btn btn-sm btn-primary" href="{{ route('package-station', ['order' => $order->id]) }}">Pack</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center"><span class="text-muted">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}</span>{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
