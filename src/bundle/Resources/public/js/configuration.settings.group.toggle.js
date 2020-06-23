(function(global, doc) {
    const togglers = [...doc.querySelectorAll('.ez-card__body-display-toggler')];
    const toggleFieldTypeView = (event) => {
        event.preventDefault();

        const group = event.currentTarget.closest('.ez-card--commerce-configuration-group');

        if (group.classList.contains('ez-card--collapsed')) {
            doc.querySelector('.ez-card--commerce-configuration-group:not(.ez-card--collapsed)').classList.add('ez-card--collapsed');
            group.classList.remove('ez-card--collapsed');
        } else {
            group.classList.add('ez-card--collapsed');
        }
    };

    togglers.forEach((button) => button.addEventListener('click', toggleFieldTypeView, false));
})(window, window.document);
