import template from './scop-platform-redirect-import-export-modal.html.twig';
import './scop-platform-redirect-import-export-modal.scss';

const {Component, Mixin} = Shopware;
const Criteria = Shopware.Data.Criteria;

Component.register('scop-platform-redirect-import-export-modal', {
    template,

    inject: [
        'importExport', 'repositoryFactory'
    ],

    compatConfig: Shopware.compatConfig,

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
        },
        sourceEntity: {
            type: String,
            default: 'scop_platform_redirecter_redirect',
        },
        exportConfig: {
            type: Object,
            default: () => ({}),
        }
    },

    emits: ['close', 'import-started', 'export-started', 'updateList'],

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
                Criteria.equals('sourceEntity', this.sourceEntity),
            );
            criteria.addFilter(Criteria.equalsAny('type', [this.type, 'import-export']));

            return criteria;
        },
    },

    created() {
        this.loadDefaultProfile();
    },

    watch: {
        type: 'loadDefaultProfile',
        sourceEntity: 'loadDefaultProfile',
    },

    methods: {
        async loadDefaultProfile() {
            const { sourceEntity, type } = this;
            this.selectedProfileId = null;

            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('sourceEntity', sourceEntity));
            criteria.addFilter(Criteria.equalsAny('type', [type, 'import-export']));
            criteria.addFilter(Criteria.equals('technicalName', `default_${sourceEntity}`));

            try {
                const result = await this.importExportProfileRepository.search(criteria);
                if (this.sourceEntity === sourceEntity && this.type === type && !this.selectedProfileId) {
                    this.selectedProfileId = result[0]?.id ?? null;
                }
            } catch {
                // The profile can still be selected manually if the default lookup fails.
            }
        },
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
            if (!this.selectedProfileId || this.processing) {
                return;
            }
            this.processing = true;

            this.importExport.export(this.selectedProfileId, this.handleProgress, this.exportConfig).catch((error) => {
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
            } else if (log.activity === 'import') {
                this.createNotificationInfo({
                    message: this.$tc('sw-import-export.importer.messageImportStarted'),
                });

                this.$emit('import-started', log);
            }

            this.$refs.activityGrid.addActivity(log);
            this.$refs.activityGrid.fetchActivities();
            this.$emit('updateList'); //Updating the List
            this.processing = false;
        },
    }

});
