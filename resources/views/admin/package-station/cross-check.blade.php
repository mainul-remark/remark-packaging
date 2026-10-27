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

<div class="container-fluid cc-shell">
    <section class="cc-panel cc-session" aria-label="Current security session">
        <div class="cc-session-title"><span class="cc-shield"><i class="mdi mdi-shield-outline"></i></span><div><small>Current security session</small><strong>Outbound dispatch verification</strong></div></div>
        <div class="cc-metrics">
            <div><small>Checked</small><strong id="cc-checked">0</strong></div>
            <div><small>Cleared</small><strong class="cc-success">0</strong></div>
            <div><small>Flagged</small><strong class="cc-danger">0</strong></div>
            <div><small>Average variance</small><strong>—</strong></div>
        </div>
    </section>

    <div class="cc-grid">
        <aside>
            <section class="cc-panel cc-scanner-card">
                <header><span><b>01</b> Carton lookup</span><span class="cc-ready"><i></i> Scanner ready</span></header>
                <div class="cc-scanner-intro"><span class="cc-barcode-icon"><i class="mdi mdi-barcode-scan"></i></span><div><h2>Scan carton label</h2><p>Use the handheld scanner or enter the carton code manually.</p></div></div>
                <form id="cc-lookup-form" novalidate>
                    <label for="cc-carton-code">Carton barcode</label>
                    <div class="cc-input-group"><i class="mdi mdi-barcode"></i><input id="cc-carton-code" type="text" placeholder="e.g. CTN-260101-0048" autocomplete="off"><button type="submit"><i class="mdi mdi-magnify"></i> Load</button></div>
                    <p class="cc-shortcuts">Press <kbd>Enter</kbd> to search · <kbd>F2</kbd> to focus</p>
                    <p id="cc-error" class="cc-error" role="alert" hidden>Enter or scan a carton barcode first.</p>
                </form>
            </section>

            <section id="cc-work-order" class="cc-panel cc-work-order" hidden>
                <header><span><b>02</b> Work order</span><em>IN PROGRESS</em></header>
                <div class="cc-wo-title"><span>WO</span><div><small>Work order</small><strong>DEMO-SO-1002-WO01</strong><p>DEMO-INV-1002 / Agora Retail Chittagong</p></div></div>
                <dl><div><dt>Sales order</dt><dd>DEMO-SO-1002</dd></div><div><dt>Destination</dt><dd>Chattogram Depot</dd></div><div><dt>Warehouse</dt><dd>Dhaka Main Warehouse</dd></div><div><dt>Packed by</dt><dd>Packaging Operator</dd></div></dl>
            </section>
        </aside>

        <main id="cc-empty" class="cc-panel cc-empty-state">
            <span><i class="mdi mdi-package-variant-closed"></i></span>
            <h2>No carton loaded</h2>
            <p>Scan or enter a carton label barcode to load its work order, contents and weight check.</p>
        </main>

        <main id="cc-loaded" class="cc-panel cc-loaded" hidden>
            <header class="cc-loaded-header"><span class="cc-box-icon"><i class="mdi mdi-package-variant-closed"></i></span><div><small>Carton under review</small><h2 id="cc-loaded-code">CTN-260101-0048</h2><p>Sorted carton · 15 units</p></div><button id="cc-new-scan" type="button"><i class="mdi mdi-refresh"></i> New scan</button></header>
            <section class="cc-loaded-section">
                <div class="cc-section-title"><span>01</span><div><small>Carton contents</small><h3>Products in carton</h3></div><em>IN PROGRESS</em></div>
                <div class="table-responsive"><table class="table cc-table mb-0"><thead><tr><th>Product</th><th>Product barcode</th><th class="text-right">Quantity</th><th class="text-right">Unit weight</th><th class="text-right">Line weight</th></tr></thead><tbody><tr><td><strong>RMK-MO-050</strong><small>Remark Daily Moisturiser 50g</small></td><td>DEMO00000003</td><td class="text-right">15</td><td class="text-right">60 g</td><td class="text-right font-weight-bold">900 g</td></tr></tbody><tfoot><tr><td colspan="2"><strong>Recorded carton contents</strong></td><td class="text-right"><strong>15 units</strong></td><td></td><td class="text-right"><strong>900 g</strong></td></tr></tfoot></table></div>
            </section>
            <section class="cc-loaded-section cc-verification">
                <div class="cc-section-title"><span>02</span><div><small>Physical verification</small><h3>Carton and weight</h3></div><em class="cc-pass"><i class="mdi mdi-check-circle-outline"></i> Within tolerance</em></div>
                <div class="row p-3"><div class="col-md-6"><div class="cc-detail-grid"><div><small>Carton type</small><strong>Standard carton</strong></div><div><small>Security seal</small><strong>FS-10048</strong></div><div><small>Product units</small><strong>15 units</strong></div><div><small>Expected gross</small><strong>1,140 g</strong></div></div></div><div class="col-md-6"><div class="cc-weight-check"><span><i class="mdi mdi-scale-balance"></i></span><div><small>Security re-weigh</small><strong>Recorded weight: 1,140 g</strong></div><label>Checker weight <span><input type="number" value="1140"><b>g</b></span></label></div></div></div>
            </section>
        </main>
    </div>
