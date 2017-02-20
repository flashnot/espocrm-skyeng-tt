Espo.define('she:views/hi-email/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({
        duplicateAction: false,
        editModeDisabled: true,
        readOnly: true,
        buttonList: [
            {
                name: 'delete',
                label: 'Remove',
                style: 'danger'
            }
        ],

        dropdownItemList: []
    });
});

