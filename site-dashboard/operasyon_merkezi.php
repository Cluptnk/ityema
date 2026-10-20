<!DOCTYPE html>
<html>
<?php include('head.php') ?>
<body>
<style>
    #side_operasyon { background-color: #e20613; color: #fff; }
    #side_operasyon > i { color: #fff; }
    .metric-panel { min-height: 118px; color: #fff; border: 0; }
    .metric-panel .metric { font-size: 34px; font-weight: bold; }
    .metric-panel .caption { font-size: 14px; opacity: .9; }
    .metric-green { background: #298f37; } .metric-blue { background: #2471a3; }
    .metric-orange { background: #dd8a16; } .metric-red { background: #b13d3d; }
    .status { display: inline-block; min-width: 98px; padding: 5px 8px; border-radius: 3px; font-weight: bold; color: #fff; text-align: center; }
    .status-running { background: #298f37; } .status-stopped { background: #777; }
    .status-stale { background: #dd8a16; } .status-offline { background: #b13d3d; }
    .alert-critical { border-left: 5px solid #b13d3d; } .alert-warning { border-left: 5px solid #dd8a16; }
    .alert-event-message { display: block; margin-bottom: 5px; }
    .alert-event-meta { color: #63707b; font-size: 12px; }
    .alert-event-meta .label { margin-right: 7px; }
    .alert-event-loom { display: block; margin-top: 6px; color: #283a4a; }
    #updatedAt { color: #777; font-size: 12px; }
    .loom-sort { width: 100%; padding: 0; color: inherit; background: transparent; border: 0; font: inherit; font-weight: inherit; text-align: left; white-space: nowrap; }
    .loom-sort:hover, .loom-sort:focus { color: #2b6df4; outline: 0; }
    .sort-mark { display: inline-block; width: 12px; margin-left: 3px; color: #2b6df4; font-size: 10px; }
</style>
<div id="wrapper">
    <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <?php include('header.php') ?>
        <?php include('sidebar.php') ?>
    </nav>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header"><i class="fa fa-tachometer"></i> Operasyon Merkezi <small>Canlı tezgâh özeti</small></h1>
                <p id="updatedAt">Veriler yükleniyor...</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3"><div class="panel metric-panel metric-green"><div class="panel-body"><div class="metric" id="runningCount">-</div><div class="caption">Çalışan tezgâh</div></div></div></div>
            <div class="col-md-3"><div class="panel metric-panel metric-blue"><div class="panel-body"><div class="metric" id="totalRpm">-</div><div class="caption">Toplam RPM</div></div></div></div>
            <div class="col-md-3"><div class="panel metric-panel metric-orange"><div class="panel-body"><div class="metric" id="offlineCount">-</div><div class="caption">Çevrimdışı / veri yok</div></div></div></div>
            <div class="col-md-3"><div class="panel metric-panel metric-red"><div class="panel-body"><div class="metric" id="criticalAlertCount">-</div><div class="caption">Aktif uyarı</div></div></div></div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="panel panel-danger">
                    <div class="panel-heading"><i class="fa fa-bell"></i> Uyarı Merkezi <span class="badge" id="alertCount">0</span></div>
                    <div class="panel-body" id="alerts"><div class="alert alert-success">Kritik uyarı yok.</div></div>
                </div>
                <div class="panel panel-default"><div class="panel-heading">Ortalama verim</div><div class="panel-body"><strong id="averageEfficiency" style="font-size:30px">-</strong> % <span id="stoppedCount" class="pull-right"></span></div></div>
            </div>
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-industry"></i> Tezgâhlar</div>
                    <div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th><button type="button" class="loom-sort" data-sort="id">ID <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="name">Tezgâh <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="status">Durum <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="rpm">RPM <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="efficiency">Verim <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="ageSeconds">Son veri <span class="sort-mark"></span></button></th><th><button type="button" class="loom-sort" data-sort="warnings">Uyarı <span class="sort-mark"></span></button></th></tr></thead><tbody id="loomRows"></tbody></table></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
(function () {
    function escapeHtml(value) { return String(value == null ? '' : value).replace(/[&<>'"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]; }); }
    function ageText(seconds) { if (seconds === null) return 'Veri yok'; if (seconds < 60) return seconds + ' sn önce'; return Math.floor(seconds / 60) + ' dk önce'; }
    var sortState = { key: 'id', direction: 'asc' };
    var currentLooms = [];
    var statusOrder = { offline: 0, stopped: 1, running: 2 };

    function sortValue(loom, key) {
        if (key === 'status') return statusOrder[loom.status] === undefined ? 99 : statusOrder[loom.status];
        if (key === 'name') return String(loom.name || '');
        if (key === 'ageSeconds') return loom.ageSeconds === null ? Number.MAX_SAFE_INTEGER : Number(loom.ageSeconds);
        return Number(loom[key] || 0);
    }

    function renderRows() {
        var looms = currentLooms.slice().sort(function (left, right) {
            var a = sortValue(left, sortState.key);
            var b = sortValue(right, sortState.key);
            var comparison = typeof a === 'string' ? a.localeCompare(b, 'tr') : a - b;
            return sortState.direction === 'asc' ? comparison : -comparison;
        });
        document.getElementById('loomRows').innerHTML = looms.map(function (loom) { return '<tr><td>' + loom.id + '</td><td>' + escapeHtml(loom.name) + '</td><td><span class="status status-' + loom.status + '">' + loom.statusText + '</span></td><td>' + loom.rpm + '</td><td>' + loom.efficiency + ' %</td><td>' + ageText(loom.ageSeconds) + '</td><td>' + (loom.warnings || '-') + '</td></tr>'; }).join('');
        $('.loom-sort').each(function () {
            var active = $(this).data('sort') === sortState.key;
            $(this).find('.sort-mark').text(active ? (sortState.direction === 'asc' ? '▲' : '▼') : '');
            $(this).attr('aria-sort', active ? (sortState.direction === 'asc' ? 'ascending' : 'descending') : 'none');
        });
    }
    function render(data) {
        document.getElementById('runningCount').textContent = data.summary.running + ' / ' + data.summary.total;
        document.getElementById('totalRpm').textContent = data.summary.totalRpm;
        document.getElementById('offlineCount').textContent = data.summary.offline;
        document.getElementById('criticalAlertCount').textContent = data.summary.alertCount;
        document.getElementById('averageEfficiency').textContent = data.summary.averageEfficiency;
        document.getElementById('stoppedCount').textContent = 'Duran: ' + data.summary.stopped;
        document.getElementById('updatedAt').textContent = 'Son güncelleme: ' + data.generatedAt + ' — 15 saniyede bir yenilenir.';
        document.getElementById('alertCount').textContent = data.alerts.length;
        document.getElementById('alerts').innerHTML = data.alerts.length ? data.alerts.map(function (a) {
            var isAlarm = a.level === 'critical';
            var labelClass = isAlarm ? 'danger' : 'warning';
            var timestamp = a.timestamp ? escapeHtml(a.timestamp) : 'Zaman bilgisi yok';
            return '<div class="alert alert-' + (isAlarm ? 'danger alert-critical' : 'warning alert-warning') + '">' +
                '<strong class="alert-event-message">' + escapeHtml(a.message) + '</strong>' +
                '<span class="alert-event-meta"><span class="label label-' + labelClass + '">' + escapeHtml(a.kind || (isAlarm ? 'Alarm' : 'Uyarı')) + '</span>' + timestamp + '</span>' +
                '<span class="alert-event-loom">' + escapeHtml(a.loom) + '</span></div>';
        }).join('') : '<div class="alert alert-success">Aktif uyarı yok.</div>';
        currentLooms = data.looms || [];
        renderRows();
    }
    $(document).on('click', '.loom-sort', function () {
        var key = $(this).data('sort');
        sortState.direction = sortState.key === key && sortState.direction === 'asc' ? 'desc' : 'asc';
        sortState.key = key;
        renderRows();
    });
    function load() { $.getJSON('dashboard_data.php').done(render).fail(function () { document.getElementById('updatedAt').textContent = 'Veriler alınamadı. Oturumu ve veritabanı bağlantısını kontrol edin.'; }); }
    load(); window.setInterval(load, 15000);
}());
</script>
</body>
</html>
