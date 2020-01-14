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

require_once(dirname(dirname(__FILE__)) . '../../config.php');

defined('MOODLE_INTERNAL') || die();

$request = $_REQUEST;

function getcategories($parentid) {
    global $DB;
    $sql = "select id,name from {course_categories} where coursecount >= 0 && visible =1 && parent =$parentid";
    return $categories = $DB->get_records_sql($sql);
}

function courselist($category, $course, $checked) {
    global $DB;

    $sql = "select id,fullname from {course} where id!=$course && visible = 1 &&  category = $category";
    $courses = $DB->get_records_sql($sql);
    $courselist = "";
    if (getcategories($category)) {
        $courselist .= '<div id="accordion_' . $category . '" class="accordion panel-group col-md-12">
			<div class="panel-body" >';
        foreach (getcategories($category) as $chidld) {
            $courselist .= ' <div class="panel panel-default " >
				<div class="panel-heading categorydiv" id="category_' . $category . '_' . $chidld->id . '" >
				<h3 class="panel-title categoryname">
				<input type="checkbox" value="1" id="checkall_' . $category . '_' . $chidld->id . '" class="checkall">
				<a data-toggle="collapse"    aria-expanded="true" aria-controls="collapse_' . $category . '_' . $chidld->id . '"  href="#collapse_' . $category . '_' . $chidld->id . '">
				<i class="indicator indicatorerro' . $category . $chidld->id . ' fa fa-caret-right"  id="indicatorerro_' . $category . '' . $chidld->id . '" aria-hidden="true" ></i> ' . $chidld->name . '
				</a>
				</h3>
				</div>
				<div id="collapse_' . $category . '_' . $chidld->id . '" class="collapse collapsesubcat' . $category . '"  data-parent="#accordion_' . $category . '">
				<div class="card-body">
				<div class="form-row checkbox-group required categorycourses" id="categorycourses_' . $category . '_' . $chidld->id . '" >
				</div>
				</div>
				</div></div>';
        }
        $courselist .= '</div></div></div>';
    }

    foreach ($courses as $course) {
        $courselist .= '<div class="col-md-6 custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="course[]" value="' . $course->id . '" id="customCheck' . $course->id . '" ' . $checked . '> <!-- Added checked in the checkbox-->
            <label class="custom-control-label" for="customCheck' . $course->id . '">' . $course->fullname . '</label>
			</div>';
    }
    return $courselist;
}

if ($request['request'] == 'getcagegorycourse') {

    $categoryid = $request['categoryid'];
    $currentcourseid = $request['currentcourseid'];
    $checked = $_POST['checked'];

    echo courselist($categoryid, $currentcourseid, $checked);
    exit;
}