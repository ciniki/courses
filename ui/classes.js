//
// The app to add/edit course instructor images
//
function ciniki_courses_classes() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the add/edit form
        //
        this.edit = new M.panel('Edit Class',
            'ciniki_courses_classes', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.courses.classes.edit');
        this.edit.default_data = {'class_date':'', 'start_time':'', 'end_time':'', 'notes':''};
        this.edit.data = {};
        this.edit.course_id = 0;
        this.edit.class_id = 0;
        this.edit.sections = {
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'class_date':{'label':'Date', 'type':'date'},
                'start_time':{'label':'Start Time', 'type':'text', 'size':'small'},
                'end_time':{'label':'End Time', 'type':'text', 'size':'small'},
            }},
            '_notes':{'label':'Notes', 'type':'simpleform', 'fields':{
                'notes':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_classes.saveClass();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_courses_classes.deleteClass();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.courses.offeringClassHistory', 'args':{'business_id':M.curBusinessID, 
                'class_id':this.class_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_courses_classes.saveClass();');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_classes', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.offering_id, args.course_id);
        } else if( args.class_id != null && args.class_id > 0 ) {
            this.showEdit(cb, args.class_id);
        }
        return false;
    }

    this.showEdit = function(cb, cid, oid, coid) {
        if( cid != null ) {
            this.edit.class_id = cid;
        }
        if( oid != null ) {
            this.edit.offering_id = oid;
        }
        if( coid != null ) {
            this.edit.course_id = coid;
        }
        if( this.edit.class_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringClassGet', 
                {'business_id':M.curBusinessID, 'class_id':this.edit.class_id}, function (rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_classes.edit.data = rsp.class;
                    M.ciniki_courses_classes.edit.refresh();
                    M.ciniki_courses_classes.edit.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = this.edit.default_data;
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveClass = function() {
        if( this.edit.class_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.courses.offeringClassUpdate', 
                    {'business_id':M.curBusinessID, 'class_id':this.edit.class_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_courses_classes.edit.close();
                            }
                        });
            }
        } else {
            var c = this.edit.serializeForm('yes');
            c += '&offering_id=' + encodeURIComponent(this.edit.offering_id);
            c += '&course_id=' + encodeURIComponent(this.edit.course_id);
            if( c != null ) {
                var rsp = M.api.postJSONFormData('ciniki.courses.offeringClassAdd', 
                    {'business_id':M.curBusinessID}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_courses_classes.edit.close();
                        }
                    });
            }
        }
    };

    this.deleteClass = function() {
        if( confirm('Are you sure you want to delete \'' + this.edit.data.class_date + '\'?') ) {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringClassDelete', {'business_id':M.curBusinessID, 
                'class_id':this.edit.class_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_classes.edit.close();
                });
        }
    };
}
