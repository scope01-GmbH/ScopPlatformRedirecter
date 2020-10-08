import template from './scop_platform_redirect_list.html.twig';

Shopware.Component.register('scop_platform_redirect_list', {
	template,
	
	metaInfo() {
		return {
			title: this.$createTitle()
		};
	}
});