import template from './scop-platform-redirect-details.html.twig';
import './scop-platform-redirect-details.scss';

const {Component, Mixin} = Shopware;

const ENTITY_ROUTE_MAP = {
    product: 'frontend.detail.page',
    category: 'frontend.navigation.page',
};

const IN_APP_PURCHASE_ID = 'scopPlatformRedirecterPremium';

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
            seoUrlOptions: [],
            selectedSeoUrlId: null,
        };
    },

    computed: {
        targetMode() {
            return this.redirect && this.redirect.targetEntityType
                ? this.redirect.targetEntityType
                : 'manual';
        },

        isIapActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', IN_APP_PURCHASE_ID);
        },

        isExpired() {
            return !!(this.redirect && this.redirect.activeUntil)
                && new Date(this.redirect.activeUntil) < new Date();
        },

        targetModeOptions() {
            const options = [
                {value: 'manual', label: this.$tc('scopplatformredirecter.detail.targetMode.manual')},
            ];
            if (this.isIapActive) {
                options.push(
                    {value: 'product', label: this.$tc('scopplatformredirecter.detail.targetMode.product')},
                    {value: 'category', label: this.$tc('scopplatformredirecter.detail.targetMode.category')},
                );
            }
            return options;
        },

        hasNoSeoUrlForEntity() {
            // The linked entity is selected but has no resolvable canonical SEO URL. This is not the
            // same as "entity deleted": keep the selectors visible so the user can pick another target
            // or switch to a manual URL.
            return this.entityLookupDone
                && !!(this.redirect && this.redirect.targetEntityType && this.redirect.targetEntityId)
                && this.seoUrlOptions.length === 0;
        },

        showIapLockedBanner() {
            return !this.isIapActive
                && !!(this.redirect && this.redirect.targetEntityType);
        },

        inAppPurchaseCheckout() {
            return Shopware.Store.get('inAppPurchaseCheckout');
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
                this.loadSeoUrlOptions();
            })
        },

        onTargetModeChange(mode) {
            this.seoUrlOptions = [];
            this.selectedSeoUrlId = null;
            this.redirect.targetLanguageId = null;
            this.redirect.targetSalesChannelId = null;

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
            this.redirect.targetLanguageId = null;
            this.redirect.targetSalesChannelId = null;
            this.loadSeoUrlOptions();
        },

        loadSeoUrlOptions() {
            this.resolvedEntityUrl = null;
            this.seoUrlOptions = [];
            this.selectedSeoUrlId = null;
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

            const criteria = new Shopware.Data.Criteria(1, 100);
            criteria.addFilter(Shopware.Data.Criteria.equals('routeName', routeName));
            criteria.addFilter(Shopware.Data.Criteria.equals('foreignKey', this.redirect.targetEntityId));
            criteria.addFilter(Shopware.Data.Criteria.equals('isCanonical', true));
            criteria.addAssociation('language');
            criteria.addAssociation('salesChannel');

            this.seoUrlRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.seoUrlOptions = result.map((seoUrl) => this.buildSeoUrlOption(seoUrl));
                this.preselectSeoUrl();
                this.entityLookupDone = true;
            }).catch(() => {
                this.entityLookupDone = true;
            });
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

        preselectSeoUrl() {
            if (!this.seoUrlOptions.length) {
                return;
            }

            let option = null;
            if (this.redirect.targetLanguageId) {
                // Honor the exact stored target (language + sales channel).
                option = this.seoUrlOptions.find((o) =>
                    o.languageId === this.redirect.targetLanguageId
                    && (o.salesChannelId || null) === (this.redirect.targetSalesChannelId || null),
                );
                // Only fall back to a language-only match when no target channel was stored (legacy
                // redirects). Never jump to a different channel than the one that was saved.
                if (!option && !this.redirect.targetSalesChannelId) {
                    option = this.seoUrlOptions.find((o) => o.languageId === this.redirect.targetLanguageId);
                }
            } else {
                // No target stored yet (freshly picked entity): default to the first option and adopt it.
                option = this.seoUrlOptions[0];
                this.redirect.targetLanguageId = option.languageId || null;
                this.redirect.targetSalesChannelId = option.salesChannelId || null;
            }

            if (option) {
                this.selectedSeoUrlId = option.value;
                this.resolvedEntityUrl = option.seoPathInfo;
            }
        },

        onSeoUrlChange(seoUrlId) {
            this.selectedSeoUrlId = seoUrlId;
            const option = this.seoUrlOptions.find((o) => o.value === seoUrlId);
            if (!option) {
                this.redirect.targetLanguageId = null;
                this.redirect.targetSalesChannelId = null;
                this.resolvedEntityUrl = null;
                return;
            }
            this.redirect.targetLanguageId = option.languageId || null;
            // Store the target's sales channel separately. It must not overwrite the redirect's own
            // (source) sales-channel scope, otherwise a cross-channel redirect would stop firing.
            this.redirect.targetSalesChannelId = option.salesChannelId || null;
            this.resolvedEntityUrl = option.seoPathInfo;
        },

        onClickPurchase() {
            if (this.inAppPurchaseCheckout) {
                this.inAppPurchaseCheckout.request({ identifier: IN_APP_PURCHASE_ID }, 'ScopPlatformRedirecter');
            }
        },

        onConvertDanglingToManual() {
            this.redirect.targetEntityType = null;
            this.redirect.targetEntityId = null;
            this.redirect.targetLanguageId = null;
            this.redirect.targetSalesChannelId = null;
            if (!this.redirect.targetURL) {
                this.redirect.targetURL = '/';
            }
            this.resolvedEntityUrl = null;
            this.seoUrlOptions = [];
            this.selectedSeoUrlId = null;
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
