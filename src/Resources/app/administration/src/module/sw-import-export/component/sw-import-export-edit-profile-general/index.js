const { Component } = Shopware;

Component.override('sw-import-export-edit-profile-general', {
    computed: {
        supportedEntities() {
            const supportedEntities = this.$super('supportedEntities');
            supportedEntities.push({
                value: 'scop_platform_redirecter_redirect',
                label: this.$tc('scopplatformredirecter.general.title'),
                type: 'import-export',
            });

            return supportedEntities;
        }
    }
});