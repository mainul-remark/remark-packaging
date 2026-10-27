@extends('admin.master')
@section('title', 'Carton Cross-check')
@section('content')
<div class="page-breadcrumb border-bottom mb-2">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-checkbox-marked-circle-outline mr-2"></i>Carton cross-check</h5>
            <p class="cc-page-subtitle mb-0">Validate carton identity, contents and gross weight before dispatch</p>
        </div>
        <div class="col-md-5 align-self-center py-2">
            <nav class="d-flex justify-content-md-end" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li><li class="breadcrumb-item active">Carton cross-check</li></ol>
            </nav>
        </div>
    </div>
</div>
<div class="container-fluid cc-shell" id="cc-page" data-lookup="{{ route('carton-cross-check.lookup') }}" data-decision="{{ url('admin/carton-cross-check') }}">
    <section class="cc-panel cc-session" aria-label="Today's security session">
        <div class="cc-session-title"><span class="cc-shield"><i class="mdi mdi-shield-outline"></i></span><div><small>Today's security session</small><strong>Outbound dispatch verification</strong></div></div>
        <div class="cc-metrics">
            <div><small>Checked</small><strong id="cc-checked">{{ $summary['checked'] }}</strong></div>
            <div><small>Cleared</small><strong class="cc-success" id="cc-cleared">{{ $summary['cleared'] }}</strong></div>
            <div><small>Flagged</small><strong class="cc-danger" id="cc-flagged">{{ $summary['flagged'] }}</strong></div>
            <div><small>Average variance</small><strong>{{ $summary['average'] === null ? '—' : number_format((float) $summary['average'], 1).' g' }}</strong></div>
        </div>
    </section>
    <div class="cc-grid">
        <aside>
            <section class="cc-panel cc-scanner-card">
                <header><span><b>01</b> Carton lookup</span><span class="cc-ready"><i></i> Scanner ready</span></header>
                <div class="cc-scanner-intro"><span class="cc-barcode-icon"><i class="mdi mdi-barcode-scan"></i></span><div><h2>Scan carton label</h2><p>Use the handheld scanner or enter the carton number manually.</p></div></div>
                <form id="cc-lookup-form" novalidate>
                    <label for="cc-carton-code">Carton barcode</label>
                    <div class="cc-input-group"><i class="mdi mdi-barcode"></i><input id="cc-carton-code" type="text" placeholder="e.g. SO-1001-C001" autocomplete="off"><button type="submit"><i class="mdi mdi-magnify"></i> Load</button></div>
                    <p class="cc-shortcuts">Press <kbd>Enter</kbd> to search · <kbd>F2</kbd> to focus</p>
                    <p id="cc-error" class="cc-error" role="alert" hidden>Enter or scan a carton barcode first.</p>
                </form>
            </section>
            <section id="cc-work-order" class="cc-panel cc-work-order" hidden>
                <header><span><b>02</b> Order</span><em id="cc-wo-status"></em></header>
                <div class="cc-wo-title"><span>SO</span><div><small>Order</small><strong id="cc-wo-number"></strong><p id="cc-wo-sub"></p></div></div>
                <dl><div><dt>Invoice</dt><dd id="cc-wo-invoice"></dd></div><div><dt>Destination</dt><dd id="cc-wo-destination"></dd></div><div><dt>Packed by</dt><dd id="cc-wo-packer"></dd></div></dl>
            </section>
        </aside>
        <main id="cc-empty" class="cc-panel cc-empty-state">
            <span><i class="mdi mdi-package-variant-closed"></i></span>
            <h2>No carton loaded</h2>
            <p>Scan or enter a carton label barcode to load its order, contents and weight check.</p>
        </main>
        <main id="cc-loaded" class="cc-panel cc-loaded" hidden>
            <header class="cc-loaded-header"><span class="cc-box-icon"><i class="mdi mdi-package-variant-closed"></i></span><div><small>Carton under review</small><h2 id="cc-loaded-code"></h2><p id="cc-loaded-sub"></p></div><button id="cc-new-scan" type="button"><i class="mdi mdi-refresh"></i> New scan</button></header>
            <section class="cc-loaded-section cc-verification">
                <div class="cc-section-title"><span>01</span><div><small>Physical verification</small><h3>Carton and weight</h3></div><em id="cc-weight-result"></em></div>
                <div class="row mx-0 p-3">
                    <div class="col-md-6">
                        <div class="cc-detail-grid"><div><small>Carton type</small><strong id="cc-type"></strong></div><div><small>Product units</small><strong id="cc-units"></strong></div></div>
                        <div class="cc-weight-breakdown">
                            <span><small>Product total</small><strong id="cc-product-total"></strong></span><i>+</i>
                            <span><small>Packaging</small><strong id="cc-packaging-weight"></strong></span><i>=</i>
                            <span><small>Expected gross</small><strong id="cc-breakdown-expected"></strong></span>
                        </div>
                    </div>
                    <div class="col-md-6"><div class="cc-weight-check"><span><i class="mdi mdi-scale-balance"></i></span><div><small>Security re-weigh</small><strong id="cc-recorded"></strong></div><div class="cc-weight-expected"><small>Expected gross</small><strong id="cc-expected"></strong></div><label>Checker weight <span><input type="number" id="cc-checker-weight" step="0.001" min="0"><b>g</b></span></label></div></div>
                </div>
            </section>
            <section class="cc-loaded-section">
                <div class="cc-section-title"><span>02</span><div><small>Carton contents</small><h3>Products in carton</h3></div><em id="cc-carton-status"></em></div>
                <div class="table-responsive"><table class="table cc-table mb-0"><thead><tr><th>Product</th><th>Product barcode</th><th class="text-right">Quantity</th><th class="text-right">Unit weight</th><th class="text-right">Line weight</th></tr></thead><tbody id="cc-items"></tbody><tfoot id="cc-items-foot"></tfoot></table></div>
            </section>
            <section class="cc-loaded-section cc-decision-section">
                <div class="cc-section-title"><span>03</span><div><small>Final checkpoint</small><h3>Security decision</h3></div><em id="cc-decision-status">Complete all checks</em></div>
                <div id="cc-decision-area">
                    <div class="cc-decision-grid">
                        <div class="cc-checklist">
                            <div class="cc-field-heading"><strong>Inspection checklist</strong><span id="cc-check-count">0 / 3 complete</span></div>
                            <label class="cc-check-row" for="cc-label-ok"><input type="checkbox" id="cc-label-ok"><span class="cc-check-icon"><i class="mdi mdi-check"></i></span><span><strong>Label and barcode match</strong><small>Carton label matches the loaded work order.</small></span></label>
                            <label class="cc-check-row" for="cc-seal-ok"><input type="checkbox" id="cc-seal-ok"><span class="cc-check-icon"><i class="mdi mdi-check"></i></span><span><strong>Security seal is intact</strong><small>Seal number and physical condition are verified.</small></span></label>
                            <label class="cc-check-row" for="cc-condition-ok"><input type="checkbox" id="cc-condition-ok"><span class="cc-check-icon"><i class="mdi mdi-check"></i></span><span><strong>Carton condition is acceptable</strong><small>No damage, tampering or visible leakage found.</small></span></label>
                        </div>
                        <div class="cc-note-field">
                            <label class="cc-field-heading" for="cc-note"><strong>Security note</strong><span id="cc-note-requirement">Optional</span></label>
                            <textarea id="cc-note" rows="5" maxlength="500" placeholder="Record any observation or discrepancy..."></textarea>
                            <p id="cc-decision-help">Complete all three inspection checks to clear this carton.</p>
                        </div>
                    </div>
                    <footer class="cc-decision-footer">
                        <div><p id="cc-decision-msg" role="alert"></p><span><kbd>F4</kbd> Clear carton</span><span><kbd>F8</kbd> Flag review</span></div>
                        <div class="cc-decision-actions">
                            <button type="button" class="cc-flag-button" data-decision="FLAGGED"><i class="mdi mdi-flag-outline"></i> Flag for review</button>
                            <button type="button" class="cc-clear-button" data-decision="CLEARED" disabled><i class="mdi mdi-shield-check-outline"></i> Clear for dispatch</button>
                        </div>
                    </footer>
                </div>
                <div class="px-3 pb-3" id="cc-decided" hidden></div>
            </section>
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
.cc-shell{--navy:#00416b;--ink:#17243a;--muted:#667085;--line:#d7e0e8;--soft:#f8fafb;--green:#027a48;--red:#b42318;padding-top:16px;padding-bottom:40px;color:var(--ink);font-family:"Poppins","Segoe UI",sans-serif;font-size:12px}.page-wrapper,.page-breadcrumb{background:#f8f5f2}.page-breadcrumb{padding-top:14px;padding-bottom:12px}.page-breadcrumb .page-title{color:#10213a;font-family:"Poppins","Segoe UI",sans-serif;font-size:20px;font-weight:700;text-transform:none}.cc-page-subtitle{color:#667085;font-size:12px}.page-breadcrumb .breadcrumb{padding:0;background:transparent;font-size:12px}.cc-panel{border:1px solid var(--line);border-radius:17px;background:#fff;box-shadow:0 2px 4px rgba(16,24,40,.05)}.cc-session{min-height:80px;margin-bottom:16px;padding:16px 20px;display:flex;align-items:center;gap:25px}.cc-session-title{display:flex;align-items:center;gap:13px;min-width:355px}.cc-shield{width:44px;height:44px;display:grid;place-items:center;border-radius:10px;color:#fff;background:var(--navy);font-size:21px}.cc-session-title small,.cc-session-title strong{display:block}.cc-session-title small{margin-bottom:3px;color:#98a2b3;font-size:9px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}.cc-session-title strong{color:#252d3a;font-size:14px}.cc-metrics{margin-left:auto;display:grid;grid-template-columns:repeat(4,minmax(115px,1fr))}.cc-metrics>div{min-height:46px;padding:2px 16px;border-left:1px solid var(--line)}.cc-metrics small,.cc-metrics strong{display:block}.cc-metrics small{color:#98a2b3;font-size:9px;font-weight:600;text-transform:uppercase}.cc-metrics strong{margin-top:2px;color:#273142;font-size:18px}.cc-metrics .cc-success{color:var(--green)}.cc-metrics .cc-danger{color:var(--red)}.cc-grid{display:grid;grid-template-columns:minmax(290px,4fr) minmax(520px,8fr);gap:16px;align-items:start}.cc-grid>aside{display:grid;gap:16px}.cc-scanner-card{overflow:hidden}.cc-scanner-card>header,.cc-work-order>header{height:48px;padding:0 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);color:var(--navy);font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.cc-scanner-card header b,.cc-work-order header b{margin-right:8px;color:#98a2b3}.cc-ready{display:flex;align-items:center;gap:6px;color:#078845}.cc-ready i{width:6px;height:6px;border-radius:50%;background:#12b76a}.cc-scanner-intro{padding:25px 20px 18px;display:flex;align-items:center;gap:16px}.cc-barcode-icon,.cc-empty-state>span{width:56px;height:56px;display:grid;flex:0 0 auto;place-items:center;border-radius:12px;color:var(--navy);background:#e8f0f6;font-size:27px}.cc-scanner-intro h2,.cc-empty-state h2,.cc-loaded-header h2,.cc-section-title h3{margin:0;color:var(--navy);font-weight:700}.cc-scanner-intro h2{font-size:16px}.cc-scanner-intro p{margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.5}.cc-scanner-card form{padding:0 20px 20px}.cc-scanner-card form>label{margin-bottom:7px;color:#344054;font-size:9px;font-weight:800;text-transform:uppercase}.cc-input-group{height:52px;display:flex;align-items:center;overflow:hidden;border:2px solid #cbd5df;border-radius:11px;background:#fff;transition:.15s}.cc-input-group:focus-within{border-color:var(--navy);box-shadow:0 0 0 4px rgba(0,65,107,.1)}.cc-input-group>i{margin-left:13px;color:#98a2b3;font-size:20px}.cc-input-group input{height:100%;min-width:0;flex:1;padding:0 12px;border:0;outline:0;color:var(--navy);font-size:12px;font-weight:600}.cc-input-group input::placeholder{color:#98a2b3;font-weight:400}.cc-input-group button{height:100%;padding:0 20px;border:0;color:#fff;background:var(--navy);font-size:11px;font-weight:700;cursor:pointer}.cc-input-group button i{margin-right:5px;font-size:16px}.cc-shortcuts{margin:9px 0 0;color:var(--muted);font-size:9px}.cc-shortcuts kbd{padding:2px 5px;border:1px solid var(--line);border-radius:4px;color:#667085;background:#fafafa;box-shadow:none;font-family:inherit;font-size:8px}.cc-error{margin:10px 0 0;padding:8px 10px;border:1px solid #fecdca;border-radius:7px;color:var(--red);background:#fef3f2;font-size:10px}.cc-empty-state{min-height:322px;padding:30px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;border-style:dashed;text-align:center}.cc-empty-state h2{font-size:15px}.cc-empty-state p{max-width:390px;margin:0;color:var(--muted);font-size:11px}.cc-work-order{overflow:hidden}.cc-work-order header em,.cc-section-title em{padding:5px 8px;border-radius:99px;color:#b54708;background:#fffaeb;font-size:8px;font-style:normal;font-weight:800}.cc-wo-title{padding:15px 20px;display:flex;align-items:center;gap:10px}.cc-wo-title>span{width:39px;height:39px;display:grid;place-items:center;border-radius:8px;color:var(--navy);background:#e8f0f6;font-weight:800}.cc-wo-title small,.cc-wo-title strong,.cc-wo-title p{display:block;margin:0}.cc-wo-title small,.cc-work-order dt{color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-wo-title strong{color:var(--navy);font-size:11px}.cc-wo-title p{color:var(--muted);font-size:8px}.cc-work-order dl{margin:0;padding:13px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;border-top:1px solid #eef1f4}.cc-work-order dd{margin:2px 0 0;color:#344054;font-size:9px;font-weight:600}.cc-loaded{overflow:hidden}.cc-loaded[hidden],.cc-work-order[hidden],.cc-empty-state[hidden]{display:none}.cc-loaded-header{padding:14px 20px;display:flex;align-items:center;gap:12px;color:#fff;background:var(--navy)}.cc-box-icon{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.2);border-radius:9px;background:rgba(255,255,255,.1);font-size:20px}.cc-loaded-header small{color:rgba(255,255,255,.7);font-size:8px;text-transform:uppercase}.cc-loaded-header h2{color:#fff;font-size:17px}.cc-loaded-header p{margin:1px 0 0;color:rgba(255,255,255,.7);font-size:9px}.cc-loaded-header button{margin-left:auto;padding:8px 11px;border:1px solid rgba(255,255,255,.3);border-radius:8px;color:#fff;background:transparent;font-size:10px;font-weight:700}.cc-loaded-section+.cc-loaded-section{border-top:1px solid var(--line)}.cc-section-title{min-height:63px;padding:10px 20px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)}.cc-section-title>span{width:34px;height:34px;display:grid;place-items:center;border-radius:8px;color:var(--navy);background:#e8f0f6;font-size:10px;font-weight:800}.cc-section-title small{display:block;color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-section-title h3{font-size:14px}.cc-section-title em{margin-left:auto}.cc-section-title .cc-pass{color:var(--green);background:#ecfdf3}.cc-table{font-size:10px}.cc-table th{padding:9px 16px;border:0;color:#98a2b3;background:#fcfcfd;font-size:8px;text-transform:uppercase}.cc-table td{padding:11px 16px;vertical-align:middle}.cc-table td small{display:block;color:var(--muted);font-size:8px}.cc-table tfoot{background:#fcfcfd}.cc-detail-grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--line);border-radius:9px;overflow:hidden}.cc-detail-grid>div{padding:11px;border-right:1px solid #edf0f2;border-bottom:1px solid #edf0f2}.cc-detail-grid>div:nth-child(2n){border-right:0}.cc-detail-grid>div:nth-last-child(-n+2){border-bottom:0}.cc-detail-grid small,.cc-weight-check small{display:block;color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-detail-grid strong{color:var(--navy);font-size:10px}.cc-weight-check{padding:14px;display:grid;grid-template-columns:36px 1fr;gap:9px;border:1px solid #a6f4c5;border-radius:10px;background:#f6fef9}.cc-weight-check>span{width:36px;height:36px;display:grid;place-items:center;border-radius:8px;color:var(--green);background:#ecfdf3;font-size:18px}.cc-weight-check strong{font-size:10px}.cc-weight-check label{grid-column:1/-1;margin:5px 0 0;color:#667085;font-size:8px;text-transform:uppercase}.cc-weight-check label>span{display:flex;align-items:center;margin-top:4px}.cc-weight-check input{width:100%;height:35px;padding:6px 30px 6px 9px;border:1px solid #a6f4c5;border-radius:7px;color:var(--navy);background:#fff;font-size:14px;font-weight:700}.cc-weight-check label b{margin-left:-24px;color:#667085;font-size:9px}.cc-input-group.cc-input-error{border-color:#f04438}.cc-error.cc-warn{border-color:#fedf89;color:#93370d;background:#fffaeb;line-height:1.5}.cc-weight-check input.cc-weight-over{border-color:#f04438;box-shadow:0 0 0 3px rgba(240,68,56,.12)}
.cc-weight-breakdown{margin-top:12px;padding:11px 13px;display:grid;grid-template-columns:1fr auto 1fr auto 1fr;align-items:center;gap:9px;border-radius:10px;background:#f8fafc}.cc-weight-breakdown span{min-width:0}.cc-weight-breakdown small,.cc-weight-expected small{display:block;color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-weight-breakdown strong,.cc-weight-expected strong{display:block;margin-top:2px;color:var(--navy);font-size:11px}.cc-weight-breakdown>i{color:#98a2b3;font-size:10px;font-style:normal}.cc-weight-expected{align-self:center;text-align:right}.cc-weight-check{grid-template-columns:36px minmax(0,1fr) auto}.cc-weight-check label{grid-column:1/-1}
.cc-section-title .cc-fail{color:var(--red);background:#fef3f2}.cc-decision-section{box-shadow:inset 0 0 0 transparent}.cc-decision-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:22px;padding:20px}.cc-field-heading{min-height:16px;margin:0 0 9px;display:flex;align-items:center;justify-content:space-between;color:#344054;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.cc-field-heading strong{font:inherit}.cc-field-heading span{color:#98a2b3;font-weight:500}.cc-check-row{position:relative;min-height:66px;margin:0;padding:12px;display:flex;align-items:center;gap:12px;border:1px solid var(--line);border-bottom:0;background:#fff;cursor:pointer}.cc-check-row:first-of-type{border-radius:10px 10px 0 0}.cc-check-row:last-child{border-bottom:1px solid var(--line);border-radius:0 0 10px 10px}.cc-check-row:has(input:checked){background:#f6fef9}.cc-check-row input{position:absolute;width:1px;height:1px;opacity:0}.cc-check-icon{width:22px;height:22px;display:grid;flex:0 0 auto;place-items:center;border:1px solid #cbd5df;border-radius:50%;color:transparent;background:#fff;font-size:15px;transition:.15s}.cc-check-row input:checked+.cc-check-icon{border-color:var(--navy);color:#fff;background:var(--navy)}.cc-check-row input:focus-visible+.cc-check-icon{box-shadow:0 0 0 4px rgba(0,65,107,.12)}.cc-check-row strong,.cc-check-row small{display:block}.cc-check-row strong{color:#344054;font-size:12px}.cc-check-row small{margin-top:3px;color:var(--muted);font-size:9px;font-weight:400}.cc-note-field textarea{width:100%;min-height:118px;padding:12px;border:1px solid #cbd5df;border-radius:10px;outline:0;resize:vertical;color:#344054;background:#fff;font-family:inherit;font-size:11px;line-height:1.5}.cc-note-field textarea:focus{border-color:var(--navy);box-shadow:0 0 0 4px rgba(0,65,107,.1)}.cc-note-field textarea.cc-note-error{border-color:#f04438;background:#fff8f7}.cc-note-field textarea::placeholder{color:#98a2b3}.cc-note-field p{margin:9px 0 0;color:var(--muted);font-size:9px;line-height:1.5}.cc-decision-footer{min-height:66px;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-top:1px solid var(--line);background:#fcfcfd}.cc-decision-footer>div:first-child{color:var(--muted);font-size:9px}.cc-decision-footer>div:first-child>span{margin-right:14px}.cc-decision-footer kbd{padding:2px 5px;border:1px solid var(--line);border-radius:4px;color:#667085;background:#fff;box-shadow:none;font-family:inherit;font-size:8px}.cc-decision-footer #cc-decision-msg{margin:0 0 5px;color:var(--red);font-size:10px;font-weight:600}.cc-decision-footer #cc-decision-msg:empty{display:none}.cc-decision-actions{display:flex;gap:9px}.cc-decision-actions button{min-height:40px;padding:8px 15px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer}.cc-decision-actions button i{margin-right:5px;font-size:16px}.cc-flag-button{border:1px solid #f97066;color:var(--red);background:#fff}.cc-flag-button:hover{background:#fef3f2}.cc-clear-button{border:1px solid var(--green);color:#fff;background:var(--green)}.cc-clear-button:hover{background:#05603a}.cc-clear-button:disabled{border-color:#d0d5dd;color:#98a2b3;background:#eaecf0;cursor:not-allowed}#cc-decided:not([hidden]){margin:14px;padding:14px;border:1px solid #a6f4c5;border-radius:10px;color:#05603a;background:#f6fef9}#cc-decided.cc-decided-flagged{border-color:#fecdca;color:var(--red);background:#fef3f2}
@media(max-width:1100px){.cc-session{align-items:flex-start;flex-direction:column}.cc-session-title{min-width:0}.cc-metrics{width:100%;margin-left:0}.cc-metrics>div:first-child{border-left:0}.cc-grid{grid-template-columns:minmax(280px,4fr) minmax(430px,8fr)}.cc-decision-grid{grid-template-columns:1fr}}@media(max-width:850px){.cc-grid{grid-template-columns:1fr}.cc-empty-state{min-height:260px}.cc-grid>aside{order:1}.cc-grid>main{order:2}}@media(max-width:575px){.cc-shell{padding-inline:10px}.cc-session{padding:15px}.cc-metrics{grid-template-columns:1fr 1fr;row-gap:10px}.cc-metrics>div:nth-child(3){border-left:0}.cc-input-group button{padding:0 14px}.cc-scanner-intro{align-items:flex-start}.cc-empty-state{min-height:230px}.cc-loaded-header{align-items:flex-start;flex-wrap:wrap}.cc-loaded-header button{margin-left:0}.cc-section-title{padding-inline:14px}.cc-detail-grid{margin-bottom:12px}.cc-decision-grid{padding:15px}.cc-decision-footer{align-items:stretch;flex-direction:column}.cc-decision-footer>div:first-child>span{display:none}.cc-decision-actions button{flex:1}}
</style>
@endpush

@push('scripts')
<script src="{{ asset('pack-pos/sweetalert2.all.min.js') }}"></script>
<script>
$(function () {
    var $page = $('#cc-page'), csrf = $('meta[name="csrf-token"]').attr('content');
    var $code = $('#cc-carton-code'), $error = $('#cc-error'), carton = null;
    function esc(text) { return $('<div>').text(text == null ? '' : text).html(); }
    function fmt(value, digits) { return value == null ? '—' : Number(value).toLocaleString('en-US', { maximumFractionDigits: digits === undefined ? 1 : digits }); }
    function reset() { carton = null; $code.val('').focus(); $error.prop('hidden', true); $('#cc-work-order,#cc-loaded').prop('hidden', true); $('#cc-empty').prop('hidden', false); }

    function weightResult() {
        if (!carton) { return; }
        var entered = parseFloat($('#cc-checker-weight').val()), recorded = carton.recorded_weight_g;
        var $badge = $('#cc-weight-result').removeClass('cc-pass cc-fail');
        if (isNaN(entered) || recorded === null) {
            $badge.addClass('cc-fail').text(recorded === null ? 'No recorded weight' : 'Enter checker weight');
        } else {
            var diff = entered - recorded, ok = Math.abs(diff) <= carton.tolerance_g;
            $badge.addClass(ok ? 'cc-pass' : 'cc-fail').text(ok ? 'Within tolerance (' + fmt(diff) + ' g)' : 'Outside tolerance (' + fmt(diff) + ' g)');
        }
        decisionState();
    }

    function decisionState() {
        if (!carton) { return; }
        var completed = $('#cc-label-ok,#cc-seal-ok,#cc-condition-ok').filter(':checked').length;
        var entered = parseFloat($('#cc-checker-weight').val()), recorded = carton.recorded_weight_g;
        var withinTolerance = !isNaN(entered) && recorded !== null && Math.abs(entered - recorded) <= carton.tolerance_g;
        var canClear = completed === 3 && withinTolerance;
        $('#cc-check-count').text(completed + ' / 3 complete');
        $('#cc-decision-status').removeClass('cc-pass cc-fail').addClass(canClear ? 'cc-pass' : (!withinTolerance ? 'cc-fail' : '')).text(!withinTolerance ? 'Flag recommended' : canClear ? 'Ready to clear' : 'Complete all checks');
        $('#cc-decision-help').text(!withinTolerance ? 'Weight is outside tolerance. This carton cannot be cleared for dispatch.' : completed < 3 ? 'Complete all three inspection checks to clear this carton.' : 'All checks passed. The carton is ready for final clearance.');
        $('[data-decision="CLEARED"]').prop('disabled', !canClear);
    }

    function render(c) {
        carton = c;
        $('#cc-loaded-code').text(c.carton_number);
        $('#cc-loaded-sub').text(fmt(c.unit_count, 0) + ' units · ' + c.status.toLowerCase());
        $('#cc-wo-number').text(c.order.number); $('#cc-wo-sub').text(c.order.customer); $('#cc-wo-status').text(c.status);
        $('#cc-wo-invoice').text(c.order.invoice || '—'); $('#cc-wo-destination').text(c.order.destination); $('#cc-wo-packer').text(c.packed_by || '—');
        $('#cc-carton-status').text(c.status);
        var total = 0;
        $('#cc-items').html(c.items.map(function (i) {
            var line = i.quantity * i.unit_weight_g; total += line;
            return '<tr><td><strong>' + esc(i.sku) + '</strong><small>' + esc(i.name) + '</small></td><td>' + esc(i.barcode || '—') + '</td><td class="text-right">' + fmt(i.quantity, 0) + '</td><td class="text-right">' + fmt(i.unit_weight_g) + ' g</td><td class="text-right font-weight-bold">' + fmt(line) + ' g</td></tr>';
        }).join(''));
        $('#cc-items-foot').html('<tr><td colspan="2"><strong>Recorded carton contents</strong></td><td class="text-right"><strong>' + fmt(c.unit_count, 0) + ' units</strong></td><td></td><td class="text-right"><strong>' + fmt(total) + ' g</strong></td></tr>');
        var packagingWeight = c.expected_weight_g - total;
        $('#cc-type').text(c.carton_type || '—'); $('#cc-units').text(fmt(c.unit_count, 0) + ' units');
        $('#cc-product-total').text(fmt(total) + ' g'); $('#cc-packaging-weight').text(fmt(packagingWeight) + ' g');
        $('#cc-breakdown-expected,#cc-expected').text(fmt(c.expected_weight_g) + ' g');
        $('#cc-recorded').text('Manual Recorded weight: ' + (c.recorded_weight_g === null ? '—' : fmt(c.recorded_weight_g) + ' g'));
        $('#cc-checker-weight').val(c.recorded_weight_g === null ? '' : c.recorded_weight_g).removeClass('cc-weight-over');
        $('#cc-label-ok,#cc-seal-ok,#cc-condition-ok').prop('checked', true); $('#cc-note').val('').removeClass('cc-note-error'); $('#cc-note-requirement').text('Optional').removeClass('cc-danger'); $('#cc-decision-msg').text('');
        var decided = c.decision;
        $('#cc-decision-area').prop('hidden', !!decided); $('#cc-decided').prop('hidden', !decided);
        if (decided) { $('#cc-decided').html('<strong>' + esc(decided.decision) + '</strong> by ' + esc(decided.decided_by) + ' · ' + esc(decided.decided_at) + (decided.note ? '<br><em>' + esc(decided.note) + '</em>' : '')); }
        weightResult();
        if (decided) {
            var flagged = decided.decision === 'FLAGGED';
            $('#cc-decision-status').removeClass('cc-pass cc-fail').addClass(flagged ? 'cc-fail' : 'cc-pass').text(flagged ? 'Security hold' : 'Dispatch cleared');
            $('#cc-decided').toggleClass('cc-decided-flagged', flagged);
        }
        $('#cc-empty').prop('hidden', true); $('#cc-work-order,#cc-loaded').prop('hidden', false);
    }

    $('#cc-lookup-form').on('submit', function (event) {
        event.preventDefault();
        var value = $code.val().trim().toUpperCase();
        if (!value) { $error.text('Enter or scan a carton barcode first.').prop('hidden', false); $code.focus(); return; }
        $error.prop('hidden', true); $code.val(value).closest('.cc-input-group').removeClass('cc-input-error');
        $.getJSON($page.data('lookup'), { code: value }).done(function (response) { render(response.data); })
            .fail(function (xhr) {
                var json = xhr.responseJSON || {}, message = json.message || 'Could not load that carton.', notPacked = json.code === 'ORDER_NOT_FULLY_PACKED';
                $error.toggleClass('cc-warn', notPacked).html(notPacked ? esc(message).replace(/^Packing not finished\./, '<strong>Packing not finished.</strong>') : esc(message)).prop('hidden', false);
                $code.closest('.cc-input-group').addClass('cc-input-error');
            });
    });
    $code.on('input', function () { $error.prop('hidden', true); $(this).closest('.cc-input-group').removeClass('cc-input-error'); this.value = this.value.toUpperCase(); });
    $('#cc-checker-weight').on('input', weightResult);
    $('#cc-checker-weight').on('change', function () {
        var entered = parseFloat($(this).val());
        $(this).removeClass('cc-weight-over');
        if (!carton || isNaN(entered) || entered <= carton.expected_weight_g) { return; }
        $(this).addClass('cc-weight-over');
        Swal.fire({
            icon: 'warning', title: 'Weight above expected gross',
            html: 'The entered weight (<b>' + fmt(entered) + ' g</b>) is more than the expected gross weight (<b>' + fmt(carton.expected_weight_g) + ' g</b>).<br>It is recommended to flag this carton.',
            confirmButtonText: 'OK', confirmButtonColor: '#00416b'
        });
    });
    $('#cc-label-ok,#cc-seal-ok,#cc-condition-ok').on('change', decisionState);
    $('#cc-note').on('input', function () { $(this).removeClass('cc-note-error'); $('#cc-note-requirement').text('Optional').removeClass('cc-danger'); $('#cc-decision-msg').text(''); });
    $('#cc-new-scan').on('click', reset);
    $(document).on('keydown', function (event) {
        if (event.key === 'F2') { event.preventDefault(); $code.focus().select(); }
        if (event.key === 'F4' && !$('[data-decision="CLEARED"]').prop('disabled')) { event.preventDefault(); $('[data-decision="CLEARED"]').trigger('click'); }
        if (event.key === 'F8' && carton && !$('#cc-decision-area').prop('hidden')) { event.preventDefault(); $('[data-decision="FLAGGED"]').trigger('click'); }
    });

    $('#cc-decision-area').on('click', '[data-decision]', function () {
        var decision = $(this).data('decision'), weight = parseFloat($('#cc-checker-weight').val());
        if (decision === 'FLAGGED' && $('#cc-note').val().trim().length < 3) {
            $('#cc-note').addClass('cc-note-error').focus(); $('#cc-note-requirement').text('Required when flagged').addClass('cc-danger');
            $('#cc-decision-msg').text('Add a short security note before flagging this carton.');
            return;
        }
        var payload = {
            decision: decision, label_matched: $('#cc-label-ok').is(':checked') ? 1 : 0, seal_intact: $('#cc-seal-ok').is(':checked') ? 1 : 0,
            condition_acceptable: $('#cc-condition-ok').is(':checked') ? 1 : 0, checker_weight_g: isNaN(weight) ? null : weight, note: $('#cc-note').val()
        };
        $.ajax({ url: $page.data('decision') + '/' + carton.id + '/decision', method: 'POST', dataType: 'json', data: payload, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
            .done(function () { window.location.reload(); })
            .fail(function (xhr) {
                var json = xhr.responseJSON || {};
                $('#cc-decision-msg').addClass('text-danger').text(json.message || (json.errors ? Object.values(json.errors)[0][0] : 'Could not record the decision.'));
            });
    });
});
</script>
@endpush
