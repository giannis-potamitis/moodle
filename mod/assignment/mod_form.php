<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_assignment_mod_form extends moodleform_mod {
    protected $_assignmentinstance = null;
    
		/* --------------- Giannis --------------------- */
		// Override it to add javascript validation
    function add_action_buttons($cancel=true, $submitlabel=null, $submit2label=null) {
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechangesanddisplay');
        }

        if (is_null($submit2label)) {
            $submit2label = get_string('savechangesandreturntocourse');
        }

        $mform = $this->_form;

        // elements in a row need a group
        $buttonarray = array();
				
				global $CFG;
				$catexists = is_readable($CFG->dirroot . '/local/cat/locallib.php'); 
					
        if ($submit2label !== false) {
        		if ($catexists) {
            	$buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label, array ('onclick' => 'return validate()'));
        		}
        		else {
        			$buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label);
        		}
        		
        }

        if ($submitlabel !== false) {
        	if ($catexists) {
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel, array ('onclick' => 'return validate()'));
        	}
        	else {
        		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        	}
        }

        if ($cancel) {
            $buttonarray[] = &$mform->createElement('cancel');
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }		
		/* --------------------------------------------- */    
    
    /* --------------- Giannis --------------------- */
    public function multiple_marking_elements() {
        global $COURSE, $CFG, $DB;
        $mform =& $this->_form;	

				//$mform->addElement('hidden', 'oldcatassignid');

        if ($this->_features->hasgrades) {

            $mform->addElement('header', 'modstandardgrade', get_string('grade'));
            $mform->addElement('checkbox', 'multiplecat', get_string('allowmultiplecat', 'local_cat'), null,array('onchange' => 'javascript: showHideTable(true)'));


						$html = '<script type="text/javascript">
										   
										  function reset(i, num) {
										  	var inputs = i.getElementsByTagName("input");

										   	var remove = null;
										   	var catid = null;
										   	var description = null;
										   	var weight = null;
										   	var maxgrade = null;
										   	var priority = null;
										   	
										   	for (k=0; k < inputs.length; k++) {
										   		
										   		var pattern = /remove\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   		}
										   			
										   		var pattern = /catid\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				catid = inputs[k];
										   		}	

										   		var pattern = /catdescription\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				description = inputs[k];
										   		}
										   		
										   		var pattern = /catweight\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				weight = inputs[k];
										   		}
										   		
										   		var pattern = /catmaxgrade\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				maxgrade = inputs[k];
										   		}
										   		
										   		var pattern = /priority\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				priority = inputs[k];
										   		}										   												   		
										   												   												   													   	
										   	
										   	}
										   	
										   	var outputs = i.getElementsByTagName("output");
										   	var virtualid = outputs[0];
										   	
										   	virtualid.name = "virtualid" + num; 
										   	priority.name = "priority" + num; 
										   	maxgrade.name = "catmaxgrade" + num;
										   	weight.name = "catweight" + num;
										   	description.name = "catdescription" + num;
										   	catid.name = "catid" + num;
										   	remove.name = "remove" + num;
										   	
										   	remove.value = 0;
										   	catid.value = -1; // denotes a new category
										   	description.value = "";
										   	weight.value = "";
										   	maxgrade.value = "";
										   	
										   	i.style.display = ""; // blank works like the block value on the table style
										   	
										   	updateCats();								  	
										  	 
										  }
										  
										  function resetSubtable(i, num) {
										  	var tables = i.getElementsByTagName("table");
										  	var stbl = tables[0];
										  	var rows = stbl.rows;
										  	
										  	// remove all the rows except the first one
										  	for (k=1; k < rows.length;) { // on each delete rows.length and index numbers are decremented
										  		stbl.deleteRow(k);					// thus we need to always delete the row at index 1
										  	}
										  	
										  	var first = rows[0];
												resetSubRow(first, num, 1, false); // not visible = removed
										  	stbl.id = "forcat" + num;
										  	stbl.style.display = "none";
										  	
										  } 
										   
										  function addCat() {
										   	if (!allowCat())
										   		return;
										   
										   	var table = document.getElementById("cat");
										   	var parent = table.getElementsByTagName("tr")[1].parentNode;
										   	var clone = table.getElementsByTagName("tr")[1].cloneNode(true);
										   	parent.appendChild(clone);
										   
										   	// add the subcat row
										   	var subclone = table.getElementsByTagName("tr")[2].cloneNode(true);
										   	parent.appendChild(subclone);
										   	
										   	// reset the values
										   	var rownum = (table.rows.length - 1) / 2;
										   	reset(clone, rownum);
										   	resetSubtable(subclone, rownum);
										   	 											  
										  } 
										  										   
										   function resetSubRow(row, num, len, visible) {

										  	var subid = null;
										  	var priority = null;
										  	var remove = null;
										  	var description = null;
										  	
										  	var inputs = row.getElementsByTagName("input");
										  	for (k=0; k < inputs.length; k++) {
										   		var pattern = /cat\d+subid\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				subid = inputs[k];
										   		}
										   		
										   		var pattern = /cat\d+priority\d+/;										   		
										   		if (pattern.test(inputs[k].name)) {
										   				priority = inputs[k];
										   		}
										   		
										   		var pattern = /cat\d+remove\d+/;										   		
										   		if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   		}
										   		
										   		var pattern = /cat\d+subdesc\d+/;										   		
										   		if (pattern.test(inputs[k].name)) {
										   				description = inputs[k];
										   		}										   													   												  	
										  	}
										  	
										  	var outputs = row.getElementsByTagName("output");
										  	var virtualid = outputs[0];
										  	
										  	virtualid.name = "cat" + num + "virtualsubid" + len;	
										  	subid.name = "cat" + num + "subid" + len;
										  	priority.name = "cat" + num + "priority" + len;
										  	remove.name = "cat" + num + "remove" + len;
										  	description.name = "cat" + num + "subdesc" + len;
										  	
										  	subid.value = -1;
										  	priority.value = 1;
										  	if (visible == true)
										  		remove.value = 0;
										  	else
										  		remove.value = 1;
										  	description.value = "";
										  	virtualid.value = len;										   
										   	
										   }
										   
										   function updateSubCats(stbl) {
										   	var rows = stbl.rows;
										   	var num = 1; // use also on priority										   
										   	for (i = 0; i < rows.length; i++) {
										   			
										   		var remove = null;
										   		var virtualid = null;
										   		var priority = null;
										   	
										   		var inputs = rows[i].getElementsByTagName("input");
										   		for (k = 0; k < inputs.length; k++) {
										   			
										   			var pattern = /cat\d+remove\d+/;
										   			if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   			}
										   			
										   			var pattern = /cat\d+priority\d+/;
										   			if (pattern.test(inputs[k].name)) {
										   				priority = inputs[k];
										   			}										   													   			
										   			
										   		}
										   		var outputs = rows[i].getElementsByTagName("output");
										   		virtualid = outputs[0];
										   		
										   		if (remove.value == 0) {
										   			virtualid.innerHTML = num;
										   			priority.value = num;
										   			num++;
										   		}
										   		else {
										   			priority.value = -1;
										   		} 
										   	}										   
										   	
										   }
										   
										   function addSubcat(parentRow) {
										   
										   	if (!allowCat())
										   		return;
										   												   
										   	var inputs = parentRow.getElementsByTagName("input");
										   	var pattern = /\d+\.?\d*/;
										   	var found = pattern.exec(inputs[0].name); // it should always found something
										   	var id = null;
										   	id = parseInt(found);
										   	var stbl = document.getElementById("forcat" + id);
										   	if (stbl.style.display == "none") {
										   		stbl.style.display = "block";
										   		resetSubRow(stbl.rows[0], id, 1, true);
										   		return;
										   	}
										   	
										   	var parent = stbl.getElementsByTagName("tr")[0].parentNode;
										   	var clone = stbl.getElementsByTagName("tr")[0].cloneNode(true);
										   	parent.appendChild(clone);
										   	
										   	var len = stbl.rows.length;
										   	clone.style.display = "";
										   	resetSubRow(clone, id, len, true);
										   	updateSubCats(stbl);										   	
										   	
										   }
										   
										   function allowCat() {
										   	var chkbox = document.getElementsByName("multiplecat");
										   	return chkbox[0].checked;
										   }
										   
										   function allremoved() {
										   	var table = document.getElementById("cat");
										   	var rows = table.rows;
													for (p = 1; p < rows.length; p++) {
														if (p % 2 == 0)
															continue;
													
														var inputs = rows[p].getElementsByTagName("input");
										   			for (k=0; k<inputs.length; k++) {
										   				var pattern = /remove\d+/;
										   				if (pattern.test(inputs[k].name)) {
										   					if (inputs[k].value == 0)
										   						return 0;
										   				}
										   			}
										   		}
										   		return 1;									   	
										   }
										   
										   function showHideTable(add) {
										   	var table = document.getElementById("cat");
										   	//alert("size: " + table.size + "width: " + table.width);										   	
										   	if (allowCat()) {
										   		if (allremoved() == 1 && add)
										   			addCat();
										   		table.style.display = "block";
										   	}
										   	else {
										   		table.style.display = "none";
										   	}
										   	
										   	//table.style.size = "100%";
										   	//table.setAttribute("width", "100%");										   	
										   }
										   
										   function findCatRows(row, up) {
										 		var table = document.getElementById("cat");
										 		var rows = table.rows;
										 		
										 		var found = false;
										 		var previousCat = null;
										 		var previousSubCat = null;
										 		var nextCat = null;
										 		var nextSubCat = null;
										 		var thisSubCat = null;
										 		
										 		for (k = 1; k < rows.length; k++) {
										 			if (rows[k] == row) {
										 				found = true;
										 			}
										 			else {
										 				if (found && thisSubCat == null) {
										 					thisSubCat = rows[k];
										 				}
										 				else if (found && thisSubCat != null) {
										 					if (nextCat == null && (k % 2 != 0)) {
										 						nextCat = rows[k];
										 					}
										 					
										 					if (nextSubCat == null && (k % 2 == 0)) {
										 						nextSubCat = rows[k];
										 					}
										 				}
										 			}
										 			
										 			if (!found) {
										 				if (k % 2 == 0)
										 					previousSubCat = rows[k];
										 				else
										 					previousCat = rows[k];
										 			}
										 		}
										 		
										 		if (up) {
										 			if (previousCat == null || previousSubCat == null)
										 				return null;
										 			obj = new Object();
										 			obj.upCat = previousCat;
										 			obj.upSubcat = previousSubCat;
										 			obj.thisSubcat = thisSubCat;
										 			return obj;
										 		}
										 		else {
										 			if (nextCat == null || nextSubCat == null)
										 				return null;
										 			obj = new Object();
										 			obj.downCat = nextCat;
										 			obj.downSubcat = nextSubCat;
										 			obj.thisSubcat = thisSubCat;
										 			return obj;										 			
										 		}
										 		
										   }
										   
										   
										   function moveCatUp(i) {
										   	obj = findCatRows(i, true);
										   	if (obj == null) {
													alert("Moving further up is not allowed!");
													return;										   	
										   	}
										   	// move up is same as moving the upper row down
										   	moveCatDown(obj.upCat);
										   }
										   
										   function moveCatDown(i) {										   
										   	obj = findCatRows(i, false);
												if (obj == null) {
													alert("Moving further down is not allowed!");
													return;
												}
												
												//var table = document.getElementById("cat");
												exchange(i.rowIndex, obj.downCat.rowIndex, "cat");
												exchange(obj.thisSubcat.rowIndex, obj.downSubcat.rowIndex, "cat");
												updateCats();
										   }
										   										   										   
							   			// taken from http://www.terrill.ca/sorting/switching_table_rows.php and modified a bit
							   			
											function exchange(i, j, tableID)
											{
												//alert("replace: " + j + " with: " + i); // i = 9 j = 7
												var oTable = document.getElementById(tableID);
												//var trs = oTable.tBodies[0].getElementsByTagName("tr");
												var trs = oTable.rows;
	
												if(i == j+1) {
													oTable.tBodies[0].insertBefore(trs[i], trs[j]);
												} else if(j == i+1) {
													oTable.tBodies[0].insertBefore(trs[j], trs[i]);
												} else {
													var tmpNode = oTable.tBodies[0].replaceChild(trs[i], trs[j]);
													//var tmpNode = oTable.tBodies[0].replaceChild(tmpNode, trs[i]);													
													//var temp = trs[j];
													//var tmpNode = oTable.rows[0].parentNode.replaceChild(trs[i], trs[j]);
													if(typeof(trs[i]) != "undefined") {
														oTable.tBodies[0].insertBefore(
														tmpNode, 
														trs[i]);
													} else {
														oTable.appendChild(tmpNode);
													}
												}
											}
										   
										  function updateCats() {
										   	var table = document.getElementById("cat");
										   	var rows = table.rows;
										   	var num = 1; // use also on priority										   
										   	for (i = 1; i < rows.length; i++) {// skip the headers rows
										   		if (i % 2 == 0) // skip the sub-category rows
										   			continue;
										   			
										   		var remove = null;
										   		var virtualid = null;
										   		var priority = null;
										   	
										   		var inputs = rows[i].getElementsByTagName("input");
										   		for (k = 0; k < inputs.length; k++) {
										   			
										   			var pattern = /remove\d+/;
										   			if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   			}
										   			
										   			var pattern = /priority\d+/;
										   			if (pattern.test(inputs[k].name)) {
										   				priority = inputs[k];
										   			}										   													   			
										   			
										   		}
										   		var outputs = rows[i].getElementsByTagName("output");
										   		virtualid = outputs[0];
										   		
										   		if (remove.value == 0) {
										   			virtualid.innerHTML = num;
										   			priority.value = num;
										   			num++;
										   		}
										   		else {
										   			priority.value = -1;
										   		} 
										   	}										   
										   }
										   
										   function deleteCat(i) {
										   
										   	var inputs = i.getElementsByTagName("input");
										   	for (k=0; k<inputs.length; k++) {
										   		var pattern = /remove\d+/;
										   		if (pattern.test(inputs[k].name)) {
										   			
										   			var answer = confirm("You are about to remove that category.  Any sub-categories will also be removed. Press ok if you really want to.");
										   			if (!answer)
										   				return;
										   			
										   			// hide sub-categories table first
										   			var pattern = /\d+\.?\d*/;
										   			var found = pattern.exec(inputs[k].name); // it should always found something
										   			var id = null;
										   			id = parseInt(found);
										   			var stbl = document.getElementById("forcat" + id);
										   			stbl.style.display = "none"; // hide also sub-categories table										   			
										   			
										   			// set remove to 1	
										   			inputs[k].value = 1;
										   			i.style.display = "none";
										   			updateCats();
										   
										   			if (allremoved() == 1) {
										   				var chkbox = document.getElementsByName("multiplecat");
										   				chkbox[0].checked = 0; // uncheck the box
										   				showHideTable(false);
										   				document.getElementsByName("grade")[0].disabled = false;
										   				document.getElementsByName("gradecat")[0].disabled = false;										   														   				
										   			}										   			
										   			
										   			
										   			return;
										   		}
										   	}
										   }
										   
										   function deleteSubcat(i) {
										   	var inputs = i.getElementsByTagName("input");
										   	for (k=0; k<inputs.length; k++) {
										   		var pattern = /cat\d+remove\d+/;
										   		if (pattern.test(inputs[k].name)) {
										   			
										   			var answer = confirm("You are about to remove that subcategory. Press ok if you really want to.");
										   			if (!answer)
										   				return;										   			
										   			
										   			// set remove to 1	
										   			inputs[k].value = 1;
										   			i.style.display = "none";
										   			var stbl = i.parentNode;
										   			updateSubCats(stbl);
										   			return;
										   		}										   	
										   		
										   	}
										   }
										   
										   function findSubcatRows(tbl, row, up) {
										 		var rows = tbl.rows;
										 		
										 		var found = false;
										 		var previous = null;
										 		var next = null;
										 		
										 		for (k = 0; k < rows.length; k++) {
										 			if (rows[k] == row) {
										 				found = true;
										 			}
										 			else {
										 				if (found && next == null) {
										 					next = rows[k];
										 				}
										 			}
										 			
										 			if (!found) {
														previous = rows[k];
										 			}
										 		}
										 		
										 		if (up) {
										 			if (previous == null)
										 				return null;
										 			obj = new Object();
										 			obj.up = previous;
										 			return obj;
										 		}
										 		else {
										 			if (next == null)
										 				return null;
										 			obj = new Object();
										 			obj.down = next;
										 			return obj;										 			
										 		}
										 		
										  }
										  
										  function moveSubcatDown(i) {
										  	var tbl = i.parentNode.parentNode;
										  	obj = findSubcatRows(tbl, i, false);
												if (obj == null) {
													alert("Moving further down is not allowed!");
													return;
												}
												
												exchange(i.rowIndex, obj.down.rowIndex, tbl.id);
												updateSubCats(tbl);										  	
										  }
										  
										  function moveSubcatUp(i) {
										  	var tbl = i.parentNode.parentNode;
										  	obj = findSubcatRows(tbl, i, true);
												if (obj == null) {
													alert("Moving further up is not allowed!");
													return;
												}
												
										   	// move up is same as moving the upper row down
										   	moveSubcatDown(obj.up);												
																						  	
										  }
										  
										  function validate() {
										  	if (!allowCat())
										  		return true;
										  		
										  	var tbl = document.getElementById("cat");
										  	var rows = tbl.rows;
										  	var valid = 0;
										  	for (i = 1; i < rows.length; i++) {
													if (i % 2 != 0) {
														valid += validateCat(rows[i]);
													}
													else {
														valid += validateSubCat(rows[i]);												
													}
												}
												
												if (valid == 0)
										  		return true;
										  	else
										  		return false;
										  }
										  
											function validateCat(row) {
											
												var inputs = row.getElementsByTagName("input");
										   	var remove = null;
										   	var description = null;
										   	var weight = null;
										   	var maxgrade = null;
										   	
										   	for (k=0; k < inputs.length; k++) {
										   		
										   		var pattern = /remove\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   		}
										   			
										   		var pattern = /catdescription\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				description = inputs[k];
										   		}
										   		
										   		var pattern = /catweight\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				weight = inputs[k];
										   		}
										   		
										   		var pattern = /catmaxgrade\d+/;	
										   		if (pattern.test(inputs[k].name)) {
										   				maxgrade = inputs[k];
										   		}										   											   												   												   												   													   	
										   	
										   	}
										   	
										   	if (remove.value == 1)
										   		return 0;
										   		
										   	var tds = row.getElementsByTagName("td");
										   	var err = tds[tds.length - 1]; // error column should be the last one
										   	err.innerHTML = ""; // reset innerHTML
										   	var errfound = 0;
										   	
										   	if (description.value == "") {
										   		err.innerHTML += "&nbsp;Description cannot be empty</br>";
										   		errfound = 1;
										   	}
										   	
										   	if (weight.value == "") {
										   		err.innerHTML += "&nbsp;Weight cannot be empty</br>";
										   		errfound = 1;										   	
										   	}
										   	else {
										   		if (!is_positive_int(weight.value)) {
										   			err.innerHTML += "&nbsp;Weight must be a positive integer</br>";
										   			errfound = 1;											   		
										   		}
										   	}
										   	
										   	if (maxgrade.value == "") {
										   		err.innerHTML += "&nbsp;Maximum grade cannot be empty</br>";
										   		errfound = 1;										   	
										   	}
										   	else {
										   		if (!is_positive_int(maxgrade.value)) {
										   			err.innerHTML += "&nbsp;Maximum grade must be a positive integer</br>";
										   			errfound = 1;											   		
										   		}
										   	}
										   	
										   	return errfound;
										   												   											  		
										  }
										  
										  function validateIndividual(row) {
												var inputs = row.getElementsByTagName("input");
										   	var remove = null;
										   	var description = null;
										   	
										   	for (k=0; k < inputs.length; k++) {
										   		
										   		var pattern = /cat\d+remove\d+/;										   		
										   		if (pattern.test(inputs[k].name)) {
										   				remove = inputs[k];
										   		}
										   		
										   		var pattern = /cat\d+subdesc\d+/;										   		
										   		if (pattern.test(inputs[k].name)) {
										   				description = inputs[k];
										   		}										   											   												   												   												   													   	
										   	
										   	}
										   	
										   	if (remove.value == 1)
										   		return 0;
										   		
										   	var tds = row.getElementsByTagName("td");
										   	var err = tds[tds.length - 1]; // error column should be the last one
										   	err.innerHTML = ""; // reset innerHTML
										   	var errfound = 0;
										   	
										   	if (description.value == "") {
										   		err.innerHTML += "&nbsp;Cannot be empty</br>";
										   		errfound = 1;
										   	}
										   	
										   	return errfound;										  
										  }
										  
											function validateSubCat(row) {
											
												var tbl = row.getElementsByTagName("table")[0];
												var rows = tbl.rows;
												var valid = 0;
												for (z = 0; z < rows.length; z++) {
													valid += validateIndividual(rows[z]);
												}
												return valid;				   											  		
										  }
										  
										  // taken from http://www.inventpartners.com/content/javascript_is_int
										  // and alter a little bit
											function is_positive_int(value){
  											if(!isNaN(value) && (parseFloat(value) == parseInt(value)) && parseInt(value) > 0){
      										return true;
 	 											} else {
      										return false;
  											}
											}										  										  																					  										  
										  										   
										   
										 </script>';
										 
						$cat = $DB->get_record('cat', array('assignmentid' => $this->_instance));
						
						$display = 'block';
						if ($cat == null) {
							$display = 'none';							
						}
						else {
            	$mform->setDefault('multiplecat', 1);							
						}										 
										 
						$html .= '<style type="text/css">
						
												table.tbl td.uncolored {
													background-color: #FFFFFF;
													width: 100%;
												}
																		
												table.tbl td {
													padding: 0px;
													background-color: #E8E8E8;												
												}
												

												table.subtable td {
													background-color: #b0c4de;												
												}
												
												.subtable {

													#position: relative;
													#right: -50px;
													#width: 89%;
													#width: 65%;
													width: 85%;
													margin-left: 15%;
													margin-right: 15%;
													
												}
												
												
												.tcellstart {
													border-left: 1px solid #0000FF;
													border-top: 1px solid #0000FF;
													width: 1%;
												}
												
												.tcellmiddle {
													border-top: 1px solid #0000FF;
													width: 10%;													
												}
												
												.tcelldescription {
													border-top: 1px solid #0000FF;
													#border-left: 1px solid #0000FF;
													#border-right: 1px solid #0000FF;													
													#width: 28%;
													width: 22%;											
												}												
												
												.tcellend {
													border-right: 1px solid #0000FF;
													border-top: 1px solid #0000FF;
													width: 7%												
												}
												
												.scellboth {
													border-left: 1px solid #0000FF;
													border-right: 1px solid #0000FF;
													border-bottom: 1px solid #0000FF;													
												}
												

												
												.tbl {
													position: relative;
													right: -50px;
													top: 10px;
													#width: 70%;
													width: 90%;
													display:' . $display . ';
													
												}
												
												.desctext {
													margin-left: -19%;
													position: relative;
												}
												
												.subdesctext {
													margin-left: -15%;
													position: relative;												
												}
												
												.subcatnum {
													width: 23%;
												}
												
												table.subtable td.suberror {
													width: 28%;
													background-color: #E8E8E8;													
												}
												
												.subicons {
													width: 10%;												
												}
												
												.scelldesc {
													width: 25%;
												}												
												
												th {
													background-color: #FFFFFF;												
												}
						
											</style>';
						
						$url = $CFG->wwwroot . '/pix/t/addgreen.gif';

								
						$html .= '<table id="cat" name="cattable" border="0" class="tbl">';
						$html .= '<tr>';
						$html .= '<th>' . get_string('virtualID', 'local_cat') . '</th>';						
						$html .= '<th>' . get_string('description', 'local_cat') . '</th>';
						$html .= '<th>' . get_string('weight', 'local_cat') . '</th>';
						$html .= '<th>' . get_string('maxgrade', 'local_cat') . '</th>';
						$title = get_string('addncat', 'local_cat');
						//$html .= '<th><a href="javascript: addCategory()" title="' . $title . '"><img src="' . $url . '" alt="Add Category"></a></th>';
						$html .= '<th><img src="' . $url . '" alt="Add Category" onclick="addCat()" style="cursor: pointer" title="' . $title . '"/></th>';
						$html .= '<th></th>'; // validation column	
						$mform->addElement('html', $html);												
						
						
					
						$categories = null;
						if ($cat != null) {
							$categories = $DB->get_records('cat_category', array('catid' => $cat->id), 'priority ASC');
						}							
						$till = ($categories == null) ? 1 : count($categories);
						$first = true;
						//$i = 1;
						for ($i = 1; $i <= $till; $i++) {
							$subcats = null;
							if ($categories != null) {
								if ($first) {
									$category = reset($categories);
									$first = false;
								}
								else {
									$category = next($categories);
								}
								
								$subcats = $DB->get_records('cat_subcat', array('categoryid' => $category->id), 'priority ASC');
							}
						
							// row
							$html = '<tr><td class="tcellstart">';
							$html .= '<center><b><output name="virtualid' . $i . '" >' . $i . '</output></b></center>';
							$html .= '</td>';
							$mform->addElement('html', $html);
							
							
							$html = '<td  class="tcelldescription">';
							$mform->addElement('html', $html);			
							$mform->addElement('text', 'catdescription' . $i, '', array('style' => 'text-align:center', 'title' => get_string('description', 'local_cat'), 'size' => '25',
																																					'class' => 'desctext'));
														
							$mform->disabledif('catdescription' . $i, 'multiplecat');
							$mform->addElement('html', '</td><td class="tcellmiddle">');
						
							$mform->addElement('text', 'catweight' . $i, '', array('size' => '5', 'style' => 'text-align:center', 'title' => get_string('weight', 'local_cat')));
							$mform->disabledif('catweight' . $i, 'multiplecat');						
							$mform->addElement('html', '</td><td class="tcellmiddle">');	
											
							$mform->addElement('text', 'catmaxgrade' . $i, '', array('size' => '5', 'style' => 'text-align:center', 'title' => get_string('maxgrade', 'local_cat')));
							$mform->disabledif('catmaxgrade' . $i, 'multiplecat');								
						
							$html = '</td>';
							// add the add subcategory symbol
							
							$html .= '<td class="tcellend">';		
							
							//$url = $CFG->wwwroot . '/pix/giannis/add-blue.gif';
							$url = $CFG->wwwroot . '/local/cat/pix/add-blue.gif';							
							$title = get_string('addnsubcat', 'local_cat');
							//$html .= '<a href="javascript: addSubcat(' . $i . ')" title="' . $title . '">  <img src="' . $url . '" alt="Add new Sub-Category"></a>';
							$html .= '  <img src="' . $url . '" alt="Add Sub-Category" onclick="addSubcat(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';								
							$mform->addElement('html', $html);
							
							// the down priority
							$url = $CFG->wwwroot . '/pix/t/down.gif';
							$title = get_string('movedown', 'local_cat');
							//$html = '<a href="javascript: moveCatDown(' . $i . ')" title="' . $title . '">  <img src="' . $url . '" alt="Move Down"></a>';
							$html = '  <img src="' . $url . '" alt="Move Down" onclick="moveCatDown(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';								
							$mform->addElement('html', $html);
							
							// the up priority
							$url = $CFG->wwwroot . '/pix/t/up.gif';
							$title = get_string('moveup', 'local_cat');
							//$html = '<a href="javascript: moveCatUp(' . $i . ')" title="' . $title . '">  <img src="' . $url . '" alt="Move Up"></a>';
							$html = '  <img src="' . $url . '" alt="Move Up" onclick="moveCatUp(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';								
							$mform->addElement('html', $html);
							
							// the delete symbol
							//$url = $CFG->wwwroot . '/pix/giannis/delete-red.gif';
							$url = $CFG->wwwroot . '/local/cat/pix/delete-red.gif';							
							$title = get_string('deletecat', 'local_cat');
							//$html = '<a href="#" onclick"javascript: deleteCat(); return false" title="' . $title . '">  <img src="' . $url . '" alt="Move Up"></a>';
							$html = '  <img src="' . $url . '" alt="Delete Category" onclick="deleteCat(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';	
							$mform->addElement('html', $html);														
																														
							// add hidden elements
							/*
							$mform->addElement('hidden', 'catid' . $i);
							$mform->addElement('hidden', 'remove' . $i);
							$mform->setDefault('remove' . $i, 0); // not removed at the beginning							
							$mform->addElement('hidden', 'priority' . $i);	
							*/
							
							$html = '<input name="remove' . $i .'" type="hidden" value=0 />';
							$mform->addElement('html', $html);	

							if ($categories != null) {
								$mform->setDefault('catdescription' . $i, $category->description);
								$mform->setDefault('catweight' . $i, round($category->weight, 2));
								$mform->setDefault('catmaxgrade' . $i, round($category->maxgrade, 2));
								//$mform->setDefault('catid' . $i, $category->id);
								//$mform->setDefault('priority' . $i, $category->priority);									
								$hcatid = $category->id;
								$hpriority = $category->priority;
							}
							else {
								//$mform->setDefault('catid' . $i, -1); // a new category
								//$mform->setDefault('priority' . $i, 1);
								$hcatid = -1; // a new category
								$hpriority = 1;									
							}
							
							$html = '<input name="catid' . $i .'" type="hidden" value=' . $hcatid . ' />';
							$html .= '<input name="priority' . $i .'" type="hidden" value=' . $hpriority . ' />';							
							$mform->addElement('html', $html);															
														
							$html = '</td>';
							
							$html .= '<td style="color: red" class="uncolored"></td>'; // the validation column 
							
							$html .= '</tr>';				
							$mform->addElement('html', $html);							
							

							
							// add the sub-categories table (SUB-TABLE)
							$subdisplay = ($subcats == null) ? "none"  : "block";
							$id = "forcat" . $i;
							$html = '<tr>';
							//$html .= '<td></td>'; // the first column is empty
							$html .= '<td colspan="5" class="scellboth">';
							$html .= '<table class="subtable" id="'. $id .'" align="center" style="display: ' .$subdisplay .'">';
							$till2 = ($subcats == null) ? 1 : count($subcats);
							
							$sfirst = true;
							for ($j = 1; $j <= $till2; $j++) {
							
								if ($subcats != null) {
									if ($sfirst) {
										$scat = reset($subcats);
										$sfirst = false;
									}
									else {
										$scat = next($subcats);
									}
								
								}
							
								$html .= '<tr><td class="subcatnum">';
								$html .= '<b>' . get_string('subcat', 'local_cat') . ' ' . '<output name="cat' . $i . 'virtualsubid' . $j . '" >' . $j . '</output>' . '</b></td>';
								$html .= '<td class="scelldesc">';
								$mform->addElement('html', $html);
								$mform->addElement('text', 'cat' . $i . 'subdesc' . $j, '', array('title' => get_string('subcatdes', 'local_cat'), 'size' => '15', 'class' => 'subdesctext'));
								$mform->disabledif('cat' . $i . 'subdesc1', 'multiplecat');			
								$html = '</td>';
								$html .= '<td class="subicons">'; // new column for the hidden fields and icons
								$mform->addElement('html', $html);								
								
								// add hidden elements								
								if ($subcats != null) {
									//$mform->setDefault('cat' . $i . 'subid' . $j, $scat->id);
									//$mform->setDefault('cat' . $i . 'priority' . $j, $scat->priority);
									$hsubcatid = $scat->id;
									$hsubpriority = $scat->priority;
									$hsubremove = 0;
									$mform->setDefault('cat' . $i . 'subdesc' . $j, $scat->description);
									
								}
								else {
									//$mform->setDefault('cat' . $i . 'subid' . $j, -1);
									//$mform->setDefault('cat' . $i . 'priority' . $j, 1);
									$hsubcatid = -1;
									$hsubpriority = 1;
									$hsubremove = 1; // sub categories start with remove set
									$mform->setDefault('cat' . $i . 'subdesc' . $j, '');																																			
								}
								
								$html = '<input name="cat' . $i . 'subid' . $j . '" type="hidden" value=' . $hsubcatid . ' />';
								$html .= '<input name="cat' . $i . 'priority' . $j . '" type="hidden" value=' . $hsubpriority . ' />';									
								$html .= '<input name="cat' . $i . 'remove' . $j . '" type="hidden" value=' . $hsubremove  . ' />';								
								$mform->addElement('html', $html);
								
								// add the symbols
								
								// move down
								//$url = $CFG->wwwroot . '/pix/giannis/down.gif';
								$url = $CFG->wwwroot . '/local/cat/pix/down.gif';								
								$title = get_string('moveup', 'local_cat');
								$html = '  <img src="' . $url . '" alt="Move Down" onclick="moveSubcatDown(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';	
								$mform->addElement('html', $html);								
								
								// move up
								//$url = $CFG->wwwroot . '/pix/giannis/up.gif';
								$url = $CFG->wwwroot . '/local/cat/pix/up.gif';								
								$title = get_string('moveup', 'local_cat');
								$html = '  <img src="' . $url . '" alt="Move Up" onclick="moveSubcatUp(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';	
								$mform->addElement('html', $html);
								
								// delete
								//$url = $CFG->wwwroot . '/pix/giannis/delete.gif';
								$url = $CFG->wwwroot . '/local/cat/pix/delete.gif';
								$title = get_string('deletesubcat', 'local_cat');
								$html = '  <img src="' . $url . '" alt="Delete Subcategory" onclick="deleteSubcat(this.parentNode.parentNode)" style="cursor: pointer" title="' . $title . '"/>';	
								$mform->addElement('html', $html);																			
								
															

								/*
								$mform->addElement('hidden', 'cat' . $i . 'subid' . $j);
								$mform->addElement('hidden', 'cat' . $i . 'remove' . $j);
								$mform->setDefault('cat' . $i . 'remove' . $j, 0); // not removed at the beginning							
								$mform->addElement('hidden', 'cat' . $i . 'priority' . $j); */
								
																
							
								$html = '</td>';
								$html .= '<td class="suberror" style="color: red"></td>'; // error column
								$html .= '</tr>';
								
							}
								
							$html .= '</table>';
							$html .='</td></tr>';
							
							$mform->addElement('html', $html);	
													
						}
						
						$html = '</table>';						
						$mform->addElement('html', $html);				
						
						$html = '<br/>';
						$mform->addElement('html', $html);
						
						// Add the ranks selection
						$sql = 'SELECT * FROM ' . $CFG->prefix . 'cat_rank' . ' WHERE courseid = 0 OR courseid = ' . $COURSE->id;
						$ranks = $DB->get_records_sql($sql);
						$select = array();
						$select[0] = '--no selection--';
						foreach ($ranks as $rank) {
							$elements = $DB->get_records('cat_ranks', array('rankid' => $rank->id));
							$string = $rank->name . ' (';
							$first = true;
							foreach ($elements as $el) {
								if ($first) {
									$first = false;
								}
								else {
									$string .= ', ';
								}
								$string .= $el->description;
							}
							$string .= ')';
							$select[$rank->id] = $string;
						}
						$mform->addElement('select', 'rank', get_string('subcatranks', 'local_cat'), $select);						
						$mform->disabledif('rank', 'multiplecat');
						if ($cat) {
							$mform->setDefault('rank', $cat->rankid);
						}
						else {
							$mform->setDefault('rank', 0);
						}
						
						// OR CHOOSE
						$html = '<br/><br/><p>' . get_string('or_cap', 'local_cat') . ' ' . get_string('choose_cap', 'local_cat') . ': ' . '</p>';
						$mform->addElement('html', $html);					
						
						
						
            //if supports grades and grades arent being handled via ratings
            if (!$this->_features->rating) {
                $mform->addElement('modgrade', 'grade', get_string('grade'), true);
                $mform->setDefault('grade', 100);
                $mform->disabledif('grade', 'multiplecat', 'checked');
            }

            if ($this->_features->gradecat) {
                $categories = grade_get_categories_menu($COURSE->id, $this->_outcomesused);
                $mform->addElement('select', 'gradecat', get_string('gradecategory', 'grades'), $categories);
                $mform->disabledif('gradecat', 'multiplecat', 'checked');
            }
        }
    }

   /* ---------------------------------------------------- */

    function definition() {
        global $CFG, $DB;
        $mform =& $this->_form;

        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            if($ass = $DB->get_record('assignment', array('id'=>$this->_instance))) {
                $type = $ass->assignmenttype;
            } else {
                print_error('invalidassignment', 'assignment');
            }
        } else {
            $type = required_param('type', PARAM_ALPHA);
        }
        $mform->addElement('hidden', 'assignmenttype', $type);
        $mform->setType('assignmenttype', PARAM_ALPHA);
        $mform->setDefault('assignmenttype', $type);
        $mform->addElement('hidden', 'type', $type);
        $mform->setType('type', PARAM_ALPHA);
        $mform->setDefault('type', $type);

        require_once($CFG->dirroot.'/mod/assignment/type/'.$type.'/assignment.class.php');
        $assignmentclass = 'assignment_'.$type;
        $assignmentinstance = new $assignmentclass();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

