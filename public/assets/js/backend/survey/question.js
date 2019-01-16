define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'survey/question/index',
                    add_url: 'survey/question/add',
                    edit_url: 'survey/question/edit',
                    del_url: 'survey/question/del',
                    multi_url: 'survey/question/multi',
                    detail_url:'survey/question/detail',
                    table: 'question',
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
                        {field: 'title', title: __('Title'),oprate:'like'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'survey_title', title: __('Survey_title'),operate: false},
                        {field: 'survey_id', title: __('Survey_id'),visible: false,addClass: "selectpage", extend: "data-source='survey/survey/index' data-field='title' "},
                        {field: 'image', title: __('Image'), operate: false, formatter: Table.api.formatter.image},
                        {field: 'type_text', title: __('Type'),operate: false},
                        {field: 'listorder', title: __('Listorder'),operate:false},
                        {field: 'describe', title: __('Describe'),operate: false},
                        {field: 'required', title: __('Required'),operate: false,yes: '1', no: '0',formatter:Table.api.formatter.toggle},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {
                            field: 'buttons',
                            width: "120px",
                            title: __('统计'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('统计'),
                                    title: __('统计'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'survey/question/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.buttons
                        }
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
        detail: function () {
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