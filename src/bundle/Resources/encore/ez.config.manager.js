const path = require('path');

module.exports = (eZConfig, eZConfigManager) => {
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-content-edit-parts-js',
        newItems: [path.resolve(__dirname, '../public/js/field-type/ezvariants.js')],
    });

    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-content-edit-parts-css',
        newItems: [path.resolve(__dirname, '../public/scss/ezcommerce-field-type.scss')],
    });
};
