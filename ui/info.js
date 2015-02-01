//
// The courses app to manage an artists collection
//
function ciniki_courses_info() {
	this.init = function() {
		//
		// Setup the main panel to list the collection
		//
		this.menu = new M.panel('Files',
			'ciniki_courses_info', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.courses.info.menu');
		this.menu.data = {};
		this.menu.sections = {
			'_menu':{'label':'', 'list':{
				'registration':{'label':'Course Registration', 'fn':'M.ciniki_courses_info.showRegistration(\'M.ciniki_courses_info.showMenu();\');'},
				}},
			};
		this.menu.addClose('Back');

		//
		// The panel to display the course form
		//
		this.course = new M.panel('Registration Registration',
			'ciniki_courses_info', 'course',
			'mc', 'medium', 'sectioned', 'ciniki.courses.info.course');
		this.course.data = {};	
		this.course.sections = {
			'course-registration-details-html':{'label':'Registration Details', 'type':'htmlcontent'},
			'course-registration-more-details-html':{'label':'More information', 'type':'htmlcontent'},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_courses_info.showEditRegistration(\'M.ciniki_courses_info.showRegistration();\');'},
				}},
			'registrations':{'label':'Registration Forms',
				'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add Registration',
				'addFn':'M.ciniki_courses_info.showAddFile(\'M.ciniki_courses_info.showRegistration();\',1);',
				}
		};
		this.course.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return d.file.name; }
		};
		this.course.rowFn = function(s, i, d) {
			return 'M.ciniki_courses_info.showEditFile(\'M.ciniki_courses_info.showRegistration();\', \'' + d.file.id + '\');'; 
		};
		this.course.sectionData = function(s) { 
			return this.data[s];
		};
		this.course.addClose('Back');

		//
		// The panel to display the edit course details form
		//
		this.editcourse = new M.panel('Registration',
			'ciniki_courses_info', 'editcourse',
			'mc', 'medium', 'sectioned', 'ciniki.courses.info.editcourse');
		this.editcourse.file_id = 0;
		this.editcourse.data = null;
		this.editcourse.sections = {
			'_description':{'label':'Registration Details', 'type':'simpleform', 'fields':{
				'course-registration-details':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
			}},
			'_post_description':{'label':'More information', 'type':'simpleform', 'fields':{
				'course-registration-more-details':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
			}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_courses_info.saveRegistration();'},
			}},
		};
		this.editcourse.fieldValue = function(s, i, d) { 
			return this.data[i]; 
		}
		this.editcourse.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.courses.settingsHistory', 'args':{'business_id':M.curBusinessID, 'setting':i}};
		};
		this.editcourse.addButton('save', 'Save', 'M.ciniki_courses_info.saveRegistration();');
		this.editcourse.addClose('Cancel');

		//
		// The panel to display the add form
		//
		this.addfile = new M.panel('Add File',
			'ciniki_courses_info', 'addfile',
			'mc', 'medium', 'sectioned', 'ciniki.courses.info.editfile');
		this.addfile.default_data = {'type':'1'};
		this.addfile.data = {};	
		this.addfile.sections = {
			'_file':{'label':'File', 'fields':{
				'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
			}},
			'info':{'label':'Information', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
			}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_courses_info.addFile();'},
			}},
		};
		this.addfile.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) {
				return this.data[i]; 
			} 
			return ''; 
		};
		this.addfile.addButton('save', 'Save', 'M.ciniki_courses_info.addFile();');
		this.addfile.addClose('Cancel');

		//
		// The panel to display the edit form
		//
		this.editfile = new M.panel('File',
			'ciniki_courses_info', 'editfile',
			'mc', 'medium', 'sectioned', 'ciniki.courses.info.editfiles');
		this.editfile.file_id = 0;
		this.editfile.data = null;
		this.editfile.sections = {
			'info':{'label':'Details', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
			}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_courses_info.saveFile();'},
				'download':{'label':'Download', 'fn':'M.ciniki_courses_info.downloadFile(M.ciniki_courses_info.editfile.file_id);'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_courses_info.deleteFile();'},
			}},
		};
		this.editfile.fieldValue = function(s, i, d) { 
			return this.data[i]; 
		}
		this.editfile.sectionData = function(s) {
			return this.data[s];
		};
		this.editfile.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.courses.fileHistory', 'args':{'business_id':M.curBusinessID, 'file_id':this.file_id, 'field':i}};
		};
		this.editfile.addButton('save', 'Save', 'M.ciniki_courses_info.saveFile();');
		this.editfile.addClose('Cancel');
	}

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_courses_info', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}

		if( args.showregistration != null && args.showregistration == 'yes' ) {
			this.showRegistration(cb);
		} else {
			this.showMenu(cb);
		}
	}

	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	};

	this.showRegistration = function(cb) {
		this.course.data = {};
		M.startLoad();
		var rsp = M.api.getJSONCb('ciniki.courses.settingsGet', 
			{'business_id':M.curBusinessID, 'processhtml':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.stopLoad();
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_courses_info.course;
				if( rsp.settings != null && rsp.settings['course-registration-details'] != null ) {
					p.data['course-registration-details-html'] = rsp.settings['course-registration-details-html'];
				} else {
					p.data['course-registration-details-html'] = '';
				}
				if( rsp.settings != null && rsp.settings['course-registration-more-details'] != null ) {
					p.data['course-registration-more-details-html'] = rsp.settings['course-registration-more-details-html'];
				} else {
					p.data['course-registration-more-details-html'] = '';
				}
				var rsp = M.api.getJSON('ciniki.courses.fileList', {'business_id':M.curBusinessID, 'type':'1'});
				if( rsp.stat != 'ok' ) {
					M.stopLoad();
					M.api.err(rsp);
					return false;
				}
				p.data.registrations = rsp.files;
				M.stopLoad();
				p.refresh();
				p.show(cb);
			});
	};

	this.showEditRegistration = function(cb) {
		this.editcourse.data = {};
		var rsp = M.api.getJSONCb('ciniki.courses.settingsGet', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_courses_info.editcourse;
				if( rsp.settings != null && rsp.settings['course-registration-details'] != null ) {
					p.data['course-registration-details'] = rsp.settings['course-registration-details'];
				} else {
					p.data['course-registration-details'] = '';
				}
				if( rsp.settings != null && rsp.settings['course-registration-more-details'] != null ) {
					p.data['course-registration-more-details'] = rsp.settings['course-registration-more-details'];
				} else {
					p.data['course-registration-more-details'] = '';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.saveRegistration = function() {
		var c = this.editcourse.serializeFormData('no');
		if( c != null ) {
			var rsp = M.api.postJSONFormData('ciniki.courses.settingsUpdate', 
				{'business_id':M.curBusinessID}, c,
				function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} else {
						M.ciniki_courses_info.editcourse.close();
					}
				});
		} else {
			M.ciniki_courses_info.editcourse.close();
		}
	};

	this.showAddFile = function(cb, type) {
		this.addfile.reset();
		this.addfile.data = {'type':type};
		this.addfile.refresh();
		this.addfile.show(cb);
	};

	this.addFile = function() {
		var c = this.addfile.serializeFormData('yes');

		if( c != '' ) {
			var rsp = M.api.postJSONFormData('ciniki.courses.fileAdd', 
				{'business_id':M.curBusinessID, 'type':this.addfile.data.type}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} else {
							M.ciniki_courses_info.addfile.close();
						}
					});
		} else {
			M.ciniki_courses_info.addfile.close();
		}
	};

	this.showEditFile = function(cb, fid) {
		if( fid != null ) {
			this.editfile.file_id = fid;
		}
		var rsp = M.api.getJSONCb('ciniki.courses.fileGet', {'business_id':M.curBusinessID, 
			'file_id':this.editfile.file_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_courses_info.editfile;
				p.data = rsp.file;
				p.refresh();
				p.show(cb);
			});
	};

	this.saveFile = function() {
		var c = this.editfile.serializeFormData('no');

		if( c != '' ) {
			var rsp = M.api.postJSONFormData('ciniki.courses.fileUpdate', 
				{'business_id':M.curBusinessID, 'file_id':this.editfile.file_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} else {
							M.ciniki_courses_info.editfile.close();
						}
					});
		} else {
			this.editfile.close();
		}
	};

	this.deleteFile = function() {
		if( confirm('Are you sure you want to delete \'' + this.editfile.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
			var rsp = M.api.getJSONCb('ciniki.courses.fileDelete', {'business_id':M.curBusinessID, 
				'file_id':M.ciniki_courses_info.editfile.file_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_courses_info.editfile.close();
				});
		}
	};

	this.downloadFile = function(fid) {
		M.api.openFile('ciniki.courses.fileDownload', {'business_id':M.curBusinessID, 'file_id':fid});
	};
}
