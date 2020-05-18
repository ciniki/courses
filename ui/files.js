//
// 
//
function ciniki_courses_files() {
    this.init = function() {
        //
        // The panel to display the add form
        //
        this.add = new M.panel('Add File',
            'ciniki_courses_files', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.courses.info.edit');
        this.add.default_data = {'type':'20'};
        this.add.data = {}; 
        this.add.sections = {
            '_file':{'label':'File', 'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_files.addFile();'},
            }},
        };
        this.add.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.add.addButton('save', 'Save', 'M.ciniki_courses_files.addFile();');
        this.add.addClose('Cancel');

        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('File',
            'ciniki_courses_files', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.courses.info.edit');
        this.edit.file_id = 0;
        this.edit.data = null;
        this.edit.sections = {
            'info':{'label':'Details', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_files.saveFile();'},
                'download':{'label':'Download', 'fn':'M.ciniki_courses_files.downloadFile(M.ciniki_courses_files.edit.file_id);'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_courses_files.deleteFile();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i]; 
        }
        this.edit.sectionData = function(s) {
            return this.data[s];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.courses.fileHistory', 'args':{'tnid':M.curTenantID, 
                'file_id':this.file_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_courses_files.saveFile();');
        this.edit.addClose('Cancel');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_files', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.file_id != null && args.file_id > 0 && args.offering_file_id != null && args.offering_file_id > 0 ) {
            this.showEditFile(cb, args.file_id, args.offering_file_id);
        } else if( args.file_id != null && args.file_id > 0 ) {
            this.showEditFile(cb, args.file_id, 0);
        } else if( args.offering_id != null && args.offering_id > 0 
            && args.course_id != null && args.course_id > 0 && args.add != null && args.add == 'yes' ) {
            this.showAddFile(cb, args.offering_id, args.course_id);
        } else if( args.type != null && args.type > 0 && args.add != null && args.add == 'yes' ) {
            this.showAddFile(cb, 0, 0, args.type);
        } else {
            M.alert('Invalid request');
        }
    }

    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    };

    this.showAddFile = function(cb, oid, cid, type) {
        this.add.reset();
        this.add.offering_file_id = 0;
        this.add.offering_id = 0;
        this.add.course_id = 0;
        if( oid != null ) { this.add.offering_id = oid; }
        if( cid != null ) { this.add.course_id = cid; }
        if( type != null ) {
            this.add.data = {'type':type};
        } else {
            this.add.data = {'type':20};
        }
        this.add.refresh();
        this.add.show(cb);
    };

    this.addFile = function() {
        var c = this.add.serializeFormData('yes');

        if( c != '' ) {
            var rsp = M.api.postJSONFormData('ciniki.courses.fileAdd', 
                {'tnid':M.curTenantID, 'type':this.add.data.type}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_courses_files.add.file_id = rsp.id;
                            M.ciniki_courses_files.saveOfferingFile();
                        }
                    });
        } else {
            this.add.close();
        }
    };

    this.showEditFile = function(cb, fid, ofid) {
        if( fid != null ) {
            this.edit.file_id = fid;
        }
        if( ofid != null ) {
            this.edit.offering_file_id = ofid;
        }
        var rsp = M.api.getJSONCb('ciniki.courses.fileGet', {'tnid':M.curTenantID, 
            'file_id':this.edit.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_files.edit;
                p.data = rsp.file;
                p.refresh();
                p.show(cb);
            });
    };

    this.saveFile = function() {
        var c = this.edit.serializeFormData('no');

        if( c != '' ) {
            var rsp = M.api.postJSONFormData('ciniki.courses.fileUpdate', 
                {'tnid':M.curTenantID, 'file_id':this.edit.file_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_courses_files.edit.close();
                        }
                    });
        } else {
            this.edit.close();
        }
    };

    this.saveOfferingFile = function() {
        // Add the file to the offering, nothing to do for update
        var rsp = M.api.getJSONCb('ciniki.courses.offeringFileAdd', {'tnid':M.curTenantID, 
            'course_id':this.add.course_id,
            'offering_id':this.add.offering_id,
            'file_id':this.add.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_files.add.close();
            });
    };

    this.deleteFile = function() {
        M.confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            if( this.edit.offering_file_id != null && this.edit.offering_file_id > 0 ) {
                M.api.getJSONCb('ciniki.courses.offeringFileDelete', {'tnid':M.curTenantID, 
                    'offering_file_id':M.ciniki_courses_files.edit.offering_file_id}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_files.edit.close();
                    });
            } else {
                M.api.getJSONCb('ciniki.courses.fileDelete', {'tnid':M.curTenantID, 
                    'file_id':M.ciniki_courses_files.edit.file_id}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_files.edit.close();
                    });
            }
        });
    };

    this.downloadFile = function(fid) {
        M.api.openFile('ciniki.courses.fileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
}
