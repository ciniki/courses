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

    //
    // Get the current offering details
    //
    $strsql = "SELECT condensed_date, "
        . "start_date, "
        . "end_date, "
        . "DATE_FORMAT(start_date, '%a %b %e, %Y') AS start_formatted, "
        . "DATE_FORMAT(start_date, '%W') AS start_dayofweek, "
        . "DATE_FORMAT(start_date, '%w') AS start_dayofweek_num, "
        . "DATE_FORMAT(start_date, '%a') AS start_dayofweek_short, "
        . "DATE_FORMAT(start_date, '%Y') AS start_year, "
        . "DATE_FORMAT(start_date, '%b') AS start_month, "
        . "DATE_FORMAT(start_date, '%e') AS start_day, "
        . "DATE_FORMAT(start_date, '%e') AS start_ts, "
        . "DATE_FORMAT(end_date, '%a %b %e, %Y') AS end_formatted, "
        . "DATE_FORMAT(end_date, '%W') AS end_dayofweek, "
        . "DATE_FORMAT(end_date, '%w') AS end_dayofweek_num, "
        . "DATE_FORMAT(end_date, '%a') AS end_dayofweek_short, "
        . "DATE_FORMAT(end_date, '%Y') AS end_year, "
        . "DATE_FORMAT(end_date, '%b') AS end_month, "
        . "DATE_FORMAT(end_date, '%e') AS end_day, "
        . "DATE_FORMAT(end_date, '%e') AS end_ts "
        . "FROM ciniki_course_offerings "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.148', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.149', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['offering'];

    //
    // Get the dates
    //
    $strsql = "SELECT class_date AS org_class_date, "
        . "DATE_FORMAT(class_date, '%a %b %e, %Y') AS class_date, "
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
        $first_date = $rc['date'];
        $last_date = $rc['date'];
    } elseif( isset($rc['rows']) && count($rc['rows']) > 1 ) {
        $dates = $rc['rows'];
    } elseif( $offering['start_date'] == $offering['end_date'] ) {
        $condensed_date = $offering['start_formatted'];
    } elseif( $offering['start_year'] == $offering['end_year'] ) {
        $condensed_date = $offering['start_dayofweek_short'] . ' ' . $offering['start_month'] . ' ' . $offering['start_day']
            . ' - '
            . $offering['end_dayofweek_short'] . ' ' . $offering['end_month'] . ' ' . $offering['end_day']
            . ', ' . $offering['end_year'];
    } else {
        $condensed_date = $offering['start_formatted'] . ' - ' . $offering['end_formatted'];
        
/*        $dates = array(
            array(
                'org_class_date' => $offering['start_date'],
                'class_date' => $offering['start_formatted'],
                'dayofweek' => $offering['start_dayofweek'],
                'dayofweek_num' => $offering['start_dayofweek_num'],
                'dayofweek_short' => $offering['start_dayofweek_short'],
                'year' => $offering['start_year'],
                'month' => $offering['start_month'],
                'day' => $offering['start_day'],
                'start_time' => '12:00 AM',
                'end_time' => '12:00 AM',
                'ts' => $offering['start_ts'],
                ),
            array(
                'org_class_date' => $offering['end_date'],
                'class_date' => $offering['end_formatted'],
                'dayofweek' => $offering['end_dayofweek'],
                'dayofweek_num' => $offering['end_dayofweek_num'],
                'dayofweek_short' => $offering['end_dayofweek_short'],
                'year' => $offering['end_year'],
                'month' => $offering['end_month'],
                'day' => $offering['end_day'],
                'start_time' => '12:00 AM',
                'end_time' => '12:00 AM',
                'ts' => $offering['end_ts'],
                ),
            ); */
    }

    if( isset($dates) ) {
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
        //
        // Reduce time
        //
        $prev_time = preg_replace('/([0-9]*[0-9]:[0-9][0-9]) PM( - [0-9]*[0-9]:[0-9][0-9] PM)/', "$1$2", $prev_time);
        $prev_time = preg_replace('/([0-9]*[0-9]:[0-9][0-9]) AM( - [0-9]*[0-9]:[0-9][0-9] AM)/', "$1$2", $prev_time);
        
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
    }

    $update_args = array();
    if( $condensed_date != $offering['condensed_date'] ) {
        $update_args['condensed_date'] = $condensed_date;
    } 
    if( isset($first_date) && $first_date['org_class_date'] != $offering['start_date'] ) {
        $update_args['start_date'] = $first_date['org_class_date'];
    } 
    if( isset($last_date) && $last_date['org_class_date'] != $offering['end_date'] ) {
        $update_args['end_date'] = $last_date['org_class_date'];
    }

    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering', $offering_id, $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.150', 'msg'=>'Unable to update the offering', 'err'=>$rc['err']));
        }
    }
   
   /*
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
    */

    return array('stat'=>'ok', 'condensed_date'=>$condensed_date);
}
?>
