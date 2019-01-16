define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'prize/year/index',
                    add_url: 'prize/year/add',
                    edit_url: 'prize/year/edit',
                    del_url: 'prize/year/del',
                    multi_url: 'prize/year/multi',
                    table: 'prize_year',
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
                        {field: 'title', title: __('Title')},
                        {field: 'year', title: __('Year')},
                        {field: 'lv', title: __('Lv')},
                        {field: 'head_img', title: __('Head_img'),operate: false,formatter: Table.api.formatter.image},
                        {field: 'start_time', title: __('Start_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),operate: false,yes: '1', no: '0',formatter:Table.api.formatter.toggle},
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
                                    url: 'prize/year/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
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