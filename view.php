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
 * SharedURL module main user interface
 *
 * @package    mod_sharedurl
 * @copyright  2021 CBlue SPRL
 * @copyright  Work based on : 2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/sharedurl/lib.php");
require_once("$CFG->dirroot/mod/sharedurl/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);        // Course module ID (sharedurl's id)

$cm = get_coursemodule_from_id('sharedurl', $id, 0, false, MUST_EXIST);
$url = $DB->get_record('sharedurl', array('id' => $cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/sharedurl:view', $context);

// Completion and trigger events.
sharedurl_view($url, $course, $cm, $context);

$PAGE->set_url('/mod/sharedurl/view.php', array('id' => $cm->id));

// Make sure URL exists before generating output
$parsed_url = parse_url(trim($url->externalurl));

// Sharedurl only works for module (must contains "/mod/" and "id=")
$is_valid_web = in_array($parsed_url['scheme'], ['http', 'https']);
$is_valid_module = strpos($parsed_url['path'], '/mod/') !== false;
$has_id_param = strpos($parsed_url['query'], 'id=') !== false;
$is_in_same_domain = $parsed_url['host'] == $PAGE->url->get_host();
$does_course_module_exist = true;

if ($is_valid_web && $is_valid_module && $has_id_param && $is_in_same_domain) {
    // Then check if module exists
    parse_str($parsed_url['query'], $get_values);
    try {
        list($course_destination, $cm_destination) = get_course_and_cm_from_cmid($get_values['id']);
    } catch (Exception $e) {
        $does_course_module_exist = false;
    }
}

// Course or module does not exist
if (!$does_course_module_exist || !$cm_destination || !$course_destination) {
    sharedurl_print_header($url, $cm, $course);
    sharedurl_print_heading($url, $cm, $course);
    notice(get_string('invalidstoredurl', 'sharedurl'), new moodle_url('/course/view.php', array('id' => $cm->course)));
    die;
}

$fullurl = str_replace('&amp;', '&', sharedurl_get_full_url($url, $cm, $course));

if (!course_get_format($course)->has_view_page()) {
    // If course format does not have a view page, add redirection delay with a link to the edit page.
    // Otherwise teacher is redirected to the external URL without any possibility to edit activity or course settings.
    $editurl = null;
    if (has_capability('moodle/course:manageactivities', $context)) {
        $editurl = new moodle_url('/course/modedit.php', array('update' => $cm->id));
        $edittext = get_string('editthisactivity');
    } else if (has_capability('moodle/course:update', $context->get_course_context())) {
        $editurl = new moodle_url('/course/edit.php', array('id' => $course->id));
        $edittext = get_string('editcoursesettings');
    }
    if ($editurl) {
        redirect($fullurl, html_writer::link($editurl, $edittext) . "<br/>" .
            get_string('pageshouldredirect'), 10);
    }
}

// Check if the user is enrolled in the destination course
$context = context_course::instance($course_destination->id);
if (!is_enrolled($context, $USER->id, '', true)) {
    $enrol_plugin = enrol_get_plugin('shared');

    // Add enrol instance of enrol_shared if it is not already added to the destination course
    if (!$DB->record_exists('enrol', array('courseid' => $course_destination->id, 'enrol' => 'shared'))) {
        $enrol_plugin->add_default_instance($course_destination);
    }

    $instance = $DB->get_record('enrol', array('courseid' => $course_destination->id, 'enrol' => 'shared'), '*', MUST_EXIST);

    // Enrol the user via enrol_shared method
    $enrol_plugin->enrol_user($instance, $USER->id, $instance->roleid, $instance->enrolstartdate, $instance->enrolenddate);
}

redirect($fullurl);