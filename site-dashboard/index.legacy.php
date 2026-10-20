<!DOCTYPE html>
<html>
<?php include('head.php') ?>

<body class="loom-map-view">
    <style>
        #droppable2 {
            border: 1px solid black;
            height: 100vh;
            width: 98%;
            position: fixed;
            position: absolute;
            overflow: hidden;
            z-index: 2;
        }

        #imgdroppable {
            position: absolute;
        }

        footer {
            margin-top: 0px;
            padding-top: 0px;
            border: 3px solid white;
        }

        .modal-header-primary {
            color: #fff;
            padding: 9px 15px;
            border-bottom: 1px solid #eee;
            background-color: #428bca;
            -webkit-border-top-left-radius: 5px;
            -webkit-border-top-right-radius: 5px;
            -moz-border-radius-topleft: 5px;
            -moz-border-radius-topright: 5px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .modal-header-success {
            color: #fff;
            padding: 9px 15px;
            border-bottom: 1px solid #eee;
            background-color: #5cb85c;
            -webkit-border-top-left-radius: 5px;
            -webkit-border-top-right-radius: 5px;
            -moz-border-radius-topleft: 5px;
            -moz-border-radius-topright: 5px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        #side_index {
            background-color: #e20613;
            color: white
        }

        #side_index>img {
            /*cambio icona*/
            content: url("./images/icona_elenco_viste_neg_r.png");
        }

        html {
            height: 100%;
        }
    </style>
    <?php include_once('./cmdd_funct.php') ?>
    <?php include_once('./cmdd_widget.html') ?>
    <script>
        var interval = setInterval(function() {
            updateTelai();
        }, 4000);
    </script>
    <!-- Wrapper -->
    <div id="wrapper">
        <nav id="nave" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <!-- Barra alta -->
            <?php include('header.php') ?>
            <!-- Sidebar Menu Items - These collapse to the responsive navigation menu on small screens -->
            <?php include('sidebar.php') ?>
            <!-- /.navbar-collapse -->
        </nav>

        <!-- Contenuto centrale della pagina -->
        <div id="page-wrapper">
            <div>
                <label style="display: none" class="control-label" for="vista_chooser" style="display: block"><?php uploadW('seleziona_vista') ?></label>
                <div>
                    <select style="display: none" class="form-control input-lg" id="vista_chooser" onchange="setView()">
                        <?php
                        $viste = $db->exec("SELECT DISTINCT vista.Nome AS nome, vista.Immagine AS immagine, vista.IdVista as idvista FROM vista INNER JOIN utenti_in_vista ON vista.IdVista = utenti_in_vista.IdVista AND IdUtente = '" . $_SESSION['login_username'] . "'");
                        if (count($viste) > 0) {
                            foreach ($viste as $vista) {
                                $outp .= '"' . $vettore[$i] . '":"' . $row['Frase'] . '"';
                                echo ('<option data-img="' . $vista['immagine'] . '" value="' . $vista['idvista'] . '">' . $vista['nome'] . '</option>');
                            }
                        } else {
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="container-fluid">

                <div id="buttonRow" class="row">

                    <div class="col-lg-2 loom-view-title">
                        <h4>Tezgâh haritası: <label id="currentView"></label></h4>
                    </div>
                    <!--<div class="col-lg-4 col-md-4 pull-right"></div>-->
                    <div class="col-md-5 col-lg-5 form-group loom-map-controls">
                        <span class="input-group-btn">
                            <button class="btn btn-lg btn-default" onclick="btnZoom(1.25);"><span class="glyphicon glyphicon-zoom-in"></span></button>
                            <button class="btn btn-lg btn-default" onclick="btnZoom(0.8);"><span class="glyphicon glyphicon-zoom-out"></span></button>
                            <button class="btn btn-lg btn-default" onclick="btnMove(1);"><span class="glyphicon glyphicon-arrow-up"></span></button>
                            <button class="btn btn-lg btn-default" onclick="btnMove(2);"><span class="glyphicon glyphicon-arrow-down"></span></button>
                            <button class="btn btn-lg btn-default" onclick="btnMove(3);"><span class="glyphicon glyphicon-arrow-left"></span></button>
                            <button class="btn btn-lg btn-default" onclick="btnMove(4);"><span class="glyphicon glyphicon-arrow-right"></span></button>
                        </span>
                    </div>
                    <div class="col-md-4 col-lg-4 form-group loom-filter-controls">
                        <span class="input-group-btn">
                            <button class="btn btn-lg btn-default" data-toggle="button" onclick="filter(this, 1)"><span class="icon-manuale"></span></button>
                            <button class="btn btn-lg btn-default" data-toggle="button" onclick="filter(this, 2)"><span class="icon-run"></span></button>
                            <button class="btn btn-lg btn-default" data-toggle="button" onclick="filter(this, 3)"><span class="icon-rocca"></span></button>
                            <button class="btn btn-lg btn-default" data-toggle="button" onclick="filter(this, 4)"><span class="icon-trama"></span></button>
                            <button class="btn btn-lg btn-default" data-toggle="button" onclick="filter(this, 5)"><span class="icon-ordito"></span></button>
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div id="droppable2"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- /#page-wrapper -->

    <!-- /#wrapper -->
    <?php include('footer.php') ?>
    <script src="js/vista_display.js"></script>
<script src="js/loom-cards.js?v=20260922-6"></script>
    <script>
        var D = false; //DEBUG
        var arr;
        var idVista;
        <?php if (filter_input(INPUT_GET, 'idVista') != NULL) { ?>
            idVista = <?php echo filter_input(INPUT_GET, 'idVista') ?>;
        <?php
        } else {
        ?>
            idVista = <?php echo $_SESSION['savedVista'] ?>;
        <?php
        }
        ?>

        $(document).ready(function() {
            <?php if (filter_input(INPUT_GET, 'zoomI') != NULL) { ?>
                moveX = parseInt(<?php echo filter_input(INPUT_GET, 'moveX') ?>);
                moveY = parseInt(<?php echo filter_input(INPUT_GET, 'moveY') ?>);
                zoomI = parseInt(<?php echo filter_input(INPUT_GET, 'zoomI') ?>);
            <?php
            } else {
            ?>
                moveX = 0;
                moveY = 0;
                zoomI = 4;
            <?php
            }
            ?>
            zoom = zoomScale[zoomI];

            $('#droppable2').children().each(function() {
                $(this).matrix();
            });

            if (idVista === -1) {
                BootstrapDialog.show({
                    title: "Information",
                    message: languageDictionary['nessuna_vista'],
                    buttons: [{
                        label: "OK",
                        cssClass: 'btn-primary',
                        action: function() {
                            window.location.href = 'events_temporary.php';
                        }
                    }]
                });
            }

            $("#vista_chooser").val(idVista);

            if (typeof idVista !== 'undefined') {
                document.getElementById("currentView").innerHTML = $("#vista_chooser option:selected").html();
                buildView(idVista);
            }
        });

        function buildView(idVista) {
            var vistaImg = $("#vista_chooser option:selected").attr("data-img");
            if (vistaImg && vistaImg.length > 0) {
                var image = document.createElement('img');
                image.id = "imgdroppable";
                document.getElementById("droppable2").appendChild(image);
                $('#' + image.id).attr('data-xpos', '0');
                $('#' + image.id).attr('data-ypos', '0');
                $('#' + image.id).attr('draggable', 'false');
                $('#' + image.id).attr('src', 'plant/' + vistaImg);
            }

            arr = [];
            var xmlhttp = new XMLHttpRequest();
            var url = "./JSONmsg.php";
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    getMsg(xmlhttp.responseText);
                }
            }
            xmlhttp.open("POST", url, true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("type=5033&viewid=" + idVista);

            function getMsg(response) {
                arr = JSON.parse(response);
                blockID = arr.length;
                if (D)
                    alert("Telai da istanziare: " + arr.length);
                for (j = 0; j < arr.length; j++) {
                    index = arr[j].idDiMappa.substring(2, arr[j].idDiMappa.length);
                    var obj = {
                        offset: {
                            left: arr[j].PosX,
                            top: arr[j].PosY
                        }
                    };
                    switch (arr[j].idDiMappa.substring(0, 2)) {
                        case 'bv':
                            addTelaio(index, obj, 4, 'vertical');
                            break;
                        case 'bh':
                            addTelaio(index, obj, 4, 'horizontal');
                            break;
                        case 'sv':
                            addTelaio(index, obj, 3, 'vertical');
                            break;
                        case 'sh':
                            addTelaio(index, obj, 3, 'horizontal');
                            break;
                        case 'sq':
                            addTelaio(index, obj, 2, 'square');
                            break;
                        case 'sm':
                            addTelaio(index, obj, 0, 'mini');
                            break;
                    }


                    //NUOVA ITEMA
                    $("#" + index).personalizza("title", arr[j].idtelaio + " " + arr[j].nome);
                    $("#" + index).attr("data-idtelaio", arr[j].idtelaio); //per indirizzamento a pagina telaio rt

                    if ($("#" + index).personalizza("dim") != 0) {
                        $("#" + index).personalizza("param1", arr[j].dato1, arr[j].dato1s);
                        $("#" + index).personalizza("param2", arr[j].dato2, arr[j].dato2s);
                        document.getElementById("unit1" + index).innerHTML = arr[j].um1;
                        document.getElementById("unit2" + index).innerHTML = arr[j].um2;
                    }

                    if ($("#" + index).personalizza("dim") > 2) {
                        $("#" + index).personalizza("param3", arr[j].dato3, arr[j].dato3s);
                        document.getElementById("unit3" + index).innerHTML = arr[j].um3;
                    }

                    if ($("#" + index).personalizza("dim") == 4) {
                        $("#" + index).personalizza("param4", arr[j].dato4, arr[j].dato4s);
                        document.getElementById("unit4" + index).innerHTML = arr[j].um4;
                    }

                    //alert("Telaio istanziato: " + arr[j].nome + " enable: " + arr[j].enable + " online: " + arr[j].online);
                    if (arr[j].enable == 1 && arr[j].online == 1) {
                        $("#" + index).personalizza("online");
                        $("#" + index).click(function() {
                            doTelaioPost($(this).attr("data-idtelaio"));
                        });
                    } else {
                        $("#" + index).personalizza("offline");
                    }
                }
                $('#droppable2').children().each(function() {
                    $(this).matrix();
                });
                updateTelai();
            }
        }

        function setView() {
            var newIdVista = $("#vista_chooser option:selected").attr("value");
            setViewInSession(newIdVista);
            document.getElementById("droppable").innerHTML = "";
            document.getElementById("currentView").innerHTML = $("#vista_chooser option:selected").html();
            buildView(newIdVista);
        }

        function checkDbSize() {
            var xmlhttp = new XMLHttpRequest();
            var url = "./JSONmsg.php";
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                    getMsg(xmlhttp.responseText);
                }
            };
            xmlhttp.open("POST", url, true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send("type=13");

            function getMsg(response) {
                var arr = JSON.parse(response);
                if (arr[0].DumpIsNeeded === 'True') {
                    BootstrapDialog.alert(languageDictionary['richiedi_dump']);
                }
            }
        }
        <?php
        if ($_SESSION['isAdmin'] && $_SESSION['firstIndex']) {
            $_SESSION['firstIndex'] = 0;
            echo ("checkDbSize();");
        }
        ?>
    </script>

    <!-- MODAL INVIA ARMATURE -->
    <div class="modal fade" id="uploadArmatura" data-id="" tabindex="-1" role="dialog" aria-labelledby="editModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-success">
                    <button type="button" class="close btn-lg" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editTelaioId"><?php uploadW('carica_armatura') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form role="form">
                                <div class="form-group">
                                    <label><?php uploadW('armatura') ?>:</label>
                                    <select class="form-control" id="gestioneArmatureList">
                                        <?php
                                        $armature = $db->exec("SELECT Nome, Path FROM gestione_armature ORDER BY Nome");
                                        foreach ($armature as $armatura) {
                                            echo ('<option id="' . $armatura["Path"] . '" value="' . $armatura["Nome"] . '">' . $armatura["Nome"] . '</option>');
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div><label class="checkbox-inline"><input type="checkbox" value="" onclick="hideDescrizioneComando('checkbox-group-arm', 'invia_direttamente_in_macch_checkbox-arm')" id="invia_direttamente_in_macch_checkbox-arm"><?php uploadW('esegui_dopo_caricamento') ?></label><br><br>
                                </div>
                                <div class="form-group" id="checkbox-group-arm" style="display:none">
                                    <fieldset>
                                        <input id="armatura1radiobutton" type="radio" name="armatura1o2" value="0" checked /><?php uploadW('armatura') ?> 1<br />
                                        <input id="armatura2radiobutton" type="radio" name="armatura1o2" value="1" /><?php uploadW('armatura') ?> 2
                                    </fieldset>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-lg" onclick="sendArmatura()"><?php uploadW('invio') ?></button>
                    <button id="modal_close_button" type="button" class="btn btn-default btn-lg" data-dismiss="modal"><?php uploadW('chiudi') ?></button>
                </div>
            </div>
        </div>
        <script>
            function sendArmatura() {
                var selectedArmatureOptions = $('#gestioneArmatureList option:selected');
                var xmlhttp = new XMLHttpRequest();
                var url = "./JSONmsg.php";
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                        getMsg(xmlhttp.responseText);
                    }
                };
                xmlhttp.open("POST", url, true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                var cod_op = 101;
                if (document.getElementById("invia_direttamente_in_macch_checkbox-arm").checked) {
                    cod_op += 200;
                    if (document.getElementById("armatura2radiobutton").checked) {
                        cod_op += 3;
                    }
                }
                xmlhttp.send("type=204&parametri=" + selectedArmatureOptions[0].value + "&descrComando=" + 0 +
                    "&codiceoperazione=" + cod_op + "&telaio=" + $('#loomOperations').attr('data-telaio'));

                function getMsg(response) {
                    BootstrapDialog.alert("<?php uploadW('operazione_in_coda') ?>");
                    $("#uploadArmatura").modal('hide');
                }
            }
        </script>
    </div>

    <!-- MODAL INVIA DISEGNI -->
    <div class="modal fade" id="uploadDisegno" data-id="" tabindex="-1" role="dialog" aria-labelledby="editModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-success">
                    <button type="button" class="close btn-lg" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editTelaioId"><?php uploadW('carica_disegno') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form role="form">
                                <div class="form-group">
                                    <label><?php uploadW('disegno') ?>:</label>
                                    <select class="form-control" id="gestioneDisegniList">
                                        <?php
                                        $disegni = $db->exec("SELECT Nome, Path FROM gestione_disegni ORDER BY Nome");
                                        foreach ($disegni as $disegno) {
                                            echo ('<option id="' . $disegno["Path"] . '" value="' . $disegno["Nome"] . '">' . $disegno["Nome"] . '</option>');
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div><label class="checkbox-inline"><input type="checkbox" value="" id="invia_direttamente_in_macch_checkbox-dis"><?php uploadW('esegui_dopo_caricamento') ?></label><br><br>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-lg" onclick="sendDisegni()"><?php uploadW('invio') ?></button>
                    <button id="modal_close_button" type="button" class="btn btn-default btn-lg" data-dismiss="modal"><?php uploadW('chiudi') ?></button>
                </div>
            </div>
        </div>
        <script>
            function sendDisegni() {
                var selectedDisegniOptions = $('#gestioneDisegniList option:selected');
                var xmlhttp = new XMLHttpRequest();
                var url = "./JSONmsg.php";
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                        getMsg(xmlhttp.responseText);
                    }
                };
                var cod_op = 102;
                if (document.getElementById("invia_direttamente_in_macch_checkbox-dis").checked) {
                    cod_op += 200;
                }
                xmlhttp.open("POST", url, true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.send("type=204&parametri=" + selectedDisegniOptions[0].value + "&descrComando=" + 0 +
                    "&codiceoperazione=" + cod_op + "&telaio=" + $('#loomOperations').attr('data-telaio'));

                function getMsg(response) {
                    BootstrapDialog.alert("<?php uploadW('operazione_in_coda') ?>");
                    $("#uploadDisegno").modal('hide');
                }
            }
        </script>
    </div>

    <script>
        //var dropdowndescrizionecomandohtmlmessage = '';

        function selectallcheckbox(value, id_check) {
            for (var i = 1; i < 15; i++) {
                document.getElementById(id_check + i).checked = value;
            }
        }

        //var dropdowninviainmacchinahtmlmessage = '';

        function getDescrizioneComandoFromCheckbox(id_check) {
            var result = 0;
            for (var i = 1; i < 14; i++) {
                if (document.getElementById(id_check + i).checked) {
                    result += Math.pow(2, (parseInt(document.getElementById(id_check + i).value) - 1));
                }
            }
            if (document.getElementById(id_check + "14").checked) {
                result += Math.pow(2, 14);
                result += Math.pow(2, 15);
                result += Math.pow(2, 16);
            }
            return result;
        }

        function hideDescrizioneComando(id_checkgrouptohide, id_checkcaller) {
            if (document.getElementById(id_checkcaller).checked == false) {
                document.getElementById(id_checkgrouptohide).style.display = "none";
            } else {
                document.getElementById(id_checkgrouptohide).style.display = "";
            }
        }
    </script>

    <!-- MODAL INVIA PARAMETRI -->
    <div class="modal fade" id="uploadParametri" data-id="" tabindex="-1" role="dialog" aria-labelledby="editModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-success">
                    <button type="button" class="close btn-lg" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editTelaioId"><?php uploadW('carica_parametri') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form role="form">
                                <div class="form-group">
                                    <label><?php uploadW('parametri_telaio') ?>:</label>
                                    <select class="form-control" id="gestioneParametriList">
                                        <?php
                                        $parametri = $db->exec("SELECT Nome, Path FROM gestione_parametri ORDER BY Nome");
                                        foreach ($parametri as $parametro) {
                                            echo ('<option id="' . $parametro["Path"] . '" value="' . $parametro["Nome"] . '">' . $parametro["Nome"] . '</option>');
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div><label class="checkbox-inline"><input onclick="hideDescrizioneComando('checkbox-group-param', 'invia_direttamente_in_macch_checkbox_par')" type="checkbox" value="" id="invia_direttamente_in_macch_checkbox_par"><?php uploadW('esegui_dopo_caricamento') ?></label>
                                </div><br />
                                <div class="form-group" id="checkbox-group-param" style="display:none">
                                    <label class="checkbox-inline"><input type="checkbox" value="1" id="descr_opt_par1"><?php uploadW('armatura') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="2" id="descr_opt_par2"><?php uploadW('colore') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="3" id="descr_opt_par3"><?php uploadW('cambio_turno_automatico') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="4" id="descr_opt_par4"><?php uploadW('parametri_ewc_etd') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="5" id="descr_opt_par5"><?php uploadW('parametri_tessili') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="6" id="descr_opt_par6"><?php uploadW('parametri_macchina') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="7" id="descr_opt_par7"><?php uploadW('cambio_subbio') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="8" id="descr_opt_par8"><?php uploadW('parametri_motore') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="9" id="descr_opt_par9"><?php uploadW('trasporto_trama') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="10" id="descr_opt_par10"><?php uploadW('manutenzione') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="11" id="descr_opt_par11"><?php uploadW('parametri_splitz') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="12" id="descr_opt_par12"><?php uploadW('parametri_taglio') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="13" id="descr_opt_par13"><?php uploadW('parametri_legacimosse') ?></label><br><label class="checkbox-inline"><input type="checkbox" value="14" id="descr_opt_par14"><?php uploadW('restanti_params') ?></label><br><br><button type="button" class="btn btn-default" onclick="selectallcheckbox(true, 'descr_opt_par')"><?php uploadW('seleziona_tutti') ?></button>
                                    <button type="button" class="btn btn-default" onclick="selectallcheckbox(false, 'descr_opt_par')"><?php uploadW('deseleziona_tutti') ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-lg" onclick="sendParametri()"><?php uploadW('invio') ?></button>
                    <button id="modal_close_button" type="button" class="btn btn-default btn-lg" data-dismiss="modal"><?php uploadW('chiudi') ?></button>
                </div>
            </div>
        </div>
        <script>
            function sendParametri() {
                var selectedParametriOptions = $('#gestioneParametriList option:selected');
                var xmlhttp = new XMLHttpRequest();
                var url = "./JSONmsg.php";
                var cod_op = 103;
                if (document.getElementById("invia_direttamente_in_macch_checkbox_par").checked) {
                    cod_op += 200;
                }
                xmlhttp.onreadystatechange = function() {
                    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                        getMsg(xmlhttp.responseText);
                    }
                };
                xmlhttp.open("POST", url, true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.send("type=204&parametri=" + selectedParametriOptions[0].value + "&descrComando=" +
                    getDescrizioneComandoFromCheckbox("descr_opt_par") + "&codiceoperazione=" + cod_op + "&telaio=" + $(
                        '#loomOperations').attr('data-telaio'));

                function getMsg(response) {
                    BootstrapDialog.alert("<?php uploadW('operazione_in_coda') ?>");
                    $("#uploadParametri").modal('hide');
                }
            }
        </script>
    </div>

    <div id="loomOperations" class="panel panel-default" style="display:none;z-index:11;position:absolute;">
        <div class="panel-heading"><?php uploadW('attenzione') ?></div>
        <div class="panel-body">
            <?php uploadW('nessuna_operazione_disponibile') ?>
        </div>
    </div>
</body>

</html>