</div>
@endsection

@push('styles')
<style>
.cc-shell{--navy:#00416b;--ink:#17243a;--muted:#667085;--line:#d7e0e8;--soft:#f8fafb;--green:#027a48;--red:#b42318;padding-top:16px;padding-bottom:40px;color:var(--ink);font-family:"Poppins","Segoe UI",sans-serif;font-size:12px}.page-wrapper,.page-breadcrumb{background:#f8f5f2}.page-breadcrumb{padding-top:14px;padding-bottom:12px}.page-breadcrumb .page-title{color:#10213a;font-family:"Poppins","Segoe UI",sans-serif;font-size:20px;font-weight:700;text-transform:none}.cc-page-subtitle{color:#667085;font-size:12px}.page-breadcrumb .breadcrumb{padding:0;background:transparent;font-size:12px}.cc-panel{border:1px solid var(--line);border-radius:17px;background:#fff;box-shadow:0 2px 4px rgba(16,24,40,.05)}.cc-session{min-height:80px;margin-bottom:16px;padding:16px 20px;display:flex;align-items:center;gap:25px}.cc-session-title{display:flex;align-items:center;gap:13px;min-width:355px}.cc-shield{width:44px;height:44px;display:grid;place-items:center;border-radius:10px;color:#fff;background:var(--navy);font-size:21px}.cc-session-title small,.cc-session-title strong{display:block}.cc-session-title small{margin-bottom:3px;color:#98a2b3;font-size:9px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}.cc-session-title strong{color:#252d3a;font-size:14px}.cc-metrics{margin-left:auto;display:grid;grid-template-columns:repeat(4,minmax(115px,1fr))}.cc-metrics>div{min-height:46px;padding:2px 16px;border-left:1px solid var(--line)}.cc-metrics small,.cc-metrics strong{display:block}.cc-metrics small{color:#98a2b3;font-size:9px;font-weight:600;text-transform:uppercase}.cc-metrics strong{margin-top:2px;color:#273142;font-size:18px}.cc-metrics .cc-success{color:var(--green)}.cc-metrics .cc-danger{color:var(--red)}.cc-grid{display:grid;grid-template-columns:minmax(290px,4fr) minmax(520px,8fr);gap:16px;align-items:start}.cc-grid>aside{display:grid;gap:16px}.cc-scanner-card{overflow:hidden}.cc-scanner-card>header,.cc-work-order>header{height:48px;padding:0 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);color:var(--navy);font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.cc-scanner-card header b,.cc-work-order header b{margin-right:8px;color:#98a2b3}.cc-ready{display:flex;align-items:center;gap:6px;color:#078845}.cc-ready i{width:6px;height:6px;border-radius:50%;background:#12b76a}.cc-scanner-intro{padding:25px 20px 18px;display:flex;align-items:center;gap:16px}.cc-barcode-icon,.cc-empty-state>span{width:56px;height:56px;display:grid;flex:0 0 auto;place-items:center;border-radius:12px;color:var(--navy);background:#e8f0f6;font-size:27px}.cc-scanner-intro h2,.cc-empty-state h2,.cc-loaded-header h2,.cc-section-title h3{margin:0;color:var(--navy);font-weight:700}.cc-scanner-intro h2{font-size:16px}.cc-scanner-intro p{margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.5}.cc-scanner-card form{padding:0 20px 20px}.cc-scanner-card form>label{margin-bottom:7px;color:#344054;font-size:9px;font-weight:800;text-transform:uppercase}.cc-input-group{height:52px;display:flex;align-items:center;overflow:hidden;border:2px solid #cbd5df;border-radius:11px;background:#fff;transition:.15s}.cc-input-group:focus-within{border-color:var(--navy);box-shadow:0 0 0 4px rgba(0,65,107,.1)}.cc-input-group>i{margin-left:13px;color:#98a2b3;font-size:20px}.cc-input-group input{height:100%;min-width:0;flex:1;padding:0 12px;border:0;outline:0;color:var(--navy);font-size:12px;font-weight:600}.cc-input-group input::placeholder{color:#98a2b3;font-weight:400}.cc-input-group button{height:100%;padding:0 20px;border:0;color:#fff;background:var(--navy);font-size:11px;font-weight:700;cursor:pointer}.cc-input-group button i{margin-right:5px;font-size:16px}.cc-shortcuts{margin:9px 0 0;color:var(--muted);font-size:9px}.cc-shortcuts kbd{padding:2px 5px;border:1px solid var(--line);border-radius:4px;color:#667085;background:#fafafa;box-shadow:none;font-family:inherit;font-size:8px}.cc-error{margin:10px 0 0;padding:8px 10px;border:1px solid #fecdca;border-radius:7px;color:var(--red);background:#fef3f2;font-size:10px}.cc-empty-state{min-height:322px;padding:30px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;border-style:dashed;text-align:center}.cc-empty-state h2{font-size:15px}.cc-empty-state p{max-width:390px;margin:0;color:var(--muted);font-size:11px}.cc-work-order{overflow:hidden}.cc-work-order header em,.cc-section-title em{padding:5px 8px;border-radius:99px;color:#b54708;background:#fffaeb;font-size:8px;font-style:normal;font-weight:800}.cc-wo-title{padding:15px 20px;display:flex;align-items:center;gap:10px}.cc-wo-title>span{width:39px;height:39px;display:grid;place-items:center;border-radius:8px;color:var(--navy);background:#e8f0f6;font-weight:800}.cc-wo-title small,.cc-wo-title strong,.cc-wo-title p{display:block;margin:0}.cc-wo-title small,.cc-work-order dt{color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-wo-title strong{color:var(--navy);font-size:11px}.cc-wo-title p{color:var(--muted);font-size:8px}.cc-work-order dl{margin:0;padding:13px 20px;display:grid;grid-template-columns:1fr 1fr;gap:12px;border-top:1px solid #eef1f4}.cc-work-order dd{margin:2px 0 0;color:#344054;font-size:9px;font-weight:600}.cc-loaded{overflow:hidden}.cc-loaded[hidden],.cc-work-order[hidden],.cc-empty-state[hidden]{display:none}.cc-loaded-header{padding:14px 20px;display:flex;align-items:center;gap:12px;color:#fff;background:var(--navy)}.cc-box-icon{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.2);border-radius:9px;background:rgba(255,255,255,.1);font-size:20px}.cc-loaded-header small{color:rgba(255,255,255,.7);font-size:8px;text-transform:uppercase}.cc-loaded-header h2{color:#fff;font-size:17px}.cc-loaded-header p{margin:1px 0 0;color:rgba(255,255,255,.7);font-size:9px}.cc-loaded-header button{margin-left:auto;padding:8px 11px;border:1px solid rgba(255,255,255,.3);border-radius:8px;color:#fff;background:transparent;font-size:10px;font-weight:700}.cc-loaded-section+.cc-loaded-section{border-top:1px solid var(--line)}.cc-section-title{min-height:63px;padding:10px 20px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)}.cc-section-title>span{width:34px;height:34px;display:grid;place-items:center;border-radius:8px;color:var(--navy);background:#e8f0f6;font-size:10px;font-weight:800}.cc-section-title small{display:block;color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-section-title h3{font-size:14px}.cc-section-title em{margin-left:auto}.cc-section-title .cc-pass{color:var(--green);background:#ecfdf3}.cc-table{font-size:10px}.cc-table th{padding:9px 16px;border:0;color:#98a2b3;background:#fcfcfd;font-size:8px;text-transform:uppercase}.cc-table td{padding:11px 16px;vertical-align:middle}.cc-table td small{display:block;color:var(--muted);font-size:8px}.cc-table tfoot{background:#fcfcfd}.cc-detail-grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--line);border-radius:9px;overflow:hidden}.cc-detail-grid>div{padding:11px;border-right:1px solid #edf0f2;border-bottom:1px solid #edf0f2}.cc-detail-grid>div:nth-child(2n){border-right:0}.cc-detail-grid>div:nth-last-child(-n+2){border-bottom:0}.cc-detail-grid small,.cc-weight-check small{display:block;color:#98a2b3;font-size:8px;text-transform:uppercase}.cc-detail-grid strong{color:var(--navy);font-size:10px}.cc-weight-check{padding:14px;display:grid;grid-template-columns:36px 1fr;gap:9px;border:1px solid #a6f4c5;border-radius:10px;background:#f6fef9}.cc-weight-check>span{width:36px;height:36px;display:grid;place-items:center;border-radius:8px;color:var(--green);background:#ecfdf3;font-size:18px}.cc-weight-check strong{font-size:10px}.cc-weight-check label{grid-column:1/-1;margin:5px 0 0;color:#667085;font-size:8px;text-transform:uppercase}.cc-weight-check label>span{display:flex;align-items:center;margin-top:4px}.cc-weight-check input{width:100%;height:35px;padding:6px 30px 6px 9px;border:1px solid #a6f4c5;border-radius:7px;color:var(--navy);background:#fff;font-size:14px;font-weight:700}.cc-weight-check label b{margin-left:-24px;color:#667085;font-size:9px}
@media(max-width:1100px){.cc-session{align-items:flex-start;flex-direction:column}.cc-session-title{min-width:0}.cc-metrics{width:100%;margin-left:0}.cc-metrics>div:first-child{border-left:0}.cc-grid{grid-template-columns:minmax(280px,4fr) minmax(430px,8fr)}}@media(max-width:850px){.cc-grid{grid-template-columns:1fr}.cc-empty-state{min-height:260px}.cc-grid>aside{order:1}.cc-grid>main{order:2}}@media(max-width:575px){.cc-shell{padding-inline:10px}.cc-session{padding:15px}.cc-metrics{grid-template-columns:1fr 1fr;row-gap:10px}.cc-metrics>div:nth-child(3){border-left:0}.cc-input-group button{padding:0 14px}.cc-scanner-intro{align-items:flex-start}.cc-empty-state{min-height:230px}.cc-loaded-header{align-items:flex-start;flex-wrap:wrap}.cc-loaded-header button{margin-left:0}.cc-section-title{padding-inline:14px}.cc-detail-grid{margin-bottom:12px}}
</style>
@endpush

@push('scripts')
<script>
$(function(){
    var $code=$('#cc-carton-code'),$error=$('#cc-error');
    function resetStation(){$code.val('').focus();$error.prop('hidden',true);$('#cc-work-order,#cc-loaded').prop('hidden',true);$('#cc-empty').prop('hidden',false)}
    $('#cc-lookup-form').on('submit',function(event){event.preventDefault();var code=$code.val().trim().toUpperCase();if(!code){$error.prop('hidden',false);$code.focus();return}$error.prop('hidden',true);$code.val(code);$('#cc-loaded-code').text(code);$('#cc-empty').prop('hidden',true);$('#cc-work-order,#cc-loaded').prop('hidden',false);$('#cc-checked').text('1')});
    $code.on('input',function(){$error.prop('hidden',true);this.value=this.value.toUpperCase()});
    $('#cc-new-scan').on('click',resetStation);
    $(document).on('keydown',function(event){if(event.key==='F2'){event.preventDefault();$code.focus().select()}});
});
</script>
@endpush
