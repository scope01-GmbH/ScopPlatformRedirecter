import template from './scop-platform-redirect-import-modal.html.twig';

const {Component, Mixin} = Shopware;

Component.register('scop-platform-redirect-import-modal', {
    template,

    inject: [
        'syncService', 'loginService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            selectedFile: null,
            noFile: true,
            processing: false,
            override: false,
            overrideID: true
        };
    },

    props: {
        show: {
            type: Boolean,
            required: true,
            default: false,
        }
    },


    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        onClose() {
            if (!this.processing)
                this.$emit('close');
        },
        onFileChange(file) {
            this.file = file;
            this.noFile = file == null;
        },
        async startImport() { //Importing the file

            this.processing = true;

            const formData = new FormData();
            formData.set("file", this.file);
            formData.set("overrideID", this.overrideID);
            formData.set("override", this.override);

            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`
            };


            //Sending the Request to the Backend, catching an Error
            const httpClient = this.syncService.httpClient;
            const response = await httpClient.post("/_action/scop/platform/redirecter/import", formData, {headers: headers}).catch((err) => {

                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.list.fileNotImported')
                });

                this.processing = false;
            });
            if (!this.processing) //Returns if an error was caught
                return;

            if (response['status'] !== 200 || response['data']['detail'] !== 'File Imported!') { //An Error occurred whilst importing, notify the User
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.list.error.' + response['data']['detail'])
                });
                this.processing = false;
                this.$emit('updateList');
                return;
            } else { //Imported successfully
                if (response['data']['error'] > 0) { //There were invalid lines in the file
                    this.createNotification({
                        title: this.$tc('scopplatformredirecter.list.importDone'),
                        message: this.$tc('scopplatformredirecter.list.fileImportedError', 0, {
                            amount: response['data']['amount'],
                            skipped: response['data']['skipped'],
                            error: response['data']['error']
                        })
                    });
                } else if (response['data']['skipped'] > 0) { //Some Redirects where skipped
                    this.createNotification({
                        title: this.$tc('scopplatformredirecter.list.importDone'),
                        message: this.$tc('scopplatformredirecter.list.fileImported', 0, {
                            amount: response['data']['amount'],
                            skipped: response['data']['skipped']
                        })
                    });
                } else { //Every Redirect was imported
                    this.createNotification({
                        title: this.$tc('scopplatformredirecter.list.importDone'),
                        message: this.$tc('scopplatformredirecter.list.fileImportedNoSkip', 0, {
                            amount: response['data']['amount']
                        })
                    });
                }
            }

            this.processing = false;
            this.$emit('updateList'); //Updating the List
            this.$emit('close'); //Closing the modal
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
