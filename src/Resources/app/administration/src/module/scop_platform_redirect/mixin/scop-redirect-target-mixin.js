const { Criteria, EntityCollection } = Shopware.Data;

const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

export default {
    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    data() {
        return {
            targetMode: 'manual',
            targetURL: '',
            httpCode: 301,
            queryParamsHandling: 0,
            salesChannelId: null,
            selectedProductId: null,
            selectedCategoryId: null,
            categoryCollection: null,
            resolvedEntityUrl: null,
        };
    },

    computed: {
        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        redirectRepository() {
            return this.repositoryFactory.create('scop_platform_redirecter_redirect');
        },

        notFoundLogRepository() {
            return this.repositoryFactory.create('scop_platform_redirecter_404');
        },

        seoUrlRepository() {
            return this.repositoryFactory.create('seo_url');
        },

        isEntityLinkIapActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', IN_APP_PURCHASE_ID);
        },

        targetModeOptions() {
            const options = [
                { value: 'manual', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeManual') },
            ];
            if (this.isEntityLinkIapActive) {
                options.push(
                    { value: 'product', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeProduct') },
                    { value: 'category', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeCategory') },
                );
            }
            return options;
        },

        hasEntityLink() {
            return this.targetMode === 'product'
                ? !!this.selectedProductId
                : this.targetMode === 'category'
                    ? !!this.selectedCategoryId
                    : false;
        },

        targetEntityTypeForSave() {
            if (this.targetMode === 'product' && this.selectedProductId) {
                return 'product';
            }
            if (this.targetMode === 'category' && this.selectedCategoryId) {
                return 'category';
            }
            return null;
        },

        targetEntityIdForSave() {
            if (this.targetMode === 'product') {
                return this.selectedProductId || null;
            }
            if (this.targetMode === 'category') {
                return this.selectedCategoryId || null;
            }
            return null;
        },
    },

    created() {
        this.initCategoryCollection();
        this.loadDefaultQueryParamsHandling();
    },

    methods: {
        initCategoryCollection() {
            this.categoryCollection = new EntityCollection(
                '/category',
                'category',
                Shopware.Context.api,
                new Criteria(),
            );
        },

        async loadDefaultQueryParamsHandling() {
            try {
                const config = await this.systemConfigApiService.getValues('ScopPlatformRedirecter.config');
                const value = config?.['ScopPlatformRedirecter.config.defaultQueryParamsHandling'];
                if (value !== undefined && value !== null && value !== '') {
                    this.queryParamsHandling = Number(value);
                }
            } catch {
                // fall back to data() default
            }
        },

        onTargetModeChange(value) {
            this.targetMode = value;
            this.targetURL = '';
            this.selectedProductId = null;
            this.selectedCategoryId = null;
            this.resolvedEntityUrl = null;
            this.initCategoryCollection();
        },

        async onProductChange(productId) {
            this.selectedProductId = productId;
            this.resolvedEntityUrl = null;
            if (!productId) {
                return;
            }
            await this.previewEntitySeoUrl('frontend.detail.page', productId);
        },

        async onCategoryChange(categoryId) {
            this.selectedCategoryId = categoryId;
            this.resolvedEntityUrl = null;
            if (!categoryId) {
                return;
            }
            await this.previewEntitySeoUrl('frontend.navigation.page', categoryId);
        },

        async previewEntitySeoUrl(routeName, foreignKey) {
            const criteria = new Criteria(1, 1);
            criteria.addFilter(Criteria.equals('routeName', routeName));
            criteria.addFilter(Criteria.equals('foreignKey', foreignKey));
            criteria.addFilter(Criteria.equals('isCanonical', true));

            if (this.salesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannelId));
            }

            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            try {
                const result = await this.seoUrlRepository.search(criteria);
                if (result.total > 0 && result.first().seoPathInfo) {
                    this.resolvedEntityUrl = '/' + result.first().seoPathInfo.replace(/^\/+/, '');
                } else {
                    this.resolvedEntityUrl = null;
                    this.createNotificationWarning({
                        message: this.$tc('scopplatformredirecter.notFound.modal.noSeoUrlFound'),
                    });
                }
            } catch {
                this.resolvedEntityUrl = null;
            }
        },

        transformHttpCodeValueToNumber() {
            this.httpCode = Number(this.httpCode);
        },

        transformQueryFieldValueToNumber() {
            this.queryParamsHandling = Number(this.queryParamsHandling);
        },
    },
};
