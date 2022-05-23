<?php
//
// Description
// -----------
// This function will process the juror voting for forms.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_accountOfferingProcess(&$ciniki, $tnid, &$request, $item) {

    $blocks = array();

/*    if( !isset($item['ref']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Request error, please contact us for help.."
            )));
    }
*/
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "You must be logged in to view this program."
            )));
    }

    if( !isset($request['uri_split'][3]) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Invalid request, no offering requested."
            )));
    }
    $course_permalink = $request['uri_split'][2];
    $offering_permalink = $request['uri_split'][3];

    $base_url = $request['base_url'] . '/' . join('/', $request['uri_split']);

    //
    // Load the offering
    //
    $strsql = "SELECT courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "courses.primary_image_id AS course_image_id, "
        . "courses.paid_content AS course_paid_content, "
        . "offerings.id AS offering_id, "
        . "offerings.name AS offering_name, "
        . "offerings.permalink AS offering_permalink, "
        . "offerings.primary_image_id AS offering_image_id, "
        . "offerings.paid_content AS offering_paid_content "
        . "FROM ciniki_course_offering_registrations AS registrations "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "registrations.offering_id = offerings.id "
            . "AND offerings.status = 10 "  // Active
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND (courses.flags&0x40) = 0x40 "     // Paid content
            . "AND (courses.status = 30 OR courses.status = 70 ) "  // Active or private
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ("
            . "registrations.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "offerings.end_date > NOW() " // Open offering
            . "OR ((courses.flags&0x10) = 0x10) " // Timeless course
            . ") "  
        . "AND courses.permalink = '" . ciniki_core_dbQuote($ciniki, $course_permalink) . "' "
        . "AND offerings.permalink = '" . ciniki_core_dbQuote($ciniki, $offering_permalink) . "' "
        . "ORDER BY courses.name, offerings.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.198', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.199', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['offering'];

    //
    // Load any files for the offering
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x08) ) {
        $offering['files'] = array();
        $offering['paid_content_files'] = array();
        
        //
        // Load course files
        //
        $strsql = "SELECT files.id, "
            . "files.uuid, "
            . "files.name, "
            . "files.permalink, "
            . "files.webflags, "
            . "files.extension "
            . "FROM ciniki_course_files AS files "
            . "WHERE files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND files.course_id = '" . ciniki_core_dbQuote($ciniki, $offering['course_id']) . "' "
            . "AND (files.webflags&0x01) = 0x01 "    // Visible
            . "ORDER BY files.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'name', 'permalink', 'webflags', 'extension')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            foreach($rc['files'] as $file) {
                $file['permalink'] = 'p/' . $file['permalink'] . '.' . $file['extension'];
                if( ($file['webflags']&0x10) == 0x10 ) {
                    $offering['paid_content_files'][$file['permalink']] = $file;
                } else {
                    $offering['files'][$file['permalink']] = $file;
                }
            }
        }

        //
        // Load offering files
        //
        $strsql = "SELECT files.id, "
            . "files.uuid, "
            . "files.name, "
            . "files.permalink, "
            . "files.webflags, "
            . "files.extension "
            . "FROM ciniki_course_offering_files AS files "
            . "WHERE files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND files.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['offering_id']) . "' "
            . "AND (files.webflags&0x01) = 0x01 "    // Visible
            . "ORDER BY files.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'name', 'permalink', 'webflags', 'extension')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            foreach($rc['files'] as $file) {
                $file['permalink'] = 's/' . $file['permalink'] . '.' . $file['extension'];
                if( ($file['webflags']&0x10) == 0x10 ) {
                    $offering['paid_content_files'][$file['permalink']] = $file;
                } else {
                    $offering['files'][$file['permalink']] = $file;
                }
            }
        }

        //
        // Check for a file download request
        //
        if( isset($request['args']['download']) ) {
            $file_permalink = $request['args']['download'];
            if( isset($offering['files'][$file_permalink]) ) {
                $file = $offering['files'][$file_permalink];
            }
            elseif( isset($offering['paid_content_files'][$file_permalink]) ) {
                $file = $offering['paid_content_files'][$file_permalink];
            } 
            else {
                $blocks[] = array(
                    'type' => 'msg', 
                    'class' => 'error',
                    'content' => 'Unable to find requested file',
                    );
            }
            if( isset($file) ) {
                //
                // Get the tenant storage directory
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
                $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $storage_filename = $rc['storage_dir'] . '/ciniki.courses/files/' . $file['uuid'][0] . '/' . $file['uuid'];

                //
                // Get the storage filename
                //
                if( !file_exists($storage_filename) ) {
                    $blocks[] = array(
                        'type' => 'msg', 
                        'class' => 'error',
                        'content' => 'Unable to find requested file',
                        );
                } else {
                    $binary_content = file_get_contents($storage_filename);    
                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    if( $file['extension'] == 'pdf' ) {
                        header('Content-Type: application/pdf');
                    }
                    header('Content-Length: ' . strlen($binary_content));
                    header('Cache-Control: max-age=0');

                    print $binary_content;
                    exit;
                }
            }
        } 
    }

    //
    // Load and process course images
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x0200) ) {
        //
        // Load course images
        //
        $strsql = "SELECT images.id, "
            . "images.name, "
            . "images.permalink, "
            . "images.flags, "
            . "images.image_id, "
            . "images.description "
            . "FROM ciniki_course_images AS images "
            . "WHERE images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND images.course_id = '" . ciniki_core_dbQuote($ciniki, $offering['course_id']) . "' "
            . "AND (images.flags&0x01) = 0x01 "    // Visible
            . "ORDER BY images.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'image_id', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            foreach($rc['images'] as $image) {
                if( ($image['flags']&0x10) == 0x10 ) {
                    $offering['paid_content_images'][$image['permalink']] = $image;
                } else {
                    $offering['images'][$image['permalink']] = $image;
                }
            }
        }
        //
        // Load offering images
        //
        $strsql = "SELECT images.id, "
            . "images.name, "
            . "images.permalink, "
            . "images.flags, "
            . "images.image_id, "
            . "images.description "
            . "FROM ciniki_course_offering_images AS images "
            . "WHERE images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND images.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['offering_id']) . "' "
            . "AND (images.flags&0x01) = 0x01 "    // Visible
            . "ORDER BY images.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'image_id', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            foreach($rc['images'] as $image) {
                if( ($image['flags']&0x10) == 0x10 ) {
                    $offering['paid_content_images'][$image['permalink']] = $image;
                } else {
                    $offering['images'][$image['permalink']] = $image;
                }
            }
        }

        if( isset($request['args']['image']) ) {
            $image_permalink = $request['args']['image'];

            //
            // Process next/prev
            //
            $prev_url = null;
            $next_url = null;
            $first_image = null;
            $last_image = null;
            foreach($offering['paid_content_images'] as $image) {
                if( $image['permalink'] == $image_permalink ) {
                    $selected_image = $image;
                }
                if( $first_image == null ) {
                    $first_image = $image;
                }
                if( $last_image != null && isset($selected_image) && $image['permalink'] == $selected_image['permalink'] ) {  
                    $prev_url = $np_base_url . '?image=' . $last_image['permalink'];
                }
                if( $last_image != null && isset($selected_image) && $last_image['permalink'] == $selected_image['permalink'] ) {
                    $next_url = $np_base_url . '?image=' . $image['permalink'];
                }
                $last_image = $image;
            }
            if( $next_url == null && $last_image != null && count($offering['paid_content_images']) > 1 ) {
                $next_url = $np_base_url . '?image=' . $first_image['permalink'];
            }
            if( $prev_url == null && $last_image != null && count($offering['paid_content_images']) > 1 ) {
                $prev_url = $np_base_url . '?image=' . $last_image['permalink'];
            }

            if( isset($selected_image) ) {
                $blocks[] = array(
                    'type' => 'image',
                    'class' => 'limit-width',
                    'image-id' => $selected_image['image_id'],
                    'title' => $selected_image['title'],
                    'prev' => $prev_url,
                    'next' => $next_url,
                    'content' => $selected_image['description'],
                    );
            }
            return array('stat'=>'ok', 'clear'=>'yes', 'stop'=>'yes', 'blocks'=>$blocks);
        }
    }
      
  
    //
    // Create the blocks for the page
    //
    $blocks[] = array(
        'type' => 'contentphoto',
        'class' => 'limit-width',
        'title' => $offering['course_name'] . ' - ' . $offering['offering_name'],
        'content' => $offering['offering_paid_content'] != '' ? $offering['offering_paid_content'] : $offering['course_paid_content'],
        );

    //
    // Check for any paid content files
    //
    if( isset($offering['paid_content_files']) ) {
        $content = '';
        foreach($offering['paid_content_files'] as $f) {
            $content .= "<a target='_blank' href='" . $base_url . "?download=" . $f['permalink'] . "'>"
                . $f['name'] 
                . "</a>\n";
        }
        $blocks[] = array(
            'type' => 'text',
            'class' => 'limit-width',
            'title' => 'Files',
            'content' => $content,
            );
    }

    //
    // Check for any paid images
    //


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
