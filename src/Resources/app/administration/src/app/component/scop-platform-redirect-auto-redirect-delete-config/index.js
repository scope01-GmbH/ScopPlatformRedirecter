import template from './scop-platform-redirect-auto-redirect-delete-config.html.twig';
import './scop-platform-redirect-auto-redirect-delete-config.scss';

const inAppPurchaseId = 'scopPlatformRedirecterPremium';

Shopware.Component.register('scop-platform-redirect-auto-redirect-delete-config', {
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
            httpCode: 301,
        };
    },

    computed: {
        inAppPurchaseCheckout() {
            return Shopware.Store.get('inAppPurchaseCheckout');
        },

        isIapActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId);
        },

        enabled: {
            get() {
                return this.value || false;
            },
            set(newValue) {
                this.$emit('update:value', newValue);
            },
        },

        httpCodeOptions() {
            return [
                {
                    value: 301,
                    label: this.$tc('scopplatformredirecter.autoRedirectDelete.httpCode301'),
                },
                {
                    value: 302,
                    label: this.$tc('scopplatformredirecter.autoRedirectDelete.httpCode302'),
                },
            ];
        },
    },

    created() {
        this.loadHttpCode();
    },

    methods: {
        async loadHttpCode() {
            try {
                const config = await this.systemConfigApiService.getValues('ScopPlatformRedirecter.config');
                const value = config?.['ScopPlatformRedirecter.config.autoRedirectOnDeleteHttpCode'];
                if (value !== undefined && value !== null && value !== '') {
                    this.httpCode = Number(value);
                }
            } catch {
                // fall back to data() default
            }
        },

        async onHttpCodeChange(newValue) {
            this.httpCode = Number(newValue);
            await this.systemConfigApiService.saveValues({
                'ScopPlatformRedirecter.config.autoRedirectOnDeleteHttpCode': this.httpCode,
            });
        },

        onClickPurchase() {
            this.inAppPurchaseCheckout.request({ identifier: inAppPurchaseId }, 'ScopPlatformRedirecter');
        },
    },
});
