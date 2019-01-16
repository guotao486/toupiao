define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'prize/prize/index',
                    add_url: 'prize/prize/add',
                    edit_url: 'prize/prize/edit',
                    del_url: 'prize/prize/del',
                    multi_url: 'prize/prize/multi',
                    table: 'prize',
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
                        {field: 'title', title: __('Title'),operate:'like'},
                        {field: 'year_title', title: __('Year'),operate:false},
                        {field: 'y_id', title: __('Year'),visible: false,addClass: "selectpage", extend: "data-source='prize/year/index' data-field='title' "},
                        {field: 'lv', title: __('Lv')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ballot_num', title: __('Ballot_num'),operate:false},
                        {field: 'is_repeat', title: __('Is_repeat'),yes: '1', no: '0',formatter:Table.api.formatter.toggle},
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