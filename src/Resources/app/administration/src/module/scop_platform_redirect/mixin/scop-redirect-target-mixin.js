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
            selectedLanguageId: null,
            seoUrlOptions: [],
            selectedSeoUrlId: null,
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

        targetLanguageIdForSave() {
            return this.targetEntityTypeForSave ? (this.selectedLanguageId || null) : null;
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
            this.resetSeoUrlSelection();
            this.initCategoryCollection();
        },

        resetSeoUrlSelection() {
            this.seoUrlOptions = [];
            this.selectedSeoUrlId = null;
            this.selectedLanguageId = null;
        },

        async onProductChange(productId) {
            this.selectedProductId = productId;
            this.resolvedEntityUrl = null;
            this.resetSeoUrlSelection();
            if (!productId) {
                return;
            }
            await this.loadSeoUrlOptions('frontend.detail.page', productId);
        },

        async onCategoryChange(categoryId) {
            this.selectedCategoryId = categoryId;
            this.resolvedEntityUrl = null;
            this.resetSeoUrlSelection();
            if (!categoryId) {
                return;
            }
            await this.loadSeoUrlOptions('frontend.navigation.page', categoryId);
        },

        async loadSeoUrlOptions(routeName, foreignKey) {
            const criteria = new Criteria(1, 100);
            criteria.addFilter(Criteria.equals('routeName', routeName));
            criteria.addFilter(Criteria.equals('foreignKey', foreignKey));
            criteria.addFilter(Criteria.equals('isCanonical', true));
            criteria.addAssociation('language');
            criteria.addAssociation('salesChannel');

            try {
                const result = await this.seoUrlRepository.search(criteria);
                this.seoUrlOptions = result.map((seoUrl) => this.buildSeoUrlOption(seoUrl));
                if (this.seoUrlOptions.length) {
                    this.applySeoUrlOption(this.seoUrlOptions[0]);
                } else {
                    this.createNotificationWarning({
                        message: this.$tc('scopplatformredirecter.notFound.modal.noSeoUrlFound'),
                    });
                }
            } catch {
                this.resetSeoUrlSelection();
                this.resolvedEntityUrl = null;
            }
        },

        buildSeoUrlOption(seoUrl) {
            const path = '/' + (seoUrl.seoPathInfo || '').replace(/^\/+/, '');
            const languageName = seoUrl.language ? seoUrl.language.name : '';
            const channelName = seoUrl.salesChannel
                ? (seoUrl.salesChannel.translated?.name || seoUrl.salesChannel.name)
                : this.$tc('scopplatformredirecter.detail.seoUrlAllChannels');
            return {
                value: seoUrl.id,
                id: seoUrl.id,
                languageId: seoUrl.languageId,
                salesChannelId: seoUrl.salesChannelId,
                seoPathInfo: path,
                label: `${channelName} · ${languageName} · ${path}`,
            };
        },

        onSeoUrlChange(seoUrlId) {
            const option = this.seoUrlOptions.find((o) => o.value === seoUrlId);
            if (!option) {
                this.selectedSeoUrlId = null;
                this.selectedLanguageId = null;
                this.resolvedEntityUrl = null;
                return;
            }
            this.applySeoUrlOption(option);
        },

        applySeoUrlOption(option) {
            this.selectedSeoUrlId = option.value;
            this.selectedLanguageId = option.languageId || null;
            this.resolvedEntityUrl = option.seoPathInfo;
            // A channel-specific SEO URL implies the redirect targets that channel; align the scope.
            if (option.salesChannelId) {
                this.salesChannelId = option.salesChannelId;
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
