<?php
/**
 * @copyright Copyright(c) 2011 jooyea.cn
 * @file paymentplugin.php
 * @brief 支付插件基类
 * @author chendeshan
 * @date 2013/5/7 20:07:59
 * @version 1.0.0
 */

 /**
 * @class PaymentPlugin
 * @brief 支付插件抽象类
 */
abstract class paymentPlugin
{
	public $method            = "post";//表单提交模式
	public $name              = null;  //支付插件名称
	public $version           = 1.0;   //版本
	public $callbackUrl       = '';    //支付完成后，回调地址
	public $serverCallbackUrl = '';    //异步通知地址

	/**
	* @brief 构造函数
	* @param $payment_id 支付方式ID
	*/
	public function __construct($payment_id)
	{
		//回调函数地址
		$this->callbackUrl       = IUrl::getHost().IUrl::creatUrl("/block/callback/_id/".$payment_id);

		//回调业务处理地址
		$this->serverCallbackUrl = IUrl::getHost().IUrl::creatUrl("/block/server_callback/_id/".$payment_id);
	}

	/**
	 * 异步通知停止
	 */
	abstract public function notifyStop();

	/**
	 * 获取提交地址
	 * @return string Url提交地址
	 */
	abstract public function getSubmitUrl();

	/**
	 * 获取要发送的数据数组结构
	 * @param $payment array 要传递的支付信息
	 * @return array
	 */
	abstract public function getSendData($paymentInfo);

	/**
	 * 同步支付回调
	 * @param $ExternalData array  支付接口回传的数据
	 * @param $paymentId    int    支付接口ID
	 * @param $money        float  交易金额
	 * @param $message      string 信息
	 * @param $orderNo      string 订单号
	 */
	abstract public function callback($ExternalData,&$paymentId,&$money,&$message,&$orderNo);

	/**
	 * 同步支付回调
	 * @param $ExternalData array  支付接口回传的数据
	 * @param $paymentId    int    支付接口ID
	 * @param $money        float  交易金额
	 * @param $message      string 信息
	 * @param $orderNo      string 订单号
	 */
	abstract public function serverCallback($ExternalData,&$paymentId,&$money,&$message,&$orderNo);
}