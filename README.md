# 72CRM-11.0-PHP

### 悟空CRM介绍

悟空软件长期为企业提供企业管理软件(CRM/HRM/OA/ERP等)的研发、实施、营销、咨询、培训、服务于一体的信息化服务。悟空软件以高科技为起点，以技术为核心、以完善的售后服务为后盾，秉承稳固与发展、求实与创新的精神，已为国内外上千家企业提供服务。

悟空的发展受益于开源，也会回馈于开源。2019年，悟空CRM会继续秉承“拥抱开放、合作共赢、创造价值”的理念，在开源的道路上继续砥砺前行，和更多的社区开发者一起为国内外开源做出积极贡献。

官网：[http://www.5kcrm.com](http://www.5kcrm.com/)

官网：[http://www.72crm.com](http://www.72crm.com/)

论坛：[http://bbs.72crm.net](http://bbs.72crm.net/)

演示地址：[demo11.5kcrm.net](http://demo11.5kcrm.net/)(帐号：18888888888   密码：123456)

QQ群交流群⑩群：[486745026](https:////shang.qq.com/wpa/qunwpa?idkey=f4687b809bf63f08f707aa1c56dee8dbcb9526237c429c4532222021d65bf83c)

JAVA版下载地址：[https://gitee.com/wukongcrm/72crm-java](https://gitee.com/wukongcrm/72crm-java)


扫码添加小悟官方客服微信，邀您加入千人微信交流群：

<img src="https://images.gitee.com/uploads/images/2019/1231/115927_f9c580c8_345098.png" width="200">

关注悟空CRM公众号，了解更多悟空资讯

<img src="https://images.gitee.com/uploads/images/2019/1202/135713_d3566c6a_345098.jpeg" width="200">


 :boom:  :boom:  :boom: 注：悟空CRM采用全新的前后端分离模式，本仓库代码中已集成前端vue打包后文件，  **可免去打包操作，无需运行前端** 。如需调整前端代码，请单独下载前端代码




悟空CRM采用全新的前后端分离模式，本仓库代码中已集成前端vue打包后文件，可免去打包操作

如需调整前端代码，请单独下载前端代码，前端代码在根目录的ux文件夹中

## 主要技术栈

后端框架：ThinkPHP 5.0.2

前端MVVM框架：Vue.JS 2.5.x 

路由：Vue-Router 3.x 

数据交互：Axios 

UI框架：Element-UI 2.6.3 

悟空crm9.0的运行环境要求PHP5.6以上


## 一键安装

代码中已集成前端vue打包后文件，可免去打包操作：
以本地（phpstudy集成环境）搭建举例：
下载悟空CRM9.0开源版，在服务器根目录（www目录）下创建72crm文件夹，并放置代码； 浏览器访问

`http://localhost/72crm/index.php/admin/install/index.html `

根据安装提示步骤，完成悟空CRM9.0 的部署安装



## 开发依赖（需个性化安装或调整前端代码请按照以下教程，一键安装用户可忽略）

### 数据交互 
数据交互通过axios以及RESTful架构来实现 
用户校验通过登录返回的auth_key放在header 
值得注意的一点是：跨域的情况下，会有预请求OPTION的情况

### Server搭建 
服务端使用的框架为thinkphp5.0.2，搭建前请确保拥有lamp/lnmp/wamp环境。

这里所说的搭建其实就是把server框架放入WEB运行环境，并使用80端口。
导入服务端根文件夹数据库文件public/sql/5kcrm.sql，并修改config/database.php配置文件。

### 配置要求
PHP >= 5.6.0 
当访问 http://localhost/, 出现“悟空软件”即代表后端接口搭建成功。
### 前端部署
安装node.js 前端部分是基于node.js上运行的，所以必须先安装`node.js`，版本要求为6.0以上

使用npm安装依赖 下载悟空CRM9.0前端代码； 可将代码放置在后端同级目录frontend，执行命令安装依赖：

    npm install

修改内部配置 修改请求地址或域名：config/dev.env.js里修改BASE_API（开发环境服务端地址，默认localhost） 修改自定义端口：config/index.js里面的dev对象的port参数（默认8080，不建议修改）

### 运行前端

     npm run dev

注意：前端服务启动，默认会占用8080端口，所以在启动前端服务之前，请确认8080端口没有被占用。
程序运行之前需搭建好Server端





## 悟空CRM功能模块预览

以下为悟空CRM11.0.0 部分功能系统截图

![仪表盘](https://images.gitee.com/uploads/images/2021/0206/112721_6e50397d_345098.png "仪表盘.png")
![客户列表](https://images.gitee.com/uploads/images/2021/0206/112822_4ab4eb50_345098.png "客户列表.png")
![客户详情](https://images.gitee.com/uploads/images/2021/0206/112842_d69aff0f_345098.png "客户详情.png")
![商机详情](https://images.gitee.com/uploads/images/2021/0206/112902_c38751fe_345098.png "商机详情.png")
![任务详情](https://images.gitee.com/uploads/images/2021/0206/112924_175278e2_345098.png "任务详情.png")
![商业智能](https://images.gitee.com/uploads/images/2021/0206/112938_0cbc95b7_345098.png "商业智能.png")
![审批](https://images.gitee.com/uploads/images/2021/0206/113001_bfcbee0a_345098.png "审批.png")
![自定义字段](https://images.gitee.com/uploads/images/2021/0206/113019_7894e7ed_345098.png "自定义字段.png")
![字段授权](https://images.gitee.com/uploads/images/2021/0206/113030_cefa8932_345098.png "字段授权.png")


