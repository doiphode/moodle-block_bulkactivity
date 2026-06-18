<?php

defined('MOODLE_INTERNAL') || die();

function moveto_module_new($mod, $section, $beforemod=NULL) {
    global $OUTPUT, $DB;

    if (empty($mod) || empty($section)) {
        return false;
    }

    // Current module visibility state - return value of this function.
    $modvisible = $mod->visible;

    // If moving to a hidden section then hide module.
    if ($mod->section != $section->id) {
        if (!$section->visible && $mod->visible) {
            // Module was visible but must become hidden after moving to hidden section.
            $modvisible = 0;
            set_coursemodule_visible($mod->id, 0);
            // Set visibleold to 1 so module will be visible when section is made visible.
            $DB->set_field('course_modules', 'visibleold', 1, array('id' => $mod->id));
        }
        if ($section->visible && !$mod->visible) {
            // Hidden module was moved to the visible section, restore the module visibility from visibleold.
            set_coursemodule_visible($mod->id, $mod->visibleold);
            $modvisible = $mod->visibleold;
        }
    }

    // Add the module into the new section.
    course_add_cm_to_section($section->course, $mod->id, $section->section, $beforemod);
    return $modvisible;
}
/**
 * Get Bootstrap data-* attribute names compatible with the current Moodle version.
 * Moodle 5.0+ uses Bootstrap 5 (data-bs-toggle / data-bs-parent),
 * Moodle 4.x uses Bootstrap 4 (data-toggle / data-parent).
 *
 * @return array ['toggle' => string, 'parent' => string]
 */
function block_bulkactivity_bs_attrs() {
    global $CFG;
    if ((int)$CFG->branch >= 500) {
        return array('toggle' => 'data-bs-toggle', 'parent' => 'data-bs-parent');
    }
    return array('toggle' => 'data-toggle', 'parent' => 'data-parent');
}