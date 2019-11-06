<?php
require_once(dirname(dirname(__FILE__)).'../../config.php');
global $PAGE,$CFG, $DB, $OUTPUT;
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string("pluginname","block_bulkactivity"));
$PAGE->set_heading(get_string("pluginname","block_bulkactivity"));
$PAGE->navbar->ignore_active();
$PAGE->requires->jquery();
$PAGE->set_url($CFG->wwwroot . "/blocks/bulkactivity/createbulkactivity.php");
$PAGE->requires->css( new moodle_url('https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'));

echo $OUTPUT->header();
if(!isset($_POST['createduplicate'])) {
    global $USER, $DB;
    $modid = $_REQUEST["cba"];
    $cmid = required_param('cba', PARAM_INT);

    $sectionreturn = optional_param('sr', null, PARAM_INT);
    $sql="select id,name from {course_categories} where coursecount >0 && visible =1";
    $categories =$DB->get_records_sql($sql);

    $cm = get_coursemodule_from_id('', $modid, 0, true, MUST_EXIST);

    function courselist($category,$course){
        global $DB;

        $sql = "select id,fullname from {course} where id!=$course && visible = 1 &&  category = $category";
        $courses = $DB->get_records_sql($sql);
        $courselist = "";
        foreach($courses as $course){
            $courselist .='<div class="col-md-6 custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="course[]" value="'.$course->id.'" id="customCheck'.$course->id.'">
            <label class="custom-control-label" for="customCheck'.$course->id.'">'.$course->fullname.'</label>
        </div>';
        }
        return $courselist;

    }
  ?>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <div class="container">
        <h3><?=get_string('courselistheader','block_bulkactivity')?></h3>
        <form action="createbulkactivities.php" method="post">
        <?php

        foreach($categories as $category) {
            echo '<div class="row">';
            echo ' <div class="card col-md-12" style="padding: 10px;"><div class="form-row">
          <div class="card-header" style="width: 100%;">
                    '.$category->name.'
          </div></div>
          <div class="card-body">
          <div class="form-row">
            '.courselist($category->id,$cm->course).'
          </div>
        </div>
        
         </div>';
       echo '</div>';
        }
        ?>
            <input type="hidden" name="cmid" value="<?=$cmid?>">
            <input type="submit" class="btn btn-primary" value="<?=get_string("submit")?>" name="createduplicate">
        </form>
    </div>


<?php

}

if(isset($_POST['createduplicate'])) {

    $modid = $_POST['cmid'];




    function duplicate_modulebac($course, $cm)
    {



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


        // Now a bit hacky part follows - we try to get the cmid of the newly
        // restored copy of the module.
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
if($newcmid){

    $sql = "select * from {course_sections} where course = $course->id";
    $csections = $DB->get_records_sql($sql);
    $empsection = array();
    foreach($csections as $section){

         $sequencearray =    explode(",",$section->sequence);
         $empsection[] = $section->id;
         foreach($sequencearray as $key=>$arr){
             if($arr==$newcmid){
                 unset($sequencearray[$key]);
                $newsec =  implode(",",$sequencearray);
                $update = new stdClass();
                $update->id  = $section->id;
                $update->sequence  = $newsec;
                $DB->update_record('course_sections',$update);
             }

         }



    }

    if(max($empsection)){
        $sectionid = max($empsection);
        $sescq = $DB->get_record('course_sections',array('id'=>$sectionid),'sequence');
        $oldsec = $sescq->sequence;
        $oldsecarr = explode(",",$oldsec);
        $oldsecarr[] = $newcmid;
        $newsecarr = implode(",",$oldsecarr);


        $updatesec =new stdClass();
        $updatesec->id = $sectionid;
        $updatesec->sequence  = $newsecarr;
        $DB->update_record('course_sections',$updatesec);

        $newcm = new stdClass();
        $newcm->id = $newcmid;
        $newcm->section = $sectionid;
        $DB->update_record('course_modules',$newcm);
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
            //$newname = get_string('duplicatedmodule', 'moodle', $newcm->name);
            //$DB->set_field($cm->modname, 'name', $newname, ['id' => $newcm->instance]);
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


    foreach($_POST['course'] as $course_id) {

        $cm = get_coursemodule_from_id('', $modid, 0, true, MUST_EXIST);

        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $modcontext = context_module::instance($cm->id);
        require_capability('moodle/course:manageactivities', $modcontext);

// Duplicate the module.
        $newcourse = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);


        $newcm = duplicate_modulebac($newcourse, $cm,'');
//redirect(course_get_url($course, $cm->sectionnum, array('sr' => $sectionreturn)));
    }

    purge_all_caches();
    $actual_link = new moodle_url('/course/view.php?id='.$cm->course);
    redirect($actual_link, '', null, \core\output\notification::NOTIFY_SUCCESS);
//    redirect($actual_link, 'There is no Assignment in the Course');
}
echo $OUTPUT->footer();