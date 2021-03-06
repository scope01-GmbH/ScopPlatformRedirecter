const { Component } = Shopware;

Shopware.Component.extend('scop-platform-redirect-create', 'scop-platform-redirect-details', {

	methods: {
		getRedirect(){
			this.redirect = this.repository.create(Shopware.Context.api);
			this.redirect.httpCode = 302; //Default Value für httpCode
		},

		onClickSave() {
		    if (this.redirect.sourceURL === this.redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.detail.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription')
                })
                return;
            }
			this.isLoading = true;
			this.repository.save(this.redirect, Shopware.Context.api).then(() => {
				this.isLoading = false;
				this.$router.push({name: 'scop.platform.redirect.details', params: {id: this.redirect.id}});
			}).catch((exception) => {
				this.isLoading = false;
				this.createNotificationError({
					title: this.$tc('scopplatformredirecter.detail.errorTitle'),
					message: exception
				})
			});
		},
	}

});
