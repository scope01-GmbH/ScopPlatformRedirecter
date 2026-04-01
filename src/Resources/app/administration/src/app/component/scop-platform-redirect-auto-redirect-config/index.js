import template from './scop-platform-redirect-auto-redirect-config.html.twig';
import './scop-platform-redirect-auto-redirect-config.scss';

const inAppPurchaseId = 'scopPlatformRedirecterPremium';
const { Criteria } = Shopware.Data;

Shopware.Component.register('scop-platform-redirect-auto-redirect-config', {
    template,

    inject: [
        'repositoryFactory',
        'syncService',
        'systemConfigApiService',
    ],

    props: {
        value: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    created() {
        this.initial = this.value;
    },

    data() {
        return {
            showDeleteModal: false,
            initial: false,
        };
    },

    computed: {
        inAppPurchaseCheckout() {
            return Shopware.Store.get('inAppPurchaseCheckout');
        },

        isIapActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId);
        },

        redirectRepository() {
            return this.repositoryFactory.create('scop_platform_redirecter_redirect');
        },

        switchValue: {
            get() {
                return this.value || false;
            },
            set(newValue) {
                this.$emit('update:value', newValue);
            },
        },
    },

    methods: {
        onClickPurchase() {
            this.inAppPurchaseCheckout.request({ identifier: inAppPurchaseId }, 'ScopPlatformRedirecter');
        },

        onSwitchChange(newValue) {
            if (!newValue && this.initial) {
                this.showDeleteModal = true;
                return;
            }

            this.switchValue = newValue;
        },

        async onModalDeleteConfirm() {
            this.showDeleteModal = false;
            this.switchValue = false;

            await this.systemConfigApiService.saveValues({
                'ScopPlatformRedirecter.config.autoRedirectEnabled': false,
            });

            await this.deleteAutoCreatedRedirects();
        },

        onModalKeepConfirm() {
            this.showDeleteModal = false;
            this.switchValue = false;
        },

        onModalCancel() {
            this.showDeleteModal = false;
            this.switchValue = true;
        },

        async deleteAutoCreatedRedirects() {
            const criteria = new Criteria(1, 500);
            criteria.addFilter(Criteria.not('AND', [
                Criteria.equals('productId', null),
            ]));

            let hasMore = true;

            while (hasMore) {
                const result = await this.redirectRepository.searchIds(criteria, Shopware.Context.api);

                if (result.total === 0) {
                    hasMore = false;
                    break;
                }

                const payload = result.data.map((id) => ({ id }));

                await this.syncService.sync([{
                    action: 'delete',
                    entity: 'scop_platform_redirecter_redirect',
                    payload,
                }]);
            }
        },
    },
});
