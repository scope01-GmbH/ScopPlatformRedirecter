import template from './scop-platform-redirect-list.html.twig';
const Criteria = Shopware.Data.Criteria;

Shopware.Component.register('scop-platform-redirect-list', {
	template,

	inject: [
		'repositoryFactory'
	],
	
	data() {
		return {
			repository: null,
			redirect: null
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
				inlineEdit: 'number',
				allowResize: true
			}];
		}
	},
	 
	created() {
		this.repository = this.repositoryFactory.create('scop_platform_redirecter_redirect');
		this.repository.search(new Criteria(), Shopware.Context.api).then((result) => {
			this.redirect = result; });
	}

});