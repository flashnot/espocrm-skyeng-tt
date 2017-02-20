Espo.define('she:views/hi-email/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({
        rowActionsView: 'she:views/hi-email/record/row-actions/default',
        massActionList: ['remove'],
        checkAllResultMassActionList: ['remove'],
        mergeAction: false,
        massUpdateAction: false,
        exportAction: false,
        quickDetailDisabled: true,
        quickEditDisabled: true,
        massFollowDisabled: true,
        buttonList: [],

        buildRow: function (i, model, callback) {
            var key = model.id;

            this.rowList.push(key);
            this.getInternalLayout(function (internalLayout) {
                internalLayout = Espo.Utils.cloneDeep(internalLayout);
                this.prepareInternalLayout(internalLayout, model);

                this.createView(key, 'views/base', {
                    model: model,
                    acl: {
                        edit: this.getAcl().checkModel(model, 'edit')
                    },
                    el: this.options.el + ' .list-row[data-id="'+key+'"]',
                    optionsToPass: ['acl'],
                    noCache: true,
                    _layout: {
                        type: this._internalLayoutType,
                        layout: internalLayout
                    },
                    name: this.type + '-' + model.name
                }, callback);
            }.bind(this), model);
        }
    });
});

