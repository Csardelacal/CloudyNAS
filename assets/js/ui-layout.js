(function () {
	
	var containerHTML = document.querySelector('.contains-sidebar');
	var sidebarHTML   = containerHTML.querySelector('.sidebar');

	/*
	 * Scroll listener for the sidebar______________________________________
	 *
	 * This listener is in charge of making the scroll bar both stick to the
	 * top of the viewport and the bottom of the viewport / container
	 */
	 var wh  = window.innerHeight;
	 var ww  = window.innerWidth;
	 
	 /*
	  * This function quickly allows the application to check whether it should 
	  * consider the browser it is running in as a mobile viewport.
	  * 
	  * @returns {Boolean}
	  */
	 var mobile = function () {
		 return ww < 960;
	 };
	 
	 /*
	  * This helper allows the application to define listeners that will prevent
	  * the application from hogging system resources when a lot of events are 
	  * fired.
	  * 
	  * @param {type} fn
	  * @returns {Function}
	  */
	 var debounce = function (fn, interval) {
		var timeout = undefined;

		return function () {
			if (timeout) { return; }
			var args = arguments;
			
			timeout = setTimeout(function () {
				fn.apply(window, args);
				timeout = undefined;
			}, interval || 50);
		};
	 };
	 
	 /**
	  * On Scroll, our sidebar is resized automatically to fill the screen within
	  * the boundaries of the container.
	  * 
	  * @returns {undefined}
	  */
	var scrollListener  = function () { 
		/*
		 * Collect the constraints from the parent element to consider where the 
		 * application is required to redraw the child.
		 * 
		 * @type type
		 */
		var constraints = containerHTML.parentNode.getBoundingClientRect();
		
		/*
		 * There's a special scenario, in which the system may have enough space 
		 * to extend the sidebar to the next sibling's starting point.
		 * 
		 * @type .document@call;querySelector.parentNode.nextSibling
		 */
		var nextSibling = containerHTML.parentNode.nextElementSibling;
		var limit       = nextSibling? Math.max(nextSibling.getBoundingClientRect().top, constraints.bottom) : wh;
		
		var height = mobile()? wh : Math.min(wh, limit) - Math.max(constraints.top, 0);
		
		/*
		 * This flag determines whether the scrolled element is past the viewport
		 * and therefore we need to "detach" the sidebar so it will follow along
		 * with the scrolling user.
		 * 
		 * @type Boolean
		 */
		var detached = constraints.top < 0;
		
		containerHTML.style.height = Math.max(height, constraints.height) + 'px';
		sidebarHTML.style.height   = height + 'px';
		sidebarHTML.style.width    = mobile()? '240px' : containerHTML.scrollWidth + 'px';
		
		containerHTML.style.top    = mobile() || detached?   '0px' : Math.max(0, 0 - constraints.top) + 'px';
		sidebarHTML.style.position = mobile() || detached? 'fixed' : 'static';
	};

	document.addEventListener('scroll', debounce(scrollListener, 25), false);
	scrollListener();

	 var resizeListener  = function () {
		//Reset the size for window width and height that we collected
		wh  = window.innerHeight;
		ww  = window.innerWidth;
		
		//List the toggle buttons
		var tb = document.querySelectorAll('.target-button');
		
		//For mobile devices we toggle to collapsable mode
		if (ww < 960 + 200) {
			containerHTML.classList.add('collapsed');
			containerHTML.classList.remove('visible');
			for (var i = 0; i < tb.length; i++) { tb[i].firstChild.classList.remove('hidden'); }
			//Show the toggle button
		} 
		else {
			containerHTML.classList.remove('collapsed');
			containerHTML.classList.add('visible');
			
			for (var i = 0; i < tb.length; i++) { 
				var method = containerHTML.classList.contains('collapsable')? 'remove' : 'add';
				tb[i].classList[method]('hidden'); 
			}
		}
		
		/**
		 * We ping the scroll listener to redraw the the UI for it too.
		 */
		scrollListener();
	 };

	 window.addEventListener('resize', debounce(resizeListener), false);
	 resizeListener();

	/*
	 * Defer the listener for the toggles to the document.
	 */
	document.addEventListener('click', function(e) { 
		if (!e.target.classList.contains('toggle-button')) { return; }
		containerHTML.classList.toggle('collapsed');
		containerHTML.classList.toggle('visible');
	}, false);

	containerHTML.addEventListener('click', function() { containerHTML.classList.add('collapsed'); containerHTML.classList.remove('visible'); }, false);
	sidebarHTML.addEventListener('click', function(e) { e.stopPropagation(); }, false);

}());

(function () {
	var stickies = Array.prototype.slice.call(document.querySelectorAll('.sticky'));
	var current  = null;
	var clone    = null;
	var invAt    = [0, 0];


	var listener = function () {
		var candidate = null;
		var next      = null;

		if (window.pageYOffset >= invAt[0] && window.pageYOffset <= invAt[1]) {
			return;
		}

		if (current) {
			clone.parentNode.removeChild(clone);
			current = clone = null;
			invAt = [0, 0];
		}

		for (var i = 0; i < stickies.length; i++) {
			var sticky = stickies[i];
			var rect   = sticky.getBoundingClientRect();

			if (rect.top < 0) {
				candidate = sticky;
				next      = stickies[i+1];
			}
		}

		if (candidate) {
			if (current !== null) {
				clone.parentNode.removeChild(clone);
				clone = current = null;
				invAt = [0, 0];
			}

			var parent = candidate.parentNode.getBoundingClientRect();
			var rect   = candidate.getBoundingClientRect();
			var nxtrect= next? next.getBoundingClientRect() : null;
			var top    = Math.min(parent.top + parent.height - rect.height, next? nxtrect.top - rect.height : 0, 0);

			invAt[0] = top? window.pageYOffset : window.pageYOffset + rect.top;
			invAt[1] = next? window.pageYOffset + nxtrect.top - rect.height : window.pageYOffset + parent.top + parent.height - rect.height;

			current  = candidate;
			clone    = candidate.cloneNode(true);
			clone.style.position = 'fixed';
			clone.style.left     = rect.left + 'px';
			clone.style.top      = top + 'px';
			clone.style.width    = rect.width + 'px';

			document.body.appendChild(clone);
		}


	};

	var debounce = null;
	document.addEventListener('scroll', function () {
		if (debounce) { return; }
		debounce = setTimeout(function () { debounce = null; listener(); }, 10);
	}, false);
}());
