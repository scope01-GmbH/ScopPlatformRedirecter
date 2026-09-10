const {Component} = Shopware;

Shopware.Component.extend('scop-platform-redirect-create', 'scop-platform-redirect-details', {

    methods: {
        getRedirect() {
            this.redirect = this.repository.create(Shopware.Context.api);
            this.redirect.httpCode = 302;
            this.redirect.enabled = true;
            this.redirect.targetURL = '';
            this.redirect.targetEntityType = null;
            this.redirect.targetEntityId = null;
            this.redirect.targetLanguageId = null;
            this.redirect.targetSalesChannelId = null;
            this.resolvedEntityUrl = null;
            this.seoUrlOptions = [];
            this.selectedSeoUrlId = null;
            this.entityLookupDone = true;
        },
    }

});
