import template from './scop-platform-redirect-not-found-create-modal.html.twig';
import targetMixin from '../../mixin/scop-redirect-target-mixin';

const { Mixin } = Shopware;

Shopware.Component.register('scop-platform-redirect-not-found-create-modal', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        targetMixin,
    ],

    props: {
        notFoundLog: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            sourceURL: '',
            isLoading: false,
        };
    },

    created() {
        this.sourceURL = this.notFoundLog.url;
        this.salesChannelId = this.notFoundLog.salesChannelId;
    },

    methods: {
        onClose() {
            this.$emit('close');
        },

        async onSave() {
            if (!this.sourceURL || !this.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.modal.errorEmptyFields'),
                });
                return;
            }

            if (this.sourceURL.trim() === this.targetURL.trim()) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription'),
                });
                return;
            }

            this.isLoading = true;

            try {
                const redirect = this.redirectRepository.create();
                redirect.sourceURL = this.sourceURL;
                redirect.targetURL = this.targetURL;
                redirect.httpCode = this.httpCode;
                redirect.enabled = true;
                redirect.queryParamsHandling = this.queryParamsHandling;
                redirect.salesChannelId = this.salesChannelId;

                await this.redirectRepository.save(redirect, Shopware.Context.api);

                this.notFoundLog.redirectId = redirect.id;
                await this.notFoundLogRepository.save(this.notFoundLog, Shopware.Context.api);

                this.$emit('redirect-created');
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: error.message || String(error),
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
});
