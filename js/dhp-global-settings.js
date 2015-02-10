// DH Press Global Settings -- deals with global settings for Kiosk usage
// NOTES:   Variables in dhpGlobals are set in print_scripts() of dhp-class-settings.php
// ASSUMES: PHP code in dhp-class-settings has already embedded modal for tips in HTML

// USES:    JavaScript libraries jQuery, Zurb Foundation

jQuery(document).ready(function($) {
	var newDHPSettings = new dhpGlobalSettings($);
});


var dhpGlobalSettings = function($) {
	var userActivity = false, secondsInactive = 0, activeMonitorID, maxSecondsInactive, myMonitor;
	var kioskTablet = true;
	var blockLinks = [];
		//Detect user agent and determine if kiosk device
	var kioskRE = new RegExp(dhpGlobals.kiosk_useragent, "i");
	var kioskDevice = kioskRE.test(navigator.userAgent.toLowerCase());

		// Block links
	blockLinks = dhpGlobals.kiosk_blockurls.split(',');
	findAndBlockLinks(blockLinks);

		// Only execute if not loaded in iframe
	if (!inIframe()) {
			// Monitor user activity, only if setting given
		maxSecondsInactive = dhpGlobals.timeout_duration * 60;
		if ((maxSecondsInactive !== null) && (maxSecondsInactive !== '') && (maxSecondsInactive !== '0') && (maxSecondsInactive !== 0)) {
			console.log('start activity monitor')
			if (typeof(maxSecondsInactive) === "string") {
				maxSecondsInactive = parseFloat(maxSecondsInactive);
			}
			activeMonitorID = window.setInterval(monitorActivity.bind(this,10) , 10000);    // 1000 milliseconds = 1 sec * 60 sec = 1 minute
			addSiteListeners();
		}
	} // if (!inIframe())

		// PURPOSE: Block link, try a second time(map renders links after load)    
	function findAndBlockLinks(linksArray)
	{
		$.each(linksArray, function(index,val)
		{
			blockLink(val.trim());
			blockLinks[val.trim()] = setTimeout(function()
			{
				blockLink(val.trim());
			}, 5000);
		});
	} // findAndBlockLinks()

	function blockLink(link)
	{
		if (findLink(link)){
			clearTimeout(blockLinks[link]);
		}
	} // blockLink()

	function findLink(link)
	{
		if ($('a[href*="'+link+'"]').length >= 1) {
			$('a[href*="'+link+'"]').on('click', function(e) {
				e.preventDefault();
				// $('#externalModal').foundation('reveal', 'open',  link, {'crossDomain':true});
			});
			console.log('blocked '+link);
			return true;
		}       
	} // findLink()

		// PURPOSE: assign listeners for user activity
		// ASSUMES: Using window assigns events above the document(2 iframes + main page == 3 document bodys)
	function addSiteListeners() 
	{
		myMonitor = new IframeActivityMonitor({trackingInterval : 5000});
			// Add iframe listeners
		window.addEventListener('mousePositionChanged', function() {
			userActivity = true;
		}, false);
			// Toplevel mouse listeners
		$(document).on('mousemove',function() {
			userActivity = true;
		});
			// Toplevel keyboard listeners
		$(document).on('keypress', function() {
			userActivity = true;
		});
			// Start iframe monitor
		myMonitor.start();
	} // addSiteListeners()


		// PURPOSE: Called once a minute to see if user has done anything in that interval
	function monitorActivity(incrementAmt)
	{
			// Either increase or rest minutesInactive, based on user activity in last minute
		if (userActivity) {
			secondsInactive = 0;
		} else {
			secondsInactive+= incrementAmt;
		}
		userActivity = false;

		if (secondsInactive >= maxSecondsInactive) {
				// Redirect page to home
			if (document.location.href !== dhpGlobals.redirect_url) {
				document.location.href = dhpGlobals.redirect_url;
			}
				// If on home url scroll to top instead of reload
			else {
				$('#tipModal').foundation('reveal', 'close');
				if ($(window).scrollTop() > 0) {
					$("html, body").animate({scrollTop:0}, '500', 'swing', function() {});
				}
			}
		}
	} // monitorActivity()


		// PURPOSE: Check if script is loading in an iframe
	function inIframe () 
	{
		try {
			return window.self !== window.top;
		} catch(e) {
			return true;
		}
	} // inIframe()

}; // dhpGlobalSettings


///////tracking in IFRAME code////////////////

// https://github.com/aroder/IframeActivityMonitor
var IframeActivityMonitor = function(args) {
	args = args || {};
	
	// how often to compare current and old positions, in milliseconds
	var trackingInterval = args.trackingInterval || 1000;
	var oldPosition = {
		x: 0,
		y: 0
	};
	var trackingTimer;

	var eventHandlers = [];
	var start = function() {
		// add mouseover and mouseout listeners to every iframe
		var frames = document.getElementsByTagName('iframe');
		for (var i = 0; i < frames.length; i++) {

			// on mouseover, start tracking position within the iframe
			frames[i].addEventListener('mouseover', track, false);

			// on mouseout, stop tracking the mouse position within the iframe
			frames[i].addEventListener('mouseout', stopTracking, false);
		}
	};
	var stop = function() {
		var frames = document.getElementsByTagName('iframe');
		for (var i = 0; i < frames.length; i++) {
			frames[i].removeEventListener('mouseover', track, false);
			frames[i].removeEventListener('mouseout', stopTracking, false);
		}
	};
	var track = function(mouseEvent) {
		trackingTimer = setTimeout(function tracker() {
			//console.log('tracking');
			var frame = mouseEvent.target;
			var div = document.createElement('div');
			div.style.height = frame.clientHeight + 'px';
			div.style.width = frame.clientWidth + 'px';

			div.style.left = frame.offsetLeft + 'px';
			div.style.top = frame.offsetTop + 'px';
			div.style.position = 'absolute';
			//div.style.backgroundColor = '#000';
			div.innerHTML = '&nbsp;';
			document.body.appendChild(div);
			div.addEventListener('mouseover', function tracker2(mouseEvent) {
				var position = {
					x: mouseEvent.clientX,
					y: mouseEvent.clientY
				};
				
				// if the position is different than the old position, the
				// the mouse has moved within the iframe. Dispatch an event
				// on the window object
				if (position.x != oldPosition.x || position.y != oldPosition.y) {
					dispatchEvent();
				}
				oldPosition = position;
				div.parentNode.removeChild(div);
			}, false);

		}, trackingInterval);
	};
	var stopTracking = function(mouseEvent) {
		clearTimeout(trackingTimer);
	};

	var dispatchEvent = function() {
		var evt = document.createEvent('Event');
		evt.initEvent('mousePositionChanged', true, true);
		window.dispatchEvent(evt);
	};

	return {
		start: start,
		stop: stop
	};
};
////////////////end tracking code///////////////
	
