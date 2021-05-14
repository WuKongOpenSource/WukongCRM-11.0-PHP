<?php
// +----------------------------------------------------------------------
// | Description: 应用配置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use com\Scan;

class LoginRecord extends Common
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    protected $name = 'admin_login_record';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 登录成功
     */
    const TYPE_SUCCESS = 0;

    /**
     * 密码错误
     */
    const TYPE_PWD_ERROR = 1;

    /**
     * 账号被禁用
     */
    const TYPE_USER_BANNED = 2;

    // 类型
    public $typeList = [
        self::TYPE_SUCCESS => '登录成功',
        self::TYPE_PWD_ERROR => '密码错误',
        self::TYPE_USER_BANNED => '账号被禁用',
    ];

    /**
     * 登录员工ID
     */
    public $user_id = 0;

    /**
     * 添加登录记录
     * todo 登录设备暂时不加 数据表字段未加。
     * @param int $type
     * @param int $platform 登录设备
     */
    public function createRecord($platform='',$type = 0)
    {
        $data = [];
        $data['type'] = $type;
        $data['create_user_id'] = $this->user_id;
        $data['create_time'] = time();
        $data['ip'] = (new Scan())->get_client_ip();
        $data['os'] = getOS();
        $data['browser'] = getBrowser();
        # todo登录设备暂时不加 数据表字段未加
//        $platform=['_mobile'=>'手机','_ding'=>'钉钉','_wechat'=>'微信','_wxwork'=>'企业微信'];
//        if(empty($platform)){
//            $data['device']='网页';
//        }else{
//            $data['device']=$platform[$platform];
//        }
        $ip_address = getAddressById($data['ip']);
        $data['address'] = $ip_address['country'];

        // 效果图有这个备注字段，不知道存啥，就把UA记录了一下
        $data['remark'] = $_SERVER['HTTP_USER_AGENT'];

        $this->save($data);
    }

    /**
     * 创建人
     */
    public function getCreateUserInfoAttr($val, $data)
    {
        return User::getUserById($data['create_user_id']) ?: [];
    }

    /**
     * 获取登录记录类型
     */
    public function getTypeNameAttr($val, $data)
    {
        return $this->typeList[$data['type']];
    }

    /**
     * 
     */
    /**
     * 固定时间内登录密码错超过限制
     *
     * @param integer $count    登录出错次数
     * @param integer $time     等待时间 （分钟）
     * @return bool
     */
    public function verify($count = 3, $time = 5)
    {
        $where = [
            'create_user_id' => $this->user_id,
            'create_time' => ['GT', time() - 60 * $time],
            'type' => 1
        ];
        $last_record = $this->order(['id' => 'DESC'])
            ->where($where)
            ->find();
        // 登录记录
        if ($last_record) {
            $last_time = strtotime($last_record['create_time']);
            $where['create_time'] = [
                'BETWEEN',
                [
                    $last_time - 60 * $time,
                    $last_time
                ]
            ];
            $list = $this->where($where)
                ->order(['id' => 'DESC'])
                ->column('type');
            if (count($list) >= $count) {
                $surplusTime = getTimeBySec(60 * $time - (time()-strtotime($last_record['create_time'])));
                $this->error = "密码错误次数过多，请在{$surplusTime}后重试！";
                return false;
            }
        }
        return true;
    }

}
