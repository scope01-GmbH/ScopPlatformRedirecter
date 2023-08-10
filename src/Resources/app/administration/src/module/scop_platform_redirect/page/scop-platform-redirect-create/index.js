const {Component} = Shopware;

Shopware.Component.extend('scop-platform-redirect-create', 'scop-platform-redirect-details', {

    methods: {
        getRedirect() {
            this.redirect = this.repository.create(Shopware.Context.api);
            this.redirect.httpCode = 302; //Default Value für httpCode
            this.redirect.enabled = true; //Default Value für enabled
        },

        onClickSave() {
            //Checking if source and target URL are the same or one of them is empty, otherwise proceed
            if (this.redirect.sourceURL === this.redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription')
                })
                return;
            }
            if (!this.redirect.sourceURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptySourceURL')
                })
                return;
            }
            if (!this.redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorEmptyTargetURL')
                })
                return;
            }
            this.isLoading = true;
            this.repository.save(this.redirect, Shopware.Context.api).then(() => { //Creating the new Redirect
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
    }

});
