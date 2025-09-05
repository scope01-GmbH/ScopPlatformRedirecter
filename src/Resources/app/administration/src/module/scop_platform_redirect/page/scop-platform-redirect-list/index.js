import template from './scop-platform-redirect-list.html.twig';

const {Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const {cloneDeep} = Shopware.Utils.object;
var inAppPurchaseId = 'scopPlatformRedirecterPremium';

Shopware.Component.register('scop-platform-redirect-list', {
    template,

    inject: [
        'repositoryFactory',
        'syncService',
        'loginService',
        'importExport',
        'numberRangeService',
        'acl',
        'filterFactory',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            repository: null,
            redirect: null,
            exportLoading: false,
            isLoading: false,
            noRedirect: true,
            showImportExportModal: false,
            modalType: 'export',
            page: 1,
            limit: 25,
            searchConfigEntity: 'scop_platform_redirecter_redirect',
            entitySearchable: true,
            total: 0,
            term: ''
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        inAppActive() {
            let active = true;
            if (!Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId)) {
                active = false;
            }
            return active;
        },
        redirectRepository() {
            return this.repositoryFactory.create('scop_platform_redirecter_redirect');
        },
        redirectCriteria() {
            const redirectCriteria = new Criteria(this.page, this.limit);
            if (Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId)) {
                redirectCriteria.setTerm(this.term);
            }

            return redirectCriteria;
        },
        columns() {
            return [{
                property: 'sourceURL',
                dataIndex: 'sourceURL',
                label: this.$tc('scopplatformredirecter.list.columnSourceUrl'),
                routerLink: 'scop.platform.redirect.details',
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'targetURL',
                dataIndex: 'targetURL',
                label: this.$tc('scopplatformredirecter.list.columnTargetUrl'),
                inlineEdit: 'string',
                allowResize: true
            }, {
                property: 'httpCode',
                dataIndex: 'httpCode',
                label: this.$tc('scopplatformredirecter.list.columnHttpCode'),
                allowResize: true
            }, {
                property: 'enabled',
                dataIndex: 'enabled',
                label: this.$tc('scopplatformredirecter.list.columnEnabled'),
                inlineEdit: 'boolean'
            }, {
                property: 'queryParamsHandling',
                dataIndex: 'queryParamsHandling',
                label: this.$tc('scopplatformredirecter.list.columnQueryParamsHandling'),
                allowResize: true
            }, {
                property: 'salesChannel',
                dataIndex: 'salesChannel',
                label: this.$tc('scopplatformredirecter.list.salesChannel'),
                allowResize: true
            },
            ];
        }
    },

    watch: {
        redirectCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },
    },

    methods: {
        updateCriteria(criteria) {
            this.page = 1;
        },
        async getList() {
            this.isLoading = true;

            try {
                let criteria = await this.addQueryScores(this.term, this.redirectCriteria);
                // disable search if in app purchase is inactive
                if (!Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId)) {
                    criteria = await this.addQueryScores('', this.redirectCriteria);
                }
                const result = await Promise.all([
                    this.redirectRepository.search(criteria),
                ]);

                const redirect = result[0];

                this.total = redirect.total;
                this.redirect = redirect;

                this.isLoading = false;

                this.selection = {};
            } catch {
                this.isLoading = false;
            }
        },
        onClickExport() {
            this.modalType = 'export';
            this.showImportExportModal = true;
        },
        updateTotal(records) {
            this.noRedirect = records.length === 0;
        },
        onClickImport() {
            this.modalType = 'import';
            this.showImportExportModal = true;
        },
        onClickCheckLinks() {
            const client = Shopware.Application.getContainer('init').httpClient;
            const headers = {
                headers: {
                    Authorization: `Bearer ${Shopware.Service('loginService').getToken()}`,
                },
            };
            console.log(headers)
            client
                .post(
                    `/_admin/scop-check-redirects`,
                    {},
                    {
                        headers
                    },
                )
                .then((response) => {
                    console.log(response)
                })
                .catch((error) => {
                    if (error instanceof CanceledError) {
                        return {};
                    }
                    throw error;
                });
        },
        closeImportExport() {
            this.showImportExportModal = false;
        },
        updateList() {
            const criteria = this.redirect.criteria;

            this.repository.search(criteria, Shopware.Context.api).then((result) => {
                this.redirect = result;
            });
        },
        onInlineEditSave(promise, redirect) {
            if (redirect.sourceURL.trim() === redirect.targetURL.trim() && redirect.enabled) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription')
                });

                redirect.enabled = false;
                redirect._origin.enabled = true;

                promise.then(() => {
                    this.repository.save(redirect, Shopware.Context.api).then(() => {
                        return this.updateList();
                    });
                });
                return;
            }

            if (!redirect.sourceURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptySourceURL')
                });
                this.updateList();
                return;
            }
            if (!redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptyTargetURL')
                });
                this.updateList();
                return;
            }

            return promise
                .catch(() => {
                    this.updateList();
                    this.createNotificationError({
                        message: this.$tc('global.notification.notificationSaveErrorMessageRequiredFieldsInvalid'),
                    });
                });
        }
    },

});
