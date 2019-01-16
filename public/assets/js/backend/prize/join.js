define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'prize/join/index',
                    add_url: 'prize/join/add',
                    edit_url: 'prize/join/edit',
                    del_url: 'prize/join/del',
                    multi_url: 'prize/join/multi',
                    table: 'prize_join',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'year_title', title: __('Y_id'),operate:false},
                        {field: 'prize_title', title: __('P_id'),operate:false},
                        {field: 'y_id', title: __('Y_id'),visible: false,addClass: "selectpage", extend: "data-source='prize/year/index' data-field='title' "},
                        {field: 'p_id', title: __('P_id'),visible: false,addClass: "selectpage", extend: "data-source='prize/prize/index' data-field='title' "},
                        {field: 'userId', title: __('Userid')},
                        {field: 'ballot', title: __('Ballot')},
                        {field: 'name', title: __('Name')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});