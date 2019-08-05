Modernizr.load([
	{
		test: window.matchMedia,
		nope: ["themes/default/js/vendor/matchMedia.js?version="+window['_PS_VERSION_'], "themes/default/js/vendor/matchMedia.addListener.js?version="+window['_PS_VERSION_']]
	},
	"themes/default/js/vendor/enquire.min.js?version="+window['_PS_VERSION_'],
	"themes/default/js/admin-theme.js?version="+window['_PS_VERSION_'],
]);
