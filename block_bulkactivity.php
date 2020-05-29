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
 * Bulk Activity Creation
 * @package    block_bulkactivity
 * @copyright  2019 Queen Mary University of London
 * @author     Shubhendra R Doiphode <doiphode.sunny@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The bulk activity creation block class
 */
class block_bulkactivity extends block_base {
    public function init() {
        $this->title = get_string('pluginname', __CLASS__);
        $this->version = 2015012700;
    }

    public function applicable_formats() {
        return array(
            'site' => true,
            'course' => true,
            'course-category' => false,
            'mod' => false,
            'my' => false,
            'tag' => false,
            'admin' => false,
        );
    }

    public function instance_can_be_docked() {
        return false; // AJAX won't work with Dock.
    }

    public function has_config() {
        return false;
    }

    /**
     *  Get the block content
     *
     * @return object|string
     * @global object $USER
     * @global object $CFG
     */
    public function get_content() {
        global $CFG, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!$this->page->user_is_editing()) {
            return $this->content = '';
        }
        $context = context_course::instance($this->page->course->id);
        if (!has_capability('moodle/backup:backupactivity', $context)) {
            return $this->content = '';
        }

        $html = "";

        $this->page->requires->jquery();
        $this->page->requires->js('/blocks/bulkactivity/script.js?v=' . time());

        $this->page->requires->strings_for_js(
            array('confirm_copy', 'createactivityincourses'),
            __CLASS__
        );

        $footer = '<div style="display:none;">'
            . '<div class="header-commands">' . $this->get_header() . '</div>'
            . '</div>';
        return $this->content = (object)array('text' => $html, 'footer' => $footer);
    }

    /**
     * Get the block header
     *
     * @return string
     * @global core_renderer $OUTPUT
     */
    private function get_header() {
        global $OUTPUT;
        // Link to bulkdelete
        $alt = get_string('bulkactivity', __CLASS__);
        $src = $OUTPUT->image_url('bulkactivity', __CLASS__);
        $url = new moodle_url('/blocks/bulkactivity/bulkactivity.php', array('course' => $this->page->course->id));
    }

    /**
     * Get bulk delete
     *
     * @param string $src
     * @param string $alt
     * @param moodle_url $url
     * @return string
     */
    private function get_bulk_activity($src, $alt, $url) {
        $bulkactivity = '<a class="editing_bulkactivity" title="' . s($alt) . '" href="' . s($url) . '">'
            . '<img src="' . s($src) . '" alt="' . s($alt) . '" />'
            . '</a>';

        return $bulkactivity;
    }


    /**
     * Check Moodle 3.2 or later.
     *
     * @return boolean.
     */
    private function is_special_version() {
        return moodle_major_version() >= 3.2;
    }

}
