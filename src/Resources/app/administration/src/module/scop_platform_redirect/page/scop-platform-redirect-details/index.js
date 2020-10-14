import template from './scop-platform-redirect-details.html.twig';

const { Component, Mixin } = Shopware;

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
			repository: null
		};
	},

	created() {
		this.repository = this.repositoryFactory.create('scop_platform_redirecter_redirect');
		this.getRedirect();
	},

	methods: {
		getRedirect() {
			this.repository.get(this.$route.params.id, Shopware.Context.api).then((entity) => { this.redirect = entity; })
		},

		onClickSave() {
			this.isLoading = true;
			this.repository.save(this.redirect, Shopware.Context.api).then(() => {
				this.getRedirect();
				this.isLoading = false;
				this.processSuccess = true;
			}).catch((exception) => {
				this.isLoading = false;
				this.createNotificationError({
					title: this.$tc('scopplatformredirecter.detail.errorTitle'),
					message: exception
				})
			});
		},
		
		saveFinish(){
			this.processSuccess = false;
		}
	}

});