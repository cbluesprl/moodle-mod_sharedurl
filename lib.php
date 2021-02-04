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
 * Mandatory public API of shared url module
 *
 * @package    mod_sharedurl
 * @copyright  2021 CBlue SPRL
 * @copyright  Work based on : 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in sharedurl module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function sharedurl_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function sharedurl_reset_userdata($data)
{
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function sharedurl_get_view_actions()
{
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function sharedurl_get_post_actions()
{
    return array('update', 'add');
}

/**
 * Add shared url instance.
 * @param object $data
 * @param object $mform
 * @return int new url instance id
 */
function sharedurl_add_instance($data, $mform)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/sharedurl/locallib.php');

    $data->externalurl = sharedurl_fix_submitted_url($data->externalurl);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->timemodified = time();
    $data->id = $DB->insert_record('sharedurl', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'sharedurl', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update shared url instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function sharedurl_update_instance($data, $mform)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/sharedurl/locallib.php');

    $data->externalurl = sharedurl_fix_submitted_url($data->externalurl);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('sharedurl', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'sharedurl', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete shared url instance.
 * @param int $id
 * @return bool true
 */
function sharedurl_delete_instance($id)
{
    global $DB;

    if (!$url = $DB->get_record('sharedurl', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('sharedurl', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'sharedurl', $id, null);

    // note: all context files are deleted automatically

    $DB->delete_records('sharedurl', array('id' => $url->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * It is interesting to modify the icon of the activity according to the type of the destination activity.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function sharedurl_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/sharedurl/locallib.php");

    if (!$url = $DB->get_record('sharedurl', array('id' => $coursemodule->instance),
        'id, name, externalurl, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $url->name;

    // TODO : Would be a good idea to change the default activity's icon depending on the destination module's type
    //$info->icon = sharedurl_guess_icon($url->externalurl, 24);
    $display = sharedurl_get_final_display_type($url);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/sharedurl/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($url->displayoptions) ? array() : unserialize($url->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/sharedurl/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullurl'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('sharedurl', $url, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Export shared URL resource contents
 *
 * @return array of file content
 */
function sharedurl_export_contents($cm, $baseurl)
{
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/sharedurl/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $urlrecord = $DB->get_record('sharedurl', array('id' => $cm->instance), '*', MUST_EXIST);

    $fullurl = str_replace('&amp;', '&', sharedurl_get_full_url($urlrecord, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return [];
    }

    $url = array();
    $url['type'] = 'sharedurl';
    $url['filename'] = clean_param(format_string($urlrecord->name), PARAM_FILE);
    $url['filepath'] = null;
    $url['filesize'] = 0;
    $url['fileurl'] = $fullurl;
    $url['timecreated'] = null;
    $url['timemodified'] = $urlrecord->timemodified;
    $url['sortorder'] = null;
    $url['userid'] = null;
    $url['author'] = null;
    $url['license'] = null;
    $contents[] = $url;

    return $contents;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $url url object
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 */
function sharedurl_view($url, $course, $cm, $context)
{

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $url->id
    );

    $event = \mod_sharedurl\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('sharedurl', $url);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Decide the best display format.
 * @param object $url
 * @return int display type constant
 */
function sharedurl_get_final_display_type($url) {
    global $CFG;

    if ($url->display != RESOURCELIB_DISPLAY_AUTO) {
        return $url->display;
    }

    // detect links to local moodle pages
    if (strpos($url->externalurl, $CFG->wwwroot) === 0) {
        if (strpos($url->externalurl, 'file.php') === false and strpos($url->externalurl, '.php') !== false ) {
            // most probably our moodle page with navigation
            return RESOURCELIB_DISPLAY_OPEN;
        }
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
        'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
        'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
        'video/quicktime', 'video/mpeg', 'video/mp4',
        'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
    );

    $mimetype = resourcelib_guess_url_mimetype($url->externalurl);

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}
