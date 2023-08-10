import template from './scop-platform-redirect-details.html.twig';

const {Component, Mixin} = Shopware;

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

    computed: {
        helptext() {
            if (hasExternalLink())
                return this.$tc('scopplatformredirecter.detail.helpText', 0, {link: '<sw-external-link href="' + this.$tc('scopplatformredirecter.general.moreInformationLink') + '">' + this.$tc('scopplatformredirecter.detail.helpHere') + '</sw-external-link>'});
            else
                return this.$tc('scopplatformredirecter.detail.helpText', 0, {link: '<a style="color: #1596ff" href="' + this.$tc('scopplatformredirecter.general.moreInformationLink') + '">' + this.$tc('scopplatformredirecter.detail.helpHere') + '</a>'});
        }
    },

    methods: {
        getRedirect() {
            this.repository.get(this.$route.params.id, Shopware.Context.api).then((entity) => {
                this.redirect = entity;
            })
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
            this.repository.save(this.redirect, Shopware.Context.api).then(() => { //Updating the Redirect in the Database
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
        }
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
