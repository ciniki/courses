<?php
//
// Description
// -----------
// This function will generate the courses page for the business.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_courses_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.courses']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.80', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );

    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'download' && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'fileDownload');
        $rc = ciniki_courses_web_fileDownload($ciniki, $business_id, $args['uri_split'][1]);
        if( $rc['stat'] == 'ok' ) {
            return array('stat'=>'ok', 'download'=>$rc['file']);
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.74', 'msg'=>'The file you requested does not exist.'));
    }

    //
    // Store the content created by the page
    // Make sure everything gets generated ok before returning the content
    //
    $content = '';
    $page_content = '';
    $page_title = 'Courses';
    if( $page['title'] == '' ) {
        $page['title'] = 'Courses';
    }
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>$page['title'], 'url'=>$args['base_url']);
    }

    //
    // FIXME: Check if anything has changed, and if not load from cache
    //

    //
    // Check if there should be a submenu
    //
    $first_course_type = '';
    if( isset($ciniki['business']['modules']['ciniki.courses']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseTypes');
        $rc = ciniki_courses_web_courseTypes($ciniki, $settings, $business_id);
        if( $rc['stat'] == 'ok' ) {
            if( count($rc['types']) > 1 ) {
                foreach($rc['types'] as $cid => $type) {
                    if( $first_course_type == '' ) {
                        $first_course_type = $type['name'];
                    }
                    if( $type != '' ) {
                        $page['submenu'][$cid] = array('name'=>$type['name'], 'url'=>$args['base_url'] . "/" . urlencode($type['name']));
                    }
                }
            } elseif( count($rc['types']) == 1 ) {
                $first_type = array_pop($rc['types']);
                $first_course_type = $first_type['name'];
            }
        }
        if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x02) == 0x02 ) {
            $page['submenu']['instructors'] = array('name'=>'Instructors', 'url'=>$args['base_url'] . '/instructors');
        }
        if( isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
            $page['submenu']['registration'] = array('name'=>'Registration', 'url'=>$args['base_url'] . '/registration');
        }
    }

    //
    // Check if we are to display the gallery image for an members
    //
    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'instructor' 
        && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' 
        ) {
        $instructor_permalink = $args['uri_split'][1];
        $gallery_url = $args['base_url'] . "/instructor/" . $instructor_permalink . "/gallery";

        //
        // Load the member to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorDetails');
        $rc = ciniki_courses_web_instructorDetails($ciniki, $settings, $business_id, $instructor_permalink);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.75', 'msg'=>"I'm sorry, but we can't seem to find the image you requested.", $rc['err']));
        }
        $instructor = $rc['instructor'];

        $page['title'] = $instructor['name'];
        $page['breadcrumbs'][] = array('name'=>'Instructors', 'url'=>$args['base_url'] . '/instructors');
        $page['breadcrumbs'][] = array('name'=>$instructor['name'], 'url'=>$args['base_url'] . '/instructor/' . $instructor_permalink);

        //
        // Check if image from instructor gallery
        //
        if( isset($args['uri_split'][2]) && $args['uri_split'][2] == 'gallery' && isset($args['uri_split'][3]) && $args['uri_split'][3] != '' ) {
            if( !isset($instructor['images']) || count($instructor['images']) < 1 ) {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.76', 'msg'=>"I'm sorry, but we can't seem to find the image you requested."));
            }

            $image_permalink = $args['uri_split'][3];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
            $rc = ciniki_web_galleryFindNextPrev($ciniki, $instructor['images'], $image_permalink);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['img'] == NULL ) {
                $page['blocks'][] = array('type'=>'message', 'section'=>'instructor-image', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
            } else {
                if( $rc['img']['title'] != '' ) {
                    $page['title'] .= ' - ' . $rc['img']['title'];
                }
                $page_title = $instructor['name'] . ' - ' . $rc['img']['title'];
                $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$args['base_url'] . '/gallery/' . $image_permalink);
                if( $rc['img']['title'] != '' ) {
                    $page['title'] .= ' - ' . $rc['img']['title'];
                }
                $block = array('type'=>'galleryimage', 'section'=>'instructor-image', 'primary'=>'yes', 'image'=>$rc['img']);
                if( $rc['prev'] != null ) {
                    $block['prev'] = array('url'=>$args['base_url'] . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                }
                if( $rc['next'] != null ) {
                    $block['next'] = array('url'=>$args['base_url'] . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                }
                $page['blocks'][] = $block;
            }
        } 
        //
        // Setup the blocks to display the instructor
        //
        else {
            if( isset($instructor['image_id']) && $instructor['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 
                    'image_id'=>$instructor['image_id'],'title'=>$instructor['name'], 'caption'=>'');
            }

            $content = '';
            if( isset($instructor['full_bio']) && $instructor['full_bio'] != '' ) {
                $content = $instructor['full_bio'];
            } else {
                $content = $instructor['short_bio'];
            }
            
            if( isset($instructor['url']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
                $rc = ciniki_web_processURL($ciniki, $instructor['url']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $url = $rc['url'];
                $display_url = $rc['display'];
            } else {
                $url = '';
            }
            if( $url != '' ) {
                $content .= "\n\nWebsite: <a class='members-url' target='_blank' href='" . $url . "' title='" . $instructor['name'] . "'>" . $display_url . "</a>";
            }
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

            if( isset($instructor['images']) && count($instructor['images']) > 0 ) {
                $page['blocks'][] = array('type'=>'gallery', 'section'=>'gallery', 'title'=>'Additional Images', 
                    'base_url'=>$args['base_url'] . "/instructor/" . $instructor['permalink'] . "/gallery",
                    'images'=>$instructor['images']);
            }
        }
    } 

    //
    // Check if we are to display a list of instructors
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'instructors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorList');
        $rc = ciniki_courses_web_instructorList($ciniki, $settings, $business_id, 0, 'cilist');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $page['breadcrumbs'][] = array('name'=>'Instructors', 'url'=>$args['base_url'] . '/instructors');
        $page['blocks'][] = array('type'=>'cilist', 'notitle'=>'yes', 'section'=>'instructors', 'base_url'=>$args['base_url'] . '/instructor', 'categories'=>$rc['instructors']);
    }

    //
    // Check if we are to display a course detail page
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'course'
        && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' 
        && isset($args['uri_split'][2]) && $args['uri_split'][2] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseOfferingDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

        //
        // Get the course information
        //
        $course_permalink = $args['uri_split'][1];
        $offering_permalink = $args['uri_split'][2];
        $rc = ciniki_courses_web_courseOfferingDetails($ciniki, $settings, $business_id, $course_permalink, $offering_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering = $rc['offering'];
        $page['title'] = $offering['name'];
        if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x01) == 0x01 && $offering['code'] != '' ) {
            $page['title'] = $offering['code'] . ' - ' . $offering['name'];
        } elseif( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x20) == 0x20 && $offering['offering_code'] != '' ) {
            $page['title'] = $offering['offering_code'] . ' - ' . $offering['name'];
        }
        $page['breadcrumbs'][] = array('name'=>$page['title'], 'url'=>$args['base_url'] . '/course/' . $course_permalink . '/' . $offering_permalink);
        $ciniki['response']['head']['og']['url'] .= '/course/' . $course_permalink . '/' . $offering_permalink;
        if( isset($settings['page-courses-level-display']) 
            && $settings['page-courses-level-display'] == 'yes' 
            && isset($offering['level']) && $offering['level'] != ''
            ) {
            $page['title'] .= ' - ' . $offering['level'];
        }
        $ciniki['response']['head']['og']['title'] = $page['title'];

        //
        // Add primary image
        //
        if( isset($offering['image_id']) && $offering['image_id'] > 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 
                'image_id'=>$offering['image_id'],'title'=>$offering['name'], 'caption'=>'');
        }
      
        if( isset($offering['short_description']) && $offering['short_description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($offering['short_description']);
        } elseif( isset($offering['long_description']) && $offering['long_description'] != '' ) {
            $ciniki['response']['head']['og']['description'] = strip_tags($offering['long_description']);
        }

        //
        // Add description
        //
        $content = "<div class='entry-content'>";
        if( isset($offering['long_description']) ) {
            $rc = ciniki_web_processContent($ciniki, $settings, $offering['long_description']); 
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $content .= $rc['content'];
        }

        //
        // List the prices for the course
        //
        if( isset($offering['prices']) && count($offering['prices']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'cartSetupPrices');
            $rc = ciniki_web_cartSetupPrices($ciniki, $settings, $business_id, $offering['prices']);
            if( $rc['stat'] != 'ok' ) {
                error_log("Error in formatting prices.");
            } else {
                $content .= $rc['content'];
            }
        }

        //
        // The classes for a course offering
        //
        if( isset($offering['classes']) && count($offering['classes']) > 1 ) {
            $content .= "<h2>Classes</h2><p>";
            foreach($offering['classes'] as $cid => $class) {
                $content .= $class['class_date'] . " " . $class['start_time'] . " - " . $class['end_time'] . "<br/>";
            }
            $content .= "</p>";
        } elseif( isset($offering['classes']) && count($offering['classes']) == 1 ) {
            $content .= "<h2>Date</h2><p>";
            $content .= "<p>" . $offering['condensed_date'] . "</p>";
        }

        //
        // The files for a course offering
        //
        if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x08) == 0x08 ) {
            if( isset($offering['files']) ) {
                $content .= "<h2>Files</h2>";
                foreach($offering['files'] as $fid => $file) {
                    $url = $args['base_url'] . '/download/' . $file['permalink'] . '.' . $file['extension'];
                }
            }
        }
        $content .= "</div>";
        $page['blocks'][] = array('type'=>'content', 'html'=>$content);

        //
        // Add the share buttons
        //
        if( !isset($settings['page-courses-share-buttons']) || $settings['page-courses-share-buttons'] == 'yes' ) {
            $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$offering['name'], 'tags'=>array());
        }

        //
        // The instructors for a course offering
        //
        if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x02) == 0x02 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorList');
            $rc = ciniki_courses_web_instructorList($ciniki, $settings, $business_id, $offering['id'], 'cilist');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( count($rc['instructors']) > 0 ) {
                $page['blocks'][] = array('type'=>'cilist', 'section'=>'instructors', 
                    'title'=>(count($rc['instructors']) > 1 ? 'Instructors' : 'Instructor'),
                    'base_url'=>$args['base_url'] . '/instructor', 'categories'=>$rc['instructors']);
            }
        }
    }

    //
    // Check if we are to display a registration detail page
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'registration' 
        && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes'
        ) {
        //
        // Check if membership info should be displayed here
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'registrationDetails');
        $rc = ciniki_courses_web_registrationDetails($ciniki, $settings, $business_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration = $rc['registration'];
        $page_content = '';
        if( $registration['details'] != '' ) {
            if( isset($settings["page-courses-registration-image"]) && $settings["page-courses-registration-image"] != '' && $settings["page-courses-registration-image"] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses-registration-image"], 'original', '500', 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= "<aside><div class='image-wrap'>"
                    . "<div class='image'><img title='' alt='" . $ciniki['business']['details']['name'] . "' src='" . $rc['url'] . "' /></div>";
                if( isset($settings["page-courses-registration-image-caption"]) && $settings["page-courses-registration-image-caption"] != '' ) {
                    $page_content .= "<div class='image-caption'>" . $settings["page-courses-registration-image-caption"] . "</div>";
                }
                $page_content .= "</div></aside>";
            }
            $rc = ciniki_web_processContent($ciniki, $settings, $registration['details']);  
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= $rc['content'];

            foreach($registration['files'] as $fid => $file) {
                $file = $file['file'];
                $url = $args['base_url'] . '/download/' . $file['permalink'] . '.' . $file['extension'];
                $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
            }
            
            if( isset($registration['more-details']) && $registration['more-details'] != '' ) {
                $rc = ciniki_web_processContent($ciniki, $settings, $registration['more-details']); 
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];
            }
            $page['blocks'][] = array('type'=>'content', 'html'=>$page_content);
        }
    }

    //
    // Generate the list of courses upcoming, current, past
    //
    else {
        $coursetype = '';
        if( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
            $coursetype = urldecode($args['uri_split'][0]);
        }
        // Setup default settings
        if( !isset($settings['page-courses-upcoming-active']) ) {
            $settings['page-courses-upcoming-active'] = 'yes';
        }
        if( !isset($settings['page-courses-current-active']) ) {
            $settings['page-courses-current-active'] = 'no';
        }
        if( !isset($settings['page-courses-past-active']) ) {
            $settings['page-courses-past-active'] = 'no';
        }
        //
        //
        // Check for content in settings
        //
        if( $coursetype != '' ) {
            $type_name = '-' . preg_replace('/[^a-z0-9]/', '', strtolower($coursetype));
        } else {
            $type_name = '';
        }
        // Load any content for this page
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $business_id, 'ciniki.web', 'content', "page-courses$type_name");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cnt = $rc['content'];

        $page_content = '';
        if( isset($settings['page-courses' . $type_name . '-image']) || isset($cnt['page-courses' . $type_name . '-content']) ) {
            // Check if there are files to be displayed on the main page
            $program_url = '';
            if( $type_name == '' && (isset($settings['page-courses-catalog-download-active']) 
                    && $settings['page-courses-catalog-download-active'] == 'yes' )
    //          || ()   -- future files
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'files');
                $rc = ciniki_courses_web_files($ciniki, $settings, $business_id);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['files']) ) {
                    $reg_files = $rc['files'];
                    // Check if program brochure download and link to image
                    if( count($reg_files) == 1 && isset($reg_files[0]['file']['permalink']) && $reg_files[0]['file']['permalink'] != '' ) {
                        $program_url = $args['base_url'] . '/download/' . $reg_files[0]['file']['permalink'] . '.' . $reg_files[0]['file']['extension'];
                        $program_url_title = $reg_files[0]['file']['name'];
                    }
                } else {
                    $reg_files = array();
                }
            }
            if( isset($settings["page-courses" . $type_name . "-image"]) && $settings["page-courses" . $type_name . "-image"] != '' && $settings["page-courses" . $type_name . "-image"] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses" . $type_name . "-image"], 'original', '500', 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= "<aside><div class='image-wrap'>"
                    . "<div class='image'>";
                if( $program_url != '' ) {
                    $page_content .= "<a target='_blank' href='$program_url' title='$program_url_title'>";
                }
                $page_content .= "<img title='' alt='" . $ciniki['business']['details']['name'] . "' src='" . $rc['url'] . "' />";
                if( $program_url != '' ) {
                    $page_content .= "</a>";
                }
                $page_content .= "</div>";
                if( isset($settings["page-courses" . $type_name . "-image-caption"]) && $settings["page-courses" . $type_name . "-image-caption"] != '' ) {
                    $page_content .= "<div class='image-caption'>" . $settings["page-courses" . $type_name . "-image-caption"] . "</div>";
                }
                $page_content .= "</div></aside>";
            }
            if( isset($cnt['page-courses' . $type_name . '-content']) ) {
                $rc = ciniki_web_processContent($ciniki, $settings, $cnt['page-courses' . $type_name . '-content']);    
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];
            }

            // Check if there are files to be displayed on the main page
            if( $type_name == '' && (isset($settings['page-courses-catalog-download-active']) 
                    && $settings['page-courses-catalog-download-active'] == 'yes' )
    //          || ()   -- future files
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'files');
                $rc = ciniki_courses_web_files($ciniki, $settings, $business_id);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['files']) ) {
                    foreach($rc['files'] as $f => $file) {
                        $file = $file['file'];
                        $url = $args['base_url'] . '/download/' . $file['permalink'] . '.' . $file['extension'];
                        $page_content .= "<p>"
                            . "<a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" 
                            . $file['name'] . "</a></p>";
                    }
                }
            }
            $page['blocks'][] = array('type'=>'content', 'html'=>$page_content);       
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseList');
        foreach(array('upcoming', 'current', 'past') as $type) {
            if( $settings["page-courses-$type-active"] != 'yes' ) {
                continue;
            }
            if( $type == 'past' ) {
                if( $settings['page-courses-current-active'] == 'yes' ) {
                    // If displaying the current list, then show past as purely past.
                    $rc = ciniki_courses_web_courseList($ciniki, $settings, $business_id, $coursetype, $type);
                } else {
                    // Otherwise, include current courses in the past
                    $rc = ciniki_courses_web_courseList($ciniki, $settings, $business_id, $coursetype, 'currentpast');
                }
            } else {
                $rc = ciniki_courses_web_courseList($ciniki, $settings, $business_id, $coursetype, $type);
            }
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $categories = $rc['categories'];

            if( isset($settings["page-courses-$type-name"]) && $settings["page-courses-$type-name"] != '' ) {
                $name = $settings["page-courses-$type-name"];
            } else {
                $name = ucwords($type . "");
            }
            $page_content = '';

            if( count($categories) > 0 ) {
                $page_content .= "<table class='clist'>\n";
                $prev_category = NULL;
                $num_categories = count($categories);
                foreach($categories as $cnum => $c) {
                    if( $prev_category != NULL ) {
                        $page_content .= "</td></tr>\n";
                    }
                    $hide_dates = 'no';
                    if( isset($c['name']) && $c['name'] != '' ) {
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'>" . $c['name'] . "</span></th>"
                            . "<td>";
                        // $content .= "<h2>" . $c['cname'] . "</h2>";
                    } elseif( $num_categories == 1 && count($c) > 0) {
                        // Only the blank category
                        $offering = reset($c['offerings']);
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'>" . $offering['condensed_date'] . "</span></th>"
                            . "<td>";
                        $hide_dates = 'yes';
                    } else {
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'></span></th>"
                            . "<td>";
                    }
                    foreach($c['offerings'] as $onum => $offering) {
                        if( $offering['is_details'] == 'yes' ) {
                            $offering_url = $args['base_url'] . '/course/' . $offering['course_permalink'] . '/' . $offering['permalink'];
                        } else {
                            $offering_url = '';
                        }
                        if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x01) == 0x01 && $offering['code'] != '' ) {
                            $offering_name = $offering['code'] . ' - ' . $offering['name'];
                        } elseif( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x20) == 0x20 && $offering['offering_code'] != '' ) {
                            $offering_name = $offering['offering_code'] . ' - ' . $offering['name'];
                        } else {
                            $offering_name = $offering['name'];
                        }
                        if( isset($settings['page-courses-level-display']) 
                            && $settings['page-courses-level-display'] == 'yes' 
                            && isset($offering['level']) && $offering['level'] != ''
                            ) {
                            $offering_name .= ' - ' . $offering['level'];
                        }

                        if( $offering_url != '' ) {
                            $page_content .= "<a href='$offering_url'><p class='clist-title'>" . $offering_name . "</p></a>";
                        } else {
                            $page_content .= "<p class='clist-title'>" . $offering_name . "</p>";
                        }
                        if( $hide_dates != 'yes' ) {
                            $page_content .= "<p class='clist-subtitle'>" . $offering['condensed_date'] . "</p>";
                        }
                        $rc = ciniki_web_processContent($ciniki, $settings, $offering['short_description'], 'clist-description');   
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $page_content .= $rc['content'];
                        // $page_content .= "<p class='clist-description'>" . $rc['content'] . "</p>";
                        if( $offering_url != '' ) {
                            $page_content .= "<p class='clist-url clist-more'><a href='" . $offering_url . "'>... more</a></p>";
                        }
                    }
                }
            } else {
                $page_content .= "<p>No " . strtolower($name) . " found</p>";
            }
            $page_content .= "</td></tr>\n</table>\n";
            $page['blocks'][] = array('type'=>'content', 'title'=>$name, 'html'=>$page_content);
        }
        //
        // Check if no submenu going to be displayed, then need to display registration information here
        //
        if( count($page['submenu']) == 1 
            && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'registrationDetails');
            $rc = ciniki_courses_web_registrationDetails($ciniki, $settings, $business_id);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration = $rc['registration'];
            if( $registration['details'] != '' ) {
                // Check for a programs pdf, and link to image if it exists
                $program_url = '';
                if( isset($registration['files']) && count($registration['files']) == 1 ) {
                    if( isset($registration['files'][0]['file']['permalink']) && $registration['files'][0]['file']['permalink'] != '' ) {
                        $program_url = $args['base_url'] . '/download/' . $registration['files'][0]['file']['permalink'] . '.' . $registration['files'][0]['file']['extension'];
                        $program_url_title = $registration['files'][0]['file']['name'];
                    }
                }
                $page_content = '';
                if( isset($settings["page-courses-registration-image"]) && $settings["page-courses-registration-image"] != '' && $settings["page-courses-registration-image"] > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                    $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses-registration-image"], 'original', '500', 0);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= "<aside><div class='image-wrap'>"
                        . "<div class='image'>";
                    if( $program_url != '' ) {
                        $page_content .= "<a target='_blank' href='$program_url' title='$program_url_title'>";
                    }
                    $page_content .= "<img title='' alt='" . $ciniki['business']['details']['name'] . "' src='" . $rc['url'] . "' />";
                    if( $program_url != '' ) {
                        $page_content .= "</a>";
                    }

                    $page_content .= "</div>";
                    if( isset($settings["page-courses-registration-image-caption"]) && $settings["page-courses-registration-image-caption"] != '' ) {
                        $page_content .= "<div class='image-caption'>" . $settings["page-courses-registration-image-caption"] . "</div>";
                    }
                    $page_content .= "</div></aside>";
                }
                $rc = ciniki_web_processContent($ciniki, $settings, $registration['details']);  
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];

                foreach($registration['files'] as $fid => $file) {
                    $file = $file['file'];
                    $url = $args['base_url'] . '/download/' . $file['permalink'] . '.' . $file['extension'];
                    $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
                }
                
                if( isset($registration['more-details']) && $registration['more-details'] != '' ) {
                    $rc = ciniki_web_processContent($ciniki, $settings, $registration['more-details']); 
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= $rc['content'];
                }
            }

            $page_content .= "</td></tr>\n</table>\n";
            $page['blocks'][] = array('type'=>'content', 'html'=>$page_content);
        }
    }

    if( count($page['submenu']) == 1 
        && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
        $page['submenu'] = array();
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
