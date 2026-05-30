<?php


class UILocale {
    protected function parseLang($file): array
    {
        $string = file_get_contents($file);
        $string = preg_replace("%^\%{.*\%}\r?$%m", "", $string); #Remove comments
        $array  = [];

        foreach(preg_split("%;[\\r\\n]++%", $string) as $statement) {
            $s = explode(" = ", trim($statement));

            // skip empty or comment-only lines that have no value part
            if (count($s) < 2 || trim($s[0]) === '') {
                continue;
            }

            try {
                $array[eval("return $s[0];")] = eval("return $s[1];");
            } catch(\ParseError $ex) {
                // silently skip unparseable statements
            }
        }

        return $array;
    }

    function trRaw($string, $locale) {
        $lang = empty($locale) ? "en-us" : $locale;
        $base = dirname(__FILE__) . "/languages/";

        // try the requested language; fall back to English if the file doesn't exist
        if (file_exists($base . "$lang.strings")) {
            $array = $this->parseLang($base . "$lang.strings");
        } else {
            $array = [];
        }

        // key missing and not already on English: try English as fallback
        if (!isset($array[$string]) && $lang !== "en-us" && file_exists($base . "en-us.strings")) {
            $en_array = $this->parseLang($base . "en-us.strings");
            return $en_array[$string] ?? "@$string";
        }

        return $array[$string] ?? "@$string";
    }
}