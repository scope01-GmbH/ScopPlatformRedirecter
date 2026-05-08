import template from './scop-platform-redirect-not-found-create-modal.html.twig';

const { Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Shopware.Component.register('scop-platform-redirect-not-found-create-modal', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    mixins: [
        Mixin.getByName('notification'),
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
            targetURL: '',
            httpCode: 301,
            queryParamsHandling: 0,
            salesChannelId: null,
            isLoading: false,
            targetMode: 'manual',
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
        this.sourceURL = this.notFoundLog.url;
        this.salesChannelId = this.notFoundLog.salesChannelId;
        this.categoryCollection = new EntityCollection(
            '/category',
            'category',
            Shopware.Context.api,
            new Criteria(),
        );
        this.loadDefaultQueryParamsHandling();
    },

    methods: {
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

        onClose() {
            this.$emit('close');
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
                    this.targetURL = '/' + result.first().seoPathInfo;
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
                // Create the redirect
                const redirect = this.redirectRepository.create();
                redirect.sourceURL = this.sourceURL;
                redirect.targetURL = this.targetURL;
                redirect.httpCode = this.httpCode;
                redirect.enabled = true;
                redirect.queryParamsHandling = this.queryParamsHandling;
                redirect.salesChannelId = this.salesChannelId;

                await this.redirectRepository.save(redirect, Shopware.Context.api);

                // Link the 404 entry to the new redirect
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

        onTargetModeChange(value) {
            this.targetMode = value;
            this.targetURL = '';
            this.selectedProductId = null;
            this.selectedCategoryId = null;
            this.categoryCollection = new EntityCollection(
                '/category',
                'category',
                Shopware.Context.api,
                new Criteria(),
            );
        },

        transformHttpCodeValueToNumber() {
            this.httpCode = Number(this.httpCode);
        },

        transformQueryFieldValueToNumber() {
            this.queryParamsHandling = Number(this.queryParamsHandling);
        },
    },
});
