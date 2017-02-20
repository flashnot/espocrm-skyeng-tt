Espo.define('she:views/hi-email/record/edit-quick', 'views/record/edit', function (Dep, Detail) {

    return Dep.extend({
        fullFormDisabled: true,

        isWide: true,

        sideView: false,

        bottomView: null,

        dropdownItemList: []
    });
});

