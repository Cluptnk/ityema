<!DOCTYPE html>
<html>
<?php include('head.php') ?>
<style>
    #side_rt_data {
        background-color: #e20613;
        color: white
    }

    #side_rt_data>img {
        /*cambio icona*/
        content: url("./images/icona_elenco_viste_neg_r.png");
    }

    html {
        height: 100%;
    }
    /* Sütun ayar paneli yerine tüm canlı veriler tek tabloda gösterilir. */
    #accordion, #page-wrapper .container-fluid > .row:first-child { display: none; }
    #page-wrapper .container-fluid { padding-top: 18px; }
</style>
<link rel="stylesheet" type="text/css" href="./css/widget.css">

<body>
    <!-- Wrapper -->
    <div id="wrapper">
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <!-- Barra alta -->
            <?php include('header.php') ?>
            <!-- Sidebar Menu Items - These collapse to the responsive navigation menu on small screens -->
            <?php include('sidebar.php') ?>
            <!-- /.navbar-collapse -->
        </nav>
        <?php include('loading_page.php') ?>
        <!-- Contenuto centrale della pagina -->
        <div id="page-wrapper">
            <div class="container-fluid">
                <!-- Intestazione della pagina centrale -->
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">
                            <?php uploadW('rt_data') ?>
                        </h1>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingOptions">
                                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOptions" aria-expanded="true" aria-controls="collapseOptions">
                                        <?php uploadW('colonne') ?>
                                    </a>
                                </div>
                                <div id="collapseOptions" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOptions">
                                    <div class="panel-body">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <div class="col-sm-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkIdTelaio">
                                                        <label class="form-check-label" for="checkIdTelaio">
                                                            <?php uploadW('IDtelaio') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkStato">
                                                        <label class="form-check-label" for="checkStato">
                                                            <?php uploadW('stato_telaio') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkArticolo">
                                                        <label class="form-check-label" for="checkArticolo">
                                                            <?php uploadW('Articolo', 'ucfirst') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkArmatura1">
                                                        <label class="form-check-label" for="checkArmatura1">
                                                            <?php uploadW('Armatura1') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkArmatura2">
                                                        <label class="form-check-label" for="checkArmatura2">
                                                            <?php uploadW('Armatura2') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkDisegno">
                                                        <label class="form-check-label" for="checkDisegno">
                                                            <?php uploadW('Disegno') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkDensitaTrama">
                                                        <label class="form-check-label" for="checkDensitaTrama">
                                                            <?php uploadW('DensitaTrama') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkTipoProduzione">
                                                        <label class="form-check-label" for="checkTipoProduzione">
                                                            <?php uploadW('TipoProduzione') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkProduzioneDesiderataEffettuata">
                                                        <label class="form-check-label" for="checkProduzioneDesiderataEffettuata">
                                                            <?php uploadW('ProduzioneDesiderataEffettuata') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkProduzioneDesiderataEffettuataPezze">
                                                        <label class="form-check-label" for="checkProduzioneDesiderataEffettuataPezze">
                                                            <?php uploadW('ProduzioneDesiderataEffettuataPezze') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkResa">
                                                        <label class="form-check-label" for="checkResa">
                                                            <?php uploadW('Resa') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkVelocita">
                                                        <label class="form-check-label" for="checkVelocita">
                                                            <?php uploadW('Velocita') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkConsumo">
                                                        <label class="form-check-label" for="checkConsumo">
                                                            <?php uploadW('Consumo') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkLottoOrditoA">
                                                        <label class="form-check-label" for="checkLottoOrditoA">
                                                            <?php uploadW('LottoOrditoA', 'ucfirst') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkLottoOrditoB">
                                                        <label class="form-check-label" for="checkLottoOrditoB">
                                                            <?php uploadW('LottoOrditoB', 'ucfirst') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkLottoTrama">
                                                        <label class="form-check-label" for="checkLottoTrama">
                                                            <?php uploadW('LottoTrama', 'ucfirst') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkTempoMancanteSubbio1">
                                                        <label class="form-check-label" for="checkTempoMancanteSubbio1">
                                                            <?php uploadW('TempoMancanteSubbio1') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriFattiSubbio1">
                                                        <label class="form-check-label" for="checkMetriFattiSubbio1">
                                                            <?php uploadW('MetriFattiSubbio1') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriMancantiSubbio1">
                                                        <label class="form-check-label" for="checkMetriMancantiSubbio1">
                                                            <?php uploadW('MetriMancantiSubbio1') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkTempoMancanteSubbio2">
                                                        <label class="form-check-label" for="checkTempoMancanteSubbio2">
                                                            <?php uploadW('TempoMancanteSubbio2') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriFattiSubbio2">
                                                        <label class="form-check-label" for="checkMetriFattiSubbio2">
                                                            <?php uploadW('MetriFattiSubbio2') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriMancantiSubbio2">
                                                        <label class="form-check-label" for="checkMetriMancantiSubbio2">
                                                            <?php uploadW('MetriMancantiSubbio2') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkTempoMancanteSubbio3">
                                                        <label class="form-check-label" for="checkTempoMancanteSubbio3">
                                                            <?php uploadW('TempoMancanteSubbio3') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriFattiSubbio3">
                                                        <label class="form-check-label" for="checkMetriFattiSubbio3">
                                                            <?php uploadW('MetriFattiSubbio3') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkMetriMancantiSubbio3">
                                                        <label class="form-check-label" for="checkMetriMancantiSubbio3">
                                                            <?php uploadW('MetriMancantiSubbio3') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkWarnings">
                                                        <label class="form-check-label" for="checkWarnings">
                                                            <?php uploadW('Warnings') ?>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkTransponderRFID">
                                                        <label class="form-check-label" for="checkTransponderRFID">
                                                            <?php uploadW('TransponderRFID') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="panel-footer">
                                                <button class="btn btn-primary btn-lg" onclick="saveColumnVisibility('rt_data_table')"><?php uploadW('salva') ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <table id="rt_data_table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th><?php uploadW('IDtelaio') ?></th>
                                    <th><?php uploadW('stato_telaio') ?></th>
                                    <th><?php uploadW('Articolo', 'ucfirst') ?></th>
                                    <th><?php uploadW('Armatura1') ?></th>
                                    <th><?php uploadW('Armatura2') ?></th>
                                    <th><?php uploadW('Disegno') ?></th>
                                    <th><?php uploadW('DensitaTrama') ?></th>
                                    <th><?php uploadW('TipoProduzione') ?></th>
                                    <th><?php uploadW('ProduzioneDesiderataEffettuata') ?></th>
                                    <th><?php uploadW('ProduzioneDesiderataEffettuataPezze') ?></th>
                                    <th><?php uploadW('Resa') ?></th>
                                    <th><?php uploadW('Velocita') ?></th>
                                    <th><?php uploadW('Consumo') ?></th>
                                    <th> <?php uploadW('LottoOrditoA', 'ucfirst') ?></th>
                                    <th><?php uploadW('LottoOrditoB', 'ucfirst') ?></th>
                                    <th> <?php uploadW('LottoTrama', 'ucfirst') ?></th>
                                    <th><?php uploadW('TempoMancanteSubbio1') ?></th>
                                    <th><?php uploadW('MetriFattiSubbio1') ?></th>
                                    <th><?php uploadW('MetriMancantiSubbio1') ?></th>
                                    <!-- MetriTotaliSubbio1 -->
                                    <!-- MetriTotaliSubbio1 - MetriFattiSubbio1 (metri mancanti)-->
                                    <th><?php uploadW('TempoMancanteSubbio2') ?></th>
                                    <th><?php uploadW('MetriFattiSubbio2') ?></th>
                                    <th><?php uploadW('MetriMancantiSubbio2') ?></th>
                                    <!-- MetriTotaliSubbio2 -->
                                    <!-- MetriTotaliSubbio2 - MetriFattiSubbio1 (metri mancanti)-->
                                    <th><?php uploadW('TempoMancanteSubbio3') ?></th>
                                    <th><?php uploadW('MetriFattiSubbio3') ?></th>
                                    <th><?php uploadW('MetriMancantiSubbio3') ?></th>
                                    <!-- MetriTotaliSubbio3 -->
                                    <!-- MetriTotaliSubbio3 - MetriFattiSubbio1 (metri mancanti)-->
                                    <th><?php uploadW('Warnings') ?></th>
                                    <th><?php uploadW('TransponderRFID') ?></th>
                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('footer.php') ?>
    <script type="text/javascript" charset="utf8" src="./js/newTablesFunction.js"></script>
    <script>
        $(document).ready(function() {
            Promise.all([getRtData(), getTableTranslation()]).then(createTable).catch(console.err);
            var interval = setInterval(() => {
                refreshRtData();
            }, 5000);
        });


        function refreshRtData() {
            getRtData().then((data) => {
                $('#rt_data_table').DataTable().clear().rows.add(data).draw()
            });
        }

        function getRtData() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: "./JSONmsg.php",
                    type: "POST",
                    data: {
                        type: "5044"
                    },
                    success: (response) => {
                        resolve(JSON.parse(response))
                    },
                    error: (error) => {
                        reject(error)
                    }
                });
            });
        }

        function createTable([rows, translation]) {
            // Eski kaydedilmiş gizleme tercihlerinden bağımsız olarak tüm
            // bilgiler aynı tabloda görünür.
            let visibility = Array(30).fill(true);
            console.log({
                rows
            })

            let dataTable = $('#rt_data_table').DataTable({
                data: rows,
                retrieve: true, //se settato a true, dopo la prima inizializzazione vanno impostate le api sulle dt
                // responsive: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                dom: 'rt',
                order: [
                    [0, "asc"]
                ],
                language: {
                    emptyTable: translation.sEmptyTable,
                    info: translation.sInfo,
                    infoEmpty: translation.sInfoEmpty,
                    infoFiltered: translation.sInfoFiltered,
                    infoPostFix: translation.sInfoPostFix,
                    thousands: translation.sInfoThousands,
                    lengthMenu: translation.sLengthMenu,
                    loadingRecords: translation.sLoadingRecords,
                    processing: translation.sProcessing,
                    search: translation.sSearch,
                    zeroRecords: translation.sZeroRecords,
                    paginate: {
                        first: translation.oPaginate.sFirst,
                        last: translation.oPaginate.sLast,
                        next: translation.oPaginate.sNext,
                        previous: translation.oPaginate.sPrevious,
                    },
                    aria: {
                        sortAscending: translation.sSortAscending,
                        sortDescending: translation.sDescending,
                    },
                },
                columns: [{
                        data: "IdTelaio",
                        visible: visibility[0]
                    },
                    {
                        data: "StatoProduzione",
                        visible: visibility[1],
                        render: function(data, type, row, meta) {
                            let statusClass = "glyphicon glyphicon-question-sign";
                            var status = parseInt(data) % 256;
                            switch (status) {
                                case 0:
                                    statusClass = "icon-run";
                                    break;
                                case 1:
                                case 15:
                                case 16:
                                    statusClass = "icon-ordito";
                                    break;
                                case 2:
                                case 3:
                                case 4:
                                case 5:
                                case 10:
                                case 11:
                                case 12:
                                case 13:
                                case 14:
                                    statusClass = "icon-trama";
                                    break;
                                case 17:
                                    statusClass = "icon-produzione";
                                    break;
                                case 18:
                                    statusClass = "icon-manuale";
                                    break;
                                case 19:
                                    statusClass = "icon-altro";
                                    break;
                                case 20:
                                    statusClass = "icon-rocca";
                                    break;
                                case 39:
                                    statusClass = "glyphicon glyphicon-sort";
                                    break;
                                default:
                                    statusClass = "glyphicon glyphicon-question-sign";
                                    break;
                            }
                            return `<span class="${statusClass}"></span>`;
                        }
                    },
                    {
                        data: "Articolo",
                        visible: visibility[2]
                    },
                    {
                        data: "Armatura1",
                        visible: visibility[3]
                    },
                    {
                        data: "Armatura2",
                        visible: visibility[4]
                    },
                    {
                        data: "Disegno",
                        visible: visibility[5]
                    },
                    {
                        data: "DensitaTrama",
                        render: function(data, type, row) {
                            return convertiDensitaTrama(data) + " " + languageDictionary[row.SisMisuraCodePhrase];
                        },
                        visible: visibility[6]
                    },
                    {
                        data: "TipoProduzioneKey",
                        visible: visibility[7],
                        render: function(data) {
                            return languageDictionary[data];
                        }
                    },
                    {
                        data: "ProduzioneEffettuata",
                        visible: visibility[8],
                        render: function(data, type, row) {
                            if (row.TipoProduzione !== "1" && row.TipoProduzione !== "6") return "---";
                            return `${(data/100)} / ${(row.ProduzioneDesiderata/100)}`;
                        }
                    },
                    {
                        data: "PezzeProdotte",
                        visible: visibility[9],
                        render: function(data, type, row) {
                            return `${data} / ${row.PezzeProgrammate}`;
                        }
                    },
                    {
                        data: "Resa",
                        render: function(data, type, row, meta) {
                            return (data / 100).toFixed(1) + "%"
                        },
                        visible: visibility[10]
                    },
                    {
                        data: "Velocita",
                        render: function(data) {
                            return data + " RPM";
                        },
                        visible: visibility[11]
                    },
                    {
                        data: "Consumo",
                        visible: visibility[12],
                        render: function(data, type, row, meta) {
                            if (row.TipoModelloTelaio !== 'Aria') return '---'
                            return data
                        }
                    },
                    {
                        data: "LottoOrditoA",
                        visible: visibility[13]
                    },
                    {
                        data: "LottoOrditoB",
                        visible: visibility[14]
                    },
                    {
                        data: "LottoTrama",
                        visible: visibility[15]
                    },
                    {
                        data: "TempoMancanteSubbio1",
                        visible: visibility[16],
                        render: function(data) {
                            return myPrettyTimelength(data)
                        }
                    },
                    {
                        data: "MetriFattiSubbio1",
                        visible: visibility[17],
                        render: function(data) {
                            return data / 100
                        }
                    },
                    {
                        data: "MetriTotaliSubbio1",
                        visible: visibility[18],
                        render: function(data, type, row) {
                            return (data - row.MetriFattiSubbio1) / 100
                        }
                    },
                    {
                        data: "TempoMancanteSubbio2",
                        visible: visibility[19],
                        render: function(data) {
                            return myPrettyTimelength(data)
                        }
                    },
                    {
                        data: "MetriFattiSubbio2",
                        visible: visibility[20],
                        render: function(data) {
                            return data / 100
                        }
                    },
                    {
                        data: "MetriTotaliSubbio2",
                        visible: visibility[21],
                        render: function(data, type, row) {
                            return (data - row.MetriFattiSubbio2) / 100
                        }
                    },
                    {
                        data: "TempoMancanteSubbio3",
                        visible: visibility[22],
                        render: function(data) {
                            return myPrettyTimelength(data)
                        }
                    },
                    {
                        data: "MetriFattiSubbio3",
                        visible: visibility[23],
                        render: function(data) {
                            return data / 100
                        }
                    },
                    {
                        data: "MetriTotaliSubbio3",
                        visible: visibility[24],
                        render: function(data, type, row) {
                            return (data - row.MetriFattiSubbio3) / 100
                        }
                    },
                    {
                        data: "Warnings",
                        visible: visibility[25],
                        sortable: false,
                        render: function(data, type, row, meta) {
                            html = '';
                            if (data & 0x01) {
                                html += "<span class='icon-finerocca rt_warning'></span>";
                            }
                            if (data & 0x02) {
                                html += "<span class='icon-cambiorocca rt_warning'></span>";
                            }
                            if (data & 0x04) {
                                html += "<span class='fa fa-percent rt_warning'></span>";
                            }
                            if (data & 0x08) {
                                html += "<span class='icon-produzione rt_warning'></span>";
                            }
                            if (data & 0x10) {
                                html += "<span class='icon-cambiosubbio rt_warning'></span>";
                            }
                            return html;
                        }
                    },
                    {
                        data: "TransponderRFID",
                        render: function(data) {
                            return isIdentile(data, "?") ? "---" : data
                        },
                        visible: visibility[26]
                    },
                ]
            });
        }
    </script>

</body>

</html>
