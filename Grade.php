<?php

namespace app\admin\controller\brand;

use think\Db;
use think\Session;
use think\Url;
use service\FormBuilder as Form;
use service\UtilService as Util;
use service\JsonService as Json;
use app\admin\controller\AuthController;
use app\admin\model\brand\Grade as GradeModel;

/**
 * 年级控制器
 * Class Grade
 * @package app\admin\controller\special
 */
class Grade extends AuthController
{
    public function index()
    {
        $this->assign('grade', GradeModel::getAll());
        return $this->fetch();
    }

    public function get_grade_list()
    {
        $where = Util::getMore([
            ['page', 1],
            ['limit', 20],
            ['cid', ''],
            ['name', ''],
        ]);
        return Json::successlayui(GradeModel::getAllList($where));
    }

    /**
     * 创建年纪
     * @param int $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function create($id = 0)
    {
        if ($id) $grade = GradeModel::get($id);
        $form = Form::create(Url::build('save', ['id' => $id]), [
            Form::input('name', '品牌名称', isset($grade) ? $grade->name : ''),
            Form::number('sort', '排序', isset($grade) ? $grade->sort : 0),
            Form::upload('image','品牌图片','upload')
        ]);
        $form->setMethod('post')->setTitle($id ? '修改品牌' : '添加品牌')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }
//    品牌图片添加方法
    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $path= $info->getSaveName();
                $image = \think\Image::open('./uploads/'.$path);
// 给原图左上角添加水印并保存water_image.png
                $image->text('online edu',ROOT_PATH.'/public/Fonts/arialbd.ttf',100,'#ffffff')->save('./uploads/'.$path);
//                存入session里
                \session('img',$path);
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    public function excel()
    {
      $list=Db::table('eb_brand')->select();
        vendor("phpexcel.Classes.PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        // 设置sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // 设置列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        // 设置表头
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', '品牌名称');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', '排序');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', '添加时间');
        //存取数据
        $num = 2;
        foreach ($list as $k => $v) {
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $num, $v['name']);
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $num, $v['sort']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $num, $v['add_time']);
            $num++;
        }
        // 文件名称
        $fileName = "品牌" . date('Y-m-d', time()) . rand(1, 1000);
        $xlsName = iconv('utf-8', 'gb2312', $fileName);
        // 设置工作表名
        $objPHPExcel->getActiveSheet()->setTitle('sheet');
        //下载 excel5与excel2007
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        ob_end_clean();     // 清除缓冲区,避免乱码
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl;charset=UTF-8");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=" . $xlsName . ".xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save("php://output");
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_value($field = '', $id = '', $value = '')
    {
        $field == '' || $id == '' || $value == '' && Json::fail('缺少参数');
        if (GradeModel::where(['id' => $id])->update([$field => $value]))
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 新增或者修改
     *
     * @return json
     */
    public function save($id = 0)
    {
        $post = Util::postMore([
            ['name', ''],
            ['sort', 0],
            ['image','']
        ]);
        $post['image']=session('img');
        if (!$post['name']) return Json::fail('请输入品牌名称');
        if ($id) {
            GradeModel::update($post, ['id' => $id]);
            return Json::successful('修改成功');
        } else {
            $post['add_time'] = time();
            if (GradeModel::set($post))
                return Json::successful('添加成功');
            else
                return Json::fail('添加失败');
        }
    }

    /**
     * 删除
     *
     * @return json
     */
    public function delete($id = 0)
    {
        if (!$id) return Json::fail('缺少参数');
        if (GradeModel::del($id))
            return Json::successful('删除成功');
        else
            return Json::fail('删除失败');
    }
}