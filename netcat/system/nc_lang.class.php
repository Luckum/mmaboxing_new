<?php

/* $Id: nc_lang.class.php 8329 2012-11-02 11:31:02Z vadim $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Lang extends nc_System {

    protected $langtable;

    public function __construct() {
        // load parent constructor
        parent::__construct();

        $this->langtable = array(
                'af' => 'Afrikaans',
                'sq' => 'Albanian',
                'ar' => 'Arabic',
                'hy' => 'Armenian',
                'as' => 'Assamese',
                'az' => 'Azeri',
                'eu' => 'Basque',
                'be' => 'Belarusian',
                'bn' => 'Bengali',
                'bg' => 'Bulgarian',
                'ca' => 'Catalan',
                'zh' => 'Chinese',
                'hr' => 'Croatian',
                'cs' => 'Chech',
                'da' => 'Danish',
                'div' => 'Divehi',
                'nl' => 'Dutch',
                'en' => 'English',
                'et' => 'Estonian',
                'fo' => 'Faeroese',
                'fa' => 'Farsi',
                'fi' => 'Finnish',
                'fr' => 'French',
                'mk' => 'FYRO Macedonian',
                'gd' => 'Gaelic',
                'ka' => 'Georgian',
                'de' => 'German',
                'el' => 'Greek',
                'gu' => 'Gujarati',
                'he' => 'Hebrew',
                'hi' => 'Hindi',
                'hu' => 'Hungarian',
                'is' => 'Icelandic',
                'id' => 'Indonesian',
                'it' => 'Italian',
                'ja' => 'Japanese',
                'kn' => 'Kannada',
                'kk' => 'Kazakh',
                'kok' => 'Konkani',
                'ko' => 'Korean',
                'kz' => 'Kyrgyz',
                'lv' => 'Latvian',
                'lt' => 'Lithuanian',
                'ms' => 'Malay',
                'ml' => 'Malayalam',
                'mt' => 'Maltese',
                'mr' => 'Marathi',
                'mn' => 'Mongolian',
                'ne' => 'Nepali',
                'no' => 'Norwegian',
                'or' => 'Oriya',
                'pl' => 'Polish',
                'pt' => 'Portuguese',
                'pa' => 'Punjabi',
                'rm' => 'Rhaeto-Romanic',
                'ro' => 'Romanian',
                'ru' => 'Russian',
                'sa' => 'Sanskrit',
                'sr' => 'Serbian',
                'sk' => 'Slovak',
                'ls' => 'Slovenian',
                'sb' => 'Sorbian',
                'es' => 'Spanish',
                'sx' => 'Sutu',
                'sw' => 'Swahili',
                'sv' => 'Swedish',
                'syr' => 'Syriac',
                'ta' => 'Tamil',
                'tt' => 'Tatar',
                'te' => 'Telugu',
                'th' => 'Thai',
                'ts' => 'Tsonga',
                'tn' => 'Tswana',
                'tr' => 'Turkish',
                'uk' => 'Ukrainian',
                'ur' => 'Urdu',
                'uz' => 'Uzbek',
                'vi' => 'Vietnamese',
                'xh' => 'Xhosa',
                'yi' => 'Yiddish',
                'zu' => 'Zulu'
        );
    }

    public function get_all() {
        return $this->langtable;
    }

    public function full_from_acronym($lang) {
        if ($lang && array_key_exists($lang, $this->langtable))
                return $this->langtable[$lang];
    }

    public function acronym_from_full($lang) {

        foreach ($this->langtable as $key => $value) {
            if ($value == $lang) return $key;
        }
    }

    /**
     * Метод определение языка
     *
     * Порядок определения:
     * - из текущего сайта, если он задан
     * - по переменной NEW_AUTH_LANG , пришедший из post'a
     * - из глобальной переменной AUTH_LANG
     * - по переменной PHP_AUTH_LANG, взяйтой из cookies
     * - из сессии пользоватедя
     * - по параметру ADMIN_LANGUAGE из конфигурационного файла
     * - первый попавшийся язык из директории lang
     *
     * @global string $AUTH_LANG
     * @param int $get_acronym вернуть акрноим
     *
     * @return string язык, например "Russian"
     */
    public function detect_lang($get_acronym = 0) {
        global $AUTH_LANG;

        $nc_core = nc_Core::get_object();
        //try {
        //	$lang = $this->full_from_acronym($nc_core->subdivision->get_lang($nc_core->subdivision->get_current('Subdivision_ID')));
        //}
        //catch ( Exception $e ) {
        $lang = $this->full_from_acronym($nc_core->catalogue->get_current('Language'));
        //}
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        $lang = $nc_core->input->fetch_get_post('NEW_AUTH_LANG');
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        $lang = $AUTH_LANG;
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        $lang = $nc_core->input->fetch_cookie('PHP_AUTH_LANG');
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        $lang = $_SESSION['User']['PHP_AUTH_LANG'];
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        $lang = $nc_core->ADMIN_LANGUAGE;
        if ($lang && $this->_check_lang($lang))
                return $get_acronym ? $this->acronym_from_full($lang) : $lang;

        if (($lang_folder = @opendir($nc_core->ADMIN_FOLDER."lang/"))) {
            while (($lang_file = readdir($lang_folder)) !== false) {
                if (substr($lang_file, -3, 3) == "php") {
                    $lang = str_replace(".php", "", $lang_file);
                    if ($lang && $this->_check_lang($lang))
                            return $get_acronym ? $this->acronym_from_full($lang) : $lang;
                }
            }
        }

        throw new Exception("Failed to determine language");
    }

    /**
     * Проверка языка на корректность
     *
     * @param string $lang язык
     * @return boolean язык можно использовать или нет
     */
    private function _check_lang($lang) {
        $nc_core = nc_Core::get_object();
        if (!preg_match("/^\w+$/", $lang)) return false;
        return file_exists($nc_core->ADMIN_FOLDER."lang/".$lang.".php");
    }

}