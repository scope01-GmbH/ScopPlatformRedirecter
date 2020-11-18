import './page/scop-platform-redirect-list';
import './page/scop-platform-redirect-details';
import './page/scop-platform-redirect-create';
import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('scop-platform-redirect', {
	type: 'plugin',
	name: 'scop-platform-redirect',
	title: 'scopplatformredirecter.general.title',
	description: 'scopplatformredirecter.general.title',
	color: '#019994',
	icon: 'small-copy',
	routes: {
		list: {
			component: 'scop-platform-redirect-list',
			path: 'list'
		},
		details: {
			component: 'scop-platform-redirect-details',
			path: 'details'
		},
		create: {
			component: 'scop-platform-redirect-create',
			path: 'create',
            meta: {
                parentPath: 'scop.platform.redirect.list'
            }
		}
	},
	settingsItem: [{
		to: 'scop.platform.redirect.list',
		group: 'shop',
		icon: 'small-arrow-large-double-right'
	}],
	snippets: {
		'de-DE': deDE,
		'en-GB': enGB
	}
}
);
