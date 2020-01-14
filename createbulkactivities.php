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

global $PAGE, $CFG, $DB, $OUTPUT;
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string("pluginname", "block_bulkactivity"));
$PAGE->set_heading(get_string("pluginname", "block_bulkactivity"));
$PAGE->navbar->ignore_active();
$PAGE->set_url($CFG->wwwroot . "/blocks/bulkactivity/createbulkactivity.php");

$PAGE->requires->jquery();

$PAGE->requires->css('/blocks/bulkactivity/styles.css');

echo $OUTPUT->header();
if (!isset($_POST['createduplicate'])) {
    global $USER, $DB;
    $modid = $_REQUEST["cba"];
    $cmid = required_param('cba', PARAM_INT);
    $sectionreturn = optional_param('sr', null, PARAM_INT);
    $sql = "select id,name from {course_categories} where coursecount >= 0 && visible =1 && parent =0";
    $categories = $DB->get_records_sql($sql);

    $cm = get_coursemodule_from_id('', $modid, 0, true, MUST_EXIST);

    $context = context_course::instance($cm->course);
    if (!has_capability('block/bulkactivity:addinstance', $context)){
        die();
    }

    function courselist($category, $course) {
        global $DB;
        $sql = "select id,fullname from {course} where id!=$course && visible = 1 &&  category = $category";
        $courses = $DB->get_records_sql($sql);
        $courselist = "";
        foreach ($courses as $course) {
            $courselist .= '<div class="col-md-6 custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="course[]" value="' . $course->id . '" id="customCheck' . $course->id . '">
            <label class="custom-control-label" for="customCheck' . $course->id . '">' . $course->fullname . '</label>
        </div>';
        }
        return $courselist;

    }

    ?>

    <!--    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script> -->
    <div class="container">

        <input type="hidden" id="currentcourseid" value="<?= $cm->course ?>">
        <h3 class="categoryname"><?= get_string('courselistheader', 'block_bulkactivity') ?></h3>
        <form action="createbulkactivities.php" method="post" id="bulkactform">
			<div class="form-group row">
               <label for="copyactivityto" class="col-sm-2 col-form-label">Copy activity to :</label>
               <div class="col-sm-3">
                   <select class="custom-select mr-sm-2" id="copyactivityto" name="copyactivityto">
                       <option value="0">Section 0</option>
                       <option value="1">Section 1</option>
                       <option value="2">Last Section</option>
                   </select>
               </div>
           </div>
			
            <?php
            echo '<div id="blkh3">';
            echo '<div id="accordion" class="accordion panel-group">

				<div class="panel-body" >';
            foreach ($categories as $category) {
                echo ' <div class="panel panel-default" >
                        <div class="panel-heading categorydiv" id="category_' . $category->id . '" >

						<h3 class="panel-title categoryname" style="font-weight: 100">
                            <input type="checkbox" class="checkall" id="checkall_' . $category->id . '" value="1">&nbsp;
                            <a data-toggle="collapse"    aria-expanded="true" aria-controls="collapse_' . $category->id . '"  href="#collapse_' . $category->id . '">
								<i class="indicator indicatorerro fa fa-caret-right" id="indicatorerro_' . $category->id . '" aria-hidden="true" ></i> ' . $category->name . '
							</a>
						</h3>
					</div>
                        <div id="collapse_' . $category->id . '" class="collapse collapsesubcat"  data-parent="#accordion" style="padding-left: 15px;padding-right: 15px;">
                        <div class="card-body" style="padding: 0px;">
                             <div class="form-row checkbox-group required categorycourses" id="categorycourses_' . $category->id . '" >
            </div>
         </div>
					</div></div>';
            }

            echo '</div></div>';

            ?>
            <input type="hidden" name="cmid" value="<?= $cmid ?>">
            <input type="submit" class="btn btn-primary" value="<?= get_string("submit") ?>" name="createduplicate"
                   style="margin-top: 30px;">
    </div>
    </form>
    </div>


    <?php

}

