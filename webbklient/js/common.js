
// -----------------------------------------------------------------------------------------------------------
// set height of #content on load so maximize will work properly

$(document).ready(function() {
    var docHeight = $(document).height();
    var topBarHeight = $('#topbar').height();
    var wBarHeight = $('#widget_bar').height();
    var contentHeight = docHeight - topBarHeight - wBarHeight - 20; // 20 is for margins
    
    $('#content').css('height',contentHeight+'px');
});

// -----------------------------------------------------------------------------------------------------------
// common functions and variables for messages

message_current_position = -100;
message_start_position = -100; // message_current_position will be set to this value after completion
message_timer = null;
message_speed = 100;
message_tick = 20;
message_width = 500; // also in css

// function for widgets to display an ok-message (green)
function show_message(message)
{
    $('#message').html('<p>'+message+'</p>'+'<p>Click anywhere to close this message</p>');
    $('#message').css('top',message_current_position+'px');
    $('#message').addClass('ok');
    
    $('#fullpage_overlay').click(function(){ close_message(); $('#message').removeClass('ok'); });
    $('#message').click(function(){ close_message(); $('#message').removeClass('ok'); }); 
    
    start_message_animate();
}

// function for widgets to display an error-message (red)
function show_errormessage(message)
{
    $('#message').html('<p>'+message+'</p>'+'<p>Click anywhere to close this message</p>'); 
    $('#message').css('top',message_current_position+'px');
    $('#message').addClass('error');
    
    $('#fullpage_overlay').click(function(){ close_message(); $('#message').removeClass('error'); });
    $('#message').click(function(){ close_message(); $('#message').removeClass('error'); }); 
    
    start_message_animate();
}

// common function to set timer and start animate
function start_message_animate()
{
    var maxWidth = $('#content').width();
    var centerPosition = (maxWidth/2)-(message_width/2);
    $('#message').css('left',centerPosition+'px');
    $('#message').css('top',message_start_position+'px');
    
    $('#fullpage_overlay').show(); 
    
    message_timer = setInterval('message_animate()', message_speed);
    $('#message').fadeIn(message_speed);
}

// callback function for timer
function message_animate()
{
    if (message_current_position<0)
    {
        message_current_position += message_tick;
        $('#message').css('top',message_current_position+'px');    
    }
    else
    {
        reset_message();
    }
}

// hide div for message and overlay
function close_message()
{
    $('#fullpage_overlay').hide();
    $('#message').hide();    
}

// reset timer and position for a new round
function reset_message()
{
    clearInterval(message_timer);    
    message_timer = null;
    message_current_position = message_start_position;  
}


// -----------------------------------------------------------------------------------------------------------
// common functions for widgets


// function to open a widget
function open_widget(widgetCallback, widgetIconId, wObject)
{
    // which state?
    var state = $('#'+widgetIconId).attr('state');
    if ( state == "" )
    {
        // no state!
        
        // set callbacks for minimize and close
        eval(wObject+'.onMinimize = function(){ close_widget("'+widgetIconId+'", "'+wObject+'"); }');
        eval(wObject+'.onClose = function(){ reset_widget("'+widgetIconId+'"); }'); 
        
        // run callback to open widget
        eval(wObject+'.open()');
        
        // set state as open and transparency for icon to 20%
        $('#'+widgetIconId).attr('state', 'open');
        $('#'+widgetIconId).css({ 'opacity':'0.2', '-ms-filter':'"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)"', 'filter':'alpha(opacity=20)' });
    }
    
}

// callback for minimize
function close_widget(widgetIconId, wObject)
{
    // close widget
    eval(wObject+'.wnd.close()');
    
    // reset icon
    reset_widget(widgetIconId);    
}

// callback for close
function reset_widget(widgetIconId)
{
    // set state to none and set transparency to 100%
    $('#'+widgetIconId).attr('state', '');
    $('#'+widgetIconId).css({ 'opacity':'1.0', '-ms-filter':'"progid:DXImageTransform.Microsoft.Alpha(Opacity=100)"', 'filter':'alpha(opacity=100)' });
}