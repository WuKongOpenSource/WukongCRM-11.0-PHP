<?php

namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class NumberSequence extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'crm_number_sequence';

    /**
     *自定义回访编号(创建)
     * @return
     */
    public function createData($param)
    {
        $user_id = $param['user_id'];
        $param['sort']+=1;
        if ($data = $this->data($param)->allowField(true)->isUpdate(false)->save()) {
            //修改记录
            updateActionLog($user_id, 'crm_number_sequence', $this->number_sequence_id, '', '', '创建了自动生成编号规则');
            return $data;
        } else {
            $this->error = '添加失败';
            return false;
        }
    }

    /**
     * 列表
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDataList($param)
    {
        $list = db('crm_number_sequence')
            ->field('number_type,number_sequence_id,status')
            ->group('number_type')
            ->select();
        $data = array();

        foreach ($list as $key => $v) {
            $data[]['setting'] = db('crm_number_sequence')
                ->where('number_type', $v['number_type'])
                ->order('sort asc')
                ->select();
            $data[$key]['number_type'] = $v['number_type'];
            $data[$key]['number_sequence_id'] = $v['number_sequence_id'];
            # 前端的status值为1代表启用，后端保存的status值为0代表启用，这里执行以下取反操作；
            $data[$key]['status'] = $v['status'] == 0 ? 1 : 0;
        }
        return $data;
    }

    /**
     * @param $param
     * @param string $param_id
     * @return array|false
     */
    public function numberSequenceUpdate($param,$param_id=''){
        $user_id = $param['user_id'];
        if ($this->update($param, ['number_sequence_id' => $param_id], true)) {
            //修改记录
            updateActionLog($user_id, 'crm_number_sequence', $param_id, '', $param);
            $data = [];
            $data['number_sequence_id'] = $param_id;
            return $data;
        } else {
            $this->error = '编辑失败';
            return false;
        }
    }

    /**
     * 批量更新上次生成的编号【last_date】、上次生成的时间【create_time】
     *
     * @param $data
     * @return array|false|\think\Collection|\think\model\Collection
     * @throws \Exception
     */
    public function batchUpdate($data)
    {
        return $this->saveAll($data);
    }
}