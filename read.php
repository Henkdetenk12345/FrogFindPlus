<?php
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

session_start();
require_once('vendor/autoload.php');
require_once('localization.php');

// Locale things
$locale = new UILocale;

if(isset($_GET['lg'])) {
    $_SESSION['lang'] = mb_strtolower($_GET['lg']);
} else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $_SESSION['lang'] = mb_strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
} else {
    $_SESSION['lang'] = "en-us";
}

$encoding = $locale->trRaw('encoding', $_SESSION['lang']);

function tr($string) {
    global $locale;
    global $encoding;
    return mb_convert_encoding($locale->trRaw($string, $_SESSION['lang']), $encoding, 'UTF-8');
}

function localeEncode($string) {
    global $encoding;
    return mb_convert_encoding($string, $encoding, 'UTF-8');
}

header('Content-Type: text/html;charset=' . $encoding);

// Fetch a URL with cURL, returns ['body' => ..., 'content_type' => ..., 'content_length' => ...]
function fetch_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
        'AppleWebKit/537.36 (KHTML, like Gecko) ' .
        'Chrome/120.0.0.0 Safari/537.36'
    );
    curl_setopt($ch, CURLOPT_ENCODING, ''); // handle gzip/deflate automatically
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // some old sites have bad certs

    $body         = curl_exec($ch);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $content_size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $http_code >= 400) return null;

    return [
        'body'           => $body,
        'content_type'   => $content_type ?? '',
        'content_length' => $content_size,
        'http_code'      => $http_code,
    ];
}

$proxy_download_max_filesize = 10 * 1024 * 1024; // 10 MB

$compatible_content_types = ['text/html', 'text/plain', 'text/xml', 'application/xhtml+xml'];

$article_url  = "";
$article_html = "";
$error_text   = "";
$readable_article = "";
$article_title    = "";

if (isset($_GET['a'])) {
    $article_url = $_GET['a'];
} else {
    echo "What do you think you're doing... >:(";
    exit();
}

if (substr($article_url, 0, 4) != "http") {
    echo tr("error_not_webpage");
    die();
}

$lang = isset($_GET['lg']) ? $_GET['lg'] : $_SESSION['lang'];
$host = parse_url($article_url, PHP_URL_HOST);

$response = fetch_url($article_url);

if (!$response) {
    $error_text .= tr("error_article_fail") . "<br>";
} else {
    $content_type = strtolower(explode(';', $response['content_type'])[0]);
    $is_html = in_array($content_type, $compatible_content_types);

    if (!$is_html) {
        // Proxy file download
        $filesize = $response['content_length'];
        if ($filesize > $proxy_download_max_filesize) {
            echo 'File too large to proxy. <a href="' . htmlspecialchars($article_url) . '">Download directly</a>.';
            die();
        }
        $filename = basename(parse_url($article_url, PHP_URL_PATH)) ?: 'download';
        header('Content-Type: ' . $response['content_type']);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $response['body'];
        die();
    }

    // Parse with Readability
    $configuration = new Configuration();
    $configuration
        ->setArticleByLine(false)
        ->setFixRelativeURLs(true)
        ->setOriginalURL($article_url);

    $readability = new Readability($configuration);

    try {
        $readability->parse($response['body']);
        $article_title = $readability->getTitle();

        $readable_article = strip_tags(
            $readability->getContent(),
            '<a><ol><ul><li><br><p><small><font><b><strong><i><em><blockquote><h1><h2><h3><h4><h5><h6>'
        );
        $readable_article = str_replace('strong>', 'b>', $readable_article);
        $readable_article = str_replace('em>', 'i>', $readable_article);
        $readable_article = clean_str($readable_article);
        $readable_article = str_replace(
            'href="http',
            'href="./read.php?lg=' . urlencode($lang) . '&a=http',
            $readable_article
        );

    } catch (ParseException $e) {
        $error_text .= 'Sorry, could not parse this page: ' . htmlspecialchars($e->getMessage()) . '<br>';
    }
}

// Replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace("\u{2018}", "'", $str);
    $str = str_replace("\u{2019}", "'", $str);
    $str = str_replace("\u{201C}", '"', $str);
    $str = str_replace("\u{201D}", '"', $str);
    $str = str_replace("\u{2013}", '-', $str);
    return $str;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= $encoding ?>">
    <title><?php echo htmlspecialchars($article_title); ?></title>
</head>
<body>
    <p>
        <form action="./read.php" method="get">
        <a href="./?lg=<?= urlencode($lang) ?>"><?= tr('back_to_frogfind'); ?> <b><font color="#008000">Frog</font><font color="000000">Find!</font></b></a> | <?= tr('browsing_url'); ?>: <input type="text" size="38" name="a" value="<?php echo htmlspecialchars($article_url) ?>">
        <input type="hidden" name="lg" value="<?= htmlspecialchars($lang); ?>">
        <input type="submit" value="<?= tr('go'); ?>">
        </form>
    </p>
    <hr>
    <h1><?php echo localeEncode(clean_str($article_title)); ?></h1>
    <?php
        if (isset($readability)) {
            $img_num = 0;
            $imgline_html = "View page images:";
            foreach ($readability->getImages() as $image_url) {
                if (strpos($image_url, ".jpg")  !== false ||
                    strpos($image_url, ".jpeg") !== false ||
                    strpos($image_url, ".png")  !== false) {
                    $img_num++;
                    $imgline_html .= " <a href='./image.php?i=" . urlencode($image_url) . "'>[$img_num]</a> ";
                }
            }
            if ($img_num > 0) echo "<p><small>" . $imgline_html . "</small></p>";
        }
    ?>
    <?php if ($error_text) { echo "<p><font color='red'>" . $error_text . "</font></p>"; } ?>
    <p><font size="4"><?php echo localeEncode($readable_article); ?></font></p>
</body>
</html>
