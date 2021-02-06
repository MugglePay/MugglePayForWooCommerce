[English](./readme.md) | 简体中文

# DFOXT MugglePay For WooCommerce
> MugglePay是为有在线收款需求的商家提供的一站式收款解决方案。

## 安装方式

### 通过 WordPress.org 官方插件安装

这个插件可以在 [WordPress.org插件库] 上找到，可以直接从那里安装，也可以从你网站的管理仪表板安装。

#### 通过 WordPress 仪表板
1. 访问 ‘插件 > 安装插件’
2. 搜索 ‘DFOXT MugglePay For WooCommerce’
3. 从“插件”页面激活 ‘DFOXT MugglePay For WooCommerce’

#### 通过 WordPress.org 插件库
1. 从 <https://wordpress.org/plugins/dfoxm-mugglepay-for-woocommerce> 下载DFOXT MugglePay For WooCommerce
2. 使用您喜欢的方法（ftp，sftp，scp等）上传到您的 ‘/wp-content/plugins/‘ 目录下
3. 从‘插件”页面激活 ‘DFOXT MugglePay For WooCommerce’

### 从这个仓库下载

在Github存储库中，单击“克隆”或“下载”按钮，然后下载存储库的zip文件，或直接通过命令行对其进行克隆。

在WordPress管理面板中，转到插件>添加新内容，然后单击页面顶部的上传插件按钮。

或者，您可以将zip文件移动到网站的 ‘/wp-content/plugins/‘ 文件夹中并解压缩。

然后，您需要转到WordPress管理插件页面，然后激活插件。

## 插件配置

你需要在 [[DFOXT MugglePay For WooCommerce](https://merchants.mugglepay.com/user/register?ref=MP9237F1193789)] 设置一个账户.

在WordPress管理区域内，转到WooCommerce > 设置 > 付款页面，您将在付款网关表中看到MugglePay。

单击右侧的“管理”按钮将带您进入设置页面，您可以在其中为商店配置插件。

**注意：如果您运行的WooCommerce版本早于3.4.x，则MugglePay标签将位于WooCommerce > 设置 > 结帐标签下面**

## 设置

### 启用 / 禁用

控制是否启用此支付方式.

### 标题

结帐页面上付款方式的标题.

### 描述 

结帐页面上的付款方式说明 

### API Key

Register your MugglePay merchant accounts with your invitation code and get your API key at [Merchants Portal](https://merchants.mugglepay.com/user/register?ref=MP9237F1193789). You will find your API Auth Token (API key) for authentication. [MORE](https://merchants.mugglepay.com/user/register?ref=MP9237F1193789)

![Setting Page](https://github.com/hoythan/MugglePayForWooCommerce/blob/main/assets/screenshot-1.jpg)

## Prerequisites

To use this plugin with your WooCommerce store you will need:

* [WordPress] (tested up to 5.6.0)
* [WooCommerce] (tested up to 4.7.1)


## License

This project is licensed under the Apache 2.0 License

## Changelog

## 1.0.2 ##
* 支持每五分钟自动检查订单支付状态并更新订单状态。

## 1.0.1 ##
* DFOXT MugglePay For WooCommerce