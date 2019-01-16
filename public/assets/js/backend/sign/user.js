define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sign/user/index',
                    add_url: 'sign/user/add',
                    edit_url: 'sign/user/edit',
                    del_url: 'sign/user/del',
                    multi_url: 'sign/user/multi',
                    table: 'sign_user',
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
                        {field: 'name', title: __('Name'),operate:false},
                        {field: 'department', title: __('Department')},
                        {field: 'sign_time', title: __('Sign_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'a_title', title: __('A_id'),operate:false},
                        {field: 'a_id', title: __('A_id'),visible: false,addClass: "selectpage", extend: "data-source='sign/activity/index' data-field='name' data-primary-key='id'"},
                        {field: 'ding_userid', title: __('Ding_userid'),operate:false},
                        {field: 'ding_userid', title: "用户",visible: false, addclass:'selectpage', extend: "data-source='dinguser/index' data-field='name' data-primary-key='userId'"},
                        {field: 'sign_start', title: __('Sign_start'),operate: false},
                        {field: 'sign_end', title: __('Sign_end'),operate: false},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'type_title', title: __('Type'),operate: false},
                        {field: 'type', title: __('Type'),visible: false, searchList: $.getJSON("sign/user/typeList")},
                        {field: 'score', title: __('Score'),operate: false},
                        //弹窗失效，在加一个又恢复了
                        {field: 'operate', title: __('Operate'),visible: false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
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