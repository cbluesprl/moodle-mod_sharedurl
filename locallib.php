<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Private shared url module utility functions
 *
 * @package    mod_sharedurl
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/sharedurl/lib.php");

/**
 * This methods does weak url validation, we are looking for major problems only,
 * no strict RFE validation. And we do not check if the URL is a valid activity.
 *
 * @param $url
 * @return bool true is seems valid, false if definitely not valid URL
 */
function sharedurl_appears_valid_url($url)
{
    if (preg_match('/^(\/|https?:|ftp:)/i', $url)) {
        // note: this is not exact validation, we look for severely malformed URLs only
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[^ @]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $url);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $url);
    }
}

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $url
 * @return string
 */
function sharedurl_fix_submitted_url($url)
{
    // note: empty urls are prevented in form validation
    $url = trim($url);

    // remove encoded entities - we want the raw URI here
    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

    if (!preg_match('|^[a-z]+:|i', $url) and !preg_match('|^/|', $url)) {
        // invalid URI, try to fix it by making it normal URL,
        // please note relative urls are not allowed, /xx/yy links are ok
        $url = 'http://' . $url;
    }

    return $url;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $url
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function sharedurl_get_full_url($url, $cm, $course, $config = null)
{

    $parameters = empty($url->parameters) ? array() : unserialize($url->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullurl = html_entity_decode($url->externalurl, ENT_QUOTES, 'UTF-8');

    $letters = '\pL';
    $latin = 'a-zA-Z';
    $digits = '0-9';
    $symbols = '\x{20E3}\x{00AE}\x{00A9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}' .
        '\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BF0}';
    $arabic = '\x{FE00}-\x{FEFF}';
    $math = '\x{2190}-\x{21FF}\x{2900}-\x{297F}';
    $othernumbers = '\x{2460}-\x{24FF}';
    $geometric = '\x{25A0}-\x{25FF}';
    $emojis = '\x{1F000}-\x{1F6FF}';

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullurl) or preg_match('|^/|', $fullurl)) {
        // encode extra chars in URLs - this does not make it always valid, but it helps with some UTF-8 problems
        // Thanks to 💩.la emojis count as valid, too.
        $allowed = "[" . $letters . $latin . $digits . $symbols . $arabic . $math . $othernumbers . $geometric .
            $emojis . "]" . preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullurl = preg_replace_callback("/[^$allowed]/u", 'url_filter_callback', $fullurl);
    } else {
        // encode special chars only
        $fullurl = str_replace('"', '%22', $fullurl);
        $fullurl = str_replace('\'', '%27', $fullurl);
        $fullurl = str_replace(' ', '%20', $fullurl);
        $fullurl = str_replace('<', '%3C', $fullurl);
        $fullurl = str_replace('>', '%3E', $fullurl);
    }

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Print url header.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return void
 */
function sharedurl_print_header($url, $cm, $course)
{
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname . ': ' . $url->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($url);
    echo $OUTPUT->header();
}

/**
 * Print url heading.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function sharedurl_print_heading($url, $cm, $course, $notused = false)
{
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($url->name), 2);
}

/**
 * TODO
 * Return the corresponding activity's acin according to the destination activity type
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null icon or null if the fullurl is not relevant
 */
function sharedurl_guess_icon($fullurl, $size = null)
{
    global $CFG, $DB;
    $icon = null;

    $all_modules = $DB->get_records('modules', null,  '', 'name');

    $all_modules = array_keys($all_modules);

    $string = ' ' . $fullurl;
    $ini = strpos($string, 'mod/');
    if ($ini == 0) {
        return null;
    }
    $ini += strlen('mod/');
    $len = strpos($string, '/', $ini) - $ini;
    $activity_type = substr($string, $ini, $len);

    if (in_array($activity_type, $all_modules)) {
        // Retrieveicon path of destination activity's icon (look for another extension if png is not found)
        $icon_path = '/mod/' . $activity_type . '/pix/icon';
        $png_version = $icon_path . '.png';
        $gif_version = $icon_path . '.gif';
        $svg_version = $icon_path . '.svg';
        if (file_exists($CFG->dirroot . $png_version)) {
            $icon = $png_version;
        } else if (file_exists($CFG->dirroot . $gif_version)) {
            $icon = $gif_version;
        } else if (file_exists($CFG->dirroot . $svg_version)) {
            $icon = $svg_version;
        }
    }

    // TODO : ne fonctionne pas... Les images ne sont pas chargées aussi facilement. A voir si possible de trouver une solution.

    return $icon;
}
