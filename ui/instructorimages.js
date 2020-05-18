//
// The app to add/edit course instructor images
//
function ciniki_courses_instructorimages() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_courses_instructorimages', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.courses.instructorimages.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.instructor_image_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
//              'url':{'label':'Link', 'type':'text'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_instructorimages.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_courses_instructorimages.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.courses.instructorImageHistory', 'args':{'tnid':M.curTenantID, 
                'instructor_image_id':this.instructor_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_courses_instructorimages.edit.setFieldValue('image_id', iid);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_courses_instructorimages.saveImage();');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_instructorimages', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.instructor_id);
        } else if( args.instructor_image_id != null && args.instructor_image_id > 0 ) {
            this.showEdit(cb, args.instructor_image_id, 0);
        }
        return false;
    }

    this.showEdit = function(cb, iid, cid) {
        if( iid != null ) {
            this.edit.instructor_image_id = iid;
        }
        if( cid != null ) {
            this.edit.instructor_id = cid;
        }
        if( this.edit.instructor_image_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.courses.instructorImageGet', 
                {'tnid':M.curTenantID, 'instructor_image_id':this.edit.instructor_image_id}, function (rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructorimages.edit.data = rsp.image;
                    M.ciniki_courses_instructorimages.edit.refresh();
                    M.ciniki_courses_instructorimages.edit.show(cb);
                });
        } else {
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.reset();
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.instructor_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.courses.instructorImageUpdate', 
                    {'tnid':M.curTenantID, 
                    'instructor_image_id':this.edit.instructor_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } 
                            M.ciniki_courses_instructorimages.edit.close();
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            c += '&instructor_id=' + encodeURIComponent(this.edit.instructor_id);
            var rsp = M.api.postJSONFormData('ciniki.courses.instructorImageAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_instructorimages.edit.close();
                });
        }
    };

    this.deleteImage = function() {
        M.confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.courses.instructorImageDelete', 
                {'tnid':M.curTenantID, 
                'instructor_image_id':M.ciniki_courses_instructorimages.edit.instructor_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_instructorimages.edit.close();
                });
        });
    };
}
