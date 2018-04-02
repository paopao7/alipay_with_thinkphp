<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
	<title>支付页面</title>
	<link href="/Public/sources/css/common.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="/Public/sources/js/jquery.min.js"></script>
	<script type="text/javascript" src="/Public/sources/js/layer/layer.js"></script>
	<script type="text/javascript" src="/Public/sources/js/common.js"></script>
</head>
<body>
	<div class="pay_backgound_box">
		<form action="<?php echo U('Home/Index/pay_action');?>" id="form" method='POST'>
			<div class="pay_box">
				<div class="pay_header_box">
					<div class="pay_header_left_text">
						电脑网站支付<br />Demo
					</div>
					<div class="pay_header_right_text">
						支付宝
					</div>
				</div>
				<div class="pay_body_box">
					<span class="pay_username_item">账户名称</span>
					<span class="pay_username">150****8888</span>
					<span class="pay_money_item">账户余额</span>
					<span class="pay_money">￥ <?php echo ($user["money"]); ?></span>
					<span class="pay_recharge_item">充值金额</span>
					<input type="input" name="money" class="pay_recharge_input" />
					<span class="pay_tips">tips：充值金额为精确到小数点后两位的正整数</span>
					<span class="pay_record_item">充值记录</span>
					<span class="pay_record"><a href="<?php echo U('record');?>">点我</a></span>
				</div>
			</div>
			<input class="pay_action" type="submit" value="走你┏ (゜ω゜)=☞"/>
		</form>
	</div>
</body>
</html>