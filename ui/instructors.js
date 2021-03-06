//
// The app to add/edit course instructor images
//
function ciniki_courses_instructors() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the complete list of instructors
        //
        this.menu = new M.panel('Instructors',
            'ciniki_courses_instructors', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.courses.instructors.menu');
        this.menu.sections = {
            'instructors':{'label':'', 'type':'simplegrid', 'num_cols':2,
                'headerValues':['name', 'status'],
                'cellClasses':[''],
                'addTxt':'Add Instructor',
                'addFn':'M.ciniki_courses_instructors.showEdit(\'M.ciniki_courses_instructors.showMenu();\',0,0,0,0);',
                },
        };
        this.menu.sectionData = function(s) {
            return this.data[s];
        };
        this.menu.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_courses_instructors.showInstructor(\'M.ciniki_courses_instructors.showMenu();\',0,\'' + d.id + '\');';
        };
        this.menu.addClose('Back');

        //
        // The panel to display the instructor information, and allow picture updates
        //
        this.instructor = new M.panel('Instructor',
            'ciniki_courses_instructors', 'instructor',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.instructors.instructor');
        this.instructor.instructor_id = 0;
        this.instructor.offering_instructor_id = 0;
        this.instructor.sections = {
            '_image':{'label':'', 'type':'imageform', 'aside':'yes', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
                }},
            'info':{'label':'', 'aside':'yes', 'list':{
                'name':{'label':'Name'},
                'web_visible':{'label':'Web Settings'},
                'url':{'label':'URL'},
                }},
            'short_bio':{'label':'Short Bio ', 'type':'htmlcontent'},
            'full_bio':{'label':'Full Bio', 'type':'htmlcontent'},
            'images':{'label':'Gallery', 'type':'simplethumbs'},
            '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Image',
                'addFn':'M.startApp(\'ciniki.courses.instructorimages\',null,\'M.ciniki_courses_instructors.showInstructor();\',\'mc\',{\'instructor_id\':M.ciniki_courses_instructors.instructor.instructor_id,\'add\':\'yes\'});',
                },
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_courses_instructors.showEdit(\'M.ciniki_courses_instructors.showInstructor();\',M.ciniki_courses_instructors.instructor.offering_instructor_id,0,0,M.ciniki_courses_instructors.instructor.instructor_id);'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_courses_instructors.deleteInstructor();'},
                }},
        };
        this.instructor.sectionData = function(s) {
            if( s == 'short_bio' || s == 'full_bio' ) { 
                return this.data[s].replace(/\n/g, '<br/>');
            }
            if( s == 'info' ) { return this.sections[s].list; }
            return this.data[s];
            };
        this.instructor.listLabel = function(s, i, d) {
            if( s == 'info' ) { return d.label; }
            return null;
        };
        this.instructor.listValue = function(s, i, d) {
            if( i == 'url' && this.data[i] != '' ) {
                return '<a target="_blank" href="' + this.data[i] + '">' + this.data[i] + '</a>';
            }
            return this.data[i];
        };
        this.instructor.fieldValue = function(s, i, d) {
            if( this.data[i] != null ) { return this.data[i]; }
            return '';
        };
        this.instructor.cellValue = function(s, i, j, d) {
            if( s == 'images' && j == 0 ) { 
                if( d.image.image_id > 0 ) {
                    if( d.image.image_data != null && d.image.image_data != '' ) {
                        return '<img width="75px" height="75px" src=\'' + d.image.image_data + '\' />'; 
                    } else {
                        return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.images.getImage', {'tnid':M.curTenantID, 'image_id':d.image.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />'; 
                    }
                } else {
                    return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
                }
            }
            if( s == 'images' && j == 1 ) { 
                return '<span class="maintext">' + d.image.name + '</span><span class="subtext">' + d.image.description + '</span>'; 
            }
        };
        this.instructor.rowFn = function(s, i, d) {
            if( s == 'images' ) {
                return 'M.startApp(\'ciniki.courses.instructorimages\',null,\'M.ciniki_courses_instructors.showInstructor();\',\'mc\',{\'instructor_image_id\':\'' + d.image.id + '\'});';
            }
        };
        this.instructor.thumbFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.courses.instructorimages\',null,\'M.ciniki_courses_instructors.showInstructor();\',\'mc\',{\'instructor_image_id\':\'' + d.image.id + '\'});';
        };
        this.instructor.addDropImage = function(iid) {
            var rsp = M.api.getJSON('ciniki.courses.instructorImageAdd',
                {'tnid':M.curTenantID, 'image_id':iid, 
                'instructor_id':M.ciniki_courses_instructors.instructor.instructor_id});
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            return true;
        };
        this.instructor.addDropImageRefresh = function() {
            if( M.ciniki_courses_instructors.instructor.instructor_id > 0 ) {
                var rsp = M.api.getJSONCb('ciniki.courses.instructorGet', {'tnid':M.curTenantID, 
                    'instructor_id':M.ciniki_courses_instructors.instructor.instructor_id, 
                    'images':'yes'}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_courses_instructors.instructor.data.images = rsp.instructor.images;
                        M.ciniki_courses_instructors.instructor.refreshSection('images');
                    });
            }
        };
        this.instructor.addButton('edit', 'Edit', 'M.ciniki_courses_instructors.showEdit(\'M.ciniki_courses_instructors.showInstructor();\',M.ciniki_courses_instructors.instructor.offering_instructor_id,0,0,M.ciniki_courses_instructors.instructor.instructor_id);');
        this.instructor.addClose('Back');

        //
        // The panel to display the add/edit form
        //
        this.edit = new M.panel('Edit Instructor',
            'ciniki_courses_instructors', 'edit',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.instructors.edit');
        this.edit.default_data = {'first':'', 'last':'', 'webflags':0};
        this.edit.data = {};
        this.edit.course_id = 0;
        this.edit.offering_id = 0;
        this.edit.offering_instructor_id = 0;
        this.edit.instructor_id = 0;
        this.edit.sections = {
            '_image':{'label':'', 'type':'imageform', 'aside':'yes', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'instructor':{'label':'Instructor', 'aside':'yes', 'fields':{
                'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
                'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
                'webflags':{'label':'Web', 'type':'flags', 'join':'yes', 'toggle':'no', 'flags':this.webFlags},
                'url':{'label':'URL', 'type':'text'},
                }},
            '_short_bio':{'label':'Short Bio', 'type':'simpleform', 'fields':{
                'short_bio':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_full_bio':{'label':'Full Bio', 'type':'simpleform', 'fields':{
                'full_bio':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_instructors.saveInstructor();'},
            }},
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'first' || i == 'last' ) {
                var rsp = M.api.getJSONBgCb('ciniki.courses.instructorSearch', 
                    {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                    function(rsp) { 
                        M.ciniki_courses_instructors.edit.liveSearchShow(s, i, M.gE(M.ciniki_courses_instructors.edit.panelUID + '_' + i), rsp.instructors); 
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'first' || f == 'last' ) { 
                return d.instructor.name;
            }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( f == 'first' || f == 'last' ) {
                return 'M.ciniki_courses_instructors.edit.updateInstructor(\'' + s + '\',\'' + d.instructor.id + '\');';
            }
        };
        this.edit.updateInstructor = function(s, iid) {
            M.ciniki_courses_instructors.showEdit(null, null,null,null,iid);
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.courses.instructorHistory', 'args':{'tnid':M.curTenantID, 
                'instructor_id':this.instructor_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_courses_instructors.edit.setFieldValue('primary_image_id', iid, null, null);
            return true;
        };
        this.edit.deleteImage = function(fid) {
            this.setFieldValue(fid, 0, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_courses_instructors.saveInstructor();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_instructors', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.offering_id, args.course_id, 0);
        } else if( args.offering_instructor_id != null && args.offering_instructor_id > 0 ) {
            this.showInstructor(cb, args.offering_instructor_id, 0);
        } else {
            this.showMenu(cb);
        }
    };

    this.showMenu = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.courses.instructorList', 
            {'tnid':M.curTenantID}, 
                function (rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructors.menu.data = {'instructors':rsp.instructors};
                    M.ciniki_courses_instructors.menu.refresh();
                    M.ciniki_courses_instructors.menu.show(cb);
                }
            );
    };

    this.showInstructor = function(cb, oiid, iid) {
        if( oiid != null ) {
            this.instructor.offering_instructor_id = oiid;
        }
        if( iid != null ) {
            this.instructor.instructor_id = iid;
        }
        if( this.instructor.offering_instructor_id > 0 ) {
            this.instructor.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.courses.offeringInstructorGet', 
                {'tnid':M.curTenantID, 'offering_instructor_id':this.instructor.offering_instructor_id, 'images':'yes'}, 
                    function (rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_courses_instructors.instructor.data = rsp.instructor;
                        if( rsp.instructor != null && rsp.instructor.instructor_id != null ) {
                            M.ciniki_courses_instructors.instructor.instructor_id = rsp.instructor.instructor_id;
                        }
                        M.ciniki_courses_instructors.instructor.refresh();
                        M.ciniki_courses_instructors.instructor.show(cb);
                    }
                );
        } else if( this.instructor.instructor_id > 0 ) {
            this.instructor.sections._buttons.buttons.delete.visible = 'no';
            var rsp = M.api.getJSONCb('ciniki.courses.instructorGet', 
                {'tnid':M.curTenantID, 'instructor_id':this.instructor.instructor_id, 'images':'yes'}, 
                    function (rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_courses_instructors.instructor.data = rsp.instructor;
                        M.ciniki_courses_instructors.instructor.refresh();
                        M.ciniki_courses_instructors.instructor.show(cb);
                    }
                );
        }
    };

    this.showEdit = function(cb, oiid, oid, cid, iid) {
        if( oiid != null ) {
            this.edit.offering_instructor_id = oiid;
        }
        if( oid != null ) {
            this.edit.offering_id = oid;
        }
        if( cid != null ) {
            this.edit.course_id = cid;
        }
        if( iid != null ) {
            this.edit.instructor_id = iid;
        }
        if( this.edit.offering_instructor_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringInstructorGet', 
                {'tnid':M.curTenantID, 'offering_instructor_id':this.edit.offering_instructor_id}, function (rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructors.edit.data = rsp.instructor;
                    M.ciniki_courses_instructors.edit.instructor_id = rsp.instructor.instructor_id;
                    M.ciniki_courses_instructors.edit.refresh();
                    M.ciniki_courses_instructors.edit.show(cb);
                });
        } else if( this.edit.instructor_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.courses.instructorGet', 
                {'tnid':M.curTenantID, 'instructor_id':this.edit.instructor_id}, function (rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructors.edit.data = rsp.instructor;
                    M.ciniki_courses_instructors.edit.instructor_id= rsp.instructor.id;
                    M.ciniki_courses_instructors.edit.refresh();
                    M.ciniki_courses_instructors.edit.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = this.edit.default_data;
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveInstructor = function() {
        if( this.edit.instructor_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.courses.instructorUpdate', 
                    {'tnid':M.curTenantID, 'instructor_id':this.edit.instructor_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_courses_instructors.saveOfferingInstructor();
                            }
                        });
            }
        } else {
            var c = this.edit.serializeForm('yes');
            if( c != null ) {
                var rsp = M.api.postJSONFormData('ciniki.courses.instructorAdd', 
                    {'tnid':M.curTenantID}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_courses_instructors.edit.instructor_id = rsp.id;
                            M.ciniki_courses_instructors.saveOfferingInstructor();
                        }
                    });
            }
        }
    };

    this.saveOfferingInstructor = function() {
        if( this.edit.offering_instructor_id == 0 && this.edit.offering_id > 0 ) {
            // Add the instructor to the offering, nothing to do for update
            var rsp = M.api.getJSONCb('ciniki.courses.offeringInstructorAdd', {'tnid':M.curTenantID, 
                'course_id':this.edit.course_id,
                'offering_id':this.edit.offering_id,
                'instructor_id':this.edit.instructor_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructors.edit.close();
                });
        } else {
            this.edit.close();
        }
    };

    this.deleteInstructor = function() {
        M.confirm('Are you sure you want to delete this instructor?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringInstructorDelete', {'tnid':M.curTenantID, 
                'offering_instructor_id':M.ciniki_courses_instructors.instructor.offering_instructor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_instructors.instructor.close();
            });
        });
    };
}
