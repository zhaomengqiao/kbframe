@extends('backend.layouts.base')

@section('content')
    <div class="layui-fluid">
        <div class="layui-card">
            <div class="layui-form layui-card-header layuiadmin-card-header-auto">
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">标题</label>
                        <div class="layui-input-block">
                            <input type="text" id="title" placeholder="请输入" autocomplete="off" class="layui-input">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <button id="search" class="layui-btn layuiadmin-btn-useradmin" lay-submit lay-filter="LAY-user-front-search">
                            <i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="layui-card-body">
                <div style="padding-bottom: 10px;">
                    @allow('backend.announce.create')
                        <button class="layui-btn" data-type="add">添加</button>
                    @endallow
                    @allow('backend.announce.destroy')
                        <button class="layui-btn layui-btn-danger" data-type="delAll">删除</button>
                    @endallow
                </div>

                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
    <script type="text/html" id="barDemo">
        @allow('backend.announce.edit')
            <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
        @endallow
        @allow('backend.announce.destroy')
            <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
        @endallow
    </script>
@endsection

@section('script')
    <script>
        layui.config({
            base: '/static/layui/' //静态资源所在路径
        }).use(['table'], function(){
            var layer = layui.layer;
            var form = layui.form;
            var table = layui.table;

            var title = $('#title').val();
            layer.load(1);
            //用户表格初始化
            var dataTable = table.render({
                elem: '#dataTable'
                ,url: "{{ route('backend.announce.list') }}" //数据接口
                ,method: 'get'
                ,where: {
                    'title': title,
                }
                ,page: true //开启分页
                ,limit: 15
                ,limits: [15,30,50,100]
                ,cols: [[ //表头
                    {checkbox: true,fixed: true}
                    ,{field: 'id', title: 'ID', sort: true,width:80}
                    ,{field: 'title', title: '标题'}
                    ,{field: 'sender_id', title: '来源', templet:function (d) {
                        return d.sender ? d.sender.username : '';
                    }}
                    ,{field: 'created_at', title: '创建时间'}
                    ,{field: 'updated_at', title: '更新时间'}
                    ,{fixed: 'right', title:'操作', toolbar: '#barDemo', width:150}
                ]]
                , done: function(){
                    layer.closeAll('loading');
                }
            });

            //监听工具条
            table.on('tool(dataTable)', function(obj){ //注：tool是工具条事件名，dataTable是table原始容器的属性 lay-filter="对应的值"
                var data = obj.data //获得当前行数据
                    ,layEvent = obj.event; //获得 lay-event 对应的值
                if(layEvent === 'del'){
                    layer.confirm('确定删除选中的公告？', {icon: 3, title: '提示信息'}, function(index) {
                        var dataId = [];
                        dataId.push(data.id);
                        $.ajax({
                            url: '/backend/announce/destroy',
                            type: "DELETE",
                            data: {ids:dataId},
                            dataType:"json",
                            beforeSend: function() {
                                layer.load(1);
                            },
                            success:function(res){
                                layer.closeAll('loading');
                                if (res.code != 0) {
                                    layer.msg('错误:'+res.msg, {icon: 2});
                                } else {
                                    layer.close(index); //关闭弹层
                                    dataTable.reload(); //数据刷新
                                    layer.msg('成功', {icon: 1,time: 1000});
                                }
                            },
                            error:function(data){
                                layer.closeAll('loading');
                                layer.msg('服务器网络错误', {icon: 2});
                            }
                        });
                    });
                } else if(layEvent === 'edit'){
                    layer.open({
                        type: 2,
                        title: '修改公告',
                        content: '/backend/announce/'+data.id+'/edit',
                        maxmin: true,
                        area: ['1200px', '708px'],
                        btn: ['确定', '取消'],
                        yes: function(index, layero){
                            var iframeWindow = window['layui-layer-iframe'+ index],
                                submitID = 'LAY-user-backend-submit',
                                submit = layero.find('iframe').contents().find('#'+ submitID);

                            {{--//监听提交--}}
                            iframeWindow.layui.form.on('submit('+ submitID +')', function(data){
                                var field = data.field; //获取提交的字段
                                //提交 Ajax 成功后，静态更新表格中的数据
                                $.ajax({
                                    url: '/backend/announce/'+field.id+'/update',
                                    data: $(layero).find("iframe").contents().find("#layui-layer").serialize(),
                                    type: "PUT",
                                    dataType:"json",
                                    beforeSend: function() {
                                        layer.load(1);
                                    },
                                    success:function(res){
                                        layer.closeAll('loading');
                                        if (res.code != 0) {
                                            layer.msg('错误:'+res.msg, {icon: 2});
                                        } else {
                                            layer.close(index); //关闭弹层
                                            dataTable.reload(); //数据刷新
                                            layer.msg('成功', {icon: 1,time: 1000});
                                        }
                                    },
                                    error:function(data){
                                        layer.closeAll('loading');
                                        layer.msg('服务器网络错误', {icon: 2});
                                    }
                                });
                            });
                            submit.trigger('click');
                        }
                    });
                }
            });

            // 执行搜索，表格重载
            $('#search').on('click', function () {
                // 搜索条件
                var title = $('#title').val();
                table.reload('backendUser', {
                    method: 'get'
                    ,where: {
                        'title': title,
                    }
                });
            });

            //事件
            var active = {
                add: function(){
                    layer.open({
                        type: 2,
                        title: '添加公告',
                        content: '/backend/announce/create',
                        maxmin: true,
                        area: ['1200px', '708px'],
                        btn: ['确定', '取消'],
                        yes: function(index, layero){
                            var iframeWindow = window['layui-layer-iframe'+ index],
                                submitID = 'LAY-user-backend-submit',
                                submit = layero.find('iframe').contents().find('#'+ submitID);

                            {{--//监听提交--}}
                            iframeWindow.layui.form.on('submit('+ submitID +')', function(data){
                                var field = data.field; //获取提交的字段
                                //提交 Ajax 成功后，静态更新表格中的数据
                                $.ajax({
                                    url: '/backend/announce/0/update',
                                    data: $(layero).find("iframe").contents().find("#layui-layer").serialize(),
                                    type: "PUT",
                                    dataType:"json",
                                    beforeSend: function() {
                                        layer.load(1);
                                    },
                                    success:function(res){
                                        layer.closeAll('loading');
                                        if (res.code != 0) {
                                            layer.msg('错误:'+res.msg, {icon: 2});
                                        } else {
                                            layer.close(index); //关闭弹层
                                            dataTable.reload(); //数据刷新
                                            layer.msg('成功', {icon: 1,time: 1000});
                                        }
                                    },
                                    error:function(data){
                                        layer.closeAll('loading');
                                        layer.msg('服务器网络错误', {icon: 2});
                                    }
                                });
                            });
                            submit.trigger('click');
                        }
                    });
                },
                delAll: function () {
                    var checkList = table.checkStatus('dataTable');
                        data = checkList.data,
                        dataId = [];
                    if(data.length > 0) {
                        for (var i in data) {
                            dataId.push(data[i].id);
                        }
                        layer.confirm('确定删除选中的公告？', {icon: 3, title: '提示信息'}, function (index) {
                            $.ajax({
                                url: '/backend/announce/destory',
                                type: "DELETE",
                                data: {ids:dataId},
                                dataType:"json",
                                beforeSend: function() {
                                    layer.load(1);
                                },
                                success:function(res){
                                    layer.closeAll('loading');
                                    if (res.code != 0) {
                                        layer.msg('错误:'+res.msg, {icon: 2});
                                    } else {
                                        layer.close(index); //关闭弹层
                                        dataTable.reload(); //数据刷新
                                        layer.msg('成功', {icon: 1,time: 1000});
                                    }
                                },
                                error:function(data){
                                    layer.closeAll('loading');
                                    layer.msg('服务器网络错误', {icon: 2});
                                }
                            });
                        })
                    }else{
                        layer.msg("请选择需要删除的公告");
                    }
                }
            };

            $('.layui-btn').on('click', function(){
                var type = $(this).data('type');
                active[type] && active[type].call(this);
            });
        })
    </script>
@endsection



