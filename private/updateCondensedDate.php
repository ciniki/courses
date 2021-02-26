<?php
//
// Description
// ===========
// This function will update the condensed date string after dates have been changed.
//
// Arguments
// =========
// ciniki:
// tnid:         The ID of the tenant the request is for.
// method:              The requested public method.
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_courses_updateCondensedDate(&$ciniki, $tnid, $offering_id) {
    $strsql = "SELECT DATE_FORMAT(class_date, '%a %b %e, %Y') AS class_date, "
        . "DATE_FORMAT(class_date, '%W') AS dayofweek, "
        . "DATE_FORMAT(class_date, '%w') AS dayofweek_num, "
        . "DATE_FORMAT(class_date, '%a') AS dayofweek_short, "
        . "DATE_FORMAT(class_date, '%Y') AS year, "
        . "DATE_FORMAT(class_date, '%b') AS month, "
        . "DATE_FORMAT(class_date, '%e') AS day, "
//      . "DATE_FORMAT(class_date, '%u') AS ts, "
        . "UNIX_TIMESTAMP(class_date) AS ts, "
        . "TIME_FORMAT(start_time, '%l:%i %p') AS start_time, "
        . "TIME_FORMAT(end_time, '%l:%i %p') AS end_time "
        . "FROM ciniki_course_offering_classes "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $days = array(
            '0' => 0,
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            '6' => 0,
            '7' => 0,
            );
        $samedays = 'no';
        $sameday = 'yes';
        $sametime = 'yes';
        $consecutive = 'yes';
        $dates = $rc['rows'];
        $day_names = array();
        foreach($dates as $did => $date) {
//          $date = $date;
            if( $first_date == null ) {
                $first_date = $date;
            }
            if( $prev_dayofweek != '' && $prev_dayofweek != $date['dayofweek'] ) {
                $sameday = 'no';
            }
            if( $prev_time != '' && $prev_time != $date['start_time'] . ' - ' . $date['end_time']) {
                $sametime = 'no';
            }
            if( $last_date != null && $last_date['ts'] != ($date['ts']-86400) ) {
                $consecutive = 'no';
            }
            $days[$date['dayofweek_num']]++;
            if( !in_array($date['dayofweek_short'], $day_names) ) {
                $day_names[] = $date['dayofweek_short'];
            }
            $prev_dayofweek = $date['dayofweek'];
            $prev_time = $date['start_time'] . ' - ' . $date['end_time'];
            $last_date = $date;     
        }
        //
        // Check if days of the week are the same
        //
        $max = 0;
        $days_text = '';
        foreach($days as $d => $num) {
            // Skip any with 0 count
            if( $num == 0 ) {
                continue;
            }
            if( $max > 0 && $max != $num ) {
                // Not all the same
                $samedays = 'no';
                $days_text = '';
                break;
            } elseif( $max == $num ) {
                // More than one the same
                $samedays = 'yes';
            }
            if( $max == 0 ) {
                $max = $num;
            } 
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
        } elseif( $consecutive == 'yes' && $sametime == 'yes' ) {
            if( $first_date['year'] != $last_date['year'] ) {
                $condensed_date = $first_date['month'] . ' ' . $first_date['day'] . ', ' . $first_date['year'] 
                    . ' - ' . $last_date['month'] . ' ' . $last_date['day'] . ', ' . $last_date['year'];
            } else {
                $condensed_date = $first_date['month'] . ' ' . $first_date['day']
                    . ' - ' . $last_date['month'] . ' ' . $last_date['day'] . ', ' . $last_date['year'];
            }
            if( $sametime == 'yes' ) {
                $condensed_date .= ' ' . $prev_time;
            }
        } elseif( $samedays == 'yes' && $sametime == 'yes' ) {
            if( count($day_names) == 2 ) {
                $condensed_date = $day_names[0] . ' & ' . $day_names[1] . ' ' . $first_date['month'] . ' ' . $first_date['day'] 
                    . ' - ' . $last_date['month'] . ' ' . $last_date['day'];
            } else {
                $condensed_date = '';
                foreach($day_names as $name) {
                    $condensed_date .= ($condensed_date != '' ? ', ' : '') . $name;
                }
                //
                // Add the start and end date
                //
                $condensed_date .= ' ' . $first_date['month'] . ' ' . $first_date['day'] 
                    . ' - ' . $last_date['month'] . ' ' . $last_date['day'];
                // Check if dates can be reduced further
                $condensed_date = str_replace('Sun, Mon, Tue, Wed, Thu, Fri, Sat ', 'Sun - Sat ', $condensed_date);
                $condensed_date = str_replace('Sun, Mon, Tue, Wed, Thu, Fri ', 'Sun - Fri ', $condensed_date);
                $condensed_date = str_replace('Sun, Mon, Tue, Wed, Thu ', 'Sun - Thu ', $condensed_date);
                $condensed_date = str_replace('Sun, Mon, Tue, Wed ', 'Sun - Wed ', $condensed_date);
                $condensed_date = str_replace('Sun, Mon, Tue ', 'Sun - Tue ', $condensed_date);
                $condensed_date = str_replace('Mon, Tue, Wed, Thu, Fri, Sat ', 'Mon - Sat ', $condensed_date);
                $condensed_date = str_replace('Mon, Tue, Wed, Thu, Fri ', 'Mon - Fri ', $condensed_date);
                $condensed_date = str_replace('Mon, Tue, Wed, Thu ', 'Mon - Thu ', $condensed_date);
                $condensed_date = str_replace('Mon, Tue, Wed ', 'Mon - Wed ', $condensed_date);
                $condensed_date = str_replace('Tue, Wed, Thu, Fri, Sat ', 'Tue - Sat ', $condensed_date);
                $condensed_date = str_replace('Tue, Wed, Thu, Fri ', 'Tue - Fri ', $condensed_date);
                $condensed_date = str_replace('Tue, Wed, Thu ', 'Tue - Thu ', $condensed_date);
                $condensed_date = str_replace('Wed, Thu, Fri, Sat ', 'Wed - Sat ', $condensed_date);
                $condensed_date = str_replace('Wed, Thu, Fri ', 'Wed - Fri ', $condensed_date);
                $condensed_date = str_replace('Thu, Fri, Sat ', 'Thu - Sat ', $condensed_date);
                if( $sametime == 'yes' ) {
                    $condensed_date .= ' ' . $prev_time;
                }
            }
        } else {
            // 
            // Misc days
            //
            $condensed_date = '';
            if( $sametime == 'yes' ) {
                $prev_month = '';
                foreach($dates as $did => $date) {
                    if( $prev_month == $date['month'] ) {
                        $condensed_date .= ($condensed_date != '' ? ', ' : '') . $date['day'];
                    } else {
                        $condensed_date .= ($condensed_date != '' ? ', ' : '') . $date['month'] . ' ' . $date['day'];
                    }
                    $prev_month = $date['month'];
                }
                if( $first_date['year'] != $last_date['year'] ) {
                    $condensed_date .= ' ' . $first_date['year'] . '/' . $last_date['year'];
                } else {
                    $condensed_date .= ' ' . $first_date['year'];
                }
                $condensed_date .= ' ' . $prev_time;
            } else {
                foreach($dates as $did => $date) {
                    $condensed_date .= ($condensed_date != '' ? ', ' : '') . $date['month'] . ' ' . $date['day'] . ' (' . $date['start_time'] . ' - ' . $date['end_time'] . ')';
                } 
                if( $first_date['year'] != $last_date['year'] ) {
                    $condensed_date .= ' ' . $first_date['year'] . '/' . $last_date['year'];
                } else {
                    $condensed_date .= ' ' . $first_date['year'];
                }
            }
        }
    } else {
        $condensed_date = '';
    }
    
    $strsql = "UPDATE ciniki_course_offerings SET last_updated = UTC_TIMESTAMP()"
        . ", condensed_date = '" . ciniki_core_dbQuote($ciniki, $condensed_date) . "' "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.6', 'msg'=>'Unable to update offering date'));   
    }

    $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 'ciniki_course_history', $tnid, 2, 'ciniki_course_offerings', $offering_id, 'condensed_date', $condensed_date);
    $ciniki['syncqueue'][] = array('push'=>'ciniki.courses.offering',
        'args'=>array('id'=>$offering_id));

    return array('stat'=>'ok', 'condensed_date'=>$condensed_date);
}
?>
