<?php
/**
 * 麦迅标准模块 模块微站定义
 *
 * @author cokeyang
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Maixun_weddingModuleSite extends WeModuleSite {

	public function __construct() {
		global $_W;
	}

	private function _checkSettings(){
		global $_W;
		if (empty($this->module['config'])) {
		  message('抱歉，系统参数没有填写，请先填写系统参数！', url('profile/module/setting', array(
		      'm' => 'maixun_demo'
		  )), 'error');
		}
	}
	public function doMobileMfuncover() {
		//这个操作被定义用来呈现 功能封面
	}
	public function doWebRule() {
		//这个操作被定义用来呈现 规则列表
	}
	public function doWebCategory() {
		global $_GPC, $_W;
		//检查是否设置必要参数
		$this->_checkSettings();
		//获取模块设置参数
		$this->module['config'];
		
		$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($operation == 'display') {
			if (!empty($_GPC['displayorder'])) {
				foreach ($_GPC['displayorder'] as $id => $displayorder) {
					pdo_update('maixun_demo_category', array('displayorder' => $displayorder), array('id' => $id, 'uniacid' => $_W['uniacid']));
				}
				message('分类排序更新成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
			}
			$children = array();
			$category = pdo_fetchall("SELECT * FROM " . tablename('maixun_demo_category') . " WHERE uniacid = '{$_W['uniacid']}' ORDER BY parentid ASC, displayorder DESC");
			foreach ($category as $index => $row) {
				if (!empty($row['parentid'])) {
					$children[$row['parentid']][] = $row;
					unset($category[$index]);
				}
			}
			include $this->template('category');
		} elseif ($operation == 'post') {
			$parentid = intval($_GPC['parentid']);
			$id = intval($_GPC['id']);
			if (!empty($id)) {
				$category = pdo_fetch("SELECT * FROM " . tablename('maixun_demo_category') . " WHERE id = :id AND uniacid = :uniacid", array(':id' => $id, ':uniacid' => $_W['uniacid']));
			} else {
				$category = array(
					'displayorder' => 0,
				);
			}
			if (!empty($parentid)) {
				$parent = pdo_fetch("SELECT id, name FROM " . tablename('maixun_demo_category') . " WHERE id = '$parentid'");
				if (empty($parent)) {
					message('抱歉，上级分类不存在或是已经被删除！', $this->createWebUrl('post'), 'error');
				}
			}
			if (checksubmit('submit')) {
				if (empty($_GPC['catename'])) {
					message('抱歉，请输入分类名称！');
				}
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $_GPC['catename'],
					'enabled' => intval($_GPC['enabled']),
					'displayorder' => intval($_GPC['displayorder']),
					'isrecommand' => intval($_GPC['isrecommand']),
					'description' => $_GPC['description'],
					'parentid' => intval($parentid),
					'thumb' => $_GPC['thumb']
				);
				if (!empty($id)) {
					unset($data['parentid']);
					pdo_update('maixun_demo_category', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
					load()->func('file');
					file_delete($_GPC['thumb_old']);
				} else {
					pdo_insert('maixun_demo_category', $data);
					$id = pdo_insertid();
				}
				message('更新分类成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
			}
			include $this->template('category');
		} elseif ($operation == 'delete') {
			$id = intval($_GPC['id']);
			$category = pdo_fetch("SELECT id, parentid FROM " . tablename('maixun_demo_category') . " WHERE id = '$id'");
			if (empty($category)) {
				message('抱歉，分类不存在或是已经被删除！', $this->createWebUrl('category', array('op' => 'display')), 'error');
			}
			pdo_delete('maixun_demo_category', array('id' => $id, 'parentid' => $id), 'OR');
			message('分类删除成功！', $this->createWebUrl('category', array('op' => 'display')), 'success');
		}
	}
	public function doMobileHomenav() {
		//这个操作被定义用来呈现 微站首页导航图标
	}
	public function doMobilePernav() {
		//这个操作被定义用来呈现 微站个人中心导航
	}
	public function doMobileQuicknav() {
		//这个操作被定义用来呈现 微站快捷功能导航
	}
	public function doWebInfun() {
		//这个操作被定义用来呈现 微站独立功能
	}

}