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
        <input id="ct-search" type="search" name="q" value="" placeholder="Find by carton, seal, invoice or order number" autocomplete="off">
        <button type="submit">Search</button>
        <a id="ct-clear" class="ct-clear" href="#" hidden>Clear</a>
    </form>

    <nav class="ct-tabs" aria-label="Carton status filters">
        <button class="{{ $activeStatus === '' ? 'active' : '' }}" type="button" data-status="">All <span>{{ array_sum($counts) }}</span></button>
        @foreach (['CONTENTS_VERIFIED', 'AWAITING_WEIGHT', 'WEIGHT_FAILED', 'WEIGHT_VERIFIED', 'AWAITING_APPROVAL', 'VERIFIED', 'SEALED', 'REOPENED', 'CANCELLED'] as $status)
            <button class="{{ $activeStatus === $status ? 'active' : '' }}" type="button" data-status="{{ $status }}">{{ ucfirst(strtolower(str_replace('_', ' ', $status))) }} <span>{{ $counts[$status] ?? 0 }}</span></button>
        @endforeach
    </nav>

    <section class="ct-card">
        <div class="table-responsive ct-table-wrap">
            <table id="cartonsDataTable" class="table ct-table mb-0 w-100">
                <thead><tr><th>Order / invoice</th><th>Carton</th><th>Carton type</th><th>Units</th><th>System weight</th><th>Created</th><th>Status</th><th>Label</th></tr></thead>
            </table>
        </div>
    </section>
</div>
<div id="ct-toast" class="ct-toast" role="status" aria-live="polite"></div>
@endsection

