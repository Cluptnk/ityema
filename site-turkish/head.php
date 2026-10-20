<?php
 if (session_status() == PHP_SESSION_NONE) {
    session_name('ITEMA');
    session_start();
}
include('maps.php');
if (!isset($_SESSION['name'])) {
    header("Location: " . $login_link) or exit("redirect failed!");
}
// Geliştirme sürümünde uygulama arayüzü her zaman Türkçe açılır.
$_SESSION['lang'] = 'tr';
?>

<head>

    <link rel="icon" href="./images/favicon.ico" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Main page">
    <meta name="author" content="">

    <title>iMANAGER</title>

    <!-- jQuery -->
    <script type="text/javascript" charset="utf8" src="plugins/jquery/jquery.js"></script>

    <!-- Bootstrap Core -->
    <link rel="stylesheet" type="text/css" href="plugins/Bootstrap-3.3.5/css/bootstrap.css">
    <script src="plugins/Bootstrap-3.3.5/js/bootstrap.min.js"></script>

    <!-- Custom CSS -->
    <link href="css/sb-admin.css" rel="stylesheet">
    <link href="css/mod_grafiche.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="plugins/font-awesome/css/font-awesome.css" rel="stylesheet" type="text/css">
    <link href="plugins/font-fontello/css/fontello.css" rel="stylesheet" type="text/css">

    <!-- Angular UI -->
    <script type="text/javascript" charset="utf8" src="plugins/AngularUI/angular.min.js"></script>
    <script type="text/javascript" charset="utf8" src="plugins/AngularUI/angular-animate.js"></script>
    <script type="text/javascript" charset="utf8" src="plugins/AngularUI/ui-bootstrap-tpls-0.13.3.min.js"></script>

    <!-- ColorPicker -->
    <script type="text/javascript" charset="utf8" src="plugins/colorpicker/bootstrap-colorpicker.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet">

    <!-- funzioni varie -->
    <script type="text/javascript" charset="utf8" src="js/functions.js"></script>
    <script type="text/javascript" charset="utf8" src="js/JSONmsgs_cmdd.js"></script>
    <script type="text/javascript" charset="utf8" src="js/turkish-ui.js"></script>

    <!-- Jquery UI -->
    <script src="plugins/Jquery UI/jquery-ui.js"></script>
    <link rel="stylesheet" href="plugins/Jquery UI/jquery-ui.css">

    <!-- Vis -->
    <script src="plugins/vis/vis.min.js"></script>
    <link href="plugins/vis/vis.min.css" rel="stylesheet" type="text/css" />  

    <!-- DatatimePicker -->
    <script type="text/javascript" charset="utf-8" src="plugins/datapicker/moment.js"></script>
    <script type="text/javascript" charset="utf-8" src="plugins/datapicker/bootstrap-datetimepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/datapicker/bootstrap-datetimepicker.css" rel="stylesheet">

    <!-- Multiselect -->
    <script type="text/javascript" charset="utf-8" src="plugins/MultiselectPlugin2/js/bootstrap-multiselect.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/MultiselectPlugin2/css/bootstrap-multiselect.css" rel="stylesheet">
    <!-- <script type="text/javascript" charset="utf-8" src="plugins/MultiselectPlugin/js/bootstrap-multiselect.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/MultiselectPlugin/css/bootstrap-multiselect.css" rel="stylesheet"> -->

    <!-- DataTables, Buttons -->
    <!-- <link rel="stylesheet" type="text/css" href="plugins/DataTables2/DataTables-1.10.25/css/dataTables.bootstrap.min.css"/>
    <script type="text/javascript" src="plugins/DataTables2/DataTables-1.10.25/js/jquery.datatables.min.js"></script>
    <script type="text/javascript" src="plugins/DataTables2/DataTables-1.10.25/js/dataTables.bootstrap.min.js"></script> -->
    <link rel="stylesheet" type="text/css" href="plugins/DataTables2/datatables.min.css"/>
    <script type="text/javascript" src="plugins/DataTables2/dataTables.js"></script>

    <!-- Bootstrap dialog -->
    <link rel="stylesheet" type="text/css" href="plugins/Bootstrap dialog/bootstrap-dialog.min.css"/>
    <script type="text/javascript" src="plugins/Bootstrap dialog/bootstrap-dialog.min.js"></script>

    <!-- Esportazione dati in Excel -->
    <script type="text/javascript" src="plugins/exportEXCEL/IntelExcel.min.js"></script>
    <script type="text/javascript" src="plugins/exportEXCEL/sheetjs.all.js"></script>

    <!-- ITEMA CSS -->
    <link href="css/mod_grafiche.css" rel="stylesheet">
    <?php
// Kickstart the framework
    $f3 = require('lib/base.php');

    $f3->set('DEBUG', 3);
    if ((float) PCRE_VERSION < 7.9) {
        trigger_error('PCRE version is out of date');
    }

// Load configuration
    $f3->config('config.ini');
//connessione al DB
    include('connectionDB.php');
    $lingua = new DB\SQL\Mapper($db, 'lingua');
    $lang = $_SESSION['lang'];

    function uploadW($code) {
        global $lingua, $lang;
        $lingua->load(array('IdLingua=:ID AND CodePhrase=:code', ":ID" => $lang, ":code" => $code));
        if (!$lingua->dry()) {
            if (func_num_args() === 1 || $lang === 'zh') { //Il cinese NON ha maiuscole/minuscole
                echo $lingua->Frase;
                return;
            }
            if (func_num_args() === 2) {
                $arg = func_get_arg(1);
                if ($arg === "strtolower") {
                    echo mb_strtolower($lingua->Frase,'UTF-8');
                    return;
                }
                if ($arg === "ucfirst") {
                    echo ucfirst(mb_strtolower($lingua->Frase,'UTF-8'));
                    return;
                }
            }
        } else {
            echo $code;
        }
    }
    ?>

</head>
