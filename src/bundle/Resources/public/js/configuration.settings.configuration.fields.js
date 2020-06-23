(function(global, doc) {
    const addItemButtons = doc.querySelectorAll('.ez-configuration-field-array .ez-btn--add-input');
    const removeItemButtons = doc.querySelectorAll('.ez-configuration-field-array .ez-btn--trash');
    const togglers = [...doc.querySelectorAll('.ez-configuration-field--boolean .ez-data-source__label')];
    const toggleCheckbox = (event) => {
        event.currentTarget.classList.toggle('is-checked');

        const isChecked = event.currentTarget.classList.contains('is-checked');

        event.currentTarget
            .closest('.ez-data-source')
            .querySelector(`.form-check-input[value="${isChecked}"]`)
            .setAttribute('checked', 'checked');
        event.currentTarget
            .closest('.ez-data-source')
            .querySelector(`.form-check-input[value="${!isChecked}"]`)
            .removeAttribute('checked');
    };
    const removeInputItem = (event) => {
        event.currentTarget.closest('.ez-configuration-field-array__item').remove();
    };
    const addInputItem = (event) => {
        const itemsWrapper = event.currentTarget
            .closest('.ez-configuration-field-array')
            .querySelector('.ez-configuration-field-array__items');
        const widget = itemsWrapper.dataset.prototype;
        const htmlWidget = doc.createRange().createContextualFragment(widget);

        itemsWrapper.append(htmlWidget);
        itemsWrapper
            .querySelector('.ez-configuration-field-array__item:last-child .ez-btn--trash')
            .addEventListener('click', removeInputItem, false);
    };

    togglers.forEach((button) => button.addEventListener('click', toggleCheckbox, false));
    addItemButtons.forEach((button) => button.addEventListener('click', addInputItem, false));
    removeItemButtons.forEach((button) => button.addEventListener('click', removeInputItem, false));
})(window, window.document);
