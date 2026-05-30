<?php
session_start();
require_once('vendor/autoload.php');
require_once('localization.php');

// Locale things

$locale = new UILocale;
if(isset($_GET['lg'])) {
    $_SESSION['lang'] =  mb_strtolower($_GET['lg']);
} else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $_SESSION['lang'] =  mb_strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
} else {
    $_SESSION['lang'] = "ru-ru";
}

$encoding = $locale->trRaw('encoding', $_SESSION['lang']);

function tr($string) {
    global $locale;
    global $encoding;
    return mb_convert_encoding($locale->trRaw($string, $_SESSION['lang']), $encoding, 'UTF-8');
}
header('Content-Type: text/html;charset='.$encoding);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<html>
<head>
	<title>FrogFind!</title>
</head>
<body>

    <form action="./" method="get">  
    <a href="./"><font size=6 color="#008000">Frog</font><font size=6 color="#000000">Find!</font></a> Leap again: <input type="text" size="30" name="q" value="<?php echo isset($query) ? urldecode($query) : "" ?>">
    <input type="submit" value="Ribbbit!">
    </form>
    <hr>
    <br>
    <center>
    <h1><?= tr('about_what_is_t'); ?></h1>
    <small><?= tr('about_faq_short_description'); ?></small>
    </center>
    <br>
    <h3><?= tr('about_who_made_q'); ?></h3>
    <?= tr('about_who_made_a'); ?>
    <h3><?= tr('about_how_does_q'); ?></h3>
    <?= tr('about_how_does_a'); ?>
    <h3><?= tr('about_what_machines_do_you_test_q'); ?></h3>
    <?= tr('about_what_machines_do_you_test_a'); ?>
    <h3><?= tr('about_how_can_i_get_in_touch_q'); ?></h3>
    <?= tr('about_how_can_i_get_in_touch_a'); ?>
	<center>
	<h1><?= tr('about_what_is_frogfindplus_t'); ?></h1>
    </center>
    <h3><?= tr('about_frogfindplus_whats_difference_q'); ?></h3>
    <?= tr('about_frogfindplus_whats_difference_a'); ?>
    <h3><?= tr('about_frogfindplus_who_translated_q'); ?></h3>
    <?= tr('about_frogfindplus_who_translated_a'); ?>
    <h3><?= tr('about_frogfindplus_does_have_source_q'); ?></h3>
    <?= tr('about_frogfindplus_does_have_source_a'); ?>
</body>
</html>
