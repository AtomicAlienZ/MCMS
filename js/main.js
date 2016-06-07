function setCookie(name, value, days) {
	value = encodeURIComponent(value);
	//URLEnocder.encoder(name);
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
	}
	else {
		var expires = "";
	}
	document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1, c.length);
		}
		if (c.indexOf(nameEQ) == 0) {
			return decodeURIComponent(c.substring(nameEQ.length, c.length));
		}
	}
	return null;
}

function clear_cookies() {
	setCookie("filter", "");
	run_filter();
}

$(function(){
	$(document).currencyConverter({
		defaultCurrency: 'USD',
		conversionTable: {
			USD: 1,
			EUR: 1.1
		},
		currencyType: 'symbol',
		roundingFunction: Math.round,
		useCache: false
	});
});