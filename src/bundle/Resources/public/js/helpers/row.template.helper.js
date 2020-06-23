export default class RowTemplateGenerator {
    constructor(type, template) {
        this.type = type;
        this.template = template;
        this.methods = {
            Mostsearch: 'mostSearch',
            lastsearch: 'lastSearch',
            Nohitssearch: 'noHitsSearch',
            dashboard: 'dashboard',
            lastorders: 'lastOrders',
            bestclients: 'bestClients',
        };
    }

    mostSearch(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ LOG_MESSAGE }}', row.logMessage || row[0]);
        rowTemplate = rowTemplate.replace('{{ AMOUNT }}', row.amount || row[1]);
        rowTemplate = rowTemplate.replace('{{ HITS }}', row.hits || row[2]);

        return rowTemplate;
    }

    lastSearch(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ TIMESTAMP }}', formatShortDateTime(new Date((row.logTimestamp || row[0]) * 1000)));
        rowTemplate = rowTemplate.replace('{{ LOG_MESSAGE }}', row.logMessage || row[1]);
        rowTemplate = rowTemplate.replace('{{ RESULTS }}', row.results || row[2]);

        return rowTemplate;
    }

    noHitsSearch(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ LOG_MESSAGE }}', row.logMessage || row[0]);
        rowTemplate = rowTemplate.replace('{{ AMOUNT }}', row.amount || row[1]);
        rowTemplate = rowTemplate.replace('{{ HITS }}', row.hits || row[2]);

        return rowTemplate;
    }

    dashboard(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ SKU }}', row.sku || row[0]);
        rowTemplate = rowTemplate.replace('{{ COUNT_SKU }}', row.countSku || row[1]);
        rowTemplate = rowTemplate.replace('{{ NAME }}', row.name || row[2]);

        return rowTemplate;
    }

    lastOrders(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ DATE }}', row.date || row[0]);
        rowTemplate = rowTemplate.replace('{{ BUYER }}', row.buyer || row[1]);
        rowTemplate = rowTemplate.replace('{{ TOTAL }}', row.total || row[2]);
        rowTemplate = rowTemplate.replace('{{ CURRENCY }}', row.currency || row[3]);

        return rowTemplate;
    }

    bestClients(row) {
        const rowTemplate = this.template;

        rowTemplate = rowTemplate.replace('{{ NAME }}', row.name || row[0]);
        rowTemplate = rowTemplate.replace('{{ AMOUNT }}', row.amount || row[1]);
        rowTemplate = rowTemplate.replace('{{ CURRENCY }}', row.currency || row[2]);

        return rowTemplate;
    }

    getTemplate(row) {
        const methodName = this.methods[this.type];

        this[methodName](row);
    }
}
