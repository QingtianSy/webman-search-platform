/**
 * 日期/时间格式化。admin 端多处 NDatePicker daterange 给的是 [ts,ts]，后端要 "YYYY-MM-DD HH:mm:ss"。
 * 与用户端同名函数口径一致，避免 admin 各页各写一份。
 */

export function pad(n: number): string {
  return `${n}`.padStart(2, '0');
}

export function formatDateTime(ts: null | number | undefined): null | string {
  if (!ts) return null;
  const d = new Date(ts);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

/** 仅日期 YYYY-MM-DD */
export function formatDate(ts: null | number | undefined): null | string {
  if (!ts) return null;
  const d = new Date(ts);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function rangeToParams(range: null | [number, number] | undefined): {
  start_time?: string;
  end_time?: string;
} {
  if (!range || range.length !== 2) return {};
  const s = formatDateTime(range[0]);
  const e = formatDateTime(range[1]);
  return {
    start_time: s ?? undefined,
    end_time: e ?? undefined,
  };
}
