define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dinguser/index',
                    add_url: 'dinguser/add',
                    edit_url: 'dinguser/edit',
                    del_url: 'dinguser/del',
                    multi_url: 'dinguser/multi',
                    table: 'ding_user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'userId',
                sortName: 'userId',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'userId', title: __('Userid')},
                        {field: 'nick', title: __('Nick')},
                        {field: 'name', title: __('Name'),operate:'like'},
                        {field: 'mobile', title: __('Mobile'),operate:'like'},
                        {field: 'avatar', title: __('Avatar'),operate: false,formatter: Table.api.formatter.image},
                        {field: 'departmentId', title: __('Departmentid'),operate: false,visible: false},
                        {field: 'department', title: __('Department')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on("click", ".btn-update", function () {
                var ids = Table.api.selectedids(table);
                var index = Layer.load(1);
                $.ajax(
                    {
                        type: "post",
                        url:"dinguser/update",
                        data: {ids:ids},
                        dataType:'json',
                        success:function(res){
                            console.log(res);
                            if(res.code == 0){
                                Layer.msg(res.msg,{icon:5});
                            }else if(res.code == 1){
                                Layer.msg(res.msg,{icon:6});
                            }
                            Layer.close(index);
                        },
                        error:function(XMLHttpRequest, textStatus, errorThrown){
                            // console.log(XMLHttpRequest.responseText);
                            Layer.close(index);
                            Layer.msg('error',{icon:5});

                        }
                    }
                );
            });
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