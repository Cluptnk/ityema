<?php
if (session_status() == PHP_SESSION_NONE) {
    session_name('ITEMA');
    session_start();
}
include('./maps.php');
if (isset($_SESSION['name'])) {
    header("Location: " . $index_link);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <link rel="icon" type="image/svg+xml" href="./images/loom-favicon.svg?v=1" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Perdekor Tezgâh Yönetimi giriş sayfası">
    <meta name="author" content="Perdekor">

    <title>Giriş</title>
    <!-- jQuery -->
    <script src="plugins/jquery/jquery.js"></script>

    <!-- Bootstrap -->
    <link rel="stylesheet" type="text/css" href="plugins/Bootstrap-3.3.5/css/bootstrap.css">

    <style>
        html,
        body {
            background-image: url('./images/sede.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            height: 100%;
        }

        .myrow {
            margin-top: 200px;
        }

        .card {
            padding-top: 18px;
            height: 320px;
            width: 400px;
            background-color: rgba(255, 0, 0, 0.5) !important;
        }
    </style>
    <link href="css/itema-interface.css?v=20260921-3" rel="stylesheet">
</head>

<body class="login-page">
    <div class="container">
        <div class="row myrow">
            <div class="col-md-4 col-md-offset-4 card">

                <!-- Login area -->
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Lütfen giriş yapın</h3>
                    </div>
                    <div class="panel-body">
                        <form name="loginForm" method="post" role="form" action="check.php" onsubmit="onSubmit(event)">
                            <fieldset>
                                <div class="form-group has-feedback">
                                    <input class="form-control" placeholder="ID" id="id" name="id" type="text" autofocus>
                                    <i class="glyphicon glyphicon-user form-control-feedback"></i>
                                </div>
                                <div class="form-group has-feedback">
                                    <input class="form-control" placeholder="Şifre" id="pass" name="pass" type="password" value="">
                                    <i class="glyphicon glyphicon-lock form-control-feedback"></i>
                                    <input class="form-control" type="hidden" id="password" name="password" value="">
                                </div>
                                <button type="submit" class="btn btn-lg btn-primary btn-block"> Giriş yap </button>
                            </fieldset>
                        </form>
                    </div>
                </div>

                <!-- Loading -->
                <div class="row">
                    <div class="col-lg-12">
                        <div id="alert_wait" class="alert alert-info" style="visibility:hidden">
                            <p>Veriler yükleniyor...</p>
                        </div>
                    </div>
                </div>

                <?php if (filter_input(INPUT_POST, 'err') != NULL) { ?>
                    <script>
                        document.getElementById("alert_wait").style.display = "none";
                    </script>
                    <div class="row">
                        <div class="col-lg-12">
                            <div id="alert_login" class="alert alert-danger alert-dismissable">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <!--Il login non è andato a buon fine.-->
                                <?php
                                echo filter_input(INPUT_POST, 'err_str');
                                if (filter_input(INPUT_POST, 'err') == "2") {
                                    //In questo caso qualcuno è già loggato e lo vogliamo estromettere
                                    echo ("<div align='right'>"
                                        . "<button class='btn btn-danger' onclick='kickOut()'>Zorla çıkış</button>"
                                        . "</div>");
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script src="plugins/Bootstrap-3.3.5/js/bootstrap.min.js"></script>
    <script src="js/sha512.js"></script>
    <script>
        $(document).ready(function() {
            localStorage.removeItem("ITEMAlanguage")
        });

        function kickOut() {
            $.ajax({
                url: './check.php',
                type: 'POST',
                data: {
                    type: "kick"
                },
                success: function(data) {
                    if (data == 0) {
                        $('#alert_login').html('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Enter your password').addClass('alert-success').removeClass('alert-danger');
                    } else {
                        $('#alert_login').html('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>...Oops! Something went wrong!');
                    }
                }
            });
        }

        function onSubmit(event) {
            let user = document.querySelector("#id").value;
            if (!user) {
                document.querySelector("#id").focus();
                event.preventDefault();
                return;
            }
            let pw = document.querySelector("#pass").value;
            if (!pw) {
                document.querySelector("#pass").focus();
                event.preventDefault();
                return;
            } else {
                document.getElementById("alert_wait").style.visibility = 'visible';
                document.querySelector("#password").value = CryptoJS.SHA512(pw);
                document.querySelector("#pass").value = "";
            }
        }
    </script>
</body>

</html>
