<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_web_registrationDetails($ciniki, $settings, $business_id) {

	$strsql = "SELECT detail_key, detail_value "
		. "FROM ciniki_course_settings "
		. "WHERE ciniki_course_settings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (detail_key = 'course-registration-details' "
			. "OR detail_key = 'course-registration-more-details') "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$registration_details = '';
	$registration_more_details = '';
	foreach($rc['rows'] as $rid => $row ) {
		if( $row['detail_key'] == 'course-registration-details' ) {
			$registration_details = $row['detail_value'];
		}
		elseif( $row['detail_key'] == 'course-registration-more-details' ) {
			$registration_more_details = $row['detail_value'];
		}
	}
	if( $registration_details == '' ) {
		return array('stat'=>'ok', 'registration'=>array('details'=>'', 'more-details'=>'', 'files'=>array()));
	}

	$strsql = "SELECT id, name, extension, permalink, description "
		. "FROM ciniki_course_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND type = 1 "
		. "AND (webflags&0x01) = 0 "
		. "ORDER BY name "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
		array('container'=>'files', 'fname'=>'name', 'name'=>'file',
			'fields'=>array('id', 'name', 'extension', 'permalink')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['files']) ) {
		return array('stat'=>'ok', 'registration'=>array('details'=>$registration_details, 'more-details'=>$registration_more_details, 'files'=>$rc['files']));
	}

	return array('stat'=>'ok', 'registration'=>array('details'=>$registration_details, 'more-details'=>$registration_more_details, 'files'=>array()));
}
?>
