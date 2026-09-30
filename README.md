# jlpay

中介支付系统（Phase 1）：Laravel 13 + dcat-plus admin + MySQL。

## 本地启动

1. 复制 `.env.example` 为 `.env`，配置 MySQL（库名建议 `jlpay`）
2. `composer install`
3. `php artisan key:generate`
4. `php artisan migrate`
5. `php artisan serve`，后台默认路径 `/admin`（默认账号见 dcat-plus 安装输出，一般为 `admin` / `admin`）

## Phase 1 范围

- 商户管理（`merchant_serial` / `md5key` / 回调地址）
- 商户 API IP 白名单
- 商户额度与调额流水
- 商户 API 验签 / IP 中间件占位（`/api/v1`，业务接口待上游文档）

## 默认管理员

安装后默认账号：`admin` / `admin`（请尽快修改密码）
