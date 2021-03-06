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
 * Prints a particular instance of markers
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once('assignmarkers_form.php');

$courseid = required_param('cid', PARAM_INT); // cid = courseid

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($courseid);


$context = get_context_instance(CONTEXT_COURSE, $courseid);

/// Print the page header
//$PAGE->set_context($context);
$url = new moodle_url('/local/markers/assignmarkers.php', array('cid' => $courseid));
$PAGE->set_url($url);
$PAGE->set_title(format_string(get_string('assignmarkertitle', 'local_markers')));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('course');
$PAGE->navbar->add(get_string('allocatemarkers', 'local_markers'), $url);

//print_r(get_user_access_sitewide($USER->id));

require_capability('local/markers:markerenrolment', $context);

$data = new stdClass();

$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $courseid), '*', MUST_EXIST);
$roleassign = $DB->get_records('role_assignments', array('roleid' => $studentrole->id, 'contextid' => $context->id));
$ids = array();
foreach ($roleassign as $therole) {
	$ids[] = $therole->userid;
}
$thestudents = $DB->get_records_list('user', 'id', $ids, 'firstname ASC');
$students = array();

foreach ($thestudents as $stud) {

	$studentObject = new stdClass();
	$studentObject->id = $stud->id;
	$studentObject->firstname = $stud->firstname;
	$studentObject->lastname = $stud->lastname;
	$studentObject->email = $stud->email;
	$moremarkers = array();
	$markers_assign = $DB->get_records_select('markers_assign', 'courseid = ' . $courseid . ' AND studentid = ' . $stud->id . ' AND role <> \'Supervisor\' AND role <> \'Second Marker\'');
	if ($markers_assign != null) {
		foreach ($markers_assign as $obj) {
			$marker = $DB->get_record('user', array('id' => $obj->markerid), '*', MUST_EXIST);
			$markerObj = new stdClass();
			$markerObj->id = $obj->markerid;
			$markerObj->firstname = $marker->firstname;
			$markerObj->lastname = $marker->lastname;
			$markerObj->role = $obj->role;
			$markerObj->assignid = $obj->id;
			$moremarkers[] = $markerObj;
		}
	}
	$studentObject->othermarkers = $moremarkers;
	$students[$stud->id] = $studentObject;
}



$data->student = $students;

$editingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);
$noneditingteacher = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
$markeroles = $DB->get_records_select('role_assignments', 'contextid = ' . $context->id . ' AND (roleid = ' . $editingteacher->id . ' OR roleid = ' . $noneditingteacher->id . ')');
$markerids = array();
foreach ($markeroles as $therole) {
	$markerids[] = $therole->userid; 
}
$markersarray = $DB->get_records_list('user', 'id', $markerids, 'firstname ASC');

$markers = array(); // array of marker objects. to be used in the automatic allocation
$markersname = array(); // array of markers' names, to be used in the select lists
$markersname["-1"] = get_string('fromlist', 'local_markers'); 
foreach ($markersarray as $obj) {
	//$markers[$obj->id] = new marker($obj->id);
	$markers[] = new marker($obj->id);
	
	//$markersname[$obj->id] = $obj->firstname . ' ' . $obj->lastname . ' (id:' . $obj->id . ')';
	$markersname[$obj->id] = $obj->firstname . ' ' . $obj->lastname . ', ' . $obj->email;	
}



$data->markers = $markersname;

$data->cid = $courseid;

$admin = false;
$context = get_context_instance(CONTEXT_USER, $USER->id);
if (has_capability('local/markers:admin', $context))
	$admin = true;

$courses = $DB->get_records('course');
$available = array();
// find all courses that this user can assign markers (either teacher or admin)
foreach ($courses as $course) {
	// check if the user is admin
	if ($admin) {
		$available[$course->id] = $course;
	}
	else {
 		// check if the user is a teacher
 		$context = get_context_instance(CONTEXT_COURSE, $course->id);
 		if (has_capability('local/markers:editingteacher', $context)) {
			$available[$course->id] = $course;
 		}	
	}
}

