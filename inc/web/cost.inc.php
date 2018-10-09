<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/8
 * Time: 14:43
 */
global $_GPC, $_W;
//获取模块设置参数
$this->module['config'];

$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

if ($operation == 'display') {
    if ($_W['ispost']){
        var_dump('321');

    }

    //获取页数
    $pindex = max(1, intval($_GPC['page']));
    //获取页行数
    $psize = 5;
    //使用拼接sql语句
    $sql = 'SELECT * FROM ' . tablename('maixun_wedding_system_item') . 'ORDER BY sort DESC';
    $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
    $itemlist = pdo_fetchall($sql);
    $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('maixun_wedding_system_item'));
//（前台用pager标签可以直接显示）
    $pager = pagination($total, $pindex, $psize);
    include $this->template('cost');//vaccine
} else if ($operation == 'edit') {
    $id = $_GPC['id'];
    if (empty($id)){
        message('id错误', '', 'error');
    }
    if($_W['ispost']){
        $title = $_GPC['title'];
        $icon = $_GPC['icon'];
        $sort = $_GPC['sort'];
        if (empty($title)){
            message('标题为空', '', 'error');
        }
        if (empty($icon)){
            message('图标为空', '', 'error');
        }
        $updata_data = array(
            'title' =>$title,
            'icon' =>$icon,
            'sort' =>$sort,
        );
        $result = pdo_update('maixun_wedding_system_item', $updata_data, array('id' => $id));
        if ($result){
            message('修改成功', referer(), 'success');
        }else{
            message('修改失败', '', 'error');
        }
    }
    $info = pdo_fetch("SELECT * FROM " . tablename('maixun_wedding_system_item'). " WHERE id=:id", array(':id' => $id));
    include $this->template('addcost');//vaccine
} else if ($operation == 'add') {
    if($_W['ispost']){
        $title = $_GPC['title'];
        $icon = $_GPC['icon'];
        $sort = $_GPC['sort'];
        if (empty($title)){
            message('标题为空', '', 'error');
        }
        if (empty($icon)){
            message('图标为空', '', 'error');
        }
        $insert_data = array(
            'title' =>$title,
            'icon' =>$icon,
            'sort' =>$sort,
        );
        $result = pdo_insert('maixun_wedding_system_item', $insert_data);
        if ($result){
            message('添加成功', referer(), 'success');
        }else{
            message('添加失败', '', 'error');
        }
    }

    include $this->template('addcost');//vaccine
}