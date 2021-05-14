<?php
namespace app\crm\logic;

use think\Db;
use think\Validate;
use app\crm\model\Market;

class MarketLogic{
    /**
     *
     * @param $param
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/27 0027 09:46
     */
    public function getDataList($param){
        $userModel = new \app\admin\model\User();
        if($param['search']) {
            $where['market_field_id'] = $param['search'];
        }
            //权限
            $a = 'index';
            $auth_user_ids = $userModel->getUserByPer('crm', 'market', $a);
            $authMap['market.create_user_id'] = ['in', $auth_user_ids];
        $list=db('crm_market')
            ->alias('market')
            ->join('__CRM_MARKET_CATEGORY__ market_category','market_category.r_id=market.market_field_id')
            ->where($where)
            ->where($authMap)
            ->page($param['page'],$param['limit'])
            ->order()
            ->select();
        return $list;
    }
    
    /**
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/27 0027 09:46
     */
    public function marketList(){
        $list=db('admin_market')->where('status',1)->field('types,name')->select();
        $data=[
            0=>[
            'name'=>'客户',
            'types'=>'crm_customer'
        ],
            1=>[
                'name'=>'线索',
                'types'=>'crm_leads'
            ]
        ];
        $data=array_merge($data,$list);
        return $data;
    }
    public function createData($param){
        $fieldModel = new \app\admin\model\Field();
        $dataInfo = db('crm_market')->where(['name' => $param['name']])->find();
        if (isset($dataInfo)) {
            // 自动验证
            $validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
            $validate = new Validate($validateArr['rule'], $validateArr['message']);
        
            $result = $validate->check($param);
            if (!$result) {
                $this->error = $validate->getError();
                return false;
            }
        }
        if (db('crm_market')->data($param)->allowField(true)->isUpdate(false)->save()) {
            updateActionLog($param['create_user_id'], 'crm_market', $this->product_id, '', '', '创建了市场活动');
            RecordActionLog($param['create_user_id'], 'crm_market', 'save', $param['name'], '', '', '新增了市场活动' . $param['name']);
            $data = [];
            $data['product_id'] = $this->product_id;
            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }
    
    /**
     * 修改
     * @param $param
     * @param string $market_id
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/27 0027 15:57
     */
    public function updateDataById($param,$market_id = ''){
        $fieldModel = new \app\admin\model\Field();
        $dataInfo = db('crm_market')->where(['market_id' => $market_id])->find();
        if (isset($dataInfo)) {
            // 自动验证
            $validateArr = $fieldModel->validateField($this->name); //获取自定义字段验证规则
            $validate = new Validate($validateArr['rule'], $validateArr['message']);
        
            $result = $validate->check($param);
            if (!$result) {
                $this->error = $validate->getError();
                return false;
            }
        }
        $param['market_id'] = $market_id;
        if (db('crm_market')->update($param, ['market_id' => $market_id], true)) {
            updateActionLog($param['create_user_id'], 'crm_market', $this->market_id, '', '', '编辑了市场活动');
            RecordActionLog($param['create_user_id'], 'crm_market', 'update', $param['name'], '', '', '编辑了市场活动' . $param['name']);
            $data = [];
            $data['market_id'] = $this->market_id;
            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }
    
    /**
     * 删除
     * @param $id_list
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/28 0028 10:48
     */
    public function delete($id_list){
        $marketModel = model('Market');
        // 错误信息
        $delIds = [];
        $error_message = [];
        // 过滤后的ID
        $id_list_filter = db('crm_market')->where(['market_id' => ['IN', $id_list]])->column('market_id');
        $diff = array_diff($id_list, $id_list_filter);
        if (!empty($diff)) {
            foreach ($diff as $key => $val) {
                $error_message[] = sprintf('ID为 %d 的活动删除失败，错误原因：数据不存在或已删除。', $val);
            }
            array_unshift($error_message, '数据已更新，刷新页面后重试！');
            return resultArray(['error' => $error_message]);
        }
        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'market', 'delete');
        foreach ($id_list as $k => $v) {
            $isDel = true;
            //数据详情
            $data = $this->getDataById($v);
            if (!in_array($data['create_user_id'], $auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为' . $data['name'] . '的活动删除失败,错误原因：无权操作';
            }
            if ($isDel) {
                $delIds[] = $v;
            }
        }
        $dataInfo = db('crm_market')->where('market_id',['in',$delIds])->select();
        if ($delIds) {
            // 软删除数据
            $res = $marketModel::destroy(['market_id' => ['IN', $delIds]]);
            if ($res == count($delIds)) {
                // 添加删除记录
                $userInfo = $this->userInfo;
                foreach ($dataInfo as $k => $v) {
                    RecordActionLog($id_list['user_id'], 'crm_market', 'delete', $v['name'], '', '', '删除了活动：' . $v['name']);
                }
                return resultArray(['data' => '删除成功']);
            } else {
                return resultArray(['error' => '删除失败']);
            }
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }
    }
    
    /**
     * 基本信息
     * @param string $id
     * @param int $userId
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/27 0027 18:03
     */
    public function getDataById($id = '', $userId = 0)
    {
        $map['market_id'] = $id;
        $dataInfo = db('crm_market')->where($map)->find();
        if (!$dataInfo) {
            $this->error = '暂无此数据';
            return false;
        }
        
        # 获取封面图片
        $dataInfo['cover_images'] = $this->getMarketImages($dataInfo['cover_images']);
        # 获取详情图片
        $dataInfo['details_images'] = $this->getMarketImages($dataInfo['details_images']);
        
        $userModel = new \app\admin\model\User();
        $dataInfo['create_user_id_info'] = $userModel->getUserById($dataInfo['create_user_id']);
    
        $dataInfo['create_time'] = !empty($dataInfo['create_time']) ? date('Y-m-d H:i:s', $dataInfo['create_time']) : null;
        $dataInfo['update_time'] = !empty($dataInfo['update_time']) ? date('Y-m-d H:i:s', $dataInfo['update_time']) : null;
        // 字段授权
        if (!empty($userId)) {
            $grantData = getFieldGrantData($userId);
            $userLevel = isSuperAdministrators($userId);
            foreach ($dataInfo as $key => $value) {
                if (!$userLevel && !empty($grantData['crm_market'])) {
                    $status = getFieldGrantStatus($key, $grantData['crm_market']);
                    # 查看权限
                    if ($status['read'] == 0) unset($dataInfo[$key]);
                }
            }
        }
        return $dataInfo;
    }
    
    /**
     * 获取活动图片
     * @param $fileIds
     *
     * @author      alvin guogaobo
     * @version     1.0 版本号
     * @since       2021/4/28 0028 10:36
     */
    private function getMarketImages($fileIds)
    {
        $files = Db::name('admin_file')->whereIn('file_id', $fileIds)->select();
        
        foreach ($files as $key => $value) {
            $files[$key]['file_path'] = getFullPath($value['file_path']);
            $files[$key]['file_path_thumb'] = getFullPath($value['file_path_thumb']);
            $files[$key]['size'] = format_bytes($value['size']);
        }
        
        return $files;
    }
}