@push('styles')
@include('admin.includes.datatable-style')
<style>
.ct-shell{--navy:#00416b;--ink:#344054;--muted:#667085;--line:#e2e2e2;padding-top:16px;padding-bottom:45px;color:var(--ink);font-family:"Poppins","Segoe UI",sans-serif;font-size:12px}.page-wrapper,.page-breadcrumb{background:#f8f5f2}.page-breadcrumb{padding-top:14px;padding-bottom:12px}.page-breadcrumb .page-title{color:#10213a;font-family:"Poppins","Segoe UI",sans-serif;font-size:20px;font-weight:700;text-transform:none}.ct-page-subtitle{color:#667085;font-size:12px}.page-breadcrumb .breadcrumb{padding:0;background:transparent;font-size:12px}.ct-search-form{width:min(575px,100%);margin-bottom:23px;display:flex;gap:9px}.ct-search-form input{height:44px;min-width:0;flex:1;padding:0 16px;border:1px solid #cdd6df;border-radius:10px;outline:0;color:#344054;background:#fff;font-size:12px;box-shadow:0 1px 2px rgba(16,24,40,.04)}.ct-search-form input:focus{border-color:var(--navy);box-shadow:0 0 0 4px rgba(0,65,107,.1)}.ct-search-form input::placeholder{color:#98a2b3}.ct-search-form>button[type=submit]{height:42px;padding:0 21px;border:0;border-radius:10px;color:#fff;background:var(--navy);font-size:11px;font-weight:700}.ct-clear{padding:0 5px;border:0;color:var(--navy);background:transparent;font-size:11px;font-weight:600}.ct-tabs{margin-bottom:27px;display:flex;gap:23px;overflow-x:auto;border-bottom:1px solid #dde3e8;scrollbar-width:thin}.ct-tabs button{position:relative;flex:0 0 auto;padding:9px 4px 13px;border:0;color:#56637a;background:transparent;font-size:11px;font-weight:500;white-space:nowrap;cursor:pointer}.ct-tabs button span{min-width:21px;height:21px;margin-left:5px;padding:0 6px;display:inline-grid;place-items:center;border-radius:99px;color:#344054;background:#f3f5f7;font-size:9px}.ct-tabs button.active{color:var(--navy);font-weight:600}.ct-tabs button.active:after{content:"";position:absolute;right:0;bottom:-1px;left:0;height:2px;background:var(--navy)}.ct-tabs button.active span{color:var(--navy);background:#e8f0f6}.ct-card{overflow:hidden;border:1px solid #d9e0e6;border-radius:18px;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.02)}.ct-table{min-width:960px;color:#56637a;font-size:11px}.ct-table th{height:42px;padding:10px 16px;border-top:0;border-bottom:1px solid #edf0f2;color:#56637a;background:#fff;font-size:10px;font-weight:500;white-space:nowrap}.ct-table td{height:65px;padding:12px 16px;border-top:1px solid #edf0f2;vertical-align:middle;line-height:1.55}.ct-table tbody tr:first-child td{border-top:0}.ct-table td a,.ct-work-order strong{color:#566b88;font-weight:600;text-decoration:none}.ct-table td a:hover{color:var(--navy)}.ct-work-order{width:200px;border-right:1px solid #edf0f2;vertical-align:top!important}.ct-work-order strong,.ct-work-order small{display:block}.ct-work-order small{margin-top:1px;color:#344054;font-size:9px;font-weight:400}.ct-prepacked{margin-left:5px;padding:3px 7px;border-radius:99px;color:#00698f;background:#eaf7ff;font-size:9px;font-weight:600;white-space:nowrap}.ct-status{padding:5px 10px;border-radius:99px;color:#344054;background:#f1efed;font-size:9px;font-weight:700;white-space:nowrap}.ct-print{min-height:40px;padding:5px 13px;border:0;border-radius:16px;color:#fff;background:var(--navy);font-size:10px;font-weight:700;line-height:1.25;white-space:nowrap;cursor:pointer}.ct-card footer{height:40px;padding:0 20px;display:flex;align-items:center;border-top:1px solid #edf0f2;color:#56637a;font-size:10px}.ct-empty{min-height:230px;padding:30px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.ct-empty[hidden]{display:none}.ct-empty>span{width:52px;height:52px;display:grid;place-items:center;border-radius:12px;color:var(--navy);background:#e8f0f6;font-size:24px}.ct-empty strong{margin-top:11px;color:var(--navy);font-size:14px}.ct-empty p{margin:3px 0 0;color:var(--muted);font-size:10px}.ct-toast{position:fixed;z-index:70;left:50%;bottom:28px;max-width:calc(100% - 30px);padding:11px 16px;border-radius:9px;color:#fff;background:#152536;box-shadow:0 12px 30px rgba(0,0,0,.2);font-size:11px;font-weight:700;opacity:0;transform:translate(-50%,20px);pointer-events:none;transition:.2s}.ct-toast.show{opacity:1;transform:translate(-50%,0)}
.ct-work-order{vertical-align:middle!important}
#cartonsDataTable_wrapper{padding:0}#cartonsDataTable.dataTable{margin:0!important;border-collapse:collapse!important}#cartonsDataTable.dataTable tbody tr{background:#fff}.ct-card .dataTables_info,.ct-card .dataTables_paginate{color:#56637a!important;font-size:10px}.ct-footer{min-height:48px;padding:6px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;border-top:1px solid #edf0f2}.ct-footer .dataTables_info,.ct-footer .dataTables_paginate{float:none;padding:0;margin:0}.ct-card .dataTables_processing{color:var(--navy)}
/* The theme paints pagination text white for dark backgrounds; this card is white, so override it here. */
.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button{min-width:30px;margin:0 2px;padding:4px 9px;border:1px solid #d9e0e6!important;border-radius:8px;color:var(--navy)!important;-webkit-text-fill-color:var(--navy);background:#fff!important;font-size:10px;font-weight:600!important}.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button:hover{border-color:var(--navy)!important;color:var(--navy)!important;-webkit-text-fill-color:var(--navy);background:#e8f0f6!important}.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button.current,.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover,.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button.current:focus{border-color:var(--navy)!important;color:#fff!important;-webkit-text-fill-color:#fff;background:var(--navy)!important}.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,.ct-card .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover{border-color:#edf0f2!important;color:#98a2b3!important;-webkit-text-fill-color:#98a2b3;background:#fff!important;cursor:default}
@media(max-width:767px){.ct-shell{padding-inline:10px}.ct-search-form{margin-bottom:15px}.ct-tabs{margin-bottom:17px;gap:15px}.ct-card{border-radius:14px}.ct-table th,.ct-table td{padding-inline:13px}}@media(max-width:480px){.ct-search-form{flex-wrap:wrap}.ct-search-form input{flex-basis:calc(100% - 88px)}.ct-search-form>button[type=submit]{padding-inline:17px}.ct-clear{width:100%;text-align:left}}
</style>
@endpush

@push('scripts')
@include('admin.includes.datatable-js')
<script src="{{ asset('pack-pos/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('pack-pos/carton-label.js') }}?v={{ filemtime(public_path('pack-pos/carton-label.js')) }}"></script>
<script>
    window.__PACK_POS_LABEL__ = @json($labelConfig);
</script>
<script>
$(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    function toast(message) { var $t = $('#ct-toast').text(message).addClass('show'); clearTimeout(window.ctToastTimer); window.ctToastTimer = setTimeout(function () { $t.removeClass('show'); }, 3200); }
    function escapeHtml(value) { return $('<div>').text(value == null ? '' : value).html(); }
    var activeStatus = @json($activeStatus);
    var table = $('#cartonsDataTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        lengthChange: false,
        pageLength: 25,
        dom: 't<"ct-footer"ip>',
        ajax: {
            url: @json(route('cartons.index')),
            data: function (data) { data.q = $('#ct-search').val().trim(); data.status = activeStatus; },
            dataSrc: function (json) { updateCounts(json.counts || {}); return json.data; },
            error: function () { toast('The carton list could not be loaded. Please try again.'); }
        },
        // Consecutive cartons of one order share a single merged order cell. Rows arrive ordered by order,
        // so merging the current page's runs is enough; an order split across two pages repeats its cell.
        drawCallback: function () {
            var runs = [], last = null;
            this.api().rows({ page: 'current' }).every(function () {
                var row = this.data(), node = this.node();
                if (last && last.order === row.order_number) { last.nodes.push(node); } else { last = { order: row.order_number, nodes: [node] }; runs.push(last); }
            });
            runs.forEach(function (run) {
                var $cell = $(run.nodes[0]).children().first(), count = run.nodes.length;
                $cell.attr('rowspan', count).append('<small>' + count + ' ' + (count === 1 ? 'carton' : 'cartons') + '</small>');
                run.nodes.slice(1).forEach(function (node) { $(node).children().first().remove(); });
            });
        },
        language: { processing: 'Loading cartons…', emptyTable: 'No cartons found. Create cartons in the package station.', zeroRecords: 'No cartons found. Try a different search or status filter.', info: '_TOTAL_ cartons', infoEmpty: '0 cartons', infoFiltered: '' },
        columns: [
            { data: 'order_number', className: 'ct-work-order', render: function (data, type, row) { return type !== 'display' ? data : '<strong>' + escapeHtml(data) + '</strong>' + (row.invoice_number ? '<small>Invoice: ' + escapeHtml(row.invoice_number) + '</small>' : ''); } },
            { data: 'carton_number', render: function (data, type, row) { return type !== 'display' ? data : '<a href="' + escapeHtml(row.show_url) + '">' + escapeHtml(data) + '</a>' + (row.weight_flag ? ' <span class="ct-prepacked">' + escapeHtml(row.weight_flag) + '</span>' : ''); } },
            { data: 'carton_type_name', render: $.fn.dataTable.render.text() },
            { data: 'unit_count', render: $.fn.dataTable.render.text() },
            { data: 'system_weight_g', render: $.fn.dataTable.render.text() },
            { data: 'created', render: $.fn.dataTable.render.text() },
            { data: 'status', render: function (data, type) { return type !== 'display' ? data : '<span class="ct-status">' + escapeHtml(data) + '</span>'; } },
            { data: 'print_url', render: function (data, type, row) { return type !== 'display' ? data : '<button class="ct-print" type="button" data-print="' + escapeHtml(data) + '" data-printed="' + row.label_prints_count + '" data-number="' + escapeHtml(row.carton_number) + '">' + (row.label_prints_count > 0 ? 'Reprint' : 'Print') + ' label</button>'; } }
        ]
    });
    function updateCounts(counts) {
        var total = 0;
        $('.ct-tabs button').not('[data-status=""]').each(function () {
            var count = Number(counts[$(this).data('status')] || 0);
            total += count;
            $(this).find('span').text(count);
        });
        $('.ct-tabs button[data-status=""] span').text(total);
    }
    $('.ct-tabs button').on('click', function () {
        activeStatus = String($(this).data('status') || '');
        $('.ct-tabs button').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });
    $('#ct-search-form').on('submit', function (event) {
        event.preventDefault();
        $('#ct-clear').prop('hidden', !$('#ct-search').val().trim());
        table.ajax.reload();
    });
    $('#ct-clear').on('click', function (event) {
        event.preventDefault();
        $('#ct-search').val('');
        $(this).prop('hidden', true);
        table.ajax.reload();
    });
    // A popup confirms the print; a reprint also asks for a reason, which is saved with the print record.
    // The label then prints from a hidden frame, so the operator stays on this page.
    $('#cartonsDataTable').on('click', '.ct-print', function () {
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
