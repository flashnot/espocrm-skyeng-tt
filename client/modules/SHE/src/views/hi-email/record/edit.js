Espo.define('she:views/hi-email/record/edit', ['views/record/edit', 'she:views/hi-email/record/detail'], function (Dep, Detail) {

    return Dep.extend({
        isWide: true,
        sideView: false
    });

});
