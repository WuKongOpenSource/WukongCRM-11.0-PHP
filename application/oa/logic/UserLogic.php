<?php

namespace app\oa\logic;

use app\admin\model\user;
use think\Db;

class UserLogic
{
    public function exp()
    {
        $contract_types = "1,2,3,4,5,6,7,8,9";
        $exp = new \think\Db\Expression('field(user.realname,' . $contract_types . '),convert(user.realname using gb2312)  asc');
        return $exp;
    }

    /**
     * 通讯录列表
     * @param $param
     * @return array
     */
    public function getDataList($param)
    {
        $user_id = $param['user_id'];
        $search = $param['search'];
        $where = [];
        $initials_type = ($param['initials'] == 1) ? 1 : 2;
        $where['user.status'] = 1;
        if ($search) {
            $where = function ($query) use ($search) {
                $query->where('user.realname', array('like', '%' . $search . '%'))
                    ->whereOr('user.mobile', array('like', '%' . $search . '%'));
            };
        }
        if ($param['star_type'] == 1) {
            $item = Db::name('crm_star')->where('user_id', $user_id)->column('target_id');
            $where['user.id'] = ['in', $item];
        }
        if ($param['structure_id'] == '') {
            $list = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->where($where)
                ->field('user.id,user.thumb_img,user.realname,user.post,structure.name as structure_name,user.mobile')
                ->page($param['page'], $param['limit'])
                ->select();

            foreach ($list as $k => $v) {
                $starWhere = ['user_id' => $user_id, 'target_id' => $v['id'], 'type' => 'admin_user'];
                $star = Db::name('crm_star')->where($starWhere)->value('star_id');
                $list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
                $list[$k]['star'] = !empty($star) ? 1 : 0;
            }
            $dataCount = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->where($where)
                ->count();
            $newarray = $this->groupByInitials($list, 'realname', $initials_type);
        } else {
            $list = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->where($where)
                ->where('structure.id', $param['structure_id'])
                ->field('user.id,user.thumb_img,user.realname,user.post,structure.name as structure_name')
                ->page($param['page'], $param['limit'])
                ->select();
            $dataCount = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->where($where)
                ->where('structure.id', $param['structure_id'])
                ->count();
            foreach ($list as $k => $v) {
                $starWhere = ['user_id' => $user_id, 'target_id' => $v['id'], 'type' => 'admin_user'];
                $star = Db::name('crm_star')->where($starWhere)->value('star_id');
                $list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
                $list[$k]['star'] = !empty($star) ? 1 : 0;
            }
            $newarray = $this->groupByInitials($list, 'realname', $initials_type);
        }
        $data = [];
        $data['list'] = $newarray;
        $data['totalRow'] = $dataCount;
        return $data;
    }

    /**
     * 我关注的数据
     * @param $param
     * @return array
     */
    public function queryList($param)
    {
        $user_id = $param['user_id'];
        $search = $param['search'];
        $where = [];
        $where['user.status'] = 1;
        if ($search) {
            $where = function ($query) use ($search) {
                $query->where('user.realname', array('like', '%' . $search . '%'))
                    ->whereOr('user.mobile', array('like', '%' . $search . '%'));
            };
        }
        $item = Db::name('crm_star')->where('user_id', $user_id)->column('target_id');
        if ($param['structure_id'] == '') {
            $list = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->whereIn('user.id', $item)
                ->where($where)
                ->field('user.id,user.thumb_img,user.realname,user.post,structure.name as structure_name')
                ->orderRaw($this->exp())
                ->page($param['page'], $param['limit'])
                ->select();
            foreach ($list as $k => $v) {
                $starWhere = ['user_id' => $user_id, 'target_id' => $v['id'], 'type' => 'admin_user'];
                $star = Db::name('crm_star')->where($starWhere)->value('star_id');
                $list[$k]['star'] = !empty($star) ? 1 : 0;
            }
        } else {
            $list = Db::name('admin_user')
                ->alias('user')
                ->join('__ADMIN_STRUCTURE__ structure', 'structure.id = user.structure_id', 'LEFT')
                ->whereIn('user.id', $item)
                ->where($where)
                ->where('structure.id', $param['structure_id'])
                ->field('user.id,user.thumb_img,user.realname,user.post,structure.name as structure_name')
                ->orderRaw($this->exp())
                ->page($param['page'], $param['limit'])
                ->select();
            foreach ($list as $k => $v) {
                $starWhere = ['user_id' => $user_id, 'target_id' => $v['id'], 'type' => 'admin_user'];
                $star = Db::name('crm_star')->where($starWhere)->value('star_id');
                $list[$k]['star'] = !empty($star) ? 1 : 0;
            }
        }
        $data = [];
        $data['list'] = $list;
        return $data;
    }


