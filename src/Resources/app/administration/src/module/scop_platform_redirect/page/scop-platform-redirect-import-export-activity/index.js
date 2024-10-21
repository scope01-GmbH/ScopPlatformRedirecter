const Criteria = Shopware.Data.Criteria;
const {Component} = Shopware;

Component.extend('scop-platform-redirect-import-export-activity', 'sw-import-export-activity', {
    computed: {
        activityCriteria() {
            const criteria = new Shopware.Data.Criteria();

            if (this.type === 'import') {
                criteria.addFilter(Criteria.multi(
                    'OR',
                    [
                        Criteria.equals('activity', 'import'),
                        Criteria.equals('activity', 'dryrun'),
                    ],
                ));
            } else if (this.type === 'export') {
                criteria.addFilter(Criteria.equals('activity', 'export'));
            }
            criteria.addFilter(Criteria.equals('profile.sourceEntity', 'scop_platform_redirecter_redirect'));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            criteria.setPage(1);
            criteria.addAssociation('user');
            criteria.addAssociation('file');
            criteria.addAssociation('profile');
            criteria.getAssociation('invalidRecordsLog')
                .addAssociation('file');

            return criteria;
        }
    }
})
