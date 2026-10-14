# BahtFlow Pay 商户 API

商户对接说明。基础路径为 `/api/v1`。金额单位为泰铢（THB）。

商户只调用本文接口。存款创建成功后，把返回的 `uploadUrl` 交给玩家自行打开并上传转账凭证。

## 基础 URL

| 环境 | 基础 URL |
|---|---|
| UAT | `https://bfpay-api.sbx888.net/api/v1` |
| PROD | `https://api.bahtflow.com/api/v1` |

生产地址由 BahtFlow Pay 另行提供。下文路径均相对于该基础路径。

## 通用规则

### 认证

每个请求都是 `POST`，`Content-Type: application/json`，并且必须包含：

| 字段 | 位置 | 说明 |
|---|---|---|
| `merchant_serial` | body | BahtFlow Pay 分配的商户标识 |
| `sign` | body | 请求签名 |

商户必须处于启用状态。停用商户与错误的 `merchant_serial` 一样返回 403。

### 签名（`sign`）

1. 取除 `sign` 以外的全部请求参数。
2. 按参数名升序排序（`ksort`）。
3. 跳过值为 `null` 或空字符串 `""` 的参数。
4. 数组转为 JSON（`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`，键排序）。
5. 将其余值转为字符串。
6. 用 `http_build_query(..., '', '&', PHP_QUERY_RFC3986)` 生成查询字符串。
7. 在末尾直接拼接商户 `md5key`（无分隔符）。
8. `sign = md5(结果)`，小写十六进制。

**示例**

参数：

```json
{
  "merchant_serial": "M001",
  "merchantOrderNo": "D20260930001",
  "amount": 100,
  "fromAccount": "0987654321",
  "fromBankCode": "004",
  "fullName": "Tester"
}
```

若 `md5key` 为 `abc123`，签名原文为：

```text
amount=100&fromAccount=0987654321&fromBankCode=004&fullName=Tester&merchantOrderNo=D20260930001&merchant_serial=M001abc123
```

`sign` 为 `32a9925a8f2d48bbbd2d0f2cd66187a8`。

含空格或非 ASCII 的字段会按 RFC 3986 编码后再参与签名。请求里发送的值必须和参与签名的值一致。

### IP 白名单

若该商户配置了至少一条启用中的 IP，请求来源 IP 必须命中其中之一。未配置任何 IP 时不限制来源。

不在白名单时返回 HTTP 403：

```json
{
  "respCode": "9999",
  "respMsg": "IP address not whitelisted."
}
```

### 响应格式

**成功**（HTTP 200）

```json
{
  "respCode": "0000",
  "respMsg": "success",
  "data": {}
}
```

**失败**

```json
{
  "respCode": "0002",
  "respMsg": "merchantOrderNo already exists."
}
```

| HTTP | 含义 |
|---|---|
| 200 | 业务成功，看 `respCode=0000` |
| 400 | 业务失败，`respCode=9999` |
| 403 | 商户、签名或 IP 校验失败，`respCode=9999` |
| 422 | 参数校验失败，或商户单号重复，`respCode=0002` |

创建被拒绝时，HTTP 400，`data.upstreamCode` 为错误码，`data.upstreamData` 为附加数据。

| respCode | 说明 |
|---|---|
| `0000` | 成功 |
| `0002` | 参数无效，或 `merchantOrderNo` 已存在 |
| `9999` | 签名/IP/商户错误、额度不足、订单不存在、订单已终态、缺少回调地址，或创建被拒绝 |

### 商户单号

`merchantOrderNo` 由商户生成，同一商户下不可重复（存款和取款共用这一约束）。最长 64 个字符。

### 回调地址

单笔请求可传 `callbackUrl`。不传则使用后台商户资料里的回调地址。两者都为空时，创建存款/取款会失败。

BahtFlow Pay 在订单进入需要通知的状态后，向该地址 `POST` JSON。回调使用同一套签名算法，`sign` 放在 body 里。

### 银行代码

`fromBankCode`、`toBankCode` 使用泰国银行代码，数字码（如 `004`）或短码（如 `KBANK`）均可。账号位数需符合该银行规则。

