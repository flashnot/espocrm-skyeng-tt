
Espo.define('she:views/dashlets/hi-email', 'views/dashlets/abstract/record-list', function (Dep) {

    return Dep.extend({

        rowActionsView: null,

        afterRender: function() {
            Dep.prototype.afterRender.call(this);
            this.actionCreate();
        }

    });
});

