import { FaChevronLeft, FaChevronRight } from 'react-icons/fa'

import { PaginationMeta } from '../types/post'
import '../styles/Pagination.css'

/**
 * 一覧のページ送りコンポーネント
 *
 * 表示するか否か（読み込み中やエラー時に古い件数を見せない等）は、通信状態を持つ呼び出し元で判断する。
 *
 * @param meta - 表示中の一覧のページ情報
 * @param onMovePage - 移動先のページ番号を受け取り、そのページを取得する処理
 * @returns JSX.Element
 */
function Pagination({
  meta,
  onMovePage,
}: {
  meta: PaginationMeta
  onMovePage: (page: number) => void
}) {
  /** 前後ページへ移動（ボタンの disabled をすり抜けた場合に備え、範囲外は呼び出し元へ通知しない） */
  const movePage = (page: number) => {
    if (page < 1 || page > meta.last_page) {
      return
    }

    onMovePage(page)
  }

  return (
    <div className="post-pagination">
      <button
        className="post-pagination-button"
        type="button"
        onClick={() => movePage(meta.current_page - 1)}
        disabled={meta.current_page <= 1}
        aria-label="前のページ"
      >
        <FaChevronLeft />
      </button>
      <span className="post-pagination-status">
        {`${meta.from}-${meta.to} / ${meta.total}件`}
        <span className="post-pagination-page">
          ページ {meta.current_page} / {meta.last_page}
        </span>
      </span>
      <button
        className="post-pagination-button"
        type="button"
        onClick={() => movePage(meta.current_page + 1)}
        disabled={!meta.has_more_pages}
        aria-label="次のページ"
      >
        <FaChevronRight />
      </button>
    </div>
  )
}

export default Pagination
