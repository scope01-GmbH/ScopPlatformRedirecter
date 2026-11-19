import template from './scop-platform-redirect-not-found-list.html.twig';
import './scop-platform-redirect-not-found-list.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const inAppPurchaseId = 'scopPlatformRedirecterPremium';
const filterConfigKey = 'grid.filter.scop-platform-redirect-not-found-list';

// The sidebar mounts its contents on open. Seed the native range fields on each mount.
Component.extend('scop-404-number-filter', 'sw-number-filter', {
    data() {
        return this.filter.value ? { numberValue: { ...this.filter.value } } : {};
    },
});

Component.extend('scop-404-date-filter', 'sw-date-filter', {
    data() {
        return this.filter.value ? { dateValue: { ...this.filter.value } } : {};
    },
});

function inclusiveRange(value, normalize) {
    const from = normalize(value?.from);
    const to = normalize(value?.to);

    return {
        ...(from !== null ? { gte: from } : {}),
        ...(to !== null ? { lte: to } : {}),
    };
}

function toStorageDate(value) {
    const date = value ? new Date(value) : null;

    return date && Number.isFinite(date.getTime())
        ? date.toISOString().slice(0, -1).replace('T', ' ')
        : null;
}

Component.register('scop-platform-redirect-not-found-list', {
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
            exportConfig: null,
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
            sidebarFiltersReady: false,
            sidebarFilterSave: Promise.resolve(),
            sidebarFilterValues: {
                hitCount: null,
                lastHitAt: null,
                salesChannel: null,
                status: ['open'],
            },
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

            this.applyNotFoundLogFilters(criteria);
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

        statusOptions() {
            return [
                { value: 'open', label: this.$tc('scopplatformredirecter.notFound.filterOpen') },
                { value: 'linked', label: this.$tc('scopplatformredirecter.notFound.filterLinked') },
                { value: 'ignored', label: this.$tc('scopplatformredirecter.notFound.filterIgnored') },
            ];
        },

        sidebarFilters() {
            return {
                hitCount: {
                    name: 'hitCount',
                    property: 'hitCount',
                    label: this.$tc('scopplatformredirecter.notFound.columnHitCount'),
                    fromFieldLabel: this.$tc('scopplatformredirecter.notFound.hitCountMin'),
                    toFieldLabel: this.$tc('scopplatformredirecter.notFound.hitCountMax'),
                    value: this.sidebarFilterValues.hitCount,
                },
                lastHitAt: {
                    name: 'lastHitAt',
                    property: 'lastHitAt',
                    label: this.$tc('scopplatformredirecter.notFound.columnLastHit'),
                    value: this.sidebarFilterValues.lastHitAt,
                },
                salesChannel: {
                    name: 'salesChannel',
                    property: 'salesChannel',
                    label: this.$tc('scopplatformredirecter.notFound.columnSalesChannel'),
                    schema: { entity: 'sales_channel', referenceField: 'id' },
                    value: this.sidebarFilterValues.salesChannel,
                },
            };
        },

        activeFilterCount() {
            const { hitCount, lastHitAt, salesChannelIds, statuses } = this.filterParameters;

            return [Object.keys(hitCount), Object.keys(lastHitAt), salesChannelIds, statuses]
                .filter((values) => values.length > 0).length;
        },

        filterParameters() {
            const { hitCount, lastHitAt, salesChannel, status } = this.sidebarFilterValues;
            const dates = inclusiveRange(lastHitAt, toStorageDate);
            if (dates.lte) {
                // Include the milliseconds of the final second of the selected day.
                dates.lte = dates.lte.replace(/\.000$/, '.999');
            }

            return {
                term: this.term,
                hitCount: inclusiveRange(hitCount, (value) => Number.isInteger(value) && value >= 0 ? value : null),
                lastHitAt: dates,
                salesChannelIds: (salesChannel ?? []).map(({ id }) => id),
                statuses: [...(status ?? [])],
            };
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

    async created() {
        if (!this.inAppActive) {
            return;
        }

        try {
            const response = await Shopware.Service('userConfigService').search([filterConfigKey]);
            this.sidebarFilterValues = {
                ...this.sidebarFilterValues,
                ...response?.data?.[filterConfigKey],
            };
        } catch {
            // The grid remains usable with its defaults if preferences are unavailable.
        } finally {
            await this.$nextTick();
            this.sidebarFiltersReady = true;
            this.getList();
        }
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

        applyNotFoundLogFilters(criteria) {
            const { term, hitCount, lastHitAt, salesChannelIds, statuses } = this.filterParameters;
            if (term) {
                criteria.setTerm(term);
            }

            if (Object.keys(hitCount).length) {
                criteria.addFilter(Criteria.range('hitCount', hitCount));
            }
            if (Object.keys(lastHitAt).length) {
                criteria.addFilter(Criteria.range('lastHitAt', lastHitAt));
            }
            if (salesChannelIds.length) {
                criteria.addFilter(Criteria.equalsAny('salesChannelId', salesChannelIds));
            }
            if (statuses.length) {
                criteria.addFilter(Criteria.multi('OR', statuses.map(this.statusCriteria)));
            }
        },

        statusCriteria(status) {
            if (status === 'ignored') {
                return Criteria.equals('ignored', true);
            }

            const unlinked = Criteria.equals('redirectId', null);
            return Criteria.multi('AND', [
                Criteria.equals('ignored', false),
                status === 'linked' ? Criteria.not('AND', [unlinked]) : unlinked,
            ]);
        },

        onExportCsv(mode) {
            if (!this.inAppActive || (mode === 'filtered' && !this.sidebarFiltersReady)) {
                return;
            }

            this.exportConfig = mode === 'filtered' ? {
                parameters: {
                    scopNotFoundLogFilter: this.filterParameters,
                },
            } : {};
        },

        onCloseExport() {
            this.exportConfig = null;
        },

        async getList() {
            if (!this.inAppActive) {
                this.notFoundLogs = null;
                this.total = 0;
                this.resetStats();
                return;
            }
            if (!this.sidebarFiltersReady) {
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
                openCriteria.addFilter(this.statusCriteria('open'));
                const linkedCriteria = new Criteria(1, 1);
                linkedCriteria.addFilter(this.statusCriteria('linked'));
                const ignoredCriteria = new Criteria(1, 1);
                ignoredCriteria.addFilter(this.statusCriteria('ignored'));

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

        onSidebarFilterUpdate(name, _criteria, value) {
            this.setSidebarFilter(name, Array.isArray(value) ? [...value] : { ...value });
        },

        onSidebarFilterReset(name) {
            if (this.sidebarFilterValues[name] !== null) {
                this.setSidebarFilter(name, null);
            }
        },

        setSidebarFilter(name, value) {
            this.sidebarFilterValues[name] = value;
            this.page = 1;

            const snapshot = Shopware.Utils.object.cloneDeep(this.sidebarFilterValues);
            this.sidebarFilterSave = this.sidebarFilterSave
                .then(() => Shopware.Service('userConfigService').upsert({ [filterConfigKey]: snapshot }))
                .catch(() => this.createNotificationError({
                    message: this.$tc('scopplatformredirecter.notFound.filterSaveError'),
                }));
        },

        updateCriteria() {
            this.page = 1;
        },
    },
});