$data->courses = $available;

$actionurl = $CFG->wwwroot . '/local/markers/assignmarkers.php?cid=' . $courseid;
$theform = new local_markers_assignmarkers_form($actionurl, $data);
$submit = $theform->get_data();


if ($theform->is_cancelled()) {
	redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
}
else if ($submit) {
	$assigns = array();
	
	if (isset($submit->automatic)){
			
		delete_assigns($courseid);	
	
		$allocations = automatic_allocation($thestudents, $markers, $courseid);
		foreach ($allocations as $allocation) {
			$assigns[] = $DB->insert_record('markers_assign', $allocation);
		}
		
	}
	else {	
		foreach ($thestudents as $stud) {
			$superstring = 'supervisor' . $stud->id;
			$supervisor = $submit->$superstring;
			// check if we have already submit a supervisor before
			$where = 'courseid = ' . $courseid . ' AND studentid = ' . $stud->id . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
			$oldsupervisor = $DB->get_record_select('markers_assign', $where);
			if ($oldsupervisor != null) {// just update
				$oldsupervisor->markerid = $supervisor;
				$DB->update_record('markers_assign', $oldsupervisor);
				$assigns[] = $oldsupervisor->id;
			}
			else {
				$assigns[] = $DB->insert_record('markers_assign', array ('courseid' => $courseid, 'studentid' => $stud->id, 'markerid' => $supervisor, 'role' => get_string('supervisor', 'local_markers')));
			}
		
			$secondstring = 'secondmarker' . $stud->id;
			$secondmarker = $submit->$secondstring;
			// check if we have already submit a second marker before
			$where = 'courseid = ' . $courseid . ' AND studentid = ' . $stud->id . ' AND role = \'' . get_string('secondmarker', 'local_markers') . '\'';  
			$oldsecondmarker = $DB->get_record_select('markers_assign', $where);
			if ($oldsecondmarker != null) {// just update
				$oldsecondmarker->markerid = $secondmarker;
				$DB->update_record('markers_assign', $oldsecondmarker);
				$assigns[] = $oldsecondmarker->id;
			}
			else {
				$assigns[] = $DB->insert_record('markers_assign', array ('courseid' => $courseid, 'studentid' => $stud->id, 'markerid' => $secondmarker, 'role' => get_string('secondmarker', 'local_markers')));		
			}		
		
		
			// Check for any other markers
			$i = 1;
			$otherstring = 'othermarker' . $stud->id . $i;
			while(isset($submit->$otherstring)) {
				$rolestr = 'role' . $stud->id . $i;
				$role = $submit->$rolestr;
				
				$assignidstr = 'otherassignid' . $stud->id . $i;
				$otherassignid = $submit->$assignidstr;
			
				if ($otherassignid != -1) {// just update
					$DB->update_record('markers_assign', array ('id' => $otherassignid, 'markerid' => $submit->$otherstring, 'role' => $role));
					$assigns[] = $otherassignid;
				}
				else {
					$assigns[] = $DB->insert_record('markers_assign', array ('courseid' => $courseid, 'studentid' => $stud->id, 'markerid' => $submit->$otherstring, 'role' => $role));
				}
	
				$i++;
				$otherstring = 'othermarker' . $stud->id . $i;
			}
		}
	}
		
	// if there are already assignments with multiple markers update markers_map table
	$assignments = $DB->get_records('assignment', array('course' => $courseid));
	if ($assignments != null) {
		foreach ($assignments as $ass) {
			$setup = $DB->get_record('markers_setup', array ('assignmentid' => $ass->id));
			if ($setup != null) {// then there are assignments with mult. markers
				foreach ($assigns as $assign) {
				
					/*
					$oldmap = $DB->get_records('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign));			
					if ($oldmap == null) {
						$DB->insert_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign, 'type' => 0, 'status' => 0, 'endmarkerid' => 0, 'altmarkerid' => 0, 'allowedit' => 1));
						$DB->insert_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign, 'type' => 1, 'status' => 0, 'endmarkerid' => 0, 'altmarkerid' => 0, 'allowedit' => 1));
					}*/
					
					$oldindividual = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign, 'type' => 0));
					if ($oldindividual == null) {
						$DB->insert_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign, 'type' => 0, 'status' => 0, 'endmarkerid' => 0, 'altmarkerid' => 0, 'allowedit' => 1));						
					}
					
					$oldagreed = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign, 'type' => 1));
					if ($oldagreed == null) {
						$DB->insert_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign, 'type' => 1, 'status' => 0, 'endmarkerid' => 0, 'altmarkerid' => 0, 'allowedit' => 1));						
					}
										
				}
			}
		}
	}
	redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
}
else {

	//print_r($theform);

	$allassign = true;
	// Setting the data of the form
	$setdata = new stdClass();
	foreach ($thestudents as $stud) {
		//$supervisor = $DB->get_record('markers_assign', array ('courseid' => $courseid, 'studentid' => $stud->id, 'role' => get_string('supervisor', 'local_markers')));
		$where = 'courseid = ' . $courseid . ' AND studentid = ' . $stud->id . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
		$supervisor = $DB->get_record_select('markers_assign', $where);
		if ($supervisor != null) {
			$superstr = 'supervisor' . $stud->id;
			$setdata->$superstr = $supervisor->markerid;
		}
		else {
			$allassign = false;
		}
		
		$where = 'courseid = ' . $courseid . ' AND studentid = ' . $stud->id . ' AND role = \'' . get_string('secondmarker', 'local_markers') . '\'';   
		$secondmarker = $DB->get_record_select('markers_assign', $where);
		if ($secondmarker != null) {
			$secondstr = 'secondmarker' . $stud->id;
			$setdata->$secondstr = $secondmarker->markerid;
		}
		else {
			$allassign = false;
		}
		
		$markers = $data->student[$stud->id]->othermarkers; 
		for ($j = 1; $j <= sizeof($markers); $j++) {
			$otherstr = 'othermarker' . $stud->id . $j;
			$setdata->$otherstr = $markers[$j-1]->id;
			$rolestr = 'role' . $stud->id . $j;
			$setdata->$rolestr = $markers[$j-1]->role;
			$otherassignidstr = 'otherassignid' . $stud->id . $j;
			$setdata->$otherassignidstr = $markers[$j-1]->assignid; 
		}	
	}
	$theform->set_data($setdata);
	
	// Output starts here
	echo $OUTPUT->header();


	// Replace the following lines with you own code
	echo $OUTPUT->heading(get_string('assignmarkersheading', 'local_markers'));

	if ($theform->hasErrors()) {  /*
		$str = 'Errors:' . '<br/>';
		$i = 1;
		foreach ($theform->getErrors() as $error) {
			$str = $str . $i . '. ' . $error . '<br/>';
			$i++;
		}
		echo "<font color='red'>";
		echo $OUTPUT->box($str, 'generalbox', 'errorbox');
		echo "</font>"; */
	}
	else {
		$errorstr = '';
		if (count($thestudents) <= 0) {
			$errorstr .= get_string('nostudents', 'local_markers');
		}
		
		if ((count($markersname)-1) < 2) {
			$errorstr .= (empty($errorstr) ? '' : '<br/>') . get_string('moremarkers', 'local_markers'); 
		}
		
		if (!empty($errorstr)) {
			echo "<font color='red'>";
			echo $OUTPUT->box($errorstr, 'generalbox', 'statusintro');
			echo "</font>";		
		}
		else {
			if ($allassign) {
				echo "<font color='green'>";
				echo $OUTPUT->box(get_string('allhavemarkers', 'local_markers'), 'generalbox', 'statusintro');
				echo "</font>";
			} 
			else {
				echo "<font color='red'>";
				echo $OUTPUT->box(get_string('notallhavemarkers', 'local_markers'), 'generalbox', 'statusintro');
				echo "</font>";
			}
		}
	}
	
	$theform->display();

	// Finish the page
	echo $OUTPUT->footer();
}