| 数字码 | 短码 | 银行 |
|---|---|---|
| `002` | `BBL` | 盘谷银行 |
| `004` | `KBANK` | 开泰银行 |
| `006` | `KTB` | 泰京银行 |
| `011` | `TTB` | TMB他纳昌银行 |
| `014` | `SCB` | 暹罗商业银行 |
| `017` | `CITI` | 花旗银行 |
| `020` | `SCBT` | 渣打银行(泰国) |
| `022` | `CIMB` | 联昌国际银行(泰国) |
| `024` | `UOB` | 大华银行 |
| `025` | `BAY` | 大城银行 |
| `030` | `GSB` | 政府储蓄银行 |
| `031` | `HSBC` | 汇丰银行 |
| `033` | `GHB` | 政府住房银行 |
| `034` | `BAAC` | 农业合作银行 |
| `066` | `IBANK` | 泰国伊斯兰银行 |
| `067` | `TISCO` | TISCO银行 |
| `069` | `KKP` | 甲那金帕特拉银行 |
| `070` | `ICBC` | 工商银行(泰国) |
| `071` | `TCRB` | 泰国信贷银行 |
| `073` | `LHFG` | LH银行 |
| `088` | `CLICX` | CLICX |

---

## 1. 创建存款

创建一笔存款并自动撮合。成功后把 `uploadUrl` 交给玩家。玩家按 `matchedWithdraws` 中的账户转入准确金额，并在过期前打开该链接上传凭证。

此接口不改变商户额度。额度在存款回调 `complete` 时增加。

| | |
|---|---|
| 方法 | `POST` |
| 路径 | `/deposit` |

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `merchant_serial` | string | 是 | 商户标识 |
| `sign` | string | 是 | 签名 |
| `merchantOrderNo` | string | 是 | 商户单号，最长 64 |
| `amount` | number | 是 | 金额，大于 0 |
| `fromAccount` | string | 是 | 玩家付款账号 |
| `fromBankCode` | string | 是 | 付款银行代码 |
| `fullName` | string | 是 | 付款账户姓名 |
| `fullNameEn` | string | 否 | 付款账户英文姓名 |
| `callbackUrl` | string | 否 | 本笔回调地址，须为 URL |
| `returnUrl` | string | 否 | 凭证页结束后的跳转地址 |

### 成功 `data`

| 字段 | 说明 |
|---|---|
| `merchantOrderNo` | 商户单号 |
| `ref` | 平台订单号，后续回调使用 |
| `amount` | 金额字符串 |
| `status` | `pending` |
| `expiredDate` | 过期时间 |
| `uploadUrl` | 玩家上传凭证的页面 |
| `uploadToken` | 上传页令牌 |
| `matchedWithdraws` | 玩家应转入的目标账户列表 |

`matchedWithdraws` 每一项包含 `ref`、`amount`、`toAccount`、`toBankCode`、`toAccountName`，以及银行名称字段。

```json
{
  "respCode": "0000",
  "respMsg": "success",
  "data": {
    "merchantOrderNo": "D20260930001",
    "ref": "JINLP-D-20260402-120100-YYYYYY",
    "amount": "100",
    "status": "pending",
    "expiredDate": "2026-04-02T12:16:00.000Z",
    "uploadUrl": "https://slip.example/JINLP-D-20260402-120100-YYYYYY?t=TOKEN",
    "uploadToken": "TOKEN",
    "matchedWithdraws": [
      {
        "ref": "JINLP-W-20260402-120000-XXXXXX",
        "amount": 100,
        "toAccount": "1234567890",
        "toBankCode": "004",
        "toAccountName": "ACCOUNT NAME"
      }
    ]
  }
}
```

---

## 2. 取消存款

取消一笔尚未进入终态的存款。建议主动取消，不要等超时。

| | |
|---|---|
| 方法 | `POST` |
| 路径 | `/deposit/cancel` |

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `merchant_serial` | string | 是 | 商户标识 |
| `sign` | string | 是 | 签名 |
| `merchantOrderNo` | string | 是 | 创建存款时的商户单号 |

### 成功 `data`

```json
{
  "respCode": "0000",
  "respMsg": "success",
  "data": {
    "merchantOrderNo": "D20260930001",
    "ref": "JINLP-D-20260402-120100-YYYYYY",
    "status": "cancelled"
  }
}
```

订单不存在返回 `Order not found.`。订单已是终态返回 `Order is already final.`。取消不会扣减或增加额度。

---

## 3. 创建取款

创建成功后立即锁定全额，额度随之减少。创建被拒绝时不会锁定额度，也不会建立订单，接口直接返回错误，不发回调。

