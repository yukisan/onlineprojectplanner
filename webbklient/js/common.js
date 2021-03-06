
// -----------------------------------------------------------------------------------------------------------
// set height of #content on load so maximize will work properly

$(window).load(function() {
    // set size on load
    setContentSize();
    
    // browser resize
    $(window).resize(function() {
        setContentSize();
    });
});

function setContentSize()
{
    var docHeight = $(document).height();
    var topBarHeight = $('#topbar').outerHeight(true);
    var wBarHeight = $('#widget_bar').outerHeight(true);
    var contentHeight;

    if($('#topbar').is(':visible') == false && $('#widget_bar').is(':visible') == false)
    {
        contentHeight = docHeight - 10; // 10 is for margins
    }
    else if($('#topbar').is(':visible') == false && $('#widget_bar').is(':visible') != false)
    {
        contentHeight = (docHeight - wBarHeight) - 10; // 10 is for margins
    }
    else if($('#widget_bar').is(':visible') == false)
    {
        contentHeight = (docHeight - topBarHeight) - 10; // 10 is for margins
    }
    else
    {
        contentHeight = ((docHeight - topBarHeight) - wBarHeight) - 20; // 20 is for margins
    }
    
    $('#desktop').css('height',contentHeight+'px');  
}


