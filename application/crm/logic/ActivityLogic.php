<?php
/**
 * 活动逻辑类
 *
 * @author qifan
 * @date 2020-12-09
 */

namespace app\crm\logic;

use app\crm\model\Activity;
use think\Db;

class ActivityLogic
{
    # 活动类型 1 线索 2 客户 3 联系人 4 产品 5 商机 6 合同 7 回款 8 日志 9 审批 10 日程 11 任务 12 发邮件
    private $activityType = [
        1  => ['en' => 'crm_leads',       'cn' => '线索'],
        2  => ['en' => 'crm_customer',    'cn' => '客户'],
        3  => ['en' => 'crm_contacts',    'cn' => '联系人'],
        4  => ['en' => 'crm_product',     'cn' => '产品'],
        5  => ['en' => 'crm_business',    'cn' => '商机'],
        6  => ['en' => 'crm_contract',    'cn' => '合同'],
        7  => ['en' => 'crm_receivables', 'cn' => '回款'],
        8  => ['en' => 'oa_log',          'cn' => '日志'],
        9  => ['en' => 'oa_examine',      'cn' => '审批'],
        10 => ['en' => 'oa_event',        'cn' => '日程'],
        11 => ['en' => 'oa_task',         'cn' => '任务'],
        12 => ['en' => 'mail',            'cn' => '发邮件']
    ];

    private $moduleToNumber = [
        'leads'       => 1,
        'customer'    => 2,
        'contacts'    => 3,
        'product'     => 4,
        'business'    => 5,
        'contract'    => 6,
        'receivables' => 7,
        'log'         => 8,
        'examine'     => 9,
        'event'       => 10,
        'task'        => 11,
        'email'       => 12
    ];

