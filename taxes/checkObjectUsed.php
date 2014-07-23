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
function ciniki_courses_taxes_checkObjectUsed($ciniki, $modules, $business_id, $object, $object_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	// Set the default to not used
	$used = 'no';
	$count = 0;
	$msg = '';

	//
	// There are only tax types in this module
	//
	if( $object == 'ciniki.taxes.type' ) {
		//
		// Check the event prices
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_course_offering_prices "
			. "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg = "There are $count course offering" . ($rc['num']['items']==1?'':'s') . " still using this tax type.";
		}
	}

	return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>