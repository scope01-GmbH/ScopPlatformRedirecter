import './page/scop-platform-redirect-list';
import './page/scop-platform-redirect-details';
import './page/scop-platform-redirect-create';
import './page/scop-platform-redirect-import-export-modal';
import './page/scop-platform-redirect-import-export-activity';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('scop-platform-redirect', {
        entity: 'scop_platform_redirecter_redirect',
        type: 'plugin',
        name: 'scop-platform-redirect',
        title: 'scopplatformredirecter.general.title',
        description: 'scopplatformredirecter.general.title',
        color: '#019994',
        icon: 'regular-double-chevron-right-s',
        defaultSearchConfiguration: {
            _searchable: true,
            sourceURL: {
                _searchable: true,
                _score: 500,
            },
            targetURL: {
                name: {
                    _searchable: true,
                    _score: 500,
                },
            },
        },
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
        settingsItem: {
            group: 'content',
            to: 'scop.platform.redirect.list',
            icon: 'regular-double-chevron-right-s',
            privilege: 'system.system_config',
        },
        snippets: {
            'de-DE': deDE,
            'en-GB': enGB
        }
    }
);

const { Application } = Shopware;

Application.addServiceProviderDecorator('searchTypeService', searchTypeService => {
    searchTypeService.upsertType('scop_platform_redirecter_redirect', {
        entityName: 'scop_platform_redirecter_redirect',
        placeholderSnippet: 'scopplatformredirecter.general.searchAllRedirects',
        hideOnGlobalSearchBar: false
    });

    return searchTypeService;
});

Shopware.Component.override('sw-search-bar-item', () => import('../../app/component/structure/sw-search-bar-item'));

