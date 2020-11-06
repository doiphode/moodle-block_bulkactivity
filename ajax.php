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
require_login();
$request = required_param('request', PARAM_TEXT);

function block_get_parentcategory($catid,$catides){
    global $DB;
    $cat =   $DB->get_record('course_categories',array('id'=>$catid),'id,parent');
    $catides[] = $cat->id;
    if($cat->parent !=0){
        return    block_get_parentcategory($cat->parent,$catides);
    }else{
        return $catides;
    }
}

function getcategories($parentid) {
    global $DB,$USER;
    $usercourses = enrol_get_users_courses($USER->id, true, Null, 'visible DESC,sortorder ASC');
    $encatarray =array();
    $coursearray = array();
    $parentcat =array();
    $catides = array();
    foreach($usercourses as $course) {

        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $context)) {
            $encatarray[] = $course->category;
            if ($course->visible == 1) {
                $parentcat [] = block_get_parentcategory($course->category, $catides);
            }
            $coursearray[] = $course->id;
        }
    }
    $pcidarray = array();
    foreach($parentcat as $pcats){
        foreach($pcats as $pcat){
            $pcidarray[] =  $pcat;
        }
    }




    $parentcat = array_unique($pcidarray);
    if (($key = array_search($parentid, $parentcat)) !== false) {
        unset($parentcat[$key]);
    }


    $catstr = implode(",",$parentcat);
    $parm = array(0,1,$parentid);
    if(is_siteadmin()) {
        $sql = "select id,name from {course_categories} where coursecount > ? and visible =? and parent =?";
        return $categories = $DB->get_records_sql($sql,$parm);
    }elseif(!empty($catstr)){
       
        list($insql, $inparams) = $DB->get_in_or_equal($parentcat);
          $parm =   array_merge($inparams,$parm);
           $sql = "SELECT id,name FROM {course_categories} WHERE id $insql and coursecount > ? and visible =? and parent =?";
        return $categories = $DB->get_records_sql($sql,$parm);
    }

}

function courselist($category, $course, $checked,$parm) {
    global $DB,$USER;


    $parm[] = 1;
    $usercoursess = enrol_get_users_courses($USER->id, true, Null, 'visible DESC,sortorder ASC');
    $encatarray =array();
    $coursearray =  array();
    foreach($usercoursess as $cid){
        $coursearray[] =$cid->id;
    }
    $cidstr = implode(",",$coursearray);
    if(is_siteadmin()) {
        $sql = "select id,fullname from {course} where id!=?  and  category = ? and visible = ?";
        $courses = $DB->get_records_sql($sql,$parm);
    }elseif(!empty($cidstr)){
        list($insql, $inparams) = $DB->get_in_or_equal($coursearray);
          $parm =   array_merge($inparams,$parm);
           $sql = "SELECT id,fullname FROM {course} WHERE id $insql and id!=? and  category = ? and visible = ?";
           $courses = $DB->get_records_sql($sql,$parm);
    }

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

if ($request == 'getcagegorycourse') {
    $categoryid = required_param('categoryid', PARAM_INT);
    $currentcourseid = required_param('currentcourseid', PARAM_INT);
    $checked = required_param('checked', PARAM_BOOL);
    $parm  =array($currentcourseid,$categoryid);
    echo courselist($categoryid, $currentcourseid, $checked,$parm);
    exit;
}
