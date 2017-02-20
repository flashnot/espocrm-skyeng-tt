Espo.define('she:views/hi-email/record/row-actions/default', 'views/record/row-actions/default', function (Dep) {

    return Dep.extend({
        getActionList: function () {
            var list = [{
                action: 'quickView',
                label: 'View',
                data: {
                    id: this.model.id
                }
            }];
            if (this.options.acl.edit) {
                list = list.concat([
                    {
                        action: 'quickRemove',
                        label: 'Remove',
                        data: {
                            id: this.model.id
                        }
                    }
                ]);
            }
            return list;
        }
    });
});

