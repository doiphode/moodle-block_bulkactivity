<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');
class compactivity_form extends moodleform
{
    function definition()
    {
        global $DB, $USER,$CFG,$OUTPUT;
        $this->_form = new MoodleQuickForm($this->_formname, 'post', '');
        $categories = $this->_customdata['categories'];
        $courseid = $this->_customdata['courseid'];
        $cmid = $this->_customdata['cmid'];


        $html = '<div id="blkh3">';
        $html .='<div id="accordion" class="accordion panel-group">

				<div class="panel-body" >';
        foreach ($categories as $category) {
            $html .=' <div class="panel panel-default" >
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
        $html .='</div></div>';
        $mform =& $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'currentcourseid','',array('id'=>'currentcourseid'));
        $mform->setType('currentcourseid', PARAM_INT);
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('header','displayinfo', get_string('courselistheader', 'block_bulkactivity'));
        $mform->addElement('select', 'copyactivityto', get_string('copyactivityto', 'block_bulkactivity'), array(0=>'Section 0',1=>'Section 1',2=>'Last Section'));
        $mform->setType('copyactivityto', PARAM_RAW);
        $mform->addRule('copyactivityto', null, 'required', null, 'client');
        $mform->addElement('html', $html);
        $mform->addElement('submit', 'submin_but', get_string('submit'));
        $mform->setDefault( 'currentcourseid', $courseid);
        $mform->setDefault( 'cmid', $cmid);
    }
}
?>
