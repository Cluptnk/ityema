<?php
$max_allowed_time = 15; //massimo tempo concesso ad un admin

// Kickstart the framework
$f3 = require('lib/base.php');
$f3->sync('SESSION');
$f3->set('DEBUG', 3);
if ((float) PCRE_VERSION < 7.9) {
    trigger_error('PCRE version is out of date');
}
// Load configuration
$f3->config('config.ini');

include_once('./maps.php');
include_once('./connectionDB.php');
include_once('./locker_functions.php');

//Qui ricevo i valori passati da login
$id = filter_input(INPUT_POST, 'id');
$password = filter_input(INPUT_POST, 'password');
$type = filter_input(INPUT_POST, 'type');

if ($debugfile) {
    $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
    fwrite($debug_file,  "----------------------------------------------------\n");
    fwrite($debug_file,  "CHECKING" . date("d/m/Y h:i:sa") . "\n");
    fclose($debug_file);
}

if ($type == 'kick') {
    if ($debugfile) {
        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
        fwrite($debug_file,  "Kick off! " . date("d/m/Y h:i:sa") . "\n");
        fclose($debug_file);
    }

    if ($lockfile && file_exists($path . "/lock.ini")) {
        $iniarray = parse_ini_file($path . "/lock.ini");
        $old_session_id = $iniarray['SessionId'];
        $iniarray['SessionId'] = "";
        $iniarray['SessionName'] = "";
        $iniarray['Date'] = date("Y-m-d H:i:s", time());
        write_php_ini($iniarray, $path . "/lock.ini");
        if ($old_session_id != "") {
            session_write_close();
            session_id($old_session_id);
            if (session_status() == PHP_SESSION_NONE) {
                session_name('ITEMA');
                session_start();
            }
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000);
            }
            session_unset();
            session_destroy();
            unset($old_session_id);
        }
    }

    echo 0; //Risposta alla ajax request
    exit;
}

$err_value = "1";
$err_string = "Invalid username or password.";
$user = new DB\SQL\Mapper($db, 'utente');
$user->load(array('IdUtente=:ID AND Password=:pwd', ":ID" => $id, ":pwd" => $password));


if (!$user->dry()) {
    if ($debugfile) {
        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
        fwrite($debug_file,  "User exists\n");
        fclose($debug_file);
    }
    if (session_status() == PHP_SESSION_NONE) {
        session_name('ITEMA');
        session_start();
    }

    if ($user->IsAdmin == 1) {
        if ($debugfile) {
            $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
            fwrite($debug_file,  "User is Admin\n");
            fclose($debug_file);
        }
        if ($lockfile && file_exists($path . "/lock.ini")) {
            $iniarray = parse_ini_file($path . "/lock.ini");
            if ($iniarray['SessionName'] === session_name() && $iniarray['SessionId'] !== session_id()) {
                $login_time = new DateTime(date($iniarray['Date']));
                $now = new DateTime(date("Y-m-d H:i:s", time()));
                $interval = $now->diff($login_time);
                $minutes = $interval->days * 24 * 60;
                $minutes += $interval->h * 60;
                $minutes += $interval->i;
                if ($minutes >= $max_allowed_time) {
                    $old_session_id = $iniarray['SessionId'];
                    if ($debugfile) {
                        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
                        fwrite($debug_file,  "Tempo scaduto! Revoca autorizzazione->" . $iniarray['SessionId'] . " " . date("d/m/Y h:i:sa") . "\n");
                        fclose($debug_file);
                    }
                } else {
                    if ($debugfile) {
                        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
                        fwrite($debug_file,  "Già dentro!->" . $iniarray['SessionId'] . " " . date("d/m/Y h:i:sa") . "\n");
                        fclose($debug_file);
                    }

                    $err_value = "2";
                    $err_string = "Someone is still logged in. Remaining time: " . ($max_allowed_time - $minutes) . " minutes";
                    session_write_close();
?>
                    <form method=POST action=./login.php name=redir>
                        <input type=hidden name=err value=<?php echo $err_value ?> />
                        <input type=hidden name=err_str value="<?php echo $err_string ?>" />
                    </form>
                    <script type=text/javascript>
                        document.redir.submit();
                    </script>
    <?php
                    return;
                }
                if (isset($old_session_id) && $old_session_id !== "") {
                    if ($debugfile) {
                        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
                        fwrite($debug_file,  "Eliminazione (tempo)->" . $old_session_id . " " . date("d/m/Y h:i:sa") . "\n");
                        fclose($debug_file);
                    }
                    $cur_session_id = session_id();
                    session_write_close();
                    session_id($old_session_id);
                    if (session_status() == PHP_SESSION_NONE) {
                        session_name('ITEMA');
                        session_start();
                    }
                    if (isset($_COOKIE[session_name()])) {
                        setcookie(session_name(), '', time() - 42000);
                    }
                    session_unset();
                    session_destroy();
                    unset($old_session_id);
                    session_write_close();

                    //Ripristino sessione attuale
                    session_id($cur_session_id);
                    if (session_status() == PHP_SESSION_NONE) {
                        session_name('ITEMA');
                        session_start();
                    }
                }
            }
        } else {
            //TODO: lockfile non esiste
        }
    }
    $_SESSION = array();
    $_SESSION['login_username'] = $user->IdUtente;
    // Kullanıcı tercihlerinden bağımsız olarak geliştirme sitesi Türkçe açılır.
    $_SESSION['lang'] = 'tr';
    $_SESSION['name'] = $user->Nome;
    $_SESSION['isAdmin'] = $user->IsAdmin;
    $_SESSION['gestioneArticoli'] = 0;  //CMDD: gestione articoli rimossa
    $_SESSION['creazioneDisegni'] = $user->DobbyCreator;
    $_SESSION['SisMisura'] = $user->SisMisura;
    $_SESSION['sessionId'] = session_id();
    $_SESSION['connectionTimestamp'] = date("Y-m-d H:i:s", time());

    $viste = $db->exec("SELECT DISTINCT vista.IdVista as idvista FROM vista INNER JOIN utenti_in_vista ON vista.IdVista = utenti_in_vista.IdVista AND IdUtente = '" . $_SESSION['login_username'] . "' LIMIT 1");
    $almenoUno = FALSE;
    foreach ($viste as $vista) {
        $_SESSION['savedVista'] = $vista['idvista'];
        $almenoUno = TRUE;
    }
    if ($almenoUno == FALSE) {
        $_SESSION['savedVista'] = -1;
    }
    $_SESSION['firstIndex'] = 1;

    if ($debugfile) {
        $debug_file = fopen($path . "/debug.txt", "a") or die("Unable to open file!");
        fwrite($debug_file,  "Authorized->" . session_name() . " - " . session_id() . " " . date("d/m/Y h:i:sa") . "\n");
        fclose($debug_file);
    }

    if ($lockfile && $user->IsAdmin == 1) {
        $iniarray = array();
        $iniarray['SessionId'] = session_id();
        $iniarray['SessionName'] = session_name();
        $iniarray['Date'] = date("Y-m-d H:i:s", time());
        write_php_ini($iniarray, $path . "/lock.ini");
    }

    session_write_close();
    header("Location: " . $index_link);
    exit;
} else {
    ?>
    <form method=POST action=login.php name=redir>
        <input type=hidden name=err value=<?php echo $err_value ?> />
        <input type=hidden name=err_str value="<?php echo $err_string ?>" />
    </form>
    <script type=text/javascript>
        document.redir.submit();
    </script>
<?php
}
?>
