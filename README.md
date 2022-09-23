# imi-migration

[![Latest Version](https://img.shields.io/packagist/v/imiphp/imi-migration.svg)](https://packagist.org/packages/imiphp/imi-migration)
[![Php Version](https://img.shields.io/badge/php-%3E=7.4-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.8.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![imi License](https://img.shields.io/badge/license-MulanPSL%202.0-brightgreen.svg)](https://github.com/imiphp/imi-migration/blob/master/LICENSE)

## 介绍

此项目是 imi 框架的数据库迁移组件。

> 正在测试阶段，使用时请先确认 SQL 无误后再执行，本项目不对删库删数据负责。

## 安装

`composer require imiphp/imi-migration:~2.1.0`

## 使用说明

### 同步表结构

将数据库中的数据表结构升级为模型中定义的结构。

```shell
vendor/bin/imi-swoole migration/patch -f
```

### 生成同步结构 SQL 语句

**输出到命令行：**

```shell
vendor/bin/imi-swoole migration/patch
```

**保存到文件：**

```shell
vendor/bin/imi-swoole migration/patch -f "文件名"
```

### 通用参数

#### 指定连接池

```shell
vendor/bin/imi-swoole migration/命令 --poolName "连接池名"
```

> 不指定时使用默认连接池

#### 指定连接参数

```shell
vendor/bin/imi-swoole migration/命令 --driver "PdoMysqlDriver" --options "host=127.0.0.1&port=3306&username=root&password=root"
```

## 免费技术支持

QQ群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题会有人解答和修复。

## 运行环境

* [PHP](https://php.net/) >= 7.4
* [Composer](https://getcomposer.org/) >= 2.0
* [Swoole](https://www.swoole.com/) >= 4.8.0
* [imi](https://www.imiphp.com/) >= 2.1

## 版权信息

`imi-migration` 依赖 [phpmyadmin/sql-parser](https://github.com/phpmyadmin/sql-parser)，所以开源协议受到污染，必须是 GPL-2.0，所有基于本项目的代码都要开源。

建议仅将此组件作为独立工具安装使用，不要在项目中调用此项目中的任意代码，这样就不受开源协议污染了！

## 捐赠

<img src="https://cdn.jsdelivr.net/gh/imiphp/imi@2.1/res/pay.png"/>

开源不求盈利，多少都是心意，生活不易，随缘随缘……
