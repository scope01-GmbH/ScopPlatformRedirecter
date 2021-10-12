import template from './scop-platform-redirect-import-modal.html.twig';

const {Component, Mixin} = Shopware;

Component.register('scop-platform-redirect-import-modal', {
    template,

    inject: [
        'syncService', 'loginService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            selectedFile: null,
            noFile: true,
            processing: false,
            override: false,
            overrideID: true
        };
    },

    props: {
        show: {
            type: Boolean,
            required: true,
            default: false,
        }
    },


    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        overrideIDHelp() {
            return this.$tc('scopplatformredirecter.list.importModal.overrideIDHelp', 0, {
                moreInformation: '<sw-external-link href="' + this.$tc('scopplatformredirecter.general.moreInformationLink') + '">' + this.$tc('scopplatformredirecter.general.moreInformation') + '</sw-external-link>'
            });
        },
        overrideHelp() {
            return this.$tc('scopplatformredirecter.list.importModal.overrideHelp', 0, {
                moreInformation: '<sw-external-link href="' + this.$tc('scopplatformredirecter.general.moreInformationLink') + '">' + this.$tc('scopplatformredirecter.general.moreInformation') + '</sw-external-link>'
            });
        }
    },

    methods: {
        onClose() {
            if (!this.processing)
                this.$emit('close');
        },
        onFileChange(file) {
            this.file = file;
            this.noFile = file == null;
        },
        async startImport() {

            this.processing = true;

            const formData = new FormData();
            formData.set("file", this.file);
            formData.set("overrideID", this.overrideID);
            formData.set("override", this.override);

            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`
            };

            const httpClient = this.syncService.httpClient;

            const response = await httpClient.post("/_action/scop/platform/redirecter/import", formData, {headers: headers}).catch((err) => {

                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.list.fileNotImported')
                });

                this.processing = false;
            });
            if (!this.processing)
                return;

            if (response['status'] !== 200 || response['data']['detail'] !== 'File Imported!') {
                if (response['data']['detail'] === 'File is not a Redirects Export') {
                    this.createNotificationError({
                        title: this.$tc('scopplatformredirecter.general.errorTitle'),
                        message: this.$tc('scopplatformredirecter.list.invalidFile')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('scopplatformredirecter.general.errorTitle'),
                        message: this.$tc('scopplatformredirecter.list.fileNotImported')
                    });
                }
                this.processing = false;
                this.$emit('updateList');
                return;
            } else {
                if (response['data']['skipped'] > 0) {
                    this.createNotification({
                        title: this.$tc('scopplatformredirecter.list.importDone'),
                        message: this.$tc('scopplatformredirecter.list.fileImported', 0, {
                            amount: response['data']['amount'],
                            skipped: response['data']['skipped']
                        })
                    });
                } else {
                    this.createNotification({
                        title: this.$tc('scopplatformredirecter.list.importDone'),
                        message: this.$tc('scopplatformredirecter.list.fileImportedNoSkip', 0, {
                            amount: response['data']['amount']
                        })
                    });
                }
            }

            this.processing = false;
            this.$emit('updateList');
            this.$emit('close');
        }
    }

});