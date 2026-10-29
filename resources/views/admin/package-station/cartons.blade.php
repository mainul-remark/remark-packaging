@extends('admin.master')
@section('title', 'Cartons')

@section('content')
<div class="page-breadcrumb border-bottom mb-2">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-package-variant-closed mr-2"></i>Cartons</h5>
            <p class="ct-page-subtitle mb-0">Every physical carton with its own identity and scan history</p>
        </div>
        <div class="col-md-5 align-self-center py-2">
            <nav class="d-flex justify-content-md-end" aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li><li class="breadcrumb-item active">Cartons</li></ol></nav>
        </div>
    </div>
</div>

<div class="container-fluid ct-shell">
    <form id="ct-search-form" class="ct-search-form" method="get" action="{{ route('cartons.index') }}">
        <label class="sr-only" for="ct-search">Search cartons</label>
        <input id="ct-search" type="search" name="q" value="{{ request('q') }}" placeholder="Find a carton by its number or seal number" autocomplete="off">
        @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
        <button type="submit">Search</button>
        @if (request('q'))<a class="ct-clear" href="{{ route('cartons.index', array_filter(['status' => $activeStatus])) }}">Clear</a>@endif
    </form>

    <nav class="ct-tabs" aria-label="Carton status filters">
        <button class="{{ $activeStatus === '' ? 'active' : '' }}" type="button" data-href="{{ route('cartons.index', array_filter(['q' => request('q')])) }}">All <span>{{ $counts->sum() }}</span></button>
        @foreach (['CONTENTS_VERIFIED', 'AWAITING_WEIGHT', 'WEIGHT_FAILED', 'WEIGHT_VERIFIED', 'AWAITING_APPROVAL', 'VERIFIED', 'SEALED', 'REOPENED', 'CANCELLED'] as $status)
            <button class="{{ $activeStatus === $status ? 'active' : '' }}" type="button" data-href="{{ route('cartons.index', array_filter(['status' => $status, 'q' => request('q')])) }}">{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }} <span>{{ $counts[$status] ?? 0 }}</span></button>
        @endforeach
    </nav>

    <section class="ct-card">
        @if ($cartons->count())
        <div class="table-responsive ct-table-wrap">
            <table class="table ct-table mb-0">
                <thead><tr><th>Order</th><th>Carton</th><th>Carton type</th><th>Units</th><th>System weight</th><th>Created</th><th>Status</th><th>Label</th></tr></thead>
                <tbody>
                    @foreach ($cartons as $carton)
                    <tr class="ct-row">
                        <td><strong>{{ $carton->order->order_number }}</strong></td>
                        <td><a href="{{ route('cartons.show', $carton) }}">{{ $carton->carton_number }}</a>@if ($carton->weight_flag) <span class="ct-prepacked">{{ ucfirst(strtolower($carton->weight_flag)) }}</span>@endif</td>
                        <td>{{ $carton->cartonType?->name ?? '—' }}</td>
                        <td>{{ rtrim(rtrim(number_format((float) $carton->unit_count, 3, '.', ''), '0'), '.') }}</td>
                        <td>{{ number_format((float) $carton->system_weight_g, 1) }} g</td>
                        <td>{{ $carton->created_at->format('m/d/Y, g:i A') }}</td>
                        <td><span class="ct-status">{{ str_replace('_', ' ', $carton->status->value) }}</span></td>
                        <td><button class="ct-print" type="button" data-print="{{ route('cartons.label-print', $carton) }}" data-printed="{{ $carton->label_prints_count }}" data-number="{{ $carton->carton_number }}">{{ $carton->label_prints_count ? 'Reprint' : 'Print' }} label</button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div id="ct-empty" class="ct-empty"><span><i class="mdi mdi-package-variant"></i></span><strong>No cartons found</strong><p>Try a different search or status filter, or create cartons in the package station.</p></div>
        @endif
        <footer><span>{{ $cartons->total() }} {{ Str::plural('carton', $cartons->total()) }}</span>@if ($cartons->hasPages())<span class="ml-3">{{ $cartons->links() }}</span>@endif</footer>
    </section>