//        $mform->addElement('static', 'statictype', get_string('assignmenttype', 'assignment'), get_string('type'.$type,'assignment'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'assignment'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('description', 'assignment'));

        $mform->addElement('date_time_selector', 'timeavailable', get_string('availabledate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timeavailable', time());
        $mform->addElement('date_time_selector', 'timedue', get_string('duedate', 'assignment'), array('optional'=>true));
        $mform->setDefault('timedue', time()+7*24*3600);

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'preventlate', get_string('preventlate', 'assignment'), $ynoptions);
        $mform->setDefault('preventlate', 0);

        // hack to support pluggable assignment type titles
        if (get_string_manager()->string_exists('type'.$type, 'assignment')) {
            $typetitle = get_string('type'.$type, 'assignment');
        } else {
            $typetitle  = get_string('type'.$type, 'assignment_'.$type);
        }

				/* -------- Giannis -------------- */
				
				if (is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
        	$this->multiple_marking_elements(); // for multiple categories
        }
        else {
        	$this->standard_grading_coursemodule_elements(); // WAS: only this line without if block
        }
        
				if (is_readable($CFG->dirroot . '/local/markers/locallib.php')) {        
        	$this->marker_elements(); // for multiple markers
        }
				/* ------------------------------- */

        $mform->addElement('header', 'typedesc', $typetitle);

        $assignmentinstance->setup_elements($mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
       	
    }
    
    /* -------------- Giannis ------------ */
    function marker_elements() {
    	global $DB;
   		$mform =& $this->_form;
      $mform->addElement('header', 'modmarkers', get_string('assformMarkers', 'local_markers'));
      $mform->addElement('checkbox', 'multiplemarkers', get_string('allowmultiplemarkers', 'local_markers'));
     
    	$setup = $DB->get_record('markers_setup', array('assignmentid' => $this->_instance));
    	
    	if ($setup == null)
      	$mform->setDefault('multiplemarkers', 0);
    	else
      	$mform->setDefault('multiplemarkers', 1);   
    }
    /* ----------------------------------- */

    // Needed by plugin assignment types if they include a filemanager element in the settings form
    function has_instance() {
        return ($this->_instance != NULL);
    }

    // Needed by plugin assignment types if they include a filemanager element in the settings form
    function get_context() {
        return $this->context;
    }

    protected function get_assignment_instance() {
        global $CFG, $DB;

        if ($this->_assignmentinstance) {
            return $this->_assignmentinstance;
        }
        if (!empty($this->_instance)) {
            if($ass = $DB->get_record('assignment', array('id'=>$this->_instance))) {
                $type = $ass->assignmenttype;
            } else {
                print_error('invalidassignment', 'assignment');
            }
        } else {
            $type = required_param('type', PARAM_ALPHA);
        }
        require_once($CFG->dirroot.'/mod/assignment/type/'.$type.'/assignment.class.php');
        $assignmentclass = 'assignment_'.$type;
        $this->assignmentinstance = new $assignmentclass();
        return $this->assignmentinstance;
    }


    function data_preprocessing(&$default_values) {
        // Allow plugin assignment types to preprocess form data (needed if they include any filemanager elements)
        $this->get_assignment_instance()->form_data_preprocessing($default_values, $this);
    }


    function validation($data, $files) {
        // Allow plugin assignment types to do any extra validation after the form has been submitted
        $errors = parent::validation($data, $files);
        $errors = array_merge($errors, $this->get_assignment_instance()->form_validation($data, $files));        
        return $errors;
    }
}

