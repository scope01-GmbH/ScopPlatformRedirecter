import './page/scop-platform-redirect-list';
import './page/scop-platform-redirect-details';
import './page/scop-platform-redirect-create';
import './page/scop-platform-redirect-import-export-modal';
import './page/scop-platform-redirect-import-export-activity';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

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
                path: 'details/:id',
                meta: {
                    parentPath: 'scop.platform.redirect.list'
                }
            },
            create: {
                component: 'scop-platform-redirect-create',
                path: 'create',
                meta: {
                    parentPath: 'scop.platform.redirect.list'
                }
            },
        },
        settingsItem: [{
            to: 'scop.platform.redirect.list',
            group: 'shop',
            icon: 'regular-double-chevron-right-s'
        }],
        snippets: {
            'de-DE': deDE,
            'en-GB': enGB
        }
    }
);
