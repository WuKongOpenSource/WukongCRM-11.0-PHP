<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-产品分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Db;
use think\Hook;
use think\Request;
use app\bi\logic\ExcelLogic;

class Product extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     **/
    public function _initialize()
    {
        $action = [
            'permission' => [''],
            'allow' => ['statistics', 'productcategory', 'excelexport']
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'product', 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code' => 102, 'error' => '无权操作']));
        }
    }

    /**
     * 产品销量统计
     *
     * @param string $param
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statistics($param = '')
    {
        $productModel = new \app\crm\model\Product();
        if($param['excel_type']!=1){
            $param = $this->param;
        }

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $list = $productModel->getStatistics($param);

        //导出使用
        if (!empty($param['excel_type'])) {
            return $list;
        }
        return resultArray(['data' => $list]);
    }

    /**
     * 产品分类销量分析
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productCategory()
    {
        $param = $this->param;

        $productModel = new \app\bi\model\Product();

        if (!empty($param['start_time'])) $param['start_time'] = strtotime($param['start_time'] . ' 00:00:00');
        if (!empty($param['end_time']))   $param['end_time']   = strtotime($param['end_time'] . ' 23:59:59');

        $list = $productModel->getStatistics($param);

        return resultArray(['data' => $list]);
    }

    /**
     * 导出
     *
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excelExport()
    {
        $param = $this->param;
        $list = $this->statistics($param);
        $data = [];
        $subtotalCount = [];
        $sumCount = [];
        $item = [];
        $unm = 0;
        $subtotal = 0;
        $res = [];
        foreach ($list as $val) {
            $res[] = $val['product_id'];
            $data[$val['product_id']][] = $val;
        }
        $res = array_unique($res);
        foreach ($res as $e) {
            foreach ($list as $v) {
                if ($e == $v['product_id']) {
                    $unm += $v['num'];
                    $subtotal += $v['subtotal'];
                    $sumCount[$e] = $unm + $v['num'];
                    $subtotalCount[$e] = (float)$subtotal + $v['subtotal'];
                }
            }
            $item[$e][] = [
                'type' => '',
                'category_id_info' => '',
                'product_name' => '',
                'contract_name' => '',
                'realname' => '',
                'name' => '',
                'price' => '合计',
                'num' => $sumCount[$e],
                'subtotal' => $subtotalCount[$e],
            ];
        }
        $items = [];
        foreach ($data as $key => $value) {
            $items[] = array_merge($data[$key], $item[$key]);
        }
        foreach ($items as $u) {
            foreach ($u as $d) {
                $field[] = $d;
            }
        }
        $excelLogic = new ExcelLogic();
        $data = $excelLogic->productExcel($param, $field);
        return $data;
    }
}
