<?php
/**
 * 模板打印控制器
 *
 * @author qifan
 * @date 2020-12-15
 */

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\crm\logic\PrintingLogic;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use think\Controller;
use think\Hook;
use think\Request;

class Printing extends ApiCommon
{
    public function _initialize()
    {
        $action = [
            'permission'=>['previewData'],
            'allow'=>['printingdata', 'template', 'setrecord', 'getrecord', 'preview', 'down']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 获取打印的数据
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function printingData(PrintingLogic $printingLogic)
    {
        $actionId   = $this->param['action_id'];
        $templateId = $this->param['template_id'];
        $type       = $this->param['type'];
        $recordId   = $this->param['record_id'];

        $data = $printingLogic->getPrintingData($type, $actionId, $templateId, $recordId);

        return resultArray(['data' => $data]);
    }

    /**
     * 获取打印模板列表
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function template(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type'])) return resultArray(['error' => '请选择打印的类型！']);

        $data = $printingLogic->getTemplateList($this->param['type']);

        return resultArray(['data' => $data]);
    }

    /**
     * 创建模板打印记录
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     */
    public function setRecord(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type']))          return resultArray(['error' => '请选择模块！']);
        if (empty($this->param['action_id']))     return resultArray(['error' => '缺少数据ID！']);
        if (empty($this->param['template_id']))   return resultArray(['error' => '缺少模板ID！']);
        if (empty($this->param['recordContent'])) return resultArray(['error' => '缺少打印内容！']);

        $userId = $this->userInfo['id'];

        if (!$printingLogic->setRecord($userId, $this->param)) return resultArray(['error' => '操作失败！']);

        return resultArray(['data' => '操作成功！']);
    }

    /**
     * 获取打印记录
     *
     * @param PrintingLogic $printingLogic
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecord(PrintingLogic $printingLogic)
    {
        if (empty($this->param['crmType'])) return resultArray(['error' => '请选择模块！']);
        if (empty($this->param['typeId']))  return resultArray(['error' => '缺少数据ID']);

        $data = $printingLogic->getRecord($this->param, $this->userInfo['id']);

        return resultArray(['data' => $data]);
    }

    /**
     * 保存打印内容
     *
     * @param user_id 用户id
     * @param type 类型（work，pdf）
     * @param content 打印内容
     * @author fanqi
     * @date 2021-03-25
     * @return \think\response\Json
     */
    public function preview(PrintingLogic $printingLogic)
    {
        if (empty($this->param['type'])) return resultArray(['error' => '缺少类型参数！']);
        if (empty($this->param['content'])) return resultArray(['error' => '缺少打印内容！']);

        $userInfo = $this->userInfo;
        $this->param['user_id'] = $userInfo['id'];

        $key = $printingLogic->preview($this->param);

        return resultArray(['data' => $key]);
    }

    /**
     * 打下打印文件
     * @param string key 打印数据的唯一key
     * @author fanqi
     * @date 2021-03-26
     * @return \think\response\Json
     */
    public function down()
    {
        if (empty($this->param['key'])) return resultArray(['error' => '参数错误！']);

        $data = db('admin_printing_data')->field(['type', 'content'])->where('key', $this->param['key'])->find();

        $type         = $data['type'];
        $contentArray = json_decode($data['content'], true);
        $content      = $contentArray['data'];

        if ($type == 'pdf') {
            require_once(EXTEND_PATH.'tcpdf'.DS.'config'.DS.'tcpdf_config.php');
            require_once(EXTEND_PATH.'tcpdf'.DS.'tcpdf.php');

            $tcpdf = new \TCPDF();

            // 设置PDF页面边距：LEFT，TOP，RIGHT
            $tcpdf->SetMargins(10, 10, 10);

            // 设置字体，防止中文乱码
            $tcpdf->SetFont('simsun', '', 10);

            // 设置文件信息
            $tcpdf->SetCreator(TITLE_NAME);
            $tcpdf->SetAuthor(TITLE_NAME);
            $tcpdf->SetTitle("打印文件");

            // 删除预定义的打印 页眉/页尾
            $tcpdf->setPrintHeader(false);

            // 设置文档对齐，间距，字体，图片
            $tcpdf->SetCreator(PDF_CREATOR);
            $tcpdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $tcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

            // 自动分页
            $tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $tcpdf->setFontSubsetting(true);
            $tcpdf->setPageMark();

            $tcpdf->AddPage();
            $html = $content;
            $tcpdf->writeHTML($html, true, false, true, true, '');
            $tcpdf->lastPage();
            $tcpdf->Output('print.PDF','I');
        }

        if ($type == 'word') {
            $fileName = 'print.docx';
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=$fileName");
            header('Transfer-Encoding: chunked');

            $html = '<html xmlns:v="urn:schemas-microsoft-com:vml" 
            xmlns:o="urn:schemas-microsoft-com:office:office" 
            xmlns:w="urn:schemas-microsoft-com:office:word"  
            xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" 
            xmlns="http://www.w3.org/TR/REC-html40">';
            $html .= '<head><meta charset="UTF-8" /></head>';

            echo $html . '<body>'.$content .'</body></html>';
        }
    }
}