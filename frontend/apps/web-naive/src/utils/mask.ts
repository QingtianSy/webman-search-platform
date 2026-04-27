/**
 * 敏感字段脱敏工具。用于日志、账号采集、API Key 等页面的列表展示。
 * 详情 Drawer 里仍以明文展示（管理员/本人视角）。
 */

export function maskEmail(email?: null | string): string {
  if (!email) return '';
  const at = email.indexOf('@');
  if (at <= 1) return email;
  const name = email.slice(0, at);
  const domain = email.slice(at);
  const head = name.slice(0, Math.min(2, name.length));
  return `${head}${'*'.repeat(Math.max(1, name.length - 2))}${domain}`;
}

export function maskMobile(mobile?: null | string): string {
  if (!mobile) return '';
  const s = String(mobile);
  if (s.length < 7) return s;
  return `${s.slice(0, 3)}****${s.slice(-4)}`;
}

/** 前 N + **** + 后 M；默认 8/4。 */
export function maskKey(key?: null | string, head = 8, tail = 4): string {
  if (!key) return '';
  if (key.length <= head + tail) return key;
  return `${key.slice(0, head)}****${key.slice(-tail)}`;
}

/** 第三方平台账号：前 6 + ****。 */
export function maskAccount(account?: null | string): string {
  if (!account) return '';
  if (account.length <= 6) return `${account}****`;
  return `${account.slice(0, 6)}****`;
}
