<?php/* *  LinePHP Framework ( http://linephp.com ) *   *                                 THE LICENSE * ========================================================================== * Copyright (c) 2014 LinePHP. * * Licensed under the Apache License, Version 2.0 (the "License"); * you may not use this file except in compliance with the License. * You may obtain a copy of the License at * *      http://www.apache.org/licenses/LICENSE-2.0 * * Unless required by applicable law or agreed to in writing, software * distributed under the License is distributed on an "AS IS" BASIS, * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. * See the License for the specific language governing permissions and * limitations under the License. * ========================================================================== */namespace line\core;use line\core\util\StringUtil;/** * Deal language change. * @class Language * @link  http://linephp.com * @author Alivop[alivop.liu@gmail.com] * @since 1.0 * @package line\core */class Language extends LinePHP{    private static $LANG = array();    public static function init()    {        $file = LP_LANG_PATH . Config::$LP_SYS['language_default'] . '.lang';        if (file_exists($file)) {            $language = parse_ini_file($file, false);            self::$LANG = $language;        } else {            self::console(Config::WARN, StringUtil::systemFormat(Config::$LP_LANG['language_not_found'], $file));        }    }    public static function get($key)    {        if (array_key_exists($key, self::$LANG))            return self::$LANG[$key];        else            return '';    }    /**     * change language     * @param string $language The language name,in i18n directory,the file without 'lang' suffix .     */    public static function change($language)    {        self::console(Config::DEBUG, Config::$LP_LANG['change_language']);        if (isset($language)) {            Config::$LP_SYS['language_default'] = $language;            self::init();            if (!Config::isSessionStarted()) {                session_start();            }            $cookie = filter_input(INPUT_COOKIE, session_name(), FILTER_NULL_ON_FAILURE);            if ($cookie) {                $_SESSION[LP_LANGUAGE] = $language;                self::console(Config::DEBUG, Config::$LP_LANG['change_language_ok']);            } else {                self::console(Config::DEBUG, StringUtil::systemFormat(Config::$LP_LANG['change_language_fail'], $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']));            }        }    }}