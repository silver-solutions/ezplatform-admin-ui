const path = require('path');

module.exports = (eZConfig, eZConfigManager) => {
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-layout-css',
        newItems: [
            path.resolve(__dirname, '../public/scss/ezcommerce-admin-ui.scss'),
            path.resolve(__dirname, '../public/scss/ezcommerce-field-type.scss'),
        ],
    });

    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-content-edit-parts-js',
        newItems: [
            path.resolve('./public/bundles/ezplatformadminui/js/scripts/admin.card.toggle.group.js'),
            path.resolve(__dirname, '../public/js/field-type/ezvariants.js'),
            path.resolve(__dirname, '../public/js/field-type/ezspecifications.js'),
            path.resolve(__dirname, '../public/js/field-type/ezspecifications.validator.js'),
        ],
    });

    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-content-type-edit-js',
        newItems: [path.resolve(__dirname, '../public/js/field-type/ezspecifications.js')],
    });

    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-location-view-js',
        newItems: [
            path.resolve(__dirname, '../public/js/price.sku.search.js'),
            path.resolve(__dirname, '../public/js/stock.sku.search.js'),
            path.resolve(__dirname, '../public/js/user.overview.chart.js'),
        ],
    });
};
