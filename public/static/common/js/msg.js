//提示窗口
function Msg(msg, icon) {
    layui.use('layer', function () {
        var layer = layui.layer
        layer.msg(msg, {icon: icon, anim: 6})
    })
}