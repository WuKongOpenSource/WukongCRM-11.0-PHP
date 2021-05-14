<?php
// +----------------------------------------------------------------------
// | Description: CRM工作台
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use app\bi\model\Common;
use app\crm\model\Contract as CrmContractModel;
use app\crm\model\Receivables as ReceivablesModel;
use app\crm\logic\IndexLogic;
use app\crm\model\Customer as CustomerModel;
use think\Db;

class Index extends ApiCommon
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
            'allow' => [
                'index',
                'achievementdata',
                'funnel',
                'saletrend',
                'search',
                'indexlist',
                'getrecordlist',
                'forgottencustomercount',
                'forgottencustomerpagelist',
                'ranking',
                'querydatainfo',
                'queryrepeat',
                'autonumberstatus',
                'dashboard',
                'updatedashboard',
                'activitylist'
            ]
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    //月份数组
    protected $monthName = [
        '01' => 'january',
        '02' => 'february',
        '03' => 'march',
        '04' => 'april',
        '05' => 'may',
        '06' => 'june',
        '07' => 'july',
        '08' => 'august',
        '09' => 'september',
        '10' => 'october',
        '11' => 'november',
        '12' => 'december',
    ];

    /**
     * CRM工作台（销售简报）
     * @param
     * @return
     * @author Michael_xu
     */
    public function index()
    {
        // Db::query('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;');
        $param = $this->param;
        $indexModel = new IndexLogic;
        $data = $indexModel->index($param);
        // Db::query('COMMIT;');
        return resultArray(['data' => $data]);
    }

    /**
     * 业绩指标
     * @param
     * @return
     * @author Michael_xu
     */
    public function achievementData()
    {
        Db::query('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $adminModel = new \app\admin\model\Admin();
        $status = $param['label'] ?: 1; //1合同目标2回款目标
        $userWhere['type']=3;
        $userWhere['status']=$param['label'];
        $userIds = [];
        if ($param['dataType'] == 3 || $param['dataType'] == 4) {
            $param['structure_id'] = $userInfo['structure_id'];
            $userWhere['type']=2;
        }
        $whereArr = $adminModel->getWhere($param, 1, '');
        if($param['user_id']){
            $userWhere['type']=3;
        }elseif ($param['structure_id']){
            $userWhere['type']=2;
        }
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        if ($param['dataType'] == 1) {
            $userIds[] = $param['user_id'];
        } else {
            $userIds = $whereArr['userIds'];
        }
        $where['owner_user_id'] = array('in', $userIds);
        if (!empty($param['type'])) {
            $between_time = getTimeByType($param['type']);
            $start_time = $between_time[0];
            $end_time = $between_time[1];
        } else {
            //自定义时间
            $start_time = $param['start_time'] ?strtotime($param['start_time'].'00:00:00'): strtotime(date('Y-01-01', time()));
            $end_time = $param['end_time'] ? strtotime($param['end_time'].'23:59:59') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
            $between_time = array($start_time, $end_time);
        }

        if($param['label']==1){
            //合同金额
            $where_contract = $where;
            $where_contract['order_date'] = array('between', [date('Y-m-d', $between_time[0]), date('Y-m-d', $between_time[1])]);
            $sql = CrmContractModel::field([
                'SUM(CASE WHEN check_status = 2 THEN money ELSE 0 END) as money'
            ])
                ->where($where_contract)
                ->fetchSql()
                ->select();
            $contractMoney = queryCache($sql, 200);
        }else{
            //回款金额
            $where_receivables = $where;
            $where_receivables['return_time'] = array('between', [date('Y-m-d', $between_time[0]), date('Y-m-d', $between_time[1])]);
            $where_receivables['check_status'] = 2; //审核通过
            $sql1 = db('crm_receivables')->field([
                'SUM(CASE WHEN check_status = 2 THEN money ELSE 0 END) as money'
            ])
                ->where($where_receivables)
                ->fetchSql()
                ->select();
            $receivablesMoney = queryCache($sql1, 200);
        }

        if (!isset($param['user_id'])) {
            $userWhere['obj_id'] = $param['user_id'];
        } else {
            $userWhere['obj_id'] = ['in', $userIds];
        }

        //业绩目标

        //获取时间段包含年份
        $year = getYearByTime($start_time, $end_time);
        $achievement = db('crm_achievement')->where($userWhere)->select();
        $achievementMoney = 0.00;
        //获取需要查询的月份
        $month = getmonthByTime($start_time, $end_time);
        foreach ($achievement as $k => $v) {
            foreach ($month as $key => $val) {
                if ($v['year'] == $key) {
                    foreach ($val as $key1 => $val1) {
                        $achievementMoney += $v[$this->monthName[$val1]];
                    }
                }
            }
        }
        $data = [];
        //完成率
        $rate = 0.00;
        if ($status == 1) {
            $rate = $achievementMoney ? $contractMoney[0]['money'] / $achievementMoney : 0.00;
            $data['contractMoney'] = $contractMoney[0]['money'] ?: '0.00';
            $data['achievementMoney'] = $achievementMoney ?: '0.00';
            $data['money'] = $contractMoney[0]['money'] ?: '0.00';
            $data['rate'] = round($rate * 100, 2);
        } else {
            $rate = $achievementMoney ? $receivablesMoney[0]['money'] / $achievementMoney : 0.00;
            $data['receivablesMoney'] = $receivablesMoney[0]['money'] ?: '0.00';
            $data['money'] = $receivablesMoney[0]['money'] ?: '0.00';
            $data['achievementMoney'] = $achievementMoney ?: '0.00';
            $data['rate'] = round($rate * 100, 2);
        }
        Db::query('COMMIT;');
        return resultArray(['data' => $data]);
    }

    /**
     * 销售漏斗
     * @param
     * @return
     * @author Michael_xu
     */
    public function funnel()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];;
        $businessModel = new \app\crm\model\Business();
        $param['merge'] = 1;
        if($param['start_time'] && $param['end_time']){
            $param['start_time']=$param['start_time'].' 00:00:00';
            $param['end_time']=$param['end_time'].' 23:59:59';
        }
        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }

    /**
     * 销售趋势
     * @return
     */
    public function saletrend()
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();

        //统计条件
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userWhere['status']=$param['label'];
        if ($param['dataType'] == 3 || $param['dataType'] == 4) {
            $userWhere['type']=2;
        }else{
            $userWhere['type']=3;
        }
        if ($param['type']) {
            $last_where_contract = getTimeByType($param['type']);
            $userWhere['year']=date('Y',$last_where_contract[0]);
            $time = getTimeArray();
        } else {
            //自定义时间
            $param['start_time']=$param['start_time']?$param['start_time'].' 00:00:00':0;
            $param['end_time']=$param['end_time'].' 23:59:59';
            $time = getTimeArray(strtotime($param['start_time']),strtotime($param['end_time']));
        }
        $whereArr = $adminModel->getWhere($param, 1, '');
        if($param['user_id']){
            $userWhere['type']=3;
        }elseif ($param['structure_id']){
            $userWhere['type']=2;
        }
        $userIds = $whereArr['userIds'];
        if (!empty($param['user_id'])) {
            $userWhere['obj_id'] = $param['user_id'];
        } else {
            $userWhere['obj_id'] = ['in', $userIds];
        }
        //时间
        $ax = 7;
        if ($time['time_format'] == '%Y-%m-%d') {
            $ax = 10;
        }
        $auth_customer_user_ids = $userModel->getUserByPer('crm', 'contract', 'index');
        $auth_customer_user_ids = $auth_customer_user_ids ? array_intersect($userIds, $auth_customer_user_ids) : []; //取交集
        $between_time = [date('Y-m-d', $whereArr['between_time'][0]), date('Y-m-d', $whereArr['between_time'][1])];

        if($param['label']==1){
            $field_contract["SUBSTR(`order_date`, 1, " . $ax . ")"] = 'type';
            $field_contract['SUM(`money`)'] = 'sum';
            $achievementData = CrmContractModel::field($field_contract)
                ->where([
                    'owner_user_id' => ['IN', $auth_customer_user_ids],
                    'check_status' => 2,
                    'order_date' => ['BETWEEN', $between_time]
                ])
                ->group('type')
                ->fetchSql()
                ->select();
            $res_contract = queryCache($achievementData, 200);

            $res_money = array_column($res_contract, null, 'type');
        }else{
            $field_receivables["SUBSTR(`return_time`, 1, " . $ax . ")"] = 'type';
            $field_receivables['SUM(`money`)'] = 'sum';

            $sql_receivables = ReceivablesModel::field($field_receivables)
                ->where([
                    'owner_user_id' => ['IN', $auth_customer_user_ids],
                    'check_status' => 2,
                    'return_time' => ['BETWEEN', $between_time]
                ])
                ->group('type')
                ->fetchSql()
                ->select();

            $res_receivables = queryCache($sql_receivables, 200);
            $res_money = array_column($res_receivables, null, 'type');

        }
        $list = array();
        $money = '0.00';
        
        foreach ($time['list'] as $val) {
            $item = [];
            $item['type'] = $val['type'];
            $item['money'] = $res_money[$val['type']]['sum'] ?: 0;
            $money += $item['money'];

            $achievement=Db::name('crm_achievement')->where($userWhere)->select();
            $data_time=date('m',strtotime($val['type']));
            $num='';
            foreach ($achievement as $val){
                $num+=(int)$val[$this->monthName[$data_time]];
                    $item['achievement']=$num;
            }
            $list[] = $item;
        }
        $data['list'] = $list;
        $data['money'] = $money ?: '0.00';
        return resultArray(['data' => $data]);
    }

    /**
     * 回款计划提醒
     * @param day 最近7天 15天...
     * @return
     * @author Michael_xu
     */
    public function receivablesPlan()
    {
        $param = $this->param;
        $adminModel = new \app\admin\model\Admin();
        if($param['start_time'] && $param['end_time']){
            $param['start_time']=$param['start_time'].'00:00:00';
            $param['end_time']=$param['end_time'].'23:59:59';
        }
        $whereArr = $adminModel->getWhere($param, '', ''); //统计条件
        $userIds = $whereArr['userIds'];
        $where = [];
        $where['owner_user_id'] = array('in', $userIds);
        //已逾期
        $return_date = array('< time', date('Y-m-d', time()));
        $where['status'] = 0;
        if ($param['day']) {
            $return_date = array('between time', array(date('Y-m-d', time()), date('Y-m-d', strtotime(date('Y-m-d', time())) + 86399 + (86400 * (int)$param['day']))));
        }
        $where['return_date'] = $return_date;
        $planList = db('crm_receivables_plan')->where($where)->select();
        return resultArray(['data' => $planList]);
    }

    /**
     * 待跟进客户
     * @param day 最近3天 7天...
     * @return
     * @author Michael_xu
     */
    public function noFollowUp()
    {
        $param = $this->param;
        $adminModel = new \app\admin\model\Admin();
        
        $whereArr = $adminModel->getWhere($param, '', ''); //统计条件
        $userIds = $whereArr['userIds'];
        $where = [];
        $where['owner_user_id'] = array('in', $userIds);
        $day = (int)$param['day'] ?: 3;
        $where['next_time'] = array('between', array(strtotime(date('Y-m-d', time())), strtotime(date('Y-m-d', time())) + 86399 + (86400 * (int)$param['day'])));
        $customerList = db('crm_customer')->where($where)->select();
        return resultArray(['data' => $customerList]);
    }

    /**
     * 客户名称、联系人姓名、联系人手机号查询
     * @param
     * @return
     * @author Michael_xu
     */
    public function search()
    {
        $param = $this->param;
        $page = $param['page'] ?: 1;
        $limit = $param['limit'] ?: 15;
        $types = $param['types'] ?: '';
        $userModel = new \app\admin\model\User();

        //省数组
        $address_arr = array('北京', '天津', '河北', '山西', '内蒙古自治区', '辽宁', '吉林', '黑龙江', '上海', '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', '湖北', '湖南', '广东', '广西壮族自治区', '海南', '重庆', '四川', '贵州', '云南', '西藏自治区', '陕西', '甘肃', '青海', '宁夏回族自治区', '新疆维吾尔自治区', '台湾', '香港特别行政区', '澳门特别行政区',);
        $addr_arr = array('北京', '天津', '河北省', '山西省', '内蒙古自治区', '辽宁省', '吉林省', '黑龙江省', '上海', '江苏省', '浙江省', '安徽省', '福建省', '江西省', '山东省', '河南省', '湖北省', '湖南省', '广东省', '广西壮族自治区', '海南省', '重庆', '四川省', '贵州省', '云南省', '西藏自治区', '陕西省', '甘肃省', '青海省', '宁夏回族自治区', '新疆维吾尔自治区', '台湾省', '香港特别行政区', '澳门特别行政区',);
        $city_arr = array('石家庄', '唐山', '秦皇岛', '邯郸', '邢台', '保定', '张家口', '承德', '沧州', '廊坊', '衡水', '太原', '大同', '阳泉', '长治', '晋城', '朔州', '晋中', '运城', '忻州', '临汾', '吕梁', '呼和浩特', '包头', '乌海', '赤峰', '通辽', '鄂尔多斯', '呼伦贝尔', '巴彦淖尔', '乌兰察布', '兴安盟', '锡林郭勒盟', '阿拉善盟', '沈阳', '大连', '鞍山', '抚顺', '本溪', '丹东', '锦州', '营口', '阜新', '辽阳', '盘锦', '铁岭', '朝阳', '葫芦岛', '长春', '吉林', '四平', '辽源', '通化', '白山', '松原', '白城', '延边朝鲜族自治州', '哈尔滨', '齐齐哈尔', '鸡西', '鹤岗', '双鸭山', '大庆', '伊春', '佳木斯', '七台河', '牡丹江', '黑河', '绥化', '大兴安岭', '南京', '无锡', '徐州', '常州', '苏州', '南通', '连云港', '淮安', '盐城', '扬州', '镇江', '泰州', '宿迁', '杭州', '宁波', '温州', '嘉兴', '湖州', '绍兴', '金华', '衢州', '舟山', '台州', '丽水', '合肥', '芜湖', '蚌埠', '淮南', '马鞍山', '淮北', '铜陵', '安庆', '黄山', '滁州', '阜阳', '宿州', '巢湖', '六安', '亳州', '池州', '宣城', '福州', '厦门', '莆田', '三明', '泉州', '漳州', '南平', '龙岩', '宁德', '南昌', '景德镇', '萍乡', '九江', '新余', '鹰潭', '赣州', '吉安', '宜春', '抚州', '上饶', '济南', '青岛', '淄博', '枣庄', '东营', '烟台', '潍坊', '济宁', '泰安', '威海', '日照', '莱芜', '临沂', '德州', '聊城', '滨州', '荷泽', '郑州', '开封', '洛阳', '平顶山', '安阳', '鹤壁', '新乡', '焦作', '濮阳', '许昌', '漯河', '三门峡', '南阳', '商丘', '信阳', '周口', '驻马店', '武汉', '黄石', '十堰', '宜昌', '襄樊', '鄂州', '荆门', '孝感', '荆州', '黄冈', '咸宁', '随州', '恩施土家族苗族自治州', '长沙', '株洲', '湘潭', '衡阳', '邵阳', '岳阳', '常德', '张家界', '益阳', '郴州', '永州', '怀化', '娄底', '湘西土家族苗族自治州', '广州', '韶关', '深圳', '珠海', '汕头', '佛山', '江门', '湛江', '茂名', '肇庆', '惠州', '梅州', '汕尾', '河源', '阳江', '清远', '东莞', '中山', '潮州', '揭阳', '云浮', '南宁', '柳州', '桂林', '梧州', '北海', '防城港', '钦州', '贵港', '玉林', '百色', '贺州', '河池', '来宾', '崇左', '海口', '三亚', '成都', '自贡', '攀枝花', '泸州', '德阳', '绵阳', '广元', '遂宁', '内江', '乐山', '南充', '眉山', '宜宾', '广安', '达州', '雅安', '巴中', '资阳', '阿坝藏族羌族自治州', '甘孜藏族自治州', '凉山彝族自治州', '贵阳', '六盘水', '遵义', '安顺', '铜仁', '黔西南布依族苗族自治州', '毕节', '黔东南苗族侗族自治州', '黔南布依族苗族自治州', '昆明', '曲靖', '玉溪', '保山', '昭通', '丽江', '思茅', '临沧', '楚雄彝族自治州', '红河哈尼族彝族自治州', '文山壮族苗族自治州', '西双版纳傣族自治州', '大理白族自治州', '德宏傣族景颇族自治州', '怒江傈僳族自治州', '迪庆藏族自治州', '拉萨', '昌都', '山南', '日喀则', '那曲', '阿里', '林芝', '西安', '铜川', '宝鸡', '咸阳', '渭南', '延安', '汉中', '榆林', '安康', '商洛', '兰州', '嘉峪关', '金昌', '白银', '天水', '武威', '张掖', '平凉', '酒泉', '庆阳', '定西', '陇南', '临夏回族自治州', '甘南藏族自治州', '西宁', '海东', '海北藏族自治州', '黄南藏族自治州', '海南藏族自治州', '果洛藏族自治州', '玉树藏族自治州', '海西蒙古族藏族自治州', '银川', '石嘴山', '吴忠', '固原', '中卫', '乌鲁木齐', '克拉玛依', '吐鲁番', '哈密', '昌吉回族自治州', '博尔塔拉蒙古自治州', '巴音郭楞蒙古自治州', '阿克苏', '克孜勒苏柯尔克孜自治州', '喀什', '和田', '伊犁哈萨克自治州', '塔城', '阿勒泰', '省直辖行政单位',);

        $un_arr = ['中国', '公司', '有限公司', '有限责任公司', '股份有限公司'];
        $name = $param['name'] ? trim($param['name']) : '';
        if (in_array($name, $address_arr) || in_array($name, $addr_arr) || in_array($name, $city_arr) || in_array($name, $un_arr)) {
            return resultArray(['error' => '查询条件不符合规则']);
        }
        if ($types == 'crm_customer') {
            if (!$param['name'] && !$param['mobile'] && !$param['telephone']) return resultArray(['error' => '查询条件不能为空']);
            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `name` like '%" . $param['name'] . "%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `mobile` = '" . $param['mobile'] . "'";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `telephone` = '" . $param['telephone'] . "' ";
            }
            $dataList = db('crm_customer')
                ->where($resWhere)
                ->field('name,customer_id,owner_user_id')
                ->limit(($page - 1) * $limit, $limit)
                ->select();
            $dataCount = db('crm_customer')
                ->where($resWhere)
                ->count();
            $customerModel = model('Customer');
            $wherePool = $customerModel->getWhereByPool();
            foreach ($dataList as $k => $v) {
                $dataList[$k]['name'] = $v['name'] ?: '查看详情';
                if ($v['owner_user_id'] > 0) {
                    $pool = $customerModel->alias('customer')
                        ->where(['customer_id' => $v['customer_id']])
                        ->where($wherePool)
                        ->value('customer_id');
                    // 是客户池
                    if ($pool) {
                        $dataList[$k]['owner_user_id_info'] = [];
                    } else {
                        $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
                    }
                } else {
                    $dataList[$k]['owner_user_id_info'] = [];
                }
            }
        } elseif ($types == 'crm_contacts') {
            if (!$param['name'] && !$param['customer_name'] && !$param['telephone'] && !$param['mobile']) return resultArray(['error' => '查询条件不能为空']);
            if (in_array($param['customer_name'], $address_arr) || in_array($param['customer_name'], $addr_arr) || in_array($param['customer_name'], $city_arr) || in_array($param['customer_name'], $un_arr)) {
                return resultArray(['error' => '查询条件不符合规则']);
            }

            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `contacts`.`name` like '%" . $param['name'] . "%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `contacts`.`mobile` = '" . $param['mobile'] . "' ";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `contacts`.`telephone` = '" . $param['telephone'] . "' ";
            } elseif ($param['customer_name']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `customer`.`name` like '%" . $param['customer_name'] . "%'";
            }
            $dataList = db('crm_contacts')
                ->alias('contacts')
                ->join('__CRM_CUSTOMER__ customer', 'contacts.customer_id = customer.customer_id', 'LEFT')
                ->where($resWhere)
                ->field('contacts.name,contacts.contacts_id,contacts.customer_id,contacts.owner_user_id,customer.name as customer_name')
                ->page($page, $limit)
                ->select();
            $dataCount = db('crm_contacts')
                ->alias('contacts')
                ->join('__CRM_CUSTOMER__ customer', 'contacts.customer_id = customer.customer_id', 'LEFT')
                ->where($resWhere)
                ->count();
            foreach ($dataList as $k => $v) {
                $dataList[$k]['name'] = $v['name'] ?: '查看详情';
                $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            }
        } elseif ($types == 'crm_leads') {
            if (!$param['name'] && !$param['telephone'] && !$param['mobile']) return resultArray(['error' => '查询条件不能为空']);
            $resWhere = '';
            if ($param['name']) {
                $resWhere .= " `name` like '%" . $param['name'] . "%' ";
            } elseif ($param['mobile']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `mobile` = '" . $param['mobile'] . "'";
            } elseif ($param['telephone']) {
                if ($resWhere) $resWhere .= 'OR';
                $resWhere .= " `telephone` = '" . $param['telephone'] . "'";
            }
            $dataList = db('crm_leads')
                ->where($resWhere)
                ->field('name,leads_id,owner_user_id')
                ->page($page, $limit)
                ->select();
            $dataCount = db('crm_leads')
                ->where($resWhere)
                ->count();
            foreach ($dataList as $k => $v) {
                $dataList[$k]['name'] = $v['name'] ?: '查看详情';
                $dataList[$k]['owner_user_id_info'] = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            }
        }
        $data = [];
        $data['dataList'] = $dataList ?: [];
        $data['dataCount'] = $dataCount ?: 0;
        return resultArray(['data' => $data]);
    }

    /**
     * 查重（客户、公海、线索）
     * @param \app\crm\model\Index $index
     * @return \think\response\Json
     */
    public function queryRepeat(\app\crm\model\Index $index)
    {
        if (empty($this->param['type'])) return resultArray(['error' => '请选择查重类型！']);
        if (empty($this->param['content'])) return resultArray(['error' => '请填写查重内容！']);
        $type = $this->param['type'];
        $content = $this->param['content'];
        $data = $index->getQueryRepeat($type, $content);
        return resultArray(['data' => $data]);
    }

    /**
     * CRM工作台跳转（销售简报）
     * @param
     * @return
     * @author Michael_xu
     */
    public function indexList()
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $param = $this->param;
        // 2客户 3联系人 5商机 6合同 7回款 ''跟进记录
        $label = $param['label'];
        $types = $param['types'];
        $userInfo = $this->userInfo;
        $user_id = $param['user_id'] ? : $userInfo['id'];
        if($param['start_time'] && $param['end_time']){
            $param['start_time']=$param['start_time'].' 00:00:00';
            $param['end_time']=$param['end_time'].' 23:59:59';
        }
        $whereArr = $adminModel->getWhere($param, 1, ''); //统计条件
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];        
        $start_time = $between_time[0];
        $end_time = $between_time[1];

        $where = [];
        $where['page'] = $param['page'];
        $where['limit'] = $param['limit'];
        $where['search'] = $param['search'];
        $typesArr = ['crm_customer', 'crm_contacts', 'crm_business', 'crm_contract', 'crm_receivables', 'crm_activity'];
        if (!in_array($types, $typesArr)) {
            return resultArray(['error' => '参数错误']);
        }
        $m = 'crm';
        $a = 'index';
        switch ($types) {
            case 'crm_customer' :
                $c = 'customer';
                $model = new \app\crm\model\Customer();
                break;
            case 'crm_contacts' :
                $c = 'contacts';
                $model = new \app\crm\model\Contacts();
                break;
            case 'crm_business' :
            case 'crm_business_status' :
                $c = 'business';
                $model = new \app\crm\model\Business();
                break;
            case 'crm_contract' :
                $c = 'contract';
                $model = new \app\crm\model\Contract();
                break;
            case 'crm_receivables' :
                $c = 'receivables';
                $model = new \app\crm\model\Receivables();
                break;
            case 'crm_activity' :
                $c = 'activity';
                $model = new \app\crm\model\Activity();
                break;
        }
        //场景默认全部
        $scene_id = db('admin_scene')->where(['types' => $types, 'bydata' => 'all'])->value('scene_id');
        $where['scene_id'] = $scene_id ?: '';
        $where['create_time']['start'] = $start_time;
        $where['create_time']['end'] = $end_time;

        $auth_user_ids = $userModel->getUserByPer('crm', $c, 'index');
        $auth_user_ids = $auth_user_ids ? array_intersect($userIds, $auth_user_ids) : []; //取交集

        if ($c != 'activity') {
            $where['owner_user_id']['value'] = $userIds;
            if($types=='crm_contract' || $types=='crm_receivables'){
                $where['check_status']=2;
            }
            $data = $model->getDataList($where);
        } else {
            $typesList = ['1', '2', '3', '5', '6'];
            $arr = [];
            $where1['create_time'] = ['between', [$start_time, $end_time]];
            $where1['create_user_id'] = ['in', $auth_user_ids];   //跟进记录查询条件
            $where1['type'] = 1;
            $where1['status'] = 1;
            foreach ($typesList as $k => $v) {
                $where1['activity_type'] = $v;
                $dataCount = db('crm_activity')->where($where1)->count();
                if($v==1){
                    $arr[$k]['types'] ='crm_leads';
                    $arr[$k]['activity_type'] =1;
                }elseif ($v==2){
                    $arr[$k]['types'] ='crm_customer';
                    $arr[$k]['activity_type'] =2;
                }elseif ($v==3){
                    $arr[$k]['types'] ='crm_contacts';
                    $arr[$k]['activity_type'] =3;
                }elseif ($v==5){
                    $arr[$k]['types'] ='crm_business';
                    $arr[$k]['activity_type'] =5;
                }elseif ($v==6){
                    $arr[$k]['types'] ='crm_contract';
                    $arr[$k]['activity_type'] =6;
                }
                $arr[$k]['dataCount'] = $dataCount;
                $arr[$k]['create_user_id'] = implode(',', $userIds);
                $arr[$k]['create_time'] = implode(',', $where['create_time']);    //查询条件返回
            }
            $data = $arr;
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 遗忘提醒
     * @return \think\response\Json
     */
    public function forgottenCustomerCount()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $indexModel = new IndexLogic;
        $customerList = $indexModel->getDataList($param);
        return resultArray(['data' => $customerList]);
    }

    /**
     * 遗忘提醒列表
     * @return \think\response\Json
     */
    public function forgottenCustomerPageList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $indexModel = new IndexLogic;
        $customerList = $indexModel->forgottenCustomerPageList($param);
        return resultArray(['data' => $customerList]);
    }

    /**
     * 排行榜
     */
    public function ranking()
    {
        // Db::query('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id']?:$userInfo['id'];
        $indexModel = new IndexLogic;
        $data = $indexModel->ranking($param);
        return resultArray(['data' => $data]);

    }

    /**
     *数据汇总
     */
    public function queryDataInfo()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id']?:$userInfo['id'];
        $indexModel = new IndexLogic;
        $data = $indexModel->queryDataInfo($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 销售漏斗点击查看赢单输单
     */
    public function businessList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $param['user_id'] ?: $userInfo['id'];
        $indexModel = new IndexLogic;
        $data = $indexModel->businessList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 查询自动编号是否开启
     *
     * @return \think\response\Json
     */
    public function autoNumberStatus()
    {
        $type = $this->param['type'];
        $typeArray = ['crm_contract' => 1, 'crm_receivables' => 2, 'crm_visit' => 3, 'crm_invoice' => 4];
        $status = Db::name('crm_number_sequence')->where('number_type', $typeArray[$type])->where('status', 0)
            ->value('number_sequence_id');
        return resultArray(['data' => ['status' => !empty($status) ? true : false]]);
    }

    /**
     * 仪表盘布局
     */
    public function dashboard()
    {
        $param = $this->param;
        unset($param['user_id']);
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $dashboard = new IndexLogic;
        $data = $dashboard->dashboard($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 修改仪表盘布局
     * @return mixed
     */
    public function updateDashboard()
    {
        $param = $this->param;
        unset($param['user_id']);
        $param['dashboard'] = $param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $dashboard = new IndexLogic;
        $data = $dashboard->updateDashboard($param);
        return resultArray(['data' => '修改成功']);
    }

    /**
     * 跟进详情
     * @return \think\response\Json
     */
    public function activityList(){
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['id']=$userInfo['id'];
        $indexLogic=new IndexLogic();
        $data=$indexLogic->activityList($param);
        return resultArray(['data'=>$data]);
    }
}