| | |
|---|---|
| 方法 | `POST` |
| 路径 | `/withdraw` |

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `merchant_serial` | string | 是 | 商户标识 |
| `sign` | string | 是 | 签名 |
| `merchantOrderNo` | string | 是 | 商户单号，最长 64 |
| `amount` | number | 是 | 金额，大于 0，且不超过当前额度 |
| `toAccount` | string | 是 | 收款账号 |
| `toBankCode` | string | 是 | 收款银行代码 |
| `toAccountName` | string | 是 | 收款账户姓名 |
| `toAccountNameEn` | string | 否 | 收款账户英文姓名 |
| `callbackUrl` | string | 否 | 本笔回调地址，须为 URL |

### 成功 `data`

```json
{
  "respCode": "0000",
  "respMsg": "success",
  "data": {
    "merchantOrderNo": "W20260930001",
    "ref": "JINLP-W-20260402-120000-XXXXXX",
    "amount": "100",
    "status": "pending",
    "expiredDate": "2026-04-02T12:30:00.000Z"
  }
}
```

额度不足时 HTTP 400：

```json
{
  "respCode": "9999",
  "respMsg": "Insufficient merchant credit."
}
```

---

## 4. 查询余额

返回该商户在 BahtFlow Pay 的可用额度。

| | |
|---|---|
| 方法 | `POST` |
| 路径 | `/balance` |

### 请求体

| 字段 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `merchant_serial` | string | 是 | 商户标识 |
| `sign` | string | 是 | 签名 |

### 成功 `data`

```json
{
  "respCode": "0000",
  "respMsg": "success",
  "data": {
    "creditBalance": "400"
  }
}
```

---

## 5. 商户回调

BahtFlow Pay 向本笔 `callbackUrl`（或商户默认回调地址）发起 `POST`，`Content-Type: application/json`。

请返回 HTTP 2xx。BahtFlow Pay 目前只投递一次；超时 8 秒或非 2xx 记为通知失败，不会自动重试。

请用 `ref` + `status` 做幂等。终态不要退回中间态。

### 回调体

| 字段 | 说明 |
|---|---|
| `type` | `deposit` 或 `withdraw` |
| `merchantOrderNo` | 商户单号 |
| `ref` | 平台订单号 |
| `status` | 见下表 |
| `amount` | 订单金额 |
| `completedAmount` | 已完成金额 |
| `remainingAmount` | 未完成金额 |
| `sign` | 用同一套签名算法计算，密钥为该商户 `md5key` |

```json
{
  "type": "deposit",
  "merchantOrderNo": "D20260930001",
  "ref": "JINLP-D-20260402-120100-YYYYYY",
  "status": "complete",
  "amount": "100",
  "completedAmount": "100",
  "remainingAmount": "0",
  "sign": "..."
}
```

### 存款状态

| status | 何时通知 | 额度 |
|---|---|---|
| `complete` | 存款完成 | 按订单金额增加一次 |
| `cancelled` | 商户取消，或订单被取消 | 不变 |
| `cancelled_by_admin` | 管理员强制取消 | 不变 |
| `timeout` | 超时 | 不变 |

创建存款被拒绝时，接口同步返回错误，不发回调。

### 取款状态

创建成功时已扣除全额。最终回调只退回未完成部分：`退回金额 = 锁定金额 - completedAmount`。

| status | 何时通知 | 额度 |
|---|---|---|
| `update` | 中间进度。通知当前 `completedAmount` 和 `remainingAmount` | 额度不变 |
| `complete` | 全额完成 | 不再退回 |
| `complete_below_min` | 剩余金额低于最小限额后自动完成 | 退回未完成部分 |
| `timeout` | 超时 | 退回未完成部分 |
| `cancelled` | 取消 | 退回未完成部分 |
| `cancelled_by_admin` | 管理员强制取消 | 退回未完成部分 |
| `failed` | 订单失败 | 退回未完成部分 |

取款的 `update` 和终态都会通知商户。每次回调都是当前进度，请用最新一次的 `completedAmount`、`remainingAmount` 覆盖本地记录，不要把多次 `update` 的金额累加。订单已经是终态后，更晚到达的回调会被忽略。额度只在终态结算。

### 额度示例

取款 100，商户原额度 500：

1. 创建成功，额度变为 400。
2. 最终 `completedAmount=40`、`remainingAmount=60`，退回 60，额度变为 460。
