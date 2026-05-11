import template from './scop-platform-redirect-not-found-list.html.twig';
import './scop-platform-redirect-not-found-list.scss';

const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const inAppPurchaseId = 'scopPlatformRedirecterPremium';

Shopware.Component.register('scop-platform-redirect-not-found-list', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            isLoading: false,
            notFoundLogs: null,
            total: 0,
            page: 1,
            limit: 25,
            sortBy: 'hitCount',
            sortDirection: 'DESC',
            term: '',
            showCreateRedirectModal: false,
            currentNotFoundLog: null,
            showDetailsModal: false,
            currentDetailsItem: null,
            filterLinked: 'open',
            stats: {
                total: 0,
                open: 0,
                linked: 0,
                ignored: 0,
            },
            showBulkCreateModal: false,
            bulkSelection: [],
            bulkSelectionAll: [],
            showBulkDeleteModal: false,
            isBulkDeleting: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        inAppActive() {
            return Shopware.InAppPurchase.isActive('ScopPlatformRedirecter', inAppPurchaseId);
        },

        inAppPurchaseCheckout() {
            return Shopware.Store.get('inAppPurchaseCheckout');
        },

        notFoundLogRepository() {
            return this.repositoryFactory.create('scop_platform_redirecter_404');
        },

        notFoundLogCriteria() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('redirect');
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            if (this.term) {
                criteria.setTerm(this.term);
            }

            if (this.filterLinked === 'open') {
                criteria.addFilter(Criteria.equals('redirectId', null));
                criteria.addFilter(Criteria.equals('ignored', false));
            } else if (this.filterLinked === 'linked') {
                criteria.addFilter(Criteria.not('AND', [Criteria.equals('redirectId', null)]));
            } else if (this.filterLinked === 'ignored') {
                criteria.addFilter(Criteria.equals('ignored', true));
            }

            return criteria;
        },

        columns() {
            return [
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: this.$tc('scopplatformredirecter.notFound.columnUrl'),
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'hitCount',
                    dataIndex: 'hitCount',
                    label: this.$tc('scopplatformredirecter.notFound.columnHitCount'),
                    allowResize: true,
                },
                {
                    property: 'lastHitAt',
                    dataIndex: 'lastHitAt',
                    label: this.$tc('scopplatformredirecter.notFound.columnLastHit'),
                    allowResize: true,
                },
                {
                    property: 'salesChannel',
                    dataIndex: 'salesChannel',
                    label: this.$tc('scopplatformredirecter.notFound.columnSalesChannel'),
                    allowResize: true,
                },
                {
                    property: 'status',
                    dataIndex: 'redirectId',
                    label: this.$tc('scopplatformredirecter.notFound.columnStatus'),
                    allowResize: true,
                },
            ];
        },

        filterOptions() {
            return [
                { value: null, label: this.$tc('scopplatformredirecter.notFound.filterAll') },
                { value: 'open', label: this.$tc('scopplatformredirecter.notFound.filterOpen') },
                { value: 'linked', label: this.$tc('scopplatformredirecter.notFound.filterLinked') },
                { value: 'ignored', label: this.$tc('scopplatformredirecter.notFound.filterIgnored') },
            ];
        },
    },

    watch: {
        notFoundLogCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },
    },

    methods: {
        formatDate(date) {
            return Shopware.Filter.getByName('date')(date, {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        getRefererList(item) {
            if (!item || !Array.isArray(item.referers)) {
                return [];
            }
            return item.referers;
        },

        onShowDetails(item) {
            this.currentDetailsItem = item;
            this.showDetailsModal = true;
        },

        onCloseDetailsModal() {
            this.showDetailsModal = false;
            this.currentDetailsItem = null;
        },

        onCreateRedirectFromDetails() {
            const item = this.currentDetailsItem;
            this.onCloseDetailsModal();
            if (item) {
                this.onCreateRedirect(item);
            }
        },

        onClickIap() {
            this.inAppPurchaseCheckout.request({ identifier: inAppPurchaseId }, 'ScopPlatformRedirecter');
        },

        async getList() {
            if (!this.inAppActive) {
                this.notFoundLogs = null;
                this.total = 0;
                this.resetStats();
                return;
            }

            this.isLoading = true;

            try {
                const result = await this.notFoundLogRepository.search(this.notFoundLogCriteria);
                this.notFoundLogs = result;
                this.total = result.total;
            } catch {
                this.notFoundLogs = null;
                this.total = 0;
            } finally {
                this.isLoading = false;
            }

            this.loadStats();
        },

        resetStats() {
            this.stats = { total: 0, open: 0, linked: 0, ignored: 0 };
        },

        async loadStats() {
            if (!this.inAppActive) {
                this.resetStats();
                return;
            }
            try {
                const ctx = Shopware.Context.api;
                const totalCriteria = new Criteria(1, 1);
                const openCriteria = new Criteria(1, 1);
                openCriteria.addFilter(Criteria.equals('redirectId', null));
                openCriteria.addFilter(Criteria.equals('ignored', false));
                const linkedCriteria = new Criteria(1, 1);
                linkedCriteria.addFilter(Criteria.not('AND', [Criteria.equals('redirectId', null)]));
                const ignoredCriteria = new Criteria(1, 1);
                ignoredCriteria.addFilter(Criteria.equals('ignored', true));

                const [total, open, linked, ignored] = await Promise.all([
                    this.notFoundLogRepository.searchIds(totalCriteria, ctx),
                    this.notFoundLogRepository.searchIds(openCriteria, ctx),
                    this.notFoundLogRepository.searchIds(linkedCriteria, ctx),
                    this.notFoundLogRepository.searchIds(ignoredCriteria, ctx),
                ]);

                this.stats = {
                    total: total.total ?? 0,
                    open: open.total ?? 0,
                    linked: linked.total ?? 0,
                    ignored: ignored.total ?? 0,
                };
            } catch {
                this.resetStats();
            }
        },

        onSelectionChange(selection) {
            const items = selection && typeof selection === 'object' ? Object.values(selection) : [];
            this.bulkSelectionAll = items;
            this.bulkSelection = items.filter((entry) => !entry.redirectId);
        },

        onOpenBulkCreateModal() {
            if (this.bulkSelection.length === 0) {
                return;
            }
            this.showBulkCreateModal = true;
        },

        onCloseBulkCreateModal() {
            this.showBulkCreateModal = false;
        },

        onBulkRedirectsCreated() {
            this.showBulkCreateModal = false;
            this.bulkSelection = [];
            this.bulkSelectionAll = [];
            if (this.$refs.listing && typeof this.$refs.listing.resetSelection === 'function') {
                this.$refs.listing.resetSelection();
            }
            this.getList();
            this.createNotificationSuccess({
                message: this.$tc('scopplatformredirecter.notFound.bulkRedirectsCreatedSuccess'),
            });
        },

        onOpenBulkDeleteModal() {
            if (this.bulkSelectionAll.length === 0) {
                return;
            }
            this.showBulkDeleteModal = true;
        },

        onCloseBulkDeleteModal() {
            if (this.isBulkDeleting) {
                return;
            }
            this.showBulkDeleteModal = false;
        },

        async onConfirmBulkDelete() {
            this.isBulkDeleting = true;
            try {
                const ids = this.bulkSelectionAll.map((entry) => entry.id);
                await this.notFoundLogRepository.syncDeleted(ids, Shopware.Context.api);
                this.showBulkDeleteModal = false;
                this.bulkSelection = [];
                this.bulkSelectionAll = [];
                if (this.$refs.listing && typeof this.$refs.listing.resetSelection === 'function') {
                    this.$refs.listing.resetSelection();
                }
                this.getList();
                this.createNotificationSuccess({
                    message: this.$tc('scopplatformredirecter.notFound.bulkDeleteSuccess'),
                });
            } catch {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.bulkDeleteError'),
                });
            } finally {
                this.isBulkDeleting = false;
            }
        },

        onCreateRedirect(item) {
            this.currentNotFoundLog = item;
            this.showCreateRedirectModal = true;
        },

        onCloseCreateRedirectModal() {
            this.showCreateRedirectModal = false;
            this.currentNotFoundLog = null;
        },

        onRedirectCreated() {
            this.showCreateRedirectModal = false;
            this.currentNotFoundLog = null;
            this.getList();
            this.createNotificationSuccess({
                message: this.$tc('scopplatformredirecter.notFound.redirectCreatedSuccess'),
            });
        },

        async onDeleteEntry(item) {
            try {
                await this.notFoundLogRepository.delete(item.id, Shopware.Context.api);
                this.getList();
                this.createNotificationSuccess({
                    message: this.$tc('scopplatformredirecter.notFound.deleteSuccess'),
                });
            } catch {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.deleteError'),
                });
            }
        },

        async onIgnoreEntry(item) {
            try {
                item.ignored = true;
                await this.notFoundLogRepository.save(item, Shopware.Context.api);
                this.getList();
            } catch {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.ignoreError'),
                });
            }
        },

        async onUnignoreEntry(item) {
            try {
                item.ignored = false;
                await this.notFoundLogRepository.save(item, Shopware.Context.api);
                this.getList();
            } catch {
                this.createNotificationError({
                    title: this.$tc('scopplatformredirecter.general.errorTitle'),
                    message: this.$tc('scopplatformredirecter.notFound.ignoreError'),
                });
            }
        },

        onFilterChange(value) {
            this.filterLinked = value;
            this.page = 1;
        },

        updateCriteria() {
            this.page = 1;
        },
    },
});
