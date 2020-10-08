import './page/scop_platform_redirect_list';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('scop_platform_redirect', {
	color: '#2288FF',
	icon: 'small-arrow-large-double-right',
	title: 'Redirects',
	description: 'XXX',
	name: 'scop_platform_redirect',
	type: 'plugin',
	routes: {
		list: {
			component: 'scop_platform_redirect_list',
			path: 'list'
		}
	},
	navigation: [{
		label: 'scopplatformredirecter.general.mainMenuItem',
		color: '#2288FF',
		path: 'scope.platform.redirect',
		position: 100
	}],
	snippets: {
		'de-DE': deDE,
		'en-GB': enGB
	}
}
);