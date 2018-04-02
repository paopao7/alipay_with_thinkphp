<?php
namespace Home\Controller;
use Org\Alipay;
use Think\Controller;

class IndexController extends Controller {
	//该方法为显示支付页面
    public function index(){
    	//获取账户余额
    	$model_users = D('Users');
    	$user = $model_users->where('id=1')->find();

    	$this->assign('user',$user);
        $this->display();
    }

    //该方法为显示配置页面
    public function config(){
        $model = D('Config');
        $where['id'] = array('eq',1);

        $result = $model->where($where)->find();

        if($result['app_id'] or $result['notify_url'] or $result['return_url'] or $result['alipay_public_key'] or $result['merchant_private_key']){
            $this->assign('result',$result);
        }
        $this->display();
    }

    //该方法为将配置信息保存数据库
    public function save_config(){
        $id = $_POST['id'];

        $model = D('Config');

        if (!$model->create()){
             // 如果创建失败 表示验证没有通过 输出错误提示信息
            $this->error($model->getError(),0);
        }else{
            $flag=$model->save();            
            if($flag){
                $this->jumpUrl = U("Home/Index/index");
                $this->success("配置成功");
            }else{
                $this->error("配置失败",0);
            }
        }

        
    }

 	//获取支付金额并写入支付记录表以及支付
    public function pay_action(){
		//获取需要支付的金额
        $money = $_POST['money'];

        if(!preg_match("/^\d+\.?\d{0,2}$/",$money) || $money == 0){
            $this->error("充值金额有误，请重新输入");
        }

        //查询配置表是否已完成配置
        $model_config = D('Config');
        $where_config['id'] = array('eq',1);

        $config_result = $model_config->where($where_config)->find();

        if(!$config_result or !$config_result['app_id'] or !$config_result['notify_url'] or !$config_result['return_url'] or !$config_result['alipay_public_key'] or !$config_result['merchant_private_key']){
            $this->error("配置信息不完整");
        }

        //生成订单编号
        $out_trade_no = uniqid();

        //填写需要写入的数据
        $data = array(
            'user_id'=>1,
            'order_no'=> $out_trade_no,
            'type'=>'支付宝支付',
            'money'=>$money,
            'status'=>0,
            'create_time'=>time(),
        );

        //写入充值记录表
        $model =  D('Record');

        $result  = $model->data($data)->add();

        /*  此处为了调用方便，我自己封装了一个类。该类只是对官方demo进行了简单的处理
            该类所在路径为 AlipayClass/ThinkPHP/Library/Org/Alipay/get_pay_data.class.php
            以下采用的方法为Tp的自动加载方法，具体使用方法请参见该地址：
            http://document.thinkphp.cn/manual_3_2.html#autoload
        */
        Vendor('get_pay_data','ThinkPHP/Library/Org/Alipay/','.class.php');

        //此处需传递支付金额以及订单号两个参数
        $alipay = Alipay::go_pay($money,$out_trade_no);

        echo json_encode($alipay);exit;

	}

    //该方法为接受支付宝回传参数以及修改对应订单状态的方法
    public function get_post(){    
        /*
            支付宝回传的数据为数组格式，再获取其中的数据的时候，可直接通过$_POST['参数名']获取到。
            告诉你一个小秘密，如果你使用json解析之后的数据的话，是无效的，别问我为什么知道 ﾍ(;´Д｀ﾍ)ﾍ(;´Д｀ﾍ)
        */
        $data = $_POST;

        /*
            此处可将支付宝回调的参数，转换为json格式，写入数据库。
            下次直接拿出来使用即可，记得转换为数组格式哦
            以下为写数据库操作
        */

        /*

            //先假设返回数据为 $data = array("aa"=>"bb","cc"=>"dd");
            $model_log = D('Log');

            $new_data_log['pay_data'] = json_encode($data);
            $new_data_log['create_time'] = date('Y-m-d H:i:s',time());

            $model_log->data($new_data_log)->add();
        */

        /*
            写入成功，此时去拷贝写入的数据 pay_data
            转换为数组进行使用
        */
         
         /*
             //该数据为数据库取出的假设数据 $pay_data = '{"aa":"bb","cc":"dd"}';
             $data = json_decode($pay_data,true);   
         */

        //此处和第44行一样
        Vendor('get_pay_data','ThinkPHP/Library/Org/Alipay/','.class.php');

        //该方法为我进行的二次封装，实际还是调用支付宝官方demo的验签功能
        $result = Alipay::check_sign($data);

        //校验成功
        if($result){
            //根据订单号查询充值记录
            $model = D('Record');

            $where_order['order_no'] = array('eq',$_POST['out_trade_no']);
            $order_info = $model->where($where_order)->find();

            //该订单不存在
            if(!$order_info){
                echo "fail";
                return;
            //回传的金额不存在或者和查询到的订单的金额不一致    
            }else if(!$_POST['total_amount'] || $_POST['total_amount'] != $order_info['money']){
                echo "fail";
                return;
            //回传状态不正确                
            }else if($_POST['trade_status'] != "TRADE_SUCCESS"){
                echo "fail";
                return;
            }else{
                //根据订单号修改订单表数据
                $result = $this->change_order_data($_POST['out_trade_no']);

                if($result == true){
                    echo "success";
                }else{
                    echo "fail";
                    return;
                }
            }
        }else {
            //验证失败
            echo "fail";
        }

    }

    ////根据订单号修改订单表数据以及用户表金额数据
    public function change_order_data($out_trade_no){
        //根据订单号查询充值记录
        $model = D('Record');

        $where_order['order_no'] = array('eq',$out_trade_no);
        $order_info = $model->where($where_order)->find();

        $new_data['status'] = 1;
        $where_order['order_no'] = array('eq',$out_trade_no);
        $flag1 = $model->where($where_order)->data($new_data)->save();//修改订单状态

        //查询用户信息
        $model_user = D('Users');
        $where_user['id'] = array('eq',$order_info['user_id']);
        $where_user['user_type_id'] = array('eq',$order_info['user_type_id']);

        $user_info = $model_user->where($where_user)->find();

        //修改用户余额
        $flag2 = $model_user->where($where_user)->setInc('money',$order_info['total_fee']);

       if($flag1 and $flag2){
            return true;
       }else{
            return false;
       }

        
    }

    //该方法显示记录页面
    public function record(){
    	//获取账户余额
    	$model_users = D('Users');
    	$user = $model_users->where('id=1')->find();

    	$model = D('Record'); // 实例化User对象
		$count      = $model->count();// 查询满足要求的总记录数
		$Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(10)
		$show       = $Page->show();// 分页显示输出
		// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$list = $model->order('create_time DESC')->relation(true)->limit($Page->firstRow.','.$Page->listRows)->select();

		$this->assign('user',$user);
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
    	$this->display();	
    }
}
