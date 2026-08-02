/**
 * 日付表記にフォーマットする関数
 * 例 → 7月12日14時
 */
export function formatPostDate(dateStr: string): string {
  const postDate = new Date(dateStr)
  const month = postDate.getMonth() + 1
  const day = postDate.getDate()
  const hour = postDate.getHours()

  return `${month}月${day}日${hour}時`
}
