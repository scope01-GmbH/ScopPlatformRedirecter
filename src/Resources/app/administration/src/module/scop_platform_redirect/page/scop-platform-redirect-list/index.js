import template from './scop-platform-redirect-list.html.twig';
const Criteria = Shopware.Data.Criteria;

const { Component, Mixin } = Shopware;

Shopware.Component.register('scop-platform-redirect-list', {
	template,

	inject: [
		'repositoryFactory',  'syncService','loginService'
	],

	mixins: [
		Mixin.getByName('notification')
	],
	
	data() {
		return {
			repository: null,
			redirect: null,
			exportLoading: false
		};
	},

	metaInfo() {
		return {
			title: this.$createTitle()
		};
	},

	computed: {
		columns() {
			return [{
				property: 'sourceURL',
				dataIndex: 'sourceURL',
				label: this.$tc('scopplatformredirecter.list.columnSourceUrl'),
				routerLink: 'scop.platform.redirect.details',
				inlineEdit: 'string',
				allowResize: true,
				primary: true
			}, {
				property: 'targetURL',
				dataIndex: 'targetURL',
				label: this.$tc('scopplatformredirecter.list.columnTargetUrl'),
				inlineEdit: 'string',
				allowResize: true
			}, {
				property: 'httpCode',
				dataIndex: 'httpCode',
				label: this.$tc('scopplatformredirecter.list.columnHttpCode'),
				allowResize: true
			}];
		}
	},
	 
	created() {
		this.repository = this.repositoryFactory.create('scop_platform_redirecter_redirect');
		this.repository.search(new Criteria(), Shopware.Context.api).then((result) => {
			this.redirect = result; });
	},

	methods: {
		async onClickExport(){

			this.exportLoading = true;

			const headers = {
				Authorization: `Bearer ${this.loginService.getToken()}`
			};
			const httpClient = this.syncService.httpClient;

			const response = await httpClient.post('/_action/scop/platform/redirecter/prepare-export', {}, {headers: headers}).catch((err) => {
				this.createNotificationError({
					title: this.$tc('scopplatformredirecter.detail.errorTitle'),
					message: this.$tc('scopplatformredirecter.detail.fileNotCreated')
				});
				this.exportLoading = false;
				return;
			});

			if(!this.exportLoading)
				return;

			this.exportLoading = false;

			if(response['status'] != 200){
				this.createNotificationError({
					title: this.$tc('scopplatformredirecter.detail.errorTitle'),
					message: this.$tc('scopplatformredirecter.detail.fileNotCreated')
				});
				return;
			}

			await window.open('/api/_action/scop/platform/redirecter/download-export?filename=' + response['data']['file'], '_blank');

		}
	},

});