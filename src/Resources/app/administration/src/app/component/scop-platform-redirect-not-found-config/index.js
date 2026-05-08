import template from './scop-platform-redirect-not-found-config.html.twig';
import './scop-platform-redirect-not-found-config.scss';

const inAppPurchaseId = 'scopPlatformRedirecterPremium';

Shopware.Component.register('scop-platform-redirect-not-found-config', {
    template,

    inject: [
        'systemConfigApiService',
    ],

    props: {
        value: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            defaultQueryParamsHandling: 0,
            retentionDays: 90,
            isLoading: false,
        };
    },

    computed: {
        inAppPurchaseCheckout() {
            return Shopware.Store.get('inAppPurchaseCheckout');
        },

        isIapActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId);
        },

        cleanupEnabled: {
            get() {
                return this.value || false;
            },
            set(newValue) {
                this.$emit('update:value', newValue);
            },
        },

        queryParamsHandlingOptions() {
            return [
                {
                    value: 0,
                    label: this.$tc('scopplatformredirecter.detail.queryParamsHandlingValues.consider'),
                },
                {
                    value: 1,
                    label: this.$tc('scopplatformredirecter.detail.queryParamsHandlingValues.ignore'),
                },
                {
                    value: 2,
                    label: this.$tc('scopplatformredirecter.detail.queryParamsHandlingValues.transfer'),
                },
            ];
        },
    },

    created() {
        this.loadSiblingValues();
    },

    methods: {
        async loadSiblingValues() {
            this.isLoading = true;
            try {
                const config = await this.systemConfigApiService.getValues('ScopPlatformRedirecter.config');
                const handling = config?.['ScopPlatformRedirecter.config.defaultQueryParamsHandling'];
                if (handling !== undefined && handling !== null && handling !== '') {
                    this.defaultQueryParamsHandling = Number(handling);
                }
                const retention = config?.['ScopPlatformRedirecter.config.notFoundLogRetentionDays'];
                if (retention !== undefined && retention !== null && retention !== '') {
                    this.retentionDays = Number(retention);
                }
            } finally {
                this.isLoading = false;
            }
        },

        async onQueryParamsHandlingChange(newValue) {
            this.defaultQueryParamsHandling = Number(newValue);
            await this.systemConfigApiService.saveValues({
                'ScopPlatformRedirecter.config.defaultQueryParamsHandling': this.defaultQueryParamsHandling,
            });
        },

        async onRetentionDaysChange(newValue) {
            const numeric = Number(newValue);
            if (Number.isNaN(numeric)) {
                return;
            }
            this.retentionDays = numeric;
            await this.systemConfigApiService.saveValues({
                'ScopPlatformRedirecter.config.notFoundLogRetentionDays': this.retentionDays,
            });
        },

        onClickPurchase() {
            this.inAppPurchaseCheckout.request({ identifier: inAppPurchaseId }, 'ScopPlatformRedirecter');
        },
    },
});
