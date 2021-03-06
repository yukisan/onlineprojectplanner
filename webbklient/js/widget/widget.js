/* 
* Name: Widget
* Desc: Basewidget class used as parent to executable widgets.
* Last update: 3/2-2011 by Dennis Sangmo
*/
function Widget() {
}

// Constructor-function. Must be executed from the dev-widget constructor.
Widget.prototype.create = function(id, wnd_options, partialClasses) {
	
	// Property assignment
	this.id = id; // The Id of the instance
	this.widgetIconId = wnd_options.widgetIconId; // Icon id
	this.divId = "widget_" + id; // The div-id of this instance
	this.dialogId = this.divId + "_dialog";
	this.settingsOpen = false;
	
	// An array containing all partial areas in the window for better updating of smaller parts.
	this.partialClassNames = new Array(); 
	if (partialClasses != undefined) {
		if($.isArray(partialClasses)) {
			this.partialClassNames = partialClasses;
		} else {
			this.partialClassNames.push(partialClasses);
		}
	}
	
	// Starting JQuery-window object
	if(wnd_options.content == undefined) {
		wnd_options.content = "<div class=\"widget_window\" id=\"" + this.divId + "\"></div>";
	} else {
		wnd_options.content = "<div class=\"widget_window\" id=\"" + this.divId + "\">" + wnd_options.content + "</div>";
	}
	
	// any saved data (internal)
	var saved_widget_data = Desktop.getWidgetData(id);
	if (saved_widget_data != false && typeof saved_widget_data == 'object')
	{
		
		// new name?
		if ( saved_widget_data.last_name != undefined && saved_widget_data.last_name != "") {
		    wnd_options.title = saved_widget_data.last_name;
		}
		
		// override width and height if resizable is not false?
		if (wnd_options.resizable != false) {
			
			// override width and height from widget constructor and use values from database
			if ( saved_widget_data.last_position != undefined && saved_widget_data.last_position.width != 0 && saved_widget_data.last_position.height != 0) {
				wnd_options.width = saved_widget_data.last_position.width;
				wnd_options.height = saved_widget_data.last_position.height;
			}
		}
	}


	// create window and save result
	this.wnd = $('#desktop').window(wnd_options);
	
	// add settings?
	if(wnd_options.allowSettings) {
		this.wnd.setFooterContent("<a href=\"javascript:void(0);\" onclick=\"Desktop.openSettingsWindow(" + this.id + ")\"><img src='"+BASE_URL+"images/buttons/small_setting.jpg' alt='Settings' /></a>");
	}
}

// returns the jquery-windowobject
Widget.prototype.getWindowObject = function() {
	return this.wnd;
}

// Closes the widgetwindow
Widget.prototype.closeWidget = function() {
    this.wnd.close();    
}

// Will set the content in the widget
Widget.prototype.index = function() {
	this.setContent("<h1>You need to implement an function named \"index\" in your widget-class!</h1>");
}

// Will set the content in the widget. Can handle partialareas
Widget.prototype.setWindowContent = function(args) {
	if($.isArray(args)){
		if($.inArray(args[1], this.partialClassNames) >= 0) {
			var element;
			element = $('#' + this.divId).find('.' + args[1]);
			if(element.length == 0){
				element = $('#' + this.divId).find('#' + args[1]);
			}
			element.html(args[0]);
		}
	} else {
		$('#' + this.divId).html(args);
	}
}

// Standard statuscatcher
Widget.prototype.catchStatus = function(data) {
	
	var json;
	try {
		
		// try to parse as json
		json = $.parseJSON(data);	
		
	} catch (err) {
		
		// json failed to parse; catch error
		Desktop.show_errormessage('Unkown error occured: '+err);
		return;
	}

	// check result
	if(json.status == "ok") {
		Desktop.show_message(json.status_message);
		// Calling the requested function
		if(json.load != undefined) {
			this[json.load](json.loadparams);
		}
	} else {
		Desktop.show_errormessage(json.status_message);
	}

}

/*
* ---------------------------------------------
* SETTINGS
* ---------------------------------------------
*/

// Opens (creates if needed) the settings window
Widget.prototype.setSettingsContent = function(data) {
	if($('#' + this.divId).next('#settings').length == 0) {
		$('#' + this.divId).after('<div id="settings"></div>');
	}
	
	$('#' + this.divId).next('#settings').html(data);
	$('#' + this.divId).fadeOut('1000');
	$('#' + this.divId).next('#settings').fadeIn('1000');
	this.settingsOpen = true;
}

// Closes the settingswindow
Widget.prototype.closeSettings = function() {
	$('#' + this.divId).next('#settings').fadeOut('1000');
	$('#' + this.divId).fadeIn('1000');
	this.settingsOpen = false;
}


// returns the settingswindow state.
Widget.prototype.getSettingsState = function() {
	return this.settingsOpen;
}

/*
* ---------------------------------------------
* AJAX Functions
* ---------------------------------------------
*/

// display a ajax spinner
Widget.prototype.show_ajax_loader = function() {
    // class frame_loading is from jquery.window 
    container = $('#' + this.divId);
    
	// no content; show white
	var loadingHTML = "<div class='frame_loading'>Loading...</div>"; 
	container.html(loadingHTML);
	var loading = container.children(".frame_loading");
	loading.css("marginLeft",    '-' + (loading.outerWidth() / 2) -20 + 'px');
}

// display an error (jquery ui)
Widget.prototype.show_ajax_error = function(loadURL, errorIcon) {
    // prepare message
    var errorMessage = "<p class=\"ajaxTemplateWidget_Error\">";
	errorMessage += "<img src=\""+Desktop._errorIcon+"\" width=\"35\" height=\"35\" />";
	
    // append message
    errorMessage += "Error: Unable to load the page at<br/><br/><small>"+loadURL+"</small></p>";

    // show in div with ID or Class
	$('#'+this.divId).html(errorMessage);
}
