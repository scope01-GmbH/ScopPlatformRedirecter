import template from './scop-platform-redirect-list.html.twig';

const Criteria = Shopware.Data.Criteria;

const {Component, Mixin} = Shopware;

Shopware.Component.register('scop-platform-redirect-list', {
    template,

    inject: [
        'repositoryFactory', 'syncService', 'loginService', 'importExport'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            repository: null,
            redirect: null,
            exportLoading: false,
            noRedirect: true,
            showImportExportModal: false,
            modalType: 'export',
            page: 1,
            limit: 25
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
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

    created() {
        this.repository = this.repositoryFactory.create('scop_platform_redirecter_redirect');

        let criteria = new Criteria(this.page, this.limit);
        criteria.addAssociation('salesChannel');

        this.repository.search(criteria, Shopware.Context.api).then((result) => {
            this.redirect = result;
        });
        this.$on('inline-edit-assign', this.onInlineEditAssign);
    },

    methods: {
        onClickExport() {
            this.modalType = 'export';
            this.showImportExportModal = true;
        },
        onUpdate(records) {
            this.noRedirect = records.length === 0;
        },
        onClickImport() {
            this.modalType = 'import';
            this.showImportExportModal = true;
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
