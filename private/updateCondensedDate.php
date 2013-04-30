<?php
//
// Description
// ===========
// This function will update the condensed date string after dates have been changed.
//
// Arguments
// =========
// ciniki:
// business_id: 		The ID of the business the request is for.
// method:				The requested public method.
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_courses_updateCondensedDate(&$ciniki, $business_id, $offering_id) {
	$strsql = "SELECT DATE_FORMAT(class_date, '%a %b %e, %Y') AS class_date, "
		. "DATE_FORMAT(class_date, '%W') AS dayofweek, "
		. "DATE_FORMAT(class_date, '%Y') AS year, "
		. "DATE_FORMAT(class_date, '%b') AS month, "
		. "DATE_FORMAT(class_date, '%e') AS day, "
		. "TIME_FORMAT(start_time, '%l:%i %p') AS start_time, "
		. "TIME_FORMAT(end_time, '%l:%i %p') AS end_time "
		. "FROM ciniki_course_offering_classes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "ORDER BY ciniki_course_offering_classes.class_date "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'date');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['date']) ) {
		$condensed_date = $rc['date']['class_date'] . ' ' . $rc['date']['start_time'] . ' - ' . $rc['date']['end_time'];
	} elseif( isset($rc['rows']) && count($rc['rows']) > 1 ) {
		$first_date = null;
		$last_date = null;
		$prev_time = '';
		$prev_dayofweek = '';
		$sameday = 'yes';
		$sametime = 'yes';
		foreach($rc['rows'] as $did => $date) {
			$date = $date;
			if( $first_date == null ) {
				$first_date = $date;
			}
			if( $prev_dayofweek != '' && $prev_dayofweek != $date['dayofweek'] ) {
				$sameday = 'no';
			}
			if( $prev_time != '' && $prev_time != $date['start_time'] . ' - ' . $date['end_time']) {
				$sametime = 'no';
			}
			$prev_dayofweek = $date['dayofweek'];
			$prev_time = $date['start_time'] . ' - ' . $date['end_time'];
			$last_date = $date;		
		}
		$condensed_date = '';
		if( $sameday == 'yes' && $sametime == 'yes' ) {
			if( $first_date['year'] != $last_date['year'] ) {
				$condensed_date = $first_date['month'] . ' ' . $first_date['day'] . ', ' . $first_date['year'] 
					. ' - ' . $last_date['month'] . ' ' . $last_date['day'] . ', ' . $last_date['year'];
			} else {
				$condensed_date = $first_date['month'] . ' ' . $first_date['day']
					. ' - ' . $last_date['month'] . ' ' . $last_date['day'] . ', ' . $last_date['year'];
			}
			$condensed_date .= ' ' . $prev_dayofweek . 's ' . $prev_time;
		} else {
			// Unable to condense the dates
			$condensed_date = '';
		}
	} else {
		$condensed_date = '';
	}
	
	$strsql = "UPDATE ciniki_course_offerings SET last_updated = UTC_TIMESTAMP()"
		. ", condensed_date = '" . ciniki_core_dbQuote($ciniki, $condensed_date) . "' "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1254', 'msg'=>'Unable to update offering date'));	
	}

	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 
		'ciniki_course_history', $business_id, 
		2, 'ciniki_course_offerings', $offering_id, 'condensed_date', $condensed_date);
	$ciniki['syncqueue'][] = array('push'=>'ciniki.courses.offering',
		'args'=>array('id'=>$offering_id));

	return array('stat'=>'ok', 'condensed_date'=>$condensed_date);
}
?>
