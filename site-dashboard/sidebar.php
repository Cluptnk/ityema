<?php
$sec = $db->exec("SELECT securityvalue AS value FROM sicurezza;");
$permessi = 0;
foreach ($sec as $s) {
    $permessi = $s['value'];
}

?>
<style>
    .icon_sidebar {
        /*per settare la grandezza delle icone*/
        width: 21px;
    }
</style>

<div id="sidebar" class="collapse navbar-collapse navbar-ex1-collapse bg-img">
    <div id="proviamo">
        <!-- WRAPPO SIDE -->
        <!-- Barra di navigazione laterale -->
        <ul id="customnav" class="nav navbar-nav side-nav">
            <!-- NUOVO MENU -->

            <li>
                <a id="side_modern_map" href="modern_harita.php"><i class="fa fa-th icon_sidebar" style="margin-bottom:5px"></i> Tezgâh Haritası</a>
            </li>
            <li>
                <a id="side_ilca_status" href="ilca_status.php"><i class="fa fa-heartbeat icon_sidebar" style="margin-bottom:5px"></i> Tezgâh Veri Takibi</a>
            </li>
            <li>
                <a id="side_operasyon" href="operasyon_merkezi.php"><i class="fa fa-tachometer icon_sidebar" style="margin-bottom:5px"></i> Operasyon Merkezi</a>
            </li>
            <li>
                <a id="side_reports" href="raporlar.php"><i class="fa fa-pie-chart icon_sidebar" style="margin-bottom:5px"></i> Raporlar</a>
            </li>
            <li>
                <a id="side_rt_data" href="rt_data.php"><img src="./images/icona_elenco_viste.png" class="icon_sidebar" style="margin-bottom:5px"></img> <?php  uploadW('rt_data') ?></a>
            </li>
            <li>
                <a id="side_accmacchine" href="javascript:;" data-toggle="collapse" data-target="#macchine">
                    <img src="./images/icona_accesso_macchine.png" class="icon_sidebar" style="margin-bottom:5px"></img> <?php uploadW('accesso_macchine') ?><i class="fa fa-fw fa-caret-down"></i></a>
                <ul id="macchine" class="collapse">

                    <?php
                    $telai = $db->exec("SELECT DISTINCT telaio.IdTelaio AS IdTelaio, telaio.NomeTelaio as NomeTelaio FROM telaio WHERE telaio.EnableTelaio = 1 ORDER BY telaio.IdTelaio");
                    foreach ($telai as $telaio) {
                        echo ("<li><a href=javascript:doTelaioPost(" . $telaio['IdTelaio'] . ");> " . $telaio['IdTelaio'] . " " . $telaio['NomeTelaio'] . " </a></li>");
                    }
                    ?>
                </ul>
            </li>
            <!-- <li>
                <a href="javascript:;" data-toggle="collapse" data-target="#utilita">
                    <img src="./images/icona_utilita.png" class="icon_sidebar" style=" margin-bottom:5px"></img> <?php //uploadW('utilita') ?><i class="fa fa-fw fa-caret-down"></i></a>
                <ul id="utilita" class="collapse">
                    <li><a id="side_supeventi" href="events_temporary.php"><img src="./images/icona_supervisione_eventi.png" class="icon_sidebar" style="margin-bottom:5px"></img> <?php //uploadW('supervisione_eventi') ?></a></li>
                </ul>
            </li> -->

            <?php

            if ($_SESSION['creazioneDisegni'] == '1' && $permessi >= 2) {
            ?>
                <li>
                    <a id="side_cad" href="disegni.php"><img src="./images/icona_gestione_articoli.png" class="icon_sidebar" style=" margin-bottom:5px"></img> <?php uploadW('editor_articoli') ?></a>
                </li>
            <?php
            }
            ?>

            <?php
            require_once './cmdd_funct.php';
            if ($_SESSION['isAdmin'] == '1') {
                echo (generate_admin_tools());
            }
            ?>
        </ul>
    </div><!-- WRAPPO SIDE -->
    <!-- QUESTE FUNZIONI CON  $_SERVER['REQUEST_URI'] servono per aprire il menu una volta che si è in una pagina di  un sottomenu -->
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'events')) {
    ?>
        <script>
            $(document).ready(function() {
                $('#supervisione').collapse('show');
                $('#eventi').collapse('show');
            });
        </script>
    <?php
    }
    ?>
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'telai')) {
    ?>
        <script>
            $(document).ready(function() {
                $('#macchine').collapse('show');
            });
        </script>
    <?php
    }
    ?>
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'manage') || strpos($_SERVER['REQUEST_URI'], 'view')) {
    ?>
        <script>
            $(document).ready(function() {
                $('#admin').collapse('show');
            });
        </script>
    <?php
    }
    ?>
    <?php
    if (strpos($_SERVER['REQUEST_URI'], 'temporary')) {
    ?>
        <script>
            $(document).ready(function() {
                $('#utilita').collapse('show');
            });
        </script>
    <?php
    }
    if (strpos($_SERVER['REQUEST_URI'], 'task')) {
    ?>
        <script>
            $(document).ready(function() {
                $('#utilita').collapse('show');
            });
        </script>
    <?php
    }
    ?>
</div>
