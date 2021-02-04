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
 * Print url introduction.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function sharedurl_print_intro($url, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($url->displayoptions) ? array() : unserialize($url->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($url->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'urlintro');
            echo format_module_intro('sharedurl', $url, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display url frames.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function sharedurl_display_frame($url, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        sharedurl_print_header($url, $cm, $course);
        sharedurl_print_heading($url, $cm, $course);
        sharedurl_print_intro($url, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('sharedurl');
        $context = context_module::instance($cm->id);
        $exteurl = sharedurl_get_full_url($url, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/sharedurl/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($url->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','sharedurl'));
        $contentframetitle = s(format_string($url->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print url info and link.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function sharedurl_print_workaround($url, $cm, $course) {
    global $OUTPUT;

    sharedurl_print_header($url, $cm, $course);
    sharedurl_print_heading($url, $cm, $course, true);
    sharedurl_print_intro($url, $cm, $course, true);

    $fullurl = sharedurl_get_full_url($url, $cm, $course);

    $display = sharedurl_get_final_display_type($url);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($url->displayoptions) ? array() : unserialize($url->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="urlworkaround">';
    print_string('clicktoopen', 'sharedurl', "<a href=\"$fullurl\" $extra>$fullurl</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded url file.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function sharedurl_display_embed($url, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = resourcelib_guess_url_mimetype($url->externalurl);
    $fullurl  = sharedurl_get_full_url($url, $cm, $course);
    $title    = $url->name;

    $link = html_writer::tag('a', $fullurl, array('href'=>str_replace('&amp;', '&', $fullurl)));
    $clicktoopen = get_string('clicktoopen', 'sharedurl', $link);
    $moodleurl = new moodle_url($fullurl);

    $extension = resourcelib_get_extension($url->externalurl);

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullurl, $title);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
    }

    sharedurl_print_header($url, $cm, $course);
    sharedurl_print_heading($url, $cm, $course);

    echo $code;

    sharedurl_print_intro($url, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * TODO : Would be a good idea to change the default activity's icon depending on the destination module's type
 *
 * @param $fullurl
 * @param int $size of the icon.
 * @return string|null icon or null if the fullurl is not relevant
 */
/*
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

    // TODO : Do not work for now...

    return $icon;
}
*/