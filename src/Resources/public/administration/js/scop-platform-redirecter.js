(function(){var e={436:function(){let{Component:e}=Shopware;Shopware.Component.extend("scop-platform-redirect-create","scop-platform-redirect-details",{methods:{getRedirect(){this.redirect=this.repository.create(Shopware.Context.api),this.redirect.httpCode=302,this.redirect.enabled=!0},onClickSave(){if(this.redirect.sourceURL===this.redirect.targetURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorSameUrlDescription")});return}if(!this.redirect.sourceURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorEmptySourceURL")});return}if(!this.redirect.targetURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorEmptyTargetURL")});return}this.isLoading=!0,this.repository.save(this.redirect,Shopware.Context.api).then(()=>{this.isLoading=!1,this.processSuccess=!0}).catch(e=>{this.isLoading=!1,this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:e})})}}})},448:function(){let{Component:e}=Shopware;e.override("sw-import-export-edit-profile-general",{computed:{supportedEntities(){let e=this.$super("supportedEntities");return e.push({value:"scop_platform_redirecter_redirect",label:this.$tc("scopplatformredirecter.general.title"),type:"import-export"}),e}}})}},t={};function r(i){var o=t[i];if(void 0!==o)return o.exports;var a=t[i]={exports:{}};return e[i](a,a.exports,r),a.exports}r.p="bundles/scopplatformredirecter/",window?.__sw__?.assetPath&&(r.p=window.__sw__.assetPath+"/bundles/scopplatformredirecter/"),function(){"use strict";let e=Shopware.Data.Criteria,{Component:t,Mixin:i}=Shopware;Shopware.Component.register("scop-platform-redirect-list",{template:'{% block scop_platform_redirect_list %}\n    <sw-page class="scop-platform-redirect-list">\n        <template #smart-bar-actions>\n            {% block scop_platform_redirect_list_smarbar %}\n                <sw-button variant="primary" :routerLink="{name: \'scop.platform.redirect.create\'}">\n                    {{ $t(\'scopplatformredirecter.list.createButton\') }}\n                </sw-button>\n                <sw-button @click="onClickExport" :isLoading="exportLoading" :disabled="noRedirect">\n                    {{ $t(\'scopplatformredirecter.list.exportAllButton\') }}\n                </sw-button>\n                <sw-button @click="onClickImport">\n                    {{ $t(\'scopplatformredirecter.list.importButton\') }}\n                </sw-button>\n                <sw-button class="mt-external-link" variant="contrast" :link="$tc(\'scopplatformredirecter.list.faqButton.link\')">\n                    {{ $t(\'scopplatformredirecter.list.faqButton.text\') }} <sw-icon name="regular-external-link" small="true"/>\n                </sw-button>\n            {% endblock %}\n        </template>\n        <template #content>\n            {% block scop_platform_redirect_list_content %}\n                <sw-entity-listing\n                        v-if="redirect"\n                        :items="redirect"\n                        :repository="repository"\n                        :columns="columns"\n                        detailRoute="scop.platform.redirect.details"\n                        @update-records="onUpdate"\n                >\n                    <template #column-queryParamsHandling="{ item }">\n                        {{ $tc(\'scopplatformredirecter.list.queryParamsHandlingValues.\' + item.queryParamsHandling) }}\n                    </template>\n                    <template #column-salesChannel="{ item }">\n                        {{ item.salesChannel ? item.salesChannel.translated.name : $tc(\'scopplatformredirecter.list.allSalesChannels\') }}\n                    </template>\n                </sw-entity-listing>\n            {% endblock %}\n            {% block scop_platform_redirect_list_view_import_modal %}\n                <scop-platform-redirect-import-modal\n                        :show="showImportExportModal"\n                        :type="modalType"\n                        @close="closeImportExport"\n                        @updateList="updateList"\n                >\n                </scop-platform-redirect-import-modal>\n            {% endblock %}\n        </template>\n    </sw-page>\n{% endblock %}\n',inject:["repositoryFactory","syncService","loginService","importExport"],mixins:[i.getByName("notification")],data(){return{repository:null,redirect:null,exportLoading:!1,noRedirect:!0,showImportExportModal:!1,modalType:"export",page:1,limit:25}},metaInfo(){return{title:this.$createTitle()}},computed:{columns(){return[{property:"sourceURL",dataIndex:"sourceURL",label:this.$tc("scopplatformredirecter.list.columnSourceUrl"),routerLink:"scop.platform.redirect.details",inlineEdit:"string",allowResize:!0,primary:!0},{property:"targetURL",dataIndex:"targetURL",label:this.$tc("scopplatformredirecter.list.columnTargetUrl"),inlineEdit:"string",allowResize:!0},{property:"httpCode",dataIndex:"httpCode",label:this.$tc("scopplatformredirecter.list.columnHttpCode"),allowResize:!0},{property:"enabled",dataIndex:"enabled",label:this.$tc("scopplatformredirecter.list.columnEnabled"),inlineEdit:"boolean"},{property:"queryParamsHandling",dataIndex:"queryParamsHandling",label:this.$tc("scopplatformredirecter.list.columnQueryParamsHandling"),allowResize:!0},{property:"salesChannel",dataIndex:"salesChannel",label:this.$tc("scopplatformredirecter.list.salesChannel"),allowResize:!0}]}},created(){this.repository=this.repositoryFactory.create("scop_platform_redirecter_redirect");let t=new e(this.page,this.limit);t.addAssociation("salesChannel"),this.repository.search(t,Shopware.Context.api).then(e=>{this.redirect=e})},methods:{onClickExport(){this.modalType="export",this.showImportExportModal=!0},onUpdate(e){this.noRedirect=0===e.length},onClickImport(){this.modalType="import",this.showImportExportModal=!0},closeImportExport(){this.showImportExportModal=!1},updateList(){let e=this.redirect.criteria;this.repository.search(e,Shopware.Context.api).then(e=>{this.redirect=e})}}});let{Component:o,Mixin:a}=Shopware;o.register("scop-platform-redirect-details",{template:'{% block scop_platform_redirect_details %}\n    <sw-page class="scop-platform-redirect-details">\n        <template #smart-bar-actions>\n            <sw-button :routerLink="{name: \'scop.platform.redirect.list\'}">\n                {{ $t(\'scopplatformredirecter.detail.cancelButton\') }}</sw-button>\n            <sw-button-process :isLoading="isLoading"\n                               :processSuccess="processSuccess" variant="primary"\n                               @update:processSuccess="saveFinish" @click="onClickSave">\n                {{ $t(\'scopplatformredirecter.detail.saveButton\') }}</sw-button-process>\n        </template>\n        <template #content>\n            <sw-card-view>\n                <sw-card v-if="redirect" :isLoading="isLoading">\n                    <sw-text-field :label="$t(\'scopplatformredirecter.detail.sourceUrlLabel\')" v-model:value="redirect.sourceURL"\n                              validation="required"></sw-text-field>\n                    <sw-text-field :label="$t(\'scopplatformredirecter.detail.targetUrlLabel\')" v-model:value="redirect.targetURL"\n                              validation="required"></sw-text-field>\n                    <sw-select-number-field :label="$t(\'scopplatformredirecter.detail.httpCodeLabel\')"\n                                            v-model:value="redirect.httpCode" validation="required" @update:value="transformHttpCodeValueToNumber">\n                        <option value=301>{{ $t(\'scopplatformredirecter.detail.httpCodeLabelValues.301\') }}</option>\n                        <option value=302>{{ $t(\'scopplatformredirecter.detail.httpCodeLabelValues.302\') }}</option>\n                    </sw-select-number-field>\n                    <sw-switch-field :label="$tc(\'scopplatformredirecter.detail.enabledLabel\')"\n                                     v-model:value="redirect.enabled" validation="required"></sw-switch-field >\n                    <sw-select-number-field :label="$t(\'scopplatformredirecter.detail.queryParamsHandling\')"\n                                            v-model:value="redirect.queryParamsHandling" validation="required" @update:value="transformQueryFieldValueToNumber">\n                        <option value=0>{{ $t(\'scopplatformredirecter.detail.queryParamsHandlingValues.consider\') }}</option>\n                        <option value=1>{{ $t(\'scopplatformredirecter.detail.queryParamsHandlingValues.ignore\') }}</option>\n                        <option value=2>{{ $t(\'scopplatformredirecter.detail.queryParamsHandlingValues.transfer\') }}</option>\n                    </sw-select-number-field>\n\n                    <sw-entity-single-select v-model:value="redirect.salesChannelId" entity="sales_channel" :resetOption="$t(\'scopplatformredirecter.detail.salesChannel.all\')" :label="$t(\'scopplatformredirecter.detail.salesChannel.select\')"></sw-entity-single-select>\n                </sw-card>\n            </sw-card-view>\n        </template>\n    </sw-page>\n{% endblock %}\n',inject:["repositoryFactory"],mixins:[a.getByName("notification")],metaInfo(){return{title:this.$createTitle()}},data(){return{redirect:null,isLoading:!1,processSuccess:!1,repository:null}},created(){this.repository=this.repositoryFactory.create("scop_platform_redirecter_redirect"),this.getRedirect()},methods:{getRedirect(){this.repository.get(this.$route.params.id,Shopware.Context.api).then(e=>{this.redirect=e})},onClickSave(){if(this.redirect.sourceURL===this.redirect.targetURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorSameUrlDescription")});return}if(!this.redirect.sourceURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorEmptySourceURL")});return}if(!this.redirect.targetURL){this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:this.$tc("scopplatformredirecter.detail.errorEmptyTargetURL")});return}this.isLoading=!0,this.repository.save(this.redirect,Shopware.Context.api).then(()=>{this.getRedirect(),this.isLoading=!1,this.processSuccess=!0}).catch(e=>{this.isLoading=!1,this.createNotificationError({title:this.$tc("scopplatformredirecter.general.errorTitle"),message:e})})},saveFinish(){this.processSuccess=!1,this.$router.push({name:"scop.platform.redirect.list"})},transformQueryFieldValueToNumber(){this.redirect.queryParamsHandling=Number(this.redirect.queryParamsHandling)},transformHttpCodeValueToNumber(){this.redirect.httpCode=Number(this.redirect.httpCode)}}}),r(436);let{Component:s,Mixin:l}=Shopware,n=Shopware.Data.Criteria;s.register("scop-platform-redirect-import-modal",{template:'{% block scop_platform_redirect_import_modal %}\n    <div class="scop-platform-redirect-import-modal">\n        <sw-modal\n                v-if="show"\n                class="scop-platform-redirect-import-modal"\n                :title="$tc(\'scopplatformredirecter.list.\' + type + \'Modal.title\')"\n                variant="small"\n                @modal-close="onClose"\n        >\n            {% block scop_platform_redirect_import_modal_content %}\n\n                <sw-card :hero="true" :isLoading="processing" v-if="type === \'import\'">\n                    <sw-file-input\n                            v-model:value="selectedFile"\n                            :maxFileSize="8*1024*1024"\n                            @update:value="onFileChange">\n                    </sw-file-input>\n                </sw-card>\n            {% endblock %}\n            {% block scop_platform_redirect_import_modal_options %}\n                <sw-entity-single-select\n                        :label="$tc(\'sw-import-export.importer.profileLabel\')"\n                        :criteria="profileCriteria"\n                        entity="import_export_profile"\n                        label-property="label"\n                        :value="selectedProfileId"\n                        required\n                        show-clearable-button\n                        @update:value="onProfileSelect"></sw-entity-single-select>\n            {% endblock %}\n            {% block scop_platform_redirect_import_modal_footer %}\n                <template #modal-footer>\n                    <sw-button @click="onClose" :disabled="processing">\n                        {{ $t(\'scopplatformredirecter.list.\' + type + \'Modal.cancel\') }}\n                    </sw-button>\n                    <sw-button variant="primary" :disabled="noFile && type !== \'export\'" :isLoading="processing" @click="startProcess">\n                        {{ $t(\'scopplatformredirecter.list.\' + type + \'Modal.start\') }}\n                    </sw-button>\n                </template>\n            {% endblock %}\n        </sw-modal>\n    </div>\n{% endblock %}\n',inject:["importExport","repositoryFactory"],mixins:[l.getByName("notification")],data(){return{selectedFile:null,noFile:!0,processing:!1,selectedProfileId:null}},props:{show:{type:Boolean,required:!0,default:!1},type:{type:String,required:!0,default:"import"}},emits:["import-started","export-started"],metaInfo(){return{title:this.$createTitle()}},computed:{importExportProfileRepository(){return this.repositoryFactory.create("import_export_profile")},profileCriteria(){let e=new n(1,25);return e.addSorting(n.sort("label")),e.addFilter(n.equals("sourceEntity","scop_platform_redirecter_redirect")),e.addQuery(n.contains("type","import")),e}},created(){let e=new n(1,25);e.addFilter(n.equals("technicalName","default_scop_redirect")),this.importExportProfileRepository.search(e).then(e=>{e[0]&&(this.selectedProfileId=e[0].id)})},methods:{onClose(){this.processing||this.$emit("close")},onFileChange(e){this.file=e,this.noFile=null==e},onProfileSelect(e){this.selectedProfileId=e},async startProcess(){"import"==this.type?this.startImport():"export"==this.type&&this.startExport()},async startImport(){this.processing=!0;let e=this.selectedProfileId;this.importExport.import(e,this.selectedFile,this.handleProgress).then(()=>{this.selectedFile=null}).catch(e=>{e.response&&e.response.data&&e.response.data.errors?e.response.data.errors.forEach(e=>{this.createNotificationError({message:`${e.code}: ${e.detail}`})}):this.createNotificationError({message:e.message}),this.processing=!1})},async startExport(){this.processing=!0,this.importExport.export(this.selectedProfileId,this.handleProgress,this.config).catch(e=>{e.response&&e.response.data&&e.response.data.errors?e.response.data.errors.forEach(e=>{this.createNotificationError({message:`${e.code}: ${e.detail}`})}):this.createNotificationError({message:e.message}),this.processing=!1})},handleProgress(e){"export"===e.activity?(this.createNotificationInfo({message:this.$tc("sw-import-export.exporter.messageExportStarted")}),this.$emit("export-started",e),this.$router.push({name:"sw.import.export.index.export"})):"import"===e.activity&&(this.createNotificationInfo({message:this.$tc("sw-import-export.importer.messageImportStarted")}),this.$emit("import-started",e)),this.processing=!1,this.$emit("updateList"),this.$emit("close")}}});var c=JSON.parse('{"scopplatformredirecter":{"general":{"title":"Weiterleitungen","errorTitle":"Fehler"},"list":{"columnSourceUrl":"Quell URL","columnTargetUrl":"Ziel URL","columnHttpCode":"HTTP Status Code","columnEnabled":"Aktiviert","columnQueryParamsHandling":"Umgang mit Query Parametern","createButton":"Weiterleitung anlegen","exportAllButton":"Alle Exportieren","importButton":"Importieren","faqButton":{"text":"FAQ","link":"https://scope01.com/shopware-redirect-plugin/"},"fileNotCreated":"Die Datei konnte nicht erstellt werden","fileNotImported":"Die Datei konnte nicht importiert werden","invalidFile":"Ung\xfcltige Datei (Es muss sich um eine .csv Datei handeln)","invalidCsvFile":"Ung\xfcltige .csv Datei","importDone":"Import abgeschlossen","fileImportedNoSkip":"Es wurden {amount} Weiterleitungen importiert","fileImported":"Es wurden {amount} Weiterleitungen importiert.<br>{skipped} Weiterleitungen wurden nicht importiert, da sie bereits vorhanden waren und nicht \xfcberschrieben werden sollten","fileImportedError":"Es wurden {amount} Weiterleitungen importiert.<br>{skipped} Weiterleitungen wurden nicht importiert, da sie bereits vorhanden waren und nicht \xfcberschrieben werden sollten.<br><b>{error} Weiterleitungen in der Datei sind ung\xfcltig!</b>","importModal":{"title":"Importieren","cancel":"Abbrechen","start":"Importieren"},"exportModal":{"title":"Exportieren","cancel":"Abbrechen","start":"Exportieren"},"queryParamsHandlingValues":{"0":"Ber\xfccksichtigen","1":"Ignorieren","2":"\xdcbernehmen"},"salesChannel":"Verkaufskanal","allSalesChannels":"Alle"},"detail":{"sourceUrlLabel":"Quell URL","targetUrlLabel":"Ziel URL","httpCodeLabel":"HTTP Status Code","enabledLabel":"Aktiviert","queryParamsHandling":"Umgang mit Query Parametern","queryParamsHandlingValues":{"consider":"Query Parameter bei der Suche ber\xfccksichtigen","ignore":"Query Parameter bei der Suche ignorieren","transfer":"Query Parameter bei der Suche ignorieren und zur Ziel-URL hinzuf\xfcgen"},"cancelButton":"Abbrechen","saveButton":"Speichern","errorSameUrlDescription":"Die Quell URL und Ziel URL d\xfcrfen nicht gleich sein","errorEmptySourceURL":"Die Quell URL darf nicht leer sein","errorEmptyTargetURL":"Die Ziel URL darf nicht leer sein","helpHere":"hier","httpCodeLabelValues":{"301":"301 (Permanent verschoben)","302":"302 (Tempor\xe4r verschoben)"},"salesChannel":{"select":"Verkaufskanal","all":"Alle Verkaufskan\xe4le"}}}}'),p=JSON.parse('{"scopplatformredirecter":{"general":{"title":"Redirects","errorTitle":"Error"},"list":{"columnSourceUrl":"Source URL","columnTargetUrl":"Target URL","columnHttpCode":"HTTP Status Code","columnEnabled":"Enabled","columnQueryParamsHandling":"Query Parameter Handling","createButton":"Add Redirect","exportAllButton":"Export All","importButton":"Import","faqButton":{"text":"FAQ","link":"https://scope01.com/en/shopware-redirect-plugin/"},"fileNotCreated":"The file could not be created","fileNotImported":"The file could not be imported","invalidFile":"Invalid File (It must be a .csv file)","invalidCsvFile":"Invalid .csv File","importDone":"Import finished","fileImportedNoSkip":"{amount} Redirects where imported","fileImported":"{amount} Redirects where imported.<br>{skipped} Redirects where not imported because they already existed and should not be overridden","fileImportedError":"{amount} Redirects where imported.<br>{skipped} Redirects where not imported because they already existed and should not be overridden.<br><b>{error} Redirects in this file are invalid!</b>","importModal":{"title":"Import","cancel":"Cancel","start":"Import"},"exportModal":{"title":"Export","cancel":"Cancel","start":"Export"},"queryParamsHandlingValues":{"0":"Consider","1":"Ignore","2":"Transfer"},"salesChannel":"Sales Channel","allSalesChannels":"All"},"detail":{"sourceUrlLabel":"Source URL","targetUrlLabel":"Target URL","httpCodeLabel":"HTTP Status Code","enabledLabel":"Enabled","queryParamsHandling":"Query Parameter Handling","queryParamsHandlingValues":{"consider":"Consider Query Parameters during search","ignore":"Ignore Query Parameters during search","transfer":"Ignore Query Parameters during search and add them to the target URL"},"cancelButton":"Cancel","saveButton":"Save","errorSameUrlDescription":"The source URL and target URL must not be the same","errorEmptySourceURL":"The source URL mustn\'t be empty","errorEmptyTargetURL":"The target URL mustn\'t be empty","helpHere":"here","httpCodeLabelValues":{"301":"301 (Moved Permanently)","302":"302 (Moved Temporarily)"},"salesChannel":{"select":"Sales Channel","all":"All Sales Channels"}}}}');Shopware.Module.register("scop-platform-redirect",{type:"plugin",name:"scop-platform-redirect",title:"scopplatformredirecter.general.title",description:"scopplatformredirecter.general.title",color:"#019994",icon:"small-copy",routes:{list:{component:"scop-platform-redirect-list",path:"list"},details:{component:"scop-platform-redirect-details",path:"details/:id",meta:{parentPath:"scop.platform.redirect.list"}},create:{component:"scop-platform-redirect-create",path:"create",meta:{parentPath:"scop.platform.redirect.list"}}},settingsItem:[{to:"scop.platform.redirect.list",group:"shop",icon:"regular-double-chevron-right-s"}],snippets:{"de-DE":c,"en-GB":p}}),r(448)}()})();