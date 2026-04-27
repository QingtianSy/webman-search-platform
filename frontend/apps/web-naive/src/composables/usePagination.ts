import { ref } from 'vue';

/**
 * 分页状态复用 hook。对齐后端分页响应 {list, total, page, page_size}。
 * 用法：
 *   const { page, pageSize, total, onPageChange, onPageSizeChange, bind } = usePagination();
 *   <NDataTable remote :pagination="bind.value" />
 */
export function usePagination(initialSize = 10) {
  const page = ref(1);
  const pageSize = ref(initialSize);
  const total = ref(0);

  function onPageChange(p: number) {
    page.value = p;
  }

  function onPageSizeChange(size: number) {
    pageSize.value = size;
    page.value = 1;
  }

  function reset() {
    page.value = 1;
    pageSize.value = initialSize;
    total.value = 0;
  }

  function apply(resp: { total?: number; page?: number; page_size?: number }) {
    if (typeof resp?.total === 'number') total.value = resp.total;
    if (typeof resp?.page === 'number') page.value = resp.page;
    if (typeof resp?.page_size === 'number') pageSize.value = resp.page_size;
  }

  return {
    page,
    pageSize,
    total,
    onPageChange,
    onPageSizeChange,
    reset,
    apply,
  };
}
