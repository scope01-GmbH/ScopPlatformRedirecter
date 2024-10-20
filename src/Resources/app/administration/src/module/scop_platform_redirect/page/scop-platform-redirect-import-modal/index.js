import template from './scop-platform-redirect-import-modal.html.twig';

const {Component, Mixin} = Shopware;
const Criteria = Shopware.Data.Criteria;

Component.register('scop-platform-redirect-import-modal', {
    template,

    inject: [
        'importExport', 'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            selectedFile: null,
            noFile: true,
            processing: false,
            selectedProfileId: null,
        };
    },

    props: {
        show: {
            type: Boolean,
            required: true,
            default: false,
        },
        type: {
            type: String,
            required: true,
            default: 'import',
        }
    },

    emits: ['import-started', 'export-started'],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        importExportProfileRepository() {
            return this.repositoryFactory.create('import_export_profile');
        },
        profileCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('label'));

            criteria.addFilter(
                Criteria.equals('sourceEntity', 'scop_platform_redirecter_redirect'),
            );
            criteria.addQuery(Criteria.contains('type', 'import'));

            return criteria;
        },
    },

    created() {
        const criteria = new Criteria(1, 25);
        criteria.addFilter(
            Criteria.equals('technicalName', 'default_scop_redirect')
        );
        this.importExportProfileRepository.search(criteria).then((result) => {
            if (result[0]) {
                this.selectedProfileId = result[0].id;
            }
        })
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
        onProfileSelect(profileId) {
            this.selectedProfileId = profileId;
        },
        async startProcess() {
            if (this.type == 'import') {
                this.startImport();
            } else if (this.type == 'export') {
                this.startExport();
            }
        },
        async startImport() { //Importing the file
            this.processing = true;
            const profile = this.selectedProfileId;

            this.importExport.import(profile, this.selectedFile, this.handleProgress).then(() => {
                this.selectedFile = null;
            }).catch((error) => {
                if (!error.response || !error.response.data || !error.response.data.errors) {
                    this.createNotificationError({
                        message: error.message,
                    });
                } else {
                    error.response.data.errors.forEach((singleError) => {
                        this.createNotificationError({
                            message: `${singleError.code}: ${singleError.detail}`,
                        });
                    });
                }

                this.processing = false;
            });
        },
        async startExport() {
            this.processing = true;

            this.importExport.export(this.selectedProfileId, this.handleProgress, this.config).catch((error) => {
                if (!error.response || !error.response.data || !error.response.data.errors) {
                    this.createNotificationError({
                        message: error.message,
                    });
                } else {
                    error.response.data.errors.forEach((singleError) => {
                        this.createNotificationError({
                            message: `${singleError.code}: ${singleError.detail}`,
                        });
                    });
                }

                this.processing = false;
            });
        },
        handleProgress(log) {
            if (log.activity === 'export') {
                this.createNotificationInfo({
                    message: this.$tc('sw-import-export.exporter.messageExportStarted'),
                });

                this.$emit('export-started', log);
                this.$router.push({name: 'sw.import.export.index.export'});
            } else if (log.activity === 'import') {
                this.createNotificationInfo({
                    message: this.$tc('sw-import-export.importer.messageImportStarted'),
                });

                this.$emit('import-started', log);
            }

            this.processing = false;
            this.$emit('updateList'); //Updating the List
            this.$emit('close'); //Closing the modal
        },
    }

});
