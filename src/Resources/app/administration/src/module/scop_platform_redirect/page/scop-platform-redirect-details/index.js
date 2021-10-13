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
            return this.$tc('scopplatformredirecter.detail.helpText', 0, {link: '<sw-external-link href="' + this.$tc('scopplatformredirecter.general.moreInformationLink') + '">' + this.$tc('scopplatformredirecter.detail.helpHere') + '</sw-external-link>'});
        }
    },

    methods: {
        getRedirect() {
            this.repository.get(this.$route.params.id, Shopware.Context.api).then((entity) => {
                this.redirect = entity;
            })
        },

        onClickSave() {
            //Checking if source and target URL are the same, otherwise proceed
            if (this.redirect.sourceURL === this.redirect.targetURL) {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.detail.errorSameUrlDescription')
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
