<?php
/**
 * 麦迅标准模块 模块小程序接口定义
 *
 * @author cokeyang
 * @url
 */
defined('IN_IA') or exit('Access Denied');

class Maixun_weddingModuleWxapp extends WeModuleWxapp
{
    //public $token = 'maixun_demo_token'; //接口通信token

    public function doPageIndex()
    {
        global $_GPC, $_W;
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=wx3831172bbb8d3427&secret=11d9ba1c8baadb7425655ef6da7cc0a3&js_code=" . $_GPC['code'] . "&grant_type=authorization_code";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $back = curl_exec($curl);
        if ($back === false) {
            $openid = 0;
            echo $openid;
        } else {
            $s = json_decode($back);
            $openid = $s->openid;
        }
        curl_close($curl);
        if (empty($openid)) {
            $this->my_message(202, [], 'openid错误');
        }
        $user = pdo_fetch("SELECT * FROM " . tablename('maixun_wedding_user') . "  WHERE  openid= :openid ", [":openid" => $openid]);
        if (!$user) {
            $inset_userdata = array(
                'openid' => $openid,
            );
            pdo_insert('maixun_wedding_user', $inset_userdata);
        }

        $info = pdo_fetch("SELECT u.*,we.wd_date FROM " . tablename('maixun_wedding_user') . " u 
        LEFT JOIN " . tablename('maixun_wedding') . " we ON u.wid = we.id 
        WHERE  u.openid= :openid ", [":openid" => $openid]);

        if (!$info) {
            $this->my_message(201, array('openid' => $openid), '没有婚礼数据');
        }

        $info['cb_num'] = count(pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_case') . " WHERE  wid= :wid and status = 1", [":wid" => $info['wid']]));
        $info['kx_num']  = count(pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_item') . " WHERE  wid= :wid and status = 1", [":wid" => $info['wid']]));
        $info['wd_date'] = date('Y-m-d', $info['wd_date']);

        $data = array(
            'openid' => $openid,
            'info' => $info
        );
        $this->my_message(200, $data, 'success');
    }


    /**
     * 查询婚礼创建人
     * */
    public function doPageselectmanager()
    {
        global $_GPC, $_W;
        $wid = $_GPC['wid'];
        $name = pdo_fetch("SELECT u.name FROM " . tablename('maixun_wedding_user') . " u 
        LEFT JOIN " . tablename('maixun_wedding') . " we ON u.wid = we.id 
        WHERE  u.wid= :wid and u.is_manager= 1", [":wid" => $wid])['name'];
        $this->my_message(200, $name, 'success');
    }

    /**
     * 查询婚礼
     * */
    public function doPageselectwedding()
    {
        global $_GPC, $_W;
        $wid = $_GPC['wid'];
        $wedding_data = pdo_fetch("SELECT * FROM " . tablename('maixun_wedding') . " WHERE  id= :id ", [":id" => $wid]);
        $wedding_data['wd_date'] = date('Y-m-d', $wedding_data['wd_date']);
        $this->my_message(200, $wedding_data, 'success');
    }


    /**
     * 查询任务
     * */
    public function doPageselecttask()
    {

        global $_GPC, $_W;
        $openid = $_GPC['openid'];
        $wid = $_GPC['wid'];
        $type_id = $_GPC['type_id'];

        if (empty($openid)) {
            $this->my_message(202, [], 'openid错误');
        }
        if (empty($wid)) {
            $this->my_message(202, [], '婚礼id错误');
        }
        if (!$type_id && $type_id != 0) {
            $this->my_message(202, [], '类型获取失败');
        }

        //查询筹备事项
        if ($type_id == 0) {
            $wedding_data = pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_case') . " WHERE  wid= :wid and status = 0 ORDER BY case_date DESC", [":wid" => $wid]);
            foreach ($wedding_data as $k => $item) {
                $days = $this->diffBetweenTwoDays(date('Y-m-d'), $item['case_date']);
                $wedding_data[$k]['num_date'] = $days;
            }
            $this->my_message(200, $wedding_data, 'success');
        }
        //查询开销记录
        if ($type_id == 1) {
            $weeklist = ['星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
            date("N",time());//星期
            $money_list = pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_item') . " WHERE  wid= :wid and status = 1 ORDER BY item_date DESC", [":wid" => $wid]);
            $res_data = array();
            $money_data = $money_list;
            foreach ($money_data as $k => $item) {
                $money_data[$k]['day'] =  date("Y-m-d",$item['item_date']);
                $money_data[$k]['week']  = $weeklist[date("N",$item['item_date'])-1];
            }
            foreach ($money_data as $v => $vo) {
                    if ($v>0){
                        if ($vo['day'] == $money_data[$v-1]['day']){
                            $res_data[$v-1]['expend'] =  $res_data[$v-1]['expend']+$vo['price'];
                            array_push( $res_data[$v-1]['expdata'],$money_list[$v]);
                        }else{
                            $res_data[$v]['day'] = $vo['day'];
                            $res_data[$v]['week'] = $vo['week'];
                            $res_data[$v]['expend'] = $vo['price'];
                            $res_data[$v]['expdata'][] = $money_list[$v];
                        }

                    }else{
                        $res_data[$v]['day'] = $vo['day'];
                        $res_data[$v]['week'] = $vo['week'];
                        $res_data[$v]['expend'] = $vo['price'];
                        $res_data[$v]['expdata'][] = $money_list[$v];
                    }

            }
            $this->my_message(200, $res_data, 'success');
        }
        //查询电子请柬
        if ($type_id == 2) {

        }

        //查询筹备事项完成记录
        if ($type_id == 3) {
            $wedding_data = pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_case') . " WHERE  wid= :wid and status = 1", [":wid" => $wid]);
            foreach ($wedding_data as $k => $item) {
                $days = $this->diffBetweenTwoDays(date('Y-m-d'), $item['case_date']);
                $wedding_data[$k]['num_date'] = $days;
            }
            $this->my_message(200, $wedding_data, 'success');
        }

        //查询开销完成记录
        if ($type_id == 4) {


        }

        //查询电子请柬完成记录
        if ($type_id == 5) {

        }

    }

    /**
     * 筹备完成
     * */
    public function doPagecompletecase()
    {
        global $_GPC, $_W;
        $wid = $_GPC['wid'];
        $openid = $_GPC['openid'];
        $com_id = $_GPC['com_id'];
        $remarks = $_GPC['remarks'];
        $caseid = $_GPC['caseid'];
        if (empty($openid)) {
            $this->my_message(202, [], 'openid错误');
        }
        if (empty($com_id)) {
            $this->my_message(202, [], '提交类型错误');
        }
        if (empty($caseid)) {
            $this->my_message(202, [], '筹备ID错误');
        }
        $up_data = array(
            'status' => $com_id,
            'openid' => $openid,
            'remarks' => $remarks,

        );
        $resx = pdo_update('maixun_wedding_wedding_case', $up_data, array('id' => $caseid));
        if ($resx) {
            $this->my_message(200, '', 'success');
        } else {
            $this->my_message(203, '', '完成失败');
        }

    }

    /**
     * 添加筹备
     * */
    public function doPageaddcase()
    {
        global $_GPC,$_W;
        $wid = $_GPC['wid'];
        $openid = $_GPC['openid'];
        $startDate = $_GPC['startDate'];
        $content = $_GPC['content'];
        $title = $_GPC['title'];

        if (empty($wid)) {
            $this->my_message(202, [], '婚礼id错误');
        }
        if (empty($startDate)) {
            $this->my_message(202, [], '时间错误');
        }
        if (empty($title)) {
            $this->my_message(202, [], '名称错误');
        }
        $inset_data = array(
            'wid' => $wid,
            'title' => $title,
            'status' => 0,
            'case_date' =>$startDate,
            'content' =>$content,
        );
        $resx = pdo_insert('maixun_wedding_wedding_case', $inset_data);
        if ($resx) {
            $this->my_message(200, '', 'success');
        } else {
            $this->my_message(203, '', '添加失败');
        }

    }
    /**
     * 添加开销
     * */
    public function doPageadditem()
    {
        global $_GPC,$_W;
        $wid = $_GPC['wid'];
        $openid = $_GPC['openid'];
        $money = $_GPC['money'];
        $title = $_GPC['title'];
        $icon = $_GPC['icon'];
        $id = $_GPC['id'];

        if (empty($wid)) {
            $this->my_message(202, [], '婚礼id错误');
        }
        if (empty($money)) {
            $this->my_message(202, [], '开销设置错误');
        }
        if (empty($title)) {
            $this->my_message(202, [], '标题错误');
        }

        $inset_data = array(
            'wid' => $wid,
            'title' => $title,
            'icon' => $icon,
            'price' =>$money,
            'openid' =>$openid,
            'stid' =>$id==-1?'':$id,
            'item_date' =>time(),
            'status' =>1,
        );
        $resx = pdo_insert('maixun_wedding_wedding_item', $inset_data);
        if ($resx) {
            $this->my_message(200, '', 'success');
        } else {
            $this->my_message(203, '', '添加失败');
        }

    }

    /**
     * 添加婚礼
     * */
    public function doPageAddwedding()
    {
        global $_GPC, $_W;
        $wid = $_GPC['wid'];
        $openid = $_GPC['openid'];
        $bride = $_GPC['bride'];
        $bridephone = $_GPC['bridephone'];
        $groom = $_GPC['groom'];
        $groomphone = $_GPC['groomphone'];
        $weddate = $_GPC['weddate'];
        $role_type = $_GPC['role_type'];

        if (empty($openid)) {
            $this->my_message(202, [], 'openid错误');
        }
        if (empty($bride)) {
            $this->my_message(202, [], '新娘填写错误');
        }
        if (empty($bridephone)) {
            $this->my_message(202, [], '新娘电话填写错误');
        } else if (!preg_match("/^1[34578]{1}\d{9}$/", $bridephone)) {
            $this->my_message(202, [], '请正确填写电话');
        }
        if (empty($groom)) {
            $this->my_message(202, [], '新郎填写错误');
        }
        if (empty($groomphone)) {
            $this->my_message(202, [], '新郎电话填写错误');
        } else if (!preg_match("/^1[34578]{1}\d{9}$/", $groomphone)) {
            $this->my_message(202, [], '请正确填写电话');
        }
        if (empty($weddate)) {
            $this->my_message(202, [], '婚礼时间填写错误');
        }

        if (!$wid) {
            $inset_data = array(
                'wd_date' => strtotime($weddate),
                'bride' => $bride,
                'groom' => $groom,
                'bridephone' => $bridephone,
                'groomphone' => $groomphone,
            );
            $result = pdo_insert('maixun_wedding', $inset_data);
            if (!empty($result)) {
                $w_id = pdo_insertid();
                $updata_userdata = array(
                    'name' => $role_type == 1 ? $groom : $bride,
                    'phone' => $role_type == 1 ? $groomphone : $bridephone,
                    'role' => $role_type == 1 ? '新郎' : '新娘',
                    'is_manager' => 1,
                    'wid' => $w_id
                );
                $resx = pdo_update('maixun_wedding_user', $updata_userdata, array('openid' => $openid));
                if (!empty($resx)) {
                    $case_data = pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_system_case'));
                    foreach ($case_data as $case) {
                        $for_data = array(
                            'wid' => $w_id,
                            'tid' => $case['id'],
                            'title' => $case['title'],
                            'status' => 0,
                            'case_date' => date("Y-m-d", strtotime($weddate . "-" . $case['case_date'] . "day")),
                        );

                        pdo_insert('maixun_wedding_wedding_case', $for_data);
                    }
                    $this->my_message(200, [], '婚礼添加成功');
                }
            }
        } else {
            $old_wday = pdo_fetch("SELECT wd_date FROM " . tablename('maixun_wedding'), array('wid' => $wid))['wd_date'];
            $days = $this->diffBetweenTwoDays(date('Y-m-d', $old_wday), $weddate);
            $updata_data = array(
                'wd_date' => strtotime($weddate),
                'bride' => $bride,
                'groom' => $groom,
                'bridephone' => $bridephone,
                'groomphone' => $groomphone,
            );
            pdo_update('maixun_wedding', $updata_data, array('id' => $wid));
            if ($days != 0) {
                $case_data = pdo_fetchall("SELECT * FROM " . tablename('maixun_wedding_wedding_case'), array('wid' => $wid));
                foreach ($case_data as $case) {
                    $for_data = array(
                        'case_date' => date("Y-m-d", strtotime($case['case_date'] . " " . $days . "day")),
                    );
                    pdo_update('maixun_wedding_wedding_case', $for_data, array('tid' => $case['id'], 'wid' => $wid));
                }
                $this->my_message(200, [], '婚礼时间更新成功');
            }
        }
    }

    //计算两个时间相差天数
    function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        return ($second1 - $second2) / 86400;
    }

    //公共返回方法
    public static function my_message($code, $data, $msg)
    {
        //code 200 正常返回  201未查到数据 202参数错误 203运行错误 204 其他错误
        $obj = new \stdClass();
        $obj->code = $code;
        $obj->data = $data;
        $obj->msg = $msg;
        echo json_encode($obj);
        die();
    }
}