if (isset($_POST['createduplicate'])) {

    $modid = $_POST['cmid'];

    function duplicate_modulebac($course, $cm) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->libdir . '/filelib.php');

        $a = new stdClass();
        $a->modtype = get_string('modulename', $cm->modname);
        $a->modname = format_string($cm->name);

        if (!plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {
            throw new moodle_exception('duplicatenosupport', 'error', '', $a);
        }

        // Backup the activity.

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();

        $bc->destroy();

        // Restore the backup immediately.

        $rc = new restore_controller($backupid, $course->id,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

        $cmcontext = context_module::instance($cm->id);

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }

        $rc->execute_plan();

        // Now a bit hacky part follows - we try to get the cmid of the newly restored copy of the module.
        $newcmid = null;

        $tasks = $rc->get_plan()->get_tasks();

        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
        }
        if ($newcmid) {

            $sql = "select * from {course_sections} where course = $course->id";
            $csections = $DB->get_records_sql($sql);
            $empsection = array();
            foreach ($csections as $section) {

                $sequencearray = explode(",", $section->sequence);
                $empsection[] = $section->id;
                foreach ($sequencearray as $key => $arr) {
                    if ($arr == $newcmid) {
                        unset($sequencearray[$key]);
                        $newsec = implode(",", $sequencearray);
                        $update = new stdClass();
                        $update->id = $section->id;
                        $update->sequence = $newsec;
                        $DB->update_record('course_sections', $update);
                    }
                }
            }

            if($empsection){
                //  $sectionid = min($empsection);


                if($_POST['copyactivityto']==0){
                    $sectionid = min($empsection);
                }elseif($_POST['copyactivityto']==1){
                    $sectionid =$empsection[1];
                }elseif ($_POST['copyactivityto']==2){
                    $sectionid =  max($empsection);
                }




                $sescq = $DB->get_record('course_sections', array('id' => $sectionid), 'sequence');
                $oldsec = $sescq->sequence;
                $oldsecarr = explode(",", $oldsec);
                $oldsecarr[] = $newcmid;
                $newsecarr = implode(",", $oldsecarr);

                $updatesec = new stdClass();
                $updatesec->id = $sectionid;
                $updatesec->sequence = $newsecarr;
                $DB->update_record('course_sections', $updatesec);

                $newcm = new stdClass();
                $newcm->id = $newcmid;
                $newcm->section = $sectionid;
                $DB->update_record('course_modules', $newcm);
            }
        }

        $rc->destroy();

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        // If we know the cmid of the new course module, let us move it
        // right below the original one. otherwise it will stay at the
        // end of the section.

        if ($newcmid) {
            // Proceed with activity renaming before everything else. We don't use APIs here to avoid
            // triggering a lot of create/update duplicated events.
            $newcm = get_coursemodule_from_id($cm->modname, $newcmid, $cm->course);
            // Add ' (copy)' to duplicates. Note we don't cleanup or validate lengths here. It comes
            // from original name that was valid, so the copy should be too.
            // $newname = get_string('duplicatedmodule', 'moodle', $newcm->name);
            // $DB->set_field($cm->modname, 'name', $newname, ['id' => $newcm->instance]);
            $section = $DB->get_record('course_sections', array('id' => $cm->section, 'course' => $cm->course));
            $modarray = explode(",", trim($section->sequence));
            $cmindex = array_search($cm->id, $modarray);
            if ($cmindex !== false && $cmindex < count($modarray) - 1) {
                moveto_module($newcm, $section, $modarray[$cmindex + 1]);
            }

        }

        return isset($newcm) ? $newcm : null;
    }

    $sesskey = $USER->sesskey;

    foreach ($_POST['course'] as $course_id) {

        $cm = get_coursemodule_from_id('', $modid, 0, true, MUST_EXIST);

        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $modcontext = context_module::instance($cm->id);
        require_capability('moodle/course:manageactivities', $modcontext);

// Duplicate the module.
        $newcourse = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

        $newcm = duplicate_modulebac($newcourse, $cm, '');

    }

    purge_all_caches();
    $actual_link = new moodle_url('/course/view.php?id=' . $cm->course);
    redirect($actual_link, '', null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->footer();
?>
<script>

    $('#bulkactform').on('change', '.checkall', function () {

        var id = this.id;
        var split_id = id.split("_");

        var postfix = "";
        for (var i = 1; i < split_id.length; i++) {
            // console.log("split : " + split_id[i]);
            if (postfix == "") {
                postfix = split_id[i];
            } else {
                postfix += "_" + split_id[i];
            }
        }

        var checked = false;
        if ($(this).is(":checked")) {
            checked = true;
            console.log("checked");
        } else {
            console.log("unchecked");
        }

        setTimeout(function () {
            console.log("id : #collapse_" + postfix);
            $('#collapse_' + postfix + " input[type=checkbox]").each(function () {

                $(this).prop('checked', checked); // Checks it

            });
        }, 1000);

    });

    $('form#bulkactform').submit(function (e) {

        if (!$('div.checkbox-group.required :checkbox:checked').length > 0) {
            e.preventDefault();
            alert('Please select one or more courses');
        }
    });

    function toggleChevron(e) {
        console.log(e.target);
        console.log($('a').target);

        $('a')
            .find("i.indicator")
            .toggleClass('fa-caret-down fa-caret-right');
    }

    function get_action_url(name, args) {

        var url = M.cfg.wwwroot + '/blocks/bulkactivity/' + name + '.php';
        if (args) {
            var q = [];
            for (var k in args) {
                q.push(k + '=' + encodeURIComponent(args[k]));
            }
            url += '?' + q.join('&');
        }
        return url;
    }

    $('.container').on('click', '.categorydiv', function (e) {
        // toggleChevron(e);
        var id = this.id;
        var catarray = id.split('_');
        if (catarray.length > 2) {
            $('.collapsesubcat' + catarray[1]).each(function () {
                var divid = $(this).attr('id');
                if (divid != 'collapse_' + catarray[1] + '_' + catarray[2]) {
                    $('.indicatorerro' + catarray[1]).toggleClass('fa-caret-down fa-caret-right');
                    $('#' + divid).removeClass('show');
                    var iid = divid.split('_');
                    $('.indicator#indicatorerro_' + iid[1] + iid[2]).removeClass('fa-caret-down').addClass('fa-caret-right');
                } else {
                    var iid = divid.split('_');
                    $('.indicator#indicatorerro_' + iid[1] + iid[2]).toggleClass('fa-caret-right fa-caret-down');
                }
            });
        } else {

            $('.collapsesubcat').each(function (e) {
                var divid = $(this).attr('id');

                if (divid != 'collapse_' + catarray[1]) {
                    var iid = divid.split('_');
                    $('#' + divid).removeClass('show');
                    $('#indicatorerro_' + iid[1]).removeClass('fa-caret-down').addClass('fa-caret-right');
                } else {
                    var iid = divid.split('_');

                    $('#indicatorerro_' + iid[1]).toggleClass('fa-caret-right fa-caret-down');
                }
            });
        }

        var idpost = catarray.length - 1;
        if (catarray.length > 2) {

            var coursediv = catarray[1] + '_' + catarray[2]
        } else {
            coursediv = catarray[1];
        }
        var categoryid = catarray[idpost];
        var currentcourseid = $('#currentcourseid').val();

        var checked = "";
        var split_id = id.split("_");
        if (split_id.length > 0) {
            var postfix = "";
            for (var i = 1; i < split_id.length; i++) {
                // console.log("split : " + split_id[i]);
                if (postfix == "") {
                    postfix = split_id[i];
                } else {
                    postfix += "_" + split_id[i];
                }
            }

            // Check checkall checkbox checked or not.
            if ($('#checkall_' + postfix).is(":checked")) {
                checked = "checked";
            }
        }
        $.ajax({
            url: get_action_url('ajax'),
            type: 'POST',
            data: {
                'request': 'getcagegorycourse',
                categoryid: categoryid,
                currentcourseid: currentcourseid,
                checked: checked
            },// Added checked: checked
            success: function (data) {
                $('#categorycourses_' + coursediv).html(data);

            }
        });

    });

</script>