    /**
     * 活动列表
     *
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param)
    {
        $userId = $param['user_id'];
        unset($param['user_id']);
        unset($param['crmType']);

        $param['limit'] = !empty($param['limit']) ? $param['limit'] : 15;
        $param['page']  = !empty($param['page'])  ? $param['page']  : 1;

        $recordWhere    = [];
        $commonWhere    = [];
        $leadsWhere     = [];
        $customerWhere  = function () {};
        $contactsWhere  = function () {};
        $businessWhere  = function () {};
        $contractWhere  = function () {};
        $dateGroupWhere = function () {};

        # 跟进记录权限判断
        if (!checkPerByAction('crm', 'activity', 'index')) {
            $recordWhere['type'] = ['neq', 1];
        }

        # 设置时间分组查询条件，第一页就是当天的数据，第二页就是下一天的数据
        $datetime = Db::name('crm_activity')
            ->field('update_time')
            ->where($recordWhere)
            ->where('status', 1)
            ->where(function ($query) use ($param) {
                $query->whereOr(function ($query) use ($param) {
                    $query->where('activity_type_id', $param['activity_type_id']);
                    $query->where('activity_type', $this->moduleToNumber[$param['module']]);
                });
                $query->whereOr('customer_ids', 'like', '%'.$param['activity_type_id'].'%');
                $query->whereOr('contacts_ids', 'like', '%'.$param['activity_type_id'].'%');
                $query->whereOr('contract_ids', 'like', '%'.$param['activity_type_id'].'%');
                $query->whereOr('business_ids', 'like', '%'.$param['activity_type_id'].'%');
                $query->whereOr('leads_ids', 'like', '%'.$param['activity_type_id'].'%');
            })
            ->order('update_time', 'desc')
            ->group('update_time')
            ->select();
        $dateGroup = [0 => '']; // 加一个占位，page是从1开始
        $dateWhere = [0 => []]; // 加一个占位，page是从1开始
        foreach ($datetime AS $key => $value) {
            $date = date('Y-m-d', $value['update_time']);
            if (!in_array($date, $dateGroup)) {
                $dateGroup[] = $date;
                $dateWhere[] = [
                    'start_time' => strtotime($date . ' 00:00:00'),
                    'end_time'   => strtotime($date . ' 23:59:59')
                ];
            }
        }
        if (!empty($dateWhere[$param['page']])) {
            $dateGroupWhere = function ($query) use ($param, $dateWhere) {
                $query->where('update_time', '>=', $dateWhere[$param['page']]['start_time']);
                $query->where('update_time', '<=', $dateWhere[$param['page']]['end_time']);
            };
        }
        if (empty($dateWhere[$param['page']])) {
            return ['lastPage' => true, 'list' => [], 'time' => ''];
        }

        # 处理公共查询参数
        if (!empty($param['interval_day'])) {
            $commonWhere['update_time'] = ['egt', time() - 86400 * $param['interval_day']];
            $commonWhere['update_time'] = ['elt', time()];
        }
        if (!empty($param['start_date']) && !empty($param['end_date'])) {
            $commonWhere['update_time'] = ['egt', strtotime($param['start_date'])];
            $commonWhere['update_time'] = ['elt', strtotime($param['end_date'])];
        }
        if (!empty($param['search'])) {
            $commonWhere['content'] = ['like', '%' . $param['search'] . '%'];
        }
        if (!empty($param['activity_type'])) {
            $commonWhere['activity_type'] = $param['activity_type'];
        }

        # 组织线索、客户、联系人、商机、合同下的查询条件
        switch ($param['module']) {
            case 'leads' :
                $leadsWhere = function ($query) use ($param) {
                    $query->where('activity_type', 1);
                    $query->where('activity_type_id', $param['activity_type_id']);
                };
                break;
            case 'customer' :
                $contactsData    = [];
                $businessData    = [];
                $contractData    = [];
                $receivablesData = [];

                # 联系人ID串
                $contacts = Db::name('crm_contacts')->field(['contacts_id'])->where('customer_id', $param['activity_type_id'])->select();
                if (!empty($contacts)) {
                    $contactsData['activity_type']    = 3;
                    $contactsData['activity_type_id'] = array_reduce($contacts, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 商机ID串
                $business = Db::name('crm_business')->field(['business_id'])->where('customer_id', $param['activity_type_id'])->select();
                if (!empty($business)) {
                    $businessData['activity_type']    = 5;
                    $businessData['activity_type_id'] = array_reduce($business, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 合同ID串
                $contract = Db::name('crm_contract')->field(['contract_id'])->where('customer_id', $param['activity_type_id'])->select();
                if (!empty($contract)) {
                    $contractData['activity_type'] = 6;
                    $contractData['activity_type_id'] = array_reduce($contract, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 回款ID串
                $receivables = Db::name('crm_receivables')->field(['receivables_id'])->where('customer_id', $param['activity_type_id'])->select();
                if (!empty($receivables)) {
                    $receivablesData['activity_type'] = 7;
                    $receivablesData['activity_type_id'] = array_reduce($receivables, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 客户模块查询条件
                $customerWhere = function ($query) use ($param, $contactsData, $businessData, $contractData, $receivablesData) {
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('activity_type', 2);
                        $query->where('activity_type_id', $param['activity_type_id']);
                    });
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('customer_ids', 'like', '%' . $param['activity_type_id'] . '%');
                    });
                    if (!empty($contactsData)) {
                        $query->whereOr(function ($query) use ($contactsData) {
                            $query->where('activity_type', $contactsData['activity_type']);
                            $query->whereIn('activity_type_id', $contactsData['activity_type_id']);
                        });
                    }
                    if (!empty($businessData)) {
                        $query->whereOr(function ($query) use ($businessData) {
                            $query->where('activity_type', $businessData['activity_type']);
                            $query->whereIn('activity_type_id', $businessData['activity_type_id']);
                        });
                    }
                    if (!empty($contractData)) {
                        $query->whereOr(function ($query) use ($contractData) {
                            $query->where('activity_type', $contractData['activity_type']);
                            $query->whereIn('activity_type_id', $contractData['activity_type_id']);
                        });
                    }
                    if (!empty($receivablesData)) {
                        $query->whereOr(function ($query) use ($receivablesData) {
                            $query->where('activity_type', $receivablesData['activity_type']);
                            $query->whereIn('activity_type_id', $receivablesData['activity_type_id']);
                        });
                    }
                };

                break;
            case 'contacts' :
                $customerId = Db::name('crm_contacts')->where('contacts_id', $param['activity_type_id'])->value('customer_id');

                $businessData    = [];
                $contractData    = [];

                # 商机ID串
                $business = Db::name('crm_business')->field(['business_id'])->where('customer_id', $customerId)->select();
                if (!empty($business)) {
                    $businessData['activity_type']    = 5;
                    $businessData['activity_type_id'] = array_reduce($business, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 合同ID串
                $contract = Db::name('crm_contract')->field(['contract_id'])->where('contacts_id', $customerId)->select();
                if (!empty($contract)) {
                    $contractData['activity_type'] = 6;
                    $contractData['activity_type_id'] = array_reduce($contract, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }


                # 联系人模块查询条件
                $contactsWhere = function ($query) use ($param, $businessData, $contractData) {
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('activity_type', 3);
                        $query->where('activity_type_id', $param['activity_type_id']);
                    });
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('contacts_ids', 'like', '%' . $param['activity_type_id'] . '%');
                    });
                    if (!empty($businessData)) {
                        $query->whereOr(function ($query) use ($businessData) {
                            $query->where('activity_type', $businessData['activity_type']);
                            $query->whereIn('activity_type_id', $businessData['activity_type_id']);
                        });
                    }
                    if (!empty($contractData)) {
                        $query->whereOr(function ($query) use ($contractData) {
                            $query->where('activity_type', $contractData['activity_type']);
                            $query->whereIn('activity_type_id', $contractData['activity_type_id']);
                        });
                    }
                };

                break;
            case 'business' :
                $contractData    = [];
                # 合同ID串
                $contract = Db::name('crm_contract')->field(['contract_id'])->where('business_id', $param['activity_type_id'])->select();
                if (!empty($contract)) {
                    $contractData['activity_type'] = 6;
                    $contractData['activity_type_id'] = array_reduce($contract, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 商机模块查询条件
                $businessWhere = function ($query) use ($param, $contractData) {
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('activity_type', 5);
                        $query->where('activity_type_id', $param['activity_type_id']);
                    });
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('business_ids', 'like', '%' . $param['activity_type_id'] . '%');
                        $query->where(['activity_type' => ['neq', 2]]);
                    });
                    if (!empty($contractData)) {
                        $query->whereOr(function ($query) use ($contractData) {
                            $query->where('activity_type', $contractData['activity_type']);
                            $query->whereIn('activity_type_id', $contractData['activity_type_id']);
                        });
                    }
                };

                break;
            case 'contract' :
                $receivablesData = [];

                # 回款ID串
                $receivables = Db::name('crm_receivables')->field(['receivables_id'])->where('contract_id', $param['activity_type_id'])->select();
                if (!empty($receivables)) {
                    $receivablesData['activity_type'] = 7;
                    $receivablesData['activity_type_id'] = array_reduce($receivables, function ($result, $value) {
                        return array_merge($result, array_values($value));
                    }, []);
                }

                # 合同模块查询条件
                $contractWhere = function ($query) use ($param, $receivables) {
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('activity_type', 6);
                        $query->where('activity_type_id', $param['activity_type_id']);
                    });
                    $query->whereOr(function ($query) use ($param) {
                        $query->where('contract_ids', 'like', '%' . $param['activity_type_id'] . '%');
                    });
                    if (!empty($receivablesData)) {
                        $query->whereOr(function ($query) use ($receivablesData) {
                            $query->where('activity_type', $receivablesData['activity_type']);
                            $query->whereIn('activity_type_id', $receivablesData['activity_type_id']);
                        });
                    }
                };

                break;
        }

        $field = [
            'activity_id',
            'type',
            'category',
            'activity_type',
            'activity_type_id',
            'content',
            'contacts_ids',
            'next_time',
            'create_user_id',
            'update_time',
            'business_ids'
        ];
        $dataArray = Db::name('crm_activity')->field($field)
            ->where($dateGroupWhere)
            ->where($recordWhere)
            ->where($commonWhere)
            ->where($customerWhere)
            ->where($contactsWhere)
            ->where($businessWhere)
            ->where($contractWhere)
            ->where($leadsWhere)
            ->where('status', 1)
            ->order('update_time', 'desc')
            ->select();

        $fileModel = new \app\admin\model\File();
        foreach ($dataArray AS $key => $value) {
            # 用户信息 todo 有模型文件，时间问题，暂时将查询写在循环中
            $realname = Db::name('admin_user')->where('id', $dataArray[$key]['create_user_id'])->value('realname');
            $dataArray[$key]['create_user_name'] = $realname;

            # 附件信息
            if ($value['type'] = 1) {
                $files    = [];
                $images   = [];
                $fileList = $fileModel->getDataList(['module' => 'crm_activity', 'module_id' => $dataArray[$key]['activity_id']], 'all');
                if (!empty($fileList['list'])) {
                    foreach ($fileList['list'] AS $k => $v) {
                        if ($v['types'] == 'file') {
                            $files[] = $v;
                        } else {
                            $images[] = $v;
                        }
                    }
                }
                $dataArray[$key]['fileList'] = $files  ? : [];
                $dataArray[$key]['imgList']  = $images ? : [];
            }

            # 判断是不是本人添加的，如果不是将禁止删除修改
            $dataArray[$key]['auth'] = false;
            if ($dataArray[$key]['type'] == 1 && $dataArray[$key]['create_user_id'] == $userId) {
                $dataArray[$key]['auth'] = true;
            }

            # 查询联系人信息
            $dataArray[$key]['contacts_name'] = '';
            if ($dataArray[$key]['type'] == 1 && !empty($dataArray[$key]['contacts_ids'])) {
                $dataArray[$key]['contacts_name'] = Db::name('crm_contacts')->where('contacts_id', $dataArray[$key]['contacts_ids'])->value('name');
            }

            # 时间格式处理
            $dataArray[$key]['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
            $dataArray[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $dataArray[$key]['next_time']   = !empty($value['next_time']) ? date('Y-m-d H:i:s', $value['next_time']) : '';

            # 获取类型名称
            $dataArray[$key]['activity_type_name'] = $this->getActivityName($value['activity_type'], $value['activity_type_id']);

            # 客户模块跟进记录关联的商机
            $dataArray[$key]['business_list'] = $value['activity_type'] == 2 ? $this->getBusinessInfo($value['business_ids']) : [];
        }

        # 是否是最后一页
        $lastPage = !empty($dateGroup[$param['page']]) && $param['page'] < count($dateGroup) - 1 ? false : true;

        return ['lastPage' => $lastPage, 'list' => $dataArray, 'time' => !empty($dateGroup[$param['page']]) ? $dateGroup[$param['page']] : ''];
    }

    /**
     * 活动详情【跟进记录】
     *
     * @param $activityId
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($activityId)
    {
        # 查询跟进记录信息
        $field = ['activity_id', 'category', 'activity_type', 'activity_type_id', 'content', 'next_time', 'customer_ids', 'contacts_ids', 'contract_ids', 'business_ids'];
        $activityData = Activity::field($field)->where('type', 1)->where('activity_id', $activityId)->where('status', 1)->find();

        if (empty($activityData)) return [];

        # 查询与跟进记录关联的客户信息
        $customerData = Db::name('crm_customer')->field(['customer_id', 'name'])->whereIn('customer_id', $activityData['customer_ids'])->select();
        $activityData['customerInfo'] = $customerData;

        # 查询与跟进记录关联的联系人信息
        $contactsData = Db::name('crm_contacts')->field(['contacts_id', 'name'])->whereIn('contacts_id', $activityData['contacts_ids'])->select();
        $activityData['contactsInfo'] = $contactsData;

        # 查询与跟进记录关联的合同信息
        $contractData = Db::name('crm_contract')->field(['contract_id', 'name'])->whereIn('contract_id', $activityData['contract_ids'])->select();
        $activityData['contractInfo'] = $contractData;

        # 查询与跟进记录关联的商机信息
        $businessData = Db::name('crm_business')->field(['business_id', 'name'])->whereIn('business_id', $activityData['business_ids'])->select();
        $activityData['businessInfo'] = $businessData;

        # 查询与跟进记录关联的附件
        $fileData = (new \app\admin\model\File())->getDataList(['module' => 'crm_activity', 'module_id' => $activityId], 'all');
        $activityData['fileInfo'] = $fileData;

        return $activityData;
    }

    /**
     * 创建活动【跟进记录】
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save($param)
    {
        $userId      = $param['user_id'];
        $isEvent     = !empty($param['is_event']) ? $param['is_event']     : 0;
        $fileIds     = !empty($param['file_id'])  ? $param['file_id']      : [];
        unset($param['is_event']);
        unset($param['file_id']);
        unset($param['user_id']);

        $param['create_user_id'] = $userId;
        $param['type']           = 1;
        $param['next_time']      = !empty($param['next_time'])    ? strtotime($param['next_time'])            : 0;
        $param['business_ids']   = !empty($param['business_ids']) ? implode(',', $param['business_ids']) : '';
        $param['create_time']    = time();
        $param['update_time']    = time();

        $activityJson = Activity::create($param);
        if (empty($activityJson)) return false;

        $activityArray = json_decode($activityJson, true);
        if (empty($activityArray['activity_id'])) return false;

        # 设置最后跟进记录
        $this->setFollowRecord($param['activity_type'], $param['activity_type_id'], $param['content']);

        # 下次联系时间
        $this->updateNextTime($this->activityType[$param['activity_type']]['en'], $param['activity_type_id'], $param['next_time'],false);

        # 处理附件关系
        if (!empty($fileIds)) {
            $fileModel = new \app\admin\model\File();

            $fileModel->createDataById($fileIds, 'crm_activity', $activityArray['activity_id']);
        }

        # 同时创建日程
        if ($isEvent) {
            $eventModel = new \app\oa\model\Event();

            $data['title']          = trim($param['content']);
            $data['content']        = trim($param['content']);
            $data['start_time']     = !empty($param['next_time']) ? $param['next_time'] : time();
            $data['end_time']       = $param['next_time'] + 86399;
            $data['create_user_id'] = $param['create_user_id'];
            $data['business_ids']   = $param['business_ids'];
            $data['contacts_ids']   = $param['contacts_ids'];
            $data['is_live']        = true;
            if ($param['activity_type'] == 'crm_customer') $data['customer_ids'] = $param['activity_type_id'];

            $eventModel->createData($data);
        }

        return true;
    }

    /**
     * 修改活动【跟进记录】
     *
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function update($param)
    {
        $isEvent     = !empty($param['is_event']) ? $param['is_event']     : 0;
        $fileIds     = !empty($param['file_id'])  ? $param['file_id']      : [];
        unset($param['is_event']);
        unset($param['file_id']);

        $param['type']         = 1;
        $param['next_time']    = strtotime($param['next_time']);
        $param['business_ids'] = !empty($param['business_ids']) ? implode(',', $param['business_ids']) : '';
        $param['update_time']  = time();

        if (!Activity::update($param)) return false;

        # 设置最后跟进记录
        $this->setFollowRecord($param['activity_type'], $param['activity_type_id'], $param['content']);

        # 下次联系时间
        $this->updateNextTime($this->activityType[$param['activity_type']]['en'], $param['activity_type_id'], $param['next_time'],false);

        # 处理附件关系
        $fileModel = new \app\admin\model\File();
        if (!empty($fileIds)) {
            # 删除
            $fileModel->delRFileByModule('crm_activity', $param['activity_id']);

            # 添加
            $fileModel->createDataById($fileIds, 'crm_activity', $param['activity_id']);
        } else {
            # 删除
            $fileModel->delRFileByModule('crm_activity', $param['activity_id']);
        }

        # 同时创建日程
        if ($isEvent) {
            $eventModel = new \app\oa\model\Event();

            $data['title']          = trim($param['content']);
            $data['content']        = trim($param['content']);
            $data['start_time']     = !empty($param['next_time']) ? $param['next_time'] : time();
            $data['end_time']       = $param['next_time'] + 86399;
            $data['create_user_id'] = $param['create_user_id'];
            $data['business_ids']   = $param['business_ids'];
            $data['contacts_ids']   = $param['contacts_ids'];
            if ($param['activity_type'] == 2) $data['customer_ids'] = $param['activity_type_id'];

            $eventModel->createData($data);
        }

        return true;
    }

    /**
     * 删除活动【跟进记录】
     *
     * @param $activityId
     * @return Activity
     */
    public function delete($activityId)
    {
        $activityInfo = Db::name('crm_activity')->where(['activity_id' => $activityId])->find();
        if (Activity::update(['activity_id' => $activityId, 'status' => 0])) {
            $this->updateNextTime($this->activityType[$activityInfo['activity_type']]['en'], $activityInfo['activity_type_id'], '', true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 相关模块下次联系时间
     *
     * @param $types
     * @param $types_id
     * @param string $next_time
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function updateNextTime($types, $types_id, $next_time = '', $is_del = false)
    {
        switch ($types) {
            case 'crm_customer' : $dbName = db('crm_customer'); $dbId = 'customer_id'; $activity_type = 2; break;
            case 'crm_leads' :    $dbName = db('crm_leads');    $dbId = 'leads_id';    $activity_type = 1; break;
            case 'crm_contacts' : $dbName = db('crm_contacts'); $dbId = 'contacts_id'; $activity_type = 3; break;
            case 'crm_business' : $dbName = db('crm_business'); $dbId = 'business_id'; $activity_type = 5; break;
            default : break;
        }

        if (!$dbName || !$dbId) return true;

        $data = [];
        $data['update_time'] = time();
        if (!empty($next_time)) {
            $data['next_time'] = $next_time;
        } else {
            if ($is_del) {
                # 查找最近一条下次联系时间补上,如果没有就置空
                $resActivity = Db::name('crm_activity')->where(['type'=>1, 'activity_type'=>$activity_type, 'activity_type_id' => $types_id,'status'=>['neq',0]])->order('activity_id desc')->find();
                $data['next_time'] = $resActivity['next_time'] ? : 0;
                unset($data['update_time']);
            } else {
                # 如果未填写下次联系时间，并且 原下次联系时间为当天，则把下次联系时间置空
                $next_time = $dbName->where([$dbId => $types_id])->value('next_time');
                list($start, $end) = getTimeByType();
                if ($next_time >= $start && $next_time <= $end) {
                    $data['next_time'] = 0;
                }                
            }
        }
        if (!$is_del && in_array($types, ['crm_customer', 'crm_leads'])) {
            $data['follow'] = '已跟进';
        }
        $dbName->where([$dbId => $types_id])->update($data);
        return true;
    }

    /**
     * 获取常用语
     *
     * @return mixed
     */
    public function getPhrase()
    {
        $dataJson = Db::name('crm_config')->where('name', 'activity_phrase')->value('value');

        return !empty($dataJson) ? unserialize($dataJson) : [];
    }

    /**
     * 设置常用语
     *
     * @param $param
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setPhrase($param)
    {
        if (!Db::name('crm_config')->where('name', 'activity_phrase')->value('value')) {
            return Db::name('crm_config')->insert(['name' => 'activity_phrase', 'value' => serialize($param), 'description' => '跟进记录常用语']);
        }

        Db::name('crm_config')->where('name', 'activity_phrase')->update(['value' => serialize($param)]);
        return true;
    }

    /**
     * 获取修改过的跟进记录信息
     *
     * @param $activityId
     * @param $userId
     * @return array|bool|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFollowData($activityId, $userId)
    {
        $field = [
            'activity_id',
            'type',
            'category',
            'activity_type',
            'activity_type_id',
            'content',
            'contacts_ids',
            'next_time',
            'create_user_id',
            'update_time',
            'business_ids'
        ];
        $data = db('crm_activity')->where('activity_id', $activityId)->field($field)->find();

        $fileModel = new \app\admin\model\File();

        $realname = Db::name('admin_user')->where('id', $data['create_user_id'])->value('realname');
        $data['create_user_name'] = $realname;

        # 附件信息
        if ($data['type'] = 1) {
            $files    = [];
            $images   = [];
            $fileList = $fileModel->getDataList(['module' => 'crm_activity', 'module_id' => $activityId], 'all');
            if (!empty($fileList['list'])) {
                foreach ($fileList['list'] AS $k => $v) {
                    if ($v['types'] == 'file') {
                        $files[] = $v;
                    } else {
                        $images[] = $v;
                    }
                }
            }
            $data['fileList'] = $files  ? : [];
            $data['imgList']  = $images ? : [];
        }

        # 判断是不是本人添加的，如果不是将禁止删除修改
        $data['auth'] = false;
        if ($data['type'] == 1 && $data['create_user_id'] == $userId) {
            $data['auth'] = true;
        }

        # 查询联系人信息
        $data['contacts_name'] = '';
        if ($data['type'] == 1 && !empty($data['contacts_ids'])) {
            $data['contacts_name'] = Db::name('crm_contacts')->where('contacts_id', $data['contacts_ids'])->value('name');
        }

        $data['update_time'] = date('Y-m-d H:i:s', $data['update_time']);
        $data['next_time']   = !empty($data['next_time']) ? date('Y-m-d H:i:s', $data['next_time']) : null;

        # 关联商机
        $data['business_list'] = $data['activity_type'] == 2 ? $this->getBusinessInfo($data['business_ids']) : [];

        return $data;
    }

    /**
     * 获取活动类型名称
     *
     * @param $activityType
     * @param $activityTypeId
     * @return float|mixed|string|\think\db\Query
     */
    private function getActivityName($activityType, $activityTypeId)
    {
        $activityTypeName = '';

        # 线索
        if ($activityType == 1) {
            $activityTypeName = Db::name('crm_leads')->where('leads_id', $activityTypeId)->value('name');
        }
        # 客户
        if ($activityType == 2) {
            $activityTypeName = Db::name('crm_customer')->where('customer_id', $activityTypeId)->value('name');
        }
        # 联系人
        if ($activityType == 3) {
            $activityTypeName = Db::name('crm_contacts')->where('contacts_id', $activityTypeId)->value('name');
        }
        # 产品
        if ($activityType == 4) {
            $activityTypeName = Db::name('crm_product')->where('product_id', $activityTypeId)->value('name');
        }
        # 商机
        if ($activityType == 5) {
            $activityTypeName = Db::name('crm_business')->where('business_id', $activityTypeId)->value('name');
        }
        # 合同
        if ($activityType == 6) {
            $activityTypeName = Db::name('crm_contract')->where('contract_id', $activityTypeId)->value('name');
        }
        # 回款
        if ($activityType == 7) {
            $activityTypeName = Db::name('crm_receivables')->where('receivables_id', $activityTypeId)->value('number');
        }
        # 日志
        if ($activityType == 8) {
            $activityTypeName = Db::name('oa_log')->where('log_id', $activityTypeId)->value('title');
        }
        # 审批
        if ($activityType == 9) {
            $categoryId       = Db::name('oa_examine')->where('examine_id', $activityTypeId)->value('category_id');
            $activityTypeName = Db::name('oa_examine_category')->where('category_id', $categoryId);
        }
        # 日程
        if ($activityType == 10) {
            $activityTypeName = Db::name('oa_event')->where('event_id', $activityTypeId)->value('title');
        }
        # 任务
        if ($activityType == 11) {
            $activityTypeName = Db::name('task')->where('task_id', $activityTypeId)->value('name');
        }

        return $activityTypeName;
    }

    /**
     * 获取客户跟进记录关联的商机
     *
     * @param $businessIds
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getBusinessInfo($businessIds)
    {
        return Db::name('crm_business')->field(['business_id', 'name'])->whereIn('business_id', $businessIds)->select();
    }

    /**
     * 设置跟进记录
     *
     * @param $type
     * @param $typeId
     * @param $content
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function setFollowRecord($type, $typeId, $content)
    {
        $model      = null;
        $primaryKey = null;

        switch ($type) {
            case 1 : $model = db('crm_leads');    $primaryKey = 'leads_id';    break;
            case 2 : $model = db('crm_customer'); $primaryKey = 'customer_id'; break;
            case 3 : $model = db('crm_contacts'); $primaryKey = 'contacts_id'; break;
            case 5 : $model = db('crm_business'); $primaryKey = 'business_id'; break;
            case 6 : $model = db('crm_contract'); $primaryKey = 'contract_id'; break;
        }

        $model->where($primaryKey, $typeId)->update(['last_time' => time(), 'last_record' => $content]);
    }
}