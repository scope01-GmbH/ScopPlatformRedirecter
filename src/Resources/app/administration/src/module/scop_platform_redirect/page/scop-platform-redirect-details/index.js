import template from './scop-platform-redirect-details.html.twig';
import './scop-platform-redirect-details.scss';

const {Component, Mixin} = Shopware;

const ENTITY_ROUTE_MAP = {
    product: 'frontend.detail.page',
    category: 'frontend.navigation.page',
};

Component.register('scop-platform-redirect-details', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],


    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            redirect: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            seoUrlRepository: null,
            resolvedEntityUrl: null,
            entityLookupDone: false,
        };
    },

    computed: {
        targetMode() {
            return this.redirect && this.redirect.targetEntityType
                ? this.redirect.targetEntityType
                : 'manual';
        },

        targetModeOptions() {
            return [
                {value: 'manual', label: this.$tc('scopplatformredirecter.detail.targetMode.manual')},
                {value: 'product', label: this.$tc('scopplatformredirecter.detail.targetMode.product')},
                {value: 'category', label: this.$tc('scopplatformredirecter.detail.targetMode.category')},
            ];
        },

        isEntityDangling() {
            return this.entityLookupDone
                && !!(this.redirect && this.redirect.targetEntityType && this.redirect.targetEntityId)
                && !this.resolvedEntityUrl;
        },
    },

    created() {
        this.repository = this.repositoryFactory.create('scop_platform_redirecter_redirect');
        this.seoUrlRepository = this.repositoryFactory.create('seo_url');
        this.getRedirect();
    },

    methods: {
        getRedirect() {
            const criteria = new Shopware.Data.Criteria();
            criteria.addAssociation('product');

            this.repository.get(this.$route.params.id, Shopware.Context.api, criteria).then((entity) => {
                this.redirect = entity;
                this.refreshResolvedEntityUrl();
            })
        },

        onTargetModeChange(mode) {
            if (mode === 'manual') {
                this.redirect.targetEntityType = null;
                this.redirect.targetEntityId = null;
                this.resolvedEntityUrl = null;
                return;
            }

            this.redirect.targetEntityType = mode;
            this.redirect.targetEntityId = null;
            this.redirect.targetURL = '';
            this.resolvedEntityUrl = null;
        },

        onEntityChange(entityType, entityId) {
            this.redirect.targetEntityType = entityType;
            this.redirect.targetEntityId = entityId || null;
            this.redirect.targetURL = '';
            this.refreshResolvedEntityUrl();
        },

        refreshResolvedEntityUrl() {
            this.resolvedEntityUrl = null;
            this.entityLookupDone = false;

            if (!this.redirect || !this.redirect.targetEntityType || !this.redirect.targetEntityId) {
                this.entityLookupDone = true;
                return;
            }
            const routeName = ENTITY_ROUTE_MAP[this.redirect.targetEntityType];
            if (!routeName) {
                this.entityLookupDone = true;
                return;
            }

            const criteria = new Shopware.Data.Criteria(1, 1);
            criteria.addFilter(Shopware.Data.Criteria.equals('routeName', routeName));
            criteria.addFilter(Shopware.Data.Criteria.equals('foreignKey', this.redirect.targetEntityId));
            criteria.addFilter(Shopware.Data.Criteria.equals('isCanonical', true));

            this.seoUrlRepository.search(criteria, Shopware.Context.api).then((result) => {
                const seoUrl = result.first();
                if (seoUrl && seoUrl.seoPathInfo) {
                    this.resolvedEntityUrl = '/' + seoUrl.seoPathInfo.replace(/^\/+/, '');
                }
                this.entityLookupDone = true;
            }).catch(() => {
                this.entityLookupDone = true;
            });
        },

        onConvertDanglingToManual() {
            this.redirect.targetEntityType = null;
            this.redirect.targetEntityId = null;
            if (!this.redirect.targetURL) {
                this.redirect.targetURL = '/';
            }
            this.resolvedEntityUrl = null;
            this.entityLookupDone = true;
        },

        onClickSave() {
            if (!this.redirect.sourceURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptySourceURL')
                })
                return;
            }

            const hasEntityLink = !!(this.redirect.targetEntityType && this.redirect.targetEntityId);

            if (!hasEntityLink && !this.redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptyTargetURL')
                })
                return;
            }

            if (!hasEntityLink && this.redirect.sourceURL.trim() === this.redirect.targetURL.trim()) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription')
                })
                return;
            }

            this.isLoading = true;
            this.repository.save(this.redirect, Shopware.Context.api).then(() => {
                this.getRedirect();
                this.isLoading = false;
                this.processSuccess = true;
            }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: exception
                })
            });
        },

        saveFinish() {
            this.processSuccess = false;
            this.$router.push({name: 'scop.platform.redirect.list'});
        },

        transformQueryFieldValueToNumber() {
          this.redirect.queryParamsHandling = Number(this.redirect.queryParamsHandling);
        },

        transformHttpCodeValueToNumber() {
            this.redirect.httpCode = Number(this.redirect.httpCode);
        },
    }

});

function hasExternalLink() {
    var version = Shopware.Context.app.config.version.split(".");
    if (parseInt(version[0]) < 6)
        return false;
    if (parseInt(version[0]) > 6)
        return true;

    if (parseInt(version[1]) < 4)
        return false;
    if (parseInt(version[1]) > 4)
        return true;

    if (parseInt(version[2]) < 3)
        return false;
    return true;
}
