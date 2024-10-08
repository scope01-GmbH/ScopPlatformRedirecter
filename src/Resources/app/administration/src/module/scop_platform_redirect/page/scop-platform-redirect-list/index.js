import template from './scop-platform-redirect-list.html.twig';

const Criteria = Shopware.Data.Criteria;

const {Component, Mixin} = Shopware;

Shopware.Component.register('scop-platform-redirect-list', {
    template,

    inject: [
        'repositoryFactory', 'syncService', 'loginService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            failedCsv: null,
            repository: null,
            redirect: null,
            exportLoading: false,
            noRedirect: true,
            showImport: false,
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
        const headers = {
            Authorization: `Bearer ${this.loginService.getToken()}`
        };
        this.syncService.httpClient.post("/_action/scop/platform/redirecter/failed", null, {headers: headers}).then(response => {
            if (response.data.failedCsv) {
                this.failedCsv = response.data.failedCsv;
            } else {
                this.failedCsv = null;
            }
        });
    },

    methods: {
        clearFailed() {
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`
            };
            this.syncService.httpClient.post("/_action/scop/platform/redirecter/clearfailed", null, {headers: headers}).then(response => {
                if (response.data.failedCsv) {
                    this.failedCsv = response.data.failedCsv;
                } else {
                    this.failedCsv = null;
                }
            });
        },

        async downloadFailed() {
            await window.open(this.syncService.httpClient.defaults.baseURL + '/_action/scop/platform/redirecter/download-export?filename=failed_import.csv', '_blank');
        },

        async onClickExport() {

            this.exportLoading = true;

            //Get Authorization
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`
            };
            const httpClient = this.syncService.httpClient;

            //Requesting to create the export file, catching an error
            const response = await httpClient.post('/_action/scop/platform/redirecter/prepare-export', {}, {headers: headers}).catch((err) => {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.list.fileNotCreated')
                });
                this.exportLoading = false;
            });

            if (!this.exportLoading) //Returning if an error was caught
                return;

            this.exportLoading = false;

            //Checking if the creation of the file was successfully, otherwise returning
            if (response['status'] !== 200) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.list.fileNotCreated')
                });
                return;
            }

            await window.open(httpClient.defaults.baseURL + '/_action/scop/platform/redirecter/download-export?filename=' + response['data']['file'], '_blank');
        },
        onUpdate(records) {
            this.noRedirect = records.length === 0;
        },
        onClickImport() {
            this.showImport = true;
        },
        closeImport() {
            this.showImport = false;
        },
        updateList(failedCsv) {
            const criteria = this.redirect.criteria;
            this.failedCsv = failedCsv;
            this.repository.search(criteria, Shopware.Context.api).then((result) => {
                this.redirect = result;
            });
        }
    },

});
