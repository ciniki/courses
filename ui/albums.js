//
// The course offerings app to manage courses offering by a business
//
function ciniki_courses_albums() {
    //
    // The panel to list the album
    //
    this.menu = new M.panel('album', 'ciniki_courses_albums', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.courses.albums.menu');
    this.menu.course_id = 0;
    this.menu.offering_id = 0;
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search album',
//            'noData':'No album found',
//            },
        'albums':{'label':'Photo Album', 'type':'simplegrid', 'num_cols':1,
            'noData':'No album',
            'addTxt':'Add Photo Album',
            'addFn':'M.ciniki_courses_albums.album.open(\'M.ciniki_courses_albums.menu.open();\',this.course_id,this.offering_id,0);'
            },
    }
/*    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.courses.albumSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_courses_albums.menu.liveSearchShow('search',null,M.gE(M.ciniki_courses_albums.menu.panelUID + '_' + s), rsp.albums);
                });
        }
    }
    this.albums.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.albums.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_courses_albums.album.open(\'M.ciniki_courses_albums.albums.open();\',\'' + d.id + '\');';
    } */
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'albums' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'albums' ) {
            return 'M.ciniki_courses_albums.album.open(\'M.ciniki_courses_albums.menu.open();\',' + this.course_id + ',' + this.offering_id + ',\'' + d.id + '\',M.ciniki_courses_albums.album.nplist);';
        }
    }
    this.menu.open = function(cb, cid, oid) {
        if( cid != null ) { this.course_id = cid; }
        if( oid != null ) { this.offering_id = oid; }
        M.api.getJSONCb('ciniki.courses.albumList', {'business_id':M.curBusinessID, 'course_id':this.course_id, 'offering_id':this.offering_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_albums.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Photo Album
    //
    this.album = new M.panel('Photo Album', 'ciniki_courses_albums', 'album', 'mc', 'medium', 'sectioned', 'ciniki.courses.albums.album');
    this.album.data = null;
    this.album.album_id = 0;
    this.album.nplist = [];
    this.album.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_description':{'label':'Description', 'aside':'yes', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        'images':{'label':'Images', 'aside':'yes', 'type':'simplethumbs'},
        '_images':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Image',
            'addFn':'M.ciniki_courses_albums.album.save("M.ciniki_courses_albums.image.open(\'M.ciniki_courses_albums.album.addDropImageRefresh();\',M.ciniki_courses_albums.album.album_id,0);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_albums.album.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_albums.album.album_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_albums.album.remove();'},
            }},
        };
    this.album.fieldValue = function(s, i, d) { return this.data[i]; }
    this.album.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.albumHistory', 'args':{'business_id':M.curBusinessID, 'album_id':this.album_id, 'field':i}};
    }
    this.album.thumbFn = function(s, i, d) {
        return 'M.ciniki_courses_albums.album.save("M.ciniki_courses_albums.image.open(\'M.ciniki_courses_albums.album.addDropImageRefresh();\',M.ciniki_courses_albums.album.album_id,\'' + d.id + '\');");';
    };
    this.album.addDropImageRefresh = function(cb, aid, iid) {
        M.api.getJSONCb('ciniki.courses.albumGet', {'business_id':M.curBusinessID, 'album_id':this.album_id, 'images':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_albums.album;
            p.data.images = rsp.album.images;
            p.refreshSection('images');
            p.show();
        });
    }
    this.album.addDropImage = function(iid) {
        if( this.album_id == 0 ) {
            var c = this.serializeForm('yes');
            var rsp = M.api.postJSON('ciniki.courses.albumAdd', {'business_id':M.curBusinessID}, c);
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            this.album_id = rsp.id;
        }
        var rsp = M.api.getJSON('ciniki.courses.albumImageAdd', {'business_id':M.curBusinessID, 'image_id':iid, 'album_id':this.album_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    }
    this.album.open = function(cb, cid, oid, aid, list) {
        if( cid != null ) { this.course_id = cid; }
        if( oid != null ) { this.offering_id = oid; }
        if( aid != null ) { this.album_id = aid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.courses.albumGet', {'business_id':M.curBusinessID, 'album_id':this.album_id, 'images':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_albums.album;
            p.data = rsp.album;
            p.refresh();
            p.show(cb);
        });
    }
    this.album.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_albums.album.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.album_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.albumUpdate', {'business_id':M.curBusinessID, 'album_id':this.album_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.albumAdd', {'business_id':M.curBusinessID, 'course_id':this.course_id, 'offering_id':this.offering_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_albums.album.album_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.album.remove = function() {
        if( confirm('Are you sure you want to remove album?') ) {
            M.api.getJSONCb('ciniki.courses.albumDelete', {'business_id':M.curBusinessID, 'album_id':this.album_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_albums.album.close();
            });
        }
    }
    this.album.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.album_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_courses_albums.album.save(\'M.ciniki_courses_albums.album.open(null,' + this.nplist[this.nplist.indexOf('' + this.album_id) + 1] + ');\');';
        }
        return null;
    }
    this.album.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.album_id) > 0 ) {
            return 'M.ciniki_courses_albums.album.save(\'M.ciniki_courses_albums.album_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.album_id) - 1] + ');\');';
        }
        return null;
    }
    this.album.addButton('save', 'Save', 'M.ciniki_courses_albums.album.save();');
    this.album.addClose('Cancel');
    this.album.addButton('next', 'Next');
    this.album.addLeftButton('prev', 'Prev');
   
    //
    // The panel to edit Photo Album Image
    //
    this.image = new M.panel('Photo Album Image', 'ciniki_courses_albums', 'image', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.albums.image');
    this.image.data = null;
    this.image.albumimage_id = 0;
    this.image.course_id = 0;
    this.image.offering_id = 0;
    this.image.nplist = [];
    this.image.sections = {
        '_image':{'label':'Photo', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'required':'yes', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_albums.image.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_albums.image.albumimage_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_albums.image.remove();'},
            }},
        };
    this.image.fieldValue = function(s, i, d) { return this.data[i]; }
    this.image.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.albumImageHistory', 'args':{'business_id':M.curBusinessID, 'albumimage_id':this.albumimage_id, 'field':i}};
    }
    this.image.open = function(cb, aid, iid, list) {
        if( aid != null ) { this.album_id = aid; }
        if( iid != null ) { this.albumimage_id = iid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.courses.albumImageGet', {'business_id':M.curBusinessID, 'albumimage_id':this.albumimage_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_albums.image;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.image.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_albums.image.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.albumimage_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.albumImageUpdate', {'business_id':M.curBusinessID, 'albumimage_id':this.albumimage_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.albumImageAdd', {'business_id':M.curBusinessID, 'album_id':this.album_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_albums.image.albumimage_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.image.remove = function() {
        if( confirm('Are you sure you want to remove this image?') ) {
            M.api.getJSONCb('ciniki.courses.albumImageDelete', {'business_id':M.curBusinessID, 'albumimage_id':this.albumimage_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_albums.image.close();
            });
        }
    }
    this.image.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.albumimage_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_courses_albums.image.save(\'M.ciniki_courses_albums.image.open(null,null,' + this.nplist[this.nplist.indexOf('' + this.albumimage_id) + 1] + ');\');';
        }
        return null;
    }
    this.image.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.albumimage_id) > 0 ) {
            return 'M.ciniki_courses_albums.image.save(\'M.ciniki_courses_albums.albumimage_id.open(null,null,' + this.nplist[this.nplist.indexOf('' + this.albumimage_id) - 1] + ');\');';
        }
        return null;
    }
    this.image.addDropImage = function(iid) {
        this.setFieldValue('image_id', iid);
        return true;
    };
    this.image.addButton('save', 'Save', 'M.ciniki_courses_albums.image.save();');
    this.image.addClose('Cancel');
    this.image.addButton('next', 'Next');
    this.image.addLeftButton('prev', 'Prev');

    //
    // Start the app
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_albums', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb, args.course_id, args.offering_id);
    }
}
