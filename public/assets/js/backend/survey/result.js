define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'survey/result/index',
                    add_url: '',
                    edit_url: 'survey/result/edit',
                    del_url: 'survey/result/del',
                    multi_url: 'survey/result/multi',
                    table: 'survey_result',
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
                        {field: 'user_name', title: __('User_name')},
                        {field: 'user_avatar', title: __('User_avatar'),operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'option_id', title: __('Option_id'),operate: false},
                        {field: 'option_text', title: __('Option_text'),operate: false},
                        {field: 'survey_title', title: __('Survey_title'),operate: false},
                        {field: 'survey_id', title: __('Survey_id'),visible: false,addClass: "selectpage", extend: "data-source='survey/survey/index' data-field='title' "},
                        {field: 'question_title', title: __('Question_title'),operate: false},
                        {field: 'question_id', title: __('Question_id'),visible: false,addClass: "selectpage", extend: "data-source='survey/question/index' data-field='title' "},
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