const { Criteria, EntityCollection } = Shopware.Data;

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

        targetModeOptions() {
            return [
                { value: 'manual', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeManual') },
                { value: 'product', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeProduct') },
                { value: 'category', label: this.$tc('scopplatformredirecter.notFound.modal.targetModeCategory') },
            ];
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
            this.initCategoryCollection();
        },

        async onProductChange(productId) {
            this.selectedProductId = productId;
            if (!productId) {
                this.targetURL = '';
                return;
            }
            await this.loadSeoUrl('frontend.detail.page', productId);
        },

        async onCategoryChange(categoryId) {
            this.selectedCategoryId = categoryId;
            if (!categoryId) {
                this.targetURL = '';
                return;
            }
            await this.loadSeoUrl('frontend.navigation.page', categoryId);
        },

        async loadSeoUrl(routeName, foreignKey) {
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
                if (result.total > 0) {
                    this.targetURL = `/${result.first().seoPathInfo}`;
                } else {
                    this.targetURL = '';
                    this.createNotificationWarning({
                        message: this.$tc('scopplatformredirecter.notFound.modal.noSeoUrlFound'),
                    });
                }
            } catch {
                this.targetURL = '';
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
