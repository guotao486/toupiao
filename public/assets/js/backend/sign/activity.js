define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sign/activity/index',
                    add_url: 'sign/activity/add',
                    edit_url: 'sign/activity/edit',
                    del_url: 'sign/activity/del',
                    multi_url: 'sign/activity/multi',
                    table: 'sign_activity',
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
                        {field: 'listorder', title: __('Listorder'),operate:false},
                        {field: 'name', title: __('Name')},
                        {field: 'create_time', title: __('Create_time'),visible: false, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'content', title: __('Content'),operate: false},
                        {field: 'time', title: __('Time')},
                        {field: 'address', title: __('Address')},
                        {field: 'users', title: __('Users')},
                        {field: 'lecture_name', title: __('Lecture'),operate: false},
                        {field: 'lecture', title: __('lecture'),visible: false,operate:'LIKE',addClass: "selectpage", extend: "data-source='dinguser/index' data-field='name' data-primary-key='userId'"},
                        {field: 'host_name', title: __('Host'),operate: false},
                        {field: 'host', title: __('Host'),visible: false,operate:'LIKE',addClass: "selectpage", extend: "data-source='dinguser/index' data-field='name' data-primary-key='userId'"},
                        {field: 'sponsor_name', title: __('Sponsor'),operate: false},
                        {field: 'sponsor', title: __('Sponsor'),visible: false,operate:'LIKE',addClass: "selectpage", extend: "data-source='dinguser/index' data-field='name' data-primary-key='userId'"},
                        {field: 'update_time', title: __('Update_time'),visible: false,operate: false, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'lecture_score', title: __('Lecture_score')},
                        {field: 'host_score', title: __('Host_score')},
                        {field: 'sponsor_score', title: __('Sponsor_score')},
                        {field: 'score', title: __('Score')},
                        {field: 'status', title: __('Status')},
                        {field: 'sign_start_time', title: __('Sign_start_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'sign_end_time', title: __('Sign_end_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'),operate: false,visible: false},

                        {field: 'upload_num', title: __('Upload_num'),operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {
                            field: 'buttons',
                            width: "120px",
                            title: __('附件'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'fileList',
                                    text: '查看附件',
                                    title: __('统计'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-cloud-upload',
                                    url: 'sign/activity/fileList',
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
                        },
                        {
                        field: 'buttons',
                        width: "120px",
                        title: __('生成签到'),
                        table: table,
                        events: Table.api.events.operate,
                        buttons: [
                            {
                                name: 'signQrcode',
                                text: '生成签到',
                                title: __('签到二维码'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                icon: 'fa fa-qrcode',
                                url: 'sign/activity/signQrcode',
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
                        },
                        {
                            field: 'buttons',
                            width: "120px",
                            title: __('签到表格'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'signQrcode',
                                    text: '导出表格',
                                    title: __('导出表格'),
                                    classname: 'btn btn-xs btn-primary ',
                                    icon: 'fa fa-file-excel-o',
                                    url: 'sign/activity/signExcel',
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
        filelist: function () {
            require(['upload'], function(Upload){
                Upload.api.plupload($(".plupload"), function(data, ret){

                    if(ret.code == 1){
                        window.location.reload();
                        Toastr.success("成功");
                    }else{
                        Toastr.error("失败");
                    }

                }, function(data, ret){
                    Toastr.success("失败");
                });
            });

            $(document).on("click", ".btn-file-del", function () {
                var url = $(this).attr('data-url');
                var index = Layer.load(1);
                $.ajax(
                    {
                        type: "post",
                        url:url,
                        success:function(res){
                            if(res.code == 0){
                                Toastr.error(res.msg);
                            }else if(res.code == 1){
                                window.location.reload();
                                Toastr.success(res.msg);
                            }
                            Layer.close(index);
                        }
                    }
                );
            });

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});