</div>
<div id="ct-toast" class="ct-toast" role="status" aria-live="polite"></div>
@endsection

@push('styles')
<style>
.ct-shell{--navy:#00416b;--ink:#344054;--muted:#667085;--line:#e2e2e2;padding-top:16px;padding-bottom:45px;color:var(--ink);font-family:"Poppins","Segoe UI",sans-serif;font-size:12px}.page-wrapper,.page-breadcrumb{background:#f8f5f2}.page-breadcrumb{padding-top:14px;padding-bottom:12px}.page-breadcrumb .page-title{color:#10213a;font-family:"Poppins","Segoe UI",sans-serif;font-size:20px;font-weight:700;text-transform:none}.ct-page-subtitle{color:#667085;font-size:12px}.page-breadcrumb .breadcrumb{padding:0;background:transparent;font-size:12px}.ct-search-form{width:min(575px,100%);margin-bottom:23px;display:flex;gap:9px}.ct-search-form input{height:44px;min-width:0;flex:1;padding:0 16px;border:1px solid #cdd6df;border-radius:10px;outline:0;color:#344054;background:#fff;font-size:12px;box-shadow:0 1px 2px rgba(16,24,40,.04)}.ct-search-form input:focus{border-color:var(--navy);box-shadow:0 0 0 4px rgba(0,65,107,.1)}.ct-search-form input::placeholder{color:#98a2b3}.ct-search-form>button[type=submit]{height:42px;padding:0 21px;border:0;border-radius:10px;color:#fff;background:var(--navy);font-size:11px;font-weight:700}.ct-clear{padding:0 5px;border:0;color:var(--navy);background:transparent;font-size:11px;font-weight:600}.ct-tabs{margin-bottom:27px;display:flex;gap:23px;overflow-x:auto;border-bottom:1px solid #dde3e8;scrollbar-width:thin}.ct-tabs button{position:relative;flex:0 0 auto;padding:9px 4px 13px;border:0;color:#56637a;background:transparent;font-size:11px;font-weight:500;white-space:nowrap;cursor:pointer}.ct-tabs button span{min-width:21px;height:21px;margin-left:5px;padding:0 6px;display:inline-grid;place-items:center;border-radius:99px;color:#344054;background:#f3f5f7;font-size:9px}.ct-tabs button.active{color:var(--navy);font-weight:600}.ct-tabs button.active:after{content:"";position:absolute;right:0;bottom:-1px;left:0;height:2px;background:var(--navy)}.ct-tabs button.active span{color:var(--navy);background:#e8f0f6}.ct-card{overflow:hidden;border:1px solid #d9e0e6;border-radius:18px;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.02)}.ct-table{min-width:960px;color:#56637a;font-size:11px}.ct-table th{height:42px;padding:10px 16px;border-top:0;border-bottom:1px solid #edf0f2;color:#56637a;background:#fff;font-size:10px;font-weight:500;white-space:nowrap}.ct-table td{height:65px;padding:12px 16px;border-top:1px solid #edf0f2;vertical-align:middle;line-height:1.55}.ct-table tbody tr:first-child td{border-top:0}.ct-table td a,.ct-work-order strong{color:#566b88;font-weight:600;text-decoration:none}.ct-table td a:hover{color:var(--navy)}.ct-work-order{width:200px;border-right:1px solid #edf0f2;vertical-align:top!important}.ct-work-order strong,.ct-work-order small{display:block}.ct-work-order small{margin-top:1px;color:#344054;font-size:9px;font-weight:400}.ct-prepacked{margin-left:5px;padding:3px 7px;border-radius:99px;color:#00698f;background:#eaf7ff;font-size:9px;font-weight:600;white-space:nowrap}.ct-status{padding:5px 10px;border-radius:99px;color:#344054;background:#f1efed;font-size:9px;font-weight:700;white-space:nowrap}.ct-print{min-height:40px;padding:5px 13px;border:0;border-radius:16px;color:#fff;background:var(--navy);font-size:10px;font-weight:700;line-height:1.25;white-space:nowrap;cursor:pointer}.ct-card footer{height:40px;padding:0 20px;display:flex;align-items:center;border-top:1px solid #edf0f2;color:#56637a;font-size:10px}.ct-empty{min-height:230px;padding:30px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.ct-empty[hidden]{display:none}.ct-empty>span{width:52px;height:52px;display:grid;place-items:center;border-radius:12px;color:var(--navy);background:#e8f0f6;font-size:24px}.ct-empty strong{margin-top:11px;color:var(--navy);font-size:14px}.ct-empty p{margin:3px 0 0;color:var(--muted);font-size:10px}.ct-toast{position:fixed;z-index:70;left:50%;bottom:28px;max-width:calc(100% - 30px);padding:11px 16px;border-radius:9px;color:#fff;background:#152536;box-shadow:0 12px 30px rgba(0,0,0,.2);font-size:11px;font-weight:700;opacity:0;transform:translate(-50%,20px);pointer-events:none;transition:.2s}.ct-toast.show{opacity:1;transform:translate(-50%,0)}
@media(max-width:767px){.ct-shell{padding-inline:10px}.ct-search-form{margin-bottom:15px}.ct-tabs{margin-bottom:17px;gap:15px}.ct-card{border-radius:14px}.ct-table th,.ct-table td{padding-inline:13px}}@media(max-width:480px){.ct-search-form{flex-wrap:wrap}.ct-search-form input{flex-basis:calc(100% - 88px)}.ct-search-form>button[type=submit]{padding-inline:17px}.ct-clear{width:100%;text-align:left}}
</style>
@endpush

@push('scripts')
<script src="{{ asset('pack-pos/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('pack-pos/carton-label.js') }}?v={{ filemtime(public_path('pack-pos/carton-label.js')) }}"></script>
<script>
    window.__PACK_POS_LABEL__ = @json($labelConfig);
</script>
<script>
$(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    function toast(message) { var $t = $('#ct-toast').text(message).addClass('show'); clearTimeout(window.ctToastTimer); window.ctToastTimer = setTimeout(function () { $t.removeClass('show'); }, 3200); }
    $('.ct-tabs button').on('click', function () { window.location.href = $(this).data('href'); });
    // A popup confirms the print; a reprint also asks for a reason, which is saved with the print record.
    // The label then prints from a hidden frame, so the operator stays on this page.
    $('.ct-print').on('click', function () {
        var $button = $(this), number = $button.data('number'), reprint = Number($button.data('printed')) > 0;
        Swal.fire({
            icon: reprint ? 'warning' : 'question',
            title: reprint ? 'Reprint label' : 'Print label',
            html: reprint ? 'The label for <b>' + $('<div>').text(number).html() + '</b> was already printed. Enter the reason for reprinting it.' : 'Print the label for <b>' + $('<div>').text(number).html() + '</b>?',
            input: reprint ? 'textarea' : undefined,
            inputPlaceholder: 'Reason for reprint',
            inputAttributes: reprint ? { maxlength: 500, 'aria-label': 'Reason for reprint' } : undefined,
            inputValidator: reprint ? function (value) { return (!value || value.trim().length < 3) ? 'Enter a reason of at least 3 characters.' : null; } : undefined,
            showCancelButton: true,
            confirmButtonText: reprint ? 'Reprint label' : 'Print label',
            confirmButtonColor: '#00416b',
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function (value) {
                return $.ajax({ url: $button.data('print'), method: 'POST', dataType: 'json', data: { reason: reprint ? value.trim() : null }, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                    .then(function (response) { return response.data; })
                    .catch(function (xhr) { Swal.showValidationMessage((xhr.responseJSON || {}).message || 'Could not print the label.'); });
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) { return; }
            $button.data('printed', result.value.copy_number).attr('data-printed', result.value.copy_number).text('Reprint label');
            window.CartonLabel.print([result.value.label]);
        });
    });
});
</script>
@endpush