    /**
     * 二维数组根据首字母分组排序
     * @param array $data 二维数组
     * @param string $targetKey 首字母的键名
     * @return array             根据首字母关联的二维数组
     */
    public function groupByInitials(array $data, $targetKey = 'name', $initials_type)
    {

        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'initials' => $this->getInitials($item[$targetKey]),
            ]);
        }, $data);
        $data = $this->sortInitials($data, $initials_type);
        $sortData = [];
        foreach ($data as $key => $value) {
            foreach ($value as $v) {
                $sortData[] = $v;
            }

        }
        return $sortData;
    }

    /**
     * 按字母排序
     * @param array $data
     * @return array
     */
    public function sortInitials(array $data, $initials_type)
    {
        $sortData = [];
        foreach ($data as $key => $value) {
            $sortData[$value['initials']][] = $value;
        }
        if ($initials_type == 1) {
            ksort($sortData, SORT_STRING);
        } else {
            krsort($sortData, SORT_STRING);
        }

        return $sortData;
    }

    /**
     * 获取首字母
     * @param string $str 汉字字符串
     * @return string 首字母
     */
    public function getInitials($str)
    {
        if (empty($str)) {
            return '';
        }
        $fchar = ord($str{0});
        if ($fchar >= ord('A') && $fchar <= ord('z')) {
            return strtoupper($str{0});
        }
        if (is_numeric($str)) {
            return $str{0};
        }
        $s1 = iconv('UTF-8', 'gb2312', $str);
        $s2 = iconv('gb2312', 'UTF-8', $s1);
        $s = $s2 == $str ? $s1 : $str;
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc == -9300) {
            return 'G';
        }
        if ($asc >= -20319 && $asc <= -20284) {
            return 'A';
        }

        if ($asc >= -20283 && $asc <= -19776) {
            return 'B';
        }

        if ($asc >= -19775 && $asc <= -19219) {
            return 'C';
        }

        if ($asc >= -19218 && $asc <= -18711) {
            return 'D';
        }

        if ($asc >= -18710 && $asc <= -18527) {
            return 'E';
        }

        if ($asc >= -18526 && $asc <= -18240) {
            return 'F';
        }

        if ($asc >= -18239 && $asc <= -17923) {
            return 'G';
        }

        if ($asc >= -17922 && $asc <= -17418) {
            return 'H';
        }

        if ($asc >= -17417 && $asc <= -16475) {
            return 'J';
        }

        if ($asc >= -16474 && $asc <= -16213) {
            return 'K';
        }

        if ($asc >= -16212 && $asc <= -15641) {
            return 'L';
        }

        if ($asc >= -15640 && $asc <= -15166) {
            return 'M';
        }

        if ($asc >= -15165 && $asc <= -14923) {
            return 'N';
        }

        if ($asc >= -14922 && $asc <= -14915) {
            return 'O';
        }

        if ($asc >= -14914 && $asc <= -14631) {
            return 'P';
        }

        if ($asc >= -14630 && $asc <= -14150) {
            return 'Q';
        }

        if ($asc >= -14149 && $asc <= -14091) {
            return 'R';
        }

        if ($asc >= -14090 && $asc <= -13319) {
            return 'S';
        }

        if ($asc >= -13318 && $asc <= -12839) {
            return 'T';
        }

        if ($asc >= -12838 && $asc <= -12557) {
            return 'W';
        }

        if ($asc >= -12556 && $asc <= -11848) {
            return 'X';
        }

        if ($asc >= -11847 && $asc <= -11056) {
            return 'Y';
        }

        if ($asc >= -11055 && $asc <= -10247) {
            return 'Z';
        }
        if ($asc >= -10247 && $asc <= -10247) {
            return 'Z';
        }
//        return '#';
    }
}