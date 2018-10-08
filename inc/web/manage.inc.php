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
    $sql = 'SELECT * FROM ' . tablename('maixun_wedding_user');
    $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
    $userlist = pdo_fetchall($sql);
    $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('maixun_wedding_user'));
//（前台用pager标签可以直接显示）
    $pager = pagination($total, $pindex, $psize);
    include $this->template('manage');//vaccine
} elseif ($operation == 'post') {

}