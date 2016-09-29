if (window.cashmusic) {
	var cm = window.cashmusic;
	cm.events.add(cm,'ready',function(e) {
		// handle first visit use cases
		if (cm.styles.hasClass(document.body,'home')) {
			if(!window.location.hash) {
				if (!localStorage.getItem('firstvisit')) {
					localStorage.setItem('firstvisit',1);
					setTimeout(function() {
						cm.styles.addClass(document.body,'firstvisit');
					}, 1300);
				}
			}
		} else {
			setTimeout(function() {
				cm.styles.addClass(document.body,'firstvisit');
			}, 500);
		}

		// make the menu toggles work
		var toggles = document.getElementsByClassName('menutoggle');
		Array.prototype.filter.call(toggles, function(toggle){
			toggle.addEventListener("click", function(e) {
				if (cm.styles.hasClass(toggle.parentNode,'hide')) {
					cm.styles.removeClass(toggle.parentNode,'hide');
				} else {
					cm.styles.addClass(toggle.parentNode,'hide');
				}
			}, false);
		});

		var toggles = document.getElementsByClassName('tab');
		Array.prototype.filter.call(toggles, function(toggle){
			toggle.addEventListener("click", function(e) {
				if (cm.styles.hasClass(toggle,'show-recent')) {
					cm.styles.removeClass(toggle.parentNode.parentNode,'show-recent');
					cm.styles.removeClass(toggle.parentNode.parentNode,'show-popular');
					cm.styles.addClass(toggle.parentNode.parentNode,'show-recent');	
				} 
				if (cm.styles.hasClass(toggle,'show-popular')) {
					cm.styles.removeClass(toggle.parentNode.parentNode,'show-popular');
					cm.styles.removeClass(toggle.parentNode.parentNode,'show-recent');
					cm.styles.addClass(toggle.parentNode.parentNode,'show-popular');
				} 

			}, false);
		});

		// add a 'reading' class when scrolled down
		if ((document.documentElement.scrollTop || document.body.scrollTop) > 130 && !cm.styles.hasClass(document.body,'reading')) {
			cm.styles.addClass(document.body,'reading');
		}
		window.onscroll = function() {
			if ((document.documentElement.scrollTop || document.body.scrollTop) > 130 && !cm.styles.hasClass(document.body,'reading')) {
				cm.styles.addClass(document.body,'reading');
			}
			if ((document.documentElement.scrollTop || document.body.scrollTop) < 130 && cm.styles.hasClass(document.body,'reading')) {
				cm.styles.removeClass(document.body,'reading');
			}
		};
	});
}
