<?php
session_start();
require_once('vendor/autoload.php');
require_once('localization.php');

$pconfig = \HTMLPurifier_Config::createDefault();
$purifier = new \HTMLPurifier($pconfig);

global $purifier;

// SearXNG instance URL - change this to your own SearXNG instance
define('SEARXNG_URL', 'http://your-searxng-instance/searxng/search');


// Locale things
$locale = new UILocale;

if(isset($_GET['lg'])) {
    $_SESSION['lang'] = mb_strtolower($_GET['lg']);
} else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $_SESSION['lang'] = mb_strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
} else {
    $_SESSION['lang'] = "nl-nl";
}

$encoding = $locale->trRaw('encoding', $_SESSION['lang']);

function tr($string) {
    global $locale;
    global $encoding;
    return mb_convert_encoding($locale->trRaw($string, $_SESSION['lang']), $encoding, 'UTF-8');
}
header('Content-Type: text/html;charset='.$encoding);

// Fetch JSON results from SearXNG
function fetch_searxng($query) {
    $url = SEARXNG_URL . '?q=' . urlencode($query) . '&format=json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result = curl_exec($ch);
    curl_close($ch);
    return ($result === false) ? null : $result;
}

// Searching
$show_results      = false;
$final_result_html = "<hr>";
$error_text        = "";

if(isset($_GET['q'])) {

    $query        = trim($_GET['q']);
    $show_results = true;

    $json = fetch_searxng($query);

    if (!$json) {
        $error_text .= tr('error_fail_to_fetch') . "<br>";
    } else {
        $data = json_decode($json, true);
        $results = isset($data['results']) ? $data['results'] : [];

        if (count($results) == 0) {
            $error_text .= tr('error_no_results') . "<br>";
        } else {
            foreach ($results as $result) {
                $href  = isset($result['url'])   ? $result['url']   : '';
                $title = isset($result['title']) ? $result['title'] : $href;

                if (!$href) continue;

                $result_link =
                    './read.php?lg=' .
                    $_SESSION['lang'] .
                    '&a=' .
                    urlencode($href);

                $final_result_html .=
                    "<br>" .
                    "<a href='" . $result_link . "'>" .
                    "<font size='4'><b>" .
                    clean_str(htmlspecialchars($title)) .
                    "</b></font></a><br>" .
                    htmlspecialchars($href) .
                    "<br><br><hr>";
            }
        }
    }
}

// Replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace("\u{2018}", "'", $str);
    $str = str_replace("\u{2019}", "'", $str);
    $str = str_replace("\u{201C}", '"', $str);
    $str = str_replace("\u{201D}", '"', $str);
    $str = str_replace("\u{2013}", '-', $str);
    $str = str_replace("&#x27;",  "'", $str);
    return $str;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; CHARSET=<?= $encoding ?>">
    <title>FrogFind!</title>
</head>
<body>

<?php if($show_results) { ?>

    <form action="./" method="get">
    <a href="./"><font size=6 color="#008000">Frog</font><font size=6 color="#000000">Find!</font></a> <?= tr('leap_to') ?>: <input type="text" size="30" name="q" value="<?php echo htmlspecialchars(urldecode($query)) ?>">
    <input type="submit" value="<?= tr('ribbbit_button') ?>">
    </form>
    <hr>
    <br>
    <?php if($error_text) { echo "<center>" . $error_text . "</center>"; } ?>
    <center><?= tr('search_results') ?> <b><?php echo strip_tags(urldecode($query)) ?></b></center>
    <br>
    <?php echo $final_result_html ?>

<?php } else { ?>

    <br><br><center><h1><font size=7><font color="#008000">Frog</font>Find!</font></h1></center>
    <center><img src="assets/frogfind.gif" alt="a pixelated cartoon graphic of a fat, lazy, unamused frog with a keyboard in front of them, awaiting your search query" style="margin: 10px 0;"></center>
    <center><h3>The Search Engine for Vintage Computers</h3></center>
    <br><br>
    <center>
    <form action="./" method="get">
    <?= tr('leap_to') ?>: <input type="text" size="30" name="q"><br>
    <?php if(isset($_GET['lg'])) { ?><input type="hidden" name="lg" value="<?= $_GET['lg'] ?>"><?php } ?>
    <input type="submit" value="<?= tr('ribbbit_button') ?>">
    </form>
    </center>
    <br><br><br>
    <small><center><?= tr('footer_author') ?> | <a href="about.php<?php echo isset($_GET['lg']) ? '?lg=' . $purifier->purify($_GET['lg']) : ''; ?>"><?= tr('footer_about') ?></a></center></small><br>
    <small><center><a href="?lg=nl-nl">Nederlands</a> | <a href="?lg=en-us">English</a> | <a href="?lg=ru-ru">Russian</a></center></small>
    <small><center><?= tr('footer_powered') ?></center></small>

<?php } ?>

</body>
</html>
