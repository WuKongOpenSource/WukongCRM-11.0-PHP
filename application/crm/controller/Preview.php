<?php

namespace app\crm\controller;

use think\Controller;
use think\Request;

class Preview extends Controller
{
    public function previewPdf(Request $request)
    {
        # 处理跨域
//        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
//        header('Access-Control-Allow-Credentials: true');
//        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
//        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
        # 相应类型
        header('Content-Type: application/pdf');
        header('Transfer-Encoding: chunked');

        $key = $request->param('key');

        $data = db('admin_printing_data')->field(['type', 'content'])->where('key', $key)->find();

        $contentArray = json_decode($data['content'], true);
        $content      = $contentArray['data'];

        require_once(EXTEND_PATH.'tcpdf'.DS.'config'.DS.'tcpdf_config.php');
        require_once(EXTEND_PATH.'tcpdf'.DS.'tcpdf.php');

        $tcpdf = new \TCPDF();

        // 设置PDF页面边距：LEFT，TOP，RIGHT
        $tcpdf->SetMargins(10, 10, 10);

        // 设置字体，防止中文乱码
        $tcpdf->SetFont('simsun', '', 10);

        // 设置文件信息
//        $tcpdf->SetCreator(TITLE_NAME);
//        $tcpdf->SetAuthor(TITLE_NAME);
        $tcpdf->SetTitle("打印内容预览");

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

        $tcpdf->Output(ROOT_PATH.DS.'public'.DS.'temp'.DS.'pdf'.DS.'print.pdf','I');

        exit($this->fetch('preview', ['key' => $key]));
    }
}