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
 * Shared sharedurl module admin settings and defaults
 *
 * @package    mod_sharedsharedurl
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('sharedurl/framesize',
        get_string('framesize', 'sharedurl'), get_string('configframesize', 'sharedurl'), 130, PARAM_INT));
    $settings->add(new admin_setting_configmultiselect('sharedurl/displayoptions',
        get_string('displayoptions', 'sharedurl'), get_string('configdisplayoptions', 'sharedurl'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('sharedurlmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('sharedurl/printintro',
        get_string('printintro', 'sharedurl'), get_string('printintroexplain', 'sharedurl'), 1));
    $settings->add(new admin_setting_configselect('sharedurl/display',
        get_string('displayselect', 'sharedurl'), get_string('displayselectexplain', 'sharedurl'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    $settings->add(new admin_setting_configtext('sharedurl/popupwidth',
        get_string('popupwidth', 'sharedurl'), get_string('popupwidthexplain', 'sharedurl'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('sharedurl/popupheight',
        get_string('popupheight', 'sharedurl'), get_string('popupheightexplain', 'sharedurl'), 450, PARAM_INT, 7));
}
