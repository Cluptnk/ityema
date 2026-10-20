<!DOCTYPE html>
<html>
<?php include('head.php') ?>

<body>
    <style>
        #side_ilca_status { background-color: #e20613; color: white; }
        #side_ilca_status > i { color: white; }
        .ilca-summary { margin-bottom: 20px; }
        .ilca-summary .panel { min-height: 112px; }
        .ilca-summary .metric { font-size: 30px; font-weight: bold; line-height: 1.1; }
        .status-badge { display: inline-block; min-width: 98px; padding: 5px 9px; border-radius: 3px; color: #fff; font-weight: bold; text-align: center; }
        .status-live { background: #3c9d23; }
        .status-stale { background: #e79b1f; }
        .status-offline { background: #b23b3b; }
        .status-unknown { background: #777; }
        .table > tbody > tr > td { vertical-align: middle; }
    </style>

    <?php
    $looms = $db->exec(
        "SELECT
            telaio.IdTelaio,
            telaio.NomeTelaio,
            telaio.Online,
            dato_rt.Timestamp AS LastUpdate,
            dato_rt.Velocita,
            dato_rt.StatoProduzione,
            CASE
                WHEN dato_rt.Timestamp IS NULL THEN NULL
                ELSE TIMESTAMPDIFF(SECOND, dato_rt.Timestamp, NOW())
            END AS SecondsSinceUpdate
         FROM telaio
         LEFT JOIN dato_rt ON dato_rt.IdTelaio = telaio.IdTelaio
         WHERE telaio.EnableTelaio = 1
         ORDER BY telaio.IdTelaio"
    );

    $live = 0;
    $offline = 0;
    foreach ($looms as $loom) {
        $age = isset($loom['SecondsSinceUpdate']) ? (int) $loom['SecondsSinceUpdate'] : null;
        if ((int) $loom['Online'] !== 1 || $age === null) {
            $offline++;
        } elseif ($age <= 60) {
            $live++;
        } else {
            $offline++;
        }
    }

    function ilca_status_label($loom) {
        $age = isset($loom['SecondsSinceUpdate']) ? (int) $loom['SecondsSinceUpdate'] : null;
        if ((int) $loom['Online'] !== 1 || $age === null) {
            return array('Çevrimdışı', 'status-offline');
        }
        if ($age <= 60) {
            return array('Canlı', 'status-live');
        }
        return array('Çevrimdışı', 'status-offline');
    }

    function ilca_age_text($seconds) {
        if ($seconds === null) {
            return 'Veri yok';
        }
        $seconds = max(0, (int) $seconds);
        if ($seconds < 60) {
            return $seconds . ' sn önce';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . ' dk önce';
        }
        return floor($seconds / 3600) . ' sa önce';
    }
    ?>

    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <?php include('header.php') ?>
            <?php include('sidebar.php') ?>
        </nav>

        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header"><i class="fa fa-heartbeat"></i> Tezgâh Veri Takibi <small>Canlı veri sağlığı</small></h1>
                    </div>
                </div>

                <div class="row ilca-summary">
                    <div class="col-md-4 col-sm-6"><div class="panel panel-primary"><div class="panel-body"><div>Toplam tezgâh</div><div class="metric"><?php echo count($looms); ?></div></div></div></div>
                    <div class="col-md-4 col-sm-6"><div class="panel panel-success"><div class="panel-body"><div>Canlı</div><div class="metric"><?php echo $live; ?></div></div></div></div>
                    <div class="col-md-4 col-sm-6"><div class="panel panel-danger"><div class="panel-body"><div>Çevrimdışı / veri yok</div><div class="metric"><?php echo $offline; ?></div></div></div></div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading"><i class="fa fa-list"></i> Tezgâh bağlantıları <span class="pull-right text-muted">30 saniyede bir yenilenir</span></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead><tr><th>ID</th><th>Tezgâh</th><th>Durum</th><th>Son veri</th><th>Son güncelleme</th><th>Hız</th><th>Üretim durumu</th></tr></thead>
                            <tbody>
                            <?php foreach ($looms as $loom) { list($label, $class) = ilca_status_label($loom); ?>
                                <tr>
                                    <td><?php echo (int) $loom['IdTelaio']; ?></td>
                                    <td><?php echo htmlspecialchars($loom['NomeTelaio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="status-badge <?php echo $class; ?>"><?php echo $label; ?></span></td>
                                    <td><?php echo ilca_age_text(isset($loom['SecondsSinceUpdate']) ? $loom['SecondsSinceUpdate'] : null); ?></td>
                                    <td><?php echo $loom['LastUpdate'] ? htmlspecialchars($loom['LastUpdate'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                    <td><?php echo isset($loom['Velocita']) ? (int) $loom['Velocita'] : '-'; ?></td>
                                    <td><?php echo isset($loom['StatoProduzione']) ? (int) $loom['StatoProduzione'] : '-'; ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>setTimeout(function () { window.location.reload(); }, 30000);</script>
</body>
</html>
