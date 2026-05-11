import template from './scop-platform-redirect-not-found-bulk-modal.html.twig';
import './scop-platform-redirect-not-found-bulk-modal.scss';
import targetMixin from '../../mixin/scop-redirect-target-mixin';

const { Mixin } = Shopware;

Shopware.Component.register('scop-platform-redirect-not-found-bulk-modal', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        targetMixin,
    ],

    props: {
        selection: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            isSaving: false,
            progressCurrent: 0,
            progressTotal: 0,
        };
    },

    computed: {
        selectionColumns() {
            return [
                {
                    property: 'url',
                    label: this.$tc('scopplatformredirecter.notFound.columnUrl'),
                    primary: true,
                    allowResize: false,
                },
                {
                    property: 'hitCount',
                    label: this.$tc('scopplatformredirecter.notFound.columnHitCount'),
                    width: '90px',
                    align: 'right',
                    allowResize: false,
                },
            ];
        },

        canSave() {
            return !this.isSaving && this.targetURL.trim().length > 0 && this.selection.length > 0;
        },
    },

    methods: {
        onClose() {
            if (this.isSaving) {
                return;
            }
            this.$emit('close');
        },

        async onSave() {
            const target = this.targetURL.trim();
            if (target === '') {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.bulk.errorTargetEmpty'),
                });
                return;
            }

            this.isSaving = true;
            this.progressCurrent = 0;
            this.progressTotal = this.selection.length;

            let createdCount = 0;
            let skippedCount = 0;
            const errors = [];

            for (const log of this.selection) {
                this.progressCurrent += 1;

                const source = (log.url || '').trim();
                if (source === '' || source === target) {
                    skippedCount += 1;
                    continue;
                }

                try {
                    const redirect = this.redirectRepository.create();
                    redirect.sourceURL = source;
                    redirect.targetURL = target;
                    redirect.httpCode = this.httpCode;
                    redirect.enabled = true;
                    redirect.queryParamsHandling = this.queryParamsHandling;
                    redirect.salesChannelId = this.salesChannelId ?? log.salesChannelId ?? null;

                    await this.redirectRepository.save(redirect, Shopware.Context.api);

                    log.redirectId = redirect.id;
                    await this.notFoundLogRepository.save(log, Shopware.Context.api);
                    createdCount += 1;
                } catch (error) {
                    errors.push(`${source}: ${error?.message || error}`);
                }
            }

            this.isSaving = false;

            if (errors.length > 0) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.bulk.errorPartial', errors.length, {
                        count: errors.length,
                        created: createdCount,
                    }),
                });
            }

            if (skippedCount > 0) {
                this.createNotificationWarning({
                    message: this.$tc('scopplatformredirecter.notFound.bulk.warningSkipped', skippedCount, {
                        count: skippedCount,
                    }),
                });
            }

            this.$emit('redirects-created', { created: createdCount, errors: errors.length, skipped: skippedCount });
        },
